<?php

/**
 * 画像加工クラス
 * ※Imagickインストール済みの環境のみ動作
 *
 * @author    Noriyoshi Takahashi
 * @copyright Copyright (c) 2015, Noriyoshi Takahashi
 * @version   1.0 (2015.05.12 created)
 */
class Image
{

    /** 一時ディレクトリ */
    protected static $tmp_directory = null;

    /** インスタンスを生成させない */
    protected function __construct() { }

    /** 許可するMIMEタイプ */
    protected static $allow_mime_types = [
        'image/jpeg',
        'image/png',
        'image/gif'
    ];

    /**
     * 一時ディレクトリを設定
     *
     * @param $path
     */
    public static function setTmpDirectory($path)
    {
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
            chmod($path, 0777);
        }
        self::$tmp_directory = $path;
    }

    /**
     * 許可されているMIMEタイプか否か
     *
     * @param string $mime_type
     *
     * @return bool
     */
    public static function isAllowMimeType($mime_type)
    {
        return in_array($mime_type, self::$allow_mime_types);
    }

    /**
     * アップロード画像を一時保存
     *
     * @param  string  $tmp_path
     * @param  int     $resize_width
     * @param  int     $resize_height
     * @param  boolean $is_crop リサイズでなく切り抜きにする場合
     *
     * @return string
     * @throws Exception
     */
    public static function saveTmpUploadImage($tmp_path, $resize_width, $resize_height, $is_crop = false)
    {

        //移動先のファイルパス
        $tmp_file_name = self::$tmp_directory . sha1_file($tmp_path);

        //元画像のImagickオブジェクトを生成
        $origin_im = new Imagick($tmp_path);

        //画像フォーマットを取得
        $origin_format = $origin_im->getimageformat();

        //種類別
        if ($origin_format === 'JPEG') {
            //画像方向を修正して元画像に上書き
            $origin_im = self::reorientationImage($origin_im, $tmp_path);
            $extension = '.jpg';
        } else if ($origin_format === 'PNG') {
            $extension = '.png';
        } else if ($origin_format === 'GIF') {
            $extension = '.gif';
        } else {
            //未対応フォーマット
            return false;
        }

        //ファイル名に拡張子を結合
        $tmp_file_name .= $extension;

        //戻り値用の配列
        $tmp_file_info = array(
            'path'      => $tmp_file_name,
            'extension' => $extension
        );

        //パーミッション変更
        self::changePermission($tmp_file_name);

        //画像サイズ取得
        $origin_width  = $origin_im->getimagewidth();
        $origin_height = $origin_im->getimageheight();

        //画像フォーマット別の処理
        if ($origin_format === 'GIF') {
            $origin_im = self::resizeGifImage($origin_im, $resize_width, $resize_height);
            $origin_im->writeimages($tmp_file_name, true);
        } else {

            if ($is_crop) {
                $origin_im->cropthumbnailimage($resize_width, $resize_height);
                $origin_im->writeimage($tmp_file_name);
            } else {
                //リサイズ
                list($dst_width, $dst_height) = self::getResizeImageSize($resize_width, $resize_height, $origin_width, $origin_height);
                $origin_im->adaptiveresizeimage($dst_width, $dst_height, true);
                //新しいキャンバスを生成して中央に合成する
                $canvas = new Imagick();
                if ($origin_format === 'PNG') {
                    $canvas->newimage($resize_width, $resize_height, 'none');
                } else {
                    $canvas->newimage($resize_width, $resize_height, '#FFFFFF');
                }
                $position_x = ($canvas->getimagewidth() - $origin_im->getimagewidth()) / 2;
                $position_y = ($canvas->getimageheight() - $origin_im->getimageheight()) / 2;
                $canvas->compositeimage($origin_im, Imagick::COMPOSITE_DEFAULT, $position_x, $position_y);
                $canvas->writeimage($tmp_file_name);
            }
        }

        //アップロード一時ファイル削除
        unlink($tmp_path);

        return $tmp_file_info;

    }

    /**
     * アップロード時のエラーをチェック
     *
     * @param int $error
     *
     * @return mixed
     * @throws Exception
     */
    public static function isValidUpload($error)
    {
        //upload_max_filesize超過
        if ($error === UPLOAD_ERR_INI_SIZE) {
            throw new Exception('アップロード出来る制限を超過しています', UPLOAD_ERR_INI_SIZE);
            //MAX_FILE_SIZE超過
        } else if ($error === UPLOAD_ERR_FORM_SIZE) {
            throw new Exception('アップロード出来る制限を超過しています', UPLOAD_ERR_FORM_SIZE);
            //一部しかアップロードできなかった
        } else if ($error === UPLOAD_ERR_PARTIAL) {
            throw new Exception('一部しかアップロード出来ませんでした', UPLOAD_ERR_PARTIAL);
            //ファイルが選択されていない
        } else if ($error === UPLOAD_ERR_NO_FILE) {
            throw new Exception('ファイルが選択されていません', UPLOAD_ERR_NO_FILE);
            //tmpディレクトリが存在しない
        } else if ($error === UPLOAD_ERR_NO_TMP_DIR) {
            throw new Exception('一時保存ディレクトリが存在しません', UPLOAD_ERR_NO_TMP_DIR);
            //書き込みに失敗
        } else if ($error === UPLOAD_ERR_CANT_WRITE) {
            throw new Exception('ファイルの保存に失敗しました', UPLOAD_ERR_CANT_WRITE);
            //PHP拡張モジュールにより中止
        } else if ($error === UPLOAD_ERR_EXTENSION) {
            throw new Exception('システムにより中止されました', UPLOAD_ERR_EXTENSION);
        } else {
            return true;
        }

    }

    /**
     * 画像パスのパーミッションを変更
     *
     * @param string $path
     */
    public static function changePermission($path)
    {
        if (file_exists($path)) {
            chmod($path, 0777);
        }
    }

    /**
     * GIF画像のリサイズ(アニメ対応版)
     *
     * @param  object $input_im Imagickのオブジェクト
     * @param  int    $resize_width
     * @param  int    $resize_height
     *
     * @return object
     */
    public static function resizeGifImage($input_im, $resize_width, $resize_height)
    {
        //イテレーターの初期化（フレームを先頭にする）
        $input_im->setFirstIterator();
        do {
            //リサイズ後のサイズを取得
            list($max_width, $max_height) = self::getResizeImageSize($resize_width, $resize_height, $input_im->getImageWidth(), $input_im->getImageHeight());
            //リサイズする
            $input_im->resizeImage(ceil($max_width), ceil($max_height), imagick::FILTER_POINT, 1);

            $position_x = ceil(($resize_width - $max_width) / 2);
            $position_y = ceil(($resize_height - $max_height) / 2);

            //マットチャンネルを持っていたらそのまま処理
            if ($input_im->getImageMatte()) {
                //フレームのジオメトリ情報の設定
                $input_im->setImagePage(ceil($resize_width), ceil($resize_height), $position_x, $position_y);
                //マットチャンネルを持っていなければ白背景のキャンバスに合成
            } else {
                //新しいキャンバスを生成
                $canvas = new Imagick();
                $canvas->newImage($resize_width, $resize_height, new ImagickPixel('#fFffff'));
                //生成したキャンバスに元画像を合成
                $canvas->compositeimage($input_im, Imagick::COMPOSITE_DEFAULT, $position_x, $position_y);
                $input_im->setImage($canvas);
                //キャンバスを破棄
                $canvas->clear();
            }
        } while ($input_im->nextImage());

        return $input_im->deconstructImages();

    }

    /**
     * アスペクト比を保ったリサイズ値を取得
     *
     * @param  int $dst_width
     * @param  int $dst_height
     * @param  int $src_width
     * @param  int $src_height
     *
     * @return Array
     */
    public static function getResizeImageSize($dst_width, $dst_height, $src_width, $src_height)
    {
        //原寸が変換サイズより小さい場合にリサイズしないようにするにはコメントを解除
        if (($dst_width < $src_width) || ($dst_height < $src_height)) {
            $factor = min(($dst_width / $src_width), ($dst_height / $src_height));

            return array(
                ($factor * $src_width),
                ($factor * $src_height)
            );
        } else {
            return array(
                $src_width,
                $src_height
            );
        }
    }

    /**
     * 画像の方向を修正する
     *
     * @param object $input_im    Imagickオブジェクト
     * @param string $output_path 出力先パス
     *
     * @return Imagick
     */
    public static function reorientationImage($input_im, $output_path)
    {

        $orientation = $input_im->getImageOrientation();
        switch ($orientation) {
            case Imagick::ORIENTATION_UNDEFINED:
                break;
            case Imagick::ORIENTATION_TOPLEFT:
                break;
            case Imagick::ORIENTATION_TOPRIGHT:
                $input_im->flopImage();
                $input_im->setimageorientation(Imagick::ORIENTATION_TOPLEFT);
                $input_im->writeImage($output_path);
                break;
            case Imagick::ORIENTATION_BOTTOMRIGHT:
                $input_im->rotateImage(new ImagickPixel(), 180);
                $input_im->setimageorientation(Imagick::ORIENTATION_TOPLEFT);
                $input_im->writeImage($output_path);
                break;
            case Imagick::ORIENTATION_BOTTOMLEFT:
                $input_im->rotateImage(new ImagickPixel(), 180);
                $input_im->flopImage();
                $input_im->setimageorientation(Imagick::ORIENTATION_TOPLEFT);
                $input_im->writeImage($output_path);
                break;
            case Imagick::ORIENTATION_LEFTTOP:
                $input_im->rotateImage(new ImagickPixel(), 90);
                $input_im->flopImage();
                $input_im->setimageorientation(Imagick::ORIENTATION_TOPLEFT);
                $input_im->writeImage($output_path);
                break;
            case Imagick::ORIENTATION_RIGHTTOP:
                $input_im->rotateImage(new ImagickPixel(), 90);
                $input_im->setimageorientation(Imagick::ORIENTATION_TOPLEFT);
                $input_im->writeImage($output_path);
                break;
            case Imagick::ORIENTATION_RIGHTBOTTOM:
                $input_im->rotateImage(new ImagickPixel(), 270);
                $input_im->flopImage();
                $input_im->setimageorientation(Imagick::ORIENTATION_TOPLEFT);
                $input_im->writeImage($output_path);
                break;
            case Imagick::ORIENTATION_LEFTBOTTOM:
                $input_im->rotateImage(new ImagickPixel(), 270);
                $input_im->setimageorientation(Imagick::ORIENTATION_TOPLEFT);
                $input_im->writeImage($output_path);
                break;
        }
        $input_im->clear();

        return new Imagick($output_path);

    }

    /**
     * 画像を保存(Phalcon用)
     * ※$upload_filesに$this->request->getUploadedFiles()を渡す
     *
     * @todo   何故か全部PNGになっちゃうから修正必要
     *
     * @param  object $upload_files
     * @param  string $save_dir
     *
     * @return array
     * @throws Exception
     * @throws RuntimeException
     */
    public static function saveImagePng($upload_files, $save_dir)
    {

        $image_info = array();

        //ディレクトリがなければ作る
        if (!is_dir($save_dir)) {
            if (!mkdir($save_dir, 0777, true)) {
                throw new Exception('mkdir is failed...');
            }
        }

        foreach ($upload_files as $key => $file) {

            $image_info[$key]['origin_name'] = $file->getName();
            $image_info[$key]['tmp_name']    = $file->getTempName();
            $image_info[$key]['type']        = $file->getType();
            $image_info[$key]['real_type']   = $file->getRealType();
            $image_info[$key]['size']        = $file->getSize();
            $image_info[$key]['error']       = $file->getError();
            $image_info[$key]['form_key']    = $file->getKey();
            $image_info[$key]['is_upload']   = $file->isUploadedFile();
            $image_info[$key]['save_path']   = sprintf($save_dir . '%s.png', sha1_file($image_info[$key]['tmp_name']));

            if ($image_info[$key]['error'] === UPLOAD_ERR_INI_SIZE) {
                throw new RuntimeException('upload_max_filesizeを超過しています');
            } else if ($image_info[$key]['error'] === UPLOAD_ERR_FORM_SIZE) {
                throw new RuntimeException('MAX_FILE_SIZEを超過しています');
            } else if ($image_info[$key]['error'] === UPLOAD_ERR_PARTIAL) {
                throw new RuntimeException('一部しかアップロード出来ませんでした');
            } else if ($image_info[$key]['error'] === UPLOAD_ERR_NO_FILE) {
                throw new RuntimeException('ファイルが選択されていません');
            } else if ($image_info[$key]['error'] === UPLOAD_ERR_NO_TMP_DIR) {
                throw new RuntimeException('tmpディレクトリが存在しません');
            } else if ($image_info[$key]['error'] === UPLOAD_ERR_CANT_WRITE) {
                throw new RuntimeException('書き込みに失敗しました');
            } else if ($image_info[$key]['error'] === UPLOAD_ERR_EXTENSION) {
                throw new RuntimeException('PHP拡張モジュールにより中止されました');
            } else if ($image_info[$key]['size'] > AppConfig::UPLOAD_MAX_FILE_SIZE) {
                throw new RuntimeException('ファイルサイズが規定値を超過しています');
            } else if ($image_info[$key]['is_upload'] === false) {
                throw new RuntimeException('アップロードに失敗しました');
            } else {

                //画像リソース
                if (false === $tmp_image = @imagecreatefromstring(file_get_contents($image_info[$key]['tmp_name'])) or !imagesx($tmp_image) || !imagesy($tmp_image)) {
                    throw new RuntimeException('無効な拡張子です');
                }

                //画像領域の作成
                $new_image = imagecreatetruecolor(imagesx($tmp_image), imagesy($tmp_image));

                //GIFとPNGのみ透過対応
                if (($image_info[$key]['type'] === 'image/gif') || ($image_info[$key]['type'] === 'image/png')) {
                    $trnprt_indx = imagecolortransparent($tmp_image);
                    if ($trnprt_indx >= 0) {
                        $trnprt_color = imagecolorsforindex($tmp_image, $trnprt_indx);
                        $trnprt_indx  = imagecolorallocate($new_image, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
                        imagefill($new_image, 0, 0, $trnprt_indx);
                        imagecolortransparent($new_image, $trnprt_indx);
                    } else if ($image_info[$key]['type'] === 'image/png') {
                        imagealphablending($new_image, false);
                        $color = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
                        imagefill($new_image, 0, 0, $color);
                        imagesavealpha($new_image, true);
                    }
                }

                //画像をリサンプル
                imagecopyresampled($new_image, $tmp_image, 0, 0, 0, 0, imagesx($tmp_image), imagesy($tmp_image), imagesx($tmp_image), imagesy($tmp_image));

                //ファイルが存在していたら削除
                if (file_exists($image_info[$key]['save_path'])) {
                    unlink($image_info[$key]['save_path']);
                }

                //タイプに応じて出力
                if ($image_info[$key]['type'] === 'image/gif') {
                    imagegif($new_image, $image_info[$key]['save_path']);
                } else if ($image_info[$key]['type'] === 'image/jpeg') {
                    imagejpeg($new_image, $image_info[$key]['save_path']);
                } else if ($image_info[$key]['type'] === 'image/png') {
                    imagepng($new_image, $image_info[$key]['save_path']);
                }

                //リソースを解放
                imagedestroy($tmp_image);
                imagedestroy($new_image);
            }
        }

        return $image_info;
    }

    /**
     * 画像をリサイズする(GIF/JPEG/PNGのみ対応)
     *
     * @author Noriyoshi Takahashi
     *
     * @param string $org_file
     * @param string $new_file
     * @param int    $max_width
     * @param int    $max_height
     */
    public static function resizeImage($org_file, $new_file, $max_width, $max_height)
    {

        //画像情報取得
        list($org_width, $org_height, $type) = getimagesize($org_file);

        //タイプに応じてリソース生成
        if ($type === IMAGETYPE_GIF) {
            $org_image = imagecreatefromgif($org_file);
        } else if ($type === IMAGETYPE_JPEG) {
            $org_image = imagecreatefromjpeg($org_file);
        } else if ($type === IMAGETYPE_PNG) {
            $org_image = imagecreatefrompng($org_file);
        }

        //リサイズ値取得
        list($new_width, $new_height) = self::getResizeImageSize($max_width, $max_height, $org_width, $org_height);

        //画像領域の作成
        $new_image = imagecreatetruecolor($new_width, $new_height);

        //GIFとPNGのみ透過対応
        if (($type === IMAGETYPE_GIF) || ($type === IMAGETYPE_PNG)) {

            $trnprt_indx = imagecolortransparent($org_image);

            if ($trnprt_indx >= 0) {

                $trnprt_color = imagecolorsforindex($org_image, $trnprt_indx);
                $trnprt_indx  = imagecolorallocate($new_image, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
                imagefill($new_image, 0, 0, $trnprt_indx);
                imagecolortransparent($new_image, $trnprt_indx);

            } else if ($type == IMAGETYPE_PNG) {

                imagealphablending($new_image, false);
                $color = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
                imagefill($new_image, 0, 0, $color);
                imagesavealpha($new_image, true);

            }

        }

        //画像をリサンプル
        imagecopyresampled($new_image, $org_image, 0, 0, 0, 0, $new_width, $new_height, $org_width, $org_height);

        //ファイルが既に存在していたら削除
        if (file_exists($new_file)) {
            unlink($new_file);
        }

        //タイプに応じて出力
        if ($type === IMAGETYPE_GIF) {
            imagegif($new_image, $new_file);
        } else if ($type === IMAGETYPE_JPEG) {
            imagejpeg($new_image, $new_file);
        } else if ($type === IMAGETYPE_PNG) {
            imagepng($new_image, $new_file);
        }

        //リソースを解放
        imagedestroy($org_image);
        imagedestroy($new_image);

    }
}

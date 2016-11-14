<?php

class FileComponent extends Phalcon\Mvc\User\Component
{

    public $errors = [];

    /**
     * エラーを取得
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * アップロードファイルが存在するか
     *
     * @return bool
     */
    public function hasFile()
    {
        return (count($_FILES) > 0);
    }

    /**
     * アップロードファイル情報を配列で返す
     * @return array
     */
    public function getUploads()
    {
        $upload_files = $this->request->getUploadedFiles();
        $file_info    = array();
        foreach($upload_files as $row){
            $key = $row->getKey();
            $file_info[$key]['name']      = $row->getName();
            $file_info[$key]['type']      = $row->getType();
            $file_info[$key]['tmp_name']  = $row->getTempName();
            $file_info[$key]['error']     = $row->getError();
            $file_info[$key]['size']      = $row->getSize();
            $file_info[$key]['extension'] = $row->getExtension();
        }
        return (count($file_info) === 1) ? $file_info[$key] : $file_info;
    }

    /**
     * サーバー上の正確なMIMEタイプを取得
     * @param string $path
     *
     * @return mixed
     */
    public function getRealMimeType($path)
    {
        $mime = shell_exec('file -bi '.escapeshellcmd($path));
        $mime = trim($mime);
        return preg_replace("/; [^ ]*/", "", $mime);
    }

    /**
     * アップロードバリデーション
     * @param int $error
     *
     * @return bool
     */
    public function validateUpload($error)
    {
        switch ($error) {

            case UPLOAD_ERR_OK:
                return true;
            case UPLOAD_ERR_INI_SIZE:
                $this->errors['file'] = 'サーバー側の容量制限を超過しています';
                return false;
            case UPLOAD_ERR_FORM_SIZE:
                $this->errors['file'] = 'ブラウザ側の容量制限を超過しています';
                return false;
            case UPLOAD_ERR_PARTIAL:
                $this->errors['file'] = '一部のみアップロードされました';
                return false;
            case UPLOAD_ERR_NO_FILE:
                $this->errors['file'] = 'アップロードに失敗しました';
                return false;
            case UPLOAD_ERR_NO_TMP_DIR:
                $this->errors['file'] = 'テンポラリフォルダがありません';
                return false;
            case UPLOAD_ERR_CANT_WRITE:
                $this->errors['file'] = 'ディスクへの書き込みに失敗しました';
                return false;
            case UPLOAD_ERR_EXTENSION:
                $this->errors['file'] = '拡張モジュールにより中断されました';
                return false;

        }
    }

    /**
     * 文字コードを調べる
     * 各文字コードで変換、変換前と変換後が同じかチェック。
     * @param string $str
     */
    public function getEncoding($str)
    {
        foreach(AppConfig::$ALLOW_ENCODING as $charset){
            if($str === mb_convert_encoding($str, $charset, $charset)){
                return $charset;
            }
        }
        return $charset;
    }

    /**
     * CSVデータを配列で返す
     * @param  string $file_path
     * @return array
     */
    public function getCsvData($file_path)
    {

        $csv_string = file_get_contents($file_path);

        //Excelで編集されたCSV対策
        $csv_string = strtr($csv_string, array("\r" => "\n"));
        $csv_string = strtr($csv_string, array("\n\n" => "\r\n"));

        $data   = array();
        $stream = tmpfile();
        fwrite($stream, mb_convert_encoding($csv_string, mb_internal_encoding(), AppConfig::$ALLOW_ENCODING));
        rewind($stream);
        while($line = fgetcsv($stream)) {
            $data[] = $line;
        }
        fclose($stream);
        return $data;
    }

    /**
     * TSVデータを配列で返す
     * @param  string $file_path
     * @return array
     */
    public function getTsvData($file_path)
    {
        $data = array();
        $stream   = tmpfile();
        fwrite($stream, mb_convert_encoding(file_get_contents($file_path), mb_internal_encoding(), AppConfig::$ALLOW_ENCODING));
        rewind($stream);
        while($line = fgetcsv($stream, 0, "\t")) {
            $data[] = $line;
        }
        fclose($stream);
        return $data;
    }

    /**
     * SJIS-WINのCSVをUTF-8に変換して配列で返す
     *
     * @param  String $file
     * @return Array
     */
    public function convertSjisWinToUtf8($file){

        $csv_data = array();
        $buf = mb_convert_encoding(file_get_contents($file), 'utf-8', 'sjis-win');
        $stream  = tmpfile();
        fwrite($stream, $buf);
        rewind($stream);
        while($line = fgetcsv($stream)) {
            $csv_data[] = $line;
        }
        fclose($stream);
        return $csv_data;

    }

    /**
     * 新規ファイル作成
     * @param string $dirname
     * @param string $filename
     */
    public function createNewFile($dirname, $filename)
    {
        if(!is_dir($dirname)){
            mkdir($dirname, 0666, true);
        }else{
            if(file_exists($filename)){
                chmod($filename, 0666);
            }else{
                touch($filename);
                chmod($filename, 0666);
            }
        }
    }

    /**
     * ディレクトリ新規作成
     * ※既存の場合は権限だけ変える
     * @param string $dirname
     */
    public function createDirectory($dirname)
    {
        if(!is_dir($dirname)){
            mkdir($dirname, 0666, true);
        }else{
            chmod($dirname, 0666);
        }
    }

    /**
     * ディレクトリ削除
     * @param  string $path
     * @return boolean
     */
    public function removeDirectory($path)
    {
        exec("rm -rf {$path}", $output, $return_var);
        return ($return_var === 0);
    }

}
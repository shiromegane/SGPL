<?php

/**
 * ファイル操作クラス
 *
 * @author    Noriyoshi Takahashi
 * @copyright Copyright (c) 2015, Noriyoshi Takahashi
 * @version   1.0 (2016.01.07 created)
 */
class File
{

    /** 対応文字コード */
    public static $ALLOW_ECODING = array(
        'SJIS', 'SJIS-win', 'EUC-JP', 'ASCII', 'JIS', 'UTF-8'
    );

    /**
     * 文字コードを調べる
     * 各文字コードで変換、変換前と変換後が同じかチェック。
     * @param string $str
     */
    public function getEncoding($str)
    {
        foreach(self::$ALLOW_ECODING as $charset){
            if($str === mb_convert_encoding($str, $charset, $charset)){
                return $charset;
            }
        }
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
        fwrite($stream, mb_convert_encoding($csv_string, mb_internal_encoding(), self::$ALLOW_ECODING));
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
        fwrite($stream, mb_convert_encoding(file_get_contents($file_path), mb_internal_encoding(), self::$ALLOW_ECODING));
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

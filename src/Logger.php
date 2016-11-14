<?php

/** DIRECTORY_SEPARATORのエイリアス */
if (defined('DS') === false) {
    define('DS', DIRECTORY_SEPARATOR);
}

/**
 * ログ出力クラス
 *
 * Ver1.3 変更点:
 *   ・save関数で独自ログを出力出来るように改修
 *
 * @author    Noriyoshi Takahashi
 * @copyright Copyright (c) 2015, Noriyoshi Takahashi
 * @version   1.3 (2016.02.22 modified)
 * @usage
 *   1.出力ディレクトリを設定
 *     Logger::setDirectory([path]);
 */
class Logger
{

    /** ログローテート対応のファイル名にするか否か */
    const ENABLE_LOG_ROTATE = false;

    /** ユーザーIDを出力するか否か */
    const ENABLE_USER_ID = true;

    /** ログローテートの日時フォーマット */
    const FORMAT_LOG_ROTATE_DATE = 'Ymd';

    /** ログに記載する日時フォーマット */
    const FORMAT_MESSAGE_TIMESTAMP = 'Y/m/d H:i:s';

    /** Exceptionログフォーマット */
    const FORMAT_EXCEPTION_LOG          = '[Exception:%s][Code:%s][File:%s][Line:%s] %s';

    /** DBException用 */
    const FORMAT_DATABASE_EXCEPTION_LOG = 'Exception has occurred. Please refer to the \'exception.log\' for details.';

    /** ログ種別 */
    const TYPE_DEFAULT   = 0;

    const TYPE_DEBUG     = 1;

    const TYPE_ERROR     = 2;

    const TYPE_SYSTEM    = 3;

    const TYPE_BATCH     = 4;

    const TYPE_AUTH      = 5;

    const TYPE_MAIL      = 6;

    const TYPE_ACTIVITY  = 7;

    const TYPE_DATABASE  = 8;

    const TYPE_EXCEPTION = 9;

    /**
     * ログ種別名称（ログのファイル名になる）
     *
     * @type array
     */
    public static $TYPE_NAME = array(
        self::TYPE_DEFAULT   => 'default',
        self::TYPE_DEBUG     => 'debug',
        self::TYPE_ERROR     => 'error',
        self::TYPE_SYSTEM    => 'system',
        self::TYPE_BATCH     => 'batch',
        self::TYPE_AUTH      => 'auth',
        self::TYPE_MAIL      => 'mail',
        self::TYPE_ACTIVITY  => 'activity',
        self::TYPE_DATABASE  => 'database',
        self::TYPE_EXCEPTION => 'exception',
    );

    /** ユーザーID */
    protected static $user_id = null;

    /** ファイルパス */
    protected static $file_path = null;

    /** 出力先ディレクトリ */
    protected static $directory = null;

    /** ファイル名 */
    protected static $file_name = null;

    /** ファイル名接頭辞 */
    protected static $file_name_prefix = null;

    /** ファイル名接尾辞 */
    protected static $file_name_suffix = null;

    /** メッセージ */
    protected static $message = null;

    /** メッセージ接頭辞 */
    protected static $message_prefix = null;

    /** メッセージ接尾辞 */
    protected static $message_suffix = null;

    /** インスタンスを生成させない */
    protected function __construct() { }

    /**
     * ディレクトリを設定する
     *
     * @param string $directory
     */
    public static function setDirectory($directory)
    {
        self::$directory = $directory;
    }

    /**
     * ユーザーIDをセット
     *
     * @param int $id
     */
    public static function setUserId($id)
    {
        self::$user_id = sprintf('%08d', $id);
    }

    /**
     * ログヘッダを生成
     *
     * @param string $message
     *
     * @return string
     */
    public static function createHeader($message)
    {
        $header = Logger::createLine('=') . PHP_EOL;
        $header .= $message . PHP_EOL;
        $header .= Logger::createLine('-');
        return $header;
    }

    /**
     * ログフッタを生成
     *
     * @param string $message
     *
     * @return string
     */
    public static function createFooter($message)
    {
        $footer = Logger::createLine('-') . PHP_EOL;
        $footer .= $message . PHP_EOL;
        $footer .= Logger::createLine('-');
        return $footer;
    }

    /**
     * 水平線を生成する
     *
     * @param string $string 水平線にする文字列
     * @param int    $repeat 線の長さ
     *
     * @return string
     */
    public static function createLine($string = '-', $repeat = 80)
    {
        return str_repeat($string, $repeat);
    }

    /**
     * デバッグログ
     *
     * @param  string $message
     * @param  bool   $timestamp
     *
     * @return string
     */
    public static function debug($message, $timestamp = true)
    {
        return self::write($message, self::TYPE_DEBUG, $timestamp);
    }

    /**
     * エラーログ
     *
     * @param  string $message
     * @param  bool   $timestamp
     *
     * @return string
     */
    public static function error($message, $timestamp = true)
    {
        return self::write($message, self::TYPE_ERROR, $timestamp);
    }

    /**
     * システムログ
     *
     * @param  string $message
     * @param  bool   $timestamp
     *
     * @return string
     */
    public static function system($message, $timestamp = true)
    {
        return self::write($message, self::TYPE_SYSTEM, $timestamp);
    }

    /**
     * バッチログ
     *
     * @param  string $message
     * @param  bool   $timestamp
     *
     * @return string
     */
    public static function batch($message, $timestamp = true)
    {
        return self::write($message, self::TYPE_BATCH, $timestamp);
    }

    /**
     * 認証ログ
     *
     * @param  string $message
     * @param  bool   $timestamp
     *
     * @return string
     */
    public static function auth($message, $timestamp = true)
    {
        return self::write($message, self::TYPE_AUTH, $timestamp);
    }

    /**
     * メール
     *
     * @param  string $message
     * @param  bool   $timestamp
     *
     * @return string
     */
    public static function mail($message, $timestamp = true)
    {
        return self::write($message, self::TYPE_MAIL, $timestamp);
    }

    /**
     * アクティビティログ
     *
     * @param  string $message
     * @param  bool   $timestamp
     *
     * @return string
     */
    public static function activity($message, $timestamp = true)
    {
        return self::write($message, self::TYPE_ACTIVITY, $timestamp);
    }

    /**
     * DB系のログ
     *
     * @param  string $message
     * @param  bool   $timestamp
     *
     * @return string
     */
    public static function database($message, $timestamp = true)
    {
        return self::write($message, self::TYPE_DATABASE, $timestamp);
    }

    /**
     * Exceptionログ
     *
     * @param  object $exception
     * @param  bool   $timestamp
     *
     * @return string
     */
    public static function exception($exception, $timestamp = true)
    {
        return self::writeException($exception, $timestamp);
    }

    /**
     * DBExceptionログ
     *
     * @param  object $exception
     * @param  bool   $timestamp
     *
     * @return string
     */
    public static function dbException($exception, $timestamp = true)
    {
        //database.logにもException発生の旨を残す
        self::write(self::FORMAT_DATABASE_EXCEPTION_LOG . PHP_EOL, self::TYPE_DATABASE, $timestamp);
        return self::writeException($exception, $timestamp);
    }

    /**
     * ログを書き込む
     *
     * @param string $message   ログメッセージ
     * @param mixed  $arg2      定義されたログタイプ または ファイル名
     * @param bool   $timestamp ログメッセージの接頭辞として日時を残すか否か
     *
     * @return mixed
     */
    public static function write($message, $arg2 = self::TYPE_DEFAULT, $timestamp = true)
    {

        //出力ディレクトリ
        self::$directory = (self::$directory === null) ? __DIR__ . DS . 'logs' . DS : self::$directory;

        //ファイル名
        self::$file_name = (is_int($arg2) && array_key_exists($arg2, self::$TYPE_NAME)) ? self::$TYPE_NAME[$arg2] . '.log' : $arg2;

        //ログローテート用接尾辞
        self::$file_name_suffix = (self::ENABLE_LOG_ROTATE) ? '.' . date(self::FORMAT_LOG_ROTATE_DATE) : '';

        //ファイルパス
        self:: $file_path = self::$directory . self::$file_name_prefix . self::$file_name . self::$file_name_suffix;

        //ユーザーID
        self::$message_prefix = ((self::ENABLE_USER_ID && (self::$user_id !== null)) ? '[UserId:' . self::$user_id . ']' : '') . self::$message_prefix;

        //タイムスタンプ
        self::$message_prefix = (($timestamp) ? '[' . date(self::FORMAT_MESSAGE_TIMESTAMP) . ']' : '') . self::$message_prefix;

        //ログメッセージを整形
        self::$message = self::$message_prefix . $message . self::$message_suffix . PHP_EOL;

        //ファイルに書き込み
        self::saveToFile();

        //書き込みが終わったら内容を初期化
        self::$message        = null;
        self::$message_prefix = null;
        self::$message_suffix = null;

        //オリジナルメッセージを返す
        return $message;

    }

    /**
     * Exceptionログを書き込む
     *
     * @param object $exception
     * @param bool   $timestamp
     *
     * @return mixed
     */
    public static function writeException($exception, $timestamp = true)
    {
        $message = sprintf(self::FORMAT_EXCEPTION_LOG, get_class($exception), $exception->getCode(), $exception->getFile(), $exception->getLine(), $exception->getMessage());
        return self::write($message, self::TYPE_EXCEPTION, $timestamp);
    }

    /**
     * 保存準備
     */
    protected static function readySave()
    {
        if (is_dir(self::$directory)) {
            if (file_exists(self::$file_path)) {
                chmod(self::$file_path, 0755);
            } else {
                touch(self::$file_path);
                chmod(self::$file_path, 0755);
            }
        } else {
            mkdir(self::$directory, 0755, true);
            touch(self::$file_path);
            chmod(self::$file_path, 0755);
        }
    }

    /**
     * ファイルに保存
     *
     * @return string
     */
    protected static function saveToFile()
    {
        //保存準備
        self::readySave();
        //ファイルへ書き込み
        file_put_contents(self::$file_path, self::$message, FILE_APPEND);
    }

}

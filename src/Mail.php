<?php

/**
 * メール送信用クラス
 * @author     Noriyoshi Takahashi
 * @copyright  Copyright (c) 2015, Noriyoshi Takahashi
 * @version    1.11 (2015.05.12 modified)
 * @usage      ※ Loggerクラスと併用すること
 *   1.SMTP情報を設定
 *     Mail::setSmtp($host, $user, $pass, $from, $port, $protocl);
 *   2.件名、本文を設定
 *     Mail::setSubject('件名');
 *     Mail::setBody('本文');
 *   3.送信先、送信元を引数に渡して送信
 *     Mail::send($to_address, $to_name, $from_address, $from_name);
 */
class Mail
{

    /** SMTPパラメータ */
    protected static $smtp_params;
    /** 件名 */
    protected static $subject;
    /** 本文 */
    protected static $body;
    /** ログフォーマット */
    const SEND_LOG_FORMAT  = "To:%s<%s>, From:%s<%s>, Subject:%s, \nBody:\n%s";
    const ERROR_LOG_FORMAT = '%s throw in %s (Line:%s)';

    /** インスタンスを生成させない */
    protected function __construct() { }

    /**
     * SMTPサーバー設定
     *
     * @param string $host     ホスト
     * @param string $user     ユーザー
     * @param string $pass     パスワード
     * @param string $from     Return-Path:ヘッダ情報(送信元アドレス)
     * @param int    $port     ポート番号(基本的に変更不要)
     * @param string $protocol プロトコル(基本的に変更不要)
     */
    public static function setSmtp($host, $user, $pass, $from, $port = 587, $protocol = 'SMTP_AUTH')
    {
        self::$smtp_params = array(
            'host'     => $host,
            'port'     => $port,
            'from'     => $from,
            'protocol' => $protocol,
            'user'     => $user,
            'pass'     => $pass
        );
    }

    /**
     * Gmail用SMTPサーバー設定
     *
     * @param string $user メールアドレス
     * @param string $pass パスワード
     */
    public static function setGmailSmtp($user, $pass)
    {
        self::$smtp_params = array(
            'host'     => 'ssl://smtp.gmail.com',
            'port'     => 465,
            'from'     => $user,
            'protocol' => 'SMTP_AUTH',
            'user'     => $user,
            'pass'     => $pass
        );
    }

    /**
     * 件名をセット
     *
     * @param  string $subject
     *
     * @return string
     */
    public static function setSubject($subject)
    {
        self::$subject = $subject;

        return self::$subject;
    }

    /**
     * 本文をセット
     *
     * @param  string $body
     *
     * @return string
     */
    public static function setBody($body)
    {
        self::$body = $body;

        return self::$body;
    }

    /**
     * メール送信
     *
     * @param string $to_address   送信先アドレス
     * @param string $to_name      送信先表示名
     * @param string $from_address 送信元アドレス
     * @param string $from_name    送信元表示名
     *
     * @return boolean
     */
    public static function send($to_address, $to_name, $from_address, $from_name)
    {
        try{
            $subject = self::$subject;
            $body    = self::$body;
            $mail    = new Qdmail();
            $mail->smtp(true);
            $mail->smtpServer(self::$smtp_params);
            $mail->from($from_address, $from_name);
            $mail->to($to_address, $to_name);
            $mail->bcc($from_address, $from_name);
            $mail->subject($subject);
            $mail->text($body);
            if($mail->send()){
                Logger::mailSend(sprintf(self::SEND_LOG_FORMAT, $to_name, $to_address, $from_name, $from_address, $subject, $body));

                return true;
            }else{
                Logger::mailError('Mail send failed.');

                return false;
            }
        }catch(Exception $e){
            Logger::mailException($e);
        }
    }
}

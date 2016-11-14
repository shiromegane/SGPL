<?php

/**
 * Session用クラス(memcached, Redis対応?)
 * @author    Noriyoshi Takahashi
 * @copyright Copyright (c) 2015, Noriyoshi Takahashi
 * @version   1.2 (2016.01.07 modified)
 */
class Session
{

    /** session.save_handler */
    protected static $handler = 'files';
    /** session.save_path */
    protected static $path = '';
    /** session.gc_maxlifetime */
    protected static $max_lifetime = 86400;
    /** session.cookie_lifetime */
    protected static $cookie_lifetime = 0;
    /** session.cache_expire */
    protected static $cache_expire = 180;
    /** SESSIONID */
    protected static $session_id = null;
    /** 設定パラメータ */
    protected static $parameters = null;
    /** memcache設定 */
    protected static $memcache_host = '127.0.0.1';
    protected static $memcache_port = 11211;
    /** Redis設定 */
    protected static $redis_host = '127.0.0.1';
    protected static $redis_port = 6379;

    /** インスタンスを生成させない */
    protected function __construct() { }

    /**
     * SESSIONハンドラを設定
     *
     * @param string $handler
     */
    public static function setHandler($handler)
    {
        self::$handler = $handler;
        ini_set('session.save_handler', $handler);
    }

    /**
     * session.save_pathを設定する
     *
     * @param string $path
     */
    public static function setSavePath($path)
    {
        self::$path = $path;
        ini_set('session.save_path', $path);
    }

    /**
     * session.gc_maxlifetimeを設定する
     *
     * @param int $lifetime
     */
    public static function setMaxLifetime($lifetime)
    {
        self::$max_lifetime = $lifetime;
        ini_set('session.gc_maxlifetime', $lifetime);
    }

    /**
     * session.cookie_lifetimeを設定する
     *
     * @param int $lifetime
     */
    public static function setCookieLifetime($lifetime)
    {
        self::$cookie_lifetime = $lifetime;
        ini_set('session.cookie_lifetime', $lifetime);
    }

    /**
     * session.cache_expireを設定する
     *
     * @param int $expire
     */
    public static function setCacheExpire($expire)
    {
        self::$cache_expire = $expire;
        ini_set('session.cache_expire', $expire);
    }

    /**
     * 初期設定
     *
     * @param string $handler
     * @param string $path
     * @param int    $lifetime
     * @param int    $cookie_lifetime
     * @param int    $cache_expire
     */
    public static function initialize($handler = 'memcache', $path = '', $lifetime = 86400, $cookie_lifetime = 0, $cache_expire = 180)
    {
        if(isset($_SESSION) === false) {
            self::setHandler($handler);
            self::setSavePath($path);
            self::setLifetime($lifetime);
            self::setCookieLifetime($cookie_lifetime);
            self::setCacheExpire($cache_expire);
            self::start();
        }

    }

    /**
     * SESSIONスタート
     */
    public static function start()
    {

        if(isset($_SESSION)){
            return false;
        }

        if(self::$handler === 'memcache'){
            self::setSavePath('tcp://' . self::$memcache_host . ':' . self::$memcache_port);
        }elseif(self::$handler === 'redis'){
            self::setSavePath('tcp://' . self::$redis_host . ':' . self::$redis_port . '?weight=1');
        }

        session_start();
        self::$session_id = session_id();
        return self::$session_id;

    }

    /**
     * リフレッシュ
     */
    public static function refresh()
    {
        if(self::destroy()){
            session_id(md5(uniqid(rand(1, 99999999))));
            self::start();
        }
    }

    /**
     * SESSIONを殺す
     * @return boolean
     */
    public static function destroy()
    {
        return session_destroy();
    }

    /**
     * 値を設定する
     *
     * @param string $key
     * @param mixed  $value
     */
    public static function set($key, $value)
    {
        if(session_id() === ''){
            self::start();
        }
        $_SESSION[$key] = $value;
    }

    /**
     * 値を取得する
     *
     * @param string $key
     *
     * @return mixed
     */
    public static function get($key)
    {
        if(session_id() === ''){
            self::start();
        }

        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    /**
     * キーが存在するか否か
     * @param  string $key
     * @return bool
     */
    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * 値を削除する
     * @param  string $key
     * @return bool
     */
    public static function delete($key)
    {
        if(isset($_SESSION[$key])){
            unset($_SESSION[$key]);
            return true;
        }else{
            return false;
        }
    }

    /**
     * 値をクリアする
     */
    public static function flush()
    {
        if(session_id() === ''){
            self::start();
        }
        session_unset();
    }

}

<?php

/**
 * キャッシュ用クラス(memcached, Redis対応)
 * @author    Noriyoshi Takahashi
 * @copyright Copyright (c) 2015, Noriyoshi Takahashi
 * @version   1.10 (2016.01.07 modified)
 */
class Cache
{

    /** ハンドラ */
    protected static $handler = 'memcache';

    /** インスタンス */
    protected static $instance = null;

    /** ホスト */
    protected static $host = '127.0.0.1';

    /** ポート */
    protected static $port = 11211;

    /** キーの接頭辞 */
    protected static $prefix = null;

    /** キーの接尾辞 */
    protected static $suffix = null;

    /** インスタンスを生成させない */
    protected function __construct(){}

    /**
     * 初期処理
     */
    public static function initialize()
    {

        $config = include DIR_CONFIGS_COMMON . 'config.php';
        self::$handler = $config->cache->handler;

        if(self::$instance === null){
            switch(self::$handler){
                case 'memcache' :
                    self::$host = $config->memcache->host;
                    self::$port = $config->memcache->port;
                    break;
                case 'redis' :
                    self::$host = $config->redis->host;
                    self::$port = $config->redis->port;
                    break;
            }
        }

    }

    /**
     * 接頭辞をセット
     * @param string $prefix
     */
    public static function setPrefix($prefix)
    {
        self::$prefix = $prefix;
    }

    /**
     * 接尾辞をセット
     * @param string $suffix
     */
    public static function setSuffix($suffix)
    {
        self::$suffix = $suffix;
    }

    /**
     * 接続
     * @return Memcached|Redis
     */
    public static function connect()
    {

        if(self::$instance === null){
            switch(self::$handler){
                case 'memcache' :
                    self::$instance = new Memcached();
                    self::$instance->addServer(self::$host, self::$port);
                    break;
                case 'redis' :
                    self::$instance = new Redis();
                    self::$instance->connect(self::$host, self::$port);
                    break;
            }
        }

        return self::$instance;

    }

    /**
     * 切断
     * @return mixed
     */
    public static function close()
    {
        if(self::$instance !== null){
            return self::$instance->quit();
        }
    }

    /**
     * キー名の配列を返す
     * @param string $needle
     * @return mixed
     */
    public static function getKeys($needle = null)
    {
        if(self::$instance === null){
            self::connect();
        }
        switch(self::$handler){
            case 'memcache' :

                $result = self::$instance->getAllKeys();

                if ($needle === null) {
                    return $result;
                } else {
                    $keys = array();
                    foreach ($result as $key) {
                        if (strpos($key, (string)$needle) !== false) {
                            $keys[] = $key;
                        }
                    }
                    return $keys;
                }

            case 'redis' :

                $result = self::$instance->getKeys('*');

                if ($needle === null) {
                    return $result;
                } else {
                    $keys = array();
                    foreach ($result as $key) {
                        if (strpos($key, (string)$needle) !== false) {
                            $keys[] = $key;
                        }
                    }
                    return $keys;
                }

        }
    }

    /**
     * キーが存在するか否か
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        $keys = self::getKeys();

        if (($keys === null) || ($keys === false)) {
            return false;
        }

        $key = (self::$prefix !== null) ? self::$prefix . $key : $key;
        $key = (self::$suffix !== null) ? $key . self::$suffix : $key;

        return in_array($key, $keys);
    }

    /**
     * 値をセットする
     * @param string $key
     * @param mixed $value
     * @param int $expire
     * @return mixed
     */
    public static function set($key, $value, $expire = TIME_1DAY)
    {
        if(self::$instance === null){
            self::connect();
        }

        $key = (self::$prefix !== null) ? self::$prefix . $key : $key;
        $key = (self::$suffix !== null) ? $key . self::$suffix : $key;

        switch(self::$handler){
            case 'memcache' :
                return self::$instance->set($key, $value, $expire);
            case 'redis' :
                if(is_array($value) || is_object($value)){
                    return self::$instance->set($key, json_encode($value));
                }else{
                    return self::$instance->set($key, $value);
                }
        }

    }

    /**
     * 値を取得する
     * @param string $key
     * @return mixed
     */
    public static function get($key)
    {
        if(self::$instance === null){
            self::connect();
        }

        $key = (self::$prefix !== null) ? self::$prefix . $key : $key;
        $key = (self::$suffix !== null) ? $key . self::$suffix : $key;

        switch(self::$handler){
            case 'memcache' :
                return self::$instance->get($key);
            case 'redis' :
                $result = self::$instance->get($key);
                if(json_decode($result) === null){
                    return $result;
                }else{
                    return json_decode($result, true);
                }
        }

    }

    /**
     * 値を削除する
     * @param string $key
     * @return mixed
     */
    public static function delete($key)
    {
        if(self::$instance === null){
            self::connect();
        }

        $key = (self::$prefix !== null) ? self::$prefix . $key : $key;
        $key = (self::$suffix !== null) ? $key . self::$suffix : $key;

        switch(self::$handler){
            case 'memcache' :
                return self::$instance->delete($key);
            case 'redis' :
                return self::$instance->delete($key);
        }
    }

    /**
     * 削除してセットする
     * @param string $key
     * @param mixed $value
     */
    public static function deleteSet($key, $value)
    {
        self::delete($key);
        self::set($key, $value);
    }

    /**
     * 全て削除する
     * @param int $delay
     * @return mixed
     */
    public static function flush($delay = 0)
    {
        if(self::$instance === null){
            self::connect();
        }
        switch(self::$handler){
            case 'memcache' :
                foreach(self::getKeys() as $key){
                    self::$instance->delete($key);
                }
                return self::$instance->flush($delay);
            case 'redis' :
                return self::$instance->flushAll();
        }
    }

    /**
     * キーワードを含むキーだけ削除
     * @param string $needle
     */
    public static function searchFlush($needle)
    {

        if(self::$instance === null){
            self::connect();
        }

        foreach(self::getKeys() as $key){
            if (strpos($key, (string)$needle) !== false) {
                self::$instance->delete($key);
            }
        }

    }

}

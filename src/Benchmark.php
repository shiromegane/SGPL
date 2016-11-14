<?php

/**
 * ベンチマーク
 *
 * @author    Noriyoshi Takahashi
 * @copyright Copyright (c) 2016, Noriyoshi Takahashi
 * @version   1.00 (2016.02.10 created)
 */
class Benchmark
{

    /**
     * ログに出力するか否か
     */
    const ENABLE_OUTPUT_LOG = true;

    /**
     * ベンチマーク結果配列
     *
     * @type array
     */
    protected static $benchmark = array();

    /**
     * 開始時間を取得
     *
     * @param  string $namespace
     *
     * @return float|null
     */
    public static function getStartTime($namespace = 'default')
    {
        return isset(self::$benchmark[$namespace]['start_time']) ? self::$benchmark[$namespace]['start_time'] : null;
    }

    /**
     * 終了時間を取得
     *
     * @param  string $namespace
     *
     * @return float|null
     */
    public static function getEndTime($namespace = 'default')
    {
        return isset(self::$benchmark[$namespace]['end_time']) ? self::$benchmark[$namespace]['end_time'] : null;
    }

    /**
     * 開始メモリ使用量を取得
     *
     * @param string $namespace
     *
     * @return int|null
     */
    public static function getStartMemory($namespace = 'default')
    {
        return isset(self::$benchmark[$namespace]['start_memory']) ? self::$benchmark[$namespace]['start_memory'] : null;
    }

    /**
     * 終了メモリ使用量を取得
     *
     * @param string $namespace
     *
     * @return int|null
     */
    public static function getEndMemory($namespace = 'default')
    {
        return isset(self::$benchmark[$namespace]['end_memory']) ? self::$benchmark[$namespace]['end_memory'] : null;
    }

    /**
     * ベンチマーク開始
     *
     * @param string $namespace
     */
    public static function start($namespace = 'default')
    {
        $backtrace = debug_backtrace();

        self::$benchmark[$namespace]['namespace']    = $namespace;
        self::$benchmark[$namespace]['start_file']   = "{$backtrace[0]['file']}:{$backtrace[0]['line']}";
        self::$benchmark[$namespace]['start_time']   = microtime(true);
        self::$benchmark[$namespace]['start_memory'] = memory_get_usage(true);
    }

    /**
     * ベンチマーク終了
     *
     * @param string $namespace
     */
    public static function end($namespace = 'default')
    {
        $backtrace = debug_backtrace();

        self::$benchmark[$namespace]['end_file']              = "{$backtrace[0]['file']}:{$backtrace[0]['line']}";
        self::$benchmark[$namespace]['end_time']              = microtime(true);
        self::$benchmark[$namespace]['end_memory']            = memory_get_usage(true);
        self::$benchmark[$namespace]['execution_time']        = self::getTime($namespace);
        self::$benchmark[$namespace]['memory_usage']          = self::getMemoryUsage($namespace);
        self::$benchmark[$namespace]['memory_peak_usage']     = self::getMemoryPeak();
        self::$benchmark[$namespace]['raw_execution_time']    = self::getTime($namespace, true);
        self::$benchmark[$namespace]['raw_memory_usage']      = self::getMemoryUsage($namespace, true);
        self::$benchmark[$namespace]['raw_memory_peak_usage'] = self::getMemoryPeak(true);
    }

    /**
     * ベンチマーク結果配列を取得
     *
     * @param string $namespace
     *
     * @return null|array
     */
    public static function getResult($namespace = 'default')
    {

        //結果セットがなければNULLを返す
        if (array_key_exists($namespace, self::$benchmark) === false) {
            return null;
        }

        return self::$benchmark[$namespace];

    }


    public static function getResultAll()
    {
        return self::$benchmark;
    }

    /**
     * 処理時間を取得
     *
     * @param  string $namespace
     * @param  bool   $raw
     *
     * @return int|string
     */
    public static function getTime($namespace = 'default', $raw = false)
    {

        //ベンチマークが開始されてなかったらNULLを返す
        if (self::getStartTime($namespace) === null) {
            return null;
        }

        //ベンチマークが終了されてなかったら現時点までの秒数とする
        if (self::getEndTime($namespace) === null) {
            self::$benchmark[$namespace]['end_time'] = microtime(true);
        }

        $time = self::$benchmark[$namespace]['end_time'] - self::$benchmark[$namespace]['start_time'];
        return ($raw) ? $time : self::formatTime($time);

    }

    /**
     * メモリ使用量取得
     *
     * @param  string $namespace
     * @param  bool   $raw 生の値にするか否か
     *
     * @return int|string
     */
    public static function getMemoryUsage($namespace = 'default', $raw = false)
    {

        //ベンチマークが開始されてなかったら現在のメモリ使用量を返す
        if (self::getStartMemory($namespace) === null) {
            $memory_usage = memory_get_usage(true);
            return ($raw) ? $memory_usage : self::formatSize($memory_usage);
        }

        //ベンチマークが終了されてなかったら現時点までの値とする
        if (self::getEndMemory($namespace) === null) {
            self::$benchmark[$namespace]['end_memory'] = memory_get_usage(true);
        }

        $memory_usage = self::$benchmark[$namespace]['end_memory'] - self::$benchmark[$namespace]['start_memory'];
        return ($raw) ? $memory_usage : self::formatSize($memory_usage);

    }

    /**
     * メモリ使用量ピーク値を取得
     *
     * @param  bool $raw
     *
     * @return int|string
     */
    public static function getMemoryPeak($raw = false)
    {
        $memory_usage = memory_get_peak_usage(true);
        return ($raw) ? $memory_usage : self::formatSize($memory_usage);
    }

    /**
     * 時間を整形する
     *
     * @param  string $time
     *
     * @return string
     */
    public static function formatTime($time)
    {
        return number_format($time, 4) . 'sec';
    }

    /**
     * サイズを整形する
     *
     * @param  int $size
     *
     * @return string
     */
    public static function formatSize($size)
    {

        //元の値を保持
        $origin_size = $size;

        if (defined('BYTE_UNIT_CHANGE_VALUE') === false) {
            define('BYTE_UNIT_CHANGE_VALUE', 1024);
        }

        $units = array('B','KB','MB','GB','TB','PB','EB','ZB','YB');

        if ($origin_size < 0) {
            $size = abs($size);
        }

        for ($unit_key = 0; $size >= BYTE_UNIT_CHANGE_VALUE; $unit_key++) {
            $size /= BYTE_UNIT_CHANGE_VALUE;
            //ヨタバイト以上は非対応
            if ($unit_key >= count($units) - 1) {
                break;
            }
        }

        if ($unit_key === 0) {
            return ($origin_size < 0) ? '-' . number_format($size) . $units[$unit_key] : number_format($size) . $units[$unit_key];
        } else {
            return ($origin_size < 0) ? '-' . number_format($size, 3) . $units[$unit_key] : number_format($size, 3) . $units[$unit_key];
        }

    }

}

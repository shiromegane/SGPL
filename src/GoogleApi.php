<?php

/**
 * GoogleAPIを扱うクラス
 * @author    Noriyoshi Takahashi
 * @copyright Copyright (c) 2015, Noriyoshi Takahashi
 * @version   1.0 (2015.05.12 created)
 */
class GoogleApi
{

    /** サーバーキー */
    protected static $server_api_key = null;
    /** ブラウザキー */
    protected static $browser_api_key = null;

    /** インスタンスを生成させない */
    protected function __construct() { }

    /**
     * サーバーキーをセットする
     *
     * @param string $key
     */
    public static function setServerApiKey($key)
    {
        self::$server_api_key = $key;
    }

    /**
     * ブラウザキーをセットする
     *
     * @param string $key
     */
    public static function setBrowserApiKey($key)
    {
        self::$browser_api_key = $key;
    }

    /**
     * 連想配列からクエリストリングに変換
     *
     * @param  array $params
     *
     * @return null|string
     */
    public static function convertArrayToQueryString($params)
    {
        if(is_array($params)){
            $tmp = array();
            foreach($params as $key => $value){
                array_push($tmp, "{$key}={$value}");
            }

            return implode('&', $tmp);
        }else{
            return null;
        }
    }

    /**
     * 期間を指定して祝日を取得する
     *
     * @param  string $start_date
     * @param  string $end_date
     *
     * @return mixed
     */
    public static function getPublicHolidays($start_date = null, $end_date = null)
    {

        //期間指定がない場合は範囲を今月にする
        $start_date = ($start_date === null) ? date('Y-m-01') . 'T00:00:00Z' : "{$start_date}T00:00:00Z";
        $end_date   = ($end_date === null) ? date('Y-m-t') . 'T23:59:59Z' : "{$end_date}T23:59:59Z";

        $cache_key = AppConfig::CACHE_KEY_PUBLIC_HOLIDAYS . "_{$start_date}_{$end_date}";


        if (Cache::has($cache_key)) {

            //キャッシュにあればキャッシュから取得
            $holidays = Cache::get($cache_key);

        } else {

            //APIのURL
            $api_url = 'https://www.googleapis.com/calendar/v3/calendars/';

            //取得するカレンダーID
            $calendar_id = 'outid3el0qkcrsuf89fltf7a4qbacgt9@import.calendar.google.com';

            //パラメータ
            $params = array(
                'key'          => self::$server_api_key,
                'timeMin'      => $start_date,
                'timeMax'      => $end_date,
                'maxResult'    => '25',
                'orderBy'      => 'startTime',
                'singleEvents' => 'true'
            );

            //クエリストリング生成
            $query_string = self::convertArrayToQueryString($params);

            //リクエストURLを整形
            $holidays_url = "{$api_url}{$calendar_id}/events?{$query_string}";

            $holidays = array();

            if($results = file_get_contents($holidays_url)){
                $results = json_decode($results);
                if(isset($results->items) && count($results->items) > 0){
                    $holidays = array();
                    foreach($results->items as $key => $item){
                        $date            = date('Y-m-d', strtotime((string)$item->start->date));
                        $holidays[$date] = trim(explode('/', (string)$item->summary)[0]);
                    }
                    ksort($holidays);
                }
            }

            Cache::set($cache_key, $holidays);

        }

        return $holidays;

    }
}
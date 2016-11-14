<?php

/**
 * 汎用的な処理をまとめておくクラス
 * @author    Noriyoshi Takahashi
 * @copyright Copyright (c) 2015, Noriyoshi Takahashi
 * @version   1.0 (2015.05.12 created)
 */
class Common
{

    /** インスタンスを生成させない */
    protected function __construct() { }

    /**
     * テキスト内のURLをリンクタグに置換
     *
     * @param string $text
     *
     * @return string
     */
    public static function replaceUrlToLinkTag($text)
    {
        return preg_replace('/(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)/', '<a href="\\1\\2" target="_blank" class="in-text" rel="nofollow">\\1\\2</a>', $text);
    }

    /**
     * 郵便番号フォーマット
     *
     * @param  string $zipcode ハイフン無し7桁の郵便番号
     *
     * @return string
     */
    public static function formatZipcode($zipcode)
    {
        return '〒' . $zipcode[0] . $zipcode[1] . $zipcode[2] . '-' . $zipcode[3] . $zipcode[4] . $zipcode[5] . $zipcode[6];
    }

    /**
     * 相対時間を返す
     *
     * @param  mixed $datetime
     *
     * @return string
     */
    public static function convertRelativeTime($datetime)
    {

        $time = strtotime($datetime);
        $diff = time() - $time;

        if($diff == 0){
            return '現在';
        }else if($diff < 60){
            return "{$diff}秒前";
        }else if(($diff >= 60) && ($diff < (60 * 60))){
            return floor($diff / 60) . "分前";
        }else if(($diff >= (60 * 60)) && ($diff < (60 * 60 * 24))){
            return floor($diff / (60 * 60)) . "時間前";
        }else if(($diff >= (60 * 60 * 24)) && ($diff < (60 * 60 * 24 * 7))){
            return floor($diff / (60 * 60 * 24)) . "日前";
        }else if(date("Y") == date("Y", $time)){
            return date("n月j日", $time);
        }else{
            return date("Y年n月j日", $time);
        }

    }

    /**
     * 誕生日から年齢を算出する
     *
     * @param  mixed $date
     *
     * @return int
     */
    public static function convertBirthdayToAge($date)
    {
        $time = strtotime($date);

        return (int)((date('Ymd') - date('Ymd', $time)) / 10000);
    }

    /**
     * PHP標準date関数のラッパー
     *
     * @param  string $format   date関数のフォーマット
     * @param  mixed  $datetime タイムスタンプでも文字列でもOK
     *
     * @return string
     */
    public static function formatDate($format, $datetime = null)
    {
        if($datetime === null){
            $datetime = time();
        }else if(!is_int($datetime)){
            $datetime = strtotime($datetime);
        }

        return date($format, $datetime);
    }

    /**
     * 曜日を取得する
     *
     * @param  string $datetime
     * @param  bool   $jp_mode
     *
     * @return string
     */
    public static function getDayOfWeek($datetime, $jp_mode = false)
    {
        if($datetime === null){
            $datetime = time();
        }else if(!is_int($datetime)){
            $datetime = strtotime($datetime);
        }

        $week_index = date('w', $datetime);

        if($jp_mode){
            $week_name_jp = array(
                '0' => '日',
                '1' => '月',
                '2' => '火',
                '3' => '水',
                '4' => '木',
                '5' => '金',
                '6' => '土'
            );
            $day_of_week  = $week_name_jp[$week_index];
        }else{
            $week_name_en = array(
                '0' => 'Sunday',
                '1' => 'Monday',
                '2' => 'Tuesday',
                '3' => 'Wednesday',
                '4' => 'Thursday',
                '5' => 'Friday',
                '6' => 'Saturday'
            );
            $day_of_week  = $week_name_en[$week_index];
        }

        return $day_of_week;

    }

    /**
     * 時間型を少数に変換
     * @param  string $time
     * @return float
     */
    public static function convertTimeToFloat($time)
    {
        if($time === null) {
            $time = '00:00';
        }
        list($hour, $min) = explode(':', $time);
        return sprintf('%0.2f', ((int)$hour + round((int)$min / TIME_1MINUTE, 2)));
    }

    /**
     * 分を丸め値で切り捨て
     *
     * @param string $time     mm:ss
     * @param int    $rounding 最小分単位
     *
     * @return string
     */
    public static function floorTime($time, $rounding)
    {

        if(($time === null || $time === '') || ($rounding === null) || ($rounding <= 0)){
            return $time;
        }

        list($hour, $min) = explode(':', $time);
        $min = floor($min / $rounding) * $rounding;

        return $hour . ':' . sprintf('%02d', $min);

    }

    /**
     * 分を丸め値で繰り上げ
     *
     * @param string $time     mm:ss
     * @param int    $rounding 丸め値
     *
     * @return string
     */
    public static function ceilTime($time, $rounding)
    {

        if(($time === null || $time === '') || ($rounding === null) || ($rounding <= 0)){
            return $time;
        }

        list($hour, $min) = explode(':', $time);
        $min = ceil($min / $rounding) * $rounding;
        if($min >= 60){
            $hour++;
            $min = 0;
        }

        return $hour . ':' . sprintf('%02d', $min);

    }

    /**
     * 時間の減算
     *
     * @param string $time1 mm:ss
     * @param string $time2 mm:ss
     *
     * @return string
     */
    public static function subTime($time1, $time2)
    {

        $time1 = ($time1 === null || $time1 === '') ? '00:00' : $time1;
        $time2 = ($time2 === null || $time2 === '') ? '00:00' : $time2;

        list($time1_hour, $time1_min) = explode(':', $time1);
        list($time2_hour, $time2_min) = explode(':', $time2);

        $sub_hour = $time1_hour - $time2_hour;
        $sub_min  = $time1_min - $time2_min;

        if($sub_min < 0){
            $underflow = abs(floor($sub_min / 60));
            $sub_min   = ($underflow * 60) - abs($sub_min);
            $sub_hour -= $underflow;
        }

        return $sub_hour . ':' . sprintf('%02d', $sub_min);

    }

    /**
     * 時間の加算
     *
     * @param string $time1 mm:ss
     * @param string $time2 mm:ss
     *
     * @return string
     */
    public static function addTime($time1, $time2)
    {

        $time1 = ($time1 === null || $time1 === '') ? '00:00' : $time1;
        $time2 = ($time2 === null || $time2 === '') ? '00:00' : $time2;

        list($time1_hour, $time1_min) = explode(':', $time1);
        list($time2_hour, $time2_min) = explode(':', $time2);

        $sum_hour = $time1_hour + $time2_hour;
        $sum_min  = $time1_min + $time2_min;

        if(($sum_min / 60) > 0){
            $overflow = floor($sum_min / 60);
            $sum_min  = $sum_min - ($overflow * 60);
            $sum_hour += $overflow;
        }

        return $sum_hour . ':' . sprintf('%02d', $sum_min);

    }

    /**
     * 24時間を超える終了時間を計算して返す
     *
     * @param int $timestamp1 開始日時(タイムスタンプ)
     * @param int $timestamp2 終了日時(タイムスタンプ)
     *
     * @return string
     */
    public static function calcOver24HTime($timestamp1, $timestamp2=null)
    {

        if(!is_int($timestamp1)){
            $timestamp1 = strtotime($timestamp1);
        }

        if(!is_int($timestamp2)){
            $timestamp2 = strtotime($timestamp2);
        }else if($timestamp2 === null){
            $timestamp2 = time();
        }

        $day1  = date('d', $timestamp1);
        $day2  = date('d', $timestamp2);
        $hour2 = date('H', $timestamp2);
        $hour2 += ($day2 - $day1) * 24;

        return $hour2 . ':' . date('i', $timestamp2);
    }

    /**
     * コマンドラインでコマンドを実行する
     *
     * @param string  $command
     * @param boolean $capture_stderr
     *
     * @return array
     */
    public static function command($command, $capture_stderr = false)
    {
        $output = array();
        $return = 0;
        if($capture_stderr === true){
            $command .= ' 2>&1';
        }
        exec($command, $output, $return);
        $imp_output = implode("\n", $output);

        return array('output' => $imp_output, 'return' => $return);
    }

    /**
     * データのサニタイジング
     *
     * @param  mixed $data
     *
     * @return mixed
     */
    public static function sanitize($data)
    {
        if(is_array($data)){
            return array_map('self::sanitize', $data);
        }else{
            return ($data === null) ? null : htmlspecialchars($data, ENT_QUOTES);
        }

    }

    /**
     * 全角英数を半角英数に置換
     *
     * @param  mixed $data
     *
     * @return mixed
     */
    public static function convertEmToEn($data)
    {
        if(is_array($data)){
            return array_map('self::convertEmToEn', $data);
        }else{
            return mb_convert_kana($data, 'as', 'UTF-8');
        }
    }

    /**
     * ページネート用パラメータ取得
     *
     * @param  int $page
     * @param  int $limit
     * @param  int $page_items
     * @param  int $total_items
     *
     * @return array
     */
    public static function getPaginateParams($page, $limit, $page_items, $total_items)
    {

        //ページ数算出
        $total_pages = (int)ceil($total_items / $limit);

        //10ページ以上存在する場合は範囲を絞る
        if($total_pages >= 10){
            //最初から5ページ以内
            if($page <= 6){
                $paging_first = 1;
                $paging_last  = 10;
                //最後から5ページ以内
            }else if($page >= ($total_pages - 4)){
                $paging_first = $total_pages - 9;
                $paging_last  = $total_pages;
            }else{
                $paging_first = $page - 5;
                $paging_last  = $page + 4;
            }
        }else{
            $paging_first = 1;
            $paging_last  = $total_pages;
        }

        $paginate = array(
            'current'      => $page,
            'first'        => 1,
            'last'         => $total_pages,
            'next'         => ($page === $total_pages) ? $total_pages : $page + 1,
            'before'       => ($page === 1) ? 1 : $page - 1,
            'total_pages'  => $total_pages,
            'page_items'   => $page_items,
            'offset_first' => ($page === 1) ? 1 : $limit * ($page - 1) + 1,
            'offset_last'  => ($page === $total_pages) ? $total_items : (($page === 1) ? $limit : $limit * $page),
            'total_items'  => $total_items,
            'paging_first' => $paging_first,
            'paging_last'  => $paging_last
        );

        return $paginate;

    }

    /**
     * 多次元配列→一次元配列の変換
     * 配列キーをもとに指定区切り文字、囲み文字を用いて生成した文字列をキーとする一次元配列
     *
     * @param  array  $values          対象配列
     * @param  string $prefix          生成キーの接頭辞
     * @param  string $delimiter       区切り文字
     * @param  string $enclosure_start 囲み文字開始文字
     * @param  string $enclosure_end   囲み文字終了文字
     * @return array                   変換後の一次元配列
     */
    public static function convArrayMultiToSingle($values, $prefix = '', $delimiter = '', $enclosure_start = '', $enclosure_end = '')
    {
        $result = array();
        foreach ($values as $key => $value) {
            // 配列キーの生成
            $new_key  = (strlen($prefix)) ? $prefix . $delimiter : '';
            $new_key .= $enclosure_start . $key . $enclosure_end;

            // 配列要素の追加
            if (is_array($value)) {
                // 対象が配列の場合は再帰処理
                $result = array_merge(
                    $result,
                    Common::convArrayMultiToSingle($value, $new_key, $delimiter, $enclosure_start, $enclosure_end)
                );
            } else {
                $result[$new_key] = $value;
            }
        }
        return $result;
    }

    /**
     * キャメルケースにする
     *
     * @param string $str
     *
     * @return mixed
     */
    public static function camelize($str)
    {
        return str_replace(' ', '', ucwords(str_replace('_',' ',$str)));
    }

    /**
     * スネークケースにする
     *
     * @param string $str
     *
     * @return string
     */
    public static function snakize($str) {
        return strtolower(preg_replace('/[a-z]+(?=[A-Z])|[A-Z]+(?=[A-Z][a-z])/', '\0_', $str));
    }

    /**
     * GFM形式に変換する
     *
     * @param string $markdown
     *
     * @return string
     */
    public static function convertToGithubFlavoredMarkdown($markdown)
    {
        $parser                 = new \cebe\markdown\GithubMarkdown();
        $parser->html5          = true;
        $parser->enableNewlines = true;
        $html = $parser->parse($markdown);
        return $html;
    }

}

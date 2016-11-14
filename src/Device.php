<?php

/**
 * デバイス判定クラス
 *
 * @author    Noriyoshi Takahashi
 * @copyright Copyright (c) 2015, Noriyoshi Takahashi
 * @version   1.0 (2016.01.07 created)
 */
class Device
{

    protected function __construct() { }

    /**
     * デバイスタイプを取得する
     * @return String
     */
    public static function getDeviceType($ua)
    {

        if ($ua === null) {
            return 'PC';
        } else if((strpos($ua, 'Android') !== false) && (strpos($ua, 'Mobile') !== false) || (strpos($ua, 'iPhone') !== false) || (strpos($ua, 'Windows Phone') !== false) || (strpos($ua, 'blackberry') !== false) || (strpos($ua, 'Windows Phone') !== false)){
            return 'SmartPhone';
        }else if((strpos($ua, 'Android') !== false) || (strpos($ua, 'iPad') !== false)){
            return 'Tablet';
        }else if((strpos($ua, 'DoCoMo') !== false) || (strpos($ua, 'KDDI') !== false) || (strpos($ua, 'SoftBank') !== false) || (strpos($ua, 'Vodafone') !== false) || (strpos($ua, 'J-PHONE') !== false)){
            return 'FeaturePhone';
        }else{
            return 'PC';
        }

    }

    /**
     * ユーザーエージェントを返す
     * @return mixed
     */
    public static function getUserAgent()
    {
        return filter_input(INPUT_SERVER, 'HTTP_USER_AGENT');
    }

    /**
     * docomoか否か
     * @return Boolean
     */
    public static function isDocomo()
    {
        $ua = self::getUserAgent();
        return (strpos($ua, 'DoCoMo') !== false);
    }

    /**
     * Softbankか否か
     * @return Boolean
     */
    public static function isSoftbank()
    {
        $ua = self::getUserAgent();
        return ((strpos($ua, 'SoftBank') !== false) || (strpos($ua, 'Vodafone') !== false) || (strpos($ua, 'J-PHONE') !== false));
    }

    /**
     * Ezwebか否か
     * @return Boolean
     */
    public static function isEzweb()
    {
        $ua = self::getUserAgent();
        return (strpos($ua, 'KDDI') !== false);
    }

    /**
     * 携帯電話か否か
     * @return Boolean
     */
    public static function isMobile()
    {
        $type = self::getDeviceType(self::getUserAgent());
        return (($type === 'SmartPhone') || ($type === 'FeaturePhone'));
    }

    /**
     * スマートフォンか否か
     * @return Boolean
     */
    public static function isSmartPhone()
    {
        $type = self::getDeviceType(self::getUserAgent());
        return ($type === 'SmartPhone');
    }

    /**
     * フィーチャーフォンか否か
     * @return Boolean
     */
    public static function isFeaturePhone()
    {
        $type = self::getDeviceType(self::getUserAgent());
        return ($type === 'FeaturePhone');
    }

    /**
     * タブレット端末か否か
     * @return Boolean
     */
    public static function isTablet()
    {
        $type = self::getDeviceType(self::getUserAgent());
        return ($type === 'Tablet');
    }

    /**
     * PC端末か否か
     * @return Boolean
     */
    public static function isPc()
    {
        $type = self::getDeviceType(self::getUserAgent());
        return ($type === 'PC');
    }

    /**
     * IE8か否か
     * @return Boolean
     */
    public static function isIE8()
    {
        $ua = self::getUserAgent();
        return (strpos($ua, 'MSIE 8.0') !== false);
    }

    /**
     * フィーチャーフォン用HTMLヘッダを取得
     * @return String
     */
    public static function getFpHtmlHeader()
    {
        if(self::isDocomo()){
            $htmlHeader  = '<?xml version="1.0" encoding="UTF-8"?>';
            $htmlHeader .= '<!DOCTYPE html PUBLIC "-//i-mode group (ja)//DTD XHTML i-XHTML(Locale/Ver.=ja/2.2) 1.0//EN" "i-xhtml_4ja_10.dtd">';
        }else if(self::isSoftbank()){
            $htmlHeader  = '<?xml version="1.0" encoding="UTF-8"?>';
            $htmlHeader .= '<!DOCTYPE html PUBLIC "-//J-PHONE//DTD XHTML Basic 1.0 Plus//EN" "xhtml-basic10-plus.dtd">';
        }else if(self::isEzweb()){
            $htmlHeader  = '<?xml version="1.0" encoding="UTF-8"?>';
            $htmlHeader .= '<!DOCTYPE html PUBLIC "-//OPENWAVE//DTD XHTML 1.0//EN" "http://www.openwave.com/DTD/xhtml-basic.dtd">';
        }else{
            $htmlHeader  = '<?xml version="1.0" encoding="UTF-8"?>';
            $htmlHeader .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
        }
        return $htmlHeader;
    }

    /**
     * フィーチャーフォン用コンテンツタイプを取得
     * @return String
     */
    public static function getFpMetaContentType()
    {
        if(self::isDocomo()){
            $contentType = '<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=UTF-8">';
        }else if(self::isSoftbank()){
            $contentType = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        }else if(self::isEzweb()){
            $contentType = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        }else{
            $contentType = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        }
        return $contentType;
    }

    /**
     * ユーザーエージェントでブラウザ情報を取得する
     * @return array
     */
    public static function getBrowser()
    {
        $browser = array();
        $ua = self::getUserAgent();
        if(preg_match('/Trident\/(\d{1,}(.\d{1,}){1,}?)/i', $ua, $matches)){

            $browser['name'] = 'Internet Explorer';

            if((float)$matches[1] >= 7){
                if(preg_match('/rv:(\d{1,}(.\d{1,}){1,}?)/i', $ua, $matches)){
                    $browser['version'] = (float)$matches[1];
                }else{
                    $browser['version'] = 11.0;
                }
            }elseif((float)$matches[1] >= 6){
                $browser['version'] = 10.0;
            }elseif((float)$matches[1] >= 5){
                $browser['version'] = 9.0;
            }elseif((float)$matches[1] >= 4){
                $browser['version'] = 8.0;
            }
        }else if(preg_match('/MSIE\s(\d{1,}(.\d{1,}){1,}?);/i', $ua, $matches)){

            $browser['name']    = 'Internet Explorer';
            $browser['version'] = (float)$matches[1];
        }else if(preg_match('/Firefox\/(\d{1,}(.\d{1,}){1,}?)/i', $ua, $matches)){

            $browser['name']    = 'Firefox';
            $browser['version'] = (float)$matches[1];
        }else if(preg_match('/Chrome\/(\d{1,}(.\d{1,}){1,}?)/i', $ua, $matches)){

            $browser['name']    = 'Chrome';
            $browser['version'] = (float)$matches[1];
        }else if(preg_match('/Safari/', $ua)){

            $browser['name'] = 'Safari';
            if(preg_match('/Version\/(\d{1,}(.\d{1,}){1,}?)/i', $ua, $matches)){
                $browser['version'] = (float)$matches[1];
            }else{
                $browser['version'] = null;
            }
        }else if(preg_match('/Opera/', $ua)){

            $browser['name'] = 'Opera';
            if(preg_match('/Version\/(\d{1,}(.\d{1,}){1,}?)/i', $ua, $matches)){
                $browser['version'] = (float)$matches[1];
            }else{
                $browser['version'] = null;
            }
        }else{

            $browser['name'] = 'その他';
            if(preg_match('/Version\/(\d{1,}(.\d{1,}){1,}?)/i', $ua, $matches)){
                $browser['version'] = (float)$matches[1];
            }else{
                $browser['version'] = null;
            }
        }

        return $browser;

    }

}

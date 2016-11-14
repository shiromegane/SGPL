<?php

class DeviceComponent extends Phalcon\Mvc\User\Component
{

    public $ua;
    public $type;

    public function __construct()
    {
        $this->ua   = $this->request->getUserAgent();
        $this->type = $this->getDeviceType();
    }

    /**
     * デバイスタイプを取得する
     * @return String
     */
    public function getDeviceType()
    {
        if((strpos($this->ua, 'Android') !== false) && (strpos($this->ua, 'Mobile') !== false) || (strpos($this->ua, 'iPhone') !== false) || (strpos($this->ua, 'Windows Phone') !== false) || (strpos($this->ua, 'blackberry') !== false) || (strpos($this->ua, 'Windows Phone') !== false)){
            return 'SmartPhone';
        }else if((strpos($this->ua, 'Android') !== false) || (strpos($this->ua, 'iPad') !== false)){
            return 'Tablet';
        }else if((strpos($this->ua, 'DoCoMo') !== false) || (strpos($this->ua, 'KDDI') !== false) || (strpos($this->ua, 'SoftBank') !== false) || (strpos($this->ua, 'Vodafone') !== false) || (strpos($this->ua, 'J-PHONE') !== false)){
            return 'FeaturePhone';
        }else{
            return 'PC';
        }

    }

    /**
     * docomoか否か
     * @return Boolean
     */
    public function isDocomo()
    {
        return (strpos($this->ua, 'DoCoMo') !== false);
    }

    /**
     * Softbankか否か
     * @return Boolean
     */
    public function isSoftbank()
    {
        return ((strpos($this->ua, 'SoftBank') !== false) || (strpos($this->ua, 'Vodafone') !== false) || (strpos($this->ua, 'J-PHONE') !== false));
    }

    /**
     * Ezwebか否か
     * @return Boolean
     */
    public function isEzweb()
    {
        return (strpos($this->ua, 'KDDI') !== false);
    }

    /**
     * 携帯電話か否か
     * @return Boolean
     */
    public function isMobile()
    {
        return (($this->type === 'SmartPhone') || ($this->type === 'FeaturePhone'));
    }

    /**
     * スマートフォンか否か
     * @return Boolean
     */
    public function isSmartPhone()
    {
        return ($this->type === 'SmartPhone');
    }

    /**
     * フィーチャーフォンか否か
     * @return Boolean
     */
    public function isFeaturePhone()
    {
        return ($this->type === 'FeaturePhone');
    }

    /**
     * タブレット端末か否か
     * @return Boolean
     */
    public function isTablet()
    {
        return ($this->type === 'Tablet');
    }

    /**
     * PC端末か否か
     * @return Boolean
     */
    public function isPc()
    {
        return ($this->type === 'PC');
    }

    /**
     * IE8か否か
     * @return Boolean
     */
    public function isIE8()
    {
        return (strpos($this->ua, 'MSIE 8.0') !== false);
    }

    /**
     * フィーチャーフォン用HTMLヘッダを取得
     * @return String
     */
    public function getFpHtmlHeader()
    {
        if($this->isDocomo()){
            $htmlHeader  = '<?xml version="1.0" encoding="UTF-8"?>';
            $htmlHeader .= '<!DOCTYPE html PUBLIC "-//i-mode group (ja)//DTD XHTML i-XHTML(Locale/Ver.=ja/2.2) 1.0//EN" "i-xhtml_4ja_10.dtd">';
        }else if($this->isSoftbank()){
            $htmlHeader  = '<?xml version="1.0" encoding="UTF-8"?>';
            $htmlHeader .= '<!DOCTYPE html PUBLIC "-//J-PHONE//DTD XHTML Basic 1.0 Plus//EN" "xhtml-basic10-plus.dtd">';
        }else if($this->isEzweb()){
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
    public function getFpMetaContentType()
    {
        if($this->isDocomo()){
            $contentType = '<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=UTF-8">';
        }else if($this->isSoftbank()){
            $contentType = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        }else if($this->isEzweb()){
            $contentType = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        }else{
            $contentType = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        }
        return $contentType;
    }

    /**
     * ユーザーエージェントでブラウザ情報を取得する
     * @param string $ua
     * @return array
     */
    public static function getBrowser($ua)
    {
        $browser = array();

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

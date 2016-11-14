<?php

class Slack
{

    protected static $url = null;
    protected static $channel = null;
    protected static $username = null;
    protected static $icon_emoji = null;
    protected static $icon_url = null;
    protected static $text = null;

    /**
     * @return mixed
     */
    public static function getUrl()
    {
        return self::$url;
    }

    /**
     * @param mixed $url
     */
    public static function setUrl($url)
    {
        self::$url = $url;
    }

    /**
     * @return mixed
     */
    public static function getChannel()
    {
        return self::$channel;
    }

    /**
     * @param mixed $channel
     */
    public static function setChannel($channel)
    {
        self::$channel = $channel;
    }

    /**
     * @return mixed
     */
    public static function getUsername()
    {
        return self::$username;
    }

    /**
     * @param mixed $username
     */
    public static function setUsername($username)
    {
        self::$username = $username;
    }

    /**
     * @return mixed
     */
    public static function getIconEmoji()
    {
        return self::$icon_emoji;
    }

    /**
     * @param mixed $icon_emoji
     */
    public static function setIconEmoji($icon_emoji)
    {
        self::$icon_emoji = $icon_emoji;
    }

    /**
     * @return mixed
     */
    public static function getIconUrl()
    {
        return self::$icon_url;
    }

    /**
     * @param mixed $icon_url
     */
    public static function setIconUrl($icon_url)
    {
        self::$icon_url = $icon_url;
    }

    /**
     * @return mixed
     */
    public static function getText()
    {
        return self::$text;
    }

    /**
     * @param mixed $text
     */
    public static function setText($text)
    {
        self::$text = $text;
    }

    /**
     * @return array
     */
    protected static function getPayload()
    {
        $payload = ['text' => (self::$text == null) ? '(Message is empty)' : self::$text];
        $payload += (self::$channel === null) ? [] : ['channel' => self::$channel];
        $payload += (self::$username === null) ? [] : ['username' => self::$username];
        $payload += (self::$icon_url === null) ? [] : ['icon_url' => self::$icon_url];
        $payload += (self::$icon_emoji === null) ? [] : ['icon_emoji' => self::$icon_emoji];
        return ['payload' => json_encode($payload)];
    }

    /**
     * @param string $message
     *
     * @return array
     */
    public static function post($message = null)
    {

        if ($message !== null) {
            self::setText($message);
        }

        $options = [
            CURLOPT_URL            => self::$url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => self::getPayload(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $result      = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header      = substr($result, 0, $header_size);
        $result      = substr($result, $header_size);
        curl_close($ch);

        return [
            'Header' => $header,
            'Result' => $result,
        ];

    }
    
}
<?php

/**
 * クラスローダー
 * @author    Noriyoshi Takahashi
 * @copyright Copyright (c) 2015, Noriyoshi Takahashi
 * @version   1.00 (2015.01.05 created)
 * @usage
 *   include ClassLoader.php;
 *   ClassLoader::setDirectories(array([path],[path],...));
 *   ClassLoader::register();
 */
class ClassLoader
{

    /**
     * ディレクトリの配列
     * @var array
     */
    protected static $directories = array();

    /**
     * 読み込むディレクトリを設定
     * @param array $directories
     */
    public static function setDirectories($directories)
    {
        self::$directories = $directories;
    }

    /**
     * クラス登録
     * @return bool
     */
    public static function register()
    {
        spl_autoload_register(function ($class_name) {
            foreach(self::$directories as $directory){
                return self::includeRecursive($directory, $class_name);
            }
            return false;
        });
    }

    /**
     * 再帰的に読み込む
     * @param  string $directory
     * @param  string $class_name
     * @return bool
     */
    protected static function includeRecursive($directory, $class_name)
    {
        $files = glob("{$directory}/*");
        if(!empty($files)){
            foreach($files as $file_path){
                if(is_dir($file_path)){
                    if(self::includeRecursive($file_path, $class_name)){
                        return true;
                    }
                }else{
                    if(is_readable($file_path) && (basename($file_path) === "{$class_name}.php")){
                        include $file_path;
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
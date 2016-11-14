<?php

/**
 * ローダー
 * @author    Noriyoshi Takahashi
 * @copyright Copyright (c) 2015, Noriyoshi Takahashi
 * @version   1.01 (2016.01.07 modified)
 * @usage
 *   include Loader.php;
 *   Loader::addDirectory(array([path],[path],...));
 *   Loader::register();
 */
class Loader
{

    /**
     * ディレクトリの配列
     * @var array
     */
    protected static $directories = array();

    /**
     * 読み込むディレクトリを追加
     * @param array $path
     */
    public static function addDirectory($path)
    {
        if(is_array($path)){
            self::$directories = $path;
        }else {
            self::$directories[] = $path;
        }
    }

    /**
     * クラス登録
     * @return bool
     */
    public static function register()
    {
        spl_autoload_register(function ($class_name) {
            if(count(self::$directories) > 0) {
                foreach (self::$directories as $directory) {
                    self::includeClass($directory, $class_name);
                }
                return true;
            }
            return false;
        });
    }

    /**
     * 再帰的に読み込む （使わないかも）
     * @param  string $directory
     * @param  string $class_name
     * @return bool
     */
    //protected static function includeRecursive($directory, $class_name)
    //{
    //    $files = glob("{$directory}/*");
    //    if(!empty($files)){
    //        foreach($files as $file_path){
    //            if(is_dir($file_path)){
    //                if(self::includeRecursive($file_path, $class_name)){
    //                    return true;
    //                }
    //            }else{
    //                if(is_readable($file_path) && (basename($file_path) === "{$class_name}.php")){
    //                    include $file_path;
    //                    return true;
    //                }
    //            }
    //        }
    //    }
    //    return false;
    //}

    /**
     * オートロード
     * @param string|array $path
     * @return bool
     */
    public static function addAutoloadPath($path)
    {
        self::addDirectory($path);
        return self::register();
    }

    /**
     * Classを再帰的にinclude
     * @param string $directory
     * @param string $class_name
     * @return bool
     */
    protected static function includeClass($directory, $class_name)
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach($files as $file){
            $file_path = $file->getPathname();
            if($file->isFile() && $file->isReadable() && (basename($file_path) === "{$class_name}.php")){
                include $file_path;
            }
        }
    }

    /**
     * インクルードパスの設定
     * @param string|array $path
     */
    public static function addIncludePath($path)
    {
        if(is_array($path)){
            set_include_path(get_include_path() . PATH_SEPARATOR . implode(PATH_SEPARATOR, $path));
        }else{
            set_include_path(get_include_path() . PATH_SEPARATOR . $path);
        }
    }

}
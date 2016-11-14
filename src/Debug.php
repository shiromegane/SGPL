<?php

/**
 * デバッグ用クラス
 * ※ NtLibs/Loggerクラスと併用すること
 *
 * Ver1.31 変更点:
 *   プロファイリング系関数をBenchmarkクラスに移行
 *
 * @author    Noriyoshi Takahashi
 * @copyright Copyright (c) 2015, Noriyoshi Takahashi
 * @version   1.31 (2016.02.12 modified)
 */
class Debug
{

    /** インスタンスを生成させない */
    protected function __construct() { }


    /**
     * デバッグダンプ
     *
     * @param mixed   $value   値
     * @param boolean $display 画面に表示するか否か
     */
    public static function dump($value = null, $display = true)
    {

        //バックトレース取得
        $backtrace = debug_backtrace();

        //XDEBUGの制限解除
        ini_set('xdebug.var_display_max_children', -1);
        ini_set('xdebug.var_display_max_data', -1);
        ini_set('xdebug.var_display_max_depth', -1);

        //出力をバッファリング
        ob_start();
        var_dump($value);
        $ob    = ob_get_clean();
        $value = PHP_EOL . html_entity_decode(preg_replace('/\]\=\>\n(\s+)/m', '] => ', $ob), ENT_QUOTES);
        $value = mb_convert_encoding($value, mb_internal_encoding(), 'ASCII,JIS,UTF-8,EUC-JP,SJIS');

        if($display){
            echo '<span style="font-family:\'Source Han Code JP\',Consolas,\'Courier New\',Courier,Monaco,monospace;font-size:12px;box-sizing:border-box;display:inline-block;width:100%;color:#a9b7c6;background:#313335;padding:4px;border-radius:4px 4px 0 0;word-break:break-all;">
                  Dump by <span style="color:#6a8759;">\'' . $backtrace[0]['file'] . '\'</span> at line <span style="color:#6897ba;">' . $backtrace[0]['line'] . '</span></span>';
            echo '<pre class="debug-output" style="font-family:\'Source Han Code JP\',Consolas,\'Courier New\',Courier,Monaco,monospace;font-size:11px;box-sizing:border-box;display:inline-block;width:100%;text-align:left; border-radius:0 0 4px 4px; background:#2b2b2b; color:#a9b7c6; padding:8px; clear:both; margin-top:0; margin-bottom:2px;">';
            $pattern     = array(
                '/NULL/',
                '/bool\((.+)\)/',
                '/string\((\d+)\) "(.*)"/',
                '/int\((\d+)\)/',
                '/int\((-\d+)\)/',
                '/array\((\d+)\)/',
                '/(object)\((.*)\)(\#\d+).*(\(\d+\))/',
                '/(^resource.*)\{/',
                '/float\((.+)\)/',
                '/(\["(.+)"\] => )/',
                '/(\["(.+)":"(.+)":(.*)\] => )/',
                '/(\["(.+)":(.*)\] => )/',
                '/(\[(.+)\] => )/',
                '/\{\n/',
                '/\}\n/'
            );
            $replacement = array(
                '<span style="color:#cc7932;">null</span>',
                '<span style="color:#cc7932;">$1</span>',
                '<span style="color:#cc7932;">string($1)</span> <span style="color:#6a8759;">\'$2\'</span>',
                '<span style="color:#cc7932;">int</span> <span style="color:#6897ba;">$1</span>',
                '<span style="color:#cc7932;">int</span> <span style="color:#6897ba;">$1</span>',
                '<span style="color:#cc7932;">array($1)</span>',
                '<span style="color:#cc7932;">$1</span> $2 <span style="color:#cc7932;">$3$4</span>',
                '<span style="color:#cc7932;">$1</span>',
                '<span style="color:#cc7932;">float</span> <span style="color:#6897ba;">$1</span>',
                '<span style="color:#9876aa;">\'$2\'</span> => ',
                '<span style="color:#cc7932;">$4</span> $3 <span style="color:#9876aa;">\'$2\'</span> => ',
                '<span style="color:#cc7932;">$3</span> <span style="color:#9876aa;">\'$2\'</span> => ',
                '<span style="color:#6897ba;">$2</span> => ',
                '<span style="color:#a9b7c6;">' . "{\n" . '</span>',
                '<span style="color:#a9b7c6;">' . "}\n" . '</span>',
            );

            $display_value = preg_replace($pattern, $replacement, $value);
            echo $display_value;
            echo '</pre>';
        }else{
            $pattern     = array(
                '/bool\((.+)\)/',
                '/string\((\d+)\) \"(.*)\"/',
                '/int\((\d+)\)/',
                '/int\((-\d+)\)/',
                '/array\((\d+)\) /',
                '/float\((.+)\)/',
                '/(\["(.+)"\] => )/',
                '/(\[(.+)\] => )/',
                '/\{\n/',
                '/\}\n/'
            );
            $replacement = array(
                '$1 | bool',
                '\'$2\' | string[$1]',
                '$1 | int',
                '$1 | int',
                'array[$1]',
                '$1 | float',
                '\'$2\' => ',
                '$2 => ',
                "(\n",
                ")\n"
            );
            $value   = ltrim(preg_replace($pattern, $replacement, $value), "\n");
            $message = "Dump by '{$backtrace[0]['file']}' at line {$backtrace[0]['line']}" . PHP_EOL;
            $message .= $value;
            Logger::debug($message);
        }

    }

    /**
     * デバッグダンプCLI用
     *
     * @param mixed   $value   値
     * @param boolean $display 画面に表示するか否か
     */
    public static function cliDump($value = null, $display=true)
    {

        //バックトレース取得
        $backtrace = debug_backtrace();

        //XDEBUGの制限解除
        ini_set('xdebug.var_display_max_children', -1);
        ini_set('xdebug.var_display_max_data', -1);
        ini_set('xdebug.var_display_max_depth', -1);

        //出力をバッファリング
        ob_start();
        var_dump($value);
        $ob    = ob_get_clean();
        $value = PHP_EOL . html_entity_decode(preg_replace('/\]\=\>\n(\s+)/m', '] => ', $ob), ENT_QUOTES);
        $value = mb_convert_encoding(strip_tags($value), mb_internal_encoding(), 'ASCII,JIS,UTF-8,EUC-JP,SJIS');

        $pattern     = array(
            '/bool\((.+)\)/',
            '/string\((\d+)\) \"(.*)\"/',
            '/int\((\d+)\)/',
            '/int\((-\d+)\)/',
            '/array\((\d+)\) /',
            '/float\((.+)\)/',
            '/(\["(.+)"\] => )/',
            '/(\[(.+)\] => )/',
            '/\{\n/',
            '/\}\n/'
        );
        $replacement = array(
            '$1 | bool',
            '\'$2\' | string[$1]',
            '$1 | int',
            '$1 | int',
            'array[$1]',
            '$1 | float',
            '\'$2\' => ',
            '$2 => ',
            "(\n",
            ")\n"
        );

        $value = ltrim(preg_replace($pattern, $replacement, $value), "\n");

        if($display){
            echo str_repeat('=', 80) . PHP_EOL;
            echo "Dump by '{$backtrace[0]['file']}' at line {$backtrace[0]['line']}". PHP_EOL;
            echo str_repeat('-', 80) . PHP_EOL;
            echo $value;
            echo str_repeat('-', 80) . PHP_EOL;
        }else{
            $message = "Dump by '{$backtrace[0]['file']}' at line {$backtrace[0]['line']}" . PHP_EOL;
            $message .= $value;
            Logger::debug($message);
        }

    }

}

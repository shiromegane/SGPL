<?php

/**
 * ゲーム関連クラス
 * @author    Noriyoshi Takahashi
 * @copyright Copyright (c) 2015, Noriyoshi Takahashi
 * @version   1.0 (2015.05.12 created)
 */
class Game
{

    /** インスタンスを生成させない */
    protected function __construct() { }

    /**
     * 経験値のパーセンテージを取得する
     *
     * @param int $exp       現在の経験値
     * @param int $prev_exp  現在のレベルの必要経験値
     * @param int $next_exp  次のレベルの必要経験値
     * @param int $precision 小数点以下の桁数
     *
     * @return float
     */
    public static function getExpPercentage($exp, $prev_exp, $next_exp, $precision = 2)
    {
        return round(100 * round(($exp - $prev_exp) / ($next_exp - $prev_exp), 4), $precision);
    }

    /**
     * 現在あるべきLvと経験値を取得する
     *
     * @param int $lv
     * @param int $exp
     *
     * @return array
     */
    public static function getLevelAndExp($lv, $exp)
    {

        $next_lv_exp    = self::getNextLevelExp($lv);
        $prev_lv_exp    = self::getNextLevelExp($lv - 1);
        $exp_percentage = self::getExpPercentage($exp, $prev_lv_exp, $next_lv_exp);

        if($exp >= $next_lv_exp){
            //次のLvに必要な経験値以上ならLvを+1して再帰処理
            return self::getLevelAndExp($lv + 1, $exp);
        }else{
            $result = array(
                'level'          => (int)$lv,
                'exp'            => (int)$exp,
                'prev_level_exp' => (int)$prev_lv_exp,
                'next_level_exp' => (int)$next_lv_exp,
                'exp_percentage' => $exp_percentage
            );

            return $result;
        }

    }

    /**
     * 次のレベルに必要な経験値を取得
     *
     * @param  int $lv
     *
     * @return int
     */
    public static function getNextLevelExp($lv)
    {
        return (int)floor($lv * ($lv + 1 * 0.5) * 2);
    }

}

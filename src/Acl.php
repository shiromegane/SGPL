<?php

/**
 * ACL用クラス
 * @author    Noriyoshi Takahashi
 * @copyright Copyright (c) 2016, Noriyoshi Takahashi
 * @version   1.00 (2016.02.03 created)
 */
class Acl
{

    protected static $control_targets;
    protected static $ignore_targets;
    protected static $default_role_action_map;
    protected static $individual_role_action_map;
    protected static $allow_actions;

    /**
     * @param mixed $control_targets
     */
    public static function setControlTargets($control_targets)
    {
        self::$control_targets = $control_targets;
    }

    /**
     * @param mixed $ignore_targets
     */
    public static function setIgnoreTargets($ignore_targets)
    {
        self::$ignore_targets = $ignore_targets;
    }

    /**
     * @param mixed $default_role_action_map
     */
    public static function setDefaultRoleActionMap($default_role_action_map)
    {
        self::$default_role_action_map = $default_role_action_map;
    }

    /**
     * @param mixed $individual_role_action_map
     */
    public static function setIndividualRoleActionMap($individual_role_action_map)
    {
        self::$individual_role_action_map = $individual_role_action_map;
    }

    /**
     * コントローラ名で許可アクションリストを取得
     * @param  string $controller
     * @return array
     */
    public static function getAllowActions($controller)
    {
        return self::$allow_actions[$controller];
    }

    /**
     * 権限ビットを渡して許可するアクションリストを作成する
     *
     * @param array $roles
     * @param string $controller
     */
    public static function setAllowActions($roles, $controller)
    {

        //権限種別を取得
        $acl_kind = self::getKindByController($controller);

        //権限ビット値
        $role_bit = ($acl_kind !== null) && array_key_exists($acl_kind, $roles) ? $roles[$acl_kind] : 0;

        //権限アクションマッピング
        $role_action_map = (array_key_exists($controller, self::$individual_role_action_map)) ? self::$individual_role_action_map[$controller] : self::$default_role_action_map;

        //許可アクションリスト生成
        $allow_actions = array_key_exists($controller, self::$ignore_targets) ? self::$ignore_targets[$controller] : [];

        if (is_array($allow_actions)) {
            foreach ($role_action_map as $role => $actions) {
                if (($role_bit & $role) > 0) {
                    foreach ($actions as $action) {
                        $allow_actions[] = $action;
                    }
                }
            }
            self::$allow_actions[$controller] = array_values(array_unique($allow_actions));
        } else {
            self::$allow_actions[$controller] = $allow_actions;
        }

    }

    /**
     * コントローラ名からアクセス制御種別を取得
     * @param  string $controller
     * @return bool
     */
    public static function getKindByController($controller) {

        foreach (self::$control_targets as $kind => $controllers) {
            if (in_array($controller, $controllers)) {
                return $kind;
            }
        }
        return null;

    }

    /**
     * アクセスが許可されているか否か
     * @param string $controller
     * @param string $action
     * @return bool
     */
    public static function isAllowAccess($controller, $action)
    {

        if (self::isIgnoreAction($controller, $action)) {
            //無視リストにあれば許可
            return true;
        } else {
            //許可アクションリストを取得
            $allow_actions = self::getAllowActions($controller);
            //許可判定
            if (is_array($allow_actions)) {
                return (in_array($action, $allow_actions) || (in_array(strtolower($action), $allow_actions)));
            } else {
                return (($allow_actions === '*') || ($allow_actions === $action) || ($allow_actions === strtolower($action)));
            }
        }

    }

    /**
     * アクセス制御を無視するアクションか否か
     *
     * @param  string $controller
     * @param  string $action
     * @return boolean
     */
    public static function isIgnoreAction($controller, $action)
    {

        if (array_key_exists($controller, self::$ignore_targets)) {
            $ignore_actions = self::$ignore_targets[$controller];
            if (is_array($ignore_actions)) {
                return (in_array($action, $ignore_actions) || in_array(strtolower($action), $ignore_actions));
            } else {
                return (($ignore_actions === '*') || ($action === $ignore_actions) || (strtolower($action) === $ignore_actions));
            }
        }

        return false;

    }

    /**
     * 全アクションに対してアクセス制御を無視するか否か
     * @param  string $controller
     * @return bool
     */
    public static function isIgnoreAll($controller)
    {

        if (array_key_exists($controller, self::$ignore_targets)) {
            $ignore_action = self::$ignore_targets[$controller];
            return is_string($ignore_action) && ($ignore_action === '*');
        }

        return false;

    }

}

<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

final class Lib_Config {

    static function GetConfig($key = null) {
        if (!empty($key)) {
            if (strpos($key, '.')) {
                $name = explode('.', $key);
                $name[0] = strtolower($name[0]);
                return isset($GLOBALS['config'][$name[0]][$name[1]]) ? $GLOBALS['config'][$name[0]][$name[1]] : null;
            } else {
                return $GLOBALS['config'][$key];
            }
        }else {
                return $GLOBALS['config'];
            }
    }

    static function SetConfig($key, $value = NULL) {
        if (is_array($key)) {
            array_merge($GLOBALS['config'], $key);
        }
        if (!empty($value)) {
            $GLOBALS['config'][$key] = $value;
        }
    }

}

?>

<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Cookie
 *
 * @author WXY
 */
class Cookie {

    // 判断Cookie是否存在
    static function is_set($name) {
        return isset($_COOKIE[Lib_Config::GetConfig('cookie.COOKIE_PREFIX') . $name]);
    }

    // 获取某个Cookie值
    static function get($name) {
        $value = $_COOKIE[Lib_Config::GetConfig('cookie.COOKIE_PREFIX') . $name];
        $value = unserialize(base64_decode($value));
        return $value;
    }

    // 设置某个Cookie值
    static function set($name, $value, $expire = '', $path = '', $domain = '') {
        $config = Lib_Config::GetConfig('cookie');
        if ($expire == '') {
            $expire = $config['COOKIE_EXPIRE'];
        }
        if (empty($path)) {
            $path = $config['COOKIE_PATH'];
        }
        if (empty($domain)) {
            $domain = $config['COOKIE_DOMAIN'];
        }
        $expire = !empty($expire) ? time() + $expire : 0;
        $value = base64_encode(serialize($value));
        setcookie($config['COOKIE_PREFIX'] . $name, $value, $expire, $path, $domain);
        $_COOKIE[$config['COOKIE_PREFIX'] . $name] = $value;
    }

    // 删除某个Cookie值
    static function delete($name) {
        Cookie::set($name, '', -3600);
        unset($_COOKIE[Lib_Config::GetConfig('cookie.COOKIE_PREFIX') . $name]);
    }

    // 清空Cookie值
    static function clear() {
        unset($_COOKIE);
    }

}

?>

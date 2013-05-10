<?php

/**
 * 仿TP的 C方法
 * @staticvar null $_config 
 * @param type $k
 * @param type $v
 * @return type
 */
function C($k = null, $v = NULL) {
    static $_config = array();
    if ($k == null) {
        return $_config;
    }
    if (is_array($k)) {
        $_config = array_merge($_config, $k);
    } else {
        if (isset($v)) {
            $_config[$k] = $v;
        } else {
            return !empty($_config[$k]) ? $_config[$k] : null;
        }
    }
}

/**
 * 是否AJAX请求
 * @access protected
 * @return boolean
 */
function isAjax() {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        if ('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
            return true;
    }
    if (!empty($_POST['is_ajax']) || !empty($_GET['is_ajax']))
    // 判断Ajax方式提交
        return true;
    return false;
}

/**
 * 发送HTTP状态
 * @param integer $code 状态码
 * @return void
 */
function send_http_status($code) {
    static $_status = array(
// Success 2xx
200 => 'OK',
 // Redirection 3xx
301 => 'Moved Permanently',
 302 => 'Moved Temporarily ', // 1.1
// Client Error 4xx
400 => 'Bad Request',
 403 => 'Forbidden',
 404 => 'Not Found',
 // Server Error 5xx
500 => 'Internal Server Error',
 503 => 'Service Unavailable',
    );
    if (isset($_status[$code])) {
        header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
        // 确保FastCGI模式下正常
        header('Status:' . $code . ' ' . $_status[$code]);
    }
}

function url($controller, $action, $id = null) {
    $index = "/";
    if (!Lib_Config::GetConfig('Template.isHtaccess')) {
        $index = '/index.php/';
    }
    $suburl = str_replace('/index.php', "", $_SERVER['SCRIPT_NAME']); //localhost下适应
    $url = 'http://' . $_SERVER["HTTP_HOST"] . $suburl . $index . $controller . '/' . $action;
    if (!empty($id)) {
        $url.='/' . $id;
    }
    return $url;
}

/**
 * 
 * @param type $name
 * @return \nameModel|null
 */
function buildModel($name = null) {
    if (!isset($name)) {
        $name = strtolower($GLOBALS['core']['controller']);
        $nameModel = $name . "Model";
    }
    if (file_exists(MODEL_DIR . $nameModel . ".class.php")) {
        require_cache(MODEL_DIR . $nameModel . ".class.php");
        return new $nameModel($name);
    } else {
        trigger_error($name . "Model不存在");
        return null;
    }
}

if (@get_magic_quotes_gpc()) {

    function rs($s) {
        if (is_array($s)) {
            foreach ($s as $k => $v)
                $s[$k] = rs($v);
        } else {
            $s = stripslashes($s);
        }
        return $s;
    }

    $_GET = rs($_GET);
    $_POST = rs($_POST);
    $_COOKIE = rs($_COOKIE);
}

/**
 * 过滤函数
 * @param type $param 参数
 */
function filter_vars(&$param) {
    $param = htmlspecialchars($param);
}

/**
 * 根据运行环境输出调试
 * @param type $var
 * @return null
 */
function dump($var) {
    if ($GLOBALS['config']['setting']['debug']) {
        var_dump($var);
    } else {
        return null;
    }
}

function Tag($tagname, &$content = NULL) {
    $tag = C('tag');
    if (!empty($tagname)) {
        if (isset($tag[$tagname])) {
            $tagfile = explode(',', $tag[$tagname]);
            foreach ($tagfile as $v) {
                require_cache(TAG_DIR . $v . '.tag.php');
                call_user_func(array($v, 'run'), $content);
                if ($content) {
                    continue;
                } else {
                    break;
                }
            }
        }
    }
}

function B() {
    $debugline = array();
    $debug = debug_backtrace();
    foreach ($debug as $k => $v) {
        $debugline[] = '文件:' . $v['file'] . "-" . $v['line'] . "琛�" . $v['class'] . $v['type'] . $v['function'];
    }
    return $debugline;
}

function file_exists_case($filename) {
    if (is_file($filename)) {
        if (basename(realpath($filename)) != basename($filename)) {
            return false;
        } else {
            return true;
        }
    }
    return false;
}

/**
 * 计时、计数、记内存
 * @staticvar null $_timer
 * @param type $timer
 * @param int $type T计时 C记录次数 M 记录内存
 * @return null|||int
 */
function timer($timer = null, $type = 'T') {
    static $_timer = null;
    if (!isset($timer)) {
        return $_timer;
    }
    switch ($type) {
        case 'T':
            if (!isset($_timer[$timer])) {
                $_timer[$timer]['_begin'] = microtime(TRUE);
                return $_timer[$timer]['_begin'];
            } else {
                $_timer[$timer]['_end'] = microtime(TRUE);
                $_timer[$timer]['_cast'] = $_timer[$timer]['_end'] - $_timer[$timer]['_begin'];
                return $_timer[$timer]['_cast'];
            }
            break;
        case 'C':
            if (isset($type['c_' . $timer])) {
                ++$type['c_' . $timer];
                return $type['c_' . $timer];
            } else {
                $type['c_' . $timer] = 1;
                return 1;
            }
            break;
        case 'M':
            if (!isset($_timer[$timer]['men_begin'])) {
                $_timer[$timer]['men_begin'] = memory_get_usage();
                return $_timer[$timer]['men_begin'];
            } else {
                $_timer[$timer]['men_end'] = memory_get_usage();
                $_timer[$timer]['men_cast'] = $_timer[$timer]['men_end'] - $_timer[$timer]['men_begin'];
                return $_timer[$timer]['men_cast'] / 1024 . "KB";
            }
            break;
    }
}

function require_cache($filename) {
    static $_importFiles = array();
    if (!isset($_importFiles[$filename])) {
        if (file_exists_case($filename)) {
            $import = require $filename;
            $_importFiles[$filename] = $import;
            return $_importFiles[$filename];
        } else {
            $_importFiles[$filename] = false;
            return FALSE;
        }
    } else {
        return $_importFiles[$filename];
    }
}

function mkIndex($dir) {
    if (!is_file($dir . 'index.html')) {
        $content = file_get_contents('index.html');
        file_put_contents($dir . 'index.html', $content);
    }
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @return mixed
 */
function get_client_ip($type = 0) {
    $type = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL)
        return $ip[$type];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos = array_search('unknown', $arr);
        if (false !== $pos)
            unset($arr[$pos]);
        $ip = trim($arr[0]);
    }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

/**
 *  获得当前的脚本网址
 *
 * @return    string
 */
if (!function_exists('GetCurUrl')) {

    function GetCurUrl() {
        if (!empty($_SERVER["REQUEST_URI"])) {
            $scriptName = $_SERVER["REQUEST_URI"];
            $nowurl = $scriptName;
        } else {
            $scriptName = $_SERVER["PHP_SELF"];
            if (empty($_SERVER["QUERY_STRING"])) {
                $nowurl = $scriptName;
            } else {
                $nowurl = $scriptName . "?" . $_SERVER["QUERY_STRING"];
            }
        }
        return $nowurl;
    }

}
if (!function_exists('CheckSql')) {

    /**
     * DEDEcms数据库安全处理
     * @param type $db_string
     * @param type $querytype
     * @return type
     */
    function CheckSql($db_string, $querytype = 'select') {
        $clean = '';
        $error = '';
        $old_pos = 0;
        $pos = -1;
        $log_file = LOG_DIR . 'sql.log';
        $userIP = get_client_ip();
        $getUrl = GetCurUrl();

        //如果是普通查询语句，直接过滤一些特殊语法
        if ($querytype == 'select') {
            $notallow1 = "[^0-9a-z@\._-]{1,}(union|sleep|benchmark|load_file|outfile)[^0-9a-z@\.-]{1,}";

            //$notallow2 = "--|/\*";
            if (preg_match("/" . $notallow1 . "/i", $db_string)) {
                fputs(fopen($log_file, 'a+'), "$userIP||$getUrl||$db_string||SelectBreak\r\n");
                exit("<font size='5' color='red'>Safe Alert: Request Error step 1 !</font>");
            }
        }

        //完整的SQL检查
        while (TRUE) {
            $pos = strpos($db_string, '\'', $pos + 1);
            if ($pos === FALSE) {
                break;
            }
            $clean .= substr($db_string, $old_pos, $pos - $old_pos);
            while (TRUE) {
                $pos1 = strpos($db_string, '\'', $pos + 1);
                $pos2 = strpos($db_string, '\\', $pos + 1);
                if ($pos1 === FALSE) {
                    break;
                } elseif ($pos2 == FALSE || $pos2 > $pos1) {
                    $pos = $pos1;
                    break;
                }
                $pos = $pos2 + 1;
            }
            $clean .= '$s$';
            $old_pos = $pos + 1;
        }
        $clean .= substr($db_string, $old_pos);
        $clean = trim(strtolower(preg_replace(array('~\s+~s'), array(' '), $clean)));

        //老版本的Mysql并不支持union，常用的程序里也不使用union，但是一些黑客使用它，所以检查它
        if (strpos($clean, 'union') !== FALSE && preg_match('~(^|[^a-z])union($|[^[a-z])~is', $clean) != 0) {
            $fail = TRUE;
            $error = "union detect";
        }

        //发布版本的程序可能比较少包括--,#这样的注释，但是黑客经常使用它们
        elseif (strpos($clean, '/*') > 2 || strpos($clean, '--') !== FALSE || strpos($clean, '#') !== FALSE) {
            $fail = TRUE;
            $error = "comment detect";
        }

        //这些函数不会被使用，但是黑客会用它来操作文件，down掉数据库
        elseif (strpos($clean, 'sleep') !== FALSE && preg_match('~(^|[^a-z])sleep($|[^[a-z])~is', $clean) != 0) {
            $fail = TRUE;
            $error = "slown down detect";
        } elseif (strpos($clean, 'benchmark') !== FALSE && preg_match('~(^|[^a-z])benchmark($|[^[a-z])~is', $clean) != 0) {
            $fail = TRUE;
            $error = "slown down detect";
        } elseif (strpos($clean, 'load_file') !== FALSE && preg_match('~(^|[^a-z])load_file($|[^[a-z])~is', $clean) != 0) {
            $fail = TRUE;
            $error = "file fun detect";
        } elseif (strpos($clean, 'into outfile') !== FALSE && preg_match('~(^|[^a-z])into\s+outfile($|[^[a-z])~is', $clean) != 0) {
            $fail = TRUE;
            $error = "file fun detect";
        }

        //老版本的MYSQL不支持子查询，我们的程序里可能也用得少，但是黑客可以使用它来查询数据库敏感信息
        elseif (preg_match('~\([^)]*?select~is', $clean) != 0) {
            $fail = TRUE;
            $error = "sub select detect";
        }
        if (!empty($fail)) {
            fputs(fopen($log_file, 'a+'), "$userIP||$getUrl||$db_string||$error\r\n");
            exit("<font size='5' color='red'>Safe Alert: Request Error step 2!</font>");
        } else {
            return $db_string;
        }
    }

}

/**
 * TP的 错误处理方法
 * @param type $error
 */
function halt($error) {
    $e = array();
    if (DEBUG_MODE) {
        //调试模式下输出错误信息
        if (!is_array($error)) {
            $trace = debug_backtrace();
            $e['message'] = $error;
            $e['file'] = $trace[0]['file'];
            $e['class'] = isset($trace[0]['class']) ? $trace[0]['class'] : '';
            $e['function'] = isset($trace[0]['function']) ? $trace[0]['function'] : '';
            $e['line'] = $trace[0]['line'];
            $traceInfo = '';
            $time = date('y-m-d H:i:m');
            foreach ($trace as $t) {
                $traceInfo .= '[' . $time . '] ' . $t['file'] . ' (' . $t['line'] . ') ';
                $traceInfo .=!empty($t['class']) ? $t['class'] : '' . !empty($t['type']) ? $t['type'] : '' . !empty($t['function']) ? $t['function'] : '' . '(';
//                $impode = implode(', ', $t['args']);
//                var_dump($t['args']);
                $traceInfo .=')<br/>';
            }
            $e['trace'] = $traceInfo;
        } else {
            $e = $error;
        }
    } else {
        if (!$GLOBALS['config']['setting']['errorMessage'])
            $e['message'] = is_array($error) ? $error['message'] : $error;
        else
            $e['message'] = $GLOBALS['config']['setting']['errorMessage'];
    }
    // 包含异常页面模板
    include $GLOBALS['config']['setting']['errorPage'];
    Lib_Log::save();
    exit;
}

?>

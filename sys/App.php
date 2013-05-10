<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of App
 *
 * @author Administrator
 */
final class App {

    static $_config = null;
    static $_lib = null;
    static $_class = null;

    static function run($config) {
        //配置加载
        self::loadConfig($config);
        self::defineDir();

        register_shutdown_function(array(__CLASS__, 'fatalError'));
        set_error_handler(array(__CLASS__, 'appError'));
        set_exception_handler(array(__CLASS__, 'appException'));
        spl_autoload_register(array(__CLASS__, 'autoload'));

        //插件加载

        self::newlib('autoload');

        self::loadAction();
        //路由转换
        $path = static::Router();
        //展示页面
        Session::_init();
        self::setWeb($path);
    }

    private static function defineDir() {
        foreach (static::$_config['tree'] as $key => $value) {
            define(strtoupper($key), $value);
        }
        //修改为自动定义
        /* define('HTML_DIR', static::$_config['tree']['HTML_DIR']);
          define('TEMP_DIR', static::$_config['tree']['TEMP_DIR']);
          define('CACHE_DIR', static::$_config['tree']['CACHE_DIR']);
          define('CORE_DIR', static::$_config['tree']['CORE_DIR']);
          define('MODEL_DIR', static::$_config['tree']['MODEL_DIR']);
          define('LIB_DIR', static::$_config['tree']['LIB_DIR']);
          define('DATA_DIR', static::$_config['tree']['DATA_DIR']);
         */
        define('DEBUG_MODE', static::$_config['setting']['debug']);
        C(array('tag' => include CONFIG_DIR . 'tag.php'));//标签 引自TP , 结合tag方法使用  不过并没有用到 
        if (!file_exists(SYSTEM_DIR . 'install.lock')) {
            foreach (static::$_config['tree'] as $dir) {
                if (!file_exists($dir)) {
                    mkdir($dir);
                }
                mkIndex($dir);
            }
            file_put_contents(SYSTEM_DIR . 'install.lock', '1');
        }
    }

    private static function setWeb($path) {
        $controller = null;
        $controllerfilename = NULL;
        $controllerclass = null;
        $action = null;
        $id = 0;
        static::setAction($path);
        if (!empty($path['controller'])) {
            $controller = $path['controller'];
            $controllerfilename = CORE_DIR . $controller . '.class.php';
            if (file_exists($controllerfilename)) {
                require $controllerfilename;
                $model = MODEL_DIR . $controller . '.model.php';
                if (file_exists($model)) {
                    require $model;
                }
            } else {
                $GLOBALS['core']['controller'] = static::$_config['setting']['NotfoundController'];
                $GLOBALS['core']['action'] = static::$_config['setting']['NotfoundAction'];
                require CORE_DIR . static::$_config['setting']['NotfoundController'] . '.class.php';
                $errorcontroller = static::$_config['setting']['NotfoundController'];
                $empty = new $errorcontroller();
                $action = static::$_config['setting']['NotfoundAction'] . 'Action';
                $empty->$action();
                unset($path);
                return;
            }
            $controllerclass = new $controller;
            if (!empty($path['action'])) {
                $action = $path['action'] . 'Action';
                if (method_exists(ucfirst($controller), $action)) {
                    if (!empty($path['id'])) {
                        $id = $path['id'];
                    }
                    if (!empty($id)) {
                        $controllerclass->$action($id);
                    } else {
                        $controllerclass->$action();
                    }
                } else {
                    $GLOBALS['core']['action'] = static::$_config['setting']['defautAction'];
                    $action = static::$_config['setting']['defautAction'] . 'Action';
                    $controllerclass->$action();
                }
            } else {
                $GLOBALS['core']['action'] = static::$_config['setting']['defautAction'];
                $action = static::$_config['setting']['defautAction'] . 'Action';
                $controllerclass->$action();
            }
        } else {
            $GLOBALS['core']['controller'] = static::$_config['setting']['NotfoundController'];
            $GLOBALS['core']['action'] = static::$_config['setting']['NotfoundAction'];
            require CORE_DIR . static::$_config['setting']['NotfoundController'] . '.class.php';
            $empty = new static::$_config['setting']['NotfoundController'] . " Controller()";
            $action = static::$_config['setting']['NotfoundAction'] . 'Action';
            call_user_func(array($empty, $action));
        }
        unset($path);
    }

    private static function setAction($path) {
        $GLOBALS['core']['controller'] = $path['controller'] ? ucfirst($path['controller']) : static::$_config['setting']['defautController'];
        $GLOBALS['core']['action'] = $path['action'] ? ucfirst($path['action']) : static::$_config['setting']['defautAction'];
        define('CORE_C', $GLOBALS['core']['controller']);
        define('CORE_A', $GLOBALS['core']['action']);
    }

    private static function Router() {
        $pathinfo = array('controller' => static::$_config['setting']['defautController'], 'action' => static::$_config['setting']['defautAction']);
        if (!empty($_SERVER['PATH_INFO'])) {
//            dump($_SERVER['PATH_INFO']);
//            dump(pathinfo($_SERVER['PATH_INFO']));
            $routerArray = explode('.', strip_tags($_SERVER['PATH_INFO']));
            dump($routerArray);
            $router = $routerArray[0];
            $routeruri = explode("/", $router);

            array_shift($routeruri);
//            dump($routeruri);
            if (!empty($routeruri[0])) {
                $pathinfo['controller'] = $routeruri[0];
            }
            if (!empty($routeruri[1])) {
                $pathinfo['action'] = $routeruri[1];
            }
            if (!empty($routeruri[2])) {
                $pathinfo['id'] = $routeruri[2];
            }
        }
        return $pathinfo;
    }

    static function Globals($k) {
        return $GLOBALS['config'][$k];
    }

    private static function loadAction() {
        require_cache(SYSTEM_DIR . "Controller.php");
        require_cache(SYSTEM_DIR . 'Model.php');
    }

    private static function newlib($string) {
        foreach (static::Globals($string) as $k => $v) {
            $v = ucfirst(trim($v));
            if (file_exists(LIB_DIR . $v . ".lib.php")) {
                require_cache(LIB_DIR . $v . ".lib.php");
            } else {
                trigger_error($v . "无法加载!");
            }
        }
    }

    private static function loadConfig($config) {
        foreach ($config as $k => $v) {
            $GLOBALS['config'][$k] = $v;
        }
        static::$_config = $GLOBALS['config'];
        unset($config);
    }

    static public function appError($errno, $errstr, $errfile, $errline) {
        switch ($errno) {
            case E_NOTICE://警告仅仅记录
                Lib_Log::record($errstr, $errno);
                break;
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                ob_end_clean();

                // 页面压缩输出支持
                $zlib = ini_get('zlib.output_compression');
                if (empty($zlib))
                    ob_start('ob_gzhandler');
                $errorStr = "$errstr " . $errfile . " 第 $errline 行.";
                Lib_Log::record($errstr, $errno);
                function_exists('halt') ? halt($errorStr) : exit('ERROR:' . $errorStr);
                break;

            case E_STRICT:
            case E_USER_WARNING:
            case E_USER_NOTICE:
            default:
                $errorStr = "[$errno] $errstr " . $errfile . " 第 $errline 行.";
                Lib_Log::record($errstr, $errno);
                if (DEBUG_MODE) {
                    dump($errorStr);
                } else {
                    return;
                }

                break;
        }
    }

    /**
     * 取得对象实例 支持调用类的静态方法 摘自 TP
     * @param string $class 对象类名
     * @param string $method 类的静态方法名
     * @return object
     */
    static public function instance($class, $method = '') {
        $identify = $class . $method;
        if (!isset(self::$_class[$identify])) {
            if (class_exists($class)) {
                $o = new $class();
                if (!empty($method) && method_exists($o, $method))
                    self::$_class[$identify] = call_user_func_array(array(&$o, $method));
                else
                    self::$_class[$identify] = $o;
            }else {
                halt("找不到CLASS对象:" . $class);
                exit();
            }
        }
        return self::$_class[$identify];
    }

    static public function autoload($class) {
        switch ($class) {
            case "L":
                require_cache(LIB_DIR . 'Log.lib.php');
                return true;
                break;
            case 'Session':
                require_cache(LIB_DIR . 'Session.lib.php');
                return true;
                break;
            case 'Cookie':
                require_cache(LIB_DIR . 'Cookie.lib.php');
                return true;
                break;
            case 'C':
                require_cache(LIB_DIR . 'Config.lib.php');
                return true;
        }

        if (0 === strpos($class, 'Lib_')) {
            require_cache(LIB_DIR . substr($class, 4) . ".lib.php");
            return true;
        }
        if (strtolower(substr($class, -5)) == 'model') {
            require_cache(MODEL_DIR . substr($class, 4) . ".class.php");
            return true;
        }
    }

    static public function appException($e) {
        halt($e->__toString());
    }

    static public function fatalError() {
        if ($e = error_get_last()) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            self::appError($e['type'], $e['message'], $e['file'], $e['line']);
        }
    }

}

?>

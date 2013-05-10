<?php

/**
 * 入口文件
 */
header("Content-type:text/html;charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
//var_dump($_SERVER);
//header("X-Powered-By:Php+Mysql");
define("DS", DIRECTORY_SEPARATOR);
define("WEB_ROOT", dirname(__FILE__) . DS);
define("SYSTEM_DIR", WEB_ROOT . DS . "sys" . DS);
define('TAG_DIR', WEB_ROOT . 'tag' . DS);
define('CONFIG_DIR', WEB_ROOT . DS . 'config' . DS);

$config = require CONFIG_DIR . 'config.php';
if ($config['setting']['debug']) {
    error_reporting(E_ALL ^ E_WARNING);
} else {
    error_reporting(0);
}
require_once SYSTEM_DIR . 'common.php';
timer('App_Start');
dump(timer('App_Start', 'M'));
require_cache(SYSTEM_DIR . 'App.php');
Tag('start_app');
App::run($config);
//dump(C('tag'));
//var_dump($GLOBALS);
Tag('end_app');

dump(timer('App_Start'));
Lib_Log::save();
//dump($GLOBALS);
unset($GLOBALS);

exit();
?>
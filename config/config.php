<?php

/**
 * 总配置文件
 */
if (defined(WEB_ROOT))
    exit();
return array(
    'autoload' => array('db', 'config'), //自动加载
    'fliter' => 'filter_vars', //过滤用方法
    'template' => array(
        'isHtaccess'=>false, //是否有伪静态
        'IsParse' => true,  //是否取消编译
        'TemplateType' => 'self', //self 模板类型 PHP 0R SELF
        'left_def' => '\{@',  //模板引擎左标签
        'right_def' => '\}',//模板引擎右标签
    ),
    'session'=>array(
        'COOKIE_DOMAIN'=>'farmcode.com', //
        'SESSION_LOCAL_NAME'=>'YHfarmcode',
        'SESSION_NAME'=>'farmcode',
        'SESSION_PATH'=>WEB_ROOT.'tmp/session/',
        'SESSION_EXPIRE'=>30*60,
    ),
    'cookie'=>array(
        'COOKIE_PREFIX'=>'my_',//cookie前缀
        'COOKIE_EXPIRE'=>30*60,//存活时间
        'COOKIE_PATH'=>WEB_ROOT.'tmp/cookie/',//存放路径
        'COOKIE_DOMAIN'=>'farmcode.com'//域名
    ),
    'setting' => array(
        'errorMessage' => '未找到该页!',//生产环境 404页面显示的字
        'errorPage' => WEB_ROOT . 'html' . DS . 'error' . DS . '404.html',//404页面模板
        'debug' => TRUE, //调试模式，false将关闭dump方法 和 异常的输出
        'url' => '',//网站域名 目前没有用到
        'defautController' => 'home',//默认的首页控制器
        'defautAction' => 'Index',//默认的首页Action
        'NotfoundController' => 'error',//错误页调用的控制器
        'NotfoundAction' => 'index',//错误页调用的Action
    ),
    'tree' => array(
        'HTML_DIR' => WEB_ROOT . 'html' . DS,
        'TEMP_DIR' => WEB_ROOT . 'temp' . DS,
        'CACHE_DIR' => WEB_ROOT . 'cache' . DS,
        'CORE_DIR' => WEB_ROOT . 'core' . DS . 'controller' . DS,
        'MODEL_DIR' => WEB_ROOT . 'core' . DS . 'model' . DS,
        'LIB_DIR' => WEB_ROOT . 'lib' . DS,
        'DATA_DIR' => WEB_ROOT . 'database' . DS,
        'LOG_DIR' => WEB_ROOT . 'log' . DS
    ),
    'cache' => array(
        'lift_time' => 60 * 60 * 12,
    ),
    'sql' => array(
        'cfg_type' => 'mysql',
        'cfg_dbhost' => 'localhost',
        'cfg_dbuser' => 'root',
        'cfg_dbpwd' => '',
        'cfg_dbname' => 'yh',
        'cfg_dbprefix' => 'yh_'
    ),
    'router' => array()
);
?>

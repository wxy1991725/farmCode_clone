<?php

/**
 * 模板正则文件，弃用
 */
if (!defined('WEB_ROOT'))
    exit();

return array(
    '__PUBLIC__' => '/public',
    '__CSS__' => '/public/css',
    '__JS__' => '/public/js',
    '__FILE__' => '/upfile',
    '__CLASS__' => $GLOBALS['core']['controller'],
    '__ACTION__' => $GLOBALS['core']['action'],
    '__SELF__' => $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]
);
?>
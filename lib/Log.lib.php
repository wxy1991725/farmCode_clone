<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Log
 *
 * @author WXY
 */
class Lib_Log {

    static $LogMessage = array();

    static function getError($errorNo) {
        switch ($errorNo) {
            case 1: return 'E_ERROR';
            case 4: return 'E_PARSE';
            case 16:return 'E_CORE_ERROR';
            case 64:return 'E_COMPILE_ERROR';
            case 256:return 'E_USER_ERROR';
            case 2048:return 'E_STRICT';
            case 512:return 'E_USER_WARNING';
            case 1024:return 'E_USER_NOTICE';
            case 8:return 'E_NOTICE';
            default: return 'UNKNOW';
        }
    }
    static function isEmpty(){
        if(empty(self::$LogMessage)){
            
        }
    }

    //put your code here
    static function record($string, $level = null) {
        $log = '  ' . date('[ c ]') . '   ';
        if ($level) {
            $error = " " . self::getError($level) . ":   ";
            $log .=$error;
        }
        $log .= $string;
        self::$LogMessage[] = $log;
    }

    static function save() {
        if (self::$LogMessage) {
            $logfile = LOG_DIR . date('Ymd') . '.log';
            $content = "\r\n#######################\r\n###";
            $content.=date('Ymd H:i:s') . "###";
            $content.="\r\n#######################\r\n";
            $content .= implode("\r\n", self::$LogMessage);
            $content .= "\r\n";
            file_put_contents($logfile, $content, FILE_APPEND);
        }
    }

}

?>

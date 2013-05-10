<?php

/*
 * 模型文件
 */

class Model extends Lib_Db {

    public function __construct($tablename) {
        parent::__construct($tablename);
        $this->tableCache($tablename);
    }

    function tableCache($tablename) {
        $tablename = $this->alias($tablename, true);
        if (!empty($this->tablekey)) {
            if (!file_exists(DATA_DIR . __CLASS__ . 'php')) {
                $header = '<?php if (!defined("SYSTEM_DIR"))
                    exit("Request Error!");  
                    return ';
                $footer = ' ?>';
                file_put_contents(DATA_DIR . $tablename . 'ORM.php', $header . var_export($this->tablevalue, true) . $footer);
            }
        }
    }

    function validValue($value, $tablename = NULL) {
       
    }

    public function __call($name, $arguments) {
        if (0 === strpos($name, "getby")) {
            $getby = substr($name, 5);
            $result = $this->select()->where($getby . "='" . $arguments[0] . "'")->run();
            return $result;
        }
    }

}

?>

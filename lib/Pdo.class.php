<?php

/**
 * PDO 个人认为 PDO 不需要修改
 */
class Lib_PDO extends PDO {

    private $conn = null;
    private $dsn = null;
    private $showerrors = null;
    #private $qurey_string = null;
    #private $error = array();
    private $Log = null;
    private $cfg_dbhost = null;
    private $cfg_dbuser = null;
    private $cfg_dbpwd = null;
    private $cfg_dbname = null;
    private $cfg_dbprefix = null;
    #private $cfg_port = null;
    #private $tablename = null;
    #private $result;
    #private $tablevalue = null;
    #private $tablekey = null;
    #private $numRows = null;
    # private $options = null;
    private $cfg_type = null;

    function init() {
        $this->cfg_type = $GLOBALS['config']['sql']['cfg_type'];
        $this->cfg_dbhost = $GLOBALS['config']['sql']['cfg_dbhost'];
        $this->cfg_dbuser = $GLOBALS['config']['sql']['cfg_dbuser'];
        $this->cfg_dbpwd = $GLOBALS['config']['sql']['cfg_dbpwd'];
        $this->cfg_dbname = $GLOBALS['config']['sql']['cfg_dbname'];
        $this->cfg_dbprefix = $GLOBALS['config']['sql']['cfg_dbprefix'];
        $this->showerrors = $GLOBALS['config']['setting']['debug'];
        $this->dsn = $this->cfg_type . ":host=" . $this->cfg_dbhost . ";dbname=" . $this->cfg_dbname;
        echo $this->dsn;
    }

    public static function PDOException(Exception $e) {
        if ($this->showerrors) {
            throw new Exception($e->getMessage());
        } else {
            die(' Mysql Error!');
        }
    }

    public function __construct() {
        set_exception_handler(array(__CLASS__, 'PDOException'));
        $this->init();
        $this->conn = parent::__construct($this->dsn, $this->cfg_dbuser, $this->cfg_dbpwd, array(PDO::ATTR_PERSISTENT => 1));
        restore_exception_handler();
        return $this->conn;
    }

    public function __destruct() {
        $this->conn = null;
    }

}

?>

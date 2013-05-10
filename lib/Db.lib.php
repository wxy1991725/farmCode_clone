<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */if (!defined('SYSTEM_DIR'))
    exit("Request Error!");

/**
 * Description of PDO
 *
 * @author WXY
 */
class Lib_Db {

    //put your code here
    private $conn = null;
    private $qurey_string = null;
    private $error = array();
    private $Log = null;
    private $cfg_dbhost = null;
    private $cfg_dbuser = null;
    private $cfg_dbpwd = null;
    private $cfg_dbname = null;
    private $cfg_dbprefix = null;
    #private $cfg_port = null;
    protected $tablename = null;
    private $result;
    protected $tablevalue = null;
    protected $tablekey = null;
    private $numRows = null;
    private $options = null;
    protected $alias = null;
    private $Sign;

    /**
     * 获得实例
     * @return \Lib_Db
     */
    public function getIntance() {
        if ($this->Sign) {
            return $this;
        } else {
            return new Lib_Db($GLOBALS['core']['controller']);
        }
    }

    /**
     *  选择数据库
     * @param type $dbname 
     * @return boolean
     */
    private function SeleteDb($dbname) {
        $result = mysql_select_db($dbname, $this->conn);
        if (!empty($result)) {
            return true;
        } else {
            $this->error[] = $this->halt();
        }
    }

    /**
     * 
     * @param type $tablename 通过此变量载入对应的数据库表
     */
    public function __construct($tablename) {
        $this->cfg_dbhost = $GLOBALS['config']['sql']['cfg_dbhost'];
        $this->cfg_dbuser = $GLOBALS['config']['sql']['cfg_dbuser'];
        $this->cfg_dbpwd = $GLOBALS['config']['sql']['cfg_dbpwd'];
        $this->cfg_dbname = $GLOBALS['config']['sql']['cfg_dbname'];
        $this->cfg_dbprefix = $GLOBALS['config']['sql']['cfg_dbprefix'];
        $this->alias = include CONFIG_DIR . "table.php";
        $tablename = $this->alias($tablename);
        $this->tablename = $this->cfg_dbprefix . $tablename;
        $this->conect();
    }

    /**
     * 别名的相互转化
     * @param type $key 
     * @param type $flip 返向查找别名
     * @return type
     */
    function alias($key, $flip = false) {
        $array = $this->alias;
        if ($flip) {
            $array = array_flip($this->alias);
        }
        if (isset($array[$key])) {
            return $array[$key];
        } else {
            return $key;
        }
    }

    /**
     *  通过主键获得详细内容
     * @param type $key 主键序列号
     * @return type 结果集
     */
    public function find($key) {
        if (is_numeric($key)) {
            return $this->select()->where($this->tablekey . "=" . $key)->run();
        } else {
            if (strpos($key, ',')) {
                return $this->select()->where($this->tablekey . " in " . $key)->run();
            } elseif (substr_count($key, '-') == 1) {
                return $this->select()->where($this->tablekey . " BETWEEN  " . str_replace($key, '-', " AND "))->run();
            }
        }
    }

    /**
     * 连接数据
     * @return boolean 是否成功
     */
    public function conect() {
        $reslut = $this->Connection();
        if ($reslut) {
            $this->bulidTable($this->tablename);
            $this->Sign = true;
            return true;
        } else {
            return false;
        }
    }

    /**
     * 私有连接方法
     * @return boolean 
     */
    private function Connection() {
            $time = microtime(TRUE);
            $this->conn = mysql_connect($this->cfg_dbhost, $this->cfg_dbuser, $this->cfg_dbpwd) or halt('Sql Error');
            dump(microtime(true) - $time);
            $this->SeleteDb($this->cfg_dbname) or halt('数据库未连接!');
        return true;
    }

    /**
     * 建立数据库表信息
     */
    function bulidTable() {
        $tablename = $this->tablename;
        $tablename = substr($tablename, 3);
        $tablename = $this->alias($tablename, true);
        $filename = DATA_DIR . $tablename . 'ORM.php';
        dump($filename);
        if (file_exists($filename)) {
            $this->tablevalue = require_once "$filename";
        } else {
            $this->tablevalue = $this->query(" DESCRIBE " . $this->tablename);
        }
        if ($this->tablevalue) {
            foreach ($this->tablevalue as $value) {
                if ($value['Key'] && $value['Key'] == "PRI") {
                    $this->tablekey = $value['Field'];
                }
            }
        } else {
            trigger_error($this->tablename . " IS NOT FOUND");
        }
    }

    /**
     * mysql_free_result
     * @param type $result 句柄
     */
    function free($result) {
        if (!empty($result)) {
            mysql_free_result($result);
        }
    }

    /**
     *  数据集，用于add方法
     * @param array $data  要加入数据库的键值对
     * @return \Lib_Db  返回实例，以方便连贯操作
     */
    function data($data = array()) {
        $key = array_keys($data);
        $value = array_values($data);
        $this->options['data'] = "(" . implode(",", $key) . ")";
        $this->options['data'].=" VALUES(" . implode(",", $value) . ")";
        return $this;
    }

    /**
     * 输出错误参数
     * @param Exception $e 异常
     * @return string|boolean 针对是否为调试模式，返回信息
     */
    function halt(Exception $e = NULL) {
        if (DEBUG_MODE) {
            $error = "";
            if (!empty($e)) {
                $error.= "ConnError Date:" . date("Ymd h:i:s");
                $error.=" file:" . $e->getFile();
                $error.=" line:" . $e->getLine();
                $error.= " msg:" . $e->getMessage();
            } else {
                $error .=" Error Date:" . date("Ymd h:i:s") . "  SQLERROR No." . mysql_errno($this->conn);
                $error .="   SQLERROR " . mysql_error($this->conn);
            }
            return $error;
        } else {
            return FALSE;
        }
    }

    public function __destruct() {
        if ($this->conn) {
            mysql_close();
        }
        $this->Sign = null;
    }

    /**
     * 获取全部结果集
     * @param type $string query返回的句柄
     * @return boolean|null 
     */
    public function getAll($string) {
        if (!$this->conn) {
            $this->error[] = $this->halt();
            return false;
        }
        //返回数据集
        if ($this->numRows > 0) {
            while ($row = mysql_fetch_assoc($string)) {
                $result[] = $row;
            }
            mysql_data_seek($string, 0);
        }
        if (!empty($result)) {
            return $result;
        } else {
            return null;
        }
    }

    /**
     * 输出运行的语句
     * @param type $key
     * @return type
     */
    function showlog($key = null) {
        if (is_numeric($key)) {
            return $this->Log[$key];
        } else {
            return $this->Log;
        }
    }

    function showError() {
        return $this->error;
    }

    function query($string = null) {
        if ($this->result) {
            $this->result = null;
        }
        if (is_string($string)) {
            CheckSql($string);
            $result = mysql_query($string, $this->conn);
        } else {
            $result = mysql_query($this->qurey_string, $this->conn);
        }
        if ($result) {
            $this->numRows = mysql_num_rows($result);
            $this->result = $this->getAll($result);
        } else {
            $this->error[] = $this->halt();
        }
        $callback = $this->result ? $this->result : $this->error;
        $this->free($result);
        $this->Log[] = $string;
        return $callback;
    }

    function count($count = '*') {
        $this->options['count'] = $count;
        $this->options['run'] = "COUNT";
        return $this;
    }

    function where($where) {
        $whereString = "";
        if (is_array($where)) {
            $key = array_keys($where);
            $value = array_values($where);
            for ($i = 0; $i++; $i < count($where)) {
                $string = $key[$i] . "=" . $value[$i];
                if ($i < count($where)) {
                    $string.=',';
                }
                $whereString.= $string;
            }
        } else {
            $whereString.=" WHERE " . $where;
        }
        $this->options['where'] = $whereString;
        return $this;
    }

    function order($order) {
        $this->options['order'] .=" ORDER BY " . $order;
        return $this;
    }

    function limit($offset, $length = null) {
        $this->options['limit'] = " LIMIT " . is_null($length) ? $offset : $offset . ',' . $length;
        return $this;
    }

    /**
     *  执行语句  
     * @param type $string
     * @return type
     */
    function execute($string) {
        if ($this->result) {
            $this->result = null;
        }
        if (is_string($string)) {
            CheckSql($string,'execute');
            $result = mysql_query($string, $this->conn);
        } else {
            $result = mysql_query($this->qurey_string, $this->conn);
        }
        if ($result) {
            $this->numRows = mysql_num_rows($result);
        } else {
            $this->error[] = $this->halt();
        }
        $callback = $this->numRows ? $this->numRows : $this->error;
        $this->free($result);
        $this->Log[] = $string . " 影响行数:" . $this->numRows;
        return $callback;
    }

    function run() {
        switch ($this->options['run']) {
            case 'INSERT':
                $this->qurey_string = " INSERT INTO ";
                $this->qurey_string.=$this->options['from'];
                $this->qurey_string.=$this->options['data'];
                $this->options = null; //防止下次混淆
                return $this->execute($this->qurey_string);
                break;
            case 'SELECT':
                $this->qurey_string = " SELECT ";
                $this->qurey_string.=!empty($this->options['select']) ? $this->options['select'] : "* ";
                $this->qurey_string.=" FROM ";
                $this->qurey_string.=!empty($this->options['from']) ? $this->options['from'] : $this->tablename;
                $this->qurey_string.=!empty($this->options['where']) ? $this->options['where'] : "";
                $this->qurey_string.=!empty($this->options['group']) ? $this->options['group'] : "";
                $this->qurey_string.=!empty($this->options['order']) ? $this->options['oder'] : " ORDER BY " . $this->tablekey;
                $this->qurey_string.=!empty($this->options['limit']) ? $this->options['limit'] : "";
                $this->options = null; //防止下次混淆
                return $this->query($this->qurey_string);
                break;

            case 'COUNT':
                $this->qurey_string = " SELECT ";
                $this->qurey_string.= " COUNT(" . $this->options['count'] . ") sum";
                $this->qurey_string.=" FROM ";
                $this->qurey_string.=!empty($this->options['from']) ? $this->options['from'] : $this->tablename;
                $this->qurey_string.=!empty($this->options['where']) ? $this->options['where'] : "";
                $this->options = null; //防止下次混淆
                $result = $this->query($this->qurey_string);
                return $result['sum'];
                break;
            case 'DELETE':
                $this->qurey_string = " DELETE ";
                $this->qurey_string.=" FROM ";
                $this->qurey_string.=!empty($this->options['from']) ? $this->options['from'] : $this->tablename;
                $this->qurey_string.=!empty($this->options['where']) ? $this->options['where'] : "";
                $this->options = null; //防止下次混淆
                return $this->execute($this->qurey_string);
                break;
            case 'UPDATE':
                $this->qurey_string = ' UPDATE ';
                $this->qurey_string.=!empty($this->options['from']) ? $this->options['from'] : $this->tablename;
                $this->qurey_string.=" SET ";
                $this->qurey_string.=$this->options['update'];
                $this->qurey_string.=!empty($this->options['where']) ? $this->options['where'] : "";
                $this->options = null; //防止下次混淆
                return $this->execute($this->qurey_string);
                break;
        }

        //这种方法比上面的方法效率要差
        /* if (!empty($this->options['select'])) {
          $this->qurey_string = " SELECT ";
          $this->qurey_string.=!empty($this->options['select']) ? $this->options['select'] : "* ";
          $this->qurey_string.=" FROM ";
          $this->qurey_string.=!empty($this->options['from']) ? $this->options['from'] : $this->tablename;
          $this->qurey_string.=!empty($this->options['where']) ? $this->options['where'] : "";
          $this->qurey_string.=!empty($this->options['order']) ? $this->options['oder'] : " ORDER BY " . $this->tablekey;
          $this->qurey_string.=!empty($this->options['limit']) ? $this->options['limit'] : "";
          } elseif ($this->options['insert']) {
          $this->qurey_string = " INSERT INTO ";
          $this->qurey_string.=$this->options['from'];
          $this->qurey_string.=$this->options['data'];
          } */
    }

    function del() {
        $this->options['run'] = 'DELETE';
        return $this;
    }

    function group($column_name) {
        $this->options['group'] = " GROUP BY " . $column_name;
        return $this;
    }

    function table($tablename = "") {
        $this->options['from'] = $tablename;
        return $this;
    }

    function update(array $data) {
        $string = "";
        foreach ($data as $key => $value) {
            $string.=$key . "='" . $value . "',";
        }
        return rtrim($string, ',');
    }

    function add() {
        $this->options['run'] = "INSTER";
        return $this;
    }

    function select($select = "*") {
        $selectString = "";
        if (is_array($select)) {
            foreach ($select as $value) {
                $selectString.=$value . ",";
            }
        } else {
            $selectSrting = func_get_args();
            if ($selectSrting >= 1) {
                foreach ($selectSrting as $value) {
                    $selectString.=$value . ",";
                }
            }
        }
        if ($selectString) {
            $selectString = rtrim($selectString, ",");
        } else {
            $selectString = "*";
        }
        $this->options['select'] = $selectString;
        $this->options['run'] = 'SELECT';
        return $this;
    }

}

?>

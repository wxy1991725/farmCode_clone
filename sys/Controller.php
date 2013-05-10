<?php

if (!defined('SYSTEM_DIR'))
    exit("Request Error!");
/*
 * 控制器文件
 * 
 */

class controller {

    public $action = null;
    public $controller = null;
    public $tplfile = null;
    var $tVar = null;
    public $tVar_lock = array();

    public function __construct() {
        $this->controller = $GLOBALS['core']['controller'];
        $this->action = 'Index';
        if (method_exists(__CLASS__, $GLOBALS['core']['action'])) {
            $this->action = $GLOBALS['core']['action'];
        }
        $this->tplfile = $this->controller . $this->action;
        dump($this->tplfile);
        // Session::start();
        if (function_exists('_init')) {
            $this->_init();
        }
    }

    public function __call($method, $args) {
        switch (strtolower($method)) {
            // 判断提交方式
            case 'ispost' :
            case 'isget' :
            case 'ishead' :
            case 'isdelete' :
            case 'isput' :
                return strtolower($_SERVER['REQUEST_METHOD']) == strtolower(substr($method, 2));
            // 获取变量 支持过滤和默认值 调用方式 $this->_post($key,$filter,$default);
            case '_get' : $input = & $_GET;
                break;
            case '_post' : $input = & $_POST;
                break;
            case '_put' : parse_str(file_get_contents('php://input'), $input);
                break;

            case '_request' : $input = & $_REQUEST;
                break;
            case '_session' : $input = & $_SESSION;
                break;
            case '_cookie' : $input = & $_COOKIE;
                break;
            case '_server' : $input = & $_SERVER;
                break;
            case '_globals' : $input = & $GLOBALS;
                break;
            default:
                return null;
        }
        if (!isset($args[0])) { // 获取全局变量
            $data = $input; // 由VAR_FILTERS配置进行过滤
        } elseif (isset($input[$args[0]])) { // 取值操作
            $data = $input[$args[0]];
            $filters = isset($args[1]) ? $args[1] : $GLOBALS['config']['fliter'];
            if ($filters) {// 2012/3/23 增加多方法过滤支持
                $filters = explode(',', $filters);
                foreach ($filters as $filter) {
                    if (function_exists($filter)) {
                        $data = is_array($data) ? array_map($filter, $data) : $filter($data); // 参数过滤
                    }
                }
            }
        } else { // 变量默认值
            $data = isset($args[2]) ? $args[2] : NULL;
        }
        return $data;
    }

    public function assgin($k, $v, $lock = false) {
        if (is_array($k)) {
            $this->tVar = array_merge($this->tVar, $k);
        } else {
            if ($lock) {
                $this->tVar_lock[] = $k;
                $this->tVar[$k] = $v;
            } elseif (in_array($k, $this->tVar_lock)) {
                return;
            } else {
                $this->tVar[$k] = $v;
            }
            //$this->tVar[$k] = $v;
        }
    }

    public function isCache() {
        $cachefilename = CACHE_DIR . md5(strtolower($this->tplfile)) . '.html';
        if (file_exists($cachefilename)) {
            $now = time();
            $cachetime = filemtime($cachefilename);
            if (($now - $cachetime) > $GLOBALS['config']['cache']['lift_time']) {
                return false;
            }
            return true;
        }
    }

    public function display($filename, $nocache = true) {
        $filename = $this->parseName($filename);
        if (!$nocache & $this->isCache()) {
            $this->render($filename, $nocache);
            return;
        }
        if ($this->checkComplie($filename)) {
            $this->fecth($filename);
        }
        if (!$nocache) {
            $this->showCache($filename);
        }
        $this->render($filename, $nocache);
    }

    public function render($filename, $nocache) {
        if ($nocache) {
            if (isset($this->tVar)) {
                extract($this->tVar);
            }
            $filepath = TEMP_DIR . md5($this->tplfile) . '.php';
        } else {
            $filepath = CACHE_DIR . md5($this->tplfile) . '.html';
        }
        include $filepath;
    }

    public function checkComplie($filename) {
        if (!file_exists(TEMP_DIR . md5($this->tplfile)) . '.php') {
            return true;
        } elseif (filemtime(HTML_DIR . $filename) > filemtime(TEMP_DIR . md5($this->tplfile) . '.php')) {
            return true;
        } else {
            return FALSE;
        }
    }

    public function parseName($filename = NULL) {
        $filepathname = $filename;
        if (empty($filename)) {
            $filepathname = $GLOBALS['core']['controller'] . DS . $GLOBALS['core']['action'];
        } elseif (0 === strpos($filename, '~')) {
            $filepathname = $GLOBALS['core']['controller'] . DS . trim($filename, "~");
        }
        return strtolower($filepathname);
    }

    function showCache($filename) {
        $templatefile = CACHE_DIR . md5($this->tplfile) . '.html';
        //压缩输出,不过貌似没变化
        if (extension_loaded('zlib')) {
            ini_set('zlib.output_compression', 'On');
            ini_set('zlib.output_compression_level', '3');
        }
        ob_start();
        if (isset($this->tVar)) {
            extract($this->tVar);
        }
        include TEMP_DIR . md5($this->tplfile) . '.php';
        $content = ob_get_clean();
        file_put_contents($templatefile, $content);
    }

    public function fecth($filename) {
        if (!is_file(HTML_DIR . $filename . '.html')) {
            trigger_error("未发现文件:" . $filename);
        }
        $templefile = HTML_DIR . $filename . '.html';
        $complie = TEMP_DIR . md5($this->tplfile) . '.php';
        if (!Lib_Config::GetConfig('Template.IsParse')) {
            Lib_Log::record(Lib_Config::GetConfig('Template.IsParse'));
            return;
        }
        switch (strtolower(Lib_Config::GetConfig('Template.TemplateType'))) {
            /*
              $content = file_get_contents();
              $content = $this->parseInclude($content);
              $list = require CONFIG_DIR . 'template.php';
              $content = preg_replace(array_keys($list), array_values($list), $content);
              $content = Lib_View::parse($content); */

            case 'php':
                $content = file_get_contents($templefile);
                $header = "<?php if (!defined('WEB_ROOT'))exit(); ?>\n\r";

                //$templatefile = TEMP_DIR . md5($this->controller . $this->action) . '.php';
                file_put_contents($complie, $header . $content);
                break;
            case 'self':
                $template = App::instance('Lib_Template');
                $template->parse($templefile, $this->tVar, $complie);
                break;
            default :
                halt('未选择模板解析类型!');
        }
    }

}

?>

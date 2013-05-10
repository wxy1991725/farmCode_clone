<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Template
 *
 * @author WXY
 */
class Lib_Template extends Lib_Taglib {

    private $left_def;
    private $right_def;
    private $tVar;
    private $complie;
    private $tagobj;

    public function __construct() {
        $this->left_def = $GLOBALS['config']['template']['left_def'];
        $this->right_def = $GLOBALS['config']['template']['right_def'];
        $this->tagobj = new Lib_Taglib();
    }

    protected $taglib = array(
        'include' => array('value' => array('file', 'empty'), 'block' => 0),
        'foreach' => array('value' => array('name', 'length', 'empty', 'from', 'item', 'key', 'mod'), 'block' => 1),
        'if' => array('value' => 1, 'block' => 1),
        'else' => array('value' => '', 'block' => 0),
        'elseif' => array('value' => 1, 'block' => 0),
        'load' => array('value' => 'type,src', 'block' => 0)
    );

//put your code here
    public function parse($templefile, $tVar, $complie) {
        $this->tVar = $tVar;
        $this->complie = $complie;

        $content = file_get_contents($templefile);
        $begin = $this->left_def;
        $end = $this->right_def;
        ob_start();
        ob_implicit_flush(0);

        foreach ($this->taglib as $name => $val) {
            $n1 = empty($val['value']) ? '(\s*?)' : '\s([^' . $end . ']*)';
            if (!$val['block']) {
                $patterns = '/' . $begin . $name . $n1 . '\/(\s*?)' . $end . '/eis';
                $replacement = "\$this->parseXmlTag('$name','$1')";
                $content = preg_replace($patterns, $replacement, $content);
            } else {
                $patterns = '/' . $begin . $name . $n1 . $end . '/eis';
                $replacement = "\$this->parseXmlTag('$name','$1')";
                $content = preg_replace($patterns, $replacement, $content);
                //块状标签要匹配首尾两次标签
                $patterns = '/' . $begin . "\/" . $name . '\s*?' . $end . '/eis';
                $replacement = "\$this->parseXmlTag('$name','',true)";
                $content = preg_replace($patterns, $replacement, $content);
            }
        }
        $content = preg_replace('/(' . $begin . ')([^\d\s' . $begin . $end . '].+?)(' . $end . ')/eis', "\$this->parseTag('$2')", $content);
        $TmpConst = require_cache(CONFIG_DIR . 'template.php');
        $search = array_keys($TmpConst);
        $replace = array_values($TmpConst);
        $content = str_replace($search, $replace, $content);
        
        $header = "<?php if (!defined('WEB_ROOT')) exit();  \n\r";
        $header.=" /*  Template form :" . $GLOBALS['core']['controller'] . "/";
        $header.=$GLOBALS['core']['action'] . "\n\r";
        $header.="*/ ?>\n\r";
        $dir = dirname($complie);
        if (!is_dir($dir))
            mkdir($dir, 0755, true);
        if (false === file_put_contents($complie, trim($header . $content)))
            throw new Exception('编译失败!');
        include $complie;
        ob_end_clean();
        //include_once $complie;
        return;
    }

    function parseTag($tagStr) {
        $tagStr = stripslashes($tagStr);

        $flag = substr($tagStr, 0, 1);
        $flag2 = substr($tagStr, 1, 1);
        $name = substr($tagStr, 1);
        if ('$' == $flag && '.' != $flag2 && '(' != $flag2) { //解析模板变量 格式 {$varName}
            return $this->parseVar($name);
        } elseif ('-' == $flag || '+' == $flag) { // 输出计算
            return '<?php echo ' . $flag . $name . ';?>';
        } elseif (':' == $flag) { // 输出某个函数的结果
            return '<?php echo ' . $name . ';?>';
        } elseif ('~' == $flag) { // 执行某个函数
            return '<?php ' . $name . ';?>';
        } elseif (substr($tagStr, 0, 2) == '//' || (substr($tagStr, 0, 2) == '/*' && substr($tagStr, -2) == '*/')) {
            //注释标签
            return '';
        }
        return stripslashes($this->left_def . $tagStr . $this->right_def);
    }

    function parseVar($varStr) {
        $varStr = trim($varStr);
        static $_varParseList = array();
        //如果已经解析过该变量字串，则直接返回变量值
        if (isset($_varParseList[$varStr]))
            return $_varParseList[$varStr];
        $parseStr = '';
        if (!empty($varStr)) {
            $varArray = explode('|', $varStr);
            //取得变量名称
            $var = array_shift($varArray);
            if ('Self.' == substr($var, 0, 5)) {
                // 所有以Self.打头的以特殊变量对待 无需模板赋值就可以输出
                $name = $this->parseSelfVar($var);
            } elseif (false !== strpos($var, '.')) {
                //支持 {$var.property}
                $vars = explode('.', $var);
                $var = array_shift($vars);
                $name = '$' . $var;
                foreach ($vars as $key => $val)
                    $name .= '["' . $val . '"]';
            } elseif (false !== strpos($var, '[')) {
                //支持 {$var['key']} 方式输出数组
                $name = "$" . $var;
                preg_match('/(.+?)\[(.+?)\]/is', $var, $match);
                $var = $match[1];
            } elseif (false !== strpos($var, ':') && false === strpos($var, '::') && false === strpos($var, '?')) {
                //支持 {$var:property} 方式输出对象的属性
                $vars = explode(':', $var);
                $var = str_replace(':', '->', $var);
                $name = "$" . $var;
                $var = $vars[0];
            } else {
                $name = "$$var";
            }
            //对变量使用函数
            if (count($varArray) > 0)
                $name = $this->parseVarFunction($name, $varArray);
            $parseStr = '<?php echo (' . $name . '); ?>';
        }
        $_varParseList[$varStr] = $parseStr;
        return $parseStr;
    }

    /**
     * 对模板变量使用函数
     * 格式 {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param string $name 变量名
     * @param array $varArray  函数列表
     * @return string
     */
    public function parseVarFunction($name, $varArray) {
        //对变量使用函数
        $length = count($varArray);
        //取得模板禁止使用函数列表
        // $template_deny_funs = explode(',',);
        for ($i = 0; $i < $length; $i++) {
            $args = explode('=', $varArray[$i], 2);
            //模板函数过滤
            $fun = strtolower(trim($args[0]));
            switch ($fun) {
                case 'default':  // 特殊模板函数
                    $name = 'isset(' . $name . ')?(' . $name . '):' . $args[1];
                    break;
                default:  // 通用模板函数
//                if(!in_array($fun,$template_deny_funs)){
                    if (isset($args[1])) {
                        if (strstr($args[1], '###')) {
                            $args[1] = str_replace('###', $name, $args[1]);
                            $name = "$fun($args[1])";
                        } else {
                            $name = "$fun($name,$args[1])";
                        }
                    } else if (!empty($args[0])) {
                        $name = "$fun($name)";
                    }
//                }
            }
        }
        return $name;
    }

    function parseSelfVar($varStr) {
        $vars = explode('.', $varStr);
        $vars[1] = strtoupper(trim($vars[1]));
        $parseStr = '';
        if (count($vars) >= 3) {
            $vars[2] = trim($vars[2]);
            switch ($vars[1]) {
                case 'SERVER':
                    $parseStr = '$_SERVER[\'' . strtoupper($vars[2]) . '\']';
                    break;
                case 'GET':
                    $parseStr = '$_GET[\'' . $vars[2] . '\']';
                    break;
                case 'POST':
                    $parseStr = '$_POST[\'' . $vars[2] . '\']';
                    break;
                case 'COOKIE':
                    if (isset($vars[3])) {
                        $parseStr = '$_COOKIE[\'' . $vars[2] . '\'][\'' . $vars[3] . '\']';
                    } else {
                        $parseStr = 'cookie(\'' . $vars[2] . '\')';
                    }
                    break;
                case 'SESSION':
                    if (isset($vars[3])) {
                        $parseStr = '$_SESSION[\'' . $vars[2] . '\'][\'' . $vars[3] . '\']';
                    } else {
                        $parseStr = 'session(\'' . $vars[2] . '\')';
                    }
                    break;
                case 'ENV':
                    $parseStr = '$_ENV[\'' . strtoupper($vars[2]) . '\']';
                    break;
                case 'REQUEST':
                    $parseStr = '$_REQUEST[\'' . $vars[2] . '\']';
                    break;
                case 'CONST':
                    $parseStr = strtoupper($vars[2]);
                    break;
                case 'CONFIG':
                    if (isset($vars[3])) {
                        $vars[2] .= '.' . $vars[3];
                    }
                    $parseStr = 'Lib_Config::getConfig("' . $vars[2] . '")';
                    break;
                default:break;
            }
        } else if (count($vars) == 2) {
            switch ($vars[1]) {
                case 'NOW':
                    $parseStr = "date('Y-m-d g:i a',time())";
                    break;
                case 'LDELIM':
                    $parseStr = $this->left_def;
                    break;
                case 'RDELIM':
                    $parseStr = $this->right_def;
                    break;
                default:
                    if (defined($vars[1]))
                        $parseStr = $vars[1];
            }
        }
        return $parseStr;
    }

    function parseXmlTag($taglib, $tagcon, $block = FALSE) {
        $tagcon = stripslashes($tagcon);
        if (ini_get('magic_quotes_sybase'))
            $tagcon = str_replace('\"', '\'', $tagcon);
        if (!$block) {
            $content = call_user_func(array($this->tagobj, "_" . $taglib), $tagcon);
        } else {
            $content = call_user_func(array($this->tagobj, "_" . $taglib), $tagcon, $block);
        }
        return $content;
    }

}

?>

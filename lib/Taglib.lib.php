<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Lib_Taglib {

    function _include($tagcon) {
        $attrs = $this->parseXml($tagcon);
        $file = $attrs['file'];
        $empty = !empty($attrs['empty']) ? $attrs['empty'] : "";
        if (0 === strpos($file, '~')) {
            $file = substr($file, 1);
            $inportfile = HTML_DIR . CORE_C . $file;
        } else {
            $inportfile = HTML_DIR . $file;
        }
        if (is_file($inportfile)) {
            $content = file_get_contents($inportfile);
            return $content;
        } else {
            return $empty;
        }
    }
  
    public function _load($atter) {
        $flag = md5($atter);
        static $loadCache = null;
        if (isset($loadCache[$flag])) {
            return $loadCache[$flag];
        }
        $attrs = $this->parseXml($atter);
        $src = $attrs['src'];
        if (empty($src)) {
            throw new Exception('load语句缺少必要的属性!');
        }
        $array = explode(',', $src);
        $parseStr = "";
        foreach ($array as $val) {

            $type = pathinfo($val, PATHINFO_EXTENSION); // strtolower(substr(strrchr($val, '.'), 1));
            switch ($type) {
                case 'js':
                    $parseStr .= '<script type="text/javascript" src="' . $val . '"></script>';
                    break;
                case 'css':
                    $parseStr .= '<link rel="stylesheet" type="text/css" href="' . $val . '" />';
                    break;
                case 'php':
                    $parseStr .= '<?php require_cache("' . $val . '"); ?>';
                    break;
            }
        }
        return $parseStr;
    }

    public function _elseif($attr) {
        $flag = md5($attr);
        static $elseifCache = null;
        if (isset($elseifCache[$flag])) {
            return $elseifCache[$flag];
        }
        $condition = $this->parseCondition($attr);
        $parseStr = '<?php elseif(' . $condition . '): ?>';
        $elseifCache[$flag] = $parseStr;
        return $parseStr;
    }

    /**
     * else标签解析
     * @access public
     * @param string $attr 标签属性
     * @return string
     */
    public function _else($attr) {
        $parseStr = '<?php else: ?>';
        return $parseStr;
    }

    function _foreach($tagcon, $flag = false) {
        if (!empty($tagcon)) {
            $flag = md5($tagcon);
            static $foreachCache = null;
            if (isset($foreachCache[$flag])) {
                return $foreachCache[$flag];
            }
            $attrs = $this->parseXml($tagcon);
            $name = $attrs['from'];
            $id = $attrs['item'];
            $empty = isset($attrs['empty']) ? $attrs['empty'] : '';
            $key = !empty($attrs['key']) ? $attrs['key'] : 'i';
            $mod = isset($attrs['mod']) ? $attrs['mod'] : '2';
            if (empty($name) || empty($id)) {
                throw new Exception('foreach语句缺少必要的属性!');
            }
            $parseStr = '<?php ';
            if (0 === strpos($name, ':')) {
                $parseStr .= '$_result=' . substr($name, 1) . ';';
                $name = '$_result';
            } else {
                $name = $this->autoBuildVar($name);
            }
            $parseStr .= 'if(is_array(' . $name . ')): $' . $key . ' = 0;';
            if (isset($attrs['length']) && '' != $attrs['length']) {
                $parseStr .= ' $__LIST__ = array_slice(' . $name . ',' . $attrs['offset'] . ',' . $attrs['length'] . ',true);';
            } elseif (isset($attrs['offset']) && '' != $attrs['offset']) {
                $parseStr .= ' $__LIST__ = array_slice(' . $name . ',' . $attrs['offset'] . ',null,true);';
            } else {
                $parseStr .= ' $__LIST__ = ' . $name . ';';
            }
            $parseStr .= 'if( count($__LIST__)==0 ) : echo "' . $empty . '" ;';
            $parseStr .= 'else: ';
            $parseStr .= 'foreach($__LIST__ as $key=>$' . $id . '): ';
            $parseStr .= '$mod = ($' . $key . ' % ' . $mod . ' );';
            $parseStr .= '++$' . $key . ';?>';
            $foreachCache[$flag] = $parseStr;
            return $parseStr;
        } else {
            $parseStr = '<?php endforeach; endif; else: echo "' . $empty . '" ;endif; ?>';
            return $parseStr;
        }
    }

    public function parseCondition($tagcon) {
        $tagcon = preg_replace('/\$(\w+):(\w+)\s/is', '$\\1->\\2 ', $tagcon);
        $tagcon = preg_replace('/\$(\w+)\.(\w+)\s/is', '$\\1["\\2"] ', $tagcon);
        if (false !== strpos($tagcon, '$Self'))
            $tagcon = preg_replace('/(\$Self.*?)\s/ies', "\$this->parseSelfVar('\\1');", $tagcon);

        return $tagcon;
    }

    public function _if($tagcon, $block = false) {
        switch ($block) {
            case false:
                if (empty($tagcon)) {
                    throw new Exception('if标签中条件不可为空!');
                }
                static $ifCache = null;
                $flag = md5($tagcon);
                if (isset($ifCache[$flag])) {
                    return $ifCache[$flag];
                }
                $tagcon = $this->parseCondition($tagcon);
                $parseStr = '<?php if(' . $tagcon . '): ?>';
                $ifCache[$flag] = $parseStr;
                return $parseStr;
                break;
            case true:
                $parseStr = '<?php endif; ?>';
                return $parseStr;
                break;
        }
    }

    /**
     * 自动识别构建变量
     * @access public
     * @param string $name 变量描述
     * @return string
     */
    public function autoBuildVar($name) {
        static $varCache = null;
        $flag = md5($name);
        if (isset($varCache[$flag])) {
            return $varCache[$flag];
        }
        if ('Self.' == substr($name, 0, 5)) {
            // 特殊变量
            return $this->parseSelfVar($name);
        } elseif (strpos($name, '.')) {
            $vars = explode('.', $name);
            $var = array_shift($vars);
            $name = '$' . $var;
            foreach ($vars as $key => $val) {
                if (0 === strpos($val, '$')) {
                    $name .= '["{' . $val . '}"]';
                } else {
                    $name .= '["' . $val . '"]';
                }
            }
        } elseif (strpos($name, ':')) {
            // 额外的对象方式支持
            $name = '$' . str_replace(':', '->', $name);
        } elseif (!defined($name)) {
            $name = '$' . $name;
        }
        $varCache[$flag] = $name;
        return $name;
    }

    function parseXml($attrs) {
        $xml = '<tpl><tag ' . $attrs . ' /></tpl>';
        $xml = simplexml_load_string($xml);
        if (!$xml)
            throw new Exception("解析出现错误!标签书写不规范!");
        $xml = (array) ($xml->tag->attributes());
        $array = array_change_key_case($xml['@attributes']);
        return $array;
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
}

?>

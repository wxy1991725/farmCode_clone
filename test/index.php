<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
error_reporting(E_ALL);
header('Content-Type:text/html;Charset=utf-8');

class View {

    public $content = null;

    static public function parse($content) {
        self::parseVar($content);
        self::parseForHead($content);
        self::parseForFooter($content);
        return $content;
    }

    /**
     * 匹配最多达3个下标的变量
     * @param type $content
     * @return type
     */
    function parseVar(&$content) {
        preg_match_all('/\{(?P<name>\$[a-zA-Z]\w*)\.*(?P<key1>\w+)*\.*(?P<key2>\w+)*[\|\w]*\}/ius', $content, $matches);
        $replacement = array();
        foreach ($matches['name'] as $key => $value) {
            $replacement[] = self::addKey($value, $matches['key1'][$key], $matches['key2'][$key]);
        }
        $preg = array_values($matches[0]);
        //var_dump($matches);
        unset($matches);
        $content = str_replace($preg, $replacement, $content);
        return $content;
    }

    /**
     * 替换IF标签
     * @param type $content
     */
    function parseIf(&$content) {
        preg_match_all('/\{if\((?P<condition>.*?)\)\}/', $content, $matches);
        $replacement = array();
        //var_dump($matches['condition']);
        foreach ($matches['condition'] as $value) {
//            var_dump($matches['condition']);
            preg_match_all('/(?<value>\$[a-zA-Z]\w*)\.?(?<key1>\w*)?\.?(?<key2>\w*)?/i', $value, $matches1, PREG_SET_ORDER);
            //  var_dump($matches1);
            $replacemenarray = array();
            foreach ($matches1 as $key => $value1) {
                $replacemenarray[] = self::addKey($value1['value'], $value1['key1'], $value1['key2'], false);
                $search[] = $value1[0];
            }
            $replacement[] = str_replace($search, $replacemenarray, $value);
            $replacemenarray = null;
            $search = null;
            //var_dump($string);
        }
        $content = str_replace($matches['condition'], $replacement, $content);
    }

    /**
     * 替换{/FOREACH}
     * @param type $content
     * @return type
     */
    function parseForFooter(&$content) {
        $content = str_replace('{/foreach}', '<?php } ?>', $content);
        return $content;
    }

    /**
     * 替换{FOREACH item=??? from=??? key=???}
     * @param type $content
     * @return type
     */
    function parseForHead(&$content) {
        preg_match_all('/\{foreach\s+(?<item>.*?)\}/', $content, $matches);
        foreach ($matches['item'] as $value) {
            $replaceval[] = self::parseItem($value);
        }
        $replacementArray = array();
        foreach ($replaceval as $key => $repval) {
            $replacement = null;

            if (isset($repval['key'])) {
                $replacement = '<?php ' . $repval['key'] . ' = 0; ?>';
            }
            $replacement.= '<?php foreach(';
            $replacement.= $repval['from'] . ' as $key=> $' . $repval['item'];
            $replacement.=' ?>';
            $replacementArray[] = $replacement;
        }
        var_dump($replacementArray);
        var_dump($matches[0]);
        $content = str_replace($matches[0], $replacementArray, $content);

        return $content;
    }

    /**
     * 附属工具类
     * 
     * 给模板变量添加下标
     */
    private function addKey($var, $key1, $key2, $flag = true) {
        $val = null;
        if ($flag) {
            $val = ' <?php echo ';
        }
        $val .=$var;
        if (!empty($key1)) {
            $val.="[" . $key1 . "]";
            if (!empty($key2)) {
                $val.="[" . $key2 . "]";
            }
        }
        if ($flag) {
            $val.=' ?>';
        }
        return $val;
    }

    /**
     * 附属工具类
     * 检测必要的foreach标志的值，from 和 item
     * @param type $string
     * @return type
     * @throws Exception
     */
    private function parseItem($string) {
        preg_match('/from=(\$[a-zA-Z]\w*?)\b/ius', $string, $matches1);
        if (empty($matches1[1])) {
            throw new Exception('缺少必要属性"from"!');
        }
        $repalce['from'] = $matches1[1];
        preg_match('/item=([a-zA-Z]\w*?)\b/ius', $string, $matches2);
        if (empty($matches2[1])) {
            throw new Exception('缺少必要属性"item"!');
        }
        $repalce['item'] = $matches2[1];
        preg_match('/key=(\$[a-zA-Z]\w*?)\b/ius', $string, $matches3);
        $repalce['key'] = $matches3[1];
        return $repalce;
    }

}

$time = microtime(true);
/**

  $content = file_get_contents('../html/Home/index.html');
  $content = View::parseIf($content);
  var_dump($content);
 * 
 */
$xml = "<note ><lib   $arr  /></note>";
$xml = str_replace('&', '___', $xml);
$info = simplexml_load_string($xml);

var_dump((array) $info);

//file_put_contents('1.html', $content);
echo microtime(true) - $time;
?>

<?php
/**
 * APIAction
 * @author Jay
 * @version 2015-11-24
 *
 */
class APIAction extends Action{
    //初始化
    public function Init(){
        $this->run();
    }

    private function run(){
        $this->init_PrintDoc();
    }
    private function init_PrintDoc(){
        //打印文档参数
        $mbdoc = empty($_GET['mbdoc']) ? NULL : $_GET['mbdoc'];
        $env = 0;
        if(in_array($_SERVER['HTTP_HOST'], array('t-www.imaibo.net'))){
            $env = 1;
        }
        if($mbdoc === 'mbdoc'  && $env){
            $this->printDoc_Strapdown();
        }
        return true;
    }

    /*
    打印文档 Editer.md
    项目地址：https://pandao.github.io/editor.md/examples/index.html
    */
    private function printDoc_Strapdown(){
        $file     = APP_PATH.'/Lib/Action/'.MODULE_NAME.'Action.class.php';
        $contents = file_get_contents($file);
        $contents = explode("\n",$contents);
        $doc      = array();
        $flags = 0;
        foreach ($contents as $key => $value) {
            if($flags == 0 && trim($value) == "/****") {
                $flags = 1;
            } elseif($flags == 1) {
                if(trim($value) != "*/") {
                    $doc[] = $value;
                }else {
                    $flags = 0;
                }
            }
        }
        $doc = implode("\n",$doc);
        $html = <<<EOF
<!DOCTYPE html>
<html> 
<xmp theme="united" style="display:none;">
$doc
</xmp>
<script src="http://strapdownjs.com/v/0.2/strapdown.js"></script>
</html>
EOF;
        die($html);
    }

    public function verifyParam($name, $defaultValue=NULL, $type=NULL, $method='GET'){
        
        switch ($method) {
            case 'GET':
                $value = isset($_GET[$name]) ? $_GET[$name] : $defaultValue;
                break;
            case 'POST':
                $value = isset($_POST[$name]) ? $_POST[$name] : $defaultValue;
                break;
            default:
                outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '不正常的操作方法');
                break;
        }

        if($value === NULL || ($value !== $defaultValue && !isset($value) ))
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), $name.'参数是必须的');

        if($type === 'int') {
            if(!is_numeric($value))
                outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), $name.'参数是必须是整形');
            $value = intval($value);
        }

        if($type === 'string') {
            if(!is_string($value) || empty($value))
                outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), $name.'参数是必须是字符串并不能为空');

        }

        if($type === 'json') {
            $value = json_decode(stripcslashes($value),true);
            if(json_last_error() != JSON_ERROR_NONE) 
                outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), $name.'参数是无效的json格式');
        }
        return $value;
    }
}
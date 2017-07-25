<?php

abstract class SmartyAction extends Action {
    /*
     * 使用前端模拟数据
     * @param $templatePath str 模板路径
     */
    protected function fillWithTestData($templatePath){
        $fis_data = parent::fillWithTestData($templatePath);
        $fis_data['loginUser'] = $this->_loginInfo();
        return $fis_data;
    }
}

?>

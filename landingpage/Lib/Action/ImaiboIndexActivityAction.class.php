<?php

/**
 * Class ImaiboIndexActivityAction
 */
class ImaiboIndexActivityAction extends APIAction
{
    public function _initialize()
    {
        parent::Init();
    }
/****
## 预约信息

###接口描述
    股海仙经活动预约信息

###接口地址
    
   POST /index.php?app=landingpage&mod=ImaiboIndexActivity&act=setOrderInfo
###参数

   mobile : String 必填

###正确返回
    {
        "code": 0,
        "message": "成功",
        "data": {}
    }
*/
    public function setOrderInfo()
    {
        if(empty($this->mid))
            outputAdaptor(C('STATUS_CODE.SESSION_TIMEOUT'),'重新登录');

        $mobile = t($_POST['mobile']);
        if(empty($mobile))
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'缺少参数');
        //预约日志
        $log = [
            'mid' => intval($this->mid),
            'mobile' => $mobile,
            'ip' => get_client_ip(),
            'userAgent' => t($_SERVER['HTTP_USER_AGENT']),
            'referer' => t($_SERVER['HTTP_REFERER']),
            'date' => intval(date('Ymd')),
            'ctime' => time()
        ];
        $res = model('FncLogMg')->addLog($log,'cs_portfolio_stock_sea');
        if($res){
            //发短信
           // service('SmsSend')->portfolioVipActTpl($mobile,'股海仙经7号');
            outputAdaptor(C('STATUS_CODE.SUCCESS'));
        }
        else
            outputAdaptor(C('STATUS_CODE.FAIL'));
    }
}
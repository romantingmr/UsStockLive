<?php

/**
 * Class ImLastVersionAction.class.php
 * @name 最后首页改版
 * @author Bande
 * @version 20170503
 */
class ImLastVersionAction extends SmartyAction
{
    /**
     * @name 首页模板
     */
	public function index()
	{

        //H5切换PC界面@todo20170714
        $pc = intval($_GET['pc']);
        if ((isMobile() || isiOS() || isiPhone())) {
            if (empty($pc)) {
                header('Location:http://' . C('SF_SITE_HOST') . '/im/#/user/login/');
                exit;
            }
        }
        /*if (service('Passport')->isLogged())
            U('home', '', true);*/

		$templatePath = "viewpoint/page/home";
        $landingData = $this->fillWithTestData($templatePath);
        if($landingData===null)return;
        $landingData['navigator']['globalHeader']['index']['active'] = true;
        $this->smarty()->assign($landingData);
        $this->smarty()->display($templatePath.".tpl");
	}

    /**
     * @name 美股播主页面
     */
    public function expertWeb()
    {
        $templatePath = "activity/page/ustocks/list";
        $landingData = $this->fillWithTestData($templatePath);
        if($landingData===null)return;
        $landingData['navigator']['globalHeader']['A50']['active'] = true;
        $this->smarty()->assign($landingData);
        $this->smarty()->display($templatePath.".tpl");
    }

    /**
     * @name 发布美股直播入口
     */
    public function publishWeb()
    {
        $uid = $_GET['uid'] ? intval($_GET['uid']) : 0;
        $res = D('ImLastVersion','landingpage')->isUsStockExpert($uid);
        if($res === false) return false;
        $mid = $this->mid ? intval($this->mid) : 0;
        if($uid == $mid){
            $templatePath = "activity/page/ustocks/zhibo";
        }else{
            //没登录
            $templatePath = "activity/page/ustocks/space";
        }
        $landingData = $this->fillWithTestData($templatePath);
        if($landingData===null)return;
        $liveUid = $_GET['uid'] ? intval($_GET['uid']) : 0;
        if(empty($liveUid)){
            $this->assign('jumpUrl',(U('landingpage/ImLastVersion/epWeb')));
            $this->error('不存在入口页面，正为你跳转正确页面！');
        }
        //访问数据
        if($this->mid) {
            if ($this->mid != $this->uid) {
                $addLog = array(
                    'mid' => intval($this->mid),
                    'uid' => intval($this->uid),
                    'ip' => get_client_ip(),
                    'userAgent' => t($_SERVER['HTTP_USER_AGENT']),
                    'referer' => t($_SERVER['HTTP_REFERER']),
                    'date' => intval(date('Ymd')),
                    'ctime' => time()
                );
                model('FncLogMg')->addLog($addLog, 'cs_us_stock_live');
            }
        }


        $landingData['navigator']['globalHeader']['A50']['active'] = true;
        $this->smarty()->assign($landingData);
        $this->smarty()->display($templatePath.".tpl");
    }

    /**
     * @name 股海仙经活动页面
     */
    public function stockSeaAct()
    {
        //访问日志
        $log = [
            'mid' => intval($this->mid),
            'ip' => get_client_ip(),
            'userAgent' => t($_SERVER['HTTP_USER_AGENT']),
            'referer' => t($_SERVER['HTTP_REFERER']),
            'date' => intval(date('Ymd')),
            'ctime' => time()
        ];
        model('FncLogMg')->addLog($log,'cs_portfolio_stock_sea');
        $templatePath = "activity/page/0707ghxj/index";
        $landingData = $this->fillWithTestData($templatePath);
        if($landingData===null)return;
        $this->smarty()->assign($landingData);
        $this->smarty()->display($templatePath.".tpl");
    }



}
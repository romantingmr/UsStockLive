<?php
/**
 * @name landingpage入口
 * @author romantingmr
 * @version 2015-11-4
 */
class LandingPageAction extends SmartyAction
{
    private $_loginNeedCode = 0; // 登录是否需要验证码
	public function login()
	{
        if((isMobile() || isiOS() || isiPhone())){
            //未登录状态下跳转下载app页面
            header('Location:http://'.C('SF_SITE_HOST').'/im/#/user/login/');
            exit;
        }
        if (service('Passport')->isLogged())
            U('home', '', true);

        //新增转化用户需求需要判断访问来源是否来源于观点页面或组合页面，如果是，保存session，供注册的时候调用 by johnson 2015/12/17
        $transfer_page = is_transfer_page($_SERVER['HTTP_REFERER']);
        if($transfer_page) {
            session_start();
            $_SESSION['imb_transfer_page'] = (int)$transfer_page;
        }

		$templatePath = "user/page/landing/landing";
        $landingData = $this->fillWithTestData($templatePath);
        if($landingData===null)return;
        
        //推荐作者
        $authorList =json_decode(D('RecommendAuthor','landingpage')->getUidByHeatV2(1,100),1);
        //$authorList = json_decode(D('RecommendAuthor','landingpage')->getUidByHeat(),true);
        $landingData['langingView'] = $authorList;
        //合作伙伴广告图
        $cooperateList = json_decode(D('CooperatePartner','landingpage')->getAdverInfo(),true);
        $landingData['langingPartners'] = $cooperateList;
        
        //投资组合
        //$langingPortfolio = D('PortfolioInfo', 'landingpage')->getportfoliotop();
        //$langingPortfolio = D('PortfolioInfo', 'landingpage')->getportfoliotop_20160129();
        $langingPortfolio = D('PortfolioInfo', 'landingpage')->getportfoliotopFromAdmin();
        $landingData['langingPortfolio'] = array(
            'profile' => '用超前的投资理念，精心配置专属你的股票组合。',
            'list' => $langingPortfolio
        );
        //第三方登录链接
        $landingData['header']['loginWx'] = U('home/public/displayAddons',array('type'=>'wechat','addon'=>'Login','hook'=>'login_sync_other'));
        $landingData['header']['loginWb'] = U('home/public/displayAddons',array('type'=>'sina','addon'=>'Login','hook'=>'login_sync_other'));
        //下载链接 @Jay暂时写死，坑
        $androidDownUrl = $this->_getAndriodLastPackage();
        $landingData['header']['androidDownUrl'] = !empty($androidDownUrl) ? $androidDownUrl : 'http://app.imaibo.net/imaibo_v3.4.01223.apk';
        $landingData['header']['iosDownUrl'] = 'https://itunes.apple.com/us/app/tou-zi-mai-bo-wang-cai-jing/id596728388?l=zh&ls=1&mt=8';
        //$landingData['header']['iosDownUrl'] ="/activity/iosChange";
        //$landingData['header']['iosDownUrl'] = 'https://appsto.re/cn/ePvKJ.i';

        //==== begin 市场趋势
        //股票列表
        $stocklist = array(
                        "bj"=>array('002504','000938','000609'),
                        "sh"=>array('002178','600602','000668'),
                        "gd"=>array('601318','600030','600036'),
                        "cq"=>array('601158','601965','600132'),
                        "xz"=>array('600089','002302','002700')
                     );
        //每天轮询，用日期/3取余数
        $i = intval(intval(date('d',time())) % 3);
        //@Jay 暂时没看到有一次查询多个股票行情的方法,先用循环！！
        //@Jay 2015-11-20 加一天cache
        //@Jay 2016-02-29  PM要求去刷新更新，去缓存
        //$list = S('langingMarketTrendsList');
        //if(empty($list)){
            foreach ($stocklist as $key => $value) {
                $stockInfo = D('StockDb', 'investment')->baseInfoByStockCode('1'.$value[$i]);
                $stockSnap = D('StockG', 'investment')->getStockSnapBase($stockInfo);
                $list[] = array(
                             'area' => $key,
                             'stockName'  => $stockSnap['StockName'],
                             'percentage' => $stockSnap['pxchgratio'],
                             'stockState' => $stockSnap['sign'] == '+' ? 'up' : 'down',
                             'stockUrl' => U("investment/Home/stock",array('stockCode'=>'1'.$stockSnap['StockCode'])),
                          );
            }
            //S('langingMarketTrendsList', $list);
        //}
        $landingData['langingMarketTrends']  =  array(
            'profile' => '用超前的投资理念，精心配置专属你的股票组合。',
            'list' => $list
        );
        //==== end 市场趋势
        //请求注册页判断
        if(isset($_GET['mode']) && $_GET['mode'] === 'reg'){
            $landingData['mode'] = 'reg';
        }
        $this->smarty()->assign($landingData);
        $this->smarty()->display($templatePath.".tpl");
	}
	
    /**
     *  注册接口
     *  HTTP : POST
     */
    public function newregister(){
        $this->_setAllowOrigin();
        //注册类型
        $type = $this->_verifyParam('type', NULL, 'string', 'POST');
        //昵称
        $nickname = $this->_verifyParam('nickname', NULL, 'string', 'POST');
        //密码
        $password = $this->_verifyParam('password', NULL, 'string', 'POST');
        //协议
        $agree = $this->_verifyParam('agree', 0, 'int', 'POST');
        //图片验证码
        $imgverify = $this->_verifyParam('imgverify', '', '', 'POST');

        switch ($type) {
            case 'mobile':
                //手机号
                $mobile = $this->_verifyParam('mobile', NULL, 'string', 'POST');
                //短信验证码
                $smsverify = $this->_verifyParam('smsverify', NULL, 'string', 'POST');
                break;
            case 'email':
                //邮箱
                $email = $this->_verifyParam('email', NULL, 'string', 'POST');
                break;
            default:
                outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'错误的注册方式');
                break;
        }

        //注册策略验证
        $data =array(
            'nickname' => t($nickname, false, ENT_QUOTES),
            'password' => t($password, false, ENT_QUOTES),
            'agree' => intval($agree),
            'imgverify' => isset($imgverify) ? t($imgverify) : NULL,
            'mobile' => isset($mobile) ? t($mobile) : NULL,
            'smsverify' => isset($smsverify) ? t($smsverify) : NULL,
            'email' => isset($email) ? t($email) : NULL,
        );
        //注册验证策略
        $this->_verifyReg($data, $type);
        //开始注册
        if ($this->mid)
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'操作错误，您已经登录');

        if($type == 'email'){
            // 是否需要Email激活
            $need_email_activate = intval(model('Xdata')->get('register:register_email_activate'));
        }
        // 注册
        switch ($type) {
            case 'mobile':
                $prefix = '0086'; //只支持中国的手机号
                $servOpUser = service('OpUser');
                $smsemail = $servOpUser->buildEmail(5,0,$prefix.$mobile);
                $regdata = array(
                    'mobile' => $mobile,
                    'email' => $smsemail,
                    'password' => md5($data['password']),
                    'uname' => $data['nickname'],
                    'ctime' => time(),
                    'is_active'=>  1, //与开关相反
                    //'sex' => 
                    'is_init' => 1, //这个是什么意思？
                );
                break;
            case 'email':
                $regdata = array(
                    'email' => $data['email'],
                    'password' => md5($data['password']),
                    'uname' => $data['nickname'],
                    'ctime' => time(),
                    'is_active'=> $need_email_activate ? 0 : 1, //与开关相反
                    //'sex' => 
                    'is_init' => 1, //这个是什么意思？
                );
                break;
        }
        if($_SESSION['imaibo_source_id']){
            $imaibo_source_id = $_SESSION['imaibo_source_id'];
        }
        if(!empty($regdata))
            $uid = D('User', 'home')->addUserData($regdata);

        if ((int) $uid === 0)
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'注册失败，请稍后再试');

        /* ==== 注册成功事件处理 ==== */
        if ($uid > 0) //添加用户账户用于支付充值
            D('Account', 'pay')->cleckUserAccount($uid);
/*
        //默认关注
        $modelFollow = D('UserFollowSv', 'user');
        $regMap['RecType'] = 1;
        $zj = M('mb_reg_recommend')->field('ObjId')->where($regMap)->select();
        if ($zj) {
            foreach ($zj as $ex) {
                $modelFollow->doFollow($uid, intval($ex['ObjId']), 0,1,0);
            }
        }
        $modelFollow->doFollow($uid, 1000, 0,0,0);//默认关注官方账号,ID写死  johnson
        $modelFollow->doFollow($uid, 1281322, 0,0,0);//默认关注脉搏精选组合账号
*/
        //写个新注册的COOKIE标识(用户弹窗显示推荐专家）
        setcookie('newreg', 1, time() + 3600*24);//一天
        //默认关注官方帐号
        $modelFollow = D('UserFollowSv', 'user');
        $modelFollow->doFollow($uid, 1000, 0,1,0);//默认关注官方账号,ID写死
        //$modelFollow->doFollow($uid, 333774, 0,1,0);//草根股神
        $modelFollow->doFollow($uid, 1281322, 0,1,0);//脉搏IN资讯
        $modelFollow->doFollow($uid, 1, 0,1,0);//Imaibo小秘书
        if(intval($_REQUEST['reqfrom']) != 0 ){//PC注册不默认关注
            $cond = array();
            $res = D('RegRecommendExpert','admin')->getList($cond, 50);
            if(!empty($res['data'])){
                //随机取4位
                $randKey = array_rand($res['data'],4);
                $uids[] = $res['data'][$randKey[0]]['uid'];
                $uids[] = $res['data'][$randKey[1]]['uid'];
                $uids[] = $res['data'][$randKey[2]]['uid'];
                $uids[] = $res['data'][$randKey[3]]['uid'];
                foreach ($uids as $v) {
                    $modelFollow->doFollow($uid, intval($v), 0,0,0);//默认关注
                }
            } 
        }


        //注册 - 微博推送实例化
        D('User', 'home')->iniUserData($uid);

        // //添加用户资料配置信息 还不确定要不要，待定
        // $pUser = D('UserProfile');
        // $pUser->uid = $uid;
        // $pUser->upModule(t($_REQUEST['dotype']));

        // 将用户添加到myop_userlog，以使漫游应用能获取到用户信息
        $user_log = array('uid' => $uid, 'action' => 'add', 'type' => '0', 'dateline' => time(),);
        M('myop_userlog')->add($user_log);

        //判断用户是否是否来源于观点页面或组合页面，如果是，则写入新增转化用户表
        if($_SESSION['imb_transfer_page']) {
            $device = 1; //先埋一个坑，这里是PC端的注册流程，所以客户端默认是1 - PC端
            addTransferUser($uid, $_SESSION['imb_transfer_page'], $device);
        }

        // 发送激活邮件 
        if (!empty($need_email_activate) && $need_email_activate == 1) {// 邮件激活
            //发邮箱写上渠道session
            $_SESSION['imaibo_source_id'] = $imaibo_source_id;
            //发送激活邮件
            $this->_sendactivemail($uid, $data['email']);
        } 
        //生成随机头像 是否异步？不是的话，建议后期改异步
        getRandAvatar();

        $resdata = array(
            'uid' => $uid
        );
        //手机号注册成功，处理登录
        if($type == 'mobile'){
            $result = service('Passport')->loginLocal_2($mobile, $data['password'], 0);
            if($result['error_code'] !== 0){
                switch ($result['error_code']) {
                    case '102':
                        outputAdaptor(C('STATUS_CODE.USER_NOT_ACTIVE'), '用户未激活');
                        break;
                    default:
                        outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '登录失败, 用户名或密码错误');
                        break;
                }
            }

            $uid = $_SESSION['mid']; //$this->mid还在缓存中，第一次读不出来
            $user = M('user')->where('uid=' . $uid)->find();

            //获取帐号信息
            if (!$user) {
                outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '用户不存在或已被禁用');
            }
        }
        outputAdaptor(C('STATUS_CODE.SUCCESS'),$resdata);

    }

    //邀请注册
    public function inviteRegister(){
        $this->_setAllowOrigin();
        //注册类型
//        $type = $this->_verifyParam('type', NULL, 'string', 'POST');
        $type = "mobile";
        //昵称
        $nickname = $this->_verifyParam('nickname', NULL, 'string', 'POST');
        //密码
        $password = $this->_verifyParam('password', NULL, 'string', 'POST');
        //邀请人
        $inviter = $this->_verifyParam('inviter', NULL, 'int', 'POST');
        //协议
//        $agree = $this->_verifyParam('agree', 0, 'int', 'POST');
        //图片验证码
//        $imgverify = $this->_verifyParam('imgverify', '', '', 'POST');

        switch ($type) {
            case 'mobile':
                //手机号
                $mobile = $this->_verifyParam('mobile', NULL, 'string', 'POST');
                //短信验证码
                $smsverify = $this->_verifyParam('smsverify', NULL, 'string', 'POST');
                break;
            case 'email':
                //邮箱
                $email = $this->_verifyParam('email', NULL, 'string', 'POST');
                break;
            default:
                outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'错误的注册方式');
                break;
        }

        //注册策略验证
        $data =array(
            'nickname' => t($nickname, false, ENT_QUOTES),
            'password' => t($password, false, ENT_QUOTES),
            'agree' => 1,
            'imgverify' => isset($imgverify) ? t($imgverify) : NULL,
            'mobile' => isset($mobile) ? t($mobile) : NULL,
            'smsverify' => isset($smsverify) ? t($smsverify) : NULL,
            'email' => isset($email) ? t($email) : NULL,
        );
        //注册验证策略
        $this->_verifyReg($data, $type);
        //开始注册
        if ($this->mid)
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'操作错误，您已经登录');

        if($type == 'email'){
            // 是否需要Email激活
            $need_email_activate = intval(model('Xdata')->get('register:register_email_activate'));
        }
        // 注册
        switch ($type) {
            case 'mobile':
                $prefix = '0086'; //只支持中国的手机号
                $servOpUser = service('OpUser');
                $smsemail = $servOpUser->buildEmail(5,0,$prefix.$mobile);
                $regdata = array(
                    'mobile' => $mobile,
                    'email' => $smsemail,
                    'password' => md5($data['password']),
                    'uname' => $data['nickname'],
                    'ctime' => time(),
                    'is_active'=>  1, //与开关相反
                    //'sex' =>
                    'is_init' => 1, //这个是什么意思？
                );
                break;
            case 'email':
                $regdata = array(
                    'email' => $data['email'],
                    'password' => md5($data['password']),
                    'uname' => $data['nickname'],
                    'ctime' => time(),
                    'is_active'=> $need_email_activate ? 0 : 1, //与开关相反
                    //'sex' =>
                    'is_init' => 1, //这个是什么意思？
                );
                break;
        }
        if($_SESSION['imaibo_source_id']){
            $imaibo_source_id = $_SESSION['imaibo_source_id'];
        }
        if(!empty($regdata))
            $uid = D('User', 'home')->addUserData($regdata);

        if ((int) $uid === 0)
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'注册失败，请稍后再试');

        /* ==== 注册成功事件处理 ==== */
        if ($uid > 0) //添加用户账户用于支付充值
            D('Account', 'pay')->cleckUserAccount($uid);

        //默认关注
        $modelFollow = D('UserFollowSv', 'user');
        $regMap['RecType'] = 1;
        $zj = M('mb_reg_recommend')->field('ObjId')->where($regMap)->select();
        if ($zj) {
            foreach ($zj as $ex) {
                $modelFollow->doFollow($uid, intval($ex['ObjId']), 0,1);
            }
        }
        $modelFollow->doFollow($uid, 1000, 0,1);//默认关注官方账号,ID写死  johnson
        //$modelFollow->doFollow($uid, 333774, 0,1,0);//草根股神
        $modelFollow->doFollow($uid, 1281322, 0,1,0);//脉搏IN资讯
        $modelFollow->doFollow($uid, 1, 0,1,0);//Imaibo小秘书
        //注册 - 微博推送实例化
        D('User', 'home')->iniUserData($uid);

        // //添加用户资料配置信息 还不确定要不要，待定
        // $pUser = D('UserProfile');
        // $pUser->uid = $uid;
        // $pUser->upModule(t($_REQUEST['dotype']));

        // 将用户添加到myop_userlog，以使漫游应用能获取到用户信息
        $user_log = array('uid' => $uid, 'action' => 'add', 'type' => '0', 'dateline' => time(),);
        M('myop_userlog')->add($user_log);

        //判断用户是否是否来源于观点页面或组合页面，如果是，则写入新增转化用户表
        if($_SESSION['imb_transfer_page']) {
            $device = 1; //先埋一个坑，这里是PC端的注册流程，所以客户端默认是1 - PC端
            addTransferUser($uid, $_SESSION['imb_transfer_page'], $device);
        }

        // 发送激活邮件
        if (!empty($need_email_activate) && $need_email_activate == 1) {// 邮件激活
            //发邮箱写上渠道session
            $_SESSION['imaibo_source_id'] = $imaibo_source_id;
            //发送激活邮件
            $this->_sendactivemail($uid, $data['email']);
        }
        //生成随机头像 是否异步？不是的话，建议后期改异步
        getRandAvatar();

        $resdata = array(
            'uid' => $uid
        );
        //邀请注册
        $inviterInfo = D('User', 'home')->getUserByIdentifier($inviter, 'uid');
        if($inviterInfo){
            //相互关注
            $modelFollow->doFollow($inviter, $uid, 0,1);
            $modelFollow->doFollow($uid, $inviter, 0,1);
            //添加推荐关系
            $cond = array(
                "invitee"=>$inviter
            );
            $inviterInviteInfo = D("InviteSv","landingpage")->getOneInviteInfo($cond);
            if(count($inviterInviteInfo['invite_path']) == 5){
                array_shift($inviterInviteInfo['invite_path']);
            }
            $inviterInviteInfo['invite_path'][] = $inviter;
            $invateData = array(
                "inviter"=>$inviter,
                "invitee"=>$uid,
                "invite_path"=>$inviterInviteInfo['invite_path'],
                "invite_depth"=>$inviterInviteInfo['invite_depth']+1,
                "ctime"=>time(),
            );
            D("InviteSv","landingpage")->addInviteRegisterRelation($invateData);
            //赠送脉搏币
            $amount = 5;//注册赠送5个脉搏币
            D("InviteSv","landingpage")->coinRecharge($uid,$amount);
            //发送系统消息
            $message = array(
                'title'   => "欢迎加入投资脉搏", //必填 标题
                'message' => "亲，脉搏君终于等到你了！你的注册红包已经到账。", //必填 内容
                'touid' => array($uid), // 非必填，发送给谁 (群发时不需要这个参数)
                'type' => 2, // 必填 发送类型[ 1(群发) | 2(单发) ]
            );
            $res = D('UserSysMsgSv','user')->addMsg($message);
        }

        outputAdaptor(C('STATUS_CODE.SUCCESS'),$resdata);

    }

    //邀请信息接口
    public function inviteInfo(){
        $uid = $this->mid;
        $returnData = array(
            "invitee"=>0,
            "beans"=>0
        );
        if(!$uid){
            outputAdaptor(C('STATUS_CODE.SUCCESS'),$returnData);
        }
        $inviteeCount = D("InviteSv","landingpage")->getInviteeCount($uid);
        $returnData = array(
            "invitee"=>$inviteeCount,
            "beans"=>$inviteeCount*200
        );
        outputAdaptor(C('STATUS_CODE.SUCCESS'),$returnData);
    }
/****

##  获取用户信息

###接口描述

###接口地址
    
    GET /index.php?app=landingPage&mod=LandingPage&act=getUserInfo

###参数

- uid : 默认不传为当前登录用户
- fields : 需要返回的字段，默认只返回:uid、uname、face ，其余字段用"."拼接，eg: isFollowed.isVerified

###正确返回

    {
        "code":0,
        "message":"成功",
        "data":{
            "uid":"3018949",  //uid
            "uname":"heihei_",//用户名
            "face":"xxx",  //头像
            "isFollowed":0,  //是否关注 [1已关注 | 0未关注]
            "isVerified":1 //是否认证用户 [1已认证 | 0非认证]
            "comment":"世界和平111 <br>ddd <br>dd", //简介
            "fansCount":53 //粉丝数
        }
    }

*/
    //获取用户信息
    public function getUserInfo(){
        $this->_setAllowOrigin();
        $uid = $_GET['uid'] ? intval($_GET['uid']) : (int)$this->mid;
        $fields = t($_GET['fields']);
        if ($uid <= 0) {
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '用户ID错误！');
        }
        $user = D('User', 'home')->getUserByIdentifier($uid, 'uid');
        if (!$user){
            outputAdaptor(C('STATUS_CODE.NO_RESULT'), '无用户信息！');
        }
        $data['uid'] = $user['uid'];
        $data['uname'] = $user['uname'];
        $data['face'] = getUserFace($user['uid'],'b');
        if($fields){
            $fields = explode('.', trim($fields));
            foreach ($fields as $field) {
                switch ($field) {
                    case 'isFollowed':
                        if((int)$this->mid == $uid){
                            $data['isFollowed'] = 1;
                        }else{
                            $data['isFollowed'] = D('UserFollowMg', 'user')->getFollowId($this->mid,$uid)? 1 : 0;
                        }
                        break;
                    case 'isVerified':
                        $data['isVerified'] = D('UserCommonSv','user')->isVerified($uid);
                        break;
                    case 'comment':
                        $data['comment'] = htmlspecialchars_decode($user['comment']);
                        break;
                    case 'fansCount':
                        $modelUserFollowSv = D('UserFollowSv', 'user');
                        $data['fansCount'] = $modelUserFollowSv->getUserFansCounter($uid);
                        break;
                }
            }
        }
        outputAdaptor(C('STATUS_CODE.SUCCESS'),$data);
    }
    /**
     *  获取手机短信验证码(唔要图片验证码)
     *  HTTP : POST
     *  参数  : "mobile":  string 必填  //手机号
     */

    public function getInviteMobileCaptcha(){
        $this->_setAllowOrigin();
        session_start();
        //手机号前缀
        $prefix = '0086'; //只支持中国的手机号
        //手机号码
        $mobile = $this->_verifyParam('mobile',NULL,'string','POST');

        //手机验证码cache key
        $key = 'mobileReg_'.$mobile;

        if(!isValidPhone($mobile)){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'手机号错误！');
        }
        //手机号是否占用
        if(!D('User', 'home')->isMobileAvailable($mobile)){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'手机号已注册');
        }
        $sms = S($key);
        $haveSent = S('PC_haveSentmobile_'.$prefix.$mobile);
        if($haveSent){
            $sendTimes = S('PC_sendTimesmobile_'.$prefix.$mobile);
        }else{
            $sendTimes = 1;
            S('PC_haveSentmobile_'.$prefix.$mobile,1,3600);
            S('PC_sendTimesmobile_'.$prefix.$mobile,$sendTimes,3600);
        }

        /*if($sms && (time()-$sms['sendtime']<60 || $sendTimes>=6)){//发送短信需间隔60秒，每小时最多只能发6条短信
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'验证码发送太频繁啦，请稍后再试');
        }*/
        $ip = get_client_ip();
        $totalTimes4Day = D('User', 'home')->getIpTotalTimes4Day($ip, 5);
        if($sms && (time()-$sms['sendtime']<60 || $sendTimes>2 || $totalTimes4Day>=5)){//发送短信需间隔60秒，每小时最多只能发2条短信，每个IP一天最多只能发5条短信
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'手机验证码发送太频繁啦，请稍后再试');
        }
        //$modelSmsSend = model('SmsSend');
        $verify = rand_string(6,1);
        //$content = '验证码：'. $verify . ',您正在申请注册投资脉搏账户。10分钟内有效，请勿泄露。';
        //$mongoInfo = $modelSmsSend->send($mobile, $content, time(), 2);
        $mongoInfo = service('SmsSend')->pcMobileRegTpl($mobile,$verify,"10");
        if($mongoInfo){
            $res = S($key,array('verify'=>$verify,'sendtime'=>time()),600);
            S('PC_sendTimesmobile_'.$prefix.$mobile,++$sendTimes,600);
            outputAdaptor(C('STATUS_CODE.SUCCESS'),'手机验证码已经发送到您的手机，10分钟内输入有效');
        }else{
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'手机验证码发送失败，请稍后再试');
        }
    }

    /**
    *  注册验证策略
    */
    private function _verifyReg($data,$type='email'){
        $nickname = $data['nickname'];
        $password = $data['password'];
        $agree    = $data['agree'];
        $imgverify = $data['imgverify'];
        switch ($type) {
            case 'email':
                $email = $data['email'];
                break;
            case 'mobile':
                $mobile = $data['mobile'];
                $smsverify = $data['smsverify'];
                break;
        }

        //手机验证码cache key
        $key =  'mobileReg_'.$mobile;
        /* ==== 非查库信息检验 ====*/
        //手机号正确性 type="mobile"需检验
        if($type == 'mobile' && !isValidPhone($mobile)){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'手机号格式错误！');
        }
        //用户名
        if(empty($nickname) || !isValidUname($nickname)){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'昵称格式错误！');
        }
        //type="mobile"需检验
        if($type == 'mobile' && empty($smsverify)){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'验证码不能为空！');
        }
        if(empty($password) || strlen(trim($password))<6 || strlen(trim($password))>16 ){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'密码由6-16个字符组成');
        }
        if(empty($agree) || $agree != 1){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'请阅读并同意用户协议！');
        }
        //短信验证码
        $sms = S($key);
        //type = "mobile" 才验证
        if( $type == 'mobile'){
            if(!$sms){
                outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'验证码已过期！');
            }
            elseif($sms['verify'] != $smsverify){
                outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'验证码错误！');
            }
        }
        //图片验证码
        $scode = $_SESSION['scode'];
        $_SESSION['scode'] = null;
        //如果开启图片验证码，则需要检验图片验证码
        if (!empty($scode) && $scode != $imgverify)
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'图片验证码错误');

        /* ==== IP策略 ==== */
        //一个IP，一小时，不能调用接口超过100次 
        $regIp = get_client_ip();
        $cachekey = 'newregister_'.$regIp;
        $num = S($cachekey) ? S($cachekey) : 0 ;
        if( $num>=100 ){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '您的注册操作过于频繁，我猜您有点累了，歇歇吧');
        }
        S($cachekey,$num++,3600);

        /* ==== 需查库信息检验 ====*/
        if(!D('User', 'home')->isUnameAvailable($nickname)){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'用户名已被使用');
        }
        if (checkKeyWord($nickname) || checkNickname($nickname)) {
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),  '这个昵称禁止注册~', $this->__visitModel);
        }
        //type="mobile"需检验 手机号是否占用
        if($type == 'mobile' &&  !D('User', 'home')->isMobileAvailable($mobile)){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'手机号已注册');
        }
        //type="email"需检验 邮箱的域名是否禁止注册
        if($type == 'email' &&  isEmailDomainForbid($email)){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'该邮箱域名不支持，请与管理员联系');
        }
        //type="email"需检验 邮箱是否被占用
        if($type == 'email' &&  !D('User', 'home')->isEmailAvailable($email)){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'邮箱已注册');
        }

        /* ==== 激活策略 ====*/
    }

    /**
    *  注册信息预先校验
    */
    public function regcheck(){
        $type = $this->_verifyParam('type', NULL, 'string');
        $verification = $this->_verifyParam('verification', NULL ,'string');
        switch ($type) {
            case 'mobile':
                //$prefix = '0086'; //只支持中国的手机号 
                //手机号正确性
                if(!isValidPhone($verification)){
                    outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'手机号格式错误！');
                }
                //手机号是否占用
                if(!D('User', 'home')->isMobileAvailable($verification)){
                    outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'手机号已注册');
                }
                break;
            case 'nickname':
                //用户名
                if(empty($verification) || !isValidUname($verification)){
                    outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'昵称格式错误！');
                }
                if(!D('User', 'home')->isUnameAvailable($verification)){
                    outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'昵称已被占用！');
                }
                if (checkKeyWord($verification) || checkNickname($verification)) {
                    outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),  '这个昵称禁止注册~', $this->__visitModel);
                }
                break;
            case 'email':
                if (isset($verification) && !isValidEmail($verification)) {
                    outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'邮箱错误');
                }
                //邮箱是否被占用
                if(isset($verification) && !D('User', 'home')->isEmailAvailable($verification)){
                    outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'邮箱已注册');
                }
                
                break;
            case 'password':
                if(strlen(trim($verification))<6 || strlen(trim($verification))>16 ){
                    outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'密码由6-16个字符组成');
                }
                break;
            default :
                outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'verification参数错误');
                break;

        }
        outputAdaptor(C('STATUS_CODE.SUCCESS'),"格式正确");
    }

    //生成图形验证码
    public function verify() {
        session_start();
        require_once (SITE_PATH . '/addons/libs/Image.class.php');
        require_once (SITE_PATH . '/addons/libs/String.class.php');
        Image::buildImageVerify(4,1,'png',48,22,'registerVerify');
    }

    /**
    *  获取手机短信验证码
    *  HTTP : POST
    *  参数  : "mobile":  string 必填  //手机号
    */

    public function getmobilecaptcha(){
        $this->_setAllowOrigin();
        session_start();
        //手机号前缀
        $prefix = '0086'; //只支持中国的手机号
        //手机号码
        $mobile = $this->_verifyParam('mobile',NULL,'string','POST');

        //手机验证码cache key
        $key = 'mobileReg_'.$mobile;

        if(!isValidPhone($mobile)){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'手机号错误！');
        }
        //手机号是否占用
        if(!D('User', 'home')->isMobileAvailable($mobile)){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'手机号已注册');
        }
        //判断图形验证码是否正确
        if(!empty($_POST['captchatoken']) && !empty($_POST['captchacode'])){
            //验证码token
            //$captchatoken = $this->_verifyParam('captchatoken',NULL,'string','POST');
            //验证码code
            //$captchacode = $this->_verifyParam('captchacode',NULL,'string','POST');
            //检验验证码
            //$code  = $this->getParam("code");
            $captchatoken = t($_POST['captchatoken']);
            $captchacode = t($_POST['captchacode']);
            require_once ADDON_PATH . '/libs/Captcha.class.php';
            $_Captcha = new Captcha();
            if(!$_Captcha->checkCode($captchatoken, $captchacode)) {
                outputAdaptor(C('STATUS_CODE.OPER_FAIL'), '验证码有误');
            }else{
                //刷新验证码
                $_Captcha->refreshCode($captchatoken);
            }

        }else{ //兼容旧的
            $mobileImgCode = (int)$_POST['mobileimgcode'];//$this->_verifyParam('mobileimgcode',NULL,'string','POST');
            $registerVerify = $_SESSION['registerVerify'];
            unset($_SESSION['registerVerify']);
            if (md5($mobileImgCode) != $registerVerify) {
                outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'图形验证码错误');
            }
        }
        $sms = S($key);
        $haveSent = S('PC_haveSentmobile_'.$prefix.$mobile);
        if($haveSent){
            $sendTimes = S('PC_sendTimesmobile_'.$prefix.$mobile);
        }else{
            $sendTimes = 1;
            S('PC_haveSentmobile_'.$prefix.$mobile,1,3600);
            S('PC_sendTimesmobile_'.$prefix.$mobile,$sendTimes,3600);
        }

        /*if($sms && (time()-$sms['sendtime']<60 || $sendTimes>=6)){//发送短信需间隔60秒，每小时最多只能发6条短信
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'验证码发送太频繁啦，请稍后再试');
        }*/
        $ip = get_client_ip();
        $totalTimes4Day = D('User', 'home')->getIpTotalTimes4Day($ip, 5);
        if($sms && (time()-$sms['sendtime']<60 || $sendTimes>2 || $totalTimes4Day>=5)){//发送短信需间隔60秒，每小时最多只能发2条短信，每个IP一天最多只能发5条短信
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'手机验证码发送太频繁啦，请稍后再试');
        }
        //$modelSmsSend = model('SmsSend');
        $verify = rand_string(6,1);
        //$content = '验证码：'. $verify . ',您正在申请注册投资脉搏账户。10分钟内有效，请勿泄露。';
        //$mongoInfo = $modelSmsSend->send($mobile, $content, time(), 2);
        $mongoInfo = service('SmsSend')->pcMobileRegTpl($mobile,$verify,"10");
        if($mongoInfo){
            $res = S($key,array('verify'=>$verify,'sendtime'=>time()),600);
            S('PC_sendTimesmobile_'.$prefix.$mobile,++$sendTimes,600);
            outputAdaptor(C('STATUS_CODE.SUCCESS'),'手机验证码已经发送到您的手机，10分钟内输入有效');
        }else{
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'手机验证码发送失败，请稍后再试');
        }
    }

    /**
    *  验证手机短信验证码
    *  HTTP : GET
    *  参数  : "mobile"  :  string 必填  //手机号
    *         "captcha" :  string 必填  //手机短信验证码
    *         "type"    :  string 必填  //短信类型
    */

    public function verifymobilecaptcha(){
        //手机号码
        $mobile = $this->_verifyParam('mobile',NULL,'string');
        //验证码
        $smsverify = $this->_verifyParam('smsverify',NULL,'string');
        //验证类型
        $type = $this->_verifyParam('type','PcReg','string');
        switch ($type) {
            case 'PcReg':
                $key = 'mobileReg_'.$mobile;
                break;
            case 'WapFindpwd':
                $key = 'WapFindpwd'.$mobile;
                break;
            case 'PCFindpwd':
                $key = 'PCFindpwd'.$mobile;
                break;
            default:
                //默认是PC手机注册类型
                $key = 'mobileReg_'.$mobile;
                break;
        }
        //手机验证码cache key
        $sms = S($key);
        if(!$sms){
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'验证码已过期！');
        }
        if($sms['verify'] == $smsverify){
            outputAdaptor(C('STATUS_CODE.SUCCESS'),'验证码正确');
        }else{
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'验证码错误！');
        }
    }

    /**
    *  登录
    *  HTTP : POST
    *  参数  : "username"  :  string 必填  //手机号|邮箱
    *         "password" :  string 必填  //密码
    */
    //public function mobilelogin(){
    public function newlogin(){
        $this->_setAllowOrigin();
        //暂时支持手机号码|邮箱 ，以后可以再支持用户名登录
        $username = $this->_verifyParam('username',NULL,'string','POST');
        //密码
        $password = $this->_verifyParam('password',NULL,'verify','POST');
        //记住我
        $remember = $this->_verifyParam('remember',0 ,'int','POST');
        //指定跳转URL
        $redirectUrl = $this->_verifyParam('redirectUrl', '', '', 'POST');
        //判断要不要加
        //登录失败进行计数
        $ip = get_client_ip();
        if( $ip != 'unknown' ){
            $loginErrCacheKey = 'LoginErr'.$ip;
            $loginErrNum = S($loginErrCacheKey);
            $loginErrNum = intval($loginErrNum);
            if($loginErrNum >= 3){//登录失败3次，则需要验证码
                $this->_loginNeedCode = 1; //需要图片验证码
                //验证码token
                $captchatoken = $this->_verifyParam('captchatoken',NULL,'string','POST');
                //验证码code
                $captchacode = $this->_verifyParam('captchacode',NULL,'string','POST');
                //检验验证码
                $resImgCode = $this->_checkImgCode($captchatoken,$captchacode);
                if(!$resImgCode) {
                    outputAdaptor(C('STATUS_CODE.LOGIN_NEED_CODE'), '验证码有误'); //LOGIN_NEED_CODE 告诉前端继续使用图片验证码
                }
            }    
        }
        $result = service('Passport')->loginLocal_2($username, $password, intval($remember));
        if($result['error_code'] !== 0){
            switch ($result['error_code']) {
                case '102':
                    //outputAdaptor(C('STATUS_CODE.USER_NOT_ACTIVE'), '用户未激活');
                    $this->_outputAdaptorFilter(C('STATUS_CODE.USER_NOT_ACTIVE'), '用户未激活');
                    break;
                case '104':
                    //outputAdaptor(C('STATUS_CODE.OPER_FAIL'), '账号已冻结');
                    $this->_outputAdaptorFilter(C('STATUS_CODE.OPER_FAIL'), '账号已冻结');
                    break;
                default:
                    //登录失败进行计数
                    $ip = get_client_ip();
                    if( $ip != 'unknown' ){
                        $loginErrCacheKey = 'LoginErr'.$ip;
                        $loginErrNum = S($loginErrCacheKey);
                        $loginErrNum = intval($loginErrNum);
                        //增加一次错误次数，缓存时间24小时
                        S($loginErrCacheKey,++$loginErrNum,86400); 
                        if($loginErrNum >= 3){//24小时内，运行登录失败3次
                            outputAdaptor(C('STATUS_CODE.LOGIN_NEED_CODE'), '登录失败, 用户名或密码错误..');
                        }    
                    }
                    //outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '登录失败, 用户名或密码错误');
                    $this->_outputAdaptorFilter(C('STATUS_CODE.PARAM_ERROR'), '登录失败, 用户名或密码错误');
                    break;
            }
        }

        //登录成功则清除登录失败计数
        $ip = get_client_ip();
        if( $ip != 'unknown' ){
            $loginErrCacheKey = 'LoginErr'.$ip;
            $loginErrNum = S($loginErrCacheKey);
            //增加一次错误次数，缓存时间24小时
            if( $loginErrNum > 0)
                S($loginErrCacheKey,0,-1);    
        }

        $uid = $_SESSION['mid']; //$this->mid还在缓存中，第一次读不出来
        if(D('BlockAdeRds','home')->isBlock($uid)){//2016-02-01 By Ken
        	//outputAdaptor(C('STATUS_CODE.OPER_FAIL'), '账号已冻结');
            $this->_outputAdaptorFilter(C('STATUS_CODE.OPER_FAIL'), '账号已冻结');
        }
        $user = M('user')->where('uid=' . $uid)->find();

        //获取帐号信息
        if (!$user) {
            //outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '用户不存在或已被禁用');
            $this->_outputAdaptorFilter(C('STATUS_CODE.PARAM_ERROR'), '用户不存在或已被禁用');
        }

        $data = array();
        if(!empty($redirectUrl)){
            $data =array('redirectUrl'=>$redirectUrl); 
        }
        $userinfo = getUserInfo($uid);
        $data['uid'] = $uid;
        $data['login_username'] = $user['uname'];
        $data['login_account'] = $username; // 登录时的用户名 [邮箱|手机]
        //$data['face'] = $userinfo['face'];
        $data['face'] = getUserFace($uid,'m',1);
        //邀请注册
        $cond = array(
            "invitee"=>(int)$uid
        );
        $inviterInviteInfo = D("InviteSv","landingpage")->getOneInviteInfo($cond);
        if($inviterInviteInfo["inviter"]){
            D("InviteSv","landingpage")->beanRecharge($inviterInviteInfo["inviter"],$uid);
        }
        outputAdaptor(C('STATUS_CODE.SUCCESS'),$data);
    }

    
    /**
    *  参数验证
    *  $param 参数名
    *  $default 默认值
    *  $type 参数类型
    *  $method GET or POST 方法，对应$_GET,$POST
    */
    private function _verifyParam($name, $defaultValue=NULL, $type=NULL, $method='GET'){
        
        switch ($method) {
            case 'GET':
                $value = isset($_GET[$name]) ? $_GET[$name] : $defaultValue;
                break;
            case 'POST':
                $value = isset($_POST[$name]) ? $_POST[$name] : $defaultValue;
                break;
            default:
                //outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '不正常的操作方法');
                $this->_outputAdaptorFilter(C('STATUS_CODE.PARAM_ERROR'), '不正常的操作方法');
                break;
        }

        if($value === NULL || ($value !== $defaultValue && !isset($value) ))
            //outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), $name.'参数是必须的');
            $this->_outputAdaptorFilter(C('STATUS_CODE.PARAM_ERROR'), $name.'参数是必须的');
        if($type === 'int') {
            if(!is_numeric($value))
                //outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), $name.'参数是必须是整形');
                $this->_outputAdaptorFilter(C('STATUS_CODE.PARAM_ERROR'), $name.'参数是必须是整形');
            $value = intval($value);
        }

        if($type === 'string') {
            if(!is_string($value) || empty($value))
                //outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), $name.'参数是必须是字符串并不能为空');
                $this->_outputAdaptorFilter(C('STATUS_CODE.PARAM_ERROR'), $name.'参数是必须是字符串并不能为空');
        }

        if($type === 'json') {
            $value = json_decode(stripcslashes($value),true);
            if(json_last_error() != JSON_ERROR_NONE) 
                //outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), $name.'参数是无效的json格式');
                $this->_outputAdaptorFilter(C('STATUS_CODE.PARAM_ERROR'), $name.'参数是无效的json格式');
        }
        return $value;
    }
    /*
    *   发送激活邮件
    *   HTTP : GET
    *   
    */
    public function newactivatemail(){
        $this->_setAllowOrigin();
        $email = $this->_verifyParam('email',NULL,'string','POST');

        //检查是否激活
        $user = D('User', 'home')->getUserByIdentifier($email, 'email');
        if(!$user){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '用户不存在或已被禁用');
        }
        if ($user['is_active'] == 1) {
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'), '您的用户已经激活，请勿重复激活');
        }   
        $email = $user['email'];
        $uid = $user['uid'];
        //操作频繁判定
        $cachekey = 'newactivatemail_sendactivemail'.$uid;
        if(!empty(S($cachekey))){
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'您的激活邮件已经发送，一分钟才能重新发送');
        }
        //发送激活邮件
        $this->_sendactivemail($uid, $email);
        //设置60s cache标志已经发送过激活邮件
        S($cachekey,array('sendtime'=>time()),60);

        outputAdaptor(C('STATUS_CODE.SUCCESS'),'激活邮件发送成功');
    }

    //发送激活邮件
    public function _sendactivemail($uid, $email, $invite = '') {

        //设置激活路径
        $activate_url = service('Validation')->addValidation($uid, '', U('home/Public/doActivate',array("imaibo_source_id"=>$_SESSION['imaibo_source_id'])), 'register_activate', serialize($invite));
        $body = <<<EOD
感谢您注册广州金砖旗下《投资脉搏网iMaibo.net》<br />

请点击以下注册确认链接，激活您的注册帐号：<br />

<a href="$activate_url" target='_blank'>$activate_url</a><br />

点击以上链接无法访问，可以尝试将该网址复制并粘贴至新的浏览器窗口中。<br />

如果误收到此电子邮件，你无需执行任何操作来取消帐号，此帐号将不会被激活。
EOD;
        // 发送邮件
        global $ts;
        $params = array(
            'email' => $email,
            'title' => "激活{$ts['site']['site_name']}帐号",
            'body'  => $body,
        );
        service('Mail')->sendRegEmail($params);
        return true;
    }

    //调用EDM(汉启)的邮件系统底层进行邮件的发送  Johnson  2015/9/25
    private function _sendEmail($email, $title, $body) {
        // require_once ADDON_PATH . '/libs/EdmTriggerMailer/EdmEmailService.class.php';
        // $emailService = new EdmEmailService();

        // $params['email'] = $email; //'123268856@qq.com';
        // $params['title'] = $title;
        // $params['body'] = $body;
        // $emailId = $emailService->addEmail($params);
        // return $emailId;
        $params = array(
            'email' => $email,
            'title' => $title,
            'body'  => $body,
        );
        service('Mail')->defaultSendEmail($params);
    }
      

    /**
     *  首页投资组合榜单
     *  HTTP: GET
     *  规则：创建时间最近两个月，且10人以上关注，收益最高
     */
    public function getportfoliotop(){
        //获取数量，默认3
        $num = $this->_verifyParam('n', 3 ,'int');
        $modelPortfolio = D('PortfolioBase', 'csi');
        $cond = array();
        $cond['status'] = 1;
        $lastlastmonth = strtotime(date('Y-m-d',time()))-3600*24*30*2; //两个个月内创建的投资组合
        $cond['ctime'] = array('$gte'=>$lastlastmonth);
        $cond['followedCount'] = array('$gte'=>10);
        $option['sort'] = array('yield_ratio_total' => -1);
        $option['limit'] = $num;

        $cachekey = 'getportfoliotop';
        $data = S($cachekey);
        if(!empty($data)){
            outputAdaptor(C('STATUS_CODE.SUCCESS'),$data);
        }else{
            $list = $modelPortfolio->getInfoList($cond, $option, array( 'uid' => true, 'portfolio_id'=> true, 'name' => true, 'yield_ratio_total' => true, 'followedCount' => true));
            foreach($list as  $v) {
                $data[] = array(
                            'pid' => $v['portfolio_id'],
                            'uid' => $v['uid'],
                            'author' => getUserName($v['uid']),
                            'title' => $v['name'],
                            'earns' => sprintf("%1\$.2f", round($v['yield_ratio_total'] * 100, 2)),
                            'followedCount' => intval($v['followedCount']),
                          );
            }
            unset($list);
            //设置一天的cache
            S($cachekey,$data,86400);
        }
        outputAdaptor(C('STATUS_CODE.SUCCESS'),$data);
    }
    /**
    * 找回密码
    */
    public function findpwdapi(){
        //手机号或邮箱
        $value = $this->_verifyParam('value',NULL,'string','POST');
        //类型 标识是哪个平台的找回密码功能
        $reqfrom =$this->_verifyParam('reqfrom',0,int,'POST');
        //找回密码步骤 用于手机,手机号有重新发送的功能step==2,表示重新发送，可以不用图片验证码
        $step = $this->_verifyParam('step',1,'int','POST');
        if(!in_array($step, array(1,2)) && $from != 'PCFindpwd' ){
            //PC才有重发不用图片验证码的逻辑， wap暂时没有
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'), '错误的提交~');
        }
        if($step == 1){
            //验证码token
            $captchatoken = $this->_verifyParam('captchatoken',NULL,'string','POST');
            //验证码code
            $captchacode = $this->_verifyParam('captchacode',NULL,'string','POST');
            //检验验证码
            require_once ADDON_PATH . '/libs/Captcha.class.php';
            $_Captcha = new Captcha();
            if(!$_Captcha->checkCode($captchatoken, $captchacode)) {
                outputAdaptor(C('STATUS_CODE.OPER_FAIL'), '验证码有误');
            }else{
                //刷新验证码
                $_Captcha->refreshCode($captchatoken);
            }
        }

        //检验邮箱或手机
        //检验是手机还是邮箱
        if(isValidPhone($value)){ //优先匹配手机号码
            $type = 'mobile';
        }elseif(isValidEmail($value)){ 
            $type = 'email';
        }else{
            $type = 'other';
        }
        switch ($type) {
            case 'email':
                if (empty($value) || !isValidEmail($value)) {
                    outputAdaptor(C('STATUS_CODE.EMAIL_ILLEGAL'), '邮箱格式错误');
                }
                $user = M("user")->where('`email`="' . $value . '"')->find();
                if(!$user){
                    outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'帐号不存在');
                }else{
                    $code = base64_encode($user["uid"] . "." . md5($user["uid"] . '+' . $user["password"]));
                    //$url = U('home/Public/resetPassword', array('code' => $code));
                    if($reqfrom == 1){ // WAP
                        $url = 'http://'. C('SF_SITE_HOST').'/im/#/user/findpassword/'.$code;
                    }else{// PC 
                        $url = U('landingpage/landingPage/findpwd', array('code' => $code));
                    }
                    
                    $body = <<<EOD
                    <strong>{$user["uname"]}，你好: </strong><br/>
                    您只需通过点击下面的链接重置您的密码: <br/>
                    <a href="$url">$url</a><br/>
                    如果通过点击以上链接无法访问，请将该网址复制并粘贴至新的浏览器窗口中。<br/>
                    如果你错误地收到了此电子邮件，你无需执行任何操作来取消帐号！此帐号将不会启动。
EOD;
                    global $ts;
                    //$email_sent = service('Mail')->send_email($user['email'], "重置{$ts['site']['site_name']}密码", $body);
                    $this->_sendEmail($user['email'], "重置{$ts['site']['site_name']}密码", $body);
                    outputAdaptor(C('STATUS_CODE.SUCCESS'), '邮件发送成功，请查看邮箱！');
                }
                break;
            case 'mobile':
                $mobile = $value;
                $prefix = "0086";
                $user = M("user")->where('`mobile`="' . $value . '"')->find();
                if(!$user){
                    outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'帐号不存在');
                }else{
                    //发验证码
                    //$key = 'WapFindpwd'.$mobile;
                    if($reqfrom == 1){ // WAP
                        $key = 'WapFindpwd'.$mobile;
                    }else{// PC 
                        $key = 'PCFindpwd'.$mobile;
                    }
                    $sms = S($key);
                    $haveSent = S('findpwd_haveSentmobile_'.$prefix.$mobile);
                    if($haveSent){
                        $sendTimes = S('findpwd_sendTimesmobile_'.$prefix.$mobile);
                    }else{
                        $sendTimes = 1;
                        S('findpwd_haveSentmobile_'.$prefix.$mobile,1,3600);
                        S('findpwd_sendTimesmobile_'.$prefix.$mobile,$sendTimes,3600);
                    }
                    $ip = get_client_ip();
                    $totalTimes4Day = D('User', 'home')->getIpTotalTimes4Day($ip, 5);
                    if($sms && (time()-$sms['sendtime']<60 || $sendTimes>20 || $totalTimes4Day>=50)){//发送短信需间隔60秒，每小时最多只能发20条短信，每个IP一天最多只能发50条短信
                        outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'密码修改太频繁了，请1分钟后再试');
                    }
                    //$modelSmsSend = model('SmsSend');
                    $verify = rand_string(6,1);
                    $content = '验证码：'. $verify . ',您正在申请重置投资脉搏账户密码。10分钟内有效，请勿泄露。';
                    //$mongoInfo = $modelSmsSend->send($mobile, $content, time(), 2);
                    $mongoInfo = service('SmsSend')->resetPwdTpl($mobile,$verify,"10");
                    if($mongoInfo){
                        $res = S($key,array('verify'=>$verify,'sendtime'=>time()),600);
                        S('findpwd_sendTimesmobile_'.$prefix.$mobile,++$sendTimes,600);
                        outputAdaptor(C('STATUS_CODE.SUCCESS'),'重置密码验证码已经发送到您的手机，10分钟内输入有效');
                    }else{
                        outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'手机验证码发送失败，请稍后再试');
                    }
                }
                break;
            default:
                outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'用户名检测错误');
                break;
        }
    }
    //找回密码页面
    public function findpwd(){
        $data = array();
        $templatePath = "user/page/landing/findpassword";
        $data = $this->fillWithTestData($templatePath);
        $data['mode'] = 'findpwd';
        if(!empty($_GET['code'])){ // 默认是 findpwd，url上携带code时，解析成resetpwd
            $data['mode'] = 'resetpwd';
        }
        $this->smarty()->assign($data);
        $this->smarty()->display($templatePath.".tpl");
    }
    // //重置密码页面
    // public function resetpwd(){
    //     //$value = $this->_verifyParam('type',NULL,'string','GET');
    // }
    //重置密码接口
    public function resetpwdapi(){
        //类型
        $type = $this->_verifyParam('type',NULL,'string','POST');
        //账户信息 【邮箱链接里的code|手机】
        $value = $this->_verifyParam('value',NULL,'string','POST');
        //新密码
        $newpasswd = t($this->_verifyParam('newpasswd',NULL,'string','POST'));
        $reqfrom =$this->_verifyParam('reqfrom',0,int,'POST');
        switch ($type) {
            case 'mobile':
                $mobile = $value;
                //手机重置密码需要短信验证码
                $smsverify = $this->_verifyParam('smsverify',NULL,'string','POST');
                if(!isValidPhone($mobile)){ //匹配手机号码
                    outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'无效手机号码');
                }
                //校验短信验证码
                //$key = 'WapFindpwd'.$mobile;
                if($reqfrom == 1){ // WAP
                    $key = 'WapFindpwd'.$mobile;
                }else{// PC 
                    $key = 'PCFindpwd'.$mobile;
                }
                $sms = S($key);
                if( !$sms || $sms['verify'] != $smsverify){
                    outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'验证码错误啦^^');
                }
                $user = M('user')->where('`mobile`=' . $mobile)->find();
                break;
            case 'email':
                $code = explode('.', base64_decode($value));
                $user = M('user')->where('`uid`=' . $code[0])->find();
                if ($code[1] !== md5($code[0] . '+' . $user["password"])) {
                    outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'错误来源');
                }
                break;
            default:
                //类型错误
                outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'错误类型');
                break;
        }
        if(!$user){
             outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'无效用户');
        }
        if(strlen(trim($newpasswd))<6 || strlen(trim($newpasswd))>16 ){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'密码由6-16个字符组成');
        }
        //当用户输入的密码跟原密码相同时
        if($user["password"] == md5($newpasswd)) {
            outputAdaptor(C('STATUS_CODE.SUCCESS'),'重置密码成功.'); //哈哈我这里加了个.
        }
        $res = M('user')->setField('password', md5($newpasswd), 'uid = ' . $user['uid']);
        //去掉用户缓存信息
        S('S_userInfo_' . $user['uid'], null);
        // 同步草根股神用户缓存信息
        S_GS('S_userInfo_' . $code[0], null);
        if ($res) {
            outputAdaptor(C('STATUS_CODE.SUCCESS'),'重置密码成功');
        } else {
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'重置密码失败');
        }
    }

    /**
    *   图片验证码过程
    *   1、获取captchatoken接口
    *   2、通过captchatoken获取验证码图片
    *   3、通过captchatoken和验证码图片内容captchacode验证验证码正确性
    */
    //获取captchatoken
    public function getCaptchaToken(){
        $this->_setAllowOrigin();
        $type = $this->_verifyParam('type',NULL,'string','GET');
        $typearray = array(
            'WapFindpwd',// wap页面找回密码
            'WapRegister', //wap注册
            //'PCLandingLogin',//PC LandingPage登录
            'PCLandingMobileCode',//PC LandingPage收手机短信验证码 
            'PCFindpwd', //PC LandingPage找回密码
            'LoginErr',
        );
        if(!in_array($type, $typearray)) {
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'参数错误');
        }
        //判断是否需要验证码
        $retoken = 1;
        $data = array();
        if($retoken) {
            require_once ADDON_PATH . '/libs/Captcha.class.php';
            $_Captcha = new Captcha();
            $token = $_Captcha->createCode();
        } else {
            $token = "";
        }
        $data['captchatoken'] = $token;
        outputAdaptor(C('STATUS_CODE.SUCCESS'),$data);
    }
    //获取验证码图片
    public function getCaptchaImg() {
        $this->_setAllowOrigin();
        $token = $this->_verifyParam('captchatoken',NULL,'string','GET');
        require_once ADDON_PATH . '/libs/Captcha.class.php';
        $_Captcha = new Captcha(100, 30, 4);
        $_Captcha->refreshCode($token);
        $_Captcha->showImage($token);
        return;
    }
    //验证图片验证码
    public function verifyCaptchaImg() {
        $this->_setAllowOrigin();
        $token = $this->_verifyParam('captchatoken',NULL,'string','POST');
        $code = $this->_verifyParam('captchacode',NULL,'string','POST');
        require_once ADDON_PATH . '/libs/Captcha.class.php';
        $_Captcha = new Captcha();
        $res = $_Captcha->checkCode($token, $code);
        if($res){
            outputAdaptor(C('STATUS_CODE.SUCCESS'),'验证码正确');
        }else{
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'验证有误');
        }
    }
    //系统登出
    public function logout() {
        if(!$this->mid){
            outputAdaptor(C('STATUS_CODE.SUCCESS'),'退出成功');
        }
        service('Passport')->logoutLocal();
        outputAdaptor(C('STATUS_CODE.SUCCESS'),'退出成功');
    }
    //检测用户登录状况，获取用户信息
    public function  userinfo() {
        if($this->mid){
            //$userinfo = getUserInfo($this->mid);
            $data['uid'] = $this->mid;
            outputAdaptor(C('STATUS_CODE.SUCCESS'),$data);
        }else{
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'操作错误，请先登录~');
        }
    }

    private function _getAndriodLastPackage(){
        $packageM = D("AppPackageMg","admin");
        $os = 2; // 安卓 
        $data = $packageM->getLastPackageByOs($os);
        return !empty($data['downurl']) ? $data['downurl'] : '';
    }
    //检验验证码
    private function _checkImgCode($captchatoken,$captchacode){
        require_once ADDON_PATH . '/libs/Captcha.class.php';
        $_Captcha = new Captcha();
        if(!$_Captcha->checkCode($captchatoken, $captchacode)) {
            //outputAdaptor(C('STATUS_CODE.OPER_FAIL'), '验证码有误');
            return false;
        }else{
            //刷新验证码
            $_Captcha->refreshCode($captchatoken);
        }
        return true;
    }
    //_outputAdaptor 过滤替换
    private function _outputAdaptorFilter($code,$message,$data=''){
        if($this->_loginNeedCode == 1){//显示需要图片验证码的错误类型
            outputAdaptor(C('STATUS_CODE.LOGIN_NEED_CODE'),$message,$data);
        }else{
            outputAdaptor($code,$message,$data);
        }
    }    
    private function _setAllowOrigin(){
        $httpOrigin = $_SERVER['HTTP_ORIGIN']; 
        $zuhe = 'http://'.C('FINANCE_SYSTEM_DOMAIN');
        $ydt = 'http://'.C('YIDIANTOU_DOMAIN');
        $allowOrigin = array($zuhe,$ydt);
        if (in_array($httpOrigin, $allowOrigin))  {    
            header("Access-Control-Allow-Origin: $httpOrigin"); 
            header("Access-Control-Allow-Credentials:true");  
            header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
        }
    }

    public function downloadAPP(){
    	$uvName = 'UV_CODE';
		$uvCode = $_COOKIE[$uvName];
		if(empty($uvCode)){
			$uvCode = md5($this->mid.session_id());
			setcookie($uvName,$uvCode,time()+311040000);
		}
		$ip = get_client_ip();
		D('StatVisitor', 'home')->addStatDownloadAPP($ip,$uvCode, $_SERVER['HTTP_USER_AGENT'], $this->mid);
		
        $templatePath = "other/page/appDownload/index";
        $data = $this->fillWithTestData($templatePath);
        $data['loginUser'] = $this->_loginInfo();
        $this->smarty()->assign($data);
        $this->smarty()->display($templatePath.".tpl");
    }

    public function oldBrowser(){
    	//数据采集
    	$ip = get_client_ip();
    	$href = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    	$uid = $this->mid ? $this->mid : 0;
    	$res = D('StatVisitor', 'home')->addStatOldBrowser($ip, $_SERVER['HTTP_REFERER'], $href, $_SERVER['HTTP_USER_AGENT'], $uid);
    	$templatePath = "_common/page/ie8";
        $data = $this->fillWithTestData($templatePath);
        $this->smarty()->assign($data);
        $this->smarty()->display($templatePath.".tpl");
    }

    public function ydtDownload(){
        if(isiOS() || isAndroid() || isMobile()) {
            header("Location:http://".C('SF_SITE_HOST')."/ydt/download/m/");
            exit;
        }
        $templatePath = "other/page/ydtDownload/index";
        $data = $this->fillWithTestData($templatePath);
        $data['loginUser'] = $this->_loginInfo();
        $data['documentMeta'] = array(
            'title' => '易点投官网-免费美股开户,美股实时行情,50美金炒美股！',
            'meta' => array(
                array('name'=>'keywords','content'=>'易点投官网,美股开户,美股投资交易,美股行情,道琼斯指数,纳斯达克指数,A50,比特币,以太币,e投睿eToro'),
                array('name'=>'description','content'=>"易点投携手国际知名券商e投睿eToro致力为国内投资者提供便捷的美股开户及在线交易服务，3分钟极速开户、免费实时行情及超低的交易点差佣金，交易品种涵括美股、指数、ETFs、商品和货币等金融产品。"),
            )
        );
        $this->smarty()->assign($data);
        $this->smarty()->display($templatePath.".tpl");
    }

/****

## 注册推荐专家

###接口描述
    这个接口用来获取推荐专家

###接口地址
    
    GET /index.php?app=landingpage&mod=landingPage&act=regrecomlists
    
###参数

- 无

###正确返回

    {
        "code":0,
        "message":"成功",
        "data":[
            {
                "uid":1,
                "comment":"222334" //简介
                "uname":"Imaibo小秘书",
                "face":"xxx",
                "icon_experts":[],
                "todaydynamic":0,//今日动态
            }

        ]
    }
*/
    public function regrecomlists(){
        $follow = 0;
        $p = $this->_verifyParam('p', 1 ,'int', 'GET');//内部用$_REQUEST['q']
        $limit = $this->_verifyParam('limit', 50 ,'int', 'GET');
        $lists = $cond = array();
        $res = D('RegRecommendExpert','admin')->getList($cond, $limit);
        $expertUids = array();
        foreach ($res['data'] as $v) {
            $uid = intval($v['uid']);
            $todaydynamic = D('WeiboReadMg', 'weibo')->countTodayWeiboByUid($uid);
            $data = array(
                'uid'  =>  $uid,
                'uname' => getUserName($uid),
                'comment'  => $v['desc'],
                'face' => getUserFace($uid),
                'icon_experts' => model('UserGroup')->getUserGroupIconSrc($uid),
                'todaydynamic'=> intval($todaydynamic),
            );
            $lists[] = $data;
            $expertUids[] = $uid;
        }

        /*
        * 过滤规则 
        *  1、显示过去24小时中有发新动态的专家
        *  2、推荐列表人数限制在2-15，若小于2(不含)则全部显示 
        */
        unset($uid);
        $wbUids = $this->_getIn24HoursWbByUids($expertUids);
        if(count($wbUids) >= 2){
            //过滤下expertUids
            foreach ($lists as $k => $v) {
                if(!in_array($v['uid'],$wbUids)){ //不符合规则1则干掉
                    unset($lists[$k]);
                    continue;
                }
            }
        }
        $lists = array_values($lists);
        //最多15人
        $max = 15;
        if(count($lists)>$max){
            $lists = array_slice($lists, 0,$max);
        }
        unset($data,$res);
        outputAdaptor(C('STATUS_CODE.SUCCESS'),$lists);
    }

    //获取用户24小时内微博(一个小时缓存)
    private function _getIn24HoursWbByUids($uids){
        $uidStr = implode(',',$uids);
        $cacheKey= md5($uidStr);
        $data = S($cacheKey);
        if(!$data){
            $wbUids = array();
            $startTime = time() - 86400;
            $resWb = D('WeiboReadMg','weibo')->getWbByUidsAndTime($uids, $startTime);
            foreach ($resWb as $k => $v) {
                $uid = intval($v['uid']); 
                if(isset($wbUids[$uid])){
                    continue;
                }
                $wbUids[$uid] = $uid;           
            }
            $wbUids = array_values($wbUids); //符合规则1的用户
            $data = $wbUids;
            //S($cacheKey,$data,3600);//一个小时缓存
        }
        return $data ? $data : array();
    }

/****

## 关注多名推荐专家

###接口描述
    这个接口用来关注多名推荐专家

###接口地址
    
    POST /index.php?app=landingpage&mod=landingPage&act=regmultifollow
    
###参数

- uids : string 以','作分割传多个uid, eg: 2121898,2638430

###正确返回

    {
        "code":0,
        "message":"成功",
        "data":""
    }

*/
    public function regmultifollow(){
        $uidStr = $this->_verifyParam('uids', NULL ,'string', 'POST');
        if(!$this->mid) {
            outputAdaptor(C('STATUS_CODE.SESSION_TIMEOUT'), '请登录');
        }
        //uids处理
        $uids = explode(',', trim($uidStr));
        foreach ($uids as $k => $v) {
            $uids[$k] = intval($v);
        }
        $modelFollow = D('UserFollowSv', 'user');
        //判断用户是否已经关注过用户
        // $count = $modelFollow->getUserFollowCounter($this->mid);
        // if($count>2){
        //     outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'您已经关注过用户~');
        // }
        //随机关注两个
        $limit = 100;
        //$cond = array('follow'=>1);
        $cond = array();
        $res = D('RegRecommendExpert','admin')->getList($cond, $limit);
        $flag = 0;
        $expertUids = array();
        if(!empty($res['data'])){
            $data = $res['data'];
            foreach ($res['data'] as $v) {
                $expertUids[] = intval($v['uid']);
            }
        }
        $tmp = array_diff($uids, $expertUids); //计算数组差集 ，array1中有但array2中没有的值
        if(!empty($tmp)){//差集合不为空，则不是子集
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'提交参数有误');
        }   
        foreach ($uids as $v) {
            $uid = intval($v);
            $modelFollow->doFollow($this->mid, $uid, 0,0,0);//默认关注
            $flag = 1;
        }

        if($flag){
            outputAdaptor(C('STATUS_CODE.SUCCESS'),'');
        }else{
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'操作失败~');
        }
    }
}
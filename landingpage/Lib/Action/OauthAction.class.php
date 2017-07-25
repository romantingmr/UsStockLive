<?php
class OauthAction extends SmartyAction {
    //index.php?app=landingpage&mod=oauth&act=qq
    private function _getOauthObj($type){
        switch ($type) {
            case 'qq':
                $client_id = '101284040';
                $client_secret = '83cfb1f0440dff95e8da51d0b62f0fb8';
                $redirect_uri = 'http://'.C('SF_SITE_HOST').'/oauth/callback';
                require_cache(VENDOR_PATH . 'libs/opUser/oauth/QQ.class.php');
                $Oauth  = new QQ($client_id,$client_secret,$redirect_uri);
                break;
        }
        return $Oauth;
       
    }
    private function _getOauthMObj($type){
        switch ($type) {
            case 'qq':
                $OauthM = D('OauthQQ','landingpage');
                break;
        }
        return $OauthM;
    }
    public function qq() {
        //$reqfrom = 0;
        $reqfrom = $this->_verifyParam('reqfrom', 0, 'int', 'GET');
        $action = 'login';
        //$httpReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U('home/User/index');
        //重定向地址
        $redirect = !empty($_GET['redirect']) ? $_GET['redirect'] : NULL;
        //用户是否登录
        $uid = $this->mid;
        if($uid){
            //已登录用户跳转
            if($reqfrom == 1){ //W3G
                redirect('/im/'); //W3G个人页
            }else{
               U('home', '', true);//PC个人页
            }
        }    
        $param = array();
        switch ($reqfrom) {
            case 0: //pc
                $param['reqfrom'] = $reqfrom;
                $param['action'] = "login";
                if(isset($redirect))
                    $param['redirect'] = $redirect;
                break;
            case 1: //wap
                $param['reqfrom'] = $reqfrom;
                $param['action'] = "login";
                if(isset($redirect))
                    $param['redirect'] = $redirect;
                break;
            default:
                die("操作有误..");
                break;
        }
        //清楚遗留的cookie
        $Oauth  = $this->_getOauthObj('qq');
        redirect($Oauth->authorizeURI($param));
    }
    
    /*
    第三方回调地址
    授权完，如果用户已经绑定脉搏帐号，则后端帮用户登录，
    如果未绑定脉搏帐号，则回调到/oauth/register，页面需要的信息以url参数的形式赋值给前端   
    */
    public function callback(){
        $state = json_decode(stripcslashes($_GET['state']),true);
        $code  = $_GET['code'];
        $action = $state["action"];
        $redirect = $state['redirect'];
        $reqfrom = $state['reqfrom'] ? intval($state['reqfrom']): 0;
        $platformId = 0;
        switch ($state['type']) {
            case 'qq':
                $Oauth  = $this->_getOauthObj('qq');
                $OauthM = $this->_getOauthMObj('qq');
                $platformId = 3;
                break;
            default:
                die("操作有误");
                break;
        }
        $result = $Oauth->getAccessToken($code);
        if(!isset($result['keyId'])) {
            die("认证失败");
        }
        if($action == "login") { //登陆模块
            $o_uid = $result['keyId'];
            $uid   = $OauthM->getUid("$o_uid",$state['type']);
            if(!$uid) {
                //获取第三方用户信息
                $Oauth->setAccessToken($result['accessToken'],$result['keyId']);
                $userInfo = $Oauth->getUserInfo();
                //未注册
                $OauthData = array(
                    "gender" => $userInfo['gender'] == '男' ? 1 : 0,
                    "nickname" => $userInfo['name'],
                    "keyId" => $result['keyId'],
                    "accessToken" => $result['accessToken'],
                    "expireDate" => $result['expireDate'],
                );
                setcookie("imb_oauth",json_encode($OauthData),time()+3600,"/");
                $param = array(
                    'name' => $userInfo['name'],
                    //'gender' => $userInfo['gender'],
                    'avatar_large' => $userInfo['avatar_large'],
                    'otype'=>$state['type'],
                );
                if(!empty($redirect))
                    $param['redirect'] = $redirect;//跳转地址

                if($reqfrom == 1){ //W3G
                    $redirect_url = 'http://'.C('SF_SITE_HOST').'/im/activity/3rdLogin';
                }else{
                    $redirect_url = 'http://'.C('SF_SITE_HOST').'/oauth/login';
                }

                $params = http_build_query($param);
                redirect($redirect_url.'?'.$params);
            }
            //存在UID
            //确认user表存不存在用户，不存在则存在脏数据，给报错信息
            $userInfo = M('user')->where('uid=' . $uid)->find();
            //获取帐号信息
            if (!$userInfo) {
                if(C('SAVE_EXCEPTION_LOG')){
                    //异常点加日志
                    Log::write($_SERVER['LOCAL_ADDR'].'[QQLOGIN-EXCEPTION]'.APP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME.'?'.$_SERVER['QUERY_STRING'].' > '.' uid : '. $uid .' ,openID: '. $result['keyId']);
                }
                $this->error('登录异常，请联系客服QQ:800165163。错误码: '.$uid); 
            }
            //帮用户登录
            $this->_login($uid,$platformId);
            //【补丁】 回填数据，修正历史原因导致的数据问题 start 
            service('OpUser')->callbackInsert($platformId,$uid,$result['keyId'],$result['accessToken'],'',$result['expireDate']);
            //【补丁】 end
            !empty($redirect) ? redirect($redirect) : U('home', '', true);
        } 

    }

    public function login(){
        //请求注册页判断
        $data['mode'] = isset($_GET['mode']) && in_array($_GET['mode'], array('bind','reg')) ? $_GET['mode'] : 'reg';
        //注册绑定页
        $templatePath = "user/page/landing/qqbind";
        //引导到APP注册页
        //$templatePath = "user/page/landing/qqyd";

        $data = $this->fillWithTestData($templatePath);

        $this->smarty()->assign($data);
        $this->smarty()->display($templatePath.".tpl");
    }
    /*
    一键注册接口
        POST /index.php?app=landingpage&mod=oauth&act=register
        参数：
        - type : int , //2:sina ,3:qq ,4:weixin
        - nickname : string , //用户昵称
    */
    public function register(){
        $type = $this->_verifyParam('type', NULL, 'int', 'POST');
        $nickname = $this->_verifyParam('nickname', NULL, 'string', 'POST');
        //授权失败
        if(!isset($_COOKIE['imb_oauth'])){
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'授权失败');
        }
        //判断类型
        switch ($type) {
            case 3:
                $Oauth  = $this->_getOauthObj('qq');
                $OauthM = $this->_getOauthMObj('qq');
                break;
            default:
                outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'错误的第三方类型');
                break;
        }
        //检测授权
        $oauthData = json_decode(stripcslashes($_COOKIE['imb_oauth']),true);
        if(!isset($oauthData['accessToken'])) {
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'授权失败~');
        }

        //再调接口获取第三方用户信息验证一次，第三方信息，防止cookie被随意伪造
        $Oauth->setAccessToken($oauthData['accessToken'],$oauthData['keyId']);
        $userInfo = $Oauth->getUserInfo();
        if(!$userInfo){
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'授权失败~~');
        }

        //开始注册
        //构造数据
        $platformId = $type; // 3
        if($type == 3){
            $appId = 101037320; // 旧的移动应用QQ才需要appId
        }else{
            $appId = 0;
        }
        $openId = $oauthData['keyId'];
        $accessToken = $oauthData['accessToken'];
        $expiresIn = $oauthData['expireDate'];
        $gender = intval($oauthData['gender']);
        $email = '';
        $mobile = '';
        $autoReg = 1;
        $opUserM = service('OpUser');
        //根据规则自动帮用户生成邮箱
        $email = $opUserM->buildEmail($platformId,$appId,$openId);
        list($res,$data,$code) = $opUserM ->transform($platformId, $appId, $openId,$accessToken,$expiresIn, $nickname,$email,$gender,$mobile,$autoReg);
        if($res){
            $userinfo = $opUserM->imaibo_getOrAddUser($platformId,$appId,$openId,$nickname,$accessToken,$expiresIn,$email,$gender,$mobile);
            if($userinfo){
                $this->_login($userinfo['open_id'],$type); //platformId 变量在transform阶段会改变
                //U('home', '', true);
                //写个新注册的COOKIE标识(用户弹窗显示推荐专家）
                setcookie('newreg', 1, time() + 3600*24);//一天
                outputAdaptor(C('STATUS_CODE.SUCCESS'),'登录成功');
            }
            outputAdaptor(C('STATUS_CODE.SUCCESS'),'用户信息不正确');
        }else{
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),$data);
        }
    }
    /*
    绑定脉搏帐号接口
        POST /index.php?app=landingpage&mod=oauth&act=bind
        - type : int , //2:sina ,3:qq ,4:weixin
        - email  邮箱
        - password 密码
    */
    public function bind(){
        $type = $this->_verifyParam('type', NULL, 'int', 'POST');
        $email = $this->_verifyParam('email', NULL, 'string', 'POST');
        $password = $this->_verifyParam('password', NULL, 'string', 'POST');
        //授权失败
        if(!isset($_COOKIE['imb_oauth'])){
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'授权失败');
        }
        //判断类型
        switch ($type) {
            case 3:          
                $Oauth  = $this->_getOauthObj('qq');
                $OauthM = $this->_getOauthMObj('qq');
                break;
            default:
                die("错误的第三方类型");
                break;
        }
        //检测授权
        $oauthData = json_decode(stripcslashes($_COOKIE['imb_oauth']),true);
        if(!isset($oauthData['accessToken'])) {
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),'授权失败');
        }
        //构造数据
        $platformId = $type;
        if($type == 3){
            $appId = 101037320; // 旧的移动应用QQ才需要appId
        }else{
            $appId = 0;
        }
        $appSecret = '';
        $openId = $oauthData['keyId'];
        $accessToken = $oauthData['accessToken'];
        $expiresIn = $oauthData['expireDate'];
        $gender = intval($oauthData['gender']);
        $nickname = intval($oauthData['nickname']);
        //$gender = 1;
        $mobile = '';
        $opUserM = service('OpUser');
        $opUserM->webQQAppID = 101284040;// bindImaiboUser内有检测accessToken合法性
        list($bindRes,$data,$code) = $opUserM->bindImaiboUser($platformId,$appId,$appSecret,$openId,$accessToken,$email,$password);
        if($bindRes){
            $appId = 0;
            //根据规则自动帮用户生成邮箱
            $email = $opUserM->buildEmail($platformId,$appId,$openId);
            list($res,$data,$code) = $opUserM ->transform($platformId, $appId, $openId,$accessToken,$expiresIn, $nickname,$email,$gender,$mobile,0);
            if($res){
                $userinfo = $opUserM->imaibo_getOrAddUser($platformId,$appId,$openId,$nickname,$accessToken,$expiresIn,$email,$gender,$mobile);
                if($userinfo){
                    $this->_login($userinfo['open_id'],$type); //platformId 变量在transform阶段会改变
                    //U('home', '', true);
                    outputAdaptor(C('STATUS_CODE.SUCCESS'),'登录成功');
                }
                outputAdaptor(C('STATUS_CODE.SUCCESS'),'用户信息不正确');
            }else{
                outputAdaptor(C('STATUS_CODE.OPER_FAIL'),$data);
            }
        }else{
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'),$data);
        }
    }
    private function _login($uid,$platformId=0){
        $uid = intval($uid);
        $_SESSION['mid'] = $uid;
        $this->mid = $uid;
        $this->uid = $uid;          
        //$userinfo = D('User', 'home')->getUserByIdentifier($this->mid);
        $userinfo = M('user')->where('uid=' . $uid)->find();
        $_SESSION['uname'] = $userinfo['uname'];            
        $_SESSION['uid'] = $uid;
        $paoid = '1-0-'.$uid;
        //$expire =  3600 * 24 * 365 ;
        $expire =  3600 * 24 * 14;
        setcookie('accessToken',$userinfo['password'], time() + $expire,'/');
        setcookie('paoid',$paoid, time()+ $expire,'/');
        // 登录积分
        X('Credit')->setUserCredit($uid, 'user_login');
        //登录记录
        //service('Passport')->recordLogin($uid);
        service('OpUser')->recordLogin($uid,$platformId);
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
<?php

/**
 * Class WeiBoLiveA50Action
 * @name 美股直播
 * @author romantingmr
 * @version 20170620
 */
class WeiBoLiveA50Action extends APIAction
{
    private static $modelInfo;
    private $etoroUrl;
    private $etUser = [];
    private $topicsUrl;
    private $linkId;
    private $etImgUrl = 'https://etoro-cdn.etorostatic.com/market-avatars';
    private static $urlArr = [
        1=> '/portfolio/history',
        2=> '/portfolio/card-trades',
        3=> '/people/{username}/chart',
        4=> '/settings/account'
    ];
    private static $usStock = [];

    public function _initialize()
    {
        self::$modelInfo = D('ImLastVersion','landingpage');
        $this->etoroUrl = C('ETORO_DOMAIN');
        $this->topicsUrl = C('TOPICS_URL');
        //默认推广ID
        $this->linkId = D('YdtEtoroSv','ydt')->getLinkId($this->uid);
        if($this->mid) {
            //不处理@todo20170703
            $this->etUser = self::$modelInfo->getEtoroUserInfo($this->mid);
        }
        array_push(self::$urlArr,'http://'.C('SF_SITE_HOST').'/im/activity/A50/?linkId='.$this->linkId.'&ad=anv&source=1');
        array_push(self::$urlArr,$this->topicsUrl);
        array_push(self::$urlArr,'http://'.C('SF_SITE_HOST').'/im/activity/07.20-A50kh/');
        self::$usStock = [
            'China50' => ['name' => 'A50','symbol'=>'China50'],
            'GOLD'=>['name' => '黄金','symbol'=>'GOLD'],
            'OIL'=>['name' => '原油','symbol'=>'OIL','logo'=>$this->etImgUrl.'/oil/70x70.png'],
            'BTC'=>['name' => '比特币','symbol'=>'BTC'],
            'AAPL'=>['name' => '苹果','symbol'=>'AAPL'],
            'TSLA'=>['name' => '特斯拉','symbol'=>'TSLA'],
            'MSFT'=>['name' => '微软','symbol'=>'MSFT'],
            'GOOG'=>['name' => '谷歌','symbol'=>'GOOG'],
            'BABA'=>['name' => '阿里巴巴','symbol'=>'BABA'],
            'BIDU'=>['name' => '百度','symbol'=>'BIDU']
        ];
        parent::Init();
    }
/****
## 发布美股直播信息

###接口描述
    这是发布美股直播信息接口

###接口地址
    
   GET /index.php?app=landingpage&mod=WeiBoLiveA50&act=publishFeed
    
###参数

    - content   : 微博内容
    - is_weibo_vip : 1:是,0:否
    -publish_type : 1:有附件，0：没有

###正确返回
    {
        "code": 0,
        "message": "成功",
        "data": [
        ]
    }
*/
    public function publishFeed()
    {
        // 微博发布锁
        $modelUserPublishLock = D('UserPublishLock', 'weibo');
        $ckPublishLock = $modelUserPublishLock->ckPublishLock($this->mid);
        if(!$ckPublishLock) {
            outputAdaptor(C('STATUS_CODE.TIME_TIMES_LIMIT'), '有话慢慢说，别着急~');
        }
        $data['content'] = t2($_POST['content']);
        $isHuman         = isset($_COOKIE["IsHuman"]) ? true : false;
        $publishType = (int) $_POST['publish_type'];
        if(empty($data['content'])) {
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '发布内容不能为空');
        }
        $modelWeibo = D('Weibo', 'weibo');
        if ($modelWeibo->isPublishingFrequently($this->mid)) {
            //1分钟内发布3条以上微博,要求用户输入验证码
            if (!$isHuman) {
                outputAdaptor(C('STATUS_CODE.TIME_TIMES_LIMIT'), '1分钟内发布3条以上微博');
            }
        }

        if ($modelWeibo->isRepeatedWeibo($this->mid, $data['content'])) {
            //10分钟内不能发重复内容的微博
            outputAdaptor(C('STATUS_CODE.REPEAT_CONTENT'), '发布重复内容');
        }
        $data['is_weibo_vip'] = 0;
        // weibo vip
        if((int) $_POST['is_weibo_vip'] === 1) {
            $data['is_weibo_vip'] = 1;
        }

        if(isset($_POST['publish_type_data']) && $publishType === 1) {
            $publishTypeData = array_map('t', $_POST['publish_type_data']);//附件
        }

        $liveUser = D('LiveRecomHost','live')->getRecomHost($this->mid, '*');
        $liveIdArr = D('Live','live')->getLive(array('uid'=>$this->mid));
        if ($liveUser['recom_status'] == 1 && !empty($liveIdArr)) {
            $data['liveId'] = $liveIdArr['id'];
            $data['liveUid'] = $this->mid;
            $weiBoId = D('LiveCommentSv', 'live')->liveComment($this->mid, $data, $publishType, $publishTypeData);
        } else {
            $weiBoId = $modelWeibo->publish($this->mid, $data, 0, $publishType, $publishTypeData);
        }

        if ($weiBoId) {
            // 发布成功后，检测后台是否开启了自动举报功能
            $weibo_option = model('Xdata')->lget('weibo');
            if ($weibo_option['openAutoDenounce']) {
                if (checkKeyWord($data['content'])) {
                    model('Denounce')->autoDenounce($weiBoId, $this->mid, $data['content']);
                    outputAdaptor(C('STATUS_CODE.CONTAINS_FORBID_WORD'), '含有敏感词');
                }
            }

            //添加积分
            X('Credit')->setUserCredit($this->mid, 'add_weibo');
            //判断非VIP微博是否提及组合20160119 by Ken
            if ((int)$_POST['is_weibo_vip'] !== 1) {
                preg_match_all("/\(ZH([0-9]{6,})\)/iu", $data['content'], $matches);
                if ($matches[1]) {
                    $portfolioIds = array();
                    $epamService = service('Epam');
                    $portfolioWeiboM = D('PortfolioWeiboRelation', 'csi');
                    foreach ($matches[1] as $code) {
                        $portfolioId = intval($code);
                        if ($epamService->requestPortfolioInfo($portfolioId, true)) {
                            $portfolioWeiboM->addData($portfolioId, $weiBoId, $this->mid);
                        }
                    }
                }
            }
            //数据采集优先股票
            $stocks = getStocksFromContent($data['content'], 999999);
            foreach ($stocks as $stock) {
                D('StockStatRds', 'investment')->updateStockPrior($stock['StockCode']);
            }
            outputAdaptor(C('STATUS_CODE.SUCCESS'));
        }else {
            outputAdaptor(C('STATUS_CODE.OPER_FAIL'), '操作失败');
        }
    }

/****
## 美股直播列表

###接口描述
   这是美股直播列表模块信息接口

###接口地址

   GET /index.php?app=landingpage&mod=WeiBoLiveA50&act=getLiveWeiBoList

###参数
   uid : int
   page : int


###正确返回
    {
    "code": 0,
    "message": "成功",
    "data": {[
        "user_info": [ //用户基本信息
        {
            "is_online": 0, //是否在线，1：是，0：否
            "space": "http://t-www.imaibo.net/space/31696", //空间跳转
            "face": "http://t-img.imaibo.net/data/uploads/avatar/31696/big.jpg?v=20151217003", //头像
            "uname": "股坛盟主", //昵称
            "introduce": "专业投资人，", //个人简介
            "icon_sign": [ //身份图标
                {
                    "img": "http://t-www.imaibo.net/public/themes/Maibo/common_v1/images/user/group/g2.png?v=20151217003",
                    "url": "http://www.imaibo.net/master",
                    "title": "专家认证-投资脉搏直播专家"
                }
            ],
            "tag_name": [], //标签
            "more_tag_name": [], //更多标签
            "watch": 1 //1:已关注,0:未关注
        }
        ],or
        'user_info' : [{
            live_host:1,
            tag_name:[] //标签
            more_tag_name": [], //更多标签
         }],
        "weibo_info":[
        {
            "weibo_id": "4313058",
            "uid": "31696",
            "content": "关于黄金的未来走势，",
            "ctime": "1473177985",
            "from": "3",
            "comment": "112",  //评论
            "transpond_id": "0",
            "transpond": "3",
            "type": "0",
            "type_data": [
                {
                    "thumburl": "http://t-img.imaibo.net/data/uploads/2016/0906/23/small_57cee3eaeb5e3.jpg",
                    "thumbmiddleurl": "http://t-img.imaibo.net/data/uploads/2016/0906/23/middle_57cee3eaeb5e3.jpg",
                    "picurl": "http://t-img.imaibo.net/data/uploads/2016/0906/23/57cee3eaeb5e3.jpg",
                    "attach_id": "http://t-img.imaibo.net/data/uploads/456214"
                }
            ],
            "from_data": "",
            "trans_comment_id": "0",
            "isdel": "0",
            "dig": "12",//点赞
	        "is_dig" : 1 //是否点赞 1:是,0：否
            "showMoreBtn" : 0
            "isRecommend": "0",
            "isRecommendByEditor": "0",
            "ip": "124.127.151.194",
            "uname": "股坛盟主",
            "face": "http://t-img.imaibo.net/data/uploads/avatar/31696/middle.jpg?v=20151217003",
            "is_favorited": false,
            "expend": "",
            "publishTime": "2016-09-07 00:06" //发布时间
        }
        ]
   }
 */
    public function getLiveWeiBoList()
    {
        $mid = $this->mid ? intval($this->mid) : 0;
        $uid = $_GET['uid'] ? intval($_GET['uid']) : 0;
        $page = $_GET['page'] ? intval($_GET['page']) : 1;
        if($uid) {
            //@todo 20170710新规则，keyword为空
            $keyWord = [];
            $modelA50Rel = D('A50LiveHostTagRelation', 'landingpage');
            $tagName = $this->getLiveHostTag($uid, $modelA50Rel);
            if ($tagName) {
                foreach ($tagName as $rw) {
                    $temp[] = $rw['tag_name'];
                    $formatName = '#' . $rw['tag_name'] . '#';
                    $afterTagName[] = $formatName;
                }
            }
            //@todo 20170706 新规则
            if($temp){
                $notIn = ['not in',$temp];
                $cond = ['t.isdel' => 1, 'c.isdel' => 1,'c.cid'=>7,'t.tag_name'=>$notIn];
            }else{
                $cond = ['t.isdel' => 1, 'c.isdel' => 1,'c.cid'=>7];
            }
            $result = D('TagsSys', 'home')->getCateJoinTagList($cond, "c.cid,t.tag_name as name");
            $moreTagName = getSubByKey($result,'name');
            if($moreTagName){
                foreach($moreTagName as &$value){
                    $value = '#'.$value.'#';
                }
            }
            if ($uid != $mid) {//取出播主基本信息
                //在线状态
                $isOnline = model('OnlineUserRds')->isOnline($uid);
                //专家基本信息
                $userInfo = getUserInfo($uid);
                $watch = D('UserFollowMg', 'user')->getFollowId($mid, $uid);
                $temp = [
                    'is_online' => $isOnline ? 1 : 0,
                    'space' => U('home/Space/index', ['uid' => $uid]),
                    'face' => getUserFace($uid, 'big'),
                    'uname' => $userInfo['uname'],
                    'introduce' => htmlspecialchars_decode($userInfo['comment']),
                    'icon_sign' => model('UserGroup')->getUserGroupIconSrc($uid,C('USER_GROUP_ID')),
                    'tag_name' => $afterTagName,
		            'more_tag_name' => $moreTagName,
                    'watch' => $watch ? 1 : 0
                ];
                $data['user_info'] = $temp;
                unset($temp);
            } else {
                $data['user_info'] = [
                    'live_host' => 1,
                    'tag_name' => $afterTagName,
		            'more_tag_name' => $moreTagName
                ];
            }

            //列表
            $weiBoInfo = self::$modelInfo->hotInvestmentChance($keyWord, $uid, $mid, 0, $page,'US_STOCK');
            if($weiBoInfo){
                $modelWeiboDigSv = D("WeiboDigSv", "weibo");
                foreach($weiBoInfo as &$row){
                    $hasDig = $modelWeiboDigSv->getWeiboDigCache($row['weibo_id'], $this->mid);
                    $row['is_dig'] = $hasDig ? 1 : 0;
                    $row['showMoreBtn'] = 0;
                    if($row['expend']){
                        $row['showMoreBtn'] = 0;
                    }
                    $row['showComment'] = '';
                    $row['commentList'] = '';
                    $row['commentContent'] = '';
                    $row['reply_comment_id'] = 0;
                    //$row['type_data'] = 'a:2:{i:0;a:4:{s:8:"thumburl";s:36:"2016/0907/02/small_57cf0b2159ef6.jpg";s:14:"thumbmiddleurl";s:37:"2016/0907/02/middle_57cf0b2159ef6.jpg";s:6:"picurl";s:30:"2016/0907/02/57cf0b2159ef6.jpg";s:9:"attach_id";i:456249;}i:1;a:4:{s:8:"thumburl";s:36:"2016/0907/02/small_57cf0b2159ef6.jpg";s:14:"thumbmiddleurl";s:37:"2016/0907/02/middle_57cf0b2159ef6.jpg";s:6:"picurl";s:30:"2016/0907/02/57cf0b2159ef6.jpg";s:9:"attach_id";i:456249;}}';
                    //图片附件
                    if($row['type_data']){
                        $typeData = unserialize($row['type_data']);
                        foreach($typeData as $k=>$rw) {
                            if (is_array($rw)) {
                                $temp = [];
                                $temp = [
                                    'thumburl' => 'http://img.imaibo.net/data/uploads/' . $rw['thumburl'],
                                    'thumbmiddleurl' => 'http://img.imaibo.net/data/uploads/' . $rw['thumbmiddleurl'],
                                    'picurl' => 'http://img.imaibo.net/data/uploads/' . $rw['picurl'],
                                    'attach_id' => $typeData['attach_id'],
                                    'openMore' => 0
                                ];
                                $array[] = $temp;
                            }
                        }
                        if(empty($array)){
                            $temp = [
                                'thumburl' => 'http://img.imaibo.net/data/uploads/'.$typeData['thumburl'],
                                'thumbmiddleurl' => 'http://img.imaibo.net/data/uploads/'.$typeData['thumbmiddleurl'],
                                'picurl' => 'http://img.imaibo.net/data/uploads/'.$typeData['picurl'],
                                'attach_id' => $typeData['attach_id'],
                                'openMore' => 0
                            ];
                            $array = $temp;
                        }
                        $row['type_data'] = $array;
                        unset($array,$temp);
                    }else{
                        unset($row['type_data']);
                    }
                }
            }
            $data['weibo_info'] = $weiBoInfo;
            //浏览的最后id
            if ($page === 1)
                D('UserUsStockWbRds', 'landingpage')->setUserWbLastId($uid, intval($weiBoInfo[0]['weibo_id']));
            $weiBoInfo = array();
        }
        outputAdaptor(C('STATUS_CODE.SUCCESS'),$data);
    }

/****
##美股直播最新数量

###接口描述
    显示已经新直播数量

###接口地址

    GET /index.php?app=landingpage&mod=WeiBoLiveA50&act=getLastWbTotal

###参数

    - uid: int

###正确返回
    {
        "code": 0,
        "message": "成功",
        "data":{
            "num": 1, //新直播数量
        }
    }
*/
    public function getLastWbTotal()
    {
        $uid = $_GET['uid'] ? intval($_GET['uid']) : 0;
        if($uid){
            $modelA50Rel = D('A50LiveHostTagRelation', 'landingpage');
            //标签
            /*$tagName = $this->getLiveHostTag($uid,$modelA50Rel);
            $content = getSubByKey($tagName,'tag_name');*/
            //@todo 20170713 关键字为空
            $content = [];
            $lastId = D('UserUsStockWbRds','landingpage')->getUserWbLastId($uid);
            $total = self::$modelInfo->getLastWbTotal($content,$uid,$lastId);
            outputAdaptor(C('STATUS_CODE.SUCCESS'),array('num'=>$total));
        }else{
            outputAdaptor(C('STATUS_CODE.SUCCESS'),array('num'=>0));
        }
    }

    /**
     * @name 获取播主标签
     * @param $uid
     * @param $modelA50Rel
     * @return bool
     */
    private function getLiveHostTag($uid,&$modelA50Rel)
    {
        if(empty($uid)) return false;
        //标签
        $aCond['us.uid'] = $uid;
        $aFields = 'info.tag_name';
        $tagName = $modelA50Rel->getData($aCond,$aFields);
        return $tagName;
    }

/****
##美股直播点赞

###接口描述
    美股直播点赞接口

###接口地址

GET /index.php?app=landingpage&mod=WeiBoLiveA50&act=doDig

###参数

    - weibo_id : int

###正确返回
    {
        "code": 0,
        "message": "成功",
        "data": {
            digId : 14457517
            userDigCount: 219272//用户点赞数
            weiboDigCount:7//微博点赞数
        }
    }
*/
    public function doDig()
    {
        $className = A('WeiboDig','weibo');
        $className->doDig();
    }

/****
##美股直播评论列表

###接口描述
    美股直播评论列表接口

###接口地址

    GET /index.php?app=landingpage&mod=WeiBoLiveA50&act=commentList

###参数

    - weibo_id : int

###正确返回
    {
        "code": 0,
        "message": "成功",
        "data":[
            {
                "uid": 4295657,//评论人uid
                "uname": "Bande",
                "space": "http://t-www.imaibo.net/space/4295657",
                "face": "http://t-www.imaibo.net/index.php?app=user&mod=Myavatar&act=avatar&size=big&tt=1498811992",
                "reply_comment_id": 4157948, //回复 评论id
                "rel_weibo_id": 0,
                "reply_uid": 3018949, //@reply_uid
                "weibo_id": 4313101,
                "ctime": "4分钟前",
                "comment_id": 4158154,
                "comment": {
                    "txt": "回复<a href='http://t-www.imaibo.net/space/2092387' rel='face' uid='2092387' class='null' target='_blank'>@雪天的心情</a>：33333333333  ",
                    "longText": "回复<a href='http://t-www.imaibo.net/space/2092387' rel='face' uid='2092387' class='null' target='_blank'>@雪天的心情</a>：33333333333  ",//评论内容
                    "more": "",
                    "script": null,
                    "txtLen": 15
                },
                "from": 0 //pc默认0
            },
        ]
    }
*/
    public function commentList()
    {
        if(empty($this->mid))
            outputAdaptor(C('STATUS_CODE.SESSION_TIMEOUT'),'会话登录超时，请重新登录');
        $weiBoId = intval($_GET['weibo_id']);
        if(empty($weiBoId))
            outputAdaptor(C('STATUS_CODE.SUCCESS'),'缺少参数');
        $list = array();
        $data['publisher']['face'] = getUserFace($this->mid,'big');
        $className = A('CommentFeedList','home');
        $result = $className->getCommentList($weiBoId);
        if($result){
            $i = 0;
            foreach($result['cmtHtml']['list'] as $key=>$rw){
                if($i >=5) break;
                $userInfo = getUserInfo($rw['uid']);
                $temp = [];
                $temp = [
                    'uid' => $rw['uid'],
                    'uname' => $userInfo['uname'],
                    'space' => U('home/Space/index', ['uid' => $rw['uid']]),
                    'face' => getUserFace($rw['uid'], 'big'),
                    'reply_comment_id' => $rw['reply_comment_id'],
                    'rel_weibo_id' => $rw['rel_weibo_id'],
                    'reply_uid' => $rw['reply_uid'],
                    'weibo_id' => $rw['weibo_id'],
                    'ctime' => friendlyDate($rw['ctime']),
                    'comment_id' => $rw['comment_id'],
                    'comment' => $rw['comment'],
                    'from' => $rw['from']
                ];
                $list[$i++] = $temp;
            }
            $data['lists'] = $list;
        }
        outputAdaptor(C('STATUS_CODE.SUCCESS'),$data);
    }

/****
##美股直播评论回复

    ###接口描述
评论回复接口

###接口地址

POST /index.php?app=landingpage&mod=WeiBoLiveA50&act=addComment

###参数

    - weibo_id : int
    - reply_comment_id: int commentList接口的reply_comment_id ，回复微博作者为空
    - conversation: int 可选 默认0
    - reply_default: String 可选 回复@雪天的心情：
    - from: int  //默认0:pc
    - comment_content: String 回复@雪天的心情：dddd

###正确返回
    {
        "code": 0,
        "message": "成功",
        "data":{}
    }
*/
    public function addComment()
    {
        $modelUserPublishLock = D('UserPublishLock', 'weibo');
        $ckPublishLock = $modelUserPublishLock->ckPublishLock($this->mid, 'cmt');
        if (!$ckPublishLock) {
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '有话慢慢说，别着急~');
        }

        if($this->mid === 0) {
            outputAdaptor(C('STATUS_CODE.SESSION_TIMEOUT'), '请登陆后再评论~');
        }

        $weiboId = (int) $_POST['weibo_id'];
        if ($weiboId === 0) {
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '缺少参数~');
        }

        $modelOperate = D('Operate', 'weibo');
        $miniInfo = $modelOperate->getOneLocation($weiboId);
        if ((int) $miniInfo['weibo_id'] === 0) {
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '评论微博不存在或已被删除~');
        }

        $weiboInfo = D('ItemOperate', 'consume')->buildWeiboPriceOne($miniInfo, $this->mid);
        if($weiboInfo['weibo_vip_show'] == 0) { // 没有购买的VIP微博
            outputAdaptor(C('STATUS_CODE.PERMISSION_DENIED'), '购买后才能评论~');
        }

        // 被拉黑的人不能说话喔
        $modelUserPrivacy = D('UserPrivacy', 'home');
        $isBlack = $modelUserPrivacy->isInBlackList($this->mid, $miniInfo['uid']);
        if ($isBlack) {
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '无权限评论，请联系小秘书了解情况~');
        }

        //个人权限设置
        $privacy = $modelUserPrivacy->getPrivacy($this->mid, $miniInfo['uid']);
        if ($privacy['weibo_comment'] === false) {
            $returnData['data'] = $post;
            $returnData['data']['comment'] = $weibo['comment'];
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '您无权评论，请联系小秘书了解情况~');
        }

        $content = h(t($_POST['comment_content']));
        if (empty($content)) {
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '评论内容不能为空~');
        }

        $modelCommentSv = D('CommentSv', 'weibo');
        //查看2分钟内，用户发的评论中，大于20个字的评论,若出现重复，弹出错误
        $ckConent = md5($content);
        if ($modelCommentSv->ckCmtContentLock($ckConent)) {
//            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '相同内容请间隔2分钟再进行发布哦~');
        }

        //评论内容
        $post['weibo_id'] = $weiboId;           //回复 微博的ID
        $post['uid'] = $this->mid;           //回复 微博的ID
        $post['reply_comment_id'] = (int) $_POST['reply_comment_id'];   //回复 评论的ID
        $post['weibo_uid'] = (int) $miniInfo['uid'];
        $post['content'] = html_entity_decode($content);         //回复内容
        $post['content'] = D('Weibo', 'weibo')->sanitizeContentNew($post['content']);
        $post['transpond'] = (int) $_POST['transpond'];            //是否同是发布一条微博
        $post['ctime'] = time();
        $post['from'] = (int) $_POST['from'];

        $commentId = $modelCommentSv->doaddcomment($this->mid, $post);
        if ($commentId) {

            //同时评论给原文作者
            $transpondWeiboId = (int)$_POST['transpond_weibo_id'];
            if ($transpondWeiboId > 0) {
                $post['reply_comment_id'] = 0;
                $transpondWeibo = $modelOperate->getOneLocation($transpondWeiboId);
                $post['weibo_id'] = (int)$transpondWeiboId;
                $post['weibo_uid'] = (int)$transpondWeibo['uid'];
                $post['reply_uid'] = (int)$transpondWeibo['uid'];
                $modelCommentSv->doaddcomment($this->mid, $post, true);
            }
            outputAdaptor(C('STATUS_CODE.SUCCESS'));
        }
        else{
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'), '评论失败~');
        }
    }

/****
## 美股直播专家信息卡片

###接口描述
     显示已经开通美股直播的播主信息

###接口地址

    GET /index.php?app=landingpage&mod=WeiBoLiveA50&act=getOpenA50ExpertList

###参数

    - 无:

###正确返回
    {
    "code": 0,
    "message": "成功",
    "data":  [
      {
        "uid" :  1966085
        "wb_count": 0, //今日直播
        "vs_count": 10, //今日人气
        "is_online": 1, //是否在线 1:是，0:否
        "uname": "Bande", //昵称
        "face": "http://t-img.imaibo.net/data/uploads/avatar/4295657/middle.jpg?v=20151217003",//头像
        "space": "http://t-www.imaibo.net/space/4295657", //space页
        "tag_name": [
            "#黄金#",
            "#原油#",
            "#美股#"
        ],
        "introduce": "专业投资人， 实战经验9年， 操作风格:稳准狠著称！&lt;br&gt;曾创出9万到千万的财富神话，被多家电视台专访。", //个人简介
        "icon_sign":  //身份认证图标
        {
                "img": "http://t-www.imaibo.net/public/themes/Maibo/common_v1/images/user/group/g2.png?v=20151217003",
                "url": "",
                "title": "美股直播"
        }
        ,
        "live_web": "http://t-www.imaibo.net/pbWeb/1966085" //访问页面
      }
    ]}
*/
    public function getOpenA50ExpertList()
    {
        //美股直播播主
        $gCond['user_group_id'] = C('USER_GROUP_ID');
        $gFields = 'uid';
        $uidData = model('UserGroupLink')->getUserGroupByMap($gCond,$gFields);
        $modelA50Rel = D('A50LiveHostTagRelation','landingpage');
        if($uidData){
            $initEnd = count($uidData) - 1;
            $initStart = 0;
            //@todo 暂不显示今日人气$visitData = $this->aggregateVisit();
            foreach($uidData as $rw){
                $temp = array();
                $afterTagName = array();
                //在线状态
                $isOnline = model('OnlineUserRds')->isOnline($rw['uid']);
                //专家基本信息
                $userInfo = getUserInfo($rw['uid']);
                //标签
                $tagName = $this->getLiveHostTag($rw['uid'],$modelA50Rel);
                if($tagName){
                    foreach($tagName as $row){
                        $formatName = '#'.$row['tag_name'].'#';
                        $afterTagName[] = $formatName;
                    }
                }
                $temp = array(
                    'uid' => $rw['uid'],
                    'wb_count' => D('WeiboReadMg', 'weibo')->countTodayWeiboByUid($rw['uid']),//今日直播数
                    'vs_count' => 0,//今日人气
                    'is_online' => $isOnline ? 1 : 0,
                    'uname' => $userInfo['uname'],
                    'face' => getUserFace($rw['uid'],'big'),
                    'space' => U('home/Space/index',array('uid'=>$rw['uid'])),
                    'tag_name' => $afterTagName,
                    'introduce' => $userInfo['comment'],
                    'icon_sign' => model('UserGroup')->getUserGroupIconSrc($rw['uid'],C('USER_GROUP_ID'))[0],
                    'live_web' => U('landingpage/ImLastVersion/publishWeb',array('uid'=>$rw['uid']))
                );
                if($isOnline){//在线的排在最前面
                    $data[$initStart++] = $temp;
                }else{
                    $data[$initEnd--] = $temp;
                }
                //匹配数据,找出对应vs_count
                /*foreach($visitData as $uid=>$uRw){
                    if($rw['uid'] == $uid){
                        $temp['vs_count'] = intval($uRw['mid_count']*50) + intval($uRw['ip_count']);
                        break;
                    }
                }*/
               // $data[] = $temp;
            }
        }
        outputAdaptor(C('STATUS_CODE.SUCCESS'),$data);
    }

/****
## 我的交易button信息

###接口描述
    我的交易，每个tag的信息

###接口地址

    GET /index.php?app=landingpage&mod=WeiBoLiveA50&act=getMyTrading

###参数

    - 无:

###正确返回
    {
        "code": 0,
        "message": "成功",
        "data": {
            "button": [
            {
                "id": 1,
                "title": "交易历史"
            },
            {
                "id": 2,
                "title": "我的持仓"
            },
            {
                "id": 3,
                "title": "收益走势"
            },
            {
                "id": 4,
                "title": "账户设置"
            }
            ],
            "user_info": {
                "user_name" : 'bande' //etoro用户名
                "yesterday_gain" : 168.8 //etoro昨日赚 默认0
            }
        }
    }
*/
    public function getMyTrading()
    {
        $linkArr = [
            /*['id'=>1,'title'=>'交易历史'],
            ['id' =>2,'title'=>'我的持仓'],
            ['id'=>3,'title'=>'收益走势'],
            ['id' =>4,'title'=>'账户设置'],*/
            ['id' =>5,'title'=>'交易'],
            ['id' =>6,'title'=>'晒单'],
            ['id' =>7,'title'=>'开户免费领取VIP直播']
        ];
        if($this->etUser['status'] == 1){
            $data = [
                'user_name' => $this->etUser['username'],
                'lastDayEarn' => $this->etUser['lastDayEarn']
            ];
        }else{
            $data = [
                'user_name' => '',
                'lastDayEarn' => ''
            ];
        }
        $outData = [
           'button' => $linkArr,
           'user_info' => $data
        ];
        outputAdaptor(C('STATUS_CODE.SUCCESS'),$outData);
    }

/****
## 我的交易，每个按钮请求不同的逻辑

###接口描述
    我的交易，每个按钮请求不同的逻辑

###接口地址

    GET /index.php?app=landingpage&mod=WeiBoLiveA50&act=isCanRedirect

###参数

    - id : button的id
    - uid : 专家Uid

###正确返回
    {
        "code": 0,
        "message": "成功",
        "data": {
            "url": "https://www.etoro.com.cn/portfolio/card-trades" //etoro地址
        }
    }OR
    {
        "code": 10100,
        "message": "会话超时请重新登录",
        "data": {}
    }OR
    {
        "code": 0,
        "message": "成功",
        "data": {
          "url": "http://t-www.imaibo.net/im/activity/A50/?linkId=116725&ad=anv" //开户地址
        }}
   
*/
    public function isCanRedirect()
    {
        $id = $_GET['id'] ? intval($_GET['id']) : 0;
        $uid = $_GET['uid'] ? intval($_GET['uid']) : 0;
        if(empty($id))
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'请求参数有错！');
        if(empty($this->mid))
            //outputAdaptor(C('STATUS_CODE.SESSION_TIMEOUT'),'请重新登录！');
        if(empty($this->etUser['status'])){

            outputAdaptor(C('STATUS_CODE.SUCCESS'),array('url'=>self::$urlArr[$id]));
        }
        if($id == 3){
            self::$urlArr[$id] = str_replace('{username}',$this->etUser['username'],self::$urlArr[$id]);
        }
        switch($id)
        {
            case 5:
                if($this->etUser['status']){
                    //app下载页
                    $url = 'http://'.C('SF_SITE_HOST').'/im/activity/ustocks/downApp.html';
                }else{
                    $disCookie = $_COOKIE['US_STOCK_OPEN_DISCOUNT_WEB'];
                    if($disCookie){
                        $url = $disCookie;
                    }else{
                        $url = 'http://'.C('SF_SITE_HOST').'/im/activity/A50/?linkId='.$this->linkId.'&ad=anv&source=1';
                    }
                }
                break;

            case 6 || 7:
                $url = self::$urlArr[$id];
                break;

            default :
                $url = $this->etoroUrl.self::$urlArr[$id];
                break;
        };
        outputAdaptor(C('STATUS_CODE.SUCCESS'),array('url'=>$url));
    }

/****
## 我的交易，获取etoro用户名

###接口描述
       获取etoro用户名

###接口地址

    POST /index.php?app=landingpage&mod=WeiBoLiveA50&act=getEtoroUserName

###参数

    - 无

###正确返回
    {
        "code": 0,
        "message": "成功",
        "data": {
             "space": "" //脉博用户空间
             "username" : 'bande'//用户名
        }
    }OR
    {
        "code": 10100,
        "message": "会话超时请重新登录",
        "data": {}
    }
*/
    public function getEtoroUserName()
    {
        if(empty(intval($this->mid))){
            outputAdaptor(C('STATUS_CODE.SESSION_TIMEOUT'),'请登录');
        }
        if($this->etUser['status']){
            $userInfo = [
                'space' => U('home/Space/index',['uid'=>$this->mid]),
                'username' => $this->etUser['username']
            ];
        }else{
            $userInfo = [
                'space' => U('home/Space/index',['uid'=>$this->mid]),
                'username' => ''
            ];
        }
        outputAdaptor(C('STATUS_CODE.SUCCESS'),$userInfo);
    }

/****
## 我的交易，获取etoro用户名

###接口描述
    获取etoro用户名

###接口地址

    POST /index.php?app=landingpage&mod=WeiBoLiveA50&act=bindYdtUser

###参数

    - 无

###正确返回
    {
        "code": 0,
        "message": "成功",
        "data": {
        "space": "" //脉博用户空间
        "username" : 'bande'//用户名
    }
*/
    public function bindYdtUser()
    {
        $linkId = t($_POST['linkId']);
        $user = t($_POST['user']);
        $from = $_POST['from'];
        $username = $_POST['username'];
        if(empty($linkId) || empty($username) || empty($user)){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'缺少参数');
        }
        $params = array('linkId'=>$linkId,'user'=>$user,'from'=>$from,'username'=>$username);
        $data = self::$modelInfo->bindYdtUser($params);
        if($data['statusCode'] == 200){
            outputAdaptor(C('STATUS_CODE.SUCCESS'));
        }
        outputAdaptor(C('STATUS_CODE.SUCCESS'),$data);
    }

/****
## 搜索股票

###接口描述
   搜索股票

###接口地址

   POST /index.php?app=landingpage&mod=WeiBoLiveA50&act=searchStock

###参数

   - name : string 股票名称或者英文缩写

###正确返回
    {
        "code": 0,
        "message": "成功",
        "data": [
            {
                "name": "新华富时中国A50指数", //名称
                "status": 0,//不在自选股
                "logo": "https://etoro-cdn.etorostatic.com/market-avatars/china50/150x150.png",//图标
                "symbol" : 'china50'//英文简写
            }
        ]
    }
*/
    public function searchStock()
    {
        $mid = $this->mid ? intval($this->mid) : 0;
        if(empty($mid))
            outputAdaptor(C('STATUS_CODE.SESSION_TIMEOUT'),'登录超时');
        $name = t($_POST['name']);
        if(empty($name))
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'缺少参数');
        $params = array('searchKey'=>$name);
        $data = self::$modelInfo->searchStock($params);
        if($data['statusCode'] == 200){
            $result = D('UserStockRel','landingpage')->getStockByUid($mid);
            $myStocks = getSubByKey($result,'name');
            foreach($data['stocks'] as $key=>$rw){
                $temp = [];
                $temp = [
                    'name' => $rw['name'],
                    'status' => isset($myStocks[$rw['name']]) ? 1 : 0,
                    'logo' => $rw['logo'],
                    'symbol' => $rw['symbol']
                ];
                $outPut[$key] = $temp;
            }
            outputAdaptor(C('STATUS_CODE.SUCCESS'),$outPut);
        }
    }

/****
## 热门股或者自选股列表

###接口描述
   热门股或者自选股列表

###接口地址

   GET /index.php?app=landingpage&mod=WeiBoLiveA50&act=hotOrMyStockList

###参数
   - 无

### 备注
    热门股列表：changePercent、price、redirect_uri不输出
    自选股列表：changePercent、price 为空输出redirect_uri
###正确返回
    {
        "code": 0,
        "message": "成功",
        "data": {
            "list": [
                {
                    "id": "3",
                    "name": "特斯拉",
                    "symbol": "TSLA",
                    "logo": "https://etoro-cdn.etorostatic.com/market-avatars/tsla/150x150.png",
                    "price": 340.9219,
                    "change_percent": -0.03
                },
            ],
                "type": 1  1:自选股，2:热门股
            }
    }
*/
    public function hotOrMyStockList()
    {
        $mid = $this->mid ? intval($this->mid) : 0;
        if (empty($mid))
            outputAdaptor(C('STATUS_CODE.SESSION_TIMEOUT'), '登录超时');
        $hotData = self::$modelInfo->hotStock();
        if ($hotData['list']) {//格式化成目标数组
            foreach ($hotData['list'] as $row) {
                foreach ($row['stocks'] as $rw) {
                    $data[] = $rw;
                }
            }
        }
        $fields = 'id,name,symbol';
        $sort = 'ctime DESC';
        $result = D('UserStockRel', 'landingpage')->getStockByUid($mid, $fields,$sort);
        if ($result) {
            foreach ($result as &$row) {
                foreach ($data as $col) {
                    if ($row['symbol'] == $col['symbol']) {
                        $row['logo'] = $col['logo'];
                        $row['price'] = $col['price'];
                        $row['change_percent'] = $col['changePercent'];
                        if (empty($col['price']) || empty($col['changePercent'])) {
                            $row['redirect_uri'] = 'https://www.etoro.com.cn/markets/'.$col['symbol'].'/chart';
                        }
                        break;
                    }else{//特殊处理原油
                        if($row['symbol'] == 'OIL'){
                            $row['logo'] = self::$usStock[$row['symbol']]['logo'];
                            $row['redirect_uri'] = 'https://www.etoro.com.cn/markets/'.$row['symbol'].'/chart';
                        }
                    }
                }
            }
            $output['list'] = $result;
            $output['type'] = 1;
            outputAdaptor(C('STATUS_CODE.SUCCESS'), $output);
        } else {
            foreach (self::$usStock as &$stock) {
                foreach ($data as $col) {
                    if ($stock['symbol'] == $col['symbol']) {
                        $stock['logo'] = $col['logo'];
                        $stock['type'] = 2;
                        break;
                    }
                }
            }
            $output['list'] = self::$usStock;
            $output['type'] = 2;
            outputAdaptor(C('STATUS_CODE.SUCCESS'), $output);
        }
    }

/****
## 添加自选股

###接口描述
   添加自选股

###接口地址

   POST /index.php?app=landingpage&mod=WeiBoLiveA50&act=addStocks

###参数
   - params: name : xxx,symbol:xxx

### 备注
  操作成功，返回结果code=0,其它视为失败
###正确返回
    {
        "code": 0||Other,
        "message": "成功"||"失败",
        "data": [
        ]
    }
*/
    public function addStocks()
    {
        $mid = $this->mid ? intval($this->mid) : 0;
        if(empty($mid))
            outputAdaptor(C('STATUS_CODE.SESSION_TIMEOUT'),'登录超时');
        $params = $_POST['params'];
        $postData = json_decode($params,true);
        if(empty($postData) || !is_array($postData)){
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'缺少参数');
        }
        foreach($postData as $rw){
            $addData[] = [
                'uid' => $mid,
                'name' => t($rw['name']),
                'symbol' => t($rw['symbol']),
                'ctime' => time()
            ];
        }
        $res = D('UserStockRel','landingpage')->addAllData($addData);
        if($res){
            outputAdaptor(C('STATUS_CODE.SUCCESS'));
        }else{
            outputAdaptor(C('STATUS_CODE.FAIL'),'添加失败');
        }
    }

/****
## 删除自选股

###接口描述
   删除自选股

###接口地址

   POST /index.php?app=landingpage&mod=WeiBoLiveA50&act=setStocksByDel

###参数
- id : String id

###正确返回
    {
        "code": 0||Other,
        "message": "成功"||"失败",
        "data": [
        ]
    }
*/
    public function setStocksByDel()
    {
        $mid = $this->mid ? intval($this->mid) : 0;
        if(empty($mid))
            outputAdaptor(C('STATUS_CODE.SESSION_TIMEOUT'),'登录超时');
        $id = intval($_POST['id']);
        if(empty($id))
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'缺少参数');
        $cond = ['id' => $id];
        $res = D('UserStockRel','landingpage')->delStocks($cond);
        if($res){
            outputAdaptor(C('STATUS_CODE.SUCCESS'));
        }else{
            outputAdaptor(C('STATUS_CODE.FAIL'),'删除失败');
        }
    }

/****
## 置顶自选股

###接口描述
   置顶自选股

###接口地址

    POST /index.php?app=landingpage&mod=WeiBoLiveA50&act=setStocksBySort

###参数
    - id : String id

###正确返回
    {
        "code": 0||Other,
        "message": "成功"||"失败",
        "data": [
        ]
    }
*/
    public function setStocksBySort()
    {
        $mid = $this->mid ? intval($this->mid) : 0;
        if(empty($mid))
            outputAdaptor(C('STATUS_CODE.SESSION_TIMEOUT'),'登录超时');
        $id = intval($_POST['id']);
        if(empty($id))
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'缺少参数');
        $res = D('UserStockRel','landingpage')->upStocks($id);
        if($res){
            outputAdaptor(C('STATUS_CODE.SUCCESS'));
        }else{
            outputAdaptor(C('STATUS_CODE.FAIL'),'置顶失败');
        }
    }

/****
##   开户免费领取VIP直播

###接口描述
    开户免费领取VIP直播

###接口地址

    GET /index.php?app=landingpage&mod=WeiBoLiveA50&act=openDiscountAct

###参数
    - 无

###正确返回
    {
        "code": 0,
        "message": "成功",
        "data": {
                  [
                    "uid": 4445589,
                    "times": "一周",
                    "introduce": "CCTV证券资讯频道特约嘉宾，交易员。注册国家高级黄金分析师，八年实战操盘喊单经验。",
                    "uname": null,
                    "space": "http://t-www.imaibo.net/space/4445589",
                    "face": "http://t-img.imaibo.net/data/uploads/avatar/4445589/big.jpg?v=20151217003",
                    "status": 1
                  ]
         }
    }
*/
    public function openDiscountAct()
    {
        $mid = $this->mid ? $this->mid : 0;
        $expert = self::$modelInfo->getExpertInfo();
        foreach($expert as &$row){
            $userInfo = getUserInfo($row['uid']);
            $row['uname'] = $userInfo['uname'];
            $row['space'] = U('home/Space/index', ['uid' => $row['uid']]);
            $row['face'] = getUserFace($row['uid'], 'big');
            //用户领取记录
            $cond = ['uid'=>$mid,'live_uid'=>$row['uid']];
            $fields = 'uid';
            $result = D('UserOpenDiscount','landingpage')->getOne($cond,$fields);
            $row['status'] = $result ? 1 : 0;
        }
        $output['list'] = $expert;
        $res = D('UserOpenDiscount','landingpage')->getOne(['uid'=>$mid],'uid');
        $output['isable'] = $res['uid'] ? 1 : 0;
        outputAdaptor(C('STATUS_CODE.SUCCESS'),$output);
    }


/****
##  点击领取VIP微博

###接口描述
    点击领取VIP微博

###接口地址

    POST /index.php?app=landingpage&mod=WeiBoLiveA50&act=putMobile

###参数
    - live_uid: int 播主uid
    - mobile : string 手机号码

###正确返回
    {
        "code": 0||Other,
        "message": "成功"||其它,
        "data": []
    }
*/
    public function putMobile()
    {
        $mid = $this->mid ? $this->mid : false;
        if($mid === false)
            outputAdaptor(C('STATUS_CODE.SESSION_TIMEOUT'),'登录超时,请重新登录');
        $mobile = t($_POST['mobile']) ? t($_POST['mobile']) : '';
        $liveUid = intval($_POST['live_uid']) ? intval($_POST['live_uid']) : 0;
        $data = D('UserOpenDiscount','landingpage')->getOne(['uid'=>$mid],'live_uid');
        if($data){
            $result = self::$modelInfo->getExpertInfo();
            foreach($result as $row){
                if($row['uid'] == $data['live_uid']){
                    $times = $row['times'];
                    $uName = getUserInfo($row['uid'])['uname'];
                    break;
                }
            }
            $expert = ['uname'=>$uName,'times'=>$times];
            model('CurlPostGet')->sendJsonHeaders();
            $output = ['code'=>999,'data'=>$expert];
            echo json_encode($output);
            unset($data,$times,$uName);
            exit;
        }
        if(empty($mobile) || empty($liveUid))
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'缺少参数');
        if(!preg_match('/(\d){11}/',$mobile,$matches) || strlen($mobile) != 11)
            outputAdaptor(C('STATUS_CODE.PARAM_ERROR'),'手机号码格式错误');

        $addData = [
            'uid' => $mid,
            'live_uid' => $liveUid,
            'mobile' => $mobile,
            'ctime' => time()
        ];
        $res = D('UserOpenDiscount','landingpage')->addData($addData);
        if($res)
            outputAdaptor(C('STATUS_CODE.SUCCESS'),'成功');
        else
            outputAdaptor(C('STATUS_CODE.FAIL'),'失败');
    }

    /**
     * @name 统计美股播主的今日人气
     * @return array
     */
    private function aggregateVisit()
    {
        //统计播主今日人气
        $date = intval(date("Ymd"));
        $condMid = [
            [  '$match'=>[
                'date'=>$date
            ]
            ],
            [  '$group'=>[
                '_id'=>['uid'=>'$uid','mid'=>'$mid'],
                'count' => ['$sum'=>1]
            ]
            ]
        ];
        $condIp = [
            [  '$match'=>[
                'date'=>$date
            ]
            ],
            [  '$group'=>[
                '_id'=>['uid'=>'$uid','ip'=>'$ip'],
                'count' => ['$sum'=>1]
            ]
            ]
        ];
        $result = array();
        $midData = model('FncLogMg')->getRecByAggregate($condMid,'cs_us_stock_live');
        $ipData = model('FncLogMg')->getRecByAggregate($condIp,'cs_us_stock_live');
        if($midData){
            foreach($midData as $mK=>$mRw){
                $uid = $mRw['_id']['uid'];
                if(isset($result[$uid]['mid_count']))
                    $result[$uid]['mid_count']++;
                else
                    $result[$uid]['mid_count'] = 1;
            }
        }
        if($ipData){
            foreach($ipData as $iK=>$iRw){
                $uid = $iRw['_id']['uid'];
                if(isset($result[$uid]['ip_count']))
                    $result[$uid]['ip_count']++;
                else
                    $result[$uid]['ip_count'] = 1;
            }
        }

        return $result;
    }

}
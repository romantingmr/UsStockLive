<?php

/**
 * Class ImLastVersionAction.class.php
 * @name 最后首页改版
 * @author Bande
 * @version 20170503
 */
class ImaiboIndexInfoAction extends APIAction
{
    private static $modelInfo;
    public function _initialize()
    {
        self::$modelInfo = D('ImLastVersion','landingpage');
        parent::Init();
    }
/****
## 轮换广告图、脉博头条、严选推荐、好股快

###接口描述
    这是轮换广告图、脉博头条、严选推荐、好股快模块信息接口

###接口地址
    
   GET /index.php?app=landingpage&mod=ImaiboIndexInfo&act=getNewsInformation
    
###参数

    - limit   : int 限制多少条轮换广告图,传值为5

###正确返回
    {
    "code": 0,
    "message": "成功",
    "data": {
        "play_adv": [ //轮换广告图
            {
                "rec_code": "20160218003",
                "title": "红包PC",
                "reason": "红包PC",
                "topic_id": 0,
                "jumpUrl": "http://t-www.imaibo.net",//跳转url
                "pic": "http://t-img.imaibo.net/data/uploads/portfolio/56c5990c8fcbe.jpg", //图片地址
                "rec_order": 0
            }
        ],
        "strict_sel": [//严选vip组合
            {
                "symbol": "+",
                "portfolio_id": "83584",
                "returnTotal": "76.34%",//总收益
                "image_src": "http://t-sio.imaibo.net/getPortfolioChartImage?portfolio=83584",//走势图
                "portfolioName": "短线冲击",
                "detail_url": "http://t-www.imaibo.net/zuhe/83584/index",//订阅跳转
                "lightspots": null //官方评价
            }
        ],
        "rec_stock": [//好股快股票
            {
                "stockCode": "600449", //股票code
                "stockName": "宁夏建材",
                "recommendDate": "04-06",//日期
                "five_gains": "4.29",
                "tenGains": "4.29"
            },
        ],
        "top_line": [//脉博头条
            {
                "title": "动词打次",//标题
                "href": "http://t-www.imaibo.net/viewpoint/detail/4314245"//跳转url
            }
        ]
    }
*/
    public function getNewsInformation()
    {
        $limit = $_GET['limit'] ? intval($_GET['limit']) : 5;
        $from = 1;
        $data = array();
        $data['play_adv'] = self::$modelInfo->playAdv($from,$limit);
        $data['strict_sel'] = self::$modelInfo->strictSelection();
        $data['rec_stock'] = self::$modelInfo->stockHgkRecommend();
        $data['top_line'] = self::$modelInfo->imTopLine();
        outputAdaptor(C('STATUS_CODE.SUCCESS'),$data);
    }
/****
## 热门投资机会A50、直播点金消息、明星播主

###接口描述
   这是热门投资机会A50、直播点金消息、明星播主模块信息接口

###接口地址

GET /index.php?app=landingpage&mod=ImaiboIndexInfo&act=getLiveWeiBoInfo

###参数

- 无

###正确返回
    {
    "code": 0,
    "message": "成功",
    "data": {[
        "A50": {//A50列表
            "weibo_id": "4314549",
            "uid": "208791",
            "content": "转发A50",
            "ctime": "1492739051",
            "publishTime" : '今天 10:11',
            "from": "3",
            "comment": "0",
            "transpond_id": "4314522",
            "transpond": "0",
            "type": "0",
            "type_data": [
            {
            "attach_id": 0,
            "thumburl": "",
            "thumbmiddleurl": "",
            "picurl": ""
            }
            ],
            "from_data": "",
            "trans_comment_id": "0",
            "isdel": "0",
            "dig": "0",
            "isRecommend": "0",
            "isRecommendByEditor": "0",
            "ip": "113.111.11.28",
            "uname": "Leiz7",
            "face": "http://t-img.imaibo.net/data/uploads/avatar/208791/middle.jpg?v=1470796959",
            "is_favorited": false,
            "expend": {
            "weibo_id": "4314522",
            "uid": "3018949",
            "content": "[heihei_直播]10086etoro23434",
            "ctime": "1492161137",
            "from": "2",
            "comment": "1",
            "transpond_id": 0,
            "transpond": "1",
            "type": "0",
            "type_data": [
            {
            "attach_id": 0,
            "thumburl": "",
            "thumbmiddleurl": "",
            "picurl": ""
            }
            ],
            "from_data": "",
            "trans_comment_id": "0",
            "isdel": "0",
            "dig": "2",
            "isRecommend": "0",
            "isRecommendByEditor": "0",
            "ip": "113.111.10.214",
            "uname": "heihei_",
            "face": "http://t-img.imaibo.net/data/uploads/avatar/3018949/middle.jpg?v=1484551764",
            "is_weibo_gold": 0,
            "style": 6,
            "is_favorited": false
        },
        'more' : 'http://'
        ]
    "goldWeiBo":
        [//点金消息
     "list":{
        "weibo_id": "4314388",
        "uid": "4098939",
        "content": "",
        "ctime": "1490683818",
        "publishTime" : '今天 10:11',
        "from": "来自iPhone客户端",
        "comment": "0",
        "transpond_id": "0",
        "transpond": "0",
        "type": "0",
        "type_data": [
        {
        "attach_id": 0,
        "thumburl": "",
        "thumbmiddleurl": "",
        "picurl": ""
        }
        ],
        "trans_comment_id": "0",
        "dig": "0",
        "uname": "MINGU",
        "face": "http://t-img.imaibo.net/data/uploads/avatar/4098939/middle.jpg?v=20151217003",
        "is_weibo_gold": 1,
        "gold_audio": "http://o93y5tjyv.bkt.clouddn.com/app/audio/201703/9a38c619433afdefe8a1a020b0b595e1-1",
        "gold_audio_sec": 1,
        "gold_audio_sec_format": "01''",
        "style": 4,
        "w3gUrl": "http://t-www.imaibo.net/w3g/weibo/4314388",
        "hasDigged": false,
        "usericon": []
      }
     "more": 'http://'
    ]
    ,"star_host":{//明星播主
         "list":[
             "uid": "276065",
            "gain": "92%",
            "face": "http://t-img.imaibo.net/data/uploads/avatar/276065/middle.jpg?v=1476773189",
            "space": "http://t-www.imaibo.net/space/276065",
            "uname": "波比",
            "title": "3个月单只股票收益92%"
        ],
        'more' : http://
      }
   }
 */
    public function getLiveWeiBoInfo()
    {
        $mid = $this->mid ? $this->mid : 0;
        $data = array();
        //$keyWord = array('A50','上证50');
        //@todo 20170706 新规则
        $result = D('TagsSys', 'home')->getCateJoinTagList(array('t.isdel' => 1, 'c.isdel' => 1,'c.cid'=>7), "c.cid,t.tag_name as name");
        $keyWord = getSubByKey($result,'name');
        //美股专家uid
        $cond['user_group_id'] = C('USER_GROUP_ID');
        $result = model('UserGroupLink')->getUserGroupByMap($cond,'uid');
        $uidArr = getSubByKey($result,'uid');
        $data['goldWeiBo']['list'] = self::$modelInfo->goldWeiBo();
        $data['goldWeiBo']['more'] = 'http://'.$_SERVER['HTTP_HOST'].'/zhibo/dianjin';
        $data['star_host']['list'] = self::$modelInfo->starLiveHost($mid);
        $data['star_host']['more'] = 'http://'.$_SERVER['HTTP_HOST'].'/master';
        $data['A50']['list'] = self::$modelInfo->hotInvestmentChance($keyWord,$uidArr,$mid);
        $data['A50']['more'] = 'http://'.C('SF_SITE_HOST').'/epWeb';
        outputAdaptor(C('STATUS_CODE.SUCCESS'),$data);
    }

/****
## uid:4089514的A50直播、观点

###接口描述
     uid:4089514的A50直播、观点

###接口地址

    GET /index.php?app=landingpage&mod=ImaiboIndexInfo&act=getA50WbAndVPointList

###参数

    - uid: int get

###正确返回
    {
    "code": 0,
    "message": "成功",
    "data": {
        "viewpoint": {
            "list": [
            {
                "title": "2-18大盘分析", //标题
                "articleUrl": "http://t-www.imaibo.net/viewpoint/detail/3353353",//文章地址
                "uname": "紫色的股",//昵称
                "uid": "2638430",
                "publishTime": "2016-02-17 15:38",//发布 时间
                "read": "1",//阅读数
                "comments": 19,//评论数
                "charge": false,//是否付费
                "cream": false,//是否精华
                "special": true,//是否特殊
                "official": false,//是否官方
                "original": false,//是否原创
                "reproduced": false
            }
            ],
        "more": "http://t-www.imaibo.net/space/4089514/viewpoint"//观点列表
    },
    "weibo": {
        "list": [
            {
                "weibo_id": "3600519", //微博id
                "uid": "4089514",//用户uid
                "content": "[黑洞论市直播]新加坡的A50指数小涨0.3%，A股有望高开",//内容
                "ctime": "1462238264",
                "from": "0",
                "comment": "0",
                "transpond_id": "0",
                "transpond": "0",
                "type": "0",
                "type_data": [
                {
                "attach_id": 0,
                "thumburl": "",
                "thumbmiddleurl": "",
                "picurl": ""
               }
            ],
            "from_data": "",
            "trans_comment_id": "0",
            "isdel": "0",
            "dig": "0",
            "isRecommend": "0",
            "isRecommendByEditor": "0",
            "ip": "119.130.187.122",
            "uname": "黑洞论市",
            "face": "http://t-img.imaibo.net/data/uploads/avatar/4089514/middle.jpg?v=1460939349",
            "is_favorited": false,
            "expend": "",
            "publishTime": "2016-05-03 09:17"//发布时间
        }
        ],
        "more": "http://t-www.imaibo.net/space/4089514"
        }
        }
    }
*/
    public function getA50WbAndVPointList()
    {
        $uid = $_GET['uid'] ? intval($_GET['uid']) : 4089514;
        $mid = $this->mid ? intval($this->mid) : 0;
        $data = array();
        $keyWord = ['A50','上证50'];
        $data['viewpoint']['list'] = self::$modelInfo->getViewPoint($keyWord,$uid);
        $data['viewpoint']['more'] = 'http://'.$_SERVER['HTTP_HOST'].'/space/'.$uid.'/viewpoint';
        $data['weibo']['list'] = self::$modelInfo->A50WeiBoListAct($keyWord,$uid,$mid);
        $data['weibo']['more'] = 'http://'.$_SERVER['HTTP_HOST'].'/space/'.$uid;
        outputAdaptor(C('STATUS_CODE.SUCCESS'),$data);
    }

    public function putDataToStarHost()
    {
        $modelStar =  D('ImStarLiveHost','landingpage');
        $result = $modelStar->getVipWeiBo();
        $data = $modelStar->formatWeiBoData($result);
        if($data){
            $i = $j = 0;
            foreach($data as $rw){
                $modelStar->delRow(array('stockId'=>$rw['stockId']));
                $res = $modelStar->addData($rw);
                if($res)
                   $i++;
                else
                    $j++;
            }
            if(isset($_SERVER['argc']) && $_SERVER['argc']>=1){
                if($res)
                    exit('success count is '.$i);
                else
                    exit('fail count is '.$j);
            }else {
                if ($res)
                    outputAdaptor(C('STATUS_CODE.SUCCESS'),array('count'=>$i));
                else
                    outputAdaptor(C('STATUS_CODE.FAIL'),array('count'=>$j));
            }
        }

    }
    /**
     * @name 重置数据
     */
    public function trunCateTable()
    {
        D('ImStarLiveHost','landingpage')->trunCateTable();
    }

    /**
     * @name 重置循环队列
     */
    public function delUidLoopList()
    {
        self::$modelInfo->delUidLoopList();
    }


}
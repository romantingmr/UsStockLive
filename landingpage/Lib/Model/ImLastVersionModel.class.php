<?php

/**
 * Class ImLastVersionModel.class.php
 * @name 首页最后改版模型
 * @author romantingmr
 * @version 20170504
 */
class ImLastVersionModel extends Model {
    protected $autoCheckFields = false;
    private $rdsObj = null;
    private $rdsDb = 0;
    private static $starHostConf = array(
        array('uid'=>221943,'title'=>'十年投资业绩超过21倍'),
        array('uid'=>702907,'title'=>'选股准确率超八成'),
        array('uid'=>27639,'title'=>'对大盘和个股的分析极度精准'),
        array('uid'=>1971570,'title'=>'时空平衡理论创始人及践行者'),
        array('uid'=>1966085,'title'=>'逃顶三次股灾，擅抓翻倍牛股'),
        array('uid'=>31696,'title'=>'最具实力派美女股神'),
        array('uid'=>187360,'title'=>'曾任职私募基金'),
        array('uid'=>1541274,'title'=>'抄底、跟庄、捉妖、做波段'),
        array('uid'=>24746,'title'=>'稳定盈利，擅抓妖股'),
        array('uid'=>2097765,'title'=>'22年专研技术分析'),
        array('uid'=>3032465,'title'=>'涨停狙击创始人'),
        array('uid'=>1343649,'title'=>'网络知名博主'),
        array('uid'=>4058505,'title'=>'曾操盘过众多妖股'),
        array('uid'=>302268,'title'=>'擅长基本面+技术面选股'),
        array('uid'=>105146,'title'=>'211大学股票投资分析教师'),
        array('uid'=>1881689,'title'=>'市场敏感性强，把握时机准确'),
        array('uid'=>107999,'title'=>'独创《周氏股市心理学》理论'),
        array('uid'=>4034595,'title'=>'擅长政策分析，中长线操作'),
        array('uid'=>505300,'title'=>'半年431.47%的收益率'),
        array('uid'=>4209607,'title'=>'致力超短线的操盘手'),
        array('uid'=>4257723,'title'=>'纯粹技术分析投机者'),
        array('uid'=>4025601,'title'=>'多家投资俱乐部首席策略师'),
        array('uid'=>355625,'title'=>'外国投资机构职业交易员'),
        array('uid'=>4089514,'title'=>'组合型投资，年收益超过100%'),
        array('uid'=>4120453,'title'=>'熊市稳定，牛市翻倍'),
        array('uid'=>4043196,'title'=>'名博，所发博文总点击量过亿'),
        array('uid'=>1986884,'title'=>'擅长挑选爆发力个股'),
        array('uid'=>216945,'title'=>'2014年中国股市好榜样冠军'),
        array('uid'=>4227737,'title'=>'擅长尾盘票，月均收益20%'),
        array('uid'=>3815507,'title'=>'擅长大盘1分钟预测'),
    );
    private static $loopList = array(
        221943,702907,27639,1971570,1966085,31696,187360,1541274,24746,2097765,3032465,1343649,4058505,
        302268,105146,1881689,107999,4034595,505300,4209607,4257723,4025601,355625,4089514,4120453,4043196,
        1986884,216945,4227737,3815507
    );

    public function _initialize()
    {
        if($this->rdsObj === null){
            require_cache(VENDOR_PATH.'libs/redis/RedisLong.class.php');
            $this->rdsObj = RedisLong::getInstance();
        }
    }
    /**
     * @name 左上角播放图片
     * @param int $isFrom 来源
     * @param int $limit 限制输出条数
     * @return mixed
     */
    public function playAdv($isFrom=1,$limit=5)
    {
        $condRec['status'] = 1;
        $condRec['pic4pc'] = array('$ne' => '');
        $modelTopics = D('mb_topics');
        $recList = D('PortfolioRec','csi')->getList(1,$limit,$condRec,array('rec_code'=>true,'title'=>true,'reason'=>true,'pic'=>true,'pic4pc'=>true,'portfolio_list'=>true,'topic_id'=>true,'rec_order'=>true,'jumpUrl' => true));
        if($recList) {
            foreach ($recList as $k => $v) {
                $recList[$k]['topic_id'] = $recList[$k]['topic_id'] ? $recList[$k]['topic_id'] : 0;
                if ($recList[$k]['topic_id'] > 0) {
                    $topicInfo = $modelTopics->field('Keyword,Id')->where(array('Id' => (int)$recList[$k]['topic_id']))->find();
                    $recList[$k]['jumpUrl'] = U('investment/Topic/portfolioDetail', array('k' => $topicInfo['Id']));
                } else {
                    $recList[$k]['jumpUrl'] = $v['jumpUrl'] ? htmlspecialchars_decode($v['jumpUrl']) : '';
                }
                if ($isFrom == 1) { //pic4pc是pc端的广告图，pic是APP端的广告图当来自pc端的请求时，pic字段返回pic4pc的值
                    $recList[$k]['pic'] = $v['pic4pc'];
                    unset($recList[$k]['pic4pc']);
                }
                unset($recList[$k]['_id']);
                unset($recList[$k]['portfolio_list']);
                if (!isset($recList[$k]['rec_order'])) $recList[$k]['rec_order'] = 0; //推荐广告图的轮播顺序
            }
        }
        return $recList;
    }

    /**
     * @name 脉博头条
     * @param string $type
     * @return array
     */
    public function imTopLine($type='')
    {

        $map['isdel'] = 0;
        if($type == "abroad"){//海外资讯
            $list = D("LongweiboAbroadLink","viewpoint")->getAbroadList($map,false,false,5);
        }else{//热门推荐
            $list = D("LongweiboHotRecommendLink","viewpoint")->getHotRecommendList($map,false,false,5);
        }
        $articleList = array();
        foreach($list['data'] as $v){
            $v['article_type'] = $v['article_type']?:1;
            $author = array(
                "uid"=> $v['info']['lwb_uid'],
                "uname"=> $v['userName'],
                "face"=> getUserFace($v['info']['lwb_uid']),
                "href"=>U("home/Space/index",array("uid"=>$v['info']['lwb_uid'])),
            );
            $articleList[] = array(
                'title'=>$v['title']?:$v['info']['lwb_title'],
                'href'=>$v['article_type']==1?U("viewpoint/Longweibo/index",array("weibo_id"=>$v['weibo_id'])):U("home/Space/detail",array("id"=>$v['weibo_id']))
            );
        }
        return $articleList;
    }

    private function getFeatures($feature)
    {
        $return = array(
            'charge' => false,
            'special' => false,
            'cream' => false,
            'official' => false
        );
        if ($feature & 1) {
            $return['charge'] = true;
        }
        if ($feature & 2) {
            $return['special'] = true;
        }
        if ($feature & 4) {
            $return['official'] = true;
        }
        if ($feature & 8) {
            $return['cream'] = true;
        }
        return $return;
    }


    /**
     * @name 严选VIP组合
     * @return array
     */
    public function strictSelection()
    {
        $cond = array('is_del'=>0);
        $fields = 'portfolioId';
        $sort = 'sort ASC';
        $pidArr = D('PcPortfolioIndexVip','admin')->getAll($cond,$fields,$sort);
        $portfolioLists = $plist = array();
        if(!empty($pidArr)){
            foreach($pidArr as $rw)
            {
                $result = Service('Epam')->requestPortfolioInfo($rw['portfolioId']);
                if($result) {
                    if ($result['returnTotal'] > 0)
                        $plist['symbol'] = '+';
                    else
                        $plist['symbol'] = '-';
                    $plist['portfolio_id'] = $rw['portfolioId'];
                    $plist['returnTotal'] = $result['returnTotal'] . '%';
                    $plist['image_src'] = 'http://'.C('PORTFOLIO_TREND_IMAGE') . '/getPortfolioChartImage?portfolio=' . $result['id'];
                    $plist['portfolioName'] = $result['portfolioName'];
                    $plist['detail_url'] = 'http://'.C('SF_SITE_HOST').'/zuhe/'.$rw['portfolioId'].'/index';
                    //官方评价
                    $vipRes = D('PortfolioVipMg', 'csi')->getPortfolioByPid($rw['portfolioId']);
                    $plist['lightspots'] = $vipRes['lightspots'];

                    $portfolioLists[] = $plist;
                }
            }
        }
        return $portfolioLists;

    }

    /**
     * @name 好股快股票推荐
     * @return array
     */
    public function stockHgkRecommend()
    {
        $res = D('StockHgkRecommendMg','activity')->getAll();
        $data = array();
        foreach ($res as $v) {
            $data[] = array(
                'stockCode' => $v['stock_code'],
                'stockName' => $v['stock_name'],
                'recommendDate' => date('Y-m-d',strtotime($v['recommend_date'])),
                'five_gains' => $v['five_gains'],
                'tenGains' => $v['ten_gains'],
            );
        }
        return $data;
    }

    /*public function starLiveHost($mid)
    {
        $retData = array();
        //从收益>=5%，并且最新动态在1个月内的播主中选10个
        //$result = D('ImStarLiveHost','landingpage')->getList(array(),"*",'gain DESC',$limit=100);
        $mcResult = S('star_host_info_index');
        if($mcResult){
            $result = $mcResult;
        }else{
            $result = D('ImStarLiveHost','landingpage')->getListBySetCond();
            S('star_host_info_index',$result,3600*7);
        }
        $showNbs = 20;
        $count = count($result) ? count($result) : 0;
        $leaveNbs = intval($showNbs - $count);
        $initKey = $count-1;
        if(empty($mid))
            $isFollow = 'unfollow';
        if($result) {
            foreach($result as $rw){
                $diffDays = intval((time()-strtotime('-1 day',$rw['ctime']))/60/60/24);
                $curMonthDays = date('t');
                $word = $diffDays > $curMonthDays ? ceil($diffDays/$curMonthDays).'个月' : $diffDays.'个交易日';
                //是否关注
                if($mid) {
                    $isFollow = D('UserFollowSv', 'user')->getFollowState($mid, $rw['uid']);
                }
                $tempArr = array(
                    'uid' => $rw['uid'],
                    'gain' => ($rw['gain']*100).'%',
                    'face' => getUserFace($rw['uid'],'big'),
                    'space' => U('home/Space/index',array('uid'=>$rw['uid'])),
                    'uname' =>getUserName($rw['uid']),
                    'title' => $word.'单只股票收益'.($rw['gain']*100).'%',
                    'follow' => ($isFollow == 'unfollow') ? 0 : 1,
                );
                $uidArr[$rw['uid']] = $rw['uid'];
                $retData[] = $tempArr;
                unset($tempArr,$result);
            }
        }
        $leaveUidArr = $this->loopConfUnique($uidArr,$leaveNbs);
        if($leaveUidArr){
            foreach ($leaveUidArr as $value){
                //是否关注
                if($mid) {
                    $isFollow = D('UserFollowSv', 'user')->getFollowState($mid, $value);
                }
                foreach (self::$starHostConf as $key=>$row){
                    if($row['uid'] == $value){
                        $title = $row['title'];
                        break;
                    }
                }
                $tempArr = array(
                    'uid' => $value,
                    'gain' => '0%',
                    'face' => getUserFace($value,'big'),
                    'space' => U('home/Space/index',array('uid'=>$value)),
                    'uname' =>getUserName($value),
                    'title' => $title,
                    'follow' => ($isFollow == 'unfollow') ? 0 : 1,
                );
                if($initKey > 0)
                    $retData[++$initKey] = $tempArr;
                else
                    $retData[++$initKey] = $tempArr;
            }
            unset($tempArr);
        }
        return $retData;
    }*/

    /**
     * @name 新规则
     * @param $mid
     * @return array
     */
    public function starLiveHost($mid)
    {
        $lists = array();
        $result = M('longweibo_special_user')->field('uid,reason')->order('ctime DESC')->select();
        if($result){
            foreach($result as $row){
                $tempArr = array();
                //是否关注
                if($mid) {
                    $watch = D('UserFollowMg', 'user')->getFollowId($mid, $row['uid']);
                }
                $tempArr = [
                    'uid' => $row['uid'],
                    'gain' => '0%',
                    'face' => getUserFace($row['uid'],'big'),
                    'space' => U('home/Space/index',array('uid'=>$row['uid'])),
                    'uname' =>getUserName($row['uid']),
                    'title' => $row['reason'],
                    'follow' => $watch ? 1 : 0,
                ];
                $lists[] = $tempArr;
            }
        }
        return $lists;
    }

    /**
     * @name 从starHostConf文件循环每天按顺序取不同的播主
     * @param $uidArr
     * @param int $needNos
     * @return array|bool
     */
    private function loopConfUnique($uidArr,$needNos=10)
    {
        $this->rdsObj->select($this->rdsDb);
        if (empty($uidArr))
            $uidArr = array();
        //获取循环队列最后一次值 start
        $ctime = $this->rdsObj->hGet('rsh_uid_loop_list','ctime');
        $rdsLoopList = json_decode($this->rdsObj->hGet('rsh_uid_loop_list','recommend_uids'),true);
        if($rdsLoopList)
             self::$loopList = $rdsLoopList;
        //end
        //去掉$uidArr 与 $starHostConf 重复
        if($uidArr) {
            foreach ($uidArr as $val) {
                //重置循环队列
                foreach (self::$loopList as $k => $value) {
                    if ($value == $val) {
                        unset(self::$loopList[$k]);
                        $temp[] = $value;
                        break;
                    }
                }
            }
            //重置队列顺序，保持原来数据 start
            foreach ($temp as $uid) {
                array_push(self::$loopList, $uid);
            }
            $tempList = array_values(self::$loopList);
        }else{
            $tempList = self::$loopList;
        }
        //end
        //每日取不重复的$needNos个数据
        foreach($tempList as $key=>$uid){
            if($key == $needNos) break;
	        $tempArr[] = $uid;
            unset($tempList[$key]);
        }
        //把取出需要显示的值加到队列最后，保持队列数据完整
        foreach($tempArr as $uid){
            array_push($tempList,$uid);
        }
        //最后一次访问的循环队列储存在rds
        if(empty($rdsLoopList) || $ctime != date('Ymd')) {
            $this->rdsObj->hSet('rsh_uid_loop_list', 'recommend_uids', json_encode(array_values($tempList)));
            $this->rdsObj->hSet('rsh_uid_loop_list', 'ctime', date('Ymd'));
        }
        return $tempArr;
    }

    /**
     * @name 初始化循环队列
     */
    public function delUidLoopList()
    {
        $this->rdsObj->del('rsh_uid_loop_list');
    }

    public function getUidLoopList()
    {
        $rdsLoopList = $this->loopConfUnique;
        return $rdsLoopList;
    }

    /**
     * @name 播主最新直播数
     * @param $content
     * @param $uid
     * @param $weiBoId
     * @param $mid
     * @return int
     */
    public function getLastWbTotal($content,$uid,$weiBoId)
    {
        $searchParam['type'] = "weibo-info";
        $searchParam = $this->buildEsSearchParamsBody($content,$uid);
        $count = count($searchParam['body']['query']['bool']['must']);
        $searchParam['body']['query']['bool']['must'][$count] = [
            'range' => [
                'weibo_id' => [
                    'gt' => intval($weiBoId)
                ]
            ]
        ];
        $searchParam['body']['fields'] = array("weibo_id");
        $searchWeibo = service("Elastic")->search($searchParam);
        $weiBoIds = array();
        if($searchParam['hits']) {
            foreach ($searchWeibo['hits'] as $weibo) {
                $weiBoIds[] = $weibo['fields']['weibo_id'][0];
            }
        }
        if($weiBoIds) {
            $num =  count($weiBoIds) ? count($weiBoIds) : 0;
        }
        return $num ? $num : 0;
    }

    /**
     * @name 获取带有keyword关键字的数据
     * @param string $keyWord
     * @param int $uid
     * @param array $uidData
     * @param int $mid
     * @param int $weiBoId
     * @param int $page
     * @param string $mod
     * @return array
     */
    public function hotInvestmentChance($keyWord='A50',$uid=0,$mid=0,$weiBoId=0,$page=1,$mod='Index')
    {
        $nowPage = $page;
        $rows = 20;
        $isVip = false;
        if($mod == 'Index'){
            $isVip = true;
        }
        $searchParam = $this->buildEsSearchParamsBody($keyWord,$uid,$weiBoId,0,$isVip);
        $searchParam['index'] = "weibo-info_*";
        $searchParam['type'] = "weibo-info";
        $searchParam['body']['from'] = ($nowPage - 1)*$rows;
        $searchParam['body']['size'] = $rows;
        $searchParam['body']['sort'] = array(array("ctime"=>"desc"));
        $searchParam['body']['fields'] = array("weibo_id");
        $searchWeibo = service("Elastic")->search($searchParam);
        $weiBoIds = array();
        foreach($searchWeibo['hits'] as $weibo){
            $weiBoIds[] = $weibo['fields']['weibo_id'][0];
        }
        $result = $this->formatWeiBoInfo($weiBoIds,$mid,$mod);
        return $result;
    }

    /**
     * @name 格式化微博数据
     * @param $weiBoIds
     * @param $mid
     * @param $mod
     * @return array
     */
    public function formatWeiBoInfo($weiBoIds,$mid,$mod='Index')
    {
        if($weiBoIds) {
            //$modelOperate = D('Operate', 'weibo');
            $i = 0;
            foreach ($weiBoIds as $k => $v) {
                $weiboDetail = D('Weibo', 'weibo')->getOneLocation($v);
                if (isset($weiboDetail['weibo_id'])) {
                    // 收费判断
                    $vipWeibo = D('ItemOperate', 'consume')->buildWeiboPriceOne($weiboDetail, $mid);
                    $weiboDetail['weibo_vip_show'] = $vipWeibo['weibo_vip_show'];
                    $weiboDetail['is_weibo_vip'] = $vipWeibo['is_weibo_vip'];
                    $weiboDetail['publishTime'] = friendlyDate($weiboDetail['ctime']);
                    $weiboDetail['content'] = str_replace('$','',format($weiboDetail['content'],true));
                    if($vipWeibo['weibo_vip_show'] == 0){
                        $weiboDetail['content'] = '';
                    }
                    $weiBoList[$i++] = $weiboDetail;
                }
            }
        }
        return $weiBoList;
    }

    /**
     * @name 生成es查询语句
     * @param $content
     * @param int $uid
     * @param int $weiBoId
     * @param $title
     * @param $isVip
     * @return array
     */
    private function buildEsSearchParamsBody($content,$uid=0,$weiBoId=0,$title=0,$isVip=0)
    {
        //mappings uid start
        //开通美股直播的所有播主
        if($uid){
            if(is_array($uid)) {//in范围查询
                $searchParam['body']['query']['bool']['must'][0] = array(
                    'terms' => array(
                        'uid' => $uid
                    )
                );
            }else{//相等查询
                $searchParam['body']['query']['bool']['must'][0] = array(
                    'term' => array(
                        'uid' => $uid
                    )
                );
            }
        }
        //end

        //mappings content start
        if($content) { 
            $count = count($searchParam['body']['query']['bool']['must']);
            if (is_array($content)) { 
                foreach ($content as $value) {
                    $temp[] = array(
                        'match_phrase' => array(
                            "content" => $value
                        )
                    );
                }
            } else {
                $searchParam['body']['query']['bool']['must'][$count][0] = array(
                    'match_phrase' => array(
                        "content" => $content
                    )
                );
            }

            //mappings title start
            if($title) {//weibo-info 特殊处理
                if (is_array($title)) {
                    foreach ($title as $value) {
                        $temp2[] = array(
                            'match_phrase' => array(
                                "title" => $value
                            )
                        );
                    }
                } else {
                    $searchParam['body']['query']['bool']['must'][1][1] = array(
                        'match_phrase' => array(
                            "title" => $title
                        )
                    );
                }
            }
            //end
            if($temp && $temp2){
                $newTemp = array_merge($temp,$temp2);
                //keyword模糊匹配
                $searchParam['body']['query']['bool']['must'][$count] = array(
                    'bool' => array(
                        'should' => $newTemp
                    )
                );
            }else{
	            $searchParam['body']['query']['bool']['must'][$count] = array(
                    'bool' => array(
                        'should' => $temp
                    )
                );
	    }
            unset($temp,$temp2);
        }
        //end
        //mappings weibo_id start
        if($weiBoId) {
            $count = count($searchParam['body']['query']['bool']['must']);
            $searchParam['body']['query']['bool']['must'][$count] = [
                'term' => [
                    'weibo_id' => $weiBoId
                ]
            ];
        }
        //end
        if($isVip) {
            $count = count($searchParam['body']['query']['bool']['must']);
            $searchParam['body']['query']['bool']['must'][$count] = [
                'term' => [
                    'is_vip' => 0
                ]
            ];
        }
        //print_r($searchParam);die;
        return $searchParam ? $searchParam : array();
    }

    /**
     * @name 播主活动A50数据
     * @param string $keyWord
     * @param $uid
     * @param $mid
     * @return array
     */
    public function A50WeiBoListAct($keyWord='A50',$uid,$mid)
    {
        $searchParam['type'] = "weibo-info";
        $searchParam = $this->buildEsSearchParamsBody($keyWord,$uid);
        $nowPage = 1;
        $rows = 20;
        $searchParam['body']['from'] = ($nowPage - 1)*$rows;
        $searchParam['body']['size'] = $rows;
        $searchParam['body']['sort'] = array(array("ctime"=>"desc"));
        $searchParam['body']['fields'] = array("weibo_id");
        $searchWeibo = service("Elastic")->search($searchParam);
        if(empty($searchWeibo['hits'])){
             unset($searchParam['body']['query']['bool']['must'][0]);
             sort($searchParam['body']['query']['bool']['must']);
             $searchWeibo = service("Elastic")->search($searchParam);
        }
        $weiBoIds = array();
        foreach($searchWeibo['hits'] as $weibo){
            $weiBoIds[] = $weibo['fields']['weibo_id'][0];
        }
        $result = $this->formatWeiBoInfo($weiBoIds,$mid);
        return $result;
    }

    /**
     * @name 通过keyword获取观点数据
     * @param string $keyWord
     * @param int $uid
     * @return array
     */
    public function getViewPoint($keyWord='A50',$uid=0)
    {
        $search_parm['type'] = "longweibo-info";
        $search_parm = $this->buildEsSearchParamsBody($keyWord,$uid,0,$keyWord);
        foreach($search_parm['body']['query']['bool']['must'][1]['bool']['should'] as &$row){
            if($row['match_phrase']['title']) {
                unset($row['match_phrase']['lwb_content']);
            }else {
                $row['match_phrase']['lwb_content'] = $row['match_phrase']['content'];
                unset($row['match_phrase']['content']);
            }
        }
	
        $search_parm['body']['from'] = 0;
        $search_parm['body']['size'] = 20;
        $search_parm['body']['sort'] = array(array("ctime"=>"desc"));
        $search_parm['body']['fields'] = array("weibo_id");
        $viewpointlist = service("Elastic")->search($search_parm);
        if(intval($viewpointlist['total']) <= 0){
            unset($search_parm['body']['query']['bool']['must'][0]);
            sort($search_parm['body']['query']['bool']['must']);
            //print_r($search_parm);die;
            $viewpointlist = service("Elastic")->search($search_parm);
        }
        $list = array();
        if($viewpointlist['hits']) {
            foreach ($viewpointlist['hits'] as $viewpoint) {
                $weiboId = $viewpoint['fields']['weibo_id'][0];
                $cond['weibo_id'] = $weiboId;
                $detail = D("LongweiboInfo", "viewpoint")->getInfoByConditon($cond);
                if($detail){
                    $features = array(
                        'charge'=>false,
                        'special'=>false,
                        'cream'=>false,
                        'official'=>false
                    );
                    if($detail['features']&1){
                        $features['charge'] = true;
                    }
                    if($detail['features']&2){
                        $features['special'] = true;
                    }
                    if($detail['features']&4){
                        $features['official'] = true;
                    }
                    if($detail['features']&8) {
                        $features['cream'] = true;
                    }
                    $list[] = array(
                        "title"=>$detail['title'],
                        "articleUrl"=>U("viewpoint/Longweibo/index",array("weibo_id"=>$detail['weibo_id'])),
                        "uname"=>getUserName($detail['uid']),
                        "uid"=>$detail['uid'],
                        "publishTime"=>friendlyDate($detail['ctime']),
                        "read"=>D("StatisticsRds","viewpoint")->getReadCount($detail['weibo_id'])?:0,
                        "comments"=>D('CommentSv', 'weibo')->getCommentCountByWid($detail['weibo_id']),
                        'charge'=> $features['charge'] ,
                        'cream'=> $features['cream'] ,
                        'special'=> $features['special'] ,
                        'official'=> $features['official'],
                        "original"=>$detail['publish_type']==1?true:false,
                        "reproduced"=>$detail['publish_type']==2?true:false,
                    );
                }
            }
        }
        return $list;
    }

    /**
     * @name 点金消息
     * @param $p
     * @param $limit
     * @return array
     */
    public function goldWeiBo()
    {
        $cond = array();
        $field = 'weibo_id';
        //拿limit * 10 的数据
        $lists = D('WeiboGoldRelate','weibo')->getList($cond,$field,20);
        $weiBoDetail = array();
        if($lists['data']){
            foreach ($lists['data'] as $v) {
                $detail = D('Weibo', 'weibo')->getOneLocation((int)$v['weibo_id']);
                //可能存在空数据，需要做下判断
                if($detail){
                    $weiBoDetail[] = $detail;
                }
            }
        }
        $weiBoDetail = D('WeiboSv','csi')->format($weiBoDetail);
        if($weiBoDetail){
            foreach($weiBoDetail as &$data){
                $data['publishTime'] = date('H:i',$data['ctime']);
            }
        }
        return $weiBoDetail;
    }

    /**
     * @name 是否已经在etoro开户
     * @param $uid
     * @return mixed
     */
    public function getEtoroUserInfo($uid)
    {
        $ydtUrl = 'http://'.C('YIDIANTOU_DOMAIN');//易点投域名
        $user = D('User', 'home')->getUserByIdentifier($uid, 'uid');
        $reqParam = array('phoneNo'=>$user['mobile']);
        $ydtApi = $ydtUrl.'/ydt/api/background/etoro/findUserInfo?'.http_build_query($reqParam);
        $response = model("CurlPostGet")->curlGet($ydtApi);
        $result = json_decode($response['output'],true);
        return $result['data'];
    }

    public function bindYdtUser($params)
    {
        $ydtUrl = 'http://'.C('YIDIANTOU_DOMAIN');//易点投域名
        $ydtApi = $ydtUrl.'/ydt/api/etoro/user/v1/bind';
        $response = model("CurlPostGet")->curlPost($ydtApi,$params);
        $result = json_decode($response['output'],true);
        return $result['data'];
    }

    public function searchStock($params)
    {
        $ydtUrl = 'http://'.C('YIDIANTOU_DOMAIN');//易点投域名
        $ydtApi = $ydtUrl.'/ydt/api/etoro/stock/search?'.http_build_query($params);
        $response = model("CurlPostGet")->curlGet($ydtApi);
        $result = json_decode($response['output'],true);
        return $result;
    }

    public function hotStock()
    {
        $ydtUrl = 'http://'.C('YIDIANTOU_DOMAIN');//易点投域名
        $ydtApi = $ydtUrl.'/ydt/api/etoro/stock/hot';
        $response = model("CurlPostGet")->curlGet($ydtApi);
        $result = json_decode($response['output'],true);
        return $result;
    }

    /**
     * @name 是否美股直播专家
     * @param $uid
     * @return bool
     */
    public function isUsStockExpert($uid)
    {
        if(empty($uid)) return false;
        $gCond['user_group_id'] = C('USER_GROUP_ID');
        $gCond['uid'] = $uid;
        $gFields = 'uid';
        $uidData = model('UserGroupLink')->getUserGroupByMap($gCond,$gFields);
        if($uidData)
            return true;
        else
            return false;
    }

    /**
     * @name 美股直播页，VIP微博活动
     * @return array
     */
    public function getExpertInfo()
    {
        $expert = [
            ['uid'=>4445589,'times'=>'一周','introduce'=>'CCTV证券资讯频道特约嘉宾，交易员。注册国家高级黄金分析师，八年实战操盘喊单经验。'],
            ['uid'=>458,'times'=>'一个月','introduce'=>'多年操盘实战经验，经历熊牛，资金翻65倍。精准趋势捕捉，波段操作。'],
            ['uid'=>4089514,'times'=>'一个月','introduce'=>'国外机构职业交易员，曾参与10余个国家的股指交易，擅长A50、黄金、原油、外汇。一个VIP，享受四大投资领域喊单！'],
            ['uid'=>4445466,'times'=>'一个月','introduce'=>'15年黄金原油实盘交易者。资深看盘与短线操作。K先结合布林线，信号为王，步步为营。']
        ];
        return $expert;
    }

}
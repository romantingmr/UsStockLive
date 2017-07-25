<?php
/**
 * @name 投资组合模型
 * @author Jay
 * @version 2015-11-5
 */
class PortfolioInfoModel extends Model {
    protected $autoCheckFields = false;

    private $_stkDB = null;

    private $msgCollection = 'cs_portfolio_info';

    public function _initialize() {

        if($this->_stkDB == null) {
            require_cache(VENDOR_PATH . 'libs/mongo/MongoPortfolio.class.php');
            $this->_stkDB = MongoPortfolio::getInstance();
            $this->_stkDB->selCollection($this->msgCollection);
        }

    }

    public function __destruct() {
        $this->_stkDB->destructClose();
    }

    /**
     * 获取投资组合榜单
     * 规则：创建时间最近两个月，且10人以上关注，收益最高
     */
    public function getportfoliotop(){
        $cond = array();
        $cond['status'] = 1;
        $lastlastmonth = strtotime(date('Y-m-d',time()))-3600*24*30*2; //两个个月内创建的投资组合
        $cond['ctime'] = array('$gte'=>$lastlastmonth);
        $cond['followedCount'] = array('$gte'=>10);
        $option['sort'] = array('yield_ratio_total' => -1);
        $option['limit'] = 3;
        $cachekey = 'getportfoliotop';
        $data = S($cachekey);
        if(!empty($data)){
            return $data;
        }else{
            $field = array( 'uid' => true, 'portfolio_id'=> true, 'name' => true, 'yield_ratio_total' => true, 'followedCount' => true);
            $this->_stkDB->selCollection($this->msgCollection);
            $res = $this->_stkDB->mgFind2($cond, $option, $field, false);
            $list = $res ? array_values($res) : array();
            unset($res);
            foreach($list as  $v) {
                $userinfo = getUserInfo($v['uid']);
                $data[] = array(
                            'area' => '沪深',
                            'combinedHref' => '/portfolio/'.$v['portfolio_id'],
                            'combinedAuthor' => getUserName($v['uid']),
                            'combinedName' => $v['name'],
                            'percentage' => sprintf("%1\$.2f", round($v['yield_ratio_total'] * 100, 2)),
                            'combinedFollowNum' => intval($v['followedCount']),
                            'headImg' => $userinfo['face'],
                          );
            }
            unset($list);
            //设置一天的cache
            S($cachekey,$data,86400);
            return $data ? $data : array();
        }
    }
    //20160129临时写死 三个组合 9916 80736 80889
    public function getportfoliotop_20160129(){
        $cachekey = 'getportfoliotop_20160129_1';
        $data = S($cachekey);
        if(!empty($data)){
            return $data;
        }else{
            $pids = array(80736,9916,80879);
            //$pids = array(80736,80889);
            foreach ($pids as $pid) {
                $info = json_decode($this->_getEPAMPortfolioInfoByPid($pid),true);
                if(empty($info))
                    continue;
                $uid = $info['imaiboId'];
                $userinfo = getUserInfo($uid);
                $data[] = array(
                    'area' => '沪深',
                    'combinedHref' =>service('Url')->portfolioDetail($pid),
                    'combinedAuthor' => getUserName($uid),
                    'combinedName' => $info['portfolioName'],
                    'percentage' => $this->_formatNumber2Bit($info['returnTotal']),
                    'combinedFollowNum' => intval($info['totalPopularity']),
                    'headImg' => $userinfo['face'],
                );
            
            }
            //设置一天的cache
            S($cachekey,$data,86400);
            return $data ? $data : array();
        }
    }

    public function getportfoliotopFromAdmin(){
        //@Jay 2016-02-29  PM要求去刷新更新，去缓存
        //$cachekey = 'getportfoliotopFromAdmin';
        //$data = S($cachekey);
        // if(!empty($data)){
        //     return $data;
        // }else{
            $lists = D('LandingPagePortfolios', 'admin')->getPortfolios();
            foreach ($lists as $key => $v) {
                $data[] = array(
                    'area' => $v['type'],
                    'combinedHref' => $v['phref'],
                    'combinedAuthor' => $v['username'],
                    'combinedName' => $v['pname'],
                    'percentage' => $v['total'],
                    'combinedFollowNum' => $v['fans'],
                    'headImg' => $v['face'],
                );
            }
            //设置一天的cache
            //S($cachekey,$data,86400);
            return $data ? $data : array();
        //}
    }
    private function _getEPAMPortfolioInfoByPid($portfolioId){
        $HOST = C('FINANCE_SYSTEM_DOMAIN').':8080';
        $API= '/portfolio/api/portfolios/';
        $PARAM = "$portfolioId";
        $url = 'http://'.$HOST.$API.$PARAM;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT,6);  //超时时间  
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }
    private function _formatNumber2Bit($number) {
        return $number ? sprintf("%1\$.2f", $number) : '0.00';
    }

}
<?php

/**
 * Class ImLastVersionModel
 * @name 明星播主
 * @author romantingmr
 * @version 20170508
 */
class ImStarLiveHostModel extends Model {
    protected $tableName = 'star_live_host';

    /**
     * @name 获取当前时间，往前一个月的vip微博
     * @return array|mixed
     */
    public function getVipWeiBo()
    {
        $startTime = strtotime('-3 month',time());
        $endTime = strtotime('-1 day',time());
        $sql = "SELECT
                    relate.weibo_id
                    ,relate.uid
                    ,link.stockId
                    ,link.ctime
                    ,link.mood_stock_price
                FROM
                `ts_mb_weibo_vip_relate`
                AS relate
                INNER JOIN
                `ts_mb_weibo_stock_link`
                AS link
                ON relate.weibo_id = link.weibo_id
                WHERE relate.`status` = 1
                AND link.ctime BETWEEN {$startTime} AND {$endTime}
                AND link.isdel = 0
                ORDER BY uid DESC";
        $data = M()->query($sql);
        return $data ? $data : array();
    }

    public function formatWeiBoData(&$data)
    {
        if(is_array($data) && isset($data)){
            $i = 0;
            foreach($data as $key=>$rw){
                if($data[$key-1]['uid'] == $data[$key]['uid'])
                    $i++;
                else
                    $i=0;
                $stockCode = D('StockDb','investment')->baseInfoById($rw['stockId']);
                if(empty($stockCode['StockCode'])) continue;
                $price = D('StockSnapSv','investment')->getStockSnap($stockCode['StockCode']);
                //收益
                $gain = round((doubleval($price['lastpx']/10000) - doubleval($rw['mood_stock_price']))/$rw['mood_stock_price'],2);
                $tempArr = array(
                    'weibo_id' => $rw['weibo_id'],
                    'uid' => $rw['uid'],
                    'stockId' => $rw['stockId'],
                    'mood_stock_price' => $rw['mood_stock_price'],
                    'gain' => $gain,
                    'ctime' => $rw['ctime']
                );
                $newData[$rw['uid']][$i] = $tempArr;
            }
            foreach($newData as $uid => $col){
                    $retData = $this->sortDataByGain($col);
                    $list[$uid] = $retData[0];
            }
            unset($tempArr,$data,$newData);
            return $list;
        }
        return;
    }

    private function sortDataByGain($data)
    {

        $max = count($data);
        $pos = 0;
        if(is_array($data) && !empty($data)){
            foreach($data as $key=>$rw){
	            $flag = 0;
                for($j = 0;$j<$max;$j++){
                    if($data[$j]['gain'] < $data[$j+1]['gain']){
                        $tempArr = $data[$j];
                        $data[$j] = $data[$j+1];
                        $data[$j+1] = $tempArr;
                        $pos = $j;
                        $flag = 1;
                    }
                }
                $max = $pos;
                if($flag == 0){
                   return $data;
                }
            }
        }
        return $data;
    }

    public function addData($data=array())
    {
        if(empty($data))
            Log::write('[halt] IM the empty data'. ' > ' .date('Ymd'));
        return $this->add($data);
    }

    public function getList($cond=array(),$fields="*",$sort='gain DESC',$limit=20)
    {
        $data = $this->field($fields)->where($cond)->order($sort)->limit($limit)->select();
        return $data;
    }

    public function delRow($cond=array())
    {
        if(empty($cond)) return false;
        $this->where($cond)->delete();
    }

    public function trunCateTable()
    {
        $this->execute('TRUNCATE TABLE ts_star_live_host');
    }

    public function getListBySetCond($limit = 10)
    {
        $searchTime = date('Ymd',strtotime('-1 month',time()));
        $sql = "SELECT
                    c.ctime,
                    c.gain,
                    d.uid,
                    d.weibo_id
                FROM
                    ts_weibo AS d
                INNER JOIN (
                    SELECT
                        max(a.weibo_id) AS weibo_id,b.gain,b.ctime
                    FROM
                        ts_weibo AS a
                    INNER JOIN ts_star_live_host AS b ON a.uid = b.uid
                    WHERE
                        b.gain >= 0.05
                    GROUP BY
                        a.uid 
                ) AS c ON d.weibo_id = c.weibo_id 
                WHERE FROM_UNIXTIME(d.ctime,'%Y%m%d')>= {$searchTime}
                LIMIT {$limit}";
        $result = $this->query($sql);
        return $result;
    }
}
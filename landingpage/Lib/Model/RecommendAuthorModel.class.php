<?php
/**
 * @name landing page 推荐5个用户
 * @author romantingmr
 * @version 2015-11-3 2016-01-29
 */
class RecommendAuthorModel extends Model 
{
	private $_myRedis=null;
	private $redisDb = 0;
	protected $tableName = 'longweibo_info';

	public function _initialize()
	{
		if($this->_myRedis === null) {
			require_cache(VENDOR_PATH . 'libs/redis/RedisLong.class.php');
			$this->_myRedis = RedisLong::getInstance();
		}
	}

	public function getUidByHeat($cond,$sort="heat DESC")
	{
	//	$data = S('landingPage_recommendAuthor_all');
		if($data) return $data;
		
		$lwbSql = "SELECT a.uid,a.title,a.weibo_id FROM ts_longweibo_info AS a INNER JOIN (SELECT weibo_id FROM ts_longweibo_info WHERE title!='' ORDER BY heat DESC LIMIT 150) AS b ON a.weibo_id=b.weibo_id GROUP BY a.uid LIMIT 80";
		$lwbUidList = $this->query($lwbSql);
		$i=1;
		foreach ($lwbUidList as $key=>$data)
		{
			$speUser= M('longweibo_user')->field('uid')->where(array('id_status'=>2,'uid'=>$data['uid']))->count();
			if(empty($speUser))
			{
				$verifyList = M('user_verified')->field('uid')->where(array('verified'=>'1','uid'=>$data['uid']))->count();
				if(empty($verifyList)) continue;
			}			
			if($i==6) break;		
			$uname = M('user')->field("uname")->where(array('uid'=>$data['uid']))->find();
			$uidList['uid'.$i]['name'] = $uname['uname'];
			$uidList['uid'.$i]['uid'] = $data['uid'];
			$uidList['uid'.$i]['title'] = $data['title'];
			$uidList['uid'.$i]['img'] = getUserFace($data['uid']);
			$uidList['uid'.$i]['href'] = U('viewpoint/longweibo/index',array('weibo_id'=>$data['weibo_id']));			
			$i++;
		}
		$uidList['profile']='高手助你一臂之力，摇身一变炒股达人。';
		S("landingPage_recommendAuthor_all",json_encode($uidList),86400);
		return json_encode($uidList);
	}

	//改为取redis的数据
	public function  getUidByHeatV2($type=1,$limit=100)
	{
		$data = S('landingPage_recommendAuthor_all_V2');
		if($data) return $data;
	    $result = $this->getHotActicle($type,$limit);
		$i = 1;
		foreach($result as $key=>$row)
		{
            $data[$row['uid']][$i]['name'] = $row['uname'];
			$data[$row['uid']][$i]['uid'] = $row['uid'];
			$data[$row['uid']][$i]['title'] = $row['title'];
			$data[$row['uid']][$i]['img'] = getUserFace($row['uid']);
			$data[$row['uid']][$i]['href'] = U('viewpoint/longweibo/index',array('weibo_id'=>$row['weibo_id']));	;
			$i++;
		}
		$i = 1;
		foreach($data as $val)
		{
			if($i==6) break;
			$j=0;
			foreach($val as $key=>$row){
				if($j==1) break;
				$uidList['uid'.$i] = $row;
				$j++;
			}
			$i++;
		}
		$uidList['profile']='高手助你一臂之力，摇身一变炒股达人。';
		S("landingPage_recommendAuthor_all_V2",json_encode($uidList),86400);
		return $uidList;
	}

	/*
     * 获取热门观点列表
     * @param $type int 类型：1、热度，2、收益率，3、人气值，4、销售量
     * @param $start int 集合成员开始位置
     * @param $end int 集合成员结束位置
     * @param $strCut int 名字最大显示字数
     */
	private function getHotActicle($type=1,$limit=100){
		$fields = array(
			1=>"heat",
			"revenue",
			"popularity",
			"sale_count"

		);
		$result = $res = array();
		$rsKey = 'rsz_lwb_info_heat';
		$this->_myRedis->select($this->redisDb);
		$result = $this->_myRedis->zRevRange($rsKey,0,$limit-1);

		if(!empty($result))
		{
			foreach($result as $key=>$weiboId)
			{
				$lwbInfo = $this->table($this->trueTableName)
					->where(array('weibo_id'=>$weiboId,"isdel"=>0))
					->find();
				if($lwbInfo){
					$res[] = $lwbInfo;
				}
			}
			unset($result);
			foreach($res as &$row)
			{
				$row['uname'] = getUserName($row['uid']);
			}
			return $res;
		}

		if(0 > $type || $type > count($fields)){
			$type = 1;
		}
		$field = $fields[$type];
		$result = $this->table($this->trueTableName." as a")
			->order($field." desc")
			->where(array("isdel"=>0))
			->limit($limit)
			->select();
		foreach($res as &$row)
		{
			$row['uname'] = getUserName($row['uid']);
		}
		return $result;
	}
}

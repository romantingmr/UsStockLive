<?php

/**
 * Class UserUsStockWbRdsModel
 * @name 用户与美股直播相关内容的关系
 * @author romantingmr
 * @version 20170623
 */
class UserUsStockWbRdsModel extends Model
{
    protected $autoCheckFields = false;
    private $_rdsObj = null;
    private $rdsDb = 5;
    private $uWbKey = 'rsh_user_uwb_last_id';
    private $uVsKey = 'rsh_user_vis_last_id';

    public function _initialize()
    {
        if($this->rdsObj === null){
            require_cache(VENDOR_PATH.'libs/redis/RedisLong.class.php');
            $this->_rdsObj = RedisLong::getInstance();
        }
    }

    public function setUserWbLastId($uid,$weiBoId)
    {
        if(empty($uid) || empty($weiBoId))
            return false;
        $this->_rdsObj->select($this->rdsDb);
        $res = $this->_rdsObj->hSet($this->uWbKey,$uid,$weiBoId);
        return $res ? $res : false;
    }

    public function getUserWbLastId($uid)
    {
        if(empty($uid))
            return false;
        $this->_rdsObj->select($this->rdsDb);
        $res = $this->_rdsObj->hGet($this->uWbKey,$uid);
        return $res ? $res : 0;
    }

    public function setUserVsLastId($uid,$visNum)
    {
        if(empty($uid) || empty($weiBoId))
            return false;
        $this->_rdsObj->select($this->rdsDb);
        $res = $this->_rdsObj->hSet($this->uVsKey,$uid,$visNum);
        return $res ? $res : false;
    }

    public function getUserVsLastId($uid)
    {
        if(empty($uid))
            return false;
        $this->_rdsObj->select($this->rdsDb);
        $res = $this->_rdsObj->hGet($this->uVsKey,$uid);
        return $res ? $res : 0;
    }
}
<?php

/**
 * Class UserStockRelModel
 * @name 用户自选股数据模型
 */
class UserStockRelModel extends Model {
    protected $tableName = 'user_stock_rel';

    public function addData($data)
    {
        if(empty($data))
            return false;
        return $this->add($data);
    }

    public function getStockByUid($mid,$fields,$sort)
    {
        if(empty($mid)) return false;
        $cond = ['uid'=>$mid];
        $result = $this->field($fields)->where($cond)->order($sort)->select();
        return $result ? $result : array();
    }

    public function delStocks($cond=array())
    {
        if(empty($cond))
            return false;
        return $this->where($cond)->delete();
    }

    public function addAllData($data)
    {
        if(empty($data))
            return false;
        return $this->addAll($data);
    }

    public function upStocks($id)
    {
        if(empty($id))
            return false;
        $cond['id'] = $id;
        $saveData['ctime'] = time();
        $res = $this->where($cond)->save($saveData);//更新需要置顶的id
        return $res ? true : false;
    }

    public function getMaxIdByUid($mid)
    {
        if(empty($mid))
            return false;
        $cond = ['uid'=>$mid];
        $result = $this->field(' MAX(id) AS id')->where($cond)->find();
        return $result;
    }

    public function getOneById($id)
    {
        if(empty($id))
            return false;
        $cond = ['id'=>$id];
        $result = $this->field('name,symbol')->where($cond)->find();
        return $result;
    }

    public function isMaxIdByUid($id,$mid)
    {
        if(empty($mid))
            return false;
        $result = $this->getMaxIdByUid($mid);
        if($result['id'] == $id)
            return true;
    }
}
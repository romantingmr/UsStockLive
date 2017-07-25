<?php

/**
 * Class A50LiveHostTagRelationModel
 * @name 开通A50直播专家与标签关系
 * @author romantingmr
 * @version 20170621
 */
class A50LiveHostTagRelationModel extends Model {
    protected $tableName = 'tag_us_stock';

    public function addData($data=array())
    {
        if(empty($data)) return false;
        return $this->add($data);
    }

    public function getOne($cond=array(),$fields="*")
    {
        if(empty($cond)) return false;
        $data = $this->field($fields)->where($cond)->find();
        return $data;
    }

    public function getData($cond=array(),$fields="*")
    {
        if(empty($cond)) return false;
        $data = $this->field($fields)->table('ts_'.$this->tableName.' AS us ')->join(' ts_tag_info AS info ON info.tag_id = us.tid','INNER')->where($cond)->select();
       // echo $this->getLastSql();
        return $data;
    }

    public function delData($cond=array())
    {
        if(empty($cond)) return false;
        $this->where($cond)->delete();
    }
}
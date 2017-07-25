<?php

/**
 * Class UserOpenDiscountModel
 * @name 用户提供手机号码
 * @author romantingmr
 * @version 20170721
 */
class UserOpenDiscountModel extends Model {
    protected $tableName = 'user_open_discount';

    public function addData($data)
    {
        if(empty($data))
            return false;
        return $this->add($data);
    }

    public function getOne($cond=[],$fields='*')
    {
        if(empty($cond)) return false;
        $result = $this->field($fields)->where($cond)->find();
        return $result ? $result : [];
    }

    public function getPage($cond=[],$limit=20)
    {
        if(empty($cond))
            return false;
        return $this->where($cond)->findPage($limit);
    }
}
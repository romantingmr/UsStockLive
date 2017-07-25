<?php

/**
 * Class PortfolioActLogModel
 * @name 组合活动日志
 */
class PortfolioActLogModel extends Model {
    protected $autoCheckFields = false;

    private $_stkDB = null;

    private $msgCollection = 'cs_portfolio_stock_sea';

    public function _initialize() {

        if($this->_stkDB == null) {
            require_cache(VENDOR_PATH . 'libs/mongo/MongoImaibo.class.php');
            $this->_stkDB = MongoImaibo::getInstance();
            $this->_stkDB->selCollection($this->msgCollection);
        }

    }

    public function __destruct() {
        $this->_stkDB->destructClose();
    }


    public function getOrdersInfo($cond,$page,$limit){
        $this->_stkDB->selCollection($this->msgCollection);
        $fields = ['mid','mobile','ctime'];
        $options['limit'] = $limit;
        $options['sort'] = ['_id'=>-1];
        $options['skip'] = ($page-1)*$limit;
        return $this->_stkDB->mgFind2($cond,$options,$fields,false);

    }

    public function getOrdersCount($cond)
    {
        $this->_stkDB->selCollection($this->msgCollection);
        return $this->_stkDB->mgCount($cond);
    }

}
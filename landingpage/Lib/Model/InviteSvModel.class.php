<?php

/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2016/6/8
 * Time: 16:21
 */
class InviteSvModel extends Model
{
    private $_stkDB = null;

    private $relationCollection = 'cs_invite_register_relation';
    private $rewardRecordCollection = 'cs_invite_register_reward_record';

    public function _initialize() {

        if($this->_stkDB == null) {
            require_cache(VENDOR_PATH . 'libs/mongo/MongoImaibo.class.php');
            $this->_stkDB = MongoImaibo::getInstance();
        }

    }

    public function addInviteRegisterRelation($saveData){
        $this->_stkDB->selCollection($this->relationCollection);
        $res = $this->_stkDB->mgInsert($saveData);
        return $res;
    }

    public function addInviteRegisterRewardRecord($saveData){
        $this->_stkDB->selCollection($this->rewardRecordCollection);
        $res = $this->_stkDB->mgInsert($saveData);
        return $res;
    }

    public function getOneInviteInfo($cond){
        $this->_stkDB->selCollection($this->relationCollection);
        $res = $this->_stkDB->mgFindOne($cond);
        return $res;
    }

    public function getInviteInfoList($cond=array(),$fields = array(),$page = 1,$size = 0,$sortKey="_id",$sort=-1){
        $this->_stkDB->selCollection($this->relationCollection);
        if($sortKey){
            $options['sort'] = array($sortKey => $sort);
        }
        if($size){
            $page = $page>0?intval($page):1;
            $skip = ($page - 1) * $size;
            $options['skip']  = $skip;
            $options['limit'] = $size;
        }
        if(!$fields){
            $fields = array(
                "_id"=>false
            );
        }
        $res = $this->_stkDB->mgFind($cond,$options,$fields,false);
        return $res;
    }

    public function getOneInviteRewardRecord($cond){
        $this->_stkDB->selCollection($this->rewardRecordCollection);
        $res = $this->_stkDB->mgFindOne($cond);
        return $res;
    }

    public function getInviteeCount($uid){
        $this->_stkDB->selCollection($this->rewardRecordCollection);
        $cond = array(
            "inviter"=>intval($uid),
            "type"=>1
        );
        $res = $this->_stkDB->mgCount($cond);
        return $res;
    }

    public function getInviterStatistics($startTime){
        $this->_stkDB->selCollection($this->relationCollection);
        if($startTime){
            $cond[] = array(
                '$match' => array('ctime' => array('$gte' => $startTime)),
            );
        }
        $cond[] = array(
            '$group' => array(
                '_id' => '$inviter',
                'count' => array('$sum' => 1),
            ),
        );
        $res = $this->_stkDB->mgAggregate($cond);
        return $res;
    }

    public function coinRecharge($uid,$amount,$type=1){
        if(empty($uid) || empty($amount)) return false;
        $data['uid'] = $uid;
        $data['amount'] = intval($amount);
        $data['type'] = intval($type);
        $data['status'] = 0; //赠送状态：0未赠送，1已赠送
        $data['rdate'] = intval(date("Ymd", time())); //赠送日期（YYYYMMDD）
        $data['ctime'] = time();
        return M("register_user_point_recharge")->add($data);
    }

    public function beanRecharge($inviter,$invitee){
        $inviter = intval($inviter);
        $invitee = intval($invitee);
        //最多邀请30个
        $inviteCount = $this->getInviteeCount($inviter);
        if($inviteCount >= 30){
            return false;
        }
        require_cache(VENDOR_PATH . 'libs/RedisLock.class.php');//进程锁类
        $lock = new RedisLock();//指定草根股神用户级别进程锁
        $key = 'lock_invite_register_user'.$inviter;
        $lock->lock($key);//开启进程锁
        $cond = array(
            "inviter"=>$inviter,
            "invitee"=>$invitee,
            "type"=>1
        );
        $rewardRecord = $this->getOneInviteRewardRecord($cond);
        if($rewardRecord){
            $lock->unlock($key);//解锁
            return false;
        }
        $url = "http://".C('SF_SITE_HOST')."/imaibo/api/addPoint";
        $post = array(
            "uid"=>$inviter,
            "ruleId"=>9,
            "type"=>5,
            "sign"=>sha1($inviter."95IMAIBO_API")
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        curl_setopt($ch, CURLOPT_POST, 1);//post方式提交
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));//要提交的信息
        $rs = curl_exec($ch); //执行cURL抓取页面内容
        curl_close($ch);
        $rs = json_decode($rs,1);
        if($rs['code']==200){
            $rewardRecordData = array(
                "inviter"=>$inviter,
                "invitee"=>$invitee,
                "type"=>1,
                "ctime"=>time()
            );
            $this->addInviteRegisterRewardRecord($rewardRecordData);
            //发送系统消息
            $message = array(
                'title'   => "邀请好友成功", //必填 标题
                'message' => getUserName($invitee)."已通过你的邀请成为投资脉搏的一员！邀请注册脉豆已到账。", //必填 内容
                'touid' => array($inviter), // 非必填，发送给谁 (群发时不需要这个参数)
                'type' => 2, // 必填 发送类型[ 1(群发) | 2(单发) ]
            );
            $res = D('UserSysMsgSv','user')->addMsg($message);
        }
        $lock->unlock($key);//解锁
        return true;
    }
}
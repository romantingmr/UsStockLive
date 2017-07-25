<?php

class OauthQQModel {

    public function getUid($openId,$type){
        switch ($type) {
            case 'qq':
                $platformId = 3;
                break;
            default:
                return false;
                break;
        }
        $appId = 0;
        if($type == 'qq'){
            $appId = 101037320;
        }
        $paoid = $platformId.'-'.$appId.'-'.$openId;
        //查redis
        $relation = model('OpUserRelationRds')->getAllRelation($paoid);
        foreach ($relation as $r) {
            $arr = explode('-', $r);
            if(intval($arr[0]) == 1){
                return intval($arr[2]);
            }
        }
        //查mysql
        $msInfo = M('login')->where(array('uid'=>$openId,'type'=>$type))->getField('uid');
        if($msInfo){
            return intval($msInfo);
        }
        //查mongo   
        $userInfo = model('Authorize')->getInfoByOpenid($openId, $type);
        if(!empty($userInfo['uid'])){
            return intval($userInfo['uid']);
        }
        //最后大招查ts_user表
        //生成邮箱
        $email = service('OpUser')->buildEmail($platformId,$appId,$openId);
        $user = D('User', 'home')->getUserByIdentifier($email, 'email');
        if(!empty($user) && $user['is_active']){//匹配到对应投资脉搏账号 !!!
            //加redis
            model('OpUserRelationRds')->createRelation($paoid,'1-0-'.$user['uid']);//关联账号
            return intval($user['uid']);
        }
        return 0;
    }

}
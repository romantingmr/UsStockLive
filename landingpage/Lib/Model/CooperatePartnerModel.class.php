<?php
/**
 * @name 合作伙伴广告图标
 * @author romantingmr
 * @version 2015-11-4
 */
class CooperatePartnerModel extends Model 
{
	protected $tableName = "advertisement_info";
	
	public function getAdverInfo($cond=array(),$sort="adv_sort ASC")
	{
		$data = S('landingPage_CooperatePartner_all');
		if($data) return $data;
		
		$adverList = $this->where($cond)->order($sort)->select();
		foreach ($adverList as $key=>$row)
		{
			$adverList[$key]['adv_img_url'] = IMG_SITE_URL."/".$row['adv_img_url'];
		}
		S("landingPage_CooperatePartner_all",json_encode($adverList),86400);
		return json_encode($adverList);
	}
}

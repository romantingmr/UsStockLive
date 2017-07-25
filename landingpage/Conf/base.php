<?php
include (dirname(dirname(dirname(__FILE__)))."/viewpoint/Conf/base.php");
$documentMeta = array(
     'title' => '投资脉搏 - 让炒股更简单',
     'meta' => array(
        array('name'=>'keywords','content'=>'股票微博，投资组合，股市观点，投资脉搏，A股，投资理财'),
        array('name'=>'description','content'=>'国内最专业的互动式股票投资交流平台，为投资者提供最新股市行情、投资组合、专家观点、心情指数等创新式理财服务，加入投资脉搏，与众多股市高手、投资顾问一同分享交流！'),
        array('name'=>'url','content'=>SITE_URL),

     )
);
$fis_data = array(
'documentMeta'=>$documentMeta,
'config'=>$config,
'navigator'=>$navigator,
'floor'=>array('floor1'=>array('name'=>'投资组合','href'=>'f1.com'),'floor2'=>array('name'=>'市场趋势','href'=>'f2.com'),'floor3'=>array('name'=>'投资观点','href'=>'f3.com'),'floor4'=>'合作伙伴'),
'header'=>array('title'=>'投资脉搏','profile'=>'实力非凡的金融社区，大开你的投资眼界。','imgEwmSrc'=>'/imaiboPC/test/imaibo/langing_ewm.png'),
'landingNavInfo'=>array('title1'=>'投资组合','profile1'=>"明智的资产管理",'title2'=>'市场趋势','profile2'=>"大数据量化预测",'title3'=>'投资观点','profile3'=>"专业的投资策略"), //
'langingPortfolio'=>array(
                'profile'=>'用超前的投资理念，精心配置专属你的股票组合。',
                'list'=>array(
                 array('area'=>'泸深','percentage'=>'396.46','combinedName'=>'推到重来','combinedAuthor'=>'鼠小易','combinedFollowNum'=>'123','combinedHref'=>'122'),
                 array('area'=>'泸深','percentage'=>'396.46','combinedName'=>'推到重来','combinedAuthor'=>'鼠小易','combinedFollowNum'=>'123','combinedHref'=>'2'),
                 array('area'=>'泸深','percentage'=>'396.46','combinedName'=>'推到重来','combinedAuthor'=>'鼠小易','combinedFollowNum'=>'123','combinedHref'=>'24')
				 ) 
                ), //投资组合
'langingMarketTrends'=>array(
                'profile'=>'用超前的投资理念，精心配置专属你的股票组合。',
                'list'=>array(
                 array('area'=>'bj','stockName'=>'弘高创意','percentage'=>'22.22','stockState'=>'up','stockUrl'=>'http://baidu.com'), //北京
                 array('area'=>'sh','stockName'=>'延华智能','percentage'=>'22.22','stockState'=>'down','stockUrl'=>'http://baidu.com'), //上海
                 array('area'=>'gd','stockName'=>'中国平安','percentage'=>'22.22','stockState'=>'up','stockUrl'=>'http://baidu.com'), //广东
                 array('area'=>'cq','stockName'=>'重庆水务','percentage'=>'22.22','stockState'=>'down','stockUrl'=>'http://baidu.com'), //重庆
                 array('area'=>'xz','stockName'=>'八一钢铁','percentage'=>'22.22','stockState'=>'up','stockUrl'=>'http://baidu.com') //西藏
                 )
				 ), //市场趋势
'langingView'=>array(
                'profile'=>'高手助你一臂之力，摇身一变炒股达人。',
                'uid1'=>array('name'=>'作者名','title'=>'标题名称','href'=>'xxxxx.com'),
                'uid2'=>array('name'=>'作者名','title'=>'标题名称','href'=>'xxxxx.com'), 
                'uid3'=>array('name'=>'作者名','title'=>'标题名称','href'=>'xxxxx.com'), 
                'uid4'=>array('name'=>'作者名','title'=>'标题名称','href'=>'xxxxx.com'), 
                'uid5'=>array('name'=>'作者名','title'=>'标题名称','href'=>'xxxxx.com')		 
                ), //投资观点       
'langingPartners'=>array(
                 array('adv_img_url'=>'/imaiboPC/test/imaibo/langing_partners_test.png','adv_url'=>'http://baidu.com'), 
                 array('adv_img_url'=>'/imaiboPC/test/imaibo/langing_partners_test.png','adv_url'=>'http://baidu.com'), 
                 array('adv_img_url'=>'/imaiboPC/test/imaibo/langing_partners_test.png','adv_url'=>'http://baidu.com'), 
                 array('adv_img_url'=>'/imaiboPC/test/imaibo/langing_partners_test.png','adv_url'=>'http://baidu.com'), 
                 array('adv_img_url'=>'/imaiboPC/test/imaibo/langing_partners_test.png','adv_url'=>'http://baidu.com'), 
                 array('adv_img_url'=>'/imaiboPC/test/imaibo/langing_partners_test.png','adv_url'=>'http://baidu.com'), 
				 ), //合作伙伴                
                
);

?>

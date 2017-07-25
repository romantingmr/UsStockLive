<?php
/**
 * @name 用户昵称
 * @author romantingmr
 */
function getUserNameByLoop(&$result){
    foreach($result as $key => &$res){
        $userName = getUserName($res['uid']);
            $res['uname'] = $userName;
    }
}
/**
 * 生成查询条件和limit
 *
 * @param string $idName 主键名
 * @param string $defaultLimit 返回记录行数
 */
function buildWhereAndLimitMap($idName = 'id', $defaultLimit = 10) {
    $map = array();
    $sinceId = $_REQUEST['since_id'] ? intval($_REQUEST['since_id']) : 0;
    $maxId = $_REQUEST['max_id'] ? intval($_REQUEST['max_id']) : 0;
    $size = $limit = $_REQUEST['limit'] ? intval($_REQUEST['limit']) : $defaultLimit;
    $p = $_REQUEST['p'] ? intval($_REQUEST['p']) : 1;

    if($sinceId) { // 有since_id则limit和p无效
        $map[$idName] = array('gt', $sinceId);
        $limit = 999;
    } else if($maxId) { // 有max_id则p无效
        $map[$idName] = array('lt', $maxId);
        $p = 1;
    }
    $limit = (($p - 1) * $limit) . ',' . $limit;

    return array('where' => $map, 'limit' => $limit, 'size' => $size, 'page' => $p);
}

/**
 * 生成查询条件和limit
 *
 * @param string $idName 主键名
 * @param string $defaultLimit 返回记录行数
 */
function buildWhereAndLimitString($idName = 'id', $defaultLimit = 10) {
    $condition = '';
    $sinceId = $_REQUEST['since_id'] ? intval($_REQUEST['since_id']) : 0;
    $maxId = $_REQUEST['max_id'] ? intval($_REQUEST['max_id']) : 0;
    $size = $limit = $_REQUEST['limit'] ? intval($_REQUEST['limit']) : $defaultLimit;
    $p = $_REQUEST['p'] ? intval($_REQUEST['p']) : 1;

    if($sinceId) { // 有since_id则limit和p无效
        $condition = ' and ' . $idName . '>' . $sinceId;
        $limit = 999;
    } else if($maxId) { // 有max_id则p无效
        $condition = $condition . ' and ' . $idName . '<' . $maxId;
        $p = 1;
    }
    $limit = (($p - 1) * $limit) . ',' . $limit;

    return array('where' => $condition, 'limit' => $limit, 'size' => $size, 'page' => $p);
}

/**
 * 检查数据库查询返回的结果
 *
 * @param array $list
 */
function checkQueryResult($list) {
    if($list === false) {
        outputAdaptor(C('STATUS_CODE.QUERY_ERROR'), '查询失败');
    }
    if(count($list) == 0) {
        outputAdaptor(C('STATUS_CODE.NO_RESULT'), '查询无记录');
    }
    return true;
}

/**
 * 过滤A标签
 */
function filter_tag_a($matches) {
    $isMember = strpos($matches[0], 'http://' . $_SERVER['HTTP_HOST']);
    if($isMember && (strpos($matches[0], '/stock') || strpos($matches[0], '/live')  || strpos($matches[0], '/space') || strpos($matches[0], '/portfolio'))){ // 股票、直播、@用户 的链接、投资组合
        $content = str_ireplace('http://' . $_SERVER['HTTP_HOST'], 'imaibo:/', $matches[0]);
        $stockIndex = strpos($content, '/stock');
        if($stockIndex !== false) { // 股票链接
            $subContent = strstr($content, 'data-stock-id=');
            $subContent = substr($subContent, 15);
            $stockId = substr($subContent, 0, strpos($subContent, '"'));
            $subContent2 = strstr($content, 'data-stock-code=');
            $subContent2 = substr($subContent2, 17);
            $stockCode = substr($subContent2, 0, strpos($subContent2, '"'));
            $content = substr_replace($content, $stockId . '/', $stockIndex + 7, 1);

            $content = preg_replace('/imaibo:\/\/stock\/[0-9]' . $stockCode . '/iu', 'imaibo://stock/' . $stockId . '/' . $stockCode, $content);
        }
        return $content;
        // @Jay 注释下面话题标签的剥离
        // }else if(strpos($matches[0], '/topics/')){//话题
        // 	return strip_tags($matches[0]);
    }else{
        $ver = $_REQUEST['versionCode'];
        if(!empty($ver) && intval($ver)>=3303){//新版本将长文地址改成特定的自定义协议
            $modelShortlink = D('ShortlinkMappingMg','csi');
            $displayUrl = strip_tags($matches[0]);
            $orgUrl = $modelShortlink->getOrglinkByShortlink($displayUrl);
            if($orgUrl){
                $customProtocol = $modelShortlink->buildCustomProtocol($orgUrl);
                if($customProtocol) return str_ireplace('href="'.$displayUrl, 'href="'.$customProtocol, $matches[0]);
            }
        }
        //return str_ireplace('href="https://', 'href="imaibo://surl/', str_ireplace('href="http://', 'href="imaibo://url/', $matches[0]));
        //@jay 2016-06-03 改surl为urls，APP文档是urls,将错就错
        return str_ireplace('href="https://', 'href="imaibo://urls/', str_ireplace('href="http://', 'href="imaibo://url/', $matches[0]));
    }
}

function filter_tag_a4ios($matches) {
    $isMember = strpos($matches[0], 'http://' . $_SERVER['HTTP_HOST']);
    if($isMember){ // 股票、直播、@用户 的链接
        $content = $matches[0];
        $stockIndex = strpos($content, '/stock');
        if($stockIndex !== false) { // 股票链接
            $subContent = strstr($content, 'data-stock-id=');
            $subContent = substr($subContent, 15);
            $stockId = substr($subContent, 0, strpos($subContent, '"'));
            $subContent2 = strstr($content, 'data-stock-code=');
            $subContent2 = substr($subContent2, 17);
            $stockCode = substr($subContent2, 0, strpos($subContent2, '"'));
            $content = substr_replace($content, $stockId . '/', $stockIndex + 7, 1);

            $content = preg_replace('/\/stock\/[0-9]' . $stockCode . '/iu', '/stock/' . $stockId , $content);
        }
//		$topicIndex = strpos($content, '/user/topics');
//		$undefined = strpos($content, '/index.php?app=');
//		$hexkIdx = strpos($content, 'hkex/');
//		if($topicIndex !== false || $undefined !== false || $hexkIdx !== false) { // 话题链接&不可识别的链接
//			return strip_tags($content);
//		}
        return $content;
    }
    return $matches[0];
}

function unityStockSign4ios($matches) {
    if($matches[0]) {
        $string = $matches[0];
        $string = str_replace(array('{','['), '(', $string);
        $string = str_replace(array('}',']'), ')', $string);

        return $string;
    }
}

function decideStkMarket($stockCode) {
    $code = strpos($stockCode, '6');
    if($code === 0 ) {
        return 'SHA:' . $stockCode;
    }
    $code1 = strpos($stockCode, '0');
    $code2 = strpos($stockCode, '3');
    if(($code1 === 0) || ($code2 === 0)) {
        return 'SZA:' . $stockCode;
    }
    return $stockCode;
}

function isAjax() {
    if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
        if('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
            return true;
    }
    if(!empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')]))
        // 判断Ajax方式提交
        return true;
    return false;
}

// 格式化内容
function weiboContentFormat($content) {
    if(isAjax()){
        return $content;
    }
    if($_REQUEST['reqfrom']==3 && $_REQUEST['versionCode'] < 3610){
        return weiboContentFormatIOS2($content);
    }

    if($_REQUEST['reqfrom']==3 && $_REQUEST['versionCode'] <= 3710){
        $content = preg_replace_callback("/<img\s+src='http:\/\/" . $_SERVER['HTTP_HOST']."\/public\/themes\/Maibo\/images\/expression\/miniblog\/([0-9a-z_]+)?\.gif' \/>/iu", filterFace, $content);
    }
    if($_REQUEST['reqfrom']==2 && $_REQUEST['versionCode'] >= 4310){ //安卓 >=4310 处理表情 img 转 []
        $content = preg_replace_callback("/<img\s+src='http:\/\/" . C('SF_SITE_HOST') ."\/public\/themes\/Maibo\/images\/expression\/miniblog\/([0-9a-z_]+)?\.gif' \/>/iu", filterFace, $content);
    }elseif($_REQUEST['versionCode'] >= 4400){ //4400 处理表情 img 转 []
        $content = preg_replace_callback("/<img\s+src='http:\/\/" . C('SF_SITE_HOST') ."\/public\/themes\/Maibo\/images\/expression\/miniblog\/([0-9a-z_]+)?\.gif' \/>/iu", filterFace, $content);
    }
    return preg_replace_callback("/<a\s+.+?>.+?<\/a>/iu", filter_tag_a, $content); // 过滤A标签
}

/**
 * 用于IOS的过滤内容方法
 * 141101 把股票的格式全部转成 $股票名称(股票代码)$ 的格式
 * @param unknown $content
 */
function weiboContentFormatIOS2($content) {
    $content = preg_replace_callback("/<img src='http:\/\/" . $_SERVER['HTTP_HOST']."\/public\/themes\/Maibo\/images\/expression\/miniblog\/([0-9a-z_]+)?\.gif' \/>/iu", filterFace4ios, $content);
    $regex = "/\\$(?:[^\s\\$]+?)[\\[\\{]{1}[0-9a-zA-Z]+?[\\]\\}]{1}\\$/is"; // 匹配全部的股票代码格式
    $content = preg_replace_callback("/<a\s+.+?>.+?<\/a>/iu", filter_tag_a4ios, $content); // 过滤A标签
    preg_replace_callback($regex, unityStockSign4ios, $content);

    return $content;
}

/**
 * 过滤微博内容里的表情图片以适配ios客户端
 * @param string $content
 */
function filterFace4ios($matches){
    return '['.$matches[1].']';
}

/**
 * 过滤微博内容里的表情图片以适配客户端
 * @param string $content
 */
function filterFace($matches){
    return '['.$matches[1].']';
}


/**
 * 格式化' 通过[草根股神]买入|卖出 '的文案
 *
 * @param unknown $content
 */
function weiboContentFormatCggs($content, $uid) {
    preg_match("/通过(\[草根股神\])(买入|卖出)/iu", $content, $match);
    if($match[0]) {
        $content = str_replace('#草根股神模拟盘#', '', $content);
        $pos1 = strpos($content, '股。<a href="imaibo://url/t.cn');
        if($pos1 !== false) {
            $content = substr($content, 0, $pos1 + 6);
        }

        $pos2 = strpos($content, '%。<a href="imaibo://url/t.cn');
        if($pos2 !== false) {
            $content = substr($content, 0, $pos2 + 6);
        }

        $userInfo = getUserInfo($uid);
        $content = str_replace($match[1], '<a href="imaibo://cggs/' . $userInfo['uid'] . '/' . $userInfo['uname'] . '/' . $userInfo['sex'] . '">' . $match[1] . '</a>', $content);
        $return['content'] = $content;
        $return['isCggsTrading'] = 1;
        $return['cggsTradingType'] = ($match[2] == '买入') ? 1 : 2;
    } else {
        $return['content'] = $content;
        $return['isCggsTrading'] = 0;
        $return['cggsTradingType'] = 0;
    }

    return $return;
}

function weiboContentFormatCggs2(&$weibo) {
    if($_REQUEST['reqfrom']==3){
        return false;
    }
    $content = $weibo['content'];
    preg_match("/通过(\[草根股神\])(买入|卖出)/iu", $content, $match);
    if($match[0]) {
        $content = str_replace('#草根股神模拟盘#', '', $content);
        $pos1 = strpos($content, '股。<a href="imaibo://url/t.cn');
        if($pos1 !== false) {
            $content = substr($content, 0, $pos1 + 6);
        }

        $pos2 = strpos($content, '%。<a href="imaibo://url/t.cn');
        if($pos2 !== false) {
            $content = substr($content, 0, $pos2 + 6);
        }

        $userInfo = getUserInfo($weibo['uid']);
        $content = str_replace($match[1], '<a href="imaibo://cggs/' . $userInfo['uid'] . '/' . $userInfo['uname'] . '/' . $userInfo['sex'] . '">' . $match[1] . '</a>', $content);
        $weibo['content'] = $content;
        $weibo['isCggsTrading'] = 1;
        $weibo['cggsTradingType'] = ($match[2] == '买入') ? 1 : 2;
    } else {
        $weibo['content'] = $content;
        $weibo['isCggsTrading'] = 0;
        $weibo['cggsTradingType'] = 0;
    }
}

// 微博图片转换成绝对路径
function typeDataFormat(&$typeData) {
    if(!empty($typeData)) {
        if(!is_array($typeData))
            $typeData = unserialize($typeData);
        if($typeData['picurl']) {
            if(strrpos($typeData['picurl'], 'http://') === false)
                $typeData['picurl'] = getImageServerUrl($typeData['picurl']);
            if(strrpos($typeData['thumbmiddleurl'], 'http://') === false)
                $typeData['thumbmiddleurl'] = getImageServerUrl($typeData['thumbmiddleurl']);
            if(strrpos($typeData['thumburl'], 'http://') === false)
                $typeData['thumburl'] = getImageServerUrl($typeData['thumburl']);
        } else if(count($typeData) > 1) { // 多张图片
            $typeData = array_values($typeData);
            foreach($typeData as &$v) {
                if(strrpos($v['picurl'], 'http://') === false)
                    $v['picurl'] = getImageServerUrl($v['picurl']);
                if(strrpos($v['thumbmiddleurl'], 'http://') === false)
                    $v['thumbmiddleurl'] = getImageServerUrl($v['thumbmiddleurl']);
                if(strrpos($v['thumburl'], 'http://') === false)
                    $v['thumburl'] = getImageServerUrl($v['thumburl']);
            }
        }
    }
    return $typeData;
}

// 微博来源分析
function getWeiboFrom($from) {
    $text = '';
    switch($from) {
        case 0:
            $text = '来自投资脉搏网';break;
        case 1:
            $text = '来自手机Web端';break;
        case 2:
            $text = '来自Android客户端';break;
        case 3:
            $text = '来自iPhone客户端';break;
    }
    return $text;
}

function mkTranspondCont($content, $uid) {
    $uname = getUserName($uid);
    if($_REQUEST['reqfrom']==3){
        $content = '<a href="http://'.$_SERVER['HTTP_HOST'].'/space/' . $uid . '">@' . $uname . '</a>：' . $content;
    }else{
        $content = '<a href="imaibo://space/' . $uid . '">@' . $uname . '</a>：' . weiboContentFormat(format($content,1));
    }
    return $content;
}

function getIndexNameByCode($stockCode) {
    switch($stockCode) {
        case '000001':

            return '上证指数';

        case '399001':
            return '深证指数';

        case '399006':
            return '创业板指';
        case '000016':
            return '上证50';
    }
}

function getActAnnSignStatus($time, $startTime, $endTime, $isJoin) {
    if($isJoin) {
        return 1; // 已参加
    } else if($time > $endTime) {
        return 2; // 已过期
    } else if($time >= $startTime && $time <= $endTime) {
        return 3; // 进行中&&未参加
    } else if($time < $startTime) {
        return 4; // 未开始
    }
}

function getActAnnBtnString($int) {
    switch ($int) {
        case 1:
            return '已参加';
            break;
        case 2:
            return '已过期';
            break;
        case 3:
            return '立即参与';
            break;
        case 4:
            return '未开始';
            break;
        case 5:
            return '手机已参与';
            break;
        case 7:
            return '立即参与';
            break;
    }
}

function getWeekTradingStr($tradingTimes) {
    if($tradingTimes >= 0 && $tradingTimes <= 2) {
        return array('str' => '低', 'ratio' => '0.33');
    } else if($tradingTimes > 2 && $tradingTimes <= 4) {
        return array('str' => '中', 'ratio' => '0.66');
    } else if($tradingTimes > 4) {
        return array('str' => '高', 'ratio' => '1');
    }
}

function getMonthTradingStr($tradingTimes) {
    if($tradingTimes >= 0 && $tradingTimes <= 10) {
        return array('str' => '低', 'ratio' => '0.33');
    } else if($tradingTimes > 10 && $tradingTimes <= 20) {
        return array('str' => '中', 'ratio' => '0.66');
    } else if($tradingTimes > 20) {
        return array('str' => '高', 'ratio' => '1');
    }
}

function getFullStockCode($stockCode){
    return (substr($stockCode,0,1)=='6'?'SHA:':'SHE:').$stockCode;
}

/**
 * 获取完整的代码（包括投资组合）
 * @param string $code
 * @param int $isZh 是否投资组合
 */
function getFullCode($code,$isZh=0){
    if(!$isZh){
        return (substr($code,0,1)=='6'?'SH':'SZ').$code;
    }else{
//		$code = str_pad($code, 6,'0',STR_PAD_LEFT);
        return strpos($code, 'ZH')===0?$code:'ZH'.$code;
    }
}

function portfolioCode2Id($code){
    $code = str_replace('ZH', '', $code);
    return intval($code);
}

/**
 * 话题，股票，@用户 代码匹配
 * @author 修改于 2013.06.03
 * @param type $content
 * @param type $typeid  返回格式；0为字符串，1为数组
 * @return type
 */
function formatInside4Api($content, $typeid = 0) {

    $content = preg_replace_callback("/(\\$[^\\$]*?\\$)/i", chgStockSignKuohao, $content); // 把$$中间的（）,替换成()
    $return = array();

    // 把话题保存到数组
    preg_match_all("/#[^#]*[^#^\s][^#]*#/is", $content, $matchTopics);
    $allTopics = $matchTopics[0];
    // 话题替换到标记码
    if ($allTopics) {
        foreach ($allTopics as $kT1 => $vT1) {
            $content = str_replace($vT1, '[temp_topics_' . $kT1 . ']', $content);
        }
    }
    //统一匹配所有股票代码
    $regex = "/\\$(?:[^\s\\$]+?)[\\(\\[\\{]{1}[0-9a-zA-Z]+?[\\)\\]\\}]{1}\\$/is";
    preg_match_all($regex, $content, $allStockFull);
    foreach ($allStockFull[0] as $key => $val) {
        //圆括号全部匹配
        if (stripos($val, '(') !== false) {
            $stockInfo['oStockFull'][] = $val;
        }
        //中括号股票代码匹配
        if (stripos($val, '[') !== false) {
            $stockInfo['oStockCode'][] = $val;
        }
        //大括号股票名称匹配
        if (stripos($val, '{') !== false) {
            $stockInfo['oStockName'][] = $val;
        }
    }

    // $stockname(stockcode)$
    if ($stockInfo['oStockFull']) {
        foreach ($stockInfo['oStockFull'] as $kSF => $vSF) {
            $content = str_replace($vSF, '[temp_o_stock_full_' . $kSF . ']', $content);
        }
    }
    // $stockname[stockcode]$
    if ($stockInfo['oStockCode']) {
        foreach ($stockInfo['oStockCode'] as $kSC => $vSC) {
            $content = str_replace($vSC, '[temp_o_stock_code_' . $kSC . ']', $content);
        }
    }
    // $stockname{stockcode}$
    if ($stockInfo['oStockName']) {
        foreach ($stockInfo['oStockName'] as $kSN => $vSN) {
            $content = str_replace($vSN, '[temp_o_stock_name_' . $kSN . ']', $content);
        }
    }

    // 把链接保存到数组
    preg_match_all("/\<(?:.*?)\>(?:.*?)\<\/a\>/i", $content, $matchLinks);
    $allLinks = $matchLinks[0];
    // 话题替换到标记
    if ($allLinks) {
        foreach ($allLinks as $kL1 => $vL1) {
            $content = str_replace($vL1, '[temp_links_' . $kL1 . ']', $content);
        }
    }

    // @用户
    preg_match_all("/(@(.+?)([\s|:]|$))/is", $content, $matchUsers);
    $allUsers = $matchUsers[0];
    // 用户替换到标记码
    if ($allUsers) {
        foreach ($allUsers as $kU1 => $vU1) {
            $content = str_replace($vU1, '[temp_users_' . $kU1 . '] ', $content);
        }
    }

    // 反解用户
    if ($allUsers) {
        foreach ($allUsers as $kU2 => $vU2) {
            $vU2 = preg_replace_callback("/@([\w\x{4e00}-\x{9fa5}\-]+)/u", getAtUserName, $vU2);
            $content = str_replace('[temp_users_' . $kU2 . ']', $vU2, $content);
        }
    }

    // 反解链接
    if ($allLinks) {
        foreach ($allLinks as $kL2 => $vL2) {
            $content = str_replace('[temp_links_' . $kL2 . ']', $vL2, $content);
        }
    }

    // 反解股票名称
    if ($stockInfo['oStockName']) {
        foreach ($stockInfo['oStockName'] as $kSN2 => $vSN2) {
            $vSN2 = preg_replace_callback("/(\\$(?:[^\s\\$]+?)\\{[0-9a-zA-Z]+?\\}\\$)/i", replaceStockLinkName, $vSN2); // 匹配$stockname[stockcode]$格式的股票名称
            $content = str_replace('[temp_o_stock_name_' . $kSN2 . ']', $vSN2, $content);
        }
    }
    // 反解股票代码
    if ($stockInfo['oStockCode']) {
        foreach ($stockInfo['oStockCode'] as $kSC2 => $vSC2) {
            $vSC2 = preg_replace_callback("/(\\$(?:[^\s\\$]+?)\\[[0-9a-zA-Z]+?\\]\\$)/i", replaceStockLinkCode, $vSC2); // 匹配$stockname{stockcode}$格式的股票代码
            $content = str_replace('[temp_o_stock_code_' . $kSC2 . ']', $vSC2, $content);
        }
    }

    // 反解股票标准
    if ($stockInfo['oStockFull']) {
        foreach ($stockInfo['oStockFull'] as $kSF2 => $vSF2) {
            $vSF2 = preg_replace_callback("/(\\$(?:[^\s\\$]+?)\\([0-9a-zA-Z]+?\\)\\$)/i", replaceStockLink, $vSF2); // 匹配$stockname(stockcode)$格式的股票
            $content = str_replace('[temp_o_stock_full_' . $kSF2 . ']', $vSF2, $content);
        }
    }

    // 反解话题
    if ($allTopics) {
        foreach ($allTopics as $kT2 => $vT2) {
            $vT2 = preg_replace_callback("/#([^#]*[^#^\s][^#]*)#/is", themeformat, $vT2);
            $content = str_replace('[temp_topics_' . $kT2 . ']', $vT2, $content);
        }
    }

    if ($typeid == 1) {
        //返回数组赋值
        $return = $stockInfo;
        $return['topic'] = $allTopics;

        return $return;
    }

    //var_dump($content);exit;
    return $content;
}

/**
 * 生成API使用的@用
 *
 * @param array $name
 * @see format()
 * @see formatComment()
 */
function getAtUserName($name) {
    $info = D('User', 'home')->getUserByIdentifier($name[1], 'uname');
    if ($info['uname']) {
        if($_REQUEST['reqfrom']==3){
            return '<a href="http://'.$_SERVER['HTTP_HOST'].'/space/' . $info['uid'] . '" >' . $name[0] . '</a>';
        }else{
            return '<a href="imaibo://space/' . $info['uid'] . '" >' . $name[0] . '</a>';
        }
    } else {
        return $name[0];
    }
}

/**
 * [fmtLwbContUrl 格式化长文的URL内容]
 * @param  [type]  $content [微博内容]
 * @param  integer $type [1安卓，2iOS，]
 * @return [type]        [description]
 */
function fmtLwbContUrl($content, $weiboId, $type = 1) {
    $searchSub = 'imaibo://url/';
    $search = '<a href="' . $searchSub;

    $pos = strpos($content, $search);
    $split = substr($content, $pos);
    $cont = substr($content, 0, $pos);

    $regex = '/<a(?:.*?)>(.*?)<\/a>/is';
    $url_ = preg_match($regex, $split, $newArr);
    $url = $newArr[1];
    $urlc = str_replace('http://', '', $url);

    $newUrl = 'imaibo://longweibo?id=' . $weiboId;

    return $cont . str_replace($searchSub . $urlc, $newUrl, $split);
}

/**
 *APP用户标识
 */
function getUserIconsApp($uid){
    $data = model('UserGroup')->getUserGroupIconSrc((int)$uid);
    foreach ($data as $v) {
        $icons[] = $v['img'];
    }
    unset($data);
    unset($v);
    return $icons ? $icons : array();
}

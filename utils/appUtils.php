<?php
/**
 * 频道处理和接口生成
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/androidURL.php';

// URL缓存，降低请求频率
$urlCache = [];

/**
 * 生成接口字符串
 */
function interfaceStr($url, $headers, $urlUserId, $urlToken) {
    $result = [
        'content' => null,
        'contentType' => 'text/plain;charset=UTF-8'
    ];
    
    $fileName = DATA_DIR . '/interface.txt';
    
    switch ($url) {
        case '/playback.xml':
            $fileName = DATA_DIR . '/playback.xml';
            $result['contentType'] = 'text/xml;charset=UTF-8';
            break;
        case '/txt':
            $fileName = DATA_DIR . '/interfaceTXT.txt';
            break;
        case '/m3u':
            $result['contentType'] = 'audio/x-mpegurl; charset=utf-8';
            break;
        default:
            break;
    }
    
    try {
        if (!file_exists($fileName)) {
            printRed('文件不存在: ' . $fileName);
            return $result;
        }
        $result['content'] = file_get_contents($fileName);
    } catch (Exception $e) {
        printRed('文件获取失败');
        error_log($e->getMessage());
        return $result;
    }
    
    if ($url == '/playback.xml') {
        return $result;
    }
    
    $replaceHost = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    
    $host = getConf('host', '');
    if (!empty($host) && (
        isset($_SERVER['HTTP_X_REAL_IP']) || 
        isset($_SERVER['HTTP_X_FORWARDED_FOR']) || 
        strpos($host, $_SERVER['HTTP_HOST'] ?? '') !== false
    )) {
        $replaceHost = $host;
    }
    
    $pass = getConf('pass', '');
    if (!empty($pass)) {
        $replaceHost = $replaceHost . '/' . $pass;
    }
    
    $configUserId = getConf('userId', '');
    $configToken = getConf('token', '');
    if ($urlUserId != $configUserId && $urlToken != $configToken) {
        $replaceHost = $replaceHost . '/' . $urlUserId . '/' . $urlToken;
    }
    
    $result['content'] = str_replace('${replace}', $replaceHost, $result['content']);
    
    return $result;
}

/**
 * 频道缓存检查
 */
function channelCache($pid, $params) {
    global $urlCache;
    
    $cache = [
        'haveCache' => false,
        'code' => 200,
        'pID' => '',
        'playURL' => '',
        'cacheDesc' => ''
    ];
    
    if (isset($urlCache[$pid]) && is_array($urlCache[$pid])) {
        $valTime = $urlCache[$pid]['valTime'] - (time() * 1000);
        
        // 缓存是否有效
        if ($valTime >= 0) {
            $cache['haveCache'] = true;
            $playURL = $urlCache[$pid]['url'];
            $msg = '节目调整，暂不提供服务';
            
            if ($urlCache[$pid]['content'] != null) {
                $msg = $urlCache[$pid]['content']['message'] ?? $msg;
            }
            
            // 节目调整
            if (empty($playURL)) {
                $cache['cacheDesc'] = $pid . ' ' . $msg;
                return $cache;
            }
            
            // 添加回放参数
            if (!empty($params)) {
                parse_str($params, $resultParams);
                foreach ($resultParams as $key => $value) {
                    $playURL = $playURL . '&' . $key . '=' . $value;
                }
            }
            
            printGreen('使用缓存数据');
            $cache['code'] = 302;
            $cache['cacheDesc'] = '缓存获取成功';
            $cache['playURL'] = $playURL;
            return $cache;
        }
    }
    
    $cache['cacheDesc'] = '暂无缓存';
    return $cache;
}

/**
 * 获取频道播放链接
 */
function channel($url, $urlUserId, $urlToken) {
    global $urlCache;
    
    $result = [
        'code' => 200,
        'pID' => '',
        'desc' => '服务异常',
        'playURL' => ''
    ];
    
    // 处理频道ID
    $urlParts = explode('/', trim($url, '/'));
    $urlSplit = $urlParts[0] ?? '';
    $pid = $urlSplit;
    $params = '';
    
    // 处理回放参数
    if (strpos($urlSplit, '?') !== false) {
        printGreen('处理传入参数');
        $parts = explode('?', $urlSplit, 2);
        $pid = $parts[0];
        $params = $parts[1] ?? '';
    } else {
        printGrey('无参数传入');
    }
    
    if (!is_numeric($pid)) {
        $result['desc'] = '地址格式错误';
        return $result;
    }
    
    printYellow('频道ID ' . $pid);
    
    // 是否存在缓存
    $cache = channelCache($pid, $params);
    if ($cache['haveCache']) {
        $result['code'] = $cache['code'];
        $result['playURL'] = $cache['playURL'];
        $result['desc'] = $cache['cacheDesc'];
        return $result;
    }
    
    $resObj = [];
    try {
        $rateType = intval(getConf('rateType', 3));
        // 未登录请求720p
        if ($rateType >= 3 && (empty($urlUserId) || empty($urlToken))) {
            $resObj = getAndroidURL720p($pid);
        } else {
            $resObj = getAndroidURL($urlUserId, $urlToken, $pid, $rateType);
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        $result['desc'] = '链接请求出错';
        return $result;
    }
    
    printDebug('添加加密字段后链接 ' . ($resObj['url'] ?? ''));
    
    // 缓存有效时长
    $addTime = 3 * 60 * 60 * 1000; // 3小时
    // 节目调整时改为1分钟
    if (empty($resObj['url'])) {
        $addTime = 1 * 60 * 1000;
    }
    
    // 加入缓存
    $urlCache[$pid] = [
        'valTime' => (time() * 1000) + $addTime,
        'url' => $resObj['url'] ?? '',
        'content' => $resObj['content'] ?? null,
    ];
    
    if (empty($resObj['url'])) {
        $msg = isset($resObj['content']['message']) ? $resObj['content']['message'] : '节目调整，暂不提供服务';
        $result['desc'] = $pid . ' ' . $msg;
        return $result;
    }
    
    $playURL = $resObj['url'];
    
    // 添加回放参数
    if (!empty($params)) {
        parse_str($params, $resultParams);
        foreach ($resultParams as $key => $value) {
            $playURL = $playURL . '&' . $key . '=' . $value;
        }
    }
    
    printGreen('链接获取成功');
    $result['code'] = 302;
    $result['playURL'] = $playURL;
    return $result;
}

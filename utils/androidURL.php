<?php
/**
 * Android URL获取
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/ddCalcuURL.php';

$client_id = getStringMD5(strval(time()));

/**
 * 获取盐值和签名
 */
function getSaltAndSign($md5) {
    $salt = 1230024;
    $suffix = '3ce941cc3cbc40528bfd1c64f9fdf6c0migu0123';
    $sign = getStringMD5($md5 . $suffix);
    return [
        'salt' => $salt,
        'sign' => $sign
    ];
}

/**
 * 获取Android播放URL
 */
function getAndroidURL($userId, $token, $pid, $rateType) {
    if ($rateType <= 1) {
        return [
            'url' => '',
            'rateType' => 0,
            'content' => null
        ];
    }
    
    $timestamp = time() * 1000;
    $appVersion = '26000370';
    $headers = [
        'AppVersion' => '2600037000',
        'TerminalId' => 'android',
        'X-UP-CLIENT-CHANNEL-ID' => '2600037000-99000-200300220100002',
    ];
    
    // cctv5和5+开启flv后不能回放
    if ($pid != '641886683' && $pid != '641886773') {
        $headers['appCode'] = 'miguvideo_default_android';
    }
    
    if ($rateType != 2 && !empty($userId) && !empty($token)) {
        $headers['UserId'] = $userId;
        $headers['UserToken'] = $token;
    }
    
    $str = $timestamp . $pid . $appVersion;
    $md5 = getStringMD5($str);
    $result = getSaltAndSign($md5);
    
    $enableHDRStr = '';
    if (getConf('enableHDR', true) !== false) {
        $enableHDRStr = '&4kvivid=true&2Kvivid=true&vivid=2';
    }
    $enableH265Str = '';
    if (getConf('enableH265', true) !== false) {
        $enableH265Str = '&h265N=true';
    }
    
    $baseURL = 'https://play.miguvideo.com/playurl/v1/play/playurl';
    $params = '?sign=' . $result['sign'] . '&rateType=' . $rateType
            . '&contId=' . $pid . '&timestamp=' . $timestamp . '&salt=' . $result['salt']
            . '&flvEnable=true&super4k=true' . ($rateType == 9 ? '&ott=true' : '') . $enableH265Str . $enableHDRStr;
    
    printDebug('请求链接: ' . $baseURL . $params);
    
    $respData = fetchUrl($baseURL . $params, [
        'headers' => $headers
    ]);
    
    printDebug(json_encode($respData));
    
    // 如果需要会员，降低画质
    if (isset($respData['rid']) && $respData['rid'] == 'TIPS_NEED_MEMBER') {
        printYellow('该账号没有会员 正在降低画质');
        $respRateType = (intval($respData['body']['urlInfo']['rateType'] ?? 0) > 4) ? 4 : 3;
        $params = '?sign=' . $result['sign'] . '&rateType=' . $respRateType
                . '&contId=' . $pid . '&timestamp=' . $timestamp . '&salt=' . $result['salt']
                . '&flvEnable=true&super4k=true' . $enableH265Str . $enableHDRStr;
        
        printDebug('请求链接: ' . $baseURL . $params);
        $respData = fetchUrl($baseURL . $params, [
            'headers' => $headers
        ]);
        
        if (isset($respData['rid']) && $respData['rid'] == 'TIPS_NEED_MEMBER') {
            printYellow('账号非钻石会员 降低画质');
            $params = '?sign=' . $result['sign'] . '&rateType=3'
                    . '&contId=' . $pid . '&timestamp=' . $timestamp . '&salt=' . $result['salt']
                    . '&flvEnable=true&super4k=true' . $enableH265Str . $enableHDRStr;
            
            printDebug('请求链接: ' . $baseURL . $params);
            $respData = fetchUrl($baseURL . $params, [
                'headers' => $headers
            ]);
        }
    }
    
    printDebug(json_encode($respData));
    
    $url = $respData['body']['urlInfo']['url'] ?? '';
    
    if (empty($url)) {
        return [
            'url' => '',
            'rateType' => 0,
            'content' => $respData
        ];
    }
    
    $pid = $respData['body']['content']['contId'] ?? $pid;
    
    // 加密URL
    $resURL = getddCalcuURL($url, $pid, 'android', $rateType, $userId);
    
    $rateType = $respData['body']['urlInfo']['rateType'] ?? $rateType;
    
    return [
        'url' => $resURL,
        'rateType' => intval($rateType),
        'content' => $respData
    ];
}

/**
 * 旧版高清画质720p
 */
function getAndroidURL720p($pid) {
    $timestamp = strval(round(time() * 1000));
    $appVersion = '2600034600';
    $appVersionID = $appVersion . '-99000-201600010010028';
    
    global $client_id;
    $headers = [
        'AppVersion' => $appVersion,
        'TerminalId' => 'android',
        'X-UP-CLIENT-CHANNEL-ID' => $appVersionID,
        'ClientId' => $client_id,
    ];
    
    printDebug('client_id: ' . $client_id);
    
    if ($pid != '641886683' && $pid != '641886773') {
        $headers['appCode'] = 'miguvideo_default_android';
    }
    
    $str = $timestamp . $pid . substr($appVersion, 0, 8);
    $md5 = getStringMD5($str);
    
    $salt = str_pad(strval(rand(0, 999999)), 6, '0', STR_PAD_LEFT) . '25';
    $suffix = '2cac4f2c6c3346a5b34e085725ef7e33migu' . substr($salt, 0, 4);
    $sign = getStringMD5($md5 . $suffix);
    
    $rateType = 3;
    $enableHDRStr = '';
    if (getConf('enableHDR', true) !== false) {
        $enableHDRStr = '&4kvivid=true&2Kvivid=true&vivid=2';
    }
    $enableH265Str = '';
    if (getConf('enableH265', true) !== false) {
        $enableH265Str = '&h265N=true';
    }
    
    $baseURL = 'https://play.miguvideo.com/playurl/v1/play/playurl';
    $params = '?sign=' . $sign . '&rateType=' . $rateType
            . '&contId=' . $pid . '&timestamp=' . $timestamp . '&salt=' . $salt
            . '&flvEnable=true&super4k=true' . $enableH265Str . $enableHDRStr;
    
    printDebug('请求链接: ' . $baseURL . $params);
    
    $respData = fetchUrl($baseURL . $params, [
        'headers' => $headers
    ]);
    
    printDebug(json_encode($respData));
    
    $url = $respData['body']['urlInfo']['url'] ?? '';
    
    if (empty($url)) {
        return [
            'url' => '',
            'rateType' => 0,
            'content' => $respData
        ];
    }
    
    $rateType = $respData['body']['urlInfo']['rateType'] ?? $rateType;
    $pid = $respData['body']['content']['contId'] ?? $pid;
    
    // 加密URL
    $resURL = getddCalcuURL720p($url, $pid);
    
    return [
        'url' => $resURL,
        'rateType' => intval($rateType),
        'content' => $respData
    ];
}

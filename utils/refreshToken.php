<?php
/**
 * Token刷新
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/utils.php';

/**
 * URL编码
 */
function encodeURLEncoder($str) {
    $encoded = urlencode($str);
    $encoded = preg_replace_callback('/[!\(\)\*]/', function($matches) {
        return '%' . strtoupper(dechex(ord($matches[0])));
    }, $encoded);
    $encoded = str_replace('%20', '+', $encoded);
    return $encoded;
}

/**
 * 刷新token
 */
function refreshToken($userId, $token) {
    if (empty($userId) || empty($token)) {
        return false;
    }
    
    // 请求体data加密前
    $time = time();
    $baseData = '{"userToken":"' . $token . '","autoDelay":true,"deviceId":"","userId":"' . $userId . '","timestamp":"' . $time . '"}';
    
    // 请求体加密
    $encryData = aesEncrypt($baseData);
    $data = '{"data":"' . $encryData . '"}';
    
    // 签名
    $str = getStringMD5($data);
    $sign = encodeURLEncoder(rsaEncrypt($str));
    
    $headers = [
        'userId' => $userId,
        'userToken' => $token,
        'Content-Type' => 'application/json; charset=utf-8'
    ];
    
    $baseURL = 'https://migu-app-umnb.miguvideo.com/login/token_refresh_migu_plus';
    $params = '?clientId=27fb3129-5a54-45bc-8af1-7dc8f1155501&sign=' . $sign . '&signType=RSA';
    
    try {
        $respResult = fetchUrl($baseURL . $params, [
            'headers' => $headers,
            'method' => 'post',
            'body' => $data
        ]);
        
        if (isset($respResult['resultCode']) && $respResult['resultCode'] == 'REFRESH_TOKEN_SUCCESS') {
            return true;
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
    
    return false;
}

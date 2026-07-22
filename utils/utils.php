<?php
/**
 * 工具函数库
 */

/**
 * MD5加密
 */
function getStringMD5($str) {
    return strtolower(md5($str));
}

/**
 * Base64加密
 */
function base64Encrypt($str) {
    return base64_encode($str);
}

/**
 * Base64解密
 */
function base64Decrypt($str) {
    return base64_decode($str);
}

/**
 * AES加密
 */
function aesEncrypt($data, $baseKey = 'MQDUjI19MGe3BhaqTlpc9g==', $ivStr = 'abcdefghijklmnop') {
    $key = base64_decode($baseKey);
    $iv = $ivStr;
    
    // 填充key到32字节
    if (strlen($key) < 32) {
        $key = str_pad($key, 32, "\0");
    }
    
    // 填充iv到16字节
    if (strlen($iv) < 16) {
        $iv = str_pad($iv, 16, "\0");
    }
    
    return openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

/**
 * AES解密
 */
function aesDecrypt($baseData, $baseKey = 'MQDUjI19MGe3BhaqTlpc9g==', $ivStr = 'abcdefghijklmnop') {
    $key = base64_decode($baseKey);
    $iv = $ivStr;
    
    // 填充key到32字节
    if (strlen($key) < 32) {
        $key = str_pad($key, 32, "\0");
    }
    
    // 填充iv到16字节
    if (strlen($iv) < 16) {
        $iv = str_pad($iv, 16, "\0");
    }
    
    return openssl_decrypt($baseData, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

/**
 * RSA加密(私钥签名)
 */
function rsaEncrypt($data, $privateKeyPem = '') {
    if (empty($privateKeyPem)) {
        $privateKeyPem = "MIICdQIBADANBgkqhkiG9w0BAQEFAASCAl8wggJbAgEAAoGBAOhvWsrglBpQGpjB\r8okxLUCaaiKKOytn9EtvytB5tKDchmgkSaXpreWcDy/9imsuOiVCSdBr6hHjrTN7\rQKkA4/QYS8ptiFv1ap61PiAyRFDI1b8wp2haJ6HF1rDShG2XdfWIhLk4Hj6efVZA\rSfa3taM7C8NseWoWh05Cp26g4hXZAgMBAAECgYBzqZXghsisH1hc04ZBRrth/nT6\rIxc2jlA+ia6+9xEvSw2HHSeY7COgsnvMQbpzg1lj2QyqLkkYBdfWWmrerpa/mb7j\rm6w95YKs5Ndii8NhFWvC0eGK8Ygt02DeLohmkQu3B+Yq8JszjB7tQJRR2kdG6cPt\rKp99ZTyyPom/9uD+AQJBAPxCwajHAkCuH4+aKdZhH6n7oDAxZoMH/mihDRxHZJof\rnT+K662QCCIx0kVCl64s/wZ4YMYbP8/PWDvLMNNWC7ECQQDr4V23KRT9fAPAN8vB\rq2NqjLAmEx+tVnd4maJ16Xjy5Q4PSRiAXYLSr9uGtneSPP2fd/tja0IyawlP5UPL\rl76pAkAeXqMWAK+CvfPKxBKZXqQDQOnuI2RmDgZQ7mK3rtirvXae+ciZ4qc4Bqt7\r7yJ3s68YRlHQR+OMzzeeKz47kzZhAkAPteH1ChJw06q4Sb8TdiPX++jbkFiCxgiN\rCsaMTfGVU/Y8xGSSYCgPelEHxu1t2wwVa/tdYs505zYmkSGT1NaJAkBCS5hymXsA\rB92Fx8eGW5WpLfnpvxl8nOcP+eNXobi8Sc6q1FmoHi8snbcmBhidcDdcieKn+DbX\rGG3BQE/OCOkM\r";
    }
    
    $clearKey = str_replace("\r", '', $privateKeyPem);
    $keyBytes = base64_decode($clearKey);
    
    // 构建PEM格式私钥
    $pemKey = "-----BEGIN PRIVATE KEY-----\n" . 
              chunk_split(base64_encode($keyBytes), 64, "\n") . 
              "-----END PRIVATE KEY-----";
    
    $privateKey = openssl_pkey_get_private($pemKey);
    if (!$privateKey) {
        return false;
    }
    
    $encrypted = '';
    openssl_private_encrypt($data, $encrypted, $privateKey, OPENSSL_PKCS1_PADDING);
    
    return base64_encode($encrypted);
}

/**
 * HTTP请求
 */
function fetchUrl($url, $options = [], $timeout = 6000) {
    $ch = curl_init();
    
    $timeoutSeconds = $timeout / 1000;
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    // 设置请求头
    if (isset($options['headers'])) {
        $headers = [];
        foreach ($options['headers'] as $key => $value) {
            $headers[] = "$key: $value";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    // 设置请求方法
    if (isset($options['method'])) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($options['method']));
    }
    
    // 设置请求体
    if (isset($options['body'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $options['body']);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        $errorMsg = "CURL Error: " . $error . " | URL: " . $url;
        error_log($errorMsg);
        printRed($errorMsg);
        return null;
    }
    
    if ($httpCode != 200) {
        $errorMsg = "HTTP Error: " . $httpCode . " | URL: " . $url;
        error_log($errorMsg);
        printRed($errorMsg);
        return null;
    }
    
    $json = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errorMsg = "JSON Decode Error: " . json_last_error_msg() . " | URL: " . $url;
        error_log($errorMsg);
        printRed($errorMsg);
        return null;
    }
    
    return $json;
}

/**
 * 获取日期字符串 YYYYMMDD
 */
function getDateString($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    }
    return date('Ymd', $timestamp);
}

/**
 * 获取时间字符串 HHmmss
 */
function getTimeString($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    }
    return date('His', $timestamp);
}

/**
 * 获取日期时间字符串 YYYYMMDDHHmmss
 */
function getDateTimeString($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    }
    return date('YmdHis', $timestamp);
}

/**
 * 获取日志时间字符串
 */
function getDateTimeStr($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    }
    return date('Y-m-d H:i:s', $timestamp);
}

/**
 * 延迟执行
 */
function delay($milliseconds) {
    usleep($milliseconds * 1000);
}

/**
 * 打印调试信息
 */
function printDebug($msg) {
    if (getConf('debug', false)) {
        error_log("[DEBUG] " . $msg);
    }
}

function printGreen($msg) {
    echo "[INFO] " . $msg . "\n";
    error_log("[INFO] " . $msg);
}

function printRed($msg) {
    echo "[ERROR] " . $msg . "\n";
    error_log("[ERROR] " . $msg);
}

function printYellow($msg) {
    echo "[WARN] " . $msg . "\n";
    error_log("[WARN] " . $msg);
}

function printBlue($msg) {
    echo "[NOTICE] " . $msg . "\n";
    error_log("[NOTICE] " . $msg);
}

function printMagenta($msg) {
    echo "[DEBUG] " . $msg . "\n";
    error_log("[MAGENTA] " . $msg);
}

function printGrey($msg) {
    echo "[TRACE] " . $msg . "\n";
    error_log("[GREY] " . $msg);
}

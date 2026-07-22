<?php
/**
 * URL加密工具 - ddCalcu
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/utils.php';

$list = [
    'h5' => [
        'keys' => 'yzwxcdabgh',
        'words' => ['', 'y', '0', 'w'],
        'thirdReplaceIndex' => 1,
        'suffix' => '&sv=10000&ct=www'
    ],
    'android' => [
        'keys' => 'cdabyzwxkl',
        'words' => ['v', 'a', '0', 'a'],
        'thirdReplaceIndex' => 6,
        'suffix' => '&sv=10004&ct=android'
    ]
];

/**
 * 获取ddCalcu
 */
function getddCalcu($puData, $programId, $clientType, $rateType, $urlUserId = '') {
    global $list;
    
    if (empty($puData) || empty($programId) || empty($rateType)) {
        return '';
    }
    
    if ($clientType !== 'android' && $clientType !== 'h5') {
        return '';
    }
    
    $userId = $urlUserId ?: getConf('userId', '');
    
    // 根据用户ID调整words
    if ($userId) {
        $words1 = $list['android']['keys'][intval($userId[7])];
        $list['android']['words'][0] = $words1;
        $list['h5']['words'][0] = $words1;
    }
    
    $keys = $list[$clientType]['keys'];
    $words = $list[$clientType]['words'];
    $thirdReplaceIndex = $list[$clientType]['thirdReplaceIndex'];
    
    // android平台标清
    if ($clientType == 'android' && $rateType == '2') {
        $words[0] = 'v';
    }
    if (strlen($userId) > 3 && strlen($userId) <= 8) {
        $words[0] = 'e';
    }
    
    $puDataLength = strlen($puData);
    $ddCalcu = [];
    
    for ($i = 0; $i < $puDataLength / 2; $i++) {
        $ddCalcu[] = $puData[$puDataLength - $i - 1];
        $ddCalcu[] = $puData[$i];
        
        switch ($i) {
            case 1:
                $ddCalcu[] = $words[$i - 1];
                break;
            case 2:
                $dateStr = getDateString();
                $ddCalcu[] = $keys[intval($dateStr[0])];
                break;
            case 3:
                $ddCalcu[] = $keys[intval($programId[$thirdReplaceIndex])];
                break;
            case 4:
                $ddCalcu[] = $words[$i - 1];
                break;
        }
    }
    
    return implode('', $ddCalcu);
}

/**
 * 加密链接
 */
function getddCalcuURL($puDataURL, $programId, $clientType, $rateType, $urlUserId = '') {
    global $list;
    
    if (empty($puDataURL) || empty($programId) || empty($rateType)) {
        return '';
    }
    
    if ($clientType !== 'android' && $clientType !== 'h5') {
        return '';
    }
    
    // 提取puData
    if (strpos($puDataURL, '&puData=') === false) {
        return '';
    }
    
    $puData = explode('&puData=', $puDataURL)[1];
    $ddCalcu = getddCalcu($puData, $programId, $clientType, $rateType, $urlUserId);
    $suffix = $list[$clientType]['suffix'];
    
    return $puDataURL . '&ddCalcu=' . $ddCalcu . $suffix;
}

/**
 * 旧版720p ddcalcu
 */
function getddCalcu720p($puData, $programId) {
    if (empty($puData) || empty($programId)) {
        return '';
    }
    
    $keys = 'cdabyzwxkl';
    $ddCalcu = [];
    
    for ($i = 0; $i < strlen($puData) / 2; $i++) {
        $ddCalcu[] = $puData[strlen($puData) - $i - 1];
        $ddCalcu[] = $puData[$i];
        
        switch ($i) {
            case 1:
                $ddCalcu[] = 'v';
                break;
            case 2:
                $dateStr = getDateString();
                $ddCalcu[] = $keys[intval($dateStr[2])];
                break;
            case 3:
                $ddCalcu[] = $keys[intval($programId[6])];
                break;
            case 4:
                $ddCalcu[] = 'a';
                break;
        }
    }
    
    return implode('', $ddCalcu);
}

/**
 * 旧版720p加密链接
 */
function getddCalcuURL720p($puDataURL, $programId) {
    if (empty($puDataURL) || empty($programId)) {
        return '';
    }
    
    if (strpos($puDataURL, '&puData=') === false) {
        return '';
    }
    
    $puData = explode('&puData=', $puDataURL)[1];
    $ddCalcu = getddCalcu720p($puData, $programId);
    
    return $puDataURL . '&ddCalcu=' . $ddCalcu . '&sv=10004&ct=android';
}

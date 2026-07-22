<?php
/**
 * 节目单(EPG)处理
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../utils/utils.php';

// CCTV频道名称映射
$cntvNames = [
    'CCTV1综合' => 'cctv1',
    'CCTV2财经' => 'cctv2',
    'CCTV3综艺' => 'cctv3',
    'CCTV4中文国际' => 'cctv4',
    'CCTV5体育' => 'cctv5',
    'CCTV5+体育赛事' => 'cctv5p',
    'CCTV6电影' => 'cctv6',
    'CCTV7国防军事' => 'cctv7',
    'CCTV8电视剧' => 'cctv8',
    'CCTV9纪录' => 'cctvjilu',
    'CCTV10科教' => 'cctv10',
    'CCTV11戏曲' => 'cctv11',
    'CCTV12社会与法' => 'cctv12',
    'CCTV13新闻' => 'cctv13',
    'CCTV14少儿' => 'cctvchild',
    'CCTV15音乐' => 'cctv15',
];

/**
 * 获取节目单数据
 */
function getPlaybackData($programId, $timeout = 6000, $githubAnd8 = 0) {
    $date = getDateString(time() + $githubAnd8);
    $url = 'https://program-sc.miguvideo.com/live/v2/tv-programs-data/' . $programId . '/' . $date;
    $resp = fetchUrl($url, [], $timeout);
    
    if (!$resp || !isset($resp['body']['program'][0]['content'])) {
        return null;
    }
    
    return $resp['body']['program'][0]['content'];
}

/**
 * 更新咪咕节目单
 */
function updatePlaybackDataByMigu($program, $filePath, $timeout = 6000, $githubAnd8 = 0) {
    $playbackData = getPlaybackData($program['pID'], $timeout, $githubAnd8);
    
    if (!$playbackData) {
        return false;
    }
    
    // 写入频道信息
    $channelXml = "    <channel id=\"{$program['name']}\">\n" .
                  "        <display-name lang=\"zh\">{$program['name']}</display-name>\n" .
                  "    </channel>\n";
    file_put_contents($filePath, $channelXml, FILE_APPEND);
    
    // 写入节目信息
    foreach ($playbackData as $item) {
        $contName = htmlspecialchars($item['contName'], ENT_QUOTES, 'UTF-8');
        $startTime = getDateTimeString(($item['startTime'] + $githubAnd8) / 1000);
        $endTime = getDateTimeString(($item['endTime'] + $githubAnd8) / 1000);
        
        $programmeXml = "    <programme channel=\"{$program['name']}\" start=\"{$startTime} +0800\" stop=\"{$endTime} +0800\">\n" .
                        "        <title lang=\"zh\">{$contName}</title>\n" .
                        "    </programme>\n";
        file_put_contents($filePath, $programmeXml, FILE_APPEND);
    }
    
    return true;
}

/**
 * 更新CCTV节目单
 */
function updatePlaybackDataByCntv($program, $filePath, $timeout = 6000, $githubAnd8 = 0) {
    global $cntvNames;
    
    $date = getDateString(time() + $githubAnd8);
    $cntvName = $cntvNames[$program['name']] ?? '';
    
    if (empty($cntvName)) {
        return false;
    }
    
    $url = "https://api.cntv.cn/epg/epginfo3?serviceId=shiyi&d={$date}&c={$cntvName}";
    $resp = fetchUrl($url, [], $timeout);
    
    if (!$resp || !isset($resp[$cntvName]['program'])) {
        return false;
    }
    
    $playbackData = $resp[$cntvName]['program'];
    
    // 写入频道信息
    $channelXml = "    <channel id=\"{$program['name']}\">\n" .
                  "        <display-name lang=\"zh\">{$program['name']}</display-name>\n" .
                  "    </channel>\n";
    file_put_contents($filePath, $channelXml, FILE_APPEND);
    
    // 写入节目信息
    foreach ($playbackData as $item) {
        $contName = htmlspecialchars($item['t'], ENT_QUOTES, 'UTF-8');
        $startTime = getDateTimeString($item['st'] * 1000 + $githubAnd8);
        $endTime = getDateTimeString($item['et'] * 1000 + $githubAnd8);
        
        $programmeXml = "    <programme channel=\"{$program['name']}\" start=\"{$startTime} +0800\" stop=\"{$endTime} +0800\">\n" .
                        "        <title lang=\"zh\">{$contName}</title>\n" .
                        "    </programme>\n";
        file_put_contents($filePath, $programmeXml, FILE_APPEND);
    }
    
    return true;
}

/**
 * 更新节目单
 */
function updatePlaybackData($program, $filePath, $timeout = 6000, $githubAnd8 = 0) {
    global $cntvNames;
    
    if (isset($cntvNames[$program['name']])) {
        return updatePlaybackDataByCntv($program, $filePath, $timeout, $githubAnd8);
    }
    return updatePlaybackDataByMigu($program, $filePath, $timeout, $githubAnd8);
}

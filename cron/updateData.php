<?php
/**
 * 数据更新
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../utils/utils.php';
require_once __DIR__ . '/../backend/fetchList.php';
require_once __DIR__ . '/../backend/playback.php';
require_once __DIR__ . '/../utils/refreshToken.php';

// 忽略分类列表
$ignoreCategorySet = [];

/**
 * 更新TV数据
 */
function updateTV($hours) {
    global $ignoreCategorySet;
    
    $start = microtime(true);
    
    printGreen('开始更新TV数据...');
    
    // 获取数据
    $datas = dataList();
    if (empty($datas)) {
        printRed('TV数据获取失败');
        return false;
    }
    printGreen('TV数据获取成功！');
    
    $interfacePath = INTERFACE_TXT;
    $interfaceTXTPath = INTERFACE_TXT_FORMAT;
    $playbackFile = PLAYBACK_XML;
    
    // 创建空文件
    file_put_contents($interfacePath, '');
    file_put_contents($interfaceTXTPath, '');
    file_put_contents($playbackFile, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tv generator-info-name=\"Tak\" generator-info-url=\"" . getConf('host', '') . "\">\n");
    
    // 写入M3U头部
    file_put_contents($interfacePath, "#EXTM3U x-tvg-url=\"\${replace}/playback.xml\" catchup=\"append\" catchup-source=\"?playbackbegin=\${(b)yyyyMMddHHmmss}&playbackend=\${(e)yyyyMMddHHmmss}\"\n", FILE_APPEND);
    
    printYellow('开始更新TV...');
    
    // 分类列表
    foreach ($datas as $i => $category) {
        // 忽略分类
        if (isset($ignoreCategorySet[$category['name']])) {
            printYellow('TV分类###:' . $category['name'] . ' 已屏蔽！');
            continue;
        }
        
        $dataList = $category['dataList'] ?? [];
        
        // txt
        file_put_contents($interfaceTXTPath, $category['name'] . ",#genre#\n", FILE_APPEND);
        
        // 写入节目
        foreach ($dataList as $program) {
            updatePlaybackData($program, $playbackFile);
            
            // 写入M3U
            $m3uEntry = "#EXTINF:-1 tvg-id=\"{$program['name']}\" tvg-name=\"{$program['name']}\" tvg-logo=\"{$program['pics']['highResolutionH']}\" group-title=\"{$category['name']}\",{$program['name']}\n\${replace}/{$program['pID']}\n";
            file_put_contents($interfacePath, $m3uEntry, FILE_APPEND);
            
            // 写入TXT
            file_put_contents($interfaceTXTPath, "{$program['name']},\${replace}/{$program['pID']}\n", FILE_APPEND);
        }
        
        printGreen('分类###:' . $category['name'] . ' 更新完成！');
    }
    
    // 关闭XML
    file_put_contents($playbackFile, "</tv>\n", FILE_APPEND);
    
    printGreen('TV更新完成！');
    $end = microtime(true);
    printYellow('TV更新耗时: ' . round($end - $start, 2) . '秒');
    
    return true;
}

/**
 * 更新PE(体育)数据
 */
function updatePE($hours) {
    global $ignoreCategorySet;
    
    $start = microtime(true);
    
    printGreen('开始更新PE数据...');
    
    // 获取PE数据
    $datas = fetchUrl('http://v0-sc.miguvideo.com/vms-match/v6/staticcache/basic/match-list/normal-match-list/0/all/default/1/miguvideo');
    
    if (!$datas || !isset($datas['body']['days'])) {
        printRed('PE数据获取失败');
        return false;
    }
    
    printGreen('PE数据获取成功！');
    
    $interfacePath = INTERFACE_TXT;
    $interfaceTXTPath = INTERFACE_TXT_FORMAT;
    
    // 屏蔽所有TV分类
    if (isset($ignoreCategorySet['TV'])) {
        file_put_contents($interfacePath, "#EXTM3U x-tvg-url=\"\${replace}/playback.xml\" catchup=\"append\" catchup-source=\"?playbackbegin=\${(b)yyyyMMddHHmmss}&playbackend=\${(e)yyyyMMddHHmmss}\"\n");
        file_put_contents($interfaceTXTPath, '');
    }
    
    printYellow('开始更新PE...');
    
    for ($i = 1; $i < 4; $i++) {
        if (!isset($datas['body']['days'][$i])) {
            continue;
        }
        
        $date = $datas['body']['days'][$i];
        $relativeDate = '昨天' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
        $dateString = getDateString();
        
        if ($date == $dateString) {
            $relativeDate = '今天' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
        } else if (intval($date) > intval($dateString)) {
            $relativeDate = '明天' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
        }
        
        // 忽略分类
        if (isset($ignoreCategorySet['体育-' . substr($relativeDate, 0, 2)])) {
            printYellow('PE分类###: 体育-' . $relativeDate . '已屏蔽！');
            continue;
        }
        
        file_put_contents($interfaceTXTPath, '体育-' . $relativeDate . ",#genre#\n", FILE_APPEND);
        
        if (!isset($datas['body']['matchList'][$date])) {
            continue;
        }
        
        foreach ($datas['body']['matchList'][$date] as $data) {
            $pkInfoTitle = $data['pkInfoTitle'] ?? '';
            if (isset($data['confrontTeams'])) {
                $pkInfoTitle = $data['confrontTeams'][0]['name'] . 'VS' . $data['confrontTeams'][1]['name'];
            }
            
            $peResult = fetchUrl('https://vms-sc.miguvideo.com/vms-match/v6/staticcache/basic/basic-data/' . $data['mgdbId'] . '/miguvideo');
            
            try {
                // 比赛已结束
                if (isset($peResult['body']['endTime']) && $peResult['body']['endTime'] < time() * 1000) {
                    $replayResult = fetchUrl('http://app-sc.miguvideo.com/vms-match/v5/staticcache/basic/all-view-list/' . $data['mgdbId'] . '/2/miguvideo');
                    $replayList = $replayResult['body']['replayList'] ?? $peResult['body']['multiPlayList']['replayList'] ?? [];
                    
                    if (empty($replayList)) {
                        printYellow($data['mgdbId'] . ' ' . $pkInfoTitle . ' 无回放');
                        continue;
                    }
                    
                    foreach ($replayList as $replay) {
                        if (preg_match('/.*集锦|训练.*/', $replay['name'] ?? '')) {
                            continue;
                        }
                        if (preg_match('/.*回放|赛.*/', $replay['name'] ?? '')) {
                            $timeStr = substr($peResult['body']['keyword'], 7);
                            if (isset($peResult['body']['multiPlayList']['preList'])) {
                                $lastPre = end($peResult['body']['multiPlayList']['preList']);
                                if (isset($lastPre['startTimeStr'])) {
                                    $timeStr = substr($lastPre['startTimeStr'], 11, 5);
                                }
                            }
                            $competitionDesc = $data['competitionName'] . ' ' . $pkInfoTitle . ' ' . $replay['name'] . ' ' . $timeStr;
                            
                            file_put_contents($interfacePath, "#EXTINF:-1 tvg-id=\"{$pkInfoTitle}\" tvg-name=\"{$competitionDesc}\" tvg-logo=\"{$data['competitionLogo']}\" group-title=\"体育-{$relativeDate}\",{$competitionDesc}\n\${replace}/{$replay['pID']}\n", FILE_APPEND);
                            file_put_contents($interfaceTXTPath, "{$competitionDesc},\${replace}/{$replay['pID']}\n", FILE_APPEND);
                        }
                    }
                    continue;
                }
                
                // 比赛未结束
                if (isset($peResult['body']['multiPlayList']['liveList'])) {
                    foreach ($peResult['body']['multiPlayList']['liveList'] as $live) {
                        if (preg_match('/.*集锦.*/', $live['name'] ?? '') || empty($live['startTimeStr'] ?? '')) {
                            continue;
                        }
                        $competitionDesc = $data['competitionName'] . ' ' . $pkInfoTitle . ' ' . $live['name'] . ' ' . substr($live['startTimeStr'], 11, 5);
                        
                        file_put_contents($interfacePath, "#EXTINF:-1 tvg-id=\"{$pkInfoTitle}\" tvg-name=\"{$competitionDesc}\" tvg-logo=\"{$data['competitionLogo']}\" group-title=\"体育-{$relativeDate}\",{$competitionDesc}\n\${replace}/{$live['pID']}\n", FILE_APPEND);
                        file_put_contents($interfaceTXTPath, "{$competitionDesc},\${replace}/{$live['pID']}\n", FILE_APPEND);
                    }
                }
            } catch (Exception $e) {
                printYellow($data['mgdbId'] . ' ' . $pkInfoTitle . ' 更新失败 此警告不影响正常使用 可忽略');
            }
        }
        
        printGreen('日期 ' . $date . ' 更新完成！');
    }
    
    printGreen('PE更新完成！');
    $end = microtime(true);
    printYellow('PE更新耗时: ' . round($end - $start, 2) . '秒');
    
    return true;
}

/**
 * 处理忽略分类
 */
function processIgnoreCategory() {
    global $ignoreCategorySet;
    
    $ignoreCategory = getConf('ignoreCategory', '');
    
    if (empty($ignoreCategory)) {
        return;
    }
    
    if (strpos($ignoreCategory, ',') !== false) {
        $split = explode(',', $ignoreCategory);
        foreach ($split as $cat) {
            $cat = trim($cat);
            if (!empty($cat)) {
                $ignoreCategorySet[$cat] = true;
            }
        }
    } else if (strpos($ignoreCategory, '，') !== false) {
        $split = explode('，', $ignoreCategory);
        foreach ($split as $cat) {
            $cat = trim($cat);
            if (!empty($cat)) {
                $ignoreCategorySet[$cat] = true;
            }
        }
    } else {
        $ignoreCategorySet[trim($ignoreCategory)] = true;
    }
}

/**
 * 更新数据
 */
function updateData($hours = 0) {
    global $ignoreCategorySet;
    
    // 处理忽略列表
    processIgnoreCategory();
    
    // 每720小时(一个月)刷新token
    if (!($hours % 720)) {
        $userId = getConf('userId', '');
        $token = getConf('token', '');
        if (!empty($userId) && !empty($token)) {
            if (refreshToken($userId, $token)) {
                printGreen('token刷新成功');
            } else {
                printRed('token刷新失败');
            }
        }
    }
    
    // 更新TV
    if (isset($ignoreCategorySet['TV'])) {
        printYellow('TV更新已屏蔽');
    } else {
        updateTV($hours);
    }
    
    // 更新PE
    if (isset($ignoreCategorySet['PE'])) {
        printYellow('PE更新已屏蔽');
    } else {
        updatePE($hours);
    }
}

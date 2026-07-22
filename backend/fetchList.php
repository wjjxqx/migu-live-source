<?php
/**
 * 频道列表获取
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../utils/utils.php';

/**
 * 合并分类
 */
function mergeCategory($cates) {
    $processCategory = [];
    $otherCategory = [
        'name' => '其他',
        'vomsID' => '',
        'fitArea' => ['10000'],
        'dataList' => []
    ];
    
    $mergeTVCategory = getConf('mergeTVCategory', true);
    $customMergeCategory = getConf('customMergeCategory', '');
    
    if ($mergeTVCategory !== false && $mergeTVCategory !== 'false') {
        foreach ($cates as $cate) {
            if (count($cate['dataList'] ?? []) <= 11) {
                foreach ($cate['dataList'] as $data) {
                    $otherCategory['dataList'][] = $data;
                }
            } else {
                $processCategory[] = $cate;
            }
        }
        $processCategory[] = $otherCategory;
    } else if (!empty($customMergeCategory)) {
        $customMergeCategorySet = [];
        
        if (strpos($customMergeCategory, ',') !== false) {
            $split = explode(',', $customMergeCategory);
            foreach ($split as $cat) {
                $cat = trim($cat);
                if (!empty($cat)) {
                    $customMergeCategorySet[$cat] = true;
                }
            }
        } else if (strpos($customMergeCategory, '，') !== false) {
            $split = explode('，', $customMergeCategory);
            foreach ($split as $cat) {
                $cat = trim($cat);
                if (!empty($cat)) {
                    $customMergeCategorySet[$cat] = true;
                }
            }
        } else {
            $customMergeCategorySet[trim($customMergeCategory)] = true;
        }
        
        foreach ($cates as $cate) {
            if (isset($customMergeCategorySet[$cate['name']])) {
                foreach ($cate['dataList'] as $data) {
                    $otherCategory['dataList'][] = $data;
                }
            } else {
                $processCategory[] = $cate;
            }
        }
        $processCategory[] = $otherCategory;
    }
    
    if (empty($processCategory)) {
        return $cates;
    }
    return $processCategory;
}

/**
 * 获取分类列表
 */
function cateList() {
    $resp = fetchUrl('https://program-sc.miguvideo.com/live/v2/tv-data/1ff892f2b5ab4a79be6e25b69d2f5d05');
    
    if (!$resp || !isset($resp['body']['liveList'])) {
        printRed('获取分类列表失败');
        return [];
    }
    
    $liveList = $resp['body']['liveList'];
    
    // 过滤热门
    $liveList = array_filter($liveList, function($item) {
        return $item['name'] != '热门';
    });
    $liveList = array_values($liveList);
    
    // 央视作为首个分类
    usort($liveList, function($a, $b) {
        if ($a['name'] === '央视') return -1;
        if ($b['name'] === '央视') return 1;
        return 0;
    });
    
    return $liveList;
}

/**
 * 对dataList去重
 */
function uniqueData($liveList) {
    $allItems = [];
    
    // 提取全部dataList
    foreach ($liveList as $category) {
        if (isset($category['dataList'])) {
            foreach ($category['dataList'] as $program) {
                $program['categoryName'] = $category['name'];
                $allItems[] = $program;
            }
        }
    }
    
    // 使用set确保唯一
    $set = [];
    $uniqueItem = [];
    
    foreach ($allItems as $item) {
        if (!isset($set[$item['name']])) {
            $set[$item['name']] = true;
            $uniqueItem[] = $item;
        }
    }
    
    $categoryMap = [];
    
    // 清空原dataList内容
    foreach ($liveList as &$live) {
        $live['dataList'] = [];
        $categoryMap[$live['name']] = [];
    }
    unset($live);
    
    // 根据分类填充内容
    foreach ($uniqueItem as $item) {
        $categoryName = $item['categoryName'];
        unset($item['categoryName']);
        $categoryMap[$categoryName][] = $item;
    }
    
    // liveList赋值
    foreach ($liveList as &$live) {
        $live['dataList'] = $categoryMap[$live['name']] ?? [];
    }
    unset($live);
    
    return $liveList;
}

/**
 * 获取所有数据
 */
function dataList() {
    $cates = cateList();
    
    if (empty($cates)) {
        return [];
    }
    
    foreach ($cates as &$cate) {
        try {
            $resp = fetchUrl('https://program-sc.miguvideo.com/live/v2/tv-data/' . $cate['vomsID']);
            $cate['dataList'] = $resp['body']['dataList'] ?? [];
        } catch (Exception $e) {
            $cate['dataList'] = [];
        }
    }
    unset($cate);
    
    // 去除重复节目
    $cates = uniqueData($cates);
    
    // 合并分类
    $mergeTVCategory = getConf('mergeTVCategory', true);
    if ($mergeTVCategory !== false && $mergeTVCategory !== 'false') {
        $cates = mergeCategory($cates);
    }
    
    return $cates;
}

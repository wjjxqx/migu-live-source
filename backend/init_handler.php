<?php
/**
 * 初始化数据处理
 */

// 设置执行时间和内存限制
set_time_limit(300); // 5分钟
ini_set('memory_limit', '512M');

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../utils/utils.php';
require_once __DIR__ . '/../cron/updateData.php';

// 关闭输出缓冲
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');
header('X-Accel-Buffering: no');

try {
    // 确保data目录存在且有权限
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0777, true);
    }
    
    if (!is_writable(DATA_DIR)) {
        chmod(DATA_DIR, 0777);
    }
    
    // 执行数据更新
    updateData(0);
    
    // 检查是否成功生成文件
    $files = [
        INTERFACE_TXT => 'interface.txt',
        INTERFACE_TXT_FORMAT => 'interfaceTXT.txt',
        PLAYBACK_XML => 'playback.xml'
    ];
    
    $generatedFiles = [];
    foreach ($files as $file => $name) {
        if (file_exists($file)) {
            $generatedFiles[] = $name;
        }
    }
    
    if (count($generatedFiles) > 0) {
        echo json_encode([
            'success' => true,
            'message' => '数据更新成功',
            'files' => $generatedFiles
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => '数据文件未生成，请检查日志'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

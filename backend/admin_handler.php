<?php
/**
 * 后台配置处理
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../utils/utils.php';
require_once __DIR__ . '/../cron/updateData.php';

// 获取当前配置
$config = getConfig();

// 处理GET请求（手动更新等）
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'update') {
        // 手动更新数据
        printBlue('开始手动更新数据...');
        try {
            // 启用错误显示
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            
            updateData(0);
            
            // 检查数据文件是否生成
            if (!file_exists(INTERFACE_TXT)) {
                throw new Exception('数据文件未生成：interface.txt');
            }
            
            header('Location: admin.php?msg=数据更新成功！');
        } catch (Exception $e) {
            $errorMsg = '数据更新失败: ' . $e->getMessage();
            error_log($errorMsg);
            header('Location: admin.php?error=' . urlencode($errorMsg));
        }
        exit;
    }
    
    // 默认显示配置页面
    header('Location: admin.php');
    exit;
}

// 处理POST请求（保存配置）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newConfig = [
        'userId' => trim($_POST['userId'] ?? ''),
        'token' => trim($_POST['token'] ?? ''),
        'host' => trim($_POST['host'] ?? ''),
        'rateType' => intval($_POST['rateType'] ?? 3),
        'pass' => trim($_POST['pass'] ?? ''),
        'enableHDR' => isset($_POST['enableHDR']),
        'enableH265' => isset($_POST['enableH265']),
        'mergeTVCategory' => isset($_POST['mergeTVCategory']),
        'ignoreCategory' => trim($_POST['ignoreCategory'] ?? ''),
        'customMergeCategory' => trim($_POST['customMergeCategory'] ?? ''),
        'updateInterval' => intval($_POST['updateInterval'] ?? 6),
    ];
    
    // 保存配置
    try {
        saveConfig($newConfig);
        header('Location: admin.php?msg=配置保存成功！');
    } catch (Exception $e) {
        header('Location: admin.php?error=配置保存失败: ' . urlencode($e->getMessage()));
    }
    exit;
}

// 默认跳转
header('Location: admin.php');
exit;

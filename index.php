<?php
/**
 * 咪咕视频爬取程序 - 主入口
 * PHP 8.0+ 版本
 */

// 错误报告
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/utils/utils.php';
require_once __DIR__ . '/utils/appUtils.php';

// 确保数据目录存在
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// 获取请求信息
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$headers = getallheaders();

// 解析URL路径 - 简化版，直接从REQUEST_URI提取
$path = parse_url($requestUri, PHP_URL_PATH);
if (empty($path)) {
    $path = '/';
}

// 确保path以/开头
if (empty($path)) {
    $path = '/';
}
if ($path[0] !== '/') {
    $path = '/' . $path;
}

// 身份认证
$pass = getConf('pass', '');
if (!empty($pass)) {
    $pathParts = explode('/', trim($path, '/'));
    if ($pathParts[0] != $pass) {
        http_response_code(200);
        header('Content-Type: application/json;charset=UTF-8');
        echo '身份认证失败';
        exit;
    } else {
        printGreen('身份认证成功');
        // 移除密码部分
        if (count($pathParts) > 1) {
            $path = '/' . implode('/', array_slice($pathParts, 1));
        } else {
            $path = '/';
        }
    }
}

// 提取URL中的用户信息
$urlToken = '';
$urlUserId = '';

if (preg_match('/^\/([^\/\s]+)\/([^\/\s]+)/', $path, $matches)) {
    $urlUserId = $matches[1];
    $urlToken = $matches[2];
    $pathParts = explode('/', trim($path, '/'));
    if (count($pathParts) >= 3) {
        $path = '/' . $pathParts[2];
    } else {
        $path = '/';
    }
} else {
    $urlUserId = getConf('userId', '');
    $urlToken = getConf('token', '');
}

printMagenta('请求地址：' . $path);

// HEAD请求
if ($requestMethod === 'HEAD') {
    http_response_code(200);
    header('Content-Type: application/json;charset=UTF-8');
    exit;
}

// 接口列表
$interfaceList = ['/', '/interface.txt', '/m3u', '/txt', '/playback.xml'];

// 静态文件路由 (admin.php等)
if ($path === '/admin.php' || $path === '/admin') {
    require_once __DIR__ . '/frontend/admin.php';
    exit;
}

if ($path === '/admin_handler.php') {
    require_once __DIR__ . '/admin_handler.php';
    exit;
}

// 健康检查端点
if ($path === '/health') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'time' => date('Y-m-d H:i:s')]);
    exit;
}

// Cron触发端点 (用于外部定时任务)
if ($path === '/cron' || $path === '/update') {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Accel-Buffering: no');
    
    echo "=== 数据更新 - " . date('Y-m-d H:i:s') . " ===\n";
    echo "PHP: " . phpversion() . "\n";
    echo "ignoreCategory: [" . getConf('ignoreCategory', '') . "]\n";
    echo "rateType: " . getConf('rateType', 3) . "\n";
    echo "---\n";
    ob_flush(); flush();
    
    // 禁用错误日志，改为输出
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    
    require_once __DIR__ . '/cron/updateData.php';
    
    // 调用更新函数
    echo ">>> 开始执行 updateData()\n";
    ob_flush(); flush();
    
    try {
        updateData(0);
    } catch (Exception $e) {
        echo ">>> 异常: " . $e->getMessage() . "\n";
    }
    
    echo ">>> updateData() 执行完毕\n";
    echo "---\n";
    echo "=== 结果 ===\n";
    
    // 检查文件
    $files = ['interface.txt', 'interfaceTXT.txt', 'playback.xml'];
    foreach ($files as $f) {
        $fp = DATA_DIR . '/' . $f;
        if (file_exists($fp)) {
            $size = filesize($fp);
            $lines = count(file($fp));
            echo "✅ {$f}: {$size} bytes, {$lines} 行\n";
        } else {
            echo "❌ {$f}: 不存在\n";
        }
    }
    echo "=== 完成 " . date('Y-m-d H:i:s') . " ===\n";
    exit;
}

// 调试
if (isset($_GET['debug'])) {
    echo "<pre>";
    echo "Path: " . $path . "\n";
    echo "In interfaceList: " . (in_array($path, $interfaceList) ? 'YES' : 'NO') . "\n";
    echo "</pre>";
    exit;
}

// 处理接口请求
if (in_array($path, $interfaceList)) {
    // 检查数据文件是否存在
    $dataFile = '';
    switch ($path) {
        case '/':
        case '/m3u':
        case '/interface.txt':
            $dataFile = INTERFACE_TXT;
            break;
        case '/txt':
            $dataFile = INTERFACE_TXT_FORMAT;
            break;
        case '/playback.xml':
            $dataFile = PLAYBACK_XML;
            break;
    }
    
    // 如果数据文件不存在，自动触发更新 (适配Render等云平台)
    if (!empty($dataFile) && !file_exists($dataFile)) {
        // 尝试自动更新数据
        @require_once __DIR__ . '/cron/updateData.php';
        
        // 再次检查
        if (!file_exists($dataFile)) {
            http_response_code(200);
            header('Content-Type: text/html;charset=UTF-8');
            echo '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>数据生成中</title></head>
<body style="font-family: sans-serif; padding: 40px; background: #f5f5f5;">
<div style="max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
<h1 style="color: #333;">⏳ 数据生成中</h1>
<p style="color: #666; line-height: 1.6;">首次访问需要从咪咕获取数据，请稍等片刻后刷新页面。</p>
<div style="margin: 20px 0; padding: 15px; background: #E8F5E9; border-radius: 6px;">
<strong>提示：</strong>数据生成约需30-60秒，完成后会自动缓存。
</div>
<p><a href="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '" style="display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;">🔄 刷新页面</a></p>
<p><a href="admin.php" style="display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;">⚙ 管理后台</a></p>
<p style="margin-top: 20px; color: #999; font-size: 13px;">请求路径: ' . htmlspecialchars($path) . '</p>
</div>
</body>
</html>';
            exit;
        }
    }
    
    $interfaceObj = interfaceStr($path, $headers, $urlUserId, $urlToken);
    
    if ($interfaceObj['content'] == null) {
        $interfaceObj['content'] = '获取失败';
    }
    
    header('Content-Type: ' . $interfaceObj['contentType']);
    if ($path == '/m3u') {
        header('Content-Disposition: inline; filename="interface.m3u"');
    }
    http_response_code(200);
    echo $interfaceObj['content'];
    exit;
}

// 频道请求
$result = channel($path, $urlUserId, $urlToken);

// 结果异常
if ($result['code'] != 302) {
    printRed($result['desc']);
    http_response_code($result['code'] == 200 ? 400 : $result['code']);
    header('Content-Type: application/json;charset=UTF-8');
    echo json_encode([
        'error' => $result['desc'],
        'path' => $path,
        'code' => $result['code']
    ]);
    exit;
}

// 302重定向
http_response_code(302);
header('Content-Type: application/json;charset=UTF-8');
header('Location: ' . $result['playURL']);
exit;

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
require_once __DIR__ . '/cron/updateData.php';

// 确保数据目录存在
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// 获取请求信息
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$headers = getallheaders();

// 解析URL - 修复Nginx下的PATH_INFO问题
$path = '';

// 方法1: 尝试从PATH_INFO获取
if (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
    $path = $_SERVER['PATH_INFO'];
}
// 方法2: 从REQUEST_URI中提取
else {
    // 移除query string
    $uri = explode('?', $requestUri)[0];
    
    // 移除script name（支持根目录和子目录）
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if (!empty($scriptName) && strpos($uri, $scriptName) === 0) {
        $path = substr($uri, strlen($scriptName));
    }
    // 方法3: 如果script name不匹配，直接使用uri
    else {
        $path = $uri;
    }
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

// 只允许GET请求
if ($requestMethod !== 'GET') {
    http_response_code(200);
    header('Content-Type: application/json;charset=UTF-8');
    echo json_encode(['data' => '请使用GET请求']);
    printRed('使用非GET请求:' . $requestMethod);
    exit;
}

// 接口列表
$interfaceList = ['/', '/interface.txt', '/m3u', '/txt', '/playback.xml'];

// Cron触发端点 (用于外部定时任务)
if ($path === '/cron' || $path === '/update') {
    header('Content-Type: text/html;charset=UTF-8');
    echo '<h3>⏰ 数据更新中...</h3>';
    ob_flush(); flush();
    require_once __DIR__ . '/cron/updateData.php';
    echo '<h3 style="color:green">✅ 更新完成！</h3>';
    echo '<p>时间: ' . date('Y-m-d H:i:s') . '</p>';
    echo '<p><a href="/">返回首页</a></p>';
    exit;
}

// 健康检查端点
if ($path === '/health') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'time' => date('Y-m-d H:i:s')]);
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

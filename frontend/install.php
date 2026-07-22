<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>环境检查 - 咪咕视频PHP版</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .check-item {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .check-item.success {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .check-item.error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .check-item.warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .check-item .label {
            font-weight: 600;
            color: #333;
        }
        .check-item .value {
            color: #666;
            font-size: 14px;
        }
        .check-item .status {
            font-size: 24px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 20px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .summary h3 {
            margin-bottom: 15px;
            color: #333;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 环境检查</h1>
        
        <?php
        $checks = [];
        $allPassed = true;
        
        // 检查PHP版本
        $phpVersion = phpversion();
        $phpOk = version_compare($phpVersion, '8.0.0', '>=');
        $checks[] = [
            'label' => 'PHP版本',
            'value' => $phpVersion . ' (需要 >= 8.0)',
            'status' => $phpOk ? 'success' : 'error',
            'icon' => $phpOk ? '✅' : '❌'
        ];
        if (!$phpOk) $allPassed = false;
        
        // 检查CURL扩展
        $curlOk = extension_loaded('curl');
        $checks[] = [
            'label' => 'CURL扩展',
            'value' => $curlOk ? '已安装' : '未安装',
            'status' => $curlOk ? 'success' : 'error',
            'icon' => $curlOk ? '✅' : '❌'
        ];
        if (!$curlOk) $allPassed = false;
        
        // 检查OpenSSL扩展
        $opensslOk = extension_loaded('openssl');
        $checks[] = [
            'label' => 'OpenSSL扩展',
            'value' => $opensslOk ? '已安装' : '未安装',
            'status' => $opensslOk ? 'success' : 'error',
            'icon' => $opensslOk ? '✅' : '❌'
        ];
        if (!$opensslOk) $allPassed = false;
        
        // 检查JSON扩展
        $jsonOk = extension_loaded('json');
        $checks[] = [
            'label' => 'JSON扩展',
            'value' => $jsonOk ? '已安装' : '未安装',
            'status' => $jsonOk ? 'success' : 'error',
            'icon' => $jsonOk ? '✅' : '❌'
        ];
        if (!$jsonOk) $allPassed = false;
        
        // 检查data目录
        $dataDir = __DIR__ . '/data';
        $dataDirExists = is_dir($dataDir);
        $dataDirWritable = is_writable($dataDir) || (!file_exists($dataDir) && is_writable(__DIR__));
        $checks[] = [
            'label' => 'data目录',
            'value' => $dataDirExists ? '已存在' : '不存在（将自动创建）',
            'status' => $dataDirWritable ? 'success' : 'error',
            'icon' => $dataDirWritable ? '✅' : '❌'
        ];
        if (!$dataDirWritable) $allPassed = false;
        
        // 检查配置文件
        $configFile = $dataDir . '/config.json';
        $configOk = file_exists($configFile) || is_writable($dataDir) || !file_exists($dataDir);
        $checks[] = [
            'label' => '配置文件',
            'value' => file_exists($configFile) ? '已存在' : '将自动创建',
            'status' => $configOk ? 'success' : 'warning',
            'icon' => $configOk ? '✅' : '⚠️'
        ];
        
        // 检查最大执行时间
        $maxExecTime = ini_get('max_execution_time');
        $execTimeOk = $maxExecTime >= 30 || $maxExecTime == 0;
        $checks[] = [
            'label' => '最大执行时间',
            'value' => $maxExecTime . '秒' . ($maxExecTime == 0 ? ' (无限制)' : ''),
            'status' => $execTimeOk ? 'success' : 'warning',
            'icon' => $execTimeOk ? '✅' : '⚠️'
        ];
        
        // 检查内存限制
        $memoryLimit = ini_get('memory_limit');
        $checks[] = [
            'label' => '内存限制',
            'value' => $memoryLimit,
            'status' => 'success',
            'icon' => '✅'
        ];
        
        // 检查allow_url_fopen
        $allowUrlFopen = ini_get('allow_url_fopen');
        $checks[] = [
            'label' => 'allow_url_fopen',
            'value' => $allowUrlFopen ? '已开启' : '已关闭',
            'status' => $allowUrlFopen ? 'success' : 'warning',
            'icon' => $allowUrlFopen ? '✅' : '⚠️'
        ];
        
        // 显示检查结果
        foreach ($checks as $check) {
            echo '<div class="check-item ' . $check['status'] . '">';
            echo '<div>';
            echo '<div class="label">' . $check['label'] . '</div>';
            echo '<div class="value">' . $check['value'] . '</div>';
            echo '</div>';
            echo '<div class="status">' . $check['icon'] . '</div>';
            echo '</div>';
        }
        ?>
        
        <div class="summary">
            <h3>📋 检查总结</h3>
            <?php if ($allPassed): ?>
                <p style="color: #28a745; font-weight: 600; font-size: 18px;">
                    ✅ 所有必需项都已通过！您可以开始使用系统了。
                </p>
                <a href="admin.php" class="btn">进入管理后台</a>
            <?php else: ?>
                <p style="color: #dc3545; font-weight: 600;">
                    ❌ 存在必需项未通过，请先解决以下问题：
                </p>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <?php if (!$phpOk): ?>
                        <li>升级PHP到8.0或更高版本</li>
                    <?php endif; ?>
                    <?php if (!$curlOk): ?>
                        <li>安装CURL扩展：<code>apt-get install php-curl</code> 或 <code>yum install php-curl</code></li>
                    <?php endif; ?>
                    <?php if (!$opensslOk): ?>
                        <li>安装OpenSSL扩展：<code>apt-get install php-openssl</code> 或 <code>yum install php-openssl</code></li>
                    <?php endif; ?>
                    <?php if (!$jsonOk): ?>
                        <li>安装JSON扩展：<code>apt-get install php-json</code> 或 <code>yum install php-json</code></li>
                    <?php endif; ?>
                    <?php if (!$dataDirWritable): ?>
                        <li>设置data目录权限：<code>chmod 777 data</code></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #e7f3ff; border-radius: 8px;">
            <h4 style="margin-bottom: 10px;">💡 部署提示</h4>
            <ol style="margin-left: 20px; line-height: 1.8;">
                <li>确保所有检查项都通过</li>
                <li>访问 <code>admin.php</code> 进行配置</li>
                <li>填写Token和ID（可选，无会员也可使用）</li>
                <li>设置定时任务自动更新数据</li>
                <li>在IPTV播放器中配置接口地址</li>
            </ol>
        </div>
    </div>
</body>
</html>

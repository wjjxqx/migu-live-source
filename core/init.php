<?php
/**
 * 一键初始化工具
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../utils/utils.php';
require_once __DIR__ . '/../cron/updateData.php';

// 检查data目录
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0777, true);
    echo "<p>✓ 创建data目录</p>";
}

if (!is_writable(DATA_DIR)) {
    chmod(DATA_DIR, 0777);
    echo "<p>✓ 设置data目录权限</p>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>初始化数据</title>
    <style>
        body { 
            font-family: -apple-system, sans-serif; 
            padding: 20px; 
            background: #f5f5f5;
        }
        .box { 
            background: white; 
            padding: 30px; 
            margin: 20px auto; 
            max-width: 800px;
            border-radius: 8px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
        }
        h1 { color: #333; }
        .log { 
            background: #2d2d2d; 
            color: #f8f8f2; 
            padding: 20px; 
            border-radius: 6px; 
            font-family: monospace; 
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
            margin: 15px 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .btn { 
            display: inline-block; 
            padding: 12px 24px; 
            background: #2196F3; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
            margin: 5px;
            font-size: 16px;
        }
        .btn:hover { background: #1976D2; }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .info { color: #2196F3; }
        .progress {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            margin: 15px 0;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #2196F3, #4CAF50);
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="box">
    <h1>🚀 数据初始化</h1>
    <p>正在更新咪咕视频数据，这可能需要几分钟时间...</p>
    
    <div class="progress">
        <div class="progress-bar" id="progress" style="width: 0%;">0%</div>
    </div>
    
    <div class="log" id="log">开始初始化...\n</div>
    
    <div id="result" style="display: none; margin-top: 20px;">
        <div id="resultMessage"></div>
        <p style="margin-top: 20px;">
            <a href="test_api.php" class="btn">🧪 测试接口</a>
            <a href="admin.php" class="btn">⚙️ 管理后台</a>
        </p>
    </div>
</div>

<script>
const log = document.getElementById('log');
const progress = document.getElementById('progress');
const result = document.getElementById('result');
const resultMessage = document.getElementById('resultMessage');

function addLog(text, className = '') {
    const div = document.createElement('div');
    div.textContent = text;
    if (className) div.className = className;
    log.appendChild(div);
    log.scrollTop = log.scrollHeight;
}

function setProgress(percent) {
    progress.style.width = percent + '%';
    progress.textContent = percent + '%';
}

// 开始更新
fetch('init_handler.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            setProgress(100);
            addLog('\n========================================', 'success');
            addLog('✓ 初始化完成！', 'success');
            addLog('========================================\n', 'success');
            result.style.display = 'block';
            resultMessage.innerHTML = '<div class="success" style="background: #d4edda; padding: 15px; border-radius: 6px;">✅ 数据更新成功！您现在可以使用接口了。</div>';
        } else {
            setProgress(100);
            addLog('\n========================================', 'error');
            addLog('✗ 更新失败: ' + (data.error || '未知错误'), 'error');
            addLog('========================================\n', 'error');
            result.style.display = 'block';
            resultMessage.innerHTML = '<div style="background: #f8d7da; padding: 15px; border-radius: 6px; color: #721c24;">❌ 更新失败，请检查下方日志。</div>';
        }
    })
    .catch(error => {
        setProgress(100);
        addLog('\n========================================', 'error');
        addLog('✗ 网络错误: ' + error.message, 'error');
        addLog('========================================\n', 'error');
        result.style.display = 'block';
        resultMessage.innerHTML = '<div style="background: #f8d7da; padding: 15px; border-radius: 6px; color: #721c24;">❌ 请求失败，请检查网络连接。</div>';
    });

// 模拟进度
let currentProgress = 0;
const progressInterval = setInterval(() => {
    if (currentProgress < 90) {
        currentProgress += Math.random() * 5;
        if (currentProgress > 90) currentProgress = 90;
        setProgress(Math.round(currentProgress));
    }
}, 1000);
</script>

</body>
</html>

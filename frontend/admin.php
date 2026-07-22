<?php
/**
 * 后台管理界面
 */
require_once __DIR__ . '/../core/config.php';

// 获取当前配置
$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>咪咕视频配置管理</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .content {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }
        
        .form-group .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 14px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .info-box h3 {
            color: #1976D2;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info-box ul {
            margin-left: 20px;
            color: #555;
            font-size: 14px;
        }
        
        .info-box li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎬 咪咕视频配置管理</h1>
            <p>PHP 8.0+ 版本 | 后台配置系统</p>
        </div>
        
        <div class="content">
            <?php if (isset($_GET['msg'])): ?>
            <div class="message success" style="display: block;">
                <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
            <div class="message error" style="display: block;">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
            <?php endif; ?>
            
            <div class="info-box">
                <h3>📋 使用说明</h3>
                <ul>
                    <li><strong>无需会员：</strong>如果不设置Token和ID，默认使用720p高清画质</li>
                    <li><strong>有会员：</strong>填写Token和ID后可使用更高画质（蓝光需要VIP）</li>
                    <li><strong>访问密码：</strong>设置后访问接口需要带上密码</li>
                    <li><strong>数据更新：</strong>系统会自动更新节目单，也可手动触发</li>
                </ul>
            </div>
            
            <form action="admin_handler.php" method="POST">
                <div class="form-group">
                    <label for="userId">用户ID（可选）</label>
                    <input type="text" id="userId" name="userId" value="<?php echo htmlspecialchars($config['userId'] ?? ''); ?>" placeholder="有会员可填写，无会员留空">
                    <div class="help-text">咪咕账号的用户ID，用于获取会员画质</div>
                </div>
                
                <div class="form-group">
                    <label for="token">用户Token（可选）</label>
                    <input type="text" id="token" name="token" value="<?php echo htmlspecialchars($config['token'] ?? ''); ?>" placeholder="有会员可填写，无会员留空">
                    <div class="help-text">咪咕账号的Token，可通过网页登录获取</div>
                </div>
                
                <div class="form-group">
                    <label for="host">公网访问地址</label>
                    <input type="text" id="host" name="host" value="<?php echo htmlspecialchars($config['host'] ?? ''); ?>" placeholder="例如: https://example.com">
                    <div class="help-text">如果使用域名访问，请填写完整地址（包含http/https）</div>
                </div>
                
                <div class="form-group">
                    <label for="rateType">画质设置</label>
                    <select id="rateType" name="rateType">
                        <option value="2" <?php echo ($config['rateType'] ?? 3) == 2 ? 'selected' : ''; ?>>标清</option>
                        <option value="3" <?php echo ($config['rateType'] ?? 3) == 3 ? 'selected' : ''; ?>>高清（推荐）</option>
                        <option value="4" <?php echo ($config['rateType'] ?? 3) == 4 ? 'selected' : ''; ?>>蓝光（需要VIP）</option>
                    </select>
                    <div class="help-text">无会员时即使选择蓝光也会自动降级到高清</div>
                </div>
                
                <div class="form-group">
                    <label for="pass">访问密码（可选）</label>
                    <input type="text" id="pass" name="pass" value="<?php echo htmlspecialchars($config['pass'] ?? ''); ?>" placeholder="留空则不设置密码">
                    <div class="help-text">设置后访问接口格式为: http://域名/密码/...</div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="enableHDR" name="enableHDR" value="1" <?php echo ($config['enableHDR'] ?? true) ? 'checked' : ''; ?>>
                        <label for="enableHDR" style="margin: 0;">开启HDR</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="enableH265" name="enableH265" value="1" <?php echo ($config['enableH265'] ?? true) ? 'checked' : ''; ?>>
                        <label for="enableH265" style="margin: 0;">开启H265编码</label>
                    </div>
                    <div class="help-text">H265可能在一些浏览器播放没有画面，如有问题请关闭</div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="mergeTVCategory" name="mergeTVCategory" value="1" <?php echo ($config['mergeTVCategory'] ?? true) ? 'checked' : ''; ?>>
                        <label for="mergeTVCategory" style="margin: 0;">合并节目数较少的分类</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="ignoreCategory">忽略分类</label>
                    <input type="text" id="ignoreCategory" name="ignoreCategory" value="<?php echo htmlspecialchars($config['ignoreCategory'] ?? ''); ?>" placeholder="例如: TV,体育-昨天,PE">
                    <div class="help-text">多个分类用逗号分隔，TV可忽略所有电视，PE可忽略所有体育</div>
                </div>
                
                <div class="form-group">
                    <label for="customMergeCategory">自定义合并分类</label>
                    <input type="text" id="customMergeCategory" name="customMergeCategory" value="<?php echo htmlspecialchars($config['customMergeCategory'] ?? ''); ?>" placeholder="例如: 熊猫,综艺,新闻">
                    <div class="help-text">需先关闭"合并节目数较少的分类"选项</div>
                </div>
                
                <div class="form-group">
                    <label for="updateInterval">数据更新间隔（小时）</label>
                    <input type="number" id="updateInterval" name="updateInterval" value="<?php echo htmlspecialchars($config['updateInterval'] ?? 6); ?>" min="1" max="24">
                    <div class="help-text">建议设置为6-12小时，不建议设置太短</div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">💾 保存配置</button>
                    <a href="admin_handler.php?action=update" class="btn btn-secondary" style="text-align: center; text-decoration: none; line-height: 20px;">🔄 手动更新数据</a>
                </div>
            </form>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
                <h3 style="margin-bottom: 15px; color: #333;">📡 接口地址</h3>
                <?php 
                $baseUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
                // 移除末尾的斜杠
                $scriptDir = rtrim($scriptDir, '/');
                
                // 检查是否支持URL重写（通过测试访问）
                $usePrettyUrl = false;
                $passStr = !empty($config['pass']) ? '/' . $config['pass'] : '';
                ?>
                <div style="background: #f5f5f5; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 13px;">
                    <p style="margin-bottom: 12px; color: #2196F3; font-weight: bold;">✅ 可用地址（推荐）：</p>
                    <p style="margin-bottom: 8px;"><strong>M3U格式:</strong> <?php echo $baseUrl . $scriptDir . '/m3u'; ?></p>
                    <p style="margin-bottom: 8px;"><strong>TXT格式:</strong> <?php echo $baseUrl . $scriptDir . '/txt'; ?></p>
                    <p style="margin-bottom: 8px;"><strong>EPG节目单:</strong> <?php echo $baseUrl . $scriptDir . '/playback.xml'; ?></p>
                    <p style="margin-bottom: 8px;"><strong>频道播放:</strong> <?php echo $baseUrl . $scriptDir . '/{频道ID}'; ?></p>
                    
                    <?php if (!empty($pass)): ?>
                    <p style="margin-top: 12px; margin-bottom: 12px; color: #FF9800; font-weight: bold;">🔐 带密码的地址：</p>
                    <p style="margin-bottom: 8px;"><strong>M3U:</strong> <?php echo $baseUrl . $scriptDir . $passStr . '/m3u'; ?></p>
                    <p style="margin-bottom: 8px;"><strong>TXT:</strong> <?php echo $baseUrl . $scriptDir . $passStr . '/txt'; ?></p>
                    <p style="margin-bottom: 8px;"><strong>频道:</strong> <?php echo $baseUrl . $scriptDir . $passStr . '/{频道ID}'; ?></p>
                    <?php endif; ?>
                    
                    <p style="margin-top: 15px; margin-bottom: 12px; color: #9E9E9E;">💡 提示：如果上面的地址无法访问，可以尝试带 index.php 的地址：</p>
                    <p style="margin-bottom: 8px; color: #9E9E9E;"><strong>M3U:</strong> <?php echo $baseUrl . $scriptDir . '/index.php/m3u'; ?></p>
                    <p style="margin-bottom: 8px; color: #9E9E9E;"><strong>TXT:</strong> <?php echo $baseUrl . $scriptDir . '/txt'; ?></p>
                    <p style="margin-bottom: 8px; color: #9E9E9E;"><strong>EPG:</strong> <?php echo $baseUrl . $scriptDir . '/playback.xml'; ?></p>
                    
                    <p style="margin-top: 15px; margin-bottom: 8px;"><strong>管理后台:</strong> <?php echo $baseUrl . $scriptDir . '/admin.php'; ?></p>
                </div>
                
                <div style="margin-top: 15px; padding: 12px; background: #E3F2FD; border-radius: 6px; font-size: 13px;">
                    <strong>📱 IPTV播放器配置示例：</strong><br>
                    <span style="color: #666;">
                    M3U地址: <code><?php echo $baseUrl . $scriptDir . '/index.php/m3u'; ?></code><br>
                    EPG地址: <code><?php echo $baseUrl . $scriptDir . '/index.php/playback.xml'; ?></code>
                    </span>
                </div>
                
                <div style="margin-top: 15px; padding: 12px; background: #FFF9C4; border-radius: 6px; font-size: 13px;">
                    <strong>⚠️ 重要说明：</strong><br>
                    <span style="color: #666;">
                    • 以上带 <code>index.php</code> 的地址<strong>立即可用</strong>，无需配置服务器<br>
                    • 如果您的Nginx配置了URL重写规则，可以使用美化地址（灰色显示）<br>
                    • 群晖NAS用户建议直接使用带 <code>index.php</code> 的地址
                    </span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

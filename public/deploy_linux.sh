#!/bin/bash

echo "========================================"
echo "  咪咕视频PHP版 - Linux快速部署"
echo "========================================"
echo ""

# 检查PHP
echo "[1/6] 检查PHP环境..."
if ! command -v php &> /dev/null; then
    echo "[错误] 未检测到PHP，请先安装PHP 8.0+"
    echo "Ubuntu/Debian: sudo apt install php8.0"
    echo "CentOS/RHEL: sudo yum install php"
    exit 1
fi

PHP_VERSION=$(php -v | head -n 1 | cut -d' ' -f2)
echo "[√] PHP版本: $PHP_VERSION"

# 检查扩展
echo ""
echo "[2/6] 检查PHP扩展..."

if php -m | grep -i curl > /dev/null; then
    echo "[√] curl扩展"
else
    echo "[错误] 缺少curl扩展"
    echo "安装: sudo apt install php-curl"
    exit 1
fi

if php -m | grep -i openssl > /dev/null; then
    echo "[√] openssl扩展"
else
    echo "[错误] 缺少openssl扩展"
    echo "安装: sudo apt install php-openssl"
    exit 1
fi

if php -m | grep -i json > /dev/null; then
    echo "[√] json扩展"
else
    echo "[错误] 缺少json扩展"
    echo "安装: sudo apt install php-json"
    exit 1
fi

# 创建data目录
echo ""
echo "[3/6] 创建数据目录..."
if [ ! -d "data" ]; then
    mkdir -p data
    echo "[√] 创建data目录"
else
    echo "[√] data目录已存在"
fi

# 设置权限
echo ""
echo "[4/6] 设置目录权限..."
chmod -R 755 .
chmod -R 777 data
echo "[√] 权限已设置"

# 检查Web服务器
echo ""
echo "[5/6] 检查Web服务器..."
WEB_SERVER=""

if command -v nginx &> /dev/null; then
    WEB_SERVER="nginx"
    echo "[√] 检测到 Nginx"
elif command -v apache2 &> /dev/null || command -v httpd &> /dev/null; then
    WEB_SERVER="apache"
    echo "[√] 检测到 Apache"
else
    echo "[!] 未检测到Web服务器"
    echo "    请安装 Nginx 或 Apache"
fi

# 获取当前路径
DEPLOY_PATH=$(pwd)

# 显示部署信息
echo ""
echo "[6/6] 部署信息"
echo "========================================"
echo ""
echo "✅ 环境检查完成！"
echo ""
echo "📁 部署路径: $DEPLOY_PATH"
echo ""

if [ -n "$WEB_SERVER" ]; then
    echo "🌐 访问地址:"
    echo "   管理后台: http://localhost/admin.php"
    echo "   环境检查: http://localhost/install.php"
    echo ""
    echo "📡 接口地址（配置后）:"
    echo "   M3U: http://localhost/m3u"
    echo "   TXT: http://localhost/txt"
    echo "   EPG: http://localhost/playback.xml"
else
    echo "⚠️  请先配置Web服务器"
    echo "   参考 nginx.conf 或 .htaccess 文件"
fi

echo ""
echo "========================================"
echo ""
echo "下一步:"
echo "1. 配置Web服务器（如未配置）"
echo "2. 访问 http://localhost/install.php 检查环境"
echo "3. 访问 http://localhost/admin.php 进行配置"
echo ""
echo "💡 提示:"
echo "   - Nginx配置参考: nginx.conf"
echo "   - Apache配置参考: .htaccess"
echo "   - 设置定时更新: crontab -e"
echo ""

# 创建定时任务提示
echo "========================================"
echo "📅 设置定时更新（可选）"
echo "========================================"
echo ""
echo "运行以下命令设置每6小时自动更新："
echo ""
echo "crontab -e"
echo ""
echo "添加以下行："
echo "0 */6 * * * /usr/bin/php $DEPLOY_PATH/cron_update.php >> /var/log/migu_update.log 2>&1"
echo ""

read -p "是否现在设置定时更新？(y/N): " SET_CRON
if [[ $SET_CRON == "y" || $SET_CRON == "Y" ]]; then
    CRON_JOB="0 */6 * * * /usr/bin/php $DEPLOY_PATH/cron_update.php >> /var/log/migu_update.log 2>&1"
    (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
    echo "[√] 定时任务已添加"
fi

echo ""
echo "🎉 部署完成！开始使用吧！"
echo ""

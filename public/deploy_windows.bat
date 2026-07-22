@echo off
chcp 65001 >nul
echo ========================================
echo   咪咕视频PHP版 - Windows快速部署
echo ========================================
echo.

:: 检查PHP是否安装
echo [1/5] 检查PHP环境...
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [错误] 未检测到PHP，请先安装PHP 8.0+
    echo 推荐下载: https://windows.php.net/download/
    pause
    exit /b 1
)

:: 获取PHP版本
for /f "tokens=2" %%i in ('php -v ^| findstr /C:"PHP"') do set PHP_VERSION=%%i
echo [√] PHP版本: %PHP_VERSION%

:: 检查必需扩展
echo.
echo [2/5] 检查PHP扩展...

php -m | findstr /C:"curl" >nul 2>&1
if %errorlevel% neq 0 (
    echo [错误] 缺少curl扩展
    pause
    exit /b 1
)
echo [√] curl扩展

php -m | findstr /C:"openssl" >nul 2>&1
if %errorlevel% neq 0 (
    echo [错误] 缺少openssl扩展
    pause
    exit /b 1
)
echo [√] openssl扩展

php -m | findstr /C:"json" >nul 2>&1
if %errorlevel% neq 0 (
    echo [错误] 缺少json扩展
    pause
    exit /b 1
)
echo [√] json扩展

:: 创建data目录
echo.
echo [3/5] 创建数据目录...
if not exist "data" (
    mkdir data
    echo [√] 创建data目录
) else (
    echo [√] data目录已存在
)

:: 设置权限
echo.
echo [4/5] 设置目录权限...
icacls data /grant Everyone:F >nul 2>&1
echo [√] data目录权限已设置

:: 显示部署信息
echo.
echo [5/5] 部署信息
echo ========================================
echo.
echo ✅ 环境检查完成！
echo.
echo 📁 部署路径: %CD%
echo.
echo 🌐 访问地址:
echo    管理后台: http://localhost/migu/admin.php
echo    环境检查: http://localhost/migu/install.php
echo.
echo 📡 接口地址（配置后）:
echo    M3U: http://localhost/migu/m3u
echo    TXT: http://localhost/migu/txt
echo    EPG: http://localhost/migu/playback.xml
echo.
echo ========================================
echo.
echo 下一步:
echo 1. 启动Apache/Nginx服务
echo 2. 访问 http://localhost/migu/install.php 检查环境
echo 3. 访问 http://localhost/migu/admin.php 进行配置
echo.
echo 💡 提示: 
echo    - 使用XAMPP/WAMP/PHPStudy等集成环境
echo    - 确保Web服务器指向当前目录
echo.
pause

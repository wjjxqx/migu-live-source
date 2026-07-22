# 咪咕直播源 - Render 部署指南

## 快速部署

### 1. 推送到 GitHub

```bash
cd C:\migu_render
git init
git add .
git commit -m "init: migu live source"
git remote add origin https://github.com/你的用户名/migu-live-source.git
git push -u origin main
```

### 2. 连接 Render

1. 登录 https://dashboard.render.com/
2. 点击 **New +** → **Web Service**
3. 选择 **Build and deploy from a Git repository**
4. 连接你的 GitHub 仓库
5. 配置：
   - **Name**: `migu-live-source`
   - **Runtime**: `PHP`
   - **Build Command**: `composer install --no-dev --optimize-autoloader`
   - **Start Command**: `php -S 0.0.0.0:$PORT index.php`

### 3. 设置环境变量

在 Render 的 **Environment** 标签页添加：

| 变量名 | 值 | 说明 |
|--------|-----|------|
| `MIGU_RATE_TYPE` | `3` | 画质: 4蓝光(需VIP), 3高清, 2标清 |
| `MIGU_HOST` | `你的Render地址` | 如 `https://migu-live-source.onrender.com` |
| `MIGU_ENABLE_HDR` | `true` | 开启HDR |
| `MIGU_ENABLE_H265` | `true` | 开启H265 |
| `MIGU_IGNORE_CATEGORY` | `TV` | 忽略分类 |
| `MIGU_USER_ID` | (可选) | 咪咕会员用户ID |
| `MIGU_TOKEN` | (可选) | 咪咕会员Token |

### 4. 部署

点击 **Create Web Service**，等待构建完成。

## 定时更新

Render 免费版会休眠，数据需要定期更新。使用免费的 cron 服务：

### 方案1: cron-job.org (推荐)

1. 注册 https://cron-job.org/
2. 创建定时任务：
   - **URL**: `https://你的地址.onrender.com/cron`
   - **Schedule**: 每小时 (`0 * * * *`)
   - **Request Method**: `GET`

### 方案2: UptimeRobot

1. 注册 https://uptimerobot.com/
2. 添加 HTTP 监控：
   - **URL**: `https://你的地址.onrender.com/health`
   - **Interval**: 5 minutes

这会保持服务不休眠，但不会自动更新数据。

## 访问地址

- **直播源接口**: `https://你的地址.onrender.com/`
- **管理后台**: `https://你的地址.onrender.com/admin.php`
- **手动更新**: `https://你的地址.onrender.com/cron`
- **健康检查**: `https://你的地址.onrender.com/health`

## 注意事项

1. **冷启动**: 免费版15分钟无请求会休眠，首次访问需等待30-60秒
2. **数据更新**: 每次冷启动后数据会自动重新获取
3. **免费额度**: Render免费版每月750小时，足够个人使用
4. **文件存储**: 免费版文件系统是临时的，重启后数据会重新生成

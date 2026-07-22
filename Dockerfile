FROM php:8.1-cli

# 安装扩展
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install curl mbstring openssl \
    && rm -rf /var/lib/apt/lists/*

# 设置工作目录
WORKDIR /app

# 复制项目文件
COPY . .

# 设置权限
RUN chmod -R 755 /app/data 2>/dev/null || true
RUN mkdir -p /app/data /app/migucache

# 暴露端口
EXPOSE 8080

# 启动 PHP 内置服务器
CMD ["php", "-S", "0.0.0.0:8080", "index.php"]

FROM php:8.1-cli

# 安装系统依赖
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install curl \
    && docker-php-ext-install xml \
    && rm -rf /var/lib/apt/lists/*

# 工作目录
WORKDIR /app

# 复制文件
COPY . .

# 创建必要目录
RUN mkdir -p /app/data /app/migucache && chmod -R 777 /app/data /app/migucache

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "index.php"]

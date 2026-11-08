# =====================================================================
# 文件名：Dockerfile
# 功能：团队主页 web 镜像（PHP 8.2 + Apache + mysqli/pdo/zip 扩展）
# 作者：金暄琛（2411672）
# 修改：黄浩天（2413421）增加 zip 扩展，供数据包导出/导入使用（FR-DB-04，2026-11-09）
# 创建日期：2026-10-29
# 所属模块：部署（加分项）
# =====================================================================

FROM php:8.2-apache

# 数据库扩展与常用配置（zip 扩展供数据包导出/导入使用，FR-DB-04）
RUN apt-get update && apt-get install -y --no-install-recommends libzip-dev && \
    docker-php-ext-install mysqli pdo_mysql zip && \
    a2enmod rewrite expires headers && rm -rf /var/lib/apt/lists/*

# 上传限制：单文件 12 MB、表单总量 16 MB
COPY uploads.ini /usr/local/etc/php/conf.d/teamhub-uploads.ini

# 静态资源长缓存 + PHP 页面不缓存
COPY apache-cache.conf /etc/apache2/conf-available/teamhub-cache.conf
RUN a2enconf teamhub-cache

# 站点根目录指向 public/（统一入口 index.php）
COPY apache-site.conf /etc/apache2/sites-available/000-default.conf

# 开发模式下源码经 docker-compose 卷挂载，无需 COPY；
# 正式发布（交付镜像）时改为：
# COPY --chown=www-data:www-data . /var/www/html

# 上传与运行时目录交给 www-data（交付镜像 COPY 源码后需再次执行 chown）
RUN mkdir -p /var/www/html/public/static/uploads/media /var/www/html/storage/uploads && \
    chown -R www-data:www-data /var/www/html/public/static/uploads /var/www/html/storage

# 上传目录安全规则模板 + 启动初始化脚本：
# 上传目录使用命名卷与源码解耦（见 docker-compose.yml），空卷首次初始化时
# 由 docker-entrypoint.sh 把安全规则补齐到卷内
COPY docker/htaccess /opt/docker/htaccess
COPY docker-entrypoint.sh /usr/local/bin/teamhub-entrypoint.sh
RUN chmod +x /usr/local/bin/teamhub-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/teamhub-entrypoint.sh"]

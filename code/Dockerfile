# =====================================================================
# 文件名：Dockerfile
# 功能：团队主页 web 镜像（PHP 8.2 + Apache + mysqli/pdo 扩展）
# 作者：（部署模块负责人 学号 姓名）
# 创建日期：2026-10-29
# 所属模块：部署（加分项）
# =====================================================================

FROM php:8.2-apache

# 数据库扩展与常用配置
RUN docker-php-ext-install mysqli pdo_mysql && \
    a2enmod rewrite expires headers

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

EXPOSE 80

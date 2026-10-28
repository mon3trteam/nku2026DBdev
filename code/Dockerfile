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
    a2enmod rewrite

# 站点根目录指向 public/（统一入口 index.php）
# TODO：骨架细化后取消注释并验证虚拟主机配置
# COPY apache-site.conf /etc/apache2/sites-available/000-default.conf

# 开发模式下源码经 docker-compose 卷挂载，无需 COPY；
# 正式发布（交付镜像）时改为：
# COPY . /var/www/html

EXPOSE 80

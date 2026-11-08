#!/bin/sh
# =====================================================================
# 文件名：docker-entrypoint.sh
# 功能：web 容器启动前的初始化——确保上传目录存在且带安全规则
# 作者：黄浩天（2413421）
# 创建日期：2026-11-08
# 所属模块：部署（加分项）
#
# 说明：
#   1) 上传目录使用 Docker 命名卷（见 docker-compose.yml），与源码解耦：
#      首次创建的空卷内没有 .htaccess，本脚本从镜像内模板补齐，
#      保证"禁止目录列表/禁止脚本执行"的安全规则始终生效；
#   2) 非 Docker 部署（phpStudy/XAMPP）不经过本脚本，目录随源码自带。
# =====================================================================
set -e

HTTPLATE_DIR=/opt/docker/htaccess

for pair in \
    "/var/www/html/public/static/uploads:uploads_public.htaccess" \
    "/var/www/html/storage/uploads:storage_uploads.htaccess"
do
    dir=${pair%%:*}
    template=${pair##*:}
    mkdir -p "$dir"
    # 模板是随镜像升级的代码资产：每次启动强制覆盖，保证安全规则始终为最新版
    if [ -f "$HTTPLATE_DIR/$template" ]; then
        cp -f "$HTTPLATE_DIR/$template" "$dir/.htaccess"
    fi
    chown -R www-data:www-data "$dir" || true
done

# 数据包导出/导入目录（FR-DB-04）：预建并赋权，避免 bind mount 属主导致 www-data 不可写
mkdir -p /var/www/html/storage/exports
chown www-data:www-data /var/www/html/storage/exports || true

# 防呆：仍在使用仓库内公开演示默认 APP_KEY 时给出醒目告警（不阻断启动，
# 以免破坏"一条命令跑通"的一键部署体验；生产部署必须替换，见 README）
if [ "${APP_KEY:-}" = "teamhub-local-demo-key-change-me" ]; then
    echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!" >&2
    echo "[teamhub] 警告：正在使用默认演示 APP_KEY。该密钥用于 IP 哈希加盐等，" >&2
    echo "[teamhub] 公开部署前必须在 .env 中替换为随机 32 字节强密钥！" >&2
    echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!" >&2
fi

exec apache2-foreground

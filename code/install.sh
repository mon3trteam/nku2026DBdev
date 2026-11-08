#!/usr/bin/env bash
# =====================================================================
# 文件名：install.sh
# 功能：团队主页项目本地部署脚本（Linux/macOS，无 Docker 环境的备选方案）
# 作者：（部署模块负责人 学号 姓名）
# 创建日期：2026-10-29
# 所属模块：部署（加分项）
#
# 说明：
#   1) 优先使用 docker compose up -d 一键部署；本脚本面向已有 PHP 8 +
#      MySQL 8 的手动环境（如 phpStudy、XAMPP、系统自带 LNMP）。
#   2) 脚本会创建数据库 team_home、应用账号 team，并导入 data/install.sql。
#      install.sql 会先删除同名表，已有数据时脚本会要求二次确认。
#   3) 管理员口令只写入临时配置文件，退出时自动删除；不会写进仓库。
#   4) 非交互执行时可通过环境变量提供管理员口令，并设置 FORCE=1 跳过确认：
#        MYSQL_ADMIN_PASSWORD='***' FORCE=1 bash install.sh
# =====================================================================
set -euo pipefail

cd "$(dirname "$0")"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-team_home}"
DB_USER="${DB_USER:-team}"
DB_PASS="${DB_PASS:-team123456}"
MYSQL_ADMIN_USER="${MYSQL_ADMIN_USER:-root}"
MYSQL_ADMIN_PASSWORD="${MYSQL_ADMIN_PASSWORD:-}"

CNF=""
SQL_TMP=""
cleanup() {
    if [ -n "$CNF" ]; then rm -f "$CNF"; fi
    if [ -n "$SQL_TMP" ]; then rm -f "$SQL_TMP"; fi
}
trap cleanup EXIT

fail() {
    echo "[错误] $*" >&2
    exit 1
}

valid_identifier() {
    [[ "$1" =~ ^[A-Za-z_][A-Za-z0-9_]*$ ]]
}

escape_sql_string() {
    # MySQL 默认把反斜杠当转义符，单引号需写成两个单引号
    printf '%s' "$1" | sed -e 's/\\/\\\\/g' -e "s/'/''/g"
}

valid_identifier "$DB_NAME" || fail "数据库名只能包含字母、数字和下划线，且不能以数字开头：$DB_NAME"
valid_identifier "$DB_USER" || fail "数据库账号只能包含字母、数字和下划线，且不能以数字开头：$DB_USER"

command -v php >/dev/null 2>&1 || fail "未找到 php，请先安装 PHP 8.0+ 并加入 PATH"
command -v mysql >/dev/null 2>&1 || fail "未找到 mysql 客户端，请先安装 MySQL 8.0+ 客户端并加入 PATH"

php -r 'exit(PHP_VERSION_ID >= 80000 ? 0 : 1);' \
    || fail "需要 PHP 8.0 及以上，当前版本：$(php -r 'echo PHP_VERSION;')"

if [ -z "$MYSQL_ADMIN_PASSWORD" ]; then
    [ -t 0 ] || fail "非交互环境请用 MYSQL_ADMIN_PASSWORD 提供 MySQL 管理员口令"
    read -r -p "MySQL 管理员账号（默认 ${MYSQL_ADMIN_USER}）：" input_user || fail "读取输入失败"
    MYSQL_ADMIN_USER="${input_user:-$MYSQL_ADMIN_USER}"
    read -r -s -p "MySQL 管理员密码：" MYSQL_ADMIN_PASSWORD || fail "读取输入失败"
    echo
    [ -n "$MYSQL_ADMIN_PASSWORD" ] || fail "管理员密码不能为空"
fi

CNF="$(mktemp "${TMPDIR:-/tmp}/teamhub-mysql.XXXXXX")"
SQL_TMP="$(mktemp "${TMPDIR:-/tmp}/teamhub-install.XXXXXX.sql")"
chmod 600 "$CNF" "$SQL_TMP"

# 口令不放在命令行参数里，避免出现在 ps / 命令历史中
cat > "$CNF" <<EOF
[client]
host=${DB_HOST}
port=${DB_PORT}
user=${MYSQL_ADMIN_USER}
password=${MYSQL_ADMIN_PASSWORD}
default-character-set=utf8mb4
EOF

echo "[1/4] 检查 MySQL 连接 ..."
MYSQL_VERSION="$(mysql --defaults-extra-file="$CNF" -N -B -e 'SELECT VERSION();' 2>/dev/null)" \
    || fail "无法连接 MySQL（${DB_HOST}:${DB_PORT}），请检查服务是否启动、管理员账号口令是否正确"

case "$MYSQL_VERSION" in
    *MariaDB*)
        fail "检测到 MariaDB ${MYSQL_VERSION}；install.sql 使用 MySQL 8 的 utf8mb4_0900_ai_ci，请改用 MySQL 8.0+"
        ;;
esac

MYSQL_MAJOR="${MYSQL_VERSION%%.*}"
if ! [[ "$MYSQL_MAJOR" =~ ^[0-9]+$ ]] || [ "$MYSQL_MAJOR" -lt 8 ]; then
    fail "需要 MySQL 8.0 及以上，当前版本：${MYSQL_VERSION}"
fi
echo "      MySQL ${MYSQL_VERSION}"

echo "[2/4] 检查目标数据库 ..."
TABLE_COUNT="$(mysql --defaults-extra-file="$CNF" -N -B \
    -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}' AND table_type='BASE TABLE';")"
TABLE_COUNT="${TABLE_COUNT:-0}"

if [ "$TABLE_COUNT" -gt 0 ] && [ "${FORCE:-0}" != "1" ]; then
    echo "      数据库 ${DB_NAME} 已有 ${TABLE_COUNT} 张表；install.sql 会先删除同名表并重建。"
    [ -t 0 ] || fail "非交互环境请设置 FORCE=1 确认覆盖"
    read -r -p "      确认继续？输入 yes：" answer || fail "读取输入失败"
    [ "$answer" = "yes" ] || { echo "已取消，未修改数据库。"; exit 0; }
fi

echo "[3/4] 创建数据库与应用账号 ..."
ESCAPED_PASS="$(escape_sql_string "$DB_PASS")"
{
    printf 'CREATE DATABASE IF NOT EXISTS `%s` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;\n' "$DB_NAME"
    for host in localhost 127.0.0.1; do
        printf "CREATE USER IF NOT EXISTS '%s'@'%s' IDENTIFIED BY '%s';\n" "$DB_USER" "$host" "$ESCAPED_PASS"
        printf "ALTER USER '%s'@'%s' IDENTIFIED BY '%s';\n" "$DB_USER" "$host" "$ESCAPED_PASS"
        printf 'GRANT ALL PRIVILEGES ON `%s`.* TO '\''%s'\''@'\''%s'\'';\n' "$DB_NAME" "$DB_USER" "$host"
    done
    printf 'FLUSH PRIVILEGES;\n'
} > "$SQL_TMP"

mysql --defaults-extra-file="$CNF" < "$SQL_TMP" \
    || fail "创建数据库或应用账号失败（管理员账号需要 CREATE USER / GRANT 权限）"

echo "[4/4] 导入 data/install.sql ..."
if [ "$DB_NAME" = "team_home" ]; then
    mysql --defaults-extra-file="$CNF" < data/install.sql || fail "导入 install.sql 失败"
else
    # install.sql 内固定写的是 team_home，自定义库名时只替换建库与 USE 两行
    sed -E \
        -e "s/^CREATE DATABASE IF NOT EXISTS team_home\$/CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`/" \
        -e "s/^USE team_home;\$/USE \`${DB_NAME}\`;/" \
        data/install.sql \
        | mysql --defaults-extra-file="$CNF" \
        || fail "导入 install.sql 失败"
fi

TABLE_COUNT="$(mysql --defaults-extra-file="$CNF" -N -B \
    -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}' AND table_type='BASE TABLE';")"
VIEW_COUNT="$(mysql --defaults-extra-file="$CNF" -N -B \
    -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}' AND table_type='VIEW';")"

[ "$TABLE_COUNT" -ge 10 ] || fail "导入后只有 ${TABLE_COUNT} 张表，未达到课程要求的 10 张以上"

cat <<EOF

部署完成：数据库 ${DB_NAME}，表 ${TABLE_COUNT} 张，视图 ${VIEW_COUNT} 个。

启动方式（PHP 内置服务器，仅用于本地演示）：
  export DB_HOST=${DB_HOST}
  export DB_PORT=${DB_PORT}
  export DB_NAME=${DB_NAME}
  export DB_USER=${DB_USER}
  export DB_PASS='安装时使用的数据库口令'
  php -S localhost:8080 -t public

然后访问 http://localhost:8080/
后台入口 http://localhost:8080/index.php?r=auth/login
初始账号见 data/install.sql 文件头；首次登录会要求修改密码。

生产环境请用 Apache/Nginx，并把站点根目录指向 code/public，
同时允许 public/.htaccess 生效（Apache 需 AllowOverride All）。
EOF

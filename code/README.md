# 团队主页 —— 课程设计项目源码

PHP 8 + MySQL 8 实现的团队主页网站：前台提供团队/个人展示、作业下载、留言与动态；后台提供内容编辑、发布、审计与数据看板。功能范围详见 `docs/team/01-需求文档`，数据库设计详见 `docs/team/02-设计文档`。

- 自建轻量 MVC：`Controller → Model → View`，不依赖 Laravel/ThinkPHP
- PDO 原生预处理，不依赖 ORM
- Session 认证 + RBAC 权限 + CSRF 防护
- Docker Compose 一键部署（PHP 8.2 + Apache + MySQL 8.0）

## 目录

```
code/
├── docker-compose.yml     # 一键部署：web(PHP+Apache) + db(MySQL)
├── Dockerfile             # web 镜像定义
├── .env.example           # 环境变量样例（复制为 .env）
├── apache-site.conf       # Apache 虚拟主机（站点根指向 public/）
├── apache-cache.conf      # 静态资源缓存策略
├── uploads.ini            # PHP 上传限制与安全基线
├── app/
│   ├── core/              # Db / Auth 等框架核心
│   ├── controller/        # 控制器
│   ├── model/             # 模型
│   └── view/              # 视图
├── config/
│   ├── app.php            # APP_KEY、会话、上传上限
│   └── database.php       # 数据库连接配置（环境变量优先）
├── public/
│   ├── index.php          # 统一入口（兼作注释头范例）
│   ├── .htaccess          # /healthz 重写
│   └── static/            # css/js/img/uploads
├── data/
│   ├── install.sql        # 数据库唯一真源（建表 + 初始数据）
│   ├── team/              # 团队文档 PDF（下载中心）
│   └── personal/          # 个人作业 zip（下载中心）
├── storage/               # 运行时日志/缓存（不在 Web 根内）
├── install.bat            # 无 Docker 环境备选（Windows）
└── install.sh             # 无 Docker 环境备选（Linux/macOS）
```

## 环境要求

| 方式 | 要求 |
| --- | --- |
| Docker（推荐） | Docker Desktop / Docker Engine，含 Compose v2 |
| 手动部署 | PHP 8.0+（含 PDO MySQL、fileinfo、mbstring 扩展）、MySQL 8.0+、Apache/Nginx 或 PHP 内置服务器 |

## Docker 一键部署（推荐）

```bash
cd code
docker compose up -d --build
docker compose ps                 # 等待 db / web 均为 healthy
```

- 前台：<http://localhost:8080/>
- 后台：<http://localhost:8080/index.php?r=auth/login>
- 健康检查：<http://localhost:8080/healthz>（同时检查 Apache 与数据库，正常返回 200 JSON）
- 初始账号见 `data/install.sql` 文件头（课程演示账号，首次登录强制改密）

首次启动时 MySQL 数据卷为空，容器会自动导入 `data/install.sql`。之后修改 SQL 不会自动重跑；需要重建演示库时执行：

```bash
docker compose down -v           # 警告：删除数据库卷，数据不可恢复
docker compose up -d --build
```

> `down -v` 会清空数据库，请先按下方“备份与恢复”导出。

国内网络拉取镜像失败时，先在 Docker Desktop 配置镜像加速源，或改用可访问的 MySQL/PHP 基础镜像并同步修改 `Dockerfile`、`docker-compose.yml`。

### 常用运维命令

```bash
docker compose logs -f web       # Apache / PHP 日志
docker compose logs -f db        # MySQL 日志
docker compose restart web       # 重启 Web
docker compose exec db mysql -uteam -p team_home   # 进入数据库（口令见 .env 或 compose 默认值）
```

生产环境请通过 `code/.env` 覆盖 `MYSQL_ROOT_PASSWORD`、`MYSQL_PASSWORD`，不要把真实口令提交到 Git；`docker compose` 会自动读取同目录 `.env`。

## 手动部署（无 Docker）

### 方式 A：安装脚本

Linux/macOS：

```bash
cd code
bash install.sh
```

Windows（phpStudy / XAMPP，需先把 `php`、`mysql` 加入 PATH）：

```bat
install.bat
```

脚本会检查 PHP/MySQL 版本，创建数据库 `team_home` 与应用账号 `team`，导入 `data/install.sql`，并打印启动命令。`install.sql` 会先删除同名表，库中已有数据时脚本会要求输入 `yes` 二次确认。

### 方式 B：手动执行

```bash
mysql -u root -p -e "CREATE DATABASE team_home DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci"
mysql -u root -p -e "CREATE USER 'team'@'localhost' IDENTIFIED BY 'team123456'; GRANT ALL ON team_home.* TO 'team'@'localhost';"
mysql -u root -p team_home < data/install.sql
```

启动 PHP 内置服务器（仅本地演示）：

```bash
export DB_HOST=127.0.0.1 DB_PORT=3306 DB_NAME=team_home DB_USER=team DB_PASS=team123456
php -S localhost:8080 -t public
```

> 内置服务器不读取 `.htaccess`，`/healthz` 不可用，请改用 `/index.php?r=system/healthz`。

生产环境请使用 Apache/Nginx，并把站点根目录指向 `code/public`：

```apache
<VirtualHost *:80>
    DocumentRoot "E:/path/to/code/public"
    <Directory "E:/path/to/code/public">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

不要将站点根指向 `code/`；`app/`、`config/`、`data/`、`storage/` 不在 Web 根内，可避免源码和数据库脚本被直接下载。上传目录 `public/static/uploads` 已带 `.htaccess` 禁止脚本执行，部署时需保证 Apache 允许 `AllowOverride`。

## 配置

`config/database.php` 优先读取环境变量：

| 变量 | 默认值 | 说明 |
| --- | --- | --- |
| `DB_HOST` | `db` | Docker 内为服务名；手动部署用 `127.0.0.1` |
| `DB_PORT` | `3306` | MySQL 端口 |
| `DB_DATABASE` / `DB_NAME` | `team_home` | 数据库名 |
| `DB_USERNAME` / `DB_USER` | `team` | 数据库账号 |
| `DB_PASSWORD` / `DB_PASS` | `team123456` | 数据库口令（演示默认值，生产必须覆盖） |
| `APP_DEBUG` | `false` | 设为 `true` 时输出详细错误，仅限本地排障 |
| `APP_BASE` | 自动识别 | 部署在子目录时设为 `/teamhub` 之类的前缀 |

## 数据库备份与恢复

以下命令在 Git Bash / macOS / Linux 执行；PowerShell 中把 `$(date +%F)` 换成手写日期。

备份（逻辑备份，含触发器与存储过程）：

```bash
docker exec teamhome-db sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" \
  --single-transaction --routines --triggers --default-character-set=utf8mb4 \
  --result-file=/tmp/team_home_backup.sql team_home'
docker cp teamhome-db:/tmp/team_home_backup.sql "./backup_team_home_$(date +%F).sql"
docker exec teamhome-db rm -f /tmp/team_home_backup.sql
```

恢复（目标库需已存在）：

```bash
docker cp ./backup_team_home_2026-11-08.sql teamhome-db:/tmp/restore.sql
docker exec teamhome-db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" \
  --default-character-set=utf8mb4 team_home < /tmp/restore.sql'
docker exec teamhome-db rm -f /tmp/restore.sql
```

上传的图片存放在 `public/static/uploads/`，不在数据库中，备份数据库时请一并备份该目录：

```bash
tar -czf "uploads_$(date +%F).tar.gz" public/static/uploads
```

## 验收清单

1. `docker compose ps` 中 `db`、`web` 均为 `healthy`
2. 前台首页、团队页、成员页、项目页、公告页、下载中心可正常打开
3. `/healthz` 返回 `{"status":"ok","database":"ok",...}`
4. 后台登录后可编辑团队信息、成员、项目、公告、资源、留言与站点设置
5. 未登录访问后台被重定向到登录页；低权限账号访问越权页面返回 403
6. 上传图片后可在前台显示；替换/删除后旧文件按引用情况清理
7. 数据库表数量大于 10（当前 21 张表、3 个视图）

## 约定

- 命名规范、注释头模板、Git 流程见仓库根 `README.md`「开发规范」；每个 PHP 文件必须带注释头（范例：`public/index.php`）。
- 数据库改动一律修改 `data/install.sql`，并在干净库上验证可完整还原，禁止只改本地库。
- 智能体/自动化编辑本目录前先阅读仓库根目录 `AGENTS.md`。

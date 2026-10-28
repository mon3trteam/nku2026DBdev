# 团队主页 —— 课程设计项目源码

PHP + MySQL 实现的团队主页网站：前台提供团队/个人展示、留言等功能并提供作业下载中心；后台提供信息编辑、内容发布等功能（功能范围详见 `docs/team/01-需求文档`）。

## 目录

```
code/
├── docker-compose.yml     # 一键部署：web(PHP+Apache) + db(MySQL)
├── Dockerfile             # web 镜像定义
├── app/
│   ├── controller/        # 控制器（每名组员至少 1 个）
│   ├── model/             # 模型（每名组员至少 1 个）
│   └── view/              # 视图（每名组员至少 1 个）
├── public/
│   ├── index.php          # 统一入口（兼作注释头范例）
│   └── static/            # css/js/img
├── data/
│   ├── install.sql        # 数据库唯一真源（建表 + 初始数据；表清单以设计文档为准）
│   ├── team/              # 团队文档 PDF（下载中心提供下载）
│   └── personal/          # 个人作业 zip（下载中心提供下载）
├── install.bat            # 无 Docker 环境备选（Windows）
└── install.sh             # 无 Docker 环境备选（Linux/Mac）
```

## 运行

### Docker 一键部署（推荐）

```bash
docker compose up -d        # 首次启动 db 自动导入 data/install.sql
# 前台 http://localhost:8080/
```

### 手动部署（phpStudy / XAMPP）

```bash
mysql -u root -p -e "CREATE DATABASE team_home DEFAULT CHARSET utf8mb4"
mysql -u root -p team_home < data/install.sql
# 站点根目录指向 code/public，或：cd public && php -S localhost:8080
```

初始账号在 `install.sql` 定稿时写入并在文件头注明。

## 约定

- 命名规范、注释头模板、Git 流程见仓库根 README「开发规范」；每个 PHP 文件必须带注释头（范例：`public/index.php`）。
- 数据库改动一律修改 `data/install.sql` 并在干净库上验证可完整还原，禁止只改本地库。
- 智能体/自动化编辑本目录前先阅读仓库根目录 `AGENTS.md`。

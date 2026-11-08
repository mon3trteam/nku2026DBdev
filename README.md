# 团队主页 · 课程设计项目（nku2026DBdev）

> **课程**：互联网数据库开发（南开大学 计算机学院）　|　**指导教师**：乜鹏
> **截止**：11.30 前将全部作业发送至 nkdbis_homework@163.com，并与助教联系项目演示
> **仓库**：https://github.com/mon3trteam/nku2026DBdev

> ⚠️ **文档优先**：本 README 中涉及功能范围、技术选型、分工、数据库设计的内容，均以 `docs/team/` 最新文档为准；两者不一致时，先改文档、再同步本文件（维护规则见 `AGENTS.md`）。仓库中未定的事项一律标注「待定」，禁止凭空填写。

## 项目信息

| 项 | 内容 | 来源 |
| --- | --- | --- |
| 项目 | 团队主页网站：前台（团队及个人展示、留言等）+ 后台（团队及个人信息编辑、信息发布等） | 作业要求 |
| 硬性指标 | 数据库表数量 **>10 张**；每名组员编写 **MVC 三层各至少 1 个文件**；SQL 放 `/data/install.sql`；团队作业在 `/data/team/`、个人作业在 `/data/personal/`；项目页面提供两类作业的**下载链接** | 作业要求 |
| 加分项 | 动态图形展示（已落地：看板 ECharts 本地 vendor 3 图）；一键部署（已落地：Docker Compose） | 作业要求 + 团队决策 |
| 技术栈 | PHP 8.1+（容器 8.2）· MySQL 8.0 · 自建轻量 MVC · Bootstrap 5 + 原生 JS（无 Node 构建链）· ECharts 5.6 本地 vendor | `docs/team/01` §2.7、`docs/team/02` ADR-01~08 |
| 产品名 / 队名 | 产品名 **数聚 TeamHub**（01/02 文档定稿）；团队代号 M3；成员名单与分工以《项目展示 PPT》为准 | `docs/team/01` 封面 |
| 运行数据 | 数据库在 Docker 命名卷 `db_data`；上传文件在命名卷 `uploads_public` / `uploads_storage`（非 Docker 部署时在 `public/static/uploads`、`storage/uploads`，均已 gitignore）。**例外**：下载中心"团队作业/个人作业"分类的文件按作业要求固定放 `code/data/team|personal/`（这是要随仓库交付的内容，非运行垃圾）。**真实站点内容（成员/公告/留言等）永不进仓库**：后台「数据包」一键导出 ZIP / 导入恢复（FR-DB-04），上云 = 装模板 + 导数据包 | 本仓库约定 |

## 项目简介

团队主页课程设计网站。前台提供团队及个人展示、留言等功能，并提供团队文档与个人作业的下载中心；后台提供团队及个人信息的编辑、信息的发布等功能。数据库表数量大于 10 张，鼓励动态图形展示。具体功能清单、用例与数据字典见 `docs/team/01-需求文档/` 与 `docs/team/02-设计文档/`。

## 仓库结构

```
nku2026DBdev/
├── README.md                  ← 本文件：总览 / 模块职责 / 规范 / 交付清单
├── AGENTS.md                  ← 智能体与开发者的仓库约定（含"文档优先"维护规则）
├── .gitignore
├── docs/                      ← 【报告文档区】README 事实内容的信息来源
│   ├── team/
│   │   ├── 01-需求文档/       # 功能需求、用例、可行性分析
│   │   ├── 02-设计文档/       # 数据库设计（>10 表）、数据字典、模块图、流程图
│   │   ├── 03-实现文档/       # 任务分工过程、Git 提交记录、主要代码及目录展示
│   │   ├── 04-用户手册/
│   │   ├── 05-部署文档/       # 部署还原流程（一键部署说明）
│   │   └── 06-项目展示PPT/    # 必含小组分工与工作量分配
│   └── assets/                # 文档公共图片（E-R 图、模块图等）
├── personal/                  ← 【个人作业区】每人一个 学号_姓名/ 目录
│   └── 学号_姓名/作业N/       # 作业1/2/3 源文件；交付前打包 zip 至 code/data/personal/
└── code/                      ← 【项目源码】
    ├── docker-compose.yml     # 一键部署：web(PHP+Apache) + db(MySQL)，上传目录用命名卷解耦
    ├── Dockerfile             # web 镜像定义（含入口脚本）
    ├── docker-entrypoint.sh   # 容器启动初始化（上传目录安全规则补齐）
    ├── docker/htaccess/       # 上传卷安全规则模板
    ├── install.bat / install.sh   # 无 Docker 环境的本地部署备选脚本
    ├── app/
    │   ├── controller/        # 控制器
    │   ├── model/             # 模型
    │   └── view/              # 视图（admin/ 后台、public/ 前台、me/ 成员中心）
    ├── public/
    │   ├── index.php          # 统一入口（兼作注释头范例）
    │   └── static/            # css / js / img / vendor（Bootstrap、ECharts 本地化）
    └── data/                  # ★ 作业要求的固定路径，不可改名
        ├── install.sql        # 数据库唯一真源（21 表 + 视图 + 种子）
        ├── team/              # 团队文档 PDF（资源记录指向此目录，下载走控制器）
        └── personal/          # 个人作业 zip（同上）
```

**发布流程**：文档在 `docs/team/` 以 Markdown 协作 → 定稿导出 PDF（命名 `队名_文档名(学号1_学号2_学号3).pdf`）→ 复制到 `code/data/team/`；个人作业在 `personal/` 维护源文件 → 打包 `作业N(学号_姓名).zip` → 复制到 `code/data/personal/`。下载中心由后台「资源管理」维护记录（分类：团队作业/个人作业等），文件放入上述目录或经后台上传，下载统一走 `resource/download` 控制器（计数 + realpath 路径校验，不暴露磁盘路径）。

## 模块与职责

分工按模块划分；**各模块负责人待小组确认后在《项目展示 PPT》与最终报告中公布**。

### 1. 文档线

| 工作项 | 产出物 | 负责人 |
| --- | --- | --- |
| 分析需求、设计功能 | 需求文档（`docs/team/01`） | 待定 |
| 数据库与系统设计（E-R 图、>10 表数据字典、模块图、流程图） | 设计文档（`docs/team/02`） | 待定 |
| 记录实现过程（任务分工过程、Git 提交记录、代码展示） | 实现文档（`docs/team/03`） | 待定 |
| 部署与还原流程撰写 | 部署文档（`docs/team/05`） | 待定 |

### 2. 前端界面

| 工作项 | 对应页面 | 负责人 |
| --- | --- | --- |
| 首页 | 前台视图 | 待定 |
| 团队介绍 | 前台视图 | 待定 |
| 成员主页 | 前台视图 | 待定 |

### 3. 后端认证与服务

| 工作项 | 对应模块 | 负责人 |
| --- | --- | --- |
| 后端认证（登录、会话、权限） | 控制器/模型 + 数据表 | 待定 |
| 管理员界面（后台框架、信息发布等） | 后台视图 | 待定 |
| 个人资料编辑区域 | 控制器/模型 + 数据表 | 待定 |
| SQL 框架（表结构、`install.sql` 维护、数据访问层） | `code/data/install.sql`、模型层 | 待定 |

### 4. 汇报展示

| 工作项 | 产出物 | 负责人 |
| --- | --- | --- |
| PPT 制作（含小组分工与工作量分配） | 项目展示 PPT（`docs/team/06`） | 待定 |
| 现场展示、通晓整个项目以应对提问 | 项目演示 | 待定 |

## 待定事项与落地时机

**不提前虚构**：已落地项注明依据；仍未定的注明触发条件与落地动作，触发后更新本表与相关正文。

### 已落地（有据可查）

| # | 事项 | 落地结果 | 依据 |
| --- | --- | --- | --- |
| 1 | 开发框架与前端选型 | 自建轻量 MVC + Bootstrap 5 原生 JS（无 Node 构建链），Session 认证、PDO 直连 | `docs/team/02` ADR-01~08 |
| 2 | 数据库表清单（>10 张） | **21 张表 + 3 视图**，空库一次安装可还原（已实测） | `docs/team/02` §4.3、`code/data/install.sql` |
| 3 | 前台 / 后台代码组织 | 视图按 `admin/`（后台）、`public/`（前台）、`me/`（成员中心）分目录；控制器以 `XxxController` 命名区分 | `code/app/view/` 实际结构 |
| 4 | 初始账号 | admin（超管）/ editor（编辑）/ 4 个成员账号，bcrypt 散列 + 首次登录强制改密 | `code/data/install.sql` 文件头 |
| 5 | 队名 / 产品名 | 产品名 **数聚 TeamHub**，团队代号 M3 | `docs/team/01`、`02` 封面 |
| 7 | 团队文档 | 01 需求、02 设计已定稿；03 实现（3 份草稿）、05 部署已产出；**04 用户手册、06 PPT 未开始** | `docs/team/` 实际文件 |
| 9 | 下载中心 | 已实现：资源记录 + 分类 + 下载计数 + realpath 路径校验流式输出 | `ResourceController`、FR-PUB-07 / AC-10 |
| 10 | 动态图形展示（加分项） | 已落地：看板接入本地 ECharts 5.6（趋势折线含访问量/访客数双系列 / 技能条形 / 留言环形），CSS 条图保留为无 JS 降级 | `code/app/view/admin/dashboard.php` |
| 11 | Docker 站点根配置 | 已启用：`apache-site.conf` DocumentRoot 指向 `public/`，源码目录双重拒绝访问 | `code/apache-site.conf` |
| 12 | 本地部署脚本 | 已实现：`install.sh`（165 行）/ `install.bat`（190 行），建库建号、导入 install.sql、二次确认与临时口令清理 | `code/install.sh`、`code/install.bat` |

### 仍待定

| # | 事项 | 触发条件 | 落地动作 |
| --- | --- | --- | --- |
| 6 | 成员名单、各模块负责人与工作量分配 | 小组确认后（最终以《项目展示 PPT》为准） | 填入「模块与职责」各表；PPT 必含 |
| 8 | 个人作业入库 | 各成员作业完成后 | 源文件放 `personal/学号_姓名/作业N/`；打包 zip 至 `code/data/personal/` |
| 13 | 杨斯源同学学号 | 本人提供后 | 替换代码注释头中 65 处「学号待填」 |
| 14 | 钱泊瑜同学的 MVC 归属文件 | 本人认领一个 P1 小功能（如 FR-DB-05 后台健康检查页、公告 CSV 导出）后 | 补齐"每人 MVC 三层各 ≥1 文件"的 PPT 硬指标（当前 金暄琛/杨斯源 已覆盖三层，黄浩天 以入口与图表修改计入，钱泊瑜 0 个） |
| 15 | 六份文档导出 PDF + 正式命名 | 文档定稿后 | 按 `队名_文档名(学号…).pdf` 导出并复制到 `code/data/team/` |
| 16 | 演示种子数据 → 真实数据 | 团队信息收集表填写完成后 | 后台录入 → 导出数据包 ZIP；云端部署 = clone 模板 + 导入数据包（install.sql 不回填真实数据） |

## 快速开始

### 方式一：Docker 一键部署（推荐）

```bash
git clone https://github.com/mon3trteam/nku2026DBdev.git
cd nku2026DBdev/code
docker compose up -d          # 启动 web(8080) + db(3306)，首次构建自动导入 install.sql
# 前台 http://localhost:8080/
```

> 国内网络拉取镜像受限时，先配置镜像加速源（如 `docker.m.daocloud.io`）。
> 运行数据解耦：数据库与上传文件分别存放在命名卷 `db_data` / `uploads_public` / `uploads_storage`，`down -v` 才会清除；普通重建容器不丢数据。

### 方式二：手动部署（phpStudy / XAMPP）

```bash
mysql -u root -p -e "CREATE DATABASE team_home DEFAULT CHARSET utf8mb4"
mysql -u root -p team_home < code/data/install.sql
# 站点根目录指向 code/public，或：cd code/public && php -S localhost:8080
```

**初始账号**（完整列表见 `code/data/install.sql` 文件头）：

| 账号 | 初始密码 | 角色 |
| --- | --- | --- |
| admin | Admin@123456 | 超级管理员 |
| editor | Member@123456 | 编辑 |
| （4 个成员账号） | Member@123456 | 成员 |

> ⚠️ **首次登录强制修改密码**：以上账号的初始密码随 `install.sql` 存在于公开仓库中，任何人都看得到，因此系统要求首次登录后立即改密，否则停留在改密页无法进入其他功能——这是一次性的安全设计，改完后不再出现。忘记改后的密码由管理员在「用户管理」重置临时密码。

## 开发规范（强制）

### 命名规范（本仓库约定）

| 对象 | 规范 | 示例 |
| --- | --- | --- |
| PHP 类 | PascalCase | `NewsController` |
| 函数 / 变量 | camelCase | `getNewsList()` |
| 常量 | ALL_UPPER | `DB_HOST` |
| 数据库表 / 字段 | snake_case | `news_categories` |
| 控制器 / 模型文件 | `XxxController.class.php` / `XxxModel.class.php` | `NewsController.class.php` |
| 视图文件 | `小写_动作.php` | `news_list.php` |

### PHP 注释头（作业要求：每个 PHP 文件必加）

```php
<?php
/**
 * 文件名：NewsController.class.php
 * 功能：新闻模块控制器，处理新闻的增删改查与展示
 * 作者：学号 姓名
 * 创建日期：YYYY-MM-DD
 * 所属模块：新闻管理
 */
```

范例：`code/public/index.php`。

### Git 协作（本仓库约定）

**分支模型**

- `main`：稳定分支，始终保持可运行、可演示的状态；
- `dev`：开发主线，**所有日常改动只提交到 dev**。

**首次克隆项目**

```bash
git clone https://github.com/mon3trteam/nku2026DBdev.git
cd nku2026DBdev
git checkout dev          # 切换到开发分支（首次克隆后执行一次）
```

**每次写代码前（必做）**

```bash
git checkout dev
git pull
```

**写代码 → 提交推送**

```bash
####
# 写代码
####

# 提交前先拉取他人改动，有冲突当场解决
git pull

# 查看修改了哪些文件
git status

# 添加文件（. 代表全部，也可只添加指定文件）
git add .

# 提交到本地仓库
git commit -m "类型: 描述"    # 例：feat: 完成登录页面布局

# 推送到团队仓库 dev 分支
git push
```

**合入 main**

```bash
# 1) 先把最新 main 合并进 dev，确认无冲突、本地运行无误
git checkout dev
git merge main

# 2) 检查无误后，再把 dev 合入 main 并推送
git checkout main
git merge dev
git push
```

**提交信息**：格式 `类型: 描述`，描述用中文一句话说清做了什么（如"完成登录页面布局"）；需要标明范围时可写 `类型(范围): 描述`（如 `feat(auth): 完成登录页面布局`）。类型必须取下表之一：

| 类型 | 含义 |
| --- | --- |
| `feat` | 新增功能 |
| `fix` | 修复 Bug |
| `docs` | 只修改文档 |
| `style` | 代码格式调整（不影响逻辑） |
| `refactor` | 重构（不新增功能、不修 Bug） |
| `test` | 测试相关 |
| `chore` | 构建、工具、依赖等杂务 |

**任务管理**：待做任务与进度直接记录在 `docs/` 项目文档中维护，不使用外部看板工具。

## 交付清单对照（提交前逐项勾选）

| 交付物 | 要求 | 仓库位置 | 状态 |
| --- | --- | --- | --- |
| 团队1 需求文档 | 功能需求 / 用例 / 可行性 | `docs/team/01-需求文档/` → PDF 至 `code/data/team/` | ☐ |
| 团队2 设计文档 | 数据库字典、模块图、流程图（>10 表） | `docs/team/02-设计文档/` | ☐ |
| 团队3 实现文档 | 分工过程、Git 提交记录、代码展示 | `docs/team/03-实现文档/` | ☐ |
| 团队4 用户手册 | 自定义 | `docs/team/04-用户手册/` | ☐ |
| 团队5 部署文档 | 部署还原流程；一键部署加分 | `docs/team/05-部署文档/` | ☐ |
| 团队6 项目展示PPT | **必含小组分工与工作量分配** | `docs/team/06-项目展示PPT/` | ☐ |
| 课程设计项目 | 前后台完整、>10 表、下载中心、每人 MVC 各 ≥1 文件 | `code/` | ☐ |
| install.sql | 建表 + 初始数据，干净库可完整还原 | `code/data/install.sql` | ☐ |
| 个人作业 1–3 | `作业N(学号_姓名).zip` | `personal/` → zip 至 `code/data/personal/` | ☐ |
| 文档命名 | `队名_文档名(学号1_学号2_学号3).pdf` | 导出 PDF 时按此命名 | ☐ |
| 提交 | 11.30 前发送至 nkdbis_homework@163.com，并联系助教演示 | — | ☐ |

---

*本 README 的事实性内容以 `docs/team/` 文档为准；文档更新后须按 `AGENTS.md` 的规则同步修订本文件。*

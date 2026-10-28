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
| 加分项 | 动态图形展示；一键部署（网页部署或批处理文件）——本项目采用 **Docker Compose** 实现一键部署 | 作业要求 + 团队决策 |
| 技术栈 | PHP + MySQL；开发框架、前端模板等选型**待需求/设计文档定稿** | 待定（`docs/team/01`、`02`） |
| 组队信息 | 队名、成员、各模块负责人与工作量分配：**待定**，最终在《项目展示 PPT》中公布 | 作业要求 |

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
    ├── docker-compose.yml     # 一键部署：web(PHP+Apache) + db(MySQL)
    ├── Dockerfile             # web 镜像定义
    ├── install.bat / install.sh   # 无 Docker 环境的本地部署备选脚本
    ├── app/
    │   ├── controller/        # 控制器
    │   ├── model/             # 模型
    │   └── view/              # 视图
    ├── public/
    │   ├── index.php          # 统一入口（兼作注释头范例）
    │   └── static/            # css / js / img
    └── data/                  # ★ 作业要求的固定路径，不可改名
        ├── install.sql        # 数据库导出（建表 + 初始数据，表清单以设计文档为准）
        ├── team/              # 6 份团队文档 PDF（供下载中心下载）
        └── personal/          # 个人作业 zip（供下载中心下载）
```

**发布流程**：文档在 `docs/team/` 以 Markdown 协作 → 定稿导出 PDF（命名 `队名_文档名(学号1_学号2_学号3).pdf`）→ 复制到 `code/data/team/`；个人作业在 `personal/` 维护源文件 → 打包 `作业N(学号_姓名).zip` → 复制到 `code/data/personal/`。网站下载中心页读取 `data/` 下两个目录生成下载链接。

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

以下事项当前均未确定，**不提前虚构**；每项注明触发条件与落地时要建立/修改的内容，触发后由负责人更新本表与相关正文。

| # | 待定事项 | 触发条件（什么时候定） | 落地时建立 / 修改 |
| --- | --- | --- | --- |
| 1 | 开发框架与前端模板选型 | 需求文档评审、设计文档定稿 | 更新本 README「项目信息 · 技术栈」；按所选框架调整 `code/` 骨架（若采用自带前后台结构的框架如 Yii2 advanced，按其 `frontend/backend` 结构重组并同步本 README 结构图） |
| 2 | 数据库表清单与数据字典（>10 张） | 设计文档定稿 | 建表与初始数据写入 `code/data/install.sql`，在干净库验证可完整还原 |
| 3 | 前台 / 后台代码子目录 | 开发对应模块时 | `app/controller/`、`app/view/` 下分别建立 `home/`（前台）与 `admin/`（后台）子目录，`app/model/` 前后台共用；若待定 #1 选定自带前后台结构的框架，则以框架结构为准 |
| 4 | 初始管理员账号 | `install.sql` 定稿时 | 账号与 bcrypt 散列写入 `install.sql`，并在该文件头注明 |
| 5 | 队名 | 小组确定后 | 更新本 README 标题与「项目信息」；此后团队文档 PDF 按 `队名_文档名(学号1_学号2_学号3).pdf` 命名 |
| 6 | 成员名单、各模块负责人与工作量分配 | 小组确认后（最终以《项目展示 PPT》为准） | 填入本 README「模块与职责」各表「负责人」列 |
| 7 | 六份团队文档内容 | 各模块负责人动笔时 | 在 `docs/team/01~06` 对应目录建立 Markdown 源稿；定稿导出 PDF 并复制到 `code/data/team/` |
| 8 | 个人作业入库 | 各成员作业完成后 | 源文件放 `personal/学号_姓名/作业N/`；交付前打包 `作业N(学号_姓名).zip` 复制到 `code/data/personal/` |
| 9 | 下载中心页面 | 开发该功能时 | 新建控制器与视图，读取 `code/data/team/`、`code/data/personal/` 生成下载链接（须过滤 `../` 防目录穿越） |
| 10 | 动态图形展示（加分项） | 设计文档确定统计维度后 | `public/static/` 引入图表库，开发统计视图与数据接口 |
| 11 | Docker 站点根目录配置 | MVC 骨架细化后 | 启用 `Dockerfile` 中注释的虚拟主机配置（站点根指向 `public/`） |
| 12 | 本地部署脚本实现（可选） | Docker 一键部署主流程跑通后 | 实现 `install.bat` / `install.sh`（无 Docker 环境的备选方案） |

## 快速开始

### 方式一：Docker 一键部署（推荐）

```bash
git clone https://github.com/mon3trteam/nku2026DBdev.git
cd nku2026DBdev/code
docker compose up -d          # 启动 web(8080) + db(3306)，首次构建自动导入 install.sql
# 前台 http://localhost:8080/
```

> 国内网络拉取镜像受限时，先配置镜像加速源（如 `docker.m.daocloud.io`）。

### 方式二：手动部署（phpStudy / XAMPP）

```bash
mysql -u root -p -e "CREATE DATABASE team_home DEFAULT CHARSET utf8mb4"
mysql -u root -p team_home < code/data/install.sql
# 站点根目录指向 code/public，或：cd code/public && php -S localhost:8080
```

初始账号将在 `install.sql` 定稿时写入并在其文件头注明。

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

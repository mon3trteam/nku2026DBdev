# AGENTS.md — 智能体（AI Agent）与开发者仓库约定

本文件供 AI 编码智能体（ZCode / Claude Code / Copilot 等）在本仓库内工作时读取。人类开发者同样适用。

## 会话开始必做（每次对话前执行）

1. **检查 `docs/team/` 是否有新增或更新的文档**（对比记忆中的状态）；
2. README 中涉及**功能范围、技术选型、模块分工、数据库设计**的描述，必须与 `docs/team/` 最新文档一致；**冲突时以文档为准**，并同步修改 README；
3. 若文档尚未产出（目录为空），对应内容在 README/代码注释中保持「待定」，**禁止凭空填写**；待定事项统一登记在 README「待定事项与落地时机」一节，某事项落地后应立即更新该表与相关正文。

## 信息来源优先级

```
作业硬性要求（PPT）
  > docs/team/ 定稿文档
    > 本仓库约定（命名、注释头、提交规范、目录结构）
      > 标记"待定"，等待上游确定
```

**禁止捏造**：不得虚构文档中不存在的信息——包括但不限于技术栈版本、表结构与表数量、模块负责人、进度日期、队名、成员名单。除约定俗成的工程规范（命名法、Conventional Commits、注释头格式等）外，一切事实性内容必须有出处：作业要求、docs/team 文档、或仓库内真实存在的文件。

## 仓库用途

南开大学《互联网数据库开发》课程设计：团队主页网站（PHP + MySQL，前后台）。仓库同时承载课程文档、各成员个人作业与项目源码。**下列路径为作业硬性要求，不得改名或移动**：

| 路径 | 含义 | 硬性要求 |
| --- | --- | --- |
| `code/data/install.sql` | 数据库唯一真源（建表+初始数据） | 必须存在；改动后须在干净库验证可完整还原 |
| `code/data/team/` | 团队文档 PDF | 网站下载中心读取此目录 |
| `code/data/personal/` | 个人作业 zip（`作业N(学号_姓名).zip`） | 同上 |
| `code/public/index.php` | 站点唯一入口 | 所有请求经此路由分发 |

作业其余硬性要求（数据库 >10 张表、每名组员 MVC 三层各 ≥1 文件、每个 PHP 文件注释头、项目页面提供作业下载链接、11.30 前邮件提交并约演示）见根 README「项目信息」「交付清单」。

## 目录地图

```
README.md        项目总览：模块职责、规范、交付清单（事实内容以 docs/team 为准）
AGENTS.md        本文件
docs/team/01~06  六份团队文档（Markdown 源稿，定稿后导出 PDF 到 code/data/team/）
docs/assets/     文档公共图片
personal/        个人作业源文件（学号_姓名/作业N/）
code/            项目源码
  app/controller|model|view/   MVC 三层
  public/static/  静态资源
  docker-compose.yml + Dockerfile   一键部署
  install.bat/install.sh   无 Docker 环境备选
```

## 编辑规则

1. **语言**：文档、注释、提交信息一律使用中文。
2. **每个 PHP 文件必须有注释头**（文件名/功能/作者[学号 姓名]/创建日期/所属模块），范例见 `code/public/index.php`。
3. **命名**：类 PascalCase、函数变量 camelCase、常量 ALL_UPPER、表与字段 snake_case、控制器/模型文件 `XxxController.class.php` / `XxxModel.class.php`、视图 `小写_动作.php`。
4. **数据库**：表数量必须 >10（作业硬性要求）；具体表清单、字段与索引**以 `docs/team/02-设计文档` 的数据字典为准**，一切表结构改动写入 `code/data/install.sql` 并保证幂等可重放，禁止只改代码不写 SQL。
5. **不预设负责人**：README 的模块职责表只列工作项，负责人留「待定」；具体人名与工作量分配只出现在最终《项目展示 PPT》与报告中。
6. **Git**：分支模型 `main`（稳定）+ `dev`（开发主线，日常改动只提交到 dev）；写码前 `git checkout dev && git pull`，提交前先 `git pull` 再 add/commit/push；合入 `main` 前先把 `main` 合并进 `dev` 并本地验证运行无误；提交信息 `类型: 描述`（feat 新功能 / fix 修 Bug / docs 文档 / style / refactor / test / chore）；**智能体不得自动合入或 push `main`**。
7. **保密**：不得将真实账号密码写入仓库；install.sql 中的初始账号密码使用 bcrypt 散列并在文件头注明。
8. **路径安全**：下载中心读取 `code/data/team|personal` 时须过滤 `../`，防目录穿越。

## 当前状态（2026-11-22 晚 交接页——换对话后先读本节）

- **队名已定稿：M 立方（M-Cubed）**，产品名 数聚 TeamHub；四名成员：黄浩天 2413421（组长）· 钱泊瑜 2411282 · 杨斯源 2412796 · 金暄琛 2411672（依据：答辩讲稿 + 服务器 team_profile）
- **分支**：main 与 dev 均已推送 GitHub（dev=737eb66，含合并钱泊瑜远端提交 54c28c6 健康检查页；main=1c8be16 快进推送）
- **线上**：http://39.96.23.4:8018 全部同步完成——logo.svg、注释头学号、钱泊瑜健康页（php -l 通过）、data/team 六份 PDF、data/personal 三份 zip；**下载中心已上线 9 条资源记录**（resources id 10~18，SQL 直建 media+resources，下载流实测 200）；四人档案 username=学号、display_name=真名（SQL 直改）
- **文档与 PDF**：六份 md 定稿；六份 PDF 已按 `M立方_文档名(2413421).pdf` 导出入 `code/data/team/`（管线：md→HTML→Edge 无头打印，mermaid 流程图/ER 图真渲染；PPT 版走 PowerPoint COM）
- **PPT**：`M立方-项目展示.pptx` 已走完审阅→修复→复审闭环（6/7/8/9/12 五页排版事故修复，复审全过，定稿）；讲稿 Q&A 已补 Datapackage 命名应答（路由 ucfirst 约定所致，代码非 bug）
- **个人作业**：黄浩天三份 zip 入 `code/data/personal/`，源文件入 `personal/2413421_黄浩天/作业N/`；其余成员待收
- **剩余待办**：① 后台重新导出数据包 ZIP（users 表与资源记录均为 SQL 直建，按数据解耦约定应在后台重导留档）② 录屏讲解（团队作业7）③ 其余成员个人作业 zip 收集入库 ④ 11.29 前邮件 nkdbis_homework@163.com + 联系助教（钱泊瑜 MVC 已由其远端提交 54c28c6 落地，无需再催）
- **已知坑**：本机到 GitHub 需代理（7890）或直连间歇可用；Git Bash 中文 grep 需用 python 代替；本机无 LibreOffice（C 盘容量警戒不装），PPT 渲染验证用 PowerPoint COM；install.sql 保持纯模板态（member1~4+admin/editor，勿加演示数据）

## 运行数据与解耦约定

1. 一切运行期写入的数据（数据库、上传文件）**不得提交进 Git**：`.gitignore` 已排除 `code/storage/`、`code/public/static/uploads/*`（保留 .htaccess）与 `code/.env`；
2. Docker 部署时上传目录映射为命名卷（`uploads_public`、`uploads_storage`）；安全规则模板在`code/docker/htaccess/`，由 `docker-entrypoint.sh` 每次启动强制覆盖到卷内（模板升级即生效），修改卷策略或模板须同步该脚本；**例外**：下载中心"团队作业/个人作业"分类文件按作业要求固定放 `code/data/team|personal/`（交付内容，需提交进仓库）；
3. `code/data/install.sql` 承载**表结构 + 模板初始数据**，永不回填真实数据；后台产生的真实内容通过"数据包"导出/导入迁移（后台 → 数据包，FR-DB-04），真实数据一律不进 Git 仓库。

## 常用命令

```bash
# 一键起环境
cd code && docker compose up -d
# 导入数据库（手动部署）
mysql -u root -p team_home < code/data/install.sql
# 内置服务器调试
cd code/public && php -S localhost:8080
```

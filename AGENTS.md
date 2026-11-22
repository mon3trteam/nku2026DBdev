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

## 当前状态（2026-11-22 交接页——换对话后先读本节）

- **分支**：本地 main = dev = `0061b2c`（含文档全量核对修订）；**GitHub 未同步**——网络不通（代理 7890 未开 + 直连被墙），恢复后执行 `git push origin main dev`
- **线上**：http://39.96.23.4:8018 运行最新代码（bind mount 即时生效），数据完好；member1/2 已实例化（黄浩天/钱泊瑜），member3/4 停用
- **文档**：六份 md 全部齐备并经子智能体全量核对修订（04 手册 15 处、03 虚构清零、05 双版本合并、02/01 口径同步）；金版两份已退役为过程稿
- **PPT**：`docs/team/06-项目展示PPT/M立方-项目展示.pptx` 已生成（13 页，未提交待审阅）；逐页讲稿 `答辩讲稿-逐页.md`（未跟踪）
- **品牌**：候选方案三套（数怪/怪才工坊/M立方）在桌面 `品牌方案/`；M立方素材：logo-mcubed.svg（待替换仓库 logo.svg）、cover-mcubed.png（4:3，已可上传）、badge-512.png（后台 Logo 槽用）
- **剩余待办**：① GitHub 推送（网络恢复后）② PPT 审阅定稿 + 讲稿定稿提交 ③ 04 手册定稿名与 PDF 导出（六份按 `队名_文档名(学号).pdf`）④ data/team|personal 填充作业 zip 与文档 PDF（黄浩天三份 zip 在桌面）⑤ 录屏讲解（团队作业7）⑥ member4 档案实例化或隐藏 ⑦ 钱泊瑜 MVC（健康检查页提示词已交给她）⑧ 11.29 前邮件 nkdbis_homework@163.com + 联系助教
- **已知坑**：本机到 GitHub 需代理（7890）或直连间歇可用，fetch/push 都要重试循环；服务器 39 内存小，禁止现场构建（镜像本地 save/load）；Git Bash 中文 grep 需用 python 代替；install.sql 已是纯模板态（member1~4+admin/editor，勿再加演示数据）

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

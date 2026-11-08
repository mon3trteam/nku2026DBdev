-- =====================================================================
-- 文件名：install.sql
-- 功能：团队主页项目数据库唯一真源（21 张表 + 3 个视图 + 初始数据）
-- 作者：（后端负责人 学号 姓名）
-- 创建日期：2026-11-08
-- 所属模块：数据库
--
-- 说明：
--   1) 本文件面向“空库一次安装”；会 DROP 同名视图与表，执行前请备份。
--   2) 表结构、字段、索引与视图以 docs/team/02-设计文档 的数据字典为准；
--      修改后必须在干净库上验证可完整还原（mysql < install.sql）。
--   3) 作业硬性要求：数据库表数量 > 10 张；本文件共 21 张表。
--   4) 初始账号（bcrypt 散列，严禁写入真实生产密码）：
--        admin        / Admin@123456   （超级管理员，must_change_password=1）
--        editor       / Member@123456  （编辑）
--        lin-zhiyuan  / Member@123456  （成员，绑定同名成员档案）
--        su-wanqing   / Member@123456  （成员）
--        chen-yiming  / Member@123456  （成员）
--        zhou-yuan    / Member@123456  （成员）
--   5) 种子中的成员、项目、公告、留言为课程演示数据，接入真实内容后可替换。
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 数据库名与 docker-compose.yml 的 MYSQL_DATABASE 保持一致
CREATE DATABASE IF NOT EXISTS team_home
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_0900_ai_ci;
USE team_home;

-- ---------------------------------------------------------------------
-- 0. 清理（按依赖反向顺序；视图先删）
-- ---------------------------------------------------------------------
DROP VIEW IF EXISTS v_dashboard_daily_views;
DROP VIEW IF EXISTS v_published_announcements;
DROP VIEW IF EXISTS v_public_members;

DROP TABLE IF EXISTS schema_migrations;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS login_logs;
DROP TABLE IF EXISTS page_views;
DROP TABLE IF EXISTS site_settings;
DROP TABLE IF EXISTS resources;
DROP TABLE IF EXISTS media;
DROP TABLE IF EXISTS message_replies;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS project_members;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS member_skills;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS members;
DROP TABLE IF EXISTS milestones;
DROP TABLE IF EXISTS team_profile;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;

-- ---------------------------------------------------------------------
-- 1. 账号权限（4 张）
-- ---------------------------------------------------------------------

CREATE TABLE roles (
    id          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '角色主键',
    code        VARCHAR(30)      NOT NULL                COMMENT '角色代码：super_admin/editor/member',
    name        VARCHAR(50)      NOT NULL                COMMENT '角色显示名',
    description VARCHAR(255)     NULL                    COMMENT '职责说明',
    is_system   TINYINT UNSIGNED NOT NULL DEFAULT 0      COMMENT '1=内置角色，不可删除',
    created_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (id),
    UNIQUE KEY uk_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='角色字典';

CREATE TABLE permissions (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '权限主键',
    code        VARCHAR(80)       NOT NULL                COMMENT '权限码，点号分隔，如 message.moderate',
    name        VARCHAR(80)       NOT NULL                COMMENT '中文名称',
    module      VARCHAR(30)       NOT NULL                COMMENT '所属模块',
    description VARCHAR(255)      NULL                    COMMENT '说明与使用场景',
    created_at  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (id),
    UNIQUE KEY uk_code (code),
    KEY idx_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='功能级权限码字典';

CREATE TABLE role_permissions (
    role_id       TINYINT UNSIGNED  NOT NULL COMMENT '角色 ID',
    permission_id SMALLINT UNSIGNED NOT NULL COMMENT '权限 ID',
    created_at    DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '授权时间',
    PRIMARY KEY (role_id, permission_id),
    KEY idx_permission (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='角色与权限多对多';

CREATE TABLE users (
    id                   BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT COMMENT '账号主键',
    role_id              TINYINT UNSIGNED NOT NULL DEFAULT 3      COMMENT '角色；默认成员',
    username             VARCHAR(50)      NOT NULL                COMMENT '登录用户名，3-50 位',
    email                VARCHAR(120)     NOT NULL                COMMENT '登录邮箱，同时用于联系',
    password_hash        VARCHAR(255)     NOT NULL                COMMENT 'password_hash() 结果，不保存明文',
    display_name         VARCHAR(50)      NOT NULL                COMMENT '后台显示名，可与成员姓名不同',
    avatar_media_id      BIGINT UNSIGNED  NULL                    COMMENT '账号头像，可选',
    status               TINYINT UNSIGNED NOT NULL DEFAULT 1      COMMENT '1 正常、0 禁用、2 锁定',
    failed_login_count   SMALLINT UNSIGNED NOT NULL DEFAULT 0     COMMENT '连续失败次数',
    locked_until         DATETIME         NULL                    COMMENT '锁定截止时间；空表示未锁定',
    must_change_password TINYINT UNSIGNED NOT NULL DEFAULT 0      COMMENT '1=下次登录必须改密',
    last_login_at        DATETIME         NULL                    COMMENT '最近成功登录时间',
    last_login_ip_hash   CHAR(64)         NULL                    COMMENT '最近登录 IP 的 HMAC-SHA256',
    password_changed_at  DATETIME         NULL                    COMMENT '密码最后修改时间',
    created_at           DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at           DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (id),
    UNIQUE KEY uk_username (username),
    UNIQUE KEY uk_email (email),
    KEY idx_role_status (role_id, status),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='账号表';

-- ---------------------------------------------------------------------
-- 2. 内容主体（10 张）
-- ---------------------------------------------------------------------

CREATE TABLE team_profile (
    id             TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '单例主键，固定为 1',
    name           VARCHAR(80)      NOT NULL           COMMENT '团队名称',
    english_name   VARCHAR(80)      NULL               COMMENT '英文名',
    slogan         VARCHAR(120)     NULL               COMMENT '口号',
    short_intro    VARCHAR(500)     NULL               COMMENT '首页一句话介绍',
    story          TEXT             NULL               COMMENT '团队故事，300-800 字',
    founded_on     DATE             NULL               COMMENT '成立日期',
    location       VARCHAR(100)     NULL               COMMENT '所在地',
    email          VARCHAR(120)     NULL               COMMENT '团队联系邮箱',
    phone          VARCHAR(30)      NULL               COMMENT '联系电话，默认不公开',
    logo_media_id  BIGINT UNSIGNED  NULL               COMMENT 'Logo 媒体 ID',
    cover_media_id BIGINT UNSIGNED  NULL               COMMENT '封面媒体 ID',
    social_json    JSON             NULL               COMMENT 'GitHub/公众号/邮箱等链接',
    values_json    JSON             NULL               COMMENT '价值观卡片数组',
    status         TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1 正常、0 维护',
    updated_by     BIGINT UNSIGNED  NULL               COMMENT '最后修改人',
    created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (id),
    KEY idx_updated_by (updated_by),
    CONSTRAINT chk_team_profile_singleton CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='团队资料单例';

CREATE TABLE milestones (
    id          INT UNSIGNED     NOT NULL AUTO_INCREMENT COMMENT '主键',
    year        SMALLINT UNSIGNED NOT NULL               COMMENT '年份',
    title       VARCHAR(120)     NOT NULL                COMMENT '里程碑标题',
    description TEXT             NULL                    COMMENT '说明，建议 80 字以内',
    sort_order  INT              NOT NULL DEFAULT 0      COMMENT '同年排序，越小越靠前',
    is_visible  TINYINT UNSIGNED NOT NULL DEFAULT 1      COMMENT '是否公开',
    created_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (id),
    KEY idx_year_sort (year, sort_order),
    KEY idx_visible (is_visible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='团队发展时间线';

CREATE TABLE members (
    id                   BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT COMMENT '主键',
    user_id              BIGINT UNSIGNED  NULL                    COMMENT '绑定账号；一个账号最多一个档案',
    slug                 VARCHAR(60)      NOT NULL                COMMENT '公开链接标识，小写字母/数字/连字符',
    name                 VARCHAR(50)      NOT NULL                COMMENT '姓名或昵称',
    english_name         VARCHAR(60)      NULL                    COMMENT '英文名',
    role_title           VARCHAR(60)      NOT NULL                COMMENT '团队角色，如“前端开发”',
    tagline              VARCHAR(150)     NULL                    COMMENT '一句话介绍',
    bio                  TEXT             NULL                    COMMENT '个人简介，200-500 字',
    avatar_media_id      BIGINT UNSIGNED  NULL                    COMMENT '头像媒体 ID',
    cover_media_id       BIGINT UNSIGNED  NULL                    COMMENT '封面媒体 ID',
    email                VARCHAR(120)     NULL                    COMMENT '联系邮箱，默认不公开',
    github_url           VARCHAR(255)     NULL                    COMMENT 'GitHub 链接',
    website_url          VARCHAR(255)     NULL                    COMMENT '个人网站',
    location             VARCHAR(100)     NULL                    COMMENT '所在地',
    join_date            DATE             NULL                    COMMENT '加入团队日期',
    sort_order           INT              NOT NULL DEFAULT 0      COMMENT '展示排序',
    is_featured          TINYINT UNSIGNED NOT NULL DEFAULT 0      COMMENT '首页精选',
    is_visible           TINYINT UNSIGNED NOT NULL DEFAULT 1      COMMENT '是否公开',
    profile_completeness TINYINT UNSIGNED NOT NULL DEFAULT 0      COMMENT '0-100，后台计算',
    created_at           DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at           DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (id),
    UNIQUE KEY uk_slug (slug),
    UNIQUE KEY uk_user_id (user_id),
    KEY idx_visible_sort (is_visible, sort_order),
    KEY idx_featured (is_featured, is_visible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='公开成员档案';

CREATE TABLE skills (
    id         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    name       VARCHAR(50)       NOT NULL                COMMENT '技能名',
    category   VARCHAR(30)       NOT NULL DEFAULT 'tech' COMMENT 'tech/design/data/soft/tool 等',
    color      CHAR(7)           NULL                    COMMENT '前端标签色，如 #2A9D8F',
    sort_order INT               NOT NULL DEFAULT 0      COMMENT '排序',
    created_at DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (id),
    UNIQUE KEY uk_name (name),
    KEY idx_category (category, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='技能字典';

CREATE TABLE member_skills (
    member_id   BIGINT UNSIGNED  NOT NULL                COMMENT '成员 ID',
    skill_id    SMALLINT UNSIGNED NOT NULL               COMMENT '技能 ID',
    proficiency TINYINT UNSIGNED NOT NULL DEFAULT 3      COMMENT '熟练度 1-5',
    years       DECIMAL(3,1)     NULL                    COMMENT '使用年限，0-20',
    created_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (member_id, skill_id),
    KEY idx_skill (skill_id),
    CONSTRAINT chk_member_skill_proficiency CHECK (proficiency BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='成员技能关联';

CREATE TABLE projects (
    id             BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT COMMENT '主键',
    slug           VARCHAR(80)      NOT NULL                COMMENT '公开链接标识',
    title          VARCHAR(120)     NOT NULL                COMMENT '项目名称',
    summary        VARCHAR(300)     NULL                    COMMENT '列表摘要',
    description    LONGTEXT         NULL                    COMMENT '详情正文，受限 Markdown',
    cover_media_id BIGINT UNSIGNED  NULL                    COMMENT '封面媒体 ID',
    tech_stack     VARCHAR(255)     NULL                    COMMENT '技术栈，逗号分隔',
    status         ENUM('planning','ongoing','completed','archived') NOT NULL DEFAULT 'ongoing' COMMENT '项目状态',
    start_date     DATE             NULL                    COMMENT '开始日期',
    end_date       DATE             NULL                    COMMENT '结束日期',
    repo_url       VARCHAR(255)     NULL                    COMMENT '仓库地址',
    demo_url       VARCHAR(255)     NULL                    COMMENT '演示地址',
    is_featured    TINYINT UNSIGNED NOT NULL DEFAULT 0      COMMENT '首页精选',
    is_visible     TINYINT UNSIGNED NOT NULL DEFAULT 1      COMMENT '是否公开',
    view_count     INT UNSIGNED     NOT NULL DEFAULT 0      COMMENT '详情浏览量，冗余计数',
    sort_order     INT              NOT NULL DEFAULT 0      COMMENT '排序',
    created_by     BIGINT UNSIGNED  NOT NULL                COMMENT '创建人',
    created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (id),
    UNIQUE KEY uk_slug (slug),
    KEY idx_status_visible (status, is_visible),
    KEY idx_featured (is_featured, is_visible),
    KEY idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='项目作品';

CREATE TABLE project_members (
    project_id   BIGINT UNSIGNED NOT NULL                COMMENT '项目 ID',
    member_id    BIGINT UNSIGNED NOT NULL                COMMENT '成员 ID',
    role_name    VARCHAR(50)     NULL                    COMMENT '项目角色，如“后端”',
    contribution VARCHAR(255)    NULL                    COMMENT '贡献说明',
    sort_order   INT             NOT NULL DEFAULT 0      COMMENT '展示排序',
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (project_id, member_id),
    KEY idx_member (member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='项目成员关联';

CREATE TABLE announcements (
    id             BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT COMMENT '主键',
    title          VARCHAR(150)     NOT NULL                COMMENT '标题',
    slug           VARCHAR(100)     NOT NULL                COMMENT '公开链接标识',
    summary        VARCHAR(300)     NULL                    COMMENT '摘要',
    content        LONGTEXT         NOT NULL                COMMENT '正文，受限 Markdown',
    cover_media_id BIGINT UNSIGNED  NULL                    COMMENT '封面媒体 ID',
    category       VARCHAR(30)      NOT NULL DEFAULT 'news' COMMENT 'news/notice/activity 等',
    status         ENUM('draft','published','offline') NOT NULL DEFAULT 'draft' COMMENT '状态',
    is_pinned      TINYINT UNSIGNED NOT NULL DEFAULT 0      COMMENT '是否置顶，最多 2 条',
    published_at   DATETIME         NULL                    COMMENT '发布时间',
    expires_at     DATETIME         NULL                    COMMENT '过期时间；空表示长期',
    view_count     INT UNSIGNED     NOT NULL DEFAULT 0      COMMENT '浏览量',
    author_id      BIGINT UNSIGNED  NOT NULL                COMMENT '作者',
    created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (id),
    UNIQUE KEY uk_slug (slug),
    KEY idx_status_published (status, published_at),
    KEY idx_pinned (is_pinned, published_at),
    KEY idx_author (author_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='公告与团队动态';

CREATE TABLE messages (
    id             BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT COMMENT '主键',
    member_id      BIGINT UNSIGNED  NULL                    COMMENT '目标成员；SET NULL',
    author_name    VARCHAR(50)      NOT NULL                COMMENT '访客昵称，2-30 字',
    author_email   VARCHAR(120)     NULL                    COMMENT '邮箱，不公开',
    author_website VARCHAR(255)     NULL                    COMMENT '个人网站，可选',
    content        VARCHAR(1000)    NOT NULL                COMMENT '留言正文，10-500 字',
    status         ENUM('pending','approved','rejected','spam') NOT NULL DEFAULT 'pending' COMMENT '审核状态',
    ip_hash        CHAR(64)         NULL                    COMMENT 'IP 的 HMAC-SHA256',
    user_agent     VARCHAR(255)     NULL                    COMMENT '浏览器 UA，截断保存',
    admin_note     VARCHAR(255)     NULL                    COMMENT '审核备注/拒绝原因',
    created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '提交时间',
    updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (id),
    KEY idx_status_created (status, created_at),
    KEY idx_member_status (member_id, status),
    KEY idx_ip_created (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='访客留言';

CREATE TABLE message_replies (
    id           BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT COMMENT '主键',
    message_id   BIGINT UNSIGNED  NOT NULL                COMMENT '所属留言；CASCADE',
    user_id      BIGINT UNSIGNED  NULL                    COMMENT '回复人账号；SET NULL',
    replier_name VARCHAR(50)      NOT NULL                COMMENT '显示名快照',
    content      VARCHAR(1000)    NOT NULL                COMMENT '回复正文',
    is_admin     TINYINT UNSIGNED NOT NULL DEFAULT 0      COMMENT '1=官方回复',
    status       ENUM('visible','hidden') NOT NULL DEFAULT 'visible' COMMENT '是否公开',
    created_at   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (id),
    KEY idx_message_status (message_id, status),
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='留言回复';

-- ---------------------------------------------------------------------
-- 3. 资源（2 张）
-- ---------------------------------------------------------------------

CREATE TABLE media (
    id            BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT COMMENT '主键',
    disk_name     VARCHAR(120)     NOT NULL                COMMENT '随机磁盘文件名',
    original_name VARCHAR(255)     NOT NULL                COMMENT '原始文件名，仅展示',
    mime_type     VARCHAR(100)     NOT NULL                COMMENT '真实 MIME',
    extension     VARCHAR(10)      NOT NULL                COMMENT '规范化扩展名',
    size_bytes    INT UNSIGNED     NOT NULL                COMMENT '文件大小',
    width         SMALLINT UNSIGNED NULL                   COMMENT '图片宽',
    height        SMALLINT UNSIGNED NULL                   COMMENT '图片高',
    sha256        CHAR(64)         NOT NULL                COMMENT '文件哈希，用于去重',
    storage_path  VARCHAR(255)     NOT NULL                COMMENT '相对 code/ 的存储路径',
    visibility    ENUM('public','private') NOT NULL DEFAULT 'public' COMMENT '可见性',
    uploaded_by   BIGINT UNSIGNED  NULL                    COMMENT '上传人；SET NULL',
    created_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
    PRIMARY KEY (id),
    UNIQUE KEY uk_disk_name (disk_name),
    KEY idx_sha256 (sha256),
    KEY idx_mime (mime_type),
    KEY idx_uploaded_by (uploaded_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='上传文件元数据';

CREATE TABLE resources (
    id             BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT COMMENT '主键',
    title          VARCHAR(150)     NOT NULL                COMMENT '资源标题',
    category       ENUM('team','personal','design','doc','other') NOT NULL DEFAULT 'other' COMMENT '资源分类',
    description    VARCHAR(500)     NULL                    COMMENT '说明',
    media_id       BIGINT UNSIGNED  NOT NULL                COMMENT '文件媒体 ID；RESTRICT',
    download_count INT UNSIGNED     NOT NULL DEFAULT 0      COMMENT '下载次数',
    is_public      TINYINT UNSIGNED NOT NULL DEFAULT 1      COMMENT '是否公开',
    sort_order     INT              NOT NULL DEFAULT 0      COMMENT '排序',
    uploaded_by    BIGINT UNSIGNED  NOT NULL                COMMENT '上传人',
    created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (id),
    KEY idx_category_public (category, is_public),
    KEY idx_media (media_id),
    KEY idx_uploaded_by (uploaded_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='课程作业与文档下载资源';

-- ---------------------------------------------------------------------
-- 4. 站点（1 张）
-- ---------------------------------------------------------------------

CREATE TABLE site_settings (
    id            INT UNSIGNED     NOT NULL AUTO_INCREMENT COMMENT '主键',
    setting_key   VARCHAR(80)      NOT NULL                COMMENT '配置键，点号分隔',
    setting_value TEXT             NULL                    COMMENT '配置值',
    value_type    ENUM('string','int','bool','json') NOT NULL DEFAULT 'string' COMMENT '值类型',
    group_name    VARCHAR(30)      NOT NULL DEFAULT 'general' COMMENT '分组：general/seo/footer/contact',
    label         VARCHAR(100)     NULL                    COMMENT '后台显示名',
    is_public     TINYINT UNSIGNED NOT NULL DEFAULT 0      COMMENT '是否允许前台读取',
    updated_by    BIGINT UNSIGNED  NULL                    COMMENT '最后修改人',
    created_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (id),
    UNIQUE KEY uk_setting_key (setting_key),
    KEY idx_group (group_name),
    KEY idx_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='站点键值配置';

-- ---------------------------------------------------------------------
-- 5. 运维（4 张）
-- ---------------------------------------------------------------------

CREATE TABLE page_views (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    path         VARCHAR(255)    NOT NULL                COMMENT '请求路径',
    route_name   VARCHAR(80)     NULL                    COMMENT '路由名，便于聚合',
    target_type  VARCHAR(30)     NULL                    COMMENT 'member/project/announcement',
    target_id    BIGINT UNSIGNED NULL                    COMMENT '目标 ID',
    visitor_hash CHAR(64)        NOT NULL                COMMENT '访客哈希',
    session_hash CHAR(64)        NULL                    COMMENT '会话哈希',
    referrer     VARCHAR(255)    NULL                    COMMENT '来源页，截断',
    user_agent   VARCHAR(255)    NULL                    COMMENT 'UA，截断',
    viewed_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '访问时间',
    PRIMARY KEY (id),
    KEY idx_viewed (viewed_at),
    KEY idx_path_date (path, viewed_at),
    KEY idx_target (target_type, target_id),
    KEY idx_visitor (visitor_hash, viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='匿名访问统计';

CREATE TABLE login_logs (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    user_id            BIGINT UNSIGNED NULL                    COMMENT '账号；不存在时为空',
    username_attempted VARCHAR(50)     NOT NULL                COMMENT '尝试登录的账号',
    success            TINYINT UNSIGNED NOT NULL               COMMENT '1 成功、0 失败',
    failure_reason     VARCHAR(50)     NULL                    COMMENT 'not_found/wrong_password/locked',
    ip_hash            CHAR(64)        NOT NULL                COMMENT 'IP 哈希',
    user_agent         VARCHAR(255)    NULL                    COMMENT 'UA',
    created_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '时间',
    PRIMARY KEY (id),
    KEY idx_user_created (user_id, created_at),
    KEY idx_ip_created (ip_hash, created_at),
    KEY idx_success (success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='登录日志';

CREATE TABLE audit_logs (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    actor_id    BIGINT UNSIGNED NULL                    COMMENT '操作者；SET NULL',
    action      VARCHAR(50)     NOT NULL                COMMENT 'create/update/publish/archive/moderate',
    entity_type VARCHAR(50)     NOT NULL                COMMENT '实体类型',
    entity_id   BIGINT UNSIGNED NULL                    COMMENT '实体 ID',
    before_json JSON            NULL                    COMMENT '修改前快照',
    after_json  JSON            NULL                    COMMENT '修改后快照',
    ip_hash     CHAR(64)        NULL                    COMMENT 'IP 哈希',
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '时间',
    PRIMARY KEY (id),
    KEY idx_actor_created (actor_id, created_at),
    KEY idx_entity (entity_type, entity_id),
    KEY idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='后台操作审计';

CREATE TABLE schema_migrations (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    version      VARCHAR(30)  NOT NULL                COMMENT '版本号，如 20261028_0001',
    filename     VARCHAR(255) NOT NULL                COMMENT '迁移文件名',
    checksum     CHAR(64)     NOT NULL                COMMENT '文件 SHA-256，防止篡改',
    applied_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '执行时间',
    execution_ms INT UNSIGNED NULL                    COMMENT '执行耗时（毫秒）',
    PRIMARY KEY (id),
    UNIQUE KEY uk_version (version),
    KEY idx_applied (applied_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='数据库迁移记录';

-- ---------------------------------------------------------------------
-- 6. 外键约束（全部建表后统一添加，解决 users 与 media 的循环引用）
-- ---------------------------------------------------------------------

ALTER TABLE users
    ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT fk_users_avatar FOREIGN KEY (avatar_media_id) REFERENCES media (id)
        ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE role_permissions
    ADD CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions (id)
        ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE team_profile
    ADD CONSTRAINT fk_team_profile_updated_by FOREIGN KEY (updated_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT fk_team_profile_logo FOREIGN KEY (logo_media_id) REFERENCES media (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT fk_team_profile_cover FOREIGN KEY (cover_media_id) REFERENCES media (id)
        ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE members
    ADD CONSTRAINT fk_members_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT fk_members_avatar FOREIGN KEY (avatar_media_id) REFERENCES media (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT fk_members_cover FOREIGN KEY (cover_media_id) REFERENCES media (id)
        ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE member_skills
    ADD CONSTRAINT fk_member_skills_member FOREIGN KEY (member_id) REFERENCES members (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT fk_member_skills_skill FOREIGN KEY (skill_id) REFERENCES skills (id)
        ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE projects
    ADD CONSTRAINT fk_projects_cover FOREIGN KEY (cover_media_id) REFERENCES media (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT fk_projects_created_by FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE project_members
    ADD CONSTRAINT fk_project_members_project FOREIGN KEY (project_id) REFERENCES projects (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT fk_project_members_member FOREIGN KEY (member_id) REFERENCES members (id)
        ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE announcements
    ADD CONSTRAINT fk_announcements_cover FOREIGN KEY (cover_media_id) REFERENCES media (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT fk_announcements_author FOREIGN KEY (author_id) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE messages
    ADD CONSTRAINT fk_messages_member FOREIGN KEY (member_id) REFERENCES members (id)
        ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE message_replies
    ADD CONSTRAINT fk_message_replies_message FOREIGN KEY (message_id) REFERENCES messages (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT fk_message_replies_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE media
    ADD CONSTRAINT fk_media_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE resources
    ADD CONSTRAINT fk_resources_media FOREIGN KEY (media_id) REFERENCES media (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT fk_resources_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE site_settings
    ADD CONSTRAINT fk_site_settings_updated_by FOREIGN KEY (updated_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE login_logs
    ADD CONSTRAINT fk_login_logs_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE audit_logs
    ADD CONSTRAINT fk_audit_logs_actor FOREIGN KEY (actor_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- ---------------------------------------------------------------------
-- 7. 视图（与设计文档 4.5 对齐）
-- ---------------------------------------------------------------------

CREATE OR REPLACE VIEW v_public_members AS
SELECT m.id,
       m.slug,
       m.name,
       m.english_name,
       m.role_title,
       m.tagline,
       m.avatar_media_id,
       m.is_featured,
       m.sort_order,
       (SELECT COUNT(*) FROM member_skills ms WHERE ms.member_id = m.id) AS skill_count,
       (SELECT COUNT(*) FROM project_members pm WHERE pm.member_id = m.id) AS project_count
FROM members m
WHERE m.is_visible = 1;

CREATE OR REPLACE VIEW v_published_announcements AS
SELECT a.*
FROM announcements a
WHERE a.status = 'published'
  AND (a.expires_at IS NULL OR a.expires_at > NOW());

CREATE OR REPLACE VIEW v_dashboard_daily_views AS
SELECT DATE(pv.viewed_at) AS view_date,
       COUNT(*) AS views,
       COUNT(DISTINCT pv.visitor_hash) AS visitors
FROM page_views pv
GROUP BY DATE(pv.viewed_at);

-- ---------------------------------------------------------------------
-- 8. 种子数据
-- ---------------------------------------------------------------------

-- 8.1 角色
INSERT INTO roles (id, code, name, description, is_system) VALUES
(1, 'super_admin', '超级管理员', '拥有全部权限，管理账号、设置与审计', 1),
(2, 'editor', '编辑', '负责内容发布、资源管理与留言审核', 1),
(3, 'member', '成员', '维护本人资料与账号安全', 1);

-- 8.2 权限码
INSERT INTO permissions (id, code, name, module, description) VALUES
(1,  'dashboard.view',   '查看数据看板', 'dashboard', '访问后台首页与统计 JSON'),
(2,  'team.edit',        '编辑团队资料', 'team',      '修改团队名称、故事、价值观'),
(3,  'member.manage',    '管理成员',     'member',    '编辑、归档成员档案'),
(4,  'content.manage',   '管理内容',     'content',   '公告、项目的增删改查'),
(5,  'content.publish',  '发布内容',     'content',   '公告与项目的发布、下线'),
(6,  'message.moderate', '审核留言',     'message',   '通过、拒绝、标记垃圾留言并回复'),
(7,  'resource.manage',  '管理资源',     'resource',  '上传、编辑、公开下载资源'),
(8,  'user.manage',      '管理账号',     'user',      '创建账号、重置密码、禁用账号'),
(9,  'setting.manage',   '管理站点设置', 'setting',   '修改 SEO、页脚与联系信息'),
(10, 'log.view',         '查看日志',     'log',       '查看登录日志与操作审计'),
(11, 'member.edit',      '编辑本人资料', 'me',        '修改本人公开资料、头像与技能'),
(12, 'media.upload',     '上传媒体',     'media',     '上传头像、封面与资源文件');

-- 8.3 角色权限
INSERT INTO role_permissions (role_id, permission_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10), (1, 11), (1, 12),
(2, 1), (2, 4), (2, 5), (2, 6), (2, 7), (2, 11), (2, 12),
(3, 11), (3, 12);

-- 8.4 账号
-- 密码散列对应：admin=Admin@123456，其余账号=Member@123456
INSERT INTO users
(id, role_id, username, email, password_hash, display_name, status, must_change_password, last_login_at, last_login_ip_hash, password_changed_at) VALUES
(1, 1, 'admin',       'admin@example.com', '$2b$10$ZbRqUKRVLJVuk74rdCrT.u5RH4M5WZ0MlMHoKfrfwBqzDgrp8/6zK', '管理员',   1, 1, '2026-11-05 09:12:00', SHA2('seed-ip-1', 256), '2026-10-29 10:00:00'),
(2, 2, 'editor',      'editor@example.com', '$2b$10$0Hk2GEp1Agw2yiiPCDc7e.o51zyl7d5QmFBjOwdiefoUGo/ql6EXa', '内容编辑', 1, 0, '2026-11-02 10:22:00', SHA2('seed-ip-2', 256), '2026-10-29 10:00:00'),
(3, 3, 'lin-zhiyuan', 'lin@example.com',    '$2b$10$0Hk2GEp1Agw2yiiPCDc7e.o51zyl7d5QmFBjOwdiefoUGo/ql6EXa', '林知远',   1, 0, '2026-11-04 21:40:00', SHA2('seed-ip-1', 256), '2026-10-29 10:00:00'),
(4, 3, 'su-wanqing',  'su@example.com',     '$2b$10$0Hk2GEp1Agw2yiiPCDc7e.o51zyl7d5QmFBjOwdiefoUGo/ql6EXa', '苏晚晴',   1, 0, '2026-11-03 18:05:00', SHA2('seed-ip-3', 256), '2026-10-29 10:00:00'),
(5, 3, 'chen-yiming', 'chen@example.com',   '$2b$10$0Hk2GEp1Agw2yiiPCDc7e.o51zyl7d5QmFBjOwdiefoUGo/ql6EXa', '陈一鸣',   1, 0, '2026-11-03 16:20:00', SHA2('seed-ip-3', 256), '2026-10-29 10:00:00'),
(6, 3, 'zhou-yuan',   'zhou@example.com',   '$2b$10$0Hk2GEp1Agw2yiiPCDc7e.o51zyl7d5QmFBjOwdiefoUGo/ql6EXa', '周予安',   1, 0, '2026-11-04 18:30:00', SHA2('seed-ip-4', 256), '2026-10-29 10:00:00');

-- 8.5 团队资料
INSERT INTO team_profile
(id, name, english_name, slogan, short_intro, story, founded_on, location, email, phone, social_json, values_json, status, updated_by) VALUES
(1,
 '数聚 TeamHub',
 'DataNova',
 '把团队的每一次协作，变成看得见的作品。',
 '数聚团队主页 · 项目、成员与课程作业的一站式展示空间。',
 '我们来自计算机学院的不同方向，因为一门数据库课程走到一起。有人负责数据建模，有人写后端接口，有人把信息整理成清晰的界面。我们相信，好的团队项目不是功能的堆叠，而是让每个人都能被看见、被记录。这个主页既是课程作业，也是我们协作方式的说明书。',
 '2026-09-01',
 '天津',
 'team@example.com',
 '',
 '{"github":"https://github.com/mon3trteam","email":"team@example.com"}',
 '[{"title":"共同目标","description":"先把主流程做通，再谈锦上添花。"},{"title":"透明协作","description":"接口、分工与进度都写在文档里，不靠口头同步。"},{"title":"可被验证","description":"每个功能都能在页面上找到证据。"},{"title":"留有余地","description":"给后来的人留下能读懂的代码与注释。"}]',
 1,
 1);

-- 8.6 里程碑
INSERT INTO milestones (id, year, title, description, sort_order, is_visible) VALUES
(1, 2026, '组队与选题',       '确定团队主页方向，完成需求分析与角色分工。', 1, 1),
(2, 2026, '需求与设计定稿',   '完成 32 项功能清单、21 张表的数据字典与页面线框。', 2, 1),
(3, 2026, '前后台开发',       '完成公共门户、成员中心与管理后台的界面与联调。', 3, 1),
(4, 2026, '演示与交付',       '整理文档、PPT 与部署说明，完成课程演示。', 4, 1);

-- 8.7 成员
INSERT INTO members
(id, user_id, slug, name, english_name, role_title, tagline, bio, email, github_url, website_url, location, join_date, sort_order, is_featured, is_visible, profile_completeness) VALUES
(1, 3, 'lin-zhiyuan', '林知远', 'Lin Zhiyuan', '前端开发 / 数据库设计',
 '用数据与界面讲好团队故事。',
 '负责团队主页的前台与后台界面，关注信息架构、交互细节与可访问性。喜欢把复杂的数据整理成一眼能看懂的页面。',
 'lin@example.com', 'https://github.com/example', '', '天津', '2026-09-01', 1, 1, 1, 90),
(2, 4, 'su-wanqing', '苏晚晴', 'Su Wanqing', '后端开发 / 接口设计',
 '让每一条请求都有清晰的去向。',
 '负责认证、会话与后台服务，关注接口边界、事务一致性与安全默认值。',
 'su@example.com', 'https://github.com/example', '', '天津', '2026-09-01', 2, 1, 1, 88),
(3, 5, 'chen-yiming', '陈一鸣', 'Chen Yiming', '数据建模 / SQL',
 '先想清楚关系，再写第一条 SQL。',
 '负责数据库设计、install.sql 与查询优化，喜欢用 EXPLAIN 找问题。',
 'chen@example.com', 'https://github.com/example', '', '天津', '2026-09-01', 3, 1, 1, 86),
(4, 6, 'zhou-yuan', '周予安', 'Zhou Yuan', '内容运营 / 视觉',
 '把团队的故事讲得清楚、好看。',
 '负责团队介绍文案、视觉素材与内容发布，关注品牌一致性与阅读体验。',
 'zhou@example.com', '', 'https://example.com', '天津', '2026-09-15', 4, 1, 1, 84);

-- 8.8 技能字典
INSERT INTO skills (id, name, category, color, sort_order) VALUES
(1, 'PHP',         'tech',   '#4F5D75', 1),
(2, 'JavaScript',  'tech',   '#F2CC8F', 2),
(3, 'MySQL',       'data',   '#2A9D8F', 3),
(4, 'Docker',      'tech',   '#3D5A80', 4),
(5, 'UI Design',   'design', '#E07A5F', 5),
(6, '写作',        'soft',   '#81B29A', 6),
(7, 'Git',         'tool',   '#F4A261', 7);

-- 8.9 成员技能
INSERT INTO member_skills (member_id, skill_id, proficiency, years) VALUES
(1, 2, 5, 2.0), (1, 1, 4, 1.5), (1, 3, 4, 1.5), (1, 5, 4, 1.0),
(2, 1, 5, 2.0), (2, 3, 5, 2.0), (2, 4, 3, 0.5),
(3, 3, 5, 2.0), (3, 1, 3, 1.0), (3, 7, 4, 1.5),
(4, 5, 5, 2.0), (4, 6, 5, 2.0), (4, 7, 3, 1.0);

-- 8.10 项目
INSERT INTO projects
(id, slug, title, summary, description, tech_stack, status, start_date, end_date, repo_url, demo_url, is_featured, is_visible, view_count, sort_order, created_by) VALUES
(1, 'teamhub', '数聚 TeamHub 团队主页',
 '团队展示、成员主页、公告与作业下载一体化的课程设计项目。',
 '数聚 TeamHub 是本课程的团队主页项目，目标是做出一套可演示、可还原、可讲清楚的 PHP + MySQL 应用。\n\n前端包含公共门户、成员中心与管理后台三套布局；后端按 MVC 分层，数据库设计 21 张表。',
 'PHP, MySQL, Bootstrap, ECharts', 'ongoing', '2026-10-28', NULL,
 'https://github.com/mon3trteam/nku2026DBdev', '', 1, 1, 128, 1, 1),
(2, 'book-market', '校园二手书交易平台',
 '面向校园的二手书发布、检索与留言系统，练习 CRUD 与权限控制。',
 '一个面向校园的二手书交易练习项目：用户可以发布书籍、按关键词检索，并在详情页留言。\n\n项目重点练习表单校验、权限边界与列表分页。',
 'PHP, MySQL, JavaScript', 'completed', '2026-09-10', '2026-10-20',
 '', '', 1, 1, 76, 2, 1),
(3, 'download-center', '课程作业下载中心',
 '按团队文档与个人作业分类的下载中心，包含权限校验与下载计数。',
 '下载中心读取 data/team 与 data/personal 目录，按团队文档与个人作业分类展示。\n\n公开资源直接下载并计数，私有资源需要登录；下载路径经过控制器校验，不暴露磁盘路径。',
 'PHP, MySQL, Bootstrap', 'planning', '2026-11-01', NULL,
 '', '', 1, 1, 32, 3, 1);

-- 8.11 项目成员
INSERT INTO project_members (project_id, member_id, role_name, contribution, sort_order) VALUES
(1, 1, '前端 / 数据库设计', '公共门户与成员中心界面', 1),
(1, 2, '后端 / 接口',       '认证、资料保存与下载接口', 2),
(2, 3, '数据建模',           '表结构与查询优化', 1),
(2, 2, '后端',               '留言与权限控制', 2),
(3, 4, '内容 / 视觉',       '资源分类与页面文案', 1);

-- 8.12 公告
INSERT INTO announcements
(id, title, slug, summary, content, category, status, is_pinned, published_at, expires_at, view_count, author_id) VALUES
(1, '团队主页初版界面开放预览', 'site-preview',
 '首页、团队介绍、成员列表与成员主页已经完成第一版，欢迎提出意见。',
 '团队主页的第一版界面已经完成，包含首页、团队介绍、成员列表与成员主页。\n\n当前使用演示数据，后端接口接入后会替换为真实内容。欢迎在留言板留下你的建议。',
 'news', 'published', 1, '2026-11-05 10:00:00', NULL, 156, 6),
(2, '需求与设计文档完成评审', 'design-review',
 '32 项功能、21 张表与页面线框已定稿，进入开发阶段。',
 '需求文档与设计文档已完成内部评审。\n\n下一步按 P0 优先级推进：公共门户、成员中心与管理后台。',
 'notice', 'published', 0, '2026-10-28 18:30:00', NULL, 98, 3),
(3, '课程作业下载中心即将上线', 'download-center',
 '团队文档与个人作业将按分类提供下载，并记录下载次数。',
 '下载中心将读取 data/team 与 data/personal 目录，按团队文档与个人作业分类展示。\n\n资源管理页面完成后即可在后台上传与公开。',
 'activity', 'published', 0, '2026-10-30 14:20:00', NULL, 64, 5);

-- 8.13 留言
INSERT INTO messages
(id, member_id, author_name, author_email, author_website, content, status, ip_hash, user_agent, admin_note, created_at) VALUES
(1, 1, '一位访客', NULL, NULL,
 '首页的结构很清楚，成员主页的技能条也很好读，期待看到项目详情页。',
 'approved', SHA2('seed-msg-ip-1', 256), 'Edge / Windows 11', NULL, '2026-11-02 20:15:00'),
(2, 4, '同班同学', NULL, NULL,
 '请问下载中心会放团队文档的 PDF 吗？想参考一下设计文档的结构。',
 'approved', SHA2('seed-msg-ip-2', 256), 'Chrome / Windows 11', NULL, '2026-11-03 09:40:00'),
(3, 2, '访客C', NULL, NULL,
 '成员主页的项目卡片排版很清晰，建议在列表页也加上项目入口。',
 'approved', SHA2('seed-msg-ip-3', 256), 'Firefox / Windows 11', NULL, '2026-11-04 21:05:00'),
(4, NULL, '学弟', NULL, NULL,
 '想了解团队招新和课程项目的方向，有没有公开的介绍材料？',
 'approved', SHA2('seed-msg-ip-4', 256), 'Safari / macOS', NULL, '2026-11-04 18:30:00'),
(5, 3, '合作方', NULL, NULL,
 '项目状态和参与成员的展示很直观，方便快速判断团队分工。',
 'approved', SHA2('seed-msg-ip-5', 256), 'Edge / Windows 11', NULL, '2026-11-03 16:20:00'),
(6, NULL, '访客D', NULL, NULL,
 '留言板会支持管理员回复吗？希望能看到官方的补充说明。',
 'approved', SHA2('seed-msg-ip-6', 256), 'Chrome / Android', NULL, '2026-11-02 11:05:00'),
(7, 1, '路过的同学', NULL, NULL,
 '移动端的导航抽屉很好用，360 宽度下也没有横向滚动。',
 'approved', SHA2('seed-msg-ip-7', 256), 'Edge / Windows 11', NULL, '2026-11-01 10:12:00');

-- 8.14 留言回复
INSERT INTO message_replies (id, message_id, user_id, replier_name, content, is_admin, status, created_at) VALUES
(1, 1, 1, '管理员', '谢谢关注，项目详情页已经补上了。', 1, 'visible', '2026-11-03 09:10:00'),
(2, 2, 1, '管理员', '会放需求、设计、部署三份文档，资源页正在做。', 1, 'visible', '2026-11-03 12:20:00'),
(3, 4, 1, '管理员', '团队介绍页有方向和分工，后续会补招新说明。', 1, 'visible', '2026-11-04 19:00:00'),
(4, 6, 1, '管理员', '支持，回复会显示在留言下方。', 1, 'visible', '2026-11-02 13:40:00');

-- 8.15 媒体（指向课程要求的 data/team 与 data/personal 目录）
-- 说明：以下记录对应预期交付文件；文件未上传前下载会返回“文件不存在”，
--       请把真实 PDF/ZIP 放入 code/data/team、code/data/personal 后再演示。
INSERT INTO media
(id, disk_name, original_name, mime_type, extension, size_bytes, width, height, sha256, storage_path, visibility, uploaded_by, created_at) VALUES
(1, 'seed-team-requirement.pdf', 'M3_需求文档.pdf',       'application/pdf', 'pdf', 1887436, NULL, NULL, SHA2('seed-media-1', 256), 'data/team/M3_需求文档.pdf',            'public', 1, '2026-10-28 10:00:00'),
(2, 'seed-team-design.pdf',      'M3_设计文档.pdf',       'application/pdf', 'pdf', 3355443, NULL, NULL, SHA2('seed-media-2', 256), 'data/team/M3_设计文档.pdf',            'public', 1, '2026-10-28 10:00:00'),
(3, 'seed-team-deploy.pdf',      'M3_部署文档.pdf',       'application/pdf', 'pdf', 1003520, NULL, NULL, SHA2('seed-media-3', 256), 'data/team/M3_部署文档.pdf',            'public', 1, '2026-11-02 10:00:00'),
(4, 'seed-personal-hw1.zip',     '作业1(学号_姓名).zip',   'application/zip', 'zip', 4823449, NULL, NULL, SHA2('seed-media-4', 256), 'data/personal/作业1(学号_姓名).zip',   'public', 1, '2026-10-20 10:00:00'),
(5, 'seed-personal-hw2.zip',     '作业2(学号_姓名).zip',   'application/zip', 'zip', 3250585, NULL, NULL, SHA2('seed-media-5', 256), 'data/personal/作业2(学号_姓名).zip',   'public', 1, '2026-10-27 10:00:00'),
(6, 'seed-personal-hw3.zip',     '作业3(学号_姓名).zip',   'application/zip', 'zip', 5662310, NULL, NULL, SHA2('seed-media-6', 256), 'data/personal/作业3(学号_姓名).zip',   'public', 1, '2026-11-01 10:00:00'),
(7, 'seed-team-er.pdf',          'M3_ER图.pdf',           'application/pdf', 'pdf', 634880,  NULL, NULL, SHA2('seed-media-7', 256), 'data/team/M3_ER图.pdf',                'public', 1, '2026-10-28 10:00:00'),
(8, 'seed-team-dictionary.pdf',  'M3_数据字典.pdf',       'application/pdf', 'pdf', 1153433, NULL, NULL, SHA2('seed-media-8', 256), 'data/team/M3_数据字典.pdf',            'public', 1, '2026-10-28 10:00:00'),
(9, 'seed-team-demo.pdf',        'M3_演示说明.pdf',       'application/pdf', 'pdf', 327680,  NULL, NULL, SHA2('seed-media-9', 256), 'data/team/M3_演示说明.pdf',            'public', 1, '2026-11-03 10:00:00');

-- 8.16 下载资源
INSERT INTO resources
(id, title, category, description, media_id, download_count, is_public, sort_order, uploaded_by, created_at, updated_at) VALUES
(1, '团队需求文档', 'team',     '功能需求、用例、业务规则与验收标准。', 1, 36, 1, 1, 1, '2026-10-28 10:00:00', '2026-10-28 10:00:00'),
(2, '团队设计文档', 'team',     '架构、21 张表的数据字典、接口与页面设计。', 2, 41, 1, 2, 1, '2026-10-28 10:00:00', '2026-10-28 10:00:00'),
(3, '部署文档',     'doc',      'Docker Compose 一键部署、备份与还原流程。', 3, 18, 1, 3, 1, '2026-11-02 10:00:00', '2026-11-02 10:00:00'),
(4, '个人作业 1（学号_姓名）', 'personal', 'Web 前端初探：抓包、jQuery 事件与浏览器插件。', 4, 12, 1, 4, 1, '2026-10-20 10:00:00', '2026-10-20 10:00:00'),
(5, '个人作业 2（学号_姓名）', 'personal', '页面设计实践与响应式布局。', 5, 9, 1, 5, 1, '2026-10-27 10:00:00', '2026-10-27 10:00:00'),
(6, '个人作业 3（学号_姓名）', 'personal', 'WordPress 建站实践与内容运营。', 6, 7, 1, 6, 1, '2026-11-01 10:00:00', '2026-11-01 10:00:00'),
(7, 'E-R 图',       'design',   '团队主页核心实体关系图。', 7, 15, 1, 7, 1, '2026-10-28 10:00:00', '2026-10-28 10:00:00'),
(8, '数据字典',     'design',   '21 张表的字段、类型、索引与约束。', 8, 22, 1, 8, 1, '2026-10-28 10:00:00', '2026-10-28 10:00:00'),
(9, '演示环境说明', 'other',    '本地 Docker 环境、初始账号与演示路径。', 9, 5, 1, 9, 1, '2026-11-03 10:00:00', '2026-11-03 10:00:00');

-- 8.17 站点设置
INSERT INTO site_settings (setting_key, setting_value, value_type, group_name, label, is_public, updated_by) VALUES
('site.name',          '数聚 TeamHub', 'string', 'general', '站点名称', 1, 1),
('site.english_name',  'DataNova',     'string', 'general', '英文名', 1, 1),
('general.maintenance','0',            'bool',   'general', '维护模式', 0, 1),
('general.values',     '[{"title":"共同目标","description":"先把主流程做通"},{"title":"透明协作","description":"接口、分工与进度都写在文档里"}]', 'json', 'general', '价值观（JSON）', 1, 1),
('site.description',   '数聚 TeamHub：团队展示、成员主页、公告与课程作业下载。', 'string', 'seo', 'SEO 描述', 1, 1),
('site.keywords',      '南开大学, 数据库, 团队主页, 课程设计', 'string', 'seo', 'SEO 关键词', 1, 1),
('footer.copyright',   '© 2026 数聚 TeamHub · 课程设计演示', 'string', 'footer', '版权文案', 1, 1),
('footer.icp',         '',             'string', 'footer', '备案信息', 1, 1),
('contact.email',      'team@example.com', 'string', 'contact', '联系邮箱', 1, 1),
('contact.location',   '天津',         'string', 'contact', '所在地', 1, 1);

-- 8.18 访问统计（演示数据；接入真实统计后可删除本段）
INSERT INTO page_views (path, route_name, visitor_hash, viewed_at)
SELECT CASE MOD(n, 3)
           WHEN 0 THEN '/'
           WHEN 1 THEN '/members'
           ELSE '/announcements'
       END,
       CASE MOD(n, 3)
           WHEN 0 THEN 'home/index'
           WHEN 1 THEN 'member/index'
           ELSE 'announcement/index'
       END,
       SHA2(CONCAT('seed-visitor-', n), 256),
       DATE_SUB(CURDATE(), INTERVAL MOD(n, 14) DAY) + INTERVAL (8 + MOD(n, 10)) HOUR
FROM (
    SELECT a.n + b.n * 10 AS n
    FROM (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
          UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a
    CROSS JOIN (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) b
) nums
WHERE n < 70;

-- 8.19 登录日志
INSERT INTO login_logs (user_id, username_attempted, success, failure_reason, ip_hash, user_agent, created_at) VALUES
(1, 'admin',       1, NULL,             SHA2('seed-ip-1', 256), 'Edge / Windows 11',    '2026-11-05 09:12:00'),
(3, 'lin-zhiyuan', 1, NULL,             SHA2('seed-ip-1', 256), 'Edge / Windows 11',    '2026-11-04 21:40:00'),
(3, 'lin-zhiyuan', 0, 'wrong_password', SHA2('seed-ip-1', 256), 'Edge / Windows 11',    '2026-11-04 21:39:00'),
(4, 'su-wanqing',  1, NULL,             SHA2('seed-ip-3', 256), 'Firefox / Windows 11', '2026-11-03 18:05:00'),
(NULL, 'unknown',  0, 'not_found',      SHA2('seed-ip-3', 256), 'Firefox / Windows 11', '2026-11-02 10:22:00');

-- 8.20 操作审计
INSERT INTO audit_logs (actor_id, action, entity_type, entity_id, before_json, after_json, ip_hash, created_at) VALUES
(1, 'publish', 'announcement', 1, '{"status":"draft"}',     '{"status":"published"}', SHA2('seed-ip-1', 256), '2026-11-05 10:20:00'),
(2, 'update',  'member',       2, NULL,                     '{"skills":["PHP","MySQL"]}', SHA2('seed-ip-2', 256), '2026-11-05 09:45:00'),
(1, 'moderate','message',      3, '{"status":"pending"}',   '{"status":"approved"}',  SHA2('seed-ip-1', 256), '2026-11-04 20:10:00'),
(2, 'create',  'announcement', 4, NULL,                     '{"status":"draft"}',     SHA2('seed-ip-2', 256), '2026-11-04 16:32:00'),
(1, 'update',  'site_settings', NULL, '{"seo.description":"旧描述"}', '{"seo.description":"数聚 TeamHub：团队展示、成员主页、公告与课程作业下载。"}', SHA2('seed-ip-1', 256), '2026-11-03 11:08:00');

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 安装完成。验证建议：
--   SELECT COUNT(*) FROM information_schema.tables
--    WHERE table_schema = 'team_home' AND table_type = 'BASE TABLE';  -- 应为 21
--   SELECT COUNT(*) FROM information_schema.views
--    WHERE table_schema = 'team_home';                                -- 应为 3
--   SELECT * FROM v_public_members;
--   SELECT * FROM v_published_announcements;
-- =====================================================================

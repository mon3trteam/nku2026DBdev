-- =====================================================================
-- 文件名：install.sql
-- 功能：表结构 + 模板初始数据（21 张表 + 3 个视图）。
--       本文件是纯模板初始态：除角色/权限/账号/成员占位档案/功能必需配置外不含任何内容数据，
--       真实站点内容全部由使用者通过后台创建，并经「数据包」迁移（FR-DB-04），不回填本文件。
-- 作者：金暄琛（2411672）
-- 创建日期：2026-11-08
-- 所属模块：数据库
--
-- 说明：
--   1) 本文件面向“空库一次安装”；会 DROP 同名视图与表，执行前请备份。
--   2) 表结构、字段、索引与视图以 docs/team/02-设计文档 的数据字典为准；
--      修改后必须在干净库上验证可完整还原（mysql < install.sql）。
--   3) 作业硬性要求：数据库表数量 > 10 张；本文件共 21 张表。
--   4) 初始账号（bcrypt 散列，严禁写入真实生产密码；全部账号
--      must_change_password=1，首次登录强制改密，防止仓库公开口令被滥用）：
--        admin    / Admin@123456   （超级管理员）
--        editor   / Member@123456  （编辑）
--        member1~4 / Member@123456 （成员账号 ×4，绑定四个成员档案）
--   5) 除角色/权限/账号/成员占位档案/技能字典/站点配置外不含任何内容数据；
--      真实内容全部由使用者通过后台创建，经「数据包」迁移。
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
-- 8. 模板初始数据
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
(12, 'media.upload',     '上传媒体',     'media',     '上传头像、封面与资源文件'),
(13, 'data.manage',      '数据包管理',   'data',      '导出/导入全站数据包（整库覆盖，仅超级管理员）');

-- 8.3 角色权限
INSERT INTO role_permissions (role_id, permission_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10), (1, 11), (1, 12), (1, 13),
(2, 1), (2, 2), (2, 3), (2, 4), (2, 5), (2, 6), (2, 7), (2, 11), (2, 12),
(3, 11), (3, 12);

-- 8.4 账号（模板初始账号；密码散列对应：admin=Admin@123456，其余=Member@123456）
INSERT INTO users
(id, role_id, username, email, password_hash, display_name, status, must_change_password, last_login_at, last_login_ip_hash, password_changed_at) VALUES
(1, 1, 'admin',       'admin@example.com', '$2b$10$ZbRqUKRVLJVuk74rdCrT.u5RH4M5WZ0MlMHoKfrfwBqzDgrp8/6zK', '管理员',   1, 1, NULL, NULL, NULL),
(2, 2, 'editor',      'editor@example.com', '$2b$10$0Hk2GEp1Agw2yiiPCDc7e.o51zyl7d5QmFBjOwdiefoUGo/ql6EXa', '内容编辑', 1, 1, NULL, NULL, NULL),
(3, 3, 'member1', 'member1@example.com', '$2b$10$0Hk2GEp1Agw2yiiPCDc7e.o51zyl7d5QmFBjOwdiefoUGo/ql6EXa', '成员一', 1, 1, NULL, NULL, NULL),
(4, 3, 'member2', 'member2@example.com', '$2b$10$0Hk2GEp1Agw2yiiPCDc7e.o51zyl7d5QmFBjOwdiefoUGo/ql6EXa', '成员二', 1, 1, NULL, NULL, NULL),
(5, 3, 'member3', 'member3@example.com', '$2b$10$0Hk2GEp1Agw2yiiPCDc7e.o51zyl7d5QmFBjOwdiefoUGo/ql6EXa', '成员三', 1, 1, NULL, NULL, NULL),
(6, 3, 'member4', 'member4@example.com', '$2b$10$0Hk2GEp1Agw2yiiPCDc7e.o51zyl7d5QmFBjOwdiefoUGo/ql6EXa', '成员四', 1, 1, NULL, NULL, NULL);

-- 8.5 团队资料
INSERT INTO team_profile
(id, name, english_name, slogan, short_intro, story, founded_on, location, email, phone, social_json, values_json, status, updated_by) VALUES
(1,
 'TeamHub',
 'TeamHub',
 '',
 '',
 '',
 NULL,
 '',
 '',
 '',
 '[]',
 '[]',
 1,
 1);

-- 8.6 成员（模板占位档案：与 member1~4 账号一一绑定，is_visible=0，
--             使用者在成员中心完成资料实例化并开启可见后再公开展示）
INSERT INTO members
(id, user_id, slug, name, english_name, role_title, tagline, bio, email, github_url, website_url, location, join_date, sort_order, is_featured, is_visible, profile_completeness) VALUES
(1, 3, 'member-1', '成员一', '', '', '', '', '', '', '', '', NULL, 1, 0, 0, 0),
(2, 4, 'member-2', '成员二', '', '', '', '', '', '', '', '', NULL, 2, 0, 0, 0),
(3, 5, 'member-3', '成员三', '', '', '', '', '', '', '', '', NULL, 3, 0, 0, 0),
(4, 6, 'member-4', '成员四', '', '', '', '', '', '', '', '', NULL, 4, 0, 0, 0);

-- 8.7 技能字典（功能必需参照数据，供成员中心技能选择；可由管理员按需扩展）
INSERT INTO skills (id, name, category, color, sort_order) VALUES
(1, 'PHP',         'tech',   '#4F5D75', 1),
(2, 'JavaScript',  'tech',   '#F2CC8F', 2),
(3, 'MySQL',       'data',   '#2A9D8F', 3),
(4, 'Docker',      'tech',   '#3D5A80', 4),
(5, 'UI Design',   'design', '#E07A5F', 5),
(6, '写作',        'soft',   '#81B29A', 6),
(7, 'Git',         'tool',   '#F4A261', 7);

-- 8.8 站点设置（功能必需配置；内容字段为中性模板值）
INSERT INTO site_settings (setting_key, setting_value, value_type, group_name, label, is_public, updated_by) VALUES
('site.name',          'TeamHub',      'string', 'general', '站点名称', 1, 1),
('site.english_name',  'TeamHub',      'string', 'general', '英文名', 1, 1),
('general.maintenance','0',            'bool',   'general', '维护模式', 0, 1),
('general.values',     '[]',           'json',   'general', '价值观（JSON）', 1, 1),
('site.description',   'TeamHub：团队展示、成员主页、公告与课程作业下载。', 'string', 'seo', 'SEO 描述', 1, 1),
('site.keywords',      '团队主页, 数据库, 课程设计', 'string', 'seo', 'SEO 关键词', 1, 1),
('footer.copyright',   '',             'string', 'footer', '版权文案（留空走默认）', 1, 1),
('footer.icp',         '',             'string', 'footer', '备案信息', 1, 1),
('contact.email',      '',             'string', 'contact', '联系邮箱', 1, 1),
('contact.location',   '',             'string', 'contact', '所在地', 1, 1);

SET FOREIGN_KEY_CHECKS = 1;

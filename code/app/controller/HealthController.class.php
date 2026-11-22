<?php
/**
 * 文件名：HealthController.class.php
 * 功能：后台「数据健康」页（FR-DB-05）——数据库状态、数据规模、运行环境与最近活动
 * 作者：钱泊瑜 2411282
 * 创建日期：2026-11-22
 * 所属模块：管理后台 / 数据健康
 *
 * 说明：整页只读，不写库、不落审计日志；数据全部来自 HealthModel，控制器不拼 SQL。
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/HealthModel.class.php';

final class HealthController
{
    /**
     * 页面含库表规模与登录日志，仅超管可访问：
     * 先要求登录，再要求 setting.manage（模板数据中只有 super_admin 拥有该权限）。
     */
    public function __construct()
    {
        Auth::requireLogin();
        Auth::requirePermission('setting.manage');
    }

    /**
     * 数据健康页：调用模型的各项只读查询，交给视图渲染四张信息卡。
     */
    public function index(): void
    {
        $tables = HealthModel::baseTables();

        render('admin/health', [
            'pageTitle' => '数据健康 · 管理后台',
            'adminNav' => 'health',
            // 数据库状态卡
            'dbVersion' => HealthModel::dbVersion(),
            'tableTotal' => count($tables),
            'viewCount' => HealthModel::viewCount(),
            // 数据规模卡
            'tableRows' => HealthModel::tableRowCounts(),
            // 运行环境卡
            'phpVersion' => HealthModel::phpVersion(),
            'serverSoftware' => (string) ($_SERVER['SERVER_SOFTWARE'] ?? '未知'),
            'diskFree' => HealthModel::diskFree(),
            'storageWritable' => HealthModel::storageWritable(),
            // 最近活动卡
            'recentLogins' => HealthModel::recentLogins(),
            'lastAuditTime' => HealthModel::lastAuditTime(),
        ]);
    }
}

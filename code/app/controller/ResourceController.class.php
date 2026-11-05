<?php
/**
 * 文件名：ResourceController.class.php
 * 功能：资源下载列表控制器；文件下载由后端媒体模块接入
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 资源
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/ResourceModel.class.php';

final class ResourceController
{
    public function index(): void
    {
        $categories = ResourceModel::categories();
        $category = (string) ($_GET['category'] ?? '');
        $category = array_key_exists($category, $categories) ? $category : '';
        $resources = ResourceModel::all($category);

        render('public/resource_list', [
            'pageTitle' => '资源下载 · 数聚 TeamHub',
            'metaDescription' => '下载数聚 TeamHub 的团队文档、个人作业、设计资料与部署资料。',
            'activeNav' => 'resource',
            'categories' => $categories,
            'activeCategory' => $category,
            'resources' => $resources,
            'grouped' => ResourceModel::grouped(),
            'total' => count($resources),
        ]);
    }
}

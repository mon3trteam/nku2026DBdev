<?php
/**
 * 文件名：member_card.php
 * 功能：成员卡片组件
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共组件
 */
?>
<a class="member-card" href="<?= e(url('member/show', ['slug' => $member['slug']])) ?>">
    <div class="member-card__avatar">
        <img src="<?= e(asset($member['avatar'])) ?>" alt="<?= e($member['name']) ?> 的头像" width="88" height="88" loading="lazy">
    </div>
    <div class="member-card__body">
        <h3 class="member-card__name"><?= e($member['name']) ?></h3>
        <p class="member-card__role"><?= e($member['role_title']) ?></p>
        <p class="member-card__tagline"><?= e(str_limit($member['tagline'], 34)) ?></p>
        <div class="tag-list">
            <?php foreach (array_slice($member['skills'], 0, 3) as $skill): ?>
                <span class="tag"><?= e($skill['name']) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</a>

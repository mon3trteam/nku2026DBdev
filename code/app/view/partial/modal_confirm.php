<?php
/**
 * 文件名：modal_confirm.php
 * 功能：通用确认对话框（危险操作二次确认）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共组件
 *
 * 用法：require 本文件，按钮加 data-bs-toggle="modal" data-bs-target="#{id}"。
 */

$modalId = $modalId ?? 'confirmModal';
$modalTitle = $modalTitle ?? '确认操作';
$modalBody = $modalBody ?? '该操作需要二次确认。';
$modalConfirmText = $modalConfirmText ?? '确认';
$modalDanger = $modalDanger ?? true;
$modalConfirmDisabled = $modalConfirmDisabled ?? true;
?>
<div class="modal fade" id="<?= e($modalId) ?>" tabindex="-1" aria-labelledby="<?= e($modalId) ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="<?= e($modalId) ?>Label"><?= e($modalTitle) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body"><?= e($modalBody) ?></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn <?= $modalDanger ? 'btn-danger' : 'btn-primary' ?>"
                        data-bs-dismiss="modal" <?= $modalConfirmDisabled ? 'disabled' : '' ?>>
                    <?= e($modalConfirmText) ?><?= $modalConfirmDisabled ? '（接口接入后启用）' : '' ?>
                </button>
            </div>
        </div>
    </div>
</div>

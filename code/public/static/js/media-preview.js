/*
 * 文件名：media-preview.js
 * 功能：头像/封面本地预览与类型、大小前置校验
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：成员中心
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
            input.addEventListener('change', function () {
                var target = document.getElementById(input.getAttribute('data-preview'));
                var file = input.files && input.files[0];
                if (!target || !file) {
                    return;
                }

                if (file.type.indexOf('image/') !== 0) {
                    if (window.showToast) {
                        window.showToast('请选择 JPG、PNG 或 WebP 图片。', 'danger');
                    }
                    input.value = '';
                    return;
                }

                var maxMb = parseFloat(input.getAttribute('data-max-mb') || '2');
                if (file.size > maxMb * 1024 * 1024) {
                    if (window.showToast) {
                        window.showToast('图片不能超过 ' + maxMb + ' MB。', 'danger');
                    }
                    input.value = '';
                    return;
                }

                target.src = URL.createObjectURL(file);
                if (window.showToast) {
                    window.showToast('已选择图片，保存后生效（演示）。', 'success');
                }
            });
        });
    });
})();

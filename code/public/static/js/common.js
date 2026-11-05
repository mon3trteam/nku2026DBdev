/*
 * 文件名：common.js
 * 功能：全站基础交互（密码显示切换、表单提交锁）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共脚本
 */

(function () {
    'use strict';

    /**
     * 显示一条 Toast。type: success | danger | warning | info
     */
    window.showToast = function (message, type) {
        var stack = document.getElementById('toast-stack');
        if (!stack) {
            return;
        }

        var toast = document.createElement('div');
        toast.className = 'app-toast app-toast--' + (type || 'info');
        toast.setAttribute('role', 'status');

        var text = document.createElement('span');
        text.textContent = message;

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'app-toast__close';
        close.setAttribute('aria-label', '关闭提示');
        close.textContent = '×';
        close.addEventListener('click', function () {
            toast.remove();
        });

        toast.appendChild(text);
        toast.appendChild(close);
        stack.appendChild(toast);

        window.setTimeout(function () {
            toast.remove();
        }, 3000);
    };

    document.addEventListener('DOMContentLoaded', function () {
        // 由服务端通过 body[data-toast] 触发的提示
        if (document.body.dataset.toast) {
            window.showToast(document.body.dataset.toast, document.body.dataset.toastType || 'info');
        }

        // 密码显示/隐藏
        document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
            button.addEventListener('click', function () {
                var selector = button.getAttribute('data-toggle-password');
                var input = document.querySelector(selector);
                if (!input) {
                    return;
                }

                var isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                button.textContent = isPassword ? '隐藏' : '显示';
                button.setAttribute('aria-label', isPassword ? '隐藏密码' : '显示密码');
            });
        });

        // 表单提交锁：防止重复提交；校验失败时浏览器不会触发 submit
        document.querySelectorAll('form[method="post"]').forEach(function (form) {
            if (form.hasAttribute('data-no-lock')) {
                return;
            }

            form.addEventListener('submit', function () {
                var button = form.querySelector('button[type="submit"]');
                if (!button) {
                    return;
                }

                button.disabled = true;
                button.dataset.originalText = button.textContent;
                button.textContent = '提交中…';
            });
        });
    });
})();

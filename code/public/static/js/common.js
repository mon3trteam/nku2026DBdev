/*
 * 文件名：common.js
 * 功能：全站基础交互（密码显示切换、表单提交锁）
 * 作者：杨斯源（2412796）
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

        // 密码可见切换（小眼睛）：为每个 .password-field 自动注入眼睛按钮，
        // 默认掩码，点击切换明文/掩码，图标在 眼睛/闭眼 之间切换。
        // 事件委托，绑定幂等；视图层零标记，全部由这里统一生成。
        var EYE_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
        var EYE_OFF_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3.5 7 10 7a13.07 13.07 0 0 0 7.13-2.04"/><path d="M2 2l20 20"/></svg>';

        function applyPasswordEyeState(field) {
            var input = field.querySelector('input');
            var button = field.querySelector('.password-eye');
            if (!input || !button) {
                return;
            }
            var revealed = input.getAttribute('data-revealed') === '1';
            input.setAttribute('type', revealed ? 'text' : 'password');
            // 图标反映当前状态：掩码=闭眼，明文=睁眼
            button.innerHTML = revealed ? EYE_ICON : EYE_OFF_ICON;
            button.setAttribute('aria-label', revealed ? '隐藏密码' : '显示密码');
            button.setAttribute('aria-pressed', String(revealed));
        }

        document.addEventListener('click', function (event) {
            var button = event.target.closest('.password-eye');
            if (!button) {
                return;
            }
            var field = button.closest('.password-field');
            if (!field) {
                return;
            }
            var input = field.querySelector('input');
            if (!input) {
                return;
            }
            input.setAttribute('data-revealed', input.getAttribute('data-revealed') === '1' ? '0' : '1');
            applyPasswordEyeState(field);
        });

        function injectPasswordEyes() {
            document.querySelectorAll('.password-field').forEach(function (field) {
                if (field.querySelector('.password-eye')) {
                    applyPasswordEyeState(field);   // 已有按钮：校准状态
                    return;
                }
                var input = field.querySelector('input');
                if (!input) {
                    return;
                }
                if (!input.id) {
                    input.id = 'pw-' + Math.random().toString(36).slice(2, 8);
                }
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'password-eye';
                button.setAttribute('aria-label', '显示密码');
                button.setAttribute('aria-pressed', 'false');
                field.appendChild(button);
                applyPasswordEyeState(field);
            });
        }
        injectPasswordEyes();

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

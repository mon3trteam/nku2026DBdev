# 互联网数据库开发 个人作业 3 - 开源建站工具初试文档

团队：M3  
项目：数聚 TeamHub  
学号：2411672  
姓名：金暄琛  

## 本地站点

- 首页：http://127.0.0.1:8081/
- 后台：http://127.0.0.1:8081/wp-admin/
- 本地管理员：admin
- 本地演示密码：M3WordPress_2411672!

## 启动方式

MariaDB 使用 XAMPP 自带版本，监听 3307：

```powershell
E:\SQL\xampp\mysql\bin\mysqld.exe --defaults-file=E:\SQL\xampp\mysql\bin\my.ini --port=3307 --console
```

WordPress 使用 PHP 8.0 内置服务器，监听 8081：

```powershell
E:\SQL\xampp\php\php.exe -S 127.0.0.1:8081 -t "<解压目录>\site\wordpress"
```

## 已安装并启用

- WordPress 7.1 中文版
- Twenty Twenty-Five 主题
- Spectra Legacy 2.20.6
- Rank Math SEO 1.0.281
- Contact Form 7 5.8.7

## 截图

- `screenshots/wordpress-home-1440.png`：桌面端完整首页
- `screenshots/wordpress-home-390.png`：移动端完整首页
- `screenshots/wordpress-plugins-1440.png`：后台插件启用列表
- `screenshots/wordpress-dashboard-1440.png`：WordPress 7.1 后台仪表盘
- `screenshots/wordpress-form-success.png`：Contact Form 7 实际提交成功状态

- `screenshots/wordpress-install-01-db.png` ~ `wordpress-install-06-admin.png`：安装过程逐屏记录（数据库信息、连接成功、站点信息、安装完成、登录、仪表盘），对应报告附录 B

## 说明

本机没有外部 SMTP，Contact Form 7 提交通过本地邮件接收器处理：邮件内容写入 `wp-content/mail-logs/mail.log`，不会向外部发送。截图均来自实际运行的 WordPress 页面和后台。

WordPress 核心源码位于本地工作目录 `site/wordpress`，体积较大，未随本压缩包提交；本包只包含报告、PDF 与管理后台/页面截图。安装与还原步骤见报告第 2 章。

## 最终提交

压缩包命名：`作业3(2411672_金暄琛).zip`

包内主文档：

```text
作业3(2411672_金暄琛).docx
作业3(2411672_金暄琛).pdf
```

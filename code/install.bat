@echo off
REM =====================================================================
REM 文件名：install.bat
REM 功能：团队主页项目本地部署脚本（Windows，无 Docker 环境的备选方案）
REM 作者：（部署模块负责人 学号 姓名）
REM 创建日期：2026-10-29
REM 所属模块：部署（加分项）
REM 说明：优先使用 docker compose up -d 一键部署；本脚本面向 phpStudy/XAMPP
REM TODO：实现建库、导入 install.sql、检测 php/mysql 环境的完整流程
REM =====================================================================
echo [nku2026DBdev] 本地部署脚本（占位，待实现）
echo 提示：推荐直接使用  docker compose up -d  一键部署
echo 1) 创建数据库 team_home ...
echo 2) 导入 data\install.sql ...
echo 3) 站点根目录指向 public，或 php -S localhost:8080 -t public ...
pause

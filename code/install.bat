@echo off
setlocal EnableExtensions EnableDelayedExpansion

REM =====================================================================
REM File: install.bat
REM Purpose: local deployment on Windows without Docker (phpStudy/XAMPP)
REM Author: 金暄琛（2411672）
REM Created: 2026-10-29
REM Module: deployment (bonus)
REM
REM Notes:
REM   1) Docker Compose is the recommended path; this script is a fallback.
REM   2) Requires PHP 8.0+ and the MySQL 8.0+ client in PATH.
REM   3) Creates database team_home and user team, then imports
REM      data\install.sql. The SQL drops existing tables, so the script
REM      asks for confirmation when the database is not empty.
REM   4) Messages are ASCII-only so the script parses correctly under any
REM      Windows code page. See README.md (Chinese) for full instructions.
REM =====================================================================

cd /d "%~dp0"

set "DB_HOST=127.0.0.1"
set "DB_PORT=3306"
set "DB_NAME=team_home"
set "DB_USER=team"
set "DB_PASS=team123456"

if not defined MYSQL_ADMIN_USER set "MYSQL_ADMIN_USER=root"

where php >nul 2>nul
if errorlevel 1 (
    echo [ERROR] php not found. Install PHP 8.0+ and add it to PATH.
    goto :fail
)

where mysql >nul 2>nul
if errorlevel 1 (
    echo [ERROR] mysql client not found. Install MySQL 8.0+ and add it to PATH.
    goto :fail
)

set "PHP_VERSION_ID="
for /f "delims=" %%v in ('php -r "echo PHP_VERSION_ID;"') do set "PHP_VERSION_ID=%%v"
if not defined PHP_VERSION_ID (
    echo [ERROR] Cannot run php. Check the PHP installation.
    goto :fail
)
if !PHP_VERSION_ID! LSS 80000 (
    echo [ERROR] PHP 8.0 or newer is required.
    goto :fail
)

if not defined MYSQL_ADMIN_PASSWORD (
    set /p "MYSQL_ADMIN_USER=MySQL admin user (default root): "
    if "!MYSQL_ADMIN_USER!"=="" set "MYSQL_ADMIN_USER=root"
    set /p "MYSQL_ADMIN_PASSWORD=MySQL admin password: "
)
if not defined MYSQL_ADMIN_PASSWORD (
    echo [ERROR] The admin password cannot be empty.
    goto :fail
)

set "CNF=%TEMP%\teamhub_mysql_%RANDOM%%RANDOM%.cnf"
set "SQLTMP=%TEMP%\teamhub_install_%RANDOM%%RANDOM%.sql"
set "QUERYOUT=%TEMP%\teamhub_query_%RANDOM%%RANDOM%.txt"

> "%CNF%" (
    echo [client]
    echo host=!DB_HOST!
    echo port=!DB_PORT!
    echo user=!MYSQL_ADMIN_USER!
    echo password=!MYSQL_ADMIN_PASSWORD!
    echo default-character-set=utf8mb4
)

echo [1/4] Checking MySQL connection ...
mysql --defaults-extra-file="%CNF%" -N -B -e "SELECT 1;" >nul 2>nul
if errorlevel 1 (
    echo [ERROR] Cannot connect to MySQL at !DB_HOST!:!DB_PORT!. Check the service and credentials.
    goto :fail
)

mysql --defaults-extra-file="%CNF%" -N -B -e "SELECT VERSION();" > "%QUERYOUT%" 2>nul
set "MYSQL_VERSION="
set /p "MYSQL_VERSION="<"%QUERYOUT%"
findstr /i "MariaDB" "%QUERYOUT%" >nul
if not errorlevel 1 (
    echo [ERROR] MariaDB detected. install.sql needs MySQL 8.0+ with utf8mb4_0900_ai_ci.
    goto :fail
)
for /f "tokens=1 delims=." %%m in ("!MYSQL_VERSION!") do set "MYSQL_MAJOR=%%m"
if not defined MYSQL_MAJOR (
    echo [ERROR] Cannot detect the MySQL version: !MYSQL_VERSION!
    goto :fail
)
if !MYSQL_MAJOR! LSS 8 (
    echo [ERROR] MySQL 8.0 or newer is required. Current: !MYSQL_VERSION!
    goto :fail
)
echo       MySQL !MYSQL_VERSION!

echo [2/4] Checking the target database ...
set "TABLE_COUNT=0"
mysql --defaults-extra-file="%CNF%" -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='team_home' AND table_type='BASE TABLE';" > "%QUERYOUT%" 2>nul
set /p "TABLE_COUNT="<"%QUERYOUT%"
if !TABLE_COUNT! GTR 0 (
    if not "!FORCE!"=="1" (
        echo       Database team_home already has !TABLE_COUNT! tables. install.sql drops and recreates them.
        set /p "CONFIRM=      Type yes to continue: "
        if /i "!CONFIRM!"=="yes" (
            echo       Continuing.
        ) else (
            echo Cancelled. The database was not changed.
            goto :cleanup_ok
        )
    )
)

echo [3/4] Creating the database and application user ...
> "%SQLTMP%" (
    echo CREATE DATABASE IF NOT EXISTS team_home DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
    echo CREATE USER IF NOT EXISTS 'team'@'localhost' IDENTIFIED BY 'team123456';
    echo ALTER USER 'team'@'localhost' IDENTIFIED BY 'team123456';
    echo GRANT ALL PRIVILEGES ON team_home.* TO 'team'@'localhost';
    echo CREATE USER IF NOT EXISTS 'team'@'127.0.0.1' IDENTIFIED BY 'team123456';
    echo ALTER USER 'team'@'127.0.0.1' IDENTIFIED BY 'team123456';
    echo GRANT ALL PRIVILEGES ON team_home.* TO 'team'@'127.0.0.1';
    echo FLUSH PRIVILEGES;
)
mysql --defaults-extra-file="%CNF%" < "%SQLTMP%"
if errorlevel 1 (
    echo [ERROR] Failed to create the database or application user. The admin account needs CREATE USER / GRANT privileges.
    goto :fail
)

echo [4/4] Importing data\install.sql ...
mysql --defaults-extra-file="%CNF%" < data\install.sql
if errorlevel 1 (
    echo [ERROR] Failed to import data\install.sql.
    goto :fail
)

set "TABLE_COUNT=0"
set "VIEW_COUNT=0"
mysql --defaults-extra-file="%CNF%" -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='team_home' AND table_type='BASE TABLE';" > "%QUERYOUT%" 2>nul
set /p "TABLE_COUNT="<"%QUERYOUT%"
mysql --defaults-extra-file="%CNF%" -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='team_home' AND table_type='VIEW';" > "%QUERYOUT%" 2>nul
set /p "VIEW_COUNT="<"%QUERYOUT%"
if !TABLE_COUNT! LSS 10 (
    echo [ERROR] Only !TABLE_COUNT! tables were imported; the course requires more than 10.
    goto :fail
)

echo.
echo Deployment finished: database team_home, !TABLE_COUNT! tables, !VIEW_COUNT! views.
echo.
echo Start the PHP built-in server (local demo only):
echo   set DB_HOST=127.0.0.1
echo   set DB_PORT=3306
echo   set DB_NAME=team_home
echo   set DB_USER=team
echo   set DB_PASS=team123456
echo   php -S localhost:8080 -t public
echo.
echo Then open http://localhost:8080/
echo Admin: http://localhost:8080/index.php?r=auth/login
echo Initial accounts are listed in the header of data\install.sql; the first login must change the password.
echo.
echo For production, point the Apache/Nginx document root to code\public.
echo.
goto :cleanup_ok

:cleanup_ok
call :cleanup_files
pause
exit /b 0

:fail
echo.
echo [FAILED] Deployment did not finish. Fix the message above and retry.
call :cleanup_files
pause
exit /b 1

:cleanup_files
if exist "%CNF%" del /q "%CNF%" >nul 2>nul
if exist "%SQLTMP%" del /q "%SQLTMP%" >nul 2>nul
if exist "%QUERYOUT%" del /q "%QUERYOUT%" >nul 2>nul
exit /b 0

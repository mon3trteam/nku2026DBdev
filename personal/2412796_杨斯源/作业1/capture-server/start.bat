@echo off
REM Keep this file pure ASCII: cmd.exe parses .bat byte-by-byte using the
REM active code page, so non-ASCII text here breaks parsing (especially
REM right after "chcp 65001").
chcp 65001 >nul
cd /d "%~dp0"
echo Console code page switched to UTF-8 (65001): Chinese logs will not garble.
echo.
E:\python\python.exe server.py
pause

@echo off
REM Keep this file pure ASCII: cmd.exe parses .bat byte-by-byte using the
REM active code page, so non-ASCII text here breaks parsing (especially
REM right after "chcp 65001"). Child processes still output UTF-8 fine.
chcp 65001 >nul
cd /d "%~dp0"

echo ============================================
echo  [1/3] Unit tests: typing / escaping / injection
echo ============================================
node "..\extension\lib\sqlbuilder.test.js"
if errorlevel 1 goto fail

echo.
echo ============================================
echo  [2/3] Content script DOM test (jsdom + demo.html)
echo ============================================
node collector.test.js
if errorlevel 1 goto fail

echo.
echo ============================================
echo  [3/3] Round-trip into a real SQLite database
echo ============================================
node emit-sql.js > "%TEMP%\p1_out.sql"
if errorlevel 1 goto fail
E:\python\python.exe run-in-sqlite.py < "%TEMP%\p1_out.sql"
if errorlevel 1 goto fail

echo.
echo ALL TESTS PASSED
pause
exit /b 0

:fail
echo.
echo *** SOME TESTS FAILED - see the output above ***
pause
exit /b 1

@echo off
setlocal enabledelayedexpansion
title NPC ELMS - Auto-Clone ^& Smart Dependency Installer
color 0A

echo ==============================================================================
echo       NAVOTAS POLYTECHNIC COLLEGE (NPC) ELMS - AUTO-SETUP ^& CLONER
echo ==============================================================================
echo   Script na awtomatikong mag-i-install ng lahat ng kailangan sa bagong PC/Laptop!
echo ==============================================================================
echo.

:: ------------------------------------------------------------------
:: 1. CHECK INTERNET CONNECTION
:: ------------------------------------------------------------------
echo [1/5] Sinusuri ang Internet Connection...
ping -n 1 8.8.8.8 >nul 2>&1
if %ERRORLEVEL% neq 0 (
    powershell -Command "try { $r = Invoke-WebRequest -Uri 'https://github.com' -TimeoutSec 4 -UseBasicParsing; exit 0 } catch { exit 1 }" >nul 2>&1
    if %ERRORLEVEL% neq 0 (
        color 0C
        echo.
        echo ==============================================================================
        echo  [ERROR] WALANG INTERNET CONNECTION!
        echo ==============================================================================
        echo  Kailangan po ng aktibong internet para ma-download ang Git, PHP, at ma-clone
        echo  ang buong repository mula sa GitHub.
        echo.
        echo  Paki-konekta muna ang laptop/PC sa Wi-Fi o Hotspot, tapos i-run muli ito.
        echo ==============================================================================
        echo.
        pause
        exit /b 1
    )
)
echo [OK] May Internet Connection!
echo.

:: ------------------------------------------------------------------
:: 2. CHOOSE STORAGE DRIVE / DESTINATION FOLDER
:: ------------------------------------------------------------------
echo ==============================================================================
echo [2/5] Pumili ng Drive / Folder kung saan ilalagay ang Project:
echo ==============================================================================
echo.
set DRIVE_CHOICE=1
if exist "D:\" (
    echo   [1] Drive D: (D:\NPC_ELMS)   -- [RECOMMENDED: Malaki ang espasyo]
    echo   [2] Drive C: (C:\NPC_ELMS)
    echo   [3] Custom Folder (Ikaw ang magta-type ng path)
    echo.
    set /p "DRIVE_CHOICE=Piliin ang number [1, 2, o 3] (Default: 1): "
) else (
    echo   [1] Drive C: (C:\NPC_ELMS)   -- [Default]
    echo   [2] Custom Folder (Ikaw ang magta-type ng path)
    echo.
    set /p "DRIVE_CHOICE=Piliin ang number [1 o 2] (Default: 1): "
)

set "TARGET_DIR=C:\NPC_ELMS"
if exist "D:\" (
    if "%DRIVE_CHOICE%"=="1" set "TARGET_DIR=D:\NPC_ELMS"
    if "%DRIVE_CHOICE%"=="2" set "TARGET_DIR=C:\NPC_ELMS"
    if "%DRIVE_CHOICE%"=="3" (
        set /p "TARGET_DIR=I-type ang buong folder path (hal. D:\MyProjects\ELMS): "
    )
) else (
    if "%DRIVE_CHOICE%"=="1" set "TARGET_DIR=C:\NPC_ELMS"
    if "%DRIVE_CHOICE%"=="2" (
        set /p "TARGET_DIR=I-type ang buong folder path: "
    )
)

echo.
echo [OK] Mapupunta ang project sa: "%TARGET_DIR%"
if not exist "%TARGET_DIR%" mkdir "%TARGET_DIR%"
if not exist "%TARGET_DIR%\tools" mkdir "%TARGET_DIR%\tools"
echo.

:: ------------------------------------------------------------------
:: 3. AUTO-CHECK & DOWNLOAD TOOLS (GIT, PHP, COMPOSER, NODE, DOCKER)
:: ------------------------------------------------------------------
echo ==============================================================================
echo [3/5] Sinusuri ang mga kinakailangang software sa system...
echo ==============================================================================
echo.

:: --- 3.1 Check GIT ---
echo [*] Sinusuri ang Git...
where git >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo     [OK] Naka-install na ang Git!
) else (
    echo     [!] Walang Git. Dina-download at ini-install ang Git for Windows...
    powershell -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $installer = '%TARGET_DIR%\tools\git-installer.exe'; Write-Host 'Downloading Git...'; Invoke-WebRequest -Uri 'https://github.com/git-for-windows/git/releases/download/v2.44.0.windows.1/Git-2.44.0-64-bit.exe' -OutFile $installer; Write-Host 'Installing Git silently...'; Start-Process -FilePath $installer -ArgumentList '/VERYSILENT /NORESTART /NOCANCEL /SP- /CLOSEAPPLICATIONS' -Wait; Remove-Item $installer -Force -ErrorAction SilentlyContinue"
    set "PATH=%PATH%;C:\Program Files\Git\cmd;C:\Program Files\Git\bin"
    echo     [OK] Matagumpay na na-install ang Git!
)

:: --- 3.2 Check PHP ---
echo [*] Sinusuri ang PHP...
set PHP_BIN=
where php >nul 2>&1
if %ERRORLEVEL% equ 0 (
    set PHP_BIN=php
    echo     [OK] Naka-install na ang PHP sa system PATH!
) else if exist "D:\xampp\php\php.exe" (
    set PHP_BIN=D:\xampp\php\php.exe
    echo     [OK] Nahanap ang PHP sa D:\xampp\php\php.exe!
) else if exist "C:\xampp\php\php.exe" (
    set PHP_BIN=C:\xampp\php\php.exe
    echo     [OK] Nahanap ang PHP sa C:\xampp\php\php.exe!
) else if exist "%TARGET_DIR%\php\php.exe" (
    set PHP_BIN=%TARGET_DIR%\php\php.exe
    echo     [OK] Nahanap ang Portable PHP sa %TARGET_DIR%\php!
) else (
    echo     [!] Walang PHP. Dina-download ang Portable PHP 8.2 (Zero Configuration)...
    powershell -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $zip = '%TARGET_DIR%\tools\php.zip'; $dest = '%TARGET_DIR%\php'; Write-Host 'Downloading PHP 8.2...'; Invoke-WebRequest -Uri 'https://windows.php.net/downloads/releases/archives/php-8.2.18-Win32-vs16-x64.zip' -OutFile $zip; Write-Host 'Extracting PHP...'; Expand-Archive -Path $zip -DestinationPath $dest -Force; Remove-Item $zip -Force; if (Test-Path \"$dest\php.ini-development\") { Copy-Item \"$dest\php.ini-development\" \"$dest\php.ini\"; (Get-Content \"$dest\php.ini\") -replace ';extension=curl', 'extension=curl' -replace ';extension=mbstring', 'extension=mbstring' -replace ';extension=pdo_mysql', 'extension=pdo_mysql' -replace ';extension=openssl', 'extension=openssl' -replace ';extension=fileinfo', 'extension=fileinfo' -replace ';extension=gd', 'extension=gd' | Set-Content \"$dest\php.ini\" }"
    set PHP_BIN=%TARGET_DIR%\php\php.exe
    set "PATH=%PATH%;%TARGET_DIR%\php"
    echo     [OK] Matagumpay na naihanda ang Portable PHP 8.2!
)

:: --- 3.3 Check Node.js ---
echo [*] Sinusuri ang Node.js...
where node >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo     [OK] Naka-install na ang Node.js!
) else (
    echo     [!] Walang Node.js. Dina-download ang Portable Node.js...
    powershell -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $zip = '%TARGET_DIR%\tools\node.zip'; $dest = '%TARGET_DIR%\node'; Write-Host 'Downloading Node.js LTS...'; Invoke-WebRequest -Uri 'https://nodejs.org/dist/v20.12.2/node-v20.12.2-win-x64.zip' -OutFile $zip; Write-Host 'Extracting Node.js...'; Expand-Archive -Path $zip -DestinationPath '%TARGET_DIR%\tools\temp_node' -Force; Move-Item '%TARGET_DIR%\tools\temp_node\node-v20.12.2-win-x64\*' $dest -Force; Remove-Item '%TARGET_DIR%\tools\temp_node' -Recurse -Force; Remove-Item $zip -Force"
    set "PATH=%PATH%;%TARGET_DIR%\node"
    echo     [OK] Matagumpay na naihanda ang Node.js!
)

:: --- 3.4 Check Docker ---
echo [*] Sinusuri ang Docker...
where docker >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo     [OK] Naka-install na ang Docker!
) else (
    echo     [INFO] Optional: Hindi pa naka-install ang Docker Desktop.
    echo     (Maaari mong i-download ito nang libre sa https://www.docker.com/ kapag gagamit ng local LiveKit stack.)
)
echo.

:: ------------------------------------------------------------------
:: 4. AUTO-CLONE GITHUB REPOSITORY
:: ------------------------------------------------------------------
echo ==============================================================================
echo [4/5] Kina-clone ang NPC ELMS Repository mula sa GitHub...
echo ==============================================================================
echo Target Repo: https://github.com/JDerramas/Laravel.git
echo.

if exist "%TARGET_DIR%\app\.git" (
    echo [!] Naka-clone na ang project. Ina-update na lamang via git pull...
    pushd "%TARGET_DIR%\app"
    git pull origin main
    popd
) else (
    echo Clonin repository into "%TARGET_DIR%\app"...
    git clone https://github.com/JDerramas/Laravel.git "%TARGET_DIR%\app"
    if %ERRORLEVEL% neq 0 (
        echo [!] Subukan gamit ang fallback branch...
        git clone https://github.com/omsim1231/Npc.git "%TARGET_DIR%\app"
    )
)

echo.
echo [OK] Matagumpay na na-clone ang buong system code!
echo.

:: ------------------------------------------------------------------
:: 5. CREATE ONE-CLICK LAUNCHER IN TARGET DIRECTORY
:: ------------------------------------------------------------------
echo ==============================================================================
echo [5/5] Gumagawa ng 1-Click Launchers sa bagong PC...
echo ==============================================================================

set "LAUNCHER=%TARGET_DIR%\START_NPC_ELMS.bat"
(
echo @echo off
echo title NPC ELMS Launcher
echo cd /d "%%~dp0app"
echo if exist "start.bat" (
echo     call start.bat
echo ^) else (
echo     php -S 0.0.0.0:8000
echo ^)
) > "%LAUNCHER%"

echo [OK] Ginawa ang launcher: "%LAUNCHER%"

:: Create Desktop Shortcut via PowerShell
powershell -Command "$WshShell = New-Object -comObject WScript.Shell; $Shortcut = $WshShell.CreateShortcut([System.IO.Path]::Combine([System.Environment]::GetFolderPath('Desktop'), 'NPC ELMS.lnk'^)^); $Shortcut.TargetPath = '%LAUNCHER%'; $Shortcut.WorkingDirectory = '%TARGET_DIR%'; $Shortcut.Save()" >nul 2>&1

echo.
color 0A
echo ==============================================================================
echo   [CONGRATULATIONS!] TAPOS NA ANG LAHAT NG INSTALLATION ^& SETUP!
echo ==============================================================================
echo.
echo  Lokasyon ng Files : %TARGET_DIR%\app
echo  1-Click Launcher  : %LAUNCHER%
echo  Desktop Shortcut  : Lilitaw sa Desktop bilang "NPC ELMS"
echo.
echo  Pindutin ang kahit anong key para buksan na agad ang NPC ELMS!
echo ==============================================================================
pause
start "" "%LAUNCHER%"

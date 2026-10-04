@echo off
setlocal enabledelayedexpansion
title NPC ELMS - Auto-Clone ^& Smart Dependency Installer (XAMPP / Portable)
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
:: 2. DETECT XAMPP & CHOOSE STORAGE DESTINATION
:: ------------------------------------------------------------------
echo ==============================================================================
echo [2/5] Pumili ng Setup Mode / Folder kung saan ilalagay ang Project:
echo ==============================================================================
echo.

set "DEFAULT_MODE=1"
set "HAS_XAMPP_D="
set "HAS_XAMPP_C="
if exist "D:\xampp\htdocs" set "HAS_XAMPP_D=1"
if exist "C:\xampp\htdocs" set "HAS_XAMPP_C=1"

if defined HAS_XAMPP_D (
    echo   [1] XAMPP Drive D: (D:\xampp\htdocs\LocalAI\app)  -- [FOUND XAMPP IN D:]
    echo   [2] Standalone Portable (D:\NPC_ELMS)
    echo   [3] Custom Folder (Ikaw ang magta-type ng path)
    set /p "DRIVE_CHOICE=Piliin ang number [1, 2, o 3] (Default: 1): "
    if "!DRIVE_CHOICE!"=="" set "DRIVE_CHOICE=1"
    if "!DRIVE_CHOICE!"=="1" set "CLONE_DIR=D:\xampp\htdocs\LocalAI\app"
    if "!DRIVE_CHOICE!"=="2" set "CLONE_DIR=D:\NPC_ELMS\app"
    if "!DRIVE_CHOICE!"=="3" (
        set /p "CLONE_DIR=I-type ang buong folder path: "
    )
) else if defined HAS_XAMPP_C (
    echo   [1] XAMPP Drive C: (C:\xampp\htdocs\LocalAI\app)  -- [FOUND XAMPP IN C:]
    echo   [2] Standalone Portable (C:\NPC_ELMS)
    echo   [3] Custom Folder (Ikaw ang magta-type ng path)
    set /p "DRIVE_CHOICE=Piliin ang number [1, 2, o 3] (Default: 1): "
    if "!DRIVE_CHOICE!"=="" set "DRIVE_CHOICE=1"
    if "!DRIVE_CHOICE!"=="1" set "CLONE_DIR=C:\xampp\htdocs\LocalAI\app"
    if "!DRIVE_CHOICE!"=="2" set "CLONE_DIR=C:\NPC_ELMS\app"
    if "!DRIVE_CHOICE!"=="3" (
        set /p "CLONE_DIR=I-type ang buong folder path: "
    )
) else (
    echo   [1] Auto-Install XAMPP 8.2 + Clone to xampp\htdocs\LocalAI\app
    echo   [2] Standalone Portable (Zero XAMPP required, may built-in PHP 8.2)
    echo   [3] Custom Folder (Ikaw ang magta-type ng path)
    set /p "DRIVE_CHOICE=Piliin ang number [1, 2, o 3] (Default: 2): "
    if "!DRIVE_CHOICE!"=="" set "DRIVE_CHOICE=2"
    if "!DRIVE_CHOICE!"=="1" (
        set "CLONE_DIR=C:\xampp\htdocs\LocalAI\app"
        set "NEED_XAMPP_INSTALL=1"
    )
    if "!DRIVE_CHOICE!"=="2" (
        if exist "D:\" (set "CLONE_DIR=D:\NPC_ELMS\app") else (set "CLONE_DIR=C:\NPC_ELMS\app")
    )
    if "!DRIVE_CHOICE!"=="3" (
        set /p "CLONE_DIR=I-type ang buong folder path: "
    )
)

echo.
echo [OK] Target Clone Directory: "%CLONE_DIR%"
for %%F in ("%CLONE_DIR%\..") do set "PARENT_DIR=%%~fF"
if not exist "%PARENT_DIR%" mkdir "%PARENT_DIR%"
if not exist "%PARENT_DIR%\tools" mkdir "%PARENT_DIR%\tools"
echo.

:: ------------------------------------------------------------------
:: 3. AUTO-CHECK & DOWNLOAD TOOLS (GIT, PHP, XAMPP, NODE)
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
    powershell -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $installer = '%PARENT_DIR%\tools\git-installer.exe'; Write-Host 'Downloading Git...'; Invoke-WebRequest -Uri 'https://github.com/git-for-windows/git/releases/download/v2.44.0.windows.1/Git-2.44.0-64-bit.exe' -OutFile $installer; Write-Host 'Installing Git silently...'; Start-Process -FilePath $installer -ArgumentList '/VERYSILENT /NORESTART /NOCANCEL /SP- /CLOSEAPPLICATIONS' -Wait; Remove-Item $installer -Force -ErrorAction SilentlyContinue"
    set "PATH=%PATH%;C:\Program Files\Git\cmd;C:\Program Files\Git\bin"
    echo     [OK] Matagumpay na na-install ang Git!
)

:: --- 3.2 Optional XAMPP Installer ---
if defined NEED_XAMPP_INSTALL (
    echo [*] Dina-download at ini-install ang XAMPP for Windows...
    powershell -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $xampp = '%PARENT_DIR%\tools\xampp-installer.exe'; Write-Host 'Downloading XAMPP 8.2...'; Invoke-WebRequest -Uri 'https://sourceforge.net/projects/xampp/files/XAMPP%%20Windows/8.2.12/xampp-windows-x64-8.2.12-0-VS16-installer.exe/download' -OutFile $xampp; Write-Host 'Launching XAMPP installer...'; Start-Process -FilePath $xampp -Wait; Remove-Item $xampp -Force -ErrorAction SilentlyContinue"
)

:: --- 3.3 Check PHP ---
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
) else if exist "%PARENT_DIR%\php\php.exe" (
    set PHP_BIN=%PARENT_DIR%\php\php.exe
    echo     [OK] Nahanap ang Portable PHP sa %PARENT_DIR%\php!
) else (
    echo     [!] Walang PHP. Dina-download ang Portable PHP 8.2 (Zero Configuration)...
    powershell -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $zip = '%PARENT_DIR%\tools\php.zip'; $dest = '%PARENT_DIR%\php'; Write-Host 'Downloading PHP 8.2...'; Invoke-WebRequest -Uri 'https://windows.php.net/downloads/releases/archives/php-8.2.18-Win32-vs16-x64.zip' -OutFile $zip; Write-Host 'Extracting PHP...'; Expand-Archive -Path $zip -DestinationPath $dest -Force; Remove-Item $zip -Force; if (Test-Path \"$dest\php.ini-development\") { Copy-Item \"$dest\php.ini-development\" \"$dest\php.ini\"; (Get-Content \"$dest\php.ini\") -replace ';extension=curl', 'extension=curl' -replace ';extension=mbstring', 'extension=mbstring' -replace ';extension=pdo_mysql', 'extension=pdo_mysql' -replace ';extension=openssl', 'extension=openssl' -replace ';extension=fileinfo', 'extension=fileinfo' -replace ';extension=gd', 'extension=gd' | Set-Content \"$dest\php.ini\" }"
    set PHP_BIN=%PARENT_DIR%\php\php.exe
    set "PATH=%PATH%;%PARENT_DIR%\php"
    echo     [OK] Matagumpay na naihanda ang Portable PHP 8.2!
)

:: --- 3.4 Check Node.js ---
echo [*] Sinusuri ang Node.js...
where node >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo     [OK] Naka-install na ang Node.js!
) else (
    echo     [!] Walang Node.js. Dina-download ang Portable Node.js...
    powershell -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $zip = '%PARENT_DIR%\tools\node.zip'; $dest = '%PARENT_DIR%\node'; Write-Host 'Downloading Node.js LTS...'; Invoke-WebRequest -Uri 'https://nodejs.org/dist/v20.12.2/node-v20.12.2-win-x64.zip' -OutFile $zip; Write-Host 'Extracting Node.js...'; Expand-Archive -Path $zip -DestinationPath '%PARENT_DIR%\tools\temp_node' -Force; Move-Item '%PARENT_DIR%\tools\temp_node\node-v20.12.2-win-x64\*' $dest -Force; Remove-Item '%PARENT_DIR%\tools\temp_node' -Recurse -Force; Remove-Item $zip -Force"
    set "PATH=%PATH%;%PARENT_DIR%\node"
    echo     [OK] Matagumpay na naihanda ang Node.js!
)
echo.

:: ------------------------------------------------------------------
:: 4. AUTO-CLONE GITHUB REPOSITORY & SETUP .ENV
:: ------------------------------------------------------------------
echo ==============================================================================
echo [4/5] Kina-clone ang NPC ELMS Repository mula sa GitHub...
echo ==============================================================================
echo Target Repo: https://github.com/JDerramas/Laravel.git
echo Lokasyon   : "%CLONE_DIR%"
echo.

if exist "%CLONE_DIR%\.git" (
    echo [!] Naka-clone na ang project. Ina-update na lamang via git pull...
    pushd "%CLONE_DIR%"
    git pull origin main
    popd
) else (
    echo Cloning repository into "%CLONE_DIR%"...
    git clone https://github.com/JDerramas/Laravel.git "%CLONE_DIR%"
    if %ERRORLEVEL% neq 0 (
        echo [!] Subukan gamit ang fallback branch...
        git clone https://github.com/omsim1231/Npc.git "%CLONE_DIR%"
    )
)

:: Auto-copy .env if missing so AI & Supabase immediately work
if not exist "%CLONE_DIR%\.env" (
    if exist "%CLONE_DIR%\.env.example" (
        copy "%CLONE_DIR%\.env.example" "%CLONE_DIR%\.env" >nul
        echo [OK] Awtomatikong nilagay ang .env configuration!
    )
)

echo.
echo ==============================================================================
echo [AI SYSTEM STATUS]:
echo   Ang AI Assistant ay 100%% BUILT-IN via Free Cloud API (OpenRouter).
echo   HINDI NA KAILANGAN mag-download ng mabigat na 4GB local model file sa bagong PC!
echo   Agad nang gagana ang AI chat, attendance, at lecture assistants.
echo ==============================================================================
echo.

:: ------------------------------------------------------------------
:: 5. CREATE ONE-CLICK LAUNCHERS & DESKTOP SHORTCUT
:: ------------------------------------------------------------------
echo ==============================================================================
echo [5/5] Gumagawa ng 1-Click Launchers sa bagong PC...
echo ==============================================================================

set "LAUNCHER=%PARENT_DIR%\START_NPC_ELMS.bat"
(
echo @echo off
echo title NPC ELMS Launcher
echo cd /d "%CLONE_DIR%"
echo if exist "start.bat" (
echo     call start.bat
echo ^) else (
echo     php -S 0.0.0.0:8000
echo ^)
) > "%LAUNCHER%"

echo [OK] Ginawa ang launcher: "%LAUNCHER%"

:: Create Desktop Shortcut via PowerShell
powershell -Command "$WshShell = New-Object -comObject WScript.Shell; $Shortcut = $WshShell.CreateShortcut([System.IO.Path]::Combine([System.Environment]::GetFolderPath('Desktop'), 'NPC ELMS.lnk'^)^); $Shortcut.TargetPath = '%LAUNCHER%'; $Shortcut.WorkingDirectory = '%CLONE_DIR%'; $Shortcut.Save()" >nul 2>&1

echo.
color 0A
echo ==============================================================================
echo   [CONGRATULATIONS!] TAPOS NA ANG LAHAT NG INSTALLATION ^& SETUP!
echo ==============================================================================
echo.
echo  Lokasyon ng Files : %CLONE_DIR%
echo  1-Click Launcher  : %LAUNCHER%
echo  Desktop Shortcut  : Lilitaw sa Desktop bilang "NPC ELMS"
echo.
echo  Pindutin ang kahit anong key para buksan na agad ang NPC ELMS!
echo ==============================================================================
pause
start "" "%LAUNCHER%"

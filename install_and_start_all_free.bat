@echo off
setlocal enabledelayedexpansion
title NPC ELMS 100%% Free All-in-One Installer ^& Launcher
echo ================================================================
echo    Navotas Polytechnic College ELMS - 100%% Free Full Stack
echo   (PlugNmeet + LiveKit Docker + PHP + Cloudflare + Local AI)
echo ================================================================
echo.

cd /d "%~dp0"

:: ------------------------------------------------------------------
:: STEP 1: Check Docker & Start Docker Daemon if not running
:: ------------------------------------------------------------------
echo [1/6] Checking Docker Desktop installation...
where docker >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo [ERROR] Hindi nakita ang Docker sa system PATH.
    echo Paki-install ang Docker Desktop mula sa: https://www.docker.com/products/docker-desktop/
    echo Kapag na-install na, i-restart ang file na ito.
    pause
    exit /b 1
)

echo [OK] Docker is installed.
echo Checking if Docker Daemon is running...
docker info >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo [!] Hindi pa bukas ang Docker Desktop. Binubuksan na ngayon...
    if exist "C:\Program Files\Docker\Docker\Docker Desktop.exe" (
        start "" "C:\Program Files\Docker\Docker\Docker Desktop.exe"
    )
    echo Naghihintay habang nag-i-initialize ang Docker Desktop (humigit-kumulang 15-20 segundo)...
    :wait_docker
    timeout /t 3 /nobreak >nul
    docker info >nul 2>&1
    if %ERRORLEVEL% neq 0 (
        echo ...naghihintay pa sa Docker engine...
        goto wait_docker
    )
)
echo [OK] Docker Daemon is ACTIVE and ready!
echo.

:: ------------------------------------------------------------------
:: STEP 2: One-Click Install & Start Free Docker PlugNmeet + LiveKit
:: ------------------------------------------------------------------
echo [2/6] Auto-installing ^& Starting 100%% FREE Self-Hosted Video Stack...
echo (LiveKit WebRTC + PlugNmeet Conference + NATS + Redis + MariaDB)
pushd "..\plugnmeet-server"
docker compose up -d
popd
echo [OK] All 5 video conference containers are UP and running!
echo.

:: ------------------------------------------------------------------
:: STEP 3: Auto-detect PHP
:: ------------------------------------------------------------------
echo [3/6] Detecting PHP environment...
set PHP_EXE=php
if exist "D:\xampp\php\php.exe" (
    set PHP_EXE=D:\xampp\php\php.exe
    echo [OK] Found PHP in Drive D: D:\xampp\php\php.exe
) else if exist "C:\xampp\php\php.exe" (
    set PHP_EXE=C:\xampp\php\php.exe
    echo [OK] Found PHP in Drive C: C:\xampp\php\php.exe
)
set PHP_CLI_SERVER_WORKERS=4
start "NPC Web Server (Port 8000)" cmd /k "%PHP_EXE% -S 0.0.0.0:8000"
echo [OK] PHP Web Server launched on port 8000.
echo.

:: ------------------------------------------------------------------
:: STEP 4: Start Gateway Proxy Router (Node.js)
:: ------------------------------------------------------------------
echo [4/6] Starting NPC Unified Gateway Router on port 8001...
start "NPC Gateway Router (Port 8001)" cmd /k "node gateway.js"
echo [OK] Gateway Router launched (routes video ^& LMS traffic).
echo.

:: ------------------------------------------------------------------
:: STEP 5: Start Cloudflare Online Tunnel (100% Free Uncapped)
:: ------------------------------------------------------------------
echo [5/6] Starting Cloudflare Online Tunnel (100%% Free Unli Bandwidth)...
start "NPC Cloudflare Tunnel" cmd /k "cloudflared tunnel --url http://127.0.0.1:8001"
echo [OK] Cloudflare Tunnel window is open.
echo.

:: ------------------------------------------------------------------
:: STEP 6: Start Local AI Server (Qwen LLM 8080)
:: ------------------------------------------------------------------
echo [6/6] Starting Offline Local AI (Port 8080)...
if exist "..\..\llama-server.exe" (
    start "NPC Local AI (Port 8080)" cmd /k "cd /d "%~dp0..\.." ^&^& llama-server.exe -m LocalAI\models\Qwen_Qwen3-4B-Instruct-2507-Q4_K_M.gguf --port 8080 -c 2048 -ngl 0"
    echo [OK] Local AI launched.
) else (
    echo [!] llama-server.exe not found, skipping AI.
)
echo.

echo ================================================================
echo   [SUCCESS] Lahat ng Services ay 100%% LIBRE at gumagana na!
echo ================================================================
echo.
echo  1. Local Portal       : http://localhost:8000
echo  2. Gateway Proxy      : http://localhost:8001
echo  3. 100%% Free Video   : PlugNmeet (Port 8085) + LiveKit (Port 7880)
echo  4. Free Public Link   : Tingnan sa Cloudflare window (*.trycloudflare.com)
echo  5. Local AI Engine    : http://127.0.0.1:8080 (Qwen 4B)
echo.
echo [TIPS]:
echo  - 100%% LIBRE: Walang bayad / zero subscription dahil sariling Docker ang video server!
echo  - May Minimize / PiP button na sa loob ng Virtual Classroom.
echo  - Para i-stop lahat: I-run lamang ang stop.bat
echo ================================================================
pause

@echo off
title NPC ELMS All-in-One Launcher
echo ===================================================
echo     Starting Navotas Polytechnic College ELMS
echo   (PHP Web Server + Cloudflare Unli Tunnel + Local AI)
echo ===================================================
echo.

:: Auto-detect PHP in Drive D or Drive C
set PHP_EXE=php
if exist "D:\xampp\php\php.exe" (
    set PHP_EXE=D:\xampp\php\php.exe
    echo [OK] Found PHP in Drive D: D:\xampp\php\php.exe
) else if exist "C:\xampp\php\php.exe" (
    set PHP_EXE=C:\xampp\php\php.exe
    echo [OK] Found PHP in Drive C: C:\xampp\php\php.exe
)

cd /d "%~dp0"

:: 0. Start 100% Free Docker Stack (PlugNmeet + LiveKit)
echo [1/5] Checking 100%% Free Docker Stack (PlugNmeet + LiveKit)...
where docker >nul 2>&1
if %ERRORLEVEL% equ 0 (
    docker info >nul 2>&1
    if %ERRORLEVEL% equ 0 (
        pushd "..\plugnmeet-server"
        docker compose up -d >nul 2>&1
        popd
        echo [OK] 100%% Free Docker Stack is UP and running!
    ) else (
        echo [!] Docker Desktop is installed but not open. Open Docker Desktop for free video server.
    )
)

:: 1. Start PHP Web Server
echo [2/5] Starting PHP Web Server on port 8000...
set PHP_CLI_SERVER_WORKERS=4
start "NPC Web Server (Port 8000)" cmd /k "%PHP_EXE% -S 0.0.0.0:8000"

:: 2. Start Gateway Proxy Router (Routes /plugnmeet & /nats to Docker & / to PHP)
echo [2/4] Starting NPC Gateway Router on port 8001...
start "NPC Gateway Router (Port 8001)" cmd /k "node gateway.js"

:: 3. Start Cloudflare Tunnel (100% UNLIMITED Bandwidth / Zero Cap)
echo [3/4] Starting Cloudflare Online Tunnel (100% Unli Bandwidth)...
start "NPC Cloudflare Tunnel" cmd /k "cloudflared tunnel --url http://127.0.0.1:8001"

:: 4. Start Local AI Server (llama-server)
echo [4/4] Starting Local Offline AI (Qwen LLM Port 8080)...
start "NPC Local AI (Port 8080)" cmd /k "cd /d "%~dp0..\.." && llama-server.exe -m LocalAI\models\Qwen_Qwen3-4B-Instruct-2507-Q4_K_M.gguf --port 8080 -c 2048 -ngl 0"

echo.
echo ===================================================
echo Lahat ng 3 Services ay matagumpay na pinaandar!
echo.
echo  1. Local Website    : http://localhost:8000
echo  2. Unli Public Link : Tingnan sa Cloudflare window (trycloudflare.com)
echo  3. Local AI Engine  : http://127.0.0.1:8080 (Qwen 4B)
echo  4. Virtual Class    : LiveKit Cloud (100% Hosted WebRTC)
echo.
echo Para i-STOP ang lahat ng 3 services:
echo I-run lamang ang stop.bat o i-close ang mga windows.
echo ===================================================
pause

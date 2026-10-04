@echo off
title NPC ELMS All-in-One Stopper
echo ===================================================
echo     Stopping Navotas Polytechnic College ELMS
echo   (PHP Web Server + Ngrok Tunnel + Local AI)
echo ===================================================
echo.

echo [1/3] Stopping PHP Web Server (php.exe)...
taskkill /F /IM php.exe /T 2>nul

echo [2/4] Stopping Cloudflare Tunnel (cloudflared.exe)...
taskkill /F /IM cloudflared.exe /T 2>nul
taskkill /F /IM ngrok.exe /T 2>nul

echo [3/4] Stopping Local AI Server (llama-server.exe)...
taskkill /F /IM llama-server.exe /T 2>nul
taskkill /F /IM node.exe /T 2>nul

echo [4/4] Closing open service windows...
taskkill /F /FI "WINDOWTITLE eq NPC Web Server*" 2>nul
taskkill /F /FI "WINDOWTITLE eq NPC Gateway Router*" 2>nul
taskkill /F /FI "WINDOWTITLE eq NPC Cloudflare Tunnel*" 2>nul
taskkill /F /FI "WINDOWTITLE eq NPC Ngrok Tunnel*" 2>nul
taskkill /F /FI "WINDOWTITLE eq NPC Local AI*" 2>nul

echo.
echo ===================================================
echo Lahat ng 3 Services ay matagumpay na nai-STOP!
echo ===================================================
pause

@echo off
title Bootstrap Entorno Desarrollo
setlocal enabledelayedexpansion
set "SCRIPT_DIR=%~dp0"
set "TOOLS_DIR=%SCRIPT_DIR%bootstrap\tools"
set "CONFIG_ZIP=%SCRIPT_DIR%bootstrap\opencode-config.zip"
set "NODE_DIR=C:\Program Files\node-v24.17.0-win-x64"

%~d0
cd "%~dp0" || exit /b 1

echo === Bootstrap: Reparar entorno post-formateo ===
echo.

rem ----- VERIFICACION INICIAL: todo instalado? -----
set "ALL_OK=1"

node --version >nul 2>&1 || set "ALL_OK=0"
start /B /WAIT cmd /c "openclaw --version >nul 2>&1" || set "ALL_OK=0"
if not exist "%USERPROFILE%\AppData\Local\ms-playwright\chromium-1228" set "ALL_OK=0"
if not exist "%USERPROFILE%\.crewai-venv\Scripts\python.exe" set "ALL_OK=0"
if not exist "%USERPROFILE%\.crewai-venv\Lib\site-packages\crewai" set "ALL_OK=0"
schtasks /query /tn "OpenClaw Gateway" >nul 2>&1 || set "ALL_OK=0"
if not exist "%USERPROFILE%\.config\opencode\opencode.json" set "ALL_OK=0"
if not exist "%USERPROFILE%\.openclaw\openclaw.json" set "ALL_OK=0"

if "!ALL_OK!"=="1" (
    echo Todo ya esta instalado y configurado.
    echo No hay nada que hacer.
    echo.
    pause
    exit /b 0
)
echo Detectados componentes faltantes. Procediendo...
echo.

rem ----- Node.js -----
echo [1/6] Verificando Node.js...
node --version >nul 2>&1
if errorlevel 1 (
    set "NODE_ZIP=%TOOLS_DIR%\node-v24.17.0-win-x64.zip"
    if not exist "!NODE_ZIP!" (
        echo Descargando Node.js...
        if not exist "%TOOLS_DIR%" mkdir "%TOOLS_DIR%"
        curl -sL -o "!NODE_ZIP!" "https://nodejs.org/dist/v24.17.0/node-v24.17.0-win-x64.zip"
    )
    if exist "!NODE_ZIP!" (
        echo Extrayendo Node.js a %NODE_DIR%...
        powershell -Command "Expand-Archive -Path '!NODE_ZIP!' -DestinationPath 'C:\Program Files' -Force"
    )
    if exist "%NODE_DIR%\node.exe" set "PATH=%NODE_DIR%;%NODE_DIR%\node_modules\npm\bin;%PATH%"
) else (
    for /f "tokens=*" %%v in ('node --version') do echo Node.js: %%v
)
echo.

rem ----- openclaw global -----
echo [2/6] Instalando openclaw...
start /B /WAIT cmd /c "openclaw --version >nul 2>&1"
if errorlevel 1 (
    set "OPENCLAW_TGZ=%TOOLS_DIR%\openclaw-2026.6.8.tgz"
    if not exist "!OPENCLAW_TGZ!" (
        if not exist "%TOOLS_DIR%" mkdir "%TOOLS_DIR%"
        curl -sL -o "!OPENCLAW_TGZ!" "https://registry.npmjs.org/openclaw/-/openclaw-2026.6.8.tgz"
    )
    npm install -g "!OPENCLAW_TGZ!" 2>&1 | findstr /v "deprecated funding"
) else (
    for /f "tokens=*" %%v in ('cmd /c openclaw --version') do echo openclaw: %%v
)
echo.

rem ----- Playwright browsers -----
echo [3/6] Instalando Playwright chromium...
set "PLAYWRIGHT_DIR=%USERPROFILE%\AppData\Local\ms-playwright"
set "CHROME_ZIP=%TOOLS_DIR%\chrome-win64.zip"
if not exist "%PLAYWRIGHT_DIR%\chromium-1228" (
    if exist "!CHROME_ZIP!" (
        echo Instalando desde cache local...
        powershell -Command "Expand-Archive -Path '!CHROME_ZIP!' -DestinationPath '%PLAYWRIGHT_DIR%' -Force"
    ) else (
        npx playwright install chromium
    )
) else (
    echo Chromium ya instalado
)
echo.

rem ----- Python venv + crewai -----
echo [4/6] Preparando entorno Python...
set "CREWAI_VENV=%USERPROFILE%\.crewai-venv"
if not exist "%CREWAI_VENV%\Scripts\python.exe" (
    python --version >nul 2>&1
    if errorlevel 1 (
        echo ERROR: Python no encontrado. Instale Python 3.12+ desde python.org
        goto :SKIP_PYTHON
    )
    python -m venv "%CREWAI_VENV%"
)
if not exist "%CREWAI_VENV%\Lib\site-packages\crewai" (
    set "PIP_CACHE=%TOOLS_DIR%\pip-cache"
    if exist "!PIP_CACHE!" (
        "%CREWAI_VENV%\Scripts\pip" install --no-index --find-links "!PIP_CACHE!" crewai
    ) else (
        "%CREWAI_VENV%\Scripts\pip" install crewai
        if not exist "!PIP_CACHE!" mkdir "!PIP_CACHE!"
        "%CREWAI_VENV%\Scripts\python" -m pip download --dest "!PIP_CACHE!" crewai 2>nul
    )
) else (
    echo crewai ya instalado
)
:SKIP_PYTHON
echo.

rem ----- Restaurar configs -----
echo [5/6] Restaurando configs...
if exist "%CONFIG_ZIP%" (
    if not exist "%USERPROFILE%\.config\opencode\opencode.json" (
        powershell -Command "Expand-Archive -Path '%CONFIG_ZIP%' -DestinationPath '%USERPROFILE%' -Force"
        echo Configs restauradas.
    ) else (
        echo Configs ya existen.
    )
) else (
    echo WARNING: %CONFIG_ZIP% no encontrado. Las configs no se restauraron.
)
echo.

rem ----- Tarea programada -----
echo [6/6] Creando tarea programada para OpenClaw Gateway...
schtasks /query /tn "OpenClaw Gateway" >nul 2>&1
if errorlevel 1 (
    schtasks /create /tn "OpenClaw Gateway" /tr "cmd /c openclaw gateway run --force" /sc onlogon /delay 0000:30 /rl highest /f
    echo Tarea creada.
) else (
    echo Tarea ya existe.
)
echo.

echo === Bootstrap completado ===
pause



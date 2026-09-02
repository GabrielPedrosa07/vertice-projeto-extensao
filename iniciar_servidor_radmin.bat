@echo off
chcp 65001 >nul
title VÉRTICE — Servidor Acadêmico (Radmin VPN & Rede Local)
color 0B

echo ===============================================================================
echo                VÉRTICE — PLATAFORMA DE ESTUDOS E COMUNICAÇÃO
echo                 Projeto de Extensão ADS - Faculdade Santa Marcelina
echo ===============================================================================
echo.

:: Detecta IP do Radmin VPN via PowerShell
for /f "usebackq tokens=*" %%i in (`powershell -NoProfile -Command "(Get-NetIPAddress -InterfaceAlias '*Radmin*' -AddressFamily IPv4 -ErrorAction SilentlyContinue | Select-Object -ExpandProperty IPAddress -First 1)"`) do set RADMIN_IP=%%i

:: Detecta IP da Rede Local / Wi-Fi via PowerShell
for /f "usebackq tokens=*" %%i in (`powershell -NoProfile -Command "(Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue | Where-Object { $_.IPAddress -notmatch '^(127\.|169\.254\.|26\.)' } | Select-Object -ExpandProperty IPAddress -First 1)"`) do set LAN_IP=%%i

:: Inicia o Radmin VPN se não estiver aberto
powershell -NoProfile -Command "if (!(Get-Process 'rvpn*' -ErrorAction SilentlyContinue)) { if (Test-Path 'C:\Program Files (x86)\Radmin VPN\rvpn.exe') { Start-Process 'C:\Program Files (x86)\Radmin VPN\rvpn.exe' } elseif (Test-Path 'C:\Program Files\Radmin VPN\rvpn.exe') { Start-Process 'C:\Program Files\Radmin VPN\rvpn.exe' } }" >nul 2>&1

:: Garante as tabelas e contas de teste prontas
php -r "require 'app/Models/Database.php'; require 'app/Models/User.php'; App\Models\Database::getConnection(); if (!App\Models\User::findByEmail('gabriel@fasm.edu.br')) { App\Models\User::create(['name'=>'Gabriel Pedrosa','email'=>'gabriel@fasm.edu.br','password'=>'vertice123','role'=>'admin']); } if (!App\Models\User::findByEmail('aluno@fasm.edu.br')) { App\Models\User::create(['name'=>'Aluno FASM','email'=>'aluno@fasm.edu.br','password'=>'vertice123','role'=>'student']); }" >nul 2>&1

echo [*] SERVIDOR PRONTO PARA CONEXÕES EXTERNAS!
echo.
echo -------------------------------------------------------------------------------
echo  [+] ACESSO NO SEU COMPUTADOR (Localhost):
echo      -> http://localhost:8000
echo.

if defined RADMIN_IP (
    echo  [+] ACESSO PARA AMIGOS VIA RADMIN VPN:
    echo      -> http://%RADMIN_IP%:8000
) else (
    echo  [-] Radmin VPN não detectado. Inicie sua rede no Radmin VPN.
)
echo.

if defined LAN_IP (
    echo  [+] ACESSO NA MESMA REDE WI-FI / FACULDADE (Sem VPN):
    echo      -> http://%LAN_IP%:8000
)
echo.

echo -------------------------------------------------------------------------------
echo  [i] CONTAS DE TESTE PRONTAS PARA LOGIN:
echo      • Admin:  gabriel@fasm.edu.br  ^|  Senha: vertice123
echo      • Aluno:  aluno@fasm.edu.br    ^|  Senha: vertice123
echo -------------------------------------------------------------------------------
echo.
echo [*] Abrindo o navegador em http://localhost:8000 ...
start http://localhost:8000
echo.
echo [*] Servidor PHP rodando em 0.0.0.0:8000 (Pressione Ctrl+C para encerrar).
echo.

php -S 0.0.0.0:8000
pause

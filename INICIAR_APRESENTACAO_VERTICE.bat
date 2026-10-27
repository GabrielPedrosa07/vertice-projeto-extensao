@echo off
chcp 65001 >nul
title VERTICE - Servidor de Apresentacao XXIV EnIC FASM 2026
color 0B

echo ============================================================================
echo    VERTICE - PLATAFORMA DE ESTUDOS E COMUNICACAO EM PHP PURO
echo    XXIV EnIC FASM 2026 - Modo Apresentacao Oficial
echo ============================================================================
echo.

cd /d "%~dp0"

echo [1/3] Verificando Banco de Dados MySQL...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo       [OK] MySQL ja esta rodando!
) else (
    if exist "C:\wamp64\bin\mysql\mysql9.1.0\bin\mysqld.exe" (
        echo       Iniciando MySQL do WampServer...
        start "" /B "C:\wamp64\bin\mysql\mysql9.1.0\bin\mysqld.exe" --defaults-file="C:\wamp64\bin\mysql\mysql9.1.0\my.ini"
        timeout /t 3 /nobreak >nul
        echo       [OK] MySQL iniciado com sucesso!
    ) else (
        echo       [AVISO] Abra o WampServer caso o MySQL nao esteja ativo.
    )
)

echo.
echo ============================================================================
echo    CONTAS PRONTAS PARA DEMONSTRACAO (SENHA DE TODAS: 123456)
echo ============================================================================
echo    1. Gabriel (Admin):   gabriel@fasm.edu.br           ^| Senha: 123456
echo    2. Gabriel (Outlook): gabrielmodestudo@outlook.com  ^| Senha: 123456
echo    3. Iuri Schelb:       iuri@fasm.edu.br              ^| Senha: 123456
echo    4. Joao:              joao@fasm.edu.br              ^| Senha: 123456
echo    5. Aluno Teste:       aluno@fasm.edu.br             ^| Senha: 123456
echo    6. Prof. Aldecir:     aldecir@fasm.edu.br           ^| Senha: 123456
echo ============================================================================
echo.
echo [2/3] Abrindo navegador em http://localhost:8000/login ...
start "" "http://localhost:8000/login"

echo [3/3] Servidor PHP ativo na porta 8000 (Nao feche esta janela durante a apresentacao!)
echo.
php -S 0.0.0.0:8000 index.php
pause

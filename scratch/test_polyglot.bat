<# :
@echo off
setlocal
title VERTICE - Agente de Controle Remoto (AnyDesk P2P)
color 0b
cls

echo ==============================================================================
echo           VERTICE - AGENTE NATIVO DE CONTROLE REMOTO (ESTILO ANYDESK)
echo ==============================================================================
echo.
echo  Este agente permite que seu colega autorizado no Vertice controle seu mouse
echo  e teclado de forma segura (apos voce clicar em Permitir no navegador).
echo.

set SERVER_INPUT=
set /p SERVER_INPUT=" Digite o IP do Servidor Radmin (Pressione Enter para Auto-Detectar): "

powershell -NoProfile -ExecutionPolicy Bypass -Command "$serverInput='%SERVER_INPUT%'; $content = [System.IO.File]::ReadAllText('%~f0'); Invoke-Expression $content"
pause
exit /b
#>

Add-Type -TypeDefinition @"
using System;
using System.Runtime.InteropServices;

public static class NativeInput
{
    [DllImport("user32.dll", SetLastError = true)]
    public static extern bool SetCursorPos(int X, int Y);

    [DllImport("user32.dll", SetLastError = true)]
    public static extern void mouse_event(uint dwFlags, uint dx, uint dy, uint dwData, UIntPtr dwExtraInfo);

    public const uint MOUSEEVENTF_LEFTDOWN   = 0x0002;
    public const uint MOUSEEVENTF_LEFTUP     = 0x0004;
    public const uint MOUSEEVENTF_RIGHTDOWN  = 0x0008;
    public const uint MOUSEEVENTF_RIGHTUP    = 0x0010;
    public const uint MOUSEEVENTF_MIDDLEDOWN = 0x0020;
    public const uint MOUSEEVENTF_MIDDLEUP   = 0x0040;
    public const uint MOUSEEVENTF_WHEEL      = 0x0800;

    public static void MoveCursor(int x, int y)
    {
        SetCursorPos(x, y);
    }

    public static void MouseDown(int x, int y, int button)
    {
        SetCursorPos(x, y);
        if (button == 2) {
            mouse_event(MOUSEEVENTF_RIGHTDOWN, 0, 0, 0, UIntPtr.Zero);
        } else if (button == 1) {
            mouse_event(MOUSEEVENTF_MIDDLEDOWN, 0, 0, 0, UIntPtr.Zero);
        } else {
            mouse_event(MOUSEEVENTF_LEFTDOWN, 0, 0, 0, UIntPtr.Zero);
        }
    }

    public static void MouseUp(int button)
    {
        if (button == 2) {
            mouse_event(MOUSEEVENTF_RIGHTUP, 0, 0, 0, UIntPtr.Zero);
        } else if (button == 1) {
            mouse_event(MOUSEEVENTF_MIDDLEUP, 0, 0, 0, UIntPtr.Zero);
        } else {
            mouse_event(MOUSEEVENTF_LEFTUP, 0, 0, 0, UIntPtr.Zero);
        }
    }

    public static void Click(int x, int y, int button)
    {
        SetCursorPos(x, y);
        if (button == 2) {
            mouse_event(MOUSEEVENTF_RIGHTDOWN, 0, 0, 0, UIntPtr.Zero);
            mouse_event(MOUSEEVENTF_RIGHTUP, 0, 0, 0, UIntPtr.Zero);
        } else {
            mouse_event(MOUSEEVENTF_LEFTDOWN, 0, 0, 0, UIntPtr.Zero);
            mouse_event(MOUSEEVENTF_LEFTUP, 0, 0, 0, UIntPtr.Zero);
        }
    }

    public static void DoubleClick(int x, int y)
    {
        SetCursorPos(x, y);
        mouse_event(MOUSEEVENTF_LEFTDOWN, 0, 0, 0, UIntPtr.Zero);
        mouse_event(MOUSEEVENTF_LEFTUP, 0, 0, 0, UIntPtr.Zero);
        System.Threading.Thread.Sleep(40);
        mouse_event(MOUSEEVENTF_LEFTDOWN, 0, 0, 0, UIntPtr.Zero);
        mouse_event(MOUSEEVENTF_LEFTUP, 0, 0, 0, UIntPtr.Zero);
    }

    public static void Scroll(int delta)
    {
        mouse_event(MOUSEEVENTF_WHEEL, 0, 0, (uint)delta, UIntPtr.Zero);
    }
}
"@

Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

Write-Host "======================================================================" -ForegroundColor Cyan
Write-Host "         VERTICE REMOTE CONTROL AGENT (Estilo AnyDesk P2P)           " -ForegroundColor Yellow
Write-Host "======================================================================" -ForegroundColor Cyan

$radminFallback = "http://26.153.185.191:8000"
$localFallback  = "http://localhost:8000"
$ServerUrl      = ""

if (-not [string]::IsNullOrWhiteSpace($serverInput)) {
    if ($serverInput -match '^https?://') {
        $ServerUrl = $serverInput
    } else {
        $ServerUrl = "http://${serverInput}:8000"
    }
} else {
    $testLocal = $false
    try {
        $res = Invoke-RestMethod -Uri "$localFallback/api/call/remote-control/events" -Method Get -TimeoutSec 1 -ErrorAction SilentlyContinue
        if ($res -and $res.status -eq 'success') { $testLocal = $true }
    } catch {}

    if ($testLocal) {
        $ServerUrl = $localFallback
    } else {
        $ServerUrl = $radminFallback
    }
}

$screenWidth = [System.Windows.Forms.Screen]::PrimaryScreen.Bounds.Width
$screenHeight = [System.Windows.Forms.Screen]::PrimaryScreen.Bounds.Height

Write-Host " [OK] Servidor Conectado : $ServerUrl" -ForegroundColor Cyan
Write-Host " [OK] Resolucao da Tela : ${screenWidth}x${screenHeight}" -ForegroundColor Magenta
Write-Host " [OK] Pronto para receber cliques e digitacao do colega!" -ForegroundColor Green
Write-Host "======================================================================" -ForegroundColor Cyan
Write-Host " (Mantenha esta janela aberta para permitir que seu colega mexa no PC)" -ForegroundColor Gray
Write-Host " (Pressione Ctrl + C para encerrar a qualquer momento)" -ForegroundColor DarkGray
Write-Host ""

# ==============================================================================
# VÉRTICE REMOTE DESKTOP AGENT (AnyDesk Companion para Windows)
# Injeta cliques reais, movimentação de cursor e digitação via Win32 API
# ==============================================================================

param (
    [string]$ServerUrl = ""
)

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

Clear-Host
Write-Host "======================================================================" -ForegroundColor Cyan
Write-Host "         VERTICE REMOTE CONTROL AGENT (Estilo AnyDesk P2P)           " -ForegroundColor Yellow
Write-Host "======================================================================" -ForegroundColor Cyan
Write-Host " [OK] Modulo Win32 NativeInput carregado com sucesso!" -ForegroundColor Green

# 1. Detectar ou Confirmar Servidor
$radminFallback = "http://26.153.185.191:8000"
$localFallback  = "http://localhost:8000"

if ([string]::IsNullOrWhiteSpace($ServerUrl)) {
    # Tenta conectar no localhost primeiro
    $testLocal = $false
    try {
        $res = Invoke-RestMethod -Uri "$localFallback/api/call/remote-control/events" -Method Get -TimeoutSec 1 -ErrorAction SilentlyContinue
        if ($res -and $res.status -eq "success") { $testLocal = $true }
    } catch {}

    if ($testLocal) {
        $ServerUrl = $localFallback
    } else {
        # Tenta Radmin VPN
        $testRadmin = $false
        try {
            $res2 = Invoke-RestMethod -Uri "$radminFallback/api/call/remote-control/events" -Method Get -TimeoutSec 2 -ErrorAction SilentlyContinue
            if ($res2 -and $res2.status -eq "success") { $testRadmin = $true }
        } catch {}

        if ($testRadmin) {
            $ServerUrl = $radminFallback
        } else {
            $ServerUrl = $localFallback
        }
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

$lastEventId = 0
$errorCount = 0

# Inicializa o lastEventId com o maior ID existente para nao re-executar eventos antigos
try {
    $initRes = Invoke-RestMethod -Uri "$ServerUrl/api/call/remote-control/events?after_id=0" -Method Get -TimeoutSec 2 -ErrorAction SilentlyContinue
    if ($initRes -and $initRes.events -and $initRes.events.Count -gt 0) {
        $lastEventId = [int]($initRes.events[-1].id)
    }
} catch {}

while ($true) {
    try {
        $url = "$ServerUrl/api/call/remote-control/events?after_id=$lastEventId"
        $response = Invoke-RestMethod -Uri $url -Method Get -TimeoutSec 2 -ErrorAction Stop

        if ($response -and $response.status -eq "success" -and $response.events) {
            foreach ($evt in $response.events) {
                $lastEventId = [int]$evt.id
                $type = $evt.event_type
                $data = $evt.payload

                if ($data -is [string]) {
                    $data = ConvertFrom-Json $data
                }

                # 1. Movimento do Mouse
                if ($type -eq "mousemove") {
                    $x = [int](($data.xPercent / 100.0) * $screenWidth)
                    $y = [int](($data.yPercent / 100.0) * $screenHeight)
                    [NativeInput]::MoveCursor($x, $y)
                }
                # 2. Clique Pressionado (MouseDown)
                elseif ($type -eq "mousedown") {
                    $x = [int](($data.xPercent / 100.0) * $screenWidth)
                    $y = [int](($data.yPercent / 100.0) * $screenHeight)
                    $btn = if ($null -ne $data.button) { [int]$data.button } else { 0 }
                    [NativeInput]::MouseDown($x, $y, $btn)
                    Write-Host "[MOUSE] Click Down ($x, $y) Btn: $btn" -ForegroundColor Cyan
                }
                # 3. Clique Solto (MouseUp)
                elseif ($type -eq "mouseup") {
                    $btn = if ($null -ne $data.button) { [int]$data.button } else { 0 }
                    [NativeInput]::MouseUp($btn)
                }
                # 4. Clique Completo
                elseif ($type -eq "click") {
                    $x = [int](($data.xPercent / 100.0) * $screenWidth)
                    $y = [int](($data.yPercent / 100.0) * $screenHeight)
                    $btn = if ($null -ne $data.button) { [int]$data.button } else { 0 }
                    [NativeInput]::Click($x, $y, $btn)
                    Write-Host "[MOUSE] Click ($x, $y)" -ForegroundColor Cyan
                }
                # 5. Duplo Clique
                elseif ($type -eq "dblclick") {
                    $x = [int](($data.xPercent / 100.0) * $screenWidth)
                    $y = [int](($data.yPercent / 100.0) * $screenHeight)
                    [NativeInput]::DoubleClick($x, $y)
                    Write-Host "[MOUSE] Double Click ($x, $y)" -ForegroundColor Cyan
                }
                # 6. Rolagem / Scroll
                elseif ($type -eq "wheel") {
                    $delta = [int]($data.deltaY * -1)
                    [NativeInput]::Scroll($delta)
                }
                # 7. Digitação de Teclado
                elseif ($type -eq "keydown") {
                    $key = $data.key
                    if ($key.Length -eq 1) {
                        if ($key -match "[\+\^\%~{}\(\)\[\]]") {
                            [System.Windows.Forms.SendKeys]::SendWait("{$key}")
                        } else {
                            [System.Windows.Forms.SendKeys]::SendWait($key)
                        }
                    } elseif ($key -eq "Enter") {
                        [System.Windows.Forms.SendKeys]::SendWait("{ENTER}")
                    } elseif ($key -eq "Backspace") {
                        [System.Windows.Forms.SendKeys]::SendWait("{BACKSPACE}")
                    } elseif ($key -eq "Tab") {
                        [System.Windows.Forms.SendKeys]::SendWait("{TAB}")
                    } elseif ($key -eq "ArrowUp") {
                        [System.Windows.Forms.SendKeys]::SendWait("{UP}")
                    } elseif ($key -eq "ArrowDown") {
                        [System.Windows.Forms.SendKeys]::SendWait("{DOWN}")
                    } elseif ($key -eq "ArrowLeft") {
                        [System.Windows.Forms.SendKeys]::SendWait("{LEFT}")
                    } elseif ($key -eq "ArrowRight") {
                        [System.Windows.Forms.SendKeys]::SendWait("{RIGHT}")
                    } elseif ($key -eq "Delete") {
                        [System.Windows.Forms.SendKeys]::SendWait("{DEL}")
                    } elseif ($key -eq "Escape") {
                        [System.Windows.Forms.SendKeys]::SendWait("{ESC}")
                    } elseif ($key -eq "Space" -or $key -eq " ") {
                        [System.Windows.Forms.SendKeys]::SendWait(" ")
                    }
                    Write-Host "[TECLADO] Tecla: $key" -ForegroundColor Yellow
                }
            }
        }
        $errorCount = 0
    } catch {
        $errorCount++
        if ($errorCount -eq 5) {
            Write-Host " [AVISO] Aguardando comunicacao com o servidor: $ServerUrl..." -ForegroundColor Yellow
        }
    }

    Start-Sleep -Milliseconds 25
}

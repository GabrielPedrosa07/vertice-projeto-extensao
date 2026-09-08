# Vértice — Configurador de Permissões do Google Chrome / Edge
$targetUrl = "http://26.153.185.191:8000"
Set-Clipboard -Value $targetUrl

Clear-Host
Write-Host "===================================================================" -ForegroundColor Cyan
Write-Host "          VERTICE - LIBERAR MICROFONE, CAMERA E TELA" -ForegroundColor Yellow
Write-Host "          Endereco: $targetUrl" -ForegroundColor White
Write-Host "===================================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "[1] Abrir o Vertice no Chrome com TUDO LIBERADO automaticamente" -ForegroundColor Green
Write-Host "    (Recomendado: abre uma janela do Chrome com as flags ja ativas!)" -ForegroundColor Gray
Write-Host ""
Write-Host "[2] Fechar o Chrome e abrir direto na pagina 'chrome://flags'" -ForegroundColor Yellow
Write-Host "    (Para configurar permanentemente no seu perfil pessoal)" -ForegroundColor Gray
Write-Host ""
Write-Host "===================================================================" -ForegroundColor Cyan

$opcao = Read-Host "Escolha a opcao [1 ou 2, padrao=1]"
if ($opcao -ne "2") {
    Write-Host "`n[1/2] Endereco $targetUrl copiado para sua Area de Transferencia!" -ForegroundColor Green
    Write-Host "[2/2] Abrindo janela do Chrome com flags ativadas..." -ForegroundColor Green
    $flags = "--unsafely-treat-insecure-origin-as-secure=`"$targetUrl,http://localhost:8000`" --enable-features=CapturedSurfaceControl,WebRtcHideLocalIpsWithMdns,DocumentPictureInPictureAPI --user-data-dir=`"$env:TEMP\VerticeChrome`" `"$targetUrl`""
    try {
        Start-Process "chrome.exe" -ArgumentList $flags -ErrorAction Stop
    } catch {
        Start-Process "msedge.exe" -ArgumentList $flags
    }
    Write-Host "`n[OK] Chrome aberto com sucesso com todas as permissoes liberadas!" -ForegroundColor Green
    Start-Sleep -Seconds 3
    exit 0
} else {
    Write-Host "`n[1/3] Fechando processos abertos do Chrome para liberar acesso a flags..." -ForegroundColor Yellow
    Stop-Process -Name "chrome" -Force -ErrorAction SilentlyContinue
    Stop-Process -Name "msedge" -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 1
    Write-Host "[2/3] Endereco $targetUrl copiado para sua Area de Transferencia!" -ForegroundColor Green
    Write-Host "[3/3] Abrindo o Chrome diretamente na pagina de Flags..." -ForegroundColor Green
    
    $flagsUrl = "chrome://flags/#unsafely-treat-insecure-origin-as-secure"
    try {
        Start-Process "chrome.exe" -ArgumentList "`"$flagsUrl`" `"$targetUrl`"" -ErrorAction Stop
    } catch {
        Start-Process "msedge.exe" -ArgumentList "`"edge://flags/#unsafely-treat-insecure-origin-as-secure`" `"$targetUrl`""
    }
    
    Write-Host "`n===================================================================" -ForegroundColor Cyan
    Write-Host "                      PASSO A PASSO NO CHROME:" -ForegroundColor Yellow
    Write-Host "===================================================================" -ForegroundColor Cyan
    Write-Host "1. Na tela amarela de flags, clique no campo de texto e aperte CTRL + V"
    Write-Host "   (O endereco $targetUrl ja foi copiado!)" -ForegroundColor Yellow
    Write-Host "2. Mude a opcao de 'Default' para 'Enabled'" -ForegroundColor Yellow
    Write-Host "3. Clique no botao azul 'Relaunch' (Reiniciar) no canto inferior." -ForegroundColor Green
    Write-Host "===================================================================" -ForegroundColor Cyan
    Read-Host "`nPressione Enter para sair..."
    exit 0
}

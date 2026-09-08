<?php
/**
 * Teste de Verificação: Criação de Servidores, Notificações Globais, Remoção de IP e Batch
 */

$cookieFile = __DIR__ . '/test_cookie.txt';
@unlink($cookieFile);

function request($url, $method = 'GET', $data = [], $isJson = false) {
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($isJson) {
            $json = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($json)
            ]);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }

    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

echo "=== INICIANDO TESTES DOS NOVOS RECURSOS ===\n";

// 1. Login com Gabriel
$login = request('http://127.0.0.1:8000/entrar', 'POST', [
    'email' => 'gabriel@fasm.edu.br',
    'password' => 'vertice123'
]);
echo "1. Login: {$login['code']}\n";

// 2. Verificar Remoção do IP Acidental do Header e Hub
$hubPage = request('http://127.0.0.1:8000/hub');
$hasIpInHub = strpos($hubPage['body'], '26.153.185.191') !== false;
echo "2. IP 26.153.185.191 no Hub/Header: " . ($hasIpInHub ? "ALERTA (AINDA PRESENTE)" : "100% REMOVIDO COM SUCESSO!") . "\n";

// 3. Testar Criação de Novo Servidor Personalizado
$srvName = "Grupo TCC Automação " . rand(100, 999);
$createSrv = request('http://127.0.0.1:8000/api/communities/create', 'POST', [
    'name' => $srvName,
    'description' => 'Servidor de testes criado para o TCC',
    'icon' => '🤖',
    'color' => '#8b5cf6'
], true);
echo "3. Criar Servidor: {$createSrv['code']} -> {$createSrv['body']}\n";
$srvData = json_decode($createSrv['body'], true);
$srvSlug = $srvData['community']['slug'] ?? '';

// 4. Acessar o Novo Servidor Criado
if ($srvSlug) {
    $srvPage = request("http://127.0.0.1:8000/comunidade/{$srvSlug}");
    echo "4. Acessar Servidor Criado (/comunidade/{$srvSlug}): {$srvPage['code']} | Tem Canais: " . (strpos($srvPage['body'], '#geral') !== false ? 'SIM' : 'NÃO') . "\n";
}

// 5. Testar Endpoint de Checagem de Notificações
$notifRes = request('http://127.0.0.1:8000/api/notifications/check');
echo "5. API Notificações: {$notifRes['code']} -> {$notifRes['body']}\n";

// 6. Testar Download do .BAT Atualizado
$batRes = request('http://127.0.0.1:8000/download/configurar-chrome');
echo "6. Download .bat: {$batRes['code']} | Tamanho: " . strlen($batRes['body']) . " bytes | Powershell Bypass: " . (strpos($batRes['body'], 'ExecutionPolicy Bypass') !== false ? 'SIM' : 'NÃO') . "\n";

@unlink($cookieFile);
echo "=== TESTES CONCLUÍDOS COM SUCESSO! ===\n";

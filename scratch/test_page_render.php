<?php
$cookieFile = __DIR__ . '/test_cookie.txt';
@unlink($cookieFile);

// 1. Login
$ch = curl_init('http://127.0.0.1:8000/entrar');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'email' => 'gabriel@fasm.edu.br',
    'password' => 'vertice123'
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_exec($ch);
curl_close($ch);

// 2. Fetch page with follow location
$ch = curl_init('http://127.0.0.1:8000/comunidade/banco-de-dados');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

echo "Final Status: {$code} | Effective URL: {$effectiveUrl} | Length: " . strlen($res) . " bytes\n";
echo "Has Live Code: " . (strpos($res, 'live-code-editor') !== false ? 'YES' : 'NO') . "\n";
echo "Has SQL Playground: " . (strpos($res, 'sql-query-input') !== false ? 'YES' : 'NO') . "\n";
echo "Has Whiteboard: " . (strpos($res, 'whiteboard-canvas') !== false ? 'YES' : 'NO') . "\n";
echo "Has Ace CDN: " . (strpos($res, 'ace.js') !== false ? 'YES' : 'NO') . "\n";

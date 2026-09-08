<?php
$cookieFile = __DIR__ . '/test_cookie.txt';
@unlink($cookieFile);

function req($url, $method = 'GET', $data = null, $isJson = false) {
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($isJson) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }

    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $res];
}

// 1. Login
$login = req('http://127.0.0.1:8000/entrar', 'POST', [
    'email' => 'gabriel@fasm.edu.br',
    'password' => 'vertice123'
]);
echo "1. Login status: {$login['code']}\n";

// 2. Code Get
$codeGet = req('http://127.0.0.1:8000/api/tools/code/get?channel_id=1');
echo "2. Code Get [{$codeGet['code']}]: " . substr($codeGet['body'], 0, 90) . "...\n";

// 3. Code Run (PHP)
$codeRun = req('http://127.0.0.1:8000/api/tools/code/run', 'POST', [
    'language' => 'php',
    'code' => '<?php echo "Live Code executado no servidor! " . (20 + 26);'
], true);
echo "3. Code Run [{$codeRun['code']}]: {$codeRun['body']}\n";

// 4. SQL Exec
$sql = req('http://127.0.0.1:8000/api/tools/sql/execute', 'POST', [
    'query' => 'SELECT COUNT(*) as total_alunos FROM alunos;'
], true);
echo "4. SQL Exec [{$sql['code']}]: {$sql['body']}\n";

// 5. Whiteboard Save & Get
$wbSave = req('http://127.0.0.1:8000/api/tools/whiteboard/save', 'POST', [
    'channel_id' => 1,
    'shape_data' => ['type' => 'rect', 'x' => 100, 'y' => 150, 'w' => 80, 'h' => 40, 'color' => '#4ecdc4']
], true);
echo "5. Whiteboard Save [{$wbSave['code']}]: {$wbSave['body']}\n";

$wbGet = req('http://127.0.0.1:8000/api/tools/whiteboard/get?channel_id=1');
echo "6. Whiteboard Get [{$wbGet['code']}]: " . substr($wbGet['body'], 0, 100) . "...\n";

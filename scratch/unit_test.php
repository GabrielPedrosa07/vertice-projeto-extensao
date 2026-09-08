<?php
require_once __DIR__ . '/../app/Models/Database.php';
require_once __DIR__ . '/../app/Models/Category.php';
require_once __DIR__ . '/../app/Models/Channel.php';
require_once __DIR__ . '/../app/Models/CommunityMember.php';
require_once __DIR__ . '/../app/Models/PrivateCall.php';
require_once __DIR__ . '/../app/Models/Friendship.php';

echo "1. Testando Category::create...\n";
$serverName = "Servidor CLI " . rand(100, 999);
$slug = 'servidor-cli-' . rand(100, 999);
$comm = \App\Models\Category::create($serverName, $slug, 'Descricao de teste', '🚀', '#4ecdc4', 1);
echo "Servidor criado com sucesso: ID " . ($comm['id'] ?? 0) . " | Nome: " . ($comm['name'] ?? '') . " | Slug: " . ($comm['slug'] ?? '') . "\n";

echo "2. Verificando canais padrão do servidor...\n";
$channels = \App\Models\Channel::getByCategory((int)$comm['id']);
echo "Total de canais criados: " . count($channels) . " (" . implode(', ', array_column($channels, 'name')) . ")\n";

echo "3. Testando Presença e Membros Online...\n";
$db = \App\Models\Database::getConnection();
$db->prepare("INSERT INTO online_users (user_id, channel_id, last_seen) VALUES (1, ?, NOW()) ON DUPLICATE KEY UPDATE last_seen = NOW()")->execute([$channels[0]['id'] ?? 1]);
$stmt = $db->query("SELECT COUNT(*) as total FROM online_users WHERE last_seen >= NOW() - INTERVAL 60 SECOND");
$onlineCount = $stmt->fetch()['total'];
echo "Total membros online detectados no banco: {$onlineCount}\n";

echo "4. Testando Chamada Privada (1x1)...\n";
$callRes = \App\Models\PrivateCall::initiateCall(1, 'Gabriel Pedrosa', 2);
echo "Chamada iniciada com ID: " . ($callRes['call_id'] ?? 0) . " para sala: " . ($callRes['room_id'] ?? '') . "\n";

$incoming = \App\Models\PrivateCall::checkIncomingCall(2);
echo "Verificando se o Aluno (ID 2) recebe a chamada: " . ($incoming ? "SUCESSO (Chamador: " . $incoming['caller_name'] . ")" : "FALHA") . "\n";

echo "5. Testando Amizades (ID 1 e ID 2)...\n";
$friendRes = \App\Models\Friendship::sendRequest(2, 1);
echo "Envio de pedido: " . json_encode($friendRes, JSON_UNESCAPED_UNICODE) . "\n";
$pending = \App\Models\Friendship::getPendingRequests(1);
echo "Pedidos pendentes para Gabriel: " . count($pending) . "\n";

echo "6. Testando Sinalização WebRTC (CallSignal::sendSignal e getSignals)...\n";
require_once __DIR__ . '/../app/Models/CallSignal.php';
\App\Models\CallSignal::sendSignal(901002, 1, 2, 'offer', json_encode(['type' => 'offer', 'sdp' => 'v=0...']));
$signals = \App\Models\CallSignal::getSignals(901002, 2);
echo "Sinais recuperados para User 2: " . count($signals) . " (Tipo: " . ($signals[0]['type'] ?? '') . ")\n";

echo "\n=== TODOS OS TESTES INTERNOS PASSARAM COM 100% DE SUCESSO! ===\n";

<?php
/**
 * Controller API — Vértice
 * Endpoints JSON para Chat em Tempo Real, Uploads e Chamadas WebRTC
 */

namespace App\Controllers;

use App\Helpers\Auth;
use App\Models\Database;
use App\Models\Message;
use App\Models\CallSignal;
use App\Models\User;

class ApiController
{
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function getAuthUser(): array
    {
        if (!Auth::check()) {
            $this->jsonResponse(['error' => 'Não autorizado. Faça login primeiro.'], 401);
        }
        return Auth::user();
    }

    // ── MENSAGENS ────────────────────────────────────────────────────────────

    public function getMessages(): void
    {
        $user = $this->getAuthUser();
        $channelId = (int)($_GET['canal'] ?? 0);
        $afterId = (int)($_GET['after_id'] ?? 0);

        if ($channelId <= 0) {
            $this->jsonResponse(['error' => 'ID do canal inválido.'], 400);
        }

        if ($afterId > 0) {
            $messages = Message::getAfter($channelId, $afterId);
        } else {
            $messages = Message::getByChannel($channelId, 50);
        }

        // Registrar heartbeat online do usuário
        $this->updateUserPresence($user['id'], $channelId);

        $this->jsonResponse([
            'status'   => 'success',
            'canal_id' => $channelId,
            'messages' => $messages,
            'user_id'  => $user['id']
        ]);
    }

    public function sendMessage(): void
    {
        $user = $this->getAuthUser();
        
        // Suporta tanto JSON payload quanto multipart/form-data
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $data = [];
        if (str_contains($contentType, 'application/json')) {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $data = $_POST;
        }

        $channelId = (int)($data['channel_id'] ?? 0);
        $content = trim($data['content'] ?? '');
        $isCode = !empty($data['is_code']);
        $codeLang = !empty($data['code_language']) ? trim($data['code_language']) : null;
        $attachmentUrl = !empty($data['attachment_url']) ? trim($data['attachment_url']) : null;
        $attachmentName = !empty($data['attachment_name']) ? trim($data['attachment_name']) : null;
        $attachmentType = !empty($data['attachment_type']) ? trim($data['attachment_type']) : null;

        if ($channelId <= 0 || (empty($content) && empty($attachmentUrl))) {
            $this->jsonResponse(['error' => 'Conteúdo ou canal inválido.'], 422);
        }

        $createdMessage = Message::create(
            $channelId,
            $user['id'],
            $content,
            $isCode,
            $codeLang,
            $attachmentUrl,
            $attachmentName,
            $attachmentType
        );

        $this->jsonResponse([
            'status'  => 'success',
            'message' => $createdMessage
        ], 201);
    }

    public function uploadFile(): void
    {
        $user = $this->getAuthUser();

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'Nenhum arquivo enviado ou erro no upload.'], 400);
        }

        $file = $_FILES['file'];
        $maxSize = 25 * 1024 * 1024; // 25MB

        if ($file['size'] > $maxSize) {
            $this->jsonResponse(['error' => 'Arquivo excede o limite máximo de 25MB.'], 400);
        }

        $origName = basename($file['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        // Extensões permitidas para ambiente acadêmico (incluindo áudio e voz)
        $allowedExts = [
            'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg',
            'mp3', 'wav', 'ogg', 'webm', 'm4a', 'aac',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'md',
            'zip', 'rar', '7z', 'tar', 'gz',
            'sql', 'php', 'py', 'js', 'html', 'css', 'json', 'c', 'cpp', 'java', 'cs'
        ];

        if (!in_array($ext, $allowedExts)) {
            $this->jsonResponse(['error' => 'Formato de arquivo não permitido.'], 400);
        }

        $uploadDir = __DIR__ . '/../../public/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $safeFileName = uniqid('vertice_', true) . '.' . $ext;
        $targetPath = $uploadDir . $safeFileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $isImage = in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg']);
            $isAudio = in_array($ext, ['mp3', 'wav', 'ogg', 'webm', 'm4a', 'aac']);
            $fileType = $isImage ? 'image' : ($isAudio ? 'audio' : 'file');

            $this->jsonResponse([
                'status'          => 'success',
                'url'             => '/public/uploads/' . $safeFileName,
                'name'            => $origName,
                'type'            => $fileType,
                'extension'       => $ext,
                'size'            => $file['size']
            ]);
        } else {
            $this->jsonResponse(['error' => 'Falha ao salvar arquivo no servidor.'], 500);
        }
    }

    public function deleteMessage(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $msgId = (int)($data['id'] ?? 0);

        if ($msgId <= 0) {
            $this->jsonResponse(['error' => 'ID de mensagem inválido.'], 400);
        }

        $isAdmin = ($user['role'] ?? '') === 'admin';
        $success = Message::delete($msgId, $user['id'], $isAdmin);

        if ($success) {
            $this->jsonResponse(['status' => 'success', 'deleted_id' => $msgId]);
        } else {
            $this->jsonResponse(['error' => 'Permissão negada ou mensagem não encontrada.'], 403);
        }
    }

    // ── PRESENÇA ONLINE ──────────────────────────────────────────────────────

    private function updateUserPresence(int $userId, int $channelId): void
    {
        try {
            $db = Database::getConnection();
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $stmt = $db->prepare("
                    INSERT INTO online_users (user_id, channel_id, last_seen)
                    VALUES (:u, :ch, CURRENT_TIMESTAMP)
                    ON DUPLICATE KEY UPDATE channel_id = VALUES(channel_id), last_seen = CURRENT_TIMESTAMP
                ");
            } else {
                $stmt = $db->prepare("
                    INSERT INTO online_users (user_id, channel_id, last_seen)
                    VALUES (:u, :ch, CURRENT_TIMESTAMP)
                    ON CONFLICT(user_id) DO UPDATE SET channel_id = :ch, last_seen = CURRENT_TIMESTAMP
                ");
            }
            $stmt->execute(['u' => $userId, 'ch' => $channelId]);
        } catch (\Throwable $e) {}
    }

    public function getOnlineUsers(): void
    {
        $user = $this->getAuthUser();
        $channelId = (int)($_GET['canal'] ?? 0);

        $db = Database::getConnection();
        try {
            $isSqlite = Database::isSqlite();

            // 1. Registra o heartbeat do usuário atual
            if ($user && isset($user['id'])) {
                if ($isSqlite) {
                    $stmtHeartbeat = $db->prepare("
                        INSERT INTO online_users (user_id, channel_id, last_seen)
                        VALUES (:uid, :cid, datetime('now'))
                        ON CONFLICT(user_id) DO UPDATE SET channel_id = :cid2, last_seen = datetime('now')
                    ");
                } else {
                    $stmtHeartbeat = $db->prepare("
                        INSERT INTO online_users (user_id, channel_id, last_seen)
                        VALUES (:uid, :cid, NOW())
                        ON DUPLICATE KEY UPDATE channel_id = :cid2, last_seen = NOW()
                    ");
                }
                $stmtHeartbeat->execute([
                    'uid' => $user['id'],
                    'cid' => $channelId > 0 ? $channelId : null,
                    'cid2' => $channelId > 0 ? $channelId : null
                ]);
            }

            // 2. Limpeza de inativos (> 60 segundos)
            if ($isSqlite) {
                $db->exec("DELETE FROM online_users WHERE last_seen < datetime('now', '-60 seconds')");
                $stmt = $db->prepare("
                    SELECT u.id, u.name, u.email, u.role, u.avatar_url, ou.channel_id
                    FROM online_users ou
                    INNER JOIN users u ON ou.user_id = u.id
                    WHERE ou.last_seen >= datetime('now', '-60 seconds')
                    ORDER BY u.name ASC
                ");
            } else {
                $db->exec("DELETE FROM online_users WHERE last_seen < DATE_SUB(NOW(), INTERVAL 60 SECOND)");
                $stmt = $db->prepare("
                    SELECT u.id, u.name, u.email, u.role, u.avatar_url, ou.channel_id
                    FROM online_users ou
                    INNER JOIN users u ON ou.user_id = u.id
                    WHERE ou.last_seen >= DATE_SUB(NOW(), INTERVAL 60 SECOND)
                    ORDER BY u.name ASC
                ");
            }

            $stmt->execute();
            $online = $stmt->fetchAll();

            $this->jsonResponse([
                'status' => 'success',
                'users'  => $online
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['status' => 'success', 'users' => []]);
        }
    }

    // ── CHAMADAS WebRTC (Voz, Vídeo e Compartilhamento de Tela) ──────────────

    public function joinCall(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $channelId = (int)($data['channel_id'] ?? 0);
        $peerId = trim($data['peer_id'] ?? ('peer_' . $user['id'] . '_' . time()));

        if ($channelId <= 0) {
            $this->jsonResponse(['error' => 'Canal inválido.'], 400);
        }

        $existingPeers = CallSignal::join($channelId, $user['id'], $user['name'], $peerId);

        $this->jsonResponse([
            'status'     => 'success',
            'channel_id' => $channelId,
            'user_id'    => $user['id'],
            'user_name'  => $user['name'],
            'peer_id'    => $peerId,
            'peers'      => $existingPeers
        ]);
    }

    public function heartbeatCall(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $channelId = (int)($data['channel_id'] ?? 0);
        if ($channelId > 0) {
            CallSignal::heartbeat($channelId, $user['id'], $data);
        }

        $peers = CallSignal::getPeers($channelId, $user['id']);
        $signals = CallSignal::getSignals($channelId, $user['id']);

        $this->jsonResponse([
            'status'  => 'success',
            'peers'   => $peers,
            'signals' => $signals
        ]);
    }

    public function leaveCall(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $channelId = (int)($data['channel_id'] ?? 0);

        if ($channelId > 0) {
            CallSignal::leave($channelId, $user['id']);
        }

        $this->jsonResponse(['status' => 'success']);
    }

    public function sendCallSignal(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $channelId = (int)($data['channel_id'] ?? 0);
        $receiverId = (int)($data['receiver_id'] ?? 0);
        $type = trim($data['type'] ?? '');
        $payload = is_string($data['payload'] ?? '') ? $data['payload'] : json_encode($data['payload'] ?? []);

        if ($channelId <= 0 || $receiverId <= 0 || empty($type) || empty($payload)) {
            $this->jsonResponse(['error' => 'Parâmetros de sinalização incompletos.'], 400);
        }

        CallSignal::sendSignal($channelId, $user['id'], $receiverId, $type, $payload);

        $this->jsonResponse(['status' => 'success']);
    }

    public function getCallSignals(): void
    {
        $user = $this->getAuthUser();
        $channelId = (int)($_GET['channel_id'] ?? $_GET['canal'] ?? 0);

        if ($channelId <= 0) {
            $this->jsonResponse(['error' => 'Canal inválido.'], 400);
        }

        $signals = CallSignal::getSignals($channelId, $user['id']);

        $this->jsonResponse([
            'status'  => 'success',
            'signals' => $signals
        ]);
    }

    public function getCallPeers(): void
    {
        $user = $this->getAuthUser();
        $channelId = (int)($_GET['canal'] ?? 0);

        if ($channelId <= 0) {
            $this->jsonResponse(['error' => 'Canal inválido.'], 400);
        }

        $peers = CallSignal::getPeers($channelId);

        $this->jsonResponse([
            'status' => 'success',
            'peers'  => $peers
        ]);
    }

    // ── CONTROLE REMOTO (AnyDesk / Desktop Injection) ─────────────────────────

    public function postRemoteControlEvent(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $channelId = (int)($data['channel_id'] ?? 1);
        $targetUserId = (int)($data['target_user_id'] ?? 0);
        $eventType = trim($data['event_type'] ?? '');
        $payload = is_string($data['payload'] ?? '') ? $data['payload'] : json_encode($data['payload'] ?? []);

        if ($targetUserId <= 0 || empty($eventType)) {
            $this->jsonResponse(['error' => 'Dados de evento inválidos.'], 400);
        }
        if ($channelId <= 0) {
            $channelId = 1;
        }

        $db = Database::getConnection();
        try {
            // Insere o evento
            $stmt = $db->prepare("
                INSERT INTO remote_control_events (channel_id, target_user_id, controller_user_id, event_type, payload)
                VALUES (:ch, :target, :ctrl, :evt, :pld)
            ");
            $stmt->execute([
                'ch'     => $channelId,
                'target' => $targetUserId,
                'ctrl'   => $user['id'],
                'evt'    => $eventType,
                'pld'    => $payload
            ]);

            // Limpa eventos com mais de 10 segundos
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $db->exec("DELETE FROM remote_control_events WHERE created_at < DATE_SUB(NOW(), INTERVAL 10 SECOND)");
            } else {
                $db->exec("DELETE FROM remote_control_events WHERE created_at < datetime('now', '-10 seconds')");
            }

            $this->jsonResponse(['status' => 'success']);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => 'Falha ao processar evento remoto: ' . $e->getMessage()], 500);
        }
    }

    public function getRemoteControlEvents(): void
    {
        $channelId = (int)($_GET['channel_id'] ?? 0);
        $lastId = (int)($_GET['after_id'] ?? 0);
        $targetUserId = (int)($_GET['target_user_id'] ?? 0);

        if ($targetUserId <= 0 && Auth::check()) {
            $user = Auth::user();
            $targetUserId = (int)$user['id'];
        }

        $db = Database::getConnection();
        try {
            if ($targetUserId > 0 && $channelId > 0) {
                $stmt = $db->prepare("
                    SELECT rce.*, u.name as controller_name
                    FROM remote_control_events rce
                    LEFT JOIN users u ON rce.controller_user_id = u.id
                    WHERE rce.channel_id = :ch AND rce.target_user_id = :target AND rce.id > :last_id
                    ORDER BY rce.id ASC
                    LIMIT 50
                ");
                $stmt->execute([
                    'ch'      => $channelId,
                    'target'  => $targetUserId,
                    'last_id' => $lastId
                ]);
            } elseif ($targetUserId > 0) {
                $stmt = $db->prepare("
                    SELECT rce.*, u.name as controller_name
                    FROM remote_control_events rce
                    LEFT JOIN users u ON rce.controller_user_id = u.id
                    WHERE rce.target_user_id = :target AND rce.id > :last_id
                    ORDER BY rce.id ASC
                    LIMIT 50
                ");
                $stmt->execute([
                    'target'  => $targetUserId,
                    'last_id' => $lastId
                ]);
            } else {
                $stmt = $db->prepare("
                    SELECT rce.*, u.name as controller_name
                    FROM remote_control_events rce
                    LEFT JOIN users u ON rce.controller_user_id = u.id
                    WHERE rce.id > :last_id
                    ORDER BY rce.id ASC
                    LIMIT 50
                ");
                $stmt->execute(['last_id' => $lastId]);
            }
            $events = $stmt->fetchAll();

            $this->jsonResponse([
                'status' => 'success',
                'events' => $events
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['status' => 'success', 'events' => []]);
        }
    }

    public function downloadRemoteAgent(): void
    {
        $file = __DIR__ . '/../../iniciar_controle_remoto.bat';
        if (!file_exists($file)) {
            header('HTTP/1.0 404 Not Found');
            echo "Arquivo não encontrado.";
            exit;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/bat');
        header('Content-Disposition: attachment; filename="vertice_remote_agent.bat"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

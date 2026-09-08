<?php
/**
 * Controller de Mensagens Privadas (1x1), Amigos e Chamadas Diretas — Vértice
 */

namespace App\Controllers;

use App\Helpers\Auth;
use App\Models\Friendship;
use App\Models\PrivateMessage;
use App\Models\PrivateCall;
use App\Models\User;

class PrivateChatController
{
    private function getAuthUser(): array
    {
        if (!Auth::check()) {
            $this->jsonResponse(['error' => 'Não autorizado. Faça login primeiro.'], 401);
        }
        $user = Auth::user();
        if (!$user) {
            $this->jsonResponse(['error' => 'Não autorizado. Faça login primeiro.'], 401);
        }
        return $user;
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Tela Principal /privado
     */
    public function index(): void
    {
        Auth::requireAuth();
        $user = Auth::user();

        $friends = Friendship::getFriends((int)$user['id']);
        $pending = Friendship::getPendingRequests((int)$user['id']);
        $conversations = PrivateMessage::getRecentConversations((int)$user['id']);

        $activeFriendId = (int)($_GET['amigo'] ?? 0);
        $activeFriend = null;
        if ($activeFriendId > 0) {
            $activeFriend = User::findById($activeFriendId);
        }

        require __DIR__ . '/../../views/hub/privado.php';
    }

    // ── AMIZADES ─────────────────────────────────────────────────────────────

    public function getFriends(): void
    {
        $user = $this->getAuthUser();
        $friends = Friendship::getFriends((int)$user['id']);
        $this->jsonResponse(['status' => 'success', 'friends' => $friends]);
    }

    public function getPendingRequests(): void
    {
        $user = $this->getAuthUser();
        $pending = Friendship::getPendingRequests((int)$user['id']);
        $this->jsonResponse(['status' => 'success', 'requests' => $pending]);
    }

    public function searchUsers(): void
    {
        $user = $this->getAuthUser();
        $query = trim($_GET['q'] ?? '');
        $results = Friendship::searchUsers($query, (int)$user['id']);
        $this->jsonResponse(['status' => 'success', 'users' => $results]);
    }

    public function sendFriendRequest(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        if (!empty($data['email'])) {
            $res = Friendship::sendRequestByEmail((int)$user['id'], $data['email']);
            $this->jsonResponse($res);
        }

        $friendId = (int)($data['friend_id'] ?? 0);
        if ($friendId <= 0) {
            $this->jsonResponse(['error' => 'Usuário inválido.'], 400);
        }

        $res = Friendship::sendRequest((int)$user['id'], $friendId);
        $this->jsonResponse($res);
    }

    public function acceptFriendRequest(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $reqId = (int)($data['request_id'] ?? 0);

        if ($reqId <= 0) $this->jsonResponse(['error' => 'ID inválido.'], 400);

        $ok = Friendship::acceptRequest($reqId, (int)$user['id']);
        $this->jsonResponse(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Amizade aceita!' : 'Não foi possível aceitar.']);
    }

    public function declineFriendRequest(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $reqId = (int)($data['request_id'] ?? 0);

        if ($reqId <= 0) $this->jsonResponse(['error' => 'ID inválido.'], 400);

        $ok = Friendship::declineRequest($reqId, (int)$user['id']);
        $this->jsonResponse(['status' => $ok ? 'success' : 'error']);
    }

    public function removeFriend(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $friendId = (int)($data['friend_id'] ?? 0);

        if ($friendId <= 0) $this->jsonResponse(['error' => 'ID inválido.'], 400);

        $ok = Friendship::removeFriend((int)$user['id'], $friendId);
        $this->jsonResponse(['status' => $ok ? 'success' : 'error']);
    }

    // ── MENSAGENS PRIVADAS ───────────────────────────────────────────────────

    public function getConversations(): void
    {
        $user = $this->getAuthUser();
        $convs = PrivateMessage::getRecentConversations((int)$user['id']);
        $this->jsonResponse(['status' => 'success', 'conversations' => $convs]);
    }

    public function getMessages(): void
    {
        $user = $this->getAuthUser();
        $friendId = (int)($_GET['friend_id'] ?? 0);
        $afterId = (int)($_GET['after_id'] ?? 0);

        if ($friendId <= 0) {
            $this->jsonResponse(['error' => 'Amigo não especificado.'], 400);
        }

        PrivateMessage::markAsRead((int)$user['id'], $friendId);
        $messages = PrivateMessage::getConversation((int)$user['id'], $friendId, $afterId);

        $this->jsonResponse([
            'status' => 'success',
            'messages' => $messages
        ]);
    }

    public function sendMessage(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $receiverId = (int)($data['receiver_id'] ?? 0);
        $content = trim($data['content'] ?? '');

        if ($receiverId <= 0 || (empty($content) && empty($data['attachment_url']))) {
            $this->jsonResponse(['error' => 'Mensagem ou anexo vazio.'], 400);
        }

        $msg = PrivateMessage::sendMessage((int)$user['id'], $receiverId, $content, [
            'is_code'         => !empty($data['is_code']),
            'code_language'   => $data['code_language'] ?? null,
            'attachment_url'  => $data['attachment_url'] ?? null,
            'attachment_name' => $data['attachment_name'] ?? null,
            'attachment_type' => $data['attachment_type'] ?? null,
        ]);

        $this->jsonResponse(['status' => 'success', 'message' => $msg], 201);
    }

    // ── CHAMADAS PRIVADAS (1x1) ──────────────────────────────────────────────

    public function startCall(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $receiverId = (int)($data['receiver_id'] ?? 0);
        if ($receiverId <= 0) {
            $this->jsonResponse(['error' => 'Destinatário da chamada inválido.'], 400);
        }

        $res = PrivateCall::initiateCall((int)$user['id'], $user['name'] ?? 'Colega', $receiverId);
        $this->jsonResponse($res);
    }

    public function checkCall(): void
    {
        $user = $this->getAuthUser();
        $incoming = PrivateCall::checkIncomingCall((int)$user['id']);

        $this->jsonResponse([
            'status' => 'success',
            'incoming_call' => $incoming
        ]);
    }

    public function respondCall(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $callId = (int)($data['call_id'] ?? 0);
        $action = trim($data['action'] ?? 'decline');

        $ok = PrivateCall::respondCall($callId, (int)$user['id'], $action);
        $call = PrivateCall::getCall($callId);

        $this->jsonResponse([
            'status' => $ok ? 'success' : 'error',
            'call' => $call
        ]);
    }

    public function endCall(): void
    {
        $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $callId = (int)($data['call_id'] ?? 0);

        PrivateCall::endCall($callId);
        $this->jsonResponse(['status' => 'success']);
    }

    // ── CONVITES PARA MATÉRIAS ───────────────────────────────────────────────

    public function sendCommunityInvite(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $receiverId = (int)($data['receiver_id'] ?? 0);
        $categoryId = (int)($data['category_id'] ?? 1);
        $channelId = (int)($data['channel_id'] ?? 1);

        if ($receiverId <= 0) {
            $this->jsonResponse(['error' => 'Amigo não selecionado.'], 400);
        }

        $res = PrivateCall::sendCommunityInvite((int)$user['id'], $user['name'] ?? 'Colega', $receiverId, $categoryId, $channelId);
        $this->jsonResponse($res);
    }

    public function getCommunityInvites(): void
    {
        $user = $this->getAuthUser();
        $invites = PrivateCall::getMyInvites((int)$user['id']);
        $this->jsonResponse(['status' => 'success', 'invites' => $invites]);
    }

    public function checkNotifications(): void
    {
        $user = $this->getAuthUser();
        $db = \App\Models\Database::getConnection();

        // Mantém presença ativa do usuário
        try {
            if (\App\Models\Database::isSqlite()) {
                $db->prepare("INSERT INTO online_users (user_id, last_seen) VALUES (:uid, datetime('now')) ON CONFLICT(user_id) DO UPDATE SET last_seen = datetime('now')")->execute(['uid' => $user['id']]);
            } else {
                $db->prepare("INSERT INTO online_users (user_id, last_seen) VALUES (:uid, NOW()) ON DUPLICATE KEY UPDATE last_seen = NOW()")->execute(['uid' => $user['id']]);
            }
        } catch (\Throwable $e) {}

        // 1. Mensagens privadas não lidas
        $stmtPm = $db->prepare("
            SELECT m.*, u.name as sender_name 
            FROM private_messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.receiver_id = :uid AND m.is_read = 0
            ORDER BY m.id DESC
            LIMIT 5
        ");
        $stmtPm->execute(['uid' => $user['id']]);
        $unreadPm = $stmtPm->fetchAll();

        // 2. Novas mensagens em canais das comunidades do usuário
        $timeCondition = \App\Models\Database::isSqlite() ? "m.created_at >= datetime('now', '-15 seconds')" : "m.created_at >= NOW() - INTERVAL 15 SECOND";
        $stmtComm = $db->prepare("
            SELECT m.*, u.name as sender_name, c.name as channel_name, cat.name as category_name, cat.slug as category_slug
            FROM messages m
            JOIN users u ON m.user_id = u.id
            JOIN channels c ON m.channel_id = c.id
            JOIN categories cat ON c.category_id = cat.id
            JOIN community_members cm ON cm.category_id = cat.id
            WHERE cm.user_id = :uid AND m.user_id != :uid2
              AND {$timeCondition}
            ORDER BY m.id DESC
            LIMIT 5
        ");
        $stmtComm->execute(['uid' => $user['id'], 'uid2' => $user['id']]);
        $channelMsgs = $stmtComm->fetchAll();

        // 3. Solicitações de amizade pendentes
        $pendingFriends = \App\Models\Friendship::getPendingRequests((int)$user['id']);

        $this->jsonResponse([
            'status' => 'success',
            'unread_private' => $unreadPm,
            'recent_channel' => $channelMsgs,
            'pending_friends' => $pendingFriends
        ]);
    }
}

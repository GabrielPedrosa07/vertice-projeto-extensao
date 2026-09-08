<?php
/**
 * Model de Chamadas Privadas (WebRTC 1x1) e Convites de Comunidade — Vértice
 */

namespace App\Models;

use PDO;

class PrivateCall
{
    /**
     * Inicia uma chamada privada gerando room_id único e registro de sinalização
     */
    public static function initiateCall(int $callerId, string $callerName, int $receiverId): array
    {
        $db = Database::getConnection();

        // Cancela chamadas pendentes anteriores entre os mesmos usuários
        $db->prepare("
            UPDATE private_call_signals 
            SET status = 'ended' 
            WHERE (caller_id = :u1 AND receiver_id = :u2) OR (caller_id = :u3 AND receiver_id = :u4)
        ")->execute([
            'u1' => $callerId, 'u2' => $receiverId,
            'u3' => $receiverId, 'u4' => $callerId
        ]);

        $min = min($callerId, $receiverId);
        $max = max($callerId, $receiverId);
        $roomId = "dm_call_{$min}_{$max}_" . time();

        $stmt = $db->prepare("
            INSERT INTO private_call_signals (caller_id, caller_name, receiver_id, room_id, status, created_at, updated_at)
            VALUES (:caller, :cname, :receiver, :room, 'calling', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'caller' => $callerId,
            'cname'  => $callerName,
            'receiver' => $receiverId,
            'room'   => $roomId
        ]);

        $callId = (int)$db->lastInsertId();

        return [
            'status'  => 'success',
            'call_id' => $callId,
            'room_id' => $roomId,
            'caller_name' => $callerName
        ];
    }

    /**
     * Verifica se o usuário tem chamadas recebidas tocando (calling) nos últimos 40 segundos
     */
    public static function checkIncomingCall(int $userId): ?array
    {
        $db = Database::getConnection();
        $isSqlite = Database::isSqlite();

        if ($isSqlite) {
            $stmt = $db->prepare("
                SELECT * FROM private_call_signals
                WHERE receiver_id = :uid AND status = 'calling'
                  AND created_at >= datetime('now', '-60 seconds')
                ORDER BY id DESC
                LIMIT 1
            ");
        } else {
            $stmt = $db->prepare("
                SELECT * FROM private_call_signals
                WHERE receiver_id = :uid AND status = 'calling'
                  AND created_at >= NOW() - INTERVAL 60 SECOND
                ORDER BY id DESC
                LIMIT 1
            ");
        }

        $stmt->execute(['uid' => $userId]);
        $call = $stmt->fetch();

        return $call ?: null;
    }

    public static function respondCall(int $callId, int $userId, string $action): bool
    {
        $status = ($action === 'accept') ? 'accepted' : 'declined';
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE private_call_signals 
            SET status = :st, updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id AND (receiver_id = :uid OR caller_id = :uid2)
        ");
        $stmt->execute(['st' => $status, 'id' => $callId, 'uid' => $userId, 'uid2' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function endCall(int $callId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE private_call_signals SET status = 'ended', updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->execute(['id' => $callId]);
        return true;
    }

    public static function getCall(int $callId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM private_call_signals WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $callId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // ── CONVITES PARA GRUPOS / COMUNIDADES ────────────────────────────────────

    public static function sendCommunityInvite(int $senderId, string $senderName, int $receiverId, int $categoryId, int $channelId): array
    {
        $db = Database::getConnection();

        // Obtém informações da categoria e canal
        $stmtCat = $db->prepare("SELECT name, slug FROM categories WHERE id = :id LIMIT 1");
        $stmtCat->execute(['id' => $categoryId]);
        $cat = $stmtCat->fetch();
        if (!$cat) return ['status' => 'error', 'message' => 'Comunidade não encontrada.'];

        $stmtChan = $db->prepare("SELECT name FROM channels WHERE id = :id LIMIT 1");
        $stmtChan->execute(['id' => $channelId]);
        $chan = $stmtChan->fetch();
        $channelName = $chan ? $chan['name'] : 'Canal Geral';

        $stmt = $db->prepare("
            INSERT INTO community_invites (
                sender_id, sender_name, receiver_id, category_id, category_name, category_slug,
                channel_id, channel_name, status, created_at
            ) VALUES (
                :s_id, :s_name, :r_id, :cat_id, :cat_name, :cat_slug,
                :chan_id, :chan_name, 'pending', CURRENT_TIMESTAMP
            )
        ");
        $stmt->execute([
            's_id'      => $senderId,
            's_name'    => $senderName,
            'r_id'      => $receiverId,
            'cat_id'    => $categoryId,
            'cat_name'  => $cat['name'],
            'cat_slug'  => $cat['slug'],
            'chan_id'   => $channelId,
            'chan_name' => $channelName
        ]);

        return [
            'status' => 'success',
            'message' => "Convite para a matéria {$cat['name']} enviado com sucesso!"
        ];
    }

    public static function getMyInvites(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM community_invites 
            WHERE receiver_id = :uid AND status = 'pending'
            ORDER BY created_at DESC
        ");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }
}

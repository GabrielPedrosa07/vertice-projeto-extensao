<?php
/**
 * Model CallSignal & CallParticipant — Vértice
 * PHP Puro + PDO (WebRTC Signaling)
 */

namespace App\Models;

use PDO;

class CallSignal
{
    public static function join(int $channelId, int $userId, string $userName, string $peerId): array
    {
        $db = Database::getConnection();
        
        // Remove registros antigos / inativos deste usuário no canal
        $stmt = $db->prepare("DELETE FROM call_participants WHERE channel_id = :ch AND user_id = :u");
        $stmt->execute(['ch' => $channelId, 'u' => $userId]);

        // Insere participante ativo
        $stmt = $db->prepare("
            INSERT INTO call_participants (channel_id, user_id, user_name, peer_id, last_seen)
            VALUES (:ch, :u, :name, :peer, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'ch'   => $channelId,
            'u'    => $userId,
            'name' => $userName,
            'peer' => $peerId
        ]);

        return self::getPeers($channelId, $userId);
    }

    public static function heartbeat(int $channelId, int $userId, array $status = []): void
    {
        $db = Database::getConnection();
        $isMuted = isset($status['is_muted']) ? ($status['is_muted'] ? 1 : 0) : 0;
        $isVideoOff = isset($status['is_video_off']) ? ($status['is_video_off'] ? 1 : 0) : 0;
        $isScreen = isset($status['is_screen_sharing']) ? ($status['is_screen_sharing'] ? 1 : 0) : 0;

        $stmt = $db->prepare("
            UPDATE call_participants 
            SET last_seen = CURRENT_TIMESTAMP,
                is_muted = :muted,
                is_video_off = :video,
                is_screen_sharing = :screen
            WHERE channel_id = :ch AND user_id = :u
        ");
        $stmt->execute([
            'muted'  => $isMuted,
            'video'  => $isVideoOff,
            'screen' => $isScreen,
            'ch'     => $channelId,
            'u'      => $userId
        ]);
    }

    public static function leave(int $channelId, int $userId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM call_participants WHERE channel_id = :ch AND user_id = :u");
        $stmt->execute(['ch' => $channelId, 'u' => $userId]);

        // Remove sinais pendentes
        $stmt = $db->prepare("DELETE FROM call_signals WHERE channel_id = :ch AND (sender_id = :u OR receiver_id = :u)");
        $stmt->execute(['ch' => $channelId, 'u' => $userId]);
    }

    public static function getPeers(int $channelId, ?int $excludeUserId = null): array
    {
        $db = Database::getConnection();

        // Limpa participantes sem heartbeat recente (mais de 20 segundos)
        try {
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $db->exec("DELETE FROM call_participants WHERE last_seen < DATE_SUB(NOW(), INTERVAL 20 SECOND)");
            } else {
                $db->exec("DELETE FROM call_participants WHERE last_seen < datetime('now', '-20 seconds')");
            }
        } catch (\Throwable $e) {}

        if ($excludeUserId !== null) {
            $stmt = $db->prepare("
                SELECT cp.*, u.avatar_url, u.role
                FROM call_participants cp
                LEFT JOIN users u ON cp.user_id = u.id
                WHERE cp.channel_id = :ch AND cp.user_id != :exclude_u
                ORDER BY cp.joined_at ASC
            ");
            $stmt->execute(['ch' => $channelId, 'exclude_u' => $excludeUserId]);
        } else {
            $stmt = $db->prepare("
                SELECT cp.*, u.avatar_url, u.role
                FROM call_participants cp
                LEFT JOIN users u ON cp.user_id = u.id
                WHERE cp.channel_id = :ch
                ORDER BY cp.joined_at ASC
            ");
            $stmt->execute(['ch' => $channelId]);
        }

        return $stmt->fetchAll();
    }

    public static function sendSignal(int $channelId, int $senderId, int $receiverId, string $type, string $payload): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO call_signals (channel_id, sender_id, receiver_id, type, payload)
            VALUES (:ch, :s, :r, :t, :p)
        ");
        $stmt->execute([
            'ch' => $channelId,
            's'  => $senderId,
            'r'  => $receiverId,
            't'  => $type,
            'p'  => $payload
        ]);
    }

    public static function getSignals(int $channelId, int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT cs.*, u.name as sender_name
            FROM call_signals cs
            LEFT JOIN users u ON cs.sender_id = u.id
            WHERE cs.channel_id = :ch AND cs.receiver_id = :u
            ORDER BY cs.id ASC
        ");
        $stmt->execute(['ch' => $channelId, 'u' => $userId]);
        $signals = $stmt->fetchAll();

        // Remove os sinais lidos para não duplicar
        if (!empty($signals)) {
            $ids = array_column($signals, 'id');
            $inQuery = implode(',', array_map('intval', $ids));
            $db->exec("DELETE FROM call_signals WHERE id IN ($inQuery)");
        }

        return $signals;
    }
}

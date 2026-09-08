<?php
/**
 * Model de Mensagens Privadas (Direct Messages / 1x1) — Vértice
 */

namespace App\Models;

use PDO;

class PrivateMessage
{
    /**
     * Retorna o histórico de mensagens privadas entre 2 usuários
     */
    public static function getConversation(int $user1, int $user2, int $afterId = 0): array
    {
        $db = Database::getConnection();
        $sql = "
            SELECT m.*, u.name AS sender_name, u.role AS sender_role
            FROM private_messages m
            JOIN users u ON m.sender_id = u.id
            WHERE ((m.sender_id = :u1 AND m.receiver_id = :u2)
               OR  (m.sender_id = :u3 AND m.receiver_id = :u4))
        ";

        $params = [
            'u1' => $user1, 'u2' => $user2,
            'u3' => $user2, 'u4' => $user1
        ];

        if ($afterId > 0) {
            $sql .= " AND m.id > :after_id";
            $params['after_id'] = $afterId;
        }

        $sql .= " ORDER BY m.id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Envia mensagem privada
     */
    public static function sendMessage(int $senderId, int $receiverId, string $content, array $meta = []): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO private_messages (
                sender_id, receiver_id, content, is_code_block, code_language,
                attachment_url, attachment_name, attachment_type, is_read, created_at
            ) VALUES (
                :sender, :receiver, :content, :code_block, :lang,
                :att_url, :att_name, :att_type, 0, CURRENT_TIMESTAMP
            )
        ");

        $stmt->execute([
            'sender'     => $senderId,
            'receiver'   => $receiverId,
            'content'    => trim($content),
            'code_block' => !empty($meta['is_code']) ? 1 : 0,
            'lang'       => $meta['code_language'] ?? null,
            'att_url'    => $meta['attachment_url'] ?? null,
            'att_name'   => $meta['attachment_name'] ?? null,
            'att_type'   => $meta['attachment_type'] ?? null,
        ]);

        $msgId = (int)$db->lastInsertId();

        // Recupera mensagem gravada com dados do remetente
        $stmtMsg = $db->prepare("
            SELECT m.*, u.name AS sender_name, u.role AS sender_role
            FROM private_messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.id = :id
            LIMIT 1
        ");
        $stmtMsg->execute(['id' => $msgId]);
        return $stmtMsg->fetch() ?: [];
    }

    /**
     * Marca mensagens de um remetente como lidas
     */
    public static function markAsRead(int $receiverId, int $senderId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE private_messages 
            SET is_read = 1 
            WHERE receiver_id = :rec AND sender_id = :snd AND is_read = 0
        ");
        $stmt->execute(['rec' => $receiverId, 'snd' => $senderId]);
    }

    /**
     * Retorna a lista de conversas recentes do usuário com contagem de não lidas
     */
    public static function getRecentConversations(int $userId): array
    {
        $friends = Friendship::getFriends($userId);
        $db = Database::getConnection();

        $conversations = [];

        foreach ($friends as $friend) {
            $fId = (int)$friend['id'];

            // Última mensagem
            $stmt = $db->prepare("
                SELECT * FROM private_messages
                WHERE (sender_id = :u1 AND receiver_id = :f1)
                   OR (sender_id = :f2 AND receiver_id = :u2)
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmt->execute(['u1' => $userId, 'f1' => $fId, 'f2' => $fId, 'u2' => $userId]);
            $lastMsg = $stmt->fetch();

            // Contagem de não lidas enviadas por este amigo
            $stmtUnread = $db->prepare("
                SELECT COUNT(*) AS total FROM private_messages
                WHERE receiver_id = :uid AND sender_id = :fid AND is_read = 0
            ");
            $stmtUnread->execute(['uid' => $userId, 'fid' => $fId]);
            $unreadCount = (int)$stmtUnread->fetchColumn();

            $conversations[] = [
                'friend_id'     => $fId,
                'friend_name'   => $friend['name'],
                'friend_email'  => $friend['email'],
                'friend_role'   => $friend['role'],
                'badge_title'   => $friend['badge_title'],
                'karma_points'  => $friend['karma_points'],
                'last_message'  => $lastMsg ? $lastMsg['content'] : 'Nenhuma mensagem ainda.',
                'last_time'     => $lastMsg ? $lastMsg['created_at'] : null,
                'unread_count'  => $unreadCount
            ];
        }

        // Ordena conversas pela data da última mensagem (mais recente primeiro)
        usort($conversations, function ($a, $b) {
            if (!$a['last_time']) return 1;
            if (!$b['last_time']) return -1;
            return strtotime($b['last_time']) <=> strtotime($a['last_time']);
        });

        return $conversations;
    }
}

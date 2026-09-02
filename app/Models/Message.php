<?php
/**
 * Model Message — Vértice
 * PHP Puro + PDO
 */

namespace App\Models;

use PDO;

class Message
{
    public static function getByChannel(int $channelId, int $limit = 50): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT m.*, u.name as user_name, u.role as user_role, u.avatar_url
            FROM messages m
            INNER JOIN users u ON m.user_id = u.id
            WHERE m.channel_id = :ch_id
            ORDER BY m.id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':ch_id', $channelId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $messages = $stmt->fetchAll();
        return array_reverse($messages);
    }

    public static function getAfter(int $channelId, int $afterId = 0): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT m.*, u.name as user_name, u.role as user_role, u.avatar_url
            FROM messages m
            INNER JOIN users u ON m.user_id = u.id
            WHERE m.channel_id = :ch_id AND m.id > :after_id
            ORDER BY m.id ASC
            LIMIT 100
        ");
        $stmt->bindValue(':ch_id', $channelId, PDO::PARAM_INT);
        $stmt->bindValue(':after_id', $afterId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT m.*, u.name as user_name, u.role as user_role, u.avatar_url
            FROM messages m
            INNER JOIN users u ON m.user_id = u.id
            WHERE m.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $msg = $stmt->fetch();
        return $msg ?: null;
    }

    public static function create(
        int $channelId,
        int $userId,
        string $content,
        bool $isCode = false,
        ?string $codeLang = null,
        ?string $attachmentUrl = null,
        ?string $attachmentName = null,
        ?string $attachmentType = null
    ): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO messages (
                channel_id, user_id, content, is_code_block, code_language,
                attachment_url, attachment_name, attachment_type
            )
            VALUES (
                :ch, :u, :content, :is_code, :lang,
                :att_url, :att_name, :att_type
            )
        ");
        $stmt->execute([
            'ch'       => $channelId,
            'u'        => $userId,
            'content'  => $content,
            'is_code'  => $isCode ? 1 : 0,
            'lang'     => $codeLang,
            'att_url'  => $attachmentUrl,
            'att_name' => $attachmentName,
            'att_type' => $attachmentType,
        ]);

        $newId = (int)$db->lastInsertId();
        return self::findById($newId);
    }

    public static function delete(int $messageId, int $userId, bool $isAdmin = false): bool
    {
        $db = Database::getConnection();
        if ($isAdmin) {
            $stmt = $db->prepare("DELETE FROM messages WHERE id = :id");
            return $stmt->execute(['id' => $messageId]);
        }
        $stmt = $db->prepare("DELETE FROM messages WHERE id = :id AND user_id = :user_id");
        return $stmt->execute(['id' => $messageId, 'user_id' => $userId]);
    }
}

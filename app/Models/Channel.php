<?php
/**
 * Model Channel & Message — Vértice
 * PHP Puro + PDO
 */

namespace App\Models;

use PDO;

class Channel
{
    public static function getByCategory(int $categoryId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM channels WHERE category_id = :cat_id ORDER BY id ASC");
        $stmt->execute(['cat_id' => $categoryId]);
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM channels WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $channel = $stmt->fetch();
        return $channel ?: null;
    }
}

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
            ORDER BY m.created_at ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':ch_id', $channelId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function create(int $channelId, int $userId, string $content, bool $isCode = false, ?string $codeLang = null): ?int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO messages (channel_id, user_id, content, is_code_block, code_language)
            VALUES (:ch, :u, :content, :is_code, :lang)
        ");
        $stmt->execute([
            'ch'      => $channelId,
            'u'       => $userId,
            'content' => $content,
            'is_code' => $isCode ? 1 : 0,
            'lang'    => $codeLang
        ]);
        return (int)$db->lastInsertId();
    }
}

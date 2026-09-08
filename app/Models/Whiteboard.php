<?php
/**
 * Model da Lousa Digital / Quadro Branco Interativo — Vértice
 */

namespace App\Models;

use PDO;

class Whiteboard
{
    /**
     * Retorna os elementos/traços salvos do quadro de um canal
     */
    public static function getShapes(int $channelId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM channel_whiteboard_shapes
            WHERE channel_id = :ch
            ORDER BY id ASC
        ");
        $stmt->execute(['ch' => $channelId]);
        return $stmt->fetchAll();
    }

    /**
     * Adiciona um novo traço, forma geométrica, texto ou post-it na lousa
     */
    public static function addShape(int $channelId, ?int $userId, string $userName, string $shapeData): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO channel_whiteboard_shapes (channel_id, user_id, user_name, shape_data, created_at)
            VALUES (:ch, :uid, :uname, :data, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'ch' => $channelId,
            'uid' => $userId,
            'uname' => $userName,
            'data' => $shapeData
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Limpa o quadro branco do canal
     */
    public static function clear(int $channelId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM channel_whiteboard_shapes WHERE channel_id = :ch");
        return $stmt->execute(['ch' => $channelId]);
    }
}

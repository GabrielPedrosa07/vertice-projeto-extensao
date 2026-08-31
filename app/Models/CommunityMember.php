<?php
/**
 * Model CommunityMember — Vértice
 * PHP Puro + PDO
 */

namespace App\Models;

use PDO;

class CommunityMember
{
    public static function isMember(int $userId, int $categoryId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM community_members WHERE user_id = :u AND category_id = :c LIMIT 1");
        $stmt->execute(['u' => $userId, 'c' => $categoryId]);
        return (bool)$stmt->fetch();
    }

    public static function join(int $userId, int $categoryId, string $role = 'member'): bool
    {
        if (self::isMember($userId, $categoryId)) {
            return true;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO community_members (user_id, category_id, role)
            VALUES (:u, :c, :r)
        ");
        return $stmt->execute(['u' => $userId, 'c' => $categoryId, 'r' => $role]);
    }

    public static function leave(int $userId, int $categoryId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM community_members WHERE user_id = :u AND category_id = :c");
        return $stmt->execute(['u' => $userId, 'c' => $categoryId]);
    }
}

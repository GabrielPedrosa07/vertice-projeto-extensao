<?php
/**
 * Model Category (Comunidades / Disciplinas) — Vértice
 * PHP Puro + PDO
 */

namespace App\Models;

use PDO;

class Category
{
    public static function all(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT c.*, COUNT(cm.id) AS members_count
            FROM categories c
            LEFT JOIN community_members cm ON c.id = cm.category_id
            GROUP BY c.id
            ORDER BY c.name ASC
        ");
        return $stmt->fetchAll();
    }

    public static function findBySlug(string $slug): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT c.*, COUNT(cm.id) AS members_count
            FROM categories c
            LEFT JOIN community_members cm ON c.id = cm.category_id
            WHERE c.slug = :slug
            GROUP BY c.id
            LIMIT 1
        ");
        $stmt->execute(['slug' => $slug]);
        $cat = $stmt->fetch();
        return $cat ?: null;
    }

    public static function getJoinedByUser(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM community_members WHERE category_id = c.id) AS members_count
            FROM categories c
            INNER JOIN community_members cm ON c.id = cm.category_id
            WHERE cm.user_id = :user_id
            ORDER BY c.name ASC
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function getAvailableForUser(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM community_members WHERE category_id = c.id) AS members_count
            FROM categories c
            WHERE c.id NOT IN (
                SELECT category_id FROM community_members WHERE user_id = :user_id
            )
            ORDER BY c.name ASC
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function search(string $term): array
    {
        $db = Database::getConnection();
        $termLike = '%' . trim($term) . '%';
        $stmt = $db->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM community_members WHERE category_id = c.id) AS members_count
            FROM categories c
            WHERE c.name LIKE :term1 OR c.description LIKE :term2
            ORDER BY c.name ASC
        ");
        $stmt->execute([
            'term1' => $termLike,
            'term2' => $termLike
        ]);
        return $stmt->fetchAll();
    }
}

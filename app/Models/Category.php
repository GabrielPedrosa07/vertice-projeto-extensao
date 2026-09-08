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

    public static function create(string $name, string $slug, ?string $description = null, ?string $icon = '📚', ?string $color = '#5b6abf', ?int $creatorId = null): ?array
    {
        $db = Database::getConnection();

        // Garante slug único
        $baseSlug = $slug;
        $counter = 1;
        while (self::findBySlug($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $stmt = $db->prepare("
            INSERT INTO categories (name, slug, description, color, icon, creator_id, created_at)
            VALUES (:name, :slug, :desc, :color, :icon, :creator, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'name'    => trim($name),
            'slug'    => $slug,
            'desc'    => $description ? trim($description) : 'Comunidade criada por estudantes da FASM.',
            'color'   => $color ?: '#5b6abf',
            'icon'    => $icon ?: '📚',
            'creator' => $creatorId
        ]);

        $catId = (int)$db->lastInsertId();

        // Cria canais padrão: #geral, #estudos, #duvidas
        $stmtChan = $db->prepare("
            INSERT INTO channels (category_id, name, slug, description, created_by, created_at)
            VALUES 
                (:c1, 'geral', 'geral', 'Bate-papo geral da comunidade', :u1, CURRENT_TIMESTAMP),
                (:c2, 'estudos', 'estudos', 'Compartilhamento de resumos e anotações', :u2, CURRENT_TIMESTAMP),
                (:c3, 'duvidas', 'duvidas', 'Perguntas e respostas com colegas', :u3, CURRENT_TIMESTAMP)
        ");
        $stmtChan->execute([
            'c1' => $catId, 'c2' => $catId, 'c3' => $catId,
            'u1' => $creatorId, 'u2' => $creatorId, 'u3' => $creatorId
        ]);

        // Associa o criador como membro
        if ($creatorId) {
            CommunityMember::join($creatorId, $catId);
        }

        return self::findBySlug($slug);
    }
}

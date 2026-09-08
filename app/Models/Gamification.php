<?php
/**
 * Model de Gamificação e Reputação Acadêmica (Karma & Medalhas) — Vértice
 */

namespace App\Models;

use PDO;

class Gamification
{
    /**
     * Adiciona pontos ao usuário e atualiza automaticamente seu título e insígnia
     */
    public static function addPoints(int $userId, int $points, bool $isSolution = false): void
    {
        $db = Database::getConnection();

        $current = self::getUserKarma($userId);
        $newPoints = max(0, $current['points'] + $points);
        $newSolutions = $current['solutions_count'] + ($isSolution ? 1 : 0);
        $newBadge = self::calculateBadge($newPoints);

        $stmt = $db->prepare("
            INSERT INTO user_karma (user_id, points, badge_title, solutions_count, updated_at)
            VALUES (:uid, :pts, :badge, :sol, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
                points = :pts_up,
                badge_title = :badge_up,
                solutions_count = :sol_up,
                updated_at = CURRENT_TIMESTAMP
        ");

        try {
            $stmt->execute([
                'uid' => $userId,
                'pts' => $newPoints,
                'badge' => $newBadge,
                'sol' => $newSolutions,
                'pts_up' => $newPoints,
                'badge_up' => $newBadge,
                'sol_up' => $newSolutions
            ]);
        } catch (\Throwable $e) {
            // Fallback SQLite
            $sqliteStmt = $db->prepare("
                INSERT INTO user_karma (user_id, points, badge_title, solutions_count, updated_at)
                VALUES (:uid, :pts, :badge, :sol, CURRENT_TIMESTAMP)
                ON CONFLICT(user_id) DO UPDATE SET
                    points = excluded.points,
                    badge_title = excluded.badge_title,
                    solutions_count = excluded.solutions_count,
                    updated_at = CURRENT_TIMESTAMP
            ");
            $sqliteStmt->execute([
                'uid' => $userId,
                'pts' => $newPoints,
                'badge' => $newBadge,
                'sol' => $newSolutions
            ]);
        }
    }

    /**
     * Retorna os dados de Karma de um usuário
     */
    public static function getUserKarma(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM user_karma WHERE user_id = :uid LIMIT 1");
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch();

        if (!$row) {
            return [
                'user_id' => $userId,
                'points' => 0,
                'badge_title' => '🥉 Iniciante Curioso',
                'solutions_count' => 0
            ];
        }

        return [
            'user_id' => (int)$row['user_id'],
            'points' => (int)$row['points'],
            'badge_title' => $row['badge_title'] ?: self::calculateBadge((int)$row['points']),
            'solutions_count' => (int)($row['solutions_count'] ?? 0)
        ];
    }

    /**
     * Calcula a insígnia/medalha com base na pontuação de Karma
     */
    public static function calculateBadge(int $points): string
    {
        if ($points >= 400) {
            return '💎 Mestre Universitário';
        } elseif ($points >= 150) {
            return '🥇 Monitor Destaque';
        } elseif ($points >= 50) {
            return '🥈 Colega Solidário';
        } else {
            return '🥉 Iniciante Curioso';
        }
    }

    /**
     * Retorna o ranking / leaderboard dos melhores alunos
     */
    public static function getLeaderboard(int $limit = 10): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.role, COALESCE(k.points, 0) AS points, 
                   COALESCE(k.badge_title, '🥉 Iniciante Curioso') AS badge_title,
                   COALESCE(k.solutions_count, 0) AS solutions_count
            FROM users u
            LEFT JOIN user_karma k ON u.id = k.user_id
            ORDER BY points DESC, solutions_count DESC, u.name ASC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

<?php
/**
 * Model de Amizades e Conexões Acadêmicas — Vértice
 */

namespace App\Models;

use PDO;

class Friendship
{
    /**
     * Envia solicitação de amizade
     */
    public static function sendRequest(int $userId, int $friendId): array
    {
        if ($userId === $friendId) {
            return ['status' => 'error', 'message' => 'Você não pode adicionar a si mesmo.'];
        }

        $db = Database::getConnection();

        // Verifica status atual
        $stmt = $db->prepare("
            SELECT * FROM friendships 
            WHERE (user_id = :u1 AND friend_id = :f1) OR (user_id = :u2 AND friend_id = :f2)
            LIMIT 1
        ");
        $stmt->execute([
            'u1' => $userId, 'f1' => $friendId,
            'u2' => $friendId, 'f2' => $userId
        ]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['status'] === 'accepted') {
                return ['status' => 'already_friends', 'message' => 'Vocês já são amigos!'];
            }
            if ($existing['status'] === 'pending') {
                if ((int)$existing['user_id'] === $userId) {
                    return ['status' => 'pending', 'message' => 'Solicitação já enviada, aguardando resposta.'];
                } else {
                    // O outro já havia pedido! Aceita automaticamente
                    self::acceptRequest((int)$existing['id'], $userId);
                    return ['status' => 'accepted', 'message' => 'Você aceitou a solicitação de amizade!'];
                }
            }
        }

        $stmtInsert = $db->prepare("
            INSERT INTO friendships (user_id, friend_id, status, created_at, updated_at)
            VALUES (:uid, :fid, 'pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmtInsert->execute(['uid' => $userId, 'fid' => $friendId]);

        return ['status' => 'success', 'message' => 'Solicitação de amizade enviada com sucesso!'];
    }

    public static function sendRequestByEmail(int $userId, string $email): array
    {
        $user = User::findByEmail(trim($email));
        if (!$user) {
            return ['status' => 'error', 'message' => 'Nenhum usuário cadastrado com este e-mail.'];
        }
        return self::sendRequest($userId, (int)$user['id']);
    }

    public static function acceptRequest(int $requestId, int $userId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE friendships 
            SET status = 'accepted', updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id AND friend_id = :uid
        ");
        $stmt->execute(['id' => $requestId, 'uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function declineRequest(int $requestId, int $userId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE friendships 
            SET status = 'declined', updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id AND friend_id = :uid
        ");
        $stmt->execute(['id' => $requestId, 'uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function removeFriend(int $userId, int $friendId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            DELETE FROM friendships 
            WHERE (user_id = :u1 AND friend_id = :f1) OR (user_id = :u2 AND friend_id = :f2)
        ");
        $stmt->execute([
            'u1' => $userId, 'f1' => $friendId,
            'u2' => $friendId, 'f2' => $userId
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Retorna a lista de amigos aceitos
     */
    public static function getFriends(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email, u.role, u.avatar_url,
                   COALESCE(k.badge_title, '🥉 Iniciante Curioso') AS badge_title,
                   COALESCE(k.points, 0) AS karma_points,
                   f.id AS friendship_id, f.created_at AS friends_since
            FROM friendships f
            JOIN users u ON (u.id = CASE WHEN f.user_id = :uid THEN f.friend_id ELSE f.user_id END)
            LEFT JOIN user_karma k ON u.id = k.user_id
            WHERE (f.user_id = :uid2 OR f.friend_id = :uid3) AND f.status = 'accepted'
            ORDER BY u.name ASC
        ");
        $stmt->execute(['uid' => $userId, 'uid2' => $userId, 'uid3' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Retorna solicitações pendentes recebidas
     */
    public static function getPendingRequests(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT f.id, f.created_at, u.id AS requester_id, u.name, u.email, u.role,
                   COALESCE(k.badge_title, '🥉 Iniciante Curioso') AS badge_title
            FROM friendships f
            JOIN users u ON f.user_id = u.id
            LEFT JOIN user_karma k ON u.id = k.user_id
            WHERE f.friend_id = :uid AND f.status = 'pending'
            ORDER BY f.created_at DESC
        ");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca usuários da faculdade para adicionar
     */
    public static function searchUsers(string $term, int $currentUserId): array
    {
        $term = trim($term);
        if (empty($term)) return [];

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email, u.role,
                   COALESCE(k.badge_title, '🥉 Iniciante Curioso') AS badge_title
            FROM users u
            LEFT JOIN user_karma k ON u.id = k.user_id
            WHERE (u.name LIKE :t1 OR u.email LIKE :t2) AND u.id != :cuid
            ORDER BY u.name ASC
            LIMIT 10
        ");
        $like = "%{$term}%";
        $stmt->execute(['t1' => $like, 't2' => $like, 'cuid' => $currentUserId]);
        $users = $stmt->fetchAll();

        foreach ($users as &$u) {
            $u['friendship_status'] = self::getFriendshipStatus($currentUserId, (int)$u['id']);
        }

        return $users;
    }

    public static function getFriendshipStatus(int $user1, int $user2): string
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM friendships 
            WHERE (user_id = :u1 AND friend_id = :f1) OR (user_id = :u2 AND friend_id = :f2)
            LIMIT 1
        ");
        $stmt->execute([
            'u1' => $user1, 'f1' => $user2,
            'u2' => $user2, 'f2' => $user1
        ]);
        $row = $stmt->fetch();
        if (!$row) return 'none';

        if ($row['status'] === 'accepted') return 'friends';
        if ($row['status'] === 'pending') {
            return ((int)$row['user_id'] === $user1) ? 'pending_sent' : 'pending_received';
        }
        return 'declined';
    }
}

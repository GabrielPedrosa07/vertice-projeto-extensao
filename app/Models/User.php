<?php
/**
 * Model User — Vértice
 * PHP Puro + PDO
 */

namespace App\Models;

use PDO;

class User
{
    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(array $data): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO users (name, email, phone, password, role)
            VALUES (:name, :email, :phone, :password, :role)
        ");

        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $role = $data['role'] ?? 'student';
        $phone = $data['phone'] ?? null;

        $stmt->execute([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $phone,
            'password' => $hashedPassword,
            'role'     => $role,
        ]);

        $id = (int)$db->lastInsertId();
        return self::findById($id);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}

<?php
/**
 * Helper de Autenticação — Vértice
 * PHP Puro
 */

namespace App\Helpers;

use App\Models\User;

class Auth
{
    public static function check(): bool
    {
        return Session::has('user_id');
    }

    public static function id(): ?int
    {
        return Session::get('user_id');
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        $cached = Session::get('user_data');
        if ($cached) {
            return $cached;
        }

        $user = User::findById(self::id());
        if ($user) {
            unset($user['password']);
            Session::set('user_data', $user);
        }
        return $user;
    }

    public static function login(array $user): void
    {
        Session::set('user_id', (int)$user['id']);
        unset($user['password']);
        Session::set('user_data', $user);
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            Session::setFlash('erro', 'Você precisa entrar na sua conta para acessar esta área.');
            header('Location: /entrar');
            exit;
        }
    }
}

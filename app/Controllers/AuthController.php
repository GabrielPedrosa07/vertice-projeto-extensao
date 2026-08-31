<?php
/**
 * Controller de Autenticação — Vértice
 * PHP Puro
 */

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Session;
use App\Models\User;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            header('Location: /hub');
            exit;
        }
        require __DIR__ . '/../../views/auth/entrar.php';
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            Session::setFlash('erro', 'Por favor, preencha o e-mail e a senha.');
            header('Location: /entrar');
            exit;
        }

        $user = User::findByEmail($email);
        if (!$user || !User::verifyPassword($password, $user['password'])) {
            Session::setFlash('erro', 'E-mail ou senha incorretos.');
            header('Location: /entrar');
            exit;
        }

        Auth::login($user);
        Session::setFlash('sucesso', 'Bem-vindo de volta, ' . htmlspecialchars(explode(' ', $user['name'])[0]) . '!');
        header('Location: /hub');
        exit;
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            header('Location: /hub');
            exit;
        }
        require __DIR__ . '/../../views/auth/cadastro.php';
    }

    public function register(): void
    {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            Session::setFlash('erro', 'Por favor, preencha todos os campos obrigatórios.');
            header('Location: /cadastro');
            exit;
        }

        if (strlen($password) < 6) {
            Session::setFlash('erro', 'A senha deve conter no mínimo 6 caracteres.');
            header('Location: /cadastro');
            exit;
        }

        if ($password !== $passwordConfirmation) {
            Session::setFlash('erro', 'As senhas não coincidem. Tente novamente.');
            header('Location: /cadastro');
            exit;
        }

        if (User::findByEmail($email)) {
            Session::setFlash('erro', 'Este e-mail já está cadastrado. Faça login ou use outro.');
            header('Location: /cadastro');
            exit;
        }

        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'phone'    => $phone,
            'password' => $password,
            'role'     => 'student'
        ]);

        if ($user) {
            Auth::login($user);
            Session::setFlash('sucesso', 'Conta criada com sucesso! Seja bem-vindo ao Vértice.');
            header('Location: /hub');
            exit;
        } else {
            Session::setFlash('erro', 'Ocorreu um erro ao criar a conta. Tente novamente.');
            header('Location: /cadastro');
            exit;
        }
    }

    public function logout(): void
    {
        Auth::logout();
        Session::setFlash('sucesso', 'Você saiu da sua conta.');
        header('Location: /');
        exit;
    }
}

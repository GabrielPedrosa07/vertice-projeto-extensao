<?php
/**
 * Controller de Comunidades & Salas — Vértice
 * PHP Puro
 */

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Session;
use App\Models\Category;
use App\Models\Channel;
use App\Models\CommunityMember;
use App\Models\Message;

class CommunityController
{
    public function search(): void
    {
        Auth::requireAuth();
        $q = trim($_GET['q'] ?? '');

        $resultados = [];
        if (!empty($q)) {
            $resultados = Category::search($q);
        } else {
            $resultados = Category::all();
        }

        require __DIR__ . '/../../views/hub/buscar.php';
    }

    public function show(string $slug): void
    {
        Auth::requireAuth();
        $user = Auth::user();

        $comunidade = Category::findBySlug($slug);
        if (!$comunidade) {
            Session::setFlash('erro', 'Comunidade não encontrada.');
            header('Location: /hub');
            exit;
        }

        $isMember = CommunityMember::isMember($user['id'], $comunidade['id']);
        $canais = Channel::getByCategory($comunidade['id']);

        $channelId = isset($_GET['canal']) ? (int)$_GET['canal'] : ($canais[0]['id'] ?? 0);
        $canalAtivo = null;
        $mensagens = [];

        if ($channelId > 0) {
            $canalAtivo = Channel::findById($channelId);
            $mensagens = Message::getByChannel($channelId);
        }

        require __DIR__ . '/../../views/hub/comunidade.php';
    }

    public function join(string $slug): void
    {
        Auth::requireAuth();
        $user = Auth::user();

        $comunidade = Category::findBySlug($slug);
        if ($comunidade) {
            CommunityMember::join($user['id'], $comunidade['id']);
            Session::setFlash('sucesso', 'Você agora participa da comunidade ' . htmlspecialchars($comunidade['name']) . '!');
            header('Location: /comunidade/' . $slug);
            exit;
        }

        header('Location: /hub');
        exit;
    }

    public function leave(string $slug): void
    {
        Auth::requireAuth();
        $user = Auth::user();

        $comunidade = Category::findBySlug($slug);
        if ($comunidade) {
            CommunityMember::leave($user['id'], $comunidade['id']);
            Session::setFlash('sucesso', 'Você saiu da comunidade ' . htmlspecialchars($comunidade['name']) . '.');
        }

        header('Location: /hub');
        exit;
    }

    public function sendMessage(string $slug): void
    {
        Auth::requireAuth();
        $user = Auth::user();

        $comunidade = Category::findBySlug($slug);
        $channelId = (int)($_POST['channel_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        $isCode = isset($_POST['is_code']);
        $codeLang = trim($_POST['code_language'] ?? '');

        if (!empty($content) && $channelId > 0 && $comunidade) {
            Message::create($channelId, $user['id'], $content, $isCode, $codeLang ?: null);
        }

        header("Location: /comunidade/{$slug}?canal={$channelId}");
        exit;
    }

    public function createCommunity(): void
    {
        Auth::requireAuth();
        $user = Auth::user();

        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $icon = trim($data['icon'] ?? '📚');
        $color = trim($data['color'] ?? '#5b6abf');

        if (empty($name)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'O nome do servidor é obrigatório.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Gera slug básico
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        if (empty($slug)) $slug = 'servidor-' . time();

        $comunidade = Category::create($name, $slug, $description, $icon, $color, (int)$user['id']);

        if (!$comunidade) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Falha ao criar o servidor.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'message' => 'Servidor criado com sucesso!',
            'community' => $comunidade,
            'redirect' => '/comunidade/' . $comunidade['slug']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

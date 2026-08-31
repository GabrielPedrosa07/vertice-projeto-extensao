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
}

<?php
/**
 * VÉRTICE — Plataforma de Estudos e Comunicação
 * Projeto de Extensão - 4º Período ADS / FASM
 * 
 * Front Controller & Router em PHP Puro (Nativo)
 */

declare(strict_types=1);

// Servidor embutido do PHP: servir arquivos estáticos diretamente
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $fullPath = __DIR__ . $path;
    if ($path !== '/' && file_exists($fullPath) && !is_dir($fullPath)) {
        return false;
    }
}

// Autoloader nativo para a namespace App\
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Helpers\Session;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\HubController;
use App\Controllers\CommunityController;

// Inicia sessão
Session::start();

// Roteamento
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Normaliza URI removendo barras extras no final
$uri = rtrim($uri, '/');
if (empty($uri)) {
    $uri = '/';
}

// ----------------------------------------------------
// Rotas Públicas
// ----------------------------------------------------
if ($method === 'GET' && $uri === '/') {
    (new HomeController())->index();
    exit;
}

if ($method === 'GET' && $uri === '/sobre') {
    (new HomeController())->sobre();
    exit;
}

if ($method === 'GET' && $uri === '/termos') {
    (new HomeController())->termos();
    exit;
}

if ($method === 'GET' && $uri === '/privacidade') {
    (new HomeController())->privacidade();
    exit;
}

// ----------------------------------------------------
// Rotas de Autenticação
// ----------------------------------------------------
if ($method === 'GET' && $uri === '/entrar') {
    (new AuthController())->showLogin();
    exit;
}

if ($method === 'POST' && $uri === '/entrar') {
    (new AuthController())->login();
    exit;
}

if ($method === 'GET' && $uri === '/cadastro') {
    (new AuthController())->showRegister();
    exit;
}

if ($method === 'POST' && $uri === '/cadastro') {
    (new AuthController())->register();
    exit;
}

if ($method === 'POST' && $uri === '/sair') {
    (new AuthController())->logout();
    exit;
}

// ----------------------------------------------------
// Rotas Autenticadas (Hub & Comunidades)
// ----------------------------------------------------
if ($method === 'GET' && $uri === '/hub') {
    (new HubController())->index();
    exit;
}

if ($method === 'GET' && $uri === '/comunidades/buscar') {
    (new CommunityController())->search();
    exit;
}

// Rotas dinâmicas de comunidade: /comunidade/{slug}
if (preg_match('#^/comunidade/([a-zA-Z0-9_-]+)$#', $uri, $matches)) {
    $slug = $matches[1];
    if ($method === 'GET') {
        (new CommunityController())->show($slug);
        exit;
    }
}

if (preg_match('#^/comunidade/([a-zA-Z0-9_-]+)/entrar$#', $uri, $matches)) {
    $slug = $matches[1];
    if ($method === 'POST') {
        (new CommunityController())->join($slug);
        exit;
    }
}

if (preg_match('#^/comunidade/([a-zA-Z0-9_-]+)/sair$#', $uri, $matches)) {
    $slug = $matches[1];
    if ($method === 'POST') {
        (new CommunityController())->leave($slug);
        exit;
    }
}

if (preg_match('#^/comunidade/([a-zA-Z0-9_-]+)/mensagem$#', $uri, $matches)) {
    $slug = $matches[1];
    if ($method === 'POST') {
        (new CommunityController())->sendMessage($slug);
        exit;
    }
}

// ----------------------------------------------------
// 404 — Página não encontrada
// ----------------------------------------------------
http_response_code(404);
$pageTitle = 'Página Não Encontrada (404) — Vértice';
require __DIR__ . '/views/layouts/header.php';
?>
<main class="container" style="padding: 160px 20px 100px; text-align: center;">
    <div style="font-size: 54px; margin-bottom: 12px;">🧭</div>
    <h1 style="font-size: 36px; font-weight: 800; color: var(--cor-titulo); margin-bottom: 8px;">Página Não Encontrada</h1>
    <p style="color: var(--cor-apagado); margin-bottom: 24px;">O endereço que você tentou acessar não existe ou foi movido.</p>
    <a href="/" class="botao botao-primario">Voltar para a Página Inicial</a>
</main>
<?php
require __DIR__ . '/views/layouts/footer.php';

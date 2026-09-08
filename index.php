<?php
/**
 * VÉRTICE — Plataforma de Estudos e Comunicação
 * Projeto de Extensão - 4º Período ADS / FASM
 * 
 * Front Controller & Router em PHP Puro (Nativo)
 */

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

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
use App\Controllers\ApiController;
use App\Controllers\StudyToolsController;
use App\Controllers\AcademicEngagementController;
use App\Controllers\PrivateChatController;

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
// Rotas de API (JSON: Chat, Uploads, Presença e WebRTC)
// ----------------------------------------------------
if ($uri === '/api/mensagens') {
    if ($method === 'GET') {
        (new ApiController())->getMessages();
    } elseif ($method === 'POST') {
        (new ApiController())->sendMessage();
    }
    exit;
}

if ($method === 'POST' && $uri === '/api/mensagens/upload') {
    (new ApiController())->uploadFile();
    exit;
}

if ($method === 'POST' && $uri === '/api/mensagens/deletar') {
    (new ApiController())->deleteMessage();
    exit;
}

if ($method === 'GET' && $uri === '/api/online') {
    (new ApiController())->getOnlineUsers();
    exit;
}

if ($method === 'POST' && $uri === '/api/call/join') {
    (new ApiController())->joinCall();
    exit;
}

if ($method === 'POST' && $uri === '/api/call/heartbeat') {
    (new ApiController())->heartbeatCall();
    exit;
}

if ($method === 'POST' && $uri === '/api/call/leave') {
    (new ApiController())->leaveCall();
    exit;
}

if ($method === 'POST' && $uri === '/api/call/signal') {
    (new ApiController())->sendCallSignal();
    exit;
}

if ($method === 'GET' && $uri === '/api/call/signals') {
    (new ApiController())->getCallSignals();
    exit;
}

if ($method === 'GET' && $uri === '/api/call/peers') {
    (new ApiController())->getCallPeers();
    exit;
}

if ($method === 'POST' && $uri === '/api/call/remote-control/event') {
    (new ApiController())->postRemoteControlEvent();
    exit;
}

if ($method === 'GET' && $uri === '/api/call/remote-control/events') {
    (new ApiController())->getRemoteControlEvents();
    exit;
}

if ($method === 'GET' && ($uri === '/download/remote-agent' || $uri === '/api/download/remote-agent')) {
    (new ApiController())->downloadRemoteAgent();
    exit;
}

// ----------------------------------------------------
// Rotas de Ferramentas de Estudo (Live Code, SQL & Whiteboard)
// ----------------------------------------------------
if ($method === 'GET' && $uri === '/api/tools/code/get') {
    (new StudyToolsController())->getCode();
    exit;
}
if ($method === 'POST' && $uri === '/api/tools/code/save') {
    (new StudyToolsController())->saveCode();
    exit;
}
if ($method === 'POST' && $uri === '/api/tools/code/run') {
    (new StudyToolsController())->runCode();
    exit;
}

if ($method === 'POST' && $uri === '/api/tools/sql/execute') {
    (new StudyToolsController())->executeSql();
    exit;
}
if ($method === 'GET' && $uri === '/api/tools/sql/schema') {
    (new StudyToolsController())->getSqlSchema();
    exit;
}
if ($method === 'POST' && $uri === '/api/tools/sql/reset') {
    (new StudyToolsController())->resetSql();
    exit;
}

if ($method === 'GET' && $uri === '/api/tools/whiteboard/get') {
    (new StudyToolsController())->getWhiteboard();
    exit;
}
if ($method === 'POST' && $uri === '/api/tools/whiteboard/save') {
    (new StudyToolsController())->saveWhiteboardShape();
    exit;
}
if ($method === 'POST' && $uri === '/api/tools/whiteboard/clear') {
    (new StudyToolsController())->clearWhiteboard();
    exit;
}

// ----------------------------------------------------
// Rotas de Gamificação, Fórum Q&A e Quizzes (Pilar 2)
// ----------------------------------------------------
if ($method === 'GET' && $uri === '/api/forum/questions') {
    (new AcademicEngagementController())->listQuestions();
    exit;
}
if ($method === 'GET' && $uri === '/api/forum/question') {
    (new AcademicEngagementController())->getQuestion();
    exit;
}
if ($method === 'POST' && $uri === '/api/forum/question/create') {
    (new AcademicEngagementController())->createQuestion();
    exit;
}
if ($method === 'POST' && $uri === '/api/forum/answer/create') {
    (new AcademicEngagementController())->createAnswer();
    exit;
}
if ($method === 'POST' && $uri === '/api/forum/answer/mark-solution') {
    (new AcademicEngagementController())->markSolution();
    exit;
}
if ($method === 'POST' && $uri === '/api/forum/answer/vote') {
    (new AcademicEngagementController())->voteAnswer();
    exit;
}

if ($method === 'GET' && $uri === '/api/gamification/leaderboard') {
    (new AcademicEngagementController())->getLeaderboard();
    exit;
}
if ($method === 'GET' && $uri === '/api/gamification/my-stats') {
    (new AcademicEngagementController())->getMyStats();
    exit;
}

if ($method === 'GET' && $uri === '/api/quiz/list') {
    (new AcademicEngagementController())->listQuizzes();
    exit;
}
if ($method === 'GET' && $uri === '/api/quiz/get') {
    (new AcademicEngagementController())->getQuiz();
    exit;
}
if ($method === 'POST' && $uri === '/api/quiz/submit') {
    (new AcademicEngagementController())->submitQuiz();
    exit;
}
if ($method === 'GET' && $uri === '/api/quiz/leaderboard') {
    (new AcademicEngagementController())->getQuizLeaderboard();
    exit;
}

// ----------------------------------------------------
// Rotas de Mensagens Privadas, Amigos e Chamadas 1x1
// ----------------------------------------------------
if ($method === 'GET' && $uri === '/privado') {
    (new PrivateChatController())->index();
    exit;
}
if ($method === 'GET' && $uri === '/api/private/friends') {
    (new PrivateChatController())->getFriends();
    exit;
}
if ($method === 'GET' && $uri === '/api/private/friends/pending') {
    (new PrivateChatController())->getPendingRequests();
    exit;
}
if ($method === 'POST' && $uri === '/api/private/friends/request') {
    (new PrivateChatController())->sendFriendRequest();
    exit;
}
if ($method === 'POST' && $uri === '/api/private/friends/accept') {
    (new PrivateChatController())->acceptFriendRequest();
    exit;
}
if ($method === 'POST' && $uri === '/api/private/friends/decline') {
    (new PrivateChatController())->declineFriendRequest();
    exit;
}
if ($method === 'POST' && $uri === '/api/private/friends/remove') {
    (new PrivateChatController())->removeFriend();
    exit;
}
if ($method === 'GET' && $uri === '/api/private/users/search') {
    (new PrivateChatController())->searchUsers();
    exit;
}
if ($method === 'GET' && $uri === '/api/private/conversations') {
    (new PrivateChatController())->getConversations();
    exit;
}
if ($method === 'GET' && $uri === '/api/private/messages') {
    (new PrivateChatController())->getMessages();
    exit;
}
if ($method === 'POST' && $uri === '/api/private/messages/send') {
    (new PrivateChatController())->sendMessage();
    exit;
}
if ($method === 'POST' && $uri === '/api/private/call/start') {
    (new PrivateChatController())->startCall();
    exit;
}
if ($method === 'GET' && $uri === '/api/private/call/check') {
    (new PrivateChatController())->checkCall();
    exit;
}
if ($method === 'POST' && $uri === '/api/private/call/respond') {
    (new PrivateChatController())->respondCall();
    exit;
}
if ($method === 'POST' && $uri === '/api/private/call/end') {
    (new PrivateChatController())->endCall();
    exit;
}
if ($method === 'POST' && $uri === '/api/private/invite/send') {
    (new PrivateChatController())->sendCommunityInvite();
    exit;
}
if ($method === 'GET' && $uri === '/api/private/invites') {
    (new PrivateChatController())->getCommunityInvites();
    exit;
}
if ($method === 'GET' && $uri === '/api/notifications/check') {
    (new PrivateChatController())->checkNotifications();
    exit;
}

if ($method === 'POST' && $uri === '/api/communities/create') {
    (new CommunityController())->createCommunity();
    exit;
}

if ($method === 'GET' && $uri === '/download/configurar-chrome') {
    $file = __DIR__ . '/public/downloads/configurar_chrome_vertice.bat';
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="configurar_chrome_vertice.bat"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

if ($method === 'GET' && $uri === '/download/configurar-chrome-ps1') {
    $file = __DIR__ . '/public/downloads/configurar_chrome_vertice.ps1';
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="configurar_chrome_vertice.ps1"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
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

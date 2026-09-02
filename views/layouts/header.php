<?php
use App\Helpers\Auth;
use App\Helpers\Session;

$loggedUser = Auth::user();
$sucessoMsg = Session::getFlash('sucesso');
$erroMsg = Session::getFlash('erro');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — Vértice' : 'Vértice — Plataforma de Estudos e Comunicação' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body data-user-id="<?= $loggedUser['id'] ?? 0 ?>" data-user-name="<?= htmlspecialchars($loggedUser['name'] ?? '') ?>" data-user-role="<?= htmlspecialchars($loggedUser['role'] ?? '') ?>">

    <div class="efeito-luz-1"></div>
    <div class="efeito-luz-2"></div>

    <!-- Barra de Navegação Superior -->
    <nav class="barra-nav">
        <div class="container nav-conteudo">
            <a href="/" class="marca">
                <div class="marca-icone">V</div>
                <span class="marca-nome">Vértice</span>
                <span class="marca-badge">Extensão ADS</span>
            </a>

            <ul class="nav-links">
                <li><a href="/" class="<?= (($_SERVER['REQUEST_URI'] ?? '') === '/') ? 'ativo' : '' ?>">Início</a></li>
                <?php if (Auth::check()): ?>
                    <li><a href="/hub" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/hub') ? 'ativo' : '' ?>">Hub</a></li>
                <?php endif; ?>
                <li><a href="/sobre" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/sobre') ? 'ativo' : '' ?>">Sobre Nós</a></li>
                <li><a href="/termos" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/termos') ? 'ativo' : '' ?>">Termos</a></li>
                <li><a href="/privacidade" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/privacidade') ? 'ativo' : '' ?>">Privacidade</a></li>
            </ul>

            <div class="nav-acoes">
                <?php if (Auth::check()): ?>
                    <span class="user-greeting">Olá, <strong><?= htmlspecialchars(explode(' ', $loggedUser['name'])[0]) ?></strong></span>
                    <a href="/hub" class="botao botao-secundario" style="padding: 7px 14px; font-size: 13px;">Meu Hub</a>
                    <form method="POST" action="/sair" style="display:inline; margin:0;">
                        <button type="submit" class="botao botao-sair" style="padding: 7px 14px; font-size: 13px;">Sair</button>
                    </form>
                <?php else: ?>
                    <a href="/entrar" class="botao botao-secundario" style="padding: 8px 16px; font-size: 13px;">Entrar</a>
                    <a href="/cadastro" class="botao botao-primario" style="padding: 8px 16px; font-size: 13px;">Criar Conta</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Alertas Flash -->
    <?php if ($sucessoMsg): ?>
        <div class="container" style="margin-top: 90px; margin-bottom: -70px;">
            <div class="alerta alerta-sucesso">
                <span>✓</span> <?= htmlspecialchars($sucessoMsg) ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($erroMsg): ?>
        <div class="container" style="margin-top: 90px; margin-bottom: -70px;">
            <div class="alerta alerta-erro">
                <span>⚠</span> <?= htmlspecialchars($erroMsg) ?>
            </div>
        </div>
    <?php endif; ?>

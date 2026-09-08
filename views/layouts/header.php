<?php
use App\Helpers\Auth;
use App\Helpers\Session;

$loggedUser = Auth::user();
$sucessoMsg = Session::getFlash('sucesso');
$erroMsg = Session::getFlash('erro');
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Vértice — Extensão Universitária ADS FASM') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body data-user-id="<?= $loggedUser['id'] ?? 0 ?>" data-user-name="<?= htmlspecialchars($loggedUser['name'] ?? '') ?>" data-user-role="<?= htmlspecialchars($loggedUser['role'] ?? '') ?>">

    <div class="efeito-luz-1"></div>
    <div class="efeito-luz-2"></div>

    <!-- Barra de Navegação Superior Moderna -->
    <nav class="barra-nav" id="main-nav">
        <div class="container nav-conteudo">
            
            <!-- Marca / Logo -->
            <div class="nav-brand-group">
                <a href="/" class="marca" title="Vértice — Plataforma Acadêmica">
                    <div class="marca-icone">V</div>
                    <div class="marca-text-group">
                        <span class="marca-nome">Vértice</span>
                        <span class="marca-badge">Extensão ADS</span>
                    </div>
                </a>
            </div>

            <!-- Links Centrais -->
            <ul class="nav-links">
                <li>
                    <a href="/" class="<?= ($currentUri === '/') ? 'ativo' : '' ?>">
                        <span class="nav-link-icon">🏠</span> Início
                    </a>
                </li>
                <?php if (Auth::check()): ?>
                    <li>
                        <a href="/hub" class="<?= str_starts_with($currentUri, '/hub') ? 'ativo' : '' ?>">
                            <span class="nav-link-icon">🧭</span> Hub
                        </a>
                    </li>
                    <li>
                        <a href="/privado" class="<?= str_starts_with($currentUri, '/privado') ? 'ativo' : '' ?>">
                            <span class="nav-link-icon">💬</span> Mensagens & Amigos
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" onclick="window.openLeaderboardModal()" title="Ver ranking acadêmico e monitores">
                            <span class="nav-link-icon">🏆</span> Ranking
                        </a>
                    </li>
                <?php endif; ?>
                <li>
                    <a href="/sobre" class="<?= str_starts_with($currentUri, '/sobre') ? 'ativo' : '' ?>">
                        <span class="nav-link-icon">ℹ️</span> Sobre
                    </a>
                </li>
            </ul>

            <!-- Ações da Direita -->
            <div class="nav-acoes">
                <!-- Botão de Configuração do Chrome (.bat) -->
                <a href="/download/configurar-chrome" class="btn-chrome-pill" title="Baixar script para abrir o Google Chrome/Edge com WebRTC, microfone e compartilhamento liberados">
                    <span>⚡</span> Configurar Chrome (.bat)
                </a>

                <!-- Botão de Alternar Som/Silenciar Notificações -->
                <button type="button" id="btn-toggle-sound" class="btn-sound-toggle" title="Notificações sonoras. Clique para silenciar/ativar.">
                    <span>🔔</span> <small>Sons ON</small>
                </button>

                <?php if (Auth::check()): ?>
                    <div class="nav-user-cluster">
                        <a href="/hub" class="nav-user-pill" title="Ir para Meu Hub">
                            <div class="nav-user-avatar">
                                <?= strtoupper(substr($loggedUser['name'] ?? 'U', 0, 2)) ?>
                                <span class="nav-user-status-dot"></span>
                            </div>
                            <div class="nav-user-meta">
                                <strong class="nav-user-name"><?= htmlspecialchars(explode(' ', $loggedUser['name'])[0]) ?></strong>
                                <span class="nav-user-role"><?= htmlspecialchars($loggedUser['role'] ?? 'Aluno') ?></span>
                            </div>
                        </a>
                        <form method="POST" action="/sair" class="nav-logout-form">
                            <button type="submit" class="btn-nav-logout" title="Sair da Conta">
                                <span>🚪</span> Sair
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <a href="/entrar" class="botao botao-secundario nav-auth-btn">Entrar</a>
                    <a href="/cadastro" class="botao botao-primario nav-auth-btn">Criar Conta</a>
                <?php endif; ?>

                <!-- Botão Menu Mobile -->
                <button type="button" class="btn-mobile-toggle" id="btn-mobile-toggle" aria-label="Abrir Menu">
                    <span></span><span></span><span></span>
                </button>
            </div>

        </div>

        <!-- Menu Mobile Drawer -->
        <div class="mobile-nav-drawer" id="mobile-nav-drawer">
            <ul class="mobile-nav-links">
                <li><a href="/" class="<?= ($currentUri === '/') ? 'ativo' : '' ?>">🏠 Início</a></li>
                <?php if (Auth::check()): ?>
                    <li><a href="/hub" class="<?= str_starts_with($currentUri, '/hub') ? 'ativo' : '' ?>">🧭 Hub de Comunidades</a></li>
                    <li><a href="/privado" class="<?= str_starts_with($currentUri, '/privado') ? 'ativo' : '' ?>">💬 Mensagens & Amigos</a></li>
                <?php endif; ?>
                <li><a href="/sobre">ℹ️ Sobre Nós</a></li>
                <li><a href="/termos">📜 Termos de Uso</a></li>
                <li><a href="/privacidade">🔒 Privacidade</a></li>
            </ul>
            <div class="mobile-drawer-footer">
                <a href="/download/configurar-chrome" class="btn-chrome-pill" style="width: 100%; justify-content: center; margin-bottom: 12px;">
                    <span>⚡</span> Configurar Chrome (.bat)
                </a>
                <?php if (!Auth::check()): ?>
                    <div style="display:flex; gap:8px;">
                        <a href="/entrar" class="botao botao-secundario" style="flex:1; text-align:center;">Entrar</a>
                        <a href="/cadastro" class="botao botao-primario" style="flex:1; text-align:center;">Cadastrar</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- MODAL FLUTUANTE DE PERFIL RÁPIDO DO COLEGA -->
    <div id="user-profile-popover" class="image-lightbox-modal" style="display:none; z-index:999999;">
        <div class="lightbox-overlay" onclick="window.closeUserProfilePopover()"></div>
        <div class="lightbox-content animate-zoom-in modal-card-box user-popover-card" style="max-width: 400px; text-align:center; padding: 26px 22px;">
            <button type="button" class="btn-lightbox-close" onclick="window.closeUserProfilePopover()">✕</button>
            
            <div class="user-popover-avatar-wrap">
                <div id="popover-avatar" class="user-popover-avatar">GP</div>
                <span class="user-popover-online-badge"></span>
            </div>

            <h3 id="popover-name" class="user-popover-name">Nome do Colega</h3>
            <p id="popover-email" class="user-popover-email">email@fasm.edu.br</p>

            <div class="user-popover-badges">
                <span id="popover-role" class="badge-role-tag">Aluno</span>
                <span id="popover-karma" class="badge-karma-chip">🥉 Iniciante</span>
            </div>

            <div class="user-popover-actions">
                <button type="button" id="popover-btn-friend" class="botao botao-primario" style="width:100%; justify-content:center; margin-bottom:8px;">
                    ➕ Adicionar Amigo
                </button>
                <div style="display:flex; gap:10px; width:100%;">
                    <button type="button" id="popover-btn-chat" class="botao botao-secundario" style="flex:1; justify-content:center;">
                        💬 Mensagem
                    </button>
                    <button type="button" id="popover-btn-call" class="botao botao-secundario" style="flex:1; justify-content:center; border-color: rgba(78,205,196,0.4); color:#4ecdc4;">
                        📞 Ligar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DE LEADERBOARD / RANKING ACADÊMICO GLOBAL -->
    <div id="leaderboard-modal" class="image-lightbox-modal" style="display:none; z-index:999999;">
        <div class="lightbox-overlay" onclick="window.closeLeaderboardModal()"></div>
        <div class="lightbox-content animate-zoom-in modal-card-box" style="max-width: 680px; padding: 24px;">
            <div class="lightbox-header">
                <span class="lightbox-title">🏆 Ranking Acadêmico & Top Monitores</span>
                <button type="button" class="btn-lightbox-close" onclick="window.closeLeaderboardModal()">✕</button>
            </div>
            <div id="leaderboard-list" style="padding: 20px 0;">
                <!-- Preenchido via gamification.js -->
            </div>
        </div>
    </div>

    <!-- MODAL DE AJUDA: CONFIGURAR CHROME FLAGS -->
    <div id="chrome-flags-modal" class="image-lightbox-modal" style="display:none; z-index:999999;">
        <div class="lightbox-overlay" onclick="document.getElementById('chrome-flags-modal').style.display='none'"></div>
        <div class="lightbox-content animate-zoom-in modal-card-box" style="max-width: 580px; padding: 26px;">
            <div class="lightbox-header">
                <span class="lightbox-title">⚡ Liberar Microfone, Câmera e Tela no Chrome/Edge</span>
                <button type="button" class="btn-lightbox-close" onclick="document.getElementById('chrome-flags-modal').style.display='none'">✕</button>
            </div>
            <div style="padding: 18px 0; font-size: 13.5px; line-height: 1.6; color: #cbd5e1;">
                <p>Em conexões de rede local ou VPN (HTTP), o Chrome bloqueia o compartilhamento de tela e microfone até que a origem seja marcada como segura.</p>
                
                <div style="background: rgba(78, 205, 196, 0.08); border: 1px solid rgba(78, 205, 196, 0.3); border-radius: 8px; padding: 14px; margin: 16px 0;">
                    <strong style="color: #4ecdc4; display:block; margin-bottom: 6px;">Opção 1: Automática via Arquivo .BAT (Recomendado)</strong>
                    <a href="/download/configurar-chrome" class="botao botao-primario" style="display:inline-flex; align-items:center; gap:8px; padding:8px 16px; margin-top:4px;">
                        <span>⚡</span> Baixar Script .BAT (Abre Direto no chrome://flags)
                    </a>
                    <span style="font-size:11px; display:block; margin-top:6px; color:var(--cor-apagado);">O script copia a URL para seu Ctrl+V e abre o Chrome na página correta da flag!</span>
                </div>

                <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid var(--cor-borda); border-radius: 8px; padding: 14px;">
                    <strong style="color: #fff; display:block; margin-bottom: 6px;">Opção 2: Manual no Navegador</strong>
                    <?php $detectedHost = 'http://' . ($_SERVER['HTTP_HOST'] ?? '26.153.185.191:8000'); ?>
                    <ol style="margin-left: 20px; margin-top: 6px; display:flex; flex-direction:column; gap:6px;">
                        <li>Abra uma nova aba e digite: <code style="color:#4ecdc4;">chrome://flags</code></li>
                        <li>Pesquise por: <strong style="color:#fff;">Insecure origins treated as secure</strong></li>
                        <li>No campo de texto, cole o endereço: <code id="copy-url-sample" style="color:#10b981; background:rgba(0,0,0,0.4); padding:2px 6px; border-radius:4px;"><?= htmlspecialchars($detectedHost) ?></code> <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($detectedHost) ?>'); alert('Endereço copiado: <?= htmlspecialchars($detectedHost) ?>');" style="cursor:pointer; background:none; border:none; color:#4ecdc4; font-size:12px; font-weight:bold;">[Copiar]</button></li>
                        <li>Mude de <strong>Default</strong> para <strong>Enabled</strong> e clique em <strong>Relaunch (Reiniciar)</strong>.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Script de Interação do Cabeçalho e Perfil Rápido -->
    <script>
    (function() {
        const mobileToggle = document.getElementById('btn-mobile-toggle');
        const mobileDrawer = document.getElementById('mobile-nav-drawer');

        if (mobileToggle && mobileDrawer) {
            mobileToggle.addEventListener('click', () => {
                mobileToggle.classList.toggle('open');
                mobileDrawer.classList.toggle('open');
            });
        }

        let currentPopoverUserId = null;

        window.openUserProfile = async function(userId, userName, userRole, badgeTitle, userEmail) {
            currentPopoverUserId = parseInt(userId, 10);
            const myId = parseInt(document.body.getAttribute('data-user-id') || '0', 10);
            const modal = document.getElementById('user-profile-popover');
            if (!modal) return;

            document.getElementById('popover-avatar').textContent = (userName || 'U').substring(0, 2).toUpperCase();
            document.getElementById('popover-name').textContent = userName || 'Colega';
            document.getElementById('popover-email').textContent = userEmail || 'Aluno FASM';
            document.getElementById('popover-role').textContent = userRole || 'Aluno';
            document.getElementById('popover-karma').textContent = badgeTitle || '🥉 Iniciante';

            const btnFriend = document.getElementById('popover-btn-friend');
            const btnChat = document.getElementById('popover-btn-chat');
            const btnCall = document.getElementById('popover-btn-call');

            // Se for o próprio usuário
            if (currentPopoverUserId === myId) {
                btnFriend.style.display = 'none';
                btnChat.style.display = 'none';
                btnCall.style.display = 'none';
            } else {
                btnFriend.style.display = 'block';
                btnChat.style.display = 'block';
                btnCall.style.display = 'block';

                btnFriend.disabled = false;
                btnFriend.textContent = '➕ Adicionar Amigo';
                btnFriend.style.background = '';
                btnFriend.onclick = () => window.sendFriendRequestFromPopover(currentPopoverUserId);

                btnChat.onclick = () => {
                    window.location.href = `/privado?amigo=${currentPopoverUserId}`;
                };

                btnCall.onclick = () => {
                    window.location.href = `/privado?amigo=${currentPopoverUserId}&startCall=1`;
                };
            }

            modal.style.display = 'flex';
        };

        window.closeUserProfilePopover = function() {
            const modal = document.getElementById('user-profile-popover');
            if (modal) modal.style.display = 'none';
        };

        window.sendFriendRequestFromPopover = async function(userId) {
            const btn = document.getElementById('popover-btn-friend');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Enviando pedido...';
            }
            try {
                const res = await fetch('/api/private/friends/request', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ friend_id: userId })
                });
                const data = await res.json();
                if (btn) {
                    btn.textContent = data.message || '✓ Solicitação Enviada!';
                    btn.style.background = '#10b981';
                }
            } catch (e) {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Erro ao adicionar';
                }
            }
        };

        // Delegação de Clique Global para Abrir Perfil do Colega
        document.addEventListener('click', function(e) {
            const trigger = e.target.closest('.user-popover-trigger, .online-user-item, .msg-autor, .msg-avatar');
            if (trigger) {
                // Não dispara se for um botão dentro do avatar
                if (e.target.tagName === 'BUTTON' || e.target.closest('button')) return;

                const uId = trigger.getAttribute('data-user-id') || trigger.dataset.userId;
                if (!uId) return;

                const uName = trigger.getAttribute('data-user-name') || trigger.dataset.userName || trigger.querySelector('.online-nome')?.textContent.trim() || trigger.textContent.trim();
                const uRole = trigger.getAttribute('data-user-role') || trigger.dataset.userRole || 'Aluno';
                const uEmail = trigger.getAttribute('data-user-email') || trigger.dataset.userEmail || '';
                window.openUserProfile(uId, uName, uRole, '🥉 Colega', uEmail);
            }
        });
    })();
    </script>

    <!-- Alertas Flash -->
    <?php if ($sucessoMsg): ?>
        <div class="container flash-alert-container">
            <div class="alerta alerta-sucesso animate-slide-down">
                <span>✓</span> <?= htmlspecialchars($sucessoMsg) ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($erroMsg): ?>
        <div class="container flash-alert-container">
            <div class="alerta alerta-erro animate-slide-down">
                <span>⚠</span> <?= htmlspecialchars($erroMsg) ?>
            </div>
        </div>
    <?php endif; ?>

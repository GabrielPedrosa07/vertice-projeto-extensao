<?php
/**
 * View de Mensagens Privadas (1x1), Amigos e Chamadas Diretas — Vértice
 */
$pageTitle = 'Mensagens Privadas & Amigos — Vértice';
require __DIR__ . '/../layouts/header.php';
?>

<div class="container" style="max-width: 1200px; padding: 95px 16px 50px;">

    <div class="private-chat-layout">

        <!-- BARRA LATERAL ESQUERDA: CONVERSAS E AMIGOS -->
        <aside class="private-sidebar">
            <div class="private-sidebar-header">
                <div class="private-user-profile">
                    <div class="private-avatar-me">
                        <?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?>
                        <span class="status-indicator-dot online"></span>
                    </div>
                    <div class="private-me-info">
                        <strong class="private-me-name"><?= htmlspecialchars($user['name'] ?? '') ?></strong>
                        <span class="private-me-role"><?= htmlspecialchars($user['role'] ?? 'student') ?></span>
                    </div>
                </div>
            </div>

            <!-- Navegação de Abas da Sidebar -->
            <div class="private-tabs-nav">
                <button type="button" class="btn-private-nav active" data-tab="tab-conversations">
                    <span>💬</span> Conversas
                </button>
                <button type="button" class="btn-private-nav" data-tab="tab-friends">
                    <span>👥</span> Amigos (<span id="friends-count"><?= count($friends) ?></span>)
                </button>
                <button type="button" class="btn-private-nav" data-tab="tab-pending">
                    <span>📥</span> Pedidos (<span id="pending-count"><?= count($pending) ?></span>)
                </button>
                <button type="button" class="btn-private-nav" data-tab="tab-add-friend">
                    <span>➕</span> Adicionar
                </button>
            </div>

            <!-- ABA 1: CONVERSAS RECENTES -->
            <div id="tab-conversations" class="private-tab-content active">
                <div class="private-conversations-list" id="conversations-list">
                    <?php if (!empty($conversations)): ?>
                        <?php foreach ($conversations as $conv): ?>
                            <div class="private-conv-item <?= (($activeFriend['id'] ?? 0) == $conv['friend_id']) ? 'active' : '' ?>" 
                                 onclick="window.selectConversation(<?= $conv['friend_id'] ?>, '<?= htmlspecialchars($conv['friend_name']) ?>', '<?= htmlspecialchars($conv['badge_title']) ?>')">
                                <div class="private-conv-avatar">
                                    <?= strtoupper(substr($conv['friend_name'], 0, 2)) ?>
                                    <span class="status-indicator-dot online"></span>
                                </div>
                                <div class="private-conv-details">
                                    <div class="private-conv-top">
                                        <strong class="private-conv-name"><?= htmlspecialchars($conv['friend_name']) ?></strong>
                                        <?php if (!empty($conv['last_time'])): ?>
                                            <span class="private-conv-time"><?= date('H:i', strtotime($conv['last_time'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="private-conv-bottom">
                                        <span class="private-conv-snippet"><?= htmlspecialchars(substr($conv['last_message'], 0, 38)) ?><?= strlen($conv['last_message']) > 38 ? '...' : '' ?></span>
                                        <?php if (($conv['unread_count'] ?? 0) > 0): ?>
                                            <span class="unread-badge"><?= $conv['unread_count'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="private-empty-sidebar">
                            <span>💬</span>
                            <p>Nenhuma conversa ativa no momento. Clique na aba <strong>👥 Amigos</strong> para iniciar um bate-papo!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ABA 2: LISTA DE AMIGOS -->
            <div id="tab-friends" class="private-tab-content" style="display:none;">
                <div class="private-search-wrap">
                    <input type="text" id="filter-friends-input" class="private-search-input" placeholder="Filtrar amigos...">
                </div>
                <div class="private-friends-list" id="friends-list">
                    <?php if (!empty($friends)): ?>
                        <?php foreach ($friends as $f): ?>
                            <div class="private-friend-item" data-name="<?= strtolower(htmlspecialchars($f['name'])) ?>">
                                <div class="private-conv-avatar">
                                    <?= strtoupper(substr($f['name'], 0, 2)) ?>
                                    <span class="status-indicator-dot online"></span>
                                </div>
                                <div class="private-friend-info">
                                    <strong><?= htmlspecialchars($f['name']) ?></strong>
                                    <span class="badge-karma-chip" style="font-size:9px; padding:1px 6px;"><?= htmlspecialchars($f['badge_title']) ?></span>
                                </div>
                                <div class="private-friend-actions">
                                    <button type="button" class="btn-friend-action" title="Abrir Chat" onclick="window.selectConversation(<?= $f['id'] ?>, '<?= htmlspecialchars($f['name']) ?>', '<?= htmlspecialchars($f['badge_title']) ?>')">
                                        💬
                                    </button>
                                    <button type="button" class="btn-friend-action" title="Iniciar Chamada Privada" onclick="window.startPrivateCall(<?= $f['id'] ?>, '<?= htmlspecialchars($f['name']) ?>')">
                                        📞
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="private-empty-sidebar">
                            <span>👥</span>
                            <p>Você ainda não possui amigos adicionados. Use a aba <strong>➕ Adicionar</strong> para conectar-se com colegas da FASM!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ABA 3: SOLICITAÇÕES PENDENTES -->
            <div id="tab-pending" class="private-tab-content" style="display:none;">
                <div class="private-pending-list" id="pending-requests-list">
                    <?php if (!empty($pending)): ?>
                        <?php foreach ($pending as $p): ?>
                            <div class="private-pending-item" id="pending-req-<?= $p['id'] ?>">
                                <div class="private-conv-avatar">
                                    <?= strtoupper(substr($p['name'], 0, 2)) ?>
                                </div>
                                <div class="private-pending-info">
                                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                                    <span style="font-size:11px; color:var(--cor-apagado);"><?= htmlspecialchars($p['email']) ?></span>
                                </div>
                                <div class="private-pending-actions">
                                    <button type="button" class="btn-accept-friend" onclick="window.respondFriendRequest(<?= $p['id'] ?>, true)" title="Aceitar">✓</button>
                                    <button type="button" class="btn-decline-friend" onclick="window.respondFriendRequest(<?= $p['id'] ?>, false)" title="Recusar">✕</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="private-empty-sidebar">
                            <span>✨</span>
                            <p>Nenhuma solicitação de amizade pendente.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ABA 4: ADICIONAR AMIGO -->
            <div id="tab-add-friend" class="private-tab-content" style="display:none;">
                <div class="add-friend-box">
                    <h4 style="font-size:13px; font-weight:800; color:#fff; margin-bottom:6px;">Conectar com Aluno ou Monitor</h4>
                    <p style="font-size:11px; color:var(--cor-apagado); margin-bottom:12px;">Busque pelo nome ou informe o e-mail institucional @fasm.edu.br</p>
                    <div style="display:flex; gap:8px;">
                        <input type="text" id="search-user-input" class="private-search-input" placeholder="Nome ou e-mail...">
                        <button type="button" id="btn-search-user" class="botao botao-primario" style="padding:6px 12px; font-size:12px;">Buscar</button>
                    </div>

                    <div id="search-users-results" class="search-results-list" style="margin-top:14px;"></div>
                </div>
            </div>
        </aside>

        <!-- ÁREA PRINCIPAL: CHAT DIRETO & CHAMADA PRIVADA -->
        <main class="private-chat-main">

            <!-- Estado Vazio (Nenhum amigo selecionado) -->
            <div id="private-empty-state" class="private-main-empty" style="<?= $activeFriend ? 'display:none;' : '' ?>">
                <div class="empty-state-card">
                    <span style="font-size: 58px; display:block; margin-bottom: 12px;">💬</span>
                    <h3>Suas Mensagens Privadas</h3>
                    <p>Converse diretamente com seus colegas de classe, tire dúvidas no privado ou inicie uma chamada de áudio e vídeo com compartilhamento de tela.</p>
                    <button type="button" class="botao botao-primario" onclick="document.querySelector('[data-tab=\'tab-friends\']').click()">
                        👥 Ver Lista de Amigos
                    </button>
                </div>
            </div>

            <!-- Painel Ativo de Conversa -->
            <div id="private-active-panel" class="private-active-panel" style="<?= $activeFriend ? 'display:flex;' : 'display:none;' ?>">
                
                <!-- Cabeçalho do Chat Privado -->
                <div class="private-chat-header">
                    <div class="private-header-user">
                        <div class="private-header-avatar" id="active-friend-avatar">
                            <?= $activeFriend ? strtoupper(substr($activeFriend['name'], 0, 2)) : 'U' ?>
                        </div>
                        <div>
                            <h3 class="private-header-name" id="active-friend-name"><?= htmlspecialchars($activeFriend['name'] ?? '') ?></h3>
                            <div class="private-header-meta">
                                <span class="status-text-online">🟢 Online</span>
                                <span id="active-friend-badge" class="badge-karma-chip" style="font-size:10px; padding:1px 6px;"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="private-header-actions" style="display:flex; align-items:center; gap:8px;">
                        <a href="/download/configurar-chrome" class="btn-chrome-pill" style="padding:6px 12px; font-size:12px;" title="Baixar script para abrir o Chrome/Edge com WebRTC e tela liberados">
                            <span>⚡</span> Configurar Chrome (.bat)
                        </a>
                        <button type="button" id="btn-start-call" class="botao botao-primario" style="padding:7px 16px; font-size:13px;" title="Iniciar chamada de áudio e vídeo com este amigo">
                            <span>📞</span> Ligar no Privado
                        </button>
                        <button type="button" id="btn-invite-community" class="botao botao-secundario" style="padding:7px 14px; font-size:13px;" title="Convidar amigo para uma comunidade/sala de aula">
                            <span>🚀</span> Convidar para Sala
                        </button>
                    </div>
                </div>

                <!-- OVERLAY DA CHAMADA PRIVADA (WEBRTC) -->
                <div id="private-call-screen" class="private-call-screen" style="display:none;">
                    <div class="private-call-header">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span id="private-call-status-label" class="private-call-status">Conectando chamada privada...</span>
                            <div class="private-call-timer" id="private-call-timer">00:00</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <button type="button" class="btn-priv-layout-toggle" onclick="window.togglePrivateCallLayout()" title="Alternar entre visualização Teatro (PiP) e Lado a Lado">
                                <span>🔄</span> <span id="priv-layout-text">Lado a Lado</span>
                            </button>
                            <button type="button" class="btn-priv-chat-toggle" onclick="window.togglePrivateChatUnderCall()" title="Mostrar ou ocultar o chat de texto durante a chamada">
                                <span>💬</span> <span id="priv-chat-toggle-text">Ocultar Chat</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="private-call-videos-grid">
                        <div class="video-card-priv" id="remote-video-priv-card" ondblclick="window.fullscreenPrivateTile('remote-video-priv-card')">
                            <video id="private-remote-video" autoplay playsinline></video>
                            <div id="remote-priv-avatar" class="tile-avatar-placeholder" style="display:flex;">
                                <div class="tile-avatar-circle" id="remote-priv-avatar-initials">👥</div>
                                <span class="tile-avatar-desc" id="remote-priv-avatar-desc">Chamando colega...</span>
                            </div>
                            <span class="video-label-priv" id="remote-video-priv-label">Colega</span>
                            <button type="button" class="btn-tile-fullscreen-priv" title="Tela Cheia (Monitor)" onclick="window.fullscreenPrivateTile('remote-video-priv-card')">⤢ Tela Cheia</button>
                        </div>
                        <div class="video-card-priv video-local-priv" id="local-video-priv-card" ondblclick="window.fullscreenPrivateTile('local-video-priv-card')">
                            <video id="private-local-video" autoplay playsinline muted></video>
                            <div id="local-priv-avatar" class="tile-avatar-placeholder" style="display:none;">
                                <div class="tile-avatar-circle" id="local-priv-avatar-initials">EU</div>
                                <span class="tile-avatar-desc">Sua Câmera</span>
                            </div>
                            <span class="video-label-priv">Você</span>
                            <button type="button" class="btn-tile-fullscreen-priv" title="Tela Cheia (Monitor)" onclick="window.fullscreenPrivateTile('local-video-priv-card')">⤢ Tela Cheia</button>
                        </div>
                    </div>

                    <div class="private-call-controls">
                        <button type="button" id="btn-priv-mic" class="btn-call-ctrl" title="Mutar/Desmutar Microfone">🎤</button>
                        <button type="button" id="btn-priv-cam" class="btn-call-ctrl" title="Ligar/Desligar Câmera">📷</button>
                        <button type="button" id="btn-priv-screen" class="btn-call-ctrl" title="Compartilhar Tela">🖥️ Compartilhar</button>
                        <button type="button" id="btn-priv-hangup" class="btn-call-ctrl btn-call-hangup" title="Desligar Chamada">🔴 Encerrar</button>
                    </div>
                </div>

                <!-- Feed de Mensagens Privadas -->
                <div class="private-messages-feed" id="private-feed">
                    <!-- Preenchido via private_chat.js -->
                </div>

                <!-- Prévia de Anexo / Imagem -->
                <div id="private-att-preview" class="chat-attachment-preview" style="display:none;">
                    <span id="private-att-name" class="attachment-preview-name"></span>
                    <button type="button" id="private-att-remove">✕</button>
                </div>

                <!-- HUD de Gravação de Áudio -->
                <div id="private-audio-hud" class="audio-record-hud" style="display:none;">
                    <div class="audio-record-status">
                        <span class="record-pulse-dot"></span>
                        <span class="record-label">Gravando Áudio Privado:</span>
                        <span id="private-audio-timer" class="record-timer">00:00</span>
                    </div>
                    <div class="audio-record-actions">
                        <button type="button" id="btn-cancel-priv-audio" class="btn-record-cancel">✕ Cancelar</button>
                        <button type="button" id="btn-send-priv-audio" class="btn-record-send">✓ Enviar Áudio</button>
                    </div>
                </div>

                <!-- Barra de Envio de Mensagem Privada -->
                <div class="private-input-area">
                    <form id="private-message-form" class="chat-form">
                        <input type="file" id="private-file-input" style="display:none;">
                        
                        <div class="chat-input-wrapper">
                            <textarea id="private-textarea" placeholder="Escreva uma mensagem privada ou cole código... [Enter para enviar]" class="chat-textarea" rows="2"></textarea>
                            
                            <div class="chat-form-opcoes">
                                <div class="chat-opcoes-esquerda">
                                    <button type="button" id="btn-priv-record-audio" class="btn-chat-tool btn-chat-audio" title="Gravar áudio privado">
                                        🎙️ Gravar Áudio
                                    </button>
                                    <button type="button" class="btn-chat-tool" onclick="document.getElementById('private-file-input').click()" title="Anexar arquivo ou imagem">
                                        📎 Anexar
                                    </button>
                                    <label class="checkbox-label" title="Formatar como código">
                                        <input type="checkbox" id="private-is-code">
                                        <span>💻 Código</span>
                                    </label>
                                    <select id="private-code-lang" class="chat-lang-select" style="display:none;">
                                        <option value="PHP">PHP</option>
                                        <option value="JavaScript">JavaScript</option>
                                        <option value="Python">Python</option>
                                        <option value="SQL">SQL</option>
                                        <option value="C/C++">C/C++</option>
                                    </select>
                                </div>

                                <button type="submit" class="botao botao-primario" style="padding: 8px 18px; font-size: 13px;">
                                    Enviar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>

        </main>

    </div>

</div>

<!-- MODAL DE CONVIDAR AMIGO PARA MATÉRIA -->
<div id="invite-community-modal" class="image-lightbox-modal" style="display:none;">
    <div class="lightbox-overlay" onclick="document.getElementById('invite-community-modal').style.display='none'"></div>
    <div class="lightbox-content animate-zoom-in modal-card-box" style="max-width: 500px;">
        <div class="lightbox-header">
            <span class="lightbox-title">🚀 Convidar Amigo para Matéria</span>
            <button type="button" class="btn-lightbox-close" onclick="document.getElementById('invite-community-modal').style.display='none'">✕</button>
        </div>
        <div style="padding: 20px;">
            <p style="font-size:12px; color:var(--cor-apagado); margin-bottom: 14px;">
                Selecione a disciplina e a sala de estudos para convidar seu amigo:
            </p>
            <div style="margin-bottom: 14px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#fff; margin-bottom:6px;">Disciplina / Comunidade:</label>
                <select id="invite-category-select" class="tool-select" style="width:100%;">
                    <option value="1">Banco de Dados</option>
                    <option value="2">Algoritmos e Estrutura de Dados</option>
                    <option value="3">Engenharia de Software</option>
                    <option value="4">Redes de Computadores</option>
                    <option value="5">Desenvolvimento Web</option>
                </select>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top: 20px;">
                <button type="button" class="botao botao-secundario" onclick="document.getElementById('invite-community-modal').style.display='none'">Cancelar</button>
                <button type="button" id="btn-confirm-send-invite" class="botao botao-primario">Enviar Convite</button>
            </div>
        </div>
    </div>
</div>

<script src="/public/js/private_chat.js"></script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

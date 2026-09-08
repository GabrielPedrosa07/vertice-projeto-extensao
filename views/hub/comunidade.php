<?php
$pageTitle = htmlspecialchars($comunidade['name']) . ' — Vértice';
require __DIR__ . '/../layouts/header.php';
?>

<div class="container" style="padding-top: 110px; padding-bottom: 80px;">

    <!-- Cabeçalho da Comunidade -->
    <div class="comunidade-header" style="border-left: 5px solid <?= htmlspecialchars($comunidade['color'] ?? '#5b6abf') ?>;">
        <div class="com-header-info">
            <div style="display:flex; align-items:center; gap:14px;">
                <span style="font-size:38px; line-height: 1;"><?= htmlspecialchars($comunidade['icon'] ?? '📚') ?></span>
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <h1 class="com-header-nome"><?= htmlspecialchars($comunidade['name']) ?></h1>
                        <span id="call-status-badge" class="badge-call-live" style="display:none;">
                            <span class="live-dot-pulse"></span> Chamada Ativa
                        </span>
                    </div>
                    <p class="com-header-desc"><?= htmlspecialchars($comunidade['description'] ?? '') ?></p>
                </div>
            </div>
        </div>

        <div class="com-header-acoes">
            <?php if ($isMember): ?>
                <button type="button" id="btn-join-call" class="botao botao-call-destaque">
                    <span>📞</span> Entrar na Call da Matéria
                </button>
                <form method="POST" action="/comunidade/<?= urlencode($comunidade['slug']) ?>/sair" onsubmit="return confirm('Deseja realmente sair desta comunidade?');" style="display:inline; margin:0;">
                    <button type="submit" class="botao botao-sair" style="padding: 8px 14px; font-size: 12px;">Sair</button>
                </form>
            <?php else: ?>
                <form method="POST" action="/comunidade/<?= urlencode($comunidade['slug']) ?>/entrar" style="display:inline; margin:0;">
                    <button type="submit" class="botao botao-primario" style="padding: 9px 18px; font-size: 13px;">+ Participar da Comunidade</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- SALA DE CHAMADA DE VÍDEO & ÁUDIO (WebRTC) -->
    <div id="call-container" class="call-room-panel animate-fade-in" style="display:none; position:relative;">
        <!-- HUD de Notificações Visuais da Chamada (Entrada, Mão Levantada, Tela) -->
        <div id="call-toast-container" class="call-toast-container"></div>

        <div class="call-room-header">
            <div class="call-header-left">
                <span class="live-indicator">● AO VIVO</span>
                <h3 class="call-header-h3">Sala: <?= htmlspecialchars($canalAtivo['name'] ?? 'Geral') ?></h3>
                <span id="call-participants-count" class="call-badge-count">1 conectado</span>
                <button type="button" id="btn-toggle-call-dock" class="btn-dock-toggle" onclick="window.toggleCallDockMode()" title="Alternar entre Grade de Vídeo e Barra Compacta">
                    <span id="dock-toggle-icon">📌</span> <span id="dock-toggle-text">Modo Barra (Dock)</span>
                </button>
            </div>
            <div class="call-header-right">
                <a href="/download/configurar-chrome" class="btn-download-agent-pill" style="border-color: rgba(78, 205, 196, 0.5); color: #4ecdc4;" title="Baixar script .bat para configurar o Google Chrome com WebRTC e tela liberados">
                    <span>⚡</span> Configurar Chrome (.bat)
                </a>
                <a href="/download/remote-agent" class="btn-download-agent-pill" title="Baixar Agente Windows (.bat) para permitir cliques reais no VS Code e desktop">
                    <span>⬇️</span> Baixar Agente (.bat)
                </a>
                <span class="call-hint-tag">💡 Dê dois cliques em qualquer vídeo para Tela Cheia</span>
            </div>
        </div>

        <!-- Grade Dinâmica de Vídeos -->
        <div id="call-video-grid" class="call-grid grid-1">
            <!-- Câmeras dos participantes são inseridas aqui pelo call.js -->
        </div>

        <!-- Barra Flutuante de Controle Remoto Ativo -->
        <div id="remote-control-active-bar" class="remote-active-bar" style="display:none;">
            <div class="remote-active-info">
                <span class="remote-pulse-dot"></span>
                <span>🎮 <strong id="remote-controller-name">Colega</strong> está controlando seu computador</span>
            </div>
            <div class="remote-active-actions">
                <a href="/download/remote-agent" class="btn-download-agent-mini" title="Baixar o arquivo .bat se ainda não tiver">⬇️ Baixar Agente (.bat)</a>
                <button type="button" class="btn-revoke-remote" onclick="window.revokeRemoteControl()">🛑 Encerrar Controle</button>
            </div>
        </div>

        <!-- Barra de Controles da Call (Estilo Discord / Google Meet) -->
        <div class="call-controls-bar">
            <div class="call-controls-center">
                <!-- Microfone -->
                <button type="button" id="btn-toggle-mic" class="btn-call-circle" title="Ligar/Desligar Microfone">
                    <span class="btn-call-icon" id="btn-mic-icon">🎙️</span>
                    <span class="btn-call-label">Mic</span>
                </button>
                
                <!-- Câmera -->
                <button type="button" id="btn-toggle-cam" class="btn-call-circle" title="Ligar/Desligar Câmera">
                    <span class="btn-call-icon" id="btn-cam-icon">📹</span>
                    <span class="btn-call-label">Câmera</span>
                </button>
                
                <!-- Compartilhar Tela -->
                <button type="button" id="btn-share-screen" class="btn-call-circle" title="Compartilhar Tela">
                    <span class="btn-call-icon">🖥️</span>
                    <span class="btn-call-label">Tela</span>
                </button>

                <!-- Controle Remoto AnyDesk -->
                <button type="button" id="btn-remote-control" class="btn-call-circle btn-remote-ctrl" title="Solicitar Controle Remoto estilo AnyDesk">
                    <span class="btn-call-icon">🎮</span>
                    <span class="btn-call-label">Controle</span>
                </button>

                <!-- Levantar a Mão -->
                <button type="button" id="btn-raise-hand" class="btn-call-circle" title="Levantar a Mão / Pedir a Palavra">
                    <span class="btn-call-icon">✋</span>
                    <span class="btn-call-label">Pedir Fala</span>
                </button>

                <!-- Reações Rápidas -->
                <div class="call-reactions-popover-wrap">
                    <button type="button" id="btn-toggle-reactions" class="btn-call-circle" title="Enviar Reação">
                        <span class="btn-call-icon">😀</span>
                        <span class="btn-call-label">Reagir</span>
                    </button>
                    <div id="call-reactions-menu" class="call-reactions-menu" style="display:none;">
                        <button type="button" class="btn-reaction-item" onclick="window.sendCallReaction('👏')" title="Palmas">👏</button>
                        <button type="button" class="btn-reaction-item" onclick="window.sendCallReaction('❤️')" title="Amei">❤️</button>
                        <button type="button" class="btn-reaction-item" onclick="window.sendCallReaction('💡')" title="Ideia genial">💡</button>
                        <button type="button" class="btn-reaction-item" onclick="window.sendCallReaction('🚀')" title="Foguete">🚀</button>
                        <button type="button" class="btn-reaction-item" onclick="window.sendCallReaction('💻')" title="Código">💻</button>
                        <button type="button" class="btn-reaction-item" onclick="window.sendCallReaction('🔥')" title="Fogo">🔥</button>
                    </div>
                </div>

                <!-- Grade de Vídeo -->
                <button type="button" id="btn-call-videogrid" class="btn-call-circle btn-call-tool active-tool" onclick="window.restoreVideoGrid()" title="Visualizar Grade Completa de Câmeras">
                    <span class="btn-call-icon">📹</span>
                    <span class="btn-call-label">Vídeos</span>
                </button>

                <!-- Live Code na Call -->
                <button type="button" id="btn-call-live-code" class="btn-call-circle btn-call-tool" onclick="window.openInCallTool('panel-live-code')" title="Abrir Editor de Código Colaborativo">
                    <span class="btn-call-icon">💻</span>
                    <span class="btn-call-label">Live Code</span>
                </button>

                <!-- Whiteboard na Call -->
                <button type="button" id="btn-call-whiteboard" class="btn-call-circle btn-call-tool" onclick="window.openInCallTool('panel-whiteboard')" title="Abrir Lousa Digital Colaborativa">
                    <span class="btn-call-icon">🎨</span>
                    <span class="btn-call-label">Lousa</span>
                </button>

                <!-- Tela Cheia -->
                <button type="button" id="btn-fullscreen-call" class="btn-call-circle" title="Modo Tela Cheia">
                    <span class="btn-call-icon">⤢</span>
                    <span class="btn-call-label">Expandir</span>
                </button>

                <!-- Sair da Chamada -->
                <button type="button" id="btn-leave-call" class="btn-call-circle btn-leave-circle" title="Desconectar da Chamada">
                    <span class="btn-call-icon">🛑</span>
                    <span class="btn-call-label">Sair</span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL DE SOLICITAÇÃO DE CONTROLE REMOTO (ANYDESK) -->
    <div id="remote-control-modal" class="remote-modal-backdrop" style="display:none;">
        <div class="remote-modal-card animate-zoom-in">
            <div class="remote-modal-icon">🎮</div>
            <h3>Solicitação de Controle Remoto</h3>
            <p><strong id="remote-requester-name">Colega</strong> quer controlar seu computador para ajudar no código.</p>
            <p class="remote-modal-warning">⚠️ Ele poderá mover o mouse e digitar na sua tela compartilhada.</p>
            
            <div style="margin: 12px 0; padding: 10px; background: rgba(255,255,255,0.04); border: 1px solid var(--cor-borda); border-radius: 8px; text-align: center;">
                <span style="font-size: 11px; color: var(--cor-apagado); display: block; margin-bottom: 6px;">Para liberar cliques reais no VS Code e desktop:</span>
                <a href="/download/remote-agent" class="botao botao-secundario" style="font-size: 12px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                    <span>⬇️</span> Baixar Agente Windows (.bat)
                </a>
            </div>

            <div class="remote-modal-actions">
                <button type="button" class="botao botao-sair" onclick="window.respondRemoteControl(false)">❌ Recusar</button>
                <button type="button" class="botao botao-primario" onclick="window.respondRemoteControl(true)">✅ Permitir Controle</button>
            </div>
        </div>
    </div>

    <!-- Layout da Sala: Sidebar de Canais + Chat + Membros Online -->
    <div class="sala-layout">
        
        <!-- Sidebar Esquerda: Canais -->
        <aside class="sala-sidebar">
            <div class="sidebar-titulo">Canais de Estudo</div>
            <ul class="canais-lista">
                <?php if (!empty($canais)): ?>
                    <?php foreach ($canais as $c): ?>
                        <li>
                            <a href="/comunidade/<?= urlencode($comunidade['slug']) ?>?canal=<?= $c['id'] ?>" class="canal-item <?= ($canalAtivo && $canalAtivo['id'] == $c['id']) ? 'canal-ativo' : '' ?>">
                                <span class="canal-hashtag">#</span>
                                <span class="canal-nome"><?= htmlspecialchars($c['name']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li style="padding: 10px; color: var(--cor-apagado); font-size: 13px;">Nenhum canal ativo</li>
                <?php endif; ?>
            </ul>

            <!-- Widget de Gamificação e Reputação Acadêmica -->
            <div class="karma-sidebar-box" style="margin-top: 20px; padding: 12px; background: rgba(255,255,255,0.03); border: 1px solid var(--cor-borda); border-radius: var(--raio-sm);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px;">
                    <span style="font-size:11px; font-weight:800; color:var(--cor-destaque); text-transform:uppercase;">🏆 Meu Karma</span>
                    <strong id="my-karma-points" style="font-size:12px; color:#fff;">0 pts</strong>
                </div>
                <div style="font-size:12px; color:#fff; font-weight:700; margin-bottom: 2px;" id="my-karma-title">🥉 Iniciante Curioso</div>
                <div style="font-size:11px; color:var(--cor-apagado); margin-bottom: 8px;" id="my-karma-solutions">0 soluções aceitas</div>
                <button type="button" class="botao botao-secundario" style="width:100%; font-size:11px; padding:5px 8px;" onclick="window.openLeaderboardModal()">
                    📊 Ver Ranking Geral
                </button>
            </div>

            <!-- Usuários Online na Matéria -->
            <div class="online-users-box">
                <div class="sidebar-titulo" style="margin-top: 20px;">
                    Membros Online (<span id="online-count-badge">0</span>)
                </div>
                <ul id="online-users-list" class="online-users-list">
                    <li class="online-loading">Carregando membros...</li>
                </ul>
                <button type="button" class="botao botao-secundario" style="width: 100%; font-size: 11px; padding: 7px; margin-top: 12px; border-color: rgba(78, 205, 196, 0.4); color: #4ecdc4;" onclick="window.openInviteFriendsModal()">
                    👥 Convidar Amigos para a Sala
                </button>
            </div>

            <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--cor-borda);">
                <a href="/hub" class="botao botao-secundario" style="width: 100%; font-size: 12px; padding: 8px;">← Voltar para o Hub</a>
            </div>
        </aside>

        <!-- Área Principal com Abas Colaborativas -->
        <main class="sala-main-content" data-category-id="<?= $categoria['id'] ?? 1 ?>">

            <!-- Barra de Abas Superiores -->
            <div class="sala-tabs-bar">
                <button type="button" class="sala-tab-btn active" onclick="window.switchSalaTab('panel-chat')">
                    <span>💬</span> Chat
                </button>
                <button type="button" class="sala-tab-btn" onclick="window.switchSalaTab('panel-forum')">
                    <span>❓</span> Fórum de Dúvidas
                </button>
                <button type="button" class="sala-tab-btn" onclick="window.switchSalaTab('panel-quiz')">
                    <span>🎮</span> Quizzes de Revisão
                </button>
                <button type="button" class="sala-tab-btn" onclick="window.switchSalaTab('panel-live-code')">
                    <span>💻</span> Live Code
                </button>
                <button type="button" class="sala-tab-btn" onclick="window.switchSalaTab('panel-sql-playground')">
                    <span>🗄️</span> SQL Playground
                </button>
                <button type="button" class="sala-tab-btn" onclick="window.switchSalaTab('panel-whiteboard')">
                    <span>🎨</span> Lousa Digital
                </button>
            </div>

            <!-- PAINEL 1: CHAT DA MATÉRIA -->
            <div id="panel-chat" class="sala-tab-panel active">
                <div class="chat-header">
                    <div class="chat-canal-info">
                        <span class="chat-hashtag">#</span>
                        <h3 class="chat-canal-nome"><?= htmlspecialchars($canalAtivo['name'] ?? 'Canal Geral') ?></h3>
                    </div>
                    <span class="chat-canal-desc"><?= htmlspecialchars($canalAtivo['description'] ?? 'Discussões, dúvidas e compartilhamento de materiais') ?></span>
                </div>

                <!-- Feed de Mensagens em Tempo Real -->
                <div class="chat-mensagens" id="chat-feed">
                    <?php if (!empty($mensagens)): ?>
                        <?php foreach ($mensagens as $m): ?>
                            <div class="msg-item <?= (($m['user_id'] ?? 0) == ($loggedUser['id'] ?? 0)) ? 'msg-propria' : '' ?>" data-id="<?= $m['id'] ?>">
                                <div class="msg-avatar user-popover-trigger" 
                                     data-user-id="<?= $m['user_id'] ?>" 
                                     data-user-name="<?= htmlspecialchars($m['user_name']) ?>"
                                     data-user-role="<?= htmlspecialchars($m['user_role'] ?? 'Aluno') ?>"
                                     style="cursor:pointer;" title="Ver perfil de <?= htmlspecialchars($m['user_name']) ?>">
                                    <?= strtoupper(substr($m['user_name'] ?? 'U', 0, 2)) ?>
                                </div>
                                <div class="msg-corpo">
                                    <div class="msg-topo">
                                        <span class="msg-autor user-popover-trigger" 
                                              data-user-id="<?= $m['user_id'] ?>" 
                                              data-user-name="<?= htmlspecialchars($m['user_name']) ?>"
                                              data-user-role="<?= htmlspecialchars($m['user_role'] ?? 'Aluno') ?>"
                                              style="cursor:pointer; text-decoration: underline dotted;" title="Ver perfil de <?= htmlspecialchars($m['user_name']) ?>">
                                            <?= htmlspecialchars($m['user_name']) ?>
                                        </span>
                                        <?php if (($m['user_role'] ?? '') === 'admin'): ?>
                                            <span class="badge-role badge-admin">Admin</span>
                                        <?php elseif (($m['user_role'] ?? '') === 'moderator'): ?>
                                            <span class="badge-role badge-mod">Monitor</span>
                                        <?php endif; ?>
                                        <span class="msg-hora"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></span>
                                        
                                        <div class="msg-acoes-wrap">
                                            <?php if ((($m['user_id'] ?? 0) == ($loggedUser['id'] ?? 0)) || (($loggedUser['role'] ?? '') === 'admin')): ?>
                                                <button type="button" class="btn-msg-acao btn-msg-delete" data-id="<?= $m['id'] ?>" title="Apagar mensagem">✕</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if (!empty($m['is_code_block'])): ?>
                                        <div class="bloco-codigo-container">
                                            <div class="bloco-codigo-cabecalho">
                                                <span class="bloco-codigo-lang"><?= htmlspecialchars($m['code_language'] ?? 'código') ?></span>
                                                <button type="button" class="btn-copiar-codigo" onclick="copiarCodigo(this)">Copiar</button>
                                            </div>
                                            <pre class="bloco-codigo"><code><?= htmlspecialchars($m['content']) ?></code></pre>
                                        </div>
                                    <?php elseif (!empty($m['content'])): ?>
                                        <div class="msg-texto">
                                            <?= nl2br(htmlspecialchars($m['content'])) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($m['attachment_url'])): ?>
                                        <?php if (($m['attachment_type'] ?? '') === 'image'): ?>
                                            <div class="msg-anexo msg-anexo-img">
                                                <div class="chat-img-wrapper" onclick="window.openImageLightbox('<?= htmlspecialchars($m['attachment_url']) ?>', '<?= htmlspecialchars($m['attachment_name'] ?? 'Imagem') ?>')" title="Clique para expandir e baixar a imagem">
                                                    <img src="<?= htmlspecialchars($m['attachment_url']) ?>" alt="<?= htmlspecialchars($m['attachment_name'] ?? 'Imagem') ?>" class="chat-img-thumb" loading="lazy">
                                                    <span class="chat-img-expand-hint">🔍 Expandir</span>
                                                </div>
                                            </div>
                                        <?php elseif (($m['attachment_type'] ?? '') === 'audio'): ?>
                                            <div class="msg-anexo msg-anexo-audio">
                                                <div class="audio-player-card">
                                                    <div class="audio-player-icon">🎙️</div>
                                                    <div class="audio-player-controls">
                                                        <audio controls preload="metadata" class="chat-audio-element">
                                                            <source src="<?= htmlspecialchars($m['attachment_url']) ?>">
                                                            Seu navegador não suporta áudio HTML5.
                                                        </audio>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="msg-anexo msg-anexo-file">
                                                <a href="<?= htmlspecialchars($m['attachment_url']) ?>" target="_blank" download="<?= htmlspecialchars($m['attachment_name'] ?? 'arquivo') ?>" class="anexo-download-card">
                                                    <span class="anexo-icone">📁</span>
                                                    <div class="anexo-info">
                                                        <span class="anexo-nome"><?= htmlspecialchars($m['attachment_name'] ?? 'Arquivo') ?></span>
                                                        <span class="anexo-hint">Clique para baixar o material</span>
                                                    </div>
                                                    <span class="anexo-btn-down">⬇</span>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="chat-vazio" id="chat-feed-vazio">
                            <span style="font-size: 34px; display:block; margin-bottom:8px;">💬</span>
                            Ainda não há mensagens neste canal. Seja o primeiro a enviar uma dúvida ou compartilhar um material!
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Botão Flutuante de Rolagem para o Fim -->
                <button type="button" id="scroll-bottom-btn" class="btn-scroll-bottom" style="display:none;" title="Descer para mensagens mais recentes">
                    ↓ Novas Mensagens
                </button>

                <!-- Pré-visualização de Anexo / Imagem Colada (Thumbnail Preview) -->
                <div id="attachment-preview" class="chat-attachment-preview" style="display:none;">
                    <div class="attachment-preview-thumb-box" id="attachment-preview-thumb-box" style="display:none;">
                        <img id="attachment-preview-img" src="" alt="Preview da Imagem" />
                    </div>
                    <div class="attachment-preview-details">
                        <span id="attachment-preview-name" class="attachment-preview-name"></span>
                        <span id="attachment-preview-status" class="attachment-preview-status">✓ Pronto para envio</span>
                    </div>
                    <button type="button" id="remove-attachment-btn" title="Remover anexo">✕</button>
                </div>

                <!-- HUD do Gravador de Áudio de Voz -->
                <div id="audio-record-hud" class="audio-record-hud" style="display:none;">
                    <div class="audio-record-status">
                        <span class="record-pulse-dot"></span>
                        <span class="record-label">Gravando Áudio:</span>
                        <span id="audio-record-timer" class="record-timer">00:00</span>
                    </div>
                    <div class="audio-record-actions">
                        <button type="button" id="btn-cancel-record" class="btn-record-cancel">✕ Cancelar</button>
                        <button type="button" id="btn-send-record" class="btn-record-send">✓ Enviar Áudio</button>
                    </div>
                </div>

                <!-- Pop-up Seletor de Emojis -->
                <div id="emoji-picker-popup" class="emoji-picker-popup" style="display:none;">
                    <div class="emoji-picker-header">
                        <span>Selecione um Emoji</span>
                    </div>
                    <div class="emoji-grid">
                        <button type="button" class="btn-emoji-item">😀</button>
                        <button type="button" class="btn-emoji-item">😁</button>
                        <button type="button" class="btn-emoji-item">😂</button>
                        <button type="button" class="btn-emoji-item">🤣</button>
                        <button type="button" class="btn-emoji-item">😃</button>
                        <button type="button" class="btn-emoji-item">😄</button>
                        <button type="button" class="btn-emoji-item">😅</button>
                        <button type="button" class="btn-emoji-item">😉</button>
                        <button type="button" class="btn-emoji-item">😊</button>
                        <button type="button" class="btn-emoji-item">🥰</button>
                        <button type="button" class="btn-emoji-item">😍</button>
                        <button type="button" class="btn-emoji-item">🤩</button>
                        <button type="button" class="btn-emoji-item">😎</button>
                        <button type="button" class="btn-emoji-item">🤓</button>
                        <button type="button" class="btn-emoji-item">🥳</button>
                        <button type="button" class="btn-emoji-item">🤔</button>
                        <button type="button" class="btn-emoji-item">🤯</button>
                        <button type="button" class="btn-emoji-item">👍</button>
                        <button type="button" class="btn-emoji-item">👎</button>
                        <button type="button" class="btn-emoji-item">👏</button>
                        <button type="button" class="btn-emoji-item">🙌</button>
                        <button type="button" class="btn-emoji-item">🤝</button>
                        <button type="button" class="btn-emoji-item">✌️</button>
                        <button type="button" class="btn-emoji-item">🤞</button>
                        <button type="button" class="btn-emoji-item">💪</button>
                        <button type="button" class="btn-emoji-item">🙏</button>
                        <button type="button" class="btn-emoji-item">💡</button>
                        <button type="button" class="btn-emoji-item">🚀</button>
                        <button type="button" class="btn-emoji-item">🔥</button>
                        <button type="button" class="btn-emoji-item">❤️</button>
                        <button type="button" class="btn-emoji-item">💯</button>
                        <button type="button" class="btn-emoji-item">✨</button>
                        <button type="button" class="btn-emoji-item">💻</button>
                        <button type="button" class="btn-emoji-item">🖥️</button>
                        <button type="button" class="btn-emoji-item">📱</button>
                        <button type="button" class="btn-emoji-item">⌨️</button>
                        <button type="button" class="btn-emoji-item">📚</button>
                        <button type="button" class="btn-emoji-item">📝</button>
                        <button type="button" class="btn-emoji-item">☕</button>
                        <button type="button" class="btn-emoji-item">🎓</button>
                        <button type="button" class="btn-emoji-item">🔍</button>
                        <button type="button" class="btn-emoji-item">⚠️</button>
                        <button type="button" class="btn-emoji-item">🟢</button>
                        <button type="button" class="btn-emoji-item">🔴</button>
                    </div>
                </div>

                <!-- Formulário de Envio com Suporte AJAX -->
                <div class="chat-input-area">
                    <form id="chat-form" class="chat-form">
                        <input type="hidden" id="chat-channel-id" name="channel_id" value="<?= $canalAtivo['id'] ?? 0 ?>">
                        <input type="file" id="chat-file-input" style="display:none;">
                        
                        <div class="chat-input-wrapper">
                            <textarea id="chat-textarea" name="content" placeholder="Escreva sua mensagem ou cole código / imagens (Ctrl+V)... [Enter para enviar]" class="chat-textarea" rows="2"></textarea>
                            
                            <div class="chat-form-opcoes">
                                <div class="chat-opcoes-esquerda">
                                    <button type="button" id="btn-toggle-emoji" class="btn-chat-tool" title="Inserir Emoji">
                                        😀 Emojis
                                    </button>
                                    
                                    <button type="button" id="btn-record-audio" class="btn-chat-tool btn-chat-audio" title="Gravar mensagem de voz na hora">
                                        🎙️ Gravar Áudio
                                    </button>

                                    <button type="button" class="btn-chat-tool" onclick="document.getElementById('chat-file-input').click()" title="Anexar arquivo, imagem ou PDF">
                                        📎 Anexar
                                    </button>
                                    
                                    <label class="checkbox-label" title="Marque para formatar como bloco de código fonte">
                                        <input type="checkbox" id="is-code-check" name="is_code" value="1">
                                        <span>💻 Código</span>
                                    </label>

                                    <select id="code-lang-select" class="chat-lang-select" style="display:none;">
                                        <option value="PHP">PHP</option>
                                        <option value="JavaScript">JavaScript</option>
                                        <option value="Python">Python</option>
                                        <option value="SQL">SQL</option>
                                        <option value="HTML/CSS">HTML/CSS</option>
                                        <option value="C/C++">C/C++</option>
                                        <option value="Java">Java</option>
                                        <option value="C#">C#</option>
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

            <!-- PAINEL 2: LIVE CODE EDITOR COLABORATIVO -->
            <div id="panel-live-code" class="sala-tab-panel" style="display:none;">
                <div class="tool-panel-container">
                    <div class="tool-panel-header">
                        <div class="tool-header-left">
                            <span class="tool-title-icon">💻</span>
                            <div>
                                <h3 class="tool-title">Live Code Editor Colaborativo</h3>
                                <span class="tool-subtitle">Programação ao vivo com sincronização em tempo real</span>
                            </div>
                            <span id="live-code-filename" class="code-filename-badge">main.php</span>
                            <span id="live-code-sync-badge" class="code-sync-badge sync-synced">Sincronizado</span>
                        </div>
                        <div class="tool-header-right">
                            <select id="live-code-lang-select" class="tool-select">
                                <option value="php">PHP (Nativo)</option>
                                <option value="python">Python 3</option>
                                <option value="javascript">JavaScript (ES6)</option>
                                <option value="sql">SQL</option>
                                <option value="c_cpp">C / C++</option>
                            </select>
                            <button type="button" id="btn-run-live-code" class="botao botao-primario" style="padding: 7px 16px; font-size: 13px;">
                                <span>▶️</span> Executar (Ctrl+Enter)
                            </button>
                            <button type="button" id="btn-download-live-code" class="botao botao-secundario" style="padding: 7px 14px; font-size: 13px;" title="Baixar arquivo de código">
                                <span>💾</span> Baixar
                            </button>
                        </div>
                    </div>

                    <div class="live-code-workbench">
                        <!-- Editor Ace -->
                        <div class="live-code-editor-box">
                            <div id="live-code-editor"></div>
                        </div>
                        <!-- Console de Saída -->
                        <div class="live-code-console-box">
                            <div class="console-header">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span class="console-dot"></span>
                                    <span class="console-title">Console de Saída</span>
                                    <span id="live-code-time-badge" class="console-time-tag">⏱️ Pronto</span>
                                </div>
                                <button type="button" id="btn-clear-console" class="btn-clear-term" title="Limpar Console">Limpar</button>
                            </div>
                            <pre id="live-code-output" class="console-body">Clique em "▶️ Executar (Ctrl+Enter)" para rodar o código e ver a saída aqui em tempo real.</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAINEL 3: SQL PLAYGROUND DIDÁTICO -->
            <div id="panel-sql-playground" class="sala-tab-panel" style="display:none;">
                <div class="tool-panel-container">
                    <div class="tool-panel-header">
                        <div class="tool-header-left">
                            <span class="tool-title-icon">🗄️</span>
                            <div>
                                <h3 class="tool-title">SQL Playground Didático</h3>
                                <span class="tool-subtitle">Banco de dados universitário pré-configurado da FASM</span>
                            </div>
                        </div>
                        <div class="tool-header-right">
                            <div class="sql-examples-dropdown">
                                <button type="button" class="btn-sql-example" onclick="window.loadSqlExample('alunos_todos')">👥 Alunos & CR</button>
                                <button type="button" class="btn-sql-example" onclick="window.loadSqlExample('join_notas')">📊 Notas (JOIN)</button>
                                <button type="button" class="btn-sql-example" onclick="window.loadSqlExample('group_media')">📈 Média (GROUP BY)</button>
                                <button type="button" class="btn-sql-example" onclick="window.loadSqlExample('extensao_bolsas')">🎓 Extensão & Bolsas</button>
                            </div>
                            <button type="button" id="btn-run-sql" class="botao botao-primario" style="padding: 7px 16px; font-size: 13px;">
                                <span>▶️</span> Executar SQL (Ctrl+Enter)
                            </button>
                            <button type="button" id="btn-export-sql-csv" class="botao botao-secundario" style="padding: 7px 14px; font-size: 13px;" title="Exportar resultado para CSV">
                                <span>📥</span> CSV
                            </button>
                            <button type="button" id="btn-reset-sql-db" class="botao botao-sair" style="padding: 7px 12px; font-size: 12px;" title="Restaurar dados de exemplo">
                                <span>🔄</span> Resetar DB
                            </button>
                        </div>
                    </div>

                    <div class="sql-workbench">
                        <!-- Sidebar de Esquema Didático -->
                        <aside class="sql-sidebar">
                            <div class="sql-sidebar-title">
                                <span>📚 Tabelas Didáticas</span>
                            </div>
                            <ul id="sql-schema-list" class="sql-schema-list">
                                <!-- Preenchido via sql_playground.js -->
                            </ul>
                        </aside>

                        <!-- Editor SQL e Resultados -->
                        <div class="sql-content-area">
                            <div class="sql-editor-container">
                                <textarea id="sql-query-input" class="sql-query-input" placeholder="Digite sua consulta SQL aqui... Ex: SELECT * FROM alunos ORDER BY coeficiente_rendimento DESC; (Atalho: Ctrl+Enter)">SELECT a.nome AS Aluno, d.nome AS Disciplina, m.semestre, m.nota_1, m.nota_2, m.media, m.status
FROM matriculas m
JOIN alunos a ON m.aluno_id = a.id
JOIN disciplinas d ON m.disciplina_id = d.id
ORDER BY m.media DESC;</textarea>
                            </div>

                            <!-- Barra de Status e Filtro -->
                            <div class="sql-results-toolbar">
                                <div id="sql-result-status" class="sql-status-neutral">
                                    <span>💡 Pressione <strong>Ctrl + Enter</strong> para executar ou clique nas tabelas ao lado.</span>
                                </div>
                                <input type="text" id="sql-table-filter" class="sql-filter-input" placeholder="Filtrar resultados na tabela..." />
                            </div>

                            <!-- Grade de Dados / Resultados -->
                            <div id="sql-results-box" class="sql-results-box">
                                <div class="sql-placeholder-box">
                                    <span>⚡</span>
                                    <p>Execute uma consulta para visualizar a tabela de dados</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAINEL 4: WHITEBOARD / LOUSA DIGITAL -->
            <div id="panel-whiteboard" class="sala-tab-panel" style="display:none;">
                <div class="tool-panel-container">
                    <div class="wb-toolbar-header">
                        <div class="wb-tools-group">
                            <button type="button" class="wb-tool-btn active" data-tool="pencil" title="Lápis Livre">✏️</button>
                            <button type="button" class="wb-tool-btn" data-tool="line" title="Linha Reta">📏</button>
                            <button type="button" class="wb-tool-btn" data-tool="arrow" title="Seta (Relacionamento / Fluxo)">➡️</button>
                            <button type="button" class="wb-tool-btn" data-tool="rect" title="Retângulo (Tabela / Entidade DER / Classe UML)">🔲</button>
                            <button type="button" class="wb-tool-btn" data-tool="circle" title="Círculo (Estado / Nó)">⭕</button>
                            <button type="button" class="wb-tool-btn" data-tool="text" title="Texto / Rótulo (Atributos, Chaves PK/FK)">🔤</button>
                            <button type="button" class="wb-tool-btn" data-tool="eraser" title="Borracha">🧹</button>
                        </div>

                        <div class="wb-colors-group">
                            <button type="button" class="wb-color-btn active" data-color="#4ecdc4" style="background:#4ecdc4;" title="Ciano"></button>
                            <button type="button" class="wb-color-btn" data-color="#5b6abf" style="background:#5b6abf;" title="Azul"></button>
                            <button type="button" class="wb-color-btn" data-color="#10b981" style="background:#10b981;" title="Verde"></button>
                            <button type="button" class="wb-color-btn" data-color="#f59e0b" style="background:#f59e0b;" title="Âmbar"></button>
                            <button type="button" class="wb-color-btn" data-color="#ef4444" style="background:#ef4444;" title="Vermelho"></button>
                            <button type="button" class="wb-color-btn" data-color="#ffffff" style="background:#ffffff;" title="Branco"></button>
                            
                            <select id="wb-line-width" class="wb-width-select" title="Espessura do Traço">
                                <option value="2">Fino (2px)</option>
                                <option value="4" selected>Médio (4px)</option>
                                <option value="8">Grosso (8px)</option>
                            </select>
                        </div>

                        <div class="wb-actions-group">
                            <span class="wb-sync-indicator">● Sincronizado</span>
                            <button type="button" id="btn-download-wb" class="botao botao-secundario" style="padding: 6px 14px; font-size: 12px;" title="Baixar quadro como imagem PNG">
                                <span>💾</span> Baixar PNG
                            </button>
                            <button type="button" id="btn-clear-wb" class="botao botao-sair" style="padding: 6px 12px; font-size: 12px;" title="Limpar tela">
                                <span>🗑️</span> Limpar
                            </button>
                        </div>
                    </div>

                    <!-- Canvas do Quadro Branco -->
                    <div class="wb-canvas-container">
                        <canvas id="whiteboard-canvas"></canvas>
                    </div>
                </div>
            </div>

            <!-- PAINEL 5: FÓRUM DE DÚVIDAS RESOLVIDAS -->
            <div id="panel-forum" class="sala-tab-panel" style="display:none;">
                <div class="tool-panel-container" id="forum-container">
                    <div class="forum-toolbar-header">
                        <div class="forum-header-left">
                            <span class="tool-title-icon">❓</span>
                            <div>
                                <h3 class="tool-title">Fórum Acadêmico & Dúvidas Resolvidas</h3>
                                <span class="tool-subtitle">Pergunte, responda e ganhe pontos de Karma</span>
                            </div>
                        </div>
                        <div class="forum-header-right">
                            <div class="forum-filters-group">
                                <button type="button" class="btn-forum-filter active" data-filter="all">Todas</button>
                                <button type="button" class="btn-forum-filter" data-filter="resolved">✅ Resolvidas</button>
                                <button type="button" class="btn-forum-filter" data-filter="unresolved">❓ Em Aberto</button>
                            </div>
                            <input type="text" id="forum-search-input" class="forum-search-box" placeholder="Buscar dúvidas por título ou #tag...">
                            <button type="button" id="btn-new-question" class="botao botao-primario" style="padding: 7px 16px; font-size: 13px;">
                                + Nova Dúvida (+5 pts)
                            </button>
                        </div>
                    </div>

                    <!-- Lista de Dúvidas -->
                    <div id="forum-list-view" class="forum-content-area">
                        <div id="forum-questions-list" class="forum-questions-list">
                            <!-- Preenchido via forum.js -->
                        </div>
                    </div>

                    <!-- Detalhes de Dúvida Única e Respostas -->
                    <div id="forum-single-view" class="forum-content-area" style="display:none;">
                        <!-- Preenchido dinamicamente via forum.js -->
                    </div>
                </div>
            </div>

            <!-- PAINEL 6: QUIZZES DE REVISÃO (MINI-KAHOOT) -->
            <div id="panel-quiz" class="sala-tab-panel" style="display:none;">
                <div class="tool-panel-container" id="quiz-container">
                    <div id="quiz-list-view" class="quiz-content-area">
                        <!-- Lista de simulados via quiz.js -->
                    </div>
                    <div id="quiz-play-view" class="quiz-content-area" style="display:none;">
                        <!-- Tela de jogo interativo via quiz.js -->
                    </div>
                </div>
            </div>

        </main>
    </div>

</div>

<!-- MODAL DE NOVA DÚVIDA -->
<div id="new-question-modal" class="image-lightbox-modal" style="display:none;">
    <div class="lightbox-overlay" onclick="document.getElementById('new-question-modal').style.display='none'"></div>
    <div class="lightbox-content animate-zoom-in modal-card-box">
        <div class="lightbox-header">
            <span class="lightbox-title">❓ Publicar Nova Dúvida</span>
            <button type="button" class="btn-lightbox-close" onclick="document.getElementById('new-question-modal').style.display='none'">✕</button>
        </div>
        <form id="form-new-question" style="padding: 20px;">
            <div style="margin-bottom: 14px;">
                <label style="display:block; font-size:12px; font-weight:700; color:var(--cor-titulo); margin-bottom:6px;">Título Claro da Dúvida:</label>
                <input type="text" id="new-q-title" class="input-form" placeholder="Ex: Como fazer um INNER JOIN com mais de duas tabelas?" required style="width:100%;">
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display:block; font-size:12px; font-weight:700; color:var(--cor-titulo); margin-bottom:6px;">Explicação Detalhada ou Código:</label>
                <textarea id="new-q-content" class="reply-textarea" rows="5" placeholder="Descreva o que você está tentando fazer, o erro que ocorreu ou cole trechos de código..." required style="width:100%;"></textarea>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display:block; font-size:12px; font-weight:700; color:var(--cor-titulo); margin-bottom:6px;">Tags (separadas por vírgula):</label>
                <input type="text" id="new-q-tags" class="input-form" placeholder="Ex: sql, join, chaves, normalizacao" style="width:100%;">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="botao botao-secundario" onclick="document.getElementById('new-question-modal').style.display='none'">Cancelar</button>
                <button type="submit" class="botao botao-primario">Publicar Dúvida (+5 pts)</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE LEADERBOARD / RANKING ACADÊMICO -->
<div id="leaderboard-modal" class="image-lightbox-modal" style="display:none;">
    <div class="lightbox-overlay" onclick="window.closeLeaderboardModal()"></div>
    <div class="lightbox-content animate-zoom-in modal-card-box" style="max-width: 680px;">
        <div class="lightbox-header">
            <span class="lightbox-title">🏆 Ranking Acadêmico & Top Monitores</span>
            <button type="button" class="btn-lightbox-close" onclick="window.closeLeaderboardModal()">✕</button>
        </div>
        <div id="leaderboard-list" style="padding: 20px;">
            <!-- Preenchido via gamification.js -->
        </div>
    </div>
</div>

<!-- MODAL DE VISUALIZAÇÃO DE IMAGEM EM ALTA RESOLUÇÃO (LIGHTBOX) -->
<div id="image-lightbox-modal" class="image-lightbox-modal" style="display:none;">
    <div class="lightbox-overlay" onclick="window.closeImageLightbox()"></div>
    <div class="lightbox-content animate-zoom-in">
        <div class="lightbox-header">
            <span id="lightbox-title" class="lightbox-title">Visualizar Imagem</span>
            <div class="lightbox-actions">
                <a id="lightbox-download-btn" href="#" download="imagem_vertice.png" class="btn-lightbox-action" title="Baixar Imagem no seu Computador">
                    ⬇ Baixar Imagem
                </a>
                <button type="button" class="btn-lightbox-close" onclick="window.closeImageLightbox()" title="Fechar (ESC)">✕</button>
            </div>
        </div>
        <div class="lightbox-body">
            <img id="lightbox-img" src="" alt="Imagem Expandida" />
        </div>
    </div>
</div>

<!-- Ace Editor CDN para o Live Code -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.7/ace.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.7/ext-language_tools.js"></script>

<!-- Scripts de Chat, Chamada e Ferramentas Colaborativas -->
<script src="/public/js/chat.js"></script>
<script src="/public/js/call.js"></script>
<script src="/public/js/live_code.js"></script>
<script src="/public/js/sql_playground.js"></script>
<script src="/public/js/whiteboard.js"></script>
<script src="/public/js/forum.js"></script>
<script src="/public/js/quiz.js"></script>
<script src="/public/js/gamification.js"></script>

<script>
window.switchSalaTab = function(panelId) {
    document.querySelectorAll('.sala-tab-panel').forEach(p => {
        p.style.display = 'none';
        p.classList.remove('active');
    });
    document.querySelectorAll('.sala-tab-btn').forEach(b => b.classList.remove('active'));

    const targetPanel = document.getElementById(panelId);
    if (targetPanel) {
        targetPanel.style.display = 'flex';
        targetPanel.classList.add('active');
    }

    const targetBtn = document.querySelector(`.sala-tab-btn[onclick*="${panelId}"]`);
    if (targetBtn) targetBtn.classList.add('active');

    // Inicialização e redimensionamento sob demanda
    if (panelId === 'panel-live-code') {
        if (window.initLiveCode) window.initLiveCode();
        if (typeof ace !== 'undefined') {
            try { ace.edit('live-code-editor').resize(); } catch(e){}
        }
    } else if (panelId === 'panel-sql-playground') {
        if (window.initSqlPlayground) window.initSqlPlayground();
    } else if (panelId === 'panel-whiteboard') {
        if (window.initWhiteboard) window.initWhiteboard();
    } else if (panelId === 'panel-forum') {
        if (window.initForum) window.initForum();
    } else if (panelId === 'panel-quiz') {
        if (window.initQuizSystem) window.initQuizSystem();
    }
};
</script>

<!-- MODAL DE CONVIDAR AMIGOS PARA ESTA SALA -->
<div id="invite-friends-to-comm-modal" class="image-lightbox-modal" style="display:none;">
    <div class="lightbox-overlay" onclick="document.getElementById('invite-friends-to-comm-modal').style.display='none'"></div>
    <div class="lightbox-content animate-zoom-in modal-card-box" style="max-width: 520px;">
        <div class="lightbox-header">
            <span class="lightbox-title">👥 Convidar Amigos para a Matéria</span>
            <button type="button" class="btn-lightbox-close" onclick="document.getElementById('invite-friends-to-comm-modal').style.display='none'">✕</button>
        </div>
        <div style="padding: 20px;">
            <p style="font-size:12px; color:var(--cor-apagado); margin-bottom: 14px;">
                Envie um convite instantâneo para seus amigos da FASM participarem da matéria <strong><?= htmlspecialchars($categoria['name'] ?? 'Comunidade') ?></strong>:
            </p>
            <div id="comm-friends-invite-list" style="display:flex; flex-direction:column; gap:10px; max-height:300px; overflow-y:auto;">
                <!-- Carregado dinamicamente -->
            </div>
        </div>
    </div>
</div>

<script>
window.openInviteFriendsModal = async function() {
    const modal = document.getElementById('invite-friends-to-comm-modal');
    const list = document.getElementById('comm-friends-invite-list');
    if (!modal || !list) return;

    modal.style.display = 'flex';
    list.innerHTML = '<div style="font-size:12px; color:var(--cor-apagado);">Carregando seus amigos...</div>';

    try {
        const res = await fetch('/api/private/friends');
        const data = await res.json();
        if (data.status === 'success' && data.friends) {
            if (data.friends.length === 0) {
                list.innerHTML = `
                    <div style="text-align:center; padding:20px; font-size:12px; color:var(--cor-apagado);">
                        Você ainda não possui amigos adicionados.<br>
                        <a href="/privado" style="color:var(--cor-destaque); font-weight:700; margin-top:8px; display:inline-block;">Ir para Mensagens & Amigos →</a>
                    </div>
                `;
                return;
            }

            const catId = <?= $categoria['id'] ?? 1 ?>;
            const chanId = <?= $canalAtivo['id'] ?? 1 ?>;

            list.innerHTML = data.friends.map(f => `
                <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.03); border:1px solid var(--cor-borda); border-radius:6px; padding:8px 12px;">
                    <div>
                        <strong style="font-size:13px; color:#fff;">${f.name}</strong>
                        <div style="font-size:10px; color:var(--cor-apagado);">${f.email}</div>
                    </div>
                    <button type="button" class="botao botao-primario" style="padding:4px 12px; font-size:11px;" id="btn-invite-f-${f.id}" onclick="window.sendInviteToFriend(${f.id}, ${catId}, ${chanId})">
                        Convidar
                    </button>
                </div>
            `).join('');
        }
    } catch (e) {
        list.innerHTML = '<div style="color:#ef4444; font-size:12px;">Erro ao carregar amigos.</div>';
    }
};

window.sendInviteToFriend = async function(friendId, catId, chanId) {
    const btn = document.getElementById(`btn-invite-f-${friendId}`);
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Enviando...';
    }

    try {
        const res = await fetch('/api/private/invite/send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                receiver_id: friendId,
                category_id: catId,
                channel_id: chanId
            })
        });
        const data = await res.json();
        if (btn) {
            btn.textContent = '✓ Convidado!';
            btn.style.background = '#10b981';
        }
    } catch (e) {
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Erro ao enviar';
        }
    }
};
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

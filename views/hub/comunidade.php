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
    <div id="call-container" class="call-room-panel animate-fade-in" style="display:none;">
        <div class="call-room-header">
            <div class="call-header-left">
                <span class="live-indicator">● AO VIVO</span>
                <h3 class="call-header-h3">Sala: <?= htmlspecialchars($canalAtivo['name'] ?? 'Geral') ?></h3>
                <span id="call-participants-count" class="call-badge-count">1 conectado</span>
            </div>
            <div class="call-header-right">
                <a href="/download/remote-agent" class="btn-download-agent-pill" title="Baixar Agente Windows (.bat) para permitir cliques reais no VS Code e desktop">
                    <span>⬇️</span> Baixar Agente (.bat)
                </a>
                <span class="call-hint-tag">💡 Clique em ⛶ para fixar uma tela</span>
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

            <!-- Usuários Online na Matéria -->
            <div class="online-users-box">
                <div class="sidebar-titulo" style="margin-top: 24px;">
                    Membros Online (<span id="online-count-badge">0</span>)
                </div>
                <ul id="online-users-list" class="online-users-list">
                    <li class="online-loading">Carregando membros...</li>
                </ul>
            </div>

            <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--cor-borda);">
                <a href="/hub" class="botao botao-secundario" style="width: 100%; font-size: 12px; padding: 8px;">← Voltar para o Hub</a>
            </div>
        </aside>

        <!-- Área Principal de Mensagens -->
        <main class="sala-chat">
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
                            <div class="msg-avatar">
                                <?= strtoupper(substr($m['user_name'] ?? 'U', 0, 2)) ?>
                            </div>
                            <div class="msg-corpo">
                                <div class="msg-topo">
                                    <span class="msg-autor"><?= htmlspecialchars($m['user_name']) ?></span>
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
                    <div class="chat-vazio">
                        <div style="font-size: 34px; margin-bottom: 8px;">💬</div>
                        <p>Ainda não há mensagens neste canal. Seja o primeiro a enviar uma dúvida ou compartilhar um material!</p>
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

        </main>
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

<!-- Scripts de Chat em Tempo Real e WebRTC -->
<script src="/public/js/chat.js"></script>
<script src="/public/js/call.js"></script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

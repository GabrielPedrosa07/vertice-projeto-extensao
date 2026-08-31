<?php
$pageTitle = htmlspecialchars($comunidade['name']) . ' — Vértice';
require __DIR__ . '/../layouts/header.php';
?>

<div class="container" style="padding-top: 120px; padding-bottom: 80px;">

    <!-- Cabeçalho da Comunidade -->
    <div class="comunidade-header" style="border-left: 5px solid <?= htmlspecialchars($comunidade['color'] ?? '#5b6abf') ?>;">
        <div class="com-header-info">
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-size:32px;"><?= htmlspecialchars($comunidade['icon'] ?? '📚') ?></span>
                <div>
                    <h1 class="com-header-nome"><?= htmlspecialchars($comunidade['name']) ?></h1>
                    <p class="com-header-desc"><?= htmlspecialchars($comunidade['description'] ?? '') ?></p>
                </div>
            </div>
        </div>

        <div class="com-header-acoes">
            <?php if ($isMember): ?>
                <span class="badge-status-membro">✓ Você é membro</span>
                <form method="POST" action="/comunidade/<?= urlencode($comunidade['slug']) ?>/sair" onsubmit="return confirm('Deseja realmente sair desta comunidade?');" style="display:inline; margin:0;">
                    <button type="submit" class="botao botao-sair" style="padding: 7px 14px; font-size: 12px;">Sair da Comunidade</button>
                </form>
            <?php else: ?>
                <form method="POST" action="/comunidade/<?= urlencode($comunidade['slug']) ?>/entrar" style="display:inline; margin:0;">
                    <button type="submit" class="botao botao-primario" style="padding: 9px 18px; font-size: 13px;">+ Participar da Comunidade</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Layout da Sala: Sidebar de Canais + Área de Chat -->
    <div class="sala-layout">
        
        <!-- Sidebar de Canais -->
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
                <span class="chat-canal-desc"><?= htmlspecialchars($canalAtivo['description'] ?? 'Discussões e dúvidas') ?></span>
            </div>

            <!-- Feed de Mensagens -->
            <div class="chat-mensagens" id="chat-feed">
                <?php if (!empty($mensagens)): ?>
                    <?php foreach ($mensagens as $m): ?>
                        <div class="msg-item">
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
                                </div>

                                <?php if (!empty($m['is_code_block'])): ?>
                                    <div class="msg-texto">
                                        <pre class="bloco-codigo"><code><?= htmlspecialchars($m['content']) ?></code></pre>
                                    </div>
                                <?php else: ?>
                                    <div class="msg-texto">
                                        <?= nl2br(htmlspecialchars($m['content'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="chat-vazio">
                        <div style="font-size: 32px; margin-bottom: 8px;">💬</div>
                        <p>Ainda não há mensagens neste canal. Seja o primeiro a enviar uma dúvida ou compartilhar um material!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Formulário de Envio -->
            <div class="chat-input-area">
                <form method="POST" action="/comunidade/<?= urlencode($comunidade['slug']) ?>/mensagem" class="chat-form">
                    <input type="hidden" name="channel_id" value="<?= $canalAtivo['id'] ?? 0 ?>">
                    
                    <div class="chat-input-wrapper">
                        <textarea name="content" placeholder="Escreva sua mensagem ou dúvida..." class="chat-textarea" required rows="2"></textarea>
                        
                        <div class="chat-form-opcoes">
                            <label class="checkbox-label" title="Marque para formatar como bloco de código fonte">
                                <input type="checkbox" name="is_code" value="1">
                                <span>💻 Bloco de código</span>
                            </label>
                            <button type="submit" class="botao botao-primario" style="padding: 8px 18px; font-size: 13px;">
                                Enviar Mensagem
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </main>
    </div>

</div>

<script>
    // Rolagem automática para a última mensagem
    document.addEventListener('DOMContentLoaded', function() {
        var feed = document.getElementById('chat-feed');
        if (feed) {
            feed.scrollTop = feed.scrollHeight;
        }
    });
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

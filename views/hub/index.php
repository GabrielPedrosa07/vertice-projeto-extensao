<?php
$pageTitle = 'Hub de Estudos — Vértice';
require __DIR__ . '/../layouts/header.php';
?>

<div class="container" style="padding-top: 120px; padding-bottom: 80px;">

    <!-- Cabeçalho do Hub com Busca por Lupa -->
    <div class="hub-topo">
        <div class="hub-boas-vindas">
            <span class="secao-tag">Área do Estudante</span>
            <h1 class="hub-titulo">Olá, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! 👋</h1>
            <p class="hub-subtitulo">Selecione uma matéria para acessar as salas de conversa ou explore novas comunidades.</p>
        </div>

        <form action="/comunidades/buscar" method="GET" class="hub-busca-form">
            <div class="busca-input-wrapper">
                <input type="text" name="q" placeholder="Pesquisar matérias e comunidades públicas..." class="busca-input" required>
                <button type="submit" class="busca-botao" title="Pesquisar">🔍</button>
            </div>
        </form>
    </div>

    <!-- Barra de Ações Rápidas: Criar Servidor e Mensagens -->
    <div class="hub-action-bar animate-fade-in" style="display: flex; justify-content: space-between; align-items: center; background: rgba(7, 11, 24, 0.85); border: 1px solid var(--cor-borda); border-radius: 12px; padding: 14px 20px; margin-bottom: 32px; box-shadow: 0 8px 30px rgba(0,0,0,0.35); flex-wrap: wrap; gap: 14px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(78, 205, 196, 0.1); border: 1px solid rgba(78, 205, 196, 0.3); display: flex; align-items: center; justify-content: center; font-size: 18px;">
                🚀
            </div>
            <div>
                <strong style="color: #fff; font-size: 14px; display: block;">Crie sua Própria Comunidade de Estudos</strong>
                <span style="color: var(--cor-apagado); font-size: 12px;">Monte seu grupo de TCC, monitoria ou estudos com canais de texto e chamadas de vídeo!</span>
            </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="/download/configurar-chrome" class="btn-chrome-pill" style="padding: 8px 14px; font-size: 12px;" title="Baixar script .bat para abrir Chrome com WebRTC e tela liberados">
                <span>⚡</span> Configurar Chrome (.bat)
            </a>
            <a href="/privado" class="botao botao-secundario" style="padding: 8px 14px; font-size: 12.5px;">
                <span>💬</span> Mensagens & Amigos
            </a>
            <button type="button" class="botao botao-primario" style="padding: 8px 16px; font-size: 12.5px;" onclick="document.getElementById('create-server-modal').style.display='flex'">
                <span>➕</span> Criar Novo Servidor
            </button>
        </div>
    </div>

    <!-- Seção: Suas Comunidades -->
    <section class="hub-secao">
        <div class="hub-secao-header">
            <h2 class="hub-secao-titulo">Suas Comunidades</h2>
            <span class="badge-contador"><?= count($minhasComunidades) ?> ativas</span>
        </div>

        <?php if (!empty($minhasComunidades)): ?>
            <div class="grade-comunidades">
                <?php foreach ($minhasComunidades as $com): ?>
                    <div class="card-comunidade" style="border-left: 4px solid <?= htmlspecialchars($com['color'] ?? '#5b6abf') ?>;">
                        <div class="card-com-topo">
                            <span class="card-com-icone"><?= htmlspecialchars($com['icon'] ?? '📚') ?></span>
                            <span class="card-com-membros">👥 <?= (int)$com['members_count'] ?> membros</span>
                        </div>
                        <h3 class="card-com-nome"><?= htmlspecialchars($com['name']) ?></h3>
                        <p class="card-com-desc"><?= htmlspecialchars($com['description'] ?? 'Espaço para estudos e troca de materiais.') ?></p>
                        <div class="card-com-acoes">
                            <a href="/comunidade/<?= urlencode($com['slug']) ?>" class="botao botao-primario" style="width: 100%; padding: 9px; font-size: 13px;">
                                Acessar Salas →
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="estado-vazio">
                <div style="font-size: 38px; margin-bottom: 12px;">📚</div>
                <h3>Você ainda não entrou em nenhuma comunidade</h3>
                <p>Veja as matérias disponíveis logo abaixo e clique em <strong>"Participar"</strong> para começar a interagir!</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- Seção: Comunidades Disponíveis -->
    <section class="hub-secao" style="margin-top: 50px;">
        <div class="hub-secao-header">
            <h2 class="hub-secao-titulo">Comunidades Públicas Disponíveis</h2>
            <span class="badge-contador"><?= count($disponiveis) ?> disponíveis</span>
        </div>

        <?php if (!empty($disponiveis)): ?>
            <div class="grade-comunidades">
                <?php foreach ($disponiveis as $com): ?>
                    <div class="card-comunidade" style="border-left: 4px solid <?= htmlspecialchars($com['color'] ?? '#4ecdc4') ?>;">
                        <div class="card-com-topo">
                            <span class="card-com-icone"><?= htmlspecialchars($com['icon'] ?? '📚') ?></span>
                            <span class="card-com-membros">👥 <?= (int)$com['members_count'] ?> membros</span>
                        </div>
                        <h3 class="card-com-nome"><?= htmlspecialchars($com['name']) ?></h3>
                        <p class="card-com-desc"><?= htmlspecialchars($com['description'] ?? 'Espaço para estudos e troca de materiais.') ?></p>
                        <div class="card-com-acoes">
                            <form method="POST" action="/comunidade/<?= urlencode($com['slug']) ?>/entrar" style="width: 100%; margin: 0;">
                                <button type="submit" class="botao botao-secundario" style="width: 100%; padding: 9px; font-size: 13px;">
                                    + Participar
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--cor-apagado);">Você já está participando de todas as comunidades cadastradas!</p>
        <?php endif; ?>
    </section>

</div>

<!-- MODAL DE CRIAR E PERSONALIZAR PRÓPRIO SERVIDOR / COMUNIDADE -->
<div id="create-server-modal" class="image-lightbox-modal" style="display:none; z-index:99999;">
    <div class="lightbox-overlay" onclick="document.getElementById('create-server-modal').style.display='none'"></div>
    <div class="lightbox-content animate-zoom-in modal-card-box" style="max-width: 540px; padding: 26px;">
        <div class="lightbox-header">
            <span class="lightbox-title">✨ Criar Meu Próprio Servidor de Estudos</span>
            <button type="button" class="btn-lightbox-close" onclick="document.getElementById('create-server-modal').style.display='none'">✕</button>
        </div>

        <form id="form-create-server" style="margin-top: 18px; display:flex; flex-direction:column; gap:16px;">
            <div class="campo-grupo">
                <label class="campo-label" for="srv-name">Nome do Servidor / Grupo de Estudos *</label>
                <input type="text" id="srv-name" class="campo-input" placeholder="Ex: TCC 2026 - Automação Residencial" required>
            </div>

            <div class="campo-grupo">
                <label class="campo-label">Ícone / Emoji do Servidor</label>
                <div class="emoji-selector-grid" style="display:flex; gap:8px; flex-wrap:wrap;">
                    <?php foreach (['📚', '💻', '🚀', '🧠', '⚡', '☕', '🎮', '🔬', '🎯', '🛠️', '🎓', '🤖'] as $em): ?>
                        <label class="emoji-option-pill" style="cursor:pointer;">
                            <input type="radio" name="srv_icon" value="<?= $em ?>" <?= ($em === '📚') ? 'checked' : '' ?> style="display:none;">
                            <span class="emoji-box"><?= $em ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="campo-grupo">
                <label class="campo-label">Cor Temática de Destaque</label>
                <div class="color-selector-grid" style="display:flex; gap:10px;">
                    <?php foreach (['#5b6abf' => 'Índigo', '#4ecdc4' => 'Ciano', '#8b5cf6' => 'Roxo', '#10b981' => 'Verde', '#f59e0b' => 'Âmbar', '#f43f5e' => 'Rosa'] as $hex => $cName): ?>
                        <label class="color-option-circle" style="cursor:pointer;" title="<?= $cName ?>">
                            <input type="radio" name="srv_color" value="<?= $hex ?>" <?= ($hex === '#5b6abf') ? 'checked' : '' ?> style="display:none;">
                            <span class="color-circle-dot" style="background: <?= $hex ?>;"></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="campo-grupo">
                <label class="campo-label" for="srv-desc">Descrição do Servidor</label>
                <textarea id="srv-desc" class="campo-input" rows="3" placeholder="Conte brevemente o objetivo deste servidor para seus colegas..."></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top: 8px;">
                <button type="button" class="botao botao-secundario" onclick="document.getElementById('create-server-modal').style.display='none'">Cancelar</button>
                <button type="submit" id="btn-submit-create-server" class="botao botao-primario">
                    🚀 Criar Servidor
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('form-create-server').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-create-server');
    btn.disabled = true;
    btn.textContent = 'Criando servidor...';

    const name = document.getElementById('srv-name').value.trim();
    const desc = document.getElementById('srv-desc').value.trim();
    const icon = document.querySelector('input[name="srv_icon"]:checked')?.value || '📚';
    const color = document.querySelector('input[name="srv_color"]:checked')?.value || '#5b6abf';

    try {
        const res = await fetch('/api/communities/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, description: desc, icon, color })
        });
        const data = await res.json();
        if (data.status === 'success' && data.redirect) {
            window.location.href = data.redirect;
        } else {
            alert(data.error || 'Erro ao criar servidor.');
            btn.disabled = false;
            btn.textContent = '🚀 Criar Servidor';
        }
    } catch(err) {
        alert('Erro ao conectar ao servidor.');
        btn.disabled = false;
        btn.textContent = '🚀 Criar Servidor';
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

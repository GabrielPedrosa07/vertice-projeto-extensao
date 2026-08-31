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

<?php require __DIR__ . '/../layouts/footer.php'; ?>

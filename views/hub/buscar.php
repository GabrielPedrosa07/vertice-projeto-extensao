<?php
$pageTitle = 'Busca de Comunidades — Vértice';
require __DIR__ . '/../layouts/header.php';
?>

<div class="container" style="padding-top: 120px; padding-bottom: 80px;">

    <div class="hub-topo">
        <div class="hub-boas-vindas">
            <span class="secao-tag">Busca Pública</span>
            <h1 class="hub-titulo">Resultados para: "<?= htmlspecialchars($q) ?>"</h1>
            <p class="hub-subtitulo">Foram encontradas <?= count($resultados) ?> comunidade(s) correspondente(s).</p>
        </div>

        <form action="/comunidades/buscar" method="GET" class="hub-busca-form">
            <div class="busca-input-wrapper">
                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar matérias..." class="busca-input" required>
                <button type="submit" class="busca-botao">🔍</button>
            </div>
        </form>
    </div>

    <div style="margin-bottom: 24px;">
        <a href="/hub" class="botao botao-secundario" style="padding: 7px 14px; font-size: 13px;">← Voltar para o Hub</a>
    </div>

    <?php if (!empty($resultados)): ?>
        <div class="grade-comunidades">
            <?php foreach ($resultados as $com): ?>
                <div class="card-comunidade" style="border-left: 4px solid <?= htmlspecialchars($com['color'] ?? '#5b6abf') ?>;">
                    <div class="card-com-topo">
                        <span class="card-com-icone"><?= htmlspecialchars($com['icon'] ?? '📚') ?></span>
                        <span class="card-com-membros">👥 <?= (int)$com['members_count'] ?> membros</span>
                    </div>
                    <h3 class="card-com-nome"><?= htmlspecialchars($com['name']) ?></h3>
                    <p class="card-com-desc"><?= htmlspecialchars($com['description'] ?? 'Espaço para estudos e troca de materiais.') ?></p>
                    <div class="card-com-acoes">
                        <a href="/comunidade/<?= urlencode($com['slug']) ?>" class="botao botao-primario" style="width: 100%; padding: 9px; font-size: 13px;">
                            Ver Salas →
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="estado-vazio">
            <div style="font-size: 38px; margin-bottom: 12px;">🔍</div>
            <h3>Nenhuma comunidade encontrada</h3>
            <p>Tente buscar por termos mais genéricos como "Banco", "Web", "Redes" ou "Dados".</p>
            <a href="/hub" class="botao botao-primario" style="margin-top: 14px;">Explorar todas as comunidades</a>
        </div>
    <?php endif; ?>

</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

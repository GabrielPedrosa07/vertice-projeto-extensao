<?php
$pageTitle = 'Entrar na Conta — Vértice';
require __DIR__ . '/../layouts/header.php';
?>

<main class="container" style="padding-top: 130px; padding-bottom: 80px;">
    <div class="card-auth">
        <div class="auth-cabecalho">
            <div class="marca-icone" style="margin: 0 auto 12px; width: 44px; height: 44px; font-size: 22px;">V</div>
            <h1 class="auth-titulo">Acesse sua conta</h1>
            <p class="auth-subtitulo">Entre para acessar as salas de estudo e o fórum</p>
        </div>

        <form method="POST" action="/entrar" class="form-auth">
            <div class="campo-grupo">
                <label for="email" class="campo-label">E-mail acadêmico ou pessoal</label>
                <input type="email" id="email" name="email" class="campo-input" placeholder="seuemail@exemplo.com" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="campo-grupo">
                <label for="password" class="campo-label">Senha de acesso</label>
                <input type="password" id="password" name="password" class="campo-input" placeholder="••••••••" required>
            </div>

            <button type="submit" class="botao botao-primario" style="width: 100%; margin-top: 10px; padding: 13px;">
                Entrar no Vértice
            </button>
        </form>

        <div class="auth-rodape">
            <p>Ainda não tem conta? <a href="/cadastro">Cadastre-se gratuitamente</a></p>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

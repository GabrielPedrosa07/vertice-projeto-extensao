<?php
$pageTitle = 'Criar Conta — Vértice';
require __DIR__ . '/../layouts/header.php';
?>

<main class="container" style="padding-top: 130px; padding-bottom: 80px;">
    <div class="card-auth">
        <div class="auth-cabecalho">
            <div class="marca-icone" style="margin: 0 auto 12px; width: 44px; height: 44px; font-size: 22px;">V</div>
            <h1 class="auth-titulo">Criar sua conta</h1>
            <p class="auth-subtitulo">Participe dos canais de estudos da FASM</p>
        </div>

        <form method="POST" action="/cadastro" class="form-auth">
            <div class="campo-grupo">
                <label for="name" class="campo-label">Nome Completo</label>
                <input type="text" id="name" name="name" class="campo-input" placeholder="Ex: Gabriel Pedrosa" required autofocus value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div class="campo-grupo">
                <label for="email" class="campo-label">E-mail</label>
                <input type="email" id="email" name="email" class="campo-input" placeholder="seuemail@exemplo.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="campo-grupo">
                <label for="phone" class="campo-label">Telefone / WhatsApp (opcional)</label>
                <input type="tel" id="phone" name="phone" class="campo-input" placeholder="(32) 99999-9999" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>

            <div class="campo-grupo">
                <label for="password" class="campo-label">Senha</label>
                <input type="password" id="password" name="password" class="campo-input" placeholder="Mínimo 6 caracteres" required minlength="6">
            </div>

            <div class="campo-grupo">
                <label for="password_confirmation" class="campo-label">Confirmar Senha</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="campo-input" placeholder="Repita sua senha" required minlength="6">
            </div>

            <button type="submit" class="botao botao-primario" style="width: 100%; margin-top: 10px; padding: 13px;">
                Criar Minha Conta
            </button>
        </form>

        <div class="auth-rodape">
            <p>Já possui cadastro? <a href="/entrar">Entrar agora</a></p>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

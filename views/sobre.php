<?php
$pageTitle = 'Sobre Nós — Vértice';
require __DIR__ . '/layouts/header.php';
?>

<header class="cab-pagina">
    <div class="container">
        <h1 class="titulo-pagina">Sobre o Projeto Vértice</h1>
        <p class="data-pagina">Projeto de Extensão Universitária III — Análise e Desenvolvimento de Sistemas / FASM</p>
    </div>
</header>

<div class="container secao-sobre">
    <div class="sobre-grid">
        <div class="sobre-texto">
            <h3>De onde veio a ideia?</h3>
            <p>
                O Vértice surgiu durante uma conversa entre os integrantes do grupo sobre como a comunicação acadêmica estava cada vez mais fragmentada. A gente usava WhatsApp pra uma coisa, Discord pra outra, e-mail pra mais outra. E sempre existia a preocupação de que aquele grupo ou servidor podia ser derrubado a qualquer momento sem a gente ter controle nenhum.
            </p>
            <p>
                Como já estamos no 4º período de ADS e tínhamos que entregar um projeto de extensão, decidimos unir as duas coisas: resolver um problema real (comunicação acadêmica descentralizada) e ao mesmo tempo desenvolver algo em PHP nativo que pudesse ser mantido, auditado e usado de verdade pela comunidade.
            </p>
            <p>
                O nome "Vértice" vem da Teoria dos Grafos — é o ponto exato onde as conexões se encontram. E é exatamente isso que a plataforma é: o ponto de encontro entre quem quer aprender e quem pode ajudar.
            </p>
        </div>
        <div>
            <div class="sobre-stats">
                <div class="stat-card">
                    <div class="stat-numero">4</div>
                    <div class="stat-label">Integrantes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-numero">8</div>
                    <div class="stat-label">Tabelas no BD</div>
                </div>
                <div class="stat-card">
                    <div class="stat-numero">100%</div>
                    <div class="stat-label">PHP Puro + PDO</div>
                </div>
                <div class="stat-card">
                    <div class="stat-numero">4º</div>
                    <div class="stat-label">Período ADS</div>
                </div>
            </div>

            <div class="caixa-conteudo" style="margin-top:24px; padding:28px;">
                <h2 style="margin:0 0 12px; border:none; padding:0;">Tecnologias Utilizadas</h2>
                <ul>
                    <li><strong>Backend:</strong> PHP 8.3 Puro (Nativo com PDO e MVC)</li>
                    <li><strong>Banco de Dados:</strong> MySQL 8 / MariaDB</li>
                    <li><strong>Frontend:</strong> HTML5, CSS3 Glassmorphism, JavaScript ES6</li>
                    <li><strong>Controle de Versão:</strong> Git + GitHub</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="container" style="margin-bottom:80px;">
    <div class="caixa-conteudo">
        <h2>Qual o objetivo do projeto como extensão?</h2>
        <p>
            Projetos de extensão universitária existem pra conectar o que a gente aprende em sala de aula com problemas reais da comunidade. No nosso caso, identificamos que a comunicação entre alunos — principalmente pra tirar dúvidas de programação e compartilhar materiais — estava dependente de plataformas que não foram feitas pra isso.
        </p>
        <p>
            Além de resolver esse problema, o Vértice serve como laboratório prático onde aplicamos conceitos de engenharia de software, banco de dados, desenvolvimento web nativo e redes de computadores.
        </p>

        <h2>Como funciona a moderação?</h2>
        <p>
            A moderação é transparente e auditável. Toda ação administrativa gera um registro no banco de dados com justificativa por escrito e identificação do moderador. O usuário afetado tem direito de contestação.
        </p>
    </div>
</div>

<?php require __DIR__ . '/layouts/footer.php'; ?>

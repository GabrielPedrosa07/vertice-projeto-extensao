<?php
$pageTitle = 'Início — Plataforma de Estudos e Comunicação';
require __DIR__ . '/layouts/header.php';
?>

<header class="secao-principal">
    <div class="container">
        <div class="badge-alerta">
            🛡️ Uma plataforma livre, desenvolvida em PHP Puro por estudantes
        </div>
        <h1 class="titulo-hero">
            Estude junto, tire dúvidas e <span class="destaque">compartilhe conhecimento</span> sem depender de ninguém.
        </h1>
        <p class="subtitulo-hero">
            O Vértice é uma plataforma de conversas em tempo real e fórum colaborativo feita por alunos do curso de ADS da FASM. Nasceu da vontade de ter um espaço nosso — sem censuras indevidas, sem algoritmos opacos e sem depender de plataformas de terceiros que decidem por nós o que pode ou não ser discutido.
        </p>
        <div class="acoes-hero">
            <a href="<?= \App\Helpers\Auth::check() ? '/hub' : '/cadastro' ?>" class="botao botao-primario">Acessar as Salas</a>
            <a href="#manifesto" class="botao botao-secundario">Ler o Manifesto</a>
        </div>
    </div>
</header>

<!-- MANIFESTO -->
<section id="manifesto" class="container">
    <div class="card-manifesto">
        <div class="manifesto-cab">
            <span style="font-size:26px;">📢</span>
            <h2 class="manifesto-titulo">Por que a gente criou o Vértice?</h2>
        </div>
        <div class="manifesto-texto">
            <p>
                Já faz um tempo que a gente vê situações complicadas acontecendo nas grandes plataformas. Contas derrubadas sem explicação, posts removidos sem motivo claro, grupos inteiros apagados de uma hora pra outra. Quando você depende 100% de uma empresa pra se comunicar com seus colegas de turma, qualquer decisão unilateral deles te afeta diretamente — e você não tem pra onde recorrer.
            </p>
            <p>
                A gente quis mudar isso. Como estudantes de Análise e Desenvolvimento de Sistemas na FASM, temos a habilidade técnica pra construir nossas próprias ferramentas. O Vértice é isso: um projeto de extensão universitária que coloca a comunicação acadêmica nas mãos de quem realmente importa — os próprios alunos. Aqui a moderação é transparente, as regras são claras e ninguém vai tirar seu conteúdo do ar porque um algoritmo decidiu que não gostou.
            </p>
            <p>
                Não somos contra moderação. Somos contra moderação arbitrária, sem justificativa e sem direito de resposta. No Vértice, toda ação administrativa fica registrada em log público e qualquer pessoa pode questionar uma decisão.
            </p>
        </div>
    </div>
</section>

<!-- RECURSOS -->
<section id="recursos" class="container">
    <div class="secao-cab">
        <span class="secao-tag">O que o Vértice oferece</span>
        <h2 class="secao-titulo">Feito em PHP Puro, pensado pra quem estuda</h2>
    </div>

    <div class="grade-recursos">
        <div class="card-recurso">
            <div class="recurso-icone">💬</div>
            <h3 class="recurso-titulo">Chat em tempo real por matéria</h3>
            <p class="recurso-desc">
                Salas de conversa organizadas por disciplina (Banco de Dados, Redes, POO...) com mensagens instantâneas e sem depender de bibliotecas pesadas.
            </p>
        </div>

        <div class="card-recurso">
            <div class="recurso-icone">💻</div>
            <h3 class="recurso-titulo">Fórum de dúvidas com suporte a código</h3>
            <p class="recurso-desc">
                Poste sua dúvida com trechos de código formatados, receba respostas da turma e marque a que resolveu seu problema. O histórico fica salvo.
            </p>
        </div>

        <div class="card-recurso">
            <div class="recurso-icone">🛡️</div>
            <h3 class="recurso-titulo">Moderação com transparência total</h3>
            <p class="recurso-desc">
                Nada de remoção silenciosa. Se um moderador tomar qualquer ação, ela aparece no log e pode ser contestada pelo usuário afetado.
            </p>
        </div>

        <div class="card-recurso">
            <div class="recurso-icone">📁</div>
            <h3 class="recurso-titulo">Compartilhamento de materiais</h3>
            <p class="recurso-desc">
                Suba apostilas, listas de exercícios, PDFs e códigos de exemplo. Tudo organizado por sala e acessível pra qualquer membro registrado.
            </p>
        </div>

        <div class="card-recurso">
            <div class="recurso-icone">🔒</div>
            <h3 class="recurso-titulo">Seus dados são seus</h3>
            <p class="recurso-desc">
                Não vendemos dados, não rastreamos comportamento e não temos anúncios. Senhas criptografadas com Bcrypt e total respeito à LGPD.
            </p>
        </div>

        <div class="card-recurso">
            <div class="recurso-icone">🎓</div>
            <h3 class="recurso-titulo">Extensão universitária de verdade</h3>
            <p class="recurso-desc">
                O Vértice conecta alunos, monitores e comunidade pra resolver problemas reais de comunicação com tecnologia direta.
            </p>
        </div>
    </div>
</section>

<!-- EQUIPE -->
<section id="equipe" class="secao-equipe">
    <div class="container">
        <div class="secao-cab">
            <span class="secao-tag">Quem está por trás</span>
            <h2 class="secao-titulo">A equipe do projeto</h2>
        </div>

        <div class="grade-equipe">
            <div class="card-membro">
                <div class="membro-avatar">GP</div>
                <h3 class="membro-nome">Gabriel Pedrosa</h3>
                <p class="membro-funcao">Desenvolvedor</p>
                <p class="membro-contato">(32) 98844-8261<br>gabrielmodestudo@outlook.com</p>
            </div>
            <div class="card-membro">
                <div class="membro-avatar">GV</div>
                <h3 class="membro-nome">Guilherme Victor</h3>
                <p class="membro-funcao">Desenvolvedor</p>
                <p class="membro-contato">(32) 99144-3266<br>guivictor400@gmail.com</p>
            </div>
            <div class="card-membro">
                <div class="membro-avatar">IS</div>
                <h3 class="membro-nome">Iuri Schelbi</h3>
                <p class="membro-funcao">Desenvolvedor</p>
                <p class="membro-contato">(32) 99838-2793<br>iurischelb@gmail.com</p>
            </div>
            <div class="card-membro">
                <div class="membro-avatar">JC</div>
                <h3 class="membro-nome">João Carlos</h3>
                <p class="membro-funcao">Desenvolvedor</p>
                <p class="membro-contato">(32) 8461-3144<br>joao.carlos@fasm.edu.br</p>
            </div>
        </div>

        <p style="text-align:center; margin-top:26px; color:var(--cor-apagado); font-size:13px;">
            <strong style="color:var(--cor-titulo);">Orientador:</strong> Prof. Aldecir de Almeida Fonseca — Extensão Universitária III (FASM)
        </p>
    </div>
</section>

<?php require __DIR__ . '/layouts/footer.php'; ?>

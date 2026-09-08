/**
 * VÉRTICE — Quizzes Rápidos de Revisão Pré-Prova
 * Estilo Kahoot universitário com cronômetro, 4 botões coloridos, feedback e ranking
 */

(function () {
    let currentCategoryId = 1;
    let activeQuiz = null;
    let currentQuestionIndex = 0;
    let userAnswers = {};
    let questionTimer = null;
    let timeLeft = 30;
    let totalTimeSpent = 0;
    let timeInterval = null;

    function initQuizSystem() {
        const catMeta = document.querySelector('[data-category-id]');
        currentCategoryId = catMeta ? parseInt(catMeta.getAttribute('data-category-id'), 10) : 1;
        loadQuizzesList();
    }

    async function loadQuizzesList() {
        const container = document.getElementById('quiz-list-view');
        if (!container) return;

        container.innerHTML = '<div class="quiz-loading">Carregando simulados disponíveis...</div>';

        try {
            const res = await fetch(`/api/quiz/list?category_id=${currentCategoryId}`);
            const data = await res.json();

            if (data.status === 'success') {
                renderQuizzesCards(data.quizzes || []);
            }
        } catch (err) {
            container.innerHTML = '<div class="quiz-error">Erro ao carregar os simulados.</div>';
        }
    }

    function renderQuizzesCards(quizzes) {
        const container = document.getElementById('quiz-list-view');
        if (!container) return;

        if (quizzes.length === 0) {
            container.innerHTML = `
                <div class="quiz-empty-box">
                    <span>📚</span>
                    <h4>Nenhum simulado disponível no momento</h4>
                    <p>Fique atento às datas das provas! Os simulados de revisão serão disponibilizados pelos professores e monitores aqui.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <div class="quiz-hero-banner">
                <div class="quiz-hero-text">
                    <h3>🎮 Simulados Rápidos de Revisão Pré-Prova</h3>
                    <p>Teste seus conhecimentos em formato dinâmico estilo Kahoot! Cada acerto concede <strong>+10 pontos de Karma</strong> para subir no ranking da turma.</p>
                </div>
            </div>
            <div class="quiz-cards-grid">
                ${quizzes.map(q => `
                    <div class="quiz-card">
                        <div class="quiz-card-badge">⏱️ 30s por questão</div>
                        <h4 class="quiz-card-title">${escapeHtml(q.title)}</h4>
                        <p class="quiz-card-desc">${escapeHtml(q.description || '')}</p>
                        <div class="quiz-card-footer">
                            <span class="quiz-card-meta">📝 ${q.total_questions || 0} questões</span>
                            <span class="quiz-card-meta">👥 ${q.total_attempts || 0} tentativas</span>
                            <button type="button" class="botao botao-primario btn-start-quiz" onclick="window.startQuiz(${q.id})">
                                ▶️ Iniciar Simulado
                            </button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    window.startQuiz = async function (quizId) {
        const listView = document.getElementById('quiz-list-view');
        const playView = document.getElementById('quiz-play-view');
        if (!listView || !playView) return;

        listView.style.display = 'none';
        playView.style.display = 'block';
        playView.innerHTML = '<div class="quiz-loading">Carregando perguntas do simulado...</div>';

        try {
            const res = await fetch(`/api/quiz/get?id=${quizId}`);
            const data = await res.json();

            if (data.status === 'success' && data.quiz) {
                activeQuiz = data.quiz;
                currentQuestionIndex = 0;
                userAnswers = {};
                totalTimeSpent = 0;
                renderCurrentQuestion();
            }
        } catch (err) {
            playView.innerHTML = '<div class="quiz-error">Erro ao iniciar simulado.</div>';
        }
    };

    function renderCurrentQuestion() {
        const playView = document.getElementById('quiz-play-view');
        if (!playView || !activeQuiz) return;

        const questions = activeQuiz.questions || [];
        if (currentQuestionIndex >= questions.length) {
            finishQuiz();
            return;
        }

        const q = questions[currentQuestionIndex];
        const colors = ['opt-red', 'opt-blue', 'opt-yellow', 'opt-green'];
        const icons = ['▲', '◆', '●', '■'];

        clearInterval(timeInterval);
        timeLeft = 30;

        playView.innerHTML = `
            <div class="quiz-screen-header">
                <div class="quiz-progress-label">Questão ${currentQuestionIndex + 1} de ${questions.length}</div>
                <div class="quiz-timer-box">
                    <span>⏱️</span> <strong id="quiz-countdown">${timeLeft}</strong>s
                </div>
                <button type="button" class="btn-quit-quiz" onclick="window.quitQuiz()" title="Abandonar simulado">✕ Sair</button>
            </div>

            <div class="quiz-timer-bar-wrap">
                <div id="quiz-timer-bar" class="quiz-timer-bar" style="width: 100%;"></div>
            </div>

            <div class="quiz-question-box">
                <h3 class="quiz-question-text">${escapeHtml(q.question_text)}</h3>
            </div>

            <div class="quiz-options-grid" id="quiz-options-grid">
                ${(q.options || []).map((opt, idx) => `
                    <button type="button" class="quiz-option-btn ${colors[idx % 4]}" onclick="window.chooseQuizOption(${q.id}, ${idx})">
                        <span class="quiz-opt-icon">${icons[idx % 4]}</span>
                        <span class="quiz-opt-text">${escapeHtml(opt)}</span>
                    </button>
                `).join('')}
            </div>

            <div id="quiz-feedback-box" class="quiz-feedback-box" style="display:none;"></div>
        `;

        startTimer(q.id);
    }

    function startTimer(questionId) {
        const timerText = document.getElementById('quiz-countdown');
        const timerBar = document.getElementById('quiz-timer-bar');

        timeInterval = setInterval(() => {
            timeLeft--;
            totalTimeSpent++;
            if (timerText) timerText.textContent = timeLeft;
            if (timerBar) timerBar.style.width = ((timeLeft / 30) * 100) + '%';

            if (timeLeft <= 0) {
                clearInterval(timeInterval);
                window.chooseQuizOption(questionId, -1); // Tempo esgotado
            }
        }, 1000);
    }

    window.chooseQuizOption = function (questionId, chosenIndex) {
        clearInterval(timeInterval);
        userAnswers[questionId] = chosenIndex;

        // Desabilita botões
        const buttons = document.querySelectorAll('.quiz-option-btn');
        buttons.forEach((btn, idx) => {
            btn.disabled = true;
            if (idx === chosenIndex) {
                btn.classList.add('selected-choice');
            }
        });

        const feedbackBox = document.getElementById('quiz-feedback-box');
        if (feedbackBox) {
            feedbackBox.style.display = 'block';
            feedbackBox.innerHTML = `
                <div class="quiz-feedback-banner">
                    <span>${chosenIndex >= 0 ? '✓ Resposta Registrada!' : '⏰ Tempo Esgotado!'}</span>
                    <button type="button" class="botao botao-primario btn-next-q" onclick="window.nextQuestion()">
                        Próxima Pergunta →
                    </button>
                </div>
            `;
        }
    };

    window.nextQuestion = function () {
        currentQuestionIndex++;
        renderCurrentQuestion();
    };

    async function finishQuiz() {
        clearInterval(timeInterval);
        const playView = document.getElementById('quiz-play-view');
        if (!playView || !activeQuiz) return;

        playView.innerHTML = '<div class="quiz-loading">Calculando resultado e pontuação de Karma...</div>';

        try {
            const res = await fetch('/api/quiz/submit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    quiz_id: activeQuiz.id,
                    answers: userAnswers,
                    time_taken: totalTimeSpent
                })
            });
            const data = await res.json();

            if (data.status === 'success') {
                renderQuizResults(data);
                if (window.refreshMyKarmaStats) window.refreshMyKarmaStats();
            }
        } catch (err) {
            playView.innerHTML = '<div class="quiz-error">Erro ao submeter respostas.</div>';
        }
    }

    function renderQuizResults(result) {
        const playView = document.getElementById('quiz-play-view');
        if (!playView) return;

        const isGood = result.percentage >= 70;

        playView.innerHTML = `
            <div class="quiz-podium-card animate-zoom-in">
                <div class="podium-icon">${isGood ? '🏆' : '📚'}</div>
                <h2 class="podium-title">${isGood ? 'Parabéns pelo Desempenho!' : 'Bom Treino de Revisão!'}</h2>
                <div class="podium-score-circle">
                    <span class="podium-score-number">${result.score}/${result.total}</span>
                    <span class="podium-score-pct">${result.percentage}% de acertos</span>
                </div>

                <div class="podium-karma-earned">
                    <span>⚡ +${result.karma_earned} Pontos de Karma Adicionados ao seu Perfil!</span>
                </div>

                <p class="podium-time">Tempo total de prova: <strong>${result.time_taken} segundos</strong></p>

                <!-- Gabarito com Explicações -->
                <div class="quiz-review-section">
                    <h4 class="review-heading">📖 Gabarito e Explicações Didáticas</h4>
                    <div class="review-list">
                        ${(result.review || []).map((r, i) => `
                            <div class="review-item ${r.is_correct ? 'review-correct' : 'review-wrong'}">
                                <div class="review-status-header">
                                    <span>${r.is_correct ? '✅ Questão ' + (i+1) + ': Correta' : '❌ Questão ' + (i+1) + ': Incorreta'}</span>
                                </div>
                                <p class="review-explanation"><strong>Explicação do Professor:</strong> ${escapeHtml(r.explanation)}</p>
                            </div>
                        `).join('')}
                    </div>
                </div>

                <div style="margin-top: 24px; display:flex; justify-content:center; gap:12px;">
                    <button type="button" class="botao botao-primario" onclick="window.backToQuizList()">
                        ← Voltar para Simulados
                    </button>
                    <button type="button" class="botao botao-secundario" onclick="window.startQuiz(${activeQuiz.id})">
                        🔄 Fazer Novamente
                    </button>
                </div>
            </div>
        `;
    }

    window.backToQuizList = function () {
        const listView = document.getElementById('quiz-list-view');
        const playView = document.getElementById('quiz-play-view');
        if (listView) listView.style.display = 'block';
        if (playView) playView.style.display = 'none';
        loadQuizzesList();
    };

    window.quitQuiz = function () {
        if (confirm('Deseja realmente sair do simulado? Suas respostas nesta tentativa não serão salvas.')) {
            clearInterval(timeInterval);
            window.backToQuizList();
        }
    };

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    window.initQuizSystem = initQuizSystem;
    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('quiz-container')) {
            initQuizSystem();
        }
    });
})();

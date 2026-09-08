/**
 * VÉRTICE — Fórum de Dúvidas Resolvidas (Estilo Stack Overflow)
 * Perguntas, respostas, soluções verificadas e upvotes úteis
 */

(function () {
    let currentCategoryId = 1;
    let currentFilter = 'all';
    let currentQuestions = [];
    let activeQuestionId = null;

    function initForum() {
        const forumContainer = document.getElementById('forum-container');
        if (!forumContainer) return;

        const catMeta = document.querySelector('[data-category-id]');
        currentCategoryId = catMeta ? parseInt(catMeta.getAttribute('data-category-id'), 10) : 1;

        // Filtros
        document.querySelectorAll('.btn-forum-filter').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.btn-forum-filter').forEach(b => b.classList.remove('active'));
                const target = e.currentTarget;
                target.classList.add('active');
                currentFilter = target.getAttribute('data-filter');
                loadQuestions();
            });
        });

        // Botão Nova Dúvida
        const btnNewQuestion = document.getElementById('btn-new-question');
        if (btnNewQuestion) {
            btnNewQuestion.addEventListener('click', () => {
                const modal = document.getElementById('new-question-modal');
                if (modal) modal.style.display = 'flex';
            });
        }

        // Formulário de Nova Dúvida
        const formNewQuestion = document.getElementById('form-new-question');
        if (formNewQuestion) {
            formNewQuestion.addEventListener('submit', handleCreateQuestion);
        }

        // Formulário de Resposta
        const formNewAnswer = document.getElementById('form-new-answer');
        if (formNewAnswer) {
            formNewAnswer.addEventListener('submit', handleCreateAnswer);
        }

        // Busca de Dúvidas
        const searchInput = document.getElementById('forum-search-input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                filterQuestionsLocally(e.target.value);
            });
        }

        loadQuestions();
    }

    async function loadQuestions() {
        const listEl = document.getElementById('forum-questions-list');
        if (!listEl) return;

        listEl.innerHTML = '<div class="forum-loading">Carregando dúvidas da matéria...</div>';

        try {
            const res = await fetch(`/api/forum/questions?category_id=${currentCategoryId}&filter=${currentFilter}`);
            const data = await res.json();

            if (data.status === 'success') {
                currentQuestions = data.questions || [];
                renderQuestionsList(currentQuestions);
            }
        } catch (err) {
            listEl.innerHTML = '<div class="forum-error">Erro ao carregar dúvidas.</div>';
        }
    }

    function renderQuestionsList(questions) {
        const listEl = document.getElementById('forum-questions-list');
        if (!listEl) return;

        if (questions.length === 0) {
            listEl.innerHTML = `
                <div class="forum-empty">
                    <span style="font-size:38px; display:block; margin-bottom:8px;">💡</span>
                    <h4>Nenhuma dúvida encontrada</h4>
                    <p>Tem alguma dificuldade com o conteúdo? Seja o primeiro a perguntar e ganhar pontos!</p>
                </div>
            `;
            return;
        }

        listEl.innerHTML = questions.map(q => {
            const tagsList = q.tags ? q.tags.split(',').map(t => `<span class="forum-tag">#${escapeHtml(t.trim())}</span>`).join(' ') : '';
            const statusBadge = q.is_resolved == 1 
                ? '<span class="badge-resolved">✅ Resolvido</span>' 
                : '<span class="badge-unresolved">❓ Em Aberto</span>';

            return `
                <div class="forum-question-card ${q.is_resolved == 1 ? 'card-resolved' : ''}" onclick="window.openForumQuestion(${q.id})">
                    <div class="forum-card-left">
                        <div class="forum-stat-box">
                            <span class="forum-stat-number">${q.answers_count || 0}</span>
                            <span class="forum-stat-label">respostas</span>
                        </div>
                        <div class="forum-stat-box">
                            <span class="forum-stat-number">${q.views || 0}</span>
                            <span class="forum-stat-label">visitas</span>
                        </div>
                    </div>
                    <div class="forum-card-main">
                        <div class="forum-card-header">
                            <h4 class="forum-card-title">${escapeHtml(q.title)}</h4>
                            ${statusBadge}
                        </div>
                        <p class="forum-card-snippet">${escapeHtml(q.content.substring(0, 160))}${q.content.length > 160 ? '...' : ''}</p>
                        <div class="forum-card-footer">
                            <div class="forum-tags-wrap">${tagsList}</div>
                            <div class="forum-author-meta">
                                <span>Por <strong>${escapeHtml(q.user_name)}</strong></span>
                                ${q.user_badge ? `<span class="badge-karma-chip">${escapeHtml(q.user_badge)}</span>` : ''}
                                <span class="forum-date">${formatDate(q.created_at)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function filterQuestionsLocally(term) {
        if (!term.trim()) {
            renderQuestionsList(currentQuestions);
            return;
        }
        const lower = term.toLowerCase().trim();
        const filtered = currentQuestions.filter(q => {
            return q.title.toLowerCase().includes(lower) || 
                   q.content.toLowerCase().includes(lower) || 
                   (q.tags && q.tags.toLowerCase().includes(lower));
        });
        renderQuestionsList(filtered);
    }

    window.openForumQuestion = async function (id) {
        activeQuestionId = id;
        const viewContainer = document.getElementById('forum-single-view');
        const listContainer = document.getElementById('forum-list-view');
        if (!viewContainer || !listContainer) return;

        listContainer.style.display = 'none';
        viewContainer.style.display = 'block';
        viewContainer.innerHTML = '<div class="forum-loading">Carregando detalhes da dúvida e respostas...</div>';

        try {
            const res = await fetch(`/api/forum/question?id=${id}`);
            const data = await res.json();

            if (data.status === 'success' && data.question) {
                renderSingleQuestion(data.question, data.answers || []);
            }
        } catch (err) {
            viewContainer.innerHTML = '<div class="forum-error">Erro ao carregar detalhes da dúvida.</div>';
        }
    };

    function renderSingleQuestion(q, answers) {
        const viewContainer = document.getElementById('forum-single-view');
        if (!viewContainer) return;

        const currentUserId = parseInt(document.body.getAttribute('data-user-id') || '0', 10);
        const currentUserRole = document.body.getAttribute('data-user-role') || 'student';
        const canMarkSolution = (currentUserId === parseInt(q.user_id, 10) || ['admin', 'moderator'].includes(currentUserRole));

        const tagsList = q.tags ? q.tags.split(',').map(t => `<span class="forum-tag">#${escapeHtml(t.trim())}</span>`).join(' ') : '';
        const statusBadge = q.is_resolved == 1 
            ? '<span class="badge-resolved-large">✅ Dúvida Resolvida</span>' 
            : '<span class="badge-unresolved-large">❓ Em Aberto — Aguardando Solução</span>';

        let answersHtml = answers.map(a => {
            const isSolution = (a.is_solution == 1);
            return `
                <div class="forum-answer-card ${isSolution ? 'answer-solution-highlight' : ''}" id="answer-${a.id}">
                    ${isSolution ? '<div class="solution-banner"><span>✅</span> Solução Aceita pelo Autor</div>' : ''}
                    <div class="answer-header">
                        <div class="answer-author-info">
                            <span class="answer-avatar">${escapeHtml(a.user_name.substring(0, 2).toUpperCase())}</span>
                            <div>
                                <strong class="answer-author-name">${escapeHtml(a.user_name)}</strong>
                                ${a.user_role === 'admin' ? '<span class="badge-role badge-admin">Admin</span>' : ''}
                                ${a.user_badge ? `<span class="badge-karma-chip">${escapeHtml(a.user_badge)}</span>` : ''}
                                <span class="answer-time">${formatDate(a.created_at)}</span>
                            </div>
                        </div>
                        <div class="answer-actions-top">
                            <button type="button" class="btn-vote-answer" onclick="window.voteAnswer(${a.id})" title="Marcar como útil (+5 pontos de Karma)">
                                <span>👍 Útil</span> (<span id="upvotes-count-${a.id}">${a.upvotes || 0}</span>)
                            </button>
                            ${canMarkSolution && !isSolution ? `
                                <button type="button" class="btn-mark-solution" onclick="window.markSolution(${q.id}, ${a.id})" title="Marcar esta resposta como a solução oficial da dúvida">
                                    ✅ Marcar como Solução
                                </button>
                            ` : ''}
                        </div>
                    </div>
                    <div class="answer-body">
                        ${nl2br(escapeHtml(a.content))}
                    </div>
                </div>
            `;
        }).join('');

        if (answers.length === 0) {
            answersHtml = `
                <div class="answers-empty-box">
                    <span>💬</span>
                    <p>Ainda não há respostas para esta dúvida. Conhece o assunto? Ajude seu colega e ganhe <strong>+10 pontos de Karma</strong>!</p>
                </div>
            `;
        }

        viewContainer.innerHTML = `
            <div class="single-question-header">
                <button type="button" class="btn-back-forum" onclick="window.backToForumList()">← Voltar para a lista de dúvidas</button>
                <div class="single-q-meta-top">
                    ${statusBadge}
                </div>
                <h2 class="single-q-title">${escapeHtml(q.title)}</h2>
                <div class="single-q-author-bar">
                    <span>Perguntado por <strong>${escapeHtml(q.user_name)}</strong></span>
                    ${q.user_badge ? `<span class="badge-karma-chip">${escapeHtml(q.user_badge)}</span>` : ''}
                    <span>em ${formatDate(q.created_at)}</span>
                    <span>👁️ ${q.views || 0} visualizações</span>
                </div>
            </div>

            <div class="single-q-body">
                ${nl2br(escapeHtml(q.content))}
            </div>

            <div class="single-q-tags">${tagsList}</div>

            <hr class="forum-divider" />

            <div class="answers-section">
                <h3 class="answers-heading">${answers.length} ${answers.length === 1 ? 'Resposta' : 'Respostas'}</h3>
                ${answersHtml}
            </div>

            <!-- Formulário de Envio de Resposta -->
            <div class="reply-box-container">
                <h4>Sua Resposta</h4>
                <p class="reply-hint">Compartilhe seu conhecimento acadêmico. Respostas claras ganham mais votos úteis e podem ser marcadas como solução oficial (+30 pts)!</p>
                <form id="form-submit-answer" onsubmit="window.handleAnswerSubmit(event, ${q.id})">
                    <textarea id="reply-textarea" class="reply-textarea" rows="4" placeholder="Escreva sua explicação detalhada, exemplos práticos ou código..." required></textarea>
                    <div style="display:flex; justify-content:flex-end; margin-top:10px;">
                        <button type="submit" class="botao botao-primario">Publicar Resposta</button>
                    </div>
                </form>
            </div>
        `;
    }

    window.backToForumList = function () {
        const viewContainer = document.getElementById('forum-single-view');
        const listContainer = document.getElementById('forum-list-view');
        if (viewContainer) viewContainer.style.display = 'none';
        if (listContainer) listContainer.style.display = 'block';
        loadQuestions();
    };

    async function handleCreateQuestion(e) {
        e.preventDefault();
        const title = document.getElementById('new-q-title').value.trim();
        const content = document.getElementById('new-q-content').value.trim();
        const tags = document.getElementById('new-q-tags').value.trim();

        if (!title || !content) return;

        try {
            const res = await fetch('/api/forum/question/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    category_id: currentCategoryId,
                    title: title,
                    content: content,
                    tags: tags
                })
            });
            const data = await res.json();

            if (data.status === 'success') {
                alert(data.message);
                document.getElementById('new-question-modal').style.display = 'none';
                document.getElementById('form-new-question').reset();
                loadQuestions();
            } else {
                alert(data.error || 'Erro ao publicar dúvida.');
            }
        } catch (err) {
            alert('Erro de conexão ao publicar.');
        }
    }

    window.handleAnswerSubmit = async function (e, questionId) {
        e.preventDefault();
        const content = document.getElementById('reply-textarea').value.trim();
        if (!content) return;

        try {
            const res = await fetch('/api/forum/answer/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    question_id: questionId,
                    content: content
                })
            });
            const data = await res.json();

            if (data.status === 'success') {
                alert(data.message);
                window.openForumQuestion(questionId);
                // Atualiza contagem de karma no widget
                if (window.refreshMyKarmaStats) window.refreshMyKarmaStats();
            } else {
                alert(data.error || 'Erro ao enviar resposta.');
            }
        } catch (err) {
            alert('Erro ao enviar resposta.');
        }
    };

    window.markSolution = async function (questionId, answerId) {
        if (!confirm('Deseja marcar esta resposta como a solução definitiva da sua dúvida? O autor receberá +30 pontos de Karma!')) return;

        try {
            const res = await fetch('/api/forum/answer/mark-solution', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    question_id: questionId,
                    answer_id: answerId
                })
            });
            const data = await res.json();

            if (data.status === 'success') {
                alert(data.message);
                window.openForumQuestion(questionId);
                if (window.refreshMyKarmaStats) window.refreshMyKarmaStats();
            } else {
                alert(data.error || 'Não foi possível marcar a solução.');
            }
        } catch (err) {
            alert('Erro de comunicação.');
        }
    };

    window.voteAnswer = async function (answerId) {
        try {
            const res = await fetch('/api/forum/answer/vote', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ answer_id: answerId })
            });
            const data = await res.json();

            if (data.status === 'success') {
                const countEl = document.getElementById(`upvotes-count-${answerId}`);
                if (countEl && data.upvotes !== undefined) {
                    countEl.textContent = data.upvotes;
                }
            } else if (data.status === 'already_voted') {
                alert(data.message);
            }
        } catch (err) {}
    };

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    function nl2br(str) {
        if (!str) return '';
        return str.replace(/\n/g, '<br>');
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return d.toLocaleDateString('pt-BR') + ' às ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }

    window.initForum = initForum;
    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('forum-container')) {
            initForum();
        }
    });
})();

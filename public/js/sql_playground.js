/**
 * VÉRTICE — SQL Playground Didático
 * Console interativo para aprendizado de banco de dados e consultas SQL
 */

(function () {
    let currentRows = [];
    let currentColumns = [];

    const exampleQueries = {
        'alunos_todos': 'SELECT id, nome, matricula, curso, periodo, cidade, coeficiente_rendimento\nFROM alunos\nORDER BY coeficiente_rendimento DESC;',
        'join_notas': 'SELECT a.nome AS Aluno, d.nome AS Disciplina, m.semestre, m.nota_1, m.nota_2, m.media, m.status\nFROM matriculas m\nJOIN alunos a ON m.aluno_id = a.id\nJOIN disciplinas d ON m.disciplina_id = d.id\nORDER BY m.media DESC;',
        'group_media': 'SELECT d.nome AS Disciplina, COUNT(m.id) AS Total_Matriculas, ROUND(AVG(m.media), 2) AS Media_Geral\nFROM matriculas m\nJOIN disciplinas d ON m.disciplina_id = d.id\nGROUP BY d.nome\nHAVING Total_Matriculas > 1\nORDER BY Media_Geral DESC;',
        'extensao_bolsas': 'SELECT p.titulo AS Projeto, p.area, p.vagas, p.bolsa, prof.nome AS Orientador\nFROM projetos_extensao p\nJOIN professores prof ON p.orientador_id = prof.id\nORDER BY p.bolsa DESC;'
    };

    function initSqlPlayground() {
        const textarea = document.getElementById('sql-query-input');
        if (!textarea) return;

        // Atalho Ctrl+Enter
        textarea.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                executeSql();
            }
        });

        const btnRun = document.getElementById('btn-run-sql');
        if (btnRun) btnRun.addEventListener('click', executeSql);

        const btnReset = document.getElementById('btn-reset-sql-db');
        if (btnReset) btnReset.addEventListener('click', resetDatabase);

        const btnExport = document.getElementById('btn-export-sql-csv');
        if (btnExport) btnExport.addEventListener('click', exportToCsv);

        const filterInput = document.getElementById('sql-table-filter');
        if (filterInput) {
            filterInput.addEventListener('input', (e) => {
                filterTableResults(e.target.value);
            });
        }

        // Carrega esquema e exemplos
        loadSchema();
    }

    async function loadSchema() {
        const schemaList = document.getElementById('sql-schema-list');
        if (!schemaList) return;

        schemaList.innerHTML = '<li class="sql-schema-loading">Carregando tabelas didáticas...</li>';

        try {
            const res = await fetch('/api/tools/sql/schema');
            const data = await res.json();

            if (data.status === 'success' && data.tables) {
                schemaList.innerHTML = '';
                data.tables.forEach(table => {
                    const li = document.createElement('li');
                    li.className = 'sql-schema-table-item';
                    li.innerHTML = `
                        <div class="sql-table-header" onclick="window.insertSqlTemplate('SELECT * FROM ${table.name} LIMIT 10;')">
                            <span class="sql-table-icon">📄</span>
                            <span class="sql-table-name">${table.name}</span>
                            <span class="sql-table-count">${table.row_count} linhas</span>
                        </div>
                        <div class="sql-table-columns">
                            ${table.columns.map(c => `<span class="sql-col-tag" title="${c.type}">${c.name}</span>`).join('')}
                        </div>
                    `;
                    schemaList.appendChild(li);
                });
            }
        } catch (err) {
            schemaList.innerHTML = '<li class="sql-schema-error">Erro ao carregar tabelas.</li>';
        }
    }

    async function executeSql() {
        const textarea = document.getElementById('sql-query-input');
        const runBtn = document.getElementById('btn-run-sql');
        const statusEl = document.getElementById('sql-result-status');
        const resultsBox = document.getElementById('sql-results-box');

        if (!textarea) return;
        const query = textarea.value.trim();
        if (!query) {
            alert('Por favor, digite uma consulta SQL para executar.');
            return;
        }

        if (runBtn) {
            runBtn.disabled = true;
            runBtn.innerHTML = '<span>⏳</span> Executando...';
        }

        try {
            const res = await fetch('/api/tools/sql/execute', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query: query })
            });
            const data = await res.json();

            if (data.status === 'success') {
                if (data.type === 'select') {
                    currentColumns = data.columns || [];
                    currentRows = data.rows || [];
                    renderTable(currentColumns, currentRows);
                    if (statusEl) {
                        statusEl.className = 'sql-status-success';
                        statusEl.innerHTML = `✅ <strong>${data.count} registros</strong> retornados em <strong>${data.duration_ms}ms</strong>.`;
                    }
                } else {
                    currentColumns = [];
                    currentRows = [];
                    if (resultsBox) {
                        resultsBox.innerHTML = `
                            <div class="sql-mutation-box">
                                <span style="font-size: 24px;">⚡</span>
                                <div>
                                    <h4>Comando Executado</h4>
                                    <p>${data.message}</p>
                                </div>
                            </div>
                        `;
                    }
                    loadSchema(); // Atualiza contagem de linhas
                }
            } else {
                if (statusEl) {
                    statusEl.className = 'sql-status-error';
                    statusEl.innerHTML = `❌ <strong>${data.message}</strong> (${data.duration_ms || 0}ms)`;
                }
            }
        } catch (err) {
            if (statusEl) {
                statusEl.className = 'sql-status-error';
                statusEl.innerHTML = `❌ Erro de conexão com o banco de dados: ${err.message}`;
            }
        } finally {
            if (runBtn) {
                runBtn.disabled = false;
                runBtn.innerHTML = '<span>▶️</span> Executar SQL (Ctrl+Enter)';
            }
        }
    }

    function renderTable(columns, rows) {
        const resultsBox = document.getElementById('sql-results-box');
        if (!resultsBox) return;

        if (rows.length === 0) {
            resultsBox.innerHTML = `
                <div class="sql-empty-results">
                    <span>🔍</span>
                    <p>Nenhum registro encontrado para esta consulta.</p>
                </div>
            `;
            return;
        }

        let html = `
            <div class="sql-table-wrapper">
                <table class="sql-data-table">
                    <thead>
                        <tr>
                            ${columns.map(col => `<th>${escapeHtml(col)}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody id="sql-data-tbody">
                        ${rows.map(row => `
                            <tr>
                                ${columns.map(col => {
                                    const val = row[col];
                                    return `<td>${val !== null && val !== undefined ? escapeHtml(String(val)) : '<span class="sql-null">NULL</span>'}</td>`;
                                }).join('')}
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;

        resultsBox.innerHTML = html;
    }

    function filterTableResults(term) {
        const tbody = document.getElementById('sql-data-tbody');
        if (!tbody || currentRows.length === 0) return;

        const filterLower = term.toLowerCase().trim();
        const filtered = currentRows.filter(row => {
            return Object.values(row).some(val => String(val).toLowerCase().includes(filterLower));
        });

        tbody.innerHTML = filtered.map(row => `
            <tr>
                ${currentColumns.map(col => {
                    const val = row[col];
                    return `<td>${val !== null && val !== undefined ? escapeHtml(String(val)) : '<span class="sql-null">NULL</span>'}</td>`;
                }).join('')}
            </tr>
        `).join('');

        const statusEl = document.getElementById('sql-result-status');
        if (statusEl && term) {
            statusEl.innerHTML = `Filtrando por "<strong>${escapeHtml(term)}</strong>": <strong>${filtered.length} de ${currentRows.length}</strong> linhas visíveis.`;
        }
    }

    async function resetDatabase() {
        if (!confirm('Deseja realmente restaurar o banco de dados didático para o estado inicial padrão?')) return;

        try {
            const res = await fetch('/api/tools/sql/reset', { method: 'POST' });
            const data = await res.json();
            if (data.status === 'success') {
                alert(data.message);
                loadSchema();
                executeSql();
            }
        } catch (err) {
            alert('Erro ao resetar banco de dados.');
        }
    }

    function exportToCsv() {
        if (currentRows.length === 0 || currentColumns.length === 0) {
            alert('Execute uma consulta SELECT com resultados para exportar para CSV.');
            return;
        }

        const headers = currentColumns.map(c => `"${c.replace(/"/g, '""')}"`).join(',');
        const lines = currentRows.map(row => {
            return currentColumns.map(col => {
                const val = row[col];
                if (val === null || val === undefined) return '""';
                return `"${String(val).replace(/"/g, '""')}"`;
            }).join(',');
        });

        const csvContent = '\uFEFF' + [headers, ...lines].join('\r\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `consulta_vertice_${new Date().getTime()}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    window.insertSqlTemplate = function (query) {
        const textarea = document.getElementById('sql-query-input');
        if (textarea) {
            textarea.value = query;
            textarea.focus();
            executeSql();
        }
    };

    window.loadSqlExample = function (key) {
        if (exampleQueries[key]) {
            window.insertSqlTemplate(exampleQueries[key]);
        }
    };

    function escapeHtml(str) {
        return str.replace(/[&<>"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    window.initSqlPlayground = initSqlPlayground;
    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('sql-query-input')) {
            initSqlPlayground();
        }
    });
})();

/**
 * VÉRTICE — Live Code Editor Colaborativo
 * Editor multi-linguagem em tempo real com execução e console integrado
 */

(function () {
    let editor = null;
    let currentChannelId = null;
    let currentFilename = 'main.php';
    let currentLanguage = 'php';
    let isRemoteUpdate = false;
    let lastContent = '';
    let saveTimeout = null;
    let syncInterval = null;

    // Mapeamento de extensões para linguagem
    const extToLang = {
        'php': 'php',
        'py': 'python',
        'js': 'javascript',
        'sql': 'sql',
        'cpp': 'c_cpp',
        'c': 'c_cpp'
    };

    function initLiveCode() {
        const editorEl = document.getElementById('live-code-editor');
        if (!editorEl) return;

        // Recupera channel_id da página
        const channelMeta = document.querySelector('[data-channel-id]');
        currentChannelId = channelMeta ? parseInt(channelMeta.getAttribute('data-channel-id'), 10) : 1;

        // Inicializa Ace Editor
        if (typeof ace !== 'undefined') {
            editor = ace.edit('live-code-editor');
            editor.setTheme('ace/theme/tomorrow_night_eighties');
            editor.session.setMode('ace/mode/php');
            editor.setFontSize(14);
            editor.setShowPrintMargin(false);
            editor.session.setUseWrapMode(true);
            editor.setOptions({
                enableBasicAutocompletion: true,
                enableLiveAutocompletion: true,
                tabSize: 4,
                useSoftTabs: true
            });

            editor.on('change', () => {
                if (isRemoteUpdate) return;
                const content = editor.getValue();
                if (content !== lastContent) {
                    lastContent = content;
                    scheduleSave();
                }
            });

            // Atalho Ctrl+Enter para Executar
            editor.commands.addCommand({
                name: 'runCode',
                bindKey: { win: 'Ctrl-Enter', mac: 'Command-Enter' },
                exec: function () {
                    executeCode();
                }
            });
        }

        // Eventos dos Controles
        const langSelect = document.getElementById('live-code-lang-select');
        if (langSelect) {
            langSelect.addEventListener('change', (e) => {
                setLanguage(e.target.value);
            });
        }

        const btnRun = document.getElementById('btn-run-live-code');
        if (btnRun) {
            btnRun.addEventListener('click', executeCode);
        }

        const btnDownload = document.getElementById('btn-download-live-code');
        if (btnDownload) {
            btnDownload.addEventListener('click', downloadCodeFile);
        }

        const btnClearConsole = document.getElementById('btn-clear-console');
        if (btnClearConsole) {
            btnClearConsole.addEventListener('click', () => {
                const consoleOutput = document.getElementById('live-code-output');
                if (consoleOutput) consoleOutput.textContent = 'Console limpo. Pronto para nova execução.';
            });
        }

        // Carrega código inicial do servidor
        loadCodeFromServer();

        // Inicia polling de sincronização colaborativa
        if (syncInterval) clearInterval(syncInterval);
        syncInterval = setInterval(syncCodeFromServer, 2000);
    }

    function setLanguage(lang) {
        currentLanguage = lang;
        if (!editor) return;

        let aceMode = 'ace/mode/php';
        if (lang === 'python') aceMode = 'ace/mode/python';
        else if (lang === 'javascript') aceMode = 'ace/mode/javascript';
        else if (lang === 'sql') aceMode = 'ace/mode/sql';
        else if (lang === 'c_cpp') aceMode = 'ace/mode/c_cpp';

        editor.session.setMode(aceMode);

        // Atualiza nome de arquivo sugerido
        const fileExt = lang === 'python' ? 'py' : (lang === 'javascript' ? 'js' : (lang === 'sql' ? 'sql' : (lang === 'c_cpp' ? 'cpp' : 'php')));
        currentFilename = `main.${fileExt}`;
        const filenameBadge = document.getElementById('live-code-filename');
        if (filenameBadge) filenameBadge.textContent = currentFilename;

        scheduleSave();
    }

    function scheduleSave() {
        showSyncStatus('Salvando...', 'syncing');
        if (saveTimeout) clearTimeout(saveTimeout);

        saveTimeout = setTimeout(() => {
            saveCodeToServer();
        }, 600);
    }

    async function loadCodeFromServer() {
        try {
            const res = await fetch(`/api/tools/code/get?channel_id=${currentChannelId}&filename=${encodeURIComponent(currentFilename)}`);
            const data = await res.json();

            if (data.status === 'success' && data.file) {
                isRemoteUpdate = true;
                lastContent = data.file.content || '';
                if (editor) {
                    editor.setValue(lastContent, -1);
                }
                if (data.file.language) {
                    const langSelect = document.getElementById('live-code-lang-select');
                    if (langSelect) langSelect.value = data.file.language;
                    currentLanguage = data.file.language;
                    if (editor) {
                        let aceMode = 'ace/mode/' + (data.file.language === 'c_cpp' ? 'c_cpp' : data.file.language);
                        editor.session.setMode(aceMode);
                    }
                }
                isRemoteUpdate = false;
                showSyncStatus(`Sincronizado (${data.file.updated_by_name || 'Sistema'})`, 'synced');
            }
        } catch (err) {
            console.warn('[LiveCode] Erro ao carregar arquivo:', err);
        }
    }

    async function syncCodeFromServer() {
        // Não sobrescreve se o usuário estiver ativamente digitando (foco recente)
        if (!editor || !currentChannelId) return;

        try {
            const res = await fetch(`/api/tools/code/get?channel_id=${currentChannelId}&filename=${encodeURIComponent(currentFilename)}`);
            const data = await res.json();

            if (data.status === 'success' && data.file) {
                const serverContent = data.file.content || '';
                const myContent = editor.getValue();

                if (serverContent !== myContent && serverContent !== lastContent) {
                    // Atualização remota recebida de um colega!
                    const cursor = editor.getCursorPosition();
                    isRemoteUpdate = true;
                    editor.setValue(serverContent, -1);
                    editor.moveCursorToPosition(cursor);
                    lastContent = serverContent;
                    isRemoteUpdate = false;

                    showSyncStatus(`Atualizado por ${data.file.updated_by_name || 'Colega'}!`, 'updated');
                }
            }
        } catch (err) {}
    }

    async function saveCodeToServer() {
        if (!editor) return;
        const content = editor.getValue();

        try {
            const res = await fetch('/api/tools/code/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    channel_id: currentChannelId,
                    filename: currentFilename,
                    language: currentLanguage,
                    content: content
                })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showSyncStatus(`Salvo às ${new Date().toLocaleTimeString()}`, 'synced');
            }
        } catch (err) {
            showSyncStatus('Erro ao salvar', 'error');
        }
    }

    async function executeCode() {
        if (!editor) return;
        const code = editor.getValue();
        const outputEl = document.getElementById('live-code-output');
        const runBtn = document.getElementById('btn-run-live-code');
        const timeBadge = document.getElementById('live-code-time-badge');

        if (runBtn) {
            runBtn.disabled = true;
            runBtn.innerHTML = '<span>⏳</span> Executando...';
        }
        if (outputEl) outputEl.textContent = 'Executando código no servidor...\n';

        try {
            if (currentLanguage === 'javascript') {
                // Execução segura local de JavaScript com captura de console.log
                let logs = [];
                const originalLog = console.log;
                const startTime = performance.now();

                console.log = function (...args) {
                    logs.push(args.map(a => (typeof a === 'object' ? JSON.stringify(a, null, 2) : a)).join(' '));
                    originalLog.apply(console, args);
                };

                try {
                    const func = new Function(code);
                    func();
                    const duration = (performance.now() - startTime).toFixed(2);
                    outputEl.textContent = logs.join('\n') || 'Código executado com sucesso (sem retorno de console.log).';
                    if (timeBadge) timeBadge.textContent = `⏱️ ${duration}ms`;
                } catch (e) {
                    outputEl.textContent = `❌ Erro de Execução JavaScript:\n${e.name}: ${e.message}`;
                } finally {
                    console.log = originalLog;
                }
            } else {
                // PHP, Python, etc. via API segura do backend
                const res = await fetch('/api/tools/code/run', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        language: currentLanguage,
                        code: code
                    })
                });
                const data = await res.json();
                if (outputEl) {
                    outputEl.textContent = data.output || 'Código executado sem saída.';
                }
                if (timeBadge && data.duration_ms !== undefined) {
                    timeBadge.textContent = `⏱️ ${data.duration_ms}ms`;
                }
            }
        } catch (err) {
            if (outputEl) outputEl.textContent = 'Falha ao conectar com o serviço de execução: ' + err.message;
        } finally {
            if (runBtn) {
                runBtn.disabled = false;
                runBtn.innerHTML = '<span>▶️</span> Executar (Ctrl+Enter)';
            }
        }
    }

    function downloadCodeFile() {
        if (!editor) return;
        const code = editor.getValue();
        const blob = new Blob([code], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = currentFilename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    function showSyncStatus(msg, type) {
        const badge = document.getElementById('live-code-sync-badge');
        if (!badge) return;

        badge.textContent = msg;
        badge.className = `code-sync-badge sync-${type}`;
    }

    // Inicialização global
    window.initLiveCode = initLiveCode;
    document.addEventListener('DOMContentLoaded', () => {
        // Inicializa se a aba estiver presente
        if (document.getElementById('live-code-editor')) {
            initLiveCode();
        }
    });
})();

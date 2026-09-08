/**
 * VÉRTICE — Sistema de Chat em Tempo Real
 * Vanilla JS (ES6) + Fetch API + Web Audio API + MediaRecorder + Emojis
 */

(function () {
    'use strict';

    // Elementos principais do DOM
    const feed = document.getElementById('chat-feed');
    const form = document.getElementById('chat-form');
    const textarea = document.getElementById('chat-textarea');
    const channelInput = document.getElementById('chat-channel-id');
    const isCodeCheck = document.getElementById('is-code-check');
    const codeLangSelect = document.getElementById('code-lang-select');
    const fileInput = document.getElementById('chat-file-input');
    const attachmentPreview = document.getElementById('attachment-preview');
    const attachmentPreviewName = document.getElementById('attachment-preview-name');
    const removeAttachmentBtn = document.getElementById('remove-attachment-btn');
    const onlineListEl = document.getElementById('online-users-list');
    const onlineCountEl = document.getElementById('online-count-badge');
    const scrollBottomBtn = document.getElementById('scroll-bottom-btn');

    // Elementos do Gravador de Áudio
    const btnRecordAudio = document.getElementById('btn-record-audio');
    const audioRecordHUD = document.getElementById('audio-record-hud');
    const audioRecordTimer = document.getElementById('audio-record-timer');
    const btnCancelRecord = document.getElementById('btn-cancel-record');
    const btnSendRecord = document.getElementById('btn-send-record');

    // Elementos do Seletor de Emojis
    const btnToggleEmoji = document.getElementById('btn-toggle-emoji');
    const emojiPickerPopup = document.getElementById('emoji-picker-popup');

    if (!feed || !channelInput) return;

    const channelId = parseInt(channelInput.value, 10);
    const currentUserId = parseInt(document.body.dataset.userId || '0', 10);
    const isAdmin = document.body.dataset.userRole === 'admin';

    let lastMessageId = 0;
    let isPolling = false;
    let isSending = false;
    let currentAttachment = null;
    let isUserAtBottom = true;

    // Estado do Gravador de Áudio
    let mediaRecorder = null;
    let audioChunks = [];
    let audioTimerInterval = null;
    let audioRecordSeconds = 0;
    let isRecordingAudio = false;

    // Inicializa o maior ID de mensagem já renderizado
    const existingMsgs = feed.querySelectorAll('.msg-item[data-id]');
    existingMsgs.forEach(el => {
        const id = parseInt(el.dataset.id, 10);
        if (id > lastMessageId) lastMessageId = id;
    });

    // ── Sintetizador de Som de Notificação (Web Audio API) ───────────────────
    function playNotificationSound() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.12); // A5

            gain.gain.setValueAtTime(0.08, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start();
            osc.stop(ctx.currentTime + 0.25);
        } catch (e) {}
    }

    // ── Rolagem do Feed ──────────────────────────────────────────────────────
    function scrollToBottom(force = false) {
        if (!feed) return;
        if (force || isUserAtBottom) {
            feed.scrollTop = feed.scrollHeight;
        }
    }

    feed.addEventListener('scroll', () => {
        const threshold = 60;
        const atBottom = feed.scrollHeight - feed.scrollTop - feed.clientHeight <= threshold;
        isUserAtBottom = atBottom;
        if (scrollBottomBtn) {
            scrollBottomBtn.style.display = atBottom ? 'none' : 'flex';
        }
    });

    if (scrollBottomBtn) {
        scrollBottomBtn.addEventListener('click', () => {
            feed.scrollTop = feed.scrollHeight;
            isUserAtBottom = true;
            scrollBottomBtn.style.display = 'none';
        });
    }

    // ── Formatação e Renderização de Mensagens ────────────────────────────────
    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text || '').replace(/[&<>"']/g, m => map[m]);
    }

    function formatTime(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr.replace(' ', 'T'));
        if (isNaN(d)) return dateStr;
        const pad = n => String(n).padStart(2, '0');
        return `${pad(d.getDate())}/${pad(d.getMonth() + 1)} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function createMessageElement(msg) {
        const div = document.createElement('div');
        div.className = 'msg-item animate-fade-in';
        div.dataset.id = msg.id;

        const isOwn = parseInt(msg.user_id, 10) === currentUserId;
        if (isOwn) {
            div.classList.add('msg-propria');
        }

        const initials = (msg.user_name || 'U').substring(0, 2).toUpperCase();
        
        let roleBadge = '';
        if (msg.user_role === 'admin') {
            roleBadge = '<span class="badge-role badge-admin">Admin</span>';
        } else if (msg.user_role === 'moderator') {
            roleBadge = '<span class="badge-role badge-mod">Monitor</span>';
        }

        let deleteBtn = '';
        if (isOwn || isAdmin) {
            deleteBtn = `<button type="button" class="btn-msg-acao btn-msg-delete" data-id="${msg.id}" title="Apagar mensagem">✕</button>`;
        }

        let bodyHtml = '';

        // Bloco de código
        if (msg.is_code_block == 1 || msg.is_code == 1) {
            const lang = msg.code_language ? escapeHtml(msg.code_language) : 'código';
            bodyHtml += `
                <div class="bloco-codigo-container">
                    <div class="bloco-codigo-cabecalho">
                        <span class="bloco-codigo-lang">${lang}</span>
                        <button type="button" class="btn-copiar-codigo" onclick="copiarCodigo(this)">Copiar</button>
                    </div>
                    <pre class="bloco-codigo"><code>${escapeHtml(msg.content)}</code></pre>
                </div>
            `;
        } else if (msg.content) {
            const formatted = escapeHtml(msg.content).replace(/\n/g, '<br>');
            bodyHtml += `<div class="msg-texto">${formatted}</div>`;
        }

        // Anexos (Imagem, Áudio de Voz ou Arquivo)
        if (msg.attachment_url) {
            const attUrl = escapeHtml(msg.attachment_url);
            const attName = escapeHtml(msg.attachment_name || 'Imagem');
            
            if (msg.attachment_type === 'image') {
                bodyHtml += `
                    <div class="msg-anexo msg-anexo-img">
                        <div class="chat-img-wrapper" onclick="window.openImageLightbox('${attUrl}', '${attName}')" title="Clique para expandir e baixar">
                            <img src="${attUrl}" alt="${attName}" loading="lazy" class="chat-img-thumb" />
                            <span class="chat-img-expand-hint">🔍 Expandir</span>
                        </div>
                    </div>
                `;
            } else if (msg.attachment_type === 'audio') {
                bodyHtml += `
                    <div class="msg-anexo msg-anexo-audio">
                        <div class="audio-player-card">
                            <div class="audio-player-icon">🎙️</div>
                            <div class="audio-player-controls">
                                <audio controls preload="metadata" class="chat-audio-element">
                                    <source src="${attUrl}">
                                    Seu navegador não suporta áudio HTML5.
                                </audio>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                bodyHtml += `
                    <div class="msg-anexo msg-anexo-file">
                        <a href="${attUrl}" target="_blank" download="${attName}" class="anexo-download-card">
                            <span class="anexo-icone">📁</span>
                            <div class="anexo-info">
                                <span class="anexo-nome">${attName}</span>
                                <span class="anexo-hint">Clique para baixar o arquivo</span>
                            </div>
                            <span class="anexo-btn-down">⬇</span>
                        </a>
                    </div>
                `;
            }
        }

        const uId = msg.user_id;
        const uName = escapeHtml(msg.user_name || 'Colega');
        const uRole = escapeHtml(msg.user_role || 'student');

        div.innerHTML = `
            <div class="msg-avatar user-popover-trigger" data-user-id="${uId}" data-user-name="${uName}" data-user-role="${uRole}" style="cursor:pointer;" title="Ver perfil de ${uName}">${escapeHtml(initials)}</div>
            <div class="msg-corpo">
                <div class="msg-topo">
                    <span class="msg-autor user-popover-trigger" data-user-id="${uId}" data-user-name="${uName}" data-user-role="${uRole}" style="cursor:pointer; text-decoration: underline dotted;" title="Ver perfil de ${uName}">${uName}</span>
                    ${roleBadge}
                    <span class="msg-hora">${formatTime(msg.created_at)}</span>
                    <div class="msg-acoes-wrap">${deleteBtn}</div>
                </div>
                ${bodyHtml}
            </div>
        `;

        return div;
    }

    function appendMessages(messages, isInitial = false) {
        if (!messages || messages.length === 0) return;

        const vazio = feed.querySelector('.chat-vazio');
        if (vazio) vazio.remove();

        let addedCount = 0;
        messages.forEach(msg => {
            const id = parseInt(msg.id, 10);
            if (id > lastMessageId) {
                lastMessageId = id;
                const el = createMessageElement(msg);
                feed.appendChild(el);
                addedCount++;
            }
        });

        if (addedCount > 0) {
            if (!isInitial) {
                playNotificationSound();
            }
            scrollToBottom(isInitial);
        }
    }

    // ── Polling de Novas Mensagens ──────────────────────────────────────────
    async function pollMessages() {
        if (isPolling) return;
        isPolling = true;

        try {
            const url = `/api/mensagens?canal=${channelId}&after_id=${lastMessageId}`;
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json' }
            });

            if (res.ok) {
                const data = await res.json();
                if (data.messages && data.messages.length > 0) {
                    appendMessages(data.messages);
                }
            }
        } catch (err) {
        } finally {
            isPolling = false;
        }
    }

    setInterval(pollMessages, 2500);

    // ── Polling de Usuários Online ──────────────────────────────────────────
    async function pollOnlineUsers() {
        if (!onlineListEl) return;
        try {
            const res = await fetch(`/api/online?canal=${channelId}`);
            if (res.ok) {
                const data = await res.json();
                if (data.users) {
                    renderOnlineUsers(data.users);
                }
            }
        } catch (e) {}
    }

    function renderOnlineUsers(users) {
        if (!onlineListEl) return;
        if (onlineCountEl) {
            onlineCountEl.textContent = users.length;
        }

        if (users.length === 0) {
            onlineListEl.innerHTML = '<li class="online-empty">Nenhum membro ativo</li>';
            return;
        }

        onlineListEl.innerHTML = users.map(u => {
            const initials = escapeHtml((u.name || 'U').substring(0, 2).toUpperCase());
            const roleBadge = u.role === 'admin' ? '<span class="badge-role-mini">Admin</span>' : '';
            return `
                <li class="online-user-item user-popover-trigger user-clickable" data-user-id="${u.id}" data-user-name="${escapeHtml(u.name)}" data-user-role="${escapeHtml(u.role || 'student')}" style="cursor:pointer;" title="Ver perfil e adicionar aos amigos">
                    <span class="online-status-dot"></span>
                    <div class="online-avatar">${initials}</div>
                    <span class="online-nome">${escapeHtml(u.name)}</span>
                    ${roleBadge}
                </li>
            `;
        }).join('');
    }

    setInterval(pollOnlineUsers, 6000);
    pollOnlineUsers();

    // ── Upload de Arquivos & Imagens com Preview Visual ─────────────────────
    const previewThumbBox = document.getElementById('attachment-preview-thumb-box');
    const previewThumbImg = document.getElementById('attachment-preview-img');
    const previewStatus = document.getElementById('attachment-preview-status');

    async function handleFileUpload(file) {
        if (!file) return;

        const isImg = file.type.startsWith('image/');

        if (attachmentPreview) {
            attachmentPreview.style.display = 'flex';
            if (attachmentPreviewName) attachmentPreviewName.textContent = file.name || 'Imagem colada';
            if (previewStatus) previewStatus.textContent = 'Enviando...';
            
            // Preview visual imediato se for imagem
            if (isImg && previewThumbBox && previewThumbImg) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewThumbImg.src = e.target.result;
                    previewThumbBox.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else if (previewThumbBox) {
                previewThumbBox.style.display = 'none';
            }
        }

        const formData = new FormData();
        formData.append('file', file);

        try {
            const res = await fetch('/api/mensagens/upload', {
                method: 'POST',
                body: formData
            });

            const data = await res.json();
            if (res.ok && data.status === 'success') {
                currentAttachment = {
                    url: data.url,
                    name: data.name,
                    type: data.type
                };
                if (attachmentPreviewName) attachmentPreviewName.textContent = `📎 ${data.name}`;
                if (previewStatus) previewStatus.textContent = '✓ Pronto para envio';
                attachmentPreview.classList.add('ready');
            } else {
                alert(data.error || 'Erro ao enviar arquivo.');
                clearAttachment();
            }
        } catch (err) {
            alert('Falha na comunicação ao enviar anexo.');
            clearAttachment();
        }
    }

    function clearAttachment() {
        currentAttachment = null;
        if (fileInput) fileInput.value = '';
        if (previewThumbImg) previewThumbImg.src = '';
        if (previewThumbBox) previewThumbBox.style.display = 'none';
        if (attachmentPreview) {
            attachmentPreview.style.display = 'none';
            attachmentPreview.classList.remove('ready');
            if (attachmentPreviewName) attachmentPreviewName.textContent = '';
        }
    }

    // ── MODAL LIGHTBOX DE IMAGEM ─────────────────────────────────────────────
    const lightboxModal = document.getElementById('image-lightbox-modal');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxTitle = document.getElementById('lightbox-title');
    const lightboxDownloadBtn = document.getElementById('lightbox-download-btn');

    window.openImageLightbox = function (imageUrl, imageName) {
        if (!lightboxModal || !lightboxImg) return;
        lightboxImg.src = imageUrl;
        if (lightboxTitle) lightboxTitle.textContent = imageName || 'Visualizar Imagem';
        if (lightboxDownloadBtn) {
            lightboxDownloadBtn.href = imageUrl;
            lightboxDownloadBtn.download = imageName || 'imagem_vertice.png';
        }
        lightboxModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    window.closeImageLightbox = function () {
        if (!lightboxModal) return;
        lightboxModal.style.display = 'none';
        if (lightboxImg) lightboxImg.src = '';
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            window.closeImageLightbox();
        }
    });

    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files[0]) {
                handleFileUpload(e.target.files[0]);
            }
        });
    }

    if (removeAttachmentBtn) {
        removeAttachmentBtn.addEventListener('click', clearAttachment);
    }

    // ── GRAVADOR DE ÁUDIO DE VOZ (MediaRecorder) ─────────────────────────────
    if (btnRecordAudio && audioRecordHUD) {
        btnRecordAudio.addEventListener('click', async () => {
            if (isRecordingAudio) return;

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                audioChunks = [];
                
                const mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
                    ? 'audio/webm;codecs=opus'
                    : (MediaRecorder.isTypeSupported('audio/ogg;codecs=opus') ? 'audio/ogg;codecs=opus' : '');

                mediaRecorder = mimeType ? new MediaRecorder(stream, { mimeType }) : new MediaRecorder(stream);

                mediaRecorder.ondataavailable = (e) => {
                    if (e.data && e.data.size > 0) {
                        audioChunks.push(e.data);
                    }
                };

                mediaRecorder.onstop = () => {
                    stream.getTracks().forEach(t => t.stop());
                };

                mediaRecorder.start(100);
                isRecordingAudio = true;
                audioRecordSeconds = 0;

                // Mostra HUD de Gravação
                audioRecordHUD.style.display = 'flex';
                btnRecordAudio.style.display = 'none';
                form.querySelector('.chat-input-wrapper').style.opacity = '0.3';
                form.querySelector('.chat-input-wrapper').style.pointerEvents = 'none';

                audioTimerInterval = setInterval(() => {
                    audioRecordSeconds++;
                    const min = String(Math.floor(audioRecordSeconds / 60)).padStart(2, '0');
                    const sec = String(audioRecordSeconds % 60).padStart(2, '0');
                    if (audioRecordTimer) audioRecordTimer.textContent = `${min}:${sec}`;
                }, 1000);

            } catch (err) {
                alert('Não foi possível acessar o microfone para gravar áudio. Verifique as permissões.');
            }
        });

        // Cancelar Gravação
        btnCancelRecord.addEventListener('click', () => {
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
            }
            clearInterval(audioTimerInterval);
            audioChunks = [];
            isRecordingAudio = false;
            audioRecordHUD.style.display = 'none';
            btnRecordAudio.style.display = 'inline-flex';
            form.querySelector('.chat-input-wrapper').style.opacity = '1';
            form.querySelector('.chat-input-wrapper').style.pointerEvents = 'auto';
        });

        // Enviar Áudio Gravado
        btnSendRecord.addEventListener('click', async () => {
            if (!mediaRecorder || mediaRecorder.state === 'inactive') return;

            mediaRecorder.stop();
            clearInterval(audioTimerInterval);
            isRecordingAudio = false;

            btnSendRecord.disabled = true;
            btnSendRecord.textContent = 'Enviando...';

            setTimeout(async () => {
                const audioBlob = new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                const ext = (mediaRecorder.mimeType && mediaRecorder.mimeType.includes('ogg')) ? 'ogg' : 'webm';
                const audioFile = new File([audioBlob], `voz_${Date.now()}.${ext}`, { type: audioBlob.type });

                // Upload
                const formData = new FormData();
                formData.append('file', audioFile);

                try {
                    const upRes = await fetch('/api/mensagens/upload', {
                        method: 'POST',
                        body: formData
                    });
                    const upData = await upRes.json();

                    if (upRes.ok && upData.status === 'success') {
                        // Envia mensagem com áudio
                        const msgRes = await fetch('/api/mensagens', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({
                                channel_id: channelId,
                                content: '🎙️ Mensagem de voz',
                                attachment_url: upData.url,
                                attachment_name: 'Mensagem de voz',
                                attachment_type: 'audio'
                            })
                        });

                        const msgData = await msgRes.json();
                        if (msgRes.ok && msgData.message) {
                            appendMessages([msgData.message]);
                            scrollToBottom(true);
                        }
                    }
                } catch (e) {
                    alert('Falha ao enviar mensagem de áudio.');
                } finally {
                    audioRecordHUD.style.display = 'none';
                    btnRecordAudio.style.display = 'inline-flex';
                    btnSendRecord.disabled = false;
                    btnSendRecord.textContent = '✓ Enviar Áudio';
                    form.querySelector('.chat-input-wrapper').style.opacity = '1';
                    form.querySelector('.chat-input-wrapper').style.pointerEvents = 'auto';
                }
            }, 300);
        });
    }

    // ── SELETOR DE EMOJIS FLUTUANTE ──────────────────────────────────────────
    if (btnToggleEmoji && emojiPickerPopup) {
        btnToggleEmoji.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = emojiPickerPopup.style.display === 'block';
            emojiPickerPopup.style.display = isOpen ? 'none' : 'block';
        });

        // Fechar ao clicar fora
        document.addEventListener('click', (e) => {
            if (!emojiPickerPopup.contains(e.target) && e.target !== btnToggleEmoji) {
                emojiPickerPopup.style.display = 'none';
            }
        });

        // Inserção de Emoji ao clicar
        emojiPickerPopup.addEventListener('click', (e) => {
            const emojiBtn = e.target.closest('.btn-emoji-item');
            if (!emojiBtn) return;

            const emoji = emojiBtn.textContent.trim();
            insertTextAtCursor(textarea, emoji);
            textarea.focus();
        });
    }

    function insertTextAtCursor(input, text) {
        if (!input) return;
        const start = input.selectionStart || 0;
        const end = input.selectionEnd || 0;
        const val = input.value;
        input.value = val.substring(0, start) + text + val.substring(end);
        input.selectionStart = input.selectionEnd = start + text.length;
    }

    // Suporte a colar imagem da área de transferência (Ctrl + V)
    if (textarea) {
        textarea.addEventListener('paste', (e) => {
            const items = (e.clipboardData || e.originalEvent.clipboardData).items;
            for (let i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    const blob = items[i].getAsFile();
                    handleFileUpload(blob);
                    e.preventDefault();
                    break;
                }
            }
        });

        // Envio com Enter (Shift+Enter para nova linha)
        textarea.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                submitMessage();
            }
        });
    }

    // ── Envio de Mensagem Assíncrono (Fetch) ──────────────────────────────────
    async function submitMessage() {
        if (isSending) return;

        const content = textarea.value.trim();
        const hasAttachment = currentAttachment !== null;

        if (!content && !hasAttachment) return;

        isSending = true;
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add('btn-loading');
        }

        const isCode = isCodeCheck ? isCodeCheck.checked : false;
        const codeLang = (isCode && codeLangSelect) ? codeLangSelect.value : null;

        const payload = {
            channel_id: channelId,
            content: content,
            is_code: isCode,
            code_language: codeLang,
            attachment_url: currentAttachment ? currentAttachment.url : null,
            attachment_name: currentAttachment ? currentAttachment.name : null,
            attachment_type: currentAttachment ? currentAttachment.type : null
        };

        try {
            const res = await fetch('/api/mensagens', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (res.ok && data.status === 'success' && data.message) {
                textarea.value = '';
                clearAttachment();
                if (isCodeCheck) isCodeCheck.checked = false;
                if (codeLangSelect) codeLangSelect.style.display = 'none';

                appendMessages([data.message]);
                scrollToBottom(true);
            } else {
                alert(data.error || 'Erro ao enviar mensagem.');
            }
        } catch (err) {
            alert('Erro de conexão ao enviar mensagem.');
        } finally {
            isSending = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-loading');
            }
            textarea.focus();
        }
    }

    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            submitMessage();
        });
    }

    if (isCodeCheck && codeLangSelect) {
        isCodeCheck.addEventListener('change', () => {
            codeLangSelect.style.display = isCodeCheck.checked ? 'inline-block' : 'none';
        });
    }

    // ── Deletar Mensagem ─────────────────────────────────────────────────────
    feed.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-msg-delete');
        if (!btn) return;

        const msgId = btn.dataset.id;
        if (!msgId) return;

        if (!confirm('Deseja realmente apagar esta mensagem?')) return;

        try {
            const res = await fetch('/api/mensagens/deletar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: msgId })
            });

            if (res.ok) {
                const msgItem = feed.querySelector(`.msg-item[data-id="${msgId}"]`);
                if (msgItem) {
                    msgItem.style.opacity = '0';
                    msgItem.style.transform = 'translateY(-10px)';
                    setTimeout(() => msgItem.remove(), 250);
                }
            }
        } catch (e) {
            alert('Falha ao apagar mensagem.');
        }
    });

    // ── Copiar Código Global ─────────────────────────────────────────────────
    window.copiarCodigo = function (btn) {
        const container = btn.closest('.bloco-codigo-container');
        if (!container) return;
        const codeEl = container.querySelector('code');
        if (!codeEl) return;

        navigator.clipboard.writeText(codeEl.textContent).then(() => {
            const orig = btn.textContent;
            btn.textContent = 'Copiado! ✓';
            btn.classList.add('copiado');
            setTimeout(() => {
                btn.textContent = orig;
                btn.classList.remove('copiado');
            }, 2000);
        });
    };

    scrollToBottom(true);
})();


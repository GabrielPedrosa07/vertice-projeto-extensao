/**
 * VÉRTICE — Mensagens Privadas (1x1), Amigos e Chamada Direta
 */

(function () {
    let currentFriendId = null;
    let currentFriendName = '';
    let lastMessageId = 0;
    let pollTimer = null;
    let localStream = null;
    let peerConn = null;
    let activeCallId = null;
    let callTimerInterval = null;
    let callSeconds = 0;
    let isRecordingAudio = false;
    let mediaRecorder = null;
    let audioChunks = [];
    let recordTimerInterval = null;
    let recordDuration = 0;

    const rtcConfig = {
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' }
        ]
    };

    function initPrivateChat() {
        // Tab switching
        document.querySelectorAll('.btn-private-nav').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.btn-private-nav').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.private-tab-content').forEach(c => {
                    c.style.display = 'none';
                    c.classList.remove('active');
                });

                const targetBtn = e.currentTarget;
                targetBtn.classList.add('active');
                const tabId = targetBtn.getAttribute('data-tab');
                const targetTab = document.getElementById(tabId);
                if (targetTab) {
                    targetTab.style.display = 'block';
                    targetTab.classList.add('active');
                }
            });
        });

        // Formulário de Envio de Mensagem
        const form = document.getElementById('private-message-form');
        if (form) {
            form.addEventListener('submit', handleSendMessage);
        }

        // Textarea Enter para Enviar
        const textarea = document.getElementById('private-textarea');
        if (textarea) {
            textarea.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    handleSendMessage(e);
                }
            });
        }

        // Filtro local de amigos
        const filterFriends = document.getElementById('filter-friends-input');
        if (filterFriends) {
            filterFriends.addEventListener('input', (e) => {
                const q = e.target.value.toLowerCase().trim();
                document.querySelectorAll('.private-friend-item').forEach(item => {
                    const name = item.getAttribute('data-name') || '';
                    item.style.display = name.includes(q) ? 'flex' : 'none';
                });
            });
        }

        // Busca de usuários para adicionar
        const btnSearchUser = document.getElementById('btn-search-user');
        if (btnSearchUser) {
            btnSearchUser.addEventListener('click', handleSearchUsers);
        }

        // Checkbox de código
        const codeCheck = document.getElementById('private-is-code');
        const codeLang = document.getElementById('private-code-lang');
        if (codeCheck && codeLang) {
            codeCheck.addEventListener('change', (e) => {
                codeLang.style.display = e.target.checked ? 'inline-block' : 'none';
            });
        }

        // Botão Ligar no Privado
        const btnCall = document.getElementById('btn-start-call');
        if (btnCall) {
            btnCall.addEventListener('click', () => {
                if (currentFriendId) window.startPrivateCall(currentFriendId, currentFriendName);
            });
        }

        // Botão Convidar para Sala
        const btnInvite = document.getElementById('btn-invite-community');
        if (btnInvite) {
            btnInvite.addEventListener('click', () => {
                const modal = document.getElementById('invite-community-modal');
                if (modal) modal.style.display = 'flex';
            });
        }

        const btnConfirmInvite = document.getElementById('btn-confirm-send-invite');
        if (btnConfirmInvite) {
            btnConfirmInvite.addEventListener('click', handleSendInvite);
        }

        // Gravação de Áudio
        initAudioRecorder();

        // Se URL tiver amigo pré-selecionado ou chamada para iniciar/atender
        const urlParams = new URLSearchParams(window.location.search);
        const preAmigo = urlParams.get('amigo');
        const preRoom = urlParams.get('room');
        const preStartCall = urlParams.get('startCall');

        if (preAmigo) {
            const el = document.querySelector(`.private-conv-item[onclick*="${preAmigo}"]`);
            if (el) {
                el.click();
            } else {
                window.selectConversation(parseInt(preAmigo, 10), 'Colega', 'Aluno FASM');
            }

            if (preRoom) {
                setTimeout(() => {
                    openPrivateCallScreen(preRoom, currentFriendName || 'Colega', false);
                }, 600);
            } else if (preStartCall) {
                setTimeout(() => {
                    window.startPrivateCall(parseInt(preAmigo, 10), currentFriendName || 'Colega');
                }, 600);
            }
        }

        // Se houver chamada privada salva na sessão (ex: usuário deu F5)
        const savedPrivCallRaw = sessionStorage.getItem('vertice_active_priv_call');
        if (savedPrivCallRaw && !preRoom && !preStartCall) {
            try {
                const saved = JSON.parse(savedPrivCallRaw);
                if (saved && saved.friendId && (Date.now() - saved.time < 120000)) {
                    setTimeout(() => {
                        window.selectConversation(saved.friendId, saved.friendName, 'Aluno FASM');
                        openPrivateCallScreen(saved.roomId, saved.friendName, saved.isInitiator);
                    }, 500);
                } else {
                    sessionStorage.removeItem('vertice_active_priv_call');
                }
            } catch (e) {}
        }
    }

    window.selectConversation = async function (friendId, friendName, badgeTitle) {
        currentFriendId = friendId;
        currentFriendName = friendName;
        lastMessageId = 0;

        const emptyState = document.getElementById('private-empty-state');
        const activePanel = document.getElementById('private-active-panel');
        if (emptyState) emptyState.style.display = 'none';
        if (activePanel) activePanel.style.display = 'flex';

        const nameEl = document.getElementById('active-friend-name');
        const avatarEl = document.getElementById('active-friend-avatar');
        const badgeEl = document.getElementById('active-friend-badge');

        if (nameEl) nameEl.textContent = friendName;
        if (avatarEl) avatarEl.textContent = friendName.substring(0, 2).toUpperCase();
        if (badgeEl) badgeEl.textContent = badgeTitle || '';

        // Highlight na sidebar
        document.querySelectorAll('.private-conv-item').forEach(c => c.classList.remove('active'));
        const activeItem = document.querySelector(`.private-conv-item[onclick*="${friendId}"]`);
        if (activeItem) {
            activeItem.classList.add('active');
            const unread = activeItem.querySelector('.unread-badge');
            if (unread) unread.remove();
        }

        clearInterval(pollTimer);
        await loadMessages(true);
        pollTimer = setInterval(() => loadMessages(false), 2500);
    };

    async function loadMessages(initial = false) {
        if (!currentFriendId) return;

        const feed = document.getElementById('private-feed');
        if (!feed) return;

        try {
            const res = await fetch(`/api/private/messages?friend_id=${currentFriendId}&after_id=${lastMessageId}`);
            const data = await res.json();

            if (data.status === 'success' && data.messages) {
                if (initial && data.messages.length === 0) {
                    feed.innerHTML = `
                        <div class="private-feed-empty">
                            <span>👋</span>
                            <p>Início da conversa com <strong>${escapeHtml(currentFriendName)}</strong>. Diga olá!</p>
                        </div>
                    `;
                    return;
                }

                if (initial) feed.innerHTML = '';

                data.messages.forEach(m => {
                    if (m.id > lastMessageId) lastMessageId = m.id;
                    appendMessageBubble(m);
                });

                if (data.messages.length > 0) {
                    feed.scrollTop = feed.scrollHeight;
                }
            }
        } catch (e) {}
    }

    function appendMessageBubble(m) {
        const feed = document.getElementById('private-feed');
        if (!feed) return;

        const currentUserId = parseInt(document.body.getAttribute('data-user-id') || '0', 10);
        const isMine = (parseInt(m.sender_id, 10) === currentUserId);

        const bubble = document.createElement('div');
        bubble.className = `priv-msg-item ${isMine ? 'priv-msg-mine' : 'priv-msg-theirs'}`;
        bubble.setAttribute('data-id', m.id);

        let contentHtml = '';
        if (m.is_code_block == 1) {
            contentHtml = `
                <div class="bloco-codigo-container">
                    <div class="bloco-codigo-cabecalho">
                        <span class="bloco-codigo-lang">${escapeHtml(m.code_language || 'código')}</span>
                    </div>
                    <pre class="bloco-codigo"><code>${escapeHtml(m.content)}</code></pre>
                </div>
            `;
        } else if (m.content) {
            contentHtml = `<div class="priv-msg-text">${nl2br(escapeHtml(m.content))}</div>`;
        }

        let anexoHtml = '';
        if (m.attachment_url) {
            if (m.attachment_type === 'image') {
                anexoHtml = `<div class="msg-anexo"><img src="${escapeHtml(m.attachment_url)}" class="chat-img-thumb" loading="lazy"></div>`;
            } else if (m.attachment_type === 'audio') {
                anexoHtml = `<div class="msg-anexo"><audio controls src="${escapeHtml(m.attachment_url)}"></audio></div>`;
            } else {
                anexoHtml = `<div class="msg-anexo"><a href="${escapeHtml(m.attachment_url)}" download class="anexo-download-card">📁 ${escapeHtml(m.attachment_name || 'arquivo')}</a></div>`;
            }
        }

        bubble.innerHTML = `
            <div class="priv-msg-bubble">
                ${contentHtml}
                ${anexoHtml}
                <div class="priv-msg-time">${formatTime(m.created_at)}</div>
            </div>
        `;

        feed.appendChild(bubble);
    }

    async function handleSendMessage(e) {
        if (e) e.preventDefault();
        if (!currentFriendId) return;

        const textarea = document.getElementById('private-textarea');
        const content = textarea ? textarea.value.trim() : '';
        const isCode = document.getElementById('private-is-code') ? document.getElementById('private-is-code').checked : false;
        const codeLang = document.getElementById('private-code-lang') ? document.getElementById('private-code-lang').value : 'PHP';

        if (!content) return;

        textarea.value = '';

        try {
            const res = await fetch('/api/private/messages/send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    receiver_id: currentFriendId,
                    content: content,
                    is_code: isCode ? 1 : 0,
                    code_language: codeLang
                })
            });
            const data = await res.json();

            if (data.status === 'success' && data.message) {
                if (data.message.id > lastMessageId) lastMessageId = data.message.id;
                appendMessageBubble(data.message);
                const feed = document.getElementById('private-feed');
                if (feed) feed.scrollTop = feed.scrollHeight;
            }
        } catch (err) {}
    }

    // ── CHAMADA PRIVADA (WEBRTC) ─────────────────────────────────────────────

    let callSignalingInterval = null;

    window.startPrivateCall = async function (friendId, friendName) {
        currentFriendId = parseInt(friendId, 10);
        currentFriendName = friendName;

        try {
            const res = await fetch('/api/private/call/start', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ receiver_id: currentFriendId })
            });
            const data = await res.json();

            if (data.status === 'success') {
                activeCallId = data.call_id;
                openPrivateCallScreen(data.room_id, friendName, true);
            }
        } catch (e) {
            alert('Erro ao iniciar chamada.');
        }
    };

    async function openPrivateCallScreen(roomId, friendName, isInitiator = false) {
        const screen = document.getElementById('private-call-screen');
        if (!screen) return;

        screen.style.display = 'flex';
        document.getElementById('private-call-status-label').textContent = isInitiator ? `Chamando ${friendName}...` : `Conectando com ${friendName}...`;

        const remoteInitials = document.getElementById('remote-priv-avatar-initials');
        const remoteLabel = document.getElementById('remote-video-priv-label');
        const remoteDesc = document.getElementById('remote-priv-avatar-desc');
        const remoteAvatar = document.getElementById('remote-priv-avatar');

        if (remoteInitials && friendName) {
            remoteInitials.textContent = friendName.substring(0, 2).toUpperCase();
        }
        if (remoteLabel && friendName) {
            remoteLabel.textContent = friendName;
        }
        if (remoteDesc) {
            remoteDesc.textContent = isInitiator ? `Chamando ${friendName}...` : `Conectando...`;
        }
        if (remoteAvatar) remoteAvatar.style.display = 'flex';

        try {
            localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: true });
            const localVideo = document.getElementById('private-local-video');
            const localAvatar = document.getElementById('local-priv-avatar');
            if (localVideo) {
                localVideo.srcObject = localStream;
                localVideo.play().catch(() => {});
            }
            if (localAvatar) localAvatar.style.display = 'none';

            initPeerConnection(roomId, isInitiator, friendName);
            startCallTimer();
        } catch (err) {
            console.warn('Câmera não acessível, tentando apenas áudio:', err);
            try {
                localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
                const localAvatar = document.getElementById('local-priv-avatar');
                if (localAvatar) localAvatar.style.display = 'flex';

                initPeerConnection(roomId, isInitiator, friendName);
                startCallTimer();
                document.getElementById('private-call-status-label').textContent = '🎙️ Chamada de Áudio Ativa';
            } catch (eAudio) {
                document.getElementById('private-call-status-label').textContent = 'Permissão de microfone necessária.';
            }
        }
    }

    window.fullscreenPrivateTile = function (tileId) {
        const tile = document.getElementById(tileId);
        if (!tile) return;
        const video = tile.querySelector('video');
        const target = video || tile;

        if (document.fullscreenElement || document.webkitFullscreenElement) {
            if (document.exitFullscreen) document.exitFullscreen().catch(()=>{});
            else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
        } else {
            const req = target.requestFullscreen || target.webkitRequestFullscreen || target.mozRequestFullScreen || target.msRequestFullscreen;
            if (req) {
                req.call(target).catch(() => {
                    const reqTile = tile.requestFullscreen || tile.webkitRequestFullscreen;
                    if (reqTile) reqTile.call(tile).catch(()=>{});
                });
            }
        }
    };

    window.togglePrivateCallLayout = function () {
        const grid = document.querySelector('.private-call-videos-grid');
        const text = document.getElementById('priv-layout-text');
        if (!grid) return;

        const isSplit = grid.classList.toggle('mode-split');
        if (text) {
            text.textContent = isSplit ? 'Modo PiP' : 'Lado a Lado';
        }
    };

    window.togglePrivateChatUnderCall = function () {
        const feed = document.getElementById('private-feed');
        const footer = document.querySelector('.private-chat-footer');
        const text = document.getElementById('priv-chat-toggle-text');
        if (!feed) return;

        const isHidden = feed.style.display === 'none';
        feed.style.display = isHidden ? 'flex' : 'none';
        if (footer) footer.style.display = isHidden ? 'block' : 'none';
        if (text) {
            text.textContent = isHidden ? 'Ocultar Chat' : 'Mostrar Chat';
        }
    };

    async function initPeerConnection(roomId, isInitiator, friendName = '') {
        const myId = parseInt(document.body.getAttribute('data-user-id') || '0', 10);
        const dmChannelId = 900000 + (Math.min(myId, currentFriendId) * 1000) + Math.max(myId, currentFriendId);

        peerConn = new RTCPeerConnection(rtcConfig);

        if (localStream) {
            localStream.getTracks().forEach(track => peerConn.addTrack(track, localStream));
        }

        const remoteVideo = document.getElementById('private-remote-video');
        const remoteAvatar = document.getElementById('remote-priv-avatar');
        const remoteDesc = document.getElementById('remote-priv-avatar-desc');
        const remoteStream = new MediaStream();

        if (remoteVideo) {
            remoteVideo.srcObject = remoteStream;
            remoteVideo.onloadedmetadata = () => remoteVideo.play().catch(() => {});
            remoteVideo.oncanplay = () => remoteVideo.play().catch(() => {});
        }

        peerConn.ontrack = (e) => {
            if (e.streams && e.streams[0]) {
                e.streams[0].getTracks().forEach(track => {
                    if (!remoteStream.getTracks().some(t => t.id === track.id)) {
                        remoteStream.addTrack(track);
                    }
                });
            } else if (e.track) {
                if (!remoteStream.getTracks().some(t => t.id === e.track.id)) {
                    remoteStream.addTrack(e.track);
                }
            }

            if (remoteVideo) remoteVideo.play().catch(() => {});

            const hasVideo = remoteStream.getVideoTracks().some(t => t.enabled && t.readyState === 'live');
            if (remoteAvatar) {
                remoteAvatar.style.display = hasVideo ? 'none' : 'flex';
                if (!hasVideo && remoteDesc) {
                    remoteDesc.textContent = '🎙️ Conectado (Áudio)';
                }
            }
            document.getElementById('private-call-status-label').textContent = '🟢 Chamada Privada Conectada';
        };

        let pendingCandidates = [];

        async function addCandidateSafe(candidate) {
            if (peerConn && peerConn.remoteDescription && peerConn.remoteDescription.type) {
                try {
                    await peerConn.addIceCandidate(new RTCIceCandidate(candidate));
                } catch (e) {}
            } else {
                pendingCandidates.push(candidate);
            }
        }

        async function flushPendingCandidates() {
            if (!peerConn) return;
            while (pendingCandidates.length > 0) {
                const cand = pendingCandidates.shift();
                try {
                    await peerConn.addIceCandidate(new RTCIceCandidate(cand));
                } catch (e) {}
            }
        }

        peerConn.onicecandidate = async (e) => {
            if (e.candidate) {
                try {
                    await fetch('/api/call/signal', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            channel_id: dmChannelId,
                            receiver_id: currentFriendId,
                            type: 'candidate',
                            payload: JSON.stringify(e.candidate)
                        })
                    });
                } catch (err) {}
            }
        };

        // Salva estado da chamada para persistir após F5
        sessionStorage.setItem('vertice_active_priv_call', JSON.stringify({
            friendId: currentFriendId,
            friendName: currentFriendName,
            roomId: roomId,
            isInitiator: isInitiator,
            time: Date.now()
        }));

        // Registra presença na call privada
        await fetch('/api/call/join', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                channel_id: dmChannelId,
                peer_id: 'dm_' + myId
            })
        }).catch(() => {});

        let offerSent = false;
        async function sendOffer() {
            if (!peerConn || peerConn.signalingState === 'closed') return;
            try {
                const offer = await peerConn.createOffer({
                    offerToReceiveAudio: true,
                    offerToReceiveVideo: true
                });
                await peerConn.setLocalDescription(offer);
                await fetch('/api/call/signal', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        channel_id: dmChannelId,
                        receiver_id: currentFriendId,
                        type: 'offer',
                        payload: JSON.stringify(offer)
                    })
                });
                offerSent = true;
            } catch (e) {}
        }

        // Se for o iniciador, cria e envia a oferta SDP imediatamente
        if (isInitiator) {
            sendOffer();
        }

        // Loop de verificação de sinalização a cada 1.5s
        clearInterval(callSignalingInterval);
        callSignalingInterval = setInterval(async () => {
            if (!peerConn) return;
            try {
                const res = await fetch(`/api/call/signals?channel_id=${dmChannelId}`);
                if (!res.ok) return;
                const data = await res.json();
                if (data.status === 'success' && data.signals) {
                    for (const sig of data.signals) {
                        if (sig.sender_id != myId) {
                            if (sig.type === 'offer') {
                                await peerConn.setRemoteDescription(new RTCSessionDescription(JSON.parse(sig.payload)));
                                await flushPendingCandidates();
                                const answer = await peerConn.createAnswer();
                                await peerConn.setLocalDescription(answer);
                                await fetch('/api/call/signal', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        channel_id: dmChannelId,
                                        receiver_id: currentFriendId,
                                        type: 'answer',
                                        payload: JSON.stringify(answer)
                                    })
                                });
                            } else if (sig.type === 'answer') {
                                if (peerConn.signalingState !== 'stable') {
                                    await peerConn.setRemoteDescription(new RTCSessionDescription(JSON.parse(sig.payload)));
                                    await flushPendingCandidates();
                                }
                            } else if (sig.type === 'candidate') {
                                await addCandidateSafe(JSON.parse(sig.payload));
                            }
                        }
                    }
                }
            } catch (e) {}
        }, 1500);

        // Controles da Chamada
        document.getElementById('btn-priv-mic').onclick = () => {
            const audioTrack = localStream ? localStream.getAudioTracks()[0] : null;
            if (audioTrack) {
                audioTrack.enabled = !audioTrack.enabled;
                document.getElementById('btn-priv-mic').classList.toggle('ctrl-off', !audioTrack.enabled);
            }
        };

        document.getElementById('btn-priv-cam').onclick = () => {
            const videoTrack = localStream ? localStream.getVideoTracks()[0] : null;
            if (videoTrack) {
                videoTrack.enabled = !videoTrack.enabled;
                document.getElementById('btn-priv-cam').classList.toggle('ctrl-off', !videoTrack.enabled);
            }
        };

        document.getElementById('btn-priv-screen').onclick = async () => {
            try {
                const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                const screenTrack = screenStream.getVideoTracks()[0];
                const sender = peerConn.getSenders().find(s => s.track && s.track.kind === 'video');
                if (sender) sender.replaceTrack(screenTrack);
                document.getElementById('private-local-video').srcObject = screenStream;

                screenTrack.onended = () => {
                    const camTrack = localStream.getVideoTracks()[0];
                    if (sender && camTrack) sender.replaceTrack(camTrack);
                    document.getElementById('private-local-video').srcObject = localStream;
                };
            } catch (e) {}
        };

        document.getElementById('btn-priv-hangup').onclick = endPrivateCall;
    }

    function startCallTimer() {
        callSeconds = 0;
        clearInterval(callTimerInterval);
        callTimerInterval = setInterval(() => {
            callSeconds++;
            const mins = String(Math.floor(callSeconds / 60)).padStart(2, '0');
            const secs = String(callSeconds % 60).padStart(2, '0');
            const timerEl = document.getElementById('private-call-timer');
            if (timerEl) timerEl.textContent = `${mins}:${secs}`;
        }, 1000);
    }

    function endPrivateCall() {
        clearInterval(callSignalingInterval);
        if (localStream) {
            localStream.getTracks().forEach(t => t.stop());
            localStream = null;
        }
        if (peerConn) {
            peerConn.close();
            peerConn = null;
        }
        clearInterval(callTimerInterval);

        const myId = parseInt(document.body.getAttribute('data-user-id') || '0', 10);
        const dmChannelId = 900000 + (Math.min(myId, currentFriendId) * 1000) + Math.max(myId, currentFriendId);
        fetch('/api/call/leave', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ channel_id: dmChannelId })
        }).catch(() => {});

        if (activeCallId) {
            fetch('/api/private/call/end', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ call_id: activeCallId })
            }).catch(() => {});
            activeCallId = null;
        }

        const screen = document.getElementById('private-call-screen');
        if (screen) screen.style.display = 'none';
    }

    // ── BUSCA DE USUÁRIOS E AMIZADES ─────────────────────────────────────────

    async function handleSearchUsers() {
        const input = document.getElementById('search-user-input');
        const resultsEl = document.getElementById('search-users-results');
        const q = input ? input.value.trim() : '';
        if (!q || !resultsEl) return;

        resultsEl.innerHTML = '<div style="font-size:11px; color:var(--cor-apagado);">Buscando...</div>';

        try {
            const res = await fetch(`/api/private/users/search?q=${encodeURIComponent(q)}`);
            const data = await res.json();

            if (data.status === 'success' && data.users) {
                if (data.users.length === 0) {
                    resultsEl.innerHTML = '<div style="font-size:11px; color:var(--cor-apagado);">Nenhum colega encontrado.</div>';
                    return;
                }

                resultsEl.innerHTML = data.users.map(u => `
                    <div class="search-user-card">
                        <div>
                            <strong style="font-size:12px; color:#fff;">${escapeHtml(u.name)}</strong>
                            <div style="font-size:10px; color:var(--cor-apagado);">${escapeHtml(u.email)}</div>
                        </div>
                        <div>
                            ${u.friendship_status === 'friends' ? '<span style="font-size:10px; color:#10b981;">✓ Já são amigos</span>' :
                              u.friendship_status === 'pending_sent' ? '<span style="font-size:10px; color:#f59e0b;">Solicitado</span>' :
                              `<button type="button" class="botao botao-primario" style="padding:4px 10px; font-size:11px;" onclick="window.sendFriendRequest(${u.id})">+ Adicionar</button>`}
                        </div>
                    </div>
                `).join('');
            }
        } catch (e) {}
    }

    window.sendFriendRequest = async function (friendId) {
        try {
            const res = await fetch('/api/private/friends/request', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ friend_id: friendId })
            });
            const data = await res.json();
            alert(data.message || 'Solicitação enviada!');
            handleSearchUsers();
        } catch (e) {}
    };

    window.respondFriendRequest = async function (requestId, accept) {
        const url = accept ? '/api/private/friends/accept' : '/api/private/friends/decline';
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: requestId })
            });
            const data = await res.json();
            if (data.status === 'success') {
                const row = document.getElementById(`pending-req-${requestId}`);
                if (row) row.remove();
                if (accept) location.reload();
            }
        } catch (e) {}
    };

    async function handleSendInvite() {
        if (!currentFriendId) return;
        const catId = document.getElementById('invite-category-select').value;

        try {
            const res = await fetch('/api/private/invite/send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    receiver_id: currentFriendId,
                    category_id: catId,
                    channel_id: 1
                })
            });
            const data = await res.json();
            alert(data.message || 'Convite enviado!');
            document.getElementById('invite-community-modal').style.display = 'none';
        } catch (e) {
            alert('Erro ao enviar convite.');
        }
    }

    // ── GRAVADOR DE ÁUDIO ────────────────────────────────────────────────────

    function initAudioRecorder() {
        const btnRecord = document.getElementById('btn-priv-record-audio');
        if (!btnRecord) return;

        btnRecord.addEventListener('click', async () => {
            if (isRecordingAudio) return;
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];

                mediaRecorder.ondataavailable = (e) => {
                    if (e.data.size > 0) audioChunks.push(e.data);
                };

                mediaRecorder.onstop = async () => {
                    stream.getTracks().forEach(t => t.stop());
                };

                mediaRecorder.start();
                isRecordingAudio = true;
                showAudioHud(true);
            } catch (err) {
                alert('Acesso ao microfone necessário para gravar áudio.');
            }
        });

        document.getElementById('btn-cancel-priv-audio').onclick = () => {
            if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
            isRecordingAudio = false;
            showAudioHud(false);
        };

        document.getElementById('btn-send-priv-audio').onclick = async () => {
            if (!mediaRecorder) return;
            mediaRecorder.stop();
            isRecordingAudio = false;
            showAudioHud(false);

            setTimeout(async () => {
                const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                const formData = new FormData();
                formData.append('anexo', audioBlob, 'audio_privado.webm');

                try {
                    const upRes = await fetch('/api/upload-anexo', { method: 'POST', body: formData });
                    const upData = await upRes.json();

                    if (upData.status === 'success') {
                        await fetch('/api/private/messages/send', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                receiver_id: currentFriendId,
                                content: 'Mensagem de voz',
                                attachment_url: upData.url,
                                attachment_name: 'audio_privado.webm',
                                attachment_type: 'audio'
                            })
                        });
                        loadMessages(false);
                    }
                } catch (e) {}
            }, 300);
        };
    }

    function showAudioHud(show) {
        const hud = document.getElementById('private-audio-hud');
        if (!hud) return;
        hud.style.display = show ? 'flex' : 'none';

        if (show) {
            recordDuration = 0;
            recordTimerInterval = setInterval(() => {
                recordDuration++;
                const mins = String(Math.floor(recordDuration / 60)).padStart(2, '0');
                const secs = String(recordDuration % 60).padStart(2, '0');
                document.getElementById('private-audio-timer').textContent = `${mins}:${secs}`;
            }, 1000);
        } else {
            clearInterval(recordTimerInterval);
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
    }

    function nl2br(str) {
        if (!str) return '';
        return str.replace(/\n/g, '<br>');
    }

    function formatTime(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }

    window.initPrivateChat = initPrivateChat;
    document.addEventListener('DOMContentLoaded', initPrivateChat);
})();

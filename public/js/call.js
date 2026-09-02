/**
 * VÉRTICE — Salas de Chamada de Voz, Vídeo e Compartilhamento de Tela
 * WebRTC P2P Mesh + Volume Control + Cross-Browser Fullscreen + Reactions & Hand Raise
 */

(function () {
    'use strict';

    // Elementos da Interface da Chamada
    const callContainer = document.getElementById('call-container');
    const btnJoinCall = document.getElementById('btn-join-call');
    const btnLeaveCall = document.getElementById('btn-leave-call');
    const btnToggleMic = document.getElementById('btn-toggle-mic');
    const btnToggleCam = document.getElementById('btn-toggle-cam');
    const btnShareScreen = document.getElementById('btn-share-screen');
    const btnFullscreenCall = document.getElementById('btn-fullscreen-call');
    const btnRaiseHand = document.getElementById('btn-raise-hand');
    const btnRemoteControl = document.getElementById('btn-remote-control');
    const btnToggleReactions = document.getElementById('btn-toggle-reactions');
    const callReactionsMenu = document.getElementById('call-reactions-menu');
    const videoGrid = document.getElementById('call-video-grid');
    const callParticipantsCount = document.getElementById('call-participants-count');

    // Modais e Banners de Controle Remoto
    const remoteModal = document.getElementById('remote-control-modal');
    const remoteRequesterName = document.getElementById('remote-requester-name');
    const remoteActiveBar = document.getElementById('remote-control-active-bar');
    const remoteControllerName = document.getElementById('remote-controller-name');

    const channelInput = document.getElementById('chat-channel-id');
    if (!callContainer || !btnJoinCall || !channelInput) return;

    const channelId = parseInt(channelInput.value, 10);
    const currentUserId = parseInt(document.body.dataset.userId || '0', 10);
    const currentUserName = document.body.dataset.userName || 'Aluno';

    // Servidores STUN públicos para NAT Traversal em redes Radmin VPN / Wi-Fi
    const rtcConfig = {
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' },
            { urls: 'stun:stun2.l.google.com:19302' },
            { urls: 'stun:stun.services.mozilla.com' },
            { urls: 'stun:global.stun.twilio.com:3478' }
        ]
    };

    let localStream = null;
    let screenStream = null;
    let isMuted = false;
    let isVideoOff = false;
    let isScreenSharing = false;
    let isHandRaised = false;
    let inCall = false;
    let myPeerId = 'peer_' + currentUserId + '_' + Math.random().toString(36).substring(2, 9);
    let focusedTileId = null;

    // Estado do Controle Remoto AnyDesk
    let isControllingRemote = false;
    let isBeingControlled = false;
    let controlledUserId = null;
    let controllingUserId = null;
    let pendingRequesterId = null;
    let pendingRequesterName = '';
    let remotePeerSharingScreenId = null;
    let lastMouseMoveTime = 0;

    // Volume salvo por participante (peerId -> volume 0..100)
    const peerVolumes = new Map();
    // Mapa de Peers: peerUserId -> { pc, userName, remoteStream, pendingCandidates }
    const peerConnections = new Map();
    let signalingInterval = null;

    // ── Verificação de Contexto Seguro (Secure Context / HTTPS / Localhost) ──
    function checkMediaDevicesSupport() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            const currentOrigin = window.location.origin;
            const msg = `⚠️ O navegador bloqueou o acesso à Câmera e Microfone por este endereço (${currentOrigin}).\n\n` +
                        `Para liberar no Chrome ou Edge:\n` +
                        `1. Abra uma nova aba e acesse: chrome://flags/#unsafely-treat-insecure-origin-as-secure (ou edge://flags)\n` +
                        `2. Ative a opção e adicione a URL: ${currentOrigin}\n` +
                        `3. Clique em Relaunch para reiniciar o navegador.\n\n` +
                        `Ou acesse usando http://localhost:8000 caso esteja no mesmo computador.`;
            alert(msg);
            return false;
        }
        return true;
    }

    // ── Obter Mídia Local (Microfone & Câmera com Fallbacks) ─────────────────
    async function getLocalMedia() {
        if (!checkMediaDevicesSupport()) return false;

        // 1. Tenta Áudio + Vídeo
        try {
            localStream = await navigator.mediaDevices.getUserMedia({
                audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true },
                video: { width: { ideal: 1280, max: 1920 }, height: { ideal: 720, max: 1080 }, frameRate: { ideal: 30 } }
            });
            isVideoOff = false;
            isMuted = false;
        } catch (errVideo) {
            console.warn('Câmera indisponível ou permissão negada. Tentando apenas áudio...', errVideo);
            // 2. Fallback: Apenas Áudio
            try {
                localStream = await navigator.mediaDevices.getUserMedia({
                    audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true },
                    video: false
                });
                isVideoOff = true;
                isMuted = false;
            } catch (errAudio) {
                console.warn('Microfone indisponível ou permissão negada. Criando stream mudo:', errAudio);
                // 3. Fallback: Cria stream silencioso para permitir ouvir os colegas
                localStream = new MediaStream();
                isVideoOff = true;
                isMuted = true;
            }
        }

        renderLocalVideoTile();
        setupAudioVisualizer(localStream, 'local-video-tile');
        updateMediaControlButtons();
        return true;
    }

    // ── Renderização do Tile Local ───────────────────────────────────────────
    function renderLocalVideoTile() {
        let tile = document.getElementById('local-video-tile');
        if (!tile) {
            tile = document.createElement('div');
            tile.id = 'local-video-tile';
            tile.className = 'video-tile video-tile-local animate-fade-in';
            tile.innerHTML = `
                <video id="local-video" autoplay muted playsinline></video>
                <div id="local-avatar-placeholder" class="tile-avatar-placeholder" style="display:none;">
                    <div class="tile-avatar-circle">${escapeHtml(currentUserName.substring(0, 2).toUpperCase())}</div>
                    <span class="tile-avatar-desc">Câmera Desligada</span>
                </div>
                <div class="video-tile-badge-screen" id="local-screen-badge" style="display:none;">🖥️ Tela Compartilhada</div>
                <div class="tile-hand-badge" id="local-hand-badge" style="display:none;">✋ Mão Levantada</div>
                <div class="reactions-container" id="local-reactions-container"></div>
                <div class="video-tile-info">
                    <span class="video-tile-name">${escapeHtml(currentUserName)} (Você)</span>
                    <span class="video-tile-status">
                        <span id="local-mic-icon">🎙️</span>
                        <span id="local-cam-icon">📹</span>
                    </span>
                </div>
                <button type="button" class="btn-tile-focus" title="Fixar na tela principal" onclick="window.toggleTileFocus('local-video-tile')">⛶</button>
            `;
            videoGrid.appendChild(tile);
        }

        const videoEl = tile.querySelector('#local-video');
        const avatarEl = tile.querySelector('#local-avatar-placeholder');
        const screenBadge = tile.querySelector('#local-screen-badge');

        if (isScreenSharing && screenStream) {
            videoEl.srcObject = screenStream;
            videoEl.style.display = 'block';
            avatarEl.style.display = 'none';
            screenBadge.style.display = 'block';
            tile.classList.add('tile-screen-share');
        } else if (localStream && localStream.getVideoTracks().length > 0 && !isVideoOff) {
            videoEl.srcObject = localStream;
            videoEl.style.display = 'block';
            avatarEl.style.display = 'none';
            screenBadge.style.display = 'none';
            tile.classList.remove('tile-screen-share');
        } else {
            videoEl.style.display = 'none';
            avatarEl.style.display = 'flex';
            screenBadge.style.display = 'none';
            tile.classList.remove('tile-screen-share');
        }

        updateGridLayout();
    }

    function getActiveChannelId() {
        const el = document.getElementById('chat-channel-id');
        let id = parseInt(el ? el.value : (channelId || '0'), 10);
        if (!id || id <= 0) {
            const urlParams = new URLSearchParams(window.location.search);
            id = parseInt(urlParams.get('canal') || '1', 10);
        }
        return id > 0 ? id : 1;
    }

    // ── Entrar na Chamada ────────────────────────────────────────────────────
    async function joinCall() {
        if (inCall) return;

        btnJoinCall.disabled = true;
        btnJoinCall.textContent = '⏳ Conectando...';

        const mediaOk = await getLocalMedia();
        if (!mediaOk) {
            btnJoinCall.disabled = false;
            btnJoinCall.textContent = '📞 Entrar na Call da Matéria';
            return;
        }

        const currentChannelId = getActiveChannelId();
        if (currentChannelId <= 0) {
            alert('Canal não selecionado ou inválido.');
            btnJoinCall.disabled = false;
            btnJoinCall.textContent = '📞 Entrar na Call da Matéria';
            return;
        }

        try {
            const res = await fetch('/api/call/join', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    channel_id: currentChannelId,
                    peer_id: myPeerId
                })
            });

            if (res.status === 401) {
                alert('Sua sessão expirou. Recarregue a página para fazer login novamente.');
                return;
            }

            const data = await res.json();
            if (res.ok && data.status === 'success') {
                inCall = true;
                callContainer.style.display = 'flex';
                btnJoinCall.style.display = 'none';

                // Inicia conexões WebRTC com os colegas já presentes
                if (data.peers && Array.isArray(data.peers)) {
                    for (const peer of data.peers) {
                        try {
                            const remoteUserId = parseInt(peer.user_id, 10);
                            if (remoteUserId !== currentUserId) {
                                initiatePeerConnection(remoteUserId, peer.user_name || 'Colega');
                            }
                        } catch (pErr) {
                            console.warn('Aviso: falha ao iniciar conexão inicial com colega:', pErr);
                        }
                    }
                }

                // Inicia ciclo de sinalização
                if (signalingInterval) clearInterval(signalingInterval);
                signalingInterval = setInterval(pollSignaling, 1500);
            } else {
                alert(data.error || 'Não foi possível entrar na chamada.');
            }
        } catch (err) {
            console.error('Erro de conexão na chamada WebRTC:', err);
            alert('Não foi possível conectar à chamada. Verifique sua conexão com o servidor.');
        } finally {
            btnJoinCall.disabled = false;
            btnJoinCall.textContent = '📞 Entrar na Call da Matéria';
        }
    }

    // ── Sair da Chamada ──────────────────────────────────────────────────────
    async function leaveCall() {
        if (!inCall) return;

        if (signalingInterval) clearInterval(signalingInterval);

        peerConnections.forEach(({ pc }) => {
            if (pc) pc.close();
        });
        peerConnections.clear();

        if (screenStream) {
            screenStream.getTracks().forEach(t => t.stop());
            screenStream = null;
        }

        if (localStream) {
            localStream.getTracks().forEach(t => t.stop());
            localStream = null;
        }

        isScreenSharing = false;
        isHandRaised = false;
        focusedTileId = null;
        videoGrid.innerHTML = '';
        callContainer.style.display = 'none';
        btnJoinCall.style.display = 'inline-flex';
        inCall = false;

        exitFullscreenSafe();

        try {
            const currentChannelId = getActiveChannelId();
            await fetch('/api/call/leave', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ channel_id: currentChannelId })
            });
        } catch (e) {}
    }

    // ── Gerenciamento de Conexões WebRTC Peer-to-Peer ────────────────────────
    function getOrCreatePeerConnection(remoteUserId, remoteUserName, isInitiator = false) {
        if (peerConnections.has(remoteUserId)) {
            return peerConnections.get(remoteUserId);
        }

        const pc = new RTCPeerConnection(rtcConfig);
        const peerData = {
            userId: remoteUserId,
            userName: remoteUserName,
            pc: pc,
            remoteStream: new MediaStream(),
            isInitiator: isInitiator,
            pendingCandidates: []
        };

        // Adiciona tracks locais à conexão
        const streamToSend = isScreenSharing && screenStream ? screenStream : localStream;
        if (streamToSend) {
            streamToSend.getTracks().forEach(track => {
                pc.addTrack(track, streamToSend);
            });
        }

        // Recebimento de áudio e vídeo do colega
        pc.ontrack = (event) => {
            if (event.streams && event.streams[0]) {
                event.streams[0].getTracks().forEach(track => {
                    if (!peerData.remoteStream.getTracks().some(t => t.id === track.id)) {
                        peerData.remoteStream.addTrack(track);
                    }
                });
            } else if (event.track) {
                peerData.remoteStream.addTrack(event.track);
            }
            renderRemoteVideoTile(remoteUserId, remoteUserName, peerData.remoteStream);
        };

        // Envio de ICE Candidates
        pc.onicecandidate = (event) => {
            if (event.candidate) {
                sendSignal(remoteUserId, 'candidate', event.candidate);
            }
        };

        pc.onconnectionstatechange = () => {
            if (pc.connectionState === 'disconnected' || pc.connectionState === 'failed' || pc.connectionState === 'closed') {
                removeRemoteVideoTile(remoteUserId);
                peerConnections.delete(remoteUserId);
                updateGridLayout();
            }
        };

        peerConnections.set(remoteUserId, peerData);
        return peerData;
    }

    async function initiatePeerConnection(remoteUserId, remoteUserName) {
        const peer = getOrCreatePeerConnection(remoteUserId, remoteUserName, true);
        try {
            const offer = await peer.pc.createOffer({
                offerToReceiveAudio: true,
                offerToReceiveVideo: true
            });
            await peer.pc.setLocalDescription(offer);
            sendSignal(remoteUserId, 'offer', offer);
        } catch (err) {
            console.error('Erro ao criar offer SDP:', err);
        }
    }

    // ── Sinalização (Polling a cada 1.5s) ────────────────────────────────────
    async function sendSignal(receiverId, type, payload) {
        try {
            const currentChannelId = getActiveChannelId();
            await fetch('/api/call/signal', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    channel_id: currentChannelId,
                    receiver_id: receiverId,
                    type: type,
                    payload: payload
                })
            });
        } catch (e) {}
    }

    async function broadcastSignal(type, payload) {
        peerConnections.forEach(({ userId }) => {
            sendSignal(userId, type, payload);
        });
    }

    async function pollSignaling() {
        if (!inCall) return;

        try {
            const currentChannelId = getActiveChannelId();
            const res = await fetch('/api/call/heartbeat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    channel_id: currentChannelId,
                    is_muted: isMuted,
                    is_video_off: isVideoOff,
                    is_screen_sharing: isScreenSharing,
                    is_hand_raised: isHandRaised
                })
            });

            if (!res.ok) return;
            const data = await res.json();

            // Atualiza contagem de pessoas na call
            const total = (data.peers ? data.peers.length : 0) + 1;
            if (callParticipantsCount) {
                callParticipantsCount.textContent = `${total} ${total === 1 ? 'pessoa' : 'pessoas'} na call`;
            }

            // Descobre novos colegas que entraram
            if (data.peers && Array.isArray(data.peers)) {
                for (const p of data.peers) {
                    const uid = parseInt(p.user_id, 10);
                    if (uid !== currentUserId && !peerConnections.has(uid)) {
                        if (currentUserId < uid) {
                            initiatePeerConnection(uid, p.user_name || 'Colega');
                        }
                    }
                }
            }

            // Processa sinais SDP, ICE, reações e mão levantada
            if (data.signals && Array.isArray(data.signals)) {
                for (const s of data.signals) {
                    await handleIncomingSignal(s);
                }
            }
        } catch (err) {}
    }

    async function handleIncomingSignal(signal) {
        const senderId = parseInt(signal.sender_id, 10);
        const senderName = signal.sender_name || 'Colega';
        const type = signal.type;
        const payload = typeof signal.payload === 'string' ? JSON.parse(signal.payload) : signal.payload;

        // Trata sinais especiais (Reação, Levantar a Mão, Transmissão e Controle Remoto AnyDesk)
        if (type === 'reaction') {
            triggerFloatingReaction(`video-tile-peer-${senderId}`, payload.emoji || '👏');
            return;
        }

        if (type === 'raise_hand') {
            const handBadge = document.getElementById(`peer-hand-badge-${senderId}`);
            if (handBadge) {
                handBadge.style.display = payload.raised ? 'block' : 'none';
            }
            if (payload.raised) {
                triggerFloatingReaction(`video-tile-peer-${senderId}`, '✋');
            }
            return;
        }

        if (type === 'screen_started') {
            if (payload.sharing) {
                remotePeerSharingScreenId = senderId;
                focusedTileId = `video-tile-peer-${senderId}`;
            } else {
                if (remotePeerSharingScreenId === senderId) remotePeerSharingScreenId = null;
                if (focusedTileId === `video-tile-peer-${senderId}`) focusedTileId = null;
            }
            updateGridLayout();
            return;
        }

        // ── CONTROLE REMOTO (AnyDesk P2P) ───────────────────────────────────
        if (type === 'remote_request') {
            pendingRequesterId = senderId;
            pendingRequesterName = payload.requester_name || senderName;
            if (remoteRequesterName) remoteRequesterName.textContent = pendingRequesterName;
            if (remoteModal) remoteModal.style.display = 'flex';
            return;
        }

        if (type === 'remote_response') {
            if (payload.approved) {
                isControllingRemote = true;
                controlledUserId = senderId;
                if (btnRemoteControl) {
                    btnRemoteControl.classList.add('btn-control-active');
                    const lbl = btnRemoteControl.querySelector('.btn-call-label');
                    if (lbl) lbl.textContent = 'Ativo';
                }
                alert(`🎮 Controle Remoto Aceito por ${senderName}!\n\nAgora seus cliques, movimentos do mouse e teclas digitadas sobre a tela dele serão executados em tempo real.`);
                attachRemoteControlListeners(`video-tile-peer-${senderId}`);
            } else {
                alert(`❌ ${senderName} recusou a solicitação de controle remoto.`);
            }
            return;
        }

        if (type === 'remote_revoke') {
            isControllingRemote = false;
            controlledUserId = null;
            if (btnRemoteControl) {
                btnRemoteControl.classList.remove('btn-control-active');
                const lbl = btnRemoteControl.querySelector('.btn-call-label');
                if (lbl) lbl.textContent = 'Controle';
            }
            alert('🛑 O controle remoto foi encerrado pelo anfitrião.');
            return;
        }

        if (type === 'remote_input') {
            // Renderiza cursor virtual na tela local
            renderVirtualCursorOnLocalScreen(payload, senderName);
            return;
        }

        const peer = getOrCreatePeerConnection(senderId, senderName, false);

        try {
            if (type === 'offer') {
                await peer.pc.setRemoteDescription(new RTCSessionDescription(payload));
                
                while (peer.pendingCandidates.length > 0) {
                    const candidate = peer.pendingCandidates.shift();
                    await peer.pc.addIceCandidate(new RTCIceCandidate(candidate));
                }

                const answer = await peer.pc.createAnswer();
                await peer.pc.setLocalDescription(answer);
                sendSignal(senderId, 'answer', answer);
            } else if (type === 'answer') {
                if (peer.pc.signalingState !== 'stable') {
                    await peer.pc.setRemoteDescription(new RTCSessionDescription(payload));
                    
                    while (peer.pendingCandidates.length > 0) {
                        const candidate = peer.pendingCandidates.shift();
                        await peer.pc.addIceCandidate(new RTCIceCandidate(candidate));
                    }
                }
            } else if (type === 'candidate') {
                if (payload) {
                    if (peer.pc.remoteDescription && peer.pc.remoteDescription.type) {
                        await peer.pc.addIceCandidate(new RTCIceCandidate(payload));
                    } else {
                        peer.pendingCandidates.push(payload);
                    }
                }
            }
        } catch (err) {
            console.warn('Erro ao processar sinal WebRTC:', err);
        }
    }

    // ── Renderização dos Tiles com Controle de Volume e Prevenção de Tela Preta
    function renderRemoteVideoTile(remoteUserId, remoteUserName, stream) {
        let tileId = `video-tile-peer-${remoteUserId}`;
        let tile = document.getElementById(tileId);

        const currentVol = peerVolumes.has(remoteUserId) ? peerVolumes.get(remoteUserId) : 100;

        if (!tile) {
            tile = document.createElement('div');
            tile.id = tileId;
            tile.className = 'video-tile video-tile-remote animate-fade-in';
            tile.innerHTML = `
                <video autoplay playsinline></video>
                <div class="tile-avatar-placeholder" style="display:none;">
                    <div class="tile-avatar-circle">${escapeHtml(remoteUserName.substring(0, 2).toUpperCase())}</div>
                    <span class="tile-avatar-desc">Câmera Desligada</span>
                </div>
                <div class="tile-hand-badge" id="peer-hand-badge-${remoteUserId}" style="display:none;">✋ Mão Levantada</div>
                <div class="reactions-container" id="peer-reactions-${remoteUserId}"></div>
                
                <!-- Controle de Volume Individual -->
                <div class="peer-volume-wrapper" title="Ajustar volume de ${escapeHtml(remoteUserName)}">
                    <button type="button" class="btn-peer-mute" id="peer-mute-btn-${remoteUserId}" onclick="window.togglePeerMute(${remoteUserId})">🔊</button>
                    <input type="range" class="peer-volume-slider" id="peer-slider-${remoteUserId}" min="0" max="100" value="${currentVol}" oninput="window.setPeerVolume(${remoteUserId}, this.value)" />
                    <span class="peer-volume-val" id="peer-vol-text-${remoteUserId}">${currentVol}%</span>
                </div>

                <!-- Botão de Solicitar Controle Remoto do Colega -->
                <button type="button" class="btn-tile-remote" title="Solicitar controle remoto do computador de ${escapeHtml(remoteUserName)}" onclick="window.requestRemoteControlForPeer(${remoteUserId}, '${escapeHtml(remoteUserName)}')">🎮 Controlar</button>

                <div class="video-tile-info">
                    <span class="video-tile-name">${escapeHtml(remoteUserName)}</span>
                    <span class="video-tile-status">
                        <span class="peer-mic-icon">🎙️</span>
                    </span>
                </div>
                <button type="button" class="btn-tile-focus" title="Fixar na tela principal" onclick="window.toggleTileFocus('${tileId}')">⛶</button>
            `;
            videoGrid.appendChild(tile);
        }

        const videoEl = tile.querySelector('video');
        const avatarEl = tile.querySelector('.tile-avatar-placeholder');

        if (videoEl) {
            videoEl.playsInline = true;
            videoEl.autoplay = true;

            if (videoEl.srcObject !== stream) {
                videoEl.srcObject = stream;
            }
            videoEl.volume = currentVol / 100;

            // Garante inicialização e reprodução contínua da transmissão sem tela preta
            videoEl.onloadedmetadata = () => {
                videoEl.play().catch(() => {});
            };
            videoEl.oncanplay = () => {
                videoEl.play().catch(() => {});
            };
            videoEl.play().catch(() => {});
        }

        const hasVideoTrack = stream.getVideoTracks().some(t => t.enabled && t.readyState === 'live');
        if (hasVideoTrack) {
            if (videoEl) videoEl.style.display = 'block';
            if (avatarEl) avatarEl.style.display = 'none';
        } else {
            if (videoEl) videoEl.style.display = 'none';
            if (avatarEl) avatarEl.style.display = 'flex';
        }

        setupAudioVisualizer(stream, tileId);
        updateGridLayout();
    }

    function removeRemoteVideoTile(remoteUserId) {
        const tile = document.getElementById(`video-tile-peer-${remoteUserId}`);
        if (tile) {
            if (focusedTileId === tile.id) focusedTileId = null;
            tile.remove();
        }
        updateGridLayout();
    }

    // ── CONTROLE DE VOLUME INDIVIDUAL (Global Window Helpers) ────────────────
    window.setPeerVolume = function (remoteUserId, val) {
        const vol = parseInt(val, 10);
        peerVolumes.set(remoteUserId, vol);

        const tile = document.getElementById(`video-tile-peer-${remoteUserId}`);
        if (!tile) return;

        const videoEl = tile.querySelector('video');
        if (videoEl) {
            videoEl.volume = vol / 100;
            videoEl.muted = (vol === 0);
        }

        const textEl = document.getElementById(`peer-vol-text-${remoteUserId}`);
        if (textEl) textEl.textContent = `${vol}%`;

        const muteBtn = document.getElementById(`peer-mute-btn-${remoteUserId}`);
        if (muteBtn) {
            if (vol === 0) muteBtn.textContent = '🔇';
            else if (vol < 40) muteBtn.textContent = '🔈';
            else if (vol < 80) muteBtn.textContent = '🔉';
            else muteBtn.textContent = '🔊';
        }
    };

    window.togglePeerMute = function (remoteUserId) {
        const currentVol = peerVolumes.has(remoteUserId) ? peerVolumes.get(remoteUserId) : 100;
        const slider = document.getElementById(`peer-slider-${remoteUserId}`);

        if (currentVol > 0) {
            window.setPeerVolume(remoteUserId, 0);
            if (slider) slider.value = 0;
        } else {
            window.setPeerVolume(remoteUserId, 100);
            if (slider) slider.value = 100;
        }
    };

    // ── REAÇÕES FLUTUANTES & LEVANTAR A MÃO ──────────────────────────────────
    function triggerFloatingReaction(tileId, emoji) {
        const tile = document.getElementById(tileId);
        if (!tile) return;

        const reactionEl = document.createElement('div');
        reactionEl.className = 'floating-reaction-badge';
        reactionEl.textContent = emoji;

        tile.appendChild(reactionEl);
        setTimeout(() => reactionEl.remove(), 2500);
    }

    window.sendCallReaction = function (emoji) {
        if (!inCall) return;
        triggerFloatingReaction('local-video-tile', emoji);
        broadcastSignal('reaction', { emoji: emoji });
    };

    if (btnRaiseHand) {
        btnRaiseHand.addEventListener('click', () => {
            if (!inCall) return;
            isHandRaised = !isHandRaised;
            btnRaiseHand.classList.toggle('btn-control-active', isHandRaised);
            btnRaiseHand.innerHTML = isHandRaised ? '✋ Baixar a Mão' : '🙋‍♂️ Levantar a Mão';

            const localHand = document.getElementById('local-hand-badge');
            if (localHand) localHand.style.display = isHandRaised ? 'block' : 'none';

            broadcastSignal('raise_hand', { raised: isHandRaised });
        });
    }

    // ── Layout Dinâmico e Foco/Spotlight na Tela ─────────────────────────────
    function updateGridLayout() {
        const tiles = videoGrid.querySelectorAll('.video-tile');
        const count = tiles.length;

        if (focusedTileId && document.getElementById(focusedTileId)) {
            videoGrid.className = 'call-grid grid-spotlight';
            tiles.forEach(t => {
                t.classList.toggle('tile-focused', t.id === focusedTileId);
            });
            return;
        }

        tiles.forEach(t => t.classList.remove('tile-focused'));

        if (count <= 1) {
            videoGrid.className = 'call-grid grid-1';
        } else if (count === 2) {
            videoGrid.className = 'call-grid grid-2';
        } else if (count <= 4) {
            videoGrid.className = 'call-grid grid-4';
        } else {
            videoGrid.className = 'call-grid grid-many';
        }
    }

    window.toggleTileFocus = function (tileId) {
        if (focusedTileId === tileId) {
            focusedTileId = null;
        } else {
            focusedTileId = tileId;
        }
        updateGridLayout();
    };

    // ── Indicador de Voz / Fala ──────────────────────────────────────────────
    function setupAudioVisualizer(stream, tileId) {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext || !stream.getAudioTracks().length) return;

            const audioCtx = new AudioContext();
            const source = audioCtx.createMediaStreamSource(stream);
            const analyser = audioCtx.createAnalyser();
            analyser.fftSize = 256;
            source.connect(analyser);

            const bufferLength = analyser.frequencyBinCount;
            const dataArray = new Uint8Array(bufferLength);

            function checkVolume() {
                if (!inCall) {
                    audioCtx.close().catch(() => {});
                    return;
                }
                analyser.getByteFrequencyData(dataArray);
                let sum = 0;
                for (let i = 0; i < bufferLength; i++) {
                    sum += dataArray[i];
                }
                const avg = sum / bufferLength;
                const tile = document.getElementById(tileId);
                if (tile) {
                    if (avg > 25) {
                        tile.classList.add('speaking');
                    } else {
                        tile.classList.remove('speaking');
                    }
                }
                requestAnimationFrame(checkVolume);
            }
            checkVolume();
        } catch (e) {}
    }

    // ── Controles de Microfone, Câmera e Compartilhamento de Tela ────────────

    // Alternar Microfone
    if (btnToggleMic) {
        btnToggleMic.addEventListener('click', () => {
            if (!localStream) return;
            isMuted = !isMuted;
            localStream.getAudioTracks().forEach(t => {
                t.enabled = !isMuted;
            });
            updateMediaControlButtons();
        });
    }

    // Alternar Câmera
    if (btnToggleCam) {
        btnToggleCam.addEventListener('click', async () => {
            if (!inCall) return;

            const existingVideoTracks = localStream ? localStream.getVideoTracks() : [];

            if (existingVideoTracks.length > 0) {
                isVideoOff = !isVideoOff;
                existingVideoTracks.forEach(t => {
                    t.enabled = !isVideoOff;
                });
                renderLocalVideoTile();
                updateMediaControlButtons();
            } else {
                try {
                    const tempStream = await navigator.mediaDevices.getUserMedia({
                        video: { width: { ideal: 1280 }, height: { ideal: 720 } }
                    });
                    const newVideoTrack = tempStream.getVideoTracks()[0];
                    localStream.addTrack(newVideoTrack);
                    isVideoOff = false;

                    peerConnections.forEach(async ({ pc, userId, userName }) => {
                        const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (sender) {
                            await sender.replaceTrack(newVideoTrack);
                        } else {
                            pc.addTrack(newVideoTrack, localStream);
                            initiatePeerConnection(userId, userName);
                        }
                    });

                    renderLocalVideoTile();
                    updateMediaControlButtons();
                } catch (err) {
                    alert('Não foi possível ativar a câmera. Verifique se outro app está usando ou libere as permissões no navegador.');
                }
            }
        });
    }

    // Compartilhamento de Tela
    if (btnShareScreen) {
        btnShareScreen.addEventListener('click', async () => {
            if (!isScreenSharing) {
                if (!checkMediaDevicesSupport()) return;

                try {
                    try {
                        screenStream = await navigator.mediaDevices.getDisplayMedia({
                            video: { cursor: 'always', displaySurface: 'monitor' },
                            audio: true
                        });
                    } catch (eWithAudio) {
                        screenStream = await navigator.mediaDevices.getDisplayMedia({
                            video: { cursor: 'always' }
                        });
                    }

                    const screenVideoTrack = screenStream.getVideoTracks()[0];
                    if (screenVideoTrack && 'contentHint' in screenVideoTrack) {
                        screenVideoTrack.contentHint = 'detail';
                    }

                    peerConnections.forEach(async ({ pc, userId, userName }) => {
                        const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (sender) {
                            await sender.replaceTrack(screenVideoTrack);
                        } else {
                            pc.addTrack(screenVideoTrack, screenStream);
                        }
                        initiatePeerConnection(userId, userName);
                    });

                    broadcastSignal('screen_started', { sharing: true });

                    isScreenSharing = true;
                    focusedTileId = 'local-video-tile';
                    renderLocalVideoTile();
                    updateMediaControlButtons();

                    screenVideoTrack.onended = () => {
                        stopScreenSharing();
                    };
                } catch (err) {
                    console.warn('Compartilhamento de tela cancelado:', err);
                }
            } else {
                stopScreenSharing();
            }
        });
    }

    function stopScreenSharing() {
        if (screenStream) {
            screenStream.getTracks().forEach(t => t.stop());
            screenStream = null;
        }

        isScreenSharing = false;

        const localVideoTrack = (localStream && localStream.getVideoTracks().length > 0) ? localStream.getVideoTracks()[0] : null;
        peerConnections.forEach(async ({ pc, userId, userName }) => {
            const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
            if (sender && localVideoTrack && !isVideoOff) {
                await sender.replaceTrack(localVideoTrack);
            }
            initiatePeerConnection(userId, userName);
        });

        broadcastSignal('screen_started', { sharing: false });

        if (focusedTileId === 'local-video-tile') {
            focusedTileId = null;
        }

        renderLocalVideoTile();
        updateMediaControlButtons();
    }

    function updateMediaControlButtons() {
        if (btnToggleMic) {
            btnToggleMic.classList.toggle('btn-control-off', isMuted);
            const micIcon = document.getElementById('btn-mic-icon');
            if (micIcon) micIcon.textContent = isMuted ? '🔇' : '🎙️';
            const micLabel = btnToggleMic.querySelector('.btn-call-label');
            if (micLabel) micLabel.textContent = isMuted ? 'Mutado' : 'Mic';
        }

        if (btnToggleCam) {
            btnToggleCam.classList.toggle('btn-control-off', isVideoOff);
            const camIcon = document.getElementById('btn-cam-icon');
            if (camIcon) camIcon.textContent = isVideoOff ? '🚫' : '📹';
            const camLabel = btnToggleCam.querySelector('.btn-call-label');
            if (camLabel) camLabel.textContent = isVideoOff ? 'Desligada' : 'Câmera';
        }

        if (btnShareScreen) {
            btnShareScreen.classList.toggle('btn-control-active', isScreenSharing);
            const screenLabel = btnShareScreen.querySelector('.btn-call-label');
            if (screenLabel) screenLabel.textContent = isScreenSharing ? 'Parar' : 'Tela';
        }

        if (btnRaiseHand) {
            btnRaiseHand.classList.toggle('btn-control-active', isHandRaised);
            const handLabel = btnRaiseHand.querySelector('.btn-call-label');
            if (handLabel) handLabel.textContent = isHandRaised ? 'Baixar' : 'Pedir Fala';
        }
    }

    // ── POPUP DE REAÇÕES RÁPIDAS ─────────────────────────────────────────────
    if (btnToggleReactions && callReactionsMenu) {
        btnToggleReactions.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = callReactionsMenu.style.display === 'flex';
            callReactionsMenu.style.display = isOpen ? 'none' : 'flex';
        });

        document.addEventListener('click', () => {
            callReactionsMenu.style.display = 'none';
        });
    }

    const originalSendReaction = window.sendCallReaction;
    window.sendCallReaction = function (emoji) {
        if (callReactionsMenu) callReactionsMenu.style.display = 'none';
        if (typeof originalSendReaction === 'function') {
            originalSendReaction(emoji);
        } else {
            triggerFloatingReaction('local-video-tile', emoji);
            broadcastSignal('reaction', { emoji: emoji });
        }
    };

    // ── CONTROLE REMOTO (ANYDESK P2P) ────────────────────────────────────────

    // Solicitar Controle Remoto diretamente para um participante
    window.requestRemoteControlForPeer = function (targetUserId, targetName) {
        if (!inCall) {
            alert('Entre na chamada primeiro para usar o Controle Remoto.');
            return;
        }
        sendSignal(targetUserId, 'remote_request', { requester_name: currentUserName });
        alert(`🎮 Solicitação de controle remoto enviada para ${targetName || 'seu colega'}!\n\nAguardando ele autorizar no computador dele...`);
    };

    // Botão Principal de Controle Remoto na Dock
    if (btnRemoteControl) {
        btnRemoteControl.addEventListener('click', () => {
            if (!inCall) {
                alert('Entre na chamada primeiro para usar o Controle Remoto.');
                return;
            }

            // Se já estiver controlando, opção de parar
            if (isControllingRemote && controlledUserId) {
                if (confirm('Deseja encerrar o controle remoto do computador do seu colega?')) {
                    sendSignal(controlledUserId, 'remote_revoke', { revoked: true });
                    isControllingRemote = false;
                    controlledUserId = null;
                    btnRemoteControl.classList.remove('btn-control-active');
                    const lbl = btnRemoteControl.querySelector('.btn-call-label');
                    if (lbl) lbl.textContent = 'Controle';
                    removeRemoteControlOverlay();
                }
                return;
            }

            // Encontra qual colega está na chamada ou compartilhando tela
            let targetId = remotePeerSharingScreenId;
            let targetName = 'Colega';

            if (!targetId && peerConnections.size > 0) {
                const firstPeer = peerConnections.values().next().value;
                if (firstPeer) {
                    targetId = firstPeer.userId;
                    targetName = firstPeer.userName || 'Colega';
                }
            }

            if (!targetId) {
                alert('Nenhum colega conectado na chamada no momento para controlar.');
                return;
            }

            window.requestRemoteControlForPeer(targetId, targetName);
        });
    }

    // Resposta do Host ao Prompt AnyDesk
    window.respondRemoteControl = function (allow) {
        if (remoteModal) remoteModal.style.display = 'none';
        if (!pendingRequesterId) return;

        if (allow) {
            isBeingControlled = true;
            controllingUserId = pendingRequesterId;
            if (remoteActiveBar) {
                remoteActiveBar.style.display = 'flex';
                if (remoteControllerName) remoteControllerName.textContent = pendingRequesterName || 'Colega';
            }
            sendSignal(pendingRequesterId, 'remote_response', { approved: true });
        } else {
            sendSignal(pendingRequesterId, 'remote_response', { approved: false });
        }
        pendingRequesterId = null;
    };

    // Host encerra controle remoto
    window.revokeRemoteControl = function () {
        if (controllingUserId) {
            sendSignal(controllingUserId, 'remote_revoke', { revoked: true });
        }
        isBeingControlled = false;
        controllingUserId = null;
        if (remoteActiveBar) remoteActiveBar.style.display = 'none';
    };

    function removeRemoteControlOverlay() {
        document.querySelectorAll('.remote-control-interactive-layer').forEach(el => el.remove());
    }

    // Captura de eventos do Mouse e Teclado sobre a tela do colega com Camada Interativa
    function attachRemoteControlListeners(tileId) {
        removeRemoteControlOverlay();
        const tile = document.getElementById(tileId);
        if (!tile) return;

        // Cria camada interativa sobre o tile para capturar 100% dos eventos
        const layer = document.createElement('div');
        layer.className = 'remote-control-interactive-layer animate-fade-in';
        layer.tabIndex = 0;
        layer.innerHTML = `
            <div class="remote-layer-header">
                <span>🟢 MODO CONTROLE REMOTO ATIVO</span>
                <button type="button" class="btn-layer-close" onclick="window.stopControllingPeer()">✕ Encerrar</button>
            </div>
            <div class="remote-layer-hint">🖱️ Clique, arraste e digite para controlar o PC</div>
        `;
        tile.appendChild(layer);
        layer.focus();

        window.stopControllingPeer = function () {
            if (controlledUserId) {
                sendSignal(controlledUserId, 'remote_revoke', { revoked: true });
            }
            isControllingRemote = false;
            controlledUserId = null;
            if (btnRemoteControl) {
                btnRemoteControl.classList.remove('btn-control-active');
                const lbl = btnRemoteControl.querySelector('.btn-call-label');
                if (lbl) lbl.textContent = 'Controle';
            }
            removeRemoteControlOverlay();
        };

        function sendRemoteInput(eventType, payload) {
            if (!isControllingRemote || !controlledUserId) return;
            const currentChannelId = getActiveChannelId();
            
            // 1. Envia via sinalização rápida WebRTC
            sendSignal(controlledUserId, 'remote_input', {
                event_type: eventType,
                ...payload
            });

            // 2. Envia para o agente nativo via API REST local
            fetch('/api/call/remote-control/event', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    channel_id: currentChannelId,
                    target_user_id: controlledUserId,
                    event_type: eventType,
                    payload: payload
                })
            }).catch(() => {});
        }

        // Movimento do Mouse
        layer.addEventListener('mousemove', (e) => {
            if (!isControllingRemote) return;
            const now = Date.now();
            if (now - lastMouseMoveTime < 30) return;
            lastMouseMoveTime = now;

            const rect = layer.getBoundingClientRect();
            const xPercent = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
            const yPercent = Math.max(0, Math.min(100, ((e.clientY - rect.top) / rect.height) * 100));

            sendRemoteInput('mousemove', { xPercent: xPercent, yPercent: yPercent });
        });

        // Clique Pressionado (MouseDown)
        layer.addEventListener('mousedown', (e) => {
            if (!isControllingRemote) return;
            layer.focus();
            const rect = layer.getBoundingClientRect();
            const xPercent = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
            const yPercent = Math.max(0, Math.min(100, ((e.clientY - rect.top) / rect.height) * 100));

            // Efeito visual de clique no controlador
            spawnClickRipple(layer, e.clientX - rect.left, e.clientY - rect.top);

            sendRemoteInput('mousedown', {
                xPercent: xPercent,
                yPercent: yPercent,
                button: e.button
            });
        });

        // Clique Solto (MouseUp)
        layer.addEventListener('mouseup', (e) => {
            if (!isControllingRemote) return;
            sendRemoteInput('mouseup', { button: e.button });
        });

        // Previne menu de contexto do navegador no botão direito
        layer.addEventListener('contextmenu', (e) => {
            if (!isControllingRemote) return;
            e.preventDefault();
        });

        // Duplo Clique
        layer.addEventListener('dblclick', (e) => {
            if (!isControllingRemote) return;
            sendRemoteInput('dblclick', {});
        });

        // Rolagem do Mouse (Scroll Wheel)
        layer.addEventListener('wheel', (e) => {
            if (!isControllingRemote) return;
            e.preventDefault();
            sendRemoteInput('wheel', { deltaY: e.deltaY });
        }, { passive: false });

        // Digitação no Teclado
        layer.addEventListener('keydown', (e) => {
            if (!isControllingRemote) return;
            if (e.key === 'Escape') {
                window.stopControllingPeer();
                return;
            }
            if (['Tab', 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Backspace'].includes(e.key)) {
                e.preventDefault();
            }
            sendRemoteInput('keydown', { key: e.key, code: e.code });
        });
    }

    function spawnClickRipple(parent, x, y) {
        const ripple = document.createElement('div');
        ripple.className = 'remote-click-ripple';
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;
        parent.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
    }

    // Renderização do Cursor Virtual no computador do Host
    function renderVirtualCursorOnLocalScreen(payload, controllerName) {
        let cursor = document.getElementById('remote-virtual-cursor');
        const targetContainer = document.getElementById('local-video-tile') || callContainer;

        if (!cursor && targetContainer) {
            cursor = document.createElement('div');
            cursor.id = 'remote-virtual-cursor';
            cursor.className = 'remote-virtual-cursor';
            cursor.innerHTML = `
                <div class="remote-cursor-point"></div>
                <div class="remote-cursor-tag">🖱️ ${escapeHtml(controllerName || 'Colega')}</div>
            `;
            targetContainer.appendChild(cursor);
        }

        if (cursor && payload.xPercent !== undefined && payload.yPercent !== undefined) {
            cursor.style.left = `${payload.xPercent}%`;
            cursor.style.top = `${payload.yPercent}%`;
            cursor.style.display = 'block';

            if (payload.event_type === 'mousedown' || payload.event_type === 'click') {
                const rect = targetContainer.getBoundingClientRect();
                const clickX = (payload.xPercent / 100) * rect.width;
                const clickY = (payload.yPercent / 100) * rect.height;
                spawnClickRipple(targetContainer, clickX, clickY);
            }
        }
    }

    // ── MODO TELA CHEIA UNIVERSAL CORRIGIDO ──────────────────────────────────
    function isFullscreenActive() {
        return !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
    }

    function toggleFullscreenSafe() {
        const doc = document;
        const el = callContainer;

        if (!isFullscreenActive()) {
            const requestFs = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen;
            if (requestFs) {
                requestFs.call(el).then(() => {
                    callContainer.classList.add('call-fullscreen-mode');
                    if (btnFullscreenCall) btnFullscreenCall.innerHTML = '⤓ Sair Tela Cheia';
                }).catch(() => {});
            }
        } else {
            exitFullscreenSafe();
        }
    }

    function exitFullscreenSafe() {
        const doc = document;
        if (isFullscreenActive()) {
            const exitFs = doc.exitFullscreen || doc.webkitExitFullscreen || doc.mozCancelFullScreen || doc.msExitFullscreen;
            if (exitFs) {
                exitFs.call(doc).catch(() => {});
            }
        }
        callContainer.classList.remove('call-fullscreen-mode');
        if (btnFullscreenCall) btnFullscreenCall.innerHTML = '⤢ Tela Cheia';
    }

    if (btnFullscreenCall) {
        btnFullscreenCall.addEventListener('click', toggleFullscreenSafe);
    }

    // Listener global de alteração de tela cheia (ex: tecla ESC)
    ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'].forEach(ev => {
        document.addEventListener(ev, () => {
            if (!isFullscreenActive()) {
                callContainer.classList.remove('call-fullscreen-mode');
                if (btnFullscreenCall) btnFullscreenCall.innerHTML = '⤢ Tela Cheia';
            } else {
                callContainer.classList.add('call-fullscreen-mode');
                if (btnFullscreenCall) btnFullscreenCall.innerHTML = '⤓ Sair Tela Cheia';
            }
        });
    });

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text || '').replace(/[&<>"']/g, m => map[m]);
    }

    // Handlers de Eventos
    btnJoinCall.addEventListener('click', joinCall);
    btnLeaveCall.addEventListener('click', leaveCall);

    window.addEventListener('beforeunload', () => {
        if (inCall) leaveCall();
    });
})();



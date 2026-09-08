/**
 * VÉRTICE — Sistema Global de Notificações com Som e Controle de Silêncio
 * Exibe alertas visuais na tela (Toast) e reproduz som ao receber mensagens
 */

(function () {
    let seenPrivateMsgIds = new Set();
    let seenChannelMsgIds = new Set();
    let pollInterval = null;
    let isSoundMuted = (localStorage.getItem('vertice_sound_muted') === '1');

    function initNotificationEngine() {
        const userId = parseInt(document.body.getAttribute('data-user-id') || '0', 10);
        if (userId <= 0) return; // Não logado

        createToastContainer();
        initMuteButton();

        // Checagem a cada 3.5 segundos
        pollInterval = setInterval(checkForNewNotifications, 3500);
        checkForNewNotifications();
    }

    function createToastContainer() {
        if (document.getElementById('vertice-toast-container')) return;
        const container = document.createElement('div');
        container.id = 'vertice-toast-container';
        container.className = 'toast-container-corner';
        document.body.appendChild(container);
    }

    function initMuteButton() {
        const btnMute = document.getElementById('btn-toggle-sound');
        if (btnMute) {
            updateMuteBtnUI(btnMute);
            btnMute.addEventListener('click', toggleMute);
        }
    }

    function toggleMute() {
        isSoundMuted = !isSoundMuted;
        localStorage.setItem('vertice_sound_muted', isSoundMuted ? '1' : '0');
        const btnMute = document.getElementById('btn-toggle-sound');
        if (btnMute) updateMuteBtnUI(btnMute);

        showToast({
            title: isSoundMuted ? '🔕 Notificações Silenciadas' : '🔔 Notificações Ativadas',
            body: isSoundMuted ? 'Você não ouvirá sons de novas mensagens.' : 'Sons de novas mensagens reativados.',
            icon: isSoundMuted ? '🔕' : '🔔',
            type: 'info',
            duration: 3000
        });
    }

    function updateMuteBtnUI(btn) {
        if (!btn) return;
        if (isSoundMuted) {
            btn.innerHTML = '<span>🔕</span> <small>Silenciado</small>';
            btn.classList.add('muted');
            btn.title = 'Sons silenciados. Clique para ativar som.';
        } else {
            btn.innerHTML = '<span>🔔</span> <small>Sons ON</small>';
            btn.classList.remove('muted');
            btn.title = 'Sons ativos. Clique para silenciar.';
        }
    }

    async function checkForNewNotifications() {
        try {
            const res = await fetch('/api/notifications/check');
            const data = await res.json();

            if (data.status === 'success') {
                // 1. Mensagens Privadas
                if (data.unread_private && data.unread_private.length > 0) {
                    data.unread_private.forEach(msg => {
                        const mId = parseInt(msg.id, 10);
                        if (!seenPrivateMsgIds.has(mId)) {
                            seenPrivateMsgIds.add(mId);

                            // Não notifica se o usuário já estiver conversando na tela /privado com essa pessoa
                            const activeAmigo = new URLSearchParams(window.location.search).get('amigo');
                            if (window.location.pathname.startsWith('/privado') && activeAmigo == msg.sender_id) {
                                return;
                            }

                            playMessageChime();
                            showToast({
                                title: `💬 ${escapeHtml(msg.sender_name)}`,
                                body: escapeHtml(msg.content ? (msg.content.substring(0, 70) + (msg.content.length > 70 ? '...' : '')) : 'Enviou um anexo/áudio.'),
                                icon: '💬',
                                actionText: 'Responder',
                                actionUrl: `/privado?amigo=${msg.sender_id}`
                            });
                        }
                    });
                }

                // 2. Mensagens em Canais de Matérias
                if (data.recent_channel && data.recent_channel.length > 0) {
                    data.recent_channel.forEach(msg => {
                        const mId = parseInt(msg.id, 10);
                        if (!seenChannelMsgIds.has(mId)) {
                            seenChannelMsgIds.add(mId);

                            // Não notifica se o usuário já estiver olhando este canal específico agora
                            const currentCanal = new URLSearchParams(window.location.search).get('canal');
                            if (window.location.pathname.startsWith('/comunidade') && currentCanal == msg.channel_id) {
                                return;
                            }

                            playMessageChime();
                            showToast({
                                title: `📚 [${escapeHtml(msg.category_name)}] #${escapeHtml(msg.channel_name)}`,
                                body: `<strong>${escapeHtml(msg.sender_name)}:</strong> ${escapeHtml(msg.content.substring(0, 60))}`,
                                icon: '📢',
                                actionText: 'Ver Mensagem',
                                actionUrl: `/comunidade/${msg.category_slug}?canal=${msg.channel_id}`
                            });
                        }
                    });
                }

                // 3. Solicitações de Amizade Recebidas
                if (data.pending_friends && data.pending_friends.length > 0) {
                    data.pending_friends.forEach(req => {
                        const reqId = 'req_' + req.id;
                        if (!seenPrivateMsgIds.has(reqId)) {
                            seenPrivateMsgIds.add(reqId);
                            playMessageChime();
                            showToast({
                                title: `👥 Novo Pedido de Amizade!`,
                                body: `<strong>${escapeHtml(req.name)}</strong> quer se conectar com você no Vértice.`,
                                icon: '🤝',
                                actionText: 'Ver Pedidos',
                                actionUrl: `/privado`
                            });
                        }
                    });
                }
            }
        } catch (e) {}
    }

    // ── SOM SINTÉTICO AGRADÁVEL (WEB AUDIO API) ──────────────────────────────
    function playMessageChime() {
        if (isSoundMuted) return;

        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();

            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            // Duas notas rápidas harmoniosas (G5 -> C6)
            osc.frequency.setValueAtTime(783.99, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(1046.50, ctx.currentTime + 0.1);

            gain.gain.setValueAtTime(0.08, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start();
            osc.stop(ctx.currentTime + 0.36);
        } catch (err) {}
    }

    // ── TOAST NOTIFICATION CARD ──────────────────────────────────────────────
    function showToast(opt) {
        const container = document.getElementById('vertice-toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `vertice-toast animate-slide-in ${opt.type || ''}`;

        toast.innerHTML = `
            <div class="toast-icon-side">${opt.icon || '💬'}</div>
            <div class="toast-body-side">
                <div class="toast-title">${opt.title}</div>
                <div class="toast-text">${opt.body}</div>
                ${opt.actionUrl ? `<a href="${opt.actionUrl}" class="toast-action-btn">${opt.actionText || 'Abrir'} →</a>` : ''}
            </div>
            <button type="button" class="toast-close-btn" title="Fechar">✕</button>
        `;

        toast.querySelector('.toast-close-btn').onclick = () => removeToast(toast);

        container.appendChild(toast);

        const duration = opt.duration || 6000;
        setTimeout(() => removeToast(toast), duration);
    }

    function removeToast(el) {
        if (!el || !el.parentNode) return;
        el.classList.add('animate-slide-out');
        setTimeout(() => { if (el.parentNode) el.remove(); }, 300);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
    }

    window.toggleNotificationSound = toggleMute;
    window.playNotificationChime = playMessageChime;
    window.showToastNotification = showToast;

    document.addEventListener('DOMContentLoaded', initNotificationEngine);
})();

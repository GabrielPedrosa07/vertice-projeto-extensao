/**
 * VÉRTICE — Sinalização Global de Chamadas Privadas e Ringtone
 * Executa em todas as páginas para notificar o aluno de chamadas e convites
 */

(function () {
    let checkInterval = null;
    let audioCtx = null;
    let ringtoneOscillator = null;
    let ringtoneGain = null;
    let isRinging = false;
    let currentCallId = null;

    function initGlobalCallListener() {
        const userId = parseInt(document.body.getAttribute('data-user-id') || '0', 10);
        if (userId <= 0) return; // Não logado

        createCallModalDOM();

        // Checagem a cada 3 segundos
        checkInterval = setInterval(checkForIncomingCall, 3000);
    }

    async function checkForIncomingCall() {
        try {
            const res = await fetch('/api/private/call/check');
            const data = await res.json();

            if (data.status === 'success' && data.incoming_call) {
                const call = data.incoming_call;
                if (!isRinging || currentCallId !== call.id) {
                    showIncomingCallModal(call);
                }
            } else {
                if (isRinging) {
                    dismissIncomingCallModal();
                }
            }
        } catch (e) {}
    }

    function createCallModalDOM() {
        if (document.getElementById('incoming-call-modal')) return;

        const modal = document.createElement('div');
        modal.id = 'incoming-call-modal';
        modal.className = 'incoming-call-overlay';
        modal.style.display = 'none';

        modal.innerHTML = `
            <div class="incoming-call-card animate-zoom-in">
                <div class="call-pulse-circle">
                    <span class="call-pulse-ring"></span>
                    <span class="call-icon-center">📞</span>
                </div>
                <h3 class="call-caller-name" id="inc-caller-name">Colega</h3>
                <p class="call-caller-sub">Chamada de vídeo privada recebida...</p>
                <div class="call-actions-row">
                    <button type="button" class="btn-inc-decline" id="btn-inc-decline">
                        <span>✕</span> Recusar
                    </button>
                    <button type="button" class="btn-inc-accept" id="btn-inc-accept">
                        <span>✓</span> Atender
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
    }

    function showIncomingCallModal(call) {
        currentCallId = call.id;
        isRinging = true;

        const modal = document.getElementById('incoming-call-modal');
        const nameEl = document.getElementById('inc-caller-name');
        if (nameEl) nameEl.textContent = call.caller_name;
        if (modal) modal.style.display = 'flex';

        playRingtone();

        const btnAccept = document.getElementById('btn-inc-accept');
        const btnDecline = document.getElementById('btn-inc-decline');

        btnAccept.onclick = async () => {
            stopRingtone();
            await fetch('/api/private/call/respond', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ call_id: call.id, action: 'accept' })
            });
            modal.style.display = 'none';
            // Redireciona para o chat privado com o amigo
            window.location.href = `/privado?amigo=${call.caller_id}&room=${encodeURIComponent(call.room_id)}`;
        };

        btnDecline.onclick = async () => {
            stopRingtone();
            await fetch('/api/private/call/respond', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ call_id: call.id, action: 'decline' })
            });
            modal.style.display = 'none';
            isRinging = false;
        };
    }

    function dismissIncomingCallModal() {
        stopRingtone();
        const modal = document.getElementById('incoming-call-modal');
        if (modal) modal.style.display = 'none';
        isRinging = false;
    }

    // ── RINGTONE SINTÉTICO (WEB AUDIO API) ───────────────────────────────────

    function playRingtone() {
        if (localStorage.getItem('vertice_sound_muted') === '1') return;

        try {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }

            ringtoneGain = audioCtx.createGain();
            ringtoneGain.gain.setValueAtTime(0.2, audioCtx.currentTime);
            ringtoneGain.connect(audioCtx.destination);

            ringtoneOscillator = audioCtx.createOscillator();
            ringtoneOscillator.type = 'sine';
            ringtoneOscillator.frequency.setValueAtTime(440, audioCtx.currentTime);
            ringtoneOscillator.connect(ringtoneGain);
            ringtoneOscillator.start();

            // Padrão de toque intermitente de chamada (1.2s som, 1.2s pausa)
            let isMutedStep = false;
            const cycleInterval = setInterval(() => {
                if (!isRinging || !ringtoneGain || !audioCtx) {
                    clearInterval(cycleInterval);
                    return;
                }
                isMutedStep = !isMutedStep;
                try {
                    ringtoneGain.gain.setValueAtTime(isMutedStep ? 0 : 0.2, audioCtx.currentTime);
                    if (!isMutedStep) {
                        ringtoneOscillator.frequency.setValueAtTime(480, audioCtx.currentTime);
                    } else {
                        ringtoneOscillator.frequency.setValueAtTime(440, audioCtx.currentTime);
                    }
                } catch (err) {}
            }, 1200);
        } catch (e) {}
    }

    function stopRingtone() {
        try {
            if (ringtoneOscillator) {
                ringtoneOscillator.stop();
                ringtoneOscillator.disconnect();
                ringtoneOscillator = null;
            }
            if (audioCtx) {
                audioCtx.close();
                audioCtx = null;
            }
        } catch (e) {}
    }

    document.addEventListener('DOMContentLoaded', initGlobalCallListener);
})();

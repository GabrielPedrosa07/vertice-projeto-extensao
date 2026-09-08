/**
 * VÉRTICE — Gamificação, Karma Acadêmico e Leaderboard
 * Sincronização de pontos, títulos e ranking universitário
 */

(function () {
    async function loadMyKarmaStats() {
        const pointsBadge = document.getElementById('my-karma-points');
        const titleBadge = document.getElementById('my-karma-title');
        const solutionsBadge = document.getElementById('my-karma-solutions');

        try {
            const res = await fetch('/api/gamification/my-stats');
            const data = await res.json();

            if (data.status === 'success' && data.stats) {
                if (pointsBadge) pointsBadge.textContent = `${data.stats.points} pts`;
                if (titleBadge) titleBadge.textContent = data.stats.badge_title;
                if (solutionsBadge) solutionsBadge.textContent = `${data.stats.solutions_count} soluções`;
            }
        } catch (e) {}
    }

    window.openLeaderboardModal = async function () {
        const modal = document.getElementById('leaderboard-modal');
        const body = document.getElementById('leaderboard-list');
        if (!modal || !body) return;

        modal.style.display = 'flex';
        body.innerHTML = '<div class="forum-loading">Carregando ranking acadêmico...</div>';

        try {
            const res = await fetch('/api/gamification/leaderboard');
            const data = await res.json();

            if (data.status === 'success') {
                const list = data.leaderboard || [];
                const medals = ['🥇', '🥈', '🥉'];

                body.innerHTML = `
                    <div class="leaderboard-table-wrap">
                        <table class="leaderboard-table">
                            <thead>
                                <tr>
                                    <th>Posição</th>
                                    <th>Aluno / Monitor</th>
                                    <th>Título & Insígnia</th>
                                    <th>Soluções</th>
                                    <th>Karma Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${list.map((u, i) => `
                                    <tr class="${i < 3 ? 'top-podium-row' : ''}">
                                        <td class="td-pos">${medals[i] || (i + 1) + 'º'}</td>
                                        <td class="td-user">
                                            <strong>${escapeHtml(u.name)}</strong>
                                            ${u.role === 'admin' ? '<span class="badge-role badge-admin">Admin</span>' : ''}
                                        </td>
                                        <td><span class="badge-karma-chip">${escapeHtml(u.badge_title)}</span></td>
                                        <td>${u.solutions_count || 0}</td>
                                        <td class="td-karma"><strong>${u.points || 0} pts</strong></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }
        } catch (err) {
            body.innerHTML = '<div class="forum-error">Erro ao carregar o ranking.</div>';
        }
    };

    window.closeLeaderboardModal = function () {
        const modal = document.getElementById('leaderboard-modal');
        if (modal) modal.style.display = 'none';
    };

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    window.refreshMyKarmaStats = loadMyKarmaStats;

    document.addEventListener('DOMContentLoaded', () => {
        loadMyKarmaStats();
    });
})();

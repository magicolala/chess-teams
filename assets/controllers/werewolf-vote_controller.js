import { Controller } from '@hotwired/stimulus';

// Connects to data-controller="werewolf-vote"
export default class extends Controller {
  static targets = [
    'container',
    'results',
    'status',
    'flash',
    'summary',
  ];

  static values = {
    gameId: String,
    apiBase: { type: String, default: '' },
    pollMs: { type: Number, default: 2000 },
  };

  connect() {
    this._poll = null;
    this.startPolling();
  }

  async close() {
    try {
      this.statusTarget.textContent = 'Clôture du vote…';
      const res = await fetch(`${this.apiBaseValue}/games/${this.gameIdValue}/votes/close`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data?.error || 'close_failed');
      this.statusTarget.textContent = 'Vote clôturé';
      this.showFlash('success', '✅ Le vote a été clôturé.');
      await this.refresh();
    } catch (e) {
      this.statusTarget.textContent = `Erreur: ${e.message}`;
      this.showFlash('error', `❌ Impossible de clôturer le vote (${e.message}).`);
    }
  }

  disconnect() {
    this.stopPolling();
  }

  async vote(event) {
    const suspectId = event.currentTarget?.dataset?.suspectId;
    if (!suspectId) return;

    try {
      this.statusTarget.textContent = 'Envoi du vote…';
      const res = await fetch(`${this.apiBaseValue}/games/${this.gameIdValue}/votes`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ suspectUserId: suspectId }),
        credentials: 'include',
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data?.error || 'vote_failed');
      this.statusTarget.textContent = 'Vote enregistré';
      await this.refresh();
    } catch (e) {
      this.statusTarget.textContent = `Erreur: ${e.message}`;
    }
  }

  startPolling() {
    if (this._poll) return;
    this.refresh();
    this._poll = setInterval(() => this.refresh(), this.pollMsValue);
  }

  stopPolling() {
    if (this._poll) {
      clearInterval(this._poll);
      this._poll = null;
    }
  }

  async refresh() {
    try {
      const res = await fetch(`${this.apiBaseValue}/games/${this.gameIdValue}/votes`, {
        credentials: 'include',
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data?.error || 'fetch_failed');

      // update results
      this.renderResults(data.results || {});

      if (!data.voteOpen) {
        this.statusTarget.textContent = 'Vote clôturé';
        this.stopPolling();
        this.renderSummary(data.results || {});
      }
    } catch (e) {
      // Silencieux pour éviter le spam d'erreurs transitoires
    }
  }

  renderResults(map) {
    const entries = Object.entries(map);
    if (entries.length === 0) {
      this.resultsTarget.innerHTML = '<div class="neo-text-sm neo-text-muted">Aucun vote pour le moment.</div>';
      return;
    }
    const list = entries
      .map(([userId, count]) => {
        const el = this.element.querySelector(`[data-suspect-id="${userId}"]`);
        const name = el?.dataset?.suspectName || userId;
        return `<div class="neo-flex neo-justify-between neo-items-center neo-py-1"><span>${name}</span><span class="neo-badge">${count}</span></div>`;
      })
      .join('');
    this.resultsTarget.innerHTML = list;
  }

  renderSummary(map) {
    if (!this.hasSummaryTarget) return;
    const entries = Object.entries(map);
    const total = entries.reduce((acc, [, c]) => acc + (Number(c) || 0), 0);
    if (total === 0) {
      this.summaryTarget.innerHTML = '<div class="neo-text-sm neo-text-secondary neo-mt-sm">Aucun vote exprimé.</div>';
      return;
    }
    let top = 0;
    let leaders = [];
    for (const [sid, cnt] of entries) {
      const n = Number(cnt) || 0;
      if (n > top) { top = n; leaders = [sid]; }
      else if (n === top) { leaders.push(sid); }
    }
    const names = leaders.map((sid) => {
      const el = this.element.querySelector(`[data-suspect-id="${sid}"]`);
      return el?.dataset?.suspectName || sid;
    });
    const hasMajority = leaders.length === 1 && top > total / 2;
    const text = hasMajority
      ? `Majorité: ${names[0]} (${top}/${total})`
      : `Pas de majorité (meilleur score: ${names.join(', ')} à ${top}/${total})`;
    this.summaryTarget.innerHTML = `<div class="neo-alert neo-alert-secondary neo-mt-sm">${text}</div>`;
  }

  showFlash(type, message) {
    if (!this.hasFlashTarget) return;
    const cls = type === 'success' ? 'neo-alert neo-alert-success' : 'neo-alert neo-alert-error';
    this.flashTarget.innerHTML = `<div class="${cls}"><span>${message}</span></div>`;
    clearTimeout(this._flashTimer);
    this._flashTimer = setTimeout(() => {
      if (this.hasFlashTarget) this.flashTarget.innerHTML = '';
    }, 4000);
  }
}

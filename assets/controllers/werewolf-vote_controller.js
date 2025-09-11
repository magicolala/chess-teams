import { Controller } from '@hotwired/stimulus';

// Connects to data-controller="werewolf-vote"
export default class extends Controller {
  static targets = [
    'container',
    'results',
    'status',
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
      await this.refresh();
    } catch (e) {
      this.statusTarget.textContent = `Erreur: ${e.message}`;
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
}

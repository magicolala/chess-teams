import { Controller } from '@hotwired/stimulus';

// Connects to data-controller="pseudo-generator"
export default class extends Controller {
  async generate() {
    try {
      const response = await fetch('/random-pseudo', {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const data = await response.json();
      const value = data?.pseudo ?? '';

      // Prefer scoped search inside this controller element
      const input = this.element.querySelector('#username') || document.querySelector('#username');
      if (input) {
        input.value = value;
        // Trigger input event so Symfony/Turbo/Form can react if needed
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.focus();
        if (typeof input.select === 'function') {
          input.select();
        }
      }
    } catch (e) {
      console.error('Failed to generate random pseudo', e);
    }
  }
}

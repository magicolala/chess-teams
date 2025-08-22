// assets/controllers/hello_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static values = { name: String }

  connect() {
    // Affiche un message en console + change le texte de la cible
    console.log(`[Stimulus] Hello, ${this.nameValue || 'world'}! Controller OK ✅`);
    if (this.element) {
      this.element.textContent = `Stimulus dit bonjour à ${this.nameValue || 'toi'} 👋`;
    }
  }
}

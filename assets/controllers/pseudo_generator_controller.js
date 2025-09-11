import { Controller } from '@hotwired/stimulus';

// Connects to data-controller="pseudo-generator"
export default class extends Controller {
  async generate() {
    try {
      const response = await fetch('/random-pseudo', {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });

      if (response.ok) {
        const data = await response.json();
        if (data?.pseudo) {
          this.setPseudo(data.pseudo);
          return;
        }
      }
      // Fallback to client-side generation if the endpoint fails
      this.generateFallbackPseudo();
    } catch (e) {
      console.error('Failed to generate random pseudo from server, using fallback', e);
      this.generateFallbackPseudo();
    }
  }

  generateFallbackPseudo() {
    const adjectives = [
      'Vif', 'Rapide', 'Fou', 'Rusé', 'Malin', 'Fort', 'Petit', 'Grand', 'Rouge', 'Noir', 
      'Blanc', 'Bleu', 'Vert', 'Jaune', 'Orange', 'Rose', 'Violet', 'Gris', 'Marron', 'Beige',
      'Vif', 'Lent', 'Fou', 'Sage', 'Rigolo', 'Sérieux', 'Haut', 'Bas', 'Joyeux', 'Triste',
      'Grand', 'Jeune', 'Vieux', 'Nouveau', 'Beau', 'Moche', 'Bon', 'Mauvais', 'Chaud', 'Froid'
    ];
    
    const nouns = [
      'Pion', 'Cavalier', 'Fou', 'Tour', 'Dame', 'Roi', 'Échec', 'Mat', 'Jeu', 'Échiquier',
      'Case', 'Pièce', 'Joueur', 'Champion', 'Maître', 'Expert', 'Débutant', 'Professionnel', 
      'Amateur', 'Tactique', 'Stratège', 'Génie', 'Prodigé', 'Talent', 'Pro', 'Novice',
      'Talentueux', 'Virtuose', 'As', 'Phénomène', 'Étoile', 'Légende', 'Maestro', 'Génie', 'Pro'
    ];
    
    const separators = ['', '_', '-', '.', ''];
    const randomAdjective = adjectives[Math.floor(Math.random() * adjectives.length)];
    const randomNoun = nouns[Math.floor(Math.random() * nouns.length)];
    const randomSeparator = separators[Math.floor(Math.random() * separators.length)];
    const randomNumber = Math.random() > 0.3 ? Math.floor(Math.random() * 1000) : '';
    
    // Randomly choose between different username formats
    const format = Math.floor(Math.random() * 4);
    let username;
    
    switch(format) {
      case 0: // Adjective + Noun + Number
        username = `${randomAdjective}${randomSeparator}${randomNoun}${randomNumber}`;
        break;
      case 1: // Noun + Adjective + Number
        username = `${randomNoun}${randomSeparator}${randomAdjective}${randomNumber}`;
        break;
      case 2: // Adjective + Noun
        username = `${randomAdjective}${randomSeparator}${randomNoun}`;
        break;
      case 3: // Noun + Number
        username = `${randomNoun}${randomNumber}`;
        break;
      default:
        username = `Joueur${Math.floor(Math.random() * 10000)}`;
    }
    
    // Ensure the username isn't too long (max 20 characters)
    if (username.length > 20) {
      username = username.substring(0, 20);
    }
    
    this.setPseudo(username);
  }

  setPseudo(pseudo) {
    const input = this.element.querySelector('#username') || document.querySelector('#username');
    if (input) {
      input.value = pseudo;
      // Trigger input event so Symfony/Turbo/Form can react if needed
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.focus();
      if (typeof input.select === 'function') {
        input.select();
      }
    }
  }
}

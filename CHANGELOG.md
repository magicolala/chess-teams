# 📝 Changelog - Chess-Teams

Tous les changements notables du projet Chess-Teams seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
et ce projet adhère au [Versioning Sémantique](https://semver.org/spec/v2.0.0.html).

## [Non publié]

### À venir
- Support des variantes d'échecs (Chess960, King of the Hill)
- Système de tournois par équipes
- Chat vocal intégré
- Application mobile (React Native)

---

## [2.0.0] - 2025-01-15

### 🎉 Version Majeure - Neo Chess Board

Cette version marque une refonte complète du système d'échiquier avec l'implémentation d'un système de rendu géométrique moderne.

#### ✨ Ajouté
- **Nouveau système de pièces géométriques** : Remplacement des symboles Unicode par des pièces dessinées avec Canvas
- **FlatSprites Engine** : Système de sprites géométriques identique au Neo Chess Board Ts Library
- **Thème Midnight** : Nouveau thème sombre avec couleurs personnalisées (blanc crème #f8f9fa, noir profond #1a1a1a)
- **Système de sprites optimisé** : Feuille de sprites 768x256px pour un rendu performant
- **Ombres intégrées** : Chaque pièce dispose d'une ombre elliptique pour plus de profondeur
- **Drag & Drop amélioré** : Gestion fluide du glisser-déposer avec mise à l'échelle des pièces (1.05x pendant le drag)
- **Documentation complète** : README.md, INSTALLATION.md, API_DOCUMENTATION.md, CONTRIBUTING.md

#### 🔄 Modifié
- **Architecture du rendu** : Migration de drawText() vers drawImage() pour les pièces
- **Système de thèmes** : Couleurs de pièces maintenant configurables via les thèmes
- **Performance améliorée** : Rendu Canvas optimisé avec feuille de sprites
- **Interface utilisateur** : Design plus moderne et cohérent

#### ⚖️ Supprimé
- **API Platform** : Suppression complète des dépendances API Platform
- **Symboles Unicode** : Remplacement par le système géométrique
- **Anciens fichiers de configuration** : Nettoyage des configurations obsolètes

#### 🐛 Corrigé
- **Compilation d'assets** : Résolution des erreurs de compilation AssetMapper
- **Centrage des pièces** : Alignement parfait au centre des cases
- **Gestion des événements** : Amélioration de la détection des clics et du drag & drop
- **Compatibilité navigateurs** : Meilleure prise en charge des différents navigateurs

#### 🔧 Technique
- Migration vers Symfony AssetMapper (suppression de Webpack Encore)
- Optimisation du cache avec Redis
- Amélioration de la structure des contrôleurs Stimulus
- Refactoring complet du système de rendu d'échiquier

---

## [1.2.1] - 2024-12-15

### 🐛 Correctifs
- **Timer** : Correction du timer qui ne se mettait pas à jour en temps réel
- **Validation des coups** : Amélioration de la détection des coups illégaux
- **Interface mobile** : Corrections mineures pour les écrans tactiles

### 🔧 Améliorations
- **Performance** : Optimisation des requêtes de base de données
- **Logs** : Amélioration du système de logging pour le debug

---

## [1.2.0] - 2024-11-20

### ✨ Nouvelles Fonctionnalités
- **Historique des coups** : Affichage en temps réel de la liste des coups
- **Auto-scroll** : Scroll automatique vers le dernier coup joué
- **Indicateurs visuels** : Timer devient rouge dans les 30 dernières secondes
- **Debug box** : Affichage des informations de débogage pour les développeurs

### 🔄 Améliorations
- **UX** : Interface utilisateur plus responsive
- **Animations** : Transitions plus fluides pour les mouvements de pièces
- **Feedback utilisateur** : Meilleurs messages d'erreur et de statut

---

## [1.1.0] - 2024-10-15

### ✨ Nouvelles Fonctionnalités
- **Système d'équipes** : Possibilité de jouer en équipe collaborative
- **Gestion des utilisateurs** : Authentification et profils utilisateur
- **API REST** : Endpoints pour l'intégration avec d'autres applications
- **Responsive Design** : Interface adaptative pour tous les écrans

### 🐛 Correctifs
- **Sécurité** : Correction de vulnérabilités mineures
- **Validation FEN** : Amélioration de la validation des positions d'échecs

---

## [1.0.0] - 2024-09-01

### 🎉 Release Initiale

#### ✨ Fonctionnalités de Base
- **Échiquier interactif** : Interface de jeu d'échecs avec Chessground
- **Moteur d'échecs** : Validation des règles avec Chess.js
- **Timer de partie** : Système de minuterie pour chaque tour
- **Backend Symfony** : Architecture robuste avec Doctrine ORM
- **Base de données** : Support PostgreSQL et MySQL

#### 🎮 Gameplay
- **Validation des coups** : Vérification automatique de la légalité des coups
- **États de jeu** : Gestion des états (en cours, terminé, abandonné)
- **Notation** : Support des notations UCI et SAN
- **FEN** : Import/export des positions au format FEN

#### 🛠️ Technique
- **Symfony 6.4** : Framework backend moderne
- **PHP 8.4+** : Support des dernières fonctionnalités PHP
- **Webpack Encore** : Build system pour les assets frontend
- **PHPUnit** : Suite de tests complète
- **Docker** : Containerisation pour le développement

---

## Format des Versions

Ce projet suit le [Versioning Sémantique](https://semver.org/) :

- **MAJOR** (X.0.0) : Changements incompatibles avec les versions précédentes
- **MINOR** (X.Y.0) : Nouvelles fonctionnalités compatibles
- **PATCH** (X.Y.Z) : Corrections de bugs compatibles

### Types de Changements

- **✨ Ajouté** : Nouvelles fonctionnalités
- **🔄 Modifié** : Changements dans les fonctionnalités existantes
- **⚠️ Déprécié** : Fonctionnalités qui seront supprimées
- **⚖️ Supprimé** : Fonctionnalités supprimées
- **🐛 Corrigé** : Corrections de bugs
- **🔒 Sécurité** : Corrections de vulnérabilités

## Migration

### De v1.x vers v2.0

La version 2.0 introduit des changements majeurs dans le système de rendu :

1. **Système de pièces** : Les pièces sont maintenant géométriques au lieu d'Unicode
2. **Suppression API Platform** : Les endpoints API Platform ont été supprimés
3. **AssetMapper** : Migration de Webpack Encore vers AssetMapper

#### Guide de Migration

```bash
# 1. Sauvegarder votre base de données
php bin/console doctrine:migrations:dump-schema

# 2. Mettre à jour le code
git pull origin main

# 3. Installer les nouvelles dépendances
composer install

# 4. Compiler les nouveaux assets
php bin/console asset-map:compile

# 5. Vider les caches
php bin/console cache:clear
```

## Support des Versions

| Version | Status        | Support PHP | Support Symfony | Fin de Support |
|---------|--------------|-------------|-----------------|----------------|
| 2.0.x   | ✅ Actuel    | 8.4+        | 6.4+           | -              |
| 1.2.x   | 🔄 LTS       | 8.4+        | 6.4            | 2025-06-01     |
| 1.1.x   | ⚠️ Déprécié  | 8.0+        | 6.3+           | 2025-01-01     |
| 1.0.x   | ❌ Obsolète  | 8.0+        | 6.3+           | 2024-12-01     |

---

## Liens Utiles

- **Repository** : [GitHub](https://github.com/magicolala/chess-teams)
- **Documentation** : [README.md](README.md)
- **API** : [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
- **Contribution** : [CONTRIBUTING.md](CONTRIBUTING.md)
- **Issues** : [GitHub Issues](https://github.com/magicolala/chess-teams/issues)
- **Releases** : [GitHub Releases](https://github.com/magicolala/chess-teams/releases)

# Changelog

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

## [Non publié]

### À venir
- API REST native GLPI + jeu de tests (Phase 9)
- Droits fins par profil GLPI (Phase 10)

## [1.0.1] - 2026-11-23

### Corrigé
- Les tâches exceptionnelles d'un modèle gardent leur statut (et leur affichage distinct) une fois la checklist ajoutée à un élément.
- Un suivi est aussi créé sur le ticket quand une tâche repasse de « Fait » à « À faire ».
- Suppression de l'avertissement GLPI 11 « All plugins resources should be accessed from the `/plugins/` path » (remplacement de `Plugin::getWebDir()`).
- Les onglets demandés pour des éléments non `CommonDBTM` ou non enregistrés sont ignorés (erreur 500 à l'export PDF).

## [1.0.0] - 2026-06-10

Première version publique. GLPI 11.0.x.

### Ajouté
- **Modèles de checklists** réutilisables avec CRUD complet (`Configuration › Modèles de checklist`).
  - Réordonnancement des tâches par glisser-déposer.
  - Sélection de l'entité et récursivité (sous-entités).
  - Délai de notification configurable en **heures / jours / semaines**.
- **Onglet Checklist** disponible sur tous les itemtypes GLPI configurés (Ticket, Computer, Phone, Monitor, NetworkEquipment, Printer, Software, Peripheral, Rack, Enclosure).
- **Vue Kanban** « À faire » / « Fait » avec bascule au clic et drag & drop (SortableJS).
- **Plusieurs checklists par élément** avec barre de progression.
- **Tâches exceptionnelles** ad hoc, distinctes des tâches verrouillées issues du modèle.
- **Historique immuable** des actions par checklist.
- **Moteur de règles GLPI** (`Administration › Règles`) : association automatique d'un modèle selon le type d'élément, le titre/nom, la catégorie ITIL ou la description.
- **Notifications des tâches en retard** : tâche CRON `checklistOverdue` (horaire) + suivi privé sur les tickets.
- **Intégration timeline ticket** : bouton « Valider une tâche checklist » + création automatique d'un suivi quand une tâche est cochée.
- **Sélecteur de modèle avec recherche** à la création d'une checklist.
- **Internationalisation FR / EN** (fichiers `.po`/`.mo`).
- **Environnement de développement Docker** (GLPI 11 + MariaDB + PhpMyAdmin).

### Sécurité
- Contrôle d'accès sur tous les endpoints AJAX : vérification des droits de l'utilisateur sur l'élément parent (`$item->can()`), couvrant droit + entité + accès spécifique (protection IDOR).
- Échappement HTML systématique des valeurs dynamiques (serveur + JavaScript).
- CSRF via le header `X-Glpi-Csrf-Token` de GLPI 11.
- L'utilitaire de dev `compile_mo.php` est restreint à la CLI.

[Non publié]: https://github.com/aprolex/GLPI-Checklist/compare/v1.0.1...HEAD
[1.0.1]: https://github.com/aprolex/GLPI-Checklist/releases/tag/v1.0.1
[1.0.0]: https://github.com/aprolex/GLPI-Checklist/releases/tag/v1.0.0

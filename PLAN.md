# 📋 Plan — Plugin GLPI Checklist

> Version du plan : 1.1 _(mis à jour après validation)_  
> Date : 09/06/2026  
> GLPI cible : **11.0.x**

---

## 🗺️ Vue d'ensemble

```
┌─────────────────────────────────────────────────────────┐
│                     GLPI (10.0.x)                       │
│                                                         │
│  Ticket / Ordinateur / Smartphone / Équipement ...      │
│  ┌─────────────────────────────────────────────────┐   │
│  │  Onglet [Checklist]                             │   │
│  │  📋 Checklist SIRH     ██████░░  3/5  [▼ Ouvrir]│  │
│  │  📋 Checklist Accueil  ████████  7/7  [▼ Ouvrir]│  │
│  │  [+ Nouvelle checklist]                         │   │
│  │  ─────────────────────────────────────────────  │   │
│  │  ▼ Checklist SIRH (dépliée)                    │   │
│  │  ┌─────────────────┬──────────────────────┐    │   │
│  │  │  📋 À FAIRE     │  ✅ FAIT              │    │   │
│  │  │  ── Tâche 1     │  ── Tâche 4 (log)    │    │   │
│  │  │  ⚠ Tâche 2 EXC  │  ── Tâche 5 (log)   │    │   │
│  │  │  ── Tâche 3     │                      │    │   │
│  │  └─────────────────┴──────────────────────┘    │   │
│  │  [+ Action exceptionnelle]  [📜 Historique]     │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│  Moteur de règles GLPI                                  │
│  → Si ticket.titre contient "SIRH" → Template "SIRH"   │
│  → Si item.type = "Computer" → Template "Deploy PC"    │
└─────────────────────────────────────────────────────────┘
              │
              │  REST API GLPI
              ▼
┌─────────────────────────────┐
│  Logiciel PC Déploiement    │
│  (Futur — Phase 7)          │
└─────────────────────────────┘
```

---

## 🏗️ Phase 1 — Environnement Docker

### Stack technique
| Composant | Approche | Version |
|-----------|----------|---------|
| GLPI | Dockerfile custom (build local) | **11.0.x** |
| Base de données | `mariadb:10.11` | 10.11 |
| Web server | Apache (dans le Dockerfile) | 2.4 |
| PHP | Image officielle `php:8.2-apache` | 8.2 |

### Structure Docker
```text
glpi-plugin-checklist/
├── docker-compose.yml
├── .env
├── docker/                 ← Image GLPI 11 personnalisée
│   ├── Dockerfile
│   ├── apache-glpi.conf    ← Vhost Apache (DocumentRoot → public/)
│   ├── php.ini             ← Config PHP optimisée GLPI
│   └── entrypoint.sh       ← Attente MariaDB + install auto optionnelle
├── mariadb/
│   └── init/               ← Scripts SQL d'initialisation optionnels
└── plugin/                 ← CODE SOURCE du plugin (monté dans le conteneur)
    └── checklist/
```

### Accès
- **GLPI** : http://localhost:8080
- **PhpMyAdmin** (optionnel) : http://localhost:8081
- **Identifiants GLPI par défaut** : `glpi` / `glpi`

---

## 🗄️ Phase 2 — Base de données du plugin

### Tables

#### `glpi_plugin_checklist_templates`
Stocke les modèles de checklists réutilisables.

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT PK AUTO | Identifiant |
| `name` | VARCHAR(255) | Nom du template |
| `comment` | TEXT | Description |
| `itemtype` | VARCHAR(100) | Type GLPI lié (Ticket, Computer, …) |
| `is_active` | TINYINT | Actif/inactif |
| `date_creation` | DATETIME | Date création |
| `date_mod` | DATETIME | Date modification |
| `users_id` | INT FK | Créateur |
| `entities_id` | INT FK | Entité GLPI |
| `is_recursive` | TINYINT | Récursif sur sous-entités |
| `notification_delay_hours` | INT | Délai avant alerte tâche en retard (en heures, 0 = désactivé) |

#### `glpi_plugin_checklist_templateitems`
Tâches définies dans un template.

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT PK AUTO | Identifiant |
| `plugin_checklist_templates_id` | INT FK | Template parent |
| `name` | VARCHAR(500) | Libellé de la tâche |
| `rank` | INT | Ordre d'affichage |
| `is_exceptional` | TINYINT | Tâche exceptionnelle ? |
| `description` | TEXT | Description/aide |
| `date_creation` | DATETIME | |
| `date_mod` | DATETIME | |

#### `glpi_plugin_checklist_checklists`
Instance de checklist liée à un élément GLPI concret.

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT PK AUTO | Identifiant |
| `name` | VARCHAR(255) | Nom de l'instance |
| `itemtype` | VARCHAR(100) | Type de l'élément lié (Ticket, Computer…) |
| `items_id` | INT | ID de l'élément lié |
| `plugin_checklist_templates_id` | INT FK NULL | Template d'origine (NULL si ad hoc) |
| `status` | VARCHAR(50) | `open` / `closed` |
| `date_creation` | DATETIME | |
| `date_mod` | DATETIME | |
| `users_id` | INT FK | Créateur |
| `entities_id` | INT FK | |

#### `glpi_plugin_checklist_items`
Tâches d'une checklist instanciée.

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT PK AUTO | |
| `plugin_checklist_checklists_id` | INT FK | Checklist parente |
| `name` | VARCHAR(500) | Libellé |
| `rank_todo` | INT | Ordre dans "À faire" |
| `rank_done` | INT | Ordre dans "Fait" |
| `status` | ENUM(`todo`, `done`) | Statut |
| `is_exceptional` | TINYINT | Tâche exceptionnelle ? |
| `description` | TEXT | |
| `date_creation` | DATETIME | |
| `date_mod` | DATETIME | |
| `date_todo` | DATETIME NULL | Date depuis laquelle la tâche est en statut `todo` |
| `date_notified` | DATETIME NULL | Date du dernier envoi de notification de retard |
| `users_id_creator` | INT FK | Qui a créé la tâche |

#### `glpi_plugin_checklist_logs`
Historique de toutes les actions (immuable).

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT PK AUTO | |
| `plugin_checklist_items_id` | INT FK | Tâche concernée |
| `plugin_checklist_checklists_id` | INT FK | Checklist concernée |
| `users_id` | INT FK | Utilisateur qui a agi |
| `action` | VARCHAR(100) | `moved_to_done`, `moved_to_todo`, `created`, `deleted`, `reordered` |
| `old_value` | TEXT NULL | Valeur avant (JSON) |
| `new_value` | TEXT NULL | Valeur après (JSON) |
| `date_action` | DATETIME | Date/heure de l'action |

---

## 🧩 Phase 3 — Architecture du plugin

### Arborescence des fichiers
```
plugins/checklist/
│
├── setup.php                    ← Déclaration du plugin (obligatoire)
├── hook.php                     ← Hooks GLPI (install, uninstall, tabs…)
│
├── inc/                         ← Classes PHP métier
│   ├── checklist.class.php      ← Entité principale (instance checklist)
│   ├── checklistitem.class.php  ← Tâche individuelle
│   ├── checklistlog.class.php   ← Historique
│   ├── template.class.php       ← Template de checklist
│   ├── templateitem.class.php   ← Tâche dans un template
│   ├── config.class.php         ← Configuration globale
│   ├── rulechecklist.class.php  ← Extension moteur de règles GLPI
│   └── crontask.class.php       ← Tâche planifiée (notifications de retard)
│
├── front/                       ← Pages PHP accessibles via URL
│   ├── checklist.php            ← Liste des checklists
│   ├── checklist.form.php       ← Formulaire checklist
│   ├── template.php             ← Liste des templates
│   └── template.form.php        ← Formulaire template
│
├── ajax/                        ← Endpoints AJAX (appelés depuis JS)
│   ├── move_item.php            ← Déplacer tâche todo↔done + log
│   ├── reorder_items.php        ← Sauvegarder l'ordre après drag&drop
│   ├── add_item.php             ← Ajouter tâche exceptionnelle
│   ├── delete_item.php          ← Supprimer une tâche
│   └── get_history.php          ← Charger l'historique (JSON)
│
├── templates/                   ← Templates Twig (GLPI 10+)
│   ├── checklist_tab.html.twig  ← Vue de l'onglet dans un élément
│   ├── kanban.html.twig         ← Colonnes À faire / Fait
│   ├── history.html.twig        ← Affichage de l'historique
│   └── template_form.html.twig  ← Formulaire de template
│
├── css/
│   └── checklist.css            ← Styles Kanban, badges, animations
│
├── js/
│   └── checklist.js             ← SortableJS + AJAX calls
│
├── locales/
│   ├── fr_FR.po                 ← Traductions FR
│   └── en_GB.po                 ← Traductions EN
│
└── sql/
    ├── install.sql              ← Création des tables
    └── uninstall.sql            ← Suppression des tables
```

---

## ⚙️ Phase 4 — Fonctionnalités détaillées

### 4.1 Gestion des Templates
- Interface de gestion standard GLPI (liste + formulaire)
- Définir le **type d'élément cible** (Ticket, Computer, NetworkEquipment…) — ou laisser générique (tous types)
- Ajouter/supprimer/réordonner des tâches dans le template
- Marquer une tâche comme **exceptionnelle** dans le template (flag `is_exceptional`)
- Gestion des entités et droits GLPI
- Duplication de template
- Configurer le **délai d'alerte** par template (ex : `48` = alerte si une tâche est en "À faire" depuis plus de 48h) — `0` = aucune notification

### 4.2 Onglet Checklist dans les éléments GLPI
- Hook `plugin_checklist_getTabNameForItem` → affiche l'onglet sur **tous les itemtypes activés** dans la config admin
- L'onglet affiche la **liste de toutes les checklists liées** à l'élément (plusieurs par élément autorisées) :
  - Chaque ligne : icône + nom de la checklist + barre de progression (`3/7 tâches faites`)
  - Bouton **[+ Nouvelle checklist]** → choisir un template ou créer ad hoc
- Cliquer sur une checklist → **déplie la vue Kanban** :
  - **Colonne "À faire"** : liste ordonnée, drag & drop via SortableJS
  - **Colonne "Fait"** : liste ordonnée, drag & drop via SortableJS
  - Clic sur une tâche → déplacement automatique dans l'autre colonne + entrée dans le log
  - Tâches issues du template : **non modifiables** (libellé verrouillé — badge 🔒)
  - Bouton **[+ Action exceptionnelle]** → modal pour saisir une tâche ad hoc
- Distinction visuelle claire :
  - Tâche normale (template, verrouillée) : style standard, icône 🔒
  - Tâche exceptionnelle (ad hoc) : badge `⚠ EXC`, fond coloré distinctif

### 4.3 Historique
- Sous-onglet ou section dépliable dans l'onglet Checklist
- Chaque entrée : `[Date] [Utilisateur] a [action] la tâche "[nom]"`
- Exemples :
  - `12/06/2026 14:32 — Jean Dupont a marqué "Vérifier antivirus" comme fait`
  - `12/06/2026 14:35 — Jean Dupont a réordonné les tâches`
  - `12/06/2026 15:00 — Marie Martin a ajouté une action exceptionnelle : "Câble réseau manquant"`

### 4.4 Règles GLPI (RuleCollection)
- Nouvelle collection de règles : **"Règles de checklists"**
- **Critères disponibles** :
  - Titre de l'élément (contient / est égal à / regex)
  - Type d'élément (Ticket, Computer…)
  - Catégorie ITIL / catégorie d'équipement
  - Groupe assigné
  - Tags/Labels
- **Actions disponibles** :
  - Associer le template X à cet élément
  - Associer plusieurs templates
  - Ne pas associer de checklist
- **Déclenchement** :
  - À la création de l'élément
  - À la modification de l'élément (optionnel)
  - Manuellement depuis l'onglet
- **Exemples de règles** :
  ```
  Règle 1 : Ticket.titre CONTIENT "SIRH"    → Template "Checklist SIRH"
  Règle 2 : Ticket.titre CONTIENT "DEPLOY"  → Template "Déploiement PC"
  Règle 3 : itemtype = Computer             → Template "Inventaire PC"
  ```

### 4.5 API REST (via API GLPI native)
GLPI expose une API REST native. Le plugin l'étend avec :

#### Endpoints

| Méthode | URL | Description |
|---------|-----|-------------|
| `GET` | `/apirest.php/PluginChecklistChecklist` | Liste toutes les checklists |
| `GET` | `/apirest.php/PluginChecklistChecklist/{id}` | Détail d'une checklist |
| `GET` | `/apirest.php/PluginChecklistChecklist?itemtype=Computer&items_id=42` | Checklists d'un ordinateur |
| `GET` | `/apirest.php/PluginChecklistItem?plugin_checklist_checklists_id={id}` | Tâches d'une checklist |
| `PUT` | `/apirest.php/PluginChecklistItem/{id}` | Modifier le statut d'une tâche |
| `GET` | `/apirest.php/PluginChecklistLog?plugin_checklist_checklists_id={id}` | Historique |
| `GET` | `/apirest.php/PluginChecklistTemplate` | Liste les templates |

**Authentification** : Token GLPI standard (`Authorization: user_token xxx`)

---

### 4.6 Notifications — Tâches en retard

- Intégration dans le **système de notifications natif GLPI 11** (`NotificationEvent`)
- Configuration par template : champ `notification_delay_hours` (ex : `48` = alerte après 2 jours sans action)
- **Tâche CRON** `PluginChecklistCronTask` — déclarée dans le planificateur GLPI :
  - Parcourt toutes les tâches en statut `todo` avec un template ayant `notification_delay_hours > 0`
  - Compare `date_todo + notification_delay_hours` avec `NOW()`
  - Si dépassé → envoie une notification et met à jour `date_notified`
  - Gère les re-notifications (évite le spam : ne re-notifie qu'après un nouveau délai)
- **Destinataires configurables** dans les paramètres du plugin :
  - `Ticket` → technicien(s) assigné(s), groupe assigné
  - `Computer` / autres → responsable technique, groupe
- **Contenu de la notification** :
  - Nom de la checklist + élément lié + lien direct
  - Liste des tâches en retard avec leur ancienneté
- **Événement GLPI créé** : `ChecklistTaskOverdue`

---

### 4.7 Support de tous les itemtypes GLPI

- L'onglet Checklist s'affiche sur **n'importe quel type d'objet GLPI** (`CommonDBTM`)
- **Page de configuration admin** du plugin : liste de tous les itemtypes avec case à cocher
- Itemtypes **activés par défaut** à l'installation :
  - `Ticket`, `Computer`, `Phone`, `Monitor`, `NetworkEquipment`, `Printer`, `Software`
- L'administrateur peut activer/désactiver n'importe quel type sans redémarrage
- Config stockée dans `glpi_plugin_checklist_configs`

---

## 🔒 Phase 5 — Droits et sécurité

| Profil | Droits |
|--------|--------|
| Super-admin | Tout |
| Admin | Gérer templates + règles + checklists |
| Technicien | Voir + modifier checklists (déplacer tâches, ajouter exceptionnelles) |
| Observateur | Lecture seule |

- Intégration dans le système de profils GLPI standard
- Vérification CSRF sur tous les appels AJAX
- Vérification des droits avant chaque action API

---

## 🔁 Phase 6 — Logiciel de déploiement PC (Futur)

> À développer en Phase 7 — hors scope initial

- Application desktop (Electron ou WPF ou PyQt) sur le PC technicien
- Connexion à l'API GLPI via token
- Récupère la checklist liée à l'ordinateur en cours de déploiement
- Affiche les tâches sous forme de wizard/checklist interactive
- Coche les tâches → appelle `PUT /apirest.php/PluginChecklistItem/{id}`
- Tout est loggé dans GLPI en temps réel

---

## 🗓️ Roadmap de développement

| Phase | Contenu | Priorité |
|-------|---------|----------|
| **Phase 1** | Docker + GLPI 11 opérationnel | 🔴 Critique |
| **Phase 2** | Structure plugin + tables SQL | 🔴 Critique |
| **Phase 3** | Templates (CRUD) | 🔴 Critique |
| **Phase 4** | Onglet Checklist + vue Kanban (multi-checklists) | 🔴 Critique |
| **Phase 5** | Drag & drop + ordonnancement | 🟠 Important |
| **Phase 6** | Historique | 🟠 Important |
| **Phase 7** | Règles GLPI | 🟠 Important |
| **Phase 8** | Notifications CRON (tâches en retard) | 🟠 Important |
| **Phase 9** | API REST + tests | 🟡 Utile |
| **Phase 10** | Droits fins + i18n FR/EN | 🟡 Utile |
| **Phase 11** | Logiciel PC déploiement | 🔵 Futur |

---

## ✅ Décisions arrêtées

| # | Question | Décision |
|---|----------|----------|
| 1 | Types d'éléments | **Tous les itemtypes GLPI** — liste activable/désactivable par l'admin |
| 2 | Checklists par élément | **Plusieurs** checklists par élément autorisées |
| 3 | Tâches modifiables | **Non** — tâches template verrouillées 🔒 ; tâches exceptionnelles ajoutables (visuellement distinctes `⚠ EXC`) |
| 4 | Clôture automatique | **Non** |
| 5 | Version GLPI | **GLPI 11.0.x** |
| 6 | Notifications | **Oui** — délai configurable par template ; alerte GLPI si tâche en retard |
| 7 | Langues | **Bilingue FR / EN** dès le départ |

---

## 📁 Structure du dépôt

```text
glpi-plugin-checklist/               ← Ce dépôt
│
├── PLAN.md                          ← Ce fichier
├── README.md                        ← Guide de démarrage rapide
├── docker-compose.yml               ← Stack Docker
├── .env                             ← Variables d'environnement
│
├── docker/                          ← Image GLPI 11 personnalisée
│   ├── Dockerfile
│   ├── apache-glpi.conf
│   ├── php.ini
│   └── entrypoint.sh
│
├── mariadb/
│   └── init/                        ← Scripts SQL de démarrage optionnels
│
└── plugin/
    └── checklist/                   ← Code source du plugin
        ├── setup.php
        ├── hook.php
        ├── inc/
        ├── front/
        ├── ajax/
        ├── templates/
        ├── css/
        ├── js/
        ├── locales/
        └── sql/
```

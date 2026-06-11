<div align="center">

# 📋 GLPI Plugin Checklist

**Des checklists opérationnelles intégrées directement dans vos éléments GLPI.**

[![GLPI](https://img.shields.io/badge/GLPI-11.0.x-2c5a8c?logo=glpi&logoColor=white)](https://glpi-project.org)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![License](https://img.shields.io/badge/license-GPL%20v3%2B-blue.svg)](LICENSE)
[![Version](https://img.shields.io/badge/version-1.0.0-success.svg)](CHANGELOG.md)
[![FR / EN](https://img.shields.io/badge/i18n-FR%20%2F%20EN-informational.svg)](plugin/checklist/locales)

</div>

---

Ce plugin ajoute un onglet **Checklists** à n'importe quel élément GLPI (Tickets, Ordinateurs, Téléphones, etc.). Il permet de créer des checklists à partir de **modèles réutilisables**, de suivre l'avancement des tâches dans une **vue Kanban** (drag & drop), et d'**automatiser** leur association via le moteur de règles GLPI. Les tâches en retard déclenchent des **notifications**.

> 💡 Cas d'usage typique : onboarding d'un collaborateur, déploiement d'un poste, procédure de mise en service d'un équipement…

---

## ✨ Fonctionnalités

- 🗂️ **Modèles de checklists réutilisables** — définissez une fois, instanciez partout. Réordonnancement des tâches par glisser-déposer, gestion des entités.
- 📌 **Onglet Checklist universel** — disponible sur tous les itemtypes GLPI (Ticket, Computer, Phone, Monitor, NetworkEquipment, Printer, Software…).
- 🔁 **Plusieurs checklists par élément** — chacune avec sa propre barre de progression.
- 🟦 **Vue Kanban « À faire » / « Fait »** — bascule par clic, réordonnancement par drag & drop (SortableJS).
- ⚠️ **Tâches exceptionnelles** — ajoutez des actions ad hoc, distinguées visuellement des tâches verrouillées issues du modèle.
- 📜 **Historique immuable** — qui a fait quoi et quand, par checklist.
- ⚙️ **Moteur de règles GLPI** — associez automatiquement un modèle à un élément selon son titre, son type, sa catégorie ITIL… (collection de règles native dans *Administration › Règles*).
- ⏰ **Notifications des tâches en retard** — tâche CRON avec délai configurable par modèle, au choix en **heures / jours / semaines**.
- 🎫 **Intégration timeline des tickets** — bouton *« Valider une tâche checklist »* + suivi automatique créé quand une tâche est cochée.
- 🔍 **Sélecteur de modèle avec recherche** à la création d'une checklist.
- 🌍 **Bilingue FR / EN** dès l'installation.
- 🔐 **Contrôle d'accès** — chaque action vérifie les droits de l'utilisateur sur l'élément parent (pas d'IDOR).

---

## 🧩 Compatibilité

| Composant | Version |
|-----------|---------|
| GLPI | **11.0.x** |
| PHP | **8.1+** (testé sur 8.2) |
| Base de données | MariaDB 10.11 / MySQL 8 |

---

## 📦 Installation (en production)

1. Téléchargez la dernière [release](../../releases) ou clonez ce dépôt.
2. Copiez le dossier **`plugin/checklist/`** dans le répertoire `plugins/` de votre GLPI :
   ```bash
   cp -r plugin/checklist /chemin/vers/glpi/plugins/checklist
   ```
   > ⚠️ Le dossier doit impérativement s'appeler `checklist`.
3. Dans GLPI : **Configuration › Plugins** → trouvez **Checklist** → **Installer** puis **Activer**.
4. (Optionnel) Vérifiez la tâche planifiée dans **Configuration › Actions automatiques** (`checklistOverdue`).

---

## 🚀 Utilisation

### Modèles
**Configuration › Modèles de checklist** → créez un modèle, ajoutez des tâches, choisissez l'entité et le délai de notification (heures / jours / semaines).

### Sur un élément
Ouvrez un Ticket (ou un Ordinateur…) → onglet **Checklists** → **+ Nouvelle checklist** → choisissez un modèle (recherche intégrée) ou partez d'une checklist vide.

### Automatisation par règles
**Administration › Règles › Checklist - Règles d'association de checklists** → créez une règle (ex. *« titre du ticket contient SIRH → modèle SIRH »*). La checklist est créée automatiquement à l'ouverture de l'élément.

### Notifications
Définissez un délai sur le modèle. La tâche CRON `checklistOverdue` (horaire) notifie les tâches dépassant `date_todo + délai` et journalise l'événement.

---

## 🗺️ Roadmap

| Phase | Contenu | État |
|-------|---------|------|
| 1 | Environnement Docker | ✅ |
| 2 | Schéma de base de données | ✅ |
| 3 | Modèles (CRUD) | ✅ |
| 4 | Onglet Checklist + Kanban | ✅ |
| 5 | Drag & drop / ordonnancement | ✅ |
| 6 | Historique | ✅ |
| 7 | Moteur de règles GLPI | ✅ |
| 8 | Notifications CRON (tâches en retard) | ✅ |
| 9 | API REST + tests | ⏳ |
| 10 | Droits fins par profil | ⏳ |
| 11 | Logiciel de déploiement PC (client) | 🔮 Futur |

Détail complet dans [PLAN.md](PLAN.md).

---

## 🔐 Sécurité

Tous les endpoints AJAX vérifient les droits de l'utilisateur courant sur l'**élément parent** (`$item->can($id, $right)`), ce qui couvre droit + entité + accès spécifique. Les valeurs dynamiques sont échappées (HTML côté serveur via `htmlspecialchars`, JS via une fonction d'échappement dédiée). Le CSRF s'appuie sur le header `X-Glpi-Csrf-Token` de GLPI 11.

Une faille ? Voir [SECURITY.md](.github/SECURITY.md).

---

## 🤝 Contribuer

Les contributions sont les bienvenues ! Consultez [CONTRIBUTING.md](CONTRIBUTING.md) pour l'environnement de dev, les conventions et le workflow i18n.

---

## 📄 Licence

Distribué sous licence **GPL v3+**. Voir [LICENSE](LICENSE).

## 👤 Auteur

Développé par **Aprolex**.

---

<div align="center">
<sub>Si ce plugin vous est utile, pensez à ⭐ le dépôt !</sub>
</div>

# Plugin Checklist — GLPI 11

Dossier source du plugin. Pour l'installer, copiez **ce dossier** (`checklist/`) dans le répertoire `plugins/` de votre GLPI :

```
glpi/plugins/checklist/
```

Puis, dans GLPI : **Configuration › Plugins** → **Checklist** → Installer → Activer.

> Le dossier doit impérativement s'appeler `checklist` (clé du plugin).

## Contenu

| Dossier | Rôle |
|---------|------|
| `setup.php` | Déclaration du plugin, hooks d'initialisation |
| `hook.php` | Installation/désinstallation, CRON, timeline, déclenchement des règles |
| `inc/` | Classes métier (checklist, item, template, log, rule, crontask) |
| `front/` | Pages liste et formulaires |
| `ajax/` | Endpoints AJAX (Kanban, création, réordonnancement…) |
| `locales/` | Traductions FR / EN (`.po` source + `.mo` compilés) |

Documentation complète, environnement de dev et contribution : voir le [README principal](../../README.md) et [CONTRIBUTING.md](../../CONTRIBUTING.md).

Licence : **GPL v3+**.

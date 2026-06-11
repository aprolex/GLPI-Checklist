# Contribuer à GLPI Plugin Checklist

Merci de votre intérêt ! Ce document décrit les conventions du projet.

## 🗂️ Structure du plugin

```
plugin/checklist/
├── setup.php              # Déclaration du plugin + hooks d'init
├── hook.php               # Install/uninstall, CRON, timeline, moteur de règles
├── inc/                   # Classes métier (CommonDBTM, Rule, CronTask…)
├── front/                 # Pages (liste / formulaires)
├── ajax/                  # Endpoints AJAX
└── locales/              # Traductions .po / .mo
```

> ℹ️ En GLPI 11, le CSS et le JS sont **injectés inline** par `PluginChecklistChecklist::injectAssets()` (le routeur Symfony ne sert pas les assets statiques des plugins dans les onglets dynamiques). Les fichiers `css/checklist.css` et `js/checklist.js` sont volontairement vides.

## ✅ Avant de soumettre

1. **Vérifiez la syntaxe PHP** de tous les fichiers modifiés :
   ```bash
   docker exec glpi_app bash -c 'find /var/www/html/glpi/plugins/checklist -name "*.php" -exec php -l {} \;'
   ```
2. **Respectez le style existant** : `declare(strict_types=1)`, indentation 4 espaces, commentaires en français, conventions de nommage GLPI (`PluginChecklistXxx`, tables `glpi_plugin_checklist_*`).
3. **Sécurité** : tout nouvel endpoint AJAX d'instance doit vérifier l'accès via `PluginChecklistChecklist::canAccessParent()` ou `getCheckedChecklist()`. Échappez les valeurs dynamiques.

## 🌍 Traductions (i18n)

Les chaînes utilisent le domaine `checklist` : `__('Mon texte', 'checklist')`.

1. Ajoutez la chaîne dans `plugin/checklist/locales/fr_FR.po` (et `en_GB.po`).
2. Recompilez les `.mo` :
   ```bash
   docker exec glpi_app php /var/www/html/glpi/plugins/checklist/locales/compile_mo.php
   ```
3. Videz le cache GLPI (obligatoire, sinon les nouvelles chaînes restent en anglais) :
   ```bash
   docker exec glpi_app php /var/www/html/glpi/bin/console cache:clear --allow-superuser
   ```

## 🔀 Workflow Git

1. Créez une branche depuis `main` : `git checkout -b feat/ma-fonctionnalite`.
2. Commits clairs (idéalement [Conventional Commits](https://www.conventionalcommits.org/) : `feat:`, `fix:`, `docs:`…).
3. Ouvrez une Pull Request en décrivant le **quoi** et le **pourquoi**, avec captures d'écran si l'UI change.

## 🐛 Signaler un bug / proposer une fonctionnalité

Utilisez les [issues GitHub](../../issues) avec les modèles fournis.

Merci ! 🙌

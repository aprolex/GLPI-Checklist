<?php
/**
 * front/template.php — Liste des templates de checklist
 */

declare(strict_types=1);

// GLPI 11 : inclus par LegacyFileLoadController (Symfony). Ne pas re-inclure.

Session::checkRight('config', READ);

Html::header(
    __('Checklist templates', 'checklist'),
    $_SERVER['PHP_SELF'],
    'config',
    'PluginChecklistTemplate'
);

PluginChecklistTemplate::showList();

Html::footer();

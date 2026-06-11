<?php
/**
 * front/template.form.php — Formulaire CRUD des templates + tâches
 */

declare(strict_types=1);

// GLPI 11 : inclus par LegacyFileLoadController (Symfony). Ne pas re-inclure.

Session::checkRight('config', UPDATE);

$tpl  = new PluginChecklistTemplate();
$item = new PluginChecklistTemplateItem();

// Normalise l'unité de délai sur une valeur autorisée (évite les données corrompues)
if (isset($_POST['notification_delay_unit'])
    && !in_array($_POST['notification_delay_unit'], ['hours', 'days', 'weeks'], true)) {
    $_POST['notification_delay_unit'] = 'hours';
}

// ─── Actions POST ─────────────────────────────────────────────────────────────

if (isset($_POST['add'])) {
    // Création d'un nouveau template
    $new_id = $tpl->add($_POST);
    Html::redirect(Plugin::getWebDir('checklist') . '/front/template.form.php?id=' . $new_id);
}

if (isset($_POST['update'])) {
    $tpl->update($_POST);
    Html::back();
}

if (isset($_POST['purge'])) {
    $tpl->delete($_POST, true);
    Html::redirect(Plugin::getWebDir('checklist') . '/front/template.php');
}

if (isset($_POST['add_item'])) {
    // Ajout d'une tâche au template
    $item->add([
        'plugin_checklist_templates_id' => (int) $_POST['template_id'],
        'name'                          => $_POST['name'] ?? '',
        'description'                   => $_POST['description'] ?? '',
        'rank'                          => (int) ($_POST['rank'] ?? 0),
        'is_exceptional'                => isset($_POST['is_exceptional']) ? 1 : 0,
    ]);
    Html::back();
}

if (isset($_POST['delete_item'])) {
    $item->delete(['id' => (int) $_POST['id']], true);
    Html::back();
}

// ─── Affichage ────────────────────────────────────────────────────────────────

$ID = (int) ($_GET['id'] ?? 0);

Html::header(
    $ID > 0 ? __('Edit checklist template', 'checklist') : __('New checklist template', 'checklist'),
    $_SERVER['PHP_SELF'],
    'config',
    'PluginChecklistTemplate'
);

$tpl->display(['id' => $ID]);

Html::footer();

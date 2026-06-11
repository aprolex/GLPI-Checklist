<?php
/**
 * ajax/reorder_items.php — Sauvegarder l'ordre des tâches après drag & drop
 */

declare(strict_types=1);

header('Content-Type: application/json');

// GLPI 11 : le bootstrap est déjà chargé par le routeur Symfony (LegacyFileLoadController).
// Ne pas re-inclure inc/includes.php.

Session::checkLoginUser();
// GLPI 11 vérifie le CSRF automatiquement via le header X-Glpi-Csrf-Token (CheckCsrfListener).

$cl_id  = (int) ($_POST['cl_id']  ?? 0);
$column = $_POST['column'] ?? 'todo';
$ids    = $_POST['ids']    ?? [];

if ($cl_id <= 0 || empty($ids)) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

// Contrôle d'accès : l'utilisateur doit pouvoir modifier l'élément parent
if (PluginChecklistChecklist::getCheckedChecklist($cl_id, UPDATE) === null) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

// Restreint le réordonnancement aux tâches appartenant réellement à cette checklist
$ids = array_values(array_filter(array_map('intval', (array) $ids), static function ($id) use ($cl_id) {
    return countElementsInTable(PluginChecklistItem::getTable(), [
        'id'                             => $id,
        'plugin_checklist_checklists_id' => $cl_id,
    ]) > 0;
}));

if (empty($ids)) {
    echo json_encode(['success' => false, 'error' => 'No valid items']);
    exit;
}

$ok = PluginChecklistItem::updateRanks($ids, $column);

if ($ok) {
    PluginChecklistLog::addEntry($cl_id, 0, 'reordered');
}

echo json_encode(['success' => $ok]);

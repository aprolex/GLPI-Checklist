<?php
/**
 * ajax/checklist.php — Créer / supprimer une checklist
 */

declare(strict_types=1);

header('Content-Type: application/json');

// GLPI 11 : le bootstrap est déjà chargé par le routeur Symfony (LegacyFileLoadController).
// Ne pas re-inclure inc/includes.php.

Session::checkLoginUser();
// GLPI 11 vérifie le CSRF automatiquement via le header X-Glpi-Csrf-Token (CheckCsrfListener).

$action      = $_POST['action']      ?? '';
$itemtype    = $_POST['itemtype']    ?? '';
$items_id    = (int) ($_POST['items_id']    ?? 0);
$name        = trim($_POST['name']   ?? '');
$tpl_id      = (int) ($_POST['templates_id'] ?? 0);
$cl_id       = (int) ($_POST['cl_id']       ?? 0);

if ($action === 'create') {
    if (empty($name) || empty($itemtype) || $items_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Missing parameters']);
        exit;
    }
    // Contrôle d'accès : l'utilisateur doit pouvoir modifier l'élément parent
    if (!PluginChecklistChecklist::canAccessParent($itemtype, $items_id, UPDATE)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    $id = PluginChecklistChecklist::createForItem($itemtype, $items_id, $name, $tpl_id);
    $total = 0;
    if ($id) {
        $total = (int) countElementsInTable(PluginChecklistItem::getTable(), ['plugin_checklist_checklists_id' => $id]);
    }
    echo json_encode(['success' => $id !== false, 'id' => $id, 'name' => $name, 'total' => $total]);

} elseif ($action === 'delete') {
    if ($cl_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Missing cl_id']);
        exit;
    }
    // Contrôle d'accès : charge la checklist + vérifie l'accès à l'élément parent
    if (PluginChecklistChecklist::getCheckedChecklist($cl_id, UPDATE) === null) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    echo json_encode(['success' => PluginChecklistChecklist::deleteChecklist($cl_id)]);

} else {
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
}

<?php
/**
 * ajax/move_item.php — Basculer une tâche todo ↔ done
 */

declare(strict_types=1);

header('Content-Type: application/json');

// GLPI 11 : le bootstrap est déjà chargé par le routeur Symfony (LegacyFileLoadController).
// Ne pas re-inclure inc/includes.php.

Session::checkLoginUser();
// GLPI 11 vérifie le CSRF automatiquement via le header X-Glpi-Csrf-Token (CheckCsrfListener).

$item_id = (int) ($_POST['item_id'] ?? 0);

if ($item_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Missing item_id']);
    exit;
}

// Contrôle d'accès : l'utilisateur doit pouvoir modifier l'élément parent
$item = new PluginChecklistItem();
if (!$item->getFromDB($item_id)) {
    echo json_encode(['success' => false, 'error' => 'Item not found']);
    exit;
}
if (PluginChecklistChecklist::getCheckedChecklist((int) $item->fields['plugin_checklist_checklists_id'], UPDATE) === null) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

echo json_encode(PluginChecklistItem::toggleStatus($item_id));

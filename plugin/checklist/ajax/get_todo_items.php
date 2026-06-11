<?php
/**
 * ajax/get_todo_items.php — Retourne les tâches todo d'un élément GLPI
 */

declare(strict_types=1);

header('Content-Type: application/json');

Session::checkLoginUser();

$itemtype = $_POST['itemtype'] ?? '';
$items_id = (int) ($_POST['items_id'] ?? 0);

if (empty($itemtype) || $items_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

// Contrôle d'accès : l'utilisateur doit pouvoir consulter l'élément parent
if (!PluginChecklistChecklist::canAccessParent($itemtype, $items_id, READ)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

global $DB;

$cl_table = PluginChecklistChecklist::getTable();
$it_table = PluginChecklistItem::getTable();

$iterator = $DB->request([
    'SELECT'    => [
        "$it_table.id",
        "$it_table.name",
        "$cl_table.name AS cl_name",
    ],
    'FROM'       => $it_table,
    'INNER JOIN' => [
        $cl_table => [
            'ON' => [
                $it_table => 'plugin_checklist_checklists_id',
                $cl_table => 'id',
            ],
        ],
    ],
    'WHERE'     => [
        "$cl_table.itemtype" => $itemtype,
        "$cl_table.items_id" => $items_id,
        "$it_table.status"   => 'todo',
    ],
    'ORDER'     => ["$cl_table.name ASC", "$it_table.rank_todo ASC"],
]);

$items = [];
foreach ($iterator as $row) {
    $items[] = [
        'id'      => (int) $row['id'],
        'name'    => $row['name'],
        'cl_name' => $row['cl_name'],
    ];
}

echo json_encode(['success' => true, 'items' => $items]);

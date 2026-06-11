<?php
/**
 * ajax/add_item.php — Ajouter une tâche exceptionnelle à une checklist
 */

declare(strict_types=1);

header('Content-Type: application/json');

// GLPI 11 : le bootstrap est déjà chargé par le routeur Symfony (LegacyFileLoadController).
// Ne pas re-inclure inc/includes.php.

Session::checkLoginUser();
// GLPI 11 vérifie le CSRF automatiquement via le header X-Glpi-Csrf-Token (CheckCsrfListener).

$cl_id  = (int) ($_POST['cl_id']  ?? 0);
$name   = trim($_POST['name']        ?? '');
$desc   = trim($_POST['description'] ?? '');

if ($cl_id <= 0 || empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

// Contrôle d'accès : l'utilisateur doit pouvoir modifier l'élément parent
if (PluginChecklistChecklist::getCheckedChecklist($cl_id, UPDATE) === null) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$id = PluginChecklistItem::addExceptional($cl_id, $name, $desc);

echo json_encode([
    'success'     => $id !== false,
    'id'          => $id,
    'name'        => $name,
    'description' => $desc,
    'cl_id'       => $cl_id,
]);

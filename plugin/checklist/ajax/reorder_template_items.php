<?php
/**
 * ajax/reorder_template_items.php — Sauvegarder l'ordre des tâches d'un template
 */

declare(strict_types=1);

header('Content-Type: application/json');

// GLPI 11 : bootstrap déjà chargé par le routeur Symfony. Ne pas re-inclure includes.php.

Session::checkRight('config', UPDATE);
// GLPI 11 vérifie le CSRF automatiquement via le header X-Glpi-Csrf-Token.

$template_id = (int) ($_POST['template_id'] ?? 0);
$ids         = $_POST['ids'] ?? [];

if ($template_id <= 0 || empty($ids)) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$ok = PluginChecklistTemplateItem::updateRanks($template_id, array_map('intval', $ids));

echo json_encode(['success' => $ok]);

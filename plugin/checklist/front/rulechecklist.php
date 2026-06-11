<?php
/**
 * front/rulechecklist.php — Liste des règles d'association de checklists
 */

declare(strict_types=1);

// GLPI 11 : inclus par LegacyFileLoadController (Symfony). Ne pas re-inclure includes.php.

Session::checkRight('config', READ);

$rulecollection = new PluginChecklistRuleChecklistCollection();
include(GLPI_ROOT . '/front/rule.common.php');

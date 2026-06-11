<?php
/**
 * Plugin Checklist — setup.php
 * Déclaration du plugin auprès de GLPI 11
 */

declare(strict_types=1);

define('PLUGIN_CHECKLIST_VERSION', '1.0.0');
define('PLUGIN_CHECKLIST_MIN_GLPI', '11.0.0');
define('PLUGIN_CHECKLIST_MAX_GLPI', '12.0.99');
define('PLUGIN_CHECKLIST_DIR', __DIR__);

function plugin_version_checklist(): array
{
    return [
        'name'         => 'Checklist',
        'version'      => PLUGIN_CHECKLIST_VERSION,
        'author'       => 'Aprolex',
        'license'      => 'GPL v3+',
        'homepage'     => '',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_CHECKLIST_MIN_GLPI,
                'max' => PLUGIN_CHECKLIST_MAX_GLPI,
            ],
            'php'  => ['min' => '8.1'],
        ],
    ];
}

function plugin_checklist_check(): bool
{
    return true;
}

function plugin_init_checklist(): void
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['checklist']  = true;
    $PLUGIN_HOOKS['use_language']['checklist']    = true;
    $PLUGIN_HOOKS['timeline_actions']['checklist'] = 'plugin_checklist_timeline_actions';

    if (!Plugin::isPluginActive('checklist')) {
        return;
    }

    // Itemtypes sur lesquels on affiche l'onglet Checklist
    $itemtypes = [
        'Ticket', 'Computer', 'Phone', 'Monitor',
        'NetworkEquipment', 'Printer', 'Software',
        'Peripheral', 'Rack', 'Enclosure',
    ];

    Plugin::registerClass('PluginChecklistChecklist', [
        'addtabon' => $itemtypes,
    ]);

    // ── Phase 7 : moteur de règles ──────────────────────────────────────────
    // Enregistre la collection de règles → apparaît dans Administration > Règles
    Plugin::registerClass('PluginChecklistRuleChecklistCollection', [
        'rulecollections_types' => true,
    ]);

    // Déclenche l'évaluation des règles à la création / modification d'un élément
    foreach ($itemtypes as $type) {
        $PLUGIN_HOOKS['item_add']['checklist'][$type]    = 'plugin_checklist_item_add';
        $PLUGIN_HOOKS['item_update']['checklist'][$type] = 'plugin_checklist_item_update';
    }

    // Entrée de menu sous Configuration
    $PLUGIN_HOOKS['menu_toadd']['checklist'] = ['config' => 'PluginChecklistTemplate'];

    // CSS et JS injectés inline par PluginChecklistChecklist::injectAssets()
    // pour garantir leur chargement dans les onglets dynamiques de GLPI 11.
}

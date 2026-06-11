<?php
/**
 * PluginChecklistTemplateItem — Tâche dans un template de checklist
 */

declare(strict_types=1);

class PluginChecklistTemplateItem extends CommonDBTM
{
    public static $rightname = 'config';

    public static function getTypeName($nb = 0): string
    {
        return _n('Template item', 'Template items', $nb, 'checklist');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_checklist_templateitems';
    }

    public static function getForTemplate(int $template_id): array
    {
        global $DB;

        $items    = [];
        $iterator = $DB->request([
            'FROM'  => static::getTable(),
            'WHERE' => ['plugin_checklist_templates_id' => $template_id],
            'ORDER' => ['rank ASC'],
        ]);

        foreach ($iterator as $row) {
            $items[] = $row;
        }

        return $items;
    }

    /**
     * Réordonne les tâches d'un template selon l'ordre des IDs fournis.
     * Sécurise en vérifiant que chaque tâche appartient bien au template.
     */
    public static function updateRanks(int $template_id, array $item_ids): bool
    {
        global $DB;

        foreach ($item_ids as $rank => $id) {
            $DB->update(static::getTable(), ['rank' => (int) $rank + 1], [
                'id'                            => (int) $id,
                'plugin_checklist_templates_id' => $template_id,
            ]);
        }

        return true;
    }
}

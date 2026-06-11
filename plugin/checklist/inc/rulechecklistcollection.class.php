<?php
/**
 * PluginChecklistRuleChecklistCollection — Collection de règles d'association de checklists
 */

declare(strict_types=1);

class PluginChecklistRuleChecklistCollection extends RuleCollection
{
    // false = on ne s'arrête pas à la première règle : plusieurs règles peuvent
    // matcher et associer chacune un template différent.
    public $stop_on_first_match = false;

    public static $rightname = 'config';

    public $menu_option = 'rulechecklist';

    public function getTitle()
    {
        return __('Checklist assignment rules', 'checklist');
    }

    // Le droit 'config' n'a que READ + UPDATE (pas de CREATE/PURGE).
    // On mappe création/purge sur UPDATE, comme le fait la classe Rule elle-même.
    public static function canCreate(): bool
    {
        return static::canUpdate();
    }

    public static function canPurge(): bool
    {
        return static::canUpdate();
    }

    /**
     * Nettoie les clés internes du résultat de test.
     */
    public function cleanTestOutputCriterias(array $output)
    {
        if (isset($output['_rule_process'])) {
            unset($output['_rule_process']);
        }
        return $output;
    }

    /**
     * Point d'entrée appelé depuis le hook item_add / item_update.
     * Évalue les règles pour un élément GLPI et crée les checklists correspondantes.
     *
     * @param CommonDBTM $item      L'élément GLPI concerné
     * @param int        $condition PluginChecklistRuleChecklist::ONADD|ONUPDATE
     */
    public static function processForItem(CommonDBTM $item, int $condition): void
    {
        $itemtype = $item->getType();
        $items_id = (int) $item->getID();

        if ($items_id <= 0) {
            return;
        }

        // Construit l'input pour le moteur de règles à partir des champs de l'élément
        $input = $item->fields;
        $input['_itemtype'] = $itemtype;

        $collection = new self();
        $collection->setEntity($item->fields['entities_id'] ?? 0);

        $output = $collection->processAllRules($input, [], [], [
            'condition' => $condition,
        ]);

        if (empty($output['_add_checklist_template'])) {
            return;
        }

        $templates_ids = array_unique(array_filter((array) $output['_add_checklist_template']));

        foreach ($templates_ids as $tpl_id) {
            $tpl_id = (int) $tpl_id;
            if ($tpl_id <= 0) {
                continue;
            }

            // Évite les doublons : ne ré-associe pas un template déjà présent sur l'élément
            if (
                countElementsInTable(PluginChecklistChecklist::getTable(), [
                    'itemtype'                      => $itemtype,
                    'items_id'                      => $items_id,
                    'plugin_checklist_templates_id' => $tpl_id,
                ]) > 0
            ) {
                continue;
            }

            $template = new PluginChecklistTemplate();
            if (!$template->getFromDB($tpl_id) || !$template->fields['is_active']) {
                continue;
            }

            PluginChecklistChecklist::createForItem(
                $itemtype,
                $items_id,
                $template->fields['name'],
                $tpl_id
            );
        }
    }
}

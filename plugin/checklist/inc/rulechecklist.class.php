<?php
/**
 * PluginChecklistRuleChecklist — Règle d'association automatique de templates de checklist
 *
 * Étend le moteur de règles natif de GLPI (Rule). Permet de définir des critères
 * sur un élément GLPI (Ticket, Computer…) et, en cas de correspondance, d'associer
 * automatiquement un ou plusieurs templates de checklist à cet élément.
 */

declare(strict_types=1);

class PluginChecklistRuleChecklist extends Rule
{
    public static $rightname = 'config';

    public const ONADD    = 1;
    public const ONUPDATE = 2;

    public $can_sort = true;

    public function getTitle()
    {
        return __('Checklist assignment rules', 'checklist');
    }

    public static function getIcon()
    {
        return 'fas fa-clipboard-check';
    }

    public function maybeRecursive()
    {
        return true;
    }

    public function isEntityAssign()
    {
        return true;
    }

    public function canUnrecurs()
    {
        return true;
    }

    /**
     * Conditions de déclenchement de la règle (création / modification).
     */
    public static function getConditionsArray()
    {
        return [
            static::ONADD    => __('Add'),
            static::ONUPDATE => __('Update'),
            static::ONADD | static::ONUPDATE => sprintf(
                __('%1$s / %2$s'),
                __('Add'),
                __('Update')
            ),
        ];
    }

    /**
     * Critères disponibles dans l'éditeur de règle.
     */
    public function getCriterias()
    {
        static $criterias = [];

        if (count($criterias)) {
            return $criterias;
        }

        // Type de l'élément (Ticket, Computer…)
        $criterias['_itemtype']['name']            = __('Item type');
        $criterias['_itemtype']['type']            = 'dropdown_tracking_itemtype';
        $criterias['_itemtype']['allow_condition'] = [
            Rule::PATTERN_IS,
            Rule::PATTERN_IS_NOT,
            Rule::REGEX_MATCH,
            Rule::REGEX_NOT_MATCH,
        ];

        // Nom / titre de l'élément (titre du ticket, nom de l'ordinateur…)
        $criterias['name']['name'] = __('Title / Name', 'checklist');
        $criterias['name']['type'] = 'text';

        // Catégorie ITIL (pertinent surtout pour les tickets)
        $criterias['itilcategories_id']['table'] = 'glpi_itilcategories';
        $criterias['itilcategories_id']['field'] = 'completename';
        $criterias['itilcategories_id']['name']  = __('Category');
        $criterias['itilcategories_id']['type']  = 'dropdown';

        // Contenu / description
        $criterias['content']['name'] = __('Description');
        $criterias['content']['type'] = 'text';

        return $criterias;
    }

    /**
     * Actions disponibles dans l'éditeur de règle.
     */
    public function getActions()
    {
        $actions = parent::getActions();

        $actions['_add_checklist_template']['name']          = __('Assign checklist template', 'checklist');
        $actions['_add_checklist_template']['type']          = 'dropdown';
        $actions['_add_checklist_template']['table']         = PluginChecklistTemplate::getTable();
        $actions['_add_checklist_template']['force_actions'] = ['assign'];

        return $actions;
    }

    /**
     * Exécute les actions de la règle correspondante.
     * Accumule les IDs de templates dans un tableau (plusieurs règles peuvent matcher).
     */
    public function executeActions($output, $params, array $input = [])
    {
        if (count($this->actions)) {
            foreach ($this->actions as $action) {
                if ($action->fields['field'] === '_add_checklist_template') {
                    if (!isset($output['_add_checklist_template'])) {
                        $output['_add_checklist_template'] = [];
                    }
                    $output['_add_checklist_template'][] = (int) $action->fields['value'];
                }
            }
        }

        return $output;
    }
}

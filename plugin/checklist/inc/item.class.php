<?php
/**
 * PluginChecklistItem — Tâche individuelle d'une checklist instanciée
 */

declare(strict_types=1);

class PluginChecklistItem extends CommonDBTM
{
    public static $rightname = 'config';

    public static function getTypeName($nb = 0): string
    {
        return _n('Checklist item', 'Checklist items', $nb, 'checklist');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_checklist_items';
    }

    // ─── Toggle todo ↔ done ────────────────────────────────────────────────────

    public static function toggleStatus(int $item_id): array
    {
        global $DB;

        $item = new self();
        if (!$item->getFromDB($item_id)) {
            return ['success' => false, 'error' => 'Item not found'];
        }

        $new_status = $item->fields['status'] === 'todo' ? 'done' : 'todo';
        $action     = $new_status === 'done' ? 'moved_to_done' : 'moved_to_todo';

        $update = [
            'status'   => $new_status,
            'date_mod' => date('Y-m-d H:i:s'),
        ];
        if ($new_status === 'todo') {
            $update['date_todo'] = date('Y-m-d H:i:s');
        }

        $DB->update(static::getTable(), $update, ['id' => $item_id]);

        $cl_id = (int) $item->fields['plugin_checklist_checklists_id'];

        PluginChecklistLog::addEntry(
            $cl_id,
            $item_id,
            $action,
            ['status' => $item->fields['status']],
            ['status' => $new_status]
        );

        if ($new_status === 'done') {
            self::addTicketFollowup($cl_id, $item->fields['name']);
        }

        return ['success' => true, 'new_status' => $new_status, 'item_id' => $item_id];
    }

    /**
     * Crée un suivi GLPI sur le ticket lié à la checklist, si applicable.
     */
    private static function addTicketFollowup(int $cl_id, string $item_name): void
    {
        global $DB;

        $cl_row = $DB->request([
            'FROM'  => PluginChecklistChecklist::getTable(),
            'WHERE' => ['id' => $cl_id],
        ])->current();

        if (!$cl_row || $cl_row['itemtype'] !== 'Ticket') {
            return;
        }

        $ticket_id = (int) $cl_row['items_id'];
        $cl_name   = $cl_row['name'];

        if (!class_exists('ITILFollowup') || !$ticket_id) {
            return;
        }

        $content = sprintf(
            '✅ Tâche <strong>«%s»</strong> validée via la checklist <em>«%s»</em>.',
            htmlspecialchars($item_name),
            htmlspecialchars($cl_name)
        );

        $followup = new ITILFollowup();
        $followup->add([
            'itemtype'        => 'Ticket',
            'items_id'        => $ticket_id,
            'users_id'        => Session::getLoginUserID() ?: 0,
            'content'         => $content,
            'is_private'      => 0,
            'requesttypes_id' => 0,
            'date'            => date('Y-m-d H:i:s'),
        ]);
    }

    // ─── Ordonnancement ────────────────────────────────────────────────────────

    public static function updateRanks(array $item_ids, string $column): bool
    {
        global $DB;

        $rank_field = $column === 'done' ? 'rank_done' : 'rank_todo';
        foreach ($item_ids as $rank => $id) {
            $DB->update(static::getTable(), [$rank_field => $rank], ['id' => (int) $id]);
        }
        return true;
    }

    // ─── Ajout d'une tâche exceptionnelle ──────────────────────────────────────

    public static function addExceptional(int $checklists_id, string $name, string $description = ''): int|false
    {
        global $DB;

        $max = (int) ($DB->request([
            'SELECT' => ['MAX' => 'rank_todo AS max_rank'],
            'FROM'   => static::getTable(),
            'WHERE'  => ['plugin_checklist_checklists_id' => $checklists_id],
        ])->current()['max_rank'] ?? -1);

        $result = $DB->insert(static::getTable(), [
            'plugin_checklist_checklists_id' => $checklists_id,
            'name'                           => $name,
            'description'                    => $description,
            'status'                         => 'todo',
            'rank_todo'                      => $max + 1,
            'rank_done'                      => 0,
            'is_exceptional'                 => 1,
            'date_creation'                  => date('Y-m-d H:i:s'),
            'date_mod'                       => date('Y-m-d H:i:s'),
            'date_todo'                      => date('Y-m-d H:i:s'),
            'users_id_creator'               => Session::getLoginUserID() ?: 0,
        ]);

        if (!$result) {
            return false;
        }

        $new_id = (int) $DB->insertId();

        PluginChecklistLog::addEntry($checklists_id, $new_id, 'exceptional_added', null, ['name' => $name]);

        return $new_id;
    }

    // ─── Récupération groupée ──────────────────────────────────────────────────

    public static function getForChecklist(int $checklists_id): array
    {
        global $DB;

        $result = ['todo' => [], 'done' => []];

        foreach (['todo', 'done'] as $status) {
            $rank_field = $status === 'done' ? 'rank_done' : 'rank_todo';
            $iterator   = $DB->request([
                'FROM'  => static::getTable(),
                'WHERE' => [
                    'plugin_checklist_checklists_id' => $checklists_id,
                    'status'                         => $status,
                ],
                'ORDER' => [$rank_field . ' ASC'],
            ]);
            foreach ($iterator as $row) {
                $result[$status][] = $row;
            }
        }

        return $result;
    }
}

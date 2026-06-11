<?php
/**
 * PluginChecklistLog — Historique immuable des actions sur les checklists
 */

declare(strict_types=1);

class PluginChecklistLog extends CommonDBTM
{
    public static $rightname = 'config';

    public static function getTypeName($nb = 0): string
    {
        return _n('Checklist log', 'Checklist logs', $nb, 'checklist');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_checklist_logs';
    }

    // ─── Écriture ─────────────────────────────────────────────────────────────

    public static function addEntry(
        int    $checklists_id,
        int    $items_id,
        string $action,
        mixed  $old_value = null,
        mixed  $new_value = null
    ): bool {
        global $DB;

        return (bool) $DB->insert(static::getTable(), [
            'plugin_checklist_checklists_id' => $checklists_id,
            'plugin_checklist_items_id'      => $items_id,
            'users_id'                       => Session::getLoginUserID() ?: 0,
            'action'                         => $action,
            'old_value'                      => $old_value !== null ? json_encode($old_value) : null,
            'new_value'                       => $new_value !== null ? json_encode($new_value) : null,
            'date_action'                    => date('Y-m-d H:i:s'),
        ]);
    }

    // ─── Lecture ──────────────────────────────────────────────────────────────

    public static function getForChecklist(int $checklists_id): array
    {
        global $DB;

        $entries  = [];
        $iterator = $DB->request([
            'SELECT'    => [
                static::getTable() . '.*',
                'glpi_users.name      AS user_login',
                'glpi_users.realname  AS user_realname',
                'glpi_users.firstname AS user_firstname',
            ],
            'FROM'      => static::getTable(),
            'LEFT JOIN' => [
                'glpi_users' => [
                    'ON' => [
                        static::getTable() => 'users_id',
                        'glpi_users'       => 'id',
                    ],
                ],
            ],
            'WHERE'  => ['plugin_checklist_checklists_id' => $checklists_id],
            'ORDER'  => ['date_action DESC'],
            'LIMIT'  => 200,
        ]);

        foreach ($iterator as $row) {
            $fname = trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_realname'] ?? ''));
            $entries[] = [
                'date'        => Html::convDateTime($row['date_action']),
                'user'        => $fname ?: ($row['user_login'] ?? __('Unknown')),
                'action'      => self::translateAction($row['action']),
                'new_value'   => $row['new_value'] ? json_decode((string) $row['new_value'], true) : null,
                'raw_action'  => $row['action'],
            ];
        }

        return $entries;
    }

    private static function translateAction(string $action): string
    {
        return match ($action) {
            'moved_to_done'     => __('marked as done', 'checklist'),
            'moved_to_todo'     => __('moved back to to-do', 'checklist'),
            'created'           => __('created task', 'checklist'),
            'deleted'           => __('deleted task', 'checklist'),
            'reordered'         => __('reordered tasks', 'checklist'),
            'exceptional_added' => __('added exceptional task', 'checklist'),
            'overdue_notified'  => __('overdue notification sent', 'checklist'),
            default             => $action,
        };
    }
}

<?php
/**
 * PluginChecklistCronTask — Tâche planifiée : notifications des tâches en retard
 *
 * Parcourt toutes les tâches en statut "todo" dont le template définit un délai
 * de notification (> 0). Si la tâche dépasse son délai (date_todo + délai converti
 * en heures), une notification est émise et date_notified est mis à jour pour
 * éviter le spam (re-notification uniquement si un nouveau retard est constaté).
 */

declare(strict_types=1);

class PluginChecklistCronTask extends CommonDBTM
{
    public static $rightname = 'config';

    /** Multiplicateurs pour convertir le délai vers des heures */
    public const UNIT_MULTIPLIERS = [
        'hours' => 1,
        'days'  => 24,
        'weeks' => 168, // 24 * 7
    ];

    /**
     * Libellés des unités (pour le formulaire et l'affichage).
     */
    public static function getUnitLabels(): array
    {
        return [
            'hours' => __('Hours', 'checklist'),
            'days'  => __('Days', 'checklist'),
            'weeks' => __('Weeks', 'checklist'),
        ];
    }

    /**
     * Convertit une valeur de délai + unité en nombre d'heures.
     */
    public static function delayToHours(int $value, string $unit): int
    {
        $mult = self::UNIT_MULTIPLIERS[$unit] ?? 1;
        return $value * $mult;
    }

    /**
     * Description affichée dans Configuration > Actions automatiques.
     */
    public static function cronInfo(string $name): array
    {
        return match ($name) {
            'checklistOverdue' => ['description' => __('Send notifications for overdue checklist tasks', 'checklist')],
            default            => [],
        };
    }

    /**
     * Point d'entrée du CRON (appelé par GLPI : cron + nom de tâche).
     *
     * @param CronTask|null $task
     * @return int 0 = rien à faire, 1 = effectué
     */
    public static function cronChecklistOverdue($task = null): int
    {
        global $DB;

        $it_table  = PluginChecklistItem::getTable();
        $cl_table  = PluginChecklistChecklist::getTable();
        $tpl_table = PluginChecklistTemplate::getTable();

        $iterator = $DB->request([
            'SELECT'     => [
                "$it_table.id AS item_id",
                "$it_table.name AS item_name",
                "$it_table.date_todo",
                "$it_table.date_notified",
                "$cl_table.id AS cl_id",
                "$cl_table.name AS cl_name",
                "$cl_table.itemtype",
                "$cl_table.items_id",
                "$tpl_table.notification_delay_hours AS delay_value",
                "$tpl_table.notification_delay_unit AS delay_unit",
            ],
            'FROM'       => $it_table,
            'INNER JOIN' => [
                $cl_table => [
                    'ON' => [$it_table => 'plugin_checklist_checklists_id', $cl_table => 'id'],
                ],
                $tpl_table => [
                    'ON' => [$cl_table => 'plugin_checklist_templates_id', $tpl_table => 'id'],
                ],
            ],
            'WHERE'      => [
                "$it_table.status"                    => 'todo',
                "$tpl_table.notification_delay_hours" => ['>', 0],
                'NOT'                                 => ["$it_table.date_todo" => null],
            ],
        ]);

        $now   = new DateTime();
        $total = 0;

        foreach ($iterator as $row) {
            $delay_hours = self::delayToHours((int) $row['delay_value'], $row['delay_unit'] ?: 'hours');
            if ($delay_hours <= 0) {
                continue;
            }

            $deadline = (new DateTime($row['date_todo']))->modify("+{$delay_hours} hours");

            // Pas encore en retard
            if ($now < $deadline) {
                continue;
            }

            // Déjà notifié après cette échéance → on ne re-notifie pas
            if (!empty($row['date_notified'])) {
                $notified = new DateTime($row['date_notified']);
                if ($notified >= $deadline) {
                    continue;
                }
            }

            self::notifyOverdue($row, $delay_hours);

            $DB->update($it_table, [
                'date_notified' => $now->format('Y-m-d H:i:s'),
            ], ['id' => (int) $row['item_id']]);

            if ($task instanceof CronTask) {
                $task->addVolume(1);
            }
            $total++;
        }

        return $total > 0 ? 1 : 0;
    }

    /**
     * Émet la notification pour une tâche en retard.
     * - Checklist sur un Ticket : ajoute un suivi privé (déclenche les notifications GLPI du ticket)
     * - Tous types : écrit une entrée dans l'historique de la checklist
     */
    private static function notifyOverdue(array $row, int $delay_hours): void
    {
        if ($row['itemtype'] === 'Ticket' && (int) $row['items_id'] > 0 && class_exists('ITILFollowup')) {
            $content = sprintf(
                __('⏰ Overdue checklist task: «%1$s» (checklist «%2$s») has been pending for more than %3$d hour(s).', 'checklist'),
                $row['item_name'],
                $row['cl_name'],
                $delay_hours
            );

            $fup = new ITILFollowup();
            $fup->add([
                'itemtype'   => 'Ticket',
                'items_id'   => (int) $row['items_id'],
                'content'    => $content,
                'is_private' => 1,
                'users_id'   => Session::getLoginUserID() ?: 0,
            ]);
        }

        PluginChecklistLog::addEntry(
            (int) $row['cl_id'],
            (int) $row['item_id'],
            'overdue_notified',
            null,
            ['name' => $row['item_name']]
        );
    }
}

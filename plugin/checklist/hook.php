<?php
/**
 * Plugin Checklist — hook.php
 * Fonctions d'installation, désinstallation et hooks GLPI
 */

declare(strict_types=1);

// ─── Install ──────────────────────────────────────────────────────────────────

function plugin_checklist_install(): bool
{
    global $DB;

    $tables = [

        'glpi_plugin_checklist_templates' => "
            CREATE TABLE IF NOT EXISTS `glpi_plugin_checklist_templates` (
                `id`                         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                `name`                       VARCHAR(255)  NOT NULL DEFAULT '',
                `comment`                    TEXT,
                `is_active`                  TINYINT(1)    NOT NULL DEFAULT 1,
                `notification_delay_hours`   INT           NOT NULL DEFAULT 0,
                `notification_delay_unit`    VARCHAR(10)   NOT NULL DEFAULT 'hours',
                `date_creation`              DATETIME,
                `date_mod`                   DATETIME,
                `users_id`                   INT UNSIGNED  NOT NULL DEFAULT 0,
                `entities_id`                INT UNSIGNED  NOT NULL DEFAULT 0,
                `is_recursive`               TINYINT(1)    NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `name`        (`name`),
                KEY `entities_id` (`entities_id`),
                KEY `is_active`   (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'glpi_plugin_checklist_templateitems' => "
            CREATE TABLE IF NOT EXISTS `glpi_plugin_checklist_templateitems` (
                `id`                              INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `plugin_checklist_templates_id`   INT UNSIGNED NOT NULL DEFAULT 0,
                `name`                            VARCHAR(500) NOT NULL DEFAULT '',
                `description`                     TEXT,
                `rank`                            INT          NOT NULL DEFAULT 0,
                `is_exceptional`                  TINYINT(1)   NOT NULL DEFAULT 0,
                `date_creation`                   DATETIME,
                `date_mod`                        DATETIME,
                PRIMARY KEY (`id`),
                KEY `plugin_checklist_templates_id` (`plugin_checklist_templates_id`),
                KEY `rank` (`rank`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'glpi_plugin_checklist_checklists' => "
            CREATE TABLE IF NOT EXISTS `glpi_plugin_checklist_checklists` (
                `id`                              INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name`                            VARCHAR(255) NOT NULL DEFAULT '',
                `itemtype`                        VARCHAR(100) NOT NULL DEFAULT '',
                `items_id`                        INT UNSIGNED NOT NULL DEFAULT 0,
                `plugin_checklist_templates_id`   INT UNSIGNED NOT NULL DEFAULT 0,
                `status`                          VARCHAR(50)  NOT NULL DEFAULT 'open',
                `date_creation`                   DATETIME,
                `date_mod`                        DATETIME,
                `users_id`                        INT UNSIGNED NOT NULL DEFAULT 0,
                `entities_id`                     INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `itemtype`    (`itemtype`),
                KEY `items_id`    (`items_id`),
                KEY `entities_id` (`entities_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'glpi_plugin_checklist_items' => "
            CREATE TABLE IF NOT EXISTS `glpi_plugin_checklist_items` (
                `id`                              INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `plugin_checklist_checklists_id`  INT UNSIGNED NOT NULL DEFAULT 0,
                `name`                            VARCHAR(500) NOT NULL DEFAULT '',
                `description`                     TEXT,
                `status`                          ENUM('todo','done') NOT NULL DEFAULT 'todo',
                `rank_todo`                       INT          NOT NULL DEFAULT 0,
                `rank_done`                       INT          NOT NULL DEFAULT 0,
                `is_exceptional`                  TINYINT(1)   NOT NULL DEFAULT 0,
                `date_creation`                   DATETIME,
                `date_mod`                        DATETIME,
                `date_todo`                       DATETIME,
                `date_notified`                   DATETIME,
                `users_id_creator`                INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `plugin_checklist_checklists_id` (`plugin_checklist_checklists_id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'glpi_plugin_checklist_logs' => "
            CREATE TABLE IF NOT EXISTS `glpi_plugin_checklist_logs` (
                `id`                              INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `plugin_checklist_items_id`       INT UNSIGNED NOT NULL DEFAULT 0,
                `plugin_checklist_checklists_id`  INT UNSIGNED NOT NULL DEFAULT 0,
                `users_id`                        INT UNSIGNED NOT NULL DEFAULT 0,
                `action`                          VARCHAR(100) NOT NULL DEFAULT '',
                `old_value`                       TEXT,
                `new_value`                       TEXT,
                `date_action`                     DATETIME     NOT NULL,
                PRIMARY KEY (`id`),
                KEY `plugin_checklist_checklists_id` (`plugin_checklist_checklists_id`),
                KEY `plugin_checklist_items_id`      (`plugin_checklist_items_id`),
                KEY `date_action`                    (`date_action`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach ($tables as $table => $sql) {
        if (!$DB->tableExists($table)) {
            $DB->doQueryOrDie($sql, "Error creating table {$table}");
        }
    }

    // ── Migration : ajout de la colonne unité de délai (installs existantes) ──
    if (
        $DB->tableExists('glpi_plugin_checklist_templates')
        && !$DB->fieldExists('glpi_plugin_checklist_templates', 'notification_delay_unit')
    ) {
        $DB->doQueryOrDie(
            "ALTER TABLE `glpi_plugin_checklist_templates`
             ADD COLUMN `notification_delay_unit` VARCHAR(10) NOT NULL DEFAULT 'hours'
             AFTER `notification_delay_hours`",
            'Error adding notification_delay_unit column'
        );
    }

    // ── Phase 8 : enregistrement de la tâche CRON ────────────────────────────
    CronTask::register(
        'PluginChecklistCronTask',
        'checklistOverdue',
        HOUR_TIMESTAMP, // fréquence : toutes les heures
        [
            'state'     => CronTask::STATE_WAITING,
            'mode'      => CronTask::MODE_INTERNAL,
            'comment'   => 'Notifications des tâches de checklist en retard',
        ]
    );

    return true;
}

// ─── Uninstall ────────────────────────────────────────────────────────────────

function plugin_checklist_uninstall(): bool
{
    global $DB;

    $tables = [
        'glpi_plugin_checklist_logs',
        'glpi_plugin_checklist_items',
        'glpi_plugin_checklist_checklists',
        'glpi_plugin_checklist_templateitems',
        'glpi_plugin_checklist_templates',
    ];

    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            $DB->doQueryOrDie("DROP TABLE IF EXISTS `{$table}`");
        }
    }

    return true;
}

// ─── Hooks item_add / item_update — moteur de règles (Phase 7) ───────────────

function plugin_checklist_item_add($item): void
{
    if ($item instanceof CommonDBTM) {
        PluginChecklistRuleChecklistCollection::processForItem(
            $item,
            PluginChecklistRuleChecklist::ONADD
        );
    }
}

function plugin_checklist_item_update($item): void
{
    if ($item instanceof CommonDBTM) {
        PluginChecklistRuleChecklistCollection::processForItem(
            $item,
            PluginChecklistRuleChecklist::ONUPDATE
        );
    }
}

// ─── Hook timeline_actions — bouton dans le menu d'action du ticket ───────────

function plugin_checklist_timeline_actions(array $params): void
{
    $item = $params['item'] ?? null;
    if (!($item instanceof CommonITILObject)) {
        return;
    }

    $items_id = (int) $item->getID();
    $itemtype = $item->getType();
    $ajax_url = Plugin::getWebDir('checklist') . '/ajax';

    static $modal_injected = false;
    if (!$modal_injected) {
        $modal_injected = true;
        plugin_checklist_inject_validate_assets($ajax_url);
    }

    echo '<li>';
    echo '<button type="button" class="btn btn-success"';
    echo ' data-clv-id="' . $items_id . '" data-clv-type="' . htmlspecialchars($itemtype) . '">';
    echo '<i class="ti ti-checks me-1"></i>';
    echo htmlspecialchars(__('Validate a checklist task', 'checklist'));
    echo '</button>';
    echo '</li>';
}

function plugin_checklist_inject_validate_assets(string $ajax_url): void
{
    $csrf    = Session::getNewCSRFToken(true);
    $title   = addslashes(__('Validate checklist tasks', 'checklist'));
    $btn_lbl = addslashes(__('Validate selected', 'checklist'));
    $cancel  = addslashes(__('Cancel'));
    $loading = addslashes(__('Loading...'));
    $empty   = addslashes(__('All tasks are already done!', 'checklist'));
    $errl    = addslashes(__('Loading error.', 'checklist'));
    $errs    = addslashes(__('An error occurred.', 'checklist'));

    // Tout le JS est dans une IIFE exécutée immédiatement.
    // Le listener click est enregistré tout de suite (pas dans DOMContentLoaded)
    // pour fonctionner même quand GLPI charge l'onglet dynamiquement.
    // Le modal est créé en lazy (au premier clic) et attaché à document.body.
    echo '<script>
(function(){
var _url=' . json_encode($ajax_url) . ';
var _csrf=' . json_encode($csrf) . ';
var _mHtml=\'<div class="modal fade" id="clvModal" tabindex="-1" aria-hidden="true">'
    . '<div class="modal-dialog modal-dialog-centered">'
    . '<div class="modal-content">'
    . '<div class="modal-header">'
    . '<h5 class="modal-title"><i class="ti ti-checks me-2"></i>' . $title . '</h5>'
    . '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>'
    . '</div>'
    . '<div class="modal-body" id="clvBody"></div>'
    . '<div class="modal-footer">'
    . '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . $cancel . '</button>'
    . '<button type="button" class="btn btn-success" id="clvSubmit"><i class="ti ti-check me-1"></i>' . $btn_lbl . '</button>'
    . '</div></div></div></div>\';

function clvEnsureModal(){
    if(document.getElementById("clvModal")) return;
    var w=document.createElement("div");
    w.innerHTML=_mHtml;
    document.body.appendChild(w.firstElementChild);
}

function clvFetch(ep,fd){
    return fetch(_url+"/"+ep,{method:"POST",body:fd,
        headers:{"X-Glpi-Csrf-Token":_csrf,"X-Requested-With":"XMLHttpRequest"}});
}

function clvEsc(s){
    return String(s==null?"":s)
        .replace(/&/g,"&amp;").replace(/</g,"&lt;")
        .replace(/>/g,"&gt;").replace(/"/g,"&quot;");
}

window.clOpenValidateModal=function(id,type){
    clvEnsureModal();
    var el=document.getElementById("clvModal");
    var modal=bootstrap.Modal.getOrCreateInstance(el);
    var body=document.getElementById("clvBody");
    body.innerHTML=\'<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> ' . $loading . '</div>\';
    modal.show();

    var fd=new FormData(); fd.append("itemtype",type); fd.append("items_id",id);
    clvFetch("get_todo_items.php",fd)
    .then(function(r){return r.json();})
    .then(function(d){
        if(!d.success||!d.items||!d.items.length){
            body.innerHTML=\'<div class="text-center py-4 text-muted"><i class="ti ti-circle-check fs-1 text-success d-block mb-2"></i>' . $empty . '</div>\';
            return;
        }
        var h=\'<div class="list-group">\';
        d.items.forEach(function(it){
            h+=\'<label class="list-group-item list-group-item-action d-flex align-items-start gap-2 py-2" style="cursor:pointer">\';
            h+=\'<input type="checkbox" class="clvcb form-check-input mt-1" value="\'+it.id+\'">\';
            h+=\'<div><div class="fw-semibold">\'+clvEsc(it.name)+\'</div>\';
            if(it.cl_name) h+=\'<small class="text-muted"><i class="ti ti-clipboard-list me-1"></i>\'+clvEsc(it.cl_name)+\'</small>\';
            h+=\'</div></label>\';
        });
        h+=\'</div>\';
        body.innerHTML=h;
    })
    .catch(function(){body.innerHTML=\'<div class="text-danger text-center py-3">' . $errl . '</div>\';});

    document.getElementById("clvSubmit").onclick=function(){
        var cbs=[].slice.call(document.querySelectorAll(".clvcb:checked"));
        if(!cbs.length) return;
        var btn=this; btn.disabled=true;
        Promise.all(cbs.map(function(c){
            var fd=new FormData(); fd.append("item_id",c.value);
            return clvFetch("move_item.php",fd).then(function(r){return r.json();});
        })).then(function(){
            bootstrap.Modal.getInstance(document.getElementById("clvModal")).hide();
            window.location.reload();
        }).catch(function(){btn.disabled=false;alert(\'' . $errs . '\');});
    };
};

// Listener immédiat — fonctionne même si le DOM est déjà chargé (tabs GLPI dynamiques)
document.addEventListener("click",function(e){
    var b=e.target.closest("[data-clv-id]");
    if(b) clOpenValidateModal(b.dataset.clvId,b.dataset.clvType);
});
})();
</script>';
}

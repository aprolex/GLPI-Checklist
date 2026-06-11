<?php
/**
 * PluginChecklistTemplate — Modèle de checklist réutilisable
 */

declare(strict_types=1);

class PluginChecklistTemplate extends CommonDBTM
{
    public static $rightname = 'config';

    public static function getTypeName($nb = 0): string
    {
        return _n('Checklist template', 'Checklist templates', $nb, 'checklist');
    }

    public static function getIcon(): string
    {
        return 'fas fa-tasks';
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_checklist_templates';
    }

    public static function getMenuName(): string
    {
        return __('Checklist templates', 'checklist');
    }

    public static function canView(): bool
    {
        return (bool) Session::haveRight('config', READ);
    }

    public static function canCreate(): bool
    {
        return (bool) Session::haveRight('config', UPDATE);
    }

    public static function getMenuContent(): array|false
    {
        if (!static::canView()) {
            return false;
        }

        $web_dir = Plugin::getWebDir('checklist');
        $menu = [
            'title' => static::getMenuName(),
            'page'  => $web_dir . '/front/template.php',
            'icon'  => static::getIcon(),
            'links' => [
                'search' => $web_dir . '/front/template.php',
            ],
        ];

        if (static::canCreate()) {
            $menu['links']['add'] = $web_dir . '/front/template.form.php';
        }

        return $menu;
    }

    // ─── Récupération ─────────────────────────────────────────────────────────

    public static function getAll(bool $active_only = true): array
    {
        global $DB;

        $where    = $active_only ? ['is_active' => 1] : [];
        $items    = [];
        $iterator = $DB->request(['FROM' => static::getTable(), 'WHERE' => $where, 'ORDER' => ['name ASC']]);

        foreach ($iterator as $row) {
            $items[] = $row;
        }

        return $items;
    }

    // ─── Affichage liste ──────────────────────────────────────────────────────

    public static function showList(): void
    {
        $templates = self::getAll(false);
        $web_dir   = Plugin::getWebDir('checklist');

        echo '<div class="container-fluid">';
        echo '<div class="d-flex justify-content-between align-items-center mb-3">';
        echo '<h2>' . __('Checklist templates', 'checklist') . '</h2>';
        echo '<a class="btn btn-primary" href="' . $web_dir . '/front/template.form.php">';
        echo '<i class="fas fa-plus me-1"></i>' . __('Add a template', 'checklist') . '</a>';
        echo '</div>';

        if (empty($templates)) {
            echo '<div class="alert alert-info">' . __('No template yet. Create one!', 'checklist') . '</div>';
            echo '</div>';
            return;
        }

        echo '<table class="table table-striped table-hover">';
        echo '<thead class="table-dark"><tr>';
        echo '<th>' . __('Name') . '</th>';
        echo '<th>' . __('Active') . '</th>';
        echo '<th>' . __('Notification delay', 'checklist') . '</th>';
        echo '<th>' . __('Tasks', 'checklist') . '</th>';
        echo '<th>' . __('Actions') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($templates as $t) {
            $nb  = countElementsInTable(PluginChecklistTemplateItem::getTable(), ['plugin_checklist_templates_id' => $t['id']]);
            $url = $web_dir . '/front/template.form.php?id=' . $t['id'];

            echo '<tr>';
            echo '<td><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($t['name']) . '</a></td>';
            echo '<td>' . ($t['is_active'] ? '<span class="badge bg-success">' . __('Yes') . '</span>' : '<span class="badge bg-secondary">' . __('No') . '</span>') . '</td>';
            if ($t['notification_delay_hours'] > 0) {
                $unit_labels = PluginChecklistCronTask::getUnitLabels();
                $unit_lbl    = $unit_labels[$t['notification_delay_unit'] ?? 'hours'] ?? $t['notification_delay_unit'];
                echo '<td>' . (int) $t['notification_delay_hours'] . ' ' . htmlspecialchars(strtolower($unit_lbl)) . '</td>';
            } else {
                echo '<td>—</td>';
            }
            echo '<td>' . $nb . '</td>';
            echo '<td><a class="btn btn-sm btn-outline-primary" href="' . htmlspecialchars($url) . '"><i class="fas fa-edit"></i></a></td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';
    }

    // ─── Formulaire CRUD ──────────────────────────────────────────────────────

    public function showForm($ID, array $options = []): bool
    {
        $this->initForm($ID, $options);
        $web_dir = Plugin::getWebDir('checklist');
        $is_new  = $ID <= 0;

        echo '<form method="POST" action="' . $web_dir . '/front/template.form.php">';
        echo Html::hidden('id', ['value' => $ID]);
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);

        // ── Champs principaux ──────────────────────────────────────────────────
        echo '<div class="card mb-3">';
        echo '<div class="card-header"><strong>' . ($is_new ? __('New template', 'checklist') : htmlspecialchars($this->fields['name'])) . '</strong></div>';
        echo '<div class="card-body row g-3">';

        echo '<div class="col-md-5">';
        echo '<label class="form-label">' . __('Name') . ' <span class="text-danger">*</span></label>';
        echo '<input type="text" class="form-control" name="name" required value="' . htmlspecialchars($this->fields['name'] ?? '') . '">';
        echo '</div>';

        echo '<div class="col-md-3">';
        echo '<label class="form-label">' . __('Active') . '</label>';
        echo '<select class="form-select" name="is_active">';
        echo '<option value="1"' . (($this->fields['is_active'] ?? 1) == 1 ? ' selected' : '') . '>' . __('Yes') . '</option>';
        echo '<option value="0"' . (($this->fields['is_active'] ?? 1) == 0 ? ' selected' : '') . '>' . __('No') . '</option>';
        echo '</select>';
        echo '</div>';

        // ── Délai de notification : valeur + unité (heures / jours / semaines) ──
        echo '<div class="col-md-4">';
        echo '<label class="form-label">' . __('Notification delay', 'checklist') . '</label>';
        echo '<div class="input-group">';
        echo '<input type="number" class="form-control" name="notification_delay_hours" min="0" value="' . (int) ($this->fields['notification_delay_hours'] ?? 0) . '">';
        echo '<select class="form-select" name="notification_delay_unit" style="max-width:130px">';
        $cur_unit = $this->fields['notification_delay_unit'] ?? 'hours';
        foreach (PluginChecklistCronTask::getUnitLabels() as $uval => $ulabel) {
            echo '<option value="' . $uval . '"' . ($cur_unit === $uval ? ' selected' : '') . '>' . htmlspecialchars($ulabel) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '<small class="text-muted">' . __('0 = disabled', 'checklist') . '</small>';
        echo '</div>';

        // ── Entité + récursivité ───────────────────────────────────────────────
        echo '<div class="col-md-6">';
        echo '<label class="form-label">' . Entity::getTypeName(1) . '</label>';
        Entity::dropdown([
            'name'   => 'entities_id',
            'value'  => $this->fields['entities_id'] ?? Session::getActiveEntity(),
            'entity' => $_SESSION['glpiactiveentities'] ?? [],
        ]);
        echo '</div>';

        echo '<div class="col-md-6 d-flex align-items-center">';
        echo '<div class="form-check mt-4">';
        echo '<input class="form-check-input" type="checkbox" name="is_recursive" value="1" id="tpl_recursive"' . (($this->fields['is_recursive'] ?? 0) ? ' checked' : '') . '>';
        echo '<label class="form-check-label" for="tpl_recursive">' . __('Child entities') . '</label>';
        echo '</div>';
        echo '</div>';

        echo '<div class="col-12">';
        echo '<label class="form-label">' . __('Comment') . '</label>';
        echo '<textarea class="form-control" name="comment" rows="2">' . htmlspecialchars($this->fields['comment'] ?? '') . '</textarea>';
        echo '</div>';

        echo '</div></div>'; // card-body / card

        // ── Boutons principaux ─────────────────────────────────────────────────
        echo '<div class="d-flex gap-2 mb-4">';
        if ($is_new) {
            echo '<button type="submit" name="add" class="btn btn-primary"><i class="fas fa-save me-1"></i>' . __('Add') . '</button>';
        } else {
            echo '<button type="submit" name="update" class="btn btn-primary"><i class="fas fa-save me-1"></i>' . __('Save') . '</button>';
            echo '<button type="submit" name="purge" class="btn btn-danger ms-auto" onclick="return confirm(\'' . __('Delete this template?') . '\')">';
            echo '<i class="fas fa-trash me-1"></i>' . __('Delete') . '</button>';
        }
        echo '</div>';
        echo '</form>';

        // ── Tâches du template ─────────────────────────────────────────────────
        if (!$is_new) {
            $items = PluginChecklistTemplateItem::getForTemplate($ID);
            echo '<div class="card">';
            echo '<div class="card-header d-flex justify-content-between"><strong>' . __('Tasks', 'checklist') . '</strong>';
            echo '<span class="badge bg-secondary">' . count($items) . '</span></div>';
            echo '<div class="card-body p-0">';

            if (!empty($items)) {
                $ajax_reorder = $web_dir . '/ajax/reorder_template_items.php';
                echo '<table class="table table-sm table-hover mb-0">';
                echo '<thead><tr>';
                echo '<th style="width:30px"></th><th style="width:40px">#</th>';
                echo '<th>' . __('Name') . '</th><th>' . __('Description') . '</th>';
                echo '<th>' . __('Exceptional', 'checklist') . '</th><th style="width:50px"></th>';
                echo '</tr></thead>';
                echo '<tbody id="cl-tpl-sortable" data-template-id="' . $ID . '" data-ajax-url="' . htmlspecialchars($ajax_reorder) . '">';

                foreach ($items as $it) {
                    echo '<tr data-id="' . (int) $it['id'] . '">';
                    echo '<td class="text-center text-muted cl-tpl-handle" style="cursor:grab"><i class="fas fa-grip-vertical"></i></td>';
                    echo '<td class="cl-tpl-rank text-muted">' . (int) $it['rank'] . '</td>';
                    echo '<td>' . htmlspecialchars($it['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($it['description'] ?? '') . '</td>';
                    echo '<td>' . ($it['is_exceptional'] ? '<span class="badge bg-warning text-dark">⚠ EXC</span>' : '') . '</td>';
                    echo '<td>';
                    echo '<form method="POST" action="' . $web_dir . '/front/template.form.php" class="d-inline">';
                    echo Html::hidden('id', ['value' => $it['id']]);
                    echo Html::hidden('template_id', ['value' => $ID]);
                    echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
                    echo '<button type="submit" name="delete_item" class="btn btn-sm btn-outline-danger"';
                    echo ' onclick="return confirm(\'' . __('Delete?') . '\')"><i class="fas fa-times"></i></button>';
                    echo '</form>';
                    echo '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';

                // SortableJS + sauvegarde AJAX de l'ordre
                self::renderReorderScript();
            } else {
                echo '<p class="text-muted text-center py-3 mb-0">' . __('No task yet.', 'checklist') . '</p>';
            }

            echo '</div>'; // card-body

            // Formulaire ajout de tâche
            echo '<div class="card-footer">';
            echo '<form method="POST" action="' . $web_dir . '/front/template.form.php" class="row g-2 align-items-end">';
            echo Html::hidden('template_id', ['value' => $ID]);
            echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);

            echo '<div class="col-md-4"><label class="form-label">' . __('Task name', 'checklist') . ' *</label>';
            echo '<input type="text" class="form-control form-control-sm" name="name" required></div>';

            echo '<div class="col-md-4"><label class="form-label">' . __('Description') . '</label>';
            echo '<input type="text" class="form-control form-control-sm" name="description"></div>';

            echo '<div class="col-md-2"><label class="form-label">' . __('Rank') . '</label>';
            echo '<input type="number" class="form-control form-control-sm" name="rank" value="' . (count($items) + 1) . '" min="0"></div>';

            echo '<div class="col-md-1"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="is_exceptional" value="1" id="is_exc">';
            echo '<label class="form-check-label" for="is_exc">EXC</label></div></div>';

            echo '<div class="col-md-1"><button type="submit" name="add_item" class="btn btn-success btn-sm w-100"><i class="fas fa-plus"></i></button></div>';

            echo '</form>';
            echo '</div>'; // card-footer
            echo '</div>'; // card
        }

        return true;
    }

    /**
     * SortableJS + sauvegarde AJAX de l'ordre des tâches du template.
     */
    private static function renderReorderScript(): void
    {
        $csrf = Session::getNewCSRFToken(true);

        echo '<script>
        (function(){
            var tbody=document.getElementById("cl-tpl-sortable");
            if(!tbody||tbody._init) return; tbody._init=true;
            var csrf=' . json_encode($csrf) . ';

            function loadSortable(cb){
                if(typeof Sortable!=="undefined"){cb();return;}
                var s=document.createElement("script");
                s.src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js";
                s.onload=cb; document.head.appendChild(s);
            }

            loadSortable(function(){
                Sortable.create(tbody,{
                    handle:".cl-tpl-handle",
                    animation:150,
                    ghostClass:"table-active",
                    onEnd:function(){
                        var ids=[].slice.call(tbody.querySelectorAll("tr")).map(function(r){return r.dataset.id;});
                        // Met à jour l affichage des rangs immédiatement
                        [].slice.call(tbody.querySelectorAll("tr")).forEach(function(r,i){
                            var c=r.querySelector(".cl-tpl-rank"); if(c) c.textContent=i+1;
                        });
                        var fd=new FormData();
                        fd.append("template_id",tbody.dataset.templateId);
                        ids.forEach(function(id){fd.append("ids[]",id);});
                        fetch(tbody.dataset.ajaxUrl,{
                            method:"POST",body:fd,
                            headers:{"X-Glpi-Csrf-Token":csrf,"X-Requested-With":"XMLHttpRequest"}
                        });
                    }
                });
            });
        })();
        </script>';
    }
}

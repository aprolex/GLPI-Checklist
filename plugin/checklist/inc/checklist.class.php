<?php
/**
 * PluginChecklistChecklist — Checklist instanciée + intégration onglet GLPI
 */

declare(strict_types=1);

class PluginChecklistChecklist extends CommonDBTM
{
    public static $rightname = 'config';

    public static function getIcon(): string
    {
        return 'fas fa-clipboard-check';
    }

    public static function getTypeName($nb = 0): string
    {
        return _n('Checklist', 'Checklists', $nb, 'checklist');
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_checklist_checklists';
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  INTÉGRATION ONGLET GLPI
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Nom de l'onglet — NON static en GLPI 11 (instance method dans CommonGLPI)
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $count = countElementsInTable(static::getTable(), [
            'itemtype' => $item->getType(),
            'items_id' => $item->getID(),
        ]);
        return self::createTabEntry(__('Checklists', 'checklist'), $count, null, self::getIcon());
    }

    /**
     * Contenu de l'onglet — static en GLPI 11 (static dans CommonGLPI)
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        self::showForItem($item);
        return true;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  ASSETS INLINE — GLPI 11 ne sert pas les CSS/JS plugins via le routeur
    // ═══════════════════════════════════════════════════════════════════════════

    private static function injectAssets(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        // ── CSS ───────────────────────────────────────────────────────────────
        echo '<style id="cl-plugin-styles">
        .cl-wrap{padding:.5rem 0}
        .cl-topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;padding-bottom:.75rem;border-bottom:2px solid #e2e8f0}
        .cl-topbar-left{display:flex;align-items:center;gap:.75rem}
        .cl-topbar-title{font-size:1rem;font-weight:700;color:#1e293b}
        .cl-topbar-sub{font-size:.78rem;color:#94a3b8}
        .cl-empty{text-align:center;padding:3rem 1rem;color:#94a3b8;border:2px dashed #e2e8f0;border-radius:12px;margin-top:.5rem}
        .cl-empty i{font-size:2rem;display:block;margin-bottom:.5rem}
        /* Cards */
        .cl-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:.75rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05);transition:box-shadow .2s}
        .cl-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.09)}
        .cl-card-hdr{display:flex;align-items:center;justify-content:space-between;padding:.7rem 1rem;background:#f8fafc;cursor:pointer;user-select:none;border-bottom:1px solid transparent}
        .cl-card-hdr:hover{background:#f1f5f9}
        .cl-card-open>.cl-card-hdr{border-bottom-color:#e2e8f0}
        .cl-card-hdr-left{display:flex;align-items:center;gap:.65rem;overflow:hidden;min-width:0}
        .cl-card-icon{color:#6366f1;flex-shrink:0}
        .cl-card-title{font-weight:600;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .cl-card-count{font-size:.75rem;color:#94a3b8;flex-shrink:0}
        .cl-card-hdr-right{display:flex;align-items:center;gap:.65rem;flex-shrink:0}
        .cl-chevron{transition:transform .25s;color:#cbd5e1;font-size:.8rem}
        .cl-chevron.open{transform:rotate(180deg)}
        /* Kanban */
        .cl-kanban-wrap{padding:1rem;background:#fafafa}
        .cl-board{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:.75rem}
        .cl-col{border-radius:10px;padding:.75rem}
        .cl-col-todo{background:#eff6ff;border:1px solid #dbeafe}
        .cl-col-done{background:#f0fdf4;border:1px solid #dcfce7}
        .cl-col-hdr{display:flex;justify-content:space-between;align-items:center;margin-bottom:.65rem;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em}
        .cl-col-todo .cl-col-hdr{color:#1d4ed8}
        .cl-col-done .cl-col-hdr{color:#15803d}
        /* Items */
        .cl-sort{list-style:none;padding:0;margin:0;min-height:60px}
        .cl-item{background:#fff;border:1px solid #e2e8f0;border-radius:7px;padding:.45rem .7rem;margin-bottom:.35rem;cursor:pointer;transition:all .15s;font-size:.85rem}
        .cl-item:hover{border-color:#6366f1;box-shadow:0 2px 8px rgba(99,102,241,.15);transform:translateY(-1px)}
        .cl-item-row{display:flex;align-items:center;gap:.5rem}
        .cl-item-name{flex:1}
        .cl-item-desc{display:block;color:#94a3b8;font-size:.72rem;margin-top:2px;padding-left:1.1rem}
        .cl-col-done .cl-item{border-color:#dcfce7;background:#fafffe}
        .cl-col-done .cl-item .cl-item-name{color:#94a3b8;text-decoration:line-through}
        .cl-item.cl-exc{border-left:3px solid #f59e0b!important}
        .cl-badge-exc{font-size:.6rem;font-weight:700;background:#fef3c7;color:#92400e;padding:1px 5px;border-radius:3px;border:1px solid #f59e0b;white-space:nowrap;flex-shrink:0}
        .cl-drag-hdl{color:#cbd5e1;font-size:.65rem;cursor:grab;flex-shrink:0}
        .cl-drag-hdl:active{cursor:grabbing}
        .cl-item.sortable-ghost{opacity:.3;background:#e0e7ff!important}
        .cl-item.sortable-chosen{box-shadow:0 8px 24px rgba(0,0,0,.15);z-index:99}
        /* Toolbar */
        .cl-toolbar{display:flex;gap:.5rem;flex-wrap:wrap;padding-top:.65rem;border-top:1px solid #e2e8f0;margin-top:.25rem}
        /* History */
        .cl-hist-wrap{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:.65rem .75rem;margin-top:.65rem;max-height:200px;overflow-y:auto}
        .cl-hist-table{width:100%;border-collapse:collapse;font-size:.77rem}
        .cl-hist-table td{padding:.2rem .4rem;border-bottom:1px solid #f1f5f9;vertical-align:top}
        .cl-hist-table tr:last-child td{border:none}
        .cl-hist-date{white-space:nowrap;color:#94a3b8}
        .cl-hist-user{color:#475569;font-weight:500}
        /* Footer link */
        .cl-footer-link{display:block;text-align:right;margin-top:.75rem;font-size:.75rem;color:#94a3b8;text-decoration:none}
        .cl-footer-link:hover{color:#6366f1;text-decoration:underline}
        /* Searchable template picker */
        .cl-tpl-picker{position:relative}
        .cl-tpl-toggle{cursor:pointer;position:relative}
        .cl-tpl-toggle.cl-open{border-color:#6366f1;box-shadow:0 0 0 .15rem rgba(99,102,241,.18)}
        .cl-tpl-menu{display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;z-index:1080;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);overflow:hidden}
        .cl-tpl-menu.cl-open{display:block;animation:clSlideIn .15s ease-out}
        .cl-tpl-search-wrap{position:relative;padding:.5rem;border-bottom:1px solid #f1f5f9}
        .cl-tpl-search-ico{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#cbd5e1;font-size:.8rem;pointer-events:none}
        .cl-tpl-filter{padding-left:1.9rem!important}
        .cl-tpl-list{list-style:none;margin:0;padding:.25rem;max-height:220px;overflow-y:auto}
        .cl-tpl-opt{padding:.45rem .6rem;border-radius:6px;cursor:pointer;font-size:.87rem;display:flex;align-items:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .cl-tpl-opt:hover,.cl-tpl-opt.cl-hl{background:#eef2ff}
        .cl-tpl-opt.cl-tpl-active{background:#e0e7ff;font-weight:600}
        .cl-tpl-noresult{padding:.75rem;text-align:center;color:#94a3b8;font-size:.82rem}
        /* Animations */
        .cl-card-new{animation:clSlideIn .35s ease-out}
        .cl-item-new{animation:clSlideIn .25s ease-out}
        @keyframes clSlideIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
        @media(max-width:640px){.cl-board{grid-template-columns:1fr}}
        </style>';

        // ── JS (SortableJS CDN + logique plugin) ──────────────────────────────
        $csrf_token = Session::getNewCSRFToken(true); // standalone = true : token persistant
        echo '<script id="cl-plugin-scripts">
        // GLPI 11 : CSRF for fetch goes via X-Glpi-Csrf-Token header
        var clCsrfToken=' . json_encode($csrf_token) . ';
        function clFetch(url, fd) {
            return fetch(url, {
                method: "POST",
                body: fd,
                headers: {
                    "X-Glpi-Csrf-Token": clCsrfToken,
                    "X-Requested-With": "XMLHttpRequest"
                }
            });
        }
        (function(){
            if(typeof Sortable!=="undefined") return;
            var s=document.createElement("script");
            s.src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js";
            document.head.appendChild(s);
        })();

        function clToggleKanban(id){
            var k=document.getElementById("cl-kanban-"+id);
            var c=document.getElementById("cl-chev-"+id);
            var card=document.getElementById("cl-card-"+id);
            if(!k) return;
            var open=k.style.display!=="none";
            k.style.display=open?"none":"block";
            if(c) c.classList.toggle("open",!open);
            if(card) card.classList.toggle("cl-card-open",!open);
            if(!open) setTimeout(function(){clInitSort(id);},80);
        }

        function clInitSort(id){
            if(typeof Sortable==="undefined") return;
            var kanban=document.querySelector(".cl-kanban-wrap[data-cl-id=\""+id+"\"]");
            var base=kanban?kanban.dataset.ajaxUrl:"";
            ["todo","done"].forEach(function(col){
                var el=document.getElementById("cl-"+col+"-"+id);
                if(!el||el._si) return; el._si=true;
                Sortable.create(el,{
                    group:"cl-"+id, handle:".cl-drag-hdl",
                    animation:150, ghostClass:"sortable-ghost", chosenClass:"sortable-chosen",
                    onEnd:function(e){
                        var from=e.from, to=e.to, itemId=parseInt(e.item.dataset.id);
                        if(from!==to) clToggleItem(itemId,base+"/move_item.php",false);
                        var ids=[].slice.call(to.querySelectorAll(".cl-item")).map(function(x){return x.dataset.id;});
                        clSaveOrder(to.dataset.clId,to.dataset.status,ids,base+"/reorder_items.php");
                        if(from!==to){
                            var fids=[].slice.call(from.querySelectorAll(".cl-item")).map(function(x){return x.dataset.id;});
                            clSaveOrder(from.dataset.clId,from.dataset.status,fids,base+"/reorder_items.php");
                        }
                        clUpdatePct(id);
                    }
                });
            });
        }

        function clToggleItem(itemId,url,move){
            var li=document.getElementById("cl-item-"+itemId);
            if(!li||li.dataset.lk) return;
            li.dataset.lk="1"; li.style.opacity=".4";
            var fd=new FormData(); fd.append("item_id",itemId);
            clFetch(url,fd)
            .then(function(r){return r.json();})
            .then(function(d){
                if(d.success){
                    var p=li.parentElement, cid=p.dataset.clId;
                    var t=document.getElementById("cl-"+d.new_status+"-"+cid);
                    if(t) t.appendChild(li);
                    clUpdatePct(cid);
                }
                delete li.dataset.lk; li.style.opacity="1";
            }).catch(function(){delete li.dataset.lk;li.style.opacity="1";});
        }

        function clSaveOrder(clId,col,ids,url){
            var fd=new FormData();
            fd.append("cl_id",clId); fd.append("column",col);
            ids.forEach(function(id){fd.append("ids[]",id);});
            clFetch(url,fd).catch(function(){});
        }

        function clUpdatePct(clId){
            var doneList=document.getElementById("cl-done-"+clId);
            var todoList=document.getElementById("cl-todo-"+clId);
            var done=doneList?doneList.querySelectorAll(".cl-item").length:0;
            var todo=todoList?todoList.querySelectorAll(".cl-item").length:0;
            var total=done+todo, pct=total>0?Math.round(done/total*100):0;
            var card=document.getElementById("cl-card-"+clId);
            if(!card) return;
            var ct=card.querySelector(".cl-card-count"),
                bar=card.querySelector(".progress-bar"),
                bdg=card.querySelector(".cl-card-hdr-right .badge");
            if(ct) ct.textContent=done+"/"+total;
            if(bar){bar.style.width=pct+"%";bar.className="progress-bar "+(pct===100?"bg-success":"bg-primary");}
            if(bdg){bdg.textContent=pct+"%";bdg.className="badge "+(pct===100?"bg-success":"bg-primary");}
            // Mise à jour des badges numériques dans les en-têtes de colonnes
            if(todoList){
                var todoBadge=todoList.closest(".cl-col");
                todoBadge=todoBadge?todoBadge.querySelector(".cl-col-hdr .badge"):null;
                if(todoBadge) todoBadge.textContent=todo;
            }
            if(doneList){
                var doneBadge=doneList.closest(".cl-col");
                doneBadge=doneBadge?doneBadge.querySelector(".cl-col-hdr .badge"):null;
                if(doneBadge) doneBadge.textContent=done;
            }
        }

        function clDeleteChecklist(id,url){
            if(!confirm("Supprimer cette checklist et toutes ses taches ?")) return;
            var fd=new FormData(); fd.append("action","delete"); fd.append("cl_id",id);
            clFetch(url,fd).then(function(r){return r.json();}).then(function(d){
                if(d.success){
                    var c=document.getElementById("cl-card-"+id);
                    if(c) c.remove();
                    clUpdateGlobalCount();
                } else alert("Erreur lors de la suppression.");
            });
        }

        // Échappement HTML complet (&, <, >, ") pour les valeurs insérées via JS
        function clEsc(s){
            return String(s==null?"":s)
                .replace(/&/g,"&amp;").replace(/</g,"&lt;")
                .replace(/>/g,"&gt;").replace(/"/g,"&quot;");
        }

        function clBuildItemHtml(id, name, desc, exc, moveUrl){
            var cls="cl-item cl-item-new"+(exc?" cl-exc":"");
            var oc="clToggleItem("+id+",\""+moveUrl+"\")";
            var h="<li class=\""+cls+"\" id=\"cl-item-"+id+"\" data-id=\""+id+"\" onclick=\""+oc+"\">";
            h+="<div class=\"cl-item-row\">";
            h+="<span class=\"cl-drag-hdl\"><i class=\"fas fa-grip-vertical\"></i></span>";
            if(exc) h+="<span class=\"cl-badge-exc\">⚠ EXC</span>";
            h+="<span class=\"cl-item-name\">"+clEsc(name)+"</span>";
            h+="</div>";
            if(desc) h+="<span class=\"cl-item-desc\">"+clEsc(desc)+"</span>";
            h+="</li>";
            return h;
        }

        function clShowAddExc(id,url,moveUrl){
            var name=prompt("Nom de la tache exceptionnelle :","");
            if(!name||!name.trim()) return;
            var desc=prompt("Description (facultatif) :","") || "";
            var fd=new FormData();
            fd.append("cl_id",id); fd.append("name",name.trim()); fd.append("description",desc);
            clFetch(url,fd).then(function(r){return r.json();}).then(function(d){
                if(d.success){
                    var todoList=document.getElementById("cl-todo-"+id);
                    if(todoList){
                        todoList.insertAdjacentHTML("beforeend", clBuildItemHtml(d.id, d.name, d.description, true, moveUrl));
                    }
                    clUpdatePct(id);
                } else alert("Erreur : "+(d.error||"Inconnue"));
            });
        }

        function clUpdateGlobalCount(){
            var sub=document.querySelector(".cl-topbar-sub");
            if(!sub) return;
            var n=document.querySelectorAll("#cl-list .cl-card").length;
            sub.textContent=n+" checklist(s)";
            var emp=document.querySelector(".cl-empty");
            var list=document.getElementById("cl-list");
            if(n===0){
                if(list) list.remove();
                if(!emp){
                    var wrap=document.getElementById("cl-container");
                    if(wrap){
                        var d=document.createElement("div");
                        d.className="cl-empty";
                        d.innerHTML="<i class=\"fas fa-clipboard\"></i>No checklist yet. Click the button above to start.";
                        var footer=wrap.querySelector(".cl-footer-link");
                        wrap.insertBefore(d, footer);
                    }
                }
            } else {
                if(emp) emp.remove();
            }
        }

        function clBuildCardHtml(id, name, total, ajaxUrl){
            var e=clEsc(name);
            var h="<div class=\"cl-card cl-card-new\" id=\"cl-card-"+id+"\">";
            h+="<div class=\"cl-card-hdr\" onclick=\"clToggleKanban("+id+")\" role=\"button\">";
            h+="<div class=\"cl-card-hdr-left\">";
            h+="<i class=\"fas fa-clipboard-list cl-card-icon\"></i>";
            h+="<span class=\"cl-card-title\">"+e+"</span>";
            h+="<span class=\"cl-card-count\">0/"+total+"</span>";
            h+="</div>";
            h+="<div class=\"cl-card-hdr-right\">";
            h+="<div class=\"progress\" style=\"width:120px;height:7px\"><div class=\"progress-bar bg-primary\" style=\"width:0%\"></div></div>";
            h+="<span class=\"badge bg-primary\">0%</span>";
            h+="<button type=\"button\" style=\"background:transparent;border:none;color:#dc3545;padding:0 3px;cursor:pointer;font-size:.85rem;line-height:1;opacity:.7\" title=\"Delete\" ";
            h+="onmouseenter=\"this.style.opacity=1\" onmouseleave=\"this.style.opacity=.7\" ";
            h+="onclick=\"event.stopPropagation();clDeleteChecklist("+id+",&quot;"+ajaxUrl+"/checklist.php&quot;)\">";
            h+="<i class=\"fas fa-trash-alt\"></i></button>";
            h+="<i class=\"fas fa-chevron-down cl-chevron\" id=\"cl-chev-"+id+"\"></i>";
            h+="</div></div>";
            h+="<div class=\"cl-kanban-wrap\" id=\"cl-kanban-"+id+"\" style=\"display:none\" data-cl-id=\""+id+"\" data-ajax-url=\""+ajaxUrl+"\">";
            h+="<div class=\"cl-board\">";
            h+="<div class=\"cl-col cl-col-todo\"><div class=\"cl-col-hdr\"><span><i class=\"far fa-circle me-1\"></i>To do</span>";
            h+="<span class=\"badge bg-primary rounded-pill\">"+total+"</span></div>";
            h+="<ul class=\"cl-sort\" id=\"cl-todo-"+id+"\" data-status=\"todo\" data-cl-id=\""+id+"\"></ul></div>";
            h+="<div class=\"cl-col cl-col-done\"><div class=\"cl-col-hdr\"><span><i class=\"fas fa-check-circle me-1\"></i>Done</span>";
            h+="<span class=\"badge bg-success rounded-pill\">0</span></div>";
            h+="<ul class=\"cl-sort\" id=\"cl-done-"+id+"\" data-status=\"done\" data-cl-id=\""+id+"\"></ul></div>";
            h+="</div>";
            h+="<div class=\"cl-toolbar\">";
            h+="<button class=\"btn btn-sm btn-outline-warning\" onclick=\"clShowAddExc("+id+",&quot;"+ajaxUrl+"/add_item.php&quot;,&quot;"+ajaxUrl+"/move_item.php&quot;)\">";
            h+="<i class=\"fas fa-exclamation-triangle me-1\"></i>Add exceptional task</button>";
            h+="</div></div>";
            h+="</div>";
            return h;
        }

        // ── Sélecteur de modèle searchable ────────────────────────────────────
        function clCloseTplMenu(){
            var m=document.getElementById("cl-tpl-menu"), t=document.getElementById("cl-tpl-toggle");
            if(m) m.classList.remove("cl-open");
            if(t) t.classList.remove("cl-open");
        }
        function clFilterTpl(q){
            q=(q||"").toLowerCase(); var any=false;
            document.querySelectorAll("#cl-tpl-list .cl-tpl-opt").forEach(function(o){
                var match=(o.dataset.name||"").toLowerCase().indexOf(q)>=0;
                o.style.display=match?"":"none";
                if(match) any=true;
            });
            var nr=document.getElementById("cl-tpl-noresult");
            if(nr) nr.style.display=any?"none":"block";
        }
        function clResetTplPicker(){
            var hid=document.querySelector("#cl-tpl-picker [name=templates_id]");
            var tog=document.getElementById("cl-tpl-toggle");
            var def=document.querySelector("#cl-tpl-list .cl-tpl-opt[data-id=\"0\"]");
            if(hid) hid.value="0";
            if(tog && def) tog.textContent=def.dataset.name;
            document.querySelectorAll("#cl-tpl-list .cl-tpl-opt").forEach(function(o){
                o.classList.toggle("cl-tpl-active",o.dataset.id==="0");
            });
        }
        document.addEventListener("click",function(e){
            var tog=e.target.closest("#cl-tpl-toggle");
            if(tog){
                var m=document.getElementById("cl-tpl-menu");
                if(m.classList.contains("cl-open")){ clCloseTplMenu(); }
                else {
                    m.classList.add("cl-open"); tog.classList.add("cl-open");
                    var f=document.getElementById("cl-tpl-filter");
                    if(f){ f.value=""; clFilterTpl(""); setTimeout(function(){f.focus();},30); }
                }
                return;
            }
            var opt=e.target.closest(".cl-tpl-opt");
            if(opt){
                var hid=document.querySelector("#cl-tpl-picker [name=templates_id]");
                var t=document.getElementById("cl-tpl-toggle");
                if(hid) hid.value=opt.dataset.id;
                if(t) t.textContent=opt.dataset.name;
                document.querySelectorAll("#cl-tpl-list .cl-tpl-opt").forEach(function(o){o.classList.remove("cl-tpl-active");});
                opt.classList.add("cl-tpl-active");
                clCloseTplMenu();
                return;
            }
            if(!e.target.closest("#cl-tpl-picker")) clCloseTplMenu();
        });
        document.addEventListener("input",function(e){
            if(e.target.id==="cl-tpl-filter") clFilterTpl(e.target.value);
        });
        document.addEventListener("keydown",function(e){
            if(e.target.id!=="cl-tpl-filter") return;
            if(e.key==="Enter"){
                e.preventDefault();
                var vis=[].slice.call(document.querySelectorAll("#cl-tpl-list .cl-tpl-opt")).filter(function(o){return o.style.display!=="none";});
                if(vis[0]) vis[0].click();
            } else if(e.key==="Escape"){ clCloseTplMenu(); }
        });

        // Event delegation on document -- works even if DOM is injected
        // after DOMContentLoaded (GLPI tabs loaded dynamically)
        document.addEventListener("click",function(e){
            var btn=e.target.closest("#cl-create-submit");
            if(!btn) return;
            var form=document.getElementById("cl-create-form");
            if(!form) return;
            var url=btn.dataset.ajaxUrl;
            var ajaxBase=url.replace(/\/checklist\.php$/,"");
            var name=form.querySelector("[name=name]").value.trim();
            var itemtype=form.querySelector("[name=itemtype]").value;
            var items_id=form.querySelector("[name=items_id]").value;
            var tpl=form.querySelector("[name=templates_id]").value;
            if(!name){form.querySelector("[name=name]").classList.add("is-invalid");return;}
            btn.disabled=true;
            var fd=new FormData();
            fd.append("action","create"); fd.append("name",name);
            fd.append("itemtype",itemtype); fd.append("items_id",items_id);
            fd.append("templates_id",tpl);
            clFetch(url,fd).then(function(r){return r.json();}).then(function(d){
                if(d.success){
                    // Close modal
                    var modal=document.getElementById("clCreateModal");
                    if(modal && typeof bootstrap!=="undefined"){
                        var bsModal=bootstrap.Modal.getInstance(modal);
                        if(bsModal) bsModal.hide();
                    }
                    // Reset form
                    form.reset();
                    clResetTplPicker();
                    // Remove empty placeholder
                    var emp=document.querySelector(".cl-empty");
                    if(emp) emp.remove();
                    // Ensure list container exists
                    var list=document.getElementById("cl-list");
                    if(!list){
                        var wrap=document.getElementById("cl-container");
                        var footer=wrap.querySelector(".cl-footer-link");
                        list=document.createElement("div");
                        list.id="cl-list";
                        wrap.insertBefore(list, footer);
                    }
                    // Build and insert card
                    list.insertAdjacentHTML("beforeend", clBuildCardHtml(d.id, d.name, d.total, ajaxBase));
                    clUpdateGlobalCount();
                    // Auto-open the new card kanban
                    setTimeout(function(){ clToggleKanban(d.id); }, 100);
                } else {
                    alert("Erreur: "+(d.error||"Inconnue"));
                }
                btn.disabled=false;
            }).catch(function(){btn.disabled=false;});
        });
        </script>';
    }


    // ═══════════════════════════════════════════════════════════════════════════
    //  AFFICHAGE PRINCIPAL
    // ═══════════════════════════════════════════════════════════════════════════

    public static function showForItem(CommonGLPI $item): void
    {
        global $DB;

        self::injectAssets(); // CSS + JS inline (GLPI 11 ne sert pas les assets statiques plugins)

        $itemtype   = $item->getType();
        $items_id   = $item->getID();
        $plugin_url = Plugin::getWebDir('checklist');
        $ajax_url   = $plugin_url . '/ajax';

        // Checklists existantes
        $checklists = [];
        foreach ($DB->request([
            'FROM'  => static::getTable(),
            'WHERE' => ['itemtype' => $itemtype, 'items_id' => $items_id],
            'ORDER' => ['date_creation ASC'],
        ]) as $row) {
            $checklists[] = $row;
        }

        $templates = PluginChecklistTemplate::getAll(true);

        // ── Container principal ────────────────────────────────────────────────
        echo '<div class="cl-wrap" id="cl-container">';
        echo '<div class="cl-topbar">';
        echo '<div class="cl-topbar-left">';
        echo '<span class="cl-topbar-title"><i class="fas fa-tasks"></i> ' . __('Checklists', 'checklist') . '</span>';
        echo '<span class="cl-topbar-sub">' . count($checklists) . ' checklist(s)</span>';
        echo '</div>';
        self::renderCreateModal($itemtype, $items_id, $templates, $ajax_url);
        echo '</div>'; // cl-topbar

        if (empty($checklists)) {
            echo '<div class="cl-empty">';
            echo '<i class="fas fa-clipboard"></i>';
            echo __('No checklist yet. Click «+ New checklist» to start.', 'checklist');
            echo '</div>';
        } else {
            echo '<div id="cl-list">';
            foreach ($checklists as $cl) {
                self::renderCard($cl, $ajax_url);
            }
            echo '</div>';
        }

        // Lien gestion des templates
        $tpl_url = Plugin::getWebDir('checklist') . '/front/template.php';
        echo '<a class="cl-footer-link" href="' . htmlspecialchars($tpl_url) . '" target="_blank">';
        echo '<i class="fas fa-cog me-1"></i>' . __('Manage checklist templates', 'checklist') . '</a>';

        echo '</div>'; // .cl-wrap
    }

    // ─── Modal de création ─────────────────────────────────────────────────────

    private static function renderCreateModal(string $itemtype, int $items_id, array $templates, string $ajax_url): void
    {
        echo '<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#clCreateModal">';
        echo '<i class="fas fa-plus me-1"></i>' . __('New checklist', 'checklist') . '</button>';

        echo '
        <div class="modal fade" id="clCreateModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>' . __('New checklist', 'checklist') . '</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <form id="cl-create-form">
                  <input type="hidden" name="itemtype" value="' . htmlspecialchars($itemtype) . '">
                  <input type="hidden" name="items_id" value="' . $items_id . '">
                  <div class="mb-3">
                    <label class="form-label fw-semibold">' . __('Name') . ' <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" required
                           placeholder="' . htmlspecialchars(__('e.g. Onboarding SIRH', 'checklist')) . '">
                  </div>
                  <div class="mb-2">
                    <label class="form-label fw-semibold">' . __('From template', 'checklist') . ' <span class="text-muted fw-normal">(' . __('optional') . ')</span></label>
                    <div class="cl-tpl-picker" id="cl-tpl-picker">
                      <input type="hidden" name="templates_id" value="0">
                      <button type="button" class="form-select text-start cl-tpl-toggle" id="cl-tpl-toggle">— ' . __('Empty checklist', 'checklist') . ' —</button>
                      <div class="cl-tpl-menu" id="cl-tpl-menu">
                        <div class="cl-tpl-search-wrap">
                          <i class="fas fa-search cl-tpl-search-ico"></i>
                          <input type="text" class="form-control form-control-sm cl-tpl-filter" id="cl-tpl-filter" placeholder="' . htmlspecialchars(__('Search a template…', 'checklist')) . '" autocomplete="off">
                        </div>
                        <ul class="cl-tpl-list" id="cl-tpl-list">
                          <li class="cl-tpl-opt cl-tpl-active" data-id="0" data-name="— ' . htmlspecialchars(__('Empty checklist', 'checklist')) . ' —"><i class="far fa-file me-2 text-muted"></i>— ' . __('Empty checklist', 'checklist') . ' —</li>';

        foreach ($templates as $tpl) {
            $tname = htmlspecialchars($tpl['name']);
            echo '<li class="cl-tpl-opt" data-id="' . (int) $tpl['id'] . '" data-name="' . $tname . '"><i class="fas fa-clipboard-list me-2 text-primary"></i>' . $tname . '</li>';
        }

        echo '            </ul>
                        <div class="cl-tpl-noresult" id="cl-tpl-noresult" style="display:none">' . __('No matching template', 'checklist') . '</div>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . __('Cancel') . '</button>
                <button type="button" class="btn btn-primary" id="cl-create-submit"
                        data-ajax-url="' . htmlspecialchars($ajax_url . '/checklist.php') . '">
                  <i class="fas fa-save me-1"></i>' . __('Create', 'checklist') . '
                </button>
              </div>
            </div>
          </div>
        </div>';
    }

    // ─── Carte checklist (résumé + kanban dépliable) ───────────────────────────

    private static function renderCard(array $cl, string $ajax_url): void
    {
        $cl_id = (int) $cl['id'];
        $done  = (int) countElementsInTable(PluginChecklistItem::getTable(), ['plugin_checklist_checklists_id' => $cl_id, 'status' => 'done']);
        $total = (int) countElementsInTable(PluginChecklistItem::getTable(), ['plugin_checklist_checklists_id' => $cl_id]);
        $pct   = $total > 0 ? (int) round($done / $total * 100) : 0;
        $full  = $total > 0 && $done === $total;

        echo '<div class="cl-card" id="cl-card-' . $cl_id . '">';

        // En-tête carte
        echo '<div class="cl-card-hdr" onclick="clToggleKanban(' . $cl_id . ')" role="button">';
        echo '<div class="cl-card-hdr-left">';
        echo '<i class="fas fa-clipboard-list cl-card-icon"></i>';
        echo '<span class="cl-card-title">' . htmlspecialchars($cl['name']) . '</span>';
        echo '<span class="cl-card-count">' . $done . '/' . $total . '</span>';
        echo '</div>';
        echo '<div class="cl-card-hdr-right">';
        echo '<div class="progress" style="width:100px;height:7px;"><div class="progress-bar ' . ($full ? 'bg-success' : 'bg-primary') . '" style="width:' . $pct . '%"></div></div>';
        echo '<span class="badge ' . ($full ? 'bg-success' : 'bg-primary') . '">' . $pct . '%</span>';
        echo '<button type="button" style="background:transparent;border:none;color:#dc3545;padding:0 3px;cursor:pointer;font-size:.85rem;line-height:1;opacity:.7" title="' . __('Delete') . '"';
        echo ' onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.7"';
        echo ' onclick="event.stopPropagation();clDeleteChecklist(' . $cl_id . ',\'' . htmlspecialchars($ajax_url . '/checklist.php') . '\')">';
        echo '<i class="fas fa-trash-alt"></i></button>';
        echo '<i class="fas fa-chevron-down cl-chevron" id="cl-chev-' . $cl_id . '"></i>';
        echo '</div>';
        echo '</div>'; // cl-card-hdr

        // Vue Kanban (masquée)
        echo '<div class="cl-kanban-wrap" id="cl-kanban-' . $cl_id . '" style="display:none"';
        echo ' data-cl-id="' . $cl_id . '" data-ajax-url="' . htmlspecialchars($ajax_url) . '">';
        self::renderKanban($cl_id, $ajax_url);
        echo '</div>';

        echo '</div>'; // .cl-card
    }

    // ─── Vue Kanban (2 colonnes) ───────────────────────────────────────────────

    public static function renderKanban(int $cl_id, string $ajax_url): void
    {
        $items = PluginChecklistItem::getForChecklist($cl_id);

        echo '<div class="cl-board">';

        $cols = ['todo' => ['label' => __('To do', 'checklist'), 'icon' => 'far fa-circle'], 'done' => ['label' => __('Done', 'checklist'), 'icon' => 'fas fa-check-circle']];
        foreach ($cols as $status => $cfg) {
            echo '<div class="cl-col cl-col-' . $status . '">';
            echo '<div class="cl-col-hdr">';
            echo '<span><i class="' . $cfg['icon'] . ' me-1"></i>' . $cfg['label'] . '</span>';
            echo '<span class="badge ' . ($status === 'done' ? 'bg-success' : 'bg-primary') . ' rounded-pill">' . count($items[$status]) . '</span>';
            echo '</div>';
            echo '<ul class="cl-sort" id="cl-' . $status . '-' . $cl_id . '" data-status="' . $status . '" data-cl-id="' . $cl_id . '">';
            foreach ($items[$status] as $it) {
                self::renderItem($it, $ajax_url);
            }
            echo '</ul>';
            echo '</div>';
        }

        echo '</div>'; // cl-board

        // Toolbar
        echo '<div class="cl-toolbar">';
        echo '<button class="btn btn-sm btn-outline-warning"';
        echo ' onclick="clShowAddExc(' . $cl_id . ',\'' . htmlspecialchars($ajax_url . '/add_item.php') . '\',\'' . htmlspecialchars($ajax_url . '/move_item.php') . '\')">';
        echo '<i class="fas fa-exclamation-triangle me-1"></i>' . __('Add exceptional task', 'checklist') . '</button>';
        echo '<button class="btn btn-sm btn-outline-secondary"';
        echo ' data-bs-toggle="collapse" data-bs-target="#cl-hist-' . $cl_id . '">';
        echo '<i class="fas fa-history me-1"></i>' . __('History', 'checklist') . '</button>';
        echo '</div>';

        // Historique
        echo '<div class="collapse" id="cl-hist-' . $cl_id . '">';
        echo '<div class="cl-hist-wrap">';
        $entries = PluginChecklistLog::getForChecklist($cl_id);
        if (empty($entries)) {
            echo '<p class="text-muted small mb-0">' . __('No history yet.', 'checklist') . '</p>';
        } else {
            echo '<table class="cl-hist-table"><tbody>';
            foreach ($entries as $e) {
                echo '<tr><td class="cl-hist-date">' . htmlspecialchars($e['date']) . '</td>';
                echo '<td class="cl-hist-user">' . htmlspecialchars($e['user']) . '</td>';
                echo '<td>' . htmlspecialchars($e['action']);
                if (isset($e['new_value']['name'])) {
                    echo ' : <em>' . htmlspecialchars($e['new_value']['name']) . '</em>';
                }
                echo '</td></tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div></div>';
    }

    // ─── Rendu d'un item (li) ─────────────────────────────────────────────────

    private static function renderItem(array $item, string $ajax_url): void
    {
        $id  = (int) $item['id'];
        $exc = (bool) $item['is_exceptional'];

        echo '<li class="cl-item' . ($exc ? ' cl-exc' : '') . '"';
        echo ' id="cl-item-' . $id . '" data-id="' . $id . '"';
        echo ' onclick="clToggleItem(' . $id . ',\'' . htmlspecialchars($ajax_url . '/move_item.php') . '\')">';

        echo '<div class="cl-item-row">';
        echo '<span class="cl-drag-hdl"><i class="fas fa-grip-vertical"></i></span>';

        if ($exc) {
            echo '<span class="cl-badge-exc">⚠ EXC</span>';
        }

        echo '<span class="cl-item-name">' . htmlspecialchars($item['name']) . '</span>';
        echo '</div>';

        if (!empty($item['description'])) {
            echo '<span class="cl-item-desc">' . htmlspecialchars($item['description']) . '</span>';
        }

        echo '</li>';
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  ACTIONS CRUD
    // ═══════════════════════════════════════════════════════════════════════════

    public static function createForItem(string $itemtype, int $items_id, string $name, int $templates_id = 0): int|false
    {
        global $DB;

        // L'entité de la checklist suit celle de son élément parent (pas la session)
        $entities_id = Session::getActiveEntity();
        if (class_exists($itemtype) && is_subclass_of($itemtype, CommonDBTM::class)) {
            $parent = new $itemtype();
            if ($parent->getFromDB($items_id) && isset($parent->fields['entities_id'])) {
                $entities_id = (int) $parent->fields['entities_id'];
            }
        }

        $result = $DB->insert(static::getTable(), [
            'name'                          => $name,
            'itemtype'                      => $itemtype,
            'items_id'                      => $items_id,
            'plugin_checklist_templates_id' => $templates_id,
            'status'                        => 'open',
            'date_creation'                 => date('Y-m-d H:i:s'),
            'date_mod'                      => date('Y-m-d H:i:s'),
            'users_id'                      => Session::getLoginUserID() ?: 0,
            'entities_id'                   => $entities_id,
        ]);

        if (!$result) {
            return false;
        }

        $cl_id = (int) $DB->insertId();

        // Instanciation depuis le template
        if ($templates_id > 0) {
            foreach (PluginChecklistTemplateItem::getForTemplate($templates_id) as $rank => $tpi) {
                $DB->insert(PluginChecklistItem::getTable(), [
                    'plugin_checklist_checklists_id' => $cl_id,
                    'name'                           => $tpi['name'],
                    'description'                    => $tpi['description'] ?? '',
                    'status'                         => 'todo',
                    'rank_todo'                      => (int) $tpi['rank'],
                    'rank_done'                      => 0,
                    'is_exceptional'                 => 0,
                    'date_creation'                  => date('Y-m-d H:i:s'),
                    'date_mod'                       => date('Y-m-d H:i:s'),
                    'date_todo'                      => date('Y-m-d H:i:s'),
                    'users_id_creator'               => Session::getLoginUserID() ?: 0,
                ]);
            }
        }

        return $cl_id;
    }

    public static function deleteChecklist(int $cl_id): bool
    {
        global $DB;

        $DB->delete(PluginChecklistLog::getTable(),  ['plugin_checklist_checklists_id' => $cl_id]);
        $DB->delete(PluginChecklistItem::getTable(), ['plugin_checklist_checklists_id' => $cl_id]);
        $DB->delete(static::getTable(),              ['id' => $cl_id]);

        return true;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  CONTRÔLE D'ACCÈS — empêche l'IDOR sur les endpoints AJAX
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Vérifie que l'utilisateur courant a le droit demandé (READ/UPDATE) sur
     * l'élément GLPI parent (Ticket, Computer…) auquel se rattache une checklist.
     * Le contrôle GLPI `can()` valide aussi l'entité et l'accès spécifique à l'item.
     */
    public static function canAccessParent(string $itemtype, int $items_id, int $right = READ): bool
    {
        if ($items_id <= 0 || !class_exists($itemtype) || !is_subclass_of($itemtype, CommonDBTM::class)) {
            return false;
        }

        $item = new $itemtype();
        return (bool) $item->can($items_id, $right);
    }

    /**
     * Charge une checklist et vérifie l'accès à son élément parent.
     * Retourne la ligne checklist si l'accès est autorisé, sinon null.
     */
    public static function getCheckedChecklist(int $cl_id, int $right = READ): ?array
    {
        global $DB;

        if ($cl_id <= 0) {
            return null;
        }

        $row = $DB->request([
            'FROM'  => static::getTable(),
            'WHERE' => ['id' => $cl_id],
        ])->current();

        if (!$row) {
            return null;
        }

        if (!self::canAccessParent((string) $row['itemtype'], (int) $row['items_id'], $right)) {
            return null;
        }

        return $row;
    }
}

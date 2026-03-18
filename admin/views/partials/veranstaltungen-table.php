<?php
/**
 * Veranstaltungen Tabelle
 */
if (!defined('ABSPATH')) exit;
?>

<div class="veranstaltungen-list" style="margin-top: 2rem; overflow: visible; position: relative;">
    <div class="veranstaltung-gruppe" style="margin-bottom: 1.5rem; border: 1px solid #c3c4c7; border-radius: 4px; position: relative;">
        <!-- Einklappbarer Header -->
        <h3 class="veranstaltung-header-collapsible" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 1rem 1.5rem; margin: 0; display: flex; align-items: center; gap: 1rem; transition: all 0.3s; cursor: pointer;" onclick="toggleVeranstaltungGroup('alle-veranstaltungen')">
            <span class="dashicons dashicons-arrow-down-alt2 collapse-icon" id="icon-alle-veranstaltungen" style="transition: transform 0.3s; font-size: 20px;"></span>
            <span class="dashicons dashicons-calendar-alt" style="font-size: 24px;"></span>
            <strong style="font-size: 1.1rem;"><?php _e('Alle Veranstaltungen', 'dienstplan-verwaltung'); ?></strong>
            <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.9rem;">
                <?php echo count($veranstaltungen); ?> <?php echo count($veranstaltungen) != 1 ? 'Veranstaltungen' : 'Veranstaltung'; ?>
            </span>
            <div style="flex: 1;"></div>
            <button type="button" onclick="event.stopPropagation(); openVeranstaltungModal();" class="button button-primary" style="background: rgba(255,255,255,0.9); color: #f59e0b; border: none; padding: 0.5rem 1rem; font-weight: 600;">
                <span class="dashicons dashicons-plus-alt" style="font-size: 16px;"></span>
                <?php _e('Neue Veranstaltung', 'dienstplan-verwaltung'); ?>
            </button>
        </h3>
        
        <!-- Einklappbarer Content -->
        <div id="alle-veranstaltungen" class="veranstaltung-content" style="display: block;">
            <!-- Bulk-Aktionen Toolbar -->
            <div class="bulk-actions-toolbar" style="background: #f9fafb; padding: 1rem; border: 1px solid #e5e7eb; border-bottom: none; display: none;">
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span class="selected-count" style="color: #6b7280;">
                        <span class="count">0</span> <?php _e('ausgewählt', 'dienstplan-verwaltung'); ?>
                    </span>
                    
                    <select class="bulk-action-select" style="min-width: 200px;">
                        <option value=""><?php _e('-- Aktion wählen --', 'dienstplan-verwaltung'); ?></option>
                        <option value="delete"><?php _e('Löschen', 'dienstplan-verwaltung'); ?></option>
                        <option value="change_status"><?php _e('Status ändern', 'dienstplan-verwaltung'); ?></option>
                    </select>
                    
                    <button type="button" class="button bulk-action-apply">
                        <?php _e('Anwenden', 'dienstplan-verwaltung'); ?>
                    </button>
                    
                    <button type="button" class="button bulk-action-cancel">
                        <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
                    </button>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped" style="margin: 0; border-collapse: separate; border-spacing: 0;">
                <thead>
                    <tr>
                        <th style="width: 40px; padding-left: 1rem;">
                            <input type="checkbox" class="select-all-veranstaltungen" style="margin: 0;">
                        </th>
                        <th width="15%"><?php _e('Name', 'dienstplan-verwaltung'); ?></th>
                        <th width="12%"><?php _e('Datum', 'dienstplan-verwaltung'); ?></th>
                        <th width="10%"><?php _e('Typ', 'dienstplan-verwaltung'); ?></th>
                        <th width="15%"><?php _e('Verantwortliche', 'dienstplan-verwaltung'); ?></th>
                        <th width="10%"><?php _e('Status', 'dienstplan-verwaltung'); ?></th>
                        <th width="13%"><?php _e('Aktionen', 'dienstplan-verwaltung'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($veranstaltungen as $v): 
                        $tage = $db->get_veranstaltung_tage($v->id);
                        $event_vereine = $db->get_veranstaltung_vereine($v->id);
                        $verantwortliche = $db->get_veranstaltung_verantwortliche($v->id);
                    ?>
                        <tr style="position: relative;" data-veranstaltung-id="<?php echo esc_attr($v->id); ?>">
                            <td style="padding-left: 1rem;">
                                <input type="checkbox" class="veranstaltung-checkbox" value="<?php echo esc_attr($v->id); ?>" style="margin: 0;">
                            </td>
                            <td>
                                <strong><?php echo esc_html($v->name); ?></strong>
                                <?php if ($v->beschreibung): ?>
                                    <br><small><?php echo esc_html(wp_trim_words($v->beschreibung, 10)); ?></small>
                                <?php endif; ?>
                                <?php if (count($event_vereine) > 0): ?>
                                    <br>
                                    <button type="button" 
                                            class="button button-small toggle-verein-details" 
                                            onclick="toggleVereinDetails(<?php echo $v->id; ?>)"
                                            style="margin-top: 0.5rem; font-size: 0.85rem; padding: 2px 8px;">
                                        <span class="dashicons dashicons-arrow-down" style="font-size: 14px; vertical-align: middle;"></span>
                                        <?php echo count($event_vereine); ?> Verein(e)
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($v->typ === 'mehrtaegig' && $v->end_datum) {
                                    echo date_i18n('d.m.Y', strtotime($v->start_datum)) . '<br><small>bis ' . date_i18n('d.m.Y', strtotime($v->end_datum)) . '</small>';
                                } else {
                                    echo date_i18n('d.m.Y', strtotime($v->start_datum));
                                }
                                ?>
                                <br><small><?php echo count($tage); ?> <?php _e('Tag(e)', 'dienstplan-verwaltung'); ?></small>
                            </td>
                            <td>
                                <?php if ($v->typ === 'mehrtaegig'): ?>
                                    <span class="dashicons dashicons-calendar" title="<?php _e('Mehrtägig', 'dienstplan-verwaltung'); ?>"></span>
                                    <?php _e('Mehrtägig', 'dienstplan-verwaltung'); ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-calendar-alt" title="<?php _e('Eintägig', 'dienstplan-verwaltung'); ?>"></span>
                                    <?php _e('Eintägig', 'dienstplan-verwaltung'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($verantwortliche)): ?>
                                    <?php 
                                    $namen = array();
                                    foreach ($verantwortliche as $person) {
                                        $user = get_user_by('id', $person->user_id);
                                        if ($user) {
                                            $namen[] = sprintf(
                                                '<span title="%s">%s</span>',
                                                esc_attr($user->user_email),
                                                esc_html($user->display_name)
                                            );
                                        }
                                    }
                                    echo implode(', ', $namen);
                                    ?>
                                <?php else: ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select class="status-quick-change" 
                                        data-veranstaltung-id="<?php echo $v->id; ?>" 
                                        onchange="quickChangeStatus(this)"
                                        style="padding: 4px 8px; border-radius: 4px; border: 1px solid #ddd; font-size: 13px; cursor: pointer;">
                                    <option value="in_planung" <?php selected($v->status, 'in_planung'); ?>>🔵 In Planung</option>
                                    <option value="geplant" <?php selected($v->status, 'geplant'); ?>>🟢 Geplant</option>
                                    <option value="aktiv" <?php selected($v->status, 'aktiv'); ?>>🟡 Aktiv</option>
                                    <option value="abgeschlossen" <?php selected($v->status, 'abgeschlossen'); ?>>⚪ Abgeschlossen</option>
                                </select>
                            </td>
                            <td style="position: relative; overflow: visible;">
                                <div class="dp-inline-action-buttons">
                                    <a href="#" class="button button-small dp-inline-action-button" onclick="editVeranstaltung(<?php echo $v->id; ?>); return false;" title="<?php esc_attr_e('Bearbeiten', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Bearbeiten', 'dienstplan-verwaltung'); ?>">
                                        <span class="dp-inline-action-emoji" aria-hidden="true">✏️</span>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=dienstplan-statistik&veranstaltung_id=' . intval($v->id))); ?>" class="button button-small dp-inline-action-button" title="<?php esc_attr_e('Statistik', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Statistik', 'dienstplan-verwaltung'); ?>">
                                        <span class="dp-inline-action-emoji" aria-hidden="true">📊</span>
                                    </a>
                                    <?php if (!empty($v->seite_id)): ?>
                                        <?php
                                        $page = get_post($v->seite_id);
                                        if ($page && $page->post_status === 'publish'):
                                            $page_url = get_permalink($v->seite_id);
                                            $share_url = $page_url;
                                            if (empty($share_url)) {
                                                $share_url = wp_get_shortlink($v->seite_id);
                                            }
                                            if (empty($share_url)) {
                                                $share_url = add_query_arg('page_id', intval($v->seite_id), home_url('/'));
                                            }
                                        ?>
                                            <a href="<?php echo esc_url($page_url); ?>" target="_blank" class="button button-small dp-inline-action-button" title="<?php esc_attr_e('Seite öffnen', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Seite öffnen', 'dienstplan-verwaltung'); ?>">
                                                <span class="dp-inline-action-emoji" aria-hidden="true">🌐</span>
                                            </a>
                                            <a href="#" class="button button-small dp-inline-action-button" onclick="sharePageLink('<?php echo esc_js($share_url); ?>', '<?php echo esc_js($v->name); ?>'); return false;" title="<?php esc_attr_e('Teilen', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Teilen', 'dienstplan-verwaltung'); ?>">
                                                <span class="dp-inline-action-emoji" aria-hidden="true">📤</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="#" class="button button-small dp-inline-action-button" onclick="createPageForEvent(<?php echo $v->id; ?>); return false;" title="<?php esc_attr_e('Seite neu erstellen', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Seite neu erstellen', 'dienstplan-verwaltung'); ?>">
                                                <span class="dp-inline-action-emoji" aria-hidden="true">➕</span>
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="#" class="button button-small dp-inline-action-button" onclick="createPageForEvent(<?php echo $v->id; ?>); return false;" title="<?php esc_attr_e('Seite erstellen', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Seite erstellen', 'dienstplan-verwaltung'); ?>">
                                            <span class="dp-inline-action-emoji" aria-hidden="true">➕</span>
                                        </a>
                                    <?php endif; ?>
                                    <a href="#" class="button button-small dp-inline-action-button is-danger" onclick="deleteVeranstaltung(<?php echo $v->id; ?>); return false;" title="<?php esc_attr_e('Löschen', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Löschen', 'dienstplan-verwaltung'); ?>">
                                        <span class="dp-inline-action-emoji" aria-hidden="true">🗑️</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Verein-Details (ausgeklappt) -->
                        <tr class="verein-details-row" id="verein-details-<?php echo $v->id; ?>" style="display: none;">
                            <td colspan="7" style="background: #f9fafb; padding: 1.5rem; border-left: 4px solid #0ea5e9;">
                                <div class="verein-details-container">
                                    <?php 
                                    // Prüfen ob Seiten erstellt werden können (nicht bei Status "in_planung")
                                    $kann_seiten_erstellen = ($v->status !== 'in_planung');
                                    ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                        <h4 style="margin: 0; color: #0f172a;">
                                            <span class="dashicons dashicons-groups" style="color: #0ea5e9; font-size: 20px; vertical-align: middle;"></span>
                                            Beteiligte Vereine & Anmeldeseiten
                                        </h4>
                                        <?php if (count($event_vereine) > 0): ?>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <?php if ($kann_seiten_erstellen): ?>
                                                    <button type="button" 
                                                            class="button button-primary dp-inline-action-button" 
                                                            onclick="createVereinspezifischeSeiten(<?php echo $v->id; ?>)"
                                                            title="Alle Verein-Seiten erstellen"
                                                            aria-label="Alle Verein-Seiten erstellen"
                                                            style="background: #0ea5e9; border-color: #0284c7;">
                                                        <span class="dp-inline-action-emoji" aria-hidden="true">📄</span>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" 
                                                            class="button button-primary dp-inline-action-button" 
                                                            disabled
                                                            title="Seiten können erst erstellt werden, wenn die Veranstaltung nicht mehr in Planung ist"
                                                            aria-label="Seiten noch nicht verfügbar"
                                                            style="background: #9ca3af; border-color: #6b7280; cursor: not-allowed;">
                                                        <span class="dp-inline-action-emoji" aria-hidden="true">🔒</span>
                                                    </button>
                                                <?php endif; ?>
                                                <?php 
                                                // Prüfen ob bereits Seiten existieren
                                                $has_pages = false;
                                                foreach ($event_vereine as $check_verein) {
                                                    $check_page = get_posts(array(
                                                        'post_type' => 'page',
                                                        'posts_per_page' => 1,
                                                        'meta_query' => array(
                                                            array('key' => '_dp_veranstaltung_id', 'value' => $v->id),
                                                            array('key' => '_dp_verein_id', 'value' => $check_verein->verein_id)
                                                        )
                                                    ));
                                                    if (!empty($check_page)) {
                                                        $has_pages = true;
                                                        break;
                                                    }
                                                }
                                                if ($has_pages): ?>
                                                    <button type="button" 
                                                            class="button dp-inline-action-button is-danger" 
                                                            onclick="deleteAllVereinSeiten(<?php echo $v->id; ?>)"
                                                            title="Alle Seiten löschen"
                                                            aria-label="Alle Seiten löschen"
                                                            style="background: #ef4444; border-color: #dc2626; color: white;">
                                                        <span class="dp-inline-action-emoji" aria-hidden="true">🗑️</span>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!$kann_seiten_erstellen): ?>
                                        <div style="background: #dbeafe; border: 2px solid #3b82f6; border-radius: 6px; padding: 1rem; margin-bottom: 1rem; color: #1e40af;">
                                            <p style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                                                <span class="dashicons dashicons-info" style="font-size: 20px;"></span>
                                                <strong>Hinweis:</strong> Anmeldeseiten können erst erstellt werden, wenn die Veranstaltung den Status "Geplant" hat.
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (count($event_vereine) > 0): ?>
                                        <div style="display: grid; gap: 1rem;">
                                            <?php foreach ($event_vereine as $verein): 
                                                // Prüfen ob Seite existiert
                                                $verein_page = get_posts(array(
                                                    'post_type' => 'page',
                                                    'post_status' => 'any',
                                                    'meta_query' => array(
                                                        array(
                                                            'key' => '_dp_veranstaltung_id',
                                                            'value' => $v->id
                                                        ),
                                                        array(
                                                            'key' => '_dp_verein_id',
                                                            'value' => $verein->verein_id
                                                        )
                                                    ),
                                                    'numberposts' => 1
                                                ));
                                                
                                                $page_exists = !empty($verein_page);
                                                $page = $page_exists ? $verein_page[0] : null;
                                            ?>
                                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; background: white; border: 1px solid #e2e8f0; border-radius: 6px;">
                                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                                        <span class="dashicons dashicons-flag" style="color: #64748b; font-size: 24px;"></span>
                                                        <div>
                                                            <strong style="color: #1e293b; font-size: 1rem;"><?php echo esc_html($verein->verein_name); ?></strong>
                                                            <?php if ($verein->verein_kuerzel): ?>
                                                                <span style="color: #64748b; font-size: 0.9rem;"> (<?php echo esc_html($verein->verein_kuerzel); ?>)</span>
                                                            <?php endif; ?>
                                                            <?php if ($page_exists): ?>
                                                                <br>
                                                                <span style="color: #16a34a; font-size: 0.85rem;">
                                                                    <span class="dashicons dashicons-yes-alt" style="font-size: 14px; vertical-align: middle;"></span>
                                                                    Seite erstellt
                                                                </span>
                                                            <?php else: ?>
                                                                <br>
                                                                <span style="color: #ea580c; font-size: 0.85rem;">
                                                                    <span class="dashicons dashicons-warning" style="font-size: 14px; vertical-align: middle;"></span>
                                                                    Keine Seite vorhanden
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <div style="display: flex; gap: 0.5rem;">
                                                        <?php if ($page_exists): ?>
                                                            <?php
                                                            $verein_share_url = get_permalink($page->ID);
                                                            if (empty($verein_share_url)) {
                                                                $verein_share_url = wp_get_shortlink($page->ID);
                                                            }
                                                            if (empty($verein_share_url)) {
                                                                $verein_share_url = add_query_arg('page_id', intval($page->ID), home_url('/'));
                                                            }
                                                            ?>
                                                            <a href="<?php echo get_permalink($page->ID); ?>" 
                                                               class="button button-small dp-inline-action-button" 
                                                               target="_blank"
                                                               title="Seite im Frontend öffnen">
                                                                <span class="dp-inline-action-emoji" aria-hidden="true">🌐</span>
                                                            </a>
                                                            <button type="button"
                                                                    class="button button-small dp-inline-action-button"
                                                                    onclick="sharePageLink('<?php echo esc_js($verein_share_url); ?>', '<?php echo esc_js($verein->verein_name); ?>')"
                                                                    title="Link teilen">
                                                                <span class="dp-inline-action-emoji" aria-hidden="true">📤</span>
                                                            </button>
                                                            <button type="button" 
                                                                    class="button button-small dp-inline-action-button is-danger" 
                                                                    onclick="deleteVereinSeite(<?php echo $page->ID; ?>, '<?php echo esc_js($verein->name); ?>')"
                                                                    style="background: #ef4444; border-color: #dc2626; color: white;"
                                                                    title="Seite löschen">
                                                                <span class="dp-inline-action-emoji" aria-hidden="true">🗑️</span>
                                                            </button>
                                                        <?php else: ?>
                                                            <?php if ($kann_seiten_erstellen): ?>
                                                                <button type="button" 
                                                                        class="button button-small dp-inline-action-button" 
                                                                        onclick="createSingleVereinSeite(<?php echo $v->id; ?>, <?php echo $verein->verein_id; ?>)"
                                                                        title="Seite erstellen"
                                                                        aria-label="Seite erstellen"
                                                                        style="background: #16a34a; border-color: #15803d; color: #fff; min-width: 40px; height: 30px; display: inline-flex; align-items: center; justify-content: center;">
                                                                    <span class="dp-inline-action-emoji" aria-hidden="true">📄</span>
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="button" 
                                                                        class="button button-small dp-inline-action-button" 
                                                                        disabled
                                                                        title="Seiten können erst erstellt werden, wenn die Veranstaltung nicht mehr in Planung ist"
                                                                        aria-label="Gesperrt"
                                                                        style="background: #e5e7eb; color: #9ca3af; border-color: #d1d5db; cursor: not-allowed;">
                                                                    <span class="dp-inline-action-emoji" aria-hidden="true">🔒</span>
                                                                </button>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p style="color: #64748b; margin: 0;">Keine Vereine für diese Veranstaltung zugewiesen.</p>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// toggleActionDropdown() und toggleVeranstaltungGroup() sind jetzt in dp-admin.js definiert

window.sharePageLink = function(url, title) {
    if (!url) {
        alert('Kein Link verfügbar.');
        return;
    }

    if (navigator.share) {
        navigator.share({
            title: title ? ('Dienstplan: ' + title) : 'Dienstplan',
            url: url
        }).catch(function() {});
        return;
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url)
            .then(function() {
                alert('Link kopiert: ' + url);
            })
            .catch(function() {
                window.prompt('Link zum Teilen kopieren:', url);
            });
        return;
    }

    window.prompt('Link zum Teilen kopieren:', url);
};

// Checkbox-Logik
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.querySelector('.select-all-veranstaltungen');
    const checkboxes = document.querySelectorAll('.veranstaltung-checkbox');
    const toolbar = document.querySelector('.bulk-actions-toolbar');
    const countSpan = document.querySelector('.selected-count .count');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBulkToolbar();
        });
    }
    
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkToolbar);
    });
    
    function updateBulkToolbar() {
        const checked = document.querySelectorAll('.veranstaltung-checkbox:checked').length;
        const toolbar = document.querySelector('.bulk-actions-toolbar');
        const countSpan = document.querySelector('.selected-count .count');
        
        if (countSpan) countSpan.textContent = checked;
        if (toolbar) {
            toolbar.style.display = checked > 0 ? 'block' : 'none';
        }
    }
    
    // Bulk-Actions Apply
    const applyBtn = document.querySelector('.bulk-action-apply');
    if (applyBtn) {
        applyBtn.addEventListener('click', function() {
            const action = document.querySelector('.bulk-action-select').value;
            const checked = Array.from(document.querySelectorAll('.veranstaltung-checkbox:checked')).map(cb => cb.value);
            
            if (!action) {
                alert('Bitte wählen Sie eine Aktion aus.');
                return;
            }
            
            if (checked.length === 0) {
                alert('Bitte wählen Sie mindestens eine Veranstaltung aus.');
                return;
            }
            
            // Speichere ausgewählte IDs global
            window.selectedVeranstaltungIds = checked;
            
            switch(action) {
                case 'delete':
                    if (confirm('Möchten Sie wirklich ' + checked.length + ' Veranstaltung(en) löschen?')) {
                        bulkDeleteVeranstaltungen(checked);
                    }
                    break;
                case 'change_status':
                    openBulkStatusModal();
                    break;
                default:
                    alert('Unbekannte Aktion: ' + action);
            }
        });
    }
    
    // Cancel
    const cancelBtn = document.querySelector('.bulk-action-cancel');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            document.querySelectorAll('.veranstaltung-checkbox').forEach(cb => cb.checked = false);
            if (selectAll) selectAll.checked = false;
            updateBulkToolbar();
        });
    }
    
    // Bulk Delete Veranstaltungen
    window.bulkDeleteVeranstaltungen = function(ids) {
        jQuery.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_bulk_delete_veranstaltungen',
                nonce: dpAjax.nonce,
                veranstaltung_ids: ids
            },
            success: function(response) {
                if (response.success) {
                    alert('Veranstaltungen erfolgreich gelöscht: ' + response.data.deleted);
                    location.reload();
                } else {
                    alert('Fehler: ' + response.data.message);
                }
            },
            error: function() {
                alert('Fehler beim Löschen der Veranstaltungen.');
            }
        });
    };
    
    // Bulk Status Modal öffnen
    window.openBulkStatusModal = function() {
        document.getElementById('bulk-status-modal').style.display = 'flex';
    };
    
    window.closeBulkStatusModal = function() {
        document.getElementById('bulk-status-modal').style.display = 'none';
    };
    
    // Bulk Status speichern
    window.saveBulkStatus = function() {
        const status = document.getElementById('bulk_status_value').value;
        
        if (!status) {
            alert('Bitte wählen Sie einen Status aus.');
            return;
        }
        
        if (!window.selectedVeranstaltungIds || window.selectedVeranstaltungIds.length === 0) {
            alert('Keine Veranstaltungen ausgewählt.');
            return;
        }
        
        jQuery.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_bulk_update_veranstaltung_status',
                nonce: dpAjax.nonce,
                veranstaltung_ids: window.selectedVeranstaltungIds,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    alert('Status erfolgreich geändert: ' + response.data.updated + ' Veranstaltung(en)');
                    location.reload();
                } else {
                    alert('Fehler: ' + response.data.message);
                }
            },
            error: function() {
                alert('Fehler beim Aktualisieren des Status.');
            },
            complete: function() {
                closeBulkStatusModal();
            }
        });
    };
});
</script>

<!-- Bulk Status ändern Modal -->
<div id="bulk-status-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content" style="max-width: 500px;">
        <div class="dp-modal-header">
            <h2><?php _e('Status ändern', 'dienstplan-verwaltung'); ?></h2>
            <button type="button" class="dp-modal-close" onclick="closeBulkStatusModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="bulk-status-form">
                <table class="form-table">
                    <tr>
                        <th><label for="bulk_status_value"><?php _e('Neuer Status', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <select id="bulk_status_value" name="status" class="regular-text" required style="width: 100%;">
                                <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                                <option value="in_planung">🔵 <?php _e('In Planung', 'dienstplan-verwaltung'); ?></option>
                                <option value="geplant">🟢 <?php _e('Geplant', 'dienstplan-verwaltung'); ?></option>
                                <option value="aktiv">🟡 <?php _e('Aktiv', 'dienstplan-verwaltung'); ?></option>
                                <option value="abgeschlossen">⚪ <?php _e('Abgeschlossen', 'dienstplan-verwaltung'); ?></option>
                            </select>
                            <p class="description">
                                Der Status wird für alle ausgewählten Veranstaltungen geändert.
                            </p>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeBulkStatusModal()">
                <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
            </button>
            <button type="button" class="button button-primary" onclick="saveBulkStatus()">
                <?php _e('Status ändern', 'dienstplan-verwaltung'); ?>
            </button>
        </div>
    </div>
</div>

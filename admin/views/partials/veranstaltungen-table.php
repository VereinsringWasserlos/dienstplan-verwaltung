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
                        <th width="15%"><?php _e('Beteiligte Vereine', 'dienstplan-verwaltung'); ?></th>
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
                                <?php if (!empty($event_vereine)): ?>
                                    <?php foreach ($event_vereine as $verein): ?>
                                        <span class="status-badge status-aktiv" style="margin: 2px;">
                                            <?php echo esc_html($verein->verein_kuerzel ?? $verein->kuerzel); ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color: #999;">—</span>
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
                                <?php
                                $status_class = '';
                                $status_text = '';
                                switch($v->status) {
                                    case 'geplant':
                                        $status_class = 'status-aktiv';
                                        $status_text = __('Geplant', 'dienstplan-verwaltung');
                                        break;
                                    case 'aktiv':
                                        $status_class = 'status-aktiv';
                                        $status_text = __('Aktiv', 'dienstplan-verwaltung');
                                        break;
                                    case 'abgeschlossen':
                                        $status_class = 'status-inaktiv';
                                        $status_text = __('Abgeschlossen', 'dienstplan-verwaltung');
                                        break;
                                }
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td style="position: relative; overflow: visible;">
                                <div class="dropdown-actions">
                                    <button class="action-button" onclick="toggleActionDropdown(this, event)">
                                        <span class="dashicons dashicons-menu" style="font-size: 16px;"></span>
                                        <?php _e('Aktionen', 'dienstplan-verwaltung'); ?>
                                    </button>
                                    <div class="action-dropdown-menu">
                                        <a href="#" onclick="editVeranstaltung(<?php echo $v->id; ?>); return false;">
                                            <span class="dashicons dashicons-edit"></span>
                                            <span><?php _e('Bearbeiten', 'dienstplan-verwaltung'); ?></span>
                                        </a>
                                        <?php if (!empty($v->seite_id)): ?>
                                            <?php
                                            $page = get_post($v->seite_id);
                                            if ($page && $page->post_status === 'publish'):
                                                $page_url = get_permalink($v->seite_id);
                                            ?>
                                                <a href="<?php echo esc_url($page_url); ?>" target="_blank">
                                                    <span class="dashicons dashicons-external"></span>
                                                    <span><?php _e('Seite öffnen', 'dienstplan-verwaltung'); ?></span>
                                                </a>
                                                <a href="#" onclick="updatePageForEvent(<?php echo $v->id; ?>); return false;">
                                                    <span class="dashicons dashicons-update"></span>
                                                    <span><?php _e('Seite aktualisieren', 'dienstplan-verwaltung'); ?></span>
                                                </a>
                                                <a href="<?php echo admin_url('post.php?post=' . $v->seite_id . '&action=edit'); ?>">
                                                    <span class="dashicons dashicons-welcome-write-blog"></span>
                                                    <span><?php _e('Seite bearbeiten', 'dienstplan-verwaltung'); ?></span>
                                                </a>
                                            <?php else: ?>
                                                <a href="#" onclick="createPageForEvent(<?php echo $v->id; ?>); return false;">
                                                    <span class="dashicons dashicons-plus-alt"></span>
                                                    <span><?php _e('Seite neu erstellen', 'dienstplan-verwaltung'); ?></span>
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <a href="#" onclick="createPageForEvent(<?php echo $v->id; ?>); return false;">
                                                <span class="dashicons dashicons-plus-alt"></span>
                                                <span><?php _e('Seite erstellen', 'dienstplan-verwaltung'); ?></span>
                                            </a>
                                        <?php endif; ?>
                                        <a href="#" onclick="deleteVeranstaltung(<?php echo $v->id; ?>); return false;">
                                            <span class="dashicons dashicons-trash"></span>
                                            <span><?php _e('Löschen', 'dienstplan-verwaltung'); ?></span>
                                        </a>
                                    </div>
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
            
            if (!action || checked.length === 0) {
                alert('Bitte wählen Sie eine Aktion und mindestens eine Veranstaltung aus.');
                return;
            }
            
            console.log('Bulk action:', action, 'für Veranstaltungen:', checked);
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
});
</script>

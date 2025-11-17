<?php
/**
 * Vereine Tabelle Partial
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/admin/views/partials
 */

if (!defined('ABSPATH')) exit;
?>

<div class="vereine-list" style="margin-top: 2rem; overflow: visible; position: relative;">
    <div class="verein-gruppe" style="margin-bottom: 1.5rem; border: 1px solid #c3c4c7; border-radius: 4px; position: relative;">
        <!-- Einklappbarer Header -->
        <h3 class="verein-header-collapsible" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 1rem 1.5rem; margin: 0; display: flex; align-items: center; gap: 1rem; transition: all 0.3s; cursor: pointer;" onclick="toggleVereinGroup('alle-vereine')">
            <span class="dashicons dashicons-arrow-down-alt2 collapse-icon" id="icon-alle-vereine" style="transition: transform 0.3s; font-size: 20px;"></span>
            <span class="dashicons dashicons-flag" style="font-size: 24px;"></span>
            <strong style="font-size: 1.1rem;"><?php _e('Alle Vereine', 'dienstplan-verwaltung'); ?></strong>
            <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.9rem;">
                <?php echo count($vereine); ?> <?php echo count($vereine) != 1 ? 'Vereine' : 'Verein'; ?>
            </span>
            <div style="flex: 1;"></div>
            <button type="button" onclick="event.stopPropagation(); openVereinModal();" class="button button-primary" style="background: rgba(255,255,255,0.9); color: #3b82f6; border: none; padding: 0.5rem 1rem; font-weight: 600;">
                <span class="dashicons dashicons-plus-alt" style="font-size: 16px;"></span>
                <?php _e('Neuer Verein', 'dienstplan-verwaltung'); ?>
            </button>
        </h3>
        
        <!-- Einklappbarer Content -->
        <div id="alle-vereine" class="verein-content" style="display: block;">
            <!-- Bulk-Aktionen Toolbar -->
            <div class="bulk-actions-toolbar" style="background: #f9fafb; padding: 1rem; border: 1px solid #e5e7eb; border-bottom: none; display: none;">
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span class="selected-count" style="color: #6b7280;">
                        <span class="count">0</span> <?php _e('ausgewählt', 'dienstplan-verwaltung'); ?>
                    </span>
                    
                    <select class="bulk-action-select" style="min-width: 200px;">
                        <option value=""><?php _e('-- Aktion wählen --', 'dienstplan-verwaltung'); ?></option>
                        <option value="delete"><?php _e('Löschen', 'dienstplan-verwaltung'); ?></option>
                        <option value="activate"><?php _e('Aktivieren', 'dienstplan-verwaltung'); ?></option>
                        <option value="deactivate"><?php _e('Deaktivieren', 'dienstplan-verwaltung'); ?></option>
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
                            <input type="checkbox" class="select-all-vereine" style="margin: 0;">
                        </th>
                        <th width="20%"><?php _e('Name', 'dienstplan-verwaltung'); ?></th>
                        <th width="10%"><?php _e('Kürzel', 'dienstplan-verwaltung'); ?></th>
                        <th width="20%"><?php _e('Verantwortliche', 'dienstplan-verwaltung'); ?></th>
                        <th width="20%"><?php _e('Kontakt', 'dienstplan-verwaltung'); ?></th>
                        <th width="15%"><?php _e('Status', 'dienstplan-verwaltung'); ?></th>
                        <th width="15%"><?php _e('Aktionen', 'dienstplan-verwaltung'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vereine as $verein): ?>
                        <tr data-verein-id="<?php echo esc_attr($verein->id); ?>">
                            <td style="padding-left: 1rem;">
                                <input type="checkbox" class="verein-checkbox" value="<?php echo esc_attr($verein->id); ?>" style="margin: 0;">
                            </td>
                            <td>
                                <?php if (!empty($verein->logo_id)): 
                                    $logo_url = wp_get_attachment_url($verein->logo_id);
                                    if ($logo_url): ?>
                                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($verein->name); ?>" style="max-width: 40px; max-height: 40px; border-radius: 4px; vertical-align: middle;">
                                    <?php else: ?>
                                        <span class="dashicons dashicons-admin-multisite" style="font-size: 32px; color: #3b82f6;"></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-admin-multisite" style="font-size: 32px; color: #3b82f6;"></span>
                                <?php endif; ?>
                                <strong style="vertical-align: middle; margin-left: 0.5rem;"><?php echo esc_html($verein->name); ?></strong>
                                <?php if ($verein->beschreibung): ?>
                                    <br><small style="margin-left: 3rem;"><?php echo esc_html($verein->beschreibung); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?php echo esc_html($verein->kuerzel); ?></code>
                            </td>
                            <td>
                                <?php
                                $verantwortliche = $db->get_verein_verantwortliche($verein->id);
                                
                                if (!empty($verantwortliche)) {
                                    $namen = array();
                                    foreach ($verantwortliche as $person) {
                                        $user = get_user_by('id', $person->user_id);
                                        if ($user) {
                                            $namen[] = esc_html($user->display_name);
                                        }
                                    }
                                    echo implode(', ', $namen);
                                } else {
                                    echo $verein->kontakt_name ? esc_html($verein->kontakt_name) : '—';
                                }
                                
                                if ($verein->kontakt_telefon): ?>
                                    <br><small>📞 <?php echo esc_html($verein->kontakt_telefon); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                if (!empty($verantwortliche)) {
                                    $emails = array();
                                    foreach ($verantwortliche as $person) {
                                        $user = get_user_by('id', $person->user_id);
                                        if ($user) {
                                            $emails[] = sprintf(
                                                '<a href="mailto:%s">%s</a>',
                                                esc_attr($user->user_email),
                                                esc_html($user->user_email)
                                            );
                                        }
                                    }
                                    echo implode('<br>', $emails);
                                } else {
                                    if ($verein->kontakt_email): ?>
                                        <a href="mailto:<?php echo esc_attr($verein->kontakt_email); ?>">
                                            <?php echo esc_html($verein->kontakt_email); ?>
                                        </a>
                                    <?php else: ?>
                                        —
                                    <?php endif;
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($verein->aktiv): ?>
                                    <span class="status-badge status-aktiv">✓ <?php _e('Aktiv', 'dienstplan-verwaltung'); ?></span>
                                <?php else: ?>
                                    <span class="status-badge status-inaktiv">✗ <?php _e('Inaktiv', 'dienstplan-verwaltung'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="position: relative; overflow: visible;">
                                <div class="dropdown-actions">
                                    <button class="action-button" onclick="toggleActionDropdown(this, event)">
                                        <span class="dashicons dashicons-menu" style="font-size: 16px;"></span>
                                        <?php _e('Aktionen', 'dienstplan-verwaltung'); ?>
                                    </button>
                                    <div class="action-dropdown-menu">
                                        <a href="#" onclick="editVerein(<?php echo $verein->id; ?>); return false;">
                                            <span class="dashicons dashicons-edit"></span>
                                            <?php _e('Bearbeiten', 'dienstplan-verwaltung'); ?>
                                        </a>
                                        <a href="#" onclick="deleteVerein(<?php echo $verein->id; ?>); return false;">
                                            <span class="dashicons dashicons-trash"></span>
                                            <?php _e('Löschen', 'dienstplan-verwaltung'); ?>
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
// toggleActionDropdown(), toggleVereinGroup() und Dropdown-Event-Listener sind jetzt in dp-admin.js definiert

// Checkbox-Logik
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.querySelector('.select-all-vereine');
    const checkboxes = document.querySelectorAll('.verein-checkbox');
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
        const checked = document.querySelectorAll('.verein-checkbox:checked').length;
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
            const checked = Array.from(document.querySelectorAll('.verein-checkbox:checked')).map(cb => cb.value);
            
            if (!action || checked.length === 0) {
                alert('Bitte wählen Sie eine Aktion und mindestens einen Verein aus.');
                return;
            }
            
            // Implementierung der Bulk-Aktionen
            console.log('Bulk action:', action, 'für Vereine:', checked);
        });
    }
    
    // Cancel
    const cancelBtn = document.querySelector('.bulk-action-cancel');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            document.querySelectorAll('.verein-checkbox').forEach(cb => cb.checked = false);
            if (selectAll) selectAll.checked = false;
            updateBulkToolbar();
        });
    }
});
</script>

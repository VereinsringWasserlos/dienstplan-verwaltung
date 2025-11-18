<?php
/**
 * Mitarbeiter-Verwaltung
 * 
 * Erwartet: $db (Dienstplan_Database Instanz aus parent scope)
 */
if (!defined('ABSPATH')) exit;

// Mitarbeiter mit Statistiken laden (verwendet $db aus parent scope)
$mitarbeiter = $db->get_mitarbeiter_with_stats($filter_verein, $filter_veranstaltung, $search);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e('Mitarbeiter', 'dienstplan-verwaltung'); ?>
    </h1>
    
    <a href="#" class="page-title-action" onclick="openMitarbeiterModal(); return false;">
        <?php _e('Neuer Mitarbeiter', 'dienstplan-verwaltung'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['dp_message'])): ?>
        <div class="notice notice-<?php echo esc_attr($_GET['dp_type'] ?? 'success'); ?> is-dismissible">
            <p><?php echo esc_html($_GET['dp_message']); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Filter -->
    <div class="tablenav top">
        <form method="get" style="display: inline-flex; gap: 0.5rem; align-items: center; margin-bottom: 1rem;">
            <input type="hidden" name="page" value="dienstplan-mitarbeiter">
            
            <label style="font-weight: 600;">
                <?php _e('Filter:', 'dienstplan-verwaltung'); ?>
            </label>
            
            <select name="filter_verein" style="width: 200px;">
                <option value=""><?php _e('-- Alle Vereine --', 'dienstplan-verwaltung'); ?></option>
                <?php foreach ($vereine as $v): ?>
                    <option value="<?php echo $v->id; ?>" <?php selected($filter_verein, $v->id); ?>>
                        <?php echo esc_html($v->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="filter_veranstaltung" style="width: 200px;">
                <option value=""><?php _e('-- Alle Veranstaltungen --', 'dienstplan-verwaltung'); ?></option>
                <?php foreach ($veranstaltungen as $ve): ?>
                    <option value="<?php echo $ve->id; ?>" <?php selected($filter_veranstaltung, $ve->id); ?>>
                        <?php echo esc_html($ve->name); ?>
                        (<?php echo date_i18n('d.m.Y', strtotime($ve->start_datum)); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="search" name="search" value="<?php echo esc_attr($search); ?>" 
                   placeholder="<?php _e('Name oder E-Mail suchen...', 'dienstplan-verwaltung'); ?>" 
                   style="width: 250px;">
            
            <button type="submit" class="button">
                <?php _e('Filtern', 'dienstplan-verwaltung'); ?>
            </button>
            
            <?php if ($filter_verein || $filter_veranstaltung || $search): ?>
                <a href="?page=dienstplan-mitarbeiter" class="button">
                    <?php _e('Filter zurücksetzen', 'dienstplan-verwaltung'); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Bulk Actions -->
    <div class="tablenav top" style="margin-bottom: 1rem;">
        <div class="alignleft actions bulkactions">
            <select id="bulk-action-selector-top" name="action">
                <option value="-1"><?php _e('Massenaktionen', 'dienstplan-verwaltung'); ?></option>
                <option value="delete"><?php _e('Löschen', 'dienstplan-verwaltung'); ?></option>
            </select>
            <button type="button" class="button action" onclick="applyBulkAction()">
                <?php _e('Anwenden', 'dienstplan-verwaltung'); ?>
            </button>
        </div>
    </div>
    
    <!-- Mitarbeiter Tabelle -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 40px;" class="check-column">
                    <input type="checkbox" id="select-all-mitarbeiter" 
                           onchange="toggleAllMitarbeiter(this.checked)">
                </th>
                <th style="width: 40px;"><?php _e('ID', 'dienstplan-verwaltung'); ?></th>
                <th><?php _e('Name', 'dienstplan-verwaltung'); ?></th>
                <th><?php _e('E-Mail', 'dienstplan-verwaltung'); ?></th>
                <th><?php _e('Telefon', 'dienstplan-verwaltung'); ?></th>
                <th><?php _e('Vereine', 'dienstplan-verwaltung'); ?></th>
                <th style="width: 100px; text-align: center;"><?php _e('Dienste', 'dienstplan-verwaltung'); ?></th>
                <th style="width: 150px;"><?php _e('Aktionen', 'dienstplan-verwaltung'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($mitarbeiter)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2rem; color: #666;">
                        <?php _e('Keine Mitarbeiter gefunden.', 'dienstplan-verwaltung'); ?>
                        <?php if ($filter_verein || $filter_veranstaltung || $search): ?>
                            <br><br>
                            <a href="?page=dienstplan-mitarbeiter" class="button">
                                <?php _e('Filter zurücksetzen', 'dienstplan-verwaltung'); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($mitarbeiter as $ma): ?>
                    <tr>
                        <th class="check-column">
                            <input type="checkbox" class="mitarbeiter-checkbox" 
                                   value="<?php echo $ma->id; ?>"
                                   onchange="updateBulkActionButton()">
                        </th>
                        <td><code>#<?php echo $ma->id; ?></code></td>
                        <td>
                            <strong><?php echo esc_html($ma->vorname . ' ' . $ma->nachname); ?></strong>
                        </td>
                        <td>
                            <a href="mailto:<?php echo esc_attr($ma->email); ?>">
                                <?php echo esc_html($ma->email); ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($ma->telefon): ?>
                                <a href="tel:<?php echo esc_attr($ma->telefon); ?>">
                                    <?php echo esc_html($ma->telefon); ?>
                                </a>
                            <?php else: ?>
                                <span style="color: #999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($ma->vereine): ?>
                                <span style="font-size: 0.9em;"><?php echo esc_html($ma->vereine); ?></span>
                            <?php else: ?>
                                <span style="color: #999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge" style="padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85em; 
                                  background: <?php echo $ma->total_dienste > 0 ? '#e0f2fe' : '#f3f4f6'; ?>; 
                                  color: <?php echo $ma->total_dienste > 0 ? '#0369a1' : '#6b7280'; ?>;">
                                <?php echo intval($ma->total_dienste); ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="button button-small" 
                                    onclick="viewMitarbeiterDienste(<?php echo $ma->id; ?>)"
                                    title="<?php _e('Dienste anzeigen', 'dienstplan-verwaltung'); ?>">
                                <span class="dashicons dashicons-list-view" style="font-size: 1rem; margin-top: 3px;"></span>
                            </button>
                            <button type="button" class="button button-small" 
                                    onclick="editMitarbeiter(<?php echo $ma->id; ?>)"
                                    title="<?php _e('Bearbeiten', 'dienstplan-verwaltung'); ?>">
                                <span class="dashicons dashicons-edit" style="font-size: 1rem; margin-top: 3px;"></span>
                            </button>
                            <button type="button" class="button button-small" 
                                    onclick="deleteMitarbeiter(<?php echo $ma->id; ?>)"
                                    title="<?php _e('Löschen', 'dienstplan-verwaltung'); ?>"
                                    style="color: #b91c1c;">
                                <span class="dashicons dashicons-trash" style="font-size: 1rem; margin-top: 3px;"></span>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <p style="margin-top: 1rem; color: #666; font-size: 0.9em;">
        <?php printf(
            _n('Insgesamt %d Mitarbeiter', 'Insgesamt %d Mitarbeiter', count($mitarbeiter), 'dienstplan-verwaltung'),
            count($mitarbeiter)
        ); ?>
    </p>
</div>

<!-- JavaScript moved to assets/js/dp-mitarbeiter.js -->
<?php 
// Modals einbinden
include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/mitarbeiter-modal.php';
include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/mitarbeiter-dienste-modal.php';
?>

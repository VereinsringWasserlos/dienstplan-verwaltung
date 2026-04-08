<?php
/**
 * Dienste Verwaltung
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/admin/views
 */

if (!defined('ABSPATH')) exit;

$is_restricted_club_admin = Dienstplan_Roles::is_restricted_club_admin();

// Filter-Parameter
$selected_veranstaltung = isset($_GET['veranstaltung']) ? intval($_GET['veranstaltung']) : 0;
$selected_verein = isset($_GET['verein']) ? intval($_GET['verein']) : 0;
$filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$filter_mitarbeiter = isset($_GET['mitarbeiter']) ? intval($_GET['mitarbeiter']) : 0;
$filter_mitarbeiter_name = '';

$mitarbeiter_filter_liste = $db->get_mitarbeiter_with_stats(
    $selected_verein,
    $selected_veranstaltung,
    '',
    $scoped_verein_ids
);

if ($filter_mitarbeiter > 0) {
    $filter_mitarbeiter_obj = $db->get_mitarbeiter($filter_mitarbeiter);
    if ($filter_mitarbeiter_obj) {
        $filter_mitarbeiter_name = trim(($filter_mitarbeiter_obj->vorname ?? '') . ' ' . ($filter_mitarbeiter_obj->nachname ?? ''));
    } else {
        $filter_mitarbeiter = 0;
    }
}

// Dienste laden (mit Filtern)
$dienste = array();
$scoped_verein_ids = isset($allowed_verein_ids) && is_array($allowed_verein_ids)
    ? array_values(array_filter(array_map('intval', $allowed_verein_ids)))
    : array();
if ($selected_veranstaltung > 0) {
    $all_dienste = $db->get_dienste($selected_veranstaltung, $selected_verein, null, $scoped_verein_ids);

    if ($filter_mitarbeiter > 0) {
        $all_dienste = array_filter($all_dienste, function($d) use ($db, $filter_mitarbeiter) {
            $slots = $db->get_dienst_slots($d->id);
            foreach ($slots as $slot) {
                if (intval($slot->mitarbeiter_id ?? 0) === $filter_mitarbeiter) {
                    return true;
                }
            }
            return false;
        });
    }
    
    // Status-Filter anwenden
    if ($filter_status === 'unvollstaendig') {
        $dienste = array_filter($all_dienste, function($d) {
            return isset($d->status) && $d->status === 'unvollstaendig';
        });
    } else {
        $dienste = $all_dienste;
    }
}

// Setup für Page-Header Partial
$page_title = __('Dienste', 'dienstplan-verwaltung');
$page_icon = 'dashicons-clipboard';
$page_class = 'header-dienste';
$nav_items = [
    [
        'label' => __('Dashboard', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan'),
        'icon' => 'dashicons-dashboard',
    ],
    [
        'label' => __('Vereine', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-vereine'),
        'icon' => 'dashicons-flag',
    ],
    [
        'label' => __('Veranstaltungen', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-veranstaltungen'),
        'icon' => 'dashicons-calendar-alt',
    ],
    [
        'label' => __('Mitarbeiter', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-mitarbeiter'),
        'icon' => 'dashicons-groups',
    ],
    [
        'label' => __('Bereiche & Tätigkeiten', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-bereiche'),
        'icon' => 'dashicons-category',
    ],
];
?>

<div class="wrap dienstplan-wrap" style="overflow: visible; position: relative;">
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/page-header.php'; ?>
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['dp_message'])): ?>
        <div class="notice notice-<?php echo esc_attr($_GET['dp_type'] ?? 'success'); ?> is-dismissible">
            <p><?php echo esc_html($_GET['dp_message']); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Filter-Bereich -->
    <div class="dp-filter-bar" style="background: #fff; padding: 1.5rem; border: 1px solid #c3c4c7; border-radius: 4px; margin: 1.5rem 0;">
        <h3 style="margin-top: 0;">
            <span class="dashicons dashicons-filter"></span>
            <?php _e('Filter', 'dienstplan-verwaltung'); ?>
        </h3>

        <?php if ($filter_mitarbeiter > 0): ?>
            <div class="notice notice-info" style="margin: 0 0 1rem 0; padding: 0.75rem 1rem;">
                <p style="margin: 0; display: flex; align-items: center; gap: 0.75rem;">
                    <span class="dashicons dashicons-admin-users"></span>
                    <strong><?php _e('Mitarbeiter-Filter aktiv:', 'dienstplan-verwaltung'); ?></strong>
                    <span><?php echo esc_html($filter_mitarbeiter_name ?: ('#' . $filter_mitarbeiter)); ?></span>
                    <?php
                    $clear_filter_url = add_query_arg(array(
                        'page' => 'dienstplan-dienste',
                        'veranstaltung' => $selected_veranstaltung,
                        'verein' => $selected_verein,
                        'status' => $filter_status,
                    ), admin_url('admin.php'));
                    ?>
                    <a href="<?php echo esc_url($clear_filter_url); ?>" class="button button-small" style="margin-left: auto;">
                        <?php _e('Mitarbeiter-Filter entfernen', 'dienstplan-verwaltung'); ?>
                    </a>
                </p>
            </div>
        <?php endif; ?>
        
        <form method="get" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <input type="hidden" name="page" value="dienstplan-dienste">
            <?php if ($filter_mitarbeiter > 0): ?>
                <input type="hidden" name="mitarbeiter" value="<?php echo intval($filter_mitarbeiter); ?>">
            <?php endif; ?>
            
            <div style="flex: 1; min-width: 250px;">
                <label for="filter-veranstaltung" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Veranstaltung', 'dienstplan-verwaltung'); ?> *
                </label>
                <select id="filter-veranstaltung" name="veranstaltung" class="regular-text" style="width: 100%;" required>
                    <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                    <?php foreach ($veranstaltungen as $v): ?>
                        <option value="<?php echo $v->id; ?>" <?php selected($selected_veranstaltung, $v->id); ?>>
                            <?php echo esc_html($v->name); ?>
                            (<?php echo date_i18n('d.m.Y', strtotime($v->start_datum)); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="flex: 1; min-width: 200px;">
                <label for="filter-verein" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Verein', 'dienstplan-verwaltung'); ?>
                </label>
                <select id="filter-verein" name="verein" class="regular-text" style="width: 100%;">
                    <option value=""><?php _e('-- Alle --', 'dienstplan-verwaltung'); ?></option>
                    <?php foreach ($vereine as $v): ?>
                        <option value="<?php echo $v->id; ?>" <?php selected($selected_verein, $v->id); ?>>
                            <?php echo esc_html($v->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="flex: 0 0 180px;">
                <label for="filter-status" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Status', 'dienstplan-verwaltung'); ?>
                </label>
                <select id="filter-status" name="status" class="regular-text" style="width: 100%;">
                    <option value=""><?php _e('-- Alle --', 'dienstplan-verwaltung'); ?></option>
                    <option value="unvollstaendig" <?php selected($filter_status, 'unvollstaendig'); ?>>
                        ⚠️ <?php _e('Nur Unvollständige', 'dienstplan-verwaltung'); ?>
                    </option>
                </select>
            </div>

            <div style="flex: 1; min-width: 240px;">
                <label for="filter-mitarbeiter" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Mitarbeiter', 'dienstplan-verwaltung'); ?>
                </label>
                <select id="filter-mitarbeiter" name="mitarbeiter" class="regular-text" style="width: 100%;">
                    <option value=""><?php _e('-- Alle --', 'dienstplan-verwaltung'); ?></option>
                    <?php foreach ((array) $mitarbeiter_filter_liste as $mf): ?>
                        <option value="<?php echo intval($mf->id); ?>" <?php selected($filter_mitarbeiter, intval($mf->id)); ?>>
                            <?php echo esc_html(trim(($mf->vorname ?? '') . ' ' . ($mf->nachname ?? ''))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Filtern', 'dienstplan-verwaltung'); ?>
                </button>
                <?php if ($selected_veranstaltung > 0 || $selected_verein > 0 || $filter_status !== '' || $filter_mitarbeiter > 0): ?>
                    <a href="?page=dienstplan-dienste" class="button">
                        <?php _e('Filter zurücksetzen', 'dienstplan-verwaltung'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <?php if ($selected_veranstaltung > 0): ?>
        <?php if (empty($dienste)): ?>
            <?php include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/dienste-empty.php'; ?>
        <?php else: ?>
            <?php 
            // Berechne Statistiken
            $unvollstaendige_count = 0;
            foreach ($dienste as $d) {
                if (isset($d->status) && $d->status === 'unvollstaendig') {
                    $unvollstaendige_count++;
                }
            }
            
            // Zeige Warnung wenn unvollständige Dienste vorhanden sind
            if ($unvollstaendige_count > 0 && $filter_status !== 'unvollstaendig'): ?>
                <div class="notice notice-warning" style="margin: 1.5rem 0; padding: 1rem; background: #fef3c7; border-left: 4px solid #f59e0b;">
                    <p style="margin: 0;">
                        <span class="dashicons dashicons-warning" style="color: #f59e0b; font-size: 1.2em; vertical-align: middle;"></span>
                        <strong><?php echo $unvollstaendige_count; ?> <?php _e('Dienst(e) mit unvollständigen Daten gefunden', 'dienstplan-verwaltung'); ?></strong>
                        <br>
                        <span style="margin-left: 2rem;">
                            <?php _e('Diese Dienste wurden beim Import mit fehlenden Informationen erstellt und sollten vervollständigt werden.', 'dienstplan-verwaltung'); ?>
                        </span>
                        <br>
                        <a href="?page=dienstplan-dienste&veranstaltung=<?php echo $selected_veranstaltung; ?>&status=unvollstaendig" class="button button-small" style="margin-top: 0.5rem; margin-left: 2rem; background: #f59e0b; color: #fff; border-color: #f59e0b;">
                            <span class="dashicons dashicons-filter"></span>
                            <?php _e('Nur unvollständige anzeigen', 'dienstplan-verwaltung'); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/dienste-table.php'; ?>
        <?php endif; ?>
    <?php else: ?>
        <div class="notice notice-info" style="margin-top: 2rem;">
            <p>
                <strong><?php _e('Keine Veranstaltung ausgewählt', 'dienstplan-verwaltung'); ?></strong><br>
                <?php _e('Bitte wählen Sie eine Veranstaltung aus, um die zugehörigen Dienste anzuzeigen und zu verwalten.', 'dienstplan-verwaltung'); ?>
            </p>
        </div>
    <?php endif; ?>
    
</div>

<?php 
// Modals einbinden
include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/dienst-modal.php';
include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/besetzung-modal.php';
include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/bulk-update-modals.php';
?>

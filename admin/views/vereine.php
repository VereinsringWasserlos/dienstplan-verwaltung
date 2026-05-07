<?php
/**
 * Vereine-Verwaltung Template
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/admin/views
 */

if (!defined('ABSPATH')) exit;

$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

if (!empty($vereine) && ($search !== '' || $filter_status !== '')) {
    $vereine = array_values(array_filter($vereine, function ($verein) use ($search, $filter_status) {
        if ($filter_status === 'active' && empty($verein->aktiv)) {
            return false;
        }
        if ($filter_status === 'inactive' && !empty($verein->aktiv)) {
            return false;
        }

        if ($search !== '') {
            $haystack = strtolower(trim((string) ($verein->name ?? '') . ' ' . (string) ($verein->kuerzel ?? '') . ' ' . (string) ($verein->beschreibung ?? '')));
            if (strpos($haystack, strtolower($search)) === false) {
                return false;
            }
        }

        return true;
    }));
}

// Setup für Page-Header Partial
$page_title = __('Vereine', 'dienstplan-verwaltung');
$page_icon = 'dashicons-flag';
$page_class = 'header-vereine';
$nav_items = [
    [
        'label' => __('Dashboard', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan'),
        'icon' => 'dashicons-dashboard',
    ],
    [
        'label' => __('Veranstaltungen', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-veranstaltungen'),
        'icon' => 'dashicons-calendar-alt',
    ],
    [
        'label' => __('Dienste', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-dienste'),
        'icon' => 'dashicons-clipboard',
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

<div class="wrap dienstplan-wrap">
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/page-header.php'; ?>
    
    <hr class="wp-header-end">

    <div class="dp-filter-bar" style="background: #fff; padding: 1.5rem; border: 1px solid #c3c4c7; border-radius: 4px; margin: 1.5rem 0;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 0.75rem;">
            <h3 style="margin: 0;">
                <span class="dashicons dashicons-filter"></span>
                <?php _e('Filter', 'dienstplan-verwaltung'); ?>
            </h3>
            <div class="dp-filter-action-group">
                <button type="button" class="button button-primary dp-filter-action-btn" onclick="openVereinModal(); return false;">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Neuer Verein', 'dienstplan-verwaltung'); ?>
                </button>
                <button type="button" class="button button-primary dp-open-import-popup dp-filter-action-btn" data-import-type="vereine">
                    <span class="dashicons dashicons-upload"></span>
                    <?php _e('Vereine importieren', 'dienstplan-verwaltung'); ?>
                </button>
            </div>
        </div>

        <form method="get" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <input type="hidden" name="page" value="dienstplan-vereine">

            <div style="flex: 1; min-width: 260px;">
                <label for="filter-search" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Suche', 'dienstplan-verwaltung'); ?>
                </label>
                <input type="search" id="filter-search" name="search" value="<?php echo esc_attr($search); ?>" class="regular-text" style="width: 100%;" placeholder="<?php esc_attr_e('Name, Kürzel oder Beschreibung', 'dienstplan-verwaltung'); ?>">
            </div>

            <div style="flex: 0 0 220px;">
                <label for="filter-status" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Status', 'dienstplan-verwaltung'); ?>
                </label>
                <select id="filter-status" name="status" class="regular-text" style="width: 100%;">
                    <option value=""><?php _e('-- Alle --', 'dienstplan-verwaltung'); ?></option>
                    <option value="active" <?php selected($filter_status, 'active'); ?>><?php _e('Aktiv', 'dienstplan-verwaltung'); ?></option>
                    <option value="inactive" <?php selected($filter_status, 'inactive'); ?>><?php _e('Inaktiv', 'dienstplan-verwaltung'); ?></option>
                </select>
            </div>

            <div>
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Filtern', 'dienstplan-verwaltung'); ?>
                </button>
                <?php if ($search !== '' || $filter_status !== ''): ?>
                    <a href="?page=dienstplan-vereine" class="button"><?php _e('Zurücksetzen', 'dienstplan-verwaltung'); ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/vereine-header.php'; ?>
    
    <?php if (!empty($vereine)): ?>
        <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/vereine-table.php'; ?>
    <?php else: ?>
        <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/vereine-empty.php'; ?>
    <?php endif; ?>
    
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/vereine-modal.php'; ?>
</div>

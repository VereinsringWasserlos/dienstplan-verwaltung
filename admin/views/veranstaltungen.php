<?php

/**
 * Veranstaltungen-Verwaltung Template
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/admin/views
 */

if (!defined('ABSPATH')) exit;

$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$filter_typ = isset($_GET['typ']) ? sanitize_text_field($_GET['typ']) : '';

if (!empty($veranstaltungen) && ($search !== '' || $filter_status !== '' || $filter_typ !== '')) {
    $veranstaltungen = array_values(array_filter($veranstaltungen, function ($veranstaltung) use ($search, $filter_status, $filter_typ) {
        if ($filter_status !== '' && (string) ($veranstaltung->status ?? '') !== $filter_status) {
            return false;
        }

        if ($filter_typ !== '' && (string) ($veranstaltung->typ ?? '') !== $filter_typ) {
            return false;
        }

        if ($search !== '') {
            $haystack = strtolower(trim((string) ($veranstaltung->name ?? '') . ' ' . (string) ($veranstaltung->beschreibung ?? '')));
            if (strpos($haystack, strtolower($search)) === false) {
                return false;
            }
        }

        return true;
    }));
}

// Setup für Page-Header Partial
$page_title = __('Veranstaltungen', 'dienstplan-verwaltung');
$page_icon = 'dashicons-calendar-alt';
$page_class = 'header-veranstaltungen';
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
                <button type="button" class="button button-primary dp-filter-action-btn" onclick="openVeranstaltungModal(); return false;">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Neue Veranstaltung', 'dienstplan-verwaltung'); ?>
                </button>
                <button type="button" class="button button-primary dp-open-import-popup dp-filter-action-btn" data-import-type="veranstaltungen">
                    <span class="dashicons dashicons-upload"></span>
                    <?php _e('Veranstaltungen importieren', 'dienstplan-verwaltung'); ?>
                </button>
            </div>
        </div>

        <form method="get" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <input type="hidden" name="page" value="dienstplan-veranstaltungen">

            <div style="flex: 1; min-width: 260px;">
                <label for="filter-search" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Suche', 'dienstplan-verwaltung'); ?>
                </label>
                <input type="search" id="filter-search" name="search" value="<?php echo esc_attr($search); ?>" class="regular-text" style="width: 100%;" placeholder="<?php esc_attr_e('Name oder Beschreibung', 'dienstplan-verwaltung'); ?>">
            </div>

            <div style="flex: 0 0 200px;">
                <label for="filter-status" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Status', 'dienstplan-verwaltung'); ?>
                </label>
                <select id="filter-status" name="status" class="regular-text" style="width: 100%;">
                    <option value=""><?php _e('-- Alle --', 'dienstplan-verwaltung'); ?></option>
                    <option value="in_planung" <?php selected($filter_status, 'in_planung'); ?>><?php _e('In Planung', 'dienstplan-verwaltung'); ?></option>
                    <option value="geplant" <?php selected($filter_status, 'geplant'); ?>><?php _e('Geplant', 'dienstplan-verwaltung'); ?></option>
                    <option value="aktiv" <?php selected($filter_status, 'aktiv'); ?>><?php _e('Aktiv', 'dienstplan-verwaltung'); ?></option>
                    <option value="abgeschlossen" <?php selected($filter_status, 'abgeschlossen'); ?>><?php _e('Abgeschlossen', 'dienstplan-verwaltung'); ?></option>
                </select>
            </div>

            <div style="flex: 0 0 200px;">
                <label for="filter-typ" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Typ', 'dienstplan-verwaltung'); ?>
                </label>
                <select id="filter-typ" name="typ" class="regular-text" style="width: 100%;">
                    <option value=""><?php _e('-- Alle --', 'dienstplan-verwaltung'); ?></option>
                    <option value="eintaegig" <?php selected($filter_typ, 'eintaegig'); ?>><?php _e('Eintägig', 'dienstplan-verwaltung'); ?></option>
                    <option value="mehrtaegig" <?php selected($filter_typ, 'mehrtaegig'); ?>><?php _e('Mehrtägig', 'dienstplan-verwaltung'); ?></option>
                </select>
            </div>

            <div>
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Filtern', 'dienstplan-verwaltung'); ?>
                </button>
                <?php if ($search !== '' || $filter_status !== '' || $filter_typ !== ''): ?>
                    <a href="?page=dienstplan-veranstaltungen" class="button"><?php _e('Zurücksetzen', 'dienstplan-verwaltung'); ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/veranstaltungen-header.php'; ?>

    <?php if (!empty($veranstaltungen)): ?>
        <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/veranstaltungen-table.php'; ?>
    <?php else: ?>
        <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/veranstaltungen-empty.php'; ?>
    <?php endif; ?>

    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/veranstaltungen-modal.php'; ?>
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/veranstaltung-konfiguration-modal.php'; ?>

    <!-- Neuer Kontakt Modal (für Verantwortliche) -->
    <div id="new-contact-modal" class="dp-modal" style="display: none;">
        <div class="dp-modal-content" style="max-width: 500px;">
            <div class="dp-modal-header">
                <h2><?php _e('Neuer Kontakt anlegen', 'dienstplan-verwaltung'); ?></h2>
                <button class="dp-modal-close" onclick="closeNewContactModal()">&times;</button>
            </div>
            <div class="dp-modal-body">
                <form id="new-contact-form" onsubmit="return false;">
                    <table class="form-table">
                        <tr>
                            <th><label for="nc_name"><?php _e('Name', 'dienstplan-verwaltung'); ?> *</label></th>
                            <td>
                                <input type="text" id="nc_name" name="nc_name" class="regular-text" required>
                                <p class="description"><?php _e('Vor- und Nachname', 'dienstplan-verwaltung'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="nc_email"><?php _e('E-Mail', 'dienstplan-verwaltung'); ?> *</label></th>
                            <td>
                                <input type="email" id="nc_email" name="nc_email" class="regular-text" required>
                                <div id="nc-email-check-result" style="margin-top: 0.5rem;"></div>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="nc_role"><?php _e('Rolle', 'dienstplan-verwaltung'); ?></label></th>
                            <td>
                                <select id="nc_role" name="nc_role" class="regular-text">
                                    <option value=""><?php _e('Keine Rolle', 'dienstplan-verwaltung'); ?></option>
                                    <option value="<?php echo Dienstplan_Roles::ROLE_CLUB_ADMIN; ?>">
                                        <?php _e('Vereins-Admin', 'dienstplan-verwaltung'); ?>
                                    </option>
                                    <option value="<?php echo Dienstplan_Roles::ROLE_EVENT_ADMIN; ?>">
                                        <?php _e('Veranstaltungs-Admin', 'dienstplan-verwaltung'); ?>
                                    </option>
                                    <option value="<?php echo Dienstplan_Roles::ROLE_GENERAL_ADMIN; ?>">
                                        <?php _e('Haupt-Admin', 'dienstplan-verwaltung'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php _e('Optionale Dienstplan-Rolle für diesen Benutzer', 'dienstplan-verwaltung'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 1rem; margin-top: 1rem; border-radius: 4px;">
                        <p style="margin: 0; font-size: 0.9rem;">
                            <span class="dashicons dashicons-email" style="color: #856404;"></span>
                            <strong><?php _e('Automatischer Versand:', 'dienstplan-verwaltung'); ?></strong><br>
                            <?php _e('Der neue Benutzer erhält eine E-Mail mit einem Link zum Setzen des Passworts.', 'dienstplan-verwaltung'); ?>
                        </p>
                    </div>
                </form>
            </div>
            <div class="dp-modal-footer">
                <button type="button" class="button" onclick="closeNewContactModal()"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
                <button type="button" class="button button-primary" onclick="saveNewContact()">
                    <span class="dashicons dashicons-plus" style="margin-top: 3px;"></span>
                    <?php _e('Kontakt anlegen & einladen', 'dienstplan-verwaltung'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
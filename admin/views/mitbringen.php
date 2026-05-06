<?php

/**
 * Mitbringen Verwaltung (separat von Diensten)
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/admin/views
 */

if (!defined('ABSPATH')) exit;

$is_restricted_club_admin = Dienstplan_Roles::is_restricted_club_admin();

$selected_veranstaltung = isset($_GET['veranstaltung']) ? intval($_GET['veranstaltung']) : 0;
$selected_verein = isset($_GET['verein']) ? intval($_GET['verein']) : 0;
$filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$scoped_verein_ids = isset($allowed_verein_ids) && is_array($allowed_verein_ids)
    ? array_values(array_filter(array_map('intval', $allowed_verein_ids)))
    : array();

$mitbringen_items = array();
if ($selected_veranstaltung > 0) {
    $mitbringen_items = $db->get_mitbringen_items(
        $selected_veranstaltung,
        $selected_verein,
        $filter_status,
        $scoped_verein_ids
    );
}

$page_title = __('Mitbringen', 'dienstplan-verwaltung');
$page_icon = 'dashicons-cart';
$page_class = 'header-dienste';
$nav_items = [
    [
        'label' => __('Dashboard', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan'),
        'icon' => 'dashicons-dashboard',
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
        'label' => __('Veranstaltungen', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-veranstaltungen'),
        'icon' => 'dashicons-calendar-alt',
    ],
];

?>

<div class="wrap dienstplan-wrap" style="overflow: visible; position: relative;">
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/page-header.php'; ?>
    <hr class="wp-header-end">

    <div class="notice notice-info" style="margin: 1rem 0;">
        <p>
            <strong><?php _e('Hinweis:', 'dienstplan-verwaltung'); ?></strong>
            <?php _e('Mitbringen ist jetzt als eigener Bereich getrennt von Diensten. Diese Ansicht zeigt bereits migrierte Einträge.', 'dienstplan-verwaltung'); ?>
        </p>
    </div>

    <div class="dp-filter-bar" style="background: #fff; padding: 1.5rem; border: 1px solid #c3c4c7; border-radius: 4px; margin: 1.5rem 0;">
        <form method="get" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <input type="hidden" name="page" value="dienstplan-mitbringen">

            <div style="flex: 1; min-width: 250px;">
                <label for="filter-veranstaltung" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Veranstaltung', 'dienstplan-verwaltung'); ?> *
                </label>
                <select id="filter-veranstaltung" name="veranstaltung" class="regular-text" style="width: 100%;" required>
                    <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                    <?php foreach ($veranstaltungen as $v): ?>
                        <option value="<?php echo intval($v->id); ?>" <?php selected($selected_veranstaltung, intval($v->id)); ?>>
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
                        <option value="<?php echo intval($v->id); ?>" <?php selected($selected_verein, intval($v->id)); ?>>
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
                    <option value="offen" <?php selected($filter_status, 'offen'); ?>><?php _e('Offen', 'dienstplan-verwaltung'); ?></option>
                    <option value="vergeben" <?php selected($filter_status, 'vergeben'); ?>><?php _e('Vergeben', 'dienstplan-verwaltung'); ?></option>
                </select>
            </div>

            <div>
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Filtern', 'dienstplan-verwaltung'); ?>
                </button>
                <?php if ($selected_veranstaltung > 0 || $selected_verein > 0 || $filter_status !== ''): ?>
                    <a href="?page=dienstplan-mitbringen" class="button">
                        <?php _e('Filter zurücksetzen', 'dienstplan-verwaltung'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if ($selected_veranstaltung > 0): ?>
        <div class="postbox" style="padding: 0; border: 1px solid #c3c4c7;">
            <table class="wp-list-table widefat fixed striped" style="margin: 0;">
                <thead>
                    <tr>
                        <th><?php _e('Bezeichnung', 'dienstplan-verwaltung'); ?></th>
                        <th style="width: 110px;"><?php _e('Menge', 'dienstplan-verwaltung'); ?></th>
                        <th style="width: 220px;"><?php _e('Verein', 'dienstplan-verwaltung'); ?></th>
                        <th style="width: 140px;"><?php _e('Tag', 'dienstplan-verwaltung'); ?></th>
                        <th style="width: 120px;"><?php _e('Status', 'dienstplan-verwaltung'); ?></th>
                        <th><?php _e('Hinweis', 'dienstplan-verwaltung'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mitbringen_items)): ?>
                        <tr>
                            <td colspan="6" style="padding: 1rem;">
                                <?php _e('Keine Mitbringen-Einträge für die aktuelle Auswahl gefunden.', 'dienstplan-verwaltung'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($mitbringen_items as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($item->bezeichnung); ?></strong>
                                    <?php if (!empty($item->taetigkeit_name)): ?>
                                        <div style="color: #6b7280; font-size: 12px;"><?php echo esc_html($item->taetigkeit_name); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo intval($item->menge); ?>
                                    <?php if (!empty($item->einheit)): ?>
                                        <?php echo ' ' . esc_html($item->einheit); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($item->verein_name ?? ''); ?></td>
                                <td>
                                    <?php
                                    if (!empty($item->tag_datum)) {
                                        echo esc_html(date_i18n('d.m.Y', strtotime($item->tag_datum)));
                                    } else {
                                        echo '&mdash;';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (($item->status ?? 'offen') === 'vergeben'): ?>
                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 999px; background: #dcfce7; color: #166534; font-weight: 600;">vergeben</span>
                                    <?php else: ?>
                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 999px; background: #fef3c7; color: #92400e; font-weight: 600;">offen</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($item->hinweis ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

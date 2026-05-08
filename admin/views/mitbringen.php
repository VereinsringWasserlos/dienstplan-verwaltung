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

$mitbringen_bereiche = $db->get_mitbringen_bereich_liste(
    $selected_veranstaltung,
    $selected_verein,
    $scoped_verein_ids
);

$mitbringen_mitarbeiter = $db->get_mitarbeiter_with_stats($selected_verein, 0, '', $scoped_verein_ids);
$mitbringen_tage = $selected_veranstaltung > 0
    ? $db->get_veranstaltung_tage($selected_veranstaltung)
    : array();

$bulk_tag_options = array();
foreach ((array) $mitbringen_tage as $tag) {
    if (!empty($tag->tag_datum)) {
        $dp_ts_bt = strtotime($tag->tag_datum);
        $dp_wt_bt = ['1'=>'Mo','2'=>'Di','3'=>'Mi','4'=>'Do','5'=>'Fr','6'=>'Sa','7'=>'So'][date('N', $dp_ts_bt)] ?? '';
        $tag_datum = $dp_wt_bt . ' ' . date_i18n('d.m.Y', $dp_ts_bt);
    } else {
        $tag_datum = '';
    }
    $bulk_tag_options[] = array(
        'value' => intval($tag->id ?? 0),
        'label' => 'Tag ' . intval($tag->tag_nummer ?? 0) . ($tag_datum ? ': ' . $tag_datum : ''),
    );
}

$bulk_verein_options = array();
foreach ((array) $vereine as $verein) {
    $bulk_verein_options[] = array(
        'value' => intval($verein->id ?? 0),
        'label' => (string) ($verein->name ?? ''),
    );
}

$bulk_bereich_options = array();
foreach ((array) $mitbringen_bereiche as $mb_bereich) {
    $bulk_bereich_options[] = array(
        'value' => (string) $mb_bereich,
        'label' => (string) $mb_bereich,
    );
}

$bulk_person_options = array(
    array(
        'value' => '0',
        'label' => __('-- Keine Person zugeordnet --', 'dienstplan-verwaltung'),
    ),
);
foreach ((array) $mitbringen_mitarbeiter as $mitarbeiter) {
    $mitarbeiter_name = trim((string) ($mitarbeiter->vorname ?? '') . ' ' . (string) ($mitarbeiter->nachname ?? ''));
    if ($mitarbeiter_name === '') {
        $mitarbeiter_name = sprintf(__('Mitarbeiter #%d', 'dienstplan-verwaltung'), intval($mitarbeiter->id ?? 0));
    }
    $bulk_person_options[] = array(
        'value' => (string) intval($mitarbeiter->id ?? 0),
        'label' => $mitarbeiter_name,
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

    <div class="dp-filter-bar" style="background: #fff; padding: 1.5rem; border: 1px solid #c3c4c7; border-radius: 4px; margin: 1.5rem 0;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 0.75rem;">
            <h3 style="margin: 0;">
                <span class="dashicons dashicons-filter"></span>
                <?php _e('Filter', 'dienstplan-verwaltung'); ?>
            </h3>
            <div class="dp-filter-action-group">
                <?php if (!$is_restricted_club_admin): ?>
                    <button type="button" class="button button-primary dp-filter-action-btn" onclick="return openMitbringenModal();">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Neues Mitbringen', 'dienstplan-verwaltung'); ?>
                    </button>
                <?php endif; ?>
                <button type="button" class="button button-primary dp-open-import-popup dp-filter-action-btn" data-import-type="dienste">
                    <span class="dashicons dashicons-upload"></span>
                    <?php _e('Mitbringen importieren', 'dienstplan-verwaltung'); ?>
                </button>
            </div>
        </div>

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
                            (<?php 
                                $dp_ts_ev1 = strtotime($v->start_datum);
                                $dp_wt_ev1 = ['1'=>'Mo','2'=>'Di','3'=>'Mi','4'=>'Do','5'=>'Fr','6'=>'Sa','7'=>'So'][date('N', $dp_ts_ev1)] ?? '';
                                echo $dp_wt_ev1 . ' ' . date_i18n('d.m.Y', $dp_ts_ev1);
                            ?>)
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
            <h3 style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 1rem 1.5rem; margin: 0; display: flex; align-items: center; gap: 1rem;">
                <span class="dashicons dashicons-cart" style="font-size: 20px;"></span>
                <strong><?php _e('Alle Mitbringen-Einträge', 'dienstplan-verwaltung'); ?></strong>
                <span style="background: rgba(255,255,255,0.22); padding: 0.2rem 0.65rem; border-radius: 999px; font-size: 0.85rem; font-weight: 600;">
                    <?php echo count($mitbringen_items); ?>
                </span>
                <?php if (!$is_restricted_club_admin): ?>
                    <div style="margin-left: auto;">
                        <button type="button" class="button dp-header-ghost-button" onclick="return openMitbringenModal();">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Neu', 'dienstplan-verwaltung'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </h3>
            <?php if (!$is_restricted_club_admin): ?>
                <div class="bulk-actions-toolbar dp-mitbringen-bulk-toolbar" style="background: #f9fafb; padding: 1rem; border: 1px solid #e5e7eb; border-bottom: none; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <span class="selected-count" style="color: #6b7280;">
                        <span class="count">0</span> <?php _e('ausgewählt', 'dienstplan-verwaltung'); ?>
                    </span>

                    <select class="bulk-action-select" style="min-width: 220px;">
                        <option value=""><?php _e('-- Aktion wählen --', 'dienstplan-verwaltung'); ?></option>
                        <option value="change_tag"><?php _e('Tag ändern', 'dienstplan-verwaltung'); ?></option>
                        <option value="change_verein"><?php _e('Verein ändern', 'dienstplan-verwaltung'); ?></option>
                        <option value="change_bereich"><?php _e('Bereich ändern', 'dienstplan-verwaltung'); ?></option>
                        <option value="change_person"><?php _e('Person ändern', 'dienstplan-verwaltung'); ?></option>
                        <option value="change_status"><?php _e('Status ändern', 'dienstplan-verwaltung'); ?></option>
                        <option value="change_admin_only"><?php _e('Admin-only ändern', 'dienstplan-verwaltung'); ?></option>
                        <option value="delete"><?php _e('Löschen', 'dienstplan-verwaltung'); ?></option>
                    </select>

                    <span class="bulk-action-value-wrap" style="display:none;">
                        <select class="bulk-action-value" style="min-width: 240px;"></select>
                    </span>

                    <button type="button" class="button bulk-action-apply" disabled>
                        <?php _e('Anwenden', 'dienstplan-verwaltung'); ?>
                    </button>

                    <button type="button" class="button bulk-action-cancel">
                        <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
                    </button>
                </div>
            <?php endif; ?>
            <table class="wp-list-table widefat fixed striped" style="margin: 0;">
                <thead>
                    <tr>
                        <?php if (!$is_restricted_club_admin): ?>
                            <th style="width: 44px; padding-left: 1rem;">
                                <input type="checkbox" class="select-all-mitbringen" style="margin: 0;">
                            </th>
                        <?php endif; ?>
                        <th><?php _e('Bezeichnung', 'dienstplan-verwaltung'); ?></th>
                        <th style="width: 190px;"><?php _e('Bereich', 'dienstplan-verwaltung'); ?></th>
                        <th style="width: 110px;"><?php _e('Menge', 'dienstplan-verwaltung'); ?></th>
                        <th style="width: 220px;"><?php _e('Verein', 'dienstplan-verwaltung'); ?></th>
                        <th style="width: 160px;"><?php _e('Tag', 'dienstplan-verwaltung'); ?></th>
                        <th style="width: 220px;"><?php _e('Person', 'dienstplan-verwaltung'); ?></th>
                        <th style="width: 120px;"><?php _e('Status', 'dienstplan-verwaltung'); ?></th>
                        <th><?php _e('Hinweis', 'dienstplan-verwaltung'); ?></th>
                        <?php if (!$is_restricted_club_admin): ?>
                            <th style="width: 160px;"><?php _e('Aktionen', 'dienstplan-verwaltung'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mitbringen_items)): ?>
                        <tr>
                            <td colspan="<?php echo $is_restricted_club_admin ? '8' : '10'; ?>" style="padding: 1rem;">
                                <?php _e('Keine Mitbringen-Einträge für die aktuelle Auswahl gefunden.', 'dienstplan-verwaltung'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($mitbringen_items as $item): ?>
                            <tr data-mitbringen-id="<?php echo intval($item->id ?? 0); ?>">
                                <?php if (!$is_restricted_club_admin): ?>
                                    <td style="padding-left: 1rem;">
                                        <input type="checkbox" class="mitbringen-checkbox" value="<?php echo intval($item->id ?? 0); ?>" style="margin: 0;">
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <strong><?php echo esc_html($item->bezeichnung); ?></strong>
                                </td>
                                <td><?php echo esc_html($item->mitbringen_bereich_name ?? ''); ?></td>
                                <td>
                                    <?php echo intval($item->menge); ?>
                                </td>
                                <td><?php echo esc_html($item->verein_kuerzel ?? $item->verein_name ?? ''); ?></td>
                                <td>
                                    <?php
                                    if (!empty($item->tag_datum)) {
                                        $dp_ts_mb = strtotime($item->tag_datum);
                                        $dp_wt_mb = ['1'=>'Mo','2'=>'Di','3'=>'Mi','4'=>'Do','5'=>'Fr','6'=>'Sa','7'=>'So'][date('N', $dp_ts_mb)] ?? '';
                                        echo esc_html($dp_wt_mb . ' ' . date_i18n('d.m.Y', $dp_ts_mb));
                                    } else {
                                        echo '&mdash;';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty(trim((string) ($item->mitarbeiter_name ?? '')))): ?>
                                        <?php echo esc_html(trim((string) $item->mitarbeiter_name)); ?>
                                    <?php else: ?>
                                        &mdash;
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($item->status ?? 'offen') === 'vergeben'): ?>
                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 999px; background: #dcfce7; color: #166534; font-weight: 600;">vergeben</span>
                                    <?php else: ?>
                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 999px; background: #fef3c7; color: #92400e; font-weight: 600;">offen</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo esc_html($item->hinweis ?? ''); ?>
                                    <?php if (!empty($item->besetzung_info ?? '')): ?>
                                        <div style="margin-top: 0.25rem; color: #475569; font-size: 12px;">
                                            <strong><?php _e('Besetzungsinfo:', 'dienstplan-verwaltung'); ?></strong>
                                            <?php echo esc_html($item->besetzung_info); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <?php if (!$is_restricted_club_admin): ?>
                                    <td>
                                        <div style="display: flex; gap: 4px; flex-wrap: wrap; justify-content: flex-end; align-items: center;">
                                            <button type="button" class="button button-small" title="<?php esc_attr_e('Person zuordnen', 'dienstplan-verwaltung'); ?>"
                                                onclick='dpOpenMitbringenAssignModal(<?php echo esc_attr(wp_json_encode(array(
                                                   'id' => intval($item->id ?? 0),
                                                   'veranstaltung_id' => intval($item->veranstaltung_id ?? 0),
                                                   'tag_id' => intval($item->tag_id ?? 0),
                                                   'verein_id' => intval($item->verein_id ?? 0),
                                                   'verein_name' => (string) ($item->verein_name ?? ''),
                                                   'mitarbeiter_id' => intval($item->mitarbeiter_id ?? 0),
                                                   'mitarbeiter_name' => trim((string) ($item->mitarbeiter_vorname ?? '') . ' ' . (string) ($item->mitarbeiter_nachname ?? '')),
                                                   'admin_only' => intval($item->admin_only ?? 0),
                                                   'besetzung_info' => (string) ($item->besetzung_info ?? ''),
                                                   'tag_datum' => !empty($item->tag_datum) ? date_i18n('d.m.Y', strtotime($item->tag_datum)) : '',
                                                   'bereich_name' => (string) ($item->mitbringen_bereich_name ?? ''),
                                                   'bezeichnung' => (string) ($item->bezeichnung ?? ''),
                                                   'menge' => intval($item->menge ?? 1),
                                                   'status' => (string) ($item->status ?? 'offen'),
                                                   'hinweis' => (string) ($item->hinweis ?? ''),
                                               ))); ?>); return false;'>
                                                <span class="dashicons dashicons-admin-users"></span>
                                            </button>
                                            <button type="button" class="button button-small" title="<?php esc_attr_e('Mitbringen bearbeiten', 'dienstplan-verwaltung'); ?>"
                                                onclick='dpEditMitbringenFromPayload(<?php echo esc_attr(wp_json_encode(array(
                                                   'id' => intval($item->id ?? 0),
                                                   'veranstaltung_id' => intval($item->veranstaltung_id ?? 0),
                                                   'tag_id' => intval($item->tag_id ?? 0),
                                                   'verein_id' => intval($item->verein_id ?? 0),
                                                   'verein_name' => (string) ($item->verein_name ?? ''),
                                                   'mitarbeiter_id' => intval($item->mitarbeiter_id ?? 0),
                                                   'admin_only' => intval($item->admin_only ?? 0),
                                                   'besetzung_info' => (string) ($item->besetzung_info ?? ''),
                                                   'tag_datum' => !empty($item->tag_datum) ? date_i18n('d.m.Y', strtotime($item->tag_datum)) : '',
                                                   'bereich_name' => (string) ($item->mitbringen_bereich_name ?? ''),
                                                   'bezeichnung' => (string) ($item->bezeichnung ?? ''),
                                                   'menge' => intval($item->menge ?? 1),
                                                   'status' => (string) ($item->status ?? 'offen'),
                                                   'hinweis' => (string) ($item->hinweis ?? ''),
                                               ))); ?>); return false;'>
                                                <span class="dashicons dashicons-edit"></span>
                                            </button>
                                            <button type="button" class="button button-small" title="<?php esc_attr_e('Mitbringen kopieren', 'dienstplan-verwaltung'); ?>"
                                                onclick="dpCopyMitbringen(<?php echo intval($item->id ?? 0); ?>); return false;">
                                                <span class="dashicons dashicons-admin-page"></span>
                                            </button>
                                            <button type="button" class="button button-small" title="<?php esc_attr_e('Mitbringen splitten', 'dienstplan-verwaltung'); ?>"
                                                onclick="dpSplitMitbringen(<?php echo intval($item->id ?? 0); ?>); return false;">
                                                <span class="dashicons dashicons-screenoptions"></span>
                                            </button>
                                            <button type="button" class="button button-small" title="<?php esc_attr_e('Status wechseln', 'dienstplan-verwaltung'); ?>"
                                                onclick="dpToggleMitbringenStatus(<?php echo intval($item->id ?? 0); ?>, '<?php echo esc_attr((string) ($item->status ?? 'offen')); ?>'); return false;">
                                                <span class="dashicons dashicons-randomize"></span>
                                            </button>
                                            <button type="button" class="button button-small" title="<?php esc_attr_e('Mitbringen löschen', 'dienstplan-verwaltung'); ?>"
                                                onclick="dpDeleteMitbringen(<?php echo intval($item->id ?? 0); ?>); return false;" style="color: #dc2626; border-color: #fecaca;">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="dp-mitbringen-modal" class="dp-modal" style="display:none;">
    <div class="dp-modal-content" style="max-width:760px;">
        <div class="dp-modal-header">
            <h2><?php _e('Neues Mitbringen', 'dienstplan-verwaltung'); ?></h2>
            <button type="button" class="dp-modal-close" onclick="dpCloseMitbringenModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="dp-mitbringen-form">
                <input type="hidden" id="mb_item_id" name="mitbringen_id" value="0">
                <table class="form-table">
                    <tr>
                        <th><label for="mb_veranstaltung_id"><?php _e('Veranstaltung', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <select id="mb_veranstaltung_id" name="veranstaltung_id" class="regular-text" required style="width:100%;">
                                <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                                <?php foreach ($veranstaltungen as $v): ?>
                                    <option value="<?php echo intval($v->id); ?>" <?php selected($selected_veranstaltung, intval($v->id)); ?>>
                                        <?php echo esc_html($v->name); ?> (<?php 
                                            $dp_ts_ev3 = strtotime($v->start_datum);
                                            $dp_wt_ev3 = ['1'=>'Mo','2'=>'Di','3'=>'Mi','4'=>'Do','5'=>'Fr','6'=>'Sa','7'=>'So'][date('N', $dp_ts_ev3)] ?? '';
                                            echo $dp_wt_ev3 . ' ' . date_i18n('d.m.Y', $dp_ts_ev3);
                                        ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mb_tag_id"><?php _e('Tag', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <select id="mb_tag_id" name="tag_id" class="regular-text" required style="width:100%;">
                                <option value=""><?php _e('-- Erst Veranstaltung wählen --', 'dienstplan-verwaltung'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mb_verein_id"><?php _e('Verantwortlicher Verein', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <select id="mb_verein_id" name="verein_id" class="regular-text" required style="width:100%;">
                                <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                                <?php foreach ($vereine as $v): ?>
                                    <option value="<?php echo intval($v->id); ?>" <?php selected($selected_verein, intval($v->id)); ?>>
                                        <?php echo esc_html($v->name); ?><?php echo !empty($v->kuerzel) ? ' (' . esc_html($v->kuerzel) . ')' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mb_mitarbeiter_id"><?php _e('Person', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <select id="mb_mitarbeiter_id" name="mitarbeiter_id" class="regular-text" style="width:100%;">
                                <option value=""><?php _e('-- Keine Person zugeordnet --', 'dienstplan-verwaltung'); ?></option>
                                <?php foreach ((array) $mitbringen_mitarbeiter as $mitarbeiter): ?>
                                    <?php
                                    $mitarbeiter_name = trim((string) ($mitarbeiter->vorname ?? '') . ' ' . (string) ($mitarbeiter->nachname ?? ''));
                                    if ($mitarbeiter_name === '') {
                                        $mitarbeiter_name = sprintf(__('Mitarbeiter #%d', 'dienstplan-verwaltung'), intval($mitarbeiter->id ?? 0));
                                    }
                                    ?>
                                    <option value="<?php echo intval($mitarbeiter->id ?? 0); ?>"><?php echo esc_html($mitarbeiter_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Optional: Wer dieses Mitbringen übernimmt.', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mb_bereich_select"><?php _e('Bereich', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                                <div style="flex:1;">
                                    <select id="mb_bereich_select" class="regular-text" style="width:100%;">
                                        <option value=""><?php _e('-- Kein Bereich --', 'dienstplan-verwaltung'); ?></option>
                                        <?php foreach ($mitbringen_bereiche as $mb_bereich): ?>
                                            <option value="<?php echo esc_attr($mb_bereich); ?>"><?php echo esc_html($mb_bereich); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="button" class="button button-secondary" onclick="dpToggleNewMitbringenBereich()" style="white-space:nowrap;">
                                    <span class="dashicons dashicons-plus-alt" style="margin-top:3px;"></span>
                                    <?php _e('Neu', 'dienstplan-verwaltung'); ?>
                                </button>
                            </div>
                            <div id="mb_bereich_new_wrap" style="display:none; margin-top:0.6rem;">
                                <input type="text" id="mb_bereich_name_new" class="regular-text" style="width:100%;" placeholder="<?php esc_attr_e('Neuen Mitbringen-Bereich eingeben', 'dienstplan-verwaltung'); ?>">
                            </div>
                            <p class="description"><?php _e('Mitbringen-Bereiche sind getrennt von Dienst-Bereichen.', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mb_bezeichnung"><?php _e('Bezeichnung', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="text" id="mb_bezeichnung" name="bezeichnung" class="regular-text" required style="width:100%;" placeholder="<?php esc_attr_e('z. B. Biertischgarnitur, Eiswürfel, Kuchen', 'dienstplan-verwaltung'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mb_menge"><?php _e('Menge', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <input type="number" id="mb_menge" name="menge" min="1" max="1" value="1" class="small-text" readonly>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mb_status"><?php _e('Status', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <select id="mb_status" name="status" class="regular-text" style="max-width:220px;">
                                <option value="offen"><?php _e('Offen', 'dienstplan-verwaltung'); ?></option>
                                <option value="vergeben"><?php _e('Vergeben', 'dienstplan-verwaltung'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mb_admin_only"><?php _e('Admin-only', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <label style="display:inline-flex; align-items:center; gap:0.5rem;">
                                <input type="checkbox" id="mb_admin_only" name="admin_only" value="1">
                                <?php _e('Nur Admins dürfen übernehmen/zuweisen', 'dienstplan-verwaltung'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mb_hinweis"><?php _e('Hinweis', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <textarea id="mb_hinweis" name="hinweis" rows="3" class="large-text" style="width:100%;"></textarea>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="dpCloseMitbringenModal()"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
            <button type="button" id="mb_save_button" class="button button-primary" onclick="dpSaveMitbringen()"><?php _e('Speichern', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>

<div id="dp-mitbringen-assign-modal" class="dp-modal" style="display:none;">
    <div class="dp-modal-content" style="max-width:800px;">
        <div class="dp-modal-header">
            <h2><?php _e('Mitbringen zuteilen', 'dienstplan-verwaltung'); ?></h2>
            <button type="button" class="dp-modal-close" onclick="dpCloseMitbringenAssignModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <input type="hidden" id="mb_assign_item_id" value="0">
            <input type="hidden" id="mb_assign_verein_id" value="0">

            <div style="padding: 1rem; background: #f0f6fc; border-left: 4px solid #2271b1; margin-bottom: 1.5rem;">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; font-size: 0.9rem;">
                    <div><strong><?php _e('Mitbringen:', 'dienstplan-verwaltung'); ?></strong> <span id="mb_assign_info_bezeichnung">-</span></div>
                    <div><strong><?php _e('Bereich:', 'dienstplan-verwaltung'); ?></strong> <span id="mb_assign_info_bereich">-</span></div>
                    <div><strong><?php _e('Menge:', 'dienstplan-verwaltung'); ?></strong> <span id="mb_assign_info_menge">-</span></div>
                    <div><strong><?php _e('Verein:', 'dienstplan-verwaltung'); ?></strong> <span id="mb_assign_info_verein">-</span></div>
                    <div><strong><?php _e('Tag:', 'dienstplan-verwaltung'); ?></strong> <span id="mb_assign_info_tag">-</span></div>
                    <div><strong><?php _e('Status:', 'dienstplan-verwaltung'); ?></strong> <span id="mb_assign_info_status">-</span></div>
                </div>
            </div>

            <div class="slot-card" style="margin-bottom:0;">
                <div class="slot-card-header">
                    <div class="slot-card-title">
                        <strong>#<span id="mb_assign_info_id">-</span></strong>
                    </div>
                    <span id="mb_assign_badge" class="slot-badge slot-badge-frei"><?php _e('Frei', 'dienstplan-verwaltung'); ?></span>
                </div>
                <div class="slot-assign-form">
                    <select id="mb_assign_mitarbeiter_id" class="regular-text" style="flex:1;">
                        <option value=""><?php _e('-- Mitarbeiter auswählen --', 'dienstplan-verwaltung'); ?></option>
                        <?php foreach ((array) $mitbringen_mitarbeiter as $mitarbeiter): ?>
                            <?php
                            $mitarbeiter_name = trim((string) ($mitarbeiter->vorname ?? '') . ' ' . (string) ($mitarbeiter->nachname ?? ''));
                            if ($mitarbeiter_name === '') {
                                $mitarbeiter_name = sprintf(__('Mitarbeiter #%d', 'dienstplan-verwaltung'), intval($mitarbeiter->id ?? 0));
                            }
                            ?>
                            <option value="<?php echo intval($mitarbeiter->id ?? 0); ?>"><?php echo esc_html($mitarbeiter_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="button" onclick="dpToggleNeuerMitarbeiterForm(true)" title="<?php esc_attr_e('Neuen Mitarbeiter anlegen', 'dienstplan-verwaltung'); ?>">
                        <span class="dashicons dashicons-plus-alt" style="font-size: 14px; width: 14px; height: 14px; margin-top: 3px;"></span>
                        <?php _e('Neu', 'dienstplan-verwaltung'); ?>
                    </button>
                    <button type="button" class="button button-primary" onclick="dpAssignMitbringenPerson()">
                        <?php _e('Zuweisen', 'dienstplan-verwaltung'); ?>
                    </button>
                    <button type="button" class="button" onclick="dpUnassignMitbringenPerson()">
                        <?php _e('Entfernen', 'dienstplan-verwaltung'); ?>
                    </button>
                </div>
                <div style="margin-top: 0.75rem;">
                    <label style="display:flex; align-items:center; gap:0.5rem; font-weight:600;">
                        <input type="checkbox" id="mb_assign_admin_only" value="1">
                        <?php _e('Nur Admin', 'dienstplan-verwaltung'); ?>
                    </label>
                </div>
                <div style="margin-top: 0.75rem;">
                    <label for="mb_assign_besetzung_info" style="display:block; margin-bottom:0.4rem; font-weight:600;">
                        <?php _e('Zusatzinfo zur Besetzung', 'dienstplan-verwaltung'); ?>
                    </label>
                    <textarea id="mb_assign_besetzung_info" rows="3" class="large-text" style="width:100%;" placeholder="<?php echo esc_attr(__('z. B. Kuchen: ohne Nüsse, Salat: vegetarisch', 'dienstplan-verwaltung')); ?>"></textarea>
                </div>
            </div>

            <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 2px solid #e5e7eb;">
                <button type="button" class="button button-primary" onclick="dpToggleNeuerMitarbeiterForm()" style="width: 100%; height: 40px; font-size: 14px;">
                    <span class="dashicons dashicons-plus-alt" style="font-size: 18px; width: 18px; height: 18px; margin-top: 3px;"></span>
                    <?php _e('Neuen Mitarbeiter anlegen', 'dienstplan-verwaltung'); ?>
                </button>
            </div>

            <div id="mb-neuer-mitarbeiter-form" style="display:none; margin-top: 1rem; padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px;">
                <h3 style="margin-top: 0; font-size: 1rem; color: #1e293b;">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php _e('Neuen Mitarbeiter anlegen', 'dienstplan-verwaltung'); ?>
                </h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div>
                        <label for="mb_new_mitarbeiter_vorname" style="display: block; margin-bottom: 0.25rem; font-weight: 600; font-size: 0.9rem;">
                            <?php _e('Vorname *', 'dienstplan-verwaltung'); ?>
                        </label>
                        <input type="text" id="mb_new_mitarbeiter_vorname" class="regular-text" style="width: 100%;" required>
                    </div>
                    <div>
                        <label for="mb_new_mitarbeiter_nachname" style="display: block; margin-bottom: 0.25rem; font-weight: 600; font-size: 0.9rem;">
                            <?php _e('Nachname *', 'dienstplan-verwaltung'); ?>
                        </label>
                        <input type="text" id="mb_new_mitarbeiter_nachname" class="regular-text" style="width: 100%;" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div>
                        <label for="mb_new_mitarbeiter_email" style="display: block; margin-bottom: 0.25rem; font-weight: 600; font-size: 0.9rem;">
                            <?php _e('E-Mail', 'dienstplan-verwaltung'); ?>
                        </label>
                        <input type="email" id="mb_new_mitarbeiter_email" class="regular-text" style="width: 100%;">
                    </div>
                    <div>
                        <label for="mb_new_mitarbeiter_telefon" style="display: block; margin-bottom: 0.25rem; font-weight: 600; font-size: 0.9rem;">
                            <?php _e('Telefon', 'dienstplan-verwaltung'); ?>
                        </label>
                        <input type="tel" id="mb_new_mitarbeiter_telefon" class="regular-text" style="width: 100%;">
                    </div>
                </div>

                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                    <button type="button" class="button" onclick="dpToggleNeuerMitarbeiterForm(false)">
                        <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
                    </button>
                    <button type="button" class="button button-primary" onclick="dpSaveNeuerMitarbeiterForMitbringen()">
                        <span class="dashicons dashicons-saved" style="margin-top: 3px;"></span>
                        <?php _e('Mitarbeiter anlegen', 'dienstplan-verwaltung'); ?>
                    </button>
                </div>
            </div>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="dpCloseMitbringenAssignModal()"><?php _e('Schließen', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>

<script>
var dpMitbringenAssignCurrentItem = null;
var dpMitbringenTagCache = {};
var dpMitbringenTagRequests = {};
var dpMitbringenSaveInProgress = false;

<?php if ($selected_veranstaltung > 0): ?>
dpMitbringenTagCache[String(<?php echo intval($selected_veranstaltung); ?>)] = <?php echo wp_json_encode(array_map(static function ($tag) {
    return array(
        'id' => intval($tag->id ?? 0),
        'tag_nummer' => intval($tag->tag_nummer ?? 0),
        'tag_datum' => !empty($tag->tag_datum) ? (string) $tag->tag_datum : '',
    );
}, (array) $mitbringen_tage)); ?>;
<?php endif; ?>

function dpRenderVeranstaltungTageOptions(tags, selectedTagId) {
    if (!Array.isArray(tags) || !tags.length) {
        return '<option value="">-- Keine Tage gefunden --</option>';
    }

    var html = '<option value="">-- Bitte wählen --</option>';
    tags.forEach(function(tag) {
        var date = tag.tag_datum ? new Date(tag.tag_datum + 'T00:00:00') : null;
        var datum = date ? date.toLocaleDateString('de-DE') : '';
        var isSelected = selectedTagId && String(selectedTagId) === String(tag.id) ? ' selected' : '';
        html += '<option value="' + tag.id + '"' + isSelected + '>Tag ' + (tag.tag_nummer || '?') + (datum ? ': ' + datum : '') + '</option>';
    });

    return html;
}

function dpLoadVeranstaltungTage(veranstaltungId, selectedTagId) {
    var tagSelect = jQuery('#mb_tag_id');
    tagSelect.html('<option value="">-- Lädt... --</option>');

    if (!veranstaltungId) {
        tagSelect.html('<option value="">-- Erst Veranstaltung wählen --</option>');
        return;
    }

    var veranstaltungKey = String(parseInt(veranstaltungId, 10));
    if (dpMitbringenTagCache[veranstaltungKey]) {
        tagSelect.html(dpRenderVeranstaltungTageOptions(dpMitbringenTagCache[veranstaltungKey], selectedTagId));
        return;
    }

    if (dpMitbringenTagRequests[veranstaltungKey]) {
        dpMitbringenTagRequests[veranstaltungKey].done(function(response) {
            if (!response || !response.success || !Array.isArray(response.data)) {
                tagSelect.html('<option value="">-- Keine Tage gefunden --</option>');
                return;
            }
            tagSelect.html(dpRenderVeranstaltungTageOptions(response.data, selectedTagId));
        }).fail(function() {
            tagSelect.html('<option value="">-- Fehler beim Laden --</option>');
        });
        return;
    }

    dpMitbringenTagRequests[veranstaltungKey] = jQuery.post(window.dpAjax.ajaxurl, {
        action: 'dp_get_veranstaltung_tage',
        nonce: window.dpAjax.nonce,
        veranstaltung_id: veranstaltungId
    }).done(function(response) {
        if (!response || !response.success || !Array.isArray(response.data)) {
            tagSelect.html('<option value="">-- Keine Tage gefunden --</option>');
            return;
        }

        dpMitbringenTagCache[veranstaltungKey] = response.data;
        tagSelect.html(dpRenderVeranstaltungTageOptions(response.data, selectedTagId));
    }).fail(function() {
        tagSelect.html('<option value="">-- Fehler beim Laden --</option>');
    }).always(function() {
        delete dpMitbringenTagRequests[veranstaltungKey];
    });
}

function openMitbringenModal() {
    var form = document.getElementById('dp-mitbringen-form');
    if (form) {
        form.reset();
    }

    jQuery('#mb_item_id').val('0');
    jQuery('#dp-mitbringen-modal .dp-modal-header h2').text('<?php echo esc_js(__('Neues Mitbringen', 'dienstplan-verwaltung')); ?>');

    jQuery('#mb_bereich_new_wrap').hide();
    jQuery('#mb_bereich_name_new').val('');

    var selectedVeranstaltung = <?php echo intval($selected_veranstaltung); ?>;
    var selectedVerein = <?php echo intval($selected_verein); ?>;
    if (selectedVeranstaltung > 0) {
        jQuery('#mb_veranstaltung_id').val(String(selectedVeranstaltung));
        dpLoadVeranstaltungTage(selectedVeranstaltung, null);
    }
    if (selectedVerein > 0) {
        jQuery('#mb_verein_id').val(String(selectedVerein));
    }
    jQuery('#mb_mitarbeiter_id').val('');
    jQuery('#mb_admin_only').prop('checked', false);

    jQuery('#dp-mitbringen-modal').css('display', 'flex');
    return false;
}

function dpCloseMitbringenModal() {
    jQuery('#dp-mitbringen-modal').hide();
}

function dpToggleNewMitbringenBereich() {
    var wrap = jQuery('#mb_bereich_new_wrap');
    if (wrap.is(':visible')) {
        wrap.hide();
        jQuery('#mb_bereich_name_new').val('');
        return;
    }

    wrap.show();
    jQuery('#mb_bereich_name_new').trigger('focus');
}

function dpSaveMitbringen() {
    if (!window.dpAjax || !window.dpAjax.ajaxurl || !window.dpAjax.nonce) {
        alert('Konfiguration fehlt. Bitte Seite neu laden.');
        return;
    }

    if (dpMitbringenSaveInProgress) {
        return;
    }

    var bereichNeu = jQuery.trim(jQuery('#mb_bereich_name_new').val());
    var bereichAuswahl = jQuery.trim(jQuery('#mb_bereich_select').val());
    var bereichName = bereichNeu !== '' ? bereichNeu : bereichAuswahl;

    var payload = {
        action: 'dp_save_mitbringen_item',
        nonce: window.dpAjax.nonce,
        mitbringen_id: jQuery('#mb_item_id').val() || '0',
        veranstaltung_id: jQuery('#mb_veranstaltung_id').val(),
        tag_id: jQuery('#mb_tag_id').val(),
        verein_id: jQuery('#mb_verein_id').val(),
        mitarbeiter_id: jQuery('#mb_mitarbeiter_id').val(),
        admin_only: jQuery('#mb_admin_only').is(':checked') ? 1 : 0,
        bereich_name: bereichName,
        bezeichnung: jQuery('#mb_bezeichnung').val(),
        menge: '1',
        status: jQuery('#mb_status').val(),
        hinweis: jQuery('#mb_hinweis').val()
    };

    if (!payload.veranstaltung_id || !payload.tag_id || !payload.verein_id || !jQuery.trim(payload.bezeichnung)) {
        alert('Bitte Veranstaltung, Tag, Verein und Bezeichnung ausfüllen.');
        return;
    }

    var saveButton = jQuery('#mb_save_button');
    var originalSaveText = saveButton.text();
    dpMitbringenSaveInProgress = true;
    saveButton.prop('disabled', true).text('Speichert...');

    jQuery.post(window.dpAjax.ajaxurl, payload)
        .done(function(response) {
            if (response && response.success) {
                dpCloseMitbringenModal();
                window.location.reload();
                return;
            }
            var msg = (response && response.data && response.data.message) ? response.data.message : 'Speichern fehlgeschlagen.';
            alert(msg);
        })
        .fail(function() {
            alert('Serverfehler beim Speichern.');
        })
        .always(function() {
            dpMitbringenSaveInProgress = false;
            saveButton.prop('disabled', false).text(originalSaveText);
        });
}

function dpEditMitbringenFromPayload(item) {
    if (!item || !item.id) {
        alert('Mitbringen-Daten unvollständig.');
        return false;
    }

    var form = document.getElementById('dp-mitbringen-form');
    if (form) {
        form.reset();
    }

    jQuery('#mb_item_id').val(String(item.id));
    jQuery('#mb_veranstaltung_id').val(String(item.veranstaltung_id || ''));
    jQuery('#mb_verein_id').val(String(item.verein_id || ''));
    jQuery('#mb_mitarbeiter_id').val(String(item.mitarbeiter_id || ''));
    var bereichValue = item.bereich_name || '';
    var bereichSelect = jQuery('#mb_bereich_select');
    var hasBereichOption = bereichSelect.find('option').filter(function() {
        return String(jQuery(this).val()) === String(bereichValue);
    }).length > 0;
    if (bereichValue && !hasBereichOption) {
        bereichSelect.append(jQuery('<option></option>').val(bereichValue).text(bereichValue));
    }
    bereichSelect.val(bereichValue);
    jQuery('#mb_bereich_name_new').val('');
    jQuery('#mb_bereich_new_wrap').hide();
    jQuery('#mb_bezeichnung').val(item.bezeichnung || '');
    jQuery('#mb_menge').val(1);
    jQuery('#mb_status').val(item.status || 'offen');
    jQuery('#mb_admin_only').prop('checked', String(item.admin_only || '0') === '1');
    jQuery('#mb_hinweis').val(item.hinweis || '');

    dpLoadVeranstaltungTage(item.veranstaltung_id || '', item.tag_id || '');

    jQuery('#dp-mitbringen-modal .dp-modal-header h2').text('<?php echo esc_js(__('Mitbringen bearbeiten', 'dienstplan-verwaltung')); ?>');
    jQuery('#dp-mitbringen-modal').css('display', 'flex');
    return false;
}

function dpOpenMitbringenAssignModal(item) {
    if (!item || !item.id) {
        return false;
    }

    dpMitbringenAssignCurrentItem = item;

    jQuery('#mb_assign_item_id').val(String(item.id || '0'));
    jQuery('#mb_assign_verein_id').val(String(item.verein_id || '0'));
    jQuery('#mb_assign_info_id').text(String(item.id || '-'));
    jQuery('#mb_assign_info_bezeichnung').text(item.bezeichnung || '-');
    jQuery('#mb_assign_info_bereich').text(item.bereich_name || '-');
    jQuery('#mb_assign_info_menge').text(String(item.menge || 1));
    jQuery('#mb_assign_info_verein').text(item.verein_name || '-');
    jQuery('#mb_assign_info_tag').text(item.tag_datum || '-');
    jQuery('#mb_assign_info_status').text(item.status || 'offen');
    jQuery('#mb_assign_mitarbeiter_id').val(item.mitarbeiter_id ? String(item.mitarbeiter_id) : '');
    // Falls die Option nicht im Dropdown ist (z.B. anderer Verein), dynamisch einfügen
    if (item.mitarbeiter_id > 0) {
        var personSelect = jQuery('#mb_assign_mitarbeiter_id');
        if (personSelect.val() !== String(item.mitarbeiter_id)) {
            var label = item.mitarbeiter_name && item.mitarbeiter_name.trim() !== ''
                ? item.mitarbeiter_name.trim()
                : 'Mitarbeiter #' + item.mitarbeiter_id;
            personSelect.append(jQuery('<option></option>').val(String(item.mitarbeiter_id)).text(label));
            personSelect.val(String(item.mitarbeiter_id));
        }
    }
    jQuery('#mb_assign_admin_only').prop('checked', String(item.admin_only || '0') === '1');
    jQuery('#mb_assign_besetzung_info').val(item.besetzung_info || '');
    dpToggleNeuerMitarbeiterForm(false);

    var isAssigned = String(item.status || 'offen') === 'vergeben' || !!item.mitarbeiter_id;
    var badge = jQuery('#mb_assign_badge');
    if (isAssigned) {
        badge.removeClass('slot-badge-frei').addClass('slot-badge-besetzt').text('<?php echo esc_js(__('Besetzt', 'dienstplan-verwaltung')); ?>');
    } else {
        badge.removeClass('slot-badge-besetzt').addClass('slot-badge-frei').text('<?php echo esc_js(__('Frei', 'dienstplan-verwaltung')); ?>');
    }

    jQuery('#dp-mitbringen-assign-modal').css('display', 'flex');
    return false;
}

function dpCloseMitbringenAssignModal() {
    jQuery('#dp-mitbringen-assign-modal').hide();
}

function dpToggleNeuerMitarbeiterForm(forceOpen) {
    var form = jQuery('#mb-neuer-mitarbeiter-form');
    var shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : !form.is(':visible');

    if (!shouldOpen) {
        form.hide();
        jQuery('#mb_new_mitarbeiter_vorname, #mb_new_mitarbeiter_nachname, #mb_new_mitarbeiter_email, #mb_new_mitarbeiter_telefon').val('');
        return;
    }

    form.show();
    jQuery('#mb_new_mitarbeiter_vorname').trigger('focus');
}

function dpSaveNeuerMitarbeiterForMitbringen() {
    if (!window.dpAjax || !window.dpAjax.ajaxurl || !window.dpAjax.nonce) {
        alert('Konfiguration fehlt. Bitte Seite neu laden.');
        return false;
    }

    var vorname = jQuery.trim(jQuery('#mb_new_mitarbeiter_vorname').val());
    var nachname = jQuery.trim(jQuery('#mb_new_mitarbeiter_nachname').val());
    var email = jQuery.trim(jQuery('#mb_new_mitarbeiter_email').val());
    var telefon = jQuery.trim(jQuery('#mb_new_mitarbeiter_telefon').val());
    var vereinId = parseInt(jQuery('#mb_assign_verein_id').val() || '0', 10);

    if (!vorname || !nachname) {
        alert('<?php echo esc_js(__('Vorname und Nachname sind Pflichtfelder.', 'dienstplan-verwaltung')); ?>');
        return false;
    }

    var payload = {
        action: 'dp_save_mitarbeiter',
        nonce: window.dpAjax.nonce,
        mitarbeiter_id: 0,
        vorname: vorname,
        nachname: nachname,
        email: email,
        telefon: telefon
    };

    if (vereinId > 0) {
        payload.verein_ids = [vereinId];
    }

    jQuery.post(window.dpAjax.ajaxurl, payload)
        .done(function(response) {
            if (!response || !response.success || !response.data || !response.data.mitarbeiter_id) {
                var msg = (response && response.data && response.data.message) ? response.data.message : 'Mitarbeiter konnte nicht angelegt werden.';
                alert(msg);
                return;
            }

            var newId = String(response.data.mitarbeiter_id);
            var newLabel = vorname + ' ' + nachname;
            var personSelect = jQuery('#mb_assign_mitarbeiter_id');

            if (personSelect.find('option[value="' + newId + '"]').length === 0) {
                personSelect.append(jQuery('<option></option>').val(newId).text(newLabel));
            }

            personSelect.val(newId);
            dpToggleNeuerMitarbeiterForm(false);
        })
        .fail(function() {
            alert('Serverfehler beim Anlegen des Mitarbeiters.');
        });

    return false;
}

function dpAssignMitbringenPerson() {
    var itemId = parseInt(jQuery('#mb_assign_item_id').val() || '0', 10);
    var mitarbeiterId = parseInt(jQuery('#mb_assign_mitarbeiter_id').val() || '0', 10);

    if (itemId <= 0) {
        return false;
    }

    if (mitarbeiterId <= 0) {
        alert('<?php echo esc_js(__('Bitte einen Mitarbeiter auswählen.', 'dienstplan-verwaltung')); ?>');
        return false;
    }

    jQuery.post(window.dpAjax.ajaxurl, {
        action: 'dp_assign_mitbringen_person',
        nonce: window.dpAjax.nonce,
        mitbringen_id: itemId,
        mitarbeiter_id: mitarbeiterId,
        admin_only: jQuery('#mb_assign_admin_only').is(':checked') ? 1 : 0,
        besetzung_info: jQuery('#mb_assign_besetzung_info').val()
    }).done(function(response) {
        if (response && response.success) {
            window.location.reload();
            return;
        }

        var msg = (response && response.data && response.data.message) ? response.data.message : 'Zuweisung fehlgeschlagen.';
        alert(msg);
    }).fail(function() {
        alert('Serverfehler beim Zuweisen.');
    });

    return false;
}

function dpUnassignMitbringenPerson() {
    var itemId = parseInt(jQuery('#mb_assign_item_id').val() || '0', 10);
    if (itemId <= 0) {
        return false;
    }

    jQuery.post(window.dpAjax.ajaxurl, {
        action: 'dp_assign_mitbringen_person',
        nonce: window.dpAjax.nonce,
        mitbringen_id: itemId,
        mitarbeiter_id: 0,
        admin_only: jQuery('#mb_assign_admin_only').is(':checked') ? 1 : 0,
        besetzung_info: ''
    }).done(function(response) {
        if (response && response.success) {
            window.location.reload();
            return;
        }

        var msg = (response && response.data && response.data.message) ? response.data.message : 'Entfernen fehlgeschlagen.';
        alert(msg);
    }).fail(function() {
        alert('Serverfehler beim Entfernen.');
    });

    return false;
}

function dpDeleteMitbringen(itemId) {
    if (!itemId || !window.dpAjax || !window.dpAjax.ajaxurl || !window.dpAjax.nonce) {
        return false;
    }

    if (!window.confirm('<?php echo esc_js(__('Diesen Mitbringen-Eintrag wirklich löschen?', 'dienstplan-verwaltung')); ?>')) {
        return false;
    }

    jQuery.post(window.dpAjax.ajaxurl, {
        action: 'dp_delete_mitbringen_item',
        nonce: window.dpAjax.nonce,
        mitbringen_id: itemId
    }).done(function(response) {
        if (response && response.success) {
            window.location.reload();
            return;
        }

        var msg = (response && response.data && response.data.message) ? response.data.message : 'Löschen fehlgeschlagen.';
        alert(msg);
    }).fail(function() {
        alert('Serverfehler beim Löschen.');
    });

    return false;
}

function dpCopyMitbringen(itemId) {
    if (!itemId || !window.dpAjax || !window.dpAjax.ajaxurl || !window.dpAjax.nonce) {
        return false;
    }

    jQuery.post(window.dpAjax.ajaxurl, {
        action: 'dp_copy_mitbringen_item',
        nonce: window.dpAjax.nonce,
        mitbringen_id: itemId
    }).done(function(response) {
        if (response && response.success) {
            window.location.reload();
            return;
        }

        var msg = (response && response.data && response.data.message) ? response.data.message : 'Kopieren fehlgeschlagen.';
        alert(msg);
    }).fail(function() {
        alert('Serverfehler beim Kopieren.');
    });

    return false;
}

function dpSplitMitbringen(itemId) {
    if (!itemId || !window.dpAjax || !window.dpAjax.ajaxurl || !window.dpAjax.nonce) {
        return false;
    }

    var raw = window.prompt('<?php echo esc_js(__('Wie viele zusätzliche Mitbringen-Einträge sollen erstellt werden?', 'dienstplan-verwaltung')); ?>', '1');
    if (raw === null) {
        return false;
    }

    var splitCount = parseInt(raw, 10);
    if (!splitCount || splitCount < 1) {
        alert('<?php echo esc_js(__('Bitte eine Zahl größer oder gleich 1 eingeben.', 'dienstplan-verwaltung')); ?>');
        return false;
    }

    jQuery.post(window.dpAjax.ajaxurl, {
        action: 'dp_split_mitbringen_item',
        nonce: window.dpAjax.nonce,
        mitbringen_id: itemId,
        split_count: splitCount
    }).done(function(response) {
        if (response && response.success) {
            window.location.reload();
            return;
        }

        var msg = (response && response.data && response.data.message) ? response.data.message : 'Splitten fehlgeschlagen.';
        alert(msg);
    }).fail(function() {
        alert('Serverfehler beim Splitten.');
    });

    return false;
}

function dpToggleMitbringenStatus(itemId, currentStatus) {
    if (!itemId || !window.dpAjax || !window.dpAjax.ajaxurl || !window.dpAjax.nonce) {
        return false;
    }

    var nextStatus = currentStatus === 'vergeben' ? 'offen' : 'vergeben';

    jQuery.post(window.dpAjax.ajaxurl, {
        action: 'dp_toggle_mitbringen_status',
        nonce: window.dpAjax.nonce,
        mitbringen_id: itemId,
        status: nextStatus
    }).done(function(response) {
        if (response && response.success) {
            window.location.reload();
            return;
        }

        var msg = (response && response.data && response.data.message) ? response.data.message : 'Statuswechsel fehlgeschlagen.';
        alert(msg);
    }).fail(function() {
        alert('Serverfehler beim Statuswechsel.');
    });

    return false;
}

jQuery(function($) {
    var dpMitbringenBulkOptions = {
        tags: <?php echo wp_json_encode($bulk_tag_options); ?>,
        vereine: <?php echo wp_json_encode($bulk_verein_options); ?>,
        bereiche: <?php echo wp_json_encode($bulk_bereich_options); ?>,
        personen: <?php echo wp_json_encode($bulk_person_options); ?>,
        status: [
            { value: 'offen', label: '<?php echo esc_js(__('Offen', 'dienstplan-verwaltung')); ?>' },
            { value: 'vergeben', label: '<?php echo esc_js(__('Vergeben', 'dienstplan-verwaltung')); ?>' }
        ],
        adminOnly: [
            { value: '0', label: '<?php echo esc_js(__('Nein', 'dienstplan-verwaltung')); ?>' },
            { value: '1', label: '<?php echo esc_js(__('Ja', 'dienstplan-verwaltung')); ?>' }
        ]
    };

    function getSelectedMitbringenIds() {
        return $('.mitbringen-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
    }

    function getBulkActionOptions(action) {
        switch (action) {
            case 'change_tag':
                return dpMitbringenBulkOptions.tags;
            case 'change_verein':
                return dpMitbringenBulkOptions.vereine;
            case 'change_bereich':
                return dpMitbringenBulkOptions.bereiche;
            case 'change_person':
                return dpMitbringenBulkOptions.personen;
            case 'change_status':
                return dpMitbringenBulkOptions.status;
            case 'change_admin_only':
                return dpMitbringenBulkOptions.adminOnly;
            default:
                return [];
        }
    }

    function updateBulkValueControl() {
        var action = $('.dp-mitbringen-bulk-toolbar .bulk-action-select').val();
        var options = getBulkActionOptions(action);
        var $wrap = $('.dp-mitbringen-bulk-toolbar .bulk-action-value-wrap');
        var $select = $('.dp-mitbringen-bulk-toolbar .bulk-action-value');

        if (!options.length) {
            $select.html('');
            $wrap.hide();
            return;
        }

        var html = '<option value=""><?php echo esc_js(__('-- Bitte wählen --', 'dienstplan-verwaltung')); ?></option>';
        options.forEach(function(option) {
            html += '<option value="' + String(option.value) + '">' + String(option.label) + '</option>';
        });

        $select.html(html);
        $wrap.show();
    }

    function updateMitbringenBulkToolbar() {
        var count = getSelectedMitbringenIds().length;
        $('.dp-mitbringen-bulk-toolbar .count').text(count);
        $('.dp-mitbringen-bulk-toolbar .bulk-action-apply').prop('disabled', count === 0);
        if (count === 0) {
            $('.dp-mitbringen-bulk-toolbar .bulk-action-select').val('');
            updateBulkValueControl();
        }
    }

    function performMitbringenBulkAction(action, selectedIds) {
        if (!action) {
            alert('<?php echo esc_js(__('Bitte wählen Sie eine Aktion aus.', 'dienstplan-verwaltung')); ?>');
            return;
        }

        if (!selectedIds.length) {
            alert('<?php echo esc_js(__('Bitte wählen Sie mindestens einen Mitbringen-Eintrag aus.', 'dienstplan-verwaltung')); ?>');
            return;
        }

        if (action === 'delete') {
            if (!window.confirm('<?php echo esc_js(__('Die ausgewählten Mitbringen-Einträge wirklich löschen?', 'dienstplan-verwaltung')); ?>')) {
                return;
            }

            $.post(window.dpAjax.ajaxurl, {
                action: 'dp_bulk_delete_mitbringen',
                nonce: window.dpAjax.nonce,
                mitbringen_ids: selectedIds
            }).done(function(response) {
                if (response && response.success) {
                    window.location.reload();
                    return;
                }

                var msg = (response && response.data && response.data.message) ? response.data.message : '<?php echo esc_js(__('Sammellöschen fehlgeschlagen.', 'dienstplan-verwaltung')); ?>';
                alert(msg);
            }).fail(function() {
                alert('<?php echo esc_js(__('Serverfehler beim Sammellöschen.', 'dienstplan-verwaltung')); ?>');
            });

            return;
        }

        var actionValue = $('.dp-mitbringen-bulk-toolbar .bulk-action-value').val();
        var updateData = {};

        if (action === 'change_tag') {
            if (!actionValue) {
                alert('<?php echo esc_js(__('Bitte einen Tag auswählen.', 'dienstplan-verwaltung')); ?>');
                return;
            }
            updateData.tag_id = actionValue;
        } else if (action === 'change_verein') {
            if (!actionValue) {
                alert('<?php echo esc_js(__('Bitte einen Verein auswählen.', 'dienstplan-verwaltung')); ?>');
                return;
            }
            updateData.verein_id = actionValue;
        } else if (action === 'change_bereich') {
            if (!actionValue) {
                alert('<?php echo esc_js(__('Bitte einen Bereich auswählen.', 'dienstplan-verwaltung')); ?>');
                return;
            }
            updateData.bereich_name = actionValue;
        } else if (action === 'change_person') {
            if (actionValue === '') {
                alert('<?php echo esc_js(__('Bitte eine Person auswählen.', 'dienstplan-verwaltung')); ?>');
                return;
            }
            updateData.mitarbeiter_id = actionValue;
        } else if (action === 'change_status') {
            if (!actionValue) {
                alert('<?php echo esc_js(__('Bitte einen Status auswählen.', 'dienstplan-verwaltung')); ?>');
                return;
            }
            updateData.status = actionValue;
        } else if (action === 'change_admin_only') {
            if (actionValue === '') {
                alert('<?php echo esc_js(__('Bitte Admin-only Auswahl treffen.', 'dienstplan-verwaltung')); ?>');
                return;
            }
            updateData.admin_only = actionValue;
        } else {
            alert('<?php echo esc_js(__('Unbekannte Aktion.', 'dienstplan-verwaltung')); ?>');
            return;
        }

        $.post(window.dpAjax.ajaxurl, {
            action: 'dp_bulk_update_mitbringen',
            nonce: window.dpAjax.nonce,
            mitbringen_ids: selectedIds,
            update_data: updateData
        }).done(function(response) {
            if (response && response.success) {
                window.location.reload();
                return;
            }

            var msg = (response && response.data && response.data.message) ? response.data.message : '<?php echo esc_js(__('Sammeländerung fehlgeschlagen.', 'dienstplan-verwaltung')); ?>';
            alert(msg);
        }).fail(function() {
            alert('<?php echo esc_js(__('Serverfehler bei der Sammeländerung.', 'dienstplan-verwaltung')); ?>');
        });
    }

    $('.mitbringen-checkbox').on('change', updateMitbringenBulkToolbar);

    $('.select-all-mitbringen').on('change', function() {
        $('.mitbringen-checkbox').prop('checked', $(this).is(':checked'));
        updateMitbringenBulkToolbar();
    });

    $('.dp-mitbringen-bulk-toolbar .bulk-action-cancel').on('click', function() {
        $('.mitbringen-checkbox, .select-all-mitbringen').prop('checked', false);
        updateMitbringenBulkToolbar();
    });

    $('.dp-mitbringen-bulk-toolbar .bulk-action-select').on('change', function() {
        updateBulkValueControl();
    });

    $('.dp-mitbringen-bulk-toolbar .bulk-action-apply').on('click', function() {
        performMitbringenBulkAction(
            $('.dp-mitbringen-bulk-toolbar .bulk-action-select').val(),
            getSelectedMitbringenIds()
        );
    });

    $('#mb_mitarbeiter_id').on('change', function() {
        if ($(this).val()) {
            $('#mb_status').val('vergeben');
        } else if ($('#mb_status').val() === 'vergeben') {
            $('#mb_status').val('offen');
        }
    });

    $('#mb_veranstaltung_id').on('change', function() {
        dpLoadVeranstaltungTage($(this).val(), null);
    });

    $(document).on('click', function(e) {
        if ($(e.target).is('#dp-mitbringen-modal')) {
            dpCloseMitbringenModal();
        }
        if ($(e.target).is('#dp-mitbringen-assign-modal')) {
            dpCloseMitbringenAssignModal();
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            if ($('#dp-mitbringen-modal').is(':visible')) {
                dpCloseMitbringenModal();
            }
            if ($('#dp-mitbringen-assign-modal').is(':visible')) {
                dpCloseMitbringenAssignModal();
            }
        }
    });

    updateMitbringenBulkToolbar();
    updateBulkValueControl();
});

</script>

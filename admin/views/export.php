<?php
/**
 * Export-Seite (separiert)
 */
if (!defined('ABSPATH')) exit;

$can_manage_clubs  = Dienstplan_Roles::can_manage_clubs()  || current_user_can('manage_options');
$can_manage_events = Dienstplan_Roles::can_manage_events() || current_user_can('manage_options');

if (!$can_manage_clubs && !$can_manage_events) {
    wp_die(__('Sie haben keine Berechtigung für den Export.', 'dienstplan-verwaltung'));
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-download" style="font-size:1.4em;vertical-align:middle;margin-right:6px;"></span>
        <?php _e('Daten exportieren', 'dienstplan-verwaltung'); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=dienstplan-import')); ?>" class="page-title-action">
        <?php _e('Zum Import', 'dienstplan-verwaltung'); ?>
    </a>
    <hr class="wp-header-end">

    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1rem; margin-top:1.5rem;">

        <?php if ($can_manage_clubs): ?>
        <!-- Vereine -->
        <div class="postbox" style="margin:0;">
            <div class="postbox-header"><h2>🏛️ <?php _e('Vereine', 'dienstplan-verwaltung'); ?></h2></div>
            <div class="inside">
                <p class="description"><?php printf(_n('%d Eintrag', '%d Einträge', count($stats['vereine']), 'dienstplan-verwaltung'), count($stats['vereine'])); ?></p>
                <p class="description" style="font-size:0.85em; color:#6b7280;"><?php _e('Spalten: name, kuerzel, beschreibung, kontakt_name, kontakt_email, kontakt_telefon', 'dienstplan-verwaltung'); ?></p>
                <button class="button button-primary dp-export-btn" style="width:100%;" data-type="vereine">
                    <span class="dashicons dashicons-download" style="margin-top:3px;"></span>
                    <?php _e('Vereine exportieren', 'dienstplan-verwaltung'); ?>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($can_manage_events): ?>
        <!-- Bereiche -->
        <div class="postbox" style="margin:0;">
            <div class="postbox-header"><h2>🗂️ <?php _e('Bereiche', 'dienstplan-verwaltung'); ?></h2></div>
            <div class="inside">
                <p class="description"><?php printf(_n('%d Eintrag', '%d Einträge', count($stats['bereiche']), 'dienstplan-verwaltung'), count($stats['bereiche'])); ?></p>
                <p class="description" style="font-size:0.85em; color:#6b7280;"><?php _e('Spalten: name, farbe, aktiv, sortierung, admin_only', 'dienstplan-verwaltung'); ?></p>
                <button class="button button-primary dp-export-btn" style="width:100%;" data-type="bereiche">
                    <span class="dashicons dashicons-download" style="margin-top:3px;"></span>
                    <?php _e('Bereiche exportieren', 'dienstplan-verwaltung'); ?>
                </button>
            </div>
        </div>

        <!-- Tätigkeiten -->
        <div class="postbox" style="margin:0;">
            <div class="postbox-header"><h2>🔧 <?php _e('Tätigkeiten', 'dienstplan-verwaltung'); ?></h2></div>
            <div class="inside">
                <p class="description"><?php printf(_n('%d Eintrag', '%d Einträge', count($stats['taetigkeiten']), 'dienstplan-verwaltung'), count($stats['taetigkeiten'])); ?></p>
                <p class="description" style="font-size:0.85em; color:#6b7280;"><?php _e('Spalten: bereich_name, bereich_id, name, beschreibung, aktiv, sortierung, admin_only', 'dienstplan-verwaltung'); ?></p>
                <button class="button button-primary dp-export-btn" style="width:100%;" data-type="taetigkeiten">
                    <span class="dashicons dashicons-download" style="margin-top:3px;"></span>
                    <?php _e('Tätigkeiten exportieren', 'dienstplan-verwaltung'); ?>
                </button>
            </div>
        </div>

        <!-- Veranstaltungen -->
        <div class="postbox" style="margin:0;">
            <div class="postbox-header"><h2>📅 <?php _e('Veranstaltungen', 'dienstplan-verwaltung'); ?></h2></div>
            <div class="inside">
                <p class="description"><?php printf(_n('%d Eintrag', '%d Einträge', count($stats['veranstaltungen']), 'dienstplan-verwaltung'), count($stats['veranstaltungen'])); ?></p>
                <p class="description" style="font-size:0.85em; color:#6b7280;"><?php _e('Spalten: name, start_datum, end_datum, beschreibung', 'dienstplan-verwaltung'); ?></p>
                <button class="button button-primary dp-export-btn" style="width:100%;" data-type="veranstaltungen">
                    <span class="dashicons dashicons-download" style="margin-top:3px;"></span>
                    <?php _e('Veranstaltungen exportieren', 'dienstplan-verwaltung'); ?>
                </button>
            </div>
        </div>

        <!-- Dienste -->
        <div class="postbox" style="margin:0;">
            <div class="postbox-header"><h2>📑 <?php _e('Dienste', 'dienstplan-verwaltung'); ?></h2></div>
            <div class="inside">
                <p class="description"><?php printf(_n('%d Eintrag', '%d Einträge', count($stats['dienste']), 'dienstplan-verwaltung'), count($stats['dienste'])); ?></p>
                <p class="description" style="font-size:0.85em; color:#6b7280;"><?php _e('Spalten: veranstaltung_id, tag_nummer, verein_kuerzel, bereich_name, taetigkeit_name, von_zeit, bis_zeit, anzahl_personen, splittbar, status', 'dienstplan-verwaltung'); ?></p>
                <button class="button button-primary dp-export-btn" style="width:100%;" data-type="dienste">
                    <span class="dashicons dashicons-download" style="margin-top:3px;"></span>
                    <?php _e('Dienste exportieren', 'dienstplan-verwaltung'); ?>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($can_manage_clubs && $can_manage_events): ?>
        <!-- Alles als ZIP -->
        <div class="postbox" style="margin:0; border:2px solid #2271b1;">
            <div class="postbox-header"><h2>📦 <?php _e('Vollständiges Backup', 'dienstplan-verwaltung'); ?></h2></div>
            <div class="inside">
                <p class="description"><?php _e('Alle Daten als ZIP-Archiv mit separaten CSV-Dateien für jeden Typ.', 'dienstplan-verwaltung'); ?></p>
                <button class="button button-primary dp-export-btn" style="width:100%; background:#2271b1;" data-type="all">
                    <span class="dashicons dashicons-download" style="margin-top:3px;"></span>
                    <?php _e('Alle als ZIP exportieren', 'dienstplan-verwaltung'); ?>
                </button>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- end grid -->

    <div id="dp-export-status" style="display:none; margin-top:1rem; padding:0.75rem 1rem; background:#f0fdf4; border-left:4px solid #22c55e; border-radius:4px;">
        <span class="dashicons dashicons-yes-alt" style="color:#16a34a;"></span>
        <span id="dp-export-status-text"></span>
    </div>
</div>

<script>
(function($) {
    'use strict';
    var nonce = <?php echo json_encode(wp_create_nonce('dp_ajax_nonce')); ?>;

    $(document).on('click', '.dp-export-btn', function() {
        var type = $(this).data('type');
        var url  = ajaxurl + '?action=dp_export_csv&type=' + encodeURIComponent(type) + '&nonce=' + nonce;

        var $status = $('#dp-export-status');
        $status.hide();

        var link = document.createElement('a');
        link.href = url;
        link.download = 'dienstplan-export-' + type + '-<?php echo date('Y-m-d'); ?>' + (type === 'all' ? '.zip' : '.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        $('#dp-export-status-text').text('<?php _e('Download gestartet…', 'dienstplan-verwaltung'); ?>');
        $status.show();
        setTimeout(function() { $status.fadeOut(); }, 3000);
    });
})(jQuery);
</script>

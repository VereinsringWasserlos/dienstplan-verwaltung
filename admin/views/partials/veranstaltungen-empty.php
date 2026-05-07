<?php
/**
 * Veranstaltungen Empty State
 */
if (!defined('ABSPATH')) exit;
?>

<div class="empty-state">
    <div class="empty-state-icon">
        <span class="dashicons dashicons-calendar-alt"></span>
    </div>
    <h3><?php _e('Keine Veranstaltungen', 'dienstplan-verwaltung'); ?></h3>
    <p><?php _e('Erstellen Sie Ihre erste Veranstaltung, um zu beginnen.', 'dienstplan-verwaltung'); ?></p>
    <div style="display:flex; gap:0.5rem; justify-content:center; flex-wrap:wrap;">
        <button type="button" class="button button-primary button-large" onclick="openVeranstaltungModal()">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e('Erste Veranstaltung erstellen', 'dienstplan-verwaltung'); ?>
        </button>
        <button type="button" class="button button-primary button-large dp-open-import-popup" data-import-type="veranstaltungen">
            <span class="dashicons dashicons-upload"></span>
            <?php _e('Import', 'dienstplan-verwaltung'); ?>
        </button>
    </div>
</div>

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
    <button type="button" class="button button-primary button-large" onclick="openVeranstaltungModal()">
        <span class="dashicons dashicons-plus-alt"></span>
        <?php _e('Erste Veranstaltung erstellen', 'dienstplan-verwaltung'); ?>
    </button>
</div>

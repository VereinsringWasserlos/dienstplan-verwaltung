<?php
/**
 * Dienste Empty State
 */
if (!defined('ABSPATH')) exit;
?>

<div class="empty-state" style="text-align: center; padding: 3rem; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; margin-top: 2rem;">
    <div class="empty-state-icon" style="font-size: 64px; color: #a7aaad; margin-bottom: 1rem;">
        <span class="dashicons dashicons-calendar-alt"></span>
    </div>
    <h3><?php _e('Noch keine Dienste', 'dienstplan-verwaltung'); ?></h3>
    <p><?php _e('Erstellen Sie den ersten Dienst für diese Veranstaltung.', 'dienstplan-verwaltung'); ?></p>
    <button type="button" class="button button-primary button-large" onclick="openDienstModal()" style="margin-top: 1rem;">
        <span class="dashicons dashicons-plus-alt"></span>
        <?php _e('Ersten Dienst erstellen', 'dienstplan-verwaltung'); ?>
    </button>
</div>

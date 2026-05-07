<?php
/**
 * Vereine Empty State Partial
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/admin/views/partials
 */

if (!defined('ABSPATH')) exit;
?>

<div class="empty-state">
    <p><?php _e('Noch keine Vereine angelegt.', 'dienstplan-verwaltung'); ?></p>
    <div style="display:flex; gap:0.5rem; justify-content:center; flex-wrap:wrap;">
        <button type="button" class="button button-primary" onclick="openVereinModal()">
            <?php _e('Ersten Verein anlegen', 'dienstplan-verwaltung'); ?>
        </button>
        <button type="button" class="button button-primary dp-open-import-popup" data-import-type="vereine">
            <span class="dashicons dashicons-upload" style="font-size: 16px; width: 16px; height: 16px;"></span>
            <?php _e('Import', 'dienstplan-verwaltung'); ?>
        </button>
    </div>
</div>

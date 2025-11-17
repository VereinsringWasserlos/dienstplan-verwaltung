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
    <button type="button" class="button button-primary" onclick="openVereinModal()">
        <?php _e('Ersten Verein anlegen', 'dienstplan-verwaltung'); ?>
    </button>
</div>

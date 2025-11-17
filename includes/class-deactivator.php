<?php
/**
 * Plugin-Deaktivierung
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/includes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wird beim Deaktivieren des Plugins ausgeführt.
 */
class Dienstplan_Deactivator {

    /**
     * Plugin deaktivieren
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Rewrite-Rules flushen
        flush_rewrite_rules();
        
        // Scheduled Events entfernen
        self::clear_scheduled_events();
        
        // Log-Eintrag
        error_log('Dienstplan Verwaltung V2 deaktiviert');
    }
    
    /**
     * Geplante Events entfernen
     *
     * @since    1.0.0
     */
    private static function clear_scheduled_events() {
        // Beispiel für Cron-Jobs
        wp_clear_scheduled_hook('dienstplan_daily_cleanup');
        wp_clear_scheduled_hook('dienstplan_send_reminders');
    }
}

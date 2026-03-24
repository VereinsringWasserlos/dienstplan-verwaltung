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

        // Optional: komplette Plugin-Daten beim Deaktivieren löschen
        if (self::should_delete_all_data_on_deactivate()) {
            self::delete_all_plugin_data();
        }
        
        // Log-Eintrag
        error_log('Dienstplan Verwaltung V2 deaktiviert');
    }

    /**
     * Prüft, ob beim Deaktivieren alle Daten gelöscht werden sollen.
     *
     * @return bool
     */
    private static function should_delete_all_data_on_deactivate() {
        return (bool) get_option('dienstplan_delete_data_on_deactivate', 0);
    }

    /**
     * Löscht alle pluginbezogenen Daten für einen sauberen Neustart.
     */
    private static function delete_all_plugin_data() {
        global $wpdb;

        $table_prefix = $wpdb->prefix . 'dp_';

        // Alle Plugin-Tabellen löschen
        $tables = $wpdb->get_col(
            $wpdb->prepare('SHOW TABLES LIKE %s', $table_prefix . '%')
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
        }

        // Plugin-Optionen löschen
        $option_names = array(
            'dienstplan_version',
            'dienstplan_db_version',
            'dienstplan_db_migration_history',
            'dienstplan_settings',
            'dienstplan_auto_update_enabled',
            'dienstplan_roles_version',
            'dienstplan_roles_migration_pending',
            'dienstplan_portal_page_id',
            'dienstplan_show_portal_setup',
            'dienstplan_last_status_repair',
            'dienstplan_update_info',
            'dienstplan_delete_data_on_deactivate',
            'dp_site_name',
            'dp_date_format'
        );

        foreach ($option_names as $option_name) {
            delete_option($option_name);
        }

        // Benachrichtigungs-/Plugin-Meta löschen
        delete_metadata('user', 0, 'dienstplan_', '', true);

        error_log('Dienstplan Verwaltung V2: Alle Plugin-Daten beim Deaktivieren gelöscht');
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

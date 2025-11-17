<?php
/**
 * Plugin-Aktivierung
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/includes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wird beim Aktivieren des Plugins ausgeführt.
 */
class Dienstplan_Activator {

    /**
     * Plugin aktivieren
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Datenbank-Tabellen erstellen
        self::create_tables();
        
        // Standard-Optionen setzen
        self::set_default_options();
        
        // Rewrite-Rules flushen
        flush_rewrite_rules();
        
        // Log-Eintrag
        error_log('Dienstplan Verwaltung V2 aktiviert');
    }
    
    /**
     * Datenbank-Tabellen erstellen
     *
     * @since    1.0.0
     */
    private static function create_tables() {
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $database = new Dienstplan_Database(DIENSTPLAN_DB_PREFIX);
        $database->install();
    }
    
    /**
     * Standard-Optionen setzen
     *
     * @since    1.0.0
     */
    private static function set_default_options() {
        // Plugin-Version speichern
        add_option('dienstplan_version', DIENSTPLAN_VERSION);
        add_option('dienstplan_db_version', DIENSTPLAN_VERSION);
        
        // Standard-Einstellungen
        $default_settings = array(
            'installed_at' => current_time('mysql'),
            'email_notifications' => true,
            'public_registration' => true,
            'auto_approval' => false,
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
        );
        
        add_option('dienstplan_settings', $default_settings);
    }
}

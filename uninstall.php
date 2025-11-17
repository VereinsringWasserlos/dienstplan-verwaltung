<?php
/**
 * Wird beim Deinstallieren des Plugins ausgeführt
 * 
 * @package DienstplanVerwaltung
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
$prefix = $wpdb->prefix . 'dp_';

// Optional: Tabellen löschen (auskommentiert zur Sicherheit)
// $wpdb->query("DROP TABLE IF EXISTS {$prefix}vereine");
// $wpdb->query("DROP TABLE IF EXISTS {$prefix}settings");

// Optional: Optionen löschen
// delete_option('dienstplan_db_version');
// delete_option('dienstplan_settings');

// Optional: User Meta löschen
// delete_metadata('user', 0, 'dienstplan_', '', true);

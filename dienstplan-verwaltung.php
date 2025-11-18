<?php
/**
 * Plugin Name: Dienstplan Verwaltung V2
 * Plugin URI: https://vereinsring-wasserlos.de
 * Description: Moderne Dienstplan-Verwaltung für Vereine und Veranstaltungen
 * Version:           0.6.0
 * Author: Kai Naumann
 * Author URI: https://vereinsring-wasserlos.de
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dienstplan-verwaltung
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * 
 * @package Dienstplan_Verwaltung
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin-Konstanten
 */
define('DIENSTPLAN_VERSION', '0.6.0');
define('DIENSTPLAN_PLUGIN_FILE', __FILE__);
define('DIENSTPLAN_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('DIENSTPLAN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DIENSTPLAN_DB_PREFIX', 'dp_');

/**
 * Plugin-Aktivierung
 */
function activate_dienstplan_verwaltung() {
    require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-activator.php';
    Dienstplan_Activator::activate();
}

/**
 * Plugin-Deaktivierung
 */
function deactivate_dienstplan_verwaltung() {
    require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-deactivator.php';
    Dienstplan_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_dienstplan_verwaltung');
register_deactivation_hook(__FILE__, 'deactivate_dienstplan_verwaltung');

/**
 * Haupt-Plugin-Klasse laden
 */
require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-dienstplan-verwaltung.php';

/**
 * Plugin ausführen
 *
 * @since    1.0.0
 */
function run_dienstplan_verwaltung() {
    $plugin = new Dienstplan_Verwaltung();
    $plugin->run();
}

run_dienstplan_verwaltung();
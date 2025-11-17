<?php
/**
 * Internationalisierung (i18n)
 *
 * Lädt die Übersetzungsdateien für das Plugin.
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/includes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * i18n Klasse
 *
 * Definiert Internationalisierungs-Funktionalität.
 */
class Dienstplan_i18n {

    /**
     * Plugin Text-Domain
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $domain    Plugin Text-Domain
     */
    private $domain;

    /**
     * Initialisierung
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->domain = 'dienstplan-verwaltung';
    }

    /**
     * Plugin-Übersetzungen laden
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            $this->domain,
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    /**
     * Text-Domain abrufen
     *
     * @since    1.0.0
     * @return   string    Text-Domain
     */
    public function get_domain() {
        return $this->domain;
    }
}

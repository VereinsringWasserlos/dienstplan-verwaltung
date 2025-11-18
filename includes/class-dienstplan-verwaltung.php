<?php
/**
 * Haupt-Plugin-Klasse
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/includes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hauptklasse des Plugins
 *
 * Definiert Internationalisierung, Admin- und Public-Hooks.
 */
class Dienstplan_Verwaltung {

    /**
     * Hook-Loader
     *
     * @since    1.0.0
     * @access   protected
     * @var      Dienstplan_Loader    $loader    Hook-Loader
     */
    protected $loader;

    /**
     * Plugin-Name
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    Plugin-ID
     */
    protected $plugin_name;

    /**
     * Plugin-Version
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    Plugin-Version
     */
    protected $version;

    /**
     * Datenbank-Präfix
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $db_prefix    DB-Präfix
     */
    protected $db_prefix;

    /**
     * Initialisierung
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->plugin_name = 'dienstplan-verwaltung';
        $this->version = DIENSTPLAN_VERSION;
        $this->db_prefix = DIENSTPLAN_DB_PREFIX;

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->check_database_updates();
    }

    /**
     * Abhängigkeiten laden
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Autoloader
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/autoloader.php';
        
        // Core-Klassen
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-loader.php';
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-i18n.php';
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-dienstplan-roles.php';
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-dienstplan-notifications.php';
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-updater.php';
        
        // Admin-Klassen
        require_once DIENSTPLAN_PLUGIN_PATH . 'admin/class-admin.php';
        
        // Public-Klassen
        require_once DIENSTPLAN_PLUGIN_PATH . 'public/class-public.php';

        // Loader initialisieren
        $this->loader = new Dienstplan_Loader();
    }

    /**
     * Locale setzen
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Dienstplan_i18n();

        $this->loader->add_action('init', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Admin-Hooks registrieren
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Dienstplan_Admin(
            $this->get_plugin_name(),
            $this->get_version(),
            $this->db_prefix
        );

        // Updater initialisieren
        $plugin_updater = new Dienstplan_Updater();

        // Admin-Menü
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_menu');
        
        // Assets
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_assets');
        
        // Admin-Notices
        $this->loader->add_action('admin_notices', $plugin_admin, 'admin_notices');
        
        // AJAX-Actions für Vereine
        $this->loader->add_action('wp_ajax_dp_save_verein', $plugin_admin, 'ajax_save_verein');
        $this->loader->add_action('wp_ajax_dp_get_verein', $plugin_admin, 'ajax_get_verein');
        $this->loader->add_action('wp_ajax_dp_delete_verein', $plugin_admin, 'ajax_delete_verein');
        
        // AJAX-Actions für Veranstaltungen
        $this->loader->add_action('wp_ajax_dp_save_veranstaltung', $plugin_admin, 'ajax_save_veranstaltung');
        $this->loader->add_action('wp_ajax_dp_get_veranstaltung', $plugin_admin, 'ajax_get_veranstaltung');
        $this->loader->add_action('wp_ajax_dp_delete_veranstaltung', $plugin_admin, 'ajax_delete_veranstaltung');
        $this->loader->add_action('wp_ajax_dp_create_event_page', $plugin_admin, 'ajax_create_event_page');
        $this->loader->add_action('wp_ajax_dp_update_event_page', $plugin_admin, 'ajax_update_event_page');
        
        // AJAX-Actions für Benutzer
        $this->loader->add_action('wp_ajax_dp_check_email', $plugin_admin, 'ajax_check_email');
        $this->loader->add_action('wp_ajax_dp_create_new_contact', $plugin_admin, 'ajax_create_new_contact');
        $this->loader->add_action('wp_ajax_dp_get_users_by_ids', $plugin_admin, 'ajax_get_users_by_ids');
        $this->loader->add_action('wp_ajax_dp_get_all_users', $plugin_admin, 'ajax_get_all_users');
        
        // AJAX-Actions für Dienste
        $this->loader->add_action('wp_ajax_dp_get_veranstaltung_tage', $plugin_admin, 'ajax_get_veranstaltung_tage');
        $this->loader->add_action('wp_ajax_dp_save_dienst', $plugin_admin, 'ajax_save_dienst');
        $this->loader->add_action('wp_ajax_dp_get_dienst', $plugin_admin, 'ajax_get_dienst');
        $this->loader->add_action('wp_ajax_dp_delete_dienst', $plugin_admin, 'ajax_delete_dienst');
        $this->loader->add_action('wp_ajax_dp_copy_dienst', $plugin_admin, 'ajax_copy_dienst');
        $this->loader->add_action('wp_ajax_dp_create_bereich', $plugin_admin, 'ajax_create_bereich');
        $this->loader->add_action('wp_ajax_dp_create_taetigkeit', $plugin_admin, 'ajax_create_taetigkeit');
        $this->loader->add_action('wp_ajax_dp_create_verein', $plugin_admin, 'ajax_create_verein');
        $this->loader->add_action('wp_ajax_dp_get_taetigkeiten_by_bereich', $plugin_admin, 'ajax_get_taetigkeiten_by_bereich');
        $this->loader->add_action('wp_ajax_dp_check_dienst_status', $plugin_admin, 'ajax_check_dienst_status');
        
        // AJAX-Actions für Bulk-Aktionen
        $this->loader->add_action('wp_ajax_bulk_delete_dienste', $plugin_admin, 'ajax_bulk_delete_dienste');
        $this->loader->add_action('wp_ajax_bulk_update_dienste', $plugin_admin, 'ajax_bulk_update_dienste');
        
        // AJAX-Actions für Bereiche & Tätigkeiten
        $this->loader->add_action('wp_ajax_get_bereich', $plugin_admin, 'ajax_get_bereich');
        $this->loader->add_action('wp_ajax_save_bereich', $plugin_admin, 'ajax_save_bereich');
        $this->loader->add_action('wp_ajax_delete_bereich', $plugin_admin, 'ajax_delete_bereich');
        $this->loader->add_action('wp_ajax_get_taetigkeit', $plugin_admin, 'ajax_get_taetigkeit');
        $this->loader->add_action('wp_ajax_save_taetigkeit', $plugin_admin, 'ajax_save_taetigkeit');
        $this->loader->add_action('wp_ajax_delete_taetigkeit', $plugin_admin, 'ajax_delete_taetigkeit');
        $this->loader->add_action('wp_ajax_toggle_taetigkeit_status', $plugin_admin, 'ajax_toggle_taetigkeit_status');
        $this->loader->add_action('wp_ajax_bulk_delete_taetigkeiten', $plugin_admin, 'ajax_bulk_delete_taetigkeiten');
        $this->loader->add_action('wp_ajax_bulk_update_taetigkeiten', $plugin_admin, 'ajax_bulk_update_taetigkeiten');
        
        // AJAX-Actions für Besetzungs-Verwaltung im Backend
        $this->loader->add_action('wp_ajax_dp_get_dienst_besetzung', $plugin_admin, 'ajax_get_dienst_besetzung');
        $this->loader->add_action('wp_ajax_dp_admin_assign_slot', $plugin_admin, 'ajax_admin_assign_slot');
        $this->loader->add_action('wp_ajax_dp_admin_remove_slot', $plugin_admin, 'ajax_admin_remove_slot');
        
        // AJAX-Actions für Mitarbeiter-Verwaltung im Backend
        $this->loader->add_action('wp_ajax_dp_get_mitarbeiter', $plugin_admin, 'ajax_get_mitarbeiter');
        $this->loader->add_action('wp_ajax_dp_save_mitarbeiter', $plugin_admin, 'ajax_save_mitarbeiter');
        $this->loader->add_action('wp_ajax_dp_add_mitarbeiter', $plugin_admin, 'ajax_save_mitarbeiter'); // Alias für neuen Mitarbeiter
        $this->loader->add_action('wp_ajax_dp_delete_mitarbeiter', $plugin_admin, 'ajax_delete_mitarbeiter');
        $this->loader->add_action('wp_ajax_dp_delete_mitarbeiter_bulk', $plugin_admin, 'ajax_delete_mitarbeiter_bulk');
        $this->loader->add_action('wp_ajax_dp_get_mitarbeiter_dienste', $plugin_admin, 'ajax_get_mitarbeiter_dienste');
        
        // Import/Export AJAX Actions
        // Export wird über admin_init in class-admin.php handle_export() behandelt
        $this->loader->add_action('wp_ajax_dp_import_csv', $plugin_admin, 'ajax_import_csv');
        
        // Login-Redirect für Dienstplan-Rollen
        $this->loader->add_filter('login_redirect', $plugin_admin, 'login_redirect', 10, 3);
        
        // Dashboard-Widget
        $this->loader->add_action('wp_dashboard_setup', $plugin_admin, 'add_dashboard_widget');
    }

    /**
     * Public-Hooks registrieren
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Dienstplan_Public(
            $this->get_plugin_name(),
            $this->get_version(),
            $this->db_prefix
        );

        // Assets
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_assets');
        
        // Shortcodes
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        
        // AJAX-Actions für öffentliche Mitarbeiter-Eintragung (auch für nicht-eingeloggte User)
        $this->loader->add_action('wp_ajax_dp_assign_slot', $plugin_public, 'ajax_assign_slot');
        $this->loader->add_action('wp_ajax_nopriv_dp_assign_slot', $plugin_public, 'ajax_assign_slot');
        $this->loader->add_action('wp_ajax_dp_remove_assignment', $plugin_public, 'ajax_remove_assignment');
        $this->loader->add_action('wp_ajax_nopriv_dp_remove_assignment', $plugin_public, 'ajax_remove_assignment');
        
        // AJAX für Dienst-Anmeldung (Frontend-Formular)
        $this->loader->add_action('wp_ajax_dp_register_service', $plugin_public, 'ajax_register_service');
        $this->loader->add_action('wp_ajax_nopriv_dp_register_service', $plugin_public, 'ajax_register_service');
    }

    /**
     * Datenbank-Updates prüfen
     *
     * @since    1.0.0
     * @access   private
     */
    private function check_database_updates() {
        $db_version = get_option('dienstplan_db_version', '0');
        
        // Datenbank-Objekt erstellen für Migrationen
        $database = new Dienstplan_Database($this->db_prefix);
        
        if (version_compare($db_version, $this->version, '<')) {
            $database->install();
            
            // Benachrichtigungssystem installieren
            $notifications = new Dienstplan_Notifications($this->db_prefix);
            $notifications->install();
            
            update_option('dienstplan_db_version', $this->version);
            
            // Wenn Datenbank aktualisiert wurde, starten Sie die Reparatur
            $this->repair_dienst_status();
        }
        
        // Führe Migrationen immer aus (sie prüfen selbst ob nötig)
        $database->migrate_veranstaltungen_add_seite_id();
        
        // Blockiere Reparatur auf Import/Export-Seite zur Performance
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        if ($current_page === 'dienstplan-import-export') {
            return;
        }
        
        // Rate-Limiting für Dienst-Status Reparatur
        // Prüfen Sie nur alle 6 Stunden, ob eine Reparatur notwendig ist
        $last_repair = get_transient('dienstplan_last_status_repair');
        if ($last_repair === false) {
            // Führen Sie Reparatur aus
            $this->repair_dienst_status();
            // Setzen Sie Transient auf 6 Stunden (21600 Sekunden)
            set_transient('dienstplan_last_status_repair', time(), 21600);
        }
        
        // Rollen installieren/aktualisieren bei jedem Laden
        $roles_version = get_option('dienstplan_roles_version', '0');
        if (version_compare($roles_version, $this->version, '<')) {
            Dienstplan_Roles::install_roles();
            update_option('dienstplan_roles_version', $this->version);
        }
    }
    
    /**
     * Repariere Dienst-Status basierend auf Pflichtfeldern
     * Diese Funktion überprüft alle Dienste und setzt den Status
     * basierend darauf, ob alle Pflichtfelder gefüllt sind
     *
     * @access private
     */
    private function repair_dienst_status() {
        global $wpdb;
        
        $table = $wpdb->prefix . $this->db_prefix . 'dienste';
        
        // Prüfe ob Tabelle existiert
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") != $table) {
            return;
        }
        
        // Alle Dienste abrufen
        $dienste = $wpdb->get_results("SELECT * FROM {$table}");
        
        if (empty($dienste)) {
            return;
        }
        
        $updated = 0;
        
        foreach ($dienste as $dienst) {
            // Überprüfe ob alle Pflichtfelder gefüllt sind
            $required_fields = array(
                'veranstaltung_id' => $dienst->veranstaltung_id,
                'tag_id' => $dienst->tag_id,
                'verein_id' => $dienst->verein_id,
                'bereich_id' => $dienst->bereich_id,
                'taetigkeit_id' => $dienst->taetigkeit_id,
                'von_zeit' => $dienst->von_zeit,
                'bis_zeit' => $dienst->bis_zeit,
                'anzahl_personen' => $dienst->anzahl_personen
            );
            
            // Prüfe ob alle Felder gefüllt sind
            $all_filled = true;
            foreach ($required_fields as $field_value) {
                if (empty($field_value) || $field_value === 0 || $field_value === '0') {
                    $all_filled = false;
                    break;
                }
            }
            
            // Bestimme neuen Status
            $new_status = $all_filled ? 'geplant' : 'unvollständig';
            
            // Update wenn Status anders ist oder nicht gesetzt
            if (empty($dienst->status) || $dienst->status !== $new_status) {
                $wpdb->update(
                    $table,
                    array('status' => $new_status),
                    array('id' => $dienst->id),
                    array('%s'),
                    array('%d')
                );
                $updated++;
            }
        }
    }

    /**
     * Plugin ausführen
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * Plugin-Name abrufen
     *
     * @since     1.0.0
     * @return    string    Plugin-Name
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Hook-Loader abrufen
     *
     * @since     1.0.0
     * @return    Dienstplan_Loader    Hook-Loader
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Plugin-Version abrufen
     *
     * @since     1.0.0
     * @return    string    Plugin-Version
     */
    public function get_version() {
        return $this->version;
    }
}

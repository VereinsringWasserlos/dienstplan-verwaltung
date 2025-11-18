<?php
/**
 * Admin-Bereich des Plugins
 *
 * @package DienstplanVerwaltung
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dienstplan_Admin {
    
    private $plugin_name;
    private $version;
    private $db_prefix;
    
    /**
     * Constructor
     */
    public function __construct($plugin_name, $version, $db_prefix) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->db_prefix = $db_prefix;
        
        // Hook für Export (vor WordPress-Headers)
        add_action('admin_init', array($this, 'handle_export'));
        
        // WordPress-Footer-Banner ausblenden
        add_filter('admin_footer_text', '__return_empty_string');
        add_filter('update_footer', '__return_empty_string');
    }
    
    /**
     * Admin-Menü registrieren
     */
    public function add_menu() {
        // Hauptmenü - für alle mit Dienstplan-Rechten
        $main_capability = 'read'; // Jeder der irgendeine DP-Berechtigung hat
        
        add_menu_page(
            __('Dienstplan', 'dienstplan-verwaltung'),
            __('Dienstplan', 'dienstplan-verwaltung'),
            $main_capability,
            'dienstplan',
            array($this, 'display_dashboard'),
            'dashicons-calendar-alt',
            30
        );
        
        // Dashboard (einziger sichtbarer Menüpunkt)
        add_submenu_page(
            'dienstplan',
            __('Dashboard', 'dienstplan-verwaltung'),
            __('Dashboard', 'dienstplan-verwaltung'),
            $main_capability,
            'dienstplan',
            array($this, 'display_dashboard')
        );
        
        // Alle anderen Seiten als versteckte Menüpunkte (nicht im Menü sichtbar, aber über Links erreichbar)
        
        // Vereine
        if (Dienstplan_Roles::can_manage_clubs() || current_user_can('manage_options')) {
            add_submenu_page(
                '', // Leerer String = nicht im Menü anzeigen
                __('Vereine', 'dienstplan-verwaltung'),
                __('Vereine', 'dienstplan-verwaltung'),
                Dienstplan_Roles::CAP_MANAGE_CLUBS,
                'dienstplan-vereine',
                array($this, 'display_vereine')
            );
        }
        
        // Veranstaltungen
        if (Dienstplan_Roles::can_manage_events() || current_user_can('manage_options')) {
            add_submenu_page(
                '',
                __('Veranstaltungen', 'dienstplan-verwaltung'),
                __('Veranstaltungen', 'dienstplan-verwaltung'),
                Dienstplan_Roles::CAP_MANAGE_EVENTS,
                'dienstplan-veranstaltungen',
                array($this, 'display_veranstaltungen')
            );
        }
        
        // Bereiche & Tätigkeiten
        if (Dienstplan_Roles::can_manage_events() || current_user_can('manage_options')) {
            add_submenu_page(
                '',
                __('Bereiche & Tätigkeiten', 'dienstplan-verwaltung'),
                __('Bereiche & Tätigkeiten', 'dienstplan-verwaltung'),
                Dienstplan_Roles::CAP_MANAGE_EVENTS,
                'dienstplan-bereiche',
                array($this, 'display_bereiche_taetigkeiten')
            );
        }
        
        // Mitarbeiter
        if (Dienstplan_Roles::can_manage_events() || current_user_can('manage_options')) {
            add_submenu_page(
                '',
                __('Mitarbeiter', 'dienstplan-verwaltung'),
                __('Mitarbeiter', 'dienstplan-verwaltung'),
                Dienstplan_Roles::CAP_MANAGE_EVENTS,
                'dienstplan-mitarbeiter',
                array($this, 'display_mitarbeiter')
            );
        }
        
        // Dienste
        if (Dienstplan_Roles::can_manage_events() || current_user_can('manage_options')) {
            add_submenu_page(
                '',
                __('Dienste', 'dienstplan-verwaltung'),
                __('Dienste', 'dienstplan-verwaltung'),
                Dienstplan_Roles::CAP_MANAGE_EVENTS,
                'dienstplan-dienste',
                array($this, 'display_dienste')
            );
            
            // Dienst-Übersicht (Timeline)
            add_submenu_page(
                '',
                __('Dienst-Übersicht', 'dienstplan-verwaltung'),
                __('Dienst-Übersicht', 'dienstplan-verwaltung'),
                Dienstplan_Roles::CAP_MANAGE_EVENTS,
                'dienstplan-overview',
                array($this, 'display_overview')
            );
        }
        
        // Einstellungen
        if (Dienstplan_Roles::can_manage_settings() || current_user_can('manage_options')) {
            add_submenu_page(
                '',
                __('Einstellungen', 'dienstplan-verwaltung'),
                __('Einstellungen', 'dienstplan-verwaltung'),
                Dienstplan_Roles::CAP_MANAGE_SETTINGS,
                'dienstplan-einstellungen',
                array($this, 'display_settings')
            );
            
            // Import/Export
            add_submenu_page(
                '',
                __('Import/Export', 'dienstplan-verwaltung'),
                __('Import/Export', 'dienstplan-verwaltung'),
                Dienstplan_Roles::CAP_MANAGE_SETTINGS,
                'dienstplan-import-export',
                array($this, 'display_import_export')
            );
        }
        
        // Benutzerverwaltung
        if (Dienstplan_Roles::can_manage_users() || current_user_can('manage_options')) {
            add_submenu_page(
                '',
                __('Benutzerverwaltung', 'dienstplan-verwaltung'),
                __('Benutzerverwaltung', 'dienstplan-verwaltung'),
                Dienstplan_Roles::CAP_MANAGE_USERS,
                'dienstplan-benutzer',
                array($this, 'display_users')
            );
        }
        
        // Dokumentation - für alle mit Dienstplan-Rechten
        add_submenu_page(
            '',
            __('Dokumentation', 'dienstplan-verwaltung'),
            __('Dokumentation', 'dienstplan-verwaltung'),
            $main_capability,
            'dienstplan-dokumentation',
            array($this, 'display_documentation')
        );
        
        // Updates - nur für WordPress-Admins
        if (current_user_can('manage_options')) {
            add_submenu_page(
                '',
                __('Updates', 'dienstplan-verwaltung'),
                __('Updates', 'dienstplan-verwaltung'),
                'manage_options',
                'dienstplan-updates',
                array($this, 'display_updates')
            );
        }
        
        // Debug & Wartung - nur für WordPress-Admins
        if (current_user_can('manage_options')) {
            add_submenu_page(
                '',
                __('Debug & Wartung', 'dienstplan-verwaltung'),
                __('Debug & Wartung', 'dienstplan-verwaltung'),
                'manage_options',
                'dienstplan-debug',
                array($this, 'display_debug')
            );
        }
        
        // Hook für Seitentitel der versteckten Seiten
        add_filter('admin_title', array($this, 'set_hidden_page_titles'), 10, 2);
    }
    
    /**
     * Setzt die korrekten Titel für versteckte Admin-Seiten
     */
    public function set_hidden_page_titles($admin_title, $title) {
        global $plugin_page;
        
        // Sicherstellen dass wir nie null zurückgeben
        $admin_title = $admin_title ?? '';
        $title = $title ?? '';
        
        $page_titles = array(
            'dienstplan-vereine' => __('Vereine', 'dienstplan-verwaltung'),
            'dienstplan-veranstaltungen' => __('Veranstaltungen', 'dienstplan-verwaltung'),
            'dienstplan-bereiche' => __('Bereiche & Tätigkeiten', 'dienstplan-verwaltung'),
            'dienstplan-mitarbeiter' => __('Mitarbeiter', 'dienstplan-verwaltung'),
            'dienstplan-dienste' => __('Dienste', 'dienstplan-verwaltung'),
            'dienstplan-overview' => __('Dienst-Übersicht', 'dienstplan-verwaltung'),
            'dienstplan-einstellungen' => __('Einstellungen', 'dienstplan-verwaltung'),
            'dienstplan-import-export' => __('Import/Export', 'dienstplan-verwaltung'),
            'dienstplan-benutzer' => __('Benutzerverwaltung', 'dienstplan-verwaltung'),
            'dienstplan-dokumentation' => __('Dokumentation', 'dienstplan-verwaltung'),
            'dienstplan-updates' => __('Updates', 'dienstplan-verwaltung'),
            'dienstplan-debug' => __('Debug & Wartung', 'dienstplan-verwaltung'),
        );
        
        if (isset($plugin_page) && isset($page_titles[$plugin_page])) {
            $blog_name = get_bloginfo('name');
            $blog_name = $blog_name ?? '';
            return $page_titles[$plugin_page] . ' &lsaquo; ' . $blog_name;
        }
        
        // Immer einen String zurückgeben, nie null
        return (string) $admin_title;
    }
    
    /**
     * Assets (CSS/JS) laden
     */
    public function enqueue_assets($hook) {
        // Nur auf Dienstplan-Seiten laden
        if (strpos($hook, 'dienstplan') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'dp-admin-styles',
            DIENSTPLAN_PLUGIN_URL . 'assets/css/dp-admin.css',
            array(),
            $this->version
        );
        
        // JS
        wp_enqueue_script('jquery');
        
        // WordPress Media Uploader für Logo-Upload
        wp_enqueue_media();
        
        wp_enqueue_script(
            'dp-admin-scripts',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-admin.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Admin Modal Functions (CRUD für alle Entitäten)
        wp_enqueue_script(
            'dp-admin-modals',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-admin-modals.js',
            array('jquery', 'dp-admin-scripts'),
            $this->version,
            true
        );
        
        // Dienst-Modal Script (für Dienste-Verwaltung)
        wp_enqueue_script(
            'dp-dienst-modal',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-dienst-modal.js',
            array('jquery', 'dp-admin-scripts'),
            $this->version,
            true
        );
        
        // Dienste-Tabelle Script (für Dienste-Übersicht mit Bulk-Aktionen)
        wp_enqueue_script(
            'dp-dienste-table',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-dienste-table.js',
            array('jquery', 'dp-admin-scripts'),
            $this->version,
            true
        );
        
        // Bulk-Update-Modals Script (für schöne Eingabemasken bei Bulk-Aktionen)
        wp_enqueue_script(
            'dp-bulk-update-modals',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-bulk-update-modals.js',
            array('jquery', 'dp-admin-scripts'),
            $this->version,
            true
        );
        
        // Besetzungs-Modal Script (für Slot-Zuweisung)
        wp_enqueue_script(
            'dp-besetzung-modal',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-besetzung-modal.js',
            array('jquery', 'dp-admin-scripts'),
            $this->version,
            true
        );
        
        // Vereine-Modal Script (für Vereine-Verwaltung)
        wp_enqueue_script(
            'dp-vereine-modal',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-vereine-modal.js',
            array('jquery', 'dp-admin-scripts'),
            $this->version,
            true
        );
        
        // Veranstaltungen-Table Script (für Veranstaltungen-Übersicht mit Dropdown-Aktionen)
        wp_enqueue_script(
            'dp-veranstaltungen-table',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-veranstaltungen-table.js',
            array('jquery', 'dp-admin-scripts'),
            $this->version,
            true
        );
        
        // Veranstaltungen-Modal Script (für Veranstaltungen-Verwaltung)
        wp_enqueue_script(
            'dp-veranstaltungen-modal',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-veranstaltungen-modal.js',
            array('jquery', 'dp-admin-scripts'),
            $this->version,
            true
        );
        
        // Mitarbeiter-Modal Script (für Mitarbeiter-Verwaltung)
        wp_enqueue_script(
            'dp-mitarbeiter-modal',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-mitarbeiter-modal.js',
            array('jquery', 'dp-admin-scripts'),
            $this->version,
            true
        );
        
        // Mitarbeiter-Dienste-Modal Script (für Mitarbeiter-Dienste-Ansicht)
        wp_enqueue_script(
            'dp-mitarbeiter-dienste-modal',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-mitarbeiter-dienste-modal.js',
            array('jquery', 'dp-admin-scripts'),
            $this->version,
            true
        );
        
        // Bereiche-Tätigkeiten Script (für Bereiche & Tätigkeiten Verwaltung)
        wp_enqueue_script(
            'dp-bereiche-taetigkeiten',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-bereiche-taetigkeiten.js',
            array('jquery', 'dp-admin-scripts'),
            $this->version,
            true
        );
        
        // Overview Script (für Dienst-Übersicht Timeline)
        wp_enqueue_script(
            'dp-overview',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-overview.js',
            array('jquery', 'dp-admin-scripts'),
            $this->version,
            true
        );
        
        // Mitarbeiter Script (für Mitarbeiter-Seite mit Bulk-Aktionen)
        wp_enqueue_script(
            'dp-mitarbeiter',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-mitarbeiter.js',
            array('jquery', 'dp-admin-scripts'),
            $this->version,
            true
        );
        
        // Import-Export Script (für Import/Export-Verwaltung)
        wp_enqueue_script(
            'dp-import-export',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-import-export.js',
            array('jquery', 'dp-admin-scripts'),
            $this->version,
            true
        );
        
        // AJAX-Daten für JavaScript
        $localize_data = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dp_ajax_nonce'),
            'i18n' => array(
                'confirm_delete' => __('Wirklich löschen?', 'dienstplan-verwaltung'),
                'error' => __('Ein Fehler ist aufgetreten', 'dienstplan-verwaltung'),
            )
        );
        
        // Füge Veranstaltungs-Vorauswahl hinzu falls vorhanden
        if (isset($_GET['veranstaltung']) && intval($_GET['veranstaltung']) > 0) {
            $localize_data['selectedVeranstaltung'] = intval($_GET['veranstaltung']);
        }
        
        wp_localize_script('dp-admin-scripts', 'dpAjax', $localize_data);
    }
    
    /**
     * Admin-Benachrichtigungen
     */
    public function admin_notices() {
        // Erfolgs-Meldungen, Fehler etc.
        if (isset($_GET['dp_message'])) {
            $message = sanitize_text_field($_GET['dp_message']);
            $class = isset($_GET['dp_type']) && $_GET['dp_type'] === 'error' ? 'error' : 'success';
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($class),
                esc_html($message)
            );
        }
    }
    
    public function display_dashboard() {
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        $stats = $db->get_stats();
        
        // Lade aktuelle Veranstaltungen für Dashboard
        $aktuelle_veranstaltungen = $db->get_veranstaltungen();
        
        // Lade letzte Dienste
        $letzte_dienste = $db->get_recent_dienste(5);
        
        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/dashboard.php';
    }
    
    public function display_vereine() {
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        $vereine = $db->get_vereine();
        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/vereine.php';
    }
    
    public function display_veranstaltungen() {
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        $veranstaltungen = $db->get_veranstaltungen();
        $vereine = $db->get_vereine(); // Für Checkboxen im Modal
        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/veranstaltungen.php';
    }
    
    public function display_users() {
        $all_users = get_users();
        $dp_users = Dienstplan_Roles::get_all_dp_users();
        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/benutzerverwaltung.php';
    }
    
    public function display_dienste() {
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        // Lade alle benötigten Daten
        $veranstaltungen = $db->get_veranstaltungen();
        $vereine = $db->get_vereine();
        $bereiche = $db->get_bereiche(true); // nur aktive
        $taetigkeiten = $db->get_taetigkeiten(true); // nur aktive
        
        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/dienste.php';
    }
    
    public function display_overview() {
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        // Lade alle benötigten Daten
        $veranstaltungen = $db->get_veranstaltungen();
        $bereiche = $db->get_bereiche(true); // nur aktive
        
        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/overview.php';
    }
    
    public function display_bereiche_taetigkeiten() {
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/bereiche-taetigkeiten.php';
    }
    
    public function display_mitarbeiter() {
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        // Filter-Parameter
        $filter_verein = isset($_GET['filter_verein']) ? intval($_GET['filter_verein']) : 0;
        $filter_veranstaltung = isset($_GET['filter_veranstaltung']) ? intval($_GET['filter_veranstaltung']) : 0;
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        
        // Lade Daten für Filter
        $vereine = $db->get_vereine(true);
        $veranstaltungen = $db->get_veranstaltungen();
        
        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/mitarbeiter.php';
    }
    
    public function display_settings() {
        $notifications = new Dienstplan_Notifications($this->db_prefix);
        $current_user_settings = $notifications->get_user_settings(get_current_user_id());
        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/einstellungen.php';
    }
    
    public function display_import_export() {
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        // Statistiken für Export
        $stats = array(
            'vereine' => $db->get_vereine(true),
            'veranstaltungen' => $db->get_veranstaltungen(),
            'dienste' => $db->get_dienste()
        );
        
        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/import-export.php';
    }
    
    public function display_debug() {
        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/debug.php';
    }
    
    public function display_documentation() {
        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/documentation.php';
    }
    
    public function display_updates() {
        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/updates.php';
    }
    
    // === AJAX HANDLERS ===
    
    public function ajax_save_verein() {
        try {
            check_ajax_referer('dp_ajax_nonce', 'nonce');
            
            // Berechtigungsprüfung
            if (!Dienstplan_Roles::can_manage_clubs()) {
                wp_send_json_error(array('message' => 'Keine Berechtigung zum Verwalten von Vereinen'));
                return;
            }
            
            // Prüfe ob erforderliche Felder vorhanden sind
            if (empty($_POST['name']) || empty($_POST['kuerzel'])) {
                wp_send_json_error(array('message' => 'Name und Kürzel sind erforderlich'));
                return;
            }
            
            require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
            $db = new Dienstplan_Database($this->db_prefix);
            
            $data = array(
                'name' => sanitize_text_field($_POST['name']),
                'kuerzel' => strtoupper(sanitize_text_field($_POST['kuerzel'])),
                'beschreibung' => sanitize_textarea_field($_POST['beschreibung'] ?? ''),
                'logo_id' => !empty($_POST['logo_id']) ? intval($_POST['logo_id']) : null,
                'kontakt_name' => sanitize_text_field($_POST['kontakt_name'] ?? ''),
                'kontakt_email' => sanitize_email($_POST['kontakt_email'] ?? ''),
                'kontakt_telefon' => sanitize_text_field($_POST['kontakt_telefon'] ?? ''),
                'aktiv' => isset($_POST['aktiv']) ? 1 : 0
            );
            
            $verein_id = !empty($_POST['verein_id']) ? intval($_POST['verein_id']) : 0;
            
            if ($verein_id > 0) {
                // Update
                $result = $db->update_verein($verein_id, $data);
                if ($result === false) {
                    wp_send_json_error(array('message' => 'Fehler: Name oder Kürzel wird bereits von einem anderen Verein verwendet'));
                    return;
                }
                
                // Verantwortliche speichern
                $verantwortliche = isset($_POST['verantwortliche']) && is_array($_POST['verantwortliche']) 
                    ? array_map('intval', $_POST['verantwortliche']) 
                    : array();
                $db->save_verein_verantwortliche($verein_id, $verantwortliche);
                
                $message = 'Verein aktualisiert';
            } else {
                // Neu anlegen
                $result = $db->add_verein($data);
                if ($result === false) {
                    wp_send_json_error(array('message' => 'Fehler: Ein Verein mit diesem Namen oder Kürzel existiert bereits'));
                    return;
                }
                
                // Neue Verein-ID holen
                global $wpdb;
                $verein_id = $wpdb->insert_id;
                
                // Verantwortliche speichern
                $verantwortliche = isset($_POST['verantwortliche']) && is_array($_POST['verantwortliche']) 
                    ? array_map('intval', $_POST['verantwortliche']) 
                    : array();
                $db->save_verein_verantwortliche($verein_id, $verantwortliche);
                
                $message = 'Verein angelegt';
                
                // WordPress-Benutzer erstellen wenn gewünscht
                if (!empty($_POST['create_user']) && !empty($data['kontakt_email'])) {
                    $user_created = $this->create_wordpress_user(
                        $data['kontakt_email'],
                        $data['kontakt_name'],
                        $_POST['user_role'] ?? ''
                    );
                    
                    if ($user_created) {
                        $message .= ' und Benutzer wurde eingeladen';
                    }
                }
            }
            
            wp_send_json_success(array('message' => $message));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Fehler: ' . $e->getMessage()));
        }
    }
    
    public function ajax_check_email() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_clubs() && !Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $email = sanitize_email($_POST['email']);
        
        if (empty($email)) {
            wp_send_json_error(array('message' => 'Keine E-Mail-Adresse angegeben'));
            return;
        }
        
        $user = get_user_by('email', $email);
        
        if ($user) {
            wp_send_json_success(array(
                'exists' => true,
                'user_id' => $user->ID,
                'user_name' => $user->display_name,
                'user_roles' => $user->roles
            ));
        } else {
            wp_send_json_success(array(
                'exists' => false
            ));
        }
    }
    
    public function ajax_create_new_contact() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_clubs() && !Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        try {
            $name = sanitize_text_field($_POST['name']);
            $email = sanitize_email($_POST['email']);
            $role = sanitize_text_field($_POST['role'] ?? '');
            
            if (empty($name) || empty($email)) {
                wp_send_json_error(array('message' => 'Name und E-Mail sind erforderlich'));
                return;
            }
            
            // Prüfen ob E-Mail bereits existiert
            if (get_user_by('email', $email)) {
                wp_send_json_error(array('message' => 'Ein Benutzer mit dieser E-Mail-Adresse existiert bereits'));
                return;
            }
            
            // Benutzer erstellen
            $user_id = $this->create_wordpress_user($email, $name, $role);
            
            if ($user_id) {
                $user = get_user_by('id', $user_id);
                wp_send_json_success(array(
                    'message' => 'Kontakt erfolgreich angelegt',
                    'user_id' => $user_id,
                    'user_name' => $user->display_name,
                    'user_email' => $user->user_email
                ));
            } else {
                wp_send_json_error(array('message' => 'Fehler beim Anlegen des Benutzers'));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Fehler: ' . $e->getMessage()));
        }
    }
    
    public function ajax_get_users_by_ids() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
        }
        
        $user_ids = isset($_POST['user_ids']) ? array_map('intval', (array)$_POST['user_ids']) : array();
        
        if (empty($user_ids)) {
            wp_send_json_success(array());
            return;
        }
        
        $users = array();
        foreach ($user_ids as $user_id) {
            $user = get_user_by('id', $user_id);
            if ($user) {
                $users[] = array(
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'email' => $user->user_email
                );
            }
        }
        
        wp_send_json_success($users);
    }
    
    public function ajax_get_all_users() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        // Lade alle Benutzer
        $wp_users = get_users(array(
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));
        
        $users = array();
        foreach ($wp_users as $user) {
            $users[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email
            );
        }
        
        wp_send_json_success($users);
    }
    
    public function ajax_get_veranstaltung_tage() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $veranstaltung_id = isset($_POST['veranstaltung_id']) ? intval($_POST['veranstaltung_id']) : 0;
        
        if ($veranstaltung_id <= 0) {
            wp_send_json_error(array('message' => 'Keine Veranstaltung angegeben'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $tage = $db->get_veranstaltung_tage($veranstaltung_id);
        wp_send_json_success($tage);
    }
    
    public function ajax_save_dienst() {
        try {
            check_ajax_referer('dp_ajax_nonce', 'nonce');
            
            if (!Dienstplan_Roles::can_manage_events()) {
                wp_send_json_error(array('message' => 'Keine Berechtigung zum Verwalten von Diensten'));
                return;
            }
            
            // Prüfe welche Pflichtfelder gefüllt sind
            $required = array('veranstaltung_id', 'tag_id', 'verein_id', 'bereich_id', 'taetigkeit_id', 'von_zeit', 'bis_zeit', 'anzahl_personen');
            $all_required_filled = true;
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    $all_required_filled = false;
                    break;
                }
            }
            
            require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
            $db = new Dienstplan_Database($this->db_prefix);
            
            // Formatiere Zeiten für Datenbank (HH:MM:SS Format) und konvertiere zu UTC
            $von_zeit = !empty($_POST['von_zeit']) ? $this->convert_time_to_utc($_POST['von_zeit']) : null;
            $bis_zeit = !empty($_POST['bis_zeit']) ? $this->convert_time_to_utc($_POST['bis_zeit']) : null;
            
            $data = array(
                'veranstaltung_id' => !empty($_POST['veranstaltung_id']) ? intval($_POST['veranstaltung_id']) : 0,
                'tag_id' => !empty($_POST['tag_id']) ? intval($_POST['tag_id']) : 0,
                'verein_id' => !empty($_POST['verein_id']) ? intval($_POST['verein_id']) : 0,
                'bereich_id' => !empty($_POST['bereich_id']) ? intval($_POST['bereich_id']) : 0,
                'taetigkeit_id' => !empty($_POST['taetigkeit_id']) ? intval($_POST['taetigkeit_id']) : 0,
                'von_zeit' => $von_zeit,
                'bis_zeit' => $bis_zeit,
                'bis_datum' => !empty($_POST['bis_folgetag']) ? '1' : null,
                'anzahl_personen' => !empty($_POST['anzahl_personen']) ? intval($_POST['anzahl_personen']) : 0,
                'splittbar' => !empty($_POST['splittbar']) ? 1 : 0,
                'besonderheiten' => sanitize_textarea_field($_POST['besonderheiten'] ?? ''),
                'status' => $all_required_filled ? 'geplant' : 'unvollständig'
            );
            
            $dienst_id = !empty($_POST['dienst_id']) ? intval($_POST['dienst_id']) : 0;
            
            if ($dienst_id > 0) {
                // Update
                // Prüfe ob Slots neu erstellt werden müssen (bei Änderung von splittbar oder anzahl_personen)
                global $wpdb;
                $table_dienste = $wpdb->prefix . $this->db_prefix . 'dienste';
                $old_dienst = $wpdb->get_row($wpdb->prepare("SELECT splittbar, anzahl_personen FROM {$table_dienste} WHERE id = %d", $dienst_id));
                
                $result = $db->update_dienst($dienst_id, $data);
                $message = 'Dienst aktualisiert';
                $return_id = $dienst_id;
                
                // Wenn splittbar oder anzahl_personen geändert wurde, Slots neu erstellen
                if ($old_dienst && ($old_dienst->splittbar != $data['splittbar'] || $old_dienst->anzahl_personen != $data['anzahl_personen'])) {
                    // Alte Slots löschen
                    $table_slots = $wpdb->prefix . $this->db_prefix . 'dienst_slots';
                    $wpdb->delete($table_slots, array('dienst_id' => $dienst_id));
                    
                    // Neue Slots erstellen
                    $slots_data = array(
                        'von_zeit' => $data['von_zeit'],
                        'bis_zeit' => $data['bis_zeit'],
                        'bis_datum' => $data['bis_datum'],
                        'anzahl_personen' => $data['anzahl_personen'],
                        'splittbar' => $data['splittbar']
                    );
                    $this->create_dienst_slots_for_copy($dienst_id, $slots_data);
                }
            } else {
                // Neu anlegen
                $result = $db->add_dienst($data);
                $message = 'Dienst erstellt';
                $return_id = $result;
            }
            
            // Prüfe ob Fehler von Validierung zurückkam
            if (is_array($result) && isset($result['error'])) {
                wp_send_json_error(array('message' => $result['message']));
                return;
            }
            
            if ($result === false) {
                wp_send_json_error(array('message' => 'Fehler beim Speichern in der Datenbank'));
                return;
            }
            
            wp_send_json_success(array(
                'message' => $message,
                'dienst_id' => $return_id
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Fehler: ' . $e->getMessage()));
        }
    }
    
    public function ajax_get_dienst() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $dienst_id = isset($_POST['dienst_id']) ? intval($_POST['dienst_id']) : 0;
        
        if ($dienst_id <= 0) {
            wp_send_json_error(array('message' => 'Keine Dienst-ID angegeben'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $dienst = $db->get_dienst($dienst_id);
        
        error_log('ADMIN ajax_get_dienst() - Dienst-ID: ' . $dienst_id);
        error_log('ADMIN ajax_get_dienst() - Dienst geladen: ' . print_r($dienst, true));
        
        if (!$dienst) {
            wp_send_json_error(array('message' => 'Dienst nicht gefunden'));
            return;
        }
        
        // Lade auch Tage und Tätigkeiten mit, um weitere AJAX-Calls zu vermeiden
        $tage = $db->get_veranstaltung_tage($dienst->veranstaltung_id);
        $taetigkeiten = $db->get_taetigkeiten_by_bereich($dienst->bereich_id, false);
        
        wp_send_json_success(array(
            'dienst' => $dienst,
            'tage' => $tage,
            'taetigkeiten' => $taetigkeiten
        ));
    }
    
    public function ajax_delete_dienst() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung zum Löschen von Diensten'));
            return;
        }
        
        $dienst_id = isset($_POST['dienst_id']) ? intval($_POST['dienst_id']) : 0;
        
        if ($dienst_id <= 0) {
            wp_send_json_error(array('message' => 'Keine Dienst-ID angegeben'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $result = $db->delete_dienst($dienst_id);
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Fehler beim Löschen des Dienstes'));
            return;
        }
        
        wp_send_json_success(array('message' => 'Dienst gelöscht'));
    }
    
    public function ajax_copy_dienst() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung zum Kopieren von Diensten'));
            return;
        }
        
        $dienst_id = isset($_POST['dienst_id']) ? intval($_POST['dienst_id']) : 0;
        $copy_count = isset($_POST['copy_count']) ? intval($_POST['copy_count']) : 1;
        
        if ($dienst_id <= 0) {
            wp_send_json_error(array('message' => 'Keine Dienst-ID angegeben'));
            return;
        }
        
        if ($copy_count < 1 || $copy_count > 50) {
            wp_send_json_error(array('message' => 'Anzahl der Kopien muss zwischen 1 und 50 liegen'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        // Lade Original-Dienst
        global $wpdb;
        $table_dienste = $wpdb->prefix . $this->db_prefix . 'dienste';
        $original = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_dienste} WHERE id = %d", $dienst_id), ARRAY_A);
        
        if (!$original) {
            wp_send_json_error(array('message' => 'Dienst nicht gefunden'));
            return;
        }
        
        // Entferne ID für Kopien
        unset($original['id']);
        
        $created_count = 0;
        for ($i = 0; $i < $copy_count; $i++) {
            $result = $wpdb->insert($table_dienste, $original);
            
            if ($result !== false) {
                $new_dienst_id = $wpdb->insert_id;
                
                // Erstelle automatisch Slots für den kopierten Dienst
                // create_dienst_slots wird intern bei add_dienst aufgerufen, 
                // daher müssen wir die Slots manuell erstellen
                $slots_data = array(
                    'von_zeit' => $original['von_zeit'],
                    'bis_zeit' => $original['bis_zeit'],
                    'bis_datum' => $original['bis_datum'],
                    'anzahl_personen' => $original['anzahl_personen'],
                    'splittbar' => $original['splittbar']
                );
                
                $this->create_dienst_slots_for_copy($new_dienst_id, $slots_data);
                
                $created_count++;
            }
        }
        
        if ($created_count === 0) {
            wp_send_json_error(array('message' => 'Fehler beim Kopieren der Dienste'));
            return;
        }
        
        wp_send_json_success(array(
            'message' => $created_count . ' Dienst(e) erfolgreich kopiert',
            'count' => $created_count
        ));
    }
    
    public function ajax_create_bereich() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $farbe = isset($_POST['farbe']) ? sanitize_hex_color($_POST['farbe']) : '#3b82f6';
        
        if (empty($name)) {
            wp_send_json_error(array('message' => 'Name ist erforderlich'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $data = array(
            'name' => $name,
            'farbe' => $farbe,
            'aktiv' => 1
        );
        
        $bereich_id = $db->add_bereich($data);
        
        if ($bereich_id) {
            wp_send_json_success(array(
                'bereich_id' => $bereich_id,
                'name' => $name,
                'farbe' => $farbe
            ));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Erstellen des Bereichs'));
        }
    }
    
    public function ajax_create_taetigkeit() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $bereich_id = isset($_POST['bereich_id']) ? intval($_POST['bereich_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        
        if (empty($name) || $bereich_id <= 0) {
            wp_send_json_error(array('message' => 'Name und Bereich sind erforderlich'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $data = array(
            'bereich_id' => $bereich_id,
            'name' => $name,
            'aktiv' => 1
        );
        
        $taetigkeit_id = $db->add_taetigkeit($data);
        
        if ($taetigkeit_id) {
            wp_send_json_success(array(
                'taetigkeit_id' => $taetigkeit_id,
                'bereich_id' => $bereich_id,
                'name' => $name
            ));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Erstellen der Tätigkeit'));
        }
    }
    
    public function ajax_create_verein() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events() && !Dienstplan_Roles::can_manage_clubs()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $kuerzel = isset($_POST['kuerzel']) ? sanitize_text_field($_POST['kuerzel']) : '';
        
        if (empty($name) || empty($kuerzel)) {
            wp_send_json_error(array('message' => 'Name und Kürzel sind erforderlich'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $data = array(
            'name' => $name,
            'kuerzel' => strtoupper($kuerzel),
            'aktiv' => 1
        );
        
        $verein_id = $db->add_verein($data);
        
        if ($verein_id) {
            wp_send_json_success(array(
                'verein_id' => $verein_id,
                'name' => $name,
                'kuerzel' => strtoupper($kuerzel)
            ));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Erstellen des Vereins'));
        }
    }
    
    public function ajax_get_taetigkeiten_by_bereich() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $bereich_id = isset($_POST['bereich_id']) ? intval($_POST['bereich_id']) : 0;
        
        if ($bereich_id <= 0) {
            wp_send_json_error(array('message' => 'Keine Bereich-ID angegeben'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $taetigkeiten = $db->get_taetigkeiten_by_bereich($bereich_id, true);
        wp_send_json_success($taetigkeiten);
    }
    
    public function ajax_delete_verein() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $verein_id = intval($_POST['verein_id']);
        $result = $db->delete_verein($verein_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Verein gelöscht'));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Löschen'));
        }
    }
    
    public function ajax_get_verein() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $verein_id = intval($_POST['verein_id']);
        $verein = $db->get_verein($verein_id);
        
        if ($verein) {
            // Verantwortliche laden
            $verantwortliche_rows = $db->get_verein_verantwortliche($verein_id);
            $verein->verantwortliche = array();
            foreach ($verantwortliche_rows as $row) {
                $verein->verantwortliche[] = $row->user_id;
            }
            
            // Logo URL hinzufügen
            if (!empty($verein->logo_id)) {
                $verein->logo_url = wp_get_attachment_url($verein->logo_id);
            }
            
            wp_send_json_success($verein);
        } else {
            wp_send_json_error(array('message' => 'Verein nicht gefunden'));
        }
    }
    
    // === VERANSTALTUNGEN AJAX HANDLERS ===
    
    public function ajax_save_veranstaltung() {
        try {
            error_log('=== SAVE VERANSTALTUNG START ===');
            error_log('POST data: ' . print_r($_POST, true));
            
            check_ajax_referer('dp_ajax_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                error_log('Keine Berechtigung');
                wp_send_json_error(array('message' => 'Keine Berechtigung'));
            }
            
            require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
            $db = new Dienstplan_Database($this->db_prefix);
            
            // Validierung
            if (empty($_POST['titel'])) {
                wp_send_json_error(array('message' => 'Titel ist erforderlich'));
            }
            
            // Veranstaltung Daten vorbereiten (Feldnamen an Datenbank angepasst)
            $veranstaltung_data = array(
                'name' => sanitize_text_field($_POST['titel']), // titel -> name
                'beschreibung' => sanitize_textarea_field($_POST['beschreibung'] ?? ''),
                'typ' => 'mehrtaegig', // Standardwert
                'status' => sanitize_text_field($_POST['status'] ?? 'geplant'),
                'start_datum' => null, // Wird aus erstem Tag gesetzt
                'end_datum' => null // Wird aus letztem Tag gesetzt
            );
            
            // Start- und End-Datum aus Tagen extrahieren
            if (isset($_POST['tage']) && !empty($_POST['tage'])) {
                $tage = json_decode(stripslashes($_POST['tage']), true);
                if (is_array($tage) && count($tage) > 0) {
                    $datums = array_column($tage, 'datum');
                    $datums = array_filter($datums); // Leere Werte entfernen
                    if (!empty($datums)) {
                        $veranstaltung_data['start_datum'] = min($datums);
                        $veranstaltung_data['end_datum'] = max($datums);
                        $veranstaltung_data['typ'] = count($tage) > 1 ? 'mehrtaegig' : 'eintaegig';
                    }
                }
            }
            
            // Wenn kein Datum vorhanden, Fehler zurückgeben
            if (empty($veranstaltung_data['start_datum'])) {
                wp_send_json_error(array('message' => 'Mindestens ein Tag mit Datum ist erforderlich'));
                return;
            }
            
            // Speichern oder Aktualisieren
            if (isset($_POST['veranstaltung_id']) && !empty($_POST['veranstaltung_id'])) {
                $veranstaltung_id = intval($_POST['veranstaltung_id']);
                $update_result = $db->update_veranstaltung($veranstaltung_id, $veranstaltung_data);
                
                if ($update_result === false) {
                    wp_send_json_error(array('message' => 'Fehler: Eine Veranstaltung mit diesem Namen existiert bereits am gleichen Datum'));
                    return;
                }
                
                $result = true;
                $message = 'Veranstaltung aktualisiert';
                
                // Alte Vereine löschen (Tage werden jetzt differenziell aktualisiert)
                $db->delete_veranstaltung_vereine($veranstaltung_id);
                // Existierende Tage laden für Mapping (Erhalt der IDs für Dienste)
                $existing_tags = $db->get_veranstaltung_tage($veranstaltung_id);
                $existing_count = count($existing_tags);
                error_log('Existierende Tags vor Update: ' . $existing_count);
            } else {
                $veranstaltung_id = $db->add_veranstaltung($veranstaltung_data);
                
                if ($veranstaltung_id === false) {
                    wp_send_json_error(array('message' => 'Fehler: Eine Veranstaltung mit diesem Namen existiert bereits am gleichen Datum'));
                    return;
                }
                
                $result = true;
                $message = 'Veranstaltung erstellt';
            }
            
            // Tage speichern / aktualisieren
            if ($result && isset($_POST['tage']) && !empty($_POST['tage'])) {
                $tage = json_decode(stripslashes($_POST['tage']), true);
                if (is_array($tage)) {
                    foreach ($tage as $index => $tag) {
                        $tag_datum = sanitize_text_field($tag['datum'] ?? '');
                        $von_zeit = sanitize_text_field($tag['von_zeit'] ?? '');
                        $bis_zeit = sanitize_text_field($tag['bis_zeit'] ?? '');
                        $dienst_von = sanitize_text_field($tag['dienst_von'] ?? '');
                        $dienst_bis = sanitize_text_field($tag['dienst_bis'] ?? '');
                        $nur_dienst = isset($tag['nur_dienst']) && $tag['nur_dienst'] == '1' ? 1 : 0;
                        if ($nur_dienst) { $von_zeit = ''; $bis_zeit = ''; }
                        $bis_datum = $tag_datum;
                        $dienst_bis_datum = $tag_datum;
                        if (!empty($von_zeit) && !empty($bis_zeit) && $bis_zeit < $von_zeit) {
                            $bis_datum = date('Y-m-d', strtotime($tag_datum . ' +1 day')); }
                        if (!empty($dienst_von) && !empty($dienst_bis) && $dienst_bis < $dienst_von) {
                            $dienst_bis_datum = date('Y-m-d', strtotime($tag_datum . ' +1 day')); }
                        $tag_data = array(
                            'veranstaltung_id' => $veranstaltung_id,
                            'tag_datum' => $tag_datum,
                            'tag_nummer' => $index + 1,
                            'von_zeit' => $von_zeit,
                            'bis_zeit' => $bis_zeit,
                            'bis_datum' => $bis_datum,
                            'dienst_von_zeit' => $dienst_von,
                            'dienst_bis_zeit' => $dienst_bis,
                            'dienst_bis_datum' => $dienst_bis_datum,
                            'nur_dienst' => $nur_dienst,
                            'notizen' => sanitize_textarea_field($tag['notizen'] ?? '')
                        );
                        // Entscheide Update vs Insert
                        if (isset($existing_tags) && $index < $existing_count) {
                            $existing_tag_id = $existing_tags[$index]->id;
                            error_log('Tag aktualisieren ID ' . $existing_tag_id . ' Daten: ' . print_r($tag_data, true));
                            $db->update_veranstaltung_tag($existing_tag_id, $tag_data);
                        } else {
                            error_log('Neuen Tag anlegen: ' . print_r($tag_data, true));
                            $db->add_veranstaltung_tag($tag_data);
                        }
                    }
                    // Überzählige alte Tags löschen falls Anzahl reduziert
                    if (isset($existing_tags) && $existing_count > count($tage)) {
                        for ($i = count($tage); $i < $existing_count; $i++) {
                            $old_id = $existing_tags[$i]->id;
                            error_log('Lösche alten Tag ID ' . $old_id . ' (Anzahl reduziert)');
                            $db->get_wpdb()->delete($db->get_prefix() . 'veranstaltung_tage', array('id' => $old_id), array('%d'));
                            // Dienste behalten ihre alte tag_id -> führt zu "ohne Tag". Optional könnten wir hier zu letztem Tag mappen.
                        }
                    }
                }
            }
            
            // Vereine speichern
            if ($result && isset($_POST['vereine']) && !empty($_POST['vereine'])) {
                error_log('Vereine JSON: ' . $_POST['vereine']);
                $vereine = json_decode(stripslashes($_POST['vereine']), true);
                error_log('Vereine decoded: ' . print_r($vereine, true));
                
                if (is_array($vereine)) {
                    foreach ($vereine as $verein_id) {
                        error_log('Verein verknüpfen: ' . $verein_id);
                        $db->add_veranstaltung_verein($veranstaltung_id, intval($verein_id));
                    }
                }
            }
            
            // Verantwortliche speichern
            if ($result) {
                $verantwortliche = isset($_POST['verantwortliche']) && is_array($_POST['verantwortliche']) 
                    ? array_map('intval', $_POST['verantwortliche']) 
                    : array();
                $db->save_veranstaltung_verantwortliche($veranstaltung_id, $verantwortliche);
                error_log('Verantwortliche gespeichert: ' . print_r($verantwortliche, true));
            }
            
            // WordPress-Seite automatisch erstellen (nur wenn noch keine existiert)
            if ($result && $veranstaltung_id) {
                $veranstaltung = $db->get_veranstaltung($veranstaltung_id);
                
                // Nur erstellen wenn noch keine seite_id vorhanden
                if (empty($veranstaltung->seite_id)) {
                    error_log('Erstelle WordPress-Seite für Veranstaltung ID: ' . $veranstaltung_id);
                    
                    $page_id = $db->create_veranstaltung_page($veranstaltung_id, (array)$veranstaltung);
                    
                    if ($page_id) {
                        $db->update_veranstaltung_page_id($veranstaltung_id, $page_id);
                        error_log('WordPress-Seite erstellt. Page ID: ' . $page_id);
                    } else {
                        error_log('Warnung: WordPress-Seite konnte nicht erstellt werden');
                    }
                } else {
                    error_log('WordPress-Seite existiert bereits. Page ID: ' . $veranstaltung->seite_id);
                }
            }
            
            if ($result) {
                error_log('Veranstaltung erfolgreich gespeichert. ID: ' . $veranstaltung_id);
                wp_send_json_success(array('message' => $message));
            } else {
                error_log('Fehler beim Speichern: ' . $message);
                wp_send_json_error(array('message' => $message));
            }
            
        } catch (Exception $e) {
            error_log('EXCEPTION: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => 'Fehler: ' . $e->getMessage()));
        }
    }
    
    public function ajax_get_veranstaltung() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $veranstaltung_id = intval($_POST['veranstaltung_id']);
        $veranstaltung = $db->get_veranstaltung($veranstaltung_id);
        
        error_log('AJAX get_veranstaltung - ID: ' . $veranstaltung_id);
        error_log('AJAX get_veranstaltung - Raw: ' . print_r($veranstaltung, true));
        
        if ($veranstaltung) {
            // In Array konvertieren
            $veranstaltung_data = (array) $veranstaltung;
            
            // Tage laden
            $tage = $db->get_veranstaltung_tage($veranstaltung_id);
            $veranstaltung_data['tage'] = array_map(function($tag) {
                return (array) $tag;
            }, $tage);
            
            // Vereine laden
            $vereine_rows = $db->get_veranstaltung_vereine($veranstaltung_id);
            $veranstaltung_data['vereine'] = array_map(function($row) {
                return intval($row->verein_id);
            }, $vereine_rows);
            
            // Verantwortliche laden
            $verantwortliche_rows = $db->get_veranstaltung_verantwortliche($veranstaltung_id);
            $veranstaltung_data['verantwortliche'] = array();
            foreach ($verantwortliche_rows as $row) {
                $veranstaltung_data['verantwortliche'][] = $row->user_id;
            }
            
            error_log('Sende Veranstaltung: ' . print_r($veranstaltung_data, true));
            wp_send_json_success($veranstaltung_data);
        } else {
            wp_send_json_error(array('message' => 'Veranstaltung nicht gefunden'));
        }
    }
    
    public function ajax_delete_veranstaltung() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $veranstaltung_id = intval($_POST['veranstaltung_id']);
        $delete_dienste = isset($_POST['delete_dienste']) ? (bool) $_POST['delete_dienste'] : false;
        
        // Zähle Dienste für diese Veranstaltung
        $dienste_count = $db->count_dienste_by_veranstaltung($veranstaltung_id);
        
        // Wenn Dienste existieren und nicht explizit gelöscht werden sollen, Fehler zurückgeben
        if ($dienste_count > 0 && !$delete_dienste) {
            wp_send_json_error(array(
                'message' => 'confirm_delete_dienste',
                'dienste_count' => $dienste_count
            ));
            return;
        }
        
        // Abrufen der seite_id vor dem Löschen
        $veranstaltung = $db->get_veranstaltung($veranstaltung_id);
        $seite_id = $veranstaltung ? intval($veranstaltung->seite_id) : 0;
        
        // Dienste löschen wenn gewünscht
        if ($delete_dienste && $dienste_count > 0) {
            $db->delete_dienste_by_veranstaltung($veranstaltung_id);
        }
        
        // Verknüpfte Daten löschen
        $db->delete_veranstaltung_tage($veranstaltung_id);
        $db->delete_veranstaltung_vereine($veranstaltung_id);
        $db->delete_veranstaltung_verantwortliche($veranstaltung_id);
        
        // Veranstaltung löschen
        $result = $db->delete_veranstaltung($veranstaltung_id);
        
        // WordPress-Seite löschen wenn vorhanden
        if ($result && $seite_id > 0) {
            wp_delete_post($seite_id, true); // true = sofort löschen, nicht in den Papierkorb
        }
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Veranstaltung gelöscht',
                'dienste_deleted' => $delete_dienste ? $dienste_count : 0
            ));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Löschen'));
        }
    }
    
    /**
     * WordPress-Benutzer erstellen und einladen
     */
    private function create_dienst_slots_for_copy($dienst_id, $dienst_data) {
        global $wpdb;
        $table_slots = $wpdb->prefix . $this->db_prefix . 'dienst_slots';
        
        $von_zeit = $dienst_data['von_zeit'];
        $bis_zeit = $dienst_data['bis_zeit'];
        $bis_datum = $dienst_data['bis_datum'] ?? null;
        $anzahl_personen = isset($dienst_data['anzahl_personen']) ? intval($dienst_data['anzahl_personen']) : 1;
        $splittbar = !empty($dienst_data['splittbar']) && $dienst_data['splittbar'] == 1;
        
        if ($splittbar) {
            // Erstelle 2 Slots für halbe Dienste
            $von_timestamp = strtotime($von_zeit);
            $bis_timestamp = strtotime($bis_zeit);
            $mitte_timestamp = $von_timestamp + (($bis_timestamp - $von_timestamp) / 2);
            $mitte_zeit = date('H:i:s', $mitte_timestamp);
            
            // Slot 1 (erste Hälfte)
            $wpdb->insert(
                $table_slots,
                array(
                    'dienst_id' => $dienst_id,
                    'slot_nummer' => 1,
                    'von_zeit' => $von_zeit,
                    'bis_zeit' => $mitte_zeit,
                    'bis_datum' => null,
                    'status' => 'offen'
                )
            );
            
            // Slot 2 (zweite Hälfte)
            $wpdb->insert(
                $table_slots,
                array(
                    'dienst_id' => $dienst_id,
                    'slot_nummer' => 2,
                    'von_zeit' => $mitte_zeit,
                    'bis_zeit' => $bis_zeit,
                    'bis_datum' => $bis_datum,
                    'status' => 'offen'
                )
            );
        } else {
            // Erstelle anzahl_personen Slots für ganze Dienste
            for ($i = 1; $i <= $anzahl_personen; $i++) {
                $wpdb->insert(
                    $table_slots,
                    array(
                        'dienst_id' => $dienst_id,
                        'slot_nummer' => $i,
                        'von_zeit' => $von_zeit,
                        'bis_zeit' => $bis_zeit,
                        'bis_datum' => $bis_datum,
                        'status' => 'offen'
                    )
                );
            }
        }
    }
    
    private function create_wordpress_user($email, $name = '', $role = '') {
        // Prüfe ob Benutzer bereits existiert
        if (get_user_by('email', $email)) {
            return false;
        }
        
        // Benutzername aus E-Mail generieren
        $username = sanitize_user(substr($email, 0, strpos($email, '@')));
        
        // Prüfe ob Username existiert, füge Nummer hinzu falls nötig
        $base_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }
        
        // Zufälliges Passwort generieren
        $password = wp_generate_password(12, true, true);
        
        // Benutzer erstellen
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            error_log('Fehler beim Erstellen des Benutzers: ' . $user_id->get_error_message());
            return false;
        }
        
        // Display Name setzen
        if (!empty($name)) {
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $name,
                'first_name' => $name
            ));
        }
        
        // Dienstplan-Rolle zuweisen
        if (!empty($role)) {
            $user = get_user_by('id', $user_id);
            if ($user) {
                $user->add_role($role);
            }
        }
        
        // Passwort-Reset-Link senden
        $this->send_user_invitation($user_id, $email, $name);
        
        return $user_id;
    }
    
    /**
     * Einladungs-E-Mail senden
     */
    private function send_user_invitation($user_id, $email, $name) {
        $user = get_user_by('id', $user_id);
        if (!$user) return false;
        
        // Passwort-Reset-Key generieren
        $key = get_password_reset_key($user);
        
        if (is_wp_error($key)) {
            return false;
        }
        
        $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login');
        
        $site_name = get_option('dp_site_name', get_bloginfo('name'));
        $subject = sprintf(__('[%s] Einladung zum Dienstplan-System', 'dienstplan-verwaltung'), $site_name);
        
        $message = sprintf(
            __("Hallo %s,\n\ndu wurdest zum Dienstplan-System von %s eingeladen.\n\nDein Benutzername: %s\n\nBitte klicke auf den folgenden Link um dein Passwort zu setzen:\n%s\n\nNach dem Setzen des Passworts kannst du dich hier anmelden:\n%s\n\nViele Grüße\nDein Dienstplan-Team", 'dienstplan-verwaltung'),
            !empty($name) ? $name : $user->user_login,
            $site_name,
            $user->user_login,
            $reset_url,
            wp_login_url()
        );
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        return wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Alle WordPress-Benutzer für Dropdown abrufen
     */
    public function get_all_wordpress_users() {
        $users = get_users(array(
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));
        
        $user_list = array();
        foreach ($users as $user) {
            $user_list[] = array(
                'id' => $user->ID,
                'name' => $user->display_name ? $user->display_name : $user->user_login,
                'email' => $user->user_email,
                'login' => $user->user_login
            );
        }
        
        return $user_list;
    }
    
    /**
     * AJAX: WordPress-Seite für Veranstaltung manuell erstellen
     */
    public function ajax_create_event_page() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
        }
        
        $veranstaltung_id = isset($_POST['veranstaltung_id']) ? intval($_POST['veranstaltung_id']) : 0;
        
        if (!$veranstaltung_id) {
            wp_send_json_error(array('message' => 'Veranstaltungs-ID fehlt'));
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $veranstaltung = $db->get_veranstaltung($veranstaltung_id);
        
        if (!$veranstaltung) {
            wp_send_json_error(array('message' => 'Veranstaltung nicht gefunden'));
        }
        
        // Prüfe ob bereits eine gültige Seite existiert
        if (!empty($veranstaltung->seite_id)) {
            $page = get_post($veranstaltung->seite_id);
            if ($page && $page->post_status !== 'trash') {
                wp_send_json_error(array('message' => 'Es existiert bereits eine Seite für diese Veranstaltung'));
            }
            // Seite existiert nicht mehr oder ist im Papierkorb -> seite_id zurücksetzen
            $db->update_veranstaltung_page_id($veranstaltung_id, null);
        }
        
        // Seite erstellen
        $page_id = $db->create_veranstaltung_page($veranstaltung_id, (array)$veranstaltung);
        
        if ($page_id) {
            $db->update_veranstaltung_page_id($veranstaltung_id, $page_id);
            wp_send_json_success(array(
                'message' => 'Seite erfolgreich erstellt',
                'page_id' => $page_id,
                'page_url' => get_permalink($page_id)
            ));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Erstellen der Seite'));
        }
    }
    
    public function ajax_update_event_page() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
        }
        
        $veranstaltung_id = isset($_POST['veranstaltung_id']) ? intval($_POST['veranstaltung_id']) : 0;
        
        if (!$veranstaltung_id) {
            wp_send_json_error(array('message' => 'Veranstaltungs-ID fehlt'));
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $veranstaltung = $db->get_veranstaltung($veranstaltung_id);
        
        if (!$veranstaltung) {
            wp_send_json_error(array('message' => 'Veranstaltung nicht gefunden'));
        }
        
        if (empty($veranstaltung->seite_id)) {
            wp_send_json_error(array('message' => 'Diese Veranstaltung hat keine zugeordnete Seite'));
        }
        
        $page = get_post($veranstaltung->seite_id);
        if (!$page || $page->post_status === 'trash') {
            wp_send_json_error(array('message' => 'Die zugeordnete Seite existiert nicht mehr'));
        }
        
        // Aktualisiere Seiten-Inhalt auf neues Format
        $new_content = sprintf('[dienstplan veranstaltung_id="%d"]', $veranstaltung_id);
        $new_title = $veranstaltung->name;
        
        $result = wp_update_post(array(
            'ID' => $veranstaltung->seite_id,
            'post_content' => $new_content,
            'post_title' => $new_title
        ));
        
        if ($result && !is_wp_error($result)) {
            wp_send_json_success(array(
                'message' => 'Seite erfolgreich aktualisiert',
                'page_id' => $veranstaltung->seite_id,
                'page_url' => get_permalink($veranstaltung->seite_id)
            ));
        } else {
            $error_message = is_wp_error($result) ? $result->get_error_message() : 'Unbekannter Fehler';
            wp_send_json_error(array('message' => 'Fehler beim Aktualisieren der Seite: ' . $error_message));
        }
    }
    
    /**
     * AJAX: Dienst Besetzung laden (Dienst-Info + Slots + Mitarbeiter)
     */
    public function ajax_get_dienst_besetzung() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $dienst_id = isset($_POST['dienst_id']) ? intval($_POST['dienst_id']) : 0;
        
        if ($dienst_id <= 0) {
            wp_send_json_error(array('message' => 'Keine Dienst-ID angegeben'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        // Dienst mit allen Details laden
        $dienst = $db->get_dienst_with_details($dienst_id);
        
        if (!$dienst) {
            wp_send_json_error(array('message' => 'Dienst nicht gefunden'));
            return;
        }
        
        // Slots für diesen Dienst laden
        $slots = $db->get_dienst_slots($dienst_id);
        
        // Alle Mitarbeiter für Auswahlliste
        global $wpdb;
        $mitarbeiter = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dp_mitarbeiter ORDER BY vorname, nachname");
        
        wp_send_json_success(array(
            'dienst' => $dienst,
            'slots' => $slots,
            'mitarbeiter' => $mitarbeiter
        ));
    }
    
    /**
     * AJAX: Slot einem Mitarbeiter zuweisen (Backend)
     */
    public function ajax_admin_assign_slot() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $slot_id = isset($_POST['slot_id']) ? intval($_POST['slot_id']) : 0;
        $mitarbeiter_id = isset($_POST['mitarbeiter_id']) ? intval($_POST['mitarbeiter_id']) : 0;
        $force_replace = isset($_POST['force_replace']) ? intval($_POST['force_replace']) : 0;
        
        if ($slot_id <= 0 || $mitarbeiter_id <= 0) {
            wp_send_json_error(array('message' => 'Slot-ID oder Mitarbeiter-ID fehlt'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        // Prüfe ob Slot existiert
        global $wpdb;
        $slot = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dp_dienst_slots WHERE id = %d",
            $slot_id
        ));
        
        if (!$slot) {
            wp_send_json_error(array('message' => 'Slot nicht gefunden'));
            return;
        }
        
        // Prüfe ob Slot schon besetzt ist (nur wenn nicht force_replace)
        if (!$force_replace && $slot->mitarbeiter_id && $slot->mitarbeiter_id > 0) {
            wp_send_json_error(array('message' => 'Slot ist bereits besetzt'));
            return;
        }
        
        // Zuweisung durchführen (oder ersetzen)
        $result = $wpdb->update(
            $wpdb->prefix . 'dp_dienst_slots',
            array(
                'mitarbeiter_id' => $mitarbeiter_id,
                'status' => 'besetzt'
            ),
            array('id' => $slot_id),
            array('%d', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            error_log("=== ADMIN ASSIGN SLOT SUCCESS ===");
            error_log("Slot ID: $slot_id -> Mitarbeiter ID: $mitarbeiter_id");
            wp_send_json_success(array(
                'message' => 'Slot erfolgreich zugewiesen',
                'slot_id' => $slot_id,
                'mitarbeiter_id' => $mitarbeiter_id
            ));
        } else {
            error_log("=== ADMIN ASSIGN SLOT ERROR ===");
            error_log("WPDB Error: " . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Fehler beim Zuweisen: ' . $wpdb->last_error));
        }
    }
    
    /**
     * AJAX: Slot-Zuweisung entfernen (Backend)
     */
    public function ajax_admin_remove_slot() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $slot_id = isset($_POST['slot_id']) ? intval($_POST['slot_id']) : 0;
        
        if ($slot_id <= 0) {
            wp_send_json_error(array('message' => 'Slot-ID fehlt'));
            return;
        }
        
        global $wpdb;
        
        // Zuweisung entfernen
        $result = $wpdb->update(
            $wpdb->prefix . 'dp_dienst_slots',
            array(
                'mitarbeiter_id' => null,
                'status' => 'frei'
            ),
            array('id' => $slot_id),
            array('%d', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            error_log("=== ADMIN REMOVE SLOT SUCCESS ===");
            error_log("Slot ID: $slot_id");
            wp_send_json_success(array(
                'message' => 'Zuweisung erfolgreich entfernt',
                'slot_id' => $slot_id
            ));
        } else {
            error_log("=== ADMIN REMOVE SLOT ERROR ===");
            error_log("WPDB Error: " . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Fehler beim Entfernen: ' . $wpdb->last_error));
        }
    }
    
    /**
     * AJAX: Mitarbeiter laden (für Bearbeitung)
     */
    public function ajax_get_mitarbeiter() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $mitarbeiter_id = isset($_POST['mitarbeiter_id']) ? intval($_POST['mitarbeiter_id']) : 0;
        
        if ($mitarbeiter_id <= 0) {
            wp_send_json_error(array('message' => 'Keine Mitarbeiter-ID angegeben'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $mitarbeiter = $db->get_mitarbeiter($mitarbeiter_id);
        
        if (!$mitarbeiter) {
            wp_send_json_error(array('message' => 'Mitarbeiter nicht gefunden'));
            return;
        }
        
        wp_send_json_success($mitarbeiter);
    }
    
    /**
     * AJAX: Mitarbeiter speichern (Neu/Bearbeiten)
     */
    public function ajax_save_mitarbeiter() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $mitarbeiter_id = isset($_POST['mitarbeiter_id']) ? intval($_POST['mitarbeiter_id']) : 0;
        $vorname = isset($_POST['vorname']) ? sanitize_text_field($_POST['vorname']) : '';
        $nachname = isset($_POST['nachname']) ? sanitize_text_field($_POST['nachname']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $telefon = isset($_POST['telefon']) ? sanitize_text_field($_POST['telefon']) : '';
        
        // Nur Vorname und Nachname sind Pflichtfelder
        if (empty($vorname) || empty($nachname)) {
            wp_send_json_error(array('message' => 'Vorname und Nachname sind Pflichtfelder'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $data = array(
            'vorname' => $vorname,
            'nachname' => $nachname,
            'email' => !empty($email) ? $email : null,
            'telefon' => !empty($telefon) ? $telefon : null
        );
        
        if ($mitarbeiter_id > 0) {
            // Update
            $result = $db->update_mitarbeiter($mitarbeiter_id, $data);
            $message = 'Mitarbeiter erfolgreich aktualisiert';
            $return_id = $mitarbeiter_id;
        } else {
            // Neu erstellen
            $result = $db->add_mitarbeiter($data);
            $message = 'Mitarbeiter erfolgreich erstellt';
            $return_id = $result;
        }
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => $message,
                'mitarbeiter_id' => $return_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Speichern des Mitarbeiters'));
        }
    }
    
    /**
     * AJAX: Mitarbeiter löschen
     */
    public function ajax_delete_mitarbeiter() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $mitarbeiter_id = isset($_POST['mitarbeiter_id']) ? intval($_POST['mitarbeiter_id']) : 0;
        
        if ($mitarbeiter_id <= 0) {
            wp_send_json_error(array('message' => 'Keine Mitarbeiter-ID angegeben'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $result = $db->delete_mitarbeiter($mitarbeiter_id);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Mitarbeiter erfolgreich gelöscht'));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Löschen des Mitarbeiters'));
        }
    }
    
    /**
     * AJAX: Dienste eines Mitarbeiters laden
     */
    public function ajax_get_mitarbeiter_dienste() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $mitarbeiter_id = isset($_POST['mitarbeiter_id']) ? intval($_POST['mitarbeiter_id']) : 0;
        
        if ($mitarbeiter_id <= 0) {
            wp_send_json_error(array('message' => 'Keine Mitarbeiter-ID angegeben'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $mitarbeiter = $db->get_mitarbeiter($mitarbeiter_id);
        
        if (!$mitarbeiter) {
            wp_send_json_error(array('message' => 'Mitarbeiter nicht gefunden'));
            return;
        }
        
        // Dienste des Mitarbeiters laden
        global $wpdb;
        $dienste = $wpdb->get_results($wpdb->prepare(
            "SELECT s.id as slot_id, s.slot_nummer, s.status as slot_status,
                    s.von_zeit, s.bis_zeit,
                    d.id as dienst_id,
                    t.name as taetigkeit_name,
                    b.name as bereich_name, b.farbe as bereich_farbe,
                    v.name as verein_name,
                    ve.name as veranstaltung_name,
                    vt.tag_nummer, vt.tag_datum
             FROM {$wpdb->prefix}dp_dienst_slots s
             INNER JOIN {$wpdb->prefix}dp_dienste d ON s.dienst_id = d.id
             LEFT JOIN {$wpdb->prefix}dp_taetigkeiten t ON d.taetigkeit_id = t.id
             LEFT JOIN {$wpdb->prefix}dp_bereiche b ON d.bereich_id = b.id
             LEFT JOIN {$wpdb->prefix}dp_vereine v ON d.verein_id = v.id
             LEFT JOIN {$wpdb->prefix}dp_veranstaltungen ve ON d.veranstaltung_id = ve.id
             LEFT JOIN {$wpdb->prefix}dp_veranstaltung_tage vt ON d.tag_id = vt.id
             WHERE s.mitarbeiter_id = %d
             ORDER BY vt.tag_datum ASC, s.von_zeit ASC",
            $mitarbeiter_id
        ));
        
        wp_send_json_success(array(
            'mitarbeiter' => $mitarbeiter,
            'dienste' => $dienste
        ));
    }
    
    /**
     * AJAX: Mehrere Mitarbeiter gleichzeitig löschen
     */
    public function ajax_delete_mitarbeiter_bulk() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $mitarbeiter_ids = isset($_POST['mitarbeiter_ids']) ? $_POST['mitarbeiter_ids'] : array();
        
        if (empty($mitarbeiter_ids) || !is_array($mitarbeiter_ids)) {
            wp_send_json_error(array('message' => 'Keine Mitarbeiter-IDs angegeben'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $deleted_count = 0;
        $failed_count = 0;
        
        foreach ($mitarbeiter_ids as $id) {
            $id = intval($id);
            if ($id <= 0) continue;
            
            $result = $db->delete_mitarbeiter($id);
            if ($result !== false) {
                $deleted_count++;
            } else {
                $failed_count++;
            }
        }
        
        if ($deleted_count > 0) {
            $message = sprintf(
                _n('%d Mitarbeiter gelöscht', '%d Mitarbeiter gelöscht', $deleted_count, 'dienstplan-verwaltung'),
                $deleted_count
            );
            
            if ($failed_count > 0) {
                $message .= sprintf(' (%d fehlgeschlagen)', $failed_count);
            }
            
            wp_send_json_success(array('message' => $message, 'deleted' => $deleted_count, 'failed' => $failed_count));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Löschen der Mitarbeiter'));
        }
    }
    
    /**
     * Export CSV Handler (nicht-AJAX für direkten Download)
     */
    public function handle_export() {
        // Prüfe ob Export-Request
        if (!isset($_GET['action']) || $_GET['action'] !== 'dp_export_csv') {
            return;
        }
        
        // Nonce-Prüfung
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'dp_ajax_nonce')) {
            wp_die('Sicherheitsprüfung fehlgeschlagen');
        }
        
        // Berechtigungsprüfung - verwende eine niedrigere Berechtigung
        if (!current_user_can('read')) {
            wp_die('Keine Berechtigung');
        }
        
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $filename = 'dienstplan-export-' . $type . '-' . date('Y-m-d') . '.csv';
        
        // Headers für Download setzen
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM für Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        switch ($type) {
            case 'vereine':
                fputcsv($output, array('name', 'kuerzel', 'beschreibung', 'kontakt_name', 'kontakt_email', 'kontakt_telefon'), ';');
                $data = $db->get_vereine();
                if ($data) {
                    foreach ($data as $row) {
                        fputcsv($output, array(
                            $row->name ?? '',
                            $row->kuerzel ?? '',
                            $row->beschreibung ?? '',
                            $row->kontakt_name ?? '',
                            $row->kontakt_email ?? '',
                            $row->kontakt_telefon ?? ''
                        ), ';');
                    }
                }
                break;
                
            case 'veranstaltungen':
                fputcsv($output, array('name', 'start_datum', 'ende_datum', 'beschreibung'), ';');
                $data = $db->get_veranstaltungen();
                if ($data) {
                    foreach ($data as $row) {
                        fputcsv($output, array(
                            $row->name ?? '',
                            $row->start_datum ?? '',
                            $row->end_datum ?? '',
                            $row->beschreibung ?? ''
                        ), ';');
                    }
                }
                break;
                
            case 'dienste':
                fputcsv($output, array('veranstaltung_id', 'veranstaltung_name', 'tag_nummer', 'verein_id', 'verein_name', 'bereich_id', 'bereich_name', 'taetigkeit_id', 'taetigkeit_name', 'von_zeit', 'bis_zeit', 'bis_datum', 'anzahl_personen', 'splittbar', 'status'), ';');
                $data = $db->get_dienste();
                if ($data) {
                    foreach ($data as $row) {
                        // Hole Tag-Nummer
                        $tag_nummer = '';
                        if (!empty($row->tag_id)) {
                            $tag = $db->get_veranstaltung_tag($row->tag_id);
                            if ($tag) {
                                $tag_nummer = $tag->tag_nummer;
                            }
                        }
                        
                        fputcsv($output, array(
                            $row->veranstaltung_id ?? '',
                            '', // veranstaltung_name (nicht in get_dienste)
                            $tag_nummer,
                            $row->verein_id ?? '',
                            $row->verein_name ?? '',
                            $row->bereich_id ?? '',
                            $row->bereich_name ?? '',
                            $row->taetigkeit_id ?? '',
                            $row->taetigkeit_name ?? '',
                            $row->von_zeit ?? '',
                            $row->bis_zeit ?? '',
                            $row->bis_datum ?? '',
                            $row->anzahl_personen ?? '',
                            $row->splittbar ? '1' : '0',
                            $row->status ?? 'geplant'
                        ), ';');
                    }
                }
                break;
        }
        
        fclose($output);
        exit; // Wichtig: WordPress-Output verhindern
    }
    
    /**
     * Import CSV Handler
     */
    public function ajax_import_csv() {
        // Verhindere jeglichen Output vor JSON
        if (ob_get_level()) {
            ob_clean();
        }
        
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        // Niedrigere Berechtigung für Import
        if (!current_user_can('read')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
        }
        
        $import_type = isset($_POST['import_type']) ? sanitize_text_field($_POST['import_type']) : '';
        $import_mode = isset($_POST['import_mode']) ? sanitize_text_field($_POST['import_mode']) : 'create';
        $timezone_input = isset($_POST['timezone']) ? sanitize_text_field($_POST['timezone']) : 'UTC';
        
        // WICHTIG: Die Zeitzone wird nur für Datumsangaben verwendet, NICHT für Uhrzeiten!
        // Uhrzeiten werden immer "as-is" aus der CSV importiert (in lokaler Zeit)
        // Die Zeitzone-Auswahl ist nur für Datumsverarbeitung relevant
        $timezone = 'UTC'; // Für Datumsverarbeitung standard auf UTC
        
        if ($timezone_input === 'WordPress') {
            // Zeitzone-Info: Wird derzeit nicht für Zeit-Konvertierung verwendet
            $wp_timezone = get_option('timezone_string');
            if (!empty($wp_timezone)) {
                // $timezone = $wp_timezone; // Deaktiviert: Zeiten sollten nicht konvertiert werden
            }
        }
        
        // Dekodiere JSON-Strings
        $csv_data = isset($_POST['csv_data']) ? json_decode(stripslashes($_POST['csv_data']), true) : array();
        $mapping = isset($_POST['mapping']) ? json_decode(stripslashes($_POST['mapping']), true) : array();
        
        // Stelle sicher, dass alle Daten in UTF-8 sind
        if (!empty($csv_data) && is_array($csv_data)) {
            $csv_data = array_map(function($row) {
                if (is_array($row)) {
                    return array_map(function($cell) {
                        // Konvertiere zu UTF-8 falls nötig
                        if (is_string($cell) && !mb_check_encoding($cell, 'UTF-8')) {
                            // Versuche von Latin1/ISO-8859-1 zu UTF-8
                            $cell = mb_convert_encoding($cell, 'UTF-8', 'ISO-8859-1');
                        }
                        return $cell;
                    }, $row);
                }
                return $row;
            }, $csv_data);
        }
        
        if (is_array($mapping)) {
            $mapping = array_map(function($item) {
                if (is_string($item) && !mb_check_encoding($item, 'UTF-8')) {
                    return mb_convert_encoding($item, 'UTF-8', 'ISO-8859-1');
                }
                return $item;
            }, $mapping);
        }
        
        if (empty($csv_data) || empty($mapping)) {
            wp_send_json_error(array(
                'message' => 'Keine Daten oder Mapping vorhanden',
                'debug' => array(
                    'csv_data_type' => gettype($csv_data),
                    'csv_data_count' => is_array($csv_data) ? count($csv_data) : 0,
                    'mapping_type' => gettype($mapping),
                    'mapping_count' => is_array($mapping) ? count($mapping) : 0
                )
            ));
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $error_details = array();
        
        // Zeitzone Objekt für Konvertierung
        $tz = new DateTimeZone($timezone);
        $tz_utc = new DateTimeZone('UTC');
        
        // Hilfsfunktion: Datum aus verschiedenen Formaten parsen
        $parse_date = function($date_string) {
            if (empty($date_string)) {
                return false;
            }
            
            // Entferne Leerzeichen
            $date_string = trim($date_string);
            
            // Verschiedene Formate ausprobieren
            $formats = array(
                'Y-m-d',           // 2024-12-31
                'd.m.Y',           // 31.12.2024
                'd/m/Y',           // 31/12/2024
                'm/d/Y',           // 12/31/2024 (US)
                'd-m-Y',           // 31-12-2024
                'Y/m/d',           // 2024/12/31
                'd.m.y',           // 31.12.24
                'd/m/y',           // 31/12/24
                'm/d/y',           // 12/31/24 (US)
                'Y-m-d H:i:s',     // 2024-12-31 14:30:00
                'd.m.Y H:i:s',     // 31.12.2024 14:30:00
            );
            
            foreach ($formats as $format) {
                $date = DateTime::createFromFormat($format, $date_string);
                if ($date !== false && $date->format($format) === $date_string) {
                    return $date->getTimestamp();
                }
            }
            
            // Fallback: strtotime (flexibler aber weniger präzise)
            $timestamp = strtotime($date_string);
            if ($timestamp !== false) {
                return $timestamp;
            }
            
            return false;
        };
        
        switch ($import_type) {
            case 'vereine':
                $row_number = 1;
                foreach ($csv_data as $row) {
                    $row_number++;
                    
                    // Mapping anwenden
                    $data = array();
                    foreach ($mapping as $field => $csvIndex) {
                        $data[$field] = isset($row[$csvIndex]) ? trim($row[$csvIndex]) : '';
                    }
                    
                    if (empty($data['name']) || empty($data['kuerzel'])) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Pflichtfelder fehlen (Name oder Kürzel)";
                        continue;
                    }
                    
                    // Check ob existiert
                    $existing = $db->get_verein_by_kuerzel($data['kuerzel']);
                    
                    if ($existing && $import_mode === 'update') {
                        $result = $db->update_verein($existing['id'], $data);
                        if ($result !== false) {
                            $updated++;
                        } else {
                            $errors++;
                            $error_details[] = "Zeile {$row_number}: Fehler beim Aktualisieren von Verein '{$data['name']}'";
                        }
                    } elseif (!$existing) {
                        $result = $db->add_verein($data);
                        if ($result !== false) {
                            $created++;
                        } else {
                            $errors++;
                            $error_details[] = "Zeile {$row_number}: Fehler beim Erstellen von Verein '{$data['name']}'";
                        }
                    } else {
                        $skipped++;
                    }
                }
                break;
                
            case 'veranstaltungen':
                $row_number = 1;
                foreach ($csv_data as $row) {
                    $row_number++;
                    
                    // Mapping anwenden
                    $data = array();
                    foreach ($mapping as $field => $csvIndex) {
                        $data[$field] = isset($row[$csvIndex]) ? trim($row[$csvIndex]) : '';
                    }
                    
                    if (empty($data['name']) || empty($data['start_datum']) || empty($data['ende_datum'])) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Pflichtfelder fehlen (Name, Start-Datum, Ende-Datum)";
                        continue;
                    }
                    
                    // Datum-Validierung mit intelligenter Erkennung
                    $start_timestamp = $parse_date($data['start_datum']);
                    $ende_timestamp = $parse_date($data['ende_datum']);
                    
                    if ($start_timestamp === false) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Ungültiges Start-Datum '{$data['start_datum']}'";
                        continue;
                    }
                    
                    if ($ende_timestamp === false) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Ungültiges Ende-Datum '{$data['ende_datum']}'";
                        continue;
                    }
                    
                    // Datum in MySQL-Format konvertieren
                    $data['start_datum'] = date('Y-m-d', $start_timestamp);
                    $data['ende_datum'] = date('Y-m-d', $ende_timestamp);
                    
                    // Setze Standardwerte wenn nicht gemappt
                    if (!isset($data['dienst_von_zeit']) || empty($data['dienst_von_zeit'])) {
                        $data['dienst_von_zeit'] = '08:00';
                    }
                    if (!isset($data['dienst_bis_zeit']) || empty($data['dienst_bis_zeit'])) {
                        $data['dienst_bis_zeit'] = '22:00';
                    }
                    
                    // Check ob existiert
                    $existing = $db->get_veranstaltung_by_name($data['name']);
                    
                    if ($existing && $import_mode === 'update') {
                        $result = $db->update_veranstaltung($existing['id'], $data);
                        if ($result !== false) {
                            $updated++;
                        } else {
                            $errors++;
                            $error_details[] = "Zeile {$row_number}: Fehler beim Aktualisieren von Veranstaltung '{$data['name']}'";
                        }
                    } elseif (!$existing) {
                        $result = $db->add_veranstaltung($data);
                        if ($result !== false) {
                            $created++;
                        } else {
                            $errors++;
                            $error_details[] = "Zeile {$row_number}: Fehler beim Erstellen von Veranstaltung '{$data['name']}'";
                        }
                    } else {
                        $skipped++;
                    }
                }
                break;
                
            case 'dienste':
                // Veranstaltungs-Daten aus POST holen
                $veranstaltung_id = isset($_POST['veranstaltung_id']) ? intval($_POST['veranstaltung_id']) : 0;
                $veranstaltung_start = isset($_POST['veranstaltung_start']) ? sanitize_text_field($_POST['veranstaltung_start']) : '';
                $veranstaltung_ende = isset($_POST['veranstaltung_ende']) ? sanitize_text_field($_POST['veranstaltung_ende']) : '';
                
                if (!$veranstaltung_id || !$veranstaltung_start) {
                    wp_send_json_error(array('message' => 'Veranstaltung fehlt oder ungültig'));
                    return;
                }
                
                // Veranstaltungs-Zeitraum parsen
                $start_timestamp = strtotime($veranstaltung_start);
                $ende_timestamp = strtotime($veranstaltung_ende);
                
                $row_number = 1; // Zeilennummer für Fehlermeldungen
                foreach ($csv_data as $row) {
                    $row_number++;
                    
                    // Mapping anwenden
                    $data = array();
                    foreach ($mapping as $field => $csvIndex) {
                        $data[$field] = isset($row[$csvIndex]) ? trim($row[$csvIndex]) : '';
                    }
                    
                    // Prüfe Pflichtfelder (nur Datum ist Pflicht)
                    if (empty($data['datum'])) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Pflichtfeld 'Datum' fehlt";
                        continue;
                    }
                    
                    // Berechne Tag-Nummer aus Datum - mit intelligenter Datumserkennung
                    $dienst_timestamp = $parse_date($data['datum']);
                    if ($dienst_timestamp === false) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Ungültiges Datum '{$data['datum']}' - unterstützte Formate: YYYY-MM-DD, DD.MM.YYYY, DD/MM/YYYY, MM/DD/YYYY";
                        continue;
                    }
                    
                    // Inklusiver Vergleich: Start <= Dienst <= Ende
                    if ($dienst_timestamp < $start_timestamp || $dienst_timestamp > strtotime($veranstaltung_ende . ' 23:59:59')) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Datum '{$data['datum']}' liegt außerhalb der Veranstaltung ({$veranstaltung_start} - {$veranstaltung_ende})";
                        continue;
                    }
                    
                    // Tag-Nummer berechnen (1-basiert)
                    $tag_nummer = floor(($dienst_timestamp - $start_timestamp) / 86400) + 1;
                    
                    // Finde den Tag-ID aus der Veranstaltung
                    $veranstaltung_tage = $db->get_veranstaltung_tage($veranstaltung_id);
                    $tag_id = null;
                    
                    // Normalisiere das Datums-Format für Vergleich (auf YYYY-MM-DD)
                    $dienst_datum_normalized = date('Y-m-d', $dienst_timestamp);
                    
                    foreach ($veranstaltung_tage as $tag) {
                        // Vergleiche beide Formate
                        $tag_datum_normalized = date('Y-m-d', strtotime($tag->tag_datum));
                        if ($tag_datum_normalized === $dienst_datum_normalized) {
                            $tag_id = $tag->id;
                            break;
                        }
                    }
                    
                    // Wenn kein Tag gefunden, detaillierten Fehler ausgeben
                    if (!$tag_id) {
                        $errors++;
                        $verfuegbare_tage = array_map(function($t) { 
                            return date('d.m.Y', strtotime($t->tag_datum)); 
                        }, $veranstaltung_tage);
                        $verfuegbare_str = !empty($verfuegbare_tage) ? implode(', ', $verfuegbare_tage) : 'keine Tage definiert';
                        $error_details[] = "Zeile {$row_number}: Datum '{$data['datum']}' nicht in Veranstaltung gefunden. Verfügbare Tage: {$verfuegbare_str}";
                        continue;
                    }
                    
                    // Basis-Dienst-Daten
                    $dienst_data = array(
                        'veranstaltung_id' => $veranstaltung_id,
                        'tag_id' => $tag_id
                    );
                    
                    // Zeiten hinzufügen falls vorhanden und normalisieren
                    if (!empty($data['von_zeit'])) {
                        // Normalisiere Zeit-Format: 19.00 -> 19:00:00
                        $von_zeit = str_replace('.', ':', $data['von_zeit']);
                        // Stelle sicher dass Format HH:MM:SS ist
                        if (preg_match('/^\d{1,2}:\d{2}$/', $von_zeit)) {
                            $von_zeit .= ':00';
                        }
                        $dienst_data['von_zeit'] = $von_zeit;
                    }
                    if (!empty($data['bis_zeit'])) {
                        // Normalisiere Zeit-Format: 01.00 -> 01:00:00
                        $bis_zeit = str_replace('.', ':', $data['bis_zeit']);
                        // Stelle sicher dass Format HH:MM:SS ist
                        if (preg_match('/^\d{1,2}:\d{2}$/', $bis_zeit)) {
                            $bis_zeit .= ':00';
                        }
                        $dienst_data['bis_zeit'] = $bis_zeit;
                        
                        // Prüfe ob Overnight-Dienst (Ende < Start)
                        if (!empty($dienst_data['von_zeit'])) {
                            $von_hour = intval(substr($dienst_data['von_zeit'], 0, 2));
                            $bis_hour = intval(substr($bis_zeit, 0, 2));
                            
                            // Wenn bis_zeit kleiner als von_zeit, ist es ein Overnight-Dienst
                            if ($bis_hour < $von_hour) {
                                // Setze bis_datum auf nächsten Tag
                                $tag = $db->get_veranstaltung_tag($tag_id);
                                if ($tag) {
                                    $next_day = date('Y-m-d', strtotime($tag->tag_datum . ' +1 day'));
                                    $dienst_data['bis_datum'] = $next_day;
                                }
                            }
                        }
                    }
                    
                    // Tracking für fehlende/problematische Daten
                    $missing_info = array();
                    
                    // Prüfe ob Zeiten fehlen
                    if (empty($data['von_zeit']) || empty($data['bis_zeit'])) {
                        $missing_info[] = "Zeiten (von/bis) fehlen";
                    }
                    
                    // Verein nach Kürzel suchen (mit Fuzzy-Matching für Abkürzungen)
                    if (!empty($data['verein_kuerzel'])) {
                        $kuerzel_input = trim($data['verein_kuerzel']);
                        
                        // Kürzel-Mapping für häufige Varianten
                        $kuerzel_aliases = array(
                            'SC' => array('SCJ', 'SC-J', 'SC J'),
                            'EC' => array('ECJ', 'EC-J', 'EC J'),
                            'CV' => array('CVJM', 'CV-JM', 'CV JM'),
                            // Weitere können hier hinzugefügt werden
                        );
                        
                        // Versuche direktes Match
                        $verein = $db->get_verein_by_kuerzel($kuerzel_input);
                        
                        // Wenn nicht gefunden, versuche Aliases
                        if (!$verein && isset($kuerzel_aliases[$kuerzel_input])) {
                            foreach ($kuerzel_aliases[$kuerzel_input] as $alias) {
                                $verein = $db->get_verein_by_kuerzel($alias);
                                if ($verein) {
                                    $error_details[] = "Zeile {$row_number}: Info - '{$kuerzel_input}' automatisch zu '{$alias}' zugeordnet";
                                    break;
                                }
                            }
                        }
                        
                        // Wenn immer noch nicht gefunden, versuche Teilstring-Suche
                        if (!$verein) {
                            $all_vereine = $db->get_vereine(false); // Alle Vereine (auch inaktive)
                            foreach ($all_vereine as $v) {
                                if (stripos($v->kuerzel, $kuerzel_input) !== false || 
                                    stripos($kuerzel_input, $v->kuerzel) !== false) {
                                    $verein = (array) $v;
                                    $error_details[] = "Zeile {$row_number}: Info - '{$kuerzel_input}' ähnlich zu '{$v->kuerzel}' zugeordnet";
                                    break;
                                }
                            }
                        }
                        
                        if ($verein) {
                            $dienst_data['verein_id'] = $verein['id'];
                        } else {
                            $missing_info[] = "Verein '{$kuerzel_input}' nicht gefunden";
                            $error_details[] = "Zeile {$row_number}: ⚠️ Verein mit Kürzel '{$kuerzel_input}' nicht gefunden - Dienst wird als unvollständig markiert";
                        }
                    } else {
                        $missing_info[] = "Kein Verein angegeben";
                    }
                    
                    // Bereich nach Name suchen/erstellen
                    if (!empty($data['bereich_name'])) {
                        $bereich = $db->get_or_create_bereich($data['bereich_name']);
                        if ($bereich) {
                            $dienst_data['bereich_id'] = $bereich;
                        } else {
                            $missing_info[] = "Bereich '{$data['bereich_name']}' konnte nicht erstellt werden";
                        }
                    } else {
                        $missing_info[] = "Kein Bereich angegeben";
                    }
                    
                    // Tätigkeit nach Name suchen/erstellen
                    if (!empty($data['taetigkeit_name'])) {
                        // Verwende bereich_id falls vorhanden
                        $bereich_id_for_taetigkeit = isset($dienst_data['bereich_id']) ? $dienst_data['bereich_id'] : null;
                        $taetigkeit = $db->get_or_create_taetigkeit($data['taetigkeit_name'], $bereich_id_for_taetigkeit);
                        if ($taetigkeit) {
                            $dienst_data['taetigkeit_id'] = $taetigkeit;
                        } else {
                            $missing_info[] = "Tätigkeit '{$data['taetigkeit_name']}' konnte nicht erstellt werden";
                        }
                    } else {
                        $missing_info[] = "Keine Tätigkeit angegeben";
                    }
                    
                    // Setze Status basierend auf fehlenden Informationen
                    if (!empty($missing_info)) {
                        $dienst_data['status'] = 'unvollstaendig';
                        // Speichere Warnungen separat (nicht in besonderheiten)
                        $error_details[] = array(
                            'row' => $row_number,
                            'type' => 'warning',
                            'message' => 'Unvollständige Daten: ' . implode(', ', $missing_info),
                            'dienst' => isset($data['taetigkeit']) ? $data['taetigkeit'] : 'Unbekannt'
                        );
                    } else {
                        $dienst_data['status'] = 'geplant';
                    }
                    
                    // Übernehme Besonderheiten aus CSV wenn vorhanden
                    if (isset($data['besonderheiten']) && !empty($data['besonderheiten'])) {
                        $dienst_data['besonderheiten'] = $data['besonderheiten'];
                    }
                    
                    // Optional fields
                    if (isset($data['anzahl_personen']) && !empty($data['anzahl_personen'])) {
                        $dienst_data['anzahl_personen'] = intval($data['anzahl_personen']) ?: 1;
                    } else {
                        $dienst_data['anzahl_personen'] = 1;
                    }
                    
                    if (isset($data['splittbar'])) {
                        $dienst_data['splittbar'] = ($data['splittbar'] === '1' || $data['splittbar'] === 'true');
                    } else {
                        $dienst_data['splittbar'] = false;
                    }
                    
                    $result = $db->add_dienst($dienst_data);
                    
                    // Prüfe ob Fehler-Array zurückgegeben wurde
                    if (is_array($result) && isset($result['error']) && $result['error']) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: " . $result['message'];
                    } elseif ($result !== false && is_numeric($result)) {
                        // Erfolg: $result ist die neue dienst_id
                        // Slots werden automatisch von add_dienst() erstellt via create_dienst_slots()
                        $created++;
                    } else {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Fehler beim Speichern des Dienstes in die Datenbank";
                    }
                }
                
                // Nach dem Import: Verknüpfe alle verwendeten Vereine mit der Veranstaltung
                if ($created > 0 || $updated > 0) {
                    // Hole alle Vereine, die in dieser Veranstaltung verwendet werden
                    $veranstaltung_dienste = $db->get_dienste($veranstaltung_id);
                    $used_verein_ids = array();
                    foreach ($veranstaltung_dienste as $dienst) {
                        if (!empty($dienst->verein_id) && !in_array($dienst->verein_id, $used_verein_ids)) {
                            $used_verein_ids[] = $dienst->verein_id;
                        }
                    }
                    
                    // Füge alle verwendeten Vereine zur Veranstaltung hinzu (falls noch nicht vorhanden)
                    foreach ($used_verein_ids as $verein_id) {
                        $existing = $db->get_wpdb()->get_var($db->get_wpdb()->prepare(
                            "SELECT id FROM {$db->get_prefix()}veranstaltung_vereine WHERE veranstaltung_id = %d AND verein_id = %d",
                            $veranstaltung_id, $verein_id
                        ));
                        
                        if (!$existing) {
                            $db->add_veranstaltung_verein($veranstaltung_id, $verein_id);
                        }
                    }
                }
                break;
        }
        
        // Stelle sicher, dass kein Output vor JSON kommt
        if (ob_get_level()) {
            ob_clean();
        }
        
        wp_send_json_success(array(
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'error_details' => $error_details,
            'message' => sprintf('Import abgeschlossen: %d erstellt, %d aktualisiert, %d übersprungen, %d Fehler', 
                $created, $updated, $skipped, $errors)
        ));
    }
    
    /**
     * AJAX-Handler für Bulk-Löschen von Diensten
     */
    public function ajax_bulk_delete_dienste() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $dienst_ids = isset($_POST['dienst_ids']) ? array_map('intval', $_POST['dienst_ids']) : array();
        
        if (empty($dienst_ids)) {
            wp_send_json_error(array('message' => 'Keine Dienste ausgewählt'));
            return;
        }
        
        $db = new Dienstplan_Database();
        $deleted = 0;
        
        foreach ($dienst_ids as $dienst_id) {
            if ($db->delete_dienst($dienst_id)) {
                $deleted++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('%d Dienst(e) erfolgreich gelöscht', $deleted),
            'deleted' => $deleted
        ));
    }
    
    /**
     * AJAX-Handler für Bulk-Update von Diensten
     */
    public function ajax_bulk_update_dienste() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $dienst_ids = isset($_POST['dienst_ids']) ? array_map('intval', $_POST['dienst_ids']) : array();
        $update_data = isset($_POST['update_data']) ? $_POST['update_data'] : array();
        
        if (empty($dienst_ids)) {
            wp_send_json_error(array('message' => 'Keine Dienste ausgewählt'));
            return;
        }
        
        if (empty($update_data)) {
            wp_send_json_error(array('message' => 'Keine Änderungen angegeben'));
            return;
        }
        
        $db = new Dienstplan_Database();
        $updated = 0;
        $errors = array();
        
        foreach ($dienst_ids as $dienst_id) {
            // Hole aktuellen Dienst
            $dienst = $db->get_dienst($dienst_id);
            if (!$dienst) {
                $errors[] = "Dienst ID $dienst_id nicht gefunden";
                continue;
            }
            
            // Zeit-Konvertierung für von_zeit und bis_zeit
            $von_zeit = $dienst->von_zeit;
            $bis_zeit = $dienst->bis_zeit;
            
            if (isset($update_data['von_zeit'])) {
                $von_zeit = $this->convert_time_to_utc($update_data['von_zeit']);
            }
            if (isset($update_data['bis_zeit'])) {
                $bis_zeit = $this->convert_time_to_utc($update_data['bis_zeit']);
            }
            
            // Merge update_data mit vorhandenen Daten
            $dienst_data = array(
                'veranstaltung_id' => $dienst->veranstaltung_id,
                'verein_id' => isset($update_data['verein_id']) ? intval($update_data['verein_id']) : $dienst->verein_id,
                'bereich_id' => isset($update_data['bereich_id']) ? intval($update_data['bereich_id']) : $dienst->bereich_id,
                'taetigkeit_id' => isset($update_data['taetigkeit_id']) ? intval($update_data['taetigkeit_id']) : $dienst->taetigkeit_id,
                'von_zeit' => $von_zeit,
                'bis_zeit' => $bis_zeit,
                'status' => isset($update_data['status']) ? sanitize_text_field($update_data['status']) : $dienst->status,
                'anzahl_personen' => $dienst->anzahl_personen,
                'besonderheiten' => $dienst->besonderheiten
            );
            
            // tag_id nur setzen, wenn explizit übergeben (für Tag-Wechsel)
            if (isset($update_data['tag_id'])) {
                $dienst_data['tag_id'] = intval($update_data['tag_id']);
            }
            
            $result = $db->update_dienst($dienst_id, $dienst_data);
            
            if ($result === false) {
                $errors[] = "Fehler beim Update von Dienst ID $dienst_id";
            } elseif (is_array($result) && isset($result['error'])) {
                $errors[] = "Dienst ID $dienst_id: " . $result['message'];
            } else {
                $updated++;
            }
        }
        
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => sprintf('%d Dienst(e) aktualisiert, %d Fehler', $updated, count($errors)),
                'errors' => $errors,
                'updated' => $updated
            ));
        } else {
            wp_send_json_success(array(
                'message' => sprintf('%d Dienst(e) erfolgreich aktualisiert', $updated),
                'updated' => $updated
            ));
        }
    }
    
    /**
     * Konvertiert eine Zeit aus der WordPress-Zeitzone nach UTC
     * @param string $time Zeit im Format HH:MM oder HH:MM:SS
     * @return string Zeit in UTC im Format HH:MM:SS
     */
    private function convert_time_to_utc($time) {
        // Stelle sicher, dass die Zeit im HH:MM:SS Format ist
        $time = sanitize_text_field($time);
        if (strlen($time) == 5) {
            $time .= ':00';
        }
        
        // Hole WordPress Zeitzone
        $wp_timezone = wp_timezone();
        
        // Erstelle DateTime-Objekt mit aktueller Zeitzone
        $date = new DateTime('today ' . $time, $wp_timezone);
        
        // Konvertiere zu UTC
        $date->setTimezone(new DateTimeZone('UTC'));
        
        return $date->format('H:i:s');
    }
    
    /**
     * AJAX-Handler: Bereich holen
     */
    public function ajax_get_bereich() {
        check_ajax_referer('dienstplan-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $bereich_id = isset($_POST['bereich_id']) ? intval($_POST['bereich_id']) : 0;
        
        if (!$bereich_id) {
            wp_send_json_error(array('message' => 'Keine Bereich-ID angegeben'));
            return;
        }
        
        $db = new Dienstplan_Database();
        $bereich = $db->get_bereich($bereich_id);
        
        if (!$bereich) {
            wp_send_json_error(array('message' => 'Bereich nicht gefunden'));
            return;
        }
        
        wp_send_json_success($bereich);
    }
    
    /**
     * AJAX-Handler: Bereich speichern
     */
    public function ajax_save_bereich() {
        check_ajax_referer('dienstplan-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $bereich_id = isset($_POST['bereich_id']) ? intval($_POST['bereich_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $farbe = isset($_POST['farbe']) ? sanitize_text_field($_POST['farbe']) : '#3b82f6';
        
        if (empty($name)) {
            wp_send_json_error(array('message' => 'Name ist erforderlich'));
            return;
        }
        
        $db = new Dienstplan_Database();
        $data = array(
            'name' => $name,
            'farbe' => $farbe
        );
        
        if ($bereich_id) {
            $result = $db->update_bereich($bereich_id, $data);
            $message = 'Bereich erfolgreich aktualisiert';
        } else {
            $result = $db->create_bereich($data);
            $message = 'Bereich erfolgreich erstellt';
        }
        
        if ($result) {
            wp_send_json_success(array('message' => $message, 'id' => $result));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Speichern'));
        }
    }
    
    /**
     * AJAX-Handler: Bereich löschen
     */
    public function ajax_delete_bereich() {
        check_ajax_referer('dienstplan-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $bereich_id = isset($_POST['bereich_id']) ? intval($_POST['bereich_id']) : 0;
        
        if (!$bereich_id) {
            wp_send_json_error(array('message' => 'Keine Bereich-ID angegeben'));
            return;
        }
        
        $db = new Dienstplan_Database();
        
        if ($db->delete_bereich($bereich_id)) {
            wp_send_json_success(array('message' => 'Bereich erfolgreich gelöscht'));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Löschen'));
        }
    }
    
    /**
     * AJAX-Handler: Tätigkeit holen
     */
    public function ajax_get_taetigkeit() {
        check_ajax_referer('dienstplan-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $taetigkeit_id = isset($_POST['taetigkeit_id']) ? intval($_POST['taetigkeit_id']) : 0;
        
        if (!$taetigkeit_id) {
            wp_send_json_error(array('message' => 'Keine Tätigkeits-ID angegeben'));
            return;
        }
        
        $db = new Dienstplan_Database();
        $taetigkeit = $db->get_taetigkeit($taetigkeit_id);
        
        if (!$taetigkeit) {
            wp_send_json_error(array('message' => 'Tätigkeit nicht gefunden'));
            return;
        }
        
        wp_send_json_success($taetigkeit);
    }
    
    /**
     * AJAX-Handler: Tätigkeit speichern
     */
    public function ajax_save_taetigkeit() {
        check_ajax_referer('dienstplan-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $taetigkeit_id = isset($_POST['taetigkeit_id']) ? intval($_POST['taetigkeit_id']) : 0;
        $bereich_id = isset($_POST['bereich_id']) ? intval($_POST['bereich_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $beschreibung = isset($_POST['beschreibung']) ? sanitize_textarea_field($_POST['beschreibung']) : '';
        $aktiv = isset($_POST['aktiv']) ? intval($_POST['aktiv']) : 1;
        
        if (empty($name) || !$bereich_id) {
            wp_send_json_error(array('message' => 'Name und Bereich sind erforderlich'));
            return;
        }
        
        $db = new Dienstplan_Database();
        $data = array(
            'bereich_id' => $bereich_id,
            'name' => $name,
            'beschreibung' => $beschreibung,
            'aktiv' => $aktiv
        );
        
        if ($taetigkeit_id) {
            $result = $db->update_taetigkeit($taetigkeit_id, $data);
            $message = 'Tätigkeit erfolgreich aktualisiert';
        } else {
            $result = $db->create_taetigkeit($data);
            $message = 'Tätigkeit erfolgreich erstellt';
        }
        
        if ($result) {
            wp_send_json_success(array('message' => $message, 'id' => $result));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Speichern'));
        }
    }
    
    /**
     * AJAX-Handler: Tätigkeit löschen
     */
    public function ajax_delete_taetigkeit() {
        check_ajax_referer('dienstplan-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $taetigkeit_id = isset($_POST['taetigkeit_id']) ? intval($_POST['taetigkeit_id']) : 0;
        
        if (!$taetigkeit_id) {
            wp_send_json_error(array('message' => 'Keine Tätigkeits-ID angegeben'));
            return;
        }
        
        $db = new Dienstplan_Database();
        
        if ($db->delete_taetigkeit($taetigkeit_id)) {
            wp_send_json_success(array('message' => 'Tätigkeit erfolgreich gelöscht'));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Löschen'));
        }
    }
    
    /**
     * AJAX-Handler: Tätigkeit Status togglen
     */
    public function ajax_toggle_taetigkeit_status() {
        check_ajax_referer('dienstplan-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $taetigkeit_id = isset($_POST['taetigkeit_id']) ? intval($_POST['taetigkeit_id']) : 0;
        $aktiv = isset($_POST['aktiv']) ? intval($_POST['aktiv']) : 0;
        
        if (!$taetigkeit_id) {
            wp_send_json_error(array('message' => 'Keine Tätigkeits-ID angegeben'));
            return;
        }
        
        $db = new Dienstplan_Database();
        
        if ($db->update_taetigkeit($taetigkeit_id, array('aktiv' => $aktiv))) {
            wp_send_json_success(array('message' => 'Status erfolgreich geändert'));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Ändern des Status'));
        }
    }
    
    /**
     * AJAX-Handler: Bulk-Löschen von Tätigkeiten
     */
    public function ajax_bulk_delete_taetigkeiten() {
        check_ajax_referer('dienstplan-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $taetigkeit_ids = isset($_POST['taetigkeit_ids']) ? array_map('intval', $_POST['taetigkeit_ids']) : array();
        
        if (empty($taetigkeit_ids)) {
            wp_send_json_error(array('message' => 'Keine Tätigkeiten ausgewählt'));
            return;
        }
        
        $db = new Dienstplan_Database();
        $deleted = 0;
        $errors = 0;
        
        foreach ($taetigkeit_ids as $taetigkeit_id) {
            if ($db->delete_taetigkeit($taetigkeit_id)) {
                $deleted++;
            } else {
                $errors++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('%d Tätigkeit(en) erfolgreich gelöscht', $deleted),
            'deleted' => $deleted,
            'errors' => $errors
        ));
    }
    
    /**
     * AJAX-Handler: Bulk-Update von Tätigkeiten
     */
    public function ajax_bulk_update_taetigkeiten() {
        check_ajax_referer('dienstplan-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $taetigkeit_ids = isset($_POST['taetigkeit_ids']) ? array_map('intval', $_POST['taetigkeit_ids']) : array();
        $update_data = isset($_POST['update_data']) ? $_POST['update_data'] : array();
        
        if (empty($taetigkeit_ids)) {
            wp_send_json_error(array('message' => 'Keine Tätigkeiten ausgewählt'));
            return;
        }
        
        if (empty($update_data)) {
            wp_send_json_error(array('message' => 'Keine Änderungen angegeben'));
            return;
        }
        
        $db = new Dienstplan_Database();
        $updated = 0;
        
        foreach ($taetigkeit_ids as $taetigkeit_id) {
            if ($db->update_taetigkeit($taetigkeit_id, $update_data)) {
                $updated++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('%d Tätigkeit(en) erfolgreich aktualisiert', $updated),
            'updated' => $updated
        ));
    }
    
    /**
     * Überprüfe und korrigiere Dienst-Status basierend auf Pflichtfeldern
     */
    public function ajax_check_dienst_status() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        global $wpdb;
        $table = $wpdb->prefix . $this->db_prefix . 'dienste';
        
        // Alle Dienste abrufen
        $dienste = $wpdb->get_results("SELECT * FROM {$table}");
        
        if (empty($dienste)) {
            wp_send_json_success(array(
                'message' => 'Keine Dienste gefunden',
                'total' => 0,
                'updated' => 0,
                'incomplete' => 0
            ));
            return;
        }
        
        $updated = 0;
        $incomplete_count = 0;
        
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
                if (empty($field_value) || $field_value === 0) {
                    $all_filled = false;
                    break;
                }
            }
            
            // Bestimme neuen Status
            $new_status = $all_filled ? 'geplant' : 'unvollständig';
            
            // Update wenn Status anders ist
            if ($dienst->status !== $new_status) {
                $wpdb->update(
                    $table,
                    array('status' => $new_status),
                    array('id' => $dienst->id),
                    array('%s'),
                    array('%d')
                );
                $updated++;
            }
            
            if ($new_status === 'unvollständig') {
                $incomplete_count++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('%d Dienst(e) überprüft, %d Status-Änderungen vorgenommen', count($dienste), $updated),
            'total' => count($dienste),
            'updated' => $updated,
            'incomplete' => $incomplete_count
        ));
    }
    
    /**
     * Login-Redirect für Dienstplan-Rollen
     * 
     * Leitet Benutzer mit Dienstplan-Rollen nach Login direkt zum Dienstplan-Dashboard,
     * nicht zum WordPress-Profil
     */
    public function login_redirect($redirect_to, $request, $user) {
        // Prüfe ob User-Objekt existiert und keine Errors hat
        if (!isset($user->ID) || is_wp_error($user)) {
            return $redirect_to;
        }
        
        // Prüfe ob User eine Dienstplan-Rolle hat
        $has_dp_role = false;
        $user_roles = $user->roles;
        
        $dp_roles = array(
            Dienstplan_Roles::ROLE_GENERAL_ADMIN,
            Dienstplan_Roles::ROLE_EVENT_ADMIN,
            Dienstplan_Roles::ROLE_CLUB_ADMIN
        );
        
        foreach ($dp_roles as $role) {
            if (in_array($role, $user_roles)) {
                $has_dp_role = true;
                break;
            }
        }
        
        // Wenn Dienstplan-Rolle: Redirect zu Dienstplan-Dashboard
        if ($has_dp_role) {
            // WordPress-Admin hat Vorrang
            if (in_array('administrator', $user_roles)) {
                return admin_url('admin.php?page=dienstplan');
            }
            
            // Event-Admin → Veranstaltungen
            if (in_array(Dienstplan_Roles::ROLE_EVENT_ADMIN, $user_roles)) {
                return admin_url('admin.php?page=dienstplan-veranstaltungen');
            }
            
            // Club-Admin → Vereine
            if (in_array(Dienstplan_Roles::ROLE_CLUB_ADMIN, $user_roles)) {
                return admin_url('admin.php?page=dienstplan-vereine');
            }
            
            // General-Admin → Dashboard
            if (in_array(Dienstplan_Roles::ROLE_GENERAL_ADMIN, $user_roles)) {
                return admin_url('admin.php?page=dienstplan');
            }
        }
        
        // Standard WordPress-Redirect
        return $redirect_to;
    }
    
    /**
     * Dashboard-Widget hinzufügen
     */
    public function add_dashboard_widget() {
        // Nur für Benutzer mit Dienstplan-Rechten
        if (!Dienstplan_Roles::can_manage_events() && 
            !Dienstplan_Roles::can_manage_clubs() && 
            !current_user_can('manage_options')) {
            return;
        }
        
        wp_add_dashboard_widget(
            'dienstplan_dashboard_widget',
            '<span class="dashicons dashicons-calendar-alt" style="font-size: 20px; margin-right: 8px; vertical-align: middle;"></span>' . __('Dienstplan-Übersicht', 'dienstplan-verwaltung'),
            array($this, 'render_dashboard_widget')
        );
    }
    
    /**
     * Dashboard-Widget rendern
     */
    public function render_dashboard_widget() {
        global $wpdb;
        $prefix = $wpdb->prefix . $this->db_prefix;
        
        // Statistiken sammeln
        $stats = array();
        
        // Vereine (wenn berechtigt)
        if (Dienstplan_Roles::can_manage_clubs() || current_user_can('manage_options')) {
            $stats['vereine'] = array(
                'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}vereine"),
                'aktiv' => $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}vereine WHERE aktiv = 1"),
                'icon' => 'dashicons-flag',
                'color' => '#00a32a',
                'link' => admin_url('admin.php?page=dienstplan-vereine')
            );
        }
        
        // Veranstaltungen (wenn berechtigt)
        if (Dienstplan_Roles::can_manage_events() || current_user_can('manage_options')) {
            $heute = date('Y-m-d');
            $stats['veranstaltungen'] = array(
                'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}veranstaltungen"),
                'kommend' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT v.id) FROM {$prefix}veranstaltungen v 
                     INNER JOIN {$prefix}veranstaltungen_tage t ON v.id = t.veranstaltung_id 
                     WHERE t.datum >= %s",
                    $heute
                )),
                'icon' => 'dashicons-calendar-alt',
                'color' => '#2271b1',
                'link' => admin_url('admin.php?page=dienstplan-veranstaltungen')
            );
            
            // Dienste
            $stats['dienste'] = array(
                'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}dienste"),
                'offen' => $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}dienste WHERE status = 'geplant'"),
                'icon' => 'dashicons-clipboard',
                'color' => '#d63638',
                'link' => admin_url('admin.php?page=dienstplan-dienste')
            );
        }
        
        // Widget HTML
        ?>
        <style>
            .dp-widget-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
                margin-bottom: 1rem;
            }
            
            .dp-widget-stat {
                background: #f6f7f7;
                border-left: 4px solid;
                padding: 1rem;
                border-radius: 4px;
                text-decoration: none;
                color: inherit;
                display: block;
                transition: all 0.2s;
            }
            
            .dp-widget-stat:hover {
                background: #fff;
                transform: translateY(-2px);
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .dp-widget-stat-header {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                margin-bottom: 0.5rem;
                font-size: 0.9rem;
                color: #646970;
            }
            
            .dp-widget-stat-value {
                font-size: 2rem;
                font-weight: bold;
                line-height: 1;
                margin-bottom: 0.25rem;
            }
            
            .dp-widget-stat-label {
                font-size: 0.85rem;
                color: #646970;
            }
            
            .dp-widget-actions {
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px solid #dcdcde;
            }
            
            .dp-widget-link {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                text-decoration: none;
                padding: 0.5rem 1rem;
                background: #2271b1;
                color: #fff;
                border-radius: 4px;
                font-weight: 500;
                transition: background 0.2s;
            }
            
            .dp-widget-link:hover {
                background: #135e96;
                color: #fff;
            }
        </style>
        
        <div class="dp-widget-grid">
            <?php foreach ($stats as $key => $stat): ?>
                <a href="<?php echo esc_url($stat['link']); ?>" 
                   class="dp-widget-stat" 
                   style="border-left-color: <?php echo esc_attr($stat['color']); ?>">
                    <div class="dp-widget-stat-header">
                        <span class="dashicons <?php echo esc_attr($stat['icon']); ?>" 
                              style="color: <?php echo esc_attr($stat['color']); ?>; font-size: 20px;"></span>
                        <span><?php echo esc_html(ucfirst($key)); ?></span>
                    </div>
                    <div class="dp-widget-stat-value" style="color: <?php echo esc_attr($stat['color']); ?>">
                        <?php echo esc_html($stat['total']); ?>
                    </div>
                    <div class="dp-widget-stat-label">
                        <?php 
                        if (isset($stat['aktiv'])) {
                            printf(__('%d aktiv', 'dienstplan-verwaltung'), $stat['aktiv']);
                        } elseif (isset($stat['kommend'])) {
                            printf(__('%d kommend', 'dienstplan-verwaltung'), $stat['kommend']);
                        } elseif (isset($stat['offen'])) {
                            printf(__('%d offen', 'dienstplan-verwaltung'), $stat['offen']);
                        }
                        ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="dp-widget-actions">
            <a href="<?php echo admin_url('admin.php?page=dienstplan'); ?>" class="dp-widget-link">
                <span class="dashicons dashicons-dashboard"></span>
                <?php _e('Zum Dienstplan-Dashboard', 'dienstplan-verwaltung'); ?>
            </a>
        </div>
        <?php
    }
}



<?php
/**
 * Public-facing Funktionalität
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/public
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Public-Klasse
 *
 * Definiert Plugin-Name, Version und Hooks für die öffentliche Seite.
 */
class Dienstplan_Public {

    /**
     * Plugin-Name
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    Plugin-ID
     */
    private $plugin_name;

    /**
     * Plugin-Version
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    Plugin-Version
     */
    private $version;
    
    /**
     * Datenbank-Präfix
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $db_prefix    DB-Präfix
     */
    private $db_prefix;

    /**
     * Initialisierung
     *
     * @since    1.0.0
     * @param    string    $plugin_name    Plugin-Name
     * @param    string    $version        Plugin-Version
     * @param    string    $db_prefix      DB-Präfix
     */
    public function __construct($plugin_name, $version, $db_prefix = null) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->db_prefix = $db_prefix ?? DIENSTPLAN_DB_PREFIX;
    }

    /**
     * Assets (CSS/JS) für Frontend laden
     *
     * @since    1.0.0
     */
    public function enqueue_assets() {
        // CSS
        wp_enqueue_style(
            'dp-public-styles',
            DIENSTPLAN_PLUGIN_URL . 'assets/css/dp-public.css',
            array(),
            $this->version,
            'all'
        );

        // JavaScript
        wp_enqueue_script(
            'dp-public-scripts',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-public.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Timeline Sync Script
        wp_enqueue_script(
            'dp-timeline-sync',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-timeline-sync.js',
            array('jquery'),
            $this->version,
            true
        );

        // AJAX-Daten für JavaScript
        wp_localize_script('dp-public-scripts', 'dpPublic', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dp_public_nonce'), // Public nonce für non-logged-in users
            'i18n' => array(
                'loading' => __('Lädt...', 'dienstplan-verwaltung'),
                'error' => __('Ein Fehler ist aufgetreten', 'dienstplan-verwaltung'),
                'success' => __('Erfolgreich gespeichert', 'dienstplan-verwaltung'),
            )
        ));
    }

    /**
     * Shortcodes registrieren
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('dienstplan', array($this, 'shortcode_dienstplan'));
        add_shortcode('dienstplan_hub', array($this, 'shortcode_dienstplan_hub')); // NEU: Frontend Portal
        add_shortcode('dienstplan_vereine', array($this, 'shortcode_vereine'));
        add_shortcode('dienstplan_veranstaltungen', array($this, 'shortcode_veranstaltungen'));
        add_shortcode('dienstplan_veranstaltung', array($this, 'shortcode_veranstaltung_verein')); // NEU: Für Verein-spezifische Anmeldung
        add_shortcode('meine_dienste', array($this, 'shortcode_meine_dienste'));
        add_shortcode('profil_bearbeiten', array($this, 'shortcode_profil_bearbeiten'));
    }

    /**
     * Shortcode: [dienstplan_hub]
     * Frontend Einstiegsseite mit Login und Veranstaltungsübersicht
     *
     * @since    0.6.6
     * @param    array     $atts    Shortcode-Attribute
     * @return   string    HTML-Output
     */
    public function shortcode_dienstplan_hub($atts) {
        $atts = shortcode_atts(array(
            'show_login' => 'true',
            'show_events' => 'true',
            'limit' => '6',
        ), $atts, 'dienstplan_hub');

        ob_start();
        include DIENSTPLAN_PLUGIN_PATH . 'public/templates/dienstplan-hub.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: [dienstplan]
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode-Attribute
     * @return   string    HTML-Output
     */
    public function shortcode_dienstplan($atts) {
        $atts = shortcode_atts(array(
            'veranstaltung' => '',
            'veranstaltung_id' => isset($_GET['veranstaltung_id']) ? intval($_GET['veranstaltung_id']) : 0,
            'verein' => '',
            'verein_id' => isset($_GET['verein_id']) ? intval($_GET['verein_id']) : 0,
            'show_filter' => 'true',
            'view' => isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'kachel',
        ), $atts, 'dienstplan');

        ob_start();
        
        if ($atts['veranstaltung_id'] > 0) {
            require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
            $db = new Dienstplan_Database($this->db_prefix);

            $veranstaltung_id = intval($atts['veranstaltung_id']);
            $verein_id = intval($atts['verein_id']);
            $veranstaltung = $db->get_veranstaltung($veranstaltung_id);

            if (!$veranstaltung) {
                echo '<div class="dp-notice dp-notice-error"><p>Veranstaltung nicht gefunden.</p></div>';
                return ob_get_clean();
            }

            $verein = null;
            if ($verein_id > 0) {
                $verein = $db->get_verein($verein_id);
            }

            include DIENSTPLAN_PLUGIN_PATH . 'public/templates/veranstaltung-verein.php';
        } else {
            // Übersicht aller Veranstaltungen
            include DIENSTPLAN_PLUGIN_PATH . 'public/templates/veranstaltungen-liste.php';
        }
        
        return ob_get_clean();
    }

    /**
     * Shortcode: [dienstplan_vereine]
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode-Attribute
     * @return   string    HTML-Output
     */
    public function shortcode_vereine($atts) {
        $atts = shortcode_atts(array(
            'show_aktiv' => 'true',
            'verein_id' => 0,
        ), $atts, 'dienstplan_vereine');

        $verein_id = isset($_GET['verein_id']) ? intval($_GET['verein_id']) : intval($atts['verein_id']);

        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-dienstplan-roles.php';
        $db = new Dienstplan_Database($this->db_prefix);

        if ($verein_id > 0 && is_singular('page')) {
            $current_page_id = intval(get_queried_object_id());
            if ($current_page_id > 0) {
                $verein_record = $db->get_verein($verein_id);
                if ($verein_record && intval($verein_record->seite_id) !== $current_page_id) {
                    $db->update_verein_page_id($verein_id, $current_page_id);
                }
            }
        }

        ob_start();
        include DIENSTPLAN_PLUGIN_PATH . 'public/templates/vereine-overview.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: [dienstplan_veranstaltungen]
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode-Attribute
     * @return   string    HTML-Output
     */
    public function shortcode_veranstaltungen($atts) {
        $atts = shortcode_atts(array(
            'limit' => '10',
            'show_past' => 'false',
        ), $atts, 'dienstplan_veranstaltungen');

        ob_start();
        include DIENSTPLAN_PLUGIN_PATH . 'public/templates/veranstaltungen-liste.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [meine_dienste]
     * Zeigt die Dienste eines Mitarbeiters (über Session oder Email-Parameter)
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode-Attribute
     * @return   string    HTML-Output
     */
    public function shortcode_meine_dienste($atts) {
        ob_start();
        
        $mitarbeiter_id = $this->get_current_mitarbeiter_id();
        
        if (!$mitarbeiter_id) {
            echo '<div class="dp-notice dp-notice-info">';
            echo '<p>Bitte geben Sie Ihre E-Mail-Adresse ein, um Ihre Dienste zu sehen.</p>';
            echo '<form method="get" action="">';
            echo '<input type="email" name="dp_email" placeholder="Ihre E-Mail" required style="margin-right: 10px;">';
            echo '<button type="submit" class="button">Anzeigen</button>';
            echo '</form>';
            echo '</div>';
            return ob_get_clean();
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $mitarbeiter = $db->get_mitarbeiter($mitarbeiter_id);
        $dienste = $db->get_mitarbeiter_dienste($mitarbeiter_id);
        
        include DIENSTPLAN_PLUGIN_PATH . 'public/templates/meine-dienste.php';
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [dienstplan_veranstaltung veranstaltung_id="123" verein_id="456"]
     * 
     * Zeigt Dienste einer Veranstaltung gefiltert nach Verein für verein-spezifische Anmeldeseiten
     *
     * @since    0.6.6
     * @param    array     $atts    Shortcode-Attribute
     * @return   string    HTML-Output
     */
    public function shortcode_veranstaltung_verein($atts) {
        $atts = shortcode_atts(array(
            'veranstaltung_id' => 0,
            'verein_id' => 0,
            'view' => 'kachel',
        ), $atts, 'dienstplan_veranstaltung');
        
        $veranstaltung_id = intval($atts['veranstaltung_id']);
        $verein_id = intval($atts['verein_id']);
        
        if ($veranstaltung_id === 0) {
            return '<div class="dp-notice dp-notice-error"><p>Fehler: Keine Veranstaltungs-ID angegeben.</p></div>';
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $veranstaltung = $db->get_veranstaltung($veranstaltung_id);
        
        if (!$veranstaltung) {
            return '<div class="dp-notice dp-notice-error"><p>Veranstaltung nicht gefunden.</p></div>';
        }
        
        // Wenn Verein-ID angegeben, prüfen ob Verein zur Veranstaltung gehört
        if ($verein_id > 0) {
            $verein = $db->get_verein($verein_id);
            if (!$verein) {
                return '<div class="dp-notice dp-notice-error"><p>Verein nicht gefunden.</p></div>';
            }
            
            // Prüfe ob Verein der Veranstaltung zugeordnet ist
            $veranstaltung_vereine = $db->get_veranstaltung_vereine($veranstaltung_id);
            $verein_ids = array_map(function($v) { return intval($v->verein_id); }, $veranstaltung_vereine);
            if (!in_array($verein_id, $verein_ids)) {
                return '<div class="dp-notice dp-notice-error"><p>Dieser Verein ist nicht an dieser Veranstaltung beteiligt.</p></div>';
            }
        } else {
            $verein = null;
        }
        
        ob_start();
        include DIENSTPLAN_PLUGIN_PATH . 'public/templates/veranstaltung-verein.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [profil_bearbeiten]
     * Profil-Bearbeitung für eingeloggte Benutzer
     *
     * @since    0.7.0
     * @param    array     $atts    Shortcode-Attribute
     * @return   string    HTML-Output
     */
    public function shortcode_profil_bearbeiten($atts) {
        if (!is_user_logged_in()) {
            return '<div class="dp-notice dp-notice-warning"><p>Bitte melden Sie sich an, um Ihr Profil zu bearbeiten.</p></div>';
        }
        
        ob_start();
        include DIENSTPLAN_PLUGIN_PATH . 'public/templates/profil-bearbeiten.php';
        return ob_get_clean();
    }
    
    /**
     * AJAX: Mitarbeiter für Slot eintragen (öffentlich, ohne Authentifizierung)
     *
     * @since    1.0.0
     */
    public function ajax_assign_slot() {
        // Verhindere jeglichen Output vor JSON
        if (!defined('DOING_AJAX')) {
            define('DOING_AJAX', true);
        }
        
        // Clean output buffer
        if (ob_get_level()) {
            ob_clean();
        }
        
        try {
            // Nonce-Validierung für Public-AJAX (auch für nopriv)
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dp_public_nonce')) {
                wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen'));
                return;
            }
            
            // Validierung Pflichtfelder
            $required = array('slot_id', 'vorname', 'nachname', 'datenschutz');
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    wp_send_json_error(array('message' => "Feld '$field' ist erforderlich"));
                    return;
                }
            }
            
            if ($_POST['datenschutz'] !== '1') {
                wp_send_json_error(array('message' => 'Bitte akzeptieren Sie die Datenschutzerklärung'));
                return;
            }
            
            require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
            $db = new Dienstplan_Database($this->db_prefix);
            
            // Prüfe ob Mitarbeiter bereits existiert (per Email)
            $mitarbeiter_id = null;
            if (!empty($_POST['email'])) {
                $existing = $db->get_mitarbeiter_by_email(sanitize_email($_POST['email']));
                if ($existing) {
                    $mitarbeiter_id = $existing->id;
                }
            }
            
            // Neuen Mitarbeiter anlegen falls nicht vorhanden
            if (!$mitarbeiter_id) {
                $mitarbeiter_data = array(
                    'vorname' => sanitize_text_field($_POST['vorname']),
                    'nachname' => sanitize_text_field($_POST['nachname']),
                    'email' => !empty($_POST['email']) ? sanitize_email($_POST['email']) : null,
                    'telefon' => !empty($_POST['telefon']) ? sanitize_text_field($_POST['telefon']) : null,
                    'datenschutz_akzeptiert' => 1
                );
                
                $mitarbeiter_id = $db->add_mitarbeiter($mitarbeiter_data);
                
                if (!$mitarbeiter_id) {
                    wp_send_json_error(array('message' => 'Fehler beim Anlegen des Mitarbeiters'));
                    return;
                }
            }
            
            // Slot zuweisen
            $result = $db->assign_mitarbeiter_to_slot(intval($_POST['slot_id']), $mitarbeiter_id);
            
            if (is_array($result) && isset($result['error'])) {
                wp_send_json_error(array('message' => $result['message']));
                return;
            }
            
            if ($result === false) {
                wp_send_json_error(array('message' => 'Fehler beim Zuweisen des Slots'));
                return;
            }

            // Optional: Direkt bei Dienst-Eintragung Portal-User anlegen/verknüpfen
            $mitarbeiter = $db->get_mitarbeiter($mitarbeiter_id);
            $email_for_user = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
            $wpdb = $db->get_wpdb();
            $prefix = $db->get_prefix();
            $dienst_for_slot = $wpdb->get_row($wpdb->prepare(
                "SELECT d.verein_id
                 FROM {$prefix}dienst_slots s
                 INNER JOIN {$prefix}dienste d ON s.dienst_id = d.id
                 WHERE s.id = %d",
                intval($_POST['slot_id'])
            ));
            $verein_id = ($dienst_for_slot && !empty($dienst_for_slot->verein_id)) ? intval($dienst_for_slot->verein_id) : 0;
            $this->ensure_portal_user_for_mitarbeiter($db, $mitarbeiter, $email_for_user, $verein_id);
            
            // Speichere Mitarbeiter-ID in Transient statt Session
            // Verwende Cookie für non-logged-in User
            $transient_key = 'dp_mitarbeiter_' . md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
            set_transient($transient_key, $mitarbeiter_id, WEEK_IN_SECONDS);
            
            // Setze Cookie als Fallback
            setcookie('dp_mitarbeiter_id', $mitarbeiter_id, time() + WEEK_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            
            wp_send_json_success(array(
                'message' => 'Sie wurden erfolgreich eingetragen!',
                'mitarbeiter_id' => $mitarbeiter_id
            ));
            exit; // Wichtig: Verhindere weiteren Output
            
        } catch (Exception $e) {
            error_log('DP AJAX Error in ajax_assign_slot: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Fehler: ' . $e->getMessage()));
            exit;
        }
        
        // Fallback falls nichts zurückgegeben wurde
        wp_send_json_error(array('message' => 'Unbekannter Fehler'));
        exit;
    }
    
    /**
     * AJAX: Dienst-Anmeldung (Frontend-Formular)
     * Legt Mitarbeiter an und weist ihn einem Dienst zu
     *
     * @since    1.0.0
     */
    public function ajax_register_service() {
        // Verhindere jeglichen Output vor JSON
        if (!defined('DOING_AJAX')) {
            define('DOING_AJAX', true);
        }
        
        // Clean output buffer
        if (ob_get_level()) {
            ob_clean();
        }
        
        try {
            // Nonce-Validierung
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dp_public_nonce')) {
                wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen'));
                return;
            }
            
            // Validierung Pflichtfelder
            $required = array('dienst_id', 'first_name', 'last_name');
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    wp_send_json_error(array('message' => "Feld '$field' ist erforderlich"));
                    return;
                }
            }
            
            require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
            $db = new Dienstplan_Database($this->db_prefix);
            
            $dienst_id = intval($_POST['dienst_id']);
            
            // Debug-Logging
            error_log('DP: Anmeldung für Dienst-ID: ' . $dienst_id);
            error_log('DP: POST-Daten: ' . print_r($_POST, true));
            
            $dienst = $db->get_dienst($dienst_id);
            
            if (!$dienst) {
                error_log('DP: Dienst nicht gefunden für ID: ' . $dienst_id);
                wp_send_json_error(array('message' => 'Dienst nicht gefunden (ID: ' . $dienst_id . ')'));
                return;
            }
            
            // Prüfe ob Mitarbeiter bereits existiert (per Email)
            $email = !empty($_POST['email']) ? sanitize_email($_POST['email']) : null;
            $mitarbeiter_id = null;
            
            if ($email) {
                $existing = $db->get_mitarbeiter_by_email($email);
                
                if ($existing) {
                    $mitarbeiter_id = $existing->id;
                } else {
                    // Neuen Mitarbeiter anlegen
                    $mitarbeiter_data = array(
                        'vorname' => sanitize_text_field($_POST['first_name']),
                        'nachname' => sanitize_text_field($_POST['last_name']),
                        'email' => $email,
                        'telefon' => !empty($_POST['phone']) ? sanitize_text_field($_POST['phone']) : null,
                        'datenschutz_akzeptiert' => 1
                    );
                    
                    $mitarbeiter_id = $db->add_mitarbeiter($mitarbeiter_data);
                    
                    if (!$mitarbeiter_id) {
                        wp_send_json_error(array('message' => 'Fehler beim Anlegen des Crewmitglieds'));
                        return;
                    }
                }
            } else {
                // Keine Email: Erstelle temporären Mitarbeiter mit generierter Email
                $temp_email = 'temp_' . time() . '_' . uniqid() . '@dienstplan.local';
                $mitarbeiter_data = array(
                    'vorname' => sanitize_text_field($_POST['first_name']),
                    'nachname' => sanitize_text_field($_POST['last_name']),
                    'email' => $temp_email,
                    'telefon' => !empty($_POST['phone']) ? sanitize_text_field($_POST['phone']) : null,
                    'datenschutz_akzeptiert' => 1
                );
                
                $mitarbeiter_id = $db->add_mitarbeiter($mitarbeiter_data);
                
                if (!$mitarbeiter_id) {
                    wp_send_json_error(array('message' => 'Fehler beim Anlegen des Crewmitglieds'));
                    return;
                }
            }
            
            // Prüfe ob Dienst geteilt werden soll (Split)
            $split_dienst = isset($_POST['split_dienst']) && $_POST['split_dienst'] === 'on';
            $gewaehlter_slot = 1; // Default: Slot 1
            
            if ($split_dienst) {
                if (empty($_POST['dienst_teil'])) {
                    wp_send_json_error(array('message' => 'Bitte wählen Sie einen Teil des Dienstes aus'));
                    return;
                }
                $gewaehlter_slot = intval($_POST['dienst_teil']); // 1 oder 2
                
                // Dienst splitten: Prüfe ob bereits gesplittet, wenn nicht -> Split durchführen
                $split_result = $this->ensure_dienst_split($db, $dienst_id);
                
                if (isset($split_result['error'])) {
                    wp_send_json_error(array('message' => $split_result['message']));
                    return;
                }
            }
            
            // Zuweisung zum Dienst (dienst_zuweisungen Tabelle)
            $zuweisung_data = array(
                'dienst_id' => $dienst_id,
                'mitarbeiter_id' => $mitarbeiter_id,
                'status' => 'bestaetigt',
                'eingetragen_am' => current_time('mysql')
            );
            
            // Prüfe ob bereits zugewiesen
            $wpdb = $db->get_wpdb();
            $prefix = $db->get_prefix();
            
            $existing_assignment = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$prefix}dienst_zuweisungen WHERE dienst_id = %d AND mitarbeiter_id = %d",
                $dienst_id,
                $mitarbeiter_id
            ));
            
            if ($existing_assignment) {
                wp_send_json_error(array('message' => 'Sie sind bereits für diesen Dienst angemeldet'));
                return;
            }
            
            // Finde einen freien Slot
            // Bei Split: Finde den gewählten Slot (1 oder 2)
            // Bei normalem Dienst: Finde irgendeinen freien Slot
            if ($split_dienst) {
                $free_slot = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$prefix}dienst_slots 
                     WHERE dienst_id = %d 
                     AND slot_nummer = %d 
                     AND mitarbeiter_id IS NULL 
                     LIMIT 1",
                    $dienst_id,
                    $gewaehlter_slot
                ));
                
                if (!$free_slot) {
                    $teil_name = ($gewaehlter_slot === 1) ? 'erste' : 'zweite';
                    wp_send_json_error(array('message' => 'Die ' . $teil_name . ' Hälfte des Dienstes ist bereits besetzt'));
                    return;
                }
            } else {
                $free_slot = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$prefix}dienst_slots WHERE dienst_id = %d AND mitarbeiter_id IS NULL ORDER BY slot_nummer ASC LIMIT 1",
                    $dienst_id
                ));
                
                if (!$free_slot) {
                    wp_send_json_error(array('message' => 'Dieser Dienst ist bereits voll besetzt'));
                    return;
                }
            }
            
            // Aktualisiere Slot mit Mitarbeiter
            $slot_update = $wpdb->update(
                $prefix . 'dienst_slots',
                array(
                    'mitarbeiter_id' => $mitarbeiter_id,
                    'status' => 'besetzt'
                ),
                array('id' => $free_slot->id),
                array('%d', '%s'),
                array('%d')
            );
            
            if ($slot_update === false) {
                wp_send_json_error(array('message' => 'Fehler beim Zuweisen des Slots'));
                return;
            }

            // Optional: Direkt bei Dienst-Eintragung Portal-User anlegen/verknüpfen
            $mitarbeiter = $db->get_mitarbeiter($mitarbeiter_id);
            $this->ensure_portal_user_for_mitarbeiter($db, $mitarbeiter, $email, intval($dienst->verein_id));
            
            // Speichere zusätzlich in dienst_zuweisungen für History
            $zuweisung_data['slot_id'] = $free_slot->id;
            $result = $wpdb->insert(
                $prefix . 'dienst_zuweisungen',
                $zuweisung_data,
                array('%d', '%d', '%d', '%s', '%s')
            );
            
            if ($result === false) {
                // Rollback: Slot wieder freigeben
                $wpdb->update(
                    $prefix . 'dienst_slots',
                    array('mitarbeiter_id' => null, 'status' => 'offen'),
                    array('id' => $free_slot->id)
                );
                wp_send_json_error(array('message' => 'Fehler beim Erstellen der Zuweisung'));
                return;
            }
            
            // Speichere Mitarbeiter-ID in Transient und Cookie
            $transient_key = 'dp_mitarbeiter_' . md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
            set_transient($transient_key, $mitarbeiter_id, WEEK_IN_SECONDS);
            setcookie('dp_mitarbeiter_id', $mitarbeiter_id, time() + WEEK_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            
            $message = 'Sie wurden erfolgreich für den Dienst angemeldet!';
            if ($split_dienst) {
                $message = 'Der Dienst wurde geteilt und Sie wurden für Teil ' . $dienst_teil . ' angemeldet!';
            }
            
            wp_send_json_success(array(
                'message' => $message,
                'mitarbeiter_id' => $mitarbeiter_id,
                'split' => $split_dienst,
                'teil' => $dienst_teil
            ));
            exit;
            
        } catch (Exception $e) {
            error_log('DP AJAX Error in ajax_register_service: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Fehler: ' . $e->getMessage()));
            exit;
        }
        
        // Fallback
        wp_send_json_error(array('message' => 'Unbekannter Fehler'));
        exit;
    }
    
    /**
     * AJAX: Mitarbeiter von Slot entfernen
     *
     * @since    1.0.0
     */
    public function ajax_remove_assignment() {
        try {
            // Nonce-Validierung
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dp_public_nonce')) {
                wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen'));
                return;
            }
            
            if (empty($_POST['slot_id'])) {
                wp_send_json_error(array('message' => 'Slot-ID fehlt'));
                return;
            }
            
            $mitarbeiter_id = $this->get_current_mitarbeiter_id();
            if (!$mitarbeiter_id) {
                wp_send_json_error(array('message' => 'Sie sind nicht angemeldet'));
                return;
            }
            
            require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
            $db = new Dienstplan_Database($this->db_prefix);
            
            // Prüfe ob Slot dem Mitarbeiter gehört
            $slot = $db->get_slot(intval($_POST['slot_id']));
            
            if (!$slot || $slot->mitarbeiter_id != $mitarbeiter_id) {
                wp_send_json_error(array('message' => 'Dieser Slot gehört nicht zu Ihnen'));
                return;
            }
            
            $result = $db->remove_mitarbeiter_from_slot(intval($_POST['slot_id']));
            
            if ($result === false) {
                wp_send_json_error(array('message' => 'Fehler beim Entfernen der Zuweisung'));
                return;
            }
            
            wp_send_json_success(array('message' => 'Sie wurden erfolgreich ausgetragen'));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Fehler: ' . $e->getMessage()));
        }
    }

    /**
     * AJAX: Admin entfernt eine beliebige Slot-Zuweisung im Frontend
     * (für Admin, Vereinsadmin, Veranstaltungsadmin)
     *
     * @since 1.0.0
     */
    public function ajax_frontend_admin_remove_slot() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dp_public_nonce')) {
                wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen'));
                return;
            }

            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => 'Nicht angemeldet'));
                return;
            }

            if (!current_user_can('manage_options') && !Dienstplan_Roles::can_manage_events() && !Dienstplan_Roles::can_manage_clubs()) {
                wp_send_json_error(array('message' => 'Keine Berechtigung'));
                return;
            }

            $slot_id = isset($_POST['slot_id']) ? intval($_POST['slot_id']) : 0;
            if ($slot_id <= 0) {
                wp_send_json_error(array('message' => 'Slot-ID fehlt'));
                return;
            }

            require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
            $db = new Dienstplan_Database($this->db_prefix);
            $slot = $db->get_slot($slot_id);

            if (!$slot) {
                wp_send_json_error(array('message' => 'Slot nicht gefunden'));
                return;
            }

            $result = $db->remove_mitarbeiter_from_slot($slot_id);

            if ($result === false) {
                wp_send_json_error(array('message' => 'Fehler beim Entfernen der Zuweisung'));
                return;
            }

            wp_send_json_success(array('message' => 'Zuweisung wurde entfernt'));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Fehler: ' . $e->getMessage()));
        }
    }

    /**
     * AJAX: Admin splittet Dienst direkt im Frontend
     * (für Admin, Vereinsadmin, Veranstaltungsadmin)
     *
     * @since 1.0.0
     */
    public function ajax_frontend_admin_split_dienst() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dp_public_nonce')) {
                wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen'));
                return;
            }

            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => 'Nicht angemeldet'));
                return;
            }

            if (!current_user_can('manage_options') && !Dienstplan_Roles::can_manage_events() && !Dienstplan_Roles::can_manage_clubs()) {
                wp_send_json_error(array('message' => 'Keine Berechtigung'));
                return;
            }

            $dienst_id = isset($_POST['dienst_id']) ? intval($_POST['dienst_id']) : 0;
            if ($dienst_id <= 0) {
                wp_send_json_error(array('message' => 'Dienst-ID fehlt'));
                return;
            }

            require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
            $db = new Dienstplan_Database($this->db_prefix);
            $split_result = $this->ensure_dienst_split($db, $dienst_id);

            if (isset($split_result['error']) && $split_result['error']) {
                wp_send_json_error(array('message' => $split_result['message'] ?? 'Split fehlgeschlagen'));
                return;
            }

            wp_send_json_success(array('message' => $split_result['message'] ?? 'Dienst erfolgreich gesplittet'));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Fehler: ' . $e->getMessage()));
        }
    }
    
    /**
     * Hole aktuelle Mitarbeiter-ID aus Transient/Cookie oder GET-Parameter
     * Session-free implementation für bessere Skalierbarkeit
     *
     * @since    1.0.0
     * @return   int|null    Mitarbeiter-ID oder null
     */
    private function get_current_mitarbeiter_id() {
        // Primär: eingeloggter Benutzer via user_id Verknüpfung
        if (is_user_logged_in()) {
            $current_user_id = get_current_user_id();
            if ($current_user_id > 0) {
                require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
                $db = new Dienstplan_Database($this->db_prefix);
                $wpdb = $db->get_wpdb();
                $mitarbeiter_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}{$this->db_prefix}mitarbeiter WHERE user_id = %d LIMIT 1",
                    $current_user_id
                ));
                if (!empty($mitarbeiter_id)) {
                    return intval($mitarbeiter_id);
                }
            }
        }

        // Aus Transient (für eindeutige Benutzer-Identifikation)
        $transient_key = 'dp_mitarbeiter_' . md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
        $mitarbeiter_id = get_transient($transient_key);
        
        if ($mitarbeiter_id) {
            return intval($mitarbeiter_id);
        }
        
        // Aus Cookie (als Fallback)
        if (!empty($_COOKIE['dp_mitarbeiter_id'])) {
            return intval($_COOKIE['dp_mitarbeiter_id']);
        }
        
        // Aus Query-Parameter (für Email-Links)
        if (isset($_GET['dp_email'])) {
            require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
            $db = new Dienstplan_Database($this->db_prefix);
            $mitarbeiter = $db->get_mitarbeiter_by_email(sanitize_email($_GET['dp_email']));
            if ($mitarbeiter) {
                // Speichere in Transient und Cookie
                set_transient($transient_key, $mitarbeiter->id, WEEK_IN_SECONDS);
                setcookie('dp_mitarbeiter_id', $mitarbeiter->id, time() + WEEK_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
                return $mitarbeiter->id;
            }
        }
        
        return null;
    }

    /**
     * Stellt sicher, dass für einen Mitarbeiter ein Portal-User existiert
     * und ordnet den User direkt einem Verein zu.
     *
     * @param Dienstplan_Database $db
     * @param object|null $mitarbeiter
     * @param string $email
     * @param int $verein_id
     * @return int User-ID oder 0
     */
    private function ensure_portal_user_for_mitarbeiter($db, $mitarbeiter, $email = '', $verein_id = 0) {
        if (empty($mitarbeiter) || empty($mitarbeiter->id)) {
            return 0;
        }

        $email = !empty($email) ? sanitize_email($email) : (!empty($mitarbeiter->email) ? sanitize_email($mitarbeiter->email) : '');

        if (empty($email) || !is_email($email) || strpos($email, '@dienstplan.local') !== false) {
            return 0;
        }

        $user_id = !empty($mitarbeiter->user_id) ? intval($mitarbeiter->user_id) : 0;

        if ($user_id > 0 && get_userdata($user_id)) {
            if ($verein_id > 0) {
                $db->assign_user_to_verein($user_id, $verein_id, intval($mitarbeiter->id));
            }
            return $user_id;
        }

        $existing_wp_user = get_user_by('email', $email);

        if ($existing_wp_user) {
            $user_id = intval($existing_wp_user->ID);
        } else {
            if (!class_exists('Dienstplan_Roles')) {
                require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-dienstplan-roles.php';
            }

            $username_base = sanitize_user(strtolower($mitarbeiter->vorname . '_' . $mitarbeiter->nachname), true);
            if (empty($username_base)) {
                $username_base = 'crew_' . intval($mitarbeiter->id);
            }

            $username = $username_base;
            $counter = 1;
            while (username_exists($username)) {
                $username = $username_base . '_' . $counter;
                $counter++;
            }

            $password = wp_generate_password(12, true, true);
            $user_id = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id) || empty($user_id)) {
                return 0;
            }

            $user = new WP_User($user_id);
            $user->set_role(Dienstplan_Roles::ROLE_CREW);

            update_user_meta($user_id, 'first_name', $mitarbeiter->vorname);
            update_user_meta($user_id, 'last_name', $mitarbeiter->nachname);
            update_user_meta($user_id, 'show_admin_bar_front', false);

            $portal_page_id = get_option('dienstplan_portal_page_id', 0);
            $login_url = $portal_page_id ? get_permalink($portal_page_id) : wp_login_url();
            $subject = sprintf(__('[%s] Zugang zum Dienstplan-Portal', 'dienstplan-verwaltung'), get_bloginfo('name'));
            $body = sprintf(
                __("Hallo %s,\n\nfür dich wurde automatisch ein Zugang zum Dienstplan-Portal erstellt.\n\nBenutzername: %s\nPasswort: %s\n\nPortal-Link: %s\n\nBitte ändere dein Passwort nach dem ersten Login.\n\nViele Grüße\n%s", 'dienstplan-verwaltung'),
                $mitarbeiter->vorname,
                $username,
                $password,
                $login_url,
                get_bloginfo('name')
            );
            wp_mail($email, $subject, $body);
        }

        $db->update_mitarbeiter(intval($mitarbeiter->id), array('user_id' => intval($user_id)));

        if ($verein_id > 0) {
            $db->assign_user_to_verein(intval($user_id), intval($verein_id), intval($mitarbeiter->id));
        }

        return intval($user_id);
    }
    
    /**
     * Stellt sicher, dass ein Dienst für Split vorbereitet ist (2 Slots)
     * Erstellt KEINE neuen Dienste, sondern passt nur Slots an!
     *
     * @since    1.0.0
     * @param    object    $db           Database-Objekt
     * @param    int       $dienst_id    Dienst-ID
     * @return   array     Success oder Error
     */
    private function ensure_dienst_split($db, $dienst_id) {
        $wpdb = $db->get_wpdb();
        $prefix = $db->get_prefix();
        
        // Hole Dienst-Daten
        $dienst = $db->get_dienst($dienst_id);
        if (!$dienst) {
            return array('error' => true, 'message' => 'Dienst nicht gefunden');
        }
        
        // Hole existierende Slots
        $slots = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$prefix}dienst_slots WHERE dienst_id = %d ORDER BY slot_nummer ASC",
            $dienst_id
        ));
        
        // Wenn bereits 2 Slots existieren, ist der Dienst bereits gesplittet
        if (count($slots) >= 2) {
            return array('success' => true, 'message' => 'Dienst ist bereits gesplittet');
        }
        
        // Hole Tag-Datum für Zeitberechnung
        $tag = $wpdb->get_row($wpdb->prepare(
            "SELECT tag_datum FROM {$prefix}veranstaltung_tage WHERE id = %d",
            $dienst->tag_id
        ));
        
        if (!$tag) {
            return array('error' => true, 'message' => 'Tag-Datum nicht gefunden');
        }
        
        $start_datum = $tag->tag_datum;
        $von_zeit = $dienst->von_zeit;
        $bis_zeit = $dienst->bis_zeit;
        $bis_datum = $dienst->bis_datum ?: $start_datum;
        
        // Berechne Mitte-Zeit
        $von_timestamp = strtotime($start_datum . ' ' . $von_zeit);
        $bis_timestamp = strtotime($bis_datum . ' ' . $bis_zeit);
        $middle_timestamp = $von_timestamp + (($bis_timestamp - $von_timestamp) / 2);
        $middle_zeit = date('H:i:s', $middle_timestamp);
        $middle_datum = date('Y-m-d', $middle_timestamp);
        
        // Wenn nur 1 Slot existiert: Passe ihn an und erstelle den 2. Slot
        if (count($slots) === 1) {
            $slot1 = $slots[0];
            
            // Update Slot 1: Erste Hälfte
            $wpdb->update(
                $prefix . 'dienst_slots',
                array(
                    'von_zeit' => $von_zeit,
                    'bis_zeit' => $middle_zeit,
                    'bis_datum' => ($middle_datum !== $start_datum) ? $middle_datum : null
                ),
                array('id' => $slot1->id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            
            // Erstelle Slot 2: Zweite Hälfte
            $wpdb->insert(
                $prefix . 'dienst_slots',
                array(
                    'dienst_id' => $dienst_id,
                    'slot_nummer' => 2,
                    'von_zeit' => $middle_zeit,
                    'bis_zeit' => $bis_zeit,
                    'bis_datum' => $bis_datum !== $start_datum ? $bis_datum : null,
                    'status' => 'offen',
                    'mitarbeiter_id' => null
                ),
                array('%d', '%d', '%s', '%s', '%s', '%s', '%d')
            );
            
        } else {
            // Keine Slots vorhanden: Erstelle beide Slots
            // Slot 1: Erste Hälfte
            $wpdb->insert(
                $prefix . 'dienst_slots',
                array(
                    'dienst_id' => $dienst_id,
                    'slot_nummer' => 1,
                    'von_zeit' => $von_zeit,
                    'bis_zeit' => $middle_zeit,
                    'bis_datum' => ($middle_datum !== $start_datum) ? $middle_datum : null,
                    'status' => 'offen',
                    'mitarbeiter_id' => null
                ),
                array('%d', '%d', '%s', '%s', '%s', '%s', '%d')
            );
            
            // Slot 2: Zweite Hälfte
            $wpdb->insert(
                $prefix . 'dienst_slots',
                array(
                    'dienst_id' => $dienst_id,
                    'slot_nummer' => 2,
                    'von_zeit' => $middle_zeit,
                    'bis_zeit' => $bis_zeit,
                    'bis_datum' => $bis_datum !== $start_datum ? $bis_datum : null,
                    'status' => 'offen',
                    'mitarbeiter_id' => null
                ),
                array('%d', '%d', '%s', '%s', '%s', '%s', '%d')
            );
        }
        
        return array('success' => true, 'message' => 'Dienst erfolgreich gesplittet');
    }
    
    /**
     * AJAX: Verein-spezifische Anmeldung für Dienst-Slots
     * Für nicht-eingeloggte Benutzer auf verein-spezifischen Seiten
     *
     * @since    0.6.6
     */
    public function ajax_anmeldung_verein() {
        global $wpdb;
        $prefix = $wpdb->prefix . $this->db_prefix;
        
        // Nonce-Prüfung (kompatibel: alter und neuer Nonce-Name)
        $request_nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        $nonce_valid = !empty($request_nonce)
            && (
                wp_verify_nonce($request_nonce, 'dp_public_nonce')
                || wp_verify_nonce($request_nonce, 'dienstplan_public_nonce')
            );

        if (!$nonce_valid) {
            wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen.'));
            return;
        }
        
        // Parameter validieren
        $slot_id = isset($_POST['slot_id']) ? intval($_POST['slot_id']) : 0;
        $dienst_id = isset($_POST['dienst_id']) ? intval($_POST['dienst_id']) : 0;
        $vorname = isset($_POST['vorname']) ? sanitize_text_field(wp_unslash($_POST['vorname'])) : '';
        $nachname = isset($_POST['nachname']) ? sanitize_text_field(wp_unslash($_POST['nachname'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $telefon = isset($_POST['telefon']) ? sanitize_text_field(wp_unslash($_POST['telefon'])) : '';
        $besonderheiten = isset($_POST['besonderheiten']) ? sanitize_textarea_field(wp_unslash($_POST['besonderheiten'])) : '';
        $create_user_account = isset($_POST['create_user_account']) && sanitize_text_field(wp_unslash($_POST['create_user_account'])) === '1';
        $create_user_datenschutz = isset($_POST['create_user_datenschutz']) && sanitize_text_field(wp_unslash($_POST['create_user_datenschutz'])) === '1';
        
        if (!$slot_id || !$dienst_id || !$vorname || !$nachname || !$email) {
            wp_send_json_error(array('message' => 'Bitte alle Pflichtfelder ausfüllen.'));
            return;
        }
        
        // E-Mail validieren
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Ungültige E-Mail-Adresse.'));
            return;
        }

        if ($create_user_account && !$create_user_datenschutz) {
            wp_send_json_error(array('message' => 'Bitte Datenschutzerklärung für die Kontoerstellung bestätigen.'));
            return;
        }
        
        // Prüfen, ob Slot existiert und frei ist
        $slot = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}dienst_slots WHERE id = %d AND dienst_id = %d",
            $slot_id,
            $dienst_id
        ));
        
        if (!$slot) {
            wp_send_json_error(array('message' => 'Dienst-Slot nicht gefunden.'));
            return;
        }
        
        if (!empty($slot->mitarbeiter_id)) {
            wp_send_json_error(array('message' => 'Dieser Platz ist bereits belegt.'));
            return;
        }
        
        // Veranstaltungs-Status prüfen - nur bei Status 'geplant' sind Anmeldungen möglich
        $dienst = $wpdb->get_row($wpdb->prepare(
            "SELECT d.*, v.status as veranstaltung_status 
             FROM {$prefix}dienste d
             INNER JOIN {$prefix}veranstaltungen v ON d.veranstaltung_id = v.id
             WHERE d.id = %d",
            $dienst_id
        ));
        
        if (!$dienst) {
            wp_send_json_error(array('message' => 'Dienst nicht gefunden.'));
            return;
        }
        
        if ($dienst->veranstaltung_status !== 'geplant') {
            $status_messages = array(
                'in_planung' => 'Anmeldungen sind noch nicht möglich. Die Veranstaltung befindet sich noch in Planung.',
                'aktiv' => 'Anmeldungen sind nicht mehr möglich. Die Veranstaltung läuft bereits.',
                'abgeschlossen' => 'Anmeldungen sind nicht mehr möglich. Die Veranstaltung ist bereits abgeschlossen.'
            );
            $message = isset($status_messages[$dienst->veranstaltung_status]) 
                ? $status_messages[$dienst->veranstaltung_status] 
                : 'Anmeldungen sind für diese Veranstaltung nicht möglich.';
            wp_send_json_error(array('message' => $message));
            return;
        }
        
        // Mitarbeiter erstellen oder finden
        $existing_mitarbeiter = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$prefix}mitarbeiter WHERE email = %s",
            $email
        ));
        
        if ($existing_mitarbeiter) {
            $mitarbeiter_id = $existing_mitarbeiter->id;
        } else {
            // Neuen Mitarbeiter anlegen (schema-sicher: nur vorhandene Spalten)
            $mitarbeiter_columns = $wpdb->get_col("SHOW COLUMNS FROM {$prefix}mitarbeiter", 0);
            $insert_data = array(
                'vorname' => $vorname,
                'nachname' => $nachname,
                'email' => $email,
                'telefon' => $telefon,
            );

            if (in_array('datenschutz_akzeptiert', $mitarbeiter_columns, true)) {
                $insert_data['datenschutz_akzeptiert'] = $create_user_account && $create_user_datenschutz ? 1 : 0;
            }

            if (in_array('notizen', $mitarbeiter_columns, true)) {
                $insert_data['notizen'] = $besonderheiten;
            }

            if (in_array('created_at', $mitarbeiter_columns, true)) {
                $insert_data['created_at'] = current_time('mysql');
            }

            if (in_array('updated_at', $mitarbeiter_columns, true)) {
                $insert_data['updated_at'] = current_time('mysql');
            }

            $insert_format = array();
            foreach ($insert_data as $column => $value) {
                $insert_format[] = $column === 'datenschutz_akzeptiert' ? '%d' : '%s';
            }

            $wpdb->insert($prefix . 'mitarbeiter', $insert_data, $insert_format);
            
            $mitarbeiter_id = $wpdb->insert_id;
            
            if (!$mitarbeiter_id) {
                wp_send_json_error(array('message' => 'Fehler beim Anlegen des Mitarbeiterprofils.'));
                return;
            }
        }
        
        // Slot zuweisen
        $update_result = $wpdb->update(
            $prefix . 'dienst_slots',
            array(
                'mitarbeiter_id' => $mitarbeiter_id,
                'status' => 'besetzt'
            ),
            array('id' => $slot_id),
            array('%d', '%s'),
            array('%d')
        );
        
        if ($update_result === false) {
            wp_send_json_error(array('message' => 'Fehler bei der Zuweisung.'));
            return;
        }
        
        // Bestätigungs-E-Mail senden
        $dienst = $wpdb->get_row($wpdb->prepare(
            "SELECT d.*, v.titel as veranstaltung, ve.name as verein, t.name as taetigkeit, b.name as bereich
             FROM {$prefix}dienste d
             LEFT JOIN {$prefix}veranstaltungen v ON d.veranstaltung_id = v.id
             LEFT JOIN {$prefix}vereine ve ON d.verein_id = ve.id
             LEFT JOIN {$prefix}taetigkeiten t ON d.taetigkeit_id = t.id
             LEFT JOIN {$prefix}bereiche b ON d.bereich_id = b.id
             WHERE d.id = %d",
            $dienst_id
        ));

        // Optional: Nur bei expliziter Auswahl Portal-User anlegen/verknüpfen
        if ($create_user_account) {
            require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
            $db = new Dienstplan_Database($this->db_prefix);
            $mitarbeiter = $db->get_mitarbeiter($mitarbeiter_id);
            $verein_id = !empty($dienst->verein_id) ? intval($dienst->verein_id) : 0;
            $this->ensure_portal_user_for_mitarbeiter($db, $mitarbeiter, $email, $verein_id);
        }
        
        if ($dienst) {
            $tag = $wpdb->get_row($wpdb->prepare(
                "SELECT datum FROM {$prefix}veranstaltung_tage WHERE id = %d",
                $dienst->tag_id
            ));
            
            $tag_datum = $tag ? date_i18n('d.m.Y', strtotime($tag->datum)) : 'N/A';
            
            $to = $email;
            $subject = 'Bestätigung Ihrer Anmeldung - ' . $dienst->veranstaltung;
            $message = "Hallo {$vorname} {$nachname},\n\n";
            $message .= "vielen Dank für Ihre Anmeldung!\n\n";
            $message .= "Details zu Ihrem Dienst:\n";
            $message .= "Veranstaltung: {$dienst->veranstaltung}\n";
            $message .= "Verein: {$dienst->verein}\n";
            $message .= "Datum: {$tag_datum}\n";
            $message .= "Uhrzeit: " . substr($slot->von_zeit, 0, 5) . " - " . substr($slot->bis_zeit, 0, 5) . " Uhr\n";
            $message .= "Tätigkeit: {$dienst->taetigkeit}\n";
            $message .= "Bereich: {$dienst->bereich}\n\n";
            
            if ($dienst->beschreibung) {
                $message .= "Beschreibung: {$dienst->beschreibung}\n\n";
            }
            
            $message .= "Bei Fragen oder Änderungen wenden Sie sich bitte an den Veranstalter.\n\n";
            $message .= "Mit freundlichen Grüßen\n";
            $message .= "Ihr Dienstplan-Team";
            
            wp_mail($to, $subject, $message);
        }
        
        wp_send_json_success(array(
            'message' => 'Anmeldung erfolgreich! Sie erhalten in Kürze eine Bestätigungs-E-Mail.',
            'mitarbeiter_id' => $mitarbeiter_id
        ));
    }
}

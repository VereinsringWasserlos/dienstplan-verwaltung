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

        // AJAX-Daten für JavaScript
        wp_localize_script('dp-public-scripts', 'dpPublic', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dp_public_nonce'),
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
        add_shortcode('dienstplan_vereine', array($this, 'shortcode_vereine'));
        add_shortcode('dienstplan_veranstaltungen', array($this, 'shortcode_veranstaltungen'));
        add_shortcode('meine_dienste', array($this, 'shortcode_meine_dienste'));
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
            'show_filter' => 'true',
            'view' => isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'compact', // 'compact', 'calendar'
        ), $atts, 'dienstplan');

        ob_start();
        
        if ($atts['veranstaltung_id'] > 0) {
            // Detail-Ansicht einer Veranstaltung - NEUE KOMPAKTE VERSION
            include DIENSTPLAN_PLUGIN_PATH . 'public/templates/veranstaltung-compact.php';
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
        ), $atts, 'dienstplan_vereine');

        ob_start();
        include DIENSTPLAN_PLUGIN_PATH . 'public/views/vereine-list.php';
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
            
            // Speichere Mitarbeiter-ID in Session für spätere Verwendung
            if (!session_id()) {
                session_start();
            }
            $_SESSION['dp_mitarbeiter_id'] = $mitarbeiter_id;
            
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
            
            // Speichere Mitarbeiter-ID in Session
            if (!session_id()) {
                session_start();
            }
            $_SESSION['dp_mitarbeiter_id'] = $mitarbeiter_id;
            
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
     * Hole aktuelle Mitarbeiter-ID aus Session oder GET-Parameter
     *
     * @since    1.0.0
     * @return   int|null    Mitarbeiter-ID oder null
     */
    private function get_current_mitarbeiter_id() {
        if (!session_id()) {
            session_start();
        }
        
        // Aus Session
        if (isset($_SESSION['dp_mitarbeiter_id'])) {
            return intval($_SESSION['dp_mitarbeiter_id']);
        }
        
        // Aus Query-Parameter (für Email-Links)
        if (isset($_GET['dp_email'])) {
            require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
            $db = new Dienstplan_Database($this->db_prefix);
            $mitarbeiter = $db->get_mitarbeiter_by_email(sanitize_email($_GET['dp_email']));
            if ($mitarbeiter) {
                $_SESSION['dp_mitarbeiter_id'] = $mitarbeiter->id;
                return $mitarbeiter->id;
            }
        }
        
        return null;
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
}

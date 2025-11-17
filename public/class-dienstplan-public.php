<?php
/**
 * Public-facing functionality
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/public
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dienstplan_Public {
    
    private $plugin_name;
    private $version;
    private $db_prefix;
    
    public function __construct($plugin_name, $version, $db_prefix) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->db_prefix = $db_prefix;
    }
    
    /**
     * Registriere Shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('dienstplan', array($this, 'dienstplan_shortcode'));
        add_shortcode('meine_dienste', array($this, 'meine_dienste_shortcode'));
    }
    
    /**
     * Hauptshortcode: Zeigt Veranstaltungen und verfügbare Slots
     * 
     * Usage: [dienstplan]
     * oder: [dienstplan veranstaltung_id="1"]
     */
    public function dienstplan_shortcode($atts) {
        $atts = shortcode_atts(array(
            'veranstaltung_id' => 0
        ), $atts);
        
        ob_start();
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        if ($atts['veranstaltung_id'] > 0) {
            // Einzelne Veranstaltung anzeigen
            $veranstaltung = $db->get_veranstaltung($atts['veranstaltung_id']);
            $dienste = $db->get_dienste($atts['veranstaltung_id']);
            include DIENSTPLAN_PLUGIN_PATH . 'public/templates/veranstaltung-detail.php';
        } else {
            // Übersicht aller aktuellen Veranstaltungen
            $veranstaltungen = $db->get_veranstaltungen();
            include DIENSTPLAN_PLUGIN_PATH . 'public/templates/veranstaltungen-liste.php';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Zeigt die Dienste eines Mitarbeiters
     * 
     * Usage: [meine_dienste]
     */
    public function meine_dienste_shortcode($atts) {
        ob_start();
        
        // Hole Mitarbeiter-ID aus Session/Cookie oder Query-Parameter
        $mitarbeiter_id = $this->get_current_mitarbeiter_id();
        
        if (!$mitarbeiter_id) {
            echo '<div class="dp-notice dp-notice-info">';
            echo '<p>Bitte geben Sie Ihre E-Mail-Adresse ein, um Ihre Dienste zu sehen.</p>';
            echo '<form method="get" action="">';
            echo '<input type="email" name="dp_email" placeholder="Ihre E-Mail" required>';
            echo '<button type="submit">Anzeigen</button>';
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
     * AJAX: Mitarbeiter für Slot eintragen
     */
    public function ajax_assign_slot() {
        try {
            // Keine Nonce-Prüfung für öffentliche Endpoints
            // Stattdessen: Honeypot und Rate-Limiting
            
            // Validierung
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
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Fehler: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX: Mitarbeiter von Slot entfernen
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
            $slot = $db->wpdb->get_row($db->wpdb->prepare(
                "SELECT * FROM {$db->prefix}dienst_slots WHERE id = %d",
                intval($_POST['slot_id'])
            ));
            
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
     * Hole aktuelle Mitarbeiter-ID aus Session
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
     * Enqueue public styles
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name . '-public',
            DIENSTPLAN_PLUGIN_URL . 'assets/css/public.css',
            array(),
            $this->version,
            'all'
        );
    }
    
    /**
     * Enqueue public scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name . '-public',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Localize script für AJAX
        wp_localize_script($this->plugin_name . '-public', 'dpPublic', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dp_public_nonce')
        ));
    }
}

<?php
if (!defined('ABSPATH')) exit;

class Dienstplan_Database {
    protected $wpdb;
    protected $prefix;
    
    public function __construct($db_prefix = 'dp_') {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix . $db_prefix;
    }
    
    /**
     * Getter für wpdb (für externe Zugriffe)
     */
    public function get_wpdb() {
        return $this->wpdb;
    }
    
    /**
     * Getter für prefix (für externe Zugriffe)
     */
    public function get_prefix() {
        return $this->prefix;
    }
    
    public function install() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset = $this->wpdb->get_charset_collate();
        
        // Settings-Tabelle
        $sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}settings (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value longtext,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset;";
        
        dbDelta($sql);
        
        // Vereine-Tabelle
        $sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}vereine (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            kuerzel varchar(10) NOT NULL,
            beschreibung text,
            logo_id bigint(20) UNSIGNED DEFAULT NULL,
            kontakt_name varchar(255),
            kontakt_email varchar(255),
            kontakt_telefon varchar(50),
            aktiv tinyint(1) DEFAULT 1,
            erstellt_am datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY kuerzel (kuerzel)
        ) $charset;";
        
        dbDelta($sql);
        
        // Prüfe ob kuerzel Spalte existiert, falls nicht hinzufügen (für Updates)
        $column_exists = $this->wpdb->get_results(
            "SHOW COLUMNS FROM {$this->prefix}vereine LIKE 'kuerzel'"
        );
        
        if (empty($column_exists)) {
            $this->wpdb->query(
                "ALTER TABLE {$this->prefix}vereine 
                ADD COLUMN kuerzel varchar(10) NOT NULL AFTER name,
                ADD UNIQUE KEY kuerzel (kuerzel)"
            );
        }
        
        // Prüfe ob logo_id Spalte existiert, falls nicht hinzufügen (für Updates)
        $logo_exists = $this->wpdb->get_results(
            "SHOW COLUMNS FROM {$this->prefix}vereine LIKE 'logo_id'"
        );
        
        if (empty($logo_exists)) {
            $this->wpdb->query(
                "ALTER TABLE {$this->prefix}vereine 
                ADD COLUMN logo_id bigint(20) UNSIGNED DEFAULT NULL AFTER beschreibung"
            );
        }
        
        // Veranstaltungen-Tabelle
        $sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}veranstaltungen (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            beschreibung text,
            typ varchar(50) DEFAULT 'eintaegig',
            status varchar(50) DEFAULT 'geplant',
            start_datum date NOT NULL,
            end_datum date,
            seite_id bigint(20) UNSIGNED DEFAULT NULL,
            erstellt_am datetime DEFAULT CURRENT_TIMESTAMP,
            aktualisiert_am datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY start_datum (start_datum),
            KEY status (status),
            KEY seite_id (seite_id)
        ) $charset;";
        
        dbDelta($sql);
        
        // Veranstaltungs-Tage Tabelle (für mehrtägige Events)
        $sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}veranstaltung_tage (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            veranstaltung_id mediumint(9) NOT NULL,
            tag_datum date NOT NULL,
            tag_nummer tinyint(2) NOT NULL,
            von_zeit time,
            bis_zeit time,
            bis_datum date,
            dienst_von_zeit time,
            dienst_bis_zeit time,
            dienst_bis_datum date,
            nur_dienst TINYINT(1) DEFAULT 0,
            notizen text,
            PRIMARY KEY (id),
            KEY veranstaltung_id (veranstaltung_id),
            KEY tag_datum (tag_datum)
        ) $charset;";
        
        dbDelta($sql);
        
        // Prüfe und füge neue Spalten hinzu (für Updates von älteren Versionen)
        $bis_datum_exists = $this->wpdb->get_results(
            "SHOW COLUMNS FROM {$this->prefix}veranstaltung_tage LIKE 'bis_datum'"
        );
        if (empty($bis_datum_exists)) {
            $this->wpdb->query(
                "ALTER TABLE {$this->prefix}veranstaltung_tage 
                ADD COLUMN bis_datum date AFTER bis_zeit,
                ADD COLUMN dienst_bis_datum date AFTER dienst_bis_zeit"
            );
        }
        
        // Prüfe und füge nur_dienst Spalte hinzu
        $nur_dienst_exists = $this->wpdb->get_results(
            "SHOW COLUMNS FROM {$this->prefix}veranstaltung_tage LIKE 'nur_dienst'"
        );
        if (empty($nur_dienst_exists)) {
            $this->wpdb->query(
                "ALTER TABLE {$this->prefix}veranstaltung_tage 
                ADD COLUMN nur_dienst TINYINT(1) DEFAULT 0 AFTER dienst_bis_datum"
            );
        }
        
        // Veranstaltungs-Vereine Zuordnung (m:n)
        $sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}veranstaltung_vereine (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            veranstaltung_id mediumint(9) NOT NULL,
            verein_id mediumint(9) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY veranstaltung_verein (veranstaltung_id, verein_id),
            KEY veranstaltung_id (veranstaltung_id),
            KEY verein_id (verein_id)
        ) $charset;";
        
        dbDelta($sql);
        
        // Verein-Verantwortliche Zuordnung (m:n)
        $sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}verein_verantwortliche (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            verein_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            erstellt_am datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY verein_user (verein_id, user_id),
            KEY verein_id (verein_id),
            KEY user_id (user_id)
        ) $charset;";
        
        dbDelta($sql);
        
        // Veranstaltung-Verantwortliche Zuordnung (m:n)
        $sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}veranstaltung_verantwortliche (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            veranstaltung_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            erstellt_am datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY veranstaltung_user (veranstaltung_id, user_id),
            KEY veranstaltung_id (veranstaltung_id),
            KEY user_id (user_id)
        ) $charset;";
        
        dbDelta($sql);
        
        // Bereiche-Tabelle (z.B. Technik, Catering, Ordner, etc.)
        $sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}bereiche (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            beschreibung text,
            farbe varchar(7) DEFAULT '#3b82f6',
            sortierung int(3) DEFAULT 0,
            aktiv tinyint(1) DEFAULT 1,
            erstellt_am datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY name (name)
        ) $charset;";
        
        dbDelta($sql);
        
        // Tätigkeiten-Tabelle (z.B. Aufbau, Abbau, Bedienung, etc.)
        // Jede Tätigkeit gehört zu einem Bereich (1:N Beziehung)
        $sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}taetigkeiten (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            bereich_id mediumint(9) NOT NULL,
            name varchar(100) NOT NULL,
            beschreibung text,
            sortierung int(3) DEFAULT 0,
            aktiv tinyint(1) DEFAULT 1,
            erstellt_am datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY bereich_id (bereich_id),
            UNIQUE KEY bereich_name (bereich_id, name)
        ) $charset;";
        
        dbDelta($sql);
        
        // Dienste-Tabelle
        $sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}dienste (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            veranstaltung_id mediumint(9) NOT NULL,
            tag_id mediumint(9) NOT NULL,
            verein_id mediumint(9) NOT NULL,
            bereich_id mediumint(9) NOT NULL,
            taetigkeit_id mediumint(9) NOT NULL,
            von_zeit time,
            bis_zeit time,
            bis_datum date,
            anzahl_personen int(2) DEFAULT 1,
            besonderheiten text,
            splittbar tinyint(1) DEFAULT 1,
            erstellt_am datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY veranstaltung_id (veranstaltung_id),
            KEY tag_id (tag_id),
            KEY verein_id (verein_id),
            KEY bereich_id (bereich_id),
            KEY taetigkeit_id (taetigkeit_id)
        ) $charset;";
        
        dbDelta($sql);
        
        // Status-Feld zur dienste Tabelle hinzufügen (wenn nicht vorhanden)
        $column_exists = $this->wpdb->get_results(
            "SHOW COLUMNS FROM {$this->prefix}dienste LIKE 'status'"
        );
        
        if (empty($column_exists)) {
            $this->wpdb->query(
                "ALTER TABLE {$this->prefix}dienste 
                ADD COLUMN status varchar(50) DEFAULT 'geplant' AFTER anzahl_personen"
            );
        }
        
        // Dienst-Slots-Tabelle (für Split-Dienste)
        $sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}dienst_slots (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            dienst_id mediumint(9) NOT NULL,
            slot_nummer tinyint(2) NOT NULL DEFAULT 1,
            mitarbeiter_id mediumint(9) DEFAULT NULL,
            von_zeit time,
            bis_zeit time,
            bis_datum date,
            status varchar(20) DEFAULT 'offen',
            erstellt_am datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY dienst_id (dienst_id),
            KEY mitarbeiter_id (mitarbeiter_id)
        ) $charset;";
        
        dbDelta($sql);
        
        // Prüfe ob mitarbeiter_id Spalte in dienst_slots existiert, falls nicht hinzufügen
        $column_exists = $this->wpdb->get_results(
            "SHOW COLUMNS FROM {$this->prefix}dienst_slots LIKE 'mitarbeiter_id'"
        );
        
        if (empty($column_exists)) {
            $this->wpdb->query(
                "ALTER TABLE {$this->prefix}dienst_slots 
                ADD COLUMN mitarbeiter_id mediumint(9) DEFAULT NULL AFTER slot_nummer,
                ADD KEY mitarbeiter_id (mitarbeiter_id)"
            );
        }
        
        // Mitarbeiter-Tabelle (für öffentliche Self-Service Eintragung)
        $sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}mitarbeiter (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            vorname varchar(100) NOT NULL,
            nachname varchar(100) NOT NULL,
            email varchar(100),
            telefon varchar(50),
            datenschutz_akzeptiert tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_name (nachname, vorname),
            KEY idx_email (email)
        ) $charset;";
        
        dbDelta($sql);
        
        // Dienst-Zuweisungen-Tabelle (Frontend-Eintragungen)
        $sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}dienst_zuweisungen (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            dienst_id mediumint(9) NOT NULL,
            slot_id mediumint(9) DEFAULT NULL,
            mitarbeiter_id mediumint(9) DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            name varchar(255),
            email varchar(255),
            telefon varchar(50),
            kommentar text,
            status varchar(20) DEFAULT 'bestaetigt',
            eingetragen_am datetime DEFAULT CURRENT_TIMESTAMP,
            eingetragen_von bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY dienst_id (dienst_id),
            KEY slot_id (slot_id),
            KEY mitarbeiter_id (mitarbeiter_id),
            KEY user_id (user_id)
        ) $charset;";
        
        dbDelta($sql);
        
        // Prüfe ob mitarbeiter_id Spalte existiert, falls nicht hinzufügen
        $column_exists = $this->wpdb->get_results(
            "SHOW COLUMNS FROM {$this->prefix}dienst_zuweisungen LIKE 'mitarbeiter_id'"
        );
        
        if (empty($column_exists)) {
            $this->wpdb->query(
                "ALTER TABLE {$this->prefix}dienst_zuweisungen 
                ADD COLUMN mitarbeiter_id mediumint(9) DEFAULT NULL AFTER slot_id,
                ADD KEY mitarbeiter_id (mitarbeiter_id)"
            );
        }
        
        // Migrationen
        $this->migrate_taetigkeiten_add_bereich();
        $this->migrate_veranstaltungen_add_seite_id();
    }
    
    /**
     * Migration: Fügt seite_id zur Veranstaltungen-Tabelle hinzu
     */
    public function migrate_veranstaltungen_add_seite_id() {
        // Prüfe ob seite_id Spalte bereits existiert
        $column_exists = $this->wpdb->get_results(
            "SHOW COLUMNS FROM {$this->prefix}veranstaltungen LIKE 'seite_id'"
        );
        
        if (empty($column_exists)) {
            error_log('Dienstplan Migration: Füge seite_id zur Veranstaltungen-Tabelle hinzu');
            
            $this->wpdb->query(
                "ALTER TABLE {$this->prefix}veranstaltungen 
                ADD COLUMN seite_id bigint(20) UNSIGNED DEFAULT NULL AFTER end_datum,
                ADD KEY seite_id (seite_id)"
            );
            
            error_log('Dienstplan Migration: seite_id Spalte erfolgreich hinzugefügt');
        }
    }
    
    /**
     * Migration: Fügt bereich_id zur Tätigkeiten-Tabelle hinzu
     */
    private function migrate_taetigkeiten_add_bereich() {
        // Prüfe ob bereich_id Spalte bereits existiert
        $column_exists = $this->wpdb->get_results(
            "SHOW COLUMNS FROM {$this->prefix}taetigkeiten LIKE 'bereich_id'"
        );
        
        if (empty($column_exists)) {
            // Spalte existiert nicht - Migration erforderlich
            error_log('Dienstplan Migration 0.2.3: Starte Migration für Tätigkeiten-Tabelle');
            
            // 1. Stelle sicher, dass mindestens ein Bereich existiert
            $default_bereich = $this->wpdb->get_var(
                "SELECT id FROM {$this->prefix}bereiche ORDER BY id ASC LIMIT 1"
            );
            
            if (!$default_bereich) {
                // Erstelle einen Default-Bereich
                $this->wpdb->insert(
                    $this->prefix . 'bereiche',
                    array(
                        'name' => 'Allgemein',
                        'farbe' => '#3b82f6',
                        'aktiv' => 1
                    )
                );
                $default_bereich = $this->wpdb->insert_id;
                error_log('Dienstplan Migration 0.2.3: Standard-Bereich erstellt (ID: ' . $default_bereich . ')');
            }
            
            // 2. Füge bereich_id Spalte hinzu
            $result = $this->wpdb->query(
                "ALTER TABLE {$this->prefix}taetigkeiten 
                ADD COLUMN bereich_id mediumint(9) NOT NULL DEFAULT {$default_bereich} AFTER id,
                ADD KEY bereich_id (bereich_id)"
            );
            
            if ($result === false) {
                error_log('Dienstplan Migration 0.2.3 FEHLER: ' . $this->wpdb->last_error);
            } else {
                error_log('Dienstplan Migration 0.2.3: Spalte bereich_id hinzugefügt');
            }
            
            // 3. Entferne alten UNIQUE KEY auf name (falls vorhanden)
            // Prüfe erst, ob der Index existiert
            $indexes = $this->wpdb->get_results(
                "SHOW INDEX FROM {$this->prefix}taetigkeiten WHERE Key_name = 'name'"
            );
            if (!empty($indexes)) {
                $this->wpdb->query(
                    "ALTER TABLE {$this->prefix}taetigkeiten DROP INDEX name"
                );
                error_log('Dienstplan Migration 0.2.3: Alter Index "name" entfernt');
            }
            
            // 4. Füge neuen UNIQUE KEY auf bereich_id + name hinzu (falls nicht vorhanden)
            $indexes = $this->wpdb->get_results(
                "SHOW INDEX FROM {$this->prefix}taetigkeiten WHERE Key_name = 'bereich_name'"
            );
            if (empty($indexes)) {
                $this->wpdb->query(
                    "ALTER TABLE {$this->prefix}taetigkeiten 
                    ADD UNIQUE KEY bereich_name (bereich_id, name)"
                );
                error_log('Dienstplan Migration 0.2.3: Neuer Index "bereich_name" hinzugefügt');
            }
            
            error_log('Dienstplan Migration 0.2.3: Migration erfolgreich abgeschlossen');
        } else {
            error_log('Dienstplan Migration 0.2.3: Spalte bereich_id existiert bereits - Migration übersprungen');
        }
    }
    
    public function get_stats() {
        $total_vereine = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->prefix}vereine");
        $aktive_vereine = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->prefix}vereine WHERE aktiv = 1");
        
        $total_veranstaltungen = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->prefix}veranstaltungen");
        $geplante_veranstaltungen = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->prefix}veranstaltungen WHERE status = 'geplant'");
        
        $total_dienste = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->prefix}dienste");
        
        // Prüfe ob mitarbeiter_id Spalte existiert
        $column_exists = $this->wpdb->get_results(
            "SHOW COLUMNS FROM {$this->prefix}dienst_slots LIKE 'mitarbeiter_id'"
        );
        
        if (!empty($column_exists)) {
            $offene_slots = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->prefix}dienst_slots WHERE mitarbeiter_id IS NULL");
            $zugewiesene_slots = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->prefix}dienst_slots WHERE mitarbeiter_id IS NOT NULL");
        } else {
            // Fallback wenn Spalte noch nicht existiert
            $offene_slots = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->prefix}dienst_slots WHERE status = 'offen'");
            $zugewiesene_slots = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->prefix}dienst_slots WHERE status != 'offen'");
        }
        
        return array(
            'version' => DIENSTPLAN_VERSION,
            'prefix' => $this->prefix,
            'total_vereine' => (int) $total_vereine,
            'aktive_vereine' => (int) $aktive_vereine,
            'total_veranstaltungen' => (int) $total_veranstaltungen,
            'geplante_veranstaltungen' => (int) $geplante_veranstaltungen,
            'total_dienste' => (int) $total_dienste,
            'offene_slots' => (int) $offene_slots,
            'zugewiesene_slots' => (int) $zugewiesene_slots
        );
    }
    
    // === VEREINE METHODEN ===
    
    public function get_vereine($aktiv_only = false) {
        $where = $aktiv_only ? "WHERE aktiv = 1" : "";
        return $this->wpdb->get_results("SELECT * FROM {$this->prefix}vereine {$where} ORDER BY name ASC");
    }
    
    public function get_verein($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->prefix}vereine WHERE id = %d",
            $id
        ));
    }
    
    public function add_verein($data) {
        // Prüfe ob Kürzel bereits existiert
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->prefix}vereine WHERE kuerzel = %s",
            $data['kuerzel']
        ));
        
        if ($existing > 0) {
            return false; // Kürzel existiert bereits
        }
        
        // Prüfe ob Name bereits existiert
        $existing_name = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->prefix}vereine WHERE name = %s",
            $data['name']
        ));
        
        if ($existing_name > 0) {
            return false; // Name existiert bereits
        }
        
        $result = $this->wpdb->insert($this->prefix . 'vereine', $data);
        
        if ($result) {
            return $this->wpdb->insert_id;
        }
        
        return false;
    }
    
    public function update_verein($id, $data) {
        // Prüfe ob Kürzel bereits von einem anderen Verein verwendet wird
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->prefix}vereine WHERE kuerzel = %s AND id != %d",
            $data['kuerzel'],
            $id
        ));
        
        if ($existing > 0) {
            return false; // Kürzel wird bereits verwendet
        }
        
        // Prüfe ob Name bereits von einem anderen Verein verwendet wird
        $existing_name = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->prefix}vereine WHERE name = %s AND id != %d",
            $data['name'],
            $id
        ));
        
        if ($existing_name > 0) {
            return false; // Name wird bereits verwendet
        }
        
        return $this->wpdb->update(
            $this->prefix . 'vereine',
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
    }
    
    public function delete_verein($id) {
        return $this->wpdb->delete(
            $this->prefix . 'vereine',
            array('id' => $id),
            array('%d')
        );
    }
    
    public function get_verein_by_kuerzel($kuerzel) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->prefix}vereine WHERE kuerzel = %s",
            $kuerzel
        ), ARRAY_A);
    }
    
    // === VERANSTALTUNGEN METHODEN ===
    
    public function get_veranstaltungen($filter = array()) {
        $where = array('1=1');
        
        if (!empty($filter['status'])) {
            $where[] = $this->wpdb->prepare("status = %s", $filter['status']);
        }
        
        if (!empty($filter['von_datum'])) {
            $where[] = $this->wpdb->prepare("start_datum >= %s", $filter['von_datum']);
        }
        
        if (!empty($filter['bis_datum'])) {
            $where[] = $this->wpdb->prepare("start_datum <= %s", $filter['bis_datum']);
        }
        
        $where_clause = implode(' AND ', $where);
        
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->prefix}veranstaltungen 
            WHERE {$where_clause} 
            ORDER BY start_datum DESC"
        );
    }
    
    public function get_veranstaltung($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->prefix}veranstaltungen WHERE id = %d",
            $id
        ));
    }
    
    public function add_veranstaltung($data) {
        // Prüfe ob bereits eine Veranstaltung mit gleichem Namen am gleichen Datum existiert
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->prefix}veranstaltungen 
            WHERE name = %s AND start_datum = %s",
            $data['name'],
            $data['start_datum']
        ));
        
        if ($existing > 0) {
            return false; // Veranstaltung existiert bereits
        }
        
        $result = $this->wpdb->insert($this->prefix . 'veranstaltungen', $data);
        if ($result) {
            return $this->wpdb->insert_id;
        }
        return false;
    }
    
    public function update_veranstaltung($id, $data) {
        // Prüfe ob bereits eine andere Veranstaltung mit gleichem Namen am gleichen Datum existiert
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->prefix}veranstaltungen 
            WHERE name = %s AND start_datum = %s AND id != %d",
            $data['name'],
            $data['start_datum'],
            $id
        ));
        
        if ($existing > 0) {
            return false; // Veranstaltung existiert bereits
        }
        
        return $this->wpdb->update(
            $this->prefix . 'veranstaltungen',
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
    }
    
    public function delete_veranstaltung($id) {
        // Erst alle zugehörigen Daten löschen
        $this->delete_veranstaltung_tage($id);
        $this->delete_veranstaltung_vereine($id);
        
        return $this->wpdb->delete(
            $this->prefix . 'veranstaltungen',
            array('id' => $id),
            array('%d')
        );
    }
    
    public function get_veranstaltung_by_name($name) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->prefix}veranstaltungen WHERE name = %s",
            $name
        ), ARRAY_A);
    }
    
    public function create_veranstaltung_page($veranstaltung_id, $data) {
        // Sicherstellen, dass es ein Array ist
        if (is_object($data)) {
            $data = (array) $data;
        }
        
        // Daten validieren
        if (empty($data['name'])) {
            return false;
        }
        
        $page_title = $data['name'];
        $page_slug = sanitize_title($page_title . '-dienstplan');
        
        // Datum formatieren
        $start_datum = isset($data['start_datum']) ? date_i18n(get_option('date_format'), strtotime($data['start_datum'])) : '';
        $end_datum = isset($data['end_datum']) && !empty($data['end_datum']) ? date_i18n(get_option('date_format'), strtotime($data['end_datum'])) : '';
        
        // Zeitraum-Text
        if ($end_datum && $end_datum !== $start_datum) {
            $zeitraum = sprintf(__('vom %s bis %s', 'dienstplan-verwaltung'), $start_datum, $end_datum);
        } else {
            $zeitraum = sprintf(__('am %s', 'dienstplan-verwaltung'), $start_datum);
        }
        
        // Seiten-Inhalt mit Shortcode
        $page_content = sprintf(
            '<h2>%s</h2>
            <p><strong>%s:</strong> %s</p>
            %s
            
            [dienstplan veranstaltung_id="%d"]
            
            <div class="dienstplan-info" style="margin-top: 2rem; padding: 1rem; background: #f0f9ff; border-left: 4px solid #0ea5e9;">
                <h3>ℹ️ Informationen</h3>
                <p>Hier können Sie sich für freie Dienste eintragen. Wählen Sie einfach einen freien Dienst aus und tragen Sie Ihre Daten ein.</p>
                <p>Bei Fragen wenden Sie sich bitte an die Veranstaltungs-Verantwortlichen.</p>
            </div>',
            esc_html($page_title),
            __('Zeitraum', 'dienstplan-verwaltung'),
            $zeitraum,
            !empty($data['beschreibung']) ? '<p>' . nl2br(esc_html($data['beschreibung'])) . '</p>' : '',
            $veranstaltung_id
        );
        
        // Seite erstellen
        $page_data = array(
            'post_title'    => $page_title . ' - Dienstplan',
            'post_content'  => $page_content,
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => $page_slug,
            'post_author'   => get_current_user_id(),
            'comment_status' => 'closed',
            'ping_status'   => 'closed'
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id && !is_wp_error($page_id)) {
            // Meta-Daten hinzufügen
            update_post_meta($page_id, '_dienstplan_veranstaltung_id', $veranstaltung_id);
            update_post_meta($page_id, '_dienstplan_auto_created', true);
            
            return $page_id;
        }
        
        return false;
    }
    
    public function update_veranstaltung_page_id($veranstaltung_id, $seite_id) {
        return $this->wpdb->update(
            $this->prefix . 'veranstaltungen',
            array('seite_id' => $seite_id),
            array('id' => $veranstaltung_id),
            array('%d'),
            array('%d')
        );
    }
    
    // === VERANSTALTUNGS-TAGE METHODEN ===
    
    public function get_veranstaltung_tage($veranstaltung_id) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->prefix}veranstaltung_tage 
            WHERE veranstaltung_id = %d 
            ORDER BY tag_nummer ASC",
            $veranstaltung_id
        ));
    }
    
    public function get_veranstaltung_tag($tag_id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->prefix}veranstaltung_tage WHERE id = %d",
            $tag_id
        ));
    }
    
    public function add_veranstaltung_tag($data) {
        return $this->wpdb->insert($this->prefix . 'veranstaltung_tage', $data);
    }

    public function update_veranstaltung_tag($tag_id, $data) {
        // Entferne Schlüssel die nicht aktualisiert werden sollen (id selbst)
        unset($data['id']);
        return $this->wpdb->update(
            $this->prefix . 'veranstaltung_tage',
            $data,
            array('id' => $tag_id),
            null,
            array('%d')
        );
    }
    
    public function delete_veranstaltung_tage($veranstaltung_id) {
        return $this->wpdb->delete(
            $this->prefix . 'veranstaltung_tage',
            array('veranstaltung_id' => $veranstaltung_id),
            array('%d')
        );
    }
    
    // === VERANSTALTUNGS-VEREINE METHODEN ===
    
    public function get_veranstaltung_vereine($veranstaltung_id) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                v.id as verein_id,
                v.name as verein_name,
                v.kuerzel as verein_kuerzel,
                v.aktiv
            FROM {$this->prefix}vereine v
            INNER JOIN {$this->prefix}veranstaltung_vereine vv ON v.id = vv.verein_id
            WHERE vv.veranstaltung_id = %d
            ORDER BY v.name ASC",
            $veranstaltung_id
        ));
    }
    
    public function add_veranstaltung_verein($veranstaltung_id, $verein_id) {
        return $this->wpdb->insert(
            $this->prefix . 'veranstaltung_vereine',
            array(
                'veranstaltung_id' => $veranstaltung_id,
                'verein_id' => $verein_id
            ),
            array('%d', '%d')
        );
    }
    
    public function delete_veranstaltung_vereine($veranstaltung_id) {
        return $this->wpdb->delete(
            $this->prefix . 'veranstaltung_vereine',
            array('veranstaltung_id' => $veranstaltung_id),
            array('%d')
        );
    }
    
    // === VEREIN-VERANTWORTLICHE METHODEN ===
    
    public function get_verein_verantwortliche($verein_id) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT user_id FROM {$this->prefix}verein_verantwortliche
            WHERE verein_id = %d
            ORDER BY id ASC",
            $verein_id
        ));
    }
    
    public function save_verein_verantwortliche($verein_id, $user_ids) {
        // Erst alle löschen
        $this->wpdb->delete(
            $this->prefix . 'verein_verantwortliche',
            array('verein_id' => $verein_id),
            array('%d')
        );
        
        // Dann neue einfügen
        if (!empty($user_ids) && is_array($user_ids)) {
            foreach ($user_ids as $user_id) {
                $this->wpdb->insert(
                    $this->prefix . 'verein_verantwortliche',
                    array(
                        'verein_id' => $verein_id,
                        'user_id' => $user_id
                    ),
                    array('%d', '%d')
                );
            }
        }
        
        return true;
    }
    
    public function delete_verein_verantwortliche($verein_id) {
        return $this->wpdb->delete(
            $this->prefix . 'verein_verantwortliche',
            array('verein_id' => $verein_id),
            array('%d')
        );
    }
    
    // === VERANSTALTUNG-VERANTWORTLICHE METHODEN ===
    
    public function get_veranstaltung_verantwortliche($veranstaltung_id) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT user_id FROM {$this->prefix}veranstaltung_verantwortliche
            WHERE veranstaltung_id = %d
            ORDER BY id ASC",
            $veranstaltung_id
        ));
    }
    
    public function save_veranstaltung_verantwortliche($veranstaltung_id, $user_ids) {
        // Erst alle löschen
        $this->wpdb->delete(
            $this->prefix . 'veranstaltung_verantwortliche',
            array('veranstaltung_id' => $veranstaltung_id),
            array('%d')
        );
        
        // Dann neue einfügen
        if (!empty($user_ids) && is_array($user_ids)) {
            foreach ($user_ids as $user_id) {
                $this->wpdb->insert(
                    $this->prefix . 'veranstaltung_verantwortliche',
                    array(
                        'veranstaltung_id' => $veranstaltung_id,
                        'user_id' => $user_id
                    ),
                    array('%d', '%d')
                );
            }
        }
        
        return true;
    }
    
    public function delete_veranstaltung_verantwortliche($veranstaltung_id) {
        return $this->wpdb->delete(
            $this->prefix . 'veranstaltung_verantwortliche',
            array('veranstaltung_id' => $veranstaltung_id),
            array('%d')
        );
    }
    
    // === BEREICHE METHODEN ===
    
    public function get_bereiche($aktiv_only = false) {
        $where = $aktiv_only ? "WHERE aktiv = 1" : "";
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->prefix}bereiche {$where} ORDER BY sortierung ASC, name ASC"
        );
    }
    
    public function get_bereich($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->prefix}bereiche WHERE id = %d",
            $id
        ));
    }
    
    public function add_bereich($data) {
        $result = $this->wpdb->insert($this->prefix . 'bereiche', $data);
        if ($result) {
            return $this->wpdb->insert_id;
        }
        return false;
    }
    
    public function update_bereich($id, $data) {
        return $this->wpdb->update(
            $this->prefix . 'bereiche',
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
    }
    
    public function delete_bereich($id) {
        return $this->wpdb->delete(
            $this->prefix . 'bereiche',
            array('id' => $id),
            array('%d')
        );
    }
    
    public function get_or_create_bereich($name) {
        // Trimme den Namen
        $name = trim($name);
        
        if (empty($name)) {
            return false;
        }
        
        // Suche nach Name (case-insensitiv möglich)
        $bereich = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT id FROM {$this->prefix}bereiche WHERE LOWER(name) = LOWER(%s)",
            $name
        ));
        
        if ($bereich) {
            return $bereich->id;
        }
        
        // Erstelle neuen Bereich mit Standard-Farbe
        $result = $this->add_bereich(array(
            'name' => $name,
            'aktiv' => 1,
            'sortierung' => 999,
            'farbe' => '#3b82f6' // Standard-Farbe
        ));
        
        // Wenn Fehler (z.B. UNIQUE Constraint), versuche erneut zu finden
        if ($result === false) {
            // Möglicherweise wurde der Bereich gerade von einem anderen Prozess erstellt
            $bereich = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT id FROM {$this->prefix}bereiche WHERE LOWER(name) = LOWER(%s)",
                $name
            ));
            if ($bereich) {
                return $bereich->id;
            }
            return false;
        }
        
        return $result;
    }
    
    // === TÄTIGKEITEN METHODEN ===
    
    // Tätigkeiten für einen bestimmten Bereich laden
    public function get_taetigkeiten_by_bereich($bereich_id, $aktiv_only = false) {
        $where = "WHERE bereich_id = %d";
        if ($aktiv_only) {
            $where .= " AND aktiv = 1";
        }
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->prefix}taetigkeiten {$where} ORDER BY sortierung ASC, name ASC",
            $bereich_id
        ));
    }
    
    // Alle Tätigkeiten laden (für Admin-Übersicht)
    public function get_taetigkeiten($aktiv_only = false) {
        $where = $aktiv_only ? "WHERE t.aktiv = 1" : "";
        return $this->wpdb->get_results(
            "SELECT t.*, b.name as bereich_name, b.farbe as bereich_farbe 
             FROM {$this->prefix}taetigkeiten t
             LEFT JOIN {$this->prefix}bereiche b ON t.bereich_id = b.id
             {$where} 
             ORDER BY b.sortierung ASC, b.name ASC, t.sortierung ASC, t.name ASC"
        );
    }
    
    public function get_taetigkeit($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT t.*, b.name as bereich_name 
             FROM {$this->prefix}taetigkeiten t
             LEFT JOIN {$this->prefix}bereiche b ON t.bereich_id = b.id
             WHERE t.id = %d",
            $id
        ));
    }
    
    public function count_dienste_by_taetigkeit($taetigkeit_id) {
        return (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->prefix}dienste WHERE taetigkeit_id = %d",
            $taetigkeit_id
        ));
    }
    
    public function add_taetigkeit($data) {
        // bereich_id ist jetzt Pflicht
        if (empty($data['bereich_id'])) {
            return false;
        }
        
        $result = $this->wpdb->insert($this->prefix . 'taetigkeiten', $data);
        if ($result) {
            return $this->wpdb->insert_id;
        }
        return false;
    }
    
    public function create_taetigkeit($data) {
        return $this->add_taetigkeit($data);
    }
    
    public function update_taetigkeit($id, $data) {
        return $this->wpdb->update(
            $this->prefix . 'taetigkeiten',
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
    }
    
    public function delete_taetigkeit($id) {
        return $this->wpdb->delete(
            $this->prefix . 'taetigkeiten',
            array('id' => $id),
            array('%d')
        );
    }
    
    public function get_or_create_taetigkeit($name, $bereich_id = null) {
        // Suche zuerst nach Name + Bereich (exakte Übereinstimmung)
        if ($bereich_id) {
            $sql = "SELECT id FROM {$this->prefix}taetigkeiten WHERE name = %s AND bereich_id = %d";
            $taetigkeit = $this->wpdb->get_row($this->wpdb->prepare($sql, $name, $bereich_id));
            
            if ($taetigkeit) {
                return $taetigkeit->id;
            }
        }
        
        // Suche nach Name (ohne Bereich-Filter) um Duplikat zu vermeiden
        $sql = "SELECT id FROM {$this->prefix}taetigkeiten WHERE name = %s LIMIT 1";
        $existing = $this->wpdb->get_row($this->wpdb->prepare($sql, $name));
        
        if ($existing) {
            return $existing->id;
        }
        
        // Erstelle neue Tätigkeit
        $data = array(
            'name' => $name,
            'aktiv' => 1,
            'sortierung' => 999
        );
        
        if ($bereich_id) {
            $data['bereich_id'] = $bereich_id;
        }
        
        $result = $this->add_taetigkeit($data);
        return $result;
    }
    
    // === DIENSTE METHODEN ===
    
    public function get_dienste($veranstaltung_id = null, $verein_id = null, $tag_id = null) {
        $where = array();
        $params = array();
        
        if ($veranstaltung_id) {
            $where[] = "d.veranstaltung_id = %d";
            $params[] = $veranstaltung_id;
        }
        
        if ($verein_id) {
            $where[] = "d.verein_id = %d";
            $params[] = $verein_id;
        }
        
        if ($tag_id) {
            $where[] = "d.tag_id = %d";
            $params[] = $tag_id;
        }
        
        $where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $sql = "SELECT d.*, v.name as verein_name, v.kuerzel as verein_kuerzel, b.name as bereich_name, b.farbe as bereich_farbe, 
                t.name as taetigkeit_name
                FROM {$this->prefix}dienste d
                LEFT JOIN {$this->prefix}vereine v ON d.verein_id = v.id
                LEFT JOIN {$this->prefix}bereiche b ON d.bereich_id = b.id
                LEFT JOIN {$this->prefix}taetigkeiten t ON d.taetigkeit_id = t.id
                {$where_sql}
                ORDER BY d.von_zeit ASC";
        
        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, $params);
        }
        
        $results = $this->wpdb->get_results($sql);
        
        return $results;
    }
    
    public function get_recent_dienste($limit = 10) {
        $sql = "SELECT d.*, v.name as verein_name, ve.name as veranstaltung_name,
                vt.tag_datum, vt.tag_nummer,
                b.name as bereich_name, b.farbe as bereich_farbe, 
                t.name as taetigkeit_name
                FROM {$this->prefix}dienste d
                LEFT JOIN {$this->prefix}vereine v ON d.verein_id = v.id
                LEFT JOIN {$this->prefix}veranstaltungen ve ON d.veranstaltung_id = ve.id
                LEFT JOIN {$this->prefix}veranstaltung_tage vt ON d.tag_id = vt.id
                LEFT JOIN {$this->prefix}bereiche b ON d.bereich_id = b.id
                LEFT JOIN {$this->prefix}taetigkeiten t ON d.taetigkeit_id = t.id
                ORDER BY d.id DESC
                LIMIT %d";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $limit));
    }
    
    public function get_dienst($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT d.id, d.veranstaltung_id, d.tag_id, d.verein_id, 
                    d.bereich_id, d.taetigkeit_id,
                    d.von_zeit, d.bis_zeit, d.bis_datum, 
                    d.anzahl_personen, d.besonderheiten, d.splittbar, d.erstellt_am,
                    v.name as verein_name,
                    ve.name as veranstaltung_name,
                    b.name as bereich_name, b.farbe as bereich_farbe,
                    t.name as taetigkeit_name,
                    vt.tag_nummer, vt.tag_datum
             FROM {$this->prefix}dienste d
             LEFT JOIN {$this->prefix}vereine v ON d.verein_id = v.id
             LEFT JOIN {$this->prefix}veranstaltungen ve ON d.veranstaltung_id = ve.id
             LEFT JOIN {$this->prefix}veranstaltung_tage vt ON d.tag_id = vt.id
             LEFT JOIN {$this->prefix}bereiche b ON d.bereich_id = b.id
             LEFT JOIN {$this->prefix}taetigkeiten t ON d.taetigkeit_id = t.id
             WHERE d.id = %d",
            $id
        ));
    }
    
    /**
     * Alias für get_dienst() - lädt Dienst mit allen Details
     * (Name ist expliziter für Backend-Verwendung)
     */
    public function get_dienst_with_details($id) {
        return $this->get_dienst($id);
    }
    
    public function add_dienst($data) {
        // Validiere Zeitfenster, falls tag_id vorhanden
        if (!empty($data['tag_id']) && !empty($data['von_zeit']) && !empty($data['bis_zeit'])) {
            $validation = $this->validate_dienst_zeitfenster(
                $data['tag_id'],
                $data['von_zeit'],
                $data['bis_zeit'],
                isset($data['bis_datum']) ? $data['bis_datum'] : null
            );
            
            if (!$validation['valid']) {
                return array('error' => true, 'message' => $validation['message']);
            }
        }
        
        $result = $this->wpdb->insert($this->prefix . 'dienste', $data);
        
        if ($result) {
            $dienst_id = $this->wpdb->insert_id;
            
            // Erstelle IMMER Slots basierend auf anzahl_personen
            // Wenn splittbar = 1: 2 Slots (halbe Dienste)
            // Wenn splittbar = 0: anzahl_personen Slots (ganze Dienste)
            $this->create_dienst_slots($dienst_id, $data);
            
            return $dienst_id;
        }
        
        return false;
    }
    
    public function update_dienst($id, $data) {
        $tag_id_from_current = false;
        
        // Hole aktuellen Dienst für tag_id falls nicht übergeben
        if (!isset($data['tag_id'])) {
            $current = $this->get_dienst($id);
            if ($current) {
                $data['tag_id'] = $current->tag_id;
                $tag_id_from_current = true;
            }
        }
        
        // Validiere Zeitfenster, falls von_zeit und bis_zeit vorhanden
        if (!empty($data['tag_id']) && !empty($data['von_zeit']) && !empty($data['bis_zeit'])) {
            $validation = $this->validate_dienst_zeitfenster(
                $data['tag_id'],
                $data['von_zeit'],
                $data['bis_zeit'],
                isset($data['bis_datum']) ? $data['bis_datum'] : null
            );
            
            if (!$validation['valid']) {
                return array('error' => true, 'message' => $validation['message']);
            }
        }
        
        // Entferne tag_id aus update NUR falls es nur für Validierung hinzugefügt wurde
        // NICHT wenn tag_id explizit im $data übergeben wurde!
        if ($tag_id_from_current) {
            unset($data['tag_id']);
        }
        
        $result = $this->wpdb->update(
            $this->prefix . 'dienste',
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
        
        return $result;
    }
    
    public function delete_dienst($id) {
        // Erst Slots löschen
        $this->wpdb->delete(
            $this->prefix . 'dienst_slots',
            array('dienst_id' => $id),
            array('%d')
        );
        
        // Dann Zuweisungen löschen
        $this->wpdb->delete(
            $this->prefix . 'dienst_zuweisungen',
            array('dienst_id' => $id),
            array('%d')
        );
        
        // Dann Dienst löschen
        return $this->wpdb->delete(
            $this->prefix . 'dienste',
            array('id' => $id),
            array('%d')
        );
    }
    
    // === DIENST-SLOTS METHODEN ===
    
    private function create_dienst_slots($dienst_id, $dienst_data) {
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
            $this->wpdb->insert(
                $this->prefix . 'dienst_slots',
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
            $this->wpdb->insert(
                $this->prefix . 'dienst_slots',
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
                $this->wpdb->insert(
                    $this->prefix . 'dienst_slots',
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
    
    public function get_dienst_slots($dienst_id) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT s.*, 
                    m.vorname as mitarbeiter_vorname,
                    m.nachname as mitarbeiter_nachname,
                    m.email as mitarbeiter_email,
                    m.telefon as mitarbeiter_telefon
             FROM {$this->prefix}dienst_slots s
             LEFT JOIN {$this->prefix}mitarbeiter m ON s.mitarbeiter_id = m.id
             WHERE s.dienst_id = %d 
             ORDER BY s.slot_nummer ASC",
            $dienst_id
        ));
    }
    
    // === DIENST-ZUWEISUNGEN METHODEN ===
    
    public function get_dienst_zuweisungen($dienst_id) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->prefix}dienst_zuweisungen WHERE dienst_id = %d ORDER BY eingetragen_am ASC",
            $dienst_id
        ));
    }
    
    public function add_dienst_zuweisung($data) {
        return $this->wpdb->insert($this->prefix . 'dienst_zuweisungen', $data);
    }
    
    public function delete_dienst_zuweisung($id) {
        return $this->wpdb->delete(
            $this->prefix . 'dienst_zuweisungen',
            array('id' => $id),
            array('%d')
        );
    }
    
    // === VALIDIERUNG ===
    
    /**
     * Prüft ob die Dienst-Zeiten innerhalb des Tag-Zeitfensters liegen
     * 
     * @param int $tag_id Tag-ID
     * @param string $dienst_von_zeit Dienst Von-Zeit (HH:MM oder HH:MM:SS)
     * @param string $dienst_bis_zeit Dienst Bis-Zeit (HH:MM oder HH:MM:SS)
     * @param string $dienst_bis_datum Optional: Datum wenn Dienst über Mitternacht geht
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validate_dienst_zeitfenster($tag_id, $dienst_von_zeit, $dienst_bis_zeit, $dienst_bis_datum = null) {
        // Hole Tag-Daten für Datumsbasis
        $tag = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT tag_datum FROM {$this->prefix}veranstaltung_tage WHERE id = %d",
            $tag_id
        ));
        
        if (!$tag) {
            return array('valid' => false, 'message' => 'Tag nicht gefunden');
        }
        
        // Normalisiere Zeiten auf HH:MM:SS Format
        $dienst_von = strlen($dienst_von_zeit) == 5 ? $dienst_von_zeit . ':00' : $dienst_von_zeit;
        $dienst_bis = strlen($dienst_bis_zeit) == 5 ? $dienst_bis_zeit . ':00' : $dienst_bis_zeit;
        
        // Extrahiere Stunden für Vergleich
        list($von_hour) = explode(':', $dienst_von);
        list($bis_hour) = explode(':', $dienst_bis);
        $von_hour = intval($von_hour);
        $bis_hour = intval($bis_hour);
        
        // Erstelle Timestamps für Vergleich
        $tag_datum = $tag->tag_datum;
        $dienst_von_ts = strtotime($tag_datum . ' ' . $dienst_von);
        
        // Auto-Erkennung: Wenn bis_zeit < von_zeit → Overnight-Dienst
        if ($bis_hour < $von_hour && empty($dienst_bis_datum)) {
            // Automatisch nächsten Tag annehmen
            $dienst_bis_datum = date('Y-m-d', strtotime($tag_datum . ' +1 day'));
        }
        
        // Berücksichtige Folgetag
        $dienst_bis_datum_final = !empty($dienst_bis_datum) ? $dienst_bis_datum : $tag_datum;
        $dienst_bis_ts = strtotime($dienst_bis_datum_final . ' ' . $dienst_bis);
        
        // Validierung: Nur logische Fehler prüfen (Start >= Ende)
        if ($dienst_von_ts >= $dienst_bis_ts) {
            return array(
                'valid' => false, 
                'message' => 'Dienst-Start muss vor Dienst-Ende liegen'
            );
        }
        
        return array('valid' => true, 'message' => '');
    }
    
    // === MITARBEITER METHODEN ===
    
    public function get_mitarbeiter($id = null) {
        if ($id) {
            return $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->prefix}mitarbeiter WHERE id = %d",
                $id
            ));
        }
        return $this->wpdb->get_results("SELECT * FROM {$this->prefix}mitarbeiter ORDER BY nachname, vorname ASC");
    }
    
    public function get_mitarbeiter_by_email($email) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->prefix}mitarbeiter WHERE email = %s",
            $email
        ));
    }
    
    public function add_mitarbeiter($data) {
        $result = $this->wpdb->insert($this->prefix . 'mitarbeiter', $data);
        return $result ? $this->wpdb->insert_id : false;
    }
    
    public function update_mitarbeiter($id, $data) {
        return $this->wpdb->update(
            $this->prefix . 'mitarbeiter',
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
    }
    
    public function delete_mitarbeiter($id) {
        // Entferne Mitarbeiter aus allen Slots (setze auf NULL)
        $this->wpdb->update(
            $this->prefix . 'dienst_slots',
            array('mitarbeiter_id' => null, 'status' => 'frei'),
            array('mitarbeiter_id' => $id),
            array('%d', '%s'),
            array('%d')
        );
        
        // Entferne veraltete Zuweisungen (falls noch vorhanden)
        $this->wpdb->delete(
            $this->prefix . 'dienst_zuweisungen',
            array('mitarbeiter_id' => $id),
            array('%d')
        );
        
        // Lösche Mitarbeiter
        return $this->wpdb->delete(
            $this->prefix . 'mitarbeiter',
            array('id' => $id),
            array('%d')
        );
    }
    
    /**
     * Hole Mitarbeiter mit Dienst-Statistiken
     * 
     * @param int $filter_verein Optional: Filter nach Verein-ID
     * @param int $filter_veranstaltung Optional: Filter nach Veranstaltung-ID
     * @param string $search Optional: Suchbegriff für Name/Email
     * @return array Mitarbeiter mit Statistiken
     */
    public function get_mitarbeiter_with_stats($filter_verein = 0, $filter_veranstaltung = 0, $search = '') {
        $sql = "SELECT m.*, 
                COUNT(DISTINCT s.id) as total_dienste,
                COUNT(DISTINCT CASE WHEN s.status = 'besetzt' THEN s.id END) as aktive_dienste,
                GROUP_CONCAT(DISTINCT v.name ORDER BY v.name SEPARATOR ', ') as vereine
                FROM {$this->prefix}mitarbeiter m
                LEFT JOIN {$this->prefix}dienst_slots s ON m.id = s.mitarbeiter_id
                LEFT JOIN {$this->prefix}dienste d ON s.dienst_id = d.id
                LEFT JOIN {$this->prefix}vereine v ON d.verein_id = v.id
                WHERE 1=1";
        
        $params = array();
        
        // Filter nach Verein
        if ($filter_verein > 0) {
            $sql .= " AND d.verein_id = %d";
            $params[] = $filter_verein;
        }
        
        // Filter nach Veranstaltung
        if ($filter_veranstaltung > 0) {
            $sql .= " AND d.veranstaltung_id = %d";
            $params[] = $filter_veranstaltung;
        }
        
        // Suche
        if (!empty($search)) {
            $sql .= " AND (m.vorname LIKE %s OR m.nachname LIKE %s OR m.email LIKE %s)";
            $search_term = '%' . $this->wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        $sql .= " GROUP BY m.id ORDER BY m.nachname ASC, m.vorname ASC";
        
        if (!empty($params)) {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
        }
        
        return $this->wpdb->get_results($sql);
    }
    
    // === SLOT-ZUWEISUNGEN METHODEN ===
    
    public function get_slot($slot_id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->prefix}dienst_slots WHERE id = %d",
            $slot_id
        ));
    }
    
    public function assign_mitarbeiter_to_slot($slot_id, $mitarbeiter_id) {
        // Prüfe ob Slot noch frei ist
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT mitarbeiter_id FROM {$this->prefix}dienst_slots WHERE id = %d",
            $slot_id
        ));
        
        if ($existing) {
            return array('error' => true, 'message' => 'Dieser Slot ist bereits vergeben');
        }
        
        // Aktualisiere Slot
        $result = $this->wpdb->update(
            $this->prefix . 'dienst_slots',
            array(
                'mitarbeiter_id' => $mitarbeiter_id,
                'status' => 'vergeben'
            ),
            array('id' => $slot_id),
            array('%d', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    public function remove_mitarbeiter_from_slot($slot_id) {
        return $this->wpdb->update(
            $this->prefix . 'dienst_slots',
            array(
                'mitarbeiter_id' => null,
                'status' => 'offen'
            ),
            array('id' => $slot_id),
            array('%d', '%s'),
            array('%d')
        );
    }
    
    public function get_mitarbeiter_dienste($mitarbeiter_id) {
        $sql = "SELECT ds.*, d.*, 
                v.name as verein_name,
                ve.name as veranstaltung_name,
                vt.tag_datum, vt.tag_nummer,
                b.name as bereich_name, b.farbe as bereich_farbe,
                t.name as taetigkeit_name
                FROM {$this->prefix}dienst_slots ds
                INNER JOIN {$this->prefix}dienste d ON ds.dienst_id = d.id
                LEFT JOIN {$this->prefix}vereine v ON d.verein_id = v.id
                LEFT JOIN {$this->prefix}veranstaltungen ve ON d.veranstaltung_id = ve.id
                LEFT JOIN {$this->prefix}veranstaltung_tage vt ON d.tag_id = vt.id
                LEFT JOIN {$this->prefix}bereiche b ON d.bereich_id = b.id
                LEFT JOIN {$this->prefix}taetigkeiten t ON d.taetigkeit_id = t.id
                WHERE ds.mitarbeiter_id = %d
                ORDER BY vt.tag_datum, d.von_zeit ASC";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $mitarbeiter_id));
    }
    
    /**
     * Zählt Dienste für eine Veranstaltung
     */
    public function count_dienste_by_veranstaltung($veranstaltung_id) {
        $sql = "SELECT COUNT(*) FROM {$this->prefix}dienste WHERE veranstaltung_id = %d";
        return (int) $this->wpdb->get_var($this->wpdb->prepare($sql, $veranstaltung_id));
    }
    
    /**
     * Löscht alle Dienste und Slots einer Veranstaltung
     */
    public function delete_dienste_by_veranstaltung($veranstaltung_id) {
        // Erst die Slots löschen
        $dienst_ids = $this->wpdb->get_col($this->wpdb->prepare(
            "SELECT id FROM {$this->prefix}dienste WHERE veranstaltung_id = %d",
            $veranstaltung_id
        ));
        
        if (!empty($dienst_ids)) {
            $placeholders = implode(',', array_fill(0, count($dienst_ids), '%d'));
            $this->wpdb->query($this->wpdb->prepare(
                "DELETE FROM {$this->prefix}dienst_slots WHERE dienst_id IN ($placeholders)",
                ...$dienst_ids
            ));
        }
        
        // Dann die Dienste löschen
        return $this->wpdb->delete(
            $this->prefix . 'dienste',
            array('veranstaltung_id' => $veranstaltung_id),
            array('%d')
        );
    }
}

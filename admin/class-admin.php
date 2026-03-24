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
        
        // Hook für Export: admin_init (reguläre Admin-Seiten) UND wp_ajax (AJAX-Aufrufe)
        add_action('admin_init', array($this, 'handle_export'));
        add_action('wp_ajax_dp_export_csv', array($this, 'handle_export'));
        
        // Admin-Notices
        add_action('admin_notices', array($this, 'show_admin_notices'));
        
        // WordPress-Footer-Banner ausblenden
        add_filter('admin_footer_text', '__return_empty_string');
        add_filter('update_footer', '__return_empty_string');
    }

    /**
     * Prüft, ob der aktuelle Benutzer uneingeschränkten Vereinszugriff hat.
     */
    private function has_unrestricted_club_access() {
        if (current_user_can('manage_options')) {
            return true;
        }

        // Haupt-Admin (Settings-Recht) ist ebenfalls uneingeschränkt.
        return current_user_can(Dienstplan_Roles::CAP_MANAGE_SETTINGS);
    }

    /**
     * Prüft, ob der aktuelle Benutzer ein eingeschränkter Vereins-Admin ist.
     */
    private function is_restricted_club_admin() {
        return Dienstplan_Roles::can_manage_clubs() && !$this->has_unrestricted_club_access();
    }

    /**
     * Liefert die dem aktuellen Benutzer zugeordneten Vereins-IDs.
     *
     * @param Dienstplan_Database $db
     * @return int[]
     */
    private function get_current_user_verein_ids($db) {
        $user_id = get_current_user_id();
        $verein_ids = array();

        // 1) Direkte Zuordnung aus dp_user_vereine
        $rows = $db->get_user_vereine($user_id);
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $verein_ids[] = intval($row->verein_id);
            }
        }

        // 2) Fallback: Zuordnung als Vereins-Verantwortlicher
        global $wpdb;
        $vv_table = $wpdb->prefix . $this->db_prefix . 'verein_verantwortliche';
        $vv_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT verein_id FROM {$vv_table} WHERE user_id = %d",
            $user_id
        ));
        if (!empty($vv_ids)) {
            foreach ($vv_ids as $vid) {
                $verein_ids[] = intval($vid);
            }
        }

        // 3) Fallback: Kontakt-E-Mail entspricht User-E-Mail
        $user = wp_get_current_user();
        if ($user && !empty($user->user_email)) {
            $vereine_table = $wpdb->prefix . $this->db_prefix . 'vereine';
            $mail_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT id FROM {$vereine_table} WHERE kontakt_email = %s",
                $user->user_email
            ));
            if (!empty($mail_ids)) {
                foreach ($mail_ids as $vid) {
                    $verein_ids[] = intval($vid);
                }
            }
        }

        $verein_ids = array_values(array_unique(array_filter($verein_ids)));
        sort($verein_ids);

        return $verein_ids;
    }

    /**
     * Liefert die für den aktuellen Benutzer sichtbaren Vereine.
     *
     * @param Dienstplan_Database $db
     * @param bool $active_only
     * @return array
     */
    private function get_scoped_vereine($db, $active_only = false) {
        if (!$this->is_restricted_club_admin()) {
            return $db->get_vereine($active_only);
        }

        $allowed_ids = $this->get_current_user_verein_ids($db);
        $vereine = array();

        foreach ($allowed_ids as $verein_id) {
            $verein = $db->get_verein($verein_id);
            if (!$verein) {
                continue;
            }

            if ($active_only && empty($verein->aktiv)) {
                continue;
            }

            $vereine[] = $verein;
        }

        return $vereine;
    }

    /**
     * Prüft, ob der aktuelle Benutzer auf eine Veranstaltung zugreifen darf.
     *
     * @param Dienstplan_Database $db
     * @param int $veranstaltung_id
     * @return bool
     */
    private function current_user_can_access_veranstaltung($db, $veranstaltung_id) {
        if (!$this->is_restricted_club_admin()) {
            return true;
        }

        $allowed_verein_ids = $this->get_current_user_verein_ids($db);
        if (empty($allowed_verein_ids)) {
            return false;
        }

        $veranstaltung_vereine = $db->get_veranstaltung_vereine($veranstaltung_id);
        if (!empty($veranstaltung_vereine)) {
            foreach ($veranstaltung_vereine as $verein) {
                if (in_array(intval($verein->verein_id), $allowed_verein_ids, true)) {
                    return true;
                }
            }
        }

        // Fallback über bereits angelegte Dienste dieser Veranstaltung
        $dienste = $db->get_dienste($veranstaltung_id);
        foreach ($dienste as $dienst) {
            if (in_array(intval($dienst->verein_id), $allowed_verein_ids, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Liefert die für den aktuellen Benutzer sichtbaren Veranstaltungen.
     *
     * @param Dienstplan_Database $db
     * @param array $filter
     * @return array
     */
    private function get_scoped_veranstaltungen($db, $filter = array()) {
        $veranstaltungen = $db->get_veranstaltungen($filter);

        if (!$this->is_restricted_club_admin()) {
            return $veranstaltungen;
        }

        return array_values(array_filter($veranstaltungen, function($veranstaltung) use ($db) {
            return $this->current_user_can_access_veranstaltung($db, intval($veranstaltung->id));
        }));
    }

    /**
     * Prüft, ob der aktuelle Benutzer auf einen Dienst zugreifen darf.
     *
     * @param Dienstplan_Database $db
     * @param int $dienst_id
     * @return bool
     */
    private function current_user_can_access_dienst($db, $dienst_id) {
        if (!$this->is_restricted_club_admin()) {
            return true;
        }

        $dienst = $db->get_dienst($dienst_id);
        if (!$dienst) {
            return false;
        }

        if (!empty($dienst->verein_id)) {
            return $this->current_user_can_access_verein($db, $dienst->verein_id);
        }

        return $this->current_user_can_access_veranstaltung($db, $dienst->veranstaltung_id);
    }

    /**
     * Prüft, ob der aktuelle Benutzer auf einen Mitarbeiter zugreifen darf.
     *
     * @param Dienstplan_Database $db
     * @param int $mitarbeiter_id
     * @return bool
     */
    private function current_user_can_access_mitarbeiter($db, $mitarbeiter_id) {
        if (!$this->is_restricted_club_admin()) {
            return true;
        }

        $allowed_verein_ids = $this->get_current_user_verein_ids($db);
        if (empty($allowed_verein_ids)) {
            return false;
        }

        $mitarbeiter_vereine = $db->get_mitarbeiter_vereine($mitarbeiter_id);
        if (!empty($mitarbeiter_vereine)) {
            foreach ($mitarbeiter_vereine as $verein) {
                if (in_array(intval($verein->verein_id), $allowed_verein_ids, true)) {
                    return true;
                }
            }
        }

        $dienste = $db->get_mitarbeiter_dienste($mitarbeiter_id);
        if (empty($dienste)) {
            return false;
        }

        foreach ($dienste as $dienst) {
            if (in_array(intval($dienst->verein_id), $allowed_verein_ids, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prüft, ob der aktuelle Benutzer auf einen Verein zugreifen darf.
     *
     * @param Dienstplan_Database $db
     * @param int $verein_id
     * @return bool
     */
    private function current_user_can_access_verein($db, $verein_id) {
        if (!$this->is_restricted_club_admin()) {
            return true;
        }

        $verein_ids = $this->get_current_user_verein_ids($db);
        return in_array(intval($verein_id), $verein_ids, true);
    }

    /**
     * Liefert die Mitarbeiter-IDs, die dem aktuellen WP-Benutzer gehoeren.
     * Primaer ueber mitarbeiter.user_id, Fallback ueber gleiche E-Mail.
     *
     * @param Dienstplan_Database $db
     * @return int[]
     */
    private function get_current_user_own_mitarbeiter_ids($db) {
        global $wpdb;

        $user_id = get_current_user_id();
        $table = $wpdb->prefix . $this->db_prefix . 'mitarbeiter';
        $ids = array();

        if ($user_id > 0) {
            $by_user_id = $wpdb->get_col($wpdb->prepare(
                "SELECT id FROM {$table} WHERE user_id = %d",
                $user_id
            ));

            if (!empty($by_user_id)) {
                foreach ($by_user_id as $id) {
                    $ids[] = intval($id);
                }
            }
        }

        if (empty($ids)) {
            $user = wp_get_current_user();
            if ($user && !empty($user->user_email)) {
                $by_email = $wpdb->get_col($wpdb->prepare(
                    "SELECT id FROM {$table} WHERE email = %s",
                    $user->user_email
                ));

                if (!empty($by_email)) {
                    foreach ($by_email as $id) {
                        $ids[] = intval($id);
                        if ($user_id > 0) {
                            $db->update_mitarbeiter(intval($id), array('user_id' => $user_id));
                        }
                    }
                }
            }
        }

        // Falls noch kein eigenes Mitarbeiterprofil existiert, lege fuer Vereins-Admins
        // ein minimales Profil an, damit die Self-Zuweisung im Modal moeglich bleibt.
        if (empty($ids) && $this->is_restricted_club_admin()) {
            $user = wp_get_current_user();
            if ($user && $user_id > 0 && !empty($user->user_email) && is_email($user->user_email)) {
                $first_name = trim((string) get_user_meta($user_id, 'first_name', true));
                $last_name = trim((string) get_user_meta($user_id, 'last_name', true));

                if ($first_name === '' && $last_name === '') {
                    $display_name = trim((string) $user->display_name);
                    if ($display_name !== '') {
                        $parts = preg_split('/\s+/', $display_name);
                        $first_name = trim((string) ($parts[0] ?? 'Verein'));
                        $last_name = trim((string) (count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'Admin'));
                    }
                }

                if ($first_name === '') {
                    $first_name = 'Verein';
                }
                if ($last_name === '') {
                    $last_name = 'Admin';
                }

                $new_id = $db->add_mitarbeiter(array(
                    'vorname' => $first_name,
                    'nachname' => $last_name,
                    'email' => sanitize_email($user->user_email),
                    'telefon' => ''
                ));

                if (!empty($new_id)) {
                    $db->update_mitarbeiter(intval($new_id), array('user_id' => $user_id));
                    $ids[] = intval($new_id);
                }
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }
    
    /**
     * Admin-Notices anzeigen
     *
     * @since 0.6.6
     */
    public function show_admin_notices() {
        // Portal-Setup-Notice nach Aktivierung
        if (get_transient('dienstplan_show_portal_setup')) {
            // Prüfe ob Portal-Seite bereits existiert
            $existing_pages = get_posts(array(
                'post_type' => 'page',
                'posts_per_page' => 1,
                'post_status' => 'any',
                's' => '[dienstplan_hub]'
            ));
            
            if (empty($existing_pages)) {
                ?>
                <div class="notice notice-success is-dismissible" id="dienstplan-portal-notice">
                    <h3 style="margin-top: 1em;">🎉 Dienstplan-Verwaltung erfolgreich aktiviert!</h3>
                    <p style="font-size: 14px;">
                        Möchten Sie jetzt eine <strong>Frontend-Portal-Seite</strong> erstellen? 
                        Diese bietet Ihren Benutzern eine moderne Einstiegsseite mit Login und Veranstaltungsübersicht.
                    </p>
                    <p>
                        <button type="button" class="button button-primary" id="dienstplan-create-portal-page">
                            <span class="dashicons dashicons-admin-page" style="margin-top: 3px;"></span>
                            Jetzt Portal-Seite erstellen
                        </button>
                        <button type="button" class="button" id="dienstplan-dismiss-portal-notice">
                            Später erstellen
                        </button>
                    </p>
                    <script>
                    jQuery(document).ready(function($) {
                        $('#dienstplan-create-portal-page').on('click', function() {
                            var btn = $(this);
                            btn.prop('disabled', true).text('Erstelle Seite...');
                            
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'dp_create_portal_page',
                                    nonce: '<?php echo wp_create_nonce('dp_create_portal_page'); ?>'
                                },
                                success: function(response) {
                                    if (response.success) {
                                        $('#dienstplan-portal-notice').html(
                                            '<h3>✅ Portal-Seite erfolgreich erstellt!</h3>' +
                                            '<p>Die Seite wurde erstellt: <strong>' + response.data.page_title + '</strong></p>' +
                                            '<p>' +
                                            '<a href="' + response.data.edit_url + '" class="button button-primary">Seite bearbeiten</a> ' +
                                            '<a href="' + response.data.view_url + '" class="button" target="_blank">Seite ansehen</a>' +
                                            '</p>'
                                        ).removeClass('notice-success').addClass('notice-info');
                                    } else {
                                        alert('Fehler: ' + (response.data.message || 'Unbekannter Fehler'));
                                        btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-page" style="margin-top: 3px;"></span> Jetzt Portal-Seite erstellen');
                                    }
                                },
                                error: function() {
                                    alert('Serverfehler beim Erstellen der Seite.');
                                    btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-page" style="margin-top: 3px;"></span> Jetzt Portal-Seite erstellen');
                                }
                            });
                        });
                        
                        $('#dienstplan-dismiss-portal-notice').on('click', function() {
                            $.post(ajaxurl, {
                                action: 'dp_dismiss_portal_notice',
                                nonce: '<?php echo wp_create_nonce('dp_dismiss_portal_notice'); ?>'
                            });
                            $('#dienstplan-portal-notice').fadeOut();
                        });
                    });
                    </script>
                </div>
                <?php
            } else {
                // Seite existiert bereits, transient löschen
                delete_transient('dienstplan_show_portal_setup');
            }
        }
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

            add_submenu_page(
                '',
                __('Statistik', 'dienstplan-verwaltung'),
                __('Statistik', 'dienstplan-verwaltung'),
                Dienstplan_Roles::CAP_MANAGE_EVENTS,
                'dienstplan-statistik',
                array($this, 'display_statistik')
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
        
        $can_access_import_export = Dienstplan_Roles::can_manage_clubs()
            || Dienstplan_Roles::can_manage_events()
            || Dienstplan_Roles::can_manage_settings()
            || current_user_can('manage_options');

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
            
        }

        // Import/Export - für alle mit Verwaltungsrechten
        if ($can_access_import_export) {
            add_submenu_page(
                '',
                __('Import', 'dienstplan-verwaltung'),
                __('Import', 'dienstplan-verwaltung'),
                'read', // Niedrigere Berechtigung, wird in Funktion genauer geprüft
                'dienstplan-import',
                array($this, 'display_import')
            );

            add_submenu_page(
                '',
                __('Export', 'dienstplan-verwaltung'),
                __('Export', 'dienstplan-verwaltung'),
                'read',
                'dienstplan-export',
                array($this, 'display_export')
            );

            add_submenu_page(
                '',
                __('Import/Export', 'dienstplan-verwaltung'),
                __('Import/Export', 'dienstplan-verwaltung'),
                'read',
                'dienstplan-import-export',
                array($this, 'display_import_export')
            );
        }
        
        // Benutzerverwaltung
        if (Dienstplan_Roles::can_manage_users() || current_user_can('manage_options')) {
            add_submenu_page(
                '',
                __('Admin-Benutzer', 'dienstplan-verwaltung'),
                __('Admin-Benutzer', 'dienstplan-verwaltung'),
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
        
        // Portal-Verwaltung - nur für WordPress-Admins
        if (current_user_can('manage_options')) {
            add_submenu_page(
                '',
                __('Portal-Verwaltung', 'dienstplan-verwaltung'),
                __('Portal-Verwaltung', 'dienstplan-verwaltung'),
                'manage_options',
                'dienstplan-portal',
                array($this, 'display_portal_verwaltung')
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
            'dienstplan-statistik' => __('Statistik', 'dienstplan-verwaltung'),
            'dienstplan-bereiche' => __('Bereiche & Tätigkeiten', 'dienstplan-verwaltung'),
            'dienstplan-mitarbeiter' => __('Mitarbeiter', 'dienstplan-verwaltung'),
            'dienstplan-dienste' => __('Dienste', 'dienstplan-verwaltung'),
            'dienstplan-overview' => __('Dienst-Übersicht', 'dienstplan-verwaltung'),
            'dienstplan-einstellungen' => __('Einstellungen', 'dienstplan-verwaltung'),
            'dienstplan-import' => __('Import', 'dienstplan-verwaltung'),
            'dienstplan-export' => __('Export', 'dienstplan-verwaltung'),
            'dienstplan-import-export' => __('Import/Export', 'dienstplan-verwaltung'),
            'dienstplan-benutzer' => __('Admin-Benutzer', 'dienstplan-verwaltung'),
            'dienstplan-dokumentation' => __('Dokumentation', 'dienstplan-verwaltung'),
            'dienstplan-updates' => __('Updates', 'dienstplan-verwaltung'),
            'dienstplan-portal' => __('Portal-Verwaltung', 'dienstplan-verwaltung'),
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
        
        // Dienste Tabelle CSS (für verbesserte Lesbarkeit)
        wp_enqueue_style(
            'dp-dienste-table-styles',
            DIENSTPLAN_PLUGIN_URL . 'assets/css/dp-dienste-table.css',
            array('dp-admin-styles'),
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
        $dp_vereine_modal_path = DIENSTPLAN_PLUGIN_PATH . 'assets/js/dp-vereine-modal.js';
        $dp_vereine_modal_version = $this->version;
        if (file_exists($dp_vereine_modal_path)) {
            $dp_vereine_modal_version .= '.' . filemtime($dp_vereine_modal_path);
        }
        wp_enqueue_script(
            'dp-vereine-modal',
            DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-vereine-modal.js',
            array('jquery', 'dp-admin-scripts'),
            $dp_vereine_modal_version,
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
        
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        if ($current_page === 'dienstplan-import-export') {
            wp_enqueue_script(
                'dp-import-export',
                DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-import-export.js',
                array('jquery', 'dp-admin-scripts'),
                $this->version,
                true
            );
        }

        if ($current_page === 'dienstplan-import') {
            $dp_import_path = DIENSTPLAN_PLUGIN_PATH . 'assets/js/dp-import.js';
            $dp_import_version = $this->version;
            if (file_exists($dp_import_path)) {
                $dp_import_version .= '.' . filemtime($dp_import_path);
            }
            wp_enqueue_script(
                'dp-import',
                DIENSTPLAN_PLUGIN_URL . 'assets/js/dp-import.js',
                array('jquery', 'dp-admin-scripts'),
                $dp_import_version,
                true
            );
        }
        
        // AJAX-Daten für JavaScript
        $localize_data = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dp_ajax_nonce'),
            'nonce_create_portal' => wp_create_nonce('dp_create_portal_page'),
            'nonce_delete_portal' => wp_create_nonce('dp_delete_portal_page'),
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

        $vereine = $this->get_scoped_vereine($db);

        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/vereine.php';
    }
    
    public function display_veranstaltungen() {
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        $veranstaltungen = $this->get_scoped_veranstaltungen($db);
        $vereine = $this->get_scoped_vereine($db); // Für Checkboxen im Modal
        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/veranstaltungen.php';
    }

    public function display_statistik() {
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);

        $veranstaltungen = $this->get_scoped_veranstaltungen($db);
        $selected_veranstaltung_id = isset($_GET['veranstaltung_id']) ? intval($_GET['veranstaltung_id']) : 0;

        $allowed_veranstaltung_ids = array_map(function($veranstaltung) {
            return intval($veranstaltung->id);
        }, $veranstaltungen);

        if ($selected_veranstaltung_id > 0 && !in_array($selected_veranstaltung_id, $allowed_veranstaltung_ids, true)) {
            $selected_veranstaltung_id = 0;
        }

        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/statistik.php';
    }
    
    public function display_users() {
        $all_users = get_users();
        $dp_users = Dienstplan_Roles::get_all_dp_users();
        $user_page_type = 'admins';
        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/benutzerverwaltung.php';
    }
    
    public function display_dienste() {
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);

        if (!empty($email)) {
            $existing_email_mitarbeiter = $db->get_mitarbeiter_by_email($email);
            if ($existing_email_mitarbeiter && intval($existing_email_mitarbeiter->id) !== intval($mitarbeiter_id)) {
                wp_send_json_error(array('message' => 'Ein Mitarbeiter mit dieser E-Mail-Adresse existiert bereits.'));
                return;
            }
        }

        $allowed_verein_ids = $this->is_restricted_club_admin() ? $this->get_current_user_verein_ids($db) : array();
        $veranstaltungen = $this->get_scoped_veranstaltungen($db);
        $vereine = $this->get_scoped_vereine($db);

        $allowed_veranstaltung_ids = array_map(function($veranstaltung) {
            return intval($veranstaltung->id);
        }, $veranstaltungen);

        $selected_veranstaltung = isset($_GET['veranstaltung']) ? intval($_GET['veranstaltung']) : 0;
        $selected_verein = isset($_GET['verein']) ? intval($_GET['verein']) : 0;

        if ($selected_veranstaltung > 0 && !in_array($selected_veranstaltung, $allowed_veranstaltung_ids, true)) {
            $_GET['veranstaltung'] = 0;
        }

        if (!empty($allowed_verein_ids) && $selected_verein > 0 && !in_array($selected_verein, $allowed_verein_ids, true)) {
            $_GET['verein'] = 0;
        }
        
        $bereiche = $db->get_bereiche(true); // nur aktive
        $taetigkeiten = $db->get_taetigkeiten(true); // nur aktive
        
        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/dienste.php';
    }
    
    public function display_overview() {
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);

        $allowed_verein_ids = $this->is_restricted_club_admin() ? $this->get_current_user_verein_ids($db) : array();
        $veranstaltungen = $this->get_scoped_veranstaltungen($db);
        $allowed_veranstaltung_ids = array_map(function($veranstaltung) {
            return intval($veranstaltung->id);
        }, $veranstaltungen);

        $selected_veranstaltung = isset($_GET['veranstaltung']) ? intval($_GET['veranstaltung']) : 0;
        if ($selected_veranstaltung > 0 && !in_array($selected_veranstaltung, $allowed_veranstaltung_ids, true)) {
            $_GET['veranstaltung'] = 0;
        }
        
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

        $allowed_verein_ids = $this->is_restricted_club_admin() ? $this->get_current_user_verein_ids($db) : array();
        $veranstaltungen = $this->get_scoped_veranstaltungen($db);
        $vereine = $this->get_scoped_vereine($db, true);

        $allowed_veranstaltung_ids = array_map(function($veranstaltung) {
            return intval($veranstaltung->id);
        }, $veranstaltungen);
        
        $filter_verein = isset($_GET['filter_verein']) ? intval($_GET['filter_verein']) : 0;
        $filter_veranstaltung = isset($_GET['filter_veranstaltung']) ? intval($_GET['filter_veranstaltung']) : 0;
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

        if (!empty($allowed_verein_ids) && $filter_verein > 0 && !in_array($filter_verein, $allowed_verein_ids, true)) {
            $_GET['filter_verein'] = 0;
            $filter_verein = 0;
        }

        if ($filter_veranstaltung > 0 && !in_array($filter_veranstaltung, $allowed_veranstaltung_ids, true)) {
            $_GET['filter_veranstaltung'] = 0;
            $filter_veranstaltung = 0;
        }
        
        $mitarbeiter = $db->get_mitarbeiter_with_stats($filter_verein, $filter_veranstaltung, $search, $allowed_verein_ids);
        
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
        
        $stats = array(
            'vereine' => $db->get_vereine(true),
            'bereiche' => $db->get_bereiche(false),
            'taetigkeiten' => $db->get_taetigkeiten(false),
            'veranstaltungen' => $db->get_veranstaltungen(),
            'dienste' => $db->get_dienste()
        );

        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/import-export.php';
    }

    public function display_import() {
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);

        $stats = array(
            'vereine' => $db->get_vereine(true),
            'bereiche' => $db->get_bereiche(false),
            'taetigkeiten' => $db->get_taetigkeiten(false),
            'veranstaltungen' => $db->get_veranstaltungen(),
            'dienste' => $db->get_dienste()
        );

        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/import.php';
    }

    public function display_export() {
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);

        $stats = array(
            'vereine' => $db->get_vereine(true),
            'bereiche' => $db->get_bereiche(false),
            'taetigkeiten' => $db->get_taetigkeiten(false),
            'veranstaltungen' => $db->get_veranstaltungen(),
            'dienste' => $db->get_dienste()
        );

        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/export.php';
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
    
    public function display_portal_verwaltung() {
        include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/portal-verwaltung.php';
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
            // Bestehende Installationen: neue Spalte farbe vor Save sicherstellen
            $db->migrate_vereine_add_farbe();

            $seite_id = !empty($_POST['seite_id']) ? intval($_POST['seite_id']) : null;
            if (!empty($seite_id)) {
                $seite = get_post($seite_id);
                if (!$seite || $seite->post_type !== 'page' || $seite->post_status === 'trash') {
                    wp_send_json_error(array('message' => 'Ungültige Vereinsseite ausgewählt'));
                    return;
                }

                $event_binding = get_post_meta($seite_id, '_dp_veranstaltung_id', true);
                if (!empty($event_binding)) {
                    wp_send_json_error(array('message' => 'Diese Seite ist bereits einer Veranstaltung zugeordnet und kann nicht als reine Vereinsseite verwendet werden'));
                    return;
                }
            }
            
            $data = array(
                'name' => sanitize_text_field($_POST['name']),
                'kuerzel' => strtoupper(sanitize_text_field($_POST['kuerzel'])),
                'farbe' => sanitize_hex_color($_POST['farbe'] ?? '#3b82f6') ?: '#3b82f6',
                'beschreibung' => sanitize_textarea_field($_POST['beschreibung'] ?? ''),
                'logo_id' => !empty($_POST['logo_id']) ? intval($_POST['logo_id']) : null,
                'kontakt_name' => sanitize_text_field($_POST['kontakt_name'] ?? ''),
                'kontakt_email' => sanitize_email($_POST['kontakt_email'] ?? ''),
                'kontakt_telefon' => sanitize_text_field($_POST['kontakt_telefon'] ?? ''),
                'seite_id' => $seite_id,
                'aktiv' => isset($_POST['aktiv']) ? 1 : 0
            );
            
            $verein_id = !empty($_POST['verein_id']) ? intval($_POST['verein_id']) : 0;

            if ($this->is_restricted_club_admin()) {
                // Eingeschränkte Vereins-Admins dürfen nur bestehende, zugeordnete Vereine bearbeiten.
                if ($verein_id <= 0) {
                    wp_send_json_error(array('message' => 'Keine Berechtigung: Vereins-Admins dürfen keine neuen Vereine anlegen.'));
                    return;
                }

                if (!$this->current_user_can_access_verein($db, $verein_id)) {
                    wp_send_json_error(array('message' => 'Keine Berechtigung für diesen Verein.'));
                    return;
                }
            }
            
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
                $this->sync_direct_verein_user_assignments($db, $verein_id, $verantwortliche);

                if (!empty($seite_id)) {
                    update_post_meta($seite_id, '_dp_verein_id', $verein_id);
                }
                
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
                $this->sync_direct_verein_user_assignments($db, $verein_id, $verantwortliche);

                if (!empty($seite_id)) {
                    update_post_meta($seite_id, '_dp_verein_id', $verein_id);
                }
                
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
        
        // Erlaube Zugriff für alle die Vereine oder Veranstaltungen verwalten dürfen
        if (!Dienstplan_Roles::can_manage_clubs() && !Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
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
        
        // Erlaube Zugriff für alle die Vereine oder Veranstaltungen verwalten dürfen
        if (!Dienstplan_Roles::can_manage_clubs() && !Dienstplan_Roles::can_manage_events()) {
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

        if (!$this->current_user_can_access_veranstaltung($db, $veranstaltung_id)) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diese Veranstaltung'));
            return;
        }
        
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

            if ($this->is_restricted_club_admin()) {
                wp_send_json_error(array('message' => 'Club-Admins dürfen Dienste nicht bearbeiten. Bitte nutzen Sie nur Zuteilung/Splitting.'));
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

            if (!$this->current_user_can_access_veranstaltung($db, $data['veranstaltung_id']) || !$this->current_user_can_access_verein($db, $data['verein_id'])) {
                wp_send_json_error(array('message' => 'Keine Berechtigung für Veranstaltung oder Verein'));
                return;
            }

            if ($dienst_id > 0 && !$this->current_user_can_access_dienst($db, $dienst_id)) {
                wp_send_json_error(array('message' => 'Keine Berechtigung für diesen Dienst'));
                return;
            }
            
            if ($dienst_id > 0) {
                // Update
                // Prüfe ob Slots neu erstellt werden müssen (bei Änderung von splittbar oder anzahl_personen)
                global $wpdb;
                $table_dienste = $wpdb->prefix . $this->db_prefix . 'dienste';
                $old_dienst = $wpdb->get_row($wpdb->prepare("SELECT splittbar, anzahl_personen FROM {$table_dienste} WHERE id = %d", $dienst_id));
                $preserved_assignments = 0;

                if ($old_dienst && ($old_dienst->splittbar != $data['splittbar'] || $old_dienst->anzahl_personen != $data['anzahl_personen'])) {
                    $existing_slots = $db->get_dienst_slots($dienst_id);
                    $assigned_mitarbeiter_ids = array();
                    foreach ($existing_slots as $existing_slot) {
                        $mitarbeiter_id = intval($existing_slot->mitarbeiter_id ?? 0);
                        if ($mitarbeiter_id > 0) {
                            $assigned_mitarbeiter_ids[] = $mitarbeiter_id;
                        }
                    }
                    $assigned_mitarbeiter_ids = array_values(array_unique($assigned_mitarbeiter_ids));

                    if (!empty($data['splittbar']) && count($assigned_mitarbeiter_ids) > 2) {
                        wp_send_json_error(array('message' => 'Dienst kann nicht auf Split umgestellt werden, solange mehr als zwei Personen zugewiesen sind.'));
                        return;
                    }

                    if (empty($data['splittbar']) && count($assigned_mitarbeiter_ids) > intval($data['anzahl_personen'])) {
                        $data['anzahl_personen'] = count($assigned_mitarbeiter_ids);
                    }
                }
                
                $result = $db->update_dienst($dienst_id, $data);
                $message = 'Dienst aktualisiert';
                $return_id = $dienst_id;
                
                // Wenn splittbar oder anzahl_personen geändert wurde, Slots neu erstellen
                if ($old_dienst && ($old_dienst->splittbar != $data['splittbar'] || $old_dienst->anzahl_personen != $data['anzahl_personen'])) {
                    $existing_slots = isset($existing_slots) ? $existing_slots : $db->get_dienst_slots($dienst_id);
                    $assigned_mitarbeiter_ids = array();
                    foreach ($existing_slots as $existing_slot) {
                        $mitarbeiter_id = intval($existing_slot->mitarbeiter_id ?? 0);
                        if ($mitarbeiter_id > 0) {
                            $assigned_mitarbeiter_ids[] = $mitarbeiter_id;
                        }
                    }
                    $assigned_mitarbeiter_ids = array_values(array_unique($assigned_mitarbeiter_ids));

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

                    if (!empty($assigned_mitarbeiter_ids)) {
                        $new_slots = $db->get_dienst_slots($dienst_id);
                        usort($new_slots, function($a, $b) {
                            return intval($a->slot_nummer ?? 0) <=> intval($b->slot_nummer ?? 0);
                        });

                        foreach ($assigned_mitarbeiter_ids as $index => $mitarbeiter_id) {
                            if (!isset($new_slots[$index])) {
                                break;
                            }

                            $assign_result = $db->assign_mitarbeiter_to_slot(intval($new_slots[$index]->id), intval($mitarbeiter_id));
                            if (is_array($assign_result) && isset($assign_result['error'])) {
                                wp_send_json_error(array('message' => $assign_result['message']));
                                return;
                            }
                            if ($assign_result === false) {
                                wp_send_json_error(array('message' => 'Dienst wurde geändert, aber eine bestehende Zuweisung konnte nicht übernommen werden.'));
                                return;
                            }
                            $preserved_assignments++;
                        }
                    }
                }

                if ($preserved_assignments > 0) {
                    $message .= ' - bestehende Belegungen wurden übernommen';
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

        if ($this->is_restricted_club_admin()) {
            wp_send_json_error(array('message' => 'Club-Admins dürfen Dienste nicht bearbeiten.'));
            return;
        }
        
        $dienst_id = isset($_POST['dienst_id']) ? intval($_POST['dienst_id']) : 0;
        
        if ($dienst_id <= 0) {
            wp_send_json_error(array('message' => 'Keine Dienst-ID angegeben'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);

        if (!$this->current_user_can_access_dienst($db, $dienst_id)) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diesen Dienst'));
            return;
        }
        
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

        if ($this->is_restricted_club_admin()) {
            wp_send_json_error(array('message' => 'Club-Admins dürfen Dienste nicht löschen.'));
            return;
        }
        
        $dienst_id = isset($_POST['dienst_id']) ? intval($_POST['dienst_id']) : 0;
        
        if ($dienst_id <= 0) {
            wp_send_json_error(array('message' => 'Keine Dienst-ID angegeben'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);

        if (!$this->current_user_can_access_dienst($db, $dienst_id)) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diesen Dienst'));
            return;
        }
        
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

        if ($this->is_restricted_club_admin()) {
            wp_send_json_error(array('message' => 'Club-Admins dürfen Dienste nicht kopieren.'));
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

        if (!$this->current_user_can_access_dienst($db, $dienst_id)) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diesen Dienst'));
            return;
        }
        
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

    /**
     * AJAX: Dienst in 2 Halbdienste splitten (Slots neu aufteilen)
     */
    public function ajax_split_dienst() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');

        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung zum Splitten von Diensten'));
            return;
        }

        $dienst_id = isset($_POST['dienst_id']) ? intval($_POST['dienst_id']) : 0;
        if ($dienst_id <= 0) {
            wp_send_json_error(array('message' => 'Keine Dienst-ID angegeben'));
            return;
        }

        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);

        if (!$this->current_user_can_access_dienst($db, $dienst_id)) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diesen Dienst'));
            return;
        }

        $dienst = $db->get_dienst($dienst_id);
        if (!$dienst) {
            wp_send_json_error(array('message' => 'Dienst nicht gefunden'));
            return;
        }

        if (intval($dienst->splittbar) === 1) {
            wp_send_json_success(array('message' => 'Dienst ist bereits gesplittet'));
            return;
        }

        if (empty($dienst->von_zeit) || empty($dienst->bis_zeit)) {
            wp_send_json_error(array('message' => 'Dienst ohne gültige Zeiten kann nicht gesplittet werden'));
            return;
        }

        $slots = $db->get_dienst_slots($dienst_id);
        $assigned_mitarbeiter_ids = array();
        foreach ($slots as $slot) {
            $mid = intval($slot->mitarbeiter_id ?? 0);
            if ($mid > 0) {
                $assigned_mitarbeiter_ids[] = $mid;
            }
        }
        $assigned_mitarbeiter_ids = array_values(array_unique($assigned_mitarbeiter_ids));

        if (count($assigned_mitarbeiter_ids) > 2) {
            wp_send_json_error(array('message' => 'Dienst kann nicht gesplittet werden, solange mehr als zwei Personen zugewiesen sind'));
            return;
        }

        global $wpdb;
        $table_slots = $wpdb->prefix . $this->db_prefix . 'dienst_slots';
        $wpdb->delete($table_slots, array('dienst_id' => $dienst_id), array('%d'));

        $update_result = $db->update_dienst($dienst_id, array('splittbar' => 1));
        if ($update_result === false) {
            wp_send_json_error(array('message' => 'Fehler beim Aktualisieren des Dienstes'));
            return;
        }

        $slot_data = array(
            'von_zeit' => $dienst->von_zeit,
            'bis_zeit' => $dienst->bis_zeit,
            'bis_datum' => $dienst->bis_datum,
            'anzahl_personen' => 1,
            'splittbar' => 1
        );
        $this->create_dienst_slots_for_copy($dienst_id, $slot_data);
        $new_slots = $db->get_dienst_slots($dienst_id);

        if (!empty($assigned_mitarbeiter_ids)) {
            usort($new_slots, function($a, $b) {
                return intval($a->slot_nummer ?? 0) <=> intval($b->slot_nummer ?? 0);
            });

            foreach ($assigned_mitarbeiter_ids as $index => $mitarbeiter_id) {
                if (!isset($new_slots[$index])) {
                    break;
                }

                $assign_result = $db->assign_mitarbeiter_to_slot(intval($new_slots[$index]->id), intval($mitarbeiter_id));
                if (is_array($assign_result) && isset($assign_result['error'])) {
                    wp_send_json_error(array('message' => $assign_result['message']));
                    return;
                }
                if ($assign_result === false) {
                    wp_send_json_error(array('message' => 'Dienst wurde gesplittet, aber eine bestehende Zuweisung konnte nicht übernommen werden'));
                    return;
                }
            }
        }

        wp_send_json_success(array(
            'message' => !empty($assigned_mitarbeiter_ids)
                ? 'Dienst wurde erfolgreich in Halbdienste gesplittet und bestehende Zuweisungen wurden übernommen'
                : 'Dienst wurde erfolgreich in Halbdienste gesplittet',
            'is_split' => true,
            'slot_count' => count($new_slots),
            'assigned_count' => count($assigned_mitarbeiter_ids)
        ));
    }

    /**
     * AJAX: Dienst-Split aufheben (Halbdienste zurück zu normalem Dienst)
     */
    public function ajax_unsplit_dienst() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');

        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung zum Aufheben von Splits'));
            return;
        }

        $dienst_id = isset($_POST['dienst_id']) ? intval($_POST['dienst_id']) : 0;
        if ($dienst_id <= 0) {
            wp_send_json_error(array('message' => 'Keine Dienst-ID angegeben'));
            return;
        }

        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);

        if (!$this->current_user_can_access_dienst($db, $dienst_id)) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diesen Dienst'));
            return;
        }

        $dienst = $db->get_dienst($dienst_id);
        if (!$dienst) {
            wp_send_json_error(array('message' => 'Dienst nicht gefunden'));
            return;
        }

        $slots = $db->get_dienst_slots($dienst_id);
        $is_split_dienst = (intval($dienst->splittbar ?? 0) === 1) || (count($slots) >= 2);

        if (!$is_split_dienst) {
            wp_send_json_error(array('message' => 'Dienst ist nicht gesplittet'));
            return;
        }

        $assigned_mitarbeiter_ids = array();
        foreach ($slots as $slot) {
            $mid = intval($slot->mitarbeiter_id ?? 0);
            if ($mid > 0) {
                $assigned_mitarbeiter_ids[] = $mid;
            }
        }
        $assigned_mitarbeiter_ids = array_values(array_unique($assigned_mitarbeiter_ids));

        global $wpdb;
        $table_slots = $wpdb->prefix . $this->db_prefix . 'dienst_slots';
        $wpdb->delete($table_slots, array('dienst_id' => $dienst_id), array('%d'));

        $new_anzahl_personen = max(intval($dienst->anzahl_personen), count($assigned_mitarbeiter_ids));

        // Setze splittbar zurück auf 0 und passe Personenanzahl ggf. an,
        // damit bestehende Zuweisungen beim Zusammenführen erhalten bleiben.
        $update_result = $db->update_dienst($dienst_id, array(
            'splittbar' => 0,
            'anzahl_personen' => $new_anzahl_personen
        ));
        if ($update_result === false) {
            wp_send_json_error(array('message' => 'Fehler beim Aktualisieren des Dienstes'));
            return;
        }

        // Erstelle neue Slots mit splittbar = 0
        $slot_data = array(
            'von_zeit' => $dienst->von_zeit,
            'bis_zeit' => $dienst->bis_zeit,
            'bis_datum' => $dienst->bis_datum,
            'anzahl_personen' => $new_anzahl_personen,
            'splittbar' => 0
        );
        $this->create_dienst_slots_for_copy($dienst_id, $slot_data);

        if (!empty($assigned_mitarbeiter_ids)) {
            $new_slots = $db->get_dienst_slots($dienst_id);
            usort($new_slots, function($a, $b) {
                return intval($a->slot_nummer ?? 0) <=> intval($b->slot_nummer ?? 0);
            });

            foreach ($assigned_mitarbeiter_ids as $index => $mitarbeiter_id) {
                if (!isset($new_slots[$index])) {
                    break;
                }

                $assign_result = $db->assign_mitarbeiter_to_slot(intval($new_slots[$index]->id), intval($mitarbeiter_id));
                if (is_array($assign_result) && isset($assign_result['error'])) {
                    wp_send_json_error(array('message' => $assign_result['message']));
                    return;
                }
                if ($assign_result === false) {
                    wp_send_json_error(array('message' => 'Split wurde aufgehoben, aber eine Zuweisung konnte nicht übernommen werden'));
                    return;
                }
            }
        }

        $msg = 'Split wurde erfolgreich aufgehoben';
        if (!empty($assigned_mitarbeiter_ids)) {
            $msg .= ' und bestehende Zuweisungen wurden übernommen';
        }
        wp_send_json_success(array(
            'message' => $msg,
            'is_split' => false,
            'slot_count' => $new_anzahl_personen,
            'assigned_count' => count($assigned_mitarbeiter_ids)
        ));
    }
    
    public function ajax_create_bereich() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }

        if ($this->is_restricted_club_admin()) {
            wp_send_json_error(array('message' => 'Club-Admins dürfen Bereiche nicht bearbeiten.'));
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
        
        if (!Dienstplan_Roles::can_manage_clubs()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $verein_id = intval($_POST['verein_id']);

        if (!$this->current_user_can_access_verein($db, $verein_id)) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diesen Verein.'));
            return;
        }
        
        // Lösche WordPress-Seiten die zu diesem Verein gehören
        $pages = get_posts(array(
            'post_type' => 'page',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_dp_verein_id',
                    'value' => $verein_id,
                    'compare' => '='
                )
            ),
            'fields' => 'ids'
        ));
        
        $pages_deleted = 0;
        foreach ($pages as $page_id) {
            if (wp_delete_post($page_id, true)) {
                $pages_deleted++;
            }
        }
        
        // Lösche Verein aus Datenbank
        $result = $db->delete_verein($verein_id);
        
        if ($result) {
            $message = 'Verein gelöscht';
            if ($pages_deleted > 0) {
                $message .= sprintf(' (%d Seite(n) ebenfalls gelöscht)', $pages_deleted);
            }
            wp_send_json_success(array('message' => $message, 'pages_deleted' => $pages_deleted));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Löschen'));
        }
    }
    
    public function ajax_get_verein() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_clubs()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $verein_id = intval($_POST['verein_id']);

        if (!$this->current_user_can_access_verein($db, $verein_id)) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diesen Verein.'));
            return;
        }

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
            
            if (!Dienstplan_Roles::can_manage_events()) {
                error_log('Keine Berechtigung');
                wp_send_json_error(array('message' => 'Keine Berechtigung'));
                return;
            }
            
            require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
            $db = new Dienstplan_Database($this->db_prefix);
            
            $veranstaltung_id = isset($_POST['veranstaltung_id']) ? intval($_POST['veranstaltung_id']) : 0;
            if ($veranstaltung_id > 0 && !$this->current_user_can_access_veranstaltung($db, $veranstaltung_id)) {
                wp_send_json_error(array('message' => 'Keine Berechtigung für diese Veranstaltung'));
                return;
            }

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

            $selected_vereine = array();
            if (isset($_POST['vereine']) && !empty($_POST['vereine'])) {
                $selected_vereine = json_decode(stripslashes($_POST['vereine']), true);
                $selected_vereine = is_array($selected_vereine) ? array_map('intval', $selected_vereine) : array();
            }

            if ($this->is_restricted_club_admin()) {
                $allowed_verein_ids = $this->get_current_user_verein_ids($db);
                foreach ($selected_vereine as $verein_id) {
                    if (!in_array($verein_id, $allowed_verein_ids, true)) {
                        wp_send_json_error(array('message' => 'Keine Berechtigung für einen oder mehrere ausgewählte Vereine'));
                        return;
                    }
                }
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
                $vereine = $selected_vereine;
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
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $veranstaltung_id = intval($_POST['veranstaltung_id']);

        if (!$this->current_user_can_access_veranstaltung($db, $veranstaltung_id)) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diese Veranstaltung'));
            return;
        }
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
        
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $veranstaltung_id = intval($_POST['veranstaltung_id']);

        if (!$this->current_user_can_access_veranstaltung($db, $veranstaltung_id)) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diese Veranstaltung'));
            return;
        }
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
        
        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
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
        
        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
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
        
        // Mitarbeiter-Auswahlliste: Vereins-Admins sehen nur Mitarbeiter im eigenen Scope.
        global $wpdb;
        if ($this->is_restricted_club_admin()) {
            $all_mitarbeiter = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dp_mitarbeiter ORDER BY vorname, nachname");
            $mitarbeiter = array();

            foreach ((array) $all_mitarbeiter as $row) {
                if ($this->current_user_can_access_mitarbeiter($db, intval($row->id))) {
                    $mitarbeiter[] = $row;
                }
            }
        } else {
            $mitarbeiter = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dp_mitarbeiter ORDER BY vorname, nachname");
        }
        
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

        if (!$this->current_user_can_access_dienst($db, intval($slot->dienst_id))) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diesen Dienst'));
            return;
        }

        if ($this->is_restricted_club_admin() && !$this->current_user_can_access_mitarbeiter($db, $mitarbeiter_id)) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diesen Mitarbeiter.'));
            return;
        }
        
        // Prüfe ob Slot schon besetzt ist (nur wenn nicht force_replace)
        if (!$force_replace && $slot->mitarbeiter_id && $slot->mitarbeiter_id > 0) {
            wp_send_json_error(array('message' => 'Slot ist bereits besetzt'));
            return;
        }
        
        // Zuweisung durchführen (oder ersetzen)
        $result = $db->assign_mitarbeiter_to_slot($slot_id, $mitarbeiter_id, (bool) $force_replace);

        if (is_array($result) && isset($result['error'])) {
            wp_send_json_error(array('message' => $result['message']));
            return;
        }
        
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

        if (!$this->current_user_can_access_mitarbeiter($db, $mitarbeiter_id)) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diesen Mitarbeiter'));
            return;
        }
        
        if (!$mitarbeiter) {
            wp_send_json_error(array('message' => 'Mitarbeiter nicht gefunden'));
            return;
        }

        $allowed_vereine = $this->get_scoped_vereine($db, true);
        $mitarbeiter->vereine = $db->get_mitarbeiter_vereine($mitarbeiter_id);
        $mitarbeiter->allowed_vereine = $allowed_vereine;
        $mitarbeiter->verein_ids = array_map(function($row) {
            return intval($row->verein_id);
        }, $mitarbeiter->vereine);
        
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

        $allowed_verein_ids = $this->is_restricted_club_admin() ? $this->get_current_user_verein_ids($db) : array();
        $requested_verein_ids = isset($_POST['verein_ids']) ? (array) $_POST['verein_ids'] : array();
        $requested_verein_ids = array_values(array_unique(array_filter(array_map('intval', $requested_verein_ids))));

        if (!empty($allowed_verein_ids)) {
            $requested_verein_ids = array_values(array_filter($requested_verein_ids, function($verein_id) use ($allowed_verein_ids) {
                return in_array(intval($verein_id), $allowed_verein_ids, true);
            }));
        }

        if ($mitarbeiter_id > 0 && !$this->current_user_can_access_mitarbeiter($db, $mitarbeiter_id)) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diesen Mitarbeiter'));
            return;
        }
        
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

        if ($result !== false && $return_id) {
            $sync_result = $db->sync_mitarbeiter_vereine($return_id, $requested_verein_ids);
            if ($sync_result === false) {
                wp_send_json_error(array('message' => 'Mitarbeiter gespeichert, aber Vereinszuordnung konnte nicht aktualisiert werden'));
                return;
            }
        }
        
        // Portal-Zugriff aktivieren, falls angefordert (nur bei neuen ohne Zugang)
        $portal_access_requested = isset($_POST['portal_access']) && $_POST['portal_access'] == '1';
        $portal_message = '';
        
        if ($result !== false && $portal_access_requested && !empty($email)) {
            // Lade Mitarbeiter-Daten erneut
            $mitarbeiter = $db->get_mitarbeiter($return_id);
            
            // Prüfe ob bereits Portal-Zugriff besteht
            if ($mitarbeiter && !$mitarbeiter->user_id) {
                // Prüfe ob E-Mail bereits verwendet wird
                if (!email_exists($email)) {
                    // Generiere Username
                    $username_base = sanitize_user(strtolower($vorname . '_' . $nachname), true);
                    $username = $username_base;
                    $counter = 1;
                    
                    // Stelle sicher dass Username einzigartig ist
                    while (username_exists($username)) {
                        $username = $username_base . '_' . $counter;
                        $counter++;
                    }
                    
                    // Generiere temporäres Passwort
                    $password = wp_generate_password(12, true, true);
                    
                    // Erstelle WordPress-User
                    $user_id = wp_create_user($username, $password, $email);
                    
                    if (!is_wp_error($user_id)) {
                        // Setze Crew-Rolle
                        $user = new WP_User($user_id);
                        $user->set_role(Dienstplan_Roles::ROLE_CREW);
                        
                        // Update User-Meta
                        update_user_meta($user_id, 'first_name', $vorname);
                        update_user_meta($user_id, 'last_name', $nachname);
                        update_user_meta($user_id, 'show_admin_bar_front', false);
                        
                        // Verlinke Mitarbeiter mit User
                        $db->update_mitarbeiter($return_id, array('user_id' => $user_id));
                        $this->sync_user_verein_assignments($db, $return_id, $user_id);
                        
                        // Sende E-Mail mit Login-Daten
                        $portal_page_id = get_option('dienstplan_portal_page_id', 0);
                        $login_url = $portal_page_id ? get_permalink($portal_page_id) : wp_login_url();
                        
                        $email_subject = sprintf(__('[%s] Zugang zum Dienstplan-Portal', 'dienstplan-verwaltung'), get_bloginfo('name'));
                        
                        $email_body = sprintf(
                            __("Hallo %s,\n\nfür dich wurde ein Zugang zum Dienstplan-Portal erstellt.\n\nHier sind deine Login-Daten:\n\nBenutzername: %s\nPasswort: %s\n\nPortal-Link: %s\n\nBitte ändere dein Passwort nach dem ersten Login.\n\nViele Grüße\n%s", 'dienstplan-verwaltung'),
                            $vorname,
                            $username,
                            $password,
                            $login_url,
                            get_bloginfo('name')
                        );
                        
                        $email_sent = wp_mail($email, $email_subject, $email_body);
                        
                        if ($email_sent) {
                            $portal_message = ' Portal-Zugriff aktiviert und Login-Daten versendet.';
                        } else {
                            $portal_message = ' Portal-Zugriff aktiviert, aber E-Mail konnte nicht versendet werden.';
                        }
                    }
                }
            }
        }
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => $message . $portal_message,
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

        if (!$this->current_user_can_access_mitarbeiter($db, $mitarbeiter_id)) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diesen Mitarbeiter'));
            return;
        }
        
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

        if (!$this->current_user_can_access_mitarbeiter($db, $mitarbeiter_id)) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diesen Mitarbeiter'));
            return;
        }
        
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

            if (!$this->current_user_can_access_mitarbeiter($db, $id)) {
                $failed_count++;
                continue;
            }
            
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
        
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        
        // Granulare Berechtigungsprüfung basierend auf Export-Typ
        $can_export = false;
        
        switch ($type) {
            case 'vereine':
                $can_export = Dienstplan_Roles::can_manage_clubs() || current_user_can('manage_options');
                break;
            case 'bereiche':
            case 'taetigkeiten':
            case 'veranstaltungen':
            case 'dienste':
                $can_export = Dienstplan_Roles::can_manage_events() || current_user_can('manage_options');
                break;
            case 'all':
                $can_export = (Dienstplan_Roles::can_manage_clubs() || Dienstplan_Roles::can_manage_events() || current_user_can('manage_options'));
                break;
            default:
                $can_export = current_user_can('manage_options');
        }
        
        if (!$can_export) {
            wp_die('Keine Berechtigung für diesen Export-Typ');
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $filename = 'dienstplan-export-' . $type . '-' . date('Y-m-d') . '.csv';
        
        // Headers für Download setzen
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Hilfsfunktion: CSV-Inhalt für einen Typ als String erzeugen
        $build_csv = function($csv_type) use ($db, $can_manage_clubs, $can_manage_events) {
            if (!isset($can_manage_clubs)) {
                $can_manage_clubs = Dienstplan_Roles::can_manage_clubs() || current_user_can('manage_options');
            }
            if (!isset($can_manage_events)) {
                $can_manage_events = Dienstplan_Roles::can_manage_events() || current_user_can('manage_options');
            }

            ob_start();
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            switch ($csv_type) {
                case 'vereine':
                    if (!$can_manage_clubs) { fclose($out); ob_end_clean(); return ''; }
                    fputcsv($out, array('name', 'kuerzel', 'beschreibung', 'kontakt_name', 'kontakt_email', 'kontakt_telefon'), ';');
                    $rows = $db->get_vereine();
                    if ($rows) {
                        foreach ($rows as $r) {
                            fputcsv($out, array($r->name ?? '', $r->kuerzel ?? '', $r->beschreibung ?? '', $r->kontakt_name ?? '', $r->kontakt_email ?? '', $r->kontakt_telefon ?? ''), ';');
                        }
                    }
                    break;

                case 'bereiche':
                    if (!$can_manage_events) { fclose($out); ob_end_clean(); return ''; }
                    fputcsv($out, array('name', 'farbe', 'aktiv', 'sortierung', 'admin_only'), ';');
                    $rows = $db->get_bereiche(false);
                    if ($rows) {
                        foreach ($rows as $r) {
                            fputcsv($out, array($r->name ?? '', $r->farbe ?? '', isset($r->aktiv) ? (int)$r->aktiv : 1, isset($r->sortierung) ? (int)$r->sortierung : 999, isset($r->admin_only) ? (int)$r->admin_only : 0), ';');
                        }
                    }
                    break;

                case 'taetigkeiten':
                    if (!$can_manage_events) { fclose($out); ob_end_clean(); return ''; }
                    fputcsv($out, array('bereich_name', 'bereich_id', 'name', 'beschreibung', 'aktiv', 'sortierung', 'admin_only'), ';');
                    $rows = $db->get_taetigkeiten(false);
                    if ($rows) {
                        foreach ($rows as $r) {
                            fputcsv($out, array($r->bereich_name ?? '', $r->bereich_id ?? '', $r->name ?? '', $r->beschreibung ?? '', isset($r->aktiv) ? (int)$r->aktiv : 1, isset($r->sortierung) ? (int)$r->sortierung : 999, isset($r->admin_only) ? (int)$r->admin_only : 0), ';');
                        }
                    }
                    break;

                case 'veranstaltungen':
                    if (!$can_manage_events) { fclose($out); ob_end_clean(); return ''; }
                    fputcsv($out, array('name', 'start_datum', 'end_datum', 'beschreibung'), ';');
                    $rows = $db->get_veranstaltungen();
                    if ($rows) {
                        foreach ($rows as $r) {
                            fputcsv($out, array($r->name ?? '', $r->start_datum ?? '', $r->end_datum ?? '', $r->beschreibung ?? ''), ';');
                        }
                    }
                    break;

                case 'dienste':
                    if (!$can_manage_events) { fclose($out); ob_end_clean(); return ''; }
                    fputcsv($out, array('veranstaltung_id', 'veranstaltung_name', 'tag_nummer', 'verein_id', 'verein_name', 'bereich_id', 'bereich_name', 'taetigkeit_id', 'taetigkeit_name', 'von_zeit', 'bis_zeit', 'bis_datum', 'anzahl_personen', 'splittbar', 'status'), ';');
                    $rows = $db->get_dienste();
                    if ($rows) {
                        foreach ($rows as $r) {
                            $tag_nummer = '';
                            if (!empty($r->tag_id)) {
                                $tag = $db->get_veranstaltung_tag($r->tag_id);
                                if ($tag) { $tag_nummer = $tag->tag_nummer; }
                            }
                            fputcsv($out, array($r->veranstaltung_id ?? '', '', $tag_nummer, $r->verein_id ?? '', $r->verein_name ?? '', $r->bereich_id ?? '', $r->bereich_name ?? '', $r->taetigkeit_id ?? '', $r->taetigkeit_name ?? '', $r->von_zeit ?? '', $r->bis_zeit ?? '', $r->bis_datum ?? '', $r->anzahl_personen ?? '', $r->splittbar ? '1' : '0', $r->status ?? 'geplant'), ';');
                        }
                    }
                    break;
            }

            fclose($out);
            return ob_get_clean();
        };

        if ($type === 'all') {
            // ZIP mit allen CSVs erstellen
            if (!class_exists('ZipArchive')) {
                wp_die('ZipArchive nicht verfügbar. Bitte exportieren Sie die Typen einzeln.');
            }

            $tmpfile = tempnam(sys_get_temp_dir(), 'dp_exp_');
            $zip = new ZipArchive();
            if ($zip->open($tmpfile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                wp_die('ZIP-Erstellung fehlgeschlagen.');
            }

            $can_manage_clubs = Dienstplan_Roles::can_manage_clubs() || current_user_can('manage_options');
            $can_manage_events = Dienstplan_Roles::can_manage_events() || current_user_can('manage_options');

            $export_types = array();
            if ($can_manage_clubs) {
                $export_types[] = 'vereine';
            }
            if ($can_manage_events) {
                $export_types = array_merge($export_types, array('bereiche', 'taetigkeiten', 'veranstaltungen', 'dienste'));
            }

            foreach ($export_types as $t) {
                $csv_content = $build_csv($t);
                $zip->addFromString('dienstplan-' . $t . '-' . date('Y-m-d') . '.csv', $csv_content);
            }

            $zip->close();

            $zip_filename = 'dienstplan-export-' . date('Y-m-d') . '.zip';
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename=' . $zip_filename);
            header('Content-Length: ' . filesize($tmpfile));
            header('Pragma: no-cache');
            header('Expires: 0');
            readfile($tmpfile);
            @unlink($tmpfile);
            exit;
        }

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
                
            case 'bereiche':
                fputcsv($output, array('name', 'farbe', 'aktiv', 'sortierung', 'admin_only'), ';');
                $data = $db->get_bereiche(false);
                if ($data) {
                    foreach ($data as $row) {
                        fputcsv($output, array(
                            $row->name ?? '',
                            $row->farbe ?? '',
                            isset($row->aktiv) ? (int)$row->aktiv : 1,
                            isset($row->sortierung) ? (int)$row->sortierung : 999,
                            isset($row->admin_only) ? (int)$row->admin_only : 0
                        ), ';');
                    }
                }
                break;

            case 'taetigkeiten':
                fputcsv($output, array('bereich_name', 'bereich_id', 'name', 'beschreibung', 'aktiv', 'sortierung', 'admin_only'), ';');
                $data = $db->get_taetigkeiten(false);
                if ($data) {
                    foreach ($data as $row) {
                        fputcsv($output, array(
                            $row->bereich_name ?? '',
                            $row->bereich_id ?? '',
                            $row->name ?? '',
                            $row->beschreibung ?? '',
                            isset($row->aktiv) ? (int)$row->aktiv : 1,
                            isset($row->sortierung) ? (int)$row->sortierung : 999,
                            isset($row->admin_only) ? (int)$row->admin_only : 0
                        ), ';');
                    }
                }
                break;

            case 'veranstaltungen':
                fputcsv($output, array('name', 'start_datum', 'end_datum', 'beschreibung'), ';');
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
        
        $import_type = isset($_POST['import_type']) ? sanitize_text_field($_POST['import_type']) : '';
        
        // Granulare Berechtigungsprüfung basierend auf Import-Typ
        $can_import = false;
        
        switch ($import_type) {
            case 'vereine':
                $can_import = Dienstplan_Roles::can_manage_clubs() || current_user_can('manage_options');
                break;
            case 'dienstplan':
            case 'bereiche':
            case 'taetigkeiten':
            case 'veranstaltungen':
            case 'dienste':
                $can_import = Dienstplan_Roles::can_manage_events() || current_user_can('manage_options');
                break;
            default:
                $can_import = current_user_can('manage_options'); // Nur WP-Admin für unbekannte Typen
        }
        
        if (!$can_import) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diesen Import-Typ'));
            return;
        }
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
        $unresolved_vereine = array();
        $unresolved_verein_keys = array();
        $unresolved_verein_indices = array();

        $verein_alias_map = get_option('dp_import_verein_aliases', array());
        if (!is_array($verein_alias_map)) {
            $verein_alias_map = array();
        }

        // Normalisiere Alias-Map auf saubere Großschreibung
        $normalized_alias_map = array();
        foreach ($verein_alias_map as $from => $to) {
            $from_clean = strtoupper(trim((string) $from));
            $to_clean = strtoupper(trim((string) $to));
            if ($from_clean !== '' && $to_clean !== '') {
                $normalized_alias_map[$from_clean] = $to_clean;
            }
        }
        $verein_alias_map = $normalized_alias_map;
        
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

        $parse_bool = function($value, $default = 0) {
            if ($value === null || $value === '') {
                return (int) $default;
            }

            $value = strtolower(trim((string) $value));
            if (in_array($value, array('1', 'true', 'yes', 'ja', 'y', 'on'), true)) {
                return 1;
            }
            if (in_array($value, array('0', 'false', 'no', 'nein', 'n', 'off'), true)) {
                return 0;
            }

            return (int) $default;
        };

        $make_verein_kuerzel = function($verein_name, $vereine_by_kuerzel) {
            $verein_name = trim((string) $verein_name);
            if ($verein_name === '') {
                return '';
            }

            $normalized_name = remove_accents($verein_name);
            $normalized_name = preg_replace('/[^A-Za-z0-9\s]/', ' ', $normalized_name);
            $parts = preg_split('/\s+/', trim($normalized_name));
            $parts = array_values(array_filter($parts));

            $base = '';
            if (count($parts) >= 2) {
                foreach ($parts as $part) {
                    $base .= strtoupper(substr($part, 0, 1));
                }
            } else {
                $collapsed = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $normalized_name));
                $base = substr($collapsed, 0, 6);
            }

            if ($base === '') {
                $base = 'VRN';
            }

            $candidate = $base;
            $suffix = 2;
            while (isset($vereine_by_kuerzel[$candidate])) {
                $candidate = substr($base, 0, 4) . $suffix;
                $suffix++;
            }

            return $candidate;
        };

        $register_unresolved_verein = function($key_hash, $payload, $dienst_id = 0) use (&$unresolved_vereine, &$unresolved_verein_indices) {
            if ($key_hash === '' || !is_array($payload)) {
                return;
            }

            if (!isset($unresolved_verein_indices[$key_hash])) {
                $payload['dienst_ids'] = array();
                $payload['rows'] = !empty($payload['row']) ? array(intval($payload['row'])) : array();
                $unresolved_vereine[] = $payload;
                $unresolved_verein_indices[$key_hash] = count($unresolved_vereine) - 1;
            }

            $entry_index = $unresolved_verein_indices[$key_hash];

            if (!empty($payload['row'])) {
                $row_number = intval($payload['row']);
                if ($row_number > 0 && !in_array($row_number, $unresolved_vereine[$entry_index]['rows'], true)) {
                    $unresolved_vereine[$entry_index]['rows'][] = $row_number;
                }
            }

            if ($dienst_id > 0 && !in_array($dienst_id, $unresolved_vereine[$entry_index]['dienst_ids'], true)) {
                $unresolved_vereine[$entry_index]['dienst_ids'][] = intval($dienst_id);
            }
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
                    
                    $raw_end_date = !empty($data['end_datum']) ? $data['end_datum'] : (isset($data['ende_datum']) ? $data['ende_datum'] : '');

                    if (empty($data['name']) || empty($data['start_datum']) || empty($raw_end_date)) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Pflichtfelder fehlen (Name, Start-Datum, End-Datum)";
                        continue;
                    }
                    
                    // Datum-Validierung mit intelligenter Erkennung
                    $start_timestamp = $parse_date($data['start_datum']);
                    $ende_timestamp = $parse_date($raw_end_date);
                    
                    if ($start_timestamp === false) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Ungültiges Start-Datum '{$data['start_datum']}'";
                        continue;
                    }
                    
                    if ($ende_timestamp === false) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Ungültiges End-Datum '{$raw_end_date}'";
                        continue;
                    }
                    
                    // Datum in MySQL-Format konvertieren
                    $data['start_datum'] = date('Y-m-d', $start_timestamp);
                    $data['end_datum'] = date('Y-m-d', $ende_timestamp);
                    unset($data['ende_datum']);
                    
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

            case 'bereiche':
                $row_number = 1;
                $existing_bereiche = $db->get_bereiche(false);
                $bereiche_by_name = array();

                foreach ($existing_bereiche as $bereich) {
                    $bereich_name_key = strtolower(trim((string) $bereich->name));
                    if ($bereich_name_key !== '') {
                        $bereiche_by_name[$bereich_name_key] = $bereich;
                    }
                }

                foreach ($csv_data as $row) {
                    $row_number++;
                    $current_unresolved_key_hash = '';
                    $current_unresolved_payload = null;

                    $data = array();
                    foreach ($mapping as $field => $csvIndex) {
                        $data[$field] = isset($row[$csvIndex]) ? trim($row[$csvIndex]) : '';
                    }

                    if (empty($data['name'])) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Pflichtfeld fehlt (Name)";
                        continue;
                    }

                    $name = sanitize_text_field($data['name']);
                    $name_key = strtolower($name);

                    $bereich_data = array(
                        'name' => $name,
                        'farbe' => !empty($data['farbe']) ? sanitize_hex_color($data['farbe']) : null,
                        'aktiv' => $parse_bool(isset($data['aktiv']) ? $data['aktiv'] : '', 1),
                        'sortierung' => isset($data['sortierung']) && $data['sortierung'] !== '' ? max(0, intval($data['sortierung'])) : 999,
                        'admin_only' => $parse_bool(isset($data['admin_only']) ? $data['admin_only'] : '', 0)
                    );

                    if (empty($bereich_data['farbe'])) {
                        $bereich_data['farbe'] = '#3b82f6';
                    }

                    $existing = isset($bereiche_by_name[$name_key]) ? $bereiche_by_name[$name_key] : null;

                    if ($existing && $import_mode === 'update') {
                        $result = $db->update_bereich($existing->id, $bereich_data);
                        if ($result !== false) {
                            $updated++;
                            $existing->name = $bereich_data['name'];
                            $existing->farbe = $bereich_data['farbe'];
                            $existing->aktiv = $bereich_data['aktiv'];
                            $existing->sortierung = $bereich_data['sortierung'];
                            $existing->admin_only = $bereich_data['admin_only'];
                            $bereiche_by_name[$name_key] = $existing;
                        } else {
                            $errors++;
                            $error_details[] = "Zeile {$row_number}: Fehler beim Aktualisieren von Bereich '{$name}'";
                        }
                    } elseif (!$existing) {
                        $result = $db->add_bereich($bereich_data);
                        if ($result !== false) {
                            $created++;
                            $bereiche_by_name[$name_key] = (object) array(
                                'id' => $result,
                                'name' => $bereich_data['name'],
                                'farbe' => $bereich_data['farbe'],
                                'aktiv' => $bereich_data['aktiv'],
                                'sortierung' => $bereich_data['sortierung'],
                                'admin_only' => $bereich_data['admin_only']
                            );
                        } else {
                            $errors++;
                            $error_details[] = "Zeile {$row_number}: Fehler beim Erstellen von Bereich '{$name}'";
                        }
                    } else {
                        $skipped++;
                    }
                }
                break;

            case 'taetigkeiten':
                $row_number = 1;
                $existing_bereiche = $db->get_bereiche(false);
                $bereiche_by_name = array();
                $bereiche_by_id = array();

                foreach ($existing_bereiche as $bereich) {
                    $bereiche_by_id[(int) $bereich->id] = $bereich;
                    $bereich_name_key = strtolower(trim((string) $bereich->name));
                    if ($bereich_name_key !== '') {
                        $bereiche_by_name[$bereich_name_key] = $bereich;
                    }
                }

                $existing_taetigkeiten = $db->get_taetigkeiten(false);
                $taetigkeiten_by_key = array();
                foreach ($existing_taetigkeiten as $taetigkeit) {
                    if (empty($taetigkeit->bereich_id) || empty($taetigkeit->name)) {
                        continue;
                    }
                    $taetigkeit_key = intval($taetigkeit->bereich_id) . '|' . strtolower(trim((string) $taetigkeit->name));
                    $taetigkeiten_by_key[$taetigkeit_key] = $taetigkeit;
                }

                foreach ($csv_data as $row) {
                    $row_number++;

                    $data = array();
                    foreach ($mapping as $field => $csvIndex) {
                        $data[$field] = isset($row[$csvIndex]) ? trim($row[$csvIndex]) : '';
                    }

                    if (empty($data['name'])) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Pflichtfeld fehlt (Name)";
                        continue;
                    }

                    $bereich_id = 0;
                    if (!empty($data['bereich_id'])) {
                        $candidate_bereich_id = intval($data['bereich_id']);
                        if ($candidate_bereich_id > 0 && isset($bereiche_by_id[$candidate_bereich_id])) {
                            $bereich_id = $candidate_bereich_id;
                        }
                    }

                    if (!$bereich_id && !empty($data['bereich_name'])) {
                        $bereich_name_key = strtolower(trim($data['bereich_name']));
                        if (isset($bereiche_by_name[$bereich_name_key])) {
                            $bereich_id = (int) $bereiche_by_name[$bereich_name_key]->id;
                        } else {
                            $new_bereich_id = $db->get_or_create_bereich($data['bereich_name']);
                            if ($new_bereich_id) {
                                $bereich_id = (int) $new_bereich_id;
                                $new_bereich = (object) array(
                                    'id' => $bereich_id,
                                    'name' => sanitize_text_field($data['bereich_name'])
                                );
                                $bereiche_by_id[$bereich_id] = $new_bereich;
                                $bereiche_by_name[$bereich_name_key] = $new_bereich;
                            }
                        }
                    }

                    if (!$bereich_id) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Pflichtfeld fehlt oder ungültig (bereich_name oder bereich_id)";
                        continue;
                    }

                    $name = sanitize_text_field($data['name']);
                    $taetigkeit_key = $bereich_id . '|' . strtolower($name);

                    $taetigkeit_data = array(
                        'bereich_id' => $bereich_id,
                        'name' => $name,
                        'beschreibung' => isset($data['beschreibung']) ? sanitize_textarea_field($data['beschreibung']) : '',
                        'aktiv' => $parse_bool(isset($data['aktiv']) ? $data['aktiv'] : '', 1),
                        'sortierung' => isset($data['sortierung']) && $data['sortierung'] !== '' ? max(0, intval($data['sortierung'])) : 999,
                        'admin_only' => $parse_bool(isset($data['admin_only']) ? $data['admin_only'] : '', 0)
                    );

                    $existing = isset($taetigkeiten_by_key[$taetigkeit_key]) ? $taetigkeiten_by_key[$taetigkeit_key] : null;

                    if ($existing && $import_mode === 'update') {
                        $result = $db->update_taetigkeit($existing->id, $taetigkeit_data);
                        if ($result !== false) {
                            $updated++;
                            $existing->name = $taetigkeit_data['name'];
                            $existing->bereich_id = $taetigkeit_data['bereich_id'];
                            $existing->beschreibung = $taetigkeit_data['beschreibung'];
                            $existing->aktiv = $taetigkeit_data['aktiv'];
                            $existing->sortierung = $taetigkeit_data['sortierung'];
                            $existing->admin_only = $taetigkeit_data['admin_only'];
                            $taetigkeiten_by_key[$taetigkeit_key] = $existing;
                        } else {
                            $errors++;
                            $error_details[] = "Zeile {$row_number}: Fehler beim Aktualisieren von Tätigkeit '{$name}'";
                        }
                    } elseif (!$existing) {
                        $result = $db->add_taetigkeit($taetigkeit_data);
                        if ($result !== false) {
                            $created++;
                            $taetigkeiten_by_key[$taetigkeit_key] = (object) array(
                                'id' => $result,
                                'bereich_id' => $taetigkeit_data['bereich_id'],
                                'name' => $taetigkeit_data['name'],
                                'beschreibung' => $taetigkeit_data['beschreibung'],
                                'aktiv' => $taetigkeit_data['aktiv'],
                                'sortierung' => $taetigkeit_data['sortierung'],
                                'admin_only' => $taetigkeit_data['admin_only']
                            );
                        } else {
                            $errors++;
                            $error_details[] = "Zeile {$row_number}: Fehler beim Erstellen von Tätigkeit '{$name}'";
                        }
                    } else {
                        $skipped++;
                    }
                }
                break;

            case 'dienstplan':
                $veranstaltung_id = isset($_POST['veranstaltung_id']) ? intval($_POST['veranstaltung_id']) : 0;
                $veranstaltung_start = isset($_POST['veranstaltung_start']) ? sanitize_text_field($_POST['veranstaltung_start']) : '';
                $veranstaltung_ende = isset($_POST['veranstaltung_ende']) ? sanitize_text_field($_POST['veranstaltung_ende']) : '';
                $default_bereich_farbe = isset($_POST['default_bereich_farbe']) ? sanitize_hex_color($_POST['default_bereich_farbe']) : '';
                $auto_create_vereine = !empty($_POST['auto_create_vereine']);

                if (empty($default_bereich_farbe)) {
                    $default_bereich_farbe = '#3b82f6';
                }

                if (!$veranstaltung_id || !$veranstaltung_start) {
                    wp_send_json_error(array('message' => 'Veranstaltung fehlt oder ungültig'));
                    return;
                }

                $start_timestamp = strtotime($veranstaltung_start);
                $ende_timestamp = strtotime($veranstaltung_ende . ' 23:59:59');

                $veranstaltung_tage = $db->get_veranstaltung_tage($veranstaltung_id);
                $tage_by_date = array();
                foreach ($veranstaltung_tage as $tag) {
                    $tage_by_date[date('Y-m-d', strtotime($tag->tag_datum))] = $tag;
                }

                $existing_bereiche = $db->get_bereiche(false);
                $bereiche_by_name = array();
                foreach ($existing_bereiche as $bereich) {
                    $bereich_name_key = strtolower(trim((string) $bereich->name));
                    if ($bereich_name_key !== '') {
                        $bereiche_by_name[$bereich_name_key] = $bereich;
                    }
                }

                $existing_taetigkeiten = $db->get_taetigkeiten(false);
                $taetigkeiten_by_key = array();
                foreach ($existing_taetigkeiten as $taetigkeit) {
                    if (empty($taetigkeit->bereich_id) || empty($taetigkeit->name)) {
                        continue;
                    }
                    $taetigkeiten_by_key[intval($taetigkeit->bereich_id) . '|' . strtolower(trim((string) $taetigkeit->name))] = $taetigkeit;
                }

                $existing_vereine = $db->get_vereine(false);
                $vereine_by_kuerzel = array();
                $vereine_by_name = array();
                foreach ($existing_vereine as $verein) {
                    $verein_array = (array) $verein;
                    if (!empty($verein_array['kuerzel'])) {
                        $vereine_by_kuerzel[strtoupper(trim((string) $verein_array['kuerzel']))] = $verein_array;
                    }
                    if (!empty($verein_array['name'])) {
                        $vereine_by_name[strtolower(trim((string) $verein_array['name']))] = $verein_array;
                    }
                }

                $row_number = 1;
                foreach ($csv_data as $row) {
                    $row_number++;

                    $data = array();
                    foreach ($mapping as $field => $csvIndex) {
                        $data[$field] = isset($row[$csvIndex]) ? trim($row[$csvIndex]) : '';
                    }

                    if (empty($data['datum'])) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Pflichtfeld 'Datum' fehlt";
                        continue;
                    }

                    $dienst_timestamp = $parse_date($data['datum']);
                    if ($dienst_timestamp === false) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Ungültiges Datum '{$data['datum']}'";
                        continue;
                    }

                    if ($dienst_timestamp < $start_timestamp || $dienst_timestamp > $ende_timestamp) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Datum '{$data['datum']}' liegt außerhalb der Veranstaltung ({$veranstaltung_start} - {$veranstaltung_ende})";
                        continue;
                    }

                    $dienst_datum = date('Y-m-d', $dienst_timestamp);
                    if (!isset($tage_by_date[$dienst_datum])) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Datum '{$data['datum']}' nicht in Veranstaltung gefunden";
                        continue;
                    }

                    $dienst_data = array(
                        'veranstaltung_id' => $veranstaltung_id,
                        'tag_id' => $tage_by_date[$dienst_datum]->id,
                        'status' => 'geplant',
                        'anzahl_personen' => isset($data['anzahl_personen']) && $data['anzahl_personen'] !== '' ? max(1, intval($data['anzahl_personen'])) : 1,
                        // Beim Import werden Dienste standardmäßig nicht automatisch gesplittet.
                        // Splittbar wird als Option für spätere manuelle Aufteilung behandelt.
                        'splittbar' => 0
                    );

                    if (!empty($data['von_zeit'])) {
                        $von_zeit = str_replace('.', ':', $data['von_zeit']);
                        if (preg_match('/^\d{1,2}:\d{2}$/', $von_zeit)) {
                            $von_zeit .= ':00';
                        }
                        $dienst_data['von_zeit'] = $von_zeit;
                    }
                    if (!empty($data['bis_zeit'])) {
                        $bis_zeit = str_replace('.', ':', $data['bis_zeit']);
                        if (preg_match('/^\d{1,2}:\d{2}$/', $bis_zeit)) {
                            $bis_zeit .= ':00';
                        }
                        $dienst_data['bis_zeit'] = $bis_zeit;

                        if (!empty($dienst_data['von_zeit'])) {
                            $von_hour = intval(substr($dienst_data['von_zeit'], 0, 2));
                            $bis_hour = intval(substr($bis_zeit, 0, 2));
                            if ($bis_hour < $von_hour) {
                                $dienst_data['bis_datum'] = date('Y-m-d', strtotime($tage_by_date[$dienst_datum]->tag_datum . ' +1 day'));
                            }
                        }
                    }

                    if (!empty($data['besonderheiten'])) {
                        $dienst_data['besonderheiten'] = sanitize_textarea_field($data['besonderheiten']);
                    }

                    $missing_info = array();
                    $bereich_id = 0;

                    if (!empty($data['bereich_name'])) {
                        $bereich_name = sanitize_text_field($data['bereich_name']);
                        $bereich_name_key = strtolower($bereich_name);
                        $bereich_farbe = !empty($data['bereich_farbe']) ? sanitize_hex_color($data['bereich_farbe']) : $default_bereich_farbe;
                        $bereich_admin_only = $parse_bool(isset($data['bereich_admin_only']) ? $data['bereich_admin_only'] : '', 0);
                        $existing_bereich = isset($bereiche_by_name[$bereich_name_key]) ? $bereiche_by_name[$bereich_name_key] : null;

                        if ($existing_bereich) {
                            $bereich_id = (int) $existing_bereich->id;
                            if ($import_mode === 'update') {
                                $db->update_bereich($bereich_id, array(
                                    'name' => $bereich_name,
                                    'farbe' => $bereich_farbe,
                                    'aktiv' => 1,
                                    'sortierung' => isset($existing_bereich->sortierung) ? intval($existing_bereich->sortierung) : 999,
                                    'admin_only' => $bereich_admin_only
                                ));
                            }
                        } else {
                            $bereich_id = $db->add_bereich(array(
                                'name' => $bereich_name,
                                'farbe' => $bereich_farbe,
                                'aktiv' => 1,
                                'sortierung' => 999,
                                'admin_only' => $bereich_admin_only
                            ));

                            if ($bereich_id) {
                                $bereiche_by_name[$bereich_name_key] = (object) array(
                                    'id' => $bereich_id,
                                    'name' => $bereich_name,
                                    'farbe' => $bereich_farbe,
                                    'sortierung' => 999,
                                    'admin_only' => $bereich_admin_only
                                );
                            } else {
                                $missing_info[] = "Bereich '{$bereich_name}' konnte nicht angelegt werden";
                            }
                        }
                    } else {
                        $missing_info[] = 'Kein Bereich angegeben';
                    }

                    if ($bereich_id) {
                        $dienst_data['bereich_id'] = $bereich_id;
                    }

                    if (!empty($data['taetigkeit_name']) && $bereich_id) {
                        $taetigkeit_name = sanitize_text_field($data['taetigkeit_name']);
                        $taetigkeit_key = $bereich_id . '|' . strtolower($taetigkeit_name);
                        $existing_taetigkeit = isset($taetigkeiten_by_key[$taetigkeit_key]) ? $taetigkeiten_by_key[$taetigkeit_key] : null;
                        $taetigkeit_data = array(
                            'bereich_id' => $bereich_id,
                            'name' => $taetigkeit_name,
                            'beschreibung' => '',
                            'aktiv' => 1,
                            'sortierung' => 999,
                            'admin_only' => $parse_bool(isset($data['taetigkeit_admin_only']) ? $data['taetigkeit_admin_only'] : '', 0)
                        );

                        if ($existing_taetigkeit) {
                            $dienst_data['taetigkeit_id'] = (int) $existing_taetigkeit->id;
                            if ($import_mode === 'update') {
                                $db->update_taetigkeit($existing_taetigkeit->id, $taetigkeit_data);
                            }
                        } else {
                            $taetigkeit_id = $db->add_taetigkeit($taetigkeit_data);
                            if ($taetigkeit_id) {
                                $dienst_data['taetigkeit_id'] = (int) $taetigkeit_id;
                                $taetigkeiten_by_key[$taetigkeit_key] = (object) array_merge(array('id' => $taetigkeit_id), $taetigkeit_data);
                            } else {
                                $missing_info[] = "Tätigkeit '{$taetigkeit_name}' konnte nicht angelegt werden";
                            }
                        }
                    } elseif (!empty($data['taetigkeit_name'])) {
                        $missing_info[] = 'Tätigkeit konnte ohne Bereich nicht angelegt werden';
                    } else {
                        $missing_info[] = 'Keine Tätigkeit angegeben';
                    }

                    $verein = null;
                    $verein_kuerzel = !empty($data['verein_kuerzel']) ? strtoupper(sanitize_text_field($data['verein_kuerzel'])) : '';
                    $verein_name = !empty($data['verein_name']) ? sanitize_text_field($data['verein_name']) : '';
                    $verein_name_key = strtolower($verein_name);

                    if ($verein_kuerzel !== '' && isset($verein_alias_map[$verein_kuerzel])) {
                        $verein_kuerzel = $verein_alias_map[$verein_kuerzel];
                    }

                    if ($verein_kuerzel !== '' && isset($vereine_by_kuerzel[$verein_kuerzel])) {
                        $verein = $vereine_by_kuerzel[$verein_kuerzel];
                    } elseif ($verein_name_key !== '' && isset($vereine_by_name[$verein_name_key])) {
                        $verein = $vereine_by_name[$verein_name_key];
                    } elseif ($auto_create_vereine && $verein_name !== '') {
                        if ($verein_kuerzel === '') {
                            $verein_kuerzel = $make_verein_kuerzel($verein_name, $vereine_by_kuerzel);
                        }

                        $verein_data = array(
                            'name' => $verein_name,
                            'kuerzel' => $verein_kuerzel,
                            'beschreibung' => ''
                        );

                        $verein_id = $db->add_verein($verein_data);
                        if ($verein_id) {
                            $verein = array_merge(array('id' => $verein_id), $verein_data);
                            $vereine_by_kuerzel[strtoupper($verein_kuerzel)] = $verein;
                            $vereine_by_name[$verein_name_key] = $verein;
                        } else {
                            $missing_info[] = "Verein '{$verein_name}' konnte nicht angelegt werden";
                        }
                    }

                    if ($verein && !empty($verein['id'])) {
                        $dienst_data['verein_id'] = (int) $verein['id'];
                    } elseif ($verein_name !== '' || $verein_kuerzel !== '') {
                        $verein_label = $verein_name !== '' ? $verein_name : $verein_kuerzel;
                        $missing_info[] = "Verein '{$verein_label}' nicht gefunden";

                        $verein_key = $verein_kuerzel !== '' ? $verein_kuerzel : strtolower($verein_name);
                        $verein_key_hash = 'dienstplan|' . $verein_key;
                        if ($verein_key !== '') {
                            $current_unresolved_key_hash = $verein_key_hash;
                            $current_unresolved_payload = array(
                                'row' => $row_number,
                                'import_type' => 'dienstplan',
                                'input_kuerzel' => $verein_kuerzel,
                                'input_name' => $verein_name,
                                'display' => $verein_label,
                                'lookup_key' => $verein_kuerzel !== '' ? $verein_kuerzel : strtoupper(substr($verein_name, 0, 64))
                            );
                            if (!isset($unresolved_verein_keys[$verein_key_hash])) {
                                $unresolved_verein_keys[$verein_key_hash] = true;
                                $register_unresolved_verein($current_unresolved_key_hash, $current_unresolved_payload);
                            }
                        }
                    } else {
                        $missing_info[] = 'Kein Verein angegeben';
                    }

                    if (!empty($missing_info)) {
                        $dienst_data['status'] = 'unvollstaendig';
                        $error_details[] = array(
                            'row' => $row_number,
                            'type' => 'warning',
                            'message' => 'Unvollständige Daten: ' . implode(', ', $missing_info)
                        );
                    }

                    $existing_dienst = $db->find_existing_dienst($dienst_data);
                    if ($existing_dienst) {
                        if ($import_mode === 'update') {
                            $result = $db->update_dienst($existing_dienst->id, $dienst_data);

                            if (is_array($result) && isset($result['error']) && $result['error']) {
                                $errors++;
                                $error_details[] = "Zeile {$row_number}: " . $result['message'];
                            } elseif ($result !== false) {
                                $updated++;
                                if ($current_unresolved_key_hash !== '' && $current_unresolved_payload !== null) {
                                    $register_unresolved_verein($current_unresolved_key_hash, $current_unresolved_payload, intval($existing_dienst->id));
                                }
                            } else {
                                $errors++;
                                $error_details[] = "Zeile {$row_number}: Fehler beim Aktualisieren des bereits vorhandenen Dienstes";
                            }
                        } else {
                            $skipped++;
                        }
                        continue;
                    }

                    $result = $db->add_dienst($dienst_data);

                    if (is_array($result) && isset($result['error']) && $result['error']) {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: " . $result['message'];
                    } elseif ($result !== false && is_numeric($result)) {
                        $created++;
                        if ($current_unresolved_key_hash !== '' && $current_unresolved_payload !== null) {
                            $register_unresolved_verein($current_unresolved_key_hash, $current_unresolved_payload, intval($result));
                        }
                    } else {
                        $errors++;
                        $error_details[] = "Zeile {$row_number}: Fehler beim Speichern des Dienstes in die Datenbank";
                    }
                }

                if ($created > 0 || $updated > 0) {
                    $veranstaltung_dienste = $db->get_dienste($veranstaltung_id);
                    $used_verein_ids = array();
                    foreach ($veranstaltung_dienste as $dienst) {
                        if (!empty($dienst->verein_id) && !in_array($dienst->verein_id, $used_verein_ids, true)) {
                            $used_verein_ids[] = $dienst->verein_id;
                        }
                    }

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
                    $current_unresolved_key_hash = '';
                    $current_unresolved_payload = null;
                    
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
                        $kuerzel_input_upper = strtoupper($kuerzel_input);

                        if (isset($verein_alias_map[$kuerzel_input_upper])) {
                            $kuerzel_input = $verein_alias_map[$kuerzel_input_upper];
                        }
                        
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

                            $verein_key_hash = 'dienste|' . strtoupper($kuerzel_input);
                            $current_unresolved_key_hash = $verein_key_hash;
                            $current_unresolved_payload = array(
                                'row' => $row_number,
                                'import_type' => 'dienste',
                                'input_kuerzel' => strtoupper($kuerzel_input),
                                'input_name' => '',
                                'display' => strtoupper($kuerzel_input),
                                'lookup_key' => strtoupper($kuerzel_input)
                            );
                            if (!isset($unresolved_verein_keys[$verein_key_hash])) {
                                $unresolved_verein_keys[$verein_key_hash] = true;
                                $register_unresolved_verein($current_unresolved_key_hash, $current_unresolved_payload);
                            }
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
                    
                    // Beim CSV-Import nie automatisch in Halbdienste splitten.
                    // Falls gewünscht, kann später manuell gesplittet werden.
                    $dienst_data['splittbar'] = 0;
                    
                    $existing_dienst = $db->find_existing_dienst($dienst_data);
                    if ($existing_dienst) {
                        if ($import_mode === 'update') {
                            $result = $db->update_dienst($existing_dienst->id, $dienst_data);

                            if (is_array($result) && isset($result['error']) && $result['error']) {
                                $errors++;
                                $error_details[] = "Zeile {$row_number}: " . $result['message'];
                            } elseif ($result !== false) {
                                $updated++;
                                if ($current_unresolved_key_hash !== '' && $current_unresolved_payload !== null) {
                                    $register_unresolved_verein($current_unresolved_key_hash, $current_unresolved_payload, intval($existing_dienst->id));
                                }
                            } else {
                                $errors++;
                                $error_details[] = "Zeile {$row_number}: Fehler beim Aktualisieren des bereits vorhandenen Dienstes";
                            }
                        } else {
                            $skipped++;
                        }
                        continue;
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
                        if ($current_unresolved_key_hash !== '' && $current_unresolved_payload !== null) {
                            $register_unresolved_verein($current_unresolved_key_hash, $current_unresolved_payload, intval($result));
                        }
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
            'unresolved_vereine' => array_values($unresolved_vereine),
            'message' => sprintf('Import abgeschlossen: %d erstellt, %d aktualisiert, %d übersprungen, %d Fehler', 
                $created, $updated, $skipped, $errors)
        ));
    }

    /**
     * Speichert Alias-Zuordnungen für Vereinskürzel, die beim Import nicht direkt gefunden wurden.
     */
    public function ajax_save_verein_aliases() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');

        $can_manage = Dienstplan_Roles::can_manage_events() || Dienstplan_Roles::can_manage_clubs() || current_user_can('manage_options');
        if (!$can_manage) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }

        $aliases_json = isset($_POST['aliases']) ? wp_unslash($_POST['aliases']) : '';
        $aliases = json_decode($aliases_json, true);
        $assignments_json = isset($_POST['assignments']) ? wp_unslash($_POST['assignments']) : '';
        $assignments = json_decode($assignments_json, true);

        if ((!is_array($aliases) || empty($aliases)) && (!is_array($assignments) || empty($assignments))) {
            wp_send_json_error(array('message' => 'Keine Alias-Zuordnungen übergeben'));
            return;
        }

        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        $wpdb = $db->get_wpdb();

        $existing = get_option('dp_import_verein_aliases', array());
        if (!is_array($existing)) {
            $existing = array();
        }

        $saved = 0;
        foreach ($aliases as $from => $to) {
            $from_clean = strtoupper(trim(sanitize_text_field((string) $from)));
            $to_clean = strtoupper(trim(sanitize_text_field((string) $to)));

            if ($from_clean === '' || $to_clean === '') {
                continue;
            }

            $existing[$from_clean] = $to_clean;
            $saved++;
        }

        update_option('dp_import_verein_aliases', $existing, false);

        $applied = 0;
        $updated_dienste = array();
        if (is_array($assignments) && !empty($assignments)) {
            foreach ($assignments as $assignment) {
                $target_kuerzel = strtoupper(trim(sanitize_text_field((string) ($assignment['target_kuerzel'] ?? ''))));
                $dienst_ids = isset($assignment['dienst_ids']) && is_array($assignment['dienst_ids'])
                    ? array_values(array_unique(array_filter(array_map('intval', $assignment['dienst_ids']))))
                    : array();

                if ($target_kuerzel === '' || empty($dienst_ids)) {
                    continue;
                }

                $target_verein = $db->get_verein_by_kuerzel($target_kuerzel);
                if (!$target_verein) {
                    continue;
                }

                $target_verein_id = intval(is_array($target_verein) ? ($target_verein['id'] ?? 0) : ($target_verein->id ?? 0));
                if ($target_verein_id <= 0) {
                    continue;
                }

                foreach ($dienst_ids as $dienst_id) {
                    $dienst_row = $wpdb->get_row($wpdb->prepare(
                        "SELECT id, veranstaltung_id, verein_id, bereich_id, taetigkeit_id, von_zeit, bis_zeit, status
                         FROM {$db->get_prefix()}dienste
                         WHERE id = %d",
                        $dienst_id
                    ));

                    if (!$dienst_row) {
                        continue;
                    }

                    $update_data = array('verein_id' => $target_verein_id);
                    $update_format = array('%d');
                    $has_complete_assignment = !empty($dienst_row->bereich_id)
                        && !empty($dienst_row->taetigkeit_id)
                        && !empty($dienst_row->von_zeit)
                        && !empty($dienst_row->bis_zeit);
                    if ($has_complete_assignment) {
                        $update_data['status'] = 'geplant';
                        $update_format[] = '%s';
                    }

                    $update_result = $wpdb->update(
                        $db->get_prefix() . 'dienste',
                        $update_data,
                        array('id' => intval($dienst_id)),
                        $update_format,
                        array('%d')
                    );

                    if ($update_result === false) {
                        continue;
                    }

                    if (!in_array($dienst_id, $updated_dienste, true)) {
                        $updated_dienste[] = $dienst_id;
                        $applied++;
                    }

                    if (!empty($dienst_row->veranstaltung_id)) {
                        $existing_link = $wpdb->get_var($wpdb->prepare(
                            "SELECT id FROM {$db->get_prefix()}veranstaltung_vereine WHERE veranstaltung_id = %d AND verein_id = %d",
                            intval($dienst_row->veranstaltung_id),
                            $target_verein_id
                        ));

                        if (!$existing_link) {
                            $db->add_veranstaltung_verein(intval($dienst_row->veranstaltung_id), $target_verein_id);
                        }
                    }
                }
            }
        }

        wp_send_json_success(array(
            'saved' => $saved,
            'aliases' => $existing,
            'applied' => $applied,
            'updated_dienste' => $updated_dienste
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
        
        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }

        if ($this->is_restricted_club_admin()) {
            wp_send_json_error(array('message' => 'Club-Admins dürfen Bereiche nicht bearbeiten.'));
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
        
        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }

        if ($this->is_restricted_club_admin()) {
            wp_send_json_error(array('message' => 'Club-Admins dürfen Bereiche nicht bearbeiten.'));
            return;
        }
        
        $bereich_id = isset($_POST['bereich_id']) ? intval($_POST['bereich_id']) : 0;
        $name = isset($_POST['bereich_name']) ? sanitize_text_field($_POST['bereich_name']) : (isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '');
        $farbe = isset($_POST['bereich_farbe']) ? sanitize_text_field($_POST['bereich_farbe']) : (isset($_POST['farbe']) ? sanitize_text_field($_POST['farbe']) : '#3b82f6');
        $admin_only = isset($_POST['bereich_admin_only']) ? intval($_POST['bereich_admin_only']) : 0;
        
        if (empty($name)) {
            wp_send_json_error(array('message' => 'Name ist erforderlich'));
            return;
        }
        
        $db = new Dienstplan_Database();
        $data = array(
            'name' => $name,
            'farbe' => $farbe,
            'admin_only' => $admin_only
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
        
        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }

        if ($this->is_restricted_club_admin()) {
            wp_send_json_error(array('message' => 'Club-Admins dürfen Bereiche nicht bearbeiten.'));
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
        
        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
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
        
        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
            return;
        }
        
        $taetigkeit_id = isset($_POST['taetigkeit_id']) ? intval($_POST['taetigkeit_id']) : 0;
        $bereich_id = isset($_POST['taetigkeit_bereich_id']) ? intval($_POST['taetigkeit_bereich_id']) : (isset($_POST['bereich_id']) ? intval($_POST['bereich_id']) : 0);
        $name = isset($_POST['taetigkeit_name']) ? sanitize_text_field($_POST['taetigkeit_name']) : (isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '');
        $beschreibung = isset($_POST['taetigkeit_beschreibung']) ? sanitize_textarea_field($_POST['taetigkeit_beschreibung']) : (isset($_POST['beschreibung']) ? sanitize_textarea_field($_POST['beschreibung']) : '');
        $aktiv = isset($_POST['taetigkeit_status']) ? (sanitize_text_field($_POST['taetigkeit_status']) === 'aktiv' ? 1 : 0) : (isset($_POST['aktiv']) ? intval($_POST['aktiv']) : 1);
        $admin_only = isset($_POST['taetigkeit_admin_only']) ? intval($_POST['taetigkeit_admin_only']) : 0;
        
        if (empty($name) || !$bereich_id) {
            wp_send_json_error(array('message' => 'Name und Bereich sind erforderlich'));
            return;
        }
        
        $db = new Dienstplan_Database();
        $data = array(
            'bereich_id' => $bereich_id,
            'name' => $name,
            'beschreibung' => $beschreibung,
            'aktiv' => $aktiv,
            'admin_only' => $admin_only
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
        
        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
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
        
        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
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
        
        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
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
        
        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
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
    
    /**
     * AJAX: Verein-spezifische Seiten für Veranstaltung erstellen
     * Erstellt für jeden beteiligten Verein eine eigene WordPress-Seite
     *
     * @since 0.6.6
     */
    public function ajax_create_verein_seiten() {
        // Nonce-Prüfung
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dp_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen.'));
            return;
        }
        
        // Berechtigungsprüfung
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }
        
        $veranstaltung_id = isset($_POST['veranstaltung_id']) ? intval($_POST['veranstaltung_id']) : 0;
        
        if (!$veranstaltung_id) {
            wp_send_json_error(array('message' => 'Ungültige Veranstaltungs-ID.'));
            return;
        }
        
        global $wpdb;
        $prefix = $wpdb->prefix . $this->db_prefix;
        
        // Veranstaltung laden
        $veranstaltung = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}veranstaltungen WHERE id = %d",
            $veranstaltung_id
        ));
        
        if (!$veranstaltung) {
            wp_send_json_error(array('message' => 'Veranstaltung nicht gefunden.'));
            return;
        }
        
        // Status-Prüfung: Seiten können nur erstellt werden, wenn Status nicht 'in_planung' ist
        if ($veranstaltung->status === 'in_planung') {
            wp_send_json_error(array('message' => 'Anmeldeseiten können noch nicht erstellt werden. Die Veranstaltung befindet sich noch in Planung. Bitte ändern Sie den Status auf "Geplant".'));
            return;
        }
        
        // Beteiligte Vereine laden
        $vereine = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT v.* 
             FROM {$prefix}vereine v
             INNER JOIN {$prefix}veranstaltung_vereine vv ON v.id = vv.verein_id
             WHERE vv.veranstaltung_id = %d
             ORDER BY v.name",
            $veranstaltung_id
        ));
        
        if (empty($vereine)) {
            wp_send_json_error(array('message' => 'Keine beteiligten Vereine gefunden.'));
            return;
        }
        
        $created_pages = 0;
        $created_page_ids = array();
        
        foreach ($vereine as $verein) {
            // Prüfen, ob bereits eine Seite existiert
            $existing_page = get_posts(array(
                'post_type' => 'page',
                'post_status' => 'any',
                'meta_query' => array(
                    array(
                        'key' => '_dp_veranstaltung_id',
                        'value' => $veranstaltung_id
                    ),
                    array(
                        'key' => '_dp_verein_id',
                        'value' => $verein->id
                    )
                ),
                'numberposts' => 1
            ));
            
            if (!empty($existing_page)) {
                // Seite existiert bereits, überspringen
                continue;
            }
            
            // Page-Titel und Slug generieren
            $page_title = sanitize_text_field($veranstaltung->name) . ' - ' . sanitize_text_field($verein->name);
            $page_slug = sanitize_title($veranstaltung->name . '-' . $verein->name);
            
            // Shortcode für Page-Content
            $page_content = '[dienstplan_veranstaltung veranstaltung_id="' . $veranstaltung_id . '" verein_id="' . $verein->id . '"]';
            
            // WordPress-Seite erstellen
            $page_id = wp_insert_post(array(
                'post_title' => $page_title,
                'post_name' => $page_slug,
                'post_content' => $page_content,
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => get_current_user_id(),
                'comment_status' => 'closed',
                'ping_status' => 'closed'
            ));
            
            if (!is_wp_error($page_id) && $page_id > 0) {
                // Meta-Daten hinzufügen für spätere Referenz
                update_post_meta($page_id, '_dp_veranstaltung_id', $veranstaltung_id);
                update_post_meta($page_id, '_dp_verein_id', $verein->id);
                update_post_meta($page_id, '_dp_autogenerated', true);
                
                $created_pages++;
                $created_page_ids[] = array(
                    'page_id' => $page_id,
                    'verein_id' => $verein->id,
                    'verein_name' => $verein->name,
                    'url' => get_permalink($page_id)
                );
            }
        }
        
        if ($created_pages === 0) {
            wp_send_json_error(array(
                'message' => 'Keine neuen Seiten erstellt. Möglicherweise existieren bereits Seiten für alle Vereine.'
            ));
            return;
        }
        
        wp_send_json_success(array(
            'message' => sprintf(
                'Es wurden %d Seite(n) für %d Verein(e) erstellt.',
                $created_pages,
                count($vereine)
            ),
            'created' => $created_pages,
            'total_vereine' => count($vereine),
            'pages' => $created_page_ids
        ));
    }
    
    /**
     * AJAX: Einzelne Verein-Seite für Veranstaltung erstellen
     *
     * @since 0.6.6
     */
    public function ajax_create_single_verein_seite() {
        // Nonce-Prüfung
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dp_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen.'));
            return;
        }
        
        // Berechtigungsprüfung
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }
        
        $veranstaltung_id = isset($_POST['veranstaltung_id']) ? intval($_POST['veranstaltung_id']) : 0;
        $verein_id = isset($_POST['verein_id']) ? intval($_POST['verein_id']) : 0;
        
        if (!$veranstaltung_id || !$verein_id) {
            wp_send_json_error(array('message' => 'Ungültige Parameter.'));
            return;
        }
        
        global $wpdb;
        $prefix = $wpdb->prefix . $this->db_prefix;
        
        // Veranstaltung laden
        $veranstaltung = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}veranstaltungen WHERE id = %d",
            $veranstaltung_id
        ));
        
        if (!$veranstaltung) {
            wp_send_json_error(array('message' => 'Veranstaltung nicht gefunden.'));
            return;
        }
        
        // Status-Prüfung: Seiten können nur erstellt werden, wenn Status nicht 'in_planung' ist
        if ($veranstaltung->status === 'in_planung') {
            wp_send_json_error(array('message' => 'Anmeldeseiten können noch nicht erstellt werden. Die Veranstaltung befindet sich noch in Planung. Bitte ändern Sie den Status auf "Geplant".'));
            return;
        }
        
        // Verein laden
        $verein = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}vereine WHERE id = %d",
            $verein_id
        ));
        
        if (!$verein) {
            wp_send_json_error(array('message' => 'Verein nicht gefunden.'));
            return;
        }
        
        // Prüfen, ob bereits eine Seite existiert
        $existing_page = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'any',
            'meta_query' => array(
                array(
                    'key' => '_dp_veranstaltung_id',
                    'value' => $veranstaltung_id
                ),
                array(
                    'key' => '_dp_verein_id',
                    'value' => $verein_id
                )
            ),
            'numberposts' => 1
        ));
        
        if (!empty($existing_page)) {
            wp_send_json_error(array('message' => 'Seite existiert bereits.'));
            return;
        }
        
        // Page-Titel und Slug generieren
        $page_title = sanitize_text_field($veranstaltung->name) . ' - ' . sanitize_text_field($verein->name);
        $page_slug = sanitize_title($veranstaltung->name . '-' . $verein->name);
        
        // Shortcode für Page-Content
        $page_content = '[dienstplan_veranstaltung veranstaltung_id="' . $veranstaltung_id . '" verein_id="' . $verein_id . '"]';
        
        // WordPress-Seite erstellen
        $page_id = wp_insert_post(array(
            'post_title' => $page_title,
            'post_name' => $page_slug,
            'post_content' => $page_content,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => get_current_user_id(),
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        ));
        
        if (is_wp_error($page_id) || $page_id === 0) {
            wp_send_json_error(array('message' => 'Fehler beim Erstellen der Seite.'));
            return;
        }
        
        // Meta-Daten hinzufügen
        update_post_meta($page_id, '_dp_veranstaltung_id', $veranstaltung_id);
        update_post_meta($page_id, '_dp_verein_id', $verein_id);
        update_post_meta($page_id, '_dp_autogenerated', true);
        
        wp_send_json_success(array(
            'message' => 'Seite erfolgreich erstellt: ' . $page_title,
            'page_id' => $page_id,
            'url' => get_permalink($page_id),
            'edit_url' => admin_url('post.php?post=' . $page_id . '&action=edit')
        ));
    }

    /**
     * AJAX: Einzelne Vereins-Übersichtsseite erstellen
     */
    public function ajax_create_single_verein_overview_page() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dp_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen.'));
            return;
        }

        if (!Dienstplan_Roles::can_manage_events() && !Dienstplan_Roles::can_manage_clubs()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }

        $verein_id = isset($_POST['verein_id']) ? intval($_POST['verein_id']) : 0;
        if ($verein_id <= 0) {
            wp_send_json_error(array('message' => 'Ungültige Vereins-ID.'));
            return;
        }

        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);

        if (!$this->current_user_can_access_verein($db, $verein_id)) {
            wp_send_json_error(array('message' => 'Keine Berechtigung für diesen Verein.'));
            return;
        }
        $verein = $db->get_verein($verein_id);

        if (!$verein) {
            wp_send_json_error(array('message' => 'Verein nicht gefunden.'));
            return;
        }

        if (!empty($verein->seite_id)) {
            $existing_page = get_post(intval($verein->seite_id));
            if ($existing_page && $existing_page->post_type === 'page' && $existing_page->post_status !== 'trash') {
                wp_send_json_success(array(
                    'message' => 'Vereinsseite existiert bereits.',
                    'page_id' => intval($existing_page->ID),
                    'url' => get_permalink($existing_page->ID),
                    'edit_url' => admin_url('post.php?post=' . intval($existing_page->ID) . '&action=edit')
                ));
                return;
            }
        }

        $fallback_page = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'any',
            'meta_query' => array(
                array(
                    'key' => '_dp_verein_id',
                    'value' => $verein_id,
                ),
                array(
                    'key' => '_dp_veranstaltung_id',
                    'compare' => 'NOT EXISTS',
                ),
            ),
            'numberposts' => 1,
        ));

        if (!empty($fallback_page)) {
            $page_id = intval($fallback_page[0]->ID);
            $db->update_verein_page_id($verein_id, $page_id);

            wp_send_json_success(array(
                'message' => 'Vorhandene Vereinsseite wurde zugeordnet.',
                'page_id' => $page_id,
                'url' => get_permalink($page_id),
                'edit_url' => admin_url('post.php?post=' . $page_id . '&action=edit')
            ));
            return;
        }

        $page_title = 'Verein: ' . sanitize_text_field($verein->name);
        $page_slug = sanitize_title('verein-' . $verein->name);
        $page_content = '[dienstplan_vereine verein_id="' . $verein_id . '"]';

        $page_id = wp_insert_post(array(
            'post_title' => $page_title,
            'post_name' => $page_slug,
            'post_content' => $page_content,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => get_current_user_id(),
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        ));

        if (is_wp_error($page_id) || $page_id === 0) {
            wp_send_json_error(array('message' => 'Fehler beim Erstellen der Vereinsseite.'));
            return;
        }

        update_post_meta($page_id, '_dp_verein_id', $verein_id);
        update_post_meta($page_id, '_dp_verein_overview', 1);
        update_post_meta($page_id, '_dp_autogenerated', true);
        $db->update_verein_page_id($verein_id, $page_id);

        wp_send_json_success(array(
            'message' => 'Vereinsseite erfolgreich erstellt.',
            'page_id' => $page_id,
            'url' => get_permalink($page_id),
            'edit_url' => admin_url('post.php?post=' . $page_id . '&action=edit')
        ));
    }

    /**
     * AJAX: Alle fehlenden Vereins-Übersichtsseiten erstellen
     */
    public function ajax_create_all_verein_overview_pages() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dp_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen.'));
            return;
        }

        if (!Dienstplan_Roles::can_manage_events() && !Dienstplan_Roles::can_manage_clubs()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }

        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        $vereine = $this->get_scoped_vereine($db, false);

        if (empty($vereine)) {
            wp_send_json_error(array('message' => 'Keine Vereine gefunden.'));
            return;
        }

        $created = 0;
        $updated = 0;

        foreach ($vereine as $verein) {
            $verein_id = intval($verein->id);
            $has_valid_page = false;

            if (!empty($verein->seite_id)) {
                $existing_page = get_post(intval($verein->seite_id));
                if ($existing_page && $existing_page->post_type === 'page' && $existing_page->post_status !== 'trash') {
                    $has_valid_page = true;
                }
            }

            if ($has_valid_page) {
                continue;
            }

            $fallback_page = get_posts(array(
                'post_type' => 'page',
                'post_status' => 'any',
                'meta_query' => array(
                    array(
                        'key' => '_dp_verein_id',
                        'value' => $verein_id,
                    ),
                    array(
                        'key' => '_dp_veranstaltung_id',
                        'compare' => 'NOT EXISTS',
                    ),
                ),
                'numberposts' => 1,
            ));

            if (!empty($fallback_page)) {
                $db->update_verein_page_id($verein_id, intval($fallback_page[0]->ID));
                $updated++;
                continue;
            }

            $page_id = wp_insert_post(array(
                'post_title' => 'Verein: ' . sanitize_text_field($verein->name),
                'post_name' => sanitize_title('verein-' . $verein->name),
                'post_content' => '[dienstplan_vereine verein_id="' . $verein_id . '"]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => get_current_user_id(),
                'comment_status' => 'closed',
                'ping_status' => 'closed'
            ));

            if (!is_wp_error($page_id) && $page_id > 0) {
                update_post_meta($page_id, '_dp_verein_id', $verein_id);
                update_post_meta($page_id, '_dp_verein_overview', 1);
                update_post_meta($page_id, '_dp_autogenerated', true);
                $db->update_verein_page_id($verein_id, $page_id);
                $created++;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf('Vereinsseiten erstellt: %d, bestehende Zuordnungen ergänzt: %d.', $created, $updated),
            'created' => $created,
            'updated' => $updated
        ));
    }
    
    /**
     * AJAX: Seite löschen
     *
     * @since 0.6.6
     */
    public function ajax_delete_page() {
        // Nonce-Prüfung
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dp_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen.'));
            return;
        }
        
        // Berechtigungsprüfung
        if (!current_user_can('manage_options') && !Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }
        
        $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
        
        if ($page_id === 0) {
            wp_send_json_error(array('message' => 'Ungültige Seiten-ID.'));
            return;
        }
        
        // Prüfen ob Seite existiert und von diesem Plugin erstellt wurde
        $page = get_post($page_id);
        if (!$page || $page->post_type !== 'page') {
            wp_send_json_error(array('message' => 'Seite nicht gefunden.'));
            return;
        }
        
        // Prüfen ob Seite von diesem Plugin erstellt wurde (Sicherheitscheck)
        $is_autogenerated = get_post_meta($page_id, '_dp_autogenerated', true);
        if (!$is_autogenerated) {
            wp_send_json_error(array('message' => 'Diese Seite wurde nicht automatisch generiert und kann hier nicht gelöscht werden.'));
            return;
        }
        
        // Seite löschen (true = permanent, nicht in den Papierkorb)
        $result = wp_delete_post($page_id, true);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Seite erfolgreich gelöscht.'));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Löschen der Seite.'));
        }
    }
    
    /**
     * AJAX: Alle Verein-Seiten einer Veranstaltung löschen
     *
     * @since 0.6.6
     */
    public function ajax_delete_all_verein_seiten() {
        // Nonce-Prüfung
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dp_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen.'));
            return;
        }
        
        // Berechtigungsprüfung
        if (!current_user_can('manage_options') && !Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }
        
        $veranstaltung_id = isset($_POST['veranstaltung_id']) ? intval($_POST['veranstaltung_id']) : 0;
        
        if ($veranstaltung_id === 0) {
            wp_send_json_error(array('message' => 'Ungültige Veranstaltungs-ID.'));
            return;
        }
        
        // Alle Seiten finden, die zu dieser Veranstaltung gehören
        $pages = get_posts(array(
            'post_type' => 'page',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_dp_veranstaltung_id',
                    'value' => $veranstaltung_id,
                    'compare' => '='
                ),
                array(
                    'key' => '_dp_autogenerated',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'fields' => 'ids'
        ));
        
        if (empty($pages)) {
            wp_send_json_error(array('message' => 'Keine Seiten gefunden.'));
            return;
        }
        
        $deleted = 0;
        foreach ($pages as $page_id) {
            if (wp_delete_post($page_id, true)) {
                $deleted++;
            }
        }
        
        if ($deleted > 0) {
            wp_send_json_success(array(
                'message' => sprintf('%d Seite(n) erfolgreich gelöscht.', $deleted),
                'deleted' => $deleted,
                'total' => count($pages)
            ));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Löschen der Seiten.'));
        }
    }
    
    /**
     * AJAX: Quick Change Status (einzelne Veranstaltung)
     *
     * @since 0.6.6
     */
    public function ajax_quick_change_status() {
        // Nonce-Prüfung
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dp_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen.'));
            return;
        }
        
        // Berechtigungsprüfung
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }
        
        $veranstaltung_id = isset($_POST['veranstaltung_id']) ? intval($_POST['veranstaltung_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if ($veranstaltung_id === 0) {
            wp_send_json_error(array('message' => 'Ungültige Veranstaltungs-ID.'));
            return;
        }
        
        if (empty($status)) {
            wp_send_json_error(array('message' => 'Kein Status angegeben.'));
            return;
        }
        
        // Valide Status-Werte
        $valid_statuses = array('in_planung', 'geplant', 'aktiv', 'abgeschlossen');
        if (!in_array($status, $valid_statuses)) {
            wp_send_json_error(array('message' => 'Ungültiger Status.'));
            return;
        }
        
        global $wpdb;
        $prefix = $wpdb->prefix . $this->db_prefix;
        
        // Status aktualisieren
        $result = $wpdb->update(
            $prefix . 'veranstaltungen',
            array('status' => $status),
            array('id' => $veranstaltung_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Status erfolgreich geändert.',
                'veranstaltung_id' => $veranstaltung_id,
                'new_status' => $status
            ));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Aktualisieren des Status.'));
        }
    }
    
    /**
     * AJAX: Bulk Update Veranstaltungs-Status
     *
     * @since 0.6.6
     */
    public function ajax_bulk_update_veranstaltung_status() {
        // Nonce-Prüfung
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dp_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen.'));
            return;
        }
        
        // Berechtigungsprüfung
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }
        
        $veranstaltung_ids = isset($_POST['veranstaltung_ids']) ? array_map('intval', $_POST['veranstaltung_ids']) : array();
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if (empty($veranstaltung_ids)) {
            wp_send_json_error(array('message' => 'Keine Veranstaltungen ausgewählt.'));
            return;
        }
        
        if (empty($status)) {
            wp_send_json_error(array('message' => 'Kein Status angegeben.'));
            return;
        }
        
        // Valide Status-Werte
        $valid_statuses = array('in_planung', 'geplant', 'aktiv', 'abgeschlossen');
        if (!in_array($status, $valid_statuses)) {
            wp_send_json_error(array('message' => 'Ungültiger Status.'));
            return;
        }
        
        global $wpdb;
        $prefix = $wpdb->prefix . $this->db_prefix;
        
        $updated = 0;
        foreach ($veranstaltung_ids as $veranstaltung_id) {
            $result = $wpdb->update(
                $prefix . 'veranstaltungen',
                array('status' => $status),
                array('id' => $veranstaltung_id),
                array('%s'),
                array('%d')
            );
            
            if ($result !== false) {
                $updated++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('Status von %d Veranstaltung(en) erfolgreich geändert.', $updated),
            'updated' => $updated,
            'total' => count($veranstaltung_ids)
        ));
    }
    
    /**
     * AJAX: Bulk Delete Veranstaltungen
     *
     * @since 0.6.6
     */
    public function ajax_bulk_delete_veranstaltungen() {
        // Nonce-Prüfung
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dp_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen.'));
            return;
        }
        
        // Berechtigungsprüfung
        if (!Dienstplan_Roles::can_manage_events()) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }
        
        $veranstaltung_ids = isset($_POST['veranstaltung_ids']) ? array_map('intval', $_POST['veranstaltung_ids']) : array();
        
        if (empty($veranstaltung_ids)) {
            wp_send_json_error(array('message' => 'Keine Veranstaltungen ausgewählt.'));
            return;
        }
        
        global $wpdb;
        $prefix = $wpdb->prefix . $this->db_prefix;
        $db = new Dienstplan_Database($this->db_prefix);
        
        $deleted = 0;
        $pages_deleted = 0;
        
        foreach ($veranstaltung_ids as $veranstaltung_id) {
            // Lösche zugehörige Daten
            $wpdb->delete($prefix . 'veranstaltung_tage', array('veranstaltung_id' => $veranstaltung_id), array('%d'));
            $wpdb->delete($prefix . 'veranstaltung_vereine', array('veranstaltung_id' => $veranstaltung_id), array('%d'));
            $wpdb->delete($prefix . 'veranstaltung_verantwortliche', array('veranstaltung_id' => $veranstaltung_id), array('%d'));
            
            // Lösche Dienste und Slots
            $dienste = $db->get_dienste($veranstaltung_id);
            foreach ($dienste as $dienst) {
                $wpdb->delete($prefix . 'dienst_slots', array('dienst_id' => $dienst->id), array('%d'));
                $wpdb->delete($prefix . 'dienste', array('id' => $dienst->id), array('%d'));
            }
            
            // Lösche WordPress-Seiten die zu dieser Veranstaltung gehören
            $pages = get_posts(array(
                'post_type' => 'page',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_dp_veranstaltung_id',
                        'value' => $veranstaltung_id,
                        'compare' => '='
                    )
                ),
                'fields' => 'ids'
            ));
            
            foreach ($pages as $page_id) {
                if (wp_delete_post($page_id, true)) {
                    $pages_deleted++;
                }
            }
            
            // Lösche Veranstaltung
            $result = $wpdb->delete($prefix . 'veranstaltungen', array('id' => $veranstaltung_id), array('%d'));
            
            if ($result !== false) {
                $deleted++;
            }
        }
        
        $message = sprintf('%d Veranstaltung(en) erfolgreich gelöscht.', $deleted);
        if ($pages_deleted > 0) {
            $message .= sprintf(' %d zugehörige Seite(n) wurden ebenfalls gelöscht.', $pages_deleted);
        }
        
        wp_send_json_success(array(
            'message' => $message,
            'deleted' => $deleted,
            'pages_deleted' => $pages_deleted,
            'total' => count($veranstaltung_ids)
        ));
    }
    
    /**
     * AJAX: Portal-Seite erstellen (nach Aktivierung)
     *
     * @since 0.6.6
     */
    public function ajax_create_portal_page() {
        // Nonce-Prüfung
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dp_create_portal_page')) {
            wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen.'));
            return;
        }
        
        // Berechtigungsprüfung
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }
        
        // Prüfe ob Seite bereits existiert (via gespeicherte Option)
        $saved_page_id = get_option('dienstplan_portal_page_id', 0);
        if ($saved_page_id && get_post($saved_page_id)) {
            wp_send_json_error(array('message' => 'Eine Portal-Seite existiert bereits.'));
            return;
        }
        
        // Fallback: Suche nach Seiten mit Shortcode (für Alt-Installationen)
        $existing_pages = get_posts(array(
            'post_type' => 'page',
            'posts_per_page' => 1,
            'post_status' => 'any',
            's' => '[dienstplan_hub]'
        ));
        
        if (!empty($existing_pages)) {
            // Speichere die gefundene Page-ID nachträglich
            update_option('dienstplan_portal_page_id', $existing_pages[0]->ID);
            wp_send_json_error(array('message' => 'Eine Portal-Seite existiert bereits.'));
            return;
        }
        
        // Erstelle die Seite
        $page_data = array(
            'post_title' => __('Dienstplan-Portal', 'dienstplan-verwaltung'),
            'post_content' => '[dienstplan_hub]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => get_current_user_id(),
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        
        $page_id = wp_insert_post($page_data);
        
        if (is_wp_error($page_id)) {
            wp_send_json_error(array('message' => 'Fehler beim Erstellen der Seite: ' . $page_id->get_error_message()));
            return;
        }
        
        if ($page_id === 0) {
            wp_send_json_error(array('message' => 'Fehler beim Erstellen der Seite.'));
            return;
        }
        
        // Speichere die Page-ID für spätere Verwaltung
        update_option('dienstplan_portal_page_id', $page_id);
        
        // Transient löschen
        delete_transient('dienstplan_show_portal_setup');
        
        wp_send_json_success(array(
            'message' => 'Portal-Seite erfolgreich erstellt!',
            'page_id' => $page_id,
            'page_title' => get_the_title($page_id),
            'edit_url' => admin_url('post.php?post=' . $page_id . '&action=edit'),
            'view_url' => get_permalink($page_id)
        ));
    }
    
    /**
     * AJAX: Portal-Setup-Notice schließen
     *
     * @since 0.6.6
     */
    public function ajax_dismiss_portal_notice() {
        // Nonce-Prüfung
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dp_dismiss_portal_notice')) {
            wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen.'));
            return;
        }
        
        // Transient löschen
        delete_transient('dienstplan_show_portal_setup');
        
        wp_send_json_success();
    }
    
    /**
     * AJAX: Portal-Seite löschen (vom Dashboard)
     *
     * @since 0.6.6
     */
    public function ajax_delete_portal_page() {
        // Nonce-Prüfung
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dp_delete_portal_page')) {
            wp_send_json_error(array('message' => 'Sicherheitsprüfung fehlgeschlagen.'));
            return;
        }
        
        // Berechtigungsprüfung
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }
        
        // Hole gespeicherte Page-ID
        $page_id = get_option('dienstplan_portal_page_id', 0);
        
        if (!$page_id) {
            wp_send_json_error(array('message' => 'Keine Portal-Seite gefunden.'));
            return;
        }
        
        // Prüfe ob Seite existiert
        $page = get_post($page_id);
        if (!$page) {
            // Option aufräumen wenn Seite nicht mehr existiert
            delete_option('dienstplan_portal_page_id');
            wp_send_json_error(array('message' => 'Seite existiert nicht mehr.'));
            return;
        }
        
        // Lösche die Seite
        $result = wp_delete_post($page_id, true); // true = permanent löschen
        
        if (!$result) {
            wp_send_json_error(array('message' => 'Fehler beim Löschen der Seite.'));
            return;
        }
        
        // Option löschen
        delete_option('dienstplan_portal_page_id');
        
        wp_send_json_success(array(
            'message' => 'Portal-Seite erfolgreich gelöscht!'
        ));
    }
    
    /**
     * AJAX: Portal-Zugriff für Mitarbeiter aktivieren
     *
     * @since 0.7.0
     */
    public function ajax_activate_portal_access() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }
        
        $mitarbeiter_id = isset($_POST['mitarbeiter_id']) ? intval($_POST['mitarbeiter_id']) : 0;
        
        if (!$mitarbeiter_id) {
            wp_send_json_error(array('message' => 'Keine Mitarbeiter-ID angegeben.'));
            return;
        }
        
        // Lade Mitarbeiter-Daten
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        $mitarbeiter = $db->get_mitarbeiter($mitarbeiter_id);
        
        if (!$mitarbeiter) {
            wp_send_json_error(array('message' => 'Mitarbeiter nicht gefunden.'));
            return;
        }
        
        // Prüfe ob bereits User existiert
        if ($mitarbeiter->user_id) {
            wp_send_json_error(array('message' => 'Mitarbeiter hat bereits Portal-Zugriff.'));
            return;
        }
        
        // Prüfe ob E-Mail vorhanden
        if (empty($mitarbeiter->email)) {
            wp_send_json_error(array('message' => 'Mitarbeiter benötigt eine E-Mail-Adresse für Portal-Zugriff.'));
            return;
        }
        
        // Prüfe ob E-Mail bereits verwendet wird
        if (email_exists($mitarbeiter->email)) {
            wp_send_json_error(array('message' => 'Diese E-Mail-Adresse wird bereits von einem anderen Benutzer verwendet.'));
            return;
        }
        
        // Generiere Username
        $username_base = sanitize_user(strtolower($mitarbeiter->vorname . '_' . $mitarbeiter->nachname), true);
        $username = $username_base;
        $counter = 1;
        
        // Stelle sicher dass Username einzigartig ist
        while (username_exists($username)) {
            $username = $username_base . '_' . $counter;
            $counter++;
        }
        
        // Generiere temporäres Passwort
        $password = wp_generate_password(12, true, true);
        
        // Erstelle WordPress-User
        $user_id = wp_create_user($username, $password, $mitarbeiter->email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => 'Fehler beim Erstellen des Benutzers: ' . $user_id->get_error_message()));
            return;
        }
        
        // Setze Crew-Rolle
        $user = new WP_User($user_id);
        $user->set_role(Dienstplan_Roles::ROLE_CREW);
        
        // Update User-Meta
        update_user_meta($user_id, 'first_name', $mitarbeiter->vorname);
        update_user_meta($user_id, 'last_name', $mitarbeiter->nachname);
        update_user_meta($user_id, 'show_admin_bar_front', false);
        
        // Verlinke Mitarbeiter mit User
        $db->update_mitarbeiter($mitarbeiter_id, array('user_id' => $user_id));
        $this->sync_user_verein_assignments($db, $mitarbeiter_id, $user_id);
        
        // Sende E-Mail mit Login-Daten
        $portal_page_id = get_option('dienstplan_portal_page_id', 0);
        $login_url = $portal_page_id ? get_permalink($portal_page_id) : wp_login_url();
        
        $email_subject = sprintf(__('[%s] Zugang zum Dienstplan-Portal', 'dienstplan-verwaltung'), get_bloginfo('name'));
        
        $email_body = sprintf(
            __("Hallo %s,\n\nfür dich wurde ein Zugang zum Dienstplan-Portal erstellt.\n\nHier sind deine Login-Daten:\n\nBenutzername: %s\nPasswort: %s\n\nPortal-Link: %s\n\nBitte ändere dein Passwort nach dem ersten Login.\n\nViele Grüße\n%s", 'dienstplan-verwaltung'),
            $mitarbeiter->vorname,
            $username,
            $password,
            $login_url,
            get_bloginfo('name')
        );
        
        $email_sent = wp_mail($mitarbeiter->email, $email_subject, $email_body);
        
        wp_send_json_success(array(
            'message' => 'Portal-Zugriff erfolgreich aktiviert!' . ($email_sent ? ' Login-Daten wurden per E-Mail versendet.' : ' Hinweis: E-Mail konnte nicht versendet werden.'),
            'user_id' => $user_id,
            'username' => $username
        ));
    }
    
    /**
     * AJAX: Portal-Zugriff für Mitarbeiter deaktivieren
     *
     * @since 0.7.0
     */
    public function ajax_deactivate_portal_access() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }
        
        $mitarbeiter_id = isset($_POST['mitarbeiter_id']) ? intval($_POST['mitarbeiter_id']) : 0;
        
        if (!$mitarbeiter_id) {
            wp_send_json_error(array('message' => 'Keine Mitarbeiter-ID angegeben.'));
            return;
        }
        
        // Lade Mitarbeiter-Daten
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        $mitarbeiter = $db->get_mitarbeiter($mitarbeiter_id);
        
        if (!$mitarbeiter || !$mitarbeiter->user_id) {
            wp_send_json_error(array('message' => 'Mitarbeiter hat keinen Portal-Zugriff.'));
            return;
        }
        
        // Deaktiviere User (nicht löschen, nur Rolle entziehen)
        $user = new WP_User($mitarbeiter->user_id);
        
        // Entferne alle Rollen
        $user->set_role(''); // Leere Rolle = kein Zugriff
        
        // Entferne Verlinkung (optional: kann auch bestehen bleiben für Audit)
        $db->update_mitarbeiter($mitarbeiter_id, array('user_id' => null));
        $db->delete_user_verein_assignments($mitarbeiter->user_id);
        
        wp_send_json_success(array(
            'message' => 'Portal-Zugriff erfolgreich deaktiviert.'
        ));
    }
    
    /**
     * AJAX: Login-Daten erneut senden
     *
     * @since 0.7.0
     */
    public function ajax_resend_login_credentials() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }
        
        $mitarbeiter_id = isset($_POST['mitarbeiter_id']) ? intval($_POST['mitarbeiter_id']) : 0;
        
        if (!$mitarbeiter_id) {
            wp_send_json_error(array('message' => 'Keine Mitarbeiter-ID angegeben.'));
            return;
        }
        
        // Lade Mitarbeiter-Daten
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        $mitarbeiter = $db->get_mitarbeiter($mitarbeiter_id);
        
        if (!$mitarbeiter || !$mitarbeiter->user_id) {
            wp_send_json_error(array('message' => 'Mitarbeiter hat keinen Portal-Zugriff.'));
            return;
        }
        
        // Lade User-Daten
        $user = get_userdata($mitarbeiter->user_id);
        if (!$user) {
            wp_send_json_error(array('message' => 'Benutzer nicht gefunden.'));
            return;
        }
        
        // Generiere neues temporäres Passwort
        $new_password = wp_generate_password(12, true, true);
        wp_set_password($new_password, $user->ID);
        
        // Sende E-Mail mit neuen Login-Daten
        $portal_page_id = get_option('dienstplan_portal_page_id', 0);
        $login_url = $portal_page_id ? get_permalink($portal_page_id) : wp_login_url();
        
        $email_subject = sprintf(__('[%s] Neue Login-Daten für das Dienstplan-Portal', 'dienstplan-verwaltung'), get_bloginfo('name'));
        
        $email_body = sprintf(
            __("Hallo %s,\n\nwie gewünscht erhältst du hier neue Login-Daten für das Dienstplan-Portal:\n\nBenutzername: %s\nNeues Passwort: %s\n\nPortal-Link: %s\n\nBitte ändere dein Passwort nach dem Login.\n\nViele Grüße\n%s", 'dienstplan-verwaltung'),
            $mitarbeiter->vorname,
            $user->user_login,
            $new_password,
            $login_url,
            get_bloginfo('name')
        );
        
        $email_sent = wp_mail($mitarbeiter->email, $email_subject, $email_body);
        
        if ($email_sent) {
            wp_send_json_success(array(
                'message' => 'Login-Daten wurden erfolgreich per E-Mail versendet.'
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Fehler beim Versenden der E-Mail. Das Passwort wurde zurückgesetzt, konnte aber nicht versendet werden.'
            ));
        }
    }

    /**
     * Synchronisiert direkte User↔Verein-Zuordnungen
     * anhand der bisherigen Dienste eines Mitarbeiters.
     *
     * @param Dienstplan_Database $db
     * @param int $mitarbeiter_id
     * @param int $user_id
     * @return void
     */
    private function sync_user_verein_assignments($db, $mitarbeiter_id, $user_id) {
        $mitarbeiter_id = intval($mitarbeiter_id);
        $user_id = intval($user_id);

        if ($mitarbeiter_id <= 0 || $user_id <= 0) {
            return;
        }

        $wpdb = $db->get_wpdb();
        $prefix = $db->get_prefix();

        $verein_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT d.verein_id
             FROM {$prefix}dienst_slots s
             INNER JOIN {$prefix}dienste d ON s.dienst_id = d.id
             WHERE s.mitarbeiter_id = %d
               AND d.verein_id IS NOT NULL
               AND d.verein_id > 0",
            $mitarbeiter_id
        ));

        if (empty($verein_ids)) {
            return;
        }

        foreach ($verein_ids as $verein_id) {
            $db->assign_user_to_verein($user_id, intval($verein_id), $mitarbeiter_id);
        }
    }

    /**
     * Synchronisiert direkte User↔Verein-Zuordnungen für Vereins-Verantwortliche.
     *
     * Es werden nur direkte Zuordnungen (ohne mitarbeiter_id) angepasst,
     * damit dienstbasierte Zuordnungen unverändert bleiben.
     *
     * @param Dienstplan_Database $db
     * @param int $verein_id
     * @param int[] $user_ids
     * @return void
     */
    private function sync_direct_verein_user_assignments($db, $verein_id, $user_ids) {
        $verein_id = intval($verein_id);

        if ($verein_id <= 0) {
            return;
        }

        $desired_ids = array_values(array_unique(array_filter(array_map('intval', (array) $user_ids))));
        $current_ids_raw = $db->get_direct_verein_user_ids($verein_id);
        $current_ids = array();

        foreach ((array) $current_ids_raw as $current_id) {
            $current_ids[] = intval($current_id);
        }

        foreach ($current_ids as $current_id) {
            if (!in_array($current_id, $desired_ids, true)) {
                $db->delete_direct_user_verein_assignment($current_id, $verein_id);
            }
        }

        foreach ($desired_ids as $desired_id) {
            if (!in_array($desired_id, $current_ids, true)) {
                $db->assign_direct_user_to_verein($desired_id, $verein_id);
            }
        }
    }
    
    /**
     * AJAX: Dienste-Übersicht per E-Mail an Mitarbeiter senden
     *
     * @since 0.9.5.7
     */
    public function ajax_resend_dienste_email() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');

        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }

        $mitarbeiter_id = isset($_POST['mitarbeiter_id']) ? intval($_POST['mitarbeiter_id']) : 0;

        if (!$mitarbeiter_id) {
            wp_send_json_error(array('message' => 'Keine Mitarbeiter-ID angegeben.'));
            return;
        }

        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db   = new Dienstplan_Database($this->db_prefix);
        $ma   = $db->get_mitarbeiter($mitarbeiter_id);

        if (!$ma) {
            wp_send_json_error(array('message' => 'Mitarbeiter nicht gefunden.'));
            return;
        }

        if (empty($ma->email)) {
            wp_send_json_error(array('message' => 'Mitarbeiter hat keine E-Mail-Adresse.'));
            return;
        }

        // Dienste laden
        global $wpdb;
        $dienste = $wpdb->get_results($wpdb->prepare(
            "SELECT s.von_zeit, s.bis_zeit,
                    t.name  AS taetigkeit,
                    b.name  AS bereich,
                    ve.name  AS veranstaltung,
                    v.name  AS verein,
                    vt.tag_datum AS tag_datum
             FROM {$wpdb->prefix}dp_dienst_slots s
             INNER JOIN {$wpdb->prefix}dp_dienste d       ON s.dienst_id       = d.id
             LEFT  JOIN {$wpdb->prefix}dp_taetigkeiten t  ON d.taetigkeit_id   = t.id
             LEFT  JOIN {$wpdb->prefix}dp_bereiche b      ON d.bereich_id      = b.id
             LEFT  JOIN {$wpdb->prefix}dp_vereine v       ON d.verein_id       = v.id
             LEFT  JOIN {$wpdb->prefix}dp_veranstaltungen ve ON d.veranstaltung_id = ve.id
             LEFT  JOIN {$wpdb->prefix}dp_veranstaltung_tage vt ON d.tag_id    = vt.id
             WHERE s.mitarbeiter_id = %d
             ORDER BY vt.tag_datum ASC, s.von_zeit ASC",
            $mitarbeiter_id
        ));

        if (empty($dienste)) {
            wp_send_json_error(array('message' => 'Für diesen Mitarbeiter sind keine Dienste hinterlegt.'));
            return;
        }

        $subject = sprintf('[%s] Deine Dienst-Übersicht', get_bloginfo('name'));

        $body  = 'Hallo ' . $ma->vorname . ",\n\n";
        $body .= "hier ist deine aktuelle Übersicht aller zugewiesenen Dienste:\n\n";
        $body .= str_repeat('-', 50) . "\n";

        $current_event = '';
        foreach ($dienste as $d) {
            if ($d->veranstaltung !== $current_event) {
                $current_event = $d->veranstaltung;
                $body .= "\n📅 Veranstaltung: " . $current_event . "\n";
                if ($d->verein) {
                    $body .= "   Verein: " . $d->verein . "\n";
                }
                $body .= str_repeat('-', 40) . "\n";
            }
            $datum  = $d->tag_datum ? date_i18n('d.m.Y (l)', strtotime($d->tag_datum)) : 'N/A';
            $von    = $d->von_zeit  ? substr($d->von_zeit,  0, 5) : '';
            $bis    = $d->bis_zeit  ? substr($d->bis_zeit,  0, 5) : '';
            $body  .= "  • {$datum}";
            if ($von && $bis) {
                $body .= ", {$von} – {$bis} Uhr";
            }
            $body .= "\n";
            if ($d->taetigkeit) {
                $body .= "    Tätigkeit: {$d->taetigkeit}";
                if ($d->bereich) {
                    $body .= " ({$d->bereich})";
                }
                $body .= "\n";
            }
        }

        $body .= "\n" . str_repeat('-', 50) . "\n";
        $body .= "Gesamt: " . count($dienste) . " Dienst" . (count($dienste) !== 1 ? 'e' : '') . "\n\n";
        $body .= "Bei Fragen wende dich bitte an den Veranstalter.\n\n";
        $body .= "Viele Grüße\n" . get_bloginfo('name');

        $sent = wp_mail($ma->email, $subject, $body);

        if ($sent) {
            wp_send_json_success(array(
                'message' => 'Dienste-Übersicht wurde erfolgreich an ' . $ma->email . ' versendet.'
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'E-Mail konnte nicht gesendet werden. Bitte WordPress-Mailkonfiguration prüfen.'
            ));
        }
    }

    /**
     * AJAX: Bulk-Portal-Zugriff aktivieren
     *
     * @since 0.7.0
     */
    public function ajax_bulk_activate_portal_access() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }
        
        $mitarbeiter_ids = isset($_POST['mitarbeiter_ids']) ? array_map('intval', $_POST['mitarbeiter_ids']) : array();
        
        if (empty($mitarbeiter_ids)) {
            wp_send_json_error(array('message' => 'Keine Mitarbeiter-IDs angegeben.'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $success_count = 0;
        $error_count = 0;
        $errors = array();
        
        foreach ($mitarbeiter_ids as $mitarbeiter_id) {
            $mitarbeiter = $db->get_mitarbeiter($mitarbeiter_id);
            
            if (!$mitarbeiter) {
                $errors[] = "Mitarbeiter #$mitarbeiter_id nicht gefunden";
                $error_count++;
                continue;
            }
            
            // Bereits Portal-Zugriff?
            if ($mitarbeiter->user_id) {
                $errors[] = "{$mitarbeiter->vorname} {$mitarbeiter->nachname} hat bereits Portal-Zugriff";
                $error_count++;
                continue;
            }
            
            // E-Mail vorhanden?
            if (empty($mitarbeiter->email)) {
                $errors[] = "{$mitarbeiter->vorname} {$mitarbeiter->nachname}: Keine E-Mail-Adresse";
                $error_count++;
                continue;
            }
            
            // E-Mail bereits verwendet?
            if (email_exists($mitarbeiter->email)) {
                $errors[] = "{$mitarbeiter->vorname} {$mitarbeiter->nachname}: E-Mail wird bereits verwendet";
                $error_count++;
                continue;
            }
            
            // Generiere Username
            $username_base = sanitize_user(strtolower($mitarbeiter->vorname . '_' . $mitarbeiter->nachname), true);
            $username = $username_base;
            $counter = 1;
            
            while (username_exists($username)) {
                $username = $username_base . '_' . $counter;
                $counter++;
            }
            
            // Generiere Passwort
            $password = wp_generate_password(12, true, true);
            
            // Erstelle User
            $user_id = wp_create_user($username, $password, $mitarbeiter->email);
            
            if (is_wp_error($user_id)) {
                $errors[] = "{$mitarbeiter->vorname} {$mitarbeiter->nachname}: " . $user_id->get_error_message();
                $error_count++;
                continue;
            }
            
            // Setze Rolle
            $user = new WP_User($user_id);
            $user->set_role(Dienstplan_Roles::ROLE_CREW);
            
            // Update Meta
            update_user_meta($user_id, 'first_name', $mitarbeiter->vorname);
            update_user_meta($user_id, 'last_name', $mitarbeiter->nachname);
            update_user_meta($user_id, 'show_admin_bar_front', false);
            
            // Verlinke mit Mitarbeiter
            $db->update_mitarbeiter($mitarbeiter_id, array('user_id' => $user_id));
            $this->sync_user_verein_assignments($db, $mitarbeiter_id, $user_id);
            
            // Sende E-Mail
            $portal_page_id = get_option('dienstplan_portal_page_id', 0);
            $login_url = $portal_page_id ? get_permalink($portal_page_id) : wp_login_url();
            
            $email_subject = sprintf(__('[%s] Zugang zum Dienstplan-Portal', 'dienstplan-verwaltung'), get_bloginfo('name'));
            $email_body = sprintf(
                __("Hallo %s,\n\nfür dich wurde ein Zugang zum Dienstplan-Portal erstellt.\n\nHier sind deine Login-Daten:\n\nBenutzername: %s\nPasswort: %s\n\nPortal-Link: %s\n\nBitte ändere dein Passwort nach dem ersten Login.\n\nViele Grüße\n%s", 'dienstplan-verwaltung'),
                $mitarbeiter->vorname,
                $username,
                $password,
                $login_url,
                get_bloginfo('name')
            );
            
            wp_mail($mitarbeiter->email, $email_subject, $email_body);
            $success_count++;
        }
        
        $message = "Portal-Zugriff aktiviert: $success_count erfolgreich";
        if ($error_count > 0) {
            $message .= ", $error_count Fehler";
            if (!empty($errors)) {
                $message .= ":\n- " . implode("\n- ", array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= "\n... und " . (count($errors) - 5) . " weitere";
                }
            }
        }
        
        wp_send_json_success(array(
            'message' => $message,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'errors' => $errors
        ));
    }
    
    /**
     * AJAX: Bulk-Portal-Zugriff deaktivieren
     *
     * @since 0.7.0
     */
    public function ajax_bulk_deactivate_portal_access() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung.'));
            return;
        }
        
        $mitarbeiter_ids = isset($_POST['mitarbeiter_ids']) ? array_map('intval', $_POST['mitarbeiter_ids']) : array();
        
        if (empty($mitarbeiter_ids)) {
            wp_send_json_error(array('message' => 'Keine Mitarbeiter-IDs angegeben.'));
            return;
        }
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($mitarbeiter_ids as $mitarbeiter_id) {
            $mitarbeiter = $db->get_mitarbeiter($mitarbeiter_id);
            
            if (!$mitarbeiter || !$mitarbeiter->user_id) {
                $error_count++;
                continue;
            }
            
            // Entferne Rolle
            $user = new WP_User($mitarbeiter->user_id);
            $user->set_role('');
            
            // Entferne Verlinkung
            $db->update_mitarbeiter($mitarbeiter_id, array('user_id' => null));
            $db->delete_user_verein_assignments($mitarbeiter->user_id);
            
            $success_count++;
        }
        
        wp_send_json_success(array(
            'message' => "Portal-Zugriff deaktiviert: $success_count erfolgreich" . ($error_count > 0 ? ", $error_count übersprungen" : ""),
            'success_count' => $success_count,
            'error_count' => $error_count
        ));
    }
    
    /**
     * Export: Mitarbeiter als CSV
     *
     * @since 0.7.0
     */
    public function ajax_export_mitarbeiter() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.');
        }
        
        $ids = isset($_GET['ids']) ? array_map('intval', explode(',', $_GET['ids'])) : array();
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $mitarbeiter = array();
        if (!empty($ids)) {
            foreach ($ids as $id) {
                $ma = $db->get_mitarbeiter($id);
                if ($ma) {
                    $mitarbeiter[] = $ma;
                }
            }
        } else {
            $mitarbeiter = $db->get_mitarbeiter_with_stats();
        }
        
        // CSV Headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="mitarbeiter_export_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM für Excel UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header-Zeile
        fputcsv($output, array('ID', 'Vorname', 'Nachname', 'E-Mail', 'Telefon', 'Portal-Zugriff', 'Anzahl Dienste'), ';');
        
        // Daten
        foreach ($mitarbeiter as $ma) {
            fputcsv($output, array(
                $ma->id,
                $ma->vorname,
                $ma->nachname,
                $ma->email ?? '',
                $ma->telefon ?? '',
                $ma->user_id ? 'Ja' : 'Nein',
                $ma->dienst_count ?? 0
            ), ';');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export: Portal-Zugänge als CSV
     *
     * @since 0.7.0
     */
    public function ajax_export_portal_credentials() {
        check_ajax_referer('dp_ajax_nonce', 'nonce');
        
        if (!Dienstplan_Roles::can_manage_events() && !current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.');
        }
        
        $ids = isset($_GET['ids']) ? array_map('intval', explode(',', $_GET['ids'])) : array();
        
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
        $db = new Dienstplan_Database($this->db_prefix);
        
        $mitarbeiter = array();
        if (!empty($ids)) {
            foreach ($ids as $id) {
                $ma = $db->get_mitarbeiter($id);
                if ($ma && $ma->user_id) {
                    $mitarbeiter[] = $ma;
                }
            }
        } else {
            // Alle Mitarbeiter mit Portal-Zugriff
            global $wpdb;
            $table = $wpdb->prefix . 'dp_mitarbeiter';
            $results = $wpdb->get_results("SELECT * FROM $table WHERE user_id IS NOT NULL ORDER BY nachname, vorname");
            $mitarbeiter = $results;
        }
        
        // CSV Headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="portal_zugaenge_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM für Excel UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header-Zeile
        fputcsv($output, array('ID', 'Vorname', 'Nachname', 'E-Mail', 'Benutzername', 'Portal-Link'), ';');
        
        $portal_page_id = get_option('dienstplan_portal_page_id', 0);
        $login_url = $portal_page_id ? get_permalink($portal_page_id) : wp_login_url();
        
        // Daten
        foreach ($mitarbeiter as $ma) {
            if (!$ma->user_id) continue;
            
            $user = get_userdata($ma->user_id);
            if (!$user) continue;
            
            fputcsv($output, array(
                $ma->id,
                $ma->vorname,
                $ma->nachname,
                $ma->email ?? '',
                $user->user_login,
                $login_url
            ), ';');
        }
        
        fclose($output);
        exit;
    }
}

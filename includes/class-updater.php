<?php
/**
 * Plugin Updater über Git
 * 
 * Automatische Updates über Git-Repository
 * 
 * @since      0.9.0
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/includes
 */

class Dienstplan_Updater {

    /**
     * Plugin-Slug
     */
    private $plugin_slug;

    /**
     * Plugin-Basename
     */
    private $plugin_basename;

    /**
     * Plugin-Verzeichnis
     */
    private $plugin_dir;

    /**
     * Git-Repository URL
     */
    private $git_repo_url;

    /**
     * Branch für Updates
     */
    private $git_branch;

    /**
     * Aktuelle Plugin-Version
     */
    private $current_version;

    /**
     * Update-Informationen
     */
    private $update_info;

    /**
     * War Plugin vor dem Update aktiv?
     *
     * @var bool
     */
    private $was_active_before_update = false;

    /**
     * War Plugin netzwerkweit aktiv?
     *
     * @var bool
     */
    private $was_network_active_before_update = false;

    /**
     * Git Command Pfad
     * @var string
     */
    private $git_cmd = 'git';

    /**
     * Git verfügbar?
     * @var bool
     */
    private $git_available = false;

    /**
     * Branch-Name für Shell-Kommandos bereinigt.
     *
     * @var string
     */
    private $safe_git_branch = 'main';

    /**
     * Initialisiert den Updater
     */
    public function __construct() {
        $this->plugin_slug = 'dienstplan-verwaltung';
        $this->plugin_basename = plugin_basename(DIENSTPLAN_PLUGIN_FILE);
        $this->plugin_dir = DIENSTPLAN_PLUGIN_PATH;
        $this->current_version = DIENSTPLAN_VERSION;
        
        // Git-Repository Konfiguration
        $this->git_repo_url = 'https://github.com/VereinsringWasserlos/dienstplan-verwaltung.git';
        $this->git_branch = 'main';
        $this->safe_git_branch = $this->sanitize_git_ref($this->git_branch);

        // Finde Git-Executable und prüfe Verfügbarkeit
        $this->find_git_executable();
        $this->git_available = $this->is_git_available();

        // WordPress Hooks für Update-System
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        add_action('upgrader_process_complete', array($this, 'after_update'), 10, 2);
        add_filter('upgrader_pre_install', array($this, 'capture_pre_update_state'), 10, 2);
        add_filter('upgrader_pre_download', array($this, 'handle_github_download'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'post_install_rename'), 10, 3);
        add_action('wp_ajax_dienstplan_download_update', array($this, 'ajax_download_update'));
        
        // Automatische Updates aktivieren wenn gewünscht
        add_filter('auto_update_plugin', array($this, 'enable_auto_update'), 10, 2);
    }

    /**
     * Merkt sich den Aktivstatus vor der Installation eines Plugin-Updates.
     *
     * @param bool|WP_Error $response  Vorheriger Install-Status
     * @param array         $hook_extra Kontextinformationen des Upgraders
     * @return bool|WP_Error
     */
    public function capture_pre_update_state($response, $hook_extra) {
        if (!is_array($hook_extra)) {
            return $response;
        }

        if (!isset($hook_extra['type']) || $hook_extra['type'] !== 'plugin') {
            return $response;
        }

        $targets = array();
        if (!empty($hook_extra['plugin'])) {
            $targets[] = $hook_extra['plugin'];
        }
        if (!empty($hook_extra['plugins']) && is_array($hook_extra['plugins'])) {
            $targets = array_merge($targets, $hook_extra['plugins']);
        }

        if (!in_array($this->plugin_basename, $targets, true)) {
            return $response;
        }

        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $this->was_active_before_update = is_plugin_active($this->plugin_basename);
        $this->was_network_active_before_update = function_exists('is_plugin_active_for_network')
            ? is_plugin_active_for_network($this->plugin_basename)
            : false;

        return $response;
    }

    /**
     * Reaktiviert das Plugin nach erfolgreichem Update, wenn es davor aktiv war.
     */
    private function restore_activation_after_update() {
        if (!$this->was_active_before_update) {
            return;
        }

        if (!function_exists('is_plugin_active') || !function_exists('activate_plugin')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if (is_plugin_active($this->plugin_basename)) {
            return;
        }

        $network_wide = is_multisite() && $this->was_network_active_before_update;
        $result = activate_plugin($this->plugin_basename, '', $network_wide, true);

        if (is_wp_error($result)) {
            error_log('DP Updater: Reaktivierung nach Update fehlgeschlagen: ' . $result->get_error_message());
            return;
        }

        error_log('DP Updater: Plugin nach Update erfolgreich reaktiviert.');
    }

    /**
     * Findet den Git-Executable Pfad
     */
    private function find_git_executable() {
        // Mögliche Git-Pfade (Windows)
        $possible_paths = array(
            'C:\\Program Files\\Git\\cmd\\git.exe',
            'C:\\Program Files\\Git\\bin\\git.exe',
            'C:\\Program Files (x86)\\Git\\cmd\\git.exe',
            'C:\\Program Files (x86)\\Git\\bin\\git.exe',
            'git' // Fallback für PATH
        );

        foreach ($possible_paths as $path) {
            $output = array();
            $return_var = 0;
            @exec('"' . $path . '" --version 2>&1', $output, $return_var);
            
            if ($return_var === 0) {
                $this->git_cmd = $path;
                error_log('DP Updater: Git gefunden: ' . $path);
                return;
            }
        }
        
        error_log('DP Updater: Kein Git-Executable gefunden');
    }

    /**
     * Prüft auf Updates
     */
    public function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Hole Update-Informationen aus Git
        $update_info = $this->get_update_info();

        if ($update_info && version_compare($this->current_version, $update_info['version'], '<')) {
            $plugin_info = array(
                'slug' => $this->plugin_slug,
                'plugin' => $this->plugin_basename,
                'new_version' => $update_info['version'],
                'url' => $update_info['url'],
                'package' => $update_info['download_url'],
                'tested' => $update_info['tested'],
                'requires_php' => $update_info['requires_php'],
                'compatibility' => new stdClass(),
            );

            $transient->response[$this->plugin_basename] = (object) $plugin_info;
        } else {
            // Auch wenn kein Update verfügbar ist, Plugin in no_update Liste eintragen
            // damit die Auto-Update-Spalte angezeigt wird
            if (!isset($transient->no_update)) {
                $transient->no_update = array();
            }
            
            $plugin_info = array(
                'slug' => $this->plugin_slug,
                'plugin' => $this->plugin_basename,
                'new_version' => $this->current_version,
                'url' => $this->git_repo_url,
                'package' => '',
                'tested' => '6.4',
                'requires_php' => '7.4',
                'compatibility' => new stdClass(),
            );
            
            $transient->no_update[$this->plugin_basename] = (object) $plugin_info;
        }

        return $transient;
    }

    /**
     * Holt Update-Informationen (Git oder GitHub API)
     */
    private function get_update_info() {
        if (!empty($this->update_info)) {
            return $this->update_info;
        }

        // Prüfe ob Git-Repository konfiguriert ist
        if (empty($this->git_repo_url)) {
            return false;
        }

        // Cache für 12 Stunden
        $cache_key = 'dienstplan_update_info';
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Verwende Git wenn verfügbar, sonst GitHub API
        if ($this->git_available) {
            $update_info = $this->get_update_info_from_git();
        } else {
            $update_info = $this->get_update_info_from_github();
        }

        if ($update_info) {
            // Cache die Update-Info
            set_transient($cache_key, $update_info, 43200); // 12 Stunden
            $this->update_info = $update_info;
        }

        return $update_info;
    }

    /**
     * Holt Update-Informationen aus Git (Entwicklungsumgebung)
     */
    private function get_update_info_from_git() {
        try {
            // Hole Remote-Version aus Git
            $remote_version = $this->get_remote_version();
            
            if (!$remote_version) {
                return false;
            }

            return array(
                'version' => $remote_version,
                'url' => $this->git_repo_url,
                'download_url' => $this->get_download_url(),
                'tested' => '6.4',
                'requires_php' => '7.4',
                'changelog' => $this->get_changelog(),
            );

        } catch (Exception $e) {
            error_log('DP Updater: Git-Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Holt Update-Informationen von GitHub API (Produktionsserver)
     */
    private function get_update_info_from_github() {
        // GitHub API: Neuestes Release
        $api_url = 'https://api.github.com/repos/VereinsringWasserlos/dienstplan-verwaltung/releases/latest';

        $response = wp_remote_get($api_url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
            )
        ));

        $release_info = false;
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $release_data = json_decode($body, true);

            if (is_array($release_data) && !empty($release_data['tag_name'])) {
                // Version aus Tag (z.B. "v0.9.1" -> "0.9.1")
                $version = ltrim($release_data['tag_name'], 'v');

                // Finde ZIP-Asset
                $download_url = '';
                if (isset($release_data['assets']) && is_array($release_data['assets'])) {
                    foreach ($release_data['assets'] as $asset) {
                        if (isset($asset['name']) && strpos($asset['name'], '.zip') !== false) {
                            $download_url = $asset['browser_download_url'];
                            break;
                        }
                    }
                }

                // Fallback: Zipball URL
                if (empty($download_url) && isset($release_data['zipball_url'])) {
                    $download_url = $release_data['zipball_url'];
                }

                $release_info = array(
                    'version' => $version,
                    'url' => isset($release_data['html_url']) ? $release_data['html_url'] : $this->git_repo_url,
                    'download_url' => $download_url,
                    'tested' => '6.4',
                    'requires_php' => '7.4',
                    'changelog' => isset($release_data['body']) ? $release_data['body'] : ''
                );
            }
        }

        // Tag-Fallback: wenn kein Release existiert oder der neueste Tag neuer als das Release ist
        $tag_info = $this->get_latest_github_tag_info();

        if ($release_info && $tag_info) {
            if (version_compare($release_info['version'], $tag_info['version'], '>=')) {
                return $release_info;
            }
            return $tag_info;
        }

        if ($release_info) {
            return $release_info;
        }

        if ($tag_info) {
            return $tag_info;
        }

        if (is_wp_error($response)) {
            error_log('DP Updater: GitHub API Fehler: ' . $response->get_error_message());
        } else {
            error_log('DP Updater: Weder Release noch Tag-Informationen gefunden');
        }

        return false;
    }

    /**
     * Holt Informationen zum neuesten GitHub-Tag.
     * Dieser Fallback ist wichtig, wenn nur ein Tag gepusht wurde,
     * aber noch kein offizieller GitHub Release-Eintrag existiert.
     *
     * @return array|false
     */
    private function get_latest_github_tag_info() {
        $tags_api_url = 'https://api.github.com/repos/VereinsringWasserlos/dienstplan-verwaltung/tags?per_page=20';

        $response = wp_remote_get($tags_api_url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
            )
        ));

        if (is_wp_error($response)) {
            error_log('DP Updater: GitHub Tags API Fehler: ' . $response->get_error_message());
            return false;
        }

        $tags_data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($tags_data) || empty($tags_data)) {
            return false;
        }

        $latest_tag = '';
        $latest_version = '';

        foreach ($tags_data as $tag) {
            if (empty($tag['name'])) {
                continue;
            }

            $tag_name = (string) $tag['name'];
            $version = ltrim($tag_name, 'v');

            if (!preg_match('/^[0-9]+(\.[0-9]+)*$/', $version)) {
                continue;
            }

            if ($latest_version === '' || version_compare($version, $latest_version, '>')) {
                $latest_version = $version;
                $latest_tag = $tag_name;
            }
        }

        if ($latest_version === '' || $latest_tag === '') {
            return false;
        }

        return array(
            'version' => $latest_version,
            'url' => 'https://github.com/VereinsringWasserlos/dienstplan-verwaltung/releases/tag/' . rawurlencode($latest_tag),
            'download_url' => 'https://github.com/VereinsringWasserlos/dienstplan-verwaltung/archive/refs/tags/' . rawurlencode($latest_tag) . '.zip',
            'tested' => '6.4',
            'requires_php' => '7.4',
            'changelog' => 'Automatisch von Git-Tag ' . $latest_tag . ' erkannt.'
        );
    }

    /**
     * Prüft ob Git verfügbar ist
     */
    private function is_git_available() {
        // Prüfe ob .git Verzeichnis existiert
        if (!is_dir($this->plugin_dir . '.git')) {
            error_log('DP Updater: .git Verzeichnis nicht gefunden in ' . $this->plugin_dir);
            return false;
        }

        // Prüfe ob exec() Funktion verfügbar ist
        if (!function_exists('exec')) {
            error_log('DP Updater: exec() Funktion ist deaktiviert');
            return false;
        }

        // Prüfe ob git Befehl verfügbar ist
        $output = array();
        $return_var = 0;
        @exec('"' . $this->git_cmd . '" --version 2>&1', $output, $return_var);
        
        if ($return_var !== 0) {
            error_log('DP Updater: Git Befehl nicht verfügbar (Return: ' . $return_var . ')');
            return false;
        }
        
        return true;
    }

    /**
     * Holt Remote-Version aus Git
     */
    private function get_remote_version() {
        $current_dir = getcwd();
        chdir($this->plugin_dir);

        try {
            // Fetch Remote-Changes
            exec('"' . $this->git_cmd . '" fetch origin "' . $this->safe_git_branch . '" 2>&1', $output, $return_var);
            
            if ($return_var !== 0) {
                error_log('DP Updater: Git fetch fehlgeschlagen');
                chdir($current_dir);
                return false;
            }

            // Hole Version aus remote Plugin-Datei
            $remote_file_cmd = '"' . $this->git_cmd . '" show origin/' . $this->safe_git_branch . ':dienstplan-verwaltung.php';
            exec($remote_file_cmd . ' 2>&1', $file_output, $return_var);
            
            if ($return_var !== 0) {
                chdir($current_dir);
                return false;
            }

            // Parse Version aus Remote-Datei
            $file_content = implode("\n", $file_output);
            if (preg_match('/Version:\s*([0-9.]+)/i', $file_content, $matches)) {
                chdir($current_dir);
                return trim($matches[1]);
            }

            chdir($current_dir);
            return false;

        } catch (Exception $e) {
            chdir($current_dir);
            error_log('DP Updater: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generiert Download-URL
     */
    private function get_download_url() {
        // Für lokales Git-Repository: Erstelle temporäres Archiv
        return admin_url('admin-ajax.php?action=dienstplan_download_update');
    }

    /**
     * Benennt den extrahierten Plugin-Ordner nach der Installation auf den
     * korrekten Slug um. Verhindert falsche Verzeichnisnamen bei GitHub-ZIPs
     * (z.B. "dienstplan-verwaltung-v0.9.5.18-WBZKhI").
     *
     * @param bool|WP_Error $response True bei Erfolg, WP_Error bei Fehler.
     * @param array         $extra    Kontext-Informationen des Upgraders.
     * @param array         $result   Ergebnis der Installation mit 'destination' usw.
     * @return array|WP_Error Ggf. korrigiertes Ergebnis.
     */
    public function post_install_rename($response, $extra, $result) {
        global $wp_filesystem;

        // Nur für dieses Plugin relevant
        if (
            !is_array($extra) ||
            empty($extra['plugin']) ||
            $extra['plugin'] !== $this->plugin_basename
        ) {
            return $result;
        }

        $proper_destination = WP_PLUGIN_DIR . '/' . $this->plugin_slug;

        // Nur umbenennen, wenn der Ordner tatsächlich falsch heißt
        if (
            isset($result['destination']) &&
            $result['destination'] !== $proper_destination
        ) {
            if ($wp_filesystem->exists($proper_destination)) {
                $wp_filesystem->delete($proper_destination, true);
            }

            $moved = $wp_filesystem->move($result['destination'], $proper_destination, true);

            if (!$moved) {
                return new WP_Error(
                    'rename_failed',
                    sprintf(
                        'Plugin-Ordner konnte nicht von "%s" nach "%s" umbenannt werden.',
                        $result['destination'],
                        $proper_destination
                    )
                );
            }

            $result['destination']      = $proper_destination;
            $result['destination_name'] = $this->plugin_slug;

            error_log('DP Updater: Plugin-Ordner erfolgreich auf "' . $this->plugin_slug . '" umbenannt.');
        }

        return $result;
    }

    /**
     * Fängt GitHub-Downloads ab und lädt mit korrekten Headern herunter.
     * Nötig, weil WordPress wp_safe_remote_get() nutzt, das keine Redirects
     * zu github CDN-Domains (objects.githubusercontent.com) erlaubt → 503.
     *
     * @param bool|WP_Error $reply    Bisheriger Reply (false = noch nicht behandelt)
     * @param string        $package  Download-URL
     * @param WP_Upgrader   $upgrader Upgrader-Instanz
     * @return string|WP_Error Pfad zur temporären Datei oder Fehler
     */
    public function handle_github_download($reply, $package, $upgrader) {
        // Nur für GitHub-Pakete eingreifen
        if (
            strpos($package, 'github.com') === false &&
            strpos($package, 'api.github.com') === false
        ) {
            return $reply;
        }

        // Temporäre Datei anlegen
        $tmpfname = wp_tempnam($package);
        if (!$tmpfname) {
            return new WP_Error('http_no_file', __('Temporäre Datei konnte nicht erstellt werden.'));
        }

        $headers = array(
            'Accept'     => 'application/octet-stream',
            'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
        );

        // Optionaler GitHub-Token (in WP-Optionen speicherbar)
        $github_token = get_option('dienstplan_github_token', '');
        if (!empty($github_token)) {
            $headers['Authorization'] = 'token ' . $github_token;
        }

        // wp_remote_get statt wp_safe_remote_get: folgt Redirects zu CDN-Domains
        $response = wp_remote_get($package, array(
            'timeout'  => 300,
            'stream'   => true,
            'filename' => $tmpfname,
            'headers'  => $headers,
        ));

        if (is_wp_error($response)) {
            @unlink($tmpfname);
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if (200 !== (int) $response_code) {
            @unlink($tmpfname);
            return new WP_Error(
                'http_404',
                sprintf(
                    /* translators: %s: HTTP-Statuscode */
                    __('Der Download ist fehlgeschlagen. HTTP-Fehler: %s'),
                    $response_code
                )
            );
        }

        return $tmpfname;
    }

    /**
     * Holt Changelog aus Git
     */
    private function get_changelog() {
        $changelog_file = $this->plugin_dir . 'documentation/CHANGELOG.md';
        
        if (file_exists($changelog_file)) {
            return file_get_contents($changelog_file);
        }
        
        return 'Keine Changelog-Informationen verfügbar.';
    }

    /**
     * AJAX-Handler: Liefert ein Git-Archiv als ZIP für WordPress-Updates
     * in der Entwicklungsumgebung (wenn Git verfügbar ist).
     */
    public function ajax_download_update() {
        if (!current_user_can('update_plugins')) {
            wp_die('Keine Berechtigung', '', array('response' => 403));
        }

        if (!$this->git_available) {
            wp_die('Git nicht verfügbar', '', array('response' => 500));
        }

        $zipfile = sys_get_temp_dir() . '/dienstplan-verwaltung-update-' . time() . '.zip';

        $current_dir = getcwd();
        chdir($this->plugin_dir);

        $archive_cmd = '"' . $this->git_cmd . '" archive --format=zip --prefix=dienstplan-verwaltung/ '
            . 'origin/' . $this->safe_git_branch
            . ' -o ' . escapeshellarg($zipfile) . ' 2>&1';

        exec($archive_cmd, $output, $return_var);
        chdir($current_dir);

        if ($return_var !== 0 || !file_exists($zipfile)) {
            error_log('DP Updater: git archive fehlgeschlagen: ' . implode("\n", $output));
            wp_die('Fehler beim Erstellen des Update-Archivs', '', array('response' => 500));
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="dienstplan-verwaltung.zip"');
        header('Content-Length: ' . filesize($zipfile));
        header('Cache-Control: no-cache, no-store, must-revalidate');

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        readfile($zipfile);
        @unlink($zipfile);
        exit;
    }


    public function plugin_info($false, $action, $response) {
        // Nur für unser Plugin
        if ($action !== 'plugin_information' || $response->slug !== $this->plugin_slug) {
            return $false;
        }

        $update_info = $this->get_update_info();
        
        if (!$update_info) {
            return $false;
        }

        $plugin_info = new stdClass();
        $plugin_info->name = 'Dienstplan Verwaltung V2';
        $plugin_info->slug = $this->plugin_slug;
        $plugin_info->version = $update_info['version'];
        $plugin_info->author = '<a href="https://github.com">Ihr Name</a>';
        $plugin_info->homepage = $this->git_repo_url;
        $plugin_info->requires = '5.8';
        $plugin_info->tested = $update_info['tested'];
        $plugin_info->requires_php = $update_info['requires_php'];
        $plugin_info->download_link = $update_info['download_url'];
        $plugin_info->sections = array(
            'description' => 'Moderne Dienstplan-Verwaltung für Events und Veranstaltungen',
            'changelog' => $update_info['changelog'],
        );

        return $plugin_info;
    }

    /**
     * Führt Update durch (Git oder WordPress-Standard)
     */
    public function perform_update() {
        // Auf Produktionsservern ohne Git: WordPress-Update direkt ausführen
        if (!$this->git_available) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

            // Update-Informationen frisch laden
            wp_update_plugins();
            $update_plugins = get_site_transient('update_plugins');

            if (
                !isset($update_plugins->response) ||
                !is_array($update_plugins->response) ||
                !isset($update_plugins->response[$this->plugin_basename])
            ) {
                return array(
                    'success' => false,
                    'message' => 'Kein Update verfügbar oder WordPress konnte keine Update-Informationen laden.',
                    'output' => ''
                );
            }

            $skin = new Automatic_Upgrader_Skin();
            $upgrader = new Plugin_Upgrader($skin);
            $result = $upgrader->upgrade($this->plugin_basename);

            if (is_wp_error($result)) {
                return array(
                    'success' => false,
                    'message' => 'WordPress-Update fehlgeschlagen: ' . $result->get_error_message(),
                    'output' => ''
                );
            }

            if (false === $result) {
                $errors = method_exists($skin, 'get_errors') ? $skin->get_errors() : null;
                $error_message = ($errors && $errors->has_errors())
                    ? implode(' | ', $errors->get_error_messages())
                    : 'Unbekannter Fehler beim WordPress-Upgrade.';

                return array(
                    'success' => false,
                    'message' => 'WordPress-Update fehlgeschlagen: ' . $error_message,
                    'output' => ''
                );
            }

            // Cache leeren und Migrationen ausführen
            delete_transient('dienstplan_update_info');
            $this->run_migrations();
            $this->restore_activation_after_update();

            return array(
                'success' => true,
                'message' => 'Update erfolgreich über den WordPress-Upgrader durchgeführt.',
                'output' => ''
            );
        }

        // Git-basiertes Update (nur für Entwicklungsumgebungen)
        $current_dir = getcwd();
        chdir($this->plugin_dir);

        try {
            // Sichere lokale Änderungen
            exec('"' . $this->git_cmd . '" stash 2>&1', $output, $return_var);

            // Entferne generierte Release-ZIPs, damit sie den Pull nicht blockieren
            $release_dir = $this->plugin_dir . 'release';
            if (is_dir($release_dir)) {
                foreach (glob($release_dir . DIRECTORY_SEPARATOR . '*.zip') as $zip_file) {
                    @unlink($zip_file);
                }
            }

            // Pull Updates (--ff-only vermeidet Merge-Nachfragen und unerwartete Merges)
            $pull_cmd = '"' . $this->git_cmd . '" pull --ff-only origin "' . $this->safe_git_branch . '" 2>&1';
            exec($pull_cmd, $pull_output, $return_var);
            
            if ($return_var !== 0) {
                chdir($current_dir);
                return array(
                    'success' => false,
                    'message' => 'Git pull fehlgeschlagen: ' . implode("\n", $pull_output)
                );
            }

            chdir($current_dir);

            // Lösche Update-Cache
            delete_transient('dienstplan_update_info');

            // Führe ggf. Datenbank-Migrationen durch
            $this->run_migrations();

            return array(
                'success' => true,
                'message' => 'Update erfolgreich durchgeführt',
                'output' => implode("\n", $pull_output)
            );

        } catch (Exception $e) {
            chdir($current_dir);
            return array(
                'success' => false,
                'message' => 'Fehler: ' . $e->getMessage()
            );
        }
    }

    /**
     * Führt Datenbank-Migrationen aus
     */
    private function run_migrations() {
        require_once $this->plugin_dir . 'includes/class-database.php';
        require_once $this->plugin_dir . 'includes/class-dienstplan-notifications.php';

        $database = new Dienstplan_Database(DIENSTPLAN_DB_PREFIX);
        $database->install();
        $database->run_versioned_migrations($this->current_version);

        $notifications = new Dienstplan_Notifications(DIENSTPLAN_DB_PREFIX);
        $notifications->install();

        update_option('dienstplan_db_version', $this->current_version);
        update_option('dienstplan_version', $this->current_version);
    }

    /**
     * Nach Update-Hook
     */
    public function after_update($upgrader_object, $options) {
        if ($options['action'] === 'update' && $options['type'] === 'plugin') {
            foreach ($options['plugins'] as $plugin) {
                if ($plugin === $this->plugin_basename) {
                    // Lösche Update-Cache
                    delete_transient('dienstplan_update_info');
                    
                    // Führe Migrationen aus
                    $this->run_migrations();

                    // Falls WordPress das Plugin beim Update deaktiviert hat,
                    // den vorherigen Aktivstatus wiederherstellen.
                    $this->restore_activation_after_update();
                }
            }
        }
    }

    /**
     * Aktiviert automatische Updates wenn in Einstellungen aktiviert
     */
    public function enable_auto_update($update, $item) {
        // Prüfe ob es sich um unser Plugin handelt
        if (isset($item->slug) && $item->slug === $this->plugin_slug) {
            // Hole Einstellung
            $auto_update_enabled = get_option('dienstplan_auto_update_enabled', 0);
            return (bool) $auto_update_enabled;
        }
        
        return $update;
    }

    /**
     * Manuelle Update-Prüfung
     */
    public function check_update_manually() {
        // Lösche Transient-Cache UND In-Memory-Cache des Objekts,
        // damit auch dann frisch geprüft wird, wenn der WP-Filter
        // check_for_updates() bereits früher im selben Request aufgerufen wurde.
        delete_transient('dienstplan_update_info');
        $this->update_info = null;

        // Hole neue Update-Informationen
        $update_info = $this->get_update_info();
        
        if (!$update_info) {
            return array(
                'has_update' => false,
                'message' => 'Keine Updates verfügbar oder Git nicht konfiguriert'
            );
        }

        $has_update = version_compare($this->current_version, $update_info['version'], '<');

        return array(
            'has_update' => $has_update,
            'current_version' => $this->current_version,
            'new_version' => $update_info['version'],
            'message' => $has_update 
                ? 'Update verfügbar: Version ' . $update_info['version']
                : 'Plugin ist auf dem neuesten Stand'
        );
    }

    /**
     * Holt Git-Status
     */
    public function get_git_status() {
        $status = array(
            'available' => $this->git_available,
            'mode' => $this->git_available ? 'Git (Entwicklung)' : 'GitHub API (Produktion)'
        );

        if (!$this->git_available) {
            $status['message'] = 'Git ist nicht verfügbar. Updates werden über GitHub API bezogen.';
            return $status;
        }

        $current_dir = getcwd();
        chdir($this->plugin_dir);

        try {
            // Hole aktuellen Branch
            exec('"' . $this->git_cmd . '" rev-parse --abbrev-ref HEAD 2>&1', $branch_output, $return_var);
            $current_branch = $return_var === 0 ? trim($branch_output[0]) : 'unknown';

            // Hole letzten Commit
            exec('"' . $this->git_cmd . '" log -1 --pretty=format:"%h - %s (%cr)" 2>&1', $commit_output, $return_var);
            $last_commit = $return_var === 0 ? trim($commit_output[0]) : 'unknown';

            // Hole Remote-URL
            exec('"' . $this->git_cmd . '" config --get remote.origin.url 2>&1', $remote_output, $return_var);
            $remote_url = $return_var === 0 ? trim($remote_output[0]) : 'not configured';

            // Prüfe auf uncommitted changes
            exec('"' . $this->git_cmd . '" status --porcelain 2>&1', $status_output, $return_var);
            $has_changes = !empty($status_output);

            chdir($current_dir);

            $status['current_branch'] = $current_branch;
            $status['last_commit'] = $last_commit;
            $status['remote_url'] = $remote_url;
            $status['has_uncommitted_changes'] = $has_changes;
            $status['configured_branch'] = $this->git_branch;
            $status['configured_repo'] = $this->git_repo_url;

            return $status;

        } catch (Exception $e) {
            chdir($current_dir);
            return array(
                'available' => false,
                'mode' => 'Fehler',
                'message' => 'Fehler: ' . $e->getMessage()
            );
        }
    }

    /**
     * Bereinigt Git-Refs für Shell-Kommandos.
     * Erlaubt nur übliche Zeichen für Branch-/Tag-Namen.
     *
     * @param string $ref
     * @return string
     */
    private function sanitize_git_ref($ref) {
        $ref = (string) $ref;
        if ($ref === '') {
            return 'main';
        }

        $sanitized = preg_replace('/[^A-Za-z0-9._\/-]/', '', $ref);
        if ($sanitized === '') {
            return 'main';
        }

        return $sanitized;
    }
}

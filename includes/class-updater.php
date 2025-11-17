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

        // Finde Git-Executable und prüfe Verfügbarkeit
        $this->find_git_executable();
        $this->git_available = $this->is_git_available();

        // WordPress Hooks für Update-System
        add_filter('site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        add_action('upgrader_process_complete', array($this, 'after_update'), 10, 2);
        
        // Automatische Updates aktivieren wenn gewünscht
        add_filter('auto_update_plugin', array($this, 'enable_auto_update'), 10, 2);
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

        if (is_wp_error($response)) {
            error_log('DP Updater: GitHub API Fehler: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $release_data = json_decode($body, true);

        if (!$release_data || !isset($release_data['tag_name'])) {
            error_log('DP Updater: Ungültige GitHub API Antwort');
            return false;
        }

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

        return array(
            'version' => $version,
            'url' => isset($release_data['html_url']) ? $release_data['html_url'] : $this->git_repo_url,
            'download_url' => $download_url,
            'tested' => '6.4',
            'requires_php' => '7.4',
            'changelog' => isset($release_data['body']) ? $release_data['body'] : ''
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
            exec('"' . $this->git_cmd . '" fetch origin ' . escapeshellarg($this->git_branch) . ' 2>&1', $output, $return_var);
            
            if ($return_var !== 0) {
                error_log('DP Updater: Git fetch fehlgeschlagen');
                chdir($current_dir);
                return false;
            }

            // Hole Version aus remote Plugin-Datei
            $remote_file_cmd = '"' . $this->git_cmd . '" show origin/' . escapeshellarg($this->git_branch) . ':dienstplan-verwaltung.php';
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
     * Plugin-Informationen für Update-Details
     */
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
        // Auf Produktionsservern ohne Git: WordPress Update-Mechanismus nutzen
        if (!$this->git_available) {
            return array(
                'success' => false, 
                'message' => 'Bitte nutzen Sie die WordPress Plugin-Verwaltung für Updates. Gehen Sie zu: Plugins → Installierte Plugins → Dienstplan Verwaltung → "Jetzt aktualisieren"'
            );
        }

        // Git-basiertes Update (nur für Entwicklungsumgebungen)
        $current_dir = getcwd();
        chdir($this->plugin_dir);

        try {
            // Sichere lokale Änderungen
            exec('"' . $this->git_cmd . '" stash 2>&1', $output, $return_var);
            
            // Pull Updates
            $pull_cmd = '"' . $this->git_cmd . '" pull origin ' . escapeshellarg($this->git_branch) . ' 2>&1';
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
        // Prüfe ob Migrationen notwendig sind
        $current_db_version = get_option('dienstplan_db_version', '0.0.0');
        
        if (version_compare($current_db_version, $this->current_version, '<')) {
            // Führe Aktivator aus (erstellt/aktualisiert Tabellen)
            require_once $this->plugin_dir . 'includes/class-activator.php';
            Dienstplan_Activator::activate();
            
            // Aktualisiere DB-Version
            update_option('dienstplan_db_version', $this->current_version);
        }
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
        // Lösche Cache
        delete_transient('dienstplan_update_info');
        
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
}

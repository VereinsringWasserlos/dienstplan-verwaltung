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
     * Initialisiert den Updater
     */
    public function __construct() {
        $this->plugin_slug = 'dienstplan-verwaltung';
        $this->plugin_basename = 'dienstplan-verwaltung/dienstplan-verwaltung.php';
        $this->plugin_dir = DIENSTPLAN_PLUGIN_PATH;
        $this->current_version = DIENSTPLAN_VERSION;
        
        // Git-Repository Konfiguration (später in Einstellungen verschieben)
        $this->git_repo_url = get_option('dienstplan_git_repo_url', '');
        $this->git_branch = get_option('dienstplan_git_branch', 'main');

        // WordPress Hooks für Update-System
        add_filter('site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        add_action('upgrader_process_complete', array($this, 'after_update'), 10, 2);
        
        // Admin-Hooks
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Registriert Einstellungen
     */
    public function register_settings() {
        register_setting('dienstplan_settings', 'dienstplan_git_repo_url');
        register_setting('dienstplan_settings', 'dienstplan_git_branch');
        register_setting('dienstplan_settings', 'dienstplan_auto_update');
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
        }

        return $transient;
    }

    /**
     * Holt Update-Informationen aus Git
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

        try {
            // Prüfe ob Git verfügbar ist
            if (!$this->is_git_available()) {
                error_log('DP Updater: Git ist nicht verfügbar');
                return false;
            }

            // Hole Remote-Version aus Git
            $remote_version = $this->get_remote_version();
            
            if (!$remote_version) {
                return false;
            }

            $update_info = array(
                'version' => $remote_version,
                'url' => $this->git_repo_url,
                'download_url' => $this->get_download_url(),
                'tested' => '6.4',
                'requires_php' => '7.4',
                'changelog' => $this->get_changelog(),
            );

            // Cache für 12 Stunden
            set_transient($cache_key, $update_info, 43200); // 12 * 3600
            
            $this->update_info = $update_info;
            return $update_info;

        } catch (Exception $e) {
            error_log('DP Updater Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Prüft ob Git verfügbar ist
     */
    private function is_git_available() {
        // Prüfe ob .git Verzeichnis existiert
        if (!is_dir($this->plugin_dir . '.git')) {
            return false;
        }

        // Prüfe ob git Befehl verfügbar ist
        $output = array();
        $return_var = 0;
        exec('git --version 2>&1', $output, $return_var);
        
        return $return_var === 0;
    }

    /**
     * Holt Remote-Version aus Git
     */
    private function get_remote_version() {
        $current_dir = getcwd();
        chdir($this->plugin_dir);

        try {
            // Fetch Remote-Changes
            exec('git fetch origin ' . escapeshellarg($this->git_branch) . ' 2>&1', $output, $return_var);
            
            if ($return_var !== 0) {
                error_log('DP Updater: Git fetch fehlgeschlagen');
                chdir($current_dir);
                return false;
            }

            // Hole Version aus remote Plugin-Datei
            $remote_file_cmd = 'git show origin/' . escapeshellarg($this->git_branch) . ':dienstplan-verwaltung.php';
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
     * Führt Update über Git durch
     */
    public function perform_update() {
        if (!$this->is_git_available()) {
            return array('success' => false, 'message' => 'Git ist nicht verfügbar');
        }

        $current_dir = getcwd();
        chdir($this->plugin_dir);

        try {
            // Sichere lokale Änderungen
            exec('git stash 2>&1', $output, $return_var);
            
            // Pull Updates
            $pull_cmd = 'git pull origin ' . escapeshellarg($this->git_branch) . ' 2>&1';
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
        if (!$this->is_git_available()) {
            return array(
                'available' => false,
                'message' => 'Git ist nicht verfügbar'
            );
        }

        $current_dir = getcwd();
        chdir($this->plugin_dir);

        try {
            // Hole aktuellen Branch
            exec('git rev-parse --abbrev-ref HEAD 2>&1', $branch_output, $return_var);
            $current_branch = $return_var === 0 ? trim($branch_output[0]) : 'unknown';

            // Hole letzten Commit
            exec('git log -1 --pretty=format:"%h - %s (%cr)" 2>&1', $commit_output, $return_var);
            $last_commit = $return_var === 0 ? trim($commit_output[0]) : 'unknown';

            // Hole Remote-URL
            exec('git config --get remote.origin.url 2>&1', $remote_output, $return_var);
            $remote_url = $return_var === 0 ? trim($remote_output[0]) : 'not configured';

            // Prüfe auf uncommitted changes
            exec('git status --porcelain 2>&1', $status_output, $return_var);
            $has_changes = !empty($status_output);

            chdir($current_dir);

            return array(
                'available' => true,
                'current_branch' => $current_branch,
                'last_commit' => $last_commit,
                'remote_url' => $remote_url,
                'has_uncommitted_changes' => $has_changes,
                'configured_branch' => $this->git_branch,
                'configured_repo' => $this->git_repo_url
            );

        } catch (Exception $e) {
            chdir($current_dir);
            return array(
                'available' => false,
                'message' => 'Fehler: ' . $e->getMessage()
            );
        }
    }
}

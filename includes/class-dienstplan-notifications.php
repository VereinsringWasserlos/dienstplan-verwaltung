<?php
/**
 * E-Mail-Benachrichtigungssystem
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/includes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dienstplan_Notifications {
    
    private $wpdb;
    private $prefix;
    
    public function __construct($db_prefix = 'dp_') {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix . $db_prefix;
    }
    
    /**
     * Benachrichtigungstabelle erstellen
     */
    public function install() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset = $this->wpdb->get_charset_collate();
        
        // Benachrichtigungs-Einstellungen pro Benutzer
        $sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}notification_settings (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            notify_on_event_create tinyint(1) DEFAULT 1,
            notify_on_event_update tinyint(1) DEFAULT 1,
            notify_on_event_delete tinyint(1) DEFAULT 1,
            notify_on_club_create tinyint(1) DEFAULT 1,
            notify_on_club_update tinyint(1) DEFAULT 1,
            notify_on_club_delete tinyint(1) DEFAULT 1,
            email_override varchar(255),
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset;";
        
        dbDelta($sql);
        
        // Benachrichtigungs-Log
        $sql = "CREATE TABLE IF NOT EXISTS {$this->prefix}notification_log (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            notification_type varchar(50) NOT NULL,
            subject varchar(255) NOT NULL,
            message text,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'sent',
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY sent_at (sent_at)
        ) $charset;";
        
        dbDelta($sql);
    }
    
    /**
     * Hole Benachrichtigungs-Einstellungen für Benutzer
     */
    public function get_user_settings($user_id) {
        $settings = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->prefix}notification_settings WHERE user_id = %d",
            $user_id
        ));
        
        // Standard-Einstellungen wenn noch nicht vorhanden
        if (!$settings) {
            $settings = (object) array(
                'user_id' => $user_id,
                'notify_on_event_create' => 1,
                'notify_on_event_update' => 1,
                'notify_on_event_delete' => 1,
                'notify_on_club_create' => 1,
                'notify_on_club_update' => 1,
                'notify_on_club_delete' => 1,
                'email_override' => ''
            );
        }
        
        return $settings;
    }
    
    /**
     * Speichere Benachrichtigungs-Einstellungen
     */
    public function save_user_settings($user_id, $settings) {
        $exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->prefix}notification_settings WHERE user_id = %d",
            $user_id
        ));
        
        if ($exists) {
            return $this->wpdb->update(
                $this->prefix . 'notification_settings',
                $settings,
                array('user_id' => $user_id)
            );
        } else {
            $settings['user_id'] = $user_id;
            return $this->wpdb->insert($this->prefix . 'notification_settings', $settings);
        }
    }
    
    /**
     * Sende Benachrichtigung bei Veranstaltungs-Erstellung
     */
    public function notify_event_created($veranstaltung_id, $veranstaltung_data) {
        $users = Dienstplan_Roles::get_event_admins();
        $current_user = wp_get_current_user();
        
        foreach ($users as $user) {
            // Nicht an sich selbst senden
            if ($user->ID == $current_user->ID) continue;
            
            $settings = $this->get_user_settings($user->ID);
            if (!$settings->notify_on_event_create) continue;
            
            $email = $settings->email_override ?: $user->user_email;
            $subject = sprintf(
                __('[Dienstplan] Neue Veranstaltung: %s', 'dienstplan-verwaltung'),
                $veranstaltung_data['name']
            );
            
            $message = sprintf(
                __("Hallo %s,\n\neine neue Veranstaltung wurde erstellt:\n\nName: %s\nStatus: %s\nErstellt von: %s\n\nÖffne das Dienstplan-System um Details zu sehen:\n%s\n\n---\nDu erhältst diese E-Mail, weil du als Veranstaltungs-Admin registriert bist.\nÄndere deine Benachrichtigungseinstellungen: %s", 'dienstplan-verwaltung'),
                $user->display_name,
                $veranstaltung_data['name'],
                $veranstaltung_data['status'] ?? 'geplant',
                $current_user->display_name,
                admin_url('admin.php?page=dienstplan-veranstaltungen'),
                admin_url('admin.php?page=dienstplan-einstellungen&tab=notifications')
            );
            
            $this->send_email($user->ID, $email, $subject, $message, 'event_created');
        }
    }
    
    /**
     * Sende Benachrichtigung bei Veranstaltungs-Aktualisierung
     */
    public function notify_event_updated($veranstaltung_id, $veranstaltung_data) {
        $users = Dienstplan_Roles::get_event_admins();
        $current_user = wp_get_current_user();
        
        foreach ($users as $user) {
            if ($user->ID == $current_user->ID) continue;
            
            $settings = $this->get_user_settings($user->ID);
            if (!$settings->notify_on_event_update) continue;
            
            $email = $settings->email_override ?: $user->user_email;
            $subject = sprintf(
                __('[Dienstplan] Veranstaltung aktualisiert: %s', 'dienstplan-verwaltung'),
                $veranstaltung_data['name']
            );
            
            $message = sprintf(
                __("Hallo %s,\n\neine Veranstaltung wurde aktualisiert:\n\nName: %s\nStatus: %s\nAktualisiert von: %s\n\nÖffne das Dienstplan-System um Details zu sehen:\n%s", 'dienstplan-verwaltung'),
                $user->display_name,
                $veranstaltung_data['name'],
                $veranstaltung_data['status'] ?? 'geplant',
                $current_user->display_name,
                admin_url('admin.php?page=dienstplan-veranstaltungen')
            );
            
            $this->send_email($user->ID, $email, $subject, $message, 'event_updated');
        }
    }
    
    /**
     * Sende Benachrichtigung bei Veranstaltungs-Löschung
     */
    public function notify_event_deleted($veranstaltung_name) {
        $users = Dienstplan_Roles::get_event_admins();
        $current_user = wp_get_current_user();
        
        foreach ($users as $user) {
            if ($user->ID == $current_user->ID) continue;
            
            $settings = $this->get_user_settings($user->ID);
            if (!$settings->notify_on_event_delete) continue;
            
            $email = $settings->email_override ?: $user->user_email;
            $subject = sprintf(
                __('[Dienstplan] Veranstaltung gelöscht: %s', 'dienstplan-verwaltung'),
                $veranstaltung_name
            );
            
            $message = sprintf(
                __("Hallo %s,\n\neine Veranstaltung wurde gelöscht:\n\nName: %s\nGelöscht von: %s", 'dienstplan-verwaltung'),
                $user->display_name,
                $veranstaltung_name,
                $current_user->display_name
            );
            
            $this->send_email($user->ID, $email, $subject, $message, 'event_deleted');
        }
    }
    
    /**
     * Sende Benachrichtigung bei Vereins-Erstellung
     */
    public function notify_club_created($verein_id, $verein_data) {
        $users = Dienstplan_Roles::get_club_admins();
        $current_user = wp_get_current_user();
        
        foreach ($users as $user) {
            if ($user->ID == $current_user->ID) continue;
            
            $settings = $this->get_user_settings($user->ID);
            if (!$settings->notify_on_club_create) continue;
            
            $email = $settings->email_override ?: $user->user_email;
            $subject = sprintf(
                __('[Dienstplan] Neuer Verein: %s', 'dienstplan-verwaltung'),
                $verein_data['name']
            );
            
            $message = sprintf(
                __("Hallo %s,\n\nein neuer Verein wurde erstellt:\n\nName: %s\nKürzel: %s\nErstellt von: %s\n\nÖffne das Dienstplan-System:\n%s", 'dienstplan-verwaltung'),
                $user->display_name,
                $verein_data['name'],
                $verein_data['kuerzel'],
                $current_user->display_name,
                admin_url('admin.php?page=dienstplan-vereine')
            );
            
            $this->send_email($user->ID, $email, $subject, $message, 'club_created');
        }
    }
    
    /**
     * Sende Benachrichtigung bei Vereins-Aktualisierung
     */
    public function notify_club_updated($verein_id, $verein_data) {
        $users = Dienstplan_Roles::get_club_admins();
        $current_user = wp_get_current_user();
        
        foreach ($users as $user) {
            if ($user->ID == $current_user->ID) continue;
            
            $settings = $this->get_user_settings($user->ID);
            if (!$settings->notify_on_club_update) continue;
            
            $email = $settings->email_override ?: $user->user_email;
            $subject = sprintf(
                __('[Dienstplan] Verein aktualisiert: %s', 'dienstplan-verwaltung'),
                $verein_data['name']
            );
            
            $message = sprintf(
                __("Hallo %s,\n\nein Verein wurde aktualisiert:\n\nName: %s\nKürzel: %s\nAktualisiert von: %s", 'dienstplan-verwaltung'),
                $user->display_name,
                $verein_data['name'],
                $verein_data['kuerzel'],
                $current_user->display_name
            );
            
            $this->send_email($user->ID, $email, $subject, $message, 'club_updated');
        }
    }
    
    /**
     * Sende Benachrichtigung bei Vereins-Löschung
     */
    public function notify_club_deleted($verein_name) {
        $users = Dienstplan_Roles::get_club_admins();
        $current_user = wp_get_current_user();
        
        foreach ($users as $user) {
            if ($user->ID == $current_user->ID) continue;
            
            $settings = $this->get_user_settings($user->ID);
            if (!$settings->notify_on_club_delete) continue;
            
            $email = $settings->email_override ?: $user->user_email;
            $subject = sprintf(
                __('[Dienstplan] Verein gelöscht: %s', 'dienstplan-verwaltung'),
                $verein_name
            );
            
            $message = sprintf(
                __("Hallo %s,\n\nein Verein wurde gelöscht:\n\nName: %s\nGelöscht von: %s", 'dienstplan-verwaltung'),
                $user->display_name,
                $verein_name,
                $current_user->display_name
            );
            
            $this->send_email($user->ID, $email, $subject, $message, 'club_deleted');
        }
    }
    
    /**
     * Sende E-Mail und logge
     */
    private function send_email($user_id, $email, $subject, $message, $notification_type) {
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        $sent = wp_mail($email, $subject, $message, $headers);
        
        // Log speichern
        $this->wpdb->insert(
            $this->prefix . 'notification_log',
            array(
                'user_id' => $user_id,
                'notification_type' => $notification_type,
                'subject' => $subject,
                'message' => $message,
                'status' => $sent ? 'sent' : 'failed'
            )
        );
        
        return $sent;
    }
    
    /**
     * Hole Benachrichtigungs-Log
     */
    public function get_notification_log($limit = 100, $user_id = null) {
        $where = $user_id ? $this->wpdb->prepare("WHERE user_id = %d", $user_id) : "";
        
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->prefix}notification_log 
            {$where}
            ORDER BY sent_at DESC 
            LIMIT {$limit}"
        );
    }
}

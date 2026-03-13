<?php
/**
 * Benutzerverwaltung und Rollen
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/includes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dienstplan_Roles {
    
    /**
     * Rollen-Definitionen
     */
    const ROLE_GENERAL_ADMIN = 'dpv2_general_admin';
    const ROLE_EVENT_ADMIN = 'dpv2_event_admin';
    const ROLE_CLUB_ADMIN = 'dpv2_club_admin';
    const ROLE_CREW = 'dpv2_crew';

    const LEGACY_ROLE_GENERAL_ADMIN = 'dp_general_admin';
    const LEGACY_ROLE_EVENT_ADMIN = 'dp_event_admin';
    const LEGACY_ROLE_CLUB_ADMIN = 'dp_club_admin';
    const LEGACY_ROLE_CREW = 'dienstplan_crew';
    
    /**
     * Capabilities
     */
    const CAP_MANAGE_SETTINGS = 'dpv2_manage_settings';
    const CAP_MANAGE_USERS = 'dpv2_manage_users';
    const CAP_MANAGE_EVENTS = 'dpv2_manage_events';
    const CAP_MANAGE_CLUBS = 'dpv2_manage_clubs';
    const CAP_VIEW_REPORTS = 'dpv2_view_reports';
    const CAP_SEND_NOTIFICATIONS = 'dpv2_send_notifications';

    const LEGACY_CAP_MANAGE_SETTINGS = 'dp_manage_settings';
    const LEGACY_CAP_MANAGE_USERS = 'dp_manage_users';
    const LEGACY_CAP_MANAGE_EVENTS = 'dp_manage_events';
    const LEGACY_CAP_MANAGE_CLUBS = 'dp_manage_clubs';
    const LEGACY_CAP_VIEW_REPORTS = 'dp_view_reports';
    const LEGACY_CAP_SEND_NOTIFICATIONS = 'dp_send_notifications';
    
    /**
     * Rollen installieren
     */
    public static function install_roles() {
        self::cleanup_legacy_roles_and_caps();
        self::migrate_legacy_user_roles();

        // Allgemeiner Admin - Vollzugriff auf alles
        add_role(
            self::ROLE_GENERAL_ADMIN,
            __('Dienstplan - Allgemeiner Admin', 'dienstplan-verwaltung'),
            array(
                'read' => true,
                self::CAP_MANAGE_SETTINGS => true,
                self::CAP_MANAGE_USERS => true,
                self::CAP_MANAGE_EVENTS => true,
                self::CAP_MANAGE_CLUBS => true,
                self::CAP_VIEW_REPORTS => true,
                self::CAP_SEND_NOTIFICATIONS => true,
            )
        );
        
        // Veranstaltungs-Admin - Nur Veranstaltungen
        add_role(
            self::ROLE_EVENT_ADMIN,
            __('Dienstplan - Veranstaltungs-Admin', 'dienstplan-verwaltung'),
            array(
                'read' => true,
                self::CAP_MANAGE_EVENTS => true,
                self::CAP_VIEW_REPORTS => true,
            )
        );
        
        // Vereins-Admin - Nur Vereine
        add_role(
            self::ROLE_CLUB_ADMIN,
            __('Dienstplan - Vereins-Admin', 'dienstplan-verwaltung'),
            array(
                'read' => true,
                self::CAP_MANAGE_CLUBS => true,
                self::CAP_MANAGE_EVENTS => true,
                self::CAP_VIEW_REPORTS => true,
            )
        );
        
        // Crew-Mitglied - Nur Frontend-Portal-Zugriff (kein Backend)
        add_role(
            self::ROLE_CREW,
            __('Dienstplan - Crew-Mitglied', 'dienstplan-verwaltung'),
            array(
                'read' => true, // Nur lesender Zugriff auf Frontend
            )
        );
        
        // WordPress-Admin erhält alle Capabilities
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap(self::CAP_MANAGE_SETTINGS);
            $admin_role->add_cap(self::CAP_MANAGE_USERS);
            $admin_role->add_cap(self::CAP_MANAGE_EVENTS);
            $admin_role->add_cap(self::CAP_MANAGE_CLUBS);
            $admin_role->add_cap(self::CAP_VIEW_REPORTS);
            $admin_role->add_cap(self::CAP_SEND_NOTIFICATIONS);

            // Legacy-Caps entfernen, damit nur noch die v2-Varianten existieren.
            $admin_role->remove_cap(self::LEGACY_CAP_MANAGE_SETTINGS);
            $admin_role->remove_cap(self::LEGACY_CAP_MANAGE_USERS);
            $admin_role->remove_cap(self::LEGACY_CAP_MANAGE_EVENTS);
            $admin_role->remove_cap(self::LEGACY_CAP_MANAGE_CLUBS);
            $admin_role->remove_cap(self::LEGACY_CAP_VIEW_REPORTS);
            $admin_role->remove_cap(self::LEGACY_CAP_SEND_NOTIFICATIONS);
        }

        // Bestehende Rollen aktiv aktualisieren (add_role() aktualisiert vorhandene Rollen nicht)
        $general_admin_role = get_role(self::ROLE_GENERAL_ADMIN);
        if ($general_admin_role) {
            $general_admin_role->add_cap('read');
            $general_admin_role->add_cap(self::CAP_MANAGE_SETTINGS);
            $general_admin_role->add_cap(self::CAP_MANAGE_USERS);
            $general_admin_role->add_cap(self::CAP_MANAGE_EVENTS);
            $general_admin_role->add_cap(self::CAP_MANAGE_CLUBS);
            $general_admin_role->add_cap(self::CAP_VIEW_REPORTS);
            $general_admin_role->add_cap(self::CAP_SEND_NOTIFICATIONS);
        }

        $event_admin_role = get_role(self::ROLE_EVENT_ADMIN);
        if ($event_admin_role) {
            $event_admin_role->add_cap('read');
            $event_admin_role->add_cap(self::CAP_MANAGE_EVENTS);
            $event_admin_role->add_cap(self::CAP_VIEW_REPORTS);
        }

        $club_admin_role = get_role(self::ROLE_CLUB_ADMIN);
        if ($club_admin_role) {
            $club_admin_role->add_cap('read');
            $club_admin_role->add_cap(self::CAP_MANAGE_CLUBS);
            $club_admin_role->add_cap(self::CAP_MANAGE_EVENTS);
            $club_admin_role->add_cap(self::CAP_VIEW_REPORTS);
        }

        $crew_role = get_role(self::ROLE_CREW);
        if ($crew_role) {
            $crew_role->add_cap('read');
        }
    }
    
    /**
     * Rollen deinstallieren
     */
    public static function uninstall_roles() {
        remove_role(self::ROLE_GENERAL_ADMIN);
        remove_role(self::ROLE_EVENT_ADMIN);
        remove_role(self::ROLE_CLUB_ADMIN);
        remove_role(self::ROLE_CREW);

        // Legacy-Rollen ebenfalls entfernen.
        remove_role(self::LEGACY_ROLE_GENERAL_ADMIN);
        remove_role(self::LEGACY_ROLE_EVENT_ADMIN);
        remove_role(self::LEGACY_ROLE_CLUB_ADMIN);
        remove_role(self::LEGACY_ROLE_CREW);
        
        // Capabilities vom Administrator entfernen
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->remove_cap(self::CAP_MANAGE_SETTINGS);
            $admin_role->remove_cap(self::CAP_MANAGE_USERS);
            $admin_role->remove_cap(self::CAP_MANAGE_EVENTS);
            $admin_role->remove_cap(self::CAP_MANAGE_CLUBS);
            $admin_role->remove_cap(self::CAP_VIEW_REPORTS);
            $admin_role->remove_cap(self::CAP_SEND_NOTIFICATIONS);
            $admin_role->remove_cap(self::LEGACY_CAP_MANAGE_SETTINGS);
            $admin_role->remove_cap(self::LEGACY_CAP_MANAGE_USERS);
            $admin_role->remove_cap(self::LEGACY_CAP_MANAGE_EVENTS);
            $admin_role->remove_cap(self::LEGACY_CAP_MANAGE_CLUBS);
            $admin_role->remove_cap(self::LEGACY_CAP_VIEW_REPORTS);
            $admin_role->remove_cap(self::LEGACY_CAP_SEND_NOTIFICATIONS);
        }
    }

    /**
     * Entfernt alte Rollen und alte Caps, falls diese noch vorhanden sind.
     */
    private static function cleanup_legacy_roles_and_caps() {
        remove_role(self::LEGACY_ROLE_GENERAL_ADMIN);
        remove_role(self::LEGACY_ROLE_EVENT_ADMIN);
        remove_role(self::LEGACY_ROLE_CLUB_ADMIN);
        remove_role(self::LEGACY_ROLE_CREW);

        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->remove_cap(self::LEGACY_CAP_MANAGE_SETTINGS);
            $admin_role->remove_cap(self::LEGACY_CAP_MANAGE_USERS);
            $admin_role->remove_cap(self::LEGACY_CAP_MANAGE_EVENTS);
            $admin_role->remove_cap(self::LEGACY_CAP_MANAGE_CLUBS);
            $admin_role->remove_cap(self::LEGACY_CAP_VIEW_REPORTS);
            $admin_role->remove_cap(self::LEGACY_CAP_SEND_NOTIFICATIONS);
        }
    }

    /**
     * Migriert Benutzer mit Legacy-Rollen auf die neuen v2-Rollen.
     */
    private static function migrate_legacy_user_roles() {
        $mapping = array(
            self::LEGACY_ROLE_GENERAL_ADMIN => self::ROLE_GENERAL_ADMIN,
            self::LEGACY_ROLE_EVENT_ADMIN => self::ROLE_EVENT_ADMIN,
            self::LEGACY_ROLE_CLUB_ADMIN => self::ROLE_CLUB_ADMIN,
            self::LEGACY_ROLE_CREW => self::ROLE_CREW,
        );

        foreach ($mapping as $legacy_role => $new_role) {
            $users = get_users(array('role' => $legacy_role));
            if (empty($users)) {
                continue;
            }

            foreach ($users as $user) {
                $user->add_role($new_role);
                $user->remove_role($legacy_role);
            }
        }
    }
    
    /**
     * Prüfe ob Benutzer Berechtigung hat
     */
    public static function user_can($capability) {
        // Hard-Reset Baseline: Berechtigungen vorerst nur über WP-Admin.
        return current_user_can('manage_options');
    }
    
    /**
     * Prüfe ob Benutzer Veranstaltungen verwalten darf
     */
    public static function can_manage_events() {
        return self::user_can(self::CAP_MANAGE_EVENTS);
    }
    
    /**
     * Prüfe ob Benutzer Vereine verwalten darf
     */
    public static function can_manage_clubs() {
        return self::user_can(self::CAP_MANAGE_CLUBS);
    }
    
    /**
     * Prüfe ob Benutzer Einstellungen verwalten darf
     */
    public static function can_manage_settings() {
        return self::user_can(self::CAP_MANAGE_SETTINGS);
    }
    
    /**
     * Prüfe ob Benutzer Benutzer verwalten darf
     */
    public static function can_manage_users() {
        return self::user_can(self::CAP_MANAGE_USERS);
    }
    
    /**
     * Prüfe ob Benutzer Benachrichtigungen senden darf
     */
    public static function can_send_notifications() {
        return self::user_can(self::CAP_SEND_NOTIFICATIONS);
    }
    
    /**
     * Hole alle Benutzer mit Dienstplan-Rollen
     */
    public static function get_all_dp_users() {
        $users = get_users(array(
            'role__in' => array(
                self::ROLE_GENERAL_ADMIN,
                self::ROLE_EVENT_ADMIN,
                self::ROLE_CLUB_ADMIN,
            )
        ));
        
        // WordPress-Admins hinzufügen
        $admins = get_users(array('role' => 'administrator'));
        $users = array_merge($users, $admins);
        
        // Duplikate entfernen (falls ein Benutzer mehrere Rollen hat)
        $unique_users = array();
        $user_ids = array();
        
        foreach ($users as $user) {
            if (!in_array($user->ID, $user_ids)) {
                $unique_users[] = $user;
                $user_ids[] = $user->ID;
            }
        }
        
        return $unique_users;
    }
    
    /**
     * Hole alle Benutzer die für Veranstaltungen zuständig sind
     */
    public static function get_event_admins() {
        $all_users = array();
        $user_ids = array();
        
        // Allgemeine Admins
        $general = get_users(array('role' => self::ROLE_GENERAL_ADMIN));
        foreach ($general as $user) {
            if (!in_array($user->ID, $user_ids)) {
                $all_users[] = $user;
                $user_ids[] = $user->ID;
            }
        }
        
        // Veranstaltungs-Admins
        $event = get_users(array('role' => self::ROLE_EVENT_ADMIN));
        foreach ($event as $user) {
            if (!in_array($user->ID, $user_ids)) {
                $all_users[] = $user;
                $user_ids[] = $user->ID;
            }
        }
        
        // WordPress-Admins
        $admins = get_users(array('role' => 'administrator'));
        foreach ($admins as $user) {
            if (!in_array($user->ID, $user_ids)) {
                $all_users[] = $user;
                $user_ids[] = $user->ID;
            }
        }
        
        return $all_users;
    }
    
    /**
     * Hole alle Benutzer die für Vereine zuständig sind
     */
    public static function get_club_admins() {
        $all_users = array();
        $user_ids = array();
        
        // Allgemeine Admins
        $general = get_users(array('role' => self::ROLE_GENERAL_ADMIN));
        foreach ($general as $user) {
            if (!in_array($user->ID, $user_ids)) {
                $all_users[] = $user;
                $user_ids[] = $user->ID;
            }
        }
        
        // Vereins-Admins
        $club = get_users(array('role' => self::ROLE_CLUB_ADMIN));
        foreach ($club as $user) {
            if (!in_array($user->ID, $user_ids)) {
                $all_users[] = $user;
                $user_ids[] = $user->ID;
            }
        }
        
        // WordPress-Admins
        $admins = get_users(array('role' => 'administrator'));
        foreach ($admins as $user) {
            if (!in_array($user->ID, $user_ids)) {
                $all_users[] = $user;
                $user_ids[] = $user->ID;
            }
        }
        
        return $all_users;
    }
    
    /**
     * Hole Benutzer-Rolle (Anzeige-Name)
     */
    public static function get_user_role_display($user) {
        if (!$user) return '';
        
        $roles = array();
        foreach ($user->roles as $role) {
            switch ($role) {
                case 'administrator':
                    $roles[] = __('WordPress Administrator', 'dienstplan-verwaltung');
                    break;
                case self::ROLE_GENERAL_ADMIN:
                    $roles[] = __('Allgemeiner Admin', 'dienstplan-verwaltung');
                    break;
                case self::ROLE_EVENT_ADMIN:
                    $roles[] = __('Veranstaltungs-Admin', 'dienstplan-verwaltung');
                    break;
                case self::ROLE_CLUB_ADMIN:
                    $roles[] = __('Vereins-Admin', 'dienstplan-verwaltung');
                    break;
                case self::ROLE_CREW:
                    $roles[] = __('Crew-Mitglied', 'dienstplan-verwaltung');
                    break;
                case self::LEGACY_ROLE_GENERAL_ADMIN:
                    $roles[] = __('Allgemeiner Admin (Legacy)', 'dienstplan-verwaltung');
                    break;
                case self::LEGACY_ROLE_EVENT_ADMIN:
                    $roles[] = __('Veranstaltungs-Admin (Legacy)', 'dienstplan-verwaltung');
                    break;
                case self::LEGACY_ROLE_CLUB_ADMIN:
                    $roles[] = __('Vereins-Admin (Legacy)', 'dienstplan-verwaltung');
                    break;
                case self::LEGACY_ROLE_CREW:
                    $roles[] = __('Crew-Mitglied (Legacy)', 'dienstplan-verwaltung');
                    break;
            }
        }
        
        return implode(', ', $roles);
    }
}

<?php
/**
 * Debug-Seite für Dienstplan-Verwaltung
 * Zeigt Statistiken und ermöglicht das Leeren von Tabellen
 */
if (!defined('ABSPATH')) exit;

require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';

// Sicherheitscheck - nur Administratoren
if (!current_user_can('manage_options')) {
    wp_die(__('Sie haben keine Berechtigung, auf diese Seite zuzugreifen.', 'dienstplan-verwaltung'));
}

$db = new Dienstplan_Database('dp_');
global $wpdb;

// Handle Option: Daten bei Deaktivierung vollständig löschen
if (isset($_POST['save_deactivate_reset_option']) && check_admin_referer('dp_debug_deactivate_reset', 'dp_debug_nonce_deactivate_reset')) {
    $enabled = isset($_POST['delete_data_on_deactivate']) ? 1 : 0;
    update_option('dienstplan_delete_data_on_deactivate', $enabled);

    if ($enabled) {
        echo '<div class="notice notice-warning"><p><strong>Reset bei Deaktivierung aktiviert.</strong> Beim nächsten Deaktivieren des Plugins werden alle Plugin-Daten gelöscht.</p></div>';
    } else {
        echo '<div class="notice notice-success"><p><strong>Reset bei Deaktivierung deaktiviert.</strong></p></div>';
    }
}

// Prüfe zuerst welche Tabellen existieren (MUSS VOR POST-Handling stehen!)
$existing_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}dp_%'");
$existing_tables_simple = array_map(function($table) use ($wpdb) {
    return str_replace($wpdb->prefix . 'dp_', '', $table);
}, $existing_tables);

// Handle Tabellen leeren
if (isset($_POST['clear_table']) && isset($_POST['table_name']) && check_admin_referer('dp_debug_clear', 'dp_debug_nonce')) {
    $table = sanitize_text_field($_POST['table_name']);
    $allowed_tables = array(
        'bereiche',
        'dienste',
        'dienst_slots',
        'dienst_zuweisungen',
        'mitarbeiter',
        'taetigkeiten',
        'veranstaltungen',
        'veranstaltung_tage',
        'vereine'
    );
    
    if (in_array($table, $allowed_tables) && in_array($table, $existing_tables_simple)) {
        $table_full = $wpdb->prefix . 'dp_' . $table;
        $wpdb->query("TRUNCATE TABLE $table_full");
        echo '<div class="notice notice-success"><p><strong>Tabelle "' . $table . '" wurde geleert!</strong></p></div>';
    }
}

// Handle ALLE Tabellen leeren
if (isset($_POST['clear_all_tables']) && check_admin_referer('dp_debug_clear_all', 'dp_debug_nonce_all')) {
    // Nur existierende Tabellen leeren, in umgekehrter Reihenfolge (wegen Foreign Keys)
    $tables = array(
        'dienst_zuweisungen',
        'dienst_slots',
        'dienste',
        'veranstaltung_tage',
        'veranstaltungen',
        'taetigkeiten',
        'bereiche',
        'mitarbeiter',
        'vereine'
    );
    
    $cleared_count = 0;
    foreach ($tables as $table) {
        if (in_array($table, $existing_tables_simple)) {
            $table_full = $wpdb->prefix . 'dp_' . $table;
            $wpdb->query("TRUNCATE TABLE $table_full");
            $cleared_count++;
        }
    }
    
    echo '<div class="notice notice-success"><p><strong>' . $cleared_count . ' Tabellen wurden geleert!</strong></p></div>';
}

// Handle Portal-Zugriffe Reset
if (isset($_POST['reset_portal_data']) && check_admin_referer('dp_debug_reset_portal', 'dp_debug_nonce_portal')) {
    // Lösche user_id Verknüpfungen
    $wpdb->query("UPDATE {$wpdb->prefix}dp_mitarbeiter SET user_id = NULL WHERE user_id IS NOT NULL");
    $affected = $wpdb->rows_affected;
    
    // Lösche Crew-User wenn gewünscht
    if (isset($_POST['delete_users'])) {
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-dienstplan-roles.php';
        $crew_users = get_users(array('role' => Dienstplan_Roles::ROLE_CREW));
        $deleted_users = 0;
        foreach ($crew_users as $user) {
            wp_delete_user($user->ID);
            $deleted_users++;
        }
        echo '<div class="notice notice-success"><p><strong>Portal-Daten zurückgesetzt!</strong><br>' . $affected . ' Verknüpfungen entfernt, ' . $deleted_users . ' Benutzer gelöscht.</p></div>';
    } else {
        echo '<div class="notice notice-success"><p><strong>Portal-Verknüpfungen zurückgesetzt!</strong><br>' . $affected . ' Verknüpfungen entfernt (Benutzer bleiben erhalten).</p></div>';
    }
}

// Handle Portal-Log Leeren
if (isset($_POST['clear_portal_log']) && check_admin_referer('dp_debug_clear_portal_log', 'dp_debug_nonce_portal_log')) {
    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}dp_portal_access_log");
    echo '<div class="notice notice-success"><p><strong>Portal-Zugriffs-Log wurde geleert!</strong></p></div>';
}

// Statistiken sammeln

$all_possible_tables = array(
    'bereiche',
    'dienste',
    'dienst_slots',
    'dienst_zuweisungen',
    'mitarbeiter',
    'taetigkeiten',
    'veranstaltungen',
    'veranstaltung_tage',
    'vereine',
    'portal_access_log'
    // Nicht implementierte Tabellen (entfernt):
    // 'vereinsmitglieder' - wird nicht verwendet
    // 'zeitslots' - wird nicht verwendet  
    // 'benutzer_rollen' - wird nicht verwendet
);

$stats = array();
foreach ($all_possible_tables as $table) {
    if (in_array($table, $existing_tables_simple)) {
        $stats[$table] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}dp_{$table}");
    } else {
        $stats[$table] = null; // Tabelle existiert nicht
    }
}

$total = 0;
foreach ($stats as $count) {
    if ($count !== null) {
        $total += $count;
    }
}
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-admin-tools" style="font-size: 2rem; margin-right: 0.5rem;"></span>
        <?php _e('Debug & Wartung', 'dienstplan-verwaltung'); ?>
    </h1>
    
    <div class="notice notice-warning" style="margin-top: 2rem;">
        <p>
            <strong>⚠️ WARNUNG:</strong> Diese Seite ist nur für Entwicklung und Testing gedacht. 
            Das Leeren von Tabellen löscht unwiderruflich alle Daten!
        </p>
    </div>

    <div class="card" style="margin-top: 2rem; border-left: 4px solid #b32d2e;">
        <h2 style="color: #b32d2e;">🧨 Reset bei Deaktivierung</h2>
        <p>
            Wenn aktiviert, löscht das Plugin beim Deaktivieren alle eigenen Tabellen und Optionen,
            damit ein kompletter Neustart möglich ist.
        </p>

        <form method="post">
            <?php wp_nonce_field('dp_debug_deactivate_reset', 'dp_debug_nonce_deactivate_reset'); ?>
            <label>
                <input type="checkbox" name="delete_data_on_deactivate" value="1"
                       <?php checked((int) get_option('dienstplan_delete_data_on_deactivate', 0), 1); ?>>
                <strong>Alle Plugin-Daten beim Deaktivieren löschen</strong>
            </label>
            <p class="description" style="color:#b32d2e; margin-top: 0.5rem;">
                Achtung: Diese Aktion ist destruktiv und nicht rückgängig.
            </p>

            <p style="margin-top: 1rem;">
                <button type="submit" name="save_deactivate_reset_option" class="button button-secondary">
                    Einstellung speichern
                </button>
            </p>
        </form>
    </div>

    <!-- Statistiken -->
    <div class="card" style="margin-top: 2rem; max-width: none;">
        <h2>📊 Datenbank-Statistiken</h2>
        <p style="color: #666; margin-bottom: 1.5rem;">
            Gesamt: <strong><?php echo number_format($total, 0, ',', '.'); ?> Einträge</strong> in allen Tabellen
        </p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="30%">Tabelle</th>
                    <th width="20%">Anzahl Einträge</th>
                    <th width="30%">Status</th>
                    <th width="20%">Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $table => $count): ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($table); ?></strong>
                        <br>
                        <small style="color: #666;"><?php echo $wpdb->prefix; ?>dp_<?php echo $table; ?></small>
                    </td>
                    <td>
                        <?php if ($count === null): ?>
                            <span style="color: #dc2626; font-weight: 600;">—</span>
                        <?php else: ?>
                            <span style="font-size: 1.2rem; font-weight: 600;">
                                <?php echo number_format($count, 0, ',', '.'); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($count === null): ?>
                            <span class="dashicons dashicons-dismiss" style="color: #dc2626;"></span>
                            <span style="color: #dc2626; font-weight: 600;">Tabelle existiert nicht</span>
                        <?php elseif ($count == 0): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
                            <span style="color: #00a32a;">Leer</span>
                        <?php elseif ($count < 10): ?>
                            <span class="dashicons dashicons-info" style="color: #dba617;"></span>
                            <span style="color: #dba617;">Wenige Einträge</span>
                        <?php else: ?>
                            <span class="dashicons dashicons-database" style="color: #2271b1;"></span>
                            <span style="color: #2271b1;">Aktiv</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($count === null): ?>
                            <span style="color: #999;">Nicht verfügbar</span>
                        <?php elseif ($count > 0): ?>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Möchten Sie wirklich alle <?php echo $count; ?> Einträge aus der Tabelle \'<?php echo $table; ?>\' löschen? Dies kann nicht rückgängig gemacht werden!');">
                                <?php wp_nonce_field('dp_debug_clear', 'dp_debug_nonce'); ?>
                                <input type="hidden" name="table_name" value="<?php echo esc_attr($table); ?>">
                                <button type="submit" name="clear_table" class="button button-small" style="background: #dc2626; color: white; border-color: #dc2626;">
                                    <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
                                    Leeren
                                </button>
                            </form>
                        <?php else: ?>
                            <span style="color: #999;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f0f6fc; font-weight: 600;">
                    <td>GESAMT</td>
                    <td><?php echo number_format($total, 0, ',', '.'); ?></td>
                    <td colspan="2">
                        <?php 
                        $missing_count = 0;
                        foreach ($stats as $count) {
                            if ($count === null) $missing_count++;
                        }
                        if ($missing_count > 0): ?>
                            <span style="color: #dc2626;">
                                <?php echo $missing_count; ?> Tabelle<?php echo $missing_count > 1 ? 'n' : ''; ?> fehlt
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Alle Tabellen leeren -->
    <?php if ($total > 0): ?>
    <div class="card" style="margin-top: 2rem; border-left: 4px solid #dc2626;">
        <h2 style="color: #dc2626;">
            <span class="dashicons dashicons-warning"></span>
            Gefährliche Aktionen
        </h2>
        
        <p style="margin-bottom: 1.5rem;">
            <strong>ACHTUNG:</strong> Diese Aktion löscht ALLE Daten aus allen Tabellen. 
            Dies sollte nur in Entwicklungs- oder Testumgebungen verwendet werden!
        </p>
        
        <form method="post" onsubmit="return confirm('⚠️⚠️⚠️ LETZTE WARNUNG ⚠️⚠️⚠️\n\nSind Sie ABSOLUT SICHER, dass Sie ALLE <?php echo number_format($total, 0, ',', '.'); ?> Einträge aus ALLEN Tabellen löschen möchten?\n\nDies kann NICHT rückgängig gemacht werden!\n\nGeben Sie zur Bestätigung \'ALLES LÖSCHEN\' ein:') && prompt('Geben Sie zur Bestätigung exakt ein: ALLES LÖSCHEN') === 'ALLES LÖSCHEN';">
            <?php wp_nonce_field('dp_debug_clear_all', 'dp_debug_nonce_all'); ?>
            <button type="submit" name="clear_all_tables" class="button button-large" style="background: #dc2626; color: white; border-color: #dc2626; font-weight: 600;">
                <span class="dashicons dashicons-trash" style="margin-top: 5px;"></span>
                ALLE TABELLEN LEEREN (<?php echo number_format($total, 0, ',', '.'); ?> Einträge)
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- System-Informationen -->
    <div class="card" style="margin-top: 2rem;">
        <h2>🔧 System-Informationen</h2>
        
        <table class="widefat" style="margin-top: 1rem;">
            <tr>
                <td width="30%"><strong>PHP Version</strong></td>
                <td><?php echo PHP_VERSION; ?></td>
            </tr>
            <tr>
                <td><strong>WordPress Version</strong></td>
                <td><?php echo get_bloginfo('version'); ?></td>
            </tr>
            <tr>
                <td><strong>Plugin Version</strong></td>
                <td><?php echo defined('DIENSTPLAN_VERSION') ? DIENSTPLAN_VERSION : 'unbekannt'; ?></td>
            </tr>
            <tr>
                <td><strong>Datenbank Präfix</strong></td>
                <td><?php echo $wpdb->prefix; ?>dp_</td>
            </tr>
            <tr>
                <td><strong>MySQL Version</strong></td>
                <td><?php echo $wpdb->db_version(); ?></td>
            </tr>
            <tr>
                <td><strong>Aktueller Benutzer</strong></td>
                <td>
                    <?php 
                    $user = wp_get_current_user();
                    echo $user->display_name . ' (' . $user->user_login . ')';
                    ?>
                </td>
            </tr>
            <tr>
                <td><strong>Aktuelle Zeit</strong></td>
                <td><?php echo date_i18n('l, d.m.Y H:i:s'); ?></td>
            </tr>
        </table>
    </div>

    <!-- 📧 Booking-Mail Debug Logs -->
    <div class="card" style="margin-top: 2rem;">
        <h2>📧 Booking-Mail Debug-Logs</h2>
        
        <?php
        $booking_mail_logs = get_option('dp_booking_mail_debug_logs', array());
        
        if (!empty($booking_mail_logs) && is_array($booking_mail_logs)):
            // Logs rückwärts (neueste zuerst)
            $logs_reversed = array_reverse($booking_mail_logs);
        ?>
        
            <p style="margin-bottom: 1rem; color: #6b7280;">
                <strong><?php echo count($booking_mail_logs); ?> Debug-Einträge</strong> vorhanden. 
                <button type="button" class="button button-small" onclick="clearBookingMailLogs()" style="margin-left: 1rem;">
                    Logs löschen
                </button>
            </p>
        
            <table class="wp-list-table widefat striped" style="margin-top: 1rem;">
                <thead>
                    <tr>
                        <th style="width: 180px;">Zeitstempel</th>
                        <th style="width: 200px;">Quelle</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs_reversed as $log_entry): ?>
                        <tr>
                            <td style="font-family: monospace; font-size: 0.85rem;">
                                <?php echo isset($log_entry['timestamp']) ? esc_html($log_entry['timestamp']) : '—'; ?>
                            </td>
                            <td>
                                <code style="background: #f0f6fc; padding: 2px 6px; border-radius: 3px;">
                                    <?php echo isset($log_entry['source']) ? esc_html($log_entry['source']) : '—'; ?>
                                </code>
                            </td>
                            <td>
                                <details style="cursor: pointer; font-size: 0.9rem;">
                                    <summary style="color: #0073aa;">Anzeigen</summary>
                                    <pre style="background: #f6f7f7; padding: 0.75rem; border-radius: 3px; margin-top: 0.5rem; overflow-x: auto; max-height: 300px; overflow-y: auto; font-size: 0.8rem;">
                                        <?php echo esc_html(wp_json_encode($log_entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?>
                                    </pre>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        
        <?php else: ?>
            <p style="color: #6b7280; font-style: italic;">
                Noch keine Booking-Mail Debug-Logs vorhanden. Lösen Sie eine Buchung auf dem Frontend aus, um Logs zu generieren.
            </p>
        <?php endif; ?>
    </div>

    <!-- Diagnose-Tools -->
    <div class="card" style="margin-top: 2rem;">
        <h2>🔍 Diagnose-Tools</h2>
        
        <ul style="line-height: 2;">
            <li>
                <strong>Dienst-Status überprüfen:</strong> 
                <button type="button" class="button button-primary" id="check-dienst-status-btn" onclick="checkDienstStatus()">
                    <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                    Alle Dienste überprüfen & korrigieren
                </button>
                <div id="check-dienst-status-result" style="margin-top: 1rem; display: none;"></div>
            </li>
            <li style="margin-top: 1rem;">
                <strong>Tag-Zuordnung prüfen:</strong> 
                <a href="<?php echo plugin_dir_url(dirname(dirname(__FILE__))); ?>fix-tags-cli.php" target="_blank">
                    fix-tags-cli.php
                </a>
                <small style="color: #666;">(via Terminal: <code>php fix-tags-cli.php</code>)</small>
            </li>
            <li>
                <strong>Tag-Zuordnung Web:</strong> 
                <a href="<?php echo plugin_dir_url(dirname(dirname(__FILE__))); ?>fix-dienste-tags.php" target="_blank">
                    fix-dienste-tags.php
                </a>
            </li>
            <li>
                <strong>Tag-Diagnose:</strong> 
                <a href="<?php echo plugin_dir_url(dirname(dirname(__FILE__))); ?>check-tag-assignments.php" target="_blank">
                    check-tag-assignments.php
                </a>
            </li>
        </ul>
    </div>

    <!-- Portal-Verwaltung -->
    <div class="card" style="margin-top: 2rem; border-left: 4px solid #667eea;">
        <h2 style="color: #667eea;">
            <span class="dashicons dashicons-admin-network"></span>
            Portal-Verwaltung & Logging
        </h2>
        
        <!-- Portal-Statistiken -->
        <div style="background: #f9fafb; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
            <h3 style="margin-top: 0;">📊 Portal-Statistiken</h3>
            <?php
            $portal_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}dp_mitarbeiter WHERE user_id IS NOT NULL");
            $total_mitarbeiter = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}dp_mitarbeiter");
            require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-dienstplan-roles.php';
            $crew_users = count(get_users(array('role' => Dienstplan_Roles::ROLE_CREW)));
            ?>
            <table class="widefat">
                <tr>
                    <td width="50%"><strong>Mitarbeiter mit Portal-Zugriff:</strong></td>
                    <td><span style="font-size: 1.2rem; font-weight: 600; color: #667eea;"><?php echo $portal_count; ?></span> von <?php echo $total_mitarbeiter; ?></td>
                </tr>
                <tr>
                    <td><strong>Crew-Benutzer (WordPress):</strong></td>
                    <td><span style="font-size: 1.2rem; font-weight: 600;"><?php echo $crew_users; ?></span></td>
                </tr>
            </table>
        </div>
        
        <!-- Letzte Portal-Zugriffe -->
        <div style="background: #f9fafb; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
            <h3 style="margin-top: 0;">🔐 Letzte Portal-Zugriffe</h3>
            <?php
            $access_logs = $wpdb->get_results(
                "SELECT l.*, m.vorname, m.nachname, u.user_login 
                FROM {$wpdb->prefix}dp_portal_access_log l
                LEFT JOIN {$wpdb->prefix}dp_mitarbeiter m ON l.mitarbeiter_id = m.id
                LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
                ORDER BY l.access_time DESC
                LIMIT 10"
            );
            
            if (!empty($access_logs)): ?>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th>Zeit</th>
                            <th>Mitarbeiter</th>
                            <th>Benutzer</th>
                            <th>Aktion</th>
                            <th>IP-Adresse</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($access_logs as $log): ?>
                        <tr>
                            <td><?php echo date_i18n('d.m.Y H:i:s', strtotime($log->access_time)); ?></td>
                            <td><?php echo esc_html($log->vorname . ' ' . $log->nachname); ?></td>
                            <td><?php echo esc_html($log->user_login); ?></td>
                            <td>
                                <?php 
                                $action_labels = array(
                                    'login' => '✅ Login',
                                    'logout' => '👋 Logout',
                                    'view_dienste' => '📋 Dienste angezeigt',
                                    'assign_dienst' => '➕ Dienst angenommen'
                                );
                                echo isset($action_labels[$log->action]) ? $action_labels[$log->action] : esc_html($log->action);
                                ?>
                            </td>
                            <td><small><?php echo esc_html($log->ip_address); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <form method="post" style="margin-top: 1rem;" onsubmit="return confirm('Möchten Sie wirklich alle Portal-Zugriffs-Logs löschen?');">
                    <?php wp_nonce_field('dp_debug_clear_portal_log', 'dp_debug_nonce_portal_log'); ?>
                    <button type="submit" name="clear_portal_log" class="button">
                        <span class="dashicons dashicons-trash"></span>
                        Zugriffs-Log leeren
                    </button>
                </form>
            <?php else: ?>
                <p style="color: #666;">Noch keine Portal-Zugriffe geloggt.</p>
            <?php endif; ?>
        </div>
        
        <!-- Portal-Reset -->
        <div style="background: #fef3c7; border: 1px solid #f59e0b; padding: 1rem; border-radius: 6px;">
            <h3 style="margin-top: 0; color: #d97706;">
                <span class="dashicons dashicons-warning"></span>
                Portal-Daten zurücksetzen
            </h3>
            <p>Entfernt alle Portal-Verknüpfungen von Mitarbeitern. Optional können auch die WordPress-Benutzer gelöscht werden.</p>
            
            <form method="post" onsubmit="return confirm('⚠️ Möchten Sie wirklich alle Portal-Daten zurücksetzen?\n\nDies entfernt die Verknüpfungen von ' + <?php echo $portal_count; ?> + ' Mitarbeitern.');">
                <?php wp_nonce_field('dp_debug_reset_portal', 'dp_debug_nonce_portal'); ?>
                <label style="display: block; margin-bottom: 1rem;">
                    <input type="checkbox" name="delete_users" value="1">
                    <strong>Auch WordPress-Benutzer löschen</strong> (<?php echo $crew_users; ?> Crew-Benutzer)
                </label>
                <button type="submit" name="reset_portal_data" class="button" style="background: #f59e0b; color: white; border-color: #d97706;">
                    <span class="dashicons dashicons-update"></span>
                    Portal-Daten zurücksetzen
                </button>
            </form>
        </div>
    </div>
    
    <!-- DB-Struktur -->
    <div class="card" style="margin-top: 2rem;">
        <h2>🗄️ Aktuelle Datenbankstruktur</h2>
        <p style="color: #666; margin-bottom: 1.5rem;">
            Alle Tabellen des Plugins mit Spalten, Typen und Constraints — direkt aus der laufenden Datenbank.
        </p>
        <?php
        $db_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}dp_%'");
        sort($db_tables);
        foreach ($db_tables as $full_table_name):
            $short_name = str_replace($wpdb->prefix . 'dp_', '', $full_table_name);
            $columns    = $wpdb->get_results("SHOW FULL COLUMNS FROM `{$full_table_name}`");
            $indexes    = $wpdb->get_results("SHOW INDEX FROM `{$full_table_name}`");
            $row_count  = $wpdb->get_var("SELECT COUNT(*) FROM `{$full_table_name}`");

            // Indexes gruppieren
            $idx_map = array();
            foreach ($indexes as $idx) {
                $idx_map[$idx->Key_name][] = $idx->Column_name;
            }
        ?>
        <details style="margin-bottom: 1rem; border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden;">
            <summary style="padding: 0.75rem 1rem; background: #f0f6fc; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 0.75rem; list-style: none;">
                <span style="font-family: monospace; color: #2271b1;"><?php echo esc_html($wpdb->prefix . 'dp_' . $short_name); ?></span>
                <span style="background: #2271b1; color: #fff; border-radius: 999px; padding: 0 0.55rem; font-size: 0.78rem; font-weight: 700;"><?php echo number_format((int)$row_count, 0, ',', '.'); ?> Zeilen</span>
                <span style="color: #666; font-size: 0.82rem; font-weight: 400;"><?php echo count($columns); ?> Spalten</span>
            </summary>
            <div style="padding: 1rem;">
                <table class="wp-list-table widefat fixed striped" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th style="width: 22%;">Spalte</th>
                            <th style="width: 20%;">Typ</th>
                            <th style="width: 8%;">Null</th>
                            <th style="width: 10%;">Key</th>
                            <th style="width: 20%;">Default</th>
                            <th style="width: 20%;">Extra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($columns as $col): ?>
                        <tr>
                            <td>
                                <strong style="font-family: monospace;">
                                    <?php if ($col->Key === 'PRI'): ?>
                                        <span style="color: #b32d2e;" title="Primary Key">🔑</span>
                                    <?php elseif ($col->Key === 'MUL'): ?>
                                        <span title="Index">⚡</span>
                                    <?php elseif ($col->Key === 'UNI'): ?>
                                        <span title="Unique">✨</span>
                                    <?php endif; ?>
                                    <?php echo esc_html($col->Field); ?>
                                </strong>
                            </td>
                            <td><code style="font-size: 0.8rem; background: #f3f4f6; padding: 1px 4px; border-radius: 3px;"><?php echo esc_html($col->Type); ?></code></td>
                            <td style="color: <?php echo $col->Null === 'YES' ? '#d97706' : '#666'; ?>;"><?php echo esc_html($col->Null); ?></td>
                            <td>
                                <?php if ($col->Key): ?>
                                    <span style="background: <?php echo $col->Key === 'PRI' ? '#fee2e2' : ($col->Key === 'UNI' ? '#dbeafe' : '#f3f4f6'); ?>; color: <?php echo $col->Key === 'PRI' ? '#b32d2e' : ($col->Key === 'UNI' ? '#1d4ed8' : '#4b5563'); ?>; border-radius: 3px; padding: 1px 5px; font-size: 0.78rem; font-weight: 700;"><?php echo esc_html($col->Key); ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="color: #666; font-style: <?php echo $col->Default === null ? 'italic' : 'normal'; ?>;"><?php echo $col->Default === null ? 'NULL' : esc_html($col->Default); ?></td>
                            <td style="color: #666; font-size: 0.78rem;"><?php echo esc_html($col->Extra); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (!empty($idx_map)): ?>
                <div style="margin-top: 1rem;">
                    <strong style="font-size: 0.85rem; color: #4b5563;">Indizes:</strong>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.4rem;">
                        <?php foreach ($idx_map as $idx_name => $idx_cols): ?>
                            <span style="background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 4px; padding: 2px 8px; font-size: 0.78rem; font-family: monospace;">
                                <?php echo $idx_name === 'PRIMARY' ? '🔑 PRIMARY' : esc_html($idx_name); ?>
                                <span style="color: #6b7280;">(<?php echo esc_html(implode(', ', $idx_cols)); ?>)</span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </details>
        <?php endforeach; ?>
        <?php if (empty($db_tables)): ?>
            <p style="color: #dc2626;">Keine Plugin-Tabellen gefunden (Präfix: <?php echo esc_html($wpdb->prefix); ?>dp_).</p>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="card" style="margin-top: 2rem;">
        <h2>⚡ Quick Actions</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
            <a href="<?php echo admin_url('admin.php?page=dienstplan-vereine'); ?>" class="button button-primary" style="height: auto; padding: 1rem; text-align: center;">
                <span class="dashicons dashicons-groups" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></span>
                Vereine
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=dienstplan-veranstaltungen'); ?>" class="button button-primary" style="height: auto; padding: 1rem; text-align: center;">
                <span class="dashicons dashicons-calendar" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></span>
                Veranstaltungen
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=dienstplan-mitarbeiter'); ?>" class="button button-primary" style="height: auto; padding: 1rem; text-align: center;">
                <span class="dashicons dashicons-admin-users" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></span>
                Mitarbeiter
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=dienstplan-dienste'); ?>" class="button button-primary" style="height: auto; padding: 1rem; text-align: center;">
                <span class="dashicons dashicons-list-view" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></span>
                Dienste
            </a>
        </div>
    </div>
</div>

<style>
.card {
    background: white;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 1.5rem;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.card h2 {
    margin-top: 0;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.wp-list-table td {
    vertical-align: middle;
}

.button.button-small {
    padding: 2px 8px;
    height: auto;
    line-height: 1.5;
}

.notice {
    padding: 1rem;
    border-left: 4px solid #f59e0b;
    background: #fef3c7;
    margin: 1rem 0;
}

.notice-success {
    border-left-color: #00a32a;
    background: #ecfdf5;
}
</style>

<script>
function checkDienstStatus() {
    const btn = document.getElementById('check-dienst-status-btn');
    const resultDiv = document.getElementById('check-dienst-status-result');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="dashicons dashicons-update-spin"></span> Lädt...';
    
    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'dp_check_dienst_status',
            nonce: '<?php echo wp_create_nonce('dp_ajax_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<span class="dashicons dashicons-update" style="margin-top: 3px;"></span> Alle Dienste überprüfen & korrigieren';
        
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="notice notice-success is-dismissible">
                    <p>
                        <strong>✅ Dienste überprüft!</strong><br>
                        ${data.data.message}<br>
                        <br>
                        <small>
                            Gesamt: ${data.data.total} Dienste<br>
                            Status-Änderungen: ${data.data.updated}<br>
                            Unvollständige Dienste: ${data.data.incomplete}
                        </small>
                    </p>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="notice notice-error is-dismissible">
                    <p>
                        <strong>❌ Fehler:</strong><br>
                        ${data.data.message}
                    </p>
                </div>
            `;
        }
        resultDiv.style.display = 'block';
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = '<span class="dashicons dashicons-update" style="margin-top: 3px;"></span> Alle Dienste überprüfen & korrigieren';
        resultDiv.innerHTML = `
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong>❌ Fehler:</strong><br>
                    ${error.message}
                </p>
            </div>
        `;
        resultDiv.style.display = 'block';
    });
}

function clearBookingMailLogs() {
    if (confirm('Alle Booking-Mail Debug-Logs löschen?\n\nDies kann nicht rückgängig gemacht werden.')) {
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'dp_clear_booking_mail_logs',
                nonce: '<?php echo wp_create_nonce('dp_ajax_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Logs wurden gelöscht! Seite wird neu geladen...');
                location.reload();
            } else {
                alert('❌ Fehler: ' + data.data.message);
            }
        })
        .catch(error => {
            alert('❌ Fehler: ' + error.message);
        });
    }
}
</script>

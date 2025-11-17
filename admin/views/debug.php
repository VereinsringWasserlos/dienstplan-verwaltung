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
    'vereine'
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
</script>

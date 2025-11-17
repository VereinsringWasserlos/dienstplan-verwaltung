<?php
/**
 * Migration Script: Füge mitarbeiter_id zu dienst_zuweisungen hinzu
 * 
 * Führe dieses Script einmalig aus im Browser oder via WP-CLI
 * URL: http://feg.test/wp-content/plugins/dienstplan-verwaltung/migrate-mitarbeiter-id.php
 */

// WordPress laden
require_once('../../../wp-load.php');

// Prüfe Admin-Rechte
if (!current_user_can('manage_options')) {
    die('Keine Berechtigung. Bitte als Administrator einloggen.');
}

global $wpdb;
$prefix = $wpdb->prefix . 'dp_';

echo '<h1>Dienstplan Migration: mitarbeiter_id</h1>';
echo '<p>Prüfe Tabelle wp_dp_dienst_zuweisungen...</p>';

// Prüfe ob Spalte existiert
$column_exists = $wpdb->get_results(
    "SHOW COLUMNS FROM {$prefix}dienst_zuweisungen LIKE 'mitarbeiter_id'"
);

if (!empty($column_exists)) {
    echo '<p style="color: green;">✓ Spalte mitarbeiter_id existiert bereits!</p>';
    echo '<p>Migration nicht erforderlich.</p>';
} else {
    echo '<p style="color: orange;">⚠ Spalte mitarbeiter_id fehlt. Füge hinzu...</p>';
    
    $result = $wpdb->query(
        "ALTER TABLE {$prefix}dienst_zuweisungen 
        ADD COLUMN mitarbeiter_id mediumint(9) DEFAULT NULL AFTER slot_id,
        ADD KEY mitarbeiter_id (mitarbeiter_id)"
    );
    
    if ($result === false) {
        echo '<p style="color: red;">✗ FEHLER: ' . $wpdb->last_error . '</p>';
    } else {
        echo '<p style="color: green;">✓ Spalte mitarbeiter_id erfolgreich hinzugefügt!</p>';
    }
}

// Zeige Tabellenstruktur
echo '<h2>Aktuelle Tabellenstruktur:</h2>';
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$prefix}dienst_zuweisungen");

echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
echo '<tr><th>Spalte</th><th>Typ</th><th>Null</th><th>Key</th><th>Default</th></tr>';
foreach ($columns as $col) {
    echo '<tr>';
    echo '<td>' . $col->Field . '</td>';
    echo '<td>' . $col->Type . '</td>';
    echo '<td>' . $col->Null . '</td>';
    echo '<td>' . $col->Key . '</td>';
    echo '<td>' . $col->Default . '</td>';
    echo '</tr>';
}
echo '</table>';

echo '<p><strong>Migration abgeschlossen!</strong></p>';
echo '<p><a href="' . admin_url('admin.php?page=dienstplan-dashboard') . '">← Zurück zum Dashboard</a></p>';

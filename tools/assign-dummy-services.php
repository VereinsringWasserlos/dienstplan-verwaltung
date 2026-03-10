<?php
if (PHP_SAPI !== 'cli') {
    echo "Dieses Skript darf nur per CLI ausgeführt werden.\n";
    exit(1);
}

$_SERVER['REQUEST_SCHEME'] = $_SERVER['REQUEST_SCHEME'] ?? 'http';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

require dirname(__DIR__, 4) . '/wp-load.php';

if (!defined('DIENSTPLAN_PLUGIN_PATH')) {
    define('DIENSTPLAN_PLUGIN_PATH', dirname(__DIR__) . '/');
}

require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';

$maxAssignments = isset($argv[1]) ? max(1, (int) $argv[1]) : 30;
$dbPrefix = defined('DIENSTPLAN_DB_PREFIX') ? DIENSTPLAN_DB_PREFIX : 'dp_';

$db = new Dienstplan_Database($dbPrefix);
$wpdb = $db->get_wpdb();
$prefix = $db->get_prefix();

$dummyMitarbeiter = $wpdb->get_results(
    "SELECT id, email
     FROM {$prefix}mitarbeiter
     WHERE email LIKE 'testcrew%@dienstplan.local'
     ORDER BY id ASC"
);

if (empty($dummyMitarbeiter)) {
    echo "Keine Dummy-Mitarbeiter gefunden. Bitte zuerst Seeder ausführen:\n";
    echo "php tools/seed-dummy-test-data.php\n";
    exit(1);
}

$openSlots = $wpdb->get_results(
    "SELECT s.id
     FROM {$prefix}dienst_slots s
     INNER JOIN {$prefix}dienste d ON d.id = s.dienst_id
     INNER JOIN {$prefix}veranstaltungen v ON v.id = d.veranstaltung_id
     WHERE s.mitarbeiter_id IS NULL
       AND (s.status = 'offen' OR s.status = 'frei' OR s.status IS NULL)
       AND v.status IN ('geplant','aktiv')
     ORDER BY d.veranstaltung_id ASC, d.tag_id ASC, s.slot_nummer ASC
     LIMIT " . (int) $maxAssignments
);

if (empty($openSlots)) {
    echo "Keine freien Slots für geplante/aktive Veranstaltungen gefunden.\n";
    exit(0);
}

$assigned = 0;
$employeeIds = array_map(static function ($item) {
    return (int) $item->id;
}, $dummyMitarbeiter);

foreach ($openSlots as $index => $slot) {
    $mitarbeiterId = $employeeIds[$index % count($employeeIds)];

    $result = $wpdb->update(
        $prefix . 'dienst_slots',
        array(
            'mitarbeiter_id' => $mitarbeiterId,
            'status' => 'besetzt',
        ),
        array('id' => (int) $slot->id),
        array('%d', '%s'),
        array('%d')
    );

    if ($result !== false) {
        $assigned++;
    }
}

echo "Dummy-Zuweisung abgeschlossen.\n";
echo "- Gefundene Dummy-Mitarbeiter: " . count($dummyMitarbeiter) . "\n";
echo "- Neu zugewiesene Slots: {$assigned}\n";

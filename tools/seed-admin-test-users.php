<?php
if (PHP_SAPI !== 'cli') {
    echo "Dieses Skript darf nur per CLI ausgeführt werden.\n";
    exit(1);
}

$wpLoadPath = dirname(__DIR__, 4) . '/wp-load.php';
if (!file_exists($wpLoadPath)) {
    echo "wp-load.php nicht gefunden unter: {$wpLoadPath}\n";
    exit(1);
}

if (!isset($_SERVER['REQUEST_SCHEME'])) {
    $_SERVER['REQUEST_SCHEME'] = 'http';
}
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

require_once $wpLoadPath;

if (!defined('DIENSTPLAN_PLUGIN_PATH')) {
    define('DIENSTPLAN_PLUGIN_PATH', dirname(__DIR__) . '/');
}

require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-dienstplan-roles.php';

$dbPrefix = defined('DIENSTPLAN_DB_PREFIX') ? DIENSTPLAN_DB_PREFIX : 'dp_';
$db = new Dienstplan_Database($dbPrefix);
$db->install();
$wpdb = $db->get_wpdb();
$prefix = $db->get_prefix();

Dienstplan_Roles::install_roles();

$defaultPassword = 'TestAdmin1234!';

echo "=== Dienstplan Admin-Testuser Seeder ===\n";
echo "Verwendetes Plugin-DB-Präfix: {$dbPrefix}\n";
echo "Standardpasswort für alle Test-Admins: {$defaultPassword}\n\n";

function ensure_min_vereine($wpdb, $prefix, $minCount = 3) {
    $vereine = $wpdb->get_results("SELECT id, name FROM {$prefix}vereine ORDER BY id ASC");
    $attempts = 0;

    while (count($vereine) < $minCount) {
        $attempts++;
        if ($attempts > 10) {
            echo "Abbruch: Vereine konnten nicht zuverlässig erzeugt werden. Letzter DB-Fehler: {$wpdb->last_error}\n";
            break;
        }

        $index = count($vereine) + 1;
        $name = 'Testverein Admin ' . $index;
        $kuerzel = 'TA' . $index;

        $wpdb->insert(
            $prefix . 'vereine',
            array(
                'name' => $name,
                'kuerzel' => $kuerzel,
                'beschreibung' => 'Automatisch erzeugt für Admin-Tests',
                'aktiv' => 1,
            ),
            array('%s', '%s', '%s', '%d')
        );

        $vereine = $wpdb->get_results("SELECT id, name FROM {$prefix}vereine ORDER BY id ASC");
    }

    return $vereine;
}

function ensure_min_veranstaltungen($wpdb, $prefix, $vereinIds, $minCount = 3) {
    $events = $wpdb->get_results("SELECT id, name FROM {$prefix}veranstaltungen ORDER BY id ASC");
    $attempts = 0;

    while (count($events) < $minCount) {
        $attempts++;
        if ($attempts > 10) {
            echo "Abbruch: Veranstaltungen konnten nicht zuverlässig erzeugt werden. Letzter DB-Fehler: {$wpdb->last_error}\n";
            break;
        }

        $index = count($events) + 1;
        $start = date('Y-m-d', strtotime('+' . (4 + $index) . ' days'));
        $end = date('Y-m-d', strtotime($start . ' +1 day'));

        $wpdb->insert(
            $prefix . 'veranstaltungen',
            array(
                'name' => 'Testveranstaltung Admin ' . $index,
                'beschreibung' => 'Automatisch erzeugt für Admin-Tests',
                'typ' => 'mehrtaegig',
                'status' => 'geplant',
                'start_datum' => $start,
                'end_datum' => $end,
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );

        $eventId = (int) $wpdb->insert_id;
        if ($eventId > 0 && !empty($vereinIds)) {
            $vereinId = (int) $vereinIds[($index - 1) % count($vereinIds)];
            $wpdb->query($wpdb->prepare(
                "INSERT IGNORE INTO {$prefix}veranstaltung_vereine (veranstaltung_id, verein_id) VALUES (%d, %d)",
                $eventId,
                $vereinId
            ));
        }

        $events = $wpdb->get_results("SELECT id, name FROM {$prefix}veranstaltungen ORDER BY id ASC");
    }

    return $events;
}

$vereine = ensure_min_vereine($wpdb, $prefix, 3);
$vereinIds = array_map(function($v) { return (int) $v->id; }, $vereine);

$events = ensure_min_veranstaltungen($wpdb, $prefix, $vereinIds, 3);
$eventIds = array_map(function($e) { return (int) $e->id; }, $events);

$testAdmins = array(
    array(
        'username' => 'testgenadmin',
        'email' => 'testgenadmin@dienstplan.local',
        'first_name' => 'General',
        'last_name' => 'Admin',
        'role' => Dienstplan_Roles::ROLE_GENERAL_ADMIN,
        'verein_indexes' => array(0, 1),
        'event_indexes' => array(0, 1),
    ),
    array(
        'username' => 'testeventadmin',
        'email' => 'testeventadmin@dienstplan.local',
        'first_name' => 'Event',
        'last_name' => 'Admin',
        'role' => Dienstplan_Roles::ROLE_EVENT_ADMIN,
        'verein_indexes' => array(1),
        'event_indexes' => array(1, 2),
    ),
    array(
        'username' => 'testclubadmin',
        'email' => 'testclubadmin@dienstplan.local',
        'first_name' => 'Club',
        'last_name' => 'Admin',
        'role' => Dienstplan_Roles::ROLE_CLUB_ADMIN,
        'verein_indexes' => array(0, 2),
        'event_indexes' => array(0),
    ),
);

$createdUsers = 0;
$updatedUsers = 0;
$vereinAssignments = 0;
$eventAssignments = 0;
$assignmentSnapshot = array();

foreach ($testAdmins as $adminConfig) {
    $existingUser = get_user_by('email', $adminConfig['email']);

    if ($existingUser) {
        $userId = (int) $existingUser->ID;
        wp_set_password($defaultPassword, $userId);
        $updatedUsers++;
    } else {
        $username = $adminConfig['username'];
        if (username_exists($username)) {
            $username = $username . '_' . wp_generate_password(4, false, false);
        }

        $createdId = wp_create_user($username, $defaultPassword, $adminConfig['email']);
        if (is_wp_error($createdId)) {
            echo "User konnte nicht erstellt werden ({$adminConfig['email']}): " . $createdId->get_error_message() . "\n";
            continue;
        }

        $userId = (int) $createdId;
        $createdUsers++;
    }

    $wpUser = new WP_User($userId);
    $wpUser->set_role($adminConfig['role']);
    update_user_meta($userId, 'first_name', $adminConfig['first_name']);
    update_user_meta($userId, 'last_name', $adminConfig['last_name']);
    update_user_meta($userId, 'show_admin_bar_front', true);

    foreach ($adminConfig['verein_indexes'] as $vereinIndex) {
        if (!isset($vereinIds[$vereinIndex])) {
            continue;
        }
        $vereinId = (int) $vereinIds[$vereinIndex];

        $db->assign_user_to_verein($userId, $vereinId, null);

        $inserted = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$prefix}verein_verantwortliche (verein_id, user_id) VALUES (%d, %d)",
            $vereinId,
            $userId
        ));

        if ($inserted !== false) {
            $vereinAssignments += (int) $inserted;
        }
    }

    foreach ($adminConfig['event_indexes'] as $eventIndex) {
        if (!isset($eventIds[$eventIndex])) {
            continue;
        }
        $eventId = (int) $eventIds[$eventIndex];

        $inserted = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$prefix}veranstaltung_verantwortliche (veranstaltung_id, user_id) VALUES (%d, %d)",
            $eventId,
            $userId
        ));

        if ($inserted !== false) {
            $eventAssignments += (int) $inserted;
        }
    }

    $vereinCount = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$prefix}verein_verantwortliche WHERE user_id = %d",
        $userId
    ));
    $eventCount = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$prefix}veranstaltung_verantwortliche WHERE user_id = %d",
        $userId
    ));

    $assignmentSnapshot[] = array(
        'email' => $adminConfig['email'],
        'role' => $adminConfig['role'],
        'vereine' => $vereinCount,
        'events' => $eventCount,
    );
}

echo "Fertig.\n";
echo "- Admin-User erstellt: {$createdUsers}\n";
echo "- Admin-User aktualisiert: {$updatedUsers}\n";
echo "- Neue Vereins-Zuordnungen: {$vereinAssignments}\n";
echo "- Neue Veranstaltungs-Zuordnungen: {$eventAssignments}\n";
echo "\nAktuelle Zuordnungen je Test-Admin:\n";
foreach ($assignmentSnapshot as $row) {
    echo "- {$row['email']} ({$row['role']}): Vereine={$row['vereine']}, Veranstaltungen={$row['events']}\n";
}

echo "\nTest-Logins (Passwort identisch für alle):\n";
echo "- testgenadmin@dienstplan.local (Rolle: " . Dienstplan_Roles::ROLE_GENERAL_ADMIN . ")\n";
echo "- testeventadmin@dienstplan.local (Rolle: " . Dienstplan_Roles::ROLE_EVENT_ADMIN . ")\n";
echo "- testclubadmin@dienstplan.local (Rolle: " . Dienstplan_Roles::ROLE_CLUB_ADMIN . ")\n";
echo "- Passwort: {$defaultPassword}\n";

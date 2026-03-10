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

$defaultPassword = 'Test1234';
$dummyCount = 12;
$maxAssignments = 24;

echo "=== Dienstplan Dummy Seeder ===\n";
echo "Verwendetes Plugin-DB-Präfix: {$dbPrefix}\n";
echo "Passwort für alle Test-User: {$defaultPassword}\n\n";

Dienstplan_Roles::install_roles();

function ensure_test_verein($wpdb, $prefix) {
    $vereinId = (int) $wpdb->get_var("SELECT id FROM {$prefix}vereine ORDER BY id ASC LIMIT 1");
    if ($vereinId > 0) {
        return $vereinId;
    }

    $wpdb->insert(
        $prefix . 'vereine',
        array(
            'name' => 'Testverein Seeder',
            'kuerzel' => 'TVS',
            'beschreibung' => 'Automatisch erzeugt für Dummy-Tests',
            'aktiv' => 1,
        ),
        array('%s', '%s', '%s', '%d')
    );

    return (int) $wpdb->insert_id;
}

function ensure_test_bereich($wpdb, $prefix) {
    $bereichId = (int) $wpdb->get_var("SELECT id FROM {$prefix}bereiche ORDER BY id ASC LIMIT 1");
    if ($bereichId > 0) {
        return $bereichId;
    }

    $wpdb->insert(
        $prefix . 'bereiche',
        array(
            'name' => 'Testbereich Seeder',
            'beschreibung' => 'Automatisch erzeugt',
            'farbe' => '#3b82f6',
            'sortierung' => 1,
            'aktiv' => 1,
        ),
        array('%s', '%s', '%s', '%d', '%d')
    );

    return (int) $wpdb->insert_id;
}

function ensure_test_taetigkeit($wpdb, $prefix, $bereichId) {
    $taetigkeitId = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$prefix}taetigkeiten WHERE bereich_id = %d ORDER BY id ASC LIMIT 1",
        $bereichId
    ));
    if ($taetigkeitId > 0) {
        return $taetigkeitId;
    }

    $wpdb->insert(
        $prefix . 'taetigkeiten',
        array(
            'bereich_id' => $bereichId,
            'name' => 'Testtätigkeit Seeder',
            'beschreibung' => 'Automatisch erzeugt',
            'sortierung' => 1,
            'aktiv' => 1,
        ),
        array('%d', '%s', '%s', '%d', '%d')
    );

    return (int) $wpdb->insert_id;
}

function ensure_seed_services_with_slots($wpdb, $prefix, $vereinId, $bereichId, $taetigkeitId) {
    $freeSlots = (int) $wpdb->get_var(
        "SELECT COUNT(*)
         FROM {$prefix}dienst_slots s
         INNER JOIN {$prefix}dienste d ON d.id = s.dienst_id
         INNER JOIN {$prefix}veranstaltungen v ON v.id = d.veranstaltung_id
         WHERE s.mitarbeiter_id IS NULL
           AND (s.status = 'offen' OR s.status = 'frei' OR s.status IS NULL)
           AND v.status IN ('geplant','aktiv')"
    );

    if ($freeSlots > 0) {
        return;
    }

    $eventName = 'Seeder Testevent ' . date('Y-m-d');
    $eventId = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$prefix}veranstaltungen WHERE name = %s LIMIT 1",
        $eventName
    ));

    $startDate = date('Y-m-d', strtotime('+5 days'));
    $endDate = date('Y-m-d', strtotime('+6 days'));

    if ($eventId <= 0) {
        $wpdb->insert(
            $prefix . 'veranstaltungen',
            array(
                'name' => $eventName,
                'beschreibung' => 'Automatisch erzeugt für Dummy-Tests',
                'typ' => 'mehrtaegig',
                'status' => 'geplant',
                'start_datum' => $startDate,
                'end_datum' => $endDate,
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
        $eventId = (int) $wpdb->insert_id;
    }

    $wpdb->query($wpdb->prepare(
        "INSERT IGNORE INTO {$prefix}veranstaltung_vereine (veranstaltung_id, verein_id) VALUES (%d, %d)",
        $eventId,
        $vereinId
    ));

    $dayRows = $wpdb->get_results($wpdb->prepare(
        "SELECT id, tag_datum FROM {$prefix}veranstaltung_tage WHERE veranstaltung_id = %d ORDER BY tag_nummer ASC",
        $eventId
    ));

    if (empty($dayRows)) {
        $days = array(
            array('date' => $startDate, 'num' => 1),
            array('date' => $endDate, 'num' => 2),
        );
        foreach ($days as $day) {
            $wpdb->insert(
                $prefix . 'veranstaltung_tage',
                array(
                    'veranstaltung_id' => $eventId,
                    'tag_datum' => $day['date'],
                    'tag_nummer' => $day['num'],
                    'von_zeit' => '16:00:00',
                    'bis_zeit' => '23:00:00',
                ),
                array('%d', '%s', '%d', '%s', '%s')
            );
        }

        $dayRows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, tag_datum FROM {$prefix}veranstaltung_tage WHERE veranstaltung_id = %d ORDER BY tag_nummer ASC",
            $eventId
        ));
    }

    foreach ($dayRows as $index => $dayRow) {
        $dienstId = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$prefix}dienste WHERE veranstaltung_id = %d AND tag_id = %d LIMIT 1",
            $eventId,
            $dayRow->id
        ));

        if ($dienstId <= 0) {
            $startTime = ($index % 2 === 0) ? '17:00:00' : '18:00:00';
            $endTime = ($index % 2 === 0) ? '21:00:00' : '22:00:00';

            $wpdb->insert(
                $prefix . 'dienste',
                array(
                    'veranstaltung_id' => $eventId,
                    'tag_id' => $dayRow->id,
                    'verein_id' => $vereinId,
                    'bereich_id' => $bereichId,
                    'taetigkeit_id' => $taetigkeitId,
                    'von_zeit' => $startTime,
                    'bis_zeit' => $endTime,
                    'anzahl_personen' => 4,
                    'status' => 'geplant',
                    'splittbar' => 1,
                ),
                array('%d', '%d', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%d')
            );
            $dienstId = (int) $wpdb->insert_id;
        }

        $existingSlots = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}dienst_slots WHERE dienst_id = %d",
            $dienstId
        ));

        if ($existingSlots <= 0) {
            for ($slot = 1; $slot <= 4; $slot++) {
                $wpdb->insert(
                    $prefix . 'dienst_slots',
                    array(
                        'dienst_id' => $dienstId,
                        'slot_nummer' => $slot,
                        'von_zeit' => ($index % 2 === 0) ? '17:00:00' : '18:00:00',
                        'bis_zeit' => ($index % 2 === 0) ? '21:00:00' : '22:00:00',
                        'status' => 'offen',
                    ),
                    array('%d', '%d', '%s', '%s', '%s')
                );
            }
        }
    }
}

$vereinId = ensure_test_verein($wpdb, $prefix);
$bereichId = ensure_test_bereich($wpdb, $prefix);
$taetigkeitId = ensure_test_taetigkeit($wpdb, $prefix, $bereichId);
ensure_seed_services_with_slots($wpdb, $prefix, $vereinId, $bereichId, $taetigkeitId);

$createdUsers = 0;
$linkedEmployees = 0;
$employeeIds = array();
$userIds = array();

for ($index = 1; $index <= $dummyCount; $index++) {
    $number = str_pad((string) $index, 2, '0', STR_PAD_LEFT);
    $firstName = 'Test' . $number;
    $lastName = 'Crew';
    $email = "testcrew{$number}@dienstplan.local";
    $phone = '0151-0000' . str_pad((string) $index, 3, '0', STR_PAD_LEFT);
    $username = 'testcrew' . $number;

    $existingUser = get_user_by('email', $email);
    if ($existingUser) {
        $userId = (int) $existingUser->ID;
        wp_set_password($defaultPassword, $userId);
    } else {
        if (username_exists($username)) {
            $username = $username . '_x';
        }

        $userId = wp_create_user($username, $defaultPassword, $email);
        if (is_wp_error($userId)) {
            echo "User konnte nicht erstellt werden ({$email}): " . $userId->get_error_message() . "\n";
            continue;
        }
        $createdUsers++;
    }

    $wpUser = new WP_User($userId);
    $wpUser->set_role(Dienstplan_Roles::ROLE_CREW);
    update_user_meta($userId, 'first_name', $firstName);
    update_user_meta($userId, 'last_name', $lastName);
    update_user_meta($userId, 'show_admin_bar_front', false);

    $mitarbeiter = $db->get_mitarbeiter_by_email($email);

    if ($mitarbeiter) {
        $mitarbeiterId = (int) $mitarbeiter->id;
        $db->update_mitarbeiter(
            $mitarbeiterId,
            array(
                'vorname' => $firstName,
                'nachname' => $lastName,
                'telefon' => $phone,
                'user_id' => $userId,
            )
        );
    } else {
        $mitarbeiterId = $db->add_mitarbeiter(
            array(
                'vorname' => $firstName,
                'nachname' => $lastName,
                'email' => $email,
                'telefon' => $phone,
                'user_id' => $userId,
            )
        );
    }

    if ($mitarbeiterId > 0) {
        $linkedEmployees++;
        $employeeIds[] = $mitarbeiterId;
        $userIds[] = (int) $userId;
        $db->assign_user_to_verein((int) $userId, $vereinId, $mitarbeiterId);
    }
}

$freeSlots = $wpdb->get_results(
    "SELECT s.id, s.dienst_id
     FROM {$prefix}dienst_slots s
     INNER JOIN {$prefix}dienste d ON d.id = s.dienst_id
     INNER JOIN {$prefix}veranstaltungen v ON v.id = d.veranstaltung_id
     WHERE s.mitarbeiter_id IS NULL
       AND (s.status = 'offen' OR s.status = 'frei' OR s.status IS NULL)
       AND v.status IN ('geplant','aktiv')
     ORDER BY d.veranstaltung_id ASC, d.tag_id ASC, s.slot_nummer ASC
     LIMIT " . (int) $maxAssignments
);

$assignedCount = 0;
if (!empty($freeSlots) && !empty($employeeIds)) {
    foreach ($freeSlots as $position => $slot) {
        $employeeId = (int) $employeeIds[$position % count($employeeIds)];

        $updated = $wpdb->update(
            $prefix . 'dienst_slots',
            array(
                'mitarbeiter_id' => $employeeId,
                'status' => 'besetzt',
            ),
            array('id' => (int) $slot->id),
            array('%d', '%s'),
            array('%d')
        );

        if ($updated !== false) {
            $assignedCount++;
        }
    }
}

echo "Fertig.\n";
echo "- Dummy-User erstellt: {$createdUsers}\n";
echo "- Mitarbeiter verknüpft/aktualisiert: {$linkedEmployees}\n";
echo "- Slot-Zuweisungen gesetzt: {$assignedCount}\n";
echo "\nTest-Login:\n";
echo "- Benutzer: testcrew01\n";
echo "- Passwort: {$defaultPassword}\n";
echo "- E-Mail: testcrew01@dienstplan.local\n";

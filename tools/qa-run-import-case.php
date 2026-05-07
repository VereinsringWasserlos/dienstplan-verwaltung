<?php

if (!defined('ABSPATH')) {
    exit(1);
}

$case = strtolower(trim((string) getenv('DP_IMPORT_CASE')));
$suffix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) getenv('DP_IMPORT_SUFFIX')));
if ($suffix === '') {
    $suffix = strtoupper(substr(md5((string) microtime(true)), 0, 6));
}

$admins = get_users(array(
    'role' => 'administrator',
    'number' => 1,
    'orderby' => 'ID',
    'order' => 'ASC',
));
if (empty($admins)) {
    fwrite(STDERR, "Kein Administrator-User gefunden.\n");
    exit(2);
}
wp_set_current_user($admins[0]->ID);

require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
$db_prefix = defined('DIENSTPLAN_DB_PREFIX') ? DIENSTPLAN_DB_PREFIX : 'dp_';
$db = new Dienstplan_Database($db_prefix);

$verein_name = 'QA Verein ' . $suffix;
$verein_kuerzel = substr('Q' . $suffix, 0, 10);
$bereich_name = 'QA Bereich ' . $suffix;
$taetigkeit_name = 'QA Taetigkeit ' . $suffix;
$event_name = 'QA Import Event ' . $suffix;
$event_start = gmdate('Y-m-d');
$event_end = gmdate('Y-m-d', strtotime('+1 day'));

$import_type = '';
$csv_data = array();
$mapping = array();
$extra_post = array();

switch ($case) {
    case 'vereine':
        $import_type = 'vereine';
        $csv_data = array(
            array($verein_name, $verein_kuerzel, 'QA Test', 'Max Mustermann', 'qa+' . strtolower($suffix) . '@example.org', '01234-567890')
        );
        $mapping = array(
            'name' => 0,
            'kuerzel' => 1,
            'beschreibung' => 2,
            'kontakt_name' => 3,
            'kontakt_email' => 4,
            'kontakt_telefon' => 5,
        );
        break;

    case 'bereiche':
        $import_type = 'bereiche';
        $csv_data = array(
            array($bereich_name, '#16a34a', '1', '100', '0')
        );
        $mapping = array(
            'name' => 0,
            'farbe' => 1,
            'aktiv' => 2,
            'sortierung' => 3,
            'admin_only' => 4,
        );
        break;

    case 'taetigkeiten':
        $import_type = 'taetigkeiten';
        $csv_data = array(
            array($bereich_name, $taetigkeit_name, 'QA Beschreibung', '1', '100', '0')
        );
        $mapping = array(
            'bereich_name' => 0,
            'name' => 1,
            'beschreibung' => 2,
            'aktiv' => 3,
            'sortierung' => 4,
            'admin_only' => 5,
        );
        break;

    case 'veranstaltungen':
        $import_type = 'veranstaltungen';
        $csv_data = array(
            array($event_name, $event_start, $event_end, 'QA Event', '08:00', '22:00')
        );
        $mapping = array(
            'name' => 0,
            'start_datum' => 1,
            'end_datum' => 2,
            'beschreibung' => 3,
            'dienst_von_zeit' => 4,
            'dienst_bis_zeit' => 5,
        );
        break;

    case 'dienste':
    case 'dienstplan':
        $event = $db->get_veranstaltung_by_name($event_name);
        $event_id = is_array($event) ? intval($event['id']) : 0;
        if ($event_id <= 0) {
            fwrite(STDERR, "Veranstaltung nicht gefunden fuer Case '" . $case . "': " . $event_name . "\n");
            exit(3);
        }

        if ($case === 'dienste') {
            $import_type = 'dienste';
            $csv_data = array(
                array($event_start, 'dienst', $bereich_name, $taetigkeit_name, '09:00', '11:00', $verein_kuerzel, '2', '0', '0', 'QA Dienst Import')
            );
            $mapping = array(
                'datum' => 0,
                'dienst_typ' => 1,
                'bereich_name' => 2,
                'taetigkeit_name' => 3,
                'von_zeit' => 4,
                'bis_zeit' => 5,
                'verein_kuerzel' => 6,
                'anzahl_personen' => 7,
                'splittbar' => 8,
                'admin_only' => 9,
                'besonderheiten' => 10,
            );
            $extra_post = array(
                'veranstaltung_id' => $event_id,
                'veranstaltung_start' => $event_start,
                'veranstaltung_ende' => $event_end,
            );
        } else {
            $import_type = 'dienstplan';
            $csv_data = array(
                array($event_start, '12:00', '13:00', $bereich_name, '#16a34a', '0', $taetigkeit_name, '0', $verein_kuerzel, $verein_name, '1', '0', 'QA Dienstplan Import')
            );
            $mapping = array(
                'datum' => 0,
                'von_zeit' => 1,
                'bis_zeit' => 2,
                'bereich_name' => 3,
                'bereich_farbe' => 4,
                'bereich_admin_only' => 5,
                'taetigkeit_name' => 6,
                'taetigkeit_admin_only' => 7,
                'verein_kuerzel' => 8,
                'verein_name' => 9,
                'anzahl_personen' => 10,
                'splittbar' => 11,
                'besonderheiten' => 12,
            );
            $extra_post = array(
                'veranstaltung_id' => $event_id,
                'veranstaltung_start' => $event_start,
                'veranstaltung_ende' => $event_end,
                'default_bereich_farbe' => '#16a34a',
                'auto_create_vereine' => 0,
            );
        }
        break;

    default:
        fwrite(STDERR, "Unbekannter Case: " . $case . "\n");
        exit(4);
}

$_POST = array_merge(array(
    'action' => 'dp_import_csv',
    'nonce' => wp_create_nonce('dp_ajax_nonce'),
    'import_type' => $import_type,
    'import_mode' => 'create',
    'timezone' => 'UTC',
    'csv_data' => wp_json_encode($csv_data),
    'mapping' => wp_json_encode($mapping),
), $extra_post);
$_REQUEST = $_POST;

if (!defined('DOING_AJAX')) {
    define('DOING_AJAX', true);
}

// Gibt JSON aus und beendet den Prozess via wp_send_json.
do_action('wp_ajax_dp_import_csv');

exit(0);

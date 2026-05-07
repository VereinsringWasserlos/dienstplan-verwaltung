<?php

if (!defined('ABSPATH')) {
    exit(1);
}

if (!defined('DOING_AJAX')) {
    define('DOING_AJAX', true);
}

require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';

function dp_run_import_test($import_type, $csv_data, $mapping, $extra_post = array())
{
    $nonce = wp_create_nonce('dp_ajax_nonce');

    $_POST = array_merge(array(
        'action' => 'dp_import_csv',
        'nonce' => $nonce,
        'import_type' => $import_type,
        'import_mode' => 'create',
        'timezone' => 'UTC',
        'csv_data' => wp_json_encode($csv_data),
        'mapping' => wp_json_encode($mapping),
    ), $extra_post);
    $_REQUEST = $_POST;

    $die_handler = function ($message = '', $title = '', $args = array()) {
        if (is_scalar($message)) {
            throw new Exception((string) $message);
        }
        throw new Exception(wp_json_encode($message));
    };

    $die_filter = function () use ($die_handler) {
        return $die_handler;
    };

    $doing_ajax_filter = function () {
        return true;
    };

    add_filter('wp_die_handler', $die_filter);
    add_filter('wp_doing_ajax', $doing_ajax_filter);

    $exception_message = '';
    ob_start();
    try {
        do_action('wp_ajax_dp_import_csv');
    } catch (Exception $e) {
        $exception_message = $e->getMessage();
    }
    $raw_output = ob_get_clean();

    remove_filter('wp_die_handler', $die_filter);
    remove_filter('wp_doing_ajax', $doing_ajax_filter);

    $payload = null;
    if (is_string($raw_output) && trim($raw_output) !== '') {
        $payload = json_decode(trim($raw_output), true);
    }

    if (!is_array($payload) && trim($exception_message) !== '') {
        $payload = json_decode(trim($exception_message), true);
    }

    $ok = is_array($payload)
        && !empty($payload['success'])
        && isset($payload['data'])
        && is_array($payload['data'])
        && intval($payload['data']['errors']) === 0;

    return array(
        'ok' => $ok,
        'payload' => $payload,
        'raw_output' => $raw_output,
        'exception' => $exception_message,
    );
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

$db_prefix = defined('DIENSTPLAN_DB_PREFIX') ? DIENSTPLAN_DB_PREFIX : 'dp_';
$db = new Dienstplan_Database($db_prefix);

$suffix = strtoupper(substr(md5((string) microtime(true)), 0, 6));
$verein_name = 'QA Verein ' . $suffix;
$verein_kuerzel = substr('Q' . $suffix, 0, 10);
$bereich_name = 'QA Bereich ' . $suffix;
$taetigkeit_name = 'QA Taetigkeit ' . $suffix;
$event_name = 'QA Import Event ' . $suffix;
$event_start = gmdate('Y-m-d');
$event_end = gmdate('Y-m-d', strtotime('+1 day'));

$results = array();

$results['vereine'] = dp_run_import_test(
    'vereine',
    array(
        array($verein_name, $verein_kuerzel, 'QA Test', 'Max Mustermann', 'qa+' . strtolower($suffix) . '@example.org', '01234-567890')
    ),
    array(
        'name' => 0,
        'kuerzel' => 1,
        'beschreibung' => 2,
        'kontakt_name' => 3,
        'kontakt_email' => 4,
        'kontakt_telefon' => 5,
    )
);

$results['bereiche'] = dp_run_import_test(
    'bereiche',
    array(
        array($bereich_name, '#16a34a', '1', '100', '0')
    ),
    array(
        'name' => 0,
        'farbe' => 1,
        'aktiv' => 2,
        'sortierung' => 3,
        'admin_only' => 4,
    )
);

$results['taetigkeiten'] = dp_run_import_test(
    'taetigkeiten',
    array(
        array($bereich_name, $taetigkeit_name, 'QA Beschreibung', '1', '100', '0')
    ),
    array(
        'bereich_name' => 0,
        'name' => 1,
        'beschreibung' => 2,
        'aktiv' => 3,
        'sortierung' => 4,
        'admin_only' => 5,
    )
);

$results['veranstaltungen'] = dp_run_import_test(
    'veranstaltungen',
    array(
        array($event_name, $event_start, $event_end, 'QA Event', '08:00', '22:00')
    ),
    array(
        'name' => 0,
        'start_datum' => 1,
        'end_datum' => 2,
        'beschreibung' => 3,
        'dienst_von_zeit' => 4,
        'dienst_bis_zeit' => 5,
    )
);

$event = $db->get_veranstaltung_by_name($event_name);
$event_id = is_array($event) ? intval($event['id']) : 0;

if ($event_id <= 0) {
    fwrite(STDERR, "Veranstaltung fuer Dienste-Tests nicht gefunden: " . $event_name . "\n");
    exit(3);
}

$results['dienste'] = dp_run_import_test(
    'dienste',
    array(
        array($event_start, 'dienst', $bereich_name, $taetigkeit_name, '09:00', '11:00', $verein_kuerzel, '2', '0', '0', 'QA Dienst Import')
    ),
    array(
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
    ),
    array(
        'veranstaltung_id' => $event_id,
        'veranstaltung_start' => $event_start,
        'veranstaltung_ende' => $event_end,
    )
);

$results['dienstplan'] = dp_run_import_test(
    'dienstplan',
    array(
        array($event_start, '12:00', '13:00', $bereich_name, '#16a34a', '0', $taetigkeit_name, '0', $verein_kuerzel, $verein_name, '1', '0', 'QA Dienstplan Import')
    ),
    array(
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
    ),
    array(
        'veranstaltung_id' => $event_id,
        'veranstaltung_start' => $event_start,
        'veranstaltung_ende' => $event_end,
        'default_bereich_farbe' => '#16a34a',
        'auto_create_vereine' => 0,
    )
);

$failed = 0;
echo "Import Testlauf (alle Typen)\n";
echo "Suffix: " . $suffix . "\n\n";

foreach ($results as $type => $result) {
    $status = $result['ok'] ? 'PASS' : 'FAIL';
    $created = isset($result['payload']['data']['created']) ? intval($result['payload']['data']['created']) : -1;
    $updated = isset($result['payload']['data']['updated']) ? intval($result['payload']['data']['updated']) : -1;
    $skipped = isset($result['payload']['data']['skipped']) ? intval($result['payload']['data']['skipped']) : -1;
    $errors = isset($result['payload']['data']['errors']) ? intval($result['payload']['data']['errors']) : -1;

    echo sprintf("- %s: %s (created=%d, updated=%d, skipped=%d, errors=%d)\n", $type, $status, $created, $updated, $skipped, $errors);

    if (!$result['ok']) {
        $failed++;
        if (is_array($result['payload']['data']['error_details'] ?? null)) {
            foreach ($result['payload']['data']['error_details'] as $detail) {
                if (is_array($detail) && isset($detail['message'])) {
                    echo "    detail: " . $detail['message'] . "\n";
                } elseif (is_string($detail)) {
                    echo "    detail: " . $detail . "\n";
                }
            }
        } elseif (!empty($result['raw_output'])) {
            echo "    raw: " . trim($result['raw_output']) . "\n";
        } elseif (!empty($result['exception'])) {
            echo "    exception: " . trim($result['exception']) . "\n";
        }
    }
}

echo "\n";
echo "Gesamt: " . (count($results) - $failed) . "/" . count($results) . " bestanden\n";

if ($failed > 0) {
    exit(1);
}

exit(0);

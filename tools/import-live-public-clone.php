<?php

if (!defined('ABSPATH')) {
    exit(1);
}

require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-activator.php';
require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-dienstplan-notifications.php';

function dp_live_import_log($message) {
    if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
        WP_CLI::log($message);
        return;
    }

    echo $message . PHP_EOL;
}

function dp_live_import_fail($message) {
    if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
        WP_CLI::error($message);
        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function dp_live_import_xpath_text(DOMXPath $xpath, $query, DOMNode $context_node = null) {
    $nodes = $context_node ? $xpath->query($query, $context_node) : $xpath->query($query);
    if (!$nodes || $nodes->length === 0) {
        return '';
    }

    return trim(preg_replace('/\s+/u', ' ', html_entity_decode($nodes->item(0)->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

function dp_live_import_load_xpath($file_path) {
    $html = file_get_contents($file_path);
    if ($html === false) {
        dp_live_import_fail('Datei konnte nicht gelesen werden: ' . $file_path);
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();

    return new DOMXPath($dom);
}

function dp_live_import_parse_event_range($text) {
    if (!preg_match('/(\d{2}\.\d{2}\.\d{4})\s*[\x{2013}\-]\s*(\d{2}\.\d{2}\.\d{4})/u', $text, $matches)) {
        return array(null, null);
    }

    $start = DateTime::createFromFormat('d.m.Y', $matches[1]);
    $end = DateTime::createFromFormat('d.m.Y', $matches[2]);

    return array(
        $start ? $start->format('Y-m-d') : null,
        $end ? $end->format('Y-m-d') : null,
    );
}

function dp_live_import_parse_service_range($text) {
    if (!preg_match('/(\d{2}\.\d{2}\.\d{4})\s+(\d{2}:\d{2})\s*[\x{2013}\-]\s*(\d{2}\.\d{2}\.\d{4})\s+(\d{2}:\d{2})/u', $text, $matches)) {
        return array(null, null, null, null);
    }

    $start_date = DateTime::createFromFormat('d.m.Y', $matches[1]);
    $end_date = DateTime::createFromFormat('d.m.Y', $matches[3]);

    return array(
        $start_date ? $start_date->format('Y-m-d') : null,
        $matches[2] . ':00',
        $end_date ? $end_date->format('Y-m-d') : null,
        $matches[4] . ':00',
    );
}

function dp_live_import_parse_time_range($text, $tag_date) {
    if (!preg_match('/(\d{2}:\d{2})\s*[\x{2013}\-]\s*(\d{2}:\d{2})/u', $text, $matches)) {
        return array(null, null, null);
    }

    $from_time = $matches[1] . ':00';
    $to_time = $matches[2] . ':00';
    $end_date = null;

    if ($to_time < $from_time) {
        $date = new DateTimeImmutable($tag_date);
        $end_date = $date->modify('+1 day')->format('Y-m-d');
    }

    return array($from_time, $to_time, $end_date);
}

function dp_live_import_extract_stat_total($text) {
    if (preg_match('/(\d+)\s+von\s+(\d+)\s+frei/i', $text, $matches)) {
        return max(1, intval($matches[2]));
    }

    return null;
}

function dp_live_import_extract_color($style) {
    if (preg_match('/background-color\s*:\s*([^;]+)/i', $style, $matches)) {
        return trim($matches[1]);
    }

    return '#3b82f6';
}

function dp_live_import_extract_slug($file_name, $prefix) {
    $base = basename($file_name, '.html');
    if (strpos($base, $prefix) !== 0) {
        return '';
    }

    return substr($base, strlen($prefix));
}

function dp_live_import_reset_plugin_data() {
    global $wpdb;

    $tables = $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->prefix . 'dp_%'));
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
    }

    $option_names = array(
        'dienstplan_version',
        'dienstplan_db_version',
        'dienstplan_db_migration_history',
        'dienstplan_settings',
        'dienstplan_auto_update_enabled',
        'dienstplan_roles_version',
        'dienstplan_roles_migration_pending',
        'dienstplan_portal_page_id',
        'dienstplan_show_portal_setup',
        'dienstplan_last_status_repair',
        'dienstplan_update_info',
        'dienstplan_delete_data_on_deactivate',
        'dp_site_name',
        'dp_date_format',
    );

    foreach ($option_names as $option_name) {
        if (function_exists('delete_option')) {
            delete_option($option_name);
        }
    }

    if (function_exists('delete_metadata')) {
        delete_metadata('user', 0, 'dienstplan_', '', true);
    }

    Dienstplan_Activator::activate();
    $notifications = new Dienstplan_Notifications(DIENSTPLAN_DB_PREFIX);
    $notifications->install();
}

$clone_dir = __DIR__ . DIRECTORY_SEPARATOR . 'live-clone-2026-05-06';
if (!is_dir($clone_dir)) {
    dp_live_import_fail('Clone-Verzeichnis nicht gefunden: ' . $clone_dir);
}

$club_overview_files = glob($clone_dir . DIRECTORY_SEPARATOR . 'verein-*.html');
$event_files = glob($clone_dir . DIRECTORY_SEPARATOR . 'kerb-2026-*.html');

if (empty($club_overview_files) || empty($event_files)) {
    dp_live_import_fail('Erwartete Live-Clone-Dateien wurden nicht gefunden.');
}

$club_meta_by_slug = array();
foreach ($club_overview_files as $club_file) {
    $slug = dp_live_import_extract_slug($club_file, 'verein-');
    if ($slug === '') {
        continue;
    }

    $xpath = dp_live_import_load_xpath($club_file);
    $club_name = dp_live_import_xpath_text($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' dp-landing-title ')]");
    $club_short = dp_live_import_xpath_text($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' dp-landing-subtitle ')]");

    if ($club_name === '' || $club_short === '') {
        continue;
    }

    $club_meta_by_slug[$slug] = array(
        'name' => $club_name,
        'kuerzel' => $club_short,
    );
}

$event_data = null;
$services = array();
$seen_service_ids = array();

foreach ($event_files as $event_file) {
    if (basename($event_file) === 'kerb-2026-dienstplan.html') {
        continue;
    }

    $slug = dp_live_import_extract_slug($event_file, 'kerb-2026-');
    if (!isset($club_meta_by_slug[$slug])) {
        continue;
    }

    $club_meta = $club_meta_by_slug[$slug];
    $xpath = dp_live_import_load_xpath($event_file);

    if ($event_data === null) {
        $event_name = dp_live_import_xpath_text($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' dp-event-title ')]");
        $event_range = dp_live_import_xpath_text($xpath, "//*[contains(., 'Veranstaltung:')]");
        $service_range = dp_live_import_xpath_text($xpath, "//*[contains(., 'Dienstzeitraum:')]");
        $status_label = dp_live_import_xpath_text($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' dp-header-chip ')][contains(., 'Anmeldung') or contains(., 'Aktiv') or contains(., 'Abgeschlossen')]");
        list($start_date, $end_date) = dp_live_import_parse_event_range($event_range);
        list($service_start_date, $service_start_time, $service_end_date, $service_end_time) = dp_live_import_parse_service_range($service_range);

        $event_status = 'geplant';
        if (stripos($status_label, 'abgeschlossen') !== false) {
            $event_status = 'abgeschlossen';
        } elseif (stripos($status_label, 'aktiv') !== false) {
            $event_status = 'aktiv';
        } elseif (stripos($status_label, 'planung') !== false) {
            $event_status = 'in_planung';
        }

        $event_data = array(
            'name' => $event_name !== '' ? $event_name : 'Kerb 2026',
            'start_datum' => $start_date,
            'end_datum' => $end_date,
            'status' => $event_status,
            'dienst_von_datum' => $service_start_date,
            'dienst_von_zeit' => $service_start_time,
            'dienst_bis_datum' => $service_end_date,
            'dienst_bis_zeit' => $service_end_time,
        );
    }

    $sections = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' dp-day-section ')]");
    foreach ($sections as $section) {
        $tag_date_text = dp_live_import_xpath_text($xpath, ".//*[contains(concat(' ', normalize-space(@class), ' '), ' dp-day-title ')]", $section);
        $tag_date = DateTime::createFromFormat('d.m.Y', $tag_date_text);
        if (!$tag_date) {
            continue;
        }

        $tag_date_iso = $tag_date->format('Y-m-d');
        $cards = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' dp-dienst-card ')]", $section);
        foreach ($cards as $card) {
            $source_service_id = intval($card->attributes->getNamedItem('data-dienst-id')->nodeValue);
            if ($source_service_id <= 0 || isset($seen_service_ids[$source_service_id])) {
                continue;
            }

            $seen_service_ids[$source_service_id] = true;

            $time_text = dp_live_import_xpath_text($xpath, ".//*[contains(concat(' ', normalize-space(@class), ' '), ' dp-dienst-time-big ')]", $card);
            list($from_time, $to_time, $end_date) = dp_live_import_parse_time_range($time_text, $tag_date_iso);
            if ($from_time === null || $to_time === null) {
                continue;
            }

            $bereich_name = dp_live_import_xpath_text($xpath, ".//*[contains(concat(' ', normalize-space(@class), ' '), ' dp-bereich-badge ')]", $card);
            $bereich_nodes = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' dp-bereich-badge ')]", $card);
            $bereich_style = '';
            if ($bereich_nodes && $bereich_nodes->length > 0 && $bereich_nodes->item(0) instanceof DOMElement) {
                /** @var DOMElement $bereich_badge */
                $bereich_badge = $bereich_nodes->item(0);
                $bereich_style = $bereich_badge->getAttribute('style');
            }
            $beschreibung = dp_live_import_xpath_text($xpath, ".//*[contains(concat(' ', normalize-space(@class), ' '), ' dp-dienst-beschreibung ')]", $card);
            $dienst_name = dp_live_import_xpath_text($xpath, ".//*[contains(concat(' ', normalize-space(@class), ' '), ' dp-dienst-name ')]", $card);
            $stats_text = dp_live_import_xpath_text($xpath, ".//*[contains(concat(' ', normalize-space(@class), ' '), ' dp-stat ')]", $card);
            $slot_nodes = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' dp-slot-item ')]", $card);
            $anzahl_personen = dp_live_import_extract_stat_total($stats_text);
            if ($anzahl_personen === null) {
                $anzahl_personen = ($slot_nodes && $slot_nodes->length > 0) ? $slot_nodes->length : 1;
            }

            $services[] = array(
                'source_id' => $source_service_id,
                'club_slug' => $slug,
                'tag_datum' => $tag_date_iso,
                'bereich_name' => $bereich_name,
                'bereich_farbe' => dp_live_import_extract_color($bereich_style),
                'taetigkeit_name' => $dienst_name,
                'besonderheiten' => $beschreibung,
                'von_zeit' => $from_time,
                'bis_zeit' => $to_time,
                'bis_datum' => $end_date,
                'anzahl_personen' => max(1, intval($anzahl_personen)),
                'admin_only' => ($card instanceof DOMElement && intval($card->getAttribute('data-admin-only'))) ? 1 : 0,
            );
        }
    }
}

if ($event_data === null || empty($services)) {
    dp_live_import_fail('Es konnten keine importierbaren Veranstaltungsdaten aus dem Live-Clone gelesen werden.');
}

usort($services, static function ($left, $right) {
    return strcmp(
        $left['tag_datum'] . '|' . $left['von_zeit'] . '|' . $left['club_slug'] . '|' . $left['taetigkeit_name'],
        $right['tag_datum'] . '|' . $right['von_zeit'] . '|' . $right['club_slug'] . '|' . $right['taetigkeit_name']
    );
});

$day_windows = array();
foreach ($services as $service) {
    $day_key = $service['tag_datum'];
    $start_ts = strtotime($service['tag_datum'] . ' ' . $service['von_zeit']);
    $end_date = $service['bis_datum'] ?: $service['tag_datum'];
    $end_ts = strtotime($end_date . ' ' . $service['bis_zeit']);

    if (!isset($day_windows[$day_key])) {
        $day_windows[$day_key] = array(
            'min_start' => $start_ts,
            'max_end' => $end_ts,
            'max_end_date' => $end_date,
            'min_start_time' => $service['von_zeit'],
            'max_end_time' => $service['bis_zeit'],
        );
        continue;
    }

    if ($start_ts < $day_windows[$day_key]['min_start']) {
        $day_windows[$day_key]['min_start'] = $start_ts;
        $day_windows[$day_key]['min_start_time'] = $service['von_zeit'];
    }

    if ($end_ts > $day_windows[$day_key]['max_end']) {
        $day_windows[$day_key]['max_end'] = $end_ts;
        $day_windows[$day_key]['max_end_date'] = $end_date;
        $day_windows[$day_key]['max_end_time'] = $service['bis_zeit'];
    }
}

dp_live_import_log('Loesche lokale Plugin-Daten und initialisiere Tabellen neu ...');
dp_live_import_reset_plugin_data();

$db = new Dienstplan_Database(DIENSTPLAN_DB_PREFIX);

$used_club_slugs = array_values(array_unique(array_map(static function ($service) {
    return $service['club_slug'];
}, $services)));

$club_ids_by_slug = array();
foreach ($used_club_slugs as $club_slug) {
    if (!isset($club_meta_by_slug[$club_slug])) {
        dp_live_import_fail('Keine Vereinsmetadaten fuer Slug gefunden: ' . $club_slug);
    }

    $club_meta = $club_meta_by_slug[$club_slug];
    $club_id = $db->add_verein(array(
        'name' => $club_meta['name'],
        'kuerzel' => $club_meta['kuerzel'],
        'farbe' => '#3b82f6',
        'aktiv' => 1,
    ));

    if (!$club_id) {
        $existing = $db->get_verein_by_kuerzel($club_meta['kuerzel']);
        if (empty($existing['id'])) {
            dp_live_import_fail('Verein konnte nicht angelegt werden: ' . $club_meta['name']);
        }
        $club_id = intval($existing['id']);
        $db->update_verein($club_id, array(
            'name' => $club_meta['name'],
            'kuerzel' => $club_meta['kuerzel'],
            'farbe' => '#3b82f6',
            'aktiv' => 1,
        ));
    }

    $club_ids_by_slug[$club_slug] = $club_id;
}

$event_id = $db->add_veranstaltung(array(
    'name' => $event_data['name'],
    'beschreibung' => 'Import aus oeffentlichem Live-Clone ohne Personen- oder Crew-Zuordnungsdaten.',
    'typ' => $event_data['start_datum'] !== $event_data['end_datum'] ? 'mehrtaegig' : 'eintaegig',
    'status' => $event_data['status'],
    'start_datum' => $event_data['start_datum'],
    'end_datum' => $event_data['end_datum'],
));

if (!$event_id) {
    $existing_event = $db->get_veranstaltung_by_name($event_data['name']);
    if (empty($existing_event['id'])) {
        dp_live_import_fail('Veranstaltung konnte nicht angelegt werden: ' . $event_data['name']);
    }
    $event_id = intval($existing_event['id']);
}

$tag_ids_by_date = array();
$sorted_dates = array_keys($day_windows);
sort($sorted_dates);

foreach ($sorted_dates as $index => $tag_date) {
    $window = $day_windows[$tag_date];
    $db->add_veranstaltung_tag(array(
        'veranstaltung_id' => $event_id,
        'tag_datum' => $tag_date,
        'tag_nummer' => $index + 1,
        'dienst_von_zeit' => $window['min_start_time'],
        'dienst_bis_zeit' => $window['max_end_time'],
        'dienst_bis_datum' => $window['max_end_date'] !== $tag_date ? $window['max_end_date'] : null,
        'nur_dienst' => 0,
    ));
}

$event_tags = $db->get_veranstaltung_tage($event_id);
foreach ($event_tags as $event_tag) {
    $tag_ids_by_date[$event_tag->tag_datum] = intval($event_tag->id);
}

foreach ($club_ids_by_slug as $club_id) {
    $db->add_veranstaltung_verein($event_id, $club_id);
}

$bereich_map = array();
foreach ($db->get_bereiche(false) as $bereich) {
    $bereich_map[mb_strtolower(trim($bereich->name), 'UTF-8')] = $bereich;
}

$taetigkeit_map = array();
foreach ($db->get_taetigkeiten(false) as $taetigkeit) {
    $taetigkeit_map[intval($taetigkeit->bereich_id) . '|' . mb_strtolower(trim($taetigkeit->name), 'UTF-8')] = $taetigkeit;
}

foreach ($services as $service) {
    $bereich_key = mb_strtolower(trim($service['bereich_name']), 'UTF-8');
    if (!isset($bereich_map[$bereich_key])) {
        $bereich_id = $db->add_bereich(array(
            'name' => $service['bereich_name'],
            'farbe' => $service['bereich_farbe'],
            'aktiv' => 1,
            'sortierung' => 999,
            'admin_only' => $service['admin_only'],
        ));

        if (!$bereich_id) {
            dp_live_import_fail('Bereich konnte nicht angelegt werden: ' . $service['bereich_name']);
        }

        $bereich_map[$bereich_key] = (object) array(
            'id' => $bereich_id,
            'name' => $service['bereich_name'],
        );
    }

    $bereich_id = intval($bereich_map[$bereich_key]->id);
    $taetigkeit_key = $bereich_id . '|' . mb_strtolower(trim($service['taetigkeit_name']), 'UTF-8');
    if (!isset($taetigkeit_map[$taetigkeit_key])) {
        $taetigkeit_id = $db->add_taetigkeit(array(
            'bereich_id' => $bereich_id,
            'name' => $service['taetigkeit_name'],
            'beschreibung' => '',
            'aktiv' => 1,
            'sortierung' => 999,
            'admin_only' => $service['admin_only'],
        ));

        if (!$taetigkeit_id) {
            dp_live_import_fail('Taetigkeit konnte nicht angelegt werden: ' . $service['taetigkeit_name']);
        }

        $taetigkeit_map[$taetigkeit_key] = (object) array(
            'id' => $taetigkeit_id,
            'name' => $service['taetigkeit_name'],
            'bereich_id' => $bereich_id,
        );
    }

    $result = $db->add_dienst(array(
        'veranstaltung_id' => $event_id,
        'tag_id' => $tag_ids_by_date[$service['tag_datum']],
        'verein_id' => $club_ids_by_slug[$service['club_slug']],
        'dienst_typ' => 'dienst',
        'bereich_id' => $bereich_id,
        'taetigkeit_id' => intval($taetigkeit_map[$taetigkeit_key]->id),
        'von_zeit' => $service['von_zeit'],
        'bis_zeit' => $service['bis_zeit'],
        'bis_datum' => $service['bis_datum'],
        'anzahl_personen' => $service['anzahl_personen'],
        'besonderheiten' => $service['besonderheiten'],
        'splittbar' => 0,
        'admin_only' => $service['admin_only'],
        'status' => 'geplant',
    ));

    if (is_array($result) && !empty($result['error'])) {
        dp_live_import_fail('Dienst konnte nicht angelegt werden: ' . $service['taetigkeit_name'] . ' am ' . $service['tag_datum'] . ' (' . $result['message'] . ')');
    }

    if (!$result) {
        dp_live_import_fail('Dienst konnte nicht angelegt werden: ' . $service['taetigkeit_name'] . ' am ' . $service['tag_datum']);
    }
}

$summary = array(
    'veranstaltungen' => count($db->get_veranstaltungen()),
    'veranstaltungstage' => count($db->get_veranstaltung_tage($event_id)),
    'vereine' => count($db->get_vereine(false)),
    'bereiche' => count($db->get_bereiche(false)),
    'taetigkeiten' => count($db->get_taetigkeiten(false)),
    'dienste' => count($db->get_dienste($event_id)),
    'mitarbeiter' => count($db->get_mitarbeiter()),
);

global $wpdb;
$summary['dienst_zuweisungen'] = intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}dp_dienst_zuweisungen"));

dp_live_import_log('Import abgeschlossen.');
dp_live_import_log(json_encode($summary, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
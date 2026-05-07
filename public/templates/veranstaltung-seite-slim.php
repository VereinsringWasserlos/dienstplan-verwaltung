<?php
/**
 * Slim FE: Veranstaltungs-Einzelseite
 * Erwartet: $veranstaltung_id, optional $verein_id
 */

if (!defined('ABSPATH')) {
    exit;
}

$atts = array(
    'veranstaltung_id' => isset($veranstaltung_id) ? intval($veranstaltung_id) : 0,
    'verein_id' => isset($verein_id) ? intval($verein_id) : 0,
    'view' => isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'kachel',
);

include DIENSTPLAN_PLUGIN_PATH . 'public/templates/veranstaltung-verein.php';

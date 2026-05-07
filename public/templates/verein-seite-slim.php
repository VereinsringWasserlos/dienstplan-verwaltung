<?php
/**
 * Slim FE: Vereins-Einzelseite
 * Erwartet: $verein_id
 */

if (!defined('ABSPATH')) {
    exit;
}

$atts = array(
    'show_aktiv' => 'true',
    'verein_id' => isset($verein_id) ? intval($verein_id) : 0,
);

include DIENSTPLAN_PLUGIN_PATH . 'public/templates/vereine-overview.php';

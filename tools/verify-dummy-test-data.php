<?php
if (PHP_SAPI !== 'cli') {
    echo "CLI only\n";
    exit(1);
}

if (!isset($_SERVER['REQUEST_SCHEME'])) {
    $_SERVER['REQUEST_SCHEME'] = 'http';
}
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

require dirname(__DIR__, 4) . '/wp-load.php';

global $wpdb;
$dbPrefix = defined('DIENSTPLAN_DB_PREFIX') ? DIENSTPLAN_DB_PREFIX : 'dp_';
$p = $wpdb->prefix . $dbPrefix;

echo 'wp_prefix=' . $wpdb->prefix . PHP_EOL;
echo 'plugin_db_prefix=' . $dbPrefix . PHP_EOL;
echo 'table_mitarbeiter_exists=' . ((int) !empty($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $p . 'mitarbeiter')))) . PHP_EOL;
echo 'table_user_vereine_exists=' . ((int) !empty($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $p . 'user_vereine')))) . PHP_EOL;
echo 'mitarbeiter_total=' . (int) $wpdb->get_var("SELECT COUNT(*) FROM {$p}mitarbeiter") . PHP_EOL;

echo 'crew_users=' . (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} WHERE user_email LIKE 'testcrew%@dienstplan.local'") . PHP_EOL;
echo 'mitarbeiter_links=' . (int) $wpdb->get_var("SELECT COUNT(*) FROM {$p}mitarbeiter WHERE email LIKE 'testcrew%@dienstplan.local' AND user_id IS NOT NULL") . PHP_EOL;
echo 'user_verein=' . (int) $wpdb->get_var("SELECT COUNT(*) FROM {$p}user_vereine uv JOIN {$wpdb->users} u ON u.ID = uv.user_id WHERE u.user_email LIKE 'testcrew%@dienstplan.local'") . PHP_EOL;
echo 'slots_assigned=' . (int) $wpdb->get_var("SELECT COUNT(*) FROM {$p}dienst_slots s JOIN {$p}mitarbeiter m ON m.id = s.mitarbeiter_id WHERE m.email LIKE 'testcrew%@dienstplan.local'") . PHP_EOL;
echo 'db_last_error=' . $wpdb->last_error . PHP_EOL;

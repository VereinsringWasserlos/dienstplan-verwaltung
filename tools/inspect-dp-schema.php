<?php
if (PHP_SAPI !== 'cli') {
    exit(1);
}
$_SERVER['REQUEST_SCHEME'] = $_SERVER['REQUEST_SCHEME'] ?? 'http';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
require dirname(__DIR__, 4) . '/wp-load.php';

global $wpdb;
$tables = array('dp_mitarbeiter', 'dp_user_vereine', 'dp_dienst_slots');
foreach ($tables as $name) {
    $table = $wpdb->prefix . $name;
    $exists = (int) !empty($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)));
    echo "table={$table} exists={$exists}" . PHP_EOL;
    if ($exists) {
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$table}");
        foreach ($columns as $col) {
            echo "  {$col->Field}|{$col->Type}|{$col->Null}|{$col->Default}" . PHP_EOL;
        }
    }
}

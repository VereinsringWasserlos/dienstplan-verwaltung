<?php
/**
 * Debug: Queue Log Viewer
 * Zeigt die letzten Queue-Log-Einträge
 */

// Disable output buffering
@ini_set('display_errors', '1');
define('WP_USE_THEMES', false);

// Load WordPress
require dirname(dirname(dirname(__DIR__))) . '/wp-load.php';

// Security: nur Admin
if (!current_user_can('manage_options')) {
    die('Access denied.');
}

$log = get_option('dp_mail_queue_log', []);
$queue = get_option('dp_mail_queue', []);

echo "Queue Status Report\n";
echo "===================\n\n";

echo "Queue items: " . count($queue) . "\n";
echo "Log entries: " . count($log) . "\n\n";

echo "Last 20 Log Entries (newest first):\n";
echo str_repeat("-", 120) . "\n";
printf("%-20s | %-30s | %-30s | %-10s | %s\n", "TIMESTAMP", "TO", "SUBJECT", "STATUS", "ERROR");
echo str_repeat("-", 120) . "\n";

foreach (array_reverse(array_slice($log, -20)) as $entry) {
    $error = isset($entry['error']) && $entry['error'] ? 'YES: ' . substr($entry['error'], 0, 40) : 'none';
    printf(
        "%-20s | %-30s | %-30s | %-10s | %s\n",
        substr($entry['timestamp'] ?? 'N/A', 0, 19),
        substr($entry['to'] ?? 'N/A', 0, 30),
        substr($entry['subject'] ?? 'N/A', 0, 30),
        $entry['status'] ?? 'N/A',
        $error
    );
}

echo "\n\nQueued items (not yet sent):\n";
echo str_repeat("-", 80) . "\n";
if (empty($queue)) {
    echo "  (empty)\n";
} else {
    foreach (array_slice($queue, -10) as $item) {
        printf("  - to: %s | subject: %s\n", $item['to'] ?? 'N/A', substr($item['subject'] ?? 'N/A', 0, 50));
    }
}

?>

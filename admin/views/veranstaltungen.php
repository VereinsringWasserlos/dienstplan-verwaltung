<?php
/**
 * Veranstaltungen-Verwaltung Template
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/admin/views
 */

if (!defined('ABSPATH')) exit;

// Setup für Page-Header Partial
$page_title = __('Veranstaltungen', 'dienstplan-verwaltung');
$page_icon = 'dashicons-calendar-alt';
$page_class = 'header-veranstaltungen';
$nav_items = [
    [
        'label' => __('Dashboard', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan'),
        'icon' => 'dashicons-dashboard',
    ],
    [
        'label' => __('Vereine', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-vereine'),
        'icon' => 'dashicons-flag',
    ],
    [
        'label' => __('Dienste', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-dienste'),
        'icon' => 'dashicons-clipboard',
    ],
    [
        'label' => __('Mitarbeiter', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-mitarbeiter'),
        'icon' => 'dashicons-groups',
    ],
    [
        'label' => __('Bereiche & Tätigkeiten', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-bereiche'),
        'icon' => 'dashicons-category',
    ],
];
?>

<div class="wrap dienstplan-wrap">
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/page-header.php'; ?>
    
    <hr class="wp-header-end">
    
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/veranstaltungen-header.php'; ?>
    
    <?php if (!empty($veranstaltungen)): ?>
        <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/veranstaltungen-table.php'; ?>
    <?php else: ?>
        <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/veranstaltungen-empty.php'; ?>
    <?php endif; ?>
    
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/veranstaltungen-modal.php'; ?>
</div>

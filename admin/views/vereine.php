<?php
/**
 * Vereine-Verwaltung Template
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/admin/views
 */

if (!defined('ABSPATH')) exit;

// Setup für Page-Header Partial
$page_title = __('Vereine', 'dienstplan-verwaltung');
$page_icon = 'dashicons-flag';
$page_class = 'header-vereine';
$nav_items = [
    [
        'label' => __('Dashboard', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan'),
        'icon' => 'dashicons-dashboard',
    ],
    [
        'label' => __('Veranstaltungen', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-veranstaltungen'),
        'icon' => 'dashicons-calendar-alt',
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
    
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/vereine-header.php'; ?>
    
    <?php if (!empty($vereine)): ?>
        <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/vereine-table.php'; ?>
    <?php else: ?>
        <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/vereine-empty.php'; ?>
    <?php endif; ?>
    
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/vereine-modal.php'; ?>
</div>

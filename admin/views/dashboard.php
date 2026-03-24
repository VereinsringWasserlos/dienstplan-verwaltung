<?php
/**
 * Dashboard-Template
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/admin/views
 */

if (!defined('ABSPATH')) exit;

// Setup für Page-Header Partial
$page_title = __('Dienstplan Verwaltung', 'dienstplan-verwaltung');
$page_icon = 'dashicons-admin-generic';
$page_class = 'header-dashboard';
$nav_items = [];
$page_meta_badges = array(
    array(
        'label' => 'Version ' . DIENSTPLAN_VERSION,
        'tone' => 'neutral',
    ),
);

$is_admin_user = current_user_can('manage_options')
    || Dienstplan_Roles::can_manage_settings()
    || Dienstplan_Roles::can_manage_users()
    || Dienstplan_Roles::can_manage_events()
    || Dienstplan_Roles::can_manage_clubs();
$is_hauptadmin = current_user_can('manage_options') || current_user_can(Dienstplan_Roles::CAP_MANAGE_SETTINGS);
?>

<div class="wrap dienstplan-admin-container">
    
    <!-- Moderner Header -->
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/page-header.php'; ?>
    
    <!-- Verwaltung -->
    <?php if (Dienstplan_Roles::can_manage_clubs() || Dienstplan_Roles::can_manage_events() || current_user_can('manage_options')): ?>
    <h2 class="dashboard-section-heading">
        <span class="dashicons dashicons-admin-settings"></span>
        <?php _e('Verwaltung', 'dienstplan-verwaltung'); ?>
    </h2>
    <div class="dashboard-compact-grid">
        
        <!-- Vereine -->
        <?php if (Dienstplan_Roles::can_manage_clubs() || current_user_can('manage_options')): ?>
        <a href="<?php echo admin_url('admin.php?page=dienstplan-vereine'); ?>" class="dashboard-admin-card card-vereine">
            <div class="dashboard-admin-card-header">
                <span class="dashicons dashicons-flag dashboard-admin-card-icon"></span>
                <h3 class="dashboard-admin-card-title"><?php _e('Vereine', 'dienstplan-verwaltung'); ?></h3>
            </div>
            <p class="dashboard-admin-card-description"><?php _e('Vereine verwalten', 'dienstplan-verwaltung'); ?></p>
        </a>
        <?php endif; ?>
        
        <!-- Veranstaltungen -->
        <?php if (Dienstplan_Roles::can_manage_events() || current_user_can('manage_options')): ?>
        <a href="<?php echo admin_url('admin.php?page=dienstplan-veranstaltungen'); ?>" class="dashboard-admin-card card-veranstaltungen">
            <div class="dashboard-admin-card-header">
                <span class="dashicons dashicons-calendar-alt dashboard-admin-card-icon"></span>
                <h3 class="dashboard-admin-card-title"><?php _e('Veranstaltungen', 'dienstplan-verwaltung'); ?></h3>
            </div>
            <p class="dashboard-admin-card-description"><?php _e('Events planen und organisieren', 'dienstplan-verwaltung'); ?></p>
        </a>
        <?php endif; ?>
        
        <!-- Dienste -->
        <a href="<?php echo admin_url('admin.php?page=dienstplan-dienste'); ?>" class="dashboard-admin-card card-dienste">
            <div class="dashboard-admin-card-header">
                <span class="dashicons dashicons-clipboard dashboard-admin-card-icon"></span>
                <h3 class="dashboard-admin-card-title"><?php _e('Dienste', 'dienstplan-verwaltung'); ?></h3>
            </div>
            <p class="dashboard-admin-card-description"><?php _e('Dienste erstellen und zuweisen', 'dienstplan-verwaltung'); ?></p>
        </a>

        <!-- Bereiche & Tätigkeiten -->
        <a href="<?php echo admin_url('admin.php?page=dienstplan-bereiche'); ?>" class="dashboard-admin-card card-bereiche">
            <div class="dashboard-admin-card-header">
                <span class="dashicons dashicons-category dashboard-admin-card-icon"></span>
                <h3 class="dashboard-admin-card-title"><?php _e('Arbeitsbereiche', 'dienstplan-verwaltung'); ?></h3>
            </div>
            <p class="dashboard-admin-card-description"><?php _e('Arbeitsbereiche und Aufgaben verwalten', 'dienstplan-verwaltung'); ?></p>
        </a>
        
        <!-- Mitarbeiter -->
        <a href="<?php echo admin_url('admin.php?page=dienstplan-mitarbeiter'); ?>" class="dashboard-admin-card card-mitarbeiter">
            <div class="dashboard-admin-card-header">
                <span class="dashicons dashicons-groups dashboard-admin-card-icon"></span>
                <h3 class="dashboard-admin-card-title"><?php _e('Crew', 'dienstplan-verwaltung'); ?></h3>
            </div>
            <p class="dashboard-admin-card-description"><?php _e('Crew-Mitglieder verwalten', 'dienstplan-verwaltung'); ?></p>
        </a>
        
        <!-- Timeline / Dienst-Übersicht -->
        <a href="<?php echo admin_url('admin.php?page=dienstplan-overview'); ?>" class="dashboard-admin-card card-timeline">
            <div class="dashboard-admin-card-header">
                <span class="dashicons dashicons-grid-view dashboard-admin-card-icon"></span>
                <h3 class="dashboard-admin-card-title"><?php _e('Timeline', 'dienstplan-verwaltung'); ?></h3>
            </div>
            <p class="dashboard-admin-card-description"><?php _e('Dienst-Übersicht als Timeline', 'dienstplan-verwaltung'); ?></p>
        </a>
        
    </div>
    <?php endif; ?>
    
    <!-- Administration -->
    <h2 class="dashboard-section-heading">
        <span class="dashicons dashicons-admin-generic"></span>
        <?php _e('Administration', 'dienstplan-verwaltung'); ?>
    </h2>
    <div class="dashboard-compact-grid">
        
        <!-- Dokumentation -->
        <a href="<?php echo admin_url('admin.php?page=dienstplan-dokumentation'); ?>" class="dashboard-admin-card card-documentation">
            <div class="dashboard-admin-card-header">
                <span class="dashicons dashicons-book dashboard-admin-card-icon"></span>
                <h3 class="dashboard-admin-card-title"><?php _e('Dokumentation', 'dienstplan-verwaltung'); ?></h3>
            </div>
            <p class="dashboard-admin-card-description"><?php _e('Anleitungen, Handbücher & technische Dokumentation', 'dienstplan-verwaltung'); ?></p>
        </a>

        <?php if (Dienstplan_Roles::can_manage_users() || current_user_can('manage_options')): ?>
        <a href="<?php echo admin_url('admin.php?page=dienstplan-benutzer'); ?>" class="dashboard-admin-card card-mitarbeiter">
            <div class="dashboard-admin-card-header">
                <span class="dashicons dashicons-shield dashboard-admin-card-icon"></span>
                <h3 class="dashboard-admin-card-title"><?php _e('Admin-Benutzer', 'dienstplan-verwaltung'); ?></h3>
            </div>
            <p class="dashboard-admin-card-description"><?php _e('Admins in eigener Seite verwalten', 'dienstplan-verwaltung'); ?></p>
        </a>
        <?php endif; ?>

        <!-- Event-Statistik (nur Admins) -->
        <?php if ($is_admin_user): ?>
        <a href="<?php echo admin_url('admin.php?page=dienstplan-statistik'); ?>" class="dashboard-admin-card card-timeline">
            <div class="dashboard-admin-card-header">
                <span class="dashicons dashicons-chart-line dashboard-admin-card-icon"></span>
                <h3 class="dashboard-admin-card-title"><?php _e('Event-Statistik', 'dienstplan-verwaltung'); ?></h3>
            </div>
            <p class="dashboard-admin-card-description"><?php _e('Qualität und Verteilung pro Veranstaltung auswerten', 'dienstplan-verwaltung'); ?></p>
        </a>
        <?php endif; ?>
        
        <!-- Import/Export -->
        <a href="<?php echo admin_url('admin.php?page=dienstplan-import'); ?>" class="dashboard-admin-card card-import">
            <div class="dashboard-admin-card-header">
                <span class="dashicons dashicons-migrate dashboard-admin-card-icon"></span>
                <h3 class="dashboard-admin-card-title"><?php _e('Import/Export', 'dienstplan-verwaltung'); ?></h3>
            </div>
            <p class="dashboard-admin-card-description"><?php _e('CSV-Daten importieren und exportieren', 'dienstplan-verwaltung'); ?></p>
        </a>

        <!-- Updates -->
        <?php if (current_user_can('manage_options')): ?>
        <a href="<?php echo admin_url('admin.php?page=dienstplan-updates'); ?>" class="dashboard-admin-card card-updates">
            <div class="dashboard-admin-card-header">
                <span class="dashicons dashicons-update dashboard-admin-card-icon"></span>
                <h3 class="dashboard-admin-card-title"><?php _e('Updates', 'dienstplan-verwaltung'); ?></h3>
            </div>
            <p class="dashboard-admin-card-description"><?php _e('Plugin-Updates & Git-Verwaltung', 'dienstplan-verwaltung'); ?></p>
        </a>
        <?php endif; ?>

        <!-- Debug -->
        <a href="<?php echo admin_url('admin.php?page=dienstplan-debug'); ?>" class="dashboard-admin-card card-debug">
            <div class="dashboard-admin-card-header">
                <span class="dashicons dashicons-admin-tools dashboard-admin-card-icon"></span>
                <h3 class="dashboard-admin-card-title"><?php _e('Debug & Wartung', 'dienstplan-verwaltung'); ?></h3>
            </div>
            <p class="dashboard-admin-card-description"><?php _e('Tabellen leeren, Statistiken & Wartung', 'dienstplan-verwaltung'); ?></p>
        </a>
        
    </div>

    <?php if ($is_hauptadmin): ?>
    <h2 class="dashboard-section-heading">
        <span class="dashicons dashicons-admin-home"></span>
        <?php _e('Frontend-Portal', 'dienstplan-verwaltung'); ?>
    </h2>
    <div class="dashboard-compact-grid">
        <a href="<?php echo admin_url('admin.php?page=dienstplan-portal'); ?>" class="dashboard-admin-card card-portal">
            <div class="dashboard-admin-card-header">
                <span class="dashicons dashicons-admin-home dashboard-admin-card-icon"></span>
                <h3 class="dashboard-admin-card-title"><?php _e('Frontend Portal', 'dienstplan-verwaltung'); ?></h3>
            </div>
            <p class="dashboard-admin-card-description"><?php _e('Portal-Seite verwalten, erstellen und bearbeiten', 'dienstplan-verwaltung'); ?></p>
        </a>
    </div>
    <?php endif; ?>
</div>

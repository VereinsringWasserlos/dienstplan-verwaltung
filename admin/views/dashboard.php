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
?>

<div class="wrap dienstplan-admin-container">
    
    <!-- Moderner Header -->
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/page-header.php'; ?>
    
    <?php if (Dienstplan_Roles::can_manage_clubs() || Dienstplan_Roles::can_manage_events() || current_user_can('manage_options')): ?>
    <div class="dashboard-main-grid">
        
        <!-- Vereine -->
        <?php if (Dienstplan_Roles::can_manage_clubs() || current_user_can('manage_options')): ?>
        <a href="<?php echo admin_url('admin.php?page=dienstplan-vereine'); ?>" class="dashboard-nav-card card-vereine">
            <div>
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon">
                        <span class="dashicons dashicons-flag"></span>
                    </div>
                    <h2 class="dashboard-card-title"><?php _e('Vereine', 'dienstplan-verwaltung'); ?></h2>
                </div>
                <p class="dashboard-card-description"><?php _e('Vereine verwalten', 'dienstplan-verwaltung'); ?></p>
                <div class="dashboard-card-cta">
                    <?php _e('Zur Verwaltung', 'dienstplan-verwaltung'); ?>
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </div>
            </div>
        </a>
        <?php endif; ?>
        
        <!-- Veranstaltungen -->
        <?php if (Dienstplan_Roles::can_manage_events() || current_user_can('manage_options')): ?>
        <a href="<?php echo admin_url('admin.php?page=dienstplan-veranstaltungen'); ?>" class="dashboard-nav-card card-veranstaltungen">
            <div>
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    <h2 class="dashboard-card-title"><?php _e('Veranstaltungen', 'dienstplan-verwaltung'); ?></h2>
                </div>
                <p class="dashboard-card-description"><?php _e('Events planen und organisieren', 'dienstplan-verwaltung'); ?></p>
                <div class="dashboard-card-cta">
                    <?php _e('Zur Verwaltung', 'dienstplan-verwaltung'); ?>
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </div>
            </div>
        </a>
        <?php endif; ?>
        
        <!-- Dienste -->
        <a href="<?php echo admin_url('admin.php?page=dienstplan-dienste'); ?>" class="dashboard-nav-card card-dienste">
            <div>
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon">
                        <span class="dashicons dashicons-clipboard"></span>
                    </div>
                    <h2 class="dashboard-card-title"><?php _e('Dienste', 'dienstplan-verwaltung'); ?></h2>
                </div>
                <p class="dashboard-card-description"><?php _e('Dienste erstellen und zuweisen', 'dienstplan-verwaltung'); ?></p>
                <div class="dashboard-card-cta">
                    <?php _e('Zur Verwaltung', 'dienstplan-verwaltung'); ?>
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </div>
            </div>
        </a>

        <!-- Bereiche & Tätigkeiten -->
        <a href="<?php echo admin_url('admin.php?page=dienstplan-bereiche'); ?>" class="dashboard-nav-card card-bereiche">
            <div>
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon">
                        <span class="dashicons dashicons-category"></span>
                    </div>
                    <h2 class="dashboard-card-title"><?php _e('Arbeitsbereiche', 'dienstplan-verwaltung'); ?></h2>
                </div>
                <p class="dashboard-card-description"><?php _e('Arbeitsbereiche und Aufgaben verwalten', 'dienstplan-verwaltung'); ?></p>
                <div class="dashboard-card-cta">
                    <?php _e('Zur Verwaltung', 'dienstplan-verwaltung'); ?>
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </div>
            </div>
        </a>
        
        <!-- Mitarbeiter -->
        <a href="<?php echo admin_url('admin.php?page=dienstplan-mitarbeiter'); ?>" class="dashboard-nav-card card-mitarbeiter">
            <div>
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <h2 class="dashboard-card-title"><?php _e('Crew', 'dienstplan-verwaltung'); ?></h2>
                </div>
                <p class="dashboard-card-description"><?php _e('Crew-Mitglieder verwalten', 'dienstplan-verwaltung'); ?></p>
                <div class="dashboard-card-cta">
                    <?php _e('Zur Verwaltung', 'dienstplan-verwaltung'); ?>
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </div>
            </div>
        </a>
        <?php endif; ?>
        
    </div>
    
    <!-- Views -->
    <h2 class="dashboard-section-heading">
        <span class="dashicons dashicons-visibility"></span>
        <?php _e('Views', 'dienstplan-verwaltung'); ?>
    </h2>
    <div class="dashboard-main-grid">
        
        <!-- Timeline / Dienst-Übersicht -->
        <a href="<?php echo admin_url('admin.php?page=dienstplan-overview'); ?>" class="dashboard-nav-card card-timeline">
            <div>
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon">
                        <span class="dashicons dashicons-grid-view"></span>
                    </div>
                    <h2 class="dashboard-card-title"><?php _e('Timeline', 'dienstplan-verwaltung'); ?></h2>
                </div>
                <p class="dashboard-card-description"><?php _e('Dienst-Übersicht als Timeline', 'dienstplan-verwaltung'); ?></p>
                <div class="dashboard-card-cta">
                    <?php _e('Zur Übersicht', 'dienstplan-verwaltung'); ?>
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </div>
            </div>
        </a>
        
    </div>
    
    <!-- Administration (klein) -->
    <h2 class="dashboard-section-heading">
        <span class="dashicons dashicons-admin-generic"></span>
        <?php _e('Administration', 'dienstplan-verwaltung'); ?>
    </h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- Dokumentation -->
        <a href="<?php echo admin_url('admin.php?page=dienstplan-dokumentation'); ?>" class="dashboard-admin-card card-documentation">
            <div class="dashboard-admin-card-header">
                <span class="dashicons dashicons-book dashboard-admin-card-icon"></span>
                <h3 class="dashboard-admin-card-title"><?php _e('Dokumentation', 'dienstplan-verwaltung'); ?></h3>
            </div>
            <p class="dashboard-admin-card-description"><?php _e('Anleitungen, Handbücher & technische Dokumentation', 'dienstplan-verwaltung'); ?></p>
        </a>
        
        <!-- Import/Export -->
        <a href="<?php echo admin_url('admin.php?page=dienstplan-import-export'); ?>" class="dashboard-admin-card card-import">
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
</div>

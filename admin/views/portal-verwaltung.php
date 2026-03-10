<?php
/**
 * Portal-Verwaltung View
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/admin/views
 */

if (!defined('ABSPATH')) {
    exit;
}

// Portal-Seiten-Status abrufen
$portal_page_id = get_option('dienstplan_portal_page_id', 0);
$portal_page = $portal_page_id ? get_post($portal_page_id) : null;
$portal_exists = $portal_page && $portal_page->post_status !== 'trash';

// Setup für Page-Header Partial
$page_title = __('Frontend Portal Verwaltung', 'dienstplan-verwaltung');
$page_icon = 'dashicons-admin-home';
$page_class = 'header-portal';
$nav_items = [];
?>

<div class="wrap dienstplan-admin-container">
    
    <!-- Moderner Header -->
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/page-header.php'; ?>
    
    <div class="dp-content-grid">
        
        <!-- Haupt-Content -->
        <div class="dp-main-content">
            
            <?php if ($portal_exists): ?>
            
            <!-- Portal-Seite existiert -->
            <div class="dp-card" id="portal-page-card">
                <div class="dp-card-header">
                    <h2 class="dp-card-title">
                        <span class="dashicons dashicons-admin-home"></span>
                        <?php _e('Portal-Seite', 'dienstplan-verwaltung'); ?>
                    </h2>
                </div>
                <div class="dp-card-body">
                    
                    <div class="portal-info-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        
                        <div class="portal-info-item">
                            <label class="portal-info-label"><?php _e('Seitentitel', 'dienstplan-verwaltung'); ?></label>
                            <div class="portal-info-value">
                                <strong><?php echo esc_html($portal_page->post_title); ?></strong>
                            </div>
                        </div>
                        
                        <div class="portal-info-item">
                            <label class="portal-info-label"><?php _e('Status', 'dienstplan-verwaltung'); ?></label>
                            <div class="portal-info-value">
                                <span class="portal-status-badge status-<?php echo esc_attr($portal_page->post_status); ?>">
                                    <?php 
                                    $status_labels = array(
                                        'publish' => __('Veröffentlicht', 'dienstplan-verwaltung'),
                                        'draft' => __('Entwurf', 'dienstplan-verwaltung'),
                                        'pending' => __('Ausstehend', 'dienstplan-verwaltung'),
                                        'private' => __('Privat', 'dienstplan-verwaltung')
                                    );
                                    echo isset($status_labels[$portal_page->post_status]) 
                                        ? esc_html($status_labels[$portal_page->post_status]) 
                                        : esc_html(ucfirst($portal_page->post_status));
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="portal-info-item">
                            <label class="portal-info-label"><?php _e('URL', 'dienstplan-verwaltung'); ?></label>
                            <div class="portal-info-value">
                                <code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.875rem;">
                                    <?php echo esc_html(get_permalink($portal_page_id)); ?>
                                </code>
                            </div>
                        </div>
                        
                        <div class="portal-info-item">
                            <label class="portal-info-label"><?php _e('Erstellt am', 'dienstplan-verwaltung'); ?></label>
                            <div class="portal-info-value">
                                <?php echo esc_html(get_the_date('', $portal_page_id)); ?>
                                <?php _e('um', 'dienstplan-verwaltung'); ?>
                                <?php echo esc_html(get_the_time('', $portal_page_id)); ?>
                            </div>
                        </div>
                        
                    </div>
                    
                    <div class="portal-actions" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                        
                        <a href="<?php echo get_permalink($portal_page_id); ?>" 
                           class="button button-primary button-large" 
                           target="_blank">
                            <span class="dashicons dashicons-visibility" style="margin-top: 4px;"></span>
                            <?php _e('Seite ansehen', 'dienstplan-verwaltung'); ?>
                        </a>
                        
                        <a href="<?php echo admin_url('post.php?post=' . $portal_page_id . '&action=edit'); ?>" 
                           class="button button-secondary button-large">
                            <span class="dashicons dashicons-edit" style="margin-top: 4px;"></span>
                            <?php _e('In WordPress bearbeiten', 'dienstplan-verwaltung'); ?>
                        </a>
                        
                        <button type="button" 
                                class="button button-secondary button-large" 
                                onclick="window.location.reload()">
                            <span class="dashicons dashicons-update" style="margin-top: 4px;"></span>
                            <?php _e('Status aktualisieren', 'dienstplan-verwaltung'); ?>
                        </button>
                        
                        <button type="button" 
                                class="button button-link-delete button-large" 
                                onclick="deletePortalPage()"
                                style="margin-left: auto;">
                            <span class="dashicons dashicons-trash" style="margin-top: 4px;"></span>
                            <?php _e('Seite permanent löschen', 'dienstplan-verwaltung'); ?>
                        </button>
                        
                    </div>
                    
                </div>
            </div>
            
            <!-- Shortcode Info -->
            <div class="dp-card" style="margin-top: 1.5rem;">
                <div class="dp-card-header">
                    <h2 class="dp-card-title">
                        <span class="dashicons dashicons-editor-code"></span>
                        <?php _e('Shortcode-Information', 'dienstplan-verwaltung'); ?>
                    </h2>
                </div>
                <div class="dp-card-body">
                    <p><?php _e('Die Portal-Seite verwendet den Shortcode:', 'dienstplan-verwaltung'); ?></p>
                    <code style="display: block; background: #f1f5f9; padding: 1rem; border-radius: 4px; margin: 1rem 0; font-size: 1rem;">
                        [dienstplan_hub]
                    </code>
                    <p class="description">
                        <?php _e('Dieser Shortcode zeigt das komplette Frontend-Portal mit Login, Registrierung und aktuellen Veranstaltungen.', 'dienstplan-verwaltung'); ?>
                    </p>
                </div>
            </div>
            
            <?php else: ?>
            
            <!-- Keine Portal-Seite vorhanden -->
            <div class="dp-card" id="portal-page-card">
                <div class="dp-card-header">
                    <h2 class="dp-card-title">
                        <span class="dashicons dashicons-admin-home"></span>
                        <?php _e('Portal-Seite erstellen', 'dienstplan-verwaltung'); ?>
                    </h2>
                </div>
                <div class="dp-card-body">
                    
                    <p style="font-size: 1.1rem; line-height: 1.6; margin-bottom: 1.5rem;">
                        <?php _e('Erstelle eine zentrale Einstiegsseite für deine Benutzer mit Login, Registrierung und einer Übersicht der aktuellen Veranstaltungen.', 'dienstplan-verwaltung'); ?>
                    </p>
                    
                    <div class="portal-features" style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                        <h3 style="margin-top: 0;"><?php _e('Funktionen des Frontend-Portals:', 'dienstplan-verwaltung'); ?></h3>
                        <ul style="list-style: none; padding-left: 0; margin: 0;">
                            <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                                <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                                <?php _e('Login-Bereich für bestehende Benutzer', 'dienstplan-verwaltung'); ?>
                            </li>
                            <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                                <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                                <?php _e('Registrierungs-Formular für neue Crew-Mitglieder', 'dienstplan-verwaltung'); ?>
                            </li>
                            <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                                <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                                <?php _e('Übersicht der aktuellen Veranstaltungen', 'dienstplan-verwaltung'); ?>
                            </li>
                            <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                                <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                                <?php _e('Quick-Links zu "Meine Dienste" und Profil', 'dienstplan-verwaltung'); ?>
                            </li>
                            <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                                <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                                <?php _e('Responsive Design für alle Geräte', 'dienstplan-verwaltung'); ?>
                            </li>
                        </ul>
                    </div>
                    
                    <button type="button" 
                            id="portal-create-button"
                            class="button button-primary button-hero" 
                            onclick="createPortalPageFromDashboard(this)"
                            style="font-size: 1.2rem; padding: 1rem 2rem;">
                        <span class="dashicons dashicons-plus-alt" style="margin-top: 4px; font-size: 1.5rem;"></span>
                        <?php _e('Portal-Seite jetzt erstellen', 'dienstplan-verwaltung'); ?>
                    </button>
                    
                </div>
            </div>
            
            <?php endif; ?>
            
        </div>
        
        <!-- Sidebar -->
        <div class="dp-sidebar">
            
            <!-- Hilfe-Card -->
            <div class="dp-card">
                <div class="dp-card-header">
                    <h3 class="dp-card-title">
                        <span class="dashicons dashicons-info"></span>
                        <?php _e('Hilfe', 'dienstplan-verwaltung'); ?>
                    </h3>
                </div>
                <div class="dp-card-body">
                    <p class="description">
                        <?php _e('Das Frontend-Portal dient als zentrale Anlaufstelle für deine Crew-Mitglieder.', 'dienstplan-verwaltung'); ?>
                    </p>
                    <hr style="margin: 1rem 0;">
                    <p class="description">
                        <strong><?php _e('Tipp:', 'dienstplan-verwaltung'); ?></strong>
                        <?php _e('Du kannst die Seite nach der Erstellung im WordPress-Editor anpassen und z.B. mit Elementor erweitern.', 'dienstplan-verwaltung'); ?>
                    </p>
                </div>
            </div>
            
            <!-- Shortcodes-Card -->
            <div class="dp-card" style="margin-top: 1rem;">
                <div class="dp-card-header">
                    <h3 class="dp-card-title">
                        <span class="dashicons dashicons-shortcode"></span>
                        <?php _e('Weitere Shortcodes', 'dienstplan-verwaltung'); ?>
                    </h3>
                </div>
                <div class="dp-card-body">
                    <div class="shortcode-item" style="margin-bottom: 1rem;">
                        <code style="display: block; background: #f1f5f9; padding: 0.5rem; border-radius: 4px; margin-bottom: 0.25rem;">
                            [meine_dienste]
                        </code>
                        <p class="description" style="margin: 0; font-size: 0.875rem;">
                            <?php _e('Zeigt persönliche Dienst-Übersicht', 'dienstplan-verwaltung'); ?>
                        </p>
                    </div>
                    <div class="shortcode-item">
                        <code style="display: block; background: #f1f5f9; padding: 0.5rem; border-radius: 4px; margin-bottom: 0.25rem;">
                            [dienstplan_veranstaltungen]
                        </code>
                        <p class="description" style="margin: 0; font-size: 0.875rem;">
                            <?php _e('Zeigt alle Veranstaltungen', 'dienstplan-verwaltung'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
        </div>
        
    </div>
    
</div>

<style>
.portal-info-label {
    display: block;
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.portal-info-value {
    font-size: 1rem;
    color: #1e293b;
}

.portal-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.portal-actions .button .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}
</style>

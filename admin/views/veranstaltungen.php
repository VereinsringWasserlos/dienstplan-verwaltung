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
    
    <!-- Neuer Kontakt Modal (für Verantwortliche) -->
    <div id="new-contact-modal" class="dp-modal" style="display: none;">
        <div class="dp-modal-content" style="max-width: 500px;">
            <div class="dp-modal-header">
                <h2><?php _e('Neuer Kontakt anlegen', 'dienstplan-verwaltung'); ?></h2>
                <button class="dp-modal-close" onclick="closeNewContactModal()">&times;</button>
            </div>
            <div class="dp-modal-body">
                <form id="new-contact-form" onsubmit="return false;">
                    <table class="form-table">
                        <tr>
                            <th><label for="nc_name"><?php _e('Name', 'dienstplan-verwaltung'); ?> *</label></th>
                            <td>
                                <input type="text" id="nc_name" name="nc_name" class="regular-text" required>
                                <p class="description"><?php _e('Vor- und Nachname', 'dienstplan-verwaltung'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="nc_email"><?php _e('E-Mail', 'dienstplan-verwaltung'); ?> *</label></th>
                            <td>
                                <input type="email" id="nc_email" name="nc_email" class="regular-text" required>
                                <div id="nc-email-check-result" style="margin-top: 0.5rem;"></div>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="nc_role"><?php _e('Rolle', 'dienstplan-verwaltung'); ?></label></th>
                            <td>
                                <select id="nc_role" name="nc_role" class="regular-text">
                                    <option value=""><?php _e('Keine Rolle', 'dienstplan-verwaltung'); ?></option>
                                    <option value="<?php echo Dienstplan_Roles::ROLE_CLUB_ADMIN; ?>">
                                        <?php _e('Vereins-Admin', 'dienstplan-verwaltung'); ?>
                                    </option>
                                    <option value="<?php echo Dienstplan_Roles::ROLE_EVENT_ADMIN; ?>">
                                        <?php _e('Veranstaltungs-Admin', 'dienstplan-verwaltung'); ?>
                                    </option>
                                    <option value="<?php echo Dienstplan_Roles::ROLE_GENERAL_ADMIN; ?>">
                                        <?php _e('Allgemeiner Admin', 'dienstplan-verwaltung'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php _e('Optionale Dienstplan-Rolle für diesen Benutzer', 'dienstplan-verwaltung'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 1rem; margin-top: 1rem; border-radius: 4px;">
                        <p style="margin: 0; font-size: 0.9rem;">
                            <span class="dashicons dashicons-email" style="color: #856404;"></span>
                            <strong><?php _e('Automatischer Versand:', 'dienstplan-verwaltung'); ?></strong><br>
                            <?php _e('Der neue Benutzer erhält eine E-Mail mit einem Link zum Setzen des Passworts.', 'dienstplan-verwaltung'); ?>
                        </p>
                    </div>
                </form>
            </div>
            <div class="dp-modal-footer">
                <button type="button" class="button" onclick="closeNewContactModal()"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
                <button type="button" class="button button-primary" onclick="saveNewContact()">
                    <span class="dashicons dashicons-plus" style="margin-top: 3px;"></span>
                    <?php _e('Kontakt anlegen & einladen', 'dienstplan-verwaltung'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

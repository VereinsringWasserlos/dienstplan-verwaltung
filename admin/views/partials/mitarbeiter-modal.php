<?php
/**
 * Mitarbeiter Modal - Erstellen/Bearbeiten
 */
if (!defined('ABSPATH')) exit;
?>

<!-- Mitarbeiter Modal -->
<div id="mitarbeiter-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content" style="max-width: 600px;">
        <div class="dp-modal-header">
            <h2 id="mitarbeiter-modal-title"><?php _e('Neuer Mitarbeiter', 'dienstplan-verwaltung'); ?></h2>
            <button type="button" class="dp-modal-close" onclick="closeMitarbeiterModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="mitarbeiter-form">
                <input type="hidden" id="mitarbeiter_id" name="mitarbeiter_id">
                
                <table class="form-table">
                    <tr>
                        <th><label for="ma_vorname"><?php _e('Vorname', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="text" id="ma_vorname" name="vorname" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="ma_nachname"><?php _e('Nachname', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="text" id="ma_nachname" name="nachname" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="ma_email"><?php _e('E-Mail', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="email" id="ma_email" name="email" class="regular-text" required>
                            <p class="description"><?php _e('Die E-Mail-Adresse des Mitarbeiters.', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="ma_telefon"><?php _e('Telefon', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <input type="tel" id="ma_telefon" name="telefon" class="regular-text">
                        </td>
                    </tr>
                    
                    <tr id="portal-access-row" style="display: none;">
                        <th><label for="ma_portal_access"><?php _e('Portal-Zugriff', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <div id="portal-status-display" style="display: none; margin-bottom: 1rem;">
                                <div class="portal-status-indicator">
                                    <span class="portal-status-badge status-active">
                                        <span class="dashicons dashicons-yes"></span>
                                        <?php _e('Portal-Zugang aktiv', 'dienstplan-verwaltung'); ?>
                                    </span>
                                    <p class="description" style="margin-top: 0.5rem;">
                                        <?php _e('Dieser Mitarbeiter hat Zugriff auf das Frontend-Portal.', 'dienstplan-verwaltung'); ?>
                                    </p>
                                </div>
                                <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                                    <button type="button" class="button" onclick="resendLoginCredentials()">
                                        <span class="dashicons dashicons-email" style="margin-top: 4px;"></span>
                                        <?php _e('Login-Daten erneut senden', 'dienstplan-verwaltung'); ?>
                                    </button>
                                    <button type="button" class="button button-link-delete" onclick="deactivatePortalAccess()">
                                        <span class="dashicons dashicons-lock" style="margin-top: 4px;"></span>
                                        <?php _e('Zugriff deaktivieren', 'dienstplan-verwaltung'); ?>
                                    </button>
                                </div>
                            </div>
                            
                            <div id="portal-activate-display" style="display: none;">
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" id="ma_portal_access" name="portal_access" value="1">
                                    <span><?php _e('Portal-Zugriff für diesen Mitarbeiter aktivieren', 'dienstplan-verwaltung'); ?></span>
                                </label>
                                <p class="description" style="margin-top: 0.5rem;">
                                    <?php _e('Erstellt einen WordPress-Benutzer und sendet Login-Daten per E-Mail.', 'dienstplan-verwaltung'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="ma_notizen"><?php _e('Notizen', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <textarea id="ma_notizen" name="notizen" class="large-text" rows="3"></textarea>
                            <p class="description"><?php _e('Interne Notizen zum Mitarbeiter.', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeMitarbeiterModal()"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
            <button type="button" class="button button-primary" onclick="saveMitarbeiter()"><?php _e('Speichern', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>

<!-- JavaScript in assets/js/dp-mitarbeiter-modal.js -->

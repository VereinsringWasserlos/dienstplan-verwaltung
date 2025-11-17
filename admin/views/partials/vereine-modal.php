<?php
/**
 * Vereine Modal Partial
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/admin/views/partials
 */

if (!defined('ABSPATH')) exit;
?>

<!-- Verein Modal -->
<div id="verein-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content">
        <div class="dp-modal-header">
            <h2 id="modal-title"><?php _e('Neuer Verein', 'dienstplan-verwaltung'); ?></h2>
            <button class="dp-modal-close" onclick="closeVereinModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="verein-form">
                <input type="hidden" id="verein_id" name="verein_id" value="">
                
                <table class="form-table">
                    <tr>
                        <th><label for="name"><?php _e('Name', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="text" id="name" name="name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="kuerzel"><?php _e('Kürzel', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="text" id="kuerzel" name="kuerzel" class="regular-text" maxlength="10" required>
                            <p class="description"><?php _e('Max. 10 Zeichen, z.B. "FEG", "ABC"', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="beschreibung"><?php _e('Beschreibung', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <textarea id="beschreibung" name="beschreibung" class="large-text" rows="3"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="logo_id"><?php _e('Logo', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <div class="dp-logo-upload">
                                <div id="logo-preview" style="margin-bottom: 0.5rem;">
                                    <!-- Logo preview will be shown here -->
                                </div>
                                <input type="hidden" id="logo_id" name="logo_id" value="">
                                <button type="button" class="button" id="upload-logo-btn">
                                    <span class="dashicons dashicons-format-image"></span>
                                    <?php _e('Logo auswählen', 'dienstplan-verwaltung'); ?>
                                </button>
                                <button type="button" class="button" id="remove-logo-btn" style="display: none;">
                                    <span class="dashicons dashicons-no"></span>
                                    <?php _e('Logo entfernen', 'dienstplan-verwaltung'); ?>
                                </button>
                                <p class="description"><?php _e('Empfohlene Größe: 200x200 Pixel', 'dienstplan-verwaltung'); ?></p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="verantwortliche"><?php _e('Verantwortliche', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                                <div id="verantwortliche-checkboxes" style="flex: 1; border: 1px solid #ddd; padding: 0.75rem; border-radius: 4px; max-height: 200px; overflow-y: auto; background: #fff;">
                                    <p style="color: #666; margin: 0;"><?php _e('Lädt...', 'dienstplan-verwaltung'); ?></p>
                                </div>
                                <button type="button" class="button button-secondary" onclick="openNewContactModal()" style="white-space: nowrap;">
                                    <span class="dashicons dashicons-plus-alt" style="margin-top: 3px;"></span> 
                                    <?php _e('Neuer Kontakt', 'dienstplan-verwaltung'); ?>
                                </button>
                            </div>
                            <p class="description">
                                <?php _e('Aktuell zugewiesene Verantwortliche. Über "Neuer Kontakt" können weitere Personen hinzugefügt werden.', 'dienstplan-verwaltung'); ?>
                            </p>
                            
                            <!-- Legacy Felder für Rückwärtskompatibilität (versteckt) -->
                            <input type="hidden" id="kontakt_name" name="kontakt_name">
                            <input type="hidden" id="kontakt_email" name="kontakt_email">
                        </td>
                    </tr>
                    <tr id="user-invite-row" style="display: none;">
                        <th></th>
                        <td>
                            <div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 1rem; border-radius: 4px;">
                                <p style="margin: 0 0 0.5rem 0;">
                                    <strong><span class="dashicons dashicons-admin-users"></span> <?php _e('WordPress-Benutzer', 'dienstplan-verwaltung'); ?></strong>
                                </p>
                                <label>
                                    <input type="checkbox" id="create_user" name="create_user" value="1">
                                    <?php _e('WordPress-Benutzer für diese E-Mail-Adresse erstellen', 'dienstplan-verwaltung'); ?>
                                </label>
                                <div id="user-role-select" style="margin-top: 0.5rem; display: none;">
                                    <label for="user_role" style="display: block; margin-bottom: 0.25rem;">
                                        <?php _e('Dienstplan-Rolle zuweisen:', 'dienstplan-verwaltung'); ?>
                                    </label>
                                    <select id="user_role" name="user_role" class="regular-text">
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
                                    <p class="description" style="margin-top: 0.25rem;">
                                        <?php _e('Der Benutzer erhält eine E-Mail mit einem Link zum Setzen des Passworts', 'dienstplan-verwaltung'); ?>
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="kontakt_telefon"><?php _e('Telefon', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <input type="text" id="kontakt_telefon" name="kontakt_telefon" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="aktiv"><?php _e('Status', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="aktiv" name="aktiv" value="1" checked>
                                <?php _e('Aktiv', 'dienstplan-verwaltung'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeVereinModal()"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
            <button type="button" class="button button-primary" onclick="saveVerein()"><?php _e('Speichern', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>

<!-- JavaScript wurde nach assets/js/dp-vereine-modal.js ausgelagert -->

<!-- Neuer Kontakt Modal -->
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

<!-- JavaScript wurde nach assets/js/dp-vereine-modal.js ausgelagert -->

<?php
/**
 * Veranstaltungen Modal
 */
if (!defined('ABSPATH')) exit;
?>

<div id="veranstaltung-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content" style="max-width: 1400px;">
        <div class="dp-modal-header">
            <h2 id="veranstaltung-modal-title"><?php _e('Neue Veranstaltung', 'dienstplan-verwaltung'); ?></h2>
            <button class="dp-modal-close" onclick="closeVeranstaltungModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="veranstaltung-form">
                <input type="hidden" id="veranstaltung_id" name="veranstaltung_id" value="">
                
                <table class="form-table">
                    <!-- Name -->
                    <tr>
                        <th><label for="v_name"><?php _e('Name', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="text" id="v_name" name="name" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <!-- Beschreibung -->
                    <tr>
                        <th><label for="v_beschreibung"><?php _e('Beschreibung', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <textarea id="v_beschreibung" name="beschreibung" class="large-text" rows="3"></textarea>
                        </td>
                    </tr>
                    
                    <!-- Typ -->
                    <tr>
                        <th><label for="v_typ"><?php _e('Typ', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <select id="v_typ" name="typ" onchange="toggleMehrtaegig()">
                                <option value="eintaegig"><?php _e('Eintägig', 'dienstplan-verwaltung'); ?></option>
                                <option value="mehrtaegig"><?php _e('Mehrtägig', 'dienstplan-verwaltung'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <!-- Status -->
                    <tr>
                        <th><label for="v_status"><?php _e('Status', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <select id="v_status" name="status">
                                <option value="geplant"><?php _e('Geplant', 'dienstplan-verwaltung'); ?></option>
                                <option value="aktiv"><?php _e('Aktiv', 'dienstplan-verwaltung'); ?></option>
                                <option value="abgeschlossen"><?php _e('Abgeschlossen', 'dienstplan-verwaltung'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <!-- Beteiligte Vereine -->
                    <tr>
                        <th><?php _e('Beteiligte Vereine', 'dienstplan-verwaltung'); ?></th>
                        <td>
                            <div style="max-height: 200px; overflow-y: auto; border: 2px solid var(--dp-gray-200); border-radius: var(--dp-radius); padding: 1rem; background: var(--dp-gray-50);">
                                <?php foreach ($vereine as $verein): ?>
                                    <label style="display: block; margin-bottom: 0.5rem; cursor: pointer;">
                                        <input type="checkbox" name="vereine[]" value="<?php echo $verein->id; ?>" class="verein-checkbox">
                                        <strong><?php echo esc_html($verein->name); ?></strong> 
                                        <span style="color: var(--dp-gray-500);">(<?php echo esc_html($verein->kuerzel); ?>)</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <p class="description"><?php _e('Wählen Sie alle Vereine aus, die an dieser Veranstaltung beteiligt sind', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                    
                    <!-- Verantwortliche -->
                    <tr>
                        <th><?php _e('Verantwortliche', 'dienstplan-verwaltung'); ?></th>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                                <div id="v_verantwortliche-checkboxes" style="flex: 1; border: 1px solid #ddd; padding: 0.75rem; border-radius: 4px; max-height: 200px; overflow-y: auto; background: #fff;">
                                    <p style="color: #666; margin: 0;"><?php _e('Lädt...', 'dienstplan-verwaltung'); ?></p>
                                </div>
                                <button type="button" class="button button-secondary" onclick="window.newContactSource='veranstaltung-checkboxes'; openNewContactModal();" style="white-space: nowrap;">
                                    <span class="dashicons dashicons-plus-alt" style="margin-top: 3px;"></span> 
                                    <?php _e('Neuer Kontakt', 'dienstplan-verwaltung'); ?>
                                </button>
                            </div>
                            <p class="description">
                                <?php _e('Wählen Sie Verantwortliche aus oder erstellen Sie neue Kontakte mit WordPress-Benutzer über "Neuer Kontakt".', 'dienstplan-verwaltung'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <hr style="margin: 2rem 0; border: none; border-top: 2px solid var(--dp-gray-200);">
                
                <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php _e('Tage & Zeiten der Veranstaltung', 'dienstplan-verwaltung'); ?>
                </h3>
                
                <div style="overflow-x: auto;">
                    <table id="tage-table" class="wp-list-table widefat fixed striped" style="margin-bottom: 1rem;">
                        <thead>
                            <tr>
                                <th style="width: 30px; padding: 6px 4px;">#</th>
                                <th style="width: 240px; padding: 6px;"><?php _e('Datum', 'dienstplan-verwaltung'); ?> *</th>
                                <th colspan="2" style="text-align: center; background: #f0f9ff; padding: 6px;"><?php _e('Veranstaltung', 'dienstplan-verwaltung'); ?></th>
                                <th colspan="2" style="text-align: center; background: #fef3c7; padding: 6px;"><?php _e('Dienstzeiten', 'dienstplan-verwaltung'); ?></th>
                                <th style="width: 160px; padding: 6px;"><?php _e('Notizen', 'dienstplan-verwaltung'); ?></th>
                                <th style="width: 45px; padding: 6px;"></th>
                            </tr>
                            <tr>
                                <th></th>
                                <th style="font-size: 0.85em; color: #666; font-weight: normal; padding: 2px 6px;">
                                    <?php _e('Tag + Wochentag', 'dienstplan-verwaltung'); ?>
                                </th>
                                <th style="background: #f0f9ff; width: 75px; padding: 2px 6px;"><?php _e('Von', 'dienstplan-verwaltung'); ?></th>
                                <th style="background: #f0f9ff; width: 75px; padding: 2px 6px;"><?php _e('Bis', 'dienstplan-verwaltung'); ?></th>
                                <th style="background: #fef3c7; width: 75px; padding: 2px 6px;"><?php _e('Von', 'dienstplan-verwaltung'); ?></th>
                                <th style="background: #fef3c7; width: 75px; padding: 2px 6px;"><?php _e('Bis', 'dienstplan-verwaltung'); ?></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="tage-tbody">
                            <!-- Wird dynamisch gefüllt -->
                        </tbody>
                    </table>
                </div>
                
                <button type="button" class="button" onclick="addTag()" style="margin-top: 0.5rem;">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Weiteren Tag hinzufügen', 'dienstplan-verwaltung'); ?>
                </button>
                
            </form>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeVeranstaltungModal()"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
            <button type="button" class="button button-primary" onclick="saveVeranstaltung()"><?php _e('Speichern', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>



<!-- JavaScript moved to assets/js/dp-veranstaltungen-modal.js -->

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.spin {
    animation: spin 1s linear infinite;
}
</style>


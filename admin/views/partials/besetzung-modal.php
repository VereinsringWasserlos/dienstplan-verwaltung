<?php
/**
 * Besetzung Modal - Verwaltung der Slot-Besetzungen für einen Dienst
 * 
 * Erwartet: $db (Dienstplan_Database Instanz aus parent scope)
 */
if (!defined('ABSPATH')) exit;

// Hole alle Mitarbeiter für die Auswahlliste (verwendet $db aus parent scope)
$all_mitarbeiter = $db->get_mitarbeiter();
?>

<!-- Besetzung Modal -->
<div id="besetzung-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content" style="max-width: 800px;">
        <div class="dp-modal-header">
            <h2 id="besetzung-modal-title"><?php _e('Besetzung verwalten', 'dienstplan-verwaltung'); ?></h2>
            <button type="button" class="dp-modal-close" onclick="closeBesetzungModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <input type="hidden" id="besetzung_dienst_id">
            
            <!-- Dienst Info -->
            <div id="besetzung-dienst-info" style="padding: 1rem; background: #f0f6fc; border-left: 4px solid #2271b1; margin-bottom: 1.5rem;">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; font-size: 0.9rem;">
                    <div>
                        <strong><?php _e('Tätigkeit:', 'dienstplan-verwaltung'); ?></strong>
                        <span id="info-taetigkeit"></span>
                    </div>
                    <div>
                        <strong><?php _e('Bereich:', 'dienstplan-verwaltung'); ?></strong>
                        <span id="info-bereich"></span>
                    </div>
                    <div>
                        <strong><?php _e('Dienstzeit:', 'dienstplan-verwaltung'); ?></strong>
                        <span id="info-zeit"></span>
                    </div>
                    <div>
                        <strong><?php _e('Verein:', 'dienstplan-verwaltung'); ?></strong>
                        <span id="info-verein"></span>
                    </div>
                </div>
            </div>
            
            <!-- Slots -->
            <div id="besetzung-slots-container">
                <p style="text-align: center; color: #666;">
                    <span class="spinner is-active" style="float: none; margin: 0;"></span>
                    <?php _e('Lade Slots...', 'dienstplan-verwaltung'); ?>
                </p>
            </div>
            
            <!-- Neuer Mitarbeiter Button -->
            <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 2px solid #e5e7eb;">
                <button type="button" class="button button-primary" onclick="openNeuerMitarbeiterForm()" style="width: 100%; height: 40px; font-size: 14px;">
                    <span class="dashicons dashicons-plus-alt" style="font-size: 18px; width: 18px; height: 18px; margin-top: 3px;"></span>
                    <?php _e('Neuen Mitarbeiter anlegen', 'dienstplan-verwaltung'); ?>
                </button>
            </div>
            
            <!-- Neuer Mitarbeiter Form (versteckt) -->
            <div id="neuer-mitarbeiter-form" style="display: none; margin-top: 1rem; padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px;">
                <h3 style="margin-top: 0; font-size: 1rem; color: #1e293b;">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php _e('Neuen Mitarbeiter anlegen', 'dienstplan-verwaltung'); ?>
                </h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.25rem; font-weight: 600; font-size: 0.9rem;">
                            <?php _e('Vorname *', 'dienstplan-verwaltung'); ?>
                        </label>
                        <input type="text" id="new_mitarbeiter_vorname" class="regular-text" style="width: 100%;" required>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.25rem; font-weight: 600; font-size: 0.9rem;">
                            <?php _e('Nachname *', 'dienstplan-verwaltung'); ?>
                        </label>
                        <input type="text" id="new_mitarbeiter_nachname" class="regular-text" style="width: 100%;" required>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.25rem; font-weight: 600; font-size: 0.9rem;">
                            <?php _e('E-Mail', 'dienstplan-verwaltung'); ?>
                        </label>
                        <input type="email" id="new_mitarbeiter_email" class="regular-text" style="width: 100%;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.25rem; font-weight: 600; font-size: 0.9rem;">
                            <?php _e('Telefon', 'dienstplan-verwaltung'); ?>
                        </label>
                        <input type="tel" id="new_mitarbeiter_telefon" class="regular-text" style="width: 100%;">
                    </div>
                </div>
                
                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                    <button type="button" class="button" onclick="closeNeuerMitarbeiterForm()">
                        <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
                    </button>
                    <button type="button" class="button button-primary" onclick="saveNeuerMitarbeiter()">
                        <span class="dashicons dashicons-saved" style="margin-top: 3px;"></span>
                        <?php _e('Mitarbeiter anlegen', 'dienstplan-verwaltung'); ?>
                    </button>
                </div>
            </div>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeBesetzungModal()"><?php _e('Schließen', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>

<style>
.slot-card {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 0.75rem;
    margin-bottom: 0.75rem;
    background: #fff;
}

.slot-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.slot-card-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.slot-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}

.slot-badge-frei {
    background: #fee2e2;
    color: #991b1b;
}

.slot-badge-besetzt {
    background: #d1fae5;
    color: #065f46;
}

.slot-assign-form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.slot-assign-form select {
    flex: 1;
}
</style>

<!-- JavaScript wurde nach assets/js/dp-besetzung-modal.js ausgelagert -->

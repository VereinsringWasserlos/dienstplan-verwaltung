<?php
/**
 * Dienst Modal
 */
if (!defined('ABSPATH')) exit;
?>

<!-- Dienst Modal -->
<div id="dienst-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content" style="max-width: 900px;">
        <div class="dp-modal-header">
            <h2 id="dienst-modal-title"><?php _e('Neuer Dienst', 'dienstplan-verwaltung'); ?></h2>
            <button type="button" class="dp-modal-close" onclick="closeDienstModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="dienst-form">
                <input type="hidden" id="dienst_id" name="dienst_id">
                
                <table class="form-table">
                    <!-- Veranstaltung -->
                    <tr>
                        <th><label for="d_veranstaltung_id"><?php _e('Veranstaltung', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <select id="d_veranstaltung_id" name="veranstaltung_id" class="regular-text" required style="width: 100%;">
                                <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                                <?php foreach ($veranstaltungen as $v): ?>
                                    <option value="<?php echo $v->id; ?>" <?php selected($selected_veranstaltung, $v->id); ?>>
                                        <?php echo esc_html($v->name); ?>
                                        (<?php echo date_i18n('d.m.Y', strtotime($v->start_datum)); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <!-- Tag -->
                    <tr>
                        <th><label for="d_tag_id"><?php _e('Tag', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <select id="d_tag_id" name="tag_id" class="regular-text" required style="width: 100%;">
                                <option value=""><?php _e('-- Erst Veranstaltung wählen --', 'dienstplan-verwaltung'); ?></option>
                            </select>
                            <div id="tag-zeitfenster-info" style="margin-top: 0.5rem; padding: 0.5rem; background: #f0f6fc; border-left: 4px solid #2271b1; display: none;">
                                <small>
                                    <strong><?php _e('Zeitfenster für Dienste:', 'dienstplan-verwaltung'); ?></strong>
                                    <span id="tag-zeitfenster-text"></span>
                                </small>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <td colspan="2"><hr style="margin: 1rem 0; border: none; border-top: 2px solid #e5e7eb;"></td>
                    </tr>
                    
                    <!-- Verein -->
                    <tr>
                        <th><label for="d_verein_id"><?php _e('Verantwortlicher Verein', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <select id="d_verein_id" name="verein_id" class="regular-text" required style="width: 100%;">
                                        <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                                        <?php foreach ($vereine as $v): ?>
                                            <option value="<?php echo $v->id; ?>">
                                                <?php echo esc_html($v->name); ?>
                                                (<?php echo esc_html($v->kuerzel); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="button" class="button button-secondary" onclick="openNeuerVereinDialog()" style="white-space: nowrap;">
                                    <span class="dashicons dashicons-plus-alt" style="margin-top: 3px;"></span>
                                    <?php _e('Neu', 'dienstplan-verwaltung'); ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Bereich -->
                    <tr>
                        <th><label for="d_bereich_id"><?php _e('Bereich', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <select id="d_bereich_id" name="bereich_id" class="regular-text" required style="width: 100%;">
                                        <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                                        <?php foreach ($bereiche as $b): ?>
                                            <option value="<?php echo $b->id; ?>" data-farbe="<?php echo esc_attr($b->farbe); ?>">
                                                <?php echo esc_html($b->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="button" class="button button-secondary" onclick="openNeuerBereichDialog()" style="white-space: nowrap;">
                                    <span class="dashicons dashicons-plus-alt" style="margin-top: 3px;"></span>
                                    <?php _e('Neu', 'dienstplan-verwaltung'); ?>
                                </button>
                            </div>
                            <p class="description"><?php _e('Bereich auswählen. Tätigkeiten werden entsprechend gefiltert.', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                    
                    <!-- Tätigkeit -->
                    <tr>
                        <th><label for="d_taetigkeit_id"><?php _e('Tätigkeit', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <select id="d_taetigkeit_id" name="taetigkeit_id" class="regular-text" required style="width: 100%;">
                                        <option value=""><?php _e('-- Erst Bereich wählen --', 'dienstplan-verwaltung'); ?></option>
                                    </select>
                                </div>
                                <button type="button" class="button button-secondary" id="btn_neue_taetigkeit" onclick="openNeueTaetigkeitDialog()" style="white-space: nowrap;" disabled>
                                    <span class="dashicons dashicons-plus-alt" style="margin-top: 3px;"></span>
                                    <?php _e('Neu', 'dienstplan-verwaltung'); ?>
                                </button>
                            </div>
                            <p class="description"><?php _e('Tätigkeit für den gewählten Bereich.', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <td colspan="2"><hr style="margin: 1rem 0; border: none; border-top: 2px solid #e5e7eb;"></td>
                    </tr>
                    
                    <!-- Dienstzeit -->
                    <tr>
                        <th><label><?php _e('Dienstzeit', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <div style="flex: 1;">
                                    <label for="d_von_zeit" style="display: block; margin-bottom: 0.25rem; font-weight: 600;">
                                        <?php _e('Von', 'dienstplan-verwaltung'); ?>
                                    </label>
                                    <input type="time" id="d_von_zeit" name="von_zeit" class="regular-text" required style="width: 100%;">
                                </div>
                                <div style="flex: 1;">
                                    <label for="d_bis_zeit" style="display: block; margin-bottom: 0.25rem; font-weight: 600;">
                                        <?php _e('Bis', 'dienstplan-verwaltung'); ?>
                                    </label>
                                    <input type="time" id="d_bis_zeit" name="bis_zeit" class="regular-text" required style="width: 100%;">
                                </div>
                            </div>
                            <div style="margin-top: 0.5rem;">
                                <label>
                                    <input type="checkbox" id="d_bis_folgetag" name="bis_folgetag" value="1">
                                    <?php _e('Dienst geht bis zum Folgetag', 'dienstplan-verwaltung'); ?>
                                </label>
                            </div>
                            <div id="dienst-zeit-warnung" style="margin-top: 0.5rem; padding: 0.5rem; background: #fef3c7; border-left: 4px solid #f59e0b; display: none;">
                                <small style="color: #92400e;">
                                    <span class="dashicons dashicons-warning" style="font-size: 1rem;"></span>
                                    <span id="dienst-zeit-warnung-text"></span>
                                </small>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Anzahl Personen -->
                    <tr>
                        <th><label for="d_anzahl_personen"><?php _e('Anzahl Personen', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="number" id="d_anzahl_personen" name="anzahl_personen" class="regular-text" min="1" max="99" value="1" required style="width: 100px;">
                            <p class="description"><?php _e('Wie viele Personen werden für diesen Dienst benötigt?', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                    
                    <!-- Splittbar -->
                    <tr>
                        <th><label for="d_splittbar"><?php _e('Splittbar', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="d_splittbar" name="splittbar" value="1">
                                <?php _e('Dienst kann in halbe Dienste aufgeteilt werden', 'dienstplan-verwaltung'); ?>
                            </label>
                            <p class="description"><?php _e('Wenn aktiviert, können sich Personen für halbe Dienste eintragen.', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                    
                    <!-- Besonderheiten -->
                    <tr>
                        <th><label for="d_besonderheiten"><?php _e('Besonderheiten', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <textarea id="d_besonderheiten" name="besonderheiten" class="large-text" rows="3" style="width: 100%;"></textarea>
                            <p class="description"><?php _e('Zusätzliche Informationen oder Anforderungen für diesen Dienst.', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeDienstModal()"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
            <button type="button" class="button button-primary" onclick="saveDienst()"><?php _e('Speichern', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>

<!-- Modal für neue Tätigkeit -->
<div id="neue-taetigkeit-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content" style="max-width: 500px;">
        <div class="dp-modal-header">
            <h2><?php _e('Neue Tätigkeit', 'dienstplan-verwaltung'); ?></h2>
            <button type="button" class="dp-modal-close" onclick="closeNeueTaetigkeitModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="neue-taetigkeit-form">
                <table class="form-table">
                    <tr>
                        <th><label for="neue_taetigkeit_name"><?php _e('Name der Tätigkeit', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="text" id="neue_taetigkeit_name" name="name" class="regular-text" style="width: 100%;" required autofocus>
                            <p class="description"><?php _e('Geben Sie einen aussagekräftigen Namen ein, z.B. "Verkauf", "Sicherheit", "Getränke".', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeNeueTaetigkeitModal()"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
            <button type="button" class="button button-primary" onclick="saveNeueTaetigkeit()"><?php _e('Erstellen', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>

<!-- Modal für neuen Bereich -->
<div id="neuer-bereich-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content" style="max-width: 500px;">
        <div class="dp-modal-header">
            <h2><?php _e('Neuer Bereich', 'dienstplan-verwaltung'); ?></h2>
            <button type="button" class="dp-modal-close" onclick="closeNeuerBereichModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="neuer-bereich-form">
                <table class="form-table">
                    <tr>
                        <th><label for="neuer_bereich_name"><?php _e('Name des Bereichs', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="text" id="neuer_bereich_name" name="name" class="regular-text" style="width: 100%;" required autofocus>
                            <p class="description"><?php _e('Geben Sie einen aussagekräftigen Namen ein, z.B. "Kasse", "Service", "Technik".', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="neuer_bereich_farbe"><?php _e('Farbe', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <input type="color" id="neuer_bereich_farbe" name="farbe" value="#3b82f6" style="width: 60px; height: 40px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;">
                            <p class="description"><?php _e('Wählen Sie eine Farbe zur visuellen Unterscheidung der Bereiche.', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeNeuerBereichModal()"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
            <button type="button" class="button button-primary" onclick="saveNeuerBereich()"><?php _e('Erstellen', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>

<!-- Modal für neuen Verein -->
<div id="neuer-verein-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content" style="max-width: 500px;">
        <div class="dp-modal-header">
            <h2><?php _e('Neuer Verein', 'dienstplan-verwaltung'); ?></h2>
            <button type="button" class="dp-modal-close" onclick="closeNeuerVereinModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="neuer-verein-form">
                <table class="form-table">
                    <tr>
                        <th><label for="neuer_verein_name"><?php _e('Name des Vereins', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="text" id="neuer_verein_name" name="name" class="regular-text" style="width: 100%;" required autofocus>
                            <p class="description"><?php _e('Geben Sie den vollständigen Namen des Vereins ein, z.B. "Sportclub Jugendabteilung".', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="neuer_verein_kuerzel"><?php _e('Kürzel', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="text" id="neuer_verein_kuerzel" name="kuerzel" class="regular-text" style="width: 100%; max-width: 100px;" placeholder="z.B. SCJ" required>
                            <p class="description"><?php _e('Kurzes Kürzel, z.B. "SCJ" oder "ECJ" (max. 5 Zeichen).', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeNeuerVereinModal()"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
            <button type="button" class="button button-primary" onclick="saveNeuerVerein()"><?php _e('Erstellen', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>

<!-- JavaScript wurde nach assets/js/dp-dienst-modal.js ausgelagert -->

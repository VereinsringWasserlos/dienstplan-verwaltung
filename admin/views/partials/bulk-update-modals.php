<?php
/**
 * Bulk Update Modals für Dienste
 */
if (!defined('ABSPATH')) exit;

$db = new Dienstplan_Database();
$vereine = $db->get_vereine();
$bereiche = $db->get_bereiche();
?>

<!-- Bulk Zeit ändern Modal -->
<div id="bulk-time-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content" style="max-width: 500px;">
        <div class="dp-modal-header">
            <h2><?php _e('Zeiten ändern', 'dienstplan-verwaltung'); ?></h2>
            <button type="button" class="dp-modal-close" onclick="closeBulkTimeModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="bulk-time-form">
                <table class="form-table">
                    <tr>
                        <th><label for="bulk_von_zeit"><?php _e('Von-Zeit', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="time" id="bulk_von_zeit" name="von_zeit" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="bulk_bis_zeit"><?php _e('Bis-Zeit', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="time" id="bulk_bis_zeit" name="bis_zeit" class="regular-text" required>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeBulkTimeModal()">
                <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
            </button>
            <button type="button" class="button button-primary" onclick="saveBulkTime()">
                <?php _e('Änderungen übernehmen', 'dienstplan-verwaltung'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Bulk Verein ändern Modal -->
<div id="bulk-verein-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content" style="max-width: 500px;">
        <div class="dp-modal-header">
            <h2><?php _e('Verein ändern', 'dienstplan-verwaltung'); ?></h2>
            <button type="button" class="dp-modal-close" onclick="closeBulkVereinModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="bulk-verein-form">
                <table class="form-table">
                    <tr>
                        <th><label for="bulk_verein_id"><?php _e('Verein', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <select id="bulk_verein_id" name="verein_id" class="regular-text" required style="width: 100%;">
                                <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                                <?php foreach ($vereine as $verein): ?>
                                    <option value="<?php echo $verein->id; ?>">
                                        <?php echo esc_html($verein->name); ?> (<?php echo esc_html($verein->kuerzel); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeBulkVereinModal()">
                <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
            </button>
            <button type="button" class="button button-primary" onclick="saveBulkVerein()">
                <?php _e('Änderungen übernehmen', 'dienstplan-verwaltung'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Bulk Bereich ändern Modal -->
<div id="bulk-bereich-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content" style="max-width: 500px;">
        <div class="dp-modal-header">
            <h2><?php _e('Bereich ändern', 'dienstplan-verwaltung'); ?></h2>
            <button type="button" class="dp-modal-close" onclick="closeBulkBereichModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="bulk-bereich-form">
                <table class="form-table">
                    <tr>
                        <th><label for="bulk_bereich_id"><?php _e('Bereich', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <select id="bulk_bereich_id" name="bereich_id" class="regular-text" required style="width: 100%;">
                                <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                                <?php foreach ($bereiche as $bereich): ?>
                                    <option value="<?php echo $bereich->id; ?>">
                                        <?php echo esc_html($bereich->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr id="bulk_taetigkeit_row" style="display: none;">
                        <th><label for="bulk_taetigkeit_id"><?php _e('Tätigkeit', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <select id="bulk_taetigkeit_id" name="taetigkeit_id" class="regular-text" style="width: 100%;">
                                <option value=""><?php _e('-- Optional --', 'dienstplan-verwaltung'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeBulkBereichModal()">
                <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
            </button>
            <button type="button" class="button button-primary" onclick="saveBulkBereich()">
                <?php _e('Änderungen übernehmen', 'dienstplan-verwaltung'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Bulk Tätigkeit ändern Modal -->
<div id="bulk-taetigkeit-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content" style="max-width: 500px;">
        <div class="dp-modal-header">
            <h2><?php _e('Tätigkeit ändern', 'dienstplan-verwaltung'); ?></h2>
            <button type="button" class="dp-modal-close" onclick="closeBulkTaetigkeitModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <p style="color: #666; margin-bottom: 1rem;">
                <?php _e('Wählen Sie zuerst einen Bereich aus, um die verfügbaren Tätigkeiten zu sehen.', 'dienstplan-verwaltung'); ?>
            </p>
            <form id="bulk-taetigkeit-form">
                <table class="form-table">
                    <tr>
                        <th><label for="bulk_taet_bereich_id"><?php _e('Bereich', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <select id="bulk_taet_bereich_id" name="bereich_id" class="regular-text" required style="width: 100%;">
                                <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                                <?php foreach ($bereiche as $bereich): ?>
                                    <option value="<?php echo $bereich->id; ?>">
                                        <?php echo esc_html($bereich->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr id="bulk_taet_taetigkeit_row" style="display: none;">
                        <th><label for="bulk_taet_taetigkeit_id"><?php _e('Tätigkeit', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <select id="bulk_taet_taetigkeit_id" name="taetigkeit_id" class="regular-text" required style="width: 100%;">
                                <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeBulkTaetigkeitModal()">
                <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
            </button>
            <button type="button" class="button button-primary" onclick="saveBulkTaetigkeit()">
                <?php _e('Änderungen übernehmen', 'dienstplan-verwaltung'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Bulk Status ändern Modal -->
<div id="bulk-status-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content" style="max-width: 500px;">
        <div class="dp-modal-header">
            <h2><?php _e('Status ändern', 'dienstplan-verwaltung'); ?></h2>
            <button type="button" class="dp-modal-close" onclick="closeBulkStatusModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="bulk-status-form">
                <table class="form-table">
                    <tr>
                        <th><label for="bulk_status"><?php _e('Status', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <select id="bulk_status" name="status" class="regular-text" required style="width: 100%;">
                                <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                                <option value="geplant"><?php _e('Geplant', 'dienstplan-verwaltung'); ?></option>
                                <option value="unvollstaendig"><?php _e('Unvollständig', 'dienstplan-verwaltung'); ?></option>
                                <option value="bestaetigt"><?php _e('Bestätigt', 'dienstplan-verwaltung'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeBulkStatusModal()">
                <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
            </button>
            <button type="button" class="button button-primary" onclick="saveBulkStatus()">
                <?php _e('Änderungen übernehmen', 'dienstplan-verwaltung'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Bulk Tag zuweisen Modal -->
<div id="bulk-tag-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content" style="max-width: 500px;">
        <div class="dp-modal-header">
            <h2><?php _e('Tag zuweisen', 'dienstplan-verwaltung'); ?></h2>
            <button type="button" class="dp-modal-close" onclick="closeBulkTagModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="bulk-tag-form">
                <table class="form-table">
                    <tr>
                        <th><label for="bulk_tag_id"><?php _e('Tag', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <select id="bulk_tag_id" name="tag_id" class="regular-text" required style="width: 100%;">
                                <option value=""><?php _e('-- Lädt Tags... --', 'dienstplan-verwaltung'); ?></option>
                            </select>
                            <p class="description"><?php _e('Die verfügbaren Tage werden aus der gewählten Veranstaltung geladen.', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeBulkTagModal()">
                <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
            </button>
            <button type="button" class="button button-primary" onclick="saveBulkTag()">
                <?php _e('Änderungen übernehmen', 'dienstplan-verwaltung'); ?>
            </button>
        </div>
    </div>
</div>

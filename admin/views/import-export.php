<?php
/**
 * Import/Export Seite
 */
if (!defined('ABSPATH')) exit;

// Erhalte WordPress Zeitzone
$wp_timezone = get_option('timezone_string');
if (empty($wp_timezone)) {
    $wp_timezone = 'UTC';
}

// Normalisiere Veranstaltungsdaten: end_datum → ende_datum
if (isset($stats['veranstaltungen'])) {
    foreach ($stats['veranstaltungen'] as $veranstaltung) {
        if (isset($veranstaltung->end_datum) && !isset($veranstaltung->ende_datum)) {
            $veranstaltung->ende_datum = $veranstaltung->end_datum;
        }
        if (!isset($veranstaltung->ende_datum)) {
            $veranstaltung->ende_datum = $veranstaltung->start_datum;
        }
    }
}
?>

<div class="wrap">
    <h1><?php _e('Import/Export', 'dienstplan-verwaltung'); ?></h1>
    
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['dp_message'])): ?>
        <div class="notice notice-<?php echo esc_attr($_GET['dp_type'] ?? 'success'); ?> is-dismissible">
            <p><?php echo esc_html($_GET['dp_message']); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Debug: Beide Spalten sollten nebeneinander sein -->
    <table style="width: 100%; border-collapse: separate; border-spacing: 20px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <!-- IMPORT Sektion -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('Import', 'dienstplan-verwaltung'); ?></h2>
                    </div>
            <div class="inside">
                <p class="description">
                    <?php _e('Importieren Sie Vereine, Veranstaltungen und Dienste aus einer CSV-Datei.', 'dienstplan-verwaltung'); ?>
                </p>
                
                <div class="notice notice-info" style="margin: 1rem 0;">
                    <p>
                        <strong><?php _e('💡 Hinweis zu Umlauten:', 'dienstplan-verwaltung'); ?></strong><br>
                        <?php _e('CSV-Dateien sollten in UTF-8 kodiert sein (mit BOM). Exportierte Dateien aus diesem System sind bereits korrekt formatiert.', 'dienstplan-verwaltung'); ?>
                        <br>
                        <small><?php _e('Falls Umlaute fehlerhaft angezeigt werden, speichern Sie die Datei in Excel/LibreOffice als "CSV UTF-8 (*.csv)" und versuchen Sie es erneut.', 'dienstplan-verwaltung'); ?></small>
                    </p>
                </div>
                
                <form id="import-form" enctype="multipart/form-data" style="margin-top: 1.5rem;">
                    <!-- Fehlermeldung nach Import -->
                    <div id="import-message" style="display: none; margin-bottom: 1.5rem; padding: 1rem; border-radius: 4px;"></div>
                    <table class="form-table">
                        <tr>
                            <th><label for="import_type"><?php _e('Import-Typ', 'dienstplan-verwaltung'); ?></label></th>
                            <td>
                                <select id="import_type" name="import_type" class="regular-text" required>
                                    <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                                    <option value="vereine"><?php _e('Vereine', 'dienstplan-verwaltung'); ?></option>
                                    <option value="veranstaltungen"><?php _e('Veranstaltungen', 'dienstplan-verwaltung'); ?></option>
                                    <option value="dienste"><?php _e('Dienste', 'dienstplan-verwaltung'); ?></option>
                                </select>
                                <p class="description"><?php _e('Wählen Sie den Typ der Daten, die Sie importieren möchten.', 'dienstplan-verwaltung'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="import_file"><?php _e('CSV-Datei', 'dienstplan-verwaltung'); ?></label></th>
                            <td>
                                <input type="file" id="import_file" name="import_file" accept=".csv" required>
                                <p class="description"><?php _e('Wählen Sie eine CSV-Datei zum Importieren aus.', 'dienstplan-verwaltung'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- CSV Analyse Button -->
                    <p style="margin-top: 1.5rem;">
                        <button type="button" class="button button-secondary" id="analyze-csv-btn" style="margin-top: 0.5rem;">
                            <?php _e('CSV analysieren', 'dienstplan-verwaltung'); ?>
                        </button>
                    </p>
                    
                    <!-- CSV-Spalten-Zuordnung -->
                    <div id="column-mapping" style="display: none; margin-top: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 4px;">
                        <h3><?php _e('Spalten-Zuordnung', 'dienstplan-verwaltung'); ?></h3>
                        <p class="description"><?php _e('Ordnen Sie die CSV-Spalten den Datenbankfeldern zu.', 'dienstplan-verwaltung'); ?></p>
                        <table class="widefat" id="mapping-table" style="margin-top: 1rem;">
                            <thead>
                                <tr>
                                    <th><?php _e('CSV-Spalte (Beispielwert)', 'dienstplan-verwaltung'); ?></th>
                                    <th><?php _e('Zuordnen zu', 'dienstplan-verwaltung'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="mapping-body"></tbody>
                        </table>
                    </div>
                    
                    <!-- Veranstaltungs-Auswahl für Dienste-Import -->
                    <table class="form-table" id="dienste_veranstaltung_row" style="display: none; margin-top: 1rem;">
                        <tr>
                            <th><label for="import_veranstaltung"><?php _e('Veranstaltung', 'dienstplan-verwaltung'); ?></label></th>
                            <td>
                                <select id="import_veranstaltung" name="import_veranstaltung" class="regular-text" required>
                                    <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                                    <?php foreach ($stats['veranstaltungen'] as $veranstaltung): 
                                        $end_datum = isset($veranstaltung->ende_datum) ? $veranstaltung->ende_datum : (isset($veranstaltung->end_datum) ? $veranstaltung->end_datum : $veranstaltung->start_datum);
                                    ?>
                                        <option value="<?php echo esc_attr($veranstaltung->id); ?>" 
                                                data-start="<?php echo esc_attr($veranstaltung->start_datum); ?>"
                                                data-ende="<?php echo esc_attr($end_datum); ?>">
                                            <?php echo esc_html($veranstaltung->name); ?> 
                                            (<?php echo esc_html($veranstaltung->start_datum); ?> - <?php echo esc_html($end_datum); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Wählen Sie die Veranstaltung, für die Dienste importiert werden sollen.', 'dienstplan-verwaltung'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <table class="form-table" id="import_mode_row" style="display: none; margin-top: 1rem;">
                        <tr>
                            <th><label for="import_mode"><?php _e('Import-Modus', 'dienstplan-verwaltung'); ?></label></th>
                            <td>
                                <select id="import_mode" name="import_mode" class="regular-text">
                                    <option value="create"><?php _e('Nur neue erstellen', 'dienstplan-verwaltung'); ?></option>
                                    <option value="update"><?php _e('Aktualisieren (wenn vorhanden)', 'dienstplan-verwaltung'); ?></option>
                                </select>
                                <p class="description"><?php _e('Bestehende Einträge werden anhand von Name/Kürzel identifiziert.', 'dienstplan-verwaltung'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- Zeitzone für Import -->
                    <table class="form-table" id="import_timezone_row" style="display: none; margin-top: 1rem;">
                        <tr>
                            <th><label for="import_timezone"><?php _e('Zeitzone für Zeiten', 'dienstplan-verwaltung'); ?></label></th>
                            <td>
                                <select id="import_timezone" name="import_timezone" class="regular-text">
                                    <option value="UTC">UTC</option>
                                    <option value="WordPress"><?php printf(__('WordPress Zeitzone (%s)', 'dienstplan-verwaltung'), esc_html($wp_timezone)); ?></option>
                                </select>
                                <p class="description"><?php _e('Wählen Sie die Zeitzone, in der die Zeiten in Ihrer CSV-Datei angegeben sind.', 'dienstplan-verwaltung'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" class="button button-primary" onclick="return startImport(event);">
                            <?php _e('Import starten', 'dienstplan-verwaltung'); ?>
                        </button>
                    </p>
                </form>
                
                <!-- Import Fortschritt -->
                <div id="import-progress" style="display: none; margin-top: 1rem; padding: 1rem; background: #f0f6fc; border-left: 4px solid #2271b1;">
                    <h3><?php _e('Import läuft...', 'dienstplan-verwaltung'); ?></h3>
                    <div class="progress-bar" style="width: 100%; height: 20px; background: #e0e0e0; border-radius: 4px; overflow: hidden; margin: 1rem 0;">
                        <div id="progress-bar-fill" style="width: 0%; height: 100%; background: #2271b1; transition: width 0.3s;"></div>
                    </div>
                    <p id="import-status"><?php _e('Vorbereitung...', 'dienstplan-verwaltung'); ?></p>
                    <div id="import-results" style="margin-top: 1rem;"></div>
                </div>
                
                <!-- Format-Hilfe -->
                <div style="margin-top: 2rem; padding: 1rem; background: #fff8e1; border-left: 4px solid #ffc107;">
                    <h4><?php _e('CSV-Format', 'dienstplan-verwaltung'); ?></h4>
                    <p><strong><?php _e('Vereine:', 'dienstplan-verwaltung'); ?></strong><br>
                    <code>name,kuerzel,beschreibung,kontakt_name,kontakt_email,kontakt_telefon</code></p>
                    
                    <p><strong><?php _e('Veranstaltungen:', 'dienstplan-verwaltung'); ?></strong><br>
                    <code>name,start_datum,ende_datum,beschreibung,dienst_von_zeit,dienst_bis_zeit</code><br>
                    <small><?php _e('Datum: YYYY-MM-DD, Zeit: HH:MM', 'dienstplan-verwaltung'); ?></small></p>
                    
                    <p><strong><?php _e('Dienste:', 'dienstplan-verwaltung'); ?></strong><br>
                    <code>datum,verein_kuerzel,bereich_name,taetigkeit_name,von_zeit,bis_zeit,anzahl_personen,splittbar,besonderheiten</code><br>
                    <small><?php _e('Datum: YYYY-MM-DD (wird in Tag-Nummer umgerechnet), Zeit: HH:MM, splittbar: 1 oder 0', 'dienstplan-verwaltung'); ?><br>
                    <?php _e('Hinweis: Veranstaltung wird per Dropdown ausgewählt, nicht aus CSV importiert. Besonderheiten werden nur importiert wenn sie in der CSV vorhanden sind und nicht überschreiben bestehende Import-Warnungen.', 'dienstplan-verwaltung'); ?></small></p>
                    
                    <h4 style="margin-top: 1.5rem; color: #2271b1;">
                        <span class="dashicons dashicons-admin-links"></span> 
                        <?php _e('Vereins-Kürzel Zuordnung', 'dienstplan-verwaltung'); ?>
                    </h4>
                    <p class="description" style="margin-bottom: 0.75rem;">
                        <?php _e('Das System erkennt automatisch verschiedene Schreibweisen von Vereinskürzeln:', 'dienstplan-verwaltung'); ?>
                    </p>
                    <table class="widefat" style="max-width: 400px;">
                        <thead>
                            <tr>
                                <th><?php _e('CSV-Kürzel', 'dienstplan-verwaltung'); ?></th>
                                <th><?php _e('Wird zugeordnet zu', 'dienstplan-verwaltung'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>SC</code></td>
                                <td>SCJ, SC-J, SC J</td>
                            </tr>
                            <tr>
                                <td><code>EC</code></td>
                                <td>ECJ, EC-J, EC J</td>
                            </tr>
                            <tr>
                                <td><code>CV</code></td>
                                <td>CVJM, CV-JM, CV JM</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="font-style: italic; color: #666;">
                                    <?php _e('+ Teilstring-Suche für ähnliche Kürzel', 'dienstplan-verwaltung'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
                </div>
            </td>
            
            <td style="width: 50%; vertical-align: top;">
                <!-- EXPORT Sektion -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('Export', 'dienstplan-verwaltung'); ?></h2>
                    </div>
            <div class="inside">
                <p class="description">
                    <?php _e('Exportieren Sie Ihre Daten als CSV-Datei.', 'dienstplan-verwaltung'); ?>
                </p>
                
                <!-- Statistiken -->
                <div style="margin: 1.5rem 0; padding: 1rem; background: #f9fafb; border-radius: 4px;">
                    <h4 style="margin-top: 0;"><?php _e('Verfügbare Daten:', 'dienstplan-verwaltung'); ?></h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;">
                            <span class="dashicons dashicons-groups" style="color: #2271b1;"></span>
                            <strong><?php echo count($stats['vereine']); ?></strong> <?php _e('Vereine', 'dienstplan-verwaltung'); ?>
                        </li>
                        <li style="padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;">
                            <span class="dashicons dashicons-calendar-alt" style="color: #2271b1;"></span>
                            <strong><?php echo count($stats['veranstaltungen']); ?></strong> <?php _e('Veranstaltungen', 'dienstplan-verwaltung'); ?>
                        </li>
                        <li style="padding: 0.5rem 0;">
                            <span class="dashicons dashicons-list-view" style="color: #2271b1;"></span>
                            <strong><?php echo count($stats['dienste']); ?></strong> <?php _e('Dienste', 'dienstplan-verwaltung'); ?>
                        </li>
                    </ul>
                </div>
                
                <h3><?php _e('Export-Optionen', 'dienstplan-verwaltung'); ?></h3>
                
                <p>
                    <button type="button" class="button button-secondary" onclick="return exportData('vereine', event);" style="display: block; width: 100%; margin-bottom: 0.5rem;">
                        <span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
                        <?php _e('Vereine exportieren', 'dienstplan-verwaltung'); ?>
                    </button>
                </p>
                
                <p>
                    <button type="button" class="button button-secondary" onclick="return exportData('veranstaltungen', event);" style="display: block; width: 100%; margin-bottom: 0.5rem;">
                        <span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
                        <?php _e('Veranstaltungen exportieren', 'dienstplan-verwaltung'); ?>
                    </button>
                </p>
                
                <p>
                    <button type="button" class="button button-secondary" onclick="return exportData('dienste', event);" style="display: block; width: 100%; margin-bottom: 0.5rem;">
                        <span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
                        <?php _e('Dienste exportieren', 'dienstplan-verwaltung'); ?>
                    </button>
                </p>
                
                <p>
                    <button type="button" class="button button-primary" onclick="return exportData('all', event);" style="display: block; width: 100%; margin-top: 1rem;">
                        <span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
                        <?php _e('Alles exportieren (ZIP)', 'dienstplan-verwaltung'); ?>
                    </button>
                </p>
            </div>
        </div>
            </td>
        </tr>
    </table>
</div>

<!-- JavaScript moved to assets/js/dp-import-export.js -->
<style>
.dp-import-export-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.dp-ie-column {
    flex: 1;
    min-width: 450px;
    max-width: calc(50% - 10px);
}

@media screen and (max-width: 1200px) {
    .dp-ie-column {
        max-width: 100%;
        min-width: 100%;
    }
}
</style>

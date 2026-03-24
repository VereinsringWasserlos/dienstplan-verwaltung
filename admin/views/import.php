<?php
/**
 * Import-Seite (neu)
 * Fokus: Import eines alten Dienstplans (Excel/CSV)
 * - Schritt-für-Schritt-Wizard
 * - Bereiche & Tätigkeiten werden vollständig angelegt
 * - Vereine werden nur angelegt wenn nicht vorhanden
 */
if (!defined('ABSPATH')) exit;

$can_manage_clubs  = Dienstplan_Roles::can_manage_clubs()  || current_user_can('manage_options');
$can_manage_events = Dienstplan_Roles::can_manage_events() || current_user_can('manage_options');

if (!$can_manage_clubs && !$can_manage_events) {
    wp_die(__('Sie haben keine Berechtigung für den Import.', 'dienstplan-verwaltung'));
}

$wp_timezone = get_option('timezone_string') ?: 'UTC';

// Veranstaltungen für Dienste-Import normalisieren
if (isset($stats['veranstaltungen'])) {
    foreach ($stats['veranstaltungen'] as $v) {
        if (!isset($v->end_datum) && isset($v->ende_datum)) {
            $v->end_datum = $v->ende_datum;
        }
        if (!isset($v->end_datum)) {
            $v->end_datum = $v->start_datum;
        }
    }
}
?>
<div class="wrap dp-import-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-upload" style="font-size:1.4em;vertical-align:middle;margin-right:6px;"></span>
        <?php _e('Daten importieren', 'dienstplan-verwaltung'); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=dienstplan-export')); ?>" class="page-title-action">
        <?php _e('Zum Export', 'dienstplan-verwaltung'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['dp_message'])): ?>
        <div class="notice notice-<?php echo esc_attr($_GET['dp_type'] ?? 'success'); ?> is-dismissible">
            <p><?php echo esc_html($_GET['dp_message']); ?></p>
        </div>
    <?php endif; ?>

    <!-- ═══ WIZARD-SCHRITTE ANZEIGE ═══ -->
    <div class="dp-wizard-steps" id="dp-wizard-steps">
        <div class="dp-step active" data-step="1">
            <span class="dp-step-number">1</span>
            <span class="dp-step-label"><?php _e('Typ & Datei', 'dienstplan-verwaltung'); ?></span>
        </div>
        <div class="dp-step-divider"></div>
        <div class="dp-step" data-step="2">
            <span class="dp-step-number">2</span>
            <span class="dp-step-label"><?php _e('Spalten zuordnen', 'dienstplan-verwaltung'); ?></span>
        </div>
        <div class="dp-step-divider"></div>
        <div class="dp-step" data-step="3">
            <span class="dp-step-number">3</span>
            <span class="dp-step-label"><?php _e('Optionen & Start', 'dienstplan-verwaltung'); ?></span>
        </div>
        <div class="dp-step-divider"></div>
        <div class="dp-step" data-step="4">
            <span class="dp-step-number">4</span>
            <span class="dp-step-label"><?php _e('Ergebnis', 'dienstplan-verwaltung'); ?></span>
        </div>
    </div>

    <!-- ═══ SCHRITT 1: Typ & Datei ═══ -->
    <div class="dp-wizard-panel postbox" id="dp-step-1">
        <div class="postbox-header"><h2><?php _e('Schritt 1: Import-Typ und Datei wählen', 'dienstplan-verwaltung'); ?></h2></div>
        <div class="inside">

            <!-- Vorlagen-Karten -->
            <p class="description" style="margin-bottom:1rem;">
                <?php _e('Wählen Sie einen Import-Typ. Die empfohlene Reihenfolge beim Erstimport: Bereiche → Tätigkeiten → Vereine → Veranstaltungen → Dienste.', 'dienstplan-verwaltung'); ?>
            </p>

            <div class="dp-type-cards" id="dp-type-cards">

                <?php if ($can_manage_events): ?>
                <!-- DIENSTPLAN (Haupttyp) -->
                <div class="dp-type-card dp-type-card--highlight" data-type="dienstplan">
                    <div class="dp-type-card-icon">📋</div>
                    <div class="dp-type-card-title"><?php _e('Alter Dienstplan (Excel-CSV)', 'dienstplan-verwaltung'); ?></div>
                    <div class="dp-type-card-desc"><?php _e('Eine Zeile = ein Dienst aus einer aus Excel exportierten CSV. Bereiche, Tätigkeiten und Vereine werden automatisch angelegt.', 'dienstplan-verwaltung'); ?></div>
                    <div class="dp-type-card-badge"><?php _e('Empfohlen', 'dienstplan-verwaltung'); ?></div>
                </div>
                <?php endif; ?>

                <?php if ($can_manage_events): ?>
                <div class="dp-type-card" data-type="bereiche">
                    <div class="dp-type-card-icon">🗂️</div>
                    <div class="dp-type-card-title"><?php _e('Bereiche', 'dienstplan-verwaltung'); ?></div>
                    <div class="dp-type-card-desc"><?php _e('Name, Farbe, Sortierung, Admin-only.', 'dienstplan-verwaltung'); ?></div>
                </div>

                <div class="dp-type-card" data-type="taetigkeiten">
                    <div class="dp-type-card-icon">🔧</div>
                    <div class="dp-type-card-title"><?php _e('Tätigkeiten', 'dienstplan-verwaltung'); ?></div>
                    <div class="dp-type-card-desc"><?php _e('Bereich, Name, Beschreibung, Admin-only.', 'dienstplan-verwaltung'); ?></div>
                </div>
                <?php endif; ?>

                <?php if ($can_manage_clubs): ?>
                <div class="dp-type-card" data-type="vereine">
                    <div class="dp-type-card-icon">🏛️</div>
                    <div class="dp-type-card-title"><?php _e('Vereine', 'dienstplan-verwaltung'); ?></div>
                    <div class="dp-type-card-desc"><?php _e('Name, Kürzel, Kontaktdaten.', 'dienstplan-verwaltung'); ?></div>
                </div>
                <?php endif; ?>

                <?php if ($can_manage_events): ?>
                <div class="dp-type-card" data-type="veranstaltungen">
                    <div class="dp-type-card-icon">📅</div>
                    <div class="dp-type-card-title"><?php _e('Veranstaltungen', 'dienstplan-verwaltung'); ?></div>
                    <div class="dp-type-card-desc"><?php _e('Name, Start-/Enddatum.', 'dienstplan-verwaltung'); ?></div>
                </div>

                <div class="dp-type-card" data-type="dienste">
                    <div class="dp-type-card-icon">📑</div>
                    <div class="dp-type-card-title"><?php _e('Dienste (einzeln)', 'dienstplan-verwaltung'); ?></div>
                    <div class="dp-type-card-desc"><?php _e('Datum, Zeiten, Verein, Bereich, Tätigkeit.', 'dienstplan-verwaltung'); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Verstecktes Input für ausgewählten Typ -->
            <input type="hidden" id="dp_import_type" value="">

            <!-- Datei-Upload (erscheint nach Typ-Wahl) -->
            <div id="dp-file-section" style="display:none; margin-top:1.5rem;">
                <div class="dp-file-drop-zone" id="dp-file-drop-zone">
                    <span class="dashicons dashicons-upload" style="font-size:2em;color:#93c5fd;display:block;margin-bottom:0.5rem;"></span>
                    <p><?php _e('CSV-Datei aus Excel hierher ziehen oder klicken zum Auswählen', 'dienstplan-verwaltung'); ?></p>
                    <p style="color:#6b7280;font-size:0.85em;"><?php _e('UTF-8 oder ISO-8859-1 (Windows), Trennzeichen: Semikolon oder Komma', 'dienstplan-verwaltung'); ?></p>
                    <p style="margin:0.75rem 0 0;">
                        <a class="button button-secondary" href="<?php echo esc_url(wp_make_link_relative(DIENSTPLAN_PLUGIN_URL . 'documentation/templates/import-dienstplan-template.csv')); ?>" download>
                            <span class="dashicons dashicons-download" style="margin-top:3px;"></span>
                            <?php _e('Template CSV herunterladen', 'dienstplan-verwaltung'); ?>
                        </a>
                    </p>
                    <input type="file" id="dp_import_file" accept=".csv,.txt" style="display:none;">
                    <button type="button" class="button" id="dp-browse-btn"><?php _e('Datei auswählen', 'dienstplan-verwaltung'); ?></button>
                </div>
                <div id="dp-file-info" style="display:none; margin-top:0.75rem; padding:0.6rem 1rem; background:#f0fdf4; border-left:4px solid #22c55e; border-radius:4px;">
                    <span class="dashicons dashicons-yes-alt" style="color:#16a34a;"></span>
                    <span id="dp-file-name"></span> &mdash; <span id="dp-file-size"></span>
                    <button type="button" class="button button-link" id="dp-file-reset" style="margin-left:1rem; color:#dc2626;"><?php _e('Entfernen', 'dienstplan-verwaltung'); ?></button>
                </div>

                <!-- Hinweis je nach Typ -->
                <div id="dp-type-hint" class="notice notice-info inline" style="margin-top:0.75rem; display:none;">
                    <p id="dp-type-hint-text"></p>
                </div>

                <p style="margin-top:1rem;">
                    <button type="button" class="button button-primary" id="dp-analyze-btn" disabled>
                        <span class="dashicons dashicons-search" style="margin-top:3px;"></span>
                        <?php _e('CSV analysieren → Schritt 2', 'dienstplan-verwaltung'); ?>
                    </button>
                </p>
            </div>
        </div>
    </div>

    <!-- ═══ SCHRITT 2: Spalten zuordnen ═══ -->
    <div class="dp-wizard-panel postbox" id="dp-step-2" style="display:none;">
        <div class="postbox-header">
            <h2><?php _e('Schritt 2: Spalten zuordnen', 'dienstplan-verwaltung'); ?></h2>
        </div>
        <div class="inside">
            <p class="description" id="dp-mapping-desc"></p>

            <!-- Vorschau-Info -->
            <div id="dp-preview-info" style="margin-bottom:1rem; padding:0.6rem 0.9rem; background:#eff6ff; border-left:4px solid #3b82f6; border-radius:4px; font-size:0.9em;"></div>

            <div class="dp-mapping-container">
                <table class="widefat dp-mapping-table" id="dp-mapping-table">
                    <thead>
                        <tr>
                            <th style="width:35%;"><?php _e('CSV-Spalte', 'dienstplan-verwaltung'); ?></th>
                            <th style="width:30%;"><?php _e('Beispielwert', 'dienstplan-verwaltung'); ?></th>
                            <th style="width:35%;"><?php _e('Zuordnen zu', 'dienstplan-verwaltung'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="dp-mapping-body"></tbody>
                </table>
            </div>

            <div class="dp-mapping-legend" style="margin-top:0.75rem; font-size:0.85em; color:#6b7280;">
                <span style="display:inline-block;width:10px;height:10px;background:#fef9c3;border:1px solid #fbbf24;border-radius:2px;margin-right:4px;"></span><?php _e('Pflichtfeld', 'dienstplan-verwaltung'); ?>
                &nbsp;&nbsp;
                <span style="display:inline-block;width:10px;height:10px;background:#dcfce7;border:1px solid #86efac;border-radius:2px;margin-right:4px;"></span><?php _e('Automatisch erkannt', 'dienstplan-verwaltung'); ?>
            </div>

            <p style="margin-top:1.5rem;">
                <button type="button" class="button" id="dp-back-btn-1"><?php _e('← Zurück', 'dienstplan-verwaltung'); ?></button>
                &nbsp;
                <button type="button" class="button button-primary" id="dp-to-step3-btn">
                    <?php _e('Weiter → Optionen', 'dienstplan-verwaltung'); ?>
                </button>
            </p>
        </div>
    </div>

    <!-- ═══ SCHRITT 3: Optionen & Start ═══ -->
    <div class="dp-wizard-panel postbox" id="dp-step-3" style="display:none;">
        <div class="postbox-header">
            <h2><?php _e('Schritt 3: Import-Optionen', 'dienstplan-verwaltung'); ?></h2>
        </div>
        <div class="inside">
            <table class="form-table">

                <!-- Import-Modus -->
                <tr>
                    <th scope="row">
                        <label for="dp_import_mode"><?php _e('Import-Modus', 'dienstplan-verwaltung'); ?></label>
                    </th>
                    <td>
                        <select id="dp_import_mode" class="regular-text">
                            <option value="create"><?php _e('Nur neue anlegen (vorhandene überspringen)', 'dienstplan-verwaltung'); ?></option>
                            <option value="update"><?php _e('Vorhandene aktualisieren + neue anlegen', 'dienstplan-verwaltung'); ?></option>
                        </select>
                        <p class="description"><?php _e('Erkennung über: Bereiche/Tätigkeiten = Name, Vereine = Kürzel, Veranstaltungen = Name, Dienste = immer neu.', 'dienstplan-verwaltung'); ?></p>
                    </td>
                </tr>

                <!-- Veranstaltungs-Auswahl (nur Dienste / Dienstplan) -->
                <tr id="dp-veranstaltung-row" style="display:none;">
                    <th scope="row">
                        <label for="dp_import_veranstaltung"><?php _e('Veranstaltung', 'dienstplan-verwaltung'); ?></label>
                    </th>
                    <td>
                        <select id="dp_import_veranstaltung" class="regular-text">
                            <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                            <?php foreach ($stats['veranstaltungen'] as $v):
                                $ed = $v->end_datum ?? $v->start_datum;
                            ?>
                            <option value="<?php echo esc_attr($v->id); ?>"
                                    data-start="<?php echo esc_attr($v->start_datum); ?>"
                                    data-ende="<?php echo esc_attr($ed); ?>">
                                <?php echo esc_html($v->name); ?>
                                (<?php echo esc_html($v->start_datum); ?> – <?php echo esc_html($ed); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Dienste werden dieser Veranstaltung zugeordnet.', 'dienstplan-verwaltung'); ?></p>
                    </td>
                </tr>

                <!-- Vereine anlegen (Dienstplan-Import) -->
                <tr id="dp-create-vereine-row" style="display:none;">
                    <th scope="row"><?php _e('Vereine anlegen', 'dienstplan-verwaltung'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" id="dp_auto_create_vereine" value="1" checked>
                            <?php _e('Fehlende Vereine automatisch anlegen (nur Name, kein Kürzel → wird aus Name erzeugt)', 'dienstplan-verwaltung'); ?>
                        </label>
                    </td>
                </tr>

                <!-- Standard-Farbe für neue Bereiche -->
                <tr id="dp-bereich-farbe-row" style="display:none;">
                    <th scope="row">
                        <label for="dp_default_bereich_farbe"><?php _e('Standard-Bereichsfarbe', 'dienstplan-verwaltung'); ?></label>
                    </th>
                    <td>
                        <input type="color" id="dp_default_bereich_farbe" value="#3b82f6">
                        <p class="description"><?php _e('Wird für neue Bereiche ohne Farb-Spalte verwendet.', 'dienstplan-verwaltung'); ?></p>
                    </td>
                </tr>

            </table>

            <!-- Zusammenfassung -->
            <div id="dp-import-summary" style="margin-top:1rem; padding:1rem; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0;">
                <h4 style="margin-top:0;"><?php _e('Import-Zusammenfassung', 'dienstplan-verwaltung'); ?></h4>
                <ul id="dp-summary-list" style="margin:0; padding-left:1.2rem; color:#374151;"></ul>
            </div>

            <p style="margin-top:1.5rem;">
                <button type="button" class="button" id="dp-back-btn-2"><?php _e('← Zurück', 'dienstplan-verwaltung'); ?></button>
                &nbsp;
                <button type="button" class="button button-primary" id="dp-start-import-btn">
                    <span class="dashicons dashicons-upload" style="margin-top:3px;"></span>
                    <?php _e('Import starten', 'dienstplan-verwaltung'); ?>
                </button>
            </p>
        </div>
    </div>

    <!-- ═══ SCHRITT 4: Ergebnis ═══ -->
    <div class="dp-wizard-panel postbox" id="dp-step-4" style="display:none;">
        <div class="postbox-header">
            <h2><?php _e('Schritt 4: Import-Ergebnis', 'dienstplan-verwaltung'); ?></h2>
        </div>
        <div class="inside">
            <!-- Fortschrittsbalken -->
            <div id="dp-progress-wrap" style="display:none;">
                <div style="width:100%; height:22px; background:#e5e7eb; border-radius:11px; overflow:hidden; margin-bottom:0.75rem;">
                    <div id="dp-progress-bar" style="width:0%; height:100%; background:#2271b1; transition:width 0.4s; border-radius:11px;"></div>
                </div>
                <p id="dp-progress-status" style="color:#374151; font-size:0.9em;"></p>
            </div>

            <div id="dp-result-box"></div>

            <p style="margin-top:1.5rem;" id="dp-result-actions" style="display:none;">
                <button type="button" class="button" onclick="dpImportReset();"><?php _e('Neuen Import starten', 'dienstplan-verwaltung'); ?></button>
            </p>
        </div>
    </div>

    <!-- ═══ FORMAT-REFERENZ (aufklappbar) ═══ -->
    <div class="postbox" style="margin-top:1rem;">
        <div class="postbox-header" style="cursor:pointer;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? '' : 'none';">
            <h2>
                <span class="dashicons dashicons-info" style="margin-right:6px;"></span>
                <?php _e('CSV-Format-Referenz (aufklappen)', 'dienstplan-verwaltung'); ?>
            </h2>
        </div>
        <div class="inside" id="dp-format-ref" style="display:none;">

            <div class="dp-format-section">
                <h3>📋 <?php _e('Alter Dienstplan / Dienste', 'dienstplan-verwaltung'); ?></h3>
                <p><?php _e('Eine Zeile pro Dienst. Bereiche, Tätigkeiten und Vereine werden bei Bedarf angelegt:', 'dienstplan-verwaltung'); ?></p>
                <table class="widefat" style="max-width:800px;">
                    <thead><tr><th><?php _e('Spaltenname', 'dienstplan-verwaltung'); ?></th><th><?php _e('Pflicht', 'dienstplan-verwaltung'); ?></th><th><?php _e('Beispiel', 'dienstplan-verwaltung'); ?></th><th><?php _e('Hinweis', 'dienstplan-verwaltung'); ?></th></tr></thead>
                    <tbody>
                        <tr><td><code>datum</code></td><td>✅</td><td><code>2024-07-20</code></td><td><?php _e('Auch DD.MM.YYYY', 'dienstplan-verwaltung'); ?></td></tr>
                        <tr><td><code>von_zeit</code></td><td></td><td><code>08:00</code></td><td></td></tr>
                        <tr><td><code>bis_zeit</code></td><td></td><td><code>16:00</code></td><td><?php _e('< von_zeit = nächster Tag', 'dienstplan-verwaltung'); ?></td></tr>
                        <tr><td><code>bereich_name</code></td><td></td><td><code>Sicherheit</code></td><td><?php _e('Wird angelegt wenn fehlend', 'dienstplan-verwaltung'); ?></td></tr>
                        <tr><td><code>taetigkeit_name</code></td><td></td><td><code>Einlasskontrolle</code></td><td><?php _e('Wird angelegt wenn fehlend', 'dienstplan-verwaltung'); ?></td></tr>
                        <tr><td><code>bereich_farbe</code></td><td></td><td><code>#ef4444</code></td><td><?php _e('Farbe beim Anlegen des Bereichs', 'dienstplan-verwaltung'); ?></td></tr>
                        <tr><td><code>bereich_admin_only</code></td><td></td><td><code>0</code></td><td><?php _e('1 = nur Admin sichtbar', 'dienstplan-verwaltung'); ?></td></tr>
                        <tr><td><code>taetigkeit_admin_only</code></td><td></td><td><code>0</code></td><td><?php _e('1 = nur Admin sichtbar', 'dienstplan-verwaltung'); ?></td></tr>
                        <tr><td><code>verein_kuerzel</code></td><td></td><td><code>SCJ</code></td><td><?php _e('Verein wird angelegt wenn fehlend', 'dienstplan-verwaltung'); ?></td></tr>
                        <tr><td><code>verein_name</code></td><td></td><td><code>SC Jugend</code></td><td><?php _e('Für Anlage benötigt', 'dienstplan-verwaltung'); ?></td></tr>
                        <tr><td><code>anzahl_personen</code></td><td></td><td><code>2</code></td><td></td></tr>
                        <tr><td><code>splittbar</code></td><td></td><td><code>1</code></td><td><?php _e('1 oder 0', 'dienstplan-verwaltung'); ?></td></tr>
                        <tr><td><code>besonderheiten</code></td><td></td><td><code>Bitte pünktlich</code></td><td></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="dp-format-section" style="margin-top:1.5rem;">
                <h3>🗂️ <?php _e('Bereiche', 'dienstplan-verwaltung'); ?></h3>
                <code>name;farbe;aktiv;sortierung;admin_only</code><br>
                <small><?php _e('Beispiel: Sicherheit;#ef4444;1;10;0', 'dienstplan-verwaltung'); ?></small>
            </div>

            <div class="dp-format-section" style="margin-top:1.5rem;">
                <h3>🔧 <?php _e('Tätigkeiten', 'dienstplan-verwaltung'); ?></h3>
                <code>bereich_name;name;beschreibung;aktiv;sortierung;admin_only</code><br>
                <small><?php _e('Beispiel: Sicherheit;Einlasskontrolle;Kontrolle am Eingang;1;10;0', 'dienstplan-verwaltung'); ?></small>
            </div>

            <div class="dp-format-section" style="margin-top:1.5rem;">
                <h3>🏛️ <?php _e('Vereine', 'dienstplan-verwaltung'); ?></h3>
                <code>name;kuerzel;beschreibung;kontakt_name;kontakt_email;kontakt_telefon</code>
            </div>

            <div class="dp-format-section" style="margin-top:1.5rem;">
                <h3>📅 <?php _e('Veranstaltungen', 'dienstplan-verwaltung'); ?></h3>
                <code>name;start_datum;end_datum;beschreibung;dienst_von_zeit;dienst_bis_zeit</code><br>
                <small><?php _e('Datum: YYYY-MM-DD oder DD.MM.YYYY', 'dienstplan-verwaltung'); ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Daten für JS -->
<script>
window.dpImportData = {
    ajaxurl: <?php echo json_encode(admin_url('admin-ajax.php')); ?>,
    nonce:   <?php echo json_encode(wp_create_nonce('dp_ajax_nonce')); ?>,
    timezone: <?php echo json_encode($wp_timezone); ?>,
    canManageClubs:  <?php echo $can_manage_clubs  ? 'true' : 'false'; ?>,
    canManageEvents: <?php echo $can_manage_events ? 'true' : 'false'; ?>,
    vereine: <?php
        $verein_payload = array();
        if (!empty($stats['vereine']) && is_array($stats['vereine'])) {
            foreach ($stats['vereine'] as $v) {
                $verein_payload[] = array(
                    'id' => isset($v->id) ? intval($v->id) : (isset($v['id']) ? intval($v['id']) : 0),
                    'name' => isset($v->name) ? (string) $v->name : (isset($v['name']) ? (string) $v['name'] : ''),
                    'kuerzel' => isset($v->kuerzel) ? (string) $v->kuerzel : (isset($v['kuerzel']) ? (string) $v['kuerzel'] : '')
                );
            }
        }
        echo json_encode($verein_payload);
    ?>
};
</script>

<style>
/* Wizard Schritte */
.dp-import-wrap { max-width: 960px; }
.dp-wizard-steps {
    display: flex;
    align-items: center;
    margin: 1rem 0 1.5rem;
    padding: 1rem 1.5rem;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}
.dp-step {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #9ca3af;
    font-weight: 500;
    font-size: 0.9em;
}
.dp-step.active   { color: #2271b1; }
.dp-step.done     { color: #16a34a; }
.dp-step-number {
    width: 26px; height: 26px;
    border-radius: 50%;
    background: #e5e7eb;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85em;
    font-weight: 700;
}
.dp-step.active .dp-step-number { background: #2271b1; color: #fff; }
.dp-step.done   .dp-step-number { background: #16a34a; color: #fff; }
.dp-step-divider { flex: 1; height: 2px; background: #e5e7eb; margin: 0 0.5rem; min-width: 20px; }

/* Typ-Karten */
.dp-type-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}
.dp-type-card {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem 0.75rem;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s;
    position: relative;
    background: #fff;
}
.dp-type-card:hover { border-color: #93c5fd; box-shadow: 0 2px 8px rgba(59,130,246,0.12); }
.dp-type-card.selected { border-color: #2271b1; background: #eff6ff; }
.dp-type-card--highlight { border-color: #f59e0b; background: #fffbeb; }
.dp-type-card--highlight.selected { border-color: #d97706; background: #fef3c7; }
.dp-type-card-icon { font-size: 1.8em; margin-bottom: 0.3rem; }
.dp-type-card-title { font-weight: 600; font-size: 0.9em; color: #1f2937; }
.dp-type-card-desc { font-size: 0.78em; color: #6b7280; margin-top: 0.3rem; }
.dp-type-card-badge {
    position: absolute;
    top: -8px; right: 8px;
    background: #f59e0b; color: #fff;
    font-size: 0.7em; font-weight: 700;
    padding: 2px 7px; border-radius: 10px;
}

/* Datei-Drop-Zone */
.dp-file-drop-zone {
    border: 2px dashed #93c5fd;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    background: #f8faff;
    transition: background 0.2s, border-color 0.2s;
    cursor: pointer;
}
.dp-file-drop-zone.drag-over { background: #dbeafe; border-color: #2271b1; }

/* Mapping-Tabelle */
.dp-mapping-table td, .dp-mapping-table th { padding: 0.5rem 0.75rem; vertical-align: middle; }
.dp-mapping-table tr.dp-required { background: #fefce8; }
.dp-mapping-table tr.dp-auto-matched { background: #f0fdf4; }
.dp-mapping-table select { width: 100%; }

/* Format-Sektionen */
.dp-format-section code { display: inline-block; background: #f3f4f6; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.85em; }
</style>

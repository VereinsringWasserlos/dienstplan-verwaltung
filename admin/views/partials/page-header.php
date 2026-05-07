<?php
/**
 * Einheitlicher Page Header für alle Verwaltungsseiten
 * 
 * Verwendung:
 * <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/page-header.php'; ?>
 * 
 * Erforderliche Variablen (vor dem Include setzen):
 * - $page_title (string): Seitentitel (z.B. 'Vereine')
 * - $page_icon (string): Dashicons-Klasse (z.B. 'dashicons-flag')
 * - $page_class (string): CSS-Klasse für Farbe (z.B. 'header-vereine')
 * - $nav_items (array): Array mit Navigationsbuttons
 *   Format: [
 *       ['label' => 'Dashboard', 'url' => '...', 'icon' => 'dashicons-dashboard', 'hide_on' => 'page-name'],
 *       ...
 *   ]
 * - $db (optional): Database-Objekt für Berechtigungen
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/admin/views/partials
 */

if (!defined('ABSPATH')) exit;

// Standardwerte setzen
$page_title = $page_title ?? 'Admin';
$page_icon = $page_icon ?? 'dashicons-admin-generic';
$page_class = 'header-unified';
$nav_items = $nav_items ?? [];
$page_meta_badges = $page_meta_badges ?? [];
$page_actions = $page_actions ?? [];
$current_page = $_GET['page'] ?? '';

// Mitbringen-Link in der Top-Navigation immer konsistent anzeigen (sofern Navigation genutzt wird).
if (!empty($nav_items) && is_array($nav_items)) {
    $has_mitbringen_nav = false;

    foreach ($nav_items as $nav_item) {
        if (!is_array($nav_item) || empty($nav_item['url'])) {
            continue;
        }

        $parts = wp_parse_url((string) $nav_item['url']);
        if (empty($parts['query'])) {
            continue;
        }

        parse_str($parts['query'], $query_vars);
        if (isset($query_vars['page']) && $query_vars['page'] === 'dienstplan-mitbringen') {
            $has_mitbringen_nav = true;
            break;
        }
    }

    if (!$has_mitbringen_nav) {
        $nav_items[] = array(
            'label' => __('Mitbringen', 'dienstplan-verwaltung'),
            'url' => admin_url('admin.php?page=dienstplan-mitbringen'),
            'icon' => 'dashicons-cart',
        );
    }
}

$can_manage_import = current_user_can('manage_options')
    || Dienstplan_Roles::can_manage_clubs()
    || Dienstplan_Roles::can_manage_events()
    || Dienstplan_Roles::can_manage_settings();

$dp_modal_veranstaltungen_js = array();
if ($can_manage_import) {
    if (!class_exists('Dienstplan_Database')) {
        require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
    }

    if (class_exists('Dienstplan_Database')) {
        $dp_modal_db = new Dienstplan_Database(DIENSTPLAN_DB_PREFIX);
        $dp_modal_veranstaltungen = $dp_modal_db->get_veranstaltungen();

        foreach ((array) $dp_modal_veranstaltungen as $v) {
            $id = isset($v->id) ? intval($v->id) : 0;
            $name = isset($v->name) ? (string) $v->name : '';
            $start = isset($v->start_datum) ? (string) $v->start_datum : '';
            $end = isset($v->end_datum) ? (string) $v->end_datum : '';

            if ($id <= 0 || $name === '') {
                continue;
            }

            $dp_modal_veranstaltungen_js[] = array(
                'id' => $id,
                'name' => $name,
                'start_datum' => $start,
                'end_datum' => $end,
            );
        }
    }
}

/**
 * Prueft, ob ein Navigationsziel fuer die aktuelle Rolle sichtbar sein soll.
 * Optional kann pro Item direkt 'capability' gesetzt werden.
 */
$can_show_nav_item = function($item) {
    if (!is_array($item)) {
        return false;
    }

    // Explizite Capability am Item hat Vorrang.
    if (!empty($item['capability'])) {
        return current_user_can('manage_options') || current_user_can($item['capability']);
    }

    if (empty($item['url'])) {
        return true;
    }

    $parts = wp_parse_url($item['url']);
    if (empty($parts['query'])) {
        return true;
    }

    parse_str($parts['query'], $query_vars);
    $target_page = isset($query_vars['page']) ? sanitize_text_field($query_vars['page']) : '';

    if (empty($target_page)) {
        return true;
    }

    if (in_array($target_page, array('dienstplan-import', 'dienstplan-export', 'dienstplan-import-export'), true)) {
        return current_user_can('manage_options')
            || Dienstplan_Roles::can_manage_clubs()
            || Dienstplan_Roles::can_manage_events()
            || Dienstplan_Roles::can_manage_settings();
    }

    if (in_array($target_page, array('dienstplan-dienste', 'dienstplan-mitbringen'), true)) {
        return current_user_can('manage_options')
            || Dienstplan_Roles::can_manage_clubs()
            || Dienstplan_Roles::can_manage_events();
    }

    // Eingeschränkte Vereins-Admins dürfen Veranstaltungen und Bereiche nicht sehen.
    if (in_array($target_page, array('dienstplan-veranstaltungen', 'dienstplan-bereiche'), true)) {
        if (Dienstplan_Roles::is_restricted_club_admin()) {
            return false;
        }

        if ($target_page === 'dienstplan-bereiche' && defined('DIENSTPLAN_SLIM_MODE') && DIENSTPLAN_SLIM_MODE) {
            return false;
        }
    }

    $required_capabilities = array(
        'dienstplan-vereine' => Dienstplan_Roles::CAP_MANAGE_CLUBS,
        'dienstplan-veranstaltungen' => Dienstplan_Roles::CAP_MANAGE_EVENTS,
        'dienstplan-bereiche' => Dienstplan_Roles::CAP_MANAGE_EVENTS,
        'dienstplan-mitarbeiter' => Dienstplan_Roles::CAP_MANAGE_EVENTS,
        'dienstplan-dienste' => Dienstplan_Roles::CAP_MANAGE_EVENTS,
        'dienstplan-mitbringen' => Dienstplan_Roles::CAP_MANAGE_EVENTS,
        'dienstplan-overview' => Dienstplan_Roles::CAP_MANAGE_EVENTS,
        'dienstplan-einstellungen' => Dienstplan_Roles::CAP_MANAGE_SETTINGS,
        'dienstplan-mail' => Dienstplan_Roles::CAP_MANAGE_SETTINGS,
        'dienstplan-benutzer' => Dienstplan_Roles::CAP_MANAGE_USERS,
        'dienstplan-dokumentation' => 'read',
        'dienstplan-updates' => 'manage_options',
        'dienstplan-portal' => 'manage_options',
        'dienstplan-debug' => 'manage_options',
    );

    if (!isset($required_capabilities[$target_page])) {
        return true;
    }

    $capability = $required_capabilities[$target_page];
    return current_user_can('manage_options') || current_user_can($capability);
};
?>

<div class="dienstplan-page-header <?php echo esc_attr($page_class); ?>">
    
    <!-- Seiten-Titel mit Icon -->
    <div class="page-title-section">
        <span class="dashicons <?php echo esc_attr($page_icon); ?>"></span>
        <div>
            <h1><?php echo esc_html($page_title); ?></h1>
            <?php if (!empty($page_meta_badges) && is_array($page_meta_badges)): ?>
                <div style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-top:0.45rem;">
                    <?php foreach ($page_meta_badges as $badge): ?>
                        <?php
                        $badge_label = isset($badge['label']) ? (string) $badge['label'] : '';
                        if ($badge_label === '') {
                            continue;
                        }

                        $badge_tone = isset($badge['tone']) ? (string) $badge['tone'] : 'neutral';
                        $badge_styles = array(
                            'neutral' => 'background: rgba(255,255,255,0.18); color: #ffffff; border: 1px solid rgba(255,255,255,0.28);',
                            'info' => 'background: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd;',
                            'success' => 'background: #dcfce7; color: #166534; border: 1px solid #86efac;'
                        );
                        $badge_style = isset($badge_styles[$badge_tone]) ? $badge_styles[$badge_tone] : $badge_styles['neutral'];
                        ?>
                        <span style="display:inline-flex; align-items:center; gap:0.35rem; padding:0.25rem 0.6rem; border-radius:999px; font-size:0.78rem; font-weight:700; line-height:1.1; <?php echo esc_attr($badge_style); ?>">
                            <?php echo esc_html($badge_label); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Navigation Buttons -->
    <div class="dienstplan-page-nav">
        <?php 
        foreach ($nav_items as $item):
            // Skip auf aktueller Seite, falls hide_on gesetzt
            if (isset($item['hide_on']) && $item['hide_on'] === $current_page) {
                continue;
            }

            // Links je Rolle/Berechtigung ausblenden
            if (!$can_show_nav_item($item)) {
                continue;
            }
            
            $icon = isset($item['icon']) ? $item['icon'] : 'dashicons-admin-generic';
            $label = $item['label'] ?? 'Link';
            $url = $item['url'] ?? '#';
        ?>
            <a href="<?php echo esc_url($url); ?>" class="page-nav-button">
                <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>

        <?php foreach ($page_actions as $action): ?>
            <?php
            if (!is_array($action)) {
                continue;
            }

            if (!empty($action['show']) && !$action['show']) {
                continue;
            }

            $action_label = isset($action['label']) ? (string) $action['label'] : '';
            if ($action_label === '') {
                continue;
            }

            $action_icon = isset($action['icon']) ? (string) $action['icon'] : 'dashicons-plus-alt';
            $action_class = isset($action['class']) ? (string) $action['class'] : 'page-nav-button';
            $action_style = isset($action['style']) ? (string) $action['style'] : '';
            $action_attrs = isset($action['attrs']) ? (string) $action['attrs'] : '';
            $action_href = isset($action['url']) ? (string) $action['url'] : '';
            ?>

            <?php if ($action_href !== ''): ?>
                <a href="<?php echo esc_url($action_href); ?>" class="<?php echo esc_attr($action_class); ?>"<?php echo $action_style !== '' ? ' style="' . esc_attr($action_style) . '"' : ''; ?> <?php echo $action_attrs; ?>>
                    <span class="dashicons <?php echo esc_attr($action_icon); ?>"></span>
                    <?php echo esc_html($action_label); ?>
                </a>
            <?php else: ?>
                <button type="button" class="<?php echo esc_attr($action_class); ?>"<?php echo $action_style !== '' ? ' style="' . esc_attr($action_style) . '"' : ''; ?> <?php echo $action_attrs; ?>>
                    <span class="dashicons <?php echo esc_attr($action_icon); ?>"></span>
                    <?php echo esc_html($action_label); ?>
                </button>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
</div>

<?php if ($can_manage_import): ?>
<div id="dp-import-wizard-modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.62); z-index:100001; align-items:center; justify-content:center;">
    <div style="width:min(1100px, 96vw); max-height:min(860px, 92vh); background:#fff; border-radius:10px; overflow:hidden; border:1px solid #cbd5e1; box-shadow:0 24px 65px rgba(0,0,0,0.32); display:flex; flex-direction:column;">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:0.85rem 1rem; border-bottom:1px solid #e2e8f0; background:#f8fafc;">
            <strong style="display:flex; align-items:center; gap:0.4rem;"><span class="dashicons dashicons-upload"></span><?php _e('CSV-Import', 'dienstplan-verwaltung'); ?></strong>
            <button type="button" id="dp-import-wizard-close" class="button" aria-label="<?php esc_attr_e('Schließen', 'dienstplan-verwaltung'); ?>">&times;</button>
        </div>

        <div style="padding:1rem; overflow:auto;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; align-items:start;">
                <div style="border:1px solid #e2e8f0; border-radius:8px; padding:0.9rem; background:#fff;">
                    <h3 style="margin:0 0 0.75rem; font-size:1rem;"><?php _e('1) Datei wählen', 'dienstplan-verwaltung'); ?></h3>
                    <div style="display:flex; gap:0.6rem; flex-wrap:wrap; align-items:center; margin-bottom:0.6rem;">
                        <span id="dp-modal-import-type-badge" style="display:inline-flex; align-items:center; gap:0.35rem; padding:0.22rem 0.6rem; border-radius:999px; font-size:0.78rem; font-weight:700; background:#dbeafe; color:#1d4ed8;"></span>
                    </div>

                    <input type="file" id="dp-modal-import-file" accept=".csv,.txt" style="display:block; width:100%; margin-bottom:0.5rem;">

                    <label for="dp-modal-import-mode" style="display:block; font-weight:600; margin:0.45rem 0 0.2rem;"><?php _e('Import-Modus', 'dienstplan-verwaltung'); ?></label>
                    <select id="dp-modal-import-mode" style="width:100%;">
                        <option value="create"><?php _e('Neu anlegen (bestehende nicht ändern)', 'dienstplan-verwaltung'); ?></option>
                        <option value="update"><?php _e('Vorhandene aktualisieren', 'dienstplan-verwaltung'); ?></option>
                    </select>

                    <div id="dp-modal-event-select-wrap" style="display:none; margin-top:0.5rem;">
                        <label for="dp-modal-veranstaltung-select" style="display:block; font-weight:600; margin:0 0 0.2rem;"><?php _e('Veranstaltung', 'dienstplan-verwaltung'); ?></label>
                        <select id="dp-modal-veranstaltung-select" style="width:100%;">
                            <option value=""><?php _e('-- Bitte auswählen --', 'dienstplan-verwaltung'); ?></option>
                            <?php foreach ($dp_modal_veranstaltungen_js as $ev): ?>
                                <option value="<?php echo esc_attr((string) $ev['id']); ?>">
                                    <?php echo esc_html($ev['name']); ?>
                                    <?php if (!empty($ev['start_datum']) || !empty($ev['end_datum'])): ?>
                                        (<?php echo esc_html($ev['start_datum']); ?><?php echo !empty($ev['end_datum']) ? ' - ' . esc_html($ev['end_datum']) : ''; ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="display:block; margin-top:0.25rem; color:#64748b;"><?php _e('Bei Dienste-Import wird die Veranstaltung über den Namen ausgewählt.', 'dienstplan-verwaltung'); ?></small>
                    </div>

                    <div id="dp-modal-import-file-info" style="margin-top:0.65rem; font-size:0.88rem; color:#475569;"></div>
                </div>

                <div style="border:1px solid #e2e8f0; border-radius:8px; padding:0.9rem; background:#fff;">
                    <h3 style="margin:0 0 0.75rem; font-size:1rem;"><?php _e('2) Validierung', 'dienstplan-verwaltung'); ?></h3>
                    <div id="dp-modal-validation-box" style="font-size:0.9rem; color:#334155;"><?php _e('Bitte Import-Typ wählen und CSV-Datei laden.', 'dienstplan-verwaltung'); ?></div>
                    <div id="dp-modal-mapping-wrap" style="display:none; margin-top:0.8rem;">
                        <h4 style="margin:0 0 0.45rem; font-size:0.9rem;"><?php _e('Feld-Mapping', 'dienstplan-verwaltung'); ?></h4>
                        <div style="overflow:auto; border:1px solid #e2e8f0; border-radius:6px; max-height:220px;">
                            <table class="widefat striped" style="margin:0; min-width:420px;">
                                <thead>
                                    <tr>
                                        <th><?php _e('Zielfeld', 'dienstplan-verwaltung'); ?></th>
                                        <th><?php _e('CSV-Spalte', 'dienstplan-verwaltung'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="dp-modal-mapping-body"></tbody>
                            </table>
                        </div>
                        <small style="display:block; margin-top:0.35rem; color:#64748b;"><?php _e('Falls Header nicht automatisch erkannt werden, hier manuell zuordnen.', 'dienstplan-verwaltung'); ?></small>
                    </div>
                    <div id="dp-modal-preview-wrap" style="display:none; margin-top:0.8rem;">
                        <h4 style="margin:0 0 0.45rem; font-size:0.9rem;"><?php _e('Vorschau (erste 5 Zeilen)', 'dienstplan-verwaltung'); ?></h4>
                        <div style="overflow:auto; border:1px solid #e2e8f0; border-radius:6px;">
                            <table id="dp-modal-preview-table" class="widefat striped" style="margin:0; min-width:600px;">
                                <thead></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top:1rem; border:1px solid #e2e8f0; border-radius:8px; padding:0.9rem; background:#f8fafc;">
                <h3 style="margin:0 0 0.6rem; font-size:1rem;"><?php _e('3) Import ausführen', 'dienstplan-verwaltung'); ?></h3>
                <div style="display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap;">
                    <button type="button" id="dp-modal-import-start" class="button button-primary" disabled>
                        <span class="dashicons dashicons-database-import" style="font-size:15px;width:15px;height:15px;margin-top:2px;"></span>
                        <?php _e('Jetzt importieren', 'dienstplan-verwaltung'); ?>
                    </button>
                    <span id="dp-modal-import-progress" style="font-size:0.88rem; color:#334155;"></span>
                </div>
                <div id="dp-modal-import-result" style="margin-top:0.7rem;"></div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const wizardModal = document.getElementById('dp-import-wizard-modal');
    const wizardClose = document.getElementById('dp-import-wizard-close');
    const fileInput = document.getElementById('dp-modal-import-file');
    const modeInput = document.getElementById('dp-modal-import-mode');
    const eventSelectWrap = document.getElementById('dp-modal-event-select-wrap');
    const eventSelect = document.getElementById('dp-modal-veranstaltung-select');
    const badge = document.getElementById('dp-modal-import-type-badge');
    const fileInfo = document.getElementById('dp-modal-import-file-info');
    const validationBox = document.getElementById('dp-modal-validation-box');
    const mappingWrap = document.getElementById('dp-modal-mapping-wrap');
    const mappingBody = document.getElementById('dp-modal-mapping-body');
    const previewWrap = document.getElementById('dp-modal-preview-wrap');
    const previewHead = document.querySelector('#dp-modal-preview-table thead');
    const previewBody = document.querySelector('#dp-modal-preview-table tbody');
    const startButton = document.getElementById('dp-modal-import-start');
    const progressText = document.getElementById('dp-modal-import-progress');
    const resultBox = document.getElementById('dp-modal-import-result');

    if (!wizardModal || !wizardClose || !fileInput || !startButton || !validationBox) {
        return;
    }

    const importOptions = {
        dienstplan: { label: 'Alter Dienstplan' },
        vereine: { label: 'Vereine' },
        veranstaltungen: { label: 'Veranstaltungen' },
        bereiche: { label: 'Bereiche' },
        taetigkeiten: { label: 'Taetigkeiten' },
        dienste: { label: 'Dienste' }
    };

    const veranstaltungen = <?php echo wp_json_encode($dp_modal_veranstaltungen_js); ?>;

    const fieldDefs = {
        dienstplan: [
            { key: 'datum', required: false },
            { key: 'wochentag', required: false },
            { key: 'tag_nummer', required: false },
            { key: 'tag_id', required: false },
            { key: 'von_zeit', required: false },
            { key: 'bis_zeit', required: false },
            { key: 'bereich_name', required: false },
            { key: 'bereich_farbe', required: false },
            { key: 'bereich_admin_only', required: false },
            { key: 'taetigkeit_name', required: false },
            { key: 'taetigkeit_admin_only', required: false },
            { key: 'verein_kuerzel', required: false },
            { key: 'verein_name', required: false },
            { key: 'anzahl_personen', required: false },
            { key: 'splittbar', required: false },
            { key: 'besonderheiten', required: false }
        ],
        bereiche: [
            { key: 'name', required: true },
            { key: 'farbe', required: false },
            { key: 'aktiv', required: false },
            { key: 'sortierung', required: false },
            { key: 'admin_only', required: false }
        ],
        taetigkeiten: [
            { key: 'bereich_name', required: true },
            { key: 'name', required: true },
            { key: 'beschreibung', required: false },
            { key: 'aktiv', required: false },
            { key: 'sortierung', required: false },
            { key: 'admin_only', required: false }
        ],
        vereine: [
            { key: 'name', required: true },
            { key: 'kuerzel', required: true },
            { key: 'beschreibung', required: false },
            { key: 'kontakt_name', required: false },
            { key: 'kontakt_email', required: false },
            { key: 'kontakt_telefon', required: false }
        ],
        veranstaltungen: [
            { key: 'name', required: true },
            { key: 'start_datum', required: true },
            { key: 'end_datum', required: true },
            { key: 'beschreibung', required: false },
            { key: 'dienst_von_zeit', required: false },
            { key: 'dienst_bis_zeit', required: false }
        ],
        dienste: [
            { key: 'datum', required: false },
            { key: 'wochentag', required: false },
            { key: 'tag_nummer', required: false },
            { key: 'tag_id', required: false },
            { key: 'dienst_typ', required: false },
            { key: 'bereich_name', required: false },
            { key: 'taetigkeit_name', required: false },
            { key: 'von_zeit', required: false },
            { key: 'bis_zeit', required: false },
            { key: 'verein_kuerzel', required: false },
            { key: 'anzahl_personen', required: false },
            { key: 'splittbar', required: false },
            { key: 'admin_only', required: false },
            { key: 'besonderheiten', required: false }
        ]
    };

    const state = {
        type: '',
        file: null,
        headers: [],
        rows: [],
        mapping: {},
        isValid: false,
        isImporting: false
    };

    function normalizeHeader(value) {
        return String(value || '').toLowerCase().trim().replace(/[\s-]+/g, '_');
    }

    function decodeCsvBytes(bytes) {
        if (bytes.length >= 3 && bytes[0] === 0xEF && bytes[1] === 0xBB && bytes[2] === 0xBF) {
            return new TextDecoder('utf-8').decode(bytes);
        }
        try {
            const latin = new TextDecoder('iso-8859-1').decode(bytes);
            if (!/\ufffd/.test(latin)) {
                return latin;
            }
        } catch (e) {
        }
        return new TextDecoder('utf-8').decode(bytes);
    }

    function parseCsvLine(line, sep) {
        const result = [];
        let cur = '';
        let inQ = false;
        for (let i = 0; i < line.length; i++) {
            const c = line[i];
            if (c === '"') {
                if (inQ && line[i + 1] === '"') {
                    cur += '"';
                    i++;
                } else {
                    inQ = !inQ;
                }
            } else if (c === sep && !inQ) {
                result.push(cur.trim());
                cur = '';
            } else {
                cur += c;
            }
        }
        result.push(cur.trim());
        return result;
    }

    function escHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function setResult(html, tone) {
        const border = tone === 'error' ? '#fecaca' : (tone === 'success' ? '#86efac' : '#cbd5e1');
        const background = tone === 'error' ? '#fff1f2' : (tone === 'success' ? '#ecfdf5' : '#f8fafc');
        resultBox.innerHTML = '<div style="padding:0.65rem 0.75rem; border:1px solid ' + border + '; background:' + background + '; border-radius:6px;">' + html + '</div>';
    }

    function collectMappingFromUi() {
        state.mapping = {};
        if (!mappingBody) {
            return;
        }

        const selects = mappingBody.querySelectorAll('select[data-field-key]');
        selects.forEach(function (select) {
            const fieldKey = select.getAttribute('data-field-key');
            const value = select.value;
            if (fieldKey && value !== '') {
                state.mapping[fieldKey] = parseInt(value, 10);
            }
        });
    }

    function buildMappingTable(defs) {
        if (!mappingBody) {
            return;
        }

        mappingBody.innerHTML = '';
        if (!defs.length || !state.headers.length) {
            mappingWrap.style.display = 'none';
            return;
        }

        const normalizedHeaders = state.headers.map(function (header) {
            return normalizeHeader(header);
        });
        const hasDatumHeader = normalizedHeaders.indexOf('datum') !== -1;
        const hasAlternativeTagHeader =
            normalizedHeaders.indexOf('tag_nummer') !== -1 ||
            normalizedHeaders.indexOf('tag_id') !== -1 ||
            normalizedHeaders.indexOf('wochentag') !== -1;

        defs.forEach(function (field) {
            // Bei Dienste/Dienstplan ohne Datumsspalte blenden wir das Datum-Feld aus,
            // wenn stattdessen ein klares Tag-Mapping vorhanden ist.
            if (
                (state.type === 'dienste' || state.type === 'dienstplan') &&
                field.key === 'datum' &&
                !hasDatumHeader &&
                hasAlternativeTagHeader
            ) {
                return;
            }

            const tr = document.createElement('tr');

            const tdField = document.createElement('td');
            tdField.textContent = field.key + (field.required ? ' *' : '');
            if (field.required) {
                tdField.style.color = '#b91c1c';
                tdField.style.fontWeight = '600';
            }

            const tdMap = document.createElement('td');
            const select = document.createElement('select');
            select.setAttribute('data-field-key', field.key);
            select.style.width = '100%';

            const emptyOpt = document.createElement('option');
            emptyOpt.value = '';
            emptyOpt.textContent = '-- nicht zuordnen --';
            select.appendChild(emptyOpt);

            state.headers.forEach(function (header, idx) {
                const opt = document.createElement('option');
                opt.value = String(idx);
                opt.textContent = header;
                select.appendChild(opt);
            });

            const wanted = normalizeHeader(field.key);
            let autoIdx = -1;
            state.headers.forEach(function (header, idx) {
                if (autoIdx !== -1) {
                    return;
                }
                if (normalizeHeader(header) === wanted) {
                    autoIdx = idx;
                }
            });
            if (autoIdx !== -1) {
                select.value = String(autoIdx);
            }

            select.addEventListener('change', function () {
                collectMappingFromUi();
                validateCsvForType();
            });

            tdMap.appendChild(select);
            tr.appendChild(tdField);
            tr.appendChild(tdMap);
            mappingBody.appendChild(tr);
        });

        mappingWrap.style.display = 'block';
        collectMappingFromUi();
    }

    function getSelectedVeranstaltung() {
        if (!eventSelect) {
            return null;
        }
        const eventId = parseInt(eventSelect.value || '0', 10);
        if (!eventId) {
            return null;
        }

        for (let i = 0; i < veranstaltungen.length; i++) {
            const ev = veranstaltungen[i];
            if (parseInt(ev.id, 10) === eventId) {
                return ev;
            }
        }
        return null;
    }

    function getEventDaySpan(ev) {
        if (!ev || !ev.start_datum || !ev.end_datum) {
            return 0;
        }

        const start = new Date(String(ev.start_datum) + 'T00:00:00');
        const end = new Date(String(ev.end_datum) + 'T00:00:00');
        if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) {
            return 0;
        }

        const msPerDay = 24 * 60 * 60 * 1000;
        return Math.floor((end - start) / msPerDay) + 1;
    }

    function renderPreview() {
        previewHead.innerHTML = '';
        previewBody.innerHTML = '';

        if (!state.headers.length || !state.rows.length) {
            previewWrap.style.display = 'none';
            return;
        }

        const trHead = document.createElement('tr');
        state.headers.forEach(function (header) {
            const th = document.createElement('th');
            th.textContent = header;
            trHead.appendChild(th);
        });
        previewHead.appendChild(trHead);

        state.rows.slice(0, 5).forEach(function (row) {
            const tr = document.createElement('tr');
            state.headers.forEach(function (_, idx) {
                const td = document.createElement('td');
                td.textContent = row[idx] || '';
                tr.appendChild(td);
            });
            previewBody.appendChild(tr);
        });

        previewWrap.style.display = 'block';
    }

    function validateCsvForType() {
        state.isValid = false;
        state.mapping = {};

        const defs = fieldDefs[state.type] || [];
        if (!defs.length) {
            validationBox.innerHTML = '<span style="color:#b91c1c;"><?php echo esc_js(__('Unbekannter Import-Typ.', 'dienstplan-verwaltung')); ?></span>';
            updateStartButton();
            return;
        }

        if (!state.headers.length || !state.rows.length) {
            validationBox.innerHTML = '<span style="color:#b91c1c;"><?php echo esc_js(__('CSV enthält keine Datenzeilen.', 'dienstplan-verwaltung')); ?></span>';
            updateStartButton();
            return;
        }

        if (mappingBody && mappingBody.childElementCount === 0) {
            buildMappingTable(defs);
        }

        collectMappingFromUi();

        const missingRequired = defs.filter(function (field) {
            return field.required && typeof state.mapping[field.key] === 'undefined';
        }).map(function (field) {
            return field.key;
        });

        // Für Dienste/Dienstplan ist Datum optional, wenn alternativ Tag-Mapping vorhanden ist.
        if (state.type === 'dienste' || state.type === 'dienstplan') {
            const hasDate = typeof state.mapping.datum !== 'undefined';
            const hasTagId = typeof state.mapping.tag_id !== 'undefined';
            const hasTagNummer = typeof state.mapping.tag_nummer !== 'undefined';
            const hasWeekday = typeof state.mapping.wochentag !== 'undefined';

            const hasDateOrTagMapping =
                hasDate || hasWeekday || hasTagNummer || hasTagId;

            if (!hasDateOrTagMapping) {
                validationBox.innerHTML = '<span style="color:#b91c1c;"><?php echo esc_js(__('Tag ist Pflicht: Bitte mindestens eines mappen (datum, tag_id, tag_nummer oder wochentag).', 'dienstplan-verwaltung')); ?></span>';
                updateStartButton();
                return;
            }

            // Bei mehrtägigen Veranstaltungen Wochentag nicht als einzige Tageslogik erlauben.
            if (hasWeekday && !hasDate && !hasTagId && !hasTagNummer) {
                const selectedEvent = getSelectedVeranstaltung();
                const daySpan = getEventDaySpan(selectedEvent);
                if (daySpan > 1) {
                    validationBox.innerHTML = '<span style="color:#b91c1c;"><?php echo esc_js(__('Bei mehrtägigen Veranstaltungen reicht Wochentag allein nicht aus. Bitte datum, tag_id oder tag_nummer mappen.', 'dienstplan-verwaltung')); ?></span>';
                    updateStartButton();
                    return;
                }
            }
        }

        const needsEvent = state.type === 'dienste' || state.type === 'dienstplan';
        const hasEvent = needsEvent ? !!getSelectedVeranstaltung() : true;

        if (missingRequired.length > 0) {
            validationBox.innerHTML = '<span style="color:#b91c1c;"><?php echo esc_js(__('Pflichtfelder fehlen in der CSV:', 'dienstplan-verwaltung')); ?> <strong>' + missingRequired.join(', ') + '</strong></span>';
            updateStartButton();
            return;
        }

        if (!hasEvent) {
            validationBox.innerHTML = '<span style="color:#b91c1c;"><?php echo esc_js(__('Bitte Veranstaltung für diesen Import-Typ auswählen.', 'dienstplan-verwaltung')); ?></span>';
            updateStartButton();
            return;
        }

        const mappedKeys = Object.keys(state.mapping);
        let validationText = '<span style="color:#166534;"><?php echo esc_js(__('Validierung erfolgreich.', 'dienstplan-verwaltung')); ?></span> ' +
            '<?php echo esc_js(__('Zugeordnete Felder:', 'dienstplan-verwaltung')); ?> <strong>' + mappedKeys.length + '</strong>, ' +
            '<?php echo esc_js(__('Datenzeilen:', 'dienstplan-verwaltung')); ?> <strong>' + state.rows.length + '</strong>';

        if ((state.type === 'dienste' || state.type === 'dienstplan') && typeof state.mapping.datum === 'undefined') {
            if (typeof state.mapping.tag_nummer !== 'undefined' || typeof state.mapping.tag_id !== 'undefined' || typeof state.mapping.wochentag !== 'undefined') {
                validationText += '<br><span style="color:#075985;"><?php echo esc_js(__('Hinweis: Kein Datum gemappt - Tag-Zuordnung erfolgt über Tag-Felder.', 'dienstplan-verwaltung')); ?></span>';
            }
        }

        validationBox.innerHTML = validationText;

        state.isValid = true;
        updateStartButton();
    }

    function updateStartButton() {
        startButton.disabled = !(state.isValid && !state.isImporting);
    }

    function resetModalState() {
        state.file = null;
        state.headers = [];
        state.rows = [];
        state.mapping = {};
        state.isValid = false;
        state.isImporting = false;
        fileInput.value = '';
        if (eventSelect) {
            eventSelect.value = '';
        }
        fileInfo.textContent = '';
        validationBox.textContent = '<?php echo esc_js(__('Bitte Import-Typ wählen und CSV-Datei laden.', 'dienstplan-verwaltung')); ?>';
        if (mappingBody) {
            mappingBody.innerHTML = '';
        }
        if (mappingWrap) {
            mappingWrap.style.display = 'none';
        }
        previewHead.innerHTML = '';
        previewBody.innerHTML = '';
        previewWrap.style.display = 'none';
        progressText.textContent = '';
        resultBox.innerHTML = '';
        updateStartButton();
    }

    function openImportWizard(typeKey) {
        const option = importOptions[typeKey] || null;
        if (!option) {
            return;
        }

        resetModalState();
        state.type = typeKey;
        badge.textContent = option.label;
        eventSelectWrap.style.display = (typeKey === 'dienste' || typeKey === 'dienstplan') ? 'block' : 'none';
        if ((typeKey === 'dienste' || typeKey === 'dienstplan') && eventSelect && eventSelect.options.length === 2) {
            eventSelect.selectedIndex = 1;
        }
        wizardModal.style.display = 'flex';
    }

    window.dpOpenImportWizard = openImportWizard;

    function closeImportWizard() {
        wizardModal.style.display = 'none';
        resetModalState();
    }

    function readSelectedFile(file) {
        if (!file) {
            return;
        }

        if (!/\.(csv|txt)$/i.test(file.name)) {
            setResult('<?php echo esc_js(__('Bitte eine CSV-Datei auswählen.', 'dienstplan-verwaltung')); ?>', 'error');
            return;
        }

        const reader = new FileReader();
        reader.onload = function (evt) {
            const bytes = new Uint8Array(evt.target.result);
            const text = decodeCsvBytes(bytes);
            const lines = text.split(/\r?\n/).filter(function (line) {
                return line.trim() !== '';
            });

            if (lines.length < 2) {
                state.headers = [];
                state.rows = [];
                fileInfo.textContent = file.name + ' (0 ' + '<?php echo esc_js(__('Datenzeilen', 'dienstplan-verwaltung')); ?>' + ')';
                validateCsvForType();
                renderPreview();
                return;
            }

            const separator = lines[0].indexOf(';') !== -1 ? ';' : ',';
            state.headers = parseCsvLine(lines[0], separator);
            state.rows = lines.slice(1).map(function (line) {
                return parseCsvLine(line, separator);
            });

            fileInfo.textContent = file.name + ' (' + state.rows.length + ' ' + '<?php echo esc_js(__('Datenzeilen', 'dienstplan-verwaltung')); ?>' + ')';
            buildMappingTable(fieldDefs[state.type] || []);
            renderPreview();
            validateCsvForType();
            setResult('<?php echo esc_js(__('CSV wurde gelesen und geprüft.', 'dienstplan-verwaltung')); ?>', 'neutral');
        };

        reader.onerror = function () {
            setResult('<?php echo esc_js(__('Datei konnte nicht gelesen werden.', 'dienstplan-verwaltung')); ?>', 'error');
        };

        reader.readAsArrayBuffer(file);
    }

    function startImport() {
        if (!state.isValid || state.isImporting) {
            return;
        }

        state.isImporting = true;
        updateStartButton();
        progressText.textContent = '<?php echo esc_js(__('Import läuft...', 'dienstplan-verwaltung')); ?>';
        resultBox.innerHTML = '';

        const payload = new URLSearchParams();
        payload.append('action', 'dp_import_csv');
        payload.append('nonce', (window.dpAjax && window.dpAjax.nonce) ? window.dpAjax.nonce : '');
        payload.append('import_type', state.type);
        payload.append('import_mode', modeInput.value || 'create');
        payload.append('csv_data', JSON.stringify(state.rows));
        payload.append('mapping', JSON.stringify(state.mapping));

        if (state.type === 'dienste' || state.type === 'dienstplan') {
            const selectedEvent = getSelectedVeranstaltung();
            if (selectedEvent) {
                payload.append('veranstaltung_id', String(parseInt(selectedEvent.id, 10)));
                payload.append('veranstaltung_start', String(selectedEvent.start_datum || ''));
                payload.append('veranstaltung_ende', String(selectedEvent.end_datum || ''));
            }
        }

        fetch((window.dpAjax && window.dpAjax.ajaxurl) ? window.dpAjax.ajaxurl : '<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            credentials: 'same-origin',
            body: payload.toString()
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (response) {
                if (response && response.success && response.data) {
                    const data = response.data;
                    let html = '<strong><?php echo esc_js(__('Import erfolgreich abgeschlossen.', 'dienstplan-verwaltung')); ?></strong><ul style="margin:0.35rem 0 0 1.1rem;">';
                    html += '<li><?php echo esc_js(__('Erstellt', 'dienstplan-verwaltung')); ?>: <strong>' + (data.created || 0) + '</strong></li>';
                    html += '<li><?php echo esc_js(__('Aktualisiert', 'dienstplan-verwaltung')); ?>: <strong>' + (data.updated || 0) + '</strong></li>';
                    html += '<li><?php echo esc_js(__('Übersprungen', 'dienstplan-verwaltung')); ?>: <strong>' + (data.skipped || 0) + '</strong></li>';
                    html += '<li><?php echo esc_js(__('Fehler', 'dienstplan-verwaltung')); ?>: <strong>' + (data.errors || 0) + '</strong></li>';
                    html += '</ul>';

                    if (Array.isArray(data.error_details) && data.error_details.length) {
                        const details = data.error_details.slice(0, 8).map(function (msg) {
                            return '<li>' + escHtml(String(msg)) + '</li>';
                        }).join('');
                        html += '<div style="margin-top:0.6rem;"><strong><?php echo esc_js(__('Details', 'dienstplan-verwaltung')); ?>:</strong><ul style="margin:0.25rem 0 0 1.1rem;">' + details + '</ul></div>';
                    }

                    setResult(html, 'success');
                } else {
                    const message = response && response.data && response.data.message
                        ? response.data.message
                        : '<?php echo esc_js(__('Import fehlgeschlagen.', 'dienstplan-verwaltung')); ?>';
                    setResult('<strong>' + escHtml(message) + '</strong>', 'error');
                }
            })
            .catch(function () {
                setResult('<strong><?php echo esc_js(__('Netzwerk- oder Serverfehler beim Import.', 'dienstplan-verwaltung')); ?></strong>', 'error');
            })
            .finally(function () {
                state.isImporting = false;
                progressText.textContent = '';
                updateStartButton();
            });
    }

    fileInput.addEventListener('change', function (event) {
        state.file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
        readSelectedFile(state.file);
    });

    if (eventSelect) {
        eventSelect.addEventListener('change', function () {
            validateCsvForType();
        });
    }

    startButton.addEventListener('click', function () {
        startImport();
    });

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('.dp-open-import-popup');
        if (trigger) {
            event.preventDefault();
            const importType = (trigger.getAttribute('data-import-type') || '').trim();
            if (importType !== '') {
                openImportWizard(importType);
            }
            return;
        }

        if (event.target === wizardModal) {
            closeImportWizard();
        }
    }, true);

    wizardClose.addEventListener('click', closeImportWizard);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && wizardModal.style.display === 'flex') {
            closeImportWizard();
        }
    });
})();
</script>
<?php endif; ?>

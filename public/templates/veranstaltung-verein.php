<?php
/**
 * Frontend Veranstaltungs-Detail View - Verein-spezifisch
 * Zeigt nur Dienste eines bestimmten Vereins für eine Veranstaltung
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/public/templates
 * 
 * Variablen:
 * $veranstaltung - Veranstaltungsobjekt
 * $verein - Vereinsobjekt (optional, wenn verein_id gesetzt)
 * $verein_id - ID des Vereins (kann 0 sein für alle Vereine)
 * $veranstaltung_id - ID der Veranstaltung
 */

if (!defined('ABSPATH')) exit;

require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-dienstplan-roles.php';

$is_logged_in = is_user_logged_in();
$current_user_id = get_current_user_id();
$can_manage_dienste = current_user_can('manage_options') || Dienstplan_Roles::can_manage_events() || Dienstplan_Roles::can_manage_clubs();
$admin_dienste_url = admin_url('admin.php?page=dienstplan-dienste');
$current_mitarbeiter_id = 0;
$current_mitarbeiter = null;
$current_user_obj = null;
$dp_prefix = defined('DIENSTPLAN_DB_PREFIX') ? DIENSTPLAN_DB_PREFIX : 'dp_';

if ($is_logged_in && $current_user_id > 0) {
    global $wpdb;
    $mitarbeiter_table = $wpdb->prefix . $dp_prefix . 'mitarbeiter';
    $current_mitarbeiter_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$mitarbeiter_table} WHERE user_id = %d LIMIT 1",
        $current_user_id
    ));

    $current_user_obj = get_user_by('id', $current_user_id);
    if ($current_mitarbeiter_id > 0) {
        $current_mitarbeiter = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$mitarbeiter_table} WHERE id = %d LIMIT 1",
            $current_mitarbeiter_id
        ));
    }
}

// Lade alle Dienste der Veranstaltung
$all_services = $db->get_dienste($veranstaltung_id);

// Filtere Dienste nach Verein (wenn verein_id gesetzt)
if ($verein_id > 0) {
    $services = array_filter($all_services, function($s) use ($verein_id) {
        return intval($s->verein_id) === intval($verein_id);
    });
} else {
    $services = $all_services;
}

// Lade Veranstaltungstage
$tage = $db->get_veranstaltung_tage($veranstaltung_id);

// Lade Verantwortliche für Kontaktanzeige bei aktivem Status
$verantwortliche = array();
if ($veranstaltung->status === 'aktiv') {
    $verantwortliche = $db->get_veranstaltung_verantwortliche($veranstaltung_id);
}

// Gruppiere Dienste nach Tag
$dienste_nach_tagen = [];
foreach ($services as $service) {
    $tag_id = $service->tag_id ?? 0;
    if (!isset($dienste_nach_tagen[$tag_id])) {
        $dienste_nach_tagen[$tag_id] = [];
    }
    $dienste_nach_tagen[$tag_id][] = $service;
}
ksort($dienste_nach_tagen);

// Filterdaten (Arbeitsbereiche & Dienste)
$filter_bereiche = array();
$filter_dienste = array();
foreach ($services as $service) {
    $bereich_id = intval($service->bereich_id ?? 0);
    $taetigkeit_id = intval($service->taetigkeit_id ?? 0);

    if ($bereich_id > 0 && !isset($filter_bereiche[$bereich_id])) {
        $bereich = $db->get_bereich($bereich_id);
        if ($bereich && !empty($bereich->name)) {
            $filter_bereiche[$bereich_id] = $bereich->name;
        }
    }

    if ($taetigkeit_id > 0 && !isset($filter_dienste[$taetigkeit_id])) {
        $taetigkeit = $db->get_taetigkeit($taetigkeit_id);
        if ($taetigkeit && !empty($taetigkeit->name)) {
            $filter_dienste[$taetigkeit_id] = $taetigkeit->name;
        }
    }
}
asort($filter_bereiche);
asort($filter_dienste);

// Sammle alle Vereine aus den Diensten (für Anzeige wenn verein_id = 0)
$alle_vereine_in_services = [];
if ($verein_id == 0) {
    foreach ($services as $service) {
        $vid = intval($service->verein_id ?? 0);
        if ($vid > 0 && !isset($alle_vereine_in_services[$vid])) {
            $alle_vereine_in_services[$vid] = !empty($service->verein) ? $service->verein : '';
            if (empty($alle_vereine_in_services[$vid])) {
                $v_obj = $db->get_verein($vid);
                if ($v_obj) $alle_vereine_in_services[$vid] = $v_obj->name;
            }
        }
    }
    asort($alle_vereine_in_services);
}

// View-Modus
$view_mode = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'kachel';
if ($view_mode === 'list') {
    $view_mode = 'kachel';
}
if (!in_array($view_mode, array('kachel', 'kompakt', 'timeline'), true)) {
    $view_mode = 'kachel';
}
$dp_debug_enabled = isset($_GET['dpdebug']) || isset($_GET['debug']);
$current_request_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Status-Anzeige und Anmeldungs-Logik
$anmeldung_aktiv = ($veranstaltung->status === 'geplant');
$status_message = '';
$status_style = '';
$status_icon = '';

switch($veranstaltung->status) {
    case 'in_planung':
        $status_message = 'In Planung';
        $status_style = 'background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-color: #3b82f6; color: #1e40af;';
        $status_icon = '🔵';
        break;
    case 'geplant':
        $status_message = 'Anmeldung möglich';
        $status_style = 'background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); border-color: #22c55e; color: #166534;';
        $status_icon = '🟢';
        break;
    case 'aktiv':
        $status_message = 'Läuft';
        $status_style = 'background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-color: #f59e0b; color: #92400e;';
        $status_icon = '🟡';
        break;
    case 'abgeschlossen':
        $status_message = 'Abgeschlossen';
        $status_style = 'background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-color: #9ca3af; color: #374151;';
        $status_icon = '⚪';
        break;
}

$veranstaltungs_zeitraum_label = '';
if (!empty($veranstaltung->start_datum)) {
    $datum_von = new DateTime($veranstaltung->start_datum);
    if (!empty($veranstaltung->end_datum)) {
        $datum_bis = new DateTime($veranstaltung->end_datum);
        if ($datum_von->format('Y-m-d') === $datum_bis->format('Y-m-d')) {
            $veranstaltungs_zeitraum_label = $datum_von->format('d.m.Y');
        } else {
            $veranstaltungs_zeitraum_label = $datum_von->format('d.m.Y') . ' – ' . $datum_bis->format('d.m.Y');
        }
    } else {
        $veranstaltungs_zeitraum_label = $datum_von->format('d.m.Y');
    }
}

$dienst_zeitraum_label = '';
$tage_by_id = array();
foreach ($tage as $tag) {
    $tage_by_id[intval($tag->id)] = $tag;
}

$dienst_start_dt = null;
$dienst_end_dt = null;
foreach ($services as $service) {
    $tag_id = intval($service->tag_id ?? 0);
    if (!isset($tage_by_id[$tag_id])) {
        continue;
    }

    $tag_obj = $tage_by_id[$tag_id];
    $tag_date = $tag_obj->tag_datum ?? ($tag_obj->datum ?? null);
    if (empty($tag_date)) {
        continue;
    }

    $von_zeit = $service->von_zeit ?? ($service->zeit_von ?? '00:00:00');
    $bis_zeit = $service->bis_zeit ?? ($service->zeit_bis ?? $von_zeit);

    try {
        $start_dt = new DateTime($tag_date . ' ' . $von_zeit);
        $end_dt = new DateTime($tag_date . ' ' . $bis_zeit);
    } catch (Exception $e) {
        continue;
    }

    if ($dienst_start_dt === null || $start_dt < $dienst_start_dt) {
        $dienst_start_dt = $start_dt;
    }
    if ($dienst_end_dt === null || $end_dt > $dienst_end_dt) {
        $dienst_end_dt = $end_dt;
    }
}

if ($dienst_start_dt !== null && $dienst_end_dt !== null) {
    if ($dienst_start_dt->format('Y-m-d') === $dienst_end_dt->format('Y-m-d')) {
        $dienst_zeitraum_label = $dienst_start_dt->format('d.m.Y H:i') . ' – ' . $dienst_end_dt->format('H:i');
    } else {
        $dienst_zeitraum_label = $dienst_start_dt->format('d.m.Y H:i') . ' – ' . $dienst_end_dt->format('d.m.Y H:i');
    }
}
?>

<div class="dp-frontend-container dp-verein-specific">
    <?php if ($dp_debug_enabled): ?>
        <div id="dp-debug-bootstrap" style="margin:0 0 1rem 0;padding:0.5rem 0.75rem;background:#111827;color:#e5e7eb;border-radius:6px;font:12px/1.4 monospace;">
            DP DEBUG BOOTSTRAP aktiv (Template geladen)
        </div>
    <?php endif; ?>

    <!-- Moderner Header mit optimiertem Layout -->
    <div class="dp-event-header" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
        <div class="dp-event-header-top" style="display: flex; justify-content: space-between; align-items: start; gap: 2rem; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px;">
                <div class="dp-title-row">
                    <h1 class="dp-event-title" style="margin: 0; font-size: 2rem; color: #0f172a;"><?php echo esc_html($veranstaltung->name); ?></h1>
                    <?php if (!empty($veranstaltungs_zeitraum_label)): ?>
                        <div class="dp-title-chip">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <span><strong>Veranstaltung:</strong> <?php echo esc_html($veranstaltungs_zeitraum_label); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($dienst_zeitraum_label)): ?>
                        <div class="dp-title-chip">
                            <span class="dashicons dashicons-clock"></span>
                            <span><strong>Dienstzeitraum:</strong> <?php echo esc_html($dienst_zeitraum_label); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <?php if ($verein_id > 0 && isset($verein)): ?>
                        <div class="dp-header-chip" style="background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); border-color: #0ea5e9; color: #0369a1;">
                            <span class="dashicons dashicons-groups" style="font-size: 16px;"></span>
                            <span><?php echo esc_html($verein->name); ?></span>
                        </div>
                    <?php elseif ($verein_id == 0 && !empty($alle_vereine_in_services)): ?>
                        <?php foreach ($alle_vereine_in_services as $vid => $vname): ?>
                            <div class="dp-header-chip" style="background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); border-color: #0ea5e9; color: #0369a1;">
                                <span class="dashicons dashicons-groups" style="font-size: 14px;"></span>
                                <span><?php echo esc_html($vname); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if ($status_message): ?>
                        <div class="dp-header-chip" style="<?php echo $status_style; ?>">
                            <span><?php echo $status_icon; ?></span>
                            <span><?php echo esc_html($status_message); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($can_manage_dienste): ?>
                        <a class="dp-header-chip dp-header-chip-link" style="background: #e0f2fe; border-color: #7dd3fc; color: #0c4a6e;" href="<?php echo esc_url($admin_dienste_url); ?>">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <span>Backend</span>
                        </a>
                    <?php endif; ?>

                    <?php if ($anmeldung_aktiv): ?>
                        <?php if ($is_logged_in): ?>
                            <a class="dp-header-chip dp-header-chip-link" style="background: #ffffff; border-color: #fca5a5; color: #991b1b;" href="<?php echo esc_url(wp_logout_url($current_request_url)); ?>">
                                <span class="dashicons dashicons-unlock"></span>
                                <span>Ausloggen</span>
                            </a>
                        <?php else: ?>
                            <button type="button" class="dp-header-chip dp-header-chip-link" style="background: #ffffff; border-color: #86efac; color: #166534;" onclick="dpOpenLoginModal()">
                                <span class="dashicons dashicons-lock"></span>
                                <span>Einloggen</span>
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dp-header-view-tools">
                <div class="dp-view-toggle dp-view-toggle-header" aria-label="Ansicht wechseln">
                    <a href="?veranstaltung_id=<?php echo $veranstaltung_id; ?><?php echo ($verein_id > 0 ? '&verein_id=' . $verein_id : ''); ?>&view=kachel" 
                       class="dp-view-btn <?php echo $view_mode === 'kachel' ? 'active' : ''; ?>" title="Kachelansicht" aria-label="Kachelansicht">
                        <span class="dp-view-icon-emoji" aria-hidden="true">🗂️</span>
                    </a>
                    <a href="?veranstaltung_id=<?php echo $veranstaltung_id; ?><?php echo ($verein_id > 0 ? '&verein_id=' . $verein_id : ''); ?>&view=kompakt" 
                       class="dp-view-btn <?php echo $view_mode === 'kompakt' ? 'active' : ''; ?>" title="Kompakte Liste" aria-label="Kompakte Liste">
                        <span class="dp-view-icon-emoji" aria-hidden="true">📋</span>
                    </a>
                    <a href="?veranstaltung_id=<?php echo $veranstaltung_id; ?><?php echo ($verein_id > 0 ? '&verein_id=' . $verein_id : ''); ?>&view=timeline" 
                       class="dp-view-btn <?php echo $view_mode === 'timeline' ? 'active' : ''; ?>" title="Timeline" aria-label="Timeline">
                        <span class="dp-view-icon-emoji" aria-hidden="true">📊</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php 
    // Kontaktinformationen bei Status "aktiv" anzeigen (für Absagen)
    if ($veranstaltung->status === 'aktiv' && (!empty($verantwortliche) || ($verein_id > 0 && isset($verein)))): 
    ?>
        <div class="dp-kontakt-box" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border: 2px solid #f97316; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(249, 115, 22, 0.1);">
            <h3 style="margin: 0 0 1rem 0; color: #9a3412; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem;">
                <span class="dashicons dashicons-phone" style="font-size: 24px;"></span>
                Kontakt für Absagen
            </h3>
            
            <p style="margin: 0 0 1rem 0; color: #9a3412; font-size: 0.95rem;">
                <strong>Wichtig:</strong> Falls Sie Ihren Dienst nicht wahrnehmen können, melden Sie sich bitte umgehend:
            </p>
                
                <div style="display: grid; gap: 1rem;">
                    <?php if ($verein_id > 0 && isset($verein)): ?>
                        <!-- Vereinskontakt -->
                        <div style="background: white; padding: 1rem; border-radius: 6px; border: 1px solid #fed7aa;">
                            <h4 style="margin: 0 0 0.5rem 0; color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
                                <span class="dashicons dashicons-groups" style="color: #f97316;"></span>
                                <?php echo esc_html($verein->name); ?>
                            </h4>
                            <?php if (!empty($verein->kontakt_name)): ?>
                                <p style="margin: 0.25rem 0; color: #475569;">
                                    <strong>Ansprechpartner:</strong> <?php echo esc_html($verein->kontakt_name); ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($verein->kontakt_telefon)): ?>
                                <p style="margin: 0.25rem 0; color: #475569;">
                                    <strong>Telefon:</strong> <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $verein->kontakt_telefon)); ?>" style="color: #f97316; text-decoration: none; font-weight: 600;">
                                        <?php echo esc_html($verein->kontakt_telefon); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($verein->kontakt_email)): ?>
                                <p style="margin: 0.25rem 0; color: #475569;">
                                    <strong>E-Mail:</strong> <a href="mailto:<?php echo esc_attr($verein->kontakt_email); ?>" style="color: #f97316; text-decoration: none; font-weight: 600;">
                                        <?php echo esc_html($verein->kontakt_email); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($verantwortliche)): ?>
                        <!-- Veranstaltungs-Verantwortliche -->
                        <div style="background: white; padding: 1rem; border-radius: 6px; border: 1px solid #fed7aa;">
                            <h4 style="margin: 0 0 0.5rem 0; color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
                                <span class="dashicons dashicons-admin-users" style="color: #f97316;"></span>
                                Verantwortliche für <?php echo esc_html($veranstaltung->name); ?>
                            </h4>
                            <?php foreach ($verantwortliche as $verantw): 
                                $user = get_user_by('id', $verantw->user_id);
                                if ($user):
                            ?>
                                <div style="margin: 0.5rem 0; padding: 0.5rem; background: #fef3c7; border-radius: 4px;">
                                    <p style="margin: 0; color: #475569;">
                                        <strong><?php echo esc_html($user->display_name); ?></strong>
                                    </p>
                                    <?php if (!empty($user->user_email)): ?>
                                        <p style="margin: 0.25rem 0 0 0; font-size: 0.9rem;">
                                            <a href="mailto:<?php echo esc_attr($user->user_email); ?>" style="color: #f97316; text-decoration: none;">
                                                <?php echo esc_html($user->user_email); ?>
                                            </a>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php
    $filter_tage = array();
    $filter_tag_counter = 1;
    foreach (array_keys($dienste_nach_tagen) as $filter_tag_id) {
        $filter_tag_obj = null;
        foreach ($tage as $filter_tag_item) {
            if (intval($filter_tag_item->id) === intval($filter_tag_id)) {
                $filter_tag_obj = $filter_tag_item;
                break;
            }
        }
        $filter_tag_raw = $filter_tag_obj->tag_datum ?? ($filter_tag_obj->datum ?? null);
        $filter_tage[] = array(
            'id' => intval($filter_tag_id),
            'label' => 'Tag ' . $filter_tag_counter . ($filter_tag_raw ? ' (' . date('d.m.', strtotime($filter_tag_raw)) . ')' : '')
        );
        $filter_tag_counter++;
    }
    ?>

    <!-- View-Toggle im Header rechts -->

    <div class="dp-frontend-filterbar">
        <div class="dp-filter-group">
            <label>Besetzung</label>
            <select id="dpFilterAvailability">
                <option value="all">Alle</option>
                <option value="open">Offen</option>
                <?php if ($is_logged_in): ?>
                    <option value="mine">Meine</option>
                <?php endif; ?>
            </select>
        </div>

        <div class="dp-filter-group">
            <label>Tag</label>
            <select id="dpFilterTag">
                <option value="all">Alle Tage</option>
                <?php foreach ($filter_tage as $filter_tag_entry): ?>
                    <option value="<?php echo intval($filter_tag_entry['id']); ?>"><?php echo esc_html($filter_tag_entry['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="dp-filter-group">
            <label>Arbeitsbereich</label>
            <select id="dpFilterBereich">
                <option value="all">Alle</option>
                <?php foreach ($filter_bereiche as $bereich_id => $bereich_name): ?>
                    <option value="<?php echo intval($bereich_id); ?>"><?php echo esc_html($bereich_name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="dp-filter-group">
            <label>Dienst</label>
            <select id="dpFilterDienst">
                <option value="all">Alle</option>
                <?php foreach ($filter_dienste as $dienst_id => $dienst_name): ?>
                    <option value="<?php echo intval($dienst_id); ?>"><?php echo esc_html($dienst_name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="dp-filter-group dp-filter-reset-wrap">
            <button type="button" class="dp-filter-reset" onclick="dpResetFilters()">Filter zurücksetzen</button>
        </div>
    </div>

    <?php if (empty($services)): ?>
        <div class="dp-notice dp-notice-info" style="padding: 2rem; text-align: center; background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px;">
            <p><strong>Keine Dienste verfügbar<?php echo ($verein_id > 0 ? ' für ' . esc_html($verein->name) : ''); ?>.</strong></p>
        </div>
    <?php else: ?>
        
        <?php if ($view_mode === 'timeline'): ?>
            <!-- Timeline-Ansicht -->
            <div class="dp-timeline-container">
                <?php
                $timeline_tag_ids = array_keys($dienste_nach_tagen);
                ?>

                <?php if (!empty($timeline_tag_ids)): ?>
                    <div class="dp-timeline-day-tabs">
                        <?php foreach ($timeline_tag_ids as $tab_index => $tab_tag_id):
                            $tab_tag = null;
                            foreach ($tage as $timeline_tag_item) {
                                if ($timeline_tag_item->id == $tab_tag_id) {
                                    $tab_tag = $timeline_tag_item;
                                    break;
                                }
                            }
                            $tab_tag_raw = $tab_tag->tag_datum ?? ($tab_tag->datum ?? null);
                            $tab_label_date = $tab_tag_raw ? date('d.m.', strtotime($tab_tag_raw)) : '—';
                        ?>
                            <button type="button" class="dp-timeline-day-tab <?php echo $tab_index === 0 ? 'active' : ''; ?>" data-tag-id="<?php echo intval($tab_tag_id); ?>">
                                <span class="dp-tab-title">Tag <?php echo intval($tab_index + 1); ?></span>
                                <span class="dp-tab-date"><?php echo esc_html($tab_label_date); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php foreach ($dienste_nach_tagen as $tag_index => $tag_dienste): 
                    $tag = null;
                    foreach ($tage as $t) {
                        if ($t->id == $tag_index) {
                            $tag = $t;
                            break;
                        }
                    }
                    $tag_raw = $tag->tag_datum ?? ($tag->datum ?? null);
                    $tag_datum = $tag_raw ? date('d.m.Y', strtotime($tag_raw)) : 'Unbekannt';

                    $timeline_min_minutes = null;
                    $timeline_max_minutes = null;
                    foreach ($tag_dienste as $dienst_window) {
                        $dienst_von_raw = $dienst_window->von_zeit ?? ($dienst_window->zeit_von ?? '00:00:00');
                        $dienst_bis_raw = $dienst_window->bis_zeit ?? ($dienst_window->zeit_bis ?? $dienst_von_raw);

                        $start_h = intval(substr($dienst_von_raw, 0, 2));
                        $start_m = intval(substr($dienst_von_raw, 3, 2));
                        $end_h = intval(substr($dienst_bis_raw, 0, 2));
                        $end_m = intval(substr($dienst_bis_raw, 3, 2));

                        $start_minutes = ($start_h * 60) + $start_m;
                        $end_minutes = ($end_h * 60) + $end_m;
                        if ($end_minutes <= $start_minutes) {
                            $end_minutes += 24 * 60;
                        }

                        if ($timeline_min_minutes === null || $start_minutes < $timeline_min_minutes) {
                            $timeline_min_minutes = $start_minutes;
                        }
                        if ($timeline_max_minutes === null || $end_minutes > $timeline_max_minutes) {
                            $timeline_max_minutes = $end_minutes;
                        }
                    }

                    if ($timeline_min_minutes === null) {
                        $timeline_min_minutes = 9 * 60;
                    }
                    if ($timeline_max_minutes === null) {
                        $timeline_max_minutes = 18 * 60;
                    }

                    $timeline_start_hour = max(0, intval(floor($timeline_min_minutes / 60)));
                    $timeline_end_hour = min(30, intval(ceil($timeline_max_minutes / 60)));
                    if ($timeline_end_hour <= $timeline_start_hour) {
                        $timeline_end_hour = $timeline_start_hour + 1;
                    }
                    $timeline_total_minutes = max(60, ($timeline_end_hour - $timeline_start_hour) * 60);

                    $timeline_hour_labels = array();
                    for ($hour_step = $timeline_start_hour; $hour_step <= $timeline_end_hour; $hour_step++) {
                        $display_hour = $hour_step % 24;
                        $timeline_hour_labels[] = sprintf('%02d:00', $display_hour);
                    }
                    ?>
                    
                    <div class="dp-timeline-day <?php echo array_key_first($dienste_nach_tagen) === $tag_index ? 'active' : ''; ?>" data-tag-id="<?php echo intval($tag_index); ?>">
                        <h3 class="dp-timeline-day-title"><?php echo esc_html($tag_datum); ?></h3>
                        <div class="dp-timeline-grid-head">
                            <div class="dp-timeline-grid-service-head">Dienste</div>
                            <div class="dp-timeline-grid-hours" style="--dp-hour-cols: <?php echo intval(count($timeline_hour_labels)); ?>;">
                                <?php foreach ($timeline_hour_labels as $timeline_hour_label): ?>
                                    <span class="dp-timeline-hour"><?php echo esc_html($timeline_hour_label); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="dp-timeline-slots">
                            <?php foreach ($tag_dienste as $dienst): 
                                $slots = $db->get_dienst_slots($dienst->id);
                                $bereich = $db->get_bereich($dienst->bereich_id);
                                $taetigkeit = $db->get_taetigkeit($dienst->taetigkeit_id);
                                $freie_slots = count(array_filter($slots, function($s) { return empty($s->mitarbeiter_id); }));
                                $besetzte_slots = count($slots) - $freie_slots;
                                $has_my_slot = ($current_mitarbeiter_id > 0) ? count(array_filter($slots, function($s) use ($current_mitarbeiter_id) {
                                    return intval($s->mitarbeiter_id ?? 0) === intval($current_mitarbeiter_id);
                                })) > 0 : false;
                                $first_free_slot_id = 0;
                                $first_own_slot_id = 0;
                                foreach ($slots as $slot_row_timeline) {
                                    if (empty($slot_row_timeline->mitarbeiter_id)) {
                                        $first_free_slot_id = intval($slot_row_timeline->id);
                                    }

                                    if ($first_own_slot_id === 0 && $current_mitarbeiter_id > 0 && intval($slot_row_timeline->mitarbeiter_id) === intval($current_mitarbeiter_id)) {
                                        $first_own_slot_id = intval($slot_row_timeline->id);
                                    }

                                    if ($first_free_slot_id > 0 && $first_own_slot_id > 0) {
                                        break;
                                    }
                                }

                                $dienst_von = $dienst->von_zeit ?? ($dienst->zeit_von ?? '00:00:00');
                                $dienst_bis = $dienst->bis_zeit ?? ($dienst->zeit_bis ?? $dienst_von);

                                $dienst_start_minutes = (intval(substr($dienst_von, 0, 2)) * 60) + intval(substr($dienst_von, 3, 2));
                                $dienst_end_minutes = (intval(substr($dienst_bis, 0, 2)) * 60) + intval(substr($dienst_bis, 3, 2));
                                if ($dienst_end_minutes <= $dienst_start_minutes) {
                                    $dienst_end_minutes += 24 * 60;
                                }

                                $start_offset_minutes = max(0, $dienst_start_minutes - ($timeline_start_hour * 60));
                                $end_offset_minutes = min($timeline_total_minutes, $dienst_end_minutes - ($timeline_start_hour * 60));
                                $bar_left_percent = max(0, min(100, ($start_offset_minutes / $timeline_total_minutes) * 100));
                                $bar_width_percent = max(4, min(100 - $bar_left_percent, (($end_offset_minutes - $start_offset_minutes) / $timeline_total_minutes) * 100));
                                ?>
                                
                                <div class="dp-timeline-dienst" data-tag-id="<?php echo intval($tag_index); ?>" data-dienst-id="<?php echo $dienst->id; ?>" data-bereich-id="<?php echo intval($dienst->bereich_id); ?>" data-taetigkeit-id="<?php echo intval($dienst->taetigkeit_id); ?>" data-has-free="<?php echo $freie_slots > 0 ? '1' : '0'; ?>" data-has-mine="<?php echo $has_my_slot ? '1' : '0'; ?>">
                                    <div class="dp-dienst-info-compact">
                                        <div class="dp-dienst-time">von <?php echo esc_html(substr($dienst_von, 0, 5)); ?> bis <?php echo esc_html(substr($dienst_bis, 0, 5)); ?></div>
                                        <div class="dp-dienst-details-compact">
                                            <strong><?php echo esc_html($taetigkeit->name ?? 'Unbekannt'); ?></strong>
                                            <em><?php echo esc_html($bereich->name ?? ''); ?></em>
                                        </div>
                                    </div>
                                    
                                    <div class="dp-timeline-track-wrap" style="--dp-hour-cols: <?php echo intval(count($timeline_hour_labels)); ?>;">
                                        <div class="dp-timeline-track-grid"></div>
                                        <div class="dp-timeline-track-bar" style="left: <?php echo esc_attr(number_format($bar_left_percent, 3, '.', '')); ?>%; width: <?php echo esc_attr(number_format($bar_width_percent, 3, '.', '')); ?>%;">
                                            <span class="dp-track-title"><?php echo esc_html($taetigkeit->name ?? 'Unbekannt'); ?></span>
                                            <span class="dp-track-occupancy <?php echo $freie_slots > 0 ? 'is-open' : 'is-full'; ?>"><?php echo intval($besetzte_slots); ?>/<?php echo intval(count($slots)); ?></span>

                                            <?php if ($has_my_slot && $first_own_slot_id > 0): ?>
                                                <button type="button" class="dp-track-own" onclick="dpCancelDienst(<?php echo intval($first_own_slot_id); ?>, this)">Dienst absagen</button>
                                            <?php endif; ?>

                                            <?php if ($anmeldung_aktiv && $first_free_slot_id > 0): ?>
                                                <button type="button" class="dp-track-action" onclick="return dpOpenTakeoverModal(<?php echo intval($first_free_slot_id); ?>, <?php echo intval($dienst->id); ?>, event);">Übernehmen</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if ($can_manage_dienste): ?>
                                        <div class="dp-timeline-admin-actions">
                                            <?php foreach ($slots as $slot):
                                                if (empty($slot->mitarbeiter_id)) {
                                                    continue;
                                                }
                                                ?>
                                                <button type="button" class="dp-btn-admin-remove" onclick="dpAdminRemoveSlot(<?php echo intval($slot->id); ?>, this)">Zuweisung löschen</button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!$anmeldung_aktiv && $freie_slots > 0): ?>
                                        <div class="dp-timeline-locked-note">
                                            <span class="dashicons dashicons-lock"></span> Anmeldung aktuell gesperrt
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
        <?php elseif ($view_mode === 'kompakt'): ?>
            <!-- Kompakte Listen-Ansicht -->
            <div class="dp-dienste-kompakt-list">
                <?php foreach ($dienste_nach_tagen as $tag_id => $tag_dienste):
                    $tag = null;
                    foreach ($tage as $t) {
                        if ($t->id == $tag_id) {
                            $tag = $t;
                            break;
                        }
                    }
                    $tag_raw = $tag->tag_datum ?? ($tag->datum ?? null);
                    $tag_datum = $tag_raw ? date('d.m.Y', strtotime($tag_raw)) : 'Unbekannt';
                ?>
                    <div class="dp-day-section-compact" data-tag-id="<?php echo intval($tag_id); ?>">
                        <h3 class="dp-day-title"><?php echo esc_html($tag_datum); ?></h3>

                        <div class="dp-dienst-rows">
                            <?php foreach ($tag_dienste as $dienst):
                                $slots = $db->get_dienst_slots($dienst->id);
                                $bereich = $db->get_bereich($dienst->bereich_id);
                                $taetigkeit = $db->get_taetigkeit($dienst->taetigkeit_id);
                                $freie_slots = count(array_filter($slots, function($s) { return empty($s->mitarbeiter_id); }));
                                $has_my_slot = ($current_mitarbeiter_id > 0) ? count(array_filter($slots, function($s) use ($current_mitarbeiter_id) {
                                    return intval($s->mitarbeiter_id ?? 0) === intval($current_mitarbeiter_id);
                                })) > 0 : false;
                                $first_free_slot_id = 0;
                                foreach ($slots as $slot_row) {
                                    if (empty($slot_row->mitarbeiter_id)) {
                                        $first_free_slot_id = intval($slot_row->id);
                                        break;
                                    }
                                }
                                $dienst_von = $dienst->von_zeit ?? ($dienst->zeit_von ?? '');
                                $dienst_bis = $dienst->bis_zeit ?? ($dienst->zeit_bis ?? '');
                                $dienst_beschreibung = $dienst->beschreibung ?? ($dienst->besonderheiten ?? '');
                            ?>
                                <div class="dp-dienst-row" data-tag-id="<?php echo intval($tag_id); ?>" data-dienst-id="<?php echo $dienst->id; ?>" data-bereich-id="<?php echo intval($dienst->bereich_id); ?>" data-taetigkeit-id="<?php echo intval($dienst->taetigkeit_id); ?>" data-has-free="<?php echo $freie_slots > 0 ? '1' : '0'; ?>" data-has-mine="<?php echo $has_my_slot ? '1' : '0'; ?>">
                                    <div class="dp-dienst-row-main">
                                        <span class="dp-dienst-row-time"><?php echo substr($dienst_von, 0, 5) . ' - ' . substr($dienst_bis, 0, 5); ?></span>
                                        <span class="dp-bereich-badge" style="background-color: <?php echo esc_attr($bereich->farbe ?? '#e2e8f0'); ?>;">
                                            <?php echo esc_html($bereich->name ?? ''); ?>
                                        </span>
                                        <strong class="dp-dienst-row-name"><?php echo esc_html($taetigkeit->name ?? 'Unbekannt'); ?></strong>
                                        <?php if (!empty($dienst_beschreibung)): ?>
                                            <span class="dp-dienst-row-hint"><?php echo esc_html($dienst_beschreibung); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="dp-dienst-row-actions">
                                        <span class="dp-row-status <?php echo $freie_slots > 0 ? 'open' : 'full'; ?>">
                                            <?php echo $freie_slots > 0 ? ($freie_slots . ' frei') : 'Voll'; ?>
                                        </span>
                                        <?php if ($anmeldung_aktiv && $first_free_slot_id > 0): ?>
                                            <button type="button" class="dp-slot-offen-label" onclick="return dpOpenTakeoverModal(<?php echo $first_free_slot_id; ?>, <?php echo intval($dienst->id); ?>, event);">Übernehmen</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- Kachel-Ansicht -->
            <div class="dp-dienste-list">
                <?php foreach ($dienste_nach_tagen as $tag_id => $tag_dienste): 
                    $tag = null;
                    foreach ($tage as $t) {
                        if ($t->id == $tag_id) {
                            $tag = $t;
                            break;
                        }
                    }
                    $tag_raw = $tag->tag_datum ?? ($tag->datum ?? null);
                    $tag_datum = $tag_raw ? date('d.m.Y', strtotime($tag_raw)) : 'Unbekannt';
                    ?>
                    
                    <div class="dp-day-section" data-tag-id="<?php echo intval($tag_id); ?>">
                        <h3 class="dp-day-title"><?php echo esc_html($tag_datum); ?></h3>
                        
                        <div class="dp-dienste-cards">
                            <?php foreach ($tag_dienste as $dienst): 
                                $slots = $db->get_dienst_slots($dienst->id);
                                $bereich = $db->get_bereich($dienst->bereich_id);
                                $taetigkeit = $db->get_taetigkeit($dienst->taetigkeit_id);
                                $freie_slots = count(array_filter($slots, function($s) { return empty($s->mitarbeiter_id); }));
                                $has_my_slot = ($current_mitarbeiter_id > 0) ? count(array_filter($slots, function($s) use ($current_mitarbeiter_id) {
                                    return intval($s->mitarbeiter_id ?? 0) === intval($current_mitarbeiter_id);
                                })) > 0 : false;
                                ?>
                                
                                <div class="dp-dienst-card" data-tag-id="<?php echo intval($tag_id); ?>" data-dienst-id="<?php echo $dienst->id; ?>" data-bereich-id="<?php echo intval($dienst->bereich_id); ?>" data-taetigkeit-id="<?php echo intval($dienst->taetigkeit_id); ?>" data-has-free="<?php echo $freie_slots > 0 ? '1' : '0'; ?>" data-has-mine="<?php echo $has_my_slot ? '1' : '0'; ?>">
                                    <div class="dp-dienst-header">
                                        <?php
                                        $dienst_von = $dienst->von_zeit ?? ($dienst->zeit_von ?? '');
                                        $dienst_bis = $dienst->bis_zeit ?? ($dienst->zeit_bis ?? '');
                                        ?>
                                        <div class="dp-dienst-time-big"><?php echo substr($dienst_von, 0, 5) . ' - ' . substr($dienst_bis, 0, 5); ?></div>
                                        <span class="dp-bereich-badge" style="background-color: <?php echo esc_attr($bereich->farbe ?? '#e2e8f0'); ?>">
                                            <?php echo esc_html($bereich->name ?? ''); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="dp-dienst-body">
                                        <h4 class="dp-dienst-name"><?php echo esc_html($taetigkeit->name ?? 'Unbekannt'); ?></h4>
                                        
                                        <?php
                                        $dienst_beschreibung = $dienst->beschreibung ?? ($dienst->besonderheiten ?? '');
                                        ?>
                                        <?php if (!empty($dienst_beschreibung)): ?>
                                            <p class="dp-dienst-beschreibung"><?php echo esc_html($dienst_beschreibung); ?></p>
                                        <?php endif; ?>
                                        
                                        <div class="dp-dienst-stats">
                                            <span class="dp-stat <?php echo $freie_slots > 0 ? 'dp-stat-success' : 'dp-stat-warning'; ?>">
                                                <span class="dashicons dashicons-admin-users"></span>
                                                <?php echo $freie_slots; ?> von <?php echo count($slots); ?> frei
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="dp-dienst-footer">
                                        <?php foreach ($slots as $idx => $slot): 
                                            $ist_belegt = !empty($slot->mitarbeiter_id);
                                            $mitarbeiter = $ist_belegt ? $db->get_mitarbeiter($slot->mitarbeiter_id) : null;
                                            $is_own_slot = ($current_mitarbeiter_id > 0 && intval($slot->mitarbeiter_id) === $current_mitarbeiter_id);
                                            ?>
                                            <div class="dp-slot-item <?php echo $ist_belegt ? 'belegt' : 'frei'; ?>">
                                                <?php if ($ist_belegt && $mitarbeiter): ?>
                                                    <?php if ($can_manage_dienste || $is_own_slot): ?>
                                                        <span class="dp-slot-belegt-name"><?php echo esc_html($mitarbeiter->vorname . ' ' . substr($mitarbeiter->nachname, 0, 1) . '.'); ?></span>
                                                    <?php else: ?>
                                                        <span class="dp-slot-belegt-name">Besetzt</span>
                                                    <?php endif; ?>
                                                    <?php if ($is_own_slot): ?>
                                                        <button type="button" class="dp-btn-absagen" onclick="dpCancelDienst(<?php echo intval($slot->id); ?>, this)">
                                                            Dienst absagen
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($can_manage_dienste): ?>
                                                        <button type="button" class="dp-btn-admin-remove" onclick="dpAdminRemoveSlot(<?php echo intval($slot->id); ?>, this)">
                                                            Zuweisung löschen
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if ($anmeldung_aktiv): ?>
                                                        <span class="dp-slot-offen-label" onclick="return dpOpenTakeoverModal(<?php echo intval($slot->id); ?>, <?php echo intval($dienst->id); ?>, event);">
                                                            <span class="dashicons dashicons-unlock"></span>
                                                            Übernehmen
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="dp-slot-gesperrt" style="color: #9ca3af; font-size: 0.875rem;">
                                                            <span class="dashicons dashicons-lock"></span> Gesperrt
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
window.dpVereinDebug = /[?&](dpdebug|debug)=1(&|$)/.test(window.location.search);
window.dpTrace = function(message, payload) {
    if (typeof payload !== 'undefined') {
        console.warn('[DP DEBUG]', message, payload);
    } else {
        console.warn('[DP DEBUG]', message);
    }

    if (window.dpVereinDebug) {
        var bootstrap = document.getElementById('dp-debug-bootstrap');
        if (bootstrap) {
            var text = typeof payload !== 'undefined' ? (message + ' ' + JSON.stringify(payload)) : message;
            bootstrap.textContent = 'DP DEBUG BOOTSTRAP aktiv | ' + text;
        }
    }
};

window.dpTrace('Script geladen: veranstaltung-verein');
window.dpLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
window.dpLoggedInPrefill = <?php echo wp_json_encode(array(
    'vorname' => $current_mitarbeiter->vorname ?? ($current_user_obj ? $current_user_obj->display_name : ''),
    'nachname' => $current_mitarbeiter->nachname ?? '',
    'email' => $current_mitarbeiter->email ?? ($current_user_obj->user_email ?? ''),
    'telefon' => $current_mitarbeiter->telefon ?? '',
)); ?>;

window.addEventListener('error', function(ev) {
    window.dpTrace('JS Fehler', { message: ev.message, source: ev.filename, line: ev.lineno });
});

window.dpOpenLoginModal = function() {
    var modal = document.getElementById('dp-login-modal');
    if (!modal) {
        alert('Login-Modal nicht verfügbar.');
        return false;
    }

    modal.classList.add('dp-modal-force-open');
    modal.style.setProperty('display', 'block', 'important');
    modal.style.setProperty('visibility', 'visible', 'important');
    modal.style.setProperty('opacity', '1', 'important');
    jQuery('#dp-login-modal').stop(true, true).fadeIn(150);
    jQuery('body').css('overflow', 'hidden');
    return false;
};

window.dpCloseLoginModal = function() {
    var modal = document.getElementById('dp-login-modal');
    if (modal) {
        modal.classList.remove('dp-modal-force-open');
    }
    jQuery('#dp-login-modal').fadeOut(200, function() {
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.style.removeProperty('visibility');
            modal.style.removeProperty('opacity');
        }
    });
    jQuery('body').css('overflow', 'auto');
};

window.dpOpenTakeoverModal = function(slotId, dienstId, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    slotId = parseInt(slotId || '0', 10);
    dienstId = parseInt(dienstId || '0', 10);
    window.dpTrace('Klick auf Frei', { slotId: slotId, dienstId: dienstId });

    if (!slotId || !dienstId) {
        window.dpTrace('Abbruch: ungültige IDs', { slotId: slotId, dienstId: dienstId });
        alert('Dienstdaten konnten nicht geladen werden. Bitte Seite neu laden.');
        return false;
    }

    if (window.dpLoggedIn) {
        return window.dpOpenLoggedInModal(slotId, dienstId, event);
    }

    jQuery('#dp-slot-id').val(slotId);
    jQuery('#dp-dienst-id').val(dienstId);
    if (typeof window.dpToggleCreateUserConsent === 'function') {
        window.dpToggleCreateUserConsent();
    }
    var modal = document.getElementById('dp-anmelde-modal');
    if (!modal) {
        window.dpTrace('Abbruch: Modal nicht gefunden');
        return false;
    }

    modal.classList.add('dp-modal-force-open');
    modal.style.setProperty('display', 'block', 'important');
    modal.style.setProperty('visibility', 'visible', 'important');
    modal.style.setProperty('opacity', '1', 'important');
    jQuery('#dp-anmelde-modal').stop(true, true).fadeIn(150);
    jQuery('body').css('overflow', 'hidden');
    window.dpTrace('Modal öffnen aufgerufen', {
        visible: jQuery('#dp-anmelde-modal').is(':visible'),
        display: jQuery('#dp-anmelde-modal').css('display'),
        computedDisplay: window.getComputedStyle(modal).display
    });
    return false;
};

window.dpOpenLoggedInModal = function(slotId, dienstId, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    var modal = document.getElementById('dp-loggedin-modal');
    if (!modal) {
        alert('Login-Modal nicht gefunden. Bitte Seite neu laden.');
        return false;
    }

    jQuery('#dp-li-slot-id').val(slotId);
    jQuery('#dp-li-dienst-id').val(dienstId);
    jQuery('#dp-li-vorname').text(window.dpLoggedInPrefill.vorname || '-');
    jQuery('#dp-li-nachname').text(window.dpLoggedInPrefill.nachname || '-');
    jQuery('#dp-li-email').text(window.dpLoggedInPrefill.email || '-');

    modal.classList.add('dp-modal-force-open');
    modal.style.setProperty('display', 'block', 'important');
    modal.style.setProperty('visibility', 'visible', 'important');
    modal.style.setProperty('opacity', '1', 'important');
    jQuery('#dp-loggedin-modal').stop(true, true).fadeIn(150);
    jQuery('body').css('overflow', 'hidden');
    return false;
};

window.dpCloseLoggedInModal = function() {
    var modal = document.getElementById('dp-loggedin-modal');
    if (modal) {
        modal.classList.remove('dp-modal-force-open');
    }
    jQuery('#dp-loggedin-modal').fadeOut(200, function() {
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.style.removeProperty('visibility');
            modal.style.removeProperty('opacity');
        }
    });
    jQuery('body').css('overflow', 'auto');
    var form = jQuery('#dp-loggedin-form')[0];
    if (form) {
        form.reset();
    }
};

function dpCancelDienst(slotId, buttonElement) {
    if (!confirm('Möchtest du diesen Dienst wirklich absagen?')) {
        return;
    }

    if (!window.dpPublic || !window.dpPublic.ajaxurl || !window.dpPublic.nonce) {
        alert('Konfiguration fehlt (dpPublic). Bitte Seite neu laden.');
        return;
    }

    var btn = buttonElement;
    var originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Wird abgesagt...';

    jQuery.ajax({
        url: window.dpPublic.ajaxurl,
        type: 'POST',
        data: {
            action: 'dp_remove_assignment',
            nonce: window.dpPublic.nonce,
            slot_id: slotId
        },
        success: function(response) {
            if (response && response.success) {
                window.location.reload();
            } else {
                var message = (response && response.data && response.data.message) ? response.data.message : 'Absage fehlgeschlagen.';
                alert(message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        },
        error: function() {
            alert('Serverfehler bei der Absage.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
}
</script>

<script>
function dpAdminRemoveSlot(slotId, buttonElement) {
    if (!confirm('Zuweisung für diesen Slot wirklich löschen?')) {
        return;
    }

    if (!window.dpPublic || !window.dpPublic.ajaxurl || !window.dpPublic.nonce) {
        alert('Konfiguration fehlt (dpPublic). Bitte Seite neu laden.');
        return;
    }

    var btn = buttonElement;
    var originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Lösche...';

    jQuery.ajax({
        url: window.dpPublic.ajaxurl,
        type: 'POST',
        data: {
            action: 'dp_frontend_admin_remove_slot',
            nonce: window.dpPublic.nonce,
            slot_id: slotId
        },
        success: function(response) {
            if (response && response.success) {
                window.location.reload();
            } else {
                var message = (response && response.data && response.data.message) ? response.data.message : 'Löschen fehlgeschlagen.';
                alert(message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        },
        error: function() {
            alert('Serverfehler beim Löschen der Zuweisung.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
}

function dpAdminSplitDienst(dienstId, buttonElement) {
    if (!confirm('Diesen Dienst jetzt splitten?')) {
        return;
    }

    if (!window.dpPublic || !window.dpPublic.ajaxurl || !window.dpPublic.nonce) {
        alert('Konfiguration fehlt (dpPublic). Bitte Seite neu laden.');
        return;
    }

    var btn = buttonElement;
    var originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Splitte...';

    jQuery.ajax({
        url: window.dpPublic.ajaxurl,
        type: 'POST',
        data: {
            action: 'dp_frontend_admin_split_dienst',
            nonce: window.dpPublic.nonce,
            dienst_id: dienstId
        },
        success: function(response) {
            if (response && response.success) {
                alert((response.data && response.data.message) ? response.data.message : 'Dienst wurde gesplittet.');
                window.location.reload();
            } else {
                var message = (response && response.data && response.data.message) ? response.data.message : 'Split fehlgeschlagen.';
                alert(message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        },
        error: function() {
            alert('Serverfehler beim Splitten.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
}

function dpApplyFrontendFilters() {
    dpUpdateFilterOptionVisibility();

    var availability = dpGetActiveFilterValue('availability');
    var tag = dpGetActiveFilterValue('tag');
    var bereich = dpGetActiveFilterValue('bereich');
    var dienst = dpGetActiveFilterValue('dienst');

    var items = document.querySelectorAll('.dp-dienst-card, .dp-dienst-row, .dp-timeline-dienst');
    items.forEach(function(item) {
        var itemTag = item.getAttribute('data-tag-id') || '';
        var itemBereich = item.getAttribute('data-bereich-id') || '';
        var itemDienst = item.getAttribute('data-taetigkeit-id') || '';
        var itemHasFree = item.getAttribute('data-has-free') || '0';
        var itemHasMine = item.getAttribute('data-has-mine') || '0';

        var show = true;
        if (tag !== 'all' && itemTag !== tag) {
            show = false;
        }
        if (availability === 'open' && itemHasFree !== '1') {
            show = false;
        }
        if (availability === 'mine' && itemHasMine !== '1') {
            show = false;
        }
        if (bereich !== 'all' && itemBereich !== bereich) {
            show = false;
        }
        if (dienst !== 'all' && itemDienst !== dienst) {
            show = false;
        }

        item.style.display = show ? '' : 'none';
    });

    document.querySelectorAll('.dp-day-section').forEach(function(section) {
        var sectionTag = section.getAttribute('data-tag-id') || '';
        var visibleCards = section.querySelectorAll('.dp-dienst-card:not([style*="display: none"])').length;
        var tagMatches = (tag === 'all' || sectionTag === tag);
        section.style.display = (tagMatches && visibleCards > 0) ? '' : 'none';
    });

    document.querySelectorAll('.dp-day-section-compact').forEach(function(section) {
        var sectionTag = section.getAttribute('data-tag-id') || '';
        var visibleRows = section.querySelectorAll('.dp-dienst-row:not([style*="display: none"])').length;
        var tagMatches = (tag === 'all' || sectionTag === tag);
        section.style.display = (tagMatches && visibleRows > 0) ? '' : 'none';
    });

    document.querySelectorAll('.dp-timeline-day').forEach(function(day) {
        var dayTag = day.getAttribute('data-tag-id') || '';
        var visibleCards = day.querySelectorAll('.dp-timeline-dienst:not([style*="display: none"])').length;
        var tagMatches = (tag === 'all' || dayTag === tag);
        day.style.display = (tagMatches && visibleCards > 0) ? '' : 'none';
    });

    document.querySelectorAll('.dp-timeline-day-tab').forEach(function(tab) {
        var tabTag = tab.getAttribute('data-tag-id') || '';
        tab.style.display = (tag === 'all' || tabTag === tag) ? '' : 'none';
    });

    var activeTab = document.querySelector('.dp-timeline-day-tab.active');
    var visibleActiveDay = activeTab ? document.querySelector('.dp-timeline-day[data-tag-id="' + activeTab.getAttribute('data-tag-id') + '"]') : null;
    if (!visibleActiveDay || visibleActiveDay.style.display === 'none') {
        var firstVisibleDay = document.querySelector('.dp-timeline-day:not([style*="display: none"])');
        var firstVisibleTagId = firstVisibleDay ? firstVisibleDay.getAttribute('data-tag-id') : null;
        if (firstVisibleTagId) {
            document.querySelectorAll('.dp-timeline-day-tab').forEach(function(tab) {
                tab.classList.toggle('active', tab.getAttribute('data-tag-id') === firstVisibleTagId);
            });
        }
    }

    dpActivateTimelineDayByTab();
}

function dpResetFilters() {
    ['availability', 'tag', 'bereich', 'dienst'].forEach(function(filterName) {
        var select = dpGetFilterSelect(filterName);
        if (select) {
            select.value = 'all';
        }
    });

    dpApplyFrontendFilters();
}

function dpGetActiveFilterValue(filterName) {
    var select = dpGetFilterSelect(filterName);
    return select ? (select.value || 'all') : 'all';
}

function dpGetFilterSelect(filterName) {
    var map = {
        availability: 'dpFilterAvailability',
        tag: 'dpFilterTag',
        bereich: 'dpFilterBereich',
        dienst: 'dpFilterDienst'
    };

    var selectId = map[filterName] || '';
    return selectId ? document.getElementById(selectId) : null;
}

function dpGetItemFilterValue(item, filterName) {
    if (filterName === 'tag') {
        return item.getAttribute('data-tag-id') || '';
    }
    if (filterName === 'bereich') {
        return item.getAttribute('data-bereich-id') || '';
    }
    if (filterName === 'dienst') {
        return item.getAttribute('data-taetigkeit-id') || '';
    }
    return '';
}

function dpItemPassesAvailability(item, value) {
    if (value === 'all') {
        return true;
    }

    var itemHasFree = item.getAttribute('data-has-free') || '0';
    var itemHasMine = item.getAttribute('data-has-mine') || '0';

    if (value === 'open') {
        return itemHasFree === '1';
    }
    if (value === 'mine') {
        return itemHasMine === '1';
    }

    return true;
}

function dpItemMatchesFilterSet(item, state, filterKeys, overrideFilterName, overrideValue) {
    for (var index = 0; index < filterKeys.length; index++) {
        var filterName = filterKeys[index];
        var selectedValue = (filterName === overrideFilterName) ? overrideValue : (state[filterName] || 'all');

        if (filterName === 'availability') {
            if (!dpItemPassesAvailability(item, selectedValue)) {
                return false;
            }
            continue;
        }

        if (selectedValue !== 'all') {
            var itemValue = dpGetItemFilterValue(item, filterName);
            if (itemValue !== selectedValue) {
                return false;
            }
        }
    }

    return true;
}

function dpEnsureVisibleActiveFilterOption(filterName) {
    var select = dpGetFilterSelect(filterName);
    if (!select) {
        return;
    }

    var selectedOption = select.options[select.selectedIndex] || null;
    var invalidSelection = !selectedOption || selectedOption.disabled || selectedOption.hidden;
    if (!invalidSelection) {
        return;
    }

    var fallback = null;
    Array.prototype.forEach.call(select.options, function(option) {
        if (!fallback && option.value === 'all' && !option.disabled && !option.hidden) {
            fallback = option;
        }
    });

    if (!fallback) {
        Array.prototype.forEach.call(select.options, function(option) {
            if (!fallback && !option.disabled && !option.hidden) {
                fallback = option;
            }
        });
    }

    if (fallback) {
        select.value = fallback.value;
    }
}

function dpUpdateFilterOptionVisibility() {
    var items = Array.prototype.slice.call(document.querySelectorAll('.dp-dienst-card, .dp-dienst-row, .dp-timeline-dienst'));
    if (!items.length) {
        return;
    }

    var state = {
        availability: dpGetActiveFilterValue('availability'),
        tag: dpGetActiveFilterValue('tag'),
        bereich: dpGetActiveFilterValue('bereich'),
        dienst: dpGetActiveFilterValue('dienst')
    };

    var relationOrder = {
        availability: ['availability'],
        tag: ['availability', 'tag'],
        bereich: ['availability', 'tag', 'bereich'],
        dienst: ['availability', 'tag', 'bereich', 'dienst']
    };

    ['availability', 'tag', 'bereich', 'dienst'].forEach(function(filterName) {
        var select = dpGetFilterSelect(filterName);
        if (!select) {
            return;
        }
        var relevantFilters = relationOrder[filterName] || [filterName];

        Array.prototype.forEach.call(select.options, function(option) {
            var optionValue = option.value || 'all';
            if (optionValue === 'all') {
                option.disabled = false;
                option.hidden = false;
                return;
            }

            var hasMatch = items.some(function(item) {
                return dpItemMatchesFilterSet(item, state, relevantFilters, filterName, optionValue);
            });

            option.disabled = !hasMatch;
            option.hidden = !hasMatch;
        });

        dpEnsureVisibleActiveFilterOption(filterName);
    });
}

function dpSetFilterSelectValue(filterName, value) {
    var select = dpGetFilterSelect(filterName);
    if (!select) {
        return;
    }

    var hasOption = false;
    Array.prototype.forEach.call(select.options, function(option) {
        if (option.value === value && !option.disabled && !option.hidden) {
            hasOption = true;
        }
    });

    select.value = hasOption ? value : 'all';
}

function dpActivateTimelineDayByTab() {
    var activeTab = document.querySelector('.dp-timeline-day-tab.active');
    var activeTagId = activeTab ? activeTab.getAttribute('data-tag-id') : null;

    document.querySelectorAll('.dp-timeline-day').forEach(function(day) {
        if (!activeTagId) {
            day.classList.remove('active');
            return;
        }

        var matches = day.getAttribute('data-tag-id') === activeTagId;
        var isHiddenByFilter = day.style.display === 'none';
        day.classList.toggle('active', matches && !isHiddenByFilter);
    });
}

document.addEventListener('DOMContentLoaded', function() {
        window.dpToggleCreateUserConsent = function() {
            var selected = jQuery('input[name="create_user_account"]:checked').val() || '0';
            var wrap = jQuery('#dp-datenschutz-wrap');
            if (!wrap.length) {
                return;
            }
            if (selected === '1') {
                wrap.show();
            } else {
                jQuery('#dp-create-user-datenschutz').prop('checked', false);
                wrap.hide();
            }
        };

        jQuery(document).on('change', 'input[name="create_user_account"]', function() {
            window.dpToggleCreateUserConsent();
        });

        window.dpToggleCreateUserConsent();

    window.dpTrace('DOMContentLoaded erreicht');
    window.dpTrace('jQuery verfügbar', { hasJQuery: typeof window.jQuery !== 'undefined' });
    var dpDebug = function() {};

    ['dpFilterAvailability', 'dpFilterTag', 'dpFilterBereich', 'dpFilterDienst'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', dpApplyFrontendFilters);
        }
    });

    document.querySelectorAll('.dp-timeline-day-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            var tabTagId = tab.getAttribute('data-tag-id');
            if (!tabTagId) {
                return;
            }
            dpSetFilterSelectValue('tag', tabTagId);
            dpApplyFrontendFilters();
        });
    });

    try {
        var params = new URLSearchParams(window.location.search);
        var availabilityParam = (params.get('availability') || '').toLowerCase();
        var tagParam = (params.get('tag') || '').toLowerCase();
        var bereichParam = (params.get('bereich') || '').toLowerCase();
        var dienstParam = (params.get('dienst') || '').toLowerCase();

        if (availabilityParam) {
            dpSetFilterSelectValue('availability', availabilityParam);
        }
        if (tagParam) {
            dpSetFilterSelectValue('tag', tagParam);
        }
        if (bereichParam) {
            dpSetFilterSelectValue('bereich', bereichParam);
        }
        if (dienstParam) {
            dpSetFilterSelectValue('dienst', dienstParam);
        }
    } catch (e) {}

    dpApplyFrontendFilters();

    if (typeof window.openAnmeldeModal !== 'function') {
        window.openAnmeldeModal = function(slotId, dienstId) {
            window.dpTrace('Fallback openAnmeldeModal', { slotId: slotId, dienstId: dienstId });
            jQuery('#dp-slot-id').val(slotId);
            jQuery('#dp-dienst-id').val(dienstId);
            jQuery('#dp-anmelde-modal').fadeIn(300);
            jQuery('body').css('overflow', 'hidden');
        };
    }

    if (typeof window.closeAnmeldeModal !== 'function') {
        window.closeAnmeldeModal = function() {
            window.dpTrace('Fallback closeAnmeldeModal');
            var modal = document.getElementById('dp-anmelde-modal');
            if (modal) {
                modal.classList.remove('dp-modal-force-open');
            }
            jQuery('#dp-anmelde-modal').fadeOut(300, function() {
                if (modal) {
                    modal.style.setProperty('display', 'none', 'important');
                    modal.style.removeProperty('visibility');
                    modal.style.removeProperty('opacity');
                }
            });
            jQuery('body').css('overflow', 'auto');
            var form = jQuery('#dp-anmelde-form')[0];
            if (form) {
                form.reset();
            }
            jQuery('#dp-loggedin-modal').hide();
        };
    }

    if (!window.dpPublicSubmitBound && !window.dpVereinSubmitBound) {
        window.dpVereinSubmitBound = true;
        jQuery(document).on('submit', '#dp-anmelde-form', function(e) {
            e.preventDefault();

            var form = jQuery(this);
            var submitBtn = form.find('button[type="submit"]');
            var originalText = submitBtn.text();
            var dpConfig = window.dpPublic || window.dpAjax || null;
            window.dpTrace('Submit gestartet', {
                hasConfig: !!dpConfig,
                hasAjaxUrl: !!(dpConfig && dpConfig.ajaxurl),
                hasNonce: !!(dpConfig && dpConfig.nonce),
                slot_id: jQuery('#dp-slot-id').val(),
                dienst_id: jQuery('#dp-dienst-id').val()
            });
            dpDebug('Submit (Fallback) gestartet', { hasConfig: !!dpConfig, slot: jQuery('#dp-slot-id').val(), dienst: jQuery('#dp-dienst-id').val() });

            if (!dpConfig || !dpConfig.ajaxurl || !dpConfig.nonce) {
                window.dpTrace('Submit Abbruch: Konfiguration fehlt');
                alert('Konfiguration fehlt. Bitte Seite neu laden.');
                dpDebug('Submit Abbruch: Konfiguration fehlt');
                return;
            }

            var formData = {
                action: 'dp_anmeldung_verein',
                nonce: dpConfig.nonce,
                slot_id: jQuery('#dp-slot-id').val(),
                dienst_id: jQuery('#dp-dienst-id').val(),
                vorname: jQuery('#dp-vorname').val(),
                nachname: jQuery('#dp-nachname').val(),
                email: jQuery('#dp-email').val(),
                telefon: jQuery('#dp-telefon').val(),
                besonderheiten: jQuery('#dp-besonderheiten').val(),
                create_user_account: jQuery('input[name="create_user_account"]:checked').val() || '0',
                create_user_datenschutz: jQuery('#dp-create-user-datenschutz').is(':checked') ? '1' : '0'
            };

            if (!formData.vorname || !formData.nachname || !formData.email) {
                window.dpTrace('Submit Abbruch: Pflichtfelder fehlen', formData);
                alert('Bitte alle Pflichtfelder ausfüllen.');
                dpDebug('Submit Abbruch: Pflichtfelder fehlen');
                return;
            }

            if (formData.create_user_account === '1' && formData.create_user_datenschutz !== '1') {
                alert('Bitte bestätige die Datenschutzerklärung für die Kontoerstellung.');
                return;
            }

            submitBtn.prop('disabled', true).text('Wird gesendet...');

            var requestSucceeded = false;
            jQuery.ajax({
                url: dpConfig.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    window.dpTrace('Submit Antwort', response);
                    dpDebug('Submit Antwort', response);
                    if (response && response.success) {
                        requestSucceeded = true;
                        alert('Vielen Dank! Die Übernahme/Zuweisung wurde gespeichert.');
                        window.closeAnmeldeModal();
                        window.location.reload();
                    } else {
                        var message = (response && response.data && response.data.message) ? response.data.message : 'Aktion fehlgeschlagen.';
                        alert('Fehler: ' + message);
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    window.dpTrace('Submit AJAX Fehler', {
                        status: status,
                        error: error,
                        responseText: xhr && xhr.responseText ? String(xhr.responseText).slice(0, 250) : ''
                    });
                    alert('Serverfehler bei der Übermittlung.');
                    dpDebug('Submit AJAX Fehler');
                    submitBtn.prop('disabled', false).text(originalText);
                },
                complete: function() {
                    window.dpTrace('Submit abgeschlossen', { requestSucceeded: requestSucceeded });
                    if (!requestSucceeded) {
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                }
            });

            return false;
        });
    }

    if (!window.dpLoggedInSubmitBound) {
        window.dpLoggedInSubmitBound = true;
        jQuery(document).on('submit', '#dp-loggedin-form', function(e) {
            e.preventDefault();

            var form = jQuery(this);
            var submitBtn = form.find('button[type="submit"]');
            var originalText = submitBtn.text();
            var dpConfig = window.dpPublic || window.dpAjax || null;

            if (!dpConfig || !dpConfig.ajaxurl || !dpConfig.nonce) {
                alert('Konfiguration fehlt. Bitte Seite neu laden.');
                return;
            }

            var payload = {
                action: 'dp_anmeldung_verein',
                nonce: dpConfig.nonce,
                slot_id: jQuery('#dp-li-slot-id').val(),
                dienst_id: jQuery('#dp-li-dienst-id').val(),
                vorname: window.dpLoggedInPrefill.vorname || 'Portal',
                nachname: window.dpLoggedInPrefill.nachname || 'Nutzer',
                email: window.dpLoggedInPrefill.email || '',
                telefon: window.dpLoggedInPrefill.telefon || '',
                besonderheiten: jQuery('#dp-li-anpassung').val(),
                create_user_account: '0',
                create_user_datenschutz: '0'
            };

            if (!payload.email || !payload.vorname) {
                alert('Bitte Profil im Portal ergänzen (Name/E-Mail fehlen).');
                return;
            }

            submitBtn.prop('disabled', true).text('Wird gespeichert...');

            jQuery.ajax({
                url: dpConfig.ajaxurl,
                type: 'POST',
                data: payload,
                success: function(response) {
                    if (response && response.success) {
                        alert('Dienst erfolgreich übernommen/angepasst.');
                        window.dpCloseLoggedInModal();
                        window.location.reload();
                    } else {
                        var message = (response && response.data && response.data.message) ? response.data.message : 'Aktion fehlgeschlagen.';
                        alert('Fehler: ' + message);
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert('Serverfehler bei der Übermittlung.');
                    submitBtn.prop('disabled', false).text(originalText);
                },
                complete: function() {
                    submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
    }

    jQuery(document).on('click', '#dp-login-modal', function(e) {
        if (e.target === this) {
            dpCloseLoginModal();
        }
    });

    jQuery(document).on('keydown', function(e) {
        if (e.key === 'Escape' && jQuery('#dp-login-modal').is(':visible')) {
            dpCloseLoginModal();
        }
    });
});
</script>

<!-- Anmelde-Modal (wird vom JavaScript eingefügt) -->
<div id="dp-anmelde-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content">
        <div class="dp-modal-header">
            <h2><?php echo $can_manage_dienste ? 'Mitarbeiter zuweisen' : 'Dienst übernehmen'; ?></h2>
            <button class="dp-modal-close" onclick="closeAnmeldeModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="dp-anmelde-form">
                <input type="hidden" id="dp-slot-id" name="slot_id">
                <input type="hidden" id="dp-dienst-id" name="dienst_id">
                
                <div class="dp-form-group">
                    <label for="dp-vorname">Vorname *</label>
                    <input type="text" id="dp-vorname" name="vorname" required>
                </div>
                
                <div class="dp-form-group">
                    <label for="dp-nachname">Nachname *</label>
                    <input type="text" id="dp-nachname" name="nachname" required>
                </div>
                
                <div class="dp-form-group">
                    <label for="dp-email">E-Mail *</label>
                    <input type="email" id="dp-email" name="email" required>
                </div>
                
                <div class="dp-form-group">
                    <label for="dp-telefon">Telefon</label>
                    <input type="tel" id="dp-telefon" name="telefon">
                </div>
                
                <div class="dp-form-group">
                    <label for="dp-besonderheiten">Besonderheiten / Anmerkungen</label>
                    <textarea id="dp-besonderheiten" name="besonderheiten" rows="3"></textarea>
                </div>

                <?php if (!$can_manage_dienste): ?>
                    <div class="dp-form-group">
                        <label>Benutzerkonto für spätere Anmeldungen anlegen?</label>
                        <div class="dp-radio-row">
                            <label><input type="radio" name="create_user_account" value="0" checked> Nein</label>
                            <label><input type="radio" name="create_user_account" value="1"> Ja</label>
                        </div>
                    </div>

                    <div class="dp-form-group dp-datenschutz-wrap" id="dp-datenschutz-wrap" style="display:none;">
                        <label class="dp-checkbox-label">
                            <input type="checkbox" id="dp-create-user-datenschutz" name="create_user_datenschutz" value="1">
                            Ich habe die Datenschutzerklärung gelesen und stimme der Erstellung eines Benutzerkontos zu.
                        </label>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="create_user_account" value="0">
                <?php endif; ?>
                
                <div class="dp-modal-footer">
                    <button type="button" class="dp-btn-secondary" onclick="closeAnmeldeModal()">Abbrechen</button>
                    <button type="submit" class="dp-btn-primary"><?php echo $can_manage_dienste ? 'Verbindlich zuweisen' : 'Verbindlich übernehmen'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!$is_logged_in): ?>
<div id="dp-login-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content">
        <div class="dp-modal-header">
            <h2>Einloggen</h2>
            <button class="dp-modal-close" onclick="dpCloseLoginModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <div class="dp-login-form-wrap">
                <?php
                wp_login_form(array(
                    'echo' => true,
                    'remember' => true,
                    'redirect' => esc_url($current_request_url),
                    'form_id' => 'dp-login-form',
                    'label_username' => 'E-Mail oder Benutzername',
                    'label_password' => 'Passwort',
                    'label_remember' => 'Angemeldet bleiben',
                    'label_log_in' => 'Einloggen',
                ));
                ?>
                <p class="dp-login-help">
                    <a href="<?php echo esc_url(wp_lostpassword_url($current_request_url)); ?>">Passwort vergessen?</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($is_logged_in): ?>
<div id="dp-loggedin-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content">
        <div class="dp-modal-header">
            <h2>Dienst übernehmen / anpassen</h2>
            <button class="dp-modal-close" onclick="dpCloseLoggedInModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="dp-loggedin-form">
                <input type="hidden" id="dp-li-slot-id" name="slot_id">
                <input type="hidden" id="dp-li-dienst-id" name="dienst_id">

                <div class="dp-form-group">
                    <label>Angemeldet als</label>
                    <div class="dp-loggedin-meta">
                        <div><strong>Vorname:</strong> <span id="dp-li-vorname">-</span></div>
                        <div><strong>Nachname:</strong> <span id="dp-li-nachname">-</span></div>
                        <div><strong>E-Mail:</strong> <span id="dp-li-email">-</span></div>
                    </div>
                </div>

                <div class="dp-form-group">
                    <label for="dp-li-anpassung">Anpassung / Hinweis (optional)</label>
                    <textarea id="dp-li-anpassung" name="anpassung" rows="3" placeholder="Optionaler Hinweis zur Übernahme oder Anpassung"></textarea>
                </div>

                <div class="dp-modal-footer">
                    <button type="button" class="dp-btn-secondary" onclick="dpCloseLoggedInModal()">Abbrechen</button>
                    <button type="submit" class="dp-btn-primary">Übernahme speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
    body .entry-title,
    body .page-title,
    body .elementor-page-title,
    body .wp-block-post-title {
        display: none !important;
    }

    .dp-verein-specific {
        max-width: 1600px;
        margin: 0 auto;
        padding: 1.5rem;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    .dp-title-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin: 0 0 1rem 0;
    }

    .dp-title-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.65rem;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        color: #334155;
        font-size: 0.85rem;
        line-height: 1.2;
    }

    .dp-title-chip .dashicons {
        font-size: 15px;
        color: #0284c7;
    }
    
    @media (max-width: 768px) {
        .dp-verein-specific {
            padding: 1rem;
        }
    }
    
    .dp-view-toggle {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.45rem;
        width: fit-content;
    }

    .dp-view-toggle-header {
        margin-bottom: 0;
        background: #ffffff;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
    }

    .dp-header-view-tools {
        margin-left: auto;
        align-self: flex-start;
    }
    
    .dp-view-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        border: 2px solid #cbd5e1;
        border-radius: 10px;
        background: white;
        color: #64748b;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .dp-view-icon-emoji {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        font-size: 18px;
        line-height: 1;
        filter: saturate(1.1);
    }
    
    .dp-view-btn:hover {
        background: #f1f5f9;
        color: #0284c7;
        border-color: #0ea5e9;
        transform: translateY(-1px) scale(1.02);
    }
    
    .dp-view-btn.active {
        background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
        color: white;
        border-color: #0284c7;
        box-shadow: 0 2px 8px rgba(14, 165, 233, 0.3);
    }

    .dp-view-btn.active .dp-view-icon-emoji {
        transform: scale(1.04);
    }

    .dp-header-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.375rem 0.75rem;
        border-radius: 8px;
        border: 1px solid;
        font-size: 0.9rem;
        font-weight: 600;
        line-height: 1.2;
    }

    .dp-header-chip-link {
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s ease;
        font-family: inherit;
    }

    .dp-header-chip-link:hover {
        transform: translateY(-1px);
        filter: brightness(0.98);
    }
    
    .dp-day-section {
        margin-bottom: 2rem;
    }

    .dp-timeline-container {
        border: 1px solid #dbe2ea;
        border-radius: 12px;
        overflow: hidden;
        background: #ffffff;
    }

    .dp-timeline-day-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .dp-timeline-day-tab {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        border-radius: 12px;
        padding: 0.5rem 1rem;
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        gap: 0.05rem;
        cursor: pointer;
        min-width: 96px;
        transition: all 0.2s ease;
    }

    .dp-timeline-day-tab .dp-tab-title {
        font-size: 0.78rem;
        font-weight: 700;
        color: #1e293b;
    }

    .dp-timeline-day-tab .dp-tab-date {
        font-size: 0.78rem;
        color: #64748b;
    }

    .dp-timeline-day-tab.active {
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        border-color: #6366f1;
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.25);
    }

    .dp-timeline-day-tab.active .dp-tab-title,
    .dp-timeline-day-tab.active .dp-tab-date {
        color: #ffffff;
    }

    .dp-timeline-day {
        display: none;
        padding: 0;
    }

    .dp-timeline-day.active {
        display: block;
    }

    .dp-timeline-day-title {
        margin: 0;
        padding: 0.85rem 1rem;
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
    }

    .dp-timeline-grid-head {
        display: grid;
        grid-template-columns: 260px 1fr;
        border-bottom: 1px solid #dbe2ea;
        background: #f8fafc;
    }

    .dp-timeline-grid-service-head {
        padding: 0.8rem 1rem;
        font-size: 1.02rem;
        font-weight: 700;
        color: #0f172a;
        border-right: 1px solid #dbe2ea;
    }

    .dp-timeline-grid-hours {
        display: grid;
        grid-template-columns: repeat(var(--dp-hour-cols, 10), minmax(58px, 1fr));
    }

    .dp-timeline-hour {
        border-left: 1px solid #dbe2ea;
        text-align: center;
        font-size: 0.86rem;
        font-weight: 700;
        color: #334155;
        padding: 0.8rem 0.2rem;
    }

    .dp-timeline-slots {
        display: flex;
        flex-direction: column;
    }

    .dp-timeline-dienst {
        display: grid;
        grid-template-columns: 260px 1fr;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
    }

    .dp-dienst-info-compact {
        padding: 0.7rem 1rem;
        border-right: 1px solid #dbe2ea;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .dp-dienst-time {
        font-size: 1.05rem;
        font-weight: 700;
        color: #2563eb;
        line-height: 1.2;
    }

    .dp-dienst-details-compact {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        flex-wrap: wrap;
        color: #334155;
        font-size: 0.98rem;
    }

    .dp-dienst-details-compact em {
        color: #475569;
        font-style: italic;
        font-size: 0.93rem;
    }

    .dp-timeline-track-wrap {
        position: relative;
        min-height: 52px;
        padding: 0;
        background: #f8fafc;
    }

    .dp-timeline-track-grid {
        position: absolute;
        inset: 0;
        display: grid;
        grid-template-columns: repeat(var(--dp-hour-cols, 10), minmax(58px, 1fr));
        pointer-events: none;
    }

    .dp-timeline-track-grid::before,
    .dp-timeline-track-grid::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        height: 1px;
        background: #e2e8f0;
    }

    .dp-timeline-track-grid::before { top: 0; }
    .dp-timeline-track-grid::after { bottom: 0; }

    .dp-timeline-track-grid > span,
    .dp-timeline-track-grid div {
        border-left: 1px solid #e2e8f0;
    }

    .dp-timeline-track-wrap::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image: repeating-linear-gradient(
            to right,
            transparent,
            transparent calc((100% / var(--dp-hour-cols, 10)) - 1px),
            #e2e8f0 calc((100% / var(--dp-hour-cols, 10)) - 1px),
            #e2e8f0 calc(100% / var(--dp-hour-cols, 10))
        );
        pointer-events: none;
    }

    .dp-timeline-track-bar {
        position: absolute;
        top: 8px;
        height: 34px;
        border-radius: 10px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0 0.6rem;
        box-shadow: 0 3px 10px rgba(37, 99, 235, 0.3);
        min-width: 130px;
        z-index: 2;
    }

    .dp-track-title {
        font-weight: 700;
        font-size: 0.9rem;
        white-space: nowrap;
    }

    .dp-track-occupancy {
        margin-left: auto;
        border-radius: 8px;
        font-size: 0.84rem;
        font-weight: 700;
        padding: 0.12rem 0.45rem;
    }

    .dp-track-occupancy.is-open {
        background: rgba(248, 113, 113, 0.25);
        color: #fee2e2;
    }

    .dp-track-occupancy.is-full {
        background: rgba(134, 239, 172, 0.25);
        color: #dcfce7;
    }

    .dp-track-own {
        background: rgba(255, 255, 255, 0.22);
        color: #ffffff;
        border-radius: 8px;
        padding: 0.1rem 0.4rem;
        font-size: 0.76rem;
        font-weight: 700;
        border: 1px solid rgba(255, 255, 255, 0.45);
        cursor: pointer;
        line-height: 1.2;
    }

    .dp-track-own:hover {
        background: rgba(255, 255, 255, 0.32);
    }

    .dp-track-action {
        border: 1px solid rgba(255, 255, 255, 0.45);
        background: rgba(255, 255, 255, 0.18);
        color: #ffffff;
        border-radius: 8px;
        padding: 0.15rem 0.45rem;
        font-size: 0.78rem;
        font-weight: 700;
        cursor: pointer;
    }

    .dp-track-action:hover {
        background: rgba(255, 255, 255, 0.28);
    }

    .dp-timeline-own-actions,
    .dp-timeline-admin-actions,
    .dp-timeline-locked-note {
        grid-column: 2;
        padding: 0.45rem 0.65rem 0.55rem;
        display: inline-flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        align-items: center;
        background: #ffffff;
    }

    .dp-timeline-locked-note {
        color: #64748b;
        font-size: 0.84rem;
    }

    .dp-frontend-filterbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.9rem;
        margin: 0 0 1.25rem 0;
        padding: 0.9rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }

    .dp-filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        min-width: 180px;
    }

    .dp-filter-group label {
        font-size: 0.8rem;
        color: #475569;
        font-weight: 600;
    }

    .dp-filter-group select {
        padding: 0.45rem 0.55rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 500;
        background: #fff;
        color: #0f172a;
        min-width: 180px;
    }

    .dp-filter-group select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
    }

    .dp-filter-group select option[disabled] {
        color: #94a3b8;
    }

    .dp-filter-reset-wrap {
        margin-left: auto;
        justify-content: flex-end;
        align-self: flex-end;
    }

    .dp-filter-reset {
        padding: 0.42rem 0.75rem;
        border: 1px solid #3b82f6;
        border-radius: 999px;
        background: #ffffff;
        color: #1e3a8a;
        cursor: pointer;
        font-size: 0.8rem;
        font-weight: 700;
        line-height: 1.2;
        opacity: 1;
    }

    .dp-filter-reset:hover {
        background: #eff6ff;
        border-color: #2563eb;
        color: #1d4ed8;
    }

    .dp-filter-reset:focus-visible {
        outline: 2px solid #93c5fd;
        outline-offset: 2px;
    }
    
    .dp-day-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 1rem;
        padding-left: 0.5rem;
        border-left: 4px solid #0ea5e9;
    }
    
    .dp-dienste-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.5rem;
    }

    .dp-day-section-compact {
        margin-bottom: 1.5rem;
    }

    .dp-dienst-rows {
        display: grid;
        gap: 0.7rem;
    }

    .dp-dienst-row {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.8rem 0.9rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.8rem;
        flex-wrap: wrap;
    }

    .dp-dienst-row-main {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
        min-width: 0;
    }

    .dp-dienst-row-time {
        font-weight: 700;
        color: #0f172a;
    }

    .dp-dienst-row-name {
        color: #0f172a;
    }

    .dp-dienst-row-hint {
        color: #64748b;
        font-size: 0.88rem;
        font-style: italic;
    }

    .dp-dienst-row-actions {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        margin-left: auto;
    }

    .dp-row-status {
        display: inline-flex;
        align-items: center;
        border-radius: 8px;
        padding: 0.2rem 0.55rem;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .dp-row-status.open {
        color: #991b1b;
        background: #fee2e2;
        border: 1px solid #fca5a5;
    }

    .dp-row-status.full {
        color: #166534;
        background: #dcfce7;
        border: 1px solid #86efac;
    }
    
    .dp-dienst-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: box-shadow 0.2s;
    }
    
    .dp-dienst-card:hover {
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .dp-dienst-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }
    
    .dp-dienst-time-big {
        font-size: 1.1rem;
        font-weight: 600;
        color: #0284c7;
    }
    
    .dp-bereich-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .dp-dienst-body h4 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0 0 0.5rem 0;
    }
    
    .dp-dienst-beschreibung {
        font-size: 0.9rem;
        color: #64748b;
        margin-bottom: 0.75rem;
    }
    
    .dp-dienst-stats {
        margin: 0.75rem 0;
    }
    
    .dp-stat {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .dp-stat-success {
        color: #dc2626;
    }
    
    .dp-stat-warning {
        color: #16a34a;
    }
    
    .dp-dienst-footer {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .dp-slot-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem;
        border-radius: 4px;
    }
    
    .dp-slot-item.frei {
        background: #fff1f2;
        border: 1px solid #fecdd3;
        cursor: pointer;
        position: relative;
        z-index: 2;
    }
    
    .dp-slot-item.belegt {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
    }

    .dp-slot.frei {
        cursor: pointer;
        position: relative;
        z-index: 2;
    }
    
    .dp-slot-nummer {
        font-size: 0.9rem;
        color: #64748b;
    }
    
    .dp-slot-belegt-name {
        font-weight: 500;
        color: #334155;
    }

    .dp-slot-content {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        justify-content: space-between;
        width: 100%;
    }

    .dp-btn-admin-split,
    .dp-btn-admin-remove {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        margin-top: 0.4rem;
        margin-left: 0.35rem;
        font-size: 0.8rem;
        border-radius: 6px;
        padding: 0.2rem 0.5rem;
        cursor: pointer;
        border: 1px solid;
    }

    .dp-btn-admin-split {
        color: #0f766e;
        background: #ccfbf1;
        border-color: #5eead4;
    }

    .dp-btn-admin-split:hover {
        background: #99f6e4;
    }

    .dp-btn-admin-remove {
        color: #9f1239;
        background: #ffe4e6;
        border-color: #fda4af;
    }

    .dp-btn-admin-remove:hover {
        background: #fecdd3;
    }

    .dp-btn-absagen {
        background: #fff1f2;
        color: #be123c;
        border: 1px solid #fecdd3;
        border-radius: 6px;
        padding: 0.25rem 0.55rem;
        font-size: 0.78rem;
        cursor: pointer;
    }

    .dp-btn-absagen:hover {
        background: #ffe4e6;
    }
    
    .dp-slot-offen-label {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        color: #991b1b;
        background: #fee2e2;
        border: 1px solid #fca5a5;
        border-radius: 6px;
        padding: 0.25rem 0.55rem;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
    }

    .dp-slot-offen-label:hover {
        background: #fecaca;
    }
    
    /* Modal-Styles */
    .dp-modal {
        display: none;
        position: fixed;
        z-index: 999999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.34), rgba(2, 6, 23, 0.52));
        backdrop-filter: blur(6px) saturate(120%);
        animation: fadeIn 0.3s;
    }

    .dp-modal.dp-modal-force-open {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        z-index: 2147483647 !important;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .dp-modal-content {
        position: relative;
        background: white;
        margin: 5% auto;
        max-width: 600px;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        animation: slideDown 0.3s;
    }
    
    @keyframes slideDown {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .dp-modal-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .dp-modal-header h2 {
        margin: 0;
        font-size: 1.5rem;
        color: #1e293b;
    }
    
    .dp-modal-close {
        background: none;
        border: none;
        font-size: 2rem;
        color: #64748b;
        cursor: pointer;
        transition: color 0.2s;
        line-height: 1;
    }
    
    .dp-modal-close:hover {
        color: #0f172a;
    }
    
    .dp-modal-body {
        padding: 2rem;
    }
    
    .dp-form-group {
        margin-bottom: 1.5rem;
    }

    .dp-radio-row {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .dp-checkbox-label {
        display: flex;
        gap: 0.5rem;
        align-items: flex-start;
        font-weight: 500;
        color: #334155;
    }

    .dp-loggedin-meta {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 0.75rem;
        display: grid;
        gap: 0.35rem;
    }

    .dp-login-form-wrap p {
        margin: 0 0 1rem 0;
    }

    .dp-login-form-wrap form {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }

    .dp-login-form-wrap label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #334155;
    }

    .dp-login-form-wrap input[type="text"],
    .dp-login-form-wrap input[type="password"] {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 1rem;
        background: #fff;
    }

    .dp-login-form-wrap input[type="text"]:focus,
    .dp-login-form-wrap input[type="password"]:focus {
        outline: none;
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }

    .dp-login-form-wrap input[type="submit"] {
        background: #0ea5e9;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 0.65rem 1.1rem;
        cursor: pointer;
        font-weight: 600;
        min-width: 160px;
    }

    .dp-login-form-wrap input[type="submit"]:hover {
        background: #0284c7;
    }

    .dp-login-form-wrap .login-remember {
        margin: 0.25rem 0 0.25rem;
    }

    .dp-login-form-wrap .login-remember label {
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
        color: #475569;
    }

    .dp-login-form-wrap .login-submit {
        margin: 0.25rem 0 0;
    }

    .dp-login-help {
        margin-top: 0.75rem;
        font-size: 0.9rem;
        text-align: left;
    }

    .dp-login-help a {
        color: #0369a1;
        text-decoration: none;
    }

    .dp-login-help a:hover {
        text-decoration: underline;
    }
    
    .dp-form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #334155;
    }
    
    .dp-form-group input[type="text"],
    .dp-form-group input[type="email"],
    .dp-form-group input[type="tel"],
    .dp-form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 1rem;
        transition: border-color 0.2s;
    }
    
    .dp-form-group input:focus,
    .dp-form-group textarea:focus {
        outline: none;
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }
    
    .dp-modal-footer {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 2rem;
    }
    
    .dp-btn-primary,
    .dp-btn-secondary {
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    
    .dp-btn-primary {
        background: #0ea5e9;
        color: white;
    }
    
    .dp-btn-primary:hover {
        background: #0284c7;
    }
    
    .dp-btn-secondary {
        background: #f1f5f9;
        color: #64748b;
    }
    
    .dp-btn-secondary:hover {
        background: #e2e8f0;
    }

    @media (max-width: 768px) {
        .dp-header-view-tools {
            margin-left: 0;
            width: 100%;
        }

        .dp-view-toggle-header {
            width: 100%;
            justify-content: flex-end;
        }

        .dp-filter-group {
            min-width: 100%;
        }

        .dp-filter-reset-wrap {
            margin-left: 0;
            align-self: flex-start;
        }

        .dp-timeline-grid-head,
        .dp-timeline-dienst {
            grid-template-columns: 1fr;
        }

        .dp-dienst-info-compact,
        .dp-timeline-grid-service-head {
            border-right: none;
        }

        .dp-timeline-track-bar {
            min-width: 110px;
        }
    }
</style>

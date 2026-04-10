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
$is_restricted_crew = false;
$crew_allowed_verein_ids = array();
$admin_dienste_url = admin_url('admin.php?page=dienstplan-dienste');
$current_mitarbeiter_id = 0;
$current_mitarbeiter = null;
$current_user_obj = null;
$admin_selectable_mitarbeiter = array();
$dp_prefix = defined('DIENSTPLAN_DB_PREFIX') ? DIENSTPLAN_DB_PREFIX : 'dp_';

if ($is_logged_in && $current_user_id > 0) {
    global $wpdb;
    $current_user_obj = wp_get_current_user();
    $is_restricted_crew = !$can_manage_dienste && in_array(Dienstplan_Roles::ROLE_CREW, (array) $current_user_obj->roles, true);

    $mitarbeiter_table = $wpdb->prefix . $dp_prefix . 'mitarbeiter';
    $current_mitarbeiter_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$mitarbeiter_table} WHERE user_id = %d LIMIT 1",
        $current_user_id
    ));

    if ($current_mitarbeiter_id > 0) {
        $current_mitarbeiter = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$mitarbeiter_table} WHERE id = %d LIMIT 1",
            $current_mitarbeiter_id
        ));
    }

    if ($is_restricted_crew) {
        $user_verein_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT verein_id FROM {$wpdb->prefix}{$dp_prefix}user_vereine WHERE user_id = %d",
            $current_user_id
        ));

        $crew_allowed_verein_ids = array_values(array_unique(array_filter(array_map('intval', (array) $user_verein_ids))));
        sort($crew_allowed_verein_ids);
    }
}

if ($can_manage_dienste) {
    $admin_selectable_mitarbeiter = $db->get_mitarbeiter();
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

if ($is_restricted_crew) {
    if (!empty($crew_allowed_verein_ids)) {
        $services = array_filter($services, function($s) use ($crew_allowed_verein_ids) {
            return in_array(intval($s->verein_id), $crew_allowed_verein_ids, true);
        });
    } else {
        $services = array();
    }
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

// Vereinsfarbe pro Dienst (mit Cache) für Buttons/Balken
$dp_verein_color_cache = [];
$dp_get_verein_color = function($dienst) use ($db, &$dp_verein_color_cache) {
    $vid = intval($dienst->verein_id ?? 0);
    if ($vid > 0) {
        if (!isset($dp_verein_color_cache[$vid])) {
            $v_obj = $db->get_verein($vid);
            $vc = (!empty($v_obj) && !empty($v_obj->farbe)) ? sanitize_hex_color($v_obj->farbe) : '';
            $dp_verein_color_cache[$vid] = !empty($vc) ? $vc : '#3b82f6';
        }
        return $dp_verein_color_cache[$vid];
    }
    return '#3b82f6';
};

// Funktion zur Generierung von Vereins-Abkürzungen
$dp_get_verein_abbrev = function($verein_name) {
    if (empty($verein_name)) {
        return '?';
    }
    // Nimm die ersten Buchstaben jedes Wortes (max. 3 Zeichen)
    $words = preg_split('/\s+/', trim($verein_name), -1, PREG_SPLIT_NO_EMPTY);
    $abbrev = '';
    foreach (array_slice($words, 0, 3) as $word) {
        $abbrev .= strtoupper(substr($word, 0, 1));
    }
    return empty($abbrev) ? '?' : $abbrev;
};

// View-Modus
$view_mode = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'kachel';
if ($view_mode === 'list') {
    $view_mode = 'kachel';
}
if (!in_array($view_mode, array('kachel', 'kompakt', 'timeline'), true)) {
    $view_mode = 'kachel';
}
$dp_debug_query = '';
if (isset($_GET['dpdebug'])) {
    $dp_debug_query = sanitize_text_field(wp_unslash($_GET['dpdebug']));
} elseif (isset($_GET['debug'])) {
    $dp_debug_query = sanitize_text_field(wp_unslash($_GET['debug']));
}
$dp_debug_enabled = ($dp_debug_query === '1') && current_user_can('manage_options');
$request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
$current_request_url = set_url_scheme(home_url($request_uri), is_ssl() ? 'https' : 'http');

// Status-Anzeige und Anmeldungs-Logik
$anmeldung_aktiv = ($veranstaltung->status === 'geplant');
$status_message = '';
$status_style = '';
$status_icon = '';

$dp_hex_to_rgba = function($hex, $alpha) {
    $hex = ltrim((string) $hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) !== 6) {
        return 'rgba(14,165,233,' . floatval($alpha) . ')';
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . floatval($alpha) . ')';
};

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
                        <?php
                        $verein_color = !empty($verein->farbe) ? sanitize_hex_color($verein->farbe) : '#0ea5e9';
                        if (empty($verein_color)) {
                            $verein_color = '#0ea5e9';
                        }
                        $verein_bg_start = $dp_hex_to_rgba($verein_color, 0.18);
                        $verein_bg_end = $dp_hex_to_rgba($verein_color, 0.35);
                        ?>
                        <div class="dp-header-chip" style="background: linear-gradient(135deg, <?php echo esc_attr($verein_bg_start); ?> 0%, <?php echo esc_attr($verein_bg_end); ?> 100%); border-color: <?php echo esc_attr($verein_color); ?>; color: #0f172a;">
                            <span class="dashicons dashicons-groups" style="font-size: 16px;"></span>
                            <span title="<?php echo esc_attr($verein->name); ?>"><?php echo esc_html(!empty($verein->kuerzel) ? $verein->kuerzel : $dp_get_verein_abbrev($verein->name)); ?></span>
                        </div>
                    <?php elseif ($verein_id == 0 && !empty($alle_vereine_in_services)): ?>
                        <?php foreach ($alle_vereine_in_services as $vid => $vname): ?>
                            <?php
                            $v_obj = $db->get_verein($vid);
                            $v_color = (!empty($v_obj) && !empty($v_obj->farbe)) ? sanitize_hex_color($v_obj->farbe) : '#0ea5e9';
                            if (empty($v_color)) {
                                $v_color = '#0ea5e9';
                            }
                            $v_bg_start = $dp_hex_to_rgba($v_color, 0.18);
                            $v_bg_end = $dp_hex_to_rgba($v_color, 0.35);
                            ?>
                            <div class="dp-header-chip" style="background: linear-gradient(135deg, <?php echo esc_attr($v_bg_start); ?> 0%, <?php echo esc_attr($v_bg_end); ?> 100%); border-color: <?php echo esc_attr($v_color); ?>; color: #0f172a;">
                                <span class="dashicons dashicons-groups" style="font-size: 14px;"></span>
                                <span title="<?php echo esc_attr($vname); ?>"><?php echo esc_html(!empty($v_obj->kuerzel) ? $v_obj->kuerzel : $dp_get_verein_abbrev($vname)); ?></span>
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

    <style>
    .dp-frontend-filterbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.9rem;
        margin: 0 0 1.25rem 0;
        padding: 0.9rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        position: sticky;
        top: 0;
        z-index: 50;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
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

    .dp-day-section {
        margin-bottom: 2rem;
    }

    .dp-day-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 1rem;
        padding-left: 0.5rem;
        border-left: 4px solid #0ea5e9;
    }

    .dp-dienste-cards,
    .dp-dienste-kompakt-list {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .dp-day-section-compact {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        overflow: hidden;
    }

    .dp-day-title {
        margin: 0;
        padding: 1rem 1.5rem;
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
    }

    .dp-dienste-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95rem;
    }

    .dp-dienste-table thead {
        background: #f0f4f8;
    }

    .dp-dienste-table th {
        padding: 0.75rem 1rem;
        text-align: left;
        font-weight: 600;
        color: #334155;
        border-bottom: 1px solid #cbd5e1;
    }

    .dp-dienste-table tbody tr {
        border-bottom: 1px solid #e8ecf1;
        transition: background-color 0.2s ease;
    }

    .dp-dienste-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .dp-dienste-table td {
        padding: 0.875rem 1rem;
        vertical-align: middle;
    }

    .dp-dienste-table .col-zeit {
        font-weight: 600;
        width: 10%;
        min-width: 90px;
    }

    .dp-dienste-table .col-bereich {
        width: 12%;
        min-width: 110px;
    }

    .dp-dienste-table .col-dienst {
        width: 18%;
        min-width: 140px;
    }

    .dp-dienste-table .col-besonderheiten {
        width: 20%;
        min-width: 150px;
        color: #64748b;
        font-size: 0.9rem;
    }

    .dp-dienste-table .col-zugeordnet {
        width: 18%;
        min-width: 140px;
        color: #64748b;
        font-size: 0.9rem;
    }

    .dp-dienste-table .col-status {
        width: 10%;
        min-width: 80px;
    }

    .dp-dienste-table .col-aktion {
        width: 12%;
        min-width: 100px;
        text-align: center;
    }

    .dp-status-badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 4px;
        font-size: 0.85rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .dp-status-badge.open {
        background: #dcfce7;
        color: #166534;
    }

    .dp-status-badge.full {
        background: #fecaca;
        color: #991b1b;
    }

    .dp-btn-anmelden {
        padding: 0.5rem 1rem;
        background: var(--dp-btn-accent, #3b82f6);
        color: #fff;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 500;
        transition: opacity 0.2s;
        white-space: nowrap;
    }

    .dp-btn-anmelden:hover {
        opacity: 0.85;
    }

    .dp-empty,
    .dp-grey-text {
        color: #94a3b8;
        font-style: italic;
    }

    .dp-bereich-badge {
        display: inline-block;
        padding: 0.35rem 0.65rem;
        border-radius: 4px;
        font-size: 0.85rem;
        font-weight: 500;
        color: #fff;
        white-space: nowrap;
    }

    .dp-assigned-names {
        color: #64748b;
        font-size: 0.9rem;
    }

    .dp-dienst-hint {
        color: #64748b;
        font-size: 0.9rem;
    }
    </style>

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
            <!-- Timeline (Schichtplan): horizontal, je Dienst eine schmale Zeile -->

            <?php

            $tl_min = 1440; $tl_max = 60;

            foreach ($dienste_nach_tagen as $_tl_tag) {

                foreach ($_tl_tag as $_tl_d) {

                    $_von = $_tl_d->von_zeit ?? ($_tl_d->zeit_von ?? '00:00');

                    $_bis = $_tl_d->bis_zeit ?? ($_tl_d->zeit_bis ?? $_von);

                    $_s = intval(substr($_von,0,2))*60 + intval(substr($_von,3,2));

                    $_e = intval(substr($_bis,0,2))*60 + intval(substr($_bis,3,2));

                    if ($_e <= $_s) $_e += 1440;

                    if ($_s < $tl_min) $tl_min = $_s;

                    if ($_e > $tl_max) $tl_max = $_e;

                }

            }

            $tl_from   = max(0, (intval(floor($tl_min / 60)) * 60) - 30);

            $tl_to     = min(2879, (intval(ceil($tl_max / 60)) * 60) + 30);

            $tl_span   = max(60, $tl_to - $tl_from);

            $tl_hstart = intval(floor($tl_from / 60));

            $tl_hend   = intval(ceil($tl_to / 60));

            $tl_hcount = max(1, $tl_hend - $tl_hstart);

            $tl_hw_pct = round((60 / $tl_span) * 100, 4);

            $tl_fh_pct = round((($tl_hstart * 60 - $tl_from) / $tl_span) * 100, 4);

            $tl_wday   = ['Monday'=>'Mo','Tuesday'=>'Di','Wednesday'=>'Mi','Thursday'=>'Do','Friday'=>'Fr','Saturday'=>'Sa','Sunday'=>'So'];

            /* Breite einer Tag-Spalte: Stundenbereich + Label */
            $tl_day_col_w = max(700, $tl_hcount * 80 + 220);

            ?>

            <div class="dp-tl-wrap" style="--dp-tl-h-w:<?php echo $tl_hw_pct; ?>%;--dp-tl-first-h:<?php echo $tl_fh_pct; ?>;opacity:0;transition:opacity 0.15s ease;">

                <div class="dp-tl-edge-hint is-left" aria-hidden="true">&#x2039;</div>
                <div class="dp-tl-edge-hint is-right" aria-hidden="true">&#x203A;</div>

                <div class="dp-tl-canvas" style="--dp-tl-day-w:<?php echo intval($tl_day_col_w); ?>px">

                <?php foreach ($dienste_nach_tagen as $tl_tag_id => $tl_tag_dienste):

                    $tl_tag = null;

                    foreach ($tage as $t) { if ($t->id == $tl_tag_id) { $tl_tag = $t; break; } }

                    $tl_traw  = $tl_tag->tag_datum ?? ($tl_tag->datum ?? null);

                    $tl_tdate = $tl_traw ? date('d.m.Y', strtotime($tl_traw)) : 'Unbekannt';

                    $tl_tdow  = $tl_traw ? ($tl_wday[date('l', strtotime($tl_traw))] ?? '') : '';

                ?>

                <div class="dp-tl-day-block" data-tag-id="<?php echo intval($tl_tag_id); ?>">

                    <div class="dp-tl-day-title">

                        <?php if ($tl_tdow): ?><span class="dp-tl-dow"><?php echo esc_html($tl_tdow); ?></span><?php endif; ?>

                        <span><?php echo esc_html($tl_tdate); ?></span>

                    </div>

                    <div class="dp-tl-day-hd">

                        <div class="dp-tl-label-col">Dienst</div>

                        <div class="dp-tl-hours">

                            <?php for ($tl_h = $tl_hstart; $tl_h <= $tl_hend; $tl_h++):

                                $tl_p = round((($tl_h * 60 - $tl_from) / $tl_span) * 100, 2);

                                if ($tl_p < -1 || $tl_p > 101) continue;

                            ?>

                            <span class="dp-tl-hlabel" style="left:<?php echo $tl_p; ?>%"><?php printf('%02d:00', $tl_h % 24); ?></span>

                            <?php endfor; ?>

                        </div>

                    </div>

                    <?php foreach ($tl_tag_dienste as $dienst):

                        $slots          = $db->get_dienst_slots($dienst->id);

                        $bereich        = $db->get_bereich($dienst->bereich_id);

                        $taetigkeit     = $db->get_taetigkeit($dienst->taetigkeit_id);

                        $freie_slots    = count(array_filter($slots, function($s) { return empty($s->mitarbeiter_id); }));

                        $besetzte_slots = count($slots) - $freie_slots;

                        $tl_assigned_names = [];
                        if ($can_manage_dienste) {
                            foreach ($slots as $slot_name_row) {
                                if (empty($slot_name_row->mitarbeiter_id)) {
                                    continue;
                                }
                                $m_obj = $db->get_mitarbeiter($slot_name_row->mitarbeiter_id);
                                if ($m_obj) {
                                    $tl_assigned_names[] = trim(($m_obj->vorname ?? '') . ' ' . substr(($m_obj->nachname ?? ''), 0, 1) . '.');
                                }
                            }
                        }

                        $has_my_slot    = ($current_mitarbeiter_id > 0) ? count(array_filter($slots, function($s) use ($current_mitarbeiter_id) {

                            return intval($s->mitarbeiter_id ?? 0) === intval($current_mitarbeiter_id);

                        })) > 0 : false;

                        $first_free_slot_id  = 0;
                        $second_free_slot_id = 0;
                        $first_own_slot_id   = 0;

                        foreach ($slots as $slot_r) {

                            if (empty($slot_r->mitarbeiter_id)) {
                                if ($first_free_slot_id === 0) {
                                    $first_free_slot_id = intval($slot_r->id);
                                } elseif ($second_free_slot_id === 0) {
                                    $second_free_slot_id = intval($slot_r->id);
                                }
                            }

                            if ($first_own_slot_id === 0 && $current_mitarbeiter_id > 0 && intval($slot_r->mitarbeiter_id) === intval($current_mitarbeiter_id)) $first_own_slot_id = intval($slot_r->id);

                            if ($first_free_slot_id && $second_free_slot_id && $first_own_slot_id) break;

                        }

                        $tl_von  = $dienst->von_zeit ?? ($dienst->zeit_von ?? '00:00:00');

                        $tl_bis  = $dienst->bis_zeit ?? ($dienst->zeit_bis ?? $tl_von);

                        $tl_vm   = intval(substr($tl_von,0,2))*60 + intval(substr($tl_von,3,2));

                        $tl_bm   = intval(substr($tl_bis,0,2))*60 + intval(substr($tl_bis,3,2));

                        if ($tl_bm <= $tl_vm) $tl_bm += 1440;

                        $tl_bl   = round(max(0, min(100, (($tl_vm - $tl_from) / $tl_span) * 100)), 2);

                        $tl_bw   = round(max(0.5, min(100 - $tl_bl, (($tl_bm - $tl_vm) / $tl_span) * 100)), 2);

                        $tl_bclr = $dp_get_verein_color($dienst);
                        // Split-Ansicht im Balken immer dann, wenn der Dienst mindestens 2 Slots hat.
                        $tl_is_split = count($slots) >= 2;
                        $tl_bar_label = !empty($tl_assigned_names)
                            ? implode(', ', $tl_assigned_names)
                            : ($taetigkeit->name ?? 'Unbekannt');
                        $tl_split_slots = array_values(array_slice($slots, 0, 2));
                        while (count($tl_split_slots) < 2) {
                            $tl_split_slots[] = null;
                        }

                    ?>

                    <div class="dp-tl-row"

                         data-tag-id="<?php echo intval($tl_tag_id); ?>"

                         data-bereich-id="<?php echo intval($dienst->bereich_id); ?>"

                         data-taetigkeit-id="<?php echo intval($dienst->taetigkeit_id); ?>"

                         data-has-free="<?php echo $freie_slots > 0 ? '1' : '0'; ?>"

                         data-has-mine="<?php echo $has_my_slot ? '1' : '0'; ?>"

                         data-start-minutes="<?php echo intval($tl_vm); ?>">

                        <div class="dp-tl-label">

                            <div class="dp-tl-svc-name"><?php echo esc_html($taetigkeit->name ?? 'Unbekannt'); ?></div>

                            <div class="dp-tl-svc-meta">

                                <span class="dp-tl-svc-bereich" style="color:<?php echo esc_attr($tl_bclr); ?>"><?php echo esc_html($bereich->name ?? ''); ?></span>

                                <span class="dp-tl-occ <?php echo $freie_slots > 0 ? 'is-open' : 'is-full'; ?>"><?php echo intval($besetzte_slots); ?>/<?php echo intval(count($slots)); ?></span>

                            </div>

                        </div>

                        <div class="dp-tl-track">

                            <div class="dp-tl-bar<?php echo $tl_is_split ? ' is-split' : ''; ?>" style="left:<?php echo $tl_bl; ?>%;width:<?php echo $tl_bw; ?>%;background:<?php echo esc_attr($tl_bclr); ?>;">

                                <?php if ($tl_is_split): ?>
                                <div class="dp-tl-split-halves">
                                    <?php foreach ($tl_split_slots as $tl_half_slot): ?>
                                        <?php
                                        $tl_half_slot_id = $tl_half_slot ? intval($tl_half_slot->id) : 0;
                                        $tl_half_is_occupied = $tl_half_slot && !empty($tl_half_slot->mitarbeiter_id);
                                        $tl_half_is_own = $tl_half_slot && $current_mitarbeiter_id > 0 && intval($tl_half_slot->mitarbeiter_id) === intval($current_mitarbeiter_id);
                                        $tl_half_label = 'Frei';
                                        if ($tl_half_is_occupied) {
                                            $tl_half_m_obj = $db->get_mitarbeiter($tl_half_slot->mitarbeiter_id);
                                            if ($tl_half_m_obj && ($can_manage_dienste || $tl_half_is_own)) {
                                                $tl_half_label = trim(($tl_half_m_obj->vorname ?? '') . ' ' . substr(($tl_half_m_obj->nachname ?? ''), 0, 1) . '.');
                                            } else {
                                                $tl_half_label = 'Besetzt';
                                            }
                                        }
                                        ?>
                                        <div class="dp-tl-split-half">
                                            <span class="dp-tl-bar-time"><?php echo esc_html($tl_half_label); ?></span>
                                            <div class="dp-tl-split-action">
                                                <?php if ($tl_half_is_own && $tl_half_slot_id > 0): ?>
                                                    <button type="button" class="dp-tl-bar-btn" onclick="dpCancelDienst(<?php echo intval($tl_half_slot_id); ?>, this)">Absagen</button>
                                                <?php elseif ($anmeldung_aktiv && !$tl_half_is_occupied && $tl_half_slot_id > 0): ?>
                                                    <button type="button" class="dp-tl-bar-btn dp-tl-bar-btn--takeover" style="--dp-btn-accent:<?php echo esc_attr($tl_bclr); ?>;" onclick="return dpOpenTakeoverModal(<?php echo intval($tl_half_slot_id); ?>, <?php echo intval($dienst->id); ?>, event);">Anmelden</button>
                                                <?php elseif (!$anmeldung_aktiv && !$tl_half_is_occupied): ?>
                                                    <span class="dp-tl-locked" title="Anmeldung gesperrt">&#x1F512;</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <span class="dp-tl-bar-time"><?php echo esc_html($tl_bar_label); ?></span>

                                <?php if ($has_my_slot && $first_own_slot_id > 0): ?>

                                <button type="button" class="dp-tl-bar-btn" onclick="dpCancelDienst(<?php echo intval($first_own_slot_id); ?>, this)">Absagen</button>

                                <?php endif; ?>

                                <?php if ($anmeldung_aktiv && $first_free_slot_id > 0): ?>

                                <button type="button" class="dp-tl-bar-btn dp-tl-bar-btn--takeover" style="--dp-btn-accent:<?php echo esc_attr($tl_bclr); ?>;" onclick="return dpOpenTakeoverModal(<?php echo intval($first_free_slot_id); ?>, <?php echo intval($dienst->id); ?>, event, <?php echo intval($second_free_slot_id); ?>, <?php echo intval($dienst->splittbar ?? 0); ?>);">Anmelden</button>

                                <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($can_manage_dienste && !$tl_is_split): ?>

                                    <?php foreach ($slots as $slot_rm): if (empty($slot_rm->mitarbeiter_id)) continue; ?>

                                    <button type="button" class="dp-tl-bar-btn dp-tl-bar-btn-rm" onclick="dpAdminRemoveSlot(<?php echo intval($slot_rm->id); ?>, this)" title="Zuweisung l&ouml;schen">&#x2715;</button>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                                <?php if (!$anmeldung_aktiv && $freie_slots > 0 && !$tl_is_split): ?>

                                <span class="dp-tl-locked" title="Anmeldung gesperrt">&#x1F512;</span>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                    <?php endforeach; ?>

                </div>

                <?php endforeach; ?>

                </div><!-- .dp-tl-canvas -->

            </div><!-- .dp-tl-wrap -->
        <?php elseif ($view_mode === 'kompakt'): ?>
            <!-- Kompakte Listen-Ansicht (Tabelle) -->
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

                        <table class="dp-dienste-table">
                            <thead>
                                <tr>
                                    <th class="col-zeit">Zeit</th>
                                    <th class="col-bereich">Bereich</th>
                                    <th class="col-dienst">Dienst</th>
                                    <th class="col-besonderheiten">Besonderheiten</th>
                                    <th class="col-zugeordnet">Zugeordnet</th>
                                    <th class="col-status">Status</th>
                                    <th class="col-aktion">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($tag_dienste as $dienst):
                                $slots = $db->get_dienst_slots($dienst->id);
                                $bereich = $db->get_bereich($dienst->bereich_id);
                                $taetigkeit = $db->get_taetigkeit($dienst->taetigkeit_id);
                                $freie_slots = count(array_filter($slots, function($s) { return empty($s->mitarbeiter_id); }));

                                $kp_assigned_names = [];
                                if ($can_manage_dienste) {
                                    foreach ($slots as $kp_slot_row) {
                                        if (empty($kp_slot_row->mitarbeiter_id)) {
                                            continue;
                                        }
                                        $kp_m_obj = $db->get_mitarbeiter($kp_slot_row->mitarbeiter_id);
                                        if ($kp_m_obj) {
                                            $kp_assigned_names[] = trim(($kp_m_obj->vorname ?? '') . ' ' . substr(($kp_m_obj->nachname ?? ''), 0, 1) . '.');
                                        }
                                    }
                                }

                                $has_my_slot = ($current_mitarbeiter_id > 0) ? count(array_filter($slots, function($s) use ($current_mitarbeiter_id) {
                                    return intval($s->mitarbeiter_id ?? 0) === intval($current_mitarbeiter_id);
                                })) > 0 : false;
                                $first_free_slot_id  = 0;
                                $second_free_slot_id = 0;
                                foreach ($slots as $slot_row) {
                                    if (empty($slot_row->mitarbeiter_id)) {
                                        if ($first_free_slot_id === 0) {
                                            $first_free_slot_id = intval($slot_row->id);
                                        } else {
                                            $second_free_slot_id = intval($slot_row->id);
                                            break;
                                        }
                                    }
                                }
                                $dienst_von = $dienst->von_zeit ?? ($dienst->zeit_von ?? '');
                                $dienst_bis = $dienst->bis_zeit ?? ($dienst->zeit_bis ?? '');
                                $dienst_beschreibung = $dienst->beschreibung ?? ($dienst->besonderheiten ?? '');
                            ?>
                                <tr class="dp-dienst-row" data-tag-id="<?php echo intval($tag_id); ?>" data-dienst-id="<?php echo $dienst->id; ?>" data-bereich-id="<?php echo intval($dienst->bereich_id); ?>" data-taetigkeit-id="<?php echo intval($dienst->taetigkeit_id); ?>" data-has-free="<?php echo $freie_slots > 0 ? '1' : '0'; ?>" data-has-mine="<?php echo $has_my_slot ? '1' : '0'; ?>">
                                    <td class="col-zeit">
                                        <strong><?php echo substr($dienst_von, 0, 5) . ' - ' . substr($dienst_bis, 0, 5); ?></strong>
                                    </td>
                                    <td class="col-bereich">
                                        <span class="dp-bereich-badge" style="background-color: <?php echo esc_attr($bereich->farbe ?? '#e2e8f0'); ?>;">
                                            <?php echo esc_html($bereich->name ?? ''); ?>
                                        </span>
                                    </td>
                                    <td class="col-dienst">
                                        <strong><?php echo esc_html($taetigkeit->name ?? 'Unbekannt'); ?></strong>
                                    </td>
                                    <td class="col-besonderheiten">
                                        <?php if (!empty($dienst_beschreibung)): ?>
                                            <span class="dp-dienst-hint"><?php echo esc_html($dienst_beschreibung); ?></span>
                                        <?php else: ?>
                                            <span class="dp-empty">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-zugeordnet">
                                        <?php if ($can_manage_dienste && !empty($kp_assigned_names)): ?>
                                            <span class="dp-assigned-names"><?php echo esc_html(implode(', ', $kp_assigned_names)); ?></span>
                                        <?php else: ?>
                                            <span class="dp-empty">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-status">
                                        <span class="dp-status-badge <?php echo $freie_slots > 0 ? 'open' : 'full'; ?>">
                                            <?php echo $freie_slots > 0 ? ($freie_slots . ' frei') : 'Voll'; ?>
                                        </span>
                                    </td>
                                    <td class="col-aktion">
                                        <?php if ($anmeldung_aktiv && $first_free_slot_id > 0): ?>
                                            <?php $dienst_btn_color = $dp_get_verein_color($dienst); ?>
                                            <button type="button" class="dp-btn-anmelden" style="--dp-btn-accent:<?php echo esc_attr($dienst_btn_color); ?>;" onclick="return dpOpenTakeoverModal(<?php echo $first_free_slot_id; ?>, <?php echo intval($dienst->id); ?>, event, <?php echo intval($second_free_slot_id); ?>, <?php echo intval($dienst->splittbar ?? 0); ?>);">Übernehmen</button>
                                        <?php elseif ($freie_slots === 0): ?>
                                            <span class="dp-grey-text">Voll</span>
                                        <?php else: ?>
                                            <span class="dp-grey-text">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
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
                                
                                <div class="dp-dienst-card" data-tag-id="<?php echo intval($tag_id); ?>" data-dienst-id="<?php echo $dienst->id; ?>" data-bereich-id="<?php echo intval($dienst->bereich_id); ?>" data-taetigkeit-id="<?php echo intval($dienst->taetigkeit_id); ?>" data-admin-only="<?php echo $taetigkeit->admin_only ? '1' : '0'; ?>" data-has-free="<?php echo $freie_slots > 0 ? '1' : '0'; ?>" data-has-mine="<?php echo $has_my_slot ? '1' : '0'; ?>">
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
                                                        <?php $dienst_btn_color = $dp_get_verein_color($dienst); ?>
                                                        <?php $is_admin_only_blocked = !empty($taetigkeit->admin_only) && !$can_manage_dienste; ?>
                                                        <?php if ($is_admin_only_blocked): ?>
                                                            <span class="dp-slot-admin-only" style="color: #d97706; font-size: 0.875rem; display: flex; align-items: center; gap: 0.4rem; cursor: not-allowed; opacity: 0.6;">
                                                                <span class="dashicons dashicons-lock" style="width: 18px; height: 18px; font-size: 18px;"></span> 
                                                                Admin-only
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="dp-slot-offen-label" style="--dp-btn-accent:<?php echo esc_attr($dienst_btn_color); ?>;" onclick="return dpOpenTakeoverModal(<?php echo intval($slot->id); ?>, <?php echo intval($dienst->id); ?>, event);" title="<?php echo $is_admin_only_blocked ? 'Nur Admins können diese Tätigkeit zuweisen' : 'Dienst übernehmen'; ?>">
                                                                <span class="dashicons dashicons-unlock"></span>
                                                                Übernehmen
                                                            </span>
                                                        <?php endif; ?>
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
window.dpVereinDebug = <?php echo $dp_debug_enabled ? 'true' : 'false'; ?>;
window.dpTrace = function(message, payload) {
    if (!window.dpVereinDebug) {
        return;
    }

    if (typeof payload !== 'undefined') {
        console.warn('[DP DEBUG]', message, payload);
    } else {
        console.warn('[DP DEBUG]', message);
    }

    var bootstrap = document.getElementById('dp-debug-bootstrap');
    if (bootstrap) {
        var text = typeof payload !== 'undefined' ? (message + ' ' + JSON.stringify(payload)) : message;
        bootstrap.textContent = 'DP DEBUG BOOTSTRAP aktiv | ' + text;
    }
};

window.dpTrace('Script geladen: veranstaltung-verein');
window.dpLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
window.dpCanManageDienste = <?php echo $can_manage_dienste ? 'true' : 'false'; ?>;
<?php if ($can_manage_dienste): ?>window.dpAdminNonce = '<?php echo esc_js(wp_create_nonce('dp_ajax_nonce')); ?>';<?php endif; ?>
window.dpCurrentMitarbeiterId = <?php echo intval($current_mitarbeiter_id); ?>;
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

window.dpOpenTakeoverModal = function(slotId, dienstId, event, slot2Id, isSplittbar) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    slotId      = parseInt(slotId      || '0', 10);
    dienstId    = parseInt(dienstId    || '0', 10);
    slot2Id     = parseInt(slot2Id     || '0', 10);
    isSplittbar = parseInt(isSplittbar || '0', 10);
    var showSplitChoice = (isSplittbar === 1 && slot2Id > 0);
    window.dpTrace('Klick auf Frei', { slotId: slotId, dienstId: dienstId, slot2Id: slot2Id, splittbar: isSplittbar });

    if (!slotId || !dienstId) {
        window.dpTrace('Abbruch: ungültige IDs', { slotId: slotId, dienstId: dienstId });
        alert('Dienstdaten konnten nicht geladen werden. Bitte Seite neu laden.');
        return false;
    }

    // Prüfe ob die Tätigkeit admin-only ist
    var dienstCard = document.querySelector('[data-dienst-id="' + dienstId + '"]');
    if (dienstCard && dienstCard.getAttribute('data-admin-only') === '1' && !window.dpCanManageDienste) {
        alert('⛔ Diese Tätigkeit kann nur durch Administratoren zugewiesen werden.\n\nBitte kontaktieren Sie einen Admin oder einen Verantwortlichen.');
        window.dpTrace('Admin-only Dienst Blockierung', { dienstId: dienstId, adminOnly: true, isAdmin: window.dpCanManageDienste });
        return false;
    }

    if (window.dpLoggedIn) {
        return window.dpOpenLoggedInModal(slotId, dienstId, event, slot2Id, showSplitChoice);
    }

    jQuery('#dp-slot-id').val(slotId);
    jQuery('#dp-dienst-id').val(dienstId);
    if (showSplitChoice) {
        jQuery('#dp-split-group').show();
        jQuery('input[name="dp_split_wahl"][value="first"]').prop('checked', true);
        jQuery('#dp-split-group').off('change.split').on('change.split', 'input[name="dp_split_wahl"]', function() {
            jQuery('#dp-slot-id').val(this.value === 'second' ? slot2Id : slotId);
        });
    } else {
        jQuery('#dp-split-group').hide();
    }
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

window.closeAnmeldeModal = function() {
    var modal = document.getElementById('dp-anmelde-modal');
    if (modal) {
        modal.classList.remove('dp-modal-force-open');
    }
    jQuery('#dp-anmelde-modal').stop(true, true).fadeOut(200, function() {
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
};

window.dpOpenLoggedInModal = function(slotId, dienstId, event, slot2Id, showSplitChoice) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    var modal = document.getElementById('dp-loggedin-modal');
    if (!modal) {
        alert('Login-Modal nicht gefunden. Bitte Seite neu laden.');
        return false;
    }

    slot2Id = parseInt(slot2Id || '0', 10);
    jQuery('#dp-li-slot-id').val(slotId);
    jQuery('#dp-li-dienst-id').val(dienstId);
    if (showSplitChoice) {
        jQuery('#dp-li-split-group').show();
        jQuery('input[name="dp_li_split_wahl"][value="first"]').prop('checked', true);
        jQuery('#dp-li-split-group').off('change.split').on('change.split', 'input[name="dp_li_split_wahl"]', function() {
            jQuery('#dp-li-slot-id').val(this.value === 'second' ? slot2Id : slotId);
        });
    } else {
        jQuery('#dp-li-split-group').hide();
    }

    if (window.dpCanManageDienste) {
        var select = document.getElementById('dp-li-mitarbeiter-id');
        if (select) {
            if (!select.value && window.dpCurrentMitarbeiterId > 0) {
                select.value = String(window.dpCurrentMitarbeiterId);
            }
            window.dpApplyAdminMitarbeiterSelection();
        }
    } else {
        jQuery('#dp-li-vorname').text(window.dpLoggedInPrefill.vorname || '-');
        jQuery('#dp-li-nachname').text(window.dpLoggedInPrefill.nachname || '-');
        jQuery('#dp-li-email').text(window.dpLoggedInPrefill.email || '-');
    }

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

window.dpApplyAdminMitarbeiterSelection = function() {
    if (!window.dpCanManageDienste) {
        return;
    }

    var select = document.getElementById('dp-li-mitarbeiter-id');
    if (!select || !select.value) {
        jQuery('#dp-li-vorname').text('-');
        jQuery('#dp-li-nachname').text('-');
        jQuery('#dp-li-email').text('-');
        return;
    }

    var option = select.options[select.selectedIndex];
    if (!option) {
        return;
    }

    var vorname = option.getAttribute('data-vorname') || '-';
    var nachname = option.getAttribute('data-nachname') || '-';
    var email = option.getAttribute('data-email') || '-';

    jQuery('#dp-li-vorname').text(vorname);
    jQuery('#dp-li-nachname').text(nachname);
    jQuery('#dp-li-email').text(email);
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

function dpApplyFrontendFilters() {
    dpUpdateFilterOptionVisibility();

    var availability = dpGetActiveFilterValue('availability');
    var tag = dpGetActiveFilterValue('tag');
    var bereich = dpGetActiveFilterValue('bereich');
    var dienst = dpGetActiveFilterValue('dienst');

    var items = document.querySelectorAll('.dp-dienst-card, .dp-dienst-row, .dp-tl-row');
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

    document.querySelectorAll('.dp-tl-day-block').forEach(function(block) {
        var blockTag = block.getAttribute('data-tag-id') || '';
        var tagMatches = (tag === 'all' || blockTag === tag);
        var visibleRows = block.querySelectorAll('.dp-tl-row:not([style*="display: none"])').length;
        block.style.display = (tagMatches && visibleRows > 0) ? '' : 'none';
    });

    if (typeof window.dpUpdateTimelineEdgeHints === 'function') {
        window.dpUpdateTimelineEdgeHints();
    }

    /* Flash-Fix: Content erst nach Filter-Lauf einblenden */
    document.querySelectorAll('.dp-tl-wrap, .dp-dienste-kompakt-list, .dp-dienste-kachel-outer').forEach(function(el) {
        el.style.opacity = '1';
    });
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
    var items = Array.prototype.slice.call(document.querySelectorAll('.dp-dienst-card, .dp-dienst-row, .dp-tl-row'));
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
document.addEventListener('DOMContentLoaded', function() {
        window.dpToggleCreateUserConsent = function() {
            var selected = jQuery('input[name="create_user_account"]:checked').val() || '0';
            var wrap = jQuery('#dp-datenschutz-wrap');
            var emailInput = jQuery('#dp-email');
            var emailLabel = jQuery('#dp-email-label');
            if (selected === '1') {
                if (wrap.length) { wrap.show(); }
                emailInput.attr('required', true);
                emailLabel.text('E-Mail *');
            } else {
                jQuery('#dp-create-user-datenschutz').prop('checked', false);
                if (wrap.length) { wrap.hide(); }
                emailInput.removeAttr('required');
                emailLabel.text('E-Mail');
            }
        };

        jQuery(document).on('change', 'input[name="create_user_account"]', function() {
            window.dpToggleCreateUserConsent();
        });

        window.dpToggleCreateUserConsent();

    window.dpTrace('DOMContentLoaded erreicht');
    window.dpTrace('jQuery verf├╝gbar', { hasJQuery: typeof window.jQuery !== 'undefined' });
    var dpDebug = function() {};

    ['dpFilterAvailability', 'dpFilterTag', 'dpFilterBereich', 'dpFilterDienst'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', dpApplyFrontendFilters);
        }
    });

    window.dpUpdateTimelineEdgeHints = function() {
        var wrap = document.querySelector('.dp-tl-wrap');
        if (!wrap) return;
        var leftHint = wrap.querySelector('.dp-tl-edge-hint.is-left');
        var rightHint = wrap.querySelector('.dp-tl-edge-hint.is-right');
        if (!leftHint || !rightHint) return;

        var maxScroll = Math.max(0, wrap.scrollWidth - wrap.clientWidth);
        var sl = wrap.scrollLeft || 0;
        leftHint.classList.toggle('is-visible', sl > 8);
        rightHint.classList.toggle('is-visible', sl < (maxScroll - 8));
    };

    var tlWrap = document.querySelector('.dp-tl-wrap');
    if (tlWrap) {
        tlWrap.addEventListener('scroll', window.dpUpdateTimelineEdgeHints, { passive: true });
        window.addEventListener('resize', window.dpUpdateTimelineEdgeHints);
        setTimeout(window.dpUpdateTimelineEdgeHints, 60);
    }

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
                email: jQuery.trim(jQuery('#dp-email').val()),
                besonderheiten: jQuery('#dp-besonderheiten').val(),
                create_user_account: jQuery('input[name="create_user_account"]:checked').val() || '0',
                create_user_datenschutz: jQuery('#dp-create-user-datenschutz').is(':checked') ? '1' : '0'
            };

            if (!formData.vorname || !formData.nachname || (formData.create_user_account === '1' && !formData.email)) {
                window.dpTrace('Submit Abbruch: Pflichtfelder fehlen', formData);
                alert('Bitte alle Pflichtfelder ausfüllen.');
                dpDebug('Submit Abbruch: Pflichtfelder fehlen');
                return;
            }

            if (formData.create_user_account === '1' && formData.create_user_datenschutz !== '1') {
                alert('Bitte best├ñtige die Datenschutzerkl├ñrung f├╝r die Kontoerstellung.');
                return;
            }

            submitBtn.prop('disabled', true).text('Wird gesendet...');

            var requestSucceeded = false;
            var sendAnmeldungRequest = function(payload) {
                jQuery.ajax({
                    url: dpConfig.ajaxurl,
                    type: 'POST',
                    data: payload,
                    success: function(response) {
                        window.dpTrace('Submit Antwort', response);
                        dpDebug('Submit Antwort', response);
                        if (response && response.success) {
                            requestSucceeded = true;
                            var successMessage = (response && response.data && response.data.message)
                                ? response.data.message
                                : 'Vielen Dank! Die Übernahme/Zuweisung wurde gespeichert.';
                            var diagnoseText = (response && response.data && response.data.diagnose_text)
                                ? ('\n\n' + response.data.diagnose_text)
                                : '';
                            alert(successMessage + diagnoseText);
                            window.closeAnmeldeModal();
                            window.location.reload();
                            return;
                        }

                        var responseCode = (response && response.data && response.data.code) ? response.data.code : '';
                        var existing = (response && response.data && response.data.existing_mitarbeiter) ? response.data.existing_mitarbeiter : null;
                        if (responseCode === 'existing_mitarbeiter_found' && existing && existing.id) {
                            var displayName = jQuery.trim(((existing.vorname || '') + ' ' + (existing.nachname || '')));
                            var confirmText = displayName
                                ? ('Die E-Mail-Adresse ist bereits dem Mitarbeiter "' + displayName + '" zugeordnet. Soll dieser Mitarbeiter verwendet werden?')
                                : 'Die E-Mail-Adresse ist bereits einem Mitarbeiter zugeordnet. Soll dieser Mitarbeiter verwendet werden?';

                            if (window.confirm(confirmText)) {
                                var retryPayload = jQuery.extend({}, payload, {
                                    use_existing_mitarbeiter: '1'
                                });
                                sendAnmeldungRequest(retryPayload);
                                return;
                            }
                        }

                        var message = (response && response.data && response.data.message) ? response.data.message : 'Aktion fehlgeschlagen.';
                        alert('Fehler: ' + message);
                        submitBtn.prop('disabled', false).text(originalText);
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
            };

            sendAnmeldungRequest(formData);

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
                besonderheiten: jQuery('#dp-li-anpassung').val(),
                create_user_account: '0',
                create_user_datenschutz: '0'
            };

            if (window.dpCanManageDienste) {
                var select = document.getElementById('dp-li-mitarbeiter-id');
                var selectedId = select ? parseInt(select.value || '0', 10) : 0;

                if (!selectedId) {
                    alert('Bitte einen Mitarbeiter auswählen.');
                    return;
                }

                var option = select.options[select.selectedIndex];
                payload.selected_mitarbeiter_id = String(selectedId);
                payload.vorname = (option && option.getAttribute('data-vorname')) || '';
                payload.nachname = (option && option.getAttribute('data-nachname')) || '';
                payload.email = (option && option.getAttribute('data-email')) || '';
                payload.telefon = (option && option.getAttribute('data-telefon')) || '';
            } else {
                payload.vorname = window.dpLoggedInPrefill.vorname || 'Portal';
                payload.nachname = window.dpLoggedInPrefill.nachname || 'Nutzer';
                payload.email = window.dpLoggedInPrefill.email || '';
                payload.telefon = window.dpLoggedInPrefill.telefon || '';
            }

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

    if (window.dpCanManageDienste) {
        jQuery(document).on('change', '#dp-li-mitarbeiter-id', function() {
            window.dpApplyAdminMitarbeiterSelection();
        });

        // Neuer Mitarbeiter anlegen – Toggle
        jQuery(document).on('click', '#dp-li-new-ma-toggle', function() {
            jQuery('#dp-li-new-ma-form').slideToggle(150);
            jQuery('#dp-li-new-ma-msg').text('');
        });
        jQuery(document).on('click', '#dp-li-new-ma-cancel', function() {
            jQuery('#dp-li-new-ma-form').slideUp(150);
            jQuery('#dp-li-new-ma-vorname, #dp-li-new-ma-nachname, #dp-li-new-ma-email, #dp-li-new-ma-telefon').val('');
            jQuery('#dp-li-new-ma-msg').text('');
        });
        jQuery(document).on('click', '#dp-li-new-ma-save', function() {
            var vorname  = jQuery.trim(jQuery('#dp-li-new-ma-vorname').val());
            var nachname = jQuery.trim(jQuery('#dp-li-new-ma-nachname').val());
            var email    = jQuery.trim(jQuery('#dp-li-new-ma-email').val());
            var telefon  = jQuery.trim(jQuery('#dp-li-new-ma-telefon').val());
            var msg      = jQuery('#dp-li-new-ma-msg');

            if (!vorname || !nachname) {
                msg.css('color','#d63638').text('Vorname und Nachname sind Pflicht.');
                return;
            }

            var dpConfig = window.dpPublic || window.dpAjax || null;
            if (!dpConfig || !dpConfig.ajaxurl || !dpConfig.nonce) {
                msg.css('color','#d63638').text('Konfiguration fehlt. Seite neu laden.');
                return;
            }

            var btn = jQuery(this);
            btn.prop('disabled', true).text('Wird angelegt…');
            msg.css('color','#666').text('');

            jQuery.post(dpConfig.ajaxurl, {
                action: 'dp_add_mitarbeiter',
                nonce:  (window.dpAdminNonce || dpConfig.nonce),
                vorname:  vorname,
                nachname: nachname,
                email:    email,
                telefon:  telefon,
                verein_id: 0
            }, function(res) {
                if (res && res.success && res.data && res.data.id) {
                    var newId   = res.data.id;
                    var label   = vorname + ' ' + nachname + (email ? ' (' + email + ')' : '');
                    var newOpt  = jQuery('<option></option>')
                        .val(newId)
                        .text(label)
                        .attr('data-vorname', vorname)
                        .attr('data-nachname', nachname)
                        .attr('data-email', email)
                        .attr('data-telefon', telefon);
                    jQuery('#dp-li-mitarbeiter-id').append(newOpt).val(newId).trigger('change');

                    jQuery('#dp-li-new-ma-form').slideUp(150);
                    jQuery('#dp-li-new-ma-vorname, #dp-li-new-ma-nachname, #dp-li-new-ma-email, #dp-li-new-ma-telefon').val('');
                    msg.css('color','#00a32a').text('✓ ' + vorname + ' ' + nachname + ' angelegt und ausgewählt.');
                    setTimeout(function(){ msg.text(''); }, 4000);
                } else {
                    var errMsg = (res && res.data && res.data.message) ? res.data.message : 'Fehler beim Anlegen.';
                    msg.css('color','#d63638').text(errMsg);
                }
            }).fail(function() {
                msg.css('color','#d63638').text('Serverfehler. Bitte erneut versuchen.');
            }).always(function() {
                btn.prop('disabled', false).text('Anlegen & auswählen');
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
                    <label for="dp-email" id="dp-email-label">E-Mail</label>
                    <input type="email" id="dp-email" name="email">
                </div>
                
                <div id="dp-split-group" class="dp-form-group" style="display:none; background:#f0f9ff; border:1px solid #7dd3fc; border-radius:6px; padding:12px 14px;">
                    <label><strong>&#x2702;&#xFE0F; Welche Hälfte möchten Sie übernehmen?</strong></label>
                    <div class="dp-radio-row" style="margin-top:8px; gap:20px;">
                        <label><input type="radio" name="dp_split_wahl" value="first" checked> 1. Hälfte</label>
                        <label><input type="radio" name="dp_split_wahl" value="second"> 2. Hälfte</label>
                    </div>
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
                            Ich habe die
                            <?php
                            $dp_dsgvo_url = get_option('dp_datenschutz_url', '');
                            if (!empty($dp_dsgvo_url)):
                            ?><a href="<?php echo esc_url($dp_dsgvo_url); ?>" target="_blank" rel="noopener">Datenschutzerklärung</a><?php
                            else:
                            ?>Datenschutzerklärung<?php
                            endif;
                            ?> gelesen und stimme der Erstellung eines Benutzerkontos zu.
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

                <?php if ($can_manage_dienste): ?>
                    <div class="dp-form-group">
                        <label for="dp-li-mitarbeiter-id">Mitarbeiter auswählen *</label>
                        <select id="dp-li-mitarbeiter-id" name="selected_mitarbeiter_id" required>
                            <option value="">Bitte auswählen</option>
                            <?php foreach ($admin_selectable_mitarbeiter as $admin_ma): ?>
                                <option
                                    value="<?php echo intval($admin_ma->id); ?>"
                                    data-vorname="<?php echo esc_attr($admin_ma->vorname ?? ''); ?>"
                                    data-nachname="<?php echo esc_attr($admin_ma->nachname ?? ''); ?>"
                                    data-email="<?php echo esc_attr($admin_ma->email ?? ''); ?>"
                                    data-telefon="<?php echo esc_attr($admin_ma->telefon ?? ''); ?>"
                                >
                                    <?php echo esc_html(trim(($admin_ma->vorname ?? '') . ' ' . ($admin_ma->nachname ?? ''))); ?>
                                    <?php if (!empty($admin_ma->email)): ?>
                                        (<?php echo esc_html($admin_ma->email); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Neuen Mitarbeiter anlegen -->
                    <div class="dp-form-group">
                        <button type="button" id="dp-li-new-ma-toggle" class="button button-secondary" style="font-size:0.85em;">
                            + Neuen Mitarbeiter anlegen
                        </button>
                    </div>
                    <div id="dp-li-new-ma-form" style="display:none; background:#f6f7f7; border:1px solid #ddd; border-radius:4px; padding:12px 14px; margin-bottom:12px;">
                        <p style="margin:0 0 8px; font-weight:600;">Neuen Mitarbeiter anlegen</p>
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="padding:4px 8px 4px 0; width:110px;"><label for="dp-li-new-ma-vorname">Vorname *</label></td>
                                <td><input type="text" id="dp-li-new-ma-vorname" class="regular-text" style="width:100%;"></td>
                            </tr>
                            <tr>
                                <td style="padding:4px 8px 4px 0;"><label for="dp-li-new-ma-nachname">Nachname *</label></td>
                                <td><input type="text" id="dp-li-new-ma-nachname" class="regular-text" style="width:100%;"></td>
                            </tr>
                            <tr>
                                <td style="padding:4px 8px 4px 0;"><label for="dp-li-new-ma-email">E-Mail</label></td>
                                <td><input type="email" id="dp-li-new-ma-email" class="regular-text" style="width:100%;"></td>
                            </tr>
                            <tr>
                                <td style="padding:4px 8px 4px 0;"><label for="dp-li-new-ma-telefon">Telefon</label></td>
                                <td><input type="text" id="dp-li-new-ma-telefon" class="regular-text" style="width:100%;"></td>
                            </tr>
                        </table>
                        <p style="margin:10px 0 0;">
                            <button type="button" id="dp-li-new-ma-save" class="button button-primary">Anlegen &amp; auswählen</button>
                            <button type="button" id="dp-li-new-ma-cancel" class="button" style="margin-left:6px;">Abbrechen</button>
                            <span id="dp-li-new-ma-msg" style="margin-left:10px; font-weight:600;"></span>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="dp-form-group">
                    <label><?php echo $can_manage_dienste ? 'Ausgewählter Mitarbeiter' : 'Angemeldet als'; ?></label>
                    <div class="dp-loggedin-meta">
                        <div><strong>Vorname:</strong> <span id="dp-li-vorname">-</span></div>
                        <div><strong>Nachname:</strong> <span id="dp-li-nachname">-</span></div>
                        <div><strong>E-Mail:</strong> <span id="dp-li-email">-</span></div>
                    </div>
                </div>

                <div id="dp-li-split-group" class="dp-form-group" style="display:none; background:#f0f9ff; border:1px solid #7dd3fc; border-radius:6px; padding:12px 14px;">
                    <label><strong>&#x2702;&#xFE0F; Welche Hälfte möchten Sie übernehmen?</strong></label>
                    <div class="dp-radio-row" style="margin-top:8px; gap:20px;">
                        <label><input type="radio" name="dp_li_split_wahl" value="first" checked> 1. Hälfte</label>
                        <label><input type="radio" name="dp_li_split_wahl" value="second"> 2. Hälfte</label>
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

    .dp-frontend-container.dp-verein-specific {
        width: 100%;
        max-width: none;
        margin-left: 0;
        margin-right: 0;
        padding: 1.5rem;
        overflow-x: clip;
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    .dp-frontend-container.dp-verein-specific > * {
        max-width: 100%;
        margin-left: auto;
        margin-right: auto;
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
        .dp-frontend-container.dp-verein-specific {
            width: 100%;
            max-width: none;
            margin-left: 0;
            margin-right: 0;
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



    .dp-tl-wrap {

        --dp-tl-label-w: 200px;

        --dp-tl-row-h: 42px;

        border: 1px solid #dbe2ea;

        border-radius: 12px;

        background: #ffffff;

        position: relative;

        overflow-x: auto;

        overflow-y: auto;

        max-height: 80vh;

    }


    .dp-tl-edge-hint {

        position: absolute;

        top: 8px;

        width: 22px;

        height: 22px;

        border-radius: 999px;

        background: rgba(15, 23, 42, 0.72);

        color: #ffffff;

        display: inline-flex;

        align-items: center;

        justify-content: center;

        font-size: 16px;

        font-weight: 700;

        z-index: 35;

        pointer-events: none;

        opacity: 0;

        transform: scale(0.92);

        transition: opacity 0.14s ease, transform 0.14s ease;

    }

    .dp-tl-edge-hint.is-left {
        left: 8px;
    }

    .dp-tl-edge-hint.is-right {
        right: 8px;
    }

    .dp-tl-edge-hint.is-visible {
        opacity: 0.95;
        transform: scale(1);
    }



    .dp-tl-canvas {

        display: flex;

        flex-direction: row;

        align-items: flex-start;

    }



    /* Sub-Header pro Tag-Spalte (ersetzt den gemeinsamen .dp-tl-header) */
    .dp-tl-day-hd {

        display: flex;

        align-items: stretch;

        position: sticky;

        top: 37px;

        z-index: 10;

        background: #f1f5f9;

        border-bottom: 2px solid #c7d2e0;

    }



    .dp-tl-label-col {

        flex: 0 0 var(--dp-tl-label-w);

        width: var(--dp-tl-label-w);

        padding: 0.5rem 0.75rem;

        font-size: 0.75rem;

        font-weight: 700;

        color: #475569;

        border-right: 2px solid #c7d2e0;

        display: flex;

        align-items: center;

        background: #f1f5f9;

    }



    .dp-tl-hours {

        flex: 1;

        position: relative;

        height: 34px;

    }



    .dp-tl-hlabel {

        position: absolute;

        transform: translateX(-50%);

        font-size: 0.72rem;

        font-weight: 700;

        color: #334155;

        top: 50%;

        transform: translateX(-50%) translateY(-50%);

        white-space: nowrap;

        user-select: none;

    }



    .dp-tl-day-block {

        flex: 0 0 var(--dp-tl-day-w, 900px);

        width: var(--dp-tl-day-w, 900px);

        border-right: 2px solid #dbe2ea;

        min-width: 0;

    }



    .dp-tl-day-block:last-child {

        border-right: none;

    }



    .dp-tl-day-title {

        display: flex;

        align-items: center;

        gap: 0.5rem;

        padding: 0.35rem 0.75rem;

        background: linear-gradient(90deg, #1e3a5f 0%, #1d4ed8 100%);

        color: #ffffff;

        font-size: 0.82rem;

        font-weight: 700;

        position: sticky;

        top: 0;

        z-index: 15;

    }



    .dp-tl-dow {

        background: rgba(255,255,255,0.2);

        border-radius: 4px;

        padding: 0.08rem 0.35rem;

        font-size: 0.75rem;

        font-weight: 700;

    }



    .dp-tl-row {

        display: flex;

        align-items: center;

        height: var(--dp-tl-row-h);

        border-bottom: 1px solid #eef2f7;

        width: 100%;

    }



    .dp-tl-row:last-child {

        border-bottom: none;

    }



    .dp-tl-label {

        flex: 0 0 var(--dp-tl-label-w);

        width: var(--dp-tl-label-w);

        padding: 0 0.65rem;

        border-right: 1px solid #dbe2ea;

        height: 100%;

        display: flex;

        flex-direction: column;

        justify-content: center;

        gap: 0.15rem;

        overflow: hidden;

        background: #ffffff;

    }



    .dp-tl-svc-name {

        font-size: 0.8rem;

        font-weight: 700;

        color: #0f172a;

        white-space: nowrap;

        overflow: hidden;

        text-overflow: ellipsis;

    }



    .dp-tl-svc-meta {

        display: flex;

        align-items: center;

        gap: 0.3rem;

        overflow: hidden;

    }

    .dp-tl-assignees {
        font-size: 0.66rem;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }



    .dp-tl-svc-bereich {

        font-size: 0.7rem;

        font-weight: 600;

        white-space: nowrap;

        overflow: hidden;

        text-overflow: ellipsis;

        flex: 1;

        min-width: 0;

    }



    .dp-tl-occ {

        flex-shrink: 0;

        border-radius: 4px;

        font-size: 0.68rem;

        font-weight: 700;

        padding: 0.08rem 0.28rem;

    }



    .dp-tl-occ.is-open {

        background: #fee2e2;

        color: #991b1b;

    }



    .dp-tl-occ.is-full {

        background: #dcfce7;

        color: #166534;

    }



    .dp-tl-track {

        flex: 1;

        position: relative;

        height: 100%;

        min-width: 500px;

        background-image: linear-gradient(to right, transparent calc(100% - 1px), #dbe2ea 100%);

        background-size: var(--dp-tl-h-w) 100%;

        background-position: var(--dp-tl-first-h) 0;

        background-repeat: repeat-x;

        background-color: #fafbfc;

    }



    .dp-tl-row:nth-child(even) .dp-tl-track {

        background-color: #f6f8fb;

    }



    .dp-tl-bar {

        position: absolute;

        top: 6px;

        bottom: 6px;

        border-radius: 7px;

        display: inline-flex;

        align-items: center;

        gap: 0.28rem;

        padding: 0 0.45rem;

        overflow: hidden;

        color: #ffffff;

        box-shadow: 0 2px 7px rgba(0,0,0,0.22);

        min-width: 4px;

        white-space: nowrap;

    }

    .dp-tl-bar.is-split {
        padding: 0;
        gap: 0;
    }

    .dp-tl-bar.is-split::after {
        content: none;
        position: absolute;
        top: 1px;
        bottom: 1px;
        left: 50%;
        width: 2px;
        margin-left: -1px;
        border-radius: 1px;
        background: rgba(255,255,255,0.96);
        box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.22);
        pointer-events: none;
    }



    .dp-tl-bar-time {

        font-size: 0.72rem;

        font-weight: 700;

        opacity: 0.96;

        flex-shrink: 1;
        min-width: 0;
        margin-right: auto;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;

    }

    .dp-tl-split-halves {
        display: grid;
        grid-template-columns: 1fr 1fr;
        column-gap: 0;
        width: 100%;
        height: 100%;
    }

    .dp-tl-split-half {
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.3rem;
        padding: 0 0.4rem;
        overflow: hidden;
        border-radius: 0;
        background: transparent;
        height: 100%;
    }

    .dp-tl-split-action {
        flex-shrink: 0;
        width: 50px;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }

    .dp-tl-split-half:first-child {
        border-right: 2px solid rgba(255,255,255,0.85);
    }

    .dp-tl-split-half .dp-tl-bar-time {
        margin-right: 0;
        margin-left: 0;
        text-align: left;
        max-width: calc(100% - 52px);
        overflow: hidden;
        text-overflow: ellipsis;
        height: 1.2em;
        line-height: 1.2;
        flex-grow: 1;
        min-width: 0;
    }

    .dp-tl-split-half .dp-tl-bar-btn,
    .dp-tl-split-half .dp-tl-locked {
        margin-left: 0;
        margin-right: 0;
        flex-shrink: 0;
        font-size: 0.7rem;
    }



    .dp-tl-day-block {

        display: block;

        border-bottom: 2px solid #dbe2ea;

    }



    .dp-tl-day-block:last-child {

        border-bottom: none;

    }

    .dp-tl-bar-btn {

        border: 0;

        background: rgba(255,255,255,0.18);

        color: #ffffff;

        border-radius: 6px;

        padding: 0.12rem 0.38rem;

        font-size: 0.7rem;

        font-weight: 700;

        cursor: pointer;

        line-height: 1.3;

    }



    .dp-tl-bar-btn:hover {

        background: rgba(255,255,255,0.30);

    }

    .dp-tl-bar-btn--takeover {
        background: #ffffff;
        color: var(--dp-btn-accent, #1d4ed8);
        border: 1px solid #ffffff;
    }

    .dp-tl-bar-btn--takeover:hover {
        background: #f8fafc;
        color: var(--dp-btn-accent, #1d4ed8);
    }



    .dp-tl-label {

        flex: 0 0 var(--dp-tl-label-w);

        width: var(--dp-tl-label-w);

        padding: 0 0.65rem;

        border-right: 1px solid #dbe2ea;

        height: 100%;

        display: flex;

        flex-direction: column;

        justify-content: center;

        gap: 0.15rem;

        overflow: hidden;

        background: #ffffff;

        position: sticky;

        left: 0;

        z-index: 5;

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
        position: sticky;
        top: 0;
        z-index: 50;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
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

    .dp-tl-label-col {

        flex: 0 0 var(--dp-tl-label-w);

        width: var(--dp-tl-label-w);

        padding: 0.5rem 0.75rem;

        font-size: 0.75rem;

        font-weight: 700;

        color: #475569;

        border-right: 2px solid #c7d2e0;

        display: flex;

        align-items: center;

        background: #f1f5f9;

        position: sticky;

        left: 0;

        z-index: 25;

    }



    .dp-tl-hours {

        flex: 1;

        position: relative;

        height: 34px;

        min-width: 500px;

    }

    .dp-dienste-kompakt-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .dp-dienste-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.5rem;
    }

    .dp-day-section-compact {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 0;
        width: 100%;
    }

    .dp-dienste-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95rem;
    }

    .dp-dienste-table thead {
        background: #f0f4f8;
    }

    .dp-dienste-table th {
        padding: 0.75rem 1rem;
        text-align: left;
        font-weight: 600;
        color: #334155;
        border-bottom: 1px solid #cbd5e1;
    }

    .dp-dienste-table tbody tr {
        border-bottom: 1px solid #e8ecf1;
        transition: background-color 0.2s ease;
    }

    .dp-dienste-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .dp-dienste-table td {
        padding: 0.875rem 1rem;
        vertical-align: middle;
    }

    .dp-dienste-table .col-zeit {
        font-weight: 600;
        width: 10%;
        min-width: 90px;
    }

    .dp-dienste-table .col-bereich {
        width: 12%;
        min-width: 110px;
    }

    .dp-dienste-table .col-dienst {
        width: 18%;
        min-width: 140px;
    }

    .dp-dienste-table .col-besonderheiten {
        width: 20%;
        min-width: 150px;
        color: #64748b;
        font-size: 0.9rem;
    }

    .dp-dienste-table .col-zugeordnet {
        width: 18%;
        min-width: 140px;
        color: #64748b;
        font-size: 0.9rem;
    }

    .dp-dienste-table .col-status {
        width: 10%;
        min-width: 80px;
    }

    .dp-dienste-table .col-aktion {
        width: 12%;
        min-width: 100px;
        text-align: center;
    }

    .dp-status-badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 4px;
        font-size: 0.85rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .dp-status-badge.open {
        background: #dcfce7;
        color: #166534;
    }

    .dp-status-badge.full {
        background: #fecaca;
        color: #991b1b;
    }

    .dp-btn-anmelden {
        padding: 0.5rem 1rem;
        background: var(--dp-btn-accent, #3b82f6);
        color: #fff;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 500;
        transition: opacity 0.2s;
        white-space: nowrap;
    }

    .dp-btn-anmelden:hover {
        opacity: 0.85;
    }

    .dp-empty,
    .dp-grey-text {
        color: #94a3b8;
        font-style: italic;
    }

    .dp-bereich-badge {
        display: inline-block;
        padding: 0.35rem 0.65rem;
        border-radius: 4px;
        font-size: 0.85rem;
        font-weight: 500;
        color: #fff;
        white-space: nowrap;
    }

    .dp-assigned-names {
        color: #64748b;
        font-size: 0.9rem;
    }

    .dp-dienst-hint {
        color: #64748b;
        font-size: 0.9rem;
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
        color: #ffffff;
        background: var(--dp-btn-accent, #0ea5e9);
        border: 1px solid rgba(15, 23, 42, 0.15);
        border-radius: 6px;
        padding: 0.25rem 0.55rem;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
    }

    .dp-slot-offen-label:hover {
        filter: brightness(0.9);
    }

    .dp-slot-admin-only {
        display: inline-flex !important;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.65rem;
        border-radius: 6px;
        background: #fef3c7;
        border: 1px solid #fde68a;
        color: #d97706 !important;
        font-weight: 600;
        font-size: 0.82rem;
        cursor: not-allowed;
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

        .dp-tl-wrap {

            --dp-tl-label-w: 160px;

            --dp-tl-row-h: 46px;

        }



        .dp-tl-hours {

            min-width: 300px;

        }



        .dp-tl-track {

            min-width: 300px;

        }



        .dp-timeline-v-day-head {
            min-height: 52px;
            padding: 0.45rem 0.55rem;
        }

        .dp-tv-title {
            font-size: 0.82rem;
        }
    }
</style>

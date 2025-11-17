<?php
/**
 * Frontend Veranstaltungs-Detail View - UPDATED 2025-11-17 08:53
 * Pro Veranstaltung: Erst Vereinsauswahl, dann Dienste mit Liste & Timeline
 * Modernes Design mit Backend-Styling
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/public/templates
 */

if (!defined('ABSPATH')) exit;

require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';

$db = new Dienstplan_Database(DIENSTPLAN_DB_PREFIX);

// Get event ID - check both shortcode attributes and GET parameters
$veranstaltung_id = isset($atts['veranstaltung_id']) && !empty($atts['veranstaltung_id']) 
    ? intval($atts['veranstaltung_id']) 
    : (isset($_GET['veranstaltung_id']) ? intval($_GET['veranstaltung_id']) : null);

if (!$veranstaltung_id) {
    echo '<p style="padding: 2rem; text-align: center; color: #ef4444;">Keine Veranstaltung ausgewählt.</p>';
    return;
}

// Ensure veranstaltung_id is in URL for all links
if (!isset($_GET['veranstaltung_id'])) {
    $_GET['veranstaltung_id'] = $veranstaltung_id;
}

$veranstaltung = $db->get_veranstaltung($veranstaltung_id);
if (!$veranstaltung) {
    echo '<p style="padding: 2rem; text-align: center; color: #ef4444;">Veranstaltung nicht gefunden.</p>';
    return;
}

// Get parameters
$verein_id = isset($_GET['verein_id']) ? intval($_GET['verein_id']) : null;

// Check cookie if no GET parameter
if (!$verein_id && isset($_COOKIE['dp_selected_verein_' . $veranstaltung_id])) {
    $verein_id = intval($_COOKIE['dp_selected_verein_' . $veranstaltung_id]);
}

$view_mode = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';

// Get all vereins and filter for this event
$vereine = $db->get_vereine();

// Get all services to determine which vereins are actually used
$all_services = $db->get_dienste($veranstaltung_id);

// Get unique verein IDs from services
$verein_ids_in_use = array_unique(array_map(function($s) {
    return intval($s->verein_id);
}, $all_services));

// Filter vereins to only those that have services in this event
$available_vereine = array_filter($vereine, function($v) use ($verein_ids_in_use) {
    return in_array(intval($v->id), $verein_ids_in_use);
});

// Get event days
$tage = $db->get_veranstaltung_tage($veranstaltung_id);

// Filter services by selected verein if applicable
if ($verein_id) {
    $services = array_filter($all_services, function($s) use ($verein_id) {
        return intval($s->verein_id) === intval($verein_id);
    });
} else {
    $services = $all_services;
}

// Group services by day
$dienste_nach_tagen = [];
foreach ($services as $service) {
    $tag_id = $service->tag_id ?? 0;
    if (!isset($dienste_nach_tagen[$tag_id])) {
        $dienste_nach_tagen[$tag_id] = [];
    }
    $dienste_nach_tagen[$tag_id][] = $service;
}

// Sort by day
ksort($dienste_nach_tagen);
?>

<div class="dp-frontend-container">
    <!-- Vereinsauswahl Modal (zeige nur wenn mehr als 1 Verein und kein Verein gewählt) -->
    <?php if (count($available_vereine) > 1 && !$verein_id): ?>
        <div id="dp-verein-modal" class="dp-verein-modal">
            <div class="dp-verein-modal-content">
                <div class="dp-verein-modal-header">
                    <h2>Für welchen Verein möchten Sie sich anmelden?</h2>
                    <p>Ihre Auswahl wird gespeichert für Ihren nächsten Besuch.</p>
                </div>
                <div class="dp-verein-modal-body">
                    <div class="dp-vereine-grid-modal">
                        <?php foreach ($available_vereine as $verein): 
                            $logo_url = !empty($verein->logo_id) ? wp_get_attachment_url($verein->logo_id) : null;
                        ?>
                            <button onclick="dpSelectVerein(<?php echo $verein->id; ?>, <?php echo $veranstaltung_id; ?>)" class="dp-verein-card-modal">
                                <div class="dp-verein-card-header">
                                    <?php if ($logo_url): ?>
                                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($verein->name); ?>" class="dp-verein-logo">
                                    <?php else: ?>
                                        <div class="dp-verein-icon">🏢</div>
                                    <?php endif; ?>
                                    <h3 class="dp-verein-name"><?php echo esc_html($verein->name); ?></h3>
                                </div>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Wenn nur 1 Verein: Auto-Select -->
    <?php 
    if (count($available_vereine) === 1 && !$verein_id) {
        $verein_id = reset($available_vereine)->id;
        // Update services filter
        $services = array_filter($all_services, function($s) use ($verein_id) {
            return intval($s->verein_id) === intval($verein_id);
        });
        // Regroup by day
        $dienste_nach_tagen = [];
        foreach ($services as $service) {
            $tag_id = $service->tag_id ?? 0;
            if (!isset($dienste_nach_tagen[$tag_id])) {
                $dienste_nach_tagen[$tag_id] = [];
            }
            $dienste_nach_tagen[$tag_id][] = $service;
        }
        ksort($dienste_nach_tagen);
    }
    
    // Get selected verein object
    $selected_verein = null;
    if ($verein_id) {
        foreach ($available_vereine as $v) {
            if (intval($v->id) === intval($verein_id)) {
                $selected_verein = $v;
                break;
            }
        }
    }
    ?>

    <!-- STAGE 1: Vereinsauswahl (wenn nicht ausgewählt) -->
    <?php if (!$verein_id): ?>
        <!-- Kompakter Header nur mit Veranstaltung -->
        <div class="dp-compact-header">
            <h1 class="dp-compact-title"><?php echo esc_html($veranstaltung->name); ?></h1>
        </div>
        
        <div class="dp-verein-selection-section">
            <h2 class="dp-section-title">Für welchen Verein möchten Sie sich anmelden?</h2>
            
            <?php if (empty($available_vereine)): ?>
                <div class="dp-empty-state">
                    <span class="dp-empty-icon">📭</span>
                    <h3>Keine Vereine verfügbar</h3>
                    <p>Diese Veranstaltung hat keine Vereine zugewiesen.</p>
                </div>
            <?php else: ?>
                <div class="dp-vereine-grid">
                    <?php foreach ($available_vereine as $verein): 
                        $logo_url = !empty($verein->logo_id) ? wp_get_attachment_url($verein->logo_id) : null;
                    ?>
                        <a href="<?php echo esc_url(add_query_arg(array('veranstaltung_id' => $veranstaltung_id, 'verein_id' => $verein->id))); ?>" class="dp-verein-card">
                            <div class="dp-verein-card-header">
                                <?php if ($logo_url): ?>
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($verein->name); ?>" class="dp-verein-logo">
                                <?php else: ?>
                                    <div class="dp-verein-icon">🏢</div>
                                <?php endif; ?>
                                <h3 class="dp-verein-name"><?php echo esc_html($verein->name); ?></h3>
                            </div>
                            <div class="dp-verein-arrow">→</div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <!-- STAGE 2: Dienste anzeigen (wenn Verein ausgewählt) -->
    <?php else: ?>
        <?php 
        $selected_verein = null;
        foreach ($vereine as $v) {
            if ($v->id == $verein_id) {
                $selected_verein = $v;
                break;
            }
        }
        ?>
        
        <!-- Kompakter Header mit Veranstaltung und Verein -->
        <div class="dp-compact-header">
            <div class="dp-compact-header-left">
                <h1 class="dp-compact-title"><?php echo esc_html($veranstaltung->name); ?></h1>
                <?php if ($selected_verein): 
                    $logo_url = !empty($selected_verein->logo_id) ? wp_get_attachment_url($selected_verein->logo_id) : null;
                ?>
                    <span class="dp-compact-verein">
                        <?php if ($logo_url): ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($selected_verein->name); ?>" class="dp-verein-logo-small">
                        <?php else: ?>
                            <span class="dp-verein-icon">🏢</span>
                        <?php endif; ?>
                        <?php echo esc_html($selected_verein->name); ?>
                        <?php if (!empty($selected_verein->kuerzel)): ?>
                            <span class="dp-verein-kuerzel">(<?php echo esc_html($selected_verein->kuerzel); ?>)</span>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="dp-compact-header-right">
                <!-- View Switcher Icons -->
                <a href="<?php echo esc_url(add_query_arg(array('veranstaltung_id' => $veranstaltung_id, 'verein_id' => $verein_id, 'view' => 'list'))); ?>" class="dp-view-icon-btn <?php echo ($view_mode === 'list' || $view_mode === '') ? 'active' : ''; ?>" title="Liste">
                    📋
                </a>
                <a href="<?php echo esc_url(add_query_arg(array('veranstaltung_id' => $veranstaltung_id, 'verein_id' => $verein_id, 'view' => 'timeline'))); ?>" class="dp-view-icon-btn <?php echo $view_mode === 'timeline' ? 'active' : ''; ?>" title="Timeline">
                    📊
                </a>
                <button class="dp-compact-change-btn" onclick="dpShowVereinModal()">
                    Verein wechseln
                </button>
            </div>
        </div>

        <!-- Vereinsauswahl Modal (für Wechsel) -->
        <div id="dp-verein-change-modal" class="dp-verein-modal" style="display: none;">
            <div class="dp-verein-modal-content">
                <div class="dp-verein-modal-header">
                    <h2>Verein wechseln</h2>
                    <p>Wählen Sie einen anderen Verein aus.</p>
                </div>
                <div class="dp-verein-modal-body">
                    <div class="dp-vereine-grid-modal">
                        <?php foreach ($available_vereine as $verein): 
                            $logo_url = !empty($verein->logo_id) ? wp_get_attachment_url($verein->logo_id) : null;
                        ?>
                            <button onclick="dpSelectVerein(<?php echo $verein->id; ?>, <?php echo $veranstaltung_id; ?>)" class="dp-verein-card-modal">
                                <?php if ($logo_url): ?>
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($verein->name); ?>" class="dp-verein-logo" style="margin-bottom: 1rem;">
                                <?php endif; ?>
                                <h3 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; color: var(--dp-gray-900);"><?php echo esc_html($verein->name); ?></h3>
                                <?php if (!empty($verein->kuerzel)): ?>
                                    <span style="color: var(--dp-primary); font-weight: 600;"><?php echo esc_html($verein->kuerzel); ?></span>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bereich Filter -->
        <?php
        // Sammle alle Bereiche aus den Diensten
        $bereiche = array();
        foreach ($services as $service) {
            if (!empty($service->bereich_name) && !empty($service->bereich_id)) {
                $bereiche[$service->bereich_id] = array(
                    'name' => $service->bereich_name,
                    'farbe' => $service->bereich_farbe ?? '#667eea'
                );
            }
        }
        ?>
        <?php if (!empty($bereiche)): ?>
            <div class="dp-bereich-filter">
                <button class="dp-filter-btn active" onclick="dpFilterBereich(null, this)">
                    Alle Bereiche
                </button>
                <?php foreach ($bereiche as $bereich_id => $bereich): ?>
                    <button class="dp-filter-btn" onclick="dpFilterBereich(<?php echo $bereich_id; ?>, this)" data-bereich-color="<?php echo esc_attr($bereich['farbe']); ?>">
                        <span class="dp-filter-color" style="background-color: <?php echo esc_attr($bereich['farbe']); ?>;"></span>
                        <?php echo esc_html($bereich['name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Liste-Ansicht -->
        <?php if ($view_mode === 'list' || $view_mode === ''): ?>
            <div class="dp-list-view">
                <?php if (empty($dienste_nach_tagen)): ?>
                    <div class="dp-empty-state">
                        <span class="dp-empty-icon">📭</span>
                        <h3>Keine Dienste verfügbar</h3>
                        <p>Es gibt keine Dienste für diesen Verein in dieser Veranstaltung.</p>
                    </div>
                <?php else: ?>
                    <?php 
                    $is_first = true;
                    foreach ($dienste_nach_tagen as $tag_id => $tag_services):
                        $tag = $db->get_veranstaltung_tag($tag_id);
                        if (!$tag) continue;
                    ?>
                        <div class="dp-accordion-item">
                            <button type="button" class="dp-accordion-header" onclick="dpToggleAccordion(this)">
                                <span class="dp-accordion-date">
                                    <strong><?php echo date_i18n('l', strtotime($tag->tag_datum)); ?></strong>
                                    <small><?php echo date_i18n('d.m.Y', strtotime($tag->tag_datum)); ?></small>
                                </span>
                                <span class="dp-accordion-count"><?php echo count($tag_services); ?> Dienste</span>
                                <span class="dp-accordion-icon">▼</span>
                            </button>
                            <div class="dp-accordion-content" style="<?php echo $is_first ? '' : 'display: none;'; ?>">
                                <div class="dp-services-list">
                                    <?php foreach ($tag_services as $service): ?>
                                        <?php
                                        $slots = $db->get_dienst_slots($service->id);
                                        $free_slots = 0;
                                        foreach ($slots as $slot) {
                                            if (!$slot->mitarbeiter_id) {
                                                $free_slots++;
                                            }
                                        }
                                        $total_slots = count($slots);
                                        ?>
                                        <div class="dp-service-item" data-bereich-id="<?php echo !empty($service->bereich_id) ? intval($service->bereich_id) : '0'; ?>">
                                            <div class="dp-service-info">
                                                <span class="dp-service-time">⏱️ <?php echo date('H:i', strtotime($service->von_zeit)); ?> - <?php echo date('H:i', strtotime($service->bis_zeit)); ?></span>
                                                <?php if (!empty($service->bereich_name)): ?>
                                                    <span class="dp-service-bereich" style="background-color: <?php echo esc_attr($service->bereich_farbe ?? '#667eea'); ?>;">
                                                        <?php echo esc_html($service->bereich_name); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <h4 class="dp-service-name"><?php echo esc_html($service->taetigkeit_name); ?></h4>
                                                <?php if (!empty($service->besonderheiten)): ?>
                                                    <span class="dp-service-note">ℹ️ <?php echo esc_html($service->besonderheiten); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="dp-service-actions">
                                                <?php if ($free_slots > 0): ?>
                                                    <span class="dp-badge dp-badge-available"><?php echo $free_slots; ?>/<?php echo $total_slots; ?></span>
                                                    <button type="button" class="dp-btn dp-btn-primary dp-btn-compact" onclick="dpOpenRegistrationModal(<?php echo $service->id; ?>, '<?php echo esc_attr($service->taetigkeit_name); ?>')">
                                                        Anmelden
                                                    </button>
                                                <?php else: ?>
                                                    <span class="dp-badge dp-badge-full">Voll</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php $is_first = false; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Timeline-Ansicht -->
        <?php if ($view_mode === 'timeline'): ?>
            <div class="dp-timeline-view">
                <?php if (empty($tage)): ?>
                    <div class="dp-empty-state">
                        <span class="dp-empty-icon">📭</span>
                        <h3>Keine Tage verfügbar</h3>
                    </div>
                <?php else: ?>
                    <!-- Tag-Tabs -->
                    <div class="dp-timeline-tabs">
                        <?php foreach ($tage as $idx => $tag): ?>
                            <button type="button" class="dp-timeline-tab <?php echo $idx === 0 ? 'active' : ''; ?>" onclick="dpSwitchTimelineDay(this, <?php echo $tag->id; ?>)">
                                Tag <?php echo $tag->tag_nummer ?? ($idx + 1); ?>
                                <br><small><?php echo date_i18n('d.m.', strtotime($tag->tag_datum)); ?></small>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Timeline pro Tag -->
                    <?php 
                    $start_hour = 0;
                    $end_hour = 24;
                    $slot_duration = 30; // 30 Minuten pro Slot
                    $total_slots = ($end_hour - $start_hour) * 2; // 48 Slots für 24h
                    
                    foreach ($tage as $idx => $tag):
                        $tag_services = $dienste_nach_tagen[$tag->id] ?? [];
                        
                        // Gruppiere Services nach Bereich UND Zeit-Slot
                        // Damit Services die gleichzeitig laufen in derselben Zeile erscheinen
                        $services_by_bereich = [];
                        $time_slots = []; // Track unique time slots per Bereich
                        
                        foreach ($tag_services as $service) {
                            $bereich_key = $service->bereich_id ?? 0;
                            if (!isset($services_by_bereich[$bereich_key])) {
                                $services_by_bereich[$bereich_key] = [
                                    'name' => $service->bereich_name ?? 'Ohne Bereich',
                                    'farbe' => $service->bereich_farbe ?? '#667eea',
                                    'time_slots' => []
                                ];
                            }
                            
                            // Erstelle einen eindeutigen Zeitslot-Key (Start-Zeit)
                            $time_key = date('H:i', strtotime($service->von_zeit));
                            
                            if (!isset($services_by_bereich[$bereich_key]['time_slots'][$time_key])) {
                                $services_by_bereich[$bereich_key]['time_slots'][$time_key] = [];
                            }
                            
                            $services_by_bereich[$bereich_key]['time_slots'][$time_key][] = $service;
                        }
                        
                        // Sortiere Zeitslots innerhalb jedes Bereichs
                        foreach ($services_by_bereich as &$bereich) {
                            ksort($bereich['time_slots']);
                        }
                    ?>
                        <div class="dp-timeline-day-container" data-day-id="<?php echo $tag->id; ?>" style="display: <?php echo $idx === 0 ? 'block' : 'none'; ?>;">
                            <div class="dp-timeline-wrapper">
                                <!-- Linke Spalte: Dienst-Namen (fixiert) -->
                                <div class="dp-timeline-left">
                                    <!-- Header (Leer für Ausrichtung) -->
                                    <div class="dp-timeline-left-header">Dienste</div>
                                    
                                    <!-- Dienst-Zeilen -->
                                    <div class="dp-timeline-rows">
                                        <?php foreach ($services_by_bereich as $bereich): ?>
                                            <!-- Bereich Header -->
                                            <div class="dp-timeline-row-group-header" style="background-color: <?php echo esc_attr($bereich['farbe']); ?>;">
                                                <?php echo esc_html($bereich['name']); ?>
                                            </div>
                                            
                                            <?php foreach ($bereich['time_slots'] as $time_key => $services_in_slot): ?>
                                                <div class="dp-timeline-row-label" data-time-slot="<?php echo $time_key; ?>">
                                                    <div class="dp-timeline-service-title">
                                                        <?php 
                                                        // Zeige alle Services in diesem Slot
                                                        $names = array_map(function($s) { return $s->taetigkeit_name; }, $services_in_slot);
                                                        echo esc_html($time_key);
                                                        ?>
                                                    </div>
                                                    <div class="dp-timeline-service-time-label">
                                                        <?php echo count($services_in_slot); ?> Dienst<?php echo count($services_in_slot) > 1 ? 'e' : ''; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Rechte Spalte: Zeitraster (scrollbar) -->
                                <div class="dp-timeline-right">
                                    <!-- Zeit-Header (fixiert, scrollt horizontal) -->
                                    <div class="dp-timeline-header-scroll">
                                        <div class="dp-timeline-time-header">
                                            <?php for ($hour = $start_hour; $hour < $end_hour; $hour++): ?>
                                                <div class="dp-timeline-hour-header" style="grid-column: <?php echo (($hour - $start_hour) * 2 + 1); ?> / span 2;">
                                                    <?php printf("%02d:00", $hour); ?>
                                                </div>
                                                <div class="dp-timeline-half-marker" style="grid-column: <?php echo (($hour - $start_hour) * 2 + 2); ?>;"></div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Timeline Grid (scrollbar vertikal und horizontal) -->
                                    <div class="dp-timeline-grid-scroll">
                                        <?php 
                                        // Zähle total Rows (Bereich-Header + Time-Slots)
                                        $total_rows = 0;
                                        foreach ($services_by_bereich as $bereich) {
                                            $total_rows++; // Header
                                            $total_rows += count($bereich['time_slots']); // Time slots
                                        }
                                        ?>
                                        <div class="dp-timeline-grid" style="grid-template-columns: repeat(<?php echo $total_slots; ?>, 60px); grid-template-rows: repeat(<?php echo $total_rows; ?>, 50px);">
                                            <!-- Vertikale Zeit-Linien als Overlays -->
                                            <div class="dp-timeline-grid-lines">
                                                <?php for ($i = 0; $i <= $total_slots; $i++): ?>
                                                    <div class="dp-timeline-grid-line <?php echo $i % 2 === 0 ? 'hour' : 'half'; ?>" style="left: <?php echo $i * 60; ?>px;"></div>
                                                <?php endfor; ?>
                                            </div>
                                            
                                            <!-- Service Bars (Vordergrund) - Mehrere nebeneinander -->
                                            <?php 
                                            $row = 1;
                                            foreach ($services_by_bereich as $bereich_id => $bereich): 
                                                // Bereich-Header-Zeile überspringen
                                                $row++; 
                                                
                                                foreach ($bereich['time_slots'] as $time_key => $services_in_slot):
                                                    // Alle Services in diesem Slot nebeneinander anzeigen
                                                    $service_index = 0;
                                                    foreach ($services_in_slot as $service):
                                                        // Berechne Zeit-Position
                                                        $start_time = strtotime($service->von_zeit);
                                                        $end_time = strtotime($service->bis_zeit);
                                                        $start_minutes = intval(date('H', $start_time)) * 60 + intval(date('i', $start_time));
                                                        $end_minutes = intval(date('H', $end_time)) * 60 + intval(date('i', $end_time));
                                                        
                                                        $start_col = floor($start_minutes / 30) + 1;
                                                        $duration_slots = max(1, ceil(($end_minutes - $start_minutes) / 30));
                                                        
                                                        // Slots zählen
                                                        $slots = $db->get_dienst_slots($service->id);
                                                        $free_slots = 0;
                                                        foreach ($slots as $slot) {
                                                            if (!$slot->mitarbeiter_id) {
                                                                $free_slots++;
                                                            }
                                                        }
                                                        $total_slots_count = count($slots);
                                                ?>
                                                        <div class="dp-timeline-bar" 
                                                             style="grid-column: <?php echo $start_col; ?> / span <?php echo $duration_slots; ?>; 
                                                                    grid-row: <?php echo $row; ?>; 
                                                                    background: linear-gradient(135deg, <?php echo esc_attr($bereich['farbe']); ?> 0%, <?php echo esc_attr($bereich['farbe']); ?>dd 100%);"
                                                             onclick="dpOpenRegistrationModal(<?php echo $service->id; ?>, '<?php echo esc_js($service->taetigkeit_name); ?>')">
                                                            <div class="dp-timeline-bar-content">
                                                                <span class="dp-timeline-bar-name"><?php echo esc_html($service->taetigkeit_name); ?></span>
                                                                <?php if ($free_slots > 0): ?>
                                                                    <span class="dp-timeline-bar-slots">🟢 <?php echo $free_slots; ?>/<?php echo $total_slots_count; ?></span>
                                                                <?php else: ?>
                                                                    <span class="dp-timeline-bar-slots">🔴 Voll</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                <?php 
                                                        $service_index++;
                                                    endforeach;
                                                    $row++; // Nächste Zeile für nächsten Zeitslot
                                                endforeach;
                                            endforeach;
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Registrierungs-Modal -->
        <div id="dpRegistrationModal" class="dp-modal" style="display: none;">
            <div class="dp-modal-overlay" onclick="dpCloseRegistrationModal()"></div>
            <div class="dp-modal-content">
                <div class="dp-modal-header">
                    <h2>Für Dienst anmelden</h2>
                    <button type="button" class="dp-modal-close" onclick="dpCloseRegistrationModal()">✕</button>
                </div>
                <div class="dp-modal-body">
                    <form id="dpRegistrationForm" method="POST">
                        <input type="hidden" name="action" value="dp_register_service">
                        <input type="hidden" name="dienst_id" id="dpDienstId" value="">
                        
                        <div class="dp-form-group">
                            <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                                <input type="checkbox" id="dpSplitDienst" name="split_dienst" onchange="dpToggleSplitOptions(this)" style="width: auto; margin: 0;">
                                <span>Ich möchte den Dienst teilen</span>
                            </label>
                            
                            <div id="dpSplitOptions" style="display: none; margin-left: 1.5rem; padding-left: 1rem; border-left: 3px solid var(--dp-primary);">
                                <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                        <input type="radio" name="dienst_teil" value="1" style="width: auto; margin: 0;">
                                        <span>1. Teil</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                        <input type="radio" name="dienst_teil" value="2" style="width: auto; margin: 0;">
                                        <span>2. Teil</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="dp-form-group">
                            <label for="dpFirstName">Vorname *</label>
                            <input type="text" id="dpFirstName" name="first_name" required>
                        </div>
                        
                        <div class="dp-form-group">
                            <label for="dpLastName">Nachname *</label>
                            <input type="text" id="dpLastName" name="last_name" required>
                        </div>
                        
                        <div class="dp-form-group">
                            <label for="dpEmail">E-Mail (optional)</label>
                            <input type="email" id="dpEmail" name="email">
                        </div>
                        
                        <div class="dp-form-group">
                            <label for="dpPhone">Telefon (optional)</label>
                            <input type="tel" id="dpPhone" name="phone">
                        </div>
                        
                        <div class="dp-modal-footer">
                            <button type="button" class="dp-btn dp-btn-secondary" onclick="dpCloseRegistrationModal()">Abbrechen</button>
                            <button type="submit" class="dp-btn dp-btn-primary">Anmelden</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
:root {
    --dp-primary: #667eea;
    --dp-primary-dark: #5568d3;
    --dp-gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --dp-success: #10b981;
    --dp-danger: #ef4444;
    --dp-bg: #f8fafc;
    --dp-surface: #ffffff;
    --dp-gray-100: #f3f4f6;
    --dp-gray-200: #e5e7eb;
    --dp-gray-500: #6b7280;
    --dp-gray-700: #374151;
    --dp-gray-900: #111827;
    --dp-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --dp-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.dp-frontend-container {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    width: 100%;
    padding: 2rem 2rem;
    background: var(--dp-bg);
}

.dp-compact-header {
    background: white;
    border-radius: 0.75rem;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--dp-shadow);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
}

.dp-compact-header-left {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.dp-compact-header-right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dp-view-icon-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 2px solid var(--dp-gray-200);
    border-radius: 0.5rem;
    font-size: 1.25rem;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
}

.dp-view-icon-btn:hover {
    border-color: var(--dp-primary);
    transform: translateY(-1px);
}

.dp-view-icon-btn.active {
    background: var(--dp-gradient-primary);
    border-color: transparent;
}

.dp-compact-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dp-gray-900);
}

.dp-compact-verein {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--dp-gray-100);
    border-radius: 0.5rem;
    font-weight: 600;
    color: var(--dp-gray-700);
}

.dp-verein-kuerzel {
    color: var(--dp-primary);
    font-weight: 700;
}

.dp-compact-change-btn {
    padding: 0.5rem 1rem;
    background: var(--dp-gray-200);
    color: var(--dp-gray-700);
    border-radius: 0.5rem;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    white-space: nowrap;
    transition: all 0.2s;
}

.dp-compact-change-btn:hover {
    background: var(--dp-gray-300);
    color: var(--dp-gray-900);
}

/* Old header styles - deprecated */

.dp-view-switcher {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.dp-view-btn {
    flex: 1;
    padding: 0.5rem 0.75rem;
    background: white;
    color: var(--dp-gray-900);
    border: 2px solid var(--dp-gray-200);
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
}

.dp-view-btn:hover {
    border-color: var(--dp-primary);
    color: var(--dp-primary);
}

.dp-view-btn.active {
    background: var(--dp-gradient-primary);
    color: white;
    border-color: transparent;
}

.dp-view-icon {
    font-size: 1rem;
}

/* Bereich-Filter */
.dp-bereich-filter {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    padding: 1rem;
    background: white;
    border-radius: 0.75rem;
    box-shadow: var(--dp-shadow);
}

.dp-filter-btn {
    padding: 0.5rem 1rem;
    background: white;
    border: 2px solid var(--dp-gray-200);
    border-radius: 0.5rem;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--dp-gray-700);
}

.dp-filter-btn:hover {
    border-color: var(--dp-gray-400);
    background: var(--dp-gray-50);
}

.dp-filter-btn.active {
    background: var(--dp-primary);
    color: white;
    border-color: var(--dp-primary);
}

.dp-filter-color {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    display: inline-block;
    border: 2px solid rgba(255,255,255,0.3);
}

.dp-filter-btn.active .dp-filter-color {
    border-color: rgba(255,255,255,0.5);
}

/* Vereinsauswahl */
.dp-verein-selection-section {
    margin-bottom: 2rem;
}

.dp-section-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 1.5rem 0;
    color: var(--dp-gray-900);
}

.dp-vereine-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
}

.dp-verein-card {
    background: white;
    border: 2px solid var(--dp-gray-200);
    border-radius: 1rem;
    padding: 1.5rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.3s;
    cursor: pointer;
}

.dp-verein-card:hover {
    border-color: var(--dp-primary);
    box-shadow: var(--dp-shadow-lg);
    transform: translateY(-4px);
}

.dp-verein-card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.dp-verein-icon {
    font-size: 1.5rem;
}

.dp-verein-logo {
    width: 50px;
    height: 50px;
    object-fit: contain;
    border-radius: 0.5rem;
}

.dp-verein-logo-small {
    width: 30px;
    height: 30px;
    object-fit: contain;
    border-radius: 0.25rem;
}

.dp-verein-name {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--dp-gray-900);
}

.dp-verein-arrow {
    color: var(--dp-primary);
    font-weight: bold;
    transition: transform 0.3s;
}

.dp-verein-card:hover .dp-verein-arrow {
    transform: translateX(4px);
}

/* Old back-section styles - deprecated */

/* Vereinsauswahl Modal */
.dp-verein-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    animation: dpFadeIn 0.3s ease-out;
}

@keyframes dpFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.dp-verein-modal-content {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    animation: dpSlideUp 0.3s ease-out;
}

@keyframes dpSlideUp {
    from { 
        opacity: 0;
        transform: translateY(20px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

.dp-verein-modal-header {
    background: var(--dp-gradient-primary);
    color: white;
    padding: 2rem;
    text-align: center;
    border-radius: 1rem 1rem 0 0;
}

.dp-verein-modal-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    font-weight: 700;
}

.dp-verein-modal-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.95rem;
}

.dp-verein-modal-body {
    padding: 2rem;
}

.dp-vereine-grid-modal {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
}

.dp-verein-card-modal {
    background: white;
    border: 2px solid var(--dp-gray-200);
    border-radius: 0.75rem;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.3s;
    text-align: left;
    width: 100%;
}

.dp-verein-card-modal:hover {
    border-color: var(--dp-primary);
    box-shadow: var(--dp-shadow-lg);
    transform: translateY(-4px);
    background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
}

/* Accordion */
.dp-list-view {
    margin: 2rem 0;
}

.dp-accordion-item {
    background: white;
    border: 1px solid var(--dp-gray-200);
    border-radius: 0.75rem;
    margin-bottom: 1rem;
    overflow: hidden;
    box-shadow: var(--dp-shadow);
}

.dp-accordion-header {
    width: 100%;
    padding: 1.25rem;
    background: var(--dp-gray-100);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 600;
    transition: all 0.3s;
}

.dp-accordion-header:hover {
    background: var(--dp-gray-200);
}

.dp-accordion-date {
    display: flex;
    align-items: baseline;
    gap: 1rem;
    text-align: left;
}

.dp-accordion-date strong {
    color: var(--dp-gray-900);
    font-size: 1.5rem;
    font-weight: 700;
}

.dp-accordion-date small {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dp-gray-700);
}

.dp-accordion-count {
    flex: 1;
    text-align: right;
    color: var(--dp-gray-700);
}

.dp-accordion-icon {
    margin-left: 1rem;
    transition: transform 0.3s;
}

.dp-accordion-item.open .dp-accordion-icon {
    transform: rotate(180deg);
}

.dp-accordion-content {
    padding: 1.5rem;
    border-top: 1px solid var(--dp-gray-200);
}

.dp-services-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.dp-service-item {
    padding: 1rem 1.5rem;
    background: var(--dp-gray-50);
    border-radius: 0.75rem;
    border-left: 4px solid var(--dp-primary);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
}

.dp-service-info {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.dp-service-time {
    margin: 0;
    color: var(--dp-gray-700);
    font-size: 0.95rem;
    font-weight: 600;
    white-space: nowrap;
    min-width: 140px;
}

.dp-service-bereich {
    padding: 0.25rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: white;
    white-space: nowrap;
}

.dp-service-name {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--dp-gray-900);
    min-width: 150px;
}

.dp-service-note {
    margin: 0;
    color: var(--dp-gray-600);
    font-size: 0.85rem;
    font-style: italic;
    flex: 1;
}

.dp-service-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-shrink: 0;
}

.dp-btn-compact {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.dp-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.8rem;
    white-space: nowrap;
}

.dp-badge-available {
    background: #d1fae5;
    color: #065f46;
}

.dp-badge-full {
    background: #fee2e2;
    color: #991b1b;
}

/* Timeline - Neue Backend-ähnliche Struktur */
.dp-timeline-view {
    margin: 2rem 0;
}

.dp-timeline-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
}

.dp-timeline-tab {
    padding: 0.75rem 1.25rem;
    background: white;
    border: 2px solid var(--dp-gray-200);
    border-radius: 0.75rem;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    white-space: nowrap;
    font-size: 0.9rem;
}

.dp-timeline-tab:hover {
    border-color: var(--dp-primary);
}

.dp-timeline-tab.active {
    background: var(--dp-gradient-primary);
    color: white;
    border-color: transparent;
}

.dp-timeline-tab small {
    display: block;
    font-size: 0.75rem;
    font-weight: normal;
}

/* Timeline Wrapper - Zwei Spalten Layout */
.dp-timeline-day-container {
    background: white;
    border-radius: 0.75rem;
    box-shadow: var(--dp-shadow);
    overflow: hidden;
}

.dp-timeline-wrapper {
    display: grid;
    grid-template-columns: 250px 1fr;
    height: 600px;
}

/* Linke Spalte - Dienst-Namen (fixiert) */
.dp-timeline-left {
    border-right: 2px solid var(--dp-gray-200);
    display: flex;
    flex-direction: column;
    background: var(--dp-gray-50);
}

.dp-timeline-left-header {
    height: 50px;
    padding: 0 1rem;
    display: flex;
    align-items: center;
    font-weight: 700;
    font-size: 1rem;
    color: var(--dp-gray-900);
    border-bottom: 2px solid var(--dp-gray-200);
    background: white;
    position: sticky;
    top: 0;
    z-index: 3;
}

.dp-timeline-rows {
    overflow-y: auto;
    flex: 1;
}

.dp-timeline-row-group-header {
    padding: 0.75rem 1rem;
    font-weight: 700;
    color: white;
    font-size: 0.9rem;
    position: sticky;
    top: 0;
    z-index: 2;
}

.dp-timeline-row-label {
    height: 50px;
    padding: 0.5rem 1rem;
    border-bottom: 1px solid var(--dp-gray-200);
    display: flex;
    flex-direction: column;
    justify-content: center;
    background: white;
}

.dp-timeline-service-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--dp-gray-900);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dp-timeline-service-time-label {
    font-size: 0.75rem;
    color: var(--dp-gray-600);
}

/* Rechte Spalte - Zeitraster */
.dp-timeline-right {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.dp-timeline-header-scroll {
    height: 50px;
    overflow-x: auto;
    overflow-y: hidden;
    border-bottom: 2px solid var(--dp-gray-200);
    background: white;
    position: sticky;
    top: 0;
    z-index: 3;
}

.dp-timeline-time-header {
    display: grid;
    grid-template-columns: repeat(48, 60px);
    height: 100%;
    min-width: fit-content;
}

.dp-timeline-hour-header {
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--dp-gray-700);
    border-left: 2px solid var(--dp-gray-300);
}

.dp-timeline-half-marker {
    border-left: 1px solid var(--dp-gray-200);
}

.dp-timeline-grid-scroll {
    flex: 1;
    overflow: auto;
    position: relative;
}

.dp-timeline-grid {
    display: grid;
    position: relative;
    min-width: 100%;
    background: white;
}

.dp-timeline-grid-lines {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    z-index: 0;
}

.dp-timeline-grid-line {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 0;
    pointer-events: none;
}

.dp-timeline-grid-line.hour {
    border-left: 2px solid var(--dp-gray-300);
}

.dp-timeline-grid-line.half {
    border-left: 1px solid var(--dp-gray-200);
}

/* Timeline Bars - Service Blöcke */
.dp-timeline-bar {
    border-radius: 0.5rem;
    padding: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    margin: 3px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    color: white;
    font-weight: 600;
    overflow: hidden;
    position: relative;
    z-index: 1;
}

.dp-timeline-bar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    z-index: 100;
}

.dp-timeline-bar-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    white-space: nowrap;
    overflow: hidden;
}

.dp-timeline-bar-name {
    font-size: 0.85rem;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dp-timeline-bar-slots {
    font-size: 0.75rem;
    background: rgba(255,255,255,0.2);
    padding: 0.125rem 0.5rem;
    border-radius: 0.25rem;
}

/* Buttons */
.dp-btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0.75rem;
    border: none;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.dp-btn-primary {
    background: var(--dp-gradient-primary);
    color: white;
}

.dp-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--dp-shadow-lg);
}

.dp-btn-secondary {
    background: var(--dp-gray-200);
    color: var(--dp-gray-900);
}

.dp-btn-secondary:hover {
    background: var(--dp-gray-300);
}

/* Modal */
.dp-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.dp-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    cursor: pointer;
}

.dp-modal-content {
    position: relative;
    background: white;
    border-radius: 1rem;
    box-shadow: var(--dp-shadow-lg);
    max-width: 500px;
    width: 90%;
    z-index: 1001;
}

.dp-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid var(--dp-gray-200);
}

.dp-modal-header h2 {
    margin: 0;
    font-size: 1.25rem;
}

.dp-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--dp-gray-500);
    transition: color 0.2s;
}

.dp-modal-close:hover {
    color: var(--dp-gray-900);
}

.dp-modal-body {
    padding: 1.5rem;
}

.dp-form-group {
    margin-bottom: 1rem;
}

.dp-form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--dp-gray-900);
}

.dp-form-group input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--dp-gray-300);
    border-radius: 0.5rem;
    font-size: 1rem;
    font-family: inherit;
    box-sizing: border-box;
}

.dp-form-group input:focus {
    outline: none;
    border-color: var(--dp-primary);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.dp-modal-footer {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 1rem;
}

/* Empty State */
.dp-empty-state {
    text-align: center;
    padding: 3rem 2rem;
    background: white;
    border-radius: 1rem;
    border: 2px dashed var(--dp-gray-200);
}

.dp-empty-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 1rem;
}

.dp-empty-state h3 {
    margin: 0 0 0.5rem 0;
    color: var(--dp-gray-900);
}

.dp-empty-state p {
    margin: 0;
    color: var(--dp-gray-500);
}

/* Responsive */
@media (max-width: 768px) {
    .dp-frontend-container {
        padding: 1rem 0.75rem;
    }
    
    .dp-compact-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .dp-compact-header-left {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .dp-compact-title {
        font-size: 1.25rem;
    }
    
    .dp-compact-change-btn {
        width: 100%;
        text-align: center;
    }
    
    .dp-view-switcher {
        flex-direction: column;
    }
    
    .dp-vereine-grid,
    .dp-events-grid {
        grid-template-columns: 1fr;
    }
    
    .dp-timeline-grid {
        grid-template-columns: 60px 1fr;
    }
}
</style>

<?php
// Inline JavaScript für Accordion, Modal, Timeline, Vereinsauswahl
?>
<script type="text/javascript">
// Cookie-Funktionen
function dpSetCookie(name, value, days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

// Vereinsauswahl und Cookie setzen
window.dpSelectVerein = function(vereinId, veranstaltungId) {
    // Cookie setzen (30 Tage gültig)
    dpSetCookie('dp_selected_verein_' + veranstaltungId, vereinId, 30);
    
    // Seite mit Verein-Parameter neu laden
    var url = new URL(window.location.href);
    url.searchParams.set('verein_id', vereinId);
    window.location.href = url.toString();
};

// Modal für Vereinswechsel anzeigen
window.dpShowVereinModal = function() {
    var modal = document.getElementById('dp-verein-change-modal');
    if (modal) {
        modal.style.display = 'flex';
    }
};

// Modal für Vereinswechsel schließen
window.dpHideVereinModal = function() {
    var modal = document.getElementById('dp-verein-change-modal');
    if (modal) {
        modal.style.display = 'none';
    }
};

// Cookie löschen und Vereinsauswahl zurücksetzen
window.dpClearVereinCookie = function(veranstaltungId) {
    dpSetCookie('dp_selected_verein_' + veranstaltungId, '', -1);
};

// Bereich-Filter
window.dpFilterBereich = function(bereichId, button) {
    // Alle Filter-Buttons deaktivieren
    document.querySelectorAll('.dp-filter-btn').forEach(function(btn) {
        btn.classList.remove('active');
    });
    
    // Aktuellen Button aktivieren
    button.classList.add('active');
    
    // Alle Service-Items durchgehen
    document.querySelectorAll('.dp-service-item').forEach(function(item) {
        var itemBereichId = item.getAttribute('data-bereich-id');
        
        if (bereichId === null || bereichId === 'null') {
            // Alle anzeigen
            item.style.display = '';
        } else if (itemBereichId === String(bereichId)) {
            // Passender Bereich
            item.style.display = '';
        } else {
            // Nicht passend
            item.style.display = 'none';
        }
    });
    
    // Prüfe ob Accordions leer sind und verstecke sie
    document.querySelectorAll('.dp-accordion-item').forEach(function(accordion) {
        var visibleItems = accordion.querySelectorAll('.dp-service-item:not([style*="display: none"])');
        if (visibleItems.length === 0) {
            accordion.style.display = 'none';
        } else {
            accordion.style.display = '';
        }
    });
};

// Zeige Modal beim Laden wenn nötig
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('dp-verein-modal');
    if (modal) {
        // Modal nach kurzer Verzögerung anzeigen
        setTimeout(function() {
            modal.style.display = 'flex';
        }, 300);
    }
});

window.dpToggleAccordion = function(button) {
    var item = button.closest('.dp-accordion-item');
    var content = item.querySelector('.dp-accordion-content');
    var isOpen = item.classList.contains('open');
    
    // Close all other items
    document.querySelectorAll('.dp-accordion-item').forEach(function(el) {
        if (el !== item && el.classList.contains('open')) {
            el.classList.remove('open');
            el.querySelector('.dp-accordion-content').style.display = 'none';
        }
    });
    
    if (isOpen) {
        item.classList.remove('open');
        content.style.display = 'none';
    } else {
        item.classList.add('open');
        content.style.display = 'block';
    }
};

window.dpOpenRegistrationModal = function(serviceId, serviceName) {
    document.getElementById('dpDienstId').value = serviceId;
    document.getElementById('dpRegistrationModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
};

window.dpCloseRegistrationModal = function() {
    document.getElementById('dpRegistrationModal').style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('dpRegistrationForm').reset();
    // Reset Split-Options
    document.getElementById('dpSplitOptions').style.display = 'none';
};

window.dpToggleSplitOptions = function(checkbox) {
    var splitOptions = document.getElementById('dpSplitOptions');
    var radioButtons = splitOptions.querySelectorAll('input[type="radio"]');
    
    if (checkbox.checked) {
        splitOptions.style.display = 'block';
        // Ersten Radio-Button automatisch auswählen
        radioButtons[0].checked = true;
        // Radio-Buttons erforderlich machen
        radioButtons.forEach(function(radio) {
            radio.required = true;
        });
    } else {
        splitOptions.style.display = 'none';
        // Radio-Buttons zurücksetzen
        radioButtons.forEach(function(radio) {
            radio.checked = false;
            radio.required = false;
        });
    }
};

window.dpSwitchTimelineDay = function(button, dayId) {
    // Deactivate all tabs
    document.querySelectorAll('.dp-timeline-tab').forEach(function(tab) {
        tab.classList.remove('active');
    });
    
    // Activate clicked tab
    button.classList.add('active');
    
    // Hide all day containers
    document.querySelectorAll('.dp-timeline-day-container').forEach(function(day) {
        day.style.display = 'none';
    });
    
    // Show selected day
    var selectedDay = document.querySelector('.dp-timeline-day-container[data-day-id="' + dayId + '"]');
    if (selectedDay) {
        selectedDay.style.display = 'block';
        
        // Sync scroll zwischen Header und Grid
        var headerScroll = selectedDay.querySelector('.dp-timeline-header-scroll');
        var gridScroll = selectedDay.querySelector('.dp-timeline-grid-scroll');
        
        if (headerScroll && gridScroll) {
            headerScroll.addEventListener('scroll', function() {
                gridScroll.scrollLeft = headerScroll.scrollLeft;
            });
            
            gridScroll.addEventListener('scroll', function() {
                headerScroll.scrollLeft = gridScroll.scrollLeft;
            });
        }
    }
};

// Show first day on page load
document.addEventListener('DOMContentLoaded', function() {
    var firstDay = document.querySelector('.dp-timeline-day-container');
    if (firstDay) {
        firstDay.style.display = 'block';
        
        // Setup scroll sync für ersten Tag
        var headerScroll = firstDay.querySelector('.dp-timeline-header-scroll');
        var gridScroll = firstDay.querySelector('.dp-timeline-grid-scroll');
        
        if (headerScroll && gridScroll) {
            headerScroll.addEventListener('scroll', function() {
                gridScroll.scrollLeft = headerScroll.scrollLeft;
            });
            
            gridScroll.addEventListener('scroll', function() {
                headerScroll.scrollLeft = gridScroll.scrollLeft;
            });
        }
    }
    
    // Handle registration form submission
    var form = document.getElementById('dpRegistrationForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(form);
            formData.append('action', 'dp_register_service');
            
            // Button deaktivieren während Request läuft
            var submitBtn = form.querySelector('button[type="submit"]');
            var originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Wird angemeldet...';
            
            // AJAX-Request
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function(response) {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.text();
            })
            .then(function(text) {
                console.log('Response text:', text);
                try {
                    var data = JSON.parse(text);
                    if (data.success) {
                        alert(data.data.message);
                        dpCloseRegistrationModal();
                        // Seite neu laden um aktualisierte Besetzung zu zeigen
                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    } else {
                        alert('Fehler: ' + (data.data && data.data.message ? data.data.message : 'Unbekannter Fehler'));
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    console.error('Response was:', text);
                    alert('Fehler: Ungültige Antwort vom Server');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            })
            .catch(function(error) {
                console.error('AJAX Error:', error);
                alert('Fehler beim Senden der Anmeldung: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    }
});
</script>

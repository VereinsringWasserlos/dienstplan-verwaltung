<?php
/**
 * Übersicht / Matrix-Ansicht für Dienste
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/admin/views
 */

if (!defined('ABSPATH')) exit;

// Filter-Parameter
$selected_veranstaltung = isset($_GET['veranstaltung']) ? intval($_GET['veranstaltung']) : 0;
$selected_bereich = isset($_GET['bereich']) ? intval($_GET['bereich']) : 0;
$selected_tag = isset($_GET['tag']) ? intval($_GET['tag']) : 0;

// Daten laden
$dienste = array();
$mitarbeiter_zuweisungen = array();
$zeitslots = array();
$veranstaltung_tage = array();
$timeline_start = null;
$timeline_end = null;

if ($selected_veranstaltung > 0) {
    // Lade Tage der Veranstaltung
    $veranstaltung_tage = $db->get_veranstaltung_tage($selected_veranstaltung);
    
    // Lade Dienste - optional nach Tag filtern
    if ($selected_tag > 0) {
        $dienste = $db->get_dienste($selected_veranstaltung, null, $selected_tag);
    } else {
        $dienste = $db->get_dienste($selected_veranstaltung);
    }
    
    // Optional: Nach Bereich filtern
    if ($selected_bereich > 0) {
        $dienste = array_filter($dienste, function($d) use ($selected_bereich) {
            return $d->bereich_id == $selected_bereich;
        });
    }
    
    // Ermittle Zeitbereich für Timeline (frühester Start bis spätestes Ende)
    foreach ($dienste as $dienst) {
        if (!empty($dienst->von_zeit)) {
            $start_time = strtotime($dienst->von_zeit);
            if ($timeline_start === null || $start_time < $timeline_start) {
                $timeline_start = $start_time;
            }
        }
        if (!empty($dienst->bis_zeit)) {
            $end_time = strtotime($dienst->bis_zeit);
            if ($timeline_end === null || $end_time > $timeline_end) {
                $timeline_end = $end_time;
            }
        }
    }
    
    // Runde auf 10-Minuten-Schritte
    if ($timeline_start !== null) {
        $timeline_start = floor($timeline_start / 600) * 600; // 600s = 10min
    }
    if ($timeline_end !== null) {
        $timeline_end = ceil($timeline_end / 600) * 600;
    }
    
    // Sammle alle einzigartigen Zeitslots (Spalten) - nur volle Stunden
    $zeitslots_map = array();
    foreach ($dienste as $dienst) {
        if (!empty($dienst->von_zeit)) {
            // Runde auf volle Stunde
            $hour = date('H:00', strtotime($dienst->von_zeit));
            $zeitslots_map[$hour] = $hour;
        }
    }
    ksort($zeitslots_map);
    
    // Initialisiere Array mit ALLEN Veranstaltungstagen (auch ohne Dienste)
    $dienste_nach_tagen = array();
    foreach ($veranstaltung_tage as $tag) {
        $dienste_nach_tagen[$tag->id] = array(
            'tag' => $tag,
            'dienste_rows' => array()
        );
    }
    
    // Sammle Dienste ohne gültige Tag-Zuordnung
    $dienste_ohne_tag = array();
    
    // Ordne Dienste den Tagen zu
    foreach ($dienste as $dienst) {
        // Überspringe Dienste ohne tag_id
        if (empty($dienst->tag_id) || $dienst->tag_id == 0) {
            $dienste_ohne_tag[] = $dienst;
            continue;
        }
        
        // Prüfe ob Tag in der Veranstaltung existiert
        if (!isset($dienste_nach_tagen[$dienst->tag_id])) {
            // Tag existiert nicht (mehr) in der Veranstaltung
            $dienste_ohne_tag[] = $dienst;
            continue;
        }
        
        $tag_key = $dienst->tag_id;
        
        // Erstelle Zeit-String
        $zeit_display = 'Keine Zeiten';
        if (!empty($dienst->von_zeit) && !empty($dienst->bis_zeit)) {
            $zeit_display = date('H:i', strtotime($dienst->von_zeit)) . ' - ' . date('H:i', strtotime($dienst->bis_zeit));
            if ($dienst->bis_datum) {
                $zeit_display .= ' (+1)';
            }
        }
        
        // Lade zugewiesene Mitarbeiter über Slots
        $mitarbeiter_liste = array();
        $slots = $db->get_dienst_slots($dienst->id);
        foreach ($slots as $slot) {
            if (!empty($slot->mitarbeiter_id)) {
                $mitarbeiter = $db->get_mitarbeiter($slot->mitarbeiter_id);
                if ($mitarbeiter) {
                    $mitarbeiter_liste[] = array(
                        'name' => $mitarbeiter->vorname . ' ' . $mitarbeiter->nachname,
                        'initial' => strtoupper(substr($mitarbeiter->vorname, 0, 1) . substr($mitarbeiter->nachname, 0, 1))
                    );
                }
            }
        }
        
        // Füge Dienst als eigene Zeile hinzu
        $dienste_nach_tagen[$tag_key]['dienste_rows'][] = array(
            'dienst_id' => $dienst->id,
            'bereich' => $dienst->bereich_name,
            'bereich_farbe' => $dienst->bereich_farbe,
            'taetigkeit' => $dienst->taetigkeit_name,
            'verein' => $dienst->verein_name,
            'zeit' => $zeit_display,
            'mitarbeiter' => $mitarbeiter_liste,
            'anzahl_personen' => $dienst->anzahl_personen
        );
    }
    
    // Sortiere Tage nach tag_nummer
    uasort($dienste_nach_tagen, function($a, $b) {
        return $a['tag']->tag_nummer - $b['tag']->tag_nummer;
    });
}

// Hole alle Bereiche für Filter
$bereiche = $db->get_bereiche();
?>

<div class="wrap dienstplan-wrap">
    <div style="display: flex; align-items: center; gap: 1rem; margin: 1rem 0 1.5rem 0; background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); padding: 1rem 1.5rem; border-radius: 8px; color: white;">
        <div style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; flex-shrink: 0;">
            <span class="dashicons dashicons-grid-view" style="font-size: 24px; color: white;"></span>
        </div>
        <div style="flex: 1;">
            <h1 style="margin: 0; font-size: 1.3rem; font-weight: 600; color: white;">
                <?php _e('Dienst-Übersicht', 'dienstplan-verwaltung'); ?>
            </h1>
        </div>
        <a href="<?php echo admin_url('admin.php?page=dienstplan'); ?>" class="button" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 0.5rem 1rem; border-radius: 3px; display: flex; align-items: center; gap: 0.5rem; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
            <span class="dashicons dashicons-arrow-left-alt2" style="font-size: 18px;"></span>
            <?php _e('Dashboard', 'dienstplan-verwaltung'); ?>
        </a>
    </div>
    
    <hr class="wp-header-end">
    
    <!-- Filter-Bereich -->
    <div class="dp-filter-bar" style="background: #fff; padding: 1.5rem; border: 1px solid #c3c4c7; border-radius: 4px; margin: 1.5rem 0;">
        <h3 style="margin-top: 0;">
            <span class="dashicons dashicons-filter"></span>
            <?php _e('Filter', 'dienstplan-verwaltung'); ?>
        </h3>
        
        <form method="get" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <input type="hidden" name="page" value="dienstplan-overview">
            
            <div style="flex: 1; min-width: 250px;">
                <label for="filter-veranstaltung" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Veranstaltung', 'dienstplan-verwaltung'); ?> *
                </label>
                <select id="filter-veranstaltung" name="veranstaltung" class="regular-text" style="width: 100%;" required>
                    <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                    <?php foreach ($veranstaltungen as $v): ?>
                        <option value="<?php echo $v->id; ?>" <?php selected($selected_veranstaltung, $v->id); ?>>
                            <?php echo esc_html($v->name); ?>
                            (<?php echo date_i18n('d.m.Y', strtotime($v->start_datum)); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="flex: 1; min-width: 200px;">
                <label for="filter-bereich" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Bereich', 'dienstplan-verwaltung'); ?>
                </label>
                <select id="filter-bereich" name="bereich" class="regular-text" style="width: 100%;">
                    <option value=""><?php _e('-- Alle --', 'dienstplan-verwaltung'); ?></option>
                    <?php foreach ($bereiche as $b): ?>
                        <option value="<?php echo $b->id; ?>" <?php selected($selected_bereich, $b->id); ?>>
                            <?php echo esc_html($b->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if ($selected_veranstaltung > 0 && !empty($veranstaltung_tage)): ?>
            <div style="flex: 1; min-width: 200px;">
                <label for="filter-tag" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Tag', 'dienstplan-verwaltung'); ?>
                </label>
                <select id="filter-tag" name="tag" class="regular-text" style="width: 100%;">
                    <option value=""><?php _e('-- Alle Tage --', 'dienstplan-verwaltung'); ?></option>
                    <?php foreach ($veranstaltung_tage as $tag): ?>
                        <option value="<?php echo $tag->id; ?>" <?php selected($selected_tag, $tag->id); ?>>
                            Tag <?php echo $tag->tag_nummer; ?>: <?php echo date_i18n('d.m.Y', strtotime($tag->tag_datum)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div>
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Filtern', 'dienstplan-verwaltung'); ?>
                </button>
                <?php if ($selected_veranstaltung > 0): ?>
                    <a href="?page=dienstplan-overview" class="button">
                        <?php _e('Zurücksetzen', 'dienstplan-verwaltung'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <?php if ($selected_veranstaltung > 0): ?>
        <?php if (empty($dienste)): ?>
            <div class="notice notice-info" style="margin-top: 2rem;">
                <p>
                    <strong><?php _e('Keine Dienste gefunden', 'dienstplan-verwaltung'); ?></strong><br>
                    <?php _e('Für diese Veranstaltung sind noch keine Dienste angelegt.', 'dienstplan-verwaltung'); ?>
                </p>
            </div>
        <?php else: ?>
            <!-- Timeline-Ansicht -->
            <div style="margin: 1.5rem 0; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                <div style="color: #666;">
                    <strong><?php echo count($dienste); ?></strong> <?php _e('Dienste', 'dienstplan-verwaltung'); ?>
                    <?php if ($timeline_start && $timeline_end): ?>
                        • <?php _e('Zeitraum', 'dienstplan-verwaltung'); ?>: 
                        <strong><?php echo date('H:i', $timeline_start); ?></strong> - 
                        <strong><?php echo date('H:i', $timeline_end); ?></strong>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($timeline_start && $timeline_end): ?>
                <!-- Timeline-Container -->
                <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; overflow: hidden;">
                    <div style="overflow-x: auto; overflow-y: auto; max-height: 800px;">
                        <table style="width: 100%; border-collapse: collapse; min-width: 1200px;">
                            <thead style="position: sticky; top: 0; z-index: 20; background: #f6f7f7;">
                                <tr>
                                    <!-- Fixierte linke Spalten -->
                                    <th style="position: sticky; left: 0; z-index: 21; background: #f6f7f7; min-width: 100px; max-width: 100px; padding: 8px; border-right: 2px solid #c3c4c7; font-size: 11px; font-weight: 600;">
                                        Bereich
                                    </th>
                                    <th style="position: sticky; left: 100px; z-index: 21; background: #f6f7f7; min-width: 120px; max-width: 120px; padding: 8px; border-right: 2px solid #c3c4c7; font-size: 11px; font-weight: 600;">
                                        Tätigkeit
                                    </th>
                                    <th style="position: sticky; left: 220px; z-index: 21; background: #f6f7f7; min-width: 80px; max-width: 80px; padding: 8px; border-right: 2px solid #c3c4c7; font-size: 11px; font-weight: 600;">
                                        Verein
                                    </th>
                                    <th style="position: sticky; left: 300px; z-index: 21; background: #f6f7f7; min-width: 100px; max-width: 100px; padding: 8px; border-right: 3px solid #666; font-size: 11px; font-weight: 600;">
                                        Zeit
                                    </th>
                                    
                                    <!-- Zeitslots (10-Minuten-Schritte) -->
                                    <?php
                                    $current_time = $timeline_start;
                                    $slot_width = 30; // Pixel pro 10-Minuten-Slot
                                    while ($current_time < $timeline_end):
                                        $time_label = date('H:i', $current_time);
                                        $is_hour = date('i', $current_time) === '00';
                                    ?>
                                        <th style="min-width: <?php echo $slot_width; ?>px; max-width: <?php echo $slot_width; ?>px; padding: 8px 2px; font-size: 9px; font-weight: <?php echo $is_hour ? '700' : '400'; ?>; border-right: 1px solid <?php echo $is_hour ? '#999' : '#ddd'; ?>; text-align: center; background: <?php echo $is_hour ? '#e5e7eb' : '#f6f7f7'; ?>;">
                                            <?php echo $is_hour ? $time_label : ''; ?>
                                        </th>
                                    <?php
                                        $current_time += 600; // +10 Minuten
                                    endwhile;
                                    ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dienste_nach_tagen as $tag_id => $tag_data): 
                                    $tag = $tag_data['tag'];
                                    $tag_dienste = array_filter($dienste, function($d) use ($tag_id) {
                                        return $d->tag_id == $tag_id;
                                    });
                                    
                                    if (empty($tag_dienste)) continue; // Überspringe Tage ohne Dienste
                                ?>
                                    <!-- Tag-Header -->
                                    <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                        <td colspan="4" style="position: sticky; left: 0; z-index: 10; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 12px; color: #fff; font-weight: 700; font-size: 13px; border-right: 3px solid #666;">
                                            <span class="dashicons dashicons-calendar-alt" style="font-size: 16px; vertical-align: middle;"></span>
                                            Tag <?php echo $tag->tag_nummer; ?>: <?php echo date_i18n('l, d.m.Y', strtotime($tag->tag_datum)); ?>
                                            <span style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 12px; margin-left: 8px; font-size: 11px;">
                                                <?php echo count($tag_dienste); ?> Dienst<?php echo count($tag_dienste) != 1 ? 'e' : ''; ?>
                                            </span>
                                        </td>
                                        <td colspan="999" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 12px;"></td>
                                    </tr>
                                    
                                    <?php 
                                    $tag_index = 0;
                                    foreach ($tag_dienste as $dienst): 
                                        // Lade Slots und Mitarbeiter
                                        $slots = $db->get_dienst_slots($dienst->id);
                                        $mitarbeiter_namen = array();
                                        foreach ($slots as $slot) {
                                            if (!empty($slot->mitarbeiter_id)) {
                                                $ma = $db->get_mitarbeiter($slot->mitarbeiter_id);
                                                if ($ma) {
                                                    $mitarbeiter_namen[] = $ma->vorname . ' ' . $ma->nachname;
                                                }
                                            }
                                        }
                                        
                                        // Berechne Position und Breite des Balkens
                                        $dienst_start = strtotime($dienst->von_zeit);
                                        $dienst_end = strtotime($dienst->bis_zeit);
                                        
                                        // Position in Slots (0-basiert)
                                        $start_slot = floor(($dienst_start - $timeline_start) / 600);
                                        $end_slot = ceil(($dienst_end - $timeline_start) / 600);
                                        $slot_count = $end_slot - $start_slot;
                                        
                                        $row_bg = $tag_index % 2 ? '#fafafa' : '#fff';
                                    ?>
                                        <tr style="border-bottom: 1px solid #e5e7eb;">
                                            <!-- Fixierte Spalten -->
                                            <td style="position: sticky; left: 0; z-index: 10; background: <?php echo $row_bg; ?>; padding: 6px; border-right: 2px solid #c3c4c7; font-size: 11px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <span style="background: <?php echo esc_attr($dienst->bereich_farbe); ?>; color: #fff; padding: 3px 6px; border-radius: 3px; font-weight: 600; font-size: 10px;">
                                                    <?php echo esc_html($dienst->bereich_name); ?>
                                                </span>
                                            </td>
                                            <td style="position: sticky; left: 100px; z-index: 10; background: <?php echo $row_bg; ?>; padding: 6px; border-right: 2px solid #c3c4c7; font-size: 11px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?php echo esc_html($dienst->taetigkeit_name); ?>
                                            </td>
                                            <td style="position: sticky; left: 220px; z-index: 10; background: <?php echo $row_bg; ?>; padding: 6px; border-right: 2px solid #c3c4c7; font-size: 11px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <span title="<?php echo esc_attr($dienst->verein_name); ?>">
                                                    <?php echo esc_html($dienst->verein_kuerzel ?: $dienst->verein_name); ?>
                                                </span>
                                            </td>
                                            <td style="position: sticky; left: 300px; z-index: 10; background: <?php echo $row_bg; ?>; padding: 6px; border-right: 3px solid #666; font-size: 11px; font-weight: 600; white-space: nowrap;">
                                                <?php echo date('H:i', strtotime($dienst->von_zeit)); ?>-<?php echo date('H:i', strtotime($dienst->bis_zeit)); ?>
                                            </td>
                                            
                                            <!-- Timeline-Zellen -->
                                            <?php
                                            $current_time = $timeline_start;
                                            $slot_index = 0;
                                            while ($current_time < $timeline_end):
                                                $in_dienst = ($slot_index >= $start_slot && $slot_index < $end_slot);
                                                $is_first = ($slot_index == $start_slot);
                                                $is_last = ($slot_index == $end_slot - 1);
                                            ?>
                                                <td style="min-width: <?php echo $slot_width; ?>px; max-width: <?php echo $slot_width; ?>px; padding: 0; border-right: 1px solid #e5e7eb; background: <?php echo $in_dienst ? esc_attr($dienst->bereich_farbe) : 'transparent'; ?>; position: relative; height: 40px;">
                                                    <?php if ($is_first && !empty($mitarbeiter_namen)): ?>
                                                        <div style="position: absolute; left: 2px; right: 0; top: 6px; bottom: 6px; display: flex; align-items: center; padding: 0 4px; overflow: hidden; white-space: nowrap;">
                                                            <span style="color: #fff; font-size: 10px; font-weight: 600; text-overflow: ellipsis; overflow: hidden;">
                                                                <?php echo esc_html(implode(', ', $mitarbeiter_namen)); ?>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php
                                                $current_time += 600;
                                                $slot_index++;
                                            endwhile;
                                            ?>
                                        </tr>
                                    <?php 
                                        $tag_index++;
                                    endforeach; 
                                    ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="notice notice-warning">
                    <p><?php _e('Keine gültigen Zeitangaben in den Diensten gefunden.', 'dienstplan-verwaltung'); ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php else: ?>
        <div class="notice notice-info" style="margin-top: 2rem;">
            <p>
                <strong><?php _e('Keine Veranstaltung ausgewählt', 'dienstplan-verwaltung'); ?></strong><br>
                <?php _e('Bitte wählen Sie eine Veranstaltung aus, um die Übersicht anzuzeigen.', 'dienstplan-verwaltung'); ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript moved to assets/js/dp-overview.js -->
<style>
/* Timeline Scrollbar-Styling */
div[style*="overflow-x: auto"]::-webkit-scrollbar {
    height: 10px;
}

div[style*="overflow-x: auto"]::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 5px;
}

div[style*="overflow-x: auto"]::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 5px;
}

div[style*="overflow-x: auto"]::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Hover-Effekt für Timeline-Zeilen */
tbody tr:hover {
    background-color: #f0f9ff !important;
}

/* Print-Styles */
@media print {
    .dp-filter-bar,
    .button,
    .dashicons {
        display: none !important;
    }
    
    table {
        page-break-inside: auto;
    }
    
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Smooth Scrolling für Timeline
    const timelineContainer = $('div[style*="overflow-x: auto"]').first();
    
    // Scroll zu aktueller Uhrzeit (falls im Zeitbereich)
    const now = new Date();
    const currentHour = now.getHours();
    const currentMinute = now.getMinutes();
    
    // Optional: Auto-scroll zu aktueller Zeit implementieren
    console.log('Timeline geladen für ' + <?php echo count($dienste); ?> + ' Dienste');
});
</script>

<?php
/**
 * Dienste Tabelle
 */
if (!defined('ABSPATH')) exit;

if (!isset($is_restricted_club_admin)) {
    $is_restricted_club_admin = Dienstplan_Roles::can_manage_clubs() && !current_user_can('manage_options') && !current_user_can(Dienstplan_Roles::CAP_MANAGE_SETTINGS);
}

// Gruppiere Dienste nach Tagen
$dienste_nach_tagen = array();
$dienste_ohne_tag = array();

foreach ($dienste as $dienst) {
    $tag_id = $dienst->tag_id;
    
    if (!$tag_id) {
        $dienste_ohne_tag[] = $dienst;
        continue;
    }
    
    if (!isset($dienste_nach_tagen[$tag_id])) {
        $tag = $db->get_veranstaltung_tag($tag_id);
        if (!$tag) {
            // Tag existiert nicht mehr, zeige als "ohne Tag"
            $dienste_ohne_tag[] = $dienst;
            continue;
        }
        $dienste_nach_tagen[$tag_id] = array(
            'tag' => $tag,
            'dienste' => array()
        );
    }
    $dienste_nach_tagen[$tag_id]['dienste'][] = $dienst;
}

// Sortiere Tage nach Datum
uasort($dienste_nach_tagen, function($a, $b) {
    return strcmp($a['tag']->tag_datum, $b['tag']->tag_datum);
});
?>

<div class="dienste-list" style="margin-top: 2rem; overflow: visible; position: relative;">
    <?php foreach ($dienste_nach_tagen as $tag_id => $tag_data): 
        $tag = $tag_data['tag'];
        $tag_dienste = $tag_data['dienste'];
        $collapse_id = 'tag-' . $tag_id;
    ?>
        <div class="tag-dienste-gruppe" style="margin-bottom: 1.5rem; border: 1px solid #c3c4c7; border-radius: 4px; position: relative;">
            <!-- Einklappbarer Header -->
            <h3 class="tag-header-collapsible" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 1.5rem; margin: 0; display: flex; align-items: center; gap: 1rem; transition: all 0.3s;">
                <span class="dashicons dashicons-arrow-down-alt2 collapse-icon" id="icon-<?php echo $collapse_id; ?>" onclick="toggleTagGroup('<?php echo $collapse_id; ?>')" style="transition: transform 0.3s; font-size: 20px; cursor: pointer;"></span>
                
                <span onclick="toggleTagGroup('<?php echo $collapse_id; ?>')" style="flex: 1; display: flex; align-items: center; gap: 1rem; cursor: pointer;">
                    <span class="dashicons dashicons-calendar" style="font-size: 24px;"></span>
                    <strong style="font-size: 1.1rem;">Tag <?php echo $tag->tag_nummer; ?>:</strong>
                    <span><?php echo date_i18n('l, d.m.Y', strtotime($tag->tag_datum)); ?></span>
                </span>
                
                <?php if (!$is_restricted_club_admin): ?>
                    <button type="button" class="button button-primary" onclick="event.stopPropagation(); openDienstModal(<?php echo $tag_id; ?>);" style="background: rgba(255,255,255,0.9); color: #667eea; border: none; font-weight: 600; padding: 0.5rem 1rem; border-radius: 3px; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;" onmouseover="this.style.background='#fff'" onmouseout="this.style.background='rgba(255,255,255,0.9)'">
                        <span class="dashicons dashicons-plus-alt" style="font-size: 18px; width: 18px; height: 18px;"></span>
                        <?php _e('Neuer Dienst', 'dienstplan-verwaltung'); ?>
                    </button>
                <?php endif; ?>
                
                <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.9rem;">
                    <?php echo count($tag_dienste); ?> Dienst<?php echo count($tag_dienste) != 1 ? 'e' : ''; ?>
                </span>
                
                <?php if ($tag->von_zeit && $tag->bis_zeit): ?>
                    <span style="font-weight: normal; font-size: 0.9rem; background: rgba(255,255,255,0.15); padding: 0.25rem 0.75rem; border-radius: 3px;">
                        <span class="dashicons dashicons-clock" style="font-size: 16px;"></span>
                        Event: <?php echo substr($tag->von_zeit, 0, 5); ?> - <?php echo substr($tag->bis_zeit, 0, 5); ?>
                    </span>
                <?php endif; ?>
                
                <?php if ($tag->dienst_von_zeit && $tag->dienst_bis_zeit): ?>
                    <span style="font-weight: normal; font-size: 0.9rem; background: rgba(255,255,255,0.15); padding: 0.25rem 0.75rem; border-radius: 3px;">
                        <span class="dashicons dashicons-hammer" style="font-size: 16px;"></span>
                        Dienst: <?php echo substr($tag->dienst_von_zeit, 0, 5); ?> - <?php echo substr($tag->dienst_bis_zeit, 0, 5); ?>
                    </span>
                <?php endif; ?>
            </h3>
            
            <!-- Einklappbarer Content -->
            <div id="<?php echo $collapse_id; ?>" class="tag-content" style="display: block;">
            <?php if (!$is_restricted_club_admin): ?>
                <!-- Bulk-Aktionen Toolbar -->
                <div class="bulk-actions-toolbar" style="background: #f9fafb; padding: 1rem; border: 1px solid #e5e7eb; border-bottom: none; display: flex;">
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <span class="selected-count" style="color: #6b7280;">
                            <span class="count">0</span> <?php _e('ausgewählt', 'dienstplan-verwaltung'); ?>
                        </span>
                        
                        <select class="bulk-action-select" style="min-width: 200px;">
                            <option value=""><?php _e('-- Aktion wählen --', 'dienstplan-verwaltung'); ?></option>
                            <option value="delete"><?php _e('Löschen', 'dienstplan-verwaltung'); ?></option>
                            <option value="move_tag"><?php _e('Tag verschieben', 'dienstplan-verwaltung'); ?></option>
                            <option value="change_time"><?php _e('Zeiten ändern', 'dienstplan-verwaltung'); ?></option>
                            <option value="change_verein"><?php _e('Verein ändern', 'dienstplan-verwaltung'); ?></option>
                            <option value="change_bereich"><?php _e('Bereich ändern', 'dienstplan-verwaltung'); ?></option>
                            <option value="change_taetigkeit"><?php _e('Tätigkeit ändern', 'dienstplan-verwaltung'); ?></option>
                            <option value="change_status"><?php _e('Status ändern', 'dienstplan-verwaltung'); ?></option>
                        </select>
                        
                        <button type="button" class="button bulk-action-apply">
                            <?php _e('Anwenden', 'dienstplan-verwaltung'); ?>
                        </button>
                        
                        <button type="button" class="button bulk-action-cancel">
                            <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div style="background: #f9fafb; padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-bottom: none; color: #6b7280; font-size: 0.85rem;">
                    <?php _e('Als Club-Admin sind nur Splitten/Zuteilen (Besetzung) erlaubt.', 'dienstplan-verwaltung'); ?>
                </div>
            <?php endif; ?>
            
            <div style="overflow: visible;">
            <table class="wp-list-table widefat fixed striped" style="position: relative; overflow: visible;">
                <thead>
                    <tr>
                        <th width="3%">
                            <input type="checkbox" class="select-all-header" data-tag="<?php echo $tag_id; ?>">
                        </th>
                        <th width="5%"><?php _e('ID', 'dienstplan-verwaltung'); ?></th>
                        <th width="8%"><?php _e('Zeit', 'dienstplan-verwaltung'); ?></th>
                        <th width="6%"><?php _e('Verein', 'dienstplan-verwaltung'); ?></th>
                        <th width="9%"><?php _e('Bereich', 'dienstplan-verwaltung'); ?></th>
                        <th width="9%"><?php _e('Tätigkeit', 'dienstplan-verwaltung'); ?></th>
                        <th width="8%"><?php _e('Personen', 'dienstplan-verwaltung'); ?></th>
                        <th width="18%"><?php _e('Besetzung', 'dienstplan-verwaltung'); ?></th>
                        <th width="11%"><?php _e('Besonderheiten', 'dienstplan-verwaltung'); ?></th>
                        <th width="26%"><?php _e('Aktionen', 'dienstplan-verwaltung'); ?></th>
                    </tr>
                </thead>
                <tbody style="position: relative; overflow: visible;">
                    <?php foreach ($tag_dienste as $dienst): 
                        $slots = $db->get_dienst_slots($dienst->id);
                        $is_split_dienst = (intval($dienst->splittbar ?? 0) === 1) || (count($slots) >= 2);
                        $ist_unvollstaendig = (isset($dienst->status) && $dienst->status === 'unvollstaendig');
                        $row_style = $ist_unvollstaendig ? 'background: #fef3c7; border-left: 4px solid #f59e0b;' : '';
                    ?>
                        <tr data-dienst-id="<?php echo $dienst->id; ?>" class="dienst-row" style="position: relative; <?php echo $row_style; ?>">
                            <td>
                                <input type="checkbox" class="dienst-checkbox" value="<?php echo $dienst->id; ?>" data-tag="<?php echo $tag_id; ?>">
                            </td>
                            <td>
                                <?php if ($ist_unvollstaendig): ?>
                                    <span class="dashicons dashicons-warning" style="color: #f59e0b; font-size: 1.2em;" title="<?php _e('Unvollständige Daten', 'dienstplan-verwaltung'); ?>"></span>
                                <?php endif; ?>
                                <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 0.85em;">
                                    #<?php echo $dienst->id; ?>
                                </code>
                                <?php if ($ist_unvollstaendig): ?>
                                    <div style="margin-top: 0.35rem;">
                                        <span style="background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; padding: 0.25rem 0.5rem; border-radius: 2px; font-size: 0.75rem; font-weight: 600; display: inline-block;">
                                            ⚠️ Unvollständig
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($dienst->von_zeit) && !empty($dienst->bis_zeit)): ?>
                                    <strong><?php echo substr($dienst->von_zeit, 0, 5); ?></strong>
                                    -
                                    <strong><?php echo substr($dienst->bis_zeit, 0, 5); ?></strong>
                                    <?php if ($dienst->bis_datum): ?>
                                        <br><small style="color: #dc2626;">
                                            <span class="dashicons dashicons-clock" style="font-size: 0.75rem;"></span>
                                            +1 Tag
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #999; font-style: italic;">
                                        <span class="dashicons dashicons-warning" style="font-size: 0.9rem; color: #f59e0b;"></span>
                                        Keine Zeiten
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (empty($dienst->verein_id) || empty($dienst->verein_kuerzel)): ?>
                                    <span style="color: #dc2626; font-weight: 600;">
                                        <span class="dashicons dashicons-warning" style="font-size: 0.9rem;"></span>
                                        Kein Verein
                                    </span>
                                <?php else: ?>
                                    <span style="font-weight: 600; cursor: help;" title="<?php echo esc_attr($dienst->verein_name); ?>">
                                        <?php echo esc_html($dienst->verein_kuerzel); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="bereich-badge" style="background: <?php echo esc_attr($dienst->bereich_farbe); ?>; color: #fff; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem;">
                                    <?php echo esc_html($dienst->bereich_name); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo esc_html($dienst->taetigkeit_name); ?>
                            </td>
                            <td style="text-align: center;">
                                <span class="dashicons dashicons-admin-users"></span>
                                <?php echo intval($dienst->anzahl_personen); ?>
                            </td>
                            <td>
                                <?php 
                                $besetzt_count = 0;
                                $gesamt_count = 0;
                                
                                if (empty($slots)) {
                                    // Keine Slots vorhanden
                                    echo '<span style="color: #dc2626; font-weight: 600; font-size: 0.85rem;">⚠️ Keine Slots!</span>';
                                } else {
                                    // Zähle besetzte Slots
                                    $gesamt_count = count($slots);
                                    $mitarbeiter_namen = array();
                                    
                                    foreach ($slots as $slot) {
                                        if (!empty($slot->mitarbeiter_id)) {
                                            $besetzt_count++;
                                            $mitarbeiter = $db->get_mitarbeiter($slot->mitarbeiter_id);
                                            if ($mitarbeiter) {
                                                $mitarbeiter_namen[] = $mitarbeiter->vorname . ' ' . $mitarbeiter->nachname;
                                            }
                                        }
                                    }
                                    
                                    // Status-Badge
                                    $status_color = $besetzt_count == $gesamt_count ? '#10b981' : ($besetzt_count > 0 ? '#f59e0b' : '#ef4444');
                                    $status_text = $besetzt_count . ' / ' . $gesamt_count . ' besetzt';
                                    $tooltip = !empty($mitarbeiter_namen) ? implode(', ', $mitarbeiter_namen) : 'Keine Mitarbeiter zugewiesen';
                                    ?>
                                    <span class="status-badge" 
                                          style="background: <?php echo $status_color; ?>; color: #fff; padding: 0.35rem 0.65rem; border-radius: 3px; font-size: 0.8rem; font-weight: 600; cursor: help; display: inline-block;" 
                                          title="<?php echo esc_attr($tooltip); ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                    <?php
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($ist_unvollstaendig): ?>
                                    <div style="color: #f59e0b; font-weight: 600; margin-bottom: 0.25rem;">
                                        <span class="dashicons dashicons-warning" style="font-size: 1em;"></span>
                                        <?php _e('Unvollständig', 'dienstplan-verwaltung'); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($dienst->besonderheiten): ?>
                                    <small><?php echo esc_html(wp_trim_words($dienst->besonderheiten, 8)); ?></small>
                                <?php elseif (!$ist_unvollstaendig): ?>
                                    <span style="color: #9ca3af;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="min-width: 250px;">
                                <div style="display: flex; gap: 4px; flex-wrap: nowrap; justify-content: flex-end; align-items: center;">
                                    <button type="button" class="button button-small" title="<?php esc_attr_e('Besetzung verwalten', 'dienstplan-verwaltung'); ?>" onclick="editBesetzung(<?php echo $dienst->id; ?>)">
                                        <span class="dashicons dashicons-admin-users"></span>
                                    </button>
                                    <?php if (!$is_restricted_club_admin): ?>
                                        <button type="button" class="button button-small" title="<?php esc_attr_e('Dienst bearbeiten', 'dienstplan-verwaltung'); ?>" onclick="editDienst(<?php echo $dienst->id; ?>)">
                                            <span class="dashicons dashicons-edit"></span>
                                        </button>
                                        <button type="button" class="button button-small" title="<?php esc_attr_e('Dienst kopieren', 'dienstplan-verwaltung'); ?>" onclick="copyDienst(<?php echo $dienst->id; ?>)">
                                            <span class="dashicons dashicons-admin-page"></span>
                                        </button>
                                        <?php if (!$is_split_dienst): ?>
                                            <button type="button" class="button button-small" title="<?php esc_attr_e('Dienst splitten', 'dienstplan-verwaltung'); ?>" onclick="window.splitDienst(<?php echo $dienst->id; ?>)">
                                                <span class="dashicons dashicons-randomize"></span>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="button button-small" title="<?php esc_attr_e('Split aufheben', 'dienstplan-verwaltung'); ?>" onclick="window.unsplitDienst(<?php echo $dienst->id; ?>)" style="border-color: #f59e0b; color: #b45309;">
                                                <span class="dashicons dashicons-editor-break"></span>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="button button-small" title="<?php esc_attr_e('Dienst löschen', 'dienstplan-verwaltung'); ?>" onclick="if(confirm('<?php _e('Wirklich löschen?', 'dienstplan-verwaltung'); ?>')) { deleteDienst(<?php echo $dienst->id; ?>); }" style="color: #dc2626; border-color: #fecaca;">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    <?php else: ?>
                                        <?php if (!$is_split_dienst): ?>
                                            <button type="button" class="button button-small" title="<?php esc_attr_e('Dienst splitten', 'dienstplan-verwaltung'); ?>" onclick="window.splitDienst(<?php echo $dienst->id; ?>)">
                                                <span class="dashicons dashicons-randomize"></span>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="button button-small" title="<?php esc_attr_e('Split aufheben', 'dienstplan-verwaltung'); ?>" onclick="window.unsplitDienst(<?php echo $dienst->id; ?>)" style="border-color: #f59e0b; color: #b45309;">
                                                <span class="dashicons dashicons-editor-break"></span>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div> <!-- Ende tag-content -->
        </div> <!-- Ende tag-dienste-gruppe -->
    <?php endforeach; ?>
    
    <?php if (!empty($dienste_ohne_tag)): ?>
        <div class="tag-dienste-gruppe" style="margin-bottom: 2rem; border: 1px solid #f59e0b; border-radius: 4px; position: relative;">
            <h3 style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; padding: 1rem 1.5rem; margin: 0; display: flex; align-items: center; gap: 1rem;">
                <span class="dashicons dashicons-warning" style="font-size: 24px;"></span>
                <span style="flex: 1;">
                    <?php _e('Dienste ohne Tag-Zuordnung', 'dienstplan-verwaltung'); ?>
                </span>
                <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.9rem;">
                    <?php echo count($dienste_ohne_tag); ?> Dienst<?php echo count($dienste_ohne_tag) != 1 ? 'e' : ''; ?>
                </span>
            </h3>
            
            <!-- Bulk-Aktionen Toolbar -->
            <div class="bulk-actions-toolbar" style="background: #fff3cd; padding: 1rem; border-bottom: 1px solid #fbbf24; display: flex;">
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span class="selected-count" style="color: #92400e;">
                        <span class="count">0</span> <?php _e('ausgewählt', 'dienstplan-verwaltung'); ?>
                    </span>
                    
                    <select class="bulk-action-select" style="min-width: 200px;">
                        <option value=""><?php _e('-- Aktion wählen --', 'dienstplan-verwaltung'); ?></option>
                        <option value="delete"><?php _e('Löschen', 'dienstplan-verwaltung'); ?></option>
                        <option value="move_tag"><?php _e('Tag zuweisen', 'dienstplan-verwaltung'); ?></option>
                        <option value="change_time"><?php _e('Zeiten ändern', 'dienstplan-verwaltung'); ?></option>
                        <option value="change_verein"><?php _e('Verein ändern', 'dienstplan-verwaltung'); ?></option>
                        <option value="change_bereich"><?php _e('Bereich ändern', 'dienstplan-verwaltung'); ?></option>
                        <option value="change_taetigkeit"><?php _e('Tätigkeit ändern', 'dienstplan-verwaltung'); ?></option>
                        <option value="change_status"><?php _e('Status ändern', 'dienstplan-verwaltung'); ?></option>
                    </select>
                    
                    <button type="button" class="button button-primary bulk-action-apply" disabled>
                        <?php _e('Anwenden', 'dienstplan-verwaltung'); ?>
                    </button>
                    
                    <button type="button" class="button bulk-action-cancel">
                        <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
                    </button>
                </div>
            </div>
            
            <div style="overflow: visible;">
            <table class="wp-list-table widefat fixed striped" style="position: relative; overflow: visible;">
                <thead>
                    <tr>
                        <th width="3%" class="check-column">
                            <input type="checkbox" class="toggle-all-in-section" data-tag="ohne-tag">
                        </th>
                        <th width="5%"><?php _e('ID', 'dienstplan-verwaltung'); ?></th>
                        <th width="8%"><?php _e('Zeit', 'dienstplan-verwaltung'); ?></th>
                        <th width="6%"><?php _e('Verein', 'dienstplan-verwaltung'); ?></th>
                        <th width="9%"><?php _e('Bereich', 'dienstplan-verwaltung'); ?></th>
                        <th width="9%"><?php _e('Tätigkeit', 'dienstplan-verwaltung'); ?></th>
                        <th width="8%"><?php _e('Personen', 'dienstplan-verwaltung'); ?></th>
                        <th width="18%"><?php _e('Besetzung', 'dienstplan-verwaltung'); ?></th>
                        <th width="11%"><?php _e('Besonderheiten', 'dienstplan-verwaltung'); ?></th>
                        <th width="26%"><?php _e('Aktionen', 'dienstplan-verwaltung'); ?></th>
                    </tr>
                </thead>
                <tbody style="position: relative; overflow: visible;">
                    <?php foreach ($dienste_ohne_tag as $dienst): 
                        $slots = $db->get_dienst_slots($dienst->id);
                        $is_split_dienst = (intval($dienst->splittbar ?? 0) === 1) || (count($slots) >= 2);
                        $ist_unvollstaendig = (isset($dienst->status) && $dienst->status === 'unvollstaendig');
                        $row_style = $ist_unvollstaendig ? 'background: #fef3c7; border-left: 4px solid #f59e0b;' : '';
                    ?>
                        <tr data-dienst-id="<?php echo $dienst->id; ?>" data-tag-id="ohne-tag" style="position: relative; <?php echo $row_style; ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" class="dienst-checkbox" value="<?php echo $dienst->id; ?>" data-tag="ohne-tag">
                            </th>
                            <td>
                                <?php if ($ist_unvollstaendig): ?>
                                    <span class="dashicons dashicons-warning" style="color: #f59e0b; font-size: 1.2em;" title="<?php _e('Unvollständige Daten', 'dienstplan-verwaltung'); ?>"></span>
                                <?php endif; ?>
                                <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 0.85em;">
                                    #<?php echo $dienst->id; ?>
                                </code>
                                <?php if ($ist_unvollstaendig): ?>
                                    <div style="margin-top: 0.35rem;">
                                        <span style="background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; padding: 0.25rem 0.5rem; border-radius: 2px; font-size: 0.75rem; font-weight: 600; display: inline-block;">
                                            ⚠️ Unvollständig
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($dienst->von_zeit && $dienst->bis_zeit): ?>
                                    <strong><?php echo substr($dienst->von_zeit, 0, 5); ?></strong>
                                    -
                                    <strong><?php echo substr($dienst->bis_zeit, 0, 5); ?></strong>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (empty($dienst->verein_id) || empty($dienst->verein_kuerzel)): ?>
                                    <span style="color: #dc2626; font-weight: 600;">
                                        <span class="dashicons dashicons-warning" style="font-size: 0.9rem;"></span>
                                        Kein Verein
                                    </span>
                                <?php else: ?>
                                    <span style="font-weight: 600; cursor: help;" title="<?php echo esc_attr($dienst->verein_name); ?>">
                                        <?php echo esc_html($dienst->verein_kuerzel); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="bereich-badge" style="background: <?php echo esc_attr($dienst->bereich_farbe); ?>; color: #fff; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem;">
                                    <?php echo esc_html($dienst->bereich_name); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo esc_html($dienst->taetigkeit_name); ?>
                            </td>
                            <td style="text-align: center;">
                                <span class="dashicons dashicons-admin-users"></span>
                                <?php echo intval($dienst->anzahl_personen); ?>
                            </td>
                            <td>
                                <?php 
                                $besetzt_count = 0;
                                $gesamt_count = 0;
                                $mitarbeiter_namen = array();
                                
                                if (!empty($slots)) {
                                    $gesamt_count = count($slots);
                                    foreach ($slots as $slot) {
                                        if (!empty($slot->mitarbeiter_id)) {
                                            $besetzt_count++;
                                            $mitarbeiter = $db->get_mitarbeiter($slot->mitarbeiter_id);
                                            if ($mitarbeiter) {
                                                $mitarbeiter_namen[] = $mitarbeiter->vorname . ' ' . $mitarbeiter->nachname;
                                            }
                                        }
                                    }
                                    
                                    // Status-Badge
                                    $status_color = $besetzt_count == $gesamt_count ? '#10b981' : ($besetzt_count > 0 ? '#f59e0b' : '#ef4444');
                                    $status_text = $besetzt_count . ' / ' . $gesamt_count . ' besetzt';
                                    $tooltip = !empty($mitarbeiter_namen) ? implode(', ', $mitarbeiter_namen) : 'Keine Mitarbeiter zugewiesen';
                                    ?>
                                    <span class="status-badge" 
                                          style="background: <?php echo $status_color; ?>; color: #fff; padding: 0.35rem 0.65rem; border-radius: 3px; font-size: 0.8rem; font-weight: 600; cursor: help; display: inline-block;" 
                                          title="<?php echo esc_attr($tooltip); ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                    <?php
                                } else {
                                    echo '<span style="color: #dc2626; font-weight: 600; font-size: 0.85rem;">⚠️ Keine Slots!</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($dienst->besonderheiten): ?>
                                    <small><?php echo esc_html(wp_trim_words($dienst->besonderheiten, 8)); ?></small>
                                <?php else: ?>
                                    <span style="color: #9ca3af;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="min-width: 250px;">
                                <div style="display: flex; gap: 4px; flex-wrap: nowrap; justify-content: flex-end; align-items: center;">
                                    <button type="button" class="button button-small" title="<?php esc_attr_e('Dienst bearbeiten', 'dienstplan-verwaltung'); ?>" onclick="editDienst(<?php echo $dienst->id; ?>)">
                                        <span class="dashicons dashicons-edit"></span>
                                    </button>
                                    <button type="button" class="button button-small" title="<?php esc_attr_e('Besetzung verwalten', 'dienstplan-verwaltung'); ?>" onclick="editBesetzung(<?php echo $dienst->id; ?>)">
                                        <span class="dashicons dashicons-admin-users"></span>
                                    </button>
                                    <button type="button" class="button button-small" title="<?php esc_attr_e('Dienst kopieren', 'dienstplan-verwaltung'); ?>" onclick="copyDienst(<?php echo $dienst->id; ?>)">
                                        <span class="dashicons dashicons-admin-page"></span>
                                    </button>
                                    <?php if (!$is_split_dienst): ?>
                                        <button type="button" class="button button-small" title="<?php esc_attr_e('Dienst splitten', 'dienstplan-verwaltung'); ?>" onclick="window.splitDienst(<?php echo $dienst->id; ?>)">
                                            <span class="dashicons dashicons-randomize"></span>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="button button-small" title="<?php esc_attr_e('Split aufheben', 'dienstplan-verwaltung'); ?>" onclick="window.unsplitDienst(<?php echo $dienst->id; ?>)" style="border-color: #f59e0b; color: #b45309;">
                                            <span class="dashicons dashicons-editor-break"></span>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="button button-small" title="<?php esc_attr_e('Dienst löschen', 'dienstplan-verwaltung'); ?>" onclick="if(confirm('<?php _e('Wirklich löschen?', 'dienstplan-verwaltung'); ?>')) { deleteDienst(<?php echo $dienst->id; ?>); }" style="color: #dc2626; border-color: #fecaca;">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div> <!-- Ende overflow-x: auto -->
        </div> <!-- Ende tag-dienste-gruppe ohne-tag -->
    <?php endif; ?>
</div> <!-- Ende dienste-list -->
<!-- JavaScript wurde nach assets/js/dp-dienste-table.js ausgelagert -->

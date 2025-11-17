<?php
/**
 * Partial: Kalender-Ansicht für Dienste
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/public/templates/partials
 */

if (!defined('ABSPATH')) exit;

// Bereite Daten für Kalender auf
$kalender_tage = array();
foreach ($tage as $tag) {
    $tag_dienste = isset($dienste_nach_tagen[$tag->id]) ? $dienste_nach_tagen[$tag->id] : array();
    
    $kalender_tage[] = array(
        'tag' => $tag,
        'dienste' => $tag_dienste,
        'datum' => strtotime($tag->tag_datum)
    );
}

// Sortiere nach Datum
usort($kalender_tage, function($a, $b) {
    return $a['datum'] - $b['datum'];
});
?>

<div class="dp-calendar-view">
    <div class="dp-calendar-grid">
        <?php foreach ($kalender_tage as $tag_data): 
            $tag = $tag_data['tag'];
            $tag_dienste = $tag_data['dienste'];
            
            // Berechne Statistiken
            $total_slots = 0;
            $open_slots = 0;
            foreach ($tag_dienste as $dienst) {
                if (empty($dienst->slots)) {
                    $open_slots++;
                } else {
                    foreach ($dienst->slots as $slot) {
                        $total_slots++;
                        if (empty($slot->mitarbeiter_id)) {
                            $open_slots++;
                        }
                    }
                }
            }
        ?>
            <div class="dp-calendar-day">
                <div class="dp-calendar-day-header">
                    <div class="dp-calendar-day-number">
                        <?php echo date('d', strtotime($tag->tag_datum)); ?>
                    </div>
                    <div class="dp-calendar-day-info">
                        <div class="dp-calendar-weekday"><?php echo date_i18n('D', strtotime($tag->tag_datum)); ?></div>
                        <div class="dp-calendar-month"><?php echo date_i18n('M', strtotime($tag->tag_datum)); ?></div>
                    </div>
                </div>
                
                <div class="dp-calendar-day-stats">
                    <span class="dp-calendar-stat">
                        <strong><?php echo count($tag_dienste); ?></strong> Dienste
                    </span>
                    <?php if ($open_slots > 0): ?>
                        <span class="dp-calendar-stat open">
                            <strong><?php echo $open_slots; ?></strong> frei
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($tag_dienste)): ?>
                    <div class="dp-calendar-day-content">
                        <?php foreach (array_slice($tag_dienste, 0, 3) as $dienst): ?>
                            <div class="dp-calendar-dienst">
                                <span class="dp-calendar-bereich" style="background-color: <?php echo esc_attr($dienst->bereich_farbe ?? '#3b82f6'); ?>;"></span>
                                <div class="dp-calendar-dienst-info">
                                    <div class="dp-calendar-dienst-name"><?php echo esc_html($dienst->taetigkeit_name); ?></div>
                                    <div class="dp-calendar-dienst-time">
                                        <?php echo date('H:i', strtotime($dienst->von_zeit)); ?> - <?php echo date('H:i', strtotime($dienst->bis_zeit)); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($tag_dienste) > 3): ?>
                            <div class="dp-calendar-more">
                                +<?php echo count($tag_dienste) - 3; ?> weitere
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button class="dp-calendar-day-btn" onclick="showTagDetails(<?php echo $tag->id; ?>)">
                        Details anzeigen
                    </button>
                <?php else: ?>
                    <div class="dp-calendar-empty">
                        Keine Dienste
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Tag-Details Modal -->
<div id="dpTagDetailsModal" class="dp-modal" style="display: none;">
    <div class="dp-modal-overlay" onclick="closeTagDetails()"></div>
    <div class="dp-modal-dialog" style="max-width: 800px;">
        <div class="dp-modal-header">
            <h3 id="tagDetailsTitle"></h3>
            <button class="dp-modal-close" onclick="closeTagDetails()">×</button>
        </div>
        <div class="dp-modal-body" id="tagDetailsContent">
            <!-- Wird via JavaScript gefüllt -->
        </div>
    </div>
</div>

<style>
.dp-calendar-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.dp-calendar-day {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    padding: 1.5rem;
    transition: all 0.3s;
}

.dp-calendar-day:hover {
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.dp-calendar-day-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e5e7eb;
}

.dp-calendar-day-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2563eb;
    line-height: 1;
}

.dp-calendar-day-info {
    display: flex;
    flex-direction: column;
}

.dp-calendar-weekday {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
}

.dp-calendar-month {
    font-size: 0.875rem;
    color: #6b7280;
}

.dp-calendar-day-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

.dp-calendar-stat strong {
    color: #111827;
}

.dp-calendar-stat.open strong {
    color: #10b981;
}

.dp-calendar-day-content {
    margin-bottom: 1rem;
}

.dp-calendar-dienst {
    display: flex;
    align-items: start;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
}

.dp-calendar-bereich {
    width: 4px;
    height: 100%;
    border-radius: 2px;
    flex-shrink: 0;
    min-height: 2rem;
}

.dp-calendar-dienst-info {
    flex: 1;
    min-width: 0;
}

.dp-calendar-dienst-name {
    font-weight: 600;
    color: #111827;
    font-size: 0.875rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dp-calendar-dienst-time {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.dp-calendar-more {
    text-align: center;
    color: #6b7280;
    font-size: 0.875rem;
    padding: 0.5rem;
}

.dp-calendar-day-btn {
    width: 100%;
    padding: 0.75rem;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.dp-calendar-day-btn:hover {
    background: #1d4ed8;
}

.dp-calendar-empty {
    text-align: center;
    padding: 2rem 1rem;
    color: #9ca3af;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .dp-calendar-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
const tageDaten = <?php echo json_encode($kalender_tage); ?>;

function showTagDetails(tagId) {
    // Finde Tag-Daten
    const tagData = tageDaten.find(t => t.tag.id == tagId);
    if (!tagData) return;
    
    const tag = tagData.tag;
    const dienste = tagData.dienste;
    
    // Setze Titel
    document.getElementById('tagDetailsTitle').textContent = 
        `Tag ${tag.tag_nummer} - ${new Date(tag.tag_datum).toLocaleDateString('de-DE', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}`;
    
    // Baue Dienste-Liste
    let html = '<div class="dp-dienste-list">';
    
    if (dienste.length === 0) {
        html += '<p style="text-align: center; color: #6b7280; padding: 2rem;">Keine Dienste an diesem Tag.</p>';
    } else {
        dienste.forEach(dienst => {
            html += `
                <div class="dp-dienst-compact">
                    <div class="dp-dienst-main">
                        <div class="dp-dienst-left">
                            <span class="dp-bereich-tag" style="background-color: ${dienst.bereich_farbe || '#3b82f6'};">
                                ${dienst.bereich_name || 'Kein Bereich'}
                            </span>
                            <h3 class="dp-dienst-name">${dienst.taetigkeit_name || 'Keine Tätigkeit'}</h3>
                        </div>
                        <div class="dp-dienst-time">
                            🕐 ${formatTime(dienst.von_zeit)} - ${formatTime(dienst.bis_zeit)}
                        </div>
                    </div>
                    
                    ${dienst.besonderheiten ? `<div class="dp-dienst-note">ℹ️ ${dienst.besonderheiten}</div>` : ''}
                    
                    <div class="dp-slots-compact">
            `;
            
            if (!dienst.slots || dienst.slots.length === 0) {
                html += `
                    <button class="dp-slot-btn-compact available" 
                            onclick="closeTagDetails(); openEintragungsModal(null, ${dienst.id}, '${dienst.taetigkeit_name}', '${formatTime(dienst.von_zeit)}', '${formatTime(dienst.bis_zeit)}')">
                        <span class="dp-slot-icon">+</span>
                        <span>Jetzt eintragen</span>
                    </button>
                `;
            } else {
                dienst.slots.forEach(slot => {
                    if (slot.mitarbeiter_id) {
                        html += `
                            <div class="dp-slot-btn-compact taken">
                                <span class="dp-slot-icon">✓</span>
                                <span>Vergeben</span>
                            </div>
                        `;
                    } else {
                        html += `
                            <button class="dp-slot-btn-compact available" 
                                    onclick="closeTagDetails(); openEintragungsModal(${slot.id}, ${dienst.id}, '${dienst.taetigkeit_name}', '${formatTime(dienst.von_zeit)}', '${formatTime(dienst.bis_zeit)}')">
                                <span class="dp-slot-icon">+</span>
                                <span>Frei</span>
                            </button>
                        `;
                    }
                });
            }
            
            html += `
                    </div>
                </div>
            `;
        });
    }
    
    html += '</div>';
    
    document.getElementById('tagDetailsContent').innerHTML = html;
    document.getElementById('dpTagDetailsModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeTagDetails() {
    document.getElementById('dpTagDetailsModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function formatTime(time) {
    if (!time) return '';
    const parts = time.split(':');
    return `${parts[0]}:${parts[1]}`;
}
</script>

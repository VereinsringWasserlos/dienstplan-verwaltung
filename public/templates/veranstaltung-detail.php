<?php
/**
 * Template: Veranstaltungs-Details mit Slot-Auswahl
 * Zeigt alle Dienste/Slots einer Veranstaltung - Xoyondo-Style
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/public/templates
 */

if (!defined('ABSPATH')) exit;

require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
$db = new Dienstplan_Database(DIENSTPLAN_DB_PREFIX);

// Hole Veranstaltungs-ID
$veranstaltung_id = isset($_GET['veranstaltung_id']) ? intval($_GET['veranstaltung_id']) : $atts['veranstaltung_id'];

if (!$veranstaltung_id) {
    echo '<p>Keine Veranstaltung ausgewählt.</p>';
    return;
}

$veranstaltung = $db->get_veranstaltung($veranstaltung_id);
if (!$veranstaltung) {
    echo '<p>Veranstaltung nicht gefunden.</p>';
    return;
}

// Lade Vereine für diese Veranstaltung
// 1. Versuch: Aus veranstaltung_vereine Tabelle (explizit zugewiesene Vereine)
$vereine = $db->get_veranstaltung_vereine($veranstaltung_id);

// 2. Versuch: Falls keine Vereine zugewiesen - Hole alle Vereine, die Dienste haben
if (empty($vereine)) {
    global $wpdb;
    $prefix = DIENSTPLAN_DB_PREFIX;
    $vereine = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT
            v.id as verein_id,
            v.name as verein_name,
            v.kuerzel as verein_kuerzel,
            v.aktiv
        FROM {$prefix}vereine v
        INNER JOIN {$prefix}dienste d ON v.id = d.verein_id
        WHERE d.veranstaltung_id = %d
        AND v.aktiv = 1
        ORDER BY v.name ASC",
        $veranstaltung_id
    ));
}

// 3. Versuch: Falls immer noch keine Vereine - Zeige alle aktiven Vereine
if (empty($vereine)) {
    $vereine = $wpdb->get_results(
        "SELECT 
            id as verein_id,
            name as verein_name,
            kuerzel as verein_kuerzel,
            aktiv
        FROM {$prefix}vereine
        WHERE aktiv = 1
        ORDER BY name ASC"
    );
}

// Hole ausgewählten Verein (aus GET-Parameter)
$selected_verein_id = isset($_GET['verein_id']) ? intval($_GET['verein_id']) : null;

// Lade Veranstaltungstage
$tage = $db->get_veranstaltung_tage($veranstaltung_id);

// Wenn kein Verein ausgewählt wurde und Vereine vorhanden sind, zeige Auswahl
if (!$selected_verein_id && !empty($vereine)) {
    // Verein-Auswahl anzeigen
    $show_verein_selection = true;
    $alle_dienste = array();
} else {
    // Lade Dienste (optional gefiltert nach Verein)
    $alle_dienste = $db->get_dienste($veranstaltung_id, $selected_verein_id);
    $show_verein_selection = false;
}

// Gruppiere Dienste nach Tagen (tag_id)
$dienste_nach_tagen = array();
foreach ($alle_dienste as $dienst) {
    if (!isset($dienst->tag_id) || empty($dienst->tag_id)) {
        continue; // Überspringe Dienste ohne Tag
    }
    
    $tag_key = $dienst->tag_id;
    
    if (!isset($dienste_nach_tagen[$tag_key])) {
        $dienste_nach_tagen[$tag_key] = array();
    }
    
    // Füge Slots hinzu
    $dienst->slots = $db->get_dienst_slots($dienst->id);
    $dienste_nach_tagen[$tag_key][] = $dienst;
}
?>

<div class="dp-public-container dp-detail-view">
    <?php if ($show_verein_selection): ?>
        <!-- Verein-Auswahl Modal -->
        <div id="dpVereinSelectionModal" class="dp-modal" style="display: flex;">
            <div class="dp-modal-overlay" onclick="closeVereinModal()"></div>
            <div class="dp-modal-content">
                <button class="dp-modal-close" onclick="closeVereinModal()">×</button>
                <h2 class="dp-section-title">Für welchen Verein möchten Sie einen Dienst übernehmen?</h2>
                <p class="dp-section-subtitle">Wählen Sie Ihren Verein aus, um die verfügbaren Dienste zu sehen.</p>
                
                <div class="dp-verein-grid">
                    <?php foreach ($vereine as $verein): ?>
                        <div class="dp-verein-card" onclick="selectVerein(<?php echo $verein->verein_id; ?>)">
                            <div class="dp-verein-header">
                                <h3 class="dp-verein-name"><?php echo esc_html($verein->verein_name); ?></h3>
                                <?php if ($verein->verein_kuerzel): ?>
                                    <span class="dp-verein-badge"><?php echo esc_html($verein->verein_kuerzel); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php 
                            // Zähle Dienste für diesen Verein
                            $verein_dienste = $db->get_dienste($veranstaltung_id, $verein->verein_id);
                            $total_slots = 0;
                            $open_slots = 0;
                            foreach ($verein_dienste as $dienst) {
                                $slots = $db->get_dienst_slots($dienst->id);
                                $total_slots += count($slots);
                                foreach ($slots as $slot) {
                                    if (!$slot->mitarbeiter_id) {
                                        $open_slots++;
                                    }
                                }
                            }
                            ?>
                            
                            <div class="dp-verein-stats">
                                <div class="dp-stat-item">
                                    <span class="dp-stat-value"><?php echo count($verein_dienste); ?></span>
                                    <span class="dp-stat-label">Dienste</span>
                                </div>
                                <div class="dp-stat-item">
                                    <span class="dp-stat-value"><?php echo $open_slots; ?></span>
                                    <span class="dp-stat-label">Freie Plätze</span>
                                </div>
                            </div>
                            
                            <div class="dp-verein-action">
                                <span class="dp-btn-text">Dienste ansehen →</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <script>
        function selectVerein(vereinId) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('verein_id', vereinId);
            window.location.href = currentUrl.toString();
        }
        
        function closeVereinModal() {
            // Optional: Falls User ohne Auswahl schließen will
            // document.getElementById('dpVereinSelectionModal').style.display = 'none';
        }
        
        // ESC-Taste zum Schließen
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeVereinModal();
            }
        });
        </script>
    <?php else: ?>
        <!-- Ausgewählter Verein anzeigen -->
        <?php if ($selected_verein_id): ?>
            <?php 
            $selected_verein = null;
            foreach ($vereine as $v) {
                if ($v->verein_id == $selected_verein_id) {
                    $selected_verein = $v;
                    break;
                }
            }
            ?>
            <?php if ($selected_verein): ?>
                <div class="dp-selected-verein">
                    <div class="dp-selected-verein-content">
                        <span class="dp-selected-label">Dienste für:</span>
                        <strong class="dp-selected-name"><?php echo esc_html($selected_verein->verein_name); ?></strong>
                    </div>
                    <a href="?veranstaltung_id=<?php echo $veranstaltung_id; ?>" class="dp-change-verein">
                        Verein wechseln
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    
    <!-- Tage und Dienste -->
    <?php if (empty($dienste_nach_tagen)): ?>
        <div class="dp-empty-state">
            <span class="dp-empty-icon">📋</span>
            <h3>Noch keine Dienste angelegt</h3>
            <p>Für diese Veranstaltung wurden noch keine Dienste erstellt.</p>
        </div>
    <?php else: ?>
        <?php foreach ($dienste_nach_tagen as $tag_id => $tag_dienste): 
            // Hole Tag-Informationen
            $tag = $db->get_veranstaltung_tag($tag_id);
            if (!$tag) continue;
        ?>
            <div class="dp-day-section">
                <h2 class="dp-day-header">
                    <span class="dp-day-badge">Tag <?php echo $tag->tag_nummer; ?></span>
                    <span class="dp-day-date"><?php echo date_i18n('l, d. F Y', strtotime($tag->tag_datum)); ?></span>
                    <?php if ($tag->von_zeit && $tag->bis_zeit): ?>
                        <span class="dp-day-time"><?php echo date('H:i', strtotime($tag->von_zeit)); ?> - <?php echo date('H:i', strtotime($tag->bis_zeit)); ?> Uhr</span>
                    <?php endif; ?>
                </h2>
                
                <div class="dp-dienste-grid">
                    <?php foreach ($tag_dienste as $dienst): ?>
                        <div class="dp-dienst-card">
                            <div class="dp-dienst-header">
                                <div class="dp-dienst-info">
                                    <span class="dp-bereich-badge" style="background-color: <?php echo esc_attr($dienst->bereich_farbe ?? '#3b82f6'); ?>;">
                                        <?php echo esc_html($dienst->bereich_name ?? 'Kein Bereich'); ?>
                                    </span>
                                    <h3 class="dp-dienst-title"><?php echo esc_html($dienst->taetigkeit_name ?? 'Keine Tätigkeit'); ?></h3>
                                </div>
                                <div class="dp-dienst-meta">
                                    <span class="dp-time-badge">
                                        🕐 <?php echo date('H:i', strtotime($dienst->von_zeit)); ?> - <?php echo date('H:i', strtotime($dienst->bis_zeit)); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="dp-verein-badge">
                                <span class="dp-icon">👥</span>
                                <?php echo esc_html($dienst->verein_name); ?>
                            </div>
                            
                            <?php if ($dienst->besonderheiten): ?>
                                <div class="dp-dienst-notes">
                                    <span class="dp-icon">ℹ️</span>
                                    <?php echo esc_html($dienst->besonderheiten); ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Slots -->
                            <div class="dp-slots-container">
                                <?php if (empty($dienst->slots)): ?>
                                    <div class="dp-slot dp-slot-available">
                                        <div class="dp-slot-header">
                                            <span class="dp-slot-number">Slot #1</span>
                                        </div>
                                        <button class="dp-slot-btn" onclick="openEintragungsModal(<?php echo $dienst->id; ?>, '<?php echo esc_js($dienst->taetigkeit_name); ?>', '<?php echo esc_js(date('H:i', strtotime($dienst->von_zeit))); ?>', '<?php echo esc_js(date('H:i', strtotime($dienst->bis_zeit))); ?>')">
                                            <span class="dp-slot-icon">+</span>
                                            <span class="dp-slot-text">Jetzt eintragen</span>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($dienst->slots as $slot): 
                                        $ist_vergeben = !empty($slot->mitarbeiter_id);
                                        $mitarbeiter = $ist_vergeben ? $db->get_mitarbeiter($slot->mitarbeiter_id) : null;
                                    ?>
                                        <div class="dp-slot <?php echo $ist_vergeben ? 'dp-slot-taken' : 'dp-slot-available'; ?>">
                                            <div class="dp-slot-header">
                                                <span class="dp-slot-number">Slot #<?php echo $slot->slot_nummer; ?></span>
                                                <?php if ($slot->von_zeit): ?>
                                                    <span class="dp-slot-time">
                                                        <?php echo date('H:i', strtotime($slot->von_zeit)); ?> - <?php echo date('H:i', strtotime($slot->bis_zeit)); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($ist_vergeben && $mitarbeiter): ?>
                                                <div class="dp-slot-content">
                                                    <span class="dp-slot-icon">✓</span>
                                                    <span class="dp-slot-name">
                                                        <?php echo esc_html($mitarbeiter->vorname . ' ' . $mitarbeiter->nachname); ?>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <button class="dp-slot-btn" onclick="openEintragungsModal(<?php echo $slot->id; ?>, '<?php echo esc_js($dienst->taetigkeit_name); ?>', '<?php echo esc_js(date('H:i', strtotime($dienst->von_zeit))); ?>', '<?php echo esc_js(date('H:i', strtotime($dienst->bis_zeit))); ?>')">
                                                    <span class="dp-slot-icon">+</span>
                                                    <span class="dp-slot-text">Jetzt eintragen</span>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php endif; // Ende else: Verein-Selection ?>
    
    <!-- Datenschutz-Hinweis -->
    <div class="dp-privacy-notice">
        <span class="dp-icon">🔒</span>
        <div>
            <strong>Datenschutz:</strong>
            Ihre Daten (Vor- und Nachname, optional E-Mail und Telefon) werden nur zur Organisation dieser Veranstaltung verwendet. 
            Die Verantwortlichen der Vereine und Veranstaltungen können Ihre Eintragungen einsehen.
        </div>
    </div>
</div>

<!-- Eintragungsmodal (Xoyondo-inspiriert) -->
<div id="dpEintragungsModal" class="dp-modal" style="display: none;">
    <div class="dp-modal-overlay" onclick="closeEintragungsModal()"></div>
    <div class="dp-modal-content">
        <div class="dp-modal-header">
            <h3 class="dp-modal-title">Für Dienst eintragen</h3>
            <button class="dp-modal-close" onclick="closeEintragungsModal()">×</button>
        </div>
        
        <div class="dp-modal-body">
            <div class="dp-modal-info" id="dienstInfo"></div>
            
            <form id="dpEintragungsForm" class="dp-form">
                <input type="hidden" id="slot_id" name="slot_id">
                
                <div class="dp-form-row">
                    <div class="dp-form-group">
                        <label for="vorname" class="dp-label">Vorname *</label>
                        <input type="text" id="vorname" name="vorname" class="dp-input" required placeholder="Max">
                    </div>
                    <div class="dp-form-group">
                        <label for="nachname" class="dp-label">Nachname *</label>
                        <input type="text" id="nachname" name="nachname" class="dp-input" required placeholder="Mustermann">
                    </div>
                </div>
                
                <div class="dp-form-row">
                    <div class="dp-form-group">
                        <label for="email" class="dp-label">E-Mail (optional)</label>
                        <input type="email" id="email" name="email" class="dp-input" placeholder="max@example.com">
                        <small class="dp-help-text">Für Rückfragen und Erinnerungen</small>
                    </div>
                    <div class="dp-form-group">
                        <label for="telefon" class="dp-label">Telefon (optional)</label>
                        <input type="tel" id="telefon" name="telefon" class="dp-input" placeholder="+49 123 456789">
                    </div>
                </div>
                
                <div class="dp-form-group">
                    <label class="dp-checkbox-label">
                        <input type="checkbox" id="datenschutz" name="datenschutz" value="1" required>
                        <span>Ich akzeptiere, dass meine Daten zur Organisation dieser Veranstaltung verwendet werden *</span>
                    </label>
                </div>
                
                <div class="dp-form-actions">
                    <button type="button" class="dp-btn dp-btn-secondary" onclick="closeEintragungsModal()">Abbrechen</button>
                    <button type="submit" class="dp-btn dp-btn-primary">
                        <span id="submitText">Eintragen</span>
                        <span id="submitLoader" class="dp-loader" style="display: none;"></span>
                    </button>
                </div>
            </form>
            
            <div id="dpSuccessMessage" class="dp-success-message" style="display: none;">
                <span class="dp-success-icon">✓</span>
                <h4>Erfolgreich eingetragen!</h4>
                <p>Sie wurden für den Dienst eingetragen. Die Verantwortlichen wurden benachrichtigt.</p>
                <button class="dp-btn dp-btn-primary" onclick="location.reload()">Weitere Dienste ansehen</button>
            </div>
            
            <div id="dpErrorMessage" class="dp-error-message" style="display: none;">
                <span class="dp-error-icon">⚠️</span>
                <p id="errorText"></p>
                <button class="dp-btn dp-btn-secondary" onclick="hideError()">Erneut versuchen</button>
            </div>
        </div>
    </div>
</div>

<script>
// AJAX URL für WordPress - nur definieren falls noch nicht vorhanden
if (typeof dpPublic === 'undefined') {
    var dpPublic = {
        ajaxurl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
        nonce: '<?php echo wp_create_nonce('dp_public_nonce'); ?>'
    };
}

console.log('dpPublic konfiguriert:', dpPublic);

function openEintragungsModal(slotId, taetigkeit, vonZeit, bisZeit) {
    document.getElementById('slot_id').value = slotId;
    document.getElementById('dienstInfo').innerHTML = `
        <div class="dp-info-card">
            <h4>${taetigkeit}</h4>
            <p>🕐 ${vonZeit} - ${bisZeit} Uhr</p>
        </div>
    `;
    document.getElementById('dpEintragungsModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeEintragungsModal() {
    document.getElementById('dpEintragungsModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('dpEintragungsForm').reset();
    document.getElementById('dpSuccessMessage').style.display = 'none';
    document.getElementById('dpErrorMessage').style.display = 'none';
}

function hideError() {
    document.getElementById('dpErrorMessage').style.display = 'none';
}

// Form-Submit Handler
document.getElementById('dpEintragungsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    console.log('=== FORM SUBMIT START ===');
    console.log('dpPublic:', dpPublic);
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const submitText = document.getElementById('submitText');
    const submitLoader = document.getElementById('submitLoader');
    
    // Zeige Loader
    submitBtn.disabled = true;
    submitText.style.display = 'none';
    submitLoader.style.display = 'inline-block';
    
    const formData = new FormData(this);
    formData.append('action', 'dp_assign_slot');
    
    // Debug: Zeige alle FormData-Einträge
    console.log('FormData Einträge:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    console.log('Sende AJAX Request an:', dpPublic.ajaxurl);
    
    fetch(dpPublic.ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response Status:', response.status);
        console.log('Response OK:', response.ok);
        
        // Prüfe zuerst den Response-Text
        return response.text().then(text => {
            console.log('Response Text (ersten 500 Zeichen):', text.substring(0, 500));
            try {
                const data = JSON.parse(text);
                console.log('Geparste Daten:', data);
                return data;
            } catch (e) {
                console.error('JSON Parse Error:', e);
                console.error('Vollständige Response:', text);
                throw new Error('Server hat kein gültiges JSON zurückgegeben');
            }
        });
    })
    .then(data => {
        console.log('=== RESPONSE ERFOLGREICH ===');
        console.log('Success:', data.success);
        console.log('Data:', data.data);
        
        submitBtn.disabled = false;
        submitText.style.display = 'inline';
        submitLoader.style.display = 'none';
        
        if (data.success) {
            document.getElementById('dpEintragungsForm').style.display = 'none';
            document.getElementById('dpSuccessMessage').style.display = 'block';
        } else {
            document.getElementById('errorText').textContent = data.data.message || 'Ein Fehler ist aufgetreten';
            document.getElementById('dpErrorMessage').style.display = 'block';
        }
    })
    .catch(error => {
        console.error('=== AJAX FEHLER ===');
        console.error('Error:', error);
        
        submitBtn.disabled = false;
        submitText.style.display = 'inline';
        submitLoader.style.display = 'none';
        
        document.getElementById('errorText').textContent = error.message || 'Netzwerkfehler. Bitte versuchen Sie es erneut.';
        document.getElementById('dpErrorMessage').style.display = 'block';
    });
});
</script>

<?php
get_footer();
?>

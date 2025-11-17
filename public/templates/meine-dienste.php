<?php
/**
 * Template: Meine Dienste
 * Zeigt die eingetragenen Dienste eines Mitarbeiters
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/public/templates
 * 
 * Verfügbare Variablen:
 * @var object $mitarbeiter - Mitarbeiter-Objekt
 * @var array  $dienste     - Array mit Dienst-Objekten
 */

if (!defined('ABSPATH')) exit;
?>

<div class="dp-public-container dp-meine-dienste">
    <div class="dp-header">
        <h2 class="dp-title">
            <span class="dp-icon">👤</span>
            Meine Dienste
        </h2>
        <p class="dp-subtitle">
            Hallo <?php echo esc_html($mitarbeiter->vorname . ' ' . $mitarbeiter->nachname); ?>!
            <?php if ($mitarbeiter->email): ?>
                <span class="dp-email-badge"><?php echo esc_html($mitarbeiter->email); ?></span>
            <?php endif; ?>
        </p>
    </div>
    
    <?php if (empty($dienste)): ?>
        <div class="dp-empty-state">
            <span class="dp-empty-icon">📋</span>
            <h3>Noch keine Dienste eingetragen</h3>
            <p>Sie haben sich noch für keinen Dienst eingetragen.</p>
            <a href="?" class="dp-btn dp-btn-primary">Zu den Veranstaltungen</a>
        </div>
    <?php else: ?>
        <div class="dp-dienste-timeline">
            <?php 
            // Gruppiere nach Veranstaltung
            $nach_veranstaltung = array();
            foreach ($dienste as $dienst) {
                $vid = $dienst->veranstaltung_id;
                if (!isset($nach_veranstaltung[$vid])) {
                    $nach_veranstaltung[$vid] = array(
                        'name' => $dienst->veranstaltung_name,
                        'dienste' => array()
                    );
                }
                $nach_veranstaltung[$vid]['dienste'][] = $dienst;
            }
            
            foreach ($nach_veranstaltung as $vid => $gruppe):
            ?>
                <div class="dp-veranstaltung-group">
                    <h3 class="dp-veranstaltung-title">
                        <span class="dp-icon">📅</span>
                        <?php echo esc_html($gruppe['name']); ?>
                    </h3>
                    
                    <div class="dp-dienste-list">
                        <?php foreach ($gruppe['dienste'] as $dienst): ?>
                            <div class="dp-dienst-item">
                                <div class="dp-dienst-main">
                                    <div class="dp-dienst-badge" style="background-color: <?php echo esc_attr($dienst->bereich_farbe ?? '#3b82f6'); ?>">
                                        <?php echo esc_html($dienst->bereich_name); ?>
                                    </div>
                                    <div class="dp-dienst-content">
                                        <h4 class="dp-dienst-name"><?php echo esc_html($dienst->taetigkeit_name); ?></h4>
                                        <div class="dp-dienst-details">
                                            <span class="dp-detail-item">
                                                <span class="dp-icon">📅</span>
                                                <?php echo date_i18n('d.m.Y', strtotime($dienst->tag_datum)); ?>
                                            </span>
                                            <span class="dp-detail-item">
                                                <span class="dp-icon">🕐</span>
                                                <?php echo date('H:i', strtotime($dienst->von_zeit)); ?> - <?php echo date('H:i', strtotime($dienst->bis_zeit)); ?> Uhr
                                            </span>
                                            <span class="dp-detail-item">
                                                <span class="dp-icon">👥</span>
                                                <?php echo esc_html($dienst->verein_name); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="dp-dienst-actions">
                                    <button class="dp-btn-icon dp-btn-danger" 
                                            onclick="removeDienst(<?php echo $dienst->id; ?>)"
                                            title="Austragen">
                                        <span class="dp-icon">🗑️</span>
                                        Austragen
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="dp-actions-footer">
            <a href="?" class="dp-btn dp-btn-primary">
                <span class="dp-icon">+</span>
                Weitere Dienste hinzufügen
            </a>
        </div>
    <?php endif; ?>
    
    <!-- Kontakt-Info -->
    <div class="dp-contact-box">
        <h4>Fragen oder Änderungen?</h4>
        <p>Bei Fragen oder Problemen wenden Sie sich bitte an die Verantwortlichen der jeweiligen Veranstaltung.</p>
    </div>
</div>

<!-- Austragungsbestätigung -->
<div id="dpRemoveModal" class="dp-modal" style="display: none;">
    <div class="dp-modal-overlay" onclick="closeRemoveModal()"></div>
    <div class="dp-modal-content dp-modal-small">
        <div class="dp-modal-header">
            <h3 class="dp-modal-title">Austragen bestätigen</h3>
            <button class="dp-modal-close" onclick="closeRemoveModal()">×</button>
        </div>
        <div class="dp-modal-body">
            <p>Möchten Sie sich wirklich von diesem Dienst austragen?</p>
            <div class="dp-form-actions">
                <button class="dp-btn dp-btn-secondary" onclick="closeRemoveModal()">Abbrechen</button>
                <button class="dp-btn dp-btn-danger" id="confirmRemove">Ja, austragen</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentRemoveSlotId = null;

function removeDienst(slotId) {
    currentRemoveSlotId = slotId;
    document.getElementById('dpRemoveModal').style.display = 'flex';
}

function closeRemoveModal() {
    document.getElementById('dpRemoveModal').style.display = 'none';
    currentRemoveSlotId = null;
}

document.getElementById('confirmRemove').addEventListener('click', function() {
    if (!currentRemoveSlotId) return;
    
    this.disabled = true;
    this.textContent = 'Wird entfernt...';
    
    const formData = new FormData();
    formData.append('action', 'dp_remove_assignment');
    formData.append('slot_id', currentRemoveSlotId);
    
    fetch(dpPublic.ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.data.message || 'Fehler beim Austragen');
            this.disabled = false;
            this.textContent = 'Ja, austragen';
        }
    })
    .catch(error => {
        alert('Netzwerkfehler. Bitte versuchen Sie es erneut.');
        this.disabled = false;
        this.textContent = 'Ja, austragen';
    });
});
</script>

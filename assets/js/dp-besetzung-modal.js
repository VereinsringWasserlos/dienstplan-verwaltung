/**
 * Dienstplan Verwaltung - Besetzungs-Modal JavaScript
 * Verwaltet die Slot-Besetzung und Mitarbeiter-Zuweisungen
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('Besetzung Modal geladen');
        
        // ESC-Taste zum Schließen
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#besetzung-modal').is(':visible')) {
                window.closeBesetzungModal();
            }
        });
    });

    // ========================================
    // Globale Funktionen
    // ========================================

    /**
     * Öffnet das Besetzungs-Modal für einen Dienst
     */
    window.editBesetzung = function(dienstId) {
        console.log('editBesetzung', dienstId);
        $('#besetzung_dienst_id').val(dienstId);
        $('#besetzung-slots-container').html('<p style="text-align: center; color: #666;"><span class="spinner is-active" style="float: none; margin: 0;"></span> Lade Slots...</p>');
        $('#besetzung-modal').css('display', 'flex');
        
        // AJAX: Dienst + Slots laden
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_get_dienst_besetzung',
                nonce: dpAjax.nonce,
                dienst_id: dienstId
            },
            success: function(response) {
                console.log('Besetzung Response:', response);
                if (response.success && response.data) {
                    renderBesetzung(response.data);
                } else {
                    $('#besetzung-slots-container').html('<div class="notice notice-error inline"><p>Fehler beim Laden: ' + (response.data ? response.data.message : 'Unbekannt') + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Fehler:', error);
                $('#besetzung-slots-container').html('<div class="notice notice-error inline"><p>AJAX-Fehler: ' + error + '</p></div>');
            }
        });
    };

    /**
     * Schließt das Besetzungs-Modal
     */
    window.closeBesetzungModal = function() {
        $('#besetzung-modal').hide();
        if(typeof dpCheckPendingReload === 'function') {
            dpCheckPendingReload();
        }
    };

    /**
     * Zeigt Mitarbeiter-Details in einem Popup
     */
    window.showMitarbeiterPopup = function(name, info) {
        const popup = $('<div class="mitarbeiter-popup"></div>')
            .css({
                position: 'fixed',
                top: '50%',
                left: '50%',
                transform: 'translate(-50%, -50%)',
                background: '#fff',
                border: '2px solid #2271b1',
                borderRadius: '8px',
                padding: '1.5rem',
                boxShadow: '0 4px 12px rgba(0,0,0,0.3)',
                zIndex: 100001,
                minWidth: '300px',
                maxWidth: '400px'
            })
            .html(
                '<div style="margin-bottom: 1rem;">' +
                '<div style="font-size: 1.1rem; font-weight: 600; color: #059669; margin-bottom: 0.5rem;">' +
                '<span class="dashicons dashicons-admin-users" style="font-size: 1.2rem; margin-right: 0.5rem;"></span>' +
                name +
                '</div>' +
                '<div style="font-size: 0.9rem; color: #6b7280;">' + info + '</div>' +
                '</div>' +
                '<button type="button" class="button button-primary" onclick="jQuery(\'.mitarbeiter-popup, .popup-overlay\').remove()" style="width: 100%;">Schließen</button>'
            );
        
        const overlay = $('<div class="popup-overlay"></div>')
            .css({
                position: 'fixed',
                top: 0,
                left: 0,
                right: 0,
                bottom: 0,
                background: 'rgba(0,0,0,0.5)',
                zIndex: 100000
            })
            .on('click', function() {
                popup.remove();
                overlay.remove();
            });
        
        $('body').append(overlay).append(popup);
    };

    /**
     * Weist einen Slot einem Mitarbeiter zu
     */
    window.assignSlot = function(slotId, isReplacement) {
        const mitarbeiterId = $('#slot-' + slotId + '-mitarbeiter').val();
        
        if (!mitarbeiterId) {
            alert('Bitte wählen Sie einen Mitarbeiter aus!');
            return;
        }
        
        // Bei Ersetzung Bestätigung verlangen
        if (isReplacement) {
            if (!confirm('Möchten Sie den aktuellen Mitarbeiter wirklich durch einen anderen ersetzen?')) {
                return;
            }
        }
        
        console.log('assignSlot', slotId, mitarbeiterId, 'isReplacement:', isReplacement);
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_admin_assign_slot',
                nonce: dpAjax.nonce,
                slot_id: slotId,
                mitarbeiter_id: mitarbeiterId,
                force_replace: isReplacement ? 1 : 0
            },
            success: function(response) {
                console.log('Assign Response:', response);
                if (response.success) {
                    // Reload Besetzung
                    const dienstId = $('#besetzung_dienst_id').val();
                    window.editBesetzung(dienstId);
                } else {
                    alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Fehler:', error);
                alert('AJAX-Fehler: ' + error);
            }
        });
    };

    /**
     * Entfernt eine Slot-Zuweisung
     */
    window.removeSlotAssignment = function(slotId) {
        if (!confirm('Möchten Sie diese Zuweisung wirklich entfernen?')) {
            return;
        }
        
        console.log('removeSlotAssignment', slotId);
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_admin_remove_slot',
                nonce: dpAjax.nonce,
                slot_id: slotId
            },
            success: function(response) {
                console.log('Remove Response:', response);
                if (response.success) {
                    // Reload Besetzung
                    const dienstId = $('#besetzung_dienst_id').val();
                    window.editBesetzung(dienstId);
                } else {
                    alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Fehler:', error);
                alert('AJAX-Fehler: ' + error);
            }
        });
    };

    /**
     * Öffnet das Formular für neuen Mitarbeiter
     */
    window.openNeuerMitarbeiterForm = function() {
        $('#neuer-mitarbeiter-form').slideDown(200);
        $('#new_mitarbeiter_vorname').focus();
    };

    /**
     * Schließt das Formular für neuen Mitarbeiter
     */
    window.closeNeuerMitarbeiterForm = function() {
        $('#neuer-mitarbeiter-form').slideUp(200);
        // Formular zurücksetzen
        $('#new_mitarbeiter_vorname').val('');
        $('#new_mitarbeiter_nachname').val('');
        $('#new_mitarbeiter_email').val('');
        $('#new_mitarbeiter_telefon').val('');
    };

    /**
     * Speichert einen neuen Mitarbeiter
     */
    window.saveNeuerMitarbeiter = function() {
        const vorname = $('#new_mitarbeiter_vorname').val().trim();
        const nachname = $('#new_mitarbeiter_nachname').val().trim();
        const email = $('#new_mitarbeiter_email').val().trim();
        const telefon = $('#new_mitarbeiter_telefon').val().trim();
        
        // Validierung nur für Pflichtfelder
        if (!vorname || !nachname) {
            alert('Bitte füllen Sie alle Pflichtfelder aus (Vorname, Nachname)!');
            return;
        }
        
        // Email-Format prüfen (nur wenn angegeben)
        if (email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Bitte geben Sie eine gültige E-Mail-Adresse ein!');
                return;
            }
        }
        
        console.log('saveNeuerMitarbeiter', {vorname, nachname, email, telefon});
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_add_mitarbeiter',
                nonce: dpAjax.nonce,
                vorname: vorname,
                nachname: nachname,
                email: email,
                telefon: telefon
            },
            success: function(response) {
                console.log('Add Mitarbeiter Response:', response);
                if (response.success) {
                    const newMitarbeiterId = response.data.mitarbeiter_id;
                    alert('Mitarbeiter erfolgreich angelegt!');
                    window.closeNeuerMitarbeiterForm();
                    
                    // Reload Besetzung und wähle neuen Mitarbeiter vor
                    const dienstId = $('#besetzung_dienst_id').val();
                    $.ajax({
                        url: dpAjax.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'dp_get_dienst_besetzung',
                            nonce: dpAjax.nonce,
                            dienst_id: dienstId
                        },
                        success: function(resp) {
                            if (resp.success) {
                                renderBesetzung(resp.data);
                                // Wähle neuen Mitarbeiter im ersten freien Slot aus
                                setTimeout(function() {
                                    const firstFreeSlotSelect = $('.slot-badge-frei').first().closest('.slot-card').find('select[id^="slot-"]');
                                    if (firstFreeSlotSelect.length > 0) {
                                        firstFreeSlotSelect.val(newMitarbeiterId).focus();
                                        firstFreeSlotSelect.css('border', '2px solid #2271b1');
                                    }
                                }, 100);
                            }
                        }
                    });
                } else {
                    alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Fehler:', error);
                alert('AJAX-Fehler: ' + error);
            }
        });
    };

    // ========================================
    // Private Funktionen
    // ========================================

    /**
     * Rendert die Besetzungsansicht mit allen Slots
     */
    function renderBesetzung(data) {
        const dienst = data.dienst;
        const slots = data.slots;
        const mitarbeiter = data.mitarbeiter;
        
        // Dienst-Info
        $('#info-taetigkeit').text(dienst.taetigkeit_name);
        $('#info-bereich').text(dienst.bereich_name);
        $('#info-zeit').text(dienst.von_zeit.substring(0, 5) + ' - ' + dienst.bis_zeit.substring(0, 5) + ' Uhr');
        $('#info-verein').text(dienst.verein_name);
        
        // Slots
        if (!slots || slots.length === 0) {
            $('#besetzung-slots-container').html('<div class="notice notice-warning inline"><p>⚠️ Keine Slots vorhanden! Bitte erst Slots erstellen.</p></div>');
            return;
        }
        
        let html = '';
        slots.forEach(function(slot, index) {
            const isBesetzt = slot.mitarbeiter_id && slot.mitarbeiter_id > 0;
            const slotNummerText = 'S' + (index + 1);
            
            html += '<div class="slot-card">';
            html += '<div class="slot-card-header">';
            html += '<div class="slot-card-title">';
            html += '<code style="background: #f3f4f6; padding: 2px 6px; border-radius: 3px; font-size: 0.85rem;">#' + slot.id + '</code> ';
            html += '<span style="color: #6b7280; font-size: 0.85rem;">' + slotNummerText + '</span> ';
            html += '<span style="color: #111; font-weight: 500;">' + slot.von_zeit.substring(0, 5) + '-' + slot.bis_zeit.substring(0, 5) + '</span>';
            html += '</div>';
            
            // Status-Badge mit Tooltip bei Besetzung
            if (isBesetzt) {
                const mitarbeiterName = slot.mitarbeiter_vorname + ' ' + slot.mitarbeiter_nachname;
                const mitarbeiterInfo = slot.mitarbeiter_email + (slot.mitarbeiter_telefon ? ' | ' + slot.mitarbeiter_telefon : '');
                
                html += '<div style="display: flex; align-items: center; gap: 0.5rem;">';
                html += '<span class="slot-badge slot-badge-besetzt" style="cursor: help;" title="' + mitarbeiterName + '\n' + mitarbeiterInfo + '" onclick="showMitarbeiterPopup(\'' + mitarbeiterName + '\', \'' + mitarbeiterInfo + '\')">';
                html += '✓ Besetzt';
                html += '</span>';
                html += '<button type="button" class="button button-small" onclick="removeSlotAssignment(' + slot.id + ')" style="color: #c00; padding: 2px 8px; height: 24px; line-height: 1;">';
                html += '<span class="dashicons dashicons-no" style="font-size: 14px; width: 14px; height: 14px;"></span>';
                html += '</button>';
                html += '</div>';
            } else {
                html += '<span class="slot-badge slot-badge-frei">';
                html += '○ Frei';
                html += '</span>';
            }
            
            html += '</div>';
            
            // Zuweisungs-Form (kompakt)
            html += '<div class="slot-assign-form">';
            html += '<select id="slot-' + slot.id + '-mitarbeiter" class="regular-text" style="width: 100%; height: 32px; font-size: 13px;">';
            html += '<option value="">-- ' + (isBesetzt ? 'Anderen Mitarbeiter wählen' : 'Mitarbeiter auswählen') + ' --</option>';
            
            mitarbeiter.forEach(function(ma) {
                const isSelected = isBesetzt && ma.id == slot.mitarbeiter_id;
                html += '<option value="' + ma.id + '"' + (isSelected ? ' selected' : '') + '>' + ma.vorname + ' ' + ma.nachname + '</option>';
            });
            
            html += '</select>';
            html += '<button type="button" class="button button-primary" onclick="assignSlot(' + slot.id + ', ' + (isBesetzt ? 'true' : 'false') + ')" style="height: 32px; padding: 0 12px;">';
            html += isBesetzt ? 'Ändern' : 'Zuweisen';
            html += '</button>';
            html += '</div>';
            
            html += '</div>';
        });
        
        $('#besetzung-slots-container').html(html);
    }

})(jQuery);

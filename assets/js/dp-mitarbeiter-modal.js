(function($) {
    'use strict';

    function setSelectedVereine(vereinIds) {
        const selected = Array.isArray(vereinIds) ? vereinIds.map(String) : [];

        $('#ma_vereine option').each(function() {
            $(this).prop('selected', selected.indexOf(String($(this).val())) !== -1);
        });
    }

    function updateVereinOptions(vereine) {
        const $select = $('#ma_vereine');
        if (!$select.length || !Array.isArray(vereine)) {
            return;
        }

        $select.empty();
        vereine.forEach(function(verein) {
            if (!verein || typeof verein.id === 'undefined') {
                return;
            }
            const option = $('<option></option>')
                .val(String(verein.id))
                .text(verein.name || ('Verein #' + verein.id));
            $select.append(option);
        });
    }
    
    $(document).ready(function() {
        console.log('Mitarbeiter Modal geladen');
    });
    
    window.openMitarbeiterModal = function(id) {
        console.log('=== openMitarbeiterModal START ===');

        if (id) {
            window.editMitarbeiter(id);
            return;
        }
        
        // Form zurücksetzen mit Error-Handling
        const form = document.getElementById('mitarbeiter-form');
        if (form) {
            form.reset();
        } else {
            console.warn('Form #mitarbeiter-form nicht gefunden');
        }
        
        $('#mitarbeiter_id').val('');
        $('#mitarbeiter-modal-title').text('Neuer Mitarbeiter');
        setSelectedVereine([]);
        
        // Portal-Zugriff-Zeile verstecken bei neuem Mitarbeiter
        $('#portal-access-row').hide();
        
        // Modal anzeigen - mehrere Methoden für Kompatibilität
        const modal = document.getElementById('mitarbeiter-modal');
        if (modal) {
            modal.style.display = 'flex';
            modal.style.visibility = 'visible';
            modal.style.opacity = '1';
        }
        
        // jQuery Fallback
        $('#mitarbeiter-modal').show().css({
            'display': 'flex',
            'visibility': 'visible',
            'opacity': '1'
        });
        
        console.log('=== openMitarbeiterModal END ===');
    };
    
    window.closeMitarbeiterModal = function() {
        $('#mitarbeiter-modal').hide();
        if(typeof dpCheckPendingReload === 'function') {
            dpCheckPendingReload();
        }
    };
    
    window.editMitarbeiter = function(id) {
        console.log('editMitarbeiter', id);
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_get_mitarbeiter',
                nonce: dpAjax.nonce,
                mitarbeiter_id: id
            },
            success: function(response) {
                console.log('Mitarbeiter geladen:', response);
                if (response.success && response.data) {
                    const ma = response.data;
                    
                    $('#mitarbeiter_id').val(ma.id);
                    $('#ma_vorname').val(ma.vorname);
                    $('#ma_nachname').val(ma.nachname);
                    $('#ma_email').val(ma.email);
                    $('#ma_telefon').val(ma.telefon || '');
                    $('#ma_notizen').val(ma.notizen || '');

                    if (Array.isArray(ma.allowed_vereine)) {
                        updateVereinOptions(ma.allowed_vereine);
                    }
                    setSelectedVereine(ma.verein_ids || []);
                    
                    // Portal-Zugriff anzeigen
                    if (ma.email) {
                        $('#portal-access-row').show();
                        
                        if (ma.user_id) {
                            // Hat bereits Portal-Zugriff
                            $('#portal-status-display').show();
                            $('#portal-activate-display').hide();
                        } else {
                            // Kein Portal-Zugriff
                            $('#portal-status-display').hide();
                            $('#portal-activate-display').show();
                            $('#ma_portal_access').prop('checked', false);
                        }
                    } else {
                        // Keine E-Mail = kein Portal-Zugriff möglich
                        $('#portal-access-row').hide();
                    }
                    
                    $('#mitarbeiter-modal-title').text('Mitarbeiter bearbeiten');
                    $('#mitarbeiter-modal').css('display', 'flex');
                } else {
                    alert('Fehler beim Laden der Mitarbeiter-Daten');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Fehler:', error);
                alert('AJAX-Fehler beim Laden der Mitarbeiter-Daten');
            }
        });
    };
    
    window.saveMitarbeiter = function() {
        console.log('saveMitarbeiter');
        
        // Validierung
        if (!$('#ma_vorname').val() || !$('#ma_nachname').val() || !$('#ma_email').val()) {
            alert('Bitte füllen Sie alle Pflichtfelder aus!');
            return;
        }
        
        const formData = {
            action: 'dp_save_mitarbeiter',
            nonce: dpAjax.nonce,
            mitarbeiter_id: $('#mitarbeiter_id').val(),
            vorname: $('#ma_vorname').val(),
            nachname: $('#ma_nachname').val(),
            email: $('#ma_email').val(),
            telefon: $('#ma_telefon').val(),
            notizen: $('#ma_notizen').val(),
            portal_access: $('#ma_portal_access').is(':checked') ? '1' : '0'
        };

        const vereinIds = $('#ma_vereine').val() || [];
        formData.verein_ids = vereinIds;
        
        console.log('Sende Save Request:', formData);
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Save Response:', response);
                if (response.success) {
                    // Zeige Erfolgsmeldung
                    if (response.data && response.data.message) {
                        alert(response.data.message);
                    }
                    
                    // Prüfe ob Veranstaltungs-Modal offen ist
                    if (window.veranstaltungModalIsOpen && typeof reloadVerantwortlicheList === 'function') {
                        closeMitarbeiterModal();
                        reloadVerantwortlicheList();
                        window.veranstaltungModalIsOpen = false;
                    } else {
                        if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
                    }
                } else {
                    alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('AJAX Fehler: ' + error);
            }
        });
    };
    
    window.deleteMitarbeiter = function(id) {
        if (!confirm('Möchten Sie diesen Mitarbeiter wirklich löschen?\n\nDamit werden auch alle seine Dienst-Zuweisungen gelöscht!')) {
            return;
        }
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_delete_mitarbeiter',
                nonce: dpAjax.nonce,
                mitarbeiter_id: id
            },
            success: function(response) {
                if (response.success) {
                    if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
                } else {
                    alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                }
            }
        });
    };
    
    // ESC-Taste
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#mitarbeiter-modal').is(':visible')) {
            closeMitarbeiterModal();
        }
    });
    
})(jQuery);

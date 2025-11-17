(function($) {
    'use strict';
    
    $(document).ready(function() {
        console.log('Mitarbeiter Modal geladen');
    });
    
    window.openMitarbeiterModal = function() {
        console.log('openMitarbeiterModal');
        $('#mitarbeiter-form')[0].reset();
        $('#mitarbeiter_id').val('');
        $('#mitarbeiter-modal-title').text('Neuer Mitarbeiter');
        $('#mitarbeiter-modal').css('display', 'flex');
    };
    
    window.closeMitarbeiterModal = function() {
        $('#mitarbeiter-modal').hide();
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
            notizen: $('#ma_notizen').val()
        };
        
        console.log('Sende Save Request:', formData);
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Save Response:', response);
                if (response.success) {
                    location.reload();
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
                    location.reload();
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

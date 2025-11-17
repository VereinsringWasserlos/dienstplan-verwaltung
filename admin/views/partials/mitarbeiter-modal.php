<?php
/**
 * Mitarbeiter Modal - Erstellen/Bearbeiten
 */
if (!defined('ABSPATH')) exit;
?>

<!-- Mitarbeiter Modal -->
<div id="mitarbeiter-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content" style="max-width: 600px;">
        <div class="dp-modal-header">
            <h2 id="mitarbeiter-modal-title"><?php _e('Neuer Mitarbeiter', 'dienstplan-verwaltung'); ?></h2>
            <button type="button" class="dp-modal-close" onclick="closeMitarbeiterModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="mitarbeiter-form">
                <input type="hidden" id="mitarbeiter_id" name="mitarbeiter_id">
                
                <table class="form-table">
                    <tr>
                        <th><label for="ma_vorname"><?php _e('Vorname', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="text" id="ma_vorname" name="vorname" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="ma_nachname"><?php _e('Nachname', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="text" id="ma_nachname" name="nachname" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="ma_email"><?php _e('E-Mail', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="email" id="ma_email" name="email" class="regular-text" required>
                            <p class="description"><?php _e('Die E-Mail-Adresse des Mitarbeiters.', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="ma_telefon"><?php _e('Telefon', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <input type="tel" id="ma_telefon" name="telefon" class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="ma_notizen"><?php _e('Notizen', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <textarea id="ma_notizen" name="notizen" class="large-text" rows="3"></textarea>
                            <p class="description"><?php _e('Interne Notizen zum Mitarbeiter.', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeMitarbeiterModal()"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
            <button type="button" class="button button-primary" onclick="saveMitarbeiter()"><?php _e('Speichern', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>

<!-- JavaScript moved to assets/js/dp-mitarbeiter-modal.js -->
    console.log('Mitarbeiter Modal geladen');
    
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
});
</script>

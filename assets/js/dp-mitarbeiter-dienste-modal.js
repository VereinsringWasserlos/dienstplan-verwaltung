(function($) {
    'use strict';
    
    $(document).ready(function() {
        console.log('Mitarbeiter Dienste Modal geladen');
    });
    
    window.viewMitarbeiterDienste = function(mitarbeiterId) {
        console.log('viewMitarbeiterDienste', mitarbeiterId);
        $('#view_mitarbeiter_id').val(mitarbeiterId);
        $('#mitarbeiter-dienste-container').html('<p style="text-align: center; color: #666;"><span class="spinner is-active" style="float: none; margin: 0;"></span> Lade Dienste...</p>');
        $('#mitarbeiter-dienste-modal').css('display', 'flex');
        
        // AJAX: Mitarbeiter + Dienste laden
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_get_mitarbeiter_dienste',
                nonce: dpAjax.nonce,
                mitarbeiter_id: mitarbeiterId
            },
            success: function(response) {
                console.log('Mitarbeiter Dienste Response:', response);
                if (response.success && response.data) {
                    renderMitarbeiterDienste(response.data);
                } else {
                    $('#mitarbeiter-dienste-container').html('<div class="notice notice-error inline"><p>Fehler beim Laden: ' + (response.data ? response.data.message : 'Unbekannt') + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Fehler:', error);
                $('#mitarbeiter-dienste-container').html('<div class="notice notice-error inline"><p>AJAX-Fehler: ' + error + '</p></div>');
            }
        });
    };
    
    window.closeMitarbeiterDiensteModal = function() {
        $('#mitarbeiter-dienste-modal').hide();
    };
    
    function renderMitarbeiterDienste(data) {
        const mitarbeiter = data.mitarbeiter;
        const dienste = data.dienste;
        
        // Mitarbeiter-Info
        $('#info-ma-name').text(mitarbeiter.vorname + ' ' + mitarbeiter.nachname);
        $('#info-ma-email').html('<a href="mailto:' + mitarbeiter.email + '">' + mitarbeiter.email + '</a>');
        $('#info-ma-telefon').text(mitarbeiter.telefon || '—');
        
        $('#mitarbeiter-dienste-title').text('Dienste von ' + mitarbeiter.vorname + ' ' + mitarbeiter.nachname);
        
        // Dienste
        if (!dienste || dienste.length === 0) {
            $('#mitarbeiter-dienste-container').html('<div class="notice notice-info inline"><p>Noch keine Dienste zugewiesen.</p></div>');
            return;
        }
        
        let html = '<table class="wp-list-table widefat fixed striped">';
        html += '<thead><tr>';
        html += '<th>Veranstaltung</th>';
        html += '<th>Tag</th>';
        html += '<th>Tätigkeit</th>';
        html += '<th>Bereich</th>';
        html += '<th>Dienstzeit</th>';
        html += '<th>Slot</th>';
        html += '<th>Status</th>';
        html += '</tr></thead>';
        html += '<tbody>';
        
        dienste.forEach(function(dienst) {
            const datum = new Date(dienst.tag_datum + 'T00:00:00');
            const datumFormatiert = datum.toLocaleDateString('de-DE', { weekday: 'short', day: '2-digit', month: '2-digit' });
            
            html += '<tr>';
            html += '<td><strong>' + dienst.veranstaltung_name + '</strong></td>';
            html += '<td>' + datumFormatiert + '</td>';
            html += '<td>' + dienst.taetigkeit_name + '</td>';
            html += '<td><span style="display: inline-block; width: 12px; height: 12px; border-radius: 2px; background: ' + dienst.bereich_farbe + '; margin-right: 0.5rem;"></span>' + dienst.bereich_name + '</td>';
            html += '<td>' + dienst.von_zeit.substring(0, 5) + ' - ' + dienst.bis_zeit.substring(0, 5) + ' Uhr</td>';
            html += '<td><code>#' + dienst.slot_id + '</code> Slot ' + dienst.slot_nummer + '</td>';
            
            let statusBadge = '';
            if (dienst.slot_status === 'besetzt') {
                statusBadge = '<span style="padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85em; background: #dcfce7; color: #166534;">✓ Besetzt</span>';
            } else {
                statusBadge = '<span style="padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85em; background: #fee2e2; color: #991b1b;">○ Frei</span>';
            }
            html += '<td>' + statusBadge + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        
        $('#mitarbeiter-dienste-container').html(html);
    }
    
    // ESC-Taste
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#mitarbeiter-dienste-modal').is(':visible')) {
            closeMitarbeiterDiensteModal();
        }
    });
    
})(jQuery);

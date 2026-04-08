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
                    try {
                        renderMitarbeiterDienste(response.data);
                    } catch (err) {
                        console.error('Render-Fehler in renderMitarbeiterDienste:', err);
                        $('#mitarbeiter-dienste-container').html('<div class="notice notice-error inline"><p>Fehler beim Anzeigen der Dienste. Bitte Seite neu laden.</p></div>');
                    }
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

    // Kompatibilität: bestehende Aufrufe nutzen weiterhin openMitarbeiterDiensteModal(...)
    window.openMitarbeiterDiensteModal = function(mitarbeiterId) {
        window.viewMitarbeiterDienste(mitarbeiterId);
    };
    
    window.closeMitarbeiterDiensteModal = function() {
        $('#mitarbeiter-dienste-modal').hide();
    };

    window.removeDienstZuweisungFromMitarbeiterModal = function(slotId) {
        if (!slotId) {
            return;
        }

        if (!window.confirm('Möchten Sie diese Zuweisung wirklich entfernen?')) {
            return;
        }

        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_admin_remove_slot',
                nonce: dpAjax.nonce,
                slot_id: slotId
            },
            success: function(response) {
                if (response && response.success) {
                    const currentMitarbeiterId = parseInt($('#view_mitarbeiter_id').val(), 10);
                    if (!Number.isNaN(currentMitarbeiterId) && currentMitarbeiterId > 0) {
                        window.viewMitarbeiterDienste(currentMitarbeiterId);
                    }
                    return;
                }

                const errorMessage = response && response.data && response.data.message
                    ? response.data.message
                    : 'Unbekannter Fehler';
                window.alert('Fehler beim Entfernen der Zuweisung: ' + errorMessage);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Fehler bei removeDienstZuweisungFromMitarbeiterModal:', error);
                window.alert('AJAX-Fehler beim Entfernen der Zuweisung: ' + error);
            }
        });
    };
    
    function renderMitarbeiterDienste(data) {
        const mitarbeiter = data.mitarbeiter;
        const dienste = data.dienste;

        const textOrDash = function(value) {
            return (value === null || value === undefined || value === '') ? '—' : String(value);
        };

        const formatTime = function(value) {
            const raw = (value === null || value === undefined) ? '' : String(value);
            return raw.length >= 5 ? raw.substring(0, 5) : '—';
        };

        const formatDateSafe = function(value) {
            if (!value) {
                return '—';
            }

            const dateObj = new Date(String(value) + 'T00:00:00');
            if (isNaN(dateObj.getTime())) {
                return '—';
            }

            return dateObj.toLocaleDateString('de-DE', { weekday: 'short', day: '2-digit', month: '2-digit' });
        };
        
        // Mitarbeiter-Info
        const maVorname = textOrDash(mitarbeiter.vorname);
        const maNachname = textOrDash(mitarbeiter.nachname);
        const maEmail = (mitarbeiter.email === null || mitarbeiter.email === undefined) ? '' : String(mitarbeiter.email);
        const maTelefon = textOrDash(mitarbeiter.telefon);

        $('#info-ma-name').text((maVorname + ' ' + maNachname).trim());
        if (maEmail) {
            $('#info-ma-email').html('<a href="mailto:' + maEmail + '">' + maEmail + '</a>');
        } else {
            $('#info-ma-email').text('—');
        }
        $('#info-ma-telefon').text(maTelefon);
        
        $('#mitarbeiter-dienste-title').text('Dienste von ' + (maVorname + ' ' + maNachname).trim());
        
        // Dienste
        if (!dienste || dienste.length === 0) {
            $('#mitarbeiter-dienste-container').html('<div class="notice notice-info inline"><p>Noch keine Dienste zugewiesen.</p></div>');
            return;
        }
        
        let html = '<table class="wp-list-table widefat fixed striped">';
        html += '<thead><tr>';
        html += '<th>Veranstaltung</th>';
        html += '<th>Verein</th>';
        html += '<th>Tag</th>';
        html += '<th>Tätigkeit</th>';
        html += '<th>Bereich</th>';
        html += '<th>Dienstzeit</th>';
        html += '<th>Slot</th>';
        html += '<th>Status</th>';
        html += '<th>Aktion</th>';
        html += '</tr></thead>';
        html += '<tbody>';
        
        dienste.forEach(function(dienst) {
            const datumFormatiert = formatDateSafe(dienst.tag_datum);
            const vonZeit = formatTime(dienst.von_zeit);
            const bisZeit = formatTime(dienst.bis_zeit);
            const bereichFarbe = (dienst.bereich_farbe === null || dienst.bereich_farbe === undefined || dienst.bereich_farbe === '') ? '#9ca3af' : String(dienst.bereich_farbe);
            
            html += '<tr>';
            html += '<td><strong>' + textOrDash(dienst.veranstaltung_name) + '</strong></td>';
            html += '<td>' + textOrDash(dienst.verein_name) + '</td>';
            html += '<td>' + datumFormatiert + '</td>';
            html += '<td>' + textOrDash(dienst.taetigkeit_name) + '</td>';
            html += '<td><span style="display: inline-block; width: 12px; height: 12px; border-radius: 2px; background: ' + bereichFarbe + '; margin-right: 0.5rem;"></span>' + textOrDash(dienst.bereich_name) + '</td>';
            html += '<td>' + vonZeit + ' - ' + bisZeit + ' Uhr</td>';
            html += '<td><code>#' + textOrDash(dienst.slot_id) + '</code> Slot ' + textOrDash(dienst.slot_nummer) + '</td>';
            
            let statusBadge = '';
            if (dienst.slot_status === 'besetzt') {
                statusBadge = '<span style="padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85em; background: #dcfce7; color: #166534;">✓ Besetzt</span>';
            } else {
                statusBadge = '<span style="padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85em; background: #fee2e2; color: #991b1b;">○ Frei</span>';
            }
            html += '<td>' + statusBadge + '</td>';
            html += '<td><button type="button" class="button button-small" style="color: #b32d2e; border-color: #b32d2e;" onclick="removeDienstZuweisungFromMitarbeiterModal(' + Number(dienst.slot_id || 0) + ')">Zuweisung löschen</button></td>';
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

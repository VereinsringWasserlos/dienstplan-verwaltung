/**
 * Dienstplan Verwaltung - Dienst Modal JavaScript
 * Verwaltet das Modal für das Erstellen/Bearbeiten von Diensten
 */

(function($) {
    'use strict';

    // Warte auf DOM Ready
    $(document).ready(function() {
        console.log('Dienst Modal geladen');
        
        // Veranstaltung auswählen -> Tage laden
        $('#d_veranstaltung_id').on('change', function() {
            const veranstaltungId = $(this).val();
            $('#d_tag_id').html('<option value="">-- Lädt... --</option>');
            $('#tag-zeitfenster-info').hide();
            
            if (!veranstaltungId) {
                $('#d_tag_id').html('<option value="">-- Erst Veranstaltung wählen --</option>');
                return;
            }
            
            // AJAX: Tage für Veranstaltung laden
            $.ajax({
                url: dpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dp_get_veranstaltung_tage',
                    veranstaltung_id: veranstaltungId,
                    nonce: dpAjax.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        let html = '<option value="">-- Bitte wählen --</option>';
                        response.data.forEach(function(tag) {
                            const datum = new Date(tag.tag_datum + 'T00:00:00');
                            const wochentag = datum.toLocaleDateString('de-DE', { weekday: 'long' });
                            const datumFormatiert = datum.toLocaleDateString('de-DE');
                            
                            html += `<option value="${tag.id}" 
                                        data-von-zeit="${tag.dienst_von_zeit || tag.von_zeit || ''}" 
                                        data-bis-zeit="${tag.dienst_bis_zeit || tag.bis_zeit || ''}"
                                        data-bis-datum="${tag.dienst_bis_datum || tag.bis_datum || ''}">
                                Tag ${tag.tag_nummer}: ${wochentag}, ${datumFormatiert}
                            </option>`;
                        });
                        $('#d_tag_id').html(html);
                    }
                }
            });
        });
        
        // Tag auswählen -> Zeitfenster anzeigen
        $('#d_tag_id').on('change', function() {
            const $option = $(this).find('option:selected');
            const vonZeit = $option.data('von-zeit');
            const bisZeit = $option.data('bis-zeit');
            const bisDatum = $option.data('bis-datum');
            
            if (vonZeit && bisZeit) {
                let zeitText = `${vonZeit.substring(0, 5)} - ${bisZeit.substring(0, 5)} Uhr`;
                if (bisDatum) {
                    zeitText += ' (+1 Tag)';
                }
                $('#tag-zeitfenster-text').text(zeitText);
                $('#tag-zeitfenster-info').show();
                
                // Speichere für Validierung
                window.currentTagZeitfenster = {
                    von: vonZeit,
                    bis: bisZeit,
                    bis_datum: bisDatum
                };
            } else {
                $('#tag-zeitfenster-info').hide();
                window.currentTagZeitfenster = null;
            }
        });
        
        // Bereich auswählen -> Tätigkeiten laden
        $('#d_bereich_id').on('change', function() {
            const bereichId = $(this).val();
            $('#d_taetigkeit_id').html('<option value="">Lädt...</option>');
            $('#btn_neue_taetigkeit').prop('disabled', true);
            
            if (!bereichId) {
                $('#d_taetigkeit_id').html('<option value="">-- Erst Bereich wählen --</option>');
                return;
            }
            
            // AJAX: Tätigkeiten für Bereich laden
            $.ajax({
                url: dpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dp_get_taetigkeiten_by_bereich',
                    bereich_id: bereichId,
                    nonce: dpAjax.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        let html = '<option value="">-- Bitte wählen --</option>';
                        response.data.forEach(function(taetigkeit) {
                            html += `<option value="${taetigkeit.id}">${taetigkeit.name}</option>`;
                        });
                        $('#d_taetigkeit_id').html(html);
                        $('#btn_neue_taetigkeit').prop('disabled', false);
                    }
                }
            });
        });
        
        // ESC-Taste zum Schließen
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                if ($('#dienst-modal').is(':visible')) {
                    window.closeDienstModal();
                }
                if ($('#neue-taetigkeit-modal').is(':visible')) {
                    window.closeNeueTaetigkeitModal();
                }
                if ($('#neuer-bereich-modal').is(':visible')) {
                    window.closeNeuerBereichModal();
                }
                if ($('#neuer-verein-modal').is(':visible')) {
                    window.closeNeuerVereinModal();
                }
            }
        });

        // Klick außerhalb der Modals -> Schließen
        $(document).on('click', function(e) {
            if ($(e.target).hasClass('dp-modal') && $(e.target).is(':visible')) {
                if ($(e.target).attr('id') === 'dienst-modal') {
                    window.closeDienstModal();
                } else if ($(e.target).attr('id') === 'neue-taetigkeit-modal') {
                    window.closeNeueTaetigkeitModal();
                } else if ($(e.target).attr('id') === 'neuer-bereich-modal') {
                    window.closeNeuerBereichModal();
                } else if ($(e.target).attr('id') === 'neuer-verein-modal') {
                    window.closeNeuerVereinModal();
                }
            }
        });

        // Zeit-Änderungen validieren
        $('#d_von_zeit, #d_bis_zeit, #d_bis_folgetag').on('change', validateDienstzeit);
    });

    // ========================================
    // Globale Funktionen (window scope)
    // ========================================

    /**
     * Öffnet das Modal für einen neuen Dienst
     * @param {number} tagId - Optional: Tag-ID zum Vorbelegen
     */
    window.openDienstModal = function(tagId) {
        console.log('openDienstModal', tagId);
        $('#dienst-form')[0].reset();
        $('#dienst_id').val('');
        $('#dienst-modal-title').text('Neuer Dienst');
        $('#tag-zeitfenster-info').hide();
        $('#dienst-zeit-warnung').hide();
        
        // Vorauswahl Veranstaltung aus Filter (falls in dpAjax definiert)
        if (typeof dpAjax.selectedVeranstaltung !== 'undefined' && dpAjax.selectedVeranstaltung > 0) {
            $('#d_veranstaltung_id').val(dpAjax.selectedVeranstaltung).trigger('change');
            
            // Wenn Tag-ID übergeben wurde, diese nach dem Laden der Tage vorbelegen
            if (tagId) {
                // Warte kurz, bis die Tage geladen sind, dann Tag auswählen
                setTimeout(function() {
                    $('#d_tag_id').val(tagId).trigger('change');
                }, 500);
            }
        }
        
        $('#dienst-modal').css('display', 'flex');
    };

    /**
     * Schließt das Dienst-Modal
     */
    window.closeDienstModal = function() {
        $('#dienst-modal').hide();
    };

    /**
     * Speichert einen Dienst (neu oder bearbeitet)
     */
    window.saveDienst = function() {
        console.log('saveDienst');
        
        // Validierung
        if (!$('#d_veranstaltung_id').val() || !$('#d_tag_id').val() || 
            !$('#d_verein_id').val() || !$('#d_bereich_id').val() || 
            !$('#d_taetigkeit_id').val() || !$('#d_von_zeit').val() || 
            !$('#d_bis_zeit').val() || !$('#d_anzahl_personen').val()) {
            alert('Bitte füllen Sie alle Pflichtfelder aus!');
            return;
        }
        
        // Zeitfenster-Validierung
        if (!validateDienstzeit()) {
            alert('Bitte korrigieren Sie die Dienstzeiten. Sie müssen innerhalb des für den Tag vorgegebenen Zeitfensters liegen.');
            return;
        }
        
        const formData = {
            action: 'dp_save_dienst',
            nonce: dpAjax.nonce,
            dienst_id: $('#dienst_id').val(),
            veranstaltung_id: $('#d_veranstaltung_id').val(),
            tag_id: $('#d_tag_id').val(),
            verein_id: $('#d_verein_id').val(),
            bereich_id: $('#d_bereich_id').val(),
            taetigkeit_id: $('#d_taetigkeit_id').val(),
            von_zeit: $('#d_von_zeit').val(),
            bis_zeit: $('#d_bis_zeit').val(),
            bis_folgetag: $('#d_bis_folgetag').is(':checked') ? 1 : 0,
            anzahl_personen: $('#d_anzahl_personen').val(),
            splittbar: $('#d_splittbar').is(':checked') ? 1 : 0,
            besonderheiten: $('#d_besonderheiten').val()
        };
        
        console.log('Sende Save Request:', formData);
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Save Response:', response);
                if (response.success) {
                    if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
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

    /**
     * Lädt einen Dienst zum Bearbeiten
     */
    window.editDienst = function(id) {
        console.log('editDienst called with id:', id);
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_get_dienst',
                nonce: dpAjax.nonce,
                dienst_id: id
            },
            success: function(response) {
                console.log('Dienst geladen:', response);
                if (response.success && response.data) {
                    const d = response.data.dienst;
                    const tage = response.data.tage;
                    const taetigkeiten = response.data.taetigkeiten;
                    
                    console.log('Dienst Daten:', d);
                    console.log('Tage:', tage);
                    console.log('Tätigkeiten:', taetigkeiten);
                    
                    $('#dienst_id').val(d.id);
                    
                    // Setze Verein
                    $('#d_verein_id').val(d.verein_id);
                    
                    // Setze Veranstaltung
                    $('#d_veranstaltung_id').val(d.veranstaltung_id);
                    
                    // Baue Tag-Dropdown direkt auf (keine AJAX nötig)
                    let tageHtml = '<option value="">-- Bitte wählen --</option>';
                    tage.forEach(function(tag) {
                        const datum = new Date(tag.tag_datum + 'T00:00:00');
                        const wochentag = datum.toLocaleDateString('de-DE', { weekday: 'long' });
                        const datumFormatiert = datum.toLocaleDateString('de-DE');
                        
                        tageHtml += `<option value="${tag.id}" 
                                    data-von-zeit="${tag.dienst_von_zeit || tag.von_zeit || ''}" 
                                    data-bis-zeit="${tag.dienst_bis_zeit || tag.bis_zeit || ''}"
                                    data-bis-datum="${tag.dienst_bis_datum || tag.bis_datum || ''}">
                            Tag ${tag.tag_nummer}: ${wochentag}, ${datumFormatiert}
                        </option>`;
                    });
                    $('#d_tag_id').html(tageHtml);
                    $('#d_tag_id').val(d.tag_id).trigger('change');
                    
                    // Setze Bereich
                    $('#d_bereich_id').val(d.bereich_id);
                    
                    // Baue Tätigkeiten-Dropdown direkt auf (keine AJAX nötig)
                    let taetigkeitenHtml = '<option value="">-- Bitte wählen --</option>';
                    taetigkeiten.forEach(function(taetigkeit) {
                        taetigkeitenHtml += `<option value="${taetigkeit.id}">${taetigkeit.name}</option>`;
                    });
                    $('#d_taetigkeit_id').html(taetigkeitenHtml);
                    $('#d_taetigkeit_id').val(d.taetigkeit_id);
                    $('#btn_neue_taetigkeit').prop('disabled', false);
                    
                    // Setze restliche Felder
                    $('#d_von_zeit').val(d.von_zeit ? d.von_zeit.substring(0, 5) : '');
                    $('#d_bis_zeit').val(d.bis_zeit ? d.bis_zeit.substring(0, 5) : '');
                    $('#d_bis_folgetag').prop('checked', d.bis_datum ? true : false);
                    $('#d_anzahl_personen').val(d.anzahl_personen || 1);
                    $('#d_splittbar').prop('checked', d.splittbar == 1);
                    $('#d_besonderheiten').val(d.besonderheiten || '');
                    
                    $('#dienst-modal-title').text('Dienst bearbeiten');
                    $('#dienst-modal').css('display', 'flex');
                } else {
                    console.error('Fehler beim Laden des Dienstes:', response);
                    alert('Fehler beim Laden der Dienst-Daten');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Fehler:', error);
                console.error('Response:', xhr.responseText);
                alert('AJAX-Fehler beim Laden der Dienst-Daten');
            }
        });
    };

    /**
     * Löscht einen Dienst
     */
    window.deleteDienst = function(id) {
        if (!confirm('Möchten Sie diesen Dienst wirklich löschen?\n\nDamit werden auch alle zugehörigen Slots und Eintragungen gelöscht!')) {
            return;
        }
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_delete_dienst',
                nonce: dpAjax.nonce,
                dienst_id: id
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

    /**
     * Kopiert einen Dienst mehrfach
     */
    window.copyDienst = function(id) {
        console.log('copyDienst called with id:', id);
        const count = prompt('Wie viele Kopien sollen erstellt werden?', '1');
        if (!count || count < 1) {
            return;
        }
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_copy_dienst',
                nonce: dpAjax.nonce,
                dienst_id: id,
                copy_count: parseInt(count)
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message || (count + ' Dienst(e) wurden erfolgreich kopiert.'));
                    if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
                } else {
                    alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                }
            },
            error: function() {
                alert('AJAX-Fehler beim Kopieren');
            }
        });
    };

    /**
     * Dialog für neuen Bereich
     */
    window.openNeuerBereichDialog = function() {
        $('#neuer-bereich-form')[0].reset();
        $('#neuer_bereich_farbe').val('#3b82f6');
        $('#neuer-bereich-modal').css('display', 'flex');
        $('#neuer_bereich_name').focus();
    };

    window.closeNeuerBereichModal = function() {
        $('#neuer-bereich-modal').hide();
    };

    window.saveNeuerBereich = function() {
        const name = $('#neuer_bereich_name').val().trim();
        const farbe = $('#neuer_bereich_farbe').val();
        
        if (!name) {
            alert('Bitte geben Sie einen Namen ein!');
            return;
        }
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_create_bereich',
                name: name,
                farbe: farbe,
                nonce: dpAjax.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Neuen Bereich zum Dropdown hinzufügen
                    const option = $('<option>', {
                        value: response.data.bereich_id,
                        text: response.data.name,
                        'data-farbe': response.data.farbe
                    });
                    $('#d_bereich_id').append(option);
                    $('#d_bereich_id').val(response.data.bereich_id).trigger('change');
                    window.closeNeuerBereichModal();
                    alert('Bereich "' + response.data.name + '" wurde erstellt!');
                } else {
                    alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                }
            }
        });
    };

    /**
     * Dialog für neue Tätigkeit
     */
    window.openNeueTaetigkeitDialog = function() {
        const bereichId = $('#d_bereich_id').val();
        if (!bereichId) {
            alert('Bitte erst einen Bereich auswählen!');
            return;
        }
        
        $('#neue-taetigkeit-form')[0].reset();
        $('#neue-taetigkeit-modal').css('display', 'flex');
        $('#neue_taetigkeit_name').focus();
        
        // Speichere die aktuelle bereich_id im Modal
        window.currentBereichIdForTaetigkeit = bereichId;
    };

    window.closeNeueTaetigkeitModal = function() {
        $('#neue-taetigkeit-modal').hide();
        window.currentBereichIdForTaetigkeit = null;
    };

    window.saveNeueTaetigkeit = function() {
        const bereichId = window.currentBereichIdForTaetigkeit;
        const name = $('#neue_taetigkeit_name').val().trim();
        
        if (!name) {
            alert('Bitte geben Sie einen Namen ein!');
            return;
        }
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_create_taetigkeit',
                bereich_id: bereichId,
                name: name,
                nonce: dpAjax.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Neue Tätigkeit zum Dropdown hinzufügen
                    const option = $('<option>', {
                        value: response.data.taetigkeit_id,
                        text: response.data.name
                    });
                    $('#d_taetigkeit_id').append(option);
                    $('#d_taetigkeit_id').val(response.data.taetigkeit_id);
                    window.closeNeueTaetigkeitModal();
                    alert('Tätigkeit "' + response.data.name + '" wurde erstellt!');
                } else {
                    alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                }
            }
        });
    };

    /**
     * Dialog für neuen Verein
     */
    window.openNeuerVereinDialog = function() {
        $('#neuer-verein-form')[0].reset();
        $('#neuer-verein-modal').css('display', 'flex');
        $('#neuer_verein_name').focus();
    };

    window.closeNeuerVereinModal = function() {
        $('#neuer-verein-modal').hide();
    };

    window.saveNeuerVerein = function() {
        const name = $('#neuer_verein_name').val().trim();
        const kuerzel = $('#neuer_verein_kuerzel').val().trim().toUpperCase();
        
        if (!name) {
            alert('Bitte geben Sie einen Namen ein!');
            return;
        }
        
        if (!kuerzel) {
            alert('Bitte geben Sie ein Kürzel ein!');
            return;
        }
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_create_verein',
                name: name,
                kuerzel: kuerzel,
                nonce: dpAjax.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Neuen Verein zum Dropdown hinzufügen
                    const option = $('<option>', {
                        value: response.data.verein_id,
                        text: response.data.name + ' (' + response.data.kuerzel + ')'
                    });
                    $('#d_verein_id').append(option);
                    $('#d_verein_id').val(response.data.verein_id);
                    window.closeNeuerVereinModal();
                    alert('Verein "' + response.data.name + '" (' + response.data.kuerzel + ') wurde erstellt!');
                } else {
                    alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                }
            }
        });
    };

    // ========================================
    // Private Funktionen
    // ========================================

    /**
     * Validiert Dienstzeiten gegen Zeitfenster des Tags
     */
    function validateDienstzeit() {
        if (!window.currentTagZeitfenster) return true;
        
        const dienstVon = $('#d_von_zeit').val();
        const dienstBis = $('#d_bis_zeit').val();
        const dienstBisFolgetag = $('#d_bis_folgetag').is(':checked');
        
        if (!dienstVon || !dienstBis) return true;
        
        // Normalisiere Zeiten auf HH:MM Format
        const tagVon = window.currentTagZeitfenster.von.substring(0, 5);
        const tagBis = window.currentTagZeitfenster.bis.substring(0, 5);
        const tagBisDatum = window.currentTagZeitfenster.bis_datum;
        
        // Vergleiche Zeiten (mit < und > damit gleiche Zeiten erlaubt sind)
        if (dienstVon < tagVon) {
            $('#dienst-zeit-warnung-text').text(`Dienst-Start (${dienstVon}) liegt vor dem erlaubten Zeitfenster-Start (${tagVon})`);
            $('#dienst-zeit-warnung').show();
            return false;
        }
        
        // Wenn Dienst über Mitternacht geht, muss auch Tag-Zeitfenster über Mitternacht gehen
        if (dienstBisFolgetag && !tagBisDatum) {
            $('#dienst-zeit-warnung-text').text('Dienst kann nicht über Mitternacht gehen, da das Zeitfenster nicht über Mitternacht geht');
            $('#dienst-zeit-warnung').show();
            return false;
        }
        
        if (dienstBis > tagBis) {
            $('#dienst-zeit-warnung-text').text(`Dienst-Ende (${dienstBis}) liegt nach dem erlaubten Zeitfenster-Ende (${tagBis})`);
            $('#dienst-zeit-warnung').show();
            return false;
        }
        
        $('#dienst-zeit-warnung').hide();
        return true;
    }

})(jQuery);

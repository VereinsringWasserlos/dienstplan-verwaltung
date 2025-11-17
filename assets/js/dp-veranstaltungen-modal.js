(function($) {
    'use strict';
    
    let tagCounter = 0;
    
    $(document).ready(function() {
        console.log('Veranstaltungen Modal geladen');
    });
    
    window.openVeranstaltungModal = function() {
        console.log('openVeranstaltungModal');
        $('#veranstaltung-form')[0].reset();
        $('#veranstaltung_id').val('');
        $('#veranstaltung-modal-title').text('Neue Veranstaltung');
        $('.verein-checkbox').prop('checked', false);
        
        // Lade alle Benutzer für die Auswahl
        $('#v_verantwortliche-checkboxes').html('<p style="color: #666; margin: 0;">Lädt...</p>');
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_get_all_users',
                nonce: dpAjax.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    let html = '';
                    response.data.forEach(function(user) {
                        const name = user.name || 'Unbekannt';
                        const email = user.email || '';
                        html += '<label style="display: block; padding: 0.5rem; cursor: pointer; border-radius: 4px; margin-bottom: 0.25rem;" ' +
                                'onmouseover="this.style.background=\'#f0f6fc\'" ' +
                                'onmouseout="this.style.background=\'transparent\'">' +
                                '<input type="checkbox" name="verantwortliche[]" value="' + user.id + '" style="margin-right: 0.5rem;"> ' +
                                '<strong>' + name + '</strong>' +
                                (email ? ' <span style="color: #666;">(' + email + ')</span>' : '') +
                                '</label>';
                    });
                    $('#v_verantwortliche-checkboxes').html(html || '<p style="color: #999; margin: 0;">Keine Benutzer gefunden</p>');
                } else {
                    $('#v_verantwortliche-checkboxes').html('<p style="color: #999; margin: 0;">Keine Benutzer gefunden</p>');
                }
            }
        });
        
        $('#tage-tbody').empty();
        tagCounter = 0;
        addTag(); // Mindestens einen Tag
        
        // Event Listener für erstes Datumsfeld registrieren
        setTimeout(() => {
            $('#tage-tbody tr:first input[name="tag_datum[]"]').on('change', function() {
                updateFollowingDates();
            });
        }, 100);
        
        $('#veranstaltung-modal').css('display', 'flex');
    };
    
    window.closeVeranstaltungModal = function() {
        $('#veranstaltung-modal').hide();
        if(typeof dpCheckPendingReload === 'function') {
            dpCheckPendingReload();
        }
    };
    
    window.toggleMehrtaegig = function() {
        // Kann später für UI-Anpassungen genutzt werden
    };
    
    window.addTag = function() {
        tagCounter++;
        const rowNumber = tagCounter;
        
        const tagHtml = `
            <tr class="tag-row" data-tag-id="${tagCounter}">
                <td style="text-align: center; font-weight: 600; color: var(--dp-primary); padding: 6px 4px;">${rowNumber}</td>
                <td style="padding: 6px;">
                    <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: nowrap;">
                        <input type="date" name="tag_datum[]" class="regular-text datum-input" required style="width: 130px; padding: 3px 6px; font-size: 12px; height: 26px;" onchange="updateWeekday(this)">
                        <div class="weekday-display" style="font-size: 0.7rem; color: var(--dp-gray-500); min-width: 65px;"></div>
                    </div>
                    <label style="display: inline-flex; align-items: center; gap: 0.2rem; font-size: 0.65rem; cursor: pointer; padding: 0.1rem 0.25rem; background: #fff3cd; border-radius: 3px; white-space: nowrap; margin-top: 0.2rem;">
                        <input type="checkbox" name="nur_dienst[]" class="nur-dienst-checkbox" value="1" onchange="toggleEventFields(this)" style="margin: 0;">
                        <span style="font-weight: 600; color: #856404;">🔧 Nur Dienst</span>
                    </label>
                </td>
                <td style="background: #f0f9ff; padding: 6px;" class="event-field">
                    <input type="time" name="tag_von[]" class="regular-text event-time-input" style="width: 100%; padding: 3px 6px; font-size: 12px; height: 26px;" onchange="checkTimeOverMidnight(this)">
                </td>
                <td style="background: #f0f9ff; padding: 6px;" class="event-field">
                    <input type="time" name="tag_bis[]" class="regular-text event-time-input" style="width: 100%; padding: 3px 6px; font-size: 12px; height: 26px;" onchange="checkTimeOverMidnight(this)">
                    <div class="time-next-day" style="font-size: 0.65rem; color: #dc2626; margin-top: 0.1rem; font-weight: 600;"></div>
                </td>
                <td style="background: #fef3c7; padding: 6px;">
                    <input type="time" name="dienst_von[]" class="regular-text" style="width: 100%; padding: 3px 6px; font-size: 12px; height: 26px;" onchange="checkTimeOverMidnight(this)">
                </td>
                <td style="background: #fef3c7; padding: 6px;">
                    <input type="time" name="dienst_bis[]" class="regular-text" style="width: 100%; padding: 3px 6px; font-size: 12px; height: 26px;" onchange="checkTimeOverMidnight(this)">
                    <div class="dienst-time-next-day" style="font-size: 0.65rem; color: #dc2626; margin-top: 0.1rem; font-weight: 600;"></div>
                </td>
                <td style="padding: 6px;">
                    <input type="text" name="tag_notizen[]" class="regular-text" placeholder="Optional..." style="width: 100%; padding: 3px 6px; font-size: 12px; height: 26px;">
                </td>
                <td style="text-align: center; padding: 6px;">
                    <button type="button" class="button button-small" onclick="removeTag(${tagCounter})" ${tagCounter === 1 ? 'disabled' : ''} title="Entfernen" style="height: 26px; padding: 0 6px; min-width: 26px;">
                        <span class="dashicons dashicons-trash" style="margin: 0; font-size: 14px;"></span>
                    </button>
                </td>
            </tr>
        `;
        
        $('#tage-tbody').append(tagHtml);
        
        // Datumsvorschlag: Nehme das Datum vom ersten Tag und addiere Tage
        const firstDateInput = $('#tage-tbody tr:first input[name="tag_datum[]"]');
        const newDateInput = $('#tage-tbody tr:last input[name="tag_datum[]"]');
        
        if (firstDateInput.length && firstDateInput.val()) {
            const firstDate = new Date(firstDateInput.val());
            const tagIndex = $('#tage-tbody tr').length - 1; // Aktueller Tag (0-basiert)
            firstDate.setDate(firstDate.getDate() + tagIndex);
            
            // Format: YYYY-MM-DD
            const year = firstDate.getFullYear();
            const month = String(firstDate.getMonth() + 1).padStart(2, '0');
            const day = String(firstDate.getDate()).padStart(2, '0');
            newDateInput.val(`${year}-${month}-${day}`);
            
            // Wochentag anzeigen
            updateWeekday(newDateInput[0]);
        }
    };
    
    window.removeTag = function(tagId) {
        if ($('#tage-tbody tr').length > 1) {
            $(`#tage-tbody tr[data-tag-id="${tagId}"]`).remove();
            renumberTags();
        }
    };
    
    function renumberTags() {
        $('#tage-tbody tr').each(function(index) {
            $(this).find('td:first').text(index + 1);
            if (index === 0) {
                $(this).find('button[onclick^="removeTag"]').prop('disabled', true);
            } else {
                $(this).find('button[onclick^="removeTag"]').prop('disabled', false);
            }
        });
    }
    
    function updateFollowingDates() {
        const firstDateInput = $('#tage-tbody tr:first input[name="tag_datum[]"]');
        const firstDateValue = firstDateInput.val();
        
        if (!firstDateValue) return;
        
        const firstDate = new Date(firstDateValue);
        
        $('#tage-tbody tr').each(function(index) {
            if (index === 0) return; // Ersten Tag überspringen
            
            const newDate = new Date(firstDate);
            newDate.setDate(firstDate.getDate() + index);
            
            // Format: YYYY-MM-DD
            const year = newDate.getFullYear();
            const month = String(newDate.getMonth() + 1).padStart(2, '0');
            const day = String(newDate.getDate()).padStart(2, '0');
            
            const dateInput = $(this).find('input[name="tag_datum[]"]');
            dateInput.val(`${year}-${month}-${day}`);
            
            // Wochentag aktualisieren
            updateWeekday(dateInput[0]);
        });
    }
    
    window.updateWeekday = function(input) {
        const dateValue = $(input).val();
        if (!dateValue) {
            $(input).siblings('.weekday-display').text('');
            return;
        }
        
        const date = new Date(dateValue + 'T00:00:00'); // Fix für Zeitzone
        const weekdays = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
        const weekday = weekdays[date.getDay()];
        
        // Wochentag ohne Icon anzeigen
        const weekdayHtml = `<span style="font-weight: 600; color: #666;">${weekday}</span>`;
        $(input).siblings('.weekday-display').html(weekdayHtml);
    };
    
    window.toggleEventFields = function(checkbox) {
        const $row = $(checkbox).closest('tr');
        const isNurDienst = $(checkbox).is(':checked');
        
        // Finde Event-Zeit-Felder
        const $eventFields = $row.find('.event-field');
        const $eventInputs = $row.find('.event-time-input');
        
        if (isNurDienst) {
            // Deaktiviere Event-Felder
            $eventFields.css({
                'background': '#f5f5f5',
                'opacity': '0.5'
            });
            $eventInputs.prop('disabled', true).val('');
            $row.find('.time-next-day').html('');
        } else {
            // Aktiviere Event-Felder
            $eventFields.css({
                'background': '#f0f9ff',
                'opacity': '1'
            });
            $eventInputs.prop('disabled', false);
        }
    };
    
    window.checkTimeOverMidnight = function(input) {
        const $row = $(input).closest('tr');
        const $vonInput = $row.find('input[name="tag_von[]"]');
        const $bisInput = $row.find('input[name="tag_bis[]"]');
        const $dienstVonInput = $row.find('input[name="dienst_von[]"]');
        const $dienstBisInput = $row.find('input[name="dienst_bis[]"]');
        
        // Prüfe Veranstaltungszeit (nur wenn nicht deaktiviert)
        const vonZeit = $vonInput.val();
        const bisZeit = $bisInput.val();
        if (vonZeit && bisZeit && bisZeit < vonZeit && !$vonInput.prop('disabled')) {
            $bisInput.siblings('.time-next-day').html(
                '<span class="dashicons dashicons-clock" style="font-size: 0.75rem; margin-right: 0.25rem;"></span>+1 Tag'
            );
        } else {
            $bisInput.siblings('.time-next-day').html('');
        }
        
        // Prüfe Dienstzeit
        const dienstVon = $dienstVonInput.val();
        const dienstBis = $dienstBisInput.val();
        if (dienstVon && dienstBis && dienstBis < dienstVon) {
            $dienstBisInput.siblings('.dienst-time-next-day').html(
                '<span class="dashicons dashicons-clock" style="font-size: 0.75rem; margin-right: 0.25rem;"></span>+1 Tag'
            );
        } else {
            $dienstBisInput.siblings('.dienst-time-next-day').html('');
        }
    };
    
    window.saveVeranstaltung = function() {
        console.log('saveVeranstaltung');
        
        // Validierung
        if (!$('#v_name').val()) {
            alert('Bitte Name eingeben!');
            return;
        }
        
        const tage = [];
        $('#tage-tbody tr').each(function(index) {
            const datum = $(this).find('input[name="tag_datum[]"]').val();
            if (!datum) {
                alert('Bitte Datum für Tag ' + (index + 1) + ' eingeben!');
                return false;
            }
            
            tage.push({
                datum: datum,
                von_zeit: $(this).find('input[name="tag_von[]"]').val() || null,
                bis_zeit: $(this).find('input[name="tag_bis[]"]').val() || null,
                dienst_von: $(this).find('input[name="dienst_von[]"]').val() || null,
                dienst_bis: $(this).find('input[name="dienst_bis[]"]').val() || null,
                nur_dienst: $(this).find('input[name="nur_dienst[]"]').is(':checked') ? '1' : '0',
                notizen: $(this).find('input[name="tag_notizen[]"]').val() || null
            });
        });
        
        const vereineIds = [];
        $('.verein-checkbox:checked').each(function() {
            vereineIds.push($(this).val());
        });
        
        // Sammle alle gecheckte Verantwortlichen-Checkboxen
        const verantwortlicheIds = [];
        $('#v_verantwortliche-checkboxes input[type="checkbox"]:checked').each(function() {
            verantwortlicheIds.push($(this).val());
        });
        
        const formData = {
            action: 'dp_save_veranstaltung',
            nonce: dpAjax.nonce,
            veranstaltung_id: $('#veranstaltung_id').val(),
            titel: $('#v_name').val(),
            beschreibung: $('#v_beschreibung').val(),
            ort: '', // Kann später hinzugefügt werden
            status: $('#v_status').val(),
            tage: JSON.stringify(tage),
            vereine: JSON.stringify(vereineIds),
            verantwortliche: verantwortlicheIds
        };
        
        console.log('Sende Daten:', formData);
        console.log('Tage:', tage);
        console.log('Vereine:', vereineIds);
        console.log('Verantwortliche:', verantwortlicheIds);
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Response:', response);
                if (response.success) {
                    $('#veranstaltung-modal').hide();
                    const msg = $('<div>').css({
                        position: 'fixed',
                        top: '20px',
                        right: '20px',
                        background: 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)',
                        color: 'white',
                        padding: '1rem 2rem',
                        borderRadius: '0.5rem',
                        boxShadow: '0 10px 25px rgba(0,0,0,0.2)',
                        zIndex: 999999,
                        fontWeight: '600'
                    }).text('✓ Gespeichert!');
                    $('body').append(msg);
                    setTimeout(() => { if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); } }, 500);
                } else {
                    alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error Details:');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response Text:', xhr.responseText);
                console.error('Status Code:', xhr.status);
                console.error('Ready State:', xhr.readyState);
                
                try {
                    const errorData = JSON.parse(xhr.responseText);
                    console.error('Parsed Error:', errorData);
                    alert('Fehler beim Speichern:\n' + (errorData.data ? errorData.data.message : error));
                } catch (e) {
                    alert('Fehler beim Speichern:\n' + xhr.responseText.substring(0, 200));
                }
            }
        });
    };
    
    window.editVeranstaltung = function(veranstaltungId) {
        console.log('editVeranstaltung', veranstaltungId);
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_get_veranstaltung',
                nonce: dpAjax.nonce,
                veranstaltung_id: veranstaltungId
            },
            success: function(response) {
                console.log('Veranstaltung geladen:', response);
                
                if (response.success && response.data) {
                    const v = response.data;
                    
                    // Formular füllen
                    $('#veranstaltung_id').val(v.id);
                    $('#v_name').val(v.name);
                    $('#v_beschreibung').val(v.beschreibung || '');
                    $('#v_typ').val(v.typ || 'mehrtaegig');
                    $('#v_status').val(v.status || 'geplant');
                    
                    // Modal-Titel ändern
                    $('#veranstaltung-modal-title').text('Veranstaltung bearbeiten');
                    
                    // Tage laden
                    $('#tage-tbody').empty();
                    tagCounter = 0;
                    
                    if (v.tage && v.tage.length > 0) {
                        v.tage.forEach(function(tag) {
                            tagCounter++;
                            const rowNumber = tagCounter;
                            
                            const tagHtml = `
                                <tr class="tag-row" data-tag-id="${tagCounter}">
                                    <td style="text-align: center; font-weight: 600; color: var(--dp-primary); padding: 6px 4px;">${rowNumber}</td>
                                    <td style="padding: 6px;">
                                        <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: nowrap;">
                                            <input type="date" name="tag_datum[]" class="regular-text datum-input" required style="width: 130px; padding: 3px 6px; font-size: 12px; height: 26px;" value="${tag.tag_datum}" onchange="updateWeekday(this)">
                                            <div class="weekday-display" style="font-size: 0.7rem; color: var(--dp-gray-500); min-width: 65px;"></div>
                                        </div>
                                        <label style="display: inline-flex; align-items: center; gap: 0.2rem; font-size: 0.65rem; cursor: pointer; padding: 0.1rem 0.25rem; background: #fff3cd; border-radius: 3px; white-space: nowrap; margin-top: 0.2rem;">
                                            <input type="checkbox" name="nur_dienst[]" class="nur-dienst-checkbox" value="1" ${tag.nur_dienst == 1 ? 'checked' : ''} onchange="toggleEventFields(this)" style="margin: 0;">
                                            <span style="font-weight: 600; color: #856404;">🔧 Nur Dienst</span>
                                        </label>
                                    </td>
                                    <td style="background: #f0f9ff; padding: 6px;" class="event-field">
                                        <input type="time" name="tag_von[]" class="regular-text event-time-input" style="width: 100%; padding: 3px 6px; font-size: 12px; height: 26px;" value="${tag.von_zeit || ''}" onchange="checkTimeOverMidnight(this)">
                                    </td>
                                    <td style="background: #f0f9ff; padding: 6px;" class="event-field">
                                        <input type="time" name="tag_bis[]" class="regular-text event-time-input" style="width: 100%; padding: 3px 6px; font-size: 12px; height: 26px;" value="${tag.bis_zeit || ''}" onchange="checkTimeOverMidnight(this)">
                                        <div class="time-next-day" style="font-size: 0.65rem; color: #dc2626; margin-top: 0.1rem; font-weight: 600;"></div>
                                    </td>
                                    <td style="background: #fef3c7; padding: 6px;">
                                        <input type="time" name="dienst_von[]" class="regular-text" style="width: 100%; padding: 3px 6px; font-size: 12px; height: 26px;" value="${tag.dienst_von_zeit || ''}" onchange="checkTimeOverMidnight(this)">
                                    </td>
                                    <td style="background: #fef3c7; padding: 6px;">
                                        <input type="time" name="dienst_bis[]" class="regular-text" style="width: 100%; padding: 3px 6px; font-size: 12px; height: 26px;" value="${tag.dienst_bis_zeit || ''}" onchange="checkTimeOverMidnight(this)">
                                        <div class="dienst-time-next-day" style="font-size: 0.65rem; color: #dc2626; margin-top: 0.1rem; font-weight: 600;"></div>
                                    </td>
                                    <td style="padding: 6px;">
                                        <input type="text" name="tag_notizen[]" class="regular-text" placeholder="Optional..." style="width: 100%; padding: 3px 6px; font-size: 12px; height: 26px;" value="${tag.notizen || ''}">
                                    </td>
                                    <td style="text-align: center; padding: 6px;">
                                        <button type="button" class="button button-small" onclick="removeTag(${tagCounter})" ${tagCounter === 1 ? 'disabled' : ''} title="Entfernen" style="height: 26px; padding: 0 6px; min-width: 26px;">
                                            <span class="dashicons dashicons-trash" style="margin: 0; font-size: 14px;"></span>
                                        </button>
                                    </td>
                                </tr>
                            `;
                            
                            $('#tage-tbody').append(tagHtml);
                            
                            // Wochentag für geladenes Datum anzeigen
                            const dateInput = $('#tage-tbody tr:last input[name="tag_datum[]"]')[0];
                            updateWeekday(dateInput);
                            
                            // Zeit-über-Mitternacht Anzeige prüfen
                            const vonInput = $('#tage-tbody tr:last input[name="tag_von[]"]')[0];
                            checkTimeOverMidnight(vonInput);
                            
                            // Event-Felder deaktivieren wenn "Nur Dienst" aktiviert ist
                            const nurDienstCheckbox = $('#tage-tbody tr:last .nur-dienst-checkbox')[0];
                            if (nurDienstCheckbox && nurDienstCheckbox.checked) {
                                toggleEventFields(nurDienstCheckbox);
                            }
                        });
                    } else {
                        addTag();
                    }
                    
                    // Vereine auswählen
                    $('.verein-checkbox').prop('checked', false);
                    if (v.vereine && v.vereine.length > 0) {
                        v.vereine.forEach(function(vereinId) {
                            $(`.verein-checkbox[value="${vereinId}"]`).prop('checked', true);
                        });
                    }
                    
                    // Verantwortlichen-Checkboxen dynamisch befüllen
                    const verantwortlicheIds = v.verantwortliche || [];
                    console.log('Veranstaltung Verantwortliche IDs aus DB:', verantwortlicheIds);
                    $('#v_verantwortliche-checkboxes').html('<p style="color: #666; margin: 0;">Lädt...</p>');
                    
                    // Lade erst alle Benutzer, dann markiere zugewiesene
                    $.ajax({
                        url: dpAjax.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'dp_get_all_users',
                            nonce: dpAjax.nonce
                        },
                        success: function(usersResponse) {
                            if (usersResponse.success && usersResponse.data) {
                                let html = '';
                                
                                usersResponse.data.forEach(function(user) {
                                    const isSelected = verantwortlicheIds.includes(String(user.id)) || 
                                                      verantwortlicheIds.includes(parseInt(user.id)) ||
                                                      verantwortlicheIds.map(String).includes(String(user.id));
                                    
                                    const name = user.name || 'Unbekannt';
                                    const email = user.email || '';
                                    html += '<label style="display: block; padding: 0.5rem; cursor: pointer; border-radius: 4px; margin-bottom: 0.25rem;" ' +
                                            'onmouseover="this.style.background=\'#f0f6fc\'" ' +
                                            'onmouseout="this.style.background=\'transparent\'">' +
                                            '<input type="checkbox" name="verantwortliche[]" value="' + user.id + '"' + 
                                            (isSelected ? ' checked' : '') + 
                                            ' style="margin-right: 0.5rem;"> ' +
                                            '<strong>' + name + '</strong>' +
                                            (email ? ' <span style="color: #666;">(' + email + ')</span>' : '') +
                                            '</label>';
                                });
                                
                                $('#v_verantwortliche-checkboxes').html(html || '<p style="color: #999; margin: 0;">Keine Benutzer gefunden</p>');
                            } else {
                                $('#v_verantwortliche-checkboxes').html('<p style="color: #999; margin: 0;">Keine Benutzer gefunden</p>');
                            }
                        }
                    });
                    
                    // Event Listener für erstes Datumsfeld
                    setTimeout(() => {
                        $('#tage-tbody tr:first input[name="tag_datum[]"]').on('change', function() {
                            updateFollowingDates();
                        });
                    }, 100);
                    
                    // Modal öffnen
                    $('#veranstaltung-modal').css('display', 'flex');
                } else {
                    alert('Fehler: Veranstaltung nicht gefunden');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('Fehler beim Laden der Veranstaltung');
            }
        });
    };
    
    window.deleteVeranstaltung = function(veranstaltungId) {
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_delete_veranstaltung',
                nonce: dpAjax.nonce,
                veranstaltung_id: veranstaltungId,
                delete_dienste: false
            },
            success: function(response) {
                if (response.success) {
                    if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
                } else if (response.data && response.data.message === 'confirm_delete_dienste') {
                    const dienstCount = response.data.dienste_count;
                    const message = `Diese Veranstaltung hat ${dienstCount} Dienst${dienstCount > 1 ? 'e' : ''}.\n\n` +
                                  `Möchten Sie die Veranstaltung inklusive aller Dienste und Zuordnungen löschen?\n\n` +
                                  `⚠️ Dies kann nicht rückgängig gemacht werden!`;
                    
                    if (confirm(message)) {
                        $.ajax({
                            url: dpAjax.ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'dp_delete_veranstaltung',
                                nonce: dpAjax.nonce,
                                veranstaltung_id: veranstaltungId,
                                delete_dienste: true
                            },
                            success: function(deleteResponse) {
                                if (deleteResponse.success) {
                                    const deletedCount = deleteResponse.data.dienste_deleted || 0;
                                    alert(`Veranstaltung und ${deletedCount} Dienst${deletedCount > 1 ? 'e' : ''} wurden gelöscht.`);
                                    if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
                                } else {
                                    alert('Fehler: ' + (deleteResponse.data ? deleteResponse.data.message : 'Unbekannt'));
                                }
                            },
                            error: function() {
                                alert('Fehler beim Löschen');
                            }
                        });
                    }
                } else {
                    alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('Fehler beim Löschen');
            }
        });
    };
    
    // ESC-Taste
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#veranstaltung-modal').is(':visible')) {
            closeVeranstaltungModal();
        }
    });
    
    // Neuer Kontakt für Veranstaltung
    window.openNewContactModalVeranstaltung = function() {
        // Öffne Mitarbeiter-Modal für neue Verantwortliche
        window.veranstaltungModalIsOpen = true;
        openMitarbeiterModal();
    };
    
    // Funktion zum Neuladen der Verantwortlichen-Liste
    window.reloadVerantwortlicheList = function() {
        console.log('Lade Verantwortliche neu...');
        $('#v_verantwortliche-checkboxes').html('<p style="color: #666; margin: 0;">Lädt...</p>');
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_get_all_users',
                nonce: dpAjax.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    let html = '';
                    response.data.forEach(function(user) {
                        const name = user.name || 'Unbekannt';
                        const email = user.email || '';
                        html += '<label style="display: block; padding: 0.5rem; cursor: pointer; border-radius: 4px; margin-bottom: 0.25rem;" ' +
                                'onmouseover="this.style.background=\'#f0f6fc\'" ' +
                                'onmouseout="this.style.background=\'transparent\'">' +
                                '<input type="checkbox" name="verantwortliche[]" value="' + user.id + '" style="margin-right: 0.5rem;"> ' +
                                '<strong>' + name + '</strong>' +
                                (email ? ' <span style="color: #666;">(' + email + ')</span>' : '') +
                                '</label>';
                    });
                    $('#v_verantwortliche-checkboxes').html(html || '<p style="color: #999; margin: 0;">Keine Benutzer gefunden</p>');
                } else {
                    $('#v_verantwortliche-checkboxes').html('<p style="color: #999; margin: 0;">Keine Benutzer gefunden</p>');
                }
            },
            error: function() {
                $('#v_verantwortliche-checkboxes').html('<p style="color: #dc2626; margin: 0;">Fehler beim Laden</p>');
            }
        });
    };
    
    // Manuelle Seiten-Erstellung für Veranstaltung
    window.createPageForEvent = function(veranstaltungId) {
        console.log('createPageForEvent aufgerufen für ID:', veranstaltungId);
        
        if (!confirm('Möchten Sie eine WordPress-Seite für diese Veranstaltung erstellen?')) {
            return;
        }
        
        const $btn = $('button[onclick*="createPageForEvent(' + veranstaltungId + ')"]');
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Erstelle Seite...');
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_create_event_page',
                nonce: dpAjax.nonce,
                veranstaltung_id: veranstaltungId
            },
            success: function(response) {
                if (response.success) {
                    const msg = $('<div>').css({
                        position: 'fixed',
                        top: '20px',
                        right: '20px',
                        background: 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)',
                        color: 'white',
                        padding: '1rem 2rem',
                        borderRadius: '0.5rem',
                        boxShadow: '0 10px 25px rgba(0,0,0,0.2)',
                        zIndex: 999999,
                        fontWeight: '600'
                    }).text('✓ Seite erstellt!');
                    $('body').append(msg);
                    setTimeout(() => { if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); } }, 800);
                } else {
                    $btn.prop('disabled', false).html(originalHtml);
                    alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                }
            },
            error: function(xhr, status, error) {
                $btn.prop('disabled', false).html(originalHtml);
                alert('AJAX Fehler: ' + error);
            }
        });
    };
    
    // Seiten-Aktualisierung für Veranstaltung
    window.updatePageForEvent = function(veranstaltungId) {
        console.log('updatePageForEvent aufgerufen für ID:', veranstaltungId);
        
        if (!confirm('Möchten Sie den Seiteninhalt auf das neue Format aktualisieren?\n\nDer Inhalt wird auf nur den Shortcode [dienstplan] reduziert.')) {
            return;
        }
        
        const $btn = $('button[onclick*="updatePageForEvent(' + veranstaltungId + ')"]');
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Aktualisiere...');
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_update_event_page',
                nonce: dpAjax.nonce,
                veranstaltung_id: veranstaltungId
            },
            success: function(response) {
                if (response.success) {
                    const msg = $('<div>').css({
                        position: 'fixed',
                        top: '20px',
                        right: '20px',
                        background: 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)',
                        color: 'white',
                        padding: '1rem 2rem',
                        borderRadius: '0.5rem',
                        boxShadow: '0 10px 25px rgba(0,0,0,0.2)',
                        zIndex: 999999,
                        fontWeight: '600'
                    }).text('✓ Seite aktualisiert!');
                    $('body').append(msg);
                    setTimeout(() => { if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); } }, 800);
                } else {
                    $btn.prop('disabled', false).html(originalHtml);
                    alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                }
            },
            error: function(xhr, status, error) {
                $btn.prop('disabled', false).html(originalHtml);
                alert('AJAX Fehler: ' + error);
            }
        });
    };
    
})(jQuery);

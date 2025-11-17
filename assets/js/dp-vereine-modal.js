/**
 * Dienstplan Verwaltung - Vereine Modal JavaScript
 * Verwaltet das Modal für Vereine und Neuer Kontakt
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('=== VEREINE MODAL SCRIPT START ===');

        // Teste ob Modal im DOM ist
        var modal = $('#verein-modal');
        
        if (modal.length === 0) {
            console.log('INFO: Verein-Modal nicht auf dieser Seite vorhanden (normal für andere Admin-Seiten)');
            return; // Beende Script, wenn Modal nicht vorhanden
        }
        
        console.log('Verein-Modal gefunden und bereit');

        // Test-Funktion zum direkten Öffnen
        window.testModal = function() {
            console.log('TEST: Modal öffnen');
            $('#verein-modal').css({
                'display': 'flex',
                'position': 'fixed',
                'z-index': '100000'
            });
            console.log('Modal Display:', $('#verein-modal').css('display'));
        };

        // E-Mail-Prüfung (wird nicht mehr benötigt, kann aber für manuelle Eingaben bleiben)
        let emailCheckTimeout;
        $('#kontakt_email').on('input', function() {
            const email = $(this).val().trim();

            clearTimeout(emailCheckTimeout);
            $('#email-check-result').html('');
            $('#user-invite-row').hide();

            if (!email || !isValidEmail(email)) {
                return;
            }

            emailCheckTimeout = setTimeout(function() {
                checkEmailExists(email);
            }, 500);
        });

        // Checkbox-Handler für Benutzer erstellen
        $('#create_user').on('change', function() {
            if ($(this).is(':checked')) {
                $('#user-role-select').show();
            } else {
                $('#user-role-select').hide();
            }
        });

        // Modal schließen bei Klick außerhalb
        $(document).on('click', '.dp-modal', function(e) {
            if (e.target === this) {
                closeVereinModal();
            }
        });

        // ESC-Taste
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#verein-modal').is(':visible')) {
                closeVereinModal();
            }
        });

        // === NEW CONTACT MODAL ===
        console.log('=== NEW CONTACT MODAL SCRIPT LOADED ===');

        // Test ob Modal existiert
        console.log('Modal exists:', $('#new-contact-modal').length > 0);
        console.log('Modal element:', $('#new-contact-modal'));

        // E-Mail Validierung während Eingabe
        let ncEmailCheckTimeout;
        $('#nc_email').on('input', function() {
            const email = $(this).val().trim();

            clearTimeout(ncEmailCheckTimeout);
            $('#nc-email-check-result').html('');

            if (!email || !isValidEmail(email)) {
                return;
            }

            ncEmailCheckTimeout = setTimeout(function() {
                $.ajax({
                    url: dpAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dp_check_email',
                        email: email,
                        nonce: dpAjax.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.exists) {
                            $('#nc-email-check-result').html(
                                '<span style="color: #dc2626;"><span class="dashicons dashicons-warning"></span> ' +
                                'Benutzer existiert bereits: <strong>' + response.data.user_name + '</strong></span>'
                            );
                        } else {
                            $('#nc-email-check-result').html(
                                '<span style="color: #00a32a;"><span class="dashicons dashicons-yes-alt"></span> ' +
                                'E-Mail ist verfügbar</span>'
                            );
                        }
                    }
                });
            }, 500);
        });

        // Modal bei Klick außerhalb schließen
        $(document).on('click', '#new-contact-modal', function(e) {
            if (e.target === this) {
                closeNewContactModal();
            }
        });

        // ESC-Taste für New Contact Modal
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#new-contact-modal').is(':visible')) {
                closeNewContactModal();
            }
        });
    });

    // === Hilfsfunktionen ===

    /**
     * WordPress Media Uploader für Logo
     */
    $(document).on('click', '#upload-logo-btn', function(e) {
        e.preventDefault();
        
        var mediaUploader;
        
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        mediaUploader = wp.media({
            title: 'Logo auswählen',
            button: {
                text: 'Logo verwenden'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#logo_id').val(attachment.id);
            $('#logo-preview').html('<img src="' + attachment.url + '" style="max-width: 150px; max-height: 150px; border-radius: 4px; border: 1px solid #ddd;">');
            $('#remove-logo-btn').show();
        });
        
        mediaUploader.open();
    });
    
    /**
     * Logo entfernen
     */
    $(document).on('click', '#remove-logo-btn', function(e) {
        e.preventDefault();
        $('#logo_id').val('');
        $('#logo-preview').html('');
        $(this).hide();
    });

    /**
     * Globale Hilfsfunktion für E-Mail-Validierung
     */
    window.isValidEmail = function(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    };

    /**
     * Prüft ob Email bereits existiert
     */
    function checkEmailExists(email) {
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_check_email',
                email: email,
                nonce: dpAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.exists) {
                        // Benutzer existiert bereits
                        $('#email-check-result').html(
                            '<span style="color: #00a32a;"><span class="dashicons dashicons-yes-alt"></span> ' +
                            'WordPress-Benutzer existiert: <strong>' + response.data.user_name + '</strong></span>'
                        );
                    } else {
                        // Benutzer existiert nicht - Einladung anbieten
                        $('#email-check-result').html(
                            '<span style="color: #dba617;"><span class="dashicons dashicons-info"></span> ' +
                            'Kein WordPress-Benutzer mit dieser E-Mail-Adresse gefunden</span>'
                        );
                        $('#user-invite-row').show();
                    }
                }
            }
        });
    }

    // === VEREIN MODAL FUNKTIONEN ===

    /**
     * Öffnet das Verein-Modal für einen neuen Verein
     */
    window.openVereinModal = function() {
        console.log('openVereinModal called');
        try {
            $('#verein-form')[0].reset();
            $('#verein_id').val('');
            $('#logo_id').val('');
            $('#logo-preview').html('');
            $('#remove-logo-btn').hide();
            $('#modal-title').text('Neuer Verein');
            $('#user-invite-row').hide();

            // Lade alle Benutzer für die Checkbox-Auswahl
            $('#verantwortliche-checkboxes').html('<p style="color: #666; margin: 0;">Lädt...</p>');

            $.ajax({
                url: dpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dp_get_all_users',
                    nonce: dpAjax.nonce
                },
                success: function(response) {
                    console.log('Users geladen:', response);
                    if (response.success && response.data) {
                        let html = '';

                        response.data.forEach(function(user) {
                            html += `
                                <label style="display: block; padding: 0.5rem; cursor: pointer; border-radius: 3px; transition: background 0.2s;" 
                                       onmouseover="this.style.background='#f0f6fc'" 
                                       onmouseout="this.style.background='transparent'">
                                    <input type="checkbox" name="verantwortliche[]" value="${user.id}">
                                    <strong>${user.name}</strong>
                                    <span style="color: #666; font-size: 0.9em;">(${user.email})</span>
                                </label>
                            `;
                        });

                        $('#verantwortliche-checkboxes').html(html || '<p style="color: #666; margin: 0;">Keine Benutzer verfügbar</p>');
                        console.log('Checkboxen befüllt');
                    } else {
                        $('#verantwortliche-checkboxes').html('<p style="color: #dc2626; margin: 0;">Fehler beim Laden der Benutzer</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Fehler beim Laden der Benutzer:', error);
                    $('#verantwortliche-checkboxes').html('<p style="color: #dc2626; margin: 0;">Fehler: ' + error + '</p>');
                }
            });

            $('#verein-modal').css('display', 'flex');
            console.log('Modal Display nach Öffnen:', $('#verein-modal').css('display'));
        } catch(e) {
            console.error('Fehler in openVereinModal:', e);
            alert('Fehler: ' + e.message);
        }
    };

    /**
     * Schließt das Verein-Modal
     */
    window.closeVereinModal = function() {
        console.log('closeVereinModal called');
        $('#verein-modal').hide();
    };

    /**
     * Öffnet das Verein-Modal zum Bearbeiten
     */
    window.editVerein = function(id) {
        console.log('editVerein called, ID:', id);

        if (typeof dpAjax === 'undefined') {
            console.error('dpAjax ist nicht definiert!');
            alert('FEHLER: dpAjax ist nicht definiert!');
            return;
        }

        console.log('Sende AJAX Request...');
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_get_verein',
                verein_id: id,
                nonce: dpAjax.nonce
            },
            success: function(response) {
                console.log('AJAX Success:', response);
                if (response.success && response.data) {
                    const v = response.data;
                    console.log('Verein Daten vollständig:', v);
                    console.log('v.verantwortliche:', v.verantwortliche);

                    // WICHTIG: Formular NICHT resetten beim Bearbeiten!

                    $('#verein_id').val(v.id);
                    $('#name').val(v.name);
                    $('#kuerzel').val(v.kuerzel);
                    $('#beschreibung').val(v.beschreibung || '');

                    console.log('Basis-Felder gesetzt');

                    // Logo anzeigen
                    if (v.logo_id && v.logo_url) {
                        $('#logo_id').val(v.logo_id);
                        $('#logo-preview').html(`<img src="${v.logo_url}" style="max-width: 150px; max-height: 150px; border-radius: 4px; border: 1px solid #ddd;">`);
                        $('#remove-logo-btn').show();
                    } else {
                        $('#logo_id').val('');
                        $('#logo-preview').html('');
                        $('#remove-logo-btn').hide();
                    }

                    // Lade erst alle Benutzer, dann markiere zugewiesene
                    // WICHTIG: Speichere die IDs BEVOR der AJAX-Call startet
                    const verantwortlicheIds = v.verantwortliche || [];
                    console.log('Verein Verantwortliche IDs aus DB:', verantwortlicheIds);
                    console.log('Typ der IDs:', typeof verantwortlicheIds, Array.isArray(verantwortlicheIds));
                    $('#verantwortliche-checkboxes').html('<p style="color: #666; margin: 0;">Lädt...</p>');

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
                                    console.log('Checking user:', user.id, 'type:', typeof user.id);
                                    console.log('IDs in array:', verantwortlicheIds, 'types:', verantwortlicheIds.map(id => typeof id));
                                    // Vergleiche sowohl als String als auch als Number
                                    const isSelected = verantwortlicheIds.includes(String(user.id)) ||
                                                      verantwortlicheIds.includes(parseInt(user.id)) ||
                                                      verantwortlicheIds.map(String).includes(String(user.id));
                                    console.log(`Verein User ${user.id} (${user.name}): Selected = ${isSelected}`);

                                    html += `
                                        <label style="display: block; padding: 0.5rem; cursor: pointer; border-radius: 3px; transition: background 0.2s;" 
                                               onmouseover="this.style.background='#f0f6fc'" 
                                               onmouseout="this.style.background='transparent'">
                                            <input type="checkbox" name="verantwortliche[]" value="${user.id}" ${isSelected ? 'checked' : ''}>
                                            <strong>${user.name}</strong>
                                            <span style="color: #666; font-size: 0.9em;">(${user.email})</span>
                                        </label>
                                    `;
                                });

                                $('#verantwortliche-checkboxes').html(html || '<p style="color: #666; margin: 0;">Keine Benutzer verfügbar</p>');
                                console.log('Verein Verantwortliche Checkboxen befüllt');
                            }
                        }
                    });

                    // Legacy: Versteckte Felder setzen
                    $('#kontakt_name').val(v.kontakt_name || '');
                    $('#kontakt_email').val(v.kontakt_email || '');
                    $('#kontakt_telefon').val(v.kontakt_telefon || '');
                    $('#aktiv').prop('checked', v.aktiv == 1);
                    $('#modal-title').text('Verein bearbeiten');
                    $('#verein-modal').css('display', 'flex');
                    console.log('Modal sollte jetzt sichtbar sein');
                } else {
                    console.error('Response Error:', response);
                    alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                console.error('Response Text:', xhr.responseText);
                alert('AJAX Fehler: ' + error);
            }
        });
    };

    /**
     * Speichert einen Verein (neu oder bearbeitet)
     */
    window.saveVerein = function() {
        console.log('saveVerein called');

        if (!$('#name').val() || !$('#kuerzel').val()) {
            alert('Bitte füllen Sie Name und Kürzel aus!');
            return;
        }

        // Sammle ausgewählte Verantwortliche aus Checkboxen
        const verantwortliche = [];
        $('input[name="verantwortliche[]"]:checked').each(function() {
            verantwortliche.push($(this).val());
        });

        const formData = {
            action: 'dp_save_verein',
            nonce: dpAjax.nonce,
            verein_id: $('#verein_id').val(),
            name: $('#name').val(),
            kuerzel: $('#kuerzel').val(),
            beschreibung: $('#beschreibung').val(),
            logo_id: $('#logo_id').val(),
            kontakt_name: $('#kontakt_name').val(),
            kontakt_email: $('#kontakt_email').val(),
            kontakt_telefon: $('#kontakt_telefon').val(),
            aktiv: $('#aktiv').is(':checked') ? 1 : 0,
            create_user: $('#create_user').is(':checked') ? 1 : 0,
            user_role: $('#user_role').val(),
            verantwortliche: verantwortliche // Array von User-IDs
        };

        console.log('Sende Save Request:', formData);

        // Button deaktivieren während Speichern
        const $saveBtn = $('.dp-modal-footer .button-primary');
        $saveBtn.prop('disabled', true).addClass('is-loading');

        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Save Response:', response);
                $saveBtn.prop('disabled', false).removeClass('is-loading');

                if (response.success) {
                    // Modal schließen
                    $('#verein-modal').hide();

                    // Erfolgs-Nachricht (optional)
                    if (response.data && response.data.message) {
                        // Kurze Nachricht vor Reload
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
                        }).text('✓ ' + response.data.message);
                        $('body').append(msg);
                    }

                    // Seite nach kurzer Verzögerung neu laden
                    setTimeout(function() {
                        if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
                    }, 500);
                } else {
                    alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannter Fehler'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                $saveBtn.prop('disabled', false).removeClass('is-loading');
                alert('AJAX-Fehler: ' + error);
            }
        });
    };

    /**
     * Löscht einen Verein
     */
    window.deleteVerein = function(id) {
        if (!confirm('Verein wirklich löschen?')) return;

        $.post(dpAjax.ajaxurl, {
            action: 'dp_delete_verein',
            verein_id: id,
            nonce: dpAjax.nonce
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
            } else {
                alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannter Fehler'));
            }
        });
    };

    // === NEW CONTACT MODAL FUNKTIONEN ===

    /**
     * Öffnet das New Contact Modal
     */
    window.openNewContactModal = function() {
        console.log('openNewContactModal called!');
        $('#new-contact-form')[0].reset();
        $('#nc-email-check-result').html('');
        $('#new-contact-modal').css('display', 'flex');
        console.log('Modal display set to flex');
    };

    /**
     * Schließt das New Contact Modal
     */
    window.closeNewContactModal = function() {
        console.log('closeNewContactModal called');
        $('#new-contact-modal').hide();
    };

    /**
     * Neuen Kontakt speichern
     */
    window.saveNewContact = function() {
        const name = $('#nc_name').val().trim();
        const email = $('#nc_email').val().trim();
        const role = $('#nc_role').val();

        if (!name || !email) {
            alert('Bitte Name und E-Mail eingeben!');
            return;
        }

        if (!isValidEmail(email)) {
            alert('Bitte gültige E-Mail-Adresse eingeben!');
            return;
        }

        const $saveBtn = $('#new-contact-modal .button-primary');
        $saveBtn.prop('disabled', true).text('Wird erstellt...');

        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_create_new_contact',
                nonce: dpAjax.nonce,
                name: name,
                email: email,
                role: role
            },
            success: function(response) {
                $saveBtn.prop('disabled', false).html('<span class="dashicons dashicons-plus" style="margin-top: 3px;"></span> Kontakt anlegen & einladen');

                if (response.success) {
                    // Modal schließen
                    closeNewContactModal();

                    const userData = response.data;
                    const source = window.newContactSource || 'verein';
                    const selectId = source === 'veranstaltung' ? '#v_verantwortliche' : '#verantwortliche';

                    // Neuen User zum entsprechenden Dropdown hinzufügen und auswählen
                    $(selectId).append(
                        $('<option>', {
                            value: userData.user_id,
                            text: userData.user_name + ' (' + userData.user_email + ')',
                            selected: true
                        })
                    );

                    // Reset source
                    window.newContactSource = null;

                    // Erfolgsmeldung
                    alert('✓ Kontakt erfolgreich angelegt und zugewiesen!\n\nEine Einladungs-E-Mail wurde an ' + userData.user_email + ' gesendet.');
                } else {
                    alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannter Fehler'));
                }
            },
            error: function(xhr, status, error) {
                $saveBtn.prop('disabled', false).html('<span class="dashicons dashicons-plus" style="margin-top: 3px;"></span> Kontakt anlegen & einladen');
                alert('AJAX Fehler: ' + error);
            }
        });
    };

})(jQuery);

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Select All / Deselect All
        $(document).on('change', '.select-all-header', function() {
            const verein = $(this).data('verein');
            const isChecked = $(this).prop('checked');
            $('.mitarbeiter-checkbox[data-verein="' + verein + '"]').prop('checked', isChecked);
            updateBulkActionToolbar();
        });
        
        // Individual Checkbox
        $(document).on('change', '.mitarbeiter-checkbox', function() {
            updateBulkActionToolbar();
        });
        
        // Update Toolbar
        function updateBulkActionToolbar() {
            const checkedCount = $('.mitarbeiter-checkbox:checked').length;
            $('.selected-count .count').text(checkedCount);
            
            if (checkedCount > 0) {
                $('.bulk-actions-toolbar').show();
            } else {
                $('.bulk-actions-toolbar').hide();
            }
        }
        
        // Cancel Selection
        $(document).on('click', '.cancel-bulk-selection', function() {
            $('.mitarbeiter-checkbox, .select-all-header').prop('checked', false);
            updateBulkActionToolbar();
        });
        
        // Apply Bulk Action
        $(document).on('click', '.bulk-action-apply', function() {
            const $button = $(this);
            const $toolbar = $button.closest('.bulk-actions-toolbar');
            const $select = $toolbar.find('.bulk-action-select');
            const action = $select.val();
            const verein = $select.data('verein');
            const $checked = $('.mitarbeiter-checkbox[data-verein="' + verein + '"]:checked');
            
            if (!action) {
                alert('Bitte wählen Sie eine Aktion aus!');
                return;
            }
            
            if ($checked.length === 0) {
                alert('Bitte wählen Sie mindestens einen Mitarbeiter aus!');
                return;
            }
            
            const ids = [];
            $checked.each(function() {
                ids.push(parseInt($(this).val()));
            });
            
            switch(action) {
                case 'delete':
                    handleBulkDelete(ids, $button);
                    break;
                case 'activate_portal':
                    handleBulkActivatePortal(ids, $button);
                    break;
                case 'deactivate_portal':
                    handleBulkDeactivatePortal(ids, $button);
                    break;
                case 'export':
                    handleBulkExport(ids);
                    break;
                case 'export_portal':
                    handleBulkExportPortal(ids);
                    break;
                default:
                    alert('Unbekannte Aktion: ' + action);
            }
        });
        
        // Bulk Delete
        function handleBulkDelete(ids, $button) {
            const count = ids.length;
            const confirmText = count === 1 
                ? 'Möchten Sie diesen Mitarbeiter wirklich löschen?\n\nDamit werden auch alle seine Dienst-Zuweisungen gelöscht!'
                : 'Möchten Sie wirklich ' + count + ' Mitarbeiter löschen?\n\nDamit werden auch alle ihre Dienst-Zuweisungen gelöscht!';
            
            if (!confirm(confirmText)) {
                return;
            }
            
            const originalText = $button.html();
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> Wird gelöscht...');
            
            $.ajax({
                url: dpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dp_delete_mitarbeiter_bulk',
                    nonce: dpAjax.nonce,
                    mitarbeiter_ids: ids
                },
                success: function(response) {
                    console.log('Bulk Delete Response:', response);
                    if (response.success) {
                        if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); }
                    } else {
                        alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                        $button.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Fehler:', error);
                    alert('AJAX-Fehler: ' + error);
                    $button.prop('disabled', false).html(originalText);
                }
            });
        }
        
        // Bulk Activate Portal
        function handleBulkActivatePortal(ids, $button) {
            const count = ids.length;
            const confirmText = 'Möchten Sie wirklich für ' + count + ' Mitarbeiter Portal-Zugänge erstellen?\n\n' +
                'Voraussetzung: Mitarbeiter müssen eine E-Mail-Adresse haben.\n' +
                'Es werden WordPress-Benutzer angelegt und Login-Daten per E-Mail versendet.';
            
            if (!confirm(confirmText)) {
                return;
            }
            
            const originalText = $button.html();
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> Wird aktiviert...');
            
            $.ajax({
                url: dpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dp_bulk_activate_portal_access',
                    nonce: dpAjax.nonce,
                    mitarbeiter_ids: ids
                },
                success: function(response) {
                    console.log('Bulk Activate Portal Response:', response);
                    if (response.success) {
                        alert(response.data.message);
                        if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); }
                    } else {
                        alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                        $button.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Fehler:', error);
                    alert('AJAX-Fehler: ' + error);
                    $button.prop('disabled', false).html(originalText);
                }
            });
        }
        
        // Bulk Deactivate Portal
        function handleBulkDeactivatePortal(ids, $button) {
            const count = ids.length;
            const confirmText = 'Möchten Sie wirklich für ' + count + ' Mitarbeiter den Portal-Zugriff deaktivieren?\n\n' +
                'Die Mitarbeiter können sich danach nicht mehr im Portal anmelden.';
            
            if (!confirm(confirmText)) {
                return;
            }
            
            const originalText = $button.html();
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> Wird deaktiviert...');
            
            $.ajax({
                url: dpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dp_bulk_deactivate_portal_access',
                    nonce: dpAjax.nonce,
                    mitarbeiter_ids: ids
                },
                success: function(response) {
                    console.log('Bulk Deactivate Portal Response:', response);
                    if (response.success) {
                        alert(response.data.message);
                        if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); }
                    } else {
                        alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                        $button.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Fehler:', error);
                    alert('AJAX-Fehler: ' + error);
                    $button.prop('disabled', false).html(originalText);
                }
            });
        }
        
        // Bulk Export (CSV)
        function handleBulkExport(ids) {
            window.location.href = dpAjax.ajaxurl + '?action=dp_export_mitarbeiter&nonce=' + dpAjax.nonce + '&ids=' + ids.join(',');
        }
        
        // Bulk Export Portal Credentials
        function handleBulkExportPortal(ids) {
            window.location.href = dpAjax.ajaxurl + '?action=dp_export_portal_credentials&nonce=' + dpAjax.nonce + '&ids=' + ids.join(',');
        }
        
        // Alte Funktionen für Kompatibilität
        window.toggleAllMitarbeiter = function(checked) {
            $('.mitarbeiter-checkbox').prop('checked', checked);
            updateBulkActionToolbar();
        };
        
        window.updateBulkActionButton = function() {
            updateBulkActionToolbar();
        };
        
        window.applyBulkAction = function() {
            $('.bulk-action-apply').first().click();
        };
        
        // Portal-Zugriff aktivieren
        window.activatePortalAccess = function(mitarbeiterId) {
            if (!confirm('Möchten Sie wirklich einen Portal-Zugang für diesen Mitarbeiter erstellen?\n\nEs wird ein WordPress-Benutzer angelegt und die Login-Daten per E-Mail versendet.')) {
                return;
            }
            
            $.ajax({
                url: dpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dp_activate_portal_access',
                    nonce: dpAjax.nonce,
                    mitarbeiter_id: mitarbeiterId
                },
                success: function(response) {
                    console.log('Activate Portal Access Response:', response);
                    if (response.success) {
                        alert(response.data.message);
                        if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); }
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
        
        // Portal-Zugriff deaktivieren
        window.deactivatePortalAccess = function() {
            const mitarbeiterId = $('#mitarbeiter_id').val();
            
            if (!mitarbeiterId) {
                alert('Mitarbeiter-ID nicht gefunden!');
                return;
            }
            
            if (!confirm('Möchten Sie den Portal-Zugang wirklich deaktivieren?\n\nDer Mitarbeiter kann sich danach nicht mehr im Portal anmelden.')) {
                return;
            }
            
            $.ajax({
                url: dpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dp_deactivate_portal_access',
                    nonce: dpAjax.nonce,
                    mitarbeiter_id: mitarbeiterId
                },
                success: function(response) {
                    console.log('Deactivate Portal Access Response:', response);
                    if (response.success) {
                        alert(response.data.message);
                        $('#mitarbeiterModal').hide();
                        if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); }
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
        
        // Login-Daten erneut senden (aus Modal heraus, liest ID aus Formular)
        window.resendLoginCredentials = function() {
            const mitarbeiterId = $('#mitarbeiter_id').val();
            resendCredentials(mitarbeiterId);
        };

        // Zugangsdaten erneut senden – direkt mit ID (für Dropdown)
        window.resendCredentials = function(mitarbeiterId) {
            if (!mitarbeiterId) {
                alert('Mitarbeiter-ID nicht gefunden!');
                return;
            }

            if (!confirm('Möchten Sie die Login-Daten erneut per E-Mail versenden?\n\nEs wird ein neues Passwort generiert und versendet.')) {
                return;
            }

            $.ajax({
                url: dpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dp_resend_login_credentials',
                    nonce: dpAjax.nonce,
                    mitarbeiter_id: mitarbeiterId
                },
                success: function(response) {
                    if (response.success) {
                        alert('✅ ' + response.data.message);
                    } else {
                        alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX-Fehler: ' + error);
                }
            });
        };

        // Dienste-Übersicht per E-Mail senden
        window.resendDiensteEmail = function(mitarbeiterId) {
            if (!mitarbeiterId) {
                alert('Mitarbeiter-ID nicht gefunden!');
                return;
            }

            if (!confirm('Soll dem Mitarbeiter eine E-Mail mit allen zugewiesenen Diensten gesendet werden?')) {
                return;
            }

            $.ajax({
                url: dpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dp_resend_dienste_email',
                    nonce: dpAjax.nonce,
                    mitarbeiter_id: mitarbeiterId
                },
                success: function(response) {
                    if (response.success) {
                        alert('✅ ' + response.data.message);
                    } else {
                        alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX-Fehler: ' + error);
                }
            });
        };

    });

})(jQuery);

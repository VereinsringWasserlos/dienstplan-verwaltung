(function($) {
    'use strict';
    
    console.log('Veranstaltungen Table Script geladen');
    
    // toggleActionDropdown() ist jetzt in dp-admin.js definiert
    // Dropdown schließen nach Aktion
    $(document).ready(function() {
        console.log('Veranstaltungen Table: DOMContentLoaded');
        
        // CSS für Hover-Effekte
        const style = document.createElement('style');
        style.textContent = `
            .dropdown-item:hover {
                background-color: #f6f7f7 !important;
            }
            .action-dropdown-menu a:last-child {
                border-bottom: none !important;
            }
        `;
        document.head.appendChild(style);

        // Event-Listener für Dropdown-Items
        document.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', function() {
                // Dropdown nach Klick schließen
                setTimeout(() => {
                    document.querySelectorAll('.action-dropdown-menu').forEach(menu => {
                        menu.style.display = 'none';
                    });
                }, 100);
            });
        });
    });
    
    // Toggle Verein-Details
    window.toggleVereinDetails = function(veranstaltungId) {
        console.log('toggleVereinDetails aufgerufen für Veranstaltung:', veranstaltungId);
        const detailRow = jQuery('#verein-details-' + veranstaltungId);
        const button = jQuery('[data-veranstaltung-id="' + veranstaltungId + '"] .toggle-verein-details');
        const icon = button.find('.dashicons');
        
        if (detailRow.is(':visible')) {
            detailRow.slideUp(300);
            icon.removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
        } else {
            detailRow.slideDown(300);
            icon.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
        }
    };
    
    // Einzelne Verein-Seite erstellen
    window.createSingleVereinSeite = function(veranstaltungId, vereinId) {
        if (!confirm('Möchten Sie eine Anmeldeseite für diesen Verein erstellen?')) {
            return;
        }
        
        jQuery.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_create_single_verein_seite',
                nonce: dpAjax.nonce,
                veranstaltung_id: veranstaltungId,
                verein_id: vereinId
            },
            beforeSend: function() {
                jQuery('.button').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    alert('Seite erfolgreich erstellt!\n\nURL: ' + response.data.url);
                    location.reload();
                } else {
                    alert('Fehler: ' + (response.data.message || 'Unbekannter Fehler'));
                }
            },
            error: function(xhr, status, error) {
                alert('Serverfehler: ' + error);
            },
            complete: function() {
                jQuery('.button').prop('disabled', false);
            }
        });
    };
    
    // Globale Funktion für Verein-spezifische Seiten
    window.createVereinspezifischeSeiten = function(veranstaltungId) {
        if (!confirm('Möchten Sie für jeden Verein dieser Veranstaltung eine eigene Anmeldeseite erstellen?\n\nFür jeden beteiligten Verein wird eine WordPress-Seite mit dem Shortcode [dienstplan_veranstaltung] erstellt.')) {
            return;
        }
        
        jQuery.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_create_verein_seiten',
                nonce: dpAjax.nonce,
                veranstaltung_id: veranstaltungId
            },
            beforeSend: function() {
                // Button deaktivieren während der Verarbeitung
                jQuery('.action-button').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    alert('Erfolgreich!\n\n' + response.data.message + '\n\nErstellt: ' + response.data.created + ' Seite(n)');
                    location.reload(); // Seite neu laden, um aktualisierte Daten zu zeigen
                } else {
                    alert('Fehler: ' + (response.data.message || 'Unbekannter Fehler'));
                }
            },
            error: function(xhr, status, error) {
                alert('Serverfehler: ' + error);
            },
            complete: function() {
                jQuery('.action-button').prop('disabled', false);
            }
        });
    };
    
    // Einzelne Verein-Seite löschen
    window.deleteVereinSeite = function(pageId, vereinName) {
        if (!confirm('Möchten Sie die Anmeldeseite für "' + vereinName + '" wirklich löschen?\n\nDie WordPress-Seite wird permanent gelöscht.')) {
            return;
        }
        
        jQuery.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_delete_page',
                nonce: dpAjax.nonce,
                page_id: pageId
            },
            beforeSend: function() {
                jQuery('.button').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    alert('Seite erfolgreich gelöscht!');
                    location.reload();
                } else {
                    alert('Fehler: ' + (response.data.message || 'Unbekannter Fehler'));
                }
            },
            error: function(xhr, status, error) {
                alert('Serverfehler: ' + error);
            },
            complete: function() {
                jQuery('.button').prop('disabled', false);
            }
        });
    };
    
    // Quick Change Status
    window.quickChangeStatus = function(selectElement) {
        const veranstaltungId = selectElement.getAttribute('data-veranstaltung-id');
        const newStatus = selectElement.value;
        const oldValue = selectElement.getAttribute('data-old-value') || selectElement.value;
        
        if (!veranstaltungId || !newStatus) {
            alert('Fehler: Ungültige Parameter.');
            return;
        }
        
        // Bestätigungsdialog
        const statusNames = {
            'in_planung': 'In Planung',
            'geplant': 'Geplant',
            'aktiv': 'Aktiv',
            'abgeschlossen': 'Abgeschlossen'
        };
        
        if (!confirm('Status ändern zu "' + statusNames[newStatus] + '"?')) {
            selectElement.value = oldValue;
            return;
        }
        
        // Select-Element deaktivieren während der Anfrage
        selectElement.disabled = true;
        
        jQuery.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_quick_change_status',
                nonce: dpAjax.nonce,
                veranstaltung_id: veranstaltungId,
                status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    alert('Status erfolgreich geändert!');
                    selectElement.setAttribute('data-old-value', newStatus);
                    // Seite neu laden um aktualisierte Daten zu zeigen
                    location.reload();
                } else {
                    alert('Fehler: ' + (response.data.message || 'Unbekannter Fehler'));
                    selectElement.value = oldValue;
                }
            },
            error: function(xhr, status, error) {
                alert('Serverfehler: ' + error);
                selectElement.value = oldValue;
            },
            complete: function() {
                selectElement.disabled = false;
            }
        });
    };
    
    // Alle Verein-Seiten einer Veranstaltung löschen
    window.deleteAllVereinSeiten = function(veranstaltungId) {
        if (!confirm('Möchten Sie ALLE Anmeldeseiten dieser Veranstaltung wirklich löschen?\n\nAlle WordPress-Seiten, die für die Vereine dieser Veranstaltung erstellt wurden, werden permanent gelöscht.\n\nDieser Vorgang kann nicht rückgängig gemacht werden!')) {
            return;
        }
        
        jQuery.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dp_delete_all_verein_seiten',
                nonce: dpAjax.nonce,
                veranstaltung_id: veranstaltungId
            },
            beforeSend: function() {
                jQuery('.button').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    alert('Erfolgreich: ' + response.data.message);
                    location.reload();
                } else {
                    alert('Fehler: ' + (response.data.message || 'Unbekannter Fehler'));
                }
            },
            error: function(xhr, status, error) {
                alert('Serverfehler: ' + error);
            },
            complete: function() {
                jQuery('.button').prop('disabled', false);
            }
        });
    };
    
})(jQuery);

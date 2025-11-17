/**
 * Dienstplan Verwaltung - Dienste Tabelle JavaScript
 * Verwaltet die Dienste-Übersicht mit Bulk-Aktionen und Tag-Gruppierung
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('Dienste-Tabelle geladen');

        // ========================================
        // Bulk-Aktionen Handling
        // ========================================

        // Checkbox-Handling
        $('.dienst-checkbox').on('change', function() {
            console.log('Dienst-Checkbox geändert:', $(this).val(), 'Checked:', $(this).is(':checked'));
            updateBulkToolbar();
        });
        
        $('.select-all-header, .select-all-dienste, .toggle-all-in-section').on('change', function() {
            const tagId = $(this).data('tag');
            const isChecked = $(this).is(':checked');
            $('.dienst-checkbox[data-tag="' + tagId + '"]').prop('checked', isChecked).trigger('change');
        });
        
        function updateBulkToolbar() {
            console.log('updateBulkToolbar aufgerufen');
            $('.tag-dienste-gruppe').each(function() {
                const $gruppe = $(this);
                const $toolbar = $gruppe.find('.bulk-actions-toolbar');
                const $checkboxes = $gruppe.find('.dienst-checkbox:checked');
                const count = $checkboxes.length;
                
                console.log('Gruppe:', $gruppe.attr('class'), 'Gecheckte Checkboxen:', count);
                
                // Aktualisiere nur den Counter, nicht die Sichtbarkeit
                $toolbar.find('.count').text(count);
                
                // Enable/disable Apply-Button basierend auf Auswahl
                const $applyBtn = $toolbar.find('.bulk-action-apply');
                if (count > 0) {
                    console.log('  → Button wird aktiviert');
                    $applyBtn.prop('disabled', false);
                } else {
                    console.log('  → Button wird deaktiviert');
                    $applyBtn.prop('disabled', true);
                    $toolbar.find('.bulk-action-select').val('');
                }
            });
        }
        
        // Bulk-Aktion anwenden
        $('.bulk-action-apply').on('click', function() {
            const $button = $(this);
            const $gruppe = $button.closest('.tag-dienste-gruppe');
            const action = $gruppe.find('.bulk-action-select').val();
            const selectedIds = [];
            
            console.log('=== Bulk Action Debug ===');
            console.log('Button geklickt:', $button);
            console.log('Gruppe gefunden:', $gruppe.length, $gruppe);
            console.log('Alle Checkboxen in Gruppe:', $gruppe.find('.dienst-checkbox').length);
            console.log('Gecheckte Checkboxen:', $gruppe.find('.dienst-checkbox:checked').length);
            
            $gruppe.find('.dienst-checkbox:checked').each(function() {
                const val = $(this).val();
                console.log('  - Checkbox value:', val, 'Type:', typeof val);
                selectedIds.push(val);
            });
            
            console.log('Bulk Action ausgewählt:', action);
            console.log('Ausgewählte IDs:', selectedIds);
            console.log('dpAjax verfügbar:', typeof dpAjax !== 'undefined');
            console.log('dpAjax.ajaxurl:', dpAjax?.ajaxurl);
            console.log('dpAjax.nonce:', dpAjax?.nonce);
            
            if (!action) {
                alert('Bitte wählen Sie eine Aktion aus.');
                return;
            }
            
            if (selectedIds.length === 0) {
                alert('Bitte wählen Sie mindestens einen Dienst aus.');
                return;
            }
            
            handleBulkAction(action, selectedIds);
        });
        
        // Bulk-Aktion abbrechen
        $('.bulk-action-cancel').on('click', function() {
            const $gruppe = $(this).closest('.tag-dienste-gruppe');
            $gruppe.find('.dienst-checkbox').prop('checked', false);
            $gruppe.find('.select-all-header, .select-all-dienste').prop('checked', false);
            updateBulkToolbar();
        });
        
        function handleBulkAction(action, diensteIds) {
            console.log('handleBulkAction aufgerufen - Action:', action, 'IDs:', diensteIds);
            
            switch(action) {
                case 'delete':
                    console.log('→ Rufe bulkDelete auf');
                    bulkDelete(diensteIds);
                    break;
                case 'move_tag':
                    console.log('→ Rufe bulkMoveTag auf');
                    bulkMoveTag(diensteIds);
                    break;
                case 'change_time':
                    console.log('→ Rufe bulkChangeTime auf');
                    bulkChangeTime(diensteIds);
                    break;
                case 'change_verein':
                    console.log('→ Rufe bulkChangeVerein auf');
                    bulkChangeVerein(diensteIds);
                    break;
                case 'change_bereich':
                    console.log('→ Rufe bulkChangeBereich auf');
                    bulkChangeBereich(diensteIds);
                    break;
                case 'change_taetigkeit':
                    console.log('→ Rufe bulkChangeTaetigkeit auf');
                    bulkChangeTaetigkeit(diensteIds);
                    break;
                case 'change_status':
                    console.log('→ Rufe bulkChangeStatus auf');
                    bulkChangeStatus(diensteIds);
                    break;
                default:
                    console.error('Unbekannte Aktion:', action);
            }
        }
        
        function bulkDelete(diensteIds) {
            console.log('bulkDelete gestartet für IDs:', diensteIds);
            
            if (!confirm('Möchten Sie ' + diensteIds.length + ' Dienste wirklich löschen?')) {
                console.log('Löschen abgebrochen durch Benutzer');
                return;
            }
            
            console.log('Sende AJAX-Request:', {
                url: dpAjax.ajaxurl,
                action: 'bulk_delete_dienste',
                nonce: dpAjax.nonce,
                dienst_ids: diensteIds
            });
            
            $.ajax({
                url: dpAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'bulk_delete_dienste',
                    nonce: dpAjax.nonce,
                    dienst_ids: diensteIds
                },
                success: function(response) {
                    console.log('AJAX Success Response:', response);
                    if (response.success) {
                        alert('Dienste wurden gelöscht.');
                        location.reload();
                    } else {
                        alert('Fehler: ' + (response.data?.message || 'Unbekannter Fehler'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                    console.error('Response Text:', xhr.responseText);
                    alert('AJAX-Fehler beim Löschen: ' + error);
                }
            });
        }
        
        function bulkMoveTag(diensteIds) {
            const veranstaltungId = $('#filter-veranstaltung').val();
            
            console.log('→ Lade Tags für Veranstaltung:', veranstaltungId);
            
            $.ajax({
                url: dpAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'dp_get_veranstaltung_tage',
                    nonce: dpAjax.nonce,
                    veranstaltung_id: veranstaltungId
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Tags geladen:', response.data);
                        openBulkTagModal(diensteIds, response.data);
                    } else {
                        alert('Fehler beim Laden der Tage: ' + (response.data?.message || 'Unbekannter Fehler'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    alert('Fehler beim Laden der Tage.');
                }
            });
        }
        
        function bulkChangeTime(diensteIds) {
            console.log('→ bulkChangeTime aufgerufen mit IDs:', diensteIds);
            
            // Prüfe, ob ausgewählte Dienste einem Tag zugeordnet sind
            let hasDiensteOhneTag = false;
            diensteIds.forEach(function(id) {
                const checkbox = $('.dienst-checkbox[value="' + id + '"]');
                const tagAttr = checkbox.attr('data-tag');
                console.log('  Dienst ID:', id, 'data-tag:', tagAttr);
                if (tagAttr === 'ohne-tag') {
                    hasDiensteOhneTag = true;
                }
            });
            
            console.log('  hasDiensteOhneTag:', hasDiensteOhneTag);
            
            if (hasDiensteOhneTag) {
                alert('Zeitänderung nicht möglich: Bitte ordnen Sie die Dienste zuerst einem Tag zu.');
                return;
            }
            
            openBulkTimeModal(diensteIds);
        }
        
        function bulkChangeVerein(diensteIds) {
            console.log('→ Öffne Verein-ändern Modal');
            openBulkVereinModal(diensteIds);
        }
        
        function bulkChangeBereich(diensteIds) {
            console.log('→ Öffne Bereich-ändern Modal');
            openBulkBereichModal(diensteIds);
        }
        
        function bulkChangeTaetigkeit(diensteIds) {
            console.log('→ Öffne Tätigkeit-ändern Modal');
            openBulkTaetigkeitModal(diensteIds);
        }
        
        function bulkChangeStatus(diensteIds) {
            console.log('→ Öffne Status-ändern Modal');
            openBulkStatusModal(diensteIds);
        }
        
        window.performBulkUpdate = function(diensteIds, updateData) {
            console.log('performBulkUpdate gestartet:', {
                diensteIds: diensteIds,
                updateData: updateData,
                ajaxurl: dpAjax.ajaxurl,
                nonce: dpAjax.nonce
            });
            
            $.ajax({
                url: dpAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'bulk_update_dienste',
                    nonce: dpAjax.nonce,
                    dienst_ids: diensteIds,
                    update_data: updateData
                },
                success: function(response) {
                    console.log('AJAX Success Response:', response);
                    console.log('Response Data:', response.data);
                    console.log('Response Success:', response.success);
                    if (response.success) {
                        alert('Dienste wurden aktualisiert.');
                        location.reload();
                    } else {
                        const errorMsg = response.data?.message || 'Unbekannter Fehler';
                        console.error('Backend Error:', errorMsg);
                        console.error('Full Response Data:', response.data);
                        if (response.data?.errors) {
                            console.error('Fehler-Details:', response.data.errors);
                            response.data.errors.forEach(function(err, idx) {
                                console.error('  Fehler ' + (idx+1) + ':', err);
                            });
                        }
                        alert('Fehler: ' + errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                    console.error('Response Text:', xhr.responseText);
                    alert('AJAX-Fehler: ' + error);
                }
            });
        }

        // ========================================
        // Dropdown-Aktionen für einzelne Dienste
        // ========================================
        // toggleDienstActionDropdown() und hideDienstDropdown() sind jetzt in dp-admin.js definiert

        // Auto-Refresh
        // ========================================

        // Auto-Refresh alle 30 Sekunden
        // Aber NICHT auf der Import/Export-Seite!
        const urlParams = new URLSearchParams(window.location.search);
        const currentPage = urlParams.get('page');
        
        let autoRefreshInterval = null;
        
        // Nur aktivieren wenn NICHT auf Import/Export-Seite
        if (currentPage !== 'dienstplan-import-export') {
            autoRefreshInterval = setInterval(function() {
                // Nur aktualisieren wenn kein Modal offen ist
                if (!$('#dienst-modal').is(':visible') && !$('#besetzung-modal').is(':visible')) {
                    console.log('Auto-Refresh: Seite wird aktualisiert...');
                    location.reload();
                }
            }, 30000); // 30 Sekunden
        }
        
        // Auto-Refresh stoppen wenn Seite verlassen wird
        window.addEventListener('beforeunload', function() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
        });
    });

    // ========================================
    // Tag-Gruppe einklappen/ausklappen
    // ========================================
    // window.toggleTagGroup() ist jetzt in dp-admin.js definiert

})(jQuery);

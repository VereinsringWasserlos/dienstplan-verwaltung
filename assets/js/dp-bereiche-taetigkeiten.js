(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // ==================== MODAL HANDLING ====================
        
        // Bereich Modal öffnen
        $('#add-bereich-btn').on('click', function(e) {
            e.preventDefault();
            $('#bereich-modal-title').text('Neuer Bereich');
            $('#bereich-id').val('');
            $('#bereich-name').val('');
            $('#bereich-farbe').val('#3b82f6');
            $('#bereich-modal').fadeIn(200);
        });
        
        $(document).on('click', '.edit-bereich-btn', function() {
            var bereichId = $(this).data('id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_bereich',
                    nonce: '<?php echo wp_create_nonce("dienstplan-nonce"); ?>',
                    bereich_id: bereichId
                },
                success: function(response) {
                    if (response.success) {
                        $('#bereich-modal-title').text('Bereich bearbeiten');
                        $('#bereich-id').val(response.data.id);
                        $('#bereich-name').val(response.data.name);
                        $('#bereich-farbe').val(response.data.farbe);
                        $('#bereich-modal').fadeIn(200);
                    }
                }
            });
        });
        
        // Tätigkeit Modal öffnen
        $(document).on('click', '.add-taetigkeit-btn', function(e) {
            e.preventDefault();
            var bereichId = $(this).data('bereich-id');
            
            $('#taetigkeit-modal-title').text('Neue Tätigkeit');
            $('#taetigkeit-id').val('');
            $('#taetigkeit-bereich-id').val(bereichId);
            $('#taetigkeit-name').val('');
            $('#taetigkeit-beschreibung').val('');
            $('#taetigkeit-qualifikation').val('');
            $('#taetigkeit-aktiv').prop('checked', true);
            $('#taetigkeit-modal').fadeIn(200);
        });
        
        $(document).on('click', '.edit-taetigkeit-btn', function() {
            var taetigkeitId = $(this).data('id');
            var bereichId = $(this).data('bereich-id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_taetigkeit',
                    nonce: '<?php echo wp_create_nonce("dienstplan-nonce"); ?>',
                    taetigkeit_id: taetigkeitId
                },
                success: function(response) {
                    if (response.success) {
                        $('#taetigkeit-modal-title').text('Tätigkeit bearbeiten');
                        $('#taetigkeit-id').val(response.data.id);
                        $('#taetigkeit-bereich-id').val(response.data.bereich_id);
                        $('#taetigkeit-name').val(response.data.name);
                        $('#taetigkeit-beschreibung').val(response.data.beschreibung || '');
                        $('#taetigkeit-aktiv').prop('checked', response.data.aktiv == 1);
                        $('#taetigkeit-modal').fadeIn(200);
                    }
                }
            });
        });
        
        // Modal schließen
        $('#cancel-bereich-modal, #cancel-taetigkeit-modal').on('click', function() {
            $(this).closest('[id$="-modal"]').fadeOut(200);
        });
        
        $(document).on('click', '.modal-overlay', function(e) {
            if (e.target === this) {
                $(this).parent().fadeOut(200);
            }
        });
        
        // ==================== FORM SUBMIT ====================
        
        // Bereich speichern
        $('#bereich-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = {
                action: 'save_bereich',
                nonce: '<?php echo wp_create_nonce("dienstplan-nonce"); ?>',
                bereich_id: $('#bereich-id').val(),
                name: $('#bereich-name').val(),
                farbe: $('#bereich-farbe').val()
            };
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
                    } else {
                        alert('Fehler: ' + response.data.message);
                    }
                }
            });
        });
        
        // Tätigkeit speichern
        $('#taetigkeit-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = {
                action: 'save_taetigkeit',
                nonce: '<?php echo wp_create_nonce("dienstplan-nonce"); ?>',
                taetigkeit_id: $('#taetigkeit-id').val(),
                bereich_id: $('#taetigkeit-bereich-id').val(),
                name: $('#taetigkeit-name').val(),
                beschreibung: $('#taetigkeit-beschreibung').val(),
                aktiv: $('#taetigkeit-aktiv').is(':checked') ? 1 : 0
            };
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
                    } else {
                        alert('Fehler: ' + response.data.message);
                    }
                }
            });
        });
        
        // ==================== EINZELAKTIONEN ====================
        
        // Bereich löschen
        $(document).on('click', '.delete-bereich-btn', function() {
            if (!confirm('Wirklich löschen? Alle zugehörigen Tätigkeiten werden ebenfalls gelöscht!')) {
                return;
            }
            
            var bereichId = $(this).data('id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_bereich',
                    nonce: '<?php echo wp_create_nonce("dienstplan-nonce"); ?>',
                    bereich_id: bereichId
                },
                success: function(response) {
                    if (response.success) {
                        if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
                    } else {
                        alert('Fehler: ' + response.data.message);
                    }
                }
            });
        });
        
        // Tätigkeit löschen
        $(document).on('click', '.delete-taetigkeit-btn', function() {
            if (!confirm('Wirklich löschen?')) {
                return;
            }
            
            var taetigkeitId = $(this).data('id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_taetigkeit',
                    nonce: '<?php echo wp_create_nonce("dienstplan-nonce"); ?>',
                    taetigkeit_id: taetigkeitId
                },
                success: function(response) {
                    if (response.success) {
                        if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
                    } else {
                        alert('Fehler: ' + response.data.message);
                    }
                }
            });
        });
        
        // Status togglen
        $(document).on('click', '.toggle-status-btn', function() {
            var taetigkeitId = $(this).data('id');
            var currentStatus = $(this).data('status');
            var newStatus = currentStatus === 'aktiv' ? 0 : 1;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'toggle_taetigkeit_status',
                    nonce: '<?php echo wp_create_nonce("dienstplan-nonce"); ?>',
                    taetigkeit_id: taetigkeitId,
                    aktiv: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
                    }
                }
            });
        });
        
        // ==================== BULK ACTIONS ====================
        
        // Checkbox-Handling
        $(document).on('change', '.taetigkeit-checkbox', function() {
            var bereichId = $(this).data('bereich');
            updateBulkToolbar(bereichId);
        });
        
        $(document).on('change', '.select-all-header, .select-all-taetigkeiten', function() {
            var bereichId = $(this).data('bereich');
            var checked = $(this).is(':checked');
            $('.taetigkeit-checkbox[data-bereich="' + bereichId + '"]').prop('checked', checked);
            updateBulkToolbar(bereichId);
        });
        
        function updateBulkToolbar(bereichId) {
            var selectedCount = $('.taetigkeit-checkbox[data-bereich="' + bereichId + '"]:checked').length;
            var $toolbar = $('#bulk-toolbar-' + bereichId);
            
            if (selectedCount > 0) {
                $toolbar.slideDown(200);
                $toolbar.find('.count').text(selectedCount);
            } else {
                $toolbar.slideUp(200);
            }
        }
        
        // Bulk-Aktion anwenden
        $(document).on('click', '.apply-bulk-action', function() {
            var bereichId = $(this).data('bereich');
            var action = $('.bulk-action-select[data-bereich="' + bereichId + '"]').val();
            var selectedIds = [];
            
            $('.taetigkeit-checkbox[data-bereich="' + bereichId + '"]:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (!action) {
                alert('Bitte wählen Sie eine Aktion aus.');
                return;
            }
            
            if (selectedIds.length === 0) {
                alert('Bitte wählen Sie mindestens eine Tätigkeit aus.');
                return;
            }
            
            handleBulkAction(action, selectedIds, bereichId);
        });
        
        function handleBulkAction(action, ids, bereichId) {
            switch(action) {
                case 'delete':
                    bulkDelete(ids);
                    break;
                case 'move_bereich':
                    bulkMoveBereich(ids);
                    break;
                case 'change_status':
                    bulkChangeStatus(ids);
                    break;
            }
        }
        
        function bulkDelete(ids) {
            if (!confirm('Wirklich ' + ids.length + ' Tätigkeiten löschen?')) {
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bulk_delete_taetigkeiten',
                    nonce: '<?php echo wp_create_nonce("dienstplan-nonce"); ?>',
                    taetigkeit_ids: ids
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
                    } else {
                        alert('Fehler: ' + response.data.message);
                    }
                }
            });
        }
        
        function bulkMoveBereich(ids) {
            var bereichId = prompt('Bereich-ID eingeben:');
            if (!bereichId) return;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bulk_update_taetigkeiten',
                    nonce: '<?php echo wp_create_nonce("dienstplan-nonce"); ?>',
                    taetigkeit_ids: ids,
                    update_data: { bereich_id: bereichId }
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
                    } else {
                        alert('Fehler: ' + response.data.message);
                    }
                }
            });
        }
        
        function bulkChangeStatus(ids) {
            var status = confirm('Aktivieren (OK) oder Deaktivieren (Abbrechen)?') ? 1 : 0;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bulk_update_taetigkeiten',
                    nonce: '<?php echo wp_create_nonce("dienstplan-nonce"); ?>',
                    taetigkeit_ids: ids,
                    update_data: { aktiv: status }
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
                    } else {
                        alert('Fehler: ' + response.data.message);
                    }
                }
            });
        }
        
        // Bulk-Auswahl abbrechen
        $(document).on('click', '.cancel-bulk-selection', function() {
            var bereichId = $(this).data('bereich');
            $('.taetigkeit-checkbox[data-bereich="' + bereichId + '"]').prop('checked', false);
            $('.select-all-header[data-bereich="' + bereichId + '"], .select-all-taetigkeiten[data-bereich="' + bereichId + '"]').prop('checked', false);
            updateBulkToolbar(bereichId);
        });
        
    });
    
})(jQuery);

/**
 * Dienstplan Verwaltung - Bulk Update Modals JavaScript
 * Verwaltet die Modal-Dialoge für Bulk-Updates von Diensten
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('Bulk Update Modals geladen');

        // Globale Variable für ausgewählte Dienst-IDs
        window.bulkSelectedIds = [];

        // ========================================
        // Zeit ändern Modal
        // ========================================

        window.openBulkTimeModal = function(diensteIds) {
            window.bulkSelectedIds = diensteIds;
            $('#bulk-time-modal').fadeIn(200);
            $('#bulk_von_zeit').val('').trigger('focus');
            $('#bulk_bis_zeit').val('');
        };

        window.closeBulkTimeModal = function() {
            $('#bulk-time-modal').fadeOut(200);
            window.bulkSelectedIds = [];
        };

        window.saveBulkTime = function() {
            const vonZeit = $('#bulk_von_zeit').val();
            const bisZeit = $('#bulk_bis_zeit').val();

            if (!vonZeit || !bisZeit) {
                alert('Bitte beide Zeiten ausfüllen.');
                return;
            }

            const ids = window.bulkSelectedIds.slice(); // Kopie der IDs erstellen
            closeBulkTimeModal();
            performBulkUpdate(ids, {
                von_zeit: vonZeit,
                bis_zeit: bisZeit
            });
        };

        // ========================================
        // Verein ändern Modal
        // ========================================

        window.openBulkVereinModal = function(diensteIds) {
            window.bulkSelectedIds = diensteIds;
            $('#bulk-verein-modal').fadeIn(200);
            $('#bulk_verein_id').val('').trigger('focus');
        };

        window.closeBulkVereinModal = function() {
            $('#bulk-verein-modal').fadeOut(200);
            window.bulkSelectedIds = [];
        };

        window.saveBulkVerein = function() {
            const vereinId = $('#bulk_verein_id').val();

            if (!vereinId) {
                alert('Bitte einen Verein auswählen.');
                return;
            }

            const ids = window.bulkSelectedIds.slice(); // Kopie der IDs erstellen
            closeBulkVereinModal();
            performBulkUpdate(ids, {
                verein_id: vereinId
            });
        };

        // ========================================
        // Bereich ändern Modal
        // ========================================

        window.openBulkBereichModal = function(diensteIds) {
            window.bulkSelectedIds = diensteIds;
            $('#bulk-bereich-modal').fadeIn(200);
            $('#bulk_bereich_id').val('').trigger('focus');
            $('#bulk_taetigkeit_row').hide();
            $('#bulk_taetigkeit_id').val('');
        };

        window.closeBulkBereichModal = function() {
            $('#bulk-bereich-modal').fadeOut(200);
            window.bulkSelectedIds = [];
        };

        // Bereich-Änderung lädt Tätigkeiten
        $('#bulk_bereich_id').on('change', function() {
            const bereichId = $(this).val();
            
            if (!bereichId) {
                $('#bulk_taetigkeit_row').hide();
                $('#bulk_taetigkeit_id').val('');
                return;
            }

            $.ajax({
                url: dpAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'dp_get_taetigkeiten_by_bereich',
                    nonce: dpAjax.nonce,
                    bereich_id: bereichId
                },
                success: function(response) {
                    if (response.success) {
                        const $select = $('#bulk_taetigkeit_id');
                        $select.empty().append('<option value="">-- Optional --</option>');
                        
                        response.data.forEach(function(taetigkeit) {
                            $select.append(
                                $('<option></option>')
                                    .val(taetigkeit.id)
                                    .text(taetigkeit.name)
                            );
                        });
                        
                        $('#bulk_taetigkeit_row').show();
                    }
                }
            });
        });

        window.saveBulkBereich = function() {
            const bereichId = $('#bulk_bereich_id').val();
            const taetigkeitId = $('#bulk_taetigkeit_id').val();

            if (!bereichId) {
                alert('Bitte einen Bereich auswählen.');
                return;
            }

            const updateData = { bereich_id: bereichId };
            if (taetigkeitId) {
                updateData.taetigkeit_id = taetigkeitId;
            }

            const ids = window.bulkSelectedIds.slice(); // Kopie der IDs erstellen
            closeBulkBereichModal();
            performBulkUpdate(ids, updateData);
        };

        // ========================================
        // Tätigkeit ändern Modal
        // ========================================

        window.openBulkTaetigkeitModal = function(diensteIds) {
            window.bulkSelectedIds = diensteIds;
            $('#bulk-taetigkeit-modal').fadeIn(200);
            $('#bulk_taet_bereich_id').val('').trigger('focus');
            $('#bulk_taet_taetigkeit_row').hide();
            $('#bulk_taet_taetigkeit_id').val('');
        };

        window.closeBulkTaetigkeitModal = function() {
            $('#bulk-taetigkeit-modal').fadeOut(200);
            window.bulkSelectedIds = [];
        };

        // Bereich-Änderung lädt Tätigkeiten
        $('#bulk_taet_bereich_id').on('change', function() {
            const bereichId = $(this).val();
            
            if (!bereichId) {
                $('#bulk_taet_taetigkeit_row').hide();
                $('#bulk_taet_taetigkeit_id').val('');
                return;
            }

            $.ajax({
                url: dpAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'dp_get_taetigkeiten_by_bereich',
                    nonce: dpAjax.nonce,
                    bereich_id: bereichId
                },
                success: function(response) {
                    if (response.success) {
                        const $select = $('#bulk_taet_taetigkeit_id');
                        $select.empty().append('<option value="">-- Bitte wählen --</option>');
                        
                        response.data.forEach(function(taetigkeit) {
                            $select.append(
                                $('<option></option>')
                                    .val(taetigkeit.id)
                                    .text(taetigkeit.name)
                            );
                        });
                        
                        $('#bulk_taet_taetigkeit_row').show();
                    }
                }
            });
        });

        window.saveBulkTaetigkeit = function() {
            const taetigkeitId = $('#bulk_taet_taetigkeit_id').val();

            if (!taetigkeitId) {
                alert('Bitte eine Tätigkeit auswählen.');
                return;
            }

            const ids = window.bulkSelectedIds.slice(); // Kopie der IDs erstellen
            closeBulkTaetigkeitModal();
            performBulkUpdate(ids, {
                taetigkeit_id: taetigkeitId
            });
        };

        // ========================================
        // Status ändern Modal
        // ========================================

        window.openBulkStatusModal = function(diensteIds) {
            window.bulkSelectedIds = diensteIds;
            $('#bulk-status-modal').fadeIn(200);
            $('#bulk_status').val('').trigger('focus');
        };

        window.closeBulkStatusModal = function() {
            $('#bulk-status-modal').fadeOut(200);
            window.bulkSelectedIds = [];
        };

        window.saveBulkStatus = function() {
            const status = $('#bulk_status').val();

            if (!status) {
                alert('Bitte einen Status auswählen.');
                return;
            }

            const ids = window.bulkSelectedIds.slice(); // Kopie der IDs erstellen
            closeBulkStatusModal();
            performBulkUpdate(ids, {
                status: status
            });
        };

        // ========================================
        // Tag zuweisen Modal
        // ========================================

        window.openBulkTagModal = function(diensteIds, tage) {
            window.bulkSelectedIds = diensteIds;
            
            // Tags in Select-Box laden
            const $select = $('#bulk_tag_id');
            $select.empty().append('<option value="">-- Bitte wählen --</option>');
            
            tage.forEach(function(tag) {
                const datum = new Date(tag.tag_datum);
                const formattedDate = datum.toLocaleDateString('de-DE', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: '2-digit', 
                    day: '2-digit' 
                });
                
                $select.append(
                    $('<option></option>')
                        .val(tag.id)
                        .text('Tag ' + tag.tag_nummer + ': ' + formattedDate)
                );
            });
            
            $('#bulk-tag-modal').fadeIn(200);
            $select.trigger('focus');
        };

        window.closeBulkTagModal = function() {
            $('#bulk-tag-modal').fadeOut(200);
            window.bulkSelectedIds = [];
        };

        window.saveBulkTag = function() {
            const tagId = $('#bulk_tag_id').val();

            if (!tagId) {
                alert('Bitte einen Tag auswählen.');
                return;
            }

            const ids = window.bulkSelectedIds.slice(); // Kopie der IDs erstellen
            closeBulkTagModal();
            performBulkUpdate(ids, {
                tag_id: tagId
            });
        };

        // ========================================
        // Escape-Taste schließt Modals
        // ========================================

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                if ($('#bulk-time-modal').is(':visible')) closeBulkTimeModal();
                if ($('#bulk-verein-modal').is(':visible')) closeBulkVereinModal();
                if ($('#bulk-bereich-modal').is(':visible')) closeBulkBereichModal();
                if ($('#bulk-taetigkeit-modal').is(':visible')) closeBulkTaetigkeitModal();
                if ($('#bulk-status-modal').is(':visible')) closeBulkStatusModal();
                if ($('#bulk-tag-modal').is(':visible')) closeBulkTagModal();
            }
        });

        // ========================================
        // Click outside schließt Modals
        // ========================================

        $('.dp-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).fadeOut(200);
                window.bulkSelectedIds = [];
            }
        });
    });

})(jQuery);

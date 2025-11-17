(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Alle Mitarbeiter auswählen/abwählen
        window.toggleAllMitarbeiter = function(checked) {
            $('.mitarbeiter-checkbox').prop('checked', checked);
            updateBulkActionButton();
        };
        
        // Button-Status aktualisieren
        window.updateBulkActionButton = function() {
            const checkedCount = $('.mitarbeiter-checkbox:checked').length;
            const $button = $('.bulkactions .button');
            
            if (checkedCount > 0) {
                $button.text('Anwenden (' + checkedCount + ')');
            } else {
                $button.text('Anwenden');
            }
        };
        
        // Massenaktion ausführen
        window.applyBulkAction = function() {
            const action = $('#bulk-action-selector-top').val();
            const checked = $('.mitarbeiter-checkbox:checked');
            
            if (action === '-1') {
                alert('Bitte wählen Sie eine Aktion aus!');
                return;
            }
            
            if (checked.length === 0) {
                alert('Bitte wählen Sie mindestens einen Mitarbeiter aus!');
                return;
            }
            
            if (action === 'delete') {
                const count = checked.length;
                const confirmText = count === 1 
                    ? 'Möchten Sie diesen Mitarbeiter wirklich löschen?\n\nDamit werden auch alle seine Dienst-Zuweisungen gelöscht!'
                    : 'Möchten Sie wirklich ' + count + ' Mitarbeiter löschen?\n\nDamit werden auch alle ihre Dienst-Zuweisungen gelöscht!';
                
                if (!confirm(confirmText)) {
                    return;
                }
                
                const ids = [];
                checked.each(function() {
                    ids.push($(this).val());
                });
                
                deleteMitarbeiterBulk(ids);
            }
        };
        
        // Mehrfach-Löschen
        function deleteMitarbeiterBulk(ids) {
            console.log('deleteMitarbeiterBulk', ids);
            
            // Zeige Loading-Indikator
            const $button = $('.bulkactions .button');
            const originalText = $button.text();
            $button.prop('disabled', true).text('Wird gelöscht...');
            
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
                        if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
                    } else {
                        alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannt'));
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Fehler:', error);
                    alert('AJAX-Fehler: ' + error);
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }
        
    });
    
})(jQuery);

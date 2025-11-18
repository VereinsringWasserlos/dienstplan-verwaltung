<?php
/**
 * Admin-Ansicht: Bereiche & Tätigkeiten Übersicht
 */

if (!defined('ABSPATH')) {
    exit;
}

// $db wird von der display-Methode bereitgestellt
$bereiche = $db->get_bereiche();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-category" style="font-size: 1.3em; margin-right: 0.25rem;"></span>
        <?php _e('Bereiche & Tätigkeiten', 'dienstplan-verwaltung'); ?>
    </h1>
    
    <a href="#" class="page-title-action" id="add-bereich-btn">
        <span class="dashicons dashicons-plus-alt"></span>
        <?php _e('Neuer Bereich', 'dienstplan-verwaltung'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                
                <?php if (empty($bereiche)): ?>
                    <div class="notice notice-info">
                        <p>
                            <span class="dashicons dashicons-info"></span>
                            <?php _e('Noch keine Bereiche vorhanden. Erstellen Sie den ersten Bereich.', 'dienstplan-verwaltung'); ?>
                        </p>
                    </div>
                <?php else: ?>
                    
                    <?php foreach ($bereiche as $bereich): 
                        $taetigkeiten = $db->get_taetigkeiten_by_bereich($bereich->id);
                    ?>
                    
                    <div class="bereich-section" style="background: #fff; margin-bottom: 2rem; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        
                        <!-- Bereich Header -->
                        <div class="bereich-header" style="background: <?php echo esc_attr($bereich->farbe); ?>; color: #fff; padding: 1rem; display: flex; justify-content: space-between; align-items: center; border-radius: 4px 4px 0 0;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <h2 style="margin: 0; color: #fff; font-size: 1.3rem;">
                                    <?php echo esc_html($bereich->name); ?>
                                </h2>
                                <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 3px; font-size: 0.85rem;">
                                    <?php echo count($taetigkeiten); ?> <?php _e('Tätigkeiten', 'dienstplan-verwaltung'); ?>
                                </span>
                            </div>
                            <div>
                                <button class="button button-small edit-bereich-btn" data-id="<?php echo $bereich->id; ?>" style="background: rgba(255,255,255,0.9); color: #333; border: none;">
                                    <span class="dashicons dashicons-edit"></span>
                                    <?php _e('Bereich bearbeiten', 'dienstplan-verwaltung'); ?>
                                </button>
                                <button class="button button-small add-taetigkeit-btn" data-bereich-id="<?php echo $bereich->id; ?>" style="background: rgba(255,255,255,0.9); color: #333; border: none; margin-left: 0.5rem;">
                                    <span class="dashicons dashicons-plus-alt"></span>
                                    <?php _e('Tätigkeit hinzufügen', 'dienstplan-verwaltung'); ?>
                                </button>
                                <button class="button button-small button-link-delete delete-bereich-btn" data-id="<?php echo $bereich->id; ?>" style="background: rgba(220,38,38,0.9); color: #fff; border: none; margin-left: 0.5rem;">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                        
                        <?php if (!empty($taetigkeiten)): ?>
                        
                        <!-- Bulk Actions Toolbar -->
                        <div class="bulk-actions-toolbar" id="bulk-toolbar-<?php echo $bereich->id; ?>" style="display: none; background: #f0f0f1; padding: 0.75rem 1rem; border-bottom: 1px solid #ddd;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <label style="margin: 0;">
                                    <input type="checkbox" class="select-all-taetigkeiten" data-bereich="<?php echo $bereich->id; ?>">
                                    <?php _e('Alle auswählen', 'dienstplan-verwaltung'); ?>
                                </label>
                                <span class="selected-count" style="color: #2271b1; font-weight: 600;">
                                    <span class="count">0</span> <?php _e('ausgewählt', 'dienstplan-verwaltung'); ?>
                                </span>
                                <select class="bulk-action-select" data-bereich="<?php echo $bereich->id; ?>" style="width: auto;">
                                    <option value=""><?php _e('-- Aktion wählen --', 'dienstplan-verwaltung'); ?></option>
                                    <option value="delete"><?php _e('Löschen', 'dienstplan-verwaltung'); ?></option>
                                    <option value="move_bereich"><?php _e('Bereich verschieben', 'dienstplan-verwaltung'); ?></option>
                                    <option value="change_status"><?php _e('Status ändern', 'dienstplan-verwaltung'); ?></option>
                                </select>
                                <button class="button apply-bulk-action" data-bereich="<?php echo $bereich->id; ?>">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php _e('Anwenden', 'dienstplan-verwaltung'); ?>
                                </button>
                                <button class="button cancel-bulk-selection" data-bereich="<?php echo $bereich->id; ?>" style="margin-left: auto;">
                                    <span class="dashicons dashicons-no-alt"></span>
                                    <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Tätigkeiten Tabelle -->
                        <table class="wp-list-table widefat fixed striped taetigkeiten-table" style="margin: 0; border: none; border-radius: 0;">
                            <thead>
                                <tr>
                                    <th width="5%" style="padding-left: 1rem;">
                                        <input type="checkbox" class="select-all-header" data-bereich="<?php echo $bereich->id; ?>">
                                    </th>
                                    <th width="5%"><?php _e('ID', 'dienstplan-verwaltung'); ?></th>
                                    <th width="40%"><?php _e('Tätigkeit', 'dienstplan-verwaltung'); ?></th>
                                    <th width="15%"><?php _e('Status', 'dienstplan-verwaltung'); ?></th>
                                    <th width="15%"><?php _e('Verwendungen', 'dienstplan-verwaltung'); ?></th>
                                    <th width="25%"><?php _e('Aktionen', 'dienstplan-verwaltung'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($taetigkeiten as $taetigkeit): 
                                    // Zähle Verwendungen in Diensten
                                    $verwendungen = $db->count_dienste_by_taetigkeit($taetigkeit->id);
                                ?>
                                    <tr class="taetigkeit-row" data-taetigkeit-id="<?php echo $taetigkeit->id; ?>" data-bereich-id="<?php echo $bereich->id; ?>">
                                        <td style="padding-left: 1rem;">
                                            <input type="checkbox" class="taetigkeit-checkbox" value="<?php echo $taetigkeit->id; ?>" data-bereich="<?php echo $bereich->id; ?>">
                                        </td>
                                        <td>
                                            <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 0.85em;">
                                                #<?php echo $taetigkeit->id; ?>
                                            </code>
                                        </td>
                                        <td>
                                            <strong style="font-size: 1.05rem;"><?php echo esc_html($taetigkeit->name); ?></strong>
                                            <?php if ($taetigkeit->beschreibung): ?>
                                                <div style="margin-top: 0.25rem; font-size: 0.85rem; color: #6b7280;">
                                                    <?php echo esc_html(wp_trim_words($taetigkeit->beschreibung, 12)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $status = isset($taetigkeit->aktiv) ? ($taetigkeit->aktiv ? 'aktiv' : 'inaktiv') : 'aktiv';
                                            $status_color = $status === 'aktiv' ? '#10b981' : '#6b7280';
                                            ?>
                                            <span style="background: <?php echo $status_color; ?>; color: #fff; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem;">
                                                <?php echo $status === 'aktiv' ? '✓ Aktiv' : '○ Inaktiv'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="dashicons dashicons-admin-links" style="color: #6b7280;"></span>
                                            <strong><?php echo $verwendungen; ?></strong> 
                                            <?php echo $verwendungen === 1 ? __('Dienst', 'dienstplan-verwaltung') : __('Dienste', 'dienstplan-verwaltung'); ?>
                                        </td>
                                        <td>
                                            <button class="button button-small edit-taetigkeit-btn" data-id="<?php echo $taetigkeit->id; ?>" data-bereich-id="<?php echo $bereich->id; ?>">
                                                <span class="dashicons dashicons-edit"></span>
                                                <?php _e('Bearbeiten', 'dienstplan-verwaltung'); ?>
                                            </button>
                                            <button class="button button-small toggle-status-btn" data-id="<?php echo $taetigkeit->id; ?>" data-status="<?php echo $status; ?>">
                                                <span class="dashicons dashicons-update"></span>
                                                <?php echo $status === 'aktiv' ? __('Deaktivieren', 'dienstplan-verwaltung') : __('Aktivieren', 'dienstplan-verwaltung'); ?>
                                            </button>
                                            <?php if ($verwendungen === 0): ?>
                                                <button class="button button-small button-link-delete delete-taetigkeit-btn" data-id="<?php echo $taetigkeit->id; ?>">
                                                    <span class="dashicons dashicons-trash"></span>
                                                    <?php _e('Löschen', 'dienstplan-verwaltung'); ?>
                                                </button>
                                            <?php else: ?>
                                                <button class="button button-small" disabled title="<?php _e('Kann nicht gelöscht werden - wird in Diensten verwendet', 'dienstplan-verwaltung'); ?>">
                                                    <span class="dashicons dashicons-lock"></span>
                                                    <?php _e('In Verwendung', 'dienstplan-verwaltung'); ?>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php else: ?>
                        <div style="padding: 2rem; text-align: center; color: #6b7280; background: #f9fafb;">
                            <span class="dashicons dashicons-info" style="font-size: 2rem; opacity: 0.5;"></span>
                            <p><?php _e('Noch keine Tätigkeiten in diesem Bereich.', 'dienstplan-verwaltung'); ?></p>
                            <button class="button button-primary add-taetigkeit-btn" data-bereich-id="<?php echo $bereich->id; ?>">
                                <span class="dashicons dashicons-plus-alt"></span>
                                <?php _e('Erste Tätigkeit hinzufügen', 'dienstplan-verwaltung'); ?>
                            </button>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                    
                    <?php endforeach; ?>
                    
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<!-- Modal: Bereich bearbeiten/erstellen -->
<div id="bereich-modal" style="display: none;">
    <div class="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100000;">
        <div class="modal-content" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <h2 id="bereich-modal-title"><?php _e('Bereich bearbeiten', 'dienstplan-verwaltung'); ?></h2>
            <form id="bereich-form">
                <input type="hidden" id="bereich-id" name="bereich_id">
                
                <p>
                    <label for="bereich-name">
                        <strong><?php _e('Name', 'dienstplan-verwaltung'); ?> *</strong>
                    </label>
                    <input type="text" id="bereich-name" name="name" class="widefat" required>
                </p>
                
                <p>
                    <label for="bereich-farbe">
                        <strong><?php _e('Farbe', 'dienstplan-verwaltung'); ?> *</strong>
                    </label>
                    <input type="color" id="bereich-farbe" name="farbe" value="#3b82f6" style="width: 100%; height: 40px;">
                </p>
                
                <p style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="button" id="cancel-bereich-modal">
                        <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
                    </button>
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php _e('Speichern', 'dienstplan-verwaltung'); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Tätigkeit bearbeiten/erstellen -->
<div id="taetigkeit-modal" style="display: none;">
    <div class="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100000;">
        <div class="modal-content" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 2rem; border-radius: 8px; width: 90%; max-width: 600px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <h2 id="taetigkeit-modal-title"><?php _e('Tätigkeit bearbeiten', 'dienstplan-verwaltung'); ?></h2>
            <form id="taetigkeit-form">
                <input type="hidden" id="taetigkeit-id" name="taetigkeit_id">
                <input type="hidden" id="taetigkeit-bereich-id" name="bereich_id">
                
                <p>
                    <label for="taetigkeit-name">
                        <strong><?php _e('Name', 'dienstplan-verwaltung'); ?> *</strong>
                    </label>
                    <input type="text" id="taetigkeit-name" name="name" class="widefat" required>
                </p>
                
                <p>
                    <label for="taetigkeit-beschreibung">
                        <strong><?php _e('Beschreibung', 'dienstplan-verwaltung'); ?></strong>
                    </label>
                    <textarea id="taetigkeit-beschreibung" name="beschreibung" class="widefat" rows="3"></textarea>
                </p>
                
                <p>
                    <label>
                        <input type="checkbox" id="taetigkeit-aktiv" name="aktiv" value="1" checked>
                        <strong><?php _e('Aktiv', 'dienstplan-verwaltung'); ?></strong>
                    </label>
                </p>
                
                <p style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="button" id="cancel-taetigkeit-modal">
                        <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
                    </button>
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php _e('Speichern', 'dienstplan-verwaltung'); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript moved to assets/js/dp-bereiche-taetigkeiten.js -->

<style>
        e.preventDefault();
        $('#bereich-modal-title').text('<?php _e('Neuer Bereich', 'dienstplan-verwaltung'); ?>');
        $('#bereich-id').val('');
        $('#bereich-name').val('');
        $('#bereich-farbe').val('#3b82f6');
        $('#bereich-modal').fadeIn(200);
    });
    
    $('.edit-bereich-btn').on('click', function() {
        var bereichId = $(this).data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_bereich',
                nonce: '<?php echo wp_create_nonce('dienstplan-nonce'); ?>',
                bereich_id: bereichId
            },
            success: function(response) {
                if (response.success) {
                    $('#bereich-modal-title').text('<?php _e('Bereich bearbeiten', 'dienstplan-verwaltung'); ?>');
                    $('#bereich-id').val(response.data.id);
                    $('#bereich-name').val(response.data.name);
                    $('#bereich-farbe').val(response.data.farbe);
                    $('#bereich-modal').fadeIn(200);
                }
            }
        });
    });
    
    // Tätigkeit Modal öffnen
    $('.add-taetigkeit-btn').on('click', function(e) {
        e.preventDefault();
        var bereichId = $(this).data('bereich-id');
        
        $('#taetigkeit-modal-title').text('<?php _e('Neue Tätigkeit', 'dienstplan-verwaltung'); ?>');
        $('#taetigkeit-id').val('');
        $('#taetigkeit-bereich-id').val(bereichId);
        $('#taetigkeit-name').val('');
        $('#taetigkeit-beschreibung').val('');
        $('#taetigkeit-qualifikation').val('');
        $('#taetigkeit-aktiv').prop('checked', true);
        $('#taetigkeit-modal').fadeIn(200);
    });
    
    $('.edit-taetigkeit-btn').on('click', function() {
        var taetigkeitId = $(this).data('id');
        var bereichId = $(this).data('bereich-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_taetigkeit',
                nonce: '<?php echo wp_create_nonce('dienstplan-nonce'); ?>',
                taetigkeit_id: taetigkeitId
            },
            success: function(response) {
                if (response.success) {
                    $('#taetigkeit-modal-title').text('<?php _e('Tätigkeit bearbeiten', 'dienstplan-verwaltung'); ?>');
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
            nonce: '<?php echo wp_create_nonce('dienstplan-nonce'); ?>',
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
                    location.reload();
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
            nonce: '<?php echo wp_create_nonce('dienstplan-nonce'); ?>',
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
                    location.reload();
                } else {
                    alert('Fehler: ' + response.data.message);
                }
            }
        });
    });
    
    // ==================== EINZELAKTIONEN ====================
    
    // Bereich löschen
    $('.delete-bereich-btn').on('click', function() {
        if (!confirm('<?php _e('Wirklich löschen? Alle zugehörigen Tätigkeiten werden ebenfalls gelöscht!', 'dienstplan-verwaltung'); ?>')) {
            return;
        }
        
        var bereichId = $(this).data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_bereich',
                nonce: '<?php echo wp_create_nonce('dienstplan-nonce'); ?>',
                bereich_id: bereichId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Fehler: ' + response.data.message);
                }
            }
        });
    });
    
    // Tätigkeit löschen
    $('.delete-taetigkeit-btn').on('click', function() {
        if (!confirm('<?php _e('Wirklich löschen?', 'dienstplan-verwaltung'); ?>')) {
            return;
        }
        
        var taetigkeitId = $(this).data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_taetigkeit',
                nonce: '<?php echo wp_create_nonce('dienstplan-nonce'); ?>',
                taetigkeit_id: taetigkeitId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Fehler: ' + response.data.message);
                }
            }
        });
    });
    
    // Status togglen
    $('.toggle-status-btn').on('click', function() {
        var taetigkeitId = $(this).data('id');
        var currentStatus = $(this).data('status');
        var newStatus = currentStatus === 'aktiv' ? 0 : 1;
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'toggle_taetigkeit_status',
                nonce: '<?php echo wp_create_nonce('dienstplan-nonce'); ?>',
                taetigkeit_id: taetigkeitId,
                aktiv: newStatus
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });
    
    // ==================== BULK ACTIONS ====================
    
    // Checkbox-Handling
    $('.taetigkeit-checkbox').on('change', function() {
        var bereichId = $(this).data('bereich');
        updateBulkToolbar(bereichId);
    });
    
    $('.select-all-header, .select-all-taetigkeiten').on('change', function() {
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
    $('.apply-bulk-action').on('click', function() {
        var bereichId = $(this).data('bereich');
        var action = $('.bulk-action-select[data-bereich="' + bereichId + '"]').val();
        var selectedIds = [];
        
        $('.taetigkeit-checkbox[data-bereich="' + bereichId + '"]:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (!action) {
            alert('<?php _e('Bitte wählen Sie eine Aktion aus.', 'dienstplan-verwaltung'); ?>');
            return;
        }
        
        if (selectedIds.length === 0) {
            alert('<?php _e('Bitte wählen Sie mindestens eine Tätigkeit aus.', 'dienstplan-verwaltung'); ?>');
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
        if (!confirm('<?php _e('Wirklich', 'dienstplan-verwaltung'); ?> ' + ids.length + ' <?php _e('Tätigkeiten löschen?', 'dienstplan-verwaltung'); ?>')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bulk_delete_taetigkeiten',
                nonce: '<?php echo wp_create_nonce('dienstplan-nonce'); ?>',
                taetigkeit_ids: ids
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Fehler: ' + response.data.message);
                }
            }
        });
    }
    
    function bulkMoveBereich(ids) {
        var bereichId = prompt('<?php _e('Bereich-ID eingeben:', 'dienstplan-verwaltung'); ?>');
        if (!bereichId) return;
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bulk_update_taetigkeiten',
                nonce: '<?php echo wp_create_nonce('dienstplan-nonce'); ?>',
                taetigkeit_ids: ids,
                update_data: { bereich_id: bereichId }
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Fehler: ' + response.data.message);
                }
            }
        });
    }
    
    function bulkChangeStatus(ids) {
        var status = confirm('<?php _e('Aktivieren (OK) oder Deaktivieren (Abbrechen)?', 'dienstplan-verwaltung'); ?>') ? 1 : 0;
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bulk_update_taetigkeiten',
                nonce: '<?php echo wp_create_nonce('dienstplan-nonce'); ?>',
                taetigkeit_ids: ids,
                update_data: { aktiv: status }
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Fehler: ' + response.data.message);
                }
            }
        });
    }
    
    // Bulk-Auswahl abbrechen
    $('.cancel-bulk-selection').on('click', function() {
        var bereichId = $(this).data('bereich');
        $('.taetigkeit-checkbox[data-bereich="' + bereichId + '"]').prop('checked', false);
        $('.select-all-header[data-bereich="' + bereichId + '"], .select-all-taetigkeiten[data-bereich="' + bereichId + '"]').prop('checked', false);
        updateBulkToolbar(bereichId);
    });
    
});
</script>

<style>
.bereich-section {
    transition: box-shadow 0.3s ease;
}

.bereich-section:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.taetigkeit-row {
    transition: background-color 0.2s ease;
}

.taetigkeit-row:hover {
    background-color: #f0f9ff !important;
}

.button-link-delete {
    background: #dc2626;
    color: #fff;
    border-color: #dc2626;
}

.button-link-delete:hover {
    background: #b91c1c;
    border-color: #b91c1c;
}

.modal-content {
    max-height: 90vh;
    overflow-y: auto;
}

.bulk-actions-toolbar {
    animation: slideDown 0.2s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

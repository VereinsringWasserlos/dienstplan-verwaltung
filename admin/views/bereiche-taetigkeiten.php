<?php
/**
 * Bereiche & Tätigkeiten Verwaltung
 * Modernes Design wie Dienste-Seite
 */

if (!defined('ABSPATH')) exit;

$bereiche = $db->get_bereiche();

// Setup für Page-Header Partial
$page_title = __('Bereiche & Tätigkeiten', 'dienstplan-verwaltung');
$page_icon = 'dashicons-category';
$page_class = 'header-bereiche';
$nav_items = [
    [
        'label' => __('Dashboard', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan'),
        'icon' => 'dashicons-dashboard',
    ],
    [
        'label' => __('Vereine', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-vereine'),
        'icon' => 'dashicons-flag',
        'capability' => 'manage_clubs',
    ],
    [
        'label' => __('Veranstaltungen', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-veranstaltungen'),
        'icon' => 'dashicons-calendar-alt',
        'capability' => 'manage_events',
    ],
    [
        'label' => __('Dienste', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-dienste'),
        'icon' => 'dashicons-clipboard',
        'capability' => 'manage_services',
    ],
    [
        'label' => __('Mitarbeiter', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-mitarbeiter'),
        'icon' => 'dashicons-groups',
        'capability' => 'manage_employees',
    ],
];
?>

<div class="wrap dienstplan-wrap">
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/page-header.php'; ?>
    
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($_GET['message']); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (empty($bereiche)): ?>
        <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 3rem; text-align: center; margin-top: 2rem;">
            <span class="dashicons dashicons-category" style="font-size: 64px; color: #c3c4c7; margin-bottom: 1rem;"></span>
            <h2 style="color: #50575e; margin-bottom: 0.5rem;"><?php _e('Noch keine Bereiche', 'dienstplan-verwaltung'); ?></h2>
            <p style="color: #787c82; margin-bottom: 2rem;">
                <?php _e('Erstellen Sie den ersten Bereich, um Tätigkeiten zu organisieren.', 'dienstplan-verwaltung'); ?>
            </p>
            <button class="button button-primary button-hero" onclick="openBereichModal(); return false;">
                <span class="dashicons dashicons-plus-alt" style="margin-top: 4px;"></span>
                <?php _e('Ersten Bereich erstellen', 'dienstplan-verwaltung'); ?>
            </button>
        </div>
    <?php else: ?>
        
        <!-- Bereiche Liste -->
        <div class="bereiche-list" style="margin-top: 2rem;">
            <?php foreach ($bereiche as $bereich): 
                $taetigkeiten = $db->get_taetigkeiten_by_bereich($bereich->id);
                $collapse_id = 'bereich-' . $bereich->id;
            ?>
                <div class="bereich-gruppe" style="margin-bottom: 1.5rem; border: 1px solid #c3c4c7; border-radius: 4px; position: relative;">
                    <!-- Einklappbarer Header -->
                    <h3 class="bereich-header-collapsible" style="background: <?php echo esc_attr($bereich->farbe); ?>; color: white; padding: 1rem 1.5rem; margin: 0; display: flex; align-items: center; gap: 1rem; transition: all 0.3s;">
                        <span class="dashicons dashicons-arrow-down-alt2 collapse-icon" id="icon-<?php echo $collapse_id; ?>" onclick="toggleBereichGroup('<?php echo $collapse_id; ?>')" style="transition: transform 0.3s; font-size: 20px; cursor: pointer;"></span>
                        
                        <span onclick="toggleBereichGroup('<?php echo $collapse_id; ?>')" style="flex: 1; display: flex; align-items: center; gap: 1rem; cursor: pointer;">
                            <span class="dashicons dashicons-category" style="font-size: 24px;"></span>
                            <strong style="font-size: 1.1rem;"><?php echo esc_html($bereich->name); ?></strong>
                        </span>
                        
                        <button type="button" class="button button-primary" onclick="event.stopPropagation(); openTaetigkeitModal(<?php echo $bereich->id; ?>);" style="background: rgba(255,255,255,0.9); color: <?php echo esc_attr($bereich->farbe); ?>; border: none; font-weight: 600; padding: 0.5rem 1rem; border-radius: 3px; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;" onmouseover="this.style.background='#fff'" onmouseout="this.style.background='rgba(255,255,255,0.9)'">
                            <span class="dashicons dashicons-plus-alt" style="font-size: 18px; width: 18px; height: 18px;"></span>
                            <?php _e('Neue Tätigkeit', 'dienstplan-verwaltung'); ?>
                        </button>
                        
                        <button type="button" class="button" onclick="event.stopPropagation(); openBereichModal(<?php echo $bereich->id; ?>);" style="background: rgba(255,255,255,0.8); color: #333; border: none; padding: 0.5rem 1rem; border-radius: 3px;">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        
                        <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.9rem;">
                            <?php echo count($taetigkeiten); ?> Tätigkeit<?php echo count($taetigkeiten) != 1 ? 'en' : ''; ?>
                        </span>
                    </h3>
                    
                    <!-- Einklappbarer Content -->
                    <div id="<?php echo $collapse_id; ?>" class="bereich-content" style="display: block;">
                        
                        <?php if (empty($taetigkeiten)): ?>
                            <div style="padding: 2rem; text-align: center; background: #f9fafb;">
                                <span class="dashicons dashicons-admin-tools" style="font-size: 48px; color: #c3c4c7;"></span>
                                <p style="color: #787c82; margin-top: 1rem;">
                                    <?php _e('Noch keine Tätigkeiten in diesem Bereich.', 'dienstplan-verwaltung'); ?>
                                </p>
                            </div>
                        <?php else: ?>
                            
                            <!-- Bulk-Aktionen Toolbar -->
                            <div class="bulk-actions-toolbar" style="background: #f9fafb; padding: 1rem; border: 1px solid #e5e7eb; border-bottom: none; display: flex;">
                                <div style="display: flex; gap: 1rem; align-items: center;">
                                    <span class="selected-count" style="color: #6b7280;">
                                        <span class="count">0</span> <?php _e('ausgewählt', 'dienstplan-verwaltung'); ?>
                                    </span>
                                    <select class="bulk-action-select" style="border: 1px solid #d1d5db; border-radius: 3px; padding: 0.375rem 0.75rem;">
                                        <option value=""><?php _e('-- Aktion wählen --', 'dienstplan-verwaltung'); ?></option>
                                        <option value="delete"><?php _e('Löschen', 'dienstplan-verwaltung'); ?></option>
                                    </select>
                                    <button class="button button-primary bulk-action-apply">
                                        <span class="dashicons dashicons-yes" style="margin-top: 3px;"></span>
                                        <?php _e('Anwenden', 'dienstplan-verwaltung'); ?>
                                    </button>
                                    <button class="button cancel-bulk-selection" style="margin-left: auto;">
                                        <span class="dashicons dashicons-no-alt" style="margin-top: 3px;"></span>
                                        <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Tätigkeiten Tabelle -->
                            <div style="overflow: visible;">
                                <table class="wp-list-table widefat fixed striped" style="border: none; margin: 0;">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px; padding-left: 10px;">
                                                <input type="checkbox" class="select-all-header" data-bereich="<?php echo $bereich->id; ?>">
                                            </th>
                                            <th style="width: 60px;"><?php _e('ID', 'dienstplan-verwaltung'); ?></th>
                                            <th><?php _e('Tätigkeit', 'dienstplan-verwaltung'); ?></th>
                                            <th style="width: 120px;"><?php _e('Status', 'dienstplan-verwaltung'); ?></th>
                                            <th style="width: 150px; text-align: center;"><?php _e('Aktionen', 'dienstplan-verwaltung'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody style="overflow: visible;">
                                        <?php foreach ($taetigkeiten as $taetigkeit): ?>
                                            <tr data-taetigkeit-id="<?php echo $taetigkeit->id; ?>" class="taetigkeit-row" style="position: relative;">
                                                <td style="padding-left: 10px;">
                                                    <input type="checkbox" class="taetigkeit-checkbox" value="<?php echo $taetigkeit->id; ?>" data-bereich="<?php echo $bereich->id; ?>">
                                                </td>
                                                <td><?php echo $taetigkeit->id; ?></td>
                                                <td>
                                                    <strong><?php echo esc_html($taetigkeit->name); ?></strong>
                                                    <?php if ($taetigkeit->beschreibung): ?>
                                                        <br><small style="color: #666;"><?php echo esc_html($taetigkeit->beschreibung); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_labels = array(
                                                        'aktiv' => __('Aktiv', 'dienstplan-verwaltung'),
                                                        'inaktiv' => __('Inaktiv', 'dienstplan-verwaltung')
                                                    );
                                                    $status_colors = array(
                                                        'aktiv' => '#10b981',
                                                        'inaktiv' => '#6b7280'
                                                    );
                                                    $status = $taetigkeit->status ?? 'aktiv';
                                                    ?>
                                                    <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; background: <?php echo $status_colors[$status]; ?>; color: white;">
                                                        <?php echo $status_labels[$status]; ?>
                                                    </span>
                                                </td>
                                                <td style="text-align: center; position: relative;">
                                                    <div class="dropdown-actions">
                                                        <button class="action-button" onclick="toggleTaetigkeitActionDropdown(event, <?php echo $taetigkeit->id; ?>)">
                                                            <span class="dashicons dashicons-menu"></span>
                                                            <?php _e('Aktionen', 'dienstplan-verwaltung'); ?>
                                                        </button>
                                                        
                                                        <!-- Dropdown-Menü -->
                                                        <div id="taetigkeit-action-dropdown-<?php echo $taetigkeit->id; ?>" class="taetigkeit-action-dropdown">
                                                            <a href="#" onclick="openTaetigkeitModal(<?php echo $bereich->id; ?>, <?php echo $taetigkeit->id; ?>); return false;">
                                                                <span class="dashicons dashicons-edit"></span>
                                                                <?php _e('Bearbeiten', 'dienstplan-verwaltung'); ?>
                                                            </a>
                                                            <a href="#" onclick="deleteTaetigkeit(<?php echo $taetigkeit->id; ?>); return false;">
                                                                <span class="dashicons dashicons-trash"></span>
                                                                <?php _e('Löschen', 'dienstplan-verwaltung'); ?>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
    <?php endif; ?>
</div>

<!-- Bereich Modal -->
<div id="bereich-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content">
        <div class="dp-modal-header">
            <h2 id="bereich-modal-title"><?php _e('Neuer Bereich', 'dienstplan-verwaltung'); ?></h2>
            <button class="dp-modal-close" onclick="closeBereichModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="bereich-form">
                <input type="hidden" id="bereich_id" name="bereich_id" value="">
                
                <table class="form-table">
                    <tr>
                        <th><label for="bereich_name"><?php _e('Name', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="text" id="bereich_name" name="bereich_name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="bereich_farbe"><?php _e('Farbe', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <input type="color" id="bereich_farbe" name="bereich_farbe" value="#3b82f6">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="bereich_beschreibung"><?php _e('Beschreibung', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <textarea id="bereich_beschreibung" name="bereich_beschreibung" class="large-text" rows="3"></textarea>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button class="button" onclick="closeBereichModal()"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
            <button class="button button-primary" onclick="saveBereich()"><?php _e('Speichern', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>

<!-- Tätigkeit Modal -->
<div id="taetigkeit-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content">
        <div class="dp-modal-header">
            <h2 id="taetigkeit-modal-title"><?php _e('Neue Tätigkeit', 'dienstplan-verwaltung'); ?></h2>
            <button class="dp-modal-close" onclick="closeTaetigkeitModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="taetigkeit-form">
                <input type="hidden" id="taetigkeit_id" name="taetigkeit_id" value="">
                <input type="hidden" id="taetigkeit_bereich_id" name="taetigkeit_bereich_id" value="">
                
                <table class="form-table">
                    <tr>
                        <th><label for="taetigkeit_name"><?php _e('Name', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td>
                            <input type="text" id="taetigkeit_name" name="taetigkeit_name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="taetigkeit_beschreibung"><?php _e('Beschreibung', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <textarea id="taetigkeit_beschreibung" name="taetigkeit_beschreibung" class="large-text" rows="3"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="taetigkeit_status"><?php _e('Status', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <select id="taetigkeit_status" name="taetigkeit_status">
                                <option value="aktiv"><?php _e('Aktiv', 'dienstplan-verwaltung'); ?></option>
                                <option value="inaktiv"><?php _e('Inaktiv', 'dienstplan-verwaltung'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button class="button" onclick="closeTaetigkeitModal()"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
            <button class="button button-primary" onclick="saveTaetigkeit()"><?php _e('Speichern', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>

<script>
// Einklappen/Ausklappen
function toggleBereichGroup(collapseId) {
    const content = document.getElementById(collapseId);
    const icon = document.getElementById('icon-' + collapseId);
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.classList.remove('dashicons-arrow-right-alt2');
        icon.classList.add('dashicons-arrow-down-alt2');
    } else {
        content.style.display = 'none';
        icon.classList.remove('dashicons-arrow-down-alt2');
        icon.classList.add('dashicons-arrow-right-alt2');
    }
}

// Dropdown Toggle
function toggleTaetigkeitActionDropdown(event, taetigkeitId) {
    event.stopPropagation();
    const dropdown = document.getElementById('taetigkeit-action-dropdown-' + taetigkeitId);
    const dropdownContainer = dropdown?.parentElement;
    const isOpening = dropdown.style.display === 'none';
    
    // Schließe alle anderen Dropdowns und entferne active-Klasse
    document.querySelectorAll('.taetigkeit-action-dropdown').forEach(function(d) {
        if (d.id !== 'taetigkeit-action-dropdown-' + taetigkeitId) {
            d.style.display = 'none';
            d.parentElement?.classList.remove('active');
        }
    });
    
    if (isOpening) {
        dropdown.style.display = 'block';
        dropdownContainer?.classList.add('active');
    } else {
        dropdown.style.display = 'none';
        dropdownContainer?.classList.remove('active');
    }
}

// Schließe Dropdowns beim Klick außerhalb
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown-actions')) {
        document.querySelectorAll('.taetigkeit-action-dropdown').forEach(function(d) {
            d.style.display = 'none';
            d.parentElement?.classList.remove('active');
        });
    }
});
</script>

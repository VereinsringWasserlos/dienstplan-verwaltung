<?php
/**
 * Mitarbeiter Verwaltung
 * Modernes Design wie Dienste-Seite
 */

if (!defined('ABSPATH')) exit;

$is_restricted_club_admin = Dienstplan_Roles::is_restricted_club_admin();

// Filter-Parameter
$filter_verein = isset($_GET['filter_verein']) ? intval($_GET['filter_verein']) : 0;
$filter_veranstaltung = isset($_GET['filter_veranstaltung']) ? intval($_GET['filter_veranstaltung']) : 0;
$filter_portal = isset($_GET['filter_portal']) ? sanitize_text_field($_GET['filter_portal']) : '';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Mitarbeiter laden
if (!isset($mitarbeiter) || !is_array($mitarbeiter)) {
    $scoped_verein_ids = isset($allowed_verein_ids) && is_array($allowed_verein_ids)
        ? array_values(array_filter(array_map('intval', $allowed_verein_ids)))
        : array();
    $mitarbeiter = $db->get_mitarbeiter_with_stats($filter_verein, $filter_veranstaltung, $search, $scoped_verein_ids);
}

// Portal-Filter anwenden (da nicht in DB-Funktion)
if ($filter_portal === 'active') {
    $mitarbeiter = array_filter($mitarbeiter, function($ma) {
        return isset($ma->user_id) && $ma->user_id;
    });
} elseif ($filter_portal === 'inactive') {
    $mitarbeiter = array_filter($mitarbeiter, function($ma) {
        return !isset($ma->user_id) || !$ma->user_id;
    });
}

// Gruppiere Mitarbeiter nach Vereinen
$mitarbeiter_nach_vereinen = array();
$mitarbeiter_ohne_verein = array();

foreach ($mitarbeiter as $ma) {
    if (empty($ma->verein_ids)) {
        $mitarbeiter_ohne_verein[] = $ma;
    } else {
        // Mitarbeiter kann mehreren Vereinen zugeordnet sein
        $verein_ids = explode(',', $ma->verein_ids);
        $verein_namen = explode(',', $ma->verein_namen);
        
        foreach ($verein_ids as $index => $verein_id) {
            if (!isset($mitarbeiter_nach_vereinen[$verein_id])) {
                $mitarbeiter_nach_vereinen[$verein_id] = array(
                    'verein_name' => $verein_namen[$index] ?? 'Unbekannt',
                    'mitarbeiter' => array()
                );
            }
            // Verhindere Duplikate
            $already_added = false;
            foreach ($mitarbeiter_nach_vereinen[$verein_id]['mitarbeiter'] as $existing_ma) {
                if ($existing_ma->id === $ma->id) {
                    $already_added = true;
                    break;
                }
            }
            if (!$already_added) {
                $mitarbeiter_nach_vereinen[$verein_id]['mitarbeiter'][] = $ma;
            }
        }
    }
}

// Sortiere Vereine alphabetisch

// Setup für Page-Header Partial
$page_title = __('Mitarbeiter', 'dienstplan-verwaltung');
$page_icon = 'dashicons-groups';
$page_class = 'header-mitarbeiter';
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
    ],
    [
        'label' => __('Veranstaltungen', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-veranstaltungen'),
        'icon' => 'dashicons-calendar-alt',
    ],
    [
        'label' => __('Dienste', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-dienste'),
        'icon' => 'dashicons-clipboard',
    ],
    [
        'label' => __('Bereiche & Tätigkeiten', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-bereiche'),
        'icon' => 'dashicons-category',
    ],
];
?>

<div class="wrap dienstplan-wrap">
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/page-header.php'; ?>

<?php
ksort($mitarbeiter_nach_vereinen);

$get_dienst_count_for_verein = function($ma, $verein_id) {
    $verein_id = intval($verein_id);

    if ($verein_id <= 0) {
        return intval($ma->dienst_count ?? 0);
    }

    $raw_map = isset($ma->dienst_count_by_verein) ? (string) $ma->dienst_count_by_verein : '';
    if ($raw_map === '') {
        return intval($ma->dienst_count ?? 0);
    }

    $entries = explode(',', $raw_map);
    foreach ($entries as $entry) {
        $parts = explode(':', $entry, 2);
        if (count($parts) !== 2) {
            continue;
        }

        if (intval($parts[0]) === $verein_id) {
            return intval($parts[1]);
        }
    }

    return 0;
};

$get_dienste_filter_url = function($ma, $verein_id = 0) use ($filter_veranstaltung) {
    $args = array(
        'page' => 'dienstplan-dienste',
        'mitarbeiter' => intval($ma->id ?? 0),
    );

    if ($filter_veranstaltung > 0) {
        $args['veranstaltung'] = intval($filter_veranstaltung);
    }

    if (intval($verein_id) > 0) {
        $args['verein'] = intval($verein_id);
    }

    return add_query_arg($args, admin_url('admin.php'));
};
?>
    
    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($_GET['message']); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Filter-Bereich -->
    <div class="dp-filter-bar" style="background: #fff; padding: 1.5rem; border: 1px solid #c3c4c7; border-radius: 4px; margin: 1.5rem 0;">
        <h3 style="margin-top: 0;">
            <span class="dashicons dashicons-filter"></span>
            <?php _e('Filter', 'dienstplan-verwaltung'); ?>
        </h3>
        
        <form method="get" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <input type="hidden" name="page" value="dienstplan-mitarbeiter">
            
            <div style="flex: 1; min-width: 250px;">
                <label for="filter-verein" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Verein', 'dienstplan-verwaltung'); ?>
                </label>
                <select id="filter-verein" name="filter_verein" class="regular-text" style="width: 100%;">
                    <option value=""><?php _e('-- Alle Vereine --', 'dienstplan-verwaltung'); ?></option>
                    <?php foreach ($vereine as $v): ?>
                        <option value="<?php echo $v->id; ?>" <?php selected($filter_verein, $v->id); ?>>
                            <?php echo esc_html($v->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="flex: 1; min-width: 250px;">
                <label for="filter-veranstaltung" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Veranstaltung', 'dienstplan-verwaltung'); ?>
                </label>
                <select id="filter-veranstaltung" name="filter_veranstaltung" class="regular-text" style="width: 100%;">
                    <option value=""><?php _e('-- Alle Veranstaltungen --', 'dienstplan-verwaltung'); ?></option>
                    <?php foreach ($veranstaltungen as $ve): ?>
                        <option value="<?php echo $ve->id; ?>" <?php selected($filter_veranstaltung, $ve->id); ?>>
                            <?php echo esc_html($ve->name); ?>
                            (<?php echo date_i18n('d.m.Y', strtotime($ve->start_datum)); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="flex: 1; min-width: 250px;">
                <label for="filter-portal" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Portal-Zugriff', 'dienstplan-verwaltung'); ?>
                </label>
                <select id="filter-portal" name="filter_portal" class="regular-text" style="width: 100%;">
                    <option value=""><?php _e('-- Alle --', 'dienstplan-verwaltung'); ?></option>
                    <option value="active" <?php selected(isset($_GET['filter_portal']) ? $_GET['filter_portal'] : '', 'active'); ?>><?php _e('Mit Portal-Zugriff', 'dienstplan-verwaltung'); ?></option>
                    <option value="inactive" <?php selected(isset($_GET['filter_portal']) ? $_GET['filter_portal'] : '', 'inactive'); ?>><?php _e('Ohne Portal-Zugriff', 'dienstplan-verwaltung'); ?></option>
                </select>
            </div>
            
            <div style="flex: 1; min-width: 250px;">
                <label for="search" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Suche', 'dienstplan-verwaltung'); ?>
                </label>
                <input type="search" id="search" name="search" value="<?php echo esc_attr($search); ?>" 
                       placeholder="<?php _e('Name, E-Mail oder Telefon...', 'dienstplan-verwaltung'); ?>" 
                       class="regular-text" style="width: 100%;">
            </div>
            
            <div>
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Filtern', 'dienstplan-verwaltung'); ?>
                </button>
                <?php if ($filter_verein || $filter_veranstaltung || $filter_portal || $search): ?>
                    <a href="?page=dienstplan-mitarbeiter" class="button">
                        <?php _e('Zurücksetzen', 'dienstplan-verwaltung'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <?php if (empty($mitarbeiter)): ?>
        <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 3rem; text-align: center; margin-top: 2rem;">
            <span class="dashicons dashicons-groups" style="font-size: 64px; color: #c3c4c7; margin-bottom: 1rem;"></span>
            <h2 style="color: #50575e; margin-bottom: 0.5rem;">
                <?php echo ($filter_verein || $filter_veranstaltung || $search) ? __('Keine Mitarbeiter gefunden', 'dienstplan-verwaltung') : __('Noch keine Mitarbeiter', 'dienstplan-verwaltung'); ?>
            </h2>
            <p style="color: #787c82; margin-bottom: 2rem;">
                <?php echo ($filter_verein || $filter_veranstaltung || $search) 
                    ? __('Keine Mitarbeiter entsprechen den Filterkriterien.', 'dienstplan-verwaltung')
                    : __('Fügen Sie den ersten Mitarbeiter hinzu.', 'dienstplan-verwaltung'); ?>
            </p>
            <?php if (!($filter_verein || $filter_veranstaltung || $search)): ?>
                <button class="button button-primary button-hero" onclick="openMitarbeiterModal(); return false;">
                    <span class="dashicons dashicons-plus-alt" style="margin-top: 4px;"></span>
                    <?php _e('Ersten Mitarbeiter hinzufügen', 'dienstplan-verwaltung'); ?>
                </button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        
        <!-- Statistik -->
        <div style="margin: 1.5rem 0; padding: 1rem; background: #f9fafb; border-radius: 8px; display: flex; gap: 2rem;">
            <div>
                <strong style="color: #666;"><?php _e('Gesamt:', 'dienstplan-verwaltung'); ?></strong>
                <span style="font-size: 1.25rem; font-weight: 700; color: #2271b1; margin-left: 0.5rem;">
                    <?php echo count($mitarbeiter); ?>
                </span>
            </div>
            <?php if (!empty($mitarbeiter_nach_vereinen)): ?>
                <div>
                    <strong style="color: #666;"><?php _e('Vereine:', 'dienstplan-verwaltung'); ?></strong>
                    <span style="font-size: 1.25rem; font-weight: 700; color: #2271b1; margin-left: 0.5rem;">
                        <?php echo count($mitarbeiter_nach_vereinen); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Mitarbeiter nach Vereinen -->
        <div class="mitarbeiter-list" style="margin-top: 2rem;">
            
            <?php foreach ($mitarbeiter_nach_vereinen as $verein_id => $verein_data): 
                $verein_mitarbeiter = $verein_data['mitarbeiter'];
                $collapse_id = 'verein-' . $verein_id;
            ?>
                <div class="verein-gruppe" style="margin-bottom: 1.5rem; border: 1px solid #c3c4c7; border-radius: 4px; position: relative;">
                    <!-- Verein Header -->
                    <h3 class="verein-header-collapsible" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 1rem 1.5rem; margin: 0; display: flex; align-items: center; gap: 1rem; transition: all 0.3s;">
                        <span class="dashicons dashicons-arrow-down-alt2 collapse-icon" id="icon-<?php echo $collapse_id; ?>" onclick="toggleVereinGroup('<?php echo $collapse_id; ?>')" style="transition: transform 0.3s; font-size: 20px; cursor: pointer;"></span>
                        
                        <span onclick="toggleVereinGroup('<?php echo $collapse_id; ?>')" style="flex: 1; display: flex; align-items: center; gap: 1rem; cursor: pointer;">
                            <span class="dashicons dashicons-flag" style="font-size: 24px;"></span>
                            <strong style="font-size: 1.1rem;"><?php echo esc_html($verein_data['verein_name']); ?></strong>
                        </span>
                        
                        <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.9rem;">
                            <?php echo count($verein_mitarbeiter); ?> Mitarbeiter
                        </span>
                    </h3>
                    
                    <!-- Einklappbarer Content -->
                    <div id="<?php echo $collapse_id; ?>" class="verein-content" style="display: block;">
                        
                        <!-- Bulk-Aktionen Toolbar -->
                        <div class="bulk-actions-toolbar" style="background: #f9fafb; padding: 1rem; border: 1px solid #e5e7eb; border-bottom: none; display: flex;">
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <span class="selected-count" style="color: #6b7280;">
                                    <span class="count">0</span> <?php _e('ausgewählt', 'dienstplan-verwaltung'); ?>
                                </span>
                                <select class="bulk-action-select" data-verein="<?php echo $verein_id; ?>" style="border: 1px solid #d1d5db; border-radius: 3px; padding: 0.375rem 0.75rem;">
                                    <option value=""><?php _e('-- Aktion wählen --', 'dienstplan-verwaltung'); ?></option>
                                    <option value="delete"><?php _e('Löschen', 'dienstplan-verwaltung'); ?></option>
                                    <option value="activate_portal"><?php _e('Portal-Zugriff aktivieren', 'dienstplan-verwaltung'); ?></option>
                                    <option value="deactivate_portal"><?php _e('Portal-Zugriff deaktivieren', 'dienstplan-verwaltung'); ?></option>
                                    <option value="export"><?php _e('Exportieren (CSV)', 'dienstplan-verwaltung'); ?></option>
                                    <?php if (!$is_restricted_club_admin): ?>
                                        <option value="export_portal"><?php _e('Portal-Zugänge exportieren', 'dienstplan-verwaltung'); ?></option>
                                    <?php endif; ?>
                                </select>
                                <button class="button button-primary bulk-action-apply dp-bulk-button">
                                    <span class="dashicons dashicons-yes" style="margin-top: 3px;"></span>
                                    <?php _e('Anwenden', 'dienstplan-verwaltung'); ?>
                                </button>
                                <button class="button cancel-bulk-selection dp-bulk-button" style="margin-left: auto;">
                                    <span class="dashicons dashicons-no-alt" style="margin-top: 3px;"></span>
                                    <?php _e('Abbrechen', 'dienstplan-verwaltung'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Mitarbeiter Tabelle -->
                        <div style="overflow: visible;">
                            <table class="wp-list-table widefat striped" style="border: none; margin: 0; table-layout: auto;">
                                <thead>
                                    <tr>
                                        <th style="width: 40px; padding-left: 10px;">
                                            <input type="checkbox" class="select-all-header" data-verein="<?php echo $verein_id; ?>">
                                        </th>
                                        <th style="width: 60px;"><?php _e('ID', 'dienstplan-verwaltung'); ?></th>
                                        <th><?php _e('Name', 'dienstplan-verwaltung'); ?></th>
                                        <th><?php _e('Kontakt', 'dienstplan-verwaltung'); ?></th>
                                        <th style="width: 120px; text-align: center;"><?php _e('Portal', 'dienstplan-verwaltung'); ?></th>
                                        <th style="width: 100px; text-align: center;"><?php _e('Dienste', 'dienstplan-verwaltung'); ?></th>
                                        <th style="width: 220px; text-align: center;"><?php _e('Aktionen', 'dienstplan-verwaltung'); ?></th>
                                    </tr>
                                </thead>
                                <tbody style="overflow: visible;">
                                    <?php foreach ($verein_mitarbeiter as $ma): ?>
                                        <tr data-mitarbeiter-id="<?php echo $ma->id; ?>" class="mitarbeiter-row" style="position: relative;">
                                            <td style="padding-left: 10px;">
                                                <input type="checkbox" class="mitarbeiter-checkbox" value="<?php echo $ma->id; ?>" data-verein="<?php echo $verein_id; ?>">
                                            </td>
                                            <td><?php echo $ma->id; ?></td>
                                            <td>
                                                <strong><?php echo esc_html($ma->vorname . ' ' . $ma->nachname); ?></strong>
                                                <?php if (isset($ma->geburtsdatum) && $ma->geburtsdatum): ?>
                                                    <br><small style="color: #666;">
                                                        <?php echo date_i18n('d.m.Y', strtotime($ma->geburtsdatum)); ?>
                                                        (<?php echo floor((time() - strtotime($ma->geburtsdatum)) / 31556926); ?> J.)
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($ma->email) && $ma->email): ?>
                                                    <div style="margin-bottom: 0.25rem;">
                                                        <span class="dashicons dashicons-email" style="font-size: 14px; color: #666;"></span>
                                                        <a href="mailto:<?php echo esc_attr($ma->email); ?>" style="text-decoration: none;">
                                                            <?php echo esc_html($ma->email); ?>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (isset($ma->telefon) && $ma->telefon): ?>
                                                    <div>
                                                        <span class="dashicons dashicons-phone" style="font-size: 14px; color: #666;"></span>
                                                        <a href="tel:<?php echo esc_attr($ma->telefon); ?>" style="text-decoration: none;">
                                                            <?php echo esc_html($ma->telefon); ?>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <?php if (isset($ma->user_id) && $ma->user_id): ?>
                                                    <span class="portal-status-badge status-active" title="<?php _e('Portal-Zugriff aktiv', 'dienstplan-verwaltung'); ?>">
                                                        <span class="dashicons dashicons-yes"></span>
                                                        <?php _e('Aktiv', 'dienstplan-verwaltung'); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <?php if (isset($ma->email) && !empty($ma->email)): ?>
                                                        <button class="button button-small" 
                                                                onclick="activatePortalAccess(<?php echo $ma->id; ?>); event.stopPropagation();"
                                                                title="<?php _e('Portal-Zugriff aktivieren', 'dienstplan-verwaltung'); ?>"
                                                                style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                                            <span class="dashicons dashicons-lock" style="font-size: 14px; line-height: 1;"></span>
                                                            <?php _e('Aktivieren', 'dienstplan-verwaltung'); ?>
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="portal-status-badge status-inactive" title="<?php _e('Keine E-Mail-Adresse hinterlegt', 'dienstplan-verwaltung'); ?>">
                                                            <span class="dashicons dashicons-minus"></span>
                                                            <?php _e('Inaktiv', 'dienstplan-verwaltung'); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <a href="<?php echo esc_url($get_dienste_filter_url($ma, $verein_id)); ?>"
                                                   title="<?php esc_attr_e('Dienste dieses Mitarbeiters anzeigen', 'dienstplan-verwaltung'); ?>"
                                                   style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600; background: #dbeafe; color: #1e40af; text-decoration: none;">
                                                    <?php echo $get_dienst_count_for_verein($ma, $verein_id); ?>
                                                </a>
                                            </td>
                                            <td style="text-align: center; position: relative; white-space: nowrap; min-width: 220px;">
                                                <div class="dropdown-actions">
                                                    <button class="action-button dp-icon-only" onclick="toggleMitarbeiterActionDropdown(event, <?php echo $ma->id; ?>)" title="<?php esc_attr_e('Aktionen', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Aktionen', 'dienstplan-verwaltung'); ?>">
                                                        <span class="dp-action-emoji" aria-hidden="true">📋</span>
                                                    </button>
                                                    
                                                    <!-- Dropdown-Menü -->
                                                    <div id="mitarbeiter-action-dropdown-<?php echo $ma->id; ?>" class="mitarbeiter-action-dropdown" style="display: none;">
                                                        <a href="#" class="dp-icon-only" title="<?php esc_attr_e('Bearbeiten', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Bearbeiten', 'dienstplan-verwaltung'); ?>" onclick="openMitarbeiterModal(<?php echo $ma->id; ?>); return false;">
                                                            <span class="dp-action-emoji" aria-hidden="true">✏️</span>
                                                        </a>
                                                        <a href="#" class="dp-icon-only" title="<?php esc_attr_e('Dienste anzeigen', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Dienste anzeigen', 'dienstplan-verwaltung'); ?>" onclick="openMitarbeiterDiensteModal(<?php echo $ma->id; ?>); return false;">
                                                            <span class="dp-action-emoji" aria-hidden="true">📅</span>
                                                        </a>
                                                        <?php if (!empty($ma->email)): ?>
                                                            <a href="#" class="dp-icon-only" title="<?php esc_attr_e('Dienste-Mail senden', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Dienste-Mail senden', 'dienstplan-verwaltung'); ?>" onclick="resendDiensteEmail(<?php echo $ma->id; ?>); return false;">
                                                                <span class="dp-action-emoji" aria-hidden="true">📨</span>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if (!empty($ma->user_id) && !empty($ma->email)): ?>
                                                            <a href="#" class="dp-icon-only" title="<?php esc_attr_e('Zugangsdaten erneut senden', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Zugangsdaten erneut senden', 'dienstplan-verwaltung'); ?>" onclick="resendCredentials(<?php echo $ma->id; ?>); return false;">
                                                                <span class="dp-action-emoji" aria-hidden="true">🔐</span>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="#" class="dp-icon-only" title="<?php esc_attr_e('Löschen', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Löschen', 'dienstplan-verwaltung'); ?>" onclick="deleteMitarbeiter(<?php echo $ma->id; ?>); return false;">
                                                            <span class="dp-action-emoji" aria-hidden="true">🗑️</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Mitarbeiter ohne Verein -->
            <?php if (!empty($mitarbeiter_ohne_verein)): ?>
                <div class="verein-gruppe" style="margin-bottom: 1.5rem; border: 1px solid #c3c4c7; border-radius: 4px; position: relative;">
                    <h3 style="background: #9ca3af; color: white; padding: 1rem 1.5rem; margin: 0; display: flex; align-items: center; gap: 1rem;">
                        <span class="dashicons dashicons-warning" style="font-size: 24px;"></span>
                        <strong style="font-size: 1.1rem;"><?php _e('Ohne Verein', 'dienstplan-verwaltung'); ?></strong>
                        <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.9rem;">
                            <?php echo count($mitarbeiter_ohne_verein); ?> Mitarbeiter
                        </span>
                    </h3>
                    
                    <div style="overflow: visible;">
                        <table class="wp-list-table widefat striped" style="border: none; margin: 0; table-layout: auto;">
                            <thead>
                                <tr>
                                    <th style="width: 40px; padding-left: 10px;">
                                        <input type="checkbox" class="select-all-header" data-verein="0">
                                    </th>
                                    <th style="width: 60px;"><?php _e('ID', 'dienstplan-verwaltung'); ?></th>
                                    <th><?php _e('Name', 'dienstplan-verwaltung'); ?></th>
                                    <th><?php _e('Kontakt', 'dienstplan-verwaltung'); ?></th>
                                    <th style="width: 100px; text-align: center;"><?php _e('Dienste', 'dienstplan-verwaltung'); ?></th>
                                    <th style="width: 220px; text-align: center;"><?php _e('Aktionen', 'dienstplan-verwaltung'); ?></th>
                                </tr>
                            </thead>
                            <tbody style="overflow: visible;">
                                <?php foreach ($mitarbeiter_ohne_verein as $ma): ?>
                                    <tr data-mitarbeiter-id="<?php echo $ma->id; ?>" class="mitarbeiter-row" style="position: relative;">
                                        <td style="padding-left: 10px;">
                                            <input type="checkbox" class="mitarbeiter-checkbox" value="<?php echo $ma->id; ?>" data-verein="0">
                                        </td>
                                        <td><?php echo $ma->id; ?></td>
                                        <td>
                                            <strong><?php echo esc_html($ma->vorname . ' ' . $ma->nachname); ?></strong>
                                            <?php if (isset($ma->geburtsdatum) && $ma->geburtsdatum): ?>
                                                <br><small style="color: #666;">
                                                    <?php echo date_i18n('d.m.Y', strtotime($ma->geburtsdatum)); ?>
                                                    (<?php echo floor((time() - strtotime($ma->geburtsdatum)) / 31556926); ?> J.)
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($ma->email) && $ma->email): ?>
                                                <div style="margin-bottom: 0.25rem;">
                                                    <span class="dashicons dashicons-email" style="font-size: 14px; color: #666;"></span>
                                                    <a href="mailto:<?php echo esc_attr($ma->email); ?>" style="text-decoration: none;">
                                                        <?php echo esc_html($ma->email); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (isset($ma->telefon) && $ma->telefon): ?>
                                                <div>
                                                    <span class="dashicons dashicons-phone" style="font-size: 14px; color: #666;"></span>
                                                    <a href="tel:<?php echo esc_attr($ma->telefon); ?>" style="text-decoration: none;">
                                                        <?php echo esc_html($ma->telefon); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <a href="<?php echo esc_url($get_dienste_filter_url($ma, 0)); ?>"
                                               title="<?php esc_attr_e('Dienste dieses Mitarbeiters anzeigen', 'dienstplan-verwaltung'); ?>"
                                               style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600; background: #dbeafe; color: #1e40af; text-decoration: none;">
                                                <?php echo $ma->dienst_count ?? 0; ?>
                                            </a>
                                        </td>
                                        <td style="text-align: center; position: relative; white-space: nowrap; min-width: 220px;">
                                            <div class="dropdown-actions">
                                                <button class="action-button dp-icon-only" onclick="toggleMitarbeiterActionDropdown(event, <?php echo $ma->id; ?>)" title="<?php esc_attr_e('Aktionen', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Aktionen', 'dienstplan-verwaltung'); ?>">
                                                    <span class="dp-action-emoji" aria-hidden="true">📋</span>
                                                </button>
                                                
                                                <div id="mitarbeiter-action-dropdown-<?php echo $ma->id; ?>" class="mitarbeiter-action-dropdown" style="display: none;">
                                                    <a href="#" class="dp-icon-only" title="<?php esc_attr_e('Bearbeiten', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Bearbeiten', 'dienstplan-verwaltung'); ?>" onclick="openMitarbeiterModal(<?php echo $ma->id; ?>); return false;">
                                                        <span class="dp-action-emoji" aria-hidden="true">✏️</span>
                                                    </a>
                                                    <a href="#" class="dp-icon-only" title="<?php esc_attr_e('Dienste anzeigen', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Dienste anzeigen', 'dienstplan-verwaltung'); ?>" onclick="openMitarbeiterDiensteModal(<?php echo $ma->id; ?>); return false;">
                                                        <span class="dp-action-emoji" aria-hidden="true">📅</span>
                                                    </a>
                                                    <?php if (!empty($ma->email)): ?>
                                                        <a href="#" class="dp-icon-only" title="<?php esc_attr_e('Dienste-Mail senden', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Dienste-Mail senden', 'dienstplan-verwaltung'); ?>" onclick="resendDiensteEmail(<?php echo $ma->id; ?>); return false;">
                                                            <span class="dp-action-emoji" aria-hidden="true">📨</span>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (!empty($ma->user_id) && !empty($ma->email)): ?>
                                                        <a href="#" class="dp-icon-only" title="<?php esc_attr_e('Zugangsdaten erneut senden', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Zugangsdaten erneut senden', 'dienstplan-verwaltung'); ?>" onclick="resendCredentials(<?php echo $ma->id; ?>); return false;">
                                                            <span class="dp-action-emoji" aria-hidden="true">🔐</span>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="#" class="dp-icon-only" title="<?php esc_attr_e('Löschen', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Löschen', 'dienstplan-verwaltung'); ?>" onclick="deleteMitarbeiter(<?php echo $ma->id; ?>); return false;">
                                                        <span class="dp-action-emoji" aria-hidden="true">🗑️</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
    <?php endif; ?>
</div>

<script>
// Einklappen/Ausklappen
function toggleVereinGroup(collapseId) {
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
function toggleMitarbeiterActionDropdown(event, mitarbeiterId) {
    event.stopPropagation();
    const dropdown = document.getElementById('mitarbeiter-action-dropdown-' + mitarbeiterId);
    const dropdownContainer = dropdown?.parentElement;
    const isOpening = dropdown && window.getComputedStyle(dropdown).display === 'none';
    
    // Schließe alle anderen Dropdowns und entferne active-Klasse
    document.querySelectorAll('.mitarbeiter-action-dropdown').forEach(function(d) {
        if (d.id !== 'mitarbeiter-action-dropdown-' + mitarbeiterId) {
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
        document.querySelectorAll('.mitarbeiter-action-dropdown').forEach(function(d) {
            d.style.display = 'none';
            d.parentElement?.classList.remove('active');
        });
    }
});
</script>

<?php
include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/mitarbeiter-modal.php';
include_once DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/mitarbeiter-dienste-modal.php';
?>

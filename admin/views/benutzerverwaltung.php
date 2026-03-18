<?php
/**
 * Benutzerverwaltung (Admins)
 */
if (!defined('ABSPATH')) exit;

$page_title = __('Admin-Benutzer', 'dienstplan-verwaltung');
$page_icon = 'dashicons-shield';
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

$dp_users = is_array($dp_users ?? null) ? $dp_users : [];

$filter_verein = isset($_GET['filter_verein']) ? intval($_GET['filter_verein']) : 0;
$search = isset($_GET['search']) ? sanitize_text_field((string) $_GET['search']) : '';

global $wpdb;
$dp_prefix = $wpdb->prefix . 'dp_';
$table_user_vereine = $dp_prefix . 'user_vereine';
$table_vereine = $dp_prefix . 'vereine';

$admins_nach_vereinen = [];
$admins_ohne_verein = [];
$user_vereine_map = [];
$filter_vereine = [];

if (!empty($dp_users)) {
    $user_ids = array_values(array_unique(array_map(function($u) {
        return intval($u->ID ?? 0);
    }, $dp_users)));

    $user_ids = array_values(array_filter($user_ids));

    if (!empty($user_ids)) {
        $placeholders = implode(',', array_fill(0, count($user_ids), '%d'));
        $sql = "SELECT uv.user_id, uv.verein_id, v.name AS verein_name
                FROM {$table_user_vereine} uv
                LEFT JOIN {$table_vereine} v ON v.id = uv.verein_id
                WHERE uv.user_id IN ({$placeholders})
                ORDER BY v.name ASC";

        $rows = $wpdb->get_results($wpdb->prepare($sql, $user_ids));

        foreach ((array) $rows as $row) {
            $uid = intval($row->user_id);
            $vid = intval($row->verein_id);

            if ($uid <= 0 || $vid <= 0) {
                continue;
            }

            if (!isset($user_vereine_map[$uid])) {
                $user_vereine_map[$uid] = [];
            }

            $user_vereine_map[$uid][$vid] = [
                'verein_id' => $vid,
                'verein_name' => !empty($row->verein_name) ? (string) $row->verein_name : __('Unbekannter Verein', 'dienstplan-verwaltung'),
            ];

            $filter_vereine[$vid] = !empty($row->verein_name) ? (string) $row->verein_name : __('Unbekannter Verein', 'dienstplan-verwaltung');
        }
    }

    if (!empty($filter_vereine)) {
        asort($filter_vereine, SORT_NATURAL | SORT_FLAG_CASE);
    }

    foreach ($dp_users as $user) {
        $uid = intval($user->ID ?? 0);
        $assignments = $user_vereine_map[$uid] ?? [];

        if (!empty($search)) {
            $search_haystack = strtolower(trim(
                (string) ($user->display_name ?? '') . ' ' .
                (string) ($user->user_login ?? '') . ' ' .
                (string) ($user->user_email ?? '') . ' ' .
                (string) Dienstplan_Roles::get_user_role_display($user)
            ));
            $search_needle = strtolower($search);
            if (strpos($search_haystack, $search_needle) === false) {
                continue;
            }
        }

        if ($filter_verein > 0) {
            if (!isset($assignments[$filter_verein])) {
                continue;
            }
            $assignments = [$filter_verein => $assignments[$filter_verein]];
        }

        if (empty($assignments)) {
            if ($filter_verein > 0) {
                continue;
            }
            $admins_ohne_verein[] = $user;
            continue;
        }

        foreach ($assignments as $assignment) {
            $vid = intval($assignment['verein_id']);
            if (!isset($admins_nach_vereinen[$vid])) {
                $admins_nach_vereinen[$vid] = [
                    'verein_name' => (string) $assignment['verein_name'],
                    'admins' => [],
                ];
            }

            $already_added = false;
            foreach ($admins_nach_vereinen[$vid]['admins'] as $existing_user) {
                if (intval($existing_user->ID ?? 0) === $uid) {
                    $already_added = true;
                    break;
                }
            }

            if (!$already_added) {
                $admins_nach_vereinen[$vid]['admins'][] = $user;
            }
        }
    }

    uasort($admins_nach_vereinen, function($a, $b) {
        return strcasecmp((string) ($a['verein_name'] ?? ''), (string) ($b['verein_name'] ?? ''));
    });
}

$filtered_admin_count = count($admins_ohne_verein);
foreach ($admins_nach_vereinen as $group) {
    $filtered_admin_count += count($group['admins'] ?? []);
}
?>

<div class="wrap dienstplan-wrap">
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/page-header.php'; ?>

    <div class="dp-filter-bar" style="background: #fff; padding: 1.5rem; border: 1px solid #c3c4c7; border-radius: 4px; margin: 1.5rem 0;">
        <h3 style="margin-top: 0;">
            <span class="dashicons dashicons-filter"></span>
            <?php _e('Filter', 'dienstplan-verwaltung'); ?>
        </h3>

        <form method="get" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <input type="hidden" name="page" value="dienstplan-benutzer">

            <div style="flex: 1; min-width: 250px;">
                <label for="filter-verein" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Verein', 'dienstplan-verwaltung'); ?>
                </label>
                <select id="filter-verein" name="filter_verein" class="regular-text" style="width: 100%;">
                    <option value=""><?php _e('-- Alle Vereine --', 'dienstplan-verwaltung'); ?></option>
                    <?php foreach ($filter_vereine as $vid => $vname): ?>
                        <option value="<?php echo intval($vid); ?>" <?php selected($filter_verein, intval($vid)); ?>>
                            <?php echo esc_html($vname); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex: 1; min-width: 250px;">
                <label for="search" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <?php _e('Suche', 'dienstplan-verwaltung'); ?>
                </label>
                <input type="search" id="search" name="search" value="<?php echo esc_attr($search); ?>"
                       placeholder="<?php _e('Name, Login, E-Mail oder Rolle...', 'dienstplan-verwaltung'); ?>"
                       class="regular-text" style="width: 100%;">
            </div>

            <div>
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Filtern', 'dienstplan-verwaltung'); ?>
                </button>
                <?php if ($filter_verein || $search): ?>
                    <a href="?page=dienstplan-benutzer" class="button">
                        <?php _e('Zurücksetzen', 'dienstplan-verwaltung'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if ($filtered_admin_count === 0): ?>
        <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 3rem; text-align: center; margin-top: 2rem;">
            <span class="dashicons dashicons-admin-users" style="font-size: 64px; color: #c3c4c7; margin-bottom: 1rem;"></span>
            <h2 style="color: #50575e; margin-bottom: 0.5rem;"><?php _e('Keine Admin-Benutzer gefunden', 'dienstplan-verwaltung'); ?></h2>
            <p style="color: #787c82;"><?php _e('Aktuell sind keine Benutzer mit Admin-Rollen vorhanden.', 'dienstplan-verwaltung'); ?></p>
        </div>
    <?php else: ?>
        <div style="margin: 1.5rem 0; padding: 1rem; background: #f9fafb; border-radius: 8px; display: flex; gap: 2rem;">
            <div>
                <strong style="color: #666;"><?php _e('Gesamt:', 'dienstplan-verwaltung'); ?></strong>
                <span style="font-size: 1.25rem; font-weight: 700; color: #2271b1; margin-left: 0.5rem;"><?php echo intval($filtered_admin_count); ?></span>
            </div>
            <div>
                <strong style="color: #666;"><?php _e('Vereine:', 'dienstplan-verwaltung'); ?></strong>
                <span style="font-size: 1.25rem; font-weight: 700; color: #2271b1; margin-left: 0.5rem;"><?php echo count($admins_nach_vereinen); ?></span>
            </div>
        </div>

        <div class="mitarbeiter-list" style="margin-top: 2rem;">
            <?php foreach ($admins_nach_vereinen as $verein_id => $verein_data):
                $verein_admins = $verein_data['admins'];
                $collapse_id = 'admin-verein-' . intval($verein_id);
            ?>
                <div class="verein-gruppe" style="margin-bottom: 1.5rem; border: 1px solid #c3c4c7; border-radius: 4px; position: relative;">
                    <h3 class="verein-header-collapsible" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 1rem 1.5rem; margin: 0; display: flex; align-items: center; gap: 1rem; transition: all 0.3s;">
                        <span class="dashicons dashicons-arrow-down-alt2 collapse-icon" id="icon-<?php echo esc_attr($collapse_id); ?>" onclick="toggleAdminGroup('<?php echo esc_js($collapse_id); ?>')" style="transition: transform 0.3s; font-size: 20px; cursor: pointer;"></span>

                        <span onclick="toggleAdminGroup('<?php echo esc_js($collapse_id); ?>')" style="flex: 1; display: flex; align-items: center; gap: 1rem; cursor: pointer;">
                            <span class="dashicons dashicons-flag" style="font-size: 24px;"></span>
                            <strong style="font-size: 1.1rem;"><?php echo esc_html($verein_data['verein_name']); ?></strong>
                        </span>

                        <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.9rem;">
                            <?php echo count($verein_admins); ?> <?php _e('Admins', 'dienstplan-verwaltung'); ?>
                        </span>
                    </h3>

                    <div id="<?php echo esc_attr($collapse_id); ?>" class="verein-content" style="display: block;">
                        <div style="overflow: visible;">
                            <table class="wp-list-table widefat fixed striped admin-users-table" style="border: none; margin: 0;">
                                <thead>
                                    <tr>
                                        <th style="width: 70px;"><?php _e('ID', 'dienstplan-verwaltung'); ?></th>
                                        <th><?php _e('Benutzer', 'dienstplan-verwaltung'); ?></th>
                                        <th><?php _e('Kontakt', 'dienstplan-verwaltung'); ?></th>
                                        <th style="width: 240px;"><?php _e('Rolle', 'dienstplan-verwaltung'); ?></th>
                                        <th style="width: 220px; text-align: center;"><?php _e('Aktionen', 'dienstplan-verwaltung'); ?></th>
                                    </tr>
                                </thead>
                                <tbody style="overflow: visible;">
                                    <?php foreach ($verein_admins as $user):
                                        $dropdown_id = 'user-action-dropdown-' . intval($verein_id) . '-' . intval($user->ID);
                                    ?>
                                        <tr style="position: relative;">
                                            <td><?php echo intval($user->ID); ?></td>
                                            <td>
                                                <strong><?php echo esc_html($user->display_name); ?></strong>
                                                <br><small style="color: #6b7280;"><?php echo esc_html($user->user_login); ?></small>
                                            </td>
                                            <td>
                                                <span class="dashicons dashicons-email" style="font-size: 14px; color: #666;"></span>
                                                <a href="mailto:<?php echo esc_attr($user->user_email); ?>" style="text-decoration: none;">
                                                    <?php echo esc_html($user->user_email); ?>
                                                </a>
                                            </td>
                                            <td><?php echo esc_html(Dienstplan_Roles::get_user_role_display($user)); ?></td>
                                            <td style="text-align: center; position: relative;">
                                                <div class="dropdown-actions">
                                                    <button class="action-button dp-icon-only" onclick="toggleUserActionDropdown(event, '<?php echo esc_js($dropdown_id); ?>')" title="<?php esc_attr_e('Aktionen', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Aktionen', 'dienstplan-verwaltung'); ?>">
                                                        <span class="dp-action-emoji" aria-hidden="true">📋</span>
                                                    </button>

                                                    <div id="<?php echo esc_attr($dropdown_id); ?>" class="mitarbeiter-action-dropdown" style="display: none;">
                                                        <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . intval($user->ID))); ?>" class="dp-icon-only" title="<?php esc_attr_e('Bearbeiten', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Bearbeiten', 'dienstplan-verwaltung'); ?>">
                                                            <span class="dp-action-emoji" aria-hidden="true">✏️</span>
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

            <?php if (!empty($admins_ohne_verein)): ?>
                <div class="verein-gruppe" style="margin-bottom: 1.5rem; border: 1px solid #c3c4c7; border-radius: 4px; position: relative;">
                    <h3 style="background: #9ca3af; color: white; padding: 1rem 1.5rem; margin: 0; display: flex; align-items: center; gap: 1rem;">
                        <span class="dashicons dashicons-warning" style="font-size: 24px;"></span>
                        <strong style="font-size: 1.1rem;"><?php _e('Ohne Verein', 'dienstplan-verwaltung'); ?></strong>
                        <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.9rem;">
                            <?php echo count($admins_ohne_verein); ?> <?php _e('Admins', 'dienstplan-verwaltung'); ?>
                        </span>
                    </h3>

                    <div style="overflow: visible;">
                        <table class="wp-list-table widefat fixed striped admin-users-table" style="border: none; margin: 0;">
                            <thead>
                                <tr>
                                    <th style="width: 70px;"><?php _e('ID', 'dienstplan-verwaltung'); ?></th>
                                    <th><?php _e('Benutzer', 'dienstplan-verwaltung'); ?></th>
                                    <th><?php _e('Kontakt', 'dienstplan-verwaltung'); ?></th>
                                    <th style="width: 240px;"><?php _e('Rolle', 'dienstplan-verwaltung'); ?></th>
                                    <th style="width: 220px; text-align: center;"><?php _e('Aktionen', 'dienstplan-verwaltung'); ?></th>
                                </tr>
                            </thead>
                            <tbody style="overflow: visible;">
                                <?php foreach ($admins_ohne_verein as $user):
                                    $dropdown_id = 'user-action-dropdown-none-' . intval($user->ID);
                                ?>
                                    <tr style="position: relative;">
                                        <td><?php echo intval($user->ID); ?></td>
                                        <td>
                                            <strong><?php echo esc_html($user->display_name); ?></strong>
                                            <br><small style="color: #6b7280;"><?php echo esc_html($user->user_login); ?></small>
                                        </td>
                                        <td>
                                            <span class="dashicons dashicons-email" style="font-size: 14px; color: #666;"></span>
                                            <a href="mailto:<?php echo esc_attr($user->user_email); ?>" style="text-decoration: none;">
                                                <?php echo esc_html($user->user_email); ?>
                                            </a>
                                        </td>
                                        <td><?php echo esc_html(Dienstplan_Roles::get_user_role_display($user)); ?></td>
                                        <td style="text-align: center; position: relative;">
                                            <div class="dropdown-actions">
                                                <button class="action-button dp-icon-only" onclick="toggleUserActionDropdown(event, '<?php echo esc_js($dropdown_id); ?>')" title="<?php esc_attr_e('Aktionen', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Aktionen', 'dienstplan-verwaltung'); ?>">
                                                    <span class="dp-action-emoji" aria-hidden="true">📋</span>
                                                </button>

                                                <div id="<?php echo esc_attr($dropdown_id); ?>" class="mitarbeiter-action-dropdown" style="display: none;">
                                                    <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . intval($user->ID))); ?>" class="dp-icon-only" title="<?php esc_attr_e('Bearbeiten', 'dienstplan-verwaltung'); ?>" aria-label="<?php esc_attr_e('Bearbeiten', 'dienstplan-verwaltung'); ?>">
                                                        <span class="dp-action-emoji" aria-hidden="true">✏️</span>
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

<style>
.admin-users-table td:last-child,
.admin-users-table th:last-child,
.admin-users-table .dropdown-actions,
.admin-users-table .action-button {
    white-space: nowrap;
}

.admin-users-table .dropdown-actions {
    display: inline-flex;
    justify-content: center;
}
</style>

<script>
function toggleAdminGroup(collapseId) {
    const content = document.getElementById(collapseId);
    const icon = document.getElementById('icon-' + collapseId);

    if (!content || !icon) {
        return;
    }

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

function toggleUserActionDropdown(event, dropdownId) {
    event.stopPropagation();

    const dropdown = document.getElementById(dropdownId);
    if (!dropdown) {
        return;
    }

    const isOpen = window.getComputedStyle(dropdown).display === 'block';

    document.querySelectorAll('.mitarbeiter-action-dropdown').forEach(function(menu) {
        menu.style.display = 'none';
    });

    dropdown.style.display = isOpen ? 'none' : 'block';
}

document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown-actions')) {
        document.querySelectorAll('.mitarbeiter-action-dropdown').forEach(function(menu) {
            menu.style.display = 'none';
        });
    }
});
</script>

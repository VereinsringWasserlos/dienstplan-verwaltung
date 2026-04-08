<?php
/**
 * Einheitlicher Page Header für alle Verwaltungsseiten
 * 
 * Verwendung:
 * <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/page-header.php'; ?>
 * 
 * Erforderliche Variablen (vor dem Include setzen):
 * - $page_title (string): Seitentitel (z.B. 'Vereine')
 * - $page_icon (string): Dashicons-Klasse (z.B. 'dashicons-flag')
 * - $page_class (string): CSS-Klasse für Farbe (z.B. 'header-vereine')
 * - $nav_items (array): Array mit Navigationsbuttons
 *   Format: [
 *       ['label' => 'Dashboard', 'url' => '...', 'icon' => 'dashicons-dashboard', 'hide_on' => 'page-name'],
 *       ...
 *   ]
 * - $db (optional): Database-Objekt für Berechtigungen
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/admin/views/partials
 */

if (!defined('ABSPATH')) exit;

// Standardwerte setzen
$page_title = $page_title ?? 'Admin';
$page_icon = $page_icon ?? 'dashicons-admin-generic';
$page_class = $page_class ?? 'header-vereine';
$nav_items = $nav_items ?? [];
$page_meta_badges = $page_meta_badges ?? [];
$current_page = $_GET['page'] ?? '';

/**
 * Prueft, ob ein Navigationsziel fuer die aktuelle Rolle sichtbar sein soll.
 * Optional kann pro Item direkt 'capability' gesetzt werden.
 */
$can_show_nav_item = function($item) {
    if (!is_array($item)) {
        return false;
    }

    // Explizite Capability am Item hat Vorrang.
    if (!empty($item['capability'])) {
        return current_user_can('manage_options') || current_user_can($item['capability']);
    }

    if (empty($item['url'])) {
        return true;
    }

    $parts = wp_parse_url($item['url']);
    if (empty($parts['query'])) {
        return true;
    }

    parse_str($parts['query'], $query_vars);
    $target_page = isset($query_vars['page']) ? sanitize_text_field($query_vars['page']) : '';

    if (empty($target_page)) {
        return true;
    }

    if (in_array($target_page, array('dienstplan-import', 'dienstplan-export', 'dienstplan-import-export'), true)) {
        return current_user_can('manage_options')
            || Dienstplan_Roles::can_manage_clubs()
            || Dienstplan_Roles::can_manage_events()
            || Dienstplan_Roles::can_manage_settings();
    }

    // Eingeschränkte Vereins-Admins dürfen Veranstaltungen und Bereiche nicht sehen.
    if (in_array($target_page, array('dienstplan-veranstaltungen', 'dienstplan-bereiche'), true)) {
        if (Dienstplan_Roles::is_restricted_club_admin()) {
            return false;
        }
    }

    $required_capabilities = array(
        'dienstplan-vereine' => Dienstplan_Roles::CAP_MANAGE_CLUBS,
        'dienstplan-veranstaltungen' => Dienstplan_Roles::CAP_MANAGE_EVENTS,
        'dienstplan-bereiche' => Dienstplan_Roles::CAP_MANAGE_EVENTS,
        'dienstplan-mitarbeiter' => Dienstplan_Roles::CAP_MANAGE_EVENTS,
        'dienstplan-dienste' => Dienstplan_Roles::CAP_MANAGE_EVENTS,
        'dienstplan-overview' => Dienstplan_Roles::CAP_MANAGE_EVENTS,
        'dienstplan-einstellungen' => Dienstplan_Roles::CAP_MANAGE_SETTINGS,
        'dienstplan-mail' => Dienstplan_Roles::CAP_MANAGE_SETTINGS,
        'dienstplan-benutzer' => Dienstplan_Roles::CAP_MANAGE_USERS,
        'dienstplan-dokumentation' => 'read',
        'dienstplan-updates' => 'manage_options',
        'dienstplan-portal' => 'manage_options',
        'dienstplan-debug' => 'manage_options',
    );

    if (!isset($required_capabilities[$target_page])) {
        return true;
    }

    $capability = $required_capabilities[$target_page];
    return current_user_can('manage_options') || current_user_can($capability);
};
?>

<div class="dienstplan-page-header <?php echo esc_attr($page_class); ?>">
    
    <!-- Seiten-Titel mit Icon -->
    <div class="page-title-section">
        <span class="dashicons <?php echo esc_attr($page_icon); ?>"></span>
        <div>
            <h1><?php echo esc_html($page_title); ?></h1>
            <?php if (!empty($page_meta_badges) && is_array($page_meta_badges)): ?>
                <div style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-top:0.45rem;">
                    <?php foreach ($page_meta_badges as $badge): ?>
                        <?php
                        $badge_label = isset($badge['label']) ? (string) $badge['label'] : '';
                        if ($badge_label === '') {
                            continue;
                        }

                        $badge_tone = isset($badge['tone']) ? (string) $badge['tone'] : 'neutral';
                        $badge_styles = array(
                            'neutral' => 'background: rgba(255,255,255,0.18); color: #ffffff; border: 1px solid rgba(255,255,255,0.28);',
                            'info' => 'background: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd;',
                            'success' => 'background: #dcfce7; color: #166534; border: 1px solid #86efac;'
                        );
                        $badge_style = isset($badge_styles[$badge_tone]) ? $badge_styles[$badge_tone] : $badge_styles['neutral'];
                        ?>
                        <span style="display:inline-flex; align-items:center; gap:0.35rem; padding:0.25rem 0.6rem; border-radius:999px; font-size:0.78rem; font-weight:700; line-height:1.1; <?php echo esc_attr($badge_style); ?>">
                            <?php echo esc_html($badge_label); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Navigation Buttons -->
    <div class="dienstplan-page-nav">
        <?php 
        foreach ($nav_items as $item):
            // Skip auf aktueller Seite, falls hide_on gesetzt
            if (isset($item['hide_on']) && $item['hide_on'] === $current_page) {
                continue;
            }

            // Links je Rolle/Berechtigung ausblenden
            if (!$can_show_nav_item($item)) {
                continue;
            }
            
            $icon = isset($item['icon']) ? $item['icon'] : 'dashicons-admin-generic';
            $label = $item['label'] ?? 'Link';
            $url = $item['url'] ?? '#';
        ?>
            <a href="<?php echo esc_url($url); ?>" class="page-nav-button">
                <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </div>
    
</div>

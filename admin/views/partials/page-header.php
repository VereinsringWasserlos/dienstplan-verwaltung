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
$current_page = $_GET['page'] ?? '';
?>

<div class="dienstplan-page-header <?php echo esc_attr($page_class); ?>">
    
    <!-- Seiten-Titel mit Icon -->
    <div class="page-title-section">
        <span class="dashicons <?php echo esc_attr($page_icon); ?>"></span>
        <h1><?php echo esc_html($page_title); ?></h1>
    </div>
    
    <!-- Navigation Buttons -->
    <div class="dienstplan-page-nav">
        <?php 
        foreach ($nav_items as $item):
            // Skip auf aktueller Seite, falls hide_on gesetzt
            if (isset($item['hide_on']) && $item['hide_on'] === $current_page) {
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

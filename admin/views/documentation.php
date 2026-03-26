<?php
/**
 * Dokumentation View
 *
 * @package DienstplanVerwaltung
 * @subpackage Admin/Views
 */

if (!defined('ABSPATH')) {
    exit;
}

// Pfad zur Dokumentation
$doc_path = DIENSTPLAN_PLUGIN_PATH . 'documentation/';

// Verfügbare Dokumente
$documents = array(
    'readme' => array(
        'file' => 'README.md',
        'title' => 'Dokumentations-Übersicht',
        'icon' => '📚',
        'description' => 'Alle Dokumente im Überblick - Start hier!',
        'category' => 'start'
    ),
    'quick-start' => array(
        'file' => 'QUICK_START.md',
        'title' => 'Quick-Start Guide',
        'icon' => '⚡',
        'description' => 'In 15 Minuten einsatzbereit - Schnellstart für Administratoren und Crew-Mitglieder',
        'category' => 'start'
    ),
    'version-090' => array(
        'file' => 'VERSION_0.9.0_FEATURES.md',
        'title' => 'Version 0.9.0 Features',
        'icon' => '🚀',
        'description' => 'UAT Release - Komplette Feature-Übersicht und Neuerungen',
        'category' => 'start'
    ),
    'roadmap' => array(
        'file' => 'ROADMAP.md',
        'title' => 'Roadmap & Ausblick',
        'icon' => '🔮',
        'description' => 'Zukünftige Features und Entwicklungsplan bis Version 2.0',
        'category' => 'start'
    ),
    'backend' => array(
        'file' => 'BEDIENUNGSANLEITUNG_BACKEND.md',
        'title' => 'Backend-Bedienungsanleitung',
        'icon' => '⚙️',
        'description' => 'Vollständige Anleitung für Administratoren und Vereinsverwalter',
        'category' => 'manual'
    ),
    'frontend' => array(
        'file' => 'BEDIENUNGSANLEITUNG_FRONTEND.md',
        'title' => 'Frontend-Bedienungsanleitung',
        'icon' => '👥',
        'description' => 'Anleitung für Crew-Mitglieder und Helfer',
        'category' => 'manual'
    ),
    'screenshots' => array(
        'file' => 'SCREENSHOTS.md',
        'title' => 'Screenshot-Anleitung',
        'icon' => '📸',
        'description' => 'Anleitung zum Erstellen von Screenshots für die Dokumentation',
        'category' => 'manual'
    ),
    'changelog' => array(
        'file' => 'CHANGELOG.md',
        'title' => 'Changelog',
        'icon' => '📋',
        'description' => 'Versions-Historie und Änderungsprotokoll (aktuell: v0.9.0)',
        'category' => 'technical'
    ),
    'database' => array(
        'file' => 'DATABASE_STRUCTURE_AKTUELL.md',
        'title' => 'Datenbank-Struktur',
        'icon' => '🗄️',
        'description' => 'Vollständige Dokumentation der Datenbank-Tabellen und Beziehungen',
        'category' => 'technical'
    ),
    'structure' => array(
        'file' => 'STRUCTURE.md',
        'title' => 'Plugin-Struktur',
        'icon' => '🏗️',
        'description' => 'Aufbau und Architektur des Plugins',
        'category' => 'technical'
    ),
    'css-components' => array(
        'file' => 'CSS_COMPONENTS.md',
        'title' => 'CSS-Komponenten',
        'icon' => '🎨',
        'description' => 'Übersicht aller CSS-Klassen und Komponenten',
        'category' => 'technical'
    ),
    'test-plan' => array(
        'file' => 'TEST_PLAN.md',
        'title' => 'Test-Plan',
        'icon' => '✅',
        'description' => 'Test-Szenarien und Qualitätssicherung für UAT',
        'category' => 'technical'
    ),
    'roles' => array(
        'file' => 'ROLLEN-UEBERSICHT.md',
        'title' => 'Rollen & Berechtigungen',
        'icon' => '🔐',
        'description' => 'Übersicht aller Benutzerrollen und Rechte',
        'category' => 'technical'
    ),
    'dienst-zeitfenster' => array(
        'file' => 'DIENST_ZEITFENSTER.md',
        'title' => 'Dienst-Zeitfenster',
        'icon' => '⏰',
        'description' => 'Dokumentation der Zeitfenster-Funktion',
        'category' => 'technical'
    ),
);

// === Rollen-Erkennung ===
$is_super_admin = current_user_can('manage_options');
$is_club_admin  = Dienstplan_Roles::is_restricted_club_admin();

if ($is_super_admin) {
    $role_label = 'WordPress-Admin';
    $role_color = '#2271b1';
    $role_icon  = '&#x1F451;'; // 👑
} elseif ($is_club_admin) {
    $role_label = 'Vereins-Admin';
    $role_color = '#7c3aed';
    $role_icon  = '&#x1F3DB;'; // 🏛️
} else {
    $role_label = 'Veranstaltungs-Admin';
    $role_color = '#059669';
    $role_icon  = '&#x1F4C5;'; // 📅
}

// Dokumente die ausschließlich für WordPress-Admins sichtbar sind
if (!$is_super_admin) {
    $admin_only = array('readme', 'version-090', 'roadmap', 'screenshots', 'changelog', 'database', 'structure', 'css-components', 'test-plan');
    foreach ($admin_only as $k) {
        unset($documents[$k]);
    }
}

// Aktives Dokument – sicherstellen, dass es für die aktuelle Rolle existiert
$active_doc = isset($_GET['doc']) ? sanitize_key($_GET['doc']) : ($is_super_admin ? 'readme' : 'quick-start');
if (!isset($documents[$active_doc])) {
    $active_doc = $is_super_admin ? 'readme' : 'quick-start';
}

// Markdown-Parsing-Funktion (vereinfacht)
function dp_parse_markdown($content) {
    // Überschriften
    $content = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $content);
    $content = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $content);
    $content = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $content);
    
    // Horizontale Linie
    $content = preg_replace('/^---$/m', '<hr>', $content);
    
    // Listen
    $content = preg_replace_callback('/(?:^|\n)(?:[\-\*\+] .+\n?)+/m', function($matches) {
        $list = $matches[0];
        $items = preg_replace('/^[\-\*\+] (.+)$/m', '<li>$1</li>', $list);
        return '<ul>' . $items . '</ul>';
    }, $content);
    
    // Code-Blöcke
    $content = preg_replace('/```([a-z]*)\n(.*?)\n```/s', '<pre><code class="language-$1">$2</code></pre>', $content);
    
    // Inline-Code
    $content = preg_replace('/`([^`]+)`/', '<code>$1</code>', $content);
    
    // Bold
    $content = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $content);
    
    // Italic
    $content = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $content);
    
    // Links
    $content = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2" target="_blank">$1</a>', $content);
    
    // Tabellen
    $content = preg_replace_callback('/(?:^\|.+\|$\n)+/m', function($matches) {
        $table = $matches[0];
        $rows = explode("\n", trim($table));
        $html = '<table class="wp-list-table widefat fixed striped">';
        
        foreach ($rows as $i => $row) {
            // Skip separator row
            if (preg_match('/^\|[\s\-:]+\|$/', $row)) continue;
            
            $cells = array_map('trim', explode('|', trim($row, '|')));
            $tag = ($i === 0) ? 'th' : 'td';
            $html .= '<tr>';
            foreach ($cells as $cell) {
                $html .= "<$tag>" . trim($cell) . "</$tag>";
            }
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        return $html;
    }, $content);
    
    // Paragraphen
    $content = preg_replace('/\n\n/', '</p><p>', $content);
    $content = '<p>' . $content . '</p>';
    
    // Cleanup
    $content = str_replace('<p></p>', '', $content);
    $content = str_replace('<p><h', '<h', $content);
    $content = str_replace('</h1></p>', '</h1>', $content);
    $content = str_replace('</h2></p>', '</h2>', $content);
    $content = str_replace('</h3></p>', '</h3>', $content);
    $content = str_replace('<p><ul>', '<ul>', $content);
    $content = str_replace('</ul></p>', '</ul>', $content);
    $content = str_replace('<p><pre>', '<pre>', $content);
    $content = str_replace('</pre></p>', '</pre>', $content);
    $content = str_replace('<p><hr></p>', '<hr>', $content);
    $content = str_replace('<p><table', '<table', $content);
    $content = str_replace('</table></p>', '</table>', $content);
    
    return $content;
}
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-book" style="font-size: 32px; width: 32px; height: 32px; margin-right: 10px;"></span>
        Dokumentation
        <span style="display:inline-block; margin-left:16px; padding:4px 12px; font-size:13px; font-weight:600; border-radius:20px; background:<?php echo esc_attr($role_color); ?>18; color:<?php echo esc_attr($role_color); ?>; border:1px solid <?php echo esc_attr($role_color); ?>55; vertical-align:middle; line-height:1.4;">
            <?php echo $role_icon; ?>&nbsp;<?php echo esc_html($role_label); ?>
        </span>
    </h1>
    
    <div class="dp-documentation-container" style="display: flex; gap: 20px; margin-top: 20px;">
        <!-- Sidebar Navigation -->
        <div class="dp-doc-sidebar" style="flex: 0 0 280px; background: #fff; padding: 15px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            
            <h3 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #2271b1;">🚀 Einstieg</h3>
            <?php foreach ($documents as $key => $doc): ?>
                <?php if ($doc['category'] === 'start'): ?>
                    <a href="?page=dienstplan-dokumentation&doc=<?php echo esc_attr($key); ?>" 
                       class="dp-doc-link <?php echo ($active_doc === $key) ? 'active' : ''; ?>"
                       style="display: block; padding: 10px; margin-bottom: 5px; text-decoration: none; color: #2271b1; border-left: 3px solid transparent; transition: all 0.2s; <?php echo ($active_doc === $key) ? 'background: #f0f6fc; border-left-color: #2271b1; font-weight: 600;' : ''; ?>">
                        <span style="margin-right: 8px;"><?php echo $doc['icon']; ?></span>
                        <?php echo esc_html($doc['title']); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <h3 style="margin-top: 20px; padding-bottom: 10px; border-bottom: 2px solid #2271b1;">📖 Anleitungen</h3>
            <?php foreach ($documents as $key => $doc): ?>
                <?php if ($doc['category'] === 'manual'): ?>
                    <a href="?page=dienstplan-dokumentation&doc=<?php echo esc_attr($key); ?>" 
                       class="dp-doc-link <?php echo ($active_doc === $key) ? 'active' : ''; ?>"
                       style="display: block; padding: 10px; margin-bottom: 5px; text-decoration: none; color: #2271b1; border-left: 3px solid transparent; transition: all 0.2s; <?php echo ($active_doc === $key) ? 'background: #f0f6fc; border-left-color: #2271b1; font-weight: 600;' : ''; ?>">
                        <span style="margin-right: 8px;"><?php echo $doc['icon']; ?></span>
                        <?php echo esc_html($doc['title']); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <?php if ($is_super_admin): ?>
            <h3 style="margin-top: 20px; padding-bottom: 10px; border-bottom: 2px solid #2271b1;">🔧 Technisch</h3>
            <?php foreach ($documents as $key => $doc): ?>
                <?php if ($doc['category'] === 'technical'): ?>
                    <a href="?page=dienstplan-dokumentation&doc=<?php echo esc_attr($key); ?>" 
                       class="dp-doc-link <?php echo ($active_doc === $key) ? 'active' : ''; ?>"
                       style="display: block; padding: 10px; margin-bottom: 5px; text-decoration: none; color: #2271b1; border-left: 3px solid transparent; transition: all 0.2s; <?php echo ($active_doc === $key) ? 'background: #f0f6fc; border-left-color: #2271b1; font-weight: 600;' : ''; ?>">
                        <span style="margin-right: 8px;"><?php echo $doc['icon']; ?></span>
                        <?php echo esc_html($doc['title']); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <div style="margin-top: 30px; padding: 15px; background: #f0f6fc; border-left: 3px solid #2271b1;">
                <h4 style="margin-top: 0;">ℹ️ Info</h4>
                <p style="margin: 0; font-size: 12px; line-height: 1.5;">
                    <strong>Version:</strong> <?php echo esc_html(DIENSTPLAN_VERSION); ?><br>
                    <strong>Ihre Rolle:</strong> <?php echo $role_icon; ?> <?php echo esc_html($role_label); ?>
                </p>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="dp-doc-content" style="flex: 1; background: #fff; padding: 30px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); min-height: 600px;">
            <?php
            if (isset($documents[$active_doc])) {
                $doc = $documents[$active_doc];
                $file_path = $doc_path . $doc['file'];
                
                if (file_exists($file_path)) {
                    // Header
                    echo '<div class="dp-doc-header" style="margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #f0f0f1;">';
                    echo '<h2 style="margin: 0 0 10px 0; font-size: 28px;">';
                    echo '<span style="margin-right: 10px;">' . $doc['icon'] . '</span>';
                    echo esc_html($doc['title']);
                    echo '</h2>';
                    echo '<p style="margin: 0; color: #646970; font-size: 14px;">' . esc_html($doc['description']) . '</p>';
                    
                    // Download-Button
                    echo '<div style="margin-top: 15px;">';
                    echo '<a href="' . esc_url(plugins_url('documentation/' . $doc['file'], dirname(__FILE__))) . '" class="button button-secondary" download>';
                    echo '<span class="dashicons dashicons-download" style="margin-top: 3px;"></span> Markdown-Datei herunterladen';
                    echo '</a>';
                    echo '</div>';
                    echo '</div>';
                    
                    // Content
                    $content = file_get_contents($file_path);
                    
                    // Entferne den ersten Header (wird bereits oben angezeigt)
                    $content = preg_replace('/^# .+\n/', '', $content);
                    
                    // Entferne Meta-Informationen
                    $content = preg_replace('/\*\*Version:.*?\n/s', '', $content);
                    $content = preg_replace('/\*\*Stand:.*?\n/s', '', $content);
                    $content = preg_replace('/\*\*Zielgruppe:.*?\n/s', '', $content);
                    
                    // Parse Markdown
                    echo '<div class="dp-doc-body" style="line-height: 1.8; font-size: 15px;">';
                    echo dp_parse_markdown($content);
                    echo '</div>';
                    
                    // Screenshot-Hinweise hervorheben
                    echo '<style>
                        .dp-doc-body blockquote {
                            background: #fff8e1;
                            border-left: 4px solid #ffa000;
                            padding: 15px;
                            margin: 20px 0;
                            font-style: italic;
                        }
                        .dp-doc-body code {
                            background: #f5f5f5;
                            padding: 2px 6px;
                            border-radius: 3px;
                            font-family: monospace;
                            font-size: 13px;
                        }
                        .dp-doc-body pre {
                            background: #282c34;
                            color: #abb2bf;
                            padding: 15px;
                            border-radius: 5px;
                            overflow-x: auto;
                            line-height: 1.5;
                        }
                        .dp-doc-body pre code {
                            background: transparent;
                            color: inherit;
                            padding: 0;
                        }
                        .dp-doc-body table {
                            margin: 20px 0;
                        }
                        .dp-doc-body h2 {
                            margin-top: 40px;
                            padding-top: 20px;
                            border-top: 1px solid #f0f0f1;
                            color: #1d2327;
                        }
                        .dp-doc-body h2:first-child {
                            margin-top: 0;
                            padding-top: 0;
                            border-top: none;
                        }
                        .dp-doc-body h3 {
                            margin-top: 30px;
                            color: #2271b1;
                        }
                        .dp-doc-body ul, .dp-doc-body ol {
                            margin: 15px 0;
                            padding-left: 30px;
                        }
                        .dp-doc-body li {
                            margin: 8px 0;
                        }
                        .dp-doc-body a {
                            color: #2271b1;
                            text-decoration: none;
                        }
                        .dp-doc-body a:hover {
                            text-decoration: underline;
                        }
                        .dp-doc-link:hover {
                            background: #f6f7f7 !important;
                        }
                    </style>';
                    
                } else {
                    echo '<div class="notice notice-error" style="padding: 20px;">';
                    echo '<p><strong>Fehler:</strong> Dokumentations-Datei nicht gefunden.</p>';
                    echo '<p>Datei: <code>' . esc_html($doc['file']) . '</code></p>';
                    echo '</div>';
                }
            } else {
                echo '<div class="notice notice-warning" style="padding: 20px;">';
                echo '<p><strong>Hinweis:</strong> Bitte wählen Sie ein Dokument aus der linken Sidebar aus.</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>

<style>
    .dp-doc-sidebar h3:first-child {
        margin-top: 0;
    }
</style>

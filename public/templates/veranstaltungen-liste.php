<?php
/**
 * Frontend Startseite - Veranstaltungen & Vereinsauswahl
 * Modernes Design mit Backend-Styling
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/public/templates
 */

if (!defined('ABSPATH')) exit;

require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
$db = new Dienstplan_Database(DIENSTPLAN_DB_PREFIX);

// Lade alle Vereine
$vereine = $db->get_vereine();
$selected_verein_id = isset($_GET['verein_id']) ? intval($_GET['verein_id']) : null;

// Wenn Verein ausgewählt, lade Veranstaltungen für diesen Verein
$heute = date('Y-m-d');

if ($selected_verein_id) {
    // Lade alle Veranstaltungen
    $alle_veranstaltungen = $db->get_veranstaltungen();
    
    // Filtere Veranstaltungen nach Verein (über Dienste)
    $veranstaltungen = array();
    foreach ($alle_veranstaltungen as $veranstaltung) {
        // Prüfe ob Verein Dienste in dieser Veranstaltung hat
        $dienste = $db->get_dienste($veranstaltung->id);
        $has_verein = false;
        foreach ($dienste as $dienst) {
            if (intval($dienst->verein_id) === $selected_verein_id) {
                $has_verein = true;
                break;
            }
        }
        if ($has_verein) {
            $veranstaltungen[] = $veranstaltung;
        }
    }
    
    // Filtere nach Status und Datum
    $aktuelle_veranstaltungen = array_filter($veranstaltungen, function($v) use ($heute) {
        $start_date = !empty($v->start_datum) ? $v->start_datum : '2099-12-31';
        // Zeige nur 'geplant' und 'aktiv', nicht 'in_planung' oder 'abgeschlossen'
        return (($v->status === 'geplant' || $v->status === 'aktiv') && $start_date >= $heute);
    });
    
    // Sortiere nach Datum
    usort($aktuelle_veranstaltungen, function($a, $b) {
        return strcmp($a->start_datum ?? '', $b->start_datum ?? '');
    });
} else {
    $aktuelle_veranstaltungen = array();
}
?>

<div class="dp-frontend-container">
    <!-- Moderner Header mit Gradient -->
    <div class="dp-frontend-header">
        <div class="dp-header-content">
            <h1 class="dp-frontend-title">Dienste</h1>
            <p class="dp-frontend-subtitle">Wählen Sie einen Verein und tragen Sie sich für Dienste ein</p>
        </div>
    </div>
    
    <!-- Vereinsauswahl - Moderne Card-Grid -->
    <?php if (!$selected_verein_id): ?>
        <div class="dp-vereine-section">
            <h2 class="dp-section-title">Schritt 1: Verein auswählen</h2>
            
            <?php if (empty($vereine)): ?>
                <div class="dp-empty-state">
                    <span class="dp-empty-icon">📭</span>
                    <h3>Keine Vereine verfügbar</h3>
                    <p>Derzeit sind keine Vereine registriert.</p>
                </div>
            <?php else: ?>
                <div class="dp-vereine-grid">
                    <?php foreach ($vereine as $verein): 
                        $logo_url = !empty($verein->logo_id) ? wp_get_attachment_url($verein->logo_id) : null;
                        $verein_page_url = '';
                        $verein_share_url = '';
                        if (!empty($verein->seite_id)) {
                            $verein_page = get_post(intval($verein->seite_id));
                            if ($verein_page && $verein_page->post_type === 'page' && $verein_page->post_status !== 'trash') {
                                $verein_page_url = get_permalink($verein_page->ID);
                                $verein_share_url = wp_get_shortlink($verein_page->ID);
                                if (empty($verein_share_url)) {
                                    $verein_share_url = add_query_arg('p', $verein_page->ID, home_url('/'));
                                }
                            }
                        }
                        $verein_link_url = !empty($verein_page_url) ? $verein_page_url : add_query_arg('verein_id', $verein->id);
                    ?>
                        <a href="<?php echo esc_url($verein_link_url); ?>" class="dp-verein-card">
                            <div class="dp-verein-card-header">
                                <?php if ($logo_url): ?>
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($verein->name); ?>" class="dp-verein-logo">
                                <?php else: ?>
                                    <div class="dp-verein-icon">🏢</div>
                                <?php endif; ?>
                                <h3 class="dp-verein-name"><?php echo esc_html($verein->name); ?></h3>
                            </div>
                            <div class="dp-verein-arrow">→</div>
                            <?php if (!empty($verein_share_url)): ?>
                                <span class="dp-inline-share" onclick="event.preventDefault(); event.stopPropagation(); dpShareLink('<?php echo esc_js($verein_share_url); ?>', '<?php echo esc_js($verein->name); ?>');">Teilen</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Zurück-Button -->
        <div class="dp-back-section">
            <a href="<?php echo esc_url(remove_query_arg('verein_id')); ?>" class="dp-back-btn">
                ← Verein ändern
            </a>
            <?php 
            $selected_verein = null;
            foreach ($vereine as $v) {
                if ($v->id == $selected_verein_id) {
                    $selected_verein = $v;
                    break;
                }
            }
            ?>
            <?php if ($selected_verein): 
                $logo_url = !empty($selected_verein->logo_id) ? wp_get_attachment_url($selected_verein->logo_id) : null;
            ?>
                <div class="dp-selected-verein">
                    <?php if ($logo_url): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($selected_verein->name); ?>" class="dp-verein-logo-small">
                    <?php else: ?>
                        <span class="dp-verein-icon">🏢</span>
                    <?php endif; ?>
                    <span class="dp-selected-name"><?php echo esc_html($selected_verein->name); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Veranstaltungsliste für ausgewählten Verein -->
        <div class="dp-veranstaltungen-section">
            <h2 class="dp-section-title">Schritt 2: Veranstaltung wählen</h2>
            
            <?php if (empty($aktuelle_veranstaltungen)): ?>
                <div class="dp-empty-state">
                    <span class="dp-empty-icon">📅</span>
                    <h3>Keine Veranstaltungen verfügbar</h3>
                    <p>Derzeit sind keine Veranstaltungen für <?php echo esc_html($selected_verein->name); ?> geplant.</p>
                </div>
            <?php else: ?>
                <div class="dp-veranstaltungen-grid">
                    <?php foreach ($aktuelle_veranstaltungen as $veranstaltung): 
                        // Bestimme die URL zur Event-Seite
                        $event_url = !empty($veranstaltung->seite_id) 
                            ? get_permalink($veranstaltung->seite_id) 
                            : add_query_arg(array('veranstaltung_id' => $veranstaltung->id, 'verein_id' => $selected_verein_id));
                        $event_share_url = '';
                        if (!empty($veranstaltung->seite_id)) {
                            $event_share_url = wp_get_shortlink($veranstaltung->seite_id);
                            if (empty($event_share_url)) {
                                $event_share_url = add_query_arg('p', intval($veranstaltung->seite_id), home_url('/'));
                            }
                        }
                        
                        // Formatiere Datum
                        $start_datum = !empty($veranstaltung->start_datum) ? date_i18n('d.m.Y', strtotime($veranstaltung->start_datum)) : '';
                        $end_datum = !empty($veranstaltung->end_datum) ? date_i18n('d.m.Y', strtotime($veranstaltung->end_datum)) : '';
                        $datum_text = $start_datum;
                        if ($end_datum && $end_datum !== $start_datum) {
                            $datum_text .= ' - ' . $end_datum;
                        }
                    ?>
                        <a href="<?php echo esc_url($event_url); ?>" class="dp-veranstaltung-card">
                            <div class="dp-veranstaltung-card-header">
                                <div class="dp-veranstaltung-icon">📅</div>
                                <div class="dp-veranstaltung-info">
                                    <h3 class="dp-veranstaltung-name"><?php echo esc_html($veranstaltung->name); ?></h3>
                                    <?php if ($datum_text): ?>
                                        <p class="dp-veranstaltung-datum">📆 <?php echo esc_html($datum_text); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($veranstaltung->beschreibung)): ?>
                                        <p class="dp-veranstaltung-beschreibung"><?php echo esc_html(wp_trim_words($veranstaltung->beschreibung, 15)); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="dp-veranstaltung-arrow">→</div>
                            <?php if (!empty($event_share_url)): ?>
                                <span class="dp-inline-share" onclick="event.preventDefault(); event.stopPropagation(); dpShareLink('<?php echo esc_js($event_share_url); ?>', '<?php echo esc_js($veranstaltung->name); ?>');">Teilen</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- Footer -->
    <div class="dp-frontend-footer">
        <p class="dp-footer-text">
            🔒 Ihre Daten werden nur zur Organisation dieser Veranstaltungen verwendet.
        </p>
    </div>
</div>

<style>
:root {
    --dp-primary: #667eea;
    --dp-primary-dark: #5568d3;
    --dp-primary-light: #a5b4fc;
    --dp-gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --dp-gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --dp-success: #10b981;
    --dp-danger: #ef4444;
    --dp-bg: #f8fafc;
    --dp-surface: #ffffff;
    --dp-gray-100: #f3f4f6;
    --dp-gray-200: #e5e7eb;
    --dp-gray-500: #6b7280;
    --dp-gray-700: #374151;
    --dp-gray-900: #111827;
    --dp-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --dp-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

* {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.dp-frontend-container {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    width: 100%;
    padding: 2rem 2rem;
    background: var(--dp-bg);
}

.dp-frontend-header {
    background: var(--dp-gradient-primary);
    border-radius: 0.75rem;
    padding: 1.5rem 2rem;
    margin-bottom: 2rem;
    color: white;
    box-shadow: var(--dp-shadow);
    text-align: center;
}

.dp-header-content h1 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 700;
}

.dp-frontend-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.dp-frontend-subtitle {
    font-size: 0.95rem;
    opacity: 0.9;
    margin: 0;
}

.dp-section-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 1.5rem 0;
    color: var(--dp-gray-900);
}

/* Vereine Grid */
.dp-vereine-section {
    margin-bottom: 3rem;
}

.dp-vereine-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

/* Veranstaltungen Grid */
.dp-veranstaltungen-section {
    margin-bottom: 3rem;
}

.dp-veranstaltungen-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.dp-veranstaltung-card {
    background: white;
    border: 2px solid var(--dp-gray-200);
    border-radius: 1rem;
    padding: 1.5rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.3s ease;
    cursor: pointer;
}

.dp-veranstaltung-card:hover {
    border-color: var(--dp-primary);
    box-shadow: var(--dp-shadow-lg);
    transform: translateY(-4px);
    background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
}

.dp-inline-share {
    margin-top: 0.6rem;
    align-self: flex-start;
    border: 1px solid var(--dp-primary-light);
    border-radius: 9999px;
    background: #eff6ff;
    color: var(--dp-primary-dark);
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.2rem 0.55rem;
}

.dp-veranstaltung-card-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    flex: 1;
}

.dp-veranstaltung-icon {
    font-size: 2.5rem;
    flex-shrink: 0;
}

.dp-veranstaltung-info {
    flex: 1;
}

.dp-veranstaltung-name {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dp-gray-900);
}

.dp-veranstaltung-datum {
    margin: 0 0 0.5rem 0;
    color: var(--dp-primary);
    font-size: 0.95rem;
    font-weight: 600;
}

.dp-veranstaltung-beschreibung {
    margin: 0;
    color: var(--dp-gray-500);
    font-size: 0.9rem;
    line-height: 1.5;
}

.dp-veranstaltung-arrow {
    color: var(--dp-primary);
    font-weight: bold;
    font-size: 1.5rem;
    align-self: center;
    transition: transform 0.3s;
    flex-shrink: 0;
}

.dp-veranstaltung-card:hover .dp-veranstaltung-arrow {
    transform: translateX(4px);
}

.dp-verein-card {
    background: white;
    border: 2px solid var(--dp-gray-200);
    border-radius: 1rem;
    padding: 1.5rem;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.3s ease;
    cursor: pointer;
}

.dp-verein-card:hover {
    border-color: var(--dp-primary);
    box-shadow: var(--dp-shadow-lg);
    transform: translateY(-4px);
    background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
}

.dp-verein-card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.dp-verein-icon {
    font-size: 2rem;
    background: var(--dp-gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.dp-verein-logo {
    width: 60px;
    height: 60px;
    object-fit: contain;
    border-radius: 0.5rem;
}

.dp-verein-logo-small {
    width: 30px;
    height: 30px;
    object-fit: contain;
    border-radius: 0.25rem;
}

.dp-verein-name {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dp-gray-900);
}

.dp-verein-description {
    margin: 0 0 1rem 0;
    color: var(--dp-gray-500);
    font-size: 0.95rem;
}

.dp-verein-arrow {
    color: var(--dp-primary);
    font-weight: bold;
    align-self: flex-start;
    transition: transform 0.3s;
}

.dp-verein-card:hover .dp-verein-arrow {
    transform: translateX(4px);
}

/* Zurück-Button */
.dp-back-section {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid var(--dp-gray-200);
}

.dp-back-btn {
    color: var(--dp-primary);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
}

.dp-back-btn:hover {
    color: var(--dp-primary-dark);
}

.dp-selected-verein {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: var(--dp-gray-100);
    border-radius: 0.5rem;
    font-weight: 600;
    color: var(--dp-gray-900);
}

.dp-selected-name {
    color: var(--dp-primary);
}

/* Veranstaltungen */
.dp-veranstaltungen-section {
    margin-bottom: 3rem;
}

.dp-events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.dp-event-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: var(--dp-shadow);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.dp-event-card:hover {
    box-shadow: var(--dp-shadow-lg);
    transform: translateY(-4px);
}

.dp-event-header {
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--dp-gray-200);
}

.dp-event-date {
    background: var(--dp-gradient-primary);
    color: white;
    border-radius: 0.75rem;
    padding: 0.5rem;
    text-align: center;
    flex-shrink: 0;
    min-width: 50px;
}

.dp-date-day {
    font-size: 1.5rem;
    font-weight: 700;
}

.dp-date-month {
    font-size: 0.75rem;
    text-transform: uppercase;
    opacity: 0.9;
}

.dp-event-info {
    flex: 1;
}

.dp-event-name {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--dp-gray-900);
}

.dp-event-location {
    margin: 0.5rem 0 0 0;
    color: var(--dp-gray-500);
    font-size: 0.9rem;
}

.dp-badge {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
    white-space: nowrap;
}

.dp-badge-success {
    background: #d1fae5;
    color: #065f46;
}

.dp-badge-full {
    background: #fee2e2;
    color: #991b1b;
}

.dp-event-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: var(--dp-gray-50);
    border-radius: 0.75rem;
}

.dp-stat {
    display: flex;
    flex-direction: column;
    text-align: center;
}

.dp-stat-label {
    font-size: 0.75rem;
    color: var(--dp-gray-500);
    text-transform: uppercase;
    font-weight: 600;
}

.dp-stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dp-primary);
}

/* Button */
.dp-btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0.75rem;
    border: none;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.dp-btn-primary {
    background: var(--dp-gradient-primary);
    color: white;
}

.dp-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--dp-shadow-lg);
}

.dp-btn-arrow {
    transition: transform 0.3s;
}

.dp-btn-primary:hover .dp-btn-arrow {
    transform: translateX(4px);
}

/* Empty State */
.dp-empty-state {
    text-align: center;
    padding: 3rem 2rem;
    background: white;
    border-radius: 1rem;
    border: 2px dashed var(--dp-gray-200);
}

.dp-empty-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 1rem;
}

.dp-empty-state h3 {
    margin: 0 0 0.5rem 0;
    color: var(--dp-gray-900);
}

.dp-empty-state p {
    margin: 0;
    color: var(--dp-gray-500);
}

/* Footer */
.dp-frontend-footer {
    text-align: center;
    padding: 2rem;
    background: var(--dp-gray-50);
    border-radius: 1rem;
    margin-top: 3rem;
}

.dp-footer-text {
    margin: 0;
    color: var(--dp-gray-600);
    font-size: 0.95rem;
}

/* Empty State */
.dp-empty-state {
    text-align: center;
    padding: 3rem 2rem;
    background: white;
    border-radius: 1rem;
    border: 2px dashed var(--dp-gray-200);
}

.dp-empty-icon {
    font-size: 4rem;
    display: block;
    margin-bottom: 1rem;
}

.dp-empty-state h3 {
    margin: 0 0 0.5rem 0;
    color: var(--dp-gray-900);
    font-size: 1.25rem;
}

.dp-empty-state p {
    margin: 0;
    color: var(--dp-gray-500);
}

/* Responsive */
@media (max-width: 768px) {
    .dp-frontend-container {
        padding: 1rem 0.75rem;
    }
    
    .dp-frontend-header {
        padding: 2rem 1rem;
        margin-bottom: 2rem;
    }
    
    .dp-frontend-title {
        font-size: 1.875rem;
    }
    
    .dp-frontend-subtitle {
        font-size: 1rem;
    }
    
    .dp-vereine-grid,
    .dp-veranstaltungen-grid,
    .dp-events-grid {
        grid-template-columns: 1fr;
    }
    
    .dp-event-header {
        gap: 1rem;
    }
    
    .dp-event-stats {
        gap: 0.75rem;
        padding: 0.75rem;
    }
    
    .dp-back-section {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .dp-selected-verein {
        width: 100%;
    }
}
</style>

<script>
function dpShareLink(url, eventName) {
    if (!url) {
        alert('Kein Link verfügbar.');
        return;
    }

    if (navigator.share) {
        navigator.share({
            title: eventName ? ('Dienstplan: ' + eventName) : 'Dienstplan',
            url: url
        }).catch(function() {
            // Ignorieren (z. B. Abbruch durch Nutzer)
        });
        return;
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url)
            .then(function() {
                alert('Link kopiert: ' + url);
            })
            .catch(function() {
                window.prompt('Link zum Teilen kopieren:', url);
            });
        return;
    }

    window.prompt('Link zum Teilen kopieren:', url);
}
</script>

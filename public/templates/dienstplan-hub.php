<?php
/**
 * Dienstplan Hub - Frontend Einstiegsseite
 * Hauptportal für Benutzer mit Anmeldung und Veranstaltungsübersicht
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/public/templates
 */

if (!defined('ABSPATH')) exit;

require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
$db = new Dienstplan_Database(DIENSTPLAN_DB_PREFIX);
global $wpdb;

// Aktuelle Veranstaltungen laden (Status: geplant oder aktiv)
$heute = date('Y-m-d');
$alle_veranstaltungen = $db->get_veranstaltungen();

$aktuelle_veranstaltungen = array_filter($alle_veranstaltungen, function($v) use ($heute) {
    $start_date = !empty($v->start_datum) ? $v->start_datum : '2099-12-31';
    return (($v->status === 'geplant' || $v->status === 'aktiv') && $start_date >= $heute);
});

// Sortiere nach Datum
usort($aktuelle_veranstaltungen, function($a, $b) {
    return strcmp($a->start_datum ?? '', $b->start_datum ?? '');
});

// Nur die nächsten 6 Veranstaltungen
$aktuelle_veranstaltungen = array_slice($aktuelle_veranstaltungen, 0, 6);

// Prüfe ob Benutzer eingeloggt ist
$is_logged_in = is_user_logged_in();
$current_user = wp_get_current_user();
$current_mitarbeiter_id = 0;
$assigned_event_ids = array();
$crew_allowed_verein_ids = array();

// Logging für eingeloggte Portal-Benutzer
if ($is_logged_in) {
    require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-dienstplan-roles.php';
    if (in_array(Dienstplan_Roles::ROLE_CREW, $current_user->roles)) {
        // Finde Mitarbeiter-ID
        global $wpdb;
        $mitarbeiter_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dp_mitarbeiter WHERE user_id = %d",
            $current_user->ID
        ));
        $current_mitarbeiter_id = intval($mitarbeiter_id);

        $user_verein_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT verein_id FROM {$wpdb->prefix}dp_user_vereine WHERE user_id = %d",
            $current_user->ID
        ));

        $crew_allowed_verein_ids = array_values(array_unique(array_filter(array_map('intval', (array) $user_verein_ids))));
        sort($crew_allowed_verein_ids);

        if ($current_mitarbeiter_id > 0) {
            $assigned_event_ids_raw = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT d.veranstaltung_id
                 FROM {$wpdb->prefix}dp_dienst_slots s
                 INNER JOIN {$wpdb->prefix}dp_dienste d ON d.id = s.dienst_id
                 WHERE s.mitarbeiter_id = %d AND d.veranstaltung_id IS NOT NULL",
                $current_mitarbeiter_id
            ));

            if (!empty($assigned_event_ids_raw)) {
                $assigned_event_ids = array_map('intval', $assigned_event_ids_raw);
            }
        }
        
        // Log Portal-Zugriff
        $wpdb->insert(
            $wpdb->prefix . 'dp_portal_access_log',
            array(
                'user_id' => $current_user->ID,
                'mitarbeiter_id' => $mitarbeiter_id,
                'action' => 'view_portal',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
}

if ($is_logged_in && in_array(Dienstplan_Roles::ROLE_CREW, $current_user->roles, true)) {
    if (empty($crew_allowed_verein_ids)) {
        $aktuelle_veranstaltungen = array();
    } else {
        $prefix = $wpdb->prefix . DIENSTPLAN_DB_PREFIX;
        $crew_allowed_events = $wpdb->get_col(
            "SELECT DISTINCT veranstaltung_id
             FROM {$prefix}veranstaltung_vereine
             WHERE verein_id IN (" . implode(',', array_map('intval', $crew_allowed_verein_ids)) . ")"
        );
        $crew_allowed_event_ids = array_values(array_unique(array_filter(array_map('intval', (array) $crew_allowed_events))));

        $aktuelle_veranstaltungen = array_values(array_filter($aktuelle_veranstaltungen, function($event) use ($crew_allowed_event_ids) {
            return in_array(intval($event->id), $crew_allowed_event_ids, true);
        }));
    }
}

// View-Handling
$view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'hub';

if ($view === 'meine-dienste') {
    echo do_shortcode('[meine_dienste]');
    return;
}

if ($view === 'profil' && $is_logged_in) {
    include DIENSTPLAN_PLUGIN_PATH . 'public/templates/profil-bearbeiten.php';
    return;
}

if (in_array($view, array('anmeldung', 'details', 'kachel', 'kompakt', 'timeline'), true) && !empty($_GET['veranstaltung_id'])) {
    $hub_veranstaltung_id = intval($_GET['veranstaltung_id']);
    $hub_verein_id = isset($_GET['verein_id']) ? intval($_GET['verein_id']) : 0;
    $hub_view_mode = in_array($view, array('kachel', 'kompakt', 'timeline'), true) ? $view : 'kachel';
    echo do_shortcode('[dienstplan veranstaltung_id="' . $hub_veranstaltung_id . '" verein_id="' . $hub_verein_id . '" view="' . $hub_view_mode . '"]');
    return;
}

$hub_base_url = get_permalink();
?>

<div class="dp-hub-container">
    <!-- Hero-Bereich mit modernem Gradient -->
    <div class="dp-hub-hero">
        <div class="dp-hero-content">
            <?php if ($is_logged_in): ?>
                <h1 class="dp-hero-title">Willkommen zurück, <?php echo esc_html($current_user->display_name); ?>! 👋</h1>
                <p class="dp-hero-subtitle">Verwalten Sie Ihre Dienste und sehen Sie aktuelle Veranstaltungen</p>
            <?php else: ?>
                <h1 class="dp-hero-title">Dienstplan Portal</h1>
                <p class="dp-hero-subtitle">Melden Sie sich an, um Dienste zu übernehmen und Ihre Einsätze zu verwalten</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Login/User-Bereich -->
    <?php if (!$is_logged_in): ?>
        <div class="dp-hub-login-section">
            <div class="dp-login-card">
                <div class="dp-login-icon">🔐</div>
                <h2>Anmelden</h2>
                <p>Melden Sie sich an, um auf alle Funktionen zugreifen zu können</p>
                
                <!-- Login-Formular direkt eingebettet -->
                <form method="post" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>" class="dp-login-form">
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr(get_permalink()); ?>">
                    
                    <div class="dp-form-group">
                        <label for="user_login">Benutzername oder E-Mail</label>
                        <input type="text" name="log" id="user_login" class="dp-form-input" required>
                    </div>
                    
                    <div class="dp-form-group">
                        <label for="user_pass">Passwort</label>
                        <input type="password" name="pwd" id="user_pass" class="dp-form-input" required>
                    </div>
                    
                    <div class="dp-form-group dp-form-remember">
                        <label>
                            <input type="checkbox" name="rememberme" value="forever">
                            Angemeldet bleiben
                        </label>
                    </div>
                    
                    <button type="submit" class="dp-btn dp-btn-primary dp-btn-block">
                        <span class="dashicons dashicons-admin-users"></span>
                        Anmelden
                    </button>
                    
                    <div class="dp-login-links">
                        <a href="<?php echo wp_lostpassword_url(get_permalink()); ?>" class="dp-link-forgot">
                            Passwort vergessen?
                        </a>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Quick-Links für eingeloggte User -->
        <div class="dp-hub-quick-links">
            <a href="<?php echo esc_url(add_query_arg(array('view' => 'profil'), $hub_base_url)); ?>" class="dp-quick-link">
                <span class="dp-link-icon">👤</span>
                <div class="dp-link-content">
                    <h3>Mein Profil</h3>
                    <p>Einstellungen & Daten</p>
                </div>
                <span class="dp-link-arrow">→</span>
            </a>
        </div>
    <?php endif; ?>

    <!-- Aktuelle Veranstaltungen -->
    <div class="dp-hub-events-section">
        <div class="dp-section-header">
            <h2 class="dp-section-title">
                <span class="dp-title-icon">📅</span>
                Aktuelle Veranstaltungen
            </h2>
            <?php if (count($alle_veranstaltungen) > 6): ?>
                <a href="<?php echo esc_url(add_query_arg(array('view' => 'alle-veranstaltungen'), $hub_base_url)); ?>" class="dp-link-more">
                    Alle anzeigen →
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($aktuelle_veranstaltungen)): ?>
            <div class="dp-empty-state">
                <span class="dp-empty-icon">📭</span>
                <h3>Keine aktuellen Veranstaltungen</h3>
                <p>Derzeit sind keine Veranstaltungen geplant.</p>
            </div>
        <?php else: ?>
            <div class="dp-events-grid">
                <?php foreach ($aktuelle_veranstaltungen as $veranstaltung): 
                    // Status-Informationen
                    $status_class = '';
                    $status_text = '';
                    $status_icon = '';
                    switch($veranstaltung->status) {
                        case 'geplant':
                            $status_class = 'status-geplant';
                            $status_text = 'Geplant';
                            $status_icon = '🟢';
                            break;
                        case 'aktiv':
                            $status_class = 'status-aktiv';
                            $status_text = 'Aktiv';
                            $status_icon = '🟡';
                            break;
                    }
                    
                    // Datum formatieren
                    $start_datum = !empty($veranstaltung->start_datum) ? date_i18n('d.m.Y', strtotime($veranstaltung->start_datum)) : 'k.A.';
                    $end_datum = !empty($veranstaltung->end_datum) ? date_i18n('d.m.Y', strtotime($veranstaltung->end_datum)) : '';
                    
                    // Anzahl Tage
                    $anzahl_tage = 0;
                    if (!empty($veranstaltung->start_datum) && !empty($veranstaltung->end_datum)) {
                        $start = new DateTime($veranstaltung->start_datum);
                        $end = new DateTime($veranstaltung->end_datum);
                        $anzahl_tage = $start->diff($end)->days + 1;
                    }
                    
                    // Beteiligte Vereine laden
                    $prefix = $wpdb->prefix . DIENSTPLAN_DB_PREFIX;
                    $vereine = $wpdb->get_results($wpdb->prepare(
                        "SELECT DISTINCT v.* 
                         FROM {$prefix}vereine v
                         INNER JOIN {$prefix}veranstaltung_vereine vv ON v.id = vv.verein_id
                         WHERE vv.veranstaltung_id = %d
                         ORDER BY v.name",
                        $veranstaltung->id
                    ));

                    $hat_uebernommen = in_array(intval($veranstaltung->id), $assigned_event_ids, true);
                ?>
                    <div class="dp-event-card">
                        <div class="dp-event-header">
                            <div class="dp-event-badge <?php echo $status_class; ?>">
                                <?php echo $status_icon . ' ' . $status_text; ?>
                            </div>
                            <div class="dp-event-header-right">
                                <?php if ($hat_uebernommen): ?>
                                    <div class="dp-event-own-marker">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        Dienst übernommen
                                    </div>
                                <?php endif; ?>
                                <?php if ($anzahl_tage > 0): ?>
                                    <div class="dp-event-duration">
                                        <?php echo $anzahl_tage; ?> Tag<?php echo $anzahl_tage !== 1 ? 'e' : ''; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <h3 class="dp-event-title"><?php echo esc_html($veranstaltung->name); ?></h3>
                        
                        <?php if (!empty($veranstaltung->ort)): ?>
                            <div class="dp-event-location">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($veranstaltung->ort); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="dp-event-date">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php 
                            if ($end_datum && $start_datum !== $end_datum) {
                                echo $start_datum . ' - ' . $end_datum;
                            } else {
                                echo $start_datum;
                            }
                            ?>
                        </div>
                        
                        <?php if (!empty($vereine)): ?>
                            <div class="dp-event-vereine">
                                <span class="dp-vereine-label">Vereine:</span>
                                <div class="dp-vereine-list">
                                    <?php 
                                    $verein_namen = array_map(function($v) { return esc_html($v->name); }, $vereine);
                                    echo implode(' • ', array_slice($verein_namen, 0, 3));
                                    if (count($vereine) > 3) {
                                        echo ' <span class="dp-more-count">+' . (count($vereine) - 3) . '</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="dp-event-actions">
                            <?php 
                            // Prüfe ob Anmeldeseiten existieren
                            $has_pages = !empty(get_posts(array(
                                'post_type' => 'page',
                                'posts_per_page' => 1,
                                'meta_query' => array(
                                    array('key' => '_dp_veranstaltung_id', 'value' => $veranstaltung->id)
                                )
                            )));

                            $event_base_args = array(
                                'veranstaltung_id' => intval($veranstaltung->id),
                                'view' => 'kachel',
                            );
                            
                            if ($has_pages && $hat_uebernommen): ?>
                                <a href="<?php echo esc_url(add_query_arg(array_merge($event_base_args, array('availability' => 'mine')), $hub_base_url)); ?>" class="dp-btn dp-btn-primary">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    Zu meinem Dienst
                                </a>
                            <?php elseif ($has_pages && $veranstaltung->status === 'geplant'): ?>
                                <a href="<?php echo esc_url(add_query_arg($event_base_args, $hub_base_url)); ?>" class="dp-btn dp-btn-primary">
                                    <span class="dashicons dashicons-yes"></span>
                                    Zur Veranstaltung
                                </a>
                            <?php elseif ($veranstaltung->status === 'aktiv'): ?>
                                <a href="<?php echo esc_url(add_query_arg($event_base_args, $hub_base_url)); ?>" class="dp-btn dp-btn-secondary">
                                    <span class="dashicons dashicons-visibility"></span>
                                    Details ansehen
                                </a>
                            <?php else: ?>
                                <button class="dp-btn dp-btn-disabled" disabled>
                                    <span class="dashicons dashicons-lock"></span>
                                    Noch nicht verfügbar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Informations-Bereich -->
    <div class="dp-hub-info-section">
        <div class="dp-info-card">
            <h3>ℹ️ Wie funktioniert's?</h3>
            <ol class="dp-info-list">
                <li>Wählen Sie eine Veranstaltung aus der Liste</li>
                <li>Registrieren Sie sich oder melden Sie sich an</li>
                <li>Tragen Sie sich für Dienste ein</li>
                <li>Sie sehen direkt an der Kachel, ob ein Dienst bereits übernommen wurde</li>
            </ol>
        </div>
    </div>
</div>

<style>
/* Dienstplan Hub Styles */
.dp-hub-container {
    max-width: none;
    margin: 0;
    padding: 2rem 1rem;
}

/* Hero-Bereich */
.dp-hub-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 1rem;
    padding: 3rem 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.dp-hero-title {
    color: white;
    font-size: 2.5rem;
    font-weight: 800;
    margin: 0 0 1rem 0;
}

.dp-hero-subtitle {
    color: rgba(255, 255, 255, 0.95);
    font-size: 1.25rem;
    margin: 0;
}

/* Login-Sektion */
.dp-hub-login-section {
    margin-bottom: 3rem;
}

.dp-login-card {
    background: white;
    border-radius: 1rem;
    padding: 2.5rem;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    max-width: 500px;
    margin: 0 auto;
}

.dp-login-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.dp-login-card h2 {
    margin: 0 0 0.5rem 0;
    color: #1f2937;
}

.dp-login-card p {
    color: #6b7280;
    margin-bottom: 1.5rem;
}

.dp-login-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* Quick-Links */
.dp-hub-quick-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
    margin-bottom: 3rem;
}

.dp-quick-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
    text-decoration: none;
    transition: all 0.2s;
    border: 2px solid transparent;
}

.dp-quick-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    border-color: #667eea;
}

.dp-link-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.dp-link-content h3 {
    margin: 0 0 0.25rem 0;
    color: #1f2937;
    font-size: 1.125rem;
}

.dp-link-content p {
    margin: 0;
    color: #6b7280;
    font-size: 0.875rem;
}

.dp-link-arrow {
    margin-left: auto;
    font-size: 1.5rem;
    color: #667eea;
}

/* Events-Sektion */
.dp-hub-events-section {
    margin-bottom: 3rem;
}

.dp-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.dp-section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.dp-title-icon {
    font-size: 2rem;
}

.dp-link-more {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
}

.dp-link-more:hover {
    color: #764ba2;
}

/* Events-Grid */
.dp-events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.dp-event-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s;
    border: 2px solid transparent;
}

.dp-event-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    border-color: #667eea;
}

.dp-event-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.dp-event-header-right {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
}

.dp-event-own-marker {
    display: inline-flex;
    align-items: center;
    gap: 0.28rem;
    padding: 0.28rem 0.55rem;
    border-radius: 999px;
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    color: #166534;
    border: 1px solid #86efac;
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
}

.dp-event-own-marker .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.dp-event-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
}

.dp-event-badge.status-geplant {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.dp-event-badge.status-aktiv {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
}

.dp-event-duration {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 600;
}

.dp-event-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 1rem 0;
}

.dp-event-location,
.dp-event-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.dp-event-vereine {
    margin: 1rem 0;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.dp-vereine-label {
    font-size: 0.75rem;
    color: #9ca3af;
    text-transform: uppercase;
    font-weight: 600;
    display: block;
    margin-bottom: 0.5rem;
}

.dp-vereine-list {
    font-size: 0.875rem;
    color: #4b5563;
}

.dp-more-count {
    color: #667eea;
    font-weight: 600;
}

.dp-event-actions {
    margin-top: 1rem;
}

/* Buttons */
.dp-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
    font-size: 0.9375rem;
}

.dp-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.dp-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(102, 126, 234, 0.3);
}

.dp-btn-secondary {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
}

.dp-btn-secondary:hover {
    background: #667eea;
    color: white;
}

.dp-btn-disabled {
    background: #f3f4f6;
    color: #9ca3af;
    cursor: not-allowed;
}

/* Info-Sektion */
.dp-hub-info-section {
    margin-bottom: 2rem;
}

.dp-info-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border-radius: 1rem;
    padding: 2rem;
    border-left: 4px solid #3b82f6;
}

.dp-info-card h3 {
    margin: 0 0 1rem 0;
    color: #1e40af;
}

.dp-info-list {
    margin: 0;
    padding-left: 1.5rem;
    color: #1e3a8a;
}

.dp-info-list li {
    margin-bottom: 0.5rem;
}

/* Empty State */
.dp-empty-state {
    text-align: center;
    padding: 3rem 2rem;
    background: #f9fafb;
    border-radius: 1rem;
}

.dp-empty-icon {
    font-size: 4rem;
    display: block;
    margin-bottom: 1rem;
}

.dp-empty-state h3 {
    color: #374151;
    margin: 0 0 0.5rem 0;
}

.dp-empty-state p {
    color: #6b7280;
    margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .dp-hero-title {
        font-size: 1.875rem;
    }
    
    .dp-hero-subtitle {
        font-size: 1rem;
    }
    
    .dp-events-grid {
        grid-template-columns: 1fr;
    }
    
    .dp-login-buttons {
        flex-direction: column;
    }
    
    .dp-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php
/**
 * Template: Profil bearbeiten
 * Ermöglicht Mitarbeitern ihr Profil zu bearbeiten
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/public/templates
 */

if (!defined('ABSPATH')) exit;

// Sicherheitscheck
if (!is_user_logged_in()) {
    echo '<div class="dp-error">Bitte melden Sie sich an, um Ihr Profil zu bearbeiten.</div>';
    return;
}

$current_user = wp_get_current_user();
require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-database.php';
$db = new Dienstplan_Database(DIENSTPLAN_DB_PREFIX);

// Lade Mitarbeiter-Daten
global $wpdb;
$mitarbeiter = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}dp_mitarbeiter WHERE user_id = %d",
    $current_user->ID
));

if (!$mitarbeiter) {
    echo '<div class="dp-error">Mitarbeiter-Profil nicht gefunden.</div>';
    return;
}

// Handle Formular-Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dp_update_profile'])) {
    check_admin_referer('dp_update_profile');
    
    $vorname = sanitize_text_field($_POST['vorname']);
    $nachname = sanitize_text_field($_POST['nachname']);
    $email = sanitize_email($_POST['email']);
    $telefon = sanitize_text_field($_POST['telefon']);
    
    // Update Mitarbeiter-Daten
    $wpdb->update(
        $wpdb->prefix . 'dp_mitarbeiter',
        array(
            'vorname' => $vorname,
            'nachname' => $nachname,
            'email' => $email,
            'telefon' => $telefon
        ),
        array('id' => $mitarbeiter->id),
        array('%s', '%s', '%s', '%s'),
        array('%d')
    );
    
    // Update WordPress-User
    wp_update_user(array(
        'ID' => $current_user->ID,
        'first_name' => $vorname,
        'last_name' => $nachname,
        'user_email' => $email,
        'display_name' => $vorname . ' ' . $nachname
    ));
    
    // Passwort ändern wenn angegeben
    if (!empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
        if ($_POST['new_password'] === $_POST['confirm_password']) {
            wp_set_password($_POST['new_password'], $current_user->ID);
            wp_set_auth_cookie($current_user->ID); // Automatisch wieder einloggen
            $password_changed = true;
        } else {
            $password_error = 'Passwörter stimmen nicht überein.';
        }
    }
    
    // Reload Daten
    $mitarbeiter = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}dp_mitarbeiter WHERE user_id = %d",
        $current_user->ID
    ));
    
    $success_message = 'Profil erfolgreich aktualisiert!';
    if (isset($password_changed)) {
        $success_message .= ' Passwort wurde geändert.';
    }
}

?>

<div class="dp-public-container dp-profil-bearbeiten">
    <div class="dp-header">
        <h2 class="dp-title">
            <span class="dp-icon">👤</span>
            Mein Profil
        </h2>
        <p class="dp-subtitle">Verwalten Sie Ihre persönlichen Daten</p>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="dp-notice dp-notice-success">
            <span class="dashicons dashicons-yes-alt"></span>
            <?php echo esc_html($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($password_error)): ?>
        <div class="dp-notice dp-notice-error">
            <span class="dashicons dashicons-warning"></span>
            <?php echo esc_html($password_error); ?>
        </div>
    <?php endif; ?>
    
    <div class="dp-profile-grid">
        <!-- Profil-Daten -->
        <div class="dp-profile-card">
            <h3 class="dp-card-title">Persönliche Daten</h3>
            
            <form method="post" class="dp-profile-form">
                <?php wp_nonce_field('dp_update_profile'); ?>
                
                <div class="dp-form-group">
                    <label for="vorname">Vorname *</label>
                    <input type="text" id="vorname" name="vorname" value="<?php echo esc_attr($mitarbeiter->vorname); ?>" class="dp-form-input" required>
                </div>
                
                <div class="dp-form-group">
                    <label for="nachname">Nachname *</label>
                    <input type="text" id="nachname" name="nachname" value="<?php echo esc_attr($mitarbeiter->nachname); ?>" class="dp-form-input" required>
                </div>
                
                <div class="dp-form-group">
                    <label for="email">E-Mail *</label>
                    <input type="email" id="email" name="email" value="<?php echo esc_attr($mitarbeiter->email); ?>" class="dp-form-input" required>
                </div>
                
                <div class="dp-form-group">
                    <label for="telefon">Telefon</label>
                    <input type="tel" id="telefon" name="telefon" value="<?php echo esc_attr($mitarbeiter->telefon ?? ''); ?>" class="dp-form-input">
                </div>
                
                <button type="submit" name="dp_update_profile" class="dp-btn dp-btn-primary">
                    <span class="dashicons dashicons-saved"></span>
                    Änderungen speichern
                </button>
            </form>
        </div>
        
        <!-- Passwort ändern -->
        <div class="dp-profile-card">
            <h3 class="dp-card-title">Passwort ändern</h3>
            
            <form method="post" class="dp-profile-form">
                <?php wp_nonce_field('dp_update_profile'); ?>
                
                <!-- Hidden Fields für Profil-Daten -->
                <input type="hidden" name="vorname" value="<?php echo esc_attr($mitarbeiter->vorname); ?>">
                <input type="hidden" name="nachname" value="<?php echo esc_attr($mitarbeiter->nachname); ?>">
                <input type="hidden" name="email" value="<?php echo esc_attr($mitarbeiter->email); ?>">
                <input type="hidden" name="telefon" value="<?php echo esc_attr($mitarbeiter->telefon ?? ''); ?>">
                
                <div class="dp-form-group">
                    <label for="new_password">Neues Passwort</label>
                    <input type="password" id="new_password" name="new_password" class="dp-form-input" minlength="8">
                    <small class="dp-form-help">Mindestens 8 Zeichen</small>
                </div>
                
                <div class="dp-form-group">
                    <label for="confirm_password">Passwort bestätigen</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="dp-form-input" minlength="8">
                </div>
                
                <button type="submit" name="dp_update_profile" class="dp-btn dp-btn-secondary">
                    <span class="dashicons dashicons-lock"></span>
                    Passwort ändern
                </button>
            </form>
        </div>
        
        <!-- Account-Info -->
        <div class="dp-profile-card">
            <h3 class="dp-card-title">Account-Informationen</h3>
            
            <div class="dp-info-list">
                <div class="dp-info-item">
                    <span class="dp-info-label">Benutzername:</span>
                    <span class="dp-info-value"><?php echo esc_html($current_user->user_login); ?></span>
                </div>
                
                <div class="dp-info-item">
                    <span class="dp-info-label">Mitarbeiter-ID:</span>
                    <span class="dp-info-value">#<?php echo esc_html($mitarbeiter->id); ?></span>
                </div>
                
                <div class="dp-info-item">
                    <span class="dp-info-label">Registriert seit:</span>
                    <span class="dp-info-value"><?php echo date_i18n('d.m.Y', strtotime($current_user->user_registered)); ?></span>
                </div>
            </div>
            
            <div class="dp-account-actions">
                <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="dp-btn dp-btn-outline">
                    <span class="dashicons dashicons-exit"></span>
                    Abmelden
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.dp-profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.dp-profile-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1.5rem;
}

.dp-card-title {
    margin: 0 0 1.5rem 0;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #f3f4f6;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
}

.dp-profile-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.dp-form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.dp-form-group label {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.dp-form-input {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 1rem;
    transition: all 0.2s;
}

.dp-form-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.dp-form-help {
    color: #6b7280;
    font-size: 0.875rem;
}

.dp-info-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.dp-info-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 6px;
}

.dp-info-label {
    font-weight: 600;
    color: #6b7280;
}

.dp-info-value {
    color: #1f2937;
}

.dp-account-actions {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.dp-btn-outline {
    background: transparent;
    color: #dc2626;
    border: 1px solid #dc2626;
}

.dp-btn-outline:hover {
    background: #dc2626;
    color: white;
}

.dp-notice {
    padding: 1rem 1.5rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dp-notice-success {
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
}

.dp-notice-error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

.dp-notice .dashicons {
    font-size: 1.5rem;
}
</style>

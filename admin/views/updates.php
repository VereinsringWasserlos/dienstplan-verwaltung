<?php
/**
 * Update-Verwaltung View
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/admin/views
 */

if (!defined('ABSPATH')) {
    exit;
}

// Updater initialisieren
if (!class_exists('Dienstplan_Updater')) {
    require_once DIENSTPLAN_PLUGIN_PATH . 'includes/class-updater.php';
}

try {
    $updater = new Dienstplan_Updater();
    
    // Git-Status holen
    $git_status = $updater->get_git_status();
} catch (Exception $e) {
    $git_status = array(
        'available' => false,
        'message' => 'Fehler beim Initialisieren: ' . $e->getMessage()
    );
}

// Aktuelle Einstellungen
$git_repo_url = get_option('dienstplan_git_repo_url', '');
$git_branch = get_option('dienstplan_git_branch', 'main');
$auto_update = get_option('dienstplan_auto_update', false);

// Einstellungen speichern
if (isset($_POST['save_git_settings']) && check_admin_referer('dienstplan_git_settings')) {
    update_option('dienstplan_git_repo_url', sanitize_text_field($_POST['git_repo_url']));
    update_option('dienstplan_git_branch', sanitize_text_field($_POST['git_branch']));
    update_option('dienstplan_auto_update', isset($_POST['auto_update']));
    
    echo '<div class="notice notice-success"><p>Einstellungen gespeichert.</p></div>';
    
    $git_repo_url = get_option('dienstplan_git_repo_url');
    $git_branch = get_option('dienstplan_git_branch');
    $auto_update = get_option('dienstplan_auto_update');
}

// Manuelle Update-Prüfung
if (isset($_POST['check_update']) && check_admin_referer('dienstplan_check_update')) {
    if (isset($updater)) {
        $update_check = $updater->check_update_manually();
        
        if ($update_check['has_update']) {
            echo '<div class="notice notice-info"><p><strong>' . esc_html($update_check['message']) . '</strong></p></div>';
        } else {
            echo '<div class="notice notice-success"><p>' . esc_html($update_check['message']) . '</p></div>';
        }
    } else {
        echo '<div class="notice notice-error"><p>Updater konnte nicht initialisiert werden.</p></div>';
    }
}

// Update durchführen
if (isset($_POST['perform_update']) && check_admin_referer('dienstplan_perform_update')) {
    if (isset($updater)) {
        $update_result = $updater->perform_update();
        
        if ($update_result['success']) {
            echo '<div class="notice notice-success"><p><strong>Update erfolgreich!</strong><br>' . 
                 nl2br(esc_html($update_result['output'])) . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p><strong>Update fehlgeschlagen:</strong><br>' . 
                 esc_html($update_result['message']) . '</p></div>';
        }
    } else {
        echo '<div class="notice notice-error"><p>Updater konnte nicht initialisiert werden.</p></div>';
    }
}
?>

<div class="wrap">
    <h1>
        <?php echo esc_html(get_admin_page_title()); ?>
        <span class="version-badge" style="background: #2271b1; color: white; padding: 5px 12px; border-radius: 3px; font-size: 14px; margin-left: 10px;">
            Version <?php echo esc_html(DIENSTPLAN_VERSION); ?>
        </span>
    </h1>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
        
        <!-- Hauptbereich -->
        <div>
            
            <!-- Git-Status -->
            <div class="card" style="max-width: none; margin-bottom: 20px;">
                <h2>Git-Status</h2>
                
                <?php if ($git_status['available']): ?>
                    <table class="widefat" style="margin-top: 15px;">
                        <tbody>
                            <tr>
                                <td style="width: 200px;"><strong>Git verfügbar:</strong></td>
                                <td>
                                    <span style="color: #46b450; font-weight: bold;">✓ Ja</span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Aktueller Branch:</strong></td>
                                <td><code><?php echo esc_html($git_status['current_branch']); ?></code></td>
                            </tr>
                            <tr>
                                <td><strong>Remote-URL:</strong></td>
                                <td>
                                    <?php if ($git_status['remote_url'] === 'not configured'): ?>
                                        <span style="color: #d63638;">Nicht konfiguriert</span>
                                    <?php else: ?>
                                        <code><?php echo esc_html($git_status['remote_url']); ?></code>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Letzter Commit:</strong></td>
                                <td><code><?php echo esc_html($git_status['last_commit']); ?></code></td>
                            </tr>
                            <tr>
                                <td><strong>Lokale Änderungen:</strong></td>
                                <td>
                                    <?php if ($git_status['has_uncommitted_changes']): ?>
                                        <span style="color: #d63638;">⚠ Ja - Vorsicht beim Update!</span>
                                    <?php else: ?>
                                        <span style="color: #46b450;">✓ Keine</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="notice notice-warning inline" style="margin: 15px 0;">
                        <p>
                            <strong>Git ist nicht verfügbar</strong><br>
                            <?php echo esc_html($git_status['message']); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Update-Einstellungen -->
            <div class="card" style="max-width: none; margin-bottom: 20px;">
                <h2>Update-Einstellungen</h2>
                
                <form method="post" style="margin-top: 15px;">
                    <?php wp_nonce_field('dienstplan_git_settings'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="git_repo_url">Git-Repository URL</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="git_repo_url" 
                                       name="git_repo_url" 
                                       value="<?php echo esc_attr($git_repo_url); ?>" 
                                       class="regular-text"
                                       placeholder="https://github.com/user/repo.git">
                                <p class="description">
                                    URL des Git-Repositories für Updates. Leer lassen für lokales Repository.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="git_branch">Branch</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="git_branch" 
                                       name="git_branch" 
                                       value="<?php echo esc_attr($git_branch); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    Branch für Updates (z.B. main, master, develop)
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="auto_update">Automatische Updates</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="auto_update" 
                                           name="auto_update" 
                                           <?php checked($auto_update); ?>>
                                    Automatisch nach Updates suchen
                                </label>
                                <p class="description">
                                    Wenn aktiviert, sucht das Plugin regelmäßig nach Updates.
                                </p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" name="save_git_settings" class="button button-primary">
                            Einstellungen speichern
                        </button>
                    </p>
                </form>
            </div>

            <!-- Update-Aktionen -->
            <div class="card" style="max-width: none;">
                <h2>Update-Aktionen</h2>
                
                <div style="margin-top: 15px;">
                    <form method="post" style="display: inline-block; margin-right: 10px;">
                        <?php wp_nonce_field('dienstplan_check_update'); ?>
                        <button type="submit" name="check_update" class="button button-secondary">
                            🔍 Jetzt nach Updates suchen
                        </button>
                    </form>

                    <?php
                    if (isset($updater)) {
                        $update_check = $updater->check_update_manually();
                        if ($update_check['has_update']):
                    ?>
                        <form method="post" style="display: inline-block;" 
                              onsubmit="return confirm('Möchten Sie das Update jetzt durchführen?\n\nHinweis: Erstellen Sie vorher ein Backup!');">
                            <?php wp_nonce_field('dienstplan_perform_update'); ?>
                            <button type="submit" name="perform_update" class="button button-primary">
                                ⬆️ Update auf Version <?php echo esc_html($update_check['new_version']); ?> durchführen
                            </button>
                        </form>
                    <?php 
                        endif;
                    }
                    ?>
                </div>

                <?php if (isset($git_status['has_uncommitted_changes']) && $git_status['has_uncommitted_changes']): ?>
                    <div class="notice notice-warning inline" style="margin-top: 20px;">
                        <p>
                            <strong>Warnung:</strong> Es gibt lokale Änderungen im Plugin-Verzeichnis. 
                            Diese werden beim Update automatisch mit <code>git stash</code> gesichert.
                        </p>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Sidebar -->
        <div>
            
            <!-- Version-Info -->
            <div class="card" style="max-width: none; margin-bottom: 20px;">
                <h3>Version-Information</h3>
                <table class="widefat" style="margin-top: 10px;">
                    <tbody>
                        <tr>
                            <td><strong>Aktuelle Version:</strong></td>
                            <td><?php echo esc_html(DIENSTPLAN_VERSION); ?></td>
                        </tr>
                        <tr>
                            <td><strong>DB-Version:</strong></td>
                            <td><?php echo esc_html(get_option('dienstplan_db_version', '0.0.0')); ?></td>
                        </tr>
                        <tr>
                            <td><strong>PHP-Version:</strong></td>
                            <td><?php echo esc_html(phpversion()); ?></td>
                        </tr>
                        <tr>
                            <td><strong>WordPress:</strong></td>
                            <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Hilfe -->
            <div class="card" style="max-width: none;">
                <h3>ℹ️ Hilfe & Dokumentation</h3>
                <div style="margin-top: 10px;">
                    <p><strong>So funktioniert das Update-System:</strong></p>
                    <ol style="padding-left: 20px; line-height: 1.8;">
                        <li>Git-Repository konfigurieren</li>
                        <li>Nach Updates suchen</li>
                        <li>Update durchführen (mit Backup!)</li>
                        <li>Datenbank wird automatisch aktualisiert</li>
                    </ol>
                    
                    <hr style="margin: 15px 0;">
                    
                    <p><strong>Wichtige Hinweise:</strong></p>
                    <ul style="padding-left: 20px; line-height: 1.8;">
                        <li>Erstellen Sie vor Updates immer ein Backup</li>
                        <li>Testen Sie Updates zuerst auf einer Test-Umgebung</li>
                        <li>Lokale Änderungen werden mit <code>git stash</code> gesichert</li>
                        <li>Datenbank-Migrationen laufen automatisch</li>
                    </ul>

                    <hr style="margin: 15px 0;">

                    <a href="<?php echo admin_url('admin.php?page=dienstplan-dokumentation&doc=changelog'); ?>" 
                       class="button button-secondary" style="width: 100%; text-align: center; margin-top: 10px;">
                        📋 Changelog anzeigen
                    </a>
                </div>
            </div>

        </div>

    </div>
</div>

<style>
.version-badge {
    display: inline-block;
    vertical-align: middle;
}

.card h2, .card h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #dcdcde;
}

table.widefat td {
    padding: 10px;
}

.notice.inline {
    margin: 0;
}
</style>

<?php
/**
 * Benutzerverwaltung
 */
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-users"></span>
        <?php _e('Benutzerverwaltung', 'dienstplan-verwaltung'); ?>
    </h1>
    
    <p class="description">
        <?php _e('Verwalte Benutzerrollen und Berechtigungen für das Dienstplan-System', 'dienstplan-verwaltung'); ?>
    </p>
    
    <div class="dp-content-grid" style="margin-top: 2rem;">
        
        <!-- Rollenerklärung -->
        <div class="dp-card">
            <div class="dp-card-header">
                <h2><?php _e('Verfügbare Rollen', 'dienstplan-verwaltung'); ?></h2>
            </div>
            <div class="dp-card-body">
                <div class="roles-explanation">
                    <div class="role-item">
                        <h3>
                            <span class="dashicons dashicons-admin-generic" style="color: #d63638;"></span>
                            <?php _e('Allgemeiner Admin', 'dienstplan-verwaltung'); ?>
                        </h3>
                        <p><?php _e('Vollzugriff auf alle Funktionen: Vereine, Veranstaltungen, Benutzerverwaltung und Einstellungen', 'dienstplan-verwaltung'); ?></p>
                    </div>
                    
                    <div class="role-item">
                        <h3>
                            <span class="dashicons dashicons-calendar-alt" style="color: #2271b1;"></span>
                            <?php _e('Veranstaltungs-Admin', 'dienstplan-verwaltung'); ?>
                        </h3>
                        <p><?php _e('Kann Veranstaltungen erstellen, bearbeiten und löschen. Sieht Reports.', 'dienstplan-verwaltung'); ?></p>
                    </div>
                    
                    <div class="role-item">
                        <h3>
                            <span class="dashicons dashicons-groups" style="color: #00a32a;"></span>
                            <?php _e('Vereins-Admin', 'dienstplan-verwaltung'); ?>
                        </h3>
                        <p><?php _e('Kann Vereine erstellen, bearbeiten und löschen. Sieht Reports.', 'dienstplan-verwaltung'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Benutzerliste -->
        <div class="dp-card">
            <div class="dp-card-header">
                <h2><?php _e('Dienstplan-Benutzer', 'dienstplan-verwaltung'); ?></h2>
            </div>
            <div class="dp-card-body">
                <?php if (empty($dp_users)): ?>
                    <p class="description">
                        <?php _e('Noch keine Benutzer mit Dienstplan-Rollen. Weise unten Rollen zu.', 'dienstplan-verwaltung'); ?>
                    </p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th width="30%"><?php _e('Benutzer', 'dienstplan-verwaltung'); ?></th>
                                <th width="30%"><?php _e('E-Mail', 'dienstplan-verwaltung'); ?></th>
                                <th width="30%"><?php _e('Rolle', 'dienstplan-verwaltung'); ?></th>
                                <th width="10%"><?php _e('Aktion', 'dienstplan-verwaltung'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dp_users as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($user->display_name); ?></strong>
                                        <br><small><?php echo esc_html($user->user_login); ?></small>
                                    </td>
                                    <td><?php echo esc_html($user->user_email); ?></td>
                                    <td><?php echo esc_html(Dienstplan_Roles::get_user_role_display($user)); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>" class="button button-small">
                                            <span class="dashicons dashicons-edit"></span>
                                            <?php _e('Bearbeiten', 'dienstplan-verwaltung'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Schnell-Zuweisung -->
        <div class="dp-card">
            <div class="dp-card-header">
                <h2><?php _e('Rolle zuweisen', 'dienstplan-verwaltung'); ?></h2>
            </div>
            <div class="dp-card-body">
                <form method="post" action="" class="dp-form">
                    <?php wp_nonce_field('dp_assign_role', 'dp_role_nonce'); ?>
                    
                    <div class="form-row">
                        <label for="user_id">
                            <?php _e('Benutzer auswählen', 'dienstplan-verwaltung'); ?>
                        </label>
                        <select name="user_id" id="user_id" required class="regular-text">
                            <option value=""><?php _e('Bitte wählen...', 'dienstplan-verwaltung'); ?></option>
                            <?php foreach ($all_users as $user): ?>
                                <option value="<?php echo $user->ID; ?>">
                                    <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <label for="dp_role">
                            <?php _e('Rolle zuweisen', 'dienstplan-verwaltung'); ?>
                        </label>
                        <select name="dp_role" id="dp_role" required class="regular-text">
                            <option value=""><?php _e('Bitte wählen...', 'dienstplan-verwaltung'); ?></option>
                            <option value="<?php echo Dienstplan_Roles::ROLE_GENERAL_ADMIN; ?>">
                                <?php _e('Allgemeiner Admin', 'dienstplan-verwaltung'); ?>
                            </option>
                            <option value="<?php echo Dienstplan_Roles::ROLE_EVENT_ADMIN; ?>">
                                <?php _e('Veranstaltungs-Admin', 'dienstplan-verwaltung'); ?>
                            </option>
                            <option value="<?php echo Dienstplan_Roles::ROLE_CLUB_ADMIN; ?>">
                                <?php _e('Vereins-Admin', 'dienstplan-verwaltung'); ?>
                            </option>
                        </select>
                    </div>
                    
                    <button type="submit" name="assign_role" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Rolle zuweisen', 'dienstplan-verwaltung'); ?>
                    </button>
                </form>
                
                <?php
                // Rolle zuweisen verarbeiten
                if (isset($_POST['assign_role']) && check_admin_referer('dp_assign_role', 'dp_role_nonce')) {
                    $user_id = intval($_POST['user_id']);
                    $role = sanitize_text_field($_POST['dp_role']);
                    
                    if (empty($user_id) || empty($role)) {
                        echo '<div class="notice notice-error" style="margin-top: 1rem;"><p>';
                        _e('Bitte wähle einen Benutzer und eine Rolle aus.', 'dienstplan-verwaltung');
                        echo '</p></div>';
                    } else {
                        $user = get_user_by('id', $user_id);
                        
                        if (!$user) {
                            echo '<div class="notice notice-error" style="margin-top: 1rem;"><p>';
                            _e('Benutzer nicht gefunden.', 'dienstplan-verwaltung');
                            echo '</p></div>';
                        } elseif (in_array($role, (array)$user->roles)) {
                            // Benutzer hat diese Rolle bereits
                            echo '<div class="notice notice-warning" style="margin-top: 1rem;"><p>';
                            printf(
                                __('%s hat bereits die Rolle "%s".', 'dienstplan-verwaltung'), 
                                '<strong>' . esc_html($user->display_name) . '</strong>',
                                '<strong>' . esc_html(Dienstplan_Roles::get_user_role_display($user)) . '</strong>'
                            );
                            echo '</p></div>';
                        } else {
                            // Rolle hinzufügen
                            $user->add_role($role);
                            echo '<div class="notice notice-success" style="margin-top: 1rem;"><p>';
                            printf(__('Rolle erfolgreich zu %s hinzugefügt!', 'dienstplan-verwaltung'), '<strong>' . esc_html($user->display_name) . '</strong>');
                            echo '</p></div>';
                            echo '<script>setTimeout(function(){ 
                                // Prüfe ob ein Modal/Popup geöffnet ist
                                var modals = document.querySelectorAll(".modal[style*=\"display: block\"]");
                                var openModals = Array.from(modals).filter(function(m) { return m.style.display !== "none" && window.getComputedStyle(m).display !== "none"; });
                                if (openModals.length === 0) {
                                    location.reload(); 
                                }
                            }, 3000);</script>';
                        }
                    }
                }
                ?>
            </div>
        </div>
        
    </div>
</div>

<style>
.roles-explanation {
    display: grid;
    gap: 1.5rem;
}

.role-item {
    padding: 1rem;
    background: #f9f9f9;
    border-left: 4px solid #2271b1;
    border-radius: 4px;
}

.role-item h3 {
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
}

.role-item p {
    margin: 0;
    color: #666;
}

.form-row {
    margin-bottom: 1.5rem;
}

.form-row label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.dp-form select {
    width: 100%;
}
</style>

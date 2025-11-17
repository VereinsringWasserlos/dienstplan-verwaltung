<?php
/**
 * Einstellungen
 */
if (!defined('ABSPATH')) exit;

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-settings"></span>
        <?php _e('Einstellungen', 'dienstplan-verwaltung'); ?>
    </h1>
    
    <h2 class="nav-tab-wrapper" style="margin-top: 1rem; margin-bottom: 2rem;">
        <a href="?page=dienstplan-einstellungen&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Allgemein', 'dienstplan-verwaltung'); ?>
        </a>
        <a href="?page=dienstplan-einstellungen&tab=notifications" class="nav-tab <?php echo $active_tab == 'notifications' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Benachrichtigungen', 'dienstplan-verwaltung'); ?>
        </a>
    </h2>
    
    <?php if ($active_tab == 'general'): ?>
        
        <div class="dp-card">
            <div class="dp-card-header">
                <h2><?php _e('Allgemeine Einstellungen', 'dienstplan-verwaltung'); ?></h2>
            </div>
            <div class="dp-card-body">
                <form method="post" action="">
                    <?php wp_nonce_field('dp_settings', 'dp_settings_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="site_name"><?php _e('Organisationsname', 'dienstplan-verwaltung'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="site_name" id="site_name" class="regular-text" 
                                       value="<?php echo esc_attr(get_option('dp_site_name', get_bloginfo('name'))); ?>">
                                <p class="description"><?php _e('Wird in E-Mails und Berichten verwendet', 'dienstplan-verwaltung'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="date_format"><?php _e('Datumsformat', 'dienstplan-verwaltung'); ?></label>
                            </th>
                            <td>
                                <select name="date_format" id="date_format" class="regular-text">
                                    <option value="d.m.Y" <?php selected(get_option('dp_date_format', 'd.m.Y'), 'd.m.Y'); ?>>
                                        <?php echo date('d.m.Y'); ?> (TT.MM.JJJJ)
                                    </option>
                                    <option value="Y-m-d" <?php selected(get_option('dp_date_format', 'd.m.Y'), 'Y-m-d'); ?>>
                                        <?php echo date('Y-m-d'); ?> (JJJJ-MM-TT)
                                    </option>
                                    <option value="m/d/Y" <?php selected(get_option('dp_date_format', 'd.m.Y'), 'm/d/Y'); ?>>
                                        <?php echo date('m/d/Y'); ?> (MM/TT/JJJJ)
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="save_general" class="button button-primary">
                            <?php _e('Speichern', 'dienstplan-verwaltung'); ?>
                        </button>
                    </p>
                </form>
                
                <?php
                if (isset($_POST['save_general']) && check_admin_referer('dp_settings', 'dp_settings_nonce')) {
                    update_option('dp_site_name', sanitize_text_field($_POST['site_name']));
                    update_option('dp_date_format', sanitize_text_field($_POST['date_format']));
                    echo '<div class="notice notice-success"><p>' . __('Einstellungen gespeichert!', 'dienstplan-verwaltung') . '</p></div>';
                }
                ?>
            </div>
        </div>
        
    <?php elseif ($active_tab == 'notifications'): ?>
        
        <div class="dp-card">
            <div class="dp-card-header">
                <h2><?php _e('Meine Benachrichtigungseinstellungen', 'dienstplan-verwaltung'); ?></h2>
            </div>
            <div class="dp-card-body">
                <p class="description">
                    <?php _e('Wähle aus, wann du E-Mail-Benachrichtigungen erhalten möchtest', 'dienstplan-verwaltung'); ?>
                </p>
                
                <form method="post" action="" style="margin-top: 1.5rem;">
                    <?php wp_nonce_field('dp_notification_settings', 'dp_notif_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('E-Mail-Adresse', 'dienstplan-verwaltung'); ?></th>
                            <td>
                                <input type="email" name="email_override" class="regular-text" 
                                       value="<?php echo esc_attr($current_user_settings->email_override); ?>"
                                       placeholder="<?php echo esc_attr(wp_get_current_user()->user_email); ?>">
                                <p class="description">
                                    <?php _e('Leer lassen um deine WordPress E-Mail-Adresse zu verwenden', 'dienstplan-verwaltung'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3><?php _e('Veranstaltungen', 'dienstplan-verwaltung'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Neue Veranstaltung', 'dienstplan-verwaltung'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="notify_on_event_create" value="1" 
                                           <?php checked($current_user_settings->notify_on_event_create, 1); ?>>
                                    <?php _e('Benachrichtigen wenn eine Veranstaltung erstellt wird', 'dienstplan-verwaltung'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Veranstaltung aktualisiert', 'dienstplan-verwaltung'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="notify_on_event_update" value="1" 
                                           <?php checked($current_user_settings->notify_on_event_update, 1); ?>>
                                    <?php _e('Benachrichtigen wenn eine Veranstaltung aktualisiert wird', 'dienstplan-verwaltung'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Veranstaltung gelöscht', 'dienstplan-verwaltung'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="notify_on_event_delete" value="1" 
                                           <?php checked($current_user_settings->notify_on_event_delete, 1); ?>>
                                    <?php _e('Benachrichtigen wenn eine Veranstaltung gelöscht wird', 'dienstplan-verwaltung'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <h3><?php _e('Vereine', 'dienstplan-verwaltung'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Neuer Verein', 'dienstplan-verwaltung'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="notify_on_club_create" value="1" 
                                           <?php checked($current_user_settings->notify_on_club_create, 1); ?>>
                                    <?php _e('Benachrichtigen wenn ein Verein erstellt wird', 'dienstplan-verwaltung'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Verein aktualisiert', 'dienstplan-verwaltung'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="notify_on_club_update" value="1" 
                                           <?php checked($current_user_settings->notify_on_club_update, 1); ?>>
                                    <?php _e('Benachrichtigen wenn ein Verein aktualisiert wird', 'dienstplan-verwaltung'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Verein gelöscht', 'dienstplan-verwaltung'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="notify_on_club_delete" value="1" 
                                           <?php checked($current_user_settings->notify_on_club_delete, 1); ?>>
                                    <?php _e('Benachrichtigen wenn ein Verein gelöscht wird', 'dienstplan-verwaltung'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="save_notifications" class="button button-primary">
                            <?php _e('Einstellungen speichern', 'dienstplan-verwaltung'); ?>
                        </button>
                    </p>
                </form>
                
                <?php
                if (isset($_POST['save_notifications']) && check_admin_referer('dp_notification_settings', 'dp_notif_nonce')) {
                    $settings = array(
                        'notify_on_event_create' => isset($_POST['notify_on_event_create']) ? 1 : 0,
                        'notify_on_event_update' => isset($_POST['notify_on_event_update']) ? 1 : 0,
                        'notify_on_event_delete' => isset($_POST['notify_on_event_delete']) ? 1 : 0,
                        'notify_on_club_create' => isset($_POST['notify_on_club_create']) ? 1 : 0,
                        'notify_on_club_update' => isset($_POST['notify_on_club_update']) ? 1 : 0,
                        'notify_on_club_delete' => isset($_POST['notify_on_club_delete']) ? 1 : 0,
                        'email_override' => sanitize_email($_POST['email_override'])
                    );
                    
                    $notifications->save_user_settings(get_current_user_id(), $settings);
                    echo '<div class="notice notice-success"><p>' . __('Benachrichtigungseinstellungen gespeichert!', 'dienstplan-verwaltung') . '</p></div>';
                    echo '<script>setTimeout(function(){ 
                        // Prüfe ob ein Modal/Popup geöffnet ist
                        var modals = document.querySelectorAll(".modal[style*=\"display: block\"]");
                        var openModals = Array.from(modals).filter(function(m) { return m.style.display !== "none" && window.getComputedStyle(m).display !== "none"; });
                        if (openModals.length === 0) {
                            location.reload(); 
                        }
                    }, 3000);</script>';
                }
                ?>
            </div>
        </div>
        
    <?php endif; ?>
</div>

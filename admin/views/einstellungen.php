<?php
/**
 * Einstellungen
 */
if (!defined('ABSPATH')) exit;

$mail_page_mode = !empty($mail_page_mode);
$active_tab = $mail_page_mode ? 'email' : (isset($_GET['tab']) ? $_GET['tab'] : 'general');
$mail_active_tab = isset($_GET['mail_tab']) ? sanitize_key($_GET['mail_tab']) : 'versand';

if (!in_array($mail_active_tab, array('versand', 'queue', 'templates', 'smtp'), true)) {
    $mail_active_tab = 'versand';
}

$page_title = $mail_page_mode ? __('E-Mail-Bereich', 'dienstplan-verwaltung') : __('Einstellungen', 'dienstplan-verwaltung');
$page_icon = $mail_page_mode ? 'dashicons-email-alt' : 'dashicons-admin-settings';
$page_class = 'header-dashboard';
$nav_items = array(
    array(
        'label' => __('Dashboard', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan'),
        'icon' => 'dashicons-dashboard',
    ),
    array(
        'label' => __('Einstellungen', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-einstellungen'),
        'icon' => 'dashicons-admin-settings',
        'capability' => Dienstplan_Roles::CAP_MANAGE_SETTINGS,
    ),
    array(
        'label' => __('E-Mail', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-mail'),
        'icon' => 'dashicons-email-alt',
        'capability' => Dienstplan_Roles::CAP_MANAGE_SETTINGS,
    ),
);
?>

<div class="wrap dienstplan-wrap" style="overflow: visible; position: relative;">
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/page-header.php'; ?>

    <?php if ($mail_page_mode): ?>
        <p class="description" style="margin-top:0.5rem; margin-bottom: 1.25rem;">
            <?php _e('Hier verwaltest du den gesamten E-Mail-Versand des Plugins zentral.', 'dienstplan-verwaltung'); ?>
        </p>
    <?php endif; ?>

    <?php if (!$mail_page_mode): ?>
    <h2 class="nav-tab-wrapper" style="margin-top: 1rem; margin-bottom: 2rem;">
        <a href="?page=dienstplan-einstellungen&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Allgemein', 'dienstplan-verwaltung'); ?>
        </a>
        <a href="?page=dienstplan-mail&mail_tab=versand" class="nav-tab <?php echo $active_tab == 'email' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-email-alt" style="vertical-align: text-top; margin-right: 4px;"></span>
            <?php _e('E-Mail-Versand', 'dienstplan-verwaltung'); ?>
        </a>
        <a href="?page=dienstplan-einstellungen&tab=notifications" class="nav-tab <?php echo $active_tab == 'notifications' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Benachrichtigungen', 'dienstplan-verwaltung'); ?>
        </a>
    </h2>
    <?php endif; ?>

    <?php if ($mail_page_mode): ?>
    <h2 class="nav-tab-wrapper" style="margin-top: 1rem; margin-bottom: 2rem;">
        <a href="?page=dienstplan-mail&mail_tab=versand" class="nav-tab <?php echo $mail_active_tab === 'versand' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-megaphone" style="vertical-align: text-top; margin-right: 4px;"></span>
            <?php _e('Versand', 'dienstplan-verwaltung'); ?>
        </a>
        <a href="?page=dienstplan-mail&mail_tab=templates" class="nav-tab <?php echo $mail_active_tab === 'templates' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-edit" style="vertical-align: text-top; margin-right: 4px;"></span>
            <?php _e('Vorlagen', 'dienstplan-verwaltung'); ?>
        </a>
        <a href="?page=dienstplan-mail&mail_tab=queue" class="nav-tab <?php echo $mail_active_tab === 'queue' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-clock" style="vertical-align: text-top; margin-right: 4px;"></span>
            <?php _e('Queue & Log', 'dienstplan-verwaltung'); ?>
        </a>
        <a href="?page=dienstplan-mail&mail_tab=smtp" class="nav-tab <?php echo $mail_active_tab === 'smtp' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-network" style="vertical-align: text-top; margin-right: 4px;"></span>
            <?php _e('SMTP & Test', 'dienstplan-verwaltung'); ?>
        </a>
    </h2>
    <?php endif; ?>
    
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
                        <tr>
                            <th scope="row">
                                <label for="dp_datenschutz_url">Datenschutzerklärung (URL)</label>
                            </th>
                            <td>
                                <input type="url" name="dp_datenschutz_url" id="dp_datenschutz_url" class="regular-text"
                                       value="<?php echo esc_attr(get_option('dp_datenschutz_url', '')); ?>"
                                       placeholder="https://example.de/datenschutz">
                                <p class="description">Link zur Datenschutzseite – wird im Frontend-Buchungsformular als klickbarer Link angezeigt.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="dp_impressum_url">Impressum (URL)</label>
                            </th>
                            <td>
                                <input type="url" name="dp_impressum_url" id="dp_impressum_url" class="regular-text"
                                       value="<?php echo esc_attr(get_option('dp_impressum_url', '')); ?>"
                                       placeholder="https://example.de/impressum">
                                <p class="description">Optional – Impressums-Link für das Frontend.</p>
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
                    update_option('dp_datenschutz_url', esc_url_raw(wp_unslash($_POST['dp_datenschutz_url'] ?? '')));
                    update_option('dp_impressum_url',   esc_url_raw(wp_unslash($_POST['dp_impressum_url']   ?? '')));
                    echo '<div class="notice notice-success"><p>' . __('Einstellungen gespeichert!', 'dienstplan-verwaltung') . '</p></div>';
                }
                ?>
            </div>
        </div>
        
    <?php elseif ($active_tab == 'email'): ?>
        <?php
        if (isset($_POST['save_email_dispatch']) && check_admin_referer('dp_email_settings', 'dp_email_nonce')) {
            update_option('dp_mail_from_name',  sanitize_text_field(wp_unslash($_POST['dp_mail_from_name'] ?? '')));
            update_option('dp_mail_from_email', sanitize_email(wp_unslash($_POST['dp_mail_from_email'] ?? '')));
            update_option('dp_mail_reply_to',   sanitize_email(wp_unslash($_POST['dp_mail_reply_to'] ?? '')));
            update_option('dp_mail_enable_booking',           isset($_POST['dp_mail_enable_booking']) ? 1 : 0);
            update_option('dp_mail_enable_portal_invite',     isset($_POST['dp_mail_enable_portal_invite']) ? 1 : 0);
            update_option('dp_mail_enable_dienste_uebersicht',isset($_POST['dp_mail_enable_dienste_uebersicht']) ? 1 : 0);
            $delivery_mode = sanitize_key(wp_unslash($_POST['dp_mail_delivery_mode'] ?? 'queue'));
            if (!in_array($delivery_mode, array('queue', 'immediate'), true)) {
                $delivery_mode = 'queue';
            }
            update_option('dp_mail_delivery_mode', $delivery_mode);
            $queue_interval = intval($_POST['dp_mail_queue_interval_minutes'] ?? 5);
            $queue_batch = intval($_POST['dp_mail_queue_batch_size'] ?? 20);
            if ($queue_interval < 1) {
                $queue_interval = 1;
            }
            if ($queue_interval > 120) {
                $queue_interval = 120;
            }
            if ($queue_batch < 1) {
                $queue_batch = 1;
            }
            if ($queue_batch > 200) {
                $queue_batch = 200;
            }
            update_option('dp_mail_queue_interval_minutes', $queue_interval);
            update_option('dp_mail_queue_batch_size', $queue_batch);
            echo '<div class="notice notice-success is-dismissible"><p><strong>Versand-Einstellungen gespeichert.</strong></p></div>';
        }

        if (isset($_POST['save_email_templates']) && check_admin_referer('dp_email_settings', 'dp_email_nonce')) {
            Dienstplan_Mail_Templates::save_templates_from_post($_POST);
            echo '<div class="notice notice-success is-dismissible"><p><strong>Mail-Vorlagen gespeichert.</strong></p></div>';
        }

        if (isset($_POST['save_email_smtp']) && check_admin_referer('dp_email_settings', 'dp_email_nonce')) {
            update_option('dp_smtp_enabled',    isset($_POST['dp_smtp_enabled']) ? 1 : 0);
            update_option('dp_smtp_host',       sanitize_text_field(wp_unslash($_POST['dp_smtp_host'] ?? '')));
            update_option('dp_smtp_port',       absint($_POST['dp_smtp_port'] ?? 587));
            update_option('dp_smtp_encryption', in_array($_POST['dp_smtp_encryption'] ?? '', ['tls','ssl','none']) ? $_POST['dp_smtp_encryption'] : 'tls');
            update_option('dp_smtp_auth',       isset($_POST['dp_smtp_auth']) ? 1 : 0);
            update_option('dp_smtp_user',       sanitize_text_field(wp_unslash($_POST['dp_smtp_user'] ?? '')));
            // Passwort nur überschreiben wenn nicht leer
            $new_pass = wp_unslash($_POST['dp_smtp_pass'] ?? '');
            if ($new_pass !== '') {
                update_option('dp_smtp_pass', $new_pass);
            }
            echo '<div class="notice notice-success is-dismissible"><p><strong>SMTP-Einstellungen gespeichert.</strong></p></div>';
        }
        ?>

        <?php if ($mail_active_tab === 'versand'): ?>
        <!-- Versand -->
        <div class="dp-card">
            <div class="dp-card-header">
                <h2><span class="dashicons dashicons-email-alt" style="vertical-align:middle; margin-right:6px;"></span><?php _e('Absender-Konfiguration', 'dienstplan-verwaltung'); ?></h2>
            </div>
            <div class="dp-card-body">
                <p class="description" style="margin-bottom:1.5rem;">
                    Diese Einstellungen gelten <strong>plugin-weit</strong> für alle vom Dienstplan-Plugin versendeten E-Mails.
                    Leer lassen = WordPress-Standard (<code>wordpress@<?php echo esc_html(wp_parse_url(home_url(), PHP_URL_HOST)); ?></code>).
                </p>
                <form method="post" action="">
                    <?php wp_nonce_field('dp_email_settings', 'dp_email_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="dp_mail_from_name">Absender-Name</label></th>
                            <td>
                                <input type="text" id="dp_mail_from_name" name="dp_mail_from_name" class="regular-text"
                                       value="<?php echo esc_attr(get_option('dp_mail_from_name', '')); ?>"
                                       placeholder="<?php echo esc_attr(get_option('dp_site_name', get_bloginfo('name'))); ?>">
                                <p class="description">Erscheint im E-Mail-Client als Absender-Name, z.&nbsp;B. <em>Vereinsring Wasserlos</em>.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dp_mail_from_email">Absender-E-Mail (From)</label></th>
                            <td>
                                <input type="email" id="dp_mail_from_email" name="dp_mail_from_email" class="regular-text"
                                       value="<?php echo esc_attr(get_option('dp_mail_from_email', '')); ?>"
                                       placeholder="dienstplan@<?php echo esc_html(wp_parse_url(home_url(), PHP_URL_HOST)); ?>">
                                <p class="description">Verwende eine Adresse der eigenen Domain, damit Mails nicht im Spam landen (<abbr title="Sender Policy Framework">SPF</abbr>/<abbr title="DomainKeys Identified Mail">DKIM</abbr>).</p>
                                <?php
                                $from_email_saved = get_option('dp_mail_from_email', '');
                                $site_host        = wp_parse_url(home_url(), PHP_URL_HOST);
                                // Warnung wenn From-Domain eine fremde Domain ist (z.B. @web.de, @gmail.com)
                                if (!empty($from_email_saved)) {
                                    $from_domain = strtolower(substr($from_email_saved, strpos($from_email_saved, '@') + 1));
                                    // Prüfe ob From-Domain ein Suffix der Site-Domain ist
                                    $site_root = preg_replace('/^[^.]+\./', '', $site_host); // z.B. vereinsring-wasserlos.de
                                    if ($from_domain !== $site_host && $from_domain !== $site_root && !str_ends_with($site_host, '.' . $from_domain)) {
                                        echo '<p class="description" style="color:#d63638; margin-top:6px;">⚠️ <strong>Achtung:</strong> Die Absender-Domain <code>' . esc_html($from_domain) . '</code> stimmt nicht mit der Server-Domain überein. Der Server darf keine Mails als <em>' . esc_html($from_domain) . '</em> versenden (SPF-Fehler) – Mails werden vom Empfänger abgelehnt oder landen im Spam. Verwende stattdessen eine Adresse wie <code>dienstplan@' . esc_html($site_root ?: $site_host) . '</code>.</p>';
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dp_mail_reply_to">Antwort-Adresse (Reply-To)</label></th>
                            <td>
                                <input type="email" id="dp_mail_reply_to" name="dp_mail_reply_to" class="regular-text"
                                       value="<?php echo esc_attr(get_option('dp_mail_reply_to', '')); ?>"
                                       placeholder="kontakt@<?php echo esc_html(wp_parse_url(home_url(), PHP_URL_HOST)); ?>">
                                <p class="description">Optional. Antwortet ein Empfänger auf eine Plugin-Mail, geht die Antwort an diese Adresse.</p>
                            </td>
                        </tr>
                    </table>

                    <hr style="margin: 1.5rem 0;">
                    <h3 style="margin-top:0;">Welche E-Mail-Typen sollen versendet werden?</h3>
                    <p class="description" style="margin-bottom:1rem;">
                        Hier kannst du einzelne automatische Mails komplett deaktivieren (z.&nbsp;B. wenn du eigene Workflows oder Newsletter-System nutzt).
                    </p>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Buchungsbestätigung</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="dp_mail_enable_booking" value="1"
                                           <?php checked(get_option('dp_mail_enable_booking', 1), 1); ?>>
                                    Bestätigungs-Mail an Mitarbeiter nach Übernahme eines Dienstes senden
                                </label>
                                <p class="description">Betrifft: <em>Dienst übernehmen</em>-Formular im Frontend (nur wenn E-Mail-Adresse angegeben).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Portal-Einladung / Zugangsdaten</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="dp_mail_enable_portal_invite" value="1"
                                           <?php checked(get_option('dp_mail_enable_portal_invite', 1), 1); ?>>
                                    Login-Daten und Einladungen beim Erstellen / Aktivieren von Portal-Zugängen senden
                                </label>
                                <p class="description">Betrifft: Portal-Zugriff aktivieren, Passwort zurücksetzen, Einladungs-Mail im Admin sowie automatische Konto-Erstellung bei „Ja"-Auswahl im Frontend-Formular.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Dienste-Übersicht</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="dp_mail_enable_dienste_uebersicht" value="1"
                                           <?php checked(get_option('dp_mail_enable_dienste_uebersicht', 1), 1); ?>>
                                    Manuelle Dienste-Übersicht an einzelne Mitarbeiter senden erlauben
                                </label>
                                <p class="description">Betrifft: Schaltfläche „Dienste-Übersicht senden" in der Mitarbeiterliste.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dp_mail_delivery_mode">Versandmodus</label></th>
                            <td>
                                <select id="dp_mail_delivery_mode" name="dp_mail_delivery_mode">
                                    <option value="immediate" <?php selected(get_option('dp_mail_delivery_mode', 'queue'), 'immediate'); ?>>Mail sofort (direkt)</option>
                                    <option value="queue" <?php selected(get_option('dp_mail_delivery_mode', 'queue'), 'queue'); ?>>Mail über Queue (Cron)</option>
                                </select>
                                <p class="description">Sofort: E-Mails werden direkt versendet. Queue: E-Mails werden gesammelt und per Cron verarbeitet.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dp_mail_queue_interval_minutes">Queue-Intervall (Minuten)</label></th>
                            <td>
                                <input type="number" id="dp_mail_queue_interval_minutes" name="dp_mail_queue_interval_minutes" class="small-text"
                                       value="<?php echo esc_attr(get_option('dp_mail_queue_interval_minutes', 5)); ?>" min="1" max="120">
                                <p class="description">Gibt an, in welchem Abstand die Mail-Queue automatisch verarbeitet wird (WP-Cron).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dp_mail_queue_batch_size">Queue-Batchgröße</label></th>
                            <td>
                                <input type="number" id="dp_mail_queue_batch_size" name="dp_mail_queue_batch_size" class="small-text"
                                       value="<?php echo esc_attr(get_option('dp_mail_queue_batch_size', 20)); ?>" min="1" max="200">
                                <p class="description">Anzahl Mails pro Queue-Durchlauf. Bei vielen Mails klein anfangen (z. B. 20).</p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" name="save_email_dispatch" class="button button-primary">Versand speichern</button>
                    </p>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($mail_active_tab === 'queue'): ?>
        <?php
        $queue_stats = Dienstplan_Mail_Queue::get_stats();
        $queue_log = Dienstplan_Mail_Queue::get_log_items();
        $delivery_mode = $queue_stats['mode'] ?? 'queue';
        $next_run_display = !empty($queue_stats['next_run'])
            ? date_i18n('d.m.Y H:i:s', intval($queue_stats['next_run']))
            : 'nicht geplant';
        ?>
        <div class="dp-card" style="margin-top: 1.5rem;">
            <div class="dp-card-header">
                <h2><span class="dashicons dashicons-clock" style="vertical-align:middle; margin-right:6px;"></span><?php _e('Mail-Queue Status', 'dienstplan-verwaltung'); ?></h2>
            </div>
            <div class="dp-card-body">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Versandmodus', 'dienstplan-verwaltung'); ?></th>
                        <td><?php echo $delivery_mode === 'immediate' ? 'Mail sofort (direkt)' : 'Queue (Cron)'; ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Einträge in Queue', 'dienstplan-verwaltung'); ?></th>
                        <td><strong><?php echo intval($queue_stats['queue_count']); ?></strong></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Nächster geplanter Lauf', 'dienstplan-verwaltung'); ?></th>
                        <td><?php echo esc_html($next_run_display); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Letzter Lauf', 'dienstplan-verwaltung'); ?></th>
                        <td><?php echo !empty($queue_stats['last_run']) ? esc_html($queue_stats['last_run']) : 'noch nicht ausgeführt'; ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Log-Einträge', 'dienstplan-verwaltung'); ?></th>
                        <td><?php echo intval($queue_stats['log_count']); ?></td>
                    </tr>
                </table>

                <p style="margin-top: 1rem; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <button type="button" id="dp-process-mail-queue-now" class="button button-secondary">
                        <?php _e('Queue jetzt verarbeiten', 'dienstplan-verwaltung'); ?>
                    </button>
                    <button type="button" id="dp-clear-mail-queue-log" class="button button-secondary" style="color:#b32d2e; border-color:#b32d2e;">
                        <?php _e('Queue-Log leeren', 'dienstplan-verwaltung'); ?>
                    </button>
                    <span id="dp-mail-queue-action-result" style="font-weight: 600;"></span>
                </p>
            </div>
        </div>

        <div class="dp-card" style="margin-top: 1.5rem;">
            <div class="dp-card-header">
                <h2><span class="dashicons dashicons-list-view" style="vertical-align:middle; margin-right:6px;"></span><?php _e('Queue-Log', 'dienstplan-verwaltung'); ?></h2>
            </div>
            <div class="dp-card-body">
                <?php if (empty($queue_log)): ?>
                    <p class="description" style="font-style: italic;"><?php _e('Noch keine Queue-Einträge vorhanden.', 'dienstplan-verwaltung'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th style="width:170px;"><?php _e('Zeitpunkt', 'dienstplan-verwaltung'); ?></th>
                                <th style="width:220px;"><?php _e('Empfänger', 'dienstplan-verwaltung'); ?></th>
                                <th style="width:160px;"><?php _e('Typ', 'dienstplan-verwaltung'); ?></th>
                                <th style="width:170px;"><?php _e('Quelle', 'dienstplan-verwaltung'); ?></th>
                                <th style="width:150px;"><?php _e('Grund', 'dienstplan-verwaltung'); ?></th>
                                <th><?php _e('Betreff', 'dienstplan-verwaltung'); ?></th>
                                <th style="width:120px;"><?php _e('Status', 'dienstplan-verwaltung'); ?></th>
                                <th style="width:80px;"><?php _e('Versuche', 'dienstplan-verwaltung'); ?></th>
                                <th><?php _e('Fehler', 'dienstplan-verwaltung'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($queue_log as $entry): ?>
                            <tr>
                                <td><?php echo esc_html($entry['time'] ?? ''); ?></td>
                                <td style="word-break: break-all;"><?php echo esc_html($entry['to'] ?? ''); ?></td>
                                <td><?php echo esc_html($entry['type'] ?? '-'); ?></td>
                                <td><?php echo esc_html($entry['source'] ?? '-'); ?></td>
                                <td><?php echo esc_html($entry['reason'] ?? '-'); ?></td>
                                <td><?php echo esc_html($entry['subject'] ?? ''); ?></td>
                                <td><?php echo esc_html($entry['status'] ?? ''); ?></td>
                                <td><?php echo intval($entry['attempts'] ?? 0); ?></td>
                                <td style="color:#b32d2e;"><?php echo esc_html($entry['error'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <script>
        (function($) {
            var nonce = '<?php echo esc_js(wp_create_nonce('dp_ajax_nonce')); ?>';
            var result = $('#dp-mail-queue-action-result');

            $('#dp-process-mail-queue-now').on('click', function() {
                var btn = $(this);
                btn.prop('disabled', true).text('Verarbeite...');
                result.css('color', '#666').text('');

                $.post(ajaxurl, {
                    action: 'dp_process_mail_queue_now',
                    nonce: nonce
                }, function(res) {
                    if (res && res.success) {
                        result.css('color', '#00a32a').text('✓ ' + res.data.message);
                        window.setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        var msg = (res && res.data && res.data.message) ? res.data.message : 'Unbekannter Fehler.';
                        result.css('color', '#d63638').text('✗ ' + msg);
                    }
                }).fail(function() {
                    result.css('color', '#d63638').text('Serverfehler - bitte Seite neu laden.');
                }).always(function() {
                    btn.prop('disabled', false).text('Queue jetzt verarbeiten');
                });
            });

            $('#dp-clear-mail-queue-log').on('click', function() {
                if (!window.confirm('Queue-Log wirklich leeren?')) {
                    return;
                }

                var btn = $(this);
                btn.prop('disabled', true).text('Leere Log...');
                result.css('color', '#666').text('');

                $.post(ajaxurl, {
                    action: 'dp_clear_mail_queue_log',
                    nonce: nonce
                }, function(res) {
                    if (res && res.success) {
                        result.css('color', '#00a32a').text('✓ ' + res.data.message);
                        window.setTimeout(function() { location.reload(); }, 500);
                    } else {
                        var msg = (res && res.data && res.data.message) ? res.data.message : 'Unbekannter Fehler.';
                        result.css('color', '#d63638').text('✗ ' + msg);
                    }
                }).fail(function() {
                    result.css('color', '#d63638').text('Serverfehler - bitte Seite neu laden.');
                }).always(function() {
                    btn.prop('disabled', false).text('Queue-Log leeren');
                });
            });
        })(jQuery);
        </script>
        <?php endif; ?>

        <?php if ($mail_active_tab === 'templates'): ?>
        <div class="dp-card">
            <div class="dp-card-header">
                <h2><span class="dashicons dashicons-edit" style="vertical-align:middle; margin-right:6px;"></span><?php _e('E-Mail-Vorlagen', 'dienstplan-verwaltung'); ?></h2>
            </div>
            <div class="dp-card-body">
                <p class="description" style="margin-bottom:1rem;">
                    Passe Betreff und Text pro Mail-Typ an. Verfuegbare Platzhalter koennen direkt im Text verwendet werden.
                </p>
                <form method="post" action="">
                    <?php wp_nonce_field('dp_email_settings', 'dp_email_nonce'); ?>
                    <?php $template_definitions = Dienstplan_Mail_Templates::get_definitions(); ?>
                    <?php foreach ($template_definitions as $template_key => $template): ?>
                        <?php
                        $subject_option = 'dp_mail_tpl_' . $template_key . '_subject';
                        $body_option = 'dp_mail_tpl_' . $template_key . '_body';
                        $cc_option = 'dp_mail_tpl_' . $template_key . '_cc';
                        $bcc_option = 'dp_mail_tpl_' . $template_key . '_bcc';
                        $subject_value = get_option($subject_option, $template['subject_default']);
                        $body_value = get_option($body_option, $template['body_default']);
                        $cc_value = get_option($cc_option, $template['cc_default']);
                        $bcc_value = get_option($bcc_option, $template['bcc_default']);
                        ?>
                        <div class="dp-mail-template-card" style="border:1px solid #dcdcde; border-radius:8px; padding:16px; margin-bottom:14px; background:#fff;">
                            <h4 style="margin:0 0 6px 0;"><?php echo esc_html($template['label']); ?></h4>
                            <p class="description" style="margin-top:0; margin-bottom:10px;"><?php echo esc_html($template['description']); ?></p>
                            <p style="margin: 0 0 8px 0;">
                                <label for="<?php echo esc_attr($subject_option); ?>"><strong>Betreff</strong></label><br>
                                <input
                                    type="text"
                                    id="<?php echo esc_attr($subject_option); ?>"
                                    name="<?php echo esc_attr($subject_option); ?>"
                                    class="regular-text"
                                    style="width:100%; max-width: 820px;"
                                    value="<?php echo esc_attr($subject_value); ?>"
                                >
                            </p>
                            <p style="margin: 0 0 8px 0;">
                                <label for="<?php echo esc_attr($body_option); ?>"><strong>Text</strong></label><br>
                                <textarea
                                    id="<?php echo esc_attr($body_option); ?>"
                                    name="<?php echo esc_attr($body_option); ?>"
                                    rows="9"
                                    style="width:100%; max-width: 820px;"
                                ><?php echo esc_textarea($body_value); ?></textarea>
                            </p>
                            <p style="margin: 0 0 8px 0;">
                                <label for="<?php echo esc_attr($cc_option); ?>"><strong>CC</strong></label><br>
                                <input
                                    type="text"
                                    id="<?php echo esc_attr($cc_option); ?>"
                                    name="<?php echo esc_attr($cc_option); ?>"
                                    class="regular-text"
                                    style="width:100%; max-width: 820px;"
                                    value="<?php echo esc_attr($cc_value); ?>"
                                    placeholder="mail1@example.de, {{veranstaltungs_admin_email}}, {{vereins_admin_email}}"
                                >
                            </p>
                            <p style="margin: 0 0 8px 0;">
                                <label for="<?php echo esc_attr($bcc_option); ?>"><strong>BCC</strong></label><br>
                                <input
                                    type="text"
                                    id="<?php echo esc_attr($bcc_option); ?>"
                                    name="<?php echo esc_attr($bcc_option); ?>"
                                    class="regular-text"
                                    style="width:100%; max-width: 820px;"
                                    value="<?php echo esc_attr($bcc_value); ?>"
                                    placeholder="audit@example.de; {{veranstaltungs_admin_email}}"
                                >
                            </p>
                            <p class="description" style="margin:0;">
                                Platzhalter: <?php echo esc_html(implode(', ', array_map(function($placeholder) { return '{{' . $placeholder . '}}'; }, $template['placeholders']))); ?>
                            </p>
                            <p class="description" style="margin-top:6px;">
                                CC/BCC akzeptiert mehrere Empfaenger (Komma, Semikolon oder Zeilenumbruch). Fuer Verantwortliche: {{veranstaltungs_admin_email}}, {{vereins_admin_email}}.
                            </p>
                            <div style="margin-top:12px; padding-top:12px; border-top:1px dashed #dcdcde; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                <input
                                    type="email"
                                    class="regular-text dp-template-test-to"
                                    style="max-width: 340px;"
                                    value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>"
                                    placeholder="test@example.de"
                                >
                                <button
                                    type="button"
                                    class="button button-secondary dp-send-template-test-mail"
                                    data-template-key="<?php echo esc_attr($template_key); ?>"
                                >
                                    <?php _e('Testmail senden', 'dienstplan-verwaltung'); ?>
                                </button>
                                <span class="dp-template-test-result" style="font-weight:600;"></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <p class="submit">
                        <button type="submit" name="save_email_templates" class="button button-primary">Vorlagen speichern</button>
                    </p>
                </form>
            </div>
        </div>
        <script>
        (function($) {
            $('.dp-send-template-test-mail').on('click', function() {
                var btn = $(this);
                var row = btn.closest('.dp-mail-template-card');
                var to = row.find('.dp-template-test-to').val();
                var result = row.find('.dp-template-test-result');
                var templateKey = btn.data('template-key');

                if (!to) {
                    result.css('color', '#d63638').text('Bitte eine Empfaenger-Adresse eingeben.');
                    return;
                }

                btn.prop('disabled', true).text('Wird gesendet...');
                result.css('color', '#666').text('');

                $.post(ajaxurl, {
                    action: 'dp_send_template_test_mail',
                    nonce: '<?php echo esc_js(wp_create_nonce('dp_send_template_test_mail')); ?>',
                    to: to,
                    template_type: templateKey
                }, function(res) {
                    if (res && res.success) {
                        result.css('color', '#00a32a').text('✓ ' + res.data.message);
                    } else {
                        var msg = (res && res.data && res.data.message) ? res.data.message : 'Unbekannter Fehler.';
                        result.css('color', '#d63638').text('✗ ' + msg);
                    }
                }).fail(function() {
                    result.css('color', '#d63638').text('Serverfehler - bitte Seite neu laden.');
                }).always(function() {
                    btn.prop('disabled', false).text('Testmail senden');
                });
            });
        })(jQuery);
        </script>
        <?php endif; ?>

        <?php if ($mail_active_tab === 'smtp'): ?>
        <!-- SMTP-Konfiguration -->
        <div class="dp-card" style="margin-top:1.5rem;">
            <div class="dp-card-header">
                <h2><span class="dashicons dashicons-admin-network" style="vertical-align:middle; margin-right:6px;"></span>SMTP-Konfiguration</h2>
            </div>
            <div class="dp-card-body">
                <p class="description" style="margin-bottom:1.5rem;">
                    Wenn PHP&apos;s <code>mail()</code>-Funktion auf deinem Server nicht verfügbar ist, kannst du hier einen externen SMTP-Server konfigurieren.
                    Diese Einstellung gilt plugin-weit für alle vom Dienstplan-Plugin versendeten Mails.<br>
                    <strong>Tipp (netcup):</strong> SMTP-Host <code>mail.vereinsring-wasserlos.de</code>, Port <code>587</code>, Verschlüsselung <code>STARTTLS</code>, Benutzername = E-Mail-Adresse des Postfachs.
                </p>
                <form method="post" action="">
                    <?php wp_nonce_field('dp_email_settings', 'dp_email_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">SMTP aktivieren</th>
                            <td>
                                <label>
                                    <input type="checkbox" id="dp_smtp_enabled" name="dp_smtp_enabled" value="1"
                                           <?php checked(get_option('dp_smtp_enabled', 0), 1); ?>>
                                    SMTP für den E-Mail-Versand verwenden
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dp_smtp_host">SMTP-Host</label></th>
                            <td>
                                <input type="text" id="dp_smtp_host" name="dp_smtp_host" class="regular-text"
                                       value="<?php echo esc_attr(get_option('dp_smtp_host', '')); ?>"
                                       placeholder="mail.example.de">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dp_smtp_port">Port</label></th>
                            <td>
                                <input type="number" id="dp_smtp_port" name="dp_smtp_port" class="small-text"
                                       value="<?php echo esc_attr(get_option('dp_smtp_port', 587)); ?>"
                                       min="1" max="65535">
                                <p class="description">587 = STARTTLS &nbsp;|&nbsp; 465 = SSL/TLS &nbsp;|&nbsp; 25 = unverschlüsselt</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dp_smtp_encryption">Verschlüsselung</label></th>
                            <td>
                                <select id="dp_smtp_encryption" name="dp_smtp_encryption">
                                    <option value="tls"  <?php selected(get_option('dp_smtp_encryption', 'tls'), 'tls'); ?>>STARTTLS (empfohlen)</option>
                                    <option value="ssl"  <?php selected(get_option('dp_smtp_encryption', 'tls'), 'ssl'); ?>>SSL/TLS</option>
                                    <option value="none" <?php selected(get_option('dp_smtp_encryption', 'tls'), 'none'); ?>>Keine</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">SMTP-Authentifizierung</th>
                            <td>
                                <label>
                                    <input type="checkbox" id="dp_smtp_auth" name="dp_smtp_auth" value="1"
                                           <?php checked(get_option('dp_smtp_auth', 1), 1); ?>>
                                    Benutzername und Passwort verwenden
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dp_smtp_user">Benutzername</label></th>
                            <td>
                                <input type="text" id="dp_smtp_user" name="dp_smtp_user" class="regular-text"
                                       value="<?php echo esc_attr(get_option('dp_smtp_user', '')); ?>"
                                       placeholder="dienstplan@example.de"
                                       autocomplete="new-password">
                                <p class="description">Meist die vollständige E-Mail-Adresse des Postfachs.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dp_smtp_pass">Passwort</label></th>
                            <td>
                                <input type="password" id="dp_smtp_pass" name="dp_smtp_pass" class="regular-text"
                                       value=""
                                       placeholder="<?php echo get_option('dp_smtp_pass', '') !== '' ? '(gespeichert – leer lassen um nicht zu ändern)' : ''; ?>"
                                       autocomplete="new-password">
                                <?php if (get_option('dp_smtp_pass', '') !== ''): ?>
                                    <p class="description" style="color:#00a32a;">&#10003; Passwort ist gespeichert. Leer lassen um es beizubehalten.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" name="save_email_smtp" class="button button-primary">SMTP-Einstellungen speichern</button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Test-Mail -->
        <div class="dp-card" style="margin-top:1.5rem;">
            <div class="dp-card-header">
                <h2><span class="dashicons dashicons-email" style="vertical-align:middle; margin-right:6px;"></span>Test-Mail senden</h2>
            </div>
            <div class="dp-card-body">
                <p class="description">Sendet eine Test-E-Mail mit den aktuell eingetragenen Absender-Einstellungen. Speichere zuerst, bevor du testest.</p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="dp_test_mail_to">Empfänger</label></th>
                        <td>
                            <input type="email" id="dp_test_mail_to" class="regular-text"
                                   value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>">
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="button" id="dp-send-test-mail" class="button button-secondary">
                        <span class="dashicons dashicons-email" style="vertical-align:middle;"></span>
                        Test-Mail jetzt senden
                    </button>
                    <span id="dp-test-mail-result" style="margin-left:1rem; font-weight:600;"></span>
                </p>
            </div>
        </div>

        <script>
        (function($) {
            $('#dp-send-test-mail').on('click', function() {
                var btn = $(this);
                var to  = $('#dp_test_mail_to').val();
                var result = $('#dp-test-mail-result');
                if (!to) { result.css('color','#d63638').text('Bitte eine Empfänger-Adresse eingeben.'); return; }
                btn.prop('disabled', true).text('Wird gesendet…');
                result.css('color','#666').text('');
                $.post(ajaxurl, {
                    action: 'dp_send_test_mail',
                    nonce:  '<?php echo esc_js(wp_create_nonce('dp_send_test_mail')); ?>',
                    to:     to
                }, function(res) {
                    if (res && res.success) {
                        result.css('color','#00a32a').text('✓ ' + res.data.message);
                    } else {
                        var msg = (res && res.data && res.data.message) ? res.data.message : 'Unbekannter Fehler.';
                        result.css('color','#d63638').text('✗ ' + msg);
                    }
                }).fail(function() {
                    result.css('color','#d63638').text('Serverfehler – bitte Seite neu laden.');
                }).always(function() {
                    btn.prop('disabled', false).html('<span class="dashicons dashicons-email" style="vertical-align:middle;"></span> Test-Mail jetzt senden');
                });
            });
        })(jQuery);
        </script>
        <?php endif; ?>

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
                    echo '<script>if(typeof dpSafeReload === "function") { dpSafeReload(2000); }</script>';
                }
                ?>
            </div>
        </div>
        
    <?php endif; ?>
</div>

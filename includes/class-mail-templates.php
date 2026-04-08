<?php
/**
 * Mail-Templates fuer den Dienstplan
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dienstplan_Mail_Templates {
    /**
     * Template-Definitionen mit Labels und Standardtexten.
     *
     * @return array
     */
    public static function get_definitions() {
        $site_name = get_option('dp_site_name', get_bloginfo('name'));

        return array(
            'booking_confirmation' => array(
                'label' => __('Buchungsbestätigung', 'dienstplan-verwaltung'),
                'description' => __('Bestätigung nach Dienst-Anmeldung im Frontend.', 'dienstplan-verwaltung'),
                'subject_default' => __('Bestätigung Ihrer Anmeldung - {{veranstaltung}}', 'dienstplan-verwaltung'),
                'body_default' => "Hallo {{vorname}} {{nachname}},\n\nvielen Dank fuer Ihre Anmeldung!\n\nDetails zu Ihrem Dienst:\nVeranstaltung: {{veranstaltung}}\nVerein: {{verein}}\nDatum: {{datum}}\nUhrzeit: {{von_zeit}} - {{bis_zeit}} Uhr\nTaetigkeit: {{taetigkeit}}\nBereich: {{bereich}}\n\n{{beschreibung_block}}{{source_url_block}}Bei Fragen oder Aenderungen wenden Sie sich bitte an den Veranstalter.\n\nMit freundlichen Gruessen\nIhr Dienstplan-Team",
                'placeholders' => array('vorname', 'nachname', 'veranstaltung', 'verein', 'datum', 'von_zeit', 'bis_zeit', 'taetigkeit', 'bereich', 'beschreibung_block', 'source_url_block')
            ),
            'portal_invite' => array(
                'label' => __('Portal-Einladung / Zugangsdaten', 'dienstplan-verwaltung'),
                'description' => __('Wird beim Erstellen/Aktivieren eines Portal-Zugangs genutzt.', 'dienstplan-verwaltung'),
                'subject_default' => __('[{{site_name}}] Zugang zum Dienstplan-Portal', 'dienstplan-verwaltung'),
                'body_default' => "Hallo {{vorname}},\n\nfuer dich wurde ein Zugang zum Dienstplan-Portal erstellt.\n\nHier sind deine Login-Daten:\n\nBenutzername: {{username}}\nPasswort: {{password}}\n\nPortal-Link: {{portal_link}}\n\nBitte aendere dein Passwort nach dem ersten Login.\n\nViele Gruesse\n{{site_name}}",
                'placeholders' => array('vorname', 'username', 'password', 'portal_link', 'site_name')
            ),
            'portal_reset_credentials' => array(
                'label' => __('Neue Login-Daten', 'dienstplan-verwaltung'),
                'description' => __('Wird bei "Login-Daten erneut senden" genutzt.', 'dienstplan-verwaltung'),
                'subject_default' => __('[{{site_name}}] Neue Login-Daten fuer das Dienstplan-Portal', 'dienstplan-verwaltung'),
                'body_default' => "Hallo {{vorname}},\n\nwie gewuenscht erhaeltst du hier neue Login-Daten fuer das Dienstplan-Portal:\n\nBenutzername: {{username}}\nNeues Passwort: {{password}}\n\nPortal-Link: {{portal_link}}\n\nBitte aendere dein Passwort nach dem Login.\n\nViele Gruesse\n{{site_name}}",
                'placeholders' => array('vorname', 'username', 'password', 'portal_link', 'site_name')
            ),
            'dienste_uebersicht' => array(
                'label' => __('Dienste-Uebersicht', 'dienstplan-verwaltung'),
                'description' => __('Manuelle Uebersicht aller zugewiesenen Dienste.', 'dienstplan-verwaltung'),
                'subject_default' => __('Ihre Dienste-Uebersicht - {{site_name}}', 'dienstplan-verwaltung'),
                'body_default' => "Hallo {{vorname}},\n\nhier ist deine aktuelle Uebersicht aller zugewiesenen Dienste:\n\n{{diensteliste}}\n\nGesamt: {{total_dienste}} Dienst(e)\n\nBei Fragen wende dich bitte an den Veranstalter.\n\nViele Gruesse\n{{site_name}}",
                'placeholders' => array('vorname', 'diensteliste', 'total_dienste', 'site_name')
            ),
        );
    }

    /**
     * Gibt ein Template inkl. ersetzter Platzhalter zurueck.
     *
     * @param string $type
     * @param array  $placeholders
     * @return array{subject:string, body:string}
     */
    public static function get_template($type, $placeholders = array()) {
        $definitions = self::get_definitions();

        if (!isset($definitions[$type])) {
            return array('subject' => '', 'body' => '');
        }

        $definition = $definitions[$type];
        $subject_option_key = 'dp_mail_tpl_' . $type . '_subject';
        $body_option_key = 'dp_mail_tpl_' . $type . '_body';

        $subject = get_option($subject_option_key, $definition['subject_default']);
        $body = get_option($body_option_key, $definition['body_default']);

        if (!is_string($subject) || $subject === '') {
            $subject = $definition['subject_default'];
        }

        if (!is_string($body) || $body === '') {
            $body = $definition['body_default'];
        }

        $placeholders = array_merge(
            array('site_name' => get_option('dp_site_name', get_bloginfo('name'))),
            $placeholders
        );

        $replace_map = array();
        foreach ($placeholders as $key => $value) {
            $replace_map['{{' . $key . '}}'] = (string) $value;
        }

        return array(
            'subject' => strtr($subject, $replace_map),
            'body' => strtr($body, $replace_map),
        );
    }

    /**
     * Speichert alle bekannten Template-Felder aus einem POST-Array.
     *
     * @param array $post_data
     * @return void
     */
    public static function save_templates_from_post($post_data) {
        $definitions = self::get_definitions();

        foreach (array_keys($definitions) as $type) {
            $subject_key = 'dp_mail_tpl_' . $type . '_subject';
            $body_key = 'dp_mail_tpl_' . $type . '_body';

            if (isset($post_data[$subject_key])) {
                update_option($subject_key, sanitize_text_field(wp_unslash($post_data[$subject_key])));
            }

            if (isset($post_data[$body_key])) {
                update_option($body_key, sanitize_textarea_field(wp_unslash($post_data[$body_key])));
            }
        }
    }
}

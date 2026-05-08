<?php
/**
 * Veranstaltung Konfiguration Modal
 */
if (!defined('ABSPATH')) exit;
?>

<div id="veranstaltung-konfiguration-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content" style="max-width: 760px;">
        <div class="dp-modal-header">
            <h2><?php _e('Veranstaltung konfigurieren', 'dienstplan-verwaltung'); ?></h2>
            <button class="dp-modal-close" onclick="closeVeranstaltungKonfigurationModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="veranstaltung-konfiguration-form" onsubmit="return false;">
                <input type="hidden" id="vk_veranstaltung_id" value="">

                <table class="form-table">
                    <tr>
                        <th><label for="vk_name"><?php _e('Veranstaltung', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <input type="text" id="vk_name" class="regular-text" readonly>
                            <p class="description"><?php _e('Basisdaten wie Name, Beschreibung und Tage bearbeiten Sie weiterhin über "Bearbeiten".', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="vk_status"><?php _e('Status', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <select id="vk_status" class="regular-text" style="max-width: 300px;">
                                <option value="in_planung"><?php _e('In Planung', 'dienstplan-verwaltung'); ?></option>
                                <option value="geplant"><?php _e('Geplant', 'dienstplan-verwaltung'); ?></option>
                                <option value="aktiv"><?php _e('Aktiv', 'dienstplan-verwaltung'); ?></option>
                                <option value="abgeschlossen"><?php _e('Abgeschlossen', 'dienstplan-verwaltung'); ?></option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="vk_mitarbeiter_anzeige_modus"><?php _e('Mitarbeiter-Anzeige im Frontend', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <select id="vk_mitarbeiter_anzeige_modus" class="regular-text" style="max-width: 500px;">
                                <option value="verkuerzt"><?php _e('Verkürzt (nur Vorname + Nachname-Initial, z.B. "Max M.")', 'dienstplan-verwaltung'); ?></option>
                                <option value="vollstaendig"><?php _e('Vollständig (alle sehen Namen, z.B. "Max Mustermann")', 'dienstplan-verwaltung'); ?></option>
                                <option value="admin_only"><?php _e('Admin-only (Admin sieht Namen, andere sehen "Besetzt")', 'dienstplan-verwaltung'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeVeranstaltungKonfigurationModal()"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
            <button type="button" class="button button-primary" onclick="saveVeranstaltungKonfiguration()"><?php _e('Konfiguration speichern', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>

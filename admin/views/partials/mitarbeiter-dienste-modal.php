<?php
/**
 * Mitarbeiter Dienste Modal - Zeigt alle Dienste eines Mitarbeiters
 */
if (!defined('ABSPATH')) exit;
?>

<!-- Mitarbeiter Dienste Modal -->
<div id="mitarbeiter-dienste-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content" style="max-width: 1000px;">
        <div class="dp-modal-header">
            <h2 id="mitarbeiter-dienste-title"><?php _e('Dienste von Mitarbeiter', 'dienstplan-verwaltung'); ?></h2>
            <button type="button" class="dp-modal-close" onclick="closeMitarbeiterDiensteModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <input type="hidden" id="view_mitarbeiter_id">
            
            <!-- Mitarbeiter Info -->
            <div id="mitarbeiter-info" style="padding: 1rem; background: #f0f6fc; border-left: 4px solid #2271b1; margin-bottom: 1.5rem;">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; font-size: 0.9rem;">
                    <div>
                        <strong><?php _e('Name:', 'dienstplan-verwaltung'); ?></strong>
                        <span id="info-ma-name"></span>
                    </div>
                    <div>
                        <strong><?php _e('E-Mail:', 'dienstplan-verwaltung'); ?></strong>
                        <span id="info-ma-email"></span>
                    </div>
                    <div>
                        <strong><?php _e('Telefon:', 'dienstplan-verwaltung'); ?></strong>
                        <span id="info-ma-telefon"></span>
                    </div>
                </div>
            </div>
            
            <!-- Dienste Container -->
            <div id="mitarbeiter-dienste-container">
                <p style="text-align: center; color: #666;">
                    <span class="spinner is-active" style="float: none; margin: 0;"></span>
                    <?php _e('Lade Dienste...', 'dienstplan-verwaltung'); ?>
                </p>
            </div>
        </div>
        <div class="dp-modal-footer">
            <button type="button" class="button" onclick="closeMitarbeiterDiensteModal()"><?php _e('Schließen', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>

<!-- JavaScript moved to assets/js/dp-mitarbeiter-dienste-modal.js -->

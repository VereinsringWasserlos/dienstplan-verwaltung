<?php
/**
 * Partial: Verein-Auswahl für Frontend
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/public/templates/partials
 */

if (!defined('ABSPATH')) exit;
?>

<div class="dp-verein-selection">
    <div class="dp-verein-selection-header">
        <h1 class="dp-selection-title">Willkommen zur Veranstaltung</h1>
        <p class="dp-selection-subtitle">Wählen Sie Ihren Verein aus, um verfügbare Dienste zu sehen</p>
    </div>
    
    <div class="dp-verein-grid">
        <?php foreach ($vereine as $verein): 
            // Zähle Dienste für diesen Verein
            $verein_dienste = $db->get_dienste($veranstaltung_id, $verein->verein_id);
            $total_slots = 0;
            $open_slots = 0;
            foreach ($verein_dienste as $dienst) {
                $slots = $db->get_dienst_slots($dienst->id);
                if (empty($slots)) {
                    $open_slots++;
                } else {
                    $total_slots += count($slots);
                    foreach ($slots as $slot) {
                        if (empty($slot->mitarbeiter_id)) {
                            $open_slots++;
                        }
                    }
                }
            }
            
            // Farbe basierend auf verfügbaren Plätzen
            $status_class = $open_slots > 0 ? 'has-openings' : 'full';
            $status_icon = $open_slots > 0 ? '🟢' : '🔴';
        ?>
            <a href="<?php echo esc_url(add_query_arg(array('veranstaltung_id' => $veranstaltung_id, 'verein_id' => $verein->verein_id))); ?>" 
               class="dp-verein-card <?php echo esc_attr($status_class); ?>">
                
                <div class="dp-verein-card-overlay"></div>
                
                <div class="dp-verein-card-content">
                    <div class="dp-verein-card-header">
                        <div class="dp-verein-card-icon">
                            <span><?php echo esc_html($verein->verein_kuerzel ?? substr($verein->verein_name, 0, 2)); ?></span>
                        </div>
                        <div class="dp-verein-card-title">
                            <h3><?php echo esc_html($verein->verein_name); ?></h3>
                            <span class="dp-verein-status-badge"><?php echo $status_icon; ?> <?php echo $open_slots > 0 ? 'Offen' : 'Voll'; ?></span>
                        </div>
                    </div>
                    
                    <div class="dp-verein-card-stats">
                        <div class="dp-verein-stat">
                            <div class="dp-stat-icon">📋</div>
                            <div class="dp-stat-content">
                                <span class="dp-verein-stat-value"><?php echo count($verein_dienste); ?></span>
                                <span class="dp-verein-stat-label">Dienste</span>
                            </div>
                        </div>
                        <div class="dp-verein-stat">
                            <div class="dp-stat-icon">✨</div>
                            <div class="dp-stat-content">
                                <span class="dp-verein-stat-value available"><?php echo $open_slots; ?></span>
                                <span class="dp-verein-stat-label">Frei</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dp-verein-card-action">
                        <span class="dp-action-text">Jetzt auswählen</span>
                        <span class="dp-action-arrow">→</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<style>
/* Verein Selection - Modern Dashboard Style */

.dp-verein-selection {
    max-width: 1400px;
    margin: 0 auto;
    padding: 3rem 1rem;
}

.dp-verein-selection-header {
    text-align: center;
    margin-bottom: 4rem;
    animation: slideDown 0.5s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dp-selection-title {
    font-size: 3rem;
    font-weight: 800;
    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0 0 1rem 0;
    letter-spacing: -0.02em;
}

.dp-selection-subtitle {
    font-size: 1.125rem;
    color: #6b7280;
    margin: 0;
    font-weight: 500;
}

/* Grid Layout */
.dp-verein-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
    animation: fadeIn 0.6s ease-out 0.1s both;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Verein Card */
.dp-verein-card {
    position: relative;
    display: flex;
    flex-direction: column;
    background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
    border: 2px solid #e5e7eb;
    border-radius: 1.25rem;
    padding: 2rem;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07);
    min-height: 280px;
}

.dp-verein-card-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.05) 0%, rgba(59, 130, 246, 0.02) 100%);
    opacity: 0;
    transition: opacity 0.35s ease;
    pointer-events: none;
}

.dp-verein-card:hover {
    border-color: #2563eb;
    box-shadow: 0 20px 40px -10px rgba(37, 99, 235, 0.25);
    transform: translateY(-8px);
}

.dp-verein-card:hover .dp-verein-card-overlay {
    opacity: 1;
}

.dp-verein-card.full {
    opacity: 0.85;
}

.dp-verein-card.full:hover {
    transform: translateY(-4px);
    opacity: 1;
}

.dp-verein-card-content {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    height: 100%;
}

/* Card Header */
.dp-verein-card-header {
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #f0f1f3;
}

.dp-verein-card-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
    border-radius: 0.75rem;
    color: white;
    font-weight: 700;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.dp-verein-card-title {
    flex: 1;
}

.dp-verein-card-title h3 {
    margin: 0;
    font-size: 1.375rem;
    font-weight: 700;
    color: #111827;
    line-height: 1.3;
}

.dp-verein-status-badge {
    display: inline-block;
    margin-top: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #059669;
}

.dp-verein-card.full .dp-verein-status-badge {
    color: #dc2626;
}

/* Card Stats */
.dp-verein-card-stats {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 2rem;
    flex: 1;
}

.dp-verein-stat {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dp-stat-icon {
    font-size: 1.5rem;
    line-height: 1;
}

.dp-stat-content {
    display: flex;
    flex-direction: column;
}

.dp-verein-stat-value {
    font-size: 1.75rem;
    font-weight: 800;
    color: #111827;
    line-height: 1;
}

.dp-verein-stat-value.available {
    color: #10b981;
}

.dp-verein-stat-label {
    font-size: 0.8125rem;
    color: #9ca3af;
    margin-top: 0.25rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Card Action */
.dp-verein-card-action {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
    border-radius: 0.75rem;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    margin-top: auto;
}

.dp-verein-card:hover .dp-verein-card-action {
    box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.3);
    transform: translateX(2px);
}

.dp-action-arrow {
    display: inline-block;
    transition: transform 0.3s ease;
    margin-left: 0.5rem;
    font-size: 1.25rem;
}

.dp-verein-card:hover .dp-action-arrow {
    transform: translateX(4px);
}

/* Responsive */
@media (max-width: 768px) {
    .dp-verein-selection {
        padding: 2rem 1rem;
    }
    
    .dp-selection-title {
        font-size: 2rem;
    }
    
    .dp-verein-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .dp-verein-card {
        min-height: auto;
        padding: 1.5rem;
    }
    
    .dp-verein-card-header {
        gap: 1rem;
    }
}

@media (max-width: 480px) {
    .dp-selection-title {
        font-size: 1.5rem;
    }
    
    .dp-selection-subtitle {
        font-size: 1rem;
    }
    
    .dp-verein-card-title h3 {
        font-size: 1.125rem;
    }
    
    .dp-verein-card-stats {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>

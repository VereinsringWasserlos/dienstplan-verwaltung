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
    <div class="dp-verein-list">
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
            $status_class = $open_slots > 0 ? 'has-openings' : 'full';
        ?>
            <a href="<?php echo esc_url(add_query_arg(array('veranstaltung_id' => $veranstaltung_id, 'verein_id' => $verein->verein_id))); ?>" 
               class="dp-verein-row <?php echo esc_attr($status_class); ?>">

                <div class="dp-verein-row-main">
                    <?php if (!empty($verein->verein_kuerzel)): ?>
                        <span class="dp-verein-badge"><?php echo esc_html($verein->verein_kuerzel); ?></span>
                    <?php endif; ?>
                    <span class="dp-verein-row-name"><?php echo esc_html($verein->verein_name); ?></span>
                </div>

                <div class="dp-verein-row-meta">
                    <span class="dp-verein-row-stat"><?php echo count($verein_dienste); ?> Dienste</span>
                    <span class="dp-verein-row-stat <?php echo $open_slots > 0 ? 'dp-stat-open' : 'dp-stat-full'; ?>">
                        <?php echo $open_slots > 0 ? $open_slots . ' frei' : 'Voll'; ?>
                    </span>
                    <span class="dp-verein-row-arrow">→</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<style>
/* Verein Selection – kompakte Liste */

.dp-verein-selection {
    margin: 0;
    padding: 0;
}

.dp-verein-list {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.dp-verein-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1.25rem;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0;
    text-decoration: none;
    color: inherit;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.dp-verein-row:first-child {
    border-radius: 0.625rem 0.625rem 0 0;
}

.dp-verein-row:last-child {
    border-radius: 0 0 0.625rem 0.625rem;
}

.dp-verein-row:not(:last-child) {
    border-bottom-color: transparent;
}

.dp-verein-row:hover {
    background: #f0f6ff;
    border-color: #93c5fd;
    z-index: 1;
    position: relative;
}

.dp-verein-row.full {
    opacity: 0.7;
}

/* Linke Seite: Kürzel-Badge + Name */
.dp-verein-row-main {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    min-width: 0;
}

.dp-verein-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.2rem 0.55rem;
    background: #2563eb;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
    border-radius: 0.375rem;
    letter-spacing: 0.03em;
    white-space: nowrap;
    flex-shrink: 0;
}

.dp-verein-row-name {
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Rechte Seite: Metadaten */
.dp-verein-row-meta {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    flex-shrink: 0;
}

.dp-verein-row-stat {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
    white-space: nowrap;
}

.dp-verein-row-stat.dp-stat-open {
    color: #059669;
    font-weight: 700;
}

.dp-verein-row-stat.dp-stat-full {
    color: #dc2626;
    font-weight: 600;
}

.dp-verein-row-arrow {
    font-size: 1rem;
    color: #9ca3af;
    transition: transform 0.2s ease, color 0.15s ease;
}

.dp-verein-row:hover .dp-verein-row-arrow {
    transform: translateX(3px);
    color: #2563eb;
}

/* Responsive */
@media (max-width: 480px) {
    .dp-verein-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
        padding: 0.875rem 1rem;
    }

    .dp-verein-row-meta {
        gap: 1rem;
    }
}
</style>

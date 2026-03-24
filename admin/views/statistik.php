<?php
/**
 * Statistik-Template (pro Veranstaltung)
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/admin/views
 */

if (!defined('ABSPATH')) exit;

$page_title = __('Statistik', 'dienstplan-verwaltung');
$page_icon = 'dashicons-chart-bar';
$page_class = 'header-statistik';
$nav_items = array(
    array(
        'label' => __('Dashboard', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan'),
        'active' => false,
    ),
    array(
        'label' => __('Veranstaltungen', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-veranstaltungen'),
        'active' => false,
    ),
    array(
        'label' => __('Statistik', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-statistik'),
        'active' => true,
    ),
);

$selected_veranstaltung = null;
foreach ($veranstaltungen as $veranstaltung_item) {
    if (intval($veranstaltung_item->id) === intval($selected_veranstaltung_id)) {
        $selected_veranstaltung = $veranstaltung_item;
        break;
    }
}
?>

<div class="wrap dienstplan-admin-container">
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/page-header.php'; ?>

    <div class="dp-card" style="margin-bottom: 1rem;">
        <form method="get" style="display: flex; gap: 0.75rem; align-items: end; flex-wrap: wrap;">
            <input type="hidden" name="page" value="dienstplan-statistik">
            <div>
                <label for="veranstaltung_id" style="display: block; margin-bottom: 0.3rem; font-weight: 600;">
                    <?php _e('Veranstaltung wählen', 'dienstplan-verwaltung'); ?>
                </label>
                <select id="veranstaltung_id" name="veranstaltung_id" style="min-width: 320px;">
                    <option value=""><?php _e('-- Bitte wählen --', 'dienstplan-verwaltung'); ?></option>
                    <?php foreach ($veranstaltungen as $veranstaltung_option): ?>
                        <option value="<?php echo intval($veranstaltung_option->id); ?>" <?php selected($selected_veranstaltung_id, $veranstaltung_option->id); ?>>
                            <?php echo esc_html($veranstaltung_option->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="button button-primary">
                <?php _e('Statistik anzeigen', 'dienstplan-verwaltung'); ?>
            </button>
        </form>
    </div>

    <?php if (empty($veranstaltungen)): ?>
        <div class="dp-empty-state">
            <h3><?php _e('Keine Veranstaltungen verfügbar', 'dienstplan-verwaltung'); ?></h3>
            <p><?php _e('Lege zuerst eine Veranstaltung an, um Statistiken zu sehen.', 'dienstplan-verwaltung'); ?></p>
        </div>
    <?php elseif (!$selected_veranstaltung): ?>
        <div class="dp-empty-state">
            <h3><?php _e('Keine Veranstaltung ausgewählt', 'dienstplan-verwaltung'); ?></h3>
            <p><?php _e('Wähle oben eine Veranstaltung aus, um die Auswertungen zu sehen.', 'dienstplan-verwaltung'); ?></p>
        </div>
    <?php else: ?>
        <?php
        $tage = $db->get_veranstaltung_tage($selected_veranstaltung->id);
        $event_vereine = $db->get_veranstaltung_vereine($selected_veranstaltung->id);
        $event_dienste = $db->get_dienste($selected_veranstaltung->id);

        $tag_label_map = array();
        $tag_order = array();
        foreach ($tage as $tag_item) {
            $tag_id = intval($tag_item->id);
            if ($tag_id <= 0) {
                continue;
            }
            $tag_label_map[$tag_id] = !empty($tag_item->tag_datum)
                ? date_i18n('d.m.Y', strtotime($tag_item->tag_datum))
                : sprintf(__('Tag %d', 'dienstplan-verwaltung'), intval($tag_item->tag_nummer));
            $tag_order[] = $tag_id;
        }

        $verein_name_map = array();
        $verein_order = array();
        foreach ($event_vereine as $event_verein) {
            $verein_id = intval($event_verein->verein_id);
            if ($verein_id <= 0) {
                continue;
            }

            if (!empty($event_verein->verein_name)) {
                $verein_label = $event_verein->verein_name;
            } elseif (!empty($event_verein->name)) {
                $verein_label = $event_verein->name;
            } else {
                $verein_label = 'Verein #' . $verein_id;
            }

            $verein_name_map[$verein_id] = $verein_label;
            $verein_order[] = $verein_id;
        }

        $unknown_tag_id = -1;
        $unknown_verein_id = -1;

        // Pivot 1: Dienste pro Verein pro Tag
        $dienste_pivot = array();
        // Statistik 2: Aufgaben (eindeutige Tätigkeiten) pro Verein (gesamt)
        $aufgaben_sets = array();
        // Pivot Aufgaben x Vereine (Anzahl Dienste je Aufgabe/Verein)
        $aufgaben_verein_pivot = array();
        $aufgabe_label_map = array();
        $aufgabe_bereich_map = array();
        $aufgabe_order = array();

        foreach ($event_dienste as $dienst_item) {
            $tag_id = intval($dienst_item->tag_id);
            $verein_id = intval($dienst_item->verein_id);

            if ($tag_id <= 0) {
                $tag_id = $unknown_tag_id;
            }

            if ($verein_id <= 0) {
                $verein_id = $unknown_verein_id;
            }

            if (!isset($tag_label_map[$tag_id])) {
                $tag_label_map[$tag_id] = ($tag_id === $unknown_tag_id)
                    ? __('Ohne Tag', 'dienstplan-verwaltung')
                    : __('Unbekannter Tag', 'dienstplan-verwaltung');
                $tag_order[] = $tag_id;
            }

            if (!isset($verein_name_map[$verein_id])) {
                if ($verein_id === $unknown_verein_id) {
                    $verein_name_map[$verein_id] = __('Ohne Verein', 'dienstplan-verwaltung');
                } else {
                    $verein_name_map[$verein_id] = !empty($dienst_item->verein_name)
                        ? $dienst_item->verein_name
                        : ('Verein #' . $verein_id);
                }
                $verein_order[] = $verein_id;
            }

            if (!isset($dienste_pivot[$verein_id])) {
                $dienste_pivot[$verein_id] = array();
            }
            if (!isset($dienste_pivot[$verein_id][$tag_id])) {
                $dienste_pivot[$verein_id][$tag_id] = 0;
            }
            $dienste_pivot[$verein_id][$tag_id]++;

            if (!isset($aufgaben_sets[$verein_id])) {
                $aufgaben_sets[$verein_id] = array();
            }

            $bereich_id = isset($dienst_item->bereich_id) ? intval($dienst_item->bereich_id) : 0;
            $bereich_label = !empty($dienst_item->bereich_name)
                ? $dienst_item->bereich_name
                : __('Ohne Bereich', 'dienstplan-verwaltung');

            $task_key = '';
            $taetigkeit_id = isset($dienst_item->taetigkeit_id) ? intval($dienst_item->taetigkeit_id) : 0;
            if ($taetigkeit_id > 0) {
                $task_key = 'b:' . $bereich_id . '|id:' . $taetigkeit_id;
            } elseif (!empty($dienst_item->taetigkeit_name)) {
                $task_key = 'b:' . $bereich_id . '|name:' . strtolower(trim((string) $dienst_item->taetigkeit_name));
            } else {
                $task_key = 'b:' . $bereich_id . '|dienst:' . intval($dienst_item->id);
            }

            $task_label = !empty($dienst_item->taetigkeit_name)
                ? $dienst_item->taetigkeit_name
                : __('Ohne Tätigkeit', 'dienstplan-verwaltung');

            if (!isset($aufgabe_label_map[$task_key])) {
                $aufgabe_label_map[$task_key] = $task_label;
                $aufgabe_bereich_map[$task_key] = $bereich_label;
                $aufgabe_order[] = $task_key;
            }

            $aufgaben_sets[$verein_id][$task_key] = true;

            if (!isset($aufgaben_verein_pivot[$task_key])) {
                $aufgaben_verein_pivot[$task_key] = array();
            }
            if (!isset($aufgaben_verein_pivot[$task_key][$verein_id])) {
                $aufgaben_verein_pivot[$task_key][$verein_id] = 0;
            }
            $aufgaben_verein_pivot[$task_key][$verein_id]++;
        }

        $tag_order = array_values(array_unique($tag_order));
        $verein_order = array_values(array_unique($verein_order));

        usort($verein_order, function($a, $b) use ($verein_name_map) {
            $name_a = isset($verein_name_map[$a]) ? $verein_name_map[$a] : ('Verein #' . $a);
            $name_b = isset($verein_name_map[$b]) ? $verein_name_map[$b] : ('Verein #' . $b);
            return strcasecmp($name_a, $name_b);
        });

        usort($aufgabe_order, function($a, $b) use ($aufgabe_label_map) {
            $label_a = isset($aufgabe_label_map[$a]) ? $aufgabe_label_map[$a] : $a;
            $label_b = isset($aufgabe_label_map[$b]) ? $aufgabe_label_map[$b] : $b;
            return strcasecmp($label_a, $label_b);
        });

        $aufgaben_pro_verein = array();
        foreach ($aufgaben_sets as $verein_id => $task_set) {
            $aufgaben_pro_verein[$verein_id] = count($task_set);
        }

        $aufgaben_compare_values = array();
        foreach ($verein_order as $verein_id) {
            $aufgaben_compare_values[] = intval(isset($aufgaben_pro_verein[$verein_id]) ? $aufgaben_pro_verein[$verein_id] : 0);
        }

        $alle_vereine_gleiche_aufgabenanzahl = true;
        $aufgaben_min = 0;
        $aufgaben_max = 0;
        if (!empty($aufgaben_compare_values)) {
            $aufgaben_min = min($aufgaben_compare_values);
            $aufgaben_max = max($aufgaben_compare_values);
            $alle_vereine_gleiche_aufgabenanzahl = ($aufgaben_min === $aufgaben_max);
        }

        $render_pivot_table = function($table_title, $matrix, $vereins_ids, $verein_labels, $tage_ids, $tage_labels, $empty_text) {
            echo '<div class="dp-card" style="margin-bottom: 1rem;">';
            echo '<h3 style="margin-top: 0;">' . esc_html($table_title) . '</h3>';

            if (empty($vereins_ids) || empty($tage_ids)) {
                echo '<p style="margin: 0; color: #64748b;">' . esc_html($empty_text) . '</p>';
                echo '</div>';
                return;
            }

            $column_totals = array();
            foreach ($tage_ids as $tag_id) {
                $column_totals[$tag_id] = 0;
            }
            $grand_total = 0;

            echo '<div style="overflow-x: auto;">';
            echo '<table class="widefat striped" style="min-width: 760px;">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__('Verein', 'dienstplan-verwaltung') . '</th>';
            foreach ($tage_ids as $tag_id) {
                $tag_label = isset($tage_labels[$tag_id]) ? $tage_labels[$tag_id] : ('Tag #' . intval($tag_id));
                echo '<th style="text-align: right;">' . esc_html($tag_label) . '</th>';
            }
            echo '<th style="text-align: right;">' . esc_html__('Gesamt', 'dienstplan-verwaltung') . '</th>';
            echo '</tr></thead>';

            echo '<tbody>';
            foreach ($vereins_ids as $verein_id) {
                $verein_total = 0;
                $verein_label = isset($verein_labels[$verein_id]) ? $verein_labels[$verein_id] : ('Verein #' . intval($verein_id));

                echo '<tr>';
                echo '<td>' . esc_html($verein_label) . '</td>';

                foreach ($tage_ids as $tag_id) {
                    $value = intval(isset($matrix[$verein_id][$tag_id]) ? $matrix[$verein_id][$tag_id] : 0);
                    $verein_total += $value;
                    $column_totals[$tag_id] += $value;
                    echo '<td style="text-align: right;">' . intval($value) . '</td>';
                }

                $grand_total += $verein_total;
                echo '<td style="text-align: right; font-weight: 700;">' . intval($verein_total) . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';

            echo '<tfoot><tr>';
            echo '<th>' . esc_html__('Gesamt', 'dienstplan-verwaltung') . '</th>';
            foreach ($tage_ids as $tag_id) {
                echo '<th style="text-align: right;">' . intval($column_totals[$tag_id]) . '</th>';
            }
            echo '<th style="text-align: right;">' . intval($grand_total) . '</th>';
            echo '</tr></tfoot>';

            echo '</table>';
            echo '</div>';
            echo '</div>';
        };
        ?>

        <div class="dp-card" style="margin-bottom: 1rem;">
            <h2 style="margin-top: 0;"><?php echo esc_html($selected_veranstaltung->name); ?></h2>
            <p style="margin: 0; color: #64748b;">
                <?php printf(__('Tage: %1$s | Vereine: %2$s | Dienste: %3$s', 'dienstplan-verwaltung'), count($tage), count($event_vereine), count($event_dienste)); ?>
            </p>
        </div>

        <?php
        $render_pivot_table(
            __('Anzahl der Dienste pro Verein pro Tag', 'dienstplan-verwaltung'),
            $dienste_pivot,
            $verein_order,
            $verein_name_map,
            $tag_order,
            $tag_label_map,
            __('Keine Daten für diese Auswertung verfügbar.', 'dienstplan-verwaltung')
        );

        ?>

        <div class="dp-card" style="margin-bottom: 1rem;">
            <h3 style="margin-top: 0;"><?php _e('Anzahl der Aufgaben pro Verein', 'dienstplan-verwaltung'); ?></h3>

            <p style="margin: 0 0 0.75rem; font-weight: 600; color: <?php echo $alle_vereine_gleiche_aufgabenanzahl ? '#166534' : '#b91c1c'; ?>;">
                <?php if ($alle_vereine_gleiche_aufgabenanzahl): ?>
                    <?php _e('Ja: Alle Vereine haben die gleiche Anzahl verschiedener Aufgaben.', 'dienstplan-verwaltung'); ?>
                <?php else: ?>
                    <?php
                    printf(
                        __('Nein: Die Anzahl verschiedener Aufgaben unterscheidet sich (Min: %1$d, Max: %2$d, Delta: %3$d).', 'dienstplan-verwaltung'),
                        intval($aufgaben_min),
                        intval($aufgaben_max),
                        intval($aufgaben_max - $aufgaben_min)
                    );
                    ?>
                <?php endif; ?>
            </p>

            <?php if (empty($verein_order)): ?>
                <p style="margin: 0; color: #64748b;"><?php _e('Keine Daten für diese Auswertung verfügbar.', 'dienstplan-verwaltung'); ?></p>
            <?php else: ?>
                <?php if (empty($aufgabe_order)): ?>
                    <p style="margin: 0; color: #64748b;"><?php _e('Keine Aufgaben in den Diensten gefunden.', 'dienstplan-verwaltung'); ?></p>
                <?php else: ?>
                    <?php
                    $pivot_column_totals = array();
                    foreach ($verein_order as $verein_id) {
                        $pivot_column_totals[$verein_id] = 0;
                    }
                    $pivot_grand_total = 0;
                    ?>
                    <div style="overflow-x: auto;">
                        <table class="widefat striped" style="min-width: 900px;">
                            <thead>
                                <tr>
                                    <th><?php _e('Bereich', 'dienstplan-verwaltung'); ?></th>
                                    <th><?php _e('Aufgabe', 'dienstplan-verwaltung'); ?></th>
                                    <?php foreach ($verein_order as $verein_id): ?>
                                        <th style="text-align: right;"><?php echo esc_html(isset($verein_name_map[$verein_id]) ? $verein_name_map[$verein_id] : ('Verein #' . intval($verein_id))); ?></th>
                                    <?php endforeach; ?>
                                    <th style="text-align: right;"><?php _e('Aufgabe gesamt', 'dienstplan-verwaltung'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aufgabe_order as $task_key): ?>
                                    <?php
                                    $row_total = 0;
                                    $task_label = isset($aufgabe_label_map[$task_key]) ? $aufgabe_label_map[$task_key] : $task_key;
                                    $bereich_label = isset($aufgabe_bereich_map[$task_key]) ? $aufgabe_bereich_map[$task_key] : __('Ohne Bereich', 'dienstplan-verwaltung');
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($bereich_label); ?></td>
                                        <td><?php echo esc_html($task_label); ?></td>
                                        <?php foreach ($verein_order as $verein_id): ?>
                                            <?php
                                            $cell_value = intval(isset($aufgaben_verein_pivot[$task_key][$verein_id]) ? $aufgaben_verein_pivot[$task_key][$verein_id] : 0);
                                            $row_total += $cell_value;
                                            $pivot_column_totals[$verein_id] += $cell_value;
                                            ?>
                                            <td style="text-align: right;"><?php echo intval($cell_value); ?></td>
                                        <?php endforeach; ?>
                                        <?php $pivot_grand_total += $row_total; ?>
                                        <td style="text-align: right; font-weight: 700;"><?php echo intval($row_total); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2"><?php _e('Gesamt', 'dienstplan-verwaltung'); ?></th>
                                    <?php foreach ($verein_order as $verein_id): ?>
                                        <th style="text-align: right;"><?php echo intval($pivot_column_totals[$verein_id]); ?></th>
                                    <?php endforeach; ?>
                                    <th style="text-align: right;"><?php echo intval($pivot_grand_total); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php
        ?>
    <?php endif; ?>
</div>

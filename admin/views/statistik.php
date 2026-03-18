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
        foreach ($tage as $tag_item) {
            $tag_label_map[intval($tag_item->id)] = !empty($tag_item->tag_datum)
                ? date_i18n('d.m.Y', strtotime($tag_item->tag_datum))
                : sprintf(__('Tag %d', 'dienstplan-verwaltung'), intval($tag_item->tag_nummer));
        }

        $event_verein_ids = array();
        foreach ($event_vereine as $event_verein) {
            $event_verein_ids[] = intval($event_verein->verein_id);
        }
        $event_verein_ids = array_values(array_unique(array_filter($event_verein_ids)));

        $distribution_groups = array();
        foreach ($event_dienste as $dienst_item) {
            $group_tag_id = intval($dienst_item->tag_id);
            $group_bereich_id = intval($dienst_item->bereich_id);
            $group_verein_id = intval($dienst_item->verein_id);
            if ($group_tag_id <= 0 || $group_bereich_id <= 0 || $group_verein_id <= 0) {
                continue;
            }

            $distribution_key = $group_tag_id . '|' . $group_bereich_id;
            if (!isset($distribution_groups[$distribution_key])) {
                $distribution_groups[$distribution_key] = array(
                    'tag_id' => $group_tag_id,
                    'bereich_id' => $group_bereich_id,
                    'bereich_name' => !empty($dienst_item->bereich_name) ? $dienst_item->bereich_name : ('Bereich #' . $group_bereich_id),
                    'counts' => array(),
                );
            }

            if (!isset($distribution_groups[$distribution_key]['counts'][$group_verein_id])) {
                $distribution_groups[$distribution_key]['counts'][$group_verein_id] = 0;
            }
            $distribution_groups[$distribution_key]['counts'][$group_verein_id]++;
        }

        $distribution_total = 0;
        $distribution_balanced = 0;
        $distribution_issues = array();

        foreach ($distribution_groups as $distribution_group) {
            $distribution_total++;
            $compare_verein_ids = !empty($event_verein_ids)
                ? $event_verein_ids
                : array_keys($distribution_group['counts']);

            $distribution_values = array();
            foreach ($compare_verein_ids as $compare_verein_id) {
                $distribution_values[] = intval(isset($distribution_group['counts'][$compare_verein_id]) ? $distribution_group['counts'][$compare_verein_id] : 0);
            }

            if (empty($distribution_values)) {
                $distribution_balanced++;
                continue;
            }

            $distribution_min = min($distribution_values);
            $distribution_max = max($distribution_values);

            if ($distribution_min === $distribution_max) {
                $distribution_balanced++;
            } else {
                $distribution_issues[] = array(
                    'tag_label' => isset($tag_label_map[$distribution_group['tag_id']]) ? $tag_label_map[$distribution_group['tag_id']] : ('Tag #' . $distribution_group['tag_id']),
                    'bereich_name' => $distribution_group['bereich_name'],
                    'delta' => $distribution_max - $distribution_min,
                    'min' => $distribution_min,
                    'max' => $distribution_max,
                );
            }
        }

        $distribution_score = $distribution_total > 0
            ? round(($distribution_balanced / $distribution_total) * 100)
            : 100;

        $coverage_counts = array();
        $coverage_area_names = array();

        foreach ($event_dienste as $dienst_item) {
            $coverage_tag_id = intval($dienst_item->tag_id);
            $coverage_bereich_id = intval($dienst_item->bereich_id);
            if ($coverage_tag_id <= 0 || $coverage_bereich_id <= 0) {
                continue;
            }

            $coverage_area_names[$coverage_tag_id . '|' . $coverage_bereich_id] = !empty($dienst_item->bereich_name)
                ? $dienst_item->bereich_name
                : ('Bereich #' . $coverage_bereich_id);

            $coverage_von = !empty($dienst_item->von_zeit) ? $dienst_item->von_zeit : '00:00:00';
            $coverage_bis = !empty($dienst_item->bis_zeit) ? $dienst_item->bis_zeit : $coverage_von;

            $coverage_von_parts = explode(':', $coverage_von);
            $coverage_bis_parts = explode(':', $coverage_bis);

            $coverage_start_min = (intval($coverage_von_parts[0]) * 60) + intval($coverage_von_parts[1]);
            $coverage_end_min = (intval($coverage_bis_parts[0]) * 60) + intval($coverage_bis_parts[1]);

            if ($coverage_end_min <= $coverage_start_min) {
                $coverage_end_min += 24 * 60;
            }

            $coverage_personen = max(1, intval(isset($dienst_item->anzahl_personen) ? $dienst_item->anzahl_personen : 1));
            $coverage_bucket_start = intval(floor($coverage_start_min / 30) * 30);

            for ($coverage_minute = $coverage_bucket_start; $coverage_minute < $coverage_end_min; $coverage_minute += 30) {
                $coverage_key = $coverage_tag_id . '|' . $coverage_bereich_id . '|' . $coverage_minute;
                if (!isset($coverage_counts[$coverage_key])) {
                    $coverage_counts[$coverage_key] = 0;
                }
                $coverage_counts[$coverage_key] += $coverage_personen;
            }
        }

        $coverage_total = count($coverage_counts);
        $coverage_ok = 0;
        $coverage_issues = array();

        foreach ($coverage_counts as $coverage_key => $coverage_person_count) {
            $coverage_parts = explode('|', $coverage_key);
            $coverage_tag_id = intval($coverage_parts[0]);
            $coverage_bereich_id = intval($coverage_parts[1]);
            $coverage_minute = intval($coverage_parts[2]);

            if (intval($coverage_person_count) >= 2) {
                $coverage_ok++;
                continue;
            }

            $coverage_clock = $coverage_minute % (24 * 60);
            $coverage_hour = intval(floor($coverage_clock / 60));
            $coverage_min = intval($coverage_clock % 60);
            $coverage_time_label = sprintf('%02d:%02d', $coverage_hour, $coverage_min);

            $coverage_area_key = $coverage_tag_id . '|' . $coverage_bereich_id;
            $coverage_issues[] = array(
                'tag_label' => isset($tag_label_map[$coverage_tag_id]) ? $tag_label_map[$coverage_tag_id] : ('Tag #' . $coverage_tag_id),
                'bereich_name' => isset($coverage_area_names[$coverage_area_key]) ? $coverage_area_names[$coverage_area_key] : ('Bereich #' . $coverage_bereich_id),
                'time_label' => $coverage_time_label,
                'persons' => intval($coverage_person_count),
            );
        }

        $coverage_score = $coverage_total > 0
            ? round(($coverage_ok / $coverage_total) * 100)
            : 100;
        ?>

        <div class="dp-card" style="margin-bottom: 1rem;">
            <h2 style="margin-top: 0;"><?php echo esc_html($selected_veranstaltung->name); ?></h2>
            <p style="margin: 0; color: #64748b;">
                <?php printf(__('Tage: %1$s | Vereine: %2$s | Dienste: %3$s', 'dienstplan-verwaltung'), count($tage), count($event_vereine), count($event_dienste)); ?>
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <div class="dp-card" style="border-left: 4px solid #2563eb;">
                <h3 style="margin-top: 0;"><?php _e('Statistik 1: Gleichverteilung', 'dienstplan-verwaltung'); ?></h3>
                <p style="font-size: 2rem; font-weight: 700; margin: 0.2rem 0;"><?php echo intval($distribution_score); ?>%</p>
                <p style="margin: 0; color: #475569;">
                    <?php printf(__('%1$s von %2$s Tag/Bereich-Kombinationen sind gleich verteilt.', 'dienstplan-verwaltung'), intval($distribution_balanced), intval($distribution_total)); ?>
                </p>
            </div>

            <div class="dp-card" style="border-left: 4px solid #16a34a;">
                <h3 style="margin-top: 0;"><?php _e('Statistik 2: Zeitplan-Abdeckung', 'dienstplan-verwaltung'); ?></h3>
                <p style="font-size: 2rem; font-weight: 700; margin: 0.2rem 0;"><?php echo intval($coverage_score); ?>%</p>
                <p style="margin: 0; color: #475569;">
                    <?php printf(__('%1$s von %2$s 30-Minuten-Fenstern erreichen mind. 2 Personen pro Bereich.', 'dienstplan-verwaltung'), intval($coverage_ok), intval($coverage_total)); ?>
                </p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: start;">
            <div class="dp-card">
                <h3 style="margin-top: 0;"><?php _e('Ungleich verteilte Bereiche', 'dienstplan-verwaltung'); ?></h3>
                <?php if (empty($distribution_issues)): ?>
                    <p style="margin: 0; color: #166534;"><?php _e('Keine Auffälligkeiten gefunden.', 'dienstplan-verwaltung'); ?></p>
                <?php else: ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Tag', 'dienstplan-verwaltung'); ?></th>
                                <th><?php _e('Bereich', 'dienstplan-verwaltung'); ?></th>
                                <th><?php _e('Min/Max', 'dienstplan-verwaltung'); ?></th>
                                <th><?php _e('Delta', 'dienstplan-verwaltung'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($distribution_issues as $issue): ?>
                                <tr>
                                    <td><?php echo esc_html($issue['tag_label']); ?></td>
                                    <td><?php echo esc_html($issue['bereich_name']); ?></td>
                                    <td><?php echo intval($issue['min']); ?> / <?php echo intval($issue['max']); ?></td>
                                    <td><?php echo intval($issue['delta']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="dp-card">
                <h3 style="margin-top: 0;"><?php _e('Unterdeckte 30-Minuten-Fenster', 'dienstplan-verwaltung'); ?></h3>
                <?php if (empty($coverage_issues)): ?>
                    <p style="margin: 0; color: #166534;"><?php _e('Alle Zeitfenster erfüllen die Mindestbesetzung.', 'dienstplan-verwaltung'); ?></p>
                <?php else: ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Tag', 'dienstplan-verwaltung'); ?></th>
                                <th><?php _e('Bereich', 'dienstplan-verwaltung'); ?></th>
                                <th><?php _e('Zeit', 'dienstplan-verwaltung'); ?></th>
                                <th><?php _e('Personen', 'dienstplan-verwaltung'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coverage_issues as $issue): ?>
                                <tr>
                                    <td><?php echo esc_html($issue['tag_label']); ?></td>
                                    <td><?php echo esc_html($issue['bereich_name']); ?></td>
                                    <td><?php echo esc_html($issue['time_label']); ?></td>
                                    <td><?php echo intval($issue['persons']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

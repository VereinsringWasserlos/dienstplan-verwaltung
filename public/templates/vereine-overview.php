<?php
if (!defined('ABSPATH')) {
    exit;
}

$show_only_active = ($atts['show_aktiv'] ?? 'true') === 'true';
$verein_id = isset($_GET['verein_id']) ? intval($_GET['verein_id']) : intval($atts['verein_id'] ?? 0);

$vereine = $db->get_vereine($show_only_active);
$selected_verein = null;

if ($verein_id > 0) {
    foreach ($vereine as $verein_item) {
        if (intval($verein_item->id) === $verein_id) {
            $selected_verein = $verein_item;
            break;
        }
    }
}

if (!$selected_verein && is_singular('page')) {
    $current_page_id = intval(get_queried_object_id());
    if ($current_page_id > 0) {
        foreach ($vereine as $verein_item) {
            if (intval($verein_item->seite_id) === $current_page_id) {
                $selected_verein = $verein_item;
                break;
            }
        }

        if (!$selected_verein) {
            $meta_verein_id = intval(get_post_meta($current_page_id, '_dp_verein_id', true));
            if ($meta_verein_id > 0) {
                foreach ($vereine as $verein_item) {
                    if (intval($verein_item->id) === $meta_verein_id) {
                        $selected_verein = $verein_item;
                        break;
                    }
                }
            }
        }
    }
}

$events_by_verein = array();
if (!empty($vereine)) {
    $wpdb = $db->get_wpdb();
    $prefix = $db->get_prefix();

    $event_rows = $wpdb->get_results(
        "SELECT vv.verein_id, v.id as veranstaltung_id, v.name, v.start_datum, v.end_datum, v.status
         FROM {$prefix}veranstaltung_vereine vv
         INNER JOIN {$prefix}veranstaltungen v ON v.id = vv.veranstaltung_id
         ORDER BY v.start_datum ASC, v.name ASC"
    );

    foreach ($event_rows as $event_row) {
        $key = intval($event_row->verein_id);
        if (!isset($events_by_verein[$key])) {
            $events_by_verein[$key] = array();
        }
        $events_by_verein[$key][] = $event_row;
    }
}

$header_logo_url = '';
$header_title = 'Dienstplan';
$header_subtitle = 'Vereinsübersicht';
$header_event_count = 0;
$is_logged_in = is_user_logged_in();
$request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
$current_request_url = set_url_scheme(home_url($request_uri), is_ssl() ? 'https' : 'http');
$logout_url = wp_logout_url($current_request_url);
$can_manage_backend = current_user_can('manage_options') || Dienstplan_Roles::can_manage_events() || Dienstplan_Roles::can_manage_clubs();
$backend_url = admin_url('admin.php?page=dienstplan');
if (Dienstplan_Roles::can_manage_clubs()) {
    $backend_url = admin_url('admin.php?page=dienstplan-vereine');
} elseif (Dienstplan_Roles::can_manage_events()) {
    $backend_url = admin_url('admin.php?page=dienstplan-veranstaltungen');
}

if ($selected_verein) {
    $header_title = $selected_verein->name;
    $header_subtitle = !empty($selected_verein->kuerzel) ? $selected_verein->kuerzel : 'Vereinsseite';
    $header_event_count = isset($events_by_verein[intval($selected_verein->id)]) ? count($events_by_verein[intval($selected_verein->id)]) : 0;
    if (!empty($selected_verein->logo_id)) {
        $header_logo_url = wp_get_attachment_url($selected_verein->logo_id);
    }
}
?>

<div class="dp-public-container dp-vereine-overview">
    <div class="dp-landing-header<?php echo !empty($header_logo_url) ? ' has-bg-logo' : ''; ?>"<?php if (!empty($header_logo_url)): ?> style="--dp-header-logo: url('<?php echo esc_url($header_logo_url); ?>');"<?php endif; ?>>
        <div class="dp-landing-brand">
            <div class="dp-landing-text">
                <div class="dp-landing-title"><?php echo esc_html($header_title); ?></div>
                <div class="dp-landing-subtitle"><?php echo esc_html($header_subtitle); ?></div>
            </div>
        </div>
        <div class="dp-landing-actions">
            <?php if ($selected_verein): ?>
                <div class="dp-landing-badge"><?php echo intval($header_event_count); ?> Veranstaltungen</div>
            <?php endif; ?>

            <?php if ($can_manage_backend): ?>
                <a class="dp-header-chip dp-header-chip-link dp-backend-chip" href="<?php echo esc_url($backend_url); ?>">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <span>Backend</span>
                </a>
            <?php endif; ?>

            <?php if ($is_logged_in): ?>
                <a class="dp-header-chip dp-header-chip-link dp-auth-chip is-logout" href="<?php echo esc_url($logout_url); ?>">
                    <span class="dashicons dashicons-unlock"></span>
                    <span>Ausloggen</span>
                </a>
            <?php else: ?>
                <button type="button" class="dp-header-chip dp-header-chip-link dp-auth-chip is-login" onclick="dpOpenLoginModal()">
                    <span class="dashicons dashicons-lock"></span>
                    <span>Einloggen</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($vereine)): ?>
        <div class="dp-notice dp-notice-info"><p>Keine Vereine verfügbar.</p></div>
    <?php else: ?>

        <?php if (!$selected_verein): ?>
            <div class="dp-vereine-grid">
                <?php foreach ($vereine as $verein_item):
                    $count_events = isset($events_by_verein[intval($verein_item->id)]) ? count($events_by_verein[intval($verein_item->id)]) : 0;
                    $verein_url = add_query_arg(array('verein_id' => intval($verein_item->id)));
                    if (!empty($verein_item->seite_id)) {
                        $verein_page = get_post(intval($verein_item->seite_id));
                        if ($verein_page && $verein_page->post_type === 'page' && $verein_page->post_status !== 'trash') {
                            $verein_url = get_permalink($verein_page->ID);
                        }
                    }
                ?>
                    <a href="<?php echo esc_url($verein_url); ?>" class="dp-verein-card">
                        <strong><?php echo esc_html($verein_item->name); ?></strong>
                        <?php if (!empty($verein_item->kuerzel)): ?>
                            <span class="dp-verein-kuerzel"><?php echo esc_html($verein_item->kuerzel); ?></span>
                        <?php endif; ?>
                        <span class="dp-verein-count"><?php echo intval($count_events); ?> Veranstaltungen</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($selected_verein):
            $verein_events = $events_by_verein[intval($selected_verein->id)] ?? array();
        ?>
            <div class="dp-verein-events">
                <?php if (empty($verein_events)): ?>
                    <p>Für diesen Verein sind keine Veranstaltungen zugeordnet.</p>
                <?php else: ?>
                    <div class="dp-events-list">
                        <?php foreach ($verein_events as $event):
                            $event_period = '';
                            if (!empty($event->start_datum)) {
                                $event_period = date('d.m.Y', strtotime($event->start_datum));
                                if (!empty($event->end_datum) && $event->end_datum !== $event->start_datum) {
                                    $event_period .= ' – ' . date('d.m.Y', strtotime($event->end_datum));
                                }
                            }

                            $status_label = 'In Planung';
                            $status_class = 'is-planning';
                            switch ($event->status ?? 'in_planung') {
                                case 'geplant':
                                    $status_label = 'Anmeldung möglich';
                                    $status_class = 'is-planned';
                                    break;
                                case 'aktiv':
                                    $status_label = 'Läuft';
                                    $status_class = 'is-active';
                                    break;
                                case 'abgeschlossen':
                                    $status_label = 'Abgeschlossen';
                                    $status_class = 'is-done';
                                    break;
                            }

                            $dienst_zeitraum_label = '';
                            $event_services_all = $db->get_dienste(intval($event->veranstaltung_id));
                            $event_services = array_filter($event_services_all, function($service) use ($selected_verein) {
                                return intval($service->verein_id ?? 0) === intval($selected_verein->id);
                            });

                            if (!empty($event_services)) {
                                $event_tage = $db->get_veranstaltung_tage(intval($event->veranstaltung_id));
                                $tage_by_id = array();
                                foreach ($event_tage as $tag_item) {
                                    $tage_by_id[intval($tag_item->id)] = $tag_item;
                                }

                                $dienst_start_dt = null;
                                $dienst_end_dt = null;

                                foreach ($event_services as $service) {
                                    $tag_id = intval($service->tag_id ?? 0);
                                    if (!isset($tage_by_id[$tag_id])) {
                                        continue;
                                    }

                                    $tag_date = $tage_by_id[$tag_id]->tag_datum ?? null;
                                    if (empty($tag_date)) {
                                        continue;
                                    }

                                    $von_zeit = $service->von_zeit ?? '00:00:00';
                                    $bis_zeit = $service->bis_zeit ?? $von_zeit;
                                    $bis_datum = $service->bis_datum ?? null;

                                    try {
                                        $start_dt = new DateTime($tag_date . ' ' . $von_zeit);
                                        $end_base_date = !empty($bis_datum) ? $bis_datum : $tag_date;
                                        $end_dt = new DateTime($end_base_date . ' ' . $bis_zeit);
                                        if ($end_dt < $start_dt) {
                                            $end_dt->modify('+1 day');
                                        }
                                    } catch (Exception $exception) {
                                        continue;
                                    }

                                    if ($dienst_start_dt === null || $start_dt < $dienst_start_dt) {
                                        $dienst_start_dt = $start_dt;
                                    }
                                    if ($dienst_end_dt === null || $end_dt > $dienst_end_dt) {
                                        $dienst_end_dt = $end_dt;
                                    }
                                }

                                if ($dienst_start_dt !== null && $dienst_end_dt !== null) {
                                    if ($dienst_start_dt->format('Y-m-d') === $dienst_end_dt->format('Y-m-d')) {
                                        $dienst_zeitraum_label = $dienst_start_dt->format('d.m.Y H:i') . ' – ' . $dienst_end_dt->format('H:i');
                                    } else {
                                        $dienst_zeitraum_label = $dienst_start_dt->format('d.m.Y H:i') . ' – ' . $dienst_end_dt->format('d.m.Y H:i');
                                    }
                                }
                            }

                            $existing_page_ids = get_posts(array(
                                'post_type' => 'page',
                                'post_status' => 'any',
                                'meta_query' => array(
                                    array('key' => '_dp_veranstaltung_id', 'value' => intval($event->veranstaltung_id)),
                                    array('key' => '_dp_verein_id', 'value' => intval($selected_verein->id)),
                                ),
                                'numberposts' => 1,
                                'fields' => 'ids',
                            ));

                            $existing_page_id = !empty($existing_page_ids) ? intval($existing_page_ids[0]) : 0;
                            $public_page_url = $existing_page_id > 0 ? get_permalink($existing_page_id) : '';
                        ?>
                            <div class="dp-event-row">
                                <div class="dp-event-main">
                                    <div class="dp-event-titleline">
                                        <span class="dp-event-name"><?php echo esc_html($event->name); ?></span>
                                        <span class="dp-status-chip <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>
                                    </div>
                                    <div class="dp-event-chips">
                                        <?php if (!empty($event_period)): ?>
                                            <span class="dp-info-chip"><span class="dashicons dashicons-calendar-alt"></span> <strong>Veranstaltung:</strong> <?php echo esc_html($event_period); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($dienst_zeitraum_label)): ?>
                                            <span class="dp-info-chip"><span class="dashicons dashicons-clock"></span> <strong>Dienstzeitraum:</strong> <?php echo esc_html($dienst_zeitraum_label); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <span class="dp-event-open">
                                    <?php if ($existing_page_id > 0): ?>
                                        <a href="<?php echo esc_url($public_page_url); ?>">Diensteseite öffnen →</a>
                                    <?php else: ?>
                                        <span class="dp-event-open-missing">Keine Diensteseite</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php if (!$is_logged_in): ?>
<div id="dp-login-modal" class="dp-modal" style="display: none;">
    <div class="dp-modal-content">
        <div class="dp-modal-header">
            <h2>Einloggen</h2>
            <button class="dp-modal-close" onclick="dpCloseLoginModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <div class="dp-login-form-wrap">
                <?php
                wp_login_form(array(
                    'echo' => true,
                    'remember' => true,
                    'redirect' => esc_url($current_request_url),
                    'form_id' => 'dp-login-form',
                    'label_username' => 'E-Mail oder Benutzername',
                    'label_password' => 'Passwort',
                    'label_remember' => 'Angemeldet bleiben',
                    'label_log_in' => 'Einloggen',
                ));
                ?>
                <p class="dp-login-help">
                    <a href="<?php echo esc_url(wp_lostpassword_url($current_request_url)); ?>">Passwort vergessen?</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.dp-vereine-overview { max-width: none; }

.dp-landing-header {
    background: linear-gradient(135deg, #eff6ff 0%, #e0ecff 45%, #f8fafc 100%);
    border: 1px solid #d6e4ff;
    border-radius: 16px;
    padding: 1.35rem 1.5rem;
    margin-bottom: 1.1rem;
    box-shadow: 0 10px 22px rgba(59, 130, 246, 0.12);
    display: grid;
    grid-template-columns: 1fr;
    justify-items: center;
    text-align: center;
    gap: 0.8rem;
    position: relative;
    overflow: hidden;
}
.dp-landing-header.has-bg-logo::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: var(--dp-header-logo);
    background-repeat: no-repeat;
    background-position: center;
    background-size: clamp(180px, 36%, 420px);
    opacity: 0.09;
    filter: grayscale(100%) saturate(0.35) contrast(0.9);
    mix-blend-mode: multiply;
    pointer-events: none;
    z-index: 0;
}
.dp-landing-brand { display: inline-flex; align-items: center; gap: 0.95rem; }
.dp-landing-brand { flex-direction: column; gap: 0.65rem; position: relative; z-index: 1; }
.dp-landing-title { font-size: 2.4rem; font-weight: 800; color: #0f172a; line-height: 1; letter-spacing: -0.02em; }
.dp-landing-subtitle { margin-top: 0.35rem; font-size: 0.95rem; color: #475569; font-weight: 500; }
.dp-landing-actions {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 0.55rem;
    position: relative;
    z-index: 1;
}
.dp-landing-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    border: 1px solid #93c5fd;
    background: rgba(255, 255, 255, 0.85);
    color: #1d4ed8;
    font-weight: 700;
    font-size: 0.9rem;
    padding: 0.42rem 0.85rem;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    position: relative;
    z-index: 1;
}
.dp-header-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.36rem;
    border-radius: 9999px;
    border: 1px solid #cbd5e1;
    padding: 0.42rem 0.85rem;
    font-size: 0.86rem;
    font-weight: 700;
}
.dp-header-chip-link {
    text-decoration: none;
    transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
    cursor: pointer;
}
.dp-header-chip-link:hover {
    transform: translateY(-1px);
}
.dp-auth-chip.is-login {
    background: #ffffff;
    border-color: #86efac;
    color: #166534;
}
.dp-auth-chip.is-login:hover {
    background: #f0fdf4;
    border-color: #4ade80;
}
.dp-auth-chip.is-logout {
    background: #ffffff;
    border-color: #fca5a5;
    color: #991b1b;
}
.dp-auth-chip.is-logout:hover {
    background: #fef2f2;
    border-color: #f87171;
}
.dp-backend-chip {
    background: #e0f2fe;
    border-color: #7dd3fc;
    color: #0c4a6e;
}
.dp-backend-chip:hover {
    background: #cffafe;
    border-color: #22d3ee;
}

.dp-vereine-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}
.dp-verein-card {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    padding: 0.8rem;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #fff;
    color: #0f172a;
    text-decoration: none;
}
.dp-verein-card:hover { background: #f8fbff; border-color: #93c5fd; }
.dp-verein-kuerzel,
.dp-verein-count { font-size: 0.85rem; color: #64748b; }

.dp-verein-events {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.25rem;
}
.dp-events-list { display: grid; gap: 0.7rem; }
.dp-event-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    align-items: start;
    padding: 0.85rem 1rem;
    border: 1px solid #dbe3ef;
    border-radius: 10px;
    color: #0f172a;
    background: #f8fbff;
}
.dp-event-row:hover { background: #eff6ff; border-color: #7fb5ff; }

.dp-event-main { display: flex; flex-direction: column; gap: 0.45rem; }
.dp-event-titleline { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
.dp-event-name { font-weight: 600; font-size: 1.05rem; }
.dp-event-chips { display: flex; flex-wrap: wrap; gap: 0.45rem; }
.dp-info-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    background: #f8fafc;
    color: #1f2937;
    padding: 0.35rem 0.55rem;
    font-size: 0.86rem;
}

.dp-status-chip {
    display: inline-flex;
    align-items: center;
    border-radius: 9999px;
    padding: 0.28rem 0.62rem;
    font-size: 0.84rem;
    font-weight: 600;
    border: 1px solid transparent;
}
.dp-status-chip.is-planned { background: #dcfce7; border-color: #86efac; color: #166534; }
.dp-status-chip.is-active { background: #fef3c7; border-color: #fde68a; color: #92400e; }
.dp-status-chip.is-done { background: #f3f4f6; border-color: #d1d5db; color: #374151; }
.dp-status-chip.is-planning { background: #dbeafe; border-color: #93c5fd; color: #1e40af; }

.dp-event-open {
    color: #0369a1;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.45rem;
    white-space: nowrap;
}
.dp-event-open a { color: inherit; text-decoration: none; }
.dp-event-open a:hover { text-decoration: underline; }
.dp-event-open-missing { color: #94a3b8; font-weight: 500; }
.dp-share-link-btn {
    margin-left: 0.5rem;
    border: 1px solid #bfdbfe;
    border-radius: 9999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.2rem 0.55rem;
    cursor: pointer;
}
.dp-share-link-btn:hover { background: #dbeafe; }

@media (max-width: 768px) {
    .dp-landing-header.has-bg-logo::after {
        background-size: clamp(140px, 60%, 240px);
        opacity: 0.08;
    }
    .dp-event-row { grid-template-columns: 1fr; }
    .dp-event-open { justify-content: flex-start; }
}
</style>

<script>
function dpShareLink(url, eventName) {
    if (!url) {
        alert('Kein Link verfügbar.');
        return;
    }

    if (navigator.share) {
        navigator.share({
            title: eventName ? ('Dienstplan: ' + eventName) : 'Dienstplan',
            url: url
        }).catch(function() {});
        return;
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url)
            .then(function() {
                alert('Link kopiert: ' + url);
            })
            .catch(function() {
                window.prompt('Link zum Teilen kopieren:', url);
            });
        return;
    }

    window.prompt('Link zum Teilen kopieren:', url);
}

function dpOpenLoginModal() {
    var modal = document.getElementById('dp-login-modal');
    if (!modal) {
        return;
    }
    modal.style.display = 'flex';
}

function dpCloseLoginModal() {
    var modal = document.getElementById('dp-login-modal');
    if (!modal) {
        return;
    }
    modal.style.display = 'none';
}

document.addEventListener('click', function(event) {
    var modal = document.getElementById('dp-login-modal');
    if (!modal || modal.style.display === 'none') {
        return;
    }
    if (event.target === modal) {
        dpCloseLoginModal();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key !== 'Escape') {
        return;
    }
    var modal = document.getElementById('dp-login-modal');
    if (!modal || modal.style.display === 'none') {
        return;
    }
    dpCloseLoginModal();
});
</script>

<?php
/**
 * Bereiche & Tätigkeiten Verwaltung
 * Modernes Design wie Dienste-Seite
 */

if (!defined('ABSPATH')) exit;

$bereiche           = $db->get_bereiche();
$bereichsgruppen    = $db->get_bereichsgruppen();
$is_restricted_club_admin = Dienstplan_Roles::is_restricted_club_admin();

// Bereiche nach gruppe_id organisieren
$bereiche_ohne_gruppe = [];
$bereiche_nach_gruppe = []; // gruppe_id => [bereiche]
foreach ($bereiche as $b) {
    if (empty($b->gruppe_id)) {
        $bereiche_ohne_gruppe[] = $b;
    } else {
        $bereiche_nach_gruppe[intval($b->gruppe_id)][] = $b;
    }
}

// Setup für Page-Header Partial
$page_title = __('Bereiche & Tätigkeiten', 'dienstplan-verwaltung');
$page_icon = 'dashicons-category';
$page_class = 'header-bereiche';
$nav_items = [
    [
        'label' => __('Dashboard', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan'),
        'icon' => 'dashicons-dashboard',
    ],
    [
        'label' => __('Vereine', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-vereine'),
        'icon' => 'dashicons-flag',
        'capability' => 'manage_clubs',
    ],
    [
        'label' => __('Veranstaltungen', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-veranstaltungen'),
        'icon' => 'dashicons-calendar-alt',
        'capability' => 'manage_events',
    ],
    [
        'label' => __('Dienste', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-dienste'),
        'icon' => 'dashicons-clipboard',
        'capability' => 'manage_services',
    ],
    [
        'label' => __('Mitarbeiter', 'dienstplan-verwaltung'),
        'url' => admin_url('admin.php?page=dienstplan-mitarbeiter'),
        'icon' => 'dashicons-groups',
        'capability' => 'manage_employees',
    ],
];
?>

<div class="wrap dienstplan-wrap">
    <?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/page-header.php'; ?>
    
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($_GET['message']); ?></p>
        </div>
    <?php endif; ?>

    <!-- Toolbar: Neue Gruppe + Neuer Bereich -->
    <?php if (!$is_restricted_club_admin): ?>
    <div style="display:flex; gap:0.75rem; margin-top:1.5rem; flex-wrap:wrap;">
        <button class="button button-primary" onclick="openGruppeModal(); return false;">
            <span class="dashicons dashicons-plus-alt" style="margin-top:3px;"></span>
            <?php _e('Neue Dienstgruppe', 'dienstplan-verwaltung'); ?>
        </button>
        <button class="button" onclick="openBereichModal(); return false;">
            <span class="dashicons dashicons-plus-alt" style="margin-top:3px;"></span>
            <?php _e('Neuer Bereich', 'dienstplan-verwaltung'); ?>
        </button>
    </div>
    <?php endif; ?>

    <?php if (empty($bereiche) && empty($bereichsgruppen)): ?>
        <div style="background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:3rem;text-align:center;margin-top:2rem;">
            <span class="dashicons dashicons-category" style="font-size:64px;color:#c3c4c7;"></span>
            <h2 style="color:#50575e;margin-bottom:0.5rem;"><?php _e('Noch keine Bereiche', 'dienstplan-verwaltung'); ?></h2>
            <p style="color:#787c82;margin-bottom:2rem;"><?php _e('Erstellen Sie zuerst eine Dienstgruppe (z.B. "Aufbau"), dann Bereiche und Tätigkeiten darin.', 'dienstplan-verwaltung'); ?></p>
        </div>
    <?php else: ?>

    <div class="bereiche-list" style="margin-top:1.5rem;">

        <?php
        // Hilfsfunktion: Bereichsblock rendern
        $render_bereich = function($bereich, $db, $is_restricted_club_admin, $bereichsgruppen) {
            $taetigkeiten = $db->get_taetigkeiten_by_bereich($bereich->id);
            $collapse_id  = 'bereich-' . $bereich->id;
            ?>
            <div class="bereich-gruppe" style="margin-bottom:0.85rem;border:1px solid #c3c4c7;border-radius:4px;position:relative;">
                <h3 class="bereich-header-collapsible" style="background:<?php echo esc_attr($bereich->farbe ?? '#3b82f6'); ?>;color:white;padding:0.75rem 1.25rem;margin:0;display:flex;align-items:center;gap:0.75rem;cursor:pointer;" onclick="dpToggleBereich('<?php echo $collapse_id; ?>')">
                    <span class="dashicons dashicons-arrow-down-alt2 collapse-icon" id="icon-<?php echo $collapse_id; ?>" style="transition:transform 0.2s;font-size:18px;"></span>
                    <span class="dashicons dashicons-category" style="font-size:20px;"></span>
                    <strong style="flex:1;font-size:1rem;"><?php echo esc_html($bereich->name); ?></strong>
                    <?php if (!$is_restricted_club_admin): ?>
                        <button type="button" class="button" onclick="event.stopPropagation();openGruppeBereichModal(<?php echo intval($bereich->id); ?>, <?php echo $bereich->gruppe_id ? intval($bereich->gruppe_id) : 0; ?>);" style="background:rgba(255,255,255,0.25);color:#fff;border-color:rgba(255,255,255,0.4);font-size:0.8rem;padding:0.3rem 0.6rem;" title="Gruppe zuweisen">
                            <span class="dashicons dashicons-move" style="font-size:14px;width:14px;height:14px;margin-top:1px;"></span>
                        </button>
                        <button type="button" class="button" onclick="event.stopPropagation();openBereichModal(<?php echo intval($bereich->id); ?>);" style="background:rgba(255,255,255,0.25);color:#fff;border-color:rgba(255,255,255,0.4);font-size:0.8rem;padding:0.3rem 0.6rem;">
                            <span class="dashicons dashicons-edit" style="font-size:14px;width:14px;height:14px;margin-top:1px;"></span>
                        </button>
                    <?php endif; ?>
                    <button type="button" class="button" onclick="event.stopPropagation();openTaetigkeitModal(<?php echo intval($bereich->id); ?>);" style="background:rgba(255,255,255,0.9);color:<?php echo esc_attr($bereich->farbe ?? '#3b82f6'); ?>;border:none;font-weight:600;font-size:0.8rem;padding:0.3rem 0.75rem;">
                        <span class="dashicons dashicons-plus-alt" style="font-size:14px;width:14px;height:14px;margin-top:1px;"></span>
                        <?php _e('Tätigkeit', 'dienstplan-verwaltung'); ?>
                    </button>
                    <span style="background:rgba(255,255,255,0.2);padding:0.2rem 0.6rem;border-radius:20px;font-size:0.8rem;">
                        <?php echo count($taetigkeiten); ?> <?php echo count($taetigkeiten) !== 1 ? 'Tätigkeiten' : 'Tätigkeit'; ?>
                    </span>
                </h3>
                <div id="<?php echo $collapse_id; ?>" class="bereich-content">
                    <?php if (empty($taetigkeiten)): ?>
                        <div style="padding:1.5rem;text-align:center;background:#f9fafb;color:#787c82;">
                            <?php _e('Noch keine Tätigkeiten. Klicke "+ Tätigkeit" um eine hinzuzufügen.', 'dienstplan-verwaltung'); ?>
                        </div>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped" style="border:none;margin:0;">
                            <thead>
                                <tr>
                                    <th style="width:40px;padding-left:10px;"><input type="checkbox" class="select-all-header" data-bereich="<?php echo $bereich->id; ?>"></th>
                                    <th style="width:60px;">ID</th>
                                    <th><?php _e('Tätigkeit', 'dienstplan-verwaltung'); ?></th>
                                    <th style="width:100px;"><?php _e('Status', 'dienstplan-verwaltung'); ?></th>
                                    <th style="width:130px;text-align:center;"><?php _e('Aktionen', 'dienstplan-verwaltung'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($taetigkeiten as $taetigkeit): ?>
                                    <tr data-taetigkeit-id="<?php echo $taetigkeit->id; ?>">
                                        <td style="padding-left:10px;"><input type="checkbox" class="taetigkeit-checkbox" value="<?php echo $taetigkeit->id; ?>" data-bereich="<?php echo $bereich->id; ?>"></td>
                                        <td><?php echo intval($taetigkeit->id); ?></td>
                                        <td>
                                            <strong><?php echo esc_html($taetigkeit->name); ?></strong>
                                            <?php if (!empty($taetigkeit->beschreibung)): ?>
                                                <br><small style="color:#666;"><?php echo esc_html($taetigkeit->beschreibung); ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($taetigkeit->admin_only)): ?>
                                                <span style="margin-left:0.4rem;" title="Nur Admins">🔒</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php $st = $taetigkeit->aktiv ? 'aktiv' : 'inaktiv'; ?>
                                            <span style="display:inline-block;padding:0.2rem 0.6rem;border-radius:12px;font-size:0.75rem;font-weight:600;background:<?php echo $st === 'aktiv' ? '#10b981' : '#6b7280'; ?>;color:#fff;">
                                                <?php echo $st === 'aktiv' ? 'Aktiv' : 'Inaktiv'; ?>
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <div class="dp-inline-action-buttons">
                                                <a href="#" class="button button-small dp-inline-action-button" onclick="openTaetigkeitModal(<?php echo intval($bereich->id); ?>, <?php echo intval($taetigkeit->id); ?>); return false;">✏️</a>
                                                <a href="#" class="button button-small dp-inline-action-button is-danger" onclick="deleteTaetigkeit(<?php echo intval($taetigkeit->id); ?>); return false;">🗑️</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        };
        ?>

        <?php // ── Gruppen mit ihren Bereichen ─────────────────────────────────────── ?>
        <?php foreach ($bereichsgruppen as $gruppe):
            $gruppe_bereiche = $bereiche_nach_gruppe[intval($gruppe->id)] ?? [];
            $gruppe_collapse = 'gruppe-' . $gruppe->id;
            $gruppen_farbe   = $gruppe->farbe ?? '#64748b';
        ?>
        <div class="dp-bereichsgruppe" style="margin-bottom:1.5rem;border:2px solid <?php echo esc_attr($gruppen_farbe); ?>;border-radius:6px;overflow:hidden;">
            <!-- Gruppen-Header -->
            <div style="background:<?php echo esc_attr($gruppen_farbe); ?>;color:#fff;padding:0.85rem 1.25rem;display:flex;align-items:center;gap:0.75rem;cursor:pointer;" onclick="dpToggleGruppe('<?php echo $gruppe_collapse; ?>')">
                <span class="dashicons dashicons-arrow-down-alt2 collapse-icon" id="icon-<?php echo $gruppe_collapse; ?>" style="font-size:20px;transition:transform 0.2s;"></span>
                <span class="dashicons dashicons-networking" style="font-size:22px;"></span>
                <strong style="flex:1;font-size:1.05rem;"><?php echo esc_html($gruppe->name); ?></strong>
                <?php if (!empty($gruppe->beschreibung)): ?>
                    <span style="font-size:0.85rem;opacity:0.85;"><?php echo esc_html($gruppe->beschreibung); ?></span>
                <?php endif; ?>
                <?php if (!$is_restricted_club_admin): ?>
                    <button type="button" class="button" onclick="event.stopPropagation();openGruppeModal(<?php echo intval($gruppe->id); ?>);" style="background:rgba(255,255,255,0.25);color:#fff;border-color:rgba(255,255,255,0.4);font-size:0.8rem;padding:0.3rem 0.6rem;">
                        <span class="dashicons dashicons-edit" style="font-size:14px;width:14px;height:14px;margin-top:1px;"></span>
                    </button>
                    <button type="button" class="button" onclick="event.stopPropagation();deleteGruppe(<?php echo intval($gruppe->id); ?>, '<?php echo esc_js($gruppe->name); ?>');" style="background:rgba(255,100,100,0.25);color:#fff;border-color:rgba(255,100,100,0.4);font-size:0.8rem;padding:0.3rem 0.6rem;">
                        <span class="dashicons dashicons-trash" style="font-size:14px;width:14px;height:14px;margin-top:1px;"></span>
                    </button>
                <?php endif; ?>
                <span style="background:rgba(255,255,255,0.2);padding:0.2rem 0.65rem;border-radius:20px;font-size:0.8rem;">
                    <?php echo count($gruppe_bereiche); ?> Bereich<?php echo count($gruppe_bereiche) !== 1 ? 'e' : ''; ?>
                </span>
            </div>
            <!-- Bereiche innerhalb der Gruppe -->
            <div id="<?php echo $gruppe_collapse; ?>" style="padding:0.75rem 0.75rem 0.25rem;">
                <?php if (empty($gruppe_bereiche)): ?>
                    <p style="color:#6b7280;padding:0.75rem;font-style:italic;"><?php _e('Keine Bereiche in dieser Gruppe. Bereich erstellen und dieser Gruppe zuweisen.', 'dienstplan-verwaltung'); ?></p>
                <?php else: ?>
                    <?php foreach ($gruppe_bereiche as $bereich): $render_bereich($bereich, $db, $is_restricted_club_admin, $bereichsgruppen); endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php // ── Bereiche ohne Gruppe ──────────────────────────────────────────── ?>
        <?php if (!empty($bereiche_ohne_gruppe)): ?>
        <div style="margin-top:1rem;">
            <h3 style="color:#475569;font-size:0.9rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.75rem;border-bottom:1px solid #e2e8f0;padding-bottom:0.5rem;">
                <span class="dashicons dashicons-category" style="font-size:16px;margin-top:1px;"></span>
                <?php _e('Bereiche ohne Gruppe', 'dienstplan-verwaltung'); ?>
            </h3>
            <?php foreach ($bereiche_ohne_gruppe as $bereich): $render_bereich($bereich, $db, $is_restricted_club_admin, $bereichsgruppen); endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
    <?php endif; ?>
</div>

<!-- ═══ MODAL: Bereichsgruppe ═══════════════════════════════════════════════════ -->
<div id="gruppe-modal" class="dp-modal" style="display:none;">
    <div class="dp-modal-content">
        <div class="dp-modal-header">
            <h2 id="gruppe-modal-title"><?php _e('Neue Dienstgruppe', 'dienstplan-verwaltung'); ?></h2>
            <button class="dp-modal-close" onclick="closeGruppeModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="gruppe-form">
                <input type="hidden" id="gruppe_id" name="gruppe_id" value="">
                <table class="form-table">
                    <tr>
                        <th><label for="gruppe_name"><?php _e('Name', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td><input type="text" id="gruppe_name" name="gruppe_name" class="regular-text" placeholder="z.B. Aufbau, Essen, Getränke" required></td>
                    </tr>
                    <tr>
                        <th><label for="gruppe_beschreibung"><?php _e('Beschreibung', 'dienstplan-verwaltung'); ?></label></th>
                        <td><input type="text" id="gruppe_beschreibung" name="gruppe_beschreibung" class="regular-text" placeholder="Kurze Beschreibung (optional)"></td>
                    </tr>
                    <tr>
                        <th><label for="gruppe_farbe"><?php _e('Farbe', 'dienstplan-verwaltung'); ?></label></th>
                        <td><input type="color" id="gruppe_farbe" name="gruppe_farbe" value="#64748b"></td>
                    </tr>
                    <tr>
                        <th><label for="gruppe_sortierung"><?php _e('Reihenfolge', 'dienstplan-verwaltung'); ?></label></th>
                        <td><input type="number" id="gruppe_sortierung" name="gruppe_sortierung" value="0" min="0" style="width:80px;"> <small style="color:#6b7280;"><?php _e('Kleinere Zahl = weiter oben', 'dienstplan-verwaltung'); ?></small></td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button class="button" onclick="closeGruppeModal()"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
            <button class="button button-primary" onclick="saveGruppe()"><?php _e('Speichern', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>

<!-- ═══ MODAL: Bereich zuordnen ═════════════════════════════════════════════════ -->
<div id="gruppe-bereich-modal" class="dp-modal" style="display:none;">
    <div class="dp-modal-content" style="max-width:420px;">
        <div class="dp-modal-header">
            <h2><?php _e('Bereich Gruppe zuweisen', 'dienstplan-verwaltung'); ?></h2>
            <button class="dp-modal-close" onclick="document.getElementById('gruppe-bereich-modal').style.display='none'">&times;</button>
        </div>
        <div class="dp-modal-body">
            <input type="hidden" id="gb_bereich_id" value="">
            <table class="form-table">
                <tr>
                    <th><label for="gb_gruppe_id"><?php _e('Gruppe', 'dienstplan-verwaltung'); ?></label></th>
                    <td>
                        <select id="gb_gruppe_id" class="regular-text">
                            <option value=""><?php _e('— Keine Gruppe —', 'dienstplan-verwaltung'); ?></option>
                            <?php foreach ($bereichsgruppen as $g): ?>
                                <option value="<?php echo intval($g->id); ?>"><?php echo esc_html($g->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <div class="dp-modal-footer">
            <button class="button" onclick="document.getElementById('gruppe-bereich-modal').style.display='none'"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
            <button class="button button-primary" onclick="saveGruppeBereich()"><?php _e('Zuweisen', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>

<!-- ═══ MODAL: Bereich ═══════════════════════════════════════════════════════════ -->
<div id="bereich-modal" class="dp-modal" style="display:none;">
    <div class="dp-modal-content">
        <div class="dp-modal-header">
            <h2 id="bereich-modal-title"><?php _e('Neuer Bereich', 'dienstplan-verwaltung'); ?></h2>
            <button class="dp-modal-close" onclick="closeBereichModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="bereich-form">
                <input type="hidden" id="bereich_id" name="bereich_id" value="">
                <table class="form-table">
                    <tr>
                        <th><label for="bereich_name"><?php _e('Name', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td><input type="text" id="bereich_name" name="bereich_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="bereich_gruppe_id"><?php _e('Dienstgruppe', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <select id="bereich_gruppe_id" name="bereich_gruppe_id" class="regular-text">
                                <option value=""><?php _e('— Keine Gruppe —', 'dienstplan-verwaltung'); ?></option>
                                <?php foreach ($bereichsgruppen as $g): ?>
                                    <option value="<?php echo intval($g->id); ?>"><?php echo esc_html($g->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="bereich_farbe"><?php _e('Farbe', 'dienstplan-verwaltung'); ?></label></th>
                        <td><input type="color" id="bereich_farbe" name="bereich_farbe" value="#3b82f6"></td>
                    </tr>
                    <tr>
                        <th><label for="bereich_beschreibung"><?php _e('Beschreibung', 'dienstplan-verwaltung'); ?></label></th>
                        <td><textarea id="bereich_beschreibung" name="bereich_beschreibung" class="large-text" rows="3"></textarea></td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <label style="display:flex;align-items:center;gap:0.75rem;margin:0;">
                                <input type="checkbox" id="bereich_admin_only" name="bereich_admin_only" value="1" style="margin:0;">
                                <strong style="color:#d97706;display:flex;align-items:center;gap:0.4rem;">
                                    <span class="dashicons dashicons-lock" style="width:18px;height:18px;font-size:18px;"></span>
                                    <?php _e('Nur Admins können zuweisen', 'dienstplan-verwaltung'); ?>
                                </strong>
                            </label>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button class="button" onclick="closeBereichModal()"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
            <?php if (!$is_restricted_club_admin): ?>
                <button class="button button-primary" onclick="saveBereich()"><?php _e('Speichern', 'dienstplan-verwaltung'); ?></button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ═══ MODAL: Tätigkeit ═════════════════════════════════════════════════════════ -->
<div id="taetigkeit-modal" class="dp-modal" style="display:none;">
    <div class="dp-modal-content">
        <div class="dp-modal-header">
            <h2 id="taetigkeit-modal-title"><?php _e('Neue Tätigkeit', 'dienstplan-verwaltung'); ?></h2>
            <button class="dp-modal-close" onclick="closeTaetigkeitModal()">&times;</button>
        </div>
        <div class="dp-modal-body">
            <form id="taetigkeit-form">
                <input type="hidden" id="taetigkeit_id" name="taetigkeit_id" value="">
                <input type="hidden" id="taetigkeit_bereich_id" name="taetigkeit_bereich_id" value="">
                <table class="form-table">
                    <tr>
                        <th><label for="taetigkeit_name"><?php _e('Name', 'dienstplan-verwaltung'); ?> *</label></th>
                        <td><input type="text" id="taetigkeit_name" name="taetigkeit_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="taetigkeit_beschreibung"><?php _e('Beschreibung', 'dienstplan-verwaltung'); ?></label></th>
                        <td><textarea id="taetigkeit_beschreibung" name="taetigkeit_beschreibung" class="large-text" rows="3"></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="taetigkeit_status"><?php _e('Status', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <select id="taetigkeit_status" name="taetigkeit_status">
                                <option value="aktiv"><?php _e('Aktiv', 'dienstplan-verwaltung'); ?></option>
                                <option value="inaktiv"><?php _e('Inaktiv', 'dienstplan-verwaltung'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <label style="display:flex;align-items:center;gap:0.75rem;margin:0;">
                                <input type="checkbox" id="taetigkeit_admin_only" name="taetigkeit_admin_only" value="1" style="margin:0;">
                                <strong style="color:#d97706;display:flex;align-items:center;gap:0.4rem;">
                                    <span class="dashicons dashicons-lock" style="width:18px;height:18px;font-size:18px;"></span>
                                    <?php _e('Nur Admins können zuweisen', 'dienstplan-verwaltung'); ?>
                                </strong>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="taetigkeit_gruppe_id"><?php _e('Gruppe', 'dienstplan-verwaltung'); ?></label></th>
                        <td>
                            <select id="taetigkeit_gruppe_id" name="taetigkeit_gruppe_id" style="min-width:220px;">
                                <option value=""><?php _e('Keine Gruppe', 'dienstplan-verwaltung'); ?></option>
                                <?php foreach ($bereichsgruppen as $grp): ?>
                                    <option value="<?php echo intval($grp->id); ?>" data-farbe="<?php echo esc_attr($grp->farbe ?? '#64748b'); ?>">
                                        <?php echo esc_html($grp->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description" style="margin-top:0.35rem;"><?php _e('Dienste dieser Tätigkeit werden im Frontend unter dieser Gruppe angezeigt.', 'dienstplan-verwaltung'); ?></p>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="dp-modal-footer">
            <button class="button" onclick="closeTaetigkeitModal()"><?php _e('Abbrechen', 'dienstplan-verwaltung'); ?></button>
            <button class="button button-primary" onclick="saveTaetigkeit()"><?php _e('Speichern', 'dienstplan-verwaltung'); ?></button>
        </div>
    </div>
</div>

<script>
var dpDienstplanNonce = '<?php echo esc_js(wp_create_nonce('dienstplan-nonce')); ?>';
var dpAjaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';

// ── Toggle ──────────────────────────────────────────────────────────────────────
function dpToggleBereich(id) {
    var el = document.getElementById(id);
    var icon = document.getElementById('icon-' + id);
    var open = el.style.display !== 'none';
    el.style.display = open ? 'none' : 'block';
    icon.style.transform = open ? 'rotate(-90deg)' : 'rotate(0deg)';
}
function dpToggleGruppe(id) {
    var el = document.getElementById(id);
    var icon = document.getElementById('icon-' + id);
    var open = el.style.display !== 'none';
    el.style.display = open ? 'none' : 'block';
    icon.style.transform = open ? 'rotate(-90deg)' : 'rotate(0deg)';
}

// ── Gruppe Modal ─────────────────────────────────────────────────────────────────
function openGruppeModal(gruppeId) {
    document.getElementById('gruppe_id').value = '';
    document.getElementById('gruppe_name').value = '';
    document.getElementById('gruppe_beschreibung').value = '';
    document.getElementById('gruppe_farbe').value = '#64748b';
    document.getElementById('gruppe_sortierung').value = '0';
    document.getElementById('gruppe-modal-title').textContent = gruppeId ? 'Dienstgruppe bearbeiten' : 'Neue Dienstgruppe';

    if (gruppeId) {
        jQuery.post(dpAjaxUrl, {
            action: 'get_bereichsgruppe',
            nonce: dpDienstplanNonce,
            gruppe_id: gruppeId
        }, function(res) {
            if (res.success) {
                document.getElementById('gruppe_id').value          = res.data.id;
                document.getElementById('gruppe_name').value        = res.data.name;
                document.getElementById('gruppe_beschreibung').value = res.data.beschreibung || '';
                document.getElementById('gruppe_farbe').value       = res.data.farbe || '#64748b';
                document.getElementById('gruppe_sortierung').value  = res.data.sortierung || 0;
            }
        });
    }
    document.getElementById('gruppe-modal').style.display = 'flex';
}
function closeGruppeModal() { document.getElementById('gruppe-modal').style.display = 'none'; }
function saveGruppe() {
    var name = document.getElementById('gruppe_name').value.trim();
    if (!name) { alert('Name ist erforderlich'); return; }
    jQuery.post(dpAjaxUrl, {
        action: 'save_bereichsgruppe',
        nonce: dpDienstplanNonce,
        gruppe_id:           document.getElementById('gruppe_id').value,
        gruppe_name:         name,
        gruppe_beschreibung: document.getElementById('gruppe_beschreibung').value,
        gruppe_farbe:        document.getElementById('gruppe_farbe').value,
        gruppe_sortierung:   document.getElementById('gruppe_sortierung').value,
    }, function(res) {
        if (res.success) { location.reload(); }
        else { alert('Fehler: ' + (res.data ? res.data.message : 'Unbekannt')); }
    });
}
function deleteGruppe(id, name) {
    if (!confirm('Gruppe "' + name + '" löschen? Bereiche bleiben erhalten, verlieren aber die Gruppenzuordnung.')) return;
    jQuery.post(dpAjaxUrl, {
        action: 'delete_bereichsgruppe',
        nonce: dpDienstplanNonce,
        gruppe_id: id
    }, function(res) {
        if (res.success) { location.reload(); }
        else { alert('Fehler: ' + (res.data ? res.data.message : 'Unbekannt')); }
    });
}

// ── Gruppe-Bereich zuweisen Modal ────────────────────────────────────────────────
function openGruppeBereichModal(bereichId, aktuelleGruppeId) {
    document.getElementById('gb_bereich_id').value = bereichId;
    document.getElementById('gb_gruppe_id').value  = aktuelleGruppeId || '';
    document.getElementById('gruppe-bereich-modal').style.display = 'flex';
}
function saveGruppeBereich() {
    var bereichId = document.getElementById('gb_bereich_id').value;
    var gruppeId  = document.getElementById('gb_gruppe_id').value;
    jQuery.post(dpAjaxUrl, {
        action: 'save_bereich',
        nonce: dpDienstplanNonce,
        bereich_id:        bereichId,
        bereich_gruppe_id: gruppeId,
    }, function(res) {
        if (res.success) { location.reload(); }
        else { alert('Fehler beim Zuweisen: ' + (res.data && res.data.message ? res.data.message : '')); }
    });
}

// ── Bereich Modal ────────────────────────────────────────────────────────────────
function openBereichModal(bereichId) {
    document.getElementById('bereich_id').value          = '';
    document.getElementById('bereich_name').value        = '';
    document.getElementById('bereich_farbe').value       = '#3b82f6';
    document.getElementById('bereich_beschreibung').value = '';
    document.getElementById('bereich_admin_only').checked = false;
    document.getElementById('bereich_gruppe_id').value   = '';
    document.getElementById('bereich-modal-title').textContent = bereichId ? 'Bereich bearbeiten' : 'Neuer Bereich';

    if (bereichId) {
        jQuery.post(dpAjaxUrl, {
            action: 'get_bereich',
            nonce: dpDienstplanNonce,
            bereich_id: bereichId
        }, function(res) {
            if (res.success) {
                document.getElementById('bereich_id').value           = res.data.id;
                document.getElementById('bereich_name').value         = res.data.name;
                document.getElementById('bereich_farbe').value        = res.data.farbe || '#3b82f6';
                document.getElementById('bereich_beschreibung').value = res.data.beschreibung || '';
                document.getElementById('bereich_admin_only').checked = parseInt(res.data.admin_only) === 1;
                document.getElementById('bereich_gruppe_id').value    = res.data.gruppe_id || '';
            }
        });
    }
    document.getElementById('bereich-modal').style.display = 'flex';
}
function closeBereichModal() { document.getElementById('bereich-modal').style.display = 'none'; }
function saveBereich() {
    var name = document.getElementById('bereich_name').value.trim();
    if (!name) { alert('Name ist erforderlich'); return; }
    jQuery.post(dpAjaxUrl, {
        action: 'save_bereich',
        nonce: dpDienstplanNonce,
        bereich_id:        document.getElementById('bereich_id').value,
        bereich_name:      name,
        bereich_farbe:     document.getElementById('bereich_farbe').value,
        bereich_admin_only: document.getElementById('bereich_admin_only').checked ? 1 : 0,
        bereich_gruppe_id: document.getElementById('bereich_gruppe_id').value,
    }, function(res) {
        if (res.success) { location.reload(); }
        else { alert('Fehler: ' + (res.data ? res.data.message : 'Unbekannt')); }
    });
}

// ── Tätigkeit Modal ──────────────────────────────────────────────────────────────
function openTaetigkeitModal(bereichId, taetigkeitId) {
    document.getElementById('taetigkeit_id').value           = '';
    document.getElementById('taetigkeit_bereich_id').value   = bereichId;
    document.getElementById('taetigkeit_name').value         = '';
    document.getElementById('taetigkeit_beschreibung').value = '';
    document.getElementById('taetigkeit_status').value       = 'aktiv';
    document.getElementById('taetigkeit_admin_only').checked = false;
    document.getElementById('taetigkeit_gruppe_id').value    = '';
    document.getElementById('taetigkeit-modal-title').textContent = taetigkeitId ? 'Tätigkeit bearbeiten' : 'Neue Tätigkeit';

    if (taetigkeitId) {
        jQuery.post(dpAjaxUrl, {
            action: 'get_taetigkeit',
            nonce: dpDienstplanNonce,
            taetigkeit_id: taetigkeitId
        }, function(res) {
            if (res.success) {
                document.getElementById('taetigkeit_id').value           = res.data.id;
                document.getElementById('taetigkeit_name').value         = res.data.name;
                document.getElementById('taetigkeit_beschreibung').value = res.data.beschreibung || '';
                document.getElementById('taetigkeit_status').value       = parseInt(res.data.aktiv) === 1 ? 'aktiv' : 'inaktiv';
                document.getElementById('taetigkeit_admin_only').checked = parseInt(res.data.admin_only) === 1;
                document.getElementById('taetigkeit_gruppe_id').value    = res.data.gruppe_id || '';
            }
        });
    }
    document.getElementById('taetigkeit-modal').style.display = 'flex';
}
function closeTaetigkeitModal() { document.getElementById('taetigkeit-modal').style.display = 'none'; }
function saveTaetigkeit() {
    var name = document.getElementById('taetigkeit_name').value.trim();
    if (!name) { alert('Name ist erforderlich'); return; }
    jQuery.post(dpAjaxUrl, {
        action: 'save_taetigkeit',
        nonce: dpDienstplanNonce,
        taetigkeit_id:          document.getElementById('taetigkeit_id').value,
        taetigkeit_bereich_id:  document.getElementById('taetigkeit_bereich_id').value,
        taetigkeit_name:        name,
        taetigkeit_beschreibung: document.getElementById('taetigkeit_beschreibung').value,
        taetigkeit_status:      document.getElementById('taetigkeit_status').value,
        taetigkeit_admin_only:  document.getElementById('taetigkeit_admin_only').checked ? 1 : 0,
        taetigkeit_gruppe_id:   document.getElementById('taetigkeit_gruppe_id').value,
    }, function(res) {
        if (res.success) { location.reload(); }
        else { alert('Fehler: ' + (res.data ? res.data.message : 'Unbekannt')); }
    });
}
function deleteTaetigkeit(id) {
    if (!confirm('Tätigkeit löschen?')) return;
    jQuery.post(dpAjaxUrl, {
        action: 'delete_taetigkeit',
        nonce: dpDienstplanNonce,
        taetigkeit_id: id
    }, function(res) {
        if (res.success) { location.reload(); }
        else { alert('Fehler: ' + (res.data ? res.data.message : 'Unbekannt')); }
    });
}
</script>

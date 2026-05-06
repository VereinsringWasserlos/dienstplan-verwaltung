# Dienstplan Verwaltung - Plugin-Struktur

## 📁 Vollständige Verzeichnisstruktur

```
dienstplan-verwaltung/
│
├── dienstplan-verwaltung.php       # Haupt-Plugin-Datei (Header, Aktivierung)
├── uninstall.php                   # Deinstallations-Routine
├── README.md                       # Dokumentation
├── CHANGELOG.md                    # Versions-Historie
├── LICENSE.txt                     # GPL-Lizenz
├── migrate-mitarbeiter-id.php      # Migrations-Script für mitarbeiter_id [NEU 0.4.7]
├── TAG_FIX_ANLEITUNG.md           # Anleitung Tag-Zuweisungs-Fix
├── check-tag-assignments.php       # Debug-Script Tag-Zuweisungen
├── fix-dienste-tags.php            # Fix-Script für Tag-Zuweisungen
├── fix-tags-cli.php                # CLI-Version des Fix-Scripts
│
├── includes/                       # Core-PHP-Klassen
│   ├── autoloader.php             # Autoloader für Klassen
│   ├── class-dienstplan-verwaltung.php  # Haupt-Plugin-Klasse
│   ├── class-loader.php           # Hook-Loader
│   ├── class-i18n.php             # Internationalisierung
│   ├── class-activator.php        # Aktivierungs-Logik
│   ├── class-deactivator.php      # Deaktivierungs-Logik
│   ├── class-database.php         # Datenbank-Manager (13 Tabellen, Migrationen)
│   ├── class-dienstplan-notifications.php  # Benachrichtigungssystem
│   └── class-dienstplan-roles.php # Rollen & Capabilities
│
├── admin/                          # Admin-Bereich
│   ├── class-admin.php            # Admin-Controller (3000+ Zeilen, alle AJAX-Handler) [AKTUALISIERT 0.4.7]
│   └── views/                     # Admin-Templates
│       ├── dashboard.php         # Dashboard-Hauptseite
│       ├── vereine.php           # Vereine-Verwaltung
│       ├── veranstaltungen.php   # Veranstaltungen-Verwaltung
│       ├── dienste.php           # Dienste-Verwaltung
│       ├── mitarbeiter.php       # Mitarbeiter-Verwaltung
│       ├── bereiche-taetigkeiten.php  # Bereiche & Tätigkeiten
│       ├── overview.php          # Übersicht (Tag-gruppiert)
│       ├── benutzerverwaltung.php  # Benutzer & Rollen
│       ├── einstellungen.php     # Plugin-Einstellungen
│       ├── import-export.php     # Import/Export CSV
│       ├── debug.php             # Debug-Informationen
│       └── partials/             # Wiederverwendbare Teilstücke
│           ├── page-header.php   # Gemeinsamer Header
│           ├── vereine-header.php
│           ├── vereine-table.php
│           ├── vereine-modal.php
│           ├── vereine-empty.php
│           ├── veranstaltungen-header.php
│           ├── veranstaltungen-table.php
│           ├── veranstaltungen-modal.php
│           ├── veranstaltungen-empty.php
│           ├── dienste-table.php
│           ├── dienste-empty.php
│           ├── dienst-modal.php
│           ├── mitarbeiter-modal.php
│           ├── mitarbeiter-dienste-modal.php
│           ├── besetzung-modal.php
│           └── bulk-update-modals.php
│
├── public/                         # Frontend/Öffentlich
│   ├── class-public.php           # Public-Controller (Split-Dienst Logik) [AKTUALISIERT 0.4.7]
│   ├── class-dienstplan-public.php  # Legacy Public-Controller
│   └── templates/                 # Frontend-Templates
│       ├── veranstaltung-compact.php  # Event-Ansicht + Anmeldeformular [AKTUALISIERT 0.4.7]
│       ├── veranstaltung-detail.php   # Detail-Ansicht
│       ├── veranstaltungen-liste.php  # Event-Liste
│       ├── meine-dienste.php      # User-Dienste
│       └── partials/              # Wiederverwendbare Teilstücke
│           ├── calendar-view.php
│           └── verein-selection.php
│
├── assets/                         # Gemeinsame Assets
│   ├── css/
│   │   ├── dp-admin.css          # Admin-Styles
│   │   └── dp-public.css         # Frontend-Styles
│   ├── js/
│   │   ├── dp-admin.js           # Admin-Scripts (Dropdown, Collapse)
│   │   ├── dp-admin-modals.js    # Modal CRUD-Funktionen (1000+ Zeilen) [NEU 0.4.7]
│   │   ├── dp-dienst-modal.js    # Dienst-Modal spezifisch
│   │   ├── dp-dienste-table.js   # Dienste-Tabelle + Bulk-Aktionen
│   │   ├── dp-bulk-update-modals.js  # Bulk-Update Dialoge
│   │   ├── dp-besetzung-modal.js # Besetzungs-Verwaltung
│   │   └── dp-public.js          # Frontend-Scripts (Dienst-Anmeldung)
│   └── images/                    # Bilder, Icons
│       └── icon-256x256.png
│
├── languages/                      # Übersetzungsdateien
│   ├── dienstplan-verwaltung.pot  # Template
│   ├── dienstplan-verwaltung-de_DE.po
│   └── dienstplan-verwaltung-de_DE.mo
│
└── documentation/                  # Entwickler-Dokumentation
    ├── CHANGELOG.md               # Versions-Historie [AKTUALISIERT 0.4.7]
    ├── DATABASE_STRUCTURE_AKTUELL.md  # Aktuelle DB-Struktur (550+ Zeilen) [NEU 0.4.7]
    ├── DATABASE_STRUCTURE.md      # Veraltete DB-Doku (historisch)
    ├── STRUCTURE.md               # Diese Datei - Plugin-Struktur [AKTUALISIERT 0.4.7]
    ├── TEST_PLAN.md               # Test-Szenarien
    ├── ROLLEN-UEBERSICHT.md       # Rollen & Capabilities
    ├── DIENST_ZEITFENSTER.md      # Zeit-Handling Dokumentation
    ├── TAG_ASSIGNMENT_BUG_FIX.md  # Tag-Zuweisungs-Bug Fix
    ├── CSS_COMPONENTS.md          # CSS-Komponenten
    └── CSS_STANDARDISIERUNG_SUMMARY.md  # CSS-Standards
```

## 🏗️ Klassen-Hierarchie

### Core-Klassen (includes/)

1. **Dienstplan_Verwaltung** (class-dienstplan-verwaltung.php)
   - Haupt-Plugin-Klasse
   - Orchestriert alle Komponenten
   - Registriert Admin/Public Hooks

2. **Dienstplan_Loader** (class-loader.php)
   - Verwaltet WordPress Actions/Filters
   - Zentrale Hook-Registrierung

3. **Dienstplan_i18n** (class-i18n.php)
   - Lädt Übersetzungen
   - Text-Domain Verwaltung

4. **Dienstplan_Database** (class-database.php)
   - Datenbank-Operationen für 13 Tabellen
   - CRUD-Methoden für alle Entitäten
   - Migrations-Management (inkl. mitarbeiter_id Migration)
   - Zeit-Validierung (Overnight-Dienste)
   - Prepared Statements für Sicherheit

5. **Dienstplan_Activator** (class-activator.php)
   - Wird bei Aktivierung ausgeführt
   - DB-Tabellen erstellen
   - Default-Optionen setzen

6. **Dienstplan_Deactivator** (class-deactivator.php)
   - Wird bei Deaktivierung ausgeführt
   - Aufräumarbeiten

### Admin-Klassen (admin/)

1. **Dienstplan_Admin** (class-admin.php)
   - Admin-Menü registrieren (11 Seiten)
   - Assets laden (CSS/JS) - 7 JavaScript-Dateien
   - AJAX-Handler (50+ Endpunkte)
   - View-Rendering
   - Import/Export-Funktionen
   - Bulk-Aktionen (Dienste, Tätigkeiten)
   - Modal CRUD-Operationen

### Public-Klassen (public/)

1. **Dienstplan_Public** (class-public.php)
   - Frontend-Assets laden
   - Shortcodes registrieren ([dienstplan_veranstaltung], [meine_dienste])
   - Frontend-Funktionalität (Dienst-Anmeldung)
   - Split-Dienst-Logik (Dienste teilen)
   - AJAX-Handler für öffentliche Anmeldungen

## 📋 Namenskonventionen

### Klassen
- Format: `Dienstplan_Class_Name`
- Datei: `class-class-name.php`
- Beispiel: `Dienstplan_Database` → `class-database.php`

### Hooks
- Actions: `dienstplan_action_name`
- Filters: `dienstplan_filter_name`
- AJAX: `wp_ajax_dp_action_name`

### Datenbank
- Tabellen: `wp_dp_table_name` (13 Tabellen total)
  - settings, vereine, veranstaltungen, veranstaltung_tage
  - bereiche, taetigkeiten, dienste, dienst_slots
  - mitarbeiter, dienst_zuweisungen, notifications
  - veranstaltung_vereine, verein_verantwortliche
- Optionen: `dienstplan_option_name`
- **Wichtige Felder:**
  - mitarbeiter: KEIN erstellt_am, KEIN rolle, KEIN aktiv
  - dienst_zuweisungen: eingetragen_am (nicht erstellt_am), kommentar (nicht bemerkung), status: bestaetigt
  - dienste: KEIN datum Feld (kommt von tag_id)
- **Dokumentation:** Siehe `documentation/DATABASE_STRUCTURE_AKTUELL.md`

### CSS-Klassen
- Format: `dienstplan-element-modifier`
- Beispiel: `dienstplan-card`, `dienstplan-card--active`

### JavaScript
- Namespace: `dpAdmin`, `dpPublic`
- Funktionen: `camelCase`
- Globale Funktionen: `window.functionName` für Modal-Operationen
- IIFE Pattern für Kapselung: `(function($) { ... })(jQuery)`
- **Admin-Scripts (7 Dateien):**
  - dp-admin.js (Base: Dropdown, Collapse)
  - dp-admin-modals.js (1000+ Zeilen: Alle CRUD-Operationen)
  - dp-dienst-modal.js (Dienst-spezifisch)
  - dp-dienste-table.js (Bulk-Aktionen)
  - dp-bulk-update-modals.js (Bulk-Dialoge)
  - dp-besetzung-modal.js (Slot-Zuweisung)
  - (Mitarbeiter-Funktionen in mitarbeiter-modal.php)

## 🔄 Datenfluss

### Admin-Seite laden
```
1. dienstplan-verwaltung.php
2. includes/class-dienstplan-verwaltung.php
3. includes/class-loader.php registriert Hooks
4. admin/class-admin.php
5. admin/views/dashboard.php (Template)
6. assets/css/admin.css & assets/js/admin.js geladen
```

### AJAX-Request
```
1. assets/js/admin.js sendet Request
2. admin/class-admin.php → ajax_*() Methode
3. includes/class-database.php für DB-Operationen
4. JSON-Response zurück
```

### Shortcode-Rendering
```
1. [dienstplan] im Post/Page
2. public/class-public.php → shortcode_dienstplan()
3. public/views/dienstplan-display.php
4. assets/css/public.css & assets/js/public.js geladen
```

## 🛠️ Entwickler-Hinweise

### Neue Admin-Seite hinzufügen
1. Submenu in `admin/class-admin.php` → `add_menu()`
2. Display-Methode erstellen
3. View-Template in `admin/views/` erstellen
4. Optional: Partials in `admin/views/partials/`

### Neuen Shortcode hinzufügen
1. In `public/class-public.php` → `register_shortcodes()`
2. Shortcode-Methode erstellen
3. View-Template in `public/views/` erstellen

### AJAX-Endpunkt hinzufügen
1. Hook in `includes/class-dienstplan-verwaltung.php` → `define_admin_hooks()`
   ```php
   $this->loader->add_action('wp_ajax_dp_your_action', $plugin_admin, 'ajax_your_action');
   ```
2. Handler in `admin/class-admin.php` → `ajax_your_action()` Methode
   ```php
   public function ajax_your_action() {
       check_ajax_referer('dienstplan-nonce', 'nonce');
       // Logik hier
       wp_send_json_success($data);
   }
   ```
3. JavaScript in passender Datei (dp-admin-modals.js für CRUD)
   ```javascript
   jQuery.post(ajaxurl, {
       action: 'dp_your_action',
       nonce: dpAjax.nonce,
       data: yourData
   }, function(response) {
       if (response.success) {
           alert('Erfolg');
           location.reload();
       }
   });
   ```

### Datenbank-Tabelle hinzufügen
1. In `includes/class-database.php` → `install()` Methode
2. CRUD-Methoden hinzufügen
3. DB-Version in Hauptdatei erhöhen

## ✅ Best Practices

- ✓ Alle Klassen mit Autoloader
- ✓ Views getrennt von Logik
- ✓ Partials für wiederverwendbare Komponenten
- ✓ Nonce-Validierung für alle AJAX/Forms
- ✓ Escaping für alle Ausgaben (esc_html, esc_attr)
- ✓ Übersetzbar mit i18n (__(), _e())
- ✓ Versionierte Assets (Cache-Busting)
- ✓ Separate Admin/Public Assets
- ✓ Hook-Loader für zentrale Verwaltung
- ✓ WordPress Coding Standards
- ✓ JavaScript ausgelagert (keine Inline-Scripts)
- ✓ IIFE Pattern für JavaScript-Kapselung
- ✓ Prepared Statements für DB-Queries
- ✓ Migrations für Schema-Änderungen
- ✓ Umfassende Dokumentation (DATABASE_STRUCTURE_AKTUELL.md)

## 🔒 Sicherheit

- **Nonce-Prüfung:** Alle AJAX-Calls haben `check_ajax_referer()`
- **Capability-Checks:** Alle Admin-Funktionen prüfen Berechtigungen
- **Prepared Statements:** Keine direkten SQL-Queries mit Variablen
- **Input-Sanitization:** `sanitize_text_field()`, `sanitize_email()`, `intval()`
- **Output-Escaping:** `esc_html()`, `esc_attr()`, `esc_url()`
- **CSRF-Schutz:** WordPress Nonces für alle Forms

## 📊 Performance

- **Asset-Loading:** Scripts nur auf relevanten Seiten laden
- **Database-Indizes:** Auf häufig genutzte Spalten gesetzt
- **Prepared Statements:** Cachen Query-Plans
- **Lazy Loading:** Mitarbeiter-Daten in Übersicht bei Bedarf
- **Cache-Busting:** Versionierte Assets (plugin_version)
- **Script-Platzierung:** Footer-Loading für bessere Page-Speed

---

**Entwickelt für Vereinsring Wasserlos e.V.**  
**WordPress Plugin Boilerplate nach aktuellen Standards**

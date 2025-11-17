# Elementor-Integration Roadmap

## Status: In Planung
**Aktuelle Version:** 0.9.5.3 (CSS-Fixes only)  
**Ziel-Version:** 0.6.0 (Vollständige Elementor-Integration)

---

## 🎯 Vision

Vollständige Elementor-Integration mit nativen Widgets, die sich nahtlos in den Elementor-Workflow einfügen.

---

## 📊 Komplexitätsanalyse

### Aktueller Stand (0.9.5.3)
- ✅ CSS-Overrides für Layout-Konflikte
- ✅ Z-Index-Fixes für Modals
- ❌ Keine nativen Elementor-Widgets
- ❌ Keine Elementor-Controls
- ❌ Nur Shortcode-basiert

### Probleme des Shortcode-Ansatzes
1. **Keine Live-Bearbeitung** im Elementor-Editor
2. **Keine visuellen Controls** (Farben, Spacing, Typography)
3. **Keine Responsive-Einstellungen** über Elementor
4. **Keine Template-Integration** (Elementor Theme Builder)
5. **Begrenzte Styling-Optionen** (nur über Plugin-CSS)

---

## 🏗️ Architektur-Plan

### Phase 1: Foundation (Version 0.6.0) - **4-6 Wochen**

#### 1.1 Elementor Widget-Basis
```php
includes/elementor/
├── class-elementor-integration.php     // Haupt-Integration
├── widgets/
│   ├── class-base-widget.php           // Basis-Widget-Klasse
│   └── class-veranstaltungen-widget.php // Erstes Widget
└── controls/
    └── class-custom-controls.php        // Custom Controls
```

**Aufgaben:**
- [ ] Elementor SDK integrieren
- [ ] Widget-Registrierung implementieren
- [ ] Basis-Widget mit Controls erstellen
- [ ] Icon-Set für Widgets definieren
- [ ] Widget-Kategorie "Dienstplan" erstellen

**Geschätzter Aufwand:** 1 Woche

---

#### 1.2 Erstes Widget: Veranstaltungs-Liste

**Controls (Elementor-Sidebar):**
- **Inhalt-Tab:**
  - Anzahl der Veranstaltungen (Number)
  - Status-Filter (Select: Alle, Geplant, Aktiv, Abgeschlossen)
  - Sortierung (Select: Datum, Name, Status)
  - Link zur Detail-Seite (URL-Control)

- **Stil-Tab:**
  - Karten-Hintergrund (Color)
  - Karten-Border (Border)
  - Karten-Schatten (Box Shadow)
  - Spacing (Dimensions)
  - Typography (Heading, Text)

- **Erweitert-Tab:**
  - CSS-ID
  - CSS-Klassen
  - Custom CSS

**Aufgaben:**
- [ ] Widget-PHP-Klasse erstellen
- [ ] Controls definieren
- [ ] Template-Rendering implementieren
- [ ] Live-Preview im Editor
- [ ] Responsive Settings

**Geschätzter Aufwand:** 1,5 Wochen

---

#### 1.3 Zweites Widget: Veranstaltungs-Detail

**Controls:**
- **Inhalt:**
  - Veranstaltungs-ID (Dynamische Auswahl)
  - Verein-Filter aktivieren (Switcher)
  - Tage-Ansicht (Tabs, Timeline, Kalender)
  - "Jetzt eintragen" Button-Text

- **Stil:**
  - Tag-Header-Farbe
  - Dienst-Karten-Layout
  - Slot-Button-Styles
  - Modal-Styles

**Aufgaben:**
- [ ] Widget erstellen
- [ ] Dynamische Veranstaltungs-Auswahl
- [ ] View-Mode Switcher
- [ ] Style-Optionen implementieren

**Geschätzter Aufwand:** 2 Wochen

---

### Phase 2: Advanced Features (Version 0.7.0) - **3-4 Wochen**

#### 2.1 Template-System

**Elementor Templates verwenden:**
```php
// Custom Template für Dienst-Karte
includes/elementor/templates/
├── dienst-card-template.php
├── slot-template.php
└── modal-template.php
```

**Features:**
- [ ] Template-Überrides im Theme
- [ ] Custom Post Type "dp_template"
- [ ] Template-Library Integration
- [ ] Vordefinierte Design-Templates

**Geschätzter Aufwand:** 1,5 Wochen

---

#### 2.2 Dynamic Tags

**Elementor Dynamic Tags für Dienstplan-Daten:**
- `{veranstaltung_name}`
- `{veranstaltung_datum}`
- `{dienst_zeit}`
- `{freie_slots}`
- `{verein_name}`

**Aufgaben:**
- [ ] Dynamic Tag Provider erstellen
- [ ] Tags registrieren
- [ ] ACF-ähnliche Daten-Bindung

**Geschätzter Aufwand:** 1 Woche

---

#### 2.3 Theme Builder Integration

**Elementor Theme Builder Templates:**
- Single Veranstaltung Template
- Veranstaltungs-Archiv
- 404 für nicht gefundene Dienste

**Aufgaben:**
- [ ] Custom Post Type für Veranstaltungen (optional)
- [ ] Template-Conditions registrieren
- [ ] Preview-Modus implementieren

**Geschätzter Aufwand:** 1,5 Wochen

---

### Phase 3: Pro Features (Version 0.8.0) - **2-3 Wochen**

#### 3.1 Popup-Integration

**Elementor Popup für:**
- Vereins-Auswahl
- Dienst-Eintragung
- Mitarbeiter-Details

**Aufgaben:**
- [ ] Popup-Trigger in Widgets
- [ ] Dynamic Popup Content
- [ ] Popup-Templates

**Geschätzter Aufwand:** 1 Woche

---

#### 3.2 Loop-Grid Integration

**Elementor Loop-Grid für:**
- Veranstaltungs-Liste
- Dienst-Übersicht
- Mitarbeiter-Galerie

**Aufgaben:**
- [ ] Query-Provider implementieren
- [ ] Skin-Templates erstellen
- [ ] Filter-Integration

**Geschätzter Aufwand:** 1,5 Wochen

---

### Phase 4: Polish & Performance (Version 0.9.0) - **2 Wochen**

#### 4.1 Performance-Optimierung

- [ ] Lazy Loading für Widgets
- [ ] CSS/JS nur bei Bedarf laden
- [ ] Cache-Integration
- [ ] DB-Query-Optimierung

**Geschätzter Aufwand:** 1 Woche

---

#### 4.2 Testing & Dokumentation

- [ ] Widget-Showcase-Seite
- [ ] Video-Tutorials
- [ ] Elementor-Starter-Templates
- [ ] Compatibility-Testing

**Geschätzter Aufwand:** 1 Woche

---

## 📋 Technische Anforderungen

### Code-Struktur
```
includes/elementor/
├── class-elementor-integration.php
├── widgets/
│   ├── class-base-widget.php
│   ├── class-veranstaltungen-liste.php
│   ├── class-veranstaltung-detail.php
│   ├── class-dienst-kalender.php
│   └── class-mitarbeiter-grid.php
├── controls/
│   ├── class-veranstaltung-select.php
│   └── class-verein-select.php
├── dynamic-tags/
│   └── class-dienstplan-tags.php
└── traits/
    └── trait-ajax-handler.php
```

### Elementor-API Integration
```php
// Haupt-Integration
class Dienstplan_Elementor_Integration {
    public function __construct() {
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        add_action('elementor/controls/register', [$this, 'register_controls']);
        add_action('elementor/dynamic_tags/register', [$this, 'register_tags']);
    }
}
```

### Widget-Beispiel
```php
class Dienstplan_Veranstaltungen_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'dienstplan-veranstaltungen';
    }
    
    public function get_title() {
        return __('Veranstaltungen', 'dienstplan-verwaltung');
    }
    
    public function get_icon() {
        return 'eicon-calendar';
    }
    
    public function get_categories() {
        return ['dienstplan'];
    }
    
    protected function register_controls() {
        // Content Tab
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Inhalt', 'dienstplan-verwaltung'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'posts_per_page',
            [
                'label' => __('Anzahl', 'dienstplan-verwaltung'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 6,
            ]
        );
        
        $this->end_controls_section();
        
        // Style Tab
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Stil', 'dienstplan-verwaltung'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'card_bg_color',
            [
                'label' => __('Hintergrundfarbe', 'dienstplan-verwaltung'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .dp-event-card' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Lade Veranstaltungen
        $db = new Dienstplan_Database(DIENSTPLAN_DB_PREFIX);
        $veranstaltungen = $db->get_veranstaltungen($settings['posts_per_page']);
        
        // Render Template
        ?>
        <div class="dp-elementor-widget dp-veranstaltungen-grid">
            <?php foreach ($veranstaltungen as $event): ?>
                <div class="dp-event-card">
                    <!-- Card Content -->
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
```

---

## 🚀 Migrations-Plan: 0.9.5.3 → 0.6.0

### Warum Version-Downgrade?

**Semantic Versioning:**
- **0.9.x** = Feature-Complete Beta (fast fertig)
- **0.6.x** = Major Feature in Development

**Aktuell:**
- 0.9.5.3 suggeriert: "Fast 1.0, nur noch Bugfixes"
- **Realität:** Elementor-Integration ist ein Major-Feature (20% des Projekts)

### Vorschlag für neue Versionierung:

```
0.5.x - Basis-Funktionalität (✅ Erledigt)
0.6.0 - Elementor Foundation (Phase 1) ← Start hier
0.7.0 - Elementor Advanced (Phase 2)
0.8.0 - Elementor Pro Features (Phase 3)
0.9.0 - Performance & Polish (Phase 4)
1.0.0 - Production Ready
```

### Migrations-Schritte:

1. **Version 0.5.9 erstellen** (aktueller Stand)
   - Alle Bugfixes von 0.9.5.0 - 0.9.5.3
   - OHNE Elementor-Integration-Versprechen
   - Changelog: "Basis-Funktionalität komplett"

2. **Branch erstellen:** `feature/elementor-integration`

3. **Version 0.6.0-alpha.1 starten**
   - Neue Ordnerstruktur
   - Elementor SDK integrieren
   - Erstes Widget (Proof of Concept)

---

## 💰 Aufwands-Schätzung

### Gesamt-Übersicht

| Phase | Aufwand | Komplexität | Wert |
|-------|---------|-------------|------|
| Phase 1: Foundation | 4-6 Wochen | Hoch | Kritisch |
| Phase 2: Advanced | 3-4 Wochen | Mittel | Hoch |
| Phase 3: Pro Features | 2-3 Wochen | Mittel | Optional |
| Phase 4: Polish | 2 Wochen | Niedrig | Wichtig |
| **GESAMT** | **11-15 Wochen** | - | - |

### Entwickler-Ressourcen

**Benötigt:**
- 1x Senior PHP/WordPress-Entwickler (Elementor-Erfahrung)
- 0.5x UI/UX Designer (Widget-Templates)
- 0.25x Tester (verschiedene Elementor-Setups)

**Kosten-Schätzung (Freiberufler):**
- 11-15 Wochen × 40h = 440-600 Stunden
- 440-600h × 80€/h = **35.200€ - 48.000€**

---

## 🎯 Alternative: Minimalistische Lösung

### "Good Enough" Ansatz (Version 0.5.10)

**Statt voller Integration:**
1. ✅ CSS-Fixes behalten (bereits erledigt)
2. ✅ Shortcode optimieren für Elementor
3. ✅ Elementor-Control für Shortcode-Parameter
4. ❌ KEINE nativen Widgets

**Vorteile:**
- ⚡ Schnell (1-2 Tage)
- 💰 Günstig
- ✅ Funktioniert mit allen Page-Buildern

**Nachteile:**
- ❌ Kein Live-Editing
- ❌ Keine visuellen Controls
- ❌ Keine Template-Integration

**Aufwand:** 1-2 Tage (2.000€ - 4.000€)

---

## 📊 Entscheidungs-Matrix

| Kriterium | Volle Integration | Minimalistisch |
|-----------|-------------------|----------------|
| Entwicklungszeit | 11-15 Wochen | 1-2 Tage |
| Kosten | 35.000-48.000€ | 2.000-4.000€ |
| User Experience | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| Wartbarkeit | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Flexibilität | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| Page-Builder Support | Nur Elementor | Alle |

---

## 🎯 Empfehlung

### Option A: Minimalistisch (Version 0.5.10)
**Für:** Schneller Launch, Budget-begrenzt, Multi-Builder-Support
**Aufwand:** 1-2 Tage
**Ergebnis:** Stabile Basis, funktioniert mit Elementor & anderen Buildern

### Option B: Volle Integration (Version 0.6.0+)
**Für:** Premium-Produkt, Elementor-fokussiert, beste UX
**Aufwand:** 11-15 Wochen
**Ergebnis:** Native Elementor-Integration, Marktdifferenzierung

### Option C: Hybrid (Version 0.6.0 Light)
**Für:** Balance zwischen Zeit und Features
**Aufgaben:**
- Phase 1 (Foundation) → 4-6 Wochen
- STOPP bei 2 funktionalen Widgets
- Später erweitern wenn Bedarf besteht

**Aufwand:** 4-6 Wochen (7.000€ - 10.000€)

---

## 🤝 Nächste Schritte

1. **Entscheidung:** Welche Option?
2. **Version:** Auf 0.5.9 zurück oder bei 0.9.x bleiben?
3. **Roadmap:** Zeitplan festlegen
4. **Team:** Ressourcen planen

---

## 📝 Notizen

- Elementor hat 5+ Mio. aktive Installationen
- Native Widgets = Marketing-Vorteil
- Shortcode-Ansatz = Robuster & einfacher
- Hybrid könnte "Best of Both" sein

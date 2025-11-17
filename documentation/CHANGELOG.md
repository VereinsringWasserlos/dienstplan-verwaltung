# Changelog

Alle wichtigen Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0/0/),
und dieses Projekt folgt [Semantic Versioning](https://semver.org/lang/de/).

---

## [0.5.9] - 2025-11-17 🎉 Stable Base - Basis-Funktionalität komplett

**Konsolidierung aller Features und Bugfixes** aus 0.9.x als stabile Basis-Version.

### ✅ Kern-Funktionalität

#### CRUD-Verwaltung
- ✅ **Veranstaltungen:** Erstellen, Bearbeiten, Löschen (Eintägig & Mehrtägig)
- ✅ **Dienste:** Zeitfenster, Besetzung, Slots-System
- ✅ **Mitarbeiter:** Kontaktverwaltung, Dienst-Zuweisung
- ✅ **Vereine:** Logo-Upload, Verantwortliche, WordPress-User-Integration
- ✅ **Bereiche & Tätigkeiten:** Kategorisierung mit Farben

#### Frontend
- ✅ **Veranstaltungs-Listen:** Card-Grid-Layout (Xoyondo-inspiriert)
- ✅ **Veranstaltungs-Detail:** Tage-Ansicht mit Dienst-Slots
- ✅ **Vereins-Auswahl:** Modal mit Statistiken
- ✅ **Dienst-Eintragung:** Selbst-Registrierung für Mitglieder
- ✅ **Responsive Design:** Mobile-optimiert

#### Shortcodes
- `[dienstplan]` - Veranstaltungs-Detail mit Diensten
- `[veranstaltungen_liste]` - Grid-Ansicht aller Veranstaltungen

### 🔧 Bugfixes (konsolidiert aus 0.9.5.x)

#### 0.9.5.0: Frontend & Vereins-Auswahl
- **CSS robuster:** !important-Regeln gegen Theme-Konflikte
- **Modal z-index:** 9.999.999 für bessere Sichtbarkeit
- **3-stufiger Fallback:** Vereins-Auswahl auch ohne Dienste
- **Box-sizing Fixes:** Container-Layout-Probleme behoben

#### 0.9.5.1: Veranstaltungen - Neuer Mitarbeiter
- **Button "Neuer Mitarbeiter":** Öffnet Mitarbeiter-Modal statt Kontakt-Modal
- **Intelligentes Reload:** Nur Verantwortlichen-Liste wird neu geladen
- **Veranstaltungs-Modal:** Bleibt offen beim Hinzufügen

#### 0.9.5.2: Vereins-Fallback
- **3-stufige Prüfung:** Explizit zugewiesen → Mit Diensten → Alle aktiven
- **Fix:** "Keine Vereine verfügbar" tritt nicht mehr auf

#### 0.9.5.3: Elementor-Basis-Kompatibilität
- **CSS-Overrides:** Elementor-spezifische Selektoren
- **Container-Fixes:** Width/Padding für Elementor-Sections
- **Z-Index:** Modal über Elementor-Popups
- **Grid-Overrides:** Verhindert Flexbox-Konflikte

#### 0.9.5.4: Vereine - Neuer Kontakt Modal
- **Button vereinfacht:** Direkter Aufruf von openNewContactModal
- **JavaScript verstärkt:** Mehrere Display-Methoden
- **CSS-Fixes:** Spezifische Regeln für #new-contact-modal

### 📋 Technische Features

#### Safe Reload System
- **dpSafeReload():** 3 Sekunden Delay, Modal-Detection
- **38 location.reload()** durch sichere Version ersetzt
- **Pending Reload:** Automatischer Reload beim Modal-Schließen

#### Rollen-System
- **WordPress-Integration:** Custom Capabilities
- **3 Rollen:** Club Admin, Event Admin, General Admin
- **Berechtigungen:** Granulare Zugriffskontrolle

#### Update-Mechanismus
- **Git-basiert:** Für Entwicklung
- **WordPress-Standard:** Für Produktionsserver
- **Auto-Detection:** Prüft Git-Verfügbarkeit

### 🚧 Bekannte Limitierungen

#### Page-Builder-Integration
- ⚠️ **Nur Shortcode-basiert** (kein natives Elementor-Widget)
- ⚠️ **Kein Live-Editing** im Page-Builder
- ⚠️ **Eingeschränkte Styling-Optionen** über Elementor-Controls
- ✅ **CSS-Kompatibilität:** Elementor, Divi, Gutenberg funktionieren

#### Frontend-Einschränkungen
- ⚠️ Keine Timeline-Ansicht
- ⚠️ Keine Kalender-Integration
- ⚠️ Keine PDF-Export-Funktion
- ⚠️ Keine E-Mail-Benachrichtigungen an Mitarbeiter

### 🔮 Roadmap - Nächste Versionen

```
0.5.9  ✅ Stable Base (AKTUELL)
       └─ Basis-Features komplett
       └─ Shortcode-basiert  
       └─ CSS-Fixes für Page-Builder

0.6.0  🚧 Elementor Foundation (geplant)
       ├─ Native Elementor-Widgets
       ├─ Live-Editing im Editor
       └─ Visual Controls

0.7.0  📅 Advanced Features (geplant)
       ├─ Template-System
       ├─ Dynamic Tags
       └─ Theme Builder

0.8.0  🎨 Pro Features (optional)
       ├─ Popup-Integration
       └─ Loop-Grid

0.9.0  ⚡ Performance & Polish (geplant)
       ├─ Lazy Loading
       └─ Cache-Optimierung

1.0.0  🎉 Production Ready (Ziel)
```

### 📖 Dokumentation

- **ELEMENTOR_ROADMAP.md:** Detaillierte Elementor-Integration-Planung
- **VERSION_STRATEGY.md:** Versionierungs-Strategie & Empfehlungen
- **DATABASE_STRUCTURE.md:** Datenbank-Schema
- **ROLLEN-UEBERSICHT.md:** Berechtigungskonzept

### ⚠️ Wichtige Hinweise

#### Versionierungs-Änderung
**WICHTIG:** Diese Version springt von 0.9.5.4 zurück auf 0.5.9!

**Grund:** 
- Version 0.9.x suggerierte "fast fertig" (90% complete)
- Realität: Elementor-Integration fehlt komplett
- 0.5.9 = ehrlichere Einschätzung des Entwicklungsstands

**Keine Breaking Changes:**
- Alle Features aus 0.9.5.x sind enthalten
- Datenbank-Schema unverändert
- API-kompatibel

#### Migration von 0.9.5.x
Kein Action erforderlich - einfach aktualisieren. Alle Daten bleiben erhalten.

---

## Versions-Historie 0.9.5.x (konsolidiert)

### [0.9.5.4] - 2025-11-17 🔧 Fix: Neuer Kontakt Modal (Vereine)

**Bugfix:** "Neuer Kontakt" Button im Vereine-Modal öffnet jetzt korrekt das Modal.

### 🔧 Bugfixes

#### Vereine-Modal: Neuer Kontakt
- **Button vereinfacht:** Entfernt komplexe inline-Logik
- **Modal-Display:** CSS-Regeln für `display: flex` hinzugefügt
- **JavaScript verstärkt:** Mehrere Display-Methoden für Kompatibilität
- **Debug-Logging:** Console-Logs zur Fehlersuche hinzugefügt

#### CSS-Fixes
- `#new-contact-modal` spezifische Styles
- `!important` auf display/visibility/opacity
- Fallback-Regeln für alle `.dp-modal` Elemente

### 📋 Technisches

#### Modal-Öffnung
- Direkter DOM-Zugriff + jQuery Fallback
- Styles: `display: flex`, `visibility: visible`, `opacity: 1`
- Z-Index: 100.000 (Admin-Bereich)

#### Betroffene Dateien
- `admin/views/partials/vereine-modal.php`
- `assets/js/dp-vereine-modal.js`
- `assets/css/dp-admin.css`

---

## [0.9.5.3] - 2025-11-17 ⚡ Elementor-Kompatibilität

**Umfassende Elementor-Kompatibilität** für Frontend-Darstellung ohne Layout-Konflikte.

### ⚡ Neu

#### Elementor Page Builder Unterstützung
- **Elementor-spezifische CSS-Overrides:** Verhindert Layout-Konflikte
- **Höherer z-index:** Modals (9.999.999) über Elementor-Popups (10.000)
- **Container-Fixes:** Width/Padding-Anpassungen für Elementor-Sections
- **Grid-Overrides:** Verhindert Elementor-Flexbox-Konflikte mit unseren Grids
- **Typography Reset:** Schriften werden nicht von Elementor überschrieben
- **Button-Styles:** Elementor-Button-Styles werden isoliert

### 🔧 Verbesserungen

#### CSS-Robustheit
- **!important auf kritischen Styles:** Grid, Display, Width, Z-Index
- **Box-sizing Override:** Auch für ::before und ::after Pseudo-Elemente
- **Background-Fixes:** Karten behalten weißen Hintergrund
- **Spacing-Isolation:** Elementor-Spacing beeinflusst Plugin nicht mehr

#### Editor-Modus
- **Elementor Editor:** Z-Index noch höher (99.999.999) im Editor-Modus
- **Live-Preview:** Funktioniert korrekt in Elementor-Vorschau

### 📋 Technisches

#### CSS-Selektoren
- `.elementor .dp-public-container` - Container in Elementor
- `.elementor-section .dp-events-grid` - Grid-Overrides
- `.elementor-popup-modal` - Z-Index niedriger als unsere Modals
- `.elementor-editor-active .dp-modal` - Extra-hoher Z-Index im Editor

#### Kompatibilität
- ✅ Elementor Free
- ✅ Elementor Pro
- ✅ Elementor Flexbox Container
- ✅ Elementor Grid Container
- ✅ Elementor Popups

---

## [0.9.5.2] - 2025-11-17 🔧 Vereins-Auswahl Fallback

**Bugfix:** "Keine Vereine verfügbar" wird nicht mehr angezeigt, auch wenn keine Dienste angelegt sind.

### 🔧 Bugfixes

#### Vereins-Auswahl im Frontend
- **3-stufiger Fallback:**
  1. Explizit zugewiesene Vereine (`veranstaltung_vereine` Tabelle)
  2. Vereine mit Diensten in der Veranstaltung
  3. **NEU:** Alle aktiven Vereine (wenn keine Dienste angelegt)
- **Fix:** "Keine Vereine verfügbar" tritt nicht mehr auf
- **Bessere UX:** Benutzer können sich auch ohne angelegte Dienste für einen Verein eintragen

### 📋 Technisches

#### SQL-Abfrage
- Fallback auf alle aktiven Vereine wenn keine Dienste vorhanden
- Sortierung nach Vereinsname (alphabetisch)

---

## [0.9.5.1] - 2025-11-17 🔧 Neuer Kontakt bei Veranstaltungen

**Bugfix:** "Neuer Kontakt" Button bei Veranstaltungen funktioniert jetzt korrekt.

### 🔧 Bugfixes

#### Veranstaltungs-Verantwortliche
- **Button "Neuer Mitarbeiter":** Öffnet jetzt das Mitarbeiter-Modal statt des Kontakt-Modals
- **Intelligentes Reload:** Nach dem Erstellen eines Mitarbeiters wird nur die Verantwortlichen-Liste neu geladen, nicht die ganze Seite
- **Bessere UX:** Veranstaltungs-Modal bleibt offen beim Hinzufügen neuer Mitarbeiter
- **Neue Funktion:** `reloadVerantwortlicheList()` für gezieltes Neuladen der Liste

### 📋 Technisches

#### JavaScript-Änderungen
- `dp-veranstaltungen-modal.js`: Neue Funktion `reloadVerantwortlicheList()`
- `dp-mitarbeiter-modal.js`: Flag-basierte Erkennung ob Veranstaltungs-Modal offen
- Smart Reload: Nur Reload wenn nicht aus Veranstaltung heraus aufgerufen

---

## [0.9.5.0] - 2025-11-17 🔧 Bugfixes & 4-stellige Versionierung

**Wichtige Bugfixes** für Frontend-Display und Vereins-Auswahl.

### 🔧 Bugfixes

#### Frontend-Darstellung
- **CSS robuster gemacht:** !important-Regeln gegen Theme-Konflikte
- **Modal-Display:** Höherer z-index (999999) für bessere Sichtbarkeit
- **Container:** Box-sizing und Layout-Fixes für Theme-Kompatibilität
- **Modal-Visibility:** Explizite Regel für display: flex

#### Vereins-Auswahl
- **Fallback-Logik:** Wenn keine Vereine explizit zugewiesen sind, werden automatisch alle Vereine angezeigt, die Dienste in der Veranstaltung haben
- **Fix:** "Keine Vereine verfügbar" wird nicht mehr fälschlicherweise angezeigt

### 📋 Technisches

#### Versionierung
- **4-stellige Versionsnummern:** Umstellung auf MAJOR.MINOR.PATCH.BUILD Format
- **Semantic Versioning 2.0:** Konform mit WordPress Best Practices

---

## [0.9.5] - 2025-11-17 🔄 Reload beim Modal-Schließen

**Pending Reload System** - Seite aktualisiert sich automatisch beim Schließen von Modals nach Änderungen.

### ✨ Neu

#### 🔄 Automatischer Reload beim Modal-Schließen
- **Neues System:** Wenn Reload unterdrückt wurde (Modal offen), wird er beim Schließen ausgeführt
- **`dpCheckPendingReload()`** - Prüft und führt ausstehenden Reload aus
- **Flag:** `window.dpReloadPending` merkt sich unterdrückte Reloads
- **Integriert in alle Modal-Close-Funktionen:**
  - `closeDienstModal()`
  - `closeBesetzungModal()`
  - `closeMitarbeiterModal()`
  - `closeVeranstaltungModal()`
  - `closeVereinModal()`

### 🔧 Verbesserungen

#### User Experience
- **Automatische Aktualisierung:** Modal schließen → Seite lädt automatisch neu
- **Keine manuelle Aktualisierung mehr nötig**
- **Zeitsparend:** Änderungen sind sofort sichtbar nach Modal-Schließen
- **Konsistent:** Funktioniert für alle Modal-Typen

#### Verhalten
1. Änderung in Modal speichern → `dpSafeReload()` wird aufgerufen
2. Modal ist noch offen → Reload wird unterdrückt, `dpReloadPending = true`
3. User schließt Modal → `dpCheckPendingReload()` führt Reload aus
4. Seite zeigt aktuelle Daten

### 🐛 Bugfixes

#### Rekursiver Aufruf in dpSafeReload()
- **Problem:** `if(typeof dpSafeReload === "function") { dpSafeReload(); }` erzeugte Endlosschleife
- **Lösung:** Geändert zu `location.reload();`

### 📝 Technische Änderungen

#### dp-admin.js
```javascript
window.dpReloadPending = false;

window.dpSafeReload = function(delay) {
    // ... Modal-Checks ...
    if (!hasOpenModal) {
        location.reload();
    } else {
        window.dpReloadPending = true; // Merken!
    }
};

window.dpCheckPendingReload = function() {
    if (window.dpReloadPending) {
        window.dpReloadPending = false;
        location.reload();
    }
};
```

#### Modal-Close-Funktionen (5 Dateien)
- **dp-dienst-modal.js**
- **dp-besetzung-modal.js**
- **dp-mitarbeiter-modal.js**
- **dp-veranstaltungen-modal.js**
- **dp-vereine-modal.js**

Alle erweitert um:
```javascript
window.closeXxxModal = function() {
    $('#xxx-modal').hide();
    if(typeof dpCheckPendingReload === 'function') {
        dpCheckPendingReload();
    }
};
```

---

## [0.9.4] - 2025-11-17 🔧 Update-Mechanismus Fix

**Kritischer Fix** - Manuelle Updates auf Produktionsservern ohne Git funktionieren jetzt.

### 🐛 Bugfixes

#### Manuelles Update auf Produktionsservern
- **Problem:** `perform_update()` verlangte Git, auch auf Produktionsservern
- **Lösung:** Zeigt hilfreiche Meldung mit Anleitung für WordPress Plugin-Update
- **Meldung:** "Bitte nutzen Sie die WordPress Plugin-Verwaltung für Updates. Gehen Sie zu: Plugins → Installierte Plugins → Dienstplan Verwaltung → 'Jetzt aktualisieren'"

### 🔧 Verbesserungen

#### Update-Methoden
- `perform_update()` erkennt jetzt `$this->git_available`
- Entwicklungsumgebungen: Weiterhin Git-basiertes Update
- Produktionsserver: Verweis auf WordPress Standard-Update
- Verhindert irreführende "Git ist nicht verfügbar" Fehlermeldung

### 📝 Technische Änderungen

**class-updater.php:**
```php
public function perform_update() {
    if (!$this->git_available) {
        // Produktionsserver → WordPress Update nutzen
        return array(
            'success' => false, 
            'message' => 'Bitte nutzen Sie die WordPress Plugin-Verwaltung...'
        );
    }
    // Entwicklung → Git Pull
}
```

### 💡 Für Administratoren

**Update auf Produktionsservern:**
1. WordPress Admin → Plugins
2. "Dienstplan Verwaltung" finden
3. Auf "Jetzt aktualisieren" klicken
4. WordPress lädt automatisch von GitHub

**Update auf Entwicklungsservern:**
- Weiterhin über Admin → Updates → "Update durchführen" (Git Pull)
- Oder manuell: `git pull origin main`

---

## [0.9.3] - 2025-11-17 🎯 Smart Reload & UX-Verbesserungen

**Safe Page Reload** - Seiten-Reloads respektieren jetzt offene Modals und geben User Zeit zum Lesen.

### ✨ Neu

#### 🛡️ Safe Reload System
- **Zentrale `dpSafeReload()` Funktion** in `dp-admin.js`
- Prüft vor Reload auf offene Modals/Dialogs:
  - Inline-Style Modals (`.modal`, `.dialog`, `[role="dialog"]`)
  - jQuery UI Dialogs (`.ui-dialog:visible`)
  - Bootstrap Modals (`.modal.show`)
  - Custom Modal-Classes (`.dp-modal-open`)
- **Kein Reload mehr bei offenen Modals** - verhindert Datenverlust
- **Verzögertes Reload** - 3 Sekunden Standard für bessere Lesbarkeit von Erfolgsmeldungen

### 🔧 Verbesserungen

#### User Experience
- **38 Reload-Aufrufe optimiert** in 9 JavaScript-Dateien
- User hat Zeit, Erfolgsmeldungen zu lesen (3s statt sofort)
- Keine verlorenen Eingaben mehr in offenen Modals
- Konsistentes Reload-Verhalten auf allen Admin-Seiten
- Console-Log bei unterdrücktem Reload: "Reload unterdrückt: Modal ist geöffnet"

#### Betroffene Bereiche
- **Dienste-Verwaltung:** 6 Reloads → Safe Reload
- **Veranstaltungen:** 7 Reloads → Safe Reload
- **Bereiche & Tätigkeiten:** 8 Reloads → Safe Reload
- **Mitarbeiter:** 3 Reloads → Safe Reload
- **Vereine:** 2 Reloads → Safe Reload
- **Dienste-Tabelle:** 3 Reloads → Safe Reload
- **Admin-Modals:** 11 Reloads → Safe Reload

### 🐛 Bugfixes

#### Reload-Probleme
- Behebt: Seite lädt neu während Modal-Eingabe
- Behebt: Erfolgsmeldung verschwindet sofort (keine Lesezeit)
- Behebt: Form-Daten gehen verloren bei vorzeitigem Reload
- Behebt: Inkonsistentes Reload-Timing auf verschiedenen Seiten

### 📝 Technische Änderungen

#### JavaScript-Dateien (9)
- **dp-admin.js:** Neue `dpSafeReload()` Funktion mit Modal-Detection
- **dp-admin-modals.js:** 11x `location.reload()` → `dpSafeReload()`
- **dp-bereiche-taetigkeiten.js:** 8x ersetzt
- **dp-veranstaltungen-modal.js:** 7x ersetzt (inkl. Syntax-Fixes)
- **dp-dienst-modal.js:** 3x ersetzt
- **dp-dienste-table.js:** 3x ersetzt
- **dp-vereine-modal.js:** 2x ersetzt
- **dp-mitarbeiter-modal.js:** 2x ersetzt
- **dp-mitarbeiter.js:** 1x ersetzt

#### Fallback-Sicherheit
```javascript
if(typeof dpSafeReload === "function") { 
    dpSafeReload(delay); 
} else { 
    location.reload(); // Fallback
}
```

---

## [0.9.2] - 2025-11-17 🚀 Produktionsserver-Support

**GitHub API Fallback** - Plugin funktioniert jetzt auch auf Servern ohne Git-Installation.

### ✨ Neu

#### 🌐 Automatischer Update-Modus
- **Ohne Git (Produktion):** Nutzt GitHub Releases API für Updates
- **Mit Git (Entwicklung):** Weiterhin Git-basierte Updates
- Automatische Erkennung der Umgebung beim Plugin-Start
- Keine Git-Abhängigkeit mehr für normale WordPress-Installationen

### 🔧 Verbesserungen

#### Update-Verwaltung
- **Update-Seite zeigt aktiven Modus:** "Git (Entwicklung)" oder "GitHub API (Produktion)"
- Bessere Fehlermeldungen wenn Git nicht verfügbar
- Klare Information über Update-Quelle in der Admin-Oberfläche
- Keine störenden Git-Fehlermeldungen mehr auf Produktionsservern

#### GitHub API Integration
- Liest neueste Version aus GitHub Releases
- Lädt ZIP-Asset automatisch herunter
- Fallback auf Zipball-URL wenn kein Asset vorhanden
- Changelog aus Release-Notes

### 🐛 Bugfixes

#### Git-Status-Anzeige
- Behebt "Git ist nicht verfügbar" Warnung auf Produktionsservern
- Korrekte Anzeige des Update-Modus in Admin-Oberfläche
- Keine unnötigen Git-Befehle auf Servern ohne Git

### 📝 Technische Änderungen

#### class-updater.php
- Neue Methode: `get_update_info_from_github()` - Holt Updates von GitHub API
- Umbenannt: `get_update_info()` → `get_update_info_from_git()` (Git-spezifisch)
- `get_update_info()` wählt automatisch zwischen Git und GitHub API
- `$git_available` Flag wird beim Start gesetzt
- `get_git_status()` gibt jetzt `mode` zurück (Git/GitHub API)

#### create-release.ps1
- Liest Version jetzt dynamisch aus Plugin-Datei
- Kein manueller Parameter mehr nötig

---

## [0.9.1] - 2025-11-17 🎨 Frontend Timeline & Auto-Update

**Timeline-View Optimierung** - Services nebeneinander + Automatische Updates.

### ✨ Neu

#### 🎯 Auto-Update-Feature
- Checkbox in Update-Einstellungen: "Automatische Updates aktivieren"
- WordPress auto_update_plugin Filter integriert
- Plugin erscheint in Auto-Update-Spalte der Plugin-Liste
- Speichert Einstellung in: `dienstplan_auto_update_enabled`

#### 🎨 Frontend Timeline-View (KOMPLETT ÜBERARBEITET!)
- **Zeit-Slot-Gruppierung:** Services zur gleichen Zeit erscheinen nebeneinander
- **Grid-Layout:** CSS Grid mit fixierter linker Spalte (280px)
- **Scroll-Synchronisierung:** 
  - Horizontal: Header ↔ Grid
  - Vertikal: Left-Panel ↔ Grid
- **Linke Spalte:** Zeigt Zeit + Anzahl Dienste (z.B. "14:00 - 3 Dienste")
- **Responsive Design:** Mobile-optimiert mit reduzierten Breiten
- **286 Zeilen neues CSS** in dp-public.css

### 🔧 Verbesserungen

#### Git-Integration
- Automatische Git-Pfad-Erkennung für Windows
- Sucht in Standard-Pfaden: `C:\Program Files\Git\`
- Fallback auf System-PATH
- Keine manuelle Git-Konfiguration mehr nötig

#### Plugin-Basename
- Dynamisch via `plugin_basename(DIENSTPLAN_PLUGIN_FILE)`
- Funktioniert mit versionierten Ordnernamen (z.B. `dienstplan-verwaltung-0.9.1/`)
- Behebt Problem bei Updates über ZIP

### 🐛 Bugfixes

#### Timeline-View
- Services werden nicht mehr untereinander angezeigt
- Services zur gleichen Zeit erscheinen in einer Zeile
- `selected_verein` wird korrekt aus `available_vereine` geholt
- Dienste werden nach Verein-Auswahl angezeigt

#### Auto-Update-Spalte
- Plugin erscheint jetzt immer in Plugin-Liste (via `no_update[]`)
- Auto-Update-Spalte wird auch ohne verfügbares Update angezeigt

### 📦 WordPress-ZIP
- Forward Slashes (Unix-Style) statt Backslashes
- .NET System.IO.Compression API für präzise Pfad-Kontrolle
- Ordnername ohne Version: `dienstplan-verwaltung/`
- Dateiname ohne Version: `dienstplan-verwaltung.zip`
- Größe: 0.27 MB (89 Dateien)

---

## [0.9.0] - 2025-11-17 🚀 UAT Release

**User Acceptance Testing Release** - Bereit für produktive Tests mit echten Nutzern.

### ✨ Neu

#### 📚 Komplette Dokumentation
- **Backend-Bedienungsanleitung** (650+ Zeilen)
  - Schritt-für-Schritt-Anleitungen für alle Funktionen
  - Screenshots-Platzhalter für 16 Backend-Bereiche
  - FAQ und Problembehandlung
  - Tipps & Best Practices

- **Frontend-Bedienungsanleitung** (500+ Zeilen)
  - Anleitung für Crew-Mitglieder
  - Split-Dienste-Erklärung
  - Checkliste für ersten Dienst
  - Mobile-Nutzung-Tipps

- **Quick-Start Guide** (300+ Zeilen)
  - In 15 Minuten einsatzbereit
  - Typische Szenarien
  - Schnelle Fehlerbehebung

- **Screenshot-Anleitung** (SCREENSHOTS.md)
  - 25 definierte Screenshots (16 Backend + 9 Frontend)
  - Detaillierte Richtlinien
  - Tools-Empfehlungen

- **Dokumentations-Menüpunkt im Backend**
  - Zugriff über Dashboard → Administration → Dokumentation
  - Kategorisierte Sidebar (Einstieg / Anleitungen / Technisch)
  - Markdown-zu-HTML-Rendering
  - Download-Buttons für alle Dokumente

#### 🔧 Split-Dienst Slot-System (KOMPLETT ÜBERARBEITET!)
- **❌ Alt:** Split erstellt neue Dienste → ✅ Neu: Split passt Slots an
- **Slot-basierte Architektur:**
  - Dienst bleibt bestehen (keine Duplikate mehr!)
  - Slot 1 wird angepasst (erste Hälfte)
  - Slot 2 wird erstellt (zweite Hälfte)
  - Mitarbeiter wird gewähltem Slot zugewiesen

- **Neue Funktion:** `ensure_dienst_split()`
  - Prüft ob bereits gesplittet (2 Slots vorhanden)
  - Passt existierende Slots an
  - Berechnet automatisch Mitte-Zeit
  - Idempotent (kann mehrfach aufgerufen werden)

#### 🎯 Slot-Zuweisung mit Split-Support
- **Intelligente Slot-Auswahl:**
  - Bei Normal-Anmeldung: Ersten freien Slot finden
  - Bei Split-Anmeldung: Gewählten Slot (1 oder 2) finden
  - Fehlerbehandlung: "Erste Hälfte bereits besetzt"

- **Zwei-Tabellen-System:**
  - `dienst_slots`: Physische Plätze (mit von_zeit/bis_zeit)
  - `dienst_zuweisungen`: Anmeldungs-Historie

- **Rollback-Mechanismus:**
  - Bei Fehler: Slot wird automatisch wieder freigegeben
  - Atomare Transaktionen

#### 📧 Email optional bei Anmeldung
- **Temporäre Mitarbeiter-Accounts:**
  - Ohne Email: `temp_[timestamp]_[uniqueid]@dienstplan.local`
  - Ermöglicht spontane Anmeldungen
  - Keine Duplikat-Probleme

#### 🐛 Debugging & Logging
- **Erweiterte Fehlerberichte:**
  - `error_log('DP: Anmeldung für Dienst-ID: X')`
  - POST-Daten werden geloggt
  - Dienst-ID wird in Fehlermeldungen angezeigt

### 🔧 Verbessert

#### Datenbank-Konsistenz
- **Migrations-Script für mitarbeiter_id:**
  - Fügt fehlende Spalte zu `dienst_zuweisungen` hinzu
  - Kann manuell ausgeführt werden

- **Slot-Struktur erweitert:**
  - `mitarbeiter_id` Spalte in `dienst_slots`
  - `von_zeit`, `bis_zeit`, `bis_datum` für Split-Zeiten
  - `slot_nummer` (1, 2, 3, ...)

#### Admin-Oberfläche
- **Modal-Funktionen (dp-admin-modals.js):**
  - 1000+ Zeilen JavaScript
  - Alle CRUD-Operationen
  - Nested Modals für schnelles Erstellen
  - Bulk-Update Funktionen

- **Auto-Refresh optimiert:**
  - Intervall: 3 Sekunden (statt 1,5s)
  - Pausiert bei geöffneten Modals

#### Frontend-UX
- **Anmelde-Formular:**
  - Checkbox "Ich möchte den Dienst teilen"
  - Radio-Buttons für Zeitfenster-Auswahl
  - Validierung: Split-Auswahl erforderlich wenn Checkbox aktiv
  - Email optional
  - Telefon-Feld hinzugefügt

- **Fehlerbehandlung:**
  - Spezifische Fehlermeldungen
  - "Erste Hälfte bereits besetzt"
  - "Dienst nicht gefunden (ID: X)"

### 🐛 Behoben

#### ❌ Kritisch: Split-Dienst-Bug
- **Problem:** Split erstellt neue Dienste → Duplizierung
- **Lösung:** Slot-basiertes System → Keine Duplikate
- **Details:**
  ```php
  // ALT (FALSCH):
  $wpdb->insert($prefix . 'dienste', $teil1_data); // ❌
  $wpdb->insert($prefix . 'dienste', $teil2_data); // ❌
  $wpdb->delete($prefix . 'dienste', array('id' => $dienst->id)); // ❌
  
  // NEU (RICHTIG):
  $wpdb->update($prefix . 'dienst_slots', [...], array('id' => $slot1->id)); // ✅
  $wpdb->insert($prefix . 'dienst_slots', [...]);  // ✅
  ```

#### ❌ "Dienst nicht gefunden" Fehler
- **Problem:** Dienst-ID wurde nicht korrekt übergeben/gelesen
- **Lösung:**
  - Debug-Logging hinzugefügt
  - `dienst_id` wird aus `$_POST['dienst_id']` gelesen
  - Formular hat Hidden-Field `<input name="dienst_id" id="dpDienstId">`
  - JavaScript setzt Wert beim Modal-Öffnen

#### ❌ Slot-Zuweisung fehlte
- **Problem:** Mitarbeiter wird in `dienst_zuweisungen` eingetragen, aber Slot bleibt leer
- **Lösung:**
  ```php
  // Finde freien Slot
  $free_slot = $wpdb->get_row("SELECT * FROM {$prefix}dienst_slots 
                                WHERE dienst_id = %d 
                                AND mitarbeiter_id IS NULL");
  
  // Update Slot
  $wpdb->update($prefix . 'dienst_slots',
      array('mitarbeiter_id' => $mitarbeiter_id, 'status' => 'besetzt'),
      array('id' => $free_slot->id)
  );
  
  // Speichere Zuweisung
  $wpdb->insert($prefix . 'dienst_zuweisungen', [...]); 
  ```

#### Datenbank-Schema-Fehler (aus 0.4.7)
- Falsche Tabellennamen korrigiert
- Falsche Spaltennamen korrigiert
- Fehlende Spalten hinzugefügt
- Siehe DATABASE_STRUCTURE_AKTUELL.md für Details

### 🗑️ Entfernt
- Alte Split-Funktion `split_dienst()` (erstellt neue Dienste)
- Unnötige Duplikat-Prüfungen
- Veraltete Dokumentation von v0.1.x - v0.4.x

### 📋 Known Issues (für UAT)
- [ ] Screenshots fehlen noch (Platzhalter vorhanden)
- [ ] Email-Benachrichtigungen nicht getestet
- [ ] Split-Dienste: Anzeige im Backend prüfen
- [ ] Performance bei >100 Diensten testen
- [ ] Mobile-Ansicht Browser-Kompatibilität

### 🧪 Testfälle für UAT

#### Split-Dienst testen:
1. Dienst mit 8 Stunden erstellen (18:00 - 02:00)
2. Als Crew: "Anmelden" → Checkbox "Teilen" aktivieren
3. "1. Teil" wählen → Anmelden
4. Prüfen: 1. Slot besetzt, 2. Slot frei
5. Als zweiter User: "2. Teil" wählen → Anmelden
6. Prüfen: Beide Slots besetzt, Dienst zeigt "Voll"

#### Normale Anmeldung testen:
1. Dienst mit 3 Plätzen erstellen
2. 3 verschiedene User anmelden
3. Prüfen: Badge zeigt "3/3 belegt"
4. 4. User versucht anzumelden → Fehler "bereits voll"

#### Backend Modal-Funktionen testen:
1. Jeden "Bearbeiten"-Button klicken
2. Modal öffnet/schließt korrekt
3. Speichern funktioniert
4. Bulk-Updates testen

---

## Archivierte Versionen

Änderungen von Version 0.1.0 bis 0.4.7 wurden archiviert.  
Siehe: `documentation/archive/CHANGELOG_LEGACY.md`

---

**Legende:**
- ✨ Added - Neue Features
- 🔧 Changed - Änderungen an bestehenden Features
- 🐛 Fixed - Bugfixes
- 🗑️ Removed - Entfernte Features
- 🔒 Security - Sicherheitsupdates
- 📋 Known Issues - Bekannte Probleme
- 🧪 Testing - Test-Informationen

#### JavaScript-Fehler
- **dp-public.js:**
  - Doppelter Code mit "Illegal return statement" entfernt
  - Datei komplett neu erstellt ohne Duplikate
  - Saubere Struktur

- **Fehlende Modal-Funktionen:**
  - Alle CRUD-Funktionen für Vereine/Veranstaltungen/Dienste fehlten
  - Buttons riefen nicht-existierende Funktionen auf
  - Komplett in dp-admin-modals.js implementiert

### 🏗️ Technische Details

#### Neue Dateien
```
assets/js/
└── dp-admin-modals.js        [NEU] - 1000+ Zeilen alle Modal-Funktionen

root/
└── migrate-mitarbeiter-id.php [NEU] - Migrations-Script

documentation/
└── DATABASE_STRUCTURE_AKTUELL.md [NEU] - 550+ Zeilen vollständige DB-Doku
```

#### Geänderte Dateien
- `admin/class-admin.php` - dp-admin-modals.js registriert
- `public/class-public.php` - split_dienst() Methode + AJAX-Handler
- `public/class-dienstplan-public.php` - erstellt_am entfernt
- `includes/class-database.php` - mitarbeiter Tabelle ohne erstellt_am, Migration hinzugefügt
- `public/templates/veranstaltung-compact.php` - Split-Formular + Email optional
- `assets/js/dp-public.js` - Neu erstellt ohne Duplikate
- `documentation/DATABASE_STRUCTURE.md` - Als veraltet markiert

#### Script-Dependencies
```
dp-admin-scripts (base - dp-admin.js)
├── dp-admin-modals (NEU - depends on: jquery, dp-admin-scripts)
├── dp-dienst-modal (depends on: jquery, dp-admin-scripts)
├── dp-dienste-table (depends on: jquery, dp-admin-scripts)
├── dp-bulk-update-modals (depends on: jquery, dp-admin-scripts)
└── dp-besetzung-modal (depends on: jquery, dp-admin-scripts)
```

#### AJAX-Endpunkte
Alle Modal-Funktionen verwenden bestehende AJAX-Handler:
- `dp_save_verein`, `dp_get_verein`, `dp_delete_verein`
- `dp_save_veranstaltung`, `dp_get_veranstaltung`, `dp_delete_veranstaltung`
- `dp_create_event_page`, `dp_update_event_page`
- `dp_save_dienst`, `dp_get_dienst`, `dp_delete_dienst`
- `dp_save_bereich`, `dp_get_bereich`
- `dp_save_taetigkeit`, `dp_get_taetigkeit`, `dp_delete_taetigkeit`
- `dp_register_service` (Frontend - neu für Split-Anmeldung)

### ⚠️ Breaking Changes
Keine - Alle Änderungen sind abwärtskompatibel.

### 📊 Datenbank-Migration erforderlich
**JA - mitarbeiter_id Spalte:**
- Automatisch: Bei Plugin-Update via `class-database.php` Lines 328-340
- Manuell: `migrate-mitarbeiter-id.php` ausführen

### 🎯 Nächste Schritte (0.5.0)
- [ ] Backend-AJAX-Handler für alle Modal-Save-Funktionen testen
- [ ] Bulk-Update-Modals voll implementieren (aktuell nur Platzhalter)
- [ ] Besetzungs-Modal vollständig integrieren
- [ ] Dienst-Split im Backend ermöglichen
- [ ] Unit-Tests für split_dienst() Methode

---

## [0.4.0] - 2025-11-13

### ✨ Hinzugefügt

#### JavaScript-Refactoring (Major Improvement)
- **JavaScript wurde aus PHP-Views ausgelagert:**
  - Neue Datei: `assets/js/dp-dienst-modal.js` - Alle Modal-Funktionen für Dienste
  - Neue Datei: `assets/js/dp-dienste-table.js` - Tabellen-Funktionen und Bulk-Aktionen
  - Proper WordPress Script-Enqueuing in `class-admin.php`
  - Dependencies korrekt definiert (jQuery)
  - Scripts werden nur im Admin-Bereich geladen

### 🔧 Geändert

#### Code-Qualität & Best Practices
- **Keine Inline-Scripts mehr in PHP-Dateien**
  - `dienst-modal.php` - Alle `<script>`-Tags entfernt
  - `dienste-table.php` - Alle `<script>`-Tags entfernt
  - Kommentare zeigen auf neue JavaScript-Dateien
- **Verbessertes Script-Loading:**
  - Scripts werden via `wp_enqueue_script()` geladen
  - Korrekte Abhängigkeiten (jQuery, dp-admin-scripts)
  - Versionierung für Cache-Busting
  - Scripts im Footer geladen (bessere Performance)
- **Erweiterte wp_localize_script Daten:**
  - `selectedVeranstaltung` wird aus GET-Parameter übernommen
  - Zentrale AJAX-Konfiguration für alle Scripts

#### JavaScript-Struktur
- **IIFE Pattern** für bessere Kapselung `(function($) { ... })(jQuery)`
- **Globale Funktionen** klar gekennzeichnet (`window.functionName`)
- **Private Funktionen** innerhalb der IIFE
- **Ausführliche Kommentare** und Funktionsbeschreibungen
- **TODOs markiert** für zukünftige Verbesserungen (Modal-Dialoge statt prompt())

### 🐛 Behoben
- **Doppelte Funktionsdefinition** von `deleteDienst` in `dienst-modal.php` behoben
- **Fehlende schließende Klammern** in JavaScript-Code korrigiert

### 📊 Performance
- **JavaScript wird gecacht** durch Browser (separate Dateien)
- **Reduzierter HTML-Output** durch Entfernung von Inline-Scripts
- **Schnelleres Laden** durch Defer/Footer-Loading

### 🏗️ Technische Details

#### Neue Dateien
```
assets/js/
├── dp-dienst-modal.js      [NEU] - Modal-Funktionen für Dienste (570 Zeilen)
└── dp-dienste-table.js     [NEU] - Tabellen-Funktionen & Bulk-Aktionen (285 Zeilen)
```

#### Geänderte Dateien
- `admin/class-admin.php` - Erweitertes Script-Enqueuing
- `admin/views/partials/dienst-modal.php` - Inline-Scripts entfernt
- `admin/views/partials/dienste-table.php` - Inline-Scripts entfernt

#### Script-Dependencies
```
dp-admin-scripts (base)
├── dp-dienst-modal (depends on: jquery, dp-admin-scripts)
└── dp-dienste-table (depends on: jquery, dp-admin-scripts)
```

### ⚠️ Breaking Changes
Keine - Die Funktionalität bleibt vollständig erhalten.

### 🎯 Nächste Schritte (0.5.0)
- [ ] Weitere Partials refactoren (vereine-modal.php, veranstaltungen-modal.php, etc.)
- [ ] Modal-Dialoge statt `prompt()` für Bulk-Aktionen
- [ ] JavaScript Minification für Production
- [ ] ESLint Integration

---

## [0.3.0] - 2025-11-12

### ✨ Hinzugefügt

#### Bereiche & Tätigkeiten Verwaltung
- Neue Admin-Seite "Bereiche & Tätigkeiten"
- Bereiche können erstellt, bearbeitet und gelöscht werden
- Farbzuordnung für bessere visuelle Unterscheidung
- Tätigkeiten sind Bereichen zugeordnet (hierarchische Struktur)
- Modal-Dialoge für komfortables Bearbeiten
- **Bulk-Aktionen für Tätigkeiten:**
  - Mehrfachauswahl mit Checkboxen
  - Löschen mehrerer Tätigkeiten gleichzeitig
  - Bereich verschieben (mehrere Tätigkeiten umziehen)
  - Status ändern (aktivieren/deaktivieren)
- Verwendungs-Counter zeigt Anzahl der Dienste pro Tätigkeit
- Schutz: Tätigkeiten mit aktiven Diensten können nicht gelöscht werden

#### Bulk-Aktionen für Dienste
- Checkbox-basierte Mehrfachauswahl in Dienste-Tabelle
- **Verfügbare Bulk-Aktionen:**
  - Löschen mehrerer Dienste
  - Tag verschieben (Dienste zu anderem Tag bewegen)
  - Zeiten ändern (Start-/Endzeit für mehrere Dienste)
  - Verein ändern
  - Bereich ändern
  - Tätigkeit ändern
  - Status ändern (geplant/unvollständig/bestätigt)
- Bulk-Toolbar erscheint automatisch bei Auswahl
- Bestätigungsdialoge vor kritischen Aktionen

#### Import/Export erweitert
- **CSV-Export für Vereine:**
  - ID, Name, Kürzel, Beschreibung, Kontaktdaten, Status
- **CSV-Export für Veranstaltungen:**
  - ID, Name, Beschreibung, Start-/Enddatum
- **CSV-Export für Dienste (komplett überarbeitet):**
  - 15 Spalten inkl. Tag-Nummer, Verein, Bereich, Tätigkeit
  - Zeitangaben, Personenanzahl, Status, Besonderheiten
  - Korrekte Objektzugriffe (Bug behoben)

#### Admin-Übersicht verbessert
- Tag-gruppierte Ansicht mit kollabierbaren Sektionen
- Alle Veranstaltungs-Tage werden angezeigt (auch ohne Dienste)
- Fixierte linke Spalten (Bereich, Tätigkeit, Verein, Zeit)
- Scrollbare Mitarbeiter-Badges für bessere Übersicht
- Visuell getrennte Bereiche mit farbigen Headers

### 🔧 Geändert

#### Code-Struktur & Wartbarkeit
- **Datenbankstruktur vollständig dokumentiert** (`DATABASE_STRUCTURE.md`)
  - Alle 13 Tabellen mit Feldern und Beziehungen
  - ER-Diagramm als Text
  - Datenfluss dokumentiert
- Code-Duplikate entfernt (3 doppelte Database-Klassen gelöscht)
- Konsistente Verwendung einer Database-Klasse (`class-database.php`)
- Cleanup-Report erstellt (`CLEANUP_REPORT.md`)

#### Zeit-Handling verbessert
- **Zeit-Normalisierung:** Eingaben wie "19.00" werden automatisch zu "19:00:00" konvertiert
- **Overnight-Dienste:** Automatische Erkennung wenn Endzeit < Startzeit
  - Setzt automatisch `bis_datum` auf +1 Tag
  - Visuelle Kennzeichnung in allen Ansichten
- Validation vereinfacht: Prüft nur noch logische Fehler (Start < Ende)
- Keine starren Zeitfenster-Beschränkungen mehr

#### Status-System
- **Dienst-Status konsistent implementiert:**
  - `geplant` - Standardstatus für neue Dienste
  - `unvollstaendig` - Fehlende Informationen (gelbe Kennzeichnung)
  - `bestaetigt` - Vollständig und bestätigt
- OR-Logik für "unvollständig": Mindestens ein Wert fehlt
- Visuelle Indikatoren (Farben, Icons) in allen Views

### 🐛 Behoben

#### Import/Export Fixes
- CSV-Export verwendete falsche Array-Syntax für Objekte (`$row['field']` → `$row->field`)
- Fehlende Felder in Exporten hinzugefügt (`bis_datum`, `status`, `tag_nummer`)
- Korrekte Feldreferenzen (`ende_datum` → `end_datum`)

#### Übersicht/Overview Fixes
- Tag-Gruppierung nutzte Dienst-Datum statt Veranstaltungs-Tag (`tag_id` korrekt verwendet)
- Null-Pointer-Fehler bei Diensten ohne `tag_id` behoben
- Dienste ohne Tag werden in separatem Bereich angezeigt

#### Validierung & Datenintegrität
- Zeit-Validation korrigiert (19.00 - 01:00 funktioniert jetzt)
- Overnight-Dienste werden korrekt validiert und gespeichert
- Status-Feld wird bei Import/Export korrekt behandelt

### ❌ Entfernt

- **Feld `erforderliche_qualifikation` aus Tätigkeiten-Tabelle**
  - Wurde in keiner Funktion tatsächlich genutzt
  - Komplexität ohne Mehrwert reduziert
  - Migration wurde **nicht** erstellt (Feld war nie produktiv)

- **Duplikat-Dateien gelöscht:**
  - `includes/class-dienstplan-database.php` (Duplikat)
  - `includes/class-dienstplan-database.backup.php`
  - `includes/class-dienstplan-database-clean.php`

### 🔒 Sicherheit

- Alle AJAX-Calls haben Nonce-Prüfungen
- Capability-Checks für alle Admin-Funktionen
- Prepared Statements für alle Datenbank-Queries
- Input-Sanitization konsequent angewendet
- Output-Escaping (esc_html, esc_attr) verwendet

### 📊 Performance

- Indizes auf häufig genutzte Spalten gesetzt
- Prepared Statements cachen Query-Plans
- Lazy Loading für Mitarbeiter-Daten in Übersicht

### 🏗️ Technische Details

#### Neue AJAX-Handler
- `bulk_delete_dienste` - Löscht mehrere Dienste
- `bulk_update_dienste` - Aktualisiert Dienst-Felder
- `get_bereich` - Lädt Bereich-Daten
- `save_bereich` - Speichert Bereich
- `delete_bereich` - Löscht Bereich
- `get_taetigkeit` - Lädt Tätigkeits-Daten
- `save_taetigkeit` - Speichert Tätigkeit
- `delete_taetigkeit` - Löscht Tätigkeit
- `toggle_taetigkeit_status` - Aktiviert/Deaktiviert Tätigkeit
- `bulk_delete_taetigkeiten` - Löscht mehrere Tätigkeiten
- `bulk_update_taetigkeiten` - Aktualisiert Tätigkeiten

#### Neue Database-Methoden
- `count_dienste_by_taetigkeit($taetigkeit_id)` - Zählt Verwendungen
- `create_taetigkeit($data)` - Alias für add_taetigkeit

#### Dateistruktur
```
admin/views/
  ├── bereiche-taetigkeiten.php  [NEU] - Bereiche & Tätigkeiten Verwaltung
  ├── overview.php               [ÜBERARBEITET] - Tag-gruppierte Übersicht
  └── partials/
      └── dienste-table.php      [ÜBERARBEITET] - Mit Bulk-Actions
```

---

## [0.2.6] - 2025-11-XX

### Geändert
- Diverse kleinere Fixes und Verbesserungen
- (Details aus früheren Versionen hier einfügen)

---

## [0.2.0] - 2025-XX-XX

### Hinzugefügt
- Grundlegende Plugin-Struktur
- Vereine-Verwaltung
- Veranstaltungen-Verwaltung
- Dienste-Verwaltung
- Mitarbeiter-Verwaltung
- Import/Export Grundfunktionen

---

## Legende

- ✨ **Hinzugefügt** - Neue Features
- 🔧 **Geändert** - Änderungen an bestehenden Features
- 🐛 **Behoben** - Bug Fixes
- ❌ **Entfernt** - Entfernte Features
- 🔒 **Sicherheit** - Sicherheits-Fixes
- 📊 **Performance** - Performance-Verbesserungen
- 🏗️ **Technische Details** - Interne Änderungen für Entwickler

---

## Migration von 0.2.x zu 0.3.0

### Datenbank
Keine Datenbank-Migration erforderlich. Alle Änderungen sind abwärtskompatibel.

### Code
Falls Sie das Plugin erweitert haben:
- Feld `erforderliche_qualifikation` existiert nicht mehr in Tätigkeiten
- Neue AJAX-Handler verfügbar (siehe Technische Details)
- Database-Klasse: Nur noch `includes/class-database.php` verwenden

### Bekannte Einschränkungen
- Bulk-Action Dialoge verwenden noch `prompt()` statt Modals (wird in 0.4.0 verbessert)
- JavaScript ist inline in PHP-Views (wird in 0.4.0 ausgelagert)

# Version 0.9.5.5 - Timeline & Filter Redesign

**Release Date:** 11. März 2026  
**Typ:** Feature Release & UI Redesign  
**Status:** Production Ready

---

## 🎯 Überblick

Version 0.9.5.5 bringt eine vollständige Überarbeitung der Veranstaltungsseite und Dienst-Verwaltung für Crew-Mitglieder. Der Fokus liegt auf:

- 📊 **Visuelle Verbesserung:** Timeline-View mit Gantt-Chart-ähnlicher Darstellung
- 🔗 **Intelligente Filter:** 4-stufiges abhängiges Filter-System
- ✨ **Moderne UI:** Emoji-basierte Icons und improvisierte Navigation
- 🎨 **Bessere Farbsemantik:** Rot=Frei, Grün=Besetzt (korrigiert)

---

## ✨ Neue Features

### 1. Verbesserte Timeline-View

#### Was ist neu?

**Timeline-Ansicht** ist eine komplett neue Visualisierungsmethode für Dienste:
- Zeitgitter-Layout (ähnlich Gantt-Chart)
- Tag-Tabs für schnelle Navigation
- Farbige Service-Balken mit Zeitpositionen
- Inline-Besetzungs-Badges

#### Visuelles Design

**Service-Balken:**
- Mindestens 34px höhe für bessere Lesbarkeit
- Gradienten: Blau (Standard) bis dunkler Blau
- Position basierend auf Dienst-Startzeit
- Länge entspricht Dienst-Dauer

**Besetzungs-Badges:**
- 🔴 **Rot/Offen:** `is-open` Klasse, Text "Plätze frei"
- 🟢 **Grün/Besetzt:** `is-full` Klasse, Text "Voll"
- Badge zeigt Besetzung (z.B. "2/3")

**Zeit-Grid:**
- Dynamische Stundeneinteilung (basierend auf min/max Servicezeiten)
- Hour-Labels: 18:00, 19:00, 20:00 etc.
- Vertikale Gridlines für Zeitabschnitte

#### Beispiel Timeline Layout

```
Freitag, 15. März 2026

Uhrzeit   Einlass      |  Catering          |  Dekoration
─────────────────────────────────────────────────────────
17:00                  |                    |

17:30                  |                    |

18:00   [Ticketkontr.] |                    |
        2/3 Besetzt    |                    |

18:30                  |  [Getränkeausgabe] |
                       |  1/3 Frei          |

19:00   [Einlass]      |  [Speiseausgabe]   |
        3/3 Voll       |  2/2 Voll          |

20:00                  |                    |  [Abbau]
                       |                    |  2/3 Frei
```

#### Technische Implementierung

**Datei:** `public/templates/veranstaltung-verein.php` (Zeilen 410–750)

**CSS-Klassen:**
```css
.dp-timeline-container        /* Hauptcontainer */
.dp-timeline-day-tabs         /* Tag-Tab-Navigation */
.dp-timeline-day-tab          /* Einzelner Tab (data-tag-id) */
.dp-timeline-day              /* Tag-Container (active: sichtbar) */
.dp-timeline-grid-header      /* Zeit-Grid Header */
.dp-timeline-track-bar        /* Service-Balken */
.dp-track-occupancy           /* Besetzungs-Badge */
.is-open                      /* Rot, frei */
.is-full                      /* Grün, besetzt */
```

**JavaScript-Funktionen:**
- `dpActivateTimelineDayByTab()`: Synced aktiven Tab mit sichtbarem Day
- `dpFormatTimelineTime()`: Format-Funktion für Zeiten
- `dpCalculateServiceBarPosition()`: Berechnet left% und width% für Balken

---

### 2. Intelligente 4-stufige Filter

#### Filter-Hierarchie

```
Besetzung (Wer?)
    ↓
Tag (Wann?)
    ↓
Arbeitsbereich (Wo?)
    ↓
Dienst (Was?)
```

#### Filter-Logik

**Besetzung-Filter:**
- `alle` (Standard)
- `nur_freie` - nur unbesetzte Dienste
- `meine_dienste` - deine persönlichen Eintragungen

**Tag-Filter:**
- Zeigt nur Tage mit verfügbaren Diensten
- Abh. von Rest-Filtern (z.B. "Nur freie" = nur Tage mit freien Diensten)

**Arbeitsbereich-Filter:**
- Zeigt nur Bereiche des ausgewählten Tages
- Abh. von Besetzungs-Filter (z.B. "Nur freie" = nur Bereiche mit freien Diensten)

**Dienst-Filter:**
- Zeigt nur Tätigkeiten des ausgewählten Bereichs
- Abh. von allen anderen Filtern

#### Intelligente Option-Verwaltung

**Features:**
- ✅ Unmögliche Optionen sind deaktiviert (grayed out)
- ✅ Keine leeren Ergebnisse durch Smart-Fallback
- ✅ Automatisches Zurücksetzen auf "Alle" wenn Auswahl unmöglich wird

**Beispiel:**
```
Benutzer wählt: "Nur freie" + "Montag" + "Catering" + "Speise..."
Ergebnis: 0 Dienste vorhanden

System-Aktion:
- Dienst-Filter schaltet auf "Alle" (Fallback)
- Oder: Arbeitsbereich auf "Alle" (Fallback)
- Oder: Tag auf "Alle" (letzte Fluchtlinie)
→ Erfolg: Mindestens 1 Dienst sichtbar
```

#### Technische Implementierung

**Datei:** `public/templates/veranstaltung-verein.php` (Zeilen 1074–1365)

**Haupt-Funktionen:**

```php
// Filter anwenden & UI-Sichtbarkeit aktualisieren
dpApplyFrontendFilters()

// Intelligente Option-Disabling basierend auf Abhängigkeiten
dpUpdateFilterOptionVisibility()

// Item gegen aktuellen Filter-Status überprüfen
dpItemMatchesFilterSet($item, $state, $filterKeys, $overrideFilterName, $overrideValue)

// Filtern nach Besetzungs-Typ (alle/frei/meine)
dpItemPassesAvailability($item, $value)

// Select-Element Management
dpGetFilterSelect($filterName)
dpSetFilterSelectValue($filterName, $value)
dpEnsureVisibleActiveFilterOption($filterName)
```

**Data-Attribute:**
```html
<!-- Dienst-Item mit Filtering-Infos -->
<article class="dp-dienst"
  data-tag-id="2025-03-15"
  data-bereich-id="1"
  data-taetigkeit-id="5"
  data-has-free="1"
  data-has-mine="0">
  ...
</article>
```

---

### 3. Moderne Icon-basierte View-Toggle

#### Neue View-Steuerung

**Position:** Header-Right (neben anderen Tools)

**Icons & Views:**
- 🗂️ = Kachel-Ansicht (Card Grid)
- 📋 = Kompakt-Ansicht (List/Table)
- 📊 = Timeline-Ansicht (Gantt-Chart)

#### Styling

**Container:**
```css
.dp-header-view-tools {
  display: flex;
  gap: 1rem;
  margin-left: auto;  /* Float to right */
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  padding: 0.5rem;
}

.dp-view-toggle {
  text-decoration: none;
  font-size: 1.5rem;
  padding: 0.5rem 0.75rem;
  border-radius: 0.25rem;
  cursor: pointer;
}

.dp-view-toggle.active {
  background: linear-gradient(135deg, #0ea5e9, #0284c7);  /* Blue gradient */
  color: white;
}
```

#### User Experience

- Softes Hover-State (Farbe ändert leicht)
- Clear Visual Feedback für aktive View
- Responsive (Icons stacken auf mobil)

---

### 4. Verbesserte Benutzerfreundlichkeit

#### "Dienst absagen"-Button im Timeline-Bar

**Feature:** Direkter Action-Button in Service-Balken

**Placierung:**
- Innerhalb des Service-Bars (rechts)
- Sichtbar nur wenn es dein Dienst ist

**Aktion:**
- Klick → Modal öffnet sich
- Modal: Bestätigungs-Dialog mit Grund
- Fallback-Link: E-Mail-Adresse des Admins falls Fehler

#### Farbsemantik-Korrektur

**Neu (0.9.5.5):**
- 🔴 **Rot = Frei/Offen** (Warnung: Stelle muss besetzt werden)
- 🟢 **Grün = Besetzt/Voll** (OK: Position ist vergeben)

**Alt (< 0.9.5.5):**
- ❌ Grün = Frei (kontraintuitive)
- ❌ Rot = Besetzt (falsch herum)

#### Filter-Reset Button

**Verbesserungen:**
- ✅ Klarerer Label: "🔄 Filter zurücksetzen"
- ✅ Besserer Kontrast: Bluer Border (#3b82f6) + dunkle Text
- ✅ Stärkere Gewichtung (font-weight: 600)
- ✅ Pill-Shape (border-radius: 999px)
- ✅ Nur sichtbar wenn mindestens eine Filter aktiv

---

## 🐛 Behobene Probleme

| Problem | Grund | Lösung |
|---------|-------|--------|
| Timeline zu klein | Dienste waren 15–20px hoch | Erhöht auf 34px, bessere Schrift |
| Filter verwirrend | Keine Abhängigkeiten, viele leere Ergebnisse | 4-stufige Logik mit Smart-Disabling |
| View-Toggle unsichtbar | Unter anderen Controls versteckt | Zu Header-Right moved, Icons statt Text |
| Farbsemantik falsch | Frei=Grün, Besetzt=Rot | Invertiert: Rot=Frei, Grün=Besetzt |
| "Dienst absagen" versteckt | Button unter Timeline | Ins Service-Bar integriert |
| Filter-Reset unleserlich | Zu heller Kontrast | Blaue Border, dunkle Text |

---

## 📊 Statistiken der Änderungen

| Metrik | Wert |
|--------|------|
| Dateien geändert | 42 |
| Neue Zeilen | 11,108 |
| Gelöschte Zeilen | 2,362 |
| Haupt-Template Größe | ~2,770 Zeilen |
| CSS-Blöcke neu | 8 |
| JS-Funktionen neu | 12+ |
| Release ZIP-Größe | 412,654 bytes |
| GitHub Release | v0.9.5.5 |
| Commit-Hash | 6d9a058 |

---

## 🔄 Migration von älteren Versionen

### Was ist kompatibel?

✅ **Vollständig abwärtskompatibel:**
- Alte Dienst-Daten werden unterstützt
- Bestehende Filter-Einstellungen funktionieren
- Keine DB-Änderungen erforderlich

### Update-Prozess

1. **WordPress Admin → Plugins**
2. **Alte "dienstplan-verwaltung" deaktivieren**
3. **Ggf. Backup (optional)**
4. **Version 0.9.5.5 hochladen & aktivieren**
5. **Frontend testen**

**Keine Daten-Migration notwendig!**

---

## 📚 Dokumentation

**Updated Files:**
- ✅ `BEDIENUNGSANLEITUNG_FRONTEND.md` - Neue UI-Anleitung
- ✅ `CHANGELOG.md` - v0.9.5.5 Entry hinzugefügt
- ✅ `README.md` - Version & Datum aktualisiert

**Neue Files:**
- ✨ `VERSION_0. 9.5.5_FEATURES.md` (dieses Dokument)

---

## 🚀 Ausblick (Kommende Versionen)

### Geplante Features

**v0.9.6 (Q2 2026):**
- 📱 Besseres Mobile-Responsive Design
- 📧 Erweiterte E-Mail-Templates
- 🔔 Push-Notifications (Web)

**v0.10.0 (Q3 2026):**
- 📊 Admin Dashboard mit Statistiken
- 🧮 Automatische Schicht-Balance
- 🔐 2-Faktor-Authentifizierung

**v1.0.0 (Q4 2026):**
- 🌍 Multi-Language Support (EN, FR, IT)
- ♿ WCAG 2.1 AA Compliance
- 📱 Native Mobile App (Concept)

---

## ❓ Support & Feedback

**Issues gefunden?**
→ GitHub: https://github.com/VereinsringWasserlos/dienstplan-verwaltung/issues

**Feedback oder Feature-Requests?**
→ Discussions: https://github.com/VereinsringWasserlos/dienstplan-verwaltung/discussions

**Kontakt:**
Vereinsring Wasserlos Development Team

---

**Letzte Aktualisierung:** 11. März 2026

# Frontend Portal - Dienstplan Hub

**Version:** 0.6.6  
**Shortcode:** `[dienstplan_hub]`

---

## 📋 Übersicht

Das Frontend Portal (Dienstplan Hub) ist eine All-in-One Einstiegsseite für Benutzer. Es kombiniert:

- 🔐 **Login/Registrierung** für neue Benutzer
- 👋 **Personalisierte Begrüßung** für angemeldete Benutzer
- 📅 **Aktuelle Veranstaltungen** mit direkter Anmeldung
- 🔗 **Quick-Links** zu wichtigen Funktionen

---

## 🚀 Schnelle Einrichtung (5 Minuten)

### Schritt 1: Neue Seite erstellen

```
WordPress-Admin → Seiten → Neu hinzufügen
```

**Seitentitel:** "Dienstplan" oder "Dienste"

### Schritt 2: Shortcode einfügen

Im Inhalts-Editor:

```
[dienstplan_hub]
```

### Schritt 3: Seite veröffentlichen

```
Klick auf "Veröffentlichen"
```

✅ **Fertig!** Die Seite ist jetzt live.

---

## 🎨 Was Besucher sehen

### Nicht angemeldete Benutzer

1. **Hero-Bereich**
   - Willkommens-Nachricht
   - Aufforderung zur Anmeldung

2. **Login-Karte**
   - "Anmelden" Button → WordPress-Login
   - "Registrieren" Button → WordPress-Registrierung

3. **Aktuelle Veranstaltungen**
   - Nur Veranstaltungen mit Status "Geplant" oder "Aktiv"
   - Maximal 6 Events (neueste zuerst)
   - Hinweis auf Anmeldung für Zugriff

4. **Info-Box**
   - Anleitung wie das System funktioniert

### Angemeldete Benutzer

1. **Personalisierte Begrüßung**
   ```
   "Willkommen zurück, [Name]! 👋"
   ```

2. **Quick-Links Dashboard**
   - 📋 **Meine Dienste** → Übersicht eigener Einsätze
   - 👤 **Mein Profil** → Benutzer-Einstellungen

3. **Veranstaltungen mit Aktionen**
   - "Zur Anmeldung" bei geplanten Events
   - "Details ansehen" bei aktiven Events
   - Anzeige der beteiligten Vereine

---

## ⚙️ Shortcode-Parameter

Der Shortcode unterstützt optionale Parameter:

```
[dienstplan_hub show_login="true" show_events="true" limit="6"]
```

### Parameter-Referenz

| Parameter | Typ | Standard | Beschreibung |
|-----------|-----|----------|--------------|
| `show_login` | boolean | `true` | Login-Bereich anzeigen |
| `show_events` | boolean | `true` | Veranstaltungen anzeigen |
| `limit` | integer | `6` | Max. Anzahl Veranstaltungen |

### Beispiele

**Nur Veranstaltungen (kein Login):**
```
[dienstplan_hub show_login="false"]
```

**Mehr Veranstaltungen anzeigen:**
```
[dienstplan_hub limit="12"]
```

**Nur Login-Bereich:**
```
[dienstplan_hub show_events="false"]
```

---

## 🎯 Funktionsweise

### Status-basierte Anzeige

**Veranstaltungen werden nur angezeigt wenn:**
- ✅ Status = "Geplant" oder "Aktiv"
- ✅ Start-Datum liegt in der Zukunft oder heute
- ❌ Status = "In Planung" → Nicht sichtbar
- ❌ Status = "Abgeschlossen" → Nicht sichtbar

### Anmelde-Links

**"Zur Anmeldung" Button erscheint wenn:**
- Veranstaltung hat Status "Geplant"
- Mindestens eine Anmeldeseite wurde erstellt
  (via Backend: Veranstaltungen → Verein-Seiten erstellen)

**Wichtig:** Ohne erstellte Vereinsseiten wird "Noch nicht verfügbar" angezeigt!

---

## 🔧 Anpassungen

### Design anpassen

Das Template verwendet Inline-CSS für einfache Anpassung.  
Datei: `public/templates/dienstplan-hub.php`

**Farben ändern:**
```css
/* Hero-Gradient */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

/* Button-Farben */
.dp-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
```

### Eigenes CSS hinzufügen

In `style.css` des aktiven Themes:

```css
/* Eigene Anpassungen für Dienstplan Hub */
.dp-hub-hero {
    background: linear-gradient(135deg, #your-color-1, #your-color-2);
}

.dp-hero-title {
    font-size: 3rem; /* Größere Überschrift */
}
```

---

## 💡 Best Practices

### 1. Als Startseite einrichten

```
Einstellungen → Lesen → Startseite
→ Wähle die "Dienstplan" Seite
```

### 2. In Navigation einbinden

```
Design → Menüs
→ Füge die Seite zum Hauptmenü hinzu
```

### 3. WordPress-Registrierung aktivieren

Für den "Registrieren" Button:

```
Einstellungen → Allgemein
→ ☑️ Jeder kann sich registrieren
```

### 4. Veranstaltungsseiten erstellen

**Wichtig:** Damit "Zur Anmeldung" funktioniert:

```
Backend → Veranstaltungen
→ Veranstaltung erweitern
→ "Alle Verein-Seiten erstellen"
```

---

## 🐛 Troubleshooting

### Problem: "Noch nicht verfügbar" bei allen Events

**Ursache:** Keine Vereinsseiten erstellt

**Lösung:**
```
1. Backend → Veranstaltungen
2. Veranstaltung erweitern (Pfeil-Icon)
3. Button "Alle Verein-Seiten erstellen" klicken
```

### Problem: Keine Veranstaltungen sichtbar

**Ursache 1:** Alle Events sind "In Planung"

**Lösung:** Status auf "Geplant" ändern:
```
Backend → Veranstaltungen
→ Status-Dropdown → "Geplant" wählen
```

**Ursache 2:** Start-Datum liegt in der Vergangenheit

**Lösung:** Datum aktualisieren oder neue Veranstaltung anlegen

### Problem: Login-Button funktioniert nicht

**Ursache:** WordPress-Login deaktiviert

**Lösung:** Standard-Login unter `/wp-login.php` sollte verfügbar sein

---

## 🔗 Verwandte Shortcodes

**Komplette Übersicht:**
```
[dienstplan_hub]              // Portal-Seite
[dienstplan_veranstaltungen]  // Nur Veranstaltungs-Liste
[dienstplan_veranstaltung id="123"] // Einzelne Veranstaltung
[meine_dienste]               // Persönliche Dienste
```

**Kombination:**

Separate Seiten für verschiedene Bereiche:

- **Startseite:** `[dienstplan_hub]`
- **/veranstaltungen:** `[dienstplan_veranstaltungen]`
- **/meine-dienste:** `[meine_dienste]`

---

## 📱 Responsive Design

Das Template ist vollständig responsive:

- **Desktop:** Grid mit 3 Spalten für Events
- **Tablet:** Grid mit 2 Spalten
- **Mobile:** Einzelne Spalte, gestapelt

Keine zusätzliche Konfiguration nötig!

---

## 🎨 Screenshots

### Desktop-Ansicht (nicht angemeldet)
```
┌─────────────────────────────────────┐
│  Dienstplan Portal                  │
│  Melden Sie sich an...              │ ← Hero
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│  🔐 Anmelden                         │
│  [Anmelden] [Registrieren]          │ ← Login
└─────────────────────────────────────┘
┌───────────┬───────────┬───────────┐
│ Event 1   │ Event 2   │ Event 3   │ ← Events
└───────────┴───────────┴───────────┘
```

### Mobile-Ansicht (angemeldet)
```
┌─────────────────────┐
│ Willkommen, Max!    │ ← Hero
└─────────────────────┘
┌─────────────────────┐
│ 📋 Meine Dienste    │ ← Quick-Links
└─────────────────────┘
┌─────────────────────┐
│ 👤 Mein Profil      │
└─────────────────────┘
┌─────────────────────┐
│ Event 1             │ ← Events
└─────────────────────┘
```

---

## 📊 Technische Details

**Template-Datei:**  
`public/templates/dienstplan-hub.php`

**Klasse & Methode:**  
`Dienstplan_Public::shortcode_dienstplan_hub()`

**Abhängigkeiten:**
- `class-database.php` für Datenbankzugriffe
- WordPress-User-Funktionen für Login-Status
- Veranstaltungs- und Vereins-Tabellen

**Performance:**
- Caching-freundlich (keine AJAX-Calls)
- Lädt maximal 6 Events (konfigurierbar)
- Optimierte SQL-Queries

---

## 🆘 Support

Bei Problemen:

1. **Debugging aktivieren:**
   ```php
   define('WP_DEBUG', true);
   ```

2. **Cache leeren:**
   - Browser-Cache
   - WordPress-Cache Plugin

3. **Shortcode testen:**
   ```
   Neue Test-Seite erstellen
   Nur [dienstplan_hub] einfügen
   ```

4. **Logs prüfen:**
   ```
   wp-content/debug.log
   ```

---

**Viel Erfolg mit dem Frontend Portal! 🚀**

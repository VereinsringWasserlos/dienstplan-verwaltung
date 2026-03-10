# Quick-Start Guide - Dienstplan-Verwaltung

**In 15 Minuten einsatzbereit!** ⚡

---

## 🎯 Für Administratoren

### 1. Plugin aktivieren (2 Min)

```
WordPress-Admin → Plugins → Dienstplan-Verwaltung → Aktivieren
```

✅ Menüpunkt "Dienstplan" erscheint in der linken Navigation

### 2. Ersten Verein anlegen (2 Min)

```
Dienstplan → Vereine → + Neuer Verein
```

**Minimal-Angaben:**
- Name: "Mein Verein"
- Email: kontakt@verein.de
- Status: Aktiv

**→ Speichern**

### 3. Bereiche & Tätigkeiten definieren (3 Min)

```
Dienstplan → Bereiche & Tätigkeiten
```

**Schnell-Setup:**

| Bereich | Tätigkeiten |
|---------|-------------|
| Einlass | Ticketkontrolle, Garderobe |
| Catering | Ausschank, Spüldienst |
| Auf-/Abbau | Aufbau, Abbau |

**→ Jeweils Speichern**

### 4. Erste Veranstaltung erstellen (3 Min)

```
Dienstplan → Veranstaltungen → + Neue Veranstaltung
```

**Pflicht-Felder:**
- Titel: "Test-Event"
- Verein: "Mein Verein" wählen
- Datum: [Morgen]
- Von: 18:00
- Bis: 23:00

**→ Speichern**

### 5. Ersten Dienst anlegen (3 Min)

```
In der Veranstaltung → Reiter "Dienste" → + Dienst hinzufügen
```

**Pflicht-Felder:**
- Bereich: Einlass
- Tätigkeit: Ticketkontrolle
- Von: 17:30
- Bis: 19:00
- Anzahl Personen: 2

**→ Speichern**

### 6. Frontend-Link teilen (2 Min)

```
https://ihre-website.de/veranstaltungen/
```

📧 Diesen Link an Crew-Mitglieder senden!

---

## 👥 Für Crew-Mitglieder

### 1. Link öffnen

Admin hat dir einen Link geschickt → Im Browser öffnen

### 2. Veranstaltung auswählen

Auf Event-Karte klicken oder "Details anzeigen"

### 3. Dienst wählen

Freien Dienst mit 🟢 **"Verfügbar"** Badge suchen

### 4. Anmelden klicken

Button **"Anmelden"** beim gewünschten Dienst

### 5. Formular ausfüllen

**Pflicht:**
- E-Mail
- Vorname
- Nachname
- ☑️ Datenschutz akzeptieren

**→ "Jetzt anmelden"**

### 6. Bestätigung checken

- ✅ Grüne Meldung erscheint
- 📧 E-Mail mit Details kommt (Spam-Ordner prüfen!)

**Fertig! 🎉**

---

## 🔥 Typische Szenarien

### Szenario 1: Wiederkehrende Veranstaltung

**Problem:** Monatliches Event mit gleichen Diensten

**Lösung:** Duplizieren!

```
1. Alte Veranstaltung öffnen
2. Button "Duplizieren" klicken
3. Neues Datum eingeben
4. Speichern
```

✅ Alle Dienste werden mitkopiert!

### Szenario 2: Mitarbeiter-Import

**Problem:** 50 Crew-Mitglieder aus Excel-Liste importieren

**Lösung:** CSV-Import

```
1. Excel als CSV speichern (UTF-8)
   Spalten: vorname,nachname,email,telefon
   
2. Dienstplan → Import & Export → Import
3. CSV hochladen
4. Import starten
```

✅ Alle Mitarbeiter in Sekunden importiert!

### Szenario 3: Langer Dienst teilen

**Problem:** 8-Stunden-Schicht, niemand will so lange

**Lösung:** Split-Dienst

```
1. Dienst bearbeiten
2. ☑️ Checkbox "Splittbar" aktivieren
3. Speichern
```

✅ Im Frontend: Crew kann halbe Schichten wählen!

### Szenario 4: Kurzfristige Besetzung

**Problem:** Dienst in 2 Stunden, noch 3 Plätze frei

**Lösung:** Direkt zuweisen (Backend)

```
1. Dienste → Dienst finden
2. Button "Besetzung" klicken
3. Mitarbeiter aus Dropdown wählen
4. "Zuweisen" klicken
```

✅ Mitarbeiter erhält sofort E-Mail!

---

## ⚙️ Wichtigste Einstellungen

### E-Mail-Benachrichtigungen

```
Dienstplan → Einstellungen → Benachrichtigungen
```

**Empfohlen:**
- ☑️ Neue Anmeldung → Admin
- ☑️ Dienst-Erinnerung → 24h vorher
- ☑️ Änderungen → Mitarbeiter

### Frontend-Optionen

```
Einstellungen → Frontend-Anzeige
```

**Empfohlen:**
- Standard-Ansicht: **Compact** (am übersichtlichsten)
- Veranstaltungen pro Seite: **12**
- ☑️ Anmeldung aktiviert
- ☑️ Split-Dienste erlauben

### Datenschutz

```
Einstellungen → Datenschutz
```

**Pflicht (DSGVO):**
- Datenschutz-URL: Link zu eurer Datenschutzerklärung
- Impressum-URL: Link zum Impressum

---

## 🎨 Frontend anpassen

### Shortcodes verwenden

**Frontend Portal (NEU):**
```
[dienstplan_hub]
```
Zeigt eine komplette Einstiegsseite mit:
- Login/Registrierung (für nicht angemeldete Benutzer)
- Willkommens-Bereich (für angemeldete Benutzer)
- Übersicht der aktuellen Veranstaltungen
- Quick-Links zu "Meine Dienste" und Profil

**Veranstaltungs-Liste:**
```
[dienstplan_veranstaltungen]
```

**Einzelne Veranstaltung:**
```
[dienstplan_veranstaltung id="123"]
```

**Meine Dienste:**
```
[dienstplan_meine_dienste]
```

**Kalender-Ansicht:**
```
[dienstplan_kalender verein="1"]
```

### In Seite einfügen

```
1. Seiten → Neue Seite
2. Titel: "Veranstaltungen"
3. Block hinzufügen → Shortcode
4. [dienstplan_veranstaltungen] einfügen
5. Veröffentlichen
```

### In Widget einfügen

```
Design → Widgets → Bereich wählen → Shortcode-Widget
```

---

## 🐛 Schnelle Fehlerbehebung

### Problem: Seite zeigt nichts an

**Checkliste:**
- [ ] Plugin aktiviert?
- [ ] Shortcode korrekt? `[dienstplan_veranstaltungen]`
- [ ] Veranstaltung existiert?
- [ ] Veranstaltung hat Status "Aktiv"?
- [ ] Cache leeren (Strg+F5)

### Problem: Anmelde-Button funktioniert nicht

**Checkliste:**
- [ ] JavaScript aktiviert im Browser?
- [ ] Ad-Blocker deaktiviert?
- [ ] Browser-Konsole öffnen (F12) → Fehler?
- [ ] Cookies erlaubt?

### Problem: E-Mails kommen nicht an

**Checkliste:**
- [ ] WordPress kann E-Mails senden? (Test-Plugin: WP Mail SMTP)
- [ ] Spam-Ordner gecheckt?
- [ ] Absender-E-Mail korrekt? (Einstellungen)
- [ ] Mailserver-Limit erreicht?

### Problem: Dienst wird nicht angezeigt

**Checkliste:**
- [ ] Dienst-Status = "Offen" oder "Besetzt"?
- [ ] Veranstaltungs-Datum in der Zukunft?
- [ ] Verein = "Aktiv"?
- [ ] Filter im Frontend richtig gesetzt?

---

## 📚 Weiterführende Dokumentation

| Dokument | Inhalt |
|----------|--------|
| **BEDIENUNGSANLEITUNG_BACKEND.md** | Vollständige Admin-Anleitung |
| **BEDIENUNGSANLEITUNG_FRONTEND.md** | Vollständige User-Anleitung |
| **CHANGELOG.md** | Versions-Historie |
| **DATABASE_STRUCTURE.md** | Datenbank-Schema |
| **TEST_PLAN.md** | Test-Szenarien |

---

## 🎯 Nächste Schritte

### Für neue Installationen

1. ✅ Quick-Start abgeschlossen
2. → **[BEDIENUNGSANLEITUNG_BACKEND.md](BEDIENUNGSANLEITUNG_BACKEND.md)** lesen
3. → Bereiche & Tätigkeiten vollständig anlegen
4. → Mitarbeiter importieren
5. → Erste echte Veranstaltung planen
6. → Frontend-Link an Crew senden

### Für Crew-Mitglieder

1. ✅ Erste Anmeldung erfolgreich
2. → **[BEDIENUNGSANLEITUNG_FRONTEND.md](BEDIENUNGSANLEITUNG_FRONTEND.md)** lesen
3. → "Meine Dienste" Funktion nutzen
4. → Kalender-Export ausprobieren
5. → Profil ausfüllen

---

## 🆘 Support

**Bei Fragen:**
1. **Dokumentation durchsuchen** (siehe oben)
2. **WordPress-Admin → Dienstplan → Debug** (Debug-Modus aktivieren)
3. **Log-Datei prüfen:** `/wp-content/debug.log`
4. **Administrator kontaktieren**

**Bei Bugs:**
1. Debug-Modus aktivieren
2. Fehler reproduzieren
3. Screenshot + Log-Eintrag sammeln
4. GitHub-Issue erstellen (falls Repository vorhanden)

---

## ✨ Pro-Tipps

### Tipp 1: Bulk-Operations
Statt einzeln: Mehrere Dienste markieren → Bulk-Aktion → Effizienter!

### Tipp 2: Tastatur-Shortcuts
- `Strg+F5`: Cache leeren & neu laden
- `F12`: Browser-Konsole (für Fehlersuche)
- `Tab`: Durch Formular-Felder springen

### Tipp 3: Mobile First
Teste Frontend immer auch auf Smartphone! Viele Crew-Mitglieder nutzen Handy.

### Tipp 4: Regelmäßige Backups
```
Dienstplan → Import & Export → Alle Exporte durchführen → CSV sichern
```

### Tipp 5: Templates nutzen
Standard-Dienste als Vorlage speichern (via Duplizieren), dann nur Zeiten anpassen.

---

**Du bist startklar! Viel Erfolg! 🚀**

---

**Letzte Aktualisierung:** November 2025  
**Plugin-Version:** 0.4.7  
**Geschätzte Lesezeit:** 5 Minuten

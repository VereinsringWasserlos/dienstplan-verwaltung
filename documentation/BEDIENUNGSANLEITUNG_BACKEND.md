# Bedienungsanleitung Backend - Dienstplan-Verwaltung

**Version:** 0.4.7  
**Stand:** November 2025  
**Zielgruppe:** Administratoren und Vereinsverwalter

---

## Inhaltsverzeichnis

1. [Erste Schritte](#erste-schritte)
2. [Dashboard](#dashboard)
3. [Vereine verwalten](#vereine-verwalten)
4. [Veranstaltungen verwalten](#veranstaltungen-verwalten)
5. [Dienste verwalten](#dienste-verwalten)
6. [Mitarbeiter verwalten](#mitarbeiter-verwalten)
7. [Bereiche & Tätigkeiten](#bereiche-tätigkeiten)
8. [Import & Export](#import-export)
9. [Einstellungen](#einstellungen)
10. [Tipps & Best Practices](#tipps-best-practices)

---

## Erste Schritte

### Plugin aktivieren

1. WordPress-Admin-Bereich öffnen
2. Navigation: **Plugins** → **Installierte Plugins**
3. Plugin "Dienstplan-Verwaltung" aktivieren
4. Neuer Menüpunkt **Dienstplan** erscheint in der linken Navigation

### Berechtigungen verstehen

Das Plugin arbeitet mit drei Benutzerrollen:

| Rolle | Rechte | Typische Verwendung |
|-------|--------|---------------------|
| **Administrator** | Alle Rechte, System-Einstellungen | IT-Verantwortliche |
| **Vereinsverwalter** | Vereine, Veranstaltungen, Dienste, Mitarbeiter | Vereinsvorstände |
| **Crew-Mitglied** | Nur eigene Dienste anzeigen | Helfer/Volunteers |

> 📸 **Screenshot-Hinweis:** Hier würde ein Screenshot der WordPress-Benutzer-Rollenverwaltung eingefügt werden.

---

## Dashboard

Der zentrale Überblick nach dem Login.

### Funktionen

**Navigation:** Dienstplan → Dashboard

#### Statistik-Karten

- **Aktive Vereine:** Anzahl der Vereine im System
- **Kommende Veranstaltungen:** Geplante Events der nächsten 30 Tage
- **Offene Dienste:** Dienste ohne Besetzung
- **Registrierte Mitarbeiter:** Gesamtanzahl Crew-Mitglieder

#### Quick-Links

- ➕ Neue Veranstaltung erstellen
- 📋 Dienste verwalten
- 👥 Mitarbeiter verwalten
- ⚙️ Einstellungen

#### Letzte Aktivitäten

Zeigt die neuesten 10 Aktionen im System:
- Neue Anmeldungen
- Erstellte Dienste
- Geänderte Veranstaltungen

> 📸 **Screenshot-Hinweis:** Dashboard mit allen Statistiken und Quick-Links

---

## Vereine verwalten

Vereine sind die Organisationseinheiten für Veranstaltungen.

### Navigation

**Dienstplan** → **Vereine**

### Neuen Verein erstellen

1. Button **+ Neuer Verein** klicken
2. Modal-Fenster öffnet sich
3. Pflichtfelder ausfüllen:
   - **Name:** Offizieller Vereinsname
   - **Beschreibung:** Kurze Info zum Verein
   - **Kontakt-Email:** Hauptansprechpartner
   - **Telefon:** Optional
   - **Adresse:** Optional
   - **Website:** Optional
4. **Status:** Aktiv/Inaktiv wählen
5. Button **Speichern** klicken

> 📸 **Screenshot-Hinweis:** Vereine-Übersicht mit Tabelle und "Neuer Verein" Button

> 📸 **Screenshot-Hinweis:** Modal-Fenster "Verein hinzufügen" mit allen Feldern

### Verein bearbeiten

1. In der Vereine-Tabelle auf **Bearbeiten** (Stift-Symbol) klicken
2. Daten im Modal anpassen
3. **Speichern** klicken

### Verein löschen

1. In der Tabelle auf **Löschen** (Papierkorb-Symbol) klicken
2. Sicherheitsabfrage bestätigen

⚠️ **Wichtig:** Vereine mit zugeordneten Veranstaltungen können nicht gelöscht werden!

### Vereine filtern

- **Suchfeld:** Name oder Kontakt eingeben
- **Status-Filter:** Aktiv/Inaktiv/Alle

### Tabellen-Spalten

| Spalte | Bedeutung |
|--------|-----------|
| **Name** | Vereinsname |
| **Kontakt** | Email und Telefon |
| **Veranstaltungen** | Anzahl zugeordneter Events |
| **Status** | Aktiv (grün) / Inaktiv (rot) |
| **Aktionen** | Bearbeiten / Löschen |

---

## Veranstaltungen verwalten

Veranstaltungen (Events) sind Termine mit mehreren Diensten.

### Navigation

**Dienstplan** → **Veranstaltungen**

### Neue Veranstaltung erstellen

1. Button **+ Neue Veranstaltung** klicken
2. Modal-Formular ausfüllen:

#### Pflichtfelder

- **Titel:** Name der Veranstaltung (z.B. "Sommerfest 2025")
- **Verein:** Aus Dropdown wählen
- **Datum:** Veranstaltungsdatum (TT.MM.JJJJ)
- **Von/Bis:** Uhrzeiten (HH:MM)

#### Optionale Felder

- **Beschreibung:** Details zur Veranstaltung
- **Ort:** Veranstaltungsort
- **Max. Teilnehmer:** Erwartete Besucherzahl
- **Anmeldeschluss:** Datum bis Crew sich anmelden kann
- **Kontaktperson:** Verantwortlicher vor Ort
- **Kontakt-Email/Telefon:** Erreichbarkeit
- **Status:** Geplant / Aktiv / Abgeschlossen / Abgesagt

3. **Speichern** klicken

> 📸 **Screenshot-Hinweis:** Veranstaltungen-Übersicht mit Filter-Optionen

> 📸 **Screenshot-Hinweis:** Modal "Veranstaltung hinzufügen" mit allen Feldern

### Veranstaltung bearbeiten

1. Auf **Bearbeiten** (Stift-Symbol) klicken
2. Daten anpassen
3. **Speichern**

### Veranstaltung duplizieren

1. Auf **Duplizieren** (Kopie-Symbol) klicken
2. System erstellt Kopie mit "_Kopie" im Titel
3. Datum und Details anpassen

💡 **Tipp:** Praktisch für wiederkehrende Events!

### Veranstaltung löschen

1. Auf **Löschen** (Papierkorb) klicken
2. Bestätigen

⚠️ **Wichtig:** Alle zugeordneten Dienste werden ebenfalls gelöscht!

### Filter & Suche

- **Verein:** Dropdown-Filter
- **Status:** Alle / Geplant / Aktiv / Abgeschlossen / Abgesagt
- **Zeitraum:** Von/Bis-Datum
- **Suchfeld:** Titel oder Ort

### Dienste zur Veranstaltung hinzufügen

1. Veranstaltung öffnen/bearbeiten
2. Zur Ansicht **Dienste** wechseln (Reiter)
3. Button **+ Dienst hinzufügen** klicken
4. Dienst-Details eingeben (siehe [Dienste verwalten](#dienste-verwalten))

---

## Dienste verwalten

Dienste sind die einzelnen Schichten/Aufgaben bei einer Veranstaltung.

### Navigation

**Dienstplan** → **Dienste**

Oder innerhalb einer Veranstaltung → Reiter **Dienste**

### Neuen Dienst erstellen

1. Button **+ Neuer Dienst** klicken
2. Modal-Formular ausfüllen:

#### Pflichtfelder

- **Veranstaltung:** Aus Dropdown wählen
- **Bereich:** Z.B. "Einlass", "Catering", "Technik"
- **Tätigkeit:** Z.B. "Ticketkontrolle", "Ausschank"
- **Von/Bis:** Dienstzeiten (HH:MM)
- **Anzahl Personen:** Wie viele Helfer benötigt?

#### Optionale Felder

- **Beschreibung:** Detaillierte Aufgabenbeschreibung
- **Anforderungen:** Z.B. "Erfahrung wünschenswert"
- **Treffpunkt:** Wo soll sich die Crew melden?
- **Status:** Offen / Besetzt / Abgeschlossen

#### Erweiterte Optionen

- **✅ Splittbar:** Dienst kann geteilt werden (2x halbe Schichten)
- **✅ Priorität:** Dienst als wichtig markieren

3. **Speichern** klicken

> 📸 **Screenshot-Hinweis:** Dienste-Übersicht mit Zeitstrahlen-Ansicht

> 📸 **Screenshot-Hinweis:** Modal "Dienst hinzufügen" mit allen Feldern

### Split-Dienste

Split-Dienste ermöglichen zwei Personen, sich einen Dienst zu teilen.

**Beispiel:**
- Dienst: 18:00 - 02:00 Uhr (8 Stunden)
- Splittbar: Ja
- **Ergebnis:** 
  - Teil 1: 18:00 - 23:00 Uhr
  - Teil 2: 23:00 - 02:00 Uhr

**Verwendung:**
1. Beim Erstellen Checkbox "Splittbar" aktivieren
2. Im Frontend können sich Crew-Mitglieder für einen Teil anmelden
3. System erstellt automatisch zwei Zeitfenster

💡 **Tipp:** Ideal für lange Nachtschichten!

### Dienst bearbeiten

1. Auf **Bearbeiten** klicken
2. Änderungen vornehmen
3. **Speichern**

⚠️ **Achtung:** Bei bereits zugewiesenen Diensten Mitarbeiter informieren!

### Dienst duplizieren

1. Auf **Duplizieren** klicken
2. Zeiten anpassen
3. **Speichern**

### Besetzung verwalten

1. In der Dienste-Tabelle auf **Besetzung** (Personen-Symbol) klicken
2. Modal zeigt alle Slots:
   - **Freie Slots:** Grau, "Nicht zugewiesen"
   - **Besetzte Slots:** Grün mit Mitarbeiter-Namen

3. Mitarbeiter zuweisen:
   - Dropdown **Mitarbeiter auswählen**
   - **Zuweisen** klicken

4. Zuweisung entfernen:
   - Bei besetztem Slot auf **Entfernen** klicken

> 📸 **Screenshot-Hinweis:** Besetzungs-Modal mit freien und besetzten Slots

### Bulk-Operationen

Mehrere Dienste gleichzeitig bearbeiten:

1. Checkboxen bei gewünschten Diensten aktivieren
2. Dropdown **Aktion wählen:**
   - Status ändern
   - Bereich ändern
   - Löschen
3. **Ausführen** klicken

### Filter & Ansichten

#### Filter
- **Veranstaltung:** Dropdown
- **Bereich:** Dropdown
- **Status:** Alle / Offen / Besetzt / Abgeschlossen
- **Datum:** Von/Bis

#### Ansichten
- **📋 Tabelle:** Übersicht mit allen Details
- **📅 Kalender:** Zeitstrahl nach Datum
- **👥 Besetzung:** Fokus auf Zuweisungen

---

## Mitarbeiter verwalten

Crew-Mitglieder (Helfer/Volunteers) im System verwalten.

### Navigation

**Dienstplan** → **Mitarbeiter**

### Neuen Mitarbeiter anlegen

1. Button **+ Neuer Mitarbeiter** klicken
2. Formular ausfüllen:

#### Pflichtfelder
- **Vorname**
- **Nachname**
- **E-Mail**

#### Optionale Felder
- **Telefon**
- **Adresse**
- **PLZ / Ort**
- **Notfallkontakt:** Name und Telefon
- **Qualifikationen:** Z.B. "Erste Hilfe", "Staplerschein"
- **Verfügbarkeit:** Notizen zu zeitlicher Verfügbarkeit
- **Verein:** Zuordnung zu Verein

#### Datenschutz
- **✅ Datenschutz akzeptiert:** Muss aktiviert sein
- **✅ Aktiv:** Mitarbeiter kann sich anmelden

3. **Speichern** klicken

> 📸 **Screenshot-Hinweis:** Mitarbeiter-Übersicht mit Tabelle

> 📸 **Screenshot-Hinweis:** Modal "Mitarbeiter hinzufügen"

### Mitarbeiter bearbeiten

1. Auf **Bearbeiten** klicken
2. Daten anpassen
3. **Speichern**

### Mitarbeiter-Dienste anzeigen

1. Auf **Dienste** (Kalender-Symbol) klicken
2. Modal zeigt alle Dienste des Mitarbeiters:
   - Vergangene Dienste
   - Kommende Dienste
   - Gesamt-Statistik (Stunden)

> 📸 **Screenshot-Hinweis:** Mitarbeiter-Dienste-Modal mit Historie

### Mitarbeiter löschen

1. Auf **Löschen** klicken
2. Bestätigen

⚠️ **Wichtig:** Mitarbeiter mit aktiven Diensten können nicht gelöscht werden!

### Import & Export

Siehe [Import & Export](#import-export)

### Tabellen-Spalten

| Spalte | Bedeutung |
|--------|-----------|
| **Name** | Vor- und Nachname |
| **E-Mail** | Kontakt-Email |
| **Telefon** | Telefonnummer |
| **Verein** | Zugeordneter Verein |
| **Dienste** | Anzahl absolvierter Dienste |
| **Letzte Aktivität** | Letzter Dienst |
| **Status** | Aktiv/Inaktiv |
| **Aktionen** | Bearbeiten / Dienste / Löschen |

---

## Bereiche & Tätigkeiten

Kategorien für Dienste definieren.

### Navigation

**Dienstplan** → **Bereiche & Tätigkeiten**

### Bereiche

Bereiche sind übergeordnete Kategorien (z.B. "Einlass", "Catering", "Technik").

#### Neuen Bereich erstellen

1. Reiter **Bereiche**
2. Button **+ Neuer Bereich** klicken
3. Eingeben:
   - **Name:** Bereichsname
   - **Beschreibung:** Optional
   - **Farbe:** Für visuelle Unterscheidung
4. **Speichern**

#### Bereich bearbeiten/löschen

- **Bearbeiten:** Stift-Symbol
- **Löschen:** Papierkorb (nur wenn keine Dienste zugeordnet)

> 📸 **Screenshot-Hinweis:** Bereiche-Verwaltung mit Farb-Chips

### Tätigkeiten

Tätigkeiten sind spezifische Aufgaben innerhalb eines Bereichs.

#### Neue Tätigkeit erstellen

1. Reiter **Tätigkeiten**
2. Button **+ Neue Tätigkeit** klicken
3. Eingeben:
   - **Name:** Tätigkeitsname
   - **Bereich:** Zuordnung zum Bereich
   - **Beschreibung:** Optional
4. **Speichern**

#### Tätigkeit bearbeiten/löschen

- **Bearbeiten:** Stift-Symbol
- **Löschen:** Papierkorb (nur wenn keine Dienste zugeordnet)

### Standard-Bereiche (Beispiele)

| Bereich | Tätigkeiten |
|---------|-------------|
| **Einlass** | Ticketkontrolle, Garderobe, Einlasskontrolle |
| **Catering** | Ausschank, Spüldienst, Essensausgabe |
| **Technik** | Ton, Licht, Bühne, Kamera |
| **Auf-/Abbau** | Aufbau, Abbau, Logistik |
| **Security** | Ordnerdienst, Personenschutz |
| **Service** | Information, Betreuung VIPs |

> 📸 **Screenshot-Hinweis:** Tätigkeiten-Verwaltung mit Bereich-Zuordnung

---

## Import & Export

Daten per CSV importieren oder exportieren.

### Navigation

**Dienstplan** → **Import & Export**

### Mitarbeiter importieren

1. Reiter **Import**
2. CSV-Datei vorbereiten mit Spalten:
   ```
   vorname,nachname,email,telefon,verein_id
   Max,Mustermann,max@example.com,0123456789,1
   Anna,Beispiel,anna@example.com,0987654321,1
   ```

3. Button **Datei auswählen** klicken
4. CSV hochladen
5. **Import starten** klicken
6. Ergebnis-Report prüfen:
   - ✅ Erfolgreich importiert
   - ⚠️ Fehler (z.B. doppelte E-Mail)

> 📸 **Screenshot-Hinweis:** Import-Interface mit Datei-Upload

### Mitarbeiter exportieren

1. Reiter **Export**
2. Filter wählen (optional):
   - Verein
   - Status (Aktiv/Inaktiv)
   - Zeitraum
3. Button **Exportieren** klicken
4. CSV-Datei wird heruntergeladen

### Dienste exportieren

1. Reiter **Export**
2. Bereich **Dienste**
3. Filter:
   - Veranstaltung
   - Zeitraum
   - Bereich
4. **Exportieren** klicken

### Veranstaltungen exportieren

1. Reiter **Export**
2. Bereich **Veranstaltungen**
3. Filter nach Zeitraum
4. **Exportieren** klicken

💡 **Tipp:** Exporte eignen sich für:
- Backup der Daten
- Reporting
- Externe Auswertungen (Excel)
- Übergabe an Dritte

---

## Einstellungen

System-Konfiguration und Anpassungen.

### Navigation

**Dienstplan** → **Einstellungen**

### Allgemein

#### E-Mail-Einstellungen

- **Absender-Name:** Name für System-E-Mails
- **Absender-E-Mail:** Absender-Adresse
- **BCC-Empfänger:** Optional für Kopien

#### Benachrichtigungen

- **✅ Neue Anmeldung:** Admin bei neuer Dienst-Anmeldung benachrichtigen
- **✅ Dienst-Erinnerung:** Mitarbeiter 24h vorher erinnern
- **✅ Änderungen:** Bei Dienst-Änderungen informieren

> 📸 **Screenshot-Hinweis:** Einstellungen-Seite mit E-Mail-Konfiguration

### Frontend-Anzeige

- **Veranstaltungen pro Seite:** Anzahl in Listen-Ansicht
- **Standard-Ansicht:** Kalender / Liste / Compact
- **✅ Anmeldung aktiviert:** Crew kann sich selbst anmelden
- **✅ Split-Dienste erlauben:** Teilung von Diensten möglich

### Datenschutz

- **Datenschutz-URL:** Link zur Datenschutzerklärung
- **Impressum-URL:** Link zum Impressum
- **Aufbewahrungsfrist:** Löschung inaktiver Mitarbeiter nach X Monaten

### Erweitert

- **Debug-Modus:** Aktiviert ausführliches Logging
- **Cache-Laufzeit:** Performance-Optimierung
- **API-Zugriff:** Token für externe Systeme

---

## Tipps & Best Practices

### 🎯 Workflow für neue Veranstaltung

1. **Verein prüfen/anlegen**
2. **Veranstaltung erstellen** mit allen Details
3. **Bereiche & Tätigkeiten** prüfen, ggf. ergänzen
4. **Dienste anlegen** mit realistischen Zeitfenstern
5. **Anmeldeschluss** setzen (ca. 1 Woche vor Event)
6. **Frontend-Link** an Crew senden
7. **Besetzung überwachen** und bei Bedarf nachhaken
8. **Vor dem Event:** Kontaktliste exportieren
9. **Nach dem Event:** Status auf "Abgeschlossen" setzen

### 📧 Kommunikation mit der Crew

- **Initial:** Veranstaltungs-Link per E-Mail/Newsletter
- **Erinnerung:** 1 Woche vor Anmeldeschluss
- **Bestätigung:** Automatische E-Mail nach Anmeldung
- **24h vorher:** Erinnerung mit Treffpunkt-Details
- **Danke:** Nach Event Dankesmail mit Feedback-Bitte

### ⚡ Effizienz-Tipps

- **Templates:** Standarddienste als Vorlage duplizieren
- **Bulk-Edit:** Mehrere Dienste gleichzeitig bearbeiten
- **Filter nutzen:** Spart Zeit bei großen Events
- **Shortcuts:** Browser-Favoriten für häufige Seiten
- **Mobile:** Responsive Design auch auf Tablet/Handy nutzbar

### 🔒 Sicherheit

- **Rollen-Prinzip:** Nur nötige Rechte vergeben
- **Regelmäßige Backups:** Datenbank exportieren
- **Datenschutz beachten:** DSGVO-konform arbeiten
- **E-Mails prüfen:** Keine Spam-Adressen in System aufnehmen

### 🐛 Problembehandlung

#### Dienst wird nicht angezeigt
- Status prüfen (muss "Offen" oder "Besetzt" sein)
- Veranstaltungs-Datum in der Zukunft?
- Cache leeren (Strg+F5)

#### Mitarbeiter kann sich nicht anmelden
- Status "Aktiv"?
- Datenschutz akzeptiert?
- Dienst noch verfügbar?
- Browser-Cookies aktiviert?

#### E-Mails kommen nicht an
- SMTP-Einstellungen prüfen
- Spam-Ordner checken
- WordPress-Mailserver testen

---

## Support & Hilfe

### Debug-Modus

Bei Problemen aktivieren:

1. **Einstellungen** → **Erweitert**
2. **Debug-Modus** aktivieren
3. Fehler reproduzieren
4. **Debug-Log** unter `/wp-content/debug.log` prüfen

### Häufige Fragen (FAQ)

**F: Kann ich gelöschte Dienste wiederherstellen?**  
A: Nein, Löschungen sind endgültig. Regelmäßig exportieren!

**F: Wie viele Mitarbeiter kann das System verwalten?**  
A: Theoretisch unbegrenzt, getestet bis 10.000+

**F: Funktioniert das Plugin mit anderen Themes?**  
A: Ja, Theme-unabhängig durch separate Templates

**F: Kann ich eigene E-Mail-Templates verwenden?**  
A: Ja, durch Filter-Hooks anpassbar (für Entwickler)

**F: Mehrsprachigkeit?**  
A: Plugin ist deutsch, Übersetzungen via .po/.mo-Dateien möglich

### Kontakt

- **Dokumentation:** `/wp-content/plugins/dienstplan-verwaltung/documentation/`
- **Changelog:** `CHANGELOG.md`
- **GitHub:** [Repository-Link wenn vorhanden]

---

**Letzte Aktualisierung:** November 2025  
**Plugin-Version:** 0.4.7

# Test-Plan für Version 0.4.7

## ⚠️ Vor dem Test: Migration durchführen
- [ ] **migrate-mitarbeiter-id.php ausführen**
  - URL: http://feg.test/wp-content/plugins/dienstplan-verwaltung/migrate-mitarbeiter-id.php
  - Prüfen: "Spalte mitarbeiter_id erfolgreich hinzugefügt"
  - Prüfen: Tabellenstruktur zeigt mitarbeiter_id

## Vor dem Release zu testen

### 🔴 KRITISCH (Muss funktionieren)

#### 0. Admin Modal-Funktionen (NEU in 0.4.7)

##### Vereine
- [ ] **Verein Modal öffnen (Neu)**
  - Button "Neuer Verein" klicken
  - Prüfen: Modal öffnet sich
  - Prüfen: Titel zeigt "Neuer Verein"
  
- [ ] **Verein bearbeiten**
  - Dropdown-Button bei Verein klicken
  - "Bearbeiten" wählen
  - Prüfen: Modal öffnet mit vorausgefüllten Daten
  - Prüfen: Titel zeigt "Verein bearbeiten"
  
- [ ] **Verein speichern**
  - Verein-Daten eingeben (Name, Kürzel)
  - "Speichern" klicken
  - Prüfen: Erfolgsmeldung
  - Prüfen: Seite reload, Verein erscheint in Liste
  
- [ ] **Verein löschen**
  - Dropdown → "Löschen"
  - Prüfen: Bestätigungsdialog
  - Bestätigen
  - Prüfen: Verein wird entfernt

##### Veranstaltungen
- [ ] **Veranstaltung Modal öffnen (Neu)**
  - Button "Neue Veranstaltung" klicken
  - Prüfen: Modal öffnet sich
  
- [ ] **Veranstaltung bearbeiten**
  - Dropdown → "Bearbeiten"
  - Prüfen: Modal öffnet mit Daten
  - Prüfen: Tags werden geladen
  
- [ ] **Tag hinzufügen**
  - Button "Tag hinzufügen" klicken
  - Prüfen: Neues Tag-Feld erscheint
  - Prüfen: Datum + Zeiten editierbar
  
- [ ] **Tag entfernen**
  - Bei Tag "Entfernen" klicken
  - Prüfen: Tag-Feld verschwindet
  
- [ ] **Veranstaltung speichern**
  - Daten eingeben
  - "Speichern" klicken
  - Prüfen: Erfolgsmeldung + Reload
  
- [ ] **Veranstaltung löschen**
  - Dropdown → "Löschen"
  - Prüfen: Warnung (Dienste werden gelöscht)
  - Bestätigen
  - Prüfen: Veranstaltung entfernt

##### Dienste
- [ ] **Dienst Modal öffnen (Neu)**
  - Button "Neuer Dienst" klicken
  - Prüfen: Modal öffnet sich
  
- [ ] **Dienst bearbeiten**
  - Dropdown bei Dienst → "Bearbeiten"
  - Prüfen: Modal mit vorausgefüllten Daten
  
- [ ] **Dienst speichern**
  - Alle Pflichtfelder ausfüllen
  - "Speichern" klicken
  - Prüfen: Erfolgsmeldung + Reload
  
- [ ] **Dienst löschen**
  - Dropdown → "Löschen"
  - Prüfen: Bestätigung
  - Prüfen: Dienst wird entfernt

##### Bereiche & Tätigkeiten
- [ ] **Bereich Modal öffnen (Neu)**
  - Button "Neuer Bereich" klicken
  - Prüfen: Modal öffnet sich
  
- [ ] **Bereich speichern**
  - Name + Farbe eingeben
  - "Speichern" klicken
  - Prüfen: Erfolgsmeldung + Reload
  
- [ ] **Tätigkeit Modal öffnen (Neu)**
  - In Bereich "+ Neue Tätigkeit" klicken
  - Prüfen: Modal öffnet sich
  - Prüfen: bereich_id ist vorausgewählt
  
- [ ] **Tätigkeit bearbeiten**
  - Dropdown bei Tätigkeit → "Bearbeiten"
  - Prüfen: Modal mit Daten
  
- [ ] **Tätigkeit löschen**
  - Dropdown → "Löschen"
  - Prüfen: Warnung wenn Verwendung vorhanden
  - Bei ungenutzt: Prüfen Löschen funktioniert

##### Mitarbeiter (bereits vorhanden - Regression Test)
- [ ] **Mitarbeiter Modal öffnen**
  - Button "Neuer Mitarbeiter" klicken
  - Prüfen: Modal öffnet sich
  
- [ ] **Mitarbeiter-Dienste anzeigen**
  - Dropdown → "Dienste anzeigen"
  - Prüfen: Modal mit Dienst-Liste

#### 1. Frontend Dienst-Anmeldung mit Split (NEU in 0.4.7)

- [ ] **Normale Anmeldung (ohne Split)**
  - Veranstaltung im Frontend öffnen
  - Bei Dienst "Für Dienst anmelden" klicken
  - Vorname, Nachname eingeben (Email optional lassen)
  - "Anmelden" klicken
  - Prüfen: Erfolgsmeldung
  - Prüfen: Seite reload
  - Prüfen: Dienst zeigt 1 Person angemeldet

- [ ] **Split-Anmeldung: Teil 1**
  - "Für Dienst anmelden" klicken
  - Checkbox "Ich möchte den Dienst teilen" aktivieren
  - Prüfen: Radio-Buttons erscheinen
  - "1. Teil" wählen
  - Daten eingeben + "Anmelden"
  - Prüfen: 2 neue Dienste erscheinen
  - Prüfen: Teil 1 hat halbe Zeit (von_zeit bis Mitte)
  - Prüfen: Teil 2 hat halbe Zeit (Mitte bis bis_zeit)
  - Prüfen: Besonderheiten enthält "[Teil 1 - gesplittet]" bzw. "[Teil 2 - gesplittet]"
  - Prüfen: Anmeldung ist bei Teil 1

- [ ] **Split-Anmeldung: Teil 2**
  - Bei gesplittetem Dienst "Teil 2" anmelden
  - Checkbox "Split" aktivieren + "2. Teil" wählen
  - Prüfen: Anmeldung erfolgt bei Teil 2
  - Prüfen: Kein weiterer Split (bereits gesplittet)

- [ ] **Email optional**
  - Anmeldung ohne Email-Eingabe
  - Prüfen: Funktioniert (kein Fehler)

- [ ] **Telefon-Feld**
  - Prüfen: Telefon-Feld ist vorhanden
  - Prüfen: Optional (nicht required)

- [ ] **Duplikat-Prüfung**
  - Dienst bereits gesplittet (besonderheiten enthält "gesplittet")
  - Erneut versuchen zu splitten
  - Prüfen: Wird nicht nochmal gesplittet

#### 2. Datenbank-Konsistenz (NEU in 0.4.7)

- [ ] **Mitarbeiter ohne erstellt_am**
  - Neuen Mitarbeiter via Frontend anlegen
  - In Datenbank prüfen: `SELECT * FROM wp_dp_mitarbeiter ORDER BY id DESC LIMIT 1`
  - Prüfen: Spalte erstellt_am existiert NICHT (Fehler bei SELECT)
  - ODER: Wenn vorhanden, Wert ist NULL

- [ ] **dienst_zuweisungen mit mitarbeiter_id**
  - Nach Dienst-Anmeldung in DB prüfen: `SELECT * FROM wp_dp_dienst_zuweisungen ORDER BY id DESC LIMIT 1`
  - Prüfen: Spalte mitarbeiter_id existiert
  - Prüfen: Wert ist gesetzt (nicht NULL)
  - Prüfen: Spalte eingetragen_am hat Wert
  - Prüfen: status = 'bestaetigt'

- [ ] **Dienste ohne datum-Feld**
  - `SELECT * FROM wp_dp_dienste LIMIT 1`
  - Prüfen: Spalte 'datum' existiert NICHT
  - Prüfen: tag_id ist gesetzt

#### 3. Bereiche & Tätigkeiten
- [ ] **Bereich erstellen**
  - Neuen Bereich mit Name und Farbe anlegen
  - Prüfen: Wird in Liste angezeigt
  
- [ ] **Bereich bearbeiten**
  - Vorhandenen Bereich öffnen
  - Name und Farbe ändern
  - Prüfen: Änderungen werden gespeichert

- [ ] **Bereich löschen**
  - Bereich ohne Tätigkeiten löschen
  - Prüfen: Wird aus Liste entfernt
  - Bereich MIT Tätigkeiten löschen
  - Prüfen: Warnung erscheint, alle Tätigkeiten werden gelöscht

- [ ] **Tätigkeit erstellen**
  - Neue Tätigkeit in Bereich anlegen
  - Prüfen: Wird in Bereichs-Tabelle angezeigt

- [ ] **Tätigkeit bearbeiten**
  - Vorhandene Tätigkeit öffnen
  - Name und Beschreibung ändern
  - Prüfen: Änderungen werden gespeichert

- [ ] **Tätigkeit löschen**
  - Tätigkeit OHNE Verwendung löschen
  - Prüfen: Wird entfernt
  - Tätigkeit MIT Verwendung löschen
  - Prüfen: Button ist disabled mit Hinweis

- [ ] **Bulk-Aktionen Tätigkeiten**
  - 3 Tätigkeiten auswählen
  - Alle löschen
  - Prüfen: Alle werden entfernt
  - 2 Tätigkeiten auswählen
  - Bereich verschieben
  - Prüfen: Erscheinen in neuem Bereich
  - 2 Tätigkeiten auswählen
  - Status ändern (deaktivieren)
  - Prüfen: Status wird aktualisiert

#### 2. Dienste Bulk-Aktionen
- [ ] **Mehrere Dienste auswählen**
  - 3 Dienste mit Checkbox markieren
  - Prüfen: Toolbar erscheint, Zähler zeigt "3 ausgewählt"

- [ ] **Bulk-Löschen**
  - 2 Dienste auswählen
  - Aktion "Löschen" wählen
  - Prüfen: Bestätigung erscheint
  - Bestätigen
  - Prüfen: Dienste werden entfernt

- [ ] **Bulk-Tag verschieben**
  - 2 Dienste auswählen
  - "Tag verschieben" wählen
  - Neuen Tag wählen
  - Prüfen: Dienste erscheinen bei neuem Tag

- [ ] **Bulk-Status ändern**
  - 2 Dienste auswählen
  - Status auf "bestätigt" ändern
  - Prüfen: Status wird aktualisiert

#### 3. Import/Export
- [ ] **Vereine exportieren**
  - Button "Vereine exportieren" klicken
  - Prüfen: CSV-Download startet
  - CSV öffnen
  - Prüfen: Enthält ID, Name, Kürzel, Kontakte

- [ ] **Veranstaltungen exportieren**
  - Button klicken
  - Prüfen: CSV enthält Name, Datum, Beschreibung

- [ ] **Dienste exportieren**
  - Button klicken
  - Prüfen: CSV enthält Tag-Nummer, Verein, Bereich, Tätigkeit, Zeiten, Status

- [ ] **CSV importieren**
  - Beispiel-CSV für Dienste vorbereiten
  - Import starten
  - Prüfen: Dienste werden korrekt angelegt

#### 4. Übersicht (Overview)
- [ ] **Veranstaltung wählen**
  - Veranstaltung aus Dropdown wählen
  - Prüfen: Alle Tage werden angezeigt

- [ ] **Tag-Gruppierung**
  - Prüfen: Dienste sind nach Tagen gruppiert
  - Prüfen: Kollabierbar

- [ ] **Dienste ohne Tag**
  - Dienst ohne tag_id erstellen (manuell in DB)
  - Prüfen: Erscheint in separatem Bereich "Dienste ohne Tag"

- [ ] **Scrolling**
  - Viele Mitarbeiter zu einem Dienst hinzufügen
  - Prüfen: Linke Spalten fixiert, Mitarbeiter scrollen

### 🟡 WICHTIG (Sollte funktionieren)

#### 5. Zeit-Handling
- [ ] **Zeit-Normalisierung**
  - Dienst mit Zeit "19.00" erstellen
  - Prüfen: Wird zu "19:00:00" konvertiert

- [ ] **Overnight-Dienste**
  - Dienst von "22:00" bis "02:00" erstellen
  - Prüfen: bis_datum wird automatisch auf +1 Tag gesetzt
  - Prüfen: Visueller Indikator "+1 Tag" wird angezeigt

- [ ] **Validation**
  - Dienst von "22:00" bis "20:00" OHNE Overnight erstellen
  - Prüfen: Fehler "Start muss vor Ende liegen"

#### 6. Status-System
- [ ] **Status "unvollständig"**
  - Dienst mit fehlender Zeit erstellen
  - Prüfen: Status wird automatisch auf "unvollständig" gesetzt
  - Prüfen: Gelbe Kennzeichnung

- [ ] **Status manuell ändern**
  - Status auf "bestätigt" setzen
  - Prüfen: Wird gespeichert
  - Prüfen: Keine gelbe Kennzeichnung mehr

### 🟢 OPTIONAL (Nice to have)

#### 7. UI/UX
- [ ] **Modals**
  - Bereiche/Tätigkeiten Modals öffnen/schließen
  - Prüfen: Smooth Animations
  - ESC-Taste zum Schließen
  - Klick außerhalb schließt Modal

- [ ] **Bulk-Toolbar**
  - Prüfen: Erscheint/verschwindet smooth
  - "Alle auswählen" funktioniert
  - "Abbrechen" löscht Auswahl

- [ ] **Responsive**
  - Auf kleinerem Bildschirm testen
  - Prüfen: Tabellen scrollbar
  - Prüfen: Buttons nicht abgeschnitten

#### 8. Performance
- [ ] **Viele Dienste**
  - 100+ Dienste in Übersicht laden
  - Prüfen: Lädt in < 3 Sekunden
  - Prüfen: Scrolling flüssig

- [ ] **Bulk-Aktionen**
  - 50 Dienste auf einmal löschen
  - Prüfen: Funktioniert ohne Timeout

---

## Gefundene Bugs dokumentieren

### Bug-Template:
```
**Bug:** [Kurze Beschreibung]
**Schritte:**
1. ...
2. ...
**Erwartet:** [Was sollte passieren]
**Tatsächlich:** [Was passiert]
**Priorität:** Kritisch/Hoch/Mittel/Niedrig
**Status:** Offen/In Arbeit/Behoben
```

### Bekannte Bugs (bereits behoben in 0.4.7):
- ✅ CSV-Export: Array-Syntax statt Objekt-Syntax
- ✅ Overview: Falsche Tag-Gruppierung
- ✅ Zeit-Validation: 19.00 - 01:00 wurde abgelehnt
- ✅ Duplikat Database-Klassen
- ✅ Mitarbeiter-Tabelle: erstellt_am Spalte existierte nicht real
- ✅ dienst_zuweisungen: mitarbeiter_id Spalte fehlte
- ✅ Falsche Tabellennamen: wp_dp_tags → wp_dp_veranstaltung_tage
- ✅ Falsche Spaltennamen: dienste.datum, mitarbeiter.rolle, mitarbeiter.aktiv
- ✅ Split-Dienst Verdreifachung (Duplikat-Prüfung verbessert)
- ✅ dp-public.js: Doppelter Code mit Syntax-Fehler
- ✅ Fehlende Modal-Funktionen: Alle CRUD-Operationen implementiert

---

## Regression Tests

### Funktionen die NICHT kaputt gehen dürfen:
- [ ] Dienst erstellen (Standard-Funktion)
- [ ] Dienst bearbeiten
- [ ] Dienst löschen (einzeln)
- [ ] Slot-Verwaltung (Mitarbeiter zuweisen)
- [ ] Mitarbeiter erstellen
- [ ] Verein erstellen/bearbeiten
- [ ] Veranstaltung erstellen/bearbeiten

---

## Browser-Kompatibilität

Testen in:
- [ ] Chrome (neueste Version)
- [ ] Firefox (neueste Version)
- [ ] Safari (macOS/iOS)
- [ ] Edge (neueste Version)

---

## Checkliste vor Release

- [x] Versionsnummer aktualisiert (0.4.7)
- [x] CHANGELOG.md aktualisiert
- [x] STRUCTURE.md aktualisiert
- [x] DATABASE_STRUCTURE_AKTUELL.md erstellt (550+ Zeilen)
- [x] TEST_PLAN.md aktualisiert
- [x] Migration für mitarbeiter_id erstellt
- [x] Alle Datenbank-Inkonsistenzen behoben
- [x] Alle Modal-Funktionen implementiert (dp-admin-modals.js)
- [x] Split-Dienst-Funktion implementiert
- [ ] Alle kritischen Tests durchgeführt ✅
- [ ] Keine bekannten kritischen Bugs
- [ ] README.md aktualisiert
- [ ] Backup der aktuellen DB erstellt
- [ ] Plugin als ZIP exportiert

---

## Nach dem Release

- [ ] In Produktions-Umgebung installieren
- [ ] Smoke-Test (Grundfunktionen prüfen)
- [ ] Feedback von Benutzern sammeln
- [ ] Bugs in Issue-Tracker eintragen

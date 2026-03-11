# Bedienungsanleitung Frontend - Dienstplan-Verwaltung

**Version:** 0.9.5.5  
**Stand:** 11. März 2026  
**Zielgruppe:** Crew-Mitglieder und Helfer

---

## Inhaltsverzeichnis

1. [Erste Schritte](#erste-schritte)
2. [Überblick der Veranstaltungsseite](#überblick-der-veranstaltungsseite)
3. [Die drei Ansichten](#die-drei-ansichten)
4. [Filter nutzen](#filter-nutzen)
5. [Für Dienste anmelden](#für-dienste-anmelden)
6. [Split-Dienste](#split-dienste)
7. [Meine Dienste](#meine-dienste)
8. [Dienst absagen](#dienst-absagen)
9. [Profil verwalten](#profil-verwalten)
10. [Tipps & FAQ](#tipps-faq)

---

## Erste Schritte

### Was ist die Dienstplan-Verwaltung?

Das System ermöglicht dir, dich für Helferdienste bei Veranstaltungen anzumelden. Du siehst:
- Welche Events geplant sind
- Welche Dienste noch frei sind
- Deine persönlichen Dienste im Überblick

### Wie komme ich zum Frontend?

Dein Vereinsverantwortlicher sendet dir einen Link, z.B.:
```
https://ihre-website.de/veranstaltungen/
```

Oder du findest das Menü auf der Website unter:
- **Veranstaltungen**
- **Dienstplan**
- **Mitmachen**

> 📸 **Screenshot-Hinweis:** Beispiel-Website mit Dienstplan-Menü

---

## Überblick der Veranstaltungsseite

Nach dem Öffnen der Veranstaltungsseite siehst du:

### Header-Bereich (oben)

**Links:**
- Veranstaltungs-Überschrift & Beschreibung
- Such- und Filter-Funktionen

**Rechts:**
- 🗂️ 📋 📊 **View-Toggle:** Drei Ansicht-Optionen
- Filter-Reset-Button (bei aktiven Filtern)

### Filter-Bereich (oben)

**Intelligente 4-stufige Filter:**

1. **Besetzung-Filter:**
   - ⭕ **Alle:** Alle Dienste anzeigen
   - 🟢 **Nur freie:** Nur unbesetzte Dienste
   - 💙 **Meine Dienste:** Nur deine eingetragenen Dienste

2. **Tag-Filter:**
   - Zeigt nur Tage, die tatsächlich Dienste haben
   - Wähle einen Tag → nur Dienste dieses Tages sichtbar

3. **Arbeitsbereich-Filter:**
   - Zeigt nur Bereiche des ausgewählten Tages
   - Z.B. "Einlass", "Catering", "Dekoration"

4. **Dienst-Filter:**
   - Zeigt nur Tätigkeiten des ausgewählten Bereichs
   - Z.B. "Ticketkontrolle", "Getränkeausgabe"

💡 **Smart-Logik:** Unmögliche Kombinationen sind automatisch deaktiviert!

> 📸 **Screenshot-Hinweis:** Filter-Bereich mit 4 Dropdowns

---

## Die drei Ansichten

### 1️⃣ 🗂️ Kachel-Ansicht (Default)

**Beste für:** Schneller Überblick über alle Tage

**Layout:**
- Tage als Karten nebeneinander
- Pro Tag: Tag, Datum, Art der Tätigkeiten
- Farben zeigen Besetzungs-Status

**Farben:**
- 🟢 **Grün:** Alle Dienste besetzt
- 🔴 **Rot:** Noch freie Dienste verfügbar

**Interaktion:**
- Karte klicken → öffnet Kompakt-Ansicht für diesen Tag

> 📸 **Screenshot-Hinweis:** Kachel-Ansicht mit mehreren Tages-Karten

### 2️⃣ 📋 Kompakt-Ansicht

**Beste für:** Detaillierter Überblick mit Tabelle

**Layout:**
- Tabellarische Darstellung aller Dienste
- Spalten: Zeitfenster, Bereich, Tätigkeit, Freie Plätze
- Sortierbar nach Tag

**Status-Badges:**
- 🟢 **Frei:** Noch Plätze verfügbar
- 🔴 **Besetzt:** Alle Plätze voll

**Interaktion:**
- "Anmelden"-Button pro Dienst
- Zeigt Besetzungs-Details (z.B. "1/3")

> 📸 **Screenshot-Hinweis:** Kompakt-Ansicht mit Tabelle

### 3️⃣ 📊 Timeline-Ansicht (Neu!)

**Beste für:** Visuelle Zeitplanung, Überschneidungen sehen

**Layout:**
- **Tag-Tabs** oben: Schnelle Navigation zwischen Tagen
- **Zeit-Grid:** Stundeneinteilung von min. bis max. Dienst-Zeit
- **Service-Balken:** Farbige Blöcke für jeden Dienst
  - Position = Uhrzeit
  - Länge = Dienst-Dauer
  - Farbe = Status

**Farben & Status:**
- 🟢 **Grün/Besetzt:** Alle Plätze belegt
- 🔴 **Rot/Frei:** Noch Plätze verfügbar

**Interaktive Elemente:**
- Dienst-Balken klicken → Popup mit Details
- "Dienst absagen"-Button (direkt im Balken, wenn es dein Dienst ist)
- Besetzungs-Badge zeigt "2/3" (besetzt/Kapazität)

**Beispiel Timeline:**
```
Tag: Freitag, 15. März 2026

Uhrzeit    |  Bereich  |  Tätigkeit
---------------------------------------
18:00 ---- | Einlass   | [Ticketkontrolle]
          |          | (grün, 3/3 voll)
19:30 ---- | Catering  | [Getränke]
          |          | (rot, 1/3 frei)
22:00 ---- | Dekoration| [Abbau]
          |          | (rot, 2/3 frei)
```

> 📸 **Screenshot-Hinweis:** Timeline-Ansicht mit farbigen Service-Balken

**Vorteile Timeline:**
- ✅ Schnell Zeitkonflikte erkennen
- ✅ Überschneidungen auf einen Blick sehen
- ✅ Visuell leichter zu verstehen
- ✅ Besser für Planung mehrerer Dienste

---

## Filter nutzen

### Besetzungs-Filter

**Dropdown 1: "Besetzung"**

```
Alle           ← Standard, alle Dienste
Nur freie      ← Zeigt nur unbesetzte
Meine Dienste  ← Nur deine eingetragenen
```

**Wirkung:**
- **"Alle":** 100 Dienste sichtbar
- **"Nur freie":** 23 Dienste sichtbar (Rest voll)
- **"Meine Dienste":** 3 Dienste sichtbar (deine Eintragungen)

**Filter zurücksetzen:** Grüner Button "🔄 Filter zurücksetzen" bringt alles auf "Alle"

### Tag-Filter

**Dropdown 2: "Tag"**

Zeigt nur Tage mit verfügbaren Diensten (unter aktuellen Filter-Bedingungen).

**Beispiel:**
- Wenn "Nur freie" ausgewählt: Zeigt nur Tage mit freien Diensten
- "Montag 10.3." hat 5 freie → sichtbar
- "Dienstag 11.3." hat 0 freie → ausgeblendet (grayed out)

### Arbeitsbereich-Filter

**Dropdown 3: "Arbeitsbereich"**

Filterung nach Aufgabenbereichen des ausgewählten Tages.

**Beispiel Freitag der Woche:**
- Einlass (3 Dienste)
- Catering (5 Dienste)
- Dekoration (2 Dienste)

Wähle "Catering" → nur Catering-Dienste sichtbar

### Dienst-Filter

**Dropdown 4: "Dienst"**

Spezifische Tätigkeiten im ausgewählten Bereich.

**Beispiel im Bereich "Catering":**
- Getränkeausgabe
- Speiseausgabe
- Spüldienst

Wähle "Getränkeausgabe" → nur diese Tätigkeit sichtbar

### Intelligente Filter-Logik

**Features:**

✅ **Nur gültige Kombinationen:** Unmögliche Optionen sind automatisch deaktiviert
- Bereich ohne freie Dienste im ausgewählten Tag? → Grayed out
- Tag ohne Dienste in der Besetzungs-Kategorie? → Grayed out

✅ **Automatischer Fallback:** Wenn deine aktuelle Auswahl unmöglich wird
- Z.B. "Nur freie" + "Montag" aber Montag ist alles besetzt
- System schaltet automatisch zurück auf "Alle"

✅ **Filter zurücksetzen:** Button "🔄 Filter zurücksetzen"
- Setzt alle Filter auf "Alle"
- Zeigt wieder alle Dienste

---

## Für Dienste anmelden

### Schritt-für-Schritt

#### 1. Filter setzen (optional)

- "Nur freie" wählen → zeigt nur offene Dienste
- Oder nach Bereich/Tätigkeit filtern

#### 2. Ansicht wählen

Wähle eine der drei Ansichten:
- 🗂️ Kachel (schnell Tage finden)
- 📋 Kompakt (Tabellen-Übersicht)
- 📊 Timeline (Zeitliche Visualisierung)

#### 3. Dienst auswählen

**In Kachel/Kompakt-Ansicht:**
- "Anmelden"-Button bei gewünschtem Dienst klicken

**In Timeline-Ansicht:**
- Auf den farbigen Service-Balken klicken
- Oder "Schnell anmelden"-Button unter dem Dienst

#### 4. Anmeldeformular ausfüllen

Modal-Fenster öffnet sich mit Feldern:

**Pflichtfelder:**
- ✉️ **E-Mail:** Deine Kontakt-Email
- 👤 **Vorname:** Dein Vorname  
- 👤 **Nachname:** Dein Nachname

**Optional:**
- 📞 **Telefon:** Für Rückfragen
- 💬 **Kommentar:** Besondere Hinweise (z.B. Allergie, Behinderung)

**Datenschutz:**
- ☑️ **Ich akzeptiere die Datenschutzerklärung** (Pflicht)

> 📸 **Screenshot-Hinweis:** Anmelde-Modal mit ausgefülltem Formular

#### 5. Anmeldung absenden

Button **"Jetzt anmelden"** klicken.

#### 6. Bestätigung erhalten

**Sofort:**
- ✅ Grüne Erfolgsmeldung erscheint
- Dienst-Status wechselt zu "Voll" oder Plätze reduzieren sich

**Per E-Mail:**
Du erhältst eine Bestätigungs-E-Mail mit:
- Event-Details
- Dienst-Zeiten
- Treffpunkt
- Kontaktdaten
- Link zum Abmelden

💡 **Tipp:** E-Mail aufbewahren! Sie enthält alle wichtigen Infos.

### Was passiert danach?

1. **Deine Anmeldung wird gespeichert**
2. **Administrator wird benachrichtigt**
3. **24h vorher:** Erinnerungs-E-Mail (automatisch)
4. **Am Event-Tag:** Pünktlich am Treffpunkt sein! 😊

---

## Split-Dienste

### Was sind Split-Dienste?

Lange Dienste (z.B. 8 Stunden) können geteilt werden:
- Du übernimmst nur eine Hälfte
- Jemand anderes die andere Hälfte

**Beispiel:**
- Dienst: 18:00 - 02:00 Uhr (8 Stunden)
- **Teil 1:** 18:00 - 23:00 Uhr (5 Stunden)
- **Teil 2:** 23:00 - 02:00 Uhr (3 Stunden)

### Split-Dienst erkennen

Dienste mit **✂️ Symbol** oder Badge **"Splittbar"** können geteilt werden.

> 📸 **Screenshot-Hinweis:** Dienst-Karte mit Split-Symbol

### Für Split-Dienst anmelden

1. Auf **"Anmelden"** bei splittbarem Dienst klicken
2. Modal zeigt **zwei Optionen:**

   **Radio-Buttons:**
   - ⭕ **Erste Hälfte** (18:00 - 23:00)
   - ⭕ **Zweite Hälfte** (23:00 - 02:00)

3. Gewünschte Hälfte wählen
4. Formular ausfüllen
5. **"Jetzt anmelden"** klicken

### Was passiert beim Split?

- System erstellt **zwei separate Dienste**
- Du bist nur für **deine gewählte Hälfte** eingeteilt
- Die andere Hälfte bleibt frei für jemand anderen
- Beide Helfer erhalten separate Bestätigungen

> 📸 **Screenshot-Hinweis:** Split-Dienst-Modal mit Zeitfenster-Auswahl

### Vorteile

- ✅ Kürzere Schichten = weniger Belastung
- ✅ Flexible Zeiteinteilung
- ✅ Mehr Menschen können mitmachen
- ✅ Ideal für Nachtschichten

---

## Meine Dienste

### Übersicht aufrufen

Klicke auf **"Meine Dienste"** im Menü oder Login-Bereich.

### Anmeldung

Du musst eingeloggt sein. Zwei Möglichkeiten:

#### Option 1: WordPress-Login
Falls du ein WordPress-Konto hast:
1. **Anmelden** oben rechts
2. Benutzername & Passwort eingeben
3. **"Meine Dienste"** ist jetzt verfügbar

#### Option 2: Magic-Link (Passwortlos)
Falls du kein Konto hast:
1. **"Meine Dienste"** klicken
2. E-Mail-Adresse eingeben (die du bei Anmeldung genutzt hast)
3. **"Link senden"** klicken
4. E-Mail checken
5. Auf Link in E-Mail klicken
6. Automatisch eingeloggt für 24h

> 📸 **Screenshot-Hinweis:** Magic-Link Login-Formular

### Dashboard "Meine Dienste"

Nach dem Login siehst du:

#### Statistiken

- **Kommende Dienste:** Anzahl zukünftiger Einsätze
- **Absolvierte Dienste:** Anzahl vergangener Einsätze
- **Gesamt-Stunden:** Summe aller Dienststunden

#### Dienst-Liste

**Kommende Dienste** (oben):
- Datum & Uhrzeit
- Veranstaltung
- Bereich & Tätigkeit
- Treffpunkt
- Kontaktperson
- ❌ **Abmelden-Button**

**Vergangene Dienste** (unten):
- Gleiche Infos
- ✅ Grün markiert als "Abgeschlossen"

> 📸 **Screenshot-Hinweis:** "Meine Dienste" Dashboard mit Listen

### Kalender-Export

Button **"📅 Zu Kalender hinzufügen"**:
- Download einer .ics-Datei
- Importierbar in:
  - Google Calendar
  - Apple Kalender
  - Outlook
  - Thunderbird

💡 **Tipp:** So vergisst du keinen Dienst!

---

## Dienst abmelden

### Wann kann ich mich abmelden?

- ✅ **Bis 48h vorher:** Jederzeit möglich
- ⚠️ **Weniger als 48h:** Kontaktiere Administrator

### Schritt-für-Schritt

1. **"Meine Dienste"** öffnen
2. Bei gewünschtem Dienst auf **❌ Abmelden** klicken
3. Bestätigungs-Dialog:
   - **Grund (optional):** Warum meldest du dich ab?
   - ☑️ **Ich verstehe, dass kurzfristige Absagen problematisch sind**
4. **"Abmeldung bestätigen"** klicken

### Was passiert?

- ✅ Du wirst aus dem Dienst entfernt
- ✅ Platz wird wieder frei
- ✅ Administrator wird benachrichtigt
- ✅ Bestätigungs-E-Mail an dich

⚠️ **Wichtig:** 
- Bitte nur im Notfall abmelden!
- Kurzfristige Absagen sind schwer zu kompensieren
- Bei wiederholten Absagen kann Zugang gesperrt werden

### Notfall-Abmeldung

**Am Event-Tag krank/verhindert?**
1. **NICHT** über System abmelden (zu spät!)
2. **SOFORT** Kontaktperson anrufen (Nummer in Bestätigungs-E-Mail)
3. Ersatz organisieren lassen

---

## Profil verwalten

### Profil aufrufen

**"Mein Profil"** im Login-Bereich oder Menü.

### Daten bearbeiten

Du kannst ändern:
- 📧 E-Mail-Adresse
- 📞 Telefonnummer
- 📍 Adresse (optional)
- 🚨 Notfallkontakt
- 📋 Qualifikationen
- 📅 Verfügbarkeit (Notizen)

💡 **Tipp:** Halte deine Daten aktuell! Besonders Telefonnummer für Notfälle.

### E-Mail-Benachrichtigungen

Einstellungen für Benachrichtigungen:

- ☑️ **Neue Veranstaltungen:** Benachrichtigung bei neuen Events
- ☑️ **Dienst-Erinnerung:** 24h vor Dienst erinnern
- ☑️ **Änderungen:** Bei Dienst-Änderungen informieren
- ☑️ **Newsletter:** Allgemeine Infos vom Verein

### Konto löschen

Button **"Konto löschen"** im Profil:
- ⚠️ **Achtung:** Kann nicht rückgängig gemacht werden!
- Alle deine Daten werden gelöscht
- Dienst-Historie wird anonymisiert (für Statistik)

---

## Tipps & FAQ

### 🎯 Best Practices

#### Vor der Anmeldung
- ✅ Termine im eigenen Kalender prüfen
- ✅ Anfahrt/Parkmöglichkeiten checken
- ✅ Beschreibung & Anforderungen lesen

#### Nach der Anmeldung
- ✅ Bestätigungs-E-Mail speichern
- ✅ In eigenen Kalender eintragen
- ✅ Erinnerung 1 Tag vorher setzen
- ✅ Treffpunkt merken

#### Am Event-Tag
- ✅ Pünktlich sein (besser 10 Min früher)
- ✅ Kontaktperson-Nummer griffbereit
- ✅ Bestätigungs-E-Mail dabei (Smartphone)
- ✅ Angemessene Kleidung (siehe Event-Beschreibung)

### ❓ Häufige Fragen

**F: Ich habe die Bestätigungs-E-Mail nicht erhalten**
- Spam-Ordner checken
- Bei "Meine Dienste" einloggen → Dienst ist dort sichtbar
- Falls nicht: Administrator kontaktieren

**F: Kann ich mich für mehrere Dienste am gleichen Tag anmelden?**
- Ja, solange sich die Zeiten nicht überschneiden
- System warnt bei Konflikten

**F: Was ist, wenn ich zu spät komme?**
- Sofort Kontaktperson anrufen (Nummer in E-Mail)
- Verspätung mitteilen
- Wenn möglich: Ersatz organisieren

**F: Bekomme ich eine Aufwandsentschädigung?**
- Das regelt jeder Verein individuell
- Infos in Event-Beschreibung oder bei Administrator nachfragen

**F: Muss ich Vorkenntnisse haben?**
- Bei den meisten Diensten: Nein
- Anforderungen stehen in der Dienst-Beschreibung
- Bei Fragen: Administrator kontaktieren

**F: Kann ich einen Freund mitbringen?**
- Freund muss sich separat anmelden
- Keine "Gastdienste" ohne Anmeldung
- Sicherheit & Versicherung!

**F: Was passiert bei Schlechtwetter/Absage?**
- Du erhältst E-Mail mit Absage-Info
- Dienst wird automatisch storniert
- Keine Verpflichtung für Ersatztermin

**F: Wie erfahre ich von neuen Veranstaltungen?**
- E-Mail-Benachrichtigungen aktivieren (Profil)
- Regelmäßig Website checken
- Social Media des Vereins folgen

### 🔒 Datenschutz

**Welche Daten werden gespeichert?**
- Name, E-Mail, Telefon
- Dienst-Historie (für Statistik)
- Login-Tokens (temporär)

**Wer sieht meine Daten?**
- Administratoren des Systems
- Veranstalter der Events (nur für ihre Events)
- NICHT öffentlich sichtbar

**Wie lange werden Daten gespeichert?**
- Aktive Dienste: Dauerhaft
- Nach Inaktivität: Löschung nach X Monaten (siehe Datenschutzerklärung)

**Kann ich meine Daten löschen?**
- Ja, über "Konto löschen" im Profil
- Oder Anfrage an Administrator

### 📱 Mobile Nutzung

Das System ist voll responsiv:
- ✅ Smartphone (iOS/Android)
- ✅ Tablet
- ✅ Desktop

**Tipps für Smartphone:**
- Landschaft-Modus für bessere Übersicht
- Lesezeichen/Bookmark setzen
- Push-Benachrichtigungen aktivieren (Browser)

### 🐛 Probleme?

**Seite lädt nicht:**
- Internet-Verbindung prüfen
- Browser-Cache leeren (Strg+F5)
- Anderen Browser testen
- Administrator kontaktieren

**Anmelde-Button funktioniert nicht:**
- JavaScript aktiviert?
- Ad-Blocker deaktivieren
- Cookies erlauben
- Inkognito-Modus testen

**Login klappt nicht:**
- E-Mail-Adresse korrekt? (Groß-/Kleinschreibung egal)
- Spam-Ordner für Magic-Link checken
- Link abgelaufen? (24h gültig) → Neuen anfordern

### 📞 Kontakt & Hilfe

**Bei technischen Problemen:**
- Administrator des Vereins kontaktieren
- E-Mail steht in Event-Beschreibung

**Bei Event-spezifischen Fragen:**
- Kontaktperson des Events (siehe Details)

**Bei Notfällen am Event-Tag:**
- Kontaktperson SOFORT anrufen
- NICHT nur E-Mail schreiben!

---

## Checkliste für deinen ersten Dienst

### 📋 Vor dem Event

- [ ] Für Dienst angemeldet ✅
- [ ] Bestätigungs-E-Mail erhalten und gespeichert
- [ ] Termin im eigenen Kalender eingetragen
- [ ] Anfahrt geplant (Parkmöglichkeiten, ÖPNV)
- [ ] Treffpunkt & Uhrzeit notiert
- [ ] Kontaktperson-Nummer gespeichert
- [ ] Kleidung/Ausrüstung bereitgelegt (falls in Beschreibung erwähnt)
- [ ] Erinnerung 1 Tag vorher gesetzt

### 📋 Am Event-Tag

- [ ] Pünktlich (10 Min früher)
- [ ] Smartphone dabei (Kontaktnummer!)
- [ ] Ausweis dabei (falls erforderlich)
- [ ] Gute Laune & Motivation 😊
- [ ] Bei Treffpunkt melden
- [ ] Anweisungen befolgen
- [ ] Bei Fragen nachfragen
- [ ] Spaß haben! 🎉

### 📋 Nach dem Event

- [ ] Bei "Meine Dienste" prüfen: Dienst als "Abgeschlossen" markiert?
- [ ] Optional: Feedback an Organisator geben
- [ ] Optional: Für nächstes Event anmelden
- [ ] Dankeschön annehmen! 👏

---

## Danke, dass du mitmachst! 🙌

Ohne Helfer wie dich wären diese Veranstaltungen nicht möglich. Jeder Dienst, den du übernimmst, trägt zum Erfolg des Events bei.

**Viel Spaß bei deinem nächsten Einsatz!**

---

**Letzte Aktualisierung:** November 2025  
**Plugin-Version:** 0.4.7

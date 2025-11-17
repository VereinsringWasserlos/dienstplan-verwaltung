# Bedienungsanleitung Frontend - Dienstplan-Verwaltung

**Version:** 0.4.7  
**Stand:** November 2025  
**Zielgruppe:** Crew-Mitglieder und Helfer

---

## Inhaltsverzeichnis

1. [Erste Schritte](#erste-schritte)
2. [Veranstaltungen finden](#veranstaltungen-finden)
3. [Für Dienste anmelden](#für-dienste-anmelden)
4. [Split-Dienste](#split-dienste)
5. [Meine Dienste](#meine-dienste)
6. [Dienst abmelden](#dienst-abmelden)
7. [Profil verwalten](#profil-verwalten)
8. [Tipps & FAQ](#tipps-faq)

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

## Veranstaltungen finden

### Ansichten

Nach dem Öffnen des Links siehst du alle Veranstaltungen. Es gibt drei Ansichten:

#### 📋 Listen-Ansicht

Klassische Liste mit allen Details:
- Datum und Uhrzeit
- Veranstaltungsort
- Anzahl freier Dienste
- Button "Details anzeigen"

> 📸 **Screenshot-Hinweis:** Listen-Ansicht mit mehreren Veranstaltungen

#### 📅 Kalender-Ansicht

Monatsübersicht mit:
- Farbigen Markierungen für Events
- Hover zeigt Titel
- Klick öffnet Details

> 📸 **Screenshot-Hinweis:** Kalender-Ansicht mit markierten Event-Tagen

#### 🎴 Compact-Ansicht (Standard)

Karten-Layout mit:
- Event-Karte pro Veranstaltung
- Große "Anmelden"-Buttons
- Schneller Überblick über freie Plätze

> 📸 **Screenshot-Hinweis:** Compact-Ansicht mit Event-Karten

### Ansicht wechseln

Buttons oben rechts:
- **📋** = Listen-Ansicht
- **📅** = Kalender-Ansicht
- **🎴** = Compact-Ansicht

### Filter nutzen

#### Verein auswählen

Dropdown oben: **"Alle Vereine"** → Verein wählen

Nur Events dieses Vereins werden angezeigt.

#### Zeitraum filtern

- **Nur kommende:** Zeigt nur zukünftige Events (Standard)
- **Alle:** Auch vergangene Events
- **Benutzerdefiniert:** Von/Bis-Datum eingeben

### Suche

Suchfeld oben rechts:
- Nach Titel suchen
- Nach Ort suchen
- Nach Datum suchen

Beispiel: "Sommerfest" findet alle Events mit "Sommerfest" im Titel

---

## Für Dienste anmelden

### Schritt-für-Schritt

#### 1. Veranstaltung öffnen

- In der Liste auf **"Details anzeigen"** klicken
- Oder in Compact-View auf die Karte klicken

#### 2. Dienste durchsehen

Die Detail-Seite zeigt:

**Veranstaltungs-Info:**
- Datum & Uhrzeit
- Ort & Beschreibung
- Kontaktperson

**Dienste-Liste:**

Jeder Dienst zeigt:
- **Bereich:** Z.B. "Einlass", "Catering"
- **Tätigkeit:** Z.B. "Ticketkontrolle"
- **Uhrzeit:** Von 18:00 - 23:00 Uhr
- **Freie Plätze:** Z.B. "2/3" = 2 von 3 frei
- **Status-Badge:**
  - 🟢 **Verfügbar** (grün) = Noch Plätze frei
  - 🔴 **Voll** (rot) = Keine Plätze mehr

> 📸 **Screenshot-Hinweis:** Veranstaltungs-Detailseite mit Diensten

#### 3. Dienst auswählen

Auf **"Anmelden"** bei gewünschtem Dienst klicken.

#### 4. Anmeldeformular ausfüllen

Modal-Fenster öffnet sich:

**Pflichtfelder:**
- ✉️ **E-Mail:** Deine Kontakt-Email
- 👤 **Vorname:** Dein Vorname
- 👤 **Nachname:** Dein Nachname

**Optional:**
- 📞 **Telefon:** Für Rückfragen
- 💬 **Kommentar:** Besondere Hinweise

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

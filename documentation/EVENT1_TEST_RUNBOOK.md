# Event 1 Test-Runbook (Start Jetzt)

**Ziel:** Heute sofort strukturiert testen und bis zur ersten Veranstaltung stabil betreiben.

---

## 1. Testfenster (heute)

- **Startzeit:** __________________
- **Endzeit:** __________________
- **Moderator:** __________________
- **Protokoll:** __________________

---

## 2. Testumfang (Muss heute gruen sein)

- [ ] Frontend-Seite laedt und ist erreichbar
- [ ] Veranstaltungsuebersicht wird angezeigt
- [ ] Anmeldung auf einen Dienst funktioniert
- [ ] Abmeldung/Umbuchung funktioniert
- [ ] Backend zeigt Besetzung korrekt
- [ ] Rollenrechte sind korrekt (kein unberechtigter Zugriff)
- [ ] Mobiltest (mind. 1 iOS, 1 Android)

---

## 3. Durchfuehrungsskript (90 Minuten)

## 0-15 Minuten: Smoke-Test
- [ ] Seite oeffnen und Ladezeit pruefen
- [ ] Veranstaltung oeffnen
- [ ] Einen Dienst mit Testaccount buchen

**Abnahme:** Kein Fehler, keine kaputten Ansichten.

## 15-45 Minuten: Kernflow End-to-End
- [ ] Testaccount A meldet sich auf Dienst 1 an
- [ ] Testaccount B meldet sich auf Dienst 2 an
- [ ] Account A aendert/entfernt Anmeldung
- [ ] Backend aktualisiert Besetzung sofort sichtbar

**Abnahme:** Zustand Frontend/Backend ist konsistent.

## 45-70 Minuten: Rollen und Rechte
- [ ] Hauptadmin: Vollzugriff vorhanden
- [ ] Admin: nur erlaubte Bereiche sichtbar
- [ ] Nicht-Admin: kein Zugriff auf Adminseiten

**Abnahme:** Keine Rechteverletzung.

## 70-90 Minuten: Mobil + Regression
- [ ] Mobil Login/Anmeldung in beiden Geraeteklassen
- [ ] Wichtige Buttons/Modals bedienbar
- [ ] Keine darstellungsbrechenden Layoutfehler

**Abnahme:** Mobile Mindestnutzung eventtauglich.

---

## 4. Go/No-Go fuer Event 1

**Go**, wenn alle Bedingungen erfuellt sind:
- [ ] Keine kritischen Fehler offen
- [ ] Kernflow erfolgreich getestet
- [ ] Rollenrechte validiert
- [ ] Verantwortlicher fuer Eventtag benannt

**No-Go**, wenn eine Bedingung fehlt. Dann nur Hotfixes mit direktem Eventbezug.

---

## 5. Incident-Log (waehrend Test und Eventtag)

| Zeit | Bereich | Fehlerbild | Schwere | Owner | Status | Notiz |
|------|---------|------------|---------|-------|--------|-------|
|      |         |            |         |       |        |       |
|      |         |            |         |       |        |       |
|      |         |            |         |       |        |       |

Schwere:
- **Kritisch:** Kernflow blockiert
- **Mittel:** Teilfunktion gestoert, Workaround vorhanden
- **Niedrig:** kosmetisch, kein Eventrisiko

---

## 6. Eventtag-Mini-Check (15 Minuten)

- [ ] Login in Backend ok
- [ ] Veranstaltung und Dienste sichtbar
- [ ] Eine Testanmeldung durchfuehrbar
- [ ] Besetzung aktualisiert sich korrekt
- [ ] Ansprechpartner und Eskalation erreichbar

Wenn ein Punkt fehlschlaegt, sofort Incident erfassen und nach Kritikalitaet behandeln.

# Event 1 Go-Live Plan (Vereinsring)

**Ziel:** Erste Veranstaltung stabil, nachvollziehbar und mit minimalem Risiko live betreiben.  
**Strategie:** Jetzt Betriebssicherheit priorisieren, Architektur-Themen (Core Theme/Child Themes) nach Event 1 umsetzen.

---

## 1. Fokus und Scope

### In Scope (bis Event 1)
- Funktionierender Anmeldeprozess im Frontend
- Stabile Verwaltung im Backend (Veranstaltungen, Dienste, Besetzung)
- Rollen und Rechte korrekt (Hauptadmin/Admin/Verein/Crew)
- Klare Betriebsprozesse für Eventtag (Support, Eskalation, Fallback)

### Out of Scope (nach Event 1)
- Neues eigenes Theme für mehrere Vereine
- Größere UX-Refactorings ohne Event-Relevanz
- Strukturumbauten an Datenmodell und Architektur

---

## 2. Langfristiger Rahmen (ohne Umsetzung jetzt)

### Phase A: Event 1 stabil ausliefern (jetzt)
- Checklistenbasiertes Testing und Go-Live
- Nur kritische Fixes mit direktem Eventnutzen

### Phase B: Nachbereitung (1-2 Wochen nach Event 1)
- Retro, Kennzahlen, Top-5 Pain Points
- Kleine Härtungsmaßnahmen aus realen Erkenntnissen

### Phase C: Multi-Verein-Ausbau
- Core Theme + Child Themes je Verein
- Standardisierter Rollout-Prozess pro Verein
- Optional Multisite-Prüfung bei wachsender Anzahl Vereine

---

## 3. Ab sofort: Event-1 Arbeitsplan

## 3.1 Vorbereitungs-Checkliste (T-14 bis T-3)

- [ ] Veranstaltung vollständig angelegt (Datum, Name, Status)
- [ ] Vereine korrekt zugeordnet
- [ ] Bereiche und Tätigkeiten vollständig gepflegt
- [ ] Dienste mit Zeiten, Slots und Regeln geprüft
- [ ] Frontend-Seite vorhanden und erreichbar
- [ ] Testanmeldungen mit mindestens 2 Rollen durchgeführt
- [ ] Mailfluss geprüft (Bestätigung/Info)
- [ ] Mobile Test (iOS + Android) durchgeführt
- [ ] Browser-Test (Chrome, Safari, Firefox) durchgeführt
- [ ] Datenexport/Import Basisfunktion geprüft

## 3.2 Go-Live Readiness (T-2 bis T-0)

- [ ] Vollbackup erstellt (Dateien + DB)
- [ ] Fallback-Kontakt und Eskalationsweg schriftlich definiert
- [ ] Verantwortliche für Eventtag benannt
- [ ] Kritische Zugänge geprüft (Admin, SSH, SFTP)
- [ ] Notfallmaßnahmen dokumentiert (z. B. manuelle Besetzung)
- [ ] Letzter Smoke-Test auf Live durchgeführt

## 3.3 Eventtag Betrieb

- [ ] Monitoring-Slot vor Öffnung (15 Minuten)
- [ ] Monitoring-Slot nach Öffnung (30-60 Minuten)
- [ ] Engpass-Check auf freie/überbuchte Slots
- [ ] Incident-Log geführt (Zeit, Problem, Lösung)
- [ ] Kommunikationsweg aktiv (wer informiert wen)

## 3.4 Nachlauf (T+1 bis T+7)

- [ ] Kurze Auswertung mit Kennzahlen
- [ ] Vorfälle klassifizieren (kritisch, mittel, kosmetisch)
- [ ] Top-5 Maßnahmen priorisieren
- [ ] Doku aktualisieren

---

## 4. Rollen und Zuständigkeiten (Template)

## 4.1 Operativ
- **Release-Verantwortung:** __________________
- **Backend-Verantwortung:** __________________
- **Frontend/Kommunikation:** __________________
- **Support Eventtag:** __________________

## 4.2 Eskalation
- **Technische Eskalation 1:** __________________
- **Technische Eskalation 2:** __________________
- **Organisatorische Entscheidung:** __________________

---

## 5. Go/No-Go Kriterien

Go nur, wenn alle Punkte erfüllt sind:

- [ ] Anmeldung funktioniert durchgängig im Frontend
- [ ] Dienste und Besetzung sind konsistent
- [ ] Keine offenen kritischen Bugs
- [ ] Backup erfolgreich und rückspielbar
- [ ] Verantwortliche und Eskalation sind benannt

Bei fehlendem Kriterium: **No-Go**, nur Hotfixes mit Eventbezug zulassen.

---

## 6. Kennzahlen für Event 1

- Anzahl erfolgreicher Anmeldungen
- Anzahl manueller Eingriffe im Backend
- Anzahl No-Shows
- Anzahl kurzfristiger Umbesetzungen
- Anzahl Supportfälle und Lösungsdauer

Diese Kennzahlen entscheiden über Prioritäten für Phase B/C.

---

## 7. Entscheidungslog (laufend pflegen)

| Datum | Entscheidung | Begründung | Verantwortlich |
|-------|--------------|------------|----------------|
| 2026-03-23 | Fokus auf Event-1-Betriebssicherheit | Minimales Risiko für erste Veranstaltung | Team |

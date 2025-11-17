# Dienstplan Verwaltung - Rollen & Berechtigungen

## Übersicht der Benutzerrollen

### 🔴 **WordPress Administrator**
**Voller Systemzugriff - Alle Rechte**

#### Menü-Zugriff:
- ✅ Dashboard
- ✅ Vereine (Vollzugriff)
- ✅ Veranstaltungen (Vollzugriff)
- ✅ Benutzerverwaltung
- ✅ Einstellungen

#### Capabilities:
```php
- dp_manage_settings      ✅
- dp_manage_users         ✅
- dp_manage_events        ✅
- dp_manage_clubs         ✅
- dp_view_reports         ✅
- dp_send_notifications   ✅
```

#### Möglichkeiten:
- Vereine: Erstellen, Bearbeiten, Löschen, Ansehen
- Veranstaltungen: Erstellen, Bearbeiten, Löschen, Ansehen
- Benutzer: Rollen zuweisen, Einladen, Verwalten
- Einstellungen: Alle System-Einstellungen ändern
- Benachrichtigungen: E-Mail-Einstellungen verwalten
- Reports: Alle Berichte ansehen

---

### 🟠 **Allgemeiner Admin** (Dienstplan)
**Vollzugriff auf Dienstplan-Funktionen**

Role: `dp_general_admin`

#### Menü-Zugriff:
- ✅ Dashboard
- ✅ Vereine (Vollzugriff)
- ✅ Veranstaltungen (Vollzugriff)
- ✅ Benutzerverwaltung
- ✅ Einstellungen

#### Capabilities:
```php
- dp_manage_settings      ✅
- dp_manage_users         ✅
- dp_manage_events        ✅
- dp_manage_clubs         ✅
- dp_view_reports         ✅
- dp_send_notifications   ✅
```

#### Möglichkeiten:
- **Vereine:**
  - ✅ Neue Vereine anlegen
  - ✅ Vereine bearbeiten
  - ✅ Vereine löschen
  - ✅ Kontaktdaten verwalten
  - ✅ Verantwortliche einladen (WordPress-Benutzer erstellen)
  
- **Veranstaltungen:**
  - ✅ Neue Veranstaltungen erstellen
  - ✅ Veranstaltungen bearbeiten
  - ✅ Veranstaltungen löschen
  - ✅ Mehrtägige Events planen
  - ✅ Zeiten über Mitternacht definieren
  - ✅ Vereine zuordnen
  - ✅ Tage und Zeiten verwalten
  
- **Benutzerverwaltung:**
  - ✅ Neue Benutzer einladen
  - ✅ Rollen zuweisen
  - ✅ Benutzer ansehen
  
- **Einstellungen:**
  - ✅ Organisations-Einstellungen
  - ✅ Benachrichtigungs-Präferenzen
  - ✅ Datumsformat
  
- **Benachrichtigungen:**
  - ✅ Erhält E-Mails bei Änderungen
  - ✅ Kann eigene Präferenzen einstellen

**Unterschied zu WordPress-Admin:** Kein Zugriff auf WordPress-Core-Einstellungen

---

### 🔵 **Veranstaltungs-Admin**
**Nur Veranstaltungen verwalten**

Role: `dp_event_admin`

#### Menü-Zugriff:
- ✅ Dashboard (nur lesen)
- ❌ Vereine (nicht sichtbar)
- ✅ Veranstaltungen (Vollzugriff)
- ❌ Benutzerverwaltung (nicht sichtbar)
- ❌ Einstellungen (nicht sichtbar)

#### Capabilities:
```php
- dp_manage_settings      ❌
- dp_manage_users         ❌
- dp_manage_events        ✅
- dp_manage_clubs         ❌
- dp_view_reports         ✅
- dp_send_notifications   ❌
```

#### Möglichkeiten:
- **Veranstaltungen:**
  - ✅ Neue Veranstaltungen erstellen
  - ✅ Veranstaltungen bearbeiten
  - ✅ Veranstaltungen löschen
  - ✅ Mehrtägige Events planen
  - ✅ Zeiten über Mitternacht definieren
  - ✅ Bestehende Vereine zuordnen (aus Dropdown)
  - ✅ Tage und Zeiten verwalten
  - ✅ Status ändern (geplant, aktiv, abgeschlossen)
  
- **Dashboard:**
  - ✅ Statistiken ansehen
  - ✅ Übersicht der Veranstaltungen
  
- **Reports:**
  - ✅ Veranstaltungs-Berichte ansehen
  
- **Benachrichtigungen:**
  - ✅ Erhält E-Mails bei Veranstaltungs-Änderungen
  - ✅ Kann eigene E-Mail-Präferenzen einstellen

#### Einschränkungen:
- ❌ Kann keine Vereine erstellen/bearbeiten/löschen
- ❌ Kann nur existierende Vereine zuordnen
- ❌ Kann keine Benutzer verwalten
- ❌ Kann keine System-Einstellungen ändern

---

### 🟢 **Vereins-Admin**
**Nur Vereine verwalten**

Role: `dp_club_admin`

#### Menü-Zugriff:
- ✅ Dashboard (nur lesen)
- ✅ Vereine (Vollzugriff)
- ❌ Veranstaltungen (nicht sichtbar)
- ❌ Benutzerverwaltung (nicht sichtbar)
- ❌ Einstellungen (nicht sichtbar)

#### Capabilities:
```php
- dp_manage_settings      ❌
- dp_manage_users         ❌
- dp_manage_events        ❌
- dp_manage_clubs         ✅
- dp_view_reports         ✅
- dp_send_notifications   ❌
```

#### Möglichkeiten:
- **Vereine:**
  - ✅ Neue Vereine anlegen
  - ✅ Vereine bearbeiten
  - ✅ Vereine löschen
  - ✅ Name und Kürzel vergeben
  - ✅ Beschreibung hinzufügen
  - ✅ Kontaktdaten verwalten (Person, E-Mail, Telefon)
  - ✅ Status aktivieren/deaktivieren
  - ✅ Verantwortliche einladen (WordPress-Benutzer erstellen)
  - ✅ E-Mail-Prüfung ob Benutzer existiert
  - ✅ Rolle zuweisen beim Einladen
  
- **Dashboard:**
  - ✅ Statistiken ansehen
  - ✅ Übersicht der Vereine
  
- **Reports:**
  - ✅ Vereins-Berichte ansehen
  
- **Benachrichtigungen:**
  - ✅ Erhält E-Mails bei Vereins-Änderungen
  - ✅ Kann eigene E-Mail-Präferenzen einstellen

#### Einschränkungen:
- ❌ Kann keine Veranstaltungen erstellen/bearbeiten/löschen
- ❌ Sieht keine Veranstaltungen
- ❌ Kann keine Benutzer verwalten
- ❌ Kann keine System-Einstellungen ändern

---

## Berechtigungs-Matrix

| Funktion | WP Admin | General Admin | Event Admin | Club Admin |
|----------|:--------:|:-------------:|:-----------:|:----------:|
| **Vereine** |
| Vereine ansehen | ✅ | ✅ | ❌ | ✅ |
| Vereine erstellen | ✅ | ✅ | ❌ | ✅ |
| Vereine bearbeiten | ✅ | ✅ | ❌ | ✅ |
| Vereine löschen | ✅ | ✅ | ❌ | ✅ |
| **Veranstaltungen** |
| Veranstaltungen ansehen | ✅ | ✅ | ✅ | ❌ |
| Veranstaltungen erstellen | ✅ | ✅ | ✅ | ❌ |
| Veranstaltungen bearbeiten | ✅ | ✅ | ✅ | ❌ |
| Veranstaltungen löschen | ✅ | ✅ | ✅ | ❌ |
| **Benutzerverwaltung** |
| Benutzer ansehen | ✅ | ✅ | ❌ | ❌ |
| Rollen zuweisen | ✅ | ✅ | ❌ | ❌ |
| Benutzer einladen | ✅ | ✅ | ❌ | ✅* |
| **Einstellungen** |
| System-Einstellungen | ✅ | ✅ | ❌ | ❌ |
| Eigene Benachrichtigungen | ✅ | ✅ | ✅ | ✅ |
| **Benachrichtigungen** |
| E-Mails erhalten | ✅ | ✅ | ✅ (nur Events) | ✅ (nur Vereine) |
| E-Mails versenden | ✅ | ✅ | ❌ | ❌ |

\* Vereins-Admin kann nur beim Erstellen eines Vereins einen Verantwortlichen als WordPress-Benutzer einladen

---

## E-Mail-Benachrichtigungen

### Veranstaltungs-Benachrichtigungen
**Empfänger:**
- WordPress-Admins
- Allgemeine Admins
- Veranstaltungs-Admins

**Events:**
- ✉️ Neue Veranstaltung erstellt
- ✉️ Veranstaltung aktualisiert
- ✉️ Veranstaltung gelöscht

**Inhalt:**
- Name der Veranstaltung
- Status
- Wer hat die Änderung vorgenommen
- Link zum Dienstplan-System

### Vereins-Benachrichtigungen
**Empfänger:**
- WordPress-Admins
- Allgemeine Admins
- Vereins-Admins

**Events:**
- ✉️ Neuer Verein erstellt
- ✉️ Verein aktualisiert
- ✉️ Verein gelöscht

**Inhalt:**
- Name des Vereins
- Kürzel
- Wer hat die Änderung vorgenommen
- Link zum Dienstplan-System

### Einladungs-E-Mails
**Empfänger:**
- Neue WordPress-Benutzer

**Inhalt:**
- Begrüßung mit Namen
- Benutzername
- Link zum Passwort setzen
- Link zur Anmeldeseite
- Automatisch generiert beim Erstellen eines Vereins/Veranstaltung

---

## Besondere Features pro Rolle

### Für Vereins-Admin:
1. **Verantwortlichen-Prüfung:**
   - Gibt E-Mail-Adresse ein
   - System prüft automatisch ob WordPress-Benutzer existiert
   - ✅ Grün: "Benutzer existiert: Max Mustermann"
   - ⚠️ Gelb: "Kein Benutzer gefunden" + Einladungs-Option

2. **Benutzer-Einladung:**
   - Checkbox: "WordPress-Benutzer erstellen"
   - Dropdown: Rolle auswählen (Vereins-Admin, Event-Admin, General-Admin)
   - Automatische E-Mail mit Passwort-Link
   - Benutzername wird aus E-Mail generiert

### Für Veranstaltungs-Admin:
1. **Mehrtägige Events:**
   - Tabellenbasierte Eingabe
   - Automatische Datumsvorschläge
   - Wochentag-Anzeige

2. **Zeiten über Mitternacht:**
   - Eingabe: "20:00 - 01:00"
   - Automatische Erkennung
   - Anzeige: "+1 Tag" in rot
   - Separate Dienst-Zeiten (Setup/Teardown)

3. **Vereins-Zuordnung:**
   - Mehrfach-Auswahl per Checkbox
   - Nur existierende Vereine aus Dropdown

---

## Dashboard-Ansicht (für alle Rollen)

**Statistiken:**
- Anzahl Vereine (wenn berechtigt)
- Anzahl Veranstaltungen (wenn berechtigt)
- Kommende Events
- Aktive Vereine

**Schnellzugriffe:**
- Neuer Verein (wenn berechtigt)
- Neue Veranstaltung (wenn berechtigt)
- Letzte Aktivitäten

---

## Sicherheitsfeatures

### Duplikats-Prüfung:
- ✅ Vereine: Name und Kürzel müssen eindeutig sein
- ✅ Veranstaltungen: Name + Datum müssen eindeutig sein
- ✅ Benutzer: Keine doppelten Rollen-Zuweisungen

### Validierung:
- ✅ E-Mail-Format-Prüfung
- ✅ Pflichtfelder (Name, Kürzel)
- ✅ Nonce-Prüfung bei allen AJAX-Calls
- ✅ Capability-Prüfung vor jeder Aktion

### Benutzer-Einladung:
- ✅ Sicheres Passwort (12 Zeichen)
- ✅ WordPress Passwort-Reset-Mechanismus
- ✅ Eindeutige Benutzernamen (bei Kollision: +Nummer)
- ✅ Keine direkten Passwörter in E-Mails

---

## Workflow-Beispiele

### Beispiel 1: Vereins-Admin legt neuen Verein an
1. Klickt auf "Neuer Verein"
2. Füllt Name: "THW Ortsverband Musterstadt"
3. Kürzel: "THW-MS"
4. Kontakt-E-Mail: "leitung@thw-musterstadt.de"
5. System prüft → ⚠️ "Kein Benutzer gefunden"
6. Aktiviert Checkbox "WordPress-Benutzer erstellen"
7. Wählt Rolle: "Vereins-Admin"
8. Speichert
9. ✅ "Verein angelegt und Benutzer wurde eingeladen"
10. E-Mail geht an leitung@thw-musterstadt.de mit Passwort-Link

### Beispiel 2: Veranstaltungs-Admin plant mehrtägiges Event
1. Klickt auf "Neue Veranstaltung"
2. Name: "Stadtfest Musterstadt 2025"
3. Status: "Geplant"
4. Klickt "Weiteren Tag hinzufügen"
5. Tag 1: 15.06.2025, Veranstaltung: 14:00-23:00, Dienst: 12:00-01:00
   - System zeigt: "+1 Tag" bei 01:00
6. Tag 2: 16.06.2025 (automatisch vorgeschlagen)
7. Wählt Vereine: THW, DRK, DLRG (Checkboxen)
8. Speichert
9. ✅ Alle Vereins-Admins und Event-Admins erhalten E-Mail

### Beispiel 3: Allgemeiner Admin weist Rolle zu
1. Geht zu "Benutzerverwaltung"
2. Wählt Benutzer: "max.mustermann"
3. Wählt Rolle: "Veranstaltungs-Admin"
4. Klickt "Rolle zuweisen"
5. ✅ "Rolle erfolgreich zu Max Mustermann hinzugefügt"
6. Max kann jetzt Veranstaltungen verwalten

---

## Technische Implementation

### Rollen-Definition:
```php
// In class-dienstplan-roles.php
const ROLE_GENERAL_ADMIN = 'dp_general_admin';
const ROLE_EVENT_ADMIN = 'dp_event_admin';
const ROLE_CLUB_ADMIN = 'dp_club_admin';
```

### Berechtigungs-Prüfung:
```php
// Beispiel in AJAX-Handler
if (!Dienstplan_Roles::can_manage_clubs()) {
    wp_send_json_error(['message' => 'Keine Berechtigung']);
    return;
}
```

### Menü-Sichtbarkeit:
```php
// Nur anzeigen wenn berechtigt
if (Dienstplan_Roles::can_manage_clubs() || current_user_can('manage_options')) {
    add_submenu_page(...);
}
```

---

## Zusammenfassung

Das Rollensystem ist hierarchisch aufgebaut:

**WordPress Admin** (höchste Ebene)
└─ **Allgemeiner Admin** (Dienstplan-spezifisch)
   ├─ **Veranstaltungs-Admin** (nur Events)
   └─ **Vereins-Admin** (nur Vereine)

Jede Rolle hat genau die Berechtigungen, die sie für ihre Aufgaben benötigt (Principle of Least Privilege).

# Dienstplan-Verwaltung - Aktuelle Datenbankstruktur

**Stand:** 17. November 2025  
**Plugin-Version:** 0.4.7  
**Präfix:** `wp_dp_` (WordPress-Präfix + `dp_`)

---

## 📋 Übersicht aller Tabellen

| Tabelle | Zweck | Beziehung |
|---------|-------|-----------|
| `settings` | Plugin-Einstellungen | - |
| `vereine` | Vereine/Clubs | 1:N zu veranstaltung_vereine |
| `veranstaltungen` | Events/Veranstaltungen | 1:N zu tage, dienste |
| `veranstaltung_tage` | Event-Tage (mehrtägig) | N:1 zu veranstaltungen |
| `veranstaltung_vereine` | Event ↔ Verein Zuordnung | M:N |
| `veranstaltung_verantwortliche` | Event ↔ User Zuordnung | M:N |
| `verein_verantwortliche` | Verein ↔ User Zuordnung | M:N |
| `bereiche` | Bereiche (Technik, Catering, etc.) | 1:N zu taetigkeiten |
| `taetigkeiten` | Tätigkeiten pro Bereich | N:1 zu bereiche |
| `dienste` | Konkrete Dienste | N:1 zu veranstaltungen, tage, vereine, bereiche, taetigkeiten |
| `dienst_slots` | Split-Dienste (optional) | N:1 zu dienste |
| `mitarbeiter` | Crewmitglieder (Frontend) | 1:N zu dienst_zuweisungen |
| `dienst_zuweisungen` | Dienst-Anmeldungen | N:1 zu dienste, mitarbeiter |

---

## 🗄️ Detaillierte Tabellenstruktur

### 1. `wp_dp_settings`
**Zweck:** Plugin-Einstellungen (Key-Value Store)

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | mediumint(9) PK | Auto-Increment |
| `setting_key` | varchar(100) UNIQUE | Einstellungsschlüssel |
| `setting_value` | longtext | Einstellungswert (JSON/Text) |

---

### 2. `wp_dp_vereine`
**Zweck:** Vereine/Clubs die an Events teilnehmen

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | mediumint(9) PK | Auto-Increment |
| `name` | varchar(255) | Vereinsname |
| `kuerzel` | varchar(10) UNIQUE | Eindeutiges Kürzel (z.B. "THW", "DRK") |
| `beschreibung` | text | Beschreibung |
| `logo_id` | bigint(20) | WordPress Media-ID für Logo |
| `kontakt_name` | varchar(255) | Ansprechpartner |
| `kontakt_email` | varchar(255) | Kontakt-Email |
| `kontakt_telefon` | varchar(50) | Telefonnummer |
| `aktiv` | tinyint(1) | 1=aktiv, 0=inaktiv |
| `erstellt_am` | datetime | Erstellungszeitpunkt |

**Indizes:** `kuerzel` (UNIQUE)

---

### 3. `wp_dp_veranstaltungen`
**Zweck:** Events/Veranstaltungen

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | mediumint(9) PK | Auto-Increment |
| `name` | varchar(255) | Veranstaltungsname |
| `beschreibung` | text | Beschreibung |
| `typ` | varchar(50) | 'eintaegig' oder 'mehrtaegig' |
| `status` | varchar(50) | 'geplant', 'aktiv', 'abgeschlossen' |
| `start_datum` | date | Start-Datum |
| `end_datum` | date | End-Datum (NULL bei eintägig) |
| `seite_id` | bigint(20) | WordPress-Seiten-ID für Frontend |
| `erstellt_am` | datetime | Erstellungszeitpunkt |
| `aktualisiert_am` | datetime | Letztes Update |

**Indizes:** `start_datum`, `status`, `seite_id`

---

### 4. `wp_dp_veranstaltung_tage`
**Zweck:** Einzelne Tage einer Veranstaltung (für mehrtägige Events)

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | mediumint(9) PK | Auto-Increment |
| `veranstaltung_id` | mediumint(9) FK | → veranstaltungen.id |
| `tag_datum` | date | Datum des Tages |
| `tag_nummer` | tinyint(2) | Tag-Nummer (1, 2, 3...) |
| `von_zeit` | time | Veranstaltungs-Start |
| `bis_zeit` | time | Veranstaltungs-Ende |
| `bis_datum` | date | End-Datum (wenn über Mitternacht) |
| `dienst_von_zeit` | time | Dienst-Start (oft früher) |
| `dienst_bis_zeit` | time | Dienst-Ende (oft später) |
| `dienst_bis_datum` | date | Dienst-End-Datum |
| `nur_dienst` | tinyint(1) | 1=Nur Dienste (keine Veranstaltung) |
| `notizen` | text | Notizen |

**Indizes:** `veranstaltung_id`, `tag_datum`

**Wichtig:** Dienste verweisen auf `tag_id`, nicht direkt auf Datum!

---

### 5. `wp_dp_veranstaltung_vereine` (M:N)
**Zweck:** Welche Vereine nehmen an welchen Events teil

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | mediumint(9) PK | Auto-Increment |
| `veranstaltung_id` | mediumint(9) FK | → veranstaltungen.id |
| `verein_id` | mediumint(9) FK | → vereine.id |

**Indizes:** UNIQUE(`veranstaltung_id`, `verein_id`)

---

### 6. `wp_dp_verein_verantwortliche` (M:N)
**Zweck:** WordPress-User die einen Verein verwalten dürfen

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | mediumint(9) PK | Auto-Increment |
| `verein_id` | mediumint(9) FK | → vereine.id |
| `user_id` | bigint(20) FK | → wp_users.ID |
| `erstellt_am` | datetime | Erstellungszeitpunkt |

**Indizes:** UNIQUE(`verein_id`, `user_id`)

---

### 7. `wp_dp_veranstaltung_verantwortliche` (M:N)
**Zweck:** WordPress-User die ein Event verwalten dürfen

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | mediumint(9) PK | Auto-Increment |
| `veranstaltung_id` | mediumint(9) FK | → veranstaltungen.id |
| `user_id` | bigint(20) FK | → wp_users.ID |
| `erstellt_am` | datetime | Erstellungszeitpunkt |

**Indizes:** UNIQUE(`veranstaltung_id`, `user_id`)

---

### 8. `wp_dp_bereiche`
**Zweck:** Bereiche (z.B. Technik, Catering, Ordner, Sanitätsdienst)

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | mediumint(9) PK | Auto-Increment |
| `name` | varchar(100) UNIQUE | Bereichsname |
| `beschreibung` | text | Beschreibung |
| `farbe` | varchar(7) | Hex-Farbcode (z.B. #3b82f6) |
| `sortierung` | int(3) | Sortierreihenfolge |
| `aktiv` | tinyint(1) | 1=aktiv, 0=inaktiv |
| `erstellt_am` | datetime | Erstellungszeitpunkt |

**Indizes:** UNIQUE(`name`)

---

### 9. `wp_dp_taetigkeiten`
**Zweck:** Tätigkeiten innerhalb eines Bereichs (z.B. "Aufbau", "Abbau", "Bedienung")

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | mediumint(9) PK | Auto-Increment |
| `bereich_id` | mediumint(9) FK | → bereiche.id |
| `name` | varchar(100) | Tätigkeitsname |
| `beschreibung` | text | Beschreibung |
| `sortierung` | int(3) | Sortierreihenfolge |
| `aktiv` | tinyint(1) | 1=aktiv, 0=inaktiv |
| `erstellt_am` | datetime | Erstellungszeitpunkt |

**Indizes:** UNIQUE(`bereich_id`, `name`), INDEX(`bereich_id`)

**Wichtig:** Tätigkeiten sind immer einem Bereich zugeordnet!

---

### 10. `wp_dp_dienste`
**Zweck:** Konkrete Dienste (Schichten) an Events

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | mediumint(9) PK | Auto-Increment |
| `veranstaltung_id` | mediumint(9) FK | → veranstaltungen.id |
| `tag_id` | mediumint(9) FK | → veranstaltung_tage.id |
| `verein_id` | mediumint(9) FK | → vereine.id |
| `bereich_id` | mediumint(9) FK | → bereiche.id |
| `taetigkeit_id` | mediumint(9) FK | → taetigkeiten.id |
| `von_zeit` | time | Dienst-Start |
| `bis_zeit` | time | Dienst-Ende |
| `bis_datum` | date | End-Datum (wenn über Mitternacht) |
| `anzahl_personen` | int(2) | Benötigte Personen |
| `status` | varchar(50) | 'geplant', 'aktiv', 'abgeschlossen' |
| `besonderheiten` | text | Notizen (auch "[Teil X - gesplittet]") |
| `splittbar` | tinyint(1) | 1=kann geteilt werden |
| `erstellt_am` | datetime | Erstellungszeitpunkt |

**Indizes:** `veranstaltung_id`, `tag_id`, `verein_id`, `bereich_id`, `taetigkeit_id`

**Wichtig:** 
- **KEIN `datum` Feld!** Datum kommt von `tag_id` → `veranstaltung_tage.tag_datum`
- Bei Split wird Original gelöscht, 2 neue Dienste mit "[Teil X - gesplittet]" erstellt

---

### 11. `wp_dp_dienst_slots`
**Zweck:** Split-Slots für Dienste (optional, bei `splittbar=1`)

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | mediumint(9) PK | Auto-Increment |
| `dienst_id` | mediumint(9) FK | → dienste.id |
| `slot_nummer` | tinyint(2) | Slot-Nummer (1, 2, 3...) |
| `mitarbeiter_id` | mediumint(9) FK | → mitarbeiter.id (NULL=frei) |
| `von_zeit` | time | Slot-Start |
| `bis_zeit` | time | Slot-Ende |
| `bis_datum` | date | Slot-End-Datum |
| `status` | varchar(20) | 'offen', 'besetzt' |
| `erstellt_am` | datetime | Erstellungszeitpunkt |

**Indizes:** `dienst_id`, `mitarbeiter_id`

**Hinweis:** Wird aktuell automatisch erstellt, aber nicht aktiv für Frontend-Anmeldungen genutzt

---

### 12. `wp_dp_mitarbeiter`
**Zweck:** Crewmitglieder (Frontend-Anmeldungen, keine WordPress-User)

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | mediumint(9) PK | Auto-Increment |
| `vorname` | varchar(100) | Vorname |
| `nachname` | varchar(100) | Nachname |
| `email` | varchar(100) | Email (optional, für Duplikat-Check) |
| `telefon` | varchar(50) | Telefon (optional) |
| `datenschutz_akzeptiert` | tinyint(1) | 1=akzeptiert |

**Indizes:** INDEX(`nachname`, `vorname`), INDEX(`email`)

**Wichtig:** 
- **KEINE `rolle` Spalte!**
- **KEINE `aktiv` Spalte!**
- **KEINE `erstellt_am` Spalte!** (Tabelle hat bewusst kein Timestamp)
- Email ist optional (kann leer sein)

---

### 13. `wp_dp_dienst_zuweisungen`
**Zweck:** Anmeldungen von Mitarbeitern zu Diensten (Frontend Self-Service)

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | mediumint(9) PK | Auto-Increment |
| `dienst_id` | mediumint(9) FK | → dienste.id |
| `slot_id` | mediumint(9) FK | → dienst_slots.id (optional) |
| `mitarbeiter_id` | mediumint(9) FK | → mitarbeiter.id |
| `user_id` | bigint(20) FK | → wp_users.ID (optional, Backend) |
| `name` | varchar(255) | Name (Legacy, kann leer sein) |
| `email` | varchar(255) | Email (Legacy, kann leer sein) |
| `telefon` | varchar(50) | Telefon (Legacy, kann leer sein) |
| `kommentar` | text | Kommentar zur Anmeldung |
| `status` | varchar(20) | 'bestaetigt', 'abgesagt', 'wartend' |
| `eingetragen_am` | datetime | Anmeldezeitpunkt |
| `eingetragen_von` | bigint(20) | WordPress User-ID (bei Backend-Eintrag) |

**Indizes:** `dienst_id`, `slot_id`, `mitarbeiter_id`, `user_id`

**Wichtig:**
- **KEINE `besetzungen` Tabelle!** Diese Tabelle heißt `dienst_zuweisungen`
- Spalte `mitarbeiter_id` muss existieren (Migration erforderlich bei älteren Installationen)
- Spaltenname: `eingetragen_am` (nicht `erstellt_am`)
- Spaltenname: `kommentar` (nicht `bemerkung`)
- Status: `bestaetigt` (nicht `zugesagt`)
- Legacy-Felder (`name`, `email`, `telefon`) sind redundant zu `mitarbeiter_id` Verknüpfung

---

## 🔗 Beziehungen & Foreign Keys

```
veranstaltungen (1) ──< (N) veranstaltung_tage
veranstaltungen (1) ──< (N) dienste
veranstaltungen (M) ──< >── (N) vereine (via veranstaltung_vereine)
veranstaltungen (M) ──< >── (N) wp_users (via veranstaltung_verantwortliche)

vereine (M) ──< >── (N) wp_users (via verein_verantwortliche)
vereine (1) ──< (N) dienste

bereiche (1) ──< (N) taetigkeiten
bereiche (1) ──< (N) dienste

taetigkeiten (1) ──< (N) dienste

veranstaltung_tage (1) ──< (N) dienste

dienste (1) ──< (N) dienst_slots
dienste (1) ──< (N) dienst_zuweisungen

mitarbeiter (1) ──< (N) dienst_zuweisungen
mitarbeiter (1) ──< (N) dienst_slots
```

---

## ⚠️ Wichtige Hinweise für Coding

### Dienst-Datum ermitteln:
```php
// FALSCH (Spalte existiert nicht):
$datum = $dienst->datum;

// RICHTIG:
$tag = $wpdb->get_row($wpdb->prepare(
    "SELECT tag_datum FROM {$prefix}veranstaltung_tage WHERE id = %d",
    $dienst->tag_id
));
$datum = $tag->tag_datum;
```

### Mitarbeiter anlegen:
```php
// FALSCH (Spalten existieren nicht):
$data = array(
    'rolle' => 'crew',
    'aktiv' => 1
);

// RICHTIG:
$data = array(
    'vorname' => $vorname,
    'nachname' => $nachname,
    'email' => $email, // optional
    'telefon' => $telefon, // optional
    'datenschutz_akzeptiert' => 1
);
// Hinweis: erstellt_am wird automatisch gesetzt (DEFAULT CURRENT_TIMESTAMP in Tabellen mit dieser Spalte)
```

### Dienst-Zuweisung erstellen:
```php
// FALSCH (Tabelle/Spalten existieren nicht):
$wpdb->insert(
    $prefix . 'besetzungen', // FALSCHE Tabelle
    array(
        'status' => 'zugesagt', // FALSCHER Status
        'erstellt_am' => current_time('mysql') // FALSCHER Spaltenname
    )
);

// RICHTIG:
$wpdb->insert(
    $prefix . 'dienst_zuweisungen', // KORREKTE Tabelle
    array(
        'dienst_id' => $dienst_id,
        'mitarbeiter_id' => $mitarbeiter_id, // WICHTIG: Spalte muss existieren!
        'status' => 'bestaetigt', // KORREKTER Status
        'eingetragen_am' => current_time('mysql') // KORREKTER Spaltenname
    ),
    array('%d', '%d', '%s', '%s') // Format-Array
);
```

**Migration erforderlich:** Bei älteren Installationen fehlt die Spalte `mitarbeiter_id`. Führe aus:
```sql
ALTER TABLE wp_dp_dienst_zuweisungen 
ADD COLUMN mitarbeiter_id mediumint(9) DEFAULT NULL AFTER slot_id,
ADD KEY mitarbeiter_id (mitarbeiter_id);
```
Oder verwende das Migrations-Script: `migrate-mitarbeiter-id.php`

### Dienst-Split implementieren:
```php
// Original-Dienst holen
$dienst = $db->get_dienst($dienst_id);

// Tag-Datum holen (NICHT von dienst->datum)
$tag = $wpdb->get_row($wpdb->prepare(
    "SELECT tag_datum FROM {$prefix}veranstaltung_tage WHERE id = %d",
    $dienst->tag_id
));

// Teil 1 & 2 mit korrekten Feldern erstellen
$teil1_data = array(
    'veranstaltung_id' => $dienst->veranstaltung_id,
    'tag_id' => $dienst->tag_id,
    'verein_id' => $dienst->verein_id,
    'bereich_id' => $dienst->bereich_id,
    'taetigkeit_id' => $dienst->taetigkeit_id,
    'von_zeit' => $von_zeit,
    'bis_zeit' => $middle_zeit,
    'anzahl_personen' => 1,
    'besonderheiten' => trim($dienst->besonderheiten . ' [Teil 1 - gesplittet]')
);

$wpdb->insert($prefix . 'dienste', $teil1_data);

// Existierende Zuweisungen kopieren
$wpdb->query("INSERT INTO {$prefix}dienst_zuweisungen 
    (dienst_id, mitarbeiter_id, status, kommentar, eingetragen_am)
    SELECT {$teil1_id}, mitarbeiter_id, status, kommentar, eingetragen_am
    FROM {$prefix}dienst_zuweisungen WHERE dienst_id = {$dienst_id}");

// Original löschen
$wpdb->delete($prefix . 'dienst_zuweisungen', array('dienst_id' => $dienst_id));
$wpdb->delete($prefix . 'dienste', array('id' => $dienst_id));
```

---

## 📊 Status-Werte

### Veranstaltungen:
- `geplant` - In Planung
- `aktiv` - Läuft aktuell
- `abgeschlossen` - Beendet

### Dienste:
- `geplant` - Geplant
- `aktiv` - Aktiv
- `abgeschlossen` - Abgeschlossen

### Dienst-Zuweisungen:
- `bestaetigt` - Bestätigt
- `abgesagt` - Abgesagt
- `wartend` - Wartet auf Bestätigung

### Dienst-Slots:
- `offen` - Frei
- `besetzt` - Zugewiesen

---

## 🔧 Getter-Methoden

Die Database-Klasse hat protected Properties. Verwende Getter:

```php
$db = new Dienstplan_Database();
$wpdb = $db->get_wpdb();
$prefix = $db->get_prefix();
```

---

**Zuletzt aktualisiert:** 17. November 2025  
**Autor:** GitHub Copilot  
**Verwendung:** Referenz für weiteres Coding im Dienstplan-Plugin

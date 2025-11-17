# Dienstplan Verwaltung - Datenbankstruktur

> **⚠️ VERALTET - Diese Datei ist nicht mehr aktuell!**
> 
> **Verwende stattdessen:** `DATABASE_STRUCTURE_AKTUELL.md`
> 
> Diese Datei enthält veraltete Informationen und wird nur aus historischen Gründen beibehalten.

## Übersicht
Alle Tabellen verwenden das Präfix `wp_dp_` (WordPress-Präfix + dp_)

---

## 1. SETTINGS
**Tabelle:** `dp_settings`
```sql
id              mediumint(9) PK AUTO_INCREMENT
setting_key     varchar(100) UNIQUE
setting_value   longtext
```
**Zweck:** Plugin-Einstellungen speichern

---

## 2. VEREINE
**Tabelle:** `dp_vereine`
```sql
id              mediumint(9) PK AUTO_INCREMENT
name            varchar(255) NOT NULL
kuerzel         varchar(10) NOT NULL UNIQUE
beschreibung    text
kontakt_name    varchar(255)
kontakt_email   varchar(255)
kontakt_telefon varchar(50)
aktiv           tinyint(1) DEFAULT 1
erstellt_am     datetime DEFAULT CURRENT_TIMESTAMP
```
**Zweck:** Organisationen/Vereine die Dienste leisten
**Beziehungen:**
- → veranstaltung_vereine (m:n)
- → verein_verantwortliche (m:n)
- → dienste (1:n)

---

## 3. VERANSTALTUNGEN
**Tabelle:** `dp_veranstaltungen`
```sql
id              mediumint(9) PK AUTO_INCREMENT
name            varchar(255) NOT NULL
beschreibung    text
typ             varchar(50) DEFAULT 'eintaegig'
status          varchar(50) DEFAULT 'geplant'
start_datum     date NOT NULL
end_datum       date
seite_id        bigint(20) (FK zu wp_posts)
erstellt_am     datetime DEFAULT CURRENT_TIMESTAMP
aktualisiert_am datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```
**Zweck:** Events/Veranstaltungen
**Beziehungen:**
- → veranstaltung_tage (1:n)
- → veranstaltung_vereine (m:n)
- → veranstaltung_verantwortliche (m:n)
- → dienste (1:n)

---

## 4. VERANSTALTUNG_TAGE
**Tabelle:** `dp_veranstaltung_tage`
```sql
id                  mediumint(9) PK AUTO_INCREMENT
veranstaltung_id    mediumint(9) NOT NULL (FK)
tag_datum           date NOT NULL
tag_nummer          tinyint(2) NOT NULL
von_zeit            time
bis_zeit            time
bis_datum           date (für Overnight)
dienst_von_zeit     time
dienst_bis_zeit     time
dienst_bis_datum    date (für Overnight)
notizen             text
```
**Zweck:** Einzelne Tage einer mehrtägigen Veranstaltung
**Beziehungen:**
- veranstaltungen → (n:1)
- → dienste (1:n)

---

## 5. VERANSTALTUNG_VEREINE (Zuordnungstabelle)
**Tabelle:** `dp_veranstaltung_vereine`
```sql
id                  mediumint(9) PK AUTO_INCREMENT
veranstaltung_id    mediumint(9) NOT NULL (FK)
verein_id           mediumint(9) NOT NULL (FK)
UNIQUE KEY (veranstaltung_id, verein_id)
```
**Zweck:** m:n Beziehung zwischen Veranstaltungen und Vereinen

---

## 6. VEREIN_VERANTWORTLICHE (Zuordnungstabelle)
**Tabelle:** `dp_verein_verantwortliche`
```sql
id              mediumint(9) PK AUTO_INCREMENT
verein_id       mediumint(9) NOT NULL (FK)
user_id         bigint(20) NOT NULL (FK zu wp_users)
erstellt_am     datetime DEFAULT CURRENT_TIMESTAMP
UNIQUE KEY (verein_id, user_id)
```
**Zweck:** m:n Beziehung zwischen Vereinen und WordPress-Benutzern (Verantwortliche)

---

## 7. VERANSTALTUNG_VERANTWORTLICHE (Zuordnungstabelle)
**Tabelle:** `dp_veranstaltung_verantwortliche`
```sql
id                  mediumint(9) PK AUTO_INCREMENT
veranstaltung_id    mediumint(9) NOT NULL (FK)
user_id             bigint(20) NOT NULL (FK zu wp_users)
erstellt_am         datetime DEFAULT CURRENT_TIMESTAMP
UNIQUE KEY (veranstaltung_id, user_id)
```
**Zweck:** m:n Beziehung zwischen Veranstaltungen und WordPress-Benutzern (Verantwortliche)

---

## 8. BEREICHE
**Tabelle:** `dp_bereiche`
```sql
id              mediumint(9) PK AUTO_INCREMENT
name            varchar(100) NOT NULL UNIQUE
beschreibung    text
farbe           varchar(7) DEFAULT '#3b82f6'
sortierung      int(3) DEFAULT 0
aktiv           tinyint(1) DEFAULT 1
erstellt_am     datetime DEFAULT CURRENT_TIMESTAMP
```
**Zweck:** Kategorien für Dienste (z.B. Technik, Catering, Ordner)
**Beziehungen:**
- → taetigkeiten (1:n)
- → dienste (1:n)

---

## 9. TAETIGKEITEN
**Tabelle:** `dp_taetigkeiten`
```sql
id              mediumint(9) PK AUTO_INCREMENT
bereich_id      mediumint(9) NOT NULL (FK)
name            varchar(100) NOT NULL
beschreibung    text
sortierung      int(3) DEFAULT 0
aktiv           tinyint(1) DEFAULT 1
erstellt_am     datetime DEFAULT CURRENT_TIMESTAMP
UNIQUE KEY (bereich_id, name)
```
**Zweck:** Spezifische Aufgaben innerhalb eines Bereichs
**Beziehungen:**
- bereiche → (n:1)
- → dienste (1:n)

**HINWEIS:** In der View werden zusätzliche Felder verwendet:
- `erforderliche_qualifikation` - sollte zur Tabelle hinzugefügt werden

---

## 10. DIENSTE
**Tabelle:** `dp_dienste`
```sql
id                  mediumint(9) PK AUTO_INCREMENT
veranstaltung_id    mediumint(9) NOT NULL (FK)
tag_id              mediumint(9) NOT NULL (FK zu veranstaltung_tage)
verein_id           mediumint(9) NOT NULL (FK)
bereich_id          mediumint(9) NOT NULL (FK)
taetigkeit_id       mediumint(9) NOT NULL (FK)
von_zeit            time
bis_zeit            time
bis_datum           date (für Overnight)
anzahl_personen     int(2) DEFAULT 1
status              varchar(50) DEFAULT 'geplant'
besonderheiten      text
splittbar           tinyint(1) DEFAULT 1
erstellt_am         datetime DEFAULT CURRENT_TIMESTAMP
```
**Zweck:** Einzelne Dienste/Schichten
**Status-Werte:** 'geplant', 'unvollstaendig', 'bestaetigt'
**Beziehungen:**
- veranstaltungen → (n:1)
- veranstaltung_tage → (n:1)
- vereine → (n:1)
- bereiche → (n:1)
- taetigkeiten → (n:1)
- → dienst_slots (1:n)
- → dienst_zuweisungen (1:n)

---

## 11. DIENST_SLOTS
**Tabelle:** `dp_dienst_slots`
```sql
id              mediumint(9) PK AUTO_INCREMENT
dienst_id       mediumint(9) NOT NULL (FK)
slot_nummer     tinyint(2) NOT NULL DEFAULT 1
mitarbeiter_id  mediumint(9) (FK)
von_zeit        time
bis_zeit        time
bis_datum       date (für Overnight)
status          varchar(20) DEFAULT 'offen'
erstellt_am     datetime DEFAULT CURRENT_TIMESTAMP
```
**Zweck:** Slots für splittbare Dienste (mehrere Personen pro Dienst)
**Beziehungen:**
- dienste → (n:1)
- mitarbeiter → (n:1, optional)
- → dienst_zuweisungen (1:n)

---

## 12. MITARBEITER
**Tabelle:** `dp_mitarbeiter`
```sql
id                      mediumint(9) PK AUTO_INCREMENT
vorname                 varchar(100) NOT NULL
nachname                varchar(100) NOT NULL
email                   varchar(100)
telefon                 varchar(50)
datenschutz_akzeptiert  tinyint(1) DEFAULT 0
erstellt_am             datetime DEFAULT CURRENT_TIMESTAMP
```
**Zweck:** Pool von Helfern (für Frontend Self-Service)
**Beziehungen:**
- → dienst_slots (1:n)
- → dienst_zuweisungen (1:n)

---

## 13. DIENST_ZUWEISUNGEN
**Tabelle:** `dp_dienst_zuweisungen`
```sql
id                  mediumint(9) PK AUTO_INCREMENT
dienst_id           mediumint(9) NOT NULL (FK)
slot_id             mediumint(9) (FK, optional)
mitarbeiter_id      mediumint(9) (FK, optional)
user_id             bigint(20) (FK zu wp_users, optional)
name                varchar(255)
email               varchar(255)
telefon             varchar(50)
kommentar           text
status              varchar(20) DEFAULT 'bestaetigt'
eingetragen_am      datetime DEFAULT CURRENT_TIMESTAMP
eingetragen_von     bigint(20) (FK zu wp_users)
```
**Zweck:** Historische/zusätzliche Zuweisungen (wird ggf. nicht mehr aktiv genutzt)
**Beziehungen:**
- dienste → (n:1)
- dienst_slots → (n:1, optional)
- mitarbeiter → (n:1, optional)
- wp_users → (n:1, optional)

---

## Datenfluss & Beziehungen

### Hierarchie: Veranstaltung → Dienst
```
Veranstaltung (Event)
  └─ Veranstaltung_Tage (einzelne Tage)
      └─ Dienste (Schichten)
          └─ Dienst_Slots (mehrere Personen pro Schicht)
              └─ Mitarbeiter (Zuweisungen)
```

### Klassifizierung: Dienst-Kategorisierung
```
Bereich (z.B. "Technik")
  └─ Tätigkeiten (z.B. "Tontechnik", "Lichttechnik")
      └─ Dienste (nutzen diese Tätigkeit)
```

### Zuständigkeit: Wer ist verantwortlich?
```
Verein
  ├─ Verein_Verantwortliche (WordPress User)
  ├─ Veranstaltung_Vereine (welche Events)
  └─ Dienste (welche Schichten)
```

---

## Fehlende Felder / Inkonsistenzen

### 1. Tätigkeiten: `erforderliche_qualifikation`
**Problem:** View `bereiche-taetigkeiten.php` verwendet Feld, existiert nicht in DB
**Lösung:** Migration hinzufügen

### 2. Status-Werte
**Dienste.status:**
- 'geplant' - Standard
- 'unvollstaendig' - Fehlende Informationen (gelb)
- 'bestaetigt' - Vollständig

**Dienst_Slots.status:**
- 'offen' - Noch keine Zuweisung
- (weitere Werte unklar)

### 3. Zeit-Felder: Overnight-Handling
**Implementiert in:**
- veranstaltung_tage: `bis_datum`, `dienst_bis_datum`
- dienste: `bis_datum`
- dienst_slots: `bis_datum`

**Validierung:** `validate_dienst_zeitfenster()` prüft und setzt automatisch +1 Tag

---

## Verwendete Dateien

### Aktive Core-Dateien:
- `includes/class-database.php` - **AKTIVE** Database-Klasse
- `includes/class-dienstplan-verwaltung.php` - Main Plugin-Klasse
- `admin/class-admin.php` - Admin-Backend

### Views (aktiv):
- `admin/views/overview.php` - Dienst-Übersicht (Matrix)
- `admin/views/bereiche-taetigkeiten.php` - Bereiche & Tätigkeiten Verwaltung
- `admin/views/partials/dienste-table.php` - Dienste-Tabelle mit Bulk-Actions

### Backup/Unused Dateien (können gelöscht werden):
- `includes/class-dienstplan-database.php` - DUPLICATE, verursacht Fehler
- `includes/class-dienstplan-database.backup.php` - Backup
- `includes/class-dienstplan-database-clean.php` - Alt
- `includes/class-database.backup.php` - falls vorhanden

---

## Empfohlene Aktionen

### 1. SOFORT: Duplikate löschen
```
DELETE: includes/class-dienstplan-database.php
DELETE: includes/class-dienstplan-database.backup.php
DELETE: includes/class-dienstplan-database-clean.php
```

### 2. Migration: Fehlende Felder hinzufügen
```sql
ALTER TABLE wp_dp_taetigkeiten 
ADD COLUMN erforderliche_qualifikation varchar(255) AFTER beschreibung;
```

### 3. Code-Audit: Prüfen Sie alle Referenzen
```bash
# Suchen nach falschen Includes
grep -r "class-dienstplan-database.php" .
```

### 4. Dokumentation aktualisieren
- README.md mit DB-Struktur
- Installations-Anleitung
- API-Dokumentation für Entwickler

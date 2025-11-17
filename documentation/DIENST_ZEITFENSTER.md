# Dienst-Zeitfenster Validierung

## Übersicht

Dienste müssen innerhalb des für den Veranstaltungstag definierten Zeitfensters liegen.

## Zeitfenster-Hierarchie

Für jeden Veranstaltungstag gibt es zwei Zeitfenster:

1. **Veranstaltungs-Zeitfenster** (`von_zeit`, `bis_zeit`)
   - Zeitraum der eigentlichen Veranstaltung
   - Hinterlegt in `dp_veranstaltung_tage`

2. **Dienst-Zeitfenster** (`dienst_von_zeit`, `dienst_bis_zeit`)
   - Zeitraum für Auf-/Abbau-Dienste
   - Optional - wenn nicht definiert, wird das Veranstaltungs-Zeitfenster verwendet
   - Hinterlegt in `dp_veranstaltung_tage`

## Validierungsregeln

Beim Erstellen oder Bearbeiten eines Dienstes (`dp_dienste`) gilt:

### 1. Dienst muss innerhalb des Zeitfensters liegen
```
Dienst-Start >= Zeitfenster-Start
Dienst-Ende <= Zeitfenster-Ende
```

### 2. Welches Zeitfenster wird verwendet?

**Falls Dienst-Zeitfenster definiert ist:**
```php
// Beispiel Tag:
von_zeit: 18:00
bis_zeit: 23:00
dienst_von_zeit: 16:00
dienst_bis_zeit: 01:00 (Folgetag)

// Dienst muss zwischen 16:00 und 01:00 (Folgetag) liegen
```

**Falls KEIN Dienst-Zeitfenster definiert ist:**
```php
// Beispiel Tag:
von_zeit: 18:00
bis_zeit: 23:00
dienst_von_zeit: NULL
dienst_bis_zeit: NULL

// Dienst muss zwischen 18:00 und 23:00 liegen
```

### 3. Über-Mitternacht-Dienste

Dienste können über Mitternacht gehen, wenn das Zeitfenster dies erlaubt:

```php
// Zeitfenster:
dienst_von_zeit: 20:00 (Sa)
dienst_bis_zeit: 04:00
dienst_bis_datum: 2024-11-17 (So)

// Gültiger Dienst:
von_zeit: 22:00 (Sa)
bis_zeit: 02:00
bis_datum: 2024-11-17 (So)
✓ GÜLTIG - liegt innerhalb 20:00 (Sa) bis 04:00 (So)

// Ungültiger Dienst:
von_zeit: 22:00 (Sa)
bis_zeit: 05:00
bis_datum: 2024-11-17 (So)
✗ UNGÜLTIG - Ende (05:00) liegt nach Zeitfenster-Ende (04:00)
```

## Implementierung

### Datenbankebene

**Validierungsmethode:**
```php
$db->validate_dienst_zeitfenster($tag_id, $von_zeit, $bis_zeit, $bis_datum = null);
```

**Rückgabe:**
```php
array(
    'valid' => true/false,
    'message' => 'Fehlermeldung falls ungültig'
)
```

**Automatische Validierung:**
- `add_dienst()` - validiert automatisch vor dem Einfügen
- `update_dienst()` - validiert automatisch vor dem Update

**Fehlerhandling:**
```php
$result = $db->add_dienst($data);

if (is_array($result) && isset($result['error'])) {
    // Validierungsfehler
    echo $result['message'];
    // z.B.: "Dienst-Ende (23:30) liegt nach dem Zeitfenster-Ende (23:00)"
} else {
    // Erfolg - $result enthält die dienst_id
    $dienst_id = $result;
}
```

## Backend-Integration

Im Admin-Bereich sollte beim Erstellen eines Dienstes:

1. **Tag-Auswahl:** Dropdown der verfügbaren Tage für die Veranstaltung
2. **Zeitfenster-Anzeige:** Nach Tag-Auswahl das gültige Zeitfenster anzeigen
3. **Von/Bis-Zeit Felder:** Mit HTML5 time-Inputs
4. **Bis-Datum Checkbox:** "Geht bis zum Folgetag" falls über Mitternacht
5. **Live-Validierung:** JavaScript prüft vor dem Speichern
6. **Server-Validierung:** PHP prüft final beim Speichern

### Beispiel UI-Flow

```
1. Admin wählt Veranstaltung "Kerb 2024"
2. Admin wählt Tag "Samstag, 16.11.2024"
   → System zeigt: "Zeitfenster: 16:00 - 01:00 (Folgetag)"
3. Admin füllt Dienst-Daten aus:
   - Verein: "Feuerwehr"
   - Bereich: "Ordner"
   - Tätigkeit: "Aufbau"
   - Von: 15:00 ← FEHLER: vor Zeitfenster-Start
   - Bis: 18:00
4. System zeigt Fehler: "Dienst-Start (15:00) liegt vor dem Zeitfenster-Start (16:00)"
5. Admin korrigiert auf 16:00 → Speichern erfolgreich
```

## Frontend-Integration

Im Frontend (Dienstplan-Anzeige für User):

1. **Nur gültige Dienste anzeigen** - bereits validiert beim Erstellen
2. **Zeitfenster-Info anzeigen** - "Verfügbar: 16:00 - 01:00 Uhr"
3. **Slot-Auswahl** - bei splittbaren Diensten Halbzeiten anzeigen

## Testfälle

### ✓ Gültige Dienste

```php
// Test 1: Innerhalb Veranstaltungs-Zeitfenster
Tag: 18:00 - 23:00
Dienst: 19:00 - 22:00 ✓

// Test 2: Innerhalb Dienst-Zeitfenster
Tag: von=18:00, bis=23:00, dienst_von=16:00, dienst_bis=01:00 (Folgetag)
Dienst: 16:30 - 00:30 (Folgetag) ✓

// Test 3: Exakt am Rand
Tag: 18:00 - 23:00
Dienst: 18:00 - 23:00 ✓
```

### ✗ Ungültige Dienste

```php
// Test 1: Start zu früh
Tag: 18:00 - 23:00
Dienst: 17:00 - 22:00 ✗
Fehler: "Dienst-Start (17:00) liegt vor dem Zeitfenster-Start (18:00)"

// Test 2: Ende zu spät
Tag: 18:00 - 23:00
Dienst: 19:00 - 23:30 ✗
Fehler: "Dienst-Ende (23:30) liegt nach dem Zeitfenster-Ende (23:00)"

// Test 3: Über Mitternacht ohne Folgetag-Flag
Tag: dienst_von=20:00, dienst_bis=01:00 (Folgetag)
Dienst: 22:00 - 02:00 (KEIN bis_datum gesetzt) ✗
Fehler: "Dienst-Ende (02:00) liegt nach dem Zeitfenster-Ende (01:00)"

// Test 4: Start nach Ende
Dienst: 22:00 - 20:00 ✗
Fehler: "Dienst-Start muss vor Dienst-Ende liegen"
```

## Datenbank-Schema

```sql
-- Veranstaltungstag mit Zeitfenstern
CREATE TABLE wp_dp_veranstaltung_tage (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    veranstaltung_id mediumint(9) NOT NULL,
    tag_datum date NOT NULL,
    
    -- Veranstaltungs-Zeitfenster
    von_zeit time,
    bis_zeit time,
    bis_datum date,  -- Falls über Mitternacht
    
    -- Dienst-Zeitfenster (optional)
    dienst_von_zeit time,
    dienst_bis_zeit time,
    dienst_bis_datum date,  -- Falls über Mitternacht
    
    -- ...
    PRIMARY KEY (id)
);

-- Dienst mit Zeiten
CREATE TABLE wp_dp_dienste (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    tag_id mediumint(9) NOT NULL,
    
    -- Dienst-Zeiten (müssen innerhalb des Tag-Zeitfensters liegen)
    von_zeit time,
    bis_zeit time,
    bis_datum date,  -- Falls über Mitternacht
    
    -- ...
    PRIMARY KEY (id)
);
```

## Migration

**Bestehende Dienste:** Werden automatisch validiert, wenn sie bearbeitet werden. Bereits gespeicherte Dienste mit ungültigen Zeiten bleiben erhalten, können aber nicht mehr gespeichert werden nach einer Änderung, ohne die Zeiten zu korrigieren.

**Empfehlung:** Nach Plugin-Update einmal alle Dienste prüfen:

```sql
-- Finde Dienste außerhalb des Zeitfensters
SELECT d.id, d.tag_id, d.von_zeit, d.bis_zeit, 
       t.dienst_von_zeit, t.dienst_bis_zeit
FROM wp_dp_dienste d
JOIN wp_dp_veranstaltung_tage t ON d.tag_id = t.id
WHERE d.von_zeit < COALESCE(t.dienst_von_zeit, t.von_zeit)
   OR d.bis_zeit > COALESCE(t.dienst_bis_zeit, t.bis_zeit);
```

## Changelog

**Version 0.2.2**
- ✓ Zeitfenster-Validierung für Dienste implementiert
- ✓ Unterstützung für Über-Mitternacht-Dienste
- ✓ Automatische Validierung in `add_dienst()` und `update_dienst()`
- ✓ Fallback auf Veranstaltungs-Zeitfenster wenn Dienst-Zeitfenster nicht definiert

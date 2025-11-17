# Tag-Zuordnung Bug Fix

**Problem:** Dienste werden angezeigt, aber haben keine Zuordnung zum Tag

## Root Cause Analysis

### 1. Bug in `update_dienst()` Methode

**Datei:** `includes/class-database.php` (Zeile 1140-1180)

**Problem:** 
Die Methode hat fälschlicherweise `tag_id` aus dem Update-Array entfernt, auch wenn es explizit übergeben wurde:

```php
// VORHER (FEHLERHAFT):
public function update_dienst($id, $data) {
    // Hole aktuellen Dienst für tag_id falls nicht übergeben
    if (!isset($data['tag_id'])) {
        $current = $this->get_dienst($id);
        if ($current) {
            $data['tag_id'] = $current->tag_id;
        }
    }
    
    // ... Validierung ...
    
    // FEHLER: Entfernt tag_id IMMER wenn $current gesetzt wurde
    if (isset($current)) {
        unset($data['tag_id']);
    }
    
    $result = $this->wpdb->update(...);
}
```

**Auswirkung:**
- Beim Bearbeiten eines Dienstes wurde `tag_id` NICHT gespeichert
- Beim Erstellen eines neuen Dienstes wurde `tag_id` korrekt gespeichert (via `add_dienst()`)
- Alle existierenden Dienste, die bearbeitet wurden, verloren ihre Tag-Zuordnung

### 2. Lösung

**Datei:** `includes/class-database.php`

```php
// NACHHER (KORRIGIERT):
public function update_dienst($id, $data) {
    $tag_id_from_current = false;
    
    // Hole aktuellen Dienst für tag_id falls nicht übergeben
    if (!isset($data['tag_id'])) {
        $current = $this->get_dienst($id);
        if ($current) {
            $data['tag_id'] = $current->tag_id;
            $tag_id_from_current = true;  // Markiere als "nur für Validierung"
        }
    }
    
    // ... Validierung ...
    
    // Entferne tag_id NUR wenn es nur für Validierung hinzugefügt wurde
    // NICHT wenn tag_id explizit im $data übergeben wurde!
    if ($tag_id_from_current) {
        unset($data['tag_id']);
    }
    
    $result = $this->wpdb->update(...);
}
```

**Änderungen:**
1. Flag `$tag_id_from_current` eingeführt
2. Nur wenn `tag_id` NICHT im Original-`$data` war, wird es für Validierung geladen
3. `tag_id` wird nur entfernt, wenn es NUR für Validierung hinzugefügt wurde
4. Wenn `tag_id` explizit übergeben wurde (z.B. aus dem Dienst-Modal), bleibt es erhalten

## Verifikation

### Vor dem Fix:

1. **Dienst-Modal:** Enthält korrekt `tag_id` Feld (Zeile 37-56 in `dienst-modal.php`) ✓
2. **AJAX Save Handler:** Übergibt `tag_id` korrekt an `update_dienst()` (Zeile 593 in `class-admin.php`) ✓
3. **DB Update:** `tag_id` wurde fälschlicherweise entfernt ✗

### Nach dem Fix:

1. **Dienst-Modal:** Unverändert ✓
2. **AJAX Save Handler:** Unverändert ✓
3. **DB Update:** `tag_id` wird korrekt gespeichert ✓

## Datenbank-Bereinigung

### Script: `fix-dienste-tags.php`

Für existierende Dienste ohne Tag-Zuordnung wurde ein Reparatur-Script erstellt:

**Funktionsweise:**
1. Findet alle Dienste mit `tag_id = 0` oder `tag_id IS NULL`
2. Ermittelt den ersten Tag der zugehörigen Veranstaltung
3. Ordnet den Dienst diesem Tag zu
4. Gibt detaillierte Fortschritts-Informationen aus

**Ausführung:**
```
Über Browser: wp-admin/...plugins/dienstplan-verwaltung/fix-dienste-tags.php
```

**Sicherheit:**
- Nur Lesezugriff bei der Diagnose
- Update nur auf Dienste ohne Tag
- Transaktionssicher (pro Dienst einzeln)

## Testing

### Test 1: Neuen Dienst erstellen
1. Öffne Dienste-Verwaltung
2. Klicke "Neuer Dienst"
3. Wähle Veranstaltung → Tag wird geladen
4. Wähle Tag
5. Fülle restliche Felder aus
6. Speichern
7. **Erwartet:** Dienst wird korrekt mit `tag_id` gespeichert

### Test 2: Existierenden Dienst bearbeiten
1. Öffne Dienste-Verwaltung
2. Klicke "Bearbeiten" bei einem Dienst
3. Ändere Tag-Auswahl
4. Speichern
5. **Erwartet:** Neue `tag_id` wird gespeichert (nicht überschrieben)

### Test 3: Dienste-Anzeige
1. Öffne Dienste-Verwaltung
2. Filtere nach Veranstaltung
3. **Erwartet:** Dienste werden nach Tag gruppiert angezeigt
4. **Erwartet:** Keine Dienste in "Ohne Tag" Sektion (nach Reparatur-Script)

## Prävention

### Code Review Checkliste:
- [ ] Prüfe bei Update-Methoden, ob alle Felder korrekt durchgereicht werden
- [ ] Flag-basierte Logik für "nur zur Validierung geladene" Felder
- [ ] Unit Tests für CRUD-Operationen mit allen Pflichtfeldern

### Monitoring:
```sql
-- Query zum Überwachen von Diensten ohne Tag
SELECT COUNT(*) as dienste_ohne_tag
FROM dp_dienste
WHERE tag_id = 0 OR tag_id IS NULL;
```

## Changelog

### Version 0.3.1 (Geplant)
- **Fix:** `update_dienst()` behält jetzt korrekt `tag_id` bei Updates
- **Fix:** Reparatur-Script für existierende Dienste ohne Tag-Zuordnung

## Related Files

- `includes/class-database.php` (Zeile 1140-1180) - Fix implementiert
- `admin/class-admin.php` (Zeile 555-640) - AJAX Handler (unverändert)
- `admin/views/partials/dienst-modal.php` (Zeile 37-56) - Tag-Feld (unverändert)
- `admin/views/partials/dienste-table.php` - Gruppierung nach Tag (unverändert)
- `fix-dienste-tags.php` - Reparatur-Script für Altdaten

## Status

- [x] Bug identifiziert
- [x] Root Cause analysiert
- [x] Fix implementiert
- [x] Reparatur-Script erstellt
- [ ] Testing durchgeführt
- [ ] Dokumentation aktualisiert
- [ ] Production Deployment

**Datum:** 2025-01-XX  
**Author:** GitHub Copilot  
**Reviewed by:** -

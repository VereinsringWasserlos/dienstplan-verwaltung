# Test-Checkliste: Dummy-Mitarbeiter, Portal-Zugänge und Zuweisungen

Stand: 09.03.2026  
Ziel: End-to-End-Test der neuen Funktionalität (Auto-Useranlage + direkte User↔Verein-Zuordnung)

---

## 1) Dummy-Daten erzeugen

Im Plugin-Ordner ausführen:

```bash
php tools/seed-dummy-test-data.php
```

Erwartetes Ergebnis in der Konsole:
- Anzahl erstellter/aktualisierter Dummy-User
- Anzahl verknüpfter Mitarbeiter
- Anzahl gesetzter Slot-Zuweisungen

### Standard-Testzugang
- Benutzername: `testcrew01`
- E-Mail: `testcrew01@dienstplan.local`
- Passwort (für alle Dummy-User): `Test1234`

---

## 2) Backend-Prüfungen

### 2.1 Mitarbeiterliste
Pfad: `Dienstplan → Mitarbeiter`

Prüfen:
- Dummy-Mitarbeiter `Test01 Crew` bis `Test12 Crew` vorhanden
- `Portal`-Status bei Dummy-Mitarbeitern = aktiv
- Bulk-Funktionen (Portal aktivieren/deaktivieren/exportieren) reagieren korrekt

### 2.2 Portal-Verwaltung
Pfad: `Dienstplan → Portal-Verwaltung`

Prüfen:
- Portal-Seite vorhanden/erstellbar
- Link zur Bearbeitung/Ansicht funktioniert

### 2.3 Dienste/Slots
Pfad: `Dienstplan → Dienste` (bzw. Veranstaltungsansicht)

Prüfen:
- Es existieren besetzte Slots mit Dummy-Mitarbeitern
- Besetzungsstatus auf `besetzt` gesetzt

---

## 3) Datenbank-Prüfungen (optional, aber empfohlen)

SQL-Checks in phpMyAdmin/DB-Tool:

```sql
SELECT COUNT(*) AS crew_users
FROM wp_users
WHERE user_email LIKE 'testcrew%@dienstplan.local';
```

```sql
SELECT COUNT(*) AS mitarbeiter_links
FROM wp_dp_mitarbeiter
WHERE email LIKE 'testcrew%@dienstplan.local'
  AND user_id IS NOT NULL;
```

```sql
SELECT COUNT(*) AS user_verein_links
FROM wp_dp_user_vereine uv
JOIN wp_users u ON u.ID = uv.user_id
WHERE u.user_email LIKE 'testcrew%@dienstplan.local';
```

```sql
SELECT COUNT(*) AS besetzte_slots
FROM wp_dp_dienst_slots s
JOIN wp_dp_mitarbeiter m ON m.id = s.mitarbeiter_id
WHERE m.email LIKE 'testcrew%@dienstplan.local';
```

Hinweis: Bei anderem WP-Tabellenpräfix `wp_` in den SQLs anpassen.

---

## 4) Frontend-/Portal-Tests

### 4.1 Login als Dummy-User
- Mit `testcrew01` / `Test1234` im Portal einloggen
- Erwartung: Login erfolgreich, keine Backend-Zugriffsrechte

### 4.2 Security-Redirect für Crew
- Teste direkten Aufruf `/wp-admin/` als Dummy-User
- Erwartung: Redirect ins Frontend-Portal (kein Admin-Backend)

### 4.3 Meine Dienste
- Seite mit `[meine_dienste]` öffnen
- Erwartung: zugewiesene Dienste sichtbar

### 4.4 Profil bearbeiten
- Seite mit `[profil_bearbeiten]` öffnen
- Erwartung: Profiländerung speicherbar

---

## 5) Funktionstests der neuen Implementierung

### 5.1 Auto-Useranlage bei neuer Dienstanmeldung
Vorgehen:
1. Freien Slot im Frontend wählen
2. Neue E-Mail-Adresse verwenden (kein bestehender WP-User)
3. Anmeldung absenden

Erwartung:
- Neuer `mitarbeiter`-Datensatz bzw. Verknüpfung erstellt
- Neuer WP-User mit Rolle `dienstplan_crew` erstellt
- `mitarbeiter.user_id` gesetzt
- Eintrag in `dp_user_vereine` vorhanden

### 5.2 Verknüpfung bei bestehender E-Mail
Vorgehen:
1. Anmeldung mit E-Mail eines bereits existierenden WP-Users

Erwartung:
- Kein doppelter WP-User
- Mitarbeiter korrekt auf bestehenden User verlinkt
- `dp_user_vereine` Zuordnung gesetzt/aktualisiert

### 5.3 Deaktivierung Portalzugriff
Vorgehen:
1. Im Backend Portalzugriff für Dummy-Mitarbeiter deaktivieren

Erwartung:
- `mitarbeiter.user_id` wird entfernt
- User↔Verein-Zuordnungen für den User werden entfernt

---

## 6) Aufräumen nach Tests (optional)

Empfohlene Cleanup-Reihenfolge:
1. Portalzugriff Dummy-Mitarbeiter deaktivieren (Backend-Bulk)
2. Dummy-Mitarbeiter löschen (`email LIKE testcrew%@dienstplan.local`)
3. Dummy-WP-User löschen (`user_email LIKE testcrew%@dienstplan.local`)
4. Falls angelegt: Seeder-Testevent und Testverein entfernen

---

## 7) Abnahmekriterien

Feature gilt als bestanden, wenn:
- Dienstanmeldung erzeugt/verknüpft User automatisch
- Direkte Zuordnung User↔Verein ist in DB vorhanden
- Portalzugriff-Aktivierung/Deaktivierung synchronisiert Zuordnungen korrekt
- Crew-User wird vom Backend ins Frontend umgeleitet

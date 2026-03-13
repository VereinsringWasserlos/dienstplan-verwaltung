# Dienstplan Verwaltung - Rollenuebersicht (Stand 0.9.5.24)

## Zielstruktur: 4 Rollen

Im Plugin sind genau diese 4 Dienstplan-Rollen vorgesehen:

1. Haupt-Admin
2. Veranstaltungs-Admin
3. Vereins-Admin
4. Crew

Hinweis:
- WordPress-`administrator` bleibt eine eigene WP-Systemrolle und kann immer alles.
- Die 4 Rollen oben sind die Plugin-Rollen.

---

## Technische Slugs (v2)

### Rollen
- `dpv2_general_admin` = Haupt-Admin
- `dpv2_event_admin` = Veranstaltungs-Admin
- `dpv2_club_admin` = Vereins-Admin
- `dpv2_crew` = Crew

### Capabilities (v2)
- `dpv2_manage_settings`
- `dpv2_manage_users`
- `dpv2_manage_events`
- `dpv2_manage_clubs`
- `dpv2_view_reports`
- `dpv2_send_notifications`

---

## Aktueller Betriebsmodus (wichtig)

Wegen Hard-Reset ist die Rechtepruefung aktuell bewusst vereinfacht:

- Backend-Berechtigungen laufen derzeit nur ueber `manage_options`.
- Das bedeutet praktisch: volle Admin-Funktionen aktuell nur fuer WordPress-Administratoren.
- Die v2-Rollen und v2-Capabilities sind bereits eingefuehrt und werden bereinigt/migriert, die fachliche Feinlogik wird im naechsten Schritt wieder aufgebaut.

---

## Rollenbeschreibung

### 1) Haupt-Admin
- Zweck: zentrale Dienstplan-Administration
- Typischer Scope (Zielbild): Einstellungen, Benutzer, Veranstaltungen, Vereine, Reports
- Rolle: `dpv2_general_admin`

### 2) Veranstaltungs-Admin
- Zweck: operative Verwaltung von Veranstaltungen und Diensten
- Typischer Scope (Zielbild): Veranstaltungen/Dienste, Reports
- Rolle: `dpv2_event_admin`

### 3) Vereins-Admin
- Zweck: Verwaltung von Vereinen und zugeordneten Daten
- Typischer Scope (Zielbild): Vereine, zugeordnete Veranstaltungen/Dienste, Reports
- Rolle: `dpv2_club_admin`

### 4) Crew
- Zweck: Frontend-Portal-Nutzung (kein Admin-Backend)
- Rolle: `dpv2_crew`

---

## Matrix: Sollbild vs. aktueller Baseline-Stand

| Bereich | Haupt-Admin | Veranstaltungs-Admin | Vereins-Admin | Crew |
|---|---|---|---|---|
| **Sollbild v2** ||||| 
| Backend Einstellungen | Ja | Nein | Nein | Nein |
| Benutzerverwaltung | Ja | Nein | Nein | Nein |
| Veranstaltungen/Dienste | Ja | Ja | Teilweise | Nein |
| Vereine | Ja | Teilweise | Ja | Nein |
| Frontend Portal | Optional | Optional | Optional | Ja |
| **Aktuell 0.9.5.24 (Baseline)** ||||| 
| Backend-Zugriff | Nur mit `manage_options` | Nur mit `manage_options` | Nur mit `manage_options` | Nein |

---

## Migration und Altlasten

Bei Rolleninstallation wird automatisch:

1. Legacy-Rollen entfernt (`dp_general_admin`, `dp_event_admin`, `dp_club_admin`, `dienstplan_crew`)
2. Legacy-Capabilities entfernt (`dp_manage_*`, `dp_view_reports`, `dp_send_notifications`)
3. Benutzer von Legacy-Rollen auf v2-Rollen migriert

Damit werden Konflikte mit alten Resten minimiert.

---

## Begriffe in der Oberflaeche

Fuer die UI gilt einheitlich:

- Haupt-Admin
- Veranstaltungs-Admin
- Vereins-Admin
- Crew

Der Begriff "Event Admin" wird nicht mehr verwendet.

---

## Naechster Schritt (v2 Rechte-Engine)

Geplant ist der Wiederaufbau der Feinlogik auf Basis der v2-Slugs:

1. zentrale Access-Checks (eine Quelle der Wahrheit)
2. klare Scope-Regeln fuer Veranstaltungs- und Vereins-Admins
3. konsistente Menue-/AJAX-Pruefungen
4. finale Matrix ohne Sonderfaelle

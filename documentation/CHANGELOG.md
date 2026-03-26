# Changelog

Alle wichtigen Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0/0/),
und dieses Projekt folgt [Semantic Versioning](https://semver.org/lang/de/).

---

## [Unreleased]

---

## [0.9.5.58] - 2026-03-26 🗄️ Debug: DB-Strukturansicht

### ✨ Neu

- **Debug-Seite: Aktuelle Datenbankstruktur**: Neuer Abschnitt zeigt alle Plugin-Tabellen (`dp_`-Präfix) als ausklappbare Detailblöcke. Jede Tabelle zeigt Zeilenanzahl, alle Spalten mit Typ/Null/Key/Default/Extra sowie alle Indizes — direkt per `SHOW FULL COLUMNS` und `SHOW INDEX` aus der laufenden DB abgerufen.

---

## [0.9.5.57] - 2026-03-26 🐛 Frontend: Verein-Kürzel aus DB statt generiert

### 🐛 Bugfixes

- **Titel-Header in `veranstaltung-verein.php` zeigte generierte Abkürzungen**: Die Funktion `$dp_get_verein_abbrev()` erzeugte Initialen aus dem Vereinsnamen (z.B. "FFW" aus "Freiwillige Feuerwehr Wasserlos") anstatt das gespeicherte `kuerzel`-Feld aus der DB zu nutzen. Fix: Beide Stellen (einzelner Verein + Mehrfach-Vereine bei `verein_id=0`) nutzen jetzt `$verein->kuerzel` bzw. `$v_obj->kuerzel`, mit `$dp_get_verein_abbrev()` nur noch als Fallback wenn kein Kürzel gespeichert ist.

---

## [0.9.5.56] - 2026-03-26 🔒 Dashboard: Debug & Statistik für Vereins-Admin ausgeblendet

### 🐛 Bugfixes

- **Dashboard-Kachel 'Debug & Wartung' ohne Berechtigung sichtbar**: Fehlender `manage_options`-Guard ergaenzt. Kachel jetzt nur noch für WordPress-Administratoren sichtbar.
- **Dashboard-Kachel 'Event-Statistik' für eingeschränkte Vereins-Admins sichtbar**: Die Statistik-Seite ist per Menue-Registrierung fuer `is_restricted_club_admin` gesperrt, die Dashboard-Kachel fehlte aber die entsprechende Prüfung. Ergaenzt durch `!$is_restricted_club_admin`-Bedingung.

---

## [0.9.5.55] - 2026-03-26 🔒 Vereins-Admin: Nav-Buttons Veranstaltungen & Bereiche ausgeblendet

### 🐛 Bugfixes

- **Vereins-Admin sah Veranstaltungen und Bereiche & Tätigkeiten im Page-Header-Nav**: Die `$can_show_nav_item`-Funktion in `page-header.php` prüfte nur Capabilities, nicht aber `is_restricted_club_admin`. Da `dpv2_club_admin` die Capability `dpv2_manage_events` hat, wurden beide Buttons trotzdem angezeigt. Fix: Neue statische Methode `Dienstplan_Roles::is_restricted_club_admin()` hinzugefügt; `page-header.php` blendet für eingeschränkte Vereins-Admins die Navigation zu `dienstplan-veranstaltungen` und `dienstplan-bereiche` aus.

---

## [0.9.5.54] - 2026-03-26 🔒 Vereins-Admin: Veranstaltungen & Bereiche ausgeblendet

### 🐛 Bugfixes

- **Vereins-Admin sah Veranstaltungen und Bereiche & Tätigkeiten**: Weil `dpv2_club_admin` die Capability `dpv2_manage_events` hat, konnte er auf diese Seiten zugreifen und Seiten für Veranstaltungen erstellen. Jetzt gilt: Eingeschränkte Vereins-Admins (`is_restricted_club_admin`) werden aus dem Menü, den Display-Handlern und dem AJAX-Handler `ajax_create_event_page()` herausgefiltert. Auf dem Dashboard werden Veranstaltungen- und Bereiche-Kacheln ebenfalls nicht mehr angezeigt. Dienste, Mitarbeiter und Dienst-Übersicht bleiben weiterhin zugänglich (auf eigene Vereinsdaten eingeschränkt).

---

## [0.9.5.53] - 2026-03-26 🔐 Rollen-Zuweisung beim Verein-Verantwortlichen-Fix

### 🐛 Bugfixes

- **Rolle fehlte beim Verein-Zuweisung**: Wenn ein bestehender WordPress-User als Verantwortlicher einem Verein zugewiesen wurde, erhielt er die Rolle `dpv2_club_admin` (Vereins-Admin) nicht automatisch. Die Funktion `sync_direct_verein_user_assignments()` hat bislang nur den DB-Eintrag gesetzt. Jetzt wird beim Hinzufügen die Rolle gesetzt (sofern keine höhere Rolle wie `dpv2_event_admin`, `dpv2_general_admin` oder `administrator` vorhanden ist). Beim Entfernen aus dem letzten Verein wird die Rolle wieder auf `subscriber` zurückgesetzt.

---

## [0.9.5.52] - 2026-03-27 👤 Neuer-Mitarbeiter im Frontend-Modal + Datenschutz-URL

### ✨ Neu

- **Neuen Mitarbeiter direkt im Frontend anlegen**: Admins sehen im „Dienst übernehmen"-Modal (eingeloggt) jetzt einen „+ Neuen Mitarbeiter anlegen"-Button. Über ein Inline-Formular (Vor-/Nachname*, E-Mail, Telefon) kann ein neuer Mitarbeiter angelegt und sofort ausgewählt werden.
- **Datenschutz-URL in Einstellungen**: Unter Einstellungen → Allgemein gibt es jetzt Felder für Datenschutzerklärung-URL und Impressum-URL. Die Datenschutz-URL wird im Anmelde-Modal als klickbarer Link angezeigt.

### 🐛 Bugfixes

- **Nonce-Konflikt behoben**: Für Admin-AJAX-Aktionen im Frontend (z.B. Mitarbeiter anlegen) wird `dp_ajax_nonce` separat als `window.dpAdminNonce` ausgegeben, sodass der Handler in `class-admin.php` die Nonce korrekt validieren kann.

---

## [0.9.5.51] - 2026-03-26 📧 Eingebaute SMTP-Konfiguration

### ✨ Neu

- **SMTP-Konfiguration direkt im Plugin**: Unter Einstellungen → E-Mail-Versand gibt es jetzt eine eigene SMTP-Sektion. Dort können Host, Port, Verschlüsselung (STARTTLS/SSL/Keine), Authentifizierung sowie Benutzername und Passwort hinterlegt werden. Eintragen und speichern genügt – kein externes SMTP-Plugin mehr nötig.
- **`phpmailer_init`-Hook**: Das Plugin hängt sich in WordPress' PHPMailer-Initialisierung ein und konfiguriert den SMTP-Transport automatisch, wenn SMTP aktiviert ist.
- Passwort-Feld wird beim Speichern **nur überschrieben wenn nicht leer** – sicheres erneutes Speichern ohne versehentliches Löschen.

---

## [0.9.5.50] - 2026-03-25 🔧 Updater: robusteres Umbenennen nach Update

### 🐛 Bugfixes

- **Updater `post_install_rename`**: `$wp_filesystem->delete()` schlug fehl wenn das Plugin-Verzeichnis ein `.git`-Unterordner enthielt (z.B. nach manuellem `git clone`-Deployment). Neuer Fallback: rekursives Löschen via PHP-nativer Funktion `recursive_rmdir()`. Zusätzlich fallback auf `rename()` wenn `$wp_filesystem->move()` scheitert.

---

## [0.9.5.49] - 2026-03-25 🔧 Bugfixes & Server-Deployment

### 🐛 Bugfixes

- **Updater**: `check_update_manually()` setzte den In-Memory-Cache `$this->update_info` nicht zurück – wenn `check_for_updates()` als WP-Filter früher im selben Request lief, wurde der gecachte Wert statt frischer API-Daten geliefert. Behoben durch `$this->update_info = null` vor dem Neuabruf.

### 🚀 Deployment

- Plugin auf Produktionsserver (vereinsring-wasserlos.de) per Git-Clone auf 0.9.5.49 aktualisiert.
- Diagnose: Fluent SMTP war auf PHP `mail()` konfiguriert; `sendmail` fehlt auf dem Server → SMTP-Konfiguration in Fluent SMTP erforderlich.

---

## [0.9.5.48] - 2026-03-25 📧 E-Mail-Konfiguration & Frontend-Verbesserungen

### ✨ Neue Features

- **E-Mail-Einstellungen (neuer Tab)**: Eigener Konfigurationsbereich unter Einstellungen → E-Mail-Versand.
  - Absender-Name, Absender-E-Mail (From) und Reply-To zentral konfigurierbar.
  - Filter `wp_mail_from`, `wp_mail_from_name` und `wp_mail` (Reply-To) werden plugin-weit gesetzt.
  - Einzelne E-Mail-Typen (Buchungsbestätigung, Portal-Einladung, Dienste-Übersicht) können separat aktiviert/deaktiviert werden.
  - Test-Mail-Funktion direkt aus dem Einstellungsbereich.
- **Einstellungen-Kachel im Dashboard**: Die Einstellungsseite ist jetzt direkt über das Admin-Dashboard erreichbar.

### 🎨 UI-Verbesserungen

- **Verein-Auswahl (Frontend)**: Umstellung von großen Karten auf kompakte Listenansicht.
  - Kürzel aus der Datenbank wird als Badge angezeigt (kein automatisch generierter Fallback mehr).
  - Name, Dienst-Anzahl und freie Plätze auf einen Blick.
- **Dienst übernehmen – Formular**: Telefon-Feld entfernt. E-Mail ist nur noch Pflichtfeld wenn „Benutzerkonto anlegen: Ja" ausgewählt wird.

### 🐛 Bugfixes

- Bestätigungsmeldung „Mail wurde versandt" erschien auch bei Auswahl „Nein" für Kontoerstellung – behoben.
- E-Mail wird serverseitig nur noch abgesendet wenn eine Adresse vorhanden ist.

---

## [0.9.5.47] - 2026-03-24 🚀 Dashboard Version & Import UX Release

### ✨ Verbesserungen

- Dashboard-Header zeigt jetzt die aktuelle Plugin-Version direkt im Seitenkopf an.
- Import-Ergebnisanzeige trennt Fehler, Warnungen und reine Infos in klaren Blöcken.
- Nicht gefundene Vereine können nach dem Import direkt zugeordnet werden; die Zuordnung wird sofort auf die gerade importierten Dienste angewendet.
- Gespeicherte Vereins-Aliase bleiben für Folgeimporte erhalten.

### 🐛 Bugfixes

- Kompaktansicht im Frontend stapelt Tagesblöcke wieder korrekt untereinander statt nebeneinander.
- Public-Templates respektieren wieder die Standard-Positionierung des aktiven Themes statt eigene Root-Zentrierungen zu erzwingen.

### 📦 Release

- Versionsstand auf 0.9.5.47 angehoben und Release-ZIP neu erstellt.

---

### 🧹 Bereinigung

- Legacy-Backup-Fallback für Import/Export entfernt; aktuelle View wird wieder konsistent geladen.
- Temporäre Backup-Artefakte (`import-export.backup.php`, `dp-import-export.backup.js`) aus dem Codebestand entfernt.
- Mehrere JavaScript-Dateien auf optionales Debug-Logging umgestellt (`window.dpTimelineDebug`, `window.dpImportExportDebug`, `window.dpAdminDebug`, `window.dpAdminModalsDebug`, `window.dpBesetzungDebug`).
- README auf aktuellen Versions- und Dokumentationsstand (0.9.5.46) konsolidiert.

---

## [0.9.5.46] - 2026-03-21 📦 Release Packaging Hotfix

### 🛠 Fixes

- Release-ZIP für Deployment neu erstellt und bereinigt.
- Doppelte Archiveinträge entfernt.
- Backup-Dateien aus dem Release-Paket ausgeschlossen.
- Paketstruktur für WordPress-Update robuster gemacht (Top-Level Plugin-Ordner).

---

## [0.9.5.45] - 2026-03-18 🔒 Admin-only Services & Bereiche Release

### ✨ Neue Features

- **Admin-only Dienste / Bereiche / Tätigkeiten**: Neue Flaggung möglich, um bestimmte Dienste (z.B. Nachtwache, Kuchenbacken) nur durch Administratoren zuweisen zu können.
- Datenbank-Migration: `bereiche.admin_only` und `taetigkeiten.admin_only` Spalten hinzugefügt.
- Admin-UI: Checkbox in Bereiche- und Tätigkeiten-Formularen zum Setzen des Admin-only-Flags.
- Frontend-Validierung: Lock-Button (🔒) für Normal-Nutzer bei Admin-only Diensten mit erklärendem Tooltip.
- Backend-Sicherheit: Doppelte AJAX-Validierung gegen unauthorized Zugriffe auf Admin-only Dienste.

### 🐛 Bugfixes

- Admin-only Checkbox lädt korrektes Flag beim Bearbeiten existierender Bereiche/Tätigkeiten.

---

## [0.9.5.44] - 2026-03-18 📊 Statistik-Seite & Admin-UX Cleanup Release

### ✨ Verbesserungen

- Neue versteckte Admin-Seite `dienstplan-statistik` eingefuehrt: pro Veranstaltung auswertbare Statistik mit Event-Auswahl.
- Statistik 1 (Gleichverteilung): Pruefung pro Tag/Bereich, ob Dienste gleichmaessig ueber Vereine verteilt sind.
- Statistik 2 (Zeitplan-Abdeckung): Pruefung pro 30-Minuten-Fenster und Bereich auf Mindestbesetzung (>= 2 Personen).
- Direkter Statistik-Link (📊) in der Veranstaltungsliste pro Event hinzugefuegt.
- Dashboard um Navigation zur neuen Event-Statistik erweitert.

### 🧹 Bereinigung

- Inline-Statistik aus der Veranstaltungs-Detailausklappung entfernt (jetzt zentrale Statistik-Seite).
- Veraltete JS-Nachkonvertierung von Action-Dropdowns entfernt, um visuelles Flackern zu vermeiden.
- Verbleibende Alt-Action-Menues auf direkte Emoji-Buttons mit Tooltip umgestellt.

### 🐛 Bugfixes

- Share-Links nutzen jetzt bevorzugt den Permalink statt numerischer `?p=`-Links.
- Uneinheitliche Icon-Buttons in Event-/Vereinsseiten-Aktionen visuell vereinheitlicht.

---

## [0.9.5.43] - 2026-03-16 🧭 Frontend Timeline & Vereins-UX Release

### ✨ Verbesserungen

- Timeline-Balken zeigen jetzt den Dienstnamen statt der Uhrzeit.
- Admins sehen zugewiesene Namen konsistent in mehreren Frontend-Ansichten (u. a. Timeline/Kompakt).
- Vereinsfarben werden in Frontend-Elementen durchgaengiger genutzt (Balken/Buttons).

### 🐛 Bugfixes

- Modal-Handling im Frontend stabilisiert (oeffnen/schliessen robuster bei Uebernehmen/Abbrechen).
- Vereins-Bearbeiten im Adminbereich abgesichert (JS-Handler und Cache-Busting fuer Modal-Script).
- Layout-/Filter-Regressions auf der Veranstaltungs-Vereinsseite korrigiert.

---

## [0.9.5.42] - 2026-03-13 🚀 Rollen-, Modal- und Zuweisungs-Release

### ✨ Verbesserungen

- Mitarbeiter koennen mehreren Vereinen stabil zugeordnet werden; manuelle Vereinsauswahl im Mitarbeiter-Modal wurde konsolidiert.
- Club-Admin-Rechte geschaerft:
  - Bereiche nur noch ansehen, nicht bearbeiten.
  - Dienste nicht mehr bearbeiten/kopieren/loeschen, nur Besetzung (Zuteilen) und Splitten.
- Dienste-Tabelle erhielt klare Direktbuttons statt verschachtelter Aktionsmenues.
- Neuer Split-Flow fuer Club-Admins: `dp_split_dienst` mit UI-Button und serverseitiger Absicherung.

### 🐛 Bugfixes

- Mitarbeiter-Dienste-Modal blieb bei "Lade Dienste..." haengen: Funktionskonflikt bereinigt und Aufrufpfad stabilisiert.
- Reload-Schleife in Admin-JS behoben (`dpSafeReload` + Seiten-Gating in Dienste-Skripten).
- Neuanlage im Besetzungs-Modal: neuer Mitarbeiter wird sofort im Scope sichtbar und vorausgewaehlt.
- Entfernen einer Vereinszugehoerigkeit im Mitarbeiter-Modal korrigiert:
  - automatische Vereinspflege aus Slots beruecksichtigt nur noch aktuelle/kommende Einsaetze.

### 🔒 Sicherheit & Konsistenz

- Zentrale E-Mail-Duplikatspruefung bei Mitarbeiter-Anlage und -Update eingefuehrt.
- Frontend-/Backend-Zuweisungsflows wurden serverseitig strenger auf Rollen- und Scope-Regeln geprueft.

---

## [0.9.5.31] - 2026-03-13 👤 Vereins-Admin: nur Selbst-Eintragung

### ✨ Verbesserungen

- Bei `Dienst zuweisen` kann ein eingeschraenkter Vereins-Admin nur noch den eigenen Mitarbeiterdatensatz auswaehlen.
- Serverseitige Absicherung in `ajax_admin_assign_slot`: fremde `mitarbeiter_id` werden fuer Vereins-Admins abgewiesen.
- Ermittlung des eigenen Mitarbeiterdatensatzes ueber `mitarbeiter.user_id`, mit E-Mail-Fallback.

---

## [0.9.5.30] - 2026-03-13 🔗 Rollenbasierte Link-Sichtbarkeit

### ✨ Verbesserungen

- Navigations-Links im einheitlichen Seitenkopf werden jetzt pro Rolle/Berechtigung gefiltert.
- Benutzer sehen nur noch Links zu Seiten, fuer die sie die passende Capability haben.
- Umsetzung zentral in `admin/views/partials/page-header.php`, damit alle Verwaltungsseiten konsistent sind.

---

## [0.9.5.29] - 2026-03-13 🧹 Dashboard/Modal Fehler bereinigt

### 🐛 Bugfixes

- PHP-Warnung behoben: `Undefined variable $veranstaltung_id` und `Undefined variable $verein_id` in `display_dashboard()`.
- Ursache war ein fehlplatzierter Berechtigungsblock im Dashboard-Renderpfad.
- Mitarbeiter-Dienste-Modal behoben: versehentlich als Klartext ausgegebener JavaScript-Block aus der Partial entfernt.
- Das Modal nutzt nun sauber nur noch `assets/js/dp-mitarbeiter-dienste-modal.js`.

---

## [0.9.5.28] - 2026-03-13 🧭 Vereins-Admin Scope reaktiviert

### ✨ Verbesserungen

- Vereins-Admin kann Mitarbeiter des eigenen Vereins bearbeiten.
- Vereins-Admin sieht nur Veranstaltungen, an denen sein Verein beteiligt ist.
- Scope-Ermittlung fuer Vereins-Admin wieder aktiv:
  - direkte Zuordnung aus `dp_user_vereine`
  - Fallback ueber `verein_verantwortliche`
  - Fallback ueber passende `kontakt_email`
- Haupt-Admin (`dpv2_manage_settings`) bleibt uneingeschraenkt.

---

## [0.9.5.27] - 2026-03-13 🧰 Veranstaltungs-Admin: Bereiche und Dienste

### ✨ Verbesserungen

- Veranstaltungs-Admin kann jetzt auch Arbeitsbereiche und Taetigkeiten verwalten.
- Entsprechende AJAX-Handler wurden von nur `manage_options` auf `can_manage_events` erweitert:
  - Bereich: laden, speichern, loeschen
  - Taetigkeit: laden, speichern, loeschen, Status umschalten
  - Bulk-Aktionen fuer Taetigkeiten
- Dienste fuer Veranstaltungen bleiben wie gewuenscht ueber `can_manage_events` verwaltbar.

---

## [0.9.5.26] - 2026-03-13 🎛️ Veranstaltungs-Admin Rechte erweitert

### ✨ Verbesserungen

- Veranstaltungs-Admins koennen wieder capability-basiert arbeiten (`dpv2_manage_events`).
- Veranstaltungs-Admins duerfen jetzt auch:
  - Mitarbeiter verwalten (ueber Events-Recht)
  - Veranstaltungsseiten anlegen und aktualisieren (`ajax_create_event_page`, `ajax_update_event_page`)
- `user_can()` prueft wieder korrekt auf `manage_options` ODER die jeweilige Plugin-Capability.

---

## [0.9.5.25] - 2026-03-13 🩹 Fatal bei Rollenmigration behoben

### 🐛 Bugfixes

- **Fatal Error behoben:** `Call to undefined function get_user_by()` waehrend der Rollenmigration beim fruehen Plugin-Load.
- Ursache: Legacy-Rollenmigration lief teilweise vor vollstaendigem WordPress-Bootstrap.
- Fix:
  - Migration wird nur ausgefuehrt, wenn `get_users()` und `get_user_by()` verfuegbar sind.
  - Falls zu frueh geladen, wird ein Pending-Flag gesetzt und die Migration automatisch auf `init` nachgeholt.

---

## [0.9.5.24] - 2026-03-13 👥 Rollenbegriffe vereinheitlicht

### ✨ Verbesserungen

- Rolle `Event Admin` ist jetzt konsequent als **Veranstaltungs-Admin** benannt.
- Rolle **Allgemeiner Admin** wurde in der Oberfläche auf **Haupt-Admin** umbenannt.
- In der Benutzerverwaltung ist die Zielstruktur jetzt klar als 4 Rollen sichtbar:
  - Haupt-Admin
  - Veranstaltungs-Admin
  - Vereins-Admin
  - Crew
- Die Rollenzuweisung in der Benutzerverwaltung enthält jetzt auch **Crew** als auswählbare Option.

---

## [0.9.5.23] - 2026-03-13 🏷️ Neue Rollen-/Capability-Namen (v2)

### ♻️ Änderungen

- **Neue Slugs eingeführt**, damit keine Kollisionen mit Altlasten auftreten:
  - Rollen: `dpv2_general_admin`, `dpv2_event_admin`, `dpv2_club_admin`, `dpv2_crew`
  - Capabilities: `dpv2_manage_settings`, `dpv2_manage_users`, `dpv2_manage_events`, `dpv2_manage_clubs`, `dpv2_view_reports`, `dpv2_send_notifications`
- Legacy-Rollen/-Caps werden bei der Rolleninstallation aktiv bereinigt.
- Benutzer mit Legacy-Rollen werden automatisch auf die neuen v2-Rollen migriert.

---

## [0.9.5.22] - 2026-03-13 ♻️ Rollensystem Hard-Reset (Baseline)

### ⚠️ Breaking Changes

- **Rollensystem zurückgesetzt:** Die bisherige, inkonsistente Scope- und Rollenlogik wurde vorerst deaktiviert.
- **Berechtigungen aktuell nur noch über WordPress-Admin (`manage_options`).**
- Damit gibt es bis zum Neuaufbau keine teilweisen Plugin-Admin-Berechtigungen mehr.

### 🧩 Technische Änderungen

- In `Dienstplan_Roles::user_can()` wird vorerst nur `manage_options` ausgewertet.
- Vereins-Scopes in `Dienstplan_Admin` sind deaktiviert (`is_restricted_club_admin()` liefert immer `false`).
- Uneingeschränkter Zugriff wird nur noch für WP-Admins angenommen.

---

## [0.9.5.21] - 2026-03-13 📁 Korrekter Plugin-Ordnername nach Update

### 🐛 Bugfixes

- **Falscher Verzeichnisname nach Installation:** GitHub-Auto-ZIPs benennen den Ordner nach dem Schema `{repo}-{tag}-{hash}` (z. B. `dienstplan-verwaltung-v0.9.5.18-WBZKhI`). WordPress deaktiviert das Plugin danach, weil der gespeicherte Pfad nicht mehr existiert.
- **Fix:** Neuer `upgrader_post_install`-Hook benennt den extrahierten Ordner nach der Installation automatisch auf den korrekten Slug `dienstplan-verwaltung` um — unabhängig davon, welchen Namen die ZIP-Datei enthält.
- Gilt für Updates über den WordPress-Updater (GitHub Release-Asset und zipball-Fallback).

---

## [0.9.5.20] - 2026-03-13 🛡️ Admin-Vollzugriff

### 🐛 Bugfixes

- **Admin-Zugriff vereinheitlicht:** Benutzer mit `dp_manage_settings` gelten jetzt systemweit als uneingeschränkte Admins.
- Dadurch greifen keine Vereins-Scopes mehr für Admins; alle Vereine, Veranstaltungen, Dienste und Mitarbeiter sind vollständig sichtbar/bearbeitbar.

---

## [0.9.5.19] - 2026-03-13 🔄 Update-Reaktivierung

### 🐛 Bugfixes

- **Plugin bleibt nach Update nicht aktiv:** Der Updater merkt sich jetzt den Aktivstatus vor dem Update und stellt ihn danach automatisch wieder her.
- Funktioniert für normale Aktivierung und Netzwerk-Aktivierung in Multisite.
- Zusätzliche Fehlerprotokollierung für fehlgeschlagene Reaktivierung ergänzt.

---

## [0.9.5.18] - 2026-03-13 🧭 Manuelle Vereinszuordnung im Mitarbeiter-Modal

### ✨ Verbesserungen

- **Mitarbeiter-Modal erweitert:** Vereine können jetzt manuell per Mehrfachauswahl zugeordnet oder entfernt werden.
- **Speichern mit Rechtebeachtung:** Für eingeschränkte Vereinsadmins werden nur erlaubte Vereine gespeichert.
- **Automatik bleibt erhalten:** Slot-basierte Vereinszuordnungen bleiben beim manuellen Speichern erhalten und werden nicht überschrieben.
- **Modal-Einbindung repariert:** Mitarbeiter-Modal und Dienste-Modal werden auf der Mitarbeiterseite wieder explizit eingebunden.

---

## [0.9.5.17] - 2026-03-13 👥 Mitarbeiter-Vereinszuordnung

### ✨ Verbesserungen

- **Mitarbeiter jetzt dauerhaft mehreren Vereinen zuordenbar:** Neue Zuordnungstabelle `dp_mitarbeiter_vereine` speichert Vereinsbezüge unabhängig von einzelnen Slots.
- **Automatische Pflege bei Buchungen:** Backend-Zuweisungen sowie öffentliche Dienst- und Vereinsanmeldungen tragen den Mitarbeiter jetzt automatisch dem betroffenen Verein zu.
- **Mitarbeiterübersicht zeigt Vereine sauber an:** Die Übersicht liefert jetzt `verein_ids` und `verein_namen` direkt aus der Datenbank und gruppiert Mitarbeiter zuverlässig nach Verein.
- **Scoped Zugriff robuster:** Vereinsadmins erhalten Mitarbeiterzugriff jetzt auch über die neue Vereinszuordnung, nicht nur über bestehende Dienste.

---

## [0.9.5.16] - 2026-03-13 🧩 Rechte-Refactor Vereinsadmin

### ✨ Verbesserungen

- **Zentrale Scope-Logik im Admin:** Verein-, Veranstaltungs-, Dienst- und Mitarbeiterzugriff
  werden nun über gemeinsame Helper ermittelt.
- **Neue Übersicht/Seitenlogik:** Die Seiten Veranstaltungen, Dienste, Mitarbeiter und Übersicht
  laden für eingeschränkte Vereinsadmins nur noch scoped Daten.
- **DB-Layer erweitert:** `get_dienste()` und `get_mitarbeiter_with_stats()` unterstützen jetzt
  erlaubte Vereins-IDs direkt im Query.
- **AJAX konsistenter abgesichert:** CRUD- und Hilfsaktionen für Veranstaltungen, Dienste,
  Mitarbeiter und Vereinsseiten prüfen jetzt den konkreten Datenzugriff statt nur eine Capability.
- **Vereins-/Veranstaltungsseiten-Erstellung scoped:** Kein globales Anfassen fremder Vereine mehr.

---

## [0.9.5.15] - 2026-03-13 🔒 Vereinsfilter-Hotfix

### 🐛 Bugfixes

- **Vereinsadmin sah weiterhin alle Vereine:** Filterlogik serverseitig verschärft.
- Einschränkung greift nun über effektive Berechtigung (nicht nur Rollenname).
- Fallback-Zuordnungen ergänzt:
  - `dp_user_vereine`
  - `dp_verein_verantwortliche`
  - `vereine.kontakt_email = user_email`
- Dadurch werden für eingeschränkte Vereinsadmins nur die eigenen Vereine angezeigt/bearbeitet.

---

## [0.9.5.14] - 2026-03-12 🔐 Rechte-Fix Vereinsadmin

### 🐛 Bugfixes

- **Vereinsadmin Zugriff repariert:** `dp_club_admin` erhält nun auch `dp_manage_events`,
  damit die Seiten Veranstaltungen, Dienste, Mitarbeiter und Übersicht erreichbar sind.
- **Rollen-Update robust gemacht:** Bestehende Rollen werden nun aktiv synchronisiert,
  da `add_role()` vorhandene Rollen nicht aktualisiert.
- **Vereinszugriff eingeschränkt:** Für reine Vereinsadmins wird serverseitig erzwungen,
  dass in der Vereinsverwaltung nur zugewiesene Vereine sichtbar und bearbeitbar sind.
- **AJAX abgesichert:** `ajax_get_verein`, `ajax_save_verein`, `ajax_delete_verein`
  prüfen jetzt ebenfalls die Vereinszuordnung.

---

## [0.9.5.13] - 2026-03-12 🧱 Overflow-Hotfix Vereinsseite

### 🐛 Bugfixes

- **Horizontales Überlaufen am rechten Rand behoben.**
- Viewport-Zentrierung der Vereinsseite jetzt mit festen Sicherheitsabständen,
  damit der Inhalt nicht mehr über die Seitenkante hinausragt.
- Mobile Breakpoint ebenfalls auf overflow-sichere Breite umgestellt.

---

## [0.9.5.12] - 2026-03-12 🧭 Layout-Korrektur Vereinsseite

### 🐛 Bugfixes

- **Verschobenes Layout nach Breiten-Fix behoben:** Der Wrapper nutzt jetzt eine saubere
  Full-Bleed-Zentrierung über `margin-left/right: calc(50% - 50vw)` statt `left + transform`.
- Der eigentliche Seiteninhalt wird innerhalb des Full-Bleed-Wrappers wieder sauber auf max. 1600px zentriert.

---

## [0.9.5.11] - 2026-03-12 ↔️ Breiten-Hotfix Vereinsseite

### 🐛 Bugfixes

- **Vereins-/Veranstaltungsseite weiterhin zu schmal:** Der Wrapper der Vereinsseite
  bricht jetzt gezielt aus schmalen Theme-/Content-Containern aus.
- `.dp-frontend-container.dp-verein-specific` nutzt nun die Viewport-Breite bis max. 1600px
  und wird unabhängig vom Parent-Container sauber zentriert.

---

## [0.9.5.10] - 2026-03-12 🛠️ Dienste-Mail Hotfix

### 🐛 Bugfixes

- **Mitarbeiter -> Dienste-Mail senden:** SQL-Feldnamen korrigiert.
- `dp_veranstaltungen`: `ve.titel` -> `ve.name`
- `dp_veranstaltung_tage`: `vt.datum` -> `vt.tag_datum`
- Sortierung entsprechend auf `vt.tag_datum` umgestellt.

---

## [0.9.5.9] - 2026-03-12 🔄 Update-Seite Fix

### 🐛 Bugfixes

- **Manuelles Update im Plugin-Backend repariert:** Der Button "Update durchführen"
  bricht auf Servern ohne Git nicht mehr mit einem Hinweis ab.
- **Neues Verhalten auf Produktionsservern:** `perform_update()` nutzt nun direkt den
  WordPress-Upgrader (`Plugin_Upgrader`) und installiert verfügbare Updates automatisch.
- **Bessere Rückmeldungen:** Erfolg-/Fehlermeldungen der Update-Seite wurden robuster gemacht,
  auch wenn kein Konsolen-Output vorhanden ist.

---

## [0.9.5.8] - 2026-03-12 ✉️ Mitarbeiter-Mailfunktionen

### ✨ Neue Features

- **Mitarbeiter-Aktionen erweitert:** Im Aktionen-Dropdown stehen jetzt
  "Dienste-Mail senden" und (bei aktivem Portalzugang) "Zugangsdaten erneut senden" zur Verfügung.
- **Zugangsdaten erneut senden:** Für bestehende Portal-User kann ein neues Passwort generiert
  und per E-Mail versendet werden.
- **Dienste-Übersicht per E-Mail:** Admins können einem Mitarbeiter eine strukturierte Übersicht
  aller zugewiesenen Dienste (Veranstaltung, Datum, Uhrzeit, Tätigkeit/Bereich) zusenden.

---

## [0.9.5.7] - 2026-07-13 🔧 Updater-Bugfix

### 🐛 Bugfixes

#### WordPress-Updater: "Der Download ist fehlgeschlagen. Service Unavailable"
- **Problem:** WordPress nutzt intern `wp_safe_remote_get()` beim Download von Plugin-Paketen.
  Diese Funktion blockiert Weiterleitungen zu externen CDN-Domains wie `objects.githubusercontent.com`,
  über die GitHub Release-Assets ausgeliefert werden → HTTP 503 / Service Unavailable.
- **Fix 1 (Produktion):** Neuer `upgrader_pre_download`-Filter (`handle_github_download()`),
  der alle GitHub-URLs abfängt und mit `wp_remote_get()` (folgt CDN-Redirects) korrekt herunterlädt.
  Unterstützt optional einen GitHub-Token (`dienstplan_github_token` WP-Option) für private Repos.
- **Fix 2 (Entwicklung):** Fehlender AJAX-Handler `wp_ajax_dienstplan_download_update` ergänzt
  (`ajax_download_update()`). Erstellt via `git archive` ein ZIP-Archiv vom Remote-Branch und
  liefert es als Download aus.

---

## [0.9.5.6] - 2026-07-12 🎨 UI & Mail-Verbesserungen

### ✨ Neue Features

- **Breitere Inhaltscontainer:** `.dp-public-container`, `.dp-verein-specific` und `.dp-vereine-overview`
  auf max. 1600px erweitert (vorher 1100–1200px).
- **Vereine im Veranstaltungs-Header:** Wenn eine Veranstaltung verein-übergreifend geöffnet wird
  (`verein_id = 0`), werden alle am Dienst beteiligten Vereine als blaue Chips im Header angezeigt.
- **Link in Bestätigungsmail:** Die Anmeldungs-Bestätigungs-E-Mail enthält nun einen direkten
  Link zurück zur Veranstaltungsseite (`Zurück zur Veranstaltungsseite: <URL>`).

---

## [0.9.5.5] - 2026-03-11 🎨 Timeline & Filter Redesign


### ✨ Neue Features

#### 1️⃣ Verbesserte Timeline-View
**Optimierte visuelle Darstellung von Diensten:**
- 📊 Höhere Service-Bars (34px statt vorher klein)
- 🔤 Lesbarere Schriftgrößen und bessere Typografie
- 📑 Breitere Tabs zur Tag-Navigation
- 🎯 Bessere Zeit-Positionierung basierend auf Dienst-Zeiten
- 🎨 Farbcodierung: Rot (🔴 = frei), Grün (🟢 = besetzt)

#### 2️⃣ Intelligente Filter mit 4-stufiger Abhängigkeit
**Neue Filterlogik: Besetzung → Tag → Arbeitsbereich → Dienst**
- 🔗 Abhängige Filter-Optionen: Nur mögliche Optionen sind aktiviert
- 🚫 Unmögliche Kombinationen werden automatisch deaktiviert
- ♻️ "Filter zurücksetzen" stellt Standardzustand wieder her
- 💡 Intelligente Option-Verwaltung: Fallback auf "Alle" wenn aktuelle Option unmöglich

#### 3️⃣ Modern Icon-basierte View-Toggle
**Neue View-Steuerung mit Emoji-Icons im Header-Right:**
- 🗂️ = Kachel-Ansicht (Card Grid)
- 📋 = Kompakt-Ansicht (Compact List)
- 📊 = Timeline-Ansicht (Gantt-like Timeline)
- ✨ Gradient-Highlighting für aktive View
- 🎨 Weißer Hintergrund mit Shadow-Effekt

#### 4️⃣ Verbesserte Benutzerfreundlichkeit
**Redesign der Frontend-Interaktion:**
- 📍 "Dienst absagen"-Button jetzt direkt in Timeline-Bar integriert
- 🎨 Kontrast-Verbesserungen bei Filter-Reset-Button (blaue Border, dunkle Text)
- 📊 Bessere Besetzungs-Badges mit farblichem Status
- 🧹 Vereinfachte UI-Struktur ohne redundante Elemente

### 🔧 Technical Changes

**`public/templates/veranstaltung-verein.php` (Hauptdatei, ~2770 Zeilen):**

1. **Filter-Architektur (Zeilen 1074–1365):**
   - `dpUpdateFilterOptionVisibility()`: Neue Funktion für abhängige Filter-Option Verwaltung
   - `dpItemMatchesFilterSet()`: Erweiterte Matching-Logik mit Override-Support
   - `dpGetFilterSelect()`, `dpSetFilterSelectValue()`: Select-Element-Verwaltung
   - Alle Filter arbeiten via `<select>` Dropdown statt Button-Groups

2. **Timeline-Rendering (Zeilen 410–750):**
   - Day-Tabs mit Click-Handler für Tag-Filter Update
   - Zeit-Grid mit dynamischen Hour-Labels basierend auf min/max Service-Zeiten
   - Service-Bars mit CSS-Positionierung: `left: X%; width: Y%;`
   - Inline-Besetzungs-Badges mit `.is-open` (rot) / `.is-full` (grün) Styling

3. **View-Toggle (Zeilen 247–275):**
   - Repositioniert zu `dp-header-view-tools` im header-right
   - Emoji-Icons (🗂️ 📋 📊) statt Text-Labels
   - Active-State Gradient: `background: linear-gradient(135deg, #0ea5e9, #0284c7)`

4. **CSS-Updates (Zeilen 1650–2200):**
   - `.dp-timeline-track-bar`: Erhöht auf 34px, bessere Lesbarkeit
   - `.dp-track-occupancy`: Farben invertiert
     - `.is-open`: Rot (#fca5a5 bg, #fee2e2 text)
     - `.is-full`: Grün (#bbf7d0 bg, #dcfce7 text)
   - `.dp-filter-group select`: Neue Styling mit `min-width: 180px`, fokus-States
   - `.dp-filter-reset`: Blauer Border, dunkle Text, pill-shape (999px radius)

### 🎯 Problem gelöst
- ✅ Timeline-Ansicht war zu klein und schwer lesbar
- ✅ Filter-Abhängigkeiten wurden nicht beachtet (Chaos bei Kombinationen)
- ✅ View-Toggle war nicht prominent genug
- ✅ Farbsemantik war kontraintuitive (frei=grün, besetzt=rot - falsch herum)
- ✅ "Dienst absagen"-Action war schwer auffindbar

### 📊 Statistiken
- **Dateien geändert:** 42
- **Neue Zeilen:** 11,108
- **Gelöschte Zeilen:** 2,362
- **Commit:** 6d9a058
- **Release:** GitHub Release mit WordPress-Plugin ZIP (412,654 bytes)

---

## [0.6.1] - 2025-11-18 🔐 Rollen: Import/Export-Berechtigungen

### 🎯 Problem gelöst
Import/Export war nur für WordPress-Admins verfügbar. Dienstplan-Admins konnten ihre zugewiesenen Daten nicht exportieren oder importieren.

### ✨ Neue Features

#### 1️⃣ Granulare Import/Export-Berechtigungen
**Admin können jetzt je nach Rolle importieren und exportieren:**

**Vereins-Admins (`dp_club_admin`):**
- ✅ Vereine exportieren
- ✅ Vereine importieren
- ❌ Keine Veranstaltungen/Dienste

**Veranstaltungs-Admins (`dp_event_admin`):**
- ✅ Veranstaltungen exportieren
- ✅ Dienste exportieren
- ✅ Veranstaltungen importieren
- ✅ Dienste importieren
- ❌ Keine Vereine

**Allgemeine Admins (`dp_general_admin`):**
- ✅ Alles exportieren (ZIP)
- ✅ Alle Typen importieren

**WordPress-Admins (`manage_options`):**
- ✅ Volle Kontrolle (Fallback)

#### 2️⃣ Rollen-basierte UI
**Import/Export-Seite zeigt nur erlaubte Optionen:**

- 🔵 **Import-Dropdown:** Nur erlaubte Typen sichtbar
- 🔵 **Export-Buttons:** Nur erlaubte Buttons sichtbar
- 🔵 **Statistiken:** Nur relevante Zahlen angezeigt
- 💬 **Hinweistext:** "Sie sehen nur Optionen für Ihre zugewiesenen Berechtigungen"

**Beispiel UI für Vereins-Admin:**
```
Export-Optionen:
[Vereine exportieren]  ✅
(Hinweis: Sie sehen nur Optionen für Ihre zugewiesenen Berechtigungen)
```

**Beispiel UI für Veranstaltungs-Admin:**
```
Export-Optionen:
[Veranstaltungen exportieren]  ✅
[Dienste exportieren]          ✅
(Hinweis: Sie sehen nur Optionen für Ihre zugewiesenen Berechtigungen)
```

### 🔧 Changed

#### Backend-Änderungen in `admin/class-admin.php`:

**1. Menü-Berechtigung gesenkt (Zeile 145-155):**
```php
// VORHER: Nur Settings-Admins
add_submenu_page(..., Dienstplan_Roles::CAP_MANAGE_SETTINGS, ...);

// NACHHER: Basis-Check, granular in Funktionen
add_submenu_page(..., 'read', ...);  // Wird in Funktionen geprüft
```

**2. Export-Handler mit Typ-Check (Zeile 2165-2195):**
```php
public function handle_export() {
    $type = sanitize_text_field($_GET['type']);
    $can_export = false;
    
    switch ($type) {
        case 'vereine':
            $can_export = Dienstplan_Roles::can_manage_clubs() 
                       || current_user_can('manage_options');
            break;
        case 'veranstaltungen':
        case 'dienste':
            $can_export = Dienstplan_Roles::can_manage_events() 
                       || current_user_can('manage_options');
            break;
        default:
            $can_export = current_user_can('manage_options');
    }
    
    if (!$can_export) {
        wp_die('Keine Berechtigung für diesen Export-Typ');
    }
    // ... CSV-Export
}
```

**3. Import-Handler mit Typ-Check (Zeile 2275-2305):**
```php
public function ajax_import_csv() {
    $import_type = sanitize_text_field($_POST['import_type']);
    $can_import = false;
    
    switch ($import_type) {
        case 'vereine':
            $can_import = Dienstplan_Roles::can_manage_clubs() 
                       || current_user_can('manage_options');
            break;
        case 'veranstaltungen':
        case 'dienste':
            $can_import = Dienstplan_Roles::can_manage_events() 
                       || current_user_can('manage_options');
            break;
        default:
            $can_import = current_user_can('manage_options');
    }
    
    if (!$can_import) {
        wp_send_json_error(array(
            'message' => 'Keine Berechtigung für diesen Import-Typ'
        ));
        return;
    }
    // ... CSV-Import
}
```

#### Frontend-Änderungen in `admin/views/import-export.php`:

**1. Berechtigungsprüfung am Seitenanfang (Zeile 1-30):**
```php
$can_manage_clubs = Dienstplan_Roles::can_manage_clubs() 
                 || current_user_can('manage_options');
$can_manage_events = Dienstplan_Roles::can_manage_events() 
                  || current_user_can('manage_options');

if (!$can_manage_clubs && !$can_manage_events) {
    wp_die('Sie haben keine Berechtigung für Import/Export.');
}
```

**2. Import-Dropdown rollen-basiert (Zeile 70-85):**
```php
<select id="import_type" name="import_type" required>
    <option value="">-- Bitte wählen --</option>
    <?php if ($can_manage_clubs): ?>
    <option value="vereine">Vereine</option>
    <?php endif; ?>
    <?php if ($can_manage_events): ?>
    <option value="veranstaltungen">Veranstaltungen</option>
    <option value="dienste">Dienste</option>
    <?php endif; ?>
</select>
```

**3. Export-Buttons rollen-basiert (Zeile 277-306):**
```php
<?php if ($can_manage_clubs): ?>
<button onclick="exportData('vereine')">Vereine exportieren</button>
<?php endif; ?>

<?php if ($can_manage_events): ?>
<button onclick="exportData('veranstaltungen')">Veranstaltungen exportieren</button>
<button onclick="exportData('dienste')">Dienste exportieren</button>
<?php endif; ?>

<?php if ($can_manage_clubs && $can_manage_events): ?>
<button onclick="exportData('all')">Alles exportieren (ZIP)</button>
<?php endif; ?>
```

**4. Statistiken rollen-basiert (Zeile 260-276):**
```php
<?php if ($can_manage_clubs): ?>
<li>Vereine: <?php echo count($stats['vereine']); ?></li>
<?php endif; ?>

<?php if ($can_manage_events): ?>
<li>Veranstaltungen: <?php echo count($stats['veranstaltungen']); ?></li>
<li>Dienste: <?php echo count($stats['dienste']); ?></li>
<?php endif; ?>
```

### 🔒 Security

- ✅ **Typ-basierte Berechtigungsprüfung:** Switch-Statement prüft jeden Datentyp
- ✅ **Fallback auf WP-Admin:** Unbekannte Typen nur für WordPress-Admins
- ✅ **Granulare Checks:** Separate Prüfung für Export (GET) und Import (POST)
- ✅ **Konsistente Error-Messages:** "Keine Berechtigung für diesen Import/Export-Typ"
- ✅ **UI-Schutz:** User sehen nur erlaubte Optionen (kein "Access Denied")

### 📦 Dateien geändert
- `admin/class-admin.php`: +45 Zeilen (Menü, Export, Import)
- `admin/views/import-export.php`: +30 Zeilen (Berechtigungen, UI)

### 🧪 Testing

**Vereins-Admin sollte:**
- ✅ Import/Export-Seite sehen
- ✅ Nur "Vereine" in Import-Dropdown sehen
- ✅ Nur "Vereine exportieren" Button sehen
- ✅ Vereine exportieren können
- ✅ Vereine importieren können
- ❌ Keine Veranstaltungen/Dienste exportieren können
- ❌ "Alles exportieren (ZIP)" nicht sehen

**Veranstaltungs-Admin sollte:**
- ✅ Import/Export-Seite sehen
- ✅ "Veranstaltungen" und "Dienste" in Dropdown sehen
- ✅ "Veranstaltungen/Dienste exportieren" Buttons sehen
- ✅ Veranstaltungen/Dienste exportieren können
- ✅ Veranstaltungen/Dienste importieren können
- ❌ Keine Vereine exportieren können
- ❌ "Alles exportieren (ZIP)" nicht sehen

**Allgemeiner Admin sollte:**
- ✅ Alle Import-Optionen sehen
- ✅ Alle Export-Buttons sehen
- ✅ "Alles exportieren (ZIP)" sehen
- ✅ Alles exportieren/importieren können

---

## [0.6.0] - 2025-11-18 ✨ UX: Login-Redirect & Dashboard-Widget

### 🎯 Problem gelöst
Benutzer mit Dienstplan-Rollen landeten nach Login auf "Profil" statt auf dem Dashboard. Das war verwirrend und unnötig umständlich.

### ✨ Neue Features

#### 1️⃣ Smart Login-Redirect
**Nach dem Login landen User direkt an der richtigen Stelle:**

- 🔴 **WordPress-Admin:** → Dienstplan-Dashboard (`/admin.php?page=dienstplan`)
- 🟠 **Allgemeiner Admin:** → Dienstplan-Dashboard (`/admin.php?page=dienstplan`)
- 🔵 **Veranstaltungs-Admin:** → Veranstaltungen (`/admin.php?page=dienstplan-veranstaltungen`)
- 🟢 **Vereins-Admin:** → Vereine (`/admin.php?page=dienstplan-vereine`)

**Funktionsweise:**
```php
public function login_redirect($redirect_to, $request, $user) {
    // Prüft User-Rollen
    // Leitet basierend auf Rolle weiter
    // Fallback auf WordPress-Standard
}
```

**Vorteile:**
- ✅ Keine zusätzlichen Klicks nötig
- ✅ Intuitive Navigation
- ✅ Rollen-spezifischer Einstieg
- ✅ WordPress-Admins behalten Kontrolle

---

#### 2️⃣ WordPress-Dashboard-Widget
**Dienstplan-Statistiken direkt im WordPress-Dashboard:**

##### Was wird angezeigt?

**Für Vereins-Admins:**
- 🏳️ **Vereine:** Gesamt + Aktive
- 📊 Klickbar → Vereins-Verwaltung

**Für Veranstaltungs-Admins:**
- 📅 **Veranstaltungen:** Gesamt + Kommende
- 📋 **Dienste:** Gesamt + Offene
- 📊 Alle Klickbar → Jeweilige Verwaltung

**Für Allgemeine Admins & WP-Admins:**
- ✅ Alle Statistiken
- ✅ Alle Links

##### Design
- 🎨 **Grid-Layout:** Responsive, automatische Anpassung
- 🎯 **Farb-Codierung:** Grün (Vereine), Blau (Veranstaltungen), Rot (Dienste)
- 🔗 **Klickbare Karten:** Direkter Zugriff auf Verwaltung
- ✨ **Hover-Effekt:** Lift + Shadow
- 🔲 **Icon-System:** Dashicons für visuelle Orientierung

##### Screenshot (Beispiel)
```
╭───────────────────────────────────────╮
│ 📅 Dienstplan-Übersicht            │
├───────────────────────────────────────┤
│  🏳️ Vereine    📅 Veranstaltungen 📋 Dienste  │
│    12             8               45     │
│  10 aktiv     5 kommend      32 offen │
│                                       │
│  [📈 Zum Dienstplan-Dashboard]      │
╰───────────────────────────────────────╯
```

---

### 📝 Technische Details

#### Login-Redirect
**Hook:** `login_redirect` (WordPress Core)
```php
add_filter('login_redirect', array($this, 'login_redirect'), 10, 3);
```

**Priorität:**
1. WordPress-Admin → Dashboard
2. Event-Admin → Veranstaltungen
3. Club-Admin → Vereine
4. General-Admin → Dashboard
5. Fallback → WordPress-Standard

#### Dashboard-Widget
**Hook:** `wp_dashboard_setup`
```php
add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
```

**Sichtbarkeit:**
- Nur für Benutzer mit Dienstplan-Rechten
- Capability-Check: `can_manage_events()` oder `can_manage_clubs()`
- WordPress-Admins sehen immer alles

**Statistiken:**
- SQL-Queries direkt auf Datenbank
- Gecacht durch WordPress (Transients möglich für spätere Optimierung)
- Farben & Icons per Inline-CSS (kein Extra-Request)

---

### 💼 Betroffene Dateien

- `includes/class-dienstplan-verwaltung.php` - Hooks registriert
- `admin/class-admin.php` - `login_redirect()` + `add_dashboard_widget()` + `render_dashboard_widget()`

---

### ✅ Was funktioniert jetzt

#### Login-Redirect
- ✅ **Event-Admin loggt ein** → Landet auf Veranstaltungen
- ✅ **Club-Admin loggt ein** → Landet auf Vereinen
- ✅ **General-Admin loggt ein** → Landet auf Dashboard
- ✅ **WordPress-Admin** → Behält volle Kontrolle
- ✅ **Standard-User** → Normales WordPress-Verhalten

#### Dashboard-Widget
- ✅ **Zeigt relevante Statistiken** basierend auf Rolle
- ✅ **Klickbare Karten** für schnellen Zugriff
- ✅ **Responsive Grid** passt sich an Bildschirmgröße an
- ✅ **Hover-Effekte** für bessere UX
- ✅ **Farb-Codierung** für visuelle Orientierung

---

### 💡 Warum diese Features?

**Problem:** Benutzer waren verwirrt nach dem Login
- ❌ Müssten manuell zum Dienstplan navigieren
- ❌ Landeten auf WordPress-Profil (irrelevant für ihre Aufgabe)
- ❌ Keine Übersicht über wichtige Zahlen

**Lösung:** Intelligenter Einstieg
- ✅ Direkt zur relevanten Ansicht
- ✅ Statistiken auf einen Blick
- ✅ Ein Klick zum Dienstplan-Dashboard

**Ergebnis:**
- ⏱️ **Zeit gespart:** 2-3 Klicks pro Login
- 🧠 **Weniger Verwirrung:** Klarer Einstiegspunkt
- 📈 **Bessere Übersicht:** Zahlen immer präsent

---

### 🔮 Nächste Schritte

**Mögliche Erweiterungen:**
- [ ] Dashboard-Widget: Caching für Performance
- [ ] Dashboard-Widget: Konfigurierbare Anzeige
- [ ] Login-Redirect: User-spezifische Präferenzen speichern
- [ ] Willkommens-Banner beim ersten Login

---

## [0.5.11] - 2025-11-17 ✅ Feature: Neuer Kontakt in Veranstaltungen

### 🎯 Problem gelöst
Button "Neuer Mitarbeiter" im Veranstaltungs-Modal sollte eigentlich **WordPress-Benutzer** anlegen, nicht einfache Mitarbeiter-Kontakte.

### ✨ Was ist neu?

#### Button umbenannt & korrigiert
- ❌ **Vorher:** "Neuer Mitarbeiter" → öffnete falsches Modal
- ✅ **Jetzt:** "Neuer Kontakt" → öffnet WordPress-User-Modal
- ✅ **Funktion:** Legt WordPress-Benutzer mit Rollen-Konzept an
- ✅ **E-Mail:** Automatische Einladung mit Passwort-Link

#### "Neuer Kontakt" Modal in Veranstaltungen
- ✅ Modal wird jetzt in `veranstaltungen.php` eingebunden
- ✅ Formular: Name, E-Mail, Rolle (Vereins-Admin, Veranstaltungs-Admin, etc.)
- ✅ Validierung: E-Mail-Prüfung, Duplikat-Check
- ✅ Automatischer Versand: Einladungs-E-Mail mit Passwort-Link

#### Smart Reload nach Speichern
```javascript
// Neu: Unterscheidung zwischen Dropdown und Checkboxen
if (source === 'veranstaltung-checkboxes') {
    reloadVerantwortlicheCheckboxes(userData.user_id); // Neu
} else {
    // Dropdown: User hinzufügen
}
```

- ✅ **Veranstaltungs-Modal:** Checkboxen werden neu geladen
- ✅ **Neuer User:** Automatisch ausgewählt nach Anlegen
- ✅ **Vereine-Modal:** Dropdown wird wie bisher aktualisiert

### 📝 Betroffene Dateien

- `admin/views/veranstaltungen.php` - "Neuer Kontakt" Modal eingebunden
- `admin/views/partials/veranstaltungen-modal.php` - Button korrigiert
- `assets/js/dp-veranstaltungen-modal.js` - `reloadVerantwortlicheCheckboxes()` Funktion
- `assets/js/dp-vereine-modal.js` - `saveNewContact()` erweitert für Checkboxen

### ✅ Was funktioniert jetzt

- ✅ **Button öffnet korrektes Modal** (WordPress-User statt Mitarbeiter)
- ✅ **WordPress-Benutzer werden angelegt** mit optionaler Rolle
- ✅ **Einladungs-E-Mail** wird automatisch versendet
- ✅ **Verantwortlichen-Liste** wird nach Speichern neu geladen
- ✅ **Neuer User** ist automatisch ausgewählt
- ✅ **Funktioniert in beiden Modals** (Vereine & Veranstaltungen)

### 💡 Hintergrund

**Verantwortliche in Veranstaltungen** sind WordPress-Benutzer mit Zugriffsrechten, keine einfachen Kontakte. Sie benötigen:
- ✅ WordPress-Login
- ✅ Dienstplan-Rollen (Vereins-Admin, Event-Admin, etc.)
- ✅ E-Mail-Benachrichtigungen

Daher wurde der Button von "Neuer Mitarbeiter" auf "Neuer Kontakt" umbenannt und öffnet jetzt das korrekte Modal.

---

## [0.5.10] - 2025-11-17 🔧 Bugfix: Mitarbeiter Modal

### Problem behoben
Mitarbeiter-Modal öffnete nicht korrekt - Fehler: `Cannot read properties of undefined (reading 'reset')`

### 🔧 Fixes

#### Inline-JavaScript entfernt
- ❌ **Alter Code:** Inline-JavaScript in `mitarbeiter-modal.php` überschrieb externe JS-Datei
- ✅ **Neu:** Nur noch externe Datei `dp-mitarbeiter-modal.js` wird verwendet
- ✅ **Vorteil:** Keine Konflikte mehr zwischen inline und extern

#### Error-Handling verstärkt
```javascript
// VORHER: Fehler wenn Form nicht existiert
$('#mitarbeiter-form')[0].reset();

// NACHHER: Sicherer Check
const form = document.getElementById('mitarbeiter-form');
if (form) {
    form.reset();
} else {
    console.warn('Form nicht gefunden');
}
```

#### Modal-Öffnung robuster
- ✅ Mehrere Display-Methoden (DOM + jQuery)
- ✅ Explizite Styles: `display: flex`, `visibility: visible`, `opacity: 1`
- ✅ Debug-Logging für Fehlersuche

### 📝 Betroffene Dateien
- `admin/views/partials/mitarbeiter-modal.php` - Inline-Code entfernt
- `assets/js/dp-mitarbeiter-modal.js` - Error-Handling + robuste Modal-Öffnung

### ✅ Was funktioniert jetzt
- ✅ Button "Öffnet Mitarbeiter-Modal ohne Fehler
- ✅ Form wird korrekt zurückgesetzt
- ✅ Modal ist sichtbar mit korrekten Styles
- ✅ Funktioniert aus Veranstaltungs-Modal heraus

---

## [0.5.9] - 2025-11-17 🎉 Stable Base - Basis-Funktionalität komplett

**Konsolidierung aller Features und Bugfixes** aus 0.9.x als stabile Basis-Version.

### ✅ Kern-Funktionalität

#### CRUD-Verwaltung
- ✅ **Veranstaltungen:** Erstellen, Bearbeiten, Löschen (Eintägig & Mehrtägig)
- ✅ **Dienste:** Zeitfenster, Besetzung, Slots-System
- ✅ **Mitarbeiter:** Kontaktverwaltung, Dienst-Zuweisung
- ✅ **Vereine:** Logo-Upload, Verantwortliche, WordPress-User-Integration
- ✅ **Bereiche & Tätigkeiten:** Kategorisierung mit Farben

#### Frontend
- ✅ **Veranstaltungs-Listen:** Card-Grid-Layout (Xoyondo-inspiriert)
- ✅ **Veranstaltungs-Detail:** Tage-Ansicht mit Dienst-Slots
- ✅ **Vereins-Auswahl:** Modal mit Statistiken
- ✅ **Dienst-Eintragung:** Selbst-Registrierung für Mitglieder
- ✅ **Responsive Design:** Mobile-optimiert

#### Shortcodes
- `[dienstplan]` - Veranstaltungs-Detail mit Diensten
- `[veranstaltungen_liste]` - Grid-Ansicht aller Veranstaltungen

### 🔧 Bugfixes (konsolidiert aus 0.9.5.x)

#### 0.9.5.0: Frontend & Vereins-Auswahl
- **CSS robuster:** !important-Regeln gegen Theme-Konflikte
- **Modal z-index:** 9.999.999 für bessere Sichtbarkeit
- **3-stufiger Fallback:** Vereins-Auswahl auch ohne Dienste
- **Box-sizing Fixes:** Container-Layout-Probleme behoben

#### 0.9.5.1: Veranstaltungen - Neuer Mitarbeiter
- **Button "Neuer Mitarbeiter":** Öffnet Mitarbeiter-Modal statt Kontakt-Modal
- **Intelligentes Reload:** Nur Verantwortlichen-Liste wird neu geladen
- **Veranstaltungs-Modal:** Bleibt offen beim Hinzufügen

#### 0.9.5.2: Vereins-Fallback
- **3-stufige Prüfung:** Explizit zugewiesen → Mit Diensten → Alle aktiven
- **Fix:** "Keine Vereine verfügbar" tritt nicht mehr auf

#### 0.9.5.3: Elementor-Basis-Kompatibilität
- **CSS-Overrides:** Elementor-spezifische Selektoren
- **Container-Fixes:** Width/Padding für Elementor-Sections
- **Z-Index:** Modal über Elementor-Popups
- **Grid-Overrides:** Verhindert Flexbox-Konflikte

#### 0.9.5.4: Vereine - Neuer Kontakt Modal
- **Button vereinfacht:** Direkter Aufruf von openNewContactModal
- **JavaScript verstärkt:** Mehrere Display-Methoden
- **CSS-Fixes:** Spezifische Regeln für #new-contact-modal

### 📋 Technische Features

#### Safe Reload System
- **dpSafeReload():** 3 Sekunden Delay, Modal-Detection
- **38 location.reload()** durch sichere Version ersetzt
- **Pending Reload:** Automatischer Reload beim Modal-Schließen

#### Rollen-System
- **WordPress-Integration:** Custom Capabilities
- **3 Rollen:** Club Admin, Event Admin, General Admin
- **Berechtigungen:** Granulare Zugriffskontrolle

#### Update-Mechanismus
- **Git-basiert:** Für Entwicklung
- **WordPress-Standard:** Für Produktionsserver
- **Auto-Detection:** Prüft Git-Verfügbarkeit

### 🚧 Bekannte Limitierungen

#### Page-Builder-Integration
- ⚠️ **Nur Shortcode-basiert** (kein natives Elementor-Widget)
- ⚠️ **Kein Live-Editing** im Page-Builder
- ⚠️ **Eingeschränkte Styling-Optionen** über Elementor-Controls
- ✅ **CSS-Kompatibilität:** Elementor, Divi, Gutenberg funktionieren

#### Frontend-Einschränkungen
- ⚠️ Keine Timeline-Ansicht
- ⚠️ Keine Kalender-Integration
- ⚠️ Keine PDF-Export-Funktion
- ⚠️ Keine E-Mail-Benachrichtigungen an Mitarbeiter

### 🔮 Roadmap - Nächste Versionen

```
0.5.9  ✅ Stable Base (AKTUELL)
       └─ Basis-Features komplett
       └─ Shortcode-basiert  
       └─ CSS-Fixes für Page-Builder

0.6.0  🚧 Elementor Foundation (geplant)
       ├─ Native Elementor-Widgets
       ├─ Live-Editing im Editor
       └─ Visual Controls

0.7.0  📅 Advanced Features (geplant)
       ├─ Template-System
       ├─ Dynamic Tags
       └─ Theme Builder

0.8.0  🎨 Pro Features (optional)
       ├─ Popup-Integration
       └─ Loop-Grid

0.9.0  ⚡ Performance & Polish (geplant)
       ├─ Lazy Loading
       └─ Cache-Optimierung

1.0.0  🎉 Production Ready (Ziel)
```

### 📖 Dokumentation

- **ELEMENTOR_ROADMAP.md:** Detaillierte Elementor-Integration-Planung
- **VERSION_STRATEGY.md:** Versionierungs-Strategie & Empfehlungen
- **DATABASE_STRUCTURE.md:** Datenbank-Schema
- **ROLLEN-UEBERSICHT.md:** Berechtigungskonzept

### ⚠️ Wichtige Hinweise

#### Versionierungs-Änderung
**WICHTIG:** Diese Version springt von 0.9.5.4 zurück auf 0.5.9!

**Grund:** 
- Version 0.9.x suggerierte "fast fertig" (90% complete)
- Realität: Elementor-Integration fehlt komplett
- 0.5.9 = ehrlichere Einschätzung des Entwicklungsstands

**Keine Breaking Changes:**
- Alle Features aus 0.9.5.x sind enthalten
- Datenbank-Schema unverändert
- API-kompatibel

#### Migration von 0.9.5.x
Kein Action erforderlich - einfach aktualisieren. Alle Daten bleiben erhalten.

---

## Versions-Historie 0.9.5.x (konsolidiert)

### [0.9.5.4] - 2025-11-17 🔧 Fix: Neuer Kontakt Modal (Vereine)

**Bugfix:** "Neuer Kontakt" Button im Vereine-Modal öffnet jetzt korrekt das Modal.

### 🔧 Bugfixes

#### Vereine-Modal: Neuer Kontakt
- **Button vereinfacht:** Entfernt komplexe inline-Logik
- **Modal-Display:** CSS-Regeln für `display: flex` hinzugefügt
- **JavaScript verstärkt:** Mehrere Display-Methoden für Kompatibilität
- **Debug-Logging:** Console-Logs zur Fehlersuche hinzugefügt

#### CSS-Fixes
- `#new-contact-modal` spezifische Styles
- `!important` auf display/visibility/opacity
- Fallback-Regeln für alle `.dp-modal` Elemente

### 📋 Technisches

#### Modal-Öffnung
- Direkter DOM-Zugriff + jQuery Fallback
- Styles: `display: flex`, `visibility: visible`, `opacity: 1`
- Z-Index: 100.000 (Admin-Bereich)

#### Betroffene Dateien
- `admin/views/partials/vereine-modal.php`
- `assets/js/dp-vereine-modal.js`
- `assets/css/dp-admin.css`

---

## [0.9.5.3] - 2025-11-17 ⚡ Elementor-Kompatibilität

**Umfassende Elementor-Kompatibilität** für Frontend-Darstellung ohne Layout-Konflikte.

### ⚡ Neu

#### Elementor Page Builder Unterstützung
- **Elementor-spezifische CSS-Overrides:** Verhindert Layout-Konflikte
- **Höherer z-index:** Modals (9.999.999) über Elementor-Popups (10.000)
- **Container-Fixes:** Width/Padding-Anpassungen für Elementor-Sections
- **Grid-Overrides:** Verhindert Elementor-Flexbox-Konflikte mit unseren Grids
- **Typography Reset:** Schriften werden nicht von Elementor überschrieben
- **Button-Styles:** Elementor-Button-Styles werden isoliert

### 🔧 Verbesserungen

#### CSS-Robustheit
- **!important auf kritischen Styles:** Grid, Display, Width, Z-Index
- **Box-sizing Override:** Auch für ::before und ::after Pseudo-Elemente
- **Background-Fixes:** Karten behalten weißen Hintergrund
- **Spacing-Isolation:** Elementor-Spacing beeinflusst Plugin nicht mehr

#### Editor-Modus
- **Elementor Editor:** Z-Index noch höher (99.999.999) im Editor-Modus
- **Live-Preview:** Funktioniert korrekt in Elementor-Vorschau

### 📋 Technisches

#### CSS-Selektoren
- `.elementor .dp-public-container` - Container in Elementor
- `.elementor-section .dp-events-grid` - Grid-Overrides
- `.elementor-popup-modal` - Z-Index niedriger als unsere Modals
- `.elementor-editor-active .dp-modal` - Extra-hoher Z-Index im Editor

#### Kompatibilität
- ✅ Elementor Free
- ✅ Elementor Pro
- ✅ Elementor Flexbox Container
- ✅ Elementor Grid Container
- ✅ Elementor Popups

---

## [0.9.5.2] - 2025-11-17 🔧 Vereins-Auswahl Fallback

**Bugfix:** "Keine Vereine verfügbar" wird nicht mehr angezeigt, auch wenn keine Dienste angelegt sind.

### 🔧 Bugfixes

#### Vereins-Auswahl im Frontend
- **3-stufiger Fallback:**
  1. Explizit zugewiesene Vereine (`veranstaltung_vereine` Tabelle)
  2. Vereine mit Diensten in der Veranstaltung
  3. **NEU:** Alle aktiven Vereine (wenn keine Dienste angelegt)
- **Fix:** "Keine Vereine verfügbar" tritt nicht mehr auf
- **Bessere UX:** Benutzer können sich auch ohne angelegte Dienste für einen Verein eintragen

### 📋 Technisches

#### SQL-Abfrage
- Fallback auf alle aktiven Vereine wenn keine Dienste vorhanden
- Sortierung nach Vereinsname (alphabetisch)

---

## [0.9.5.1] - 2025-11-17 🔧 Neuer Kontakt bei Veranstaltungen

**Bugfix:** "Neuer Kontakt" Button bei Veranstaltungen funktioniert jetzt korrekt.

### 🔧 Bugfixes

#### Veranstaltungs-Verantwortliche
- **Button "Neuer Mitarbeiter":** Öffnet jetzt das Mitarbeiter-Modal statt des Kontakt-Modals
- **Intelligentes Reload:** Nach dem Erstellen eines Mitarbeiters wird nur die Verantwortlichen-Liste neu geladen, nicht die ganze Seite
- **Bessere UX:** Veranstaltungs-Modal bleibt offen beim Hinzufügen neuer Mitarbeiter
- **Neue Funktion:** `reloadVerantwortlicheList()` für gezieltes Neuladen der Liste

### 📋 Technisches

#### JavaScript-Änderungen
- `dp-veranstaltungen-modal.js`: Neue Funktion `reloadVerantwortlicheList()`
- `dp-mitarbeiter-modal.js`: Flag-basierte Erkennung ob Veranstaltungs-Modal offen
- Smart Reload: Nur Reload wenn nicht aus Veranstaltung heraus aufgerufen

---

## [0.9.5.0] - 2025-11-17 🔧 Bugfixes & 4-stellige Versionierung

**Wichtige Bugfixes** für Frontend-Display und Vereins-Auswahl.

### 🔧 Bugfixes

#### Frontend-Darstellung
- **CSS robuster gemacht:** !important-Regeln gegen Theme-Konflikte
- **Modal-Display:** Höherer z-index (999999) für bessere Sichtbarkeit
- **Container:** Box-sizing und Layout-Fixes für Theme-Kompatibilität
- **Modal-Visibility:** Explizite Regel für display: flex

#### Vereins-Auswahl
- **Fallback-Logik:** Wenn keine Vereine explizit zugewiesen sind, werden automatisch alle Vereine angezeigt, die Dienste in der Veranstaltung haben
- **Fix:** "Keine Vereine verfügbar" wird nicht mehr fälschlicherweise angezeigt

### 📋 Technisches

#### Versionierung
- **4-stellige Versionsnummern:** Umstellung auf MAJOR.MINOR.PATCH.BUILD Format
- **Semantic Versioning 2.0:** Konform mit WordPress Best Practices

---

## [0.9.5] - 2025-11-17 🔄 Reload beim Modal-Schließen

**Pending Reload System** - Seite aktualisiert sich automatisch beim Schließen von Modals nach Änderungen.

### ✨ Neu

#### 🔄 Automatischer Reload beim Modal-Schließen
- **Neues System:** Wenn Reload unterdrückt wurde (Modal offen), wird er beim Schließen ausgeführt
- **`dpCheckPendingReload()`** - Prüft und führt ausstehenden Reload aus
- **Flag:** `window.dpReloadPending` merkt sich unterdrückte Reloads
- **Integriert in alle Modal-Close-Funktionen:**
  - `closeDienstModal()`
  - `closeBesetzungModal()`
  - `closeMitarbeiterModal()`
  - `closeVeranstaltungModal()`
  - `closeVereinModal()`

### 🔧 Verbesserungen

#### User Experience
- **Automatische Aktualisierung:** Modal schließen → Seite lädt automatisch neu
- **Keine manuelle Aktualisierung mehr nötig**
- **Zeitsparend:** Änderungen sind sofort sichtbar nach Modal-Schließen
- **Konsistent:** Funktioniert für alle Modal-Typen

#### Verhalten
1. Änderung in Modal speichern → `dpSafeReload()` wird aufgerufen
2. Modal ist noch offen → Reload wird unterdrückt, `dpReloadPending = true`
3. User schließt Modal → `dpCheckPendingReload()` führt Reload aus
4. Seite zeigt aktuelle Daten

### 🐛 Bugfixes

#### Rekursiver Aufruf in dpSafeReload()
- **Problem:** `if(typeof dpSafeReload === "function") { dpSafeReload(); }` erzeugte Endlosschleife
- **Lösung:** Geändert zu `location.reload();`

### 📝 Technische Änderungen

#### dp-admin.js
```javascript
window.dpReloadPending = false;

window.dpSafeReload = function(delay) {
    // ... Modal-Checks ...
    if (!hasOpenModal) {
        location.reload();
    } else {
        window.dpReloadPending = true; // Merken!
    }
};

window.dpCheckPendingReload = function() {
    if (window.dpReloadPending) {
        window.dpReloadPending = false;
        location.reload();
    }
};
```

#### Modal-Close-Funktionen (5 Dateien)
- **dp-dienst-modal.js**
- **dp-besetzung-modal.js**
- **dp-mitarbeiter-modal.js**
- **dp-veranstaltungen-modal.js**
- **dp-vereine-modal.js**

Alle erweitert um:
```javascript
window.closeXxxModal = function() {
    $('#xxx-modal').hide();
    if(typeof dpCheckPendingReload === 'function') {
        dpCheckPendingReload();
    }
};
```

---

## [0.9.4] - 2025-11-17 🔧 Update-Mechanismus Fix

**Kritischer Fix** - Manuelle Updates auf Produktionsservern ohne Git funktionieren jetzt.

### 🐛 Bugfixes

#### Manuelles Update auf Produktionsservern
- **Problem:** `perform_update()` verlangte Git, auch auf Produktionsservern
- **Lösung:** Zeigt hilfreiche Meldung mit Anleitung für WordPress Plugin-Update
- **Meldung:** "Bitte nutzen Sie die WordPress Plugin-Verwaltung für Updates. Gehen Sie zu: Plugins → Installierte Plugins → Dienstplan Verwaltung → 'Jetzt aktualisieren'"

### 🔧 Verbesserungen

#### Update-Methoden
- `perform_update()` erkennt jetzt `$this->git_available`
- Entwicklungsumgebungen: Weiterhin Git-basiertes Update
- Produktionsserver: Verweis auf WordPress Standard-Update
- Verhindert irreführende "Git ist nicht verfügbar" Fehlermeldung

### 📝 Technische Änderungen

**class-updater.php:**
```php
public function perform_update() {
    if (!$this->git_available) {
        // Produktionsserver → WordPress Update nutzen
        return array(
            'success' => false, 
            'message' => 'Bitte nutzen Sie die WordPress Plugin-Verwaltung...'
        );
    }
    // Entwicklung → Git Pull
}
```

### 💡 Für Administratoren

**Update auf Produktionsservern:**
1. WordPress Admin → Plugins
2. "Dienstplan Verwaltung" finden
3. Auf "Jetzt aktualisieren" klicken
4. WordPress lädt automatisch von GitHub

**Update auf Entwicklungsservern:**
- Weiterhin über Admin → Updates → "Update durchführen" (Git Pull)
- Oder manuell: `git pull origin main`

---

## [0.9.3] - 2025-11-17 🎯 Smart Reload & UX-Verbesserungen

**Safe Page Reload** - Seiten-Reloads respektieren jetzt offene Modals und geben User Zeit zum Lesen.

### ✨ Neu

#### 🛡️ Safe Reload System
- **Zentrale `dpSafeReload()` Funktion** in `dp-admin.js`
- Prüft vor Reload auf offene Modals/Dialogs:
  - Inline-Style Modals (`.modal`, `.dialog`, `[role="dialog"]`)
  - jQuery UI Dialogs (`.ui-dialog:visible`)
  - Bootstrap Modals (`.modal.show`)
  - Custom Modal-Classes (`.dp-modal-open`)
- **Kein Reload mehr bei offenen Modals** - verhindert Datenverlust
- **Verzögertes Reload** - 3 Sekunden Standard für bessere Lesbarkeit von Erfolgsmeldungen

### 🔧 Verbesserungen

#### User Experience
- **38 Reload-Aufrufe optimiert** in 9 JavaScript-Dateien
- User hat Zeit, Erfolgsmeldungen zu lesen (3s statt sofort)
- Keine verlorenen Eingaben mehr in offenen Modals
- Konsistentes Reload-Verhalten auf allen Admin-Seiten
- Console-Log bei unterdrücktem Reload: "Reload unterdrückt: Modal ist geöffnet"

#### Betroffene Bereiche
- **Dienste-Verwaltung:** 6 Reloads → Safe Reload
- **Veranstaltungen:** 7 Reloads → Safe Reload
- **Bereiche & Tätigkeiten:** 8 Reloads → Safe Reload
- **Mitarbeiter:** 3 Reloads → Safe Reload
- **Vereine:** 2 Reloads → Safe Reload
- **Dienste-Tabelle:** 3 Reloads → Safe Reload
- **Admin-Modals:** 11 Reloads → Safe Reload

### 🐛 Bugfixes

#### Reload-Probleme
- Behebt: Seite lädt neu während Modal-Eingabe
- Behebt: Erfolgsmeldung verschwindet sofort (keine Lesezeit)
- Behebt: Form-Daten gehen verloren bei vorzeitigem Reload
- Behebt: Inkonsistentes Reload-Timing auf verschiedenen Seiten

### 📝 Technische Änderungen

#### JavaScript-Dateien (9)
- **dp-admin.js:** Neue `dpSafeReload()` Funktion mit Modal-Detection
- **dp-admin-modals.js:** 11x `location.reload()` → `dpSafeReload()`
- **dp-bereiche-taetigkeiten.js:** 8x ersetzt
- **dp-veranstaltungen-modal.js:** 7x ersetzt (inkl. Syntax-Fixes)
- **dp-dienst-modal.js:** 3x ersetzt
- **dp-dienste-table.js:** 3x ersetzt
- **dp-vereine-modal.js:** 2x ersetzt
- **dp-mitarbeiter-modal.js:** 2x ersetzt
- **dp-mitarbeiter.js:** 1x ersetzt

#### Fallback-Sicherheit
```javascript
if(typeof dpSafeReload === "function") { 
    dpSafeReload(delay); 
} else { 
    location.reload(); // Fallback
}
```

---

## [0.9.2] - 2025-11-17 🚀 Produktionsserver-Support

**GitHub API Fallback** - Plugin funktioniert jetzt auch auf Servern ohne Git-Installation.

### ✨ Neu

#### 🌐 Automatischer Update-Modus
- **Ohne Git (Produktion):** Nutzt GitHub Releases API für Updates
- **Mit Git (Entwicklung):** Weiterhin Git-basierte Updates
- Automatische Erkennung der Umgebung beim Plugin-Start
- Keine Git-Abhängigkeit mehr für normale WordPress-Installationen

### 🔧 Verbesserungen

#### Update-Verwaltung
- **Update-Seite zeigt aktiven Modus:** "Git (Entwicklung)" oder "GitHub API (Produktion)"
- Bessere Fehlermeldungen wenn Git nicht verfügbar
- Klare Information über Update-Quelle in der Admin-Oberfläche
- Keine störenden Git-Fehlermeldungen mehr auf Produktionsservern

#### GitHub API Integration
- Liest neueste Version aus GitHub Releases
- Lädt ZIP-Asset automatisch herunter
- Fallback auf Zipball-URL wenn kein Asset vorhanden
- Changelog aus Release-Notes

### 🐛 Bugfixes

#### Git-Status-Anzeige
- Behebt "Git ist nicht verfügbar" Warnung auf Produktionsservern
- Korrekte Anzeige des Update-Modus in Admin-Oberfläche
- Keine unnötigen Git-Befehle auf Servern ohne Git

### 📝 Technische Änderungen

#### class-updater.php
- Neue Methode: `get_update_info_from_github()` - Holt Updates von GitHub API
- Umbenannt: `get_update_info()` → `get_update_info_from_git()` (Git-spezifisch)
- `get_update_info()` wählt automatisch zwischen Git und GitHub API
- `$git_available` Flag wird beim Start gesetzt
- `get_git_status()` gibt jetzt `mode` zurück (Git/GitHub API)

#### create-release.ps1
- Liest Version jetzt dynamisch aus Plugin-Datei
- Kein manueller Parameter mehr nötig

---

## [0.9.1] - 2025-11-17 🎨 Frontend Timeline & Auto-Update

**Timeline-View Optimierung** - Services nebeneinander + Automatische Updates.

### ✨ Neu

#### 🎯 Auto-Update-Feature
- Checkbox in Update-Einstellungen: "Automatische Updates aktivieren"
- WordPress auto_update_plugin Filter integriert
- Plugin erscheint in Auto-Update-Spalte der Plugin-Liste
- Speichert Einstellung in: `dienstplan_auto_update_enabled`

#### 🎨 Frontend Timeline-View (KOMPLETT ÜBERARBEITET!)
- **Zeit-Slot-Gruppierung:** Services zur gleichen Zeit erscheinen nebeneinander
- **Grid-Layout:** CSS Grid mit fixierter linker Spalte (280px)
- **Scroll-Synchronisierung:** 
  - Horizontal: Header ↔ Grid
  - Vertikal: Left-Panel ↔ Grid
- **Linke Spalte:** Zeigt Zeit + Anzahl Dienste (z.B. "14:00 - 3 Dienste")
- **Responsive Design:** Mobile-optimiert mit reduzierten Breiten
- **286 Zeilen neues CSS** in dp-public.css

### 🔧 Verbesserungen

#### Git-Integration
- Automatische Git-Pfad-Erkennung für Windows
- Sucht in Standard-Pfaden: `C:\Program Files\Git\`
- Fallback auf System-PATH
- Keine manuelle Git-Konfiguration mehr nötig

#### Plugin-Basename
- Dynamisch via `plugin_basename(DIENSTPLAN_PLUGIN_FILE)`
- Funktioniert mit versionierten Ordnernamen (z.B. `dienstplan-verwaltung-0.9.1/`)
- Behebt Problem bei Updates über ZIP

### 🐛 Bugfixes

#### Timeline-View
- Services werden nicht mehr untereinander angezeigt
- Services zur gleichen Zeit erscheinen in einer Zeile
- `selected_verein` wird korrekt aus `available_vereine` geholt
- Dienste werden nach Verein-Auswahl angezeigt

#### Auto-Update-Spalte
- Plugin erscheint jetzt immer in Plugin-Liste (via `no_update[]`)
- Auto-Update-Spalte wird auch ohne verfügbares Update angezeigt

### 📦 WordPress-ZIP
- Forward Slashes (Unix-Style) statt Backslashes
- .NET System.IO.Compression API für präzise Pfad-Kontrolle
- Ordnername ohne Version: `dienstplan-verwaltung/`
- Dateiname ohne Version: `dienstplan-verwaltung.zip`
- Größe: 0.27 MB (89 Dateien)

---

## [0.9.0] - 2025-11-17 🚀 UAT Release

**User Acceptance Testing Release** - Bereit für produktive Tests mit echten Nutzern.

### ✨ Neu

#### 📚 Komplette Dokumentation
- **Backend-Bedienungsanleitung** (650+ Zeilen)
  - Schritt-für-Schritt-Anleitungen für alle Funktionen
  - Screenshots-Platzhalter für 16 Backend-Bereiche
  - FAQ und Problembehandlung
  - Tipps & Best Practices

- **Frontend-Bedienungsanleitung** (500+ Zeilen)
  - Anleitung für Crew-Mitglieder
  - Split-Dienste-Erklärung
  - Checkliste für ersten Dienst
  - Mobile-Nutzung-Tipps

- **Quick-Start Guide** (300+ Zeilen)
  - In 15 Minuten einsatzbereit
  - Typische Szenarien
  - Schnelle Fehlerbehebung

- **Screenshot-Anleitung** (SCREENSHOTS.md)
  - 25 definierte Screenshots (16 Backend + 9 Frontend)
  - Detaillierte Richtlinien
  - Tools-Empfehlungen

- **Dokumentations-Menüpunkt im Backend**
  - Zugriff über Dashboard → Administration → Dokumentation
  - Kategorisierte Sidebar (Einstieg / Anleitungen / Technisch)
  - Markdown-zu-HTML-Rendering
  - Download-Buttons für alle Dokumente

#### 🔧 Split-Dienst Slot-System (KOMPLETT ÜBERARBEITET!)
- **❌ Alt:** Split erstellt neue Dienste → ✅ Neu: Split passt Slots an
- **Slot-basierte Architektur:**
  - Dienst bleibt bestehen (keine Duplikate mehr!)
  - Slot 1 wird angepasst (erste Hälfte)
  - Slot 2 wird erstellt (zweite Hälfte)
  - Mitarbeiter wird gewähltem Slot zugewiesen

- **Neue Funktion:** `ensure_dienst_split()`
  - Prüft ob bereits gesplittet (2 Slots vorhanden)
  - Passt existierende Slots an
  - Berechnet automatisch Mitte-Zeit
  - Idempotent (kann mehrfach aufgerufen werden)

#### 🎯 Slot-Zuweisung mit Split-Support
- **Intelligente Slot-Auswahl:**
  - Bei Normal-Anmeldung: Ersten freien Slot finden
  - Bei Split-Anmeldung: Gewählten Slot (1 oder 2) finden
  - Fehlerbehandlung: "Erste Hälfte bereits besetzt"

- **Zwei-Tabellen-System:**
  - `dienst_slots`: Physische Plätze (mit von_zeit/bis_zeit)
  - `dienst_zuweisungen`: Anmeldungs-Historie

- **Rollback-Mechanismus:**
  - Bei Fehler: Slot wird automatisch wieder freigegeben
  - Atomare Transaktionen

#### 📧 Email optional bei Anmeldung
- **Temporäre Mitarbeiter-Accounts:**
  - Ohne Email: `temp_[timestamp]_[uniqueid]@dienstplan.local`
  - Ermöglicht spontane Anmeldungen
  - Keine Duplikat-Probleme

#### 🐛 Debugging & Logging
- **Erweiterte Fehlerberichte:**
  - `error_log('DP: Anmeldung für Dienst-ID: X')`
  - POST-Daten werden geloggt
  - Dienst-ID wird in Fehlermeldungen angezeigt

### 🔧 Verbessert

#### Datenbank-Konsistenz
- **Migrations-Script für mitarbeiter_id:**
  - Fügt fehlende Spalte zu `dienst_zuweisungen` hinzu
  - Kann manuell ausgeführt werden

- **Slot-Struktur erweitert:**
  - `mitarbeiter_id` Spalte in `dienst_slots`
  - `von_zeit`, `bis_zeit`, `bis_datum` für Split-Zeiten
  - `slot_nummer` (1, 2, 3, ...)

#### Admin-Oberfläche
- **Modal-Funktionen (dp-admin-modals.js):**
  - 1000+ Zeilen JavaScript
  - Alle CRUD-Operationen
  - Nested Modals für schnelles Erstellen
  - Bulk-Update Funktionen

- **Auto-Refresh optimiert:**
  - Intervall: 3 Sekunden (statt 1,5s)
  - Pausiert bei geöffneten Modals

#### Frontend-UX
- **Anmelde-Formular:**
  - Checkbox "Ich möchte den Dienst teilen"
  - Radio-Buttons für Zeitfenster-Auswahl
  - Validierung: Split-Auswahl erforderlich wenn Checkbox aktiv
  - Email optional
  - Telefon-Feld hinzugefügt

- **Fehlerbehandlung:**
  - Spezifische Fehlermeldungen
  - "Erste Hälfte bereits besetzt"
  - "Dienst nicht gefunden (ID: X)"

### 🐛 Behoben

#### ❌ Kritisch: Split-Dienst-Bug
- **Problem:** Split erstellt neue Dienste → Duplizierung
- **Lösung:** Slot-basiertes System → Keine Duplikate
- **Details:**
  ```php
  // ALT (FALSCH):
  $wpdb->insert($prefix . 'dienste', $teil1_data); // ❌
  $wpdb->insert($prefix . 'dienste', $teil2_data); // ❌
  $wpdb->delete($prefix . 'dienste', array('id' => $dienst->id)); // ❌
  
  // NEU (RICHTIG):
  $wpdb->update($prefix . 'dienst_slots', [...], array('id' => $slot1->id)); // ✅
  $wpdb->insert($prefix . 'dienst_slots', [...]);  // ✅
  ```

#### ❌ "Dienst nicht gefunden" Fehler
- **Problem:** Dienst-ID wurde nicht korrekt übergeben/gelesen
- **Lösung:**
  - Debug-Logging hinzugefügt
  - `dienst_id` wird aus `$_POST['dienst_id']` gelesen
  - Formular hat Hidden-Field `<input name="dienst_id" id="dpDienstId">`
  - JavaScript setzt Wert beim Modal-Öffnen

#### ❌ Slot-Zuweisung fehlte
- **Problem:** Mitarbeiter wird in `dienst_zuweisungen` eingetragen, aber Slot bleibt leer
- **Lösung:**
  ```php
  // Finde freien Slot
  $free_slot = $wpdb->get_row("SELECT * FROM {$prefix}dienst_slots 
                                WHERE dienst_id = %d 
                                AND mitarbeiter_id IS NULL");
  
  // Update Slot
  $wpdb->update($prefix . 'dienst_slots',
      array('mitarbeiter_id' => $mitarbeiter_id, 'status' => 'besetzt'),
      array('id' => $free_slot->id)
  );
  
  // Speichere Zuweisung
  $wpdb->insert($prefix . 'dienst_zuweisungen', [...]); 
  ```

#### Datenbank-Schema-Fehler (aus 0.4.7)
- Falsche Tabellennamen korrigiert
- Falsche Spaltennamen korrigiert
- Fehlende Spalten hinzugefügt
- Siehe DATABASE_STRUCTURE_AKTUELL.md für Details

### 🗑️ Entfernt
- Alte Split-Funktion `split_dienst()` (erstellt neue Dienste)
- Unnötige Duplikat-Prüfungen
- Veraltete Dokumentation von v0.1.x - v0.4.x

### 📋 Known Issues (für UAT)
- [ ] Screenshots fehlen noch (Platzhalter vorhanden)
- [ ] Email-Benachrichtigungen nicht getestet
- [ ] Split-Dienste: Anzeige im Backend prüfen
- [ ] Performance bei >100 Diensten testen
- [ ] Mobile-Ansicht Browser-Kompatibilität

### 🧪 Testfälle für UAT

#### Split-Dienst testen:
1. Dienst mit 8 Stunden erstellen (18:00 - 02:00)
2. Als Crew: "Anmelden" → Checkbox "Teilen" aktivieren
3. "1. Teil" wählen → Anmelden
4. Prüfen: 1. Slot besetzt, 2. Slot frei
5. Als zweiter User: "2. Teil" wählen → Anmelden
6. Prüfen: Beide Slots besetzt, Dienst zeigt "Voll"

#### Normale Anmeldung testen:
1. Dienst mit 3 Plätzen erstellen
2. 3 verschiedene User anmelden
3. Prüfen: Badge zeigt "3/3 belegt"
4. 4. User versucht anzumelden → Fehler "bereits voll"

#### Backend Modal-Funktionen testen:
1. Jeden "Bearbeiten"-Button klicken
2. Modal öffnet/schließt korrekt
3. Speichern funktioniert
4. Bulk-Updates testen

---

## Archivierte Versionen

Änderungen von Version 0.1.0 bis 0.4.7 wurden archiviert.  
Siehe: `documentation/archive/CHANGELOG_LEGACY.md`

---

**Legende:**
- ✨ Added - Neue Features
- 🔧 Changed - Änderungen an bestehenden Features
- 🐛 Fixed - Bugfixes
- 🗑️ Removed - Entfernte Features
- 🔒 Security - Sicherheitsupdates
- 📋 Known Issues - Bekannte Probleme
- 🧪 Testing - Test-Informationen

#### JavaScript-Fehler
- **dp-public.js:**
  - Doppelter Code mit "Illegal return statement" entfernt
  - Datei komplett neu erstellt ohne Duplikate
  - Saubere Struktur

- **Fehlende Modal-Funktionen:**
  - Alle CRUD-Funktionen für Vereine/Veranstaltungen/Dienste fehlten
  - Buttons riefen nicht-existierende Funktionen auf
  - Komplett in dp-admin-modals.js implementiert

### 🏗️ Technische Details

#### Neue Dateien
```
assets/js/
└── dp-admin-modals.js        [NEU] - 1000+ Zeilen alle Modal-Funktionen

root/
└── migrate-mitarbeiter-id.php [NEU] - Migrations-Script

documentation/
└── DATABASE_STRUCTURE_AKTUELL.md [NEU] - 550+ Zeilen vollständige DB-Doku
```

#### Geänderte Dateien
- `admin/class-admin.php` - dp-admin-modals.js registriert
- `public/class-public.php` - split_dienst() Methode + AJAX-Handler
- `public/class-dienstplan-public.php` - erstellt_am entfernt
- `includes/class-database.php` - mitarbeiter Tabelle ohne erstellt_am, Migration hinzugefügt
- `public/templates/veranstaltung-compact.php` - Split-Formular + Email optional
- `assets/js/dp-public.js` - Neu erstellt ohne Duplikate
- `documentation/DATABASE_STRUCTURE.md` - Als veraltet markiert

#### Script-Dependencies
```
dp-admin-scripts (base - dp-admin.js)
├── dp-admin-modals (NEU - depends on: jquery, dp-admin-scripts)
├── dp-dienst-modal (depends on: jquery, dp-admin-scripts)
├── dp-dienste-table (depends on: jquery, dp-admin-scripts)
├── dp-bulk-update-modals (depends on: jquery, dp-admin-scripts)
└── dp-besetzung-modal (depends on: jquery, dp-admin-scripts)
```

#### AJAX-Endpunkte
Alle Modal-Funktionen verwenden bestehende AJAX-Handler:
- `dp_save_verein`, `dp_get_verein`, `dp_delete_verein`
- `dp_save_veranstaltung`, `dp_get_veranstaltung`, `dp_delete_veranstaltung`
- `dp_create_event_page`, `dp_update_event_page`
- `dp_save_dienst`, `dp_get_dienst`, `dp_delete_dienst`
- `dp_save_bereich`, `dp_get_bereich`
- `dp_save_taetigkeit`, `dp_get_taetigkeit`, `dp_delete_taetigkeit`
- `dp_register_service` (Frontend - neu für Split-Anmeldung)

### ⚠️ Breaking Changes
Keine - Alle Änderungen sind abwärtskompatibel.

### 📊 Datenbank-Migration erforderlich
**JA - mitarbeiter_id Spalte:**
- Automatisch: Bei Plugin-Update via `class-database.php` Lines 328-340
- Manuell: `migrate-mitarbeiter-id.php` ausführen

### 🎯 Nächste Schritte (0.5.0)
- [ ] Backend-AJAX-Handler für alle Modal-Save-Funktionen testen
- [ ] Bulk-Update-Modals voll implementieren (aktuell nur Platzhalter)
- [ ] Besetzungs-Modal vollständig integrieren
- [ ] Dienst-Split im Backend ermöglichen
- [ ] Unit-Tests für split_dienst() Methode

---

## [0.4.0] - 2025-11-13

### ✨ Hinzugefügt

#### JavaScript-Refactoring (Major Improvement)
- **JavaScript wurde aus PHP-Views ausgelagert:**
  - Neue Datei: `assets/js/dp-dienst-modal.js` - Alle Modal-Funktionen für Dienste
  - Neue Datei: `assets/js/dp-dienste-table.js` - Tabellen-Funktionen und Bulk-Aktionen
  - Proper WordPress Script-Enqueuing in `class-admin.php`
  - Dependencies korrekt definiert (jQuery)
  - Scripts werden nur im Admin-Bereich geladen

### 🔧 Geändert

#### Code-Qualität & Best Practices
- **Keine Inline-Scripts mehr in PHP-Dateien**
  - `dienst-modal.php` - Alle `<script>`-Tags entfernt
  - `dienste-table.php` - Alle `<script>`-Tags entfernt
  - Kommentare zeigen auf neue JavaScript-Dateien
- **Verbessertes Script-Loading:**
  - Scripts werden via `wp_enqueue_script()` geladen
  - Korrekte Abhängigkeiten (jQuery, dp-admin-scripts)
  - Versionierung für Cache-Busting
  - Scripts im Footer geladen (bessere Performance)
- **Erweiterte wp_localize_script Daten:**
  - `selectedVeranstaltung` wird aus GET-Parameter übernommen
  - Zentrale AJAX-Konfiguration für alle Scripts

#### JavaScript-Struktur
- **IIFE Pattern** für bessere Kapselung `(function($) { ... })(jQuery)`
- **Globale Funktionen** klar gekennzeichnet (`window.functionName`)
- **Private Funktionen** innerhalb der IIFE
- **Ausführliche Kommentare** und Funktionsbeschreibungen
- **TODOs markiert** für zukünftige Verbesserungen (Modal-Dialoge statt prompt())

### 🐛 Behoben
- **Doppelte Funktionsdefinition** von `deleteDienst` in `dienst-modal.php` behoben
- **Fehlende schließende Klammern** in JavaScript-Code korrigiert

### 📊 Performance
- **JavaScript wird gecacht** durch Browser (separate Dateien)
- **Reduzierter HTML-Output** durch Entfernung von Inline-Scripts
- **Schnelleres Laden** durch Defer/Footer-Loading

### 🏗️ Technische Details

#### Neue Dateien
```
assets/js/
├── dp-dienst-modal.js      [NEU] - Modal-Funktionen für Dienste (570 Zeilen)
└── dp-dienste-table.js     [NEU] - Tabellen-Funktionen & Bulk-Aktionen (285 Zeilen)
```

#### Geänderte Dateien
- `admin/class-admin.php` - Erweitertes Script-Enqueuing
- `admin/views/partials/dienst-modal.php` - Inline-Scripts entfernt
- `admin/views/partials/dienste-table.php` - Inline-Scripts entfernt

#### Script-Dependencies
```
dp-admin-scripts (base)
├── dp-dienst-modal (depends on: jquery, dp-admin-scripts)
└── dp-dienste-table (depends on: jquery, dp-admin-scripts)
```

### ⚠️ Breaking Changes
Keine - Die Funktionalität bleibt vollständig erhalten.

### 🎯 Nächste Schritte (0.5.0)
- [ ] Weitere Partials refactoren (vereine-modal.php, veranstaltungen-modal.php, etc.)
- [ ] Modal-Dialoge statt `prompt()` für Bulk-Aktionen
- [ ] JavaScript Minification für Production
- [ ] ESLint Integration

---

## [0.3.0] - 2025-11-12

### ✨ Hinzugefügt

#### Bereiche & Tätigkeiten Verwaltung
- Neue Admin-Seite "Bereiche & Tätigkeiten"
- Bereiche können erstellt, bearbeitet und gelöscht werden
- Farbzuordnung für bessere visuelle Unterscheidung
- Tätigkeiten sind Bereichen zugeordnet (hierarchische Struktur)
- Modal-Dialoge für komfortables Bearbeiten
- **Bulk-Aktionen für Tätigkeiten:**
  - Mehrfachauswahl mit Checkboxen
  - Löschen mehrerer Tätigkeiten gleichzeitig
  - Bereich verschieben (mehrere Tätigkeiten umziehen)
  - Status ändern (aktivieren/deaktivieren)
- Verwendungs-Counter zeigt Anzahl der Dienste pro Tätigkeit
- Schutz: Tätigkeiten mit aktiven Diensten können nicht gelöscht werden

#### Bulk-Aktionen für Dienste
- Checkbox-basierte Mehrfachauswahl in Dienste-Tabelle
- **Verfügbare Bulk-Aktionen:**
  - Löschen mehrerer Dienste
  - Tag verschieben (Dienste zu anderem Tag bewegen)
  - Zeiten ändern (Start-/Endzeit für mehrere Dienste)
  - Verein ändern
  - Bereich ändern
  - Tätigkeit ändern
  - Status ändern (geplant/unvollständig/bestätigt)
- Bulk-Toolbar erscheint automatisch bei Auswahl
- Bestätigungsdialoge vor kritischen Aktionen

#### Import/Export erweitert
- **CSV-Export für Vereine:**
  - ID, Name, Kürzel, Beschreibung, Kontaktdaten, Status
- **CSV-Export für Veranstaltungen:**
  - ID, Name, Beschreibung, Start-/Enddatum
- **CSV-Export für Dienste (komplett überarbeitet):**
  - 15 Spalten inkl. Tag-Nummer, Verein, Bereich, Tätigkeit
  - Zeitangaben, Personenanzahl, Status, Besonderheiten
  - Korrekte Objektzugriffe (Bug behoben)

#### Admin-Übersicht verbessert
- Tag-gruppierte Ansicht mit kollabierbaren Sektionen
- Alle Veranstaltungs-Tage werden angezeigt (auch ohne Dienste)
- Fixierte linke Spalten (Bereich, Tätigkeit, Verein, Zeit)
- Scrollbare Mitarbeiter-Badges für bessere Übersicht
- Visuell getrennte Bereiche mit farbigen Headers

### 🔧 Geändert

#### Code-Struktur & Wartbarkeit
- **Datenbankstruktur vollständig dokumentiert** (`DATABASE_STRUCTURE.md`)
  - Alle 13 Tabellen mit Feldern und Beziehungen
  - ER-Diagramm als Text
  - Datenfluss dokumentiert
- Code-Duplikate entfernt (3 doppelte Database-Klassen gelöscht)
- Konsistente Verwendung einer Database-Klasse (`class-database.php`)
- Cleanup-Report erstellt (`CLEANUP_REPORT.md`)

#### Zeit-Handling verbessert
- **Zeit-Normalisierung:** Eingaben wie "19.00" werden automatisch zu "19:00:00" konvertiert
- **Overnight-Dienste:** Automatische Erkennung wenn Endzeit < Startzeit
  - Setzt automatisch `bis_datum` auf +1 Tag
  - Visuelle Kennzeichnung in allen Ansichten
- Validation vereinfacht: Prüft nur noch logische Fehler (Start < Ende)
- Keine starren Zeitfenster-Beschränkungen mehr

#### Status-System
- **Dienst-Status konsistent implementiert:**
  - `geplant` - Standardstatus für neue Dienste
  - `unvollstaendig` - Fehlende Informationen (gelbe Kennzeichnung)
  - `bestaetigt` - Vollständig und bestätigt
- OR-Logik für "unvollständig": Mindestens ein Wert fehlt
- Visuelle Indikatoren (Farben, Icons) in allen Views

### 🐛 Behoben

#### Import/Export Fixes
- CSV-Export verwendete falsche Array-Syntax für Objekte (`$row['field']` → `$row->field`)
- Fehlende Felder in Exporten hinzugefügt (`bis_datum`, `status`, `tag_nummer`)
- Korrekte Feldreferenzen (`ende_datum` → `end_datum`)

#### Übersicht/Overview Fixes
- Tag-Gruppierung nutzte Dienst-Datum statt Veranstaltungs-Tag (`tag_id` korrekt verwendet)
- Null-Pointer-Fehler bei Diensten ohne `tag_id` behoben
- Dienste ohne Tag werden in separatem Bereich angezeigt

#### Validierung & Datenintegrität
- Zeit-Validation korrigiert (19.00 - 01:00 funktioniert jetzt)
- Overnight-Dienste werden korrekt validiert und gespeichert
- Status-Feld wird bei Import/Export korrekt behandelt

### ❌ Entfernt

- **Feld `erforderliche_qualifikation` aus Tätigkeiten-Tabelle**
  - Wurde in keiner Funktion tatsächlich genutzt
  - Komplexität ohne Mehrwert reduziert
  - Migration wurde **nicht** erstellt (Feld war nie produktiv)

- **Duplikat-Dateien gelöscht:**
  - `includes/class-dienstplan-database.php` (Duplikat)
  - `includes/class-dienstplan-database.backup.php`
  - `includes/class-dienstplan-database-clean.php`

### 🔒 Sicherheit

- Alle AJAX-Calls haben Nonce-Prüfungen
- Capability-Checks für alle Admin-Funktionen
- Prepared Statements für alle Datenbank-Queries
- Input-Sanitization konsequent angewendet
- Output-Escaping (esc_html, esc_attr) verwendet

### 📊 Performance

- Indizes auf häufig genutzte Spalten gesetzt
- Prepared Statements cachen Query-Plans
- Lazy Loading für Mitarbeiter-Daten in Übersicht

### 🏗️ Technische Details

#### Neue AJAX-Handler
- `bulk_delete_dienste` - Löscht mehrere Dienste
- `bulk_update_dienste` - Aktualisiert Dienst-Felder
- `get_bereich` - Lädt Bereich-Daten
- `save_bereich` - Speichert Bereich
- `delete_bereich` - Löscht Bereich
- `get_taetigkeit` - Lädt Tätigkeits-Daten
- `save_taetigkeit` - Speichert Tätigkeit
- `delete_taetigkeit` - Löscht Tätigkeit
- `toggle_taetigkeit_status` - Aktiviert/Deaktiviert Tätigkeit
- `bulk_delete_taetigkeiten` - Löscht mehrere Tätigkeiten
- `bulk_update_taetigkeiten` - Aktualisiert Tätigkeiten

#### Neue Database-Methoden
- `count_dienste_by_taetigkeit($taetigkeit_id)` - Zählt Verwendungen
- `create_taetigkeit($data)` - Alias für add_taetigkeit

#### Dateistruktur
```
admin/views/
  ├── bereiche-taetigkeiten.php  [NEU] - Bereiche & Tätigkeiten Verwaltung
  ├── overview.php               [ÜBERARBEITET] - Tag-gruppierte Übersicht
  └── partials/
      └── dienste-table.php      [ÜBERARBEITET] - Mit Bulk-Actions
```

---

## [0.2.6] - 2025-11-XX

### Geändert
- Diverse kleinere Fixes und Verbesserungen
- (Details aus früheren Versionen hier einfügen)

---

## [0.2.0] - 2025-XX-XX

### Hinzugefügt
- Grundlegende Plugin-Struktur
- Vereine-Verwaltung
- Veranstaltungen-Verwaltung
- Dienste-Verwaltung
- Mitarbeiter-Verwaltung
- Import/Export Grundfunktionen

---

## Legende

- ✨ **Hinzugefügt** - Neue Features
- 🔧 **Geändert** - Änderungen an bestehenden Features
- 🐛 **Behoben** - Bug Fixes
- ❌ **Entfernt** - Entfernte Features
- 🔒 **Sicherheit** - Sicherheits-Fixes
- 📊 **Performance** - Performance-Verbesserungen
- 🏗️ **Technische Details** - Interne Änderungen für Entwickler

---

## Migration von 0.2.x zu 0.3.0

### Datenbank
Keine Datenbank-Migration erforderlich. Alle Änderungen sind abwärtskompatibel.

### Code
Falls Sie das Plugin erweitert haben:
- Feld `erforderliche_qualifikation` existiert nicht mehr in Tätigkeiten
- Neue AJAX-Handler verfügbar (siehe Technische Details)
- Database-Klasse: Nur noch `includes/class-database.php` verwenden

### Bekannte Einschränkungen
- Bulk-Action Dialoge verwenden noch `prompt()` statt Modals (wird in 0.4.0 verbessert)
- JavaScript ist inline in PHP-Views (wird in 0.4.0 ausgelagert)

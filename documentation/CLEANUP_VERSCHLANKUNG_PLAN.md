# Verschlankungs-Plan Dienstplan-Verwaltung

Stand: 2026-05-06

## Ziel
Das Plugin auf einen stabilen Minimalbetrieb reduzieren: nur Kernfunktionen aktiv lassen, alles andere kontrolliert abschalten.

## Backup-Status
- Dateibackup als ZIP liegt vor.
- Git-Backup-Branch und Git-Backup-Tag sind angelegt.

## Minimalbetrieb (soll aktiv bleiben)
- Vereine verwalten
- Veranstaltungen verwalten
- Bereiche und Taetigkeiten verwalten
- Dienste verwalten
- Import und Export (CSV)
- Rollen- und Rechtepruefung

## Vorlaeufig abschaltbar (Phase 1)
- Auto-Updater
- Mail Queue und Mail Templates
- Benachrichtigungs-Mails
- Erweiterte Komfortfunktionen im Import (Alias-Mapping, Auto-Zuordnung optional)
- Nicht-kritische Admin-UX Helfer

## Spaeter pruefen (Phase 2)
- Oeffentliche Self-Service-Flows (Frontend-Anmeldung/Abmeldung), falls fuer Live-Betrieb nicht zwingend
- Historische Legacy-Pfade und doppelte Codepfade
- Sehr spezielle Sonderfaelle fuer Split/Auto-Zuordnung

## Technischer Ablauf
1. Inventur
- Alle Hooks und AJAX-Endpoints erfassen.
- Jeder Hook bekommt eine Klassifikation: Kern / Optional / Unklar.

2. Feature-Flags einbauen
- Ein zentrales Config-Array in den Plugin-Einstellungen oder als Konstante.
- Optional-Funktionen nur registrieren, wenn Flag aktiv ist.

3. Sanftes Abschalten
- Zuerst Updater, Mail Queue, Notifications deaktivieren.
- Danach Import-Komfortfunktionen schrittweise reduzieren.

4. Regressionstests nach jedem Schritt
- Imports: vereine, bereiche, taetigkeiten, veranstaltungen, dienste, dienstplan.
- Exports: alle CSV-Varianten.
- Basis-Adminseiten: laden ohne PHP-Warnungen.

5. Cleanup-Runde
- Tote Includes entfernen.
- Unbenutzte JS/CSS entkoppeln.
- Dokumentation aktualisieren.

## Akzeptanzkriterien fuer "verschlankt"
- Kein Funktionsverlust bei Kernprozessen.
- Keine PHP-Warnings im Import/Export.
- Alle 6 Importtypen laufen durch.
- Exportdateien bleiben kompatibel zum Import.
- Update-Mechanik ist bewusst an/aus dokumentiert.

## Umsetzung in 3 Paketen
- Paket A (sicher): Updater + Mail + Notifications abschaltbar machen.
- Paket B (mittel): Import-UX vereinfachen, aber Mapping/Validierung stabil halten.
- Paket C (optional): Frontend-Self-Service reduzieren, falls fachlich freigegeben.

## Naechster Schritt
Paket A starten: zentrale Feature-Flags einbauen und Updater/Mail/Notifications per Default deaktivierbar machen, danach Import/Export komplett durchtesten.

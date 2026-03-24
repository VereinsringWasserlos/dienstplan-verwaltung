# Dienstplan Verwaltung V2

**Version:** 0.9.5.47 (Dashboard Version & Import UX Release)  
**Requires at least:** WordPress 5.0  
**Tested up to:** WordPress 6.4  
**Requires PHP:** 7.2  
**License:** GPLv2 or later  

Moderne Dienstplan-Verwaltung für Events und Veranstaltungen mit Slot-System, Frontend Crew-Portal und vollständiger Admin-Verwaltung.

---

## 🚀 Features

### Kern-Funktionalität
- **Frontend Portal** - Moderne Einstiegsseite mit Login und Veranstaltungsübersicht (NEU v0.6.6)
- **Slot-basiertes Dienst-System** - Flexible Besetzung mit mehreren Personen pro Dienst
- **Split-Dienste** - Dienste in zwei Zeitfenster aufteilen
- **Frontend Crew-Portal** - Self-Service für Helfer-Anmeldung
- **Admin-Backend** - Vollständige Verwaltung aller Bereiche
- **3 Benutzerrollen** - WordPress Admin, Vereinsverwalter, Crew-Mitglied
- **AJAX-basiert** - Schnelle, moderne Benutzeroberfläche

### Verwaltung
- **Vereine** - Multi-Verein-Unterstützung
- **Veranstaltungen** - Event-Management mit Datum/Uhrzeit
- **Bereiche & Tätigkeiten** - Flexible Kategorisierung
- **Mitarbeiter** - Crew-Verwaltung mit Kontaktdaten
- **Dienste** - Zeitfenster-basierte Dienst-Planung

### Technisch
- **13 Datenbank-Tabellen** - Normalisierte Struktur
- **Git-basierte Updates** - Automatische WordPress-Updates
- **Responsive Design** - Mobile-optimiert
- **Umfassende Dokumentation** - Backend-integriert

---

## 📦 Installation

### Automatisch (WordPress)
1. Download Plugin-ZIP
2. WordPress Admin → Plugins → Installieren → ZIP hochladen
3. Plugin aktivieren
4. Fertig! Datenbank-Tabellen werden automatisch erstellt

### Manuell
```bash
cd wp-content/plugins/
git clone https://github.com/VereinsringWasserlos/dienstplan-verwaltung.git
```

Dann in WordPress Admin das Plugin aktivieren.

---

## 🔄 Updates

Das Plugin unterstützt automatische Updates über das WordPress Update-System:

1. Bei neuer Version erscheint Update-Benachrichtigung in WordPress
2. Update über WordPress Admin → Updates durchführen
3. Datenbank-Migrationen laufen automatisch

**Wichtig:** Vor Updates immer ein Backup erstellen!

---

## 📖 Dokumentation

Die komplette Dokumentation ist im Backend integriert:
- **Dienstplan → Dokumentation**

Verfügbare Dokumente:
- Quick-Start Guide (15 Minuten Einstieg)
- Backend-Bedienungsanleitung (650+ Zeilen)
- Frontend-Bedienungsanleitung (500+ Zeilen)
- Frontend Portal Setup (NEU v0.6.6)
- Datenbank-Struktur
- Technische Dokumentation
- Test-Plan
- Changelog
- Roadmap bis Version 2.0

### Frontend Portal Schnellstart

**Neue WordPress-Seite erstellen und Shortcode einfügen:**

```
[dienstplan_hub]
```

Zeigt eine komplette Einstiegsseite mit:
- Login/Registrierung für neue Benutzer
- Personalisierte Begrüßung für angemeldete Benutzer
- Übersicht aktueller Veranstaltungen
- Quick-Links zu "Meine Dienste" und Profil

**Weitere Details:** `documentation/FRONTEND_PORTAL.md`

---

## 🎯 Aktueller Stand

- Aktuelle Version: **0.9.5.47**
- Laufende Änderungen und Releases: siehe `documentation/CHANGELOG.md`
- Rollenübersicht: siehe `documentation/ROLLEN-UEBERSICHT.md`
- Schnellstart: siehe `documentation/QUICK_START.md`

---

## 📄 Lizenz

GPLv2 or later

---

## 💬 Support

- **Dokumentation:** Backend → Dienstplan → Dokumentation
- **Issues:** GitHub Issues
- **Updates:** Automatisch über WordPress

---

**Version 0.9.5.47** - Dashboard Version & Import UX Release  
Stand: 24. März 2026

Entwickelt für den Vereinsring Wasserlos und die Feuerwehr- und Event-Gemeinschaft.
# Dokumentation - Dienstplan-Verwaltung V2

**Version:** 0.9.0 (UAT Release)  
**Stand:** 17. November 2025  
**Status:** Ready for User Acceptance Testing

---

## 📚 Schnellzugriff

### Für neue Benutzer
👉 **[Quick-Start Guide](QUICK_START.md)** - In 15 Minuten einsatzbereit

### Für Administratoren
📖 **[Backend-Bedienungsanleitung](BEDIENUNGSANLEITUNG_BACKEND.md)** - Vollständige Admin-Anleitung

### Für Crew-Mitglieder
👥 **[Frontend-Bedienungsanleitung](BEDIENUNGSANLEITUNG_FRONTEND.md)** - Anleitung für Helfer

---

## 📋 Dokumentations-Übersicht

### 🚀 Einstieg

| Dokument | Beschreibung | Zielgruppe |
|----------|--------------|------------|
| **[QUICK_START.md](QUICK_START.md)** | 15-Minuten-Setup-Guide | Alle |
| **[VERSION_0.9.0_FEATURES.md](VERSION_0.9.0_FEATURES.md)** | Komplette Feature-Übersicht | Alle |
| **[ROADMAP.md](ROADMAP.md)** | Zukünftige Entwicklung & Ausblick | Entscheider |

### 📖 Bedienungsanleitungen

| Dokument | Beschreibung | Zielgruppe |
|----------|--------------|------------|
| **[BEDIENUNGSANLEITUNG_BACKEND.md](BEDIENUNGSANLEITUNG_BACKEND.md)** | Vollständige Admin-Dokumentation (650+ Zeilen) | Admins, Vereinsverwalter |
| **[BEDIENUNGSANLEITUNG_FRONTEND.md](BEDIENUNGSANLEITUNG_FRONTEND.md)** | User-Guide für Crew-Mitglieder (500+ Zeilen) | Crew, Helfer |
| **[SCREENSHOTS.md](SCREENSHOTS.md)** | Anleitung für Screenshot-Erstellung | Content-Ersteller |

### 🔧 Technische Dokumentation

| Dokument | Beschreibung | Zielgruppe |
|----------|--------------|------------|
| **[DATABASE_STRUCTURE_AKTUELL.md](DATABASE_STRUCTURE_AKTUELL.md)** | Vollständige DB-Schema-Dokumentation (550+ Zeilen) | Entwickler |
| **[STRUCTURE.md](STRUCTURE.md)** | Plugin-Architektur & Code-Organisation | Entwickler |
| **[CSS_COMPONENTS.md](CSS_COMPONENTS.md)** | CSS-Klassen & Styling-Guide | Designer, Entwickler |
| **[ROLLEN-UEBERSICHT.md](ROLLEN-UEBERSICHT.md)** | Rollen & Berechtigungen | Admins |
| **[DIENST_ZEITFENSTER.md](DIENST_ZEITFENSTER.md)** | Zeitfenster-Dokumentation | Entwickler |
| **[TAG_ASSIGNMENT_BUG_FIX.md](TAG_ASSIGNMENT_BUG_FIX.md)** | Spezifische Bug-Fix-Dokumentation | Entwickler |

### 📝 Projekt-Management

| Dokument | Beschreibung | Zielgruppe |
|----------|--------------|------------|
| **[CHANGELOG.md](CHANGELOG.md)** | Vollständiges Änderungsprotokoll | Alle |
| **[TEST_PLAN.md](TEST_PLAN.md)** | Test-Szenarien & QA | Tester, QA |
| **[ROADMAP.md](ROADMAP.md)** | Feature-Roadmap bis Version 2.0 | Alle |

---

## 🎯 Version 0.9.0 - UAT Release

### Was ist neu?

**Kern-Features:**
- ✅ Slot-basiertes Split-System (keine Dienst-Duplikate mehr!)
- ✅ Intelligente Slot-Zuweisung mit Split-Support
- ✅ Email optional bei Anmeldung
- ✅ Komplette Dokumentation (Backend, Frontend, Technisch)
- ✅ Integrierter Dokumentations-Bereich im Backend
- ✅ 25 definierte Screenshots (Platzhalter + Richtlinien)

**Wichtigste Änderung:**
```
ALT (0.4.7): Split erstellt neue Dienste → Duplikate!
NEU (0.9.0): Split passt Slots an → Keine Duplikate!
```

**Details:** [VERSION_0.9.0_FEATURES.md](VERSION_0.9.0_FEATURES.md)

---

## 🧪 UAT-Testing

### Für Tester

**Testfokus Version 0.9.0:**
1. **Split-Dienst-System** (Priorität 1)
   - Funktioniert Split korrekt?
   - Werden Slots richtig erstellt?
   - Keine Duplikate mehr?

2. **Anmeldeprozess** (Priorität 1)
   - Frontend-Anmeldung reibungslos?
   - Email optional funktioniert?
   - Fehlerbehandlung korrekt?

3. **Admin-Funktionen** (Priorität 2)
   - Alle Modals funktionieren?
   - Bulk-Updates klappen?
   - Dokumentation erreichbar?

**Testplan:** [TEST_PLAN.md](TEST_PLAN.md)

### Feedback einreichen

- 🐛 **Bugs:** GitHub Issues erstellen
- 💡 **Features:** GitHub Discussions
- 📚 **Dokumentation:** Pull Request
- 📧 **Email:** support@vereinsring-wasserlos.de

---

## 🗂️ Ordnerstruktur

```
documentation/
├── README.md (diese Datei)
├── QUICK_START.md
├── VERSION_0.9.0_FEATURES.md ⭐ NEU
├── ROADMAP.md ⭐ NEU
├── CHANGELOG.md (aktualisiert)
├── BEDIENUNGSANLEITUNG_BACKEND.md ⭐ NEU
├── BEDIENUNGSANLEITUNG_FRONTEND.md ⭐ NEU
├── SCREENSHOTS.md ⭐ NEU
├── DATABASE_STRUCTURE_AKTUELL.md
├── STRUCTURE.md
├── CSS_COMPONENTS.md
├── TEST_PLAN.md
├── ROLLEN-UEBERSICHT.md
├── DIENST_ZEITFENSTER.md
├── TAG_ASSIGNMENT_BUG_FIX.md
├── archive/
│   ├── README.md
│   └── VERSION_0.4.7_FEATURES.md
└── screenshots/
    ├── backend/ (16 Platzhalter)
    └── frontend/ (9 Platzhalter)
```

---

## 📖 Dokumentation im Plugin

### Zugriff im Backend

Die gesamte Dokumentation ist auch direkt im WordPress-Backend verfügbar:

**Navigation:**
```
WordPress-Admin → Dienstplan → Dashboard → Administration → Dokumentation
```

**Features:**
- Kategorisierte Sidebar (Einstieg / Anleitungen / Technisch)
- Markdown-zu-HTML-Rendering
- Syntax-Highlighting für Code
- Download-Buttons für Original-Dateien
- Responsive Design

---

## 🚀 Quick-Links

### Für Erste Schritte
1. [Quick-Start Guide](QUICK_START.md) lesen (15 Min)
2. Plugin aktivieren
3. Ersten Verein anlegen
4. Erste Veranstaltung erstellen
5. Frontend-Link an Crew senden

### Für Entwickler
1. [STRUCTURE.md](STRUCTURE.md) - Code-Organisation
2. [DATABASE_STRUCTURE_AKTUELL.md](DATABASE_STRUCTURE_AKTUELL.md) - DB-Schema
3. [CSS_COMPONENTS.md](CSS_COMPONENTS.md) - Styling
4. GitHub Repository forken
5. Feature-Branch erstellen

### Für Tester
1. [VERSION_0.9.0_FEATURES.md](VERSION_0.9.0_FEATURES.md) - Was testen?
2. [TEST_PLAN.md](TEST_PLAN.md) - Testfälle
3. Test-Environment aufsetzen
4. Feedback sammeln
5. Issues erstellen

---

## 📊 Dokumentations-Status

| Kategorie | Status | Vollständigkeit |
|-----------|--------|-----------------|
| **Bedienungsanleitungen** | ✅ Vollständig | 100% |
| **Technische Docs** | ✅ Vollständig | 100% |
| **Screenshots** | ⚠️ Platzhalter | 0/25 |
| **API-Dokumentation** | ❌ Ausstehend | 0% |
| **Video-Tutorials** | ❌ Geplant für 1.1.0 | 0% |

---

## 🤝 Beitragen

### Dokumentation verbessern

**Fehler gefunden?**
1. Issue erstellen oder direkt Pull Request
2. Markdown-Datei bearbeiten
3. Änderungen committen
4. Pull Request einreichen

**Screenshots erstellen?**
1. [SCREENSHOTS.md](SCREENSHOTS.md) lesen
2. 25 Screenshots nach Vorlage erstellen
3. In `/screenshots/backend/` oder `/screenshots/frontend/` ablegen
4. Markdown-Dateien aktualisieren (Platzhalter ersetzen)
5. Pull Request einreichen

**Neue Dokumentation?**
1. Diskussion im GitHub-Repository starten
2. Template verwenden (falls vorhanden)
3. Peer-Review einholen
4. Pull Request einreichen

---

## 📞 Support & Kontakt

**Projekt-Lead:** Kai Naumann  
**Website:** https://vereinsring-wasserlos.de  
**Email:** support@vereinsring-wasserlos.de  

**Office Hours:**
- Dienstags: 19:00-21:00 Uhr (Online-Sprechstunde)
- Donnerstags: 15:00-17:00 Uhr (Chat-Support)

**Community:**
- Discord (geplant)
- Forum (geplant)
- GitHub Discussions

---

## 📜 Lizenz

**GPL v2 or later**

Diese Dokumentation ist Teil des Dienstplan-Verwaltung V2 Plugins und unterliegt der gleichen Lizenz (GPL v2 or later).

---

**Letzte Aktualisierung:** 17. November 2025  
**Version:** 0.9.0 UAT Release  
**Nächste Aktualisierung:** Dezember 2025 (v0.9.5)

---

*Vielen Dank für die Nutzung der Dienstplan-Verwaltung! Bei Fragen stehen wir gerne zur Verfügung.*

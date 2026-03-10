# Automatischer Portal-Setup nach Plugin-Aktivierung

**Version:** 0.6.6  
**Feature:** Automatischer Hinweis zur Erstellung einer Frontend Portal-Seite

---

## Funktionsweise

Nach der Aktivierung des Plugins wird automatisch ein **freundlicher Hinweis** im WordPress-Admin-Bereich angezeigt, der die Erstellung einer Frontend Portal-Seite vorschlägt.

### Was passiert bei Aktivierung?

1. **Plugin wird aktiviert**
   ```
   WordPress → Plugins → Aktivieren
   ```

2. **Transient wird gesetzt**
   - Zeitraum: 24 Stunden
   - Name: `dienstplan_show_portal_setup`

3. **Admin-Notice erscheint**
   - Wird auf allen Admin-Seiten angezeigt
   - Nur wenn noch keine Portal-Seite existiert
   - Automatisch ausgeblendet wenn Seite schon existiert

---

## Der Hinweis

### Aussehen

```
┌────────────────────────────────────────────────────┐
│ 🎉 Dienstplan-Verwaltung erfolgreich aktiviert!    │
│                                                     │
│ Möchten Sie jetzt eine Frontend-Portal-Seite       │
│ erstellen? Diese bietet Ihren Benutzern eine       │
│ moderne Einstiegsseite mit Login und               │
│ Veranstaltungsübersicht.                           │
│                                                     │
│ [Jetzt Portal-Seite erstellen] [Später erstellen] │
└────────────────────────────────────────────────────┘
```

### Optionen

**Option 1: "Jetzt Portal-Seite erstellen"**
- Erstellt automatisch eine neue WordPress-Seite
- Titel: "Dienstplan"
- Inhalt: `[dienstplan_hub]`
- Status: Veröffentlicht
- Zeigt nach Erstellung:
  - ✅ Erfolgs-Meldung
  - Link zur Seiten-Bearbeitung
  - Link zur Seiten-Vorschau

**Option 2: "Später erstellen"**
- Schließt den Hinweis
- Löscht das Transient
- Keine weitere Anzeige

---

## Technische Details

### Dateien

**Aktivierung:**
```
includes/class-activator.php
→ activate() Methode setzt Transient
```

**Admin-Notice:**
```
admin/class-admin.php
→ show_admin_notices() Methode zeigt Hinweis
→ ajax_create_portal_page() erstellt Seite
→ ajax_dismiss_portal_notice() schließt Hinweis
```

**Hook-Registrierung:**
```
includes/class-dienstplan-verwaltung.php
→ wp_ajax_dp_create_portal_page
→ wp_ajax_dp_dismiss_portal_notice
```

### Transient

```php
// Gesetzt bei Aktivierung
set_transient('dienstplan_show_portal_setup', true, 60 * 60 * 24);

// Gelöscht bei:
// - Seiten-Erstellung
// - "Später erstellen" Klick
// - Automatisch nach 24 Stunden
delete_transient('dienstplan_show_portal_setup');
```

### AJAX-Endpoints

**Portal-Seite erstellen:**
```javascript
POST /wp-admin/admin-ajax.php
action: dp_create_portal_page
nonce: [wp_nonce]

Response:
{
    success: true,
    data: {
        page_id: 123,
        page_title: "Dienstplan",
        edit_url: "/wp-admin/post.php?post=123&action=edit",
        view_url: "/dienstplan/"
    }
}
```

**Hinweis schließen:**
```javascript
POST /wp-admin/admin-ajax.php
action: dp_dismiss_portal_notice
nonce: [wp_nonce]

Response:
{
    success: true
}
```

---

## Benutzer-Ablauf

### Szenario 1: Sofort erstellen

```
1. Plugin aktivieren
   ↓
2. Hinweis im Admin-Bereich sehen
   ↓
3. "Jetzt Portal-Seite erstellen" klicken
   ↓
4. Warten (1-2 Sekunden)
   ↓
5. Erfolgs-Meldung sehen
   ↓
6. "Seite bearbeiten" oder "Seite ansehen" klicken
   ↓
7. Fertig! 🎉
```

### Szenario 2: Später erstellen

```
1. Plugin aktivieren
   ↓
2. Hinweis im Admin-Bereich sehen
   ↓
3. "Später erstellen" klicken
   ↓
4. Hinweis verschwindet
   ↓
5. Manuelle Erstellung möglich über:
   - Seiten → Neu hinzufügen
   - [dienstplan_hub] einfügen
```

### Szenario 3: Seite existiert bereits

```
1. Plugin aktivieren
   ↓
2. System prüft ob Portal-Seite existiert
   ↓
3. Seite gefunden: Kein Hinweis angezeigt
   ↓
4. Transient wird automatisch gelöscht
```

---

## Vorteile

### Für Administratoren

✅ **Einfacher Einstieg**
- Keine manuelle Seiten-Erstellung nötig
- Ein Klick genügt

✅ **Zeitersparnis**
- Kein Copy-Paste von Shortcodes
- Automatische Setup

✅ **Keine Fehler**
- Richtiger Shortcode wird automatisch eingefügt
- Korrekte Seiteneinstellungen

### Für Entwickler

✅ **Beste Praktiken**
- Transient statt permanente Option
- Nicht aufdringlich (24h Zeitlimit)
- Leicht zu dismissieren

✅ **Sicher**
- Nonce-Prüfung
- Capability-Check (manage_options)
- Sanitized Input

✅ **Performance**
- Keine DB-Abfrage bei jeder Admin-Seite
- Transient wird automatisch gelöscht
- Effiziente Prüfung

---

## Anpassungen

### Hinweis-Text ändern

In `admin/class-admin.php` → `show_admin_notices()`:

```php
<h3>🎉 Ihr eigener Text hier!</h3>
<p>Ihre Beschreibung...</p>
```

### Seiten-Titel ändern

In `admin/class-admin.php` → `ajax_create_portal_page()`:

```php
'post_title' => __('Mein Portal', 'dienstplan-verwaltung'),
```

### Transient-Dauer ändern

In `includes/class-activator.php` → `activate()`:

```php
// 7 Tage statt 24 Stunden
set_transient('dienstplan_show_portal_setup', true, 60 * 60 * 24 * 7);
```

### Hinweis erneut anzeigen

Wenn der Hinweis geschlossen wurde und erneut angezeigt werden soll:

**Manuell in Database:**
```sql
INSERT INTO wp_options (option_name, option_value, autoload) 
VALUES ('_transient_dienstplan_show_portal_setup', '1', 'no');
```

**Per PHP:**
```php
set_transient('dienstplan_show_portal_setup', true, 60 * 60 * 24);
```

**Im Admin:**
```
Plugins → Deaktivieren → Aktivieren
```

---

## Troubleshooting

### Problem: Hinweis wird nicht angezeigt

**Mögliche Ursachen:**

1. **Portal-Seite existiert bereits**
   - Lösung: Seite löschen oder Shortcode ändern

2. **Transient abgelaufen**
   - Lösung: Plugin neu aktivieren

3. **JavaScript-Fehler**
   - Lösung: Browser-Console prüfen

### Problem: Button funktioniert nicht

**Prüfen:**
```javascript
// Browser-Console
console.log('ajaxurl:', ajaxurl);
console.log('jQuery:', typeof jQuery);
```

**Lösung:**
- jQuery muss geladen sein
- ajaxurl muss definiert sein
- Browser-Cache leeren

### Problem: Seiten-Erstellung schlägt fehl

**Gründe:**
- Keine `manage_options` Berechtigung
- Datenbank-Schreibrechte fehlen
- Plugin-Konflikt

**Debug:**
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Dann prüfen:
wp-content/debug.log
```

---

## Best Practices

### Für Plugin-Entwickler

✅ **DO:**
- Transients für temporäre Hinweise verwenden
- Always check capabilities
- Provide dismiss option
- Auto-hide nach Zeitlimit

❌ **DON'T:**
- Permanente Admin-Notices ohne Dismiss
- Aufdringliche Werbung
- Seiten ohne Zustimmung erstellen
- Unnötige DB-Queries

### Für Administrator

✅ **Empfohlen:**
- Hinweis direkt nach Aktivierung beachten
- Portal-Seite erstellen für beste UX
- Seite nach Erstellung anpassen/testen

---

## FAQ

**Q: Kann ich den Hinweis dauerhaft deaktivieren?**

A: Ja, mit einem Filter:
```php
add_filter('dienstplan_show_portal_notice', '__return_false');
```

**Q: Wird die Seite wirklich automatisch erstellt?**

A: Ja, aber nur auf ausdrücklichen Klick des "Erstellen"-Buttons. Nicht vollautomatisch bei Aktivierung.

**Q: Was passiert wenn ich mehrere Portale brauche?**

A: Einfach weitere Seiten manuell erstellen mit dem gleichen Shortcode `[dienstplan_hub]`.

**Q: Beeinflusst das die Performance?**

A: Nein. Der Check läuft nur im Admin-Bereich und nur für 24 Stunden nach Aktivierung.

---

**Entwickelt für optimale User Experience! 🚀**

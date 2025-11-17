# CSS-Komponenten Dokumentation

## Übersicht
Dieses Dokument beschreibt die vereinheitlichten CSS-Klassen und Komponenten für die Dienstplan Verwaltung.

---

## 1. Page Header (`.dienstplan-page-header`)

Einheitlicher Header für alle Verwaltungsseiten mit Navigation.

### PHP-Partial
```php
<?php include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/page-header.php'; ?>
```

### Erforderliche Variablen (vor Include setzen):
```php
$page_title = 'Vereine';                    // Seitentitel
$page_icon = 'dashicons-flag';              // Dashicons-Klasse
$page_class = 'header-vereine';             // CSS-Klasse für Farbe
$nav_items = [                              // Navigation Items
    [
        'label' => 'Dashboard',
        'url' => admin_url('admin.php?page=dienstplan'),
        'icon' => 'dashicons-dashboard',
        'capability' => 'manage_options',    // Optional: Berechtigung
        'hide_on' => 'page-name',            // Optional: Auf dieser Seite verstecken
    ],
    // ...
];
```

### Header-Farb-Klassen
- `.header-vereine` - Blau
- `.header-veranstaltungen` - Orange
- `.header-dienste` - Violett
- `.header-bereiche` - Pink
- `.header-mitarbeiter` - Grün

---

## 2. Buttons (`.dp-btn-*`)

### Basis-Button
```html
<button class="dp-btn dp-btn-primary">
    <span class="dashicons dashicons-plus-alt"></span>
    Neu erstellen
</button>
```

### Button-Varianten
- `.dp-btn-primary` - Hauptfarbe (Blau)
- `.dp-btn-success` - Erfolg (Grün)
- `.dp-btn-warning` - Warnung (Orange)
- `.dp-btn-danger` - Gefahr (Rot)
- `.dp-btn-secondary` - Sekundär (Grau)

### Button-Größen
- `.dp-btn-sm` - Klein
- `.dp-btn` - Standard
- `.dp-btn-lg` - Groß

### Beispiele
```html
<!-- Primär -->
<button class="dp-btn dp-btn-primary">Speichern</button>

<!-- Klein -->
<button class="dp-btn dp-btn-sm dp-btn-secondary">Abbrechen</button>

<!-- Groß mit Icon -->
<a href="#" class="dp-btn dp-btn-lg dp-btn-success">
    <span class="dashicons dashicons-yes"></span>
    Bestätigen
</a>
```

---

## 3. Listen (`.dienstplan-list-*`)

### List Container
```html
<div class="dienstplan-list-container">
    <div class="dienstplan-list-item group-header">
        Vereine
    </div>
    
    <div class="dienstplan-list-item">
        <div class="list-item-content">
            <div class="list-item-content-main">Haupttext</div>
            <div class="list-item-content-secondary">Zusatzinfo</div>
        </div>
        <div class="list-item-actions">
            <button class="dp-btn dp-btn-sm dp-btn-secondary">Edit</button>
            <button class="dp-btn dp-btn-sm dp-btn-danger">Delete</button>
        </div>
    </div>
</div>
```

---

## 4. Forms (`.dp-input`, `.dp-select`, `.dp-textarea`)

### Form Group
```html
<div class="dp-form-group">
    <label for="name">Name</label>
    <input type="text" id="name" class="dp-input" placeholder="Eingeben...">
    <small>Optionale Hilfetxt</small>
</div>

<div class="dp-form-group">
    <label for="role">Rolle</label>
    <select id="role" class="dp-select">
        <option>-- Bitte wählen --</option>
        <option>Admin</option>
    </select>
</div>

<div class="dp-form-group">
    <label for="description">Beschreibung</label>
    <textarea id="description" class="dp-textarea"></textarea>
</div>
```

### Checkboxes & Radios
```html
<div class="dp-form-group">
    <label>
        <input type="checkbox" class="dp-checkbox"> Option 1
    </label>
    <label>
        <input type="checkbox" class="dp-checkbox"> Option 2
    </label>
</div>
```

---

## 5. Alerts (`.dp-alert-*`)

```html
<!-- Erfolg -->
<div class="dp-alert dp-alert-success">
    Operation erfolgreich abgeschlossen!
</div>

<!-- Warnung -->
<div class="dp-alert dp-alert-warning">
    Bitte überprüfen Sie Ihre Eingaben.
</div>

<!-- Fehler -->
<div class="dp-alert dp-alert-danger">
    Ein Fehler ist aufgetreten.
</div>

<!-- Info -->
<div class="dp-alert dp-alert-info">
    Weitere Informationen hier...
</div>
```

---

## 6. Badges (`.dp-badge-*`)

```html
<span class="dp-badge dp-badge-primary">Primary</span>
<span class="dp-badge dp-badge-success">Active</span>
<span class="dp-badge dp-badge-warning">Pending</span>
<span class="dp-badge dp-badge-danger">Inactive</span>
<span class="dp-badge dp-badge-muted">Muted</span>
```

---

## 7. Empty State (`.dp-empty-state`)

```html
<div class="dp-empty-state">
    <div class="dp-empty-state-icon">
        <span class="dashicons dashicons-folder-open"></span>
    </div>
    <h3>Keine Daten gefunden</h3>
    <p>Starten Sie, indem Sie einen neuen Eintrag erstellen.</p>
    <button class="dp-btn dp-btn-primary">
        <span class="dashicons dashicons-plus-alt"></span>
        Neu erstellen
    </button>
</div>
```

---

## 8. Action Dropdowns (Action Buttons)

Siehe [ACTION_BUTTONS_GUIDE.md](./ACTION_BUTTONS_GUIDE.md) für Details zu den einheitlichen Aktion-Dropdown-Menüs.

---

## 9. Farbpalette (CSS Variables)

```css
:root {
    /* Hauptfarben */
    --dp-primary: #667eea;
    --dp-success: #10b981;
    --dp-warning: #f59e0b;
    --dp-danger: #ef4444;
    --dp-info: #3b82f6;
    
    /* Graustufen */
    --dp-gray-50: #f9fafb;
    --dp-gray-100: #f3f4f6;
    --dp-gray-200: #e5e7eb;
    --dp-gray-300: #d1d5db;
    --dp-gray-500: #6b7280;
    --dp-gray-700: #374151;
    --dp-gray-900: #111827;
}
```

---

## 10. Spacing Variables

```css
--dp-spacing-xs: 0.5rem    /* 8px */
--dp-spacing-sm: 0.75rem   /* 12px */
--dp-spacing-md: 1rem      /* 16px */
--dp-spacing-lg: 1.5rem    /* 24px */
--dp-spacing-xl: 2rem      /* 32px */
--dp-spacing-2xl: 3rem     /* 48px */
```

---

## 11. Transitions

```css
--dp-transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
--dp-transition: 300ms cubic-bezier(0.4, 0, 0.2, 1);
--dp-transition-slow: 500ms cubic-bezier(0.4, 0, 0.2, 1);
```

---

## Best Practices

### 1. Verwende immer die CSS-Klassen
✅ RICHTIG:
```html
<button class="dp-btn dp-btn-primary">Speichern</button>
```

❌ FALSCH (inline styles):
```html
<button style="background: blue; padding: 10px;">Speichern</button>
```

### 2. Nutze die Variablen für Spacing
✅ RICHTIG:
```css
margin: var(--dp-spacing-md);
```

❌ FALSCH:
```css
margin: 16px;
```

### 3. Verwende das Page-Header Partial
✅ RICHTIG:
```php
<?php
$page_title = 'Meine Seite';
$page_icon = 'dashicons-admin-generic';
include DIENSTPLAN_PLUGIN_PATH . 'admin/views/partials/page-header.php';
?>
```

❌ FALSCH (inline HTML):
```html
<div style="background: linear-gradient(...)">
    ...
</div>
```

### 4. Beschreibe Daten-Attribute für Dynamik
```html
<!-- Gut: Verwende data-Attribute -->
<button class="dp-btn dp-btn-primary" data-id="123" onclick="editItem(this)">
    Edit
</button>
```

---

## Weitere Ressourcen

- **Dropdown-Buttons**: Siehe `dp-admin.js` für Toggle-Funktionen
- **Modals**: Siehe `.dp-modal` Klassen in `dp-admin.css`
- **Tabellen**: Nutze `.wp-list-table` mit den modernen Styles

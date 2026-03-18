/**
 * Dienstplan Verwaltung - Admin JavaScript
 * @version 0.2.2
 */

jQuery(document).ready(function($) {
    console.log('Dienstplan Admin JS geladen');
    console.log('dpAjax verfügbar:', typeof dpAjax !== 'undefined');
});

/**
 * ===== SAFE PAGE RELOAD =====
 * Reload nur wenn keine Modals/Dialogs/Popups offen sind
 */
window.dpSafeReload = function(delay) {
    delay = delay || 3000; // Standard: 3 Sekunden (Zeit zum Lesen der Erfolgsmeldung)

    // Verhindert mehrfaches Triggern und Reload-Schleifen.
    if (window.__dpReloadScheduled) {
        return;
    }
    window.__dpReloadScheduled = true;
    
    setTimeout(function() {
        // Prüfe verschiedene Modal-Typen
        var hasOpenModal = false;
        
        // 1. Inline-Style Modals (display: block)
        var inlineModals = document.querySelectorAll('.modal, .dialog, .popup, [role="dialog"]');
        for (var i = 0; i < inlineModals.length; i++) {
            var elem = inlineModals[i];
            var style = window.getComputedStyle(elem);
            if (style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0') {
                hasOpenModal = true;
                break;
            }
        }
        
        // 2. jQuery UI Dialogs
        if (typeof jQuery !== 'undefined') {
            if (jQuery('.ui-dialog:visible').length > 0) {
                hasOpenModal = true;
            }
        }
        
        // 3. Bootstrap Modals
        if (typeof jQuery !== 'undefined') {
            if (jQuery('.modal.show, .modal.in').length > 0) {
                hasOpenModal = true;
            }
        }
        
        // 4. Custom Modal Classes
        var customModals = document.querySelectorAll('.dp-modal-open, .modal-open, .popup-open');
        if (customModals.length > 0) {
            hasOpenModal = true;
        }
        
        // Reload nur wenn keine Modals offen
        if (!hasOpenModal) {
            window.location.reload();
        } else {
            console.log('Reload unterdrückt: Modal ist geöffnet');
            window.__dpReloadScheduled = false;
        }
    }, delay);
};

/**
 * ===== DROPDOWN MENU TOGGLE =====
 */

// Toggle Aktionen-Dropdown in Vereine/Veranstaltungen
window.toggleActionDropdown = function(button, event) {
    if (event) event.stopPropagation();
    
    const dropdownContainer = button.parentElement;
    const menu = button.nextElementSibling;
    const isOpening = !menu.classList.contains('open');
    
    // Alle anderen Dropdowns schließen
    document.querySelectorAll('.action-dropdown-menu.open').forEach(m => {
        if (m !== menu) {
            m.classList.remove('open');
            if (m.parentElement) m.parentElement.classList.remove('active');
        }
    });
    
    // Aktuelles Dropdown togglen
    if (isOpening) {
        menu.classList.add('open');
        dropdownContainer.classList.add('active');
    } else {
        menu.classList.remove('open');
        dropdownContainer.classList.remove('active');
    }
};

// Toggle Aktionen-Dropdown in Dienste (dynamische IDs)
window.toggleDienstActionDropdown = function(event, dienstId) {
    if (event) event.stopPropagation();
    
    const button = event ? event.target.closest('button') : null;
    if (!button) return;
    
    const dropdownId = 'dienst-action-dropdown-' + dienstId;
    const menu = document.getElementById(dropdownId);
    if (!menu) return;
    
    const isOpening = !menu.classList.contains('open');
    
    // Alle anderen Dienst-Dropdowns schließen
    document.querySelectorAll('.dienst-action-dropdown.open').forEach(m => {
        if (m !== menu) {
            m.classList.remove('open');
        }
    });
    
    // Aktuelles Dropdown togglen
    if (isOpening) {
        menu.classList.add('open');
    } else {
        menu.classList.remove('open');
    }
};

// Dienst-Dropdown verstecken
window.hideDienstDropdown = function(dienstId) {
    const dropdownId = 'dienst-action-dropdown-' + dienstId;
    const menu = document.getElementById(dropdownId);
    if (menu) {
        menu.classList.remove('open');
    }
};

// Dropdown schließen bei Klick außerhalb
document.addEventListener('click', function(event) {
    // Für normale Dropdowns (Vereine, Veranstaltungen)
    if (!event.target.closest('.dropdown-actions')) {
        document.querySelectorAll('.action-dropdown-menu.open').forEach(menu => {
            menu.classList.remove('open');
            if (menu.parentElement) menu.parentElement.classList.remove('active');
        });
    }
    
    // Für Dienst-Dropdowns
    if (!event.target.closest('.dienst-action-button-container')) {
        document.querySelectorAll('.dienst-action-dropdown.open').forEach(menu => {
            menu.classList.remove('open');
        });
    }
});

/**
 * ===== COLLAPSE TOGGLES =====
 */

// Toggle Verein-Gruppe (nicht relevant aber mitinc zur Konsistenz)
window.toggleVereinGroup = function(id) {
    const content = document.getElementById(id);
    const icon = document.getElementById('icon-' + id);
    
    if (!content) return;
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        if (icon) icon.style.transform = 'rotate(0deg)';
    } else {
        content.style.display = 'none';
        if (icon) icon.style.transform = 'rotate(-90deg)';
    }
};

// Toggle Veranstaltung-Gruppe
window.toggleVeranstaltungGroup = function(id) {
    const content = document.getElementById(id);
    const icon = document.getElementById('icon-' + id);
    
    if (!content) return;
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        if (icon) icon.style.transform = 'rotate(0deg)';
    } else {
        content.style.display = 'none';
        if (icon) icon.style.transform = 'rotate(-90deg)';
    }
};

// Toggle Tag-Gruppe (Dienste)
window.toggleTagGroup = function(id) {
    const content = document.getElementById(id);
    const icon = document.getElementById('icon-' + id);
    
    if (!content) return;
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        if (icon) icon.style.transform = 'rotate(0deg)';
    } else {
        content.style.display = 'none';
        if (icon) icon.style.transform = 'rotate(-90deg)';
    }
};

/**
 * ===== TÄTIGKEITEN & MITARBEITER DROPDOWN TOGGLES =====
 */

// Toggle Tätigkeits-Action Dropdown
window.toggleTaetigkeitActionDropdown = function(event, taetigkeitId) {
    if (event) event.stopPropagation();
    
    const button = event ? event.target.closest('button') : null;
    if (!button) return;
    
    const dropdownId = 'taetigkeit-action-dropdown-' + taetigkeitId;
    const menu = document.getElementById(dropdownId);
    if (!menu) return;
    
    const isOpening = !menu.classList.contains('open');
    
    // Alle anderen Dropdowns schließen
    document.querySelectorAll('.taetigkeit-action-dropdown.open').forEach(m => {
        if (m !== menu) {
            m.classList.remove('open');
        }
    });
    
    // Aktuelles Dropdown togglen
    if (isOpening) {
        menu.classList.add('open');
    } else {
        menu.classList.remove('open');
    }
};

// Toggle Mitarbeiter-Action Dropdown
window.toggleMitarbeiterActionDropdown = function(event, mitarbeiterId) {
    if (event) event.stopPropagation();
    
    const button = event ? event.target.closest('button') : null;
    if (!button) return;
    
    const dropdownId = 'mitarbeiter-action-dropdown-' + mitarbeiterId;
    const menu = document.getElementById(dropdownId);
    if (!menu) return;
    
    const isOpening = !menu.classList.contains('open');
    
    // Alle anderen Dropdowns schließen
    document.querySelectorAll('.mitarbeiter-action-dropdown.open').forEach(m => {
        if (m !== menu) {
            m.classList.remove('open');
        }
    });
    
    // Aktuelles Dropdown togglen
    if (isOpening) {
        menu.classList.add('open');
    } else {
        menu.classList.remove('open');
    }
};

// Globales Event-Listener für Close-on-Click-Outside
document.addEventListener('click', function(event) {
    // Für Tätigkeits-Dropdowns
    if (!event.target.closest('.taetigkeit-row .dropdown-actions')) {
        document.querySelectorAll('.taetigkeit-action-dropdown.open').forEach(menu => {
            menu.classList.remove('open');
        });
    }
    
    // Für Mitarbeiter-Dropdowns
    if (!event.target.closest('.mitarbeiter-row .dropdown-actions')) {
        document.querySelectorAll('.mitarbeiter-action-dropdown.open').forEach(menu => {
            menu.classList.remove('open');
        });
    }
});

/**
 * ===== PORTAL PAGE MANAGEMENT (DASHBOARD) =====
 * Funktionen zum Erstellen/Löschen der Portal-Seite vom Dashboard aus
 * @since 0.6.6
 */

/**
 * Portal-Seite erstellen (vom Dashboard)
 */
window.createPortalPageFromDashboard = function(buttonElement) {
    if (!confirm('Möchtest du jetzt die Portal-Seite erstellen?\n\nDie Seite wird mit dem Titel "Dienstplan-Portal" und dem Shortcode [dienstplan_hub] veröffentlicht.')) {
        return;
    }
    
    console.log('Erstelle Portal-Seite vom Dashboard...');
    
    // Zeige Loading-Status - nutze entweder übergebenen Button oder suche ihn
    var button = buttonElement || document.querySelector('#portal-page-card button') || document.getElementById('portal-create-button');
    if (!button) {
        console.error('Button nicht gefunden');
        alert('Fehler: Button konnte nicht gefunden werden.');
        return;
    }
    var originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="dashicons dashicons-update" style="margin-top: 4px; animation: rotation 1s infinite linear;"></span> Wird erstellt...';
    
    jQuery.ajax({
        url: dpAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'dp_create_portal_page',
            nonce: dpAjax.nonce_create_portal
        },
        success: function(response) {
            console.log('Portal-Seite erstellt:', response);
            
            if (response.success) {
                alert('✓ Portal-Seite erfolgreich erstellt!\n\nDie Seite ist jetzt verfügbar und kann bearbeitet werden.');
                // Seite neu laden um aktuelle Card-Ansicht zu zeigen
                window.location.reload();
            } else {
                alert('✗ Fehler: ' + (response.data.message || 'Unbekannter Fehler'));
                button.disabled = false;
                button.innerHTML = originalText;
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX-Fehler beim Erstellen:', error);
            alert('✗ AJAX-Fehler beim Erstellen der Portal-Seite.\n\nBitte versuche es erneut oder kontaktiere den Support.');
            button.disabled = false;
            button.innerHTML = originalText;
        }
    });
};

/**
 * Portal-Seite löschen (vom Dashboard)
 */
window.deletePortalPage = function() {
    if (!confirm('⚠️ ACHTUNG: Möchtest du die Portal-Seite wirklich permanent löschen?\n\nDiese Aktion kann nicht rückgängig gemacht werden!')) {
        return;
    }
    
    console.log('Lösche Portal-Seite vom Dashboard...');
    
    // Zeige Loading-Status
    var button = document.querySelector('#portal-page-card button[onclick*="deletePortalPage"]');
    var originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="dashicons dashicons-update" style="margin-top: 4px; animation: rotation 1s infinite linear;"></span> Wird gelöscht...';
    
    jQuery.ajax({
        url: dpAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'dp_delete_portal_page',
            nonce: dpAjax.nonce_delete_portal
        },
        success: function(response) {
            console.log('Portal-Seite gelöscht:', response);
            
            if (response.success) {
                alert('✓ Portal-Seite erfolgreich gelöscht!');
                // Seite neu laden um "Erstellen"-Ansicht zu zeigen
                window.location.reload();
            } else {
                alert('✗ Fehler: ' + (response.data.message || 'Unbekannter Fehler'));
                button.disabled = false;
                button.innerHTML = originalText;
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX-Fehler beim Löschen:', error);
            alert('✗ AJAX-Fehler beim Löschen der Portal-Seite.\n\nBitte versuche es erneut oder kontaktiere den Support.');
            button.disabled = false;
            button.innerHTML = originalText;
        }
    });
};

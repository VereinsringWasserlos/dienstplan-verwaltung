/**
 * Dienstplan Verwaltung - Admin JavaScript
 * @version 0.2.1
 */

jQuery(document).ready(function($) {
    console.log('Dienstplan Admin JS geladen');
    console.log('dpAjax verfügbar:', typeof dpAjax !== 'undefined');
});

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

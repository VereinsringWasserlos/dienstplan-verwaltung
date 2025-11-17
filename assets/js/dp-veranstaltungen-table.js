(function($) {
    'use strict';
    
    // toggleActionDropdown() ist jetzt in dp-admin.js definiert
    // Dropdown schließen nach Aktion
    $(document).ready(function() {
        // CSS für Hover-Effekte
        const style = document.createElement('style');
        style.textContent = `
            .dropdown-item:hover {
                background-color: #f6f7f7 !important;
            }
            .action-dropdown-menu a:last-child {
                border-bottom: none !important;
            }
        `;
        document.head.appendChild(style);

        // Event-Listener für Dropdown-Items
        document.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', function() {
                // Dropdown nach Klick schließen
                setTimeout(() => {
                    document.querySelectorAll('.action-dropdown-menu').forEach(menu => {
                        menu.style.display = 'none';
                    });
                }, 100);
            });
        });
    });
    
})(jQuery);

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Smooth Scrolling für Timeline
        const timelineContainer = $('div[style*="overflow-x: auto"]').first();
        
        // Scroll zu aktueller Uhrzeit (falls im Zeitbereich)
        const now = new Date();
        const currentHour = now.getHours();
        const currentMinute = now.getMinutes();
        
        // Optional: Auto-scroll zu aktueller Zeit implementieren
        console.log('Timeline geladen für Dienste');
    });
    
})(jQuery);

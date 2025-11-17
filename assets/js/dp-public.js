/**
 * Public JavaScript für Dienstplan-Verwaltung Frontend
 * Modern Frontend mit Liste & Timeline Ansicht
 */

jQuery(document).ready(function($) {
    
    // ===================================
    // Accordion Toggle (neue Frontend-Views)
    // ===================================
    window.dpToggleAccordion = function(button) {
        const item = jQuery(button).closest('.dp-accordion-item');
        const content = item.find('.dp-accordion-content');
        
        // Schließe alle anderen
        jQuery('.dp-accordion-item').not(item).removeClass('active');
        jQuery('.dp-accordion-item').not(item).find('.dp-accordion-content').slideUp(300);
        
        // Toggle current
        item.toggleClass('active');
        if (item.hasClass('active')) {
            content.slideDown(300);
        } else {
            content.slideUp(300);
        }
    };
    
    // ===================================
    // Registration Modal (neue Frontend-Views)
    // ===================================
    window.dpOpenRegistrationModal = function(serviceId, serviceName) {
        var dienstIdField = document.getElementById('dpDienstId');
        if (dienstIdField) {
            dienstIdField.value = serviceId;
        }
        var modal = document.getElementById('dpRegistrationModal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    };
    
    window.dpCloseRegistrationModal = function() {
        var modal = document.getElementById('dpRegistrationModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        var form = document.getElementById('dpRegistrationForm');
        if (form) {
            form.reset();
            // Reset Split-Options
            var splitOptions = document.getElementById('dpSplitOptions');
            if (splitOptions) {
                splitOptions.style.display = 'none';
            }
        }
    };
    
    window.dpSwitchTimelineDay = function(button, dayId) {
        jQuery('.dp-timeline-tab').removeClass('active');
        jQuery(button).addClass('active');
        
        jQuery('.dp-timeline-day-container').hide();
        jQuery('[data-day-id="' + dayId + '"]').show();
    };
    
    // Timeline Scroll-Synchronisierung
    jQuery(document).ready(function($) {
        // Synchronisiere horizontales Scrollen zwischen Header und Grid
        $('.dp-timeline-grid-scroll').on('scroll', function() {
            const scrollLeft = $(this).scrollLeft();
            $('.dp-timeline-header-scroll').scrollLeft(scrollLeft);
        });
        
        $('.dp-timeline-header-scroll').on('scroll', function() {
            const scrollLeft = $(this).scrollLeft();
            $('.dp-timeline-grid-scroll').scrollLeft(scrollLeft);
        });
        
        // Synchronisiere vertikales Scrollen zwischen Left-Panel und Grid
        $('.dp-timeline-left').on('scroll', function() {
            const scrollTop = $(this).scrollTop();
            $('.dp-timeline-grid-scroll').scrollTop(scrollTop);
        });
        
        $('.dp-timeline-grid-scroll').on('scroll', function() {
            const scrollTop = $(this).scrollTop();
            $('.dp-timeline-left').scrollTop(scrollTop);
        });
    });
    
    // ===================================
    // Hilfsfunktionen
    // ===================================
    function showMessage(message, type) {
        const messageContainer = $('#dienstplan-messages');
        const messageClass = type === 'success' ? 'message-success' : 'message-error';
        const messageId = 'msg-' + Date.now();
        
        const messageHtml = `
            <div id="${messageId}" class="dienstplan-message ${messageClass}">
                ${escapeHtml(message)}
                <button type="button" class="message-close" onclick="$('#${messageId}').fadeOut()">×</button>
            </div>
        `;
        
        messageContainer.append(messageHtml).show();
        
        setTimeout(function() {
            $('#' + messageId).fadeOut(function() {
                $(this).remove();
                if (messageContainer.children().length === 0) {
                    messageContainer.hide();
                }
            });
        }, 8000);
        
        $('html, body').animate({
            scrollTop: messageContainer.offset().top - 100
        }, 500);
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) { 
            return map[m]; 
        });
    }
    
    // ===================================
    // Alte Event-Listener (Legacy Support)
    // ===================================
    
    // Dienst übernehmen
    $('.dienst-uebernehmen-btn').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const dienstId = button.data('dienst-id');
        const nameInput = button.siblings('.name-input');
        const name = nameInput.val().trim();
        
        if (!name) {
            showMessage('Bitte geben Sie Ihren Namen ein.', 'error');
            nameInput.focus();
            return;
        }
        
        if (name.length < 2) {
            showMessage('Der Name muss mindestens 2 Zeichen lang sein.', 'error');
            nameInput.focus();
            return;
        }
        
        button.prop('disabled', true).addClass('btn-loading').text('Wird übernommen...');
        nameInput.prop('disabled', true);
        
        $.ajax({
            url: dienstplan_public_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'dienstplan_assign_public',
                dienst_id: dienstId,
                name: name,
                nonce: dienstplan_public_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data, 'success');
                    const dienstKarte = button.closest('.dienst-karte');
                    dienstKarte.removeClass('dienst-frei').addClass('dienst-vergeben');
                    const statusBereich = button.closest('.dienst-status');
                    statusBereich.html('<span class="status-vergeben">✓ Übernommen von: <strong>' + escapeHtml(name) + '</strong></span>');
                    $('.name-input').not(nameInput).val('');
                } else {
                    showMessage(response.data, 'error');
                    button.prop('disabled', false).removeClass('btn-loading').text('Übernehmen');
                    nameInput.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showMessage('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'error');
                button.prop('disabled', false).removeClass('btn-loading').text('Übernehmen');
                nameInput.prop('disabled', false);
            }
        });
    });
    
    // Enter-Taste in Name-Input
    $('.name-input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $(this).siblings('.dienst-uebernehmen-btn').click();
        }
    });
    
    // Name-Input Validierung
    $('.name-input').on('input', function() {
        const input = $(this);
        const button = input.siblings('.dienst-uebernehmen-btn');
        const value = input.val().trim();
        
        if (value.length >= 2) {
            button.prop('disabled', false);
            input.removeClass('error');
        } else {
            button.prop('disabled', true);
            if (value.length > 0 && value.length < 2) {
                input.addClass('error');
            } else {
                input.removeClass('error');
            }
        }
    });
    
    // Responsive Verbesserungen
    function adjustLayout() {
        const windowWidth = $(window).width();
        if (windowWidth < 768) {
            $('.dienst-header').addClass('mobile-header');
            $('.filter-row').addClass('mobile-filter');
        } else {
            $('.dienst-header').removeClass('mobile-header');
            $('.filter-row').removeClass('mobile-filter');
        }
    }
    
    adjustLayout();
    $(window).on('resize', adjustLayout);
    
    // Keyboard Navigation
    $(document).on('keydown', function(e) {
        if (e.which === 27) {
            $('.dienstplan-message').fadeOut();
            if (typeof window.dpCloseRegistrationModal === 'function') {
                window.dpCloseRegistrationModal();
            }
        }
    });
});

// Globale Funktionen
window.DienstplanPublic = {
    assignDienst: function(dienstId, name) {
        const button = jQuery('.dienst-uebernehmen-btn[data-dienst-id="' + dienstId + '"]');
        const nameInput = button.siblings('.name-input');
        nameInput.val(name);
        button.click();
    },
    
    setFilter: function(verein, veranstaltung) {
        if (verein) jQuery('#filter_verein').val(verein);
        if (veranstaltung) jQuery('#filter_veranstaltung').val(veranstaltung);
        jQuery('.dienstplan-filter-btn').click();
    },
    
    scrollToDienst: function(dienstId) {
        const dienstKarte = jQuery('.dienst-karte[data-dienst-id="' + dienstId + '"]');
        if (dienstKarte.length) {
            jQuery('html, body').animate({
                scrollTop: dienstKarte.offset().top - 100
            }, 800);
            dienstKarte.addClass('highlight');
            setTimeout(function() {
                dienstKarte.removeClass('highlight');
            }, 2000);
        }
    },
    
    getAvailableDiensteCount: function() {
        return jQuery('.dienst-frei').length;
    },
    
    getAssignedDiensteCount: function() {
        return jQuery('.dienst-vergeben').length;
    }
};

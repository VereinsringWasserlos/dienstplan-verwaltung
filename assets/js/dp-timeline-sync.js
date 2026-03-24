/**
 * Timeline Synchrones Scrollen
 * Synchronisiert vertikales und horizontales Scrollen zwischen Timeline-Bereichen
 */

document.addEventListener('DOMContentLoaded', function() {
    const debugEnabled = Boolean(window.dpTimelineDebug);
    const log = function() {
        if (!debugEnabled) {
            return;
        }
        console.log.apply(console, arguments);
    };

    const timelineWrappers = document.querySelectorAll('.dp-timeline-wrapper');
    log('Timeline Sync: Found ' + timelineWrappers.length + ' wrapper(s)');
    
    timelineWrappers.forEach(wrapper => {
        const leftColumn = wrapper.querySelector('.dp-timeline-left');
        const rightColumn = wrapper.querySelector('.dp-timeline-right');
        
        log('Timeline Sync: Left column:', leftColumn, 'Right column:', rightColumn);
        
        if (!leftColumn || !rightColumn) {
            log('Timeline Sync: Missing columns, skipping');
            return;
        }

        log('Timeline Sync: Setting up scroll listeners');
        
        let isLeftScrolling = false;
        let isRightScrolling = false;
        
        // Linke Spalte scrollt vertikal -> Rechte Spalte folgt vertikal
        leftColumn.addEventListener('scroll', function() {
            log('Left scroll event fired, scrollTop:', leftColumn.scrollTop);
            if (isRightScrolling) return;
            
            isLeftScrolling = true;
            rightColumn.scrollTop = leftColumn.scrollTop;
            
            setTimeout(() => {
                isLeftScrolling = false;
            }, 10);
        });
        
        // Rechte Spalte scrollt (vertikal UND horizontal) -> Linke Spalte folgt nur vertikal
        rightColumn.addEventListener('scroll', function() {
            log('Right scroll event fired, scrollTop:', rightColumn.scrollTop);
            if (isLeftScrolling) return;
            
            isRightScrolling = true;
            leftColumn.scrollTop = rightColumn.scrollTop;
            
            setTimeout(() => {
                isRightScrolling = false;
            }, 10);
        });
    });
});

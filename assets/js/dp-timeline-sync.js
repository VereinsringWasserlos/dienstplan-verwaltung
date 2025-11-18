/**
 * Timeline Synchrones Scrollen
 * Synchronisiert vertikales und horizontales Scrollen zwischen Timeline-Bereichen
 */

document.addEventListener('DOMContentLoaded', function() {
    const timelineWrappers = document.querySelectorAll('.dp-timeline-wrapper');
    
    timelineWrappers.forEach(wrapper => {
        const leftColumn = wrapper.querySelector('.dp-timeline-left');
        const rightColumn = wrapper.querySelector('.dp-timeline-right');
        
        if (!leftColumn || !rightColumn) return;
        
        let isLeftScrolling = false;
        let isRightScrolling = false;
        
        // Linke Spalte scrollt vertikal -> Rechte Spalte folgt vertikal
        leftColumn.addEventListener('scroll', function() {
            if (isRightScrolling) return;
            
            isLeftScrolling = true;
            rightColumn.scrollTop = leftColumn.scrollTop;
            
            setTimeout(() => {
                isLeftScrolling = false;
            }, 10);
        });
        
        // Rechte Spalte scrollt (vertikal UND horizontal) -> Linke Spalte folgt nur vertikal
        rightColumn.addEventListener('scroll', function() {
            if (isLeftScrolling) return;
            
            isRightScrolling = true;
            leftColumn.scrollTop = rightColumn.scrollTop;
            
            setTimeout(() => {
                isRightScrolling = false;
            }, 10);
        });
    });
});

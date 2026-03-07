/**
 * Timeline Synchrones Scrollen
 * Synchronisiert vertikales und horizontales Scrollen zwischen Timeline-Bereichen
 */

document.addEventListener('DOMContentLoaded', function() {
    const timelineWrappers = document.querySelectorAll('.dp-timeline-wrapper');
    
    console.log('Timeline Sync: Found ' + timelineWrappers.length + ' wrapper(s)');
    
    timelineWrappers.forEach(wrapper => {
        const leftColumn = wrapper.querySelector('.dp-timeline-left');
        const rightColumn = wrapper.querySelector('.dp-timeline-right');
        
        console.log('Timeline Sync: Left column:', leftColumn, 'Right column:', rightColumn);
        
        if (!leftColumn || !rightColumn) {
            console.log('Timeline Sync: Missing columns, skipping');
            return;
        }
        
        console.log('Timeline Sync: Setting up scroll listeners');
        
        console.log('Timeline Sync: Setting up scroll listeners');
        
        let isLeftScrolling = false;
        let isRightScrolling = false;
        
        // Linke Spalte scrollt vertikal -> Rechte Spalte folgt vertikal
        leftColumn.addEventListener('scroll', function() {
            console.log('Left scroll event fired, scrollTop:', leftColumn.scrollTop);
            if (isRightScrolling) return;
            
            isLeftScrolling = true;
            rightColumn.scrollTop = leftColumn.scrollTop;
            
            setTimeout(() => {
                isLeftScrolling = false;
            }, 10);
        });
        
        // Rechte Spalte scrollt (vertikal UND horizontal) -> Linke Spalte folgt nur vertikal
        rightColumn.addEventListener('scroll', function() {
            console.log('Right scroll event fired, scrollTop:', rightColumn.scrollTop);
            if (isLeftScrolling) return;
            
            isRightScrolling = true;
            leftColumn.scrollTop = rightColumn.scrollTop;
            
            setTimeout(() => {
                isRightScrolling = false;
            }, 10);
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Make spans in the tree view clickable
    const treeSpans = document.querySelectorAll('.tree span, .tree .tree-item-span');
    
    treeSpans.forEach(span => {
        span.addEventListener('click', function(e) {
            // Find the collapse link within this span
            const collapseLink = this.querySelector('a[data-toggle="collapse"]');
            
            // If the click wasn't directly on the link, trigger the link
            if (collapseLink && e.target !== collapseLink && !e.target.closest('.rightfloatet') && !e.target.closest('.treeIcon')) {
                // Find the collapse target
                const target = document.querySelector(collapseLink.getAttribute('href'));
                if (target) {
                    // Toggle the collapse state using Bootstrap's API
                    $(collapseLink.getAttribute('href')).collapse('toggle');
                    
                    // Update the aria-expanded attribute
                    const expanded = collapseLink.getAttribute('aria-expanded') === 'true';
                    collapseLink.setAttribute('aria-expanded', !expanded);
                }
            }
        });
    });
}); 
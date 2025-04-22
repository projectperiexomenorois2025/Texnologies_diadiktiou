/**
 * Accordion functionality
 * Used on the home page and help page
 */
document.addEventListener('DOMContentLoaded', function() {
    const accordions = document.querySelectorAll('.accordion');
    
    accordions.forEach(accordion => {
        const header = accordion.querySelector('.accordion-header');
        
        header.addEventListener('click', () => {
            // Close all other accordions
            accordions.forEach(item => {
                if (item !== accordion && item.classList.contains('active')) {
                    item.classList.remove('active');
                }
            });
            
            // Toggle current accordion
            accordion.classList.toggle('active');
        });
    });
    
    // Open first accordion by default
    if (accordions.length > 0) {
        accordions[0].classList.add('active');
    }
});

/**
 * Theme toggler functionality
 * Handles switching between light and dark themes
 */
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;
    
    // Check for saved theme preference or use default light theme
    const savedTheme = getCookie('theme') || 'light';
    body.classList.add(`${savedTheme}-theme`);
    
    // Toggle theme when button is clicked
    themeToggle.addEventListener('click', function() {
        if (body.classList.contains('light-theme')) {
            body.classList.replace('light-theme', 'dark-theme');
            setCookie('theme', 'dark', 365);
        } else {
            body.classList.replace('dark-theme', 'light-theme');
            setCookie('theme', 'light', 365);
        }
    });
    
    /**
     * Set a cookie with the given name, value and expiry days
     */
    function setCookie(name, value, days) {
        let expires = '';
        
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        
        document.cookie = name + '=' + value + expires + '; path=/; SameSite=Lax';
    }
    
    /**
     * Get cookie value by name
     */
    function getCookie(name) {
        const nameEQ = name + '=';
        const ca = document.cookie.split(';');
        
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') {
                c = c.substring(1, c.length);
            }
            if (c.indexOf(nameEQ) === 0) {
                return c.substring(nameEQ.length, c.length);
            }
        }
        
        return null;
    }
});

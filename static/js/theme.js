/**
 * Theme toggler functionality
 * Handles switching between light and dark themes
 */
document.addEventListener('DOMContentLoaded', function() {
    const themeToggleBtn = document.getElementById('theme-toggle-btn');
    const themeIcon = themeToggleBtn.querySelector('i');
    
    // Check if user has a theme preference saved
    const currentTheme = getCookie('theme') || 'light';
    
    // Apply theme on page load
    applyTheme(currentTheme);
    
    // Toggle theme when button is clicked
    themeToggleBtn.addEventListener('click', function() {
        const body = document.body;
        const isDarkTheme = body.classList.contains('dark-theme');
        
        // Toggle theme
        if (isDarkTheme) {
            applyTheme('light');
            setCookie('theme', 'light', 365);
        } else {
            applyTheme('dark');
            setCookie('theme', 'dark', 365);
        }
    });
    
    /**
     * Apply the specified theme to the document
     * @param {string} theme - 'light' or 'dark'
     */
    function applyTheme(theme) {
        const body = document.body;
        const isDark = theme === 'dark';
        
        if (isDark) {
            body.classList.add('dark-theme');
            themeIcon.className = 'fas fa-sun';
        } else {
            body.classList.remove('dark-theme');
            themeIcon.className = 'fas fa-moon';
        }
    }
    
    /**
     * Set a cookie with the given name, value and expiry days
     */
    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "")  + expires + "; path=/";
    }
    
    /**
     * Get cookie value by name
     */
    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for(let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
});
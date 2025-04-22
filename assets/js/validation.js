/**
 * Form validation functions for client-side validation
 */
document.addEventListener('DOMContentLoaded', function() {
    // Registration form validation
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', validateRegisterForm);
    }
    
    // Login form validation
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', validateLoginForm);
    }
    
    // Profile edit form validation
    const profileForm = document.getElementById('profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', validateProfileForm);
    }
    
    // Create playlist form validation
    const playlistForm = document.getElementById('playlist-form');
    if (playlistForm) {
        playlistForm.addEventListener('submit', validatePlaylistForm);
    }
    
    // Search form validation
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', validateSearchForm);
    }
});

/**
 * Validate registration form
 */
function validateRegisterForm(event) {
    const form = event.target;
    const username = form.querySelector('input[name="username"]').value.trim();
    const firstName = form.querySelector('input[name="first_name"]').value.trim();
    const lastName = form.querySelector('input[name="last_name"]').value.trim();
    const email = form.querySelector('input[name="email"]').value.trim();
    const password = form.querySelector('input[name="password"]').value;
    const confirmPassword = form.querySelector('input[name="confirm_password"]').value;
    
    let isValid = true;
    clearErrors(form);
    
    // Username validation
    if (username === '') {
        showError(form, 'username', 'Username is required');
        isValid = false;
    } else if (username.length < 3) {
        showError(form, 'username', 'Username must be at least 3 characters');
        isValid = false;
    }
    
    // First name validation
    if (firstName === '') {
        showError(form, 'first_name', 'First name is required');
        isValid = false;
    }
    
    // Last name validation
    if (lastName === '') {
        showError(form, 'last_name', 'Last name is required');
        isValid = false;
    }
    
    // Email validation
    if (email === '') {
        showError(form, 'email', 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email)) {
        showError(form, 'email', 'Please enter a valid email address');
        isValid = false;
    }
    
    // Password validation
    if (password === '') {
        showError(form, 'password', 'Password is required');
        isValid = false;
    } else if (password.length < 6) {
        showError(form, 'password', 'Password must be at least 6 characters');
        isValid = false;
    }
    
    // Confirm password validation
    if (confirmPassword === '') {
        showError(form, 'confirm_password', 'Please confirm your password');
        isValid = false;
    } else if (password !== confirmPassword) {
        showError(form, 'confirm_password', 'Passwords do not match');
        isValid = false;
    }
    
    if (!isValid) {
        event.preventDefault();
    }
}

/**
 * Validate login form
 */
function validateLoginForm(event) {
    const form = event.target;
    const username = form.querySelector('input[name="username"]').value.trim();
    const password = form.querySelector('input[name="password"]').value;
    
    let isValid = true;
    clearErrors(form);
    
    if (username === '') {
        showError(form, 'username', 'Username is required');
        isValid = false;
    }
    
    if (password === '') {
        showError(form, 'password', 'Password is required');
        isValid = false;
    }
    
    if (!isValid) {
        event.preventDefault();
    }
}

/**
 * Validate profile edit form
 */
function validateProfileForm(event) {
    const form = event.target;
    const firstName = form.querySelector('input[name="first_name"]').value.trim();
    const lastName = form.querySelector('input[name="last_name"]').value.trim();
    const email = form.querySelector('input[name="email"]').value.trim();
    const currentPassword = form.querySelector('input[name="current_password"]').value;
    const newPassword = form.querySelector('input[name="new_password"]').value;
    const confirmPassword = form.querySelector('input[name="confirm_password"]').value;
    
    let isValid = true;
    clearErrors(form);
    
    // First name validation
    if (firstName === '') {
        showError(form, 'first_name', 'First name is required');
        isValid = false;
    }
    
    // Last name validation
    if (lastName === '') {
        showError(form, 'last_name', 'Last name is required');
        isValid = false;
    }
    
    // Email validation
    if (email === '') {
        showError(form, 'email', 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email)) {
        showError(form, 'email', 'Please enter a valid email address');
        isValid = false;
    }
    
    // Current password validation
    if (currentPassword === '') {
        showError(form, 'current_password', 'Current password is required to make changes');
        isValid = false;
    }
    
    // New password and confirm password validation (only if new password is provided)
    if (newPassword !== '') {
        if (newPassword.length < 6) {
            showError(form, 'new_password', 'New password must be at least 6 characters');
            isValid = false;
        }
        
        if (confirmPassword === '') {
            showError(form, 'confirm_password', 'Please confirm your new password');
            isValid = false;
        } else if (newPassword !== confirmPassword) {
            showError(form, 'confirm_password', 'Passwords do not match');
            isValid = false;
        }
    }
    
    if (!isValid) {
        event.preventDefault();
    }
}

/**
 * Validate playlist creation/edit form
 */
function validatePlaylistForm(event) {
    const form = event.target;
    const title = form.querySelector('input[name="title"]').value.trim();
    
    let isValid = true;
    clearErrors(form);
    
    if (title === '') {
        showError(form, 'title', 'Playlist title is required');
        isValid = false;
    }
    
    if (!isValid) {
        event.preventDefault();
    }
}

/**
 * Validate search form
 */
function validateSearchForm(event) {
    const form = event.target;
    const query = form.querySelector('input[name="query"]').value.trim();
    const startDate = form.querySelector('input[name="start_date"]').value;
    const endDate = form.querySelector('input[name="end_date"]').value;
    
    // Dates validation if both dates are provided
    if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
        showError(form, 'end_date', 'End date must be after start date');
        event.preventDefault();
    }
}

/**
 * Helper function to show error message
 */
function showError(form, fieldName, message) {
    const field = form.querySelector(`[name="${fieldName}"]`);
    const errorElement = document.createElement('div');
    errorElement.className = 'error';
    errorElement.textContent = message;
    
    field.classList.add('is-invalid');
    field.parentNode.appendChild(errorElement);
}

/**
 * Helper function to clear all error messages
 */
function clearErrors(form) {
    form.querySelectorAll('.error').forEach(error => error.remove());
    form.querySelectorAll('.is-invalid').forEach(field => field.classList.remove('is-invalid'));
}

/**
 * Helper function to validate email format
 */
function isValidEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

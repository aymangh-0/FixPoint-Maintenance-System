function initPasswordStrength() {
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    const strengthContainer = document.getElementById('passwordStrength');
    
    if (!passwordInput || !strengthBar) return; // Exit if elements don't exist
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        
        if (password.length === 0) {
            strengthContainer.style.display = 'none';
            strengthText.textContent = '';
            return;
        }
        
        strengthContainer.style.display = 'block';
        
        let strength = 0;
        
        // Length check
        if (password.length >= 6) strength++;
        if (password.length >= 10) strength++;
        
        // Character variety
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        
        // Update UI
        strengthBar.className = 'password-strength-bar';
        
        if (strength <= 2) {
            strengthBar.classList.add('password-strength-weak');
            strengthText.textContent = 'Weak password';
            strengthText.style.color = '#ef4444';
        } else if (strength <= 4) {
            strengthBar.classList.add('password-strength-medium');
            strengthText.textContent = 'Medium strength';
            strengthText.style.color = '#f59e0b';
        } else {
            strengthBar.classList.add('password-strength-strong');
            strengthText.textContent = 'Strong password';
            strengthText.style.color = '#10b981';
        }
    });
}

// Password match validation for registration
function initPasswordMatch() {
    const passwordInput = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const form = document.getElementById('registerForm');
    
    if (!form || !passwordInput || !confirmPassword) return; // Exit if elements don't exist
    
    form.addEventListener('submit', function(e) {
        if (passwordInput.value !== confirmPassword.value) {
            e.preventDefault();
            alert('❌ Passwords do not match!');
            confirmPassword.focus();
        }
    });
}

// Show/hide password toggle
function initPasswordToggle() {
    const toggleButtons = document.querySelectorAll('.password-toggle');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            
            if (input.type === 'password') {
                input.type = 'text';
                this.textContent = '🙈';
            } else {
                input.type = 'password';
                this.textContent = '👁️';
            }
        });
    });
}

// Email validation (real-time)
function initEmailValidation() {
    const emailInput = document.getElementById('email');
    
    if (!emailInput) return;
    
    emailInput.addEventListener('blur', function() {
        const email = this.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            this.style.borderColor = '#ef4444';
            showFieldError(this, 'Please enter a valid email address');
        } else {
            this.style.borderColor = '#e2e8f0';
            hideFieldError(this);
        }
    });
}

// Show field error message
function showFieldError(field, message) {
    let errorDiv = field.parentElement.querySelector('.field-error');
    
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.color = '#ef4444';
        errorDiv.style.fontSize = '0.875rem';
        errorDiv.style.marginTop = '0.25rem';
        field.parentElement.appendChild(errorDiv);
    }
    
    errorDiv.textContent = message;
}

// Hide field error message
function hideFieldError(field) {
    const errorDiv = field.parentElement.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// Initialize all auth functions when page loads
document.addEventListener('DOMContentLoaded', function() {
    initPasswordStrength();
    initPasswordMatch();
    initPasswordToggle();
    initEmailValidation();
});
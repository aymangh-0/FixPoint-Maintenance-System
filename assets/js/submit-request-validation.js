/**
 * FixPoint - Submit Request Form Validation (Client-Side)
 * Add this script before </body> in user/submit-request.php
 * 
 * Validates: location, category, priority, title, description, photo
 */

document.addEventListener('DOMContentLoaded', function() {
    
    const form = document.querySelector('form[method="POST"]');
    if (!form) return;
    
    const locationSelect = document.getElementById('location_id');
    const categorySelect = document.getElementById('category_id');
    const prioritySelect = document.getElementById('priority_id');
    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');
    const photoInput = document.getElementById('photo');
    
    // ========================
    // Real-time validation
    // ========================
    
    // Title: Show character count + validate length
    if (titleInput) {
        // Add character counter
        const counter = document.createElement('small');
        counter.style.cssText = 'color: #94a3b8; font-size: 0.8rem; float: right;';
        counter.id = 'titleCounter';
        counter.textContent = '0 / 200';
        titleInput.parentElement.appendChild(counter);
        
        titleInput.addEventListener('input', function() {
            const len = this.value.length;
            counter.textContent = len + ' / 200';
            
            if (len > 180) {
                counter.style.color = '#ef4444';
            } else if (len > 150) {
                counter.style.color = '#f59e0b';
            } else {
                counter.style.color = '#94a3b8';
            }
            
            if (len >= 10) {
                clearFieldError(this);
            }
        });
        
        titleInput.addEventListener('blur', function() {
            if (this.value.trim().length > 0 && this.value.trim().length < 10) {
                showFieldError(this, 'Title must be at least 10 characters long');
            } else if (this.value.trim().length >= 10) {
                clearFieldError(this);
            }
        });
    }
    
    // Description: Show character count + min length check
    if (descriptionInput) {
        const counter = document.createElement('small');
        counter.style.cssText = 'color: #94a3b8; font-size: 0.8rem; float: right;';
        counter.id = 'descCounter';
        counter.textContent = '0 characters';
        descriptionInput.parentElement.appendChild(counter);
        
        descriptionInput.addEventListener('input', function() {
            const len = this.value.length;
            counter.textContent = len + ' characters';
            
            if (len >= 20) {
                clearFieldError(this);
                counter.style.color = '#10b981';
            } else {
                counter.style.color = '#94a3b8';
            }
        });
        
        descriptionInput.addEventListener('blur', function() {
            if (this.value.trim().length > 0 && this.value.trim().length < 20) {
                showFieldError(this, 'Description must be at least 20 characters. Please provide more detail.');
            } else if (this.value.trim().length >= 20) {
                clearFieldError(this);
            }
        });
    }
    
    // Photo: Validate file type and size
    if (photoInput) {
        photoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            
            // Check file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                showFieldError(this, 'Invalid file type. Only JPG, PNG, and GIF are allowed.');
                this.value = '';
                return;
            }
            
            // Check file size (20MB)
            const maxSize = 20 * 1024 * 1024;
            if (file.size > maxSize) {
                showFieldError(this, 'File is too large. Maximum size is 20MB. Your file: ' + (file.size / (1024 * 1024)).toFixed(1) + 'MB');
                this.value = '';
                return;
            }
            
            clearFieldError(this);
            
            // Show file preview
            showImagePreview(file, this);
        });
    }
    
    // Select fields: Clear error on change
    [locationSelect, categorySelect, prioritySelect].forEach(function(select) {
        if (select) {
            select.addEventListener('change', function() {
                if (this.value !== '' && this.value !== '0') {
                    clearFieldError(this);
                }
            });
        }
    });
    
    // ========================
    // Form submission validation
    // ========================
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        let firstError = null;
        
        // Validate Location
        if (locationSelect && (locationSelect.value === '' || locationSelect.value === '0')) {
            showFieldError(locationSelect, 'Please select a location');
            isValid = false;
            if (!firstError) firstError = locationSelect;
        }
        
        // Validate Category
        if (categorySelect && (categorySelect.value === '' || categorySelect.value === '0')) {
            showFieldError(categorySelect, 'Please select a category');
            isValid = false;
            if (!firstError) firstError = categorySelect;
        }
        
        // Validate Title (min 10 chars)
        if (titleInput) {
            const titleVal = titleInput.value.trim();
            if (titleVal === '') {
                showFieldError(titleInput, 'Title is required');
                isValid = false;
                if (!firstError) firstError = titleInput;
            } else if (titleVal.length < 10) {
                showFieldError(titleInput, 'Title must be at least 10 characters long');
                isValid = false;
                if (!firstError) firstError = titleInput;
            }
        }
        
        // Validate Description (min 20 chars)
        if (descriptionInput) {
            const descVal = descriptionInput.value.trim();
            if (descVal === '') {
                showFieldError(descriptionInput, 'Description is required');
                isValid = false;
                if (!firstError) firstError = descriptionInput;
            } else if (descVal.length < 20) {
                showFieldError(descriptionInput, 'Please provide more detail (at least 20 characters)');
                isValid = false;
                if (!firstError) firstError = descriptionInput;
            }
        }
        
        // If invalid, prevent submission and scroll to first error
        if (!isValid) {
            e.preventDefault();
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
        }
    });
    
    // ========================
    // Helper functions
    // ========================
    
    function showFieldError(field, message) {
        clearFieldError(field);
        
        field.style.borderColor = '#ef4444';
        field.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error-msg';
        errorDiv.style.cssText = 'color: #ef4444; font-size: 0.8rem; margin-top: 0.25rem; display: flex; align-items: center; gap: 0.25rem;';
        errorDiv.innerHTML = '⚠️ ' + message;
        
        field.parentElement.appendChild(errorDiv);
    }
    
    function clearFieldError(field) {
        field.style.borderColor = '';
        field.style.boxShadow = '';
        
        const existingError = field.parentElement.querySelector('.field-error-msg');
        if (existingError) {
            existingError.remove();
        }
    }
    
    function showImagePreview(file, input) {
        // Remove existing preview
        const existingPreview = input.parentElement.querySelector('.image-preview');
        if (existingPreview) existingPreview.remove();
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.className = 'image-preview';
            preview.style.cssText = 'margin-top: 0.75rem; padding: 0.75rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.5rem; display: flex; align-items: center; gap: 0.75rem;';
            preview.innerHTML = 
                '<img src="' + e.target.result + '" style="width: 60px; height: 60px; object-fit: cover; border-radius: 0.375rem; border: 1px solid #d1d5db;">' +
                '<div>' +
                    '<div style="font-weight: 600; color: #166534; font-size: 0.85rem;">✅ ' + file.name + '</div>' +
                    '<div style="color: #64748b; font-size: 0.8rem;">' + (file.size / (1024 * 1024)).toFixed(2) + ' MB</div>' +
                '</div>';
            
            input.parentElement.appendChild(preview);
        };
        reader.readAsDataURL(file);
    }
    
});
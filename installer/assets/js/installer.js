// Phoenix AI Installer JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('Phoenix AI Installer loaded');
    
    // Initialize installer
    initializeInstaller();
    
    // Add form validation
    setupFormValidation();
    
    // Add interactive features
    setupInteractiveFeatures();
});

function initializeInstaller() {
    // Add loading states to forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="loading"></span> Processing...';
            }
        });
    });
    
    // Auto-focus first input
    const firstInput = document.querySelector('input:not([type="hidden"])');
    if (firstInput) {
        firstInput.focus();
    }
}

function setupFormValidation() {
    // Real-time validation for email fields
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateEmail(this);
        });
        
        input.addEventListener('input', function() {
            clearValidationError(this);
        });
    });
    
    // Password strength validation
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.name === 'admin_password') {
                validatePasswordStrength(this);
            }
        });
    });
    
    // Database connection test
    const dbForm = document.querySelector('#database-form');
    if (dbForm) {
        const testBtn = document.createElement('button');
        testBtn.type = 'button';
        testBtn.className = 'btn btn-secondary';
        testBtn.textContent = 'Test Connection';
        testBtn.onclick = testDatabaseConnection;
        
        const submitBtn = dbForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.parentNode.insertBefore(testBtn, submitBtn);
        }
    }
}

function setupInteractiveFeatures() {
    // Add tooltips
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
    
    // Add copy to clipboard functionality
    const copyButtons = document.querySelectorAll('[data-copy]');
    copyButtons.forEach(button => {
        button.addEventListener('click', copyToClipboard);
    });
    
    // Add progress animation
    animateProgress();
}

function validateEmail(input) {
    const email = input.value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email && !emailRegex.test(email)) {
        showValidationError(input, 'Please enter a valid email address');
        return false;
    }
    
    clearValidationError(input);
    return true;
}

function validatePasswordStrength(input) {
    const password = input.value;
    const strengthIndicator = document.getElementById('password-strength');
    
    if (!strengthIndicator) {
        createPasswordStrengthIndicator(input);
    }
    
    const strength = calculatePasswordStrength(password);
    updatePasswordStrengthIndicator(strength);
    
    return strength >= 3; // Require medium strength
}

function calculatePasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    return Math.min(strength, 5);
}

function createPasswordStrengthIndicator(input) {
    const indicator = document.createElement('div');
    indicator.id = 'password-strength';
    indicator.className = 'password-strength-indicator';
    indicator.innerHTML = `
        <div class="strength-bar">
            <div class="strength-fill"></div>
        </div>
        <div class="strength-text">Password strength: <span class="strength-level">Weak</span></div>
    `;
    
    input.parentNode.appendChild(indicator);
    
    // Add CSS for password strength indicator
    const style = document.createElement('style');
    style.textContent = `
        .password-strength-indicator {
            margin-top: 10px;
        }
        .strength-bar {
            width: 100%;
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 3px;
        }
        .strength-text {
            font-size: 0.85rem;
            color: #6b7280;
        }
        .strength-level {
            font-weight: 600;
        }
    `;
    document.head.appendChild(style);
}

function updatePasswordStrengthIndicator(strength) {
    const fill = document.querySelector('.strength-fill');
    const level = document.querySelector('.strength-level');
    
    if (!fill || !level) return;
    
    const colors = ['#ef4444', '#f59e0b', '#f59e0b', '#10B981', '#10B981', '#059669'];
    const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
    const widths = ['20%', '40%', '60%', '80%', '90%', '100%'];
    
    fill.style.background = colors[strength] || colors[0];
    fill.style.width = widths[strength] || widths[0];
    level.textContent = labels[strength] || labels[0];
    level.style.color = colors[strength] || colors[0];
}

function testDatabaseConnection() {
    const form = document.querySelector('#database-form');
    const formData = new FormData(form);
    const testBtn = event.target;
    
    testBtn.disabled = true;
    testBtn.innerHTML = '<span class="loading"></span> Testing...';
    
    // Create test request
    fetch('?step=3&action=test', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const resultDiv = document.getElementById('db-test-result') || createDbTestResult();
        
        if (data.success) {
            resultDiv.className = 'database-test success';
            resultDiv.innerHTML = `
                <h4>✅ Database Connection Successful</h4>
                <p>Successfully connected to the database server.</p>
            `;
        } else {
            resultDiv.className = 'database-test error';
            resultDiv.innerHTML = `
                <h4>❌ Database Connection Failed</h4>
                <p>${data.error || 'Could not connect to the database'}</p>
            `;
        }
    })
    .catch(error => {
        const resultDiv = document.getElementById('db-test-result') || createDbTestResult();
        resultDiv.className = 'database-test error';
        resultDiv.innerHTML = `
            <h4>❌ Connection Test Failed</h4>
            <p>Network error: ${error.message}</p>
        `;
    })
    .finally(() => {
        testBtn.disabled = false;
        testBtn.textContent = 'Test Connection';
    });
}

function createDbTestResult() {
    const div = document.createElement('div');
    div.id = 'db-test-result';
    div.className = 'database-test';
    
    const form = document.querySelector('#database-form');
    form.appendChild(div);
    
    return div;
}

function showValidationError(input, message) {
    clearValidationError(input);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'validation-error';
    errorDiv.textContent = message;
    errorDiv.style.cssText = `
        color: #ef4444;
        font-size: 0.85rem;
        margin-top: 5px;
        font-weight: 500;
    `;
    
    input.parentNode.appendChild(errorDiv);
    input.style.borderColor = '#ef4444';
}

function clearValidationError(input) {
    const errorDiv = input.parentNode.querySelector('.validation-error');
    if (errorDiv) {
        errorDiv.remove();
    }
    input.style.borderColor = '';
}

function showTooltip(event) {
    const element = event.target;
    const tooltipText = element.getAttribute('data-tooltip');
    
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = tooltipText;
    tooltip.style.cssText = `
        position: absolute;
        background: #1a202c;
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 0.85rem;
        z-index: 1000;
        pointer-events: none;
        white-space: nowrap;
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
}

function hideTooltip(event) {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

function copyToClipboard(event) {
    const button = event.target;
    const textToCopy = button.getAttribute('data-copy');
    
    navigator.clipboard.writeText(textToCopy).then(() => {
        const originalText = button.textContent;
        button.textContent = 'Copied!';
        button.style.background = '#10B981';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.background = '';
        }, 2000);
    });
}

function animateProgress() {
    const currentStep = document.querySelector('.step.active');
    if (currentStep) {
        const stepNumber = parseInt(currentStep.querySelector('.step-number').textContent);
        const progressPercentage = ((stepNumber - 1) / 5) * 100;
        
        // Create progress bar if it doesn't exist
        let progressBar = document.querySelector('.installer-progress-bar');
        if (!progressBar) {
            progressBar = document.createElement('div');
            progressBar.className = 'installer-progress-bar';
            progressBar.innerHTML = '<div class="installation-progress-fill"></div>';
            
            const progressContainer = document.querySelector('.installer-progress');
            if (progressContainer) {
                progressContainer.appendChild(progressBar);
            }
        }
        
        const progressFill = progressBar.querySelector('.installation-progress-fill');
        if (progressFill) {
            setTimeout(() => {
                progressFill.style.width = progressPercentage + '%';
            }, 300);
        }
    }
}

// Auto-refresh installation log
function startInstallationMonitoring() {
    const logContainer = document.querySelector('.installation-log');
    if (logContainer) {
        const interval = setInterval(() => {
            // Check if installation is complete
            if (document.querySelector('.installation-complete')) {
                clearInterval(interval);
                return;
            }
            
            // Auto-scroll to bottom
            logContainer.scrollTop = logContainer.scrollHeight;
        }, 1000);
    }
}

// Form auto-save
function setupAutoSave() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());
                localStorage.setItem('installer_form_data', JSON.stringify(data));
            });
        });
    });
    
    // Restore form data
    const savedData = localStorage.getItem('installer_form_data');
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(key => {
                const input = document.querySelector(`[name="${key}"]`);
                if (input && input.type !== 'password') {
                    input.value = data[key];
                }
            });
        } catch (e) {
            console.warn('Could not restore form data:', e);
        }
    }
}

// Initialize auto-save
setupAutoSave();

// Start installation monitoring if on installation step
if (window.location.search.includes('step=5')) {
    startInstallationMonitoring();
}
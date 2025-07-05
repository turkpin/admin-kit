<h2 style="margin-bottom: 1.5rem; color: #374151;">Admin User Setup</h2>
<p style="color: #6b7280; margin-bottom: 2rem;">
    Create your administrator account. This user will have full access to all AdminKit features.
</p>

<form method="POST">
    <div class="form-group">
        <label for="admin_name">Full Name</label>
        <input type="text" id="admin_name" name="admin_name" value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>" required>
        <small style="color: #6b7280;">Your full name for display purposes</small>
    </div>
    
    <div class="form-group">
        <label for="admin_email">Email Address</label>
        <input type="email" id="admin_email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
        <small style="color: #6b7280;">This will be your login username</small>
    </div>
    
    <div class="grid">
        <div class="form-group">
            <label for="admin_password">Password</label>
            <input type="password" id="admin_password" name="admin_password" required minlength="8">
            <small style="color: #6b7280;">Must be at least 8 characters long</small>
        </div>
        
        <div class="form-group">
            <label for="admin_password_confirm">Confirm Password</label>
            <input type="password" id="admin_password_confirm" name="admin_password_confirm" required minlength="8">
            <small style="color: #6b7280;">Re-enter your password</small>
        </div>
    </div>
    
    <div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; padding: 1rem; margin: 1.5rem 0;">
        <h4 style="margin-bottom: 0.5rem; color: #92400e;">Security Recommendations:</h4>
        <ul style="margin-left: 1.5rem; color: #a16207; font-size: 0.875rem;">
            <li>Use a strong password with mixed case, numbers, and symbols</li>
            <li>Don't use the same password for other accounts</li>
            <li>Consider enabling two-factor authentication after installation</li>
            <li>Keep your email address secure as it's used for password resets</li>
        </ul>
    </div>
    
    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
        <a href="?step=database" class="btn btn-secondary">← Back</a>
        <button type="submit" class="btn">Create Admin & Complete Setup →</button>
    </div>
</form>

<script>
// Password strength indicator and validation
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('admin_password');
    const passwordConfirm = document.getElementById('admin_password_confirm');
    
    // Add password strength indicator
    const strengthIndicator = document.createElement('div');
    strengthIndicator.style.cssText = 'margin-top: 0.5rem; font-size: 0.875rem;';
    password.parentNode.appendChild(strengthIndicator);
    
    function checkPasswordStrength(pwd) {
        let strength = 0;
        let feedback = [];
        
        if (pwd.length >= 8) strength++;
        else feedback.push('At least 8 characters');
        
        if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) strength++;
        else feedback.push('Mixed case letters');
        
        if (/\d/.test(pwd)) strength++;
        else feedback.push('Numbers');
        
        if (/[^a-zA-Z0-9]/.test(pwd)) strength++;
        else feedback.push('Special characters');
        
        const levels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
        const colors = ['#dc2626', '#f59e0b', '#f59e0b', '#10b981', '#059669'];
        
        return {
            level: levels[Math.min(strength, 4)],
            color: colors[Math.min(strength, 4)],
            feedback: feedback
        };
    }
    
    password.addEventListener('input', function() {
        const result = checkPasswordStrength(this.value);
        strengthIndicator.innerHTML = `
            <span style="color: ${result.color}; font-weight: 600;">
                Password Strength: ${result.level}
            </span>
            ${result.feedback.length > 0 ? '<br><span style="color: #6b7280;">Missing: ' + result.feedback.join(', ') + '</span>' : ''}
        `;
    });
    
    // Password confirmation validation
    function validatePasswordMatch() {
        if (passwordConfirm.value && password.value !== passwordConfirm.value) {
            passwordConfirm.setCustomValidity('Passwords do not match');
        } else {
            passwordConfirm.setCustomValidity('');
        }
    }
    
    password.addEventListener('input', validatePasswordMatch);
    passwordConfirm.addEventListener('input', validatePasswordMatch);
});
</script>

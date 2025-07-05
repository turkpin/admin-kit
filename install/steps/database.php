<h2 style="margin-bottom: 1.5rem; color: #374151;">Database Configuration</h2>
<p style="color: #6b7280; margin-bottom: 2rem;">
    Please provide your database connection details. AdminKit will test the connection and create the database if it doesn't exist.
</p>

<form method="POST">
    <div class="grid">
        <div class="form-group">
            <label for="db_host">Database Host</label>
            <input type="text" id="db_host" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
            <small style="color: #6b7280;">Usually localhost or 127.0.0.1</small>
        </div>
        
        <div class="form-group">
            <label for="db_port">Database Port</label>
            <input type="number" id="db_port" name="db_port" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>" required>
            <small style="color: #6b7280;">Default MySQL port is 3306</small>
        </div>
    </div>
    
    <div class="form-group">
        <label for="db_database">Database Name</label>
        <input type="text" id="db_database" name="db_database" value="<?= htmlspecialchars($_POST['db_database'] ?? 'adminkit') ?>" required>
        <small style="color: #6b7280;">Will be created if it doesn't exist</small>
    </div>
    
    <div class="grid">
        <div class="form-group">
            <label for="db_username">Database Username</label>
            <input type="text" id="db_username" name="db_username" value="<?= htmlspecialchars($_POST['db_username'] ?? 'root') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="db_password">Database Password</label>
            <input type="password" id="db_password" name="db_password" value="<?= htmlspecialchars($_POST['db_password'] ?? '') ?>">
            <small style="color: #6b7280;">Leave empty if no password</small>
        </div>
    </div>
    
    <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 1rem; margin: 1.5rem 0;">
        <h4 style="margin-bottom: 0.5rem; color: #0284c7;">Database Requirements:</h4>
        <ul style="margin-left: 1.5rem; color: #0369a1; font-size: 0.875rem;">
            <li>MySQL 5.7+ or MariaDB 10.2+</li>
            <li>User must have CREATE, ALTER, INSERT, UPDATE, DELETE permissions</li>
            <li>UTF8MB4 character set support</li>
        </ul>
    </div>
    
    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
        <a href="?step=requirements" class="btn btn-secondary">← Back</a>
        <button type="submit" class="btn">Test Connection & Continue →</button>
    </div>
</form>

<script>
// Auto-fill common configurations
document.addEventListener('DOMContentLoaded', function() {
    const hostField = document.getElementById('db_host');
    const portField = document.getElementById('db_port');
    
    hostField.addEventListener('change', function() {
        if (this.value.includes(':')) {
            const parts = this.value.split(':');
            this.value = parts[0];
            if (parts[1]) {
                portField.value = parts[1];
            }
        }
    });
});
</script>

<?php
// Clean up session data
session_destroy();
?>

<div style="text-align: center; padding: 2rem 0;">
    <div style="background: #10b981; color: white; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem; font-size: 2rem;">
        ✓
    </div>
    
    <h2 style="margin-bottom: 1rem; color: #374151;">Installation Complete!</h2>
    <p style="font-size: 1.125rem; color: #6b7280; margin-bottom: 2rem;">
        Congratulations! AdminKit has been successfully installed and configured.
    </p>
    
    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 1.5rem; margin: 2rem 0; text-align: left;">
        <h3 style="margin-bottom: 1rem; color: #166534;">What's Next?</h3>
        <ul style="list-style: none; padding: 0;">
            <li style="padding: 0.5rem 0; display: flex; align-items: center;">
                <span style="color: #10b981; margin-right: 0.5rem;">1.</span>
                <strong>Access your admin panel:</strong> <a href="/admin" style="color: #0369a1; text-decoration: underline;">Go to Admin Panel</a>
            </li>
            <li style="padding: 0.5rem 0; display: flex; align-items: center;">
                <span style="color: #10b981; margin-right: 0.5rem;">2.</span>
                <strong>Configure your entities:</strong> Add your own entities and customize fields
            </li>
            <li style="padding: 0.5rem 0; display: flex; align-items: center;">
                <span style="color: #10b981; margin-right: 0.5rem;">3.</span>
                <strong>Customize the dashboard:</strong> Add widgets and configure the interface
            </li>
            <li style="padding: 0.5rem 0; display: flex; align-items: center;">
                <span style="color: #10b981; margin-right: 0.5rem;">4.</span>
                <strong>Set up users and roles:</strong> Create additional users and configure permissions
            </li>
        </ul>
    </div>
    
    <div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; padding: 1.5rem; margin: 2rem 0;">
        <h4 style="margin-bottom: 0.5rem; color: #92400e;">Important Security Notes:</h4>
        <ul style="margin-left: 1.5rem; color: #a16207; font-size: 0.875rem; text-align: left;">
            <li>Delete or secure the <code>/install</code> directory for production use</li>
            <li>Change default passwords and enable two-factor authentication</li>
            <li>Review and configure your .env file for production settings</li>
            <li>Set up regular database backups</li>
        </ul>
    </div>
    
    <div style="background: #f9fafb; border-radius: 8px; padding: 1.5rem; margin: 2rem 0;">
        <h4 style="margin-bottom: 1rem; color: #374151;">Resources & Documentation:</h4>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="https://github.com/oktayaydogan/admin-kit" target="_blank" style="color: #0369a1; text-decoration: underline;">GitHub Repository</a>
            <a href="https://github.com/oktayaydogan/admin-kit/wiki" target="_blank" style="color: #0369a1; text-decoration: underline;">Documentation</a>
            <a href="https://github.com/oktayaydogan/admin-kit/issues" target="_blank" style="color: #0369a1; text-decoration: underline;">Report Issues</a>
        </div>
    </div>
    
    <div style="margin-top: 3rem;">
        <a href="/admin" class="btn" style="font-size: 1.125rem; padding: 1rem 2rem;">Launch AdminKit →</a>
    </div>
    
    <p style="margin-top: 2rem; color: #9ca3af; font-size: 0.875rem;">
        Thank you for choosing AdminKit! We hope it serves you well.
    </p>
</div>

<script>
// Confetti animation for celebration
document.addEventListener('DOMContentLoaded', function() {
    // Simple confetti effect
    function createConfetti() {
        const confetti = document.createElement('div');
        confetti.style.cssText = `
            position: fixed;
            width: 10px;
            height: 10px;
            background: ${['#ff6b6b', '#4ecdc4', '#45b7d1', '#f39c12', '#e74c3c'][Math.floor(Math.random() * 5)]};
            top: -10px;
            left: ${Math.random() * 100}%;
            border-radius: 50%;
            pointer-events: none;
            z-index: 1000;
            animation: fall ${3 + Math.random() * 2}s linear forwards;
        `;
        
        document.body.appendChild(confetti);
        setTimeout(() => confetti.remove(), 5000);
    }
    
    // Add CSS animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fall {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Create confetti burst
    for (let i = 0; i < 50; i++) {
        setTimeout(createConfetti, i * 100);
    }
});
</script>

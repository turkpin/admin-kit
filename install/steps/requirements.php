<?php $requirements = checkRequirements(); ?>

<h2 style="margin-bottom: 1.5rem; color: #374151;">System Requirements</h2>
<p style="color: #6b7280; margin-bottom: 2rem;">
    Please ensure your system meets the following requirements before proceeding with the installation.
</p>

<ul class="requirements">
    <?php foreach ($requirements as $requirement => $passed): ?>
        <li class="<?= $passed ? 'pass' : 'fail' ?>">
            <?= htmlspecialchars($requirement) ?>
        </li>
    <?php endforeach; ?>
</ul>

<?php 
$allPassed = !in_array(false, $requirements, true);
?>

<?php if ($allPassed): ?>
    <div class="alert alert-success" style="margin-top: 2rem;">
        <strong>Great!</strong> Your system meets all the requirements. You can proceed with the installation.
    </div>
    
    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
        <a href="?step=welcome" class="btn btn-secondary">← Back</a>
        <a href="?step=database" class="btn">Continue →</a>
    </div>
<?php else: ?>
    <div class="alert alert-error" style="margin-top: 2rem;">
        <strong>Attention!</strong> Some requirements are not met. Please install the missing extensions or fix the issues before proceeding.
    </div>
    
    <div style="margin-top: 2rem;">
        <button onclick="window.location.reload()" class="btn">Check Again</button>
        <a href="?step=welcome" class="btn btn-secondary" style="margin-left: 1rem;">← Back</a>
    </div>
    
    <div style="margin-top: 2rem; padding: 1.5rem; background: #f9fafb; border-radius: 8px;">
        <h3 style="margin-bottom: 1rem; color: #374151;">Installation Tips:</h3>
        <ul style="margin-left: 1.5rem; color: #6b7280;">
            <li>Make sure you have PHP 8.0 or higher installed</li>
            <li>Install required PHP extensions using your package manager</li>
            <li>Run <code style="background: #e5e7eb; padding: 0.25rem 0.5rem; border-radius: 4px;">composer install</code> to install dependencies</li>
            <li>Ensure web server has write permissions to var/ and config/ directories</li>
        </ul>
    </div>
<?php endif; ?>

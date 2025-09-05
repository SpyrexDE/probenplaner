<?php $this->layout('layouts/default', ['title' => 'Registerführer Dashboard', 'currentPage' => $currentPage ?? 'leader']) ?>

<?php include __DIR__ . '/../components/promises-resources.php'; ?>

<?php if (isset($memberPromises) && !empty($_SESSION['role']) && $_SESSION['role'] === 'leader'): ?>
<div style="margin-bottom: var(--space-6); text-align: right;">
    <div style="display: inline-flex; align-items: center; gap: var(--space-3); padding: var(--space-3) var(--space-4); background: var(--color-white); border-radius: var(--radius-lg); border: 1px solid var(--color-border); box-shadow: var(--shadow-sm);" title="<?php echo empty($leadersCanViewAllSections) ? 'Nicht verfügbar: vom Dirigenten deaktiviert' : ''; ?>">
        <span style="font-size: var(--font-size-sm); font-weight: var(--font-weight-medium); color: var(--color-text-secondary);">Alle Register anzeigen</span>
        <label style="position: relative; display: inline-block; width: 50px; height: 24px;">
            <input type="checkbox" id="viewToggle" <?php echo empty($leadersCanViewAllSections) ? 'disabled' : ''; ?> 
                   <?php echo ($currentlyViewingAll ?? false) ? 'checked' : ''; ?>
                   style="opacity: 0; width: 0; height: 0;" />
            <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--color-gray-300); border-radius: 24px; transition: .3s;"></span>
            <span style="position: absolute; content: ''; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: .3s;"></span>
        </label>
    </div>
    <?php if (empty($leadersCanViewAllSections)): ?>
    <div style="margin-top: var(--space-2); text-align: right;">
        <span style="font-size: var(--font-size-xs); color: var(--color-text-muted); background: var(--color-gray-100); padding: var(--space-1) var(--space-2); border-radius: var(--radius-sm); display: inline-block;">
            Vom Dirigenten deaktiviert
        </span>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php 
// Set leader context for dashboard
$isAdmin = false;
$isLeader = true;
include __DIR__ . '/../components/promises-dashboard-wrapper.php'; 
?>

<script>
// Leader-specific toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('viewToggle');
    if (toggle) {
        toggle.addEventListener('change', function() {
            const slider = this.nextElementSibling;
            const dot = slider.nextElementSibling;
            
            // Update URL parameter and reload page
            const url = new URL(window.location);
            if (this.checked) {
                // Show all sections
                url.searchParams.set('viewAll', '1');
                slider.style.backgroundColor = 'var(--color-primary)';
                dot.style.transform = 'translateX(26px)';
            } else {
                // Show only own section
                url.searchParams.delete('viewAll');
                slider.style.backgroundColor = 'var(--color-gray-300)';
                dot.style.transform = 'translateX(0px)';
            }
            
            // Reload page with new parameter
            window.location.href = url.toString();
        });
        
        // Initialize toggle state based on current view mode
        const currentlyViewingAll = <?= json_encode($currentlyViewingAll ?? false) ?>;
        const slider = toggle.nextElementSibling;
        const dot = slider.nextElementSibling;
        
        if (currentlyViewingAll) {
            slider.style.backgroundColor = 'var(--color-primary)';
            dot.style.transform = 'translateX(26px)';
        } else {
            slider.style.backgroundColor = 'var(--color-gray-300)';
            dot.style.transform = 'translateX(0px)';
        }
        
        console.log('Leader view initialized - Currently viewing all:', currentlyViewingAll);
    }
});
</script>
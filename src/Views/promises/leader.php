<?php $this->layout('layouts/default', ['title' => 'Registerführer Dashboard', 'currentPage' => $currentPage ?? 'leader']) ?>

<?php include __DIR__ . '/../components/promises-resources.php'; ?>

<?php if (!empty($_SESSION['current_role']) && $_SESSION['current_role'] === 'leader'): ?>
<div style="margin-bottom: 20px; text-align: right;">
    <div style="display: inline-flex; align-items: center; gap: 12px; padding: 12px 16px; background: white; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 2px 4px rgba(0,0,0,0.1); <?php echo empty($leadersCanViewAllSections) ? 'opacity: 0.6;' : ''; ?>" title="<?php echo empty($leadersCanViewAllSections) ? 'Nicht verfügbar: vom Dirigenten deaktiviert' : ''; ?>">
        <span style="font-size: 14px; font-weight: 500; color: <?php echo empty($leadersCanViewAllSections) ? '#999' : '#666'; ?>;">Alle Register anzeigen</span>
        <label style="position: relative; display: inline-block; width: 50px; height: 24px;">
            <input type="checkbox" id="viewToggle" <?php echo empty($leadersCanViewAllSections) ? 'disabled' : ''; ?> 
                   <?php echo ($currentlyViewingAll ?? false) ? 'checked' : ''; ?>
                   style="opacity: 0; width: 0; height: 0; position: absolute;" />
            <span class="toggle-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 24px; transition: .3s;"></span>
            <span class="toggle-dot" style="position: absolute; content: ''; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: .3s;"></span>
        </label>
    </div>
    <?php if (empty($leadersCanViewAllSections)): ?>
    <div style="margin-top: 8px; text-align: right;">
        <span style="font-size: 12px; color: #999; background: #f5f5f5; padding: 4px 8px; border-radius: 4px; display: inline-block;">
            Vom Dirigenten deaktiviert
        </span>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php 
// Dashboard context
$isAdmin = false;
$isLeader = true;
include __DIR__ . '/../components/promises-dashboard-wrapper.php'; 
?>

<script>
// Toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('viewToggle');
    if (toggle) {
        const slider = toggle.nextElementSibling;
        const dot = slider.nextElementSibling;
        
        if (toggle.disabled) {
            // Disabled state
            slider.style.backgroundColor = '#e0e0e0';
            slider.style.cursor = 'not-allowed';
            slider.style.opacity = '0.6';
            dot.style.opacity = '0.6';
        } else if (toggle.checked) {
            slider.style.backgroundColor = '#007bff';
            dot.style.transform = 'translateX(26px)';
        } else {
            slider.style.backgroundColor = '#ccc';
            dot.style.transform = 'translateX(0px)';
        }
        
        console.log('Leader view initialized - Toggle checked:', toggle.checked, 'Disabled:', toggle.disabled);
    }
});
</script>
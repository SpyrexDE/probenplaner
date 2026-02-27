<?php $this->layout('layouts/default', ['title' => 'Registerführer Dashboard', 'currentPage' => $currentPage ?? 'leader']) ?>

<?php include __DIR__ . '/../components/promises-resources.php'; ?>

<?php if (!empty($canViewAllSections) && empty($_SESSION['current_permissions']['can_manage_ensemble'])): ?>
    <div style="margin-bottom: 20px; text-align: right;">
        <div style="display: inline-flex; align-items: center; gap: 12px; padding: 12px 16px; background: white; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <span style="font-size: 14px; font-weight: 500; color: #666;">Alle Register anzeigen</span>
            <label style="position: relative; display: inline-block; width: 50px; height: 24px;">
                <input type="checkbox" id="viewToggle"
                    <?php echo ($currentlyViewingAll ?? false) ? 'checked' : ''; ?>
                    style="opacity: 0; width: 0; height: 0; position: absolute;" />
                <span class="toggle-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 24px; transition: .3s;"></span>
                <span class="toggle-dot" style="position: absolute; content: ''; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: .3s;"></span>
            </label>
        </div>
    </div>
<?php endif; ?>

<?php
// Dashboard context
$isAdmin = false;
$isLeader = true;
include __DIR__ . '/../components/promises-dashboard-wrapper.php';
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('viewToggle');
        if (toggle) {
            const slider = toggle.nextElementSibling;
            const dot = slider.nextElementSibling;

            if (toggle.checked) {
                slider.style.backgroundColor = '#007bff';
                dot.style.transform = 'translateX(26px)';
            } else {
                slider.style.backgroundColor = '#ccc';
                dot.style.transform = 'translateX(0px)';
            }
        }
    });
</script>
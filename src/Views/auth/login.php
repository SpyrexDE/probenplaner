<?php $this->layout('layouts/default', ['title' => 'Login', 'currentPage' => $currentPage]) ?>

<?php 
// Login form component configuration
$csrf_token = isset($csrf_token) ? $csrf_token : '';
$action = '/login';
$logoPath = '/assets/img/Logo.png';
include __DIR__ . '/../components/login-form.php'; 
?>

<!-- Load JavaScript libraries -->
<script src="/assets/js/script.min.js"></script>

<script>
// openOld() function removed - now using scrollable interface with date separator
</script>

<?php if (isset($_SESSION['alerts']) && !empty($_SESSION['alerts'])): ?>
<script>
    <?php foreach ($_SESSION['alerts'] as $key => $alert): ?>
        // Convert alerts to toasts for consistent UX
        const icon = '<?= $alert[2] === 'error' ? 'error' : ($alert[2] === 'success' ? 'success' : 'info') ?>';
        const title = '<?= htmlspecialchars($alert[1]) ?>';
        if (icon === 'success') {
            window.notifySuccess(title);
        } else if (icon === 'error') {
            window.notifyError(title);
        } else {
            window.notifyInfo(title);
        }
    <?php unset($_SESSION['alerts'][$key]); endforeach; ?>
</script>
<?php endif; ?>
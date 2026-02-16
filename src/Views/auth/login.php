<?php $this->layout('layouts/default', ['title' => 'Anmelden', 'currentPage' => $currentPage, 'isFluid' => true]) ?>

<?php
// Login configuration
$csrf_token = isset($csrf_token) ? $csrf_token : '';
$action = '/login';
$logoPath = '/assets/img/Logo.png';
include __DIR__ . '/../components/login-form.php';
?>

<!-- Scripts -->
<script src="/assets/js/script.min.js"></script>


<?php if (isset($_SESSION['alerts']) && !empty($_SESSION['alerts'])): ?>
    <script>
        <?php foreach ($_SESSION['alerts'] as $key => $alert): ?>
            // Toast conversion
            const icon = '<?= $alert[2] === 'error' ? 'error' : ($alert[2] === 'success' ? 'success' : 'info') ?>';
            const title = '<?= htmlspecialchars($alert[1]) ?>';
            if (icon === 'success') {
                window.notifySuccess(title);
            } else if (icon === 'error') {
                window.notifyError(title);
            } else {
                window.notifyInfo(title);
            }
        <?php unset($_SESSION['alerts'][$key]);
        endforeach; ?>
    </script>
<?php endif; ?>
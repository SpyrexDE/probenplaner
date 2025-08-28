<?php $this->layout('layouts/default', ['title' => 'Login', 'currentPage' => $currentPage]) ?>

<div class="login-container">
    <form method="post" action="/login" class="login-form">
        <?php if (isset($csrf_token)): ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        
        <div class="login-logo">
            <img src="/assets/img/Logo.png" alt="Logo"/>
        </div>

        <input class="login-input" 
               type="text" 
               name="username" 
               placeholder="Nutzername" 
               required 
               minlength="2" 
               maxlength="20"
               autocomplete="username">

        <input class="login-input" 
               type="password" 
               name="password" 
               placeholder="Passwort" 
               required 
               minlength="4" 
               maxlength="20"
               autocomplete="current-password">

        <button class="login-button" type="submit">
            Einloggen
        </button>

        <div class="auth-links">
            <a href="/register" class="auth-link">
                Noch keinen Account? <span class="auth-link-primary">Registrieren</span>
            </a>
        </div>
    </form>
</div>

<!-- Load JavaScript libraries -->
<script src="/assets/js/script.min.js"></script>

<script>
// Helper function to show old/current entries
function openOld() {
    var currentUrl = window.location.href;
    var newUrl;
    
    if (currentUrl.indexOf('showOld=true') > -1) {
        // Currently showing old entries, switch to only current ones
        Swal.fire({
            title: 'Zur relevanten Ansicht wechseln?',
            text: 'In der relevanten Ansicht werden nur zukünftige Proben angezeigt.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Relevante Ansicht',
            cancelButtonText: 'Abbrechen',
            confirmButtonColor: '#478cf4'
        }).then((result) => {
            if (result.isConfirmed) {
                newUrl = currentUrl.replace(/[?&]showOld=true/, '');
                window.location.href = newUrl;
            }
        });
    } else {
        // Currently showing only current entries, switch to all entries
        Swal.fire({
            title: 'Zur vollständigen Ansicht wechseln?',
            text: 'In der vollständigen Ansicht werden auch bereits vergangene Proben angezeigt.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Vollständige Ansicht',
            cancelButtonText: 'Abbrechen',
            confirmButtonColor: '#478cf4'
        }).then((result) => {
            if (result.isConfirmed) {
                newUrl = currentUrl + (currentUrl.indexOf('?') > -1 ? '&' : '?') + 'showOld=true';
                window.location.href = newUrl;
            }
        });
    }
}
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
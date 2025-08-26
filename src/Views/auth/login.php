<?php $this->layout('layouts/default', ['title' => 'Login', 'currentPage' => $currentPage]) ?>



<style>
    .login-clean {
        width: 100%;
        padding-bottom: 20vh;
        padding-top: 5vh;
        height: 100%;
        min-height: 440px;
        background: #f1f7fc;
    }
    .login-clean form {
        max-width: 320px;
        width: 90%;
        margin: 0 auto;
        background-color: #fff;
        padding: 40px;
        border-radius: 4px;
        color: #505e6c;
        box-shadow: 1px 1px 5px rgba(0,0,0,.1);
    }
    .illustration {
        text-align: center;
        padding: 0 0 20px;
        font-size: 100px;
        color: #f4476b;
    }
    .illustration img {
        max-width: 200px;
        height: auto;
        width: 100%;
        max-height: 120px;
        object-fit: contain;
    }
    .login-clean form .form-control {
        background: #f7f9fc;
        border: none;
        border-bottom: 1px solid #dfe7f1;
        border-radius: 0;
        box-shadow: none;
        outline: 0;
        color: inherit;
        text-indent: 8px;
        height: 42px;
    }
    .login-clean form .btn-primary {
        background: #f4476b;
        border: none;
        border-radius: 4px;
        padding: 11px;
        box-shadow: none;
        margin-top: 26px;
        text-shadow: none;
        outline: 0!important;
    }
    .login-clean form .btn-primary:active,
    .login-clean form .btn-primary:hover {
        background: #eb3b60;
    }
    .login-clean form .btn-primary:active {
        transform: translateY(1px);
    }
    .login-clean form .forgot {
        display: block;
        text-align: center;
        font-size: 12px;
        color: #6f7a85;
        opacity: .9;
        text-decoration: none;
    }
    .login-clean form .forgot:active,
    .login-clean form .forgot:hover {
        opacity: 1;
        text-decoration: none;
    }
    /* Ensure mobile responsiveness */
    @media (max-width: 767px) {
        .login-clean form {
            width: 90%;
            margin: 0 auto;
        }
        .login-clean {
            padding-bottom: 10vh;
        }
        .illustration img {
            max-width: 150px;
            max-height: 90px;
        }
    }
</style>

<div class="login-clean">
    <form method="post" action="/login">
        <?php if (isset($csrf_token)): ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        <h2 class="sr-only">Login Form</h2>
        <div class="illustration">
            <img src="/assets/img/Logo.png" alt="Logo"/>
        </div>
        <div class="form-group">
            <input class="form-control" type="text" id="username" name="username" placeholder="Nutzername" style="font-family: Roboto, sans-serif;" required minlength="2" maxlength="20">
        </div>
        <div class="form-group">
            <input class="form-control" type="password" id="password" name="password" placeholder="Passwort" style="font-family: Roboto, sans-serif;" required minlength="4" maxlength="20">
        </div>
        <div class="form-group">
            <button class="btn btn-primary btn-block" type="submit" style="background-color: rgb(71,140,244); font-family: Roboto, sans-serif;">Einloggen</button>
        </div>
        <a href="/register" class="forgot">
            Noch keinen Account? Hier <font color="#5772b4">registrieren</font>!
        </a>
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
 
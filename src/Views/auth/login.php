<!DOCTYPE html>
<html lang="de" style="width: 100%; height: 100%;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?= APP_NAME ?></title>
    <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=ABeeZee">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Bitter:400,700">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Catamaran:100,200,300,400,500,600,700,800,900">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Muli">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Titan+One">
    <link href="https://fonts.googleapis.com/css2?family=Fugaz+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="/assets/fonts/font-awesome.min.css">
    <link rel="stylesheet" href="/assets/fonts/ionicons.min.css">
    <link rel="stylesheet" href="/assets/fonts/fontawesome5-overrides.min.css">
    <link rel="stylesheet" href="/assets/css/styles.min.css">
    <link rel="stylesheet" href="/assets/css/custom.css">
    <link rel="shortcut icon" href="/assets/img/tabIcon.png" type="image/x-icon">
    <link rel="manifest" href="/manifest.json">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/easy-pwa-js@1.0/dist/front.js"></script>
    <script src="/assets/js/jquery.min.js"></script>
    <!-- Tippy.js for tooltips -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/animations/scale.css"/>
    <style>
        body {
            margin-top: 0 !important;
            padding-top: 67px;
        }
        .login-clean {
            width: 100%;
            padding-bottom: 20vh;
            padding-top: 5vh;
            height: 100%;
            min-height: 440px;
        }
        .navbar-brand {
            color: #478cf4 !important;
            font-size: 31px !important;
            padding-top: 0 !important;
            font-weight: 1000 !important;
            margin-top: 4px !important;
            padding-bottom: 0px !important;
            font-family: 'Fugaz One', cursive !important;
        }
        .fas, .far {
            font-size: 21px;
        }
        /* Ensure mobile responsiveness */
        @media (max-width: 767px) {
            .navbar-brand {
                font-size: 24px !important;
            }
            .login-clean form {
                width: 90%;
                margin: 0 auto;
            }
            .navbar .container-fluid {
                padding-left: 10px;
                padding-right: 10px;
            }
        }
    </style>
</head>
<body style="width: 100%; height: 100%;">


<div class="login-clean">
    <form method="post" action="/login">
        <h2 class="sr-only">Login Form</h2>
        <div class="illustration">
            <img src="/assets/img/Logo.png" style="transform: scale(0.85); transform-origin: 0 0;"/>
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
        <a href="/register" style="display: block; text-align: center; font-size: 12px; color: gray;">
            Noch keinen Account? Hier <font color="#5772b4">registrieren</font>!
        </a>
    </form>
</div>

<!-- Load JavaScript libraries -->
<script src="/assets/bootstrap/js/bootstrap.min.js"></script>
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
        Swal.fire({
            title: '<?= htmlspecialchars($alert[0]) ?>',
            text: '<?= htmlspecialchars($alert[1]) ?>',
            icon: '<?= $alert[2] === 'error' ? 'error' : ($alert[2] === 'success' ? 'success' : 'info') ?>',
            confirmButtonColor: '#478cf4'
        });
    <?php unset($_SESSION['alerts'][$key]); endforeach; ?>
</script>
<?php endif; ?>
</body>
</html> 
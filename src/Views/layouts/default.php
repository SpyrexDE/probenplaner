<!DOCTYPE html>
<html lang="de" style="width: 100%; height: 100%;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?= isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : (isset($title) ? $title : APP_NAME) ?></title>
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
    <link rel="stylesheet" href="/assets/css/tree-view-clickable.css">
    <link rel="shortcut icon" href="/assets/img/tabIcon.png" type="image/x-icon">
    <link rel="manifest" href="/manifest.json">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/easy-pwa-js@1.0/dist/front.js"></script>
    <script src="/assets/js/jquery.min.js"></script>
    <!-- Tippy.js for tooltips -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/animations/scale.css"/>
</head>
<body style="width: 100%; height: 100%;">
<?php if (isset($_SESSION['username'])): ?>
    <div id="wrapper">
        <div class="shadow-lg topBar" id="sidebar-wrapper" style="background-color: #ffffff;">
            <ul class="sidebar-nav">
                <li class="sidebar-brand" style="background-color: #478cf4; height: 67px;">
                    <div class="text-secondary" style="width: 100%; height: 100%; overflow: hidden; background-color: #ffffff; border-width: 0; border-bottom: 0; border-color: lightgrey; border-style: solid;">
                        <div style="width: 30%; background: grey; float: left; height: 100%; background-color: rgba(255,255,255,0);">
                            <i class="icon ion-ios-contact" style="color: #478cf4; font-size: 64px; margin: -18px; margin-left: -28px;"></i>
                        </div>
                        <div class="text-nowrap" style="width: 70%; background: green; overflow: hidden; height: 100%; background-color: rgba(255,255,255,0);">
                            <label style="margin: 0; width: 100%; height: 50%; float: left; margin-left: -10px; margin-top: -7px;"><?= $_SESSION['username'] ?? '' ?></label>
                            <label id="groupLabel" style="margin: 0; width: 100%; height: 50%; float: left; margin-top: -12px; margin-left: -10px;"><?= $_SESSION['type'] ?? '' ?></label>
                        </div>
                    </div>
                </li>
                <li>
                    <?php if (isset($_SESSION['type']) && $_SESSION['type'] === 'Dirigent'): ?>
                        <!-- Admin menu -->
                        <a class="<?= $currentPage === 'rehearsals' ? 'activeTab' : '' ?>" href="/rehearsals" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Termine</a>
                        <a class="<?= $currentPage === 'probenplan' ? 'activeTab' : '' ?>" href="/probenplan" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Probenplan</a>
                        <a class="<?= $currentPage === 'admin' ? 'activeTab' : '' ?>" href="/promises/admin" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Rückmeldungen</a>
                        <a class="<?= $currentPage === 'conductor_profile' ? 'activeTab' : '' ?>" href="/conductor/profile" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Profil bearbeiten</a>
                        <a href="/logout" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Logout</a>
                    <?php elseif (isset($_SESSION['username']) && strpos($_SESSION['username'], '♚') !== false): ?>
                        <!-- Section leader menu -->
                        <a class="<?= $currentPage === 'promises' ? 'activeTab' : '' ?>" href="/promises" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Meine Meldungen</a>
                        <a class="<?= $currentPage === 'leader' ? 'activeTab' : '' ?>" href="/promises/leader" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Rückmeldungen</a>
                        <a class="<?= $currentPage === 'rehearsals' ? 'activeTab' : '' ?>" href="/rehearsals" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Probenplan</a>
                        <a class="<?= $currentPage === 'profile' ? 'activeTab' : '' ?>" href="/profile" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Profil bearbeiten</a>
                        <a href="/logout" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Logout</a>
                    <?php else: ?>
                        <!-- Regular user menu -->
                        <a class="<?= $currentPage === 'promises' ? 'activeTab' : '' ?>" href="/promises" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Meine Meldungen</a>
                        <a class="<?= $currentPage === 'rehearsals' ? 'activeTab' : '' ?>" href="/rehearsals" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Probenplan</a>
                        <a class="<?= $currentPage === 'profile' ? 'activeTab' : '' ?>" href="/profile" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Profil bearbeiten</a>
                        <a href="/logout" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Logout</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
        <div class="shadow-sm page-content-wrapper" style="width: 100%; background-color: #ffffff; padding-bottom: 0px;">
            <!-- Navbar in exact original style -->
            <div class="col topBar"><a class="btn btn-link float-left" role="button" id="menu-toggle" href="#menu-toggle" style="font-size: 37px;"><i class="fa fa-bars"></i></a>
                <div class="float-none text-center">
                    <div style="white-space: pre;display: block;padding: 9.5px;margin: 0 0 10px;font-size: 13px;line-height: 1.42857143;word-break: break-all;word-wrap: break-word;overflow: hidden;"> <a class="navbar-brand float-none" href="#" style="color: #478cf4 !important;
    font-size: 31px !important;
    padding-top: 0 !important;
    font-weight: 1000 !important;
    margin-top: 4px !important;
    padding-bottom: 0px !important;
    font-family: 'Fugaz One', cursive !important; margin-right: 50px;"><?= isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : APP_NAME ?></a><i onclick="openOld();" class="fas fa-history" style="transform: scale(1.5); transform-origin: 0; position: fixed; top: 23px; right: 65px; cursor: pointer;"></i><i onclick="showHelp();" class="fas fa-question-circle help-link" style="transform: scale(1.5); transform-origin: 0; position: fixed; top: 23px; right: 25px; cursor: pointer;"></i></div>
                </div>
            </div>
            <div id="contentPage" class="col" style="padding: 0;">
                <?php 
                // Remove the floating filter checkbox widget since functionality is already in header
                ?>
                
                <?= $content ?? '' ?>
            </div>
        </div>
    </div>
<?php else: ?>
<?php 
// Hide topbar on login and register pages
$hideNavbar = false;
if (isset($currentPage) && ($currentPage === 'login' || $currentPage === 'register')) {
    $hideNavbar = true;
}
?>

<?php if (!$hideNavbar): ?>
<!-- Navbar for non-logged in users - match original style -->
<div class="shadow-sm page-content-wrapper" style="width: 100%; background-color: #ffffff; padding-bottom: 0px;">
    <div class="col topBar">
        <div class="float-none text-center">
            <div style="white-space: pre;display: block;padding: 9.5px;margin: 0 0 10px;font-size: 13px;line-height: 1.42857143;word-break: break-all;word-wrap: break-word;overflow: hidden;"> <a class="navbar-brand float-none" href="#" style="color: #478cf4 !important;
    font-size: 31px !important;
    padding-top: 0 !important;
    font-weight: 1000 !important;
    margin-top: 4px !important;
    padding-bottom: 0px !important;
    font-family: 'Fugaz One', cursive !important; margin-right: 50px;"><?= isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : APP_NAME ?></a><i onclick="openOld();" class="fas fa-history" style="transform: scale(1.5); transform-origin: 0; position: fixed; top: 26px; right: 50px; cursor: pointer;"></i><i onclick="showHelp();" class="fas fa-question-circle help-link" style="transform: scale(1.5); transform-origin: 0; position: fixed; top: 26px; right: 20px; cursor: pointer;"></i></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container mt-4">
    <?= $content ?? '' ?>
</div>
<?php endif; ?>

<!-- Add scripts at the end of the body -->
<script src="/assets/js/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/assets/js/script.min.js"></script>
<script src="/assets/js/tree-view-clickable.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Show help function with content from old site
    function showHelp() {
        Swal.fire({
            title: 'Hilfe',
            html: '<p>Um eine Probe zu bearbeiten, klicke auf den Stift.</p>' +
                  '<p>Um eine Probe zu löschen, klicke auf den Mülleimer.</p>' +
                  '<p>Um eine neue Probe anzulegen, klicke unten rechts auf das Plus.</p>' +
                  '<p>Klicke auf das Uhrsymbol in der oberen rechten Ecke, um vergangene Proben ein- und auszublenden.</p>',
            icon: 'info',
            confirmButtonColor: '#478cf4'
        });
    }
    
    // Helper function to show old/current entries
    function openOld() {
        var currentUrl = window.location.href;
        var newUrl;
        
        if (currentUrl.indexOf('showOld=true') > -1 || currentUrl.indexOf('showOld=1') > -1) {
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
                    newUrl = currentUrl.replace(/[?&]showOld=(true|1)/, '');
                    if (newUrl.endsWith('?') || newUrl.endsWith('&')) {
                        newUrl = newUrl.slice(0, -1);
                    }
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
                    newUrl = currentUrl + (currentUrl.indexOf('?') > -1 ? '&' : '?') + 'showOld=1';
                    window.location.href = newUrl;
                }
            });
        }
    }
</script>
</body>
</html> 
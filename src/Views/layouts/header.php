<?php 
/**
 * Header Template
 * 
 * @package JSO-APP
 * @author  Jakub Sofinski <jakkraw@gmail.com> <github/JakubJedrzejczak>
 * @updated 2023-08-16 
 * @update  Replaced some icons for more legible ones, added menu toggler in the left side.
 */
?>
<!DOCTYPE html>
<html lang="de" style="width: 100%; height: 100%;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?= isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : APP_NAME ?></title>
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
                            <?php if (isset($_SESSION['orchestra_name'])): ?>
                                <small style="display: block; font-size: 10px; margin-left: -10px; margin-top: -5px;"><?= $_SESSION['orchestra_name'] ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
                <li>
                    <a class="<?= $currentPage === 'promises' ? 'activeTab' : '' ?>" href="/promises" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Meine Meldungen</a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'leader'): ?>
                        <a class="<?= $currentPage === 'leader' ? 'activeTab' : '' ?>" href="/promises/leader" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Rückmeldungen</a>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'conductor' || isset($_SESSION['type']) && $_SESSION['type'] === 'Dirigent'): ?>
                        <a class="<?= $currentPage === 'admin' ? 'activeTab' : '' ?>" href="/promises/admin" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Alle Rückmeldungen</a>
                        <a class="<?= $currentPage === 'rehearsals' ? 'activeTab' : '' ?>" href="/rehearsals" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Proben verwalten</a>
                        <a class="<?= $currentPage === 'conductor_profile' ? 'activeTab' : '' ?>" href="/conductor/profile" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Profil bearbeiten</a>
                        <a class="<?= $currentPage === 'orchestra_settings' ? 'activeTab' : '' ?>" href="/orchestras/settings" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Orchester bearbeiten</a>
                    <?php else: ?>
                        <a class="<?= $currentPage === 'rehearsals' ? 'activeTab' : '' ?>" href="/rehearsals" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Probenplan</a>
                        <a class="<?= $currentPage === 'profile' ? 'activeTab' : '' ?>" href="/profile" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Profil bearbeiten</a>
                    <?php endif; ?>
                    <a href="/logout" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">Logout</a>
                </li>
            </ul>
        </div>
        <div class="page-content-wrapper" style="width: 100%; background-color: #ffffff; padding-bottom: 0px;">
            <!-- Standardized navbar with fixed position icons -->
            <nav class="navbar navbar-light topBar">
              <div class="container-fluid">
                <div class="row w-100 align-items-center">
                  <div class="col">
                    <div class="row align-items-center">
                      <div class="col-auto">
                        <a href="#menu-toggle" id="menu-toggle">
                          <i class="fas fa-bars"></i>
                        </a>
                      </div>
                      <div class="col-auto">
                        <a class="navbar-brand ml-2" href="/"><?= isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : APP_NAME ?></a>
                      </div>
                    </div>
                  </div>
                  <div class="col-auto">
                    <a href="javascript:void(0)" class="history-link" onclick="openOld()">
                      <i class="fas fa-history mr-4"></i>
                    </a>
                    <a href="javascript:void(0)" class="help-link" onclick="window.app && window.app.help()">
                      <i class="fas fa-question-circle"></i>
                    </a>
                  </div>
                </div>
              </div>
            </nav>
            <div id="contentPage" class="col" style="padding: 0;">
                <div class="float-none text-center">
<?php else: ?>
<!-- Navbar for non-logged in users -->
<nav class="navbar navbar-light topBar">
  <div class="container-fluid">
    <div class="row w-100 align-items-center">
      <div class="col">
        <div class="row align-items-center">
          <div class="col-auto">
            <a class="navbar-brand ml-2" href="/"><?= isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : APP_NAME ?></a>
          </div>
        </div>
      </div>
      <div class="col-auto">
        <a href="javascript:void(0)" class="history-link" onclick="openOld()">
          <i class="fas fa-history mr-4"></i>
        </a>
        <a href="javascript:void(0)" class="help-link" onclick="window.app && window.app.help()">
          <i class="fas fa-question-circle"></i>
        </a>
      </div>
    </div>
  </div>
</nav>
<?php endif; ?>

<?php // DEBUG: Remove after testing ?>
<?php if (isset($_SESSION['role']) || isset($_SESSION['type'])): ?>
    <div style="color:red; font-size:10px;">role: <?= $_SESSION['role'] ?? 'unset' ?>, type: <?= $_SESSION['type'] ?? 'unset' ?></div>
<?php endif; ?>

<!-- Add scripts at the end of the body -->
<script src="/assets/js/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/assets/js/script.min.js"></script>
</body>
</html> 
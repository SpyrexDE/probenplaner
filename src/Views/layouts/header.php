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
<html lang="de" class="w-full h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?= isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : APP_NAME ?></title>
    <!-- Note: This header.php appears to be legacy. The main layout is now in default.php with Tailwind CSS -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,600,700">
    <link href="https://fonts.googleapis.com/css2?family=Fugaz+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="/assets/fonts/font-awesome.min.css">
    <link rel="stylesheet" href="/assets/fonts/ionicons.min.css">
    <link rel="stylesheet" href="/assets/fonts/fontawesome5-overrides.min.css">
    <link rel="shortcut icon" href="/assets/img/tabIcon.png" type="image/x-icon">
    <link rel="manifest" href="/manifest.json">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/easy-pwa-js@1.0/dist/front.js"></script>
    <script src="/assets/js/jquery.min.js"></script>
    <!-- Tippy.js for tooltips -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/animations/scale.css"/>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link rel="stylesheet" href="/assets/css/focus-removal.css">
</head>
<body class="w-full h-full">
<?php if (isset($_SESSION['username'])): ?>
    <div id="wrapper">
        <div class="topBar bg-white border-r border-gray-200" id="sidebar-wrapper">
            <ul class="sidebar-nav">
                <li class="sidebar-brand bg-primary h-16">
                    <div class="text-secondary" style="width: 100%; height: 100%; overflow: hidden; background-color: #ffffff; border-width: 0; border-bottom: 0; border-color: lightgrey; border-style: solid;">
                        <div style="width: 30%; background: grey; float: left; height: 100%; background-color: rgba(255,255,255,0);">
                            <i class="icon ion-ios-contact" style="color: #478cf4; font-size: 64px; margin: -18px; margin-left: -28px;"></i>
                        </div>
                        <div class="text-nowrap" style="width: 70%; background: green; overflow: hidden; height: 100%; background-color: rgba(255,255,255,0);">
                            <label style="margin: 0; width: 100%; height: 50%; float: left; margin-left: -10px; margin-top: -7px;"><?= $_SESSION['username'] ?? '' ?></label>
                            <label id="groupLabel" style="margin: 0; width: 100%; height: 50%; float: left; margin-top: -12px; margin-left: -10px;"><?= $_SESSION['type'] ?? '' ?></label>
                            <?php if (isset($_SESSION['orchestra_name'])): ?>
                                <small class="block text-xs -ml-2 -mt-1"><?= $_SESSION['orchestra_name'] ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
                <li>
                    <a class="<?= $currentPage === 'promises' ? 'activeTab' : '' ?>" href="/promises" class="text-black">Meine Meldungen</a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'leader'): ?>
                        <a class="<?= $currentPage === 'leader' ? 'activeTab' : '' ?>" href="/promises/leader" class="text-black">Rückmeldungen</a>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'conductor' || isset($_SESSION['type']) && $_SESSION['type'] === 'Dirigent'): ?>
                        <a class="<?= $currentPage === 'admin' ? 'activeTab' : '' ?>" href="/promises/admin" class="text-black">Alle Rückmeldungen</a>
                        <a class="<?= $currentPage === 'rehearsals' ? 'activeTab' : '' ?>" href="/rehearsals" class="text-black">Proben verwalten</a>
                        <a class="<?= $currentPage === 'conductor_profile' ? 'activeTab' : '' ?>" href="/conductor/profile" class="text-black">Profil bearbeiten</a>
                        <a class="<?= $currentPage === 'orchestra_settings' ? 'activeTab' : '' ?>" href="/orchestras/settings" class="text-black">Orchester bearbeiten</a>
                    <?php else: ?>
                        <a class="<?= $currentPage === 'probenplan' ? 'activeTab' : '' ?>" href="/probenplan" class="text-black">Probenplan</a>
                        <a class="<?= $currentPage === 'profile' ? 'activeTab' : '' ?>" href="/profile" class="text-black">Profil bearbeiten</a>
                    <?php endif; ?>
                    <a href="/logout" class="text-black">Logout</a>
                </li>
            </ul>
        </div>
        <div class="page-content-wrapper w-full bg-white pb-0">
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
                    <?php 
                    // Show buttons on relevant routes
                    $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                    
                    // Show history button only on promises routes
                    $showHistoryButton = (strpos($currentUri, '/promises') === 0);
                    
                    // Show help button on all main feature pages
                    $showHelpButton = in_array($currentUri, ['/promises', '/promises/leader', '/promises/admin', 
                                                            '/rehearsals', '/probenplan', '/profile', '/conductor/profile']) 
                                     || (strpos($currentUri, '/promises/') === 0)
                                     || (strpos($currentUri, '/rehearsals/') === 0);
                    ?>
                    
                    <?php if ($showHistoryButton): ?>
                    <a href="javascript:void(0)" class="history-link" onclick="openOld()">
                      <i class="fas fa-history mr-4"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($showHelpButton): ?>
                    <a href="javascript:void(0)" class="help-link" onclick="window.app && window.app.help()">
                      <i class="fas fa-question-circle"></i>
                    </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </nav>
            <div id="contentPage" class="col p-0">
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
        <?php 
        // Show buttons on relevant routes
        $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Show history button only on promises routes
        $showHistoryButton = (strpos($currentUri, '/promises') === 0);
        
        // Show help button on all main feature pages
        $showHelpButton = in_array($currentUri, ['/promises', '/promises/leader', '/promises/admin', 
                                                '/rehearsals', '/probenplan', '/profile', '/conductor/profile']) 
                                 || (strpos($currentUri, '/promises/') === 0)
                                 || (strpos($currentUri, '/rehearsals/') === 0);
        ?>
        
        <?php if ($showHistoryButton): ?>
        <a href="javascript:void(0)" class="history-link" onclick="openOld()">
          <i class="fas fa-history mr-4"></i>
        </a>
        <?php endif; ?>
        
        <?php if ($showHelpButton): ?>
        <a href="javascript:void(0)" class="help-link" onclick="window.app && window.app.help()">
          <i class="fas fa-question-circle"></i>
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<?php endif; ?>

<?php // DEBUG: Remove after testing ?>
<?php if (isset($_SESSION['role']) || isset($_SESSION['type'])): ?>
    <div class="text-red-500 text-xs">role: <?= $_SESSION['role'] ?? 'unset' ?>, type: <?= $_SESSION['type'] ?? 'unset' ?></div>
<?php endif; ?>

<!-- Add scripts at the end of the body -->
<script src="/assets/js/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/assets/js/script.min.js"></script>
</body>
</html> 
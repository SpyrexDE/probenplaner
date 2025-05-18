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
<?php 
use App\Core\Utilities;
if (isset($_SESSION['username'])): ?>
    <div id="wrapper">
        <div class="shadow-lg topBar" id="sidebar-wrapper" style="background-color: #ffffff;">
            <ul class="sidebar-nav">
                <li class="sidebar-brand" style="background-color: #478cf4; height: 67px;">
                    <div class="text-secondary" style="width: 100%; height: 100%; overflow: hidden; background-color: #ffffff; border-width: 0; border-bottom: 0; border-color: lightgrey; border-style: solid;">
                        <div style="width: 30%; background: grey; float: left; height: 100%; background-color: rgba(255,255,255,0);">
                            <i class="icon ion-ios-contact" style="color: #478cf4; font-size: 64px; margin: -18px; margin-left: -28px;"></i>
                        </div>
                        <div class="text-nowrap" style="width: 70%; background: green; overflow: hidden; height: 100%; background-color: rgba(255,255,255,0);">
                            <label style="margin: 0; width: 100%; height: 50%; float: left; margin-left: -10px; margin-top: -7px;">
                                <?= Utilities::formatUsername($_SESSION['username'], $_SESSION['role'] ?? 'member', $_SESSION['is_small_group'] ?? false) ?>
                            </label>
                            <label id="groupLabel" style="margin: 0; width: 100%; height: 50%; float: left; margin-top: -12px; margin-left: -10px;"><?= isset($_SESSION['type']) ? str_replace('_', ' ', $_SESSION['type']) : '' ?></label>
                        </div>
                    </div>
                </li>
                <li>
                    <?php
                    $menu = [];
                    if (isset($_SESSION['type']) && $_SESSION['type'] === 'Dirigent') {
                        $menu = [
                            ['label' => 'Termine', 'href' => '/rehearsals', 'page' => 'rehearsals'],
                            ['label' => 'Probenplan', 'href' => '/probenplan', 'page' => 'probenplan'],
                            ['label' => 'Rückmeldungen', 'href' => '/promises/admin', 'page' => 'admin'],
                            ['label' => 'Profil bearbeiten', 'href' => '/conductor/profile', 'page' => 'conductor_profile'],
                            ['label' => 'Orchester bearbeiten', 'href' => '/orchestras/settings', 'page' => 'orchestra_settings'],
                            ['label' => 'Logout', 'href' => '/logout', 'page' => null],
                        ];
                    } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'leader') {
                        $menu = [
                            ['label' => 'Meine Meldungen', 'href' => '/promises', 'page' => 'promises'],
                            ['label' => 'Rückmeldungen', 'href' => '/promises/leader', 'page' => 'leader'],
                            ['label' => 'Probenplan', 'href' => '/probenplan', 'page' => 'probenplan'],
                            ['label' => 'Profil bearbeiten', 'href' => '/profile', 'page' => 'profile'],
                            ['label' => 'Logout', 'href' => '/logout', 'page' => null],
                        ];
                    } else {
                        $menu = [
                            ['label' => 'Meine Meldungen', 'href' => '/promises', 'page' => 'promises'],
                            ['label' => 'Probenplan', 'href' => '/probenplan', 'page' => 'probenplan'],
                            ['label' => 'Profil bearbeiten', 'href' => '/profile', 'page' => 'profile'],
                            ['label' => 'Logout', 'href' => '/logout', 'page' => null],
                        ];
                    }
                    foreach ($menu as $item) {
                        $active = isset($item['page']) && $currentPage === $item['page'] ? 'activeTab' : '';
                        echo '<a class="' . $active . '" href="' . $item['href'] . '" style="color: rgb(0,0,0); font-family: Roboto, sans-serif;">' . $item['label'] . '</a>';
                    }
                    ?>
                </li>
            </ul>
        </div>
        <div class="shadow-sm page-content-wrapper" style="width: 100%; background-color: #ffffff; padding-bottom: 0px;">
            <!-- Navbar in exact original style -->
            <div class="col topBar"><a class="btn btn-link float-left" role="button" id="menu-toggle" href="#menu-toggle" style="font-size: 37px;"><i class="fa fa-bars"></i></a>
                <div class="float-none text-center">
                    <div style="display: block;padding: 9.5px;margin: 0 0 10px;font-size: 13px; word-break: break-all;word-wrap: break-word;overflow: hidden;"> <a class="navbar-brand float-none" href="#" style="color: #478cf4 !important;
    font-size: 31px !important;
    padding-top: 0 !important;
    font-weight: 1000 !important;
    margin-top: 4px !important;
    padding-bottom: 0px !important;
    font-family: 'Fugaz One', cursive !important; margin-right: 50px;"><?= isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : APP_NAME ?></a>
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
    <i onclick="openOld();" class="fas fa-history" style="transform: scale(1.5); transform-origin: 0; position: fixed; top: 23px; right: 65px; cursor: pointer;"></i>
    <?php endif; ?>
    
    <?php if ($showHelpButton): ?>
    <i onclick="showHelp();" class="fas fa-question-circle help-link" style="transform: scale(1.5); transform-origin: 0; position: fixed; top: 23px; right: 25px; cursor: pointer;"></i>
    <?php endif; ?>
    </div>
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
            <div style="display: block;padding: 9.5px;margin: 0 0 10px;font-size: 13px;line-height: 1.42857143;word-break: break-all;word-wrap: break-word;overflow: hidden;"> <a class="navbar-brand float-none" href="#" style="color: #478cf4 !important;
    font-size: 31px !important;
    padding-top: 0 !important;
    font-weight: 1000 !important;
    margin-top: 4px !important;
    padding-bottom: 0px !important;
    font-family: 'Fugaz One', cursive !important; margin-right: 50px;"><?= isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : APP_NAME ?></a>
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
    <i onclick="openOld();" class="fas fa-history" style="transform: scale(1.5); transform-origin: 0; position: fixed; top: 26px; right: 50px; cursor: pointer;"></i>
    <?php endif; ?>
    
    <?php if ($showHelpButton): ?>
    <i onclick="showHelp();" class="fas fa-question-circle help-link" style="transform: scale(1.5); transform-origin: 0; position: fixed; top: 26px; right: 20px; cursor: pointer;"></i>
    <?php endif; ?>
    </div>
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
        const currentRoute = window.location.pathname;
        let helpTitle = 'Hilfe';
        let helpContent = '';
        
        // Provide different help content based on the current route
        if (currentRoute.startsWith('/promises/admin')) {
            // Director view of all promises/responses
            helpTitle = 'Hilfe - Rückmeldungen verwalten';
            helpContent = '<p>Hier sehen Sie alle Rückmeldungen zu den Proben.</p>' +
                          '<p>In der Tabelle werden die An-/Abmeldungen Ihrer Orchestermitglieder angezeigt.</p>' +
                          '<p>Mit dem Filter oben können Sie die Anzeige auf bestimmte Instrumente oder Zeiträume beschränken.</p>' +
                          '<p>Klicken Sie auf den Namen einer Person, um deren Notizen zu sehen.</p>';
        } 
        else if (currentRoute.startsWith('/promises/leader')) {
            // Group leader view of responses
            helpTitle = 'Hilfe - Gruppen-Rückmeldungen';
            helpContent = '<p>Hier sehen Sie alle Rückmeldungen Ihrer Gruppe.</p>' +
                          '<p>In der Tabelle werden die An-/Abmeldungen der Mitglieder Ihrer Instrumentengruppe angezeigt.</p>' +
                          '<p>Klicken Sie auf den Namen einer Person, um deren Notizen zu sehen.</p>';
        }
        else if (currentRoute.startsWith('/promises')) {
            // Individual member view of their promises
            helpTitle = 'Hilfe - Meine Meldungen';
            helpContent = '<p>Hier können Sie Ihre An- und Abmeldungen für kommende Proben verwalten.</p>' +
                          '<p>Klicken Sie auf eine Probe in der Tabelle, um Ihre Teilnahme zu bestätigen oder abzusagen.</p>' +
                          '<p>Bei einer Absage können Sie optional einen Grund angeben.</p>' +
                          '<p>Vergangene Proben werden automatisch ausgeblendet.</p>';
        }
        else if (currentRoute.startsWith('/rehearsals')) {
            // Rehearsal management for directors
            helpTitle = 'Hilfe - Proben verwalten';
            helpContent = '<p>Um eine Probe zu bearbeiten, klicken Sie auf den Stift.</p>' +
                          '<p>Um eine Probe zu löschen, klicken Sie auf den Mülleimer.</p>' +
                          '<p>Um eine neue Probe anzulegen, klicken Sie unten rechts auf das Plus.</p>' +
                          '<p>Klicken Sie auf das Uhrsymbol in der oberen rechten Ecke, um vergangene Proben ein- und auszublenden.</p>';
        }
        else if (currentRoute.startsWith('/probenplan')) {
            // Rehearsal plan for members
            helpTitle = 'Hilfe - Probenplan';
            helpContent = '<p>Hier sehen Sie den aktuellen Probenplan.</p>' +
                          '<p>Sie können zwischen personalisierter und vollständiger Ansicht wechseln.</p>' +
                          '<p>In der personalisierten Ansicht werden nur Proben angezeigt, die für Ihre Stimme relevant sind.</p>' +
                          '<p>Mit dem Uhr-Symbol können Sie vergangene Proben ein- oder ausblenden.</p>' +
                          '<p>Mit dem Drucker-Symbol können Sie den Probenplan ausdrucken.</p>';
        }
        else if (currentRoute.startsWith('/profile')) {
            // User profile
            helpTitle = 'Hilfe - Profil bearbeiten';
            helpContent = '<p>Hier können Sie Ihre persönlichen Daten und Einstellungen bearbeiten.</p>' +
                          '<p>Ändern Sie Ihr Passwort, Ihren Namen oder Ihre Kontaktdaten nach Bedarf.</p>' +
                          '<p>Vergessen Sie nicht, Ihre Änderungen zu speichern.</p>';
        }
        else {
            // Default help content
            helpContent = '<p>Willkommen im Probenplaner!</p>' +
                          '<p>Verwenden Sie die Navigation, um zwischen den verschiedenen Funktionen zu wechseln.</p>' +
                          '<p>Bei Fragen zur Bedienung klicken Sie auf das Fragezeichen-Symbol.</p>';
        }
        
        Swal.fire({
            title: helpTitle,
            html: helpContent,
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
<?php if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])): ?>
<script>
    const Toast = Swal.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
    
    <?php foreach ($_SESSION['flash_messages'] as $key => $message): ?>
        <?php
        $config = [
            'icon' => $message['type'] === 'error' ? 'error' : ($message['type'] === 'success' ? 'success' : 'info'),
            'title' => htmlspecialchars($message['message'])
        ];
        
        if (isset($message['details']) && $message['details']) {
            $config['html'] = htmlspecialchars($message['message']) . '<br><button id="showDetailsBtn" style="margin-top:10px;" class="swal2-styled">Details anzeigen</button><div id="errorDetails" style="display:none; margin-top:10px; text-align:left; font-size:12px; color:#a94442; background:#f9f2f4; border:1px solid #ebccd1; padding:10px; border-radius:4px; white-space:pre-wrap;">' . htmlspecialchars($message['details']) . '</div>';
            $config['didOpen'] = 'function() { const btn = document.getElementById("showDetailsBtn"); if (btn) { btn.onclick = function() { const details = document.getElementById("errorDetails"); if (details.style.display === "none") { details.style.display = "block"; btn.textContent = "Details ausblenden"; } else { details.style.display = "none"; btn.textContent = "Details anzeigen"; } }; } }';
        }
        ?>
        Toast.fire(<?= json_encode($config) ?>);
    <?php unset($_SESSION['flash_messages'][$key]); endforeach; ?>
</script>
<?php endif; ?>

<?php if (isset($_SESSION['alerts']) && !empty($_SESSION['alerts'])): ?>
<script>
    <?php foreach ($_SESSION['alerts'] as $key => $alert): ?>
        <?php
        $hasDetails = isset($alert[3]) && $alert[3];
        $details = $hasDetails ? htmlspecialchars($alert[3]) : '';
        ?>
        Swal.fire({
            title: '<?= htmlspecialchars($alert[0]) ?>',
            html: `<?= nl2br(htmlspecialchars($alert[1])) ?><?php if ($hasDetails): ?>
                <br><button id="showDetailsBtn_<?= $key ?>" style="margin-top:10px;" class="swal2-styled">Details anzeigen</button>
                <div id="errorDetails_<?= $key ?>" style="display:none; margin-top:10px; text-align:left; font-size:12px; color:#a94442; background:#f9f2f4; border:1px solid #ebccd1; padding:10px; border-radius:4px; white-space:pre-wrap;"><?= $details ?></div>
            <?php endif; ?>`,
            icon: '<?= $alert[2] === 'error' ? 'error' : ($alert[2] === 'success' ? 'success' : 'info') ?>',
            confirmButtonColor: '#478cf4',
            showConfirmButton: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                const btn = document.getElementById('showDetailsBtn_<?= $key ?>');
                const details = document.getElementById('errorDetails_<?= $key ?>');
                if (btn && details) {
                    btn.onclick = function() {
                        if (details.style.display === 'none') {
                            details.style.display = 'block';
                            btn.textContent = 'Details ausblenden';
                        } else {
                            details.style.display = 'none';
                            btn.textContent = 'Details anzeigen';
                        }
                    };
                }
            }
        });
    <?php unset($_SESSION['alerts'][$key]); endforeach; ?>
</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update UI visibility based on current route
    updateUIForCurrentRoute();
    
    // Add event listeners to all internal links for route-based UI updates
    document.querySelectorAll('a[href^="/"]').forEach(function(link) {
        link.addEventListener('click', function() {
            // Get the target route from the link's href
            const route = this.getAttribute('href');
            
            // Update UI visibility after a short delay to allow navigation
            setTimeout(function() {
                updateUIForCurrentRoute();
            }, 100);
        });
    });
});

// Function to update UI visibility based on current route
function updateUIForCurrentRoute() {
    const currentRoute = window.location.pathname;
    
    // Determine if buttons should be shown based on route
    const showHistoryButton = currentRoute.startsWith('/promises');
    
    // Show help button on main feature pages
    const helpRelevantPaths = ['/promises', '/promises/leader', '/promises/admin', 
                              '/rehearsals', '/probenplan', '/profile', '/conductor/profile'];
    
    const showHelpButton = helpRelevantPaths.some(path => currentRoute === path) || 
                          currentRoute.startsWith('/promises/') || 
                          currentRoute.startsWith('/rehearsals/');
    
    // Update UI elements visibility
    document.querySelectorAll('.history-link').forEach(function(element) {
        element.style.display = showHistoryButton ? 'inline-block' : 'none';
    });
    
    document.querySelectorAll('.help-link').forEach(function(element) {
        element.style.display = showHelpButton ? 'inline-block' : 'none';
    });
}

// Update UI immediately when script loads
updateUIForCurrentRoute();
</script>
</body>
</html> 
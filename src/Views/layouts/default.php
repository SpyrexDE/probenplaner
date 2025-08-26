<!DOCTYPE html>
<html lang="de" style="width: 100%; height: 100%;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?= isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : (isset($title) ? $title : APP_NAME) ?></title>
    
    <!-- Theme Configuration -->
    <script type="module">
        import { Config } from 'https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4';
        
        Config.theme = {
            colors: {
                primary: {
                    50: '#eff6ff',
                    100: '#dbeafe', 
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#478cf4',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a'
                },
                success: {
                    50: '#f0fdf4',
                    100: '#dcfce7',
                    200: '#bbf7d0', 
                    300: '#86efac',
                    400: '#4ade80',
                    500: '#53d650',
                    600: '#16a34a',
                    700: '#15803d',
                    800: '#166534',
                    900: '#14532d'
                },
                danger: {
                    50: '#fef2f2',
                    100: '#fee2e2',
                    200: '#fecaca',
                    300: '#fca5a5', 
                    400: '#f87171',
                    500: '#eb554b',
                    600: '#dc2626',
                    700: '#b91c1c',
                    800: '#991b1b',
                    900: '#7f1d1d'
                },
                status: {
                    transparent: 'transparent',
                    red: '#ffe1e1',
                    blue: '#e1ecff',
                    yellow: '#fff9e1', 
                    green: '#ebffe1'
                },
                auth: {
                    bg: '#f1f7fc',
                    accent: '#f4476b',
                    'accent-hover': '#eb3b60'
                }
            },
            fontFamily: {
                'sans': ['Roboto', 'system-ui', 'sans-serif'],
                'brand': ['Fugaz One', 'cursive']
            },
            extend: {
                spacing: {
                    '18': '4.5rem',
                    '100': '25rem'
                },
                zIndex: {
                    '999': '999',
                    '1000': '1000'
                },
                boxShadow: {
                    'soft': '0px 0px 30px rgba(128,128,128,0.4)',
                    'soft-sm': '0px 0px 10px rgba(0,0,0,0.1)',
                    'auth': '1px 1px 5px rgba(0,0,0,0.1)'
                },
                borderRadius: {
                    'xl': '10px'
                },
                transitionDuration: {
                    '250': '250ms'
                },
                animation: {
                    'fade-in': 'fade-in 0.2s ease-out',
                    'scale-in': 'scale-in 0.15s ease-out'
                },
                keyframes: {
                    'fade-in': {
                        '0%': { opacity: '0', transform: 'translateY(10px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' }
                    },
                    'scale-in': {
                        '0%': { opacity: '0', transform: 'scale(0.95)' },
                        '100%': { opacity: '1', transform: 'scale(1)' }
                    }
                }
            }
        };
    </script>
    
    <!-- Custom styles for sidebar behavior -->
    <style>

        
        /* Professional layout system */
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Roboto', sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.4;
            overflow-x: hidden;
        }
        
        /* App Layout Container */
        #wrapper {
            display: flex;
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        
                 /* Professional Sidebar */
         #sidebar-wrapper {
             width: 280px;
             background: #212529;
             position: fixed;
             top: 0;
             left: -280px;
             height: 100vh;
             z-index: 1001;
             transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
             overflow-y: auto;
             box-shadow: 4px 0 20px rgba(0,0,0,0.25);
         }
         
         #wrapper.toggled #sidebar-wrapper {
             left: 0;
         }
         
         #wrapper.toggled::before {
             content: '';
             position: fixed;
             top: 0;
             left: 0;
             right: 0;
             bottom: 0;
             background: rgba(0,0,0,0.5);
             z-index: 1000;
             opacity: 1;
             transition: opacity 0.3s ease;
             pointer-events: auto;
         }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
            width: 100%;
        }
        
        .sidebar-brand {
            height: 80px;
            background: linear-gradient(135deg, #4285f4, #34a853);
            display: flex;
            align-items: center;
            padding: 0 20px;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            width: 100%;
            background: rgba(255,255,255,0.95);
            color: #333;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .user-avatar {
            width: 52px;
            height: 52px;
            margin-right: 12px;
            color: #4285f4;
            font-size: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-info h4 {
            margin: 0 0 2px 0;
            font-size: 15px;
            font-weight: 600;
            color: #333;
        }
        
        .user-info p {
            margin: 0;
            font-size: 13px;
            color: #666;
            opacity: 0.8;
        }
        
        .sidebar-nav li a {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            color: #b8bcc8;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 15px;
            font-weight: 500;
        }
        
        .sidebar-nav li a:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
            text-decoration: none;
            padding-left: 24px;
        }
        
        .sidebar-nav li a.activeTab {
            background: rgba(66, 133, 244, 0.15);
            color: #4285f4;
            border-left: 4px solid #4285f4;
            font-weight: 600;
        }
        
        .sidebar-nav li a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 16px;
        }
        
        /* Top Navigation Bar */
        .top-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 64px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            border-bottom: 1px solid #e8eaed;
        }
        
        .nav-left {
            display: flex;
            align-items: center;
        }
        
        .menu-toggle {
            background: none;
            border: none;
            font-size: 24px;
            color: #478cf4;
            cursor: pointer;
            margin-right: 16px;
            padding: 12px;
            border-radius: 8px;
            transition: all 0.2s ease;
            min-width: 48px;
            min-height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .menu-toggle:hover {
            background-color: rgba(71, 140, 244, 0.1);
            color: #3a7bd5;
            transform: scale(1.05);
        }
        
        .menu-toggle:active {
            transform: scale(0.95);
            background-color: rgba(71, 140, 244, 0.2);
        }
        
        .brand-title {
            font-family: 'Fugaz One', cursive;
            font-size: 28px;
            color: #478cf4;
            text-decoration: none;
            font-weight: 900;
        }
        
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .nav-icon {
            font-size: 20px;
            color: #666;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        
        .nav-icon:hover {
            color: #478cf4;
        }
        
        /* Main Content Area */
        #page-content-wrapper {
            flex: 1;
            padding-top: 64px;
            min-height: 100vh;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @media (min-width: 768px) {
            #wrapper.toggled #page-content-wrapper {
                margin-left: 280px;
                transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
        }
        
        @media (max-width: 767px) {
            #sidebar-wrapper {
                width: 280px;
                left: -280px;
            }
        }
        
        /* Content Area Styling */
        .main-content {
            padding: 8px;
        }
        
        /* Clean and minimal styling */
        .far, .fas { font-size: 21px; }
        
        /* Tree view styles */
        .tree li { cursor: pointer; }
        .treeIcon { margin-top: 2px; margin-right: 5px; margin-left: 8px; }
        .smallTreeIcon { font-size: 1rem; transform: scale(1); transform-origin: 0 0; margin-top: 2px; margin-right: 5px; margin-left: 8px; }
        
        /* Button interactions */
        .checkBtn, .crossBtn, .tree li span, .x-drop-btn:hover { cursor: pointer; }
    </style>
    <!-- Bootstrap CSS for component functionality -->
    <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
    
    <!-- Tailwind CSS for custom styling -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    
    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,600,700">
    <link href="https://fonts.googleapis.com/css2?family=Fugaz+One&display=swap" rel="stylesheet">
    
    <!-- Icon Fonts -->
    <link rel="stylesheet" href="/assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="/assets/fonts/font-awesome.min.css">
    <link rel="stylesheet" href="/assets/fonts/ionicons.min.css">
    <link rel="stylesheet" href="/assets/fonts/fontawesome5-overrides.min.css">
    <link rel="shortcut icon" href="/assets/img/tabIcon.png" type="image/x-icon">
    <link rel="manifest" href="/manifest.json">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/easy-pwa-js@1.0/dist/front.js"></script>
    <script src="/assets/js/jquery.min.js"></script>
    <script src="/assets/js/notifications.js"></script>
    <!-- Tippy.js for tooltips -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/animations/scale.css"/>
</head>
<body>
<?php 
use App\Core\Utilities;
if (isset($_SESSION['username'])): ?>
    <div id="wrapper">
        <!-- Top Navigation -->
        <nav class="top-nav">
            <div class="nav-left">
                <button class="menu-toggle" id="menu-toggle" onclick="(function(){const w=document.getElementById('wrapper');if(w)w.classList.toggle('toggled');})();">
                    <i class="fa fa-bars"></i>
                </button>
                <a href="/" class="brand-title">
                    <?= isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : APP_NAME ?>
                </a>

            </div>
            <div class="nav-actions">
                <?php 
                // Show buttons on relevant routes
                $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                
                // Show history button on promises routes and rehearsals routes
                $showHistoryButton = (strpos($currentUri, '/promises') === 0) || (strpos($currentUri, '/rehearsals') === 0);
                
                // Show help button on all main feature pages
                $showHelpButton = in_array($currentUri, ['/promises', '/promises/leader', '/promises/admin', 
                                                        '/rehearsals', '/probenplan', '/profile', '/conductor/profile']) 
                                 || (strpos($currentUri, '/promises/') === 0)
                                 || (strpos($currentUri, '/rehearsals/') === 0);
                ?>
                
                <?php if ($showHistoryButton): ?>
                <i onclick="openOld();" class="fas fa-history nav-icon"></i>
                <?php endif; ?>
                
                <?php if ($showHelpButton): ?>
                <i onclick="showHelp();" class="fas fa-question-circle nav-icon"></i>
                <?php endif; ?>
            </div>
        </nav>
        
        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <ul class="sidebar-nav">
                <li class="sidebar-brand">
                    <div class="user-profile">
                        <div class="user-avatar">
                            <i class="icon ion-ios-contact"></i>
                        </div>
                        <div class="user-info">
                            <h4><?= Utilities::formatUsername($_SESSION['username'], $_SESSION['role'] ?? 'member', $_SESSION['is_small_group'] ?? false) ?></h4>
                            <p><?= isset($_SESSION['type']) ? str_replace('_', ' ', $_SESSION['type']) : '' ?></p>
                        </div>
                    </div>
                </li>
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
                    echo '<li><a class="' . $active . '" href="' . $item['href'] . '">' . $item['label'] . '</a></li>';
                }
                ?>
            </ul>
        </div>
        <!-- Main Content -->
        <div id="page-content-wrapper">
            <div id="contentPage">
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
<!-- Top Navigation for non-logged in users -->
<nav class="top-nav">
    <div class="nav-left">
        <a href="/" class="brand-title">
            <?= isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : APP_NAME ?>
        </a>
    </div>
    <div class="nav-actions">
        <?php 
        // Show buttons on relevant routes
        $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Show history button on promises routes and rehearsals routes
        $showHistoryButton = (strpos($currentUri, '/promises') === 0) || (strpos($currentUri, '/rehearsals') === 0);
        
        // Show help button on all main feature pages
        $showHelpButton = in_array($currentUri, ['/promises', '/promises/leader', '/promises/admin', 
                                                '/rehearsals', '/probenplan', '/profile', '/conductor/profile']) 
                         || (strpos($currentUri, '/promises/') === 0)
                         || (strpos($currentUri, '/rehearsals/') === 0);
        ?>
        
        <?php if ($showHistoryButton): ?>
        <i onclick="openOld();" class="fas fa-history nav-icon"></i>
        <?php endif; ?>
        
        <?php if ($showHelpButton): ?>
        <i onclick="showHelp();" class="fas fa-question-circle nav-icon"></i>
        <?php endif; ?>
    </div>
</nav>
<?php endif; ?>

<div class="main-content">
    <?= $content ?? '' ?>
</div>
<?php endif; ?>

<!-- Add scripts at the end of the body -->
<script src="/assets/js/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/assets/bootstrap/js/bootstrap.min.js"></script>
<script src="/assets/js/script.min.js"></script>
<script src="/assets/js/tree-view-clickable.js"></script>
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
    <?php foreach ($_SESSION['flash_messages'] as $key => $message): ?>
        (function(){
            const type = '<?= $message['type'] ?>';
            const text = '<?= htmlspecialchars($message['message']) ?>';
            const details = <?= isset($message['details']) && $message['details'] ? json_encode($message['details']) : 'null' ?>;
            if (type === 'error' && details) {
                Swal.fire({
                    title: text,
                    html: `${text}<br><button id="flashDetailsBtn_<?= $key ?>" style="margin-top:10px;" class="swal2-styled">Details anzeigen</button><div id="flashErrorDetails_<?= $key ?>" style="display:none; margin-top:10px; text-align:left; font-size:12px; color:#a94442; background:#f9f2f4; border:1px solid #ebccd1; padding:10px; border-radius:4px; white-space:pre-wrap;">${details}</div>`,
                    icon: 'error',
                    confirmButtonColor: '#478cf4',
                    didOpen: () => {
                        const btn = document.getElementById('flashDetailsBtn_<?= $key ?>');
                        const detailsEl = document.getElementById('flashErrorDetails_<?= $key ?>');
                        if (btn && detailsEl) {
                            btn.onclick = function() {
                                if (detailsEl.style.display === 'none') {
                                    detailsEl.style.display = 'block';
                                    btn.textContent = 'Details ausblenden';
                                } else {
                                    detailsEl.style.display = 'none';
                                    btn.textContent = 'Details anzeigen';
                                }
                            };
                        }
                    }
                });
            } else {
                if (type === 'success') window.notifySuccess(text); else if (type === 'warning') window.notifyInfo(text); else window.notifyInfo(text);
            }
        })();
    <?php unset($_SESSION['flash_messages'][$key]); endforeach; ?>
</script>
<?php endif; ?>

<?php if (isset($_SESSION['alerts']) && !empty($_SESSION['alerts'])): ?>
<script>
    <?php foreach ($_SESSION['alerts'] as $key => $alert): ?>
        (function(){
            const type = '<?= $alert[2] ?>';
            const title = '<?= htmlspecialchars($alert[0]) ?>';
            const message = `<?= nl2br(htmlspecialchars($alert[1])) ?>`;
            const hasDetails = <?= isset($alert[3]) && $alert[3] ? 'true' : 'false' ?>;
            const details = `<?= isset($alert[3]) ? htmlspecialchars($alert[3]) : '' ?>`;
            if (type === 'error') {
                Swal.fire({
                    title: title,
                    html: hasDetails ? `${message}<br><button id="showDetailsBtn_<?= $key ?>" style="margin-top:10px;" class="swal2-styled">Details anzeigen</button><div id="errorDetails_<?= $key ?>" style="display:none; margin-top:10px; text-align:left; font-size:12px; color:#a94442; background:#f9f2f4; border:1px solid #ebccd1; padding:10px; border-radius:4px; white-space:pre-wrap;">${details}</div>` : message,
                    icon: 'error',
                    confirmButtonColor: '#478cf4',
                    showConfirmButton: true,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        const btn = document.getElementById('showDetailsBtn_<?= $key ?>');
                        const detailsEl = document.getElementById('errorDetails_<?= $key ?>');
                        if (btn && detailsEl) {
                            btn.onclick = function() {
                                if (detailsEl.style.display === 'none') {
                                    detailsEl.style.display = 'block';
                                    btn.textContent = 'Details ausblenden';
                                } else {
                                    detailsEl.style.display = 'none';
                                    btn.textContent = 'Details anzeigen';
                                }
                            };
                        }
                    }
                });
            } else if (type === 'success') {
                window.notifySuccess(message.replace(/<br\/>/g, ' '));
            } else {
                window.notifyInfo(message.replace(/<br\/>/g, ' '));
            }
        })();
    <?php unset($_SESSION['alerts'][$key]); endforeach; ?>
</script>
<?php endif; ?>

<script>
  document.addEventListener('DOMContentLoaded', function() {
     // Robust sidebar toggle setup
     const wrapper = document.getElementById('wrapper');
     const sidebar = document.getElementById('sidebar-wrapper');
     const menuToggle = document.getElementById('menu-toggle');
     
     // Primary toggle function
     function toggleSidebar() {
         if (wrapper) {
             wrapper.classList.toggle('toggled');
         }
     }
     
     // Setup menu toggle with multiple handlers for reliability
     if (menuToggle && wrapper) {
         // Modern event listener
         menuToggle.addEventListener('click', function(e) {
             e.preventDefault();
             e.stopPropagation();
             toggleSidebar();
         });
         
         // Backup onclick property
         menuToggle.onclick = function(e) {
             e.preventDefault();
             toggleSidebar();
             return false;
         };
     }
     
     // Outside click and escape key handling
     if (wrapper && sidebar && menuToggle) {
         document.addEventListener('click', function(e) {
             if (wrapper.classList.contains('toggled') && 
                 !sidebar.contains(e.target) && 
                 !menuToggle.contains(e.target)) {
                 wrapper.classList.remove('toggled');
             }
         });
         
         sidebar.addEventListener('click', function(e) {
             e.stopPropagation();
         });
         
         document.addEventListener('keydown', function(e) {
             if (e.key === 'Escape' && wrapper.classList.contains('toggled')) {
                 wrapper.classList.remove('toggled');
             }
         });
     }
     
     // Global function for inline onclick as backup
     window.toggleSidebarMenu = toggleSidebar;
    
     // Handle window resize
     window.addEventListener('resize', function() {
         const w = document.getElementById('wrapper');
         if (window.innerWidth >= 768 && w) {
             w.classList.remove('toggled');
         }
     });
    
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
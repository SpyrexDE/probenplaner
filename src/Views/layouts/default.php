<!DOCTYPE html>
<html lang="de" style="width: 100%; height: 100%;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?= isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : (isset($title) ? $title : APP_NAME) ?></title>
    
    
    
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
            background: #ffffff;
            position: fixed;
            top: 0;
            left: -280px;
            height: 100vh;
            z-index: 1001;
            transition: left 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-right: 1px solid #e5e7eb;
        }
        
        /* Desktop: Always visible */
        @media (min-width: 1200px) {
            #sidebar-wrapper {
                left: 0;
                position: fixed;
            }
            
            #page-content-wrapper {
                margin-left: 280px;
            }
            
            .menu-toggle {
                display: none;
            }
        }
        
        /* Tablet/Mobile: Drawer behavior */
        @media (max-width: 1199px) {
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
                background: rgba(0,0,0,0.3);
                z-index: 1000;
                opacity: 1;
                transition: opacity 0.25s ease;
                pointer-events: auto;
            }
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
            width: 100%;
        }
        
        /* Professional Header */
        .sidebar-header {
            padding: 24px 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e2e8f0;
        }
        
        .orchestra-info {
            margin-bottom: 18px;
            text-align: center;
        }
        
        .orchestra-name {
            font-size: 14px;
            font-weight: 700;
            color: #334155;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 16px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .user-avatar {
            width: 44px;
            height: 44px;
            margin-right: 14px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            font-weight: 700;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }
        
        .user-info {
            flex: 1;
            min-width: 0;
        }
        
        .user-info h4 {
            margin: 0 0 2px 0;
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-info p {
            margin: 0;
            font-size: 12px;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 500;
        }
        
        /* Professional Stats */
        .sidebar-stats {
            padding: 20px;
            background: #fafbfc;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .stats-title {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stats-bar {
            height: 6px;
            background: #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            display: flex;
            margin-bottom: 14px;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .stats-segment {
            height: 100%;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .stats-segment.attending {
            background: linear-gradient(90deg, #10b981, #059669);
        }
        
        .stats-segment.not-attending {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }
        
        .stats-segment.no-response {
            background: linear-gradient(90deg, #94a3b8, #64748b);
        }
        
        .stats-legend {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
        }
        
        .stats-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .stats-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .stats-dot.attending {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .stats-dot.not-attending {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        .stats-dot.no-response {
            background: linear-gradient(135deg, #94a3b8, #64748b);
        }
        
        /* Professional Navigation */
        .sidebar-nav {
            list-style: none;
            padding: 8px 12px;
            margin: 0;
        }
        
        .sidebar-nav li {
            margin: 0 0 4px 0;
        }
        
        .sidebar-nav li a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            position: relative;
        }
        
        .sidebar-nav li a:hover {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            color: #334155;
            text-decoration: none;
            transform: translateX(2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .sidebar-nav li a.activeTab {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
        }
        
        .sidebar-nav li a.activeTab::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 20px;
            background: linear-gradient(180deg, #3b82f6, #1d4ed8);
            border-radius: 0 2px 2px 0;
        }
        
        .sidebar-nav li a i {
            margin-right: 14px;
            width: 18px;
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
    <script src="https://cdn.tailwindcss.com"></script>
    
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
        
        <!-- Modern Sidebar -->
        <div id="sidebar-wrapper">
            <!-- Sidebar Header -->
            <div class="sidebar-header">
                <div class="orchestra-info">
                    <h3 class="orchestra-name"><?= isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : APP_NAME ?></h3>
                </div>
                
                <div class="user-profile">
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="user-info">
                        <h4><?= Utilities::formatUsername($_SESSION['username'], $_SESSION['role'] ?? 'member', $_SESSION['is_small_group'] ?? false) ?></h4>
                        <p><?= isset($_SESSION['type']) ? str_replace('_', ' ', $_SESSION['type']) : '' ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Section -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="sidebar-stats">
                <div class="stats-title">Meine Proben</div>
                <div class="stats-bar" id="sidebar-stats-bar">
                    <div class="stats-segment attending" style="width: 0%;"></div>
                    <div class="stats-segment not-attending" style="width: 0%;"></div>
                    <div class="stats-segment no-response" style="width: 100%;"></div>
                </div>
                <div class="stats-legend">
                    <div class="stats-item">
                        <div class="stats-dot attending"></div>
                        <span id="stats-attending">0</span>
                    </div>
                    <div class="stats-item">
                        <div class="stats-dot not-attending"></div>
                        <span id="stats-not-attending">0</span>
                    </div>
                    <div class="stats-item">
                        <div class="stats-dot no-response"></div>
                        <span id="stats-no-response">0</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Navigation Menu -->
            <ul class="sidebar-nav">
                <?php
                $menu = [];
                if (isset($_SESSION['type']) && $_SESSION['type'] === 'Dirigent') {
                    $menu = [
                        ['label' => 'Termine', 'href' => '/rehearsals', 'page' => 'rehearsals', 'icon' => 'fas fa-calendar-alt'],
                        ['label' => 'Probenplan', 'href' => '/probenplan', 'page' => 'probenplan', 'icon' => 'fas fa-list'],
                        ['label' => 'Rückmeldungen', 'href' => '/promises/admin', 'page' => 'admin', 'icon' => 'fas fa-chart-bar'],
                        ['label' => 'Profil bearbeiten', 'href' => '/conductor/profile', 'page' => 'conductor_profile', 'icon' => 'fas fa-user-cog'],
                        ['label' => 'Orchester bearbeiten', 'href' => '/orchestras/settings', 'page' => 'orchestra_settings', 'icon' => 'fas fa-cog'],
                        ['label' => 'Logout', 'href' => '/logout', 'page' => null, 'icon' => 'fas fa-sign-out-alt'],
                    ];
                } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'leader') {
                    $menu = [
                        ['label' => 'Meine Meldungen', 'href' => '/promises', 'page' => 'promises', 'icon' => 'fas fa-clipboard-check'],
                        ['label' => 'Rückmeldungen', 'href' => '/promises/leader', 'page' => 'leader', 'icon' => 'fas fa-chart-bar'],
                        ['label' => 'Probenplan', 'href' => '/probenplan', 'page' => 'probenplan', 'icon' => 'fas fa-list'],
                        ['label' => 'Profil bearbeiten', 'href' => '/profile', 'page' => 'profile', 'icon' => 'fas fa-user-cog'],
                        ['label' => 'Logout', 'href' => '/logout', 'page' => null, 'icon' => 'fas fa-sign-out-alt'],
                    ];
                } else {
                    $menu = [
                        ['label' => 'Meine Meldungen', 'href' => '/promises', 'page' => 'promises', 'icon' => 'fas fa-clipboard-check'],
                        ['label' => 'Probenplan', 'href' => '/probenplan', 'page' => 'probenplan', 'icon' => 'fas fa-list'],
                        ['label' => 'Profil bearbeiten', 'href' => '/profile', 'page' => 'profile', 'icon' => 'fas fa-user-cog'],
                        ['label' => 'Logout', 'href' => '/logout', 'page' => null, 'icon' => 'fas fa-sign-out-alt'],
                    ];
                }
                foreach ($menu as $item) {
                    $active = isset($item['page']) && $currentPage === $item['page'] ? 'activeTab' : '';
                    echo '<li><a class="' . $active . '" href="' . $item['href'] . '">';
                    echo '<i class="' . $item['icon'] . '"></i>';
                    echo $item['label'] . '</a></li>';
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
         // Only close sidebar on mobile/tablet breakpoint
         if (window.innerWidth < 1200 && w) {
             w.classList.remove('toggled');
         }
     });
     
     // Function to load user statistics
     window.loadUserStats = function() {
         console.log('Loading user stats...');
         
         // Try to get stats from current page first
         let stats = {
             attending: 0,
             not_attending: 0,
             no_response: 0,
             total: 0
         };
         
         // If we're on the promises page, count the actual rehearsal cards
         if (window.location.pathname === '/promises') {
             const rehearsalCards = document.querySelectorAll('.rehearsal-card');
             
             rehearsalCards.forEach(card => {
                 stats.total++;
                 
                 // Check if user has responded to this rehearsal
                 if (card.classList.contains('greenOut')) {
                     stats.attending++;
                 } else if (card.classList.contains('redOut')) {
                     stats.not_attending++;
                 } else {
                     stats.no_response++;
                 }
             });
             
             console.log('Stats from current page:', stats);
         } else {
             // If not on promises page, try to load via AJAX
             fetch('/promises', {
                 method: 'GET',
                 headers: {
                     'Content-Type': 'text/html'
                 }
             })
             .then(response => response.text())
             .then(html => {
                 // Parse the HTML to extract rehearsal data
                 const parser = new DOMParser();
                 const doc = parser.parseFromString(html, 'text/html');
                 
                 // Count rehearsals by status
                 const ajaxStats = {
                     attending: 0,
                     not_attending: 0,
                     no_response: 0,
                     total: 0
                 };
                 
                 // Look for rehearsal cards and their status
                 const rehearsalCards = doc.querySelectorAll('.rehearsal-card');
                 
                 rehearsalCards.forEach(card => {
                     ajaxStats.total++;
                     
                     // Check if user has responded to this rehearsal
                     if (card.classList.contains('greenOut')) {
                         ajaxStats.attending++;
                     } else if (card.classList.contains('redOut')) {
                         ajaxStats.not_attending++;
                     } else {
                         ajaxStats.no_response++;
                     }
                 });
                 
                 console.log('Stats loaded via AJAX:', ajaxStats);
                 updateStatsDisplay(ajaxStats);
             })
             .catch(error => {
                 console.error('AJAX stats loading failed:', error);
                 // Use stats from current page if available
                 if (stats.total > 0) {
                     updateStatsDisplay(stats);
                 } else {
                     // Fallback to zero stats
                     updateStatsDisplay({ attending: 0, not_attending: 0, no_response: 0, total: 0 });
                 }
             });
             
             // Return early since we're handling stats asynchronously
             return;
         }
         
         // Update display with stats from current page
         updateStatsDisplay(stats);
     }
     
     // Function to update stats display
     function updateStatsDisplay(stats) {
         const total = stats.total || 1; // Avoid division by zero
         const attendingPercent = ((stats.attending || 0) / total) * 100;
         const notAttendingPercent = ((stats.not_attending || 0) / total) * 100;
         const noResponsePercent = ((stats.no_response || 0) / total) * 100;
         
         // Update progress bar segments
         const attendingSegment = document.querySelector('.stats-segment.attending');
         const notAttendingSegment = document.querySelector('.stats-segment.not-attending');
         const noResponseSegment = document.querySelector('.stats-segment.no-response');
         
         if (attendingSegment) attendingSegment.style.width = attendingPercent + '%';
         if (notAttendingSegment) notAttendingSegment.style.width = notAttendingPercent + '%';
         if (noResponseSegment) noResponseSegment.style.width = noResponsePercent + '%';
         
         // Update legend numbers
         const attendingText = document.getElementById('stats-attending');
         const notAttendingText = document.getElementById('stats-not-attending');
         const noResponseText = document.getElementById('stats-no-response');
         
         if (attendingText) attendingText.textContent = stats.attending || 0;
         if (notAttendingText) notAttendingText.textContent = stats.not_attending || 0;
         if (noResponseText) noResponseText.textContent = stats.no_response || 0;
     }
    
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
    
    // Load user statistics for all logged-in users
    <?php if (isset($_SESSION['user_id'])): ?>
    loadUserStats();
    
    // Also update stats when page becomes visible (e.g., after tab switch)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && typeof window.loadUserStats === 'function') {
            console.log('Page became visible, updating stats...');
            setTimeout(function() {
                window.loadUserStats();
            }, 200);
        }
    });
    <?php endif; ?>
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
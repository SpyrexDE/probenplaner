<!DOCTYPE html>
<html lang="de" class="w-full h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Probenplaner</title>
    
    <!-- PWA Meta Tags -->
    <meta name="application-name" content="Probenplaner">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Probenplaner">
    <meta name="description" content="App zum Probenmanagement">
    <meta name="format-detection" content="telephone=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#ffffff">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="/assets/img/Logo.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/assets/img/Logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/Logo.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/assets/img/Logo.png">
    
    <!-- Microsoft Tiles -->
    <meta name="msapplication-TileColor" content="#478cf4">
    <meta name="msapplication-TileImage" content="/assets/img/Logo.png">
    <meta name="msapplication-config" content="/browserconfig.xml">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Aldrich&family=Goldman:wght@400;700&family=Kantumruy+Pro:ital,wght@0,100..700;1,100..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Sansation:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700&display=swap" rel="stylesheet">
    
    <!-- Theme and Component Styles -->
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/assets/css/focus-removal.css">
    
    <!-- Vanilla CSS Components -->
    <!-- Bootstrap CSS removed - using custom components instead -->
    
    <!-- Tailwind CSS for utility classes -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#478cf4',
                            50: '#f0f7ff',
                            100: '#dbeafe',
                            200: '#bfdbfe', 
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#478cf4',
                            600: '#3a7bd5',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        success: {
                            DEFAULT: '#10b981',
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        },
                        error: {
                            DEFAULT: '#ef4444',
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d',
                        }
                    },
                    fontFamily: {
                        'sans': ['Roboto', 'sans-serif'],
                    },
                    spacing: {
                        '1': '0.25rem',
                        '2': '0.5rem',
                        '3': '0.75rem',
                        '4': '1rem',
                        '5': '1.25rem',
                        '6': '1.5rem',
                        '8': '2rem',
                        '10': '2.5rem',
                        '12': '3rem',
                        '16': '4rem',
                        '20': '5rem',
                        '24': '6rem',
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,600,700">
    
    <!-- Icon Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
    <link rel="shortcut icon" href="/assets/img/Logo.png" type="image/x-icon">
    <link rel="manifest" href="/manifest.json">
    
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/assets/js/jquery.min.js"></script>
    <script src="/assets/js/notifications.js"></script>
    
    <!-- Tippy.js for tooltips -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/animations/scale.css"/>
</head>
<body class="bg-gray-50 text-gray-900 font-sans overflow-x-hidden">




<?php 
use App\Core\Utilities;
use App\Core\Version;
if (isset($_SESSION['username'])): ?>
    <div id="wrapper" class="flex min-h-screen transition-all duration-slow">
        <!-- Top Navigation -->
        <nav class="top-nav">
            <div class="top-nav-left">
                <button class="top-nav-menu-toggle" id="menu-toggle" onclick="(function(){const w=document.getElementById('wrapper');if(w)w.classList.toggle('toggled');})();">
                    <i class="fa fa-bars"></i>
                </button>
<?php
                // Function to get page title based on current page
                function getPageTitle($currentPage) {
                    $pageTitles = [
                        'admin' => 'Rückmeldungen',
                        'rehearsals' => 'Termine',
                        'probenplan' => 'Probenplan',
                        'conductor_profile' => 'Profil bearbeiten',
                        'orchestra_settings' => 'Orchester bearbeiten',
                        'promises' => 'Meine Meldungen',
                        'leader' => 'Rückmeldungen',
                        'profile' => 'Profil bearbeiten',
                    ];
                    
                    return isset($pageTitles[$currentPage]) ? $pageTitles[$currentPage] : 'Probenplaner';
                }
                
                $displayTitle = isset($currentPage) ? getPageTitle($currentPage) : 'Probenplaner';
                ?>
                <div class="top-nav-title">
                    <?= $displayTitle ?>
                </div>
            </div>
            <div class="top-nav-actions">
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
                
                <?php // History button removed - now using scrollable interface ?>
                
                <?php if ($showHelpButton): ?>
                <i onclick="showHelp();" class="fas fa-question-circle top-nav-icon"></i>
                <?php endif; ?>
            </div>
        </nav>
        
        <!-- Sidebar Overlay for Mobile -->
        <div class="sidebar-overlay" onclick="document.getElementById('wrapper').classList.remove('toggled');"></div>
        
        <!-- Modern Sidebar -->
        <div id="sidebar-wrapper" class="sidebar">
            <!-- Compact Header -->
            <div class="sidebar-header">
                <div class="sidebar-user">
                    <div class="sidebar-avatar">
                        <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="sidebar-info">
                        <div class="sidebar-name">
                            <?= $_SESSION['username'] ?? 'User' ?>
                            <?php 
                            $userData = [
                                'role' => $_SESSION['role'] ?? 'member',
                                'is_small_group' => $_SESSION['is_small_group'] ?? false
                            ];
                            echo \App\Core\Utilities::generateUserBadges($userData);
                            ?>
                        </div>
                        <div class="sidebar-details">
                            <?php 
                            $parts = [];
                            
                            // Orchestra (abbreviated if too long)
                            $orchestra = isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : APP_NAME;
                            if (strlen($orchestra) > 12) {
                                $orchestra = substr($orchestra, 0, 9) . '...';
                            }
                            $parts[] = '<span class="orchestra">' . $orchestra . '</span>';
                            
                            // Section/Instrument
                            if (isset($_SESSION['type'])) {
                                $parts[] = str_replace('_', ' ', $_SESSION['type']);
                            }
                            

                            
                            echo implode(' · ', $parts);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Section -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="sidebar-stats">
                <?php if (isset($_SESSION['type']) && $_SESSION['type'] === 'Dirigent'): ?>
                <div class="sidebar-stats-header">
                    <div class="sidebar-stats-title">Probe</div>
                    <div class="sidebar-stats-date" id="next-rehearsal-date"></div>
                </div>
                <?php else: ?>
                <div class="sidebar-stats-title">Meine Proben</div>
                <?php endif; ?>
                <div class="sidebar-stats-bar" id="sidebar-stats-bar">
                    <div class="sidebar-stats-segment attending"></div>
                    <div class="sidebar-stats-segment not-attending"></div>
                    <div class="sidebar-stats-segment no-response"></div>
                </div>
                <div class="sidebar-stats-legend">
                    <div class="sidebar-stats-item">
                        <div class="sidebar-stats-dot attending"></div>
                        <span id="stats-attending">0</span>
                    </div>
                    <div class="sidebar-stats-item">
                        <div class="sidebar-stats-dot not-attending"></div>
                        <span id="stats-not-attending">0</span>
                    </div>
                    <div class="sidebar-stats-item">
                        <div class="sidebar-stats-dot no-response"></div>
                        <span id="stats-no-response">0</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Navigation Menu -->
            <nav class="sidebar-nav">
                <ul class="sidebar-nav-list">
                <?php
                $menu = [];
                if (isset($_SESSION['type']) && $_SESSION['type'] === 'Dirigent') {
                    $menu = [
                        ['label' => 'Rückmeldungen', 'href' => '/promises/admin', 'page' => 'admin', 'icon' => 'fas fa-chart-bar'],
                        ['label' => 'Termine', 'href' => '/rehearsals', 'page' => 'rehearsals', 'icon' => 'fas fa-calendar-alt'],
                        ['label' => 'Probenplan', 'href' => '/probenplan', 'page' => 'probenplan', 'icon' => 'fas fa-list'],
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
                    $active = isset($item['page']) && $currentPage === $item['page'] ? 'active' : '';
                    echo '<li class="sidebar-nav-item"><a class="sidebar-nav-link ' . $active . '" href="' . $item['href'] . '">';
                    echo '<i class="sidebar-nav-icon ' . $item['icon'] . '"></i>';
                    echo $item['label'] . '</a></li>';
                }
                ?>
                </ul>
            </nav>
            
            <!-- PWA Install Card -->
            <div id="pwa-install-card" class="sidebar-install-card" style="display: none;" onclick="installPWA()">
                <div class="sidebar-install-content">
                    <i class="sidebar-install-icon fas fa-download"></i>
                    <div class="sidebar-install-text">
                        <div class="sidebar-install-title">App installieren</div>
                        <div class="sidebar-install-subtitle">Für bessere Performance</div>
                    </div>
                </div>
            </div>
            
            <!-- Version Footer -->
            <div class="sidebar-footer">
                <div class="sidebar-version">
                    Probenplaner · <?php 
                    echo Version::getTag();
                    ?>
                </div>
            </div>
        </div>
        <!-- Main Content -->
        <div id="page-content-wrapper" class="page-content main-content-with-sidebar">
            <div id="contentPage" class="page-content-inner">
                <?= $content ?? '' ?>
            </div>
        </div>
    </div>
<?php else: ?>
<?php 
// Hide topbar on login, register, and admin verify pages
$hideNavbar = false;
if (isset($currentPage) && ($currentPage === 'login' || $currentPage === 'register' || $currentPage === 'create_orchestra')) {
    $hideNavbar = true;
}
?>

<?php if (!$hideNavbar): ?>
<!-- Top Navigation for non-logged in users -->
<nav class="top-nav">
    <div class="top-nav-left">
        <div class="top-nav-title">
            Probenplaner
        </div>
    </div>
    <div class="top-nav-actions">
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
        
        <?php // History button removed - now using scrollable interface ?>
        
        <?php if ($showHelpButton): ?>
        <i onclick="showHelp();" class="fas fa-question-circle top-nav-icon"></i>
        <?php endif; ?>
    </div>
</nav>
<?php endif; ?>

<div class="page-content-inner">
    <?= $content ?? '' ?>
</div>
<?php endif; ?>

<!-- Add scripts at the end of the body -->
<script src="/assets/js/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Bootstrap JS removed - using custom components instead -->
<script src="/assets/js/collapse.js"></script>
<script src="/assets/js/dropdown.js"></script>
<script src="/assets/js/tooltip.js"></script>
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
            helpContent = '<div style="text-align: left;">' +
                          '<h4><i class="fas fa-table"></i> Übersicht</h4>' +
                          '<p>Diese Seite zeigt alle Rückmeldungen zu den Proben in einer übersichtlichen Dashboard-Ansicht.</p>' +
                          '<p><strong>Proben-Karten:</strong> Jede Probe wird als Karte mit Datum, Uhrzeit, Ort und Teilnahme-Statistiken angezeigt.</p>' +
                          '<p><strong>Farbkodierung:</strong> Die Teilnahme-Balken sind farbkodiert - Grün für Zusagen, Rot für Absagen, Grau für fehlende Rückmeldungen.</p>' +
                          
                          '<h4><i class="fas fa-users"></i> Teilnehmer-Details</h4>' +
                          '<p><strong>Aufklappbare Bereiche:</strong> Klicken Sie auf die Registerbezeichnungen (z.B. "Violine 1", "Blechbläser"), um die einzelnen Mitglieder zu sehen.</p>' +
                          '<p><strong>Status-Icons:</strong> ✓ = Teilnahme, ✗ = Absage, ? = Keine Rückmeldung</p>' +
                          '<p><strong>Notizen einsehen:</strong> Absage-Begründungen und Notizen werden direkt neben den Namen angezeigt (z.B. "Notiz: Krank" oder "Notiz: Terminkonflikt").</p>' +
                          
                          '<h4><i class="fas fa-brain"></i> Proben-Insights (Beta)</h4>' +
                          '<p>Wenn in den Orchester-Einstellungen aktiviert, sehen Sie zusätzlich:</p>' +
                          '<p><strong>Kritische Register:</strong> Register mit besonders niedriger Teilnahme werden hervorgehoben.</p>' +
                          '<p><strong>Auffälligkeiten:</strong> Das System erkennt automatisch ungewöhnliche Muster:</p>' +
                          '<ul style="margin-left: 20px;">' +
                          '<li><strong>Teilnahme-Anomalien:</strong> Ungewöhnlich hohe/niedrige Teilnahme verglichen mit der Historie</li>' +
                          '<li><strong>Rückmeldungs-Anomalien:</strong> Ungewöhnlich wenige Rückmeldungen in bestimmten Registern</li>' +
                          '<li><strong>Trends:</strong> Langfristige Veränderungen der Teilnahme-Bereitschaft</li>' +
                          '<li><strong>Rekorde:</strong> Neue Höchst- oder Tiefstände bei Teilnahme oder Rückmeldungen</li>' +
                          '</ul>' +
                          '<p><em>Diese Insights helfen dabei, Probleme frühzeitig zu erkennen und gezielt zu handeln.</em></p>' +
                          
                          '<h4><i class="fas fa-filter"></i> Navigation & Filter</h4>' +
                          '<p><strong>Zeitraum-Filter:</strong> Mit dem Uhren-Symbol können Sie vergangene Proben ein-/ausblenden.</p>' +
                          '<p><strong>Drucken:</strong> Nutzen Sie das Drucker-Symbol für eine druckfreundliche Ansicht.</p>' +
                          '</div>';
        } 
        else if (currentRoute.startsWith('/promises/leader')) {
            // Group leader view of responses
            helpTitle = 'Hilfe - Gruppen-Rückmeldungen';
            helpContent = '<div style="text-align: left;">' +
                          '<h4><i class="fas fa-users"></i> Gruppen-Übersicht</h4>' +
                          '<p>Als Stimmführer sehen Sie hier die Rückmeldungen Ihrer Instrumentengruppe in einer übersichtlichen Dashboard-Ansicht.</p>' +
                          
                          '<h4><i class="fas fa-list"></i> Proben-Karten</h4>' +
                          '<p><strong>Probe-Informationen:</strong> Jede Probe wird als Karte mit Datum, Uhrzeit, Ort und Teilnahme-Statistiken Ihrer Gruppe angezeigt.</p>' +
                          '<p><strong>Farbkodierung:</strong> Grün = Zusagen, Rot = Absagen, Grau = Fehlende Rückmeldungen</p>' +
                          '<p><strong>Mitglieder-Details:</strong> Die Namen Ihrer Gruppenmitglieder sind mit ihrem Status aufgelistet.</p>' +
                          '<p><strong>Status-Icons:</strong> ✓ = Teilnahme, ✗ = Absage, ? = Keine Rückmeldung</p>' +
                          
                          '<h4><i class="fas fa-comment-dots"></i> Notizen einsehen</h4>' +
                          '<p>Absage-Begründungen und Notizen werden direkt neben den Mitgliedernamen angezeigt.</p>' +
                          '<p>Dies hilft Ihnen, die Gründe für Absagen auf einen Blick zu erkennen und bei Bedarf Rücksprache zu halten.</p>' +
                          
                          '<h4><i class="fas fa-clock"></i> Navigation</h4>' +
                          '<p><strong>Zeitraum-Filter:</strong> Mit dem Uhren-Symbol können Sie vergangene Proben ein-/ausblenden.</p>' +
                          '<p><strong>Drucken:</strong> Das Drucker-Symbol erstellt eine druckfreundliche Übersicht.</p>' +
                          
                          '<p><em>Hinweis: Die erweiterten Proben-Insights sind für Stimmführer nicht sichtbar - diese stehen nur der Dirigentin/dem Dirigenten zur Verfügung.</em></p>' +
                          '</div>';
        }
        else if (currentRoute.startsWith('/promises')) {
            // Individual member view of their promises
            helpTitle = 'Hilfe - Meine Rückmeldungen';
            helpContent = '<div style="text-align: left;">' +
                          '<h4><i class="fas fa-calendar-check"></i> Rückmeldungen verwalten</h4>' +
                          '<p>Hier können Sie Ihre An- und Abmeldungen für kommende Proben verwalten. Die Proben werden in einer modernen Dashboard-Ansicht angezeigt.</p>' +
                          
                          '<h4><i class="fas fa-mouse-pointer"></i> Teilnahme bestätigen/absagen</h4>' +
                          '<p><strong>Klicken zum Antworten:</strong> Klicken Sie auf eine beliebige Stelle einer Probe-Karte, um Ihre Teilnahme zu bestätigen oder abzusagen.</p>' +
                          '<p><strong>Status-Anzeige:</strong> Ihre aktuelle Rückmeldung wird farblich hervorgehoben:</p>' +
                          '<ul style="margin-left: 20px;">' +
                          '<li><strong>Grün:</strong> Sie haben zugesagt</li>' +
                          '<li><strong>Rot:</strong> Sie haben abgesagt</li>' +
                          '<li><strong>Grau:</strong> Noch keine Rückmeldung</li>' +
                          '</ul>' +
                          
                          '<h4><i class="fas fa-comment"></i> Notizen hinzufügen</h4>' +
                          '<p><strong>Absage-Begründung:</strong> Bei einer Absage können Sie optional einen Grund angeben - dies hilft der Dirigentin/dem Dirigenten bei der Planung.</p>' +
                          '<p><strong>Notizen bearbeiten:</strong> Sie können Ihre Notizen jederzeit nachträglich bearbeiten, indem Sie erneut auf die Probe klicken.</p>' +
                          
                          '<h4><i class="fas fa-eye"></i> Sichtbarkeit</h4>' +
                          '<p><strong>Relevante Proben:</strong> Es werden nur Proben angezeigt, bei denen Ihr Instrument/Register benötigt wird.</p>' +
                          '<p><strong>Zeitraum:</strong> Vergangene Proben werden automatisch ausgeblendet, um die Übersicht aktuell zu halten.</p>' +
                          '<p><strong>Uhren-Symbol:</strong> Mit dem Uhren-Symbol können Sie trotzdem vergangene Proben einblenden.</p>' +
                          '</div>';
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
    
    // openOld() function removed - now using scrollable interface with date separator
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
     // Function to trigger container-app fade-in
     function triggerContainerFadeIn() {
       const containerApps = document.querySelectorAll('.container-app');
       containerApps.forEach(function(container) {
         container.classList.add('fade-in');
       });
     }
     
     // Check if page has scroll positioning logic (like date separator)
     const separator = document.getElementById('dateSeparator');
     if (separator && !window.location.search.includes('showOld')) {
       // Listen for scroll positioning completion, then fade in
       document.addEventListener('scrollPositioningComplete', function() {
         triggerContainerFadeIn();
       });
       
       // Fallback timeout in case the event doesn't fire
       setTimeout(function() {
         triggerContainerFadeIn();
       }, 300);
     } else {
       // No scroll positioning needed, fade in immediately
       setTimeout(function() {
         triggerContainerFadeIn();
       }, 50); // Small delay to ensure DOM is fully ready
     }
     
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
     
    // Function to load user statistics with retry mechanism
    window.loadUserStats = function(retryCount = 0) {
        const MAX_RETRIES = 2;
        
        // Set loading state first (but only on initial call, not retries)
        if (retryCount === 0) {
            setStatsLoadingState(true);
        }
        
        // Use the proper API endpoint instead of scraping HTML
        fetch('/api/user-stats', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.stats) {
                updateStatsDisplay(data.stats);
                setStatsLoadingState(false);
            } else {
                console.error('API returned error:', data.error || 'Unknown error');
                // Try again if we haven't exceeded max retries
                if (retryCount < MAX_RETRIES) {
                    setTimeout(() => window.loadUserStats(retryCount + 1), 1000 * (retryCount + 1));
                } else {
                    // Fallback to zero stats but indicate error state
                    updateStatsDisplay({ attending: 0, not_attending: 0, no_response: 0, total: 0 }, true);
                    setStatsLoadingState(false);
                }
            }
        })
        .catch(error => {
            console.error('Failed to load stats via API:', error);
            // Try again if we haven't exceeded max retries
            if (retryCount < MAX_RETRIES) {
                setTimeout(() => window.loadUserStats(retryCount + 1), 1000 * (retryCount + 1));
            } else {
                // Fallback to zero stats but indicate error state
                updateStatsDisplay({ attending: 0, not_attending: 0, no_response: 0, total: 0 }, true);
                setStatsLoadingState(false);
            }
        });
    }
     
     // Function to set loading state for stats
     function setStatsLoadingState(isLoading) {
         const dateElement = document.getElementById('next-rehearsal-date');
         const attendingText = document.getElementById('stats-attending');
         const notAttendingText = document.getElementById('stats-not-attending');
         const noResponseText = document.getElementById('stats-no-response');
         
         if (isLoading) {
             // Show loading state
             if (dateElement) dateElement.textContent = 'Lade...';
             if (attendingText) attendingText.textContent = '-';
             if (notAttendingText) notAttendingText.textContent = '-';
             if (noResponseText) noResponseText.textContent = '-';
         }
     }
     
     // Function to update stats display
     function updateStatsDisplay(stats, isError = false) {
         const total = stats.total || 1; // Avoid division by zero
         const attendingPercent = ((stats.attending || 0) / total) * 100;
         const notAttendingPercent = ((stats.not_attending || 0) / total) * 100;
         const noResponsePercent = ((stats.no_response || 0) / total) * 100;
         
         // Update progress bar segments
        const attendingSegment = document.querySelector('.sidebar-stats-segment.attending');
        const notAttendingSegment = document.querySelector('.sidebar-stats-segment.not-attending');
        const noResponseSegment = document.querySelector('.sidebar-stats-segment.no-response');
         
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

         // If this is conductor stats, update the next rehearsal display
         const dateElement = document.getElementById('next-rehearsal-date');
         const titleElement = document.querySelector('.sidebar-stats-header .sidebar-stats-title');
         
         if (stats.next_rehearsal) {
             if (dateElement) {
                 dateElement.textContent = stats.next_rehearsal.date_formatted || stats.next_rehearsal.date;
             }
             
             if (titleElement) {
                 // Show rehearsal type if it's not "Probe", otherwise just "Probe"
                 const rehearsalType = stats.next_rehearsal.type || 'Probe';
                 titleElement.textContent = rehearsalType;
             }
         } else if (isError && dateElement) {
             // Show error state for conductor view
             dateElement.textContent = 'Fehler beim Laden';
         } else if (dateElement && titleElement) {
             // Clear loading text if no rehearsal and no error (conductor view)
             dateElement.textContent = 'Keine Proben';
         }
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
    // Load stats immediately when page loads
    loadUserStats();
    
    // Also update stats when page becomes visible (e.g., after tab switch)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && typeof window.loadUserStats === 'function') {
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
    const showHistoryButton = currentRoute.startsWith('/promises') || currentRoute.startsWith('/rehearsals');
    
    // Show help button on main feature pages
    const helpRelevantPaths = ['/promises', '/promises/leader', '/promises/admin', 
                              '/rehearsals', '/probenplan', '/profile', '/conductor/profile'];
    
    const showHelpButton = helpRelevantPaths.some(path => currentRoute === path) || 
                          currentRoute.startsWith('/promises/') || 
                          currentRoute.startsWith('/rehearsals/');
    
            // Update UI elements visibility
        document.querySelectorAll('.top-nav-icon.fa-history').forEach(function(element) {
            element.style.display = showHistoryButton ? 'inline-block' : 'none';
        });
        
        document.querySelectorAll('.top-nav-icon.fa-question-circle').forEach(function(element) {
            element.style.display = showHelpButton ? 'inline-block' : 'none';
        });
}

// Update UI immediately when script loads
updateUIForCurrentRoute();

// PWA Service Worker Registration with Version Control
if ('serviceWorker' in navigator) {
    // Store current app version from server (use tag for PWA stability)
    window.APP_VERSION = '<?php echo Version::getTag(); ?>';
    window.APP_ENV = '<?php echo APP_ENV; ?>';
    
    window.addEventListener('load', function() {
        // Register dynamic service worker (no timestamp - let version handle updates)
        const swUrl = '/dynamic-sw.php';
        
        navigator.serviceWorker.register(swUrl, {
            scope: '/',
            updateViaCache: 'none'  // Always check for SW updates
        }).then(function(registration) {
            console.log('Dynamic Service Worker registered:', registration.scope, 'Version:', window.APP_VERSION);
            
            // Check for updates immediately and periodically
            function checkForUpdates() {
                registration.update().then(() => {
                    console.log('Service Worker update check completed');
                });
            }
            
            // Initial update check
            checkForUpdates();
            
            // Periodic update checks every 30 seconds in production/test
            if (window.APP_ENV !== 'development') {
                setInterval(checkForUpdates, 30000);
            }
            
            // Listen for service worker updates (only show notifications in production)
            registration.addEventListener('updatefound', function() {
                const newWorker = registration.installing;
                console.log('New Service Worker found:', newWorker);
                
                newWorker.addEventListener('statechange', function() {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        // New version available, show update notification
                        if (window.Swal) {
                            Swal.fire({
                                title: 'Update verfügbar',
                                text: 'Eine neue Version der App ist verfügbar. Die Seite wird neu geladen um die neueste Version zu laden.',
                                icon: 'info',
                                showCancelButton: true,
                                confirmButtonText: 'Jetzt aktualisieren',
                                cancelButtonText: 'Später',
                                confirmButtonColor: '#478cf4',
                                allowOutsideClick: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Show loading state
                                    Swal.fire({
                                        title: 'Update wird durchgeführt...',
                                        text: 'Bitte warten Sie, während die neue Version geladen wird.',
                                        icon: 'info',
                                        allowOutsideClick: false,
                                        allowEscapeKey: false,
                                        showConfirmButton: false,
                                        willOpen: () => {
                                            Swal.showLoading();
                                        }
                                    });
                                    
                                    // Request service worker to clear old caches
                                    if (navigator.serviceWorker.controller) {
                                        navigator.serviceWorker.controller.postMessage({
                                            type: 'CLEAR_OLD_CACHES'
                                        });
                                    } else {
                                        // Fallback if no service worker controller
                                        window.location.reload(true);
                                    }
                                }
                            });
                        } else {
                            // Fallback if SweetAlert is not available
                            if (confirm('Eine neue Version ist verfügbar. Jetzt aktualisieren?')) {
                                // Request service worker to clear old caches
                                if (navigator.serviceWorker.controller) {
                                    navigator.serviceWorker.controller.postMessage({
                                        type: 'CLEAR_OLD_CACHES'
                                    });
                                    // The reload will happen when we receive the CACHE_CLEARED message
                                } else {
                                    // Fallback if no service worker controller
                                    window.location.reload(true);
                                }
                            }
                        }
                    }
                });
            });
            
            // Listen for messages from service worker
            navigator.serviceWorker.addEventListener('message', event => {
                if (event.data && event.data.type === 'VERSION_AVAILABLE') {
                    console.log('New service worker version available:', event.data.version);
                    // Version is available but caches are not cleared yet - user needs to confirm
                } else if (event.data && event.data.type === 'CACHE_CLEARED') {
                    console.log('Service Worker caches cleared:', event.data.success);
                    
                    if (event.data.success) {
                        // Cache clearing successful, now reload to get fresh content
                        console.log('Cache clearing successful, reloading page...');
                        window.location.reload(true);
                    } else {
                        // Cache clearing failed, show error and reload anyway
                        console.error('Cache clearing failed:', event.data.error);
                        Swal.fire({
                            title: 'Update-Fehler',
                            text: 'Cache konnte nicht vollständig gelöscht werden, aber das Update wird trotzdem fortgesetzt.',
                            icon: 'warning',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#478cf4'
                        }).then(() => {
                            window.location.reload(true);
                        });
                    }
                }
            });
            
        }).catch(function(error) {
            console.error('Service Worker registration failed:', error);
            // In development, this is expected and okay
            if (window.APP_ENV === 'development') {
                console.log('Service Worker registration failed in development - this is normal');
            }
        });
        
        // Listen for service worker controller changes (when SW takes control)
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            console.log('Service Worker controller changed - new version is now active');
            // Optional: Reload the page to use the new service worker
            // window.location.reload();
        });
    });
    
    // Manual version check function (can be called from anywhere)
    window.checkAppVersion = function() {
        if (!navigator.serviceWorker.controller) {
            console.log('No service worker controller available for version check');
            return;
        }
        
        const messageChannel = new MessageChannel();
        messageChannel.port1.onmessage = function(event) {
            if (event.data && event.data.type === 'VERSION_INFO') {
                console.log('Current SW version:', event.data.version);
                console.log('Client app version:', window.APP_VERSION);
                
                if (event.data.version !== window.APP_VERSION) {
                    console.log('Version mismatch detected - triggering update');
                    location.reload(true);
                }
            }
        };
        
        navigator.serviceWorker.controller.postMessage(
            { type: 'CHECK_VERSION' },
            [messageChannel.port2]
        );
    };
}

// PWA Installation Prompt
let deferredPrompt;
const installCard = document.getElementById('pwa-install-card');

window.addEventListener('beforeinstallprompt', function(e) {
    // Prevent Chrome 67 and earlier from automatically showing the prompt
    e.preventDefault();
    // Stash the event so it can be triggered later
    deferredPrompt = e;
    // Show the install card
    if (installCard) {
        installCard.style.display = 'block';
    }
});

function installPWA() {
    if (deferredPrompt) {
        // Show the install prompt
        deferredPrompt.prompt();
        // Wait for the user to respond to the prompt
        deferredPrompt.userChoice.then(function(choiceResult) {
            if (choiceResult.outcome === 'accepted') {
                console.log('User accepted the install prompt');
            } else {
                console.log('User dismissed the install prompt');
            }
            deferredPrompt = null;
            if (installCard) {
                installCard.style.display = 'none';
            }
        });
    }
}

// Hide install card if app is already installed
window.addEventListener('appinstalled', function() {
    console.log('PWA was installed');
    if (installCard) {
        installCard.style.display = 'none';
    }
    deferredPrompt = null;
    
    // Show success message
    if (window.notifySuccess) {
        window.notifySuccess('App erfolgreich installiert!');
    }
});

// Hide install card on mobile if already in standalone mode
if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
    if (installCard) {
        installCard.style.display = 'none';
    }
}
</script>
</body>
</html> 
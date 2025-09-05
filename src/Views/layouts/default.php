<!DOCTYPE html>
<html lang="de" class="w-full h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?= isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : (isset($title) ? $title : APP_NAME) ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Aldrich&family=Goldman:wght@400;700&family=Kantumruy+Pro:ital,wght@0,100..700;1,100..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Sansation:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700&display=swap" rel="stylesheet">
    
    <!-- Theme and Component Styles -->
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/components.css">
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
                        'brand': ['Fugaz One', 'cursive'],
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
    <link href="https://fonts.googleapis.com/css2?family=Fugaz+One&display=swap" rel="stylesheet">
    
    <!-- Icon Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
    <link rel="shortcut icon" href="/assets/img/tabIcon.png" type="image/x-icon">
    <link rel="manifest" href="/manifest.json">
    
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/easy-pwa-js@1.0/dist/front.js"></script>
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
                <a href="/" class="top-nav-brand">
                    <?= APP_NAME ?>
                </a>
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
                
                <?php if ($showHistoryButton): ?>
                <i onclick="openOld();" class="fas fa-history top-nav-icon"></i>
                <?php endif; ?>
                
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
                    <div class="sidebar-stats-date" id="next-rehearsal-date">Lade...</div>
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
                        ['label' => 'Rückmeldungen', 'href' => '/promises/leader', 'page' => 'leader', 'icon' => 'fas fa-chart-bar'],
                        ['label' => 'Meine Meldungen', 'href' => '/promises', 'page' => 'promises', 'icon' => 'fas fa-clipboard-check'],
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
        <a href="/" class="top-nav-brand">
            <?= isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : APP_NAME ?>
        </a>
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
        
        <?php if ($showHistoryButton): ?>
        <i onclick="openOld();" class="fas fa-history top-nav-icon"></i>
        <?php endif; ?>
        
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
         // Use the proper API endpoint instead of scraping HTML
         fetch('/api/user-stats', {
             method: 'GET',
             headers: {
                 'Content-Type': 'application/json'
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
             } else {
                 console.error('API returned error:', data.error || 'Unknown error');
                 // Fallback to zero stats
                 updateStatsDisplay({ attending: 0, not_attending: 0, no_response: 0, total: 0 });
             }
         })
         .catch(error => {
             console.error('Failed to load stats via API:', error);
             // Fallback to zero stats
             updateStatsDisplay({ attending: 0, not_attending: 0, no_response: 0, total: 0 });
         });
     }
     
     // Function to update stats display
     function updateStatsDisplay(stats) {
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
         if (stats.next_rehearsal) {
             const dateElement = document.getElementById('next-rehearsal-date');
             const titleElement = document.querySelector('.sidebar-stats-header .sidebar-stats-title');
             
             if (dateElement) {
                 dateElement.textContent = stats.next_rehearsal.date_formatted || stats.next_rehearsal.date;
             }
             
             if (titleElement) {
                 // Show rehearsal type if it's not "Probe", otherwise just "Probe"
                 const rehearsalType = stats.next_rehearsal.type || 'Probe';
                 titleElement.textContent = rehearsalType;
             }
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
</script>
</body>
</html> 
<!DOCTYPE html>
<?php
// Theme — use session cache, only query DB on first load
$currentUserTheme = $_SESSION['theme'] ?? null;
if ($currentUserTheme === null && isset($_SESSION['user_id'])) {
    $userModel = new \App\Models\User();
    $currentUserTheme = $userModel->getUserTheme($_SESSION['user_id']);
}
if ($currentUserTheme === null || !\App\Core\ThemeManager::themeExists($currentUserTheme)) {
    $currentUserTheme = \App\Core\ThemeManager::getDefaultTheme();
}

$authPages = [
    'login',
    'register',
    'onboarding',
    'orchestra_select',
    'invite_landing',
    'invite_section_picker',
    'invite_redeem',
    'invite_invalid',
    'join_orchestra',
    'select_section',
    'create_orchestra',
];
$standalonePanels = ['admin_panel', 'orga_panel'];
$noSidebarPages = ['admin_verify'];
$isAuthPage = isset($currentPage) && in_array($currentPage, $authPages);
$isStandalone = isset($currentPage) && in_array($currentPage, $standalonePanels);
$currentPageHidesSidebar = $isAuthPage || $isStandalone || in_array($currentPage ?? '', $noSidebarPages);
$showSidebar = isset($_SESSION['user_id']) && isset($_SESSION['current_orchestra_id']) && !$currentPageHidesSidebar;
$hideNavbar = $isAuthPage || $isStandalone;
?>
<html lang="de" class="w-full h-full" data-current-theme="<?= htmlspecialchars($currentUserTheme) ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <?php if (isset($_SESSION['user_id'])): ?>
        <meta name="csrf-token" content="<?= htmlspecialchars(\App\Core\CSRF::getToken()) ?>">
    <?php endif; ?>
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
    <link rel="apple-touch-icon" href="/assets/icons/apple/Probenplaner-iOS-Default-1024x1024@1x.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/assets/icons/apple/Probenplaner-iOS-Default-1024x1024@1x.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/icons/apple/Probenplaner-iOS-Default-1024x1024@1x.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/assets/icons/apple/Probenplaner-iOS-Default-1024x1024@1x.png">

    <!-- Microsoft Tiles -->
    <meta name="msapplication-TileColor" content="#478cf4">
    <meta name="msapplication-TileImage" content="/assets/icons/apple/Probenplaner-iOS-Default-1024x1024@1x.png">
    <meta name="msapplication-config" content="/browserconfig.xml">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Aldrich&family=Goldman:wght@400;700&family=Kantumruy+Pro:ital,wght@0,100..700;1,100..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Sansation:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700&display=swap" rel="stylesheet">

    <!-- Theme and Component Styles -->
    <?php
    // Update session with current theme for performance
    $_SESSION['theme'] = $currentUserTheme;

    // Generate theme CSS link
    echo \App\Core\ThemeManager::generateThemeCssLink($currentUserTheme);
    ?>
    <?php $assetVersion = \App\Core\Version::getVersion(); ?>
    <link rel="stylesheet" href="/assets/css/components.css?v=<?= $assetVersion ?>">
    <link rel="stylesheet" href="/assets/css/utilities.css?v=<?= $assetVersion ?>">
    <link rel="stylesheet" href="/assets/css/focus-removal.css">

    <!-- Vanilla CSS Components -->

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



    <!-- Icon Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
    <link rel="shortcut icon" href="/assets/icons/branding/Probenplaner Icon.svg" type="image/x-icon">
    <link rel="manifest" href="/manifest.json">

    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/animations/scale.css" />

    <!-- jQuery (loaded in head so inline scripts in page content can use $) -->
    <script src="/assets/js/jquery.min.js"></script>
</head>

<body class="bg-gray-50 text-gray-900 font-sans overflow-x-hidden<?= $showSidebar ? '' : ' layout-guest' ?>">




    <?php

    use App\Core\Utilities;
    use App\Core\Version;

    if ($showSidebar): ?>
        <div id="wrapper" class="flex min-h-screen transition-all duration-slow">
            <!-- Top Navigation -->
            <?php
            // Function to get page title based on current page
            function getPageTitle($currentPage)
            {
                $pageTitles = [
                    'admin' => 'Rückmeldungen',
                    'rehearsals' => 'Termine',
                    'probenplan' => 'Probenplan',
                    'conductor_profile' => 'Profil bearbeiten',
                    'orchestra_settings' => 'Orchester-Einstellungen',
                    'promises' => 'Meine Meldungen',
                    'leader' => 'Rückmeldungen',
                    'profile' => 'Profil bearbeiten',
                ];

                return isset($pageTitles[$currentPage]) ? $pageTitles[$currentPage] : 'Probenplaner';
            }
            $displayTitle = isset($currentPage) ? getPageTitle($currentPage) : 'Probenplaner';
            $title = $displayTitle;
            $showMenuToggle = true;
            // Actions array
            $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $showHelpButton = in_array($currentUri, [
                '/promises',
                '/promises/leader',
                '/promises/admin',
                '/rehearsals',
                '/probenplan',
                '/profile',
                '/conductor/profile'
            ])
                || (strpos($currentUri, '/promises/') === 0)
                || (strpos($currentUri, '/rehearsals/') === 0);
            $actions = [];
            if ($showHelpButton) {
                $actions[] = ['icon' => 'fas fa-question-circle', 'onclick' => 'showHelp()'];
            }
            include __DIR__ . '/../components/top-navigation.php';
            ?>

            <!-- Sidebar Overlay for Mobile -->
            <div class="sidebar-overlay" onclick="document.getElementById('wrapper').classList.remove('toggled');"></div>

            <!-- Modern Sidebar -->
            <div id="sidebar-wrapper" class="sidebar">
                <?php
                // Component styles (load styles only, don't render components)
                $renderComponent = false;
                include __DIR__ . '/../components/pwa-install-card.php';
                include __DIR__ . '/../components/sidebar.php';
                include __DIR__ . '/../components/user-badge.php'; // For generateUserLabels() styling
                include __DIR__ . '/../components/top-navigation.php';
                include __DIR__ . '/../components/tree-view.php';
                include __DIR__ . '/../components/page-header.php';
                $renderComponent = true; // Reset for sidebar-content
                ?>
                <?php include __DIR__ . '/../components/sidebar-content.php'; ?>
            </div>
            <!-- Main Content -->
            <div id="page-content-wrapper" class="page-content main-content-with-sidebar">
                <?php
                $isFluid = $isFluid ?? false;
                $contentClasses = $isFluid ? 'w-full h-full p-0' : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6';
                ?>
                <div id="contentPage" class="page-content-inner <?= $contentClasses ?>">
                    <?= $content ?? '' ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php if (!$hideNavbar): ?>
            <?php
            $title = 'Probenplaner';
            $showMenuToggle = false;
            $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $showHelpButton = in_array($currentUri, [
                '/promises',
                '/promises/leader',
                '/promises/admin',
                '/rehearsals',
                '/probenplan',
                '/profile',
                '/conductor/profile'
            ])
                || (strpos($currentUri, '/promises/') === 0)
                || (strpos($currentUri, '/rehearsals/') === 0);
            $actions = [];
            if ($showHelpButton) {
                $actions[] = ['icon' => 'fas fa-question-circle', 'onclick' => 'showHelp()'];
            }
            include __DIR__ . '/../components/top-navigation.php';
            ?>
        <?php endif; ?>

        <style>
            /* Guest layout */
            body.layout-guest {
                min-height: 100vh;
            }

            .guest-layout {
                display: flex;
                flex-direction: column;
                min-height: 100vh;
            }

            .guest-layout .page-content-inner.flex-1 {
                flex: 1;
                min-height: 0;
                position: relative;
                display: flex;
                flex-direction: column;
            }

            .legal-footer {
                flex-shrink: 0;
                padding: var(--space-3) var(--space-4);
                text-align: center;
                font-size: var(--font-size-xs);
                color: var(--color-gray-500);
                position: relative;
                z-index: 2;
            }

            .guest-layout.guest-auth .legal-footer {
                background: transparent;
            }

            .legal-footer a {
                color: var(--color-gray-500);
                text-decoration: none;
                transition: color var(--transition-base);
            }

            .legal-footer a:hover {
                color: var(--color-gray-700);
            }

            .legal-footer-sep {
                margin: 0 var(--space-2);
                user-select: none;
            }
        </style>

        <div class="guest-layout<?= $hideNavbar ? ' guest-auth' : '' ?>">
            <div class="page-content-inner flex-1">
                <?php if ($isStandalone): ?>
                    <?= $content ?? '' ?>
                <?php elseif ($isAuthPage):
                    $authScreenContent = $content ?? '';
                    include __DIR__ . '/../components/auth-screen.php';
                else: ?>
                    <?= $content ?? '' ?>
                <?php endif; ?>
            </div>
            <?php if (!$hideNavbar): ?>
                <footer class="legal-footer">
                    <a href="https://www.jmd.info/globals/datenschutz" target="_blank" rel="noopener">Datenschutz</a>
                    <span class="legal-footer-sep">·</span>
                    <a href="https://www.jmd.info/globals/impressum" target="_blank" rel="noopener">Impressum</a>
                </footer>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <script src="https://unpkg.com/@popperjs/core@2" defer></script>
    <script src="https://unpkg.com/tippy.js@6" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/assets/js/notifications.js?v=<?= $assetVersion ?>"></script>
    <script src="/assets/js/collapse.js"></script>
    <script src="/assets/js/dropdown.js"></script>
    <script src="/assets/js/tooltip.js"></script>
    <script src="/assets/js/member-actions.js?v=<?= $assetVersion ?>"></script>
    <script src="/assets/js/script.min.js"></script>
    <script src="/assets/js/tree-view-clickable.js"></script>
    <?php include __DIR__ . '/../components/help-modal.php'; ?>
    <?php include __DIR__ . '/../components/notification-system.php'; ?>

    <?php include __DIR__ . '/../components/sidebar-stats.js.php'; ?>
    <?php include __DIR__ . '/../components/service-worker.php'; ?>
</body>

</html>
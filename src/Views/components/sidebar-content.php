<?php
/**
 * Sidebar Content Component
 * Renders the authenticated sidebar contents (header, stats, menu, PWA card, version).
 */
?>

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
                $orchestra = isset($_SESSION['orchestra_name']) ? $_SESSION['orchestra_name'] : APP_NAME;
                if (strlen($orchestra) > 12) {
                    $orchestra = substr($orchestra, 0, 9) . '...';
                }
                $parts[] = '<span class="orchestra">' . $orchestra . '</span>';
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
        $active = isset($item['page']) && isset($currentPage) && $currentPage === $item['page'] ? 'active' : '';
        echo '<li class="sidebar-nav-item"><a class="sidebar-nav-link ' . $active . '" href="' . $item['href'] . '">';
        echo '<i class="sidebar-nav-icon ' . $item['icon'] . '"></i>';
        echo $item['label'] . '</a></li>';
    }
    ?>
    </ul>
</nav>

<!-- PWA Install Card -->
<?php 
$renderComponent = true; 
$title = 'App installieren';
$subtitle = 'Für bessere Performance';
$icon = 'download';
$onclick = 'installPWA()';
$hidden = false;
include __DIR__ . '/pwa-install-card.php';
?>

<!-- Version Footer -->
<div class="sidebar-footer">
    <div class="sidebar-version">
        Probenplaner · <?php 
        echo \App\Core\Version::getTag();
        ?>
    </div>
    </div>



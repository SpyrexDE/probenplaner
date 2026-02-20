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
            <?php
            $displayName = $_SESSION['display_name'] ?? $_SESSION['username'] ?? 'U';
            echo strtoupper(substr($displayName, 0, 1));
            ?>
        </div>
        <div class="sidebar-info">
            <div class="sidebar-name">
                <?= htmlspecialchars($displayName) ?>
                <?php
                $isSmallGroup = false;
                $userId = $_SESSION['user_id'] ?? null;
                $orchestraId = $_SESSION['current_orchestra_id'] ?? null;
                if ($userId && $orchestraId) {
                    $userOrchestraModel = new \App\Models\UserOrchestra();
                    $isSmallGroup = $userOrchestraModel->isUserInSmallGroup((int)$userId, (int)$orchestraId);
                }

                $userData = [
                    'permissions' => $_SESSION['current_permissions'] ?? [],
                    'is_small_group' => $isSmallGroup
                ];
                echo \App\Core\Utilities::generateUserBadges($userData);
                ?>
            </div>
            <div class="sidebar-details">
                <?php
                $parts = [];
                $orchestra = isset($_SESSION['current_orchestra_name']) ? $_SESSION['current_orchestra_name'] : APP_NAME;
                if (strlen($orchestra) > 22) {
                    $orchestra = substr($orchestra, 0, 19) . '...';
                }
                $parts[] = '<span class="orchestra">' . $orchestra . '</span>';

                $displayInfo = \App\Core\Utilities::getUserDisplayInfo(
                    $_SESSION['current_type'] ?? '',
                    $_SESSION['current_permissions'] ?? []
                );

                if ($displayInfo['type']) {
                    $parts[] = $displayInfo['type'];
                } elseif ($displayInfo['role']) {
                    $parts[] = $displayInfo['role'];
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
        <?php if (!empty($_SESSION['current_permissions']['can_manage_rehearsals'])): ?>
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
        $permissions = $_SESSION['current_permissions'] ?? [];
        $orchestraId = $_SESSION['current_orchestra_id'] ?? null;
        $orchestraSlug = $_SESSION['current_orchestra_slug'] ?? $orchestraId;
        $orgSlug = $_SESSION['current_org_slug'] ?? '';
        $basePath = '/' . $orgSlug . '/' . $orchestraSlug;
        $userOrchestras = $_SESSION['user_orchestras_count'] ?? 1;

        $menu = [];

        // Core menu items — permission-gated per wireframe

        // Show "Meine Meldungen" for anyone with the attendance permission
        if (!empty($permissions['can_attend_rehearsals'])) {
            $menu[] = ['label' => 'Meine Meldungen', 'href' => "{$basePath}/promises", 'page' => 'promises', 'icon' => 'fas fa-clipboard-check'];
        }

        if (!empty($permissions['can_view_all_section_stats']) || !empty($permissions['can_view_own_section_stats'])) {
            $route = !empty($permissions['can_manage_ensemble']) ? "{$basePath}/promises/admin" : "{$basePath}/promises/leader";
            $menu[] = ['label' => 'Rückmeldungen', 'href' => $route, 'page' => !empty($permissions['can_manage_ensemble']) ? 'admin' : 'leader', 'icon' => 'fas fa-chart-bar'];
        }

        if (!empty($permissions['can_manage_rehearsals'])) {
            $menu[] = ['label' => 'Termine', 'href' => "{$basePath}/rehearsals", 'page' => 'rehearsals', 'icon' => 'fas fa-calendar-alt'];
        }

        $menu[] = ['label' => 'Probenplan', 'href' => "{$basePath}/probenplan", 'page' => 'probenplan', 'icon' => 'fas fa-list'];

        if (!empty($permissions['can_view_members']) || !empty($permissions['can_manage_members'])) {
            $menu[] = ['label' => 'Mitglieder', 'href' => "{$basePath}/members", 'page' => 'members', 'icon' => 'fas fa-users'];
        }

        if (!empty($permissions['can_manage_ensemble'])) {
            $menu[] = ['label' => 'Ensemble', 'href' => "{$basePath}/orchestras/settings", 'page' => 'orchestra_settings', 'icon' => 'fas fa-cog'];
        }

        if ($userOrchestras > 1) {
            $menu[] = ['label' => 'Ensemble wechseln', 'href' => '/orchestras/select', 'icon' => 'fas fa-exchange-alt'];
        }

        $profileRoute = !empty($permissions['can_manage_ensemble']) ? "{$basePath}/conductor/profile" : "{$basePath}/profile";
        $profilePage = !empty($permissions['can_manage_ensemble']) ? 'conductor_profile' : 'profile';
        $menu[] = ['label' => 'Profil', 'href' => $profileRoute, 'page' => $profilePage, 'icon' => 'fas fa-user'];
        $menu[] = ['label' => 'Logout', 'href' => '/logout', 'icon' => 'fas fa-sign-out-alt'];

        // Render main menu
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
    <div class="sidebar-footer-inner">
        <div class="sidebar-version">
            Probenplaner · <?php echo \App\Core\Version::getTag(); ?>
        </div>
        <div class="sidebar-legal-dropdown" id="sidebar-legal-dropdown">
            <button type="button" class="sidebar-legal-btn" id="sidebar-legal-btn" aria-haspopup="true" aria-expanded="false" title="Rechtliches">§</button>
            <div class="dropdown-menu sidebar-legal-menu" role="menu" aria-labelledby="sidebar-legal-btn">
                <a href="https://www.jmd.info/globals/datenschutz" target="_blank" rel="noopener" class="dropdown-item" role="menuitem">Datenschutz</a>
                <a href="https://www.jmd.info/globals/impressum" target="_blank" rel="noopener" class="dropdown-item" role="menuitem">Impressum</a>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        function init() {
            var wrap = document.getElementById('sidebar-legal-dropdown');
            var btn = document.getElementById('sidebar-legal-btn');
            var menu = wrap ? wrap.querySelector('.dropdown-menu') : null;
            if (!btn || !menu) return;
            if (typeof tippy !== 'undefined') {
                tippy(btn, {
                    content: 'Rechtliches',
                    placement: 'right'
                });
            }

            function closeMenu() {
                menu.classList.remove('show');
                if (menu.parentNode === document.body) {
                    wrap.appendChild(menu);
                }
            }
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var open = menu.classList.contains('show');
                document.querySelectorAll('.sidebar-legal-menu.show').forEach(function(m) {
                    m.classList.remove('show');
                    var w = document.getElementById('sidebar-legal-dropdown');
                    if (m.parentNode === document.body && w) w.appendChild(m);
                });
                if (!open) {
                    if (menu.parentNode !== document.body) {
                        document.body.appendChild(menu);
                    }
                    menu.classList.add('show');
                    var rect = btn.getBoundingClientRect();
                    menu.style.top = (rect.top - menu.offsetHeight - 4) + 'px';
                    menu.style.left = rect.left + 'px';
                }
            });
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.sidebar-legal-dropdown') && !e.target.closest('.sidebar-legal-menu')) {
                    closeMenu();
                }
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>
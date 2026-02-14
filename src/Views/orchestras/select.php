<?php $this->layout('layouts/default', ['title' => 'Orchester auswählen', 'currentPage' => 'orchestra_select']) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';
include __DIR__ . '/../components/user-badge.php';

ob_start();
?>

<style>
    .orchestra-card {
        display: block;
        width: 100%;
        text-align: left;
        padding: 1rem;
        border: 1px solid var(--color-gray-300);
        border-radius: var(--radius-lg);
        background: white;
        transition: all var(--transition-base);
        text-decoration: none;
        color: inherit;
    }

    .orchestra-card:hover {
        border-color: var(--color-primary);
        background-color: var(--color-gray-50);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .orchestra-card h3 {
        font-weight: 600;
        color: var(--color-text-primary);
        margin-bottom: 0.25rem;
        display: inline-flex;
        align-items: center;
        line-height: 1.3;
    }

    .orchestra-card:hover h3 {
        color: var(--color-primary);
    }

    .orchestra-meta {
        font-size: var(--font-size-sm);
        color: var(--color-text-secondary);
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
    }

    .join-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 0.75rem 1rem;
        background: var(--color-primary);
        color: white;
        text-decoration: none;
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        font-weight: 500;
        transition: background-color var(--transition-base);
    }

    .join-button:hover {
        background: var(--color-primary-600);
        color: white;
    }

    .join-button-outline {
        background: transparent;
        color: var(--color-primary);
        border: 1px solid var(--color-primary);
    }

    .join-button-outline:hover {
        background: var(--color-primary);
        color: white;
    }

    .orchestra-card .user-badge {
        margin-left: 0.5rem;
        vertical-align: middle;
    }

    .orchestra-card[style*="cursor: default"]:hover {
        border-color: var(--color-gray-300);
        background-color: white;
        transform: none;
        box-shadow: none;
    }
</style>

<div class="login-form">
    <?php if (!empty($orchestras)): ?>
        <div style="margin-bottom: 1.5rem;">
            <?php foreach ($orchestras as $orchestra): ?>
                <form method="POST" action="/orchestras/set-current" style="margin-bottom: 0.75rem;">
                    <?php include __DIR__ . '/../components/csrf-input.php'; ?>
                    <input type="hidden" name="orchestra_id" value="<?= htmlspecialchars($orchestra['orchestra_id']) ?>">

                    <button type="submit" class="orchestra-card">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3><?= htmlspecialchars($orchestra['orchestra_name']) ?></h3>
                                <div class="orchestra-meta">
                                    <?php
                                    $displayInfo = \App\Core\Utilities::getUserDisplayInfo($orchestra['type'], $orchestra['role']);
                                    ?>
                                    <?php if ($displayInfo['type']): ?>
                                        <span><?= htmlspecialchars($displayInfo['type']) ?></span>
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($displayInfo['role']) ?></span>
                                    <?php
                                    $userData = ['role' => $orchestra['role'], 'is_small_group' => false];
                                    echo \App\Core\Utilities::generateUserBadges($userData);
                                    ?>
                                </div>
                            </div>
                            <div style="color: var(--color-primary);">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>

        <a href="/orchestras/join" class="join-button join-button-outline">
            <i class="fas fa-plus" style="margin-right: 0.5rem;"></i>
            Beitreten
        </a>
    <?php else: ?>
        <div style="margin-bottom: 1.5rem;">
            <div class="orchestra-card" style="cursor: default; border-style: dashed; border-color: var(--color-gray-300);">
                <div style="text-align: center; color: var(--color-text-secondary);">
                    <div style="font-weight: 500; margin-bottom: 0.25rem;">
                        Keinem Orchester beigetreten
                    </div>
                    <div style="font-size: var(--font-size-sm); color: var(--color-gray-500);">
                        Treten Sie einem Orchester bei, um zu beginnen
                    </div>
                </div>
            </div>
        </div>

        <a href="/orchestras/join" class="join-button join-button-outline">
            <i class="fas fa-plus" style="margin-right: 0.5rem;"></i>
            Beitreten
        </a>
    <?php endif; ?>

    <?php
    $links = [
        ['url' => '/orchestras/create', 'text' => 'Neues Orchester erstellen', 'secondary' => true]
    ];
    include __DIR__ . '/../components/auth-footer.php';
    ?>
</div>

<?php
$content = ob_get_clean();

// Render logo separately to pass as headerContent
ob_start();
include __DIR__ . '/../components/logo.php';
$headerContent = ob_get_clean();

$backLink = ['url' => '/logout', 'text' => 'Abmelden', 'icon' => 'fa-sign-out-alt'];
include __DIR__ . '/../components/centered-card.php';
?>
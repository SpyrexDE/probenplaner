<?php $this->layout('layouts/default', ['title' => 'Orchester auswählen', 'currentPage' => 'orchestra_select']) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';
include __DIR__ . '/../components/user-badge.php';
$renderComponent = true;
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

    .join-button-secondary {
        background: var(--color-bg-tertiary, #f5f5f5);
        color: var(--color-text-primary);
        font-weight: 500;
    }

    .join-button-secondary:hover {
        background: var(--color-gray-200, #e5e5e5);
        color: var(--color-text-primary);
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

<?php ob_start(); ?>

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
                                    $displayType = '';
                                    if (!empty($orchestra['type'])) {
                                        if ($orchestra['type'] === 'conductor') {
                                            $displayType = 'Dirigent*in';
                                        } elseif ($orchestra['type'] !== 'none') {
                                            $groupManager = new \App\Core\GroupManager();
                                            $displayType = $groupManager->getDisplayName($orchestra['type']);
                                        }
                                    }
                                    ?>
                                    <?php if ($displayType): ?>
                                        <span><?= htmlspecialchars($displayType) ?></span>
                                    <?php endif; ?>
                                    
                                    <?php
                                    // Generate badges for small group members, but skip the leader crown 
                                    // since we don't load permissions here
                                    $userData = [
                                        'permissions' => [], 
                                        'is_small_group' => !empty($orchestra['is_small_group'])
                                    ];
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

        <a href="/orchestras/redeem" class="join-button join-button-secondary">
            <i class="fas fa-link" style="margin-right: 0.5rem;"></i>
            Einladungslink einlösen
        </a>
    <?php else: ?>
        <div style="margin-bottom: 1.5rem;">
            <div class="orchestra-card" style="cursor: default; border-style: dashed; border-color: var(--color-gray-300);">
                <div style="text-align: center; color: var(--color-text-secondary);">
                    <div style="font-weight: 500; margin-bottom: 0.25rem;">
                        Keinem Ensemble beigetreten
                    </div>
                    <div style="font-size: var(--font-size-sm); color: var(--color-gray-500);">
                        Löse einen Einladungslink ein, um beizutreten
                    </div>
                </div>
            </div>
        </div>

        <a href="/orchestras/redeem" class="join-button join-button-secondary">
            <i class="fas fa-link" style="margin-right: 0.5rem;"></i>
            Einladungslink einlösen
        </a>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();

ob_start();
include __DIR__ . '/../components/logo.php';
$headerContent = ob_get_clean();

$backLink = ['url' => '/logout', 'text' => 'Abmelden', 'icon' => 'fa-sign-out-alt'];
include __DIR__ . '/../components/centered-card.php';
?>
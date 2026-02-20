<?php $this->layout('layouts/default', ['title' => 'Beitreten', 'currentPage' => 'invite_section_picker']) ?>

<style>
    .section-group {
        margin-bottom: var(--space-3);
    }

    .section-group-header {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        padding: var(--space-2) 0;
        font-weight: 500;
        font-size: var(--font-size-sm);
        color: var(--color-gray-700);
        cursor: pointer;
        user-select: none;
    }

    .section-group-header i {
        font-size: var(--font-size-xs);
        transition: transform var(--transition-base);
        width: 12px;
    }

    .section-group.open .section-group-header i {
        transform: rotate(90deg);
    }

    .section-group-items {
        display: none;
        padding-left: var(--space-4);
    }

    .section-group.open .section-group-items {
        display: block;
    }

    .section-radio {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        padding: var(--space-1) 0;
        font-size: var(--font-size-sm);
        cursor: pointer;
    }

    .section-radio input[type="radio"] {
        accent-color: var(--color-primary);
    }

    .section-picker-subtitle {
        font-size: var(--font-size-sm);
        color: var(--color-gray-500);
        text-align: center;
        margin-bottom: var(--space-5);
    }

    .section-join-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 0.75rem 1rem;
        background: var(--color-primary);
        color: white;
        border: none;
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        font-weight: 500;
        cursor: pointer;
        transition: background-color var(--transition-base);
        margin-top: var(--space-4);
    }

    .section-join-btn:hover {
        background: var(--color-primary-600);
    }
</style>

<?php
ob_start();
?>

<div class="login-form">
    <div class="section-picker-subtitle">
        🎵 <?= htmlspecialchars($orchestra['name']) ?> beitreten
        <?php if (!empty($orgName)): ?>
            <div style="font-size: var(--font-size-xs); color: var(--color-gray-400); margin-top: 2px;">
                <?= htmlspecialchars($orgName) ?>
            </div>
        <?php endif; ?>
    </div>

    <div style="font-weight: 500; margin-bottom: var(--space-3); font-size: var(--font-size-sm);">
        Was spielst du?
    </div>

    <form method="POST" action="/invite/join">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <?php foreach ($sections as $groupName => $groupSections): ?>
            <div class="section-group">
                <div class="section-group-header" onclick="this.parentElement.classList.toggle('open')">
                    <i class="fas fa-caret-right"></i> <?= htmlspecialchars($groupName) ?>
                </div>
                <div class="section-group-items">
                    <?php foreach ($groupSections as $section): ?>
                        <label class="section-radio">
                            <input type="radio" name="section" value="<?= htmlspecialchars($section) ?>" required>
                            <?= htmlspecialchars($section) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="section-join-btn">Beitreten</button>
    </form>
</div>

<?php
$content = ob_get_clean();

ob_start();
include __DIR__ . '/../components/logo.php';
$headerContent = ob_get_clean();

$backLink = ['url' => '/orchestras/select', 'text' => 'Zurück', 'icon' => 'fa-arrow-left'];
include __DIR__ . '/../components/centered-card.php';
?>
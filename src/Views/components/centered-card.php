<?php

/**
 * Centered Card Component - Centered card container with optional back link
 * 
 * Props:
 * - $maxWidth (string): Max width CSS value, default '400px'
 * - $backLink (array): ['url' => '/path', 'text' => 'Back', 'icon' => 'fa-arrow-left']
 * - $headerContent (string): Content to display above the card (centered)
 * - $content (string): Main content to display
 */

$maxWidth = $maxWidth ?? '400px';
$backLink = $backLink ?? null;
$headerContent = $headerContent ?? null;
$content = $content ?? '';
?>

<style>
  .centered-card-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100%;
    padding: var(--space-8) var(--space-4);
    position: relative;
  }

  .centered-card-wrapper {
    width: 100%;
    max-width: <?= htmlspecialchars($maxWidth) ?>;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: auto 0;
  }

  .centered-card {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .centered-card-header {
    width: 100%;
    display: flex;
    justify-content: center;
    margin-bottom: var(--space-4);
  }

  .centered-card-ghost {
    width: 100%;
    visibility: hidden;
    pointer-events: none;
    margin-top: var(--space-4);
  }

  .centered-card-back {
    position: absolute;
    top: var(--space-4);
    left: var(--space-4);
    z-index: 10;
  }
</style>

<?php if ($backLink): ?>
  <div class="centered-card-back">
    <a href="<?= htmlspecialchars($backLink['url']) ?>" class="back-link">
      <i class="fas <?= htmlspecialchars($backLink['icon'] ?? 'fa-arrow-left') ?>"></i>
      <?= htmlspecialchars($backLink['text'] ?? 'Zurück') ?>
    </a>
  </div>
<?php endif; ?>

<div class="centered-card-container">
  <div class="centered-card-wrapper">
    <?php if ($headerContent): ?>
      <div class="centered-card-header">
        <?= $headerContent ?>
      </div>
    <?php endif; ?>

    <div class="centered-card">
      <?= $content ?>
    </div>

    <?php if ($headerContent): ?>
      <div class="centered-card-ghost">
        <?= $headerContent ?>
      </div>
    <?php endif; ?>
  </div>
</div>
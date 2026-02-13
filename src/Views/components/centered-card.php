<?php
/**
 * Centered Card Component - Centered card container with optional back link
 * 
 * Props:
 * - $maxWidth (string): Max width CSS value, default '400px'
 * - $backLink (array): ['url' => '/path', 'text' => 'Back', 'icon' => 'fa-arrow-left']
 * - $content (string): Main content to display
 */

$maxWidth = $maxWidth ?? '400px';
$backLink = $backLink ?? null;
$content = $content ?? '';
?>

<style>
.centered-card-container {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-4);
  min-height: 100%;
  position: relative;
}

.centered-card {
  width: 100%;
  max-width: <?= htmlspecialchars($maxWidth) ?>;
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
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
  <div class="centered-card">
    <?= $content ?>
  </div>
</div>

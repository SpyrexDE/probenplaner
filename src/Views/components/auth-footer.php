<?php
/**
 * Auth Footer Component - Links at bottom of auth forms
 * 
 * Props:
 * - $links (array): [['url' => '/path', 'text' => 'Text', 'primary' => 'Primary Text']]
 */

$links = $links ?? [];

if (empty($links)) {
  return;
}
?>

<style>
.auth-footer {
  text-align: center;
  border-top: 1px solid var(--color-gray-200);
  padding-top: var(--space-4);
  margin-top: var(--space-2);
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.auth-footer-link {
  display: inline-block;
  color: var(--color-text-secondary);
  text-decoration: none;
  transition: color var(--transition-base);
  font-size: var(--font-size-sm);
  margin: var(--space-2) 0;
  text-align: center;
}

.auth-footer-link:hover {
  color: var(--color-text-primary);
}

.auth-footer-link-primary {
  color: var(--color-primary);
  font-weight: var(--font-weight-semibold);
}

.auth-footer-link-secondary {
  font-size: var(--font-size-xs);
  color: var(--color-gray-500);
}

.auth-footer-link-secondary:hover {
  color: var(--color-gray-700);
}

.auth-footer-icon {
  margin-right: 0.25rem;
}
</style>

<div class="auth-footer">
  <?php foreach ($links as $link): ?>
    <a href="<?= htmlspecialchars($link['url']) ?>" 
       class="auth-footer-link <?= isset($link['secondary']) && $link['secondary'] ? 'auth-footer-link-secondary' : '' ?>">
      <?php if (isset($link['icon'])): ?>
        <i class="fas <?= htmlspecialchars($link['icon']) ?> auth-footer-icon"></i>
      <?php endif; ?>
      <?= htmlspecialchars($link['text']) ?>
      <?php if (isset($link['primary'])): ?>
        <span class="auth-footer-link-primary"><?= htmlspecialchars($link['primary']) ?></span>
      <?php endif; ?>
    </a>
  <?php endforeach; ?>
</div>

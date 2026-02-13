<?php
/**
 * Full-viewport auth screen: gradient background, overlay, centered content with footer.
 * Used by the layout when $hideNavbar. Content is passed as $authScreenContent.
 */
?>
<style>
.auth-screen {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  position: relative;
}
.auth-screen::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 20% 20%, rgba(71, 140, 244, 0.1) 0%, transparent 50%),
              radial-gradient(circle at 80% 80%, rgba(244, 71, 107, 0.1) 0%, transparent 50%);
  pointer-events: none;
  z-index: 0;
}
.auth-screen-content {
  position: relative;
  z-index: 1;
  flex: 1;
  display: flex;
  flex-direction: column;
}
.auth-screen-footer {
  position: relative;
  z-index: 1;
  flex-shrink: 0;
  padding: var(--space-3) var(--space-4);
  text-align: center;
  font-size: var(--font-size-xs);
  color: var(--color-gray-500);
}
.auth-screen-footer a {
  color: var(--color-gray-500);
  text-decoration: none;
  transition: color var(--transition-base);
}
.auth-screen-footer a:hover {
  color: var(--color-gray-700);
}
.auth-screen-footer-sep {
  margin: 0 var(--space-2);
  user-select: none;
}
</style>
<div class="auth-screen">
  <div class="auth-screen-content">
    <?= $authScreenContent ?? '' ?>
  </div>
  <footer class="auth-screen-footer">
    <a href="https://www.jmd.info/globals/datenschutz" target="_blank" rel="noopener">Datenschutz</a>
    <span class="auth-screen-footer-sep">·</span>
    <a href="https://www.jmd.info/globals/impressum" target="_blank" rel="noopener">Impressum</a>
  </footer>
</div>

<?php
/**
 * CSRF Token Input Component
 * 
 * Props:
 * - $csrf_token (string): CSRF token value
 */

if (isset($csrf_token) && $csrf_token):
?>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>

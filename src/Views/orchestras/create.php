<?php $this->layout('layouts/default', ['title' => 'Neues Orchester erstellen', 'currentPage' => $currentPage]) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';

ob_start();
?>

<?php if (isset($admin_verified) && $admin_verified): ?>
    <form action="/orchestras/store" method="post" class="login-form" style="max-width: 480px;">
        <?php include __DIR__ . '/../components/csrf-input.php'; ?>

        <?php include __DIR__ . '/../components/logo.php'; ?>

        <h2 class="verify-title">Neues Orchester erstellen</h2>
        <p class="verify-subtitle">Orchester-Konfiguration</p>

        <div style="margin: var(--space-6) 0; padding: var(--space-4); background: var(--color-bg-secondary); border-radius: var(--radius-lg); border-left: 4px solid var(--color-primary);">
            <h3 style="margin: 0 0 var(--space-3) 0; font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); color: var(--color-text-primary); display: flex; align-items: center; gap: var(--space-2);">
                <i class="fas fa-music" style="color: var(--color-primary);"></i>
                Orchester-Konfiguration
            </h3>

            <input type="text"
                class="login-input"
                name="name"
                placeholder="Orchestername"
                value="<?= isset($formData['name']) ? htmlspecialchars($formData['name']) : '' ?>"
                style="margin-bottom: var(--space-1);"
                required>
            <div class="form-text" style="margin-bottom: var(--space-4);">z.B. Philharmonisches Orchester München</div>

            <input type="text"
                class="login-input"
                name="token"
                placeholder="Registrierungs-Token"
                value="<?= isset($formData['token']) ? htmlspecialchars($formData['token']) : '' ?>"
                style="margin-bottom: var(--space-1);"
                required>
            <div class="form-text" style="margin-bottom: 0;">Kurzer Code für neue Mitglieder (z.B. PHIL2024)</div>
        </div>

        <button type="submit" class="login-button">
            <i class="fas fa-plus-circle" style="margin-right: var(--space-2);"></i>
            Orchester erstellen
        </button>

        <div class="form-text" style="margin-top: var(--space-3); text-align: center;">
            Das Orchester wird erstellt und Sie können sich dann als Dirigent registrieren
        </div>
    </form>
<?php else: ?>
    <form method="post" action="/orchestras/create" class="login-form">
        <?php include __DIR__ . '/../components/csrf-input.php'; ?>

        <?php include __DIR__ . '/../components/logo.php'; ?>

        <h2 class="verify-title">Admin Verifizierung</h2>
        <p class="verify-subtitle">Um ein neues Orchester anlegen zu können, benötigen Sie das Admin-Passwort</p>

        <input class="login-input"
            type="password"
            name="admin_password"
            placeholder="Admin-Passwort"
            required>

        <button class="login-button" type="submit">
            Verifizieren
        </button>
    </form>
<?php endif; ?>

<?php
$content = ob_get_clean();
$backLink = ['url' => '/', 'text' => 'Zurück', 'icon' => 'fa-arrow-left'];
$maxWidth = isset($admin_verified) && $admin_verified ? '480px' : '400px';
include __DIR__ . '/../components/centered-card.php';
?>
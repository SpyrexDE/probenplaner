<?php $this->layout('layouts/default', ['title' => 'Instrument auswählen', 'currentPage' => 'select_section']) ?>

<?php 
// Load form styles 
$renderComponent = false; // Just load styles, don't render component
include __DIR__ . '/../components/form-input.php'; 
?>

<div class="login-container">
    <form method="POST" action="/orchestras/complete-join" class="login-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="orchestra_id" value="<?= htmlspecialchars($orchestra['id']) ?>">
        
        <div class="login-logo">
            <img src="/assets/img/Logo.png" alt="Logo"/>
        </div>

        <h2 style="text-align: center; margin-bottom: 1rem; color: var(--color-text-primary);">
            <?= htmlspecialchars($orchestra['name']) ?>
        </h2>
        
        <div class="form-text" style="margin-bottom: 2rem;">Wählen Sie Ihr Instrument</div>

        <select class="login-input" id="type" name="type" required>
            <option value="" disabled selected>Instrument auswählen</option>
            <?php foreach ($typeStructure as $sectionName => $instruments): ?>
                <optgroup label="<?= htmlspecialchars($sectionName) ?>">
                    <?php foreach ($instruments as $instrument): ?>
                        <option 
                            value="<?= htmlspecialchars($instrument) ?>"
                            <?= (isset($_SESSION['form_data']['type']) && $_SESSION['form_data']['type'] === $instrument) ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars(str_replace('_', ' ', $instrument)) ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>

        <button class="login-button" type="submit">
            Beitreten
        </button>

        <div class="auth-links">
            <a href="/orchestras/join" class="auth-link">
                <i class="fas fa-chevron-left" style="margin-right: 0.25rem;"></i>
                Zurück
            </a>
        </div>
    </form>
</div>

<?php
// Clear form data from session
if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
}
?>

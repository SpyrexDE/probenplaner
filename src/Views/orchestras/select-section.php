<?php $this->layout('layouts/default', ['title' => 'Instrument auswählen', 'currentPage' => 'select_section']) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';

ob_start();
?>

<form method="POST" action="/orchestras/complete-join" class="login-form">
    <?php include __DIR__ . '/../components/csrf-input.php'; ?>
    <input type="hidden" name="orchestra_id" value="<?= htmlspecialchars($orchestra['id']) ?>">

    <?php include __DIR__ . '/../components/logo.php'; ?>

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
                        <?= (isset($_SESSION['form_data']['type']) && $_SESSION['form_data']['type'] === $instrument) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(str_replace('_', ' ', $instrument)) ?>
                    </option>
                <?php endforeach; ?>
            </optgroup>
        <?php endforeach; ?>
    </select>

    <button class="login-button" type="submit">
        Beitreten
    </button>

    <?php
    $links = [
        ['url' => '/orchestras/join', 'icon' => 'fa-chevron-left', 'text' => 'Zurück']
    ];
    include __DIR__ . '/../components/auth-footer.php';
    ?>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../components/centered-card.php';

if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
}
?>
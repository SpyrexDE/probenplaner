<?php

/**
 * Section Picker Form - the "choose your section" screen (joining via invite link, confirming a
 * section guessed from the JOinJMD instrument). Render inside centered-card.php.
 *
 * @param string $pickerAction      - Form action
 * @param string $pickerHeading     - Heading under the logo
 * @param string $pickerButton      - Submit button text
 * @param array  $pickerHidden      - Hidden fields [name => value] (csrf_token included by the caller)
 * @param string $pickerSelected    - Preselected section ID ('' = placeholder)
 * @param int    $pickerOrchestraId - Orchestra whose section config is offered
 * @param array  $pickerLinks       - auth-footer links (optional)
 */

// Form styles only (login-input etc.)
$renderComponent = false;
include __DIR__ . '/form-input.php';
?>
<form method="POST" action="<?= htmlspecialchars($pickerAction) ?>" class="login-form">
    <?php foreach ($pickerHidden as $hiddenName => $hiddenValue): ?>
        <input type="hidden" name="<?= htmlspecialchars($hiddenName) ?>" value="<?= htmlspecialchars($hiddenValue) ?>">
    <?php endforeach; ?>

    <?php include __DIR__ . '/logo.php'; ?>

    <h2 style="text-align: center; color: var(--color-text-primary); margin-bottom: var(--space-6); font-size: var(--font-size-base); font-weight: 700;">
        <?= htmlspecialchars($pickerHeading) ?>
    </h2>

    <?php
    $selectedType = $pickerSelected;
    $selectName   = 'section';
    $selectId     = 'section';
    $selectClass  = 'login-input';
    $orchestraId  = (int) $pickerOrchestraId;
    include __DIR__ . '/instrument-select.php';
    ?>

    <button class="login-button" type="submit"><?= htmlspecialchars($pickerButton) ?></button>

    <?php
    if (!empty($pickerLinks)) {
        $links = $pickerLinks;
        include __DIR__ . '/auth-footer.php';
    }
    ?>
</form>

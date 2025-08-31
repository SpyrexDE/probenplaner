<?php
use App\Core\Constants;
?>
<div class="compact-color-picker" data-selected-color="<?= htmlspecialchars($selectedColor ?? Constants::COLOR_WHITE) ?>">
    <label class="compact-color-picker-label">Farbenauswahl</label>
    <div class="compact-color-picker-grid">
        <?php foreach (Constants::getRehearsalColors() as $colorValue => $colorName): ?>
            <button 
                type="button" 
                class="compact-color-option color-<?= str_replace('#', '', $colorValue) ?> <?= ($selectedColor ?? Constants::COLOR_WHITE) === $colorValue ? 'selected' : '' ?>" 
                data-color="<?= htmlspecialchars($colorValue) ?>"
                data-color-name="<?= htmlspecialchars($colorName) ?>"
                title="<?= htmlspecialchars($colorName) ?>"
                aria-label="<?= htmlspecialchars($colorName) ?> auswählen"
            ></button>
        <?php endforeach; ?>
    </div>
    <input type="hidden" name="color" value="<?= htmlspecialchars($selectedColor ?? Constants::COLOR_WHITE) ?>">
</div>

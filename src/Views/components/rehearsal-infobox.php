<?php

/**
 * REHEARSAL INFO BOX
 * 
 * Displays a list of info items with emojis.
 * Only renders if there are items.
 * 
 * @param array $infos Array of ['emoji' => '...', 'text' => '...']
 */

if (empty($infos)) return;
?>

<div class="rehearsal-infobox mb-4 py-3 px-4 rounded-lg"
    style="background: linear-gradient(135deg, var(--color-bg-tertiary) 0%, var(--color-bg-secondary) 100%);">
    <div class="flex flex-col gap-1.5">
        <?php foreach ($infos as $info): ?>
            <div class="flex items-start gap-3">
                <span class="text-lg leading-snug select-none"><?= htmlspecialchars($info['emoji']) ?></span>
                <span class="text-sm font-medium leading-relaxed pt-0.5" style="color: var(--color-text-secondary);"><?= htmlspecialchars($info['text']) ?></span>
            </div>

        <?php endforeach; ?>
    </div>
</div>
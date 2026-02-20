<?php

/**
 * Panel Section Component — card section for panel views.
 *
 * Props:
 * - $sectionTitle   (string)  Header label
 * - $sectionIcon    (string)  FontAwesome icon class, e.g. 'fa-cog'
 * - $sectionVariant (string)  'default' | 'danger'
 * - $sectionContent (string)  HTML body (use ob_start/ob_get_clean)
 */

$sectionTitle   = $sectionTitle   ?? '';
$sectionIcon    = $sectionIcon    ?? null;
$sectionVariant = $sectionVariant ?? 'default';
$sectionContent = $sectionContent ?? '';

$isDanger = $sectionVariant === 'danger';
$cardClass = $isDanger ? 'modern-card modern-card-danger mb-4' : 'modern-card mb-4';
?>

<div class="<?= $cardClass ?>">
    <?php if ($sectionTitle): ?>
        <div class="modern-card-header">
            <div class="flex-start gap-3">
                <?php if ($sectionIcon): ?>
                    <i class="fas <?= htmlspecialchars($sectionIcon) ?> text-muted"></i>
                <?php endif; ?>
                <span class="text-heading"><?= htmlspecialchars($sectionTitle) ?></span>
            </div>
        </div>
    <?php endif; ?>
    <div class="modern-card-body">
        <?= $sectionContent ?>
    </div>
</div>
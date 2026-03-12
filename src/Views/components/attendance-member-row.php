<?php
/**
 * Attendance Member Row Component
 *
 * @param array $member         Member data (user_id/id, display_name, type)
 * @param array|null $promise   Promise record (status, note) or null
 * @param array|null $attendance Attendance record (present, comment) or null
 * @param bool $showInstrument  Whether to show instrument label
 */

$memberId = (int)($member['user_id'] ?? $member['id']);
$displayName = htmlspecialchars($member['display_name'] ?? 'Unbekannt');
$instrument = htmlspecialchars($member['type'] ?? '');

// Promise state
$promiseStatus = 'none';
$promiseNote = '';
if ($promise) {
    $promiseStatus = ($promise['status'] === 'yes') ? 'yes' : 'no';
    $promiseNote = $promise['note'] ?? '';
}

// Attendance state
$attStatus = 'unset';
$attComment = '';
if ($attendance) {
    $attStatus = $attendance['present'] ? 'present' : 'absent';
    $attComment = $attendance['comment'] ?? '';
}

// Deviation: only when an explicit promise was given
$deviated = false;
if ($attendance && $promiseStatus !== 'none') {
    $promisedYes = ($promiseStatus === 'yes');
    $wasPresent = (bool)$attendance['present'];
    $deviated = ($promisedYes !== $wasPresent);
}

$promiseIcon = match($promiseStatus) {
    'yes'  => 'fa-check',
    'no'   => 'fa-times',
    default => 'fa-minus',
};
?>

<div class="att-member-row <?= $deviated ? 'deviated' : '' ?>"
     data-user-id="<?= $memberId ?>"
     data-att-status="<?= $attStatus ?>"
     data-promise-status="<?= $promiseStatus ?>">

    <div class="att-member-info" data-user-id="<?= $memberId ?>">
        <div class="att-member-name">
            <?= $displayName ?>
            <?php if ($showInstrument && $instrument): ?>
                <span class="att-member-instrument">&middot; <?= $instrument ?></span>
            <?php endif; ?>
        </div>

        <?php if ($promiseNote): ?>
            <div class="att-comment att-comment-member">
                <i class="fa-solid fa-quote-left att-comment-icon"></i>
                <span><?= htmlspecialchars($promiseNote) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($attComment): ?>
            <div class="att-comment att-comment-admin">
                <i class="fa-solid fa-pen att-comment-icon"></i>
                <span class="att-comment-text" data-user-id="<?= $memberId ?>"><?= htmlspecialchars($attComment) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="att-indicator promise-<?= $promiseStatus ?> att-<?= $attStatus ?> <?= $deviated ? 'deviated' : '' ?>"
         data-user-id="<?= $memberId ?>"
         role="button"
         tabindex="0"
         aria-label="Anwesenheit für <?= $displayName ?>">
        <i class="fas <?= $promiseIcon ?> att-indicator-icon"></i>
    </div>
</div>

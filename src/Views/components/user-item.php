<?php
/**
 * User item component for promise views
 * 
 * @param array $member - Member data with keys: username, note, status (or determine from context)
 * @param string $status - Member status: 'attending', 'not_attending', 'no_response' (optional if in $member)
 * @param string $additionalInfo - Additional info to display after username (optional)
 */

$username = htmlspecialchars($member['username']);
$note = !empty($member['note']) ? htmlspecialchars($member['note']) : '';
$memberStatus = $status ?? $member['status'] ?? 'no_response';
$additionalInfo = $additionalInfo ?? '';

// Determine icon and color based on status
$iconClass = 'fas fa-question-circle';
$iconColor = 'gray';

switch ($memberStatus) {
    case 'attending':
        $iconClass = 'fas fa-check-circle';
        $iconColor = 'green';
        break;
    case 'not_attending':
        $iconClass = 'fas fa-times-circle';
        $iconColor = 'red';
        break;
}
?>

<li class="flex items-center justify-between py-2 px-3 text-sm">
    <span class="flex items-center flex-1">
        <i class="fas fa-user text-xs mr-2 text-muted"></i> 
        <span class="font-medium"><?= $username ?></span>
        <?php if ($additionalInfo): ?>
            <span class="text-muted ml-1"><?= $additionalInfo ?></span>
        <?php endif; ?>
        <?php if (!empty($note)): ?>
            <span class="text-subtle ml-2">- <?= $note ?></span>
        <?php endif; ?>
    </span>
    <i class="<?= $iconClass ?> text-sm ml-2" style="color: <?= $iconColor ?>;"></i>
</li>

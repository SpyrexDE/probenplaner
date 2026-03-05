<?php

/**
 * User item component for promise views
 * 
 * @param array $member - Member data with keys: username, note, status (or determine from context)
 * @param string $status - Member status: 'attending', 'not_attending', 'no_response' (optional if in $member)
 * @param string $additionalInfo - Additional info to display after username (optional)
 */

use App\Core\Utilities;

$username = htmlspecialchars($member['display_name'] ?? $member['email'] ?? '');
$note = !empty($member['note']) ? htmlspecialchars($member['note']) : '';
$memberStatus = $status ?? $member['status'] ?? 'no_response';
$additionalInfo = $additionalInfo ?? '';

// Generate badges for the user
$userLabels = Utilities::generateUserLabels($member);

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

<li class="tree-user-item userSpan" data-user-id="<?= $member['user_id'] ?? $member['id'] ?>">
    <i class="tree-user-item-icon fas fa-user"></i>

    <div class="tree-user-item-content">
        <span class="tree-user-item-name"><?= $username ?><?= $userLabels ?></span>
        <?php if ($additionalInfo): ?>
            <span class="tree-user-item-info"><?= $additionalInfo ?></span>
        <?php endif; ?>
        <?php if (!empty($note)): ?>
            <span class="tree-user-item-note"><?= icon('quote-left', 'tree-user-note-icon') ?> <?= $note ?></span>
        <?php endif; ?>
    </div>

    <div class="tree-user-item-status">
        <i class="tree-user-item-status-icon <?= $iconClass ?> status-<?= $memberStatus ?>"></i>
    </div>
</li>
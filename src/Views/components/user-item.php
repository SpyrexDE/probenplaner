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

<li>
    <span class="userSpan">
        <i class="fas fa-user" style="zoom: 0.8; margin-right: 5px;"></i> 
        <?= $username ?>
        <?= $additionalInfo ?>
        <?php if (!empty($note)): ?>
            - <?= $note ?>
        <?php endif; ?>
        <i class="<?= $iconClass ?> smallTreeIcon rightfloatet" style="color: <?= $iconColor ?>;"></i>
    </span>
</li>

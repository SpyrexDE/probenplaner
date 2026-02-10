<?php
/**
 * Notification System Component
 * Handles flash messages and alerts display using SweetAlert2
 */
?>

<?php if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])): ?>
<script>
    <?php foreach ($_SESSION['flash_messages'] as $key => $message): ?>
        (function(){
            const type = '<?= $message['type'] ?>';
            const text = '<?= htmlspecialchars($message['message']) ?>';
            const details = <?= isset($message['details']) && $message['details'] ? json_encode($message['details']) : 'null' ?>;
            
            if (type === 'error' && details) {
                window.notifyErrorWithDetails(text, details);
            } else {
                if (type === 'success') window.notifySuccess(text);
                else if (type === 'error') window.notifyError(text);
                else if (type === 'warning') window.notifyInfo(text); // Warning maps to info for now, or could use specific icon
                else window.notifyInfo(text);
            }
        })();
    <?php unset($_SESSION['flash_messages'][$key]); endforeach; ?>
</script>
<?php endif; ?>

<?php if (isset($_SESSION['alerts']) && !empty($_SESSION['alerts'])): ?>
<script>
    <?php foreach ($_SESSION['alerts'] as $key => $alert): ?>
        (function(){
            const type = '<?= $alert[2] ?>';
            const title = '<?= htmlspecialchars($alert[0]) ?>';
            const message = `<?= nl2br(htmlspecialchars($alert[1])) ?>`;
            const hasDetails = <?= isset($alert[3]) && $alert[3] ? 'true' : 'false' ?>;
            const details = `<?= isset($alert[3]) ? htmlspecialchars($alert[3]) : '' ?>`;
            
            // Combine title and message for the toast/modal
            const fullMessage = type === 'error' ? message : (title + ': ' + message);
            
            if (type === 'error') {
                if (hasDetails) {
                    window.notifyErrorWithDetails(message, details);
                } else {
                    window.notifyError(message);
                }
            } else if (type === 'success') {
                window.notifySuccess(fullMessage.replace(/<br\/>/g, ' '));
            } else {
                window.notifyInfo(fullMessage.replace(/<br\/>/g, ' '));
            }
        })();
    <?php unset($_SESSION['alerts'][$key]); endforeach; ?>
</script>
<?php endif; ?>

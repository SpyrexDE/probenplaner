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
                Swal.fire({
                    title: text,
                    html: `${text}<br><button id="flashDetailsBtn_<?= $key ?>" style="margin-top:10px;" class="swal2-styled">Details anzeigen</button><div id="flashErrorDetails_<?= $key ?>" style="display:none; margin-top:10px; text-align:left; font-size:12px; color:#a94442; background:#f9f2f4; border:1px solid #ebccd1; padding:10px; border-radius:4px; white-space:pre-wrap;">${details}</div>`,
                    icon: 'error',
                    confirmButtonColor: '#478cf4',
                    didOpen: () => {
                        const btn = document.getElementById('flashDetailsBtn_<?= $key ?>');
                        const detailsEl = document.getElementById('flashErrorDetails_<?= $key ?>');
                        if (btn && detailsEl) {
                            btn.onclick = function() {
                                if (detailsEl.style.display === 'none') {
                                    detailsEl.style.display = 'block';
                                    btn.textContent = 'Details ausblenden';
                                } else {
                                    detailsEl.style.display = 'none';
                                    btn.textContent = 'Details anzeigen';
                                }
                            };
                        }
                    }
                });
            } else {
                if (type === 'success') window.notifySuccess(text); else if (type === 'warning') window.notifyInfo(text); else window.notifyInfo(text);
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
            if (type === 'error') {
                Swal.fire({
                    title: title,
                    html: hasDetails ? `${message}<br><button id="showDetailsBtn_<?= $key ?>" style="margin-top:10px;" class="swal2-styled">Details anzeigen</button><div id="errorDetails_<?= $key ?>" style="display:none; margin-top:10px; text-align:left; font-size:12px; color:#a94442; background:#f9f2f4; border:1px solid #ebccd1; padding:10px; border-radius:4px; white-space:pre-wrap;">${details}</div>` : message,
                    icon: 'error',
                    confirmButtonColor: '#478cf4',
                    showConfirmButton: true,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        const btn = document.getElementById('showDetailsBtn_<?= $key ?>');
                        const detailsEl = document.getElementById('errorDetails_<?= $key ?>');
                        if (btn && detailsEl) {
                            btn.onclick = function() {
                                if (detailsEl.style.display === 'none') {
                                    detailsEl.style.display = 'block';
                                    btn.textContent = 'Details ausblenden';
                                } else {
                                    detailsEl.style.display = 'none';
                                    btn.textContent = 'Details anzeigen';
                                }
                            };
                        }
                    }
                });
            } else if (type === 'success') {
                window.notifySuccess(message.replace(/<br\/>/g, ' '));
            } else {
                window.notifyInfo(message.replace(/<br\/>/g, ' '));
            }
        })();
    <?php unset($_SESSION['alerts'][$key]); endforeach; ?>
</script>
<?php endif; ?>

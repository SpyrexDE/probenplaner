<?php

/**
 * Password Change Modal Component
 *
 * Renders a "Change Password" button that opens a SweetAlert2 modal
 * with current/new/confirm password fields.
 *
 * Usage:
 *   $passwordChangeUrl = "/{orchestra_id}/profile";
 *   include __DIR__ . '/password-change-modal.php';
 */

$pwUrl = $passwordChangeUrl ?? ('/' . ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '') . '/profile');
?>

<style>
    .password-change-btn {
        display: inline-flex;
        align-items: center;
        gap: var(--space-2);
        padding: var(--space-2) var(--space-4);
        border-radius: var(--radius-lg);
        font-weight: var(--font-weight-medium);
        font-size: var(--font-size-sm);
        cursor: pointer;
        transition: all var(--transition-base);
        color: var(--color-primary);
        background: var(--color-primary-50);
        border: 1px solid var(--color-primary-200);
    }

    .password-change-btn:hover {
        background: var(--color-primary-100);
        border-color: var(--color-primary-300);
        box-shadow: var(--shadow-sm);
    }
</style>

<div class="modern-card mb-6">
    <div class="modern-card-header">
        <div class="flex items-center">
            <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                <?= icon('lock', 'text-yellow-600 text-sm') ?>
            </div>
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Passwort</h2>
            </div>
        </div>
    </div>
    <div class="modern-card-body">
        <p class="text-sm text-gray-500 mb-4">Ändere dein Passwort für die Anmeldung.</p>
        <button type="button" class="password-change-btn" onclick="openPasswordChangeModal()">
            <?= icon('key') ?>
            Passwort ändern
        </button>
    </div>
</div>

<script>
    function openPasswordChangeModal() {
        Swal.fire({
            title: 'Passwort ändern',
            html: `
            <div style="text-align:left; display:flex; flex-direction:column; gap:12px;">
                <div>
                    <label style="display:block; font-weight:500; margin-bottom:4px; font-size:14px;">Aktuelles Passwort</label>
                    <input type="password" id="swal-current-password" class="swal2-input" style="margin:0; width:100%;" placeholder="Aktuelles Passwort">
                </div>
                <div>
                    <label style="display:block; font-weight:500; margin-bottom:4px; font-size:14px;">Neues Passwort</label>
                    <input type="password" id="swal-new-password" class="swal2-input" style="margin:0; width:100%;" placeholder="Neues Passwort">
                </div>
                <div>
                    <label style="display:block; font-weight:500; margin-bottom:4px; font-size:14px;">Neues Passwort bestätigen</label>
                    <input type="password" id="swal-confirm-password" class="swal2-input" style="margin:0; width:100%;" placeholder="Passwort bestätigen">
                </div>
            </div>
        `,
            showCancelButton: true,
            confirmButtonText: 'Passwort ändern',
            cancelButtonText: 'Abbrechen',
            confirmButtonColor: 'var(--color-primary, #478cf4)',
            focusConfirm: false,
            preConfirm: () => {
                const current = document.getElementById('swal-current-password').value;
                const newPw = document.getElementById('swal-new-password').value;
                const confirm = document.getElementById('swal-confirm-password').value;

                if (!current) {
                    Swal.showValidationMessage('Bitte gib dein aktuelles Passwort ein');
                    return false;
                }
                if (!newPw) {
                    Swal.showValidationMessage('Bitte gib ein neues Passwort ein');
                    return false;
                }
                if (newPw !== confirm) {
                    Swal.showValidationMessage('Die Passwörter stimmen nicht überein');
                    return false;
                }
                if (newPw.length < <?= defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 6 ?>) {
                    Swal.showValidationMessage('Das Passwort muss mindestens <?= defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 6 ?> Zeichen lang sein');
                    return false;
                }

                return {
                    current_password: current,
                    new_password: newPw,
                    confirm_password: confirm
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit via hidden form POST to preserve existing password change flow
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= htmlspecialchars($pwUrl) ?>';
                form.style.display = 'none';

                const fields = {
                    current_password: result.value.current_password,
                    new_password: result.value.new_password,
                    confirm_password: result.value.confirm_password,
                    username: '<?= htmlspecialchars($_SESSION['username'] ?? '') ?>'
                };

                for (const [key, val] of Object.entries(fields)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = val;
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
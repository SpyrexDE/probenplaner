<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-3xl mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-500 rounded-full mb-4 shadow-lg">
                <?= icon('user', 'text-white text-2xl') ?>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Mein Profil</h1>
        </div>

        <!-- Profile Settings Card -->
        <div class="modern-card mb-6">
            <div class="modern-card-header">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                        <?= icon('edit', 'text-blue-600 text-sm') ?>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Profil bearbeiten</h2>
                    </div>
                </div>
            </div>
            
            <div class="modern-card-body">
                <form action="/profile" method="post" class="space-y-6">
                    <?php if (isset($csrf_token)): ?>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                    
                    <!-- Basic Information -->
                    <div class="form-section">
                        <div class="form-group-modern">
                            <label for="username" class="form-label-modern">
                                <?= icon('user', 'form-label-icon') ?>
                                Nutzername
                            </label>
                            <input type="text" class="form-input-modern" id="username" name="username" 
                                   placeholder="Dein Nutzername" minlength="3" maxlength="20" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                    </div>

                    <!-- Orchestra Information -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <h3 class="form-section-title">Orchester-Mitgliedschaft</h3>
                        </div>
                        
                        <div class="form-group-modern">
                            <label for="group_type" class="form-label-modern">
                                <?= icon('music', 'form-label-icon') ?>
                                Instrument / Stimmgruppe
                            </label>
                            <?php
                            // Ensure type is correctly retrieved, falling back to empty string
                            $currentType = '';
                            if (isset($user['type']) && !empty($user['type'])) {
                                $currentType = $user['type'];
                            }
                            
                            echo '<input type="hidden" id="current_type" value="' . htmlspecialchars($currentType) . '">';
                            ?>
                            <select class="form-input-modern" id="group_type" name="group_type" required>
                                <option value="">Bitte Instrument / Stimmgruppe wählen</option>
                                <?php 
                                function renderTypeOptions($structure, $level = 0, $currentType = '') {
                                    foreach ($structure as $key => $value) {
                                        if (is_array($value)) {
                                            // Group header
                                            echo '<option value="" disabled class="font-bold text-gray-600">' . str_replace('_', ' ', $key) . '</option>';
                                            renderTypeOptions($value, $level + 1, $currentType);
                                        } else {
                                            // Selectable option
                                            // Compare exact string values for more reliable matching
                                            $selected = ($value === $currentType) ? ' selected' : '';
                                            echo '<option value="' . $value . '"' . $selected . '>' . str_repeat('&nbsp;&nbsp;', $level) . str_replace('_', ' ', $value) . '</option>';
                                        }
                                    }
                                }
                                
                                renderTypeOptions($typeStructure, 0, $currentType);
                                ?>
                            </select>
                        </div>
                        
                        <!-- Special Groups -->
                        <div class="space-y-4 mt-4">
                            <div class="modern-checkbox-group">
                                <div class="flex items-start">
                                    <input type="checkbox" id="small_group" name="small_group" 
                                           class="modern-checkbox" 
                                           <?php echo \App\Core\RehearsalTypeManager::isUserInSmallGroup($user) ? 'checked' : ''; ?>>
                                    <div class="ml-3 flex-1">
                                        <label for="small_group" class="modern-checkbox-label">
                                            <?= \App\Core\RehearsalTypeManager::LABEL_KLEINGRUPPE ?>
                                        </label>
                                        <p class="modern-checkbox-description">
                                            Zusätzliche Proben für Stücke mit geringer Besetzung
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="modern-checkbox-group">
                                <div class="flex items-start">
                                    <input type="checkbox" id="group_leader" name="group_leader" 
                                           class="modern-checkbox" 
                                           <?php echo ($user['role'] === 'leader') ? 'checked' : ''; ?>>
                                    <div class="ml-3 flex-1">
                                        <label for="group_leader" class="modern-checkbox-label">
                                            Stimmführer
                                        </label>
                                        <p class="modern-checkbox-description">
                                            Erweiterte Berechtigungen für Stimmgruppen-Verwaltung
                                        </p>
                                    </div>
                                </div>
                                <input type="hidden" id="group_leader_password" name="group_leader_password">
                            </div>
                        </div>
                    </div>

                    <!-- Password Section -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <h3 class="form-section-title">Passwort ändern</h3>
                            <p class="form-section-description">Felder leer lassen, wenn keine Änderung gewünscht</p>
                        </div>
                        <div class="space-y-4">
                            <div class="form-group-modern">
                                <label for="current_password" class="form-label-modern">
                                    <?= icon('lock', 'form-label-icon') ?>
                                    Aktuelles Passwort
                                </label>
                                <input type="password" class="form-input-modern" id="current_password" 
                                       name="current_password" placeholder="Gib dein aktuelles Passwort ein"
                                       autocomplete="current-password">
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group-modern">
                                    <label for="new_password" class="form-label-modern">
                                        <?= icon('key', 'form-label-icon') ?>
                                        Neues Passwort
                                    </label>
                                    <input type="password" class="form-input-modern" id="new_password" 
                                           name="new_password" placeholder="Neues Passwort" 
                                           minlength="4" maxlength="20" autocomplete="new-password">
                                </div>
                                
                                <div class="form-group-modern">
                                    <label for="confirm_password" class="form-label-modern">
                                        <?= icon('check-circle', 'form-label-icon') ?>
                                        Passwort bestätigen
                                    </label>
                                    <input type="password" class="form-input-modern" id="confirm_password" 
                                           name="confirm_password" placeholder="Passwort wiederholen"
                                           minlength="4" maxlength="20" autocomplete="new-password">
                                </div>
                            </div>
                            
                            <div class="password-strength" id="passwordStrength" style="display: none;">
                                <div class="password-strength-bar">
                                    <div class="password-strength-fill" id="strengthFill"></div>
                                </div>
                                <div class="password-strength-text" id="strengthText"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn-modern btn-primary">
                            <?= icon('save', 'btn-icon') ?>
                            Änderungen speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="modern-card modern-card-danger">
            <div class="modern-card-header">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                        <?= icon('trash', 'text-red-600 text-sm') ?>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Gefährliche Aktionen</h2>
                    </div>
                </div>
            </div>
            
            <div class="modern-card-body">
                <div class="danger-zone-content">
                    <div class="danger-zone-info">
                        <h3 class="text-lg font-medium text-red-900 mb-2">Account dauerhaft löschen</h3>
                        <p class="text-red-700 mb-4">Alle Daten werden unwiderruflich gelöscht.</p>
                    </div>
                    <button type="button" id="deleteAccount" class="btn-modern btn-danger">
                        <?= icon('trash', 'btn-icon') ?>
                        Account löschen
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // Password strength checker
    function checkPasswordStrength(password) {
        const strengthIndicator = $('#passwordStrength');
        const strengthFill = $('#strengthFill');
        const strengthText = $('#strengthText');
        
        if (password.length === 0) {
            strengthIndicator.hide();
            return;
        }
        
        strengthIndicator.show();
        
        let score = 0;
        let feedback = [];
        
        // Length check
        if (password.length >= 8) score += 2;
        else if (password.length >= 4) score += 1;
        else feedback.push('Mindestens 4 Zeichen erforderlich');
        
        // Complexity checks
        if (/[a-z]/.test(password)) score += 1;
        if (/[A-Z]/.test(password)) score += 1;
        if (/[0-9]/.test(password)) score += 1;
        if (/[^a-zA-Z0-9]/.test(password)) score += 1;
        
        // Update visual indicator
        const percentage = Math.min((score / 6) * 100, 100);
        strengthFill.css('width', percentage + '%');
        
        let strengthClass = 'weak';
        let strengthLabel = 'Schwach';
        
        if (score >= 5) {
            strengthClass = 'strong';
            strengthLabel = 'Stark';
        } else if (score >= 3) {
            strengthClass = 'medium';
            strengthLabel = 'Mittel';
        }
        
        strengthFill.removeClass('weak medium strong').addClass(strengthClass);
        strengthText.text(strengthLabel + (feedback.length > 0 ? ' - ' + feedback.join(', ') : ''));
    }
    
    // Password field handlers
    $('#new_password').on('input', function() {
        const password = $(this).val();
        checkPasswordStrength(password);
        
        // Check if passwords match
        const confirmPassword = $('#confirm_password').val();
        if (confirmPassword && password !== confirmPassword) {
            $('#confirm_password').addClass('error');
        } else {
            $('#confirm_password').removeClass('error');
        }
    });
    
    $('#confirm_password').on('input', function() {
        const password = $('#new_password').val();
        const confirmPassword = $(this).val();
        
        if (confirmPassword && password !== confirmPassword) {
            $(this).addClass('error');
        } else {
            $(this).removeClass('error');
        }
    });
    
    // Ensure the correct option is selected
    const currentType = $('#current_type').val();
    if (currentType) {
        // Find the option that matches the current type
        $('#group_type option').each(function() {
            if ($(this).val() === currentType) {
                $(this).prop('selected', true);
                return false; // break the loop
            }
        });
    }
    
    // Modern Stimmführer checkbox handler
    $('#group_leader').on('change', function(){
        if($(this).is(':checked')){
            Swal.fire({
                title: 'Stimmführer-Berechtigung',
                html: `
                    <div class="text-left mb-4">
                        <p class="text-gray-600 mb-3">Um Stimmführer-Berechtigungen zu erhalten, benötigst du das entsprechende Passwort.</p>
                    </div>
                `,
                input: 'password',
                inputPlaceholder: 'Stimmführer-Passwort eingeben',
                inputAttributes: {
                    autocapitalize: 'off',
                    autocorrect: 'off'
                },
                showCancelButton: true,
                confirmButtonText: 'Bestätigen',
                cancelButtonText: 'Abbrechen',
                confirmButtonColor: '#478cf4',
                cancelButtonColor: '#6b7280',
                focusConfirm: false,
                preConfirm: (password) => {
                    if (!password) {
                        Swal.showValidationMessage('Bitte gib das Passwort ein');
                        return false;
                    }
                    return password.trim();
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const password = result.value;
                    $('#group_leader_password').val(password);
                    
                    // Show loading
                    Swal.fire({
                        title: 'Überprüfung...',
                        html: 'Das Passwort wird überprüft',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // AJAX request to verify the password
                    $.ajax({
                        type: "POST",
                        url: "/profile/check-leader-password",
                        data: { password: password },
                        success: function(response){
                            // Parse response if it's a string
                            if (typeof response === 'string') {
                                try {
                                    response = JSON.parse(response);
                                } catch (e) {
                                    console.error('Failed to parse response:', e);
                                }
                            }
                            
                            if(response.valid){
                                $('#group_leader').prop('checked', true);
                                window.notifySuccess('Passwort akzeptiert');
                            } else {
                                $('#group_leader').prop('checked', false);
                                Swal.fire({
                                    title: 'Ungültiges Passwort',
                                    text: 'Das eingegebene Passwort ist nicht korrekt.',
                                    icon: 'error',
                                    confirmButtonColor: '#ef4444'
                                });
                            }
                        },
                        error: function() {
                            $('#group_leader').prop('checked', false);
                            Swal.fire({
                                title: 'Fehler',
                                text: 'Die Überprüfung konnte nicht durchgeführt werden. Versuche es später erneut.',
                                icon: 'error',
                                confirmButtonColor: '#ef4444'
                            });
                        }
                    });
                } else {
                    // User cancelled, uncheck the checkbox
                    $(this).prop('checked', false);
                }
            });
        } else {
            // User unchecked, clear the password
            $('#group_leader_password').val('');
        }
    });
    
    // Enhanced form validation
    $('form').on('submit', function(e) {
        // Check if instrument/type is selected
        if (!$('#group_type').val()) {
            e.preventDefault();
            window.notifyError('Bitte wähle dein Instrument bzw. deine Stimmgruppe aus.');
            $('#group_type').focus();
            return false;
        }
        
        const newPassword = $('#new_password').val();
        const confirmPassword = $('#confirm_password').val();
        const currentPassword = $('#current_password').val();
        
        // If trying to change password
        if (newPassword || confirmPassword || currentPassword) {
            // All password fields must be filled
            if (!newPassword || !confirmPassword || !currentPassword) {
                e.preventDefault();
                window.notifyError('Bitte fülle alle Passwort-Felder aus, um das Passwort zu ändern.');
                return false;
            }
            
            // Passwords must match
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                window.notifyError('Die Passwörter stimmen nicht überein.');
                $('#confirm_password').addClass('error').focus();
                return false;
            }
            
            // Minimum password length
            if (newPassword.length < 4) {
                e.preventDefault();
                window.notifyError('Das Passwort muss mindestens 4 Zeichen lang sein.');
                $('#new_password').focus();
                return false;
            }
        }
        
        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<div class="inline-flex items-center"><div class="loading-spinner mr-2"></div>Speichere...</div>');
        submitBtn.prop('disabled', true);
        
        // Re-enable button after a delay (in case of validation errors)
        setTimeout(() => {
            submitBtn.html(originalText);
            submitBtn.prop('disabled', false);
        }, 5000);
        
        return true;
    });
    
    // Modern account deletion with better UX
    $('#deleteAccount').click(function(){
        Swal.fire({
            title: 'Account dauerhaft löschen?',
            html: `
                <div class="text-left space-y-3">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <h3 class="font-semibold text-red-800 mb-2">⚠️ Warnung: Diese Aktion kann nicht rückgängig gemacht werden!</h3>
                        <ul class="text-red-700 text-sm space-y-1">
                            <li>• Alle deine Daten werden unwiderruflich gelöscht</li>
                            <li>• Der Zugriff auf das System wird sofort gesperrt</li>
                            <li>• Eine Wiederherstellung ist nicht möglich</li>
                        </ul>
                    </div>
                    <p class="text-gray-600">Bist du sicher, dass du deinen Account löschen möchtest?</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ja, Account löschen',
            cancelButtonText: 'Abbrechen',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            focusCancel: true,
            reverseButtons: true,
            customClass: {
                popup: 'swal-custom-popup',
                confirmButton: 'swal-confirm-delete',
                cancelButton: 'swal-cancel'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Account wird gelöscht...',
                    html: 'Bitte warten...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Redirect to deletion endpoint
                window.location.href = "/profile/delete";
            }
        });
    });
    
    // Enhanced input interactions
    $('.form-input-modern').on('focus', function() {
        $(this).closest('.form-group-modern').addClass('focused');
    }).on('blur', function() {
        $(this).closest('.form-group-modern').removeClass('focused');
        if ($(this).val()) {
            $(this).closest('.form-group-modern').addClass('filled');
        } else {
            $(this).closest('.form-group-modern').removeClass('filled');
        }
    });
    
    // Initial state for filled inputs
    $('.form-input-modern').each(function() {
        if ($(this).val()) {
            $(this).closest('.form-group-modern').addClass('filled');
        }
    });
});
</script> 
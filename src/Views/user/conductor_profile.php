<?php
// Load form, card, and modern checkbox styles (components used for styles only)
$renderComponent = false; // Just load styles, don't render component
include __DIR__ . '/../components/form-input.php';
include __DIR__ . '/../components/modern-checkbox.php';
include __DIR__ . '/../components/theme-selector.php';
?>

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-3xl mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-500 rounded-full mb-4 shadow-lg">
                <?= icon('user-tie', 'text-white text-2xl') ?>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Dirigent-Profil</h1>
        </div>

        <!-- Theme Selection Card -->
        <div class="modern-card mb-6">
            <div class="modern-card-header">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                        <?= icon('palette', 'text-purple-600 text-sm') ?>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Design-Theme</h2>
                        <p class="text-sm text-gray-500 mt-1">Wähle dein bevorzugtes Farbschema</p>
                    </div>
                </div>
            </div>
            
            <div class="modern-card-body">
                <div class="theme-selection-compact">
                    <?php 
                    $currentTheme = $user['theme'] ?? 'default';
                    foreach ($availableThemes as $themeKey => $theme): 
                    ?>
                    <div class="theme-option-compact">
                        <input type="radio" 
                               id="theme_compact_<?= $themeKey ?>" 
                               name="theme_compact" 
                               value="<?= $themeKey ?>"
                               class="theme-radio-compact sr-only"
                               data-theme-key="<?= $themeKey ?>"
                               <?= ($themeKey === $currentTheme) ? 'checked' : '' ?>>
                        
                        <label for="theme_compact_<?= $themeKey ?>" class="theme-selector-compact">
                            <div class="theme-preview-compact">
                                <div class="theme-colors-compact">
                                    <?php foreach ($theme['preview_colors'] as $colorName => $colorValue): ?>
                                    <div class="theme-dot" 
                                         style="background-color: <?= htmlspecialchars($colorValue) ?>"
                                         title="<?= ucfirst($colorName) ?>">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <span class="theme-name-compact"><?= htmlspecialchars($theme['name']) ?></span>
                                <div class="theme-check-compact">
                                    <?= icon('check', 'text-white text-xs') ?>
                                </div>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
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
                <form action="/<?= $orchestraId ?>/conductor/profile" method="post" class="space-y-6">
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

                    <!-- Password Section -->
                    <div class="form-section ring-2 ring-primary-200 rounded-xl">
                        <?php $hasPassword = isset($hasPassword) ? (bool)$hasPassword : !empty($user['password']); ?>
                        <?php if (!$hasPassword): ?>
                        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-800 flex items-start">
                            <div class="mr-2 mt-0.5">
                                <?= icon('info', 'text-yellow-600') ?>
                            </div>
                            <div>
                                <p class="text-sm">
                                    Du hast aktuell kein Passwort. Setze jetzt eines, um dich auch ohne JMD App anmelden zu können.
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="form-section-header">
                            <h3 class="form-section-title"><?= $hasPassword ? 'Passwort ändern' : 'Passwort festlegen' ?></h3>
                            <p class="form-section-description"><?= $hasPassword ? 'Felder leer lassen, wenn keine Änderung gewünscht' : 'Lege ein neues Passwort für deinen Account fest' ?></p>
                        </div>
                        <div class="space-y-4">
                            <?php if ($hasPassword): ?>
                            <div class="form-group-modern">
                                <label for="current_password" class="form-label-modern">
                                    <?= icon('lock', 'form-label-icon') ?>
                                    Aktuelles Passwort
                                </label>
                                <input type="password" class="form-input-modern" id="current_password" 
                                       name="current_password" placeholder="Gib dein aktuelles Passwort ein"
                                       autocomplete="current-password">
                            </div>
                            <?php endif; ?>
                            
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
    const hasPassword = <?php echo isset($hasPassword) && $hasPassword ? 'true' : 'false'; ?>;
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
    
    // Enhanced form validation
    $('form').on('submit', function(e) {
        const newPassword = $('#new_password').val();
        const confirmPassword = $('#confirm_password').val();
        const currentPassword = $('#current_password').length ? $('#current_password').val() : '';
        
        // If trying to change password
        if (newPassword || confirmPassword || currentPassword) {
            // All password fields must be filled
            if (!newPassword || !confirmPassword || (hasPassword && !currentPassword)) {
                e.preventDefault();
                if (hasPassword && !currentPassword) {
                    window.notifyError('Bitte gib dein aktuelles Passwort ein.');
                } else {
                    window.notifyError('Bitte fülle alle Passwort-Felder aus, um das Passwort zu ändern.');
                }
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
    
    // Compact theme selection with instant switching
    $('.theme-radio-compact').on('change', function() {
        if ($(this).is(':checked')) {
            const selectedTheme = $(this).val();
            const themeKey = $(this).data('theme-key');
            const themeName = $(this).closest('.theme-option-compact').find('.theme-name-compact').text();
            
            // Add switching state
            $('.theme-selection-compact').addClass('theme-switching');
            
            // Apply theme instantly via AJAX
            switchThemeInstantly(themeKey, themeName);
        }
    });
    
    // Function to switch theme instantly
    function switchThemeInstantly(themeKey, themeName) {
        $.ajax({
            type: 'POST',
            url: '/<?= $orchestraId ?>/profile/switch-theme',
            data: {
                theme: themeKey,
                csrf_token: $('input[name="csrf_token"]').val()
            },
            success: function(response) {
                // Parse response if it's a string
                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        console.error('Failed to parse response:', e);
                        response = { success: false };
                    }
                }
                
                if (response.success) {
                    // Apply theme to current page
                    applyThemeToPage(themeKey);
                    
                    // Show success notification
                    window.notifySuccess(`Theme "${themeName}" aktiviert`, 'Sofort angewendet!');
                    
                    // Add applied animation
                    $('body').addClass('theme-applying');
                    setTimeout(() => {
                        $('body').removeClass('theme-applying');
                    }, 600);
                } else {
                    // Handle error
                    window.notifyError('Fehler beim Wechseln des Themes', response.message || 'Unbekannter Fehler');
                    
                    // Reset selection to previous theme
                    const currentTheme = $('body').data('current-theme') || 'default';
                    $(`input[data-theme-key="${currentTheme}"]`).prop('checked', true);
                }
                
                // Remove switching state
                $('.theme-selection-compact').removeClass('theme-switching');
                
            },
            error: function() {
                // Handle AJAX error
                window.notifyError('Fehler beim Wechseln des Themes', 'Netzwerkfehler');
                
                // Reset selection to previous theme
                const currentTheme = $('body').data('current-theme') || 'default';
                $(`input[data-theme-key="${currentTheme}"]`).prop('checked', true);
                
                // Remove switching state
                $('.theme-selection-compact').removeClass('theme-switching');
            }
        });
    }
});
</script> 
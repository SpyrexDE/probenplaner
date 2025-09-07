<?php $this->layout('layouts/default', ['title' => 'Orchester bearbeiten', 'currentPage' => $currentPage]) ?>

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-3xl mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-500 rounded-full mb-4 shadow-lg">
                <?= icon('music', 'text-white text-2xl') ?>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Orchester-Einstellungen</h1>
        </div>

        <!-- Orchestra Settings Card -->
        <div class="modern-card mb-6">
            <div class="modern-card-header">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                        <?= icon('cog', 'text-blue-600 text-sm') ?>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Orchester bearbeiten</h2>
                    </div>
                </div>
            </div>
            
            <div class="modern-card-body">
                <form action="/orchestras/update" method="post" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <!-- Basic Information -->
                    <div class="form-section">
                        <div class="form-group-modern">
                            <label for="name" class="form-label-modern">
                                <?= icon('music', 'form-label-icon') ?>
                                Orchestername
                            </label>
                            <input type="text" class="form-input-modern" id="name" name="name" 
                                   placeholder="Name deines Orchesters" 
                                   value="<?php echo htmlspecialchars($orchestra['name']); ?>" required>
                        </div>
                    </div>

                    <!-- Access & Security -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <h3 class="form-section-title">Zugang & Sicherheit</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group-modern">
                                <label for="token" class="form-label-modern" style="display: flex; align-items: center;">
                                    <?= icon('key', 'form-label-icon') ?>
                                    Orchester-Token
                                    <i id="toggleTokenBtn" class="fas fa-eye" onclick="toggleTokenVisibility()" 
                                       title="Token anzeigen/verbergen" 
                                       style="margin-left: 8px; font-size: 14px; color: var(--color-text-secondary); cursor: pointer; transition: color var(--transition-base); padding: 2px; border-radius: var(--radius-base);"></i>
                                </label>
                                <input type="password" class="form-input-modern" id="token" name="token" 
                                       placeholder="Eindeutiger Token" 
                                       value="<?php echo htmlspecialchars($orchestra['token']); ?>" required>
                                <div class="form-help-text">Für Mitglieder-Registrierung</div>
                            </div>
                            
                            <div class="form-group-modern">
                                <label for="leader_password" class="form-label-modern" style="display: flex; align-items: center;">
                                    <?= icon('shield', 'form-label-icon') ?>
                                    Stimmführer-Passwort
                                    <i id="togglePasswordBtn" class="fas fa-eye" onclick="togglePasswordVisibility()" 
                                       title="Passwort anzeigen/verbergen" 
                                       style="margin-left: 8px; font-size: 14px; color: var(--color-text-secondary); cursor: pointer; transition: color var(--transition-base); padding: 2px; border-radius: var(--radius-base);"></i>
                                </label>
                                <input type="password" class="form-input-modern" id="leader_password" name="leader_password" 
                                       placeholder="Passwort für Stimmführer" 
                                       value="<?php echo htmlspecialchars($orchestra['leader_pw']); ?>" required>
                                <div class="form-help-text">Für Stimmführer-Berechtigungen</div>
                            </div>
                        </div>
                        
                    </div>

                    <!-- Permissions -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <h3 class="form-section-title">Berechtigungen</h3>
                        </div>
                        
                        <div class="modern-checkbox-group">
                            <div class="flex items-start">
                                <input type="checkbox" id="leaders_can_view_all_sections" name="leaders_can_view_all_sections" 
                                       class="modern-checkbox" 
                                       <?php echo !empty($orchestra['leaders_can_view_all_sections']) ? 'checked' : ''; ?>>
                                <div class="ml-3 flex-1">
                                    <label for="leaders_can_view_all_sections" class="modern-checkbox-label">
                                        Stimmführer dürfen alle Register sehen
                                    </label>
                                    <p class="modern-checkbox-description">
                                        Stimmführer können alle Register in der Rückmeldungsübersicht einsehen
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <h3 class="form-section-title">Features</h3>
                        </div>
                        
                        <div class="modern-checkbox-group">
                            <div class="flex items-start">
                                <input type="checkbox" id="show_rehearsal_insights" name="show_rehearsal_insights" 
                                       class="modern-checkbox" 
                                       <?php echo !empty($orchestra['show_rehearsal_insights']) ? 'checked' : ''; ?>>
                                <div class="ml-3 flex-1">
                                    <label for="show_rehearsal_insights" class="modern-checkbox-label">
                                        Proben-Insights anzeigen (Beta)
                                    </label>
                                    <p class="modern-checkbox-description">
                                        Aktiviert erweiterte Analyse-Features für Proben-Rückmeldungen
                                    </p>
                                </div>
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
                        <h3 class="text-lg font-medium text-red-900 mb-2">Orchester dauerhaft löschen</h3>
                        <p class="text-red-700 mb-4">
                            Alle Daten (Mitglieder, Proben, Zusagen) werden unwiderruflich gelöscht.
                        </p>
                    </div>
                    <button type="button" onclick="confirmDelete(event)" class="btn-modern btn-danger">
                        <?= icon('trash', 'btn-icon') ?>
                        Orchester löschen
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // Enhanced form validation
    $('form').on('submit', function(e) {
        const orchestraName = $('#name').val().trim();
        const token = $('#token').val().trim();
        const leaderPassword = $('#leader_password').val().trim();
        
        // Validate required fields
        if (!orchestraName) {
            e.preventDefault();
            window.notifyError('Bitte gib einen Orchesternamen ein.');
            $('#name').focus();
            return false;
        }
        
        if (!token) {
            e.preventDefault();
            window.notifyError('Bitte gib einen Orchester-Token ein.');
            $('#token').focus();
            return false;
        }
        
        if (!leaderPassword) {
            e.preventDefault();
            window.notifyError('Bitte gib ein Stimmführer-Passwort ein.');
            $('#leader_password').focus();
            return false;
        }
        
        // Validate token format (alphanumeric, no spaces)
        if (!/^[a-zA-Z0-9_-]+$/.test(token)) {
            e.preventDefault();
            window.notifyError('Der Token darf nur Buchstaben, Zahlen, Bindestriche und Unterstriche enthalten.');
            $('#token').focus();
            return false;
        }
        
        // Validate password strength
        if (leaderPassword.length < 4) {
            e.preventDefault();
            window.notifyError('Das Stimmführer-Passwort muss mindestens 4 Zeichen lang sein.');
            $('#leader_password').focus();
            return false;
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
    
    // Token validation feedback
    $('#token').on('input', function() {
        const token = $(this).val();
        const isValid = /^[a-zA-Z0-9_-]*$/.test(token);
        
        if (token && !isValid) {
            $(this).addClass('error');
            $(this).siblings('.form-help-text').text('Der Token darf nur Buchstaben, Zahlen, Bindestriche und Unterstriche enthalten');
        } else {
            $(this).removeClass('error');
            $(this).siblings('.form-help-text').text('Wird bei der Registrierung neuer Mitglieder benötigt');
        }
    });
    
    // Success message for saved settings
    if (window.location.search.includes('saved=1')) {
        window.notifySuccess('Orchester-Einstellungen wurden erfolgreich gespeichert!');
    }
});

// Password visibility toggle
function togglePasswordVisibility() {
    const passwordField = $('#leader_password');
    const toggleBtn = $('#togglePasswordBtn');
    const isPassword = passwordField.attr('type') === 'password';
    
    if (isPassword) {
        passwordField.attr('type', 'text');
        toggleBtn.removeClass('fa-eye').addClass('fa-eye-slash');
        toggleBtn.attr('title', 'Passwort verbergen');
    } else {
        passwordField.attr('type', 'password');
        toggleBtn.removeClass('fa-eye-slash').addClass('fa-eye');
        toggleBtn.attr('title', 'Passwort anzeigen');
    }
}

// Token visibility toggle
function toggleTokenVisibility() {
    const tokenField = $('#token');
    const toggleBtn = $('#toggleTokenBtn');
    const isPassword = tokenField.attr('type') === 'password';
    
    if (isPassword) {
        tokenField.attr('type', 'text');
        toggleBtn.removeClass('fa-eye').addClass('fa-eye-slash');
        toggleBtn.attr('title', 'Token verbergen');
    } else {
        tokenField.attr('type', 'password');
        toggleBtn.removeClass('fa-eye-slash').addClass('fa-eye');
        toggleBtn.attr('title', 'Token anzeigen');
    }
}

// Enhanced delete confirmation
function confirmDelete(event) {
    event.preventDefault();
    
    // First confirmation
    Swal.fire({
        title: 'Orchester wirklich löschen?',
        html: `
            <div class="text-left space-y-4">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <h3 class="font-semibold text-red-800 mb-3">⚠️ Kritische Warnung</h3>
                    <p class="text-red-700 mb-3">Das Löschen eines Orchesters ist <strong>unwiderruflich</strong> und entfernt:</p>
                    <ul class="text-red-700 text-sm space-y-1 ml-4">
                        <li>• Alle Mitgliedsdaten und Profile</li>
                        <li>• Komplette Probenplanungen</li>
                        <li>• Alle Zusagen und Absagen</li>
                        <li>• Orchester-Einstellungen</li>
                        <li>• Historische Daten</li>
                    </ul>
                </div>
                <p class="text-gray-600">
                    <strong>Diese Aktion kann nicht rückgängig gemacht werden.</strong>
                    Bist du dir absolut sicher?
                </p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ja, ich verstehe die Konsequenzen',
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
            // Second confirmation with typing requirement
            Swal.fire({
                title: 'Letzte Bestätigung',
                html: `
                    <div class="text-left mb-4">
                        <p class="text-gray-700 mb-3">
                            Um das Löschen zu bestätigen, tippe den Namen deines Orchesters:
                        </p>
                        <p class="font-bold text-lg text-gray-900 bg-gray-100 px-3 py-2 rounded">
                            <?php echo htmlspecialchars($orchestra['name']); ?>
                        </p>
                    </div>
                `,
                input: 'text',
                inputPlaceholder: 'Orchestername hier eingeben...',
                inputAttributes: {
                    autocapitalize: 'off',
                    autocorrect: 'off'
                },
                showCancelButton: true,
                confirmButtonText: 'Orchester endgültig löschen',
                cancelButtonText: 'Abbrechen',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                focusCancel: true,
                reverseButtons: true,
                preConfirm: (inputValue) => {
                    const expectedName = '<?php echo htmlspecialchars($orchestra['name'], ENT_QUOTES); ?>';
                    if (inputValue !== expectedName) {
                        Swal.showValidationMessage('Der eingegebene Name stimmt nicht überein');
                        return false;
                    }
                    return inputValue;
                },
                customClass: {
                    popup: 'swal-custom-popup',
                    confirmButton: 'swal-confirm-delete',
                    cancelButton: 'swal-cancel'
                }
            }).then((finalResult) => {
                if (finalResult.isConfirmed) {
                    // Show deletion in progress
                    Swal.fire({
                        title: 'Orchester wird gelöscht...',
                        html: 'Alle Daten werden entfernt. Dies kann einen Moment dauern...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Redirect to deletion endpoint
                    setTimeout(() => {
                        window.location.href = '/orchestras/delete-confirm';
                    }, 1000);
                }
            });
        }
    });
}
</script> 
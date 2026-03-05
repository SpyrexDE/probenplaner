<?php $this->layout('layouts/default', ['title' => 'Mein Profil', 'currentPage' => $currentPage, 'isFluid' => true]) ?>
<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';
include __DIR__ . '/../components/modern-checkbox.php';
include __DIR__ . '/../components/theme-selector.php';
?>

<div class="min-h-screen py-8">
    <div class="max-w-3xl mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-500 rounded-full mb-4 shadow-lg">
                <?= icon('user', 'text-white text-2xl') ?>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Mein Profil</h1>
        </div>

        <!-- Theme -->
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
                            <input type="radio" id="theme_compact_<?= $themeKey ?>"
                                name="theme_compact" value="<?= $themeKey ?>"
                                class="theme-radio-compact sr-only"
                                data-theme-key="<?= $themeKey ?>"
                                <?= ($themeKey === $currentTheme) ? 'checked' : '' ?>>
                            <label for="theme_compact_<?= $themeKey ?>" class="theme-selector-compact">
                                <div class="theme-preview-compact">
                                    <div class="theme-colors-compact">

                                        <?php foreach ($theme['preview_colors'] as $colorName => $colorValue): ?>
                                            <div class="theme-dot" style="background-color: <?= htmlspecialchars($colorValue) ?>" title="<?= ucfirst($colorName) ?>"></div>
                                        <?php endforeach; ?>
                                    </div>
                                    <span class="theme-name-compact"><?= htmlspecialchars($theme['name']) ?></span>
                                    <div class="theme-check-compact"><?= icon('check', 'text-white text-xs') ?></div>
                                </div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Profile -->
        <div class="modern-card mb-6">
            <div class="modern-card-header">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                        <?= icon('edit', 'text-blue-600 text-sm') ?>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900">Profil</h2>
                </div>
            </div>
            <div class="modern-card-body">
                <label for="email" class="form-label-modern">
                    <?= icon('envelope', 'form-label-icon') ?> E-Mail
                </label>
                <input type="email" class="form-input-modern" id="email" name="email"
                    placeholder="Deine E-Mail-Adresse"
                    value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>

                <label for="display_name" class="form-label-modern" style="margin-top: 1rem;">
                    <?= icon('user', 'form-label-icon') ?> Anzeigename
                </label>
                <input type="text" class="form-input-modern" id="display_name" name="display_name"
                    placeholder="Dein Anzeigename" minlength="2" maxlength="100"
                    value="<?= htmlspecialchars($user['display_name'] ?? '') ?>" required>
            </div>
        </div>

        <!-- Orchestra Membership -->
        <div class="modern-card mb-6">
            <div class="modern-card-header">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center mr-3">
                        <?= icon('music', 'text-indigo-600 text-sm') ?>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900">Orchester-Mitgliedschaft</h2>
                </div>
            </div>
            <div class="modern-card-body">
                <label for="group_type" class="form-label-modern">
                    <?= icon('music', 'form-label-icon') ?> Instrument / Stimmgruppe
                </label>
                <?php
                $isConductor = !empty($_SESSION['current_permissions']['can_manage_ensemble']);
                if ($isConductor): ?>
                    <div class="form-input-modern" style="background: var(--color-bg-tertiary); cursor: default;">Leitung</div>
                <?php else: ?>
                    <?php $currentType = $user['type'] ?? ''; ?>
                    <select class="form-input-modern" id="group_type" name="group_type" required>
                        <option value="">Bitte Instrument / Stimmgruppe wählen</option>
                        <?php
                        function renderTypeOptions($structure, $level = 0, $currentType = '')
                        {
                            static $gm = null;
                            $gm ??= new \App\Core\GroupManager();
                            foreach ($structure as $key => $value) {
                                if (is_array($value)) {
                                    echo '<option value="" disabled class="font-bold text-gray-600">' . htmlspecialchars($gm->getDisplayName($key)) . '</option>';
                                    renderTypeOptions($value, $level + 1, $currentType);
                                } else {
                                    $selected = ($value === $currentType) ? ' selected' : '';
                                    echo '<option value="' . $value . '"' . $selected . '>' . str_repeat('&nbsp;&nbsp;', $level) . htmlspecialchars($gm->getDisplayName($value)) . '</option>';
                                }
                            }
                        }
                        renderTypeOptions($typeStructure, 0, $currentType);
                        ?>
                    </select>
                <?php endif; ?>

                <?php if (!empty($allRoles)): ?>
                    <div class="mt-6">
                        <input type="hidden" name="role_ids_submitted" value="1">
                        <?php
                        $tagSelectName = 'role_ids';
                        $tagSelectId = 'profileRoleSelect';
                        $tagSelectLabel = 'Meine Rollen';
                        $tagSelectPlaceholder = 'Rolle hinzufügen…';
                        $tagSelectOptions = array_map(fn($r) => [
                            'id' => $r['id'],
                            'name' => $r['name'],
                            'color' => $r['tag_color'] ?? '#478cf4',
                            'is_default' => $r['is_default'] ?? 0,
                            'removable' => in_array((int)$r['id'], $selfAssignableIds),
                            'addable' => in_array((int)$r['id'], $selfAssignableIds),
                        ], $allRoles);
                        $tagSelectSelected = $userRoleIds ?? [];
                        include __DIR__ . '/../components/tag-select.php';
                        ?>
                    </div>
                <?php endif; ?>

                <div class="mt-8">
                    <div class="bg-gray-50 rounded-lg p-5 border border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-base font-medium text-gray-900">Orchester verlassen</h3>
                            <p class="text-sm text-gray-500 mt-1">Du entfernst dich aus diesem Orchester. Du kannst später über einen Einladungslink jederzeit wieder beitreten.</p>
                        </div>
                        <div class="flex-shrink-0">
                            <button type="button" id="leaveOrchestra" class="btn-modern btn-secondary w-full sm:w-auto">
                                <?= icon('person-walking-arrow-right', 'btn-icon mr-2 text-gray-600') ?> Verlassen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Password -->
        <?php
        $passwordChangeUrl = '/' . ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '') . '/profile';
        $hasPassword = $hasPassword ?? !empty($user['password']);
        include __DIR__ . '/../components/password-change-modal.php';
        ?>

        <!-- Danger Zone -->
        <div class="modern-card modern-card-danger">
            <div class="modern-card-header">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                        <?= icon('trash', 'text-red-600 text-sm') ?>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900">Gefährliche Aktionen</h2>
                </div>
            </div>
            <div class="modern-card-body">
                <div class="danger-zone-content">
                    <div class="danger-zone-info">
                        <h3 class="text-lg font-medium text-red-900 mb-2">Account dauerhaft löschen</h3>
                        <p class="text-red-700 mb-4">Alle Daten werden unwiderruflich gelöscht.</p>
                    </div>
                    <button type="button" id="deleteAccount" class="btn-modern btn-danger">
                        <?= icon('trash', 'btn-icon mr-2') ?> Account löschen
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .settings-save-indicator {
        display: none;
        position: fixed;
        bottom: var(--space-5);
        right: var(--space-5);
        padding: var(--space-2) var(--space-4);
        border-radius: var(--radius-full);
        font-size: var(--font-size-sm);
        z-index: var(--z-toast);
        box-shadow: var(--shadow-lg);
        align-items: center;
        gap: var(--space-2);
    }

    .settings-save-indicator.saving {
        background: var(--color-primary-100);
        color: var(--color-primary-700);
    }

    .settings-save-indicator.success {
        background: var(--color-success-100, #d1fae5);
        color: var(--color-success-700, #047857);
    }

    .settings-save-indicator.error {
        background: var(--color-error-100, #fee2e2);
        color: var(--color-error-700, #b91c1c);
    }
</style>
<div id="settingsSaveIndicator" class="settings-save-indicator"></div>

<script src="/assets/js/settings-engine.js"></script>
<script>
    $(document).ready(function() {
        <?php $orchestraBase = ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? ''); ?>
        const userId = <?= (int)$user['id'] ?>;
        const orchestraSlug = '<?= $orchestraBase ?>';
        const csrfToken = '<?= \App\Core\CSRF::getToken() ?>';

        function wireField(selector, fieldName, fieldType) {
            const el = document.querySelector(selector);
            if (!el) return;
            el.dataset.entity = 'user';
            el.dataset.entityId = userId;
            el.dataset.orchestraId = orchestraSlug;
            el.dataset.field = fieldName;
            el.dataset.saveMode = 'auto';
            if (fieldType) el.dataset.fieldType = fieldType;
        }

        wireField('#email', 'email', 'text');
        wireField('#display_name', 'display_name', 'text');
        wireField('#group_type', 'group_type', 'select');

        // Auto-save role tag changes
        const roleSelect = document.getElementById('profileRoleSelect');
        if (roleSelect) {
            roleSelect.addEventListener('tag-select:change', (e) => {
                const ids = e.detail.ids;
                if (window.SettingsEngine) window.SettingsEngine.showSaveState('saving');
                fetch('/' + orchestraSlug + '/api/settings/user/' + userId, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            field: 'role_ids',
                            value: JSON.stringify(ids)
                        }),
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            if (window.SettingsEngine) window.SettingsEngine.showSaveState('success');
                        } else {
                            if (window.SettingsEngine) window.SettingsEngine.showSaveState('error');
                            if (window.notifyErrorWithDetails) window.notifyErrorWithDetails('Fehler beim Speichern', data.error || JSON.stringify(data));
                        }
                    })
                    .catch(err => {
                        if (window.SettingsEngine) window.SettingsEngine.showSaveState('error');
                        if (window.notifyErrorWithDetails) window.notifyErrorWithDetails('Netzwerkfehler', err.message || String(err));
                    });
            });
        }

        if (window.SettingsEngine) window.SettingsEngine.init();

        // Theme selection
        $('.theme-radio-compact').on('change', function() {
            if (!$(this).is(':checked')) return;
            const themeKey = $(this).data('theme-key');
            const themeName = $(this).closest('.theme-option-compact').find('.theme-name-compact').text();
            $('.theme-selection-compact').addClass('theme-switching');

            $.ajax({
                type: 'POST',
                url: '/' + orchestraSlug + '/profile/switch-theme',
                data: {
                    theme: themeKey,
                    csrf_token: csrfToken
                },
                success: function(response) {
                    if (typeof response === 'string') try {
                        response = JSON.parse(response);
                    } catch (e) {
                        response = {
                            success: false
                        };
                    }
                    if (response.success) {
                        const link = $('link[data-theme]');
                        if (link.length) {
                            link.attr('href', '/assets/css/themes/theme-' + themeKey + '.css').attr('data-theme', themeKey);
                        }
                        $('body').attr('data-current-theme', themeKey);
                        if (typeof Storage !== 'undefined') sessionStorage.setItem('current-theme', themeKey);
                        window.notifySuccess('Theme "' + themeName + '" aktiviert');
                    } else {
                        window.notifyErrorWithDetails('Fehler beim Wechseln des Themes', response.message || response.error || JSON.stringify(response));
                        const cur = $('body').data('current-theme') || 'default';
                        $('input[data-theme-key="' + cur + '"]').prop('checked', true);
                    }
                    $('.theme-selection-compact').removeClass('theme-switching');
                },
                error: function(xhr, status, error) {
                    const details = 'Status: ' + status + '\nError: ' + error + '\nResponse: ' + xhr.responseText;
                    window.notifyErrorWithDetails('Netzwerkfehler beim Wechseln des Themes', details);
                    $('.theme-selection-compact').removeClass('theme-switching');
                }
            });
        });

        // Leave Orchestra
        $('#leaveOrchestra').click(function() {
            Swal.fire({
                title: 'Orchester verlassen?',
                text: 'Möchtest du dieses Orchester wirklich verlassen? Du hast danach keinen Zugriff mehr auf die Proben dieses Orchesters, kannst aber später über einen Link wieder beitreten.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ja, Orchester verlassen',
                cancelButtonText: 'Abbrechen',
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#6b7280',
                focusCancel: true,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Wird verlassen...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => Swal.showLoading()
                    });
                    window.location.href = '/' + orchestraSlug + '/profile/leave';
                }
            });
        });

        // Account deletion
        $('#deleteAccount').click(function() {
            Swal.fire({
                title: 'Account dauerhaft löschen?',
                html: '<div class="text-left"><div class="bg-red-50 border border-red-200 rounded-lg p-4"><h3 class="font-semibold text-red-800 mb-2">⚠️ Diese Aktion kann nicht rückgängig gemacht werden!</h3><ul class="text-red-700 text-sm space-y-1"><li>• Alle Daten werden unwiderruflich gelöscht</li><li>• Der Zugriff wird sofort gesperrt</li></ul></div></div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ja, Account löschen',
                cancelButtonText: 'Abbrechen',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                focusCancel: true,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Account wird gelöscht...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => Swal.showLoading()
                    });
                    window.location.href = '/' + orchestraSlug + '/profile/delete';
                }
            });
        });
    });
</script>
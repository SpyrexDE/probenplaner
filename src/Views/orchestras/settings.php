<?php $this->layout('layouts/default', ['title' => 'Orchester-Einstellungen', 'currentPage' => $currentPage, 'isFluid' => true]) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';
include __DIR__ . '/../components/modern-checkbox.php';
$renderComponent = true;
?>

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-3xl mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-500 rounded-full mb-4 shadow-lg">
                <?= icon('music', 'text-white text-2xl') ?>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Orchester-Einstellungen</h1>
        </div>

        <?php
        // Re-render settings
        $settingsEntity   = 'orchestra';
        $settingsEntityId = $orchestra['id'];
        $settingsData     = $orchestra;
        include __DIR__ . '/../components/settings-renderer.php';
        ?>

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
    function confirmDelete(event) {
        event.preventDefault();

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
        }).then((result) => {
            if (result.isConfirmed) {
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
                }).then((finalResult) => {
                    if (finalResult.isConfirmed) {
                        Swal.fire({
                            title: 'Orchester wird gelöscht...',
                            html: 'Alle Daten werden entfernt. Dies kann einen Moment dauern...',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            willOpen: () => Swal.showLoading()
                        });
                        setTimeout(() => {
                            window.location.href = '/<?= ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '') ?>/orchestras/delete-confirm';
                        }, 1000);
                    }
                });
            }
        });
    }
</script>
<?php $this->layout('layouts/default', ['title' => 'Orchester bearbeiten', 'currentPage' => $currentPage]) ?>

<div class="container-app mt-8">
    <div class="max-w-4xl mx-auto">
        <div class="card-base">
            <div class="p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-3xl font-bold text-gray-700">Orchester bearbeiten</h2>
                    <i id="editInfoTip" class="fa fa-exclamation-circle text-2xl text-gray-500 cursor-pointer hover:text-primary"></i>
                </div>
                
                <form action="/orchestras/update" method="post">
                    <div class="form-group">
                        <label for="name">Orchestername</label>
                        <input type="text" class="form-input mb-4" id="name" name="name" placeholder="Orchestername" value="<?php echo htmlspecialchars($orchestra['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="token">Token</label>
                        <input type="text" class="form-input mb-4" id="token" name="token" placeholder="Token" value="<?php echo htmlspecialchars($orchestra['token']); ?>" required>
                        <small class="form-text text-muted">Dieser Token wird für die Registrierung neuer Mitglieder verwendet.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="leader_password">Stimmführer-Passwort</label>
                        <input type="text" class="form-input mb-4" id="leader_password" name="leader_password" placeholder="Stimmführer-Passwort" value="<?php echo htmlspecialchars($orchestra['leader_pw']); ?>" required>
                        <small class="form-text text-muted">Dieses Passwort ermöglicht Stimmführer-Berechtigungen bei der Registrierung.</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-checkbox mb-4">
                            <input type="checkbox" id="leaders_can_view_all_sections" name="leaders_can_view_all_sections" <?php echo !empty($orchestra['leaders_can_view_all_sections']) ? 'checked' : ''; ?>>
                            <label for="leaders_can_view_all_sections">Stimmführer dürfen alle Register sehen</label>
                        </div>
                        <small class="form-text text-muted">Erlaubt Stimmführern die Ansicht aller Register in der Rückmeldungsübersicht.</small>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary w-full">
                            <i class="fas fa-save mr-2"></i>Speichern
                        </button>
                    </div>
                </form>
            </div>

            <div class="card-base mt-6">
                <div class="p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-3xl font-bold text-gray-700">Orchester löschen</h2>
                        <i id="deleteInfoTip" class="fa fa-exclamation-circle text-2xl text-gray-500 cursor-pointer hover:text-error"></i>
                    </div>
                    
                    <div class="mt-6">
                        <a href="#" onclick="confirmDelete(event)" class="btn btn-danger w-full text-center block">
                            <i class="fas fa-trash-alt mr-2"></i>Orchester löschen
                        </a>
                    </div>
                </div>
            </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // Set up tooltips
    tippy('#editInfoTip', {
        content: 'Es müssen nur die Felder ausgefüllt werden, die auch bearbeitet werden sollen.',
        arrow: true
    });
    
    tippy('#deleteInfoTip', {
        content: 'Diese Aktion kann nicht rückgängig gemacht werden. Alle Daten werden unwiderruflich gelöscht.',
        arrow: true
    });
    
    // Example: show a contextual info toast if needed
    // window.notifyInfo('Einstellungen speichern, um Änderungen zu übernehmen.', { timer: 4000 });
});

function confirmDelete(event) {
    event.preventDefault();
    Swal.fire({
        title: 'Orchester löschen',
        html: '<div class="text-left"><p><strong>Achtung:</strong> Das Löschen eines Orchesters kann nicht rückgängig gemacht werden.</p><p>Alle Daten, einschließlich Proben, Nutzer und Zusagen werden unwiderruflich gelöscht.</p></div>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Löschen',
        cancelButtonText: 'Abbrechen',
        confirmButtonColor: '#dc3545',
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '/orchestras/delete-confirm';
        }
    });
}
</script> 
<?php $this->layout('layouts/default', ['title' => 'Orchester bearbeiten', 'currentPage' => $currentPage]) ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow-sm" style="background-color: white; padding: 30px; border-radius: 10px;">
                <div class="card-header" style="background: none; border: none; padding: 0 0 20px 0;">
                    <h2 style="color: #525861; font-size: 31px; font-family: Roboto, sans-serif; font-weight: 1000; margin: 0;">Orchester bearbeiten</h2>
                    <i id="editInfoTip" class="fa fa-exclamation-circle" style="transform: scale(2); transform-origin: 0; position: absolute; cursor: pointer; right: 30px; top: 30px;"></i>
                </div>
                
                <form action="/orchestras/update" method="post">
                    <div class="form-group">
                        <label for="name">Orchestername</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Orchestername" style="font-family: Roboto, sans-serif;margin-bottom: 15px;" value="<?php echo htmlspecialchars($orchestra['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="token">Token</label>
                        <input type="text" class="form-control" id="token" name="token" placeholder="Token" style="font-family: Roboto, sans-serif;margin-bottom: 15px;" value="<?php echo htmlspecialchars($orchestra['token']); ?>" required>
                        <small class="form-text text-muted">Dieser Token wird für die Registrierung neuer Mitglieder verwendet.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="leader_password">Stimmführer-Passwort</label>
                        <input type="text" class="form-control" id="leader_password" name="leader_password" placeholder="Stimmführer-Passwort" style="font-family: Roboto, sans-serif;margin-bottom: 15px;" value="<?php echo htmlspecialchars($orchestra['leader_pw']); ?>" required>
                        <small class="form-text text-muted">Dieses Passwort ermöglicht Stimmführer-Berechtigungen bei der Registrierung.</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch" style="margin-bottom: 15px;">
                            <input type="checkbox" class="custom-control-input" id="leaders_can_view_all_sections" name="leaders_can_view_all_sections" <?php echo !empty($orchestra['leaders_can_view_all_sections']) ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="leaders_can_view_all_sections">Stimmführer dürfen alle Register sehen</label>
                        </div>
                        <small class="form-text text-muted">Erlaubt Stimmführern die Ansicht aller Register in der Rückmeldungsübersicht.</small>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-block" style="background-color: rgb(71,140,244); color: white; font-family: Roboto, sans-serif;">
                            <i class="fas fa-save mr-2" style="color: white;"></i>Speichern
                        </button>
                    </div>
                </form>
            </div>

            <div class="card shadow-sm mt-4" style="background-color: white; padding: 30px; border-radius: 10px;">
                <div class="card-header" style="background: none; border: none; padding: 0 0 20px 0;">
                    <h2 style="color: #525861; font-size: 31px; font-family: Roboto, sans-serif; font-weight: 1000; margin: 0;">Orchester löschen</h2>
                    <i id="deleteInfoTip" class="fa fa-exclamation-circle" style="transform: scale(2); transform-origin: 0; position: absolute; cursor: pointer; right: 30px; top: 30px;"></i>
                </div>
                
                <div class="form-group mt-4">
                    <a href="#" onclick="confirmDelete(event)" class="btn btn-block" style="background-color: #dc3545; color: white; font-family: Roboto, sans-serif;">
                        <i class="fas fa-trash-alt mr-2" style="color: white;"></i>Orchester löschen
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
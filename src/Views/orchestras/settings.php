<?php $this->layout('layouts/default', ['title' => 'Orchester bearbeiten', 'currentPage' => $currentPage]) ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="shadow-sm">
                <div style="white-space: pre;display: block;margin: 0 0 10px;font-size: 13px;line-height: 1.42857143;word-break: break-all;word-wrap: break-word;overflow: hidden;">
                    <span class="float-none" href="#" style="color: #525861;font-size: 31px;padding-top: 0;font-family: Roboto, sans-serif;font-weight: 1000;padding-bottom: 0px;margin-right: 0;">Orchester bearbeiten</span>
                    <i id="editInfoTip" class="fa fa-exclamation-circle" style="transform: scale(2); transform-origin: 0; position: absolute; cursor: pointer;"></i>
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
                        <label for="leader_pw">Stimmführer-Passwort</label>
                        <input type="text" class="form-control" id="leader_pw" name="leader_pw" placeholder="Stimmführer-Passwort" style="font-family: Roboto, sans-serif;margin-bottom: 15px;" value="<?php echo htmlspecialchars($orchestra['leader_pw']); ?>" required>
                        <small class="form-text text-muted">Dieses Passwort ermöglicht Stimmführer-Berechtigungen bei der Registrierung.</small>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-block" style="background-color: rgb(71,140,244); color: white; font-family: Roboto, sans-serif;">
                            <i class="fas fa-save mr-2" style="color: white;"></i>Speichern
                        </button>
                    </div>
                </form>
            </div>

            <div class="shadow-sm mt-4">
                <div style="white-space: pre;display: block;margin: 0 0 10px;font-size: 13px;line-height: 1.42857143;word-break: break-all;word-wrap: break-word;overflow: hidden;">
                    <span class="float-none" href="#" style="color: #525861;font-size: 31px;padding-top: 0;font-family: Roboto, sans-serif;font-weight: 1000;padding-bottom: 0px;margin-right: 0;">Orchester löschen</span>
                    <i id="deleteInfoTip" class="fa fa-exclamation-circle" style="transform: scale(2); transform-origin: 0; position: absolute; cursor: pointer;"></i>
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
    
    // Show warning toast on page load
    const Toast = Swal.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true
    });
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
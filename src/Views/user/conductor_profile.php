<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow-sm" style="background-color: white; padding: 30px; border-radius: 10px;">
                <div class="card-header" style="background: none; border: none; padding: 0 0 20px 0;">
                    <h2 style="color: #525861; font-size: 31px; font-family: Roboto, sans-serif; font-weight: 1000; margin: 0;">Profil bearbeiten</h2>
                    <i id="editInfoTip" class="fa fa-exclamation-circle" style="transform: scale(2); transform-origin: 0; position: absolute; cursor: pointer; right: 30px; top: 30px;"></i>
                </div>
                
                <form action="/conductor/profile" method="post">
                    <?php if (isset($csrf_token)): ?>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="username">Nutzername</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Nutzername" minlength="3" maxlength="20" style="font-family: Roboto, sans-serif;margin-bottom: 15px;" value="<?php echo htmlspecialchars($user['username']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="current_password">Aktuelles Passwort</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" style="font-family: Roboto, sans-serif;margin-bottom: 15px;">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Neues Passwort</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="4" maxlength="20" style="font-family: Roboto, sans-serif;margin-bottom: 15px;">
                        <small class="form-text text-muted">Das Passwort muss mindestens 4 und darf maximal 20 Zeichen haben.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Neues Passwort bestätigen</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="4" maxlength="20" style="font-family: Roboto, sans-serif;margin-bottom: 15px;">
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
                    <h2 style="color: #525861; font-size: 31px; font-family: Roboto, sans-serif; font-weight: 1000; margin: 0;">Account löschen</h2>
                    <i id="deleteInfoTip" class="fa fa-exclamation-circle" style="transform: scale(2); transform-origin: 0; position: absolute; cursor: pointer; right: 30px; top: 30px;"></i>
                </div>
                
                <div class="form-group mt-4">
                    <button type="button" id="deleteAccount" class="btn btn-block" style="background-color: rgb(226, 38, 38); color: white; font-family: Roboto, sans-serif;">
                        <i class="fas fa-trash mr-2" style="color: white;"></i>Account löschen
                    </button>
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
    
    // Handle account deletion
    $('#deleteAccount').click(function(){
        if(confirm("Willst du deinen Account wirklich löschen?\nWir können keine Daten wiederherstellen!")){
            window.location.href = "/profile/delete";
        }
    });
});
</script> 
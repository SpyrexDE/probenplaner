<div class="container-app mt-8">
    <div class="max-w-4xl mx-auto">
        <div class="card-base">
            <div class="p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-3xl font-bold text-gray-700">Profil bearbeiten</h2>
                    <i id="editInfoTip" class="fa fa-exclamation-circle text-2xl text-gray-500 cursor-pointer hover:text-primary"></i>
                </div>
                
                <form action="/conductor/profile" method="post">
                    <?php if (isset($csrf_token)): ?>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="username">Nutzername</label>
                        <input type="text" class="form-input mb-4" id="username" name="username" placeholder="Nutzername" minlength="3" maxlength="20" value="<?php echo htmlspecialchars($user['username']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="current_password">Aktuelles Passwort</label>
                        <input type="password" class="form-input mb-4" id="current_password" name="current_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Neues Passwort</label>
                        <input type="password" class="form-input mb-4" id="new_password" name="new_password" minlength="4" maxlength="20">
                        <small class="form-text text-muted">Das Passwort muss mindestens 4 und darf maximal 20 Zeichen haben.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Neues Passwort bestätigen</label>
                        <input type="password" class="form-input mb-4" id="confirm_password" name="confirm_password" minlength="4" maxlength="20">
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn-base btn-primary w-full">
                            <i class="fas fa-save mr-2"></i>Speichern
                        </button>
                    </div>
                </form>
            </div>

            <div class="card-base mt-6">
                <div class="p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-3xl font-bold text-gray-700">Account löschen</h2>
                        <i id="deleteInfoTip" class="fa fa-exclamation-circle text-2xl text-gray-500 cursor-pointer hover:text-error"></i>
                    </div>
                    
                    <div class="mt-6">
                        <button type="button" id="deleteAccount" class="btn-base btn-danger w-full">
                            <i class="fas fa-trash mr-2"></i>Account löschen
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
<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="shadow-sm">
                <div style="white-space: pre;display: block;margin: 0 0 10px;font-size: 13px;line-height: 1.42857143;word-break: break-all;word-wrap: break-word;overflow: hidden;"><span class="float-none" href="#" style="color: #525861;font-size: 31px;padding-top: 0;font-family: Roboto, sans-serif;font-weight: 1000;padding-bottom: 0px;margin-right: 0;">Profil bearbeiten</span><i id="editInfoTip" class="fa fa-exclamation-circle" style="transform: scale(2); transform-origin: 0; position: absolute; cursor: pointer;"></i>
                </div>
                
                <form action="/conductor/profile" method="post">
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
});
</script> 
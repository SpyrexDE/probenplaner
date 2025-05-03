<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="shadow-sm">
                <div style="white-space: pre;display: block;margin: 0 0 10px;font-size: 13px;line-height: 1.42857143;word-break: break-all;word-wrap: break-word;overflow: hidden;"><span class="float-none" href="#" style="color: #525861;font-size: 31px;padding-top: 0;font-family: Roboto, sans-serif;font-weight: 1000;padding-bottom: 0px;margin-right: 0;">Profil bearbeiten</span><i id="editInfoTip" class="fa fa-exclamation-circle" style="transform: scale(2); transform-origin: 0; position: absolute; cursor: pointer;"></i>
                </div>
                
                <form action="/profile" method="post">
                    <div class="form-group">
                        <label for="username">Nutzername</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Nutzername" minlength="3" maxlength="20" style="font-family: Roboto, sans-serif;margin-bottom: 15px;" value="<?php echo htmlspecialchars(str_replace('♚', '', $user['username'])); ?>">
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
                    
                    <div class="form-group">
                        <label for="group_type">Stimmgruppe</label>
                        <select class="form-control" id="group_type" name="group_type" style="font-family: Roboto, sans-serif;">
                            <option value="" disabled>Instrument / Stimmgruppe</option>
                            <?php 
                            function renderTypeOptions($structure, $level = 0) {
                                global $user;
                                $currentType = str_replace('*', '', $user['type']);
                                
                                foreach ($structure as $key => $value) {
                                    if (is_array($value)) {
                                        // Group header
                                        echo '<option value="" disabled style="font-weight: bold; background-color: ' . ($level === 0 ? '#e1e1e1' : '#f3f3f3') . '">' . str_replace('_', ' ', $key) . '</option>';
                                        renderTypeOptions($value, $level + 1);
                                    } else {
                                        // Selectable option
                                        $selected = ($value === $currentType) ? ' selected' : '';
                                        echo '<option value="' . $value . '"' . $selected . '>' . str_repeat('&nbsp;&nbsp;', $level) . str_replace('_', ' ', $value) . '</option>';
                                    }
                                }
                            }
                            
                            renderTypeOptions($typeStructure);
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox mb-3 zoomed" style="position: relative;">
                            <input type="checkbox" class="custom-control-input" id="small_group" name="small_group" <?php echo (strpos($user['type'], '*') !== false) ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="small_group">Kleingruppe</label>
                            <i class="fa fa-question-circle ml-2" id="smallGroupTip" style="cursor: pointer;"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox mb-3 zoomed" style="position: relative;">
                            <input type="checkbox" class="custom-control-input" id="group_leader" name="group_leader" <?php echo (strpos($user['username'], '♚') !== false) ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="group_leader">Stimmführer</label>
                            <input type="hidden" id="group_leader_pw" name="group_leader_pw">
                        </div>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-block" style="background-color: rgb(71,140,244); color: white; font-family: Roboto, sans-serif;">
                            <i class="fas fa-save mr-2" style="color: white;"></i>Speichern
                        </button>
                    </div>
                </form>
                
                <button type="button" id="deleteAccount" class="btn btn-block mt-3" style="background-color: rgb(226, 38, 38); color: white; font-family: Roboto, sans-serif;">
                    <i class="fas fa-trash mr-2" style="color: white;"></i>Account löschen
                </button>
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
    
    tippy('#smallGroupTip', {
        content: 'Markiere diese Checkbox, wenn du zur Kleingruppe gehörst. Personen die zur Kleingruppe gehören, bekommen auch die Proben angezeigt, bei denen für Stücke mit geringer Besetzung geprobt wird.',
        arrow: true
    });
    
    // Handle Stimmführer checkbox
    $('#group_leader').click(function(){
        if($(this).is(':checked')){
            var password = prompt("Stimmführerpasswort angeben:", "");
            if (password === null) {
                return false;
            }
            
            // Trim the password to remove any accidental spaces
            password = password.trim();
            
            $('#group_leader_pw').val(password);
            
            // AJAX request to verify the password
            $.ajax({
                type: "POST",
                url: "/profile/check-leader-password",
                data: { password: password },
                success: function(response){
                    // Parse response if it's a string
                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                        } catch (e) {
                            console.error('Failed to parse response:', e);
                        }
                    }
                    
                    if(response.valid){
                        $('#group_leader').prop('checked', true);
                    } else {
                        alert("Ungültiges Passwort!");
                        $('#group_leader').prop('checked', false);
                    }
                }
            });
        }
    });
    
    // Handle account deletion
    $('#deleteAccount').click(function(){
        if(confirm("Willst du deinen Account wirklich löschen?\nWir können keine Daten wiederherstellen!")){
            window.location.href = "/profile/delete";
        }
    });
});
</script> 
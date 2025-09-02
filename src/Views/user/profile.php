<div class="container-app mt-8">
    <div class="max-w-4xl mx-auto">
        <div class="card-base">
            <div class="p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-3xl font-bold text-gray-700">Profil bearbeiten</h2>
                    <i id="editInfoTip" class=" text-2xl text-gray-500 cursor-pointer hover:text-primary"><?= icon('exclamation-circle', 'text-gray-600') ?></i>
                </div>
                

                
                <form action="/profile" method="post">
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
                    
                    <div class="form-group">
                        <label for="group_type">Stimmgruppe</label>
                        <?php
                        // Ensure type is correctly retrieved, falling back to empty string
                        $currentType = '';
                        if (isset($user['type']) && !empty($user['type'])) {
                            $currentType = str_replace('*', '', $user['type']);
                        }
                        
                        echo '<input type="hidden" id="current_type" value="' . htmlspecialchars($currentType) . '">';
                        ?>
                        <select class="form-input" id="group_type" name="group_type" required>
                            <option value="">Bitte Instrument / Stimmgruppe wählen</option>
                            <?php 
                            function renderTypeOptions($structure, $level = 0, $currentType = '') {
                                foreach ($structure as $key => $value) {
                                    if (is_array($value)) {
                                        // Group header
                                        echo '<option value="" disabled class="font-bold text-gray-600">' . str_replace('_', ' ', $key) . '</option>';
                                        renderTypeOptions($value, $level + 1, $currentType);
                                    } else {
                                        // Selectable option
                                        // Compare exact string values for more reliable matching
                                        $selected = ($value === $currentType) ? ' selected' : '';
                                        echo '<option value="' . $value . '"' . $selected . '>' . str_repeat('&nbsp;&nbsp;', $level) . str_replace('_', ' ', $value) . '</option>';
                                    }
                                }
                            }
                            
                            renderTypeOptions($typeStructure, 0, $currentType);
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-checkbox mb-4">
                            <input type="checkbox" id="small_group" name="small_group" <?php echo (isset($user['is_small_group']) && $user['is_small_group']) ? 'checked' : ''; ?>>
                            <label for="small_group">Kleingruppe</label>
                            <i class=" text-gray-500 cursor-pointer hover:text-primary ml-2" id="smallGroupTip"><?= icon('question-circle', 'text-gray-600') ?></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-checkbox mb-6">
                            <input type="checkbox" id="group_leader" name="group_leader" <?php echo ($user['role'] === 'leader') ? 'checked' : ''; ?>>
                            <label for="group_leader">Stimmführer</label>
                            <input type="hidden" id="group_leader_password" name="group_leader_password">
                        </div>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn-base btn-primary w-full">
                            <i class=" mr-2"><?= icon('save', 'text-gray-600') ?></i>Speichern
                        </button>
                    </div>
                </form>
            </div>

            <div class="card-base mt-6">
                <div class="p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-3xl font-bold text-gray-700">Account löschen</h2>
                        <i id="deleteInfoTip" class=" text-2xl text-gray-500 cursor-pointer hover:text-error"><?= icon('exclamation-circle', 'text-gray-600') ?></i>
                    </div>
                    
                    <div class="mt-6">
                        <button type="button" id="deleteAccount" class="btn-base btn-danger w-full">
                            <i class=" mr-2"><?= icon('trash', 'text-white') ?></i>Account löschen
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
    
    tippy('#smallGroupTip', {
        content: 'Markiere diese Checkbox, wenn du zur Kleingruppe gehörst. Personen die zur Kleingruppe gehören, bekommen auch die Proben angezeigt, bei denen für Stücke mit geringer Besetzung geprobt wird.',
        arrow: true
    });
    
    tippy('#deleteInfoTip', {
        content: 'Diese Aktion kann nicht rückgängig gemacht werden. Alle Daten werden unwiderruflich gelöscht.',
        arrow: true
    });
    
    // Ensure the correct option is selected
    const currentType = $('#current_type').val();
    if (currentType) {
        // Find the option that matches the current type
        $('#group_type option').each(function() {
            if ($(this).val() === currentType) {
                $(this).prop('selected', true);
                return false; // break the loop
            }
        });
    }
    
    // Handle Stimmführer checkbox
    $('#group_leader').click(function(){
        if($(this).is(':checked')){
            var password = prompt("Stimmführerpasswort angeben:", "");
            if (password === null) {
                return false;
            }
            
            // Trim the password to remove any accidental spaces
            password = password.trim();
            
            $('#group_leader_password').val(password);
            
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
    
    // Handle form submission
    $('form').submit(function() {
        // Make sure a type is selected
        if (!$('#group_type').val()) {
            alert('Bitte wählen Sie eine Stimmgruppe aus.');
            return false;
        }
        
        return true;
    });
    
    // Handle account deletion
    $('#deleteAccount').click(function(){
        if(confirm("Willst du deinen Account wirklich löschen?\nWir können keine Daten wiederherstellen!")){
            window.location.href = "/profile/delete";
        }
    });
});
</script> 
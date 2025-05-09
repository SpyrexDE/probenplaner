<?php $this->layout('layouts/default', ['title' => 'Create Rehearsal', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

<div class="container-fluid mt-4">
    <?php if (!empty($errors)): ?>
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true
        });
        
        <?php foreach ($errors as $error): ?>
            Toast.fire({
                icon: 'error',
                title: '<?= htmlspecialchars($error) ?>'
            });
        <?php endforeach; ?>
    </script>
    <?php endif; ?>
    
    <div class="float-none text-center">
        <div style="padding: 3px; margin: 0 auto; max-width: 600px;">
            <span class="float-none" style="color: #525861; font-size: 31px; font-family: Roboto, sans-serif; font-weight: 700; display: block; margin-bottom: 20px;">Neuer Termin</span>
            
            <form method="post" action="/rehearsals/create">
                <input class="form-control" type="date" id="date" name="date" value="<?= htmlspecialchars($formData['date'] ?? '') ?>" placeholder="Datum" style="font-family: Roboto, sans-serif; margin-bottom: 20px;" required="" minlength="3" maxlength="50">
                
                <input class="form-control" type="time" id="time" name="time" value="<?= htmlspecialchars($formData['time'] ?? '') ?>" placeholder="Uhrzeit" style="font-family: Roboto, sans-serif; margin-bottom: 20px;" required="" minlength="3" maxlength="50">
                
                <input class="form-control" type="text" id="location" name="location" value="<?= htmlspecialchars($formData['location'] ?? '') ?>" placeholder="Ort" style="font-family: Roboto, sans-serif; margin-bottom: 20px;" required="" minlength="3" maxlength="50">
                
                <div class="dropdown" style="margin-bottom: 40px; text-align: left;">
                    <button id="dropD" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false" type="button" style="width: 100%; color: black; background-color: <?= htmlspecialchars($formData['color'] ?? 'white') ?>;">Farbenauswahl</button>
                    <div role="menu" class="dropdown-menu pre-scrollable">
                        <a role="presentation" class="dropdown-item" href="#" id="white" style="background-color: white;"></a>
                        <a role="presentation" class="dropdown-item" href="#" id="red" style="background-color: #ffcccc;"></a>
                        <a role="presentation" class="dropdown-item" href="#" id="blue" style="background-color: #ccccff;"></a>
                        <a role="presentation" class="dropdown-item" href="#" id="yellow" style="background-color: #ffffcc;"></a>
                        <a role="presentation" class="dropdown-item" href="#" id="green" style="background-color: #ccffcc;"></a>
                    </div>
                    <input type="hidden" name="color" id="selectedColor" value="<?= htmlspecialchars($formData['color'] ?? 'white') ?>">
                </div>

                <p class="float-none" style="color: rgba(82,88,97,0.74); font-size: 27px; font-family: Roboto, sans-serif; font-weight: 700; text-align: left; margin-bottom: 20px;">Stimmgruppen</p>
                
                <div style="text-align: left;">
                    <div class="form-check custom-control custom-checkbox mb-3" style="margin-bottom: 10px !important;">
                        <input class="form-check-input custom-control-input" type="checkbox" id="is_small_group" name="is_small_group" value="1" <?= !empty($formData['is_small_group']) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="is_small_group">Kleingruppe</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3" style="margin-bottom: 10px !important;">
                        <input class="form-check-input custom-control-input" type="checkbox" id="Konzertreise" name="rehearsal_type" value="Konzertreise" <?= ($formData['rehearsal_type'] ?? '') === 'Konzertreise' ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Konzertreise">Konzertreise</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3" style="margin-bottom: 10px !important;">
                        <input class="form-check-input custom-control-input" type="checkbox" id="Konzert" name="rehearsal_type" value="Konzert" <?= ($formData['rehearsal_type'] ?? '') === 'Konzert' ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Konzert">Konzert</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3" style="margin-bottom: 10px !important;">
                        <input class="form-check-input custom-control-input" type="checkbox" id="Generalprobe" name="rehearsal_type" value="Generalprobe" <?= ($formData['rehearsal_type'] ?? '') === 'Generalprobe' ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Generalprobe">Generalprobe</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3" style="margin-bottom: 10px !important;">
                        <input class="form-check-input custom-control-input" type="checkbox" id="Stimmprobe" name="rehearsal_type" value="Stimmprobe" <?= ($formData['rehearsal_type'] ?? '') === 'Stimmprobe' ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Stimmprobe">Stimmprobe</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3" style="margin-bottom: 10px !important; font-weight: 700; font-size: 1.1em;">
                        <input name="rehearsal_type" class="form-check-input custom-control-input" type="checkbox" id="Tutti" value="Tutti" <?= ($formData['rehearsal_type'] ?? '') === 'Tutti' ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" id="TuttiLabel" for="Tutti">Tutti</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck" style="margin-bottom: 10px !important; margin-left: 25px;">
                        <input class="form-check-input custom-control-input" type="checkbox" id="Streicher" name="groups[]" value="Streicher" <?= in_array('Streicher', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label id="StreicherLabel" class="form-check-label custom-control-label" for="Streicher">Streicher</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck subCheck subCheckStr" style="margin-bottom: 10px !important; margin-left: 50px;">
                        <input class="form-check-input custom-control-input" id="Vio1" name="groups[]" value="Violine_1" type="checkbox" <?= in_array('Violine_1', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Vio1">Violine 1</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck subCheck subCheckStr" style="margin-bottom: 10px !important; margin-left: 50px;">
                        <input class="form-check-input custom-control-input" id="Vio2" name="groups[]" value="Violine_2" type="checkbox" <?= in_array('Violine_2', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Vio2">Violine 2</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck subCheck subCheckStr" style="margin-bottom: 10px !important; margin-left: 50px;">
                        <input class="form-check-input custom-control-input" id="Br" name="groups[]" value="Bratsche" type="checkbox" <?= in_array('Bratsche', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Br">Bratsche</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck subCheck subCheckStr" style="margin-bottom: 10px !important; margin-left: 50px;">
                        <input class="form-check-input custom-control-input" id="Clo" name="groups[]" value="Cello" type="checkbox" <?= in_array('Cello', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Clo">Cello</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck subCheck subCheckStr" style="margin-bottom: 10px !important; margin-left: 50px;">
                        <input class="form-check-input custom-control-input" id="Kontrabass" name="groups[]" value="Kontrabass" type="checkbox" <?= in_array('Kontrabass', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Kontrabass">Kontrabass</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck" style="margin-bottom: 10px !important; margin-left: 25px;">
                        <input class="form-check-input custom-control-input" type="checkbox" id="Bläser" name="groups[]" value="Bläser" <?= in_array('Bläser', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" id="BläserLabel" for="Bläser">Bläser</label>
                    </div>
                    
                    <!-- Blechbläser group -->
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck subCheck subCheckBl" style="margin-bottom: 10px !important; margin-left: 50px;">
                        <input class="form-check-input custom-control-input subCheckBl" type="checkbox" name="groups[]" value="Blechbläser" id="BBläser" <?= in_array('Blechbläser', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" id="BBläserLabel" for="BBläser">Blechbläser</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck doubleSubCheck subCheckBl subCheckBBl" style="margin-bottom: 10px !important; margin-left: 75px;">
                        <input class="form-check-input custom-control-input" id="Tro" name="groups[]" value="Trompete" type="checkbox" <?= in_array('Trompete', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Tro">Trompete</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck doubleSubCheck subCheckBl subCheckBBl" style="margin-bottom: 10px !important; margin-left: 75px;">
                        <input class="form-check-input custom-control-input" id="Po" name="groups[]" value="Posaune" type="checkbox" <?= in_array('Posaune', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Po">Posaune</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck doubleSubCheck subCheckBl subCheckBBl" style="margin-bottom: 10px !important; margin-left: 75px;">
                        <input class="form-check-input custom-control-input" id="Ho" name="groups[]" value="Horn" type="checkbox" <?= in_array('Horn', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Ho">Horn</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck doubleSubCheck subCheckBl subCheckBBl" style="margin-bottom: 10px !important; margin-left: 75px;">
                        <input class="form-check-input custom-control-input" id="Tu" name="groups[]" value="Tuba" type="checkbox" <?= in_array('Tuba', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Tu">Tuba</label>
                    </div>
                    
                    <!-- Holzbläser group -->
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck subCheck subCheckBl" style="margin-bottom: 10px !important; margin-left: 50px;">
                        <input class="form-check-input custom-control-input" type="checkbox" id="HBläser" name="groups[]" value="Holzbläser" <?= in_array('Holzbläser', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" id="HBläserLabel" for="HBläser">Holzbläser</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck doubleSubCheck subCheckBl subCheckHBl" style="margin-bottom: 10px !important; margin-left: 75px;">
                        <input class="form-check-input custom-control-input" id="Fl" name="groups[]" value="Flöte" type="checkbox" <?= in_array('Flöte', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Fl">Flöte</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck doubleSubCheck subCheckBl subCheckHBl" style="margin-bottom: 10px !important; margin-left: 75px;">
                        <input class="form-check-input custom-control-input" id="Ob" name="groups[]" value="Oboe" type="checkbox" <?= in_array('Oboe', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Ob">Oboe</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck doubleSubCheck subCheckBl subCheckHBl" style="margin-bottom: 10px !important; margin-left: 75px;">
                        <input class="form-check-input custom-control-input" id="Kl" name="groups[]" value="Klarinette" type="checkbox" <?= in_array('Klarinette', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Kl">Klarinette</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck doubleSubCheck subCheckBl subCheckHBl" style="margin-bottom: 10px !important; margin-left: 75px;">
                        <input class="form-check-input custom-control-input" id="Fa" name="groups[]" value="Fagott" type="checkbox" <?= in_array('Fagott', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Fa">Fagott</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck" style="margin-bottom: 10px !important; margin-left: 25px;">
                        <input class="form-check-input custom-control-input" type="checkbox" id="Schlagwerk" name="groups[]" value="Schlagwerk" <?= in_array('Schlagwerk', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" id="SchlagwerkLabel" for="Schlagwerk">Schlagwerk</label>
                    </div>
                    
                    <div class="form-check custom-control custom-checkbox mb-3 allCheck" style="margin-bottom: 10px !important; margin-left: 25px;">
                        <input class="form-check-input custom-control-input" type="checkbox" id="Andere" name="groups[]" value="Andere" <?= in_array('Andere', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label custom-control-label" for="Andere">Andere</label>
                    </div>
                </div>
                
                <div class="form-group mt-4">
                    <label for="description" style="text-align: left; display: block;">Notizen (optional)</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <button class="btn btn-primary btn-block" type="submit" style="background-color: rgb(71,140,244); font-family: Roboto, sans-serif;">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle dropdown color selection
    $(".dropdown-item").click(function(e) {
        $("#selectedColor").val($(e.target).css("background-color"));
        $("#dropD").css("background-color", $(e.target).css("background-color"));
    });
    
    // Initialize all checkboxes based on Tutti state
    function initCheckboxes() {
        if ($('#Tutti').prop('checked')) {
            $('.allCheck').find('input[type="checkbox"]').prop('checked', true);
            $('.allCheck').find('input[type="checkbox"]').prop('disabled', true);
        } else {
            $('.allCheck').find('input[type="checkbox"]').prop('disabled', false);
            
            // Handle Streicher checkbox
            if ($('#Streicher').prop('checked')) {
                $('.subCheckStr').find('input[type="checkbox"]').prop('checked', true);
                $('.subCheckStr').find('input[type="checkbox"]').prop('disabled', true);
            } else {
                $('.subCheckStr').find('input[type="checkbox"]').prop('disabled', false);
            }
            
            // Handle Bläser checkbox
            if ($('#Bläser').prop('checked')) {
                $('.subCheckBl').find('input[type="checkbox"]').prop('checked', true);
                $('.subCheckBl').find('input[type="checkbox"]').prop('disabled', true);
            } else {
                $('.subCheckBl').find('input[type="checkbox"]').prop('disabled', false);
                
                // Handle Blechbläser checkbox
                if ($('#BBläser').prop('checked')) {
                    $('.subCheckBBl').find('input[type="checkbox"]').prop('checked', true);
                    $('.subCheckBBl').find('input[type="checkbox"]').prop('disabled', true);
                } else {
                    $('.subCheckBBl').find('input[type="checkbox"]').prop('disabled', false);
                }
                
                // Handle Holzbläser checkbox
                if ($('#HBläser').prop('checked')) {
                    $('.subCheckHBl').find('input[type="checkbox"]').prop('checked', true);
                    $('.subCheckHBl').find('input[type="checkbox"]').prop('disabled', true);
                } else {
                    $('.subCheckHBl').find('input[type="checkbox"]').prop('disabled', false);
                }
            }
        }
    }
    
    // Run initialization
    initCheckboxes();
    
    // Tutti checkbox behavior
    $("#Tutti").change(function() {
        if ($(this).prop('checked')) {
            // Select and disable all checkboxes
            $('.allCheck').find('input[type="checkbox"]').prop('checked', true);
            $('.allCheck').find('input[type="checkbox"]').prop('disabled', true);
        } else {
            // Enable all main group checkboxes and uncheck them
            $('.allCheck').find('input[type="checkbox"]').prop('checked', false);
            $('.allCheck').find('input[type="checkbox"]').prop('disabled', false);
        }
    });
    
    // Streicher checkbox behavior
    $("#Streicher").change(function() {
        if ($(this).prop('checked')) {
            // Select and disable all string checkboxes
            $('.subCheckStr').find('input[type="checkbox"]').prop('checked', true);
            $('.subCheckStr').find('input[type="checkbox"]').prop('disabled', true);
        } else {
            // Deselect all string checkboxes and enable them
            $('.subCheckStr').find('input[type="checkbox"]').prop('checked', false);
            $('.subCheckStr').find('input[type="checkbox"]').prop('disabled', false);
        }
    });
    
    // Bläser checkbox behavior
    $("#Bläser").change(function() {
        if ($(this).prop('checked')) {
            // Select and disable all wind checkboxes
            $('.subCheckBl').find('input[type="checkbox"]').prop('checked', true);
            $('.subCheckBl').find('input[type="checkbox"]').prop('disabled', true);
        } else {
            // Deselect all wind checkboxes and enable them
            $('.subCheckBl').find('input[type="checkbox"]').prop('checked', false);
            $('.subCheckBl').find('input[type="checkbox"]').prop('disabled', false);
        }
    });
    
    // Blechbläser checkbox behavior
    $("#BBläser").change(function() {
        if ($(this).prop('checked')) {
            // Select and disable all brass checkboxes
            $('.subCheckBBl').find('input[type="checkbox"]').prop('checked', true);
            $('.subCheckBBl').find('input[type="checkbox"]').prop('disabled', true);
        } else {
            // Deselect all brass checkboxes and enable them
            $('.subCheckBBl').find('input[type="checkbox"]').prop('checked', false);
            $('.subCheckBBl').find('input[type="checkbox"]').prop('disabled', false);
        }
    });
    
    // Holzbläser checkbox behavior
    $("#HBläser").change(function() {
        if ($(this).prop('checked')) {
            // Select and disable all woodwind checkboxes
            $('.subCheckHBl').find('input[type="checkbox"]').prop('checked', true);
            $('.subCheckHBl').find('input[type="checkbox"]').prop('disabled', true);
        } else {
            // Deselect all woodwind checkboxes and enable them
            $('.subCheckHBl').find('input[type="checkbox"]').prop('checked', false);
            $('.subCheckHBl').find('input[type="checkbox"]').prop('disabled', false);
        }
    });
    
    // Disable clicking directly on input elements that are disabled
    $('.custom-control-input').click(function(event) {
        if ($(this).prop('disabled')) {
            event.preventDefault();
            event.stopPropagation();
        }
    });
    
    // Rehearsal type radio-like behavior
    $('input[name="rehearsal_type"]').change(function() {
        if ($(this).prop('checked')) {
            $('input[name="rehearsal_type"]').not(this).prop('checked', false);
        }
    });
});
</script> 
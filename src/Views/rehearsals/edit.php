<?php $this->layout('layouts/default', ['title' => 'Edit Rehearsal', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

<div class="container-app mt-6">
    <?php if (!empty($errors)): ?>
    <script>
        <?php foreach ($errors as $error): ?>
            window.notifyError('<?= htmlspecialchars($error) ?>', { timer: 5000 });
        <?php endforeach; ?>
    </script>
    <?php endif; ?>
    
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-700 text-center mb-8">Termin bearbeiten</h1>
            
            <form method="post" action="/rehearsals/edit/<?= $rehearsal['id'] ?>">
                <input class="form-input mb-5" type="date" id="date" name="date" value="<?= htmlspecialchars($formData['date'] ?? '') ?>" placeholder="Datum" required minlength="3" maxlength="50">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div>
                        </div></div>
                        <label for="start_time" class="block text-left mb-2 font-medium">Startzeit</label>
                        <input class="form-input" type="time" id="start_time" name="start_time" value="<?= htmlspecialchars($formData['start_time'] ?? '') ?>" placeholder="Startzeit" required>
                    </div></div>
                    <div>
                        </div></div>
                        <label for="end_time" class="block text-left mb-2 font-medium">Endzeit</label>
                        <input class="form-input" type="time" id="end_time" name="end_time" value="<?= htmlspecialchars($formData['end_time'] ?? '') ?>" placeholder="Endzeit" required>
                    </div></div>
                </div>
                
                <input class="form-input mb-5" type="text" id="location" name="location" value="<?= htmlspecialchars($formData['location'] ?? '') ?>" placeholder="Ort" required minlength="3" maxlength="50">
                
                <div class="mb-10 text-left">
                    <button id="dropD" class="w-full btn-base btn-outline text-black dropdown-toggle" data-toggle="dropdown" aria-expanded="false" type="button" style="background-color: <?= htmlspecialchars($formData['color'] ?? 'white') ?>;">Farbenauswahl</button>
                    <div role="menu" class="dropdown-menu pre-scrollable">
                        <a role="presentation" class="dropdown-item" href="#" id="white" style="background-color: white;"></a>
                        <a role="presentation" class="dropdown-item" href="#" id="red" style="background-color: #ffcccc;"></a>
                        <a role="presentation" class="dropdown-item" href="#" id="blue" style="background-color: #ccccff;"></a>
                        <a role="presentation" class="dropdown-item" href="#" id="yellow" style="background-color: #ffffcc;"></a>
                        <a role="presentation" class="dropdown-item" href="#" id="green" style="background-color: #ccffcc;"></a>
                    </div></div>
                    <input type="hidden" name="color" id="selectedColor" value="<?= htmlspecialchars($formData['color'] ?? 'white') ?>">
                </div>

                <div class="text-left">
                    <p class="my-2 text-muted font-semibold">Sondertermin (maximal eins)</p>
                    <div class="custom-checkbox">
                        <input type="checkbox" id="Konzertreise" name="rehearsal_type" value="Konzertreise" <?= ($formData['rehearsal_type'] ?? '') === 'Konzertreise' ? 'checked' : '' ?>>
                        </div></div>
                        <label for="Konzertreise">Konzertreise</label>
                    </div></div>
                    
                    <div class="custom-checkbox">
                        <input type="checkbox" id="Konzert" name="rehearsal_type" value="Konzert" <?= ($formData['rehearsal_type'] ?? '') === 'Konzert' ? 'checked' : '' ?>>
                        </div></div>
                        <label for="Konzert">Konzert</label>
                    </div></div>
                    
                    <div class="custom-checkbox">
                        <input type="checkbox" id="Generalprobe" name="rehearsal_type" value="Generalprobe" <?= ($formData['rehearsal_type'] ?? '') === 'Generalprobe' ? 'checked' : '' ?>>
                        </div></div>
                        <label for="Generalprobe">Generalprobe</label>
                    </div></div>
                    
                    <div class="custom-checkbox">
                        <input type="checkbox" id="Registerprobe" name="rehearsal_type" value="Registerprobe" <?= ($formData['rehearsal_type'] ?? '') === 'Registerprobe' ? 'checked' : '' ?>>
                        </div></div>
                        <label for="Registerprobe">Registerprobe</label>
                    </div></div>
                    
                    <p class="my-4 text-muted font-semibold">Gruppen</p>
                    <div class="form-checkbox-group">
                        <div class="custom-checkbox">
                            <input type="checkbox" id="is_small_group" name="is_small_group" value="1" <?= !empty($formData['is_small_group']) ? 'checked' : '' ?>>
                            </div></div>
                        <label for="is_small_group">Kleingruppe</label>
                        </div></div>
                    </div></div>
                    <div class="form-checkbox-group tutti">
                        <div class="custom-checkbox">
                            <input name="is_tutti" type="checkbox" id="Tutti" value="1" <?= !empty($formData['is_tutti']) ? 'checked' : '' ?>>
                            </div></div>
                        <label id="TuttiLabel" for="Tutti">Tutti</label>
                        </div></div>
                    </div></div>
                    
                    <div class="form-checkbox-group main-group indent-1 allCheck">
                        <div class="custom-checkbox">
                            <input type="checkbox" id="Streicher" name="groups[]" value="Streicher" <?= in_array('Streicher', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                            </div></div>
                        <label id="StreicherLabel" for="Streicher">Streicher</label>
                        </div></div>
                    </div></div>
                    
                    <div class="form-checkbox-group indent-2 allCheck subCheck subCheckStr"><div class="custom-checkbox"> ml-12">
                        <input  id="Vio1" name="groups[]" value="Violine_1" type="checkbox" <?= in_array('Violine_1', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label for="Vio1">Violine 1</label>
                    </div></div>
                    
                    <div class="form-checkbox-group indent-2 allCheck subCheck subCheckStr"><div class="custom-checkbox"> ml-12">
                        <input  id="Vio2" name="groups[]" value="Violine_2" type="checkbox" <?= in_array('Violine_2', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label for="Vio2">Violine 2</label>
                    </div></div>
                    
                    <div class="form-checkbox-group indent-2 allCheck subCheck subCheckStr"><div class="custom-checkbox"> ml-12">
                        <input  id="Br" name="groups[]" value="Bratsche" type="checkbox" <?= in_array('Bratsche', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label for="Br">Bratsche</label>
                    </div></div>
                    
                    <div class="form-checkbox-group indent-2 allCheck subCheck subCheckStr"><div class="custom-checkbox"> ml-12">
                        <input  id="Clo" name="groups[]" value="Cello" type="checkbox" <?= in_array('Cello', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label for="Clo">Cello</label>
                    </div></div>
                    
                    <div class="form-checkbox-group indent-2 allCheck subCheck subCheckStr"><div class="custom-checkbox"> ml-12">
                        <input  id="Kontrabass" name="groups[]" value="Kontrabass" type="checkbox" <?= in_array('Kontrabass', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label for="Kontrabass">Kontrabass</label>
                    </div></div>
                    
                    <div class="form-checkbox-group main-group indent-1 allCheck"><div class="custom-checkbox">" class="ml-6">
                        <input  type="checkbox" id="Bläser" name="groups[]" value="Bläser" <?= in_array('Bläser', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label  id="BläserLabel" for="Bläser">Bläser</label>
                    </div></div>
                    
                    <!-- Blechbläser group -->
                    <div class="form-checkbox-group main-group indent-1 allCheck"><div class="custom-checkbox"> subCheck subCheckBl" class="ml-12">
                        <input class="form-check-input custom-control-input subCheckBl" type="checkbox" name="groups[]" value="Blechbläser" id="BBläser" <?= in_array('Blechbläser', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label  id="BBläserLabel" for="BBläser">Blechbläser</label>
                    </div></div>
                    
                    <div class="form-checkbox-group main-group indent-1 allCheck"><div class="custom-checkbox"> doubleSubCheck subCheckBl subCheckBBl" class="ml-18">
                        <input  id="Tro" name="groups[]" value="Trompete" type="checkbox" <?= in_array('Trompete', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label  for="Tro">Trompete</label>
                    </div></div>
                    
                    <div class="form-checkbox-group main-group indent-1 allCheck"><div class="custom-checkbox"> doubleSubCheck subCheckBl subCheckBBl" class="ml-18">
                        <input  id="Po" name="groups[]" value="Posaune" type="checkbox" <?= in_array('Posaune', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label  for="Po">Posaune</label>
                    </div></div>
                    
                    <div class="form-checkbox-group main-group indent-1 allCheck"><div class="custom-checkbox"> doubleSubCheck subCheckBl subCheckBBl" class="ml-18">
                        <input  id="Ho" name="groups[]" value="Horn" type="checkbox" <?= in_array('Horn', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label  for="Ho">Horn</label>
                    </div></div>
                    
                    <div class="form-checkbox-group main-group indent-1 allCheck"><div class="custom-checkbox"> doubleSubCheck subCheckBl subCheckBBl" class="ml-18">
                        <input  id="Tu" name="groups[]" value="Tuba" type="checkbox" <?= in_array('Tuba', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label  for="Tu">Tuba</label>
                    </div></div>
                    
                    <!-- Holzbläser group -->
                    <div class="form-checkbox-group main-group indent-1 allCheck"><div class="custom-checkbox"> subCheck subCheckBl" class="ml-12">
                        <input  type="checkbox" id="HBläser" name="groups[]" value="Holzbläser" <?= in_array('Holzbläser', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label  id="HBläserLabel" for="HBläser">Holzbläser</label>
                    </div></div>
                    
                    <div class="form-checkbox-group main-group indent-1 allCheck"><div class="custom-checkbox"> doubleSubCheck subCheckBl subCheckHBl" class="ml-18">
                        <input  id="Fl" name="groups[]" value="Flöte" type="checkbox" <?= in_array('Flöte', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label  for="Fl">Flöte</label>
                    </div></div>
                    
                    <div class="form-checkbox-group main-group indent-1 allCheck"><div class="custom-checkbox"> doubleSubCheck subCheckBl subCheckHBl" class="ml-18">
                        <input  id="Ob" name="groups[]" value="Oboe" type="checkbox" <?= in_array('Oboe', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label  for="Ob">Oboe</label>
                    </div></div>
                    
                    <div class="form-checkbox-group main-group indent-1 allCheck"><div class="custom-checkbox"> doubleSubCheck subCheckBl subCheckHBl" class="ml-18">
                        <input  id="Kl" name="groups[]" value="Klarinette" type="checkbox" <?= in_array('Klarinette', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label  for="Kl">Klarinette</label>
                    </div></div>
                    
                    <div class="form-checkbox-group main-group indent-1 allCheck"><div class="custom-checkbox"> doubleSubCheck subCheckBl subCheckHBl" class="ml-18">
                        <input  id="Fa" name="groups[]" value="Fagott" type="checkbox" <?= in_array('Fagott', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label  for="Fa">Fagott</label>
                    </div></div>
                    
                    <div class="form-checkbox-group main-group indent-1 allCheck"><div class="custom-checkbox">" class="ml-6">
                        <input  type="checkbox" id="Schlagwerk" name="groups[]" value="Schlagwerk" <?= in_array('Schlagwerk', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label  id="SchlagwerkLabel" for="Schlagwerk">Schlagwerk</label>
                    </div></div>
                    
                    <div class="form-checkbox-group main-group indent-1 allCheck"><div class="custom-checkbox">" class="ml-6">
                        <input  type="checkbox" id="Andere" name="groups[]" value="Andere" <?= in_array('Andere', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        </div></div>
                        <label  for="Andere">Andere</label>
                    </div></div>
                </div>
                
                <div class="form-group">
                    <button class="btn-base btn-primary w-full" type="submit">Speichern</button>
                </div>
            </form>
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
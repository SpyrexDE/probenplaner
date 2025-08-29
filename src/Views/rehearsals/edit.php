<?php $this->layout('layouts/default', ['title' => 'Edit Rehearsal', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

<div class="container-app">
    <?php if (!empty($errors)): ?>
    <script>
        <?php foreach ($errors as $error): ?>
            window.notifyError('<?= htmlspecialchars($error) ?>', { timer: 5000 });
        <?php endforeach; ?>
    </script>
    <?php endif; ?>
    
    <div class="page-header">
        <h1 class="page-title">Termin bearbeiten</h1>
        <p class="page-subtitle">Ändere die Details der Probe oder des Konzerts</p>
    </div>
    
    <div class="form-container">
        <form method="post" action="/rehearsals/edit/<?= $rehearsal['id'] ?>" class="form">
            <div class="form-group">
                <label for="date" class="form-label">Datum</label>
                <input class="form-input" type="date" id="date" name="date" value="<?= htmlspecialchars($formData['date'] ?? '') ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_time" class="form-label">Startzeit</label>
                    <input class="form-input" type="time" id="start_time" name="start_time" value="<?= htmlspecialchars($formData['start_time'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_time" class="form-label">Endzeit</label>
                    <input class="form-input" type="time" id="end_time" name="end_time" value="<?= htmlspecialchars($formData['end_time'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="location" class="form-label">Ort</label>
                <input class="form-input" type="text" id="location" name="location" value="<?= htmlspecialchars($formData['location'] ?? '') ?>" required minlength="3" maxlength="50">
            </div>
            
            <div class="form-group">
                <label class="form-label">Farbenauswahl</label>
                <div class="color-picker">
                    <button id="dropD" class="btn-base btn-outline color-picker-btn" data-toggle="dropdown" aria-expanded="false" type="button" style="background-color: <?= htmlspecialchars($formData['color'] ?? 'white') ?>;">
                        Farbenauswahl
                    </button>
                    <div class="dropdown-menu color-options">
                        <a class="dropdown-item color-option" href="#" id="white" style="background-color: white;"></a>
                        <a class="dropdown-item color-option" href="#" id="red" style="background-color: #ffcccc;"></a>
                        <a class="dropdown-item color-option" href="#" id="blue" style="background-color: #ccccff;"></a>
                        <a class="dropdown-item color-option" href="#" id="yellow" style="background-color: #ffffcc;"></a>
                        <a class="dropdown-item color-option" href="#" id="green" style="background-color: #ccffcc;"></a>
                    </div>
                </div>
                <input type="hidden" name="color" id="selectedColor" value="<?= htmlspecialchars($formData['color'] ?? 'white') ?>">
            </div>

            <div class="form-section">
                <h3 class="form-section-title">Sondertermin (maximal eins)</h3>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="Konzertreise" name="rehearsal_type" value="Konzertreise" <?= ($formData['rehearsal_type'] ?? '') === 'Konzertreise' ? 'checked' : '' ?>>
                        <label for="Konzertreise">Konzertreise</label>
                    </div>
                    
                    <div class="checkbox-item">
                        <input type="checkbox" id="Konzert" name="rehearsal_type" value="Konzert" <?= ($formData['rehearsal_type'] ?? '') === 'Konzert' ? 'checked' : '' ?>>
                        <label for="Konzert">Konzert</label>
                    </div>
                    
                    <div class="checkbox-item">
                        <input type="checkbox" id="Generalprobe" name="rehearsal_type" value="Generalprobe" <?= ($formData['rehearsal_type'] ?? '') === 'Generalprobe' ? 'checked' : '' ?>>
                        <label for="Generalprobe">Generalprobe</label>
                    </div>
                    
                    <div class="checkbox-item">
                        <input type="checkbox" id="Registerprobe" name="rehearsal_type" value="Registerprobe" <?= ($formData['rehearsal_type'] ?? '') === 'Registerprobe' ? 'checked' : '' ?>>
                        <label for="Registerprobe">Registerprobe</label>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="form-section-title">Gruppen</h3>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="is_small_group" name="is_small_group" value="1" <?= !empty($formData['is_small_group']) ? 'checked' : '' ?>>
                        <label for="is_small_group">Kleingruppe</label>
                    </div>
                </div>
                
                <!-- Level 1: Root (Tutti) -->
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input name="is_tutti" type="checkbox" id="Tutti" value="1" <?= !empty($formData['is_tutti']) ? 'checked' : '' ?>>
                        <label id="TuttiLabel" for="Tutti">Tutti</label>
                    </div>
                </div>
                
                <!-- Level 2: Main Sections (under Tutti) -->
                <div class="checkbox-group sub-group">
                    <!-- Streicher -->
                    <div class="checkbox-item">
                        <input type="checkbox" id="Streicher" name="groups[]" value="Streicher" <?= in_array('Streicher', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label id="StreicherLabel" for="Streicher">Streicher</label>
                    </div>
                
                    <!-- Level 3: Individual instruments (under Streicher) -->
                    <div class="checkbox-group sub-sub-group">
                        <div class="checkbox-item">
                            <input id="Vio1" name="groups[]" value="Violine_1" type="checkbox" <?= in_array('Violine_1', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                            <label for="Vio1">Violine 1</label>
                        </div>
                        
                        <div class="checkbox-item">
                            <input id="Vio2" name="groups[]" value="Violine_2" type="checkbox" <?= in_array('Violine_2', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                            <label for="Vio2">Violine 2</label>
                        </div>
                        
                        <div class="checkbox-item">
                            <input id="Vla" name="groups[]" value="Viola" type="checkbox" <?= in_array('Viola', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                            <label for="Vla">Viola</label>
                        </div>
                        
                        <div class="checkbox-item">
                            <input id="Vc" name="groups[]" value="Violoncello" type="checkbox" <?= in_array('Violoncello', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                            <label for="Vc">Violoncello</label>
                        </div>
                        
                        <div class="checkbox-item">
                            <input id="Cb" name="groups[]" value="Kontrabass" type="checkbox" <?= in_array('Kontrabass', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                            <label for="Cb">Kontrabass</label>
                        </div>
                    </div>
                    
                    <!-- Holzbläser -->
                    <div class="checkbox-item">
                        <input type="checkbox" id="Holzblaeser" name="groups[]" value="Holzblaeser" <?= in_array('Holzblaeser', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label id="HolzblaeserLabel" for="Holzblaeser">Holzbläser</label>
                    </div>
                    
                    <!-- Level 3: Individual instruments (under Holzbläser) -->
                    <div class="checkbox-group sub-sub-group">
                        <div class="checkbox-item">
                            <input id="Fl" name="groups[]" value="Flöte" type="checkbox" <?= in_array('Flöte', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                            <label for="Fl">Flöte</label>
                        </div>
                        
                        <div class="checkbox-item">
                            <input id="Ob" name="groups[]" value="Oboe" type="checkbox" <?= in_array('Oboe', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                            <label for="Ob">Oboe</label>
                        </div>
                        
                        <div class="checkbox-item">
                            <input id="Kl" name="groups[]" value="Klarinette" type="checkbox" <?= in_array('Klarinette', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                            <label for="Kl">Klarinette</label>
                        </div>
                        
                        <div class="checkbox-item">
                            <input id="Fg" name="groups[]" value="Fagott" type="checkbox" <?= in_array('Fagott', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                            <label for="Fg">Fagott</label>
                        </div>
                    </div>
                    
                    <!-- Blechbläser -->
                    <div class="checkbox-item">
                        <input type="checkbox" id="Blechblaeser" name="groups[]" value="Blechblaeser" <?= in_array('Blechblaeser', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label id="BlechblaeserLabel" for="Blechblaeser">Blechbläser</label>
                    </div>
                    
                    <!-- Level 3: Individual instruments (under Blechbläser) -->
                    <div class="checkbox-group sub-sub-group">
                        <div class="checkbox-item">
                            <input id="Hr" name="groups[]" value="Horn" type="checkbox" <?= in_array('Horn', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                            <label for="Hr">Horn</label>
                        </div>
                        
                        <div class="checkbox-item">
                            <input id="Tr" name="groups[]" value="Trompete" type="checkbox" <?= in_array('Trompete', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                            <label for="Tr">Trompete</label>
                        </div>
                        
                        <div class="checkbox-item">
                            <input id="Po" name="groups[]" value="Posaune" type="checkbox" <?= in_array('Posaune', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                            <label for="Po">Posaune</label>
                        </div>
                        
                        <div class="checkbox-item">
                            <input id="Tu" name="groups[]" value="Tuba" type="checkbox" <?= in_array('Tuba', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                            <label for="Tu">Tuba</label>
                        </div>
                    </div>
                    
                    <!-- Schlagwerk -->
                    <div class="checkbox-item">
                        <input type="checkbox" id="Schlagzeug" name="groups[]" value="Schlagwerk" <?= in_array('Schlagwerk', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label id="SchlagzeugLabel" for="Schlagzeug">Schlagwerk</label>
                    </div>
                    
                    <!-- Andere -->
                    <div class="checkbox-item">
                        <input type="checkbox" id="Andere" name="groups[]" value="Andere" <?= in_array('Andere', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label id="AndereLabel" for="Andere">Andere</label>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Änderungen speichern</button>
                <a href="/rehearsals" class="btn-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</div>

<script>
// Color picker functionality
document.querySelectorAll('.color-option').forEach(function(option) {
    option.addEventListener('click', function(e) {
        e.preventDefault();
        const color = this.id;
        const colorValue = this.style.backgroundColor;
        
        document.getElementById('selectedColor').value = colorValue;
        document.getElementById('dropD').style.backgroundColor = colorValue;
    });
});

// 3-Level Hierarchy Logic:
// Level 1: Tutti (root) controls all main groups
// Level 2: Main groups (Streicher, Holzbläser, Blechbläser, Schlagwerk, Andere) control their sub-instruments
// Level 3: Individual instruments update their parent's state

// Tutti controls all main groups
document.getElementById('Tutti').addEventListener('change', function() {
    const isChecked = this.checked;
    
    // Select all main groups (Level 2)
    const mainGroups = document.querySelectorAll('.sub-group > .checkbox-item > input[type="checkbox"]');
    mainGroups.forEach(function(checkbox) {
        checkbox.checked = isChecked;
    });
    
    // Select all sub-instruments (Level 3)
    const subInstruments = document.querySelectorAll('.sub-sub-group input[type="checkbox"]');
    subInstruments.forEach(function(checkbox) {
        checkbox.checked = isChecked;
    });
});

// Main group selection logic (Level 2 controls Level 3)
document.querySelectorAll('.sub-group > .checkbox-item > input[type="checkbox"]').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        const isChecked = this.checked;
        
        // Find the next sub-sub-group (if any)
        let nextElement = this.closest('.checkbox-item').nextElementSibling;
        if (nextElement && nextElement.classList.contains('sub-sub-group')) {
            const subCheckboxes = nextElement.querySelectorAll('input[type="checkbox"]');
            subCheckboxes.forEach(function(subCheckbox) {
                subCheckbox.checked = isChecked;
            });
        }
        
        // Update Tutti state
        updateTuttiState();
    });
});

// Sub-instrument selection logic (Level 3 updates Level 2)
document.querySelectorAll('.sub-sub-group input[type="checkbox"]').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        // Find the parent main group checkbox
        const parentItem = this.closest('.sub-sub-group').previousElementSibling;
        const parentCheckbox = parentItem.querySelector('input[type="checkbox"]');
        
        // Check all siblings
        const siblingCheckboxes = this.closest('.sub-sub-group').querySelectorAll('input[type="checkbox"]');
        const checkedCount = Array.from(siblingCheckboxes).filter(cb => cb.checked).length;
        
        if (checkedCount === 0) {
            parentCheckbox.checked = false;
            parentCheckbox.indeterminate = false;
        } else if (checkedCount === siblingCheckboxes.length) {
            parentCheckbox.checked = true;
            parentCheckbox.indeterminate = false;
        } else {
            parentCheckbox.checked = false;
            parentCheckbox.indeterminate = true;
        }
        
        // Update Tutti state
        updateTuttiState();
    });
});

// Rehearsal type radio-like behavior (only one can be selected)
document.querySelectorAll('input[name="rehearsal_type"]').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        const currentCheckbox = this;
        if (this.checked) {
            // Uncheck all other rehearsal type checkboxes
            document.querySelectorAll('input[name="rehearsal_type"]').forEach(function(otherCheckbox) {
                if (otherCheckbox !== currentCheckbox) {
                    otherCheckbox.checked = false;
                }
            });
        }
    });
});

// Function to update Tutti state based on main groups
function updateTuttiState() {
    const tuttiCheckbox = document.getElementById('Tutti');
    const mainGroupCheckboxes = document.querySelectorAll('.sub-group > .checkbox-item > input[type="checkbox"]');
    
    // Count checked and total main groups
    const checkedCount = Array.from(mainGroupCheckboxes).filter(cb => cb.checked).length;
    const totalCount = mainGroupCheckboxes.length;
    
    // Update Tutti state based on main groups
    if (checkedCount === 0) {
        tuttiCheckbox.checked = false;
        tuttiCheckbox.indeterminate = false;
    } else if (checkedCount === totalCount) {
        tuttiCheckbox.checked = true;
        tuttiCheckbox.indeterminate = false;
    } else {
        tuttiCheckbox.checked = false;
        tuttiCheckbox.indeterminate = true;
    }
}

// Make entire checkbox item clickable
document.querySelectorAll('.checkbox-item').forEach(function(item) {
    const checkbox = item.querySelector('input[type="checkbox"]');
    const label = item.querySelector('label');
    
    // Click on the entire item toggles the checkbox
    item.addEventListener('click', function(e) {
        // Don't trigger if clicking directly on the checkbox or label
        if (e.target === checkbox || e.target === label) {
            return;
        }
        

        
        checkbox.checked = !checkbox.checked;
        checkbox.dispatchEvent(new Event('change'));
    });
});

// Initialize the form state
document.addEventListener('DOMContentLoaded', function() {
    updateTuttiState();
});
</script> 
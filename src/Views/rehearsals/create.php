<?php $this->layout('layouts/default', ['title' => 'Create Rehearsal', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

<div class="container-app">
    <?php if (!empty($errors)): ?>
    <script>
        <?php foreach ($errors as $error): ?>
            window.notifyError('<?= htmlspecialchars($error) ?>', { timer: 5000 });
        <?php endforeach; ?>
    </script>
    <?php endif; ?>
    
    <div class="page-header">
        <h1 class="page-title">Neuer Termin</h1>
        <p class="page-subtitle">Erstelle eine neue Probe oder einen Konzerttermin</p>
    </div>
    
    <div class="form-container">
        <form method="post" action="/rehearsals/create" class="form">
            <div class="form-group">
                <label for="date" class="form-label">Datum</label>
                <input type="date" id="date" name="date" value="<?= htmlspecialchars($formData['date'] ?? '') ?>" class="form-input" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_time" class="form-label">Startzeit</label>
                    <input type="time" id="start_time" name="start_time" value="<?= htmlspecialchars($formData['start_time'] ?? '') ?>" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="end_time" class="form-label">Endzeit</label>
                    <input type="time" id="end_time" name="end_time" value="<?= htmlspecialchars($formData['end_time'] ?? '') ?>" class="form-input" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="location" class="form-label">Ort</label>
                <input type="text" id="location" name="location" value="<?= htmlspecialchars($formData['location'] ?? '') ?>" class="form-input" required minlength="3" maxlength="50">
            </div>
            
            <div class="form-group">
                <label class="form-label">Farbenauswahl</label>
                <div class="color-picker">
                    <button type="button" id="dropD" class="btn-base btn-outline color-picker-btn" data-toggle="dropdown" aria-expanded="false" style="background-color: <?= htmlspecialchars($formData['color'] ?? 'white') ?>;">
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
                
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input name="is_tutti" type="checkbox" id="Tutti" value="1" <?= !empty($formData['is_tutti']) ? 'checked' : '' ?>>
                        <label id="TuttiLabel" for="Tutti">Tutti</label>
                    </div>
                </div>
                
                <div class="checkbox-group main-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="Streicher" name="groups[]" value="Streicher" <?= in_array('Streicher', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label id="StreicherLabel" for="Streicher">Streicher</label>
                    </div>
                </div>
                
                <div class="checkbox-group sub-group">
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
                
                <div class="checkbox-group main-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="Holzblaeser" name="groups[]" value="Holzblaeser" <?= in_array('Holzblaeser', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label id="HolzblaeserLabel" for="Holzblaeser">Holzbläser</label>
                    </div>
                </div>
                
                <div class="checkbox-group sub-group">
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
                
                <div class="checkbox-group main-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="Blechblaeser" name="groups[]" value="Blechblaeser" <?= in_array('Blechblaeser', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label id="BlechblaeserLabel" for="Blechblaeser">Blechbläser</label>
                    </div>
                </div>
                
                <div class="checkbox-group sub-group">
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
                
                <div class="checkbox-group main-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="Schlagzeug" name="groups[]" value="Schlagzeug" <?= in_array('Schlagzeug', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label id="SchlagzeugLabel" for="Schlagzeug">Schlagzeug</label>
                    </div>
                </div>
                
                <div class="checkbox-group sub-group">
                    <div class="checkbox-item">
                        <input id="Pk" name="groups[]" value="Pauke" type="checkbox" <?= in_array('Pauke', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label for="Pk">Pauke</label>
                    </div>
                    
                    <div class="checkbox-item">
                        <input id="Gl" name="groups[]" value="Glockenspiel" type="checkbox" <?= in_array('Glockenspiel', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label for="Gl">Glockenspiel</label>
                    </div>
                    
                    <div class="checkbox-item">
                        <input id="Xy" name="groups[]" value="Xylophon" type="checkbox" <?= in_array('Xylophon', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label for="Xy">Xylophon</label>
                    </div>
                    
                    <div class="checkbox-item">
                        <input id="Tr" name="groups[]" value="Triangel" type="checkbox" <?= in_array('Triangel', $formData['groups'] ?? []) ? 'checked' : '' ?>>
                        <label for="Tr">Triangel</label>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Termin erstellen</button>
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

// Group selection logic
document.getElementById('Tutti').addEventListener('change', function() {
    const isChecked = this.checked;
    const allCheckboxes = document.querySelectorAll('input[name="groups[]"]');
    
    allCheckboxes.forEach(function(checkbox) {
        checkbox.checked = isChecked;
    });
});

// Main group selection logic
document.querySelectorAll('.main-group input').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        const isChecked = this.checked;
        const subGroup = this.closest('.main-group').nextElementSibling;
        
        if (subGroup && subGroup.classList.contains('sub-group')) {
            const subCheckboxes = subGroup.querySelectorAll('input[type="checkbox"]');
            subCheckboxes.forEach(function(subCheckbox) {
                subCheckbox.checked = isChecked;
            });
        }
    });
});

// Sub group selection logic
document.querySelectorAll('.sub-group input').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        const mainGroup = this.closest('.sub-group').previousElementSibling;
        const mainCheckbox = mainGroup.querySelector('input[type="checkbox"]');
        const subCheckboxes = this.closest('.sub-group').querySelectorAll('input[type="checkbox"]');
        const checkedCount = Array.from(subCheckboxes).filter(cb => cb.checked).length;
        
        if (checkedCount === 0) {
            mainCheckbox.checked = false;
        } else if (checkedCount === subCheckboxes.length) {
            mainCheckbox.checked = true;
        } else {
            mainCheckbox.indeterminate = true;
        }
    });
});
</script> 
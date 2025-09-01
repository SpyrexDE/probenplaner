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
                <?php 
                $selectedColor = $formData['color'] ?? null;
                include __DIR__ . '/../components/compact-color-picker.php'; 
                ?>
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
                
                <?php 
                // Use the dynamic group selector component
                include __DIR__ . '/../components/dynamic-group-selector.php';
                ?>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Termin erstellen</button>
                <a href="/rehearsals" class="btn-outline">Abbrechen</a>
            </div>
        </form>
    </div>
</div>

<script src="/assets/js/compact-color-picker.js"></script>
<script>
// Color picker functionality is now handled by compact-color-picker.js

// 3-Level Hierarchy Logic:
// Level 1: Tutti (root) controls all main groups
// Level 2: Main sections control their sub-instruments dynamically
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
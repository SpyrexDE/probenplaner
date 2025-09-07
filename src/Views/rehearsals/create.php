<?php $this->layout('layouts/default', ['title' => 'Create Rehearsal', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

<div class="container-app">
    <?php if (!empty($errors)): ?>
    <script>
        <?php foreach ($errors as $error): ?>
            window.notifyError('<?= htmlspecialchars($error) ?>', { timer: 5000 });
        <?php endforeach; ?>
    </script>
    <?php endif; ?>
    
    
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
                        <label for="is_small_group"><?= \App\Core\RehearsalTypeManager::LABEL_KLEINGRUPPE ?></label>
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

// Group selection is now handled by the dynamic-group-selector component
</script> 
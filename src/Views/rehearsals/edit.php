<?php $this->layout('layouts/default', ['title' => 'Probe bearbeiten', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

<?php
// Component styles
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';
include __DIR__ . '/../components/tree-checkbox.php';
include __DIR__ . '/../components/checkbox.php';
?>

<div class="container-app">
    <?php if (!empty($errors)): ?>
    <script>
        <?php foreach ($errors as $error): ?>
            window.notifyError('<?= htmlspecialchars($error) ?>', { timer: 5000 });
        <?php endforeach; ?>
    </script>
    <?php endif; ?>
    
    
    <div class="form-container">
        <form method="post" action="/<?= $_SESSION['current_orchestra_id'] ?>/rehearsals/edit/<?= $rehearsal['id'] ?>" class="form">
            <div class="form-group">
                <?php 
                $start_value = $formData['start'] ?? '';
                $end_value = $formData['end'] ?? '';
                $start_name = 'start';
                $end_name = 'end';
                $label_start = 'Anfang';
                $label_end = 'Ende';
                $required = true;
                include __DIR__ . '/../components/datetime-range-picker.php'; 
                ?>
            </div>
            
            <div class="form-group">
                <label for="location" class="form-label">Ort</label>
                <input class="form-input" type="text" id="location" name="location" value="<?= htmlspecialchars($formData['location'] ?? '') ?>" maxlength="50">
            </div>
            
            <div class="form-group">
                <?php 
                $selectedColor = $formData['color'] ?? null;
                include __DIR__ . '/../components/compact-color-picker.php'; 
                ?>
            </div>

            <div class="form-section">
                <?php 
                $name = 'rehearsal_type';
                $id = 'rehearsal_type';
                $label = 'Sondertermin';
                $value = $formData['rehearsal_type'] ?? '';
                $suggestions = ['Konzertreise', 'Konzert', 'Generalprobe', 'Registerprobe', 'Probenwochenende', 'Dozentenregisterprobe'];
                $placeholder = 'Sondertermin eingeben oder auswählen';
                $required = false;
                include __DIR__ . '/../components/autocomplete-input.php'; 
                ?>
            </div>
            
            <div class="form-section">
                <h3 class="form-section-title">Gruppen</h3>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="is_small_group" name="is_small_group" value="1" <?= !empty($formData['is_small_group']) ? 'checked' : '' ?>>
                        <label for="is_small_group"><?= \App\Core\RehearsalTypeManager::LABEL_SMALL_GROUP ?></label>
                    </div>
                </div>
                
                <?php 
                // Dynamic group selector
                include __DIR__ . '/../components/dynamic-group-selector.php';
                ?>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Änderungen speichern</button>
                <a href="/<?= $_SESSION['current_orchestra_id'] ?>/rehearsals" class="btn-outline">Abbrechen</a>
            </div>
        </form>
    </div>
</div>

<script src="/assets/js/compact-color-picker.js"></script>
<script>
</script>
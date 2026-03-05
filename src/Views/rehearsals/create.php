<?php $this->layout('layouts/default', ['title' => 'Probe anlegen', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

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
                window.notifyError('<?= htmlspecialchars($error) ?>', {
                    timer: 5000
                });
            <?php endforeach; ?>
        </script>
    <?php endif; ?>


    <div class="form-container">
        <form method="post" action="/<?= ($_SESSION['current_org_slug'] ?? '') . '/' . $_SESSION['current_orchestra_slug'] ?>/rehearsals/create" class="form">
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
                <input type="text" id="location" name="location" value="<?= htmlspecialchars($formData['location'] ?? '') ?>" class="form-input" maxlength="50">
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

                <?php
                $tagSelectName = 'role_ids';
                $tagSelectId = 'rehearsalRoleSelect';
                $tagSelectLabel = 'Gilt für Rollen';
                $tagSelectPlaceholder = 'Rolle hinzufügen…';
                $tagSelectOptions = array_map(fn($r) => [
                    'id' => $r['id'],
                    'name' => $r['name'],
                    'color' => $r['tag_color'] ?? '#478cf4',
                    'is_default' => $r['is_default'] ?? 0,
                ], $availableRoles ?? []);
                $tagSelectSelected = $formData['role_ids'] ?? [];
                include __DIR__ . '/../components/tag-select.php';
                ?>

                <?php
                include __DIR__ . '/../components/dynamic-group-selector.php';
                ?>
            </div>

            <?php
            // Re-use autoSave and apiUrl variables for consistency, though create doesn't autosave
            $autoSave = false;
            $apiUrl = '';
            include __DIR__ . '/../components/infobox-editor.php';
            ?>

            <?php
            include __DIR__ . '/../components/schedule-editor.php';
            ?>


            <div class="form-actions">
                <button type="submit" class="btn-primary">Termin erstellen</button>
                <a href="/<?= ($_SESSION['current_org_slug'] ?? '') . '/' . $_SESSION['current_orchestra_slug'] ?>/rehearsals" class="btn-outline">Abbrechen</a>
            </div>
        </form>
    </div>
</div>

<script src="/assets/js/compact-color-picker.js"></script>
<script>
</script>
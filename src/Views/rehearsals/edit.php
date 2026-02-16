<?php $this->layout('layouts/default', ['title' => 'Probe bearbeiten', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';
include __DIR__ . '/../components/tree-checkbox.php';
include __DIR__ . '/../components/checkbox.php';
include __DIR__ . '/../components/modern-checkbox.php';
$renderComponent = true;

$orchestraId = $_SESSION['current_orchestra_id'] ?? '';
$backUrl = '/' . $orchestraId . '/rehearsals';
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
        <div class="form">
            <a href="<?= $backUrl ?>" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Zurück
            </a>

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

            <div class="form-section" id="groupsSection">
                <h3 class="form-section-title">Gruppen</h3>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="is_small_group" name="is_small_group" value="1" <?= !empty($formData['is_small_group']) ? 'checked' : '' ?>>
                        <label for="is_small_group"><?= \App\Core\RehearsalTypeManager::LABEL_SMALL_GROUP ?></label>
                    </div>
                </div>

                <?php
                include __DIR__ . '/../components/dynamic-group-selector.php';
                ?>
            </div>

            <?php
            $autoSave = true;
            $apiUrl = '/' . $orchestraId . '/api/settings/rehearsal/' . $rehearsal['id'];
            include __DIR__ . '/../components/schedule-editor.php';
            ?>

            <a href="<?= $backUrl ?>" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Zurück
            </a>
        </div>
    </div>
</div>

<!-- Save indicator -->
<div id="settingsSaveIndicator" class="settings-save-indicator">
    <span class="indicator-text"></span>
</div>

<style>
    .settings-save-indicator {
        position: fixed;
        bottom: var(--space-4);
        right: var(--space-4);
        padding: var(--space-2) var(--space-4);
        border-radius: var(--radius-lg);
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-medium);
        display: none;
        align-items: center;
        gap: var(--space-2);
        z-index: 1000;
        box-shadow: var(--shadow-lg);
    }

    .settings-save-indicator.saving {
        background: var(--color-primary-100);
        color: var(--color-primary-700);
    }

    .settings-save-indicator.success {
        background: var(--color-success-100, #d1fae5);
        color: var(--color-success-700, #047857);
    }

    .settings-save-indicator.error {
        background: var(--color-error-100, #fee2e2);
        color: var(--color-error-700, #b91c1c);
    }
</style>

<script src="/assets/js/compact-color-picker.js"></script>
<script src="/assets/js/settings-engine.js"></script>
<script>
    (function() {
        const REHEARSAL_ID = '<?= $rehearsal['id'] ?>';
        const ORCHESTRA_ID = '<?= $orchestraId ?>';
        const API_URL = `/${ORCHESTRA_ID}/api/settings/rehearsal/${REHEARSAL_ID}`;

        // ── Wire simple fields for auto-save ──
        const fieldMap = {
            start: 'input[name="start"]',
            end: 'input[name="end"]',
            location: 'input[name="location"]',
            color: 'input[name="color"]',
            type: 'input[name="rehearsal_type"]',
            is_small_group: 'input[name="is_small_group"]',
        };

        for (const [field, selector] of Object.entries(fieldMap)) {
            const el = document.querySelector(selector);
            if (!el) continue;
            el.dataset.field = field;
            el.dataset.entity = 'rehearsal';
            el.dataset.entityId = REHEARSAL_ID;
            el.dataset.orchestraId = ORCHESTRA_ID;
            el.dataset.saveMode = 'auto';
            el.dataset.fieldType = el.type === 'checkbox' ? 'toggle' :
                el.type === 'hidden' ? 'color' :
                el.type === 'datetime-local' ? 'datetime' :
                'text';
        }

        if (window.SettingsEngine) {
            window.SettingsEngine.init();
        }

        // ── Auto-save groups ──
        const groupsSection = document.getElementById('groupsSection');
        if (groupsSection) {
            groupsSection.addEventListener('change', function(e) {
                if (e.target.name !== 'groups[]') return;

                const checked = [...groupsSection.querySelectorAll('input[name="groups[]"]:checked')]
                    .map(cb => cb.value);

                if (window.SettingsEngine && window.SettingsEngine.showSaveState) {
                    window.SettingsEngine.showSaveState('saving');
                }

                fetch(API_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            field: 'groups',
                            value: JSON.stringify(checked)
                        }),
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            if (window.SettingsEngine && window.SettingsEngine.showSaveState) {
                                window.SettingsEngine.showSaveState('success');
                            }
                        } else {
                            if (window.SettingsEngine && window.SettingsEngine.showSaveState) {
                                window.SettingsEngine.showSaveState('error');
                            }
                            window.notifyErrorWithDetails('Fehler beim Speichern der Gruppen', data.debug_message || data.error || JSON.stringify(data));
                        }
                    })
                    .catch(err => {
                        if (window.SettingsEngine && window.SettingsEngine.showSaveState) {
                            window.SettingsEngine.showSaveState('error');
                        }
                        window.notifyErrorWithDetails('Netzwerkfehler', err.message || String(err));
                    });
            });
        }
    })();
</script>
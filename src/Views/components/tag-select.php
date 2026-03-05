<?php

/**
 * Tag-Select Component
 * Multi-select with tag pills and autocomplete dropdown.
 * 
 * Usage:
 * <?php
 * $tagSelectName = 'role_ids';
 * $tagSelectId = 'roleSelect';
 * $tagSelectLabel = 'Rollen';
 * $tagSelectPlaceholder = 'Rolle hinzufügen…';
 * $tagSelectOptions = [['id' => 1, 'name' => 'Mitglied', 'color' => '#10b981'], ...];
 * $tagSelectSelected = [1, 3];
 * include __DIR__ . '/tag-select.php';
 * ?>
 */
$tsName = $tagSelectName ?? 'tags';
$tsId = $tagSelectId ?? 'tagSelect_' . uniqid();
$tsLabel = $tagSelectLabel ?? '';
$tsPlaceholder = $tagSelectPlaceholder ?? 'Hinzufügen…';
$tsOptions = $tagSelectOptions ?? [];
$tsSelected = $tagSelectSelected ?? [];
?>

<style>
    .tag-select-wrapper {
        position: relative;
    }

    .tag-select-container {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        min-height: 44px;
        border: 2px solid var(--color-border);
        border-radius: var(--radius-base);
        background: var(--color-bg-primary);
        cursor: text;
        transition: border-color 0.2s;
    }

    .tag-select-container:focus-within {
        border-color: var(--color-primary);
    }

    /* Inherits base gradient from .role-tag, adds interactive sizing */
    .tag-select-tag {
        font-size: 13px;
        padding: 5px 10px;
        text-transform: none;
        animation: tagAppear 0.15s ease-out;
    }

    .tag-select-default-star {
        font-size: 10px;
    }

    @keyframes tagAppear {
        from {
            transform: scale(0.8);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    .tag-select-tag .tag-remove {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: transparent;
        cursor: pointer;
        font-size: 14px;
        line-height: 1;
        -webkit-text-fill-color: #9ca3af;
        opacity: 0.6;
        transition: opacity 0.15s;
    }

    .tag-select-tag .tag-remove:hover {
        opacity: 1;
    }

    .tag-select-tag.tag-locked {
        opacity: 0.7;
        cursor: default;
    }

    .tag-select-input {
        border: none;
        outline: none;
        background: transparent;
        flex: 1;
        min-width: 80px;
        font-size: var(--font-size-sm);
        color: var(--color-text-primary);
        padding: 2px 0;
    }

    .tag-select-input::placeholder {
        color: var(--color-text-tertiary);
    }

    .tag-select-dropdown {
        position: absolute;
        top: calc(100% - 2px);
        left: 0;
        right: 0;
        z-index: 50;
        max-height: 200px;
        overflow-y: auto;
        background: var(--color-bg-primary);
        border: 2px solid var(--color-primary);
        border-top: none;
        border-radius: 0 0 var(--radius-base) var(--radius-base);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        display: none;
    }

    .tag-select-dropdown.show {
        display: block;
    }

    .tag-select-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        cursor: pointer;
        font-size: var(--font-size-sm);
        color: var(--color-text-primary);
        transition: background 0.1s;
    }

    .tag-select-option:hover,
    .tag-select-option.highlighted {
        background: var(--color-bg-tertiary);
    }

    .tag-select-option.selected {
        opacity: 0.4;
        pointer-events: none;
    }

    .tag-select-option-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .tag-select-option .tag-select-default-star {
        font-size: 10px;
    }

    .tag-select-empty {
        padding: 10px 12px;
        font-size: var(--font-size-sm);
        color: var(--color-text-tertiary);
        text-align: center;
    }
</style>

<?php if ($tsLabel): ?>
    <label class="form-label-modern" for="<?= htmlspecialchars($tsId) ?>_input"><?= htmlspecialchars($tsLabel) ?></label>
<?php endif; ?>

<div class="tag-select-wrapper" id="<?= htmlspecialchars($tsId) ?>">
    <div class="tag-select-container" data-name="<?= htmlspecialchars($tsName) ?>">
        <?php foreach ($tsOptions as $opt):
            $isRemovable = !isset($opt['removable']) || $opt['removable'];
            if (in_array($opt['id'], $tsSelected)): ?>
                <span class="role-tag tag-select-tag<?= $isRemovable ? '' : ' tag-locked' ?>" data-id="<?= (int)$opt['id'] ?>" data-default="<?= !empty($opt['is_default']) ? '1' : '' ?>" data-removable="<?= $isRemovable ? '1' : '0' ?>" style="--role-color:<?= htmlspecialchars($opt['color'] ?? '#478cf4') ?>">
                    <?php if (!empty($opt['is_default'])): ?><i class="fas fa-star tag-select-default-star"></i><?php endif; ?>
                    <?= htmlspecialchars($opt['name']) ?>
                    <?php if ($isRemovable): ?><span class="tag-remove">&times;</span><?php endif; ?>
                </span>
                <?php if ($isRemovable): ?>
                    <input type="hidden" name="<?= htmlspecialchars($tsName) ?>[]" value="<?= (int)$opt['id'] ?>">
                <?php endif; ?>
        <?php endif;
        endforeach; ?>
        <input type="text" class="tag-select-input" id="<?= htmlspecialchars($tsId) ?>_input"
            placeholder="<?= htmlspecialchars($tsPlaceholder) ?>" autocomplete="off">
    </div>
    <div class="tag-select-dropdown">
        <?php foreach ($tsOptions as $opt):
            $isAddable = !isset($opt['addable']) || $opt['addable'];
            if (!$isAddable) continue;
        ?>
            <div class="tag-select-option <?= in_array($opt['id'], $tsSelected) ? 'selected' : '' ?>"
                data-id="<?= (int)$opt['id'] ?>"
                data-name="<?= htmlspecialchars($opt['name']) ?>"
                data-color="<?= htmlspecialchars($opt['color'] ?? '#478cf4') ?>"
                data-default="<?= !empty($opt['is_default']) ? '1' : '' ?>">
                <span class="tag-select-option-dot" style="background:<?= htmlspecialchars($opt['color'] ?? '#478cf4') ?>"></span>
                <?php if (!empty($opt['is_default'])): ?><i class="fas fa-star tag-select-default-star"></i><?php endif; ?>
                <?= htmlspecialchars($opt['name']) ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    (function() {
        const wrapper = document.getElementById('<?= $tsId ?>');
        if (!wrapper || wrapper.dataset.initialized) return;
        wrapper.dataset.initialized = '1';

        const container = wrapper.querySelector('.tag-select-container');
        const input = wrapper.querySelector('.tag-select-input');
        const dropdown = wrapper.querySelector('.tag-select-dropdown');
        const fieldName = container.dataset.name;
        let highlightIdx = -1;

        function getOptions() {
            return [...dropdown.querySelectorAll('.tag-select-option')];
        }

        function getVisible() {
            return getOptions().filter(o => o.style.display !== 'none' && !o.classList.contains('selected'));
        }

        function addTag(id, name, color) {
            const opt = dropdown.querySelector(`.tag-select-option[data-id="${id}"]`);
            const isDefault = opt?.dataset.default === '1';
            if (opt) opt.classList.add('selected');

            const tag = document.createElement('span');
            tag.className = 'role-tag tag-select-tag';
            tag.dataset.id = id;
            tag.dataset.default = isDefault ? '1' : '';
            tag.style.setProperty('--role-color', color);
            const star = isDefault ? '<i class="fas fa-star tag-select-default-star"></i>' : '';
            tag.innerHTML = `${star}${escHtml(name)} <span class="tag-remove">&times;</span>`;

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = fieldName + '[]';
            hidden.value = id;

            tag.querySelector('.tag-remove').addEventListener('click', (e) => {
                e.stopPropagation();
                removeTag(id);
            });

            container.insertBefore(tag, input);
            container.insertBefore(hidden, input);
            input.value = '';
            filterOptions('');
            dispatchChange();
        }

        function removeTag(id) {
            const tag = container.querySelector(`.tag-select-tag[data-id="${id}"]`);
            if (tag && tag.dataset.removable === '0') return;
            const hidden = container.querySelector(`input[type="hidden"][value="${id}"]`);
            if (tag) tag.remove();
            if (hidden) hidden.remove();

            const opt = dropdown.querySelector(`.tag-select-option[data-id="${id}"]`);
            if (opt) opt.classList.remove('selected');
            dispatchChange();
        }

        function dispatchChange() {
            const ids = [...container.querySelectorAll('input[type="hidden"]')].map(h => h.value);
            wrapper.dispatchEvent(new CustomEvent('tag-select:change', {
                detail: {
                    ids
                }
            }));
        }

        function filterOptions(query) {
            const q = query.toLowerCase().trim();
            getOptions().forEach(opt => {
                const name = opt.dataset.name.toLowerCase();
                opt.style.display = (!q || name.includes(q)) ? '' : 'none';
            });
            highlightIdx = -1;
            updateHighlight();
        }

        function updateHighlight() {
            const visible = getVisible();
            visible.forEach((o, i) => o.classList.toggle('highlighted', i === highlightIdx));
        }

        function showDropdown() {
            dropdown.classList.add('show');
        }

        function hideDropdown() {
            dropdown.classList.remove('show');
            highlightIdx = -1;
        }

        function escHtml(s) {
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        // Attach click handlers to server-rendered remove buttons
        container.querySelectorAll('.tag-select-tag .tag-remove').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const tag = btn.closest('.tag-select-tag');
                if (tag) removeTag(tag.dataset.id);
            });
        });

        container.addEventListener('click', () => {
            input.focus();
            showDropdown();
        });

        input.addEventListener('focus', () => {
            showDropdown();
            filterOptions(input.value);
        });
        input.addEventListener('input', () => {
            filterOptions(input.value);
            showDropdown();
        });

        input.addEventListener('keydown', (e) => {
            const visible = getVisible();

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                highlightIdx = Math.min(highlightIdx + 1, visible.length - 1);
                updateHighlight();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                highlightIdx = Math.max(highlightIdx - 1, 0);
                updateHighlight();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (highlightIdx >= 0 && visible[highlightIdx]) {
                    const opt = visible[highlightIdx];
                    addTag(opt.dataset.id, opt.dataset.name, opt.dataset.color);
                }
            } else if (e.key === 'Escape') {
                hideDropdown();
            } else if (e.key === 'Backspace' && input.value === '') {
                const tags = container.querySelectorAll('.tag-select-tag');
                if (tags.length) removeTag(tags[tags.length - 1].dataset.id);
            }
        });

        dropdown.addEventListener('mousedown', (e) => {
            const opt = e.target.closest('.tag-select-option');
            if (opt && !opt.classList.contains('selected')) {
                e.preventDefault();
                addTag(opt.dataset.id, opt.dataset.name, opt.dataset.color);
            }
        });

        wrapper.addEventListener('focusout', (e) => {
            if (!wrapper.contains(e.relatedTarget)) hideDropdown();
        });
    })();
</script>
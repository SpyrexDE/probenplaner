<?php

/**
 * Section Config Editor Component
 *
 * Inline drag-and-drop tree editor for customizing orchestra register structure.
 *
 * @param array  $editorConfig       The tree config to render (default or custom)
 * @param bool   $isCustom           Whether the orchestra already has a custom config
 * @param string $sectionConfigApiUrl API URL for auto-save
 */

$editorConfig      = $editorConfig ?? [];
$isCustom          = $isCustom ?? false;
$sectionConfigApiUrl = $sectionConfigApiUrl ?? '';
$editorId          = 'section-config-editor';
?>

<!-- Picmo Emoji Picker -->
<script>
    if (!window.picmo) {
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/picmo@5.8.5/dist/umd/index.min.js';
        document.head.appendChild(s);
    }
</script>

<style>
    .sce-tree {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sce-tree .sce-tree {
        padding-left: var(--space-6);
        border-left: 2px solid var(--color-border-light);
        margin-left: var(--space-4);
    }

    .sce-node {
        margin-bottom: var(--space-1);
        position: relative;
    }

    .sce-node-row {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        padding: var(--space-2) var(--space-3);
        border-radius: var(--radius-md);
        background: var(--color-bg-primary);
        border: 1px solid var(--color-border-light);
        transition: all 0.15s ease;
        min-height: 42px;
    }

    .sce-node-row:hover {
        border-color: var(--color-primary-300);
        box-shadow: var(--shadow-sm);
    }

    .sce-node-row.drag-inside {
        border-color: var(--color-primary);
        background: var(--color-primary-50);
    }

    /* Notion-style drop line */
    .sce-drop-line {
        position: absolute;
        left: 0;
        right: 0;
        height: 2px;
        background: var(--color-primary);
        pointer-events: none;
        z-index: 10;
        display: none;
    }

    .sce-drop-line::before {
        content: '';
        position: absolute;
        left: -3px;
        top: -3px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--color-primary);
    }

    .sce-drop-line.before {
        display: block;
        top: -2px;
    }

    .sce-drop-line.after {
        display: block;
        bottom: -2px;
        top: auto;
    }

    .sce-node-row.dragging {
        opacity: 0.4;
    }

    .sce-drag-handle {
        cursor: grab;
        color: var(--color-text-muted);
        font-size: 14px;
        padding: var(--space-1);
        flex-shrink: 0;
        touch-action: none;
    }

    .sce-drag-handle:active {
        cursor: grabbing;
    }



    .sce-name-input {
        flex: 1;
        min-width: 0;
        border: none;
        background: transparent;
        font-size: 14px;
        font-weight: 500;
        color: var(--color-text-primary);
        padding: var(--space-1) var(--space-2);
        border-radius: var(--radius-sm);
        outline: none;
        transition: background 0.15s;
    }

    .sce-name-input:hover,
    .sce-name-input:focus {
        background: var(--color-bg-secondary);
    }

    .sce-name-input::placeholder {
        color: var(--color-text-muted);
        font-weight: 400;
    }

    .sce-actions {
        display: flex;
        gap: var(--space-1);
        flex-shrink: 0;
    }

    .sce-action-btn {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: none;
        color: var(--color-text-muted);
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-size: 12px;
        transition: all 0.15s;
    }

    .sce-action-btn:hover {
        background: var(--color-bg-tertiary);
        color: var(--color-text-primary);
    }

    .sce-action-btn.danger:hover {
        background: var(--color-error-50);
        color: var(--color-error);
    }

    .sce-toggle-btn {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: none;
        color: var(--color-text-muted);
        cursor: pointer;
        font-size: 11px;
        transition: transform 0.2s;
        flex-shrink: 0;
    }

    .sce-toggle-btn.open {
        transform: rotate(90deg);
        color: var(--color-primary);
    }

    .sce-toolbar {
        display: flex;
        align-items: center;
        gap: var(--space-3);
        padding: var(--space-3) 0;
        flex-wrap: wrap;
    }

    .sce-drop-indicator {
        height: 3px;
        background: var(--color-primary);
        border-radius: 2px;
        margin: var(--space-1) 0;
        display: none;
        transition: all 0.15s;
    }

    .sce-drop-indicator.visible {
        display: block;
    }

    .sce-emoji-btn {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--color-border-light);
        background: var(--color-bg-secondary);
        border-radius: var(--radius-md);
        cursor: pointer;
        font-size: 16px;
        flex-shrink: 0;
        transition: all 0.15s;
        line-height: 1;
        padding: 0;
    }

    .sce-emoji-btn:hover {
        border-color: var(--color-primary-300);
        background: var(--color-primary-50);
        transform: scale(1.1);
    }

    .picmo__picker {
        box-shadow: var(--shadow-xl);
        border: 1px solid var(--color-border);
        z-index: 9999;
    }

    @media (max-width: 640px) {
        .sce-tree .sce-tree {
            padding-left: var(--space-3);
            margin-left: var(--space-2);
        }

        .sce-name-input {
            font-size: 13px;
        }
    }
</style>

<div class="modern-card" id="<?= $editorId ?>-wrapper">
    <!-- Toolbar -->
    <div class="modern-card-body" style="border-bottom: 1px solid var(--color-border-light); padding: var(--space-3) var(--space-5);">
        <div class="sce-toolbar">
            <button type="button" class="btn-modern btn-outline btn-sm" id="<?= $editorId ?>-add-root" title="Neuen Eintrag hinzufügen">
                <?= icon('plus', 'btn-icon') ?> Hinzufügen
            </button>
            <div style="flex:1;"></div>
            <button type="button" class="btn-modern btn-outline btn-sm" id="<?= $editorId ?>-reset" style="display: none;">
                <?= icon('undo', 'btn-icon') ?> Standard laden
            </button>
        </div>
    </div>

    <!-- Tree -->
    <div class="modern-card-body" style="padding: var(--space-4) var(--space-5);">
        <ul class="sce-tree" id="<?= $editorId ?>-root"></ul>
    </div>
</div>


<script>
    (function() {
        const EDITOR_ID = '<?= $editorId ?>';
        const API_URL = '<?= htmlspecialchars($sectionConfigApiUrl) ?>';

        const DEFAULT_EMOJI = '🎶';

        let tree = <?= json_encode($editorConfig) ?>;
        let isCustom = <?= $isCustom ? 'true' : 'false' ?>;
        const defaultTree = <?= json_encode($editorConfig) ?>;

        const rootEl = document.getElementById(EDITOR_ID + '-root');

        // --- Unique ID generator ---
        let idCounter = 0;

        function uid() {
            return 'node_' + Date.now() + '_' + (idCounter++);
        }

        // --- Convert tree to flat working model ---
        // Working model: array of nodes with { _uid, id, display_name, type, emoji?, bg?, tc?, plural?, aliases?, special_rules?, children: [] }
        function ensureUids(nodes) {
            if (!nodes || typeof nodes !== 'object') return {};
            const result = {};
            for (const [key, node] of Object.entries(nodes)) {
                if (!node || typeof node !== 'object' || !node.id) continue;
                const n = {
                    ...node,
                    _uid: uid(),
                    _key: key
                };
                if (n.children && typeof n.children === 'object') {
                    n.children = ensureUids(n.children);
                }
                result[key] = n;
            }
            return result;
        }

        let workingTree = ensureUids(tree);

        // --- Render ---
        function render() {
            rootEl.innerHTML = '';
            const tuttiNode = workingTree.tutti;
            if (tuttiNode && tuttiNode.children) {
                renderNodes(tuttiNode.children, rootEl);
            } else {
                renderNodes(workingTree, rootEl);
            }
        }

        function renderNodes(nodes, parentEl) {
            for (const [key, node] of Object.entries(nodes)) {
                if (!node || !node.id) continue;
                const li = createNodeEl(node, key);
                parentEl.appendChild(li);
            }
        }

        function createNodeEl(node, key) {
            const li = document.createElement('li');
            li.className = 'sce-node';
            li.dataset.uid = node._uid;

            const hasChildren = node.children && Object.keys(node.children).length > 0;

            // Row
            const row = document.createElement('div');
            row.className = 'sce-node-row';

            // Toggle
            const toggle = document.createElement('button');
            toggle.className = 'sce-toggle-btn' + (hasChildren ? ' open' : '');
            toggle.type = 'button';
            toggle.innerHTML = hasChildren ? '<i class="fas fa-chevron-right"></i>' : '<i class="fas fa-circle" style="font-size:5px;opacity:0.3;"></i>';
            toggle.onclick = () => toggleChildren(li, toggle);
            if (!hasChildren) toggle.style.pointerEvents = 'none';

            // Drag handle
            const handle = document.createElement('span');
            handle.className = 'sce-drag-handle';
            handle.innerHTML = '<i class="fas fa-grip-vertical"></i>';

            // Emoji button (sections only)
            const isSection = hasChildren || node.emoji;
            let emojiBtn = null;
            if (isSection) {
                if (!node.emoji) node.emoji = DEFAULT_EMOJI;
                emojiBtn = document.createElement('button');
                emojiBtn.type = 'button';
                emojiBtn.className = 'sce-emoji-btn';
                emojiBtn.textContent = node.emoji;
                emojiBtn.title = 'Emoji ändern';
                emojiBtn.onclick = (e) => {
                    e.stopPropagation();
                    openEmojiPicker(emojiBtn, node);
                };
            }

            // Name input
            const input = document.createElement('input');
            input.className = 'sce-name-input';
            input.type = 'text';
            input.value = node.display_name || node.id;
            input.placeholder = 'Name…';
            let nameDebounce = null;
            input.oninput = () => {
                node.display_name = input.value;
                node.id = input.value.replace(/\s+/g, '_');
                clearTimeout(nameDebounce);
                nameDebounce = setTimeout(() => save(), 600);
            };
            input.onkeydown = (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    input.blur();
                }
            };

            // Actions
            const actions = document.createElement('div');
            actions.className = 'sce-actions';

            const addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'sce-action-btn';
            addBtn.title = 'Kind hinzufügen';
            addBtn.innerHTML = '<i class="fas fa-plus"></i>';
            addBtn.onclick = () => addChild(node, li);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'sce-action-btn danger';
            removeBtn.title = 'Entfernen';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.onclick = () => removeNode(node._uid);

            actions.appendChild(addBtn);
            actions.appendChild(removeBtn);

            row.appendChild(toggle);
            row.appendChild(handle);
            if (emojiBtn) row.appendChild(emojiBtn);
            row.appendChild(input);
            row.appendChild(actions);

            // Drop indicator lines
            const dropBefore = document.createElement('div');
            dropBefore.className = 'sce-drop-line';
            const dropAfter = document.createElement('div');
            dropAfter.className = 'sce-drop-line';

            li.appendChild(dropBefore);
            li.appendChild(row);
            li.appendChild(dropAfter);

            // Children
            if (hasChildren) {
                const childUl = document.createElement('ul');
                childUl.className = 'sce-tree';
                renderNodes(node.children, childUl);
                li.appendChild(childUl);
            }

            // Drag events on li (covers row + gap)
            setupDrag(row, li, node);

            return li;
        }

        function toggleChildren(li, toggleBtn) {
            const childUl = li.querySelector(':scope > .sce-tree');
            if (!childUl) return;
            const isHidden = childUl.style.display === 'none';
            childUl.style.display = isHidden ? '' : 'none';
            toggleBtn.classList.toggle('open', isHidden);
        }


        function addChild(parentNode, parentLi) {
            if (!parentNode.children) parentNode.children = {};
            const newKey = 'new_' + uid();
            parentNode.children[newKey] = {
                _uid: uid(),
                _key: newKey,
                id: 'Neu',
                display_name: 'Neu',
                children: {}
            };
            render();
            const inputs = rootEl.querySelectorAll('.sce-name-input');
            const last = inputs[inputs.length - 1];
            if (last && last.value === 'Neu') {
                last.focus();
                last.select();
            }
            save();
        }

        function addRootSection() {
            const targetNodes = workingTree.tutti?.children ?? workingTree;
            const newKey = 'new_' + uid();
            targetNodes[newKey] = {
                _uid: uid(),
                _key: newKey,
                id: 'Neu',
                display_name: 'Neu',
                children: {}
            };
            render();
            const inputs = rootEl.querySelectorAll('.sce-name-input');
            const last = inputs[inputs.length - 1];
            if (last) {
                last.focus();
                last.select();
            }
            save();
        }

        // Derive API base for section-members / reassign-members endpoints
        const API_BASE = API_URL.replace(/\/settings\/orchestra\/\d+$/, '');

        function collectGroupIds(node) {
            const ids = [node.id];
            if (node.children) {
                for (const child of Object.values(node.children)) {
                    ids.push(...collectGroupIds(child));
                }
            }
            return ids;
        }

        function findNodeByUid(nodes, uid) {
            for (const node of Object.values(nodes)) {
                if (node._uid === uid) return node;
                if (node.children) {
                    const found = findNodeByUid(node.children, uid);
                    if (found) return found;
                }
            }
            return null;
        }

        function getAllLeafIds(nodes, exclude = []) {
            const ids = [];
            for (const node of Object.values(nodes)) {
                if (!exclude.includes(node.id)) {
                    const hasKids = node.children && Object.keys(node.children).length > 0;
                    if (!hasKids) {
                        ids.push({
                            id: node.id,
                            name: node.display_name || node.id
                        });
                    }
                    if (node.children) {
                        ids.push(...getAllLeafIds(node.children, exclude));
                    }
                }
            }
            return ids;
        }

        async function removeNode(targetUid) {
            const allNodes = workingTree.tutti?.children ?? workingTree;
            const targetNode = findNodeByUid(allNodes, targetUid);
            if (!targetNode) return;

            const deletedIds = collectGroupIds(targetNode);

            // Check for affected members
            let members = [];
            try {
                const res = await fetch(API_BASE + '/section-members', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        types: deletedIds
                    })
                });
                const data = await res.json();
                members = data.members || [];
            } catch (e) {
                /* proceed without check */
            }

            if (members.length === 0) {
                doRemove(targetUid);
                return;
            }

            // Build remaining groups for dropdowns
            const remaining = getAllLeafIds(allNodes, deletedIds);
            if (remaining.length === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Keine Zielgruppe',
                        text: 'Es gibt keine verbleibenden Gruppen, in die Mitglieder verschoben werden könnten.',
                        customClass: {
                            popup: 'swal-custom-popup'
                        }
                    });
                }
                return;
            }

            const optionsHtml = remaining.map(g => `<option value="${g.id}">${g.name}</option>`).join('');

            const memberRows = members.map((m, i) => `
                <div class="swal-perm-row" style="justify-content: space-between;">
                    <span>${m.display_name} <small style="color:var(--color-text-muted);">(${m.type})</small></span>
                    <select class="swal-select-modern reassign-select" data-idx="${i}" style="width: auto; min-width: 140px;">
                        ${optionsHtml}
                    </select>
                </div>
            `).join('');

            const html = `
                <div class="swal-members-permissions">
                    <div class="swal-perm-group" style="margin-bottom: var(--space-4);">
                        <div class="swal-perm-title">Alle verschieben nach</div>
                        <select class="swal-select-modern" id="swal-bulk-move" style="width: 100%;">
                            ${optionsHtml}
                        </select>
                    </div>
                    <div class="swal-perm-group">
                        <div class="swal-perm-title">${members.length} betroffene Mitglieder</div>
                        ${memberRows}
                    </div>
                </div>
            `;

            if (typeof Swal === 'undefined') {
                if (!confirm(`${members.length} Mitglieder sind betroffen. Trotzdem löschen?`)) return;
                doRemove(targetUid);
                return;
            }

            const result = await Swal.fire({
                title: 'Mitglieder verschieben',
                html,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Verschieben & Löschen',
                cancelButtonText: 'Abbrechen',
                customClass: {
                    popup: 'swal-custom-popup',
                    confirmButton: 'swal-confirm-delete',
                    cancelButton: 'swal-cancel'
                },
                didOpen: () => {
                    const bulk = document.getElementById('swal-bulk-move');
                    bulk.addEventListener('change', () => {
                        document.querySelectorAll('.reassign-select').forEach(s => s.value = bulk.value);
                    });
                },
                preConfirm: () => {
                    const selects = document.querySelectorAll('.reassign-select');
                    return Array.from(selects).map((s, i) => ({
                        user_id: members[i].user_id,
                        new_type: s.value
                    }));
                }
            });

            if (!result.isConfirmed) return;

            // Reassign members then delete
            try {
                await fetch(API_BASE + '/reassign-members', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        moves: result.value
                    })
                });
            } catch (e) {
                showSaveState('error');
                return;
            }

            doRemove(targetUid);
        }

        function doRemove(targetUid) {
            function removeRecursive(nodes) {
                for (const [key, node] of Object.entries(nodes)) {
                    if (node._uid === targetUid) {
                        delete nodes[key];
                        return true;
                    }
                    if (node.children && removeRecursive(node.children)) return true;
                }
                return false;
            }
            removeRecursive(workingTree.tutti?.children ?? workingTree);
            render();
            save();
        }

        // --- Drag & Drop (zone-based) ---
        let draggedUid = null;

        function getDropZone(e, rowEl) {
            const rect = rowEl.getBoundingClientRect();
            const y = e.clientY - rect.top;
            const ratio = y / rect.height;
            if (ratio < 0.25) return 'before';
            if (ratio > 0.75) return 'after';
            return 'inside';
        }

        function clearDropFeedback() {
            document.querySelectorAll('.sce-drop-line.before,.sce-drop-line.after')
                .forEach(el => el.classList.remove('before', 'after'));
            document.querySelectorAll('.drag-inside')
                .forEach(el => el.classList.remove('drag-inside'));
        }

        function isDescendantOf(parentUid, childUid, nodes) {
            for (const node of Object.values(nodes)) {
                if (node._uid === parentUid) {
                    return containsUid(node.children || {}, childUid);
                }
                if (node.children && isDescendantOf(parentUid, childUid, node.children)) return true;
            }
            return false;
        }

        function containsUid(nodes, uid) {
            for (const node of Object.values(nodes)) {
                if (node._uid === uid) return true;
                if (node.children && containsUid(node.children, uid)) return true;
            }
            return false;
        }

        function setupDrag(rowEl, liEl, node) {
            // Only the handle initiates drag
            const handle = rowEl.querySelector('.sce-drag-handle');
            handle.addEventListener('mousedown', () => {
                rowEl.draggable = true;
            });
            rowEl.addEventListener('dragend', () => {
                rowEl.draggable = false;
            });

            rowEl.addEventListener('dragstart', (e) => {
                draggedUid = node._uid;
                rowEl.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', node._uid);
            });

            rowEl.addEventListener('dragend', () => {
                rowEl.classList.remove('dragging');
                clearDropFeedback();
                draggedUid = null;
            });

            liEl.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (!draggedUid || draggedUid === node._uid) return;
                e.dataTransfer.dropEffect = 'move';
                clearDropFeedback();
                const zone = getDropZone(e, rowEl);
                const lines = liEl.querySelectorAll(':scope > .sce-drop-line');
                if (zone === 'before') {
                    lines[0].classList.add('before');
                } else if (zone === 'after') {
                    lines[1].classList.add('after');
                } else {
                    rowEl.classList.add('drag-inside');
                }
            });

            liEl.addEventListener('dragleave', (e) => {
                if (!liEl.contains(e.relatedTarget)) {
                    const lines = liEl.querySelectorAll(':scope > .sce-drop-line');
                    lines.forEach(l => l.classList.remove('before', 'after'));
                    rowEl.classList.remove('drag-inside');
                }
            });

            liEl.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                clearDropFeedback();
                if (!draggedUid || draggedUid === node._uid) return;

                const allNodes = workingTree.tutti?.children ?? workingTree;
                if (isDescendantOf(draggedUid, node._uid, allNodes)) return;

                const zone = getDropZone(e, rowEl);
                moveNode(draggedUid, node._uid, zone);
                draggedUid = null;
            });
        }

        // Allow drops on the root container (prevents 'not allowed' cursor in empty areas)
        rootEl.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });

        function moveNode(sourceUid, targetUid, zone) {
            const allNodes = workingTree.tutti?.children ?? workingTree;

            let sourceNode = null;

            function extractSource(nodes) {
                for (const [key, node] of Object.entries(nodes)) {
                    if (node._uid === sourceUid) {
                        sourceNode = {
                            ...node
                        };
                        delete nodes[key];
                        return true;
                    }
                    if (node.children && extractSource(node.children)) return true;
                }
                return false;
            }
            extractSource(allNodes);
            if (!sourceNode) return;

            if (zone === 'inside') {
                // Nest as child of target
                function nestInside(nodes) {
                    for (const node of Object.values(nodes)) {
                        if (node._uid === targetUid) {
                            if (!node.children) node.children = {};
                            node.children[sourceNode._key || 'moved_' + uid()] = sourceNode;
                            return true;
                        }
                        if (node.children && nestInside(node.children)) return true;
                    }
                    return false;
                }
                nestInside(allNodes);
            } else {
                // Insert as sibling before or after target
                function insertSibling(nodes) {
                    const entries = Object.entries(nodes);
                    const rebuilt = {};
                    let found = false;
                    for (const [key, node] of entries) {
                        if (node._uid === targetUid && zone === 'before') {
                            rebuilt[sourceNode._key || 'moved_' + uid()] = sourceNode;
                            found = true;
                        }
                        rebuilt[key] = node;
                        if (node._uid === targetUid && zone === 'after') {
                            rebuilt[sourceNode._key || 'moved_' + uid()] = sourceNode;
                            found = true;
                        }
                    }
                    if (found) {
                        for (const k of Object.keys(nodes)) delete nodes[k];
                        for (const [k, v] of Object.entries(rebuilt)) nodes[k] = v;
                        return true;
                    }
                    for (const node of Object.values(nodes)) {
                        if (node.children && insertSibling(node.children)) return true;
                    }
                    return false;
                }
                insertSibling(allNodes);
            }

            render();
            save();
        }

        // --- Serialize (strip internal keys, infer type from structure) ---
        function serializeTree() {
            function clean(nodes) {
                const result = {};
                for (const [key, node] of Object.entries(nodes)) {
                    if (!node || !node.id) continue;
                    const cleaned = {};
                    for (const [k, v] of Object.entries(node)) {
                        if (k === '_uid' || k === '_key' || k === 'type') continue;
                        if (k === 'children') {
                            cleaned.children = clean(v || {});
                        } else {
                            cleaned[k] = v;
                        }
                    }
                    const hasKids = cleaned.children && Object.keys(cleaned.children).length > 0;
                    cleaned.type = hasKids ? 'section' : 'instrument';
                    result[key] = cleaned;
                }
                return result;
            }
            return clean(workingTree);
        }

        // --- Save ---
        let saveTimeout = null;

        function save() {
            isCustom = true;
            updateResetButton();
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(doSave, 400);
        }

        function doSave() {
            if (!API_URL) return;
            showSaveState('saving');

            const serialized = serializeTree();

            fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        field: 'section_config',
                        value: JSON.stringify(serialized)
                    })
                })
                .then(r => r.json())
                .then(data => {
                    showSaveState(data.success ? 'success' : 'error');
                })
                .catch(() => showSaveState('error'));
        }

        function resetToDefault() {
            if (!confirm('Registerstruktur auf Standard zurücksetzen?')) return;

            showSaveState('saving');
            fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        field: 'section_config',
                        value: 'null'
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showSaveState('success');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        showSaveState('error');
                    }
                })
                .catch(() => showSaveState('error'));
        }

        function showSaveState(state) {
            if (window.SettingsEngine?.showSaveState) {
                window.SettingsEngine.showSaveState(state);
            }
        }

        function updateResetButton() {
            const btn = document.getElementById(EDITOR_ID + '-reset');
            if (btn) btn.style.display = isCustom ? '' : 'none';
        }

        // --- Emoji Picker (picmo) ---
        let pmPicker = null;
        let pmContainer = null;
        let pmTargetNode = null;
        let pmTargetBtn = null;

        function initPicmo() {
            if (pmPicker) return;
            pmContainer = document.createElement('div');
            pmContainer.style.cssText = 'position:absolute;z-index:1000;display:none;';
            document.body.appendChild(pmContainer);

            pmPicker = picmo.createPicker({
                rootElement: pmContainer,
                showPreview: false,
                autoFocus: 'search',
                messages: {
                    searchPlaceholder: 'Suchen...',
                    noEmojisFound: 'Keine Emojis gefunden',
                    recents: 'Zuletzt verwendet'
                }
            });

            pmPicker.addEventListener('emoji:select', event => {
                if (pmTargetNode && pmTargetBtn) {
                    pmTargetNode.emoji = event.emoji;
                    pmTargetBtn.textContent = event.emoji;
                    hidePicmo();
                    save();
                }
            });

            document.addEventListener('click', (e) => {
                if (pmContainer.style.display === 'block' &&
                    !pmContainer.contains(e.target) &&
                    !e.target.closest('.sce-emoji-btn')) {
                    hidePicmo();
                }
            });
        }

        function openEmojiPicker(btnEl, node) {
            initPicmo();
            pmTargetNode = node;
            pmTargetBtn = btnEl;
            const rect = btnEl.getBoundingClientRect();
            pmContainer.style.top = (rect.bottom + window.scrollY + 5) + 'px';
            pmContainer.style.left = (rect.left + window.scrollX) + 'px';
            pmContainer.style.display = 'block';
        }

        function hidePicmo() {
            if (pmContainer) pmContainer.style.display = 'none';
            pmTargetNode = null;
            pmTargetBtn = null;
        }

        // --- Event bindings ---
        document.getElementById(EDITOR_ID + '-add-root').onclick = addRootSection;
        document.getElementById(EDITOR_ID + '-reset').onclick = resetToDefault;

        // --- Init ---
        render();
        updateResetButton();
    })();
</script>
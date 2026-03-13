<?php $this->layout('layouts/default', ['title' => 'Termine', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

<?php
$orchestraBase = ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '');
$rolesJson = json_encode(array_map(fn($r) => [
    'id' => (int)$r['id'],
    'name' => $r['name'],
    'color' => $r['tag_color'] ?? '#478cf4',
    'is_default' => !empty($r['is_default']),
    'is_self_assignable' => !empty($r['is_self_assignable']),
], $availableRoles ?? []), JSON_UNESCAPED_UNICODE);

$germanWeekdaysJs = json_encode(['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa']);
$germanMonthsJs = json_encode(['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez']);
?>

<div class="container-app pb-20">

    <?php if (empty($rehearsals)): ?>
        <?php
        if (!$showOld && ($hasPastRehearsals ?? false)) {
            $title = 'Keine aktuellen Termine';
            $message = 'Es stehen keine kommenden Proben an.';
            $actionHref = '?showOld=1';
            $actionLabel = 'Vergangene Termine anzeigen';
        } else {
            $title = 'Noch keine Termine';
            $message = 'Klicke unten auf „Neue Probe", um loszulegen.';
            $actionHref = null;
            $actionLabel = null;
        }
        include __DIR__ . '/../components/empty-state.php';
        ?>
    <?php else: ?>
        <?php
        $currentRehearsals = [];
        $pastRehearsals = [];
        $today = date('Y-m-d');
        foreach ($rehearsals as $rehearsal) {
            if ($rehearsal['date'] >= $today) {
                $currentRehearsals[] = $rehearsal;
            } else {
                $pastRehearsals[] = $rehearsal;
            }
        }
        ?>

        <?php if ($showOld && !empty($pastRehearsals)): ?>
            <div class="past-rehearsals-section" id="pastRehearsalsSection">
                <?php foreach ($pastRehearsals as $rehearsal): ?>
                    <?php
                    $context = 'inline-edit';
                    $options = ['showButtons' => false];
                    include __DIR__ . '/../components/rehearsal-card.php';
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($currentRehearsals) || !empty($pastRehearsals)): ?>
            <?php include __DIR__ . '/../components/date-separator.php'; ?>
        <?php endif; ?>

        <div id="rehearsalsList">
            <?php foreach ($currentRehearsals as $rehearsal): ?>
                <?php
                $context = 'inline-edit';
                $options = ['showButtons' => false];
                include __DIR__ . '/../components/rehearsal-card.php';
                ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php include __DIR__ . '/../components/rehearsal-add-box.php'; ?>
</div>

<!-- Groups selection modal (rendered once, reused for all cards) -->
<dialog class="ie-groups-dialog" id="ieGroupsModal">
    <div class="ie-groups-panel">
        <div class="ie-groups-header">
            <h3>Gruppen auswählen</h3>
            <button type="button" class="ie-groups-close" onclick="window.IEM && window.IEM.closeGroupsModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="ie-groups-body" id="ieGroupsBody">
            <?php
            $renderComponent = false;
            include __DIR__ . '/../components/tree-checkbox.php';
            $formData = ['groups' => []];
            include __DIR__ . '/../components/dynamic-group-selector.php';
            ?>
        </div>
    </div>
</dialog>
<script>
// Close dialog on backdrop click
document.getElementById('ieGroupsModal')?.addEventListener('click', function(e) {
    if (e.target === this) window.IEM?.closeGroupsModal();
});
</script>

<script src="/assets/js/settings-engine.js"></script>
<script>
(function() {
    'use strict';

    const BASE = '/<?= $orchestraBase ?>';
    const ROLES = <?= $rolesJson ?>;
    const WEEKDAYS = <?= $germanWeekdaysJs ?>;
    const MONTHS = <?= $germanMonthsJs ?>;
    const COLORS = {
        '#e5e7eb': 'Weiß', '#3b82f6': 'Blau', '#10b981': 'Grün',
        '#f59e0b': 'Gelb', '#ef4444': 'Rot', '#8b5cf6': 'Lila',
        '#f97316': 'Orange', '#ec4899': 'Pink', '#14b8a6': 'Türkis',
        '#6366f1': 'Indigo', '#6b7280': 'Grau', '#475569': 'Schiefer'
    };

    const IEM = {
        _expanded: null,
        _activePopover: null,
        _backdrop: null,

        // Only stops propagation when card is expanded; returns false otherwise so click bubbles to expand
        _guard(e) {
            const card = e.target.closest('.ie-card');
            if (card && card.classList.contains('ie-expanded')) {
                e.stopPropagation();
                return true;
            }
            return false;
        },

        onCardClick(card, e) {
            if (e.target.closest('.ie-footer-btn') ||
                e.target.closest('.ie-popover') || e.target.closest('.ie-edit-toggle') ||
                e.target.closest('.ie-section')) return;
            if (!card.classList.contains('ie-expanded')) {
                this._expand(card);
            }
        },

        toggleEdit(btn) {
            const card = this._card(btn);
            if (card.classList.contains('ie-expanded')) {
                this._collapse(card);
            } else {
                this._expand(card);
            }
        },

        _expand(card) {
            if (this._expanded && this._expanded !== card) this._collapse(this._expanded);
            card.classList.add('ie-expanded');
            this._expanded = card;
            const dtField = card.querySelector('[data-ie-field="datetime"]');
            if (dtField) this.editDatetime(dtField);
        },

        _collapse(card) {
            this._closePopover();
            // Rebuild datetime display from current values
            const dtField = card.querySelector('[data-ie-field="datetime"]');
            if (dtField && dtField.dataset.ieDtBound) {
                const dateEl = dtField.querySelector('[data-ie-date]');
                const timeEl = dtField.querySelector('[data-ie-time]');
                const s = card.dataset.start, e = card.dataset.end;
                if (dateEl && s) {
                    const sD = new Date(s.replace(' ', 'T')), eD = new Date(e.replace(' ', 'T'));
                    const sameDay = sD.toDateString() === eD.toDateString();
                    dateEl.textContent = sameDay ? this._formatDate(s) : this._formatDate(s) + ' – ' + this._formatDate(e);
                    dateEl.style.cssText = dateEl.getAttribute('style')?.replace(/text-decoration[^;]*;?/g, '') || '';
                }
                if (timeEl && s) {
                    timeEl.textContent = this._formatTime(s) + ' – ' + this._formatTime(e);
                }
                // Remove any dynamically added elements
                dtField.querySelectorAll('input, .ie-dt-toggle').forEach(x => x.remove());
                dtField.style.position = '';
                delete dtField.dataset.ieDtBound;
                delete dtField._dtOriginal;
            }
            card.classList.remove('ie-expanded');
            if (this._expanded === card) this._expanded = null;
        },

        _closePopover() {
            if (this._activePopover) { this._activePopover.remove(); this._activePopover = null; }
            if (this._backdrop) { this._backdrop.remove(); this._backdrop = null; }
        },

        _showPopover(pop, anchorEl) {
            pop.addEventListener('click', e => e.stopPropagation());
            this._activePopover = pop;

            const backdrop = document.createElement('div');
            backdrop.style.cssText = 'position: fixed; inset: 0; z-index: 49;';
            backdrop.addEventListener('click', () => this._closePopover());
            document.body.appendChild(backdrop);
            this._backdrop = backdrop;

            const rect = anchorEl.getBoundingClientRect();
            pop.style.cssText = `position: fixed; z-index: 50; top: ${rect.bottom + 4}px; left: ${rect.left}px;`;
            document.body.appendChild(pop);
        },

        _card(el) { return el.closest('[data-rehearsal-id]'); },

        _save(card, field, value) {
            return fetch(card.dataset.apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ field, value }),
            })
            .then(r => r.json());
        },

        _saveFields(card, fields) {
            return fetch(card.dataset.apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ fields }),
            })
            .then(r => r.json());
        },

        _formatDate(dateStr) {
            const d = new Date(dateStr.replace(' ', 'T'));
            if (isNaN(d)) return dateStr;
            return d.getDate() + '. ' + MONTHS[d.getMonth()] + ' ' + d.getFullYear();
        },

        _formatTime(dateStr) {
            const d = new Date(dateStr.replace(' ', 'T'));
            if (isNaN(d)) return '';
            return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
        },

        // ── LOCATION ──
        editLocation(el) {
            const card = this._card(el);
            if (!card.classList.contains('ie-expanded') || el.querySelector('.ie-inline-input')) return;
            const currentVal = card.dataset.location || '';
            const originalHTML = el.innerHTML;

            // Collect unique locations from existing cards
            const allLocations = [...new Set(
                [...document.querySelectorAll('.ie-card[data-location]')]
                    .map(c => c.dataset.location?.trim()).filter(Boolean)
            )].sort();

            el.classList.add('ie-editing');
            el.innerHTML = '';
            el.style.position = 'relative';

            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'ie-inline-input';
            input.value = currentVal;
            input.placeholder = 'Ort eingeben…';
            input.autocomplete = 'off';
            input.style.cssText = 'font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;';

            const dropdown = document.createElement('div');
            dropdown.style.cssText = 'position: absolute; top: 100%; left: 0; z-index: 50; min-width: 100%; width: fit-content; max-height: 160px; overflow-y: auto; background: var(--color-bg-primary); border: 1px solid var(--color-border); border-top: none; border-radius: 0 0 var(--radius-sm) var(--radius-sm); box-shadow: 0 4px 8px rgba(0,0,0,0.1); display: none;';

            let highlighted = -1;

            const render = (query) => {
                const q = query.toLowerCase();
                const filtered = q ? allLocations.filter(s => s.toLowerCase().includes(q)) : allLocations;
                dropdown.innerHTML = '';
                highlighted = -1;
                if (!filtered.length) { dropdown.style.display = 'none'; return filtered; }
                filtered.forEach((s, i) => {
                    const item = document.createElement('div');
                    item.textContent = s;
                    item.style.cssText = 'padding: 6px 10px; cursor: pointer; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; font-weight: 600; transition: background 0.1s;';
                    item.addEventListener('mouseenter', () => { highlighted = i; updateHL(); });
                    item.addEventListener('click', (e) => { e.stopPropagation(); input.value = s; dropdown.style.display = 'none'; input.blur(); });
                    dropdown.appendChild(item);
                });
                dropdown.style.display = 'block';
                return filtered;
            };

            const updateHL = () => {
                [...dropdown.children].forEach((c, i) => { c.style.background = i === highlighted ? 'var(--color-primary-50)' : ''; });
            };

            el.append(input, dropdown);
            input.focus();
            input.select();
            render(currentVal);

            input.addEventListener('input', () => render(input.value));
            input.addEventListener('focus', () => render(input.value));

            const commit = () => {
                dropdown.style.display = 'none';
                const newVal = input.value.trim();
                el.classList.remove('ie-editing');
                el.style.position = '';
                card.dataset.location = newVal;
                el.textContent = newVal || '📍 Ort…';
                if (!newVal || newVal.toLowerCase() === 'probenraum') {
                    el.style.opacity = '0.4';
                    el.style.borderStyle = 'dashed';
                } else {
                    el.style.opacity = '';
                    el.style.borderStyle = '';
                }
                if (newVal !== currentVal) this._save(card, 'location', newVal);
            };

            input.addEventListener('blur', () => setTimeout(commit, 150));
            input.addEventListener('keydown', e => {
                const items = dropdown.children;
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (highlighted < items.length - 1) { highlighted++; updateHL(); }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (highlighted > 0) { highlighted--; updateHL(); }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (highlighted >= 0 && items[highlighted]) input.value = items[highlighted].textContent;
                    input.blur();
                } else if (e.key === 'Escape') {
                    el.innerHTML = originalHTML;
                    el.classList.remove('ie-editing');
                    el.style.position = '';
                }
            });
        },

        // ── TYPE ──
        editType(el) {
            const card = this._card(el);
            if (!card.classList.contains('ie-expanded') || el.querySelector('.ie-inline-input')) return;
            const currentVal = card.dataset.type || '';
            const isCurrentDefault = !currentVal || currentVal.toLowerCase() === 'probe';
            const originalHTML = el.innerHTML;

            // Collect suggestions: defaults + unique types from existing cards (exclude "Probe" — it's the implicit default)
            const defaults = ['Konzertreise', 'Konzert', 'Generalprobe', 'Registerprobe', 'Probenwochenende', 'Dozentenregisterprobe'];
            const existing = [...document.querySelectorAll('.ie-card[data-type]')]
                .map(c => c.dataset.type?.trim()).filter(v => v && v.toLowerCase() !== 'probe');
            const suggestions = [...new Set([...defaults, ...existing])].sort();

            el.classList.add('ie-editing');
            el.innerHTML = '';
            el.style.position = 'relative';

            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'ie-inline-input';
            input.value = isCurrentDefault ? '' : currentVal;
            input.placeholder = 'Typ eingeben…';
            input.autocomplete = 'off';
            input.style.cssText = 'font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; min-width: 80px;';

            const dropdown = document.createElement('div');
            dropdown.style.cssText = 'position: absolute; top: 100%; left: 0; z-index: 50; min-width: 100%; width: fit-content; max-height: 160px; overflow-y: auto; background: var(--color-bg-primary); border: 1px solid var(--color-border); border-top: none; border-radius: 0 0 var(--radius-sm) var(--radius-sm); box-shadow: 0 4px 8px rgba(0,0,0,0.1); display: none;';

            let highlighted = -1;

            const render = (query) => {
                const q = query.toLowerCase();
                const filtered = q ? suggestions.filter(s => s.toLowerCase().includes(q)) : suggestions;
                dropdown.innerHTML = '';
                highlighted = -1;
                if (!filtered.length) {
                    dropdown.style.display = 'none';
                    return filtered;
                }
                filtered.forEach((s, i) => {
                    const item = document.createElement('div');
                    item.textContent = s;
                    item.style.cssText = 'padding: 6px 10px; cursor: pointer; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; font-weight: 600; transition: background 0.1s;';
                    item.addEventListener('mouseenter', () => { highlighted = i; updateHL(); });
                    item.addEventListener('click', (e) => {
                        e.stopPropagation();
                        input.value = s;
                        dropdown.style.display = 'none';
                        input.blur();
                    });
                    dropdown.appendChild(item);
                });
                dropdown.style.display = 'block';
                return filtered;
            };

            const updateHL = () => {
                [...dropdown.children].forEach((c, i) => {
                    c.style.background = i === highlighted ? 'var(--color-primary-50)' : '';
                });
            };

            el.append(input, dropdown);
            input.focus();
            input.select();
            render(currentVal);

            input.addEventListener('input', () => render(input.value));
            input.addEventListener('focus', () => render(input.value));

            const isDefault = (v) => !v || v.toLowerCase() === 'probe';

            const commit = () => {
                dropdown.style.display = 'none';
                const newVal = input.value.trim();
                el.classList.remove('ie-editing');
                el.style.position = '';
                card.dataset.type = newVal;
                el.textContent = isDefault(newVal) ? 'Typ…' : newVal;
                if (isDefault(newVal)) {
                    el.style.opacity = '0.4';
                    el.style.borderStyle = 'dashed';
                } else {
                    el.style.opacity = '';
                    el.style.borderStyle = '';
                }
                if (newVal !== currentVal) this._save(card, 'type', newVal);
            };

            input.addEventListener('blur', () => setTimeout(commit, 150));
            input.addEventListener('keydown', e => {
                const items = dropdown.children;
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (highlighted < items.length - 1) { highlighted++; updateHL(); items[highlighted]?.scrollIntoView({ block: 'nearest' }); }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (highlighted > 0) { highlighted--; updateHL(); items[highlighted]?.scrollIntoView({ block: 'nearest' }); }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (highlighted >= 0 && highlighted < items.length) {
                        input.value = items[highlighted].textContent;
                    } else if (items.length === 1) {
                        input.value = items[0].textContent;
                    }
                    input.blur();
                } else if (e.key === 'Escape') {
                    dropdown.style.display = 'none';
                    el.innerHTML = originalHTML;
                    el.classList.remove('ie-editing');
                    el.style.position = '';
                }
            });
        },

        // ── DATETIME ──
        editDatetime(el) {
            const card = this._card(el);
            if (!card.classList.contains('ie-expanded') || el.dataset.ieDtBound) return;
            el.dataset.ieDtBound = '1';

            const parse = (dt) => {
                if (!dt) return null;
                const d = new Date(dt.replace(' ', 'T'));
                return isNaN(d) ? null : d;
            };
            const fmtDate = (d) => d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
            const fmtTime = (d) => String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');

            const sD = parse(card.dataset.start);
            const eD = parse(card.dataset.end);
            if (!sD || !eD) return;

            const isMultiDay = fmtDate(sD) !== fmtDate(eD);
            let multiDay = isMultiDay;

            const dateEl = el.querySelector('[data-ie-date]');
            const timeEl = el.querySelector('[data-ie-time]');
            if (!dateEl || !timeEl) return;

            // Store original HTML for collapse restore
            el._dtOriginal = { date: dateEl.innerHTML, time: timeEl.innerHTML };

            const overlayStyle = 'position:absolute;inset:0;width:100%;height:100%;opacity:0.01;cursor:pointer;z-index:1;border:0;padding:0;margin:0;-webkit-appearance:none;background:transparent;color:transparent;font-size:16px;';
            const spanStyle = 'display:inline-block;position:relative;cursor:pointer;text-decoration:underline dashed var(--color-primary-200);text-underline-offset:2px;text-decoration-thickness:2px;';

            // Create an overlay input inside a span — tapping opens native picker (iOS)
            // + showPicker on click for desktop (entire area, not just calendar icon)
            const mkOverlay = (parent, type, val) => {
                const inp = document.createElement('input');
                inp.type = type;
                inp.value = val;
                inp.style.cssText = overlayStyle;
                inp.addEventListener('click', () => {
                    try { inp.showPicker(); } catch(_) {}
                });
                parent.style.cssText += spanStyle;
                parent.appendChild(inp);
                return inp;
            };

            // Date inputs
            const dateInp = document.createElement('input');
            dateInp.type = 'date';
            dateInp.value = fmtDate(sD);
            dateInp.style.cssText = overlayStyle;
            dateInp.addEventListener('click', () => { try { dateInp.showPicker(); } catch(_) {} });

            const endDateInp = document.createElement('input');
            endDateInp.type = 'date';
            endDateInp.value = fmtDate(eD);
            endDateInp.style.cssText = overlayStyle;
            endDateInp.addEventListener('click', () => { try { endDateInp.showPicker(); } catch(_) {} });

            // Time: split into two spans with overlay inputs
            timeEl.innerHTML = '';
            const mkTimeSpan = (label, val) => {
                const span = document.createElement('span');
                span.style.cssText = spanStyle;
                span.textContent = label;
                const inp = document.createElement('input');
                inp.type = 'time';
                inp.value = val;
                inp.style.cssText = overlayStyle;
                inp.addEventListener('click', () => { try { inp.showPicker(); } catch(_) {} });
                span.appendChild(inp);
                return { span, inp };
            };

            const startT = mkTimeSpan(this._formatTime(card.dataset.start), fmtTime(sD));
            const dashSpan = document.createElement('span');
            dashSpan.textContent = ' – ';
            const endT = mkTimeSpan(this._formatTime(card.dataset.end), fmtTime(eD));

            timeEl.append(startT.span, dashSpan, endT.span);

            // End-date display
            const endDateDash = document.createElement('span');
            endDateDash.textContent = ' – ';
            const endDateSpan = document.createElement('span');
            endDateSpan.style.cssText = spanStyle;

            // Subtle icon toggle next to the date
            const toggleIcon = document.createElement('span');
            toggleIcon.style.cssText = 'cursor: pointer; font-size: 14px; color: var(--color-text-muted); opacity: 0.35; transition: opacity 0.15s; margin-left: 6px;';
            toggleIcon.innerHTML = multiDay
                ? '<i class="fas fa-calendar-minus" title="Gleicher Tag"></i>'
                : '<i class="fas fa-calendar-plus" title="Mehrtägig"></i>';
            toggleIcon.addEventListener('mouseenter', () => toggleIcon.style.opacity = '0.7');
            toggleIcon.addEventListener('mouseleave', () => toggleIcon.style.opacity = '0.35');

            const updateDateDisplay = () => {
                dateEl.innerHTML = '';
                const sdSpan = document.createElement('span');
                sdSpan.style.cssText = spanStyle;
                sdSpan.textContent = this._formatDate(card.dataset.start);
                sdSpan.appendChild(dateInp);
                dateEl.appendChild(sdSpan);
                if (multiDay) {
                    dateEl.appendChild(endDateDash);
                    endDateSpan.textContent = this._formatDate(endDateInp.value + ' 00:00:00');
                    endDateSpan.appendChild(endDateInp);
                    dateEl.appendChild(endDateSpan);
                    toggleIcon.innerHTML = '<i class="fas fa-calendar-minus" title="Gleicher Tag"></i>';
                } else {
                    toggleIcon.innerHTML = '<i class="fas fa-calendar-plus" title="Mehrtägig"></i>';
                }
                dateEl.appendChild(toggleIcon);
            };

            toggleIcon.addEventListener('click', (e) => {
                e.stopPropagation();
                multiDay = !multiDay;
                if (multiDay) {
                    endDateInp.value = endDateInp.value || dateInp.value;
                } else {
                    endDateInp.value = dateInp.value;
                }
                updateDateDisplay();
                save();
            });

            updateDateDisplay();

            const save = () => {
                const d = dateInp.value;
                const st = startT.inp.value;
                const ed = multiDay ? endDateInp.value : d;
                const et = endT.inp.value;
                if (!d || !st || !et) return;

                const newStart = d + ' ' + st + ':00';
                const newEnd = ed + ' ' + et + ':00';
                card.dataset.start = newStart;
                card.dataset.end = newEnd;

                updateDateDisplay();
                startT.span.firstChild.textContent = this._formatTime(newStart);
                endT.span.firstChild.textContent = this._formatTime(newEnd);

                const sDate = new Date(newStart.replace(' ', 'T'));
                const weekdayEl = card.querySelector('[data-ie-weekday]');
                if (weekdayEl && !isNaN(sDate)) weekdayEl.textContent = WEEKDAYS[sDate.getDay()].toUpperCase();

                this._saveFields(card, { start: newStart, end: newEnd });
            };

            dateInp.addEventListener('change', save);
            endDateInp.addEventListener('change', () => { updateDateDisplay(); save(); });
            startT.inp.addEventListener('change', save);
            endT.inp.addEventListener('change', save);
        },

        // ── ROLES: remove a role tag ──
        removeRole(btn, roleId) {
            const card = this._card(btn);
            const tag = btn.closest('.ie-role-tag');
            if (tag) {
                tag.style.transition = 'opacity 0.15s ease, transform 0.15s ease';
                tag.style.opacity = '0';
                tag.style.transform = 'scale(0.8)';
                setTimeout(() => tag.remove(), 150);
            }
            const ids = this._getCurrentRoleIds(card).filter(id => id !== roleId);
            this._save(card, 'role_ids', JSON.stringify(ids));
        },

        // ── ROLES: "+" opens popover showing only addable roles ──
        addRolePopover(addBtn) {
            const card = this._card(addBtn);
            if (!card.classList.contains('ie-expanded')) return;
            this._closePopover();

            const currentIds = this._getCurrentRoleIds(card);
            const available = ROLES.filter(r => !r.is_default && r.is_self_assignable && !currentIds.includes(r.id));

            if (available.length === 0) {
                const pop = document.createElement('div');
                pop.className = 'ie-popover';
                pop.innerHTML = '<div style="font-size: var(--font-size-xs); color: var(--color-text-muted); padding: 8px;">Alle Rollen zugewiesen</div>';
                this._showPopover(pop, addBtn);
                return;
            }

            const pop = document.createElement('div');
            pop.className = 'ie-popover';
            pop.style.cssText = 'min-width: 160px; display: flex; flex-direction: column; gap: 2px;';

            available.forEach(role => {
                const item = document.createElement('div');
                item.style.cssText = 'display: flex; align-items: center; cursor: pointer; padding: 8px 10px; border-radius: var(--radius-sm); transition: background 0.1s;';
                item.innerHTML = `<span class="role-tag" style="--role-color: ${role.color}; pointer-events: none;">${role.name}</span>`;
                item.addEventListener('mouseenter', () => { item.style.background = 'var(--color-bg-tertiary)'; });
                item.addEventListener('mouseleave', () => { item.style.background = ''; });
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this._addRoleTag(card, role);
                    item.remove();
                    if (!pop.querySelector('div')) this._closePopover();
                });
                pop.appendChild(item);
            });

            this._showPopover(pop, addBtn);
        },

        _getCurrentRoleIds(card) {
            return [...card.querySelectorAll('.ie-role-tag[data-role-id]')].map(t => parseInt(t.dataset.roleId));
        },

        _addRoleTag(card, role) {
            const addBtn = card.querySelector('.ie-role-add');
            const tag = document.createElement('span');
            tag.className = 'role-tag ie-role-tag';
            tag.setAttribute('data-role-id', role.id);
            tag.style.cssText = `--role-color: ${role.color}; cursor: default;`;
            tag.textContent = role.name;
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'ie-role-remove';
            removeBtn.title = 'Entfernen';
            removeBtn.textContent = '×';
            removeBtn.addEventListener('click', (e) => { e.stopPropagation(); this.removeRole(removeBtn, role.id); });
            tag.appendChild(removeBtn);
            if (addBtn) addBtn.parentElement.insertBefore(tag, addBtn);
            else card.querySelector('.rehearsal-badges')?.appendChild(tag);

            const ids = this._getCurrentRoleIds(card);
            this._save(card, 'role_ids', JSON.stringify(ids));
        },

        // ── COLOR: popover ──
        editColor(el) {
            const card = this._card(el);
            if (!card.classList.contains('ie-expanded')) return;
            this._closePopover();

            const pop = document.createElement('div');
            pop.className = 'ie-popover';
            pop.style.cssText += 'display: flex; flex-wrap: wrap; gap: 8px; width: 200px; justify-content: center;';

            Object.entries(COLORS).forEach(([hex, name]) => {
                const swatch = document.createElement('button');
                swatch.type = 'button';
                swatch.title = name;
                swatch.style.cssText = `width: 34px; height: 34px; border-radius: 50%; border: 2px solid var(--color-border); background: ${hex}; cursor: pointer; transition: transform 0.1s ease, border-color 0.15s ease;`;
                if (hex === card.dataset.color) {
                    swatch.style.borderColor = 'var(--color-primary)';
                    swatch.style.borderWidth = '3px';
                }
                swatch.addEventListener('click', (e) => {
                    e.stopPropagation();
                    card.dataset.color = hex;
                    card.style.borderLeftColor = hex;
                    const dot = card.querySelector('[data-ie-color-dot]');
                    if (dot) dot.style.background = hex;
                    this._closePopover();
                    this._save(card, 'color', hex);
                });
                pop.appendChild(swatch);
            });

            this._showPopover(pop, el);
        },

        // ── GROUPS: full-view modal ──
        _groupsCard: null,

        editGroups(el) {
            const card = this._card(el);
            if (!card.classList.contains('ie-expanded')) return;
            this._closePopover();

            const modal = document.getElementById('ieGroupsModal');
            const body = document.getElementById('ieGroupsBody');
            if (!modal || !body) return;

            this._groupsCard = card;
            const currentGroups = JSON.parse(card.dataset.groups || '[]');

            // Sync checkboxes to card's current groups
            body.querySelectorAll('input[name="groups[]"]').forEach(cb => {
                cb.checked = currentGroups.includes(cb.value);
                cb.indeterminate = false;
            });

            // Recalculate parent states (indeterminate/checked) without cascading
            if (typeof recalculateHierarchyStates === 'function') {
                recalculateHierarchyStates(body);
            }

            modal.showModal();
        },

        closeGroupsModal() {
            const modal = document.getElementById('ieGroupsModal');
            if (!modal) return;
            modal.close();

            const card = this._groupsCard;
            if (!card) return;

            const body = document.getElementById('ieGroupsBody');
            const checked = [...body.querySelectorAll('input[name="groups[]"]:checked')].map(cb => cb.value);
            card.dataset.groups = JSON.stringify(checked);

            const badge = card.querySelector('[data-ie-groups]');
            if (badge) {
                badge.style.opacity = '0.5';
                badge.textContent = '…';
            }

            this._save(card, 'groups', JSON.stringify(checked))
                .then(data => {
                    if (badge) {
                        badge.style.opacity = '';
                        badge.textContent = data?.groups_display || (checked.length > 0
                            ? [...body.querySelectorAll('input[name="groups[]"]:checked')]
                                .map(cb => cb.closest('label')?.textContent?.trim() || cb.value).join(', ')
                            : 'Alle');
                    }
                });
            this._groupsCard = null;
        },

        // ── DELETE ──
        deleteRehearsal(rehearsalId) {
            Swal.fire({
                title: 'Willst du diesen Termin wirklich löschen?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#478cf4',
                cancelButtonText: 'Abbrechen',
                confirmButtonText: 'Löschen'
            }).then((result) => {
                if (!result.isConfirmed) return;
                fetch(`${BASE}/rehearsals/delete/${rehearsalId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                    body: 'id=' + rehearsalId
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const card = document.querySelector(`[data-rehearsal-id="${rehearsalId}"]`);
                    if (!card) return;
                    card.style.transition = 'opacity 0.3s ease, max-height 0.3s ease, margin 0.3s ease, padding 0.3s ease';
                    card.style.opacity = '0';
                    card.style.maxHeight = '0';
                    card.style.margin = '0';
                    card.style.padding = '0';
                    card.style.overflow = 'hidden';
                    setTimeout(() => card.remove(), 350);
                });
            });
        },

        // ── CREATE ──
        createRehearsal(addBox) {
            const url = addBox.dataset.createUrl;
            addBox.classList.add('loading');
            addBox.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Erstelle…</span>';

            fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                addBox.classList.remove('loading');
                addBox.innerHTML = '<i class="fas fa-plus"></i> <span>Neue Probe</span>';
                if (!data.success || !data.html) return;
                const list = document.getElementById('rehearsalsList') || addBox.parentElement;
                const tmp = document.createElement('div');
                tmp.innerHTML = data.html;
                const newCard = tmp.firstElementChild;
                if (!newCard) return;
                list.insertBefore(newCard, addBox);
                requestAnimationFrame(() => {
                    this._expand(newCard);
                    newCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            })
            .catch(() => {
                addBox.classList.remove('loading');
                addBox.innerHTML = '<i class="fas fa-plus"></i> <span>Neue Probe</span>';
            });
        },
    };

    window.IEM = IEM;
})();
</script>
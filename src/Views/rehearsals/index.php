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

    <?php if (!empty($rehearsals) || ($hasPastRehearsals ?? false)): ?>
        <?php include __DIR__ . '/../components/date-separator.php'; ?>
    <?php endif; ?>

    <?php include __DIR__ . '/../components/bulk-select-bar.php'; ?>

    <?php if (empty($rehearsals)): ?>
        <?php
        if ($hasPastRehearsals ?? false) {
            $title = 'Keine aktuellen Termine';
            $message = 'Es stehen keine kommenden Proben an.';
        } else {
            $title = 'Noch keine Termine';
            $message = 'Klicke unten auf „Neue Probe", um loszulegen.';
        }

        include __DIR__ . '/../components/empty-state.php';
        ?>
        <div id="rehearsalsList">
            <?php include __DIR__ . '/../components/recurring-dialog.php'; ?>
        </div>
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



        <div id="rehearsalsList">
            <?php include __DIR__ . '/../components/recurring-dialog.php'; ?>
            <?php foreach ($currentRehearsals as $rehearsal): ?>
                <?php
                $context = 'inline-edit';
                $options = ['showButtons' => false];
                include __DIR__ . '/../components/rehearsal-card.php';
                ?>
            <?php endforeach; ?>

            <?php if ($hasMoreRehearsals ?? false): ?>
                <?php
                $lazyBase = '/' . ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '');
                $lazyUrl = $lazyBase . '/rehearsals/lazy?offset=' . count($rehearsals);
                $lazyId = 'rehearsals-list';
                $lazyType = 'cards';
                $lazyCount = min(3, ($totalRehearsals ?? 0) - count($rehearsals));
                include __DIR__ . '/../components/lazy-section.php';
                ?>
            <?php endif; ?>
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

<?php if (!empty($allowRehearsalImport)): ?>
    <?php include __DIR__ . '/../components/ai-rehearsal-import-modal.php'; ?>
<?php endif; ?>

<script>
(function() {
    const modal = document.getElementById('ieGroupsModal');
    if (!modal) return;
    // Backdrop click → validate before close
    modal.addEventListener('click', function(e) {
        if (e.target === this) window.IEM?.closeGroupsModal();
    });
    // Escape key → validate before close
    modal.addEventListener('cancel', function(e) {
        e.preventDefault();
        window.IEM?.closeGroupsModal();
    });
})();
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
        _saveControllers: new Map(),

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
            document.querySelectorAll('.ie-time-popover').forEach(p => p.remove());
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
            pop.style.cssText += `; position: fixed; z-index: 50; top: ${rect.bottom + 4}px; left: ${rect.left}px;`;
            document.body.appendChild(pop);
        },

        _card(el) { return el.closest('[data-rehearsal-id]'); },

        _fetchWithAbort(url, body, abortKey) {
            this._saveControllers.get(abortKey)?.abort();
            const ctrl = new AbortController();
            this._saveControllers.set(abortKey, ctrl);
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
                signal: ctrl.signal,
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    window.notifyError?.(data.error || 'Änderung konnte nicht gespeichert werden');
                }
                return data;
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                window.notifyError?.('Netzwerkfehler – Änderung nicht gespeichert');
            })
            .finally(() => this._saveControllers.delete(abortKey));
        },

        _save(card, field, value) {
            if (!card.dataset.apiUrl) return Promise.resolve();
            return this._fetchWithAbort(
                card.dataset.apiUrl,
                { field, value },
                `${card.dataset.rehearsalId}:${field}`
            );
        },

        _saveFields(card, fields) {
            if (!card.dataset.apiUrl) return Promise.resolve();
            const key = `${card.dataset.rehearsalId}:${Object.keys(fields).join('+')}}`;
            return this._fetchWithAbort(card.dataset.apiUrl, { fields }, key);
        },

        _sortRehearsals(container) {
            if (!container) return;
            const cards = Array.from(container.querySelectorAll('.ie-card[data-rehearsal-id]:not([data-rehearsal-id="recurring"])'));
            if (cards.length < 2) return;
            cards.sort((a, b) => {
                const sA = a.dataset.start || '';
                const sB = b.dataset.start || '';
                return sA.localeCompare(sB);
            });
            const lazySection = container.querySelector('[data-lazy-section]');
            cards.forEach(c => {
                if (lazySection) {
                    container.insertBefore(c, lazySection);
                } else {
                    container.appendChild(c);
                }
            });
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
            const fmtDate = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            const fmtTime = (d) => String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');

            const sD = parse(card.dataset.start);
            const eD = parse(card.dataset.end);
            if (!sD || !eD) return;

            const isMultiDay = fmtDate(sD) !== fmtDate(eD);
            let multiDay = isMultiDay;

            const dateEl = el.querySelector('[data-ie-date]');
            const timeEl = el.querySelector('[data-ie-time]');
            if (!dateEl || !timeEl) return;

            el._dtOriginal = { date: dateEl.innerHTML, time: timeEl.innerHTML };

            // Detect Firefox for custom time input fallback
            const isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;

            // opacity:0 keeps layout + pointer events but hides native widget chrome (Firefox-safe)
            const overlayStyle = 'position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;z-index:1;border:0;padding:0;margin:0;-webkit-appearance:none;-moz-appearance:none;appearance:none;background:transparent;color:transparent;font-size:16px;';
            const spanStyle = 'display:inline-block;position:relative;cursor:pointer;text-decoration:underline dashed var(--color-primary-200);text-underline-offset:2px;text-decoration-thickness:2px;';

            // Firefox-compatible: showPicker() is not supported everywhere
            const tryShowPicker = (inp) => {
                if (typeof inp.showPicker === 'function') {
                    try { inp.showPicker(); } catch (_) { }
                }
            };

            // Date inputs
            const dateInp = document.createElement('input');
            dateInp.type = 'date';
            dateInp.value = fmtDate(sD);
            dateInp.style.cssText = overlayStyle;
            dateInp.addEventListener('click', () => tryShowPicker(dateInp));

            const endDateInp = document.createElement('input');
            endDateInp.type = 'date';
            endDateInp.value = fmtDate(eD);
            endDateInp.style.cssText = overlayStyle;
            endDateInp.addEventListener('click', () => tryShowPicker(endDateInp));

            // Time: two spans — overlay input (non-Firefox) or click-to-popover (Firefox)
            timeEl.innerHTML = '';
            const mkTimeSpan = (label, val, onCommit) => {
                const span = document.createElement('span');
                span.style.cssText = spanStyle;
                span.textContent = label;

                // Hidden input stores the canonical HH:MM value in both branches
                const inp = document.createElement('input');
                inp.type = 'time';
                inp.value = val;

                if (isFirefox) {
                    inp.style.cssText = 'position:absolute;width:0;height:0;opacity:0;pointer-events:none;';
                    span.appendChild(inp);
                    span.addEventListener('click', (e) => {
                        e.stopPropagation();
                        this._showTimePicker(span, inp, onCommit);
                    });
                } else {
                    inp.style.cssText = overlayStyle;
                    inp.addEventListener('click', () => tryShowPicker(inp));
                    span.appendChild(inp);
                }

                return { span, inp };
            };

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
                
                const container = card.closest('#rehearsalsList') || card.parentElement;
                this._sortRehearsals(container);
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            };

            const startT = mkTimeSpan(this._formatTime(card.dataset.start), fmtTime(sD), save);
            const dashSpan = document.createElement('span');
            dashSpan.textContent = ' – ';
            const endT = mkTimeSpan(this._formatTime(card.dataset.end), fmtTime(eD), save);

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

            // Cascade parent selections to children (e.g. 'tutti' → all descendants)
            body.querySelectorAll('input[name="groups[]"]:checked').forEach(cb => {
                const next = cb.closest('.checkbox-item')?.nextElementSibling;
                if (next?.classList.contains('checkbox-group')) {
                    next.querySelectorAll('input[name="groups[]"]').forEach(child => {
                        child.checked = true;
                        child.indeterminate = false;
                    });
                }
            });

            if (typeof recalculateHierarchyStates === 'function') {
                recalculateHierarchyStates(body);
            }

            modal.showModal();
        },

        closeGroupsModal() {
            const modal = document.getElementById('ieGroupsModal');
            if (!modal) return;

            const body = document.getElementById('ieGroupsBody');
            const checked = [...body.querySelectorAll('input[name="groups[]"]:checked')].map(cb => cb.value);

            if (checked.length === 0) {
                const panel = modal.querySelector('.ie-groups-panel');
                panel.style.animation = 'none';
                panel.offsetHeight;
                panel.style.animation = 'ie-shake 0.4s ease';
                panel.addEventListener('animationend', () => { panel.style.animation = ''; }, { once: true });
                return;
            }

            modal.close();

            const card = this._groupsCard;
            if (!card) return;

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
                        badge.textContent = data?.groups_display ||
                            [...body.querySelectorAll('input[name="groups[]"]:checked')]
                                .map(cb => cb.closest('.checkbox-item')?.querySelector('label')?.textContent?.trim() || cb.value)
                                .join(', ');
                    }
                });
            this._groupsCard = null;
        },

        // ── TAGS ──
        _tagsCache: null,

        removeTag(btn) {
            const card = this._card(btn);
            if (!card.classList.contains('ie-expanded')) return;
            btn.closest('.ie-tag').remove();
            this._saveTagsFromCard(card);
        },

        addTagInput(addBtn) {
            const card = this._card(addBtn);
            if (!card.classList.contains('ie-expanded')) return;

            addBtn.style.display = 'none';
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'ie-tag-input';
            input.placeholder = 'Tag…';
            addBtn.parentElement.insertBefore(input, addBtn);
            input.focus();

            const commitTag = () => {
                const name = input.value.trim();
                if (!name) return;

                // Avoid duplicates
                const existing = [...card.querySelectorAll('.ie-tag')].map(t => t.dataset.tag);
                if (existing.includes(name)) { input.value = ''; return; }

                const tag = document.createElement('span');
                tag.className = 'ie-tag';
                tag.dataset.tag = name;
                tag.textContent = name;
                const rm = document.createElement('button');
                rm.type = 'button';
                rm.className = 'ie-tag-remove';
                rm.title = 'Entfernen';
                rm.textContent = '×';
                rm.style.display = 'inline';
                rm.addEventListener('click', (e) => { e.stopPropagation(); this.removeTag(rm); });
                tag.appendChild(rm);
                input.parentElement.insertBefore(tag, input);
                input.value = '';
                this._saveTagsFromCard(card);
            };

            const closeInput = () => {
                this._closePopover();
                input.remove();
                addBtn.style.display = '';
            };

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    commitTag();
                } else if (e.key === 'Escape') {
                    closeInput();
                }
            });

            input.addEventListener('blur', () => {
                setTimeout(() => {
                    if (document.activeElement !== input) {
                        commitTag();
                        closeInput();
                    }
                }, 150);
            });

            // Autocomplete
            const showSuggestions = async () => {
                if (!this._tagsCache) {
                    try {
                        const res = await fetch(`${BASE}/rehearsals/tags`);
                        this._tagsCache = await res.json();
                    } catch { this._tagsCache = []; }
                }

                // Capture locally so renderFiltered is safe even if _tagsCache is invalidated mid-session
                const tags = this._tagsCache;
                const existing = [...card.querySelectorAll('.ie-tag')].map(t => t.dataset.tag);
                const available = tags.filter(t => !existing.includes(t));
                if (!available.length) return;

                this._closePopover();
                const pop = document.createElement('div');
                pop.className = 'ie-popover';
                pop.style.cssText = 'min-width: 140px; display: flex; flex-direction: column; gap: 2px;';

                const renderFiltered = (query) => {
                    pop.innerHTML = '';
                    const current = [...card.querySelectorAll('.ie-tag')].map(t => t.dataset.tag);
                    const filtered = tags.filter(t =>
                        !current.includes(t) && t.toLowerCase().includes(query.toLowerCase())
                    );
                    if (!filtered.length) { this._closePopover(); return; }
                    filtered.forEach(name => {
                        const item = document.createElement('div');
                        item.style.cssText = 'cursor: pointer; padding: 6px 10px; border-radius: var(--radius-sm); font-size: 12px; transition: background 0.1s;';
                        item.textContent = name;
                        item.addEventListener('mouseenter', () => item.style.background = 'var(--color-bg-secondary)');
                        item.addEventListener('mouseleave', () => item.style.background = '');
                        item.addEventListener('mousedown', (e) => {
                            e.preventDefault();
                            input.value = name;
                            commitTag();
                            renderFiltered(input.value);
                        });
                        pop.appendChild(item);
                    });
                };

                renderFiltered('');
                this._showPopover(pop, input);

                input.addEventListener('input', () => renderFiltered(input.value));
            };

            showSuggestions();
        },

        _saveTagsFromCard(card) {
            const tags = [...card.querySelectorAll('.ie-tag')].map(t => t.dataset.tag);
            this._save(card, 'tags', JSON.stringify(tags));
            this._tagsCache = null;
        },

        // Re-execute <script> tags inside dynamically inserted HTML
        _activateScripts(el) {
            el.querySelectorAll('script').forEach(old => {
                const s = document.createElement('script');
                s.textContent = old.textContent;
                old.replaceWith(s);
            });
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
                    if (!data.success) {
                        window.notifyError?.(data.error || 'Termin konnte nicht gelöscht werden');
                        return;
                    }
                    const card = document.querySelector(`[data-rehearsal-id="${rehearsalId}"]`);
                    if (!card) return;
                    card.style.transition = 'opacity 0.3s ease, max-height 0.3s ease, margin 0.3s ease, padding 0.3s ease';
                    card.style.opacity = '0';
                    card.style.maxHeight = '0';
                    card.style.margin = '0';
                    card.style.padding = '0';
                    card.style.overflow = 'hidden';
                    setTimeout(() => card.remove(), 350);
                })
                .catch(() => window.notifyError?.('Netzwerkfehler – Termin nicht gelöscht'));
            });
        },

        // ── DUPLICATE ──
        duplicateRehearsal(rehearsalId, btn) {
            const card = document.querySelector(`[data-rehearsal-id="${rehearsalId}"]`);
            if (!card) return;

            const startDate = card.dataset.start?.split(' ')[0];
            const defaultDate = startDate ? (() => {
                const d = new Date(startDate);
                d.setDate(d.getDate() + 7);
                return d.toISOString().split('T')[0];
            })() : new Date().toISOString().split('T')[0];

            const rect = btn.getBoundingClientRect();
            const pop = document.createElement('div');
            pop.className = 'bulk-filter-dropdown';
            pop.innerHTML = `<div class="bulk-popover-title">Duplizieren</div>
                <label>Neues Datum</label>
                <input type="date" class="bulk-popover-input" id="dupDate" value="${defaultDate}">
                <button class="bulk-popover-apply" id="dupConfirm">Duplizieren</button>`;
            if (rect.bottom + 200 > window.innerHeight) {
                pop.style.cssText = `position:fixed;bottom:${window.innerHeight - rect.top + 4}px;left:${Math.max(8, rect.left - 80)}px;`;
            } else {
                pop.style.cssText = `position:fixed;top:${rect.bottom + 4}px;left:${Math.max(8, rect.left - 80)}px;`;
            }
            pop.onclick = e => e.stopPropagation();

            const backdrop = document.createElement('div');
            backdrop.className = 'bulk-backdrop';
            backdrop.onclick = () => { backdrop.remove(); pop.remove(); };
            document.body.appendChild(backdrop);
            document.body.appendChild(pop);

            pop.querySelector('#dupConfirm').addEventListener('click', () => {
                const newDate = pop.querySelector('#dupDate').value;
                if (!newDate || !startDate) return;

                const origMs = new Date(startDate).getTime();
                const newMs = new Date(newDate).getTime();
                const offsetDays = Math.round((newMs - origMs) / 86400000);

                backdrop.remove();
                pop.remove();

                const fd = new FormData();
                fd.append('offset_days', offsetDays);

                fetch(`${BASE}/rehearsals/duplicate/${rehearsalId}`, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (!data.success || !data.html) {
                        window.notifyError?.(data.error || 'Termin konnte nicht dupliziert werden');
                        return;
                    }
                    const tmp = document.createElement('div');
                    tmp.innerHTML = data.html;
                    const newCard = tmp.querySelector('.ie-card');
                    if (!newCard) return;
                    
                    // Insert all children from tmp after the current card
                    while (tmp.firstChild) {
                        card.parentNode.insertBefore(tmp.firstChild, card.nextSibling);
                    }
                    this._activateScripts(newCard);
                    requestAnimationFrame(() => {
                        this._expand(newCard);
                        const container = newCard.closest('#rehearsalsList') || newCard.parentElement;
                        this._sortRehearsals(container);
                        newCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    });
                })
                .catch(() => window.notifyError?.('Netzwerkfehler – Termin nicht dupliziert'));
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
                if (!data.success || !data.html) {
                    window.notifyError?.(data.error || 'Termin konnte nicht erstellt werden');
                    return;
                }
                const list = document.getElementById('rehearsalsList') || addBox.parentElement;
                const tmp = document.createElement('div');
                tmp.innerHTML = data.html;
                const newCard = tmp.querySelector('.ie-card');
                if (!newCard) return;
                
                while (tmp.firstChild) {
                    list.appendChild(tmp.firstChild);
                }
                
                this._activateScripts(newCard);
                document.querySelector('.empty-state')?.closest('.flex')?.remove();
                requestAnimationFrame(() => {
                    this._expand(newCard);
                    const container = newCard.closest('#rehearsalsList') || newCard.parentElement;
                    this._sortRehearsals(container);
                    newCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            })
            .catch(() => {
                addBox.classList.remove('loading');
                addBox.innerHTML = '<i class="fas fa-plus"></i> <span>Neue Probe</span>';
                window.notifyError?.('Netzwerkfehler – Termin nicht erstellt');
            });
        },

        // ── FIREFOX TIME PICKER ──
        _showTimePicker(span, inp, onCommit) {
            document.querySelectorAll('.ie-time-popover').forEach(p => p.remove());

            const [curH, curM] = inp.value ? inp.value.split(':').map(Number) : [0, 0];
            let selH = curH, selM = curM;

            const pop = document.createElement('div');
            pop.className = 'ie-time-popover';

            const hourCol = document.createElement('div');
            hourCol.className = 'ie-time-popover-col';
            const minCol = document.createElement('div');
            minCol.className = 'ie-time-popover-col';

            const commit = () => {
                const hh = String(selH).padStart(2, '0');
                const mm = String(selM).padStart(2, '0');
                inp.value = `${hh}:${mm}`;
                span.firstChild.textContent = `${hh}:${mm}`;
                pop.remove();
                document.removeEventListener('click', onOutside, true);
                onCommit();
            };

            for (let h = 0; h < 24; h++) {
                const item = document.createElement('div');
                item.className = 'ie-time-popover-item' + (h === curH ? ' ie-time-selected' : '');
                item.textContent = String(h).padStart(2, '0');
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    selH = h;
                    hourCol.querySelectorAll('.ie-time-popover-item').forEach(i => i.classList.remove('ie-time-selected'));
                    item.classList.add('ie-time-selected');
                });
                hourCol.appendChild(item);
            }

            for (let m = 0; m < 60; m += 5) {
                const item = document.createElement('div');
                item.className = 'ie-time-popover-item' + (m === curM ? ' ie-time-selected' : '');
                item.textContent = String(m).padStart(2, '0');
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    selM = m;
                    minCol.querySelectorAll('.ie-time-popover-item').forEach(i => i.classList.remove('ie-time-selected'));
                    item.classList.add('ie-time-selected');
                    commit();
                });
                minCol.appendChild(item);
            }

            pop.append(hourCol, minCol);

            const rect = span.getBoundingClientRect();
            pop.style.top = (rect.bottom + 4) + 'px';
            pop.style.left = rect.left + 'px';
            document.body.appendChild(pop);
            requestAnimationFrame(() => {
                hourCol.children[curH]?.scrollIntoView({ block: 'center' });
                minCol.children[Math.round(curM / 5)]?.scrollIntoView({ block: 'center' });
            });

            const onOutside = (e) => {
                if (!pop.contains(e.target) && e.target !== span) {
                    pop.remove();
                    document.removeEventListener('click', onOutside, true);
                }
            };
            document.addEventListener('click', onOutside, true);
        },
    };

    window.IEM = IEM;
})();
</script>

<script>
(function() {
    'use strict';

    // Move action bar to body so parent transforms don't break position:fixed
    const actionBar = document.getElementById('bulkActionBar');
    if (actionBar) document.body.appendChild(actionBar);

    const BASE = '/' + document.querySelector('[data-api-url]')?.dataset.apiUrl?.split('/').slice(1, 3).join('/') || '';
    const COLORS = {
        '#e5e7eb': 'Weiß', '#3b82f6': 'Blau', '#10b981': 'Grün',
        '#f59e0b': 'Gelb', '#ef4444': 'Rot', '#8b5cf6': 'Lila',
        '#f97316': 'Orange', '#ec4899': 'Pink', '#14b8a6': 'Türkis',
        '#6366f1': 'Indigo', '#6b7280': 'Grau', '#475569': 'Schiefer'
    };
    const TYPE_PRESETS = ['Konzertreise', 'Konzert', 'Generalprobe', 'Registerprobe', 'Probenwochenende', 'Dozentenregisterprobe'];

    const BulkMgr = {
        active: false,
        selected: new Set(),
        _popover: null,
        _backdrop: null,
        _activeFilters: {},
        _lastClickedCard: null,

        // ── Mode toggle ──
        toggle() {
            this.active = !this.active;
            const btn = document.getElementById('bulkSelectToggle');
            btn?.classList.toggle('active', this.active);

            document.querySelectorAll('.ie-card:not([data-rehearsal-id="recurring"])').forEach(c => {
                if (this.active) {
                    c.classList.add('bulk-selectable');
                } else {
                    c.classList.remove('bulk-selectable', 'bulk-selected');
                }
            });

            if (!this.active) {
                this.selected.clear();
                this._lastClickedCard = null;
                this._updateBar();
            }
        },

        // ── Card selection ──
        onCardClick(card, e) {
            if (!this.active) return false;
            e.preventDefault();
            e.stopPropagation();

            const id = card.dataset.rehearsalId;
            const isSelected = this.selected.has(id);
            const willSelect = !isSelected;

            if (e.shiftKey && this._lastClickedCard) {
                const allVisibleCards = [...document.querySelectorAll('.ie-card:not([data-rehearsal-id="recurring"])')].filter(c => c.style.display !== 'none');
                const startIdx = allVisibleCards.indexOf(this._lastClickedCard);
                const endIdx = allVisibleCards.indexOf(card);

                if (startIdx !== -1 && endIdx !== -1) {
                    const minIdx = Math.min(startIdx, endIdx);
                    const maxIdx = Math.max(startIdx, endIdx);

                    for (let i = minIdx; i <= maxIdx; i++) {
                        const targetCard = allVisibleCards[i];
                        const targetId = targetCard.dataset.rehearsalId;
                        
                        if (willSelect) {
                            this.selected.add(targetId);
                            targetCard.classList.add('bulk-selected');
                        } else {
                            this.selected.delete(targetId);
                            targetCard.classList.remove('bulk-selected');
                        }
                    }
                }
            } else {
                if (isSelected) {
                    this.selected.delete(id);
                    card.classList.remove('bulk-selected');
                } else {
                    this.selected.add(id);
                    card.classList.add('bulk-selected');
                }
            }

            this._lastClickedCard = card;
            this._updateBar();
            return true;
        },

        _updateBar() {
            const bar = document.getElementById('bulkActionBar');
            const count = document.getElementById('bulkCount');
            if (this.selected.size > 0) {
                bar?.classList.add('visible');
                count.textContent = this.selected.size + ' ausgewählt';
            } else {
                bar?.classList.remove('visible');
            }
        },

        deselectAll() {
            this.selected.clear();
            document.querySelectorAll('.ie-card.bulk-selected').forEach(c => c.classList.remove('bulk-selected'));
            this._updateBar();
        },

        // ── Search ──
        search(query) {
            if (!this._lazyLoaded && document.querySelector('[data-lazy-section]')) {
                this._lazyLoaded = true;
                LazySection.loadAll('rehearsals-list').then(() => this.search(query));
                return;
            }
            const q = query.toLowerCase().trim();
            let visible = 0;
            document.querySelectorAll('.ie-card[data-rehearsal-id]:not([data-rehearsal-id="recurring"])').forEach(card => {
                const haystack = [
                    card.dataset.type || '',
                    card.dataset.location || '',
                    card.dataset.tags || '',
                    card.dataset.roles || '',
                    card.dataset.note || '',
                    card.dataset.start || '',
                    card.querySelector('[data-ie-date]')?.textContent || '',
                    card.querySelector('[data-ie-weekday]')?.textContent || '',
                    card.querySelector('[data-ie-groups]')?.textContent || '',
                ].join(' ').toLowerCase();

                const matchSearch = !q || haystack.includes(q);
                const matchFilter = this._matchFilters(card);
                const show = matchSearch && matchFilter;
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            const noRes = document.getElementById('bulkNoResults');
            if (noRes) noRes.style.display = visible === 0 && (q || Object.keys(this._activeFilters).length) ? '' : 'none';
        },

        _matchFilters(card) {
            for (const [key, val] of Object.entries(this._activeFilters)) {
                if (!val) continue;
                if (key === 'type' && (card.dataset.type || '').toLowerCase() !== val.toLowerCase()) return false;
                if (key === 'location' && (card.dataset.location || '').toLowerCase() !== val.toLowerCase()) return false;
                if (key === 'color' && (card.dataset.color || '') !== val) return false;
                if (key === 'tags' && !(card.dataset.tags || '').toLowerCase().includes(val.toLowerCase())) return false;
                if (key === 'roles' && !(card.dataset.roles || '').toLowerCase().includes(val.toLowerCase())) return false;
                if (key === 'groups') {
                    try {
                        const groups = JSON.parse(card.dataset.groups || '[]');
                        if (Array.isArray(val) && val.length > 0) {
                            if (groups.length === 0) return false;
                            if (!groups.every(g => val.includes(g))) return false;
                        } else {
                            if (!groups.includes(val)) return false;
                        }
                    } catch(e) { return false; }
                }
                if (key === 'dateRange') {
                    const start = card.dataset.start?.split(' ')[0] || '';
                    if (val.from && start < val.from) return false;
                    if (val.to && start > val.to) return false;
                }
            }
            return true;
        },

        // ── Filter chips ──
        openFilter(chip) {
            this._closePopover();
            const filterType = chip.dataset.filter;

            if (filterType === 'groups') {
                this._filterGroupsMode = true;
                this._filterGroupsChip = chip;
                const modal = document.getElementById('ieGroupsModal');
                const body = document.getElementById('ieGroupsBody');
                if (modal && body) {
                    body.querySelectorAll('input[name="groups[]"]').forEach(cb => { cb.checked = false; cb.indeterminate = false; });
                    const currentFilters = this._activeFilters['groups'] || [];
                    if (Array.isArray(currentFilters)) {
                        currentFilters.forEach(g => {
                            const cb = body.querySelector(`input[name="groups[]"][value="${g}"]`);
                            if (cb) cb.checked = true;
                        });
                    }
                    if (typeof recalculateHierarchyStates === 'function') recalculateHierarchyStates(body);
                    modal.showModal();
                }
                return;
            }

            // Force-load all lazy batches before building filter options
            if (!this._lazyLoaded && document.querySelector('[data-lazy-section]')) {
                this._lazyLoaded = true;
                LazySection.loadAll('rehearsals-list').then(() => this._buildFilterDropdown(chip, filterType));
                return;
            }
            this._buildFilterDropdown(chip, filterType);
        },

        _buildFilterDropdown(chip, filterType) {
            const vals = this._collectValues(filterType);

            const pop = document.createElement('div');
            pop.className = 'bulk-filter-dropdown';

            if (filterType === 'dateRange') {
                pop.innerHTML = `<div class="bulk-popover-title">Zeitraum</div>
                    <label>Von</label>
                    <input type="date" class="bulk-popover-input" id="filterDateFrom" value="${this._activeFilters.dateRange?.from || ''}">
                    <label>Bis</label>
                    <input type="date" class="bulk-popover-input" id="filterDateTo" value="${this._activeFilters.dateRange?.to || ''}">
                    <button class="bulk-popover-apply" onclick="BulkMgr._applyDateFilter()">Anwenden</button>`;
            } else if (filterType === 'color') {
                pop.innerHTML = '<div class="bulk-popover-title">Farbe</div><div class="bulk-color-grid"></div>';
                const grid = pop.querySelector('.bulk-color-grid');
                Object.entries(COLORS).forEach(([hex, name]) => {
                    const sw = document.createElement('button');
                    sw.className = 'bulk-color-swatch';
                    sw.style.background = hex;
                    sw.title = name;
                    sw.onclick = (e) => { e.stopPropagation(); this._setFilter('color', hex, chip); this._closePopover(); };
                    grid.appendChild(sw);
                });
                const clearBtn = document.createElement('button');
                clearBtn.className = 'bulk-popover-apply';
                clearBtn.textContent = 'Filter entfernen';
                clearBtn.style.cssText = 'background:var(--color-bg-tertiary);color:var(--color-text-secondary);margin-top:8px';
                clearBtn.onclick = () => { this._clearFilter('color', chip); this._closePopover(); };
                pop.appendChild(clearBtn);
            } else {
                vals.forEach(v => {
                    const opt = document.createElement('div');
                    opt.className = 'bulk-filter-option' + (this._activeFilters[filterType] === v ? ' selected' : '');
                    opt.textContent = v;
                    opt.onclick = (e) => {
                        e.stopPropagation();
                        if (this._activeFilters[filterType] === v) {
                            this._clearFilter(filterType, chip);
                        } else {
                            this._setFilter(filterType, v, chip);
                        }
                        this._closePopover();
                    };
                    pop.appendChild(opt);
                });
                if (!vals.length) {
                    pop.innerHTML = '<div style="padding:8px;font-size:12px;color:var(--color-text-muted)">Keine Optionen</div>';
                }
            }

            const rect = chip.getBoundingClientRect();
            const popWidth = 240;
            const clampedLeft = Math.min(Math.max(8, rect.left), window.innerWidth - popWidth - 8);
            const isBottomSticky = document.getElementById('bulkToolbarSticky')?.classList.contains('is-bottom-sticky');
            if (isBottomSticky || rect.bottom + 250 > window.innerHeight) {
                pop.style.cssText += `position:fixed;bottom:${window.innerHeight - rect.top + 4}px;left:${clampedLeft}px;`;
            } else {
                pop.style.cssText += `position:fixed;top:${rect.bottom + 4}px;left:${clampedLeft}px;`;
            }
            pop.onclick = e => e.stopPropagation();

            const backdrop = document.createElement('div');
            backdrop.className = 'bulk-backdrop';
            backdrop.onclick = () => this._closePopover();
            document.body.appendChild(backdrop);
            document.body.appendChild(pop);
            this._backdrop = backdrop;
            this._popover = pop;
        },

        _collectValues(filterType) {
            const set = new Set();
            document.querySelectorAll('.ie-card[data-rehearsal-id]:not([data-rehearsal-id="recurring"])').forEach(card => {
                let v;
                if (filterType === 'type') v = card.dataset.type;
                else if (filterType === 'location') v = card.dataset.location;
                else if (filterType === 'tags') {
                    (card.dataset.tags || '').split(',').forEach(t => { if (t.trim()) set.add(t.trim()); });
                    return;
                }
                else if (filterType === 'roles') {
                    (card.dataset.roles || '').split(',').forEach(r => { if (r.trim()) set.add(r.trim()); });
                    return;
                }
                else if (filterType === 'groups') {
                    try {
                        const groups = JSON.parse(card.dataset.groups || '[]');
                        groups.forEach(g => { if (g.trim()) set.add(g.trim()); });
                    } catch(e) {}
                    return;
                }
                if (v?.trim()) set.add(v.trim());
            });
            return [...set].sort();
        },

        _setFilter(key, val, chip) {
            this._activeFilters[key] = val;
            chip.classList.add('active');
            this.search(document.getElementById('bulkSearch')?.value || '');
        },

        _clearFilter(key, chip) {
            delete this._activeFilters[key];
            chip.classList.remove('active');
            this.search(document.getElementById('bulkSearch')?.value || '');
        },

        _applyDateFilter() {
            const from = document.getElementById('filterDateFrom')?.value || '';
            const to = document.getElementById('filterDateTo')?.value || '';
            const chip = document.querySelector('[data-filter="dateRange"]');
            if (from || to) {
                this._setFilter('dateRange', { from, to }, chip);
            } else {
                this._clearFilter('dateRange', chip);
            }
            this._closePopover();
        },

        _closePopover() {
            this._popover?.remove(); this._popover = null;
            this._backdrop?.remove(); this._backdrop = null;
            this._bulkPicmoContainer?.remove(); this._bulkPicmoContainer = null;
        },

        // ── Bulk actions ──
        openAction(type, btn) {
            this._closePopover();
            const ids = [...this.selected];
            if (!ids.length) return;

            const pop = document.createElement('div');
            pop.className = 'bulk-popover';

            switch (type) {
                case 'type':
                    pop.innerHTML = '<div class="bulk-popover-title">Typ setzen</div><div class="bulk-type-list"></div>';
                    const list = pop.querySelector('.bulk-type-list');
                    const allTypes = [...new Set([...TYPE_PRESETS, ...[...document.querySelectorAll('.ie-card[data-type]')].map(c => c.dataset.type).filter(t => t && t.toLowerCase() !== 'probe')])];
                    allTypes.forEach(t => {
                        const opt = document.createElement('div');
                        opt.className = 'bulk-type-option';
                        opt.textContent = t;
                        opt.onclick = () => { this._applyBulk({ type: t }); this._closePopover(); };
                        list.appendChild(opt);
                    });
                    break;

                case 'location':
                    pop.innerHTML = `<div class="bulk-popover-title">Ort setzen</div>
                        <input class="bulk-popover-input" id="bulkLocInput" placeholder="Ort eingeben…" autocomplete="off">
                        <button class="bulk-popover-apply" onclick="BulkMgr._applyBulk({location:document.getElementById('bulkLocInput').value.trim()});BulkMgr._closePopover()">Anwenden</button>`;
                    setTimeout(() => pop.querySelector('#bulkLocInput')?.focus(), 50);
                    pop.querySelector('#bulkLocInput')?.addEventListener('keydown', e => {
                        if (e.key === 'Enter') { e.preventDefault(); this._applyBulk({ location: e.target.value.trim() }); this._closePopover(); }
                    });
                    break;

                case 'groups':
                    // Reuse the existing groups modal with bulk callback
                    this._bulkGroupsMode = true;
                    const modal = document.getElementById('ieGroupsModal');
                    const body = document.getElementById('ieGroupsBody');
                    if (modal && body) {
                        body.querySelectorAll('input[name="groups[]"]').forEach(cb => { cb.checked = false; cb.indeterminate = false; });
                        if (typeof recalculateHierarchyStates === 'function') recalculateHierarchyStates(body);
                        modal.showModal();
                    }
                    return;

                case 'color':
                    pop.innerHTML = '<div class="bulk-popover-title">Farbe setzen</div><div class="bulk-color-grid"></div>';
                    const cGrid = pop.querySelector('.bulk-color-grid');
                    Object.entries(COLORS).forEach(([hex, name]) => {
                        const sw = document.createElement('button');
                        sw.className = 'bulk-color-swatch';
                        sw.style.background = hex;
                        sw.title = name;
                        sw.onclick = () => { this._applyBulk({ color: hex }); this._closePopover(); };
                        cGrid.appendChild(sw);
                    });
                    break;

                case 'time':
                    pop.innerHTML = `<div class="bulk-popover-title">Uhrzeit setzen</div>
                        <div class="bulk-time-row">
                            <input type="time" id="bulkTimeStart" value="18:00">
                            <span>–</span>
                            <input type="time" id="bulkTimeEnd" value="20:00">
                        </div>
                        <button class="bulk-popover-apply" onclick="BulkMgr._applyTimeAction()">Anwenden</button>`;
                    break;

                case 'tag':
                    pop.innerHTML = `<div class="bulk-popover-title">Tag hinzufügen</div>
                        <input class="bulk-popover-input" id="bulkTagInput" placeholder="Tag eingeben…" autocomplete="off">
                        <button class="bulk-popover-apply" onclick="BulkMgr._applyTagAction()">Hinzufügen</button>`;
                    setTimeout(() => pop.querySelector('#bulkTagInput')?.focus(), 50);
                    pop.querySelector('#bulkTagInput')?.addEventListener('keydown', e => {
                        if (e.key === 'Enter') { e.preventDefault(); this._applyTagAction(); }
                    });
                    break;

                case 'note':
                    pop.innerHTML = `<div class="bulk-popover-title">Info hinzufügen</div>
                        <div class="bulk-note-row">
                            <button type="button" class="bulk-emoji-pick" id="bulkNoteEmoji" title="Emoji wählen">📌</button>
                            <input class="bulk-popover-input" id="bulkNoteText" placeholder="Info-Text…" style="flex:1">
                        </div>
                        <button class="bulk-popover-apply" onclick="BulkMgr._applyNoteAction()">Hinzufügen</button>`;
                    pop.querySelector('#bulkNoteEmoji').addEventListener('click', (e) => {
                        e.stopPropagation();
                        this._openBulkEmojiPicker(pop.querySelector('#bulkNoteEmoji'));
                    });
                    setTimeout(() => pop.querySelector('#bulkNoteText')?.focus(), 50);
                    break;

                case 'delete':
                    pop.innerHTML = `<div class="bulk-popover-title">${ids.length} Termin${ids.length > 1 ? 'e' : ''} löschen</div>
                        <p style="font-size:var(--font-size-sm);color:var(--color-text-secondary);margin:0 0 var(--space-2)">Kann nicht rückgängig gemacht werden.</p>
                        <button class="bulk-popover-apply" id="bulkDeleteConfirm" style="background:var(--color-danger,#ef4444)">Endgültig löschen</button>`;
                    pop.querySelector('#bulkDeleteConfirm').addEventListener('click', async () => {
                        this._closePopover();
                        const orchestraBase = document.querySelector('[data-api-url]')?.dataset.apiUrl?.match(/^\/([^/]+\/[^/]+)/)?.[1];
                        let failed = 0;
                        await Promise.all(ids.map(id =>
                            fetch(`/${orchestraBase}/rehearsals/delete/${id}`, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'id=' + id,
                            })
                            .then(r => r.json())
                            .then(data => { if (!data.success) failed++; })
                            .catch(() => { failed++; })
                        ));
                        ids.forEach(id => {
                            const card = document.querySelector(`[data-rehearsal-id="${id}"]`);
                            if (card) {
                                card.style.transition = 'opacity 0.3s ease, max-height 0.3s ease';
                                card.style.opacity = '0';
                                card.style.maxHeight = '0';
                                card.style.overflow = 'hidden';
                                setTimeout(() => card.remove(), 350);
                            }
                        });
                        this.deselectAll();
                        if (this.active) this.toggle();
                        if (failed > 0) {
                            window.notifyError?.(`${failed} Termin${failed > 1 ? 'e' : ''} konnten nicht gelöscht werden`);
                        } else {
                            window.notifySuccess?.(ids.length + ' Termine gelöscht');
                        }
                    });
                    break;

                case 'duplicate':
                    pop.innerHTML = `<div class="bulk-popover-title">${ids.length} Termin${ids.length > 1 ? 'e' : ''} duplizieren</div>
                        <label style="font-size:var(--font-size-sm);color:var(--color-text-secondary)">Tage verschieben
                            <input type="number" class="bulk-popover-input" id="bulkDupOffset" value="7" min="1" max="365" style="margin-top:4px">
                        </label>
                        <button class="bulk-popover-apply" id="bulkDupConfirm">Duplizieren</button>`;
                    pop.querySelector('#bulkDupConfirm').addEventListener('click', async () => {
                        const offset = parseInt(pop.querySelector('#bulkDupOffset').value) || 7;
                        this._closePopover();
                        const orchestraBase = document.querySelector('[data-api-url]')?.dataset.apiUrl?.match(/^\/([^/]+\/[^/]+)/)?.[1];
                        const list = document.getElementById('rehearsalsList');
                        let created = 0;
                        for (const id of ids) {
                            try {
                                const res = await fetch(`/${orchestraBase}/rehearsals/duplicate/${id}`, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: 'offset_days=' + offset,
                                });
                                const data = await res.json();
                                if (data.success && data.html && list) {
                                    const tmp = document.createElement('div');
                                    tmp.innerHTML = data.html;
                                    const newCard = tmp.querySelector('.ie-card');
                                    if (newCard) {
                                        newCard.style.opacity = '0';
                                        while (tmp.firstChild) {
                                            list.appendChild(tmp.firstChild);
                                        }
                                        requestAnimationFrame(() => { newCard.style.transition = 'opacity 0.3s'; newCard.style.opacity = '1'; });
                                        created++;
                                    }
                                }
                            } catch (_) {
                                window.notifyError?.('Ein Termin konnte nicht dupliziert werden');
                            }
                        }
                        this.deselectAll();
                        if (this.active) this.toggle();
                        window.notifySuccess?.(created + ' Termin' + (created !== 1 ? 'e' : '') + ' dupliziert');
                    });
                    break;
            }

            // Position above the action bar
            const barRect = document.querySelector('.bulk-action-panel')?.getBoundingClientRect();
            if (barRect) {
                pop.style.bottom = (window.innerHeight - barRect.top + 8) + 'px';
                pop.style.left = '50%';
                pop.style.transform = 'translateX(-50%)';
                pop.style.position = 'fixed';
            }

            const backdrop = document.createElement('div');
            backdrop.className = 'bulk-backdrop';
            backdrop.onclick = () => this._closePopover();
            document.body.appendChild(backdrop);
            document.body.appendChild(pop);
            this._backdrop = backdrop;
            this._popover = pop;
        },

        // ── Apply bulk field update via API ──
        _applyBulk(fields) {
            const ids = [...this.selected].map(Number);
            if (!ids.length) return;

            const orchestraBase = document.querySelector('[data-api-url]')?.dataset.apiUrl?.match(/^\/([^/]+\/[^/]+)/)?.[1];
            if (!orchestraBase) return;

            fetch('/' + orchestraBase + '/api/rehearsals/bulk-update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids, fields }),
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success && data.errors > 0) {
                    window.notifyError?.('Einige Änderungen konnten nicht gespeichert werden');
                }
                // Update DOM for each affected card
                ids.forEach(id => {
                    const card = document.querySelector(`[data-rehearsal-id="${id}"]`);
                    if (!card) return;
                    if (fields.type != null) {
                        card.dataset.type = fields.type;
                        const badge = card.querySelector('[data-ie-type]');
                        if (badge) {
                            const isDefault = !fields.type || fields.type.toLowerCase() === 'probe';
                            badge.textContent = isDefault ? 'Typ…' : fields.type;
                            badge.style.opacity = isDefault ? '0.4' : '';
                            badge.style.borderStyle = isDefault ? 'dashed' : '';
                        }
                    }
                    if (fields.location != null) {
                        card.dataset.location = fields.location;
                        const loc = card.querySelector('[data-ie-location]');
                        if (loc) {
                            const isDefault = !fields.location || fields.location.toLowerCase() === 'probenraum';
                            loc.textContent = fields.location || '📍 Ort…';
                            loc.style.opacity = isDefault ? '0.4' : '';
                            loc.style.borderStyle = isDefault ? 'dashed' : '';
                        }
                    }
                    if (fields.color != null) {
                        card.dataset.color = fields.color;
                        card.style.borderLeftColor = fields.color;
                        const dot = card.querySelector('[data-ie-color-dot]');
                        if (dot) dot.style.background = fields.color;
                    }
                    if (data.results?.[id]?.groups_display) {
                        const badge = card.querySelector('[data-ie-groups]');
                        if (badge) badge.textContent = data.results[id].groups_display;
                    }
                });
                window.notifySuccess?.(data.updated + ' Termine aktualisiert');
            })
            .catch(() => window.notifyError?.('Netzwerkfehler – Änderungen nicht gespeichert'));
        },

        _applyTimeAction() {
            const st = document.getElementById('bulkTimeStart')?.value;
            const et = document.getElementById('bulkTimeEnd')?.value;
            if (!st || !et) return;

            [...this.selected].forEach(id => {
                const card = document.querySelector(`[data-rehearsal-id="${id}"]`);
                if (!card) return;
                const oldStart = card.dataset.start || '';
                const oldEnd = card.dataset.end || '';
                const datePart = oldStart.split(' ')[0] || new Date().toISOString().split('T')[0];
                const endDatePart = oldEnd.split(' ')[0] || datePart;
                const newStart = datePart + ' ' + st + ':00';
                const newEnd = endDatePart + ' ' + et + ':00';
                card.dataset.start = newStart;
                card.dataset.end = newEnd;
                window.IEM?._saveFields(card, { start: newStart, end: newEnd });

                // Update time display
                const timeEl = card.querySelector('[data-ie-time]');
                if (timeEl) timeEl.textContent = st + ' – ' + et;
            });
            this._closePopover();
            window.notifySuccess?.(this.selected.size + ' Termine aktualisiert');
        },

        _applyTagAction() {
            const tag = document.getElementById('bulkTagInput')?.value.trim();
            if (!tag) return;

            [...this.selected].forEach(id => {
                const card = document.querySelector(`[data-rehearsal-id="${id}"]`);
                if (!card) return;
                const existing = [...card.querySelectorAll('.ie-tag')].map(t => t.dataset.tag);
                if (existing.includes(tag)) return;

                // Create tag element
                const tagsContainer = card.querySelector('[data-ie-tags]');
                if (tagsContainer) {
                    const addBtn = tagsContainer.querySelector('.ie-tag-add');
                    const tagEl = document.createElement('span');
                    tagEl.className = 'ie-tag';
                    tagEl.dataset.tag = tag;
                    tagEl.textContent = tag;
                    const rm = document.createElement('button');
                    rm.type = 'button';
                    rm.className = 'ie-tag-remove';
                    rm.title = 'Entfernen';
                    rm.textContent = '×';
                    rm.addEventListener('click', (e) => { e.stopPropagation(); window.IEM?.removeTag(rm); });
                    tagEl.appendChild(rm);
                    if (addBtn) tagsContainer.insertBefore(tagEl, addBtn);
                    else tagsContainer.appendChild(tagEl);
                }

                const allTags = [...card.querySelectorAll('.ie-tag')].map(t => t.dataset.tag);
                card.dataset.tags = allTags.join(',');
                window.IEM?._save(card, 'tags', JSON.stringify(allTags));
            });
            this._closePopover();
            window.notifySuccess?.('Tag "' + tag + '" hinzugefügt');
        },

        _openBulkEmojiPicker(btnEl) {
            // Lazy-load picmo
            const load = () => new Promise(resolve => {
                if (window.picmo) return resolve();
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/picmo@5.8.5/dist/umd/index.min.js';
                s.onload = resolve;
                document.head.appendChild(s);
            });

            load().then(() => {
                this._bulkPicmoContainer?.remove();
                const c = document.createElement('div');
                c.style.cssText = 'position:fixed;z-index:9999;';
                document.body.appendChild(c);
                this._bulkPicmoContainer = c;

                const picker = picmo.createPicker({
                    rootElement: c,
                    showPreview: false,
                    autoFocus: 'search',
                    messages: { searchPlaceholder: 'Suchen...', noEmojisFound: 'Keine Emojis gefunden', recents: 'Zuletzt verwendet' }
                });

                picker.addEventListener('emoji:select', ev => {
                    btnEl.textContent = ev.emoji;
                    c.remove();
                    this._bulkPicmoContainer = null;
                });

                // Position above the popover
                const rect = btnEl.getBoundingClientRect();
                c.style.bottom = (window.innerHeight - rect.top + 8) + 'px';
                c.style.left = '50%';
                c.style.transform = 'translateX(-50%)';
            });
        },

        _applyNoteAction() {
            const emoji = document.getElementById('bulkNoteEmoji')?.textContent?.trim() || '📌';
            const text = document.getElementById('bulkNoteText')?.value.trim();
            if (!text) return;

            [...this.selected].forEach(id => {
                const card = document.querySelector(`[data-rehearsal-id="${id}"]`);
                if (!card) return;

                // Update badge row live
                const badges = card.querySelector('.rehearsal-badges');
                if (badges) {
                    const infoTag = document.createElement('span');
                    infoTag.className = 'ie-info-tag';
                    infoTag.style.cssText = 'font-size:11px;padding:2px 6px;border-radius:var(--radius-sm);display:inline-flex;align-items:center;justify-content:center;width:fit-content;margin-right:var(--space-1);background-color:transparent;border:1px solid var(--color-border);color:var(--color-text-primary);transition:all 0.25s ease;';
                    infoTag.textContent = emoji;
                    badges.appendChild(infoTag);
                }

                // addInfoItem appends to the editor's items array and triggers auto-save
                card.querySelector('.infobox-editor')?.addInfoItem?.(emoji, text);

                // Keep data-note in sync for search
                card.dataset.note = (card.dataset.note ? card.dataset.note + ' ' : '') + emoji + ' ' + text;
            });
            this._closePopover();
            window.notifySuccess?.('Info zu ' + this.selected.size + ' Terminen hinzugefügt');
        },
    };

    window.BulkMgr = BulkMgr;

    // ── Wire up events ──
    document.getElementById('bulkSelectToggle')?.addEventListener('click', () => BulkMgr.toggle());
    document.getElementById('bulkSearch')?.addEventListener('input', e => BulkMgr.search(e.target.value));
    document.getElementById('bulkDeselectAll')?.addEventListener('click', () => { BulkMgr.deselectAll(); if (BulkMgr.active) BulkMgr.toggle(); });

    document.querySelectorAll('.bulk-filter-chip').forEach(chip => {
        chip.addEventListener('click', () => BulkMgr.openFilter(chip));
    });

    document.querySelectorAll('[data-bulk]').forEach(btn => {
        btn.addEventListener('click', () => BulkMgr.openAction(btn.dataset.bulk, btn));
    });

    // Quick-add rehearsal with date picker
    document.getElementById('bulkQuickAdd')?.addEventListener('click', function() {
        BulkMgr._closePopover();

        const btn = this;
        const rect = btn.getBoundingClientRect();
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const defaultDate = tomorrow.toISOString().split('T')[0];

        const pop = document.createElement('div');
        pop.className = 'bulk-filter-dropdown';
        pop.innerHTML = `<div class="bulk-popover-title">Neue Probe</div>
            <label>Datum</label>
            <input type="date" class="bulk-popover-input" id="quickAddDate" value="${defaultDate}">
            <button class="bulk-popover-apply" id="quickAddConfirm">Erstellen</button>`;
        const isBottomSticky = document.getElementById('bulkToolbarSticky')?.classList.contains('is-bottom-sticky');
        if (isBottomSticky || rect.bottom + 200 > window.innerHeight) {
            pop.style.cssText = `position:fixed;bottom:${window.innerHeight - rect.top + 4}px;left:${Math.max(8, rect.left - 120)}px;`;
        } else {
            pop.style.cssText = `position:fixed;top:${rect.bottom + 4}px;left:${Math.max(8, rect.left - 120)}px;`;
        }
        pop.onclick = e => e.stopPropagation();

        const backdrop = document.createElement('div');
        backdrop.className = 'bulk-backdrop';
        backdrop.onclick = () => BulkMgr._closePopover();
        document.body.appendChild(backdrop);
        document.body.appendChild(pop);
        BulkMgr._backdrop = backdrop;
        BulkMgr._popover = pop;

        pop.querySelector('#quickAddConfirm').addEventListener('click', () => {
            const date = pop.querySelector('#quickAddDate').value;
            if (!date) return;
            BulkMgr._closePopover();

            const createUrl = document.querySelector('[data-create-url]')?.dataset.createUrl;
            if (!createUrl) return;

            btn.classList.add('loading');
            fetch(createUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ date })
            })
            .then(r => r.json())
            .then(data => {
                btn.classList.remove('loading');
                if (!data.success || !data.html) {
                    window.notifyError?.(data.error || 'Termin konnte nicht erstellt werden');
                    return;
                }
                const list = document.getElementById('rehearsalsList') || document.querySelector('.rehearsal-add-box')?.parentElement;
                if (!list) return;
                const tmp = document.createElement('div');
                tmp.innerHTML = data.html;
                const newCard = tmp.querySelector('.ie-card');
                if (!newCard) return;
                // Insert before add-box if present
                const addBox = list.querySelector('.rehearsal-add-box');
                if (addBox) list.insertBefore(newCard, addBox);
                else list.appendChild(newCard);
                if (window.IEM) window.IEM._activateScripts(newCard);
                document.querySelector('.empty-state')?.closest('.flex')?.remove();
                requestAnimationFrame(() => {
                    if (window.IEM) window.IEM._expand(newCard);
                    newCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            })
            .catch(() => {
                btn.classList.remove('loading');
                window.notifyError?.('Netzwerkfehler – Termin nicht erstellt');
            });
        });
    });

    // Vertical wheel → horizontal scroll for chip rows
    document.querySelectorAll('.bulk-action-buttons, .bulk-filter-row').forEach(row => {
        row.addEventListener('wheel', e => {
            if (row.scrollWidth <= row.clientWidth) return;
            e.preventDefault();
            row.scrollLeft += e.deltaY;
        }, { passive: false });
    });

    // Intercept card clicks in bulk mode
    document.addEventListener('click', e => {
        if (!BulkMgr.active) return;
        const card = e.target.closest('.ie-card');
        if (card && !e.target.closest('.ie-footer-btn') && !e.target.closest('.ie-popover') && !e.target.closest('.ie-section')) {
            BulkMgr.onCardClick(card, e);
        }
    }, true);

    // Handle groups modal close in bulk mode
    const origClose = window.IEM?.closeGroupsModal;
    if (origClose) {
        const originalFn = origClose.bind(window.IEM);
        window.IEM.closeGroupsModal = function() {
            if (BulkMgr._bulkGroupsMode) {
                const body = document.getElementById('ieGroupsBody');
                const checked = [...body.querySelectorAll('input[name="groups[]"]:checked')].map(cb => cb.value);
                document.getElementById('ieGroupsModal')?.close();
                BulkMgr._bulkGroupsMode = false;
                if (checked.length > 0) {
                    BulkMgr._applyBulk({ groups: JSON.stringify(checked) });
                }
                return;
            }
            if (BulkMgr._filterGroupsMode) {
                const body = document.getElementById('ieGroupsBody');
                const checked = [...body.querySelectorAll('input[name="groups[]"]:checked')].map(cb => cb.value);
                document.getElementById('ieGroupsModal')?.close();
                BulkMgr._filterGroupsMode = false;
                if (checked.length > 0) {
                    BulkMgr._setFilter('groups', checked, BulkMgr._filterGroupsChip);
                } else {
                    BulkMgr._clearFilter('groups', BulkMgr._filterGroupsChip);
                }
                return;
            }
            originalFn();
        };
    }

    // Long-press to enter select mode (mobile)
    let longPressTimer = null;
    document.addEventListener('touchstart', (e) => {
        const card = e.target.closest('.ie-card');
        if (!card || BulkMgr.active) return;
        longPressTimer = setTimeout(() => {
            BulkMgr.toggle();
            BulkMgr.onCardClick(card, e);
        }, 500);
    }, { passive: true });
    document.addEventListener('touchend', () => clearTimeout(longPressTimer));
    document.addEventListener('touchmove', () => clearTimeout(longPressTimer));

    // ── Recurring Card (inline series creator) ──
    const recCard = document.getElementById('recurringCard');
    if (recCard) {
        const DAY_SHORT = ['So','Mo','Di','Mi','Do','Fr','Sa'];
        let selectedDays = new Set();
        let intervalWeeks = 1;

        const today = new Date();
        const nextMon = new Date(today);
        nextMon.setDate(today.getDate() + ((8 - today.getDay()) % 7 || 7));
        const endDefault = new Date(nextMon);
        endDefault.setMonth(endDefault.getMonth() + 3);

        function computeDates() {
            const startInput = document.getElementById('recurringStart');
            const endInput = document.getElementById('recurringEnd');
            const startStr = startInput ? startInput.value : null;
            const endStr = endInput ? endInput.value : null;
            if (!startStr || !endStr || selectedDays.size === 0) return [];

            const start = new Date(startStr + 'T00:00:00');
            const end = new Date(endStr + 'T23:59:59');
            if (start > end) return [];
            
            const dates = [];
            const cursor = new Date(start);
            cursor.setDate(cursor.getDate() - ((cursor.getDay() + 6) % 7));
            while (cursor <= end) {
                for (let d = 0; d < 7; d++) {
                    const check = new Date(cursor);
                    check.setDate(cursor.getDate() + d);
                    if (check < start || check > end) continue;
                    if (selectedDays.has(check.getDay())) {
                        const localDateStr = check.getFullYear() + '-' + String(check.getMonth() + 1).padStart(2, '0') + '-' + String(check.getDate()).padStart(2, '0');
                        dates.push(localDateStr);
                    }
                }
                cursor.setDate(cursor.getDate() + 7 * intervalWeeks);
            }
            return dates;
        }

        window.updateEndDateFromDuration = function(selectEl) {
            const val = selectEl.value;
            if (val === 'custom') return;
            
            // Text is now natively displayed by the select element
            
            const startInput = document.getElementById('recurringStart');
            const endInput = document.getElementById('recurringEnd');
            if (!startInput || !endInput || !startInput.value) return;
            
            const start = new Date(startInput.value + 'T00:00:00');
            const num = parseInt(val);
            const unit = val.replace(/[0-9]/g, '');
            
            if (unit === 'w') start.setDate(start.getDate() + num * 7);
            else if (unit === 'm') start.setMonth(start.getMonth() + num);
            else if (unit === 'y') start.setFullYear(start.getFullYear() + num);
            
            const endStr = start.toISOString().split('T')[0];
            endInput.value = endStr;
            const endSpanText = document.getElementById('recurringEndSpan').firstChild;
            if (endSpanText) endSpanText.textContent = endStr + '\n';
            
            updatePreview();
        };

        function updatePreview() {
            const dates = computeDates();
            const preview = document.getElementById('recurringPreview');
            const submit = document.getElementById('recurringSubmit');
            if (dates.length === 0) {
                preview.innerHTML = 'Wähle mindestens einen Tag';
                submit.disabled = true;
            } else {
                preview.innerHTML = `→ <strong>${dates.length}</strong> Termin${dates.length !== 1 ? 'e' : ''}`;
                submit.disabled = false;
            }
        }

        document.getElementById('recurringDays').addEventListener('click', e => {
            const btn = e.target.closest('.rc-day');
            if (!btn) return;
            const day = parseInt(btn.dataset.day);
            const DAY_TAGS = ['Sonntags-Probe', 'Montags-Probe', 'Dienstags-Probe', 'Mittwochs-Probe', 'Donnerstags-Probe', 'Freitags-Probe', 'Samstags-Probe'];
            const tagName = DAY_TAGS[day];

            const tagsContainer = document.getElementById('recurringTagsContainer');
            const addBtn = tagsContainer?.querySelector('.ie-tag-add');

            if (selectedDays.has(day)) {
                // Deselect day
                selectedDays.delete(day);
                btn.classList.remove('active');
                
                // Remove the corresponding auto-tag if it exists
                if (tagsContainer) {
                    const tagEl = tagsContainer.querySelector(`.ie-tag[data-tag="${tagName}"]`);
                    if (tagEl) tagEl.remove();
                }
            } else {
                // Select day
                selectedDays.add(day);
                btn.classList.add('active');
                
                // Add the corresponding auto-tag
                if (tagsContainer && addBtn && !tagsContainer.querySelector(`.ie-tag[data-tag="${tagName}"]`)) {
                    const span = document.createElement('span');
                    span.className = 'ie-tag';
                    span.dataset.tag = tagName;
                    span.innerHTML = `${tagName}<button type="button" class="ie-tag-remove" onclick="if(!window.IEM?._guard(event))return; window.IEM.removeTag(this)" title="Entfernen">×</button>`;
                    tagsContainer.insertBefore(span, addBtn);
                }
            }
            
            updatePreview();
        });

        document.getElementById('recurringStart')?.addEventListener('change', updatePreview);
        document.getElementById('recurringEnd')?.addEventListener('change', updatePreview);

        document.getElementById('recurringInterval').addEventListener('click', e => {
            const btn = e.target.closest('.rc-toggle-opt');
            if (!btn) return;
            document.querySelectorAll('.rc-toggle-opt').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            intervalWeeks = parseInt(btn.dataset.weeks);
            updatePreview();
        });

        // Type, location, color: handled by IEM via onclick on badges
        // Values are read from the ie-card's data-* attrs at submit time

        // Listen for dataset.start/end changes (IEM updates these) to refresh the preview
        const observer = new MutationObserver((mutations) => {
            for (let m of mutations) {
                if (m.type === 'attributes' && (m.attributeName === 'data-start' || m.attributeName === 'data-end')) {
                    updatePreview();
                }
            }
        });
        observer.observe(recCard.querySelector('.ie-card'), { attributes: true, attributeFilter: ['data-start', 'data-end'] });

        document.getElementById('recurringOpen')?.addEventListener('click', () => {
            const isOpen = recCard.classList.toggle('open');
            if (isOpen) recCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
        document.getElementById('recurringClose').addEventListener('click', () => recCard.classList.remove('open'));

        document.addEventListener('click', (e) => {
            if (!recCard.classList.contains('open')) return;
            const openBtn = document.getElementById('recurringOpen');
            if (!recCard.contains(e.target) && !openBtn?.contains(e.target)) {
                recCard.classList.remove('open');
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && recCard.classList.contains('open')) {
                recCard.classList.remove('open');
            }
        });

        document.getElementById('recurringSubmit').addEventListener('click', () => {
            const dates = computeDates();
            if (dates.length === 0) return;

            let ieCard = recCard.querySelector('.ie-card');
            if (!ieCard) {
                const liveRecCard = document.getElementById('recurringCard');
                if (liveRecCard) ieCard = liveRecCard.querySelector('.ie-card');
            }
            
            if (!ieCard) {
                window.notifyError?.('Konnte Proben-Details nicht abrufen. Bitte laden Sie die Seite neu.');
                const submit = document.getElementById('recurringSubmit');
                if (submit) {
                    submit.disabled = false;
                    submit.textContent = 'Erstellen';
                }
                return;
            }
            
            // Extract tags from DOM elements (managed by IEM addTagInput/removeTag)
            const tags = Array.from(recCard.querySelectorAll('#recurringTagsContainer .ie-tag'))
                .map(span => span.dataset.tag)
                .filter(Boolean);

            // Read JSON from the hidden inputs created by the editors
            let scheduleItems = [];
            let infos = [];
            try {
                const schedInput = document.getElementById('schedule-editor-recurring-hidden');
                if (schedInput && schedInput.value) scheduleItems = JSON.parse(schedInput.value);
                const infoInput = document.getElementById('infobox-editor-recurring-hidden');
                if (infoInput && infoInput.value) infos = JSON.parse(infoInput.value);
            } catch (e) { console.error('Error parsing embedded editors', e); }

            const stInput = document.getElementById('recurringTimeStart');
            const etInput = document.getElementById('recurringTimeEnd');
            const startTimeStr = stInput && stInput.value ? stInput.value : (ieCard.dataset.start ? ieCard.dataset.start.split(' ')[1] : '18:00:00');
            const endTimeStr = etInput && etInput.value ? etInput.value : (ieCard.dataset.end ? ieCard.dataset.end.split(' ')[1] : '20:00:00');
            const formatTime = (t) => t.length === 5 ? t + ':00' : t;

            const payload = {
                dates,
                start_time: formatTime(startTimeStr),
                end_time: formatTime(endTimeStr),
                type: ieCard.dataset.type || '',
                location: ieCard.dataset.location || 'Probenraum',
                color: ieCard.dataset.color || '#e5e7eb',
                tags,
                schedule_items: scheduleItems,
                infos
            };

            const submit = document.getElementById('recurringSubmit');
            submit.disabled = true;
            submit.textContent = 'Erstelle…';

            const baseUrl = window.location.pathname.replace(/\/rehearsals\/?$/, '');
            fetch(`${baseUrl}/rehearsals/batch-create`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    recCard.classList.remove('open');
                    window.notifySuccess?.(`${data.count} Termine erfolgreich erstellt!`);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    submit.disabled = false;
                    submit.textContent = 'Erstellen';
                }
            })
            .catch(() => {
                submit.disabled = false;
                submit.textContent = 'Erstellen';
                window.notifyError?.('Netzwerkfehler – Termine nicht erstellt');
            });
        });
    }

    // Ensure newly loaded rehearsals inherit the bulk-selection state
    document.addEventListener('lazy:loaded', function(e) {
        if (window.BulkMgr && window.BulkMgr.active) {
            var cards = (e.target || document).querySelectorAll('.ie-card:not(.bulk-selectable)');
            cards.forEach(card => card.classList.add('bulk-selectable'));
        }
    });

})();
</script>
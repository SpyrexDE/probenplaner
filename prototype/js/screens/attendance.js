import { registerScreen, navigate, setHeader, renderTabbar, openSheet, toast, haptic, h, html, icon, refreshIcons, TABS } from '../app.js';
import { NOW, rehearsals, promises, attendance, users, ensemble, getMember, getSection, formatDate } from '../data.js';
import * as ui from '../ui.js';
import { weekdayAnchor, dayNumber, dayTile, rowDate, rowTags } from '../rsvp.js';
import { renderTeamHeader, deskBar } from './team.js';
import { openMenu } from '../menu.js';
import { seatingChart, attendanceSeats } from '../seating.js';

const state = { filter: 'all', rehearsalId: null, keep: new Set(), focus: null, reveal: false, period: 'recent', min: 2, sort: null };
const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
let bulkTimers = [];
let mounted = null;

const FILTERS = [
  { value: 'all', label: 'Alle', icon: 'users-round', tone: 'neutral' },
  { value: 'absent', label: 'Fehlt', icon: 'x', tone: 'no' },
  { value: 'deviation', label: 'Abweichung', icon: 'triangle-alert', tone: 'dev' },
  { value: 'unconfirmed', label: 'Nicht erfasst', icon: 'circle-dashed', tone: 'warn' },
];

const EMPTY = {
  absent: ['circle-check', 'green', 'Niemand fehlt'],
  deviation: ['circle-check', 'green', 'Keine Abweichungen'],
  unconfirmed: ['circle-check', 'green', 'Alles erfasst'],
};

const NOTE_CHIPS = [
  { value: 'late', label: 'Verspätet', side: 'present', kind: 'status', icon: 'clock', tone: 'yes' },
  { value: 'early', label: 'Früher gegangen', side: 'present', kind: 'reason', icon: 'log-out', tone: 'yes' },
  { value: 'excused', label: 'Entschuldigt', side: 'absent', kind: 'status', icon: 'circle-minus', tone: 'open' },
  { value: 'sick', label: 'Krank', side: 'absent', kind: 'reason', icon: 'thermometer', tone: 'no' },
];

const REASONS = { early: ['log-out', 'Früher gegangen'], sick: ['thermometer', 'Krank'] };
const GLYPHS = { late: ['present', 'clock', 'Verspätet'], excused: ['absent', 'circle-minus', 'Entschuldigt'] };

/* Data */

function me(role) {
  return users[role] ?? users.conductor;
}

function leaderSection(role) {
  return role === 'leader' ? me(role).sectionId : null;
}

function startedRehearsals(role) {
  const scope = leaderSection(role);
  return rehearsals
    .filter((r) => r.start <= NOW && !r.cancelled && (!scope || r.instrumentIds.includes(scope)))
    .sort((a, b) => b.start.localeCompare(a.start));
}

function recordOf(rehearsalId, memberId) {
  return attendance.find((a) => a.rehearsalId === rehearsalId && a.memberId === memberId) ?? null;
}

function proposalOf(promise) {
  if (promise?.status === 'attending') return 'present';
  if (promise?.status === 'not_attending') return 'absent';
  return null;
}

function sideOf(record) {
  return record ? (record.present ? 'present' : 'absent') : null;
}

function isDeviation(entry) {
  if (!entry.record) return false;
  if (entry.promise.status === 'attending') return !entry.record.present;
  if (entry.promise.status === 'not_attending') return entry.record.present;
  return false;
}

function groupOf(member) {
  const instrument = getSection(member.sectionId);
  if (!instrument) return null;
  return instrument.kind === 'section' ? instrument : getSection(instrument.parentId) ?? instrument;
}

function roster(rehearsal, role) {
  const scope = leaderSection(role);
  return promises
    .filter((p) => p.rehearsalId === rehearsal.id)
    .map((promise) => ({ promise, member: getMember(promise.memberId) }))
    .filter(({ member }) => member && (!scope || member.sectionId === scope))
    .map((entry) => {
      const record = recordOf(rehearsal.id, entry.member.id);
      return { ...entry, record, proposal: record ? null : proposalOf(entry.promise) };
    })
    .sort((a, b) => {
      const sa = getSection(a.member.sectionId)?.order ?? 99;
      const sb = getSection(b.member.sectionId)?.order ?? 99;
      return sa - sb || Number(b.member.isLeader) - Number(a.member.isLeader) || a.member.displayName.localeCompare(b.member.displayName, 'de');
    });
}

function counts(entries) {
  const recorded = entries.filter((e) => e.record);
  return {
    total: entries.length,
    recorded: recorded.length,
    present: recorded.filter((e) => e.record.present).length,
    excused: recorded.filter((e) => e.record.status === 'excused').length,
    unexcused: recorded.filter((e) => e.record.status === 'absent').length,
    absent: recorded.filter((e) => !e.record.present).length,
    proposals: entries.filter((e) => e.proposal).length,
    deviation: entries.filter(isDeviation).length,
    unconfirmed: entries.length - recorded.length,
  };
}

function matches(entry, filter) {
  if (filter === 'absent') return Boolean(entry.record) && !entry.record.present;
  if (filter === 'deviation') return isDeviation(entry);
  if (filter === 'unconfirmed') return !entry.record;
  return true;
}

function isLive(rehearsal) {
  return rehearsal.start <= NOW && rehearsal.end > NOW;
}

function writeRecord(rehearsal, member, patch, role) {
  let record = recordOf(rehearsal.id, member.id);
  if (!record) {
    record = { rehearsalId: rehearsal.id, memberId: member.id, status: 'present', present: true, comment: '', reason: null, recordedBy: me(role).id };
    attendance.push(record);
  }
  Object.assign(record, patch, { recordedBy: me(role).id });
  record.present = record.status === 'present' || record.status === 'late';
  return record;
}

function removeRecord(record) {
  const index = attendance.indexOf(record);
  if (index >= 0) attendance.splice(index, 1);
}

function responsesHref(rehearsal, role) {
  return role === 'conductor' ? `#/rehearsals/${rehearsal.id}/responses` : `#/promises/${rehearsal.id}/responses`;
}

/* Context card */

function progressBar(c) {
  if (!c.total) return null;
  const done = c.recorded === c.total;
  const seg = (kind, n) => (n ? h('span', { class: `att-progress__seg att-progress__seg--${kind}`, style: `width:${(100 * n) / c.total}%` }) : null);
  return h('span', { class: 'att-context__progress att-progress' },
    h('span', {
      class: 'att-progress__track',
      role: 'progressbar',
      'aria-valuemin': '0',
      'aria-valuemax': String(c.total),
      'aria-valuenow': String(c.recorded),
      'aria-label': `${c.recorded} von ${c.total} erfasst`,
    },
    seg('present', c.present),
    seg('excused', c.excused),
    seg('absent', c.unexcused)),
    h('span', { class: ['att-progress__count', done && 'is-done'], 'aria-hidden': 'true' },
      done ? icon('circle-check') : null, done ? 'Alle erfasst' : `${c.recorded} erfasst`));
}

function dayOnly(rehearsal) {
  return rowDate(rehearsal).slice(0, -4);
}

function dayChip(rehearsal) {
  const chip = ui.tintedChip(rehearsal, { size: 'sm' });
  if (rehearsal.isToday && !rehearsal.multiDay) chip.querySelector('.date-chip__wd').textContent = 'Heute';
  return chip;
}

function contextCard(rehearsal, list, c, ctx) {
  const index = list.findIndex((r) => r.id === rehearsal.id);
  const older = index === -1 ? list[0] : list[index + 1];
  const newer = index > 0 ? list[index - 1] : null;
  const step = (target, focus) => {
    if (!target) return;
    haptic('selection');
    selectRehearsal(ctx, target.id, focus);
  };
  const live = isLive(rehearsal);
  const open = new Set(ui.undocumentedRehearsals(ctx.role).map((r) => r.id));
  const olderOpen = (index === -1 ? list : list.slice(index + 1)).filter((r) => open.has(r.id)).length;
  const newerOpen = index > 0 ? list.slice(0, index).filter((r) => open.has(r.id)).length : 0;
  const openNote = (n) => (n ? `${n === 1 ? 'eine Probe' : `${n} Proben`} nicht erfasst` : null);
  const navLabel = (base, target, n) => [target ? `${base}, ${formatDate(target.date)}` : base, openNote(n)].filter(Boolean).join(', ');
  const mark = (on) => (on ? h('span', { class: 'marker marker--warn att-context__mark', 'aria-hidden': 'true' }) : null);
  const tags = rowTags(rehearsal, { roles: false });
  return h('div', { class: ['list-group', 'att-context', rehearsal.cancelled && 'is-cancelled', c?.total && 'has-progress'] },
    h('button', {
      class: 'icon-button att-context__nav att-context__nav--prev',
      type: 'button',
      'aria-label': navLabel('Vorherige Probe', older, olderOpen),
      title: navLabel('Vorherige Probe', older, olderOpen),
      disabled: !older,
      onclick: () => step(older, '.att-context__nav--prev'),
    }, icon('chevron-left'), mark(olderOpen)),
    h('button', {
      class: 'att-context__pick',
      type: 'button',
      'aria-label': ['Probe wählen', formatDate(rehearsal.date), rehearsal.startTime, rehearsal.type !== 'Probe' ? rehearsal.type : null,
        rehearsal.groupsText, live ? 'läuft' : null, rehearsal.cancelled ? 'fällt aus' : null,
        c?.total ? `${c.recorded} von ${c.total} erfasst` : null].filter(Boolean).join(', '),
      'aria-haspopup': 'menu',
      'aria-expanded': 'false',
      'data-act': 'pick',
      onclick: (event) => openRehearsalMenu(event.currentTarget, ctx, rehearsal),
    },
    ui.isWide() ? h('span', { class: 'att-context__tile', style: ui.rowTint(rehearsal) }, dayTile(rehearsal, { today: rehearsal.isToday })) : dayChip(rehearsal),
    h('span', { class: 'att-context__text' },
      h('span', { class: 'att-context__title' },
        live ? h('span', { class: 'live-dot', 'aria-hidden': 'true' }) : null,
        h('span', { class: 'att-context__date' }, `${dayOnly(rehearsal)} · ${rehearsal.startTime}`)),
      tags ? h('span', { class: 'att-context__sub' }, tags) : null,
      c ? progressBar(c) : null),
    icon('chevron-down', { class: 'att-context__chevron' })),
    h('button', {
      class: 'icon-button att-context__nav att-context__nav--next',
      type: 'button',
      'aria-label': navLabel('Nächste Probe', newer, newerOpen),
      title: navLabel('Nächste Probe', newer, newerOpen),
      disabled: !newer,
      onclick: () => step(newer, '.att-context__nav--next'),
    }, icon('chevron-right'), mark(newerOpen)));
}

/* Filters */

function setFilter(ctx, value) {
  state.filter = value;
  state.keep = new Set();
  state.reveal = true;
  state.focus = [`[data-filter="${value}"]`];
  paint(ctx);
}

function revealSelected(row) {
  const chip = row.querySelector('.chip.is-selected');
  if (!chip) return;
  const box = row.getBoundingClientRect();
  const rect = chip.getBoundingClientRect();
  const pad = parseFloat(getComputedStyle(row).paddingLeft) || 16;
  if (rect.right > box.right - pad) row.scrollLeft += rect.right - box.right + pad;
  else if (rect.left < box.left + pad) row.scrollLeft -= box.left + pad - rect.left;
}

function filterChips(c, ctx) {
  const values = { all: c.total, absent: c.absent, deviation: c.deviation, unconfirmed: c.unconfirmed };
  const row = ui.chipRow(FILTERS.map((f) => {
    const inert = f.value !== 'all' && values[f.value] === 0 && state.filter !== f.value;
    const selected = state.filter === f.value;
    const chip = ui.chip({
      label: f.label,
      icon: f.icon,
      tone: f.tone,
      count: values[f.value],
      pressed: selected,
      role: 'radio',
      ariaLabel: `${f.label}, ${values[f.value]}`,
      className: inert ? 'is-empty' : null,
      onClick: inert ? null : () => {
        haptic('selection');
        setFilter(ctx, f.value);
      },
    });
    chip.dataset.filter = f.value;
    chip.tabIndex = selected ? 0 : -1;
    if (inert) chip.setAttribute('aria-disabled', 'true');
    return chip;
  }), { className: 'att-filters', label: 'Filter' });
  row.setAttribute('role', 'radiogroup');
  row.addEventListener('keydown', (event) => {
    const step = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[event.key];
    if (!step) return;
    const chips = [...row.querySelectorAll('.chip:not([aria-disabled="true"])')];
    const i = chips.indexOf(document.activeElement);
    if (i < 0) return;
    event.preventDefault();
    chips[(i + step + chips.length) % chips.length].click();
  });
  return row;
}

/* Rows */

function metaLine(entry, instrument) {
  const reason = entry.record?.reason ? REASONS[entry.record.reason] : null;
  if (!instrument && !reason) return null;
  return h('span', { class: 'list-row__sub att-row__meta' },
    instrument ? h('span', { class: 'att-row__instr' }, instrument) : null,
    instrument && reason ? h('span', { class: 'att-row__sep', 'aria-hidden': 'true' }) : null,
    reason ? h('span', { class: 'att-row__reason' }, icon(reason[0]), reason[1]) : null);
}

function noteLine(entry) {
  const comment = entry.record?.comment;
  const text = comment || entry.promise.note;
  if (!text) return null;
  return h('span', { class: 'att-row__note', title: text },
    icon(comment ? 'message-square-text' : 'quote'),
    h('span', { class: 'att-row__note-text' }, text));
}

function setSide(entry, rehearsal, value, ctx) {
  if (sideOf(entry.record) === value) return;
  haptic('light');
  writeRecord(rehearsal, entry.member, { status: value, reason: null, comment: entry.record?.comment ?? '' }, ctx.role);
  state.focus = `[data-member="${entry.member.id}"] .att-toggle__btn[data-value="${value}"]`;
  repaint(ctx);
}

function decorateToggle(toggle, record) {
  const glyph = record ? GLYPHS[record.status] : null;
  if (!glyph) return;
  const [side, iconName, label] = glyph;
  const btn = toggle.querySelector(`[data-value="${side}"]`);
  if (!btn) return;
  btn.querySelector('[data-lucide]')?.setAttribute('data-lucide', iconName);
  btn.setAttribute('aria-label', label);
  btn.title = label;
  toggle.dataset.status = record.status;
}

function attRow(entry, rehearsal, ctx) {
  const { member } = entry;
  const deviation = isDeviation(entry);
  const instrument = null;
  let pressTimer = null;
  let longPressed = false;
  const cancelPress = () => clearTimeout(pressTimer);
  const toggle = ui.attToggle({
    value: sideOf(entry.record),
    proposed: entry.proposal,
    name: member.displayName,
    onChange: (value) => setSide(entry, rehearsal, value, ctx),
  });
  decorateToggle(toggle, entry.record);
  toggle.addEventListener('pointerdown', () => {
    longPressed = false;
    cancelPress();
    pressTimer = setTimeout(() => {
      longPressed = true;
      haptic('medium');
      openNoteSheet(entry, rehearsal, ctx);
    }, 500);
  });
  toggle.addEventListener('contextmenu', (event) => event.preventDefault());
  toggle.addEventListener('click', (event) => {
    if (!longPressed) return;
    event.stopPropagation();
    event.preventDefault();
    longPressed = false;
  }, true);
  for (const type of ['pointerup', 'pointerleave', 'pointercancel']) toggle.addEventListener(type, cancelPress);

  return h('div', {
    class: ['list-row', 'att-row', deviation && 'is-deviation'],
    'data-member': String(member.id),
    onclick: (event) => {
      if (event.target.closest('.att-toggle')) return;
      openNoteSheet(entry, rehearsal, ctx);
    },
  },
  h('span', { class: 'list-row__lead' }, ui.avatar(member, 32)),
  h('button', {
    class: 'list-row__body att-row__open',
    type: 'button',
    'aria-haspopup': 'dialog',
    'aria-label': [member.displayName, instrument, deviation ? 'Abweichung von der Rückmeldung' : null].filter(Boolean).join(', '),
    'data-key': `o${member.id}`,
  },
  h('span', { class: 'att-row__lines' },
    h('span', { class: 'list-row__title att-row__name' },
      h('span', { class: 'att-row__name-text' }, member.displayName),
      deviation ? icon('triangle-alert', { class: 'att-row__dev' }) : null),
    metaLine(entry, instrument),
    noteLine(entry))),
  h('span', { class: 'list-row__trail' }, toggle));
}

function sectionBlock(group, entries, rehearsal, ctx, single) {
  const c = counts(group.all);
  const marks = !single && state.filter === 'all' && (c.absent || c.deviation)
    ? h('span', { class: 'att-section__marks' },
      c.absent ? h('span', { class: 'section-header__count att-section__mark att-section__mark--no', 'aria-label': `${c.absent} fehlt` }, icon('x'), String(c.absent)) : null,
      c.deviation ? h('span', { class: 'section-header__count att-section__mark att-section__mark--warn', 'aria-label': `${c.deviation} Abweichungen` }, icon('triangle-alert'), String(c.deviation)) : null)
    : null;
  const rows = ui.voiceBlocks(entries, (e) => e.member.sectionId, (entry) => attRow(entry, rehearsal, ctx));
  return h('section', { class: 'att-section', style: group.color ? `--head:${group.color}` : null, 'aria-label': group.title },
    h('h2', { class: 'section-header att-section__header jmd-head' },
      h('span', { class: 'section-header__title' }, group.title),
      marks,
      h('span', { class: 'section-header__count' }, String(entries.length))),
    h('div', { class: 'list-group att-section__rows voice-grid' }, rows));
}

/* Sheets */

function progressRing(done, total) {
  const radius = 9;
  const length = 2 * Math.PI * radius;
  const ratio = total ? Math.min(1, done / total) : 0;
  if (ratio >= 1) return h('span', { class: 'att-pick__ring is-full', 'aria-hidden': 'true' }, icon('circle-check'));
  return h('span', { class: 'att-pick__ring', 'aria-hidden': 'true' },
    html`<svg class="att-ring" viewBox="0 0 24 24"><circle class="att-ring__track" cx="12" cy="12" r="${radius}"/><circle class="att-ring__value" cx="12" cy="12" r="${radius}" stroke-dasharray="${(length * ratio).toFixed(2)} ${length.toFixed(2)}"/></svg>`);
}

function openRehearsalMenu(anchor, ctx, current) {
  const list = startedRehearsals(ctx.role);
  const info = list.map((r) => ({ r, c: counts(roster(r, ctx.role)) }));
  const open = info.filter((i) => i.c.total && i.c.recorded < i.c.total);
  const months = new Map();
  info.filter((i) => !open.includes(i)).forEach((i) => {
    const [y, m] = i.r.date.split('-').map(Number);
    const label = new Intl.DateTimeFormat('de-DE', { month: 'long', year: 'numeric' }).format(new Date(y, m - 1, 1));
    if (!months.has(label)) months.set(label, []);
    months.get(label).push(i);
  });
  const item = ({ r, c }) => ({
    lead: dayChip(r),
    label: `${dayOnly(r)} · ${ui.timeRange(r)}`,
    sub: [r.type !== 'Probe' ? r.type : null, ui.showsScope(r) ? r.groupsText : null, c.recorded === 0 ? 'nicht erfasst' : `${c.recorded} von ${c.total} erfasst`].filter(Boolean).join(' · '),
    checked: r.id === current.id,
    trail: c.recorded === 0 ? h('span', { class: 'marker marker--warn' }) : progressRing(c.recorded, c.total),
    onSelect: () => {
      if (r.id !== current.id) selectRehearsal(ctx, r.id);
    },
  });
  const items = [];
  if (open.length) items.push({ type: 'label', label: 'Nicht erfasst' }, ...open.map(item));
  for (const [label, group] of months) items.push({ type: 'label', label }, ...group.map(item));
  openMenu(anchor, items, { className: 'att-menu', label: 'Probe wählen', minWidth: 320 });
}

function openNoteSheet(entry, rehearsal, ctx) {
  const { member, promise } = entry;
  const record = recordOf(rehearsal.id, member.id);
  const draft = {
    side: sideOf(record) ?? proposalOf(promise),
    picks: new Set(),
  };
  if (record?.status === 'late' || record?.status === 'excused') draft.picks.add(record.status);
  if (record?.reason) draft.picks.add(record.reason);
  const comment = record?.comment ?? '';

  const textarea = h('textarea', {
    class: 'textarea',
    rows: '3',
    maxlength: '500',
    placeholder: 'Notiz',
    'aria-label': 'Notiz',
  });
  textarea.value = comment;
  const grow = () => {
    textarea.style.height = 'auto';
    textarea.style.height = `${Math.min(200, Math.max(88, textarea.scrollHeight))}px`;
  };
  textarea.addEventListener('input', grow);

  const choice = h('div', { class: 'rsvp att-note__choice', role: 'group', 'aria-label': 'Anwesenheit' });

  const setDraftSide = (value) => {
    draft.side = value;
    NOTE_CHIPS.filter((chip) => chip.side !== value).forEach((chip) => draft.picks.delete(chip.value));
    choice.classList.remove('is-missing');
  };

  const sides = [['present', 'yes', 'check', 'Anwesend'], ['absent', 'no', 'x', 'Fehlt']];
  const paintChoice = () => {
    choice.replaceChildren(...sides.map(([value, tone, iconName, label]) => h('div', {
      class: ['att-note__side', draft.side === value && 'is-active'],
      role: 'group',
      'aria-label': label,
    },
    h('button', {
      class: ['rsvp__btn', `rsvp__btn--${tone}`],
      type: 'button',
      'aria-pressed': String(draft.side === value),
      onclick: () => {
        if (draft.side === value) return;
        haptic('selection');
        setDraftSide(value);
        paintChoice();
      },
    }, icon(iconName), label),
    NOTE_CHIPS.filter((chip) => chip.side === value).map((chip) => ui.chip({
      label: chip.label,
      icon: chip.icon,
      tone: chip.tone,
      pressed: draft.picks.has(chip.value),
      className: 'att-note__chip',
      onClick: () => {
        haptic('selection');
        if (draft.picks.has(chip.value)) {
          draft.picks.delete(chip.value);
        } else {
          if (draft.side !== chip.side) setDraftSide(chip.side);
          draft.picks.add(chip.value);
        }
        paintChoice();
      },
    })))));
    choice.classList.toggle('has-choice', Boolean(draft.side));
    refreshIcons(choice);
  };
  paintChoice();

  const answerKind = { attending: 'yes', not_attending: 'no', pending: 'open' }[promise.status] ?? 'open';
  const instrument = getSection(member.sectionId)?.name;
  const content = h('div', { class: 'att-note' },
    h('div', { class: 'att-note__who' },
      ui.avatar(member, 40),
      h('span', { class: 'att-note__instr' }, instrument),
      ui.statusPill(answerKind)),
    promise.note && !comment.toLowerCase().includes(promise.note.toLowerCase())
      ? h('p', { class: 'att-note__quote' }, icon('quote'), h('span', null, promise.note)) : null,
    choice,
    textarea);

  const actions = [{
    label: 'Speichern',
    variant: 'primary',
    onClick: () => {
      if (!draft.side) {
        haptic('warning');
        choice.classList.remove('is-missing');
        void choice.offsetWidth;
        choice.classList.add('is-missing');
        return false;
      }
      const status = draft.side === 'present'
        ? (draft.picks.has('late') ? 'late' : 'present')
        : (draft.picks.has('excused') ? 'excused' : 'absent');
      const reason = NOTE_CHIPS.find((chip) => chip.kind === 'reason' && chip.side === draft.side && draft.picks.has(chip.value))?.value ?? null;
      writeRecord(rehearsal, member, { status, reason, comment: textarea.value.trim() }, ctx.role);
      haptic('light');
      repaint(ctx);
      toast(html`${icon('circle-check')} Gespeichert`);
      return true;
    },
  }];
  if (record) {
    actions.push({
      label: 'Zurücksetzen',
      variant: 'destructive',
      icon: 'rotate-ccw',
      onClick: () => {
        const snapshot = { ...record };
        removeRecord(record);
        haptic('light');
        repaint(ctx);
        toast(html`${icon('rotate-ccw')} Zurückgesetzt`, {
          action: {
            label: 'Rückgängig',
            onClick: () => {
              attendance.push(snapshot);
              repaint(ctx);
            },
          },
        });
      },
    });
  }
  const sheet = openSheet({
    title: member.displayName,
    content,
    actions,
    className: 'att-note-sheet',
    onClose: () => queueMicrotask(() => {
      if (!document.activeElement || document.activeElement === document.body) focusFirst([`[data-key="o${member.id}"]`, '[data-act="pick"]']);
    }),
  });
  refreshIcons(sheet.el);
  requestAnimationFrame(grow);
}

/* Bulk confirm */

function confirmAll(rehearsal, ctx) {
  const entries = roster(rehearsal, ctx.role).filter((e) => e.proposal);
  if (!entries.length) return;
  const created = entries.map((e) => writeRecord(rehearsal, e.member, { status: e.proposal, reason: null, comment: '' }, ctx.role));
  haptic('success');

  const finish = () => {
    bulkTimers = [];
    state.focus = ['[data-act="bar"]', '.att-row .att-row__open'];
    repaint(ctx);
  };
  const proposed = [...document.querySelectorAll('.att-screen .att-toggle__btn.is-proposed')];
  bulkTimers.forEach(clearTimeout);
  bulkTimers = [];
  if (reducedMotion.matches || document.documentElement.dataset.motion === 'reduced' || !proposed.length) {
    finish();
  } else {
    const step = Math.min(12, 300 / proposed.length);
    proposed.forEach((btn, i) => {
      bulkTimers.push(setTimeout(() => {
        btn.classList.remove('is-proposed');
        btn.setAttribute('aria-checked', 'true');
      }, i * step));
    });
    bulkTimers.push(setTimeout(finish, Math.min(300, proposed.length * step) + 150));
  }

  const present = created.filter((r) => r.present).length;
  const absent = created.length - present;
  toast(html`${icon('circle-check')} Gespeichert · <span class="att-toast__n att-toast__n--yes">${icon('check')}${present}</span>${absent ? html`<span class="att-toast__n att-toast__n--no">${icon('x')}${absent}</span>` : ''}`, {
    action: {
      label: 'Rückgängig',
      onClick: () => {
        bulkTimers.forEach(clearTimeout);
        bulkTimers = [];
        created.forEach(removeRecord);
        repaint(ctx);
      },
    },
  });
}

/* Screen */

function allowed(ctx) {
  return ui.guard(ctx, ['leader', 'conductor']);
}

function selectRehearsal(ctx, id, focus = '[data-act="pick"]') {
  state.rehearsalId = id;
  state.keep = new Set();
  state.focus = [focus, '[data-act="pick"]'];
  navigate(`/attendance?rehearsal=${id}`, { replace: true, direction: 'none' });
}

function resolveRehearsal(ctx, list) {
  const requested = Number(ctx.query?.rehearsal ?? NaN);
  if (requested) {
    const r = rehearsals.find((x) => x.id === requested);
    if (r) return r;
  }
  if (state.rehearsalId && list.some((r) => r.id === state.rehearsalId)) {
    return list.find((r) => r.id === state.rehearsalId);
  }
  return list[0] ?? null;
}

function absencesLink() {
  return { icon: 'calendar-x-2', label: 'Fehlzeiten', onClick: () => navigate('/attendance/table') };
}

function detailPath(r, ctx) {
  const past = r.isPast && !r.isToday;
  if (ctx.role !== 'conductor') return `/promises/${r.id}`;
  return ui.isWide() ? `/rehearsals/${r.id}${past ? '?seg=past' : ''}` : `/rehearsals?${past ? 'seg=past&' : ''}probe=${r.id}`;
}

function rehearsalLinks(ctx) {
  const r = resolveRehearsal(ctx, startedRehearsals(ctx.role));
  if (!r) return [];
  return [
    { icon: 'calendar-search', label: 'Zur Probe', onClick: () => navigate(detailPath(r, ctx)) },
    ctx.role === 'conductor' && ui.isWide() ? { icon: 'pencil', label: 'Bearbeiten', onClick: () => navigate(`/rehearsals?edit=${r.id}`) } : null,
  ].filter(Boolean);
}

export function placeBarAction() {
  const button = mounted?.barButton;
  if (!button) return;
  const host = mounted.root.querySelector(ui.isWide() ? '.att-acts' : '.att-sticky');
  if (host && button.parentElement !== host) host.append(button);
}

function header(ctx) {
  if (!mounted) return;
  mounted.undocumented = ui.undocumentedCount(ctx.role);
  if (mounted.root.classList.contains('att-fixed')) return;
  renderTeamHeader(ctx, '/attendance', { root: mounted.root, sticky: false, actions: ui.isWide() ? [] : screenActions(ctx) });
  placeBarAction();
}

function screenActions(ctx) {
  return [...rehearsalLinks(ctx), absencesLink()];
}

function sideActions(ctx) {
  const links = [...rehearsalLinks(ctx), absencesLink()];
  return h('div', { class: 'att-acts' }, links.map((link) => h('button', {
    class: 'button button--sm button--plain att-acts__link',
    type: 'button',
    onclick: link.onClick,
  }, icon(link.icon), h('span', { class: 'button__label' }, link.label))));
}

function seatChart(rehearsal, ctx) {
  const scope = leaderSection(ctx.role);
  const seats = attendanceSeats(rehearsal, { scope });
  if (mounted.chart?.rid === rehearsal.id) {
    const { el } = mounted.chart;
    requestAnimationFrame(() => requestAnimationFrame(() => el.update(seats)));
    return el;
  }
  const el = seatingChart(seats, { size: 'medium', kind: 'attendance', emphasis: scope, ownId: scope ? me(ctx.role).id : null });
  el.classList.add('att-seats');
  mounted.chart = { rid: rehearsal.id, el };
  return el;
}

function refreshCounts(ctx) {
  if (TABS[ctx.role]) renderTabbar(TABS[ctx.role]);
  if (mounted && mounted.undocumented !== ui.undocumentedCount(ctx.role)) header(ctx);
}

function repaint(ctx) {
  paint(ctx);
  refreshCounts(ctx);
}

function nextOpen(ctx, rehearsal) {
  if (mounted?.root.classList.contains('att-fixed')) return null;
  return ui.undocumentedRehearsals(ctx.role).find((r) => r.id !== rehearsal.id) ?? null;
}

function setBarAction(ctx, rehearsal, proposals, next = null) {
  if (!proposals && !next) {
    mounted.barButton?.remove();
    mounted.barButton = null;
    mounted.barKey = null;
    return;
  }
  if (!mounted.barButton) {
    mounted.barButton = h('button', { class: ['button', 'button--primary', 'att-bar__button', ui.isWide() && 'desk-bar__primary'], type: 'button', 'data-act': 'bar' });
  }
  placeBarAction();
  const key = proposals ? `save${proposals}` : `next${next.id}`;
  if (mounted.barKey !== key) {
    mounted.barKey = key;
    mounted.barButton.replaceChildren(...h('div', {},
      icon(proposals ? 'check-check' : 'clipboard-pen'),
      h('span', { class: 'att-bar__label' }, proposals
        ? `${proposals} ${proposals === 1 ? 'Vorschlag' : 'Vorschläge'} übernehmen`
        : `${formatDate(next.date)} erfassen`)).childNodes);
    refreshIcons(mounted.barButton);
  }
  mounted.barButton.onclick = proposals
    ? () => confirmAll(rehearsal, ctx)
    : () => {
      haptic('selection');
      mounted.content.scrollTop = 0;
      selectRehearsal(ctx, next.id);
    };
}

function syncStuck() {
  const sticky = mounted?.root.querySelector('.sticky-bar.att-sticky');
  if (!sticky) return;
  const edge = (mounted.root.querySelector(':scope > .desk-bar') ?? document.getElementById('appHeader'))?.getBoundingClientRect().bottom ?? 0;
  const stuck = mounted.content.scrollTop > 0 && sticky.getBoundingClientRect().top <= edge + 0.5;
  if (stuck === sticky.classList.contains('is-stuck')) return;
  const height = sticky.offsetHeight;
  sticky.classList.toggle('is-stuck', stuck);
  sticky.style.marginBottom = stuck ? `${height - sticky.offsetHeight}px` : '';
}

function emptyActions(rehearsal, list, ctx) {
  const actions = [];
  if (!rehearsal.cancelled && rehearsal.start > NOW) {
    actions.push(h('a', { class: 'button button--tinted', href: responsesHref(rehearsal, ctx.role) }, icon('message-circle-reply'), h('span', { class: 'button__label' }, 'Rückmeldungen')));
  }
  if (list[0]) {
    actions.push(h('button', { class: 'button button--plain', type: 'button', onclick: () => selectRehearsal(ctx, list[0].id) }, icon('history'), h('span', { class: 'button__label' }, 'Zur letzten Probe')));
  }
  return actions.length ? h('div', { class: 'att-empty__actions' }, actions) : null;
}

function paint(ctx) {
  if (!mounted) return;
  const { content, root } = mounted;
  const top = content.scrollTop;
  const before = root.querySelector('.sticky-bar.att-sticky')?.offsetHeight ?? 0;
  drawBody(ctx);
  const after = root.querySelector('.sticky-bar.att-sticky')?.offsetHeight ?? 0;
  const target = before && top > 0 ? Math.max(0, top + after - before) : top;
  if (content.scrollTop !== target) {
    content.scrollTop = target;
    syncStuck();
  }
}

function focusFirst(selectors) {
  for (const selector of [selectors].flat()) {
    const el = mounted?.root.querySelector(selector);
    if (el && !el.disabled) {
      el.focus({ preventScroll: true });
      return;
    }
  }
}

function drawBody(ctx) {
  const { body } = mounted;
  const chipScroll = body.querySelector('.att-filters')?.scrollLeft ?? 0;
  const list = startedRehearsals(ctx.role);
  const rehearsal = resolveRehearsal(ctx, list);
  body.replaceChildren();

  if (!rehearsal) {
    setBarAction(ctx, null, 0);
    body.append(ui.emptyState({ icon: 'clipboard-check', tone: 'blue', title: 'Keine Probe zum Erfassen' }));
    refreshIcons(body);
    return;
  }

  const scope = leaderSection(ctx.role);
  const scopeOk = !scope || rehearsal.instrumentIds.includes(scope);
  if (rehearsal.cancelled || rehearsal.start > NOW || !scopeOk) {
    setBarAction(ctx, null, 0);
    const [iconName, tone, title] = rehearsal.cancelled ? ['ban', 'rose', 'Probe fällt aus']
      : !scopeOk ? ['users-round', 'violet', 'Nicht eingeladen'] : ['calendar-clock', 'blue', 'Noch nicht begonnen'];
    const empty = ui.emptyState({ icon: iconName, tone, title });
    empty.classList.add('att-empty');
    const extra = emptyActions(rehearsal, list, ctx);
    if (extra) empty.append(extra);
    body.append(h('div', { class: 'sticky-bar att-sticky' }, contextCard(rehearsal, list, null, ctx)), empty);
    refreshIcons(body);
    syncStuck();
    if (state.focus) focusFirst(state.focus);
    state.focus = null;
    return;
  }

  const entries = roster(rehearsal, ctx.role);
  const c = counts(entries);
  const chips = filterChips(c, ctx);
  const chart = seatChart(rehearsal, ctx);
  let main = body;
  if (ui.isWide()) {
    main = h('div', { class: 'att-main' }, h('div', { class: 'sticky-bar att-sticky' }, chips));
    body.append(h('div', { class: 'att-layout' },
      h('div', { class: 'att-side' },
        h('div', { class: 'att-sticky att-pick' }, contextCard(rehearsal, list, c, ctx)),
        h('div', { class: 'att-board' }, chart, sideActions(ctx))),
      main));
  } else {
    body.append(h('div', { class: 'sticky-bar att-sticky' }, contextCard(rehearsal, list, c, ctx)), chart, chips);
  }
  chips.scrollLeft = chipScroll;

  const visible = entries.filter((e) => matches(e, state.filter) || state.keep.has(e.member.id));
  state.keep = state.filter === 'all' ? new Set() : new Set(visible.map((e) => e.member.id));

  const groups = new Map();
  for (const entry of entries) {
    const group = scope ? getSection(scope) : groupOf(entry.member);
    const key = group?.id ?? 'other';
    if (!groups.has(key)) groups.set(key, { title: group?.name ?? 'Weitere', color: group ? ui.registerColor(group.id) : null, all: [], shown: [] });
    const bucket = groups.get(key);
    bucket.all.push(entry);
    if (visible.includes(entry)) bucket.shown.push(entry);
  }
  const blocks = [...groups.values()].filter((g) => g.shown.length);

  if (!blocks.length) {
    const [iconName, tone, title] = EMPTY[state.filter] ?? ['users-round', 'blue', 'Niemand eingeladen'];
    main.append(ui.emptyState({ icon: iconName, tone, title }));
  } else {
    main.append(h('div', { class: 'att-sections' },
      blocks.map((g) => sectionBlock(g, g.shown, rehearsal, ctx, groups.size === 1))));
  }

  setBarAction(ctx, rehearsal, c.proposals, c.proposals || c.unconfirmed ? null : nextOpen(ctx, rehearsal));
  refreshIcons(body);
  syncStuck();
  if (state.reveal) revealSelected(chips);
  state.reveal = false;
  if (state.focus) {
    focusFirst(state.focus);
    state.focus = null;
  }
}

registerScreen('attendance', {
  title: 'Anwesenheit',
  tab: '/team',
  placeAction: placeBarAction,
  render(root, params, ctx) {
    if (!allowed(ctx)) return;
    const requested = Number(ctx.query?.rehearsal ?? NaN);
    if (requested && requested !== state.rehearsalId) {
      state.rehearsalId = requested;
      state.filter = 'all';
      state.keep = new Set();
    }
    root.classList.add('att-screen');
    const body = h('div', { class: 'att-body' });
    root.append(body);
    const content = document.getElementById('appContent');
    mounted = { root, body, undocumented: null, content };
    content.addEventListener('scroll', syncStuck, { passive: true });
    header(ctx);
    paint(ctx);
  },
  onLeave() {
    bulkTimers.forEach(clearTimeout);
    bulkTimers = [];
    mounted?.content.removeEventListener('scroll', syncStuck);
    mounted = null;
  },
});

/* Fehlzeiten */

const RECENT_COLUMNS = 10;

function tableColumns(role, period) {
  const started = startedRehearsals(role);
  const documented = started.filter((r) => attendance.some((a) => a.rehearsalId === r.id));
  return (period === 'recent' ? documented.slice(0, RECENT_COLUMNS) : started).slice().reverse();
}

function tablePeople(role, columns) {
  const scope = leaderSection(role);
  return [...new Map(promises
    .filter((p) => columns.some((r) => r.id === p.rehearsalId))
    .map((p) => getMember(p.memberId))
    .filter((m) => m && (!scope || m.sectionId === scope))
    .map((m) => [m.id, m])).values()]
    .sort((a, b) => (getSection(a.sectionId)?.order ?? 99) - (getSection(b.sectionId)?.order ?? 99) || a.displayName.localeCompare(b.displayName, 'de'));
}

const CELL = {
  yes: ['check', 'Anwesend'],
  late: ['clock', 'Verspätet'],
  no: ['x', 'Fehlt'],
  excused: ['circle-minus', 'Entschuldigt'],
  open: [null, 'Nicht erfasst'],
  none: [null, 'Nicht betroffen'],
};

const CELL_OF_STATUS = { present: 'yes', late: 'late', absent: 'no', excused: 'excused' };

const CELL_RANK = { no: 5, excused: 4, late: 3, open: 2, yes: 1, none: 0 };

function cellKind(r, m) {
  if (!promises.some((p) => p.rehearsalId === r.id && p.memberId === m.id)) return 'none';
  const rec = recordOf(r.id, m.id);
  return rec ? CELL_OF_STATUS[rec.status] ?? 'yes' : 'open';
}

function sortArrow(key) {
  return state.sort?.key === String(key) ? icon(state.sort.dir === 'asc' ? 'arrow-up' : 'arrow-down', { class: 'att-matrix__arrow' }) : null;
}

function ariaSort(key) {
  return state.sort?.key === String(key) ? (state.sort.dir === 'asc' ? 'ascending' : 'descending') : 'none';
}

function columnKind(r) {
  const badge = ui.typeBadge(r);
  badge?.classList.add('att-matrix__kind');
  return badge;
}

function columnHead(r, documented) {
  const [, m, d] = r.date.split('-').map(Number);
  return h('th', {
    scope: 'col',
    class: ['att-matrix__col', !documented && 'is-open'],
    'aria-sort': ariaSort(r.id),
    title: [formatDate(r.date), r.startTime, r.type !== 'Probe' ? r.type : null, ui.showsScope(r) ? r.groupsText : null, documented ? null : 'nicht erfasst'].filter(Boolean).join(' · '),
  },
  h('button', { class: 'att-matrix__sort', type: 'button', 'data-sort': String(r.id) },
    weekdayAnchor(r),
    h('span', { class: 'att-matrix__date' }, `${d}.${m}.`, h('span', { class: 'att-matrix__time' }, r.startTime)),
    columnKind(r),
    ui.showsScope(r) ? h('span', { class: 'att-matrix__cast' }, r.groupsText) : null,
    h('span', { class: 'att-matrix__flag', 'aria-hidden': 'true' },
      documented ? null : h('span', { class: 'marker marker--warn' })),
    sortArrow(r.id)));
}

function tableCell(r, m) {
  const kind = cellKind(r, m);
  const [iconName, label] = CELL[kind];
  return h('td', { class: `att-matrix__cell att-matrix__cell--${kind}`, 'aria-label': label, title: label },
    iconName ? icon(iconName) : kind === 'open' ? h('span', { class: 'marker marker--warn', 'aria-hidden': 'true' }) : h('span', { class: 'att-matrix__none', 'aria-hidden': 'true' }, '–'));
}

function sortRows(rows, columns) {
  if (!state.sort) return rows;
  const { key, dir } = state.sort;
  const sign = dir === 'asc' ? 1 : -1;
  const column = columns.find((r) => String(r.id) === key);
  if (!column && key !== 'name' && key !== 'total') return rows;
  const value = (row) => (key === 'total' ? row.missed : CELL_RANK[cellKind(column, row.m)]);
  return rows.map((row, i) => ({ row, i })).sort((a, b) => {
    const diff = key === 'name' ? a.row.m.displayName.localeCompare(b.row.m.displayName, 'de') : value(a.row) - value(b.row);
    return sign * diff || a.i - b.i;
  }).map(({ row }) => row);
}

function toggleSort(key) {
  const same = state.sort?.key === key;
  state.sort = { key, dir: same ? (state.sort.dir === 'asc' ? 'desc' : 'asc') : key === 'name' ? 'asc' : 'desc' };
}

function watchScroll(scroller, card) {
  const more = card.querySelector('.att-table__more');
  const sync = () => {
    const over = scroller.scrollWidth > scroller.clientWidth + 1;
    if (more) {
      const nameEdge = scroller.querySelector('thead .att-matrix__name')?.getBoundingClientRect().right ?? 0;
      const hidden = [...scroller.querySelectorAll('thead .att-matrix__col')].filter((col) => col.getBoundingClientRect().left < nameEdge - 8).length;
      more.hidden = !hidden;
      more.querySelector('.att-table__more-text').textContent = `${hidden} ältere`;
    }
    card.classList.toggle('is-overflowing', over);
    card.classList.toggle('is-at-end', over && scroller.scrollLeft + scroller.clientWidth >= scroller.scrollWidth - 2);
    card.classList.toggle('is-scrolled-x', scroller.scrollLeft > 0);
    card.classList.toggle('is-scrolled-y', scroller.scrollTop > 0);
  };
  const measure = () => {
    const name = scroller.querySelector('thead .att-matrix__name');
    const total = scroller.querySelector('thead .att-matrix__total');
    card.style.setProperty('--att-name-w', `${name?.offsetWidth ?? 0}px`);
    card.style.setProperty('--att-total-w', `${(total?.offsetWidth ?? 0) + scroller.offsetWidth - scroller.clientWidth}px`);
    card.style.setProperty('--att-head-h', `${scroller.querySelector('thead')?.offsetHeight ?? 0}px`);
    sync();
  };
  scroller.addEventListener('scroll', sync, { passive: true });
  if ('ResizeObserver' in window) new ResizeObserver(measure).observe(scroller);
  requestAnimationFrame(measure);
}

function printTable() {
  let opened = false;
  const mark = () => {
    opened = true;
  };
  window.addEventListener('beforeprint', mark, { once: true });
  window.print();
  setTimeout(() => {
    window.removeEventListener('beforeprint', mark);
    if (opened) return;
    toast('Druckdialog blockiert', {
      icon: 'printer',
      action: {
        label: 'Druckvorschau öffnen',
        onClick: () => window.open(`${location.pathname}${location.search}#/attendance/table?print=1`, '_blank', 'noopener'),
      },
    });
  }, 300);
}

function tableLegend() {
  return h('ul', { class: 'att-print__legend' },
    [['yes', 'check', 'Anwesend'], ['late', 'clock', 'Verspätet'], ['no', 'x', 'Fehlt'], ['excused', 'circle-minus', 'Entschuldigt']]
      .map(([kind, iconName, label]) => h('li', { class: `att-print__key att-print__key--${kind}` }, icon(iconName), label)),
    h('li', { class: 'att-print__key att-print__key--open' }, h('span', { class: 'marker marker--warn', 'aria-hidden': 'true' }), 'Nicht erfasst'),
    h('li', { class: 'att-print__key att-print__key--none' }, h('span', { class: 'att-matrix__none', 'aria-hidden': 'true' }, '–'), 'Nicht betroffen'));
}

registerScreen('attendance-table', {
  title: 'Fehlzeiten',
  tab: '/team',
  render(root, params, ctx) {
    if (!allowed(ctx)) return;
    const wide = ui.isWide();
    root.classList.add('screen--wide', 'att-table-screen');
    setHeader({
      title: 'Fehlzeiten',
      actions: wide ? [] : [{ icon: 'printer', label: 'Drucken', onClick: printTable }],
    });

    const host = h('div', { class: 'att-table' });
    const stepperText = h('span', { class: 'att-stepper__text' });
    const stepperLive = h('span', { class: 'visually-hidden', 'aria-live': 'polite' });
    const less = h('button', { class: 'icon-button att-stepper__btn', type: 'button', 'aria-label': 'Verringern' }, icon('minus'));
    const more = h('button', { class: 'icon-button att-stepper__btn', type: 'button', 'aria-label': 'Erhöhen' }, icon('plus'));
    const printMeta = h('p', { class: 'att-print__meta' });
    const printScope = h('span', { class: 'att-print__line' });
    printMeta.append(h('span', { class: 'att-print__line' }, ensemble.name), printScope);

    const paintTable = () => {
      const columns = tableColumns(ctx.role, state.period);
      const documented = new Set(columns.filter((r) => attendance.some((a) => a.rehearsalId === r.id)).map((r) => r.id));
      const rows = tablePeople(ctx.role, columns).map((m) => {
        const missed = columns.filter((r) => {
          const rec = recordOf(r.id, m.id);
          return rec && !rec.present;
        }).length;
        return { m, missed };
      }).filter((row) => row.missed >= state.min);
      const threshold = `Ab ${state.min} ${state.min === 1 ? 'Fehlzeit' : 'Fehlzeiten'}`;
      stepperText.textContent = threshold;
      stepperLive.textContent = threshold;
      printScope.textContent = `${state.period === 'recent' ? `Letzte ${columns.length} Proben` : ensemble.season}, ${threshold}`;
      less.disabled = state.min <= 1;
      more.disabled = state.min >= 10;

      const previous = host.querySelector('.att-table__scroll');
      const fromEnd = previous ? previous.scrollWidth - previous.clientWidth - previous.scrollLeft : 0;
      const fromTop = previous?.scrollTop ?? 0;
      if (!rows.length) {
        host.replaceChildren(ui.emptyState({ icon: 'circle-check', tone: 'green', title: 'Keine Fehlzeiten' }));
        refreshIcons(host);
        return;
      }
      const scroller = h('div', { class: 'att-table__scroll' },
        h('table', { class: 'att-matrix', style: `--att-cols:${columns.length}` },
          h('thead', null, h('tr', null,
            h('th', { scope: 'col', class: 'att-matrix__name', 'aria-sort': ariaSort('name') },
              h('button', { class: 'att-matrix__sort att-matrix__sort--name', type: 'button', 'data-sort': 'name' }, 'Name', sortArrow('name'))),
            columns.map((r) => columnHead(r, documented.has(r.id))),
            h('th', { scope: 'col', class: 'att-matrix__total', title: 'Fehlzeiten', 'aria-sort': ariaSort('total') },
              h('button', { class: 'att-matrix__sort', type: 'button', 'data-sort': 'total' }, icon('calendar-x-2'), h('span', { class: 'att-matrix__total-label' }, 'Fehlzeiten'), sortArrow('total'))))),
          h('tbody', null, sortRows(rows, columns).map(({ m, missed }) => h('tr', null,
            h('th', { scope: 'row', class: 'att-matrix__name' }, h('a', { class: 'att-matrix__member', href: `#/members/${m.id}` }, m.displayName)),
            columns.map((r) => tableCell(r, m)),
            h('td', { class: 'att-matrix__total' }, String(missed)))))));
      const card = h('div', { class: 'list-group att-table__card' }, scroller,
        h('button', { class: 'att-table__more', type: 'button', hidden: true, onclick: () => scroller.scrollTo({ left: 0, behavior: 'smooth' }) }, icon('chevron-left'), h('span', { class: 'att-table__more-text' })),
        h('span', { class: 'att-table__fade att-table__fade--start', 'aria-hidden': 'true' }),
        h('span', { class: 'att-table__fade att-table__fade--end', 'aria-hidden': 'true' }));
      host.replaceChildren(card);
      refreshIcons(host);
      scroller.scrollTop = fromTop;
      const toEnd = () => {
        scroller.scrollLeft = scroller.scrollWidth - scroller.clientWidth - fromEnd;
      };
      toEnd();
      requestAnimationFrame(toEnd);
      watchScroll(scroller, card);
    };

    host.addEventListener('click', (event) => {
      const button = event.target.closest('.att-matrix__sort');
      if (!button) return;
      haptic('selection');
      toggleSort(button.dataset.sort);
      paintTable();
      host.querySelector(`[data-sort="${button.dataset.sort}"]`)?.focus({ preventScroll: true });
    });

    less.addEventListener('click', () => {
      state.min = Math.max(1, state.min - 1);
      haptic('selection');
      paintTable();
    });
    more.addEventListener('click', () => {
      state.min = Math.min(10, state.min + 1);
      haptic('selection');
      paintTable();
    });

    const period = ui.segmented([
      { value: 'recent', label: `Letzte ${tableColumns(ctx.role, 'recent').length}` },
      { value: 'season', label: 'Saison' },
    ], state.period, (value) => {
      state.period = value;
      paintTable();
    });
    period.setAttribute('aria-label', 'Zeitraum');

    const stepper = h('div', { class: 'att-stepper', role: 'group', 'aria-label': 'Mindestanzahl Fehlzeiten' },
      less,
      h('span', { class: 'att-stepper__value', 'aria-hidden': 'true' }, stepperText),
      more,
      stepperLive);
    root.append(
      h('header', { class: 'att-print' },
        h('h1', { class: 'att-print__title' }, 'Fehlzeiten'),
        printMeta),
      ...(wide ? [] : [h('div', { class: 'att-table__controls' }, period, stepper)]),
      host,
      tableLegend());
    if (wide) deskBar(root, { title: 'Fehlzeiten', showBack: true, items: [period, stepper], actions: [{ icon: 'printer', label: 'Drucken', onClick: printTable }] });
    paintTable();
    if (ctx.query?.print === '1') setTimeout(() => window.print(), 400);
  },
});

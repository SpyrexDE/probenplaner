/* Proben (Leitung): list, create menu and editor column */

import { openPlaceSheet } from '../places.js';
import { openMenu } from '../menu.js';
import * as ui from '../ui.js';
import { ensembleChip } from '../ensemble-menu.js';
import {
  registerScreen, setHeader, navigate, back, openSheet, closeSheet, toast, h, html, icon, refreshIcons, haptic, currentRoute, rerender,
} from '../app.js';
import { probenRow, rowItem, cancelledSlot, rowsMode, expandRow, collapseRow, toggleRow as toggleInline, rowPanel, rowTimeText } from '../rsvp.js';
import { seatSlot } from '../seating.js';
import {
  closeFilter, createActions, filterButton, filterCount, filterRow, openFilterSheet, passes, resetFilters, resetSelection, selection, selectControls, toggleFilter,
} from './rehearsals-tools.js';
import {
  NOW,
  TODAY,
  rehearsals,
  promises,
  members,
  sections,
  ensemble,
  colors,
  locations,
  rehearsalTypes,
  documentedRehearsalIds,
  formatDate,
  formatDateLong,
  getRehearsal,
  getAttendance,
  getLocation,
  getUpcoming,
  getPast,
  appliesTo,
  compressGroups,
  defaultBesetzung,
} from '../data.js';

const MONTHS_LONG = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
const NO_COLOR = '#e5e7eb';
const STANDARD_TYPES = ['Probe', 'Registerprobe', 'Generalprobe', 'Konzert', 'Probenwochenende', 'Konzertreise', 'Vorstandssitzung'];
const OTHER_PLACE = '__other';
const CUSTOM_TYPE = '__custom';
const wideQuery = matchMedia('(min-width: 840px)');
const finePointer = matchMedia('(pointer: fine)');
const editorModule = import('./rehearsal-edit.js').catch(() => ({}));
let editorApi = null;
editorModule.then((mod) => { editorApi = mod; });
const detailModule = import('./promise-detail.js').catch(() => ({}));
let detailApi = null;
detailModule.then((mod) => { detailApi = mod; });

const state = { search: false, query: '', listScroll: 0, flash: null, cleanup: [], expandedId: null, openOnly: false };

/* Date helpers */

const pad = (n) => String(n).padStart(2, '0');
const utc = (iso) => {
  const [y, m, d] = iso.slice(0, 10).split('-').map(Number);
  return Date.UTC(y, m - 1, d);
};
export const addDays = (iso, n) => {
  const t = new Date(utc(iso) + n * 86400000);
  return `${t.getUTCFullYear()}-${pad(t.getUTCMonth() + 1)}-${pad(t.getUTCDate())}`;
};
export const weekday = (iso) => new Date(utc(iso)).getUTCDay();
const dayNum = (iso) => Number(iso.slice(8, 10));
const shortDate = (iso) => `${dayNum(iso)}.${Number(iso.slice(5, 7))}.`;
const dayDiff = (iso) => Math.round((utc(iso) - utc(TODAY)) / 86400000);

export function monthLabel(iso, { year = false } = {}) {
  const month = MONTHS_LONG[Number(iso.slice(5, 7)) - 1];
  return year || iso.slice(0, 4) !== TODAY.slice(0, 4) ? `${month} ${iso.slice(0, 4)}` : month;
}

/* Data helpers */

const isDocumented = (r) => documentedRehearsalIds.includes(r.id) || getAttendance(r.id).length > 0;
const instrumentIds = () => sections.filter((s) => s.kind === 'instrument').map((s) => s.id);
const hasColor = (value) => Boolean(value) && value.toLowerCase() !== NO_COLOR;

const isOngoing = (r) => r.start <= NOW && NOW < r.end;
const editHref = (id) => `#/rehearsals?edit=${id}`;

const detailHref = (r) => `#/rehearsals/${r.id}${r.isPast && !r.isToday ? '?seg=past' : ''}`;
function mostCommon(values, fallback) {
  const counts = new Map();
  let best = fallback;
  let max = 0;
  for (const value of values) {
    const n = (counts.get(value) ?? 0) + 1;
    counts.set(value, n);
    if (n > max) {
      best = value;
      max = n;
    }
  }
  return best;
}

export function templateDefaults() {
  const regular = rehearsals.filter((r) => r.type === 'Probe' && !r.cancelled && !r.multiDay);
  return {
    weekday: mostCommon(regular.map((r) => weekday(r.date)), 2),
    type: 'Probe',
    startTime: mostCommon(regular.map((r) => r.startTime), '19:00'),
    endTime: mostCommon(regular.map((r) => r.endTime), '21:30'),
    location: mostCommon(regular.map((r) => r.location), ensemble.defaultLocation),
    ...defaultBesetzung(),
    color: NO_COLOR,
  };
}

export const takenBy = (date) => rehearsals.find((r) => r.date <= date && date <= r.endDate) ?? null;

export function nextFreeDate(from, wd) {
  let d = from;
  for (let i = 0; i < 400 && (weekday(d) !== wd || takenBy(d)); i += 1) d = addDays(d, 1);
  return d;
}

function newDefaults() {
  const t = templateDefaults();
  return { ...t, date: nextFreeDate(addDays(TODAY, 1), t.weekday) };
}

export function buildRehearsal(fields = {}, id = Math.max(...rehearsals.map((r) => r.id)) + 1) {
  const date = fields.date;
  const endDate = fields.endDate ?? date;
  const startTime = fields.startTime ?? '19:00';
  const endTime = fields.endTime ?? '21:30';
  const location = fields.location ?? ensemble.defaultLocation;
  return {
    id,
    start: `${date}T${startTime}:00`,
    end: `${endDate}T${endTime}:00`,
    date,
    endDate,
    startTime,
    endTime,
    multiDay: endDate !== date,
    type: fields.type ?? 'Probe',
    location,
    room: fields.room ?? null,
    address: getLocation(location)?.address ?? null,
    color: fields.color ?? NO_COLOR,
    groups: [...(fields.groups ?? ['tutti'])],
    groupsText: fields.groupsText ?? 'Tutti',
    instrumentIds: [...(fields.instrumentIds ?? instrumentIds())],
    roleIds: [],
    roles: [],
    tags: [...(fields.tags ?? [])],
    infos: (fields.infos ?? []).map((i) => ({ ...i })),
    scheduleItems: [...(fields.scheduleItems ?? [])],
    deadline: fields.deadline ?? null,
    cancelled: false,
    cancelReason: null,
    isToday: date <= TODAY && TODAY <= endDate,
    isPast: `${endDate}T${endTime}:00` < NOW,
  };
}

function publishRehearsal(r) {
  delete r.draft;
  for (const m of members) {
    if (appliesTo(m, r)) promises.push({ memberId: m.id, rehearsalId: r.id, status: 'pending', note: '', respondedAt: null });
  }
}

export function createRehearsal(fields = {}, { draft = false } = {}) {
  const r = buildRehearsal(fields);
  rehearsals.push(r);
  rehearsals.sort((a, b) => a.start.localeCompare(b.start));
  if (draft) r.draft = true;
  else publishRehearsal(r);
  return r;
}

function isOpenDraft(r) {
  const search = location.hash.split('?')[1] ?? '';
  return new URLSearchParams(search).get('edit') === String(r.id);
}

export function settleDrafts() {
  const settled = rehearsals.filter((r) => r.draft && !isOpenDraft(r));
  settled.forEach(publishRehearsal);
  return settled.map((r) => r.id);
}

const appContent = document.getElementById('appContent');
if (appContent) {
  new MutationObserver(() => {
    settleDrafts();
    editorApi?.leaveEditors?.();
  }).observe(appContent, { childList: true });
}

export function removeRehearsals(ids) {
  for (const id of ids) {
    const index = rehearsals.findIndex((r) => r.id === id);
    if (index !== -1) rehearsals.splice(index, 1);
  }
  for (let i = promises.length - 1; i >= 0; i -= 1) {
    if (ids.includes(promises[i].rehearsalId)) promises.splice(i, 1);
  }
}

export function flashRehearsals(ids) {
  state.flash = [...ids];
}

export function undoToast(ids, message) {
  toast(html`${icon('circle-check')} ${message}`, {
    duration: 5000,
    action: {
      label: 'Rückgängig',
      onClick: () => {
        removeRehearsals(ids);
        const route = currentRoute();
        if (route?.name === 'rehearsal-edit' && ids.includes(Number(route.params.id))) {
          back();
        } else if (route?.name === 'rehearsals') {
          const shown = [route.query.edit, route.query.probe].map(Number);
          if (ids.some((id) => shown.includes(id))) navigate('/rehearsals', { replace: true, direction: 'none' });
          else rerender();
        }
      },
    },
  });
}

export function mountBottomBar(root, ...children) {
  const desk = root.querySelector(':scope > .desk-bar');
  if (desk && wideQuery.matches) {
    for (const child of children) {
      child.classList.remove('button--lg', 'button--block');
      child.classList.add('desk-bar__primary');
    }
    desk.append(...children);
    return () => {};
  }
  const bar = h('div', { class: 'bottom-bar' }, h('div', { class: 'bottom-bar__inner' }, children));
  root.append(bar);
  document.documentElement.style.setProperty('--bottom-bar-height', '66px');
  root.classList.add('has-bottom-bar');
  return () => document.documentElement.style.removeProperty('--bottom-bar-height');
}

/* Field pickers and head card values shared with Serie, KI-Import and Probe bearbeiten */

export function pickType(current, onPick, anchor = null) {
  const names = [...new Set([...rehearsalTypes.map((t) => t.name), ...STANDARD_TYPES, current])].filter(Boolean);
  const custom = () => setTimeout(() => ui.openPromptSheet({ title: 'Eigene Art', placeholder: 'Name', maxlength: 30, confirm: 'Fertig', onConfirm: onPick }), anchor ? 0 : 300);
  if (anchor?.isConnected) {
    openMenu(anchor, [
      ...names.map((name) => ({ label: name, checked: name === current, onSelect: () => name !== current && onPick(name) })),
      '-',
      { label: 'Eigene Art…', icon: 'pencil-line', onSelect: custom },
    ], { label: 'Art', minWidth: 200 });
    return;
  }
  const options = names.map((name) => ({ value: name, label: name }));
  options.push({ value: CUSTOM_TYPE, label: 'Eigene Art…' });
  ui.openPicker({
    title: 'Art',
    options,
    value: current,
    onSelect: (value) => {
      if (value === CUSTOM_TYPE) custom();
      else if (value !== current) onPick(value);
    },
  });
}

export function pickGroups(ids, onPick, anchor = null) {
  ui.openBesetzungSheet({
    title: 'Besetzung',
    value: ids,
    anchor,
    onDone: (next) => onPick({ groups: compressGroups(next), groupsText: ui.groupsSentence(next), instrumentIds: next }),
  });
}

export function openOtherPlace(onSave) {
  return openPlaceSheet({ onSaved: (place) => onSave(place.name) });
}

export function pickLocation(current, onPick, anchor = null) {
  const options = locations.map((l) => ({ value: l.name, label: l.name, sub: l.address ?? null, icon: 'map-pin', tone: 'blue' }));
  if (current && !options.some((o) => o.value === current)) options.unshift({ value: current, label: current, icon: 'map-pin', tone: 'blue' });
  options.push({ value: OTHER_PLACE, label: 'Anderer Ort', icon: 'map-pin-plus', tone: 'slate' });
  ui.openPicker({
    title: 'Ort',
    options,
    value: current,
    anchor,
    search: options.length > 6,
    onSelect: (value) => {
      if (value === OTHER_PLACE) setTimeout(() => openOtherPlace((name) => name !== current && onPick(name)), 300);
      else if (value !== current) onPick(value);
    },
  });
}

export function pickColor(current, onPick, anchor = null) {
  const value = hasColor(current) ? current.toLowerCase() : NO_COLOR;
  ui.openPicker({
    title: 'Farbe',
    anchor,
    options: colors.map((c) => (hasColor(c.value)
      ? { value: c.value.toLowerCase(), label: c.name, color: c.value }
      : { value: NO_COLOR, label: 'Keine Farbe', swatchNone: true })),
    value,
    onSelect: (next) => next !== value && onPick(next),
  });
}

export function markUsedEmojis(sheet, used) {
  const taken = new Set(used.filter(Boolean));
  for (const el of sheet.el.querySelectorAll('.editor__emoji')) {
    if (!taken.has(el.dataset.emoji)) continue;
    el.classList.add('is-used');
    el.title = 'Schon verwendet';
  }
}

export function nativeField({ type = 'time', value, label, onInput }) {
  return h('input', {
    class: 'native-field',
    type,
    value,
    required: true,
    'aria-label': label,
    oninput: (event) => event.target.value && onInput(event.target.value),
  });
}

export function fieldRow(title, control) {
  return h('label', { class: 'list-row' },
    h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, title)),
    h('span', { class: 'list-row__trail' }, control));
}

export function pickerOnClick(event) {
  if (!finePointer.matches) return;
  try {
    event.target.showPicker();
  } catch {}
}

export function markInvalid(el, invalid) {
  if (invalid) el.setAttribute('aria-invalid', 'true');
  else el.removeAttribute('aria-invalid');
}

export function deadlineText(r) {
  if (!r.deadline) return 'Keine';
  return `${formatDate(r.deadline).split(',')[0]}, ${r.deadline.slice(8, 10)}.${r.deadline.slice(5, 7)}., ${r.deadline.slice(11, 16)}`;
}

export function fieldError(text, id, hidden = false, className = null) {
  return h('p', { class: ['field-error', className], id, role: 'alert', hidden }, icon('circle-alert'), h('span', {}, text));
}

export function selectField({ label, text, lead = null, className = null, style = null, onClick = null, as = 'button', extra = null, key = null, popup = 'dialog' }) {
  const body = [
    h('span', { class: 'edit-select__label' }, label),
    h('span', { class: 'edit-select__value' }, lead, h('span', { class: 'edit-select__text' }, text)),
    icon('chevron-down', { class: 'edit-select__chevron' }),
    extra,
  ];
  if (as === 'label') return h('label', { class: ['edit-select', className], style, 'data-key': key }, body);
  return h('button', {
    class: ['edit-select', className],
    type: 'button',
    style,
    'data-key': key,
    'aria-label': `${label}: ${text}`,
    'aria-haspopup': popup,
    onclick: (event) => {
      haptic('selection');
      onClick?.(event.currentTarget);
    },
  }, body);
}

export function timeInput({ key, value, label, onChange, invalid = false, describedBy = null }) {
  return h('input', {
    class: 'native-field editor-head__time',
    type: 'time',
    value,
    required: true,
    'aria-label': label,
    'aria-invalid': invalid ? 'true' : null,
    'aria-describedby': invalid ? describedBy : null,
    dataset: { time: key },
    onclick: pickerOnClick,
    onchange: (event) => event.target.value && onChange(event.target.value),
  });
}

/* Banner */

function attention() {
  for (const r of getUpcoming()) {
    if (dayDiff(r.date) > 14) break;
    const target = ui.remindTarget(r);
    if (target.low.length) return { r, ...target };
  }
  return null;
}

function attentionChip(onToggle) {
  const hit = attention();
  if (!hit) return null;
  const { r, low, ids } = hit;
  const day = `${formatDate(r.date).split(',')[0]} ${r.date.slice(8, 10)}.${r.date.slice(5, 7)}.`;
  const sections = low.map((x) => `${x.name} ${x.rate} %`).join(', ');
  const href = `#/rehearsals/${r.id}/responses?${new URLSearchParams({ filter: ids.length ? 'open' : 'no' })}`;
  if (!ids.length) {
    return ui.chip({ label: 'Wenig Zusagen', icon: 'circle-dashed', tone: 'open', href, ariaLabel: `${day}: ${sections}`, className: 'proben-open-chip' });
  }
  const chip = ui.chip({
    label: `${ids.length} offen`,
    icon: 'circle-dashed',
    tone: 'open',
    pressed: state.openOnly,
    className: 'proben-open-chip',
    ariaLabel: `${ids.length} offen für ${day}, ${sections}. Proben mit offenen Antworten filtern`,
    onClick: () => {
      haptic('selection');
      state.openOnly = !state.openOnly;
      chip.classList.toggle('is-selected', state.openOnly);
      chip.setAttribute('aria-pressed', String(state.openOnly));
      onToggle?.();
    },
  });
  chip.title = `${day} · ${sections}`;
  return chip;
}

function attendanceCounts(r) {
  const rows = getAttendance(r.id);
  const present = rows.filter((a) => a.present).length;
  const absent = rows.filter((a) => a.status === 'absent').length;
  return { present, absent, excused: rows.length - present - absent, total: rows.length };
}

function cardLabel(r, past) {
  const parts = [formatDate(r.date), ui.timeRange(r).replace(' – ', ' bis '), r.type !== 'Probe' ? r.type : '', r.groupsText, ui.metaText(r)];
  if (r.cancelled) parts.push(`fällt aus${r.cancelReason ? `: ${r.cancelReason}` : ''}`);
  else if (past) {
    const c = attendanceCounts(r);
    parts.push(c.total ? `${c.present} von ${c.total} anwesend` : 'Anwesenheit nicht erfasst');
  } else {
    const s = ui.castStats(r);
    parts.push(`${s.attending} von ${s.total} zugesagt, ${s.pending} offen`);
  }
  return [...new Set(parts.filter(Boolean))].join(', ');
}

function markPicked(el, on) {
  el.classList.toggle('is-picked', on);
  el.querySelector('.check')?.classList.toggle('is-checked', on);
  if (selection.on) el.querySelector('.proben-row__link, .rehearsal-card__link')?.setAttribute('aria-checked', String(on));
}

/* Cards below 840 px */

const LOW_RATE = 0.5;

function meter(r) {
  const slot = seatSlot(r, { href: `#/rehearsals/${r.id}/responses` });
  slot.classList.add('rehearsals-quota');
  return slot;
}

function attendanceMeter(r) {
  const slot = seatSlot(r, { past: true, href: `#/rehearsals/${r.id}/attendance` });
  slot.classList.add('rehearsals-quota');
  return slot;
}

function recordButton(r) {
  return h('a', {
    class: 'button button--sm button--tinted rehearsals-record',
    href: `#/rehearsals/${r.id}/attendance`,
    'aria-label': `Anwesenheit für ${formatDate(r.date)} erfassen`,
  }, icon('clipboard-pen'), 'Erfassen');
}

function editButton(r, { hero = false } = {}) {
  return h('a', {
    class: ['rehearsals-edit', hero && 'rehearsals-edit--hero'],
    href: editHref(r.id),
    'aria-label': `${formatDate(r.date)} bearbeiten`,
    title: 'Bearbeiten',
    'data-edit': r.id,
  }, icon('pencil'));
}

function cardChildren(r) {
  const past = r.isPast && !r.isToday;
  const line2 = r.cancelled
    ? [
      ui.cancelledTag(),
      r.cancelReason ? h('span', { class: 'rehearsals-reason' }, r.cancelReason) : null,
    ]
    : [ui.typeBadge(r), ui.groupBadge(r), ui.roleChips(r.roleIds), ui.locationMeta(r)];
  const pills = ui.infoPills(r.infos);
  const single = pills && r.infos.length === 1;
  const trail = [];
  if (past) {
    if (!r.cancelled) trail.push(isDocumented(r) ? attendanceMeter(r) : recordButton(r));
  } else {
    if (!r.cancelled && !r.draft) trail.push(meter(r));
    if (wideQuery.matches) trail.push(editButton(r));
  }
  return [
    h('a', { class: 'rehearsal-card__link', href: detailHref(r), 'aria-label': cardLabel(r, past) }),
    ui.dateChip(r),
    h('div', { class: 'rehearsal-card__main' },
      h('div', { class: 'rehearsal-card__line1' }, h('span', { class: ['rehearsal-card__time', r.multiDay && 'rehearsal-card__time--range'] }, rowTimeText(r))),
      h('div', { class: 'rehearsal-card__line2' }, line2.filter(Boolean), single ? pills : null),
      pills && !single ? h('div', { class: 'rehearsal-card__line3' }, pills) : null),
    trail.length ? h('div', { class: 'rehearsal-card__trail rehearsals-trail' }, trail) : null,
    pickMark(),
  ];
}

function pickMark() {
  return h('span', { class: 'check rehearsal-card__check', 'aria-hidden': 'true' }, icon('check'));
}

function decorate(el) {
  if (el.dataset.decorated) return;
  const r = getRehearsal(el.dataset.id);
  if (!r) return;
  const past = r.isPast && !r.isToday;
  const keep = ['is-selected', 'is-highlighted', 'is-updated', 'is-entering', 'is-picked'].filter((c) => el.classList.contains(c));
  el.className = ['rehearsal-card', 'rehearsal-card--mgmt', 'rehearsals-card', past && 'rehearsal-card--past', r.cancelled && 'rehearsal-card--cancelled', r.draft && 'is-draft', ...keep]
    .filter(Boolean).join(' ');
  if (hasColor(r.color) && !r.cancelled) {
    el.classList.add('is-tinted');
    el.style.setProperty('--tag', r.color);
  } else el.style.removeProperty('--tag');
  el.dataset.decorated = '1';
  el.replaceChildren(...cardChildren(r).filter(Boolean));
  refreshIcons(el);
}

function card(r) {
  const el = h('article', { class: 'rehearsal-card', 'data-id': r.id });
  decorate(el);
  if (selection.ids.has(r.id)) markPicked(el, true);
  return el;
}

function dayLabel(r) {
  const diff = dayDiff(r.date);
  if (diff === 0) return 'Heute';
  if (diff === 1) return 'Morgen';
  return formatDate(r.date);
}

function hero(r) {
  const s = ui.stats(r.id);
  const live = isOngoing(r);
  const started = r.start <= NOW;
  const documented = isDocumented(r);
  const now = live ? ui.liveItem(r) : null;
  const meta = [ui.typeBadge(r), ui.groupBadge(r), ui.roleChips(r.roleIds), ui.locationMeta(r, { always: true }), ui.infoPills(r.infos)].filter(Boolean);
  const label = [dayLabel(r), ui.timeRange(r).replace(' – ', ' bis '), live ? 'jetzt' : '', r.groupsText, ui.metaText(r),
    `${s.attending} zugesagt, ${s.notAttending} abgesagt, ${s.pending} offen`].filter(Boolean).join(', ');
  const day = dayDiff(r.date) === 0 || dayDiff(r.date) === 1 ? `${dayLabel(r)} · ${r.date.slice(8, 10)}.${r.date.slice(5, 7)}.` : dayLabel(r);
  return h('article', { class: 'rehearsal-hero proben-hero rehearsals-hero', 'data-id': r.id },
    h('a', { class: 'rehearsal-card__link', href: detailHref(r), 'aria-label': label }),
    pickMark(),
    h('div', { class: 'proben-hero__top' },
      h('span', { class: 'proben-hero__day' }, day),
      seatSlot(r, { href: `#/rehearsals/${r.id}/responses` })),
    h('div', { class: 'proben-hero__time' }, h('span', { class: 'proben-hero__range' }, rowTimeText(r))),
    meta.length ? h('div', { class: 'rehearsal-hero__meta' }, meta) : null,
    now ? h('div', { class: 'rehearsal-hero__live' },
      h('span', { class: 'live-dot', 'aria-hidden': 'true' }),
      h('span', { class: 'rehearsal-hero__live-label' }, 'Jetzt'),
      h('span', { class: 'rehearsal-hero__live-title' }, now.title),
      h('span', { class: 'rehearsal-hero__live-until' }, `bis ${now.until}`)) : null,
    live ? ui.timeLine(r, { className: 'time-line--hero' }) : null,
    started ? h('div', { class: 'rehearsals-hero__row rehearsals-hero__actions' },
      h('a', { class: 'button button--on-hero', href: `#/rehearsals/${r.id}/attendance` },
        icon(documented ? 'clipboard-list' : 'clipboard-pen'),
        h('span', { class: 'button__label' }, documented ? 'Anwesenheit ansehen' : 'Anwesenheit erfassen')),
      editButton(r, { hero: true })) : null);
}

/* Rows */

function countOf(r) {
  return seatSlot(r);
}

function attendanceSlot(r) {
  return seatSlot(r, { past: true });
}

function recordLink(r) {
  return h('a', {
    class: 'button button--plain button--sm proben-row__action',
    href: `#/rehearsals/${r.id}/attendance`,
    'aria-label': `Anwesenheit für ${formatDate(r.date)} erfassen`,
  }, 'Erfassen');
}

function rowSlot(r, past) {
  if (r.cancelled) return cancelledSlot();
  if (past) return isDocumented(r) ? attendanceSlot(r) : recordLink(r);
  return r.draft ? null : countOf(r);
}

function row(r, { nav = false } = {}) {
  const past = r.isPast && !r.isToday;
  const el = probenRow(r, {
    nav,
    href: detailHref(r),
    label: cardLabel(r, past),
    className: r.draft ? 'is-draft' : null,
    today: r.isToday && !past && !r.cancelled,
    past,
    deadline: past || r.cancelled ? null : ui.deadlineInfo(r),
    slot: rowSlot(r, past),
    expanded: !nav && wideQuery.matches && r.id === state.expandedId && !paired(),
    check: !nav,
    live: wideQuery.matches && !past ? ui.liveLine(r) : null,
    dateLine: false,
  });
  if (!wideQuery.matches && !past) {
    const live = ui.liveLine(r);
    if (live) el.append(live);
  }
  if (selection.ids.has(r.id)) markPicked(el, true);
  return el;
}

export function listItem(r, { nav = false } = {}) {
  return wideQuery.matches ? rowItem(row(r, { nav })) : card(r);
}

function rowDetail(r, ctx, segQuery) {
  if (!detailApi?.responsesHref) return h('div');
  return rowPanel(r, ctx, detailApi, { editPath: `/rehearsals?${segQuery}edit=${r.id}`, inBanner: attention()?.r.id === r.id });
}

function addSplit() {
  return h('div', { class: 'proben-add' },
    h('button', { class: 'proben-add__main', type: 'button', onclick: createNew }, 'Neue Probe'),
    h('button', {
      class: 'proben-add__more',
      type: 'button',
      'aria-label': 'Serie oder Plan-Import',
      'aria-haspopup': 'menu',
      'aria-expanded': 'false',
      onclick: (event) => toggleMenu(event.currentTarget),
    }, icon('chevron-down')));
}

/* Grouping */

export function upcomingGroups(list) {
  const monday = addDays(TODAY, -((weekday(TODAY) + 6) % 7));
  const sunday = addDays(monday, 6);
  const nextMonday = addDays(sunday, 1);
  const nextSunday = addDays(sunday, 7);
  const groups = [];
  const push = (key, label, range, r) => {
    let g = groups.find((x) => x.key === key);
    if (!g) groups.push((g = { key, label, range, items: [] }));
    g.items.push(r);
  };
  for (const r of list) {
    if (r.date <= sunday) push('this', 'Diese Woche', `${shortDate(monday)} – ${shortDate(sunday)}`, r);
    else if (r.date <= nextSunday) push('next', 'Nächste Woche', `${shortDate(nextMonday)} – ${shortDate(nextSunday)}`, r);
    else push(r.date.slice(0, 7), monthLabel(r.date), null, r);
  }
  return groups;
}

function pastGroups(list) {
  const groups = [];
  for (const r of list) {
    const key = r.date.slice(0, 7);
    let g = groups.find((x) => x.key === key);
    if (!g) groups.push((g = { key, label: monthLabel(r.date), range: null, items: [] }));
    g.items.push(r);
  }
  return groups;
}

export function dateSep(label, range) {
  return h('h2', { class: 'date-sep' },
    h('span', { class: 'date-sep__label' }, label),
    range ? h('span', { class: 'date-sep__range' }, range) : null);
}

function haystack(r) {
  return [
    formatDate(r.date),
    formatDateLong(r.date),
    shortDate(r.date),
    monthLabel(r.date),
    r.type,
    r.location,
    r.groupsText,
    r.cancelReason,
  ].filter(Boolean).join(' ').toLowerCase();
}

function snippetOf(r, query) {
  const tag = r.tags.find((t) => t.toLowerCase().includes(query));
  if (tag) return { lead: icon('tag'), text: tag };
  const info = r.infos.find((i) => i.text.toLowerCase().includes(query));
  if (info) return { lead: icon('info'), text: info.text };
  const item = r.scheduleItems.find((i) => i.title.toLowerCase().includes(query));
  return item ? { lead: icon('list-music'), text: item.title } : null;
}

function snippetLine({ lead, text }, query) {
  const at = text.toLowerCase().indexOf(query);
  return h('p', { class: 'rehearsals-snippet' }, lead,
    h('span', { class: 'rehearsals-snippet__text' },
      text.slice(0, at), h('mark', null, text.slice(at, at + query.length)), text.slice(at + query.length)));
}

/* Create */

function createNew() {
  createSingle(newDefaults());
}

function createSingle(d) {
  const r = createRehearsal(d, { draft: true });
  haptic('light');
  flashRehearsals([r.id]);
  if (wideQuery.matches) navigate(`/rehearsals?edit=${r.id}`, { replace: true, direction: 'none' });
  else navigate(`/rehearsals?edit=${r.id}&new=1`);
}

function openUpcoming(path) {
  if (currentRoute()?.query?.seg === 'past') history.replaceState(history.state, '', '#/rehearsals');
  navigate(path);
}

function createRows(close, role) {
  const d = newDefaults();
  const row = (iconName, title, onClick) => h('button', {
    class: 'list-row',
    type: 'button',
    role: role ?? null,
    onclick: () => {
      close();
      onClick();
    },
  },
  h('span', { class: 'list-row__lead' }, icon(iconName)),
  h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, title)));
  return [
    row('calendar-plus', 'Neue Probe', () => createSingle(d)),
    row('repeat', 'Serie anlegen', () => openUpcoming('/rehearsals/series')),
    ensemble.settings.allowRehearsalImport ? row('sparkles', 'Plan importieren', () => openUpcoming('/rehearsals/import')) : null,
  ].filter(Boolean);
}

function openCreateSheet(select) {
  haptic('light');
  const sheet = openSheet({
    title: 'Proben anlegen',
    content: h('div', { class: 'list-group action-list rehearsals-create' },
      createRows(() => closeSheet()),
      select ? h('button', {
        class: 'list-row',
        type: 'button',
        onclick: () => {
          closeSheet();
          select();
        },
      }, h('span', { class: 'list-row__lead' }, icon('square-check')), h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Mehrere auswählen'))) : null),
    actions: [{ label: 'Abbrechen', variant: 'secondary' }],
  });
  sheet.el.classList.add('sheet--action');
}

let menu = null;

function closeMenu({ focus = false } = {}) {
  if (!menu) return;
  const { el, anchor, cleanup } = menu;
  menu = null;
  cleanup();
  anchor.setAttribute('aria-expanded', 'false');
  el.classList.remove('is-open');
  el.remove();
  if (focus && anchor.isConnected) anchor.focus({ preventScroll: true });
}

function toggleMenu(anchor) {
  if (menu) {
    closeMenu();
    return;
  }
  closeFilter();
  haptic('light');
  const el = h('div', { class: 'list-group action-list rehearsals-menu', role: 'menu', 'aria-label': 'Probe anlegen' },
    createRows(() => closeMenu(), 'menuitem'));
  const rect = anchor.getBoundingClientRect();
  el.style.top = `${Math.round(rect.bottom + 4)}px`;
  el.style.right = `${Math.round(document.documentElement.clientWidth - rect.right)}px`;
  document.body.append(el);
  refreshIcons(el);
  anchor.setAttribute('aria-expanded', 'true');
  const items = () => [...el.querySelectorAll('[role="menuitem"]')];
  const onDown = (event) => {
    if (!el.contains(event.target) && !anchor.contains(event.target)) closeMenu();
  };
  const onKey = (event) => {
    if (event.key === 'Escape') {
      event.preventDefault();
      event.stopPropagation();
      closeMenu({ focus: true });
    } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      const list = items();
      const i = list.indexOf(document.activeElement);
      list[(i + (event.key === 'ArrowDown' ? 1 : list.length - 1)) % list.length]?.focus();
    } else if (event.key === 'Tab') {
      closeMenu();
    }
  };
  const onResize = () => closeMenu();
  document.addEventListener('pointerdown', onDown, true);
  document.addEventListener('keydown', onKey, true);
  window.addEventListener('resize', onResize);
  menu = {
    el,
    anchor,
    cleanup: () => {
      document.removeEventListener('pointerdown', onDown, true);
      document.removeEventListener('keydown', onKey, true);
      window.removeEventListener('resize', onResize);
    },
  };
  requestAnimationFrame(() => {
    el.classList.add('is-open');
    items()[0]?.focus({ preventScroll: true });
  });
}

/* Header and search */

let currentCtx = null;

function headerActions({ filter, select }) {
  if (selection.on) return [];
  const count = filterCount();
  return [
    { text: true, label: count ? `Filtern (${count})` : 'Filtern', onClick: filter },
    ui.bellAction('conductor'),
    { icon: 'plus', label: 'Neue Probe', primary: true, onClick: () => openCreateSheet() },
  ];
}

function mobileHeader(handlers) {
  if (selection.on) return { actions: [], leading: null, accessory: ui.titleTools(ensembleChip()) };
  return {
    actions: headerActions(handlers),
    leading: h('button', { class: 'app-header__action is-text app-header__select', type: 'button', onclick: handlers.select }, 'Auswählen'),
    accessory: ui.titleTools(
      ui.titleAction({ icon: 'search', label: 'Suchen', key: 'search', pressed: state.search, onClick: toggleSearch }),
      ui.titleAction({ icon: 'plus', label: 'Neue Probe', key: 'new', primary: true, onClick: () => openCreateSheet() }),
      ensembleChip()),
  };
}

function focusSearch(event) {
  if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey) return;
  if (event.target.closest?.('input, textarea, select, [contenteditable="true"]') || document.querySelector('.sheet-host')) return;
  const input = document.querySelector('.rehearsals-screen .desk-search .search-field__input');
  if (!input) return;
  event.preventDefault();
  input.focus();
  input.select();
}

function toggleSearch() {
  state.search = !state.search;
  if (!state.search) state.query = '';
  currentCtx?.rerender();
}

function searchField(onInput, { desk = false } = {}) {
  const input = h('input', {
    class: 'search-field__input',
    type: 'search',
    placeholder: 'Suchen',
    'aria-label': 'Proben durchsuchen',
    enterkeyhint: 'search',
    autocomplete: 'off',
    value: state.query,
    oninput: (event) => {
      state.query = event.target.value;
      clear.hidden = !state.query;
      onInput();
    },
    onkeydown: (event) => {
      if (event.key !== 'Escape') return;
      event.preventDefault();
      if (desk) clear.click();
      else toggleSearch();
    },
  });
  const clear = h('button', {
    class: 'search-field__clear',
    type: 'button',
    'aria-label': 'Suche leeren',
    hidden: !state.query,
    onclick: () => {
      state.query = '';
      input.value = '';
      clear.hidden = true;
      onInput();
      input.focus();
    },
  }, icon('circle-x'));
  if (desk) return h('label', { class: 'search-field desk-search' }, icon('search', { class: 'search-field__icon' }), input, clear);
  requestAnimationFrame(() => input.isConnected && input.focus({ preventScroll: true }));
  return h('div', { class: 'rehearsals-search' },
    h('label', { class: 'search-field' }, icon('search', { class: 'search-field__icon' }), input, clear),
    h('button', { class: 'button button--plain rehearsals-search__cancel', type: 'button', onclick: toggleSearch }, 'Abbrechen'));
}

/* Sticky separators */

function trackStuck(scroller, host) {
  let frame = 0;
  const update = () => {
    frame = 0;
    const seps = host.querySelectorAll('.date-sep');
    if (!seps.length || !scroller.isConnected) return;
    const offset = parseFloat(getComputedStyle(seps[0]).top) || 0;
    const limit = scroller.getBoundingClientRect().top + (parseFloat(getComputedStyle(scroller).paddingTop) || 0) + offset;
    for (const sep of seps) {
      const pinned = Math.abs(sep.getBoundingClientRect().top - limit) < 1;
      const moved = sep.parentElement.getBoundingClientRect().top < limit - 1;
      sep.classList.toggle('is-stuck', scroller.scrollTop > 0 && pinned && moved);
    }
  };
  const onScroll = () => {
    if (!frame) frame = requestAnimationFrame(update);
  };
  scroller.addEventListener('scroll', onScroll, { passive: true });
  state.cleanup.push(() => {
    scroller.removeEventListener('scroll', onScroll);
    cancelAnimationFrame(frame);
  });
  requestAnimationFrame(update);
  return update;
}

/* Screen */

function toolbarItems({ segControl, remindChip, filterBtn, selectBtn, fillList }) {
  return [
    h('h1', { class: 'proben-toolbar__title' }, 'Proben'),
    remindChip,
    segControl,
    h('div', { class: 'proben-toolbar__spacer' }),
    searchField(fillList, { desk: true }),
    filterBtn,
    selectBtn,
    addSplit(),
    ui.bellButton('conductor'),
  ].filter(Boolean);
}

function listPathFor(segQuery) {
  return `/rehearsals${segQuery ? '?seg=past' : ''}`;
}

const paired = () => document.documentElement.hasAttribute('data-pair');

function selectRow(id, listHost, segQuery) {
  const r = getRehearsal(id);
  if (!r) return;
  state.expandedId = id;
  history.replaceState(history.state, '', `#/rehearsals/${id}${segQuery ? '?seg=past' : ''}`);
  import('../shell-aside.js').then((mod) => mod.syncAside(document.getElementById('appContent')));
}

async function toggleRow(id, listHost, ctx, segQuery) {
  haptic('selection');
  if (paired()) {
    selectRow(id, listHost, segQuery);
    return;
  }
  const r = getRehearsal(id);
  const row = listHost.querySelector(`.proben-row[data-id="${id}"]`);
  if (!r || !row) return;
  if (!detailApi) await detailModule;
  if (!row.isConnected) return;
  toggleInline(listHost, row, () => rowDetail(r, ctx, segQuery));
  const open = row.classList.contains('is-expanded') ? row : listHost.querySelector('.proben-row.is-expanded');
  state.expandedId = open ? Number(open.dataset.id) : null;
  history.replaceState(history.state, '', open ? `#/rehearsals/${open.dataset.id}${segQuery ? '?seg=past' : ''}` : `#${listPathFor(segQuery)}`);
}

export function renderRehearsals(root, ctx, { expandedId = null } = {}) {
  return render(root, {}, ctx, { expandedId });
}

function render(root, params, ctx, { expandedId = null } = {}) {
  if (!ui.guard(ctx, ['conductor'])) return;
  currentCtx = ctx;
  const wide = wideQuery.matches;
  rowsMode();
  const settled = settleDrafts();
  if (settled.length && wide) undoToast(settled, 'Probe angelegt');
  const seg = ctx.query.seg === 'past' ? 'past' : 'upcoming';
  const forceEmpty = ctx.query.empty === '1';
  const segQuery = seg === 'past' ? 'seg=past&' : '';
  const onWide = () => currentCtx?.rerender();
  wideQuery.addEventListener('change', onWide);
  state.cleanup.push(() => wideQuery.removeEventListener('change', onWide));

  const probeId = Number(ctx.query.probe) || null;
  if (getRehearsal(probeId)) {
    navigate(`/rehearsals/${probeId}${seg === 'past' ? '?seg=past' : ''}`, { replace: true, direction: 'none' });
    return;
  }

  const editId = Number(ctx.query.edit) || null;
  if (!wide && editId && getRehearsal(editId)) return renderEditPage(root, editId, ctx);

  root.classList.add('rehearsals-screen');

  if (wide && editId && getRehearsal(editId)) return renderEditColumn(root, editId, ctx, segQuery);

  const flash = state.flash;
  state.flash = null;
  const flashIds = new Set(flash ?? []);

  const upcoming = forceEmpty ? [] : getUpcoming().filter((r) => !r.draft);
  const past = forceEmpty ? [] : getPast();
  const highlightId = Number(ctx.query.highlight) || (!wide ? editId ?? probeId : null);
  state.expandedId = wide && expandedId && getRehearsal(expandedId) ? Number(expandedId) : null;
  const headerHandlers = {
    filter: () => openFilterSheet({ count: countShown, onChange: applyFilters }),
    select: () => enterSelect(),
  };
  setHeader(wide ? { actions: [] } : mobileHeader(headerHandlers));
  document.addEventListener('keydown', focusSearch);
  state.cleanup.push(() => document.removeEventListener('keydown', focusSearch));

  const rows = wide;
  const heroR = upcoming.find((r) => !r.cancelled && (isOngoing(r) || (r.start > NOW && dayDiff(r.date) <= 2)));
  const listUpcoming = rows ? upcoming : upcoming.filter((r) => r !== heroR);
  const listHost = h('div', { class: ['rehearsals-list', rows && 'proben-rows'] });
  let bar = null;
  let selectBar = null;
  let refreshStuck = () => {};
  state.cleanup.push(() => document.documentElement.style.removeProperty('--bottom-bar-height'));

  const countShown = () => (seg === 'past' ? past : upcoming).filter(passes).length;
  const filterBar = filterRow({ onChange: () => applyFilters() });
  const filterBtn = filterButton({ onClick: (anchor) => toggleFilter(anchor, { count: countShown, onChange: applyFilters, onOpen: closeMenu }) });
  const selectBtn = h('button', { class: 'proben-tool proben-toolbar__select', type: 'button', title: 'Auswählen', onclick: () => enterSelect() },
    icon('square-check'), h('span', { class: 'proben-tool__label' }, 'Auswählen'));
  const controls = selectControls({ onAction: (key) => runAction(key), onCancel: () => exitSelect() });
  const runAction = createActions({
    onChanged: () => {
      fillList();
      controls.sync();
    },
    onExit: () => {
      exitSelect();
      currentCtx?.rerender();
    },
    onRestore: () => currentCtx?.rerender(),
  });

  const filteredEmpty = () => h('div', { class: 'proben-empty' },
    h('span', { class: 'proben-empty__text' }, 'Keine Proben für diese Filter'),
    h('button', {
      class: 'button button--plain',
      type: 'button',
      onclick: () => {
        resetFilters();
        applyFilters();
      },
    }, 'Filter zurücksetzen'));

  function applyFilters() {
    filterBar.paint();
    filterBtn.paint();
    if (!wide) setHeader(mobileHeader(headerHandlers));
    fillList();
  }

  function paintBar() {
    bar.classList.toggle('is-selecting', selection.on);
    bar.replaceChildren(...(selection.on
      ? [controls.count, controls.actions, controls.cancel]
      : toolbarItems({ segControl, remindChip, filterBtn: filterBtn.el, selectBtn, fillList })));
    refreshIcons(bar);
  }

  function mountSelectBar(on) {
    selectBar?.remove();
    selectBar = null;
    root.classList.toggle('has-bottom-bar', on);
    if (!on) {
      document.documentElement.style.removeProperty('--bottom-bar-height');
      return;
    }
    selectBar = h('div', { class: 'bottom-bar proben-selectbar' },
      h('div', { class: 'bottom-bar__inner' },
        h('div', { class: 'proben-selectbar__head' }, controls.count, controls.cancel),
        controls.actions));
    root.append(selectBar);
    document.documentElement.style.setProperty('--bottom-bar-height', `${selectBar.offsetHeight}px`);
  }

  function paintSelect() {
    const on = selection.on;
    listHost.classList.toggle('is-selecting', on);
    root.classList.toggle('is-selecting', on);
    for (const link of listHost.querySelectorAll('.proben-row__link, .rehearsal-card__link')) {
      if (on) {
        link.setAttribute('role', 'checkbox');
        link.setAttribute('aria-checked', String(selection.ids.has(Number(link.closest('[data-id]')?.dataset.id))));
      } else {
        link.removeAttribute('role');
        link.removeAttribute('aria-checked');
      }
    }
    if (wide) paintBar();
    else {
      setHeader(mobileHeader(headerHandlers));
      mountSelectBar(on);
    }
    controls.sync();
  }

  function enterSelect() {
    if (selection.on) return;
    closeMenu();
    closeFilter();
    const open = [...listHost.querySelectorAll('.proben-row.is-expanded')];
    if (open.length) {
      state.expandedId = null;
      for (const row of open) collapseRow(row, { animate: false });
      history.replaceState(history.state, '', `#${listPathFor(segQuery)}`);
    }
    haptic('light');
    selection.on = true;
    selection.ids.clear();
    paintSelect();
  }

  function exitSelect() {
    if (!selection.on) return;
    selection.on = false;
    selection.ids.clear();
    for (const el of listHost.querySelectorAll('.is-picked')) markPicked(el, false);
    paintSelect();
  }

  function toggleSelect(id) {
    haptic('selection');
    if (selection.ids.has(id)) selection.ids.delete(id);
    else selection.ids.add(id);
    const el = listHost.querySelector(`[data-id="${id}"]`);
    if (el) markPicked(el, selection.ids.has(id));
    controls.sync();
  }

  const onSelectKey = (event) => {
    if (event.key === 'Escape' && selection.on && !document.querySelector('.sheet-host')) exitSelect();
  };
  document.addEventListener('keydown', onSelectKey);
  state.cleanup.push(() => document.removeEventListener('keydown', onSelectKey));

  listHost.addEventListener('click', (event) => {
    if (!selection.on) return;
    const item = event.target.closest('[data-id]');
    if (!item || !listHost.contains(item)) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    toggleSelect(Number(item.dataset.id));
  }, true);

  const expandInitial = () => {
    if (paired()) return;
    const id = state.expandedId;
    const row = id && listHost.querySelector(`.proben-row[data-id="${id}"]`);
    if (!row || row.parentElement?.querySelector(':scope > .proben-expand')) return;
    expandRow(row, rowDetail(getRehearsal(id), ctx, segQuery), { animate: false });
  };

  const fillList = () => {
    const query = state.query.trim().toLowerCase();
    const source = (seg === 'past' ? past : listUpcoming).filter(passes).filter((r) => !state.openOnly || seg === 'past' || ui.castStats(r).pending > 0);
    const showHero = seg === 'upcoming' && heroR && !rows && passes(heroR) && (!state.openOnly || ui.castStats(heroR).pending > 0);
    const filtering = filterCount() > 0;
    const direct = (r) => haystack(r).includes(query);
    const deep = query && ![...source, showHero ? heroR : null].some((r) => r && direct(r));
    const matches = (r) => !query || (deep ? Boolean(snippetOf(r, query)) : direct(r));
    const filtered = source.filter(matches);
    const visible = new Set(filtered.map((r) => r.id));
    if (showHero && matches(heroR)) visible.add(heroR.id);
    for (const id of selection.ids) {
      if (!visible.has(id)) selection.ids.delete(id);
    }
    controls.sync();
    const nodes = [];
    if (showHero && matches(heroR)) {
      const heroEl = hero(heroR);
      if (selection.ids.has(heroR.id)) markPicked(heroEl, true);
      nodes.push(heroEl);
    }
    const groups = seg === 'past' ? pastGroups(filtered) : upcomingGroups(filtered);
    for (const g of groups) {
      const items = g.items.map((r) => {
        const el = rows ? row(r) : card(r);
        if (r.id === highlightId || flashIds.has(r.id)) el.classList.add('is-highlighted');
        if (deep) el.append(snippetLine(snippetOf(r, query), query));
        return el;
      });
      nodes.push(h('section', { class: 'rehearsals-group' }, dateSep(g.label, g.range), rows ? h('div', { class: 'proben-card' }, items.map((el) => rowItem(el))) : items));
    }
    nodes.splice(0, nodes.length, ...nodes.filter(Boolean));
    if (!nodes.length) {
      if (filtering) nodes.push(filteredEmpty());
      else if (query) {
        nodes.push(ui.emptyState({
          icon: 'search-x',
          title: 'Keine Treffer',
          tone: 'slate',
          action: {
            label: 'Suche leeren',
            icon: 'circle-x',
            onClick: () => {
              const input = root.querySelector('.search-field__input');
              if (!input) return;
              input.value = '';
              input.dispatchEvent(new Event('input'));
              input.focus();
            },
          },
        }));
      }
      else if (seg === 'past') nodes.push(ui.emptyState({ icon: 'history', title: 'Keine vergangenen Proben', tone: 'slate' }));
      else {
        const empty = ui.emptyState({ icon: 'calendar-plus', title: 'Keine Proben', action: { label: 'Probe anlegen', icon: 'plus', onClick: createNew } });
        empty.querySelector('.button')?.classList.replace('button--tinted', 'button--primary');
        nodes.push(empty);
      }
    }
    listHost.replaceChildren(...nodes);
    refreshIcons(listHost);
    if (wide) {
      if (detailApi) expandInitial();
      else detailModule.then(() => listHost.isConnected && expandInitial());
    }
    refreshStuck();
  };

  const remindChip = forceEmpty ? null : attentionChip(() => fillList());
  const segControl = ui.segmented([
    { value: 'upcoming', label: 'Anstehend', icon: remindChip ? null : 'calendar-clock' },
    { value: 'past', label: 'Vergangen', icon: remindChip ? null : 'history' },
  ], seg, (value) => {
    state.listScroll = 0;
    navigate(value === 'past' ? '/rehearsals?seg=past' : '/rehearsals', { replace: true, direction: 'none', scroll: 'top' });
  });
  segControl.setAttribute('aria-label', 'Zeitraum');

  fillList();

  if (!rows) {
    const observer = new MutationObserver((records) => {
      for (const record of records) {
        for (const node of record.addedNodes) {
          if (node instanceof Element && node.matches('.rehearsal-card')) decorate(node);
        }
      }
    });
    observer.observe(listHost, { childList: true, subtree: true });
    state.cleanup.push(() => observer.disconnect());
  }

  const firstFlash = () => flash && listHost.querySelector(`:is(.rehearsal-card, .proben-row)[data-id="${flash[0]}"]`);

  if (wide) {
    root.classList.add('proben-desk');
    bar = h('div', { class: 'proben-toolbar' });
    paintBar();
    root.append(bar, filterBar.el, listHost);
    listHost.addEventListener('click', (event) => {
      if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey) return;
      const item = event.target.closest('.proben-row[data-id]');
      const link = event.target.closest('.proben-row__link');
      if (!item || !link) return;
      event.preventDefault();
      event.stopPropagation();
      toggleRow(Number(item.dataset.id), listHost, ctx, segQuery);
    }, true);
    const onArrow = (event) => {
      if (!paired() || selection.on || !['ArrowDown', 'ArrowUp'].includes(event.key)) return;
      if (event.target.closest?.('input, textarea, select, [contenteditable], .sheet-host')) return;
      const rows = [...listHost.querySelectorAll('.proben-row[data-id]')];
      const focused = event.target.closest?.('.proben-row[data-id]');
      const at = rows.indexOf(focused && listHost.contains(focused) ? focused : listHost.querySelector('.proben-row.is-current'));
      const next = rows[Math.min(rows.length - 1, Math.max(0, at + (event.key === 'ArrowDown' ? 1 : -1)))];
      if (!next || next === rows[at]) return;
      event.preventDefault();
      selectRow(Number(next.dataset.id), listHost, segQuery);
      next.querySelector('.proben-row__link')?.focus({ preventScroll: true });
      next.scrollIntoView({ block: 'nearest' });
    };
    document.addEventListener('keydown', onArrow);
    state.cleanup.push(() => document.removeEventListener('keydown', onArrow));
    const scroller = document.getElementById('appContent');
    refreshStuck = trackStuck(scroller, listHost);
    const syncBar = () => bar.classList.toggle('is-stuck', scroller.scrollTop > 0);
    scroller.addEventListener('scroll', syncBar, { passive: true });
    state.cleanup.push(() => scroller.removeEventListener('scroll', syncBar));
    requestAnimationFrame(() => {
      const target = firstFlash() ?? (state.expandedId && listHost.querySelector(`.proben-row[data-id="${state.expandedId}"]`));
      if (target) target.scrollIntoView({ block: firstFlash() ? 'center' : 'nearest' });
      syncBar();
    });
  } else {
    const head = [state.search ? searchField(fillList) : null, h('div', { class: 'proben-top' }, remindChip, segControl)];
    root.append(...head.filter(Boolean), filterBar.el, listHost);
    refreshStuck = trackStuck(document.getElementById('appContent'), listHost);
    const target = firstFlash() ?? (highlightId && listHost.querySelector(`:is(.rehearsal-card, .proben-row)[data-id="${highlightId}"]`));
    if (target) requestAnimationFrame(() => target.scrollIntoView({ block: 'center' }));
  }
  if (selection.on) paintSelect();
}

export async function renderDetailPage(root, id, ctx) {
  const mod = await detailModule;
  if (typeof mod.renderRehearsalDetail !== 'function') {
    navigate(`/promises/${id}`, { replace: true, direction: 'none' });
    return;
  }
  const r = getRehearsal(id);
  const past = r?.isPast && !r.isToday;
  await mod.renderRehearsalDetail(root, id, { ...ctx, params: { id: String(id) } }, { editPath: `/rehearsals?${past ? 'seg=past&' : ''}edit=${id}` });
}

async function renderEditPage(root, id, ctx) {
  const mod = await editorModule;
  if (typeof mod.renderEditor !== 'function') {
    navigate(`/rehearsals/${id}`, { replace: true, direction: 'none' });
    return;
  }
  const query = getRehearsal(id).draft ? { ...ctx.query, new: '1' } : ctx.query;
  mod.renderEditor(root, id, { ...ctx, params: { id: String(id) }, query });
}

/* Editor in the content column */

async function renderEditColumn(root, id, ctx, segQuery) {
  const r = getRehearsal(id);
  const draft = Boolean(r.draft);
  setHeader({ actions: [] });
  root.classList.add('rehearsals-edit-col', 'proben-desk');
  const host = h('div');
  root.append(host);
  const mod = await editorModule;
  if (!host.isConnected) return;
  if (typeof mod.renderEditor === 'function') {
    try {
      await mod.renderEditor(host, r.id, { ...ctx, params: { id: String(r.id) }, query: draft ? { new: '1' } : {} }, { embedded: true });
      refreshIcons(host);
      return;
    } catch (error) {
      console.error(error);
    }
  }
  navigate(`/rehearsals/${r.id}`, { replace: true, direction: 'none' });
}

registerScreen('rehearsals', {
  title: 'Proben',
  tab: '/rehearsals',
  render,
  onLeave() {
    closeMenu();
    closeFilter();
    state.cleanup.splice(0).forEach((fn) => fn());
    const next = location.hash.replace(/^#/, '').split('?')[0];
    if (next !== '/rehearsals') resetSelection();
  },
});

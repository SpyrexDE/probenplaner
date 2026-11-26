/* Probe bearbeiten (Leitung) and Rückmeldungen */

import {
  TABS, back, h, haptic, html, icon, navigate, openSheet, refreshIcons, registerScreen, renderTabbar, rerender, setHeader, store, toast,
} from '../app.js';
import { openMenu } from '../menu.js';
import {
  NOW, TODAY, appliesTo, colors, compressGroups, formatDate, getLocation, getPast, getRehearsal, getRole, getSection, getUpcoming, locations, members, promises,
  rehearsals, roles, tags as knownTags,
} from '../data.js';
import { addPlace } from '../places.js';
import { rowDate, rowsMode, weekdayAnchor, dayNumber } from '../rsvp.js';
import * as ui from '../ui.js';
import {
  deadlineText, fieldError, fieldRow, markInvalid, markUsedEmojis, nativeField, openOtherPlace, pickType, pickerOnClick, selectField, timeInput,
} from './rehearsals.js';

const CANCEL_REASONS = [
  ['Leitung erkrankt', 'thermometer'],
  ['Raum nicht verfügbar', 'door-closed'],
  ['Zu wenige Zusagen', 'users-round'],
  ['Sonstiges', 'pencil-line'],
];
const OTHER_REASON = 'Sonstiges';
const WEEKDAYS = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
const NO_COLOR = '#e5e7eb';

/* Date helpers */

const pad = (n) => String(n).padStart(2, '0');

function addDays(iso, n) {
  const [y, m, d] = iso.split('-').map(Number);
  const t = new Date(Date.UTC(y, m - 1, d + n));
  return `${t.getUTCFullYear()}-${pad(t.getUTCMonth() + 1)}-${pad(t.getUTCDate())}`;
}

function daysBetween(from, to) {
  return Math.round((Date.parse(to) - Date.parse(from)) / 86400000);
}

function dateNumeric(iso) {
  const [y, m, d] = iso.split('-').map(Number);
  return `${WEEKDAYS[new Date(Date.UTC(y, m - 1, d)).getUTCDay()]}, ${pad(d)}.${pad(m)}.${y}`;
}

function addMinutes(time, minutes) {
  const [hh, mm] = time.split(':').map(Number);
  const total = Math.min(23 * 60 + 59, hh * 60 + mm + minutes);
  return `${pad(Math.floor(total / 60))}:${pad(total % 60)}`;
}

function minutesBetween(from, to) {
  const minutes = (t) => Number(t.slice(0, 2)) * 60 + Number(t.slice(3, 5));
  return minutes(to) - minutes(from);
}

function dateTitle(iso) {
  return iso.slice(0, 4) === TODAY.slice(0, 4) ? formatDate(iso) : `${formatDate(iso)} ${iso.slice(0, 4)}`;
}

/* Data writes */

export function syncDerived(r) {
  if (!r.multiDay) r.endDate = r.date;
  r.start = `${r.date}T${r.startTime}:00`;
  r.end = `${r.endDate}T${r.endTime}:00`;
  r.isToday = r.date <= TODAY && TODAY <= r.endDate;
  r.isPast = r.end < NOW;
  r.address = getLocation(r.location)?.address ?? null;
  r.roles = r.roleIds.map(getRole).filter(Boolean).map(({ id, name, tagColor, icon }) => ({ id, name, tagColor, icon }));
  r.groupsText = ui.groupsSentence(r.instrumentIds);
  r.groups = compressGroups(r.instrumentIds);
  for (const item of r.scheduleItems) {
    if (item.date < r.date) item.date = r.date;
    else if (item.date > r.endDate) item.date = r.endDate;
  }
}

function leavesOf(section) {
  return section.instrumentIds?.length ? section.instrumentIds : [section.id];
}

function sortRehearsals() {
  rehearsals.sort((a, b) => a.start.localeCompare(b.start));
}

function refreshTabbar() {
  const role = store.get('role');
  if (TABS[role]) renderTabbar(TABS[role]);
}

function colorName(value) {
  if (!value || value.toLowerCase() === NO_COLOR) return 'Keine';
  return colors.find((c) => c.value.toLowerCase() === value.toLowerCase())?.name ?? 'Eigene';
}

function refersTo(hash, id) {
  return new RegExp(`(?:/|[?&](?:probe|edit|highlight)=)${id}(?!\\d)`).test(hash);
}

const entries = new Map();
const faults = new Map();

function editing(id) {
  return new RegExp(`(?:[?&]edit=${id}(?!\\d)|/plan/${id}/edit)`).test(location.hash);
}

function dropEmpty(list, text) {
  for (let i = list.length - 1; i >= 0; i -= 1) {
    if (!text(list[i]).trim()) list.splice(i, 1);
  }
}

function pruneEmpty(r) {
  dropEmpty(r.scheduleItems, (item) => item.title);
  dropEmpty(r.infos, (info) => info.text);
}

function forget(id) {
  entries.delete(id);
  faults.delete(id);
}

export function leaveEditors() {
  for (const id of [...entries.keys()]) {
    if (editing(id)) continue;
    const r = getRehearsal(id);
    if (r) pruneEmpty(r);
    forget(id);
  }
  settleNew();
}

function segQuery(r) {
  return r.isPast ? 'seg=past&' : '';
}

function inPlan() {
  return location.hash.startsWith('#/plan/');
}

function listPath() {
  return inPlan() ? '/plan' : '/rehearsals';
}

function editPath(st, r) {
  if (inPlan() && !st.embedded) return `/plan/${r.id}/edit`;
  return `/rehearsals?${segQuery(r)}edit=${r.id}`;
}

async function flashList(ids) {
  try {
    (await import('./rehearsals.js')).flashRehearsals?.(ids);
  } catch {}
}

function afterRemoval(id) {
  if (refersTo(location.hash, id)) navigate(listPath(), { replace: true, direction: 'none' });
  else rerender();
}

/* Editor */

let activeEditor = null;
const drafts = new WeakMap();
const settled = new WeakSet();

function isDraft(r, ctx) {
  if (ctx.query?.new !== '1' || settled.has(r)) return false;
  return r.draft ?? !promises.some((p) => p.rehearsalId === r.id && p.status !== 'pending');
}

export function renderEditor(root, id, ctx, { embedded = false } = {}) {
  const r = getRehearsal(id);
  const desk = embedded || ui.isWide();
  root.classList.add('editor');
  if (desk) {
    rowsMode();
    root.classList.add('proben-desk');
  }

  if (!r) {
    if (!embedded) setHeader({ title: 'Probe', back: true, large: false });
    root.replaceChildren(ui.emptyState({
      icon: 'calendar-x',
      title: 'Probe nicht gefunden',
      action: { label: 'Zu den Proben', href: '#/rehearsals' },
    }));
    refreshIcons(root);
    return;
  }

  const isNew = isDraft(r, ctx);
  if (isNew && !drafts.has(r)) drafts.set(r, { touched: false, done: false });
  const st = {
    r,
    root,
    ctx,
    embedded,
    desk,
    isNew,
    state: isNew ? drafts.get(r) : null,
    textTimer: 0,
    draft: null,
    entry: entries.get(r.id) ?? entries.set(r.id, structuredClone(r)).get(r.id),
    focus: null,
  };
  resetDraft(st);
  if (!embedded) activeEditor = st;
  paint(st);
}

function touch(st) {
  if (st.state) st.state.touched = true;
}

function settleNew() {
  const st = activeEditor;
  if (!st || editing(st.r.id)) return;
  activeEditor = null;
  if (!st.isNew) return;
  const { r } = st;
  const state = drafts.get(r);
  drafts.delete(r);
  settled.add(r);
  if (!state || !rehearsals.includes(r)) return;
  if (!state.done && !state.touched) {
    removeRehearsal(r);
    return;
  }
  createdToast(r);
}

function createdToast(r) {
  withUndo(html`${icon('circle-check')} Probe angelegt`, () => {
    removeRehearsal(r);
    afterRemoval(r.id);
  });
}

function resetDraft(st) {
  const { r } = st;
  st.draft = { date: r.date, endDate: r.endDate, startTime: r.startTime, endTime: r.endTime, multiDay: r.multiDay };
}

function barTitle(st) {
  return st.isNew ? 'Neue Probe' : 'Probe bearbeiten';
}

function savedToast() {
  toast('Gespeichert', { icon: 'circle-check' });
}

function leaveEditor(st) {
  if (st.embedded) navigate(`/rehearsals/${st.r.id}${st.r.isPast ? '?seg=past' : ''}`, { replace: true, direction: 'none' });
  else back();
}

function cancelEdit(st) {
  if (JSON.stringify(st.r) !== JSON.stringify(st.entry)) {
    Object.assign(st.r, structuredClone(st.entry));
    sortRehearsals();
    refreshTabbar();
  }
  forget(st.r.id);
  leaveEditor(st);
}

function cancelOrDiscard(st) {
  if (st.isNew) discardNew(st);
  else cancelEdit(st);
}

function blocked(st) {
  return faults.has(st.r.id);
}

function finish(st) {
  if (blocked(st)) return false;
  pruneEmpty(st.r);
  forget(st.r.id);
  if (st.state) st.state.done = true;
  else savedToast();
  return true;
}

function applyHeader(st) {
  if (st.desk) {
    document.title = `${barTitle(st)} · Probenplaner`;
    return;
  }
  setHeader({
    title: barTitle(st),
    back: false,
    leading: h('button', { class: 'app-header__action is-text', type: 'button', onclick: () => cancelOrDiscard(st) }, 'Abbrechen'),
    large: false,
    actions: [{
      label: st.isNew ? 'Anlegen' : 'Fertig',
      text: true,
      primary: true,
      disabled: blocked(st),
      onClick: () => {
        if (finish(st)) back();
      },
    }],
  });
}

function paint(st) {
  const { root, desk } = st;
  root.classList.add('editor--cards');
  applyHeader(st);
  root.replaceChildren(...[
    desk ? deskBar(st) : null,
    h('div', { class: 'editor-head-slot' }),
    h('div', { class: 'editor-desk' }),
  ].filter(Boolean));
  repaintCard(st);
  paintBody(st);
}

function paintBody(st) {
  const { root, desk, r } = st;
  const body = root.querySelector('.editor-desk');
  if (!body) return;
  if (desk) {
    const bar = deskBar(st);
    root.querySelector('.editor-bar')?.replaceWith(bar);
    refreshIcons(bar);
  }
  body.classList.toggle('is-days', Boolean(r.multiDay));
  body.replaceChildren(...[
    h('div', { class: 'editor-desk__agenda' }, agendaCards(st)),
    h('div', { class: 'editor-desk__side' }, infosCard(st)),
    desk ? null : h('section', { class: 'editor-card editor-card--more' }, rolesGroup(st), tagsGroup(st)),
    desk ? null : dangerGroup(st),
  ].filter(Boolean));
  refreshIcons(body);
  requestAnimationFrame(() => body.querySelectorAll('.editor__text').forEach(fitText));
}

function repaintCard(st) {
  const holder = st.root.querySelector('.editor-head-slot');
  if (!holder) return;
  const focused = document.activeElement?.closest?.('.editor-head [data-key]')?.dataset.key;
  holder.replaceChildren(editHead(st));
  refreshIcons(holder);
  if (focused && !holder.contains(document.activeElement)) holder.querySelector(`[data-key="${focused}"]`)?.focus({ preventScroll: true });
}

function saved(st, { head = true } = {}) {
  touch(st);
  syncDerived(st.r);
  applyHeader(st);
  if (head) repaintCard(st);
  refreshTabbar();
}

/* Toolbar */

function deskBar(st) {
  const done = () => {
    if (!finish(st)) return;
    leaveEditor(st);
    if (st.embedded && st.isNew) {
      settled.add(st.r);
      createdToast(st.r);
    }
  };
  const tool = (label, iconName, open, { danger = false } = {}) => h('button', {
    class: ['act-bar__icon', danger && 'is-danger'],
    type: 'button',
    'aria-label': label,
    'data-tip': label,
    onclick: () => open(st),
  }, icon(iconName));
  const past = st.r.isPast && !st.r.isToday;
  const danger = [
    st.isNew || past ? null
      : st.r.cancelled ? tool('Absage zurücknehmen', 'rotate-ccw', openUncancelSheet)
        : tool('Probe absagen', 'calendar-x', openCancelSheet, { danger: true }),
    st.isNew ? null : tool('Probe löschen', 'trash-2', openDeleteSheet, { danger: true }),
  ].filter(Boolean);
  return h('div', { class: 'proben-toolbar editor-bar' },
    h('h1', { class: 'proben-toolbar__title editor-bar__title' }, barTitle(st)),
    h('div', { class: 'proben-toolbar__spacer' }),
    st.isNew ? null : h('div', { class: 'act-bar__tools editor-bar__tools' }, tool('Duplizieren', 'copy', openDuplicateSheet)),
    danger.length ? h('div', { class: 'act-bar__danger editor-bar__danger' }, danger) : null,
    h('button', {
      class: 'button button--plain editor-bar__cancel',
      type: 'button',
      onclick: () => cancelOrDiscard(st),
    }, 'Abbrechen'),
    h('button', { class: 'button button--primary editor-bar__done', type: 'button', disabled: blocked(st), onclick: done }, st.isNew ? 'Anlegen' : 'Fertig'));
}

function syncBlocked(st) {
  applyHeader(st);
  const done = st.root.querySelector('.editor-bar__done');
  if (done) done.disabled = blocked(st);
}

function setFault(st, key, value) {
  const id = st.r.id;
  const fault = { ...faults.get(id) };
  if (value == null) delete fault[key];
  else fault[key] = value;
  if (Object.keys(fault).length) faults.set(id, fault);
  else faults.delete(id);
}

const TIME_FAULT = 'Ende muss nach Beginn liegen';
const DEADLINE_FAULT = 'Antwort bis muss vor dem Beginn liegen';

function paintTimeFault(st) {
  const head = st.root.querySelector('.editor-head');
  if (!head) return;
  const on = Boolean(faults.get(st.r.id)?.times);
  const message = head.querySelector('.editor-head__error');
  if (message) message.hidden = !on;
  const end = head.querySelector('[data-time="endTime"]');
  if (end) {
    markInvalid(end, on);
    if (on) end.setAttribute('aria-describedby', 'editTimeError');
    else end.removeAttribute('aria-describedby');
  }
  syncBlocked(st);
}

/* Head card: the rehearsal itself, every value is a control */

function timeValue(st, key) {
  return faults.get(st.r.id)?.times?.[key] ?? st.r[key];
}

function timeField(st, key) {
  const { r } = st;
  return timeInput({
    key,
    value: timeValue(st, key),
    label: key === 'startTime' ? 'Beginn' : 'Ende',
    invalid: key === 'endTime' && Boolean(faults.get(r.id)?.times),
    describedBy: key === 'endTime' ? 'editTimeError' : null,
    onChange: (value) => {
      const pending = faults.get(r.id)?.times;
      const next = { startTime: timeValue(st, 'startTime'), endTime: timeValue(st, 'endTime'), [key]: value };
      if (key === 'startTime' && !r.multiDay && !pending) next.endTime = addMinutes(value, minutesBetween(r.startTime, r.endTime));
      if (`${r.multiDay ? r.endDate : r.date}T${next.endTime}` <= `${r.date}T${next.startTime}`) {
        setFault(st, 'times', next);
        haptic('error');
        paintTimeFault(st);
        return;
      }
      setFault(st, 'times', null);
      if (next.startTime === r.startTime && next.endTime === r.endTime) {
        paintTimeFault(st);
        return;
      }
      const before = snapshot(r, ['startTime', 'endTime']);
      Object.assign(r, next);
      saved(st, { head: false });
      const other = st.root.querySelector(`.editor-head [data-time="${key === 'startTime' ? 'endTime' : 'startTime'}"]`);
      if (other && other.value !== r[other.dataset.time]) other.value = r[other.dataset.time];
      paintTimeFault(st);
      sortRehearsals();
      paintBody(st);
      undoable(st, 'Uhrzeit geändert', before);
    },
  });
}

function deadlineLimit(r) {
  return `${r.date}T${r.startTime}`;
}

function setDeadline(st, value) {
  const { r } = st;
  setFault(st, 'deadline', null);
  if ((r.deadline ?? null) === value) {
    saved(st);
    syncBlocked(st);
    return;
  }
  const before = snapshot(r, ['deadline']);
  r.deadline = value;
  saved(st);
  syncBlocked(st);
  undoable(st, 'Antwortfrist geändert', before);
}

function deadlineField(st) {
  const { r } = st;
  const fault = faults.get(r.id)?.deadline ?? null;
  const value = fault ?? (r.deadline ? r.deadline.slice(0, 16) : '');
  const text = value ? deadlineText({ deadline: `${value}:00` }) : 'Keine';
  const input = h('input', {
    class: 'value-pill__native',
    type: 'datetime-local',
    value,
    max: deadlineLimit(r),
    'aria-label': 'Antwort bis',
    'aria-invalid': fault ? 'true' : null,
    'aria-describedby': fault ? 'editDeadlineError' : null,
    onclick: pickerOnClick,
    onchange: (event) => {
      const next = event.target.value;
      if (next && next > deadlineLimit(r)) {
        setFault(st, 'deadline', next);
        haptic('error');
        repaintCard(st);
        syncBlocked(st);
        return;
      }
      setDeadline(st, next ? `${next}:00` : null);
    },
  });
  return h('div', { class: ['edit-select-group', fault && 'is-invalid'] },
    selectField({ label: 'Antwort bis', text, as: 'label', extra: input, key: 'deadline', className: 'edit-select--date' }),
    value ? h('button', {
      class: 'edit-select__clear',
      type: 'button',
      'aria-label': 'Antwortfrist entfernen',
      title: 'Antwortfrist entfernen',
      onclick: () => {
        haptic('light');
        setDeadline(st, null);
      },
    }, icon('x')) : null,
    fault ? fieldError(DEADLINE_FAULT, 'editDeadlineError') : null);
}

function dayPrefix(r, iso) {
  return r.multiDay ? h('span', { class: 'editor-head__wd' }, formatDate(iso).split(',')[0]) : null;
}

function editHead(st) {
  const { r } = st;
  const past = r.isPast && !r.isToday;
  const tinted = Boolean(r.color) && r.color.toLowerCase() !== NO_COLOR;
  const place = [r.location, r.room].filter(Boolean).join(' · ');
  const date = rowDate(r);
  const openWhen = () => {
    haptic('selection');
    openWhenSheet(st);
  };
  return h('section', { class: ['editor-head', r.cancelled && 'is-cancelled', !r.cancelled && past && 'is-past'] },
    h('button', { class: 'editor-head__day', type: 'button', style: ui.rowTint(r), 'aria-label': `Datum: ${date}`, onclick: openWhen }, ui.isWide() ? [weekdayAnchor(r), dayNumber(r)] : ui.tintedChip(r)),
    h('div', { class: 'editor-head__when' },
      h('button', { class: 'edit-value editor-head__date', type: 'button', 'aria-label': `Datum: ${date}`, onclick: openWhen },
        h('span', { class: 'edit-value__text' }, date)),
      h('div', { class: 'editor-head__times' },
        dayPrefix(r, r.date), timeField(st, 'startTime'), h('span', { class: 'editor-head__dash' }, '–'), dayPrefix(r, r.endDate), timeField(st, 'endTime')),
      fieldError(TIME_FAULT, 'editTimeError', !faults.get(r.id)?.times, 'editor-head__error')),
    h('div', { class: 'editor-head__fields' },
      r.cancelled ? h('span', { class: 'badge badge--no editor-head__off' }, icon('ban'), h('span', { class: 'badge__label' }, r.cancelReason ? `Fällt aus: ${r.cancelReason}` : 'Fällt aus')) : null,
      selectField({
        label: 'Art',
        text: r.type,
        key: 'type',
        popup: 'menu',
        className: tinted ? 'edit-select--kind is-tinted' : 'edit-select--kind',
        style: tinted ? `--tag:${r.color}` : null,
        onClick: (anchor) => openTypePicker(st, anchor),
      }),
      selectField({ label: 'Besetzung', text: r.groupsText, key: 'groups', onClick: (anchor) => openGroupsSheet(st, anchor) }),
      selectField({ label: 'Ort', text: place, key: 'location', className: 'edit-select--wide', onClick: (anchor) => openLocationPicker(st, anchor) }),
      deadlineField(st),
      selectField({
        label: 'Farbe',
        key: 'color',
        lead: h('span', { class: ['color-dot', 'edit-select__dot', !tinted && 'color-dot--none'], style: tinted ? `--dot:${r.color}` : null }),
        text: tinted ? colorName(r.color) : 'Keine Farbe',
        onClick: (anchor) => openColorSheet(st, anchor),
      })),
    st.desk ? h('div', { class: 'editor-head__more' }, rolesGroup(st), tagsGroup(st)) : null);
}

function refresh(st) {
  sortRehearsals();
  if (st.root.isConnected) {
    saved(st);
    paintBody(st);
  } else {
    syncDerived(st.r);
    refreshTabbar();
    rerender();
  }
}

function snapshot(r, fields) {
  return Object.fromEntries(fields.map((key) => [key, structuredClone(r[key])]));
}

function undoable(st, message, before) {
  const { r } = st;
  if (Object.keys(before).every((key) => JSON.stringify(r[key]) === JSON.stringify(before[key]))) return;
  withUndo(message, () => {
    Object.assign(r, structuredClone(before));
    refresh(st);
  });
}

function savedText(st) {
  touch(st);
  clearTimeout(st.textTimer);
  st.textTimer = setTimeout(() => syncDerived(st.r), 600);
}

/* Cards */

function editorCard(className, title, add, rows) {
  return h('section', { class: ['editor-card', className] },
    h('div', { class: 'editor-card__head' },
      h('h2', { class: 'editor-card__title' }, title),
      h('button', { class: 'editor-card__add', type: 'button', 'aria-label': add.label, onclick: add.onClick }, h('span', { class: 'editor-card__plus' }, icon('plus')), add.short ?? 'Hinzufügen')),
    rows.length ? h('div', { class: 'editor-card__rows' }, rows) : null);
}

function removeButton(label, onClick) {
  return h('button', { class: 'icon-button editor__remove', type: 'button', 'aria-label': label, onclick: onClick }, icon('circle-minus'));
}

function sortAgenda(r) {
  r.scheduleItems.sort((a, b) => `${a.date}T${a.time}`.localeCompare(`${b.date}T${b.time}`));
}

function fitText(el) {
  if (!el.offsetParent) return;
  el.style.height = 'auto';
  el.style.height = `${el.scrollHeight}px`;
}

function textField({ value, placeholder, label, maxlength, onInput, onCommit }) {
  return h('textarea', {
    class: 'editor__text',
    rows: '1',
    placeholder,
    maxlength: String(maxlength),
    'aria-label': label,
    enterkeyhint: 'done',
    onkeydown: (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        event.target.blur();
      }
    },
    oninput: (event) => {
      onInput(event.target.value.replace(/\n/g, ' '));
      fitText(event.target);
    },
    onchange: () => onCommit?.(),
  }, value);
}

function withUndo(message, undo) {
  toast(message, { duration: 5000, icon: typeof message === 'string' ? 'circle-check' : undefined, action: { label: 'Rückgängig', onClick: undo } });
}

function spanDays(from, to) {
  const days = [];
  for (let d = from; d <= to && days.length < 62; d = addDays(d, 1)) days.push(d);
  return days;
}

function itemLeaves(item) {
  return [...new Set(item.groups.flatMap((g) => (getSection(g) ? leavesOf(getSection(g)) : [g])))];
}

function openItemGroups(st, item) {
  const { r } = st;
  ui.openBesetzungSheet({
    title: 'Besetzung',
    value: item.groups?.length ? itemLeaves(item) : r.instrumentIds,
    onDone: (next) => {
      const chosen = next.filter((id) => r.instrumentIds.includes(id));
      if (!chosen.length) return;
      const all = chosen.length === r.instrumentIds.length;
      const groups = all ? null : compressGroups(chosen);
      if (JSON.stringify(groups) === JSON.stringify(item.groups ?? null)) return;
      const before = snapshot(r, ['scheduleItems']);
      item.groups = groups;
      saved(st);
      paintBody(st);
      undoable(st, 'Besetzung geändert', before);
    },
  });
}

function agendaCards(st) {
  const { r } = st;

  const addItem = (date) => {
    const last = r.scheduleItems.filter((x) => x.date === date).at(-1);
    const item = { date, time: last ? addMinutes(last.time, 30) : date === r.date ? r.startTime : '10:00', title: '', groups: null };
    r.scheduleItems.push(item);
    sortAgenda(r);
    st.focus = item;
    saved(st);
    paintBody(st);
  };

  const moveItem = (item, step, via) => {
    const day = r.scheduleItems.filter((x) => x.date === item.date);
    const other = day[day.indexOf(item) + step];
    if (!other) return;
    haptic('selection');
    [item.time, other.time] = [other.time, item.time];
    const a = r.scheduleItems.indexOf(item);
    const b = r.scheduleItems.indexOf(other);
    [r.scheduleItems[a], r.scheduleItems[b]] = [other, item];
    sortAgenda(r);
    st.moved = { item, via };
    saved(st);
    paintBody(st);
  };

  const moveButton = (item) => {
    const day = r.scheduleItems.filter((x) => x.date === item.date);
    if (day.length < 2) return h('span', { class: 'editor-row__move is-empty', 'aria-hidden': 'true' });
    const at = day.indexOf(item);
    const name = item.title || 'Ablaufpunkt';
    return h('button', {
      class: 'editor-row__move',
      type: 'button',
      title: 'Verschieben',
      'aria-label': `${name} verschieben`,
      'aria-haspopup': 'menu',
      'aria-keyshortcuts': 'ArrowUp ArrowDown',
      onclick: (event) => openMenu(event.currentTarget, [
        { label: 'Nach oben', icon: 'arrow-up', disabled: at === 0, onSelect: () => moveItem(item, -1, 'move') },
        { label: 'Nach unten', icon: 'arrow-down', disabled: at === day.length - 1, onSelect: () => moveItem(item, 1, 'move') },
      ], { label: `${name} verschieben`, minWidth: 160 }),
      onkeydown: (event) => {
        if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') return;
        event.preventDefault();
        moveItem(item, event.key === 'ArrowUp' ? -1 : 1, 'move');
      },
    }, icon('arrow-up-down'));
  };

  const itemRow = (item) => {
    const time = h('input', {
      class: 'value-pill__native',
      type: r.multiDay ? 'datetime-local' : 'time',
      value: r.multiDay ? `${item.date}T${item.time}` : item.time,
      min: r.multiDay ? `${r.date}T00:00` : null,
      max: r.multiDay ? `${r.endDate}T23:59` : null,
      'aria-label': `${r.multiDay ? 'Tag und Uhrzeit' : 'Uhrzeit'} für ${item.title || 'Ablaufpunkt'}`,
      title: r.multiDay ? 'Tag und Uhrzeit' : 'Uhrzeit',
      onclick: pickerOnClick,
      onchange: (event) => {
        const value = event.target.value;
        if (!value) return;
        if (r.multiDay) {
          const [date, clock] = value.split('T');
          if (date < r.date || date > r.endDate) return;
          item.date = date;
          item.time = clock.slice(0, 5);
        } else {
          item.time = value;
        }
        sortAgenda(r);
        st.moved = { item, via: 'time' };
        saved(st);
        paintBody(st);
      },
    });
    const all = !item.groups?.length;
    const groupText = all ? 'Alle' : ui.groupsSentence(itemLeaves(item));
    const field = textField({
      value: item.title,
      placeholder: 'Ablaufpunkt',
      label: 'Ablaufpunkt',
      maxlength: 80,
      onInput: (text) => {
        item.title = text;
        savedText(st);
      },
    });
    if (st.focus === item) {
      st.focus = null;
      requestAnimationFrame(() => field.focus());
    }
    const moved = st.moved?.item === item ? st.moved.via : null;
    const row = h('div', { class: ['editor-row', moved && 'is-moved'] },
      h('label', { class: 'value-pill editor-row__time' }, item.time, time),
      field,
      h('button', {
        class: ['editor-row__group', all && 'is-all'],
        type: 'button',
        title: `Besetzung: ${groupText}`,
        'aria-label': `Besetzung für ${item.title || 'Ablaufpunkt'}: ${groupText}`,
        onclick: () => {
          haptic('selection');
          openItemGroups(st, item);
        },
      }, all ? icon('users-round') : h('span', { class: 'edit-value__text' }, groupText)),
      moveButton(item),
      removeButton(`${item.title || 'Ablaufpunkt'} entfernen`, () => {
        const index = r.scheduleItems.indexOf(item);
        r.scheduleItems.splice(index, 1);
        saved(st);
        paintBody(st);
        withUndo('Ablaufpunkt entfernt', () => {
          r.scheduleItems.splice(index, 0, item);
          refresh(st);
        });
      }));
    if (moved) {
      st.moved = null;
      requestAnimationFrame(() => {
        row.scrollIntoView({ block: 'nearest' });
        row.querySelector(moved === 'move' ? '.editor-row__move' : '.editor-row__time input')?.focus({ preventScroll: true });
      });
    }
    return row;
  };

  if (!r.multiDay) {
    return [editorCard('editor-card--agenda', 'Ablauf', { label: 'Ablaufpunkt hinzufügen', short: 'Ablaufpunkt hinzufügen', onClick: () => addItem(r.date) }, r.scheduleItems.map(itemRow))];
  }
  return spanDays(r.date, r.endDate).map((date) => editorCard(
    'editor-card--agenda',
    formatDate(date),
    { label: `Ablaufpunkt am ${formatDate(date)} hinzufügen`, short: 'Ablaufpunkt hinzufügen', onClick: () => addItem(date) },
    r.scheduleItems.filter((item) => item.date === date).map(itemRow),
  ));
}

function openHintEmoji(st, info) {
  const sheet = ui.openEmojiSheet(info.emoji, (emoji) => {
    info.emoji = emoji;
    saved(st);
    paintBody(st);
  });
  markUsedEmojis(sheet, st.r.infos.map((x) => x.emoji));
}

function infosCard(st) {
  const { r } = st;
  const rows = r.infos.map((info, index) => {
    const field = textField({
      value: info.text,
      placeholder: 'Hinweis',
      label: 'Hinweis',
      maxlength: 160,
      onInput: (text) => {
        info.text = text;
        savedText(st);
      },
    });
    if (st.focus === info) {
      st.focus = null;
      requestAnimationFrame(() => field.focus());
    }
    return h('div', { class: 'editor-row editor-row--hint' },
      h('button', {
        class: 'editor__emoji-btn',
        type: 'button',
        'aria-label': `Emoji ${info.emoji || '📝'} ändern`,
        onclick: () => openHintEmoji(st, info),
      }, ui.infoPill(info.emoji || '📝', { size: 'lg' })),
      field,
      removeButton('Hinweis entfernen', () => {
        const removed = r.infos.splice(index, 1)[0];
        saved(st);
        paintBody(st);
        withUndo('Hinweis entfernt', () => {
          r.infos.splice(index, 0, removed);
          refresh(st);
        });
      }));
  });

  return editorCard('editor-card--infos', 'Hinweise', {
    label: 'Hinweis hinzufügen',
    onClick: () => {
      const info = { emoji: '📝', text: '' };
      r.infos.push(info);
      st.focus = info;
      saved(st);
      paintBody(st);
    },
  }, rows);
}

function rolesGroup(st) {
  const { r } = st;
  const list = roles
    .filter((role) => !role.isDefault && (!role.isSystem || r.roleIds.includes(role.id)))
    .sort((a, b) => (a.sortOrder ?? 0) - (b.sortOrder ?? 0));
  const changed = () => {
    touch(st);
    syncDerived(r);
    applyHeader(st);
    sync();
  };
  const all = ui.roleChip({ name: 'Alle', icon: 'users-round', tagColor: 'var(--accent)' }, {
    names: true,
    selected: !r.roleIds.length,
    onClick: () => {
      if (!r.roleIds.length) return;
      haptic('selection');
      r.roleIds = [];
      changed();
    },
  });
  const chips = list.map((role) => {
    const el = ui.roleChip(role, {
      names: true,
      selected: r.roleIds.includes(role.id),
      onClick: () => {
        haptic('selection');
        r.roleIds = r.roleIds.includes(role.id)
          ? r.roleIds.filter((x) => x !== role.id)
          : [...r.roleIds, role.id].sort((a, b) => a - b);
        changed();
      },
    });
    el.dataset.role = role.id;
    return el;
  });
  const sync = () => {
    ui.setPressed(all, !r.roleIds.length);
    for (const el of chips) ui.setPressed(el, r.roleIds.includes(Number(el.dataset.role)));
  };
  return h('div', { class: 'editor-head__group editor__roles', role: 'group', 'aria-label': 'Sichtbar für' },
    h('span', { class: 'editor-head__label' }, 'Sichtbar für'), all, chips);
}

function tagsGroup(st) {
  const { r } = st;
  return h('div', { class: 'editor-head__group editor__tags' },
    h('span', { class: 'editor-head__label' }, 'Tags'),
    r.tags.map((tag, index) => h('span', { class: 'chip editor__tag' },
      h('span', { class: 'chip__label' }, tag),
      h('button', {
        class: 'chip__remove',
        type: 'button',
        'aria-label': `${tag} entfernen`,
        onclick: () => {
          const before = snapshot(r, ['tags']);
          r.tags.splice(index, 1);
          haptic('light');
          saved(st);
          paintBody(st);
          undoable(st, 'Tag entfernt', before);
        },
      }, icon('x')))),
    h('button', { class: 'chip editor__tag-add', type: 'button', onclick: () => openTagSheet(st) }, icon('plus'), h('span', { class: 'chip__label' }, 'Tag')));
}

function actionRow(iconName, label, onClick, { destructive = false } = {}) {
  return h('button', {
    class: ['list-row', destructive ? 'list-row--destructive' : 'list-row--action'],
    type: 'button',
    onclick: onClick,
  },
  h('span', { class: 'list-row__lead' }, icon(iconName)),
  h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, label)));
}

function dangerGroup(st) {
  return h('div', { class: 'list-group editor__danger' },
    h('button', {
      class: 'list-row list-row--destructive',
      type: 'button',
      onclick: () => (st.isNew ? discardNew(st) : openDeleteSheet(st)),
    }, h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, st.isNew ? 'Neue Probe verwerfen' : 'Probe löschen'))));
}

/* Sheets */

const inRange = (date) => date >= addDays(TODAY, -730) && date <= addDays(TODAY, 730);

function isValidDraft(d) {
  return inRange(d.date) && `${d.multiDay ? d.endDate : d.date}T${d.endTime}` > `${d.date}T${d.startTime}`;
}

function openWhenSheet(st) {
  resetDraft(st);
  const d = st.draft;
  const before = snapshot(st.r, ['date', 'endDate', 'startTime', 'endTime', 'multiDay', 'scheduleItems', 'deadline']);
  const inputs = {};
  let done = null;

  const sync = () => {
    for (const [key, el] of Object.entries(inputs)) {
      if (el !== document.activeElement && el.value !== d[key]) el.value = d[key];
    }
    const invalid = inRange(d.date) && !isValidDraft(d);
    markInvalid(inputs.date, !inRange(d.date));
    markInvalid(inputs.endTime, invalid);
    markInvalid(inputs.endDate, invalid);
    if (!d.multiDay) untilRow.remove();
    else if (!untilRow.isConnected) list.append(untilRow);
    if (done) done.disabled = !isValidDraft(d);
  };
  const commit = () => {
    if (!d.multiDay) d.endDate = d.date;
    else if (d.endDate <= d.date) d.endDate = addDays(d.date, 1);
    if (isValidDraft(d)) {
      const { r } = st;
      const shift = daysBetween(r.date, d.date);
      Object.assign(r, { date: d.date, endDate: d.endDate, startTime: d.startTime, endTime: d.endTime, multiDay: d.multiDay });
      if (shift) for (const item of r.scheduleItems) item.date = addDays(item.date, shift);
      if (shift && r.deadline) r.deadline = `${addDays(r.deadline.slice(0, 10), shift)}${r.deadline.slice(10)}`;
      saved(st);
      sortAgenda(r);
      sortRehearsals();
      paintBody(st);
    } else {
      haptic('error');
    }
    sync();
  };
  const set = (key) => (value) => {
    if (key === 'startTime' && !d.multiDay && isValidDraft(d)) {
      d.endTime = addMinutes(value, minutesBetween(d.startTime, d.endTime));
    }
    if (key === 'date' && d.multiDay) d.endDate = addDays(value, Math.max(1, daysBetween(d.date, d.endDate)));
    d[key] = value;
    commit();
  };
  const field = (title, key, type) => {
    inputs[key] = nativeField({ type, value: d[key], label: title, onInput: set(key) });
    return fieldRow(title, inputs[key]);
  };

  const untilRow = field('Bis', 'endDate', 'date');
  const list = h('div', { class: 'list-group' },
    field('Datum', 'date', 'date'),
    field('Beginn', 'startTime', 'time'),
    field('Ende', 'endTime', 'time'),
    h('button', {
      class: 'list-row editor__multi',
      type: 'button',
      role: 'checkbox',
      'aria-checked': String(d.multiDay),
      onclick: (event) => {
        haptic('light');
        d.multiDay = !d.multiDay;
        event.currentTarget.setAttribute('aria-checked', String(d.multiDay));
        event.currentTarget.querySelector('.check').classList.toggle('is-checked', d.multiDay);
        d.endDate = d.multiDay ? addDays(d.date, 1) : d.date;
        commit();
      },
    },
    h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Mehrtägig')),
    h('span', { class: ['check', d.multiDay && 'is-checked'], 'aria-hidden': 'true' }, icon('check'))));

  const sheet = openSheet({
    title: 'Datum & Uhrzeit',
    content: h('div', { class: 'editor__when' }, list),
    actions: [{ label: 'Fertig', variant: 'primary', onClick: () => isValidDraft(d) }],
    onClose: () => {
      const { r } = st;
      const moved = ['date', 'endDate', 'multiDay'].some((key) => r[key] !== before[key]);
      undoable(st, moved ? 'Datum geändert' : 'Uhrzeit geändert', before);
    },
  });
  done = sheet.el.querySelector('.sheet__actions .button--primary');
  sync();
  return sheet;
}

function openTypePicker(st, anchor) {
  const { r } = st;
  pickType(r.type, (value) => {
    const name = value.trim();
    if (!name || name === r.type) return;
    const before = snapshot(r, ['type']);
    r.type = name;
    saved(st);
    undoable(st, 'Art geändert', before);
  }, anchor);
}

function setLocation(st, name, room = null) {
  const { r } = st;
  if (name === r.location && (room || null) === (r.room ?? null)) return;
  const before = snapshot(r, ['location', 'room']);
  addPlace(name);
  r.location = name;
  r.room = room || null;
  saved(st);
  undoable(st, 'Ort geändert', before);
}

function placeRow({ title, sub, iconName, checked = false, onPick }) {
  return h('button', {
    class: ['list-row', sub && 'list-row--2line', checked && 'is-checked'],
    type: 'button',
    role: 'option',
    'aria-selected': String(checked),
    onclick: () => {
      haptic('selection');
      onPick();
    },
  },
  h('span', { class: 'list-row__lead' }, icon(iconName)),
  h('span', { class: 'list-row__body' },
    h('span', { class: 'list-row__title' }, title),
    sub ? h('span', { class: 'list-row__sub' }, sub) : null),
  h('span', { class: 'list-row__trail' }, checked ? icon('check', { class: 'list-row__check' }) : null));
}

function openLocationPicker(st, anchor) {
  const { r } = st;
  pickPlace({
    anchor,
    location: r.location,
    room: r.room,
    address: r.address,
    onPick: (name, room) => setLocation(st, name, room),
  });
}

export function pickPlace({ location, room: currentRoom = null, address = null, onPick, anchor = null }) {
  const norm = (text) => text.trim().toLowerCase();
  const places = locations.map((l) => ({ name: l.name, address: l.address }));
  if (location && !places.some((p) => p.name === location)) places.unshift({ name: location, address });
  const group = h('div', { class: 'list-group action-list editor__places', role: 'listbox', 'aria-label': 'Ort' });
  let sheet = null;
  let query = '';
  let picked = false;
  let touched = false;

  const typedRoom = () => room.value.trim() || null;
  const roomFor = (name) => (touched ? typedRoom() : name === location ? currentRoom : null);
  const pick = (name, extra) => {
    picked = true;
    onPick(name, extra === undefined ? roomFor(name) : extra);
    sheet.close();
  };

  const build = () => {
    const q = norm(query);
    const rows = [];
    if (q && !places.some((p) => norm(p.name) === q)) {
      const [head, ...tail] = query.split(',');
      const room = tail.join(',').trim();
      const base = room && norm(head).length >= 3 ? places.find((p) => norm(p.name).includes(norm(head))) : null;
      if (base) {
        rows.push(placeRow({ title: `${base.name} · ${room}`, sub: base.address, iconName: 'map-pin', onPick: () => pick(base.name, room) }));
      }
      rows.push(placeRow({ title: `„${query.trim()}“ anlegen`, iconName: 'map-pin-plus', onPick: () => pick(query.trim()) }));
    }
    for (const p of places) {
      if (q && !`${p.name} ${p.address ?? ''}`.toLowerCase().includes(q)) continue;
      const current = p.name === location;
      rows.push(placeRow({ title: p.name, sub: p.address, iconName: 'map-pin', checked: current, onPick: () => pick(p.name) }));
    }
    if (!q) {
      rows.push(placeRow({
        title: 'Anderer Ort',
        iconName: 'map-pin-plus',
        onPick: () => {
          const extra = roomFor(null);
          picked = true;
          sheet.close();
          setTimeout(() => openOtherPlace((text) => onPick(text, extra)), 300);
        },
      }));
    }
    group.replaceChildren(...rows);
    refreshIcons(group);
  };

  const room = h('input', {
    class: 'input editor__room-input',
    type: 'text',
    value: currentRoom ?? '',
    maxlength: '40',
    'aria-label': 'Raum / Zusatz',
    enterkeyhint: 'done',
    oninput: () => {
      touched = true;
    },
    onkeydown: (event) => {
      if (event.key !== 'Enter') return;
      event.preventDefault();
      sheet.close();
    },
  });

  const search = h('input', {
    class: 'search-field__input',
    type: 'search',
    placeholder: 'Suchen',
    'aria-label': 'Suchen',
    enterkeyhint: 'go',
    oninput: (event) => {
      query = event.target.value;
      build();
    },
    onkeydown: (event) => {
      if (event.key !== 'Enter' || !query.trim()) return;
      event.preventDefault();
      group.querySelector('.list-row')?.click();
    },
  });

  build();
  sheet = openSheet({
    title: 'Ort',
    anchor,
    className: 'editor__place-sheet',
    onClose: () => {
      if (!picked && touched && location) onPick(location, typedRoom());
    },
    content: h('div', { class: 'picker editor__place' },
      h('label', { class: 'search-field' }, icon('search', { class: 'search-field__icon' }), search),
      group,
      h('label', { class: 'editor__room' }, h('span', { class: 'editor__room-label' }, 'Raum / Zusatz'), room)),
  });
  if (places.length > 7) sheet.el.classList.add('sheet--large');
  return sheet;
}

function openColorSheet(st, anchor) {
  const { r } = st;
  ui.openColorSheet({
    anchor,
    current: r.color ?? NO_COLOR,
    onPick: (value) => {
      const before = snapshot(r, ['color']);
      r.color = value;
      saved(st);
      undoable(st, 'Farbe geändert', before);
    },
  });
}

function openGroupsSheet(st, anchor) {
  const { r } = st;
  ui.openBesetzungSheet({
    title: 'Besetzung',
    anchor,
    value: r.instrumentIds,
    onDone: (next) => {
      if (next.join() === r.instrumentIds.join()) return;
      const before = snapshot(r, ['instrumentIds']);
      r.instrumentIds = next;
      saved(st);
      undoable(st, 'Besetzung geändert', before);
    },
  });
}

function openTagSheet(st) {
  const { r } = st;
  ui.openTagSheet({
    suggestions: knownTags.map((t) => t.name).filter((name) => !r.tags.includes(name)),
    onAdd: (name) => {
      const tag = name.trim();
      if (!tag || r.tags.includes(tag)) return;
      r.tags.push(tag);
      saved(st);
      paintBody(st);
    },
  });
}

/* Actions */

export function cloneRehearsal(r, date) {
  const id = Math.max(...rehearsals.map((x) => x.id)) + 1;
  const span = r.multiDay ? daysBetween(r.date, r.endDate) : 0;
  const copy = {
    ...r,
    id,
    date,
    endDate: addDays(date, span),
    groups: [...r.groups],
    instrumentIds: [...r.instrumentIds],
    roleIds: [...r.roleIds],
    tags: [...r.tags],
    infos: r.infos.map((x) => ({ ...x })),
    scheduleItems: r.scheduleItems.map((x) => ({ ...x, groups: x.groups ? [...x.groups] : null, date: addDays(date, daysBetween(r.date, x.date)) })),
    deadline: r.deadline ? `${addDays(date, daysBetween(r.date, r.deadline.slice(0, 10)))}${r.deadline.slice(10)}` : null,
    cancelled: false,
    cancelReason: null,
  };
  syncDerived(copy);
  return copy;
}

function occupantOn(date) {
  return rehearsals.find((x) => !x.cancelled && x.date <= date && date <= (x.endDate ?? x.date)) ?? null;
}

function nextFreeDay(date) {
  let day = date;
  for (let i = 0; i < 90 && occupantOn(day); i += 1) day = addDays(day, 1);
  return day;
}

function shortDay(iso) {
  const [y, m, d] = iso.split('-').map(Number);
  return `${WEEKDAYS[new Date(Date.UTC(y, m - 1, d)).getUTCDay()]} ${d}.${m}.`;
}

function copyDetailPath(copy) {
  const path = location.hash.replace(/^#/, '');
  if (path.startsWith('/plan')) return `/plan/${copy.id}`;
  if (path.startsWith('/team')) return `/team/rehearsals/${copy.id}`;
  return `/rehearsals/${copy.id}${copy.isPast ? '?seg=past' : ''}`;
}

function openDuplicateSheet(st) {
  const { r } = st;
  let date = nextFreeDay(addDays(r.date, 7));
  const text = h('span', {}, dateNumeric(date));
  const clash = h('div', { class: 'list-row dup-clash', role: 'status' });
  const native = h('input', {
    class: 'value-pill__native',
    type: 'date',
    value: date,
    'aria-label': 'Datum der Kopie',
    onclick: pickerOnClick,
    onchange: (event) => {
      if (event.target.value) setDate(event.target.value);
    },
  });
  function setDate(value) {
    date = value;
    text.textContent = dateNumeric(date);
    native.value = date;
    const other = occupantOn(date);
    clash.hidden = !other;
    if (!other) return;
    const free = nextFreeDay(date);
    clash.replaceChildren(...h('div', {},
      h('span', { class: 'list-row__lead' }, icon('triangle-alert')),
      h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, `Am ${shortDay(date)} gibt es schon eine Probe (${other.location})`)),
      h('span', { class: 'list-row__trail' }, h('button', {
        class: 'button button--secondary button--sm',
        type: 'button',
        onclick: () => setDate(free),
      }, shortDay(free)))).childNodes);
    refreshIcons(clash);
  }
  setDate(date);
  return openSheet({
    title: 'Duplizieren',
    content: h('div', { class: 'list-group' },
      h('label', { class: 'list-row' },
        h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Datum')),
        h('span', { class: 'list-row__trail' }, h('span', { class: 'value-pill' }, text, native))),
      clash),
    actions: [{
      label: 'Duplizieren',
      variant: 'primary',
      icon: 'copy',
      onClick: async () => {
        const copy = cloneRehearsal(r, date);
        rehearsals.push(copy);
        for (const m of members) {
          if (appliesTo(m, copy)) promises.push({ memberId: m.id, rehearsalId: copy.id, status: 'pending', note: '', respondedAt: null });
        }
        sortRehearsals();
        refreshTabbar();
        haptic('success');
        await flashList([copy.id]);
        const origin = location.hash.replace(/^#/, '');
        navigate(st.detail ? copyDetailPath(copy) : editPath(st, copy), { direction: 'none' });
        withUndo(html`${icon('circle-check')} Probe dupliziert`, () => {
          removeRehearsal(copy);
          if (refersTo(location.hash, copy.id)) navigate(origin, { replace: true, direction: 'none' });
          else rerender();
        });
        return true;
      },
    }],
  });
}

function setCancelled(st, cancelled, reason = null) {
  st.r.cancelled = cancelled;
  st.r.cancelReason = cancelled ? reason : null;
  refresh(st);
}

function audience(r) {
  return promises.filter((p) => p.rehearsalId === r.id).length;
}

function withNotice(verb, count) {
  return count ? `${verb} und ${count} benachrichtigen` : verb;
}

function openCancelSheet(st) {
  const count = audience(st.r);
  let reason = null;
  let confirmButton = null;
  const other = h('textarea', { class: 'textarea editor__reason-text', rows: '2', maxlength: '120', placeholder: 'Grund', 'aria-label': 'Grund' });
  other.hidden = true;
  const rows = CANCEL_REASONS.map(([label, iconName]) => h('button', {
    class: 'list-row editor__reason',
    type: 'button',
    role: 'radio',
    'aria-checked': 'false',
    onclick: (event) => {
      haptic('selection');
      reason = label;
      for (const row of rows) row.setAttribute('aria-checked', String(row === event.currentTarget));
      other.hidden = reason !== OTHER_REASON;
      if (!other.hidden) other.focus();
      if (confirmButton) confirmButton.disabled = false;
    },
  },
  h('span', { class: 'list-row__lead' }, icon(iconName)),
  h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, label)),
  h('span', { class: 'list-row__trail' }, icon('check', { class: 'list-row__check' }))));
  const sheet = openSheet({
    title: `Probe am ${shortDay(st.r.date)} absagen?`,
    className: 'sheet--confirm sheet--destructive',
    content: h('div', { class: 'editor__cancel' },
      h('div', { class: 'list-group editor__reasons', role: 'radiogroup', 'aria-label': 'Grund' }, rows),
      other),
    actions: [
      {
        label: withNotice('Absagen', count),
        variant: 'destructive-solid',
        icon: 'ban',
        onClick: () => {
          if (!reason) return false;
          const text = reason === OTHER_REASON ? other.value.trim() || null : reason;
          haptic('warning');
          const editing = st.root.isConnected && !st.detail;
          setCancelled(st, true, text);
          if (count) toast(html`${icon('ban')} Probe abgesagt`);
          else withUndo(html`${icon('ban')} Probe abgesagt`, () => setCancelled(st, false));
          if (editing) leaveEditor(st);
          return true;
        },
      },
      { label: 'Abbrechen', variant: 'secondary' },
    ],
  });
  confirmButton = sheet.el.querySelector('.sheet__actions .button');
  confirmButton.disabled = true;
}

async function openUncancelSheet(st) {
  const count = audience(st.r);
  const reason = st.r.cancelReason;
  if (!count) {
    setCancelled(st, false);
    withUndo(html`${icon('circle-check')} Absage zurückgenommen`, () => setCancelled(st, true, reason));
    return;
  }
  const ok = await ui.openConfirmSheet({
    title: `Absage für ${shortDay(st.r.date)} zurücknehmen?`,
    action: withNotice('Zurücknehmen', count),
    icon: 'rotate-ccw',
  });
  if (!ok) return;
  setCancelled(st, false);
  toast(html`${icon('circle-check')} Absage zurückgenommen`);
}

function removeRehearsal(r) {
  const index = rehearsals.indexOf(r);
  if (index !== -1) rehearsals.splice(index, 1);
  const removed = [];
  for (let i = promises.length - 1; i >= 0; i -= 1) {
    if (promises[i].rehearsalId === r.id) removed.unshift(...promises.splice(i, 1));
  }
  refreshTabbar();
  return () => {
    rehearsals.splice(Math.min(index, rehearsals.length), 0, r);
    sortRehearsals();
    promises.push(...removed);
    refreshTabbar();
  };
}

function neighbourOf(r) {
  if (r.isPast) {
    const pool = getPast();
    return pool.find((x) => x.start < r.start) ?? pool.at(-1) ?? null;
  }
  const pool = getUpcoming();
  return pool.find((x) => x.start > r.start) ?? pool.at(-1) ?? null;
}

async function leaveAfterRemoval(st, { toList = false } = {}) {
  const { r } = st;
  if (st.embedded) {
    const next = toList ? null : neighbourOf(r);
    if (next) await flashList([next.id]);
    navigate(next ? `/rehearsals/${next.id}${r.isPast ? '?seg=past' : ''}` : `/rehearsals${r.isPast ? '?seg=past' : ''}`, { replace: true, direction: 'none' });
    return;
  }
  const home = listPath();
  if (history.state?.idx > 0) {
    window.addEventListener('popstate', () => {
      if (refersTo(location.hash, r.id)) navigate(home, { replace: true, direction: 'none' });
    }, { once: true });
    back();
    return;
  }
  navigate(home, { replace: true, direction: 'none' });
}

async function undoRemoval(st, restore) {
  restore();
  const { r } = st;
  await flashList([r.id]);
  if (ui.isWide() && !inPlan()) navigate(`/rehearsals/${r.id}${r.isPast && !r.isToday ? '?seg=past' : ''}`, { replace: true, direction: 'none' });
  else rerender();
}

async function openDeleteSheet(st) {
  const { r } = st;
  const answered = promises.filter((p) => p.rehearsalId === r.id && p.status !== 'pending').length;
  if (answered) {
    const ok = await ui.openConfirmSheet({
      title: `Probe am ${shortDay(r.date)} löschen?`,
      text: `${answered} ${answered === 1 ? 'Antwort geht' : 'Antworten gehen'} verloren.`,
      action: 'Probe löschen',
      icon: 'trash-2',
      destructive: true,
    });
    if (!ok) return;
    removeRehearsal(r);
    leaveAfterRemoval(st);
    toast(html`${icon('trash-2')} Probe gelöscht`);
    return;
  }
  const restore = removeRehearsal(r);
  leaveAfterRemoval(st);
  withUndo(html`${icon('trash-2')} Probe gelöscht`, () => undoRemoval(st, restore));
}

function discardNew(st) {
  const restore = removeRehearsal(st.r);
  drafts.delete(st.r);
  leaveAfterRemoval(st, { toList: true });
  withUndo('Neue Probe verworfen', () => undoRemoval(st, restore));
}

/* Menu */

export function openRehearsalMenu(r, { editor = null, embedded = false } = {}) {
  if (!r) return null;
  const st = editor ?? { r, root: h('div'), ctx: {}, embedded, isNew: false, state: null, detail: true };
  const past = r.isPast && !r.isToday;
  const run = (fn) => () => fn(st);
  return openSheet({
    title: editor?.embedded || embedded ? dateNumeric(r.date) : dateTitle(r.date),
    className: 'editor__menu-sheet',
    content: h('div', { class: 'list-group action-list editor__actions', role: 'menu' },
      actionRow('copy', 'Duplizieren', run(openDuplicateSheet)),
      past ? null
        : r.cancelled ? actionRow('rotate-ccw', 'Absage zurücknehmen', run(openUncancelSheet))
          : actionRow('ban', 'Probe absagen', run(openCancelSheet), { destructive: true }),
      editor?.embedded ? actionRow('trash-2', 'Probe löschen', run(openDeleteSheet), { destructive: true }) : null),
  });
}

/* Screens */

registerScreen('rehearsal-edit', {
  title: 'Probe',
  tab: '/rehearsals',
  render(root, params, ctx) {
    if (!ui.guard(ctx, ['conductor'])) return;
    if (ctx.query?.seg === 'wer') {
      navigate(`/rehearsals/${params.id}/responses`, { replace: true, direction: 'none' });
      return;
    }
    const r = getRehearsal(params.id);
    if (!r) {
      renderEditor(root, params.id, ctx);
      return;
    }
    if (ctx.query?.new === '1') {
      navigate(`/rehearsals?${segQuery(r)}edit=${r.id}&new=1`, { replace: true, direction: 'none' });
      return;
    }
    return import('./rehearsals.js').then((mod) => (ui.isWide()
      ? mod.renderRehearsals(root, ctx, { expandedId: r.id })
      : mod.renderDetailPage(root, r.id, ctx)));
  },
  onLeave() {
    settleNew();
  },
});

let responsesModule = null;

registerScreen('rehearsal-responses', {
  title: 'Rückmeldungen',
  tab: '/rehearsals',
  async render(root, params, ctx) {
    if (!ui.guard(ctx, ['conductor'])) return;
    try {
      responsesModule ??= await import('./responses.js');
    } catch {
      responsesModule = null;
    }
    if (!responsesModule?.renderResponses) {
      const r = getRehearsal(params.id);
      setHeader({ title: 'Rückmeldungen', subtitle: r ? formatDate(r.date) : undefined, back: true, large: false });
      root.append(ui.emptyState({
        icon: 'users-round',
        title: 'Rückmeldungen nicht verfügbar',
        action: { label: 'Zur Probe', href: `#/rehearsals/${params.id}` },
      }));
      return;
    }
    await responsesModule.renderResponses(root, params.id, ctx);
  },
  onLeave(root) {
    responsesModule?.cleanupResponses?.(root);
  },
});

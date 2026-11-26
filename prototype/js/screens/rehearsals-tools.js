/* Proben (Leitung): Filter und Mehrfachauswahl */

import { h, html, icon, refreshIcons, haptic, openSheet, toast } from '../app.js';
import * as ui from '../ui.js';
import {
  colors, defaultBesetzung, getRehearsal, getSection, promises, appliesTo, members, rehearsals, roles, tags as knownTags,
} from '../data.js';
import { refreshBadges } from '../rsvp.js';
import {
  addDays, fieldRow, flashRehearsals, markInvalid, markUsedEmojis, nativeField, pickGroups, pickType, pickerOnClick, undoToast,
} from './rehearsals.js';

const NO_COLOR = '#e5e7eb';
const FILTER_KEYS = ['colors', 'types', 'groups', 'roles', 'places', 'tags'];

const plural = (n) => `${n} ${n === 1 ? 'Probe' : 'Proben'}`;
const colorKey = (value) => (value || NO_COLOR).toLowerCase();
const editorModule = () => import('./rehearsal-edit.js');

/* Filter */

const emptyFilters = () => ({
  colors: new Set(), types: new Set(), groups: new Set(), roles: new Set(), places: new Set(), tags: new Set(), from: '', to: '',
});

export const filters = emptyFilters();

export function resetFilters() {
  Object.assign(filters, emptyFilters());
}

export function filterCount() {
  return FILTER_KEYS.reduce((n, key) => n + filters[key].size, 0) + (filters.from || filters.to ? 1 : 0);
}

export function passes(r) {
  if (!filterCount()) return true;
  if (filters.colors.size && !filters.colors.has(colorKey(r.color))) return false;
  if (filters.types.size && !filters.types.has(r.type)) return false;
  if (filters.places.size && !filters.places.has(r.location)) return false;
  if (filters.tags.size && !r.tags.some((tag) => filters.tags.has(tag))) return false;
  if (filters.roles.size && !r.roleIds.some((id) => filters.roles.has(id))) return false;
  if (filters.groups.size) {
    const wanted = new Set([...filters.groups].flatMap((id) => getSection(id)?.instrumentIds ?? [id]));
    if (!r.instrumentIds.some((id) => wanted.has(id))) return false;
  }
  if (filters.from && r.date < filters.from) return false;
  if (filters.to && r.date > filters.to) return false;
  return true;
}

function tally(values) {
  const counts = new Map();
  for (const value of values) counts.set(value, (counts.get(value) ?? 0) + 1);
  return [...counts].sort((a, b) => b[1] - a[1] || String(a[0]).localeCompare(String(b[0]), 'de')).map(([value]) => value);
}

const allTags = () => tally(rehearsals.flatMap((r) => r.tags));

function filterOptions() {
  return {
    colors: tally(rehearsals.map((r) => colorKey(r.color))),
    types: tally(rehearsals.map((r) => r.type)),
    places: tally(rehearsals.map((r) => r.location)),
    tags: allTags(),
    roles: roles.filter((role) => rehearsals.some((r) => r.roleIds.includes(role.id))),
    groups: ui.registerGroups(),
  };
}

const colorName = (value) => (colorKey(value) === NO_COLOR ? 'Keine Farbe' : colors.find((c) => c.value.toLowerCase() === colorKey(value))?.name ?? 'Eigene');
const dmy = (iso) => `${iso.slice(8, 10)}.${iso.slice(5, 7)}.${iso.slice(0, 4)}`;

function rangeLabel() {
  if (filters.from && filters.to) return `${dmy(filters.from)} – ${dmy(filters.to)}`;
  return filters.from ? `ab ${dmy(filters.from)}` : `bis ${dmy(filters.to)}`;
}

function activeItems() {
  const items = [];
  const add = (key, value, label, dot = null) => items.push({ key, value, label, dot });
  for (const c of colors) if (filters.colors.has(c.value.toLowerCase())) add('colors', c.value.toLowerCase(), colorName(c.value), c.value);
  for (const value of filters.types) add('types', value, value);
  for (const id of filters.groups) add('groups', id, getSection(id)?.name ?? id);
  for (const id of filters.roles) add('roles', id, roles.find((role) => role.id === id)?.name ?? String(id));
  for (const value of filters.places) add('places', value, value);
  for (const value of filters.tags) add('tags', value, value);
  if (filters.from || filters.to) add('range', null, rangeLabel());
  return items;
}

function removeItem(item) {
  if (item.key === 'range') Object.assign(filters, { from: '', to: '' });
  else filters[item.key].delete(item.value);
}

export function filterRow({ onChange }) {
  const el = h('div', { class: 'proben-filters', role: 'group', 'aria-label': 'Aktive Filter', hidden: true });
  const paint = () => {
    const items = activeItems();
    el.hidden = !items.length;
    const chips = items.map((item) => h('button', {
      class: 'chip proben-filters__chip',
      type: 'button',
      'aria-label': `Filter ${item.label} entfernen`,
      style: item.dot ? `--dot:${item.dot}` : null,
      onclick: () => {
        haptic('light');
        removeItem(item);
        onChange();
      },
    },
    item.dot ? h('span', { class: 'chip__dot', 'aria-hidden': 'true' }) : null,
    h('span', { class: 'chip__label' }, item.label),
    icon('x')));
    if (items.length > 1) {
      chips.push(h('button', {
        class: 'button button--plain button--sm proben-filters__reset',
        type: 'button',
        onclick: () => {
          haptic('light');
          resetFilters();
          onChange();
        },
      }, 'Zurücksetzen'));
    }
    el.replaceChildren(...chips);
    refreshIcons(el);
  };
  paint();
  return { el, paint };
}

export function filterButton({ onClick }) {
  const count = h('span', { class: 'proben-filter__count', 'aria-hidden': 'true' });
  const el = h('button', {
    class: 'proben-tool proben-filter',
    type: 'button',
    title: 'Filtern',
    'aria-haspopup': 'dialog',
    'aria-expanded': 'false',
    onclick: (event) => onClick(event.currentTarget),
  }, icon('list-filter'), h('span', { class: 'proben-tool__label' }, 'Filtern'), count);
  const paint = () => {
    const n = filterCount();
    count.textContent = n ? String(n) : '';
    el.classList.toggle('is-active', n > 0);
    el.setAttribute('aria-label', n ? `Filtern, ${n} aktiv` : 'Filtern');
  };
  paint();
  return { el, paint };
}

function typeColor(type) {
  const hit = rehearsals.find((r) => r.type === type && colorKey(r.color) !== NO_COLOR);
  return hit?.color ?? 'var(--accent-icon)';
}

function filterPanel({ onChange, footDates = false }) {
  const el = h('div', { class: 'pfilter__body' });
  const range = h('div', { class: 'pfilter__range' });
  const section = (title, body) => h('section', { class: 'pfilter__sec' }, h('h3', { class: 'pfilter__h' }, title), body);
  const toggle = (set, value) => {
    haptic('selection');
    const on = !set.has(value);
    if (on) set.add(value);
    else set.delete(value);
    onChange();
    return on;
  };

  const chips = (key, items, label) => h('div', { class: 'pfilter__chips', role: 'group', 'aria-label': label },
    items.map(({ value, text, dot }) => {
      const chip = ui.chip({
        label: text,
        dot,
        pressed: filters[key].has(value),
        onClick: () => ui.setPressed(chip, toggle(filters[key], value)),
      });
      return chip;
    }));

  const dates = () => {
    const field = (key, label, limit) => {
      const input = h('input', {
        class: 'native-field pfilter__date-input',
        type: 'date',
        value: filters[key],
        'aria-label': label,
        onclick: pickerOnClick,
        onchange: (event) => {
          filters[key] = event.target.value;
          if (key === 'from') to.querySelector('input').min = filters.from;
          else from.querySelector('input').max = filters.to;
          onChange();
        },
      });
      if (limit) input[limit.attr] = limit.value;
      return h('label', { class: 'pfilter__date' }, h('span', { class: 'pfilter__date-label' }, label), input);
    };
    const from = field('from', 'Von', filters.to ? { attr: 'max', value: filters.to } : null);
    const to = field('to', 'Bis', filters.from ? { attr: 'min', value: filters.from } : null);
    return h('div', { class: 'pfilter__dates' }, from, to);
  };

  const build = () => {
    const options = filterOptions();
    const nodes = [
      section('Farbe', chips('colors', options.colors.map((value) => ({ value, text: colorName(value), dot: value === NO_COLOR ? null : value })), 'Farbe')),
      section('Art', chips('types', options.types.map((value) => ({ value, text: value, dot: typeColor(value) })), 'Art')),
      section('Register', chips('groups', options.groups.map((s) => ({ value: s.id, text: s.name, dot: s.color })), 'Register')),
      options.roles.length ? section('Rolle', chips('roles', options.roles.map((role) => ({ value: role.id, text: role.name, dot: role.tagColor })), 'Rolle')) : null,
      section('Ort', chips('places', options.places.map((value) => ({ value, text: value })), 'Ort')),
      options.tags.length ? section('Tags', chips('tags', options.tags.map((value) => ({ value, text: value })), 'Tags')) : null,
      footDates ? null : section('Zeitraum', dates()),
    ].filter(Boolean);
    el.replaceChildren(...nodes);
    refreshIcons(el);
    if (footDates) range.replaceChildren(h('span', { class: 'pfilter__h' }, 'Zeitraum'), dates());
  };

  build();
  return { el, range, rebuild: build };
}

let pop = null;

export function closeFilter({ focus = false } = {}) {
  if (!pop) return;
  const { el, anchor, cleanup } = pop;
  pop = null;
  cleanup();
  anchor.setAttribute('aria-expanded', 'false');
  el.classList.remove('is-open');
  el.remove();
  if (focus && anchor.isConnected) anchor.focus({ preventScroll: true });
}

export function toggleFilter(anchor, { count, onChange, onOpen }) {
  if (pop) {
    closeFilter();
    return;
  }
  onOpen?.();
  haptic('light');
  const foot = h('div', { class: 'pfilter__foot' });
  const countEl = h('span', { class: 'pfilter__count', 'aria-live': 'polite' });
  const reset = h('button', { class: 'button button--plain button--sm pfilter__reset', type: 'button' }, 'Zurücksetzen');
  const sync = () => {
    countEl.textContent = plural(count());
    reset.disabled = filterCount() === 0;
  };
  const panel = filterPanel({ footDates: true, onChange: () => {
    onChange();
    sync();
  } });
  foot.append(panel.range, countEl, reset);
  reset.addEventListener('click', () => {
    haptic('light');
    resetFilters();
    panel.rebuild();
    onChange();
    sync();
  });
  sync();

  const el = h('div', { class: 'pfilter', role: 'dialog', 'aria-label': 'Filter' }, panel.el, foot);
  const rect = anchor.getBoundingClientRect();
  el.style.top = `${Math.round(rect.bottom + 4)}px`;
  el.style.right = `${Math.round(document.documentElement.clientWidth - rect.right)}px`;
  el.style.maxHeight = `${Math.max(280, Math.round(window.innerHeight - rect.bottom - 16))}px`;
  document.body.append(el);
  anchor.setAttribute('aria-expanded', 'true');

  const onDown = (event) => {
    if (!el.contains(event.target) && !anchor.contains(event.target)) closeFilter();
  };
  const onKey = (event) => {
    if (event.key === 'Escape') {
      event.preventDefault();
      event.stopPropagation();
      closeFilter({ focus: true });
    }
  };
  const onResize = () => closeFilter();
  document.addEventListener('pointerdown', onDown, true);
  document.addEventListener('keydown', onKey, true);
  window.addEventListener('resize', onResize);
  pop = {
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
    el.querySelector('button')?.focus({ preventScroll: true });
  });
}

export function openFilterSheet({ count, onChange }) {
  let primary = null;
  const sync = () => {
    if (primary) primary.textContent = `${plural(count())} anzeigen`;
  };
  const panel = filterPanel({ onChange: () => {
    onChange();
    sync();
  } });
  const sheet = openSheet({
    title: 'Filter',
    content: panel.el,
    actions: [
      {
        label: 'Zurücksetzen',
        variant: 'secondary',
        onClick: () => {
          resetFilters();
          panel.rebuild();
          onChange();
          sync();
          return false;
        },
      },
      { label: '', variant: 'primary' },
    ],
  });
  primary = sheet.el.querySelector('.sheet__actions .button--primary .button__label');
  sync();
  return sheet;
}

/* Mehrfachauswahl */

export const selection = { on: false, ids: new Set() };

export function resetSelection() {
  selection.on = false;
  selection.ids.clear();
}

const EDITS = [
  ['type', 'Art', 'shapes'],
  ['location', 'Ort', 'map-pin'],
  ['groups', 'Besetzung', 'users-round'],
  ['color', 'Farbe', 'palette'],
  ['time', 'Uhrzeit', 'clock'],
  ['tag', 'Tag', 'tag'],
  ['hint', 'Hinweis', 'sticky-note'],
];
const RECORDS = [
  ['duplicate', 'Duplizieren', 'copy'],
  ['delete', 'Löschen', 'trash-2'],
];

let actionAnchor = null;

export function selectControls({ onAction, onCancel }) {
  const count = h('span', { class: 'proben-select__count', 'aria-live': 'polite' });
  const button = ([key, label, iconName], className) => h('button', {
    class: ['proben-tool', className, 'proben-select__btn', key === 'delete' && 'is-danger'],
    type: 'button',
    dataset: { action: key },
    onclick: (event) => {
      actionAnchor = event.currentTarget;
      onAction(key);
    },
  }, icon(iconName), h('span', { class: 'proben-tool__label' }, label));
  const edits = EDITS.map((item) => button(item, 'proben-select__edit'));
  const records = RECORDS.map((item) => button(item, 'proben-select__record'));
  const buttons = [...edits, ...records];
  const actions = h('div', { class: 'proben-select__actions', role: 'toolbar', 'aria-label': 'Auswahl bearbeiten' },
    h('div', { class: 'proben-select__edits', role: 'group', 'aria-label': 'Ändern' }, edits),
    h('div', { class: 'proben-select__records' }, records));
  const cancel = h('button', { class: 'button button--plain proben-select__cancel', type: 'button', onclick: onCancel }, 'Abbrechen');
  refreshIcons(actions);
  const sync = () => {
    const n = selection.ids.size;
    count.textContent = `${n} ausgewählt`;
    for (const button of buttons) button.disabled = n === 0;
  };
  sync();
  return { count, actions, cancel, sync };
}

const picked = () => [...selection.ids].map(getRehearsal).filter(Boolean);

function common(list, get) {
  const first = JSON.stringify(get(list[0]));
  return list.every((r) => JSON.stringify(get(r)) === first) ? get(list[0]) : undefined;
}

export function createActions({ onChanged, onExit, onRestore }) {
  const change = async (list, mutate, message) => {
    const { syncDerived } = await editorModule();
    let changed = 0;
    for (const r of list) {
      const before = JSON.stringify(r);
      mutate(r);
      syncDerived(r);
      if (JSON.stringify(r) !== before) changed += 1;
    }
    if (!changed) return;
    rehearsals.sort((a, b) => a.start.localeCompare(b.start));
    refreshBadges();
    haptic('success');
    toast(message ?? `${plural(changed)} geändert`, { icon: 'circle-check' });
    onChanged();
  };

  const timeSheet = (list) => {
    const first = list[0];
    const times = { startTime: common(list, (r) => r.startTime) ?? first.startTime, endTime: common(list, (r) => r.endTime) ?? first.endTime };
    const strict = list.some((r) => !r.multiDay);
    const invalid = () => strict && times.endTime <= times.startTime;
    let apply = null;
    const check = () => {
      markInvalid(end, invalid());
      if (apply) apply.disabled = invalid();
    };
    const start = nativeField({ value: times.startTime, label: 'Beginn', onInput: (value) => { times.startTime = value; check(); } });
    const end = nativeField({ value: times.endTime, label: 'Ende', onInput: (value) => { times.endTime = value; check(); } });
    const sheet = openSheet({
      title: 'Uhrzeit',
      content: h('div', { class: 'list-group' }, fieldRow('Beginn', start), fieldRow('Ende', end)),
      actions: [{
        label: 'Speichern',
        variant: 'primary',
        onClick: () => {
          if (invalid()) return false;
          change(list, (r) => Object.assign(r, times));
          return true;
        },
      }],
    });
    apply = sheet.el.querySelector('.sheet__actions .button--primary');
    check();
  };

  const hintSheet = (list) => {
    let emoji = '📝';
    const inAll = list[0].infos.map((x) => x.emoji).filter((e) => list.every((r) => r.infos.some((x) => x.emoji === e)));
    const pill = h('button', {
      class: 'editor__emoji-btn',
      type: 'button',
      'aria-label': 'Emoji wählen',
      onclick: () => markUsedEmojis(ui.openEmojiSheet(emoji, (next) => {
        emoji = next;
        pill.replaceChildren(ui.infoPill(emoji, { size: 'lg' }));
      }, { stack: true }), inAll),
    }, ui.infoPill(emoji, { size: 'lg' }));
    const input = h('input', { class: 'input', type: 'text', placeholder: 'Hinweis', maxlength: '160', 'aria-label': 'Hinweis', autofocus: true });
    const add = () => {
      const text = input.value.trim();
      if (!text) {
        input.focus();
        return false;
      }
      change(list, (r) => r.infos.push({ emoji, text }));
      return true;
    };
    const sheet = openSheet({
      title: 'Hinweis hinzufügen',
      content: h('div', { class: 'editor__prompt proben-hint' }, pill, input),
      actions: [{ label: 'Hinzufügen', variant: 'primary', onClick: add }],
    });
    input.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' && add()) sheet.close();
    });
  };

  const duplicateSheet = (list) => {
    const offset = h('input', {
      class: 'native-field proben-offset',
      type: 'number',
      min: '1',
      max: '365',
      value: '7',
      inputmode: 'numeric',
      'aria-label': 'Tage verschieben',
    });
    openSheet({
      title: `${plural(list.length)} duplizieren`,
      content: h('div', { class: 'list-group' }, fieldRow('Tage verschieben', offset)),
      actions: [{
        label: 'Duplizieren',
        variant: 'primary',
        icon: 'copy',
        onClick: async () => {
          const days = Math.min(365, Math.max(1, parseInt(offset.value, 10) || 7));
          const { cloneRehearsal } = await editorModule();
          const copies = [];
          for (const r of list) {
            const copy = cloneRehearsal(r, addDays(r.date, days));
            rehearsals.push(copy);
            copies.push(copy);
            for (const m of members) {
              if (appliesTo(m, copy)) promises.push({ memberId: m.id, rehearsalId: copy.id, status: 'pending', note: '', respondedAt: null });
            }
          }
          rehearsals.sort((a, b) => a.start.localeCompare(b.start));
          refreshBadges();
          haptic('success');
          const ids = copies.map((c) => c.id);
          flashRehearsals(ids);
          onExit();
          undoToast(ids, ids.length === 1 ? 'Probe dupliziert' : `${ids.length} Proben dupliziert`);
          return true;
        },
      }],
    });
  };

  const remove = (list) => {
    const ids = new Set(list.map((r) => r.id));
    const spots = list.map((r) => [r, rehearsals.indexOf(r)]);
    const dropped = promises.filter((p) => ids.has(p.rehearsalId));
    for (const r of list) {
      const index = rehearsals.indexOf(r);
      if (index !== -1) rehearsals.splice(index, 1);
    }
    for (let i = promises.length - 1; i >= 0; i -= 1) {
      if (ids.has(promises[i].rehearsalId)) promises.splice(i, 1);
    }
    refreshBadges();
    return () => {
      for (const [r, index] of spots) rehearsals.splice(Math.min(index, rehearsals.length), 0, r);
      rehearsals.sort((a, b) => a.start.localeCompare(b.start));
      promises.push(...dropped);
      refreshBadges();
    };
  };

  const deleteSheet = async (list) => {
    const answered = promises.filter((p) => list.some((r) => r.id === p.rehearsalId) && p.status !== 'pending').length;
    if (answered) {
      const ok = await ui.openConfirmSheet({
        title: `${plural(list.length)} löschen?`,
        text: `${answered} ${answered === 1 ? 'Antwort geht' : 'Antworten gehen'} verloren.`,
        action: `${plural(list.length)} löschen`,
        icon: 'trash-2',
        destructive: true,
      });
      if (!ok) return;
      remove(list);
      onExit();
      toast(html`${icon('trash-2')} ${plural(list.length)} gelöscht`);
      return;
    }
    const restore = remove(list);
    onExit();
    toast(html`${icon('trash-2')} ${plural(list.length)} gelöscht`, {
      duration: 5000,
      action: {
        label: 'Rückgängig',
        onClick: () => {
          restore();
          flashRehearsals(list.map((r) => r.id));
          onRestore();
        },
      },
    });
  };

  return (key) => {
    const list = picked();
    if (!list.length) return;
    haptic('light');
    const anchor = actionAnchor?.isConnected ? actionAnchor : null;
    const handlers = {
      duplicate: () => duplicateSheet(list),
      delete: () => deleteSheet(list),
      type: () => pickType(common(list, (r) => r.type), (value) => change(list, (r) => { r.type = value; }), anchor),
      location: async () => {
        const { pickPlace } = await editorModule();
        pickPlace({
          anchor,
          location: common(list, (r) => r.location),
          room: common(list, (r) => r.room ?? null),
          onPick: (name, room) => change(list, (r) => Object.assign(r, { location: name, room: room ?? null })),
        });
      },
      groups: () => pickGroups(common(list, (r) => r.instrumentIds) ?? defaultBesetzung().instrumentIds, (patch) => change(list, (r) => {
        Object.assign(r, { groups: [...patch.groups], groupsText: patch.groupsText, instrumentIds: [...patch.instrumentIds] });
      }), anchor),
      color: () => ui.openColorSheet({ anchor, current: common(list, (r) => colorKey(r.color)), onPick: (value) => change(list, (r) => { r.color = value; }) }),
      time: () => timeSheet(list),
      tag: () => ui.openTagSheet({
        suggestions: allTags(),
        onAdd: (name) => {
          if (!knownTags.some((t) => t.name === name)) knownTags.push({ name, count: list.length });
          change(list, (r) => {
            if (!r.tags.includes(name)) r.tags.push(name);
          });
        },
      }),
      hint: () => hintSheet(list),
    };
    handlers[key]?.();
  };
}

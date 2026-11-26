/* Serie anlegen (Leitung) */

import * as ui from '../ui.js';
import { registerScreen, setHeader, back, h, icon, refreshIcons, haptic } from '../app.js';
import { openMenu } from '../menu.js';
import { addPlace } from '../places.js';
import { deskBar } from './team.js';
import { pickRow, rowsMode, weekdayAnchor } from '../rsvp.js';
import { colors, defaultBesetzung, formatDate, getUpcoming, rehearsalTypes, tags as knownTags, TODAY } from '../data.js';
import { pickPlace } from './rehearsal-edit.js';
import {
  addDays, weekday, createRehearsal, markUsedEmojis, mountBottomBar, templateDefaults, nextFreeDate, takenBy, fieldError, selectField, timeInput,
  pickGroups, pickType, flashRehearsals, undoToast, monthLabel, nativeField, fieldRow, markInvalid, pickerOnClick, buildRehearsal, dateSep,
} from './rehearsals.js';

const DAYS = [[1, 'Mo', 'Montag'], [2, 'Di', 'Dienstag'], [3, 'Mi', 'Mittwoch'], [4, 'Do', 'Donnerstag'], [5, 'Fr', 'Freitag'], [6, 'Sa', 'Samstag'], [0, 'So', 'Sonntag']];
const DURATIONS = [
  ['1w', '1 Woche'], ['2w', '2 Wochen'], ['3w', '3 Wochen'], ['4w', '4 Wochen'],
  ['1m', '1 Monat'], ['2m', '2 Monate'], ['3m', '3 Monate'], ['6m', '6 Monate'], ['1y', '1 Jahr'],
  ['season', 'Saisonende'],
];
const SEASON_END = '2027-07-20';
const MAX = 366;
const NO_COLOR = '#e5e7eb';
const DEADLINES = [null, 1, 2, 3, 5, 7, 14];
const TIME_FAULT = 'Ende muss nach Beginn liegen';

let cleanup = null;
let draft = null;

const pad = (n) => String(n).padStart(2, '0');

const fullDate = (iso) => (iso.slice(0, 4) === TODAY.slice(0, 4) ? formatDate(iso) : `${formatDate(iso)} ${iso.slice(0, 4)}`);

function addMinutes(time, minutes) {
  const [hours, mins] = time.split(':').map(Number);
  const total = Math.min(hours * 60 + mins + minutes, 23 * 60 + 59);
  return `${pad(Math.floor(total / 60))}:${pad(total % 60)}`;
}

function minutesBetween(from, to) {
  const minutes = (t) => Number(t.slice(0, 2)) * 60 + Number(t.slice(3, 5));
  return minutes(to) - minutes(from);
}

const hasColor = (value) => Boolean(value) && value.toLowerCase() !== NO_COLOR;
const colorName = (value) => colors.find((c) => c.value.toLowerCase() === value.toLowerCase())?.name ?? 'Eigene';
const daysText = (n) => `${n} ${n === 1 ? 'Tag' : 'Tage'} vorher`;
const deadlineText = (n) => (n ? `${daysText(n)}, 12:00` : 'Keine');

function durationEnd(from, key) {
  if (key === 'season') return SEASON_END;
  const amount = parseInt(key, 10);
  const unit = key.slice(-1);
  if (unit === 'w') return addDays(from, amount * 7);
  const date = new Date(`${from}T00:00:00Z`);
  if (unit === 'm') date.setUTCMonth(date.getUTCMonth() + amount);
  else date.setUTCFullYear(date.getUTCFullYear() + amount);
  return date.toISOString().slice(0, 10);
}

function defaults() {
  const template = templateDefaults();
  const from = nextFreeDate(addDays(getUpcoming().at(-1)?.endDate ?? TODAY, 1), template.weekday);
  return {
    template: { ...template, ...defaultBesetzung(), date: from },
    days: new Set([template.weekday]),
    every: 1,
    from,
    to: durationEnd(from, '3m'),
    deadlineDays: null,
    toggled: new Set(),
    items: [],
    infos: [],
    tags: [],
  };
}

function dates(form) {
  const out = [];
  if (!form.days.size || form.to < form.from) return out;
  let weekStart = form.from;
  while (weekday(weekStart) !== 1) weekStart = addDays(weekStart, -1);
  for (let week = 0; out.length < MAX; week += form.every) {
    const monday = addDays(weekStart, week * 7);
    if (monday > form.to) break;
    for (let i = 0; i < 7; i += 1) {
      const d = addDays(monday, i);
      if (d >= form.from && d <= form.to && form.days.has(weekday(d))) out.push(d);
    }
  }
  return out;
}

const isOn = (form, d) => (takenBy(d) ? form.toggled.has(d) : !form.toggled.has(d));

const plausible = (iso) => Math.abs(Number(iso.slice(0, 4)) - Number(TODAY.slice(0, 4))) <= 5;

function dateField(label, current, onChange) {
  const el = nativeField({ type: 'date', value: current(), label, onInput: (v) => plausible(v) && onChange(v) });
  el.addEventListener('blur', () => {
    if (el.value !== current()) el.value = current();
  });
  return el;
}

function seriesChip(form) {
  const days = DAYS.filter(([value]) => form.days.has(value)).map(([, short]) => short.toUpperCase());
  const anchor = weekdayAnchor(buildRehearsal({ ...form.template, infos: [] }, 0));
  anchor.textContent = days.length ? days.join('·') : '–';
  anchor.classList.toggle('proben-row__wd--range', days.length > 1);
  return h('div', { class: 'series-chip editor-head__day', 'aria-hidden': 'true' }, anchor, icon('repeat'));
}

function templateHead(form, { onChange, onTime }) {
  const t = form.template;
  const tinted = hasColor(t.color);
  const place = [t.location, t.room].filter(Boolean).join(' · ');
  const set = (patch) => {
    Object.assign(t, patch);
    onChange();
  };
  const bad = t.endTime <= t.startTime;
  const time = (key, label) => timeInput({
    key,
    value: t[key],
    label,
    invalid: key === 'endTime' && bad,
    describedBy: 'seriesTimeError',
    onChange: (value) => {
      if (key === 'startTime' && t.endTime > t.startTime) t.endTime = addMinutes(value, minutesBetween(t.startTime, t.endTime));
      t[key] = value;
      onTime();
    },
  });
  return h('section', { class: 'editor-head series-head' },
    seriesChip(form),
    h('div', { class: 'editor-head__when' },
      h('div', { class: 'editor-head__times' }, time('startTime', 'Beginn'), h('span', { class: 'editor-head__dash' }, '–'), time('endTime', 'Ende')),
      fieldError(TIME_FAULT, 'seriesTimeError', !bad, 'editor-head__error')),
    h('div', { class: 'editor-head__fields' },
      selectField({
        label: 'Art',
        text: t.type,
        key: 'type',
        popup: 'menu',
        className: tinted ? 'edit-select--kind is-tinted' : 'edit-select--kind',
        style: tinted ? `--tag:${t.color}` : null,
        onClick: (anchor) => pickType(t.type, (value) => set({ type: value }), anchor),
      }),
      selectField({ label: 'Besetzung', text: t.groupsText, key: 'groups', onClick: (anchor) => pickGroups(t.instrumentIds, set, anchor) }),
      selectField({
        label: 'Ort',
        text: place,
        key: 'location',
        className: 'edit-select--wide',
        onClick: (anchor) => pickPlace({
          anchor,
          location: t.location,
          room: t.room ?? null,
          onPick: (name, room) => {
            addPlace(name);
            set({ location: name, room: room || null });
          },
        }),
      }),
      selectField({
        label: 'Antwort bis',
        text: deadlineText(form.deadlineDays),
        key: 'deadline',
        popup: 'menu',
        onClick: (anchor) => openMenu(anchor, DEADLINES.map((n) => ({
          label: n ? daysText(n) : 'Keine',
          checked: n === form.deadlineDays,
          onSelect: () => {
            form.deadlineDays = n;
            onChange();
          },
        })), { label: 'Antwort bis', minWidth: 180 }),
      }),
      selectField({
        label: 'Farbe',
        key: 'color',
        lead: h('span', { class: ['color-dot', 'edit-select__dot', !tinted && 'color-dot--none'], style: tinted ? `--dot:${t.color}` : null }),
        text: tinted ? colorName(t.color) : 'Keine Farbe',
        onClick: (anchor) => ui.openColorSheet({ anchor, current: t.color ?? NO_COLOR, onPick: (value) => set({ color: value }) }),
      })));
}

/* Ablauf, Hinweise, Tags */

function removeButton(label, onClick) {
  return h('button', { class: 'icon-button editor__remove', type: 'button', 'aria-label': label, onclick: onClick }, icon('circle-minus'));
}

function addRow(label, onClick) {
  return h('button', { class: 'list-row list-row--action editor__add', type: 'button', onclick: onClick },
    h('span', { class: 'list-row__lead' }, icon('circle-plus')),
    h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, label)));
}

function addChip(label, onClick) {
  return h('button', { class: 'chip series-add', type: 'button', onclick: onClick },
    icon('plus'), h('span', { class: 'chip__label' }, label));
}

function textField({ value, placeholder, label, maxlength, onInput }) {
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
    oninput: (event) => onInput(event.target.value.replace(/\n/g, ' ')),
  }, value);
}

function openTagSheet(form, onAdd) {
  ui.openTagSheet({
    suggestions: knownTags.map((t) => t.name).filter((name) => !form.tags.includes(name)),
    onAdd: (name) => {
      const tag = name.trim();
      if (!tag || form.tags.includes(tag)) return;
      form.tags.push(tag);
      onAdd();
    },
  });
}

function agendaGroup(form, repaint) {
  const sort = () => form.items.sort((a, b) => a.time.localeCompare(b.time));
  const rows = form.items.map((item, index) => h('div', { class: 'list-row editor__item' },
    h('span', { class: 'list-row__lead' },
      h('label', { class: 'value-pill editor__time' }, item.time,
        h('input', {
          class: 'value-pill__native',
          type: 'time',
          value: item.time,
          'aria-label': `Uhrzeit für ${item.title || 'Ablaufpunkt'}`,
          onclick: pickerOnClick,
          onchange: (event) => {
            if (!event.target.value) return;
            item.time = event.target.value;
            sort();
            repaint();
          },
        }))),
    h('span', { class: 'list-row__body' },
      textField({
        value: item.title,
        placeholder: 'Ablaufpunkt',
        label: 'Ablaufpunkt',
        maxlength: 80,
        onInput: (text) => {
          item.title = text;
        },
      })),
    h('span', { class: 'list-row__trail' },
      removeButton(`${item.title || 'Ablaufpunkt'} entfernen`, () => {
        form.items.splice(index, 1);
        repaint();
      }))));
  const onAdd = () => {
    const last = form.items.at(-1);
    form.items.push({ time: last ? addMinutes(last.time, 30) : form.template.startTime, title: '' });
    repaint();
    [...document.querySelectorAll('.series-extras .editor__agenda .editor__text')].at(-1)?.focus();
  };
  if (!rows.length) return { add: addChip('Ablauf', onAdd), blocks: [] };
  return {
    blocks: [
      ui.sectionHeader('Ablauf'),
      h('div', { class: 'list-group editor__list editor__agenda' }, rows, addRow('Ablaufpunkt hinzufügen', onAdd)),
    ],
  };
}

function infosGroup(form, repaint) {
  const rows = form.infos.map((info, index) => h('div', { class: 'list-row editor__item' },
    h('span', { class: 'list-row__lead' },
      h('button', {
        class: 'editor__emoji-btn',
        type: 'button',
        'aria-label': `Emoji ${info.emoji} ändern`,
        onclick: () => markUsedEmojis(ui.openEmojiSheet(info.emoji, (emoji) => {
          info.emoji = emoji;
          repaint();
        }), form.infos.map((x) => x.emoji)),
      }, ui.infoPill(info.emoji, { size: 'lg' }))),
    h('span', { class: 'list-row__body' },
      textField({
        value: info.text,
        placeholder: 'Hinweis',
        label: 'Hinweis',
        maxlength: 160,
        onInput: (text) => {
          info.text = text;
        },
      })),
    h('span', { class: 'list-row__trail' },
      removeButton('Hinweis entfernen', () => {
        form.infos.splice(index, 1);
        repaint();
      }))));
  const onAdd = () => {
    form.infos.push({ emoji: '📝', text: '' });
    repaint();
    [...document.querySelectorAll('.series-extras .editor__infos .editor__text')].at(-1)?.focus();
  };
  if (!rows.length) return { add: addChip('Hinweis', onAdd), blocks: [] };
  return {
    blocks: [
      ui.sectionHeader('Hinweise'),
      h('div', { class: 'list-group editor__list editor__infos' }, rows, addRow('Hinweis hinzufügen', onAdd)),
    ],
  };
}

function tagsGroup(form, repaint) {
  return {
    add: addChip('Tag', () => openTagSheet(form, repaint)),
    tags: form.tags.map((tag, index) => h('span', { class: 'chip editor__tag' },
      h('span', { class: 'chip__label' }, tag),
      h('button', {
        class: 'chip__remove',
        type: 'button',
        'aria-label': `${tag} entfernen`,
        onclick: () => {
          haptic('light');
          form.tags.splice(index, 1);
          repaint();
        },
      }, icon('x')))),
  };
}

/* Screen */

function render(root, params, ctx) {
  if (!ui.guard(ctx, ['conductor'])) return;
  setHeader({ title: 'Serie anlegen' });
  document.documentElement.dataset.rows = '1';
  root.classList.add('series-screen');
  if (ui.isWide()) {
    root.classList.add('proben-desk');
    deskBar(root, { title: 'Serie anlegen', showBack: true });
  }
  draft ??= defaults();
  const form = draft;

  const submit = h('button', { class: 'button button--primary button--lg button--block', type: 'button', onclick: create });
  const templateHost = h('div', { class: 'series-template' });
  const extrasHost = h('div', { class: 'editor series-extras' });
  const fromField = dateField('Von', () => form.from, (v) => {
    form.from = v;
    update();
  });
  const toField = dateField('Bis', () => form.to, (v) => setTo(v));
  const preview = h('div', { class: 'series-preview' });
  const durationValue = h('span', { class: 'list-row__value' });

  const durationLabel = () => DURATIONS.find(([key]) => durationEnd(form.from, key) === form.to)?.[1] ?? 'Individuell';

  const durationRow = h('button', {
    class: 'list-row',
    type: 'button',
    onclick: () => {
      haptic('light');
      const current = DURATIONS.find(([key]) => durationEnd(form.from, key) === form.to)?.[0];
      ui.openPicker({
        title: 'Dauer',
        options: DURATIONS.map(([value, label]) => ({ value, label })),
        value: current,
        onSelect: (value) => setTo(durationEnd(form.from, value)),
      });
    },
  },
  h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Dauer')),
  h('span', { class: 'list-row__trail' }, durationValue, icon('chevron-right', { class: 'list-row__chevron' })));

  const timeOk = () => form.template.endTime > form.template.startTime;

  function paintTemplate() {
    const focused = document.activeElement?.closest?.('.series-template [data-key]')?.dataset.key;
    templateHost.replaceChildren(templateHead(form, { onChange: update, onTime: syncTime }));
    refreshIcons(templateHost);
    if (focused && !templateHost.contains(document.activeElement)) templateHost.querySelector(`[data-key="${focused}"]`)?.focus({ preventScroll: true });
    syncSubmit();
  }

  function syncTime() {
    const bad = !timeOk();
    const start = templateHost.querySelector('[data-time="startTime"]');
    const end = templateHost.querySelector('[data-time="endTime"]');
    if (start && start.value !== form.template.startTime) start.value = form.template.startTime;
    if (end) {
      if (end.value !== form.template.endTime) end.value = form.template.endTime;
      markInvalid(end, bad);
      if (bad) end.setAttribute('aria-describedby', 'seriesTimeError');
      else end.removeAttribute('aria-describedby');
    }
    const message = templateHost.querySelector('.editor-head__error');
    if (message) message.hidden = !bad;
    update({ head: false });
  }

  function paintExtras() {
    const agenda = agendaGroup(form, paintExtras);
    const infos = infosGroup(form, paintExtras);
    const tags = tagsGroup(form, paintExtras);
    extrasHost.replaceChildren(
      ...agenda.blocks,
      ...infos.blocks,
      h('div', { class: 'editor__chips editor__tags series-adds' }, agenda.add, infos.add, tags.tags, tags.add),
    );
    refreshIcons(extrasHost);
  }

  function syncSubmit() {
    const active = dates(form).filter((d) => isOn(form, d));
    submit.disabled = !active.length || !timeOk();
    submit.textContent = active.length === 1 ? '1 Probe anlegen' : `${active.length} Proben anlegen`;
  }

  function dateRow(d) {
    const taken = takenBy(d);
    const r = buildRehearsal({
      ...form.template,
      date: d,
      endDate: d,
      infos: [],
    }, 0);
    const message = taken ? { text: `Belegt: ${taken.type}${taken.cancelled ? ' (fällt aus)' : ''}`, kind: 'warn' } : null;
    return pickRow(r, {
      on: isOn(form, d),
      message,
      dateText: ui.timeRange(r),
      timeText: '',
      label: `${fullDate(d)}${message ? `, ${message.text.toLowerCase()}` : ''}`,
      onToggle: () => {
        haptic('selection');
        if (form.toggled.has(d)) form.toggled.delete(d);
        else form.toggled.add(d);
        update();
      },
    });
  }

  function update({ head = true } = {}) {
    const list = dates(form);
    const active = list.filter((d) => isOn(form, d));
    const rangeOk = form.to >= form.from;
    markInvalid(toField, !rangeOk);
    durationValue.textContent = durationLabel();
    const months = [];
    for (const d of list) {
      const key = d.slice(0, 7);
      let m = months.at(-1);
      if (m?.key !== key) months.push((m = { key, label: monthLabel(d, { year: true }), items: [] }));
      m.items.push(d);
    }
    preview.replaceChildren(...(list.length ? [
      ui.sectionHeader('Proben', { count: active.length === list.length ? active.length : `${active.length}/${list.length}` }),
      h('div', { class: 'proben-rows', role: 'group', 'aria-label': 'Proben' },
        months.map((m) => h('section', { class: 'rehearsals-group' }, dateSep(m.label), h('div', { class: 'proben-card' }, m.items.map(dateRow))))),
    ] : []));
    refreshIcons(preview);
    if (head) paintTemplate();
    else syncSubmit();
  }

  function setTo(value) {
    form.to = value;
    if (toField !== document.activeElement) toField.value = value;
    update();
  }

  const dayButtons = DAYS.map(([value, short, long]) => h('button', {
    class: 'series-day',
    type: 'button',
    'aria-pressed': String(form.days.has(value)),
    'aria-label': long,
    onclick: (event) => {
      haptic('selection');
      if (form.days.has(value)) form.days.delete(value);
      else form.days.add(value);
      event.currentTarget.setAttribute('aria-pressed', String(form.days.has(value)));
      update();
    },
  }, h('span', { class: 'series-day__dot', 'aria-hidden': 'true' }, short)));

  root.append(
    templateHost,
    ui.sectionHeader('Rhythmus'),
    h('div', { class: 'list-group series-rhythm' },
      h('div', { class: 'series-days', role: 'group', 'aria-label': 'Wochentage' }, dayButtons),
      ui.segmented([
        { value: '1', label: 'Jede Woche' },
        { value: '2', label: 'Alle 2 Wochen' },
      ], String(form.every), (value) => {
        form.every = Number(value);
        update();
      }, { radio: true, label: 'Rhythmus' })),
    ui.sectionHeader('Zeitraum'),
    h('div', { class: 'list-group series-period' },
      fieldRow('Von', fromField),
      fieldRow('Bis', toField),
      durationRow),
    extrasHost,
    preview,
  );
  cleanup = mountBottomBar(root, submit);
  paintExtras();
  setTo(form.to);

  function create() {
    const active = dates(form).filter((d) => isOn(form, d));
    if (!active.length || !timeOk()) return;
    haptic('success');
    const { type, startTime, endTime, location, room, groups, groupsText, instrumentIds, color } = form.template;
    const items = form.items.filter((item) => item.title.trim());
    const infos = form.infos.filter((info) => info.text.trim());
    const ids = active.map((date) => createRehearsal({
      date,
      type,
      startTime,
      endTime,
      location,
      room,
      groups,
      groupsText,
      instrumentIds,
      color,
      tags: [...form.tags],
      deadline: form.deadlineDays ? `${addDays(date, -form.deadlineDays)}T12:00:00` : null,
      infos: infos.map((info) => ({ emoji: info.emoji, text: info.text.trim() })),
      scheduleItems: items.map((item) => ({ date, time: item.time, title: item.title.trim(), groups: null })),
    }).id);
    const knownType = rehearsalTypes.find((t) => t.name === type);
    if (knownType) knownType.count += ids.length;
    else rehearsalTypes.push({ name: type, count: ids.length });
    for (const name of form.tags) {
      const known = knownTags.find((t) => t.name === name);
      if (known) known.count += ids.length;
      else knownTags.push({ name, count: ids.length });
    }
    draft = null;
    flashRehearsals(ids);
    back();
    undoToast(ids, ids.length === 1 ? '1 Probe angelegt' : `${ids.length} Proben angelegt`);
  }
}

registerScreen('series', {
  title: 'Serie anlegen',
  tab: '/rehearsals',
  render,
  onLeave() {
    cleanup?.();
    cleanup = null;
    if (!location.hash.startsWith('#/rehearsals/series')) draft = null;
    rowsMode();
  },
});

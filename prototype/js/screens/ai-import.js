/* Plan importieren (Leitung) */

import * as ui from '../ui.js';
import { registerScreen, setHeader, back, toast, h, html, icon, refreshIcons, haptic } from '../app.js';
import { deskBar } from './team.js';
import { pickRow, rowsMode } from '../rsvp.js';
import {
  NOW, TODAY, colors, ensemble, formatDate, getSection, getUpcoming, locations, rehearsalTypes, rehearsals, roles, sections, tags as knownTags,
} from '../data.js';
import {
  addDays, buildRehearsal, createRehearsal, dateSep, fieldError, flashRehearsals, monthLabel, mountBottomBar, nextFreeDate, pickLocation, settleDrafts, undoToast, weekday,
} from './rehearsals.js';

const MAX_ROWS = 300;
const DEFAULT_COLOR = '#e5e7eb';
const DEFAULT_EMOJI = '❗';
const DEFAULT_MINUTES = 150;
const LIMITS = { location: 255, type: 100, tag: 100, label: 255, emoji: 16 };
const WEEKDAY_NAMES = [null, 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];
const WEEKDAY_KEYS = { mo: 1, di: 2, mi: 3, do: 4, fr: 5, sa: 6, so: 7 };

let cleanup = null;
let draft = emptyDraft();

function emptyDraft() {
  return { text: '', rows: [], stale: false, error: '', promptOpen: false };
}

/* Prompt */

const quoted = (names) => names.map((name) => `"${name}"`).join(', ');

function buildPrompt() {
  const roleNames = roles.filter((r) => r.name !== 'Mitglied').map((r) => r.name);
  const groupNames = sections.filter((s) => s.kind === 'section').map((s) => s.name);
  return `Du bist ein Assistent, der Probenpläne (PDF oder Text) in ein striktes JSON-Format übersetzt, das direkt in den Probenplaner importiert werden kann.

## Aktuelle Konfiguration
Verwende bevorzugt diese Werte:
- Existierende Rollen: [${quoted(roleNames)}]
- Existierende Orte: [${quoted(locations.map((l) => l.name))}]
- Existierende Arten (Beispiele): [${quoted(rehearsalTypes.map((t) => t.name))}]
- Verfügbare Gruppen: [${quoted(groupNames)}]

## Regeln
1. Mehrtägige Proben: lieber eine Probe pro Tag.
2. roles nur für Spezialprojekte, bei denen sich jede Person extra anmeldet. Bei regulären Proben bleibt roles leer.
3. groups: Bei Tutti-Proben strikt leer []. Sonst die passenden Gruppennamen aus der Liste.
4. Orte nur aus der Liste „Existierende Orte“. Passt ein Ort oder ein Kürzel wie NBK zu keinem davon, schreibe es unverändert in location; erfinde weder Orte noch Adressen. Neue Arten und Tags darfst du verwenden, neue Rollen werden nicht angelegt.
5. Tag "regulär" nur für feste, wiederkehrende Wochenproben.
6. schedule_items nur mit eigener Uhrzeit. Alles ohne Uhrzeit gehört in infos, jeweils mit passendem Emoji.
7. color: exakt einer dieser Werte: ${colors.map((c) => c.value).join(', ')}.
8. Fehlt die Uhrzeit, nimm Beginn 19:30 und Ende 22:00. Fehlt das Jahr, wähle es so, dass die Probe in der Zukunft liegt.
9. weekday exakt so, wie er im Plan steht. Steht keiner im Plan, lass das Feld weg.
10. type ist optional und nur für echte Bezeichnungen wie Generalprobe oder Konzert.

## Zieldatenformat
{
  "rehearsals": [
    {
      "weekday": "Sa",
      "start": "YYYY-MM-DD HH:MM:SS",
      "end": "YYYY-MM-DD HH:MM:SS",
      "location": "Name des Ortes",
      "type": "Art der Probe",
      "color": "#e5e7eb",
      "tags": ["Tag1", "regulär"],
      "groups": ["Gruppenname"],
      "roles": ["Rollenname"],
      "schedule_items": [{"time": "HH:MM", "label": "Was geprobt wird"}],
      "infos": [{"emoji": "📍", "text": "Treffpunkt am Hintereingang"}]
    }
  ]
}

Gib ausschließlich das JSON in einem Markdown-Code-Block zurück, ohne weiteren Text.`;
}

function sampleJson() {
  const upcoming = getUpcoming();
  const taken = upcoming.find((r) => r.type === 'Probe' && !r.cancelled && r.date > TODAY) ?? rehearsals[0];
  const after = addDays(upcoming.at(-1)?.endDate ?? TODAY, 1);
  const tuesday = nextFreeDate(after, 2);
  const saturday = nextFreeDate(after, 6);
  const nextTuesday = nextFreeDate(addDays(tuesday, 1), 2);
  const stamp = (date, time) => `${date} ${time}:00`;
  const day = (iso) => formatDate(iso).split(',')[0];
  const regular = ['Streicher', 'Bläser', 'Schlagwerk'];
  return JSON.stringify({
    rehearsals: [
      {
        weekday: day(taken.date),
        start: stamp(taken.date, taken.startTime),
        end: stamp(taken.date, taken.endTime),
        location: taken.location,
        type: taken.type,
        tags: ['regulär'],
        groups: regular,
      },
      {
        weekday: day(tuesday),
        start: stamp(tuesday, '19:00'),
        end: stamp(tuesday, '21:30'),
        location: ensemble.defaultLocation,
        type: 'Probe',
        tags: ['regulär', 'Dvořák 8'],
        groups: regular,
        infos: [{ emoji: '🎼', text: 'Neue Noten: Dvořák 8' }],
      },
      {
        weekday: day(saturday),
        start: stamp(saturday, '10:00'),
        end: stamp(saturday, '13:00'),
        location: 'NBK',
        type: 'Registerprobe',
        color: '#8b5cf6',
        tags: ['Dvořák 8'],
        groups: ['Streicher'],
        schedule_items: [
          { time: '10:00', label: 'Stimmen & Einspielen' },
          { time: '10:15', label: 'Dvořák 8, 1. Satz' },
          { time: '12:00', label: 'Pause' },
        ],
      },
      {
        weekday: day(nextTuesday),
        start: stamp(nextTuesday, '19:00'),
        location: ensemble.defaultLocation,
        type: 'Probe',
        groups: ['Holzbläser', 'Hörner'],
        infos: [{ emoji: '🎺', text: 'Dämpfer mitbringen' }],
      },
    ],
  }, null, 2);
}

/* Parsing */

const pad = (n) => String(n).padStart(2, '0');
const blank = (value) => value == null || (typeof value === 'string' && value.trim() === '');
const clean = (value) => String(value ?? '').replace(/\s+/g, ' ').trim();
const key = (text) => String(text).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]/g, '');
const shortDay = (iso) => `${iso.slice(8, 10)}.${iso.slice(5, 7)}.`;
const isoWeekday = (iso) => weekday(iso) || 7;

function parseDateTime(raw) {
  if (typeof raw !== 'string') return null;
  const match = raw.trim().match(/^(\d{4})-(\d{1,2})-(\d{1,2})[ T]+(\d{1,2}):(\d{2})(?::\d{2})?$/);
  if (!match) return null;
  const [year, month, day, hours, minutes] = match.slice(1).map(Number);
  const probe = new Date(Date.UTC(year, month - 1, day));
  if (probe.getUTCMonth() !== month - 1 || probe.getUTCDate() !== day || hours > 23 || minutes > 59) return null;
  return { date: `${year}-${pad(month)}-${pad(day)}`, time: `${pad(hours)}:${pad(minutes)}` };
}

function parseTime(raw) {
  const match = String(raw ?? '').trim().match(/^(\d{1,2})[:.](\d{2})(?::\d{2})?$/);
  if (!match) return null;
  const [hours, minutes] = [Number(match[1]), Number(match[2])];
  return hours > 23 || minutes > 59 ? null : `${pad(hours)}:${pad(minutes)}`;
}

function addMinutes(date, time, minutes) {
  const [hours, mins] = time.split(':').map(Number);
  const total = hours * 60 + mins + minutes;
  return { date: addDays(date, Math.floor(total / 1440)), time: `${pad(Math.floor((total % 1440) / 60))}:${pad(total % 60)}` };
}

function parseWeekday(raw) {
  if (blank(raw)) return null;
  return WEEKDAY_KEYS[key(raw).slice(0, 2)] ?? null;
}

function lookups() {
  const groups = new Map();
  for (const s of sections) {
    for (const name of [s.id, s.name, ...(s.aliases ?? [])]) groups.set(key(name), s.id);
  }
  const roleMap = new Map(roles.filter((r) => !r.isDefault).map((r) => [key(r.name), r]));
  return { groups, roleMap };
}

function asList(raw) {
  if (typeof raw === 'string') return raw.trim() === '' ? [] : [raw];
  return raw;
}

function groupsLabel(groupIds, instrumentIds) {
  const sentence = ui.groupsSentence(instrumentIds);
  if (sentence.startsWith('Tutti')) return sentence;
  const covered = (id, other) => other !== id && getSection(other).instrumentIds.some((leaf) => getSection(id).instrumentIds.includes(leaf)) && getSection(id).instrumentIds.every((leaf) => getSection(other).instrumentIds.includes(leaf));
  const names = groupIds.filter((id) => !groupIds.some((other) => covered(id, other))).map((id) => getSection(id).name);
  return names.length > 1 ? `${names.slice(0, -1).join(', ')} und ${names.at(-1)}` : names[0];
}

function knownPlace(text) {
  const wanted = key(text);
  const exact = locations.find((l) => key(l.name) === wanted);
  if (exact) return exact.name;
  if (wanted.length < 4) return null;
  const partial = locations.filter((l) => key(l.name).includes(wanted));
  return partial.length === 1 ? partial[0].name : null;
}

function normalizeRow(raw, ctx) {
  const row = { errors: [], warnings: [], notes: [], duplicate: null, mismatch: null, fields: null, place: null };
  const fields = {
    date: null,
    endDate: null,
    startTime: null,
    endTime: null,
    location: ensemble.defaultLocation,
    type: 'Probe',
    color: DEFAULT_COLOR,
    groups: ['tutti'],
    groupsText: 'Tutti',
    instrumentIds: [...(getSection('tutti')?.instrumentIds ?? [])],
    roleIds: [],
    roles: [],
    tags: [],
    infos: [],
    scheduleItems: [],
  };
  row.fields = fields;
  if (raw == null || typeof raw !== 'object' || Array.isArray(raw)) {
    row.errors.push('Eintrag ist kein gültiges Termin-Objekt');
    return row;
  }

  const start = parseDateTime(raw.start);
  if (!start) {
    if (blank(raw.start)) row.errors.push('Startzeit fehlt');
    else if (/^\s*\d{4}-\d{1,2}-\d{1,2}\s*$/.test(raw.start)) row.errors.push('Uhrzeit fehlt');
    else row.errors.push('Startzeit ungültig');
  } else {
    let end = parseDateTime(raw.end);
    if (!end && typeof raw.end === 'string') {
      const time = parseTime(raw.end);
      if (time) end = { date: start.date, time };
    }
    if (!end) {
      end = addMinutes(start.date, start.time, DEFAULT_MINUTES);
      const label = end.date === start.date ? end.time : `${shortDay(end.date)} ${end.time}`;
      row.warnings.push(`${blank(raw.end) ? 'Ende fehlt' : 'Ende ungültig'}, ${label} angenommen`);
    } else {
      const from = `${start.date}T${start.time}`;
      const to = `${end.date}T${end.time}`;
      if (to < from) row.errors.push('Ende vor Beginn');
      else if (to === from) row.warnings.push('Beginn gleich Ende');
      else if (end.date !== start.date) row.notes.push(`Mehrtägig bis ${end.date.slice(8, 10)}.${end.date.slice(5, 7)}.${end.date.slice(0, 4)}`);
    }
    Object.assign(fields, { date: start.date, endDate: end.date, startTime: start.time, endTime: end.time });
    if (`${end.date}T${end.time}:00` < NOW) row.warnings.push('Liegt in der Vergangenheit');
    const named = parseWeekday(raw.weekday);
    if (named && named !== isoWeekday(start.date)) {
      row.mismatch = `Im Plan steht ${WEEKDAY_NAMES[named]}, der ${shortDay(start.date)} ist aber ein ${WEEKDAY_NAMES[isoWeekday(start.date)]}`;
      row.warnings.push(row.mismatch);
    }
  }

  let location = raw.location != null && typeof raw.location !== 'object' ? clean(raw.location) : '';
  if (raw.location != null && typeof raw.location === 'object') row.warnings.push('Ort ungültig');
  if (location.length > LIMITS.location) {
    location = location.slice(0, LIMITS.location);
    row.warnings.push('Ort gekürzt');
  }
  if (location) {
    const known = knownPlace(location);
    if (known) {
      fields.location = known;
      if (key(known) !== key(location)) row.notes.push(`${location}: ${known}`);
    } else {
      fields.location = location;
      row.place = location;
    }
  }

  let type = raw.type != null && typeof raw.type !== 'object' ? clean(raw.type) : '';
  if (raw.type != null && typeof raw.type === 'object') row.warnings.push('Art ungültig');
  if (type.length > LIMITS.type) {
    type = type.slice(0, LIMITS.type);
    row.warnings.push('Art gekürzt');
  }
  if (type) fields.type = type;

  if (!blank(raw.color)) {
    const color = typeof raw.color === 'string' ? raw.color.trim().toLowerCase() : '';
    const long = color.match(/^#?([0-9a-f]{6})$/);
    const short = color.match(/^#?([0-9a-f])([0-9a-f])([0-9a-f])$/);
    if (long) fields.color = `#${long[1]}`;
    else if (short) fields.color = `#${short[1]}${short[1]}${short[2]}${short[2]}${short[3]}${short[3]}`;
    else row.warnings.push('Farbe ungültig');
  }

  let groupList = asList(raw.groups ?? []);
  if (!Array.isArray(groupList)) {
    row.warnings.push('Gruppen ungültig');
    groupList = [];
  }
  const groupIds = [];
  const unknownGroups = [];
  for (const entry of groupList) {
    if (entry === false || blank(entry)) continue;
    const label = typeof entry === 'object' ? '' : clean(entry);
    if (!label) continue;
    const id = ctx.groups.get(key(label));
    if (!id) unknownGroups.push(label);
    else if (!groupIds.includes(id)) groupIds.push(id);
  }
  const unknown = [...new Set(unknownGroups)];
  if (!groupIds.length && unknown.length) row.warnings.push(`Unbekannt: ${unknown.join(', ')}, daher Tutti`);
  else if (unknown.length) row.warnings.push(`Unbekannt: ${unknown.join(', ')}`);
  if (groupIds.length && !groupIds.includes('tutti')) {
    const instrumentIds = [...new Set(groupIds.flatMap((id) => getSection(id)?.instrumentIds ?? [id]))];
    Object.assign(fields, { groups: groupIds, instrumentIds, groupsText: groupsLabel(groupIds, instrumentIds) });
  }

  let roleList = asList(raw.roles ?? []);
  if (!Array.isArray(roleList)) {
    row.errors.push('Rollen ungültig');
    roleList = [];
  }
  const unknownRoles = [];
  for (const entry of roleList) {
    if (entry === false || blank(entry)) continue;
    const label = typeof entry === 'object' ? '' : clean(entry);
    if (!label) continue;
    const role = ctx.roleMap.get(key(label));
    if (!role) unknownRoles.push(label);
    else if (!fields.roleIds.includes(role.id)) {
      fields.roleIds.push(role.id);
      fields.roles.push({ id: role.id, name: role.name, tagColor: role.tagColor, icon: role.icon });
    }
  }
  if (unknownRoles.length) row.errors.push(`Rolle unbekannt: ${[...new Set(unknownRoles)].join(', ')}`);

  let tagList = asList(raw.tags ?? []);
  if (!Array.isArray(tagList)) {
    row.warnings.push('Tags ungültig');
    tagList = [];
  }
  const seen = new Set();
  for (const entry of tagList) {
    if (entry == null || typeof entry === 'object') continue;
    let tag = clean(String(entry).trim().replace(/^#+/, ''));
    if (!tag) continue;
    if (tag.length > LIMITS.tag) {
      tag = tag.slice(0, LIMITS.tag);
      row.warnings.push('Tag gekürzt');
    }
    if (seen.has(tag.toLowerCase())) continue;
    seen.add(tag.toLowerCase());
    fields.tags.push(tag);
  }

  let itemList = raw.schedule_items;
  if (itemList == null || itemList === '') itemList = [];
  if (!Array.isArray(itemList)) {
    row.warnings.push('Ablauf ungültig');
    itemList = [];
  }
  for (const item of itemList) {
    let time = null;
    let label = '';
    if (item && typeof item === 'object') {
      time = parseTime(item.time);
      label = item.label != null && typeof item.label !== 'object' ? clean(item.label) : '';
    } else if (typeof item === 'string') {
      const match = item.match(/^\s*(\d{1,2}[:.]\d{2})\s*(?:Uhr)?\s*[-–:]?\s*(.+)$/u);
      if (match) {
        time = parseTime(match[1]);
        label = clean(match[2]);
      } else {
        label = clean(item);
      }
    }
    if (!label) {
      row.warnings.push('Ablaufpunkt ohne Text übersprungen');
      continue;
    }
    if (!time) {
      row.warnings.push('Ablaufpunkt ohne Uhrzeit übersprungen');
      continue;
    }
    if (label.length > LIMITS.label) {
      label = label.slice(0, LIMITS.label);
      row.warnings.push('Ablaufpunkt gekürzt');
    }
    fields.scheduleItems.push({ date: fields.date, time, title: label, groups: null });
  }

  let infoList = raw.infos;
  if (infoList == null || infoList === '') infoList = [];
  if (typeof infoList === 'string') infoList = [infoList];
  if (!Array.isArray(infoList)) {
    row.warnings.push('Infos ungültig');
    infoList = [];
  }
  let emptyInfos = 0;
  for (const info of infoList) {
    let emoji = '';
    let text = '';
    if (info && typeof info === 'object') {
      emoji = info.emoji != null && typeof info.emoji !== 'object' ? String(info.emoji).trim() : '';
      text = info.text != null && typeof info.text !== 'object' ? String(info.text).trim() : '';
    } else if (info != null) {
      text = String(info).trim();
    }
    if (!text) {
      emptyInfos += 1;
      continue;
    }
    if (!emoji || [...emoji].length > LIMITS.emoji) emoji = DEFAULT_EMOJI;
    fields.infos.push({ emoji, text });
  }
  if (emptyInfos) row.warnings.push('Leere Infos übersprungen');
  return row;
}

function parseAnswer(text) {
  const fenced = text.match(/```(?:json)?\s*([\s\S]*?)\s*```/);
  let payload;
  try {
    payload = JSON.parse(fenced ? fenced[1] : text);
  } catch {
    return { error: 'Die Antwort ist kein gültiges JSON – kopiere die komplette Antwort der KI ab { und füge sie erneut ein.' };
  }
  const list = Array.isArray(payload) ? payload : payload?.rehearsals;
  if (!Array.isArray(list)) return { error: 'Im JSON fehlt die Liste „rehearsals“ – bitte die KI, das Format aus Schritt 1 zu nutzen, und füge die neue Antwort ein.' };
  if (!list.length) return { error: 'Die Liste „rehearsals“ ist leer – bitte die KI, den Plan erneut zu übersetzen, und füge die neue Antwort ein.' };
  if (list.length > MAX_ROWS) return { error: `Die Antwort hat mehr als ${MAX_ROWS} Proben – teile den Plan auf und füge die Teile nacheinander ein.` };
  return { list };
}

function analyse(list) {
  const ctx = lookups();
  const rows = list.map((raw, index) => ({ index, ...normalizeRow(raw, ctx) }));
  const existing = new Set(rehearsals.map((r) => `${r.date} ${r.startTime}`));
  const counts = new Map();
  for (const row of rows) {
    if (!row.fields.date) continue;
    const stamp = `${row.fields.date} ${row.fields.startTime}`;
    counts.set(stamp, (counts.get(stamp) ?? 0) + 1);
  }
  for (const row of rows) {
    if (!row.fields.date) continue;
    const stamp = `${row.fields.date} ${row.fields.startTime}`;
    if (existing.has(stamp)) row.duplicate = 'Schon vorhanden';
    else if (counts.get(stamp) > 1) row.duplicate = 'Doppelt im Import';
    row.on = !row.errors.length && !row.duplicate && !row.mismatch && !row.place;
  }
  return rows;
}

function nextImportTag() {
  const numbers = rehearsals.flatMap((r) => r.tags).map((tag) => /^Importiert-(\d+)$/i.exec(tag)).filter(Boolean).map((m) => Number(m[1]));
  return `Importiert-${Math.max(0, ...numbers) + 1}`;
}

function messageOf(row) {
  if (row.errors.length) return { text: row.errors.join(' · '), kind: 'error' };
  if (row.place) return { text: `Ort unbekannt: ${row.place}`, kind: 'warn' };
  const parts = [row.duplicate, ...row.warnings, ...row.notes].filter(Boolean);
  return parts.length ? { text: parts.join(' · '), kind: row.warnings.length || row.duplicate ? 'warn' : 'note' } : null;
}

/* Screen */

function copy(text, message) {
  navigator.clipboard?.writeText(text).catch(() => {});
  haptic('light');
  toast(html`${icon('circle-check')} ${message}`);
}

function render(root, params, ctx) {
  if (!ui.guard(ctx, ['conductor'])) return;
  setHeader({ title: 'Plan importieren' });
  document.documentElement.dataset.rows = '1';
  root.classList.add('import-screen', 'import-flow');
  if (ui.isWide()) {
    root.classList.add('proben-desk');
    deskBar(root, { title: 'Plan importieren', showBack: true });
  }

  const previewHost = h('div', { class: 'import-preview proben-rows' });
  const primary = h('button', {
    class: 'button button--primary button--lg button--block',
    type: 'button',
    onclick: () => (hasPreview() ? importAll() : showPreview()),
  });
  const error = fieldError('', 'import-error', true);

  const showError = (message, { silent = false } = {}) => {
    draft.error = message ?? '';
    error.lastElementChild.textContent = draft.error;
    error.hidden = !message;
    if (message) {
      answer.setAttribute('aria-invalid', 'true');
      answer.setAttribute('aria-describedby', error.id);
      if (!silent) haptic('error');
    } else {
      answer.removeAttribute('aria-invalid');
      answer.removeAttribute('aria-describedby');
    }
  };

  const answer = h('textarea', {
    class: 'textarea import-text',
    id: 'import-answer',
    rows: 8,
    spellcheck: 'false',
    placeholder: '{"rehearsals": [ … ]}',
    'aria-label': 'Antwort der KI',
    oninput: () => {
      draft.text = answer.value;
      if (draft.rows.length) draft.stale = true;
      showError('');
      paintPreview();
    },
  });

  const prompt = buildPrompt();

  const insertSample = () => {
    haptic('light');
    answer.value = sampleJson();
    draft.text = answer.value;
    if (draft.rows.length) draft.stale = true;
    showError('');
    paintPreview();
  };

  const hasPreview = () => draft.rows.length > 0 && !draft.stale;
  const active = () => draft.rows.filter((row) => row.on);

  const syncPrimary = () => {
    if (hasPreview()) {
      const n = active().length;
      primary.disabled = n === 0;
      primary.replaceChildren(h('span', { class: 'button__label' }, `${n} importieren`));
      return;
    }
    primary.disabled = !answer.value.trim();
    primary.replaceChildren(h('span', { class: 'button__icon' }, icon('scan-eye')), h('span', { class: 'button__label' }, 'Vorschau'));
    refreshIcons(primary);
  };

  const unknownPlaces = () => {
    const seen = new Map();
    for (const row of draft.rows) {
      if (row.place) seen.set(row.place, (seen.get(row.place) ?? 0) + 1);
    }
    return [...seen];
  };

  const assignPlace = (raw, name) => {
    haptic('selection');
    for (const row of draft.rows) {
      if (row.place !== raw) continue;
      row.place = null;
      row.fields.location = name;
      row.notes.push(`${raw}: ${name}`);
      row.on = !row.errors.length && !row.duplicate && !row.mismatch;
    }
    paintPreview();
  };

  const placeQuestions = () => {
    const open = unknownPlaces();
    if (!open.length) return null;
    return h('section', { class: 'import-places', 'aria-label': 'Orte zuordnen' },
      ui.sectionHeader('Ort unbekannt', { count: open.length }),
      h('div', { class: 'list-group' }, open.map(([raw, n]) => h('button', {
        class: 'list-row import-place',
        type: 'button',
        onclick: () => pickLocation(null, (name) => assignPlace(raw, name)),
      },
      h('span', { class: 'list-row__body' },
        h('span', { class: 'list-row__title import-place__name' }, raw)),
      h('span', { class: 'list-row__trail' },
        h('span', { class: 'list-row__value' }, n === 1 ? '1 Probe' : `${n} Proben`),
        h('span', { class: 'import-place__choose' }, 'Ort wählen'),
        icon('chevron-right', { class: 'list-row__chevron' }))))));
  };

  const rowItem = (row) => {
    const failed = row.errors.length > 0 || Boolean(row.place);
    const message = messageOf(row);
    const r = row.fields.date ? buildRehearsal(row.fields, 0) : null;
    if (r) {
      r.roleIds = [...row.fields.roleIds];
      r.roles = row.fields.roles.map((role) => ({ ...role }));
    }
    const shown = r ?? { ...row.fields, start: null, multiDay: false, infos: row.fields.infos };
    return pickRow(shown, {
      on: Boolean(row.on),
      disabled: failed,
      message,
      label: r ? `${formatDate(row.fields.date)}, ${ui.timeRange(r)}${message ? `: ${message.text}` : ''}` : `Eintrag ${row.index + 1}${message ? `: ${message.text}` : ''}`,
      lead: r ? undefined : h('span', { class: 'proben-row__wd' }, '–'),
      dateText: r ? undefined : `Eintrag ${row.index + 1}`,
      timeText: r ? undefined : '',
      onToggle: () => {
        haptic('selection');
        row.on = !row.on;
        paintPreview();
      },
    });
  };

  function paintPreview() {
    root.classList.toggle('has-rows', draft.rows.length > 0);
    previewHost.classList.toggle('is-empty', draft.rows.length === 0);
    previewHost.classList.toggle('is-stale', draft.stale);
    if (!draft.rows.length) {
      previewHost.replaceChildren(
        ui.sectionHeader('Vorschau'),
        h('div', { class: 'import-empty' }, icon('scan-eye'), h('span', {}, 'Noch keine Vorschau')));
      refreshIcons(previewHost);
      syncPrimary();
      return;
    }
    const total = draft.rows.length;
    const n = active().length;
    const nodes = [placeQuestions(), ui.sectionHeader('Vorschau', { count: n === total ? String(total) : `${n}/${total}` })].filter(Boolean);
    const order = (row) => `${row.fields.date ?? '9999'}${row.fields.startTime ?? ''}${String(row.index).padStart(4, '0')}`;
    const sorted = [...draft.rows].sort((a, b) => order(a).localeCompare(order(b)));
    const groups = [];
    for (const row of sorted) {
      const label = row.fields.date ? monthLabel(row.fields.date, { year: true }) : 'Ohne Datum';
      if (groups.at(-1)?.label !== label) groups.push({ label, items: [] });
      groups.at(-1).items.push(row);
    }
    for (const group of groups) {
      nodes.push(h('section', { class: 'rehearsals-group' }, dateSep(group.label), h('div', { class: 'proben-card' }, group.items.map(rowItem))));
    }
    previewHost.replaceChildren(...nodes);
    refreshIcons(previewHost);
    syncPrimary();
  }

  function showPreview() {
    const text = answer.value.trim();
    if (!text) return;
    haptic('light');
    const parsed = parseAnswer(text);
    if (parsed.error) {
      showError(parsed.error);
      answer.focus();
      return;
    }
    showError('');
    draft.rows = analyse(parsed.list);
    draft.stale = false;
    paintPreview();
    if (!ui.isWide()) previewHost.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
  }

  const promptBody = h('pre', { class: 'import-prompt', id: 'import-prompt', tabindex: '0', 'aria-label': 'Prompt' }, prompt);
  const promptWrap = h('div', { class: 'import-prompt-wrap' }, promptBody);
  const promptToggle = h('button', {
    class: 'import-prompt-toggle',
    type: 'button',
    'aria-expanded': 'false',
    'aria-controls': 'import-prompt',
    onclick: () => {
      haptic('selection');
      setPromptOpen(!draft.promptOpen);
    },
  }, h('span', { class: 'import-prompt-toggle__label' }, 'Prompt'), icon('chevron-down', { class: 'import-prompt-toggle__chevron' }));
  const promptCard = h('div', { class: 'import-prompt-card' }, promptToggle, promptWrap);

  function setPromptOpen(open) {
    draft.promptOpen = open;
    promptCard.classList.toggle('is-open', open);
    promptToggle.setAttribute('aria-expanded', String(open));
    promptWrap.inert = !open;
  }

  const step = (no, title, { action = null, body = null } = {}) => {
    const id = `import-step-${no}`;
    return h('section', { class: 'import-step', 'aria-labelledby': id },
      h('div', { class: 'import-step__head' },
        h('span', { class: 'import-step__no', 'aria-hidden': 'true' }, String(no)),
        h('h2', { class: 'import-step__title', id }, title),
        action ? h('button', { class: 'section-header__action', type: 'button', onclick: action.onClick }, action.icon ? icon(action.icon) : null, action.label) : null),
      body);
  };

  root.append(
    h('div', { class: 'import-steps' },
      step(1, 'Prompt kopieren', {
        action: { label: 'Kopieren', icon: 'copy', onClick: () => copy(prompt, 'Prompt kopiert') },
        body: promptCard,
      }),
      step(2, 'In die KI einfügen, mit deinem Plan'),
      step(3, 'Antwort einfügen', {
        action: { label: 'Beispiel einfügen', onClick: insertSample },
        body: h('div', { class: 'import-field' }, answer, error),
      })),
    previewHost,
  );
  cleanup = mountBottomBar(root, primary);

  answer.value = draft.text;
  setPromptOpen(draft.promptOpen);
  if (draft.error) showError(draft.error, { silent: true });
  paintPreview();
  refreshIcons(root);

  function importAll() {
    const chosen = active();
    if (!chosen.length) return;
    haptic('success');
    const importTag = nextImportTag();
    const ids = [...chosen]
      .sort((a, b) => `${a.fields.date}${a.fields.startTime}`.localeCompare(`${b.fields.date}${b.fields.startTime}`))
      .map((row) => {
        const { roleIds, roles: rowRoles, ...fields } = row.fields;
        const tagList = [...fields.tags];
        if (!tagList.includes(importTag)) tagList.push(importTag);
        const made = createRehearsal({ ...fields, tags: tagList }, { draft: roleIds.length > 0 });
        if (roleIds.length) {
          made.roleIds = [...roleIds];
          made.roles = rowRoles.map((role) => ({ ...role }));
        }
        return made.id;
      });
    settleDrafts();
    for (const row of chosen) {
      const type = rehearsalTypes.find((t) => t.name === row.fields.type);
      if (type) type.count += 1;
      else rehearsalTypes.push({ name: row.fields.type, count: 1 });
      for (const name of [...row.fields.tags, importTag]) {
        const tag = knownTags.find((t) => t.name === name);
        if (tag) tag.count += 1;
        else knownTags.push({ name, count: 1 });
      }
    }
    draft = emptyDraft();
    flashRehearsals(ids);
    back();
    undoToast(ids, ids.length === 1 ? '1 Probe importiert' : `${ids.length} Proben importiert`);
  }
}

registerScreen('ai-import', {
  title: 'Plan importieren',
  tab: '/rehearsals',
  render,
  onLeave() {
    cleanup?.();
    cleanup = null;
    if (!location.hash.startsWith('#/rehearsals/import')) draft = emptyDraft();
    rowsMode();
  },
});

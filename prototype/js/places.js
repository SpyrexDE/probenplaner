/* Orte: the single place store (data.js `locations`) and the sheet that creates and edits places */

import { openSheet, toast, haptic, h, html, icon } from './app.js';
import { ensemble, locations, rehearsals } from './data.js';
import * as ui from './ui.js';
import { fieldError } from './screens/rehearsals.js';

function plural(n, one, many) {
  return `${n} ${n === 1 ? one : many}`;
}

export function usageLabel(n) {
  return plural(n, 'Probe', 'Proben');
}

export function usageOf(name) {
  return rehearsals.filter((r) => r.location === name).length;
}

function entryOf(name) {
  return locations.find((l) => l.name === name) ?? null;
}

function sameName(a, b) {
  return a.trim().toLowerCase() === b.trim().toLowerCase();
}

function ensure(name) {
  let entry = entryOf(name);
  if (!entry) {
    entry = { name, count: 0, address: null };
    locations.push(entry);
  }
  return entry;
}

function syncRehearsals(name) {
  const address = entryOf(name)?.address ?? null;
  for (const r of rehearsals) {
    if (r.location === name) r.address = address;
  }
}

function sync() {
  for (const name of new Set([ensemble.defaultLocation, ...rehearsals.map((r) => r.location)])) {
    if (name) ensure(name);
  }
  for (const entry of locations) entry.count = usageOf(entry.name);
}

export function listPlaces() {
  sync();
  const standard = ensemble.defaultLocation;
  return [...locations].sort((a, b) =>
    (b.name === standard) - (a.name === standard)
    || usageOf(b.name) - usageOf(a.name)
    || a.name.localeCompare(b.name, 'de'));
}

export function addPlace(name, address = null) {
  const text = name.trim();
  const known = locations.find((l) => sameName(l.name, text));
  if (known) {
    if (address && !known.address) setAddress(known.name, address);
    return known;
  }
  const entry = ensure(text);
  entry.address = address?.trim() || null;
  return entry;
}

export function setAddress(name, address) {
  const entry = ensure(name);
  entry.address = address?.trim() || null;
  syncRehearsals(name);
  return entry;
}

export function renamePlace(oldName, newName) {
  const text = newName.trim();
  if (!text || text === oldName) return entryOf(oldName);
  const entry = entryOf(oldName);
  const target = entryOf(text);
  let result = entry;
  if (entry && target) {
    if (!target.address) target.address = entry.address;
    locations.splice(locations.indexOf(entry), 1);
    result = target;
  } else if (entry) {
    entry.name = text;
  } else {
    result = ensure(text);
  }
  for (const r of rehearsals) {
    if (r.location === oldName) r.location = text;
  }
  if (ensemble.defaultLocation === oldName) ensemble.defaultLocation = text;
  syncRehearsals(text);
  return result;
}

export function removePlace(name) {
  if (usageOf(name) > 0 || ensemble.defaultLocation === name) return false;
  const index = locations.findIndex((l) => l.name === name);
  if (index === -1) return false;
  locations.splice(index, 1);
  return true;
}

function mapUrl(name, address) {
  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${name}, ${address}`)}`;
}

function standardRow(locked, checked, onChange) {
  const box = h('span', { class: ['check', 'place-check__box', checked && 'is-checked'], 'aria-hidden': 'true' }, icon('check'));
  const row = h('button', {
    class: ['place-check', locked && 'is-locked'],
    type: 'button',
    role: 'checkbox',
    'aria-checked': String(checked),
    'aria-label': locked ? 'Standardort, gesperrt' : null,
    'aria-description': locked ? 'Wähle einen anderen Ort als Standard, um das zu ändern' : null,
    onclick: () => {
      if (locked) {
        haptic('warning');
        toast(html`${icon('lock')} Wähle einen anderen Ort als Standard, um das zu ändern`);
        return;
      }
      haptic('light');
      const on = row.getAttribute('aria-checked') !== 'true';
      row.setAttribute('aria-checked', String(on));
      box.classList.toggle('is-checked', on);
      onChange(on);
    },
  },
  box,
  h('span', { class: 'place-check__label' }, 'Standardort'),
  locked ? icon('lock', { class: 'place-check__lock' }) : null);
  return row;
}

export function openPlaceSheet({ place = null, onSaved, onDeleted } = {}) {
  const entry = typeof place === 'string' ? entryOf(place) : place;
  const used = entry ? usageOf(entry.name) : 0;
  const isStandard = Boolean(entry) && ensemble.defaultLocation === entry.name;
  let makeStandard = false;

  const next = (target) => (event) => {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    target();
  };
  const name = h('input', {
    class: 'input place-field__input',
    id: 'place-name',
    type: 'text',
    value: entry?.name ?? '',
    maxlength: '50',
    autocomplete: 'off',
    autofocus: !entry,
    enterkeyhint: 'next',
  });
  const address = h('input', {
    class: 'input place-field__input',
    id: 'place-address',
    type: 'text',
    value: entry?.address ?? '',
    placeholder: 'optional',
    maxlength: '80',
    autocomplete: 'off',
    enterkeyhint: 'done',
  });

  const nameError = fieldError('', 'place-name-error', true);
  const showNameError = (message) => {
    nameError.lastElementChild.textContent = message ?? '';
    nameError.hidden = !message;
    if (message) {
      name.setAttribute('aria-invalid', 'true');
      name.setAttribute('aria-describedby', nameError.id);
      name.focus();
      haptic('error');
    } else {
      name.removeAttribute('aria-invalid');
      name.removeAttribute('aria-describedby');
    }
  };
  name.addEventListener('input', () => showNameError(''));

  const save = () => {
    const text = name.value.trim();
    if (!text) {
      showNameError('Name fehlt – gib dem Ort einen Namen.');
      return false;
    }
    if (locations.some((l) => l !== entry && sameName(l.name, text))) {
      showNameError('Diesen Ort gibt es schon – wähle einen anderen Namen.');
      return false;
    }
    const value = address.value.trim() || null;
    let saved;
    if (entry) {
      saved = text === entry.name ? entry : renamePlace(entry.name, text);
      setAddress(saved.name, value);
      if (makeStandard) ensemble.defaultLocation = saved.name;
    } else {
      saved = addPlace(text, value);
    }
    haptic('success');
    onSaved?.(saved);
    return true;
  };

  const remove = async () => {
    const ok = await ui.openConfirmSheet({
      title: `${entry.name} löschen?`,
      action: 'Ort löschen',
      icon: 'trash-2',
      destructive: true,
      stack: true,
      onConfirm: () => sheet.close(),
    });
    if (!ok || !removePlace(entry.name)) return;
    onDeleted?.(entry);
    toast(html`${icon('trash-2')} Ort gelöscht`);
  };

  const body = h('div', { class: 'vw-field place-form' },
    h('div', { class: 'place-field' },
      h('label', { class: 'place-field__label', for: 'place-name' }, 'Name'),
      name,
      nameError),
    h('div', { class: 'place-field' },
      h('label', { class: 'place-field__label', for: 'place-address' }, 'Adresse'),
      address));
  if (entry) {
    body.append(h('div', { class: 'list-group' },
      standardRow(isStandard, isStandard, (on) => { makeStandard = on; }),
      h('div', { class: 'list-row vw-value-row' },
        h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Verwendet in')),
        h('span', { class: 'list-row__trail' }, h('span', { class: 'list-row__value' }, usageLabel(used)))),
      entry.address ? h('a', { class: 'list-row is-external vw-plain-row', href: mapUrl(entry.name, entry.address), target: '_blank', rel: 'noopener' },
        h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Auf der Karte ansehen')),
        h('span', { class: 'list-row__trail' }, icon('arrow-up-right', { class: 'list-row__chevron' }))) : null));
  }

  const actions = [{ label: entry ? 'Speichern' : 'Anlegen', variant: 'primary', onClick: save }];
  if (entry && used === 0 && !isStandard) {
    actions.push({
      label: 'Löschen',
      variant: 'plain',
      icon: 'trash-2',
      onClick: () => {
        remove();
        return false;
      },
    });
  }

  const sheet = openSheet({
    title: entry ? entry.name : 'Neuer Ort',
    className: 'vw-sheet orte-sheet',
    content: body,
    actions,
  });
  name.addEventListener('keydown', next(() => address.focus()));
  address.addEventListener('keydown', next(() => save() && sheet.close()));
  return sheet;
}

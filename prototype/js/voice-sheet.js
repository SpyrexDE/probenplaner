/* Stimme wählen: eine Auswahl für Profil und Mitglied, gegliedert nach Registern */

import { h, icon, haptic, openSheet } from './app.js';
import { getSection, canChangeOwnVoice, voiceManagers } from './data.js';

const FAMILIES = ['Streicher', 'Holzbläser', 'Blechbläser', 'Schlagwerk', 'Harfe'];

export function voiceLockText() {
  const names = voiceManagers();
  return `Stimme ändern nur ${names.length > 1 ? `${names.slice(0, -1).join(', ')} und ${names.at(-1)}` : names[0] ?? 'Leitung'}`;
}

export function ownVoiceLock(person) {
  return canChangeOwnVoice(person) ? null : voiceLockText();
}

export function voicePath(sectionId) {
  const section = getSection(sectionId);
  if (!section) return sectionId ?? '';
  const parent = section.parentId && section.parentId !== 'tutti' ? getSection(section.parentId) : null;
  return parent ? `${parent.name} · ${section.name}` : section.name;
}

export function openVoiceSheet({ value, onSelect, title = 'Stimme' }) {
  const choose = (sectionId) => {
    haptic('selection');
    sheet.close();
    if (sectionId !== value) onSelect(sectionId);
  };
  const families = FAMILIES.map(getSection).filter(Boolean);
  const isSolo = (family) => family.instrumentIds.length === 1 && family.instrumentIds[0] === family.id;
  const voiceRow = (id) => {
    const checked = id === value;
    return h('button', {
      class: ['list-row', checked && 'is-checked'],
      type: 'button',
      role: 'radio',
      'aria-checked': String(checked),
      onclick: () => choose(id),
    },
    h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, getSection(id)?.name ?? id)),
    h('span', { class: 'list-row__trail' }, checked ? icon('check', { class: 'list-row__check' }) : null));
  };
  const solos = families.filter(isSolo);
  const content = h('div', { class: 'me-voices', role: 'radiogroup', 'aria-label': title },
    families.filter((family) => !isSolo(family)).map((family) => h('section', { class: 'me-voices__family', style: `--dot:${family.color}`, 'aria-label': family.name },
      h('h3', { class: 'me-voices__head' }, family.name),
      h('div', { class: 'list-group' }, family.instrumentIds.map(voiceRow)))),
    solos.length ? h('section', { class: 'me-voices__family me-voices__family--solo' },
      h('div', { class: 'list-group' }, solos.map((family) => voiceRow(family.id)))) : null);
  const rows = () => [...content.querySelectorAll('[role="radio"]')];
  for (const row of rows()) row.tabIndex = row.getAttribute('aria-checked') === 'true' ? 0 : -1;
  if (!rows().some((row) => row.tabIndex === 0)) rows()[0].tabIndex = 0;
  content.addEventListener('keydown', (event) => {
    const all = rows();
    const index = all.indexOf(document.activeElement);
    if (index === -1) return;
    const next = { ArrowDown: all[index + 1], ArrowRight: all[index + 1], ArrowUp: all[index - 1], ArrowLeft: all[index - 1], Home: all[0], End: all.at(-1) }[event.key];
    if (!next) return;
    event.preventDefault();
    all[index].tabIndex = -1;
    next.tabIndex = 0;
    next.focus();
  });
  const sheet = openSheet({ title, content, className: 'me-sheet sheet--large me-voice-sheet' });
  const heads = [...content.querySelectorAll('.me-voices__head')];
  const syncStuck = () => {
    const top = sheet.body.getBoundingClientRect().top;
    for (const head of heads) {
      const headTop = head.getBoundingClientRect().top;
      const familyTop = head.parentElement.getBoundingClientRect().top;
      head.classList.toggle('is-stuck', Math.abs(headTop - top) <= 1 && familyTop < headTop - 1);
    }
  };
  sheet.body.addEventListener('scroll', syncStuck, { passive: true });
  requestAnimationFrame(() => {
    content.querySelector('[role="radio"][tabindex="0"]')?.focus({ preventScroll: true });
    const family = content.querySelector('[aria-checked="true"]')?.closest('.me-voices__family');
    if (family && family !== content.firstElementChild) {
      sheet.body.scrollTop += family.getBoundingClientRect().top - sheet.body.getBoundingClientRect().top;
    }
    syncStuck();
  });
  return sheet;
}

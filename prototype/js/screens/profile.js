/* Profil and its pages */

import {
  registerScreen, ROLES, store, navigate, setRole, setTheme,
  openSheet, toast, haptic, h, html, raw, icon, refreshIcons,
} from '../app.js';
import { ensemble, users, roles, getRole, getSection, getMember, isSelfAssignable } from '../data.js';
import * as ui from '../ui.js';
import { deskBar } from './team.js';
import { valueRow } from '../value-row.js';
import { openVoiceSheet as pickVoice, ownVoiceLock, voicePath } from '../voice-sheet.js';
import { menuSelect } from '../menu.js';

const VERSION = '1.1.0';

/* Builders */

function tile(name, color) {
  return h('span', { class: 'icon-tile', style: color ? `--tile:var(${color})` : null, 'aria-hidden': 'true' }, icon(name));
}

function row({ lead, title, sub, value, trail, href, url, onClick, chevron, external, destructive, action, cls, attrs = {} }) {
  const tag = href || url ? 'a' : onClick ? 'button' : 'div';
  const hasChevron = chevron ?? Boolean(href || (onClick && !destructive && !action && !external));
  const hasTrail = value != null || trail || hasChevron || external;
  return h(tag, {
    class: ['list-row', sub && 'list-row--2line', destructive && 'list-row--destructive', action && 'list-row--action', external && 'is-external', cls],
    href: href ? `#${href}` : url ?? null,
    target: url ? '_blank' : null,
    rel: url ? 'noopener' : null,
    type: tag === 'button' ? 'button' : null,
    onclick: onClick ? (event) => {
      haptic('light');
      onClick(event);
    } : null,
    ...attrs,
  },
  lead ? h('span', { class: 'list-row__lead' }, lead) : null,
  h('span', { class: 'list-row__body' },
    h('span', { class: 'list-row__title' }, title),
    sub ? h('span', { class: 'list-row__sub' }, sub) : null),
  hasTrail ? h('span', { class: 'list-row__trail' },
    value != null ? h('span', { class: 'list-row__value' }, value) : null,
    trail ?? null,
    hasChevron ? icon('chevron-right', { class: 'list-row__chevron' }) : null,
    external ? icon('arrow-up-right', { class: 'list-row__chevron' }) : null) : null);
}

function switchRow({ lead, title, sub, checked, disabled, onChange }) {
  const input = h('input', {
    type: 'checkbox',
    role: 'switch',
    class: 'switch',
    checked: Boolean(checked),
    disabled: Boolean(disabled),
    onchange: () => {
      haptic('light');
      onChange?.(input.checked, input);
    },
  });
  return h('label', { class: ['list-row', 'list-row--switch', sub && 'list-row--2line', disabled && 'is-disabled'] },
    lead ? h('span', { class: 'list-row__lead' }, lead) : null,
    h('span', { class: 'list-row__body' },
      h('span', { class: 'list-row__title' }, title),
      sub ? h('span', { class: 'list-row__sub' }, sub) : null),
    h('span', { class: 'list-row__trail' }, input));
}

function card(title, ...children) {
  return h('section', { class: 'set-card' }, title ? ui.sectionHeader(title, { className: 'jmd-head' }) : null, ...children);
}

function focusAct(act, tries = 30) {
  if (document.querySelector('.sheet-host') && tries > 0) {
    setTimeout(() => focusAct(act, tries - 1), 50);
    return;
  }
  const target = document.querySelector(`[data-act="${act}"]`);
  const current = document.activeElement;
  if (target && (!current || current === document.body || !current.isConnected)) target.focus({ preventScroll: true });
}

const triggerAct = () => document.activeElement?.closest?.('[data-act]')?.dataset.act ?? null;

function valueSegment(items, value, onChange, label) {
  const el = h('div', { class: ['segmented', items.some((item) => item.icon) && 'segmented--icons'], role: 'radiogroup', 'aria-label': label });
  const buttons = items.map((item) => h('button', {
    class: 'segmented__btn',
    type: 'button',
    role: 'radio',
    'data-value': item.value,
    'aria-checked': String(item.value === value),
    tabindex: item.value === value ? '0' : '-1',
    onclick: () => choose(item.value),
  },
  item.icon ? icon(item.icon) : null,
  h('span', { class: 'segmented__label' }, item.label)));
  const choose = (next, focus) => {
    const current = buttons.find((btn) => btn.getAttribute('aria-checked') === 'true');
    if (current?.dataset.value === String(next)) return;
    haptic('selection');
    for (const btn of buttons) {
      const on = btn.dataset.value === String(next);
      btn.setAttribute('aria-checked', String(on));
      btn.tabIndex = on ? 0 : -1;
      if (on && focus) btn.focus();
    }
    onChange?.(next);
  };
  el.addEventListener('keydown', (event) => {
    const step = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[event.key];
    if (!step) return;
    event.preventDefault();
    const i = buttons.findIndex((btn) => btn === document.activeElement);
    const next = buttons[(i + step + buttons.length) % buttons.length];
    choose(next.dataset.value, true);
  });
  el.append(...buttons);
  return el;
}

function meSheet(options) {
  const act = triggerAct();
  return openSheet({
    ...options,
    className: ['me-sheet', options.className].filter(Boolean).join(' '),
    onClose: () => {
      options.onClose?.();
      if (act) setTimeout(() => focusAct(act));
    },
  });
}

async function copyText(text) {
  try {
    await navigator.clipboard?.writeText(text);
  } catch {}
}

function breakable(text) {
  return text.split(/(?<=[/@])/).flatMap((part, i) => (i ? [h('wbr'), part] : [part]));
}

/* Preferences and module state */

function readLocal(key, fallback) {
  try {
    return localStorage.getItem(key) ?? fallback;
  } catch {
    return fallback;
  }
}

function writeLocal(key, value) {
  try {
    if (value == null) localStorage.removeItem(key);
    else localStorage.setItem(key, value);
  } catch {}
}

const appearance = (name) => readLocal(`pp.${name}`, 'default');

const THEME_LABELS = { system: 'System', light: 'Hell', dark: 'Dunkel' };

const CAL_MODES = ['view', 'sync'];

const calendar = {
  mode: CAL_MODES.includes(readLocal('pp.calendar', null)) ? readLocal('pp.calendar', null) : null,
  token: 'k7f2m9q4x1',
  password: 'q4Rt-8mZx-2kLw-Vn7e',
};
const DEFAULT_LEAD = 60;
const DEFAULT_ANSWER_DAYS = 3;
const ANSWER_DAYS = [1, 2, 3, 5, 7, 14];
const reminders = new Map();
const install = { prompt: null };

window.addEventListener('beforeinstallprompt', (event) => {
  event.preventDefault();
  install.prompt = event;
});
window.addEventListener('appinstalled', () => {
  install.prompt = null;
});

function me(role) {
  return users[role] ?? users.member;
}

function hasVoice(user) {
  return getSection(user.sectionId)?.kind === 'instrument';
}

function attends(user) {
  return user.canAttend !== false;
}

function reminderState(user) {
  if (!reminders.has(user.id)) {
    reminders.set(user.id, { minutes: user.pushReminderMinutes || DEFAULT_LEAD, days: user.answerReminderDays || DEFAULT_ANSWER_DAYS });
    if (user.answerReminderDays === undefined) user.answerReminderDays = DEFAULT_ANSWER_DAYS;
  }
  return reminders.get(user.id);
}

function remindersOn(user) {
  reminderState(user);
  return Boolean(user.pushReminderMinutes || user.answerReminderDays);
}

function sectionName(user) {
  return getSection(user.sectionId)?.name ?? user.sectionId;
}

function initialsOf(name) {
  return name.split(/\s+/).filter(Boolean).map((part) => part[0]).slice(0, 2).join('').toUpperCase();
}

function syncMember(user, patch) {
  Object.assign(user, patch);
  const member = getMember(user.id);
  if (member && member !== user) Object.assign(member, patch);
}

function isInstalled() {
  return matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
}

function detectPlatform() {
  const ua = navigator.userAgent;
  if (/iPhone|iPad|iPod/.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1)) return 'ios';
  if (/Android/.test(ua)) return 'android';
  return 'desktop';
}

/* Profil */

function identityHero(user) {
  return h('a', { class: 'me-hero', href: '#/profile/konto', 'aria-label': `${user.displayName}, ${user.email}, Konto` },
    ui.avatar(user, 48),
    h('span', { class: 'me-hero__body' },
      h('span', { class: 'me-hero__name' }, user.displayName),
      h('span', { class: 'me-hero__mail' }, breakable(user.email))),
    icon('chevron-right', { class: 'me-hero__chevron' }));
}

function demoGroup(ctx) {
  const control = ui.segmented(ROLES.map((role) => ({ value: role, label: users[role].label })), ctx.role, (role) => setRole(role));
  control.setAttribute('aria-label', 'Demo-Rolle');
  return h('div', { class: 'me-demo', role: 'group', 'aria-label': 'Demo' },
    h('span', { class: 'me-demo__tile', 'aria-hidden': 'true' }, icon('flask-conical')),
    control);
}

function calendarLabel() {
  return calendar.mode ? 'An' : 'Aus';
}

function notLinked() {
  toast('Im Prototyp nicht verlinkt');
}

function legalFoot() {
  return h('footer', { class: 'me-foot' },
    h('button', { class: 'me-foot__link', type: 'button', onclick: notLinked }, 'Hilfe'),
    h('a', { href: 'https://www.jmd.info/globals/datenschutz', target: '_blank', rel: 'noopener' }, 'Datenschutz'),
    h('a', { href: 'https://www.jmd.info/globals/impressum', target: '_blank', rel: 'noopener' }, 'Impressum'),
    h('span', {}, `Probenplaner ${VERSION}`));
}

function renderProfileDesk(root, ctx, focus) {
  const user = me(ctx.role);
  root.classList.add('me', 'me--desk');
  const bar = deskBar(root, {
    title: 'Profil',
    actions: [{ icon: 'log-out', label: 'Abmelden', onClick: logout }],
  });
  bar.lastElementChild.before(demoGroup(ctx));
  const place = (page, cls, el) => {
    el.classList.add(cls);
    el.dataset.page = page;
    return el;
  };
  const grid = h('div', { class: 'me-grid' },
    h('div', { class: 'me-grid__main' },
      place('konto', 'me-c-konto', kontoCard(ctx)),
      place('stimme', 'me-c-voice', stimmeCard(ctx)),
      place('kalender', 'me-c-cal', kalenderCard(ctx))),
    h('div', { class: 'me-grid__side' },
      attends(user) ? place('benachrichtigungen', 'me-c-remind', benachCard(ctx)) : null,
      place('darstellung', 'me-c-look', darstellungCard()),
      place('installieren', 'me-c-install', installCard(ctx))));
  root.append(grid, legalFoot());
  if (focus) {
    const target = grid.querySelector(`[data-page="${CSS.escape(focus)}"]`);
    requestAnimationFrame(() => {
      target?.scrollIntoView({ block: 'nearest' });
      target?.classList.add('is-flashed');
    });
  }
}

function renderProfile(root, params, ctx) {
  if (ui.isWide()) {
    renderProfileDesk(root, ctx);
    return;
  }
  const user = me(ctx.role);
  root.classList.add('me', 'me--root');
  root.append(
    demoGroup(ctx),
    identityHero(user),
    h('div', { class: 'list-group' },
      row({
        lead: tile(hasVoice(user) ? 'music' : 'shield', hasVoice(user) ? '--violet-500' : '--indigo-500'),
        title: hasVoice(user) ? 'Stimme & Rollen' : 'Rollen',
        value: hasVoice(user) ? sectionName(user) : roleNames(user),
        href: '/profile/stimme',
        cls: 'me-value-row',
      }),
      attends(user) ? row({ lead: tile('bell', '--amber-500'), title: 'Benachrichtigungen', value: remindersOn(user) ? 'An' : 'Aus', href: '/profile/benachrichtigungen', cls: 'me-value-row' }) : null,
      row({ lead: tile('calendar-sync', '--teal-500'), title: 'Kalender', value: calendarLabel(), href: '/profile/kalender', cls: 'me-value-row' })),
    h('div', { class: 'list-group' },
      row({ lead: tile('sun-moon', '--indigo-500'), title: 'Darstellung', value: THEME_LABELS[store.get('theme')] ?? 'System', href: '/profile/darstellung', cls: 'me-value-row' }),
      row({ lead: tile('smartphone', '--green-500'), title: 'App installieren', value: isInstalled() ? 'Installiert' : null, href: '/profile/installieren', cls: 'me-value-row' })),
    h('div', { class: 'list-group' },
      row({ lead: tile('circle-help', '--sky-500'), title: 'Hilfe', external: true, onClick: notLinked }),
      row({ lead: tile('shield-check', '--green-600'), title: 'Datenschutz', external: true, url: 'https://www.jmd.info/globals/datenschutz' }),
      row({ lead: tile('info', '--violet-600'), title: 'Impressum', external: true, url: 'https://www.jmd.info/globals/impressum' })),
    h('div', { class: 'list-group' },
      row({ title: 'Abmelden', action: true, cls: 'me-logout', onClick: logout })),
    h('p', { class: 'me-version' }, `Probenplaner ${VERSION}`));
}

function logout() {
  navigate('/login', { direction: 'none' });
}

registerScreen('profile', {
  title: 'Profil',
  tab: '/profile',
  render: renderProfile,
});

/* Konto */

function kontoCard(ctx) {
  const user = me(ctx.role);
  return card('Konto',
    h('div', { class: 'list-group' },
      row({ lead: ui.avatar(user, 32), title: user.displayName, onClick: () => openNameSheet(ctx), attrs: { 'aria-label': `Anzeigename: ${user.displayName}`, 'data-act': 'name' } }),
      row({ lead: tile('mail', '--teal-500'), title: user.email, onClick: () => openEmailSheet(ctx), attrs: { 'aria-label': `E-Mail: ${user.email}`, 'data-act': 'email' } }),
      row({ lead: tile('key-round', '--amber-500'), title: 'Passwort ändern', onClick: () => openPasswordSheet(user), attrs: { 'data-act': 'password' } })),
    h('div', { class: 'me-danger' },
      h('button', { class: 'button button--plain button--sm me-danger__btn', type: 'button', 'aria-haspopup': 'dialog', 'data-act': 'leave', onclick: confirmLeave }, `${ensemble.type ?? 'Ensemble'} verlassen`),
      h('button', { class: 'button button--plain button--sm me-danger__btn', type: 'button', 'aria-haspopup': 'dialog', 'data-act': 'delete', onclick: confirmDelete }, 'Konto löschen')));
}

function renderKonto(root, ctx) {
  root.append(kontoCard(ctx));
}

function fieldSheet({ title, type, value, autocomplete, inputmode, validate, error, onSave }) {
  const message = h('p', { class: 'section-footer is-error', hidden: true }, icon('circle-alert'), h('span', {}, error));
  const clear = h('button', {
    class: 'me-input__clear',
    type: 'button',
    'aria-label': 'Leeren',
    hidden: !value,
    onclick: () => {
      input.value = '';
      clear.hidden = true;
      message.hidden = true;
      input.removeAttribute('aria-invalid');
      input.focus();
    },
  }, icon('circle-x'));
  const input = h('input', {
    class: 'input',
    type,
    value,
    autocomplete,
    inputmode,
    enterkeyhint: 'done',
    'aria-label': title,
    oninput: () => {
      message.hidden = true;
      clear.hidden = !input.value;
      input.removeAttribute('aria-invalid');
    },
  });
  input.addEventListener('focus', () => input.select(), { once: true });
  const save = () => {
    const next = input.value.trim();
    if (!validate(next)) {
      message.hidden = false;
      input.setAttribute('aria-invalid', 'true');
      haptic('error');
      return false;
    }
    if (next !== value) onSave(next);
    return true;
  };
  const sheet = meSheet({
    title,
    content: h('form', {
      class: 'me-form',
      novalidate: true,
      onsubmit: (event) => {
        event.preventDefault();
        if (save()) sheet.close();
      },
    }, h('div', { class: 'me-input' }, input, clear), message),
    actions: [{ label: 'Speichern', variant: 'primary', onClick: save }],
  });
  requestAnimationFrame(() => {
    input.focus();
    input.select();
  });
  return sheet;
}

function openNameSheet(ctx) {
  const user = me(ctx.role);
  fieldSheet({
    title: 'Anzeigename',
    type: 'text',
    value: user.displayName,
    autocomplete: 'name',
    error: 'Name fehlt, bitte einen Namen eingeben',
    validate: (next) => next.length > 0,
    onSave: (next) => {
      syncMember(user, { displayName: next, initials: initialsOf(next) });
      ctx.rerender();
    },
  });
}

function openEmailSheet(ctx) {
  const user = me(ctx.role);
  fieldSheet({
    title: 'E-Mail',
    type: 'email',
    value: user.email,
    autocomplete: 'email',
    inputmode: 'email',
    error: 'Keine gültige E-Mail-Adresse, bitte so eingeben: name@beispiel.de',
    validate: (next) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(next),
    onSave: (next) => {
      syncMember(user, { email: next });
      ctx.rerender();
      toast(html`${icon('circle-check')} E-Mail geändert`);
    },
  });
}

let fieldCount = 0;

function openPasswordSheet(user) {
  const clear = (f) => {
    f.error.hidden = true;
    f.input.removeAttribute('aria-invalid');
  };
  const field = (label, autocomplete) => {
    const id = `meField${++fieldCount}`;
    const f = {};
    f.error = h('p', { class: 'section-footer is-error', id: `${id}Error`, hidden: true });
    f.input = h('input', { class: 'input', id, type: 'password', autocomplete, 'aria-describedby': `${id}Error`, oninput: () => clear(f) });
    f.el = h('div', { class: 'me-field' }, h('label', { class: 'me-field__label', for: id }, label), f.input, f.error);
    return f;
  };
  const fields = [
    field('Aktuelles Passwort', 'current-password'),
    field('Neues Passwort', 'new-password'),
    field('Neues Passwort wiederholen', 'new-password'),
  ];
  const inputs = () => fields.map((f) => f.input);
  const reveal = h('button', {
    class: 'button button--plain me-reveal',
    type: 'button',
    'aria-pressed': 'false',
    onclick: () => {
      const shown = reveal.getAttribute('aria-pressed') !== 'true';
      reveal.setAttribute('aria-pressed', String(shown));
      inputs().forEach((input) => { input.type = shown ? 'text' : 'password'; });
      reveal.replaceChildren(h('span', { class: 'me-reveal__icon' }, icon(shown ? 'eye-off' : 'eye')), document.createTextNode(shown ? 'Verbergen' : 'Anzeigen'));
      refreshIcons(reveal);
    },
  }, icon('eye'), 'Anzeigen');
  const content = h('form', { class: 'me-form', onsubmit: (event) => event.preventDefault() },
    h('input', { type: 'text', autocomplete: 'username', value: user.email, hidden: true, readonly: true, tabindex: '-1' }),
    fields.map((f) => f.el), reveal);
  meSheet({
    title: 'Passwort ändern',
    content,
    actions: [{
      label: 'Speichern',
      variant: 'primary',
      onClick: () => {
        const [current, next, repeat] = inputs().map((input) => input.value);
        const [index, message] = !current ? [0, 'Aktuelles Passwort fehlt']
          : next.length < 8 ? [1, 'Mindestens 8 Zeichen']
            : next !== repeat ? [2, 'Passwörter stimmen nicht überein'] : [-1, ''];
        fields.forEach(clear);
        if (message) {
          const f = fields[index];
          f.error.replaceChildren(h('span', {}, icon('circle-alert')), h('span', {}, message));
          f.error.hidden = false;
          f.input.setAttribute('aria-invalid', 'true');
          f.input.focus();
          refreshIcons(f.error);
          haptic('error');
          return false;
        }
        toast(html`${icon('circle-check')} Passwort geändert`);
        return true;
      },
    }],
  });
}

async function confirmLeave() {
  const confirmed = await ui.openConfirmSheet({
    title: `${ensemble.shortName} verlassen?`,
    text: 'Antworten in diesem Ensemble werden entfernt.',
    action: 'Verlassen',
    destructive: true,
    icon: 'log-out',
  });
  if (confirmed) navigate('/ensembles', { direction: 'none' });
  else focusAct('leave');
}

const DELETE_WORD = 'LÖSCHEN';

function confirmDelete() {
  const typed = () => input.value.trim().toLocaleUpperCase('de') === DELETE_WORD;
  const remove = () => {
    if (!typed()) return false;
    haptic('warning');
    toast('Im Prototyp nicht möglich');
    return true;
  };
  const input = h('input', {
    class: 'input me-delete__input',
    id: 'meDeleteWord',
    type: 'text',
    autocomplete: 'off',
    autocapitalize: 'characters',
    spellcheck: 'false',
    enterkeyhint: 'done',
    autofocus: true,
    oninput: () => { button.disabled = !typed(); },
  });
  const sheet = meSheet({
    title: 'Konto löschen?',
    className: 'sheet--confirm sheet--destructive me-delete',
    content: h('form', {
      class: 'me-form',
      novalidate: true,
      onsubmit: (event) => {
        event.preventDefault();
        if (remove()) sheet.close();
      },
    },
    h('p', { class: 'sheet__lead' }, 'Antworten und Notizen werden entfernt.'),
    h('div', { class: 'me-field' },
      h('label', { class: 'me-field__label', for: 'meDeleteWord' }, `${DELETE_WORD} eingeben`),
      input)),
    actions: [
      { label: 'Konto löschen', icon: 'trash-2', variant: 'destructive-solid', onClick: remove },
      { label: 'Abbrechen', variant: 'secondary' },
    ],
  });
  const button = sheet.el.querySelector('.sheet__actions .button');
  button.disabled = true;
}

/* Stimme & Rollen */

function rolesRows(user, label = 'Rollen') {
  const owns = (role) => Boolean(user.roleIds?.includes(role.id));
  const fixed = (user.roleIds ?? []).map(getRole).filter((role) => role && !role.isDefault && !isSelfAssignable(role));
  const chips = ui.roleChips(fixed, { names: true, max: fixed.length });
  const pickable = [...roles].filter(isSelfAssignable).sort((a, b) => a.sortOrder - b.sortOrder);
  const toggle = (role, on) => {
    const ids = new Set(user.roleIds ?? []);
    if (on) ids.add(role.id);
    else ids.delete(role.id);
    role.memberCount = Math.max(0, (role.memberCount ?? 0) + (on ? 1 : -1));
    const roleIds = [...ids].sort((a, b) => a - b);
    syncMember(user, {
      roleIds,
      roles: roleIds.map(getRole).filter((r) => r && !r.isDefault).map((r) => r.name),
    });
  };
  return [
    chips ? valueRow({ lead: icon('shield'), label, value: chips, className: 'me-roles-row' }) : null,
    ...pickable.map((role) => switchRow({
      lead: ui.roleChip(role),
      title: role.name,
      checked: owns(role),
      onChange: (on) => toggle(role, on),
    })),
  ];
}

function roleNames(user) {
  return (user.roleIds ?? []).map(getRole).filter((role) => role && !role.isDefault).sort((a, b) => a.sortOrder - b.sortOrder).map((role) => role.name).join(', ') || 'Mitglied';
}

function stimmeCard(ctx) {
  const user = me(ctx.role);
  const title = hasVoice(user) ? 'Stimme & Rollen' : 'Rollen';
  const head = ui.sectionHeader(title, { className: 'jmd-head me-voice-head' });
  if (user.permissions?.includes('can_manage_permissions')) {
    head.append(h('a', { class: 'me-head-link', href: '#/settings/roles' }, h('span', {}, 'Rollen verwalten'), icon('chevron-right')));
  }
  const manage = user.permissions?.includes('can_manage_permissions') && !ui.isWide()
    ? h('div', { class: 'list-group' }, row({ lead: icon('shield'), title: 'Rollen verwalten', href: '/settings/roles', cls: 'me-manage-row' }))
    : null;
  return h('section', { class: 'set-card' }, head,
    h('div', { class: 'list-group' },
      hasVoice(user) ? valueRow({
        lead: icon('music'),
        label: 'Stimme',
        value: voicePath(user.sectionId),
        onClick: () => openVoiceSheet(ctx),
        locked: ownVoiceLock(user),
        act: 'voice',
      }) : null,
      rolesRows(user, hasVoice(user) ? 'Rollen' : ensemble.shortName)),
    manage);
}

function renderStimme(root, ctx) {
  root.append(stimmeCard(ctx));
}

function openVoiceSheet(ctx) {
  const user = me(ctx.role);
  if (ownVoiceLock(user)) return;
  pickVoice({
    value: user.sectionId,
    onSelect: (sectionId) => {
      const previous = user.sectionId;
      syncMember(user, { sectionId });
      ctx.rerender();
      focusAct('voice');
      toast(html`${icon('music')} Stimme geändert`, {
        action: {
          label: 'Rückgängig',
          onClick: () => {
            syncMember(user, { sectionId: previous });
            ctx.rerender();
            focusAct('voice');
          },
        },
      });
    },
  });
}

/* Benachrichtigungen */

const LEAD_MINUTES = [30, 60, 90, 120, 180, 360, 1440];

function leadLabel(minutes) {
  if (minutes < 60) return `${minutes} Minuten`;
  if (minutes === 1440) return '1 Tag';
  const hours = minutes / 60;
  return hours === 1 ? '1 Stunde' : `${String(hours).replace('.', ',')} Stunden`;
}

function reminderRow({ title, checked, field, unit, onToggle }) {
  const input = h('input', {
    class: 'switch',
    type: 'checkbox',
    role: 'switch',
    checked,
    'aria-label': title,
    onchange: () => {
      haptic('light');
      field.disabled = !input.checked;
      onToggle(input.checked);
    },
  });
  field.disabled = !checked;
  return h('div', { class: 'me-remind' },
    h('span', { class: 'me-remind__title' }, title),
    h('span', { class: 'me-remind__lead' }, field, unit),
    input);
}

function benachCard(ctx) {
  const user = me(ctx.role);
  const state = reminderState(user);
  const leadChoices = [...new Set([...LEAD_MINUTES, state.minutes])].sort((a, b) => a - b);
  const time = menuSelect({
    value: state.minutes,
    label: 'Vorlauf der Erinnerung vor der Probe',
    options: leadChoices.map((n) => ({ value: n, label: leadLabel(n) })),
    format: (o) => leadLabel(o.value),
    onChange: (value) => {
      state.minutes = value;
      syncMember(user, { pushReminderMinutes: state.minutes });
    },
  });
  const dayLabel = (n) => `${n} ${n === 1 ? 'Tag' : 'Tage'}`;
  const days = menuSelect({
    value: state.days,
    label: 'Vorlauf der Erinnerung zum Rückmelden',
    options: ANSWER_DAYS.map((n) => ({ value: n, label: dayLabel(n) })),
    format: (o) => dayLabel(o.value),
    onChange: (value) => {
      state.days = value;
      syncMember(user, { answerReminderDays: state.days });
    },
  });
  return card('Benachrichtigungen',
    h('div', { class: 'list-group me-reminders' },
      reminderRow({
        title: 'Erinnerung zum Rückmelden',
        checked: Boolean(user.answerReminderDays),
        field: days,
        unit: h('span', { class: 'me-remind__unit' }, 'vorher'),
        onToggle: (on) => syncMember(user, { answerReminderDays: on ? state.days : null }),
      }),
      reminderRow({
        title: 'Erinnerung vor der Probe',
        checked: Boolean(user.pushReminderMinutes),
        field: time,
        unit: h('span', { class: 'me-remind__unit' }, 'vorher'),
        onToggle: (on) => syncMember(user, { pushReminderMinutes: on ? state.minutes : null }),
      })));
}

function renderBenachrichtigungen(root, ctx) {
  root.append(benachCard(ctx));
}

/* Kalender */

const CAL_HOST = 'probenplaner.example';

function calendarUrls(user) {
  return {
    webcal: `webcal://${CAL_HOST}/ical/${calendar.token}`,
    https: `https://${CAL_HOST}/ical/${calendar.token}`,
    server: `https://${CAL_HOST}/caldav/`,
    username: `${user.id}@${CAL_HOST}`,
    password: calendar.password,
  };
}

function copyRow({ lead, title, text }) {
  const status = h('span', { class: 'visually-hidden', 'aria-live': 'polite' });
  const glyph = h('span', { class: 'me-copy-row__icon' }, icon('copy'));
  const el = h('button', {
    class: 'list-row list-row--2line me-copy-row',
    type: 'button',
    'aria-label': `${title} kopieren`,
    onclick: async () => {
      haptic('light');
      await copyText(text);
      el.classList.add('is-copied');
      glyph.innerHTML = icon('check');
      status.textContent = 'Kopiert';
      refreshIcons(glyph);
      clearTimeout(el.timer);
      el.timer = setTimeout(() => {
        el.classList.remove('is-copied');
        glyph.innerHTML = icon('copy');
        status.textContent = '';
        refreshIcons(glyph);
      }, 1600);
    },
  },
  lead ? h('span', { class: 'list-row__lead' }, lead) : null,
  h('span', { class: 'list-row__body' },
    h('span', { class: 'list-row__title' }, title),
    h('span', { class: 'list-row__sub me-mono' }, breakable(text.replace(/^https?:\/\//, '')))),
  h('span', { class: 'list-row__trail' }, glyph, status));
  return el;
}

function newSecret(parts, length) {
  return Array.from({ length: parts }, () => Math.random().toString(36).slice(2, 2 + length)).join('-');
}

function revokeCalendar() {
  calendar.token = newSecret(1, 10);
  calendar.password = newSecret(4, 4);
}

const CAL_APPS = {
  view: [
    ['Apple Kalender', 'Im Kalender öffnen'],
    ['Google Kalender', 'Weitere Kalender › Per URL'],
    ['Outlook', 'Kalender hinzufügen › Aus dem Internet'],
    ['Thunderbird', 'Neuer Kalender › Im Netzwerk'],
  ],
  sync: [
    ['Apple Kalender', 'Einstellungen › Kalender › Accounts › CalDAV'],
    ['Android', 'DAVx⁵', 'https://www.davx5.com/'],
    ['Outlook', 'CalDav Synchronizer', 'https://caldavsynchronizer.org/'],
    ['Thunderbird', 'Neuer Kalender › Im Netzwerk › CalDAV'],
  ],
};

function calOptions(user) {
  const answers = attends(user);
  return [
    {
      mode: 'view',
      title: 'Nur ansehen',
      action: 'Abonnieren',
      apps: ['Apple', 'Google', 'Outlook', 'Thunderbird', 'Android'],
      pros: ['Mit jeder Kalender-App', 'Mit einem Klick eingerichtet'],
      cons: [answers ? 'Zu- und Absagen nur im Probenplaner' : 'Nur Lesen', 'Google übernimmt Änderungen bis zu 24 Stunden später'],
    },
    {
      mode: 'sync',
      title: answers ? 'Mit Zu- und Absagen' : 'Als Kalender-Konto',
      action: 'Einrichten',
      apps: ['Apple', 'Thunderbird', 'Android mit DAVx⁵', 'Outlook mit Plugin'],
      pros: [answers ? 'Zu- und Absagen direkt im Kalender' : 'Alle deine Orchester', 'Änderungen kommen sofort an'],
      cons: ['Zugangsdaten einmal eintragen', 'Android und Outlook brauchen eine Zusatz-App'],
    },
  ];
}

function calFact(kind, text) {
  return h('li', { class: `me-cal-fact me-cal-fact--${kind}` }, icon(kind === 'pro' ? 'check' : 'minus'), h('span', {}, text));
}

function calOption(option, choose) {
  return h('div', { class: 'me-option' },
    h('h3', { class: 'me-option__title' }, option.title),
    h('ul', { class: 'me-cal-facts' },
      option.pros.map((text) => calFact('pro', text)),
      option.cons.map((text) => calFact('con', text))),
    h('ul', { class: 'me-cal-compat' }, option.apps.map((app) => h('li', {}, app))),
    h('button', { class: 'button button--secondary button--block me-option__action', type: 'button', 'data-act': `cal-${option.mode}`, onclick: () => choose(option.mode) }, option.action));
}

function calAppRow([name, text, url]) {
  return h('div', { class: 'me-cal-app' },
    h('span', { class: 'me-cal-app__name' }, name),
    url
      ? h('a', { class: 'me-cal-app__how', href: url, target: '_blank', rel: 'noopener' }, text, icon('arrow-up-right'))
      : h('span', { class: 'me-cal-app__how' }, text));
}

function kalenderCard(ctx) {
  const user = me(ctx.role);
  const body = h('div', { class: 'me-cal' });

  const choose = (mode) => {
    haptic('selection');
    calendar.mode = mode;
    paint();
    body.querySelector('[data-act="cal-end"]')?.focus({ preventScroll: true });
  };

  const endSubscription = async () => {
    const confirmed = await ui.openConfirmSheet({
      title: 'Abo beenden?',
      text: 'Link und Zugangsdaten werden ungültig.',
      action: 'Abo beenden',
      destructive: true,
      icon: 'calendar-x',
    });
    if (!confirmed) {
      focusAct('cal-end');
      return;
    }
    revokeCalendar();
    calendar.mode = null;
    paint();
    focusAct('cal-view');
    toast(html`${icon('calendar-x')} Abo beendet`);
  };

  const active = () => {
    const urls = calendarUrls(user);
    const option = calOptions(user).find((o) => o.mode === calendar.mode);
    return [
      h('div', { class: 'me-cal__head' },
        icon('circle-check', { class: 'me-cal__check' }),
        h('h3', { class: 'me-option__title' }, option.title),
        h('button', { class: 'button button--plain button--sm me-danger__btn', type: 'button', 'aria-haspopup': 'dialog', 'data-act': 'cal-end', onclick: endSubscription }, 'Abo beenden')),
      h('div', { class: 'me-cal__setup' },
        calendar.mode === 'view'
          ? h('div', { class: 'list-group' },
            copyRow({ lead: tile('link'), title: 'Link', text: urls.https }),
            h('a', { class: 'list-row list-row--action', href: urls.webcal, onclick: () => haptic('light') },
              h('span', { class: 'list-row__lead' }, icon('calendar-plus')),
              h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Im Kalender öffnen'))))
          : h('div', { class: 'list-group' },
            copyRow({ lead: tile('server'), title: 'Server', text: urls.server }),
            copyRow({ lead: tile('at-sign'), title: 'Benutzername', text: urls.username }),
            copyRow({ lead: tile('key-round'), title: 'Kalender-Passwort', text: urls.password })),
        h('div', { class: 'me-cal-apps' }, CAL_APPS[calendar.mode].map(calAppRow))),
    ];
  };

  const choices = () => [h('div', { class: 'me-options' }, calOptions(user).map((option) => calOption(option, choose)))];

  const paint = () => {
    writeLocal('pp.calendar', calendar.mode);
    body.replaceChildren(...(calendar.mode ? active() : choices()));
    refreshIcons(body);
  };

  paint();
  return card('Kalender-Abo', body);
}

function renderKalender(root, ctx) {
  root.append(kalenderCard(ctx));
}

/* Darstellung */

function darstellungCard() {
  const theme = valueSegment([
    { value: 'system', label: 'System', icon: 'monitor-smartphone' },
    { value: 'light', label: 'Hell', icon: 'sun' },
    { value: 'dark', label: 'Dunkel', icon: 'moon' },
  ], store.get('theme'), (value) => setTheme(value), 'Modus');
  return card('Darstellung',
    theme,
    h('div', { class: 'list-group' },
      switchRow({
        lead: tile('rows-4'),
        title: 'Kompakte Darstellung',
        checked: appearance('density') === 'compact',
        onChange: (on) => ui.setAppearance('density', on ? 'compact' : 'default'),
      }),
      switchRow({
        lead: tile('move'),
        title: 'Bewegung reduzieren',
        checked: appearance('motion') === 'reduced',
        onChange: (on) => ui.setAppearance('motion', on ? 'reduced' : 'default'),
      }),
      switchRow({
        lead: tile('layers'),
        title: 'Transparenz reduzieren',
        checked: appearance('transparency') === 'reduced',
        onChange: (on) => ui.setAppearance('transparency', on ? 'reduced' : 'default'),
      })));
}

function renderDarstellung(root) {
  root.append(darstellungCard());
}

/* App installieren */

const QR_ECC_M = [null, [10, 1], [16, 1], [26, 1], [18, 2], [24, 2], [16, 4], [18, 4], [22, 4], [22, 5], [26, 5]];

const QR_MASKS = [
  (r, c) => (r + c) % 2 === 0,
  (r) => r % 2 === 0,
  (r, c) => c % 3 === 0,
  (r, c) => (r + c) % 3 === 0,
  (r, c) => (Math.floor(r / 2) + Math.floor(c / 3)) % 2 === 0,
  (r, c) => ((r * c) % 2) + ((r * c) % 3) === 0,
  (r, c) => (((r * c) % 2) + ((r * c) % 3)) % 2 === 0,
  (r, c) => (((r + c) % 2) + ((r * c) % 3)) % 2 === 0,
];

function gfMul(x, y) {
  let z = 0;
  for (let i = 7; i >= 0; i -= 1) {
    z = (z << 1) ^ ((z >>> 7) * 0x11d);
    z ^= ((y >>> i) & 1) * x;
  }
  return z;
}

function rsDivisor(degree) {
  const poly = Array(degree).fill(0);
  poly[degree - 1] = 1;
  let root = 1;
  for (let i = 0; i < degree; i += 1) {
    for (let j = 0; j < degree; j += 1) {
      poly[j] = gfMul(poly[j], root);
      if (j + 1 < degree) poly[j] ^= poly[j + 1];
    }
    root = gfMul(root, 2);
  }
  return poly;
}

function rsRemainder(data, divisor) {
  const out = divisor.map(() => 0);
  for (const byte of data) {
    const factor = byte ^ out.shift();
    out.push(0);
    divisor.forEach((coef, i) => { out[i] ^= gfMul(coef, factor); });
  }
  return out;
}

function qrRawModules(ver) {
  let n = (16 * ver + 128) * ver + 64;
  if (ver >= 2) {
    const count = Math.floor(ver / 7) + 2;
    n -= (25 * count - 10) * count - 55;
    if (ver >= 7) n -= 36;
  }
  return n;
}

function qrAlignment(ver, size) {
  if (ver === 1) return [];
  const count = Math.floor(ver / 7) + 2;
  const step = Math.ceil((ver * 4 + 4) / (count * 2 - 2)) * 2;
  const out = [6];
  for (let pos = size - 7; out.length < count; pos -= step) out.splice(1, 0, pos);
  return out;
}

function qrMatrix(text) {
  const bytes = [...new TextEncoder().encode(text)];
  let ver = 1;
  while (ver <= 10) {
    const [ecc, blocks] = QR_ECC_M[ver];
    if (4 + (ver < 10 ? 8 : 16) + bytes.length * 8 <= (Math.floor(qrRawModules(ver) / 8) - ecc * blocks) * 8) break;
    ver += 1;
  }
  if (ver > 10) return null;
  const [eccLen, blockCount] = QR_ECC_M[ver];
  const rawCodewords = Math.floor(qrRawModules(ver) / 8);
  const dataLen = rawCodewords - eccLen * blockCount;

  const bits = [];
  const push = (value, length) => {
    for (let i = length - 1; i >= 0; i -= 1) bits.push((value >>> i) & 1);
  };
  push(4, 4);
  push(bytes.length, ver < 10 ? 8 : 16);
  bytes.forEach((b) => push(b, 8));
  push(0, Math.min(4, dataLen * 8 - bits.length));
  push(0, (8 - (bits.length % 8)) % 8);
  const data = [];
  for (let i = 0; i < bits.length; i += 8) data.push(bits.slice(i, i + 8).reduce((acc, b) => (acc << 1) | b, 0));
  for (let pad = 0xec; data.length < dataLen; pad ^= 0xec ^ 0x11) data.push(pad);

  const shortBlocks = blockCount - (rawCodewords % blockCount);
  const shortLen = Math.floor(rawCodewords / blockCount);
  const divisor = rsDivisor(eccLen);
  const blocks = [];
  for (let i = 0, k = 0; i < blockCount; i += 1) {
    const chunk = data.slice(k, k + shortLen - eccLen + (i < shortBlocks ? 0 : 1));
    k += chunk.length;
    const ecc = rsRemainder(chunk, divisor);
    if (i < shortBlocks) chunk.push(null);
    blocks.push(chunk.concat(ecc));
  }
  const codewords = [];
  for (let i = 0; i < blocks[0].length; i += 1) {
    for (const block of blocks) if (block[i] !== null) codewords.push(block[i]);
  }

  const size = ver * 4 + 17;
  const grid = Array.from({ length: size }, () => Array(size).fill(false));
  const fixed = Array.from({ length: size }, () => Array(size).fill(false));
  const set = (r, c, dark) => {
    if (r < 0 || c < 0 || r >= size || c >= size) return;
    grid[r][c] = dark;
    fixed[r][c] = true;
  };
  for (let i = 0; i < size; i += 1) {
    set(6, i, i % 2 === 0);
    set(i, 6, i % 2 === 0);
  }
  for (const [r0, c0] of [[3, 3], [3, size - 4], [size - 4, 3]]) {
    for (let dr = -4; dr <= 4; dr += 1) {
      for (let dc = -4; dc <= 4; dc += 1) {
        const d = Math.max(Math.abs(dr), Math.abs(dc));
        set(r0 + dr, c0 + dc, d !== 2 && d !== 4);
      }
    }
  }
  const align = qrAlignment(ver, size);
  const last = align.length - 1;
  align.forEach((r, i) => align.forEach((c, j) => {
    if ((i === 0 && j === 0) || (i === 0 && j === last) || (i === last && j === 0)) return;
    for (let dr = -2; dr <= 2; dr += 1) {
      for (let dc = -2; dc <= 2; dc += 1) set(r + dr, c + dc, Math.max(Math.abs(dr), Math.abs(dc)) !== 1);
    }
  }));
  const drawFormat = (mask) => {
    let rem = mask;
    for (let i = 0; i < 10; i += 1) rem = (rem << 1) ^ ((rem >>> 9) * 0x537);
    const format = ((mask << 10) | rem) ^ 0x5412;
    const bit = (i) => ((format >>> i) & 1) === 1;
    for (let i = 0; i <= 5; i += 1) set(i, 8, bit(i));
    set(7, 8, bit(6));
    set(8, 8, bit(7));
    set(8, 7, bit(8));
    for (let i = 9; i < 15; i += 1) set(8, 14 - i, bit(i));
    for (let i = 0; i < 8; i += 1) set(8, size - 1 - i, bit(i));
    for (let i = 8; i < 15; i += 1) set(size - 15 + i, 8, bit(i));
    set(size - 8, 8, true);
  };
  drawFormat(0);
  if (ver >= 7) {
    let rem = ver;
    for (let i = 0; i < 12; i += 1) rem = (rem << 1) ^ ((rem >>> 11) * 0x1f25);
    const info = (ver << 12) | rem;
    for (let i = 0; i < 18; i += 1) {
      const dark = ((info >>> i) & 1) === 1;
      const a = size - 11 + (i % 3);
      const b = Math.floor(i / 3);
      set(b, a, dark);
      set(a, b, dark);
    }
  }

  let n = 0;
  for (let right = size - 1; right >= 1; right -= 2) {
    if (right === 6) right = 5;
    const upward = ((right + 1) & 2) === 0;
    for (let vert = 0; vert < size; vert += 1) {
      const r = upward ? size - 1 - vert : vert;
      for (let j = 0; j < 2; j += 1) {
        const c = right - j;
        if (!fixed[r][c] && n < codewords.length * 8) {
          grid[r][c] = ((codewords[n >>> 3] >>> (7 - (n & 7))) & 1) === 1;
          n += 1;
        }
      }
    }
  }

  const applyMask = (m) => {
    for (let r = 0; r < size; r += 1) {
      for (let c = 0; c < size; c += 1) if (!fixed[r][c] && QR_MASKS[m](r, c)) grid[r][c] = !grid[r][c];
    }
  };
  const penalty = () => {
    let score = 0;
    for (let i = 0; i < size; i += 1) {
      for (const line of [grid[i], grid.map((cells) => cells[i])]) {
        let run = 1;
        for (let k = 1; k <= size; k += 1) {
          if (k < size && line[k] === line[k - 1]) {
            run += 1;
          } else {
            if (run >= 5) score += run - 2;
            run = 1;
          }
        }
        const s = line.map((d) => (d ? '1' : '0')).join('');
        for (const pattern of ['10111010000', '00001011101']) {
          for (let k = s.indexOf(pattern); k !== -1; k = s.indexOf(pattern, k + 1)) score += 40;
        }
      }
    }
    for (let r = 0; r < size - 1; r += 1) {
      for (let c = 0; c < size - 1; c += 1) {
        const d = grid[r][c];
        if (d === grid[r][c + 1] && d === grid[r + 1][c] && d === grid[r + 1][c + 1]) score += 3;
      }
    }
    const dark = grid.flat().filter(Boolean).length;
    return score + Math.floor(Math.abs((dark * 20) / (size * size) - 10)) * 10;
  };
  let best = 0;
  let bestScore = Infinity;
  for (let m = 0; m < 8; m += 1) {
    applyMask(m);
    drawFormat(m);
    const score = penalty();
    if (score < bestScore) {
      best = m;
      bestScore = score;
    }
    applyMask(m);
  }
  applyMask(best);
  drawFormat(best);
  return grid;
}

function qrSvg(text) {
  const grid = qrMatrix(text);
  if (!grid) return '';
  const quiet = 4;
  const total = grid.length + quiet * 2;
  const px = total * Math.max(3, Math.floor(200 / total));
  let path = '';
  grid.forEach((cells, r) => cells.forEach((dark, c) => {
    if (dark) path += `M${c + quiet} ${r + quiet}h1v1h-1z`;
  }));
  return `<svg viewBox="0 0 ${total} ${total}" width="${px}" height="${px}" shape-rendering="crispEdges" aria-hidden="true"><path fill="currentColor" d="${path}"/></svg>`;
}

function appUrl() {
  return `${location.origin}${location.pathname}`;
}

function guide(steps) {
  return h('ol', { class: 'me-guide' }, steps.map((s, i) => h('li', { class: 'me-guide__step' },
    h('span', { class: 'me-guide__num', 'aria-hidden': 'true' }, String(i + 1)),
    h('span', { class: 'me-guide__label' }, s.label),
    s.mock ? h('span', { class: 'me-guide__shot', 'aria-hidden': 'true' }, s.mock) : null,
    s.extra ? h('span', { class: 'me-guide__extra' }, s.extra) : null)));
}

const hi = (on) => (on ? 'me-mock__hi' : null);

function mockToolbar() {
  return h('span', { class: 'me-mock me-mock--toolbar' },
    ['chevron-left', 'chevron-right', 'share', 'book-open', 'copy'].map((name) => h('span', { class: ['me-mock__icon', hi(name === 'share')] }, icon(name))));
}

function mockMenu(items) {
  return h('span', { class: 'me-mock me-mock--menu' },
    items.map(([glyph, text, on]) => h('span', { class: ['me-mock__row', hi(on)] }, h('span', {}, text), icon(glyph))));
}

function mockSheetHead() {
  return h('span', { class: 'me-mock me-mock--head' },
    h('span', { class: 'me-mock__muted' }, 'Abbrechen'),
    h('span', { class: 'me-mock__text me-mock__hi' }, 'Hinzufügen'));
}

function mockAddressBar() {
  return h('span', { class: 'me-mock me-mock--address' },
    h('span', { class: 'me-mock__field' }, icon('lock'), h('span', {}, location.host || 'probenplaner.de')),
    h('span', { class: 'me-mock__tabs' }, '1'),
    h('span', { class: 'me-mock__icon me-mock__hi' }, icon('ellipsis-vertical')));
}

const INSTALL_PLATFORMS = [
  { value: 'ios', label: 'iPhone' },
  { value: 'android', label: 'Android' },
];

function installButton(ctx, label) {
  return h('button', {
    class: 'button button--primary button--lg button--block me-install__cta',
    type: 'button',
    onclick: async () => {
      const prompt = install.prompt;
      if (!prompt) return;
      haptic('light');
      prompt.prompt();
      const choice = await prompt.userChoice.catch(() => null);
      install.prompt = null;
      if (choice?.outcome === 'accepted') toast(html`${icon('circle-check')} Installiert`);
      ctx.rerender();
    },
  }, icon('download'), label);
}

function mobileInstall(platform, ctx) {
  if (platform === 'ios') {
    return [guide([
      { label: 'Teilen', mock: mockToolbar() },
      { label: 'Zum Home-Bildschirm', mock: mockMenu([['copy', 'Kopieren'], ['square-plus', 'Zum Home-Bildschirm', true]]) },
      { label: 'Hinzufügen', mock: mockSheetHead() },
    ])];
  }
  if (install.prompt) return [installButton(ctx, 'Jetzt installieren')];
  return [guide([
    { label: 'Menü', mock: mockAddressBar() },
    { label: 'App installieren', mock: mockMenu([['plus', 'Neuer Tab'], ['download', 'App installieren', true]]) },
  ])];
}

function copyLinkButton(url) {
  const copyButton = h('button', {
    class: 'button button--secondary',
    type: 'button',
    onclick: async () => {
      haptic('light');
      await copyText(url);
      copyButton.replaceChildren(h('span', { class: 'me-reveal__icon' }, icon('check')), document.createTextNode('Kopiert'));
      refreshIcons(copyButton);
      setTimeout(() => {
        if (!copyButton.isConnected) return;
        copyButton.replaceChildren(h('span', { class: 'me-reveal__icon' }, icon('copy')), document.createTextNode('Link kopieren'));
        refreshIcons(copyButton);
      }, 1600);
    },
  }, icon('copy'), 'Link kopieren');
  return copyButton;
}

function installContent(platform, ctx) {
  if (platform !== 'desktop') {
    const body = h('div', { class: 'me-install-body' });
    const show = (value) => {
      body.replaceChildren(...mobileInstall(value, ctx));
      refreshIcons(body);
    };
    const control = valueSegment(INSTALL_PLATFORMS, platform, show, 'Gerät');
    show(platform);
    return [control, body];
  }
  const url = appUrl();
  return [
    h('div', { class: 'me-handoff' },
      h('div', { class: 'me-qr', role: 'img', 'aria-label': `QR-Code: ${url}` }, raw(qrSvg(url))),
      h('div', { class: 'me-handoff__body' },
        h('h3', { class: 'me-handoff__title' }, 'Auf dem Smartphone öffnen'),
        h('p', { class: 'me-handoff__url me-mono' }, url.replace(/^https?:\/\//, '')),
        copyLinkButton(url))),
    install.prompt ? installButton(ctx, 'Auf diesem Computer installieren') : null,
  ];
}

function installCard(ctx) {
  if (isInstalled()) {
    return card('App installieren', h('div', { class: 'list-group' },
      row({ lead: tile('circle-check'), title: 'Installiert' })));
  }
  return card('App installieren', ...installContent(detectPlatform(), ctx).filter(Boolean));
}

function renderInstallieren(root, ctx) {
  root.append(installCard(ctx));
}

/* Pages */

const PAGES = {
  konto: { title: 'Konto', render: renderKonto },
  stimme: { title: () => (hasVoice(me(store.get('role'))) ? 'Stimme & Rollen' : 'Rollen'), render: renderStimme },
  benachrichtigungen: { title: 'Benachrichtigungen', render: renderBenachrichtigungen, allowed: attends },
  kalender: { title: 'Kalender', render: renderKalender },
  darstellung: { title: 'Darstellung', render: renderDarstellung },
  installieren: { title: 'App installieren', render: renderInstallieren },
};

registerScreen('profile-page', {
  title: (params) => {
    const title = PAGES[params.page]?.title;
    return (typeof title === 'function' ? title() : title) ?? 'Profil';
  },
  tab: '/profile',
  render(root, params, ctx) {
    const page = PAGES[params.page];
    if (ui.isWide() && page) {
      renderProfileDesk(root, ctx, params.page);
      return;
    }
    root.classList.add('me', 'me--page', `me--${params.page}`);
    if (!page) {
      root.append(ui.emptyState({ icon: 'circle-help', title: 'Nicht gefunden', action: { label: 'Profil', href: '#/profile' } }));
      return;
    }
    if (page.allowed && !page.allowed(me(ctx.role))) {
      navigate('/profile', { replace: true, direction: 'none' });
      return;
    }
    page.render(root, ctx);
  },
});

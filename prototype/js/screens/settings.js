/* Verwaltung (Orchester tab): manage list, Einstellungen, Registerstruktur, Rollen & Rechte */

import { registerScreen, navigate, openSheet, setHeader, toast, haptic, h, html, icon, refreshIcons, rerender, SIDEBAR, TABS } from '../app.js';
import {
  applySectionTree, colors, compressGroups, createSection, defaultBesetzung, effectiveColor, ensemble, getSection, locations, members,
  resetSectionDefaults, roles, sectionColors, sections, users, BASE_PERMISSIONS, grantsRights, rightsOf,
} from '../data.js';
import * as ui from '../ui.js';
import { listPlaces, usageOf, usageLabel, openPlaceSheet } from '../places.js';
import { renderTeamHeader, TEAM_SEGMENTS, deskBar } from './team.js';
import { valueRow as valueLine } from '../value-row.js';
import { openInvite, resetInvite } from './members.js';

const settings = ensemble.settings;

const ENSEMBLE_TYPES = ['Orchester', 'Chor', 'Ensemble', 'Band'];
const CUSTOM_TYPE = '__custom';
const SEASONS = ['2025/26', '2026/27', '2027/28'];

const SECTION_SCOPE = ['Keine', 'Stimme', 'Register', 'Alle'];

const PERMISSION_GROUPS = [
  {
    title: 'Proben',
    items: [['can_view_schedule', 'Proben sehen'], ['can_attend_rehearsals', 'An Proben teilnehmen'], ['can_manage_rehearsals', 'Proben planen']],
  },
  {
    title: 'Rückmeldungen sehen',
    scope: SECTION_SCOPE,
    keys: ['can_view_own_section_stats', 'can_view_parent_section_stats', 'can_view_all_section_stats'],
  },
  {
    title: 'Anwesenheit erfassen',
    scope: SECTION_SCOPE,
    keys: ['can_manage_attendance_own_section', 'can_manage_attendance_parent_section', 'can_manage_attendance_all'],
  },
  {
    title: 'Mitglieder',
    scope: ['Keine', 'Sehen', 'Verwalten'],
    keys: ['can_view_members', 'can_manage_members'],
    child: ['can_manage_permissions', 'Rollen & Rechte'],
  },
  {
    title: 'Datenschutz',
    items: [['can_view_member_email', 'E-Mail-Adressen sehen']],
  },
  {
    title: 'Verwaltung',
    items: [['can_manage_ensemble', () => `${ui.ensembleLabel()} verwalten`]],
  },
];

const ICON_CHOICES = [
  ['crown', 'Krone'], ['star', 'Stern'], ['briefcase', 'Aktentasche'], ['file-music', 'Noten'],
  ['wand-sparkles', 'Zauberstab'], ['piano', 'Klavier'], ['mic', 'Mikrofon'], ['megaphone', 'Megafon'],
  ['key', 'Schlüssel'], ['wrench', 'Werkzeug'], ['truck', 'Transporter'], ['coffee', 'Kaffee'],
  ['heart', 'Herz'], ['shield', 'Schild'], ['book-open', 'Buch'], ['tag', 'Etikett'],
];
const ROLE_COLORS = colors.filter((c) => !['#e5e7eb', '#6b7280'].includes(c.value));
const COLOR_PREFERENCE = ['#8b5cf6', '#f97316', '#ef4444', '#14b8a6', '#ec4899', '#10b981'];

const MANAGE_SECTIONS = [
  { value: '/settings', label: 'Einstellungen', icon: 'settings', perm: 'can_manage_ensemble' },
  { value: '/settings/sections', label: 'Registerstruktur', icon: 'network', perm: 'can_manage_ensemble' },
  { value: '/settings/places', label: 'Orte', icon: 'map-pin', perm: 'can_manage_ensemble' },
  { value: '/settings/roles', label: 'Rollen & Rechte', icon: 'shield', perm: 'can_manage_permissions' },
];

function can(persona, perm) {
  const ids = users[persona]?.roleIds ?? [];
  return roles.some((role) => ids.includes(role.id) && role.permissions.includes(perm));
}

function sectionsFor(persona) {
  return MANAGE_SECTIONS.filter((section) => can(persona, section.perm));
}

function syncList(list, match, allowed, item, at = list.length) {
  const index = list.findIndex(match);
  if (allowed && index === -1) list.splice(at, 0, item);
  if (!allowed && index !== -1) list.splice(index, 1);
}

function syncManageNav() {
  for (const persona of Object.keys(SIDEBAR)) {
    const allowed = sectionsFor(persona).length > 0;
    syncList(SIDEBAR[persona], (item) => item.route === '/manage', allowed, { route: '/manage', icon: 'settings', label: 'Verwaltung', group: '/team', also: ['/settings'] });
    TEAM_SEGMENTS[persona] ??= [];
    syncList(TEAM_SEGMENTS[persona], (item) => item.value === '/manage', allowed, { value: '/manage', label: 'Verwaltung' });
    const hasTeam = TEAM_SEGMENTS[persona].length > 0;
    syncList(TABS[persona], (tab) => tab.route === '/team', hasTeam, { route: '/team', icon: 'users-round', label: ui.ensembleLabel() }, TABS[persona].length - 1);
  }
}

function freeColor(used) {
  return COLOR_PREFERENCE.find((value) => !used.has(value)) ?? ROLE_COLORS[0].value;
}

function freeIcon(used) {
  return ICON_CHOICES.find(([name]) => !used.has(name))?.[0] ?? 'tag';
}

/* Builders */

function tile(name, tone) {
  return h('span', { class: 'icon-tile', style: tone ? `--tile:${ui.TONES[tone] ?? tone}` : null, 'aria-hidden': 'true' }, icon(name));
}

function chevron() {
  return icon('chevron-right', { class: 'list-row__chevron' });
}

function lock() {
  return icon('lock', { class: 'vw-lock' });
}

function tap(fn) {
  return (event) => {
    haptic('light');
    fn(event);
  };
}

function navRow({ icon: iconName, tone, title, value, href, onClick }) {
  return h(href ? 'a' : 'button', {
    class: 'list-row',
    href: href ? `#${href}` : null,
    type: href ? null : 'button',
    'aria-haspopup': href ? null : 'dialog',
    onclick: onClick ? tap(onClick) : null,
  },
  h('span', { class: 'list-row__lead' }, tile(iconName, tone)),
  h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, title)),
  h('span', { class: 'list-row__trail' },
    value != null ? h('span', { class: 'list-row__value' }, value) : null,
    chevron()));
}

function valueRow({ title, value, onClick, className }) {
  return valueLine({ label: title, value, onClick, className: ['vw-value-row', className].filter(Boolean).join(' ') });
}

function switchRow({ title, sub, checked, disabled = false, onChange }) {
  const input = h('input', {
    class: 'switch',
    type: 'checkbox',
    role: 'switch',
    checked: Boolean(checked),
    disabled,
    onchange: () => {
      haptic('light');
      onChange?.(input.checked);
    },
  });
  const lockValue = h('span', { class: 'vw-locked__value' });
  const lockButton = h('button', { class: 'vw-locked', type: 'button', hidden: true }, lockValue, icon('lock'));
  const row = h('label', { class: ['list-row', 'list-row--switch', 'vw-plain-row', sub && 'list-row--2line'] },
    h('span', { class: 'list-row__body' },
      h('span', { class: 'list-row__title' }, title),
      sub ? h('span', { class: 'list-row__sub' }, sub) : null),
    h('span', { class: 'list-row__trail' }, input, lockButton));
  row.input = input;
  row.setLocked = (message) => {
    const on = Boolean(message);
    row.classList.toggle('is-locked', on);
    lockButton.hidden = !on;
    input.hidden = on;
    lockValue.textContent = input.checked ? 'An' : 'Aus';
    lockButton.setAttribute('aria-label', on ? `${title}: ${lockValue.textContent}, gesperrt. ${message}` : '');
    lockButton.onclick = on ? (event) => {
      event.preventDefault();
      event.stopPropagation();
      haptic('warning');
      toast(html`${icon('lock')} ${message}`);
    } : null;
  };
  return row;
}

function lockedValue(label, message) {
  return h('button', {
    class: 'vw-locked',
    type: 'button',
    'aria-label': `${label}, gesperrt. ${message}`,
    onclick: () => {
      haptic('warning');
      toast(html`${icon('lock')} ${message}`);
    },
  }, h('span', { class: 'vw-locked__value' }, label), icon('lock'));
}

function addRow(label, onClick) {
  return h('button', {
    class: ['list-row', 'list-row--action', 'vw-add'],
    type: 'button',
    'aria-haspopup': 'dialog',
    onclick: tap(onClick),
  },
  h('span', { class: 'list-row__lead' }, icon('plus')),
  h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, label)));
}

function memberCount(n) {
  return h('span', { class: 'vw-count', 'aria-hidden': 'true' }, String(n));
}

function sectionTile(node, small = false) {
  return h('span', {
    class: ['icon-tile', 'vw-tile', small && 'icon-tile--sm'],
    style: `--tile:${node.color}`,
    'aria-hidden': 'true',
  }, node.emoji);
}

function nameField({ value, maxLength, accept = () => true, onCommit }) {
  let current = value;
  const input = h('input', {
    class: 'vw-title-input',
    type: 'text',
    value,
    maxlength: String(maxLength),
    autocomplete: 'off',
    enterkeyhint: 'done',
    spellcheck: 'false',
    'aria-label': 'Name',
  });
  const commit = () => {
    const next = input.value.trim();
    input.removeAttribute('aria-invalid');
    if (!next || !accept(next)) {
      input.value = current;
      return;
    }
    input.value = next;
    if (next === current) return;
    current = next;
    onCommit(next);
  };
  input.addEventListener('input', () => {
    const next = input.value.trim();
    input.toggleAttribute('aria-invalid', !next || !accept(next));
  });
  input.addEventListener('change', commit);
  input.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    input.blur();
  });
  return { input, commit };
}

function titleField(sheet, options) {
  const { input, commit } = nameField(options);
  sheet.el.querySelector('.sheet__title').replaceChildren(input);
  return commit;
}

const MANAGE_TONES = {
  '/settings': 'blue',
  '/settings/sections': 'teal',
  '/settings/places': 'amber',
  '/settings/roles': 'indigo',
};

const HEAD_ICONS = {
  Antworten: ['circle-check', 'green'],
  Funktionen: ['sparkles', 'violet'],
};

function card(title, ...children) {
  const [iconName, tone] = ui.isWide() ? [] : title === ui.ensembleLabel() ? ['users-round', 'blue'] : HEAD_ICONS[title] ?? [];
  return h('section', { class: 'set-card' }, ui.sectionHeader(title, { className: 'jmd-head', icon: iconName, tone }), ...children);
}

function plural(n, one, many) {
  return `${n} ${n === 1 ? one : many}`;
}

function focusField(field) {
  requestAnimationFrame(() => field.focus({ preventScroll: true }));
}

function sheetButton(sheet, index = 0) {
  return sheet.el.querySelectorAll('.sheet__actions .button')[index];
}

function textSheet({ title, value = '', placeholder, maxLength = 40, action = 'Speichern', onSave }) {
  const input = h('input', {
    class: 'input',
    type: 'text',
    value,
    placeholder: placeholder ?? null,
    maxlength: String(maxLength),
    autocomplete: 'off',
    enterkeyhint: 'done',
    'aria-label': placeholder ?? title,
  });
  const valid = () => {
    const next = input.value.trim();
    return Boolean(next) && next !== value;
  };
  const sheet = openSheet({
    title,
    className: 'vw-sheet',
    content: h('div', { class: 'vw-field' }, input),
    actions: [{
      label: action,
      variant: 'primary',
      onClick: () => {
        if (!valid()) return false;
        haptic('success');
        onSave(input.value.trim());
      },
    }],
  });
  const button = sheetButton(sheet);
  const sync = () => {
    button.disabled = !valid();
  };
  input.addEventListener('input', sync);
  input.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' || !valid()) return;
    event.preventDefault();
    button.click();
  });
  sync();
  focusField(input);
  return sheet;
}

function guard(ctx, perm = 'can_manage_ensemble') {
  return can(ctx.role, perm) || ui.guard(ctx, []);
}

const wideRoles = matchMedia('(min-width: 1200px)');
let lastPane = null;

function paneButton({ icon: iconName, label, onClick, primary = false, pressed = null }) {
  return h('button', {
    class: ['button', 'button--sm', primary ? 'button--primary' : 'button--tinted', 'vw-pane__btn'],
    type: 'button',
    'aria-pressed': pressed == null ? null : String(pressed),
    onclick: tap(onClick),
  }, icon(iconName), h('span', { class: 'button__label' }, label));
}

function manageNav(ctx, active) {
  return h('nav', { class: 'vw-nav', 'aria-label': 'Verwaltung' },
    sectionsFor(ctx.role).map((section) => h('a', {
      class: ['vw-nav__item', section.value === active && 'is-active'],
      href: `#${section.value}`,
      'data-replace': section.value === '/settings/roles' ? null : '',
      'aria-current': section.value === active ? 'page' : null,
    },
    tile(section.icon, MANAGE_TONES[section.value]),
    h('span', { class: 'vw-nav__label' }, section.label))));
}

function managePane(root, ctx, active, actions = []) {
  renderTeamHeader(ctx, '/manage', { root });
  root.classList.add('vw-desk');
  const section = MANAGE_SECTIONS.find((item) => item.value === active);
  const pane = h('div', { class: ['vw-pane', lastPane && lastPane !== active && 'is-entering'] },
    h('header', { class: 'vw-pane__head' },
      h('h2', { class: 'vw-pane__title' }, section?.label ?? ''),
      h('div', { class: 'vw-pane__actions' }, actions)));
  lastPane = active;
  root.append(h('div', { class: 'vw-split' }, manageNav(ctx, active), pane));
  return pane;
}

function mobileBack() {
  if (!ui.isWide()) setHeader({ backTo: '/manage' });
}

/* Einladungslink */

function shortLink(link) {
  return link.replace(/^https?:\/\//, '');
}

function inviteActions(sync) {
  const sharing = ui.linkSharing({ title: ensemble.shortName, url: () => ensemble.inviteLink });
  return {
    share: sharing.share,
    reset: async () => {
      if (await resetInvite()) sync();
    },
  };
}

function inviteCard() {
  const urlText = h('span', { class: 'vw-invite-card__url' }, shortLink(ensemble.inviteLink));
  const { share, reset } = inviteActions(() => {
    urlText.textContent = shortLink(ensemble.inviteLink);
  });
  return card('Einladungslink',
    h('div', { class: 'vw-invite-card' },
      urlText,
      h('div', { class: 'vw-invite-card__actions' },
        h('button', { class: 'button button--primary', type: 'button', onclick: tap(share) }, icon('share-2'), 'Teilen'),
        h('button', { class: 'button button--plain', type: 'button', 'aria-haspopup': 'dialog', onclick: tap(reset) }, icon('refresh-cw'), 'Zurücksetzen'))));
}

/* Verwaltung segment */

function renderManage(root, params, ctx) {
  const sections = sectionsFor(ctx.role);
  if (!sections.length) {
    ui.guard(ctx, []);
    return;
  }
  if (ui.isWide()) {
    navigate(sections[0].value, { replace: true, direction: 'none' });
    return;
  }
  renderTeamHeader(ctx, '/manage', { root });
  root.classList.add('vw', 'vw-manage', 'screen--wide');
  root.append(
    h('div', { class: 'list-group' },
      sections.map((section) => navRow({ icon: section.icon, tone: MANAGE_TONES[section.value], title: section.label, href: section.value }))),
    can(ctx.role, 'can_manage_members')
      ? h('div', { class: 'list-group' }, navRow({ icon: 'link', tone: 'violet', title: 'Einladungslink', onClick: openInvite }))
      : null,
  );
}

registerScreen('manage', {
  title: () => ui.ensembleLabel(),
  tab: '/team',
  render: renderManage,
});

/* Einstellungen */

function setEnsembleType(type) {
  ensemble.type = type;
  for (const tabs of Object.values(TABS)) {
    for (const tab of tabs) {
      if (tab.route === '/team') tab.label = type;
    }
  }
}

async function openDeleteEnsemble() {
  const type = ui.ensembleLabel();
  const ok = await ui.openConfirmSheet({
    title: `${ensemble.shortName} löschen?`,
    action: `${type} löschen`,
    icon: 'trash-2',
    destructive: true,
  });
  if (ok) toast(html`${icon('lock')} Im Prototyp nicht möglich`);
}

function openTypePicker(refresh) {
  const pick = (type) => {
    setEnsembleType(type);
    refresh();
  };
  const known = ENSEMBLE_TYPES.includes(ensemble.type) ? ENSEMBLE_TYPES : [...ENSEMBLE_TYPES, ensemble.type];
  ui.openPicker({
    title: 'Art',
    options: [...known, { value: CUSTOM_TYPE, label: 'Eigene Art…' }],
    value: ensemble.type,
    onSelect: (type) => {
      if (type === CUSTOM_TYPE) {
        setTimeout(() => ui.openPromptSheet({ title: 'Eigene Art', placeholder: 'Name', maxlength: 24, confirm: 'Übernehmen', onConfirm: pick }), 300);
        return;
      }
      pick(type);
    },
  });
}

function renderSettings(root, params, ctx) {
  if (!guard(ctx)) return;
  const wide = ui.isWide();
  const host = wide ? managePane(root, ctx, '/settings') : root;
  mobileBack();
  root.classList.add('vw', 'vw-settings', 'screen--wide');
  const refresh = () => ctx.rerender();
  const toggle = (key, title) => switchRow({
    title,
    checked: settings[key],
    onChange: (on) => {
      settings[key] = on;
    },
  });

  const season = ensemble.season.replace(/^Saison\s+/, '');

  host.append(h('div', { class: 'vw-cols' },
    card(ui.ensembleLabel(), h('div', { class: 'list-group' },
      valueRow({
        title: 'Name',
        value: ensemble.name,
        onClick: () => textSheet({
          title: 'Name',
          value: ensemble.name,
          maxLength: 80,
          onSave: (value) => {
            ensemble.name = value;
            refresh();
          },
        }),
      }),
      valueRow({
        title: 'Kurzname',
        value: ensemble.shortName,
        onClick: () => textSheet({
          title: 'Kurzname',
          value: ensemble.shortName,
          maxLength: 20,
          onSave: (value) => {
            ensemble.shortName = value;
            refresh();
          },
        }),
      }),
      valueRow({
        title: 'Art',
        value: ensemble.type,
        onClick: () => openTypePicker(refresh),
      }),
      valueRow({
        title: 'Standardort',
        value: ensemble.defaultLocation,
        onClick: () => ui.openPicker({
          title: 'Standardort',
          options: locations.map((l) => ({ value: l.name, label: l.name, sub: l.address ?? undefined })),
          value: ensemble.defaultLocation,
          onSelect: (name) => {
            ensemble.defaultLocation = name;
            refresh();
          },
        }),
      }),
      valueRow({
        title: 'Standardbesetzung',
        value: ensemble.defaultScope,
        onClick: () => ui.openBesetzungSheet({
          title: 'Standardbesetzung',
          value: defaultBesetzung().instrumentIds,
          onDone: (ids) => {
            ensemble.defaultGroups = compressGroups(ids);
            ensemble.defaultScope = ui.groupsSentence(ids);
            refresh();
          },
        }),
      }),
      valueRow({
        title: 'Saison',
        value: season,
        onClick: () => ui.openPicker({
          title: 'Saison',
          options: SEASONS,
          value: season,
          onSelect: (value) => {
            ensemble.season = `Saison ${value}`;
            refresh();
          },
        }),
      })),
      wide ? h('div', { class: 'me-danger' },
        h('button', { class: 'button button--plain button--sm me-danger__btn', type: 'button', 'aria-haspopup': 'dialog', onclick: tap(openDeleteEnsemble) }, `${ui.ensembleLabel()} löschen`)) : null),
    card('Antworten', h('div', { class: 'list-group' },
      toggle('forceDeclineReason', 'Grund bei Absage verlangen'),
      toggle('allowAttendanceReset', 'Antwort zurücknehmen erlauben'),
      toggle('allowPastEdit', 'Antworten zu vergangenen Proben änderbar'))),
    card('Funktionen', h('div', { class: 'list-group' },
      toggle('showRehearsalInsights', 'Statistiken'),
      toggle('allowRehearsalImport', 'Plan importieren'))),
    wide && can(ctx.role, 'can_manage_members') ? inviteCard() : null,
    wide ? null : h('div', { class: 'list-group vw-danger' },
      h('button', { class: 'list-row list-row--destructive', type: 'button', 'aria-haspopup': 'dialog', onclick: tap(openDeleteEnsemble) },
        h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, `${ui.ensembleLabel()} löschen`)))),
  ));
}

registerScreen('settings', {
  title: 'Einstellungen',
  tab: '/team',
  render: renderSettings,
});

/* Rollen & Rechte */

function has(role, key) {
  return role.permissions.includes(key);
}

function grant(role, keys) {
  role.permissions = [...new Set([...role.permissions, ...keys])];
  syncManageNav();
}

function revoke(role, keys) {
  role.permissions = role.permissions.filter((p) => !keys.includes(p));
  syncManageNav();
}

function scopeLevel(role, keys) {
  for (let i = keys.length - 1; i >= 0; i -= 1) {
    if (has(role, keys[i])) return i + 1;
  }
  return 0;
}

function setScope(role, keys, level) {
  revoke(role, keys);
  grant(role, keys.slice(0, level));
}

function normalizeRole(role) {
  for (const group of PERMISSION_GROUPS) {
    if (!group.scope) continue;
    setScope(role, group.keys, scopeLevel(role, group.keys));
    if (group.child && !has(role, group.keys[0])) revoke(role, [group.child[0]]);
  }
}

function roleRow(role, onOpen, selected = null) {
  const locked = Boolean(role.isSystem);
  const master = selected != null;
  return h('button', {
    class: ['list-row', 'vw-role-row', selected && 'is-selected'],
    type: 'button',
    style: `--role:${role.tagColor}`,
    'data-id': role.id,
    'aria-haspopup': master ? null : 'dialog',
    'aria-current': selected ? 'true' : null,
    'aria-label': `${role.name}, ${plural(role.memberCount, 'Mitglied', 'Mitglieder')}${locked ? ', gesperrt' : ''}`,
    onclick: tap(() => onOpen(role)),
  },
  h('span', { class: 'list-row__lead' }, ui.roleChips([role], { all: true })),
  h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, role.name)),
  h('span', { class: 'list-row__trail' },
    memberCount(role.memberCount),
    locked ? lock() : master ? null : chevron()));
}

const SELF_LOCKED = 'Selbst wählbar zuerst ausschalten';
const RIGHTS_LOCKED = 'Selbst wählbar sind nur Rollen ohne weitere Rechte';
const LAST_DEFAULT = 'Eine Standardrolle muss bleiben';

function guardClicks(el, isLocked, message) {
  el.addEventListener('click', (event) => {
    if (!isLocked()) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    haptic('warning');
    toast(html`${icon('lock')} ${message()}`);
  }, true);
}

function permissionBlock(role, group, { readonly = false, readonlyText = '', locked = () => false, onChange } = {}) {
  const lockers = [];
  const toggle = ([key, label]) => {
    const row = switchRow({
      title: typeof label === 'function' ? label() : label,
      checked: has(role, key),
      disabled: readonly,
      onChange: (on) => {
        if (on) grant(role, [key]);
        else revoke(role, [key]);
        onChange?.();
      },
    });
    if (readonly) {
      row.setLocked(readonlyText);
      guardClicks(row, () => true, () => readonlyText);
    } else if (editorLacks([key])) {
      row.setLocked(OWN_RIGHTS);
      guardClicks(row, () => true, () => OWN_RIGHTS);
    } else if (!BASE_PERMISSIONS.includes(key)) {
      guardClicks(row, locked, () => SELF_LOCKED);
      lockers.push(() => row.setLocked(locked() ? SELF_LOCKED : null));
    }
    return row;
  };
  let block;
  if (group.scope) {
    const child = group.child ? toggle(group.child) : null;
    const childGroup = child ? h('div', { class: 'list-group' }, child) : null;
    const childLock = () => (locked() ? SELF_LOCKED : scopeLevel(role, group.keys) === 0 ? `Zuerst ${group.title} sehen erlauben` : null);
    const lockChild = (off) => {
      child.input.disabled = readonly || off;
      if (!readonly) child.setLocked(childLock());
    };
    if (child && !readonly) {
      guardClicks(child, () => Boolean(childLock()), childLock);
      lockers.push(() => child.setLocked(childLock()));
    }
    if (child) lockChild(scopeLevel(role, group.keys) === 0);
    const control = ui.segmented(
      group.scope.map((label, value) => ({ value, label })),
      scopeLevel(role, group.keys),
      (level) => {
        setScope(role, group.keys, level);
        if (child) {
          lockChild(level === 0);
          if (level === 0) {
            revoke(role, [group.child[0]]);
            child.input.checked = false;
          }
        }
        onChange?.();
      },
    );
    control.setAttribute('aria-label', group.title);
    const lockHost = h('span', { class: 'vw-scope__lock', hidden: true });
    const scopeGroup = h('div', { class: 'list-group vw-scope' }, control);
    const showLock = (message) => {
      scopeGroup.hidden = Boolean(message);
      lockHost.hidden = !message;
      lockHost.replaceChildren(...(message ? [lockedValue(group.scope[scopeLevel(role, group.keys)], message)] : []));
      refreshIcons(lockHost);
    };
    block = card(group.title, scopeGroup, childGroup);
    block.querySelector('.section-header').append(lockHost);
    if (readonly) showLock(readonlyText);
    else if (editorLacks(group.keys)) showLock(OWN_RIGHTS);
    else lockers.push(() => showLock(locked() ? SELF_LOCKED : null));
  } else {
    block = card(group.title, h('div', { class: 'list-group' }, group.items.map(toggle)));
  }
  block.syncLock = () => lockers.forEach((fn) => fn());
  block.syncLock();
  return block;
}

function nameTaken(name, except) {
  return roles.some((r) => r !== except && r.name.toLowerCase() === name.toLowerCase());
}

function nameInput(value, placeholder) {
  return h('input', {
    class: 'input',
    type: 'text',
    value: value ?? '',
    placeholder: placeholder ?? null,
    'aria-label': 'Name',
    maxlength: '30',
    autocomplete: 'off',
    enterkeyhint: 'done',
  });
}

let editor = null;
const OWN_RIGHTS = 'Vergeben kannst du nur Rechte, die du selbst hast';

function editorLacks(keys) {
  const own = editor ? rightsOf(editor) : [];
  return keys.some((key) => !BASE_PERMISSIONS.includes(key) && !own.includes(key));
}

function roleBlocks(role, { host, paintMark, repaint, onDefault }) {
  const readonly = Boolean(role.isSystem);
  const view = readonly ? { ...role, permissions: [...role.permissions] } : role;
  normalizeRole(view);
  const parts = { flags: null, look: [], rights: [] };
  const selfOn = () => !readonly && Boolean(role.isSelfAssignable);
  const rights = PERMISSION_GROUPS.map((group) => permissionBlock(view, group, {
    readonly,
    readonlyText: `Rechte der ${role.name} sind fest`,
    locked: selfOn,
    onChange: () => syncSelf(),
  }));
  let syncSelf = () => {};
  if (!readonly) {
    if (role.isSelfAssignable && grantsRights(role)) role.isSelfAssignable = false;
    const selfRow = switchRow({
      title: 'Selbst wählbar',
      checked: role.isSelfAssignable,
      onChange: (on) => {
        role.isSelfAssignable = on;
        rights.forEach((block) => block.syncLock());
        repaint(role.id);
      },
    });
    const selfBlocked = () => role.isDefault || grantsRights(role);
    const selfMessage = () => (role.isDefault ? 'Eine Standardrolle hat jedes Mitglied' : RIGHTS_LOCKED);
    syncSelf = () => selfRow.setLocked(selfBlocked() ? selfMessage() : null);
    guardClicks(selfRow, selfBlocked, selfMessage);
    syncSelf();
    const lastDefault = role.isDefault && roles.filter((r) => r.isDefault).length === 1;
    const defaultRow = switchRow({
      title: 'Standardrolle',
      checked: role.isDefault,
      onChange: (on) => {
        role.isDefault = on;
        if (on) role.isSelfAssignable = false;
        selfRow.input.checked = role.isSelfAssignable;
        syncSelf();
        rights.forEach((block) => block.syncLock());
        onDefault?.(on);
        repaint(role.id);
      },
    });
    if (lastDefault) {
      defaultRow.setLocked(LAST_DEFAULT);
      guardClicks(defaultRow, () => true, () => LAST_DEFAULT);
    }
    parts.flags = h('div', { class: 'list-group vw-role-flags' }, defaultRow, selfRow);
    parts.look = [
      card('Farbe', h('div', { class: 'list-group vw-swatches' }, swatchGrid(role.tagColor, (value) => {
        role.tagColor = value;
        host().style.setProperty('--role', value);
        paintMark();
        repaint(role.id);
      }))),
      card('Icon', h('div', { class: 'list-group vw-swatches' }, iconGrid(role.icon, (value) => {
        role.icon = value;
        paintMark();
        repaint(role.id);
      })))];
  }
  parts.rights = rights;
  return parts;
}

function openRoleSheet(role, repaint) {
  const readonly = Boolean(role.isSystem);
  let commitName = () => {};
  const mark = h('span', { class: 'vw-role-mark' });
  const paintMark = () => {
    mark.replaceChildren(ui.roleChip(role));
    refreshIcons(mark);
  };
  const body = document.createDocumentFragment();
  const parts = roleBlocks(role, {
    host: () => sheet.el,
    paintMark,
    repaint,
    onDefault: (on) => sheet.el.querySelector('.sheet__actions')?.toggleAttribute('hidden', on),
  });
  body.append(...parts.look, ...[parts.flags].filter(Boolean), ...parts.rights);
  const sheet = openSheet({
    title: role.name,
    className: ['vw-sheet', 'vw-role-sheet', 'sheet--large', readonly && 'is-readonly'].filter(Boolean).join(' '),
    content: body,
    actions: readonly ? [] : [{
      label: 'Rolle löschen',
      variant: 'plain',
      icon: 'trash-2',
      onClick: () => {
        deleteRole(role, repaint, sheet);
        return false;
      },
    }],
    onClose: () => commitName(),
  });
  sheet.el.style.setProperty('--role', role.tagColor);
  sheet.el.querySelector('.sheet__actions')?.toggleAttribute('hidden', role.isDefault);
  if (!readonly) {
    commitName = titleField(sheet, {
      value: role.name,
      maxLength: 30,
      accept: (name) => !nameTaken(name, role),
      onCommit: (name) => {
        role.name = name;
        paintMark();
        repaint(role.id);
      },
    });
  }
  paintMark();
  sheet.el.querySelector('.sheet__title').prepend(mark);
  return sheet;
}

async function deleteRole(role, repaint, parent) {
  const held = role.memberCount > 0;
  if (held) {
    const ok = await ui.openConfirmSheet({
      title: `${role.name} löschen?`,
      text: `${plural(role.memberCount, 'Mitglied verliert', 'Mitglieder verlieren')} diese Rolle.`,
      action: 'Rolle löschen',
      icon: 'trash-2',
      destructive: true,
      stack: true,
      onConfirm: () => parent?.close(),
    });
    if (!ok) return;
  } else {
    parent?.close();
  }
  const index = roles.indexOf(role);
  if (index === -1) return;
  roles.splice(index, 1);
  for (const member of members) {
    if (!member.roleIds.includes(role.id)) continue;
    member.roleIds = member.roleIds.filter((id) => id !== role.id);
    member.roles = (member.roles ?? []).filter((name) => name !== role.name);
  }
  repaint();
  toast(html`${icon('trash-2')} ${role.name} gelöscht`, held ? {} : {
    action: {
      label: 'Rückgängig',
      onClick: () => {
        roles.splice(index, 0, role);
        repaint(role.id);
      },
    },
  });
}

function radioKeys(grid) {
  const items = () => [...grid.querySelectorAll('[role="radio"]')];
  const rove = () => {
    const all = items();
    const checked = all.find((el) => el.getAttribute('aria-checked') === 'true') ?? all[0];
    for (const el of all) el.tabIndex = el === checked ? 0 : -1;
  };
  grid.addEventListener('keydown', (event) => {
    const step = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[event.key];
    const all = items();
    const index = all.indexOf(document.activeElement);
    if (index === -1) return;
    let next = null;
    if (step) next = all[(index + step + all.length) % all.length];
    else if (event.key === 'Home') next = all[0];
    else if (event.key === 'End') next = all.at(-1);
    if (!next) return;
    event.preventDefault();
    next.click();
    next.focus();
  });
  grid.addEventListener('click', () => requestAnimationFrame(rove));
  rove();
  return grid;
}

function iconGrid(value, onChange) {
  const grid = h('div', { class: 'swatch-grid icon-grid', role: 'radiogroup', 'aria-label': 'Icon' });
  const paint = (current) => {
    for (const btn of grid.children) btn.setAttribute('aria-checked', String(btn.dataset.value === current));
  };
  for (const [name, label] of ICON_CHOICES) {
    grid.append(h('button', {
      class: 'swatch swatch--icon',
      type: 'button',
      role: 'radio',
      'data-value': name,
      'aria-checked': String(name === value),
      onclick: () => {
        haptic('selection');
        paint(name);
        onChange(name);
      },
    },
    h('span', { class: 'swatch__color' }, icon(name)),
    h('span', { class: 'swatch__label' }, label)));
  }
  return radioKeys(grid);
}

function swatchGrid(value, onChange, palette = ROLE_COLORS) {
  const grid = h('div', { class: 'swatch-grid', role: 'radiogroup', 'aria-label': 'Farbe' });
  const paint = (current) => {
    for (const btn of grid.children) btn.setAttribute('aria-checked', String(btn.dataset.value === current));
  };
  for (const c of palette) {
    grid.append(h('button', {
      class: 'swatch',
      type: 'button',
      role: 'radio',
      'data-value': c.value,
      'aria-checked': String(c.value === value),
      style: `--swatch:${c.value}`,
      onclick: () => {
        haptic('selection');
        paint(c.value);
        onChange(c.value);
      },
    },
    h('span', { class: 'swatch__color' }, icon('check')),
    h('span', { class: 'swatch__label' }, c.name)));
  }
  grid.select = paint;
  return radioKeys(grid);
}

function openNewRole(repaint, onCreated) {
  let color = freeColor(new Set(roles.map((r) => r.tagColor.toLowerCase())));
  let iconName = freeIcon(new Set(roles.map((r) => r.icon)));
  const input = nameInput('', 'z. B. Kassenwart');
  input.id = 'vwNewRoleName';
  input.removeAttribute('aria-label');
  const valid = () => {
    const name = input.value.trim();
    return Boolean(name) && !nameTaken(name);
  };
  const sheet = openSheet({
    title: 'Neue Rolle',
    className: 'vw-sheet',
    content: h('div', { class: 'vw-field' },
      h('label', { class: 'vw-field__label', for: 'vwNewRoleName' }, 'Name'),
      input,
      ui.sectionHeader('Farbe'),
      h('div', { class: 'list-group vw-swatches' }, swatchGrid(color, (value) => {
        color = value;
        sheet.el.style.setProperty('--role', value);
      })),
      ui.sectionHeader('Icon'),
      h('div', { class: 'list-group vw-swatches' }, iconGrid(iconName, (value) => { iconName = value; }))),
    actions: [{
      label: 'Anlegen',
      variant: 'primary',
      icon: 'plus',
      onClick: () => {
        if (!valid()) return false;
        haptic('success');
        const role = {
          id: Math.max(...roles.map((r) => r.id)) + 1,
          name: input.value.trim(),
          tagColor: color,
          icon: iconName,
          isSystem: false,
          isDefault: false,
          isSelfAssignable: false,
          sortOrder: 50,
          permissions: ['can_attend_rehearsals', 'can_view_schedule'],
          memberCount: 0,
        };
        roles.push(role);
        repaint(role.id);
        onCreated?.(role);
      },
    }],
  });
  sheet.el.style.setProperty('--role', color);
  const button = sheetButton(sheet);
  const sync = () => {
    button.disabled = !valid();
  };
  input.addEventListener('input', sync);
  input.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' || !valid()) return;
    event.preventDefault();
    button.click();
  });
  sync();
  focusField(input);
}

let selectedRoleId = null;

function sortedRoles() {
  return [...roles].sort((a, b) => a.sortOrder - b.sortOrder || a.id - b.id);
}

function membersLink(role) {
  const n = role.memberCount;
  return h('a', {
    class: 'vw-role-head__count',
    href: `#/members?role=${role.id}`,
    'aria-label': `${plural(n, 'Mitglied', 'Mitglieder')} mit ${role.name} anzeigen`,
  },
  h('span', { class: 'vw-role-head__n' }, String(n)),
  h('span', {}, n === 1 ? 'Mitglied' : 'Mitglieder'),
  chevron());
}

function renderRolesDesk(root, ctx, pane) {
  const list = h('div', { class: 'list-group vw-role-list' });
  const panel = h('div', { class: 'vw-role-panel' });
  let shown = null;

  const paintPanel = (role) => {
    shown = role;
    const readonly = Boolean(role.isSystem);
    const mark = h('span', { class: 'vw-role-mark' });
    const paintMark = () => {
      mark.replaceChildren(ui.roleChip(role));
      refreshIcons(mark);
    };
    const remove = readonly ? null : h('button', {
      class: 'button button--plain button--sm vw-role-head__delete',
      type: 'button',
      'aria-haspopup': 'dialog',
      hidden: role.isDefault,
      onclick: tap(() => deleteRole(role, repaint)),
    }, icon('trash-2'), h('span', { class: 'button__label' }, 'Rolle löschen'));
    const name = readonly
      ? h('span', { class: 'vw-role-head__name' }, role.name)
      : nameField({
        value: role.name,
        maxLength: 30,
        accept: (next) => !nameTaken(next, role),
        onCommit: (next) => {
          role.name = next;
          paintMark();
          repaint();
        },
      }).input;
    const parts = roleBlocks(role, {
      host: () => panel,
      paintMark,
      repaint: () => repaint(),
      onDefault: (on) => {
        if (remove) remove.hidden = on;
      },
    });
    panel.style.setProperty('--role', role.tagColor);
    panel.classList.toggle('is-readonly', readonly);
    panel.classList.remove('is-entering');
    void panel.offsetWidth;
    panel.classList.add('is-entering');
    panel.replaceChildren(
      h('header', { class: 'vw-role-head' },
        mark,
        name,
        readonly ? h('span', { class: 'vw-role-head__lock', title: 'Systemrolle' }, lock()) : null,
        h('span', { class: 'vw-role-head__spacer' }),
        membersLink(role)),
      h('div', { class: 'vw-role-body' },
        h('div', { class: 'vw-role-rights' }, parts.rights),
        readonly ? null : h('div', { class: 'vw-role-side' }, parts.flags, parts.look, remove)));
    paintMark();
    refreshIcons(panel);
  };

  const select = (role) => {
    selectedRoleId = role.id;
    repaint();
  };

  function repaint() {
    const sorted = sortedRoles();
    const role = sorted.find((r) => r.id === selectedRoleId) ?? sorted.find((r) => !r.isSystem) ?? sorted[0];
    selectedRoleId = role.id;
    const focused = list.contains(document.activeElement) ? document.activeElement.dataset.id : null;
    list.replaceChildren(...sorted.map((r) => roleRow(r, select, r === role)));
    refreshIcons(list);
    if (focused) list.querySelector(`[data-id="${focused}"]`)?.focus({ preventScroll: true });
    if (role !== shown) paintPanel(role);
  }

  root.classList.add('vw-roles--desk');
  repaint();
  pane.append(h('div', { class: 'vw-roles-split' }, list, panel));
  return () => openNewRole(() => repaint(), select);
}

function renderRoles(root, params, ctx) {
  if (!guard(ctx, 'can_manage_permissions')) return;
  editor = users[ctx.role] ?? null;
  const wanted = Number(ctx.query?.role);
  if (wanted && roles.some((r) => r.id === wanted)) selectedRoleId = wanted;
  root.classList.add('vw', 'vw-roles', 'screen--wide');
  if (ui.isWide()) {
    let add = () => {};
    setHeader({ title: 'Rollen & Rechte', back: true, large: false });
    root.classList.add('vw-roles-page');
    deskBar(root, {
      title: 'Rollen & Rechte',
      showBack: true,
      backTo: '/manage',
      actions: [{ icon: 'plus', label: 'Neue Rolle', primary: true, onClick: () => add() }],
    });
    const pane = root;
    if (wideRoles.matches) {
      add = renderRolesDesk(root, ctx, pane);
      return;
    }
    const list = h('div', { class: 'list-group vw-role-list' });
    const open = (role) => openRoleSheet(role, repaint);
    function repaint(flashId) {
      list.replaceChildren(...sortedRoles().map((role) => roleRow(role, open)));
      refreshIcons(list);
      if (flashId != null) list.querySelector(`[data-id="${flashId}"]`)?.classList.add('is-flashed');
    }
    add = () => openNewRole(repaint, open);
    repaint();
    pane.append(list);
    return;
  }
  mobileBack();
  const list = h('div', { class: 'list-group' });
  const open = (role) => openRoleSheet(role, repaint);
  function repaint(flashId) {
    list.replaceChildren(...sortedRoles().map((role) => roleRow(role, open)));
    refreshIcons(list);
    if (flashId != null) list.querySelector(`[data-id="${flashId}"]`)?.classList.add('is-flashed');
  }
  repaint();
  root.append(
    list,
    h('div', { class: 'list-group' }, addRow('Neue Rolle', () => openNewRole(repaint, open))),
  );
  if (wanted) {
    const role = roles.find((r) => r.id === wanted);
    if (role) requestAnimationFrame(() => open(role));
  }
}

wideRoles.addEventListener('change', () => {
  if (document.querySelector('#appContent > [data-screen="settings-roles"]')) rerender();
});

registerScreen('settings-roles', {
  title: 'Rollen & Rechte',
  tab: '/team',
  render: renderRoles,
});

/* Orte */

function placeMeta(place, standard) {
  return [
    standard ? h('span', { class: 'orte-tag' }, 'Standardort') : null,
    h('span', { class: place.address ? 'orte-address' : 'orte-address is-empty' }, place.address ?? 'Keine Adresse'),
  ];
}

function placeRow(place, onOpen) {
  const used = usageOf(place.name);
  const standard = place.name === ensemble.defaultLocation;
  const label = [place.name, place.address, standard && 'Standardort', usageLabel(used)];
  return h('button', {
    class: ['list-row', 'list-row--2line', 'vw-plain-row', 'orte-row', standard && 'is-standard'],
    type: 'button',
    'data-id': place.name,
    'aria-haspopup': 'dialog',
    'aria-label': label.filter(Boolean).join(', '),
    onclick: tap(() => onOpen(place)),
  },
  h('span', { class: 'list-row__body' },
    h('span', { class: 'list-row__title orte-title' }, place.name),
    h('span', { class: 'list-row__sub orte-meta' }, placeMeta(place, standard))),
  h('span', { class: 'list-row__trail' },
    h('span', { class: 'orte-count', 'aria-hidden': 'true' },
      h('span', { class: 'orte-count__n' }, String(used)),
      used === 1 ? 'Probe' : 'Proben'),
    chevron()));
}

function renderPlaces(root, params, ctx) {
  if (!guard(ctx)) return;
  const wide = ui.isWide();
  const list = h('div', { class: wide ? 'orte-grid' : 'list-group' });
  const open = (place) => openPlaceSheet({ place, onSaved: repaint, onDeleted: () => repaint() });
  function repaint(saved) {
    list.replaceChildren(...listPlaces().map((place) => placeRow(place, open)));
    refreshIcons(list);
    if (saved) [...list.children].find((row) => row.dataset.id === saved.name)?.classList.add('is-flashed');
  }
  const add = () => openPlaceSheet({ onSaved: repaint });
  const host = wide ? managePane(root, ctx, '/settings/places', [paneButton({ icon: 'plus', label: 'Neuer Ort', onClick: add, primary: true })]) : root;
  mobileBack();
  root.classList.add('vw', 'vw-places', 'screen--wide');
  repaint();
  host.append(list);
  if (!wide) root.append(h('div', { class: 'list-group' }, addRow('Neuer Ort', add)));
}

registerScreen('settings-places', {
  title: 'Orte',
  tab: '/team',
  render: renderPlaces,
});

/* Registerstruktur */

let tree = null;
let uid = 0;
let editing = false;

function treeNode(record, children) {
  const node = { id: record.id, record, children };
  Object.defineProperties(node, {
    name: { enumerable: true, get: () => record.name, set: (value) => { record.name = value; } },
    emoji: { enumerable: true, get: () => record.emoji, set: (value) => { record.emoji = value; } },
    shortName: { enumerable: true, get: () => record.shortName, set: (value) => { record.shortName = value; } },
    colorOwn: { enumerable: true, get: () => record.colorOwn, set: (value) => { record.colorOwn = value; } },
    color: { enumerable: true, get: () => effectiveColor(record) },
  });
  return node;
}

function buildTree() {
  const build = (s) => treeNode(s, s.kind === 'section' ? s.childIds.map((id) => build(getSection(id))) : null);
  return sections.filter((s) => s.parentId === 'tutti').map(build);
}

function idsOf(node, out = new Set()) {
  out.add(node.id);
  node.children?.forEach((child) => idsOf(child, out));
  return out;
}

function isVoice(node) {
  return !node.children?.length;
}

function suggestShortName(name) {
  return name.trim().split(/\s+/).filter(Boolean).map((word) => {
    if (/^\d+$/.test(word)) return word;
    const rest = word.slice(1).match(/[b-df-hj-np-tv-zß]/i);
    return word[0].toUpperCase() + (rest ? rest[0].toLowerCase() : '');
  }).join(' ').slice(0, 8);
}

function membersOf(node) {
  const ids = idsOf(node);
  return members.filter((m) => ids.has(m.sectionId));
}

function countOf(node) {
  return membersOf(node).length;
}

function membersLabel(n) {
  return plural(n, 'Mitglied', 'Mitglieder');
}

function locate(id, list = tree, parent = null, level = 0) {
  for (const [index, node] of list.entries()) {
    if (node.id === id) return { list, index, node, parent, level };
    if (node.children) {
      const found = locate(id, node.children, node, level + 1);
      if (found) return found;
    }
  }
  return null;
}

function isGroup(node) {
  return Array.isArray(node.children);
}

function indentOf(level) {
  const base = editing ? 44 : 16;
  return level > 0 ? base + 42 + (level - 1) * 40 : base;
}

function moveNode(id, to) {
  const found = locate(id);
  if (!found) return false;
  const { list, index } = found;
  const target = Math.max(0, Math.min(list.length - 1, to));
  if (target === index) return false;
  const [node] = list.splice(index, 1);
  list.splice(target, 0, node);
  applySectionTree(tree);
  return true;
}

function kindLabel(node, level) {
  if (level === 0) return 'Register';
  return isGroup(node) ? 'Gruppe' : 'Stimme';
}

function moveTargets(node, level) {
  const out = [];
  const walk = (list, depth, path) => {
    for (const item of list) {
      if (!isGroup(item) || item === node) continue;
      if (isGroup(node) ? depth === 0 : true) {
        out.push({ value: item.id, label: item.name, sub: path || undefined, lead: () => sectionTile(item, true) });
      }
      if (!isGroup(node)) walk(item.children, depth + 1, item.name);
    }
  };
  if (level > 0) walk(tree, 0, '');
  return out;
}

function renderSections(root, params, ctx) {
  if (!guard(ctx)) return;
  tree ??= buildTree();
  editing = false;
  root.classList.add('vw', 'vw-sections', 'screen--wide');
  const host = h('div', { class: 'vw-tree' });
  const bar = h('div', { class: 'vw-tree__bar' });

  const toggleEditing = () => {
    editing = !editing;
    syncHeader();
    paint();
  };

  const syncHeader = () => {
    if (ui.isWide()) return;
    setHeader({
      backTo: '/manage',
      actions: [{ icon: 'rotate-ccw', label: 'Auf Standard zurücksetzen', onClick: resetDefaults }, {
        label: editing ? 'Fertig' : 'Bearbeiten',
        text: true,
        primary: editing,
        onClick: toggleEditing,
      }],
    });
  };

  const paint = ({ focus, flash } = {}) => {
    host.classList.toggle('is-editing', editing);
    if (ui.isWide()) {
      bar.replaceChildren(
        h('button', { class: 'button button--plain button--sm vw-pane__btn', type: 'button', onclick: tap(resetDefaults) }, icon('rotate-ccw'), h('span', { class: 'button__label' }, 'Auf Standard zurücksetzen')),
        paneButton({ icon: editing ? 'check' : 'pencil', label: editing ? 'Fertig' : 'Register bearbeiten', onClick: toggleEditing, primary: editing }));
      refreshIcons(bar);
    }
    host.replaceChildren(
      ...tree.map((reg) => nodeEl(reg, 0)),
      editing ? h('div', { class: 'list-group vw-tree__add' }, addRow('Register', openAddRegister)) : '',
    );
    refreshIcons(host);
    const rowOf = (id) => host.querySelector(`.vw-node[data-id="${CSS.escape(id)}"] > .vw-row`);
    if (flash) rowOf(flash)?.classList.add('is-flashed');
    if (focus) rowOf(focus)?.querySelector('.vw-row__handle')?.focus({ preventScroll: true });
  };

  function nodeEl(node, level) {
    const el = h('div', {
      class: ['vw-node', level === 0 && 'list-group', isGroup(node) && 'vw-node--group'],
      style: isGroup(node) || level === 0 ? `--head:${node.color}` : null,
      'data-id': node.id,
    }, rowEl(node, level));
    if (isGroup(node)) {
      el.append(h('div', { class: 'vw-children' },
        node.children.map((child) => nodeEl(child, level + 1)),
        editing ? addBar(node, level + 1) : ''));
    }
    return el;
  }

  function addBar(node, level) {
    const button = (label, onClick) => h('button', {
      class: 'vw-adds__btn',
      type: 'button',
      'aria-haspopup': 'dialog',
      'aria-label': `${label} in ${node.name}`,
      onclick: tap(onClick),
    }, icon('plus'), label);
    const onlyGroups = node.children.length > 0 && node.children.every(isGroup);
    return h('div', { class: 'vw-adds', style: `--indent:${indentOf(level)}px` },
      onlyGroups ? null : button('Stimme', () => openAdd(node, 'Stimme')),
      level === 1 ? button('Registergruppe', () => openAdd(node, 'Gruppe')) : null);
  }

  function rowEl(node, level) {
    const count = countOf(node);
    const group = isGroup(node);
    return h('div', {
      class: ['list-row', 'vw-row', group && 'vw-row--group', level === 0 && 'vw-row--register', level === 0 && 'jmd-head'],
      style: `--indent:${indentOf(level)}px`,
    },
    editing ? h('button', {
      class: 'vw-row__handle',
      type: 'button',
      'aria-label': `${node.name} verschieben`,
      onpointerdown: (event) => startDrag(event, node),
      onkeydown: (event) => keyMove(event, node),
    }, icon('grip-vertical')) : '',
    h('button', {
      class: 'vw-row__main',
      type: 'button',
      'aria-haspopup': 'dialog',
      'aria-label': `${node.name}, ${membersLabel(count)}`,
      onclick: tap(() => (editing ? openNodeSheet(node) : openDetail(node))),
    },
    group ? sectionTile(node, level > 0) : null,
    h('span', { class: 'vw-row__name' }, node.name),
    h('span', { class: 'vw-count', 'aria-hidden': 'true' }, count),
    editing ? icon('pencil', { class: 'vw-row__edit' }) : chevron()));
  }

  function keyMove(event, node) {
    if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') return;
    event.preventDefault();
    const { index } = locate(node.id);
    if (moveNode(node.id, index + (event.key === 'ArrowUp' ? -1 : 1))) {
      haptic('selection');
      paint({ focus: node.id, flash: node.id });
    }
  }

  function startDrag(event, node) {
    if (event.button !== 0) return;
    const handle = event.currentTarget;
    const el = handle.closest('.vw-node');
    const container = el.parentElement;
    const siblings = [...container.children].filter((child) => child.classList.contains('vw-node'));
    if (siblings.length < 2) return;
    const from = siblings.indexOf(el);
    const rects = siblings.map((child) => child.getBoundingClientRect());
    const gap = rects[1].top - rects[0].bottom;
    const shift = rects[from].height + gap;
    const minDy = rects[0].top - rects[from].top;
    const maxDy = rects.at(-1).bottom - rects[from].bottom;
    const startY = event.clientY;
    let to = from;
    let moved = false;
    handle.setPointerCapture(event.pointerId);

    const onMove = (e) => {
      const dy = Math.max(minDy, Math.min(maxDy, e.clientY - startY));
      if (!moved) {
        if (Math.abs(e.clientY - startY) < 4) return;
        moved = true;
        el.classList.add('is-dragging');
        container.classList.add('is-sorting');
        haptic('light');
      }
      el.style.transform = `translateY(${dy}px)`;
      const center = rects[from].top + rects[from].height / 2 + dy;
      const next = siblings.reduce((acc, child, i) => (i !== from && center > rects[i].top + rects[i].height / 2 ? acc + 1 : acc), 0);
      if (next !== to) {
        to = next;
        haptic('selection');
      }
      siblings.forEach((child, i) => {
        if (i === from) return;
        let offset = 0;
        if (from < to && i > from && i <= to) offset = -shift;
        if (from > to && i >= to && i < from) offset = shift;
        child.style.transform = offset ? `translateY(${offset}px)` : '';
      });
    };
    const onUp = () => {
      handle.removeEventListener('pointermove', onMove);
      handle.removeEventListener('pointerup', onUp);
      handle.removeEventListener('pointercancel', onUp);
      if (!moved) return;
      siblings.forEach((child) => {
        child.style.transform = '';
      });
      el.classList.remove('is-dragging');
      container.classList.remove('is-sorting');
      if (to !== from && moveNode(node.id, to)) {
        haptic('medium');
        paint({ flash: node.id });
      }
    };
    handle.addEventListener('pointermove', onMove);
    handle.addEventListener('pointerup', onUp);
    handle.addEventListener('pointercancel', onUp);
  }

  function openDetail(node) {
    const list = membersOf(node);
    const group = isGroup(node);
    const voice = (m) => locate(m.sectionId)?.node.name ?? getSection(m.sectionId)?.name ?? '';
    const sheet = openSheet({
      title: node.name,
      className: 'vw-sheet vw-detail-sheet',
      content: h('div', { class: 'vw-detail' },
        list.length ? h('div', { class: 'list-group' }, list.map((m) => h('a', {
          class: ['list-row', 'vw-member-row', group && 'list-row--2line'],
          href: `#/members/${m.id}`,
          onclick: () => sheet.close(),
        },
        h('span', { class: 'list-row__lead' }, ui.avatar(m, 36)),
        h('span', { class: 'list-row__body' },
          h('span', { class: 'list-row__title' }, m.displayName),
          group ? h('span', { class: 'list-row__sub' }, voice(m)) : null),
        h('span', { class: 'list-row__trail' }, ui.roleChips(m.roleIds), chevron())))) : h('p', { class: 'vw-detail__empty' }, 'Keine Mitglieder')),
    });
  }

  async function resetDefaults() {
    const ok = await ui.openConfirmSheet({
      title: 'Auf Standard zurücksetzen?',
      text: 'Name, Emoji, Farbe und Kurzname der Standardregister gehen auf die Vorgaben zurück. Eigene Register, Stimmen und alle Zuordnungen bleiben.',
      action: 'Zurücksetzen',
      icon: 'rotate-ccw',
      destructive: true,
    });
    if (!ok) return;
    resetSectionDefaults();
    haptic('success');
    paint();
    toast(html`${icon('circle-check')} Standardwerte wiederhergestellt`);
  }

  function openNodeSheet(node) {
    const { level, parent } = locate(node.id);
    const kind = kindLabel(node, level);
    const targets = moveTargets(node, level);
    const voice = isVoice(node);
    let commitName = () => {};
    let commitShort = () => {};

    const shortInput = h('input', {
      class: 'vw-inline-input',
      type: 'text',
      value: node.shortName ?? '',
      maxlength: '8',
      placeholder: suggestShortName(node.name),
      autocomplete: 'off',
      enterkeyhint: 'done',
      spellcheck: 'false',
      'aria-label': 'Kurzname',
    });
    commitShort = () => {
      const next = shortInput.value.trim() || null;
      if (next === (node.shortName ?? null)) return;
      node.shortName = next;
      paint({ flash: node.id });
    };
    shortInput.addEventListener('change', commitShort);
    shortInput.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter') return;
      event.preventDefault();
      shortInput.blur();
    });

    const emojiValue = h('span', { class: 'vw-emoji-value', 'aria-hidden': 'true' }, node.emoji);
    const emojiRow = h('button', {
      class: ['list-row', 'vw-value-row'],
      type: 'button',
      'aria-haspopup': 'dialog',
      'aria-label': `Emoji: ${node.emoji}`,
      onclick: tap(() => ui.openEmojiSheet(node.emoji, (emoji) => {
        node.emoji = emoji;
        emojiValue.textContent = emoji;
        emojiRow.setAttribute('aria-label', `Emoji: ${emoji}`);
        paint({ flash: node.id });
      }, { used: [...new Set(sections.map((x) => x.emoji).filter(Boolean))], stack: true })),
    },
    h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Emoji')),
    h('span', { class: 'list-row__trail' }, emojiValue, chevron()));

    const rows = [
      voice ? h('label', { class: ['list-row', 'vw-input-row'] },
        h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Kurzname')),
        h('span', { class: 'list-row__trail' }, shortInput)) : null,
      emojiRow,
      targets.length > 1 ? valueRow({
        title: level === 1 ? 'Register' : 'Gruppe',
        value: parent.name,
        onClick: () => ui.openPicker({
          title: 'Verschieben nach',
          options: targets,
          value: parent.id,
          onSelect: (id) => relocate(node, id),
        }),
      }) : null,
    ].filter(Boolean);

    const inheritRow = level > 0 ? h('button', {
      class: ['list-row', 'vw-inherit-row'],
      type: 'button',
      role: 'radio',
      'aria-checked': String(node.colorOwn == null),
      onclick: tap(() => {
        node.colorOwn = null;
        syncColor();
        paint({ flash: node.id });
      }),
    },
    h('span', { class: 'list-row__lead' }, h('span', { class: 'color-dot', style: `--dot:${effectiveColor(parent.record)}` })),
    h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Vom Register erben')),
    h('span', { class: 'list-row__trail' }, icon('check', { class: 'list-row__check' }))) : null;

    const grid = swatchGrid(node.colorOwn, (value) => {
      node.colorOwn = value;
      syncColor();
      paint({ flash: node.id });
    }, sectionColors);
    function syncColor() {
      inheritRow?.setAttribute('aria-checked', String(node.colorOwn == null));
      grid.select(node.colorOwn);
    }

    const sheet = openSheet({
      title: node.name,
      className: 'vw-sheet vw-node-sheet',
      content: h('div', { class: 'vw-field' },
        h('div', { class: 'list-group' }, rows),
        ui.sectionHeader('Farbe'),
        h('div', { class: 'list-group vw-swatches' }, inheritRow, grid),
        h('div', { class: 'list-group' },
          h('button', { class: 'list-row list-row--destructive', type: 'button', onclick: tap(() => confirmDelete(node, level, sheet)) },
            h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, `${kind} löschen`))))),
      onClose: () => {
        commitName();
        commitShort();
      },
    });
    commitName = titleField(sheet, {
      value: node.name,
      maxLength: 40,
      onCommit: (name) => {
        node.name = name;
        shortInput.placeholder = suggestShortName(name);
        paint({ flash: node.id });
      },
    });
  }

  function relocate(node, targetId) {
    const from = locate(node.id);
    const target = locate(targetId)?.node;
    if (!from || !target || target === from.parent) return;
    from.list.splice(from.index, 1);
    target.children.push(node);
    applySectionTree(tree);
    haptic('success');
    paint({ flash: node.id });
    toast(html`${icon('folder-input')} ${node.name} → ${target.name}`, {
      action: {
        label: 'Rückgängig',
        onClick: () => {
          target.children.splice(target.children.indexOf(node), 1);
          from.list.splice(from.index, 0, node);
          applySectionTree(tree);
          paint({ flash: node.id });
        },
      },
    });
  }

  function detach(node) {
    const found = locate(node.id);
    if (!found) return null;
    found.list.splice(found.index, 1);
    applySectionTree(tree);
    paint();
    return found;
  }

  function reattach(node, { list, index }) {
    list.splice(index, 0, node);
    applySectionTree(tree);
    paint({ flash: node.id });
  }

  async function confirmDelete(node, level, parent) {
    const affected = membersOf(node);
    if (affected.length) {
      openMoveSheet(node, level, parent, affected);
      return;
    }
    parent?.close();
    const found = detach(node);
    if (!found) return;
    toast(html`${icon('trash-2')} ${node.name} gelöscht`, {
      action: { label: 'Rückgängig', onClick: () => reattach(node, found) },
    });
  }

  function openMoveSheet(node, level, nodeSheet, affected) {
    const n = affected.length;
    const kind = kindLabel(node, level);
    const inside = idsOf(node);
    const options = [];
    const walk = (list, path) => {
      for (const item of list) {
        if (inside.has(item.id)) continue;
        if (isVoice(item)) options.push({ value: item.id, label: item.name, sub: path || undefined, lead: () => sectionTile(item, true) });
        else walk(item.children, path ? `${path} · ${item.name}` : item.name);
      }
    };
    walk(tree, '');
    let target = null;
    const value = h('span', { class: 'list-row__value' }, 'Auswählen');
    const pick = h('button', {
      class: ['list-row', 'vw-value-row'],
      type: 'button',
      'aria-haspopup': 'dialog',
      onclick: tap(() => ui.openPicker({
        title: 'Verschieben nach',
        options,
        value: target?.id,
        search: options.length > 10,
        stack: true,
        onSelect: (id, option) => {
          target = { id, name: option.label };
          value.textContent = option.label;
          sheetButton(sheet, 1).disabled = false;
        },
      })),
    },
    h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Verschieben nach')),
    h('span', { class: 'list-row__trail' }, value, chevron()));
    const sheet = openSheet({
      title: 'Mitglieder verschieben',
      className: 'vw-sheet vw-move-sheet',
      stack: true,
      content: h('div', { class: 'vw-field' },
        h('p', { class: 'sheet__lead' }, `${plural(n, 'Mitglied ist', 'Mitglieder sind')} ${node.name} zugeordnet. ${n === 1 ? 'Es wird' : 'Sie werden'} verschoben, bevor ${kind === 'Stimme' ? 'die Stimme' : kind === 'Gruppe' ? 'die Gruppe' : 'das Register'} gelöscht wird.`),
        h('div', { class: 'list-group' }, pick)),
      actions: [
        { label: 'Abbrechen', variant: 'secondary' },
        {
          label: `${kind} löschen`,
          variant: 'destructive-solid',
          icon: 'trash-2',
          onClick: () => {
            if (!target) return false;
            const before = affected.map((m) => [m, m.sectionId]);
            const ids = new Set(affected.map((m) => m.id));
            const placeUsers = (place) => {
              for (const user of Object.values(users)) {
                if (ids.has(user.id)) user.sectionId = place(user.id);
              }
            };
            affected.forEach((m) => {
              m.sectionId = target.id;
            });
            placeUsers(() => target.id);
            const found = detach(node);
            nodeSheet?.close();
            haptic('success');
            toast(html`${icon('folder-input')} ${plural(n, 'Mitglied', 'Mitglieder')} nach ${target.name} verschoben`, {
              action: {
                label: 'Rückgängig',
                onClick: () => {
                  before.forEach(([m, sectionId]) => {
                    m.sectionId = sectionId;
                  });
                  placeUsers((id) => before.find(([m]) => m.id === id)[1]);
                  if (found) reattach(node, found);
                },
              },
            });
          },
        },
      ],
    });
    sheetButton(sheet, 1).disabled = true;
  }

  function openAdd(parent, kind) {
    textSheet({
      title: kind === 'Gruppe' ? 'Neue Registergruppe' : 'Neue Stimme',
      placeholder: 'Name',
      action: 'Hinzufügen',
      onSave: (name) => {
        const record = createSection({
          id: `neu-${++uid}`,
          name,
          parentId: parent.id,
          kind: kind === 'Gruppe' ? 'section' : 'instrument',
          emoji: parent.emoji,
        });
        const node = treeNode(record, kind === 'Gruppe' ? [] : null);
        parent.children.push(node);
        applySectionTree(tree);
        paint({ flash: node.id });
      },
    });
  }

  function openAddRegister() {
    textSheet({
      title: 'Neues Register',
      placeholder: 'Name',
      action: 'Hinzufügen',
      onSave: (name) => {
        const used = new Set(tree.map((reg) => reg.colorOwn));
        const record = createSection({
          id: `neu-${++uid}`,
          name,
          parentId: 'tutti',
          kind: 'section',
          emoji: getSection('tutti').emoji,
          color: (sectionColors.find((c) => !used.has(c.value)) ?? sectionColors[0]).value,
        });
        const node = treeNode(record, []);
        tree.push(node);
        applySectionTree(tree);
        paint({ flash: node.id });
      },
    });
  }

  const paneHost = ui.isWide() ? managePane(root, ctx, '/settings/sections', [bar]) : root;
  syncHeader();
  paint();
  paneHost.append(host);
}

registerScreen('settings-sections', {
  title: 'Registerstruktur',
  tab: '/team',
  render: renderSections,
});

syncManageNav();

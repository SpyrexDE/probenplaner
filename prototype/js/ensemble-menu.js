/* Orchesterwahl: sidebar, rail and title triggers open one dropdown menu */

import { h, html, icon, toast, store, setTitleAccessory, setRailAccessory } from './app.js';
import { ensemble, otherEnsembles, getRole } from './data.js';
import * as ui from './ui.js';
import { openMenu } from './menu.js';

function roleName(user) {
  const role = (user?.roleIds ?? []).map(getRole).filter((r) => r && !r.isDefault)
    .sort((a, b) => a.sortOrder - b.sortOrder)[0];
  return role?.name ?? 'Mitglied';
}

function tile(item, size = 32) {
  return ui.ensembleTile(size, item.monogram ?? ui.ensembleMonogram());
}

function label() {
  return `${ensemble.name}, ${ui.ensembleLabel()} wechseln`;
}

export function openEnsembleMenu(anchor, options = {}) {
  const role = store.get('role');
  const user = ui.me(role);
  const others = otherEnsembles[role] ?? [];
  const items = [
    { lead: tile({}, 32), label: ensemble.name, sub: roleName(user), checked: true },
    ...others.map((item) => ({
      lead: tile(item, 32),
      label: item.name,
      sub: item.role,
      checked: false,
      onSelect: () => toast(html`${icon('lock')} Im Prototyp nur ${ensemble.shortName}`),
    })),
    '-',
    {
      icon: 'link',
      label: 'Einladungslink einlösen',
      action: true,
      onSelect: () => import('./screens/auth.js').then((mod) => mod.openInviteSheet?.()),
    },
  ];
  return openMenu(anchor, items, { className: 'ens-menu', label: `${ui.ensembleLabel()} wechseln`, minWidth: 300, ...options });
}

function trigger(attrs, ...children) {
  const button = h('button', {
    type: 'button',
    'aria-haspopup': 'menu',
    'aria-expanded': 'false',
    'aria-label': label(),
    ...attrs,
    onclick: (event) => openEnsembleMenu(event.currentTarget),
  }, ...children);
  return button;
}

export function ensembleChip() {
  const text = h('span', { class: 'ensemble-chip__name' }, ensemble.shortName);
  return trigger({ class: 'ensemble-chip large-title__ensemble ens-trigger' },
    ui.ensembleTile(28), text, icon('chevron-down', { class: 'ensemble-chip__chevron ens-trigger__chevron' }));
}

export function ensembleRailChip() {
  return trigger({ class: 'ensemble-chip ensemble-chip--rail ens-trigger' },
    ui.ensembleTile(32),
    h('span', { class: 'ensemble-chip__name' }, ensemble.shortName),
    icon('chevron-down', { class: 'ensemble-chip__chevron ens-trigger__chevron' }));
}

export function navEnsemble(form) {
  const side = form === 'side';
  return trigger({ class: ['nav-ensemble', `nav-ensemble--${form}`, 'ens-trigger'], title: side ? null : ensemble.name },
    ui.ensembleTile(side ? 32 : 40),
    side ? h('span', { class: 'nav-ensemble__name' }, ensemble.shortName) : null,
    side ? icon('chevron-down', { class: 'nav-ensemble__chevron ens-trigger__chevron' }) : null);
}

setTitleAccessory(ensembleChip);
setRailAccessory(ensembleRailChip);

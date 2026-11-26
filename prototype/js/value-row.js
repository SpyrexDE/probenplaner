/* Wertzeile: Label, Wert, Chevron; öffnet die Auswahl nur, wenn man darf, sonst Schloss */

import { h, icon, haptic, toast, html } from './app.js';

export function valueRow({ lead, label, value, onClick, href, className, ariaLabel, act, locked } = {}) {
  const interactive = Boolean(onClick || href) && !locked;
  const tag = locked ? 'button' : href ? 'a' : onClick ? 'button' : 'div';
  return h(tag, {
    class: ['list-row', 'value-row', !interactive && !locked && 'is-static', locked && 'is-locked-value', className],
    href: href && !locked ? `#${href}` : null,
    type: tag === 'button' ? 'button' : null,
    'aria-haspopup': interactive && onClick ? 'dialog' : null,
    'aria-label': locked ? `${ariaLabel ?? label}, gesperrt` : ariaLabel ?? null,
    'aria-description': locked || null,
    'data-act': act ?? null,
    onclick: locked ? () => {
      haptic('warning');
      toast(html`${icon('lock')} ${locked}`);
    } : onClick ? (event) => {
      haptic('light');
      onClick(event);
    } : null,
  },
  lead ? h('span', { class: 'list-row__lead' }, lead) : null,
  h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title value-row__label' }, label)),
  h('span', { class: 'list-row__trail' },
    value != null ? h('span', { class: 'list-row__value value-row__value' }, value) : null,
    locked ? icon('lock', { class: 'value-row__lock' }) : interactive ? icon('chevron-right', { class: 'list-row__chevron' }) : null));
}

export function iconTile(name, tone) {
  return h('span', { class: 'icon-tile', style: tone ? `--tile:${tone}` : null, 'aria-hidden': 'true' }, icon(name));
}

/* Dropdown menu anchored to its trigger: one component for every dropdown */

import { h, icon, refreshIcons, haptic } from './app.js';

const GAP = 6;
const EDGE = 8;
let active = null;

function reduced() {
  return matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.dataset.motion === 'reduced';
}

function itemEl(item, close) {
  if (item === '-') return h('div', { class: 'menu__sep', role: 'separator' });
  if (item.type === 'label') return h('div', { class: 'menu__label', role: 'presentation' }, item.label);
  const radio = item.checked !== undefined;
  const tag = item.href ? 'a' : 'button';
  return h(tag, {
    class: ['menu__item', item.destructive && 'menu__item--danger', item.action && 'menu__item--action', item.checked && 'is-checked', item.sub && 'menu__item--2line'],
    type: tag === 'button' ? 'button' : null,
    href: item.href ?? null,
    role: radio ? 'menuitemradio' : 'menuitem',
    'aria-checked': radio ? String(Boolean(item.checked)) : null,
    'aria-disabled': item.disabled ? 'true' : null,
    tabindex: '-1',
    'data-replace': item.replace ? '' : null,
    onclick: (event) => {
      if (item.disabled) {
        event.preventDefault();
        return;
      }
      haptic('selection');
      close({ focus: !item.href });
      item.onSelect?.(event);
    },
  },
  item.lead ? h('span', { class: 'menu__lead' }, item.lead) : item.icon ? h('span', { class: 'menu__lead menu__lead--icon' }, icon(item.icon)) : null,
  h('span', { class: 'menu__body' },
    h('span', { class: 'menu__title' }, item.label),
    item.sub ? h('span', { class: 'menu__sub' }, item.sub) : null),
  radio || item.trail ? h('span', { class: 'menu__check', 'aria-hidden': 'true' }, item.checked ? icon('check') : item.trail ?? null) : null);
}

function place(menu, anchor, align) {
  const box = anchor.getBoundingClientRect();
  const width = menu.offsetWidth;
  const below = innerHeight - box.bottom - GAP - EDGE;
  const above = box.top - GAP - EDGE;
  const natural = menu.scrollHeight;
  const up = natural > below && above > below;
  const room = Math.max(120, up ? above : below);
  menu.style.maxHeight = `${Math.round(room)}px`;
  const height = Math.min(natural, room);
  let left = align === 'end' ? box.right - width : box.left;
  left = Math.min(Math.max(EDGE, left), innerWidth - width - EDGE);
  const top = up ? box.top - GAP - height : box.bottom + GAP;
  menu.style.left = `${Math.round(left)}px`;
  menu.style.top = `${Math.round(top)}px`;
  menu.dataset.side = up ? 'top' : 'bottom';
  const originX = Math.min(Math.max(0, box.left + box.width / 2 - left), width);
  menu.style.transformOrigin = `${Math.round(originX)}px ${up ? '100%' : '0'}`;
}

function items(menu) {
  return [...menu.querySelectorAll('.menu__item:not([aria-disabled="true"])')];
}

export function closeMenu(options) {
  active?.close(options);
}

export function isMenuOpen(anchor) {
  return Boolean(active && (!anchor || active.anchor === anchor));
}

export function openMenu(anchor, list, { align = 'start', label, className, minWidth = 220, onClose } = {}) {
  if (active?.anchor === anchor) {
    active.close();
    return null;
  }
  active?.close({ focus: false });
  const menu = h('div', {
    class: ['menu', className],
    role: 'menu',
    'aria-label': label ?? anchor.getAttribute('aria-label') ?? null,
    tabindex: '-1',
  });
  let closed = false;
  const close = ({ focus = true } = {}) => {
    if (closed) return;
    closed = true;
    if (active?.menu === menu) active = null;
    anchor.setAttribute('aria-expanded', 'false');
    anchor.classList.remove('is-menu-open');
    document.removeEventListener('pointerdown', onOutside, true);
    document.removeEventListener('keydown', onKey, true);
    document.removeEventListener('scroll', onScroll, true);
    removeEventListener('resize', onResize);
    removeEventListener('hashchange', onResize);
    menu.classList.remove('is-open');
    menu.classList.add('is-closing');
    const done = () => menu.remove();
    if (reduced()) setTimeout(done, 120);
    else setTimeout(done, 200);
    if (focus && anchor.isConnected) anchor.focus({ preventScroll: true });
    onClose?.();
  };
  menu.append(...list.filter(Boolean).map((item) => itemEl(item, close)));
  menu.style.minWidth = `${Math.max(minWidth, Math.round(anchor.getBoundingClientRect().width))}px`;
  document.body.append(menu);
  refreshIcons(menu);
  place(menu, anchor, align);

  const focusAt = (index) => {
    const all = items(menu);
    if (!all.length) return;
    all[(index + all.length) % all.length].focus({ preventScroll: false });
  };
  const onKey = (event) => {
    if (event.key === 'Escape') {
      event.preventDefault();
      event.stopPropagation();
      close();
      return;
    }
    if (event.key === 'Tab') {
      close({ focus: true });
      return;
    }
    const all = items(menu);
    const index = all.indexOf(document.activeElement);
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      focusAt(index + 1);
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      focusAt(index < 0 ? -1 : index - 1);
    } else if (event.key === 'Home') {
      event.preventDefault();
      focusAt(0);
    } else if (event.key === 'End') {
      event.preventDefault();
      focusAt(-1);
    }
  };
  const onOutside = (event) => {
    if (menu.contains(event.target) || anchor.contains(event.target)) return;
    close({ focus: false });
  };
  const onScroll = (event) => {
    if (menu.contains(event.target)) return;
    close({ focus: false });
  };
  const onResize = () => close({ focus: false });
  menu.addEventListener('focusout', (event) => {
    const next = event.relatedTarget;
    if (next && (menu.contains(next) || next === anchor)) return;
    requestAnimationFrame(() => {
      if (!menu.contains(document.activeElement)) close({ focus: false });
    });
  });
  document.addEventListener('pointerdown', onOutside, true);
  document.addEventListener('keydown', onKey, true);
  document.addEventListener('scroll', onScroll, true);
  addEventListener('resize', onResize);
  addEventListener('hashchange', onResize);

  anchor.setAttribute('aria-expanded', 'true');
  anchor.classList.add('is-menu-open');
  void menu.offsetWidth;
  menu.classList.add('is-open');
  const checked = menu.querySelector('.menu__item.is-checked');
  (checked ?? items(menu)[0] ?? menu).focus({ preventScroll: true });
  active = { menu, anchor, close };
  return active;
}

export function menuTrigger(button, build, options = {}) {
  button.setAttribute('aria-haspopup', 'menu');
  button.setAttribute('aria-expanded', 'false');
  button.addEventListener('click', (event) => {
    event.preventDefault();
    haptic('light');
    openMenu(button, build(), options);
  });
  button.addEventListener('keydown', (event) => {
    if (event.key !== 'ArrowDown' || isMenuOpen(button)) return;
    event.preventDefault();
    openMenu(button, build(), options);
  });
  return button;
}

export function menuSelect({ value, options, onChange, label, className, format = (o) => o.label, align = 'start' }) {
  const list = options.map((o) => (typeof o === 'object' ? o : { value: o, label: String(o) }));
  let current = value;
  const text = h('span', { class: 'menu-select__value' });
  const paint = () => {
    const option = list.find((o) => o.value === current);
    text.textContent = option ? format(option) : '';
  };
  paint();
  const button = h('button', { class: ['menu-select', className], type: 'button', 'aria-label': label ?? null },
    text, icon('chevron-down', { class: 'menu-select__chevron' }));
  menuTrigger(button, () => list.map((o) => ({
    label: o.label,
    sub: o.sub,
    checked: o.value === current,
    onSelect: () => {
      if (o.value === current) return;
      current = o.value;
      paint();
      onChange?.(o.value);
    },
  })), { align, minWidth: 160, label });
  button.setValue = (next) => {
    current = next;
    paint();
  };
  return button;
}

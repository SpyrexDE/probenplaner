import { registerScreen, navigate, setHeader, back, haptic, h, icon, refreshIcons, navListsSections } from '../app.js';
import * as ui from '../ui.js';
import { rowsMode } from '../rsvp.js';

export const TEAM_SEGMENTS = {
  conductor: [
    { value: '/dashboard', label: 'Übersicht' },
    { value: '/attendance', label: 'Anwesenheit', badge: true },
    { value: '/members', label: 'Mitglieder' },
    { value: '/manage', label: 'Verwaltung' },
  ],
  leader: [
    { value: '/attendance', label: 'Anwesenheit', badge: true },
    { value: '/members', label: 'Mitglieder' },
  ],
};

export function teamDefault(role) {
  return role === 'conductor' ? '/dashboard' : '/attendance';
}

export function teamSegments(role) {
  return TEAM_SEGMENTS[role] ?? TEAM_SEGMENTS.leader;
}

function segmentItems(role) {
  return teamSegments(role).map((item) => {
    const count = item.badge ? ui.undocumentedCount(role) : 0;
    return {
      value: item.value,
      label: item.label,
      count: count || null,
      badge: Boolean(count),
      ariaLabel: count ? `${item.label}, ${count} nicht erfasst` : item.label,
    };
  });
}

export function teamTitle(role, active) {
  if (!ui.isWide() || !navListsSections()) return ui.ensembleLabel();
  return teamSegments(role).find((item) => item.value === active)?.label ?? ui.ensembleLabel();
}

export function columnVars(count) {
  return [2, 3, 4, 5].map((columns) => `--r${columns}:${Math.ceil(count / columns)}`).join(';');
}

let barScroll = null;

export function deskBar(root, { title, showBack = false, backTo, items = [], actions = [] } = {}) {
  rowsMode();
  root.classList.add('desk-frame');
  const bar = h('div', { class: 'proben-toolbar desk-bar' },
    showBack ? h('button', {
      class: 'icon-button desk-bar__back',
      type: 'button',
      'aria-label': 'Zurück',
      onclick: () => {
        haptic('light');
        back(backTo ?? undefined);
      },
    }, icon('chevron-left')) : null,
    h('h1', { class: 'proben-toolbar__title' }, title),
    items,
    h('div', { class: 'proben-toolbar__spacer' }),
    actions.filter(Boolean).map((action) => h('button', {
      class: ['button', action.primary ? 'button--primary' : 'button--plain', 'desk-bar__action', action.iconOnly && 'desk-bar__action--icon', action.destructive && 'desk-bar__action--danger', action.primary && 'desk-bar__action--primary'],
      type: 'button',
      'aria-label': action.label,
      title: action.iconOnly ? action.label : null,
      onclick: (event) => {
        haptic('light');
        action.onClick?.(event);
      },
    }, icon(action.icon), action.iconOnly ? null : h('span', { class: 'button__label' }, action.label))));
  root.querySelector(':scope > .desk-bar')?.remove();
  root.prepend(bar);
  refreshIcons(bar);
  const content = document.getElementById('appContent');
  content.removeEventListener('scroll', barScroll);
  barScroll = () => bar.classList.toggle('is-stuck', content.scrollTop > 0);
  content.addEventListener('scroll', barScroll, { passive: true });
  barScroll();
  return bar;
}

function teamNav(ctx, current) {
  const items = segmentItems(ctx.role);
  const segmented = ui.segmented(items, current, (path) => {
    if (path === current) return;
    ctx.store.set('teamSegment', path);
    navigate(path, { replace: true, direction: 'none' });
  });
  segmented.setAttribute('aria-label', ui.ensembleLabel());
  return segmented;
}

export function renderTeamHeader(ctx, active, { actions = [], tools = [], subtitle, root, sticky = true } = {}) {
  const current = active ?? ctx.path.split('?')[0];
  if (ctx.store.get('teamSegment') !== current) ctx.store.set('teamSegment', current);
  if (ui.isWide()) {
    setHeader({ title: teamTitle(ctx.role, current), large: true, back: false, subtitle });
    root?.querySelector(':scope > .team-nav')?.remove();
    if (!root) return null;
    const tabs = navListsSections() ? null : teamNav(ctx, current);
    tabs?.classList.add('team-tabs');
    const bar = deskBar(root, { title: teamTitle(ctx.role, current), items: [tabs, ...tools], actions });
    bar.classList.add('team-bar');
    return null;
  }
  setHeader({ title: teamTitle(ctx.role, current), large: true, back: false, subtitle, actions });
  const items = segmentItems(ctx.role);
  const wrap = h('div', { class: ['segmented--sticky', 'team-nav', items.length > 2 && 'team-nav--many', !sticky && 'team-nav--static'] }, teamNav(ctx, current));
  root?.querySelector(':scope > .team-nav')?.remove();
  if (root) root.prepend(wrap);
  refreshIcons(wrap);
  return wrap;
}

registerScreen('team', {
  title: () => ui.ensembleLabel(),
  tab: '/team',
  render(root, params, ctx) {
    if (!ui.guard(ctx, ['leader', 'conductor'])) return;
    const stored = ctx.store.get('teamSegment');
    const allowed = teamSegments(ctx.role).map((item) => item.value);
    const target = allowed.includes(stored) ? stored : teamDefault(ctx.role);
    navigate(target, { replace: true, direction: 'none' });
  },
});

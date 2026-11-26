/* Shell from 1200 px: Seitenleiste by default, ?nav=top (Kopfleiste), ?nav=jmd (gefüllte Leiste), ?nav=rail (Schiene) */

import { store, rerender, renderNav, SIDEBAR, h, icon, refreshIcons } from './app.js';
import * as data from './data.js';
import * as ui from './ui.js';
import { syncAside, statCard, ownUpcoming, upcomingAll } from './shell-aside.js';
import { ensembleRailChip, openEnsembleMenu } from './ensemble-menu.js';
import { dayTile, rowTimeText, answerControl } from './rsvp.js';
import { seatSlot } from './seating.js';

const requested = new URLSearchParams(location.search).get('nav');
const variant = ['top', 'jmd', 'rail'].includes(requested) ? requested : 'side';
const rootEl = document.documentElement;
const content = document.getElementById('appContent');
const rail = document.getElementById('appRail');
const wide = matchMedia('(min-width: 1200px)');

const MARGIN = 32;
const PAIR_MIN = 1624;
const PLAN_WIDE = 1320;
const PLAN_TEXTS = 2;
const PLAN_ALL_NOTES = 1700;
const PLAN_COLS = 7;

rootEl.dataset.nav = variant;

/* Width: list plus second column */

function syncPair() {
  rootEl.toggleAttribute('data-pair', wide.matches && content.clientWidth - 2 * MARGIN >= PAIR_MIN);
}

/* Plan: real columns on wide windows */

function planNotes(r, all) {
  const infos = (r.infos ?? []).filter((info) => info.text);
  const shown = all ? infos : infos.slice(0, PLAN_TEXTS);
  const rest = all ? [] : infos.slice(PLAN_TEXTS);
  return h('div', { class: 'shell-plan__notes' }, shown.map((info, i) => h('span', { class: 'shell-plan__line' },
    h('span', { class: 'shell-plan__note' },
      info.emoji ? h('span', { class: 'rx-emo', 'aria-hidden': 'true' }, info.emoji) : null,
      h('span', { class: 'shell-plan__text', title: info.text }, info.text)),
    i === shown.length - 1 ? rest.map((more) => h('span', { class: 'rx-emo', role: 'img', title: more.text, 'aria-label': more.text }, more.emoji || '+')) : null)));
}

function planCast(r) {
  const roles = (r.roleIds ?? []).length ? ui.roleChips(r.roleIds, { names: true }) : null;
  return roles ?? h('span', { class: 'rx-tag rx-tag--cast', title: r.groupsText }, icon('users-round'), h('span', {}, r.groupsText));
}

function widenPlan(table, all) {
  table.classList.add('shell-plan');
  table.querySelector('.plan-table__col--type').after(h('col', { class: 'plan-table__col--cast' }));
  table.querySelector('.plan-table__col--place').after(h('col', { class: 'plan-table__col--notes' }));
  const headType = table.querySelector('thead .plan-table__type');
  headType.textContent = 'Art';
  headType.after(h('th', { class: 'plan-table__cast', scope: 'col' }, 'Besetzung'));
  table.querySelector('thead .plan-table__place').after(h('th', { class: 'plan-table__notes', scope: 'col' }, 'Hinweise'));
  for (const th of table.querySelectorAll('.plan-table__month th')) th.colSpan = PLAN_COLS;
  for (const tr of table.querySelectorAll('tbody tr[data-id]')) {
    const r = data.getRehearsal(tr.dataset.id);
    if (!r) continue;
    const type = tr.querySelector('.plan-table__type');
    type.after(h('td', { class: 'plan-table__cast' }, r.groupsText || r.roleIds?.length ? planCast(r) : null));
    const badges = type.querySelector('.plan-table__badges');
    const kind = badges.querySelector('.rx-tag--kind');
    const off = badges.querySelector('.badge--no');
    badges.replaceChildren(...[off, kind ?? (off ? null : h('span', { class: 'shell-plan__kind' }, r.type))].filter(Boolean));
    tr.querySelector('.plan-table__place').after(h('td', { class: 'plan-table__notes' }, planNotes(r, all)));
  }
  refreshIcons(table);
}

function syncPlan() {
  const host = content.querySelector(':scope > [data-screen="plan"] .plan__table');
  const table = host?.querySelector('.plan-table');
  if (!table) return;
  const want = wide.matches && host.clientWidth >= PLAN_WIDE;
  const has = table.classList.contains('shell-plan');
  if (want && !has) widenPlan(table, host.clientWidth >= PLAN_ALL_NOTES);
  else if (!want && has) rerender();
  if (want) for (const cell of table.querySelectorAll('.plan-table__panel > td')) cell.colSpan = PLAN_COLS;
}

/* A: Kopfleiste */

function clearTop() {
  delete rootEl.dataset.topInline;
  for (const label of content.querySelectorAll('.shell-back-label')) label.remove();
  const bell = rail.querySelector('.app-rail__bell');
  const head = rail.querySelector('.app-rail__head');
  if (bell && head && bell.parentElement !== head) head.append(bell);
}

function backOf(bar) {
  return bar.querySelector(':scope > :is(.desk-bar__back, .responses-bar__back)');
}

function dedupeTitle(bar) {
  const active = rail.querySelector('[aria-current="page"][data-title]')?.dataset.title;
  const back = backOf(bar);
  if (back) {
    const text = active ?? 'Zurück';
    let label = back.querySelector('.shell-back-label');
    if (!label) back.append(label = h('span', { class: 'shell-back-label' }));
    if (label.textContent !== text) label.textContent = text;
    back.setAttribute('aria-label', `Zurück zu ${text}`);
  }
}

function syncTop() {
  if (!wide.matches) {
    clearTop();
    return;
  }
  const nav = rail.querySelector('.app-rail__nav');
  const bell = rail.querySelector('.app-rail__bell');
  if (!nav || !bell) return;
  if (bell.previousElementSibling !== nav) nav.after(bell);
  rootEl.dataset.topInline = '0';
  const bar = content.querySelector('.proben-toolbar');
  if (!bar) return;
  dedupeTitle(bar);
  for (const action of bar.querySelectorAll('.desk-bar__action:not([title])')) action.title = action.getAttribute('aria-label') ?? '';
}

/* C: Wechsler in der Titelzeile */

function syncRailChip() {
  for (const bar of content.querySelectorAll('.proben-toolbar')) {
    const chip = bar.querySelector(':scope > .shell-chip');
    if (wide.matches && !chip) {
      const fresh = ensembleRailChip();
      fresh.classList.add('shell-chip');
      bar.prepend(fresh);
      refreshIcons(fresh);
    } else if (!wide.matches && chip) {
      chip.remove();
    }
  }
}

/* B: gefüllte JMD-Leiste */

function jmdHead(user) {
  const voice = ui.sectionOf(user);
  const label = data.ensemble.shortName;
  return h('button', {
    class: 'sj-head',
    type: 'button',
    'aria-haspopup': 'menu',
    'aria-expanded': 'false',
    'aria-label': `${user.displayName}, ${label}, ${ui.ensembleLabel()} wechseln`,
    onclick: (event) => openEnsembleMenu(event.currentTarget),
  },
  ui.avatar(user, 36),
  h('span', { class: 'sj-head__text' },
    h('span', { class: 'sj-head__line' },
      h('span', { class: 'sj-head__name' }, user.displayName),
      h('span', { class: 'sj-head__role' }, user.label)),
    h('span', { class: 'sj-head__sub' },
      h('span', { class: 'sj-head__ensemble' }, label),
      voice ? ` · ${voice}` : null)),
  icon('chevron-down', { class: 'sj-head__chevron ens-trigger__chevron' }));
}

function jmdItem(item) {
  return h('a', { class: 'nav-item', href: `#${item.route}`, 'data-nav': item.route, 'data-title': item.label },
    h('span', { class: 'nav-item__icon' }, icon(item.icon)),
    h('span', { class: 'nav-item__label' }, item.label),
    item.count ? h('span', { class: 'nav-item__count', 'data-count': item.count }) : null);
}

function unread(role) {
  return data.notifications.filter((n) => !n.read && (role !== 'member' || n.audience === 'all')).length;
}

function jmdBell(role) {
  const count = unread(role);
  return h('button', {
    class: 'nav-item sj-bell',
    type: 'button',
    'aria-label': count ? `Mitteilungen, ${count} neu` : 'Mitteilungen',
    onclick: (event) => ui.openNotificationsSheet(store.get('role'), event.currentTarget),
  },
  h('span', { class: 'nav-item__icon' }, icon('bell')),
  h('span', { class: 'nav-item__label' }, 'Mitteilungen'),
  count ? h('span', { class: 'sj-bell__count' }, String(count)) : null);
}

function jmdNav(role) {
  return h('nav', { class: 'sj-nav', 'aria-label': 'Hauptnavigation' },
    (SIDEBAR[role] ?? SIDEBAR.member).map(jmdItem),
    jmdBell(role),
    jmdItem({ route: '/profile', icon: 'circle-user-round', label: 'Profil' }));
}

function leaderCounts(role, user) {
  const undocumented = ui.undocumentedRehearsals(role);
  const scope = role === 'leader' ? [user.sectionId] : null;
  const due = (role === 'leader' ? ownUpcoming(user) : upcomingAll()).filter((r) => !r.cancelled && !ui.hasStarted(r) && ui.isDue(r));
  const rows = [
    { label: 'Nicht erfasst', value: undocumented.length, href: undocumented.length ? `#/attendance?rehearsal=${undocumented[0].id}` : '#/attendance' },
    { label: 'Offene Antworten', value: due.reduce((sum, r) => sum + ui.openIds(r, scope).length, 0), href: role === 'leader' ? '#/promises' : '#/rehearsals' },
  ];
  if (role === 'conductor') rows.push({ label: 'Zu wenig Zusagen', value: due.filter((r) => ui.criticalSections(r).length).length, href: '#/dashboard' });
  return rows;
}

function jmdCounts(rows) {
  return h('nav', { class: 'sj-counts', 'aria-label': 'Offene Punkte' },
    rows.map((row) => h('a', { class: ['sj-count', !row.value && 'is-zero'], href: row.href },
      h('span', { class: 'sj-count__label' }, row.label),
      h('span', { class: 'sj-count__n' }, String(row.value)),
      icon('chevron-right'))));
}

function jmdSignature(role, user, rows) {
  const own = ownUpcoming(user).map((r) => data.getPromise(user.id, r.id)?.status ?? '').join();
  return [role, own, rows.map((row) => `${row.value}${row.href}`).join(), unread(role)].join('|');
}

function syncJmd() {
  if (!rail.querySelector(':scope > .app-rail__nav')) return;
  const role = store.get('role');
  const user = ui.me(role);
  const rows = role === 'member' ? [] : leaderCounts(role, user);
  const signature = jmdSignature(role, user, rows);
  let panel = rail.querySelector(':scope > .sj');
  if (panel?.dataset.signature === signature) return;
  panel?.remove();
  panel = h('div', { class: 'sj', dataset: { signature } },
    jmdHead(user),
    statCard(role, user, { link: true }),
    jmdNav(role),
    rows.length ? jmdCounts(rows) : null);
  rail.append(panel);
  refreshIcons(panel);
  renderNav();
}

/* Seitenleiste: Nächste Probe über der Person (R89, wie in Produktion) */

function nextRehearsal(role) {
  const list = role === 'conductor' ? upcomingAll() : ownUpcoming(ui.me(role));
  return list.find((r) => !r.cancelled) ?? null;
}

function nextSignature(role, r) {
  if (!r) return role;
  const user = ui.me(role);
  const own = role === 'conductor'
    ? data.promises.filter((p) => p.rehearsalId === r.id).map((p) => p.status[0]).join('')
    : data.getPromise(user.id, r.id)?.status ?? '';
  return [role, r.id, r.start, r.end, r.location, own, ui.hasStarted(r)].join('|');
}

function nextWidget(role, r) {
  const user = ui.me(role);
  const conductor = role === 'conductor';
  const href = conductor ? `#/rehearsals/${r.id}` : `#/promises/${r.id}`;
  const place = r.location && r.location !== data.ensemble.defaultLocation ? r.location : null;
  const slot = conductor
    ? seatSlot(r)
    : answerControl(r, user.id, { locked: ui.hasStarted(r), onChange: () => rerender() });
  return h('section', { class: 'side-next', 'aria-labelledby': 'sideNextTitle' },
    h('h2', { class: 'side-next__title', id: 'sideNextTitle' }, 'Nächste Probe'),
    h('div', { class: ['side-next__card', r.isToday && 'is-today'], style: ui.rowTint(r) },
      h('a', { class: 'side-next__link', href, 'aria-label': `${data.formatDate(r.date)}, ${ui.timeRange(r)}${place ? `, ${place}` : ''}` }),
      h('span', { class: 'day-tile side-next__tile', 'aria-hidden': 'true' }, dayTile(r, { today: r.isToday })),
      h('span', { class: 'side-next__when', 'aria-hidden': 'true' },
        h('span', { class: 'side-next__time' }, rowTimeText(r)),
        place ? h('span', { class: 'side-next__place' }, place) : null),
      h('span', { class: 'side-next__slot' }, slot)));
}

function syncNext() {
  const side = rail.classList.contains('is-side') ? rail : null;
  const current = rail.querySelector(':scope > .side-next');
  const screen = content.querySelector('.screen')?.dataset.screen ?? '';
  const showsRehearsals = /^(promise|rehearsal|plan|dashboard|attendance|team|series|ai-import)/.test(screen);
  if (!side || variant !== 'side' || showsRehearsals) {
    current?.remove();
    return;
  }
  const role = store.get('role');
  const r = nextRehearsal(role);
  const signature = nextSignature(role, r);
  if (current?.dataset.signature === signature) return;
  const fresh = r ? nextWidget(role, r) : null;
  if (fresh) {
    fresh.dataset.signature = signature;
    rail.insertBefore(fresh, rail.querySelector(':scope > .app-rail__me'));
    refreshIcons(fresh);
  }
  current?.remove();
}

/* Together */

function syncScrollbar() {
  const width = content.offsetWidth - content.clientWidth;
  rootEl.style.setProperty('--shell-sb', `${width}px`);
}

function clearNarrow() {
  clearTop();
  rail.querySelector(':scope > .sj')?.remove();
  rail.querySelector(':scope > .side-next')?.remove();
  for (const chip of content.querySelectorAll('.shell-chip')) chip.remove();
  content.querySelector(':scope > .shell-aside')?.remove();
  delete rootEl.dataset.aside;
}

function sync() {
  syncPair();
  if (!wide.matches) {
    clearNarrow();
    syncPlan();
    return;
  }
  if (variant === 'top') syncTop();
  else if (variant === 'rail') syncRailChip();
  else if (variant === 'jmd') syncJmd();
  else syncNext();
  syncAside(content);
  syncPlan();
}

const observer = new MutationObserver(sync);
observer.observe(content, { childList: true, subtree: true });
observer.observe(rail, { childList: true, subtree: true });

wide.addEventListener('change', () => {
  if (variant !== 'side') rerender();
  sync();
});
window.addEventListener('resize', () => {
  syncScrollbar();
  sync();
});
document.fonts?.ready.then(sync);
syncScrollbar();
syncPair();

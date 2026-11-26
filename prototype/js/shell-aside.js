/* Second column of the Proben screen and the stat card of the ?nav= variants */

import { store, currentRoute, rerender, h, refreshIcons } from './app.js';
import * as data from './data.js';
import * as ui from './ui.js';
import { dayTile, rowTimeText, rowPanel, actionBar } from './rsvp.js';

const detail = await import('./screens/promise-detail.js');

export function ownUpcoming(user) {
  return data.getRehearsalsForMember(user.id).filter((r) => r.end > data.NOW).sort((a, b) => a.start.localeCompare(b.start));
}

export function upcomingAll() {
  return data.rehearsals.filter((r) => !r.draft && r.end > data.NOW).sort((a, b) => a.start.localeCompare(b.start));
}

function ownPast(user) {
  return data.getRehearsalsForMember(user.id).filter((r) => r.end <= data.NOW && !r.cancelled).sort((a, b) => b.start.localeCompare(a.start));
}

function answerable(r) {
  return !r.cancelled && !ui.hasStarted(r);
}

/* Stat card, as in the production sidebar */

function dayMonth(iso) {
  const d = new Date(iso);
  return `${String(d.getDate()).padStart(2, '0')}.${String(d.getMonth() + 1).padStart(2, '0')}.`;
}

function weekdayOf(iso) {
  const wd = data.weekdayShort(iso);
  return `${wd[0]}${wd.slice(1).toLowerCase()}`;
}

function statsOf(role, user) {
  if (role !== 'conductor') {
    const counts = { attending: 0, notAttending: 0, pending: 0 };
    for (const r of ownUpcoming(user).filter(answerable)) {
      const status = data.getPromise(user.id, r.id)?.status ?? 'pending';
      if (status === 'attending') counts.attending += 1;
      else if (status === 'not_attending') counts.notAttending += 1;
      else counts.pending += 1;
    }
    return { title: 'Meine Proben', date: null, counts, href: '#/promises' };
  }
  const r = upcomingAll().find(answerable);
  if (!r) return null;
  return { title: 'Probe', date: `${weekdayOf(r.start)} ${dayMonth(r.start)}`, counts: ui.castStats(r), href: `#/rehearsals/${r.id}` };
}

export function statCard(role, user, { link = false } = {}) {
  const stat = statsOf(role, user);
  if (!stat) return null;
  const { attending, notAttending, pending } = stat.counts;
  const seg = (kind, n) => (n > 0 ? h('span', { class: `sj-stat__seg sj-stat__seg--${kind}`, style: `flex-grow:${n}` }) : null);
  const num = (kind, n) => h('span', { class: 'sj-stat__num' }, h('span', { class: `sj-stat__dot sj-stat__seg--${kind}` }), String(n));
  const label = `${stat.title}${stat.date ? ` ${stat.date}` : ''}: ${attending} zugesagt, ${notAttending} abgesagt, ${pending} offen`;
  return h(link ? 'a' : 'div', {
    class: 'sj-stat',
    href: link ? stat.href : null,
    role: link ? null : 'img',
    'aria-label': label,
  },
  h('span', { class: 'sj-stat__head' },
    h('span', { class: 'sj-stat__title' }, stat.title),
    stat.date ? h('span', { class: 'sj-stat__date' }, stat.date) : null),
  h('span', { class: 'sj-stat__bar' }, seg('yes', attending), seg('no', notAttending), seg('open', pending)),
  h('span', { class: 'sj-stat__nums' }, num('yes', attending), num('no', notAttending), num('open', pending)));
}

/* The rehearsal of the column */

function nextFor(user) {
  return ownUpcoming(user).find((r) => !r.cancelled) ?? null;
}

function paneCard(r, ctx, { editPath } = {}) {
  const past = r.isPast && !r.isToday;
  const today = !past && !r.cancelled && (r.isToday || ui.hasStarted(r));
  const head = h('header', {
    class: ['pane-head', today && 'is-today', past && 'is-past', r.cancelled && 'is-cancelled'],
    style: ui.rowTint(r),
  },
  h('div', { class: 'proben-row__day' }, dayTile(r, { today })),
  h('div', { class: 'pane-head__when' }, h('span', { class: 'pane-head__time' }, rowTimeText(r))),
  ['leader', 'conductor'].includes(ctx.role) ? null : actionBar(r, ctx, detail));
  const panel = rowPanel(r, ctx, detail, { onChange: () => rerender(), editPath });
  const item = h('div', { class: 'proben-item is-pane', dataset: { item: r.id } },
    head,
    h('div', { class: 'proben-expand is-open' }, h('div', { class: 'proben-expand__inner' }, panel)));
  return h('div', { class: 'proben-card' }, item);
}

function nextCard(r, user, ctx) {
  return paneCard(r, ctx);
}

function leadCard(r, ctx) {
  const past = r.isPast && !r.isToday;
  return paneCard(r, ctx, { editPath: `/rehearsals?${past ? 'seg=past&' : ''}edit=${r.id}` });
}

/* Leitung: the selected rehearsal, by default the next one */

export function paneRehearsal(past) {
  const picked = location.hash.match(/^#\/rehearsals\/(\d+)(?:[?]|$)/);
  const r = picked ? data.getRehearsal(picked[1]) : null;
  if (r && !r.draft) return r;
  if (past) return data.getPast().find((item) => !item.cancelled) ?? null;
  return upcomingAll().find((item) => !item.cancelled) ?? null;
}

function paneSignature(r) {
  if (!r) return '';
  const answers = data.promises.filter((p) => p.rehearsalId === r.id).map((p) => p.status[0]).join('');
  return `${r.id}:${answers}:${data.getAttendance(r.id).length}`;
}

function conductorColumn(ctx, r) {
  return r ? leadCard(r, ctx) : null;
}

/* Placement */

function pastSegment(screen) {
  if (/[?&](past=1|seg=past)\b/.test(location.hash)) return true;
  return screen.querySelector(':scope > .proben-toolbar .segmented__btn[aria-selected="true"]')?.dataset.value === 'past';
}

function ownSignature(user) {
  return ownUpcoming(user).map((r) => {
    const promise = data.getPromise(user.id, r.id);
    return `${r.id}:${promise?.status ?? ''}:${promise?.note ?? ''}`;
  }).join();
}

function inList(list, lead) {
  const shown = [...list.querySelectorAll('.proben-row[data-id]')].map((row) => Number(row.dataset.id));
  if (!lead || shown.includes(lead.id)) return lead;
  return shown.length ? data.getRehearsal(shown[0]) : null;
}

function memberPick(user, past, list) {
  const picked = location.hash.match(/^#\/promises\/(\d+)(?:[?]|$)/);
  const r = picked ? data.getRehearsal(picked[1]) : null;
  const lead = r && data.appliesTo(user, r) && (r.isPast && !r.isToday) === past ? r : past ? ownPast(user)[0] ?? null : nextFor(user);
  return inList(list, lead);
}

function memberColumn(user, ctx, r) {
  return r ? nextCard(r, user, ctx) : null;
}

function markCurrent(list, r) {
  const paired = document.documentElement.hasAttribute('data-pair');
  for (const row of list.querySelectorAll('.proben-row.is-current')) {
    if (paired && Number(row.dataset.id) === r?.id) continue;
    row.classList.remove('is-current');
    row.querySelector('.proben-row__link')?.removeAttribute('aria-current');
  }
  if (!paired || !r) return;
  const row = list.querySelector(`.proben-row[data-id="${r.id}"]`);
  row?.classList.add('is-current');
  row?.querySelector('.proben-row__link')?.setAttribute('aria-current', 'true');
}

export function syncAside(content) {
  const rootEl = document.documentElement;
  const screen = content.querySelector(':scope > .screen.proben-desk');
  const list = screen?.querySelector(':scope > :is(.proben-list, .rehearsals-list)');
  const current = content.querySelector(':scope > .shell-aside');
  const { query = {} } = currentRoute() ?? {};
  if (!list || query.demo === 'empty' || query.empty === '1') {
    current?.remove();
    delete rootEl.dataset.aside;
    return;
  }
  rootEl.dataset.aside = '1';
  const role = store.get('role');
  const user = ui.me(role);
  const variant = rootEl.dataset.nav;
  const past = pastSegment(screen);
  const own = past ? 'past' : ownSignature(user);
  const lead = role === 'conductor' ? inList(list, paneRehearsal(past)) : memberPick(user, past, list);
  const signature = `${role}|${variant}|${role === 'conductor' ? paneSignature(lead) : `${own}|${lead?.id}`}`;
  markCurrent(list, lead);
  if (current?.dataset.signature === signature && content.lastElementChild === current) return;
  const ctx = { ...currentRoute(), role, me: user };
  const children = [role === 'conductor' ? conductorColumn(ctx, lead) : memberColumn(user, ctx, lead)].filter(Boolean);
  const reuse = current && content.lastElementChild === current;
  const aside = reuse ? current : h('aside', {
    class: 'shell-aside proben-rows',
    'aria-label': 'Probe',
  });
  const changed = reuse && current.dataset.lead !== String(lead?.id ?? '');
  aside.dataset.signature = signature;
  aside.dataset.lead = String(lead?.id ?? '');
  aside.replaceChildren(...children);
  if (!reuse) {
    current?.remove();
    content.append(aside);
  }
  refreshIcons(aside);
  if (changed) {
    const card = aside.querySelector('.is-pane');
    card?.classList.add('is-swapping');
    requestAnimationFrame(() => requestAnimationFrame(() => card?.classList.remove('is-swapping')));
  }
}

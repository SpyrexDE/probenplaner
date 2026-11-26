import { registerScreen, screenNamed, setHeader, navigate, rerender, toast, haptic, h, html, icon, refreshIcons, currentRoute } from '../app.js';
import {
  NOW, TODAY, members, sections, ensemble,
  getRehearsal, getSection, getPromise, getAttendance, getLocation, appliesTo,
  formatDate, formatTime,
} from '../data.js';
import * as ui from '../ui.js';
import * as rsvp from '../rsvp.js';
import { deskBar } from './team.js';
import { seatSlot, seatingChart, answerSeats, attendanceSeats } from '../seating.js';

export const DISPLAY_GROUPS = ['Streicher', 'Holzbläser', 'Blechbläser', 'Schlagwerk', 'Harfe'];
const MONTHS_LONG = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
const remindedIds = new Map();
let wideQuery = null;
const onWideChange = () => rerender();

/* Data helpers shared with responses.js */

export function leafSections() {
  return sections.filter((s) => !s.childIds?.length && (s.kind === 'instrument' || s.kind === 'section'));
}

export function sectionName(id) {
  return getSection(id)?.name ?? id;
}

export function displayGroupOf(sectionId) {
  let s = getSection(sectionId);
  while (s && !DISPLAY_GROUPS.includes(s.id)) s = getSection(s.parentId);
  return s?.id ?? null;
}

export function groupColor(sectionId) {
  return getSection(displayGroupOf(sectionId))?.color ?? 'var(--slate-500)';
}

export function statusOf(m, r) {
  return getPromise(m.id, r.id)?.status ?? 'pending';
}

export function hasStarted(r) {
  return ui.hasStarted(r);
}

export function eligibleMembers(r, scopeSectionIds) {
  return members.filter((m) => appliesTo(m, r) && (!scopeSectionIds || scopeSectionIds.includes(m.sectionId)));
}

export function scopeFor(role) {
  if (role !== 'leader') return null;
  const user = ui.me(role);
  return user ? [user.sectionId] : null;
}

export function countsFor(list, r) {
  const c = { attending: 0, notAttending: 0, pending: 0, total: list.length };
  for (const m of list) {
    const status = statusOf(m, r);
    if (status === 'attending') c.attending += 1;
    else if (status === 'not_attending') c.notAttending += 1;
    else c.pending += 1;
  }
  return c;
}

export function isReminded(r, id) {
  return Boolean(remindedIds.get(r.id)?.has(id));
}

export function wasReminded(r, ids) {
  return ids.length > 0 && ids.every((id) => isReminded(r, id));
}

export function openRemind(r, ids, { scopeLabel, onSent } = {}) {
  const sheet = ui.openRemindSheet(r, ids, { scopeLabel });
  sheet?.sent?.then((sent) => {
    if (!sent) return;
    const done = remindedIds.get(r.id) ?? new Set();
    ids.forEach((id) => done.add(id));
    remindedIds.set(r.id, done);
    onSent?.();
  });
  return sheet;
}

export function subPath(r, ctx, page) {
  const path = ctx.path ?? '';
  let base = ctx.role === 'conductor' ? '/rehearsals' : '/promises';
  if (path.startsWith('/plan')) base = '/plan';
  else if (path.startsWith('/team')) base = '/team/rehearsals';
  return `${base}/${r.id}/${page}`;
}

export function responsesHref(r, ctx, scope, counts) {
  const params = new URLSearchParams();
  if (scope) params.set('scope', scope);
  params.set('filter', counts === 'all' ? 'all' : !counts || counts.pending ? 'open' : counts.notAttending ? 'no' : 'yes');
  return `#${subPath(r, ctx, 'responses')}?${params}`;
}

/* Text */

function yearSuffix(year) {
  return String(year) === TODAY.slice(0, 4) ? '' : ` ${year}`;
}

/* Actions */

function shareUrl(r) {
  return `${location.origin}${location.pathname}#/promises/${r.id}`;
}

function copyLink(r) {
  navigator.clipboard?.writeText(shareUrl(r)).catch(() => {});
  haptic('light');
  toast(html`${icon('link')} Link kopiert`);
}

function shareLink(r) {
  if (typeof navigator.share !== 'function') {
    copyLink(r);
    return;
  }
  navigator.share({ title: `${r.type} · ${formatDate(r.date)}`, url: shareUrl(r) }).catch((error) => {
    if (error?.name !== 'AbortError') copyLink(r);
  });
}

function shareAction(r) {
  return { icon: 'share-2', label: 'Teilen', onClick: () => shareLink(r) };
}

function headerActions(r, ctx) {
  return ['leader', 'conductor'].includes(ctx.role) ? [] : [shareAction(r)];
}

/* Blocks */

function ownUser(ctx) {
  const user = ui.me(ctx.role);
  return user?.canAttend ? user : null;
}

function placeRow(r) {
  const address = getLocation(r.location)?.address ?? null;
  const body = h('span', { class: 'pd-place' },
    h('span', { class: 'pd-place__name' }, [r.location, r.room].filter(Boolean).join(' · ')),
    address ? h('span', { class: 'pd-place__address' }, address) : null);
  if (!address) return h('div', { class: 'detail-card__place' }, icon('map-pin'), body);
  return h('a', {
    class: 'detail-card__place is-link',
    href: `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${r.location}, ${address}`)}`,
    target: '_blank',
    rel: 'noopener',
    'aria-label': `${r.location}, ${address}, Karte öffnen`,
  }, icon('map-pin'), body, h('span', { class: 'pd-place__map' }, 'Karte', icon('arrow-up-right')));
}

function deadlineText(r) {
  return `Antwort bis ${formatDate(r.deadline).split(',')[0]} ${r.deadline.slice(8, 10)}.${r.deadline.slice(5, 7)}., ${formatTime(r.deadline)}`;
}

function deadlineSub(r) {
  if (!ui.deadlineOpen(r) || r.cancelled) return null;
  return h('p', { class: 'detail-card__sub pd-due' }, icon('clock'), h('span', {}, deadlineText(r)));
}

function monthText(r) {
  const [y1, m1] = r.date.split('-').map(Number);
  const [y2, m2] = (r.endDate ?? r.date).split('-').map(Number);
  if (m1 === m2 && y1 === y2) return `${MONTHS_LONG[m1 - 1]}${yearSuffix(y1)}`;
  return `${MONTHS_LONG[m1 - 1]}${y1 === y2 ? '' : yearSuffix(y1)} – ${MONTHS_LONG[m2 - 1]}${yearSuffix(y2)}`;
}

function answerSlot(r, ctx, onChange) {
  const user = ownUser(ctx);
  if (!user || r.cancelled || !appliesTo(user, r)) return null;
  if (r.isPast && !r.isToday) return getAttendance(r.id).length ? rsvp.answerControl(r, user.id, { readOnlyResult: true }) : null;
  return rsvp.answerControl(r, user.id, { onChange, locked: hasStarted(r) });
}

function headCard(r, ctx, onChange) {
  const user = ownUser(ctx);
  const outsider = user && !r.cancelled && !appliesTo(user, r);
  const tint = ui.rowTint(r);
  const slot = answerSlot(r, ctx, onChange);
  const badges = h('div', { class: 'detail-card__badges' },
    r.cancelled ? ui.cancelledTag() : ui.typeBadge(r),
    ui.groupBadge(r),
    ui.roleChips(r.roleIds),
    outsider ? h('span', { class: 'badge badge--neutral' }, icon('user-x'), h('span', { class: 'badge__label' }, 'Nicht betroffen')) : null);

  return h('article', {
    class: ['detail-card', 'pd-head', tint && 'is-tinted', r.cancelled && 'is-cancelled'],
    style: tint,
    'aria-label': formatDate(r.date),
  },
  ui.dateChip(r, { size: 'lg' }),
  h('div', { class: 'detail-card__head' },
    h('h2', { class: 'detail-card__title' }, rsvp.rowTimeText(r)),
    h('p', { class: 'detail-card__sub' }, monthText(r)),
    deadlineSub(r)),
  slot ? h('div', { class: 'pd-head__slot' }, slot) : null,
  badges.childElementCount ? badges : null,
  r.cancelled && r.cancelReason ? h('p', { class: 'pd-head__reason' }, r.cancelReason) : null,
  r.location ? placeRow(r) : null);
}

function noteRow(text, user, onEdit) {
  return h('div', { class: 'list-group pd-note' },
    h(onEdit ? 'button' : 'div', {
      class: 'list-row',
      type: onEdit ? 'button' : null,
      onclick: onEdit ?? null,
      'aria-label': onEdit ? `Grund bearbeiten: ${text}` : null,
    },
    h('span', { class: 'list-row__lead' }, ui.avatar(user, 28, { ring: false })),
    h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, text)),
    onEdit ? h('span', { class: 'list-row__trail pd-note__edit' }, icon('pencil')) : null));
}

function rsvpBlock(r, ctx, onChange) {
  const user = ownUser(ctx);
  if (!user || r.cancelled || hasStarted(r) || !appliesTo(user, r)) return null;
  const promise = getPromise(user.id, r.id);
  const status = promise?.status ?? 'pending';
  if (status === 'pending') return null;
  const declined = status === 'not_attending';
  const edit = () => (declined ? rsvp.openDeclineSheet : rsvp.openNoteSheet)(r, user.id, { onDone: onChange });
  const reset = ensemble.settings.allowAttendanceReset
    ? h('button', { class: 'button button--plain button--sm pd-rsvp__reason', type: 'button', onclick: () => rsvp.resetAnswer(r, user.id, { onChange }) }, icon('rotate-ccw'), 'Antwort zurücksetzen')
    : null;
  return h('section', { class: 'pd-rsvp', 'aria-label': 'Antwort' },
    promise?.note
      ? noteRow(promise.note, user, edit)
      : h('button', { class: 'button button--plain button--sm pd-rsvp__reason', type: 'button', onclick: edit }, icon('pencil'), declined ? 'Grund angeben' : 'Notiz hinzufügen'),
    reset);
}

function whoBlock(r, ctx) {
  if (!['leader', 'conductor'].includes(ctx.role) || r.cancelled) return null;
  const scope = scopeFor(ctx.role);
  const list = eligibleMembers(r, scope);
  if (!list.length) return null;
  const scopeId = scope?.[0] ?? null;
  const held = r.isPast && !r.isToday && getAttendance(r.id).length > 0;
  const seats = held ? attendanceSeats(r, { scope: scopeId }) : answerSeats(r, { scope: scopeId });
  const mine = seats.filter((seat) => seat.state !== 'ghost');
  const total = held
    ? { attending: mine.filter((x) => x.state === 'yes').length, notAttending: mine.filter((x) => x.state === 'no').length, pending: mine.filter((x) => x.state === 'open').length }
    : countsFor(list, r);
  const chart = seatingChart(seats, {
    size: 'medium',
    kind: held ? 'attendance' : 'answers',
    emphasis: scopeId,
    ownId: scopeId ? ui.me(ctx.role)?.id : null,
    sectionHref: scopeId || held ? null : (sid) => responsesHref(r, ctx, sid, 'all'),
  });
  chart.classList.add('pd-seats');
  const card = h('div', { class: 'list-group who-card' },
    chart,
    h('a', {
      class: 'who-card__summary pd-summary',
      href: held ? `#${subPath(r, ctx, 'attendance')}` : responsesHref(r, ctx, scopeId, 'all'),
      'aria-label': `${scopeId ? `${sectionName(scopeId)}: ` : ''}${total.attending} zugesagt, ${total.notAttending} abgesagt, ${total.pending} offen`,
    },
    h('span', { class: 'pd-summary__top' },
      chart.querySelector('[class*="legend"]') ? null : rsvp.answerLegend(total),
      scopeId ? ui.groupBadge(sectionName(scopeId), { tutti: true }) : null),
    icon('chevron-right', { class: 'pd-summary__chevron' })));
  return h('section', { class: 'pd-block pd-who' },
    ui.sectionHeader(held ? 'Anwesenheit' : 'Rückmeldungen'),
    card);
}

function infosBlock(r) {
  const infos = (r.infos ?? []).filter((info) => !(r.cancelled && /fällt .*aus/i.test(info.text)));
  if (!infos.length) return null;
  return h('section', { class: 'pd-block' },
    ui.sectionHeader('Hinweise'),
    h('div', { class: 'list-group pd-infos' }, infos.map((info) => ui.infoRow(info))));
}

function agendaLine(item, r) {
  const groups = (item.groups ?? []).map(sectionName).join(', ');
  return { title: item.title, groups: groups && !/^Tutti\b/.test(groups) && groups !== r.groupsText ? groups : null };
}

function agendaEntries(r) {
  const live = r.start <= NOW && NOW < r.end;
  const byDay = new Map();
  for (const item of r.scheduleItems) {
    if (!byDay.has(item.date)) byDay.set(item.date, new Map());
    const slots = byDay.get(item.date);
    if (!slots.has(item.time)) slots.set(item.time, []);
    slots.get(item.time).push(item);
  }
  const flat = [...byDay].flatMap(([date, slots]) => [...slots.keys()].map((time) => `${date}T${time}`));
  let nowIndex = -1;
  if (live) flat.forEach((stamp, i) => { if (stamp <= NOW.slice(0, 16)) nowIndex = i; });

  const entries = [];
  let index = 0;
  for (const [date, slots] of byDay) {
    if (byDay.size > 1) entries.push({ day: formatDate(date) });
    for (const [time, items] of slots) {
      const i = index++;
      const state = r.isPast || (nowIndex > -1 && i < nowIndex) ? 'done' : i === nowIndex ? 'now' : null;
      entries.push({ time, title: items.map((item) => agendaLine(item, r)), state });
    }
  }
  return entries;
}

function agendaBlock(r) {
  if (!r.scheduleItems?.length || r.cancelled) return null;
  return h('section', { class: 'pd-block' },
    ui.sectionHeader('Ablauf'),
    ui.agenda(agendaEntries(r)));
}

/* Wide screens: the row and its panel, same component as the Proben list */

const panelApi = {
  subPath, scopeFor, eligibleMembers, countsFor, sectionName, responsesHref, statusOf, isReminded, openRemind, leafSections,
};

function wideSlot(r, ctx, onChange) {
  if (r.cancelled) return rsvp.cancelledSlot();
  const past = r.isPast && !r.isToday;
  const rows = getAttendance(r.id);
  if (ctx.role === 'conductor') {
    if (!past) return seatSlot(r);
    return rows.length ? seatSlot(r, { past: true }) : null;
  }
  const user = ownUser(ctx);
  if (!user || !appliesTo(user, r)) return null;
  if (past) return rows.length ? rsvp.answerControl(r, user.id, { readOnlyResult: true }) : null;
  if (hasStarted(r)) return rsvp.answerState(r, user.id);
  return rsvp.answerControl(r, user.id, { onChange });
}

function renderWideDetail(root, r, ctx, editPath) {
  const past = r.isPast && !r.isToday;
  const panel = rsvp.rowPanel(r, ctx, panelApi, { editPath, onChange: () => rerender() });
  const row = rsvp.probenRow(r, { today: r.isToday, past, expanded: true, slot: wideSlot(r, ctx, () => panel.sync()), dateLine: false });
  const card = h('div', { class: 'proben-card' }, rsvp.rowItem(row));
  root.replaceChildren(h('div', { class: 'proben-rows' }, card));
  rsvp.expandRow(row, panel, { animate: false });
  deskBar(root, { title: (ctx.path ?? '').startsWith('/plan') ? 'Plan' : 'Probe', showBack: true });
  refreshIcons(root);
}

function barBlock(r, ctx, editPath, redraw) {
  if (!['leader', 'conductor'].includes(ctx.role)) return null;
  return h('section', { class: 'pd-bar' }, rsvp.actionBar(r, ctx, panelApi, { editPath, redraw }));
}

function deferHeaderTitle(root) {
  const content = root.closest('.app-content') ?? document.getElementById('appContent');
  const title = root.querySelector('.pd-head .detail-card__title');
  if (!content || !title) return;
  const sync = () => {
    if (!root.isConnected) {
      content.removeEventListener('scroll', sync);
      return;
    }
    const header = document.getElementById('appHeader');
    const bar = header?.querySelector('.app-header__title');
    if (!bar) return;
    bar.classList.toggle('is-deferred', title.getBoundingClientRect().bottom > header.getBoundingClientRect().bottom);
  };
  content.addEventListener('scroll', sync, { passive: true });
  sync();
  requestAnimationFrame(sync);
}

/* Render */

export function renderRehearsalDetail(root, id, ctx, { editPath, planView = false } = {}) {
  const r = getRehearsal(id);
  root.classList.add('probe-detail');
  const inPlan = (currentRoute()?.path ?? ctx.path ?? '').startsWith('/plan');
  const edit = editPath ?? (r ? (inPlan ? `/plan/${r.id}/edit` : `/rehearsals/${r.id}`) : '/rehearsals');

  const wide = rsvp.rowsMode() && ui.isWide();
  setHeader({ title: r ? formatDate(r.date) : 'Probe', back: true, large: false, actions: r && !wide ? headerActions(r, ctx) : [] });

  if (!r) {
    root.replaceChildren(ui.emptyState({
      icon: 'calendar-x',
      title: 'Probe nicht gefunden',
      action: { label: 'Zu den Proben', onClick: () => navigate(ctx.role === 'conductor' ? '/rehearsals' : '/promises', { direction: 'back' }) },
    }));
    refreshIcons(root);
    return;
  }

  if (wide) {
    renderWideDetail(root, r, ctx, edit);
    return;
  }

  const slots = {};
  const placeholder = () => h('div', { hidden: true });
  const swap = (key, build) => {
    const next = build() ?? placeholder();
    slots[key]?.replaceWith(next);
    slots[key] = next;
    refreshIcons(next);
  };
  const redrawLive = () => {
    if (!root.isConnected) return;
    swap('rsvp', () => rsvpBlock(r, ctx, redrawLive));
    swap('who', () => whoBlock(r, ctx));
    swap('bar', () => barBlock(r, ctx, edit, redrawLive));
  };

  slots.rsvp = rsvpBlock(r, ctx, redrawLive) ?? placeholder();
  slots.who = whoBlock(r, ctx) ?? placeholder();
  slots.bar = barBlock(r, ctx, edit, redrawLive) ?? placeholder();
  slots.head = headCard(r, ctx, redrawLive);
  const conductor = ctx.role === 'conductor';
  const toProbe = planView ? h('div', { class: 'list-group' },
    h('a', { class: 'list-row list-row--action', href: conductor ? `#/rehearsals?probe=${r.id}` : `#/promises/${r.id}` },
      h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Zur Probe')),
      h('span', { class: 'list-row__trail' }, icon('chevron-right')))) : null;
  root.replaceChildren(...[
    slots.head,
    planView ? null : slots.bar,
    conductor || planView ? null : slots.rsvp,
    planView ? null : slots.who,
    infosBlock(r),
    agendaBlock(r),
    toProbe,
  ].filter(Boolean));

  refreshIcons(root);
  deferHeaderTitle(root);
}

registerScreen('promise-detail', {
  title: (params) => {
    const r = getRehearsal(params.id);
    return r ? formatDate(r.date) : 'Probe';
  },
  tab: '/promises',
  async render(root, params, ctx) {
    if (ctx.role === 'conductor') {
      const r = getRehearsal(params.id);
      const past = r && r.isPast && !r.isToday;
      const target = !r ? '/rehearsals' : ui.isWide() ? `/rehearsals/${r.id}${past ? '?seg=past' : ''}` : `/rehearsals?${past ? 'seg=past&' : ''}probe=${r.id}`;
      navigate(target, { replace: true, direction: 'none' });
      return;
    }
    if (!ui.guard(ctx, ['member', 'leader'])) return;
    wideQuery?.removeEventListener('change', onWideChange);
    wideQuery = matchMedia('(min-width: 840px)');
    wideQuery.addEventListener('change', onWideChange);
    if (wideQuery.matches) {
      const list = await import('./promises.js').catch(() => null);
      if (list?.renderPromises) {
        list.renderPromises(root, ctx, { selectedId: Number(params.id) });
        return;
      }
    }
    renderRehearsalDetail(root, params.id, ctx);
  },
  onLeave() {
    wideQuery?.removeEventListener('change', onWideChange);
    wideQuery = null;
  },
});

/* Anwesenheit as a page of the current tab */

let capture = null;

function fixCaptureChrome(root) {
  root.querySelectorAll(':scope > .team-nav').forEach((node) => node.remove());
}

async function renderCapture(root, params, ctx) {
  if (!ui.guard(ctx, ctx.tab === '/rehearsals' ? ['conductor'] : ['leader', 'conductor'])) return;
  const base = screenNamed('attendance');
  const r = getRehearsal(params.id);
  if (!base || !r) {
    setHeader({ title: 'Anwesenheit', back: true, large: false });
    root.replaceChildren(ui.emptyState({ icon: 'calendar-x', title: 'Probe nicht gefunden' }));
    refreshIcons(root);
    return;
  }
  root.classList.add('att-fixed');
  await base.render(root, params, { ...ctx, query: { ...ctx.query, rehearsal: String(r.id) } });
  const parent = ctx.path.split('?')[0].replace(/\/attendance$/, '');
  const past = r.isPast && !r.isToday;
  const detail = !parent.startsWith('/rehearsals/') ? parent : ui.isWide() ? `/rehearsals/${r.id}${past ? '?seg=past' : ''}` : `/rehearsals?${past ? 'seg=past&' : ''}probe=${r.id}`;
  setHeader({ title: 'Anwesenheit', back: true, large: false, actions: [], backTo: detail });
  if (ui.isWide()) {
    deskBar(root, {
      title: 'Anwesenheit',
      showBack: true,
      backTo: detail,
      actions: [
        ctx.role === 'conductor' ? { icon: 'pencil', label: 'Bearbeiten', onClick: () => navigate(`/rehearsals?edit=${r.id}`) } : null,
        { icon: 'calendar-x-2', label: 'Fehlzeiten', onClick: () => navigate('/attendance/table') },
      ],
    });
    base.placeAction?.();
  }
  fixCaptureChrome(root);
  capture = new MutationObserver(() => fixCaptureChrome(root));
  capture.observe(root, { childList: true, subtree: true });
}

for (const [name, tab] of [['promise-attendance', '/promises'], ['plan-attendance', '/plan'], ['rehearsal-attendance', '/rehearsals'], ['team-attendance', '/team']]) {
  registerScreen(name, {
    title: 'Anwesenheit',
    tab,
    render: renderCapture,
    onLeave(root) {
      capture?.disconnect();
      capture = null;
      screenNamed('attendance')?.onLeave?.(root);
    },
  });
}

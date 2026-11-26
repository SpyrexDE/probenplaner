import { registerScreen, setHeader, navigate, haptic, h, icon, refreshIcons } from '../app.js';
import * as data from '../data.js';
import * as ui from '../ui.js';
import { teamHeader, rehearsalRow, spokenDate } from './members.js';
import { probenRow, rowsMode, rowTags, placeMeta, weekdayAnchor, dayNumber, dueLine } from '../rsvp.js';
import { seatingChart, answerSeats, seatSlot } from '../seating.js';

const detail = await import('./promise-detail.js').catch(() => ({}));
const listModule = import('./rehearsals.js').catch(() => ({}));
let list = {};
const NEXT_COUNT = 3;
const WIDE_NEXT_COUNT = 7;
const RECENT_COUNT = 4;

export function nextRehearsals(count = NEXT_COUNT) {
  return data.rehearsals
    .filter((r) => !r.cancelled && !r.draft && r.start > data.NOW)
    .sort((a, b) => a.start.localeCompare(b.start))
    .slice(0, count);
}

function remindButton(r, ctx) {
  const { ids } = ui.remindTarget(r);
  if (!ids.length) return null;
  const todo = detail.isReminded ? ids.filter((id) => !detail.isReminded(r, id)) : ids;
  const done = !todo.length;
  return h('button', {
    class: ['button', done ? 'button--plain' : 'button--primary', 'dash-hero__remind'],
    type: 'button',
    disabled: done || null,
    'aria-label': done ? 'Erinnert' : 'Erinnern',
    onclick: () => {
      haptic('light');
      const open = detail.openRemind ?? ((rehearsal, people, opts) => ui.openRemindSheet(rehearsal, people, opts));
      open(r, todo, { scopeLabel: r.groupsText, onSent: () => ctx.rerender() });
    },
  },
  icon(done ? 'bell-check' : 'bell-ring'),
  h('span', { class: 'button__label' }, done ? 'Erinnert' : 'Erinnern'));
}

function heroCounts(r) {
  const s = ui.castStats(r);
  const item = (kind, iconName, n, word) => h('span', { class: `dash-hero__n dash-hero__n--${kind}`, title: word }, icon(iconName), String(n));
  return h('div', { class: 'dash-hero__counts', role: 'img', 'aria-label': `${s.attending} zugesagt, ${s.notAttending} abgesagt, ${s.pending} offen` },
    item('no', 'circle-x', s.notAttending, 'Abgesagt'),
    item('open', 'circle-dashed', s.pending, 'Offen'));
}

function heroCard(r, ctx) {
  const href = ui.isWide() ? `#/rehearsals/${r.id}` : `#/rehearsals?probe=${r.id}`;
  const tags = rowTags(r);
  tags?.querySelector('.rx-tag--cast.is-standard')?.remove();
  const place = r.location !== data.ensemble.defaultLocation || r.room ? placeMeta(r) : null;
  const chart = seatingChart(answerSeats(r), {
    size: 'hero',
    sectionHref: (sid) => detail.responsesHref?.(r, ctx, sid, 'all') ?? null,
  });
  chart.classList.add('dash-hero__seats');
  return h('article', { class: 'dash-hero', 'data-id': r.id },
    h('a', { class: 'dash-hero__head', href, 'aria-label': spokenDate(r) },
      h('span', { class: 'dash-hero__tile', style: ui.rowTint(r) }, weekdayAnchor(r), dayNumber(r)),
      h('span', { class: 'dash-hero__when' },
        h('span', { class: 'dash-hero__time' }, ui.timeRange(r)),
        h('span', { class: 'dash-hero__rel' }, list.monthLabel?.(r.date) ?? '', dueLine(ui.deadlineInfo(r))))),
    tags?.childElementCount || place ? h('div', { class: 'dash-hero__meta' }, tags?.childElementCount ? tags : null, place) : null,
    heroCounts(r),
    h('div', { class: 'dash-hero__act' }, remindButton(r, ctx)),
    chart);
}

function nextSection(next, ctx) {
  if (!next.length) return h('section', { class: 'dashboard__next' }, h('p', { class: 'dash-calm' }, 'Keine Proben geplant.'));
  const [first, ...rest] = next;
  const wide = ui.isWide();
  const groups = rest.length && list.upcomingGroups ? list.upcomingGroups(rest) : [];
  const sections = groups.map((g) => {
    const items = g.items.map((r) => list.listItem(r, { nav: true }));
    return h('section', { class: 'rehearsals-group' }, list.dateSep(g.label, g.range), wide ? h('div', { class: 'proben-card' }, items) : items);
  });
  return h('section', { class: 'dashboard__next' },
    heroCard(first, ctx),
    sections.length ? h('div', { class: ['dash-next', wide && 'proben-desk'] },
      h('div', { class: ['rehearsals-list', wide && 'proben-rows'] }, sections)) : null);
}

function textButton(label, { href, iconName } = {}) {
  return h('a', { class: ['button', 'button--sm', 'button--tinted', 'dash-probe__btn'], href }, iconName ? icon(iconName) : null, h('span', { class: 'button__label' }, label));
}

function headRow(r, { href, slot, className }) {
  if (ui.isWide()) return probenRow(r, { href, label: spokenDate(r), className: ['proben-row--dash', className].filter(Boolean).join(' '), past: true, today: r.isToday, slot, dateLine: false, nav: Boolean(href) });
  return rehearsalRow(r, { href, className: 'dash-probe__head', trail: slot });
}

function attendanceSection(role) {
  const items = ui.undocumentedRehearsals(role);
  if (!items.length) return null;
  const rows = items.map((r) => headRow(r, {
    href: ui.isWide() ? null : `#/team/rehearsals/${r.id}/attendance`,
    className: 'proben-row--dash-side',
    slot: textButton('Erfassen', { href: `#/attendance?rehearsal=${r.id}`, iconName: 'clipboard-pen' }),
  }));
  return h('section', { class: 'dashboard__att' },
    ui.sectionHeader('Nicht erfasst', { count: items.length }),
    h('div', { class: ['dash-att', ui.isWide() ? 'proben-card' : 'list-group'] }, rows));
}

function recentSection() {
  const past = data.rehearsals
    .filter((r) => !r.cancelled && r.start <= data.NOW && data.getAttendance(r.id).length)
    .sort((a, b) => b.start.localeCompare(a.start))
    .slice(0, RECENT_COUNT);
  if (!past.length) return null;
  const rows = past.map((r) => headRow(r, {
    href: `#/team/rehearsals/${r.id}/attendance`,
    className: 'proben-row--dash-side',
    slot: seatSlot(r, { past: true }),
  }));
  return h('section', { class: 'dashboard__recent' },
    ui.sectionHeader('Letzte Proben', { action: { label: 'Fehlzeiten', href: '#/attendance/table' } }),
    h('div', { class: ['dash-att', ui.isWide() ? 'proben-card' : 'list-group'] }, rows));
}

async function renderDashboard(root, params, ctx) {
  if (!ui.guard(ctx, ['conductor'])) return;
  list = await listModule;
  root.classList.add('screen--wide');
  rowsMode();
  const next = nextRehearsals(ui.isWide() ? WIDE_NEXT_COUNT : NEXT_COUNT + 1);

  root.append(h('div', { class: 'dashboard' },
    nextSection(next, ctx),
    h('div', { class: 'dashboard__aside' }, attendanceSection(ctx.role), recentSection())));

  teamHeader(root, ctx);
  refreshIcons(root);
}

registerScreen('dashboard', {
  title: () => ui.ensembleLabel(),
  tab: '/team',
  render: renderDashboard,
});

/* Rehearsal detail and responses inside the Orchester tab */

let responsesModule = null;

function keepInTeam(root, id) {
  root.addEventListener('click', (event) => {
    const link = event.target.closest(`a[href^="#/rehearsals/${id}/responses"], a[href^="#/promises/${id}/responses"]`);
    if (!link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey) return;
    event.preventDefault();
    navigate(link.getAttribute('href').slice(1).replace(/^\/(rehearsals|promises)\//, '/team/rehearsals/'));
  });
}

registerScreen('team-rehearsal', {
  title: (params) => {
    const r = data.getRehearsal(params.id);
    return r ? data.formatDate(r.date) : 'Probe';
  },
  tab: '/team',
  render(root, params, ctx) {
    if (!ui.guard(ctx, ['leader', 'conductor'])) return;
    const r = data.getRehearsal(params.id);
    if (typeof detail.renderRehearsalDetail !== 'function') {
      setHeader({ title: r ? data.formatDate(r.date) : 'Probe', back: true, large: false });
      root.append(ui.emptyState({ icon: 'calendar-x', title: r ? spokenDate(r) : 'Probe nicht gefunden' }));
      refreshIcons(root);
      return;
    }
    detail.renderRehearsalDetail(root, params.id, ctx, { editPath: `/rehearsals/${params.id}` });
    keepInTeam(root, params.id);
  },
});

registerScreen('team-responses', {
  title: 'Rückmeldungen',
  tab: '/team',
  async render(root, params, ctx) {
    if (!ui.guard(ctx, ['leader', 'conductor'])) return;
    try {
      responsesModule ??= await import('./responses.js');
    } catch {
      responsesModule = null;
    }
    if (!responsesModule?.renderResponses) {
      setHeader({ title: 'Rückmeldungen', back: true, large: false });
      root.append(ui.emptyState({ icon: 'users-round', title: 'Rückmeldungen nicht verfügbar' }));
      refreshIcons(root);
      return;
    }
    await responsesModule.renderResponses(root, params.id, ctx);
  },
  onLeave(root) {
    responsesModule?.cleanupResponses?.(root);
  },
});

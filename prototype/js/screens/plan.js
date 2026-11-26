/* Plan: season overview by month, filter Alle Proben | eigene Stimme, print table */

import { h, icon, navigate, refreshIcons, registerScreen, setHeader, store, toast, haptic } from '../app.js';
import { appliesTo, ensemble, formatDate, formatTime, getRehearsal, getSection, rehearsals, TODAY } from '../data.js';
import * as ui from '../ui.js';
import { weekdayAnchor, rowDate, rowsMode, rowPanel, probenRow, rowItem, toggleRow, expandRow, rowTimeText } from '../rsvp.js';
import * as detailApi from './promise-detail.js';
import { deskBar } from './team.js';

const MONTH = new Intl.DateTimeFormat('de-DE', { month: 'long' });

const dayMonth = (iso) => `${iso.slice(8, 10)}.${iso.slice(5, 7)}.`;
const keep = (text) => text.replace(/([A-Za-z]{2}) (?=\d)/g, '$1 ');

let untrack = null;

function detailPath(r) {
  return `/plan/${r.id}`;
}

function visibleTo(user, r) {
  if (!r.roleIds?.length || user.permissions?.includes('can_manage_rehearsals')) return true;
  return r.roleIds.some((id) => user.roleIds?.includes(id));
}

const rowTime = (r) => keep(ui.timeRange(r));
const isWide = (text) => !text || /^Tutti\b/.test(text);
const rowDay = (r) => rowDate(r).slice(0, -4);

function byMonth(list) {
  const groups = [];
  for (const r of list) {
    const key = r.date.slice(0, 7);
    if (groups.at(-1)?.key !== key) {
      const date = new Date(r.start);
      groups.push({ key, month: MONTH.format(date), year: String(date.getFullYear()), items: [] });
    }
    groups.at(-1).items.push(r);
  }
  return groups;
}

function roleNames(r) {
  return (r.roles ?? []).filter((role) => role.name !== 'Mitglied').map((role) => role.name);
}

function leadBadge(r) {
  return r.cancelled ? ui.cancelledTag() : ui.typeBadge(r);
}

function scopeBadges(r) {
  const roles = roleNames(r);
  const restricted = roles.length
    ? h('span', { class: 'badge badge--restricted' }, icon('lock'), h('span', { class: 'badge__label' }, roles.join(', ')))
    : null;
  if (restricted && isWide(r.groupsText)) return [restricted];
  const group = ui.groupBadge(r);
  if (group && isWide(r.groupsText)) group.classList.add('badge--quiet');
  return [group, restricted].filter(Boolean);
}

function rowLabel(r) {
  const roles = roleNames(r);
  return [
    r.multiDay
      ? `${formatDate(r.date)}, ${formatTime(r.start)} bis ${formatDate(r.endDate)}, ${formatTime(r.end)}`
      : `${formatDate(r.date)}, ${formatTime(r.start)} bis ${formatTime(r.end)}`,
    r.type !== 'Probe' && r.type,
    roles.length ? `nur ${roles.join(', ')}` : r.groupsText,
    r.location,
    r.cancelled && 'fällt aus',
  ].filter(Boolean).join(', ');
}

function stateClasses(r) {
  return [r.isToday && 'is-today', r.isPast && 'is-past', r.cancelled && 'is-cancelled'];
}

/* List (phone and tablet) */

function planRow(r) {
  const tint = ui.rowTint(r);
  return h('a', {
    class: ['list-row', 'plan-row', tint && 'is-tinted', ...stateClasses(r)],
    href: `#${detailPath(r)}`,
    'data-id': r.id,
    'aria-label': rowLabel(r),
    style: tint,
  },
  h('span', { class: 'plan-row__date' }, ui.dateChip(r, { size: 'sm' })),
  h('span', { class: 'list-row__body', 'aria-hidden': 'true' },
    h('span', { class: 'plan-row__line1' },
      h('span', { class: 'plan-row__time' }, rowTimeText(r)),
      leadBadge(r)),
    h('span', { class: 'plan-row__line2' },
      scopeBadges(r),
      ui.locationMeta(r))),
  h('span', { class: 'list-row__trail', 'aria-hidden': 'true' }, icon('chevron-right', { class: 'list-row__chevron' })));
}

function monthHead(group) {
  return h('h2', { class: 'plan-month__head' },
    h('span', { class: 'plan-month__name' }, group.month),
    group.year === TODAY.slice(0, 4) ? null : h('span', { class: 'plan-month__year' }, group.year));
}

function monthList(groups) {
  return groups.map((group) => h('section', { class: 'plan-month', 'aria-label': `${group.month} ${group.year}` },
    monthHead(group),
    h('div', { class: 'list-group' }, group.items.map((r) => planRow(r)))));
}

/* Rows (wide screens) */

function allHints(r) {
  const infos = (r.infos ?? []).filter((info) => info.text && !(r.cancelled && /fällt .*aus/i.test(info.text)));
  if (!infos.length) return null;
  return h('span', { class: 'plan-hints', 'aria-hidden': 'true' }, infos.map((info) => h('span', { class: 'plan-hints__item', title: info.text },
    info.emoji ? h('span', { class: 'rx-emo' }, info.emoji) : null,
    h('span', { class: 'plan-hints__text' }, info.text))));
}

function wideRow(r) {
  const past = r.isPast && !r.isToday;
  const row = probenRow(r, {
    href: `#${detailPath(r)}`,
    label: rowLabel(r),
    today: r.isToday && !r.cancelled,
    past,
    dateLine: false,
  });
  const hints = allHints(r);
  if (hints) row.querySelector('.proben-row__notes')?.append(hints);
  return rowItem(row);
}

function planRows(groups) {
  const head = h('div', { class: 'plan-rows__head', 'aria-hidden': 'true' },
    h('span', { class: 'plan-rows__h plan-rows__h--date' }, 'Datum'),
    h('span', { class: 'plan-rows__h plan-rows__h--kind' }, 'Art'),
    h('span', { class: 'plan-rows__h plan-rows__h--place' }, 'Ort'),
    h('span', { class: 'plan-rows__h plan-rows__h--notes' }, 'Hinweise'));
  return h('div', { class: 'proben-list proben-rows plan-rows' },
    head,
    groups.map((group) => h('section', { class: 'proben-group', 'aria-label': `${group.month} ${group.year}` },
      h('h2', { class: 'date-sep' },
        h('span', {}, group.year === TODAY.slice(0, 4) ? group.month : `${group.month} ${group.year}`)),
      h('div', { class: 'proben-card' }, group.items.map((r) => wideRow(r))))));
}

/* Print */

function sheetTable(list) {
  const heads = [['day', 'Tag'], ['date', 'Datum'], ['time', 'Zeit'], ['place', 'Ort'], ['type', 'Art'], ['groups', 'Stimmen']];
  return h('table', { class: 'plan-sheet__table' },
    h('thead', null, h('tr', null, heads.map(([key, label]) => h('th', { class: `plan-sheet__${key}`, scope: 'col' }, label)))),
    h('tbody', null, list.map((r) => {
      const roles = roleNames(r);
      return h('tr', { class: stateClasses(r) },
        h('td', { class: 'plan-sheet__day' }, weekdayAnchor(r)),
        h('td', { class: 'plan-sheet__date' }, rowDate(r)),
        h('td', { class: 'plan-sheet__time' }, rowTime(r)),
        h('td', { class: 'plan-sheet__place' }, [r.location, r.room].filter(Boolean).join(' · ')),
        h('td', { class: 'plan-sheet__type' }, r.cancelled ? 'Fällt aus' : r.type),
        h('td', { class: 'plan-sheet__groups' },
          h('span', { class: 'plan-sheet__voices' }, roles.length ? h('span', { class: 'plan-sheet__role' }, roles.join(', ')) : h('span', null, r.groupsText))));
    })));
}

function printHeader(list, scope, role, past, group) {
  const section = group ? getSection(group) : getSection(ui.me(role).sectionId);
  const numeric = (iso) => `${dayMonth(iso)}${iso.slice(0, 4)}`;
  const two = (n) => String(n).padStart(2, '0');
  const stamp = new Date();
  const now = `${two(stamp.getDate())}.${two(stamp.getMonth() + 1)}.${stamp.getFullYear()}, ${two(stamp.getHours())}:${two(stamp.getMinutes())} Uhr`;
  const range = list.length ? `${numeric(list[0].date)} – ${numeric(list.at(-1).endDate ?? list.at(-1).date)}` : '';
  const filter = [(scope === 'mine' || group) && section ? section.name : 'Alle Proben', past ? 'Vergangen' : null].filter(Boolean).join(' · ');
  return h('header', { class: 'plan-sheet__head' },
    h('h1', { class: 'plan-sheet__title' }, 'Probenplan'),
    h('p', { class: 'plan-sheet__sub' }, `${ensemble.name} · ${filter}`),
    h('p', { class: 'plan-sheet__info' },
      h('span', null, `Stand: ${now}`),
      h('span', null, range)));
}

function printPlan(state) {
  let opened = false;
  const mark = () => {
    opened = true;
  };
  window.addEventListener('beforeprint', mark, { once: true });
  window.print();
  setTimeout(() => {
    window.removeEventListener('beforeprint', mark);
    if (opened) return;
    toast('Druckdialog blockiert', {
      icon: 'printer',
      action: {
        label: 'Druckvorschau öffnen',
        onClick: () => {
          const path = planPath(state);
          window.open(`${location.pathname}${location.search}#${path}${path.includes('?') ? '&' : '?'}print=1`, '_blank', 'noopener');
        },
      },
    });
  }, 300);
}

/* Sticky month heads */

function trackSticky(root) {
  const content = document.getElementById('appContent');
  const onScroll = () => {
    if (!root.isConnected) return;
    for (const head of root.querySelectorAll('.plan-month__head')) {
      const top = head.getBoundingClientRect().top;
      const sectionTop = head.parentElement.getBoundingClientRect().top;
      head.classList.toggle('is-stuck', head.offsetParent !== null && top > sectionTop + 0.5);
    }
  };
  content.addEventListener('scroll', onScroll, { passive: true });
  requestAnimationFrame(onScroll);
  return () => content.removeEventListener('scroll', onScroll);
}

/* Screen */

function planPath(state) {
  const query = new URLSearchParams();
  if (state.scope === 'mine') query.set('scope', 'mine');
  if (state.past) query.set('past', '1');
  if (state.group) query.set('group', state.group);
  if (state.open) query.set('open', state.open);
  const qs = query.toString();
  return `/plan${qs ? `?${qs}` : ''}`;
}

registerScreen('plan', {
  title: 'Plan',
  tab: '/plan',
  render(root, params, ctx) {
    const { role } = ctx;
    const user = ui.me(role);
    const scoped = role !== 'conductor' && user.canAttend !== false;
    const wide = rowsMode() && ui.isWide();
    const groups = role === 'conductor' ? ui.registerGroups() : [];
    const state = {
      scope: scoped && ctx.query.scope === 'mine' ? 'mine' : 'all',
      past: ctx.query.past === '1',
      group: groups.some((g) => g.id === ctx.query.group) ? ctx.query.group : null,
      open: Number(ctx.query.open) || null,
    };
    root.classList.add('screen--wide');

    const print = () => printPlan(state);
    setHeader({
      title: 'Plan',
      actions: wide ? [] : [{ icon: 'printer', label: 'Drucken', onClick: print }],
    });

    const sorted = [...rehearsals].sort((a, b) => a.start.localeCompare(b.start));
    const content = h('div', { class: 'plan__content' });
    const printHost = h('div', { class: 'plan__print' });

    const period = ui.segmented([
      { value: 'upcoming', label: 'Anstehend', icon: 'calendar-clock' },
      { value: 'past', label: 'Vergangen', icon: 'history' },
    ], state.past ? 'past' : 'upcoming', (value) => {
      state.past = value === 'past';
      paint();
      document.getElementById('appContent')?.scrollTo({ top: 0 });
    });
    period.setAttribute('aria-label', 'Zeitraum');
    const SCOPES = ['all', 'mine'];
    const scopeChip = (value, label) => ui.chip({
      label,
      count: 0,
      pressed: state.scope === value,
      onClick: () => {
        if (state.scope === value) return;
        state.scope = value;
        SCOPES.forEach((option, i) => ui.setPressed(scope.children[i], state.scope === option));
        paint();
      },
    });
    const scope = scoped ? ui.chipRow([
      scopeChip('all', 'Alle Proben'),
      scopeChip('mine', getSection(user.sectionId)?.name ?? 'Meine Stimme'),
    ], { className: 'plan__groups', label: 'Proben filtern' }) : null;

    const groupOptions = groups.length ? [{ id: null, name: 'Alle Proben' }, ...groups] : [];
    const groupRow = groupOptions.length ? ui.chipRow(groupOptions.map((g) => ui.chip({
      label: g.name,
      pressed: state.group === g.id,
      onClick: () => {
        state.group = g.id;
        groupOptions.forEach((option, i) => {
          groupRow.children[i].classList.toggle('is-selected', state.group === option.id);
          groupRow.children[i].setAttribute('aria-pressed', String(state.group === option.id));
        });
        paint();
      },
    })), { className: 'plan__groups', label: 'Stimmgruppe' }) : null;

    if (!wide) root.append(h('div', { class: ['segmented--sticky', 'plan__scope', (scope || groupRow) && 'plan__scope--groups'] }, period, scope, groupRow));
    root.append(content, printHost);
    if (wide) deskBar(root, { title: 'Plan', items: [period, scope, groupRow], actions: [{ icon: 'printer', label: 'Drucken', onClick: print }] });

    function paint() {
      history.replaceState(history.state, '', `#${planPath(state)}`);
      const inGroup = (r) => !state.group || getSection(state.group).instrumentIds.some((id) => r.instrumentIds.includes(id));
      const list = sorted.filter((r) => (state.scope === 'all' ? visibleTo(user, r) : appliesTo(user, r)) && inGroup(r));
      const inPeriod = (r) => (state.past ? r.isPast && !r.isToday : !r.isPast || r.isToday);
      const shown = state.past ? list.filter(inPeriod).reverse() : list.filter(inPeriod);
      if (scope) {
        const counts = [
          sorted.filter((r) => visibleTo(user, r) && inPeriod(r)).length,
          sorted.filter((r) => appliesTo(user, r) && inPeriod(r)).length,
        ];
        counts.forEach((n, i) => { scope.children[i].querySelector('.chip__count').textContent = String(n); });
      }

      if (!shown.length) {
        content.replaceChildren(ui.emptyState({
          icon: state.past ? 'history' : 'list-music',
          title: state.past ? 'Keine vergangenen Proben' : 'Keine Proben',
          action: scoped && state.scope === 'mine' ? {
            label: 'Alle Proben',
            onClick: () => scope?.children[0].click(),
          } : null,
        }));
        printHost.replaceChildren();
        refreshIcons(content);
        return;
      }

      const groups = byMonth(shown);
      content.replaceChildren(wide
        ? h('div', { class: 'plan__rows proben-desk' }, planRows(groups))
        : h('div', { class: 'plan__list' }, monthList(groups)));

      const printed = state.past ? [...shown].reverse() : shown;
      printHost.replaceChildren(h('div', { class: 'plan-sheet' },
        printHeader(printed, state.scope, role, state.past, state.group),
        sheetTable(printed)));
      refreshIcons(content);
      if (wide && state.open) openRow(state.open);
    }

    const panelFor = (r) => rowPanel(r, ctx, detailApi, { plan: true, openPath: role === 'conductor' ? `/rehearsals/${r.id}` : `/promises/${r.id}` });

    function openRow(id) {
      const row = content.querySelector(`.plan-rows .proben-row[data-id="${id}"]`);
      const r = getRehearsal(id);
      if (!row || !r) return;
      expandRow(row, panelFor(r), { animate: false });
      row.scrollIntoView({ block: 'nearest' });
    }

    if (wide) {
      content.addEventListener('click', (event) => {
        const link = event.target.closest('.plan-rows .proben-row__link');
        if (!link || event.metaKey || event.ctrlKey || event.shiftKey) return;
        event.preventDefault();
        event.stopPropagation();
        const row = link.closest('.proben-row');
        const r = getRehearsal(row.dataset.id);
        if (!r) return;
        haptic('selection');
        toggleRow(content, row, () => panelFor(r));
        const open = row.classList.contains('is-expanded') ? row : content.querySelector('.plan-rows .proben-row.is-expanded');
        state.open = open ? Number(open.dataset.id) : null;
        history.replaceState(history.state, '', `#${planPath(state)}`);
      }, true);
    }

    paint();
    refreshIcons(root);
    untrack?.();
    untrack = trackSticky(root);
    if (ctx.query.print === '1') setTimeout(() => window.print(), 400);
  },
  onLeave() {
    untrack?.();
    untrack = null;
  },
});

registerScreen('plan-detail', {
  title: (params) => {
    const r = getRehearsal(params.id);
    return r && visibleTo(ui.me(store.get('role')), r) ? formatDate(r.date) : 'Probe';
  },
  tab: '/plan',
  render(root, params, ctx) {
    const r = getRehearsal(params.id);
    if (r && !visibleTo(ui.me(ctx.role), r)) {
      setHeader({ title: 'Probe', back: true, large: false });
      root.replaceChildren(ui.emptyState({
        icon: 'calendar-x',
        title: 'Probe nicht gefunden',
        action: { label: 'Zum Plan', onClick: () => navigate('/plan', { direction: 'back' }) },
      }));
      refreshIcons(root);
      return;
    }
    if (ui.isWide()) {
      navigate(`/plan?open=${params.id}`, { replace: true, direction: 'none' });
      return;
    }
    detailApi.renderRehearsalDetail(root, params.id, ctx, { editPath: `/plan/${params.id}/edit`, planView: true });
  },
});

registerScreen('plan-edit', {
  title: (params) => {
    const r = getRehearsal(params.id);
    return r ? formatDate(r.date) : 'Probe';
  },
  tab: '/plan',
  async render(root, params, ctx) {
    if (!ui.guard(ctx, ['conductor'])) return;
    const editor = await import('./rehearsal-edit.js').catch(() => ({}));
    root.dataset.screen = 'rehearsal-edit';
    editor.renderEditor?.(root, params.id, ctx);
  },
});

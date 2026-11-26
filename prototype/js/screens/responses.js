import { registerScreen, setHeader, navigate, back, currentRoute, h, haptic, icon, refreshIcons, toast } from '../app.js';
import { getRehearsal, getSection, getPromise, formatDate } from '../data.js';
import * as ui from '../ui.js';
import { rowDate, rowsMode, weekdayAnchor } from '../rsvp.js';
import { seatingChart, answerSeats, seatGauge } from '../seating.js';
import { voicePath } from '../voice-sheet.js';
import {
  eligibleMembers, scopeFor, openRemind, isReminded, leafSections, sectionName, statusOf, countsFor,
  displayGroupOf, hasStarted,
} from './promise-detail.js';

const FILTERS = [
  { value: 'yes', status: 'attending', label: 'Zugesagt', key: 'attending', icon: 'check' },
  { value: 'no', status: 'not_attending', label: 'Abgesagt', key: 'notAttending', icon: 'x' },
  { value: 'open', status: 'pending', label: 'Offen', key: 'pending', icon: 'circle-dashed' },
];
const wideQuery = matchMedia('(min-width: 840px)');
let stopWatching = null;
const EMPTY = {
  open: 'Keine offenen Rückmeldungen',
  no: 'Keine Absagen',
  yes: 'Keine Zusagen',
};

function basePath(ctx, r) {
  const path = ctx.path?.split('?')[0];
  if (path?.endsWith(`/${r.id}/responses`)) return path;
  return ctx.path?.startsWith('/rehearsals') ? `/rehearsals/${r.id}/responses` : `/promises/${r.id}/responses`;
}

function scopeIds(scope) {
  if (!scope || scope === 'all') return null;
  return getSection(scope)?.instrumentIds ?? [scope];
}

function headerSubtitle(r) {
  const date = formatDate(r.date);
  return r.type && r.type !== 'Probe' ? `${r.type} · ${date}` : date;
}

function scopeModel(r, fixed) {
  if (fixed) return { level: 'fixed', items: [] };
  const families = ui.registerGroups();
  if (families.filter((s) => eligibleMembers(r, s.instrumentIds).length).length > 1) return { level: 'family', items: families.map((s) => s.id) };
  const leafs = leafSections().filter((s) => eligibleMembers(r, [s.id]).length).map((s) => s.id);
  if (leafs.length > 1) return { level: 'instrument', items: leafs };
  return { level: 'none', items: [] };
}

function resolveScope(r, fixed, model, requested) {
  if (fixed) return fixed[0];
  if (!requested || requested === 'all' || !getSection(requested)) return 'all';
  if (model.items.includes(requested)) return requested;
  if (model.level === 'family' && model.items.includes(displayGroupOf(requested)) && !getSection(requested).childIds?.length) return requested;
  return 'all';
}

function memberHref(m) {
  const path = currentRoute()?.path.split('?')[0] ?? '';
  return path.endsWith('/responses') ? `#${path.replace(/\/responses$/, '')}/members/${m.id}` : `#/members/${m.id}`;
}

function personRow(m, r, { section }) {
  const note = getPromise(m.id, r.id)?.note;
  const reminded = statusOf(m, r) === 'pending' && isReminded(r, m.id);
  const face = ui.avatar(m, 36, { ring: false });
  const sub = [
    section ? h('span', { class: 'responses__section' }, sectionName(m.sectionId)) : null,
    note ? h('span', { class: 'responses__note' }, `„${note}“`) : null,
  ].filter(Boolean);
  return h('a', { class: 'list-row list-row--dense person-row', href: memberHref(m) },
    h('span', { class: 'list-row__lead' }, face),
    h('span', { class: 'list-row__body' },
      h('span', { class: 'list-row__title' }, m.displayName),
      sub.length ? h('span', { class: 'list-row__sub responses__sub' }, sub) : null),
    h('span', { class: 'list-row__trail' },
      reminded ? h('span', { class: 'responses__reminded', role: 'img', 'aria-label': 'Erinnert', title: 'Erinnert' }, icon('bell-check')) : null,
      icon('chevron-right')));
}

const STATUS_WORD = { attending: 'Zugesagt', not_attending: 'Abgesagt', pending: 'Offen' };
const STATUS_ICON = { attending: 'check', not_attending: 'x', pending: 'circle-dashed' };

function personRowWide(m, r, { section, showStatus = false }) {
  const note = getPromise(m.id, r.id)?.note;
  const status = statusOf(m, r);
  const reminded = status === 'pending' && isReminded(r, m.id);
  const remark = note ? h('span', { class: 'person-tile__note' }, `„${note}“`) : null;
  return h('a', { class: 'person-tile', href: memberHref(m), dataset: { member: m.id } },
    ui.avatar(m, 40),
    h('span', { class: 'person-tile__body' },
      h('span', { class: 'person-tile__name' }, m.displayName),
      section || remark ? h('span', { class: 'person-tile__sub' },
        section ? h('span', { class: 'person-tile__section' }, sectionName(m.sectionId)) : null,
        remark) : null),
    reminded ? h('span', { class: 'person-tile__reminded', role: 'img', 'aria-label': 'Erinnert', title: 'Erinnert' }, icon('bell-check')) : null,
    showStatus ? h('span', { class: `person-tile__status person-tile__status--${status}`, role: 'img', 'aria-label': STATUS_WORD[status], title: STATUS_WORD[status] }, icon(STATUS_ICON[status])) : null,
    icon('chevron-right', { class: 'person-tile__chevron' }));
}

function groupBy(list, keyOf) {
  const groups = new Map();
  for (const m of list) {
    const key = keyOf(m);
    if (!groups.has(key)) groups.set(key, []);
    groups.get(key).push(m);
  }
  return groups;
}

function registerSlot(people, r) {
  const c = countsFor(people, r);
  const text = `${c.attending} von ${c.total} zugesagt, ${c.notAttending} abgesagt, ${c.pending} offen`;
  return h('span', { class: 'resp-reg__slot', role: 'img', 'aria-label': text, title: text },
    h('span', { class: 'seat-slot' }, seatGauge({ yes: c.attending, no: c.notAttending, open: c.pending }, { label: '' })),
    h('span', { class: 'resp-reg__of', 'aria-hidden': 'true' }, `/${c.total}`));
}

function responsesBoard(shown, r, options) {
  const groupKey = (m) => displayGroupOf(m.sectionId) ?? m.sectionId;
  const registers = groupBy(shown, groupKey);
  const byRegister = registers.size > 1;
  const keyOf = byRegister ? groupKey : (m) => m.sectionId;
  const groups = byRegister ? registers : groupBy(shown, keyOf);
  const poolOf = (id) => options.pool.filter((m) => keyOf(m) === id);
  return h('div', { class: 'resp-board', style: `--total:${shown.length}` },
    [...groups].map(([id, people]) => h('section', {
      class: 'resp-reg',
      style: `--n:${people.length};--head:${ui.registerColor(id) ?? 'var(--accent)'}`,
      'aria-label': sectionName(id),
    },
    h('h2', { class: 'section-header resp-reg__head jmd-head' },
      h('span', { class: 'section-header__title' }, sectionName(id)),
      registerSlot(poolOf(id), r)),
    h('div', { class: 'resp-reg__people' }, people.map((m) => personRowWide(m, r, { ...options, section: options.section && byRegister }))))));
}

function packBoard(board) {
  if (!board?.isConnected) return;
  const cols = getComputedStyle(board).gridTemplateColumns.split(' ').length;
  const cards = [...board.children];
  const sizeOf = (card) => Math.min(Number(card.style.getPropertyValue('--n')), cols);
  let used = 0;
  cards.forEach((card, index) => {
    const size = sizeOf(card);
    const next = cards[index + 1];
    const closes = !next || used + size + sizeOf(next) > cols;
    card.style.setProperty('--span', closes ? cols - used : size);
    used = closes ? 0 : used + size;
  });
}

export function cleanupResponses() {
  document.documentElement.style.removeProperty('--bottom-bar-height');
}

function leave() {
  cleanupResponses();
  stopWatching?.();
  stopWatching = null;
}

export function renderResponses(root, id, ctx, { scope: scopeOption } = {}) {
  const r = getRehearsal(id);
  const wide = wideQuery.matches;
  root.classList.add('responses');
  cleanupResponses();
  stopWatching?.();
  const onWide = () => ctx.rerender?.();
  wideQuery.addEventListener('change', onWide);
  const content = document.getElementById('appContent');
  const packer = wide && content ? new ResizeObserver(() => packBoard(root.querySelector('.resp-board'))) : null;
  packer?.observe(content);
  stopWatching = () => {
    wideQuery.removeEventListener('change', onWide);
    packer?.disconnect();
  };

  if (!r) {
    setHeader({ title: 'Rückmeldungen', back: true, large: false });
    root.replaceChildren(ui.emptyState({
      icon: 'calendar-x',
      title: 'Probe nicht gefunden',
      action: { label: 'Zu den Proben', onClick: () => navigate(ctx.role === 'conductor' ? '/rehearsals' : '/promises', { direction: 'back' }) },
    }));
    refreshIcons(root);
    return;
  }

  const headerActions = () => (wide ? [] : [
    { icon: state?.search ? 'x' : 'search', label: state?.search ? 'Suche schließen' : 'Suchen', onClick: toggleSearch },
    { icon: 'copy', label: 'Namen kopieren', onClick: copyNames },
  ]);
  setHeader({ title: 'Rückmeldungen', subtitle: headerSubtitle(r), back: true, large: false });

  if (r.cancelled) {
    root.replaceChildren(h('div', { class: 'banner banner--error', role: 'status' },
      icon('ban'),
      h('div', { class: 'banner__body' },
        h('p', { class: 'banner__title' }, 'Fällt aus'),
        r.cancelReason ? h('p', { class: 'banner__text' }, r.cancelReason) : null)));
    refreshIcons(root);
    return;
  }

  const fixed = scopeFor(ctx.role);
  const model = scopeModel(r, fixed);
  const order = new Map(leafSections().map((s, i) => [s.id, i]));
  const state = {
    scope: resolveScope(r, fixed, model, scopeOption ?? ctx.query?.scope ?? ctx.query?.section),
    filter: ctx.query?.filter === 'all' || FILTERS.some((f) => f.value === ctx.query?.filter) ? ctx.query.filter : 'open',
    search: false,
    query: '',
  };
  let chipScroll = 0;
  let namesShown = [];

  const searchInput = h('input', {
    class: 'search-field__input',
    type: 'search',
    placeholder: 'Suchen',
    'aria-label': 'Personen suchen',
    autocomplete: 'off',
    oninput: (event) => {
      state.query = event.target.value;
      paint();
      searchInput.focus({ preventScroll: true });
    },
    onkeydown: (event) => {
      if (event.key !== 'Escape') return;
      event.preventDefault();
      if (wide) {
        searchInput.value = '';
        state.query = '';
        paint();
        searchInput.focus({ preventScroll: true });
      } else {
        toggleSearch();
      }
    },
  });

  function toggleSearch() {
    state.search = !state.search;
    state.query = '';
    searchInput.value = '';
    paint();
    if (state.search) searchInput.focus({ preventScroll: true });
  }

  function copyNames() {
    if (!namesShown.length) return;
    navigator.clipboard?.writeText(namesShown.map((m) => m.displayName).join(', ')).catch(() => {});
    haptic('light');
    toast(`${namesShown.length} ${namesShown.length === 1 ? 'Name' : 'Namen'} kopiert`, { icon: 'copy' });
  }

  const matches = (m) => {
    const q = state.query.trim().toLowerCase();
    return !q || `${m.displayName} ${sectionName(m.sectionId)}`.toLowerCase().includes(q);
  };

  const syncUrl = () => {
    const params = new URLSearchParams();
    if (!fixed && state.scope !== 'all') params.set('scope', state.scope);
    params.set('filter', state.filter);
    history.replaceState(history.state, '', `#${basePath(ctx, r)}?${params}`);
  };

  const keepListInView = () => {
    const content = root.closest('.app-content');
    if (!content) return;
    const top = root.offsetTop - parseFloat(getComputedStyle(content).paddingTop || '0');
    if (content.scrollTop > top) content.scrollTop = Math.max(0, top);
  };

  const set = (patch) => {
    Object.assign(state, patch);
    syncUrl();
    keepListInView();
    paint();
  };

  const scopeTitle = () => h('div', { class: 'responses__scope-title' },
    h('span', { class: 'responses__scope-name' }, state.scope === 'all' ? r.groupsText : sectionName(state.scope)));

  const scopeChips = (pool) => {
    const active = FILTERS.find((f) => f.value === state.filter);
    const countIn = (ids) => pool.filter((m) => (!ids || ids.includes(m.sectionId)) && (!active || statusOf(m, r) === active.status)).length;
    const narrowed = model.level === 'family' && state.scope !== 'all' && !model.items.includes(state.scope);
    const family = narrowed ? displayGroupOf(state.scope) : null;
    const items = [{ id: 'all', label: 'Alle' }, ...model.items.map((sid) => ({ id: sid, label: sectionName(sid) }))];
    const chips = items.map((item) => {
      const sub = item.id === family ? sectionName(state.scope) : null;
      const selected = item.id === state.scope || item.id === family;
      const none = item.id !== 'all' && !eligibleMembers(r, scopeIds(item.id)).length;
      const n = countIn(scopeIds(item.id));
      const chip = ui.chip({
        label: item.label,
        count: none ? null : n,
        tone: 'neutral',
        role: 'radio',
        selected,
        dot: item.dot,
        className: none ? 'is-empty' : null,
        ariaLabel: sub ? voicePath(state.scope) : none ? null : `${item.label}: ${n} ${active ? active.label.toLowerCase() : ''}`.trim(),
        onClick: none ? null : () => {
          if (item.id !== state.scope) set({ scope: item.id });
        },
      });
      if (none) chip.setAttribute('aria-disabled', 'true');
      if (sub) {
        chip.classList.add('is-narrowed');
        chip.append(h('span', { class: 'chip__narrow' },
          h('span', { class: 'chip__crumb', 'aria-hidden': 'true' }, '·'),
          h('span', { class: 'chip__label' }, sub),
          icon('x', { class: 'chip__clear' })));
      }
      return chip;
    });
    const row = ui.chipRow(chips, { className: 'responses__scope', label: 'Stimmen' });
    row.setAttribute('role', 'radiogroup');
    return row;
  };

  const pick = (value) => {
    haptic('selection');
    set({ filter: value === state.filter && wide ? 'all' : value });
    root.querySelector('.status-tab.is-on .status-tab__hit')?.focus({ preventScroll: true });
  };

  const statusTabs = (counts, action = null) => {
    const total = FILTERS.reduce((sum, f) => sum + counts[f.key], 0);
    return h('div', { class: 'status-tabs', role: 'group', 'aria-label': 'Antworten' },
      FILTERS.map((f) => {
        const n = counts[f.key];
        const on = f.value === state.filter;
        return h('div', { class: ['status-tab', `status-tab--${f.value}`, on && 'is-on'] },
          h('button', {
            class: 'status-tab__hit',
            type: 'button',
            'aria-pressed': String(on),
            'aria-label': `${f.label}: ${n}`,
            dataset: { value: f.value },
            onclick: () => pick(f.value),
          }),
          icon(f.icon, { class: 'status-tab__icon' }),
          h('span', { class: 'status-tab__label' }, f.label),
          h('span', { class: 'status-tab__count' }, String(n)),
          f.value === 'open' ? action : null,
          h('span', { class: 'status-tab__bar', 'aria-hidden': 'true' },
            h('span', { class: 'status-tab__fill', style: `width:${total ? (n / total) * 100 : 0}%` })));
      }));
  };

  const remindAction = (open) => {
    if (!open.length || hasStarted(r)) return null;
    const scopeLabel = state.scope === 'all' ? r.groupsText : sectionName(state.scope);
    const todo = open.filter((m) => !isReminded(r, m.id)).map((m) => m.id);
    return todo.length
      ? h('button', {
        class: 'button button--sm button--tinted status-tab__action',
        type: 'button',
        'aria-label': 'Erinnern',
        onclick: () => openRemind(r, todo, { scopeLabel, onSent: paint }),
      }, icon('bell-ring'), 'Erinnern')
      : h('button', { class: 'button button--sm button--plain status-tab__action', type: 'button', disabled: true }, icon('bell-check'), 'Erinnert');
  };

  const toolbar = (scope) => h('div', { class: 'proben-toolbar responses-bar' },
    h('button', {
      class: 'icon-button desk-bar__back',
      type: 'button',
      'aria-label': 'Zurück',
      title: 'Zurück',
      onclick: () => back(),
    }, icon('chevron-left')),
    h('div', { class: 'responses-bar__title' },
      h('h1', { class: 'proben-toolbar__title' }, 'Rückmeldungen'),
      h('span', { class: 'responses-bar__sub' }, weekdayAnchor(r), h('span', {}, rowDate(r)))),
    scope,
    h('label', { class: 'search-field desk-search' }, icon('search', { class: 'search-field__icon' }), searchInput),
    h('button', {
      class: 'proben-tool',
      type: 'button',
      title: 'Namen kopieren',
      disabled: !namesShown.length || null,
      'aria-label': `${namesShown.length} Namen kopieren`,
      onclick: copyNames,
    }, icon('copy'), h('span', { class: 'proben-tool__label' }, 'Namen kopieren')));

  const seatsFor = () => {
    const own = ui.me(ctx.role);
    const chart = seatingChart(answerSeats(r, { scope: fixed?.[0] ?? null }), {
      size: 'medium',
      emphasis: state.scope === 'all' ? null : state.scope,
      ownId: fixed ? own?.id : null,
      sectionHref: fixed ? null : (sid) => {
        const params = new URLSearchParams({ scope: sid, filter: state.filter });
        return `#${basePath(ctx, r)}?${params}`;
      },
    });
    chart.classList.add('responses__seats');
    return chart;
  };

  const paint = () => {
    const eligible = eligibleMembers(r, scopeIds(state.scope));
    const list = eligible.filter(matches);
    const counts = countsFor(list, r);
    const active = FILTERS.find((f) => f.value === state.filter);
    const shown = list
      .filter((m) => !active || statusOf(m, r) === active.status)
      .sort((a, b) => (order.get(a.sectionId) ?? 99) - (order.get(b.sectionId) ?? 99) || a.displayName.localeCompare(b.displayName, 'de'));
    namesShown = shown;

    const oldRow = root.querySelector('.responses__scope');
    if (oldRow) chipScroll = oldRow.scrollLeft;
    const choosable = model.items.length > 0;
    const pool = eligibleMembers(r, null).filter(matches);
    const scope = choosable ? scopeChips(pool) : scopeTitle();
    const nodes = [];
    if (wide) {
      nodes.push(toolbar(scope));
      if (eligible.length) nodes.push(h('div', { class: 'resp-head' }, seatsFor(), statusTabs(counts, remindAction(list.filter((m) => statusOf(m, r) === 'pending')))));
    } else {
      setHeader({ title: 'Rückmeldungen', subtitle: headerSubtitle(r), back: true, large: false, actions: headerActions() });
      if (eligible.length) nodes.push(seatsFor());
      nodes.push(h('div', { class: ['segmented--sticky', 'responses__bar'] },
        scope,
        state.search ? h('label', { class: 'search-field responses__search' }, icon('search', { class: 'search-field__icon' }), searchInput) : null,
        eligible.length ? statusTabs(counts) : null));
    }

    if (!eligible.length) {
      nodes.push(ui.emptyState({ icon: null, title: 'Nicht betroffen' }));
    } else if (!list.length) {
      nodes.push(ui.emptyState({ icon: null, title: 'Keine Treffer' }));
    } else if (!shown.length) {
      nodes.push(ui.emptyState({ icon: null, title: EMPTY[state.filter] ?? 'Keine Treffer' }));
    } else {
      const single = (scopeIds(state.scope)?.length ?? 2) < 2;
      nodes.push(wide
        ? responsesBoard(shown, r, { section: !single, showStatus: !active, pool: list })
        : h('div', { class: 'list-group responses__list' }, shown.map((m) => personRow(m, r, { section: !single }))));
    }

    cleanupResponses();
    root.classList.remove('has-bottom-bar');
    if (!wide && state.filter === 'open' && shown.length && !hasStarted(r)) {
      const todo = shown.filter((m) => !isReminded(r, m.id)).map((m) => m.id);
      const scopeLabel = state.scope === 'all' ? r.groupsText : sectionName(state.scope);
      nodes.push(h('div', { class: 'bottom-bar' },
        h('div', { class: ['bottom-bar__inner', !todo.length && 'responses__sent'] },
          todo.length
            ? h('button', {
              class: 'button button--primary button--lg button--block responses__remind',
              type: 'button',
              onclick: () => openRemind(r, todo, { scopeLabel, onSent: paint }),
            }, icon('bell-ring'), h('span', {}, 'Erinnern'))
            : h('button', { class: 'button button--plain button--lg button--block', type: 'button', disabled: true }, icon('bell-check'), h('span', {}, 'Erinnert')))));
      document.documentElement.style.setProperty('--bottom-bar-height', '66px');
      root.classList.add('has-bottom-bar');
    }

    root.replaceChildren(...nodes);
    refreshIcons(root);
    packBoard(root.querySelector('.resp-board'));

    const row = root.querySelector('.responses__scope');
    if (row) {
      row.scrollLeft = chipScroll;
      const box = row.getBoundingClientRect();
      const chip = row.querySelector('.is-selected')?.getBoundingClientRect();
      if (chip && (chip.left < box.left || chip.right > box.right)) row.scrollLeft += chip.left - box.left - box.width / 2 + chip.width / 2;
    }
  };

  if (wide) {
    rowsMode();
    root.classList.add('proben-desk');
  }
  paint();
}

registerScreen('plan-responses', {
  title: 'Rückmeldungen',
  tab: '/plan',
  render(root, params, ctx) {
    if (!ui.guard(ctx, ['leader', 'conductor'])) return;
    renderResponses(root, params.id, ctx);
  },
  onLeave: leave,
});

registerScreen('responses', {
  title: 'Rückmeldungen',
  tab: '/promises',
  render(root, params, ctx) {
    if (ctx.role === 'conductor') {
      const query = new URLSearchParams(ctx.query).toString();
      navigate(`/rehearsals/${params.id}/responses${query ? `?${query}` : ''}`, { replace: true, direction: 'none' });
      return;
    }
    if (!ui.guard(ctx, ['leader', 'conductor'])) return;
    renderResponses(root, params.id, ctx);
  },
  onLeave: leave,
});

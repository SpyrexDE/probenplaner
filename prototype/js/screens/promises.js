/* Proben (Mitglied, Stimmführung) */

import { registerScreen, setHeader, h, haptic, icon, refreshIcons } from '../app.js';
import {
  NOW, attendance, getRehearsal, getRehearsalsForMember, getSection, getAttendance, getPromise, formatDate, formatTime,
} from '../data.js';
import * as ui from '../ui.js';
import { ensembleChip } from '../ensemble-menu.js';
import {
  openAnswerSheet, answerControl, probenRow, rowItem, cancelledSlot, rowsMode, expandRow, toggleRow, rowPanel, voiceCount, hintLine, rowTimeText,
} from '../rsvp.js';

const NOW_DATE = new Date(NOW);
const DAY = 24 * 60 * 60 * 1000;
const MONTHS = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
const WEEKDAYS = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
const WEEKDAYS_SHORT = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
const ANSWER_WORDS = { attending: 'zugesagt', not_attending: 'abgesagt', pending: 'offen' };
const RESULT_WORDS = { present: 'anwesend', late: 'verspätet', absent: 'fehlt', excused: 'entschuldigt' };

const state = { selectedId: null, highlighted: null, past: false, anchor: null, openOnly: false, query: '', search: false };
let current = { root: null, ctx: null };
const detailModule = import('./promise-detail.js').catch(() => ({}));
let detailApi = null;
detailModule.then((mod) => { detailApi = mod; });
let wideQuery = null;
let wideListener = null;
let highlightTimer = null;
let stickyWatch = null;

/* Data helpers */

function startOf(r) { return new Date(r.start); }
function endOf(r) { return new Date(r.end); }

function dayStart(date) {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

function addDays(date, days) {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);
}

function shortDate(date) {
  return `${date.getDate()}.${date.getMonth() + 1}.`;
}

function daysAway(date) {
  return Math.round((dayStart(date) - dayStart(NOW_DATE)) / DAY);
}

function isPast(r) {
  return endOf(r) <= NOW_DATE;
}

function deadlineLine(dl) {
  return h('div', { class: 'proben-deadline', title: dl.title }, icon('clock'), h('span', {}, dl.text));
}

function isDueOpen(r, memberId) {
  return ui.answerOpen(r, memberId) && ui.isDue(r);
}

function lists(me) {
  const all = getRehearsalsForMember(me.id);
  const upcoming = all.filter((r) => !isPast(r)).sort((a, b) => a.start.localeCompare(b.start));
  const past = all.filter(isPast).sort((a, b) => a.start.localeCompare(b.start));
  const first = upcoming.find((r) => !r.cancelled) ?? null;
  const hero = first && startOf(first).getTime() <= NOW_DATE.getTime() + 7 * DAY ? first : null;
  return { upcoming, past, hero };
}

function monthLabel(date) {
  const month = MONTHS[date.getMonth()];
  return date.getFullYear() === NOW_DATE.getFullYear() ? month : `${month} ${date.getFullYear()}`;
}

function groupUpcoming(items) {
  const today = dayStart(NOW_DATE);
  const monday = addDays(today, -((today.getDay() + 6) % 7));
  const thisEnd = addDays(monday, 7);
  const nextEnd = addDays(monday, 14);
  const groups = new Map();
  for (const r of items) {
    const start = startOf(r);
    let key;
    let label;
    let range = null;
    if (start < thisEnd) {
      key = 'this';
      label = 'Diese Woche';
      range = `${shortDate(monday)} – ${shortDate(addDays(thisEnd, -1))}`;
    } else if (start < nextEnd) {
      key = 'next';
      label = 'Nächste Woche';
      range = `${shortDate(thisEnd)} – ${shortDate(addDays(nextEnd, -1))}`;
    } else {
      key = `${start.getFullYear()}-${start.getMonth()}`;
      label = monthLabel(start);
    }
    if (!groups.has(key)) groups.set(key, { label, range, items: [] });
    groups.get(key).items.push(r);
  }
  return [...groups.values()];
}

function groupPast(items) {
  const groups = new Map();
  for (const r of items) {
    const start = startOf(r);
    const key = `${start.getFullYear()}-${start.getMonth()}`;
    if (!groups.has(key)) groups.set(key, { label: monthLabel(start), items: [] });
    groups.get(key).items.push(r);
  }
  return [...groups.values()];
}

function ownGroups(me) {
  const ids = new Set([me.sectionId]);
  for (let section = getSection(me.sectionId); section?.parentId; section = getSection(section.parentId)) ids.add(section.parentId);
  return ids;
}

function liveItem(r, me) {
  if (startOf(r) > NOW_DATE || endOf(r) <= NOW_DATE) return null;
  const today = NOW.slice(0, 10);
  const mine = ownGroups(me);
  const items = (r.scheduleItems ?? []).filter((item) => item.date === today);
  const relevant = items.filter((item) => !item.groups?.length || item.groups.some((g) => mine.has(g)));
  const at = (item) => new Date(`${item.date}T${item.time}:00`);
  const currentItem = relevant.findLast((item) => at(item) <= NOW_DATE);
  if (!currentItem) return null;
  const next = items.find((item) => at(item) > at(currentItem));
  return { title: currentItem.title, until: next ? next.time : formatTime(r.end) };
}

function dayName(date, ongoing = false, short = false) {
  const diff = daysAway(date);
  if (ongoing || diff === 0) return 'Heute';
  if (diff === 1) return 'Morgen';
  return (short ? WEEKDAYS_SHORT : WEEKDAYS)[date.getDay()];
}

function heroDay(r) {
  const start = startOf(r);
  const ongoing = start <= NOW_DATE;
  if (!r.multiDay) return `${dayName(start, ongoing)} · ${shortDate(start)}`;
  const end = endOf(r);
  const range = start.getMonth() === end.getMonth() ? `${start.getDate()}.–${shortDate(end)}` : `${shortDate(start)} – ${shortDate(end)}`;
  return `${dayName(start, ongoing, true)} – ${dayName(end, false, true)} · ${range}`;
}

/* Builders */

function stateWord(r, me) {
  if (r.cancelled) return 'fällt aus';
  if (isPast(r)) {
    if (!attendance.length) return null;
    const row = getAttendance(r.id).find((a) => a.memberId === Number(me.id));
    return row ? RESULT_WORDS[row.status] ?? 'anwesend' : 'Anwesenheit nicht erfasst';
  }
  return `Antwort ${ANSWER_WORDS[getPromise(me.id, r.id)?.status ?? 'pending']}`;
}

function ariaLabel(r, me, extra) {
  return [formatDate(r.start), ui.timeRange(r).replace(' – ', ' bis '), r.type !== 'Probe' ? r.type : null, ui.metaText(r) || null, extra, stateWord(r, me)]
    .filter(Boolean).join(', ');
}

function buildCard(r, ctx) {
  const { me } = ctx;
  const past = isPast(r);
  const mode = past ? 'past' : ctx.role === 'leader' ? 'leader' : 'member';
  const card = ui.rehearsalCard(r, { mode, memberId: me.id, href: `#/promises/${r.id}` });
  card.querySelector('.rehearsal-card__link')?.setAttribute('aria-label', ariaLabel(r, me));
  if (past && !r.cancelled) card.querySelector('.rehearsal-card__trail')?.replaceChildren(...[rowSlot(r, me)].filter(Boolean));

  const time = card.querySelector('.rehearsal-card__time');
  time.classList.toggle('rehearsal-card__time--range', Boolean(r.multiDay));
  time.replaceChildren(...rowTimeText(r));
  if (!card.querySelector('.meta-location')) {
    const loc = ui.locationMeta(r);
    const line2 = card.querySelector('.rehearsal-card__line2');
    if (loc && line2) line2.append(loc);
    else if (loc) card.querySelector('.rehearsal-card__line1').after(h('div', { class: 'rehearsal-card__line2' }, loc));
  }
  const line3 = card.querySelector('.rehearsal-card__line3');
  const hints = hintLine(r);
  line3?.querySelector(':scope > .section-meter')?.remove();
  const pills = line3?.querySelector(':scope > .info-pills');
  if (pills && hints) pills.replaceWith(hints);
  else pills?.remove();
  if (line3 && !line3.childElementCount) line3.remove();
  const dl = ui.deadlineInfo(r);
  if (!past && !r.cancelled) {
    const trail = card.querySelector('.rehearsal-card__trail');
    trail.replaceChildren(statusSlot(r, me));
    const count = ctx.role === 'leader' ? voiceCount(r) : null;
    if (count) trail.after(count);
  }
  if (!past && !r.cancelled && dl) card.append(deadlineLine(dl));
  if (r.id === state.selectedId && ui.isWide()) card.classList.add('is-selected');

  if (!r.cancelled && !past && !ui.hasStarted(r)) attachLongPress(card, () => openAnswerSheet(r, me.id, { onChange: changed(r) }));
  attachSelect(card, r);
  return card;
}

function statusSlot(r, me, { onHero = false } = {}) {
  return answerControl(r, me.id, { onChange: changed(r), locked: ui.hasStarted(r), onHero });
}

function rowSlot(r, me) {
  if (r.cancelled) return cancelledSlot();
  if (isPast(r)) return attendance.length ? answerControl(r, me.id, { readOnlyResult: true }) : null;
  return statusSlot(r, me);
}

function buildRow(r, ctx) {
  const { me } = ctx;
  const past = isPast(r);
  const start = startOf(r);
  const row = probenRow(r, {
    href: `#/promises/${r.id}`,
    label: ariaLabel(r, me),
    today: !past && !r.cancelled && (daysAway(start) === 0 || start <= NOW_DATE),
    past,
    deadline: past || r.cancelled ? null : ui.deadlineInfo(r),
    count: ctx.role === 'leader' && !past && !r.cancelled ? voiceCount(r) : null,
    slot: rowSlot(r, me),
    dateLine: false,
    expanded: r.id === state.selectedId && ui.isWide() && !paired(),
    live: ui.isWide() ? ui.liveLine(r, me) : null,
  });
  attachSelect(row, r);
  return row;
}

function rowDetail(r) {
  const { ctx } = current;
  return detailApi?.responsesHref ? rowPanel(r, ctx, detailApi, { onChange: changed(r) }) : h('div');
}

function heroCard(r, ctx) {
  const { me } = ctx;
  const live = liveItem(r, me);

  const meta = [ui.typeBadge(r), ui.groupBadge(r), ui.roleChips(r.roleIds), ui.locationMeta(r, { always: true }), hintLine(r)].filter(Boolean);
  const dl = ui.deadlineInfo(r);
  const count = ctx.role === 'leader' ? voiceCount(r) : null;

  const hero = h('article', {
    class: ['rehearsal-hero', 'proben-hero', r.id === state.selectedId && ui.isWide() && 'is-selected'],
    dataset: { id: r.id },
  },
  h('a', {
    class: 'rehearsal-card__link',
    href: `#/promises/${r.id}`,
    'aria-label': ariaLabel(r, me, live ? `jetzt ${live.title}` : null),
  }),
  h('div', { class: 'proben-hero__top' },
    h('span', { class: 'proben-hero__day' }, heroDay(r)),
    statusSlot(r, me, { onHero: true })),
  h('div', { class: 'proben-hero__time' },
    h('span', { class: 'proben-hero__range' }, rowTimeText(r)),
    count),
  meta.length ? h('div', { class: 'rehearsal-hero__meta' }, meta) : null,
  !ui.hasStarted(r) && dl ? deadlineLine(dl) : null,
  live ? h('div', { class: 'rehearsal-hero__live' },
    h('span', { class: 'live-dot', 'aria-hidden': 'true' }),
    h('span', { class: 'rehearsal-hero__live-label' }, 'Jetzt'),
    h('span', { class: 'rehearsal-hero__live-title' }, live.title),
    h('span', { class: 'rehearsal-hero__live-until' }, `bis ${live.until}`)) : null,
  live ? ui.timeLine(r, { member: me, className: 'time-line--hero' }) : null);

  attachSelect(hero, r);
  if (!ui.hasStarted(r)) attachLongPress(hero, () => openAnswerSheet(r, me.id, { onChange: changed(r) }));
  return hero;
}

function attachLongPress(card, onLongPress) {
  let timer = null;
  let origin = null;
  let fired = false;
  const cancel = () => {
    clearTimeout(timer);
    timer = null;
  };
  card.addEventListener('pointerdown', (event) => {
    if (event.button !== 0 || event.target.closest('button, .rsvp, .section-meter')) return;
    fired = false;
    origin = { x: event.clientX, y: event.clientY };
    timer = setTimeout(() => {
      fired = true;
      haptic('medium');
      onLongPress();
    }, 500);
  });
  card.addEventListener('pointermove', (event) => {
    if (timer && origin && Math.hypot(event.clientX - origin.x, event.clientY - origin.y) > 8) cancel();
  });
  for (const type of ['pointerup', 'pointercancel', 'pointerleave']) card.addEventListener(type, cancel);
  card.addEventListener('contextmenu', (event) => {
    if (fired) event.preventDefault();
  });
  card.addEventListener('click', (event) => {
    if (!fired) return;
    fired = false;
    event.preventDefault();
    event.stopPropagation();
  }, true);
}

function attachSelect(card, r) {
  card.querySelector('.rehearsal-card__link, .proben-row__link')?.addEventListener('click', (event) => {
    if (!ui.isWide() || event.metaKey || event.ctrlKey || event.shiftKey) return;
    event.preventDefault();
    event.stopPropagation();
    toggle(r.id);
  });
}

function dateSep(group) {
  return h('h2', { class: 'date-sep' },
    h('span', {}, group.label),
    group.range ? h('span', { class: 'date-sep__range' }, group.range) : null);
}

function groupSection(group, ctx) {
  if (!ui.isWide()) return h('section', { class: 'proben-group' }, dateSep(group), group.items.map((r) => buildCard(r, ctx)));
  const card = h('div', { class: 'proben-card' });
  for (const r of group.items) {
    const row = buildRow(r, ctx);
    card.append(rowItem(row));
    if (row.classList.contains('is-expanded')) expandRow(row, rowDetail(r), { animate: false });
  }
  return h('section', { class: 'proben-group' }, dateSep(group), card);
}

function openCount(ctx) {
  return lists(ctx.me).upcoming.filter((r) => isDueOpen(r, ctx.me.id)).length;
}

function openChip(ctx) {
  const count = openCount(ctx);
  if (!count && !state.openOnly) return null;
  return ui.chip({
    label: `${count} offen`,
    icon: 'circle-dashed',
    tone: 'open',
    pressed: state.openOnly,
    className: 'proben-open-chip',
    onClick: () => {
      haptic('selection');
      if (state.past) setPeriod('upcoming');
      state.openOnly = !state.openOnly;
      if (current.root?.isConnected) renderPromises(current.root, current.ctx);
    },
  });
}

function setPeriod(value) {
  const { root, ctx } = current;
  if (!root?.isConnected) return;
  const query = { ...ctx.query };
  delete query.rehearsal;
  if (value === 'past') query.past = '1';
  else delete query.past;
  state.past = value === 'past';
  state.selectedId = null;
  state.openOnly = false;
  current.ctx = { ...ctx, query };
  renderPromises(root, current.ctx);
  syncPath();
  if (!ui.isWide()) scrollerOf()?.scrollTo({ top: 0 });
}

function periodSeg({ icons = true } = {}) {
  const seg = ui.segmented([
    { value: 'upcoming', label: 'Anstehend', icon: icons ? 'calendar-clock' : null },
    { value: 'past', label: 'Vergangen', icon: icons ? 'history' : null },
  ], state.past ? 'past' : 'upcoming', setPeriod);
  seg.setAttribute('aria-label', 'Zeitraum');
  return seg;
}

function topBar(ctx) {
  const chip = openChip(ctx);
  return h('div', { class: 'proben-top' }, chip, periodSeg({ icons: !chip }));
}

function haystack(r) {
  return [
    formatDate(r.start), r.type, r.location, r.room, r.groupsText,
    ...(r.tags ?? []), ...(r.infos ?? []).map((i) => i.text), ...(r.scheduleItems ?? []).map((i) => i.title),
  ].filter(Boolean).join(' ').toLowerCase();
}

function matchesQuery(r) {
  const q = state.query.trim().toLowerCase();
  return !q || haystack(r).includes(q);
}

function refill() {
  const { root, ctx } = current;
  const list = root?.querySelector('.proben-list');
  if (!list) return;
  list.replaceChildren();
  buildList(ctx, list);
  refreshIcons(list);
  for (const panel of list.querySelectorAll('.pd-x')) panel.sync?.();
}

function clearSearch() {
  state.query = '';
  const input = current.root?.querySelector('.search-field__input');
  if (input) {
    input.value = '';
    input.dispatchEvent(new Event('input'));
    input.focus();
  } else refill();
}

function toggleSearch() {
  state.search = !state.search;
  if (!state.search) state.query = '';
  if (current.root?.isConnected) renderPromises(current.root, current.ctx);
}

function searchField({ desk = true } = {}) {
  const input = h('input', {
    class: 'search-field__input',
    type: 'search',
    placeholder: 'Suchen',
    'aria-label': 'Proben durchsuchen',
    enterkeyhint: 'search',
    autocomplete: 'off',
    value: state.query,
    oninput: (event) => {
      state.query = event.target.value;
      clear.hidden = !state.query;
      refill();
    },
    onkeydown: (event) => {
      if (event.key !== 'Escape') return;
      event.preventDefault();
      if (desk || state.query) clear.click();
      else toggleSearch();
    },
  });
  const clear = h('button', {
    class: 'search-field__clear',
    type: 'button',
    'aria-label': 'Suche leeren',
    hidden: !state.query,
    onclick: () => {
      state.query = '';
      input.value = '';
      clear.hidden = true;
      refill();
      input.focus();
    },
  }, icon('circle-x'));
  if (desk) return h('label', { class: 'search-field desk-search' }, icon('search', { class: 'search-field__icon' }), input, clear);
  return h('div', { class: 'rehearsals-search' },
    h('label', { class: 'search-field' }, icon('search', { class: 'search-field__icon' }), input, clear),
    h('button', { class: 'button button--plain rehearsals-search__cancel', type: 'button', onclick: toggleSearch }, 'Abbrechen'));
}

function noHits() {
  return ui.emptyState({ icon: 'search-x', title: 'Keine Treffer', tone: 'slate', action: { label: 'Suche leeren', icon: 'circle-x', onClick: clearSearch } });
}

function focusSearch(event) {
  if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey) return;
  if (event.target.closest?.('input, textarea, select, [contenteditable="true"]') || document.querySelector('.sheet-host')) return;
  const input = current.root?.querySelector('.desk-search .search-field__input');
  if (!input) return;
  event.preventDefault();
  input.focus();
  input.select();
}

function toolbar(ctx) {
  return h('div', { class: 'proben-toolbar' },
    h('h1', { class: 'proben-toolbar__title' }, 'Proben'),
    openChip(ctx),
    periodSeg(),
    h('div', { class: 'proben-toolbar__spacer' }),
    searchField(),
    ui.bellButton(ctx.role));
}

function syncOpenChip() {
  const { root, ctx } = current;
  const chip = root?.querySelector('.proben-open-chip');
  if (!chip) return;
  const count = openCount(ctx);
  chip.querySelector('.chip__label').textContent = `${count} offen`;
}

/* Scroll position */

function offsetIn(scroller, el) {
  return el.getBoundingClientRect().top - scroller.getBoundingClientRect().top;
}

function captureAnchor(list) {
  const scroller = scrollerOf();
  if (!scroller || scroller.scrollTop < 1) return { top: true };
  const edge = scroller.getBoundingClientRect().top;
  for (const el of list.querySelectorAll('.rehearsal-hero, .rehearsal-card, .proben-row[data-id]')) {
    if (el.getBoundingClientRect().bottom > edge) return { id: el.dataset.id, offset: offsetIn(scroller, el) };
  }
  return null;
}

function restoreAnchor(list, anchor) {
  const scroller = scrollerOf();
  if (!scroller || !anchor) return false;
  if (anchor.top) {
    if (state.past) return false;
    scroller.scrollTop = 0;
    return true;
  }
  const el = list.querySelector(`:is(.rehearsal-hero, .rehearsal-card, .proben-row)[data-id="${anchor.id}"]`);
  if (!el) return false;
  scroller.scrollTop += offsetIn(scroller, el) - anchor.offset;
  return true;
}

function placeList(list, anchor, { reveal = false } = {}) {
  if (restoreAnchor(list, anchor)) return;
  const selected = reveal && ui.isWide() && state.selectedId
    ? list.querySelector(`.proben-row[data-id="${state.selectedId}"]`)
    : null;
  if (selected) selected.scrollIntoView({ block: 'nearest', behavior: 'auto' });
  else if (anchor) scrollerOf().scrollTop = 0;
}

/* Sticky month headers */

function scrollerOf() {
  return document.getElementById('appContent');
}

function watchSticky(list) {
  stickyWatch?.abort();
  stickyWatch = new AbortController();
  const target = scrollerOf();
  let frame = 0;
  const sync = () => {
    frame = 0;
    for (const sep of list.querySelectorAll('.date-sep')) {
      const stuck = sep.getBoundingClientRect().top - sep.parentElement.getBoundingClientRect().top > 0.5;
      sep.classList.toggle('is-stuck', stuck);
    }
  };
  target?.addEventListener('scroll', () => {
    if (!frame) frame = requestAnimationFrame(sync);
  }, { passive: true, signal: stickyWatch.signal });
  requestAnimationFrame(sync);
}

/* Rendering */

function changed(r) {
  return (promise, info) => {
    setTimeout(() => {
      refreshCard(r);
      syncOpenChip();
    }, info?.undo || ui.isWide() ? 0 : 400);
  };
}

function refreshCard(r) {
  const { root, ctx } = current;
  if (!root?.isConnected) return;
  const old = root.querySelector(`.rehearsal-card[data-id="${r.id}"], .rehearsal-hero[data-id="${r.id}"], .proben-row[data-id="${r.id}"]`);
  if (!old) return;
  const isRow = old.classList.contains('proben-row');
  const focus = focusSpot(old);
  const fresh = isRow ? buildRow(r, ctx) : old.classList.contains('rehearsal-hero') ? heroCard(r, ctx) : buildCard(r, ctx);
  if (!isRow) fresh.classList.add('is-updated');
  for (const name of ['is-current', 'is-expanded']) {
    if (isRow && old.classList.contains(name)) fresh.classList.add(name);
  }
  fresh.querySelector('.proben-row__link')?.setAttribute('aria-expanded', String(fresh.classList.contains('is-expanded')));
  old.replaceWith(fresh);
  refreshIcons(fresh);
  if (focus) fresh.querySelector(focus)?.focus({ preventScroll: true });
  const wrap = isRow ? fresh.parentElement?.querySelector(':scope > .proben-expand') : null;
  if (wrap && fresh.classList.contains('is-expanded')) {
    const inner = wrap.querySelector('.proben-expand__inner');
    const panel = rowDetail(r);
    inner.replaceChildren(panel);
    refreshIcons(inner);
    panel.sync?.();
  }
}

function focusSpot(el) {
  const active = document.activeElement;
  if (!active || !el.contains(active)) return null;
  if (active.matches('.answer-control__btn')) return `.answer-control__btn[data-value="${active.dataset.value}"]`;
  if (active.matches('.answer-lock')) return '.answer-lock';
  if (active.matches('.proben-row__link, .rehearsal-card__link')) return '.proben-row__link, .rehearsal-card__link';
  return null;
}

function moveSelection(list, event) {
  if (!paired() || event.altKey || event.ctrlKey || event.metaKey) return;
  const link = event.target.closest?.('.proben-row__link');
  if (!link) return;
  const links = [...list.querySelectorAll('.proben-row__link')];
  const at = links.indexOf(link);
  const index = { ArrowDown: at + 1, ArrowUp: at - 1, Home: 0, End: links.length - 1 }[event.key];
  const next = links[index];
  if (index === undefined) return;
  event.preventDefault();
  if (!next || next === link) return;
  next.focus();
  next.closest('.proben-row')?.scrollIntoView({ block: 'nearest' });
  toggle(Number(next.closest('.proben-row').dataset.id));
}

function listPath() {
  return state.past ? '/promises?past=1' : '/promises';
}

function selectPath(id) {
  return state.past ? `/promises/${id}?past=1` : `/promises/${id}`;
}

function syncPath() {
  const path = ui.isWide() && state.selectedId ? selectPath(state.selectedId) : listPath();
  history.replaceState(history.state, '', `#${path}`);
}

const paired = () => document.documentElement.hasAttribute('data-pair');

async function toggle(id) {
  const { root } = current;
  if (!root?.isConnected) return;
  haptic('selection');
  if (paired()) {
    state.selectedId = id;
    syncPath();
    import('../shell-aside.js').then((mod) => mod.syncAside(document.getElementById('appContent')));
    return;
  }
  const r = getRehearsal(id);
  const row = root.querySelector(`.proben-row[data-id="${id}"]`);
  if (!r || !row) return;
  if (!detailApi) await detailModule;
  if (!row.isConnected) return;
  const opened = toggleRow(root, row, () => rowDetail(r));
  const other = root.querySelector('.proben-row.is-expanded');
  state.selectedId = opened ? id : other ? Number(other.dataset.id) : null;
  syncPath();
}

function setProbenHeader(role) {
  setHeader({
    title: 'Proben',
    large: true,
    back: false,
    actions: [ui.bellAction(role)],
    accessory: ui.titleTools(
      ui.titleAction({ icon: 'search', label: 'Suchen', key: 'search', pressed: state.search, onClick: toggleSearch }),
      ensembleChip()),
  });
}

function emptyList() {
  return ui.emptyState({
    icon: null,
    title: 'Keine Proben',
    text: 'Sobald die Leitung eine Probe plant, erscheint sie hier.',
    action: { label: 'Kalender-Abo', icon: 'calendar-sync', href: '#/profile/kalender' },
  });
}

function buildList(ctx, list) {
  if (ctx.query.demo === 'empty') {
    list.append(emptyList());
    return;
  }
  const { upcoming, past, hero } = lists(ctx.me);
  const query = state.query.trim();
  const wide = ui.isWide();
  if (!wide) list.append(topBar(ctx));
  if (state.past) {
    const shown = past.filter(matchesQuery);
    if (!shown.length) list.append(query ? noHits() : emptyList());
    for (const group of groupPast([...shown].reverse())) list.append(groupSection(group, ctx));
    return;
  }
  const pool = (state.openOnly && !query ? upcoming.filter((r) => isDueOpen(r, ctx.me.id)) : upcoming).filter(matchesQuery);
  if (!upcoming.length) list.append(emptyList());
  else if (!pool.length && query) list.append(noHits());
  if (wide) {
    for (const group of groupUpcoming(pool)) list.append(groupSection(group, ctx));
    return;
  }
  if (hero && pool.includes(hero)) list.append(heroCard(hero, ctx));
  for (const group of groupUpcoming(pool.filter((r) => r !== hero))) list.append(groupSection(group, ctx));
}

function highlight(root, id, ctx) {
  const target = root.querySelector(`.rehearsal-card[data-id="${id}"], .rehearsal-hero[data-id="${id}"], .proben-row[data-id="${id}"]`);
  if (!target || state.highlighted === ctx.path) return;
  state.highlighted = ctx.path;
  requestAnimationFrame(() => {
    target.scrollIntoView({ block: 'center', behavior: 'auto' });
    target.classList.remove('is-highlighted');
    void target.offsetWidth;
    target.classList.add('is-highlighted');
    clearTimeout(highlightTimer);
    highlightTimer = setTimeout(() => target.classList.remove('is-highlighted'), 2200);
  });
}

export function renderPromises(root, ctx, { selectedId } = {}) {
  const role = ctx.role === 'leader' ? 'leader' : 'member';
  const me = ui.me(role);
  const deepId = Number(ctx.query.rehearsal) || null;
  const deep = deepId ? getRehearsal(deepId) : null;
  const legacyPast = ctx.query.seg === 'past';
  const picked = selectedId ? getRehearsal(selectedId) : null;
  state.past = ctx.query.past === '1' || legacyPast || Boolean(deep && isPast(deep)) || Boolean(picked && isPast(picked));

  current = { root, ctx: { ...ctx, role, me } };
  setProbenHeader(role);

  const wide = ui.isWide();
  rowsMode();

  const { upcoming, past } = lists(me);
  if (selectedId) state.selectedId = Number(selectedId);
  if (deepId) state.selectedId = deepId;
  const pool = state.past ? past : upcoming;
  if (!pool.some((r) => r.id === state.selectedId)) state.selectedId = null;

  root.classList.add('proben');
  root.classList.toggle('proben-desk', wide);

  const list = h('div', { class: ['proben-list', wide && 'proben-rows'] });
  if (wide) list.addEventListener('keydown', (event) => moveSelection(list, event));
  buildList(current.ctx, list);
  const anchor = state.anchor;
  state.anchor = null;

  if (wide) root.replaceChildren(toolbar(current.ctx), list);
  else root.replaceChildren(...[state.search ? searchField({ desk: false }) : null, list].filter(Boolean));
  if (!wide && state.search && !state.query) requestAnimationFrame(() => root.querySelector('.search-field__input')?.focus({ preventScroll: true }));
  refreshIcons(root);
  for (const panel of list.querySelectorAll('.pd-x')) panel.sync?.();
  watchSticky(list);
  if (wide) watchToolbar(root.firstElementChild);
  if (legacyPast && !deepId) syncPath();
  if (deepId) highlight(root, deepId, ctx);
  else setTimeout(() => list.isConnected && placeList(list, anchor, { reveal: Boolean(selectedId) }), 0);
}

function watchToolbar(bar) {
  const scroller = scrollerOf();
  const sync = () => bar.classList.toggle('is-stuck', scroller.scrollTop > 0);
  scroller.addEventListener('scroll', sync, { passive: true, signal: stickyWatch?.signal });
  sync();
}

registerScreen('promises', {
  title: 'Proben',
  tab: '/promises',
  render(root, params, ctx) {
    if (!ui.guard(ctx, ['member', 'leader'])) return;
    if (wideQuery && wideListener) wideQuery.removeEventListener('change', wideListener);
    wideQuery = matchMedia('(min-width: 840px)');
    wideListener = () => {
      if (current.root?.isConnected) renderPromises(current.root, current.ctx);
    };
    wideQuery.addEventListener('change', wideListener);
    document.removeEventListener('keydown', focusSearch);
    document.addEventListener('keydown', focusSearch);
    renderPromises(root, ctx);
  },
  onRetap() {
    if (state.openOnly && current.root?.isConnected) {
      state.openOnly = false;
      renderPromises(current.root, current.ctx);
      return true;
    }
    if (ui.isWide() && state.past && current.root?.isConnected) {
      current.root.querySelector('.proben-toolbar .segmented__btn[data-value="upcoming"]')?.click();
      return true;
    }
    if (!state.past || !current.root?.isConnected) return false;
    setPeriod('upcoming');
    return true;
  },
  onLeave() {
    const list = current.root?.querySelector('.proben-list');
    if (list && !ui.isWide()) state.anchor = captureAnchor(list);
    if (wideQuery && wideListener) wideQuery.removeEventListener('change', wideListener);
    document.removeEventListener('keydown', focusSearch);
    wideQuery = null;
    wideListener = null;
    clearTimeout(highlightTimer);
    stickyWatch?.abort();
    stickyWatch = null;
    state.highlighted = null;
    current = { root: null, ctx: null };
  },
});

/* Shared RSVP controls: Zusagen/Absagen bar, Absage sheet, Antwort ändern, answer control and row anatomy */

import { h, html, icon, toast, haptic, openSheet, renderTabbar, refreshIcons, TABS, store } from './app.js';
import {
  ensemble, promises, getPromise, getAttendance, getLocation, getRole, appliesTo, formatDate, formatTime, NOW, TODAY,
} from './data.js';
import * as ui from './ui.js';
import { seatingChart, answerSeats, attendanceSeats } from './seating.js';

const REASONS = [
  ['Krank', 'thermometer', 'no'],
  ['Beruflich', 'briefcase', 'board'],
  ['Schule/Uni', 'graduation-cap', 'register'],
  ['Klausur/Prüfung', 'notebook-pen', 'register'],
  ['Urlaub', 'plane', 'weekend'],
  ['Familie', 'heart', 'concert'],
  ['Terminkonflikt', 'calendar-x', 'warn'],
];
const MAX_NOTE = 500;
const COUNT_FROM = 400;

export const STATUS_KIND = { attending: 'yes', not_attending: 'no', pending: 'open' };

export function statusPill(kind, { interactive = false, label, onHero = false, compact = false, onClick } = {}) {
  return ui.statusPill(kind, {
    interactive,
    label,
    onHero,
    compact,
    onClick: interactive && onClick
      ? (event) => {
        event.preventDefault();
        event.stopPropagation();
        onClick(event);
      }
      : null,
  });
}

export function refreshBadges() {
  const role = store.get('role');
  if (TABS[role]) renderTabbar(TABS[role]);
}

function ensurePromise(rehearsal, memberId) {
  let promise = getPromise(memberId, rehearsal.id);
  if (!promise) {
    promise = { memberId: Number(memberId), rehearsalId: rehearsal.id, status: 'pending', note: '', respondedAt: null };
    promises.push(promise);
  }
  return promise;
}

function snapshot(promise) {
  return { status: promise.status, note: promise.note ?? '', respondedAt: promise.respondedAt };
}

function dateLabel(rehearsal) {
  return formatDate(rehearsal.start);
}

export function setAnswer(rehearsal, memberId, status, { onChange, note } = {}) {
  const promise = ensurePromise(rehearsal, memberId);
  const prev = snapshot(promise);
  const patch = { status, respondedAt: status === 'pending' ? null : NOW };
  if (note !== undefined) patch.note = note;
  else if (status !== 'not_attending' && prev.status === 'not_attending') patch.note = '';
  if (status === 'pending') patch.note = '';
  Object.assign(promise, patch);
  haptic('light');
  refreshBadges();
  onChange?.(promise, { prev });
  if (prev.status === 'not_attending' && prev.note && note === undefined && status !== 'not_attending') {
    toast(`Grund entfernt: ${prev.note}`, {
      icon: 'quote',
      action: {
        label: 'Rückgängig',
        onClick: () => {
          const now = snapshot(promise);
          Object.assign(promise, prev);
          refreshBadges();
          onChange?.(promise, { prev: now, undo: true });
        },
      },
    });
  }
  return { promise, prev };
}

/* RSVP bar */

export function rsvpBar(rehearsal, memberId, { onChange, size, onHero = false } = {}) {
  const yes = h('button', { class: 'rsvp__btn rsvp__btn--yes', type: 'button' });
  const no = h('button', { class: 'rsvp__btn rsvp__btn--no', type: 'button' });
  const bar = h('div', {
    class: ['rsvp', size === 'lg' && 'rsvp--lg', onHero && 'rsvp--on-hero'],
    role: 'group',
    'aria-label': `Antwort für ${dateLabel(rehearsal)}`,
    dataset: { rehearsal: rehearsal.id },
  }, yes, no);

  const sync = () => {
    const status = getPromise(memberId, rehearsal.id)?.status ?? 'pending';
    const isYes = status === 'attending';
    const isNo = status === 'not_attending';
    yes.setAttribute('aria-pressed', String(isYes));
    no.setAttribute('aria-pressed', String(isNo));
    yes.innerHTML = String(html`${icon(isYes ? 'circle-check' : 'check')}<span class="rsvp__label">${isYes ? 'Zugesagt' : 'Zusagen'}</span>`);
    no.innerHTML = String(html`${icon(isNo ? 'circle-x' : 'x')}<span class="rsvp__label">${isNo ? 'Abgesagt' : 'Absagen'}</span>`);
    refreshIcons(bar);
  };

  const changed = (promise, info) => {
    if (bar.isConnected) sync();
    onChange?.(promise, info);
  };

  yes.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    if (getPromise(memberId, rehearsal.id)?.status === 'attending') return;
    setAnswer(rehearsal, memberId, 'attending', { onChange: changed });
  });

  no.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    openDeclineSheet(rehearsal, memberId, { onDone: changed });
  });

  sync();
  return bar;
}

/* Absage sheet */

export function openDeclineSheet(rehearsal, memberId, { onDone, anchor } = {}) {
  const promise = ensurePromise(rehearsal, memberId);
  const forced = Boolean(ensemble.settings.forceDeclineReason);
  const editing = promise.status === 'not_attending';
  const before = snapshot(promise);
  const date = dateLabel(rehearsal);
  let applied = editing;

  if (!editing && !forced) {
    setAnswer(rehearsal, memberId, 'not_attending', { onChange: onDone, note: '' });
    applied = true;
  }

  const textarea = h('textarea', {
    id: 'declineReason',
    class: 'textarea',
    rows: '2',
    maxlength: String(MAX_NOTE),
  });
  const saved = (editing ? promise.note ?? '' : '').trim();
  let pick = REASONS.map(([label]) => label).find((label) => saved === label || saved.startsWith(`${label} · `)) ?? null;
  textarea.value = pick ? saved.slice(pick.length).replace(/^ · /, '') : saved;
  const count = h('div', { class: 'textarea__count', 'aria-live': 'polite' });

  const chips = REASONS.map(([reason, iconName, tone]) => ui.chip({
    label: reason,
    icon: iconName,
    tone,
    pressed: false,
    onClick: () => {
      haptic('selection');
      pick = pick === reason ? null : reason;
      update();
    },
  }));

  const content = h('div', { class: 'decline-sheet' },
    h('div', { class: 'chip-row decline-sheet__reasons', role: 'group', 'aria-label': 'Gründe' }, chips),
    h('div', { class: 'decline-sheet__field' },
      h('label', { class: 'decline-sheet__label', for: 'declineReason' }, 'Grund'),
      textarea,
      count));

  const reason = () => [pick, textarea.value.trim()].filter(Boolean).join(' · ');

  const finish = (note) => {
    if (editing) {
      if (note === (promise.note ?? '')) return;
      const prev = snapshot(promise);
      Object.assign(promise, { note, respondedAt: NOW });
      refreshBadges();
      onDone?.(promise, { prev });
      toast(html`${icon('circle-check')} Grund gespeichert`);
      return;
    }
    if (applied) {
      Object.assign(promise, { note });
      onDone?.(promise, { prev: before });
    } else {
      setAnswer(rehearsal, memberId, 'not_attending', { onChange: onDone, note });
    }
  };

  let actions;
  if (editing) {
    actions = [
      { label: 'Abbrechen', variant: 'plain' },
      { label: 'Grund speichern', variant: 'primary', onClick: () => finish(reason()) },
    ];
  } else if (forced) {
    actions = [
      { label: 'Abbrechen', variant: 'plain' },
      {
        label: 'Absage senden',
        variant: 'primary',
        onClick: () => {
          if (reason().length < 3) {
            haptic('warning');
            textarea.focus();
            return false;
          }
          return finish(reason());
        },
      },
    ];
  } else {
    actions = [
      { label: 'Ohne Grund', variant: 'plain', onClick: () => finish('') },
      { label: 'Grund speichern', variant: 'primary', onClick: () => finish(reason()) },
    ];
  }

  const sheet = openSheet({
    title: editing ? `Grund · ${date}` : applied ? html`${icon('circle-x')}<span>Abgesagt · ${date}</span>` : `Absage · ${date}`,
    className: 'sheet--decline',
    content,
    actions,
    anchor,
  });

  function update() {
    const over = textarea.value.length >= COUNT_FROM;
    count.hidden = !over;
    count.textContent = over ? `${textarea.value.length}/${MAX_NOTE}` : '';
    for (const chip of chips) {
      const on = chip.textContent.trim() === pick;
      chip.setAttribute('aria-pressed', String(on));
      chip.classList.toggle('is-selected', on);
    }
  }

  textarea.addEventListener('input', update);
  update();
  return sheet;
}

/* Notiz zur Zusage */

export function openNoteSheet(rehearsal, memberId, { onDone, anchor } = {}) {
  const promise = ensurePromise(rehearsal, memberId);
  const textarea = h('textarea', {
    id: 'noteText',
    class: 'textarea',
    rows: '3',
    maxlength: String(MAX_NOTE),
  });
  textarea.value = promise.note ?? '';
  return openSheet({
    title: `Notiz · ${dateLabel(rehearsal)}`,
    className: 'sheet--decline',
    content: h('div', { class: 'decline-sheet' },
      h('div', { class: 'decline-sheet__field' },
        h('label', { class: 'decline-sheet__label', for: 'noteText' }, 'Notiz'),
        textarea)),
    anchor,
    actions: [
      {
        label: 'Speichern',
        variant: 'primary',
        onClick: () => {
          const note = textarea.value.trim();
          if (note === (promise.note ?? '')) return;
          const prev = snapshot(promise);
          promise.note = note;
          onDone?.(promise, { prev });
        },
      },
      { label: 'Abbrechen', variant: 'secondary' },
    ],
  });
}

/* Antwort ändern */

export function openAnswerSheet(rehearsal, memberId, { onChange } = {}) {
  const promise = ensurePromise(rehearsal, memberId);
  const status = promise.status;
  let sheet;

  const bar = (ui.isWide() ? answerControl : rsvpBar)(rehearsal, memberId, {
    size: 'lg',
    onChange: (changed, info) => {
      onChange?.(changed, info);
      if (!info?.undo && changed.status === 'attending') setTimeout(() => sheet?.close(), 220);
    },
  });

  const row = ({ iconName, title, chevron, onSelect, className }) => h('button', {
    class: ['list-row', className],
    type: 'button',
    onclick: () => {
      haptic('light');
      onSelect();
    },
  },
  h('span', { class: 'list-row__lead' }, icon(iconName)),
  h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, title)),
  chevron ? h('span', { class: 'list-row__trail' }, icon('chevron-right', { class: 'list-row__chevron' })) : null);

  const declined = status === 'not_attending';
  const editReason = () => (declined ? openDeclineSheet : openNoteSheet)(rehearsal, memberId, { onDone: onChange });
  const rows = [];
  if (status !== 'pending') {
    rows.push(promise.note
      ? row({ iconName: 'quote', title: promise.note, chevron: true, className: 'answer-sheet__note', onSelect: editReason })
      : row({ iconName: 'plus', title: declined ? 'Grund hinzufügen' : 'Notiz hinzufügen', className: 'list-row--action', onSelect: editReason }));
  }
  if (ensemble.settings.allowAttendanceReset && status !== 'pending') {
    rows.push(row({
      iconName: 'rotate-ccw',
      title: 'Antwort zurücksetzen',
      className: 'list-row--action answer-sheet__reset',
      onSelect: () => {
        sheet.close();
        setAnswer(rehearsal, memberId, 'pending', { onChange });
        toast(html`${icon('rotate-ccw')} Zurückgesetzt · ${dateLabel(rehearsal)}`);
      },
    }));
  }

  sheet = openSheet({
    title: dateLabel(rehearsal),
    className: 'sheet--answer',
    content: h('div', { class: 'answer-sheet' },
      bar,
      rows.length ? h('div', { class: 'list-group' }, rows) : null),
  });
  return sheet;
}

/* Rows: the one rehearsal list, desktop and mobile */

export function rowsMode() {
  document.documentElement.dataset.rows = '1';
  return true;
}

rowsMode();

function dmy(iso) {
  const [y, m, d] = iso.slice(0, 10).split('-');
  return { d, m, y };
}

export function rowDate(r) {
  const a = dmy(r.start);
  if (!r.multiDay) return `${a.d}.${a.m}.${a.y}`;
  const b = dmy(r.end);
  if (a.m !== b.m) return `${a.d}.${a.m}.–${b.d}.${b.m}.${b.y}`;
  return `${a.d}.–${b.d}.${b.m}.${b.y}`;
}

export function weekdayAnchor(r) {
  return ui.weekdayAnchor(r);
}

export function placeText(r) {
  return [r.location, r.room].filter(Boolean).join(' · ');
}

const rowTint = (r) => ui.rowTint(r);

export function dayNumber(r) {
  if (!r.start) return null;
  const from = Number(dmy(r.start).d);
  if (!r.multiDay) return h('span', { class: 'proben-row__dn' }, String(from));
  return h('span', { class: 'proben-row__dn is-range' }, `${from}–${Number(dmy(r.end).d)}`);
}

function ownRoles(r) {
  return (r.roleIds ?? []).filter((id) => !(r.type ?? '').includes(getRole(id?.id ?? id)?.name ?? '\u0000'));
}

export function rowTags(r, { roles = true } = {}) {
  const audience = (r.roleIds ?? []).length > 0;
  const tags = [
    ui.typeBadge(r),
    r.groupsText && !audience ? h('span', { class: ['rx-tag', 'rx-tag--cast', !ui.showsScope(r) && 'is-standard'], title: r.groupsText }, icon('users-round'), h('span', {}, r.groupsText)) : null,
    roles ? ui.roleChips(ownRoles(r), { names: true }) : null,
  ].filter(Boolean);
  return tags.length ? h('span', { class: 'rx-tags' }, tags) : null;
}

export function dueLine(deadline) {
  if (!deadline) return null;
  return h('span', { class: ['rx-due', deadline.due && 'is-due'], title: deadline.title }, icon('clock'), h('span', {}, deadline.text));
}

export function placeMeta(r) {
  const name = placeText(r);
  if (!name) return null;
  const standard = !r.room && r.location === ensemble.defaultLocation;
  return h('span', { class: ['rx-place', standard && 'is-standard'], title: name }, icon('map-pin'), h('span', { class: 'rx-place__text' }, name));
}

function metaCell(className, ...content) {
  return h('div', { class: ['proben-row__cell', className] }, content.filter(Boolean));
}

export function infoBoxes(r, { max = 4, leadLast = false } = {}) {
  const infos = r.infos ?? [];
  if (!infos.length) return null;
  const boxes = infos.slice(0, max).map((info) => h('span', { class: 'proben-row__info rx-emo', title: info.text, role: 'img', 'aria-label': info.text }, info.emoji || icon('info')));
  const more = infos.length > max ? h('span', { class: 'proben-row__info rx-emo proben-row__info--more', title: infos.slice(max).map((i) => i.text).join('\n') }, `+${infos.length - max}`) : null;
  return h('span', { class: 'proben-row__infos' }, leadLast ? [boxes.slice(1), more, boxes[0]] : [boxes, more]);
}

function hintTip(text, label, glyph, more = false) {
  return h('button', {
    class: ['rx-emo', 'rx-emo--tip', more && 'rx-emo--more'],
    type: 'button',
    title: text,
    'aria-label': `Hinweis: ${text}`,
    onclick: (event) => {
      event.preventDefault();
      event.stopPropagation();
      haptic('light');
      toast(label);
    },
  }, glyph);
}

export function hintLine(r, { max = 3 } = {}) {
  const infos = (r.infos ?? []).filter((info) => info.text && !(r.cancelled && /fällt .*aus/i.test(info.text)));
  if (!infos.length) return null;
  const [lead, ...rest] = infos;
  const more = rest.slice(max);
  return h('span', { class: 'rx-hints' },
    h('span', { class: 'rx-hints__lead' },
      lead.emoji ? h('span', { class: 'rx-emo', 'aria-hidden': 'true' }, lead.emoji) : icon('info'),
      h('span', { class: 'rx-hints__text' }, lead.text)),
    rest.slice(0, max).map((info) => hintTip(info.text, `${info.emoji ?? ''} ${info.text}`.trim(), info.emoji || icon('info'))),
    more.length ? hintTip(more.map((info) => info.text).join(', '), more.map((info) => `${info.emoji ?? ''} ${info.text}`.trim()).join(' · '), `+${more.length}`, true) : null);
}

function notesCell(r) {
  const first = (r.infos ?? []).find((info) => info.text);
  return h('div', { class: 'proben-row__cell proben-row__notes' },
    infoBoxes(r, { leadLast: true }),
    first ? h('span', { class: 'proben-row__hint' }, first.text) : null);
}

function rowTime(r) {
  const day = (iso) => (r.multiDay ? h('span', { class: 'proben-row__wdn' }, `${formatDate(iso).split(',')[0]} `) : null);
  return [
    h('span', { class: 'rx-time__from' }, day(r.start), formatTime(r.start)),
    h('span', { class: 'rx-time__to' }, r.multiDay ? ' – ' : '–', day(r.end), formatTime(r.end)),
  ];
}

export function cancelledSlot() {
  return null;
}

function liveFits(r) {
  const plainKind = (!r.type || r.type === 'Probe') && !ui.showsScope(r) && !ownRoles(r).length;
  const plainPlace = !r.room && r.location === ensemble.defaultLocation;
  return plainKind && plainPlace;
}

function todayLead(r) {
  return [h('span', { class: 'proben-row__wd proben-row__wd--today' }, 'Heute'), dayNumber(r)];
}

export function dayTile(r, { today = false } = {}) {
  return today && !r.multiDay ? todayLead(r) : [weekdayAnchor(r), dayNumber(r)];
}

export function rowTimeText(r) {
  return rowTime(r);
}

export function probenRow(r, {
  href, label, lead, dataset, className, today = false, past = false, deadline = null, count = null, slot = null, expanded = false, check = false, live = null, dateLine = true, nav = false,
} = {}) {
  const liveWide = live && liveFits(r);
  const todayTile = !dateLine && today;
  return h('article', {
    class: ['proben-row', className, today && 'is-today', past && 'is-past', r.cancelled && 'is-cancelled', expanded && 'is-expanded', count && 'has-count', nav && 'is-nav'],
    style: rowTint(r),
    dataset: dataset ?? { id: r.id },
  },
  href ? h('a', { class: 'proben-row__link', href, 'aria-label': label ?? null, 'aria-expanded': ui.isWide() && !nav ? String(expanded) : null }) : null,
  h('div', { class: 'proben-row__day' }, lead ?? dayTile(r, { today: todayTile })),
  h('div', { class: 'proben-row__when' },
    dateLine ? h('div', { class: 'proben-row__line1' },
      today ? h('span', { class: 'proben-row__today' }, 'Heute') : null,
      h('span', { class: ['proben-row__date', r.multiDay && 'proben-row__date--range'] }, rowDate(r))) : null,
    h('div', { class: 'proben-row__line2' },
      h('span', { class: 'proben-row__time' }, rowTime(r))),
    deadline ? h('div', { class: 'proben-row__line3' }, dueLine(deadline)) : null),
  h('div', { class: 'proben-row__meta' },
    liveWide ? metaCell('proben-row__livecell', live) : [
      metaCell('proben-row__kind', r.cancelled ? ui.cancelledTag() : rowTags(r)),
      metaCell('proben-row__place', placeMeta(r)),
    ],
    live && !liveWide ? metaCell('proben-row__notes', live) : notesCell(r)),
  count ? h('div', { class: 'proben-row__count' }, count) : null,
  h('div', { class: 'proben-row__slot' }, slot),
  h('div', { class: 'proben-row__chevron', 'aria-hidden': 'true' },
    icon(nav ? 'chevron-right' : 'chevron-down'),
    check ? h('span', { class: 'check proben-row__check' }, icon('check')) : null));
}

export function rowItem(row, { className } = {}) {
  return h('div', { class: ['proben-item', className], dataset: { item: row.dataset.id ?? '' } }, row);
}

export function pickRow(r, { on = true, disabled = false, message = null, label, onToggle, dataset, className, lead, dateText, timeText } = {}) {
  const first = (r.infos ?? []).find((info) => info.text);
  const hint = message ?? (first ? { text: first.text, kind: 'info' } : null);
  const tags = rowTags(r);
  const place = placeMeta(r);
  const boxes = infoBoxes(r);
  return h('article', {
    class: ['proben-row', 'proben-row--pick', className, !on && 'is-off', disabled && 'is-disabled'],
    dataset: dataset ?? null,
  },
  h('button', {
    class: 'proben-row__link',
    type: 'button',
    role: 'checkbox',
    'aria-checked': String(on),
    'aria-label': label ?? null,
    disabled: disabled || null,
    onclick: onToggle ?? null,
  }),
  h('div', { class: 'proben-row__day' }, lead ?? [weekdayAnchor(r), dayNumber(r)]),
  h('div', { class: 'proben-row__when' },
    h('div', { class: 'proben-row__line1' }, h('span', { class: ['proben-row__date', r.multiDay && 'proben-row__date--range'] }, dateText ?? rowDate(r))),
    h('div', { class: 'proben-row__line2' }, timeText === '' ? null : h('span', { class: 'proben-row__time' }, timeText ?? rowTime(r)))),
  h('div', { class: 'proben-row__mid' },
    tags || place ? h('span', { class: 'proben-row__pick-meta' }, tags, place) : null,
    boxes || hint
      ? h('span', { class: 'proben-row__pick-note' },
        boxes,
        hint ? h('span', { class: ['proben-row__hint', `is-${hint.kind}`], title: hint.text }, hint.text) : null)
      : null),
  h('div', { class: 'proben-row__slot' }, h('span', { class: ['check', 'proben-row__check', on && 'is-checked'], 'aria-hidden': 'true' }, icon('check'))));
}

export function noticeBanner({ count, title, text, action, href, label, className, dataset } = {}) {
  return h('div', { class: ['rx-banner', className], dataset: dataset ?? null },
    href ? h('a', { class: 'rx-banner__link', href, 'aria-label': label ?? null }) : null,
    h('span', { class: 'rx-banner__count' }, String(count)),
    h('span', { class: 'rx-banner__body' },
      h('span', { class: 'rx-banner__title' }, title),
      text ? h('span', { class: 'rx-banner__text', title: text }, text) : null),
    action ?? null);
}

export function countSlot({ yes = 0, no = 0, open = 0, value, label } = {}) {
  const total = yes + no + open;
  const text = label ?? `${yes} von ${total} zugesagt, ${no} abgesagt, ${open} offen`;
  const seg = (kind, n) => (n > 0 ? h('span', { class: ['count-slot__seg', `count-slot__seg--${kind}`], style: `flex-grow:${n}` }) : null);
  return h('div', { class: 'count-slot', role: 'img', 'aria-label': text, title: text },
    h('span', { class: 'count-slot__value' }, value ?? `${yes}/${total}`),
    h('span', { class: 'count-slot__bar' },
      total ? [seg('yes', yes), seg('no', no), seg('open', open)] : h('span', { class: 'count-slot__seg count-slot__seg--open', style: 'flex-grow:1' })));
}

export function voiceCount(r) {
  const s = ui.sectionStats(r);
  if (!s) return null;
  const seg = (kind, n) => (n > 0 ? h('span', { class: ['count-slot__seg', `count-slot__seg--${kind}`], style: `flex-grow:${n}` }) : null);
  const text = `${s.name}: ${s.attending} zugesagt, ${s.notAttending} abgesagt, ${s.pending} offen`;
  return h('a', { class: ['voice-count', s.low && 'is-low'], href: s.href, 'aria-label': text, title: text },
    h('span', { class: 'voice-count__line' },
      h('span', { class: 'voice-count__name' }, s.name),
      h('span', { class: 'voice-count__value' }, `${s.attending}/${s.total}`)),
    h('span', { class: 'count-slot__bar' }, [seg('yes', s.attending), seg('no', s.notAttending), seg('open', s.pending)]));
}

/* Expanding a row in place */

const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
const EXPAND_MS = 240;

function motionOk() {
  return !reducedMotion.matches && document.documentElement.dataset.motion !== 'reduced';
}

function cardOf(row) {
  return row.parentElement?.classList.contains('proben-item') ? row.parentElement : null;
}

function panelOf(row) {
  const item = cardOf(row);
  const next = item ? item.querySelector(':scope > .proben-expand') : row.nextElementSibling;
  return next?.classList.contains('proben-expand') ? next : null;
}

function settle(el, height) {
  return new Promise((resolve) => {
    let done = false;
    const finish = (event) => {
      if (done || (event && (event.target !== el || event.propertyName !== 'height'))) return;
      done = true;
      el.removeEventListener('transitionend', finish);
      resolve();
    };
    el.addEventListener('transitionend', finish);
    setTimeout(finish, EXPAND_MS + 80);
    void el.offsetHeight;
    el.style.height = height;
  });
}

export function expandRow(row, content, { animate = true } = {}) {
  const wrap = h('div', { class: 'proben-expand' }, h('div', { class: 'proben-expand__inner' }, content));
  const item = cardOf(row);
  if (item) item.append(wrap);
  else row.after(wrap);
  row.classList.add('is-expanded');
  row.querySelector('.proben-row__link')?.setAttribute('aria-expanded', 'true');
  refreshIcons(wrap);
  content.sync?.();
  if (!animate || !motionOk()) {
    wrap.classList.add('is-open');
    return wrap;
  }
  wrap.style.height = '0px';
  wrap.classList.add('is-entering');
  const target = wrap.firstElementChild.offsetHeight;
  requestAnimationFrame(() => wrap.classList.remove('is-entering'));
  settle(wrap, `${target}px`).then(() => {
    if (!wrap.isConnected) return;
    wrap.style.height = '';
    wrap.classList.add('is-open');
  });
  return wrap;
}

export function collapseRow(row, { animate = true } = {}) {
  row.classList.remove('is-expanded');
  row.querySelector('.proben-row__link')?.setAttribute('aria-expanded', 'false');
  const wrap = panelOf(row);
  if (!wrap) return Promise.resolve();
  return collapseAway(wrap, { animate });
}

export function collapseAway(el, { animate = true } = {}) {
  if (!animate || !motionOk()) {
    el.remove();
    return Promise.resolve();
  }
  el.classList.remove('is-open');
  el.style.height = `${el.offsetHeight}px`;
  el.classList.add('is-closing');
  return settle(el, '0px').then(() => el.remove());
}

let anchorTimer = 0;

function closeOthers(host, row) {
  const list = row.closest('.proben-list, .rehearsals-list') ?? host;
  const others = [...list.querySelectorAll('.proben-row.is-expanded')].filter((other) => other !== row);
  if (!others.length) return;
  const scroller = document.getElementById('appContent');
  const top = row.getBoundingClientRect().top;
  const above = others
    .filter((other) => other.getBoundingClientRect().top < top)
    .reduce((sum, other) => sum + (panelOf(other)?.offsetHeight ?? 0), 0);
  const instant = !scroller || scroller.scrollTop >= above;
  for (const other of others) collapseRow(other, { animate: !instant });
  if (!instant) return;
  scroller.style.overflowAnchor = 'none';
  const shift = row.getBoundingClientRect().top - top;
  if (shift) scroller.scrollTop += shift;
  clearTimeout(anchorTimer);
  anchorTimer = setTimeout(() => { scroller.style.overflowAnchor = ''; }, EXPAND_MS + 160);
}

export function toggleRow(host, row, build) {
  if (row.classList.contains('is-expanded')) {
    collapseRow(row);
    return false;
  }
  closeOthers(host, row);
  expandRow(row, build());
  return true;
}

/* Answer control */

const RESULTS = {
  present: ['check', 'Anwesend', 'yes'],
  late: ['check', 'Verspätet', 'yes'],
  absent: ['x', 'Fehlt', 'no'],
  excused: ['x', 'Entschuldigt', 'none'],
};

const LOCKED = {
  attending: ['check', 'Zugesagt', 'yes'],
  not_attending: ['x', 'Abgesagt', 'no'],
  pending: ['circle-dashed', 'Offen', 'open'],
};
const LOCK_REASON = 'Nach Probenbeginn kann die Antwort nicht mehr geändert werden';

function resultMark([name, label, tone]) {
  return h('span', { class: ['answer-result', `answer-result--${tone}`], role: 'img', 'aria-label': label, title: label },
    name ? icon(name) : h('span', { class: 'answer-result__dash', 'aria-hidden': 'true' }),
    h('span', { class: 'answer-result__text' }, label));
}

function answerResult(rehearsal, memberId) {
  const row = getAttendance(rehearsal.id).find((a) => a.memberId === Number(memberId));
  return resultMark(row ? RESULTS[row.status] ?? RESULTS.present : [null, 'Nicht erfasst', 'none']);
}

export function answerState(rehearsal, memberId) {
  return answerControl(rehearsal, memberId, { locked: true });
}

function lockedAnswer(rehearsal, status, onHero) {
  const [name, label, tone] = LOCKED[status] ?? LOCKED.pending;
  return h('button', {
    class: ['answer-control', 'answer-lock', onHero && 'answer-control--on-hero'],
    type: 'button',
    'aria-label': `Antwort, ${dateLabel(rehearsal).replace(',', '')}: ${label}, gesperrt`,
    title: `${label}. ${LOCK_REASON}`,
    dataset: { rehearsal: rehearsal.id, value: status },
    onclick: (event) => {
      event.preventDefault();
      event.stopPropagation();
      haptic('light');
      toast(LOCK_REASON, { icon: 'lock' });
    },
  },
  h('span', { class: ['answer-lock__mark', `answer-lock__mark--${tone}`], 'aria-hidden': 'true' }, icon(name)),
  h('span', { class: 'answer-lock__lock', 'aria-hidden': 'true' }, icon('lock')));
}

export function answerControl(rehearsal, memberId, { onChange, readOnlyResult = false, locked = false, onHero = false } = {}) {
  if (readOnlyResult) return answerResult(rehearsal, memberId);
  const status = getPromise(memberId, rehearsal.id)?.status ?? 'pending';
  if (locked) return lockedAnswer(rehearsal, status, onHero);
  const segment = (value, name, title) => h('button', {
    class: ['answer-control__btn', `answer-control__btn--${value}`],
    type: 'button',
    role: 'radio',
    title,
    'aria-label': title,
    dataset: { value, key: `answer-${rehearsal.id}-${value}` },
  }, icon(name));
  const yes = segment('yes', 'check', 'Zusagen');
  const no = segment('no', 'x', 'Absagen');
  const el = h('div', {
    class: ['answer-control', onHero && 'answer-control--on-hero'],
    role: 'radiogroup',
    'aria-label': `Antwort, ${dateLabel(rehearsal).replace(',', '')}`,
    dataset: { rehearsal: rehearsal.id },
  }, yes, no);

  const sync = () => {
    const status = getPromise(memberId, rehearsal.id)?.status ?? 'pending';
    yes.setAttribute('aria-checked', String(status === 'attending'));
    no.setAttribute('aria-checked', String(status === 'not_attending'));
    el.dataset.value = status;
  };

  const changed = (promise, info) => {
    if (el.isConnected) sync();
    onChange?.(promise, info);
  };

  const stop = (event) => {
    event.preventDefault();
    event.stopPropagation();
  };

  yes.addEventListener('click', (event) => {
    stop(event);
    if (getPromise(memberId, rehearsal.id)?.status === 'attending') return;
    setAnswer(rehearsal, memberId, 'attending', { onChange: changed });
  });

  no.addEventListener('click', (event) => {
    stop(event);
    openDeclineSheet(rehearsal, memberId, { onDone: changed, anchor: no });
  });

  el.addEventListener('click', stop);
  sync();
  return el;
}

/* Expanded row panel */

function ownUser(ctx) {
  const user = ui.me(ctx.role);
  return user?.canAttend ? user : null;
}

function shareUrl(r) {
  return `${location.origin}${location.pathname}#/promises/${r.id}`;
}

function copyLink(r) {
  navigator.clipboard?.writeText(shareUrl(r)).catch(() => {});
  haptic('light');
  toast(html`${icon('link')} Link kopiert`);
}

export function shareLink(r) {
  if (!navigator.share) {
    copyLink(r);
    return;
  }
  navigator.share({ title: `${r.type} · ${formatDate(r.date)}`, url: shareUrl(r) }).catch((error) => {
    if (error?.name !== 'AbortError') copyLink(r);
  });
}

/* Action bar */

function barIcon(label, iconName, { href, onClick, danger = false } = {}) {
  return h(href ? 'a' : 'button', {
    class: ['act-bar__icon', danger && 'is-danger'],
    type: href ? null : 'button',
    href: href ? `#${href}` : null,
    'aria-label': label,
    'data-tip': label,
    onclick: onClick ?? null,
  }, icon(iconName));
}

function editorAction(r, ctx, label) {
  haptic('light');
  import('./screens/rehearsal-edit.js').then((mod) => {
    const st = { r, root: h('div'), ctx, embedded: true, isNew: false, state: null, detail: true };
    const sheet = mod.openRehearsalMenu?.(r, { editor: st });
    const host = sheet?.el.closest('.sheet-host');
    if (!host) return;
    host.style.display = 'none';
    const item = [...sheet.el.querySelectorAll('.list-row')].find((b) => b.textContent.trim() === label);
    if (item) item.click();
    else sheet.close();
  }).catch(() => {});
}

function primaryButton(label, iconName, { href, onClick, disabled = false } = {}) {
  return h(href ? 'a' : 'button', {
    class: ['button', 'button--sm', disabled ? 'button--plain' : 'button--primary', 'act-bar__primary'],
    type: href ? null : 'button',
    href: href ? `#${href}` : null,
    disabled: disabled || null,
    onclick: onClick ?? null,
  },
  icon(iconName),
  h('span', { class: 'button__label' }, label));
}

function primaryAction(r, ctx, api, redraw) {
  if (r.cancelled || !['leader', 'conductor'].includes(ctx.role)) return null;
  if (r.date <= TODAY) {
    if (getAttendance(r.id).length && !r.isToday) return null;
    return primaryButton(r.isToday && getAttendance(r.id).length ? 'Anwesenheit ansehen' : 'Anwesenheit erfassen', 'clipboard-pen', { href: api.subPath(r, ctx, 'attendance') });
  }
  if (ui.hasStarted(r)) return null;
  const scope = api.scopeFor(ctx.role);
  const open = api.eligibleMembers(r, scope).filter((m) => api.statusOf(m, r) === 'pending').map((m) => m.id);
  if (!open.length) return null;
  const todo = open.filter((id) => !api.isReminded(r, id));
  const scopeLabel = scope?.[0] ? api.sectionName(scope[0]) : r.groupsText;
  return todo.length
    ? primaryButton('Erinnern', 'bell-ring', { onClick: () => api.openRemind(r, todo, { scopeLabel, onSent: redraw }) })
    : primaryButton('Erinnert', 'bell-check', { disabled: true });
}

export function actionBar(r, ctx, api, { editPath, redraw } = {}) {
  const conductor = ctx.role === 'conductor';
  const primary = primaryAction(r, ctx, api, redraw);
  const tools = [
    conductor ? barIcon('Bearbeiten', 'pencil', { href: editPath ?? `/rehearsals/${r.id}` }) : null,
    conductor ? barIcon('Duplizieren', 'copy', { onClick: () => editorAction(r, ctx, 'Duplizieren') }) : null,
    barIcon('Teilen', 'share-2', { onClick: () => shareLink(r) }),
  ].filter(Boolean);
  const danger = conductor ? [
    r.isPast && !r.isToday ? null : r.cancelled
      ? barIcon('Absage zurücknehmen', 'rotate-ccw', { onClick: () => editorAction(r, ctx, 'Absage zurücknehmen') })
      : barIcon('Probe absagen', 'calendar-x', { danger: true, onClick: () => editorAction(r, ctx, 'Probe absagen') }),
    barIcon('Probe löschen', 'trash-2', { danger: true, onClick: () => editorAction(r, ctx, 'Probe löschen') }),
  ].filter(Boolean) : [];
  const solo = !primary && !danger.length && tools.length === 1;
  return h('div', { class: ['act-bar', solo && 'is-solo'], role: 'toolbar', 'aria-label': 'Aktionen' },
    primary,
    h('div', { class: 'act-bar__tools' }, tools),
    danger.length ? h('div', { class: 'act-bar__danger' }, danger) : null);
}

/* Facts */

function fact(lead, ...content) {
  return h('div', { class: 'rx-fact' },
    h('span', { class: 'rx-fact__lead' }, lead),
    h('div', { class: 'rx-fact__body' }, content.filter(Boolean)));
}

function shownIn(row) {
  const none = { hint: null, cast: false };
  if (!row?.isConnected) return none;
  const visible = (el) => Boolean(el) && el.getClientRects().length > 0;
  const hint = row.querySelector('.proben-row__notes .proben-row__hint');
  const full = visible(hint) && hint.scrollWidth <= hint.clientWidth + 1 && hint.scrollHeight <= hint.clientHeight + 1;
  return {
    hint: full ? hint.textContent : null,
    cast: visible(row.querySelector('.rx-tag--cast')) || visible(row.querySelector('.proben-row__kind .role-chips')),
  };
}

export function mapHref(r, address) {
  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${r.location}, ${address}`)}`;
}

function placeFact(r) {
  const address = getLocation(r.location)?.address ?? null;
  const standard = !r.room && r.location === ensemble.defaultLocation;
  if (!address) return standard && r.location ? fact(icon('map-pin'), h('span', {}, r.location)) : null;
  return fact(icon('map-pin'), h('a', {
    class: 'rx-fact__link',
    href: mapHref(r, address),
    target: '_blank',
    rel: 'noopener',
    'aria-label': `${r.location}, ${address}, Karte öffnen`,
  }, standard ? `${r.location}, ${address}` : address, h('span', { class: 'rx-fact__go' }, icon('arrow-up-right'))));
}

function castFact(r, shown) {
  if (shown.cast || r.cancelled) return null;
  const roles = ownRoles(r);
  if (roles.length) return fact(icon('users-round'), ui.roleChips(roles, { names: true }));
  return ui.showsScope(r) && r.groupsText ? fact(icon('users-round'), h('span', {}, r.groupsText)) : null;
}

function panelNote(r, ctx, onChange) {
  const user = ownUser(ctx);
  if (!user || r.cancelled || !appliesTo(user, r)) return null;
  const promise = getPromise(user.id, r.id);
  const note = promise?.note?.trim();
  const status = promise?.status ?? 'pending';
  if (ui.hasStarted(r)) return note ? fact(icon('quote'), h('span', { class: 'rx-fact__note' }, note)) : null;
  if (status === 'pending') return null;
  const declined = status === 'not_attending';
  const edit = (event) => (declined ? openDeclineSheet : openNoteSheet)(r, user.id, { onDone: onChange, anchor: event.currentTarget });
  return fact(icon('quote'),
    note ? h('span', { class: 'rx-fact__note' }, note) : null,
    h('span', { class: 'rx-fact__actions' },
      h('button', { class: 'rx-fact__action', type: 'button', onclick: edit }, note ? 'Ändern' : declined ? 'Grund angeben' : 'Notiz hinzufügen'),
      ensemble.settings.allowAttendanceReset
        ? h('button', { class: 'rx-fact__action', type: 'button', onclick: () => resetAnswer(r, user.id, { onChange }) }, 'Antwort zurücksetzen')
        : null));
}

export function resetAnswer(rehearsal, memberId, { onChange } = {}) {
  const { prev } = setAnswer(rehearsal, memberId, 'pending', { onChange });
  toast(html`${icon('rotate-ccw')} Antwort zurückgesetzt`, {
    action: {
      label: 'Rückgängig',
      onClick: () => {
        const promise = ensurePromise(rehearsal, memberId);
        const now = snapshot(promise);
        Object.assign(promise, prev);
        refreshBadges();
        onChange?.(promise, { prev: now, undo: true });
      },
    },
  });
}

export function hintItems(r, { skip = null } = {}) {
  const infos = (r.infos ?? []).filter((info) => !(r.cancelled && /fällt .*aus/i.test(info.text)) && info.text !== skip);
  return infos.map((info) => fact(
    info.emoji ? h('span', { class: 'rx-emo', 'aria-hidden': 'true' }, info.emoji) : icon('info'),
    h('span', { class: 'rx-fact__hint' }, info.text),
  ));
}

function panelFacts(r, ctx, shown, onChange, plan) {
  const user = ownUser(ctx);
  const items = [
    r.cancelled && r.cancelReason ? fact(icon('ban'), h('span', {}, r.cancelReason)) : null,
    user && !r.cancelled && !appliesTo(user, r) ? fact(icon('user-x'), h('span', {}, 'Nicht betroffen')) : null,
    plan ? null : panelNote(r, ctx, onChange),
    ...hintItems(r, { skip: shown.hint }),
    castFact(r, shown),
    placeFact(r),
  ].filter(Boolean);
  return items.length ? h('section', { class: 'pd-x__facts' }, items) : null;
}

/* Ablauf */

function agendaDays(r, api) {
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
  const line = (item) => {
    const groups = (item.groups ?? []).map(api.sectionName).join(', ');
    return { title: item.title, groups: groups && !/^Tutti\b/.test(groups) && groups !== r.groupsText ? groups : null };
  };
  let index = 0;
  return [...byDay].map(([date, slots]) => ({
    date,
    entries: [...slots].map(([time, items]) => {
      const i = index++;
      const state = (r.isPast && !r.isToday) || (nowIndex > -1 && i < nowIndex) ? 'done' : i === nowIndex ? 'now' : null;
      return { time, title: items.map(line), state };
    }),
  }));
}

function panelAgenda(r, api) {
  if (r.cancelled) return null;
  if (!r.scheduleItems?.length) {
    return h('section', { class: 'pd-x__agenda' },
      h('h3', { class: 'pd-x__title' }, 'Ablauf'),
      h('p', { class: 'pd-x__none' }, r.isPast && !r.isToday ? 'Kein Ablauf' : 'Noch kein Ablauf'));
  }
  const days = agendaDays(r, api);
  const multi = days.length > 1;
  const lists = days.map(({ date, entries }) => {
    const list = ui.agenda(entries);
    for (const badge of list.querySelectorAll('.badge, .rx-tag--cast')) badge.replaceWith(h('span', { class: 'agenda__groups' }, badge.textContent.trim()));
    return h('div', { class: 'pd-x__day' }, multi ? h('h4', { class: 'pd-x__day-h' }, formatDate(date)) : null, list);
  });
  return h('section', { class: ['pd-x__agenda', multi && 'pd-x__agenda--days'] },
    h('h3', { class: 'pd-x__title' }, 'Ablauf'),
    h('div', { class: 'pd-x__days' }, lists));
}

/* Rückmeldungen */

const VOICE_MARKS = {
  attending: ['yes', 'check', 'Zugesagt'],
  not_attending: ['no', 'x', 'Abgesagt'],
  pending: ['open', 'minus', 'Offen'],
};

export function answerLegend(counts, { open = true } = {}) {
  const legend = ui.triadLegend(counts);
  legend.querySelector('.triad-legend__item--yes')?.remove();
  if (!open) legend.querySelector('.triad-legend__item--open')?.remove();
  return legend;
}

function panelResponses(r, ctx, api, { chipScope = false } = {}) {
  if (!['leader', 'conductor'].includes(ctx.role) || r.cancelled) return null;
  const scope = api.scopeFor(ctx.role);
  const list = api.eligibleMembers(r, scope);
  if (!list.length) return null;
  const scopeId = scope?.[0] ?? null;
  const scopeName = scopeId ? api.sectionName(scopeId) : null;
  const own = ui.me(ctx.role);
  const held = r.isPast && !r.isToday && getAttendance(r.id).length > 0;
  const seats = held ? attendanceSeats(r, { scope: scopeId }) : answerSeats(r, { scope: scopeId });
  const mine = seats.filter((seat) => seat.state !== 'ghost');
  const total = held
    ? { attending: mine.filter((x) => x.state === 'yes').length, notAttending: mine.filter((x) => x.state === 'no').length, pending: mine.filter((x) => x.state === 'open').length }
    : api.countsFor(list, r);
  const chart = seatingChart(seats, {
    size: 'medium',
    kind: held ? 'attendance' : 'answers',
    emphasis: scopeId,
    ownId: scopeId ? own?.id : null,
    sectionHref: scopeId || held ? null : (sid) => api.responsesHref(r, ctx, sid, 'all'),
  });
  chart.classList.add('pd-x__seats');
  const legend = chart.querySelector('[class*="legend"]') ? null : answerLegend(total, { open: held ? total.pending > 0 : !chipScope });
  const title = held ? 'Anwesenheit' : 'Rückmeldungen';
  const head = h('a', {
    class: 'pd-x__head-link',
    href: held ? `#${api.subPath(r, ctx, 'attendance')}` : api.responsesHref(r, ctx, scopeId, 'all'),
    'aria-label': `${title}${scopeName ? ` ${scopeName}` : ''}: ${total.attending} ${held ? 'anwesend' : 'zugesagt'}, ${total.notAttending} ${held ? 'gefehlt' : 'abgesagt'}, ${total.pending} ${held ? 'nicht erfasst' : 'offen'}`,
  },
  h('h3', { class: 'pd-x__title' }, title),
  scopeName ? h('span', { class: 'pd-x__scope' }, scopeName) : null,
  legend,
  icon('chevron-right', { class: 'pd-x__chevron' }));
  const names = scopeId && !held ? h('div', { class: 'pd-x__voice' },
    list.map((m) => {
      const [tone, glyph, text] = VOICE_MARKS[api.statusOf(m, r)] ?? VOICE_MARKS.pending;
      return h('a', { class: 'pd-x__voice-row', href: `#${api.subPath(r, ctx, `members/${m.id}`)}` },
        h('span', { class: 'pd-x__voice-name' }, m.displayName),
        h('span', { class: ['pd-x__voice-mark', `pd-x__voice-mark--${tone}`], role: 'img', 'aria-label': text, title: text }, icon(glyph)));
    })) : null;
  return h('section', { class: 'pd-x__resp' }, head, chart, names);
}

function listRowOf(r, el) {
  const item = el.closest('.proben-item');
  if (item && !item.classList.contains('is-pane')) return item.querySelector(':scope > .proben-row');
  return document.querySelector(`.proben-desk :is(.proben-list, .rehearsals-list) .proben-row[data-id="${r.id}"]`);
}

export function rowPanel(r, ctx, api, { editPath, onChange, plan = false, openPath = null, inBanner = false } = {}) {
  const edit = editPath ?? `/rehearsals?${r.isPast && !r.isToday ? 'seg=past&' : ''}edit=${r.id}`;
  const el = h('div', { class: ['pd-x', plan && 'pd-x--plan'] });
  const paint = () => {
    const redraw = () => el.isConnected && paint();
    const changed = (promise, info) => {
      redraw();
      onChange?.(promise, info);
    };
    const row = listRowOf(r, el);
    if (!row?.isConnected && !el.dataset.later) {
      el.dataset.later = '1';
      requestAnimationFrame(() => el.isConnected && paint());
    }
    const shown = shownIn(row);
    if (row?.isConnected && !el.closest('.is-pane')) row.dataset.hint = shown.hint ? 'row' : 'panel';
    const agenda = panelAgenda(r, api);
    const facts = panelFacts(r, ctx, shown, changed, plan);
    const answers = plan ? null : panelResponses(r, ctx, api, { chipScope: inBanner });
    const actions = plan
      ? h('div', { class: 'act-bar' },
        primaryButton('Zur Probe', 'arrow-right', { href: openPath }),
        h('div', { class: 'act-bar__tools' }, barIcon('Teilen', 'share-2', { onClick: () => shareLink(r) })))
      : actionBar(r, ctx, api, { editPath: edit, redraw });
    const cols = [agenda, facts, answers].filter(Boolean);
    el.replaceChildren(
      actions,
      h('div', { class: ['pd-x__grid', `pd-x__grid--${cols.length}`, answers && 'has-answers', actions.classList.contains('is-solo') && 'has-solo'] }, cols));
    refreshIcons(el);
  };
  el.sync = paint;
  paint();
  return el;
}

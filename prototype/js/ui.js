/* Shared UI builders and data helpers for all screens */

import { h, html, icon, toast, haptic, openSheet, navigate, refreshIcons, TABS } from './app.js';
import {
  NOW, ensemble, users, members, sections, rehearsals, promises, attendance, notifications, colors,
  formatDate, formatTime, weekdayShort, getMember, getSection, getRole, getAttendance, appliesTo,
} from './data.js';

const DAY_MS = 24 * 60 * 60 * 1000;
const NEUTRAL_COLOR = '#e5e7eb';
const wideQuery = matchMedia('(min-width: 840px)');

/* People and stats */

export function me(role) {
  return users[role] ?? users.member;
}

export function now() {
  return new Date(NOW);
}

function dayStart(value) {
  const d = new Date(value);
  return new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
}

function daysFromNow(value) {
  return Math.round((dayStart(value) - dayStart(NOW)) / DAY_MS);
}

export function stats(rehearsalId, memberIds) {
  const id = Number(rehearsalId);
  const filter = memberIds ? new Set(memberIds.map(Number)) : null;
  const out = { attending: 0, notAttending: 0, pending: 0, total: 0 };
  for (const p of promises) {
    if (p.rehearsalId !== id || (filter && !filter.has(p.memberId))) continue;
    out.total += 1;
    if (p.status === 'attending') out.attending += 1;
    else if (p.status === 'not_attending') out.notAttending += 1;
    else out.pending += 1;
  }
  return out;
}

export const castIds = (r, sectionId) => members
  .filter((m) => appliesTo(m, r) && (!sectionId || m.sectionId === sectionId))
  .map((m) => m.id);

export function castStats(r, sectionId) {
  const ids = castIds(r, sectionId);
  const s = stats(r.id, ids);
  const answered = new Set(promises.filter((p) => p.rehearsalId === r.id).map((p) => p.memberId));
  const missing = ids.filter((id) => !answered.has(id)).length;
  return { ...s, pending: s.pending + missing, total: s.total + missing };
}

export function openIds(r, sectionIds) {
  const done = new Set(promises.filter((p) => p.rehearsalId === r.id && p.status !== 'pending').map((p) => p.memberId));
  const scope = sectionIds ? new Set(sectionIds) : null;
  return members
    .filter((m) => appliesTo(m, r) && (!scope || scope.has(m.sectionId)) && !done.has(m.id))
    .map((m) => m.id);
}

const CRITICAL_RATE = 0.65;
const CRITICAL_ANSWERED = 0.5;
const CRITICAL_DAYS = 21;
const CRITICAL_MAX = 3;

export function criticalSections(r) {
  if (!ensemble.settings.showRehearsalInsights || r.cancelled || r.draft || r.start <= NOW) return [];
  if (daysFromNow(r.start) > CRITICAL_DAYS) return [];
  return sections
    .filter((sec) => sec.kind === 'instrument')
    .map((sec) => ({ sec, s: castStats(r, sec.id) }))
    .filter(({ s }) => s.total >= 2 && s.attending / s.total < CRITICAL_RATE && (s.attending + s.notAttending) / s.total >= CRITICAL_ANSWERED)
    .map(({ sec, s }) => ({ id: sec.id, name: sec.name, rate: Math.round((100 * s.attending) / s.total), pending: s.pending }))
    .sort((a, b) => a.rate - b.rate)
    .slice(0, CRITICAL_MAX);
}

export function remindTarget(r) {
  const low = criticalSections(r);
  const weak = new Set(low.map((c) => c.id));
  const ids = openIds(r);
  const first = ids.filter((id) => weak.has(getMember(id)?.sectionId));
  return { low, ids: [...first, ...ids.filter((id) => !first.includes(id))], label: null };
}

export function hasStarted(r) {
  return r.start <= NOW;
}

export function isDue(r) {
  const t = now().getTime();
  const start = new Date(r.start).getTime();
  return start > t && start <= t + 28 * DAY_MS;
}

export function answerOpen(r, memberId) {
  if (r.cancelled || hasStarted(r)) return false;
  return promises.find((p) => p.memberId === Number(memberId) && p.rehearsalId === r.id)?.status === 'pending';
}

export function openCount(memberId) {
  return rehearsals.filter((r) => isDue(r) && answerOpen(r, memberId)).length;
}

export function undocumentedRehearsals(role) {
  const t = now().getTime();
  const from = t - 60 * DAY_MS;
  const leaderSection = role === 'leader' ? users.leader.sectionId : null;
  if (role === 'member') return [];
  return rehearsals.filter((r) => {
    const start = new Date(r.start).getTime();
    if (r.cancelled || start > t || start < from) return false;
    if (leaderSection && !r.instrumentIds?.includes(leaderSection)) return false;
    const invited = promises.filter((p) => p.rehearsalId === r.id && (!leaderSection || getMember(p.memberId)?.sectionId === leaderSection));
    if (!invited.length) return !attendance.some((a) => a.rehearsalId === r.id);
    return invited.some((p) => !attendance.some((a) => a.rehearsalId === r.id && a.memberId === p.memberId));
  }).sort((a, b) => b.start.localeCompare(a.start));
}

export function undocumentedCount(role) {
  return undocumentedRehearsals(role).length;
}

export function isWide() {
  return wideQuery.matches;
}

export function guard(ctx, roles) {
  if (roles.includes(ctx.role)) return true;
  const tabs = TABS[ctx.role] ?? TABS.member;
  if (ctx.tab && ctx.tab !== ctx.path?.split('?')[0] && tabs.some((t) => t.route === ctx.tab)) {
    navigate(ctx.tab, { replace: true, direction: 'none' });
    return false;
  }
  const first = tabs[0].route;
  navigate(first, { replace: true, direction: 'none' });
  toast(html`${icon('lock')} Kein Zugriff`);
  return false;
}

export function ensembleLabel() {
  return ensemble.type ?? 'Ensemble';
}

/* Text helpers */

function weekday(iso) {
  return formatDate(iso).split(',')[0];
}

export function timeRange(r) {
  if (r.multiDay) {
    return `${weekday(r.start)} ${formatTime(r.start)} – ${weekday(r.end)} ${formatTime(r.end)}`;
  }
  return `${formatTime(r.start)} – ${formatTime(r.end)}`;
}

export function relDay(r) {
  const diff = daysFromNow(r.start ?? r);
  if (diff === 0) return 'Heute';
  if (diff === 1) return 'Morgen';
  if (diff > 1 && diff < 7) return `in ${diff} Tagen`;
  return formatDate(r.start ?? r);
}

export function showsScope(r) {
  return Boolean(r.groupsText) && r.groupsText !== ensemble.defaultScope;
}

export function metaText(r) {
  const parts = [];
  if (showsScope(r)) parts.push(r.groupsText);
  if (r.location && r.location !== ensemble.defaultLocation) parts.push(r.location);
  return parts.join(' · ');
}

function joinNames(names) {
  if (names.length <= 1) return names.join('');
  return `${names.slice(0, -1).join(', ')} und ${names.at(-1)}`;
}

export function groupsSentence(instrumentIds = []) {
  const chosen = new Set(instrumentIds);
  const tutti = getSection('tutti');
  const all = tutti?.instrumentIds ?? [];
  if (all.length && all.every((id) => chosen.has(id))) return 'Tutti';
  const top = sections.filter((s) => s.parentId === 'tutti');
  const leafs = (s) => (s.instrumentIds?.length ? s.instrumentIds : [s.id]);
  const full = top.filter((s) => leafs(s).every((id) => chosen.has(id)));
  const partial = top.filter((s) => !full.includes(s) && leafs(s).some((id) => chosen.has(id)));
  const missing = top.filter((s) => !leafs(s).some((id) => chosen.has(id)));
  if (partial.length === 0 && missing.length === 1 && full.length === top.length - 1) {
    return `Tutti ohne ${missing[0].name}`;
  }
  if (partial.length === 0 && full.length) return joinNames(full.map((s) => s.name));
  const names = sections
    .filter((s) => chosen.has(s.id) && (s.kind === 'instrument' || !s.childIds?.length))
    .map((s) => s.name);
  return joinNames(names);
}

/* Tones: colour tokens for tiles, chips and section headers */

export const TONES = {
  blue: 'var(--brand-500)',
  violet: 'var(--violet-500)',
  rose: 'var(--rose-600)',
  teal: 'var(--teal-500)',
  amber: 'var(--amber-500)',
  green: 'var(--green-500)',
  red: 'var(--red-500)',
  indigo: 'var(--indigo-500)',
  pink: 'var(--pink-500)',
  slate: 'var(--slate-500)',
};

function toneColor(tone) {
  return TONES[tone] ?? tone ?? TONES.blue;
}

/* Registers and voices */

const REGISTER_IDS = ['Streicher', 'Holzbläser', 'Blechbläser', 'Schlagwerk', 'Harfe'];

export function registerGroups() {
  return REGISTER_IDS.map(getSection).filter(Boolean);
}

export function registerColor(sectionId) {
  let s = getSection(sectionId);
  while (s && !REGISTER_IDS.includes(s.id)) s = getSection(s.parentId);
  return s?.color ?? null;
}

export function voiceBlocks(list, sectionOfItem, rowOf) {
  const voices = new Map();
  for (const item of list) {
    const id = sectionOfItem(item);
    if (!voices.has(id)) voices.set(id, []);
    voices.get(id).push(item);
  }
  if (voices.size < 2) return list.map(rowOf);
  return [...voices].map(([id, items]) => h('div', {
    class: 'voice-block',
    style: registerColor(id) ? `--voice:${registerColor(id)}` : null,
  },
  h('h3', { class: 'voice-block__name' }, getSection(id)?.name ?? id, h('span', { class: 'voice-block__n' }, String(items.length))),
  items.map(rowOf)));
}

/* Status tile of the next rehearsal, from every screen */

export function railStat(role) {
  const user = me(role);
  const upcoming = (role === 'conductor' ? rehearsals : rehearsals.filter((r) => appliesTo(user, r)))
    .filter((r) => !r.cancelled && !r.draft && !hasStarted(r))
    .sort((a, b) => a.start.localeCompare(b.start));
  const r = upcoming[0];
  if (!r) return null;
  let counts;
  if (role === 'member') {
    counts = { attending: 0, notAttending: 0, pending: 0 };
    for (const item of upcoming) {
      const status = promises.find((p) => p.memberId === user.id && p.rehearsalId === item.id)?.status ?? 'pending';
      if (status === 'attending') counts.attending += 1;
      else if (status === 'not_attending') counts.notAttending += 1;
      else counts.pending += 1;
    }
  } else {
    counts = castStats(r, role === 'leader' ? user.sectionId : null);
  }
  const [, m, d] = r.date.split('-');
  const wd = weekdayShort(r.start);
  const day = role === 'member' ? '' : `${wd[0]}${wd.slice(1).toLowerCase()} ${d}.${m}.`;
  const title = role === 'member' ? 'Meine Proben' : role === 'leader' ? `Probe · ${sectionOf(user)}` : 'Probe';
  const seg = (kind, n) => (n > 0 ? h('span', { class: `rail-stat__seg rail-stat__seg--${kind}`, style: `flex-grow:${n}` }) : null);
  const num = (kind, n) => h('span', { class: 'rail-stat__num' }, h('span', { class: `rail-stat__dot rail-stat__seg--${kind}` }), String(n));
  return h('a', {
    class: 'rail-stat',
    href: `#${role === 'conductor' ? '/rehearsals' : '/promises'}/${r.id}`,
    'aria-label': `${title} ${day}: ${counts.attending} zugesagt, ${counts.notAttending} abgesagt, ${counts.pending} offen`,
  },
  h('span', { class: 'rail-stat__head' }, h('span', { class: 'rail-stat__title' }, title), h('span', { class: 'rail-stat__date' }, day)),
  h('span', { class: 'rail-stat__bar' }, seg('yes', counts.attending), seg('no', counts.notAttending), seg('open', counts.pending)),
  h('span', { class: 'rail-stat__nums' }, num('yes', counts.attending), num('no', counts.notAttending), num('open', counts.pending)));
}

/* Weekday anchor: carries the rehearsal colour */

export function rowTint(r) {
  return r.color && r.color.toLowerCase() !== NEUTRAL_COLOR ? `--tag:${r.color}` : null;
}

export function weekdayAnchor(r) {
  const range = Boolean(r.multiDay && r.endDate && r.endDate !== r.date);
  const tint = rowTint(r);
  return h('span', { class: ['proben-row__wd', range && 'proben-row__wd--range', tint && 'is-tinted'], style: tint },
    range ? `${weekdayShort(r.start)}–${weekdayShort(r.end)}` : weekdayShort(r.start));
}

/* Rehearsal type, group, location, infos */

const KIND_FALLBACK = '#7c3aed';

export function kindColor(r) {
  return r?.color && r.color.toLowerCase() !== NEUTRAL_COLOR ? r.color : KIND_FALLBACK;
}

export function typeBadge(r) {
  const type = typeof r === 'string' ? r : r?.type;
  if (!type || type === 'Probe') return null;
  const color = typeof r === 'string' ? KIND_FALLBACK : kindColor(r);
  return h('span', { class: ['rx-tag', 'rx-tag--kind'], style: `--kind:${color}` }, /^tutti/i.test(type) ? icon('users-round') : null, h('span', {}, type));
}

export function cancelledTag() {
  return h('span', { class: ['rx-tag', 'rx-tag--off'] }, 'Fällt aus');
}

export function deadlineOpen(r) {
  return Boolean(r?.deadline) && new Date(r.deadline).getTime() > now().getTime();
}

export function deadlineInfo(r) {
  if (!deadlineOpen(r)) return null;
  const clock = formatTime(r.deadline);
  return {
    text: `Antwort bis ${formatDate(r.deadline).split(',')[0]} ${clock}`,
    title: `Antwort bis ${formatDate(r.deadline).split(',')[0]} ${r.deadline.slice(8, 10)}.${r.deadline.slice(5, 7)}., ${clock}`,
    due: new Date(r.deadline).getTime() <= now().getTime() + DAY_MS,
  };
}

export function groupBadge(r, { tutti = false, always = false } = {}) {
  const rehearsal = typeof r !== 'string';
  if (rehearsal && !always && !showsScope(r)) return null;
  const text = rehearsal ? r.groupsText ?? groupsSentence(r.instrumentIds) : r;
  if (!text || (text === 'Tutti' && !tutti && !rehearsal)) return null;
  return h('span', { class: ['rx-tag', 'rx-tag--cast'] }, icon('users-round'), h('span', {}, text));
}

function liveRole(role) {
  const live = getRole(role?.id ?? role);
  return live ?? (typeof role === 'object' ? role : null);
}

export function roleChip(role, { names = false, count, selected, onClick, className } = {}) {
  const control = typeof onClick === 'function';
  return h(control ? 'button' : 'span', {
    class: ['role-chip', names && 'role-chip--names', control && 'role-chip--control', className],
    type: control ? 'button' : null,
    role: !control && !names ? 'img' : null,
    style: `--role:${role.tagColor}`,
    title: role.name,
    'aria-label': names ? null : role.name,
    'aria-pressed': control && selected != null ? String(Boolean(selected)) : null,
    onclick: control ? onClick : null,
  },
  icon(role.icon ?? 'tag'),
  names ? h('span', { class: 'role-chip__label' }, role.name) : null,
  count != null ? h('span', { class: 'role-chip__count' }, String(count)) : null);
}

export function roleChips(roles = [], { max = 3, names = false, all = false } = {}) {
  const list = roles.map(liveRole).filter((role) => role && (all || !role.isDefault))
    .sort((a, b) => (a.sortOrder ?? 0) - (b.sortOrder ?? 0) || a.id - b.id);
  if (!list.length) return null;
  const rest = list.slice(max).map((role) => role.name).join(', ');
  return h('span', { class: 'role-chips' },
    list.slice(0, max).map((role) => roleChip(role, { names })),
    rest ? h('span', {
      class: ['role-chip', 'role-chip--more', names && 'role-chip--names'],
      role: 'img',
      title: rest,
      'aria-label': rest,
    }, h('span', { class: 'role-chip__label' }, `+${list.length - max}`)) : null);
}

document.addEventListener('click', (event) => {
  if (wideQuery.matches) return;
  const tip = event.target.closest?.('.proben-row :is(.role-chip, .proben-row__info, .proben-row__own-note)[title]');
  if (!tip || tip.closest('.is-selecting')) return;
  event.preventDefault();
  event.stopPropagation();
  haptic('selection');
  toast(tip.title.split(/\n/).join(' · '));
}, true);

export function locationMeta(r, { always = false } = {}) {
  const name = typeof r === 'string' ? r : [r.location, r.room].filter(Boolean).join(' · ');
  if (!name || (!always && name === ensemble.defaultLocation)) return null;
  return h('span', { class: 'meta-location' },
    icon('map-pin'), h('span', { class: 'meta-location__text' }, name));
}

export function badges(r, { tutti = false, roles = true } = {}) {
  const frag = document.createDocumentFragment();
  frag.append(...[typeBadge(r), groupBadge(r, { tutti }), roles ? roleChips(r.roleIds) : null].filter(Boolean));
  return frag;
}

export function infoPill(emoji, { size } = {}) {
  return h('span', { class: ['info-pill', size === 'lg' && 'info-pill--lg'], 'aria-hidden': 'true' }, emoji);
}

export function infoPills(infos = [], { max = 3 } = {}) {
  const list = infos.map((i) => (typeof i === 'string' ? i : i.emoji)).filter(Boolean);
  if (!list.length) return null;
  return h('span', { class: 'info-pills' },
    list.slice(0, max).map((emoji) => infoPill(emoji)),
    list.length > max ? h('span', { class: 'info-pill info-pill--more' }, `+${list.length - max}`) : null);
}

export function infoRow(info) {
  return h('div', { class: 'info-row' },
    infoPill(info.emoji, { size: 'lg' }),
    h('span', { class: 'info-row__text' }, info.text));
}

/* Builders */

const monthShort = (month) => new Date(2000, month - 1, 1).toLocaleDateString('de-DE', { month: 'short' }).replace('.', '').toUpperCase();

export function tintedChip(r, options) {
  const chip = dateChip(r, options);
  const tint = rowTint(r);
  if (tint) {
    chip.classList.add('is-tinted');
    chip.style.cssText += `;${tint}`;
  }
  return chip;
}

export function dateChip(r, { size = 'md', month = false } = {}) {
  const today = daysFromNow(r.start) === 0 || r.isToday;
  const range = r.multiDay && r.endDate && r.endDate !== r.date;
  const classes = ['date-chip'];
  if (size === 'lg') classes.push('date-chip--lg');
  if (size === 'sm') classes.push('date-chip--sm');
  if (r.cancelled) classes.push('date-chip--cancelled');
  else if (today) classes.push('date-chip--today');
  else if (r.isPast) classes.push('date-chip--past');
  if (range) classes.push('date-chip--range');
  const day = (iso) => String(Number(iso.slice(8, 10)));
  const monthOf = (iso) => monthShort(Number(iso.slice(5, 7)));
  const wd = month ? monthOf(r.start) : range ? `${weekdayShort(r.start)}–${weekdayShort(r.end)}` : weekdayShort(r.start);
  const dd = range ? `${day(r.start)}–${day(r.end)}` : day(r.start);
  if (month) classes.push('date-chip--month');
  return h('div', { class: classes, 'aria-hidden': 'true' },
    h('span', { class: 'date-chip__wd' }, wd),
    h('span', { class: 'date-chip__day' }, dd));
}

const PILLS = {
  yes: ['circle-check', 'Zugesagt'],
  no: ['circle-x', 'Abgesagt'],
  open: ['circle-dashed', 'Offen'],
  warn: ['triangle-alert', 'Abweichung'],
  cancelled: ['ban', 'Fällt aus'],
  live: [null, 'Jetzt'],
  present: ['check', 'Anwesend'],
  absent: ['x', 'Fehlt'],
  excused: ['circle-minus', 'Entschuldigt'],
  queued: ['clock', 'Wird gesendet'],
};
const PILL_ALIASES = {
  attending: 'yes', not_attending: 'no', pending: 'open', late: 'present',
};

export function pillKind(status) {
  return PILL_ALIASES[status] ?? status;
}

export function statusPill(kind, { interactive = false, label, compact = false, onHero = false, onClick } = {}) {
  interactive = interactive && typeof onClick === 'function';
  const k = pillKind(kind);
  const [iconName, defaultLabel] = PILLS[k] ?? PILLS.open;
  const text = label ?? defaultLabel;
  const classes = ['status-pill', `status-pill--${k}`];
  if (interactive) classes.push('is-interactive');
  if (compact) classes.push('status-pill--compact');
  if (onHero) classes.push('status-pill--on-hero');
  const lead = iconName ? icon(iconName) : h('span', { class: 'status-pill__dot', 'aria-hidden': 'true' });
  return h(interactive ? 'button' : 'span', {
    class: classes,
    type: interactive ? 'button' : null,
    onclick: interactive ? onClick : null,
  },
  lead,
  h('span', { class: compact ? 'status-pill__label visually-hidden' : 'status-pill__label' }, text),
  interactive ? icon('chevron-down', { class: 'status-pill__chevron' }) : null);
}

function normCounts(c = {}) {
  return {
    yes: c.attending ?? c.yes ?? c.present ?? 0,
    no: c.notAttending ?? c.no ?? c.absent ?? 0,
    open: c.pending ?? c.open ?? 0,
  };
}

export function triad(counts, { size, onHero = false } = {}) {
  const { yes, no, open } = normCounts(counts);
  const total = yes + no + open;
  const classes = ['triad'];
  if (size === 'sm' || size === 'lg') classes.push(`triad--${size}`);
  if (onHero) classes.push('triad--on-hero');
  const el = h('div', {
    class: classes,
    role: 'img',
    'aria-label': `${yes} zugesagt, ${no} abgesagt, ${open} offen`,
  });
  if (!total) {
    el.append(h('span', { class: 'triad__seg triad__seg--empty', style: 'flex-grow:1' }));
    return el;
  }
  for (const [key, value] of [['yes', yes], ['no', no], ['open', open]]) {
    if (value > 0) el.append(h('span', { class: `triad__seg triad__seg--${key}`, style: `flex-grow:${value}` }));
  }
  return el;
}

export function triadLegend(counts) {
  const { yes, no, open } = normCounts(counts);
  return h('div', { class: 'triad-legend' },
    h('span', { class: 'triad-legend__item triad-legend__item--yes', title: 'Zugesagt' }, icon('circle-check'), String(yes)),
    h('span', { class: 'triad-legend__item triad-legend__item--no', title: 'Abgesagt' }, icon('circle-x'), String(no)),
    h('span', { class: 'triad-legend__item triad-legend__item--open', title: 'Offen' }, icon('circle-dashed'), String(open)));
}

const OWN_IDS = new Set(Object.values(users).map((u) => u.id));

function initialsOf(member) {
  if (member?.initials) return member.initials;
  return String(member?.displayName ?? member?.name ?? '?')
    .split(/\s+/).map((p) => p[0]).slice(0, 2).join('').toUpperCase();
}

export function avatar(member, size = 36) {
  const classes = ['avatar', `avatar--${size}`];
  const tint = getSection(member?.sectionId)?.color ?? member?.avatarColor;
  let style = null;
  if (OWN_IDS.has(member?.id)) classes.push('avatar--accent');
  else if (tint) style = `--avatar:${tint}`;
  return h('span', { class: classes, style, 'aria-hidden': 'true' }, initialsOf(member));
}

export function ensembleMonogram() {
  const words = ensemble.shortName.split(/\s+/).filter(Boolean);
  if (/^[A-ZÄÖÜ]{2,3}$/.test(words[0] ?? '')) return words[0];
  return words.slice(0, 2).map((w) => w[0]).join('').toUpperCase();
}

export function ensembleTile(size = 32, mono = ensembleMonogram()) {
  return h('span', { class: ['ensemble-tile', `ensemble-tile--${size}`], 'aria-hidden': 'true' }, mono);
}

export function sectionStats(r) {
  const leader = users.leader;
  const ids = members.filter((m) => m.sectionId === leader.sectionId && appliesTo(m, r)).map((m) => m.id);
  if (!ids.length) return null;
  const s = stats(r.id, ids);
  const params = new URLSearchParams({ scope: leader.sectionId, filter: s.pending ? 'open' : 'yes' });
  return {
    ...s,
    name: getSection(leader.sectionId)?.name ?? '',
    low: criticalSections(r).some((c) => c.id === leader.sectionId),
    href: `#/promises/${r.id}/responses?${params}`,
  };
}

function ownGroups(member) {
  const ids = new Set([member.sectionId]);
  for (let section = getSection(member.sectionId); section?.parentId; section = getSection(section.parentId)) ids.add(section.parentId);
  return ids;
}

export function liveItem(r, member = null) {
  if (r.start > NOW || r.end <= NOW) return null;
  const today = NOW.slice(0, 10);
  const stamp = NOW.slice(0, 16);
  const at = (item) => `${item.date}T${item.time}`;
  const items = (r.scheduleItems ?? []).filter((item) => item.date === today);
  const mine = member?.sectionId ? ownGroups(member) : null;
  const relevant = mine ? items.filter((item) => !item.groups?.length || item.groups.some((g) => mine.has(g))) : items;
  const running = relevant.findLast((item) => at(item) <= stamp);
  if (!running) return null;
  const next = items.find((item) => at(item) > at(running));
  return { title: running.title, until: next ? next.time : formatTime(r.end) };
}

export function liveLine(r, member = null, { action = null } = {}) {
  const now = liveItem(r, member);
  if (!now) return null;
  return h('div', { class: 'proben-row__live' },
    h('span', { class: 'live-dot', 'aria-hidden': 'true' }),
    h('span', { class: 'proben-row__live-label' }, 'Jetzt'),
    h('span', { class: 'proben-row__live-title' }, now.title),
    h('span', { class: 'proben-row__live-until' }, `bis ${now.until}`),
    action);
}

export function timeLine(r, { member = null, className } = {}) {
  if (!r?.scheduleItems?.length || r.cancelled) return null;
  const day = r.start <= NOW && NOW < r.end ? NOW.slice(0, 10) : r.date;
  const mine = member?.sectionId ? ownGroups(member) : null;
  const items = r.scheduleItems.filter((item) => item.date === day);
  const times = [...new Set(items.map((item) => item.time))].sort();
  if (times.length < 2) return null;
  const minutes = (hhmm) => Number(hhmm.slice(0, 2)) * 60 + Number(hhmm.slice(3, 5));
  const endTime = r.end.slice(0, 10) === day ? r.end.slice(11, 16) : '23:59';
  const now = NOW.slice(0, 10) === day ? minutes(NOW.slice(11, 16)) : NOW.slice(0, 10) > day ? Infinity : -Infinity;
  const slots = times.map((time, i) => {
    const from = minutes(time);
    const to = minutes(times[i + 1] ?? endTime);
    const titles = items.filter((item) => item.time === time && (!mine || !item.groups?.length || item.groups.some((g) => mine.has(g)))).map((item) => item.title);
    return { time, from, to, titles };
  }).filter((slot) => slot.to > slot.from);
  const current = slots.find((slot) => slot.from <= now && now < slot.to);
  const agendaText = slots.map((slot) => `${slot.time} ${slot.titles.join(' · ')}`.trim()).join(', ');
  const label = [current ? `Jetzt ${current.titles[0] ?? ''}, bis ${times[times.indexOf(current.time) + 1] ?? endTime}` : 'Ablauf', agendaText].filter(Boolean).join('. ');
  return h('div', { class: ['time-line', className], role: 'img', 'aria-label': label },
    slots.map((slot) => {
      const share = now >= slot.to ? 1 : now <= slot.from ? 0 : (now - slot.from) / (slot.to - slot.from);
      return h('span', {
        class: ['time-line__seg', share === 1 && 'is-done', slot === current && 'is-now'],
        style: `flex-grow:${slot.to - slot.from}`,
        title: `${slot.time} ${slot.titles.join(' · ')}`,
      },
      h('span', { class: 'time-line__fill', style: `width:${Math.round(share * 1000) / 10}%` }),
      slot === current ? h('span', { class: 'time-line__now', style: `left:${Math.round(share * 1000) / 10}%` }) : null);
    }));
}

export function sectionMeter(r, { onHero = false } = {}) {
  const leader = users.leader;
  const ids = members.filter((m) => m.sectionId === leader.sectionId && appliesTo(m, r)).map((m) => m.id);
  if (!ids.length) return null;
  const s = stats(r.id, ids);
  const name = getSection(leader.sectionId)?.name ?? '';
  const rate = s.total ? Math.round((100 * s.attending) / s.total) : 100;
  const low = criticalSections(r).some((c) => c.id === leader.sectionId);
  const params = new URLSearchParams({ scope: leader.sectionId, filter: s.pending ? 'open' : 'yes' });
  return h('a', {
    class: ['section-meter', onHero && 'section-meter--on-hero'],
    href: `#/promises/${r.id}/responses?${params}`,
    'aria-label': `${name}: ${s.attending} zugesagt, ${s.notAttending} abgesagt, ${s.pending} offen${low ? `, ${rate} %` : ''}`,
  },
  h('span', { class: 'section-meter__name' }, name),
  triad(s, { size: 'sm' }),
  h('span', { class: 'section-meter__count' }, s.pending ? `${s.pending} offen` : `${s.attending}/${s.total}`),
  icon('chevron-right', { class: 'section-meter__chevron' }));
}

function responseMeter(r) {
  const s = stats(r.id);
  const crit = criticalSections(r)[0] ?? null;
  return h('button', {
    class: ['response-meter', crit && 'is-flagged'],
    type: 'button',
    'aria-label': `${s.attending} von ${s.total} zugesagt, ${s.pending} offen${crit ? `, ${crit.name} ${crit.rate} %` : ''}. Rückmeldungen anzeigen`,
    onclick: (event) => {
      event.preventDefault();
      navigate(`/rehearsals/${r.id}?seg=wer`);
    },
  },
  h('span', { class: 'response-meter__value' },
    crit ? h('span', { class: 'marker marker--warn', 'aria-hidden': 'true' }) : null,
    `${s.attending}/${s.total}`),
  triad(s),
  h('span', { class: 'response-meter__open' },
    s.pending ? `${s.pending} offen` : icon('check')));
}

function outcomePill(r, memberId) {
  const row = getAttendance(r.id).find((a) => a.memberId === Number(memberId));
  if (!row) return null;
  return statusPill(row.status === 'late' ? 'present' : row.status, { compact: true });
}

export function rehearsalCard(r, { mode = 'member', memberId, href } = {}) {
  const ownId = memberId ?? (mode === 'leader' ? users.leader.id : users.member.id);
  const promise = ['member', 'leader', 'past', 'preview'].includes(mode)
    ? promises.find((p) => p.memberId === Number(ownId) && p.rehearsalId === r.id) ?? null
    : null;
  const isPast = mode === 'past' || (r.isPast && !r.isToday);
  const open = (mode === 'member' || mode === 'leader') && !isPast && answerOpen(r, ownId);

  const classes = ['rehearsal-card'];
  if (rowTint(r)) classes.push('is-tinted');
  if (mode === 'mgmt') classes.push('rehearsal-card--mgmt');
  if (mode === 'edit') classes.push('rehearsal-card--edit');
  if (open) classes.push('rehearsal-card--open');
  if (isPast) classes.push('rehearsal-card--past');
  if (r.cancelled) classes.push('rehearsal-card--cancelled');

  const statusWord = r.cancelled ? 'fällt aus'
    : promise ? { attending: 'zugesagt', not_attending: 'abgesagt', pending: 'offen' }[promise.status] : '';
  const label = [formatDate(r.start), timeRange(r).replace(' – ', ' bis '), r.type !== 'Probe' ? r.type : '', metaText(r), statusWord]
    .filter(Boolean).join(', ');
  const target = href ?? (mode === 'edit' || mode === 'preview' ? null
    : mode === 'mgmt' ? `#/rehearsals/${r.id}` : `#/promises/${r.id}`);

  let trail = null;
  if (r.cancelled) trail = cancelledTag();
  else if (mode === 'mgmt') trail = responseMeter(r);
  else if (isPast) trail = outcomePill(r, ownId);
  else if (promise && promise.status !== 'pending') trail = statusPill(promise.status, { interactive: mode !== 'preview' && mode !== 'edit' && !hasStarted(r) });

  const line2 = [typeBadge(r), groupBadge(r), roleChips(r.roleIds), locationMeta(r)].filter(Boolean);
  const pills = infoPills(r.infos);
  const meter = mode === 'leader' && !isPast && !r.cancelled ? sectionMeter(r) : null;
  const note = r.cancelled ? r.cancelReason : isPast ? null : promise?.note;

  return h('article', {
    class: classes,
    style: rowTint(r),
    'data-id': r.id,
  },
  target ? h('a', { class: 'rehearsal-card__link', href: target, 'aria-label': label }) : null,
  dateChip(r),
  h('div', { class: 'rehearsal-card__main' },
    h('div', { class: 'rehearsal-card__line1' }, h('span', { class: 'rehearsal-card__time' }, timeRange(r))),
    line2.length ? h('div', { class: 'rehearsal-card__line2' }, line2) : null,
    pills || meter ? h('div', { class: 'rehearsal-card__line3' }, pills, meter) : null,
    note ? h('p', { class: 'rehearsal-card__note' }, r.cancelled ? null : icon('quote'), note) : null),
  h('div', { class: 'rehearsal-card__trail' }, trail));
}

export function segmented(items, value, onChange, { radio = false, label = null } = {}) {
  const list = items.map((item) => (typeof item === 'string' ? { value: item, label: item } : item));
  const stateAttr = radio ? 'aria-checked' : 'aria-selected';
  const el = h('div', {
    class: ['segmented', list.some((item) => item.icon) && 'segmented--icons'],
    role: radio ? 'radiogroup' : 'tablist',
    'aria-label': label,
  });
  const select = (v, focus) => {
    for (const btn of el.children) {
      const on = btn.dataset.value === String(v);
      btn.setAttribute(stateAttr, String(on));
      btn.tabIndex = on ? 0 : -1;
      if (on && focus) btn.focus();
    }
  };
  for (const item of list) {
    el.append(h('button', {
      class: 'segmented__btn',
      type: 'button',
      role: radio ? 'radio' : 'tab',
      'data-value': item.value,
      [stateAttr]: String(item.value === value),
      'aria-label': item.ariaLabel ?? null,
      tabindex: item.value === value ? '0' : '-1',
      onclick: () => {
        if (el.querySelector(`[${stateAttr}="true"]`)?.dataset.value === String(item.value)) return;
        haptic('selection');
        select(item.value);
        onChange?.(item.value);
      },
    },
    item.icon ? icon(item.icon) : null,
    item.label != null ? h('span', { class: 'segmented__label' }, item.label) : null,
    item.count != null && (item.count !== 0 || item.showZero)
      ? h('span', { class: item.badge ? 'badge badge--count' : 'segmented__count' }, String(item.count))
      : null));
  }
  el.addEventListener('keydown', (event) => {
    const keys = radio ? ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'] : ['ArrowLeft', 'ArrowRight'];
    if (!keys.includes(event.key)) return;
    const btns = [...el.children];
    const i = btns.findIndex((b) => b.getAttribute(stateAttr) === 'true');
    const forward = event.key === 'ArrowRight' || event.key === 'ArrowDown';
    const next = btns[(i + (forward ? 1 : btns.length - 1)) % btns.length];
    event.preventDefault();
    const info = locateTrigger(next);
    next.click();
    next.focus();
    if (info) restoreFocus(info);
  });
  return el;
}

export function chip({ label, icon: iconName, tone, pressed, selected, count, dot, onClick, href, role, ariaLabel, className } = {}) {
  const state = pressed ?? selected;
  return h(href ? 'a' : 'button', {
    class: ['chip', tone && `chip--${tone}`, state && 'is-selected', className],
    type: href ? null : 'button',
    href: href ?? null,
    role: role ?? null,
    'aria-pressed': role || href || state == null ? null : String(Boolean(state)),
    'aria-checked': role === 'radio' || role === 'checkbox' ? String(Boolean(state)) : null,
    'aria-current': href && state ? 'true' : null,
    'aria-label': ariaLabel ?? null,
    style: dot ? `--dot:${dot}` : null,
    onclick: onClick ?? null,
  },
  dot ? h('span', { class: 'chip__dot', 'aria-hidden': 'true' }) : null,
  iconName ? icon(iconName) : null,
  label != null ? h('span', { class: 'chip__label' }, label) : null,
  count != null ? h('span', { class: 'chip__count' }, String(count)) : null);
}

export function chipRow(children, { className, label } = {}) {
  const row = h('div', { class: ['chip-row', className], role: label ? 'group' : null, 'aria-label': label ?? null }, children);
  const sync = () => {
    const over = row.scrollWidth > row.clientWidth + 1;
    row.classList.toggle('is-overflowing', over);
    row.classList.toggle('is-at-end', over && row.scrollLeft + row.clientWidth >= row.scrollWidth - 2);
  };
  row.addEventListener('scroll', sync, { passive: true });
  if ('ResizeObserver' in window) new ResizeObserver(sync).observe(row);
  requestAnimationFrame(sync);
  return row;
}

export function sectionHeader(title, { icon: iconName, tone, count, action, id, tag = 'h2', className } = {}) {
  return h(tag, { class: ['section-header', iconName && 'section-header--icon', className], id: id ?? null },
    iconName ? h('span', { class: 'section-header__icon', style: `--tile:${toneColor(tone)}`, 'aria-hidden': 'true' }, icon(iconName)) : null,
    h('span', { class: 'section-header__title' }, title),
    count != null ? h('span', { class: 'section-header__count' }, String(count)) : null,
    action ? h(action.href ? 'a' : 'button', {
      class: 'section-header__action',
      type: action.href ? null : 'button',
      href: action.href ?? null,
      onclick: action.onClick ?? null,
    }, action.icon ? icon(action.icon) : null, action.label) : null);
}

export function agenda(entries = []) {
  const list = h('div', { class: 'list-group agenda' });
  for (const entry of entries) {
    if (entry.day) {
      list.append(h('div', { class: 'agenda__day' }, entry.day));
      continue;
    }
    const lines = Array.isArray(entry.title) ? entry.title : [{ title: entry.title, groups: entry.groups }];
    list.append(h('div', {
      class: ['agenda__item', entry.state === 'done' && 'is-done', entry.state === 'now' && 'is-now'],
      'aria-current': entry.state === 'now' ? 'time' : null,
    },
    h('span', { class: 'agenda__time' }, entry.time),
    h('span', { class: 'agenda__dot', 'aria-hidden': 'true' }),
    h('div', { class: 'agenda__title' },
      lines.map((line) => h('div', { class: 'agenda__title-line' },
        h('span', { class: 'agenda__text' }, line.title),
        line.groups && String(line.groups) !== 'Tutti' ? groupBadge(Array.isArray(line.groups) ? line.groups.join(', ') : line.groups, { tutti: true }) : null)))));
  }
  return list;
}

export function whoRow({ name, counts, rate, low = false, count = true, href, onClick, ariaLabel } = {}) {
  const { yes, no, open } = normCounts(counts);
  const total = yes + no + open;
  return h(href ? 'a' : onClick ? 'button' : 'div', {
    class: ['who-row', !count && 'who-row--rate'],
    href: href ?? null,
    type: !href && onClick ? 'button' : null,
    onclick: onClick ?? null,
    'aria-label': ariaLabel ?? `${name}: ${yes} von ${total} zugesagt, ${no} abgesagt, ${open} offen${low && rate != null ? `, ${rate} %` : ''}`,
  },
  h('span', { class: 'who-row__name' }, name),
  h('span', { class: 'who-row__bar' }, triad(counts, { size: 'sm' })),
  h('span', { class: 'who-row__rate' }),
  count ? h('span', { class: 'who-row__count' }, `${yes}/${total}`) : null,
  href || onClick ? icon('chevron-right', { class: 'who-row__chevron' }) : null);
}

export function attToggle({ value = null, proposed = null, name = '', onChange } = {}) {
  const el = h('div', { class: 'att-toggle', role: 'radiogroup', 'aria-label': name ? `Anwesenheit ${name}` : 'Anwesenheit' });
  const options = [['present', 'check', 'Anwesend', 'yes'], ['absent', 'x', 'Fehlt', 'no']];
  const buttons = options.map(([v, iconName, label, tone]) => h('button', {
    class: ['att-toggle__btn', `att-toggle__btn--${tone}`],
    type: 'button',
    role: 'radio',
    'aria-label': label,
    title: label,
    'data-value': v,
    onclick: () => {
      haptic('selection');
      set(v);
      onChange?.(v);
    },
  }, icon(iconName)));
  const set = (v, p = null) => {
    for (const btn of buttons) {
      const on = btn.dataset.value === v;
      btn.setAttribute('aria-checked', String(on));
      btn.classList.toggle('is-proposed', !v && btn.dataset.value === p);
    }
    el.dataset.value = v ?? '';
  };
  el.append(...buttons);
  set(value, proposed);
  el.setValue = set;
  return el;
}

export function kpiStrip(items = []) {
  return h('div', { class: 'kpi-strip' }, items.map((item) => h(item.onClick ? 'button' : item.href ? 'a' : 'div', {
    class: ['kpi', item.tone && `kpi--${item.tone}`],
    type: item.onClick ? 'button' : null,
    href: item.href ?? null,
    onclick: item.onClick ?? null,
  },
  item.icon ? h('span', { class: 'kpi__icon', 'aria-hidden': 'true' }, icon(item.icon)) : null,
  h('span', { class: 'kpi__value' }, String(item.value)),
  h('span', { class: 'kpi__label' }, item.label))));
}

export function titleAction({ icon: iconName, label, onClick, primary = false, pressed = null, key }) {
  return h('button', {
    class: ['title-action', primary && 'title-action--primary'],
    type: 'button',
    'aria-label': label,
    title: label,
    'aria-pressed': pressed == null ? null : String(pressed),
    'data-act': key ?? null,
    onclick: (event) => {
      haptic('light');
      onClick?.(event);
    },
  }, icon(iconName));
}

export function titleTools(...nodes) {
  return h('div', { class: 'large-title__tools' }, ...nodes.flat().filter(Boolean));
}

export function emptyState({ icon: iconName = 'calendar-check', title, text, action, tone } = {}) {
  return h('div', { class: 'empty-state' },
    iconName ? h('span', { class: 'icon-tile icon-tile--lg', style: `--tile:${toneColor(tone)}` }, icon(iconName)) : null,
    title ? h('h2', { class: 'empty-state__title' }, title) : null,
    text ? h('p', { class: 'empty-state__text' }, text) : null,
    action ? h(action.href ? 'a' : 'button', {
      class: 'button button--tinted',
      type: action.href ? null : 'button',
      href: action.href ?? null,
      onclick: action.onClick ?? null,
    }, action.icon ? icon(action.icon) : null, h('span', { class: 'button__label' }, action.label)) : null);
}

export function actionRow({ icon: iconName, label, href, onClick, className }) {
  return h('div', { class: ['list-group', 'action-row', className] },
    h(href ? 'a' : 'button', {
      class: 'list-row',
      type: href ? null : 'button',
      href: href ?? null,
      onclick: onClick ?? null,
    },
    h('span', { class: 'list-row__lead' }, h('span', { class: 'icon-tile', 'aria-hidden': 'true' }, icon(iconName))),
    h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, label)),
    h('span', { class: 'list-row__trail', 'aria-hidden': 'true' }, icon('chevron-right', { class: 'list-row__chevron' }))));
}

const userEnsembles = [ensemble];

export function bellAction(role) {
  const unread = notifications.filter((n) => !n.read && (role !== 'member' || n.audience === 'all')).length;
  return {
    icon: 'bell',
    label: 'Mitteilungen',
    dot: unread > 0,
    onClick: (event) => openNotificationsSheet(role, event?.currentTarget),
  };
}

export function bellButton(role) {
  const action = bellAction(role);
  return h('button', {
    class: 'app-header__action shell-bell',
    type: 'button',
    'aria-label': action.dot ? `${action.label}, neu` : action.label,
    title: action.label,
    onclick: action.onClick,
  }, icon('bell'), action.dot ? h('span', { class: 'app-header__dot', 'aria-hidden': 'true' }) : null);
}

/* Link sharing: one "Teilen" on touch, "Link kopieren" first on a pointer device */

export function linkSharing({ title, url, copied = 'Link kopiert' }) {
  const canShare = () => Boolean(navigator.share) && (!navigator.canShare || navigator.canShare({ title, url: url() }));
  const copy = async () => {
    try {
      await navigator.clipboard.writeText(url());
      haptic('success');
      toast(html`${icon('circle-check')} ${copied}`);
    } catch {
      toast(html`${icon('circle-x')} Kopieren nicht möglich`);
    }
  };
  const share = async () => {
    if (canShare()) {
      try {
        await navigator.share({ title, url: url() });
        return;
      } catch (error) {
        if (error?.name === 'AbortError') return;
      }
    }
    copy();
  };
  const touch = matchMedia('(hover: none)').matches;
  const actions = touch
    ? [{ label: 'Teilen', icon: 'share-2', variant: 'primary', onClick: () => { share(); } }]
    : [
      { label: 'Link kopieren', icon: 'copy', variant: 'primary', onClick: () => { copy(); } },
      canShare() ? { label: 'Teilen', icon: 'share-2', variant: 'secondary', onClick: () => { share(); } } : null,
    ].filter(Boolean);
  return { copy, share, actions };
}

/* Sheets */

export function sectionOf(user) {
  if (user?.isConductor) return null;
  return getSection(user?.sectionId)?.name ?? null;
}

const NOTE_ICONS = {
  reminder_sent: ['bell-ring', 'var(--brand-500)'],
  note_added: ['quote', 'var(--amber-500)'],
  rehearsal_created: ['calendar-plus', 'var(--green-500)'],
  member_joined: ['user-plus', 'var(--violet-600)'],
  info_added: ['info', 'var(--brand-500)'],
  rehearsal_cancelled: ['ban', 'var(--red-500)'],
};

function relTime(iso) {
  const diffMin = Math.round((now() - new Date(iso)) / 60000);
  if (diffMin < 60) return `vor ${Math.max(1, diffMin)} Min.`;
  const days = -daysFromNow(iso);
  if (days === 0) return `heute, ${formatTime(iso)}`;
  if (days === 1) return `gestern, ${formatTime(iso)}`;
  return formatDate(iso);
}

export function openNotificationsSheet(role, anchor) {
  const leaders = role === 'leader' || role === 'conductor';
  const list = notifications.filter((n) => n.audience === 'all' || leaders);
  const group = h('div', { class: 'list-group' },
    list.map((n) => {
      const [iconName, tile] = NOTE_ICONS[n.type] ?? ['bell', 'var(--brand-500)'];
      return h(n.rehearsalId ? 'a' : 'div', {
        class: ['list-row', 'list-row--2line', 'notification-row', !n.read && 'is-unread'],
        href: n.rehearsalId ? `#/${role === 'conductor' ? 'rehearsals' : 'promises'}/${n.rehearsalId}` : null,
        onclick: () => sheet.close(),
      },
      h('span', { class: 'list-row__lead' }, h('span', { class: 'icon-tile', style: `--tile:${tile}` }, icon(iconName))),
      h('span', { class: 'list-row__body' },
        h('span', { class: 'list-row__title' }, n.title),
        h('span', { class: 'list-row__sub list-row__sub--wrap' }, n.text)),
      h('span', { class: 'list-row__trail list-row__meta' }, relTime(n.createdAt)));
    }));
  const sheet = openSheet({ title: 'Mitteilungen', anchor, content: list.length ? group : emptyState({ icon: 'bell', title: 'Keine Mitteilungen' }) });
  if (!sheet.popover) sheet.el.classList.add('sheet--large');
  const settings = h('a', {
    class: 'sheet__close sheet__settings',
    href: '#/profile/benachrichtigungen',
    'aria-label': 'Erinnerungen einstellen',
    title: 'Erinnerungen einstellen',
    onclick: () => sheet.close(),
  }, icon('settings'));
  sheet.el.querySelector('.sheet__close')?.before(settings);
  refreshIcons(settings);
  return sheet;
}

export function openConfirmSheet({ title = '', text, content = null, action = 'OK', destructive = false, icon: iconName, cancel = 'Abbrechen', stack = false, onConfirm } = {}) {
  return new Promise((resolve) => {
    let confirmed = false;
    const sheet = openSheet({
      title,
      stack,
      className: ['sheet--confirm', destructive && 'sheet--destructive'].filter(Boolean).join(' '),
      content: text || content ? h('div', { class: 'sheet__confirm' }, text ? h('p', { class: 'sheet__lead' }, text) : null, content) : '',
      actions: [
        { label: cancel, variant: 'secondary' },
        {
          label: action,
          icon: iconName,
          variant: destructive ? 'destructive-solid' : 'primary',
          onClick: () => {
            confirmed = true;
            haptic(destructive ? 'warning' : 'success');
            onConfirm?.();
          },
        },
      ],
      onClose: () => resolve(confirmed),
    });
    sheet.el.querySelector('.sheet__actions .button')?.focus({ preventScroll: true });
  });
}

export function openRemindSheet(rehearsal, memberIds = [], { scopeLabel } = {}) {
  const people = memberIds.map(getMember).filter(Boolean);
  const count = memberIds.length;
  let resolveSent;
  const sent = new Promise((resolve) => { resolveSent = resolve; });
  let didSend = false;
  const sentText = (n) => (n === 1 ? '1 Erinnerung gesendet' : `${n} Erinnerungen gesendet`);
  const shown = people.slice(0, 8);
  const oneSection = Boolean(scopeLabel) && people.every((m) => getSection(m.sectionId)?.name === scopeLabel);

  const content = h('div', { class: 'remind-sheet' },
    h('div', { class: 'remind-sheet__context' },
      tintedChip(rehearsal),
      h('span', { class: 'remind-sheet__when' },
        h('span', { class: 'remind-sheet__date' }, new Date(`${rehearsal.date}T12:00`).toLocaleDateString('de-DE', { day: 'numeric', month: 'long', year: 'numeric' })),
        h('span', { class: 'remind-sheet__time' }, timeRange(rehearsal))),
      scopeLabel ? groupBadge(scopeLabel, { tutti: true }) : null),
    h('p', { class: 'sheet__lead remind-sheet__lead' }, count === 1
      ? 'Per Push an diese Person, weil ihre Antwort auf die Probe noch fehlt.'
      : `Per Push an diese ${count} Personen, weil ihre Antwort auf die Probe noch fehlt.`),
    h('div', { class: 'list-group' },
      shown.map((m) => h('div', { class: 'list-row list-row--dense person-row' },
        h('span', { class: 'list-row__lead' }, avatar(m, 32)),
        h('span', { class: 'list-row__body' },
          h('span', { class: 'list-row__title' }, m.displayName),
          oneSection ? null : h('span', { class: 'list-row__sub' }, getSection(m.sectionId)?.name ?? '')))),
      count > shown.length ? h('div', { class: 'list-row list-row--more' },
        h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, `+${count - shown.length}`))) : null));

  const sheet = openSheet({
    title: count === 1 ? '1 Person erinnern?' : `${count} Personen erinnern?`,
    className: 'sheet--remind',
    content,
    actions: [
      {
        label: 'Per Push erinnern',
        variant: 'primary',
        icon: 'bell-ring',
        onClick: () => {
          if (!count) return false;
          didSend = true;
          haptic('success');
          toast(html`${icon('circle-check')} ${sentText(count)}`);
        },
      },
      {
        label: 'Namen kopieren',
        variant: 'plain',
        icon: 'copy',
        onClick: () => {
          navigator.clipboard?.writeText(people.map((m) => m.displayName).join(', ')).catch(() => {});
          haptic('light');
          toast(html`${icon('copy')} Namen kopiert`);
        },
      },
    ],
    onClose: () => resolveSent(didSend),
  });
  return { ...sheet, sent };
}

export function openPicker({ title = '', options = [], value, onSelect, search = false, stack = false, anchor = null } = {}) {
  const list = options.map((o) => (typeof o === 'string' ? { value: o, label: o } : o));
  const multi = Array.isArray(value);
  const selected = new Set(multi ? value : [value]);
  const group = h('div', {
    class: 'list-group action-list',
    role: 'listbox',
    'aria-label': title || null,
    'aria-multiselectable': multi ? 'true' : null,
    onkeydown: (event) => {
      if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
      const options = [...group.querySelectorAll('[role="option"]')];
      const at = options.indexOf(document.activeElement);
      if (at === -1) return;
      event.preventDefault();
      const next = event.key === 'Home' ? 0 : event.key === 'End' ? options.length - 1
        : Math.min(options.length - 1, Math.max(0, at + (event.key === 'ArrowDown' ? 1 : -1)));
      options[next].focus();
    },
  });

  const renderRows = (query = '') => {
    const q = query.trim().toLowerCase();
    group.replaceChildren(...list
      .filter((o) => !q || `${o.label} ${o.sub ?? ''}`.toLowerCase().includes(q))
      .map((o) => h('button', {
        class: ['list-row', o.sub && 'list-row--2line', selected.has(o.value) && 'is-checked'],
        type: 'button',
        role: 'option',
        'aria-selected': String(selected.has(o.value)),
        onclick: () => {
          haptic('selection');
          onSelect?.(o.value, o);
          sheet.close();
        },
      },
      o.lead || o.color || o.emoji || o.icon || o.swatchNone
        ? h('span', { class: 'list-row__lead' },
          o.lead ? o.lead()
            : o.swatchNone ? h('span', { class: 'color-dot color-dot--none' })
              : o.color ? h('span', { class: 'color-dot', style: `--dot:${o.color}` })
                : o.emoji ? infoPill(o.emoji, { size: 'lg' })
                  : o.tone ? h('span', { class: 'icon-tile icon-tile--sm', style: `--tile:${toneColor(o.tone)}` }, icon(o.icon))
                    : icon(o.icon))
        : null,
      h('span', { class: 'list-row__body' },
        h('span', { class: 'list-row__title' }, o.label),
        o.sub ? h('span', { class: 'list-row__sub' }, o.sub) : null),
      h('span', { class: 'list-row__trail' },
        o.count != null ? h('span', { class: 'list-row__value' }, String(o.count)) : null,
        selected.has(o.value) ? icon('check', { class: 'list-row__check' }) : null))));
    refreshIcons(group);
  };

  const field = search ? h('label', { class: 'search-field' },
    icon('search', { class: 'search-field__icon' }),
    h('input', {
      class: 'search-field__input',
      type: 'search',
      placeholder: 'Suchen',
      'aria-label': 'Suchen',
      oninput: (event) => renderRows(event.target.value),
    })) : null;

  renderRows();
  const sheet = openSheet({ title, stack, anchor, content: h('div', { class: 'picker' }, field, group) });
  if (list.length > 8) sheet.el.classList.add('sheet--large');
  return sheet;
}

/* Emoji, Eingabe, Besetzung */

const EMOJI_WORDS = {
  '📍': 'Ort',
  '👔': 'Anzug Konzertkleidung',
  '🎼': 'Noten Partitur',
  '🥪': 'Essen Verpflegung',
  '🚌': 'Bus Fahrt',
  '🅿️': 'Parken',
  '⚠️': 'Achtung',
  '📝': 'Notiz',
  '⏰': 'Zeit',
  '🧳': 'Koffer Übernachtung',
  '💊': 'Medikamente',
  '🎻': 'Instrument Violine Geige Streicher',
  '🥁': 'Schlagwerk Trommel',
  '🪑': 'Stuhl',
  '🎉': 'Feier',
  '📸': 'Foto',
  '📋': 'Liste',
  '☕': 'Pause Kaffee',
  '🍽️': 'Essen',
  '🛏️': 'Übernachtung',
  '🎄': 'Weihnachten',
  '❌': 'Absage',
  '💶': 'Geld Beitrag',
  '📣': 'Ansage',
  '🎺': 'Trompete Blech Blechbläser',
  '🪈': 'Flöte Holz Holzbläser',
  '🎷': 'Saxophon',
  '🎸': 'Gitarre Bass',
  '🎹': 'Klavier Keyboard',
  '🪕': 'Banjo Zupfinstrument',
  '🪗': 'Akkordeon',
  '🎤': 'Gesang Chor Mikrofon',
  '🎵': 'Note Musik Harfe',
  '🎶': 'Noten Musik',
  '🪘': 'Trommel Pauke',
  '🔔': 'Glocke Glockenspiel',
  '🪄': 'Leitung Dirigent Zauberstab',
};
const EMOJIS = Object.keys(EMOJI_WORDS);
const EMOJI_KEYS = ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'];

function fold(text) {
  return text.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();
}

function firstGrapheme(text) {
  const [first] = new Intl.Segmenter('de', { granularity: 'grapheme' }).segment(text.trim());
  return first?.segment ?? '📝';
}

export function setPressed(el, on) {
  el.classList.toggle('is-selected', on);
  if (el.hasAttribute('aria-checked')) el.setAttribute('aria-checked', String(on));
  else el.setAttribute('aria-pressed', String(on));
}

export function openPromptSheet({ title, value = '', placeholder, maxlength = 50, confirm, onConfirm, stack = false }) {
  const input = h('input', { class: 'input', type: 'text', value, placeholder, maxlength: String(maxlength), autofocus: true, 'aria-label': title });
  const count = h('span', { class: 'textarea__count' }, `${value.length}/${maxlength}`);
  input.addEventListener('input', () => {
    count.textContent = `${input.value.length}/${maxlength}`;
  });
  input.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && input.value.trim()) {
      onConfirm(input.value.trim());
      sheet.close();
    }
  });
  const sheet = openSheet({
    title,
    stack,
    content: h('div', { class: 'editor__prompt' }, input, count),
    actions: [{
      label: confirm,
      variant: 'primary',
      onClick: () => {
        if (!input.value.trim()) {
          input.focus();
          return false;
        }
        onConfirm(input.value.trim());
        return true;
      },
    }],
  });
  setTimeout(() => input.focus(), 50);
  return sheet;
}

export function openTagSheet({ suggestions = [], onAdd, stack = false } = {}) {
  const input = h('input', { class: 'input', type: 'text', placeholder: 'Neuer Tag', maxlength: '24', 'aria-label': 'Neuer Tag' });
  const sheet = openSheet({
    title: 'Tag hinzufügen',
    stack,
    content: h('div', { class: 'editor__prompt' },
      input,
      suggestions.length ? sectionHeader('Vorschläge', { tag: 'h3' }) : null,
      suggestions.length ? h('div', { class: 'editor__chips editor__chips--sheet' },
        suggestions.map((name) => chip({
          label: name,
          icon: 'plus',
          onClick: () => {
            haptic('selection');
            onAdd(name);
            sheet.close();
          },
        }))) : null),
    actions: [{
      label: 'Hinzufügen',
      variant: 'primary',
      onClick: () => {
        if (!input.value.trim()) {
          input.focus();
          return false;
        }
        onAdd(input.value.trim());
        return true;
      },
    }],
  });
  input.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && input.value.trim()) {
      onAdd(input.value.trim());
      sheet.close();
    }
  });
  return sheet;
}

export function openColorSheet({ current, onPick, stack = false, anchor = null } = {}) {
  const value = current ? current.toLowerCase() : null;
  const sheet = openSheet({
    title: 'Farbe',
    stack,
    anchor,
    content: h('div', { class: 'swatch-grid editor__swatches', role: 'radiogroup', 'aria-label': 'Farbe' },
      colors.map((c) => {
        const none = c.value.toLowerCase() === NEUTRAL_COLOR;
        const name = none ? 'Keine Farbe' : c.name;
        return h('button', {
          class: ['swatch', none && 'swatch--none'],
          type: 'button',
          role: 'radio',
          'aria-checked': String(c.value.toLowerCase() === value),
          'aria-label': name,
          style: none ? null : `--swatch:${c.value}`,
          onclick: () => {
            haptic('selection');
            if (c.value.toLowerCase() !== value) onPick(c.value);
            sheet.close();
          },
        }, h('span', { class: 'swatch__color' }, icon('check')), h('span', { class: 'swatch__label' }, name));
      })),
  });
  return sheet;
}

const OTHER_ENSEMBLE_EMOJIS = ['🎼', '👔', '🍰', '🚗', '🎻', '📄', '⏰', '🅿️'];

function emojiChoices(current, used) {
  let frequent = used;
  if (!frequent) {
    const counts = new Map();
    for (const x of rehearsals) {
      for (const info of x.infos ?? []) {
        if (info.emoji) counts.set(info.emoji, (counts.get(info.emoji) ?? 0) + 1);
      }
    }
    frequent = [...counts].sort((a, b) => b[1] - a[1]).map(([emoji]) => emoji);
  }
  frequent = [...new Set(frequent)];
  if (current && !frequent.includes(current) && !EMOJIS.includes(current)) frequent.unshift(current);
  const elsewhere = OTHER_ENSEMBLE_EMOJIS.filter((emoji) => !frequent.includes(emoji));
  return { frequent: [...frequent, ...elsewhere], rest: EMOJIS.filter((emoji) => !frequent.includes(emoji) && !elsewhere.includes(emoji)) };
}

export function openEmojiSheet(current, onPick, { used, stack = false } = {}) {
  const { frequent, rest } = emojiChoices(current, used);
  const pick = (emoji) => {
    haptic('selection');
    if (emoji !== current) onPick(emoji);
    sheet.close();
  };
  const cells = [...frequent, ...rest].map((emoji) => h('button', {
    class: 'editor__emoji',
    type: 'button',
    role: 'option',
    tabindex: '-1',
    'aria-selected': String(emoji === current),
    'data-emoji': emoji,
    onclick: () => pick(emoji),
  }, emoji));
  const selected = cells.find((el) => el.dataset.emoji === current) ?? cells[0];
  const shown = () => cells.filter((el) => !el.hidden);
  let active = null;
  const activate = (el, focus = false) => {
    if (active) active.tabIndex = -1;
    active = el ?? null;
    if (!active) return;
    active.tabIndex = 0;
    if (focus) active.focus();
  };

  const neighbour = (from, key) => {
    const list = shown();
    const i = list.indexOf(from);
    if (key === 'ArrowLeft') return list[i - 1];
    if (key === 'ArrowRight') return list[i + 1];
    if (key === 'Home') return list[0];
    if (key === 'End') return list.at(-1);
    const down = key === 'ArrowDown';
    const rows = list.filter((el) => (down ? el.offsetTop > from.offsetTop : el.offsetTop < from.offsetTop));
    if (!rows.length) return null;
    const tops = rows.map((el) => el.offsetTop);
    const top = down ? Math.min(...tops) : Math.max(...tops);
    return rows.filter((el) => el.offsetTop === top)
      .reduce((best, el) => (Math.abs(el.offsetLeft - from.offsetLeft) < Math.abs(best.offsetLeft - from.offsetLeft) ? el : best));
  };

  const rule = frequent.length && rest.length ? h('span', { class: 'editor__emoji-rule', 'aria-hidden': 'true' }) : null;
  const grid = h('div', {
    class: 'editor__emoji-grid',
    role: 'listbox',
    'aria-label': 'Emoji',
    onkeydown: (event) => {
      const from = event.target.closest('.editor__emoji');
      if (!from || !EMOJI_KEYS.includes(event.key)) return;
      event.preventDefault();
      const next = neighbour(from, event.key);
      if (next) activate(next, true);
      else if (event.key === 'ArrowUp') input.focus();
    },
  }, cells.slice(0, frequent.length), rule, cells.slice(frequent.length));
  const empty = h('p', { class: 'editor__emoji-empty', hidden: true });

  const filter = () => {
    const query = input.value.trim();
    const q = fold(query);
    for (const el of cells) el.hidden = Boolean(q) && !fold(EMOJI_WORDS[el.dataset.emoji] ?? '').includes(q);
    if (rule) rule.hidden = Boolean(q);
    const list = shown();
    empty.hidden = list.length > 0;
    empty.textContent = list.length ? '' : `Kein Treffer für „${query}“`;
    activate(q ? list[0] : selected);
  };

  const input = h('input', {
    class: 'search-field__input',
    type: 'search',
    placeholder: 'Suchen',
    'aria-label': 'Suchen',
    autofocus: matchMedia('(pointer: fine)').matches,
    oninput: filter,
    onkeydown: (event) => {
      if (event.key === 'ArrowDown' && active) {
        event.preventDefault();
        active.focus();
      } else if (event.key === 'Enter' && input.value.trim() && active) {
        event.preventDefault();
        pick(active.dataset.emoji);
      }
    },
  });
  activate(selected);

  const results = h('div', { class: 'editor__emoji-results' }, grid, empty);
  const sheet = openSheet({
    title: 'Emoji',
    stack,
    content: h('div', { class: 'editor__emojis' },
      h('label', { class: 'search-field' }, icon('search', { class: 'search-field__icon' }), input),
      results,
      h('div', { class: 'list-group editor__emoji-other' },
        h('button', {
          class: 'list-row list-row--action',
          type: 'button',
          onclick: () => {
            sheet.close();
            setTimeout(() => openPromptSheet({
              title: 'Anderes Emoji',
              placeholder: '🙂',
              maxlength: 8,
              confirm: 'Übernehmen',
              stack,
              onConfirm: (text) => onPick(firstGrapheme(text)),
            }), 300);
          },
        }, h('span', { class: 'list-row__lead' }, icon('smile-plus')), h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Anderes Emoji…'))))),
  });
  requestAnimationFrame(() => {
    results.style.minHeight = `${results.offsetHeight}px`;
  });
  return sheet;
}

export function openBesetzungSheet({ title = 'Register', value = [], onDone, anchor = null } = {}) {
  const chosen = new Set(value);
  const tree = sections.filter((s) => s.kind !== 'root');
  const quick = sections
    .filter((s) => s.kind === 'root' || s.kind === 'section')
    .sort((a, b) => a.depth - b.depth || a.order - b.order);
  const sentence = h('span', { class: 'editor__sentence-text' });
  const lead = h('p', { class: 'editor__sentence' }, icon('users-round'), sentence);
  const list = h('div', { class: 'list-group tree' });
  let sheet = null;

  const chips = quick.map((s) => {
    const el = chip({
      label: s.name,
      dot: s.color || null,
      pressed: false,
      onClick: () => {
        haptic('selection');
        chosen.clear();
        s.instrumentIds.forEach((x) => chosen.add(x));
        update();
      },
    });
    el.dataset.section = s.id;
    return el;
  });
  const row = chipRow(chips, { className: 'editor__quick', label: 'Schnellauswahl' });

  const stateOf = (s) => {
    const n = s.instrumentIds.filter((x) => chosen.has(x)).length;
    return n === 0 ? 'off' : n === s.instrumentIds.length ? 'on' : 'mixed';
  };

  const update = () => {
    const empty = chosen.size === 0;
    sentence.textContent = empty ? 'Kein Register' : groupsSentence([...chosen]);
    lead.classList.toggle('is-empty', empty);
    const done = sheet?.el.querySelector('.sheet__actions .button--primary');
    if (done) done.disabled = empty;
    for (const el of chips) {
      const leaves = getSection(el.dataset.section).instrumentIds;
      setPressed(el, leaves.length === chosen.size && leaves.every((x) => chosen.has(x)));
    }
    list.replaceChildren(...tree.map((s) => {
      const state = stateOf(s);
      return h('button', {
        class: 'list-row tree-row',
        type: 'button',
        role: 'checkbox',
        'aria-checked': state === 'mixed' ? 'mixed' : String(state === 'on'),
        'data-id': s.id,
        style: `--level:${s.depth - 1}`,
        onclick: () => {
          haptic('selection');
          if (state === 'on') s.instrumentIds.forEach((x) => chosen.delete(x));
          else s.instrumentIds.forEach((x) => chosen.add(x));
          update();
          list.querySelector(`[data-id="${s.id}"]`)?.focus({ preventScroll: true });
        },
      },
      h('span', { class: 'list-row__lead' },
        h('span', { class: ['check', state === 'on' && 'is-checked', state === 'mixed' && 'is-mixed'], 'aria-hidden': 'true' }, icon(state === 'mixed' ? 'minus' : 'check')),
        s.kind === 'section' ? h('span', { class: 'tree-row__emoji', 'aria-hidden': 'true' }, s.emoji) : null),
      h('span', { class: 'list-row__body' }, h('span', { class: ['list-row__title', s.kind === 'section' && 'tree__group'] }, s.name)),
      h('span', { class: 'list-row__trail' }, h('span', { class: 'list-row__value' }, String(s.memberCount))));
    }));
    refreshIcons(list);
  };

  sheet = openSheet({
    title,
    anchor,
    className: 'sheet--large',
    content: h('div', { class: 'editor__tree' }, lead, row, list),
    actions: [{
      label: 'Fertig',
      variant: 'primary',
      onClick: () => {
        if (!chosen.size) return false;
        const order = new Map(sections.map((s, i) => [s.id, i]));
        onDone([...chosen].sort((a, b) => order.get(a) - order.get(b)));
        return true;
      },
    }],
  });
  update();
  refreshIcons(sheet.el);
  return sheet;
}

/* Appearance */

const APPEARANCE = {
  palette: 'pp.palette',
  density: 'pp.density',
  motion: 'pp.motion',
  transparency: 'pp.transparency',
};

export function applyAppearance() {
  const root = document.documentElement;
  for (const [key, storageKey] of Object.entries(APPEARANCE)) {
    let value = null;
    try {
      value = localStorage.getItem(storageKey);
    } catch {}
    if (value && value !== 'default' && value !== 'system') root.dataset[key] = value;
    else delete root.dataset[key];
  }
}

export function setAppearance(key, value) {
  const storageKey = APPEARANCE[key];
  if (!storageKey) return;
  try {
    if (value == null || value === 'default' || value === 'system') localStorage.removeItem(storageKey);
    else localStorage.setItem(storageKey, value);
  } catch {}
  applyAppearance();
}

/* Tabs */

function assignTabs() {
  const fmt = (n) => (n > 99 ? '99+' : n || null);
  for (const [role, tabs] of Object.entries(TABS)) {
    for (const tab of tabs) {
      if (tab.route === '/promises') tab.badge = () => fmt(openCount(me(role).id));
      if (tab.route === '/team') {
        tab.label = ensembleLabel();
        tab.badge = () => fmt(undocumentedCount(role));
      }
    }
  }
}

/* Focus restore after re-render */

const KEY_ATTRS = ['data-focus-key', 'data-act', 'data-action', 'data-id', 'data-rehearsal', 'data-member', 'data-value', 'data-key', 'data-route', 'aria-label', 'name', 'href'];

function isTabbable(el) {
  return el.tabIndex >= 0 && !el.disabled && !el.closest('[inert], [hidden]')
    && el.getClientRects().length > 0 && getComputedStyle(el).visibility !== 'hidden';
}

function ownKeyParts(el) {
  const tag = el.tagName.toLowerCase();
  if (el.id) return [`${tag}#${CSS.escape(el.id)}`, true];
  const parts = [];
  for (const attr of KEY_ATTRS) {
    const value = el.getAttribute(attr);
    if (value) parts.push(`[${attr}=${JSON.stringify(value)}]`);
  }
  return [tag + parts.join(''), parts.length > 0];
}

function locateTrigger(el) {
  if (!el || el === document.body || !el.isConnected) return null;
  let selector = '';
  let node = el;
  for (let depth = 0; node && node !== document.body && depth < 6; depth++, node = node.parentElement) {
    const [part, keyed] = ownKeyParts(node);
    if (!keyed && node !== el) continue;
    selector = selector ? `${part} ${selector}` : part;
    let matches;
    try { matches = document.querySelectorAll(selector); } catch { return null; }
    if (matches.length === 1 && matches[0] === el) return { selector, index: 0 };
  }
  try {
    const matches = [...document.querySelectorAll(selector)];
    const index = matches.indexOf(el);
    return index === -1 ? null : { selector, index };
  } catch { return null; }
}

function relocate(info) {
  try { return document.querySelectorAll(info.selector)[info.index] ?? null; } catch { return null; }
}

function restoreFocus(info, attempt = 0) {
  const active = document.activeElement;
  if (active && active !== document.body && active.isConnected) return;
  const target = relocate(info);
  if (target && isTabbable(target)) {
    target.focus({ preventScroll: true });
    return;
  }
  if (attempt < 8) setTimeout(() => restoreFocus(info, attempt + 1), 60 + attempt * 60);
}

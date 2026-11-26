import { registerScreen, setHeader, navigate, back, rerender, openSheet, toast, haptic, h, html, icon, refreshIcons } from '../app.js';
import * as data from '../data.js';
import * as ui from '../ui.js';
import { weekdayAnchor, rowTags, dayNumber } from '../rsvp.js';
import { valueRow as valueLine } from '../value-row.js';
import { openVoiceSheet, voiceLockText, voicePath } from '../voice-sheet.js';

const team = await import('./team.js').catch(() => ({}));

/* Shared helpers */

const NBSP = ' ';
const MONTHS = ['Jan.', 'Feb.', 'März', 'Apr.', 'Mai', 'Juni', 'Juli', 'Aug.', 'Sept.', 'Okt.', 'Nov.', 'Dez.'];

export const pct = (value) => `${Math.round(value)}${NBSP}%`;

export function liveStats(rehearsalId, memberIds) {
  const ids = memberIds ? new Set(memberIds) : null;
  const counts = { attending: 0, notAttending: 0, pending: 0, total: 0 };
  for (const p of data.promises) {
    if (p.rehearsalId !== rehearsalId || (ids && !ids.has(p.memberId))) continue;
    if (!data.getMember(p.memberId)) continue;
    counts.total += 1;
    if (p.status === 'attending') counts.attending += 1;
    else if (p.status === 'not_attending') counts.notAttending += 1;
    else counts.pending += 1;
  }
  return counts;
}

export function groupOf(sectionId) {
  let section = data.getSection(sectionId);
  while (section?.parentId && section.parentId !== 'tutti' && section.parentId !== 'Bläser') {
    section = data.getSection(section.parentId);
  }
  return section;
}

export function copyText(text, message) {
  const done = () => toast(html`${icon('circle-check')} ${message}`);
  try {
    const result = navigator.clipboard?.writeText(text);
    if (result) result.then(done, done);
    else done();
  } catch {
    done();
  }
  haptic('light');
}

export function rolesOf(member) {
  return (member.roleIds ?? [])
    .map((id) => data.getRole(id))
    .filter((r) => r && !r.isDefault && !r.isSystem)
    .sort((a, b) => a.sortOrder - b.sortOrder);
}

export function teamHeader(root, ctx, actions = [], tools = []) {
  const label = team.teamTitle?.(ctx.role, ctx.path.split('?')[0]) ?? ui.ensembleLabel();
  let bar = null;
  if (typeof team.renderTeamHeader === 'function') {
    bar = team.renderTeamHeader(ctx, null, { actions, tools, root });
  } else {
    setHeader({ title: label, large: true, back: false, actions });
    const items = ctx.role === 'conductor'
      ? [{ value: '/dashboard', label: 'Übersicht' }, { value: '/attendance', label: 'Anwesenheit' }, { value: '/members', label: 'Mitglieder' }]
      : [{ value: '/attendance', label: 'Anwesenheit' }, { value: '/members', label: 'Mitglieder' }];
    const active = ctx.path.split('?')[0];
    bar = h('div', { class: 'segmented--sticky' }, ui.segmented(items, items.find((i) => active.startsWith(i.value))?.value, (path) => {
      navigate(path, { replace: true, direction: 'none' });
    }));
    root.prepend(bar);
  }
  const shown = document.querySelector('#appContent > .large-title .large-title__text')?.textContent;
  if (shown !== label) setHeader({ title: label, large: true, back: false, actions: ui.isWide() ? [] : actions });
  return bar instanceof Node ? bar : root.querySelector('.segmented--sticky');
}

/* Rehearsal rows: month chip, weekday and time, type and group */

export function spokenDate(r) {
  return [data.formatDate(r.date), ui.timeRange(r).replace(' – ', ' bis '), r.type !== 'Probe' ? r.type : '', r.groupsText].filter(Boolean).join(', ');
}

const STATES = {
  pending: ['circle-dashed', 'Offen'],
  attending: ['circle-check', 'Zugesagt'],
  not_attending: ['circle-x', 'Abgesagt'],
};

function stateMark(state) {
  const [name, label] = STATES[state] ?? STATES.pending;
  return h('span', { class: `rehearsal-row__state rehearsal-row__state--${state}`, title: label },
    icon(name), h('span', { class: 'visually-hidden' }, `${label}, `));
}

function timeText(r) {
  const [from, to] = ui.timeRange(r).split(/\s*–\s*/);
  return [h('span', { class: 'rehearsal-row__start' }, from), to ? h('span', { class: 'rehearsal-row__end' }, `–${to}`) : null];
}

export function rehearsalRow(r, { href, overlay, trail, note, state, className, label } = {}) {
  return h(href ? 'a' : 'div', {
    class: ['list-row', 'rehearsal-row', className],
    href: href ?? null,
    'data-id': r.id,
  },
  overlay ? h('a', { class: 'rehearsal-row__link', href: overlay, 'aria-label': label ?? spokenDate(r) }) : null,
  h('span', { class: 'list-row__lead' }, h('span', { class: 'day-tile', style: ui.rowTint(r) }, weekdayAnchor(r), dayNumber(r))),
  h('span', { class: 'list-row__body' },
    h('span', { class: 'list-row__title rehearsal-row__when' },
      state ? stateMark(state) : null,
      h('span', { class: 'visually-hidden' }, `${data.formatDate(r.date)}, `),
      h('span', { class: 'rehearsal-row__time' }, timeText(r))),
    h('span', { class: 'rehearsal-row__badges' }, rowTags(r)),
    note ? h('span', { class: 'list-row__sub rehearsal-row__note' }, icon('quote'), h('span', {}, note)) : null),
  trail ? h('span', { class: 'list-row__trail' }, trail) : null);
}

function sectionName(member) {
  return data.getSection(member.sectionId)?.name ?? member.sectionId;
}

function scopeMembers() {
  return data.members.filter((m) => m.canAttend || m.isConductor);
}

function shownRoles(member) {
  return member.isConductor ? member.roleIds.map((id) => data.getRole(id)).filter(Boolean) : rolesOf(member);
}

function normalizeText(value) {
  return String(value).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
}

function matches(member, query) {
  if (!query) return true;
  const section = data.getSection(member.sectionId);
  const group = groupOf(member.sectionId);
  const haystack = normalizeText([member.displayName, section?.name, section?.shortName, ...(section?.aliases ?? []), group?.name, ...(member.roles ?? [])].join(' '));
  return normalizeText(query).split(/\s+/).filter(Boolean).every((part) => haystack.includes(part));
}

function joinedText(member) {
  if (!member.joinedAt) return '–';
  const [year, month] = member.joinedAt.split('-').map(Number);
  return `${MONTHS[month - 1]} ${year}`;
}

function shortDate(iso) {
  return data.formatDate(iso).split(', ')[1] ?? data.formatDate(iso);
}

/* Members list */

const listState = { query: '', scope: null, roles: new Set(), own: null, viewer: null, searching: false };

function syncListScope(role) {
  const own = role === 'leader' ? ui.me(role).sectionId : null;
  if (listState.viewer === role) return;
  listState.viewer = role;
  listState.own = own;
  listState.scope = own ? 'own' : null;
  listState.roles = new Set();
}

function inScope(member, { scope = listState.scope, roles = listState.roles } = {}) {
  if (scope === 'own' && member.sectionId !== listState.own) return false;
  return [...roles].every((id) => member.roleIds.includes(id));
}
let listCleanup = null;

function personRow(member, role) {
  const roles = member.isConductor ? [] : shownRoles(member).filter((r) => !listState.roles.has(r.id));
  const page = access(role, member).activity || (member.isConductor && role === 'conductor');
  return h('a', {
    class: 'list-row list-row--dense person-row',
    href: `#/members/${member.id}`,
    onclick: page ? null : (event) => {
      if (event.metaKey || event.ctrlKey || event.shiftKey) return;
      event.preventDefault();
      openIdentitySheet(member);
    },
  },
  h('span', { class: 'list-row__lead' }, ui.avatar(member, 32)),
  h('span', { class: 'list-row__body' },
    h('span', { class: 'list-row__title person-row__name' },
      h('span', { class: 'person-row__text' }, member.displayName),
      ui.roleChips(roles))),
  page ? h('span', { class: 'list-row__trail' }, icon('chevron-right', { class: 'list-row__chevron' })) : null);
}

function openIdentitySheet(member) {
  haptic('light');
  const sheet = openSheet({
    className: 'sheet--identity',
    content: h('div', { class: 'identity-sheet' },
      identity(member),
      memberRows(member, { manage: false, assign: false, mail: false })),
  });
  sheet.el.setAttribute('aria-label', member.displayName);
}

function queried() {
  return scopeMembers().filter((m) => matches(m, listState.query));
}

function filteredMembers() {
  return queried().filter((m) => inScope(m));
}

function renderList(list, role, onClear) {
  const filtered = filteredMembers();
  if (!filtered.length) {
    const query = listState.query.trim();
    list.replaceChildren(ui.emptyState({
      icon: 'search-x',
      title: query ? `Kein Treffer für „${query}“` : listState.roles.size > 1 ? 'Niemand mit allen diesen Rollen' : 'Niemand mit dieser Rolle',
      action: query ? { label: 'Suche leeren', icon: 'circle-x', onClick: onClear } : null,
    }));
    refreshIcons(list);
    return;
  }
  const groups = new Map();
  const leadership = filtered.filter((m) => m.isConductor).sort((a, b) => a.displayName.localeCompare(b.displayName, 'de'));
  for (const member of filtered.filter((m) => !m.isConductor)) {
    const group = groupOf(member.sectionId);
    if (!groups.has(group.id)) groups.set(group.id, { group, members: [] });
    groups.get(group.id).members.push(member);
  }
  const ordered = [...groups.values()].sort((a, b) => a.group.order - b.group.order);
  if (leadership.length) ordered.unshift({ group: { id: 'leitung', name: 'Leitung', order: -1 }, members: leadership });
  for (const entry of ordered) {
    entry.members.sort((a, b) => {
      const sa = data.getSection(a.sectionId)?.order ?? 0;
      const sb = data.getSection(b.sectionId)?.order ?? 0;
      return sa - sb || b.roleIds.includes(2) - a.roleIds.includes(2) || a.displayName.localeCompare(b.displayName, 'de');
    });
  }
  list.replaceChildren(...ordered.map(({ group, members }) => h('section', {
    class: ['members-group', group.id !== 'leitung' && members.length < 4 && `members-group--small members-group--n${members.length}`],
    style: group.color ? `--head:${group.color}` : null,
    'aria-label': group.name,
  },
  h('h2', { class: 'section-header sticky-bar members-group__header jmd-head' },
    h('span', { class: 'section-header__title' }, groupTitle(group, members)),
    h('span', { class: 'section-header__count' }, String(members.length))),
  h('div', { class: 'list-group members-group__rows voice-grid' }, ui.voiceBlocks(members, (m) => m.sectionId, (m) => personRow(m, role))))));
  refreshIcons(list);
}

function groupTitle(group, members) {
  const voices = new Set(members.map((m) => m.sectionId));
  if (group.id === 'leitung' || voices.size !== 1) return group.name;
  const [voice] = voices;
  return voice === group.id ? group.name : voicePath(voice);
}

function roleFilter(onChange) {
  const own = listState.own ? data.getSection(listState.own) : null;
  const items = [
    ...(own ? [{ key: 'own', role: { name: own.name, icon: 'music', tagColor: groupOf(own.id)?.color ?? own.color } }] : []),
    { key: 'all', role: { name: 'Alle Mitglieder', icon: 'users-round', tagColor: 'var(--accent)' } },
    ...data.roles
      .filter((role) => !role.isDefault && !role.isSystem)
      .sort((a, b) => a.sortOrder - b.sortOrder)
      .map((role) => ({ key: String(role.id), role })),
  ];
  const isOn = (key) => {
    if (key === 'all') return !listState.scope && !listState.roles.size;
    if (key === 'own') return listState.scope === 'own';
    return listState.roles.has(Number(key));
  };
  const preview = (key) => {
    if (key === 'all') return { scope: null, roles: new Set() };
    if (key === 'own') return { scope: 'own', roles: new Set() };
    return { scope: null, roles: new Set([...listState.roles, Number(key)]) };
  };
  const choose = (key) => {
    haptic('selection');
    if (key === 'all') {
      listState.scope = null;
      listState.roles.clear();
    } else if (key === 'own') {
      listState.scope = listState.scope === 'own' ? null : 'own';
      listState.roles.clear();
    } else {
      listState.scope = null;
      const id = Number(key);
      if (!listState.roles.delete(id)) listState.roles.add(id);
    }
    sync();
    row.updateCounts();
    onChange();
  };
  const chips = items.map(({ key, role }) => {
    const el = ui.roleChip(role, { names: true, count: 0, selected: isOn(key), onClick: () => choose(key) });
    el.dataset.key = key;
    return el;
  });
  const sync = () => chips.forEach((el) => el.setAttribute('aria-pressed', String(isOn(el.dataset.key))));
  const row = ui.chipRow(chips, { className: 'members-roles', label: 'Rolle' });
  row.updateCounts = () => {
    const pool = queried();
    for (const el of chips) {
      const n = pool.filter((m) => inScope(m, preview(el.dataset.key))).length;
      el.querySelector('.role-chip__count').textContent = String(n);
    }
  };
  row.updateCounts();
  requestAnimationFrame(() => {
    const active = chips.find((el) => el.getAttribute('aria-pressed') === 'true');
    if (active && active !== chips[0] && !['own', 'all'].includes(active.dataset.key)) row.scrollLeft = active.offsetLeft - row.offsetLeft - 16;
  });
  return row;
}

function trackSticky(root) {
  const content = document.getElementById('appContent');
  const measure = () => {
    const seg = root.querySelector('.segmented--sticky');
    if (seg) root.style.setProperty('--sticky-offset', `${seg.offsetHeight}px`);
    onScroll();
  };
  const onScroll = () => {
    const seg = root.querySelector('.segmented--sticky');
    const line = seg ? seg.getBoundingClientRect().bottom : content.getBoundingClientRect().top;
    for (const head of root.querySelectorAll('.members-group__header')) {
      const top = head.getBoundingClientRect().top;
      const groupTop = head.parentElement.getBoundingClientRect().top;
      head.classList.toggle('is-stuck', Math.abs(top - line) < 1.5 && groupTop < top - 1);
    }
  };
  const observer = 'ResizeObserver' in window ? new ResizeObserver(measure) : null;
  const seg = root.querySelector('.segmented--sticky');
  if (observer && seg) observer.observe(seg);
  content.addEventListener('scroll', onScroll, { passive: true });
  requestAnimationFrame(measure);
  return () => {
    observer?.disconnect();
    content.removeEventListener('scroll', onScroll);
  };
}

let appliedQuery = null;

function renderMembers(root, params, ctx) {
  if (!ui.guard(ctx, ['leader', 'conductor'])) return;
  const perms = ui.me(ctx.role).permissions ?? [];
  const canInvite = perms.includes('can_manage_members');
  const canRoles = perms.includes('can_manage_permissions');
  const wide = ui.isWide();
  root.classList.add('screen--members');
  syncListScope(ctx.role);
  if (ctx.query?.role && appliedQuery !== ctx.path) {
    appliedQuery = ctx.path;
    listState.scope = null;
    listState.roles = new Set([Number(ctx.query.role)]);
  }

  const list = h('div', { class: 'members-list' });
  const draw = () => renderList(list, ctx.role, clearSearch);
  const chips = roleFilter(draw);
  const refresh = () => {
    chips.updateCounts();
    draw();
  };
  const closeSearch = () => {
    listState.searching = false;
    tools?.classList.remove('is-searching');
  };
  const clearSearch = () => {
    const wasEmpty = !input.value;
    input.value = '';
    listState.query = '';
    clear.hidden = wide;
    if (!wide && wasEmpty) closeSearch();
    else input.focus();
    refresh();
  };
  const clear = h('button', {
    class: 'search-field__clear',
    type: 'button',
    'aria-label': 'Suche leeren',
    hidden: !listState.query,
    onclick: clearSearch,
  }, icon('circle-x'));
  const input = h('input', {
    class: 'search-field__input',
    type: 'search',
    placeholder: 'Suchen',
    'aria-label': 'Name oder Stimme suchen',
    autocomplete: 'off',
    enterkeyhint: 'search',
    value: listState.query,
    oninput: () => {
      listState.query = input.value;
      clear.hidden = wide && !input.value;
      refresh();
    },
  });

  let tools = null;
  const search = h('label', { class: 'search-field members-search' }, icon('search', { class: 'search-field__icon' }), input, clear);
  const manageRoles = canRoles ? h('a', {
    class: 'members-action',
    href: '#/settings/roles',
    title: 'Rollen verwalten',
    'aria-label': 'Rollen verwalten',
  }, icon('shield'), h('span', { class: 'members-action__label' }, 'Rollen verwalten')) : null;
  const inviteButton = canInvite ? h('button', {
    class: 'members-action members-action--invite',
    type: 'button',
    title: 'Einladen',
    'aria-label': 'Einladen',
    'aria-haspopup': 'dialog',
    onclick: () => {
      haptic('light');
      openInvite();
    },
  }, icon('user-plus'), h('span', { class: 'members-action__label' }, 'Einladen')) : null;
  if (wide) {
    tools = h('div', { class: 'members-bar' }, search, chips, manageRoles, h('span', { class: 'members-bar__spacer' }), inviteButton);
    root.append(tools, list);
    teamHeader(root, ctx);
  } else {
    listState.searching = Boolean(listState.query);
    clear.hidden = !listState.searching;
    const find = h('button', {
      class: 'members-action members-tools__find',
      type: 'button',
      title: 'Suchen',
      'aria-label': 'Suchen',
      onclick: () => {
        haptic('light');
        listState.searching = true;
        clear.hidden = false;
        tools.classList.add('is-searching');
        input.focus();
      },
    }, icon('search'));
    tools = manageRoles || inviteButton
      ? h('div', { class: ['members-tools', 'members-tools--actions', listState.searching && 'is-searching'] },
        h('div', { class: 'members-tools__row' }, find, search, manageRoles, h('span', { class: 'members-tools__spacer' }), inviteButton),
        chips)
      : h('div', { class: ['members-tools', listState.searching && 'is-searching'] }, find, search, chips);
    input.addEventListener('blur', () => {
      setTimeout(() => {
        if (!input.value && document.activeElement !== input) closeSearch();
      }, 120);
    });
    root.append(tools, list);
    teamHeader(root, ctx, []);
  }
  draw();
  refreshIcons(root);
  listCleanup?.();
  listCleanup = trackSticky(root);
}

/* Invite sheet */

const invite = { jmdOnly: false };

function inviteCode() {
  return data.ensemble.inviteLink.split('/').pop();
}

function inviteUrl() {
  return data.ensemble.inviteLink;
}

function hashCode(text) {
  let hash = 7;
  for (const char of text) hash = (hash * 31 + char.charCodeAt(0)) | 0;
  return hash * 2654435761;
}

export async function resetInvite() {
  haptic('medium');
  const ok = await ui.openConfirmSheet({ title: 'Einladungslink zurücksetzen?', action: 'Zurücksetzen', destructive: true, icon: 'refresh-cw' });
  if (!ok) return false;
  const code = Math.abs(hashCode(inviteCode() + Date.now())).toString(16).padStart(8, '0').slice(0, 8);
  data.ensemble.inviteLink = `https://probenplaner.example/invite/${code}`;
  toast(html`${icon('circle-check')} Link zurückgesetzt`);
  return true;
}

export function openInvite() {
  const sharing = ui.linkSharing({ title: data.ensemble.name, url: inviteUrl });
  const content = h('div', { class: 'invite' },
    h('div', { class: 'list-group invite__card' },
      h('div', { class: 'list-row invite__link' },
        h('span', { class: 'list-row__lead' }, icon('link')),
        h('span', { class: 'list-row__body' },
          h('span', { class: 'list-row__title invite__url' }, 'probenplaner.example/', h('wbr'), 'invite/', h('wbr'), h('span', { class: 'invite__code' }, inviteCode())))),
      h('label', { class: 'list-row list-row--switch' },
        h('span', { class: 'list-row__lead' }, icon('shield-check')),
        h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Nur JMD-Konten')),
        h('span', { class: 'list-row__trail' }, h('input', {
          class: 'switch',
          type: 'checkbox',
          role: 'switch',
          checked: invite.jmdOnly,
          onchange: (event) => {
            invite.jmdOnly = event.target.checked;
            haptic('light');
          },
        })))),
    );
  openSheet({
    title: 'Einladen',
    content,
    actions: [
      { label: 'Teilen', icon: 'share-2', variant: 'primary', onClick: () => { sharing.share(); return false; } },
      {
        label: 'Zurücksetzen',
        icon: 'refresh-cw',
        variant: 'plain',
        onClick: async () => {
          await resetInvite();
          openInvite();
        },
      },
    ],
  });
}

/* Member detail */

const reminded = new Set();
const expanded = new Set();
let identityObserver = null;

function access(role, member) {
  const viewer = ui.me(role);
  const perms = viewer.permissions ?? [];
  const self = member.id === viewer.id;
  const mail = !self && perms.includes('can_view_member_email');
  const target = !self && !member.isConductor;
  const manage = target && perms.includes('can_manage_members');
  const assign = manage && perms.includes('can_manage_permissions');
  if (role === 'conductor') return { self, mail, manage, assign, account: !self && perms.includes('can_manage_members'), activity: !member.isConductor, contact: !self };
  const own = member.sectionId === viewer.sectionId;
  return { self, mail, manage, assign, account: false, activity: self || own, contact: !self && own };
}

function openAnswers(member) {
  return data.promises
    .filter((p) => p.memberId === member.id && p.status === 'pending')
    .map((p) => data.getRehearsal(p.rehearsalId))
    .filter((r) => r && !r.cancelled && r.end > data.NOW)
    .sort((a, b) => a.start.localeCompare(b.start));
}

function lastAnswers(member) {
  return data.promises
    .filter((p) => p.memberId === member.id && p.respondedAt && p.status !== 'pending')
    .sort((a, b) => b.respondedAt.localeCompare(a.respondedAt))
    .map((p) => ({ promise: p, rehearsal: data.getRehearsal(p.rehearsalId) }))
    .filter((x) => x.rehearsal && !x.rehearsal.cancelled)
    .slice(0, 3)
    .sort((a, b) => b.rehearsal.start.localeCompare(a.rehearsal.start));
}

function attendanceRecords(member) {
  return data.attendance
    .filter((a) => a.memberId === member.id)
    .map((a) => ({ ...a, rehearsal: data.getRehearsal(a.rehearsalId) }))
    .filter((a) => a.rehearsal)
    .sort((a, b) => b.rehearsal.start.localeCompare(a.rehearsal.start))
    .slice(0, 10)
    .reverse();
}

const REHEARSAL_BASE = {
  '/promises': (id) => `#/promises/${id}`,
  '/rehearsals': (id) => `#/rehearsals/${id}/responses`,
  '/plan': (id) => `#/plan/${id}`,
};

function rehearsalHref(r, ctx, member = null) {
  if (member && member.id !== ui.me(ctx?.role)?.id && ['leader', 'conductor'].includes(ctx?.role)) {
    const params = new URLSearchParams({ scope: member.sectionId, filter: 'open', from: 'members' });
    return `#/team/rehearsals/${r.id}/responses?${params}`;
  }
  return (REHEARSAL_BASE[ctx?.tab] ?? ((id) => `#/team/rehearsals/${id}`))(r.id);
}

function upcomingCount(member) {
  return data.promises.filter((p) => {
    if (p.memberId !== member.id) return false;
    const r = data.getRehearsal(p.rehearsalId);
    return r && !r.cancelled && r.end > data.NOW;
  }).length;
}

function moreRow(count, onClick) {
  return h('button', { class: 'list-row list-row--more member-more', type: 'button', onclick: onClick },
    h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, `${count} weitere`)),
    h('span', { class: 'list-row__trail' }, icon('chevron-down')));
}

function mailRow(member) {
  return h('div', { class: 'list-row member-mail' },
    h('a', { class: 'member-mail__link', href: `mailto:${member.email}`, 'aria-label': `E-Mail an ${member.displayName}` }),
    h('span', { class: 'list-row__lead' }, icon('mail')),
    h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title member-mail__address' },
      member.email.split('@')[0], '@', h('wbr'), member.email.split('@').slice(1).join('@'))),
    h('span', { class: 'list-row__trail' },
      h('button', {
        class: 'icon-button member-mail__copy',
        type: 'button',
        title: 'Adresse kopieren',
        'aria-label': 'E-Mail-Adresse kopieren',
        onclick: () => copyText(member.email, 'Adresse kopiert'),
      }, icon('copy'))));
}

const DOTS = {
  present: ['check', 'Anwesend'],
  late: ['clock', 'Verspätet'],
  absent: ['x', 'Fehlt'],
  excused: ['minus', 'Entschuldigt'],
};

function levelOf(rate) {
  if (rate >= 80) return 'good';
  if (rate >= 65) return 'warn';
  return 'bad';
}

function attDot(status) {
  const [name] = DOTS[status] ?? DOTS.present;
  return h('span', { class: 'member-att__dot', 'data-status': status, 'aria-hidden': 'true' }, icon(name));
}

export function fairRate(rows) {
  const present = rows.filter((a) => a.status === 'present' || a.status === 'late').length;
  return rows.length ? Math.round((present / rows.length) * 100) : 100;
}

function attendanceCard(member, ctx) {
  const records = attendanceRecords(member);
  if (!records.length) return null;
  const rate = fairRate(records);
  const present = records.filter((a) => a.status === 'present' || a.status === 'late').length;
  const range = records.length > 1 ? `${shortDate(records[0].rehearsal.date)} – ${shortDate(records.at(-1).rehearsal.date)}` : shortDate(records[0].rehearsal.date);
  return h('section', { class: 'list-group member-att', 'aria-label': `Anwesenheit ${pct(rate)}, ${present} von ${records.length}` },
    h('header', { class: 'member-att__head' },
      h('h3', { class: 'member-att__title' }, 'Anwesenheit'),
      h('span', { class: 'member-att__value' }, pct(rate))),
    h('div', { class: 'member-att__track' },
      h('ol', { class: 'member-att__dots', 'aria-label': `Letzte Proben, ${range}` },
        records.map((a) => {
          const text = `${data.formatDate(a.rehearsal.date)}: ${DOTS[a.status]?.[1] ?? ''}`;
          return h('li', { class: 'member-att__item', title: text, 'aria-label': text }, attDot(a.status));
        })),
      h('span', { class: 'member-att__range', 'aria-hidden': 'true' }, range)),
    h('ul', { class: 'member-att__legend', 'aria-hidden': 'true' },
      Object.entries(DOTS)
        .map(([status, [, label]]) => [status, label, records.filter((a) => a.status === status).length])
        .filter(([, , n]) => n)
        .map(([status, label, n]) => h('li', { class: 'member-att__key' }, attDot(status), h('span', {}, label), h('span', { class: 'member-att__n' }, String(n))))));
}

function openMemberRemind(member, due, onSent) {
  const firstName = member.displayName.split(' ')[0];
  let sent = false;
  openSheet({
    title: `Erinnerung an ${firstName} senden?`,
    className: ['sheet--remind', due.length > 4 && 'sheet--large'].filter(Boolean).join(' '),
    content: h('div', { class: 'remind-sheet' },
      h('div', { class: 'list-group' }, due.map((r) => rehearsalRow(r)))),
    actions: [
      {
        label: 'Senden',
        variant: 'primary',
        icon: 'bell-ring',
        onClick: () => {
          sent = true;
          haptic('success');
          toast(html`${icon('circle-check')} ${due.length === 1 ? '1 Erinnerung' : `${due.length} Erinnerungen`} gesendet`);
        },
      },
      { label: 'Abbrechen', variant: 'secondary' },
    ],
    onClose: () => {
      if (sent) onSent();
    },
  });
}

function remindAction(member, due) {
  if (!due.length) return null;
  const done = reminded.has(member.id);
  return h('button', {
    class: ['button', 'button--sm', done ? 'button--plain' : 'button--tinted', 'member-remind'],
    type: 'button',
    disabled: done,
    onclick: () => {
      haptic('light');
      openMemberRemind(member, due, () => {
        reminded.add(member.id);
        rerender();
      });
    },
  },
  icon(done ? 'bell-check' : 'bell-ring'),
  h('span', { class: 'button__label' }, done ? 'Erinnert' : 'Erinnern'));
}

function identity(member) {
  return h('header', { class: 'identity' },
    ui.avatar(member, 72),
    h('h2', { class: 'identity__name' }, member.displayName));
}

function rolesLock(can, role) {
  if (can.assign) return null;
  const names = data.roles.filter((r) => r.permissions.includes('can_manage_members') && r.permissions.includes('can_manage_permissions')).map((r) => r.name);
  const who = names.length ? names.join(' und ') : 'Leitung';
  return can.self && (ui.me(role).permissions ?? []).includes('can_manage_permissions')
    ? `Eigene Rollen ändert nur eine andere ${who}`
    : `Rollen vergeben nur ${who}`;
}

function memberRows(member, can, role) {
  const roles = shownRoles(member);
  const chips = ui.roleChips(roles, { names: true, max: roles.length });
  return h('div', { class: 'list-group member-info' },
    member.isConductor ? null : valueLine({
      lead: icon('music'),
      label: 'Stimme',
      value: voicePath(member.sectionId),
      onClick: can.manage ? () => openVoicePicker(member) : null,
      locked: can.manage ? null : voiceLockText(),
      ariaLabel: `Stimme: ${voicePath(member.sectionId)}`,
      act: 'voice',
    }),
    chips || can.assign ? valueLine({
      lead: icon('shield'),
      label: 'Rollen',
      value: chips ?? h('span', { class: 'value-row__empty' }, 'Keine'),
      onClick: can.assign ? () => openRolesSheet(member, role) : null,
      locked: rolesLock(can, role),
      ariaLabel: `Rollen: ${roles.map((r) => r.name).join(', ') || 'keine'}`,
      className: 'member-roles-row',
      act: 'roles',
    }) : null,
    can.mail ? mailRow(member) : null,
    valueLine({ lead: icon('calendar-check'), label: 'Dabei seit', value: joinedText(member) }));
}

function renderMemberDetail(root, params, ctx) {
  if (!ui.guard(ctx, ['leader', 'conductor'])) return;
  const role = ctx.role;
  const member = data.getMember(params.id);
  root.classList.add('screen--member');
  if (member && ui.registerColor(member.sectionId)) root.style.setProperty('--head', ui.registerColor(member.sectionId));
  if (!member) {
    setHeader({ title: 'Mitglied', back: true, large: false });
    root.append(ui.emptyState({ icon: 'user-x', title: 'Mitglied nicht gefunden' }));
    refreshIcons(root);
    return;
  }

  const can = access(role, member);
  const open = can.activity ? openAnswers(member) : [];
  const last = can.activity ? lastAnswers(member) : [];
  const showAll = expanded.has(member.id);
  const card = can.activity ? attendanceCard(member, ctx) : null;
  const wide = ui.isWide();
  const title = member.displayName;

  setHeader({ title, back: true, large: false });

  const openRows = (showAll ? open : open.slice(0, 3)).map((r) => rehearsalRow(r, {
    href: rehearsalHref(r, ctx, member),
    state: 'pending',
    trail: icon('chevron-right', { class: 'list-row__chevron' }),
  }));

  const openHead = ui.sectionHeader('Offene Antworten', { count: open.length ? `${open.length} von ${upcomingCount(member)}` : null, tag: 'h3', className: 'member-open__head jmd-head jmd-head--plain' });
  const remind = can.activity && can.contact ? remindAction(member, open.filter(ui.isDue)) : null;
  if (remind) openHead.append(remind);

  root.append(h('div', { class: 'member-col' },
    h('section', { class: 'member-person' },
      wide ? null : identity(member),
      memberRows(member, can, role)),
    card),
  h('div', { class: 'member-col' },
    ...(can.activity ? [
      openHead,
      open.length
        ? h('div', { class: 'list-group member-open' },
          openRows,
          !showAll && open.length > 3 ? moreRow(open.length - 3, () => {
            expanded.add(member.id);
            rerender();
          }) : null)
        : h('div', { class: 'list-group' },
          h('div', { class: 'list-row member-done' },
            h('span', { class: 'list-row__lead' }, icon('check')),
            h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Alles beantwortet')))),
    ] : []),

    ...(last.length ? [
      ui.sectionHeader('Letzte Antworten', { tag: 'h3', className: 'jmd-head jmd-head--plain' }),
      h('div', { class: 'list-group' }, last.map(({ promise, rehearsal }) => rehearsalRow(rehearsal, {
        href: rehearsalHref(rehearsal, ctx, member),
        note: promise.note || null,
        state: promise.status,
        trail: icon('chevron-right', { class: 'list-row__chevron' }),
      }))),
    ] : []),

    ...(can.account && !wide ? [
      ui.sectionHeader('Konto', { tag: 'h3', className: 'jmd-head jmd-head--plain' }),
      h('div', { class: 'list-group' },
        h('button', { class: 'list-row list-row--action', type: 'button', onclick: () => confirmPasswordReset(member) },
          h('span', { class: 'list-row__lead' }, icon('key-round')),
          h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Passwort zurücksetzen')))),
      h('div', { class: 'list-group member-remove' },
        h('button', { class: 'list-row list-row--destructive', type: 'button', onclick: () => confirmRemove(member) },
          h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Aus Ensemble entfernen')))),
    ] : [])));
  if (wide) {
    team.deskBar?.(root, {
      title: [ui.avatar(member, 40), h('span', { class: 'member-title__name' }, title)],
      showBack: true,
      actions: can.account ? [
        { icon: 'key-round', label: 'Passwort zurücksetzen', onClick: () => confirmPasswordReset(member) },
        { icon: 'user-minus', label: 'Aus Ensemble entfernen', destructive: true, onClick: () => confirmRemove(member) },
      ] : [],
    });
  }
  refreshIcons(root);

  const name = root.querySelector('.identity__name');
  identityObserver?.disconnect();
  if (name && 'IntersectionObserver' in window) {
    root.dataset.identityVisible = '';
    identityObserver = new IntersectionObserver(([entry]) => {
      root.toggleAttribute('data-identity-visible', entry.isIntersecting);
    }, { rootMargin: '-60px 0px 0px 0px' });
    identityObserver.observe(name);
  }
}

function openVoicePicker(member) {
  openVoiceSheet({
    value: member.sectionId,
    onSelect: (sectionId) => {
      const previous = member.sectionId;
      member.sectionId = sectionId;
      rerender();
      toast(html`${icon('music')} Stimme geändert`, {
        action: { label: 'Rückgängig', onClick: () => { member.sectionId = previous; rerender(); } },
      });
    },
  });
}

function syncRoles(member) {
  member.roleIds.sort((a, b) => (data.getRole(a)?.sortOrder ?? 0) - (data.getRole(b)?.sortOrder ?? 0));
  member.roles = member.roleIds.map((id) => data.getRole(id)).filter((r) => r && !r.isDefault).map((r) => r.name);
  member.isLeader = member.roleIds.includes(2);
}

function openRolesSheet(member, viewerRole) {
  const viewer = ui.me(viewerRole);
  const assignable = data.roles.filter((r) => !r.isSystem && !r.isDefault && data.canGrantRole(viewer, r)).sort((a, b) => a.sortOrder - b.sortOrder);
  const canManage = viewer.permissions?.includes('can_manage_permissions');
  const content = h('div', { class: 'roles-assign' },
    h('div', { class: 'list-group' }, assignable.map((r) => h('label', { class: 'list-row list-row--switch' },
      h('span', { class: 'list-row__lead' }, ui.roleChip(r)),
      h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, r.name)),
      h('span', { class: 'list-row__trail' }, h('input', {
        class: 'switch',
        type: 'checkbox',
        role: 'switch',
        'aria-label': r.name,
        checked: member.roleIds.includes(r.id),
        onchange: (event) => {
          haptic('light');
          if (event.target.checked) {
            if (!member.roleIds.includes(r.id)) member.roleIds.push(r.id);
            r.memberCount += 1;
          } else {
            member.roleIds = member.roleIds.filter((x) => x !== r.id);
            r.memberCount -= 1;
          }
          syncRoles(member);
        },
      }))))),
    canManage ? h('div', { class: 'list-group' },
      h('a', { class: 'list-row list-row--action roles-assign__manage', href: '#/settings/roles', onclick: () => sheet.close() },
        h('span', { class: 'list-row__lead' }, icon('shield')),
        h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, 'Rollen verwalten')),
        h('span', { class: 'list-row__trail' }, icon('chevron-right', { class: 'list-row__chevron' })))) : null);
  const sheet = openSheet({ title: `Rollen · ${member.displayName.split(' ')[0]}`, content, className: 'roles-sheet', actions: [{ label: 'Fertig', variant: 'primary' }], onClose: () => rerender() });
  return sheet;
}

function passwordFor(member) {
  const words = ['Kanon', 'Fermate', 'Coda', 'Tutti', 'Largo', 'Presto', 'Motiv', 'Tonika'];
  const seed = (member.id * 7919 + Date.parse(data.NOW) / 60000) % 9000;
  return `${words[member.id % words.length]}-${words[(member.id * 3 + 1) % words.length].toLowerCase()}-${1000 + Math.round(seed)}`;
}

async function confirmPasswordReset(member) {
  const ok = await ui.openConfirmSheet({
    title: 'Passwort zurücksetzen?',
    text: `${member.displayName.split(' ')[0]} kann sich nur noch mit dem neuen Passwort anmelden.`,
    action: 'Zurücksetzen',
    destructive: true,
    icon: 'key-round',
  });
  if (!ok) return;
  const password = passwordFor(member);
  haptic('success');
  openSheet({
    title: 'Neues Passwort',
    content: h('div', { class: 'password-box' }, h('code', { class: 'password-box__value' }, password)),
    actions: [
      { label: 'Kopieren', variant: 'primary', icon: 'copy', onClick: () => { copyText(password, 'Passwort kopiert'); } },
      { label: 'Fertig', variant: 'secondary' },
    ],
  });
}

async function confirmRemove(member) {
  const ok = await ui.openConfirmSheet({
    title: `${member.displayName} entfernen?`,
    text: `${member.displayName.split(' ')[0]} verliert den Zugang zum ${data.ensemble.shortName}.`,
    action: 'Entfernen',
    destructive: true,
    icon: 'user-minus',
  });
  if (!ok) return;
  const index = data.members.indexOf(member);
  if (index < 0) return;
  data.members.splice(index, 1);
  back();
  setTimeout(() => toast(html`${icon('circle-check')} ${member.displayName} entfernt`), 120);
}

registerScreen('members', {
  title: () => ui.ensembleLabel(),
  tab: '/team',
  render: renderMembers,
  onLeave() {
    listCleanup?.();
    listCleanup = null;
  },
});

for (const [name, tab] of [['member-detail', '/team'], ['promise-member', '/promises'], ['rehearsal-member', '/rehearsals'], ['plan-member', '/plan'], ['team-member', '/team']]) {
  registerScreen(name, {
    title: (params) => data.getMember(params.id)?.displayName ?? 'Mitglied',
    tab,
    render(root, params, ctx) {
      root.dataset.screen = 'member-detail';
      renderMemberDetail(root, params, ctx);
    },
    onLeave() {
      identityObserver?.disconnect();
      identityObserver = null;
    },
  });
}

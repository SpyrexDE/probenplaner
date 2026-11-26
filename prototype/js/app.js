/* Probenplaner prototype: router, state and shell helpers */

export const ROLES = ['member', 'leader', 'conductor'];
export const THEMES = ['light', 'dark', 'system'];

export const ROLE_LABELS = {
  member: 'Mitglied',
  leader: 'Stimmführung',
  conductor: 'Leitung',
};

export const ROUTES = [
  ['/login', 'login', null],
  ['/ensembles', 'ensembles', null],
  ['/promises', 'promises', '/promises'],
  ['/promises/:rid/members/:id', 'promise-member', '/promises'],
  ['/promises/:id/responses', 'responses', '/promises'],
  ['/promises/:id/attendance', 'promise-attendance', '/promises'],
  ['/promises/:id', 'promise-detail', '/promises'],
  ['/rehearsals', 'rehearsals', '/rehearsals'],
  ['/rehearsals/series', 'series', '/rehearsals'],
  ['/rehearsals/import', 'ai-import', '/rehearsals'],
  ['/rehearsals/:rid/members/:id', 'rehearsal-member', '/rehearsals'],
  ['/rehearsals/:id/responses', 'rehearsal-responses', '/rehearsals'],
  ['/rehearsals/:id/attendance', 'rehearsal-attendance', '/rehearsals'],
  ['/rehearsals/:id/edit', 'rehearsals', '/rehearsals', { edit: 'id' }],
  ['/rehearsals/:id', 'rehearsal-edit', '/rehearsals'],
  ['/plan', 'plan', '/plan'],
  ['/plan/:rid/members/:id', 'plan-member', '/plan'],
  ['/plan/:id/responses', 'plan-responses', '/plan'],
  ['/plan/:id/attendance', 'plan-attendance', '/plan'],
  ['/plan/:id/edit', 'plan-edit', '/plan'],
  ['/plan/:id', 'plan-detail', '/plan'],
  ['/team', 'team', '/team'],
  ['/dashboard', 'dashboard', '/team'],
  ['/team/rehearsals/:rid/members/:id', 'team-member', '/team'],
  ['/team/rehearsals/:id/responses', 'team-responses', '/team'],
  ['/team/rehearsals/:id/attendance', 'team-attendance', '/team'],
  ['/team/rehearsals/:id', 'team-rehearsal', '/team'],
  ['/attendance/table', 'attendance-table', '/team'],
  ['/attendance', 'attendance', '/team'],
  ['/members/:id', 'member-detail', '/team'],
  ['/members', 'members', '/team'],
  ['/manage', 'manage', '/team'],
  ['/profile', 'profile', '/profile'],
  ['/profile/:page', 'profile-page', '/profile'],
  ['/settings/roles', 'settings-roles', '/team'],
  ['/settings/sections', 'settings-sections', '/team'],
  ['/settings/places', 'settings-places', '/team'],
  ['/settings', 'settings', '/team'],
];

const REDIRECTS = [
  ['/more', '/profile'],
  ['/more/:page', '/profile/:page'],
];

export const TABS = {
  member: [
    { route: '/promises', icon: 'calendar-check', label: 'Proben' },
    { route: '/plan', icon: 'list-music', label: 'Plan' },
    { route: '/profile', icon: 'circle-user-round', label: 'Profil' },
  ],
  leader: [
    { route: '/promises', icon: 'calendar-check', label: 'Proben' },
    { route: '/plan', icon: 'list-music', label: 'Plan' },
    { route: '/team', icon: 'users-round', label: 'Orchester' },
    { route: '/profile', icon: 'circle-user-round', label: 'Profil' },
  ],
  conductor: [
    { route: '/rehearsals', icon: 'calendar-check', label: 'Proben' },
    { route: '/plan', icon: 'list-music', label: 'Plan' },
    { route: '/team', icon: 'users-round', label: 'Orchester' },
    { route: '/profile', icon: 'circle-user-round', label: 'Profil' },
  ],
};

export const SIDEBAR = {
  member: [
    { route: '/promises', icon: 'calendar-check', label: 'Proben', count: '/promises' },
    { route: '/plan', icon: 'list-music', label: 'Plan' },
  ],
  leader: [
    { route: '/promises', icon: 'calendar-check', label: 'Proben', count: '/promises' },
    { route: '/plan', icon: 'list-music', label: 'Plan' },
    { route: '/attendance', icon: 'clipboard-check', label: 'Anwesenheit', group: '/team', count: '/team' },
    { route: '/members', icon: 'users-round', label: 'Mitglieder', group: '/team' },
  ],
  conductor: [
    { route: '/rehearsals', icon: 'calendar-check', label: 'Proben' },
    { route: '/plan', icon: 'list-music', label: 'Plan' },
    { route: '/dashboard', icon: 'layout-dashboard', label: 'Übersicht', group: '/team' },
    { route: '/attendance', icon: 'clipboard-check', label: 'Anwesenheit', group: '/team', count: '/team' },
    { route: '/members', icon: 'users-round', label: 'Mitglieder', group: '/team' },
    { route: '/manage', icon: 'settings', label: 'Verwaltung', group: '/team', also: ['/settings'] },
  ],
};

const HAPTICS = {
  selection: 5,
  light: 10,
  medium: 20,
  heavy: 30,
  success: [10, 50, 10],
  warning: [20, 60, 20],
  error: [30, 50, 30, 50, 30],
};

const rootEl = document.documentElement;
const shell = {
  header: document.getElementById('appHeader'),
  content: document.getElementById('appContent'),
  tabbar: document.getElementById('appTabbar'),
  rail: document.getElementById('appRail'),
  sheets: document.getElementById('layerSheets'),
  toasts: document.getElementById('layerToasts'),
};
const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
const darkScheme = matchMedia('(prefers-color-scheme: dark)');
const desktopQuery = matchMedia('(min-width: 840px)');
const sideQuery = matchMedia('(min-width: 1200px)');
let shellUi = null;
let shellData = null;
let ensembleUi = null;

/* HTML helpers */

class SafeHTML extends String {}

const ESCAPES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

export function escapeHTML(value) {
  return String(value).replace(/[&<>"']/g, (char) => ESCAPES[char]);
}

export function raw(value) {
  return new SafeHTML(value);
}

function toHTML(value) {
  if (value == null || value === false) return '';
  if (Array.isArray(value)) return value.map(toHTML).join('');
  if (value instanceof SafeHTML) return String(value);
  return escapeHTML(value);
}

export function html(strings, ...values) {
  return raw(strings.reduce((out, str, i) => out + str + (i < values.length ? toHTML(values[i]) : ''), ''));
}

function isAttrs(value) {
  return value != null
    && typeof value === 'object'
    && !Array.isArray(value)
    && !(value instanceof Node)
    && !(value instanceof SafeHTML);
}

function toNodes(children) {
  return children
    .flat(Infinity)
    .filter((child) => child != null && child !== false && child !== true)
    .map((child) => {
      if (child instanceof Node) return child;
      if (child instanceof SafeHTML) {
        const template = document.createElement('template');
        template.innerHTML = child;
        return template.content;
      }
      return document.createTextNode(child);
    });
}

export function h(tag, attrs, ...children) {
  if (!isAttrs(attrs)) {
    children.unshift(attrs);
    attrs = {};
  }
  const el = document.createElement(tag);
  for (const [key, value] of Object.entries(attrs)) {
    if (value == null || value === false) continue;
    if (key.startsWith('on') && typeof value === 'function') {
      el.addEventListener(key.slice(2).toLowerCase(), value);
    } else if (key === 'class' || key === 'className') {
      el.className = Array.isArray(value) ? value.filter(Boolean).join(' ') : value;
    } else if (key === 'style' && typeof value === 'object') {
      Object.assign(el.style, value);
    } else if (key === 'dataset') {
      Object.assign(el.dataset, value);
    } else {
      el.setAttribute(key, value === true ? '' : value);
    }
  }
  el.append(...toNodes(children));
  return el;
}

export function icon(name, { size, class: className } = {}) {
  const classes = escapeHTML(['icon', className].filter(Boolean).join(' '));
  const dimensions = size ? ` width="${Number(size)}" height="${Number(size)}"` : '';
  return raw(`<i data-lucide="${escapeHTML(name)}" class="${classes}"${dimensions}></i>`);
}

export function refreshIcons(root = document) {
  window.lucide?.createIcons({ root });
}

/* Formatting */

const DATE_PRESETS = {
  short: { day: '2-digit', month: '2-digit' },
  numeric: { day: '2-digit', month: '2-digit', year: 'numeric' },
  medium: { weekday: 'short', day: 'numeric', month: 'short' },
  long: { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' },
};
const DAY_MS = 24 * 60 * 60 * 1000;
const formatters = new Map();

function formatter(options) {
  const key = JSON.stringify(options);
  if (!formatters.has(key)) formatters.set(key, new Intl.DateTimeFormat('de-DE', options));
  return formatters.get(key);
}

export function toDate(value) {
  if (value instanceof Date) return value;
  if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)) {
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
  }
  return new Date(value);
}

function startOfDay(date) {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();
}

export function fmtDate(value, preset = 'medium') {
  const options = typeof preset === 'string' ? DATE_PRESETS[preset] ?? DATE_PRESETS.medium : preset;
  return formatter(options).format(toDate(value));
}

export function fmtTime(value) {
  const match = typeof value === 'string' && /^(\d{1,2}):(\d{2})/.exec(value);
  if (match) return `${match[1].padStart(2, '0')}:${match[2]}`;
  return formatter({ hour: '2-digit', minute: '2-digit' }).format(toDate(value));
}

export function formatRelativeDay(value, now = new Date()) {
  const date = toDate(value);
  const diff = Math.round((startOfDay(date) - startOfDay(now)) / DAY_MS);
  if (diff === 0) return 'Heute';
  if (diff === 1) return 'Morgen';
  if (diff === -1) return 'Gestern';
  if (diff > 1 && diff < 7) return formatter({ weekday: 'long' }).format(date);
  return fmtDate(date, 'medium');
}

/* Store */

function readPref(key, allowed, fallback) {
  try {
    const value = localStorage.getItem(key);
    return allowed.includes(value) ? value : fallback;
  } catch {
    return fallback;
  }
}

function writePref(key, value) {
  try {
    localStorage.setItem(key, value);
  } catch {}
}

function createStore(initial) {
  let state = { ...initial };
  const listeners = new Set();
  return {
    get(key) {
      return key === undefined ? state : state[key];
    },
    set(key, value) {
      const patch = typeof key === 'object' ? key : { [key]: value };
      const prev = state;
      state = { ...state, ...patch };
      if (Object.keys(patch).some((k) => prev[k] !== state[k])) {
        listeners.forEach((fn) => fn(state, prev));
      }
    },
    subscribe(fn) {
      listeners.add(fn);
      return () => listeners.delete(fn);
    },
  };
}

export const store = createStore({
  role: readPref('pp.role', ROLES, 'member'),
  theme: readPref('pp.theme', THEMES, 'system'),
});

export function setRole(role) {
  if (ROLES.includes(role)) store.set('role', role);
}

export function setTheme(theme) {
  if (THEMES.includes(theme)) store.set('theme', theme);
}

function applyTheme() {
  const theme = store.get('theme');
  rootEl.dataset.theme = theme === 'system' ? (darkScheme.matches ? 'dark' : 'light') : theme;
  const color = getComputedStyle(rootEl).getPropertyValue('--theme-color').trim();
  if (color) document.querySelector('meta[name="theme-color"]')?.setAttribute('content', color);
}

store.subscribe((state, prev) => {
  if (state.theme !== prev.theme) {
    writePref('pp.theme', state.theme);
    applyTheme();
  }
  if (state.role !== prev.role) {
    writePref('pp.role', state.role);
    rootEl.dataset.role = state.role;
    const tabs = tabsFor(state.role);
    if (current && !tabs.some((tab) => tab.route === current.tab)) {
      navigate(tabs[0].route, { replace: true, direction: 'none' });
    } else {
      rerender();
    }
  }
});

/* Router */

const screens = new Map();
const scrollPositions = new Map();
let current = null;
let historyIndex = 0;
let currentVia = 'none';
let renderCount = 0;
let renderingPath = null;

export function registerScreen(name, screen) {
  screens.set(name, screen);
}

export function screenNamed(name) {
  return screens.get(name) ?? null;
}

function tabsFor(role) {
  return TABS[role] ?? TABS.member;
}

function sidebarFor(role) {
  return SIDEBAR[role] ?? SIDEBAR.member;
}

function defaultPath() {
  return tabsFor(store.get('role'))[0].route;
}

function normalize(path) {
  let value = String(path ?? '').replace(/^#/, '');
  if (!value.startsWith('/')) value = `/${value}`;
  return value.length > 1 ? value.replace(/\/+(?=\?|$)/, '') : value;
}

function matchPattern(pattern, parts) {
  const keys = pattern.split('/').filter(Boolean);
  if (keys.length !== parts.length) return null;
  const params = {};
  const matches = keys.every((key, i) => {
    if (!key.startsWith(':')) return key === parts[i];
    params[key.slice(1)] = decodeURIComponent(parts[i]);
    return true;
  });
  return matches ? params : null;
}

function redirect(fullPath) {
  const [path, search] = fullPath.split('?');
  const parts = path.split('/').filter(Boolean);
  for (const [from, to] of REDIRECTS) {
    const params = matchPattern(from, parts);
    if (!params) continue;
    const target = to.replace(/:(\w+)/g, (_, key) => encodeURIComponent(params[key] ?? ''));
    return search ? `${target}?${search}` : target;
  }
  return fullPath;
}

function matchRoute(fullPath) {
  const [path, search = ''] = fullPath.split('?');
  const parts = path.split('/').filter(Boolean);
  for (const [pattern, name, tab, fromParams = {}] of ROUTES) {
    const params = matchPattern(pattern, parts);
    if (params) {
      const query = Object.fromEntries(new URLSearchParams(search));
      for (const [key, param] of Object.entries(fromParams)) query[key] = params[param];
      return { path: fullPath, name, params, tab: tab ?? null, query };
    }
  }
  return null;
}

export function currentRoute() {
  if (!current) return null;
  const { path, name, params, query, tab } = current;
  return { path, name, params, query, tab };
}

export function navigate(path, { replace = false, direction = 'forward', scroll } = {}) {
  const target = redirect(normalize(path));
  if (!replace && current?.path === target) return;
  if (renderingPath === target) return;
  saveScroll();
  if (replace) {
    history.replaceState({ idx: historyIndex, via: currentVia }, '', `#${target}`);
  } else {
    historyIndex += 1;
    currentVia = direction;
    history.pushState({ idx: historyIndex, via: currentVia }, '', `#${target}`);
  }
  return render(direction, { replace, scroll });
}

export function back(fallback) {
  if (historyIndex > 0) {
    history.back();
    return;
  }
  const target = fallback ?? parentPath();
  if (target !== current?.path) navigate(target, { replace: true, direction: 'back' });
}

function parentPath() {
  if (!current) return defaultPath();
  const person = current.path.split('?')[0].match(/^(.+\/\d+)\/members\/\d+$/);
  if (person && matchRoute(`${person[1]}/responses`)) return `${person[1]}/responses`;
  const parent = current.path.split('?')[0].split('/').slice(0, -1).join('/');
  if (parent && matchRoute(parent)) return parent;
  return current.tab !== current.path ? current.tab : defaultPath();
}

export function rerender() {
  if (!current) return;
  saveScroll();
  return render('none');
}

function saveScroll() {
  if (current) scrollPositions.set(current.path, shell.content.scrollTop);
}

function keptScroll(entry) {
  return { top: shell.content.scrollTop, inner: entry.screen.scrollers?.(entry.root)?.map((el) => el.scrollTop) ?? [] };
}

function restoreScroll(kept, screen, root) {
  shell.content.scrollTop = kept.top;
  screen.scrollers?.(root)?.forEach((el, i) => {
    if (kept.inner[i] != null) el.scrollTop = kept.inner[i];
  });
}

function onPopState(event) {
  saveScroll();
  closeAllSheets();
  const state = event.state;
  let direction = 'none';
  if (state?.idx == null) {
    historyIndex += 1;
    currentVia = 'none';
    history.replaceState({ idx: historyIndex, via: currentVia }, '');
  } else {
    if (state.idx < historyIndex) direction = currentVia === 'none' ? 'none' : 'back';
    else if (state.idx > historyIndex) direction = state.via ?? 'forward';
    historyIndex = state.idx;
    currentVia = state.via ?? 'forward';
  }
  render(direction);
}

function screenFor(route) {
  return screens.get(route.name) ?? {
    title: 'Nicht gefunden',
    render(root) {
      root.innerHTML = html`<p>Für <code>${route.path}</code> ist noch kein Screen registriert.</p>`;
    },
  };
}

async function render(direction = 'none', { replace = false, scroll } = {}) {
  const requested = normalize(location.hash || defaultPath());
  const resolved = redirect(requested);
  if (resolved !== requested) {
    history.replaceState({ idx: historyIndex, via: currentVia }, '', `#${resolved}`);
  }
  const route = matchRoute(resolved);
  if (!route) {
    history.replaceState({ idx: historyIndex, via: currentVia }, '', `#${defaultPath()}`);
    return render('none');
  }
  const token = ++renderCount;
  const screen = screenFor(route);

  const update = async () => {
    if (token !== renderCount) return;
    const kept = replace && direction !== 'back' && scroll !== 'top' && current?.name === route.name ? keptScroll(current) : null;
    current?.screen.onLeave?.(current.root);
    const root = h('section', { class: 'screen', 'data-screen': route.name });
    current = { ...route, screen, root, tab: screen.tab ?? route.tab ?? route.path.split('?')[0] };
    shell.content.replaceChildren(root);
    renderingPath = route.path;
    applyHeaderDefaults();
    renderNav();
    try {
      await screen.render(root, route.params, {
        ...currentRoute(),
        role: store.get('role'),
        store,
        rerender,
      });
    } finally {
      renderingPath = null;
    }
    if (token !== renderCount) return;
    refreshIcons(shell.content);
    if (kept) restoreScroll(kept, screen, root);
    else shell.content.scrollTop = direction === 'forward' ? 0 : scrollPositions.get(route.path) ?? 0;
    syncHeaderScroll();
    observeColumn(root);
  };

  try {
    await transition(update, direction);
  } catch (error) {
    console.error(error);
  }
}

let activeTransition = null;

function transition(update, direction) {
  rootEl.dataset.direction = direction;
  const animate = !desktopQuery.matches && direction !== 'none' && !reducedMotion.matches && document.documentElement.dataset.motion !== 'reduced';
  if (animate && document.startViewTransition) {
    rootEl.classList.add('is-transitioning');
    const vt = document.startViewTransition(update);
    activeTransition = vt;
    vt.ready.catch(() => {});
    vt.finished.catch(() => {}).finally(() => {
      if (activeTransition === vt) rootEl.classList.remove('is-transitioning');
    });
    return vt.updateCallbackDone;
  }
  return update().then(() => {
    if (!animate) return;
    const { content } = shell;
    content.classList.remove('is-entering');
    void content.offsetWidth;
    content.classList.add('is-entering');
    afterMotion(content).then(() => content.classList.remove('is-entering'));
  });
}

function afterMotion(...elements) {
  const durations = elements.flatMap((el) => {
    const style = getComputedStyle(el);
    return `${style.transitionDuration},${style.animationDuration}`.split(',');
  });
  const ms = Math.max(0, ...durations.map((value) => parseFloat(value) * 1000 || 0));
  return new Promise((resolve) => setTimeout(resolve, ms));
}

/* Header and navigation */

let headerDefaults = {};
let largeTitle = null;
let titleAccessory = null;

export function setTitleAccessory(factory) {
  titleAccessory = typeof factory === 'function' ? factory : null;
}

let railAccessory = null;

export function setRailAccessory(factory) {
  railAccessory = typeof factory === 'function' ? factory : null;
}

function applyHeaderDefaults() {
  const path = current.path.split('?')[0];
  const isRoot = tabsFor(store.get('role')).some((tab) => tab.route === path);
  const { title } = current.screen;
  headerDefaults = {
    title: typeof title === 'function' ? title(current.params, currentRoute()) : title ?? '',
    large: isRoot,
    back: !isRoot,
    actions: [],
    leading: null,
    subtitle: '',
    accessory: undefined,
    backTo: null,
  };
  setHeader();
}

function isEnsembleChip(node) {
  return node instanceof Element && node.classList.contains('ensemble-chip');
}

export function setHeader(options = {}) {
  const { title, large, back: showBack, actions, subtitle, accessory: accessoryOption, backTo } = { ...headerDefaults, ...options };
  let { leading } = { ...headerDefaults, ...options };
  if (large && isEnsembleChip(leading)) leading = null;
  const accessory = large && accessoryOption !== false
    ? accessoryOption ?? titleAccessory?.() ?? null
    : null;

  shell.header.classList.toggle('is-large', Boolean(large));
  shell.header.replaceChildren(
    h('div', { class: ['app-header__bar', (showBack || leading) && 'has-leading'] },
      h('div', { class: 'app-header__leading' },
        showBack ? h('button', {
          class: 'app-header__back',
          type: 'button',
          'aria-label': 'Zurück',
          onclick: () => {
            haptic('light');
            back(backTo ?? undefined);
          },
        }, icon('chevron-left'), h('span', { class: 'app-header__back-label' }, 'Zurück')) : leading ?? null,
      ),
      h(large ? 'div' : 'h1', { class: 'app-header__title', 'aria-hidden': large ? 'true' : null },
        subtitle ? h('span', { class: 'app-header__title-text' }, title) : title,
        subtitle ? h('span', { class: 'app-header__subtitle' }, subtitle) : null),
      h('div', { class: 'app-header__actions' },
        (actions ?? []).filter((action) => action && !(desktopQuery.matches && action.icon === 'bell')).map((action) => h('button', {
          class: ['app-header__action', action.text && 'is-text', action.primary && 'is-primary', action.wideLabel && 'has-label'],
          type: 'button',
          'aria-label': action.text ? null : action.label,
          title: action.text ? null : action.label,
          disabled: action.disabled ?? null,
          onclick: (event) => {
            haptic('light');
            action.onClick?.(event);
          },
        },
        action.text ? action.label : icon(action.icon),
        action.wideLabel ? h('span', { class: 'app-header__action-label' }, action.label) : null,
        action.dot ? h('span', { class: 'app-header__dot', 'aria-hidden': 'true' }) : null)),
      ),
    ),
  );

  largeTitle = shell.content.querySelector(':scope > .large-title');
  if (large) {
    if (!largeTitle) {
      largeTitle = h('div', { class: 'large-title' },
        h('h1', { class: 'large-title__text' }),
        h('div', { class: 'large-title__accessory' }));
      shell.content.prepend(largeTitle);
    }
    largeTitle.querySelector('.large-title__text').textContent = title;
    largeTitle.querySelector('.large-title__accessory').replaceChildren(...(accessory ? [accessory] : []));
    largeTitle.classList.toggle('has-accessory', Boolean(accessory));
    refreshIcons(largeTitle);
  } else {
    largeTitle?.remove();
    largeTitle = null;
  }

  document.title = title ? `${title} · Probenplaner` : 'Probenplaner';
  refreshIcons(shell.header);
  syncHeaderScroll();
  if (!renderingPath) syncHeaderColumn();
}

function syncHeaderScroll() {
  const top = shell.content.scrollTop;
  shell.header.classList.toggle('is-scrolled', top > 0);
  shell.header.classList.toggle('is-collapsed', !largeTitle || top > largeTitle.offsetHeight * 0.6);
  shell.content.classList.toggle('is-past-title', top > 0 && top >= (largeTitle?.offsetHeight ?? 0) - 1);
}

function countOf(badge) {
  return (typeof badge === 'function' ? badge() : badge) || null;
}

function navItem(item) {
  const badge = countOf(item.badge);
  return h('a', {
    class: 'nav-item',
    href: `#${item.route}`,
    'data-tab': item.route,
  },
  h('span', { class: 'nav-item__icon' }, icon(item.icon), badge ? h('span', { class: 'nav-item__badge' }, badge) : null),
  h('span', { class: 'nav-item__label' }, item.label));
}

function sideItem(item) {
  return h('a', {
    class: 'nav-item',
    href: `#${item.route}`,
    'data-nav': item.route,
    'data-title': item.label,
  },
  h('span', { class: 'nav-item__icon' }, icon(item.icon)),
  h('span', { class: 'nav-item__label' }, item.label),
  item.count ? h('span', { class: 'nav-item__count', 'data-count': item.count }) : null);
}

function unreadCount(role) {
  return (shellData?.notifications ?? []).filter((n) => !n.read && (role !== 'member' || n.audience === 'all')).length;
}

function ensembleSwitch(form) {
  return ensembleUi?.navEnsemble?.(form) ?? null;
}

function railTabs(tabs) {
  return [
    ensembleSwitch('rail'),
    h('nav', { class: 'app-rail__nav', 'aria-label': 'Hauptnavigation' }, tabs.map(navItem)),
  ].filter(Boolean);
}

function sideNav(role, tabs) {
  const user = shellUi?.me(role);
  const profile = tabs.find((tab) => tab.route === '/profile');
  return [
    ensembleSwitch('side'),
    h('nav', { class: 'app-rail__nav', 'aria-label': 'Hauptnavigation' }, tabs.filter((tab) => tab !== profile).map(navItem)),
    user && profile ? h('a', { class: 'nav-item app-rail__me', href: `#${profile.route}`, 'data-tab': profile.route, 'aria-label': `${profile.label}, ${user.displayName}` },
      shellUi.avatar(user, 32),
      h('span', { class: 'nav-item__label' }, user.displayName)) : null,
  ].filter(Boolean);
}

export function navForm() {
  if (!sideQuery.matches) return 'rail';
  return rootEl.dataset.nav === 'side' || !rootEl.dataset.nav ? 'side' : 'legacy';
}

export function navListsSections() {
  return navForm() === 'legacy';
}

function sidebar(role, tabs) {
  const user = shellUi?.me(role);
  const groups = [];
  for (const item of sidebarFor(role)) {
    const last = groups.at(-1);
    if (last && last.group === (item.group ?? null)) last.items.push(item);
    else groups.push({ group: item.group ?? null, items: [item] });
  }
  return [
    h('div', { class: 'app-rail__head' },
      railAccessory?.(),
      h('button', {
        class: 'app-rail__bell',
        type: 'button',
        'aria-label': 'Mitteilungen',
        title: 'Mitteilungen',
        onclick: (event) => shellUi?.openNotificationsSheet(store.get('role'), event.currentTarget),
      }, icon('bell'), h('span', { class: 'app-rail__dot', 'aria-hidden': 'true', hidden: !unreadCount(role) }))),
    h('div', { class: 'app-rail__stat' }),
    h('nav', { class: 'app-rail__nav', 'aria-label': 'Hauptnavigation' },
      groups.map(({ group, items }) => {
        if (!group) return items.map(sideItem);
        const id = `navGroup${group.replace(/\W/g, '')}`;
        return h('div', { class: 'app-rail__group', role: 'group', 'aria-labelledby': id },
          h('span', { class: 'app-rail__group-label', id }, tabs.find((tab) => tab.route === group)?.label ?? ''),
          items.map(sideItem));
      })),
    user ? h('div', { class: 'app-rail__foot' },
      h('a', { class: 'nav-item app-rail__profile', href: '#/profile', 'data-nav': '/profile', 'data-title': 'Profil' },
        shellUi.avatar(user, 32, { ring: false }),
        h('span', { class: 'nav-item__label' }, user.displayName))) : null,
  ].filter(Boolean);
}

let navKey = '';

export function renderNav() {
  const role = store.get('role');
  const tabs = tabsFor(role);
  const form = navForm();
  const key = [role, form, Boolean(shellUi), Boolean(ensembleUi), ...tabs.map((tab) => tab.label)].join('|');
  if (key !== navKey) {
    navKey = key;
    shell.tabbar.replaceChildren(...tabs.map(navItem));
    const forms = { rail: railTabs, side: (list) => sideNav(role, list), legacy: (list) => sidebar(role, list) };
    shell.rail.replaceChildren(...forms[form](tabs));
    shell.rail.className = `app-rail is-${form}`;
    refreshIcons(shell.tabbar);
    refreshIcons(shell.rail);
  }
  updateNavCounts(role, tabs);
  setActiveNav();
}

export function renderTabbar() {
  renderNav();
}

function updateNavCounts(role, tabs) {
  const counts = new Map(tabs.map((tab) => [tab.route, countOf(tab.badge)]));
  for (const link of [...shell.tabbar.children, ...shell.rail.querySelectorAll('.nav-item[data-tab]')]) {
    const host = link.querySelector('.nav-item__icon');
    if (!host) continue;
    const value = counts.get(link.dataset.tab);
    let badge = host.querySelector('.nav-item__badge');
    if (!value) {
      badge?.remove();
      continue;
    }
    if (!badge) host.append(badge = h('span', { class: 'nav-item__badge' }));
    if (badge.textContent !== String(value)) badge.textContent = value;
  }
  for (const el of shell.rail.querySelectorAll('.nav-item__count')) {
    const value = String(counts.get(el.dataset.count) ?? '');
    if (el.textContent !== value) el.textContent = value;
  }
  const dot = shell.rail.querySelector('.app-rail__dot');
  if (dot) dot.hidden = !unreadCount(role);
  const stat = shell.rail.querySelector('.app-rail__stat');
  if (stat && shellUi?.railStat) {
    const tile = shellUi.railStat(role);
    stat.replaceChildren(...(tile ? [tile] : []));
  }
}

function tabOf(base) {
  if (current && current.path.split('?')[0] === base) return current.tab;
  return matchRoute(base)?.tab ?? base;
}

function navSection(base) {
  const items = sidebarFor(store.get('role'));
  const within = (route) => base === route || base.startsWith(`${route}/`);
  const hit = items.find((item) => [item.route, ...(item.also ?? [])].some(within));
  if (hit) return hit.route;
  const tab = tabOf(base);
  return items.find((item) => item.group === tab)?.route ?? tab;
}

export function setActiveNav(path = current?.path) {
  if (!path) return;
  const [base, search = ''] = normalize(path).split('?');
  const tab = tabOf(base);
  const from = new URLSearchParams(search).get('from');
  const section = desktopQuery.matches ? (from ? `/${from}` : navSection(base)) : null;
  for (const link of [...shell.tabbar.children, ...shell.rail.querySelectorAll('.nav-item')]) {
    const on = link.dataset.nav ? link.dataset.nav === section : link.dataset.tab === tab;
    if (on && link.getAttribute('aria-current') !== 'page') link.setAttribute('aria-current', 'page');
    else if (!on && link.hasAttribute('aria-current')) link.removeAttribute('aria-current');
  }
}

function afterPaint(fn) {
  requestAnimationFrame(() => setTimeout(fn, 0));
}

function selectTab(route) {
  haptic('selection');
  setActiveNav(route);
  if (current?.tab !== route) {
    navigate(route, { direction: 'none' });
  } else if (current.path === route) {
    if (current.screen.onRetap?.()) return;
    shell.content.scrollTo({ top: 0, behavior: reducedMotion.matches || document.documentElement.dataset.motion === 'reduced' ? 'auto' : 'smooth' });
  } else {
    navigate(route, { direction: 'back' });
  }
}

function selectNav(route) {
  const section = current ? navSection(current.path.split('?')[0]) : null;
  setActiveNav(route);
  if (section !== route) {
    afterPaint(() => navigate(route, { direction: 'none' }));
  } else if (current.path.split('?')[0] === route) {
    if (current.screen.onRetap?.()) return;
    shell.content.scrollTo({ top: 0, behavior: 'auto' });
  } else {
    afterPaint(() => navigate(route, { direction: 'back' }));
  }
}

function onLinkClick(event) {
  if (event.defaultPrevented || event.button !== 0) return;
  if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
  const link = event.target.closest('a[href^="#/"]');
  if (!link || link.target) return;
  event.preventDefault();
  const path = normalize(link.getAttribute('href'));
  if (link.dataset.nav) {
    selectNav(path);
    return;
  }
  if (link.dataset.tab) {
    selectTab(path);
    return;
  }
  navigate(path, {
    direction: link.dataset.direction || 'forward',
    replace: link.hasAttribute('data-replace'),
  });
}

let observedRoot = null;
const columnObserver = new ResizeObserver(() => syncHeaderColumn());

function observeColumn(root) {
  if (observedRoot !== root) {
    if (observedRoot) columnObserver.unobserve(observedRoot);
    observedRoot = root;
    columnObserver.observe(root);
  }
  syncHeaderColumn();
}

function syncHeaderColumn() {
  const { style } = shell.header;
  const root = current?.root;
  if (!desktopQuery.matches || !root?.isConnected) {
    style.removeProperty('--column-start');
    style.removeProperty('--column-end');
    style.removeProperty('--actions-inset');
    return;
  }
  const pane = root.querySelector('.split__list');
  const box = (pane ?? root).getBoundingClientRect();
  if (!box.width) return;
  const bar = shell.header.getBoundingClientRect();
  const inset = pane ? 16 : parseFloat(getComputedStyle(root).getPropertyValue('--gutter')) || 0;
  const action = shell.header.querySelector('.app-header__actions > :last-child');
  style.setProperty('--column-start', `${Math.round(box.left - bar.left + inset)}px`);
  style.setProperty('--column-end', `${Math.round(bar.right - box.right + inset)}px`);
  style.setProperty('--actions-inset', `${action ? inkInset(action) : 0}px`);
}

function inkInset(el) {
  const css = getComputedStyle(el);
  if (css.backgroundImage !== 'none' || css.backgroundColor !== 'rgba(0, 0, 0, 0)' || css.boxShadow !== 'none') return 0;
  const range = document.createRange();
  range.selectNodeContents(el);
  return Math.max(0, Math.round(el.getBoundingClientRect().right - range.getBoundingClientRect().right));
}

/* Sheets */

const sheetStack = [];
let sheetCount = 0;

const popoverQuery = matchMedia('(min-width: 840px) and (pointer: fine)');

function placePopover(entry, anchor) {
  const POP_GAP = 8;
  const EDGE = 12;
  const box = anchor.getBoundingClientRect();
  const { sheet } = entry;
  const width = sheet.offsetWidth;
  const height = sheet.offsetHeight;
  const maxLeft = innerWidth - width - EDGE;
  let left = Math.min(Math.max(EDGE, box.left), maxLeft);
  if (box.right - width >= EDGE && box.left > innerWidth / 2) left = box.right - width;
  let top = box.bottom + POP_GAP;
  if (top + height > innerHeight - EDGE) top = Math.max(EDGE, box.top - height - POP_GAP);
  sheet.style.left = `${Math.round(left)}px`;
  sheet.style.top = `${Math.round(top)}px`;
}

const FOCUS_KEYS = ['data-act', 'data-key', 'data-filter', 'data-id', 'data-value', 'id'];

function focusKey(el) {
  if (!(el instanceof Element) || el === document.body) return null;
  for (const attr of FOCUS_KEYS) {
    const value = el.getAttribute(attr);
    if (value) return `${el.tagName.toLowerCase()}[${attr}="${CSS.escape(value)}"]`;
  }
  const label = el.getAttribute('aria-label') ?? el.getAttribute('href');
  return label ? `${el.tagName.toLowerCase()}[${el.hasAttribute('aria-label') ? 'aria-label' : 'href'}="${CSS.escape(label)}"]` : null;
}

function focusFallback() {
  const title = document.querySelector('#appContent > .screen:last-child :is(.proben-toolbar__title, .large-title__text), #appHeader .large-title__text, #appHeader .app-header__title');
  if (!title) return;
  title.setAttribute('tabindex', '-1');
  title.focus({ preventScroll: true });
}

function restoreFocus(entry) {
  const top = sheetStack.at(-1);
  const usable = (el) => el?.isConnected && !el.closest('[inert]') && (!top || top.host.contains(el)) && el.getClientRects().length;
  if (top && document.activeElement && top.host.contains(document.activeElement)) return;
  const target = usable(entry.returnFocus) ? entry.returnFocus
    : entry.returnKey ? [...document.querySelectorAll(entry.returnKey)].find(usable) : null;
  if (target) target.focus({ preventScroll: true });
  else if (!top && (document.activeElement === document.body || !document.activeElement)) focusFallback();
}

function trapFocus(event) {
  if (event.key !== 'Tab') return;
  const sheet = event.currentTarget;
  const items = [...sheet.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')]
    .filter((el) => el.tabIndex >= 0 && el.getClientRects().length && !el.closest('[hidden], [inert]'));
  if (!items.length) return;
  const first = items[0];
  const last = items.at(-1);
  const active = document.activeElement;
  if (event.shiftKey && (active === first || active === sheet)) {
    event.preventDefault();
    last.focus();
  } else if (!event.shiftKey && (active === last || !sheet.contains(active))) {
    event.preventDefault();
    first.focus();
  }
}

export function openSheet({ title = '', content = '', actions = [], onClose, lead, className, stack = false, anchor = null } = {}) {
  const opener = document.activeElement;
  const replaced = !stack ? sheetStack.find((item) => item.host.contains(opener)) : null;
  if (!stack) closeAllSheets();
  activeToast?.dismiss();
  const titleId = `sheetTitle${++sheetCount}`;
  const body = h('div', { class: 'sheet__body' });
  if (lead) body.append(h('p', { class: 'sheet__lead' }, lead));
  if (content instanceof Node) body.append(content);
  else body.insertAdjacentHTML('beforeend', content);

  const entry = {
    onClose,
    returnFocus: replaced ? replaced.returnFocus : opener,
    returnKey: replaced ? replaced.returnKey : focusKey(opener),
  };
  const close = () => dismissSheet(entry);

  entry.sheet = h('section', {
    class: ['sheet', className],
    role: 'dialog',
    'aria-modal': 'true',
    'aria-labelledby': title ? titleId : null,
    tabindex: '-1',
  },
  h('div', { class: 'sheet__grabber', 'aria-hidden': 'true' }),
  h('header', { class: 'sheet__header' },
    h('h2', { class: 'sheet__title', id: titleId }, title),
    h('button', { class: 'sheet__close', type: 'button', 'aria-label': 'Schließen', onclick: close }, icon('x'))),
  body,
  actions.length > 0 && h('footer', { class: 'sheet__actions' },
    actions.map((action) => h('button', {
      class: `button button--${action.variant ?? 'secondary'}`,
      type: 'button',
      onclick: async (event) => {
        haptic('light');
        if ((await action.onClick?.(event)) !== false) close();
      },
    }, action.icon ? icon(action.icon) : null, h('span', { class: 'button__label' }, action.label)))));

  const popover = Boolean(anchor?.isConnected) && popoverQuery.matches;
  entry.backdrop = h('div', { class: 'sheet-backdrop', onclick: close });
  entry.host = h('div', { class: ['sheet-host', popover && 'sheet-host--popover'] }, entry.backdrop, entry.sheet);

  sheetStack.push(entry);
  shell.sheets.append(entry.host);
  syncSheetLayer();
  refreshIcons(entry.host);
  enableSwipeToClose(entry);
  entry.sheet.addEventListener('keydown', trapFocus);
  if (popover) placePopover(entry, anchor);
  void entry.host.offsetHeight;
  entry.host.classList.add('is-open');
  (entry.sheet.querySelector('[autofocus]') ?? entry.sheet).focus({ preventScroll: true });

  const setTitle = (text) => {
    entry.sheet.querySelector('.sheet__title').textContent = text;
  };
  const setBack = (onBack) => {
    body.querySelector(':scope > .sheet__back')?.remove();
    if (!onBack) return;
    const btn = h('button', { class: 'sheet__back', type: 'button', 'aria-label': 'Zurück', onclick: onBack }, icon('chevron-left'));
    body.prepend(btn);
    refreshIcons(btn);
  };

  return { el: entry.sheet, body, close, setTitle, setBack, popover };
}

export function closeSheet() {
  const top = sheetStack.at(-1);
  return top ? dismissSheet(top) : Promise.resolve();
}

function closeAllSheets() {
  [...sheetStack].reverse().forEach(dismissSheet);
}

async function dismissSheet(entry) {
  const index = sheetStack.indexOf(entry);
  if (index === -1) return;
  sheetStack.splice(index, 1);
  entry.host.inert = true;
  entry.host.classList.remove('is-open');
  entry.host.classList.add('is-closing');
  syncSheetLayer();
  await afterMotion(entry.host, entry.sheet, entry.backdrop);
  entry.host.remove();
  entry.onClose?.();
  if (!entry.returnFocus?.isConnected) await new Promise((resolve) => requestAnimationFrame(resolve));
  restoreFocus(entry);
}

function syncSheetLayer() {
  const open = sheetStack.length > 0;
  for (const el of [shell.header, shell.content, shell.tabbar, shell.rail]) el.inert = open;
  sheetStack.forEach((entry, i) => {
    entry.host.inert = i < sheetStack.length - 1;
  });
  shell.sheets.classList.toggle('has-sheet', open);
}

function enableSwipeToClose(entry) {
  const { sheet } = entry;
  let drag = null;

  const onDown = (event) => {
    if (event.button !== 0 || event.target.closest('button')) return;
    drag = { y: event.clientY, time: event.timeStamp, dy: 0 };
    event.currentTarget.setPointerCapture(event.pointerId);
    sheet.style.transition = 'none';
  };
  const onMove = (event) => {
    if (!drag) return;
    drag.dy = Math.max(0, event.clientY - drag.y);
    sheet.style.transform = `translateY(${drag.dy}px)`;
  };
  const onUp = (event) => {
    if (!drag) return;
    const { dy, time } = drag;
    const velocity = dy / Math.max(1, event.timeStamp - time);
    drag = null;
    sheet.style.transition = '';
    sheet.style.transform = '';
    if (dy > 24 && (dy > sheet.offsetHeight / 3 || velocity > 0.5)) dismissSheet(entry);
  };

  for (const handle of sheet.querySelectorAll('.sheet__grabber, .sheet__header')) {
    handle.addEventListener('pointerdown', onDown);
    handle.addEventListener('pointermove', onMove);
    handle.addEventListener('pointerup', onUp);
    handle.addEventListener('pointercancel', onUp);
  }
}

/* Toasts and haptics */

let activeToast = null;

export function toast(message, { action, duration = action ? 5000 : 3000, icon: iconName } = {}) {
  activeToast?.dismiss();
  let timer;
  const el = h('div', { class: 'toast' },
    h('span', { class: 'toast__message' }, iconName ? icon(iconName) : null, message),
    action && h('button', {
      class: 'toast__action',
      type: 'button',
      onclick: () => {
        haptic('light');
        action.onClick?.();
        dismiss();
      },
    }, action.label));

  const handle = { el, dismiss };
  async function dismiss() {
    clearTimeout(timer);
    if (activeToast === handle) activeToast = null;
    if (el.classList.contains('is-leaving')) return;
    el.classList.remove('is-visible');
    el.classList.add('is-leaving');
    await afterMotion(el);
    el.remove();
  }

  shell.toasts.append(el);
  refreshIcons(el);
  void el.offsetHeight;
  el.classList.add('is-visible');
  if (duration > 0 && Number.isFinite(duration)) timer = setTimeout(dismiss, duration);
  activeToast = handle;
  return handle;
}

export function haptic(type = 'light') {
  if (navigator.userActivation && !navigator.userActivation.hasBeenActive) return;
  try {
    navigator.vibrate?.(HAPTICS[type] ?? HAPTICS.light);
  } catch {}
}

/* Boot */

function isEditable(el) {
  return el instanceof Element && (el.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName));
}

function onKeydown(event) {
  if (event.key === 'Escape' && sheetStack.length > 0) {
    event.preventDefault();
    closeSheet();
  } else if (event.altKey && event.key === 'ArrowLeft' && !isEditable(event.target)) {
    event.preventDefault();
    back();
  }
}

async function boot() {
  history.scrollRestoration = 'manual';

  const params = new URLSearchParams(location.search);
  if (params.get('tint') === '1') document.documentElement.dataset.tint = '1';
  if (params.get('palette') === 'jeunesse') rootEl.dataset.colors = 'jeunesse';
  setRole(params.get('role'));
  setTheme(params.get('theme'));
  rootEl.dataset.role = store.get('role');
  if (['centered', 'left'].includes(params.get('shell'))) rootEl.dataset.shellWidth = params.get('shell');
  applyTheme();

  historyIndex = history.state?.idx ?? 0;
  currentVia = history.state?.via ?? 'none';
  history.replaceState({ idx: historyIndex, via: currentVia }, '');

  darkScheme.addEventListener('change', applyTheme);
  window.addEventListener('popstate', onPopState);
  document.addEventListener('click', onLinkClick);
  document.addEventListener('keydown', onKeydown);
  shell.content.addEventListener('scroll', syncHeaderScroll, { passive: true });

  desktopQuery.addEventListener('change', () => rerender());
  sideQuery.addEventListener('change', () => renderNav());
  columnObserver.observe(shell.content);

  window.pp = { store, setRole, setTheme, navigate, back, currentRoute, openSheet, closeSheet, toast };

  await import('./screens/index.js');
  [shellUi, shellData] = await Promise.all([import('./ui.js'), import('./data.js')]);
  ensembleUi = await import('./ensemble-menu.js');
  await import('./shell-nav.js');
  await render('none');
}

boot();

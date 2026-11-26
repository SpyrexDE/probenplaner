/* Auth shell: Anmelden and Ensembles */

import { TABS, haptic, h, icon, navigate, openSheet, refreshIcons, registerScreen, setRole, store, toast } from '../app.js';
import { ensemble, getMember, getSection, sections, users } from '../data.js';
import * as ui from '../ui.js';

const DEMO = [
  { role: 'member', label: 'Als Mitglied' },
  { role: 'leader', label: 'Als Stimmführung' },
  { role: 'conductor', label: 'Als Leitung' },
];
const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const INVITE_CODE = ensemble.inviteLink.split('/').pop();

let account = null;
let pendingJoin = null;

function enterShell() {
  document.documentElement.dataset.shell = 'auth';
}

function leaveShell() {
  delete document.documentElement.dataset.shell;
}

function firstTab(role) {
  return (TABS[role] ?? TABS.member)[0].route;
}

function enterApp(role) {
  const target = firstTab(role);
  if (store.get('role') !== role) setRole(role);
  if (location.hash.replace(/^#/, '').split('?')[0] !== target) navigate(target, { replace: true, direction: 'none' });
}

function brand() {
  return h('div', { class: 'auth__brand' },
    h('span', { class: 'auth__logo' }, h('img', { src: 'assets/icon.svg', alt: '', width: 44, height: 44 })),
    h('h1', { class: 'auth__wordmark' }, 'Probenplaner'));
}

function legal() {
  return h('footer', { class: 'auth__legal' },
    h('a', { href: 'https://jmd.info/datenschutz', target: '_blank', rel: 'noopener' }, 'Datenschutz'),
    h('span', { 'aria-hidden': 'true' }, '·'),
    h('a', { href: 'https://jmd.info/impressum', target: '_blank', rel: 'noopener' }, 'Impressum'));
}

function setLoading(button, loading) {
  button.classList.toggle('is-loading', loading);
  button.disabled = loading;
  button.setAttribute('aria-busy', String(loading));
}

function field({ iconName, input, trail }) {
  return h('div', { class: 'auth__field' },
    h('span', { class: 'auth__field-icon', 'aria-hidden': 'true' }, icon(iconName)),
    input,
    trail ?? null);
}

function errorLine(id) {
  return h('p', { class: 'section-footer is-error auth__error', id, role: 'alert', hidden: true },
    icon('circle-alert'), h('span', { class: 'auth__error-text' }));
}

function textInput({ type = 'text', name, placeholder, autocomplete, describedBy, value }) {
  return h('input', {
    class: 'input',
    type,
    name,
    value: value || null,
    autocomplete,
    inputmode: type === 'email' ? 'email' : null,
    autocapitalize: type === 'email' || type === 'password' ? 'off' : null,
    spellcheck: type === 'email' ? 'false' : null,
    placeholder,
    'aria-label': placeholder,
    'aria-describedby': describedBy,
  });
}

function formErrors(error, inputs) {
  const show = (message, input) => {
    error.querySelector('.auth__error-text').textContent = message;
    input?.closest('.auth__field')?.after(error);
    error.hidden = false;
    for (const el of inputs) el.removeAttribute('aria-invalid');
    input?.setAttribute('aria-invalid', 'true');
    haptic('error');
    input?.focus();
    return false;
  };
  const clear = () => {
    error.hidden = true;
    for (const el of inputs) el.removeAttribute('aria-invalid');
  };
  for (const el of inputs) el.addEventListener('input', clear);
  return { show, clear };
}

function sheetForm(...children) {
  return h('form', {
    class: 'auth__sheet',
    novalidate: true,
    onsubmit: (event) => {
      event.preventDefault();
      event.currentTarget.closest('.sheet')?.querySelector('.sheet__actions .button--primary')?.click();
    },
  }, children);
}

function emailProblem(value) {
  if (!value) return 'E-Mail fehlt';
  if (!EMAIL.test(value)) return 'E-Mail ungültig';
  return null;
}

/* Sheets */

function openResetSheet(prefill) {
  const email = textInput({ type: 'email', name: 'email', placeholder: 'E-Mail', autocomplete: 'username', describedBy: 'resetError', value: prefill });
  const error = errorLine('resetError');
  const errors = formErrors(error, [email]);
  openSheet({
    title: 'Passwort zurücksetzen',
    content: sheetForm(field({ iconName: 'mail', input: email }), error),
    actions: [{
      label: 'Link senden',
      icon: 'send',
      variant: 'primary',
      onClick: () => {
        const value = email.value.trim();
        const problem = emailProblem(value);
        if (problem) return errors.show(problem, email);
        haptic('success');
        toast(`Link an ${value} gesendet`, { icon: 'mail-check' });
        return true;
      },
    }],
  });
}

function openRegisterSheet() {
  const name = textInput({ name: 'name', placeholder: 'Name', autocomplete: 'name', describedBy: 'registerError' });
  const email = textInput({ type: 'email', name: 'email', placeholder: 'E-Mail', autocomplete: 'email', describedBy: 'registerError' });
  const password = textInput({ type: 'password', name: 'new-password', placeholder: 'Passwort', autocomplete: 'new-password', describedBy: 'registerError' });
  const error = errorLine('registerError');
  const errors = formErrors(error, [name, email, password]);
  openSheet({
    title: 'Registrieren',
    content: sheetForm(
      field({ iconName: 'user-round', input: name }),
      field({ iconName: 'mail', input: email }),
      field({ iconName: 'lock', input: password }),
      error),
    actions: [{
      label: 'Konto erstellen',
      variant: 'primary',
      onClick: () => {
        if (!name.value.trim()) return errors.show('Name fehlt', name);
        const problem = emailProblem(email.value.trim());
        if (problem) return errors.show(problem, email);
        if (!password.value) return errors.show('Passwort fehlt', password);
        account = { id: 0, displayName: name.value.trim(), avatarColor: 'var(--brand-500)' };
        if (pendingJoin) {
          completeJoin();
          return true;
        }
        haptic('success');
        navigate('/ensembles?empty=1', { replace: true, direction: 'none' });
        return true;
      },
    }],
  });
}

/* Beitritt: Stimme */

const fold = (text) => String(text).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

function voiceGroups() {
  const leaves = sections.filter((s) => s.kind === 'instrument' || (s.kind === 'section' && !s.childIds.length));
  const families = new Map();
  const solos = [];
  for (const leaf of leaves) {
    const parent = getSection(leaf.parentId);
    if (leaf.kind === 'instrument' && parent) {
      if (!families.has(parent.id)) families.set(parent.id, { section: parent, items: [] });
      families.get(parent.id).items.push(leaf);
    } else {
      solos.push(leaf);
    }
  }
  return { leaves, families: [...families.values()], solos };
}

function openVoiceSheet({ action, onChoose }) {
  const { leaves, families, solos } = voiceGroups();
  let chosen = null;
  const rows = new Map();

  const error = h('p', { class: 'section-footer is-error auth__voice-error', role: 'alert', hidden: true },
    icon('circle-alert'), h('span', {}, 'Stimme fehlt'));

  const choose = (id) => {
    haptic('selection');
    chosen = id;
    error.hidden = true;
    for (const [rowId, el] of rows) {
      const on = rowId === id;
      el.classList.toggle('is-checked', on);
      el.setAttribute('aria-checked', String(on));
      el.querySelector('.list-row__trail').innerHTML = on ? icon('check', { class: 'list-row__check' }) : '';
    }
    refreshIcons(content);
  };

  const voiceRow = (section) => {
    const el = h('button', {
      class: 'list-row',
      type: 'button',
      role: 'radio',
      'aria-checked': 'false',
      onclick: () => choose(section.id),
    },
    h('span', { class: 'list-row__body' }, h('span', { class: 'list-row__title' }, section.name)),
    h('span', { class: 'list-row__trail' }));
    el.dataset.search = fold([section.name, section.shortName, ...(section.aliases ?? [])].join(' '));
    rows.set(section.id, el);
    return el;
  };

  const blocks = [
    ...families.map(({ section, items }) => ({
      el: h('section', { class: 'me-voices__family', style: `--dot:${section.color}`, 'aria-label': section.name },
        h('h3', { class: 'me-voices__head' }, section.name),
        h('div', { class: 'list-group' }, items.map(voiceRow))),
    })),
    ...(solos.length ? [{
      el: h('section', { class: 'me-voices__family me-voices__family--solo' },
        h('div', { class: 'list-group' }, solos.map(voiceRow))),
    }] : []),
  ];

  const empty = h('p', { class: 'flow-note auth__voice-empty', hidden: true }, 'Keine Stimme gefunden');
  const filter = (query) => {
    const q = fold(query.trim());
    let shown = 0;
    for (const el of rows.values()) {
      const hit = !q || el.dataset.search.includes(q);
      el.hidden = !hit;
      if (hit) shown += 1;
    }
    for (const { el } of blocks) el.hidden = ![...el.querySelectorAll('.list-row')].some((row) => !row.hidden);
    empty.hidden = shown > 0;
  };

  const search = leaves.length > 10 ? h('label', { class: 'search-field' },
    icon('search', { class: 'search-field__icon' }),
    h('input', {
      class: 'search-field__input',
      type: 'search',
      placeholder: 'Suchen',
      'aria-label': 'Stimme suchen',
      oninput: (event) => filter(event.target.value),
    })) : null;

  const content = h('div', { class: 'auth__voices' },
    search,
    error,
    h('div', { class: 'me-voices', role: 'radiogroup', 'aria-label': 'Stimme' }, blocks.map(({ el }) => el)),
    empty);

  return openSheet({
    title: 'Deine Stimme',
    lead: `Beitritt zum ${ensemble.shortName}`,
    className: 'sheet--large me-voice-sheet me-sheet',
    content,
    actions: [{
      label: action,
      variant: 'primary',
      onClick: () => {
        if (!chosen) {
          error.hidden = false;
          haptic('error');
          return false;
        }
        onChoose(chosen);
        return true;
      },
    }],
  });
}

function completeJoin() {
  const { sectionId } = pendingJoin;
  pendingJoin = null;
  const user = users.member;
  user.sectionId = sectionId;
  const member = getMember(user.id);
  if (member && member !== user) member.sectionId = sectionId;
  haptic('success');
  toast(`${ensemble.shortName} beigetreten`, { icon: 'circle-check' });
  enterApp('member');
}

function inviteBanner(onClear) {
  const section = getSection(pendingJoin.sectionId);
  return h('div', { class: 'auth__invite' },
    h('span', { class: 'auth__invite-icon', 'aria-hidden': 'true' }, icon('ticket')),
    h('span', { class: 'auth__invite-text' },
      h('span', { class: 'auth__invite-title' }, `Beitritt zum ${ensemble.shortName}`),
      h('span', { class: 'auth__invite-sub' }, `Stimme: ${section?.name ?? ''}`)),
    h('button', {
      class: 'icon-button auth__invite-clear',
      type: 'button',
      'aria-label': 'Beitritt verwerfen',
      onclick: () => {
        pendingJoin = null;
        onClear();
      },
    }, icon('x')));
}

export function openInviteSheet({ joinable = false, signin = false, onPending } = {}) {
  const input = h('input', {
    class: 'input',
    type: 'text',
    inputmode: 'url',
    autocomplete: 'off',
    autocapitalize: 'off',
    spellcheck: 'false',
    placeholder: 'Link oder Code',
    'aria-label': 'Einladungslink oder Code',
    'aria-describedby': 'inviteError',
  });
  const error = errorLine('inviteError');
  const errors = formErrors(error, [input]);

  const paste = h('button', {
    class: 'button button--tinted button--sm auth__paste',
    type: 'button',
    onclick: async () => {
      let text = '';
      try {
        text = (await navigator.clipboard?.readText?.()) ?? '';
      } catch {
        text = '';
      }
      input.value = text.trim() || ensemble.inviteLink;
      input.dispatchEvent(new Event('input'));
      haptic('selection');
    },
  }, icon('clipboard-paste'), 'Einfügen');

  openSheet({
    title: 'Einladung einlösen',
    content: sheetForm(
      field({ iconName: 'link', input, trail: paste }),
      error),
    actions: [{
      label: joinable || signin ? 'Weiter' : 'Beitreten',
      variant: 'primary',
      onClick: () => {
        const value = input.value.trim();
        if (!value) return errors.show('Link oder Code fehlt', input);
        if (value !== ensemble.inviteLink && value !== INVITE_CODE) return errors.show('Einladung ungültig oder abgelaufen', input);
        if (joinable || signin) {
          setTimeout(() => openVoiceSheet({
            action: joinable ? 'Beitreten' : 'Weiter',
            onChoose: (sectionId) => {
              pendingJoin = { sectionId };
              if (joinable) completeJoin();
              else onPending?.();
            },
          }), 300);
          return true;
        }
        toast(`Schon Mitglied im ${ensemble.shortName}`, { icon: 'circle-check' });
        return true;
      },
    }],
  });
}

/* Anmelden */

function buildLogin(root, repaint) {
  const email = textInput({ type: 'email', name: 'email', placeholder: 'E-Mail', autocomplete: 'username', describedBy: 'loginError' });
  const password = textInput({ type: 'password', name: 'password', placeholder: 'Passwort', autocomplete: 'current-password', describedBy: 'loginError' });
  const eye = h('button', {
    class: 'icon-button auth__eye',
    type: 'button',
    'aria-label': 'Passwort anzeigen',
    'aria-pressed': 'false',
    onclick: () => {
      const visible = password.type === 'password';
      password.type = visible ? 'text' : 'password';
      eye.setAttribute('aria-pressed', String(visible));
      eye.replaceChildren(h('span', { class: 'auth__eye-icon' }, icon(visible ? 'eye-off' : 'eye')));
      refreshIcons(eye);
    },
  }, h('span', { class: 'auth__eye-icon' }, icon('eye')));

  const error = errorLine('loginError');
  const errors = formErrors(error, [email, password]);
  const submit = h('button', { class: 'button button--primary button--lg button--block auth__submit', type: 'submit' }, 'Anmelden');
  const jmd = h('button', {
    class: 'button button--secondary button--lg button--block auth__jmd',
    type: 'button',
    onclick: () => {
      haptic('light');
      setLoading(jmd, true);
      setTimeout(() => (pendingJoin ? completeJoin() : enterApp(store.get('role'))), 700);
    },
  }, h('span', { class: 'auth__jmd-mark', 'aria-hidden': 'true' }, h('img', { src: 'assets/jmd.png', alt: '', width: 20, height: 20 })), h('span', { class: 'button__label' }, 'Mit JMD anmelden'));

  const form = h('form', {
    class: 'auth__form',
    novalidate: true,
    onsubmit: (event) => {
      event.preventDefault();
      const address = email.value.trim().toLowerCase();
      const problem = emailProblem(address);
      if (problem) return errors.show(problem, email);
      if (!password.value) return errors.show('Passwort fehlt', password);
      errors.clear();
      haptic('light');
      setLoading(submit, true);
      const match = Object.values(users).find((u) => u.email?.toLowerCase() === address);
      setTimeout(() => (pendingJoin ? completeJoin() : enterApp(match?.role ?? store.get('role'))), 700);
      return true;
    },
  },
  field({ iconName: 'mail', input: email }),
  field({ iconName: 'lock', input: password, trail: eye }),
  error,
  h('div', { class: 'auth__forgot' },
    h('button', {
      class: 'button button--plain button--sm',
      type: 'button',
      onclick: () => openResetSheet(email.value.trim()),
    }, 'Passwort vergessen?')),
  submit,
  h('hr', { class: 'auth__divider' }),
  jmd,
  h('button', {
    class: 'button button--plain auth__register',
    type: 'button',
    onclick: openRegisterSheet,
  }, 'Registrieren'),
  pendingJoin ? null : h('button', {
    class: 'button button--plain auth__register',
    type: 'button',
    onclick: () => openInviteSheet({ signin: true, onPending: repaint }),
  }, 'Einladungslink einlösen'));

  const demo = (id) => h('section', { class: 'auth__demo', 'aria-labelledby': id },
    ui.sectionHeader('Ohne Konto ausprobieren', { id }),
    h('div', { class: 'list-group' },
      DEMO.map(({ role, label }) => {
        const user = users[role];
        return h('button', {
          class: 'list-row list-row--2line',
          type: 'button',
          onclick: () => {
            haptic('light');
            enterApp(role);
          },
        },
        h('span', { class: 'list-row__lead' }, ui.avatar(user, 36)),
        h('span', { class: 'list-row__body' },
          h('span', { class: 'list-row__title' }, label),
          h('span', { class: 'list-row__sub' }, [user.displayName, ui.sectionOf(user)].filter(Boolean).join(' · '))),
        h('span', { class: 'list-row__trail', 'aria-hidden': 'true' }, icon('chevron-right', { class: 'list-row__chevron' })));
      })));

  root.append(h('div', { class: 'auth auth--login' },
    h('aside', { class: 'auth__panel' },
      h('span', { class: 'auth__panel-logo', 'aria-hidden': 'true' }, h('img', { src: 'assets/icon.svg', alt: '', width: 64, height: 64 })),
      h('p', { class: 'auth__panel-wordmark' }, 'Probenplaner'),
      pendingJoin ? null : demo('demoTitleWide')),
    h('div', { class: 'auth__main' },
      h('div', { class: 'auth__column' },
        h('div', { class: 'auth__stack' },
          brand(),
          pendingJoin ? inviteBanner(repaint) : null,
          h('div', { class: 'auth__card' }, form),
          pendingJoin ? null : demo('demoTitle')),
        legal()))));
  refreshIcons(root);
}

function renderLogin(root, params, ctx) {
  enterShell();
  root.classList.add('auth-screen');
  const repaint = () => {
    root.replaceChildren();
    buildLogin(root, repaint);
  };
  buildLogin(root, repaint);
  if (ctx?.query?.invite === INVITE_CODE && !pendingJoin) {
    setTimeout(() => openVoiceSheet({
      action: 'Weiter',
      onChoose: (sectionId) => {
        pendingJoin = { sectionId };
        repaint();
      },
    }), 250);
  }
}

/* Ensembles */

function renderEnsembles(root, params, ctx) {
  const role = ctx.role;
  const empty = ctx.query.empty === '1' || history.state?.idx > 0;
  if (!empty) {
    enterApp(role);
    return;
  }
  enterShell();
  root.classList.add('auth-screen');
  const user = account ?? ui.me(role);

  const content = ui.emptyState({
    icon: 'users-round',
    title: 'Noch in keinem Ensemble',
    action: { label: 'Einladungslink einlösen', icon: 'link', onClick: () => openInviteSheet({ joinable: true }) },
  });

  root.append(h('div', { class: 'auth auth--ensembles' },
    h('div', { class: 'auth__topbar' },
      h('span', { class: 'auth__account' },
        account ? ui.avatar(account, 32) : ui.avatar(user, 32),
        h('span', { class: 'auth__account-name' }, user.displayName)),
      h('button', {
        class: 'button button--plain button--sm auth__logout',
        type: 'button',
        onclick: () => {
          account = null;
          navigate('/login', { replace: true, direction: 'none' });
        },
      }, icon('log-out'), 'Abmelden')),
    h('div', { class: 'auth__column auth__column--wide' },
      brand(),
      content,
      legal())));
}

registerScreen('login', { title: 'Anmelden', render: renderLogin, onLeave: leaveShell });
registerScreen('ensembles', { title: 'Ensembles', render: renderEnsembles, onLeave: leaveShell });

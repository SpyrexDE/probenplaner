/* Placeholder for every route until its real screen exists */

import { h, registerScreen } from '../app.js';

const SCREENS = [
  ['promises', 'Proben', '/promises'],
  ['promise-detail', 'Probe', '/promises'],
  ['dashboard', 'Übersicht', '/team'],
  ['rehearsals', 'Proben', '/rehearsals'],
  ['attendance', 'Anwesenheit', '/team'],
  ['plan', 'Plan', '/plan'],
  ['members', 'Mitglieder', '/team'],
  ['manage', 'Verwaltung', '/team'],
  ['settings', 'Einstellungen', '/team'],
  ['profile', 'Profil', '/profile'],
];

for (const [name, title, tab] of SCREENS) {
  registerScreen(name, {
    title,
    tab,
    render(root, params, ctx) {
      root.append(h('div', { class: 'empty-state' },
        h('h2', { class: 'empty-state__title' }, title),
        h('code', null, `#${ctx.path}`)));
    },
  });
}

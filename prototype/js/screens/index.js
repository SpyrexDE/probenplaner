/* Screen modules: placeholder first, real screens replace it; a missing file never blocks boot.
   settings.js registers 'settings', 'settings-roles', 'settings-sections' and 'manage'. */

import '../ui.js';
import './placeholder.js';

const SCREENS = [
  'promises', 'promise-detail', 'responses', 'rehearsals', 'series', 'ai-import', 'rehearsal-edit',
  'team', 'dashboard', 'attendance', 'members', 'plan', 'auth', 'profile', 'settings',
];

const results = await Promise.allSettled(SCREENS.map((name) => import(`./${name}.js`)));
results.forEach((result, i) => {
  if (result.status === 'rejected' && !/Failed to fetch|Importing a module script failed|error loading dynamically imported module/i.test(String(result.reason?.message))) {
    console.warn(`Screen ${SCREENS[i]} konnte nicht geladen werden:`, result.reason);
  }
});

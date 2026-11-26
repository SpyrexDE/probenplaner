export const TODAY = '2026-10-27';
export const NOW = '2026-10-27T20:10:00';
const SEED_DAY = '2026-10-24';

// Hilfsfunktionen

const WEEKDAYS = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
const WEEKDAYS_LONG = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
const MONTHS = ['Jan.', 'Feb.', 'März', 'Apr.', 'Mai', 'Juni', 'Juli', 'Aug.', 'Sept.', 'Okt.', 'Nov.', 'Dez.'];
const MONTHS_LONG = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

const pad = (n) => String(n).padStart(2, '0');
const dateParts = (iso) => iso.slice(0, 10).split('-').map(Number);
const utcDay = (iso) => {
  const [y, m, d] = dateParts(iso);
  return Date.UTC(y, m - 1, d);
};
const addDays = (iso, n) => {
  const t = new Date(utcDay(iso) + n * 86400000);
  return `${t.getUTCFullYear()}-${pad(t.getUTCMonth() + 1)}-${pad(t.getUTCDate())}`;
};
const daysBetween = (a, b) => Math.round((utcDay(b) - utcDay(a)) / 86400000);
const weekdayIndex = (iso) => new Date(utcDay(iso)).getUTCDay();

const hash = (...parts) => {
  let h = 2166136261;
  for (const p of parts) {
    h ^= p;
    h = Math.imul(h, 16777619);
    h ^= h >>> 15;
    h = Math.imul(h, 2246822507);
  }
  h ^= h >>> 13;
  h = Math.imul(h, 3266489909);
  h ^= h >>> 16;
  return (h >>> 0) / 4294967296;
};
const pick = (list, h) => list[Math.floor(h * list.length)];

const translit = (s) =>
  s
    .toLowerCase()
    .replace(/ä/g, 'ae')
    .replace(/ö/g, 'oe')
    .replace(/ü/g, 'ue')
    .replace(/ß/g, 'ss')
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '');

const tally = (values) =>
  Object.entries(
    values.reduce((acc, v) => {
      acc[v] = (acc[v] ?? 0) + 1;
      return acc;
    }, {})
  )
    .map(([name, count]) => ({ name, count }))
    .sort((a, b) => b.count - a.count || a.name.localeCompare(b.name, 'de'));

// Farben

export const colors = [
  { value: '#e5e7eb', name: 'Weiß' },
  { value: '#3b82f6', name: 'Blau' },
  { value: '#10b981', name: 'Grün' },
  { value: '#f59e0b', name: 'Gelb' },
  { value: '#ef4444', name: 'Rot' },
  { value: '#8b5cf6', name: 'Lila' },
  { value: '#f97316', name: 'Orange' },
  { value: '#ec4899', name: 'Pink' },
  { value: '#14b8a6', name: 'Türkis' },
  { value: '#6366f1', name: 'Indigo' },
  { value: '#6b7280', name: 'Grau' },
  { value: '#475569', name: 'Schiefer' },
];

const AVATAR_COLORS = colors.filter((c) => c.value !== '#e5e7eb' && c.value !== '#6b7280').map((c) => c.value);

// Register

const SECTION_DEFS = [
  ['tutti', 'Tutti', null, null, 'root', '🎼', '#6b7280', []],
  ['Streicher', 'Streicher', null, 'tutti', 'section', '🎻', '#478cf4', []],
  ['Violine_1', 'Violine 1', 'Vl 1', 'Streicher', 'instrument', '🎻', null, []],
  ['Violine_2', 'Violine 2', 'Vl 2', 'Streicher', 'instrument', '🎻', null, []],
  ['Bratsche', 'Bratsche', 'Vla', 'Streicher', 'instrument', '🎻', null, ['Viola', 'Bratschen']],
  ['Cello', 'Cello', 'Vc', 'Streicher', 'instrument', '🎻', null, ['Violoncello', 'Celli']],
  ['Kontrabass', 'Kontrabass', 'Kb', 'Streicher', 'instrument', '🎻', null, ['Kontrabässe']],
  ['Bläser', 'Bläser', null, 'tutti', 'section', '🎺', '#7c3aed', []],
  ['Holzbläser', 'Holzbläser', null, 'Bläser', 'section', '🪈', '#0d9488', []],
  ['Flöte', 'Flöte', 'Fl', 'Holzbläser', 'instrument', '🪈', null, ['Flöten']],
  ['Oboe', 'Oboe', 'Ob', 'Holzbläser', 'instrument', '🪈', null, ['Oboen']],
  ['Klarinette', 'Klarinette', 'Kl', 'Holzbläser', 'instrument', '🪈', null, ['Klarinetten']],
  ['Fagott', 'Fagott', 'Fg', 'Holzbläser', 'instrument', '🪈', null, ['Fagotte']],
  ['Blechbläser', 'Blechbläser', null, 'Bläser', 'section', '🎺', '#d97706', []],
  ['Horn', 'Horn', 'Hn', 'Blechbläser', 'instrument', '🎺', null, []],
  ['Trompete', 'Trompete', 'Trp', 'Blechbläser', 'instrument', '🎺', null, ['Trompeten']],
  ['Posaune', 'Posaune', 'Pos', 'Blechbläser', 'instrument', '🎺', null, ['Posaunen']],
  ['Tuba', 'Tuba', 'Tb', 'Blechbläser', 'instrument', '🎺', null, ['Tuben']],
  ['Schlagwerk', 'Schlagwerk', 'Schl', 'tutti', 'section', '🥁', '#ec4899', []],
  ['Harfe', 'Harfe', 'Hfe', 'tutti', 'section', '🎵', '#6366f1', ['Harfen']],
];

export const sectionColors = [
  { value: '#478cf4', name: 'Blau' },
  { value: '#6366f1', name: 'Indigo' },
  { value: '#7c3aed', name: 'Violett' },
  { value: '#ec4899', name: 'Pink' },
  { value: '#ef4444', name: 'Rot' },
  { value: '#f97316', name: 'Orange' },
  { value: '#d97706', name: 'Bernstein' },
  { value: '#10b981', name: 'Grün' },
  { value: '#0d9488', name: 'Türkis' },
  { value: '#6b7280', name: 'Grau' },
];

const SECTION_NAME = Object.fromEntries(SECTION_DEFS.map((d) => [d[0], d[1]]));
const childrenOf = (id) => SECTION_DEFS.filter((d) => d[3] === id).map((d) => d[0]);
const leavesOf = (id) => {
  const kids = childrenOf(id);
  return kids.length ? kids.flatMap(leavesOf) : [id];
};

// Rollen

const ATTEND = ['can_attend_rehearsals', 'can_view_schedule'];

const role = (id, name, tagColor, icon, sortOrder, permissions, flags = {}) => ({
  id,
  name,
  tagColor,
  icon,
  isSystem: false,
  isDefault: false,
  isSelfAssignable: false,
  sortOrder,
  permissions,
  ...flags,
});

const ROLE_DEFS = [
  role(
    1,
    'Leitung',
    '#478cf4',
    'wand-sparkles',
    0,
    [
      'can_view_own_section_stats',
      'can_view_parent_section_stats',
      'can_view_all_section_stats',
      'can_view_members',
      'can_view_member_email',
      'can_manage_rehearsals',
      'can_manage_members',
      'can_manage_permissions',
      'can_manage_ensemble',
      'can_manage_attendance_own_section',
      'can_manage_attendance_parent_section',
      'can_manage_attendance_all',
    ],
    { isSystem: true }
  ),
  role(2, 'Stimmführung', '#f59e0b', 'crown', 10, [
    ...ATTEND,
    'can_view_own_section_stats',
    'can_view_members',
    'can_manage_attendance_own_section',
  ]),
  role(3, 'Konzertmeister', '#ec4899', 'star', 5, [
    ...ATTEND,
    'can_view_parent_section_stats',
    'can_view_members',
    'can_manage_attendance_parent_section',
  ]),
  role(4, 'Vorstand', '#ef4444', 'briefcase', 20, [...ATTEND, 'can_view_members', 'can_view_member_email', 'can_manage_members', 'can_view_all_section_stats']),
  role(5, 'Notenwart', '#14b8a6', 'file-music', 30, [...ATTEND, 'can_view_members']),
  role(7, 'Kammermusik', '#8b5cf6', 'piano', 40, ATTEND, { isSelfAssignable: true }),
  role(6, 'Mitglied', '#10b981', 'tag', 100, ATTEND, { isDefault: true }),
];

export const BASE_PERMISSIONS = ATTEND;

export function grantsRights(role) {
  return (role?.permissions ?? []).some((p) => !BASE_PERMISSIONS.includes(p));
}

export function isSelfAssignable(role) {
  return Boolean(role?.isSelfAssignable) && !role.isSystem && !role.isDefault && !grantsRights(role);
}

export function canGrantRole(viewer, role) {
  const own = viewer?.permissions ?? [];
  if (!own.includes('can_manage_members') || !own.includes('can_manage_permissions')) return false;
  if (role.isSystem || role.isDefault) return false;
  return role.permissions.every((p) => BASE_PERMISSIONS.includes(p) || own.includes(p));
}

const VOICE_SCOPED = [
  'can_view_own_section_stats',
  'can_view_parent_section_stats',
  'can_manage_attendance_own_section',
  'can_manage_attendance_parent_section',
];

export function rightsOf(person) {
  return [...new Set((person?.roleIds ?? []).flatMap((id) => roles.find((r) => r.id === id)?.permissions ?? []))];
}

export function canChangeOwnVoice(person) {
  const own = rightsOf(person);
  return own.includes('can_manage_members') || !own.some((p) => VOICE_SCOPED.includes(p));
}

export function voiceManagers() {
  return roles.filter((r) => r.permissions.includes('can_manage_members')).sort((a, b) => a.sortOrder - b.sortOrder).map((r) => r.name);
}

const ROLE_ID = Object.fromEntries(ROLE_DEFS.map((r) => [r.name, r.id]));
const permissionsOf = (roleIds) => [
  ...new Set(roleIds.flatMap((id) => ROLE_DEFS.find((r) => r.id === id).permissions)),
];

// Mitglieder

const ROSTER = [
  ['Stefan Eberhardt', null, ['Leitung']],
  ['Julia Reinhardt', null, ['Leitung']],
  ['Charlotte Brandt', 'Violine_1', ['Stimmführung', 'Konzertmeister']],
  ['Jonas Weidner', 'Violine_1'],
  ['Mei-Lin Zhang', 'Violine_1', ['Kammermusik']],
  ['Leon Kowalski', 'Violine_1'],
  ['Amelie Fischer', 'Violine_1'],
  ['Deniz Aydin', 'Violine_1'],
  ['Sophie Marquardt', 'Violine_1'],
  ['Tim Lehmann', 'Violine_1'],
  ['Katharina Roth', 'Violine_2', ['Stimmführung']],
  ['Lena Hartmann', 'Violine_2'],
  ['Mateo Rossi', 'Violine_2'],
  ['Hannah Schuster', 'Violine_2', ['Vorstand']],
  ['Minh Anh Nguyen', 'Violine_2'],
  ['Felix Neumann', 'Violine_2'],
  ['Pauline Vogt', 'Violine_2'],
  ['Sebastian Krämer', 'Bratsche', ['Stimmführung']],
  ['Ida Sommer', 'Bratsche', ['Notenwart']],
  ['Omar Haddad', 'Bratsche'],
  ['Greta Pohl', 'Bratsche', ['Kammermusik']],
  ['Lukas Brenner', 'Bratsche'],
  ['Marlene Ostermann', 'Cello', ['Stimmführung']],
  ['Ben Schulte', 'Cello', ['Kammermusik']],
  ['Anastasia Volkova', 'Cello'],
  ['Jakob Engel', 'Cello', ['Vorstand']],
  ['Fatma Çelik', 'Cello'],
  ['Matthias Dreyer', 'Kontrabass', ['Stimmführung', 'Vorstand']],
  ['Clemens Wolff', 'Kontrabass'],
  ['Rosa Navarro', 'Kontrabass'],
  ['Alina Berger', 'Flöte', ['Stimmführung']],
  ['Paul Henning', 'Flöte', ['Kammermusik']],
  ['Sarah Lindner', 'Flöte', ['Notenwart']],
  ['Tobias Frey', 'Oboe', ['Stimmführung']],
  ['Elif Kaya', 'Oboe', ['Vorstand']],
  ['Moritz Albrecht', 'Klarinette', ['Stimmführung']],
  ['Julia Sandmann', 'Klarinette'],
  ['Emre Şahin', 'Klarinette'],
  ['Valentin Lorenz', 'Fagott', ['Stimmführung']],
  ['Johanna Mayer', 'Fagott', ['Kammermusik']],
  ['Benedikt Auer', 'Horn', ['Stimmführung']],
  ['Mia Santoro', 'Horn'],
  ['Jan Ludwig', 'Horn'],
  ['Nele Thomsen', 'Horn', ['Kammermusik']],
  ['Aaron Beck', 'Horn'],
  ['David Stein', 'Trompete', ['Stimmführung']],
  ['Lisa Wagner', 'Trompete'],
  ['Kerem Öztürk', 'Trompete'],
  ['Niklas Pfeiffer', 'Posaune', ['Stimmführung']],
  ['Anna Kowalczyk', 'Posaune'],
  ['Robin Maurer', 'Posaune'],
  ['Henrik Jansen', 'Tuba', ['Vorstand']],
  ['Cem Doğan', 'Schlagwerk', ['Stimmführung']],
  ['Vanessa Hoffmann', 'Schlagwerk'],
  ['Tom Berndt', 'Schlagwerk'],
  ['Eleonora Marchetti', 'Harfe'],
];

const JOINED = {
  'Stefan Eberhardt': '2018-09-01',
  'Julia Reinhardt': '2023-01-09',
  'Elif Kaya': '2026-09-08',
  'Rosa Navarro': '2026-10-19',
};
const PUSH_MINUTES = { 'Lena Hartmann': 90, 'Benedikt Auer': 60, 'Stefan Eberhardt': null };

const baseMembers = ROSTER.map(([displayName, sectionId, roleNames = []], i) => {
  const id = i + 1;
  const words = displayName.split(' ');
  const isConductor = roleNames.includes('Leitung');
  const roleIds = isConductor ? [ROLE_ID.Leitung] : [ROLE_ID.Mitglied, ...roleNames.map((n) => ROLE_ID[n])];
  const avatarSeed = Math.floor(hash(id, 11) * AVATAR_COLORS.length);
  return {
    id,
    displayName,
    initials: (words[0][0] + words[words.length - 1][0]).toUpperCase(),
    email: `${words.map(translit).join('.')}@${pick(['example.org', 'example.com', 'example.net'], hash(id, 12))}`,
    sectionId,
    roleIds,
    roles: roleNames,
    isLeader: roleNames.includes('Stimmführung'),
    isConductor,
    canAttend: permissionsOf(roleIds).includes('can_attend_rehearsals'),
    joinedAt: JOINED[displayName] ?? addDays('2019-09-01', Math.floor(hash(id, 13) * 2500)),
    avatarSeed,
    avatarColor: AVATAR_COLORS[avatarSeed],
    pushReminderMinutes:
      displayName in PUSH_MINUTES
        ? PUSH_MINUTES[displayName]
        : hash(id, 14) < 0.5
          ? null
          : pick([30, 60, 90, 120], hash(id, 15)),
  };
});
const MEMBER = new Map(baseMembers.map((m) => [m.id, m]));
const leaderOfSection = Object.fromEntries(baseMembers.filter((m) => m.isLeader).map((m) => [m.sectionId, m.id]));

// Proben

const joinNames = (names) =>
  names.length > 1 ? `${names.slice(0, -1).join(', ')} und ${names[names.length - 1]}` : names[0];

const TUTTI = { groups: ['tutti'], groupsText: 'Tutti' };
const OHNE_HARFE = { groups: ['Streicher', 'Bläser', 'Schlagwerk'], groupsText: 'Tutti ohne Harfe' };

const tue = (d, tags = [], o = {}) => ({
  d,
  from: '19:30',
  to: '22:00',
  tags: ['regulär', ...tags],
  ...OHNE_HARFE,
  ...o,
});

const simplePlan = (a, b) => [
  ['19:30', 'Stimmen & Einspielen'],
  ['19:40', a],
  ['20:45', 'Pause'],
  ['21:00', b],
  ['21:50', 'Organisatorisches'],
];

const splitPlan = (a, b, c) => [
  ['19:30', 'Stimmen & Einspielen'],
  ['19:40', a],
  ['20:45', 'Pause'],
  ['21:00', b, ['Streicher']],
  ['21:00', `${c} (Raum 2)`, ['Bläser']],
  ['21:35', 'Zusammenführung'],
  ['21:50', 'Organisatorisches'],
];

const SPECS = [
  tue('2026-09-15', ['Brahms 2', 'Freischütz']),
  tue('2026-09-22', ['Brahms 2', 'Freischütz'], { plan: splitPlan('Brahms 2, 1. Satz', 'Brahms 2, 2. Satz', 'Freischütz-Ouvertüre') }),
  tue('2026-09-29', ['Brahms 2'], { type: 'Registerprobe', color: '#8b5cf6', groups: ['Streicher'], groupsText: 'Streicher' }),
  tue('2026-10-06', ['Brahms 2', 'Freischütz'], { plan: simplePlan('Freischütz-Ouvertüre', 'Brahms 2, 3. Satz') }),
  tue('2026-10-13', ['Brahms 2'], {
    room: 'Raum 12',
    plan: splitPlan('Brahms 2, 4. Satz', 'Brahms 2, 2. Satz', 'Freischütz-Ouvertüre'),
  }),
  tue('2026-10-20', ['Brahms 2', 'Freischütz'], { plan: simplePlan('Brahms 2, 1. Satz', 'Freischütz-Ouvertüre') }),
  tue('2026-10-27', ['Brahms 2', 'Freischütz'], {
    id: 114,
    deadline: '2026-10-26T12:00:00',
    infos: [['🎼', 'Noten mitbringen']],
    plan: splitPlan('Brahms 2, 1. Satz', 'Brahms 2, 2. Satz', 'Freischütz-Ouvertüre'),
  }),
  tue('2026-11-03', ['Brahms 2'], { plan: simplePlan('Brahms 2, 3. Satz', 'Brahms 2, 4. Satz') }),
  {
    id: 117,
    d: '2026-11-06',
    endDate: '2026-11-08',
    from: '17:30',
    to: '15:30',
    type: 'Probenwochenende',
    location: 'Musikakademie Schloss Weikersheim',
    color: '#14b8a6',
    ...OHNE_HARFE,
    tags: ['Brahms 2', 'Freischütz', 'Pflicht'],
    infos: [
      ['🏡', 'Unterkunft in der Musikakademie Schloss Weikersheim'],
      ['🚗', 'Fahrgemeinschaften: Plätze in der Gruppe eintragen'],
      ['🍽️', 'Vollverpflegung, Allergien vorab melden'],
      ['🎼', 'Alle Stimmen für Brahms 2 und Freischütz vorbereiten'],
    ],
    plan: {
      '2026-11-06': [
        ['17:30', 'Anreise & Zimmerbezug'],
        ['19:00', 'Abendessen'],
        ['20:00', 'Einspielprobe Brahms 2, 1. Satz'],
      ],
      '2026-11-07': [
        ['09:00', 'Frühstück'],
        ['10:00', 'Brahms 2, 1. & 2. Satz'],
        ['12:30', 'Mittagessen'],
        ['14:30', 'Brahms 2, 3. Satz', ['Streicher']],
        ['14:30', 'Freischütz-Ouvertüre', ['Bläser']],
        ['17:00', 'Freischütz-Ouvertüre'],
        ['19:00', 'Abendessen'],
        ['20:30', 'Gemeinsamer Abend im Gewölbekeller'],
      ],
      '2026-11-08': [
        ['09:30', 'Brahms 2, 3. & 4. Satz'],
        ['12:00', 'Mittagessen'],
        ['13:00', 'Durchlauf des Konzertprogramms'],
        ['15:00', 'Aufräumen & Abreise'],
      ],
    },
  },
  tue('2026-11-10', ['Brahms 2', 'Bruch'], {
    to: '21:30',
    type: 'Registerprobe',
    color: '#8b5cf6',
    groups: ['Streicher'],
    groupsText: 'Streicher',
    plan: [
      ['19:30', 'Einspielen & Bogenstriche'],
      ['19:45', 'Bruch: Violinkonzert Nr. 1, 1. Satz mit Solistin'],
      ['20:30', 'Pause'],
      ['20:40', 'Bruch: Violinkonzert Nr. 1, 2. & 3. Satz'],
      ['21:20', 'Organisatorisches'],
    ],
  }),
  tue('2026-11-17', ['Brahms 2', 'Freischütz'], {
    room: 'Raum 12',
    plan: simplePlan('Freischütz-Ouvertüre', 'Brahms 2, Durchlauf'),
  }),
  {
    d: '2026-11-21',
    from: '18:00',
    to: '21:30',
    type: 'Generalprobe',
    location: 'Stadthalle Heidelberg',
    color: '#ec4899',
    ...OHNE_HARFE,
    tags: ['Herbstkonzert', 'Brahms 2', 'Pflicht'],
    infos: [
      ['👔', 'Konzertkleidung mitbringen'],
      ['🅿️', 'Parken in der Tiefgarage der Stadthalle'],
    ],
    plan: [
      ['18:00', 'Einlass & Aufbau'],
      ['18:30', 'Akustikprobe'],
      ['19:00', 'Durchlauf: Weber, Freischütz-Ouvertüre'],
      ['19:20', 'Durchlauf: Bruch mit Solistin'],
      ['20:00', 'Pause'],
      ['20:15', 'Durchlauf: Brahms 2'],
      ['21:15', 'Korrekturen & Besprechung'],
    ],
  },
  {
    d: '2026-11-22',
    from: '16:00',
    to: '22:00',
    type: 'Konzert',
    location: 'Stadthalle Heidelberg',
    color: '#ec4899',
    deadline: '2026-11-13T12:00:00',
    ...OHNE_HARFE,
    tags: ['Herbstkonzert', 'Brahms 2', 'Freischütz', 'Pflicht'],
    infos: [
      ['👔', 'Konzertkleidung: ganz in Schwarz'],
      ['⏰', 'Einlass ab 16:00 über den Bühneneingang'],
      ['🎟️', 'Freikarten für Angehörige bei den Stimmführungen abholen'],
    ],
    plan: [
      ['16:00', 'Einlass & Aufbau'],
      ['16:30', 'Anspielprobe'],
      ['18:00', 'Umziehen & Pause'],
      ['19:00', 'Einspielen'],
      ['19:30', 'Konzertbeginn: Weber, Freischütz-Ouvertüre'],
      ['19:45', 'Bruch: Violinkonzert Nr. 1 mit Solistin'],
      ['20:25', 'Pause'],
      ['20:45', 'Brahms: Sinfonie Nr. 2'],
      ['21:35', 'Zugabe & Applaus'],
      ['21:45', 'Gemeinsamer Abbau'],
    ],
  },
  tue('2026-11-24', ['Weihnachtskonzert'], {
    ...TUTTI,
    plan: splitPlan('Humperdinck: Hänsel und Gretel, Ouvertüre', 'Corelli: Weihnachtskonzert', 'Nussknacker-Suite, Stellenproben'),
  }),
  {
    id: 124,
    d: '2026-11-26',
    from: '19:30',
    to: '21:00',
    type: 'Vorstandssitzung',
    location: 'Vereinsraum',
    color: '#6366f1',
    ...TUTTI,
    roleIds: [4],
    tags: ['Vorstand'],
    infos: [['📋', 'Tagesordnung folgt per E-Mail']],
    plan: [
      ['19:30', 'Begrüßung & Protokoll'],
      ['19:40', 'Finanzen: Abrechnung Probenwochenende'],
      ['20:15', 'Planung Weihnachtskonzert'],
      ['20:45', 'Verschiedenes'],
    ],
  },
  tue('2026-12-01', [], {
    ...TUTTI,
    color: '#6b7280',
    cancelled: true,
    cancelReason: 'Raum nicht verfügbar',
  }),
];

const planItems = (plan, fallbackDate) => {
  const entries = Array.isArray(plan) ? [[fallbackDate, plan]] : Object.entries(plan);
  return entries.flatMap(([date, list]) =>
    list.map(([time, title, groups = null]) => ({ date, time, title, groups }))
  );
};

const ADDRESSES = {
  'Stadthalle Heidelberg': 'Neckarstaden 24, 69117 Heidelberg',
};

const buildRehearsal = (s, id) => {
  const endDate = s.endDate ?? s.d;
  const end = `${endDate}T${s.to}:00`;
  const roleIds = s.roleIds ?? [];
  const groups = s.groups ?? TUTTI.groups;
  return {
    id,
    start: `${s.d}T${s.from}:00`,
    end,
    date: s.d,
    endDate,
    startTime: s.from,
    endTime: s.to,
    multiDay: endDate !== s.d,
    type: s.type ?? 'Probe',
    location: s.location ?? 'Probenraum',
    room: s.room ?? null,
    address: ADDRESSES[s.location ?? 'Probenraum'] ?? null,
    color: s.color ?? '#e5e7eb',
    groups,
    groupsText: s.groupsText ?? joinNames(groups.map((g) => SECTION_NAME[g])),
    instrumentIds: [...new Set(groups.flatMap(leavesOf))],
    roleIds,
    roles: roleIds.map((rid) => {
      const role = ROLE_DEFS.find((r) => r.id === rid);
      return { id: role.id, name: role.name, tagColor: role.tagColor, icon: role.icon };
    }),
    tags: s.tags ?? [],
    infos: (s.infos ?? []).map(([emoji, text]) => ({ emoji, text })),
    scheduleItems: planItems(s.plan ?? [], s.d),
    deadline: s.deadline ?? null,
    cancelled: Boolean(s.cancelled),
    cancelReason: s.cancelReason ?? null,
    isToday: s.d <= TODAY && TODAY <= endDate,
    isPast: end < NOW,
  };
};

let nextId = 108;
export const rehearsals = [...SPECS]
  .sort((a, b) => (a.d + a.from).localeCompare(b.d + b.from))
  .map((s) => {
    if (s.id) nextId = s.id;
    return buildRehearsal(s, nextId++);
  });

// Zu- und Absagen

const FORCED = {
  '2026-10-27': { Horn: [2, 2], Violine_2: [2, 4], Kontrabass: [3, 0], Klarinette: [3, 0], Fagott: [2, 0], Posaune: [2, 1], Oboe: [2, 0], Flöte: [2, 1] },
  '2026-11-03': { Posaune: [1, 1], Schlagwerk: [1, 1] },
  '2026-11-06': { Cello: [2, 2], Horn: [2, 1] },
};

const PINNED = {
  'Lena Hartmann': {
    '2026-10-27': ['pending', ''],
    '2026-11-03': ['attending', 'Komme 15 Minuten später'],
    '2026-11-06': ['not_attending', 'Hochzeit meiner Schwester'],
    '2026-11-10': ['pending', ''],
  },
  'Benedikt Auer': {
    '2026-10-27': ['attending', ''],
    '2026-11-03': ['attending', ''],
    '2026-11-06': ['attending', ''],
  },
};

const NOTES_NO = [
  'Dienstreise',
  'krank',
  'Klausur',
  'Familienfeier',
  'Arzttermin',
  'Erkältung',
  'Hochzeit im Freundeskreis',
  'Spätschicht',
  'Uni-Exkursion',
];
const NOTES_SUMMER = ['Urlaub', 'Sommerurlaub', 'Familienurlaub', 'Urlaub in Italien'];
const NOTES_WINTER = ['Klausurenphase', 'Hausarbeit', 'Skiurlaub', 'krank', 'Familienbesuch'];
const NOTES_YES = [
  'Komme 15 Minuten später',
  'Muss um 21 Uhr gehen',
  'Komme direkt von der Arbeit',
  'Bringe eine Freundin zum Zuhören mit',
];

const noteNo = (m, r) => {
  const h = hash(m.id, r.id, 16);
  const month = dateParts(r.date)[1];
  if (r.date < '2026-09-10') return pick(NOTES_SUMMER, h);
  if (month === 12 || month === 1) return pick(NOTES_WINTER, h);
  return pick(NOTES_NO, h);
};

const typeFactor = (r) =>
  ({ Registerprobe: 0.96, Generalprobe: 0.98, Konzert: 1.05, Probenwochenende: 0.9, Konzertreise: 0.78, Vorstandssitzung: 0.9 })[
    r.type
  ] ?? 1;
const seasonFactor = (r) => (r.date < '2026-09-10' ? 0.82 : 1);
const reliability = (m) =>
  Math.min(0.97, 0.68 + 0.3 * hash(m.id, 1) + (m.isLeader ? 0.08 : 0) - (hash(m.id, 2) < 0.12 ? 0.2 : 0));

const respondProb = (r) => {
  const d = daysBetween(SEED_DAY, r.date);
  if (d < 0) return 0.985;
  let p = d <= 2 ? 0.96 : d <= 14 ? 0.9 : d <= 21 ? 0.8 : d <= 42 ? 0.42 : d <= 63 ? 0.2 : 0.07;
  if (['Generalprobe', 'Konzert', 'Probenwochenende', 'Konzertreise'].includes(r.type)) {
    p = Math.max(p, d <= 42 ? 0.55 : 0.3);
  }
  return p;
};

const isEligible = (m, r) =>
  m.canAttend &&
  m.joinedAt <= r.date &&
  r.instrumentIds.includes(m.sectionId) &&
  (r.roleIds.length === 0 || r.roleIds.some((id) => m.roleIds.includes(id)));

const respondedAt = (m, r) => {
  const base = r.date < SEED_DAY ? r.date : SEED_DAY;
  const back = 1 + Math.floor(hash(m.id, r.id, 8) * 8);
  const hour = 7 + Math.floor(hash(m.id, r.id, 10) * 15);
  const minute = Math.floor(hash(m.id, r.id, 14) * 12) * 5;
  const day = addDays(base, -back);
  return `${day < m.joinedAt ? m.joinedAt : day}T${pad(hour)}:${pad(minute)}:00`;
};

export const promises = rehearsals.flatMap((r) => {
  const eligible = baseMembers.filter((m) => isEligible(m, r));
  const pinned = (m) => PINNED[m.displayName]?.[r.date];
  const forced = {};
  for (const [sectionId, [attending, notAttending]] of Object.entries(FORCED[r.date] ?? {})) {
    const inSection = eligible.filter((m) => m.sectionId === sectionId);
    const fixed = inSection.filter(pinned).map((m) => pinned(m)[0]);
    const open = attending - fixed.filter((st) => st === 'attending').length;
    const closed = notAttending - fixed.filter((st) => st === 'not_attending').length;
    inSection
      .filter((m) => !pinned(m))
      .sort((x, y) => hash(x.id, r.id, 9) - hash(y.id, r.id, 9))
      .forEach((m, i) => {
        forced[m.id] = i < open ? 'attending' : i < open + closed ? 'not_attending' : 'pending';
      });
  }
  return eligible.map((m) => {
    if (pinned(m) && !r.cancelled) {
      const [status, note] = pinned(m);
      return {
        memberId: m.id,
        rehearsalId: r.id,
        status,
        note,
        respondedAt: status === 'pending' ? null : respondedAt(m, r),
      };
    }
    const responds = hash(m.id, r.id, 3) < respondProb(r);
    const attends = hash(m.id, r.id, 4) < Math.min(0.985, reliability(m) * typeFactor(r) * seasonFactor(r));
    const status = r.cancelled ? 'pending' : (forced[m.id] ?? (responds ? (attends ? 'attending' : 'not_attending') : 'pending'));
    let note = '';
    if (status === 'not_attending' && hash(m.id, r.id, 5) < 0.6) note = noteNo(m, r);
    if (status === 'attending' && r.type !== 'Konzert' && hash(m.id, r.id, 6) < 0.04) {
      note = pick(NOTES_YES, hash(m.id, r.id, 7));
    }
    return {
      memberId: m.id,
      rehearsalId: r.id,
      status,
      note,
      respondedAt: status === 'pending' ? null : respondedAt(m, r),
    };
  });
});

export const promiseStats = rehearsals.map((r) => {
  const rows = promises.filter((p) => p.rehearsalId === r.id);
  const count = (list) => ({
    attending: list.filter((p) => p.status === 'attending').length,
    notAttending: list.filter((p) => p.status === 'not_attending').length,
    pending: list.filter((p) => p.status === 'pending').length,
    total: list.length,
  });
  const totals = count(rows);
  return {
    rehearsalId: r.id,
    ...totals,
    responseRate: totals.total ? Math.round((100 * (totals.total - totals.pending)) / totals.total) : 0,
    bySection: r.instrumentIds
      .map((sectionId) => ({
        sectionId,
        ...count(rows.filter((p) => MEMBER.get(p.memberId).sectionId === sectionId)),
      }))
      .filter((s) => s.total > 0),
  };
});

// Anwesenheit

const UNDOCUMENTED = ['2026-10-20'];
const LATE_COMMENTS = ['Zug verspätet', 'Stau auf der B37', 'kam 20 Minuten später'];

export const documentedRehearsalIds = rehearsals
  .filter((r) => r.isPast && !r.cancelled && !UNDOCUMENTED.includes(r.date))
  .map((r) => r.id);

export const attendance = promises
  .filter((p) => documentedRehearsalIds.includes(p.rehearsalId))
  .map((p) => {
    const h = hash(p.memberId, p.rehearsalId, 20);
    let status;
    if (p.status === 'attending') status = h < 0.87 ? 'present' : h < 0.94 ? 'late' : h < 0.98 ? 'absent' : 'excused';
    else if (p.status === 'not_attending') status = h < 0.78 ? 'excused' : h < 0.88 ? 'present' : 'absent';
    else status = h < 0.6 ? 'present' : h < 0.65 ? 'late' : h < 0.95 ? 'absent' : 'excused';
    const h2 = hash(p.memberId, p.rehearsalId, 21);
    let comment = '';
    if (status === 'late' && h2 < 0.5) comment = pick(LATE_COMMENTS, h2 * 2);
    if (status === 'excused') comment = p.note;
    if (status === 'present' && p.status === 'not_attending') comment = 'Hatte doch Zeit';
    return {
      rehearsalId: p.rehearsalId,
      memberId: p.memberId,
      status,
      present: status === 'present' || status === 'late',
      comment,
      recordedBy: leaderOfSection[MEMBER.get(p.memberId).sectionId] ?? 1,
    };
  });

const attendanceRateOf = (memberId) => {
  const rows = attendance.filter((a) => a.memberId === memberId);
  return rows.length ? Math.round((100 * rows.filter((a) => a.present).length) / rows.length) : null;
};

export const members = baseMembers.map((m) => ({ ...m, attendanceRate: attendanceRateOf(m.id) }));

const sectionRegistry = new Map();

export function effectiveColor(node) {
  for (let s = node; s; s = s.parentId ? sectionRegistry.get(s.parentId) : null) {
    if (s.colorOwn) return s.colorOwn;
  }
  return null;
}

export function createSection({ id, name, parentId = null, kind = 'instrument', emoji = '', color = null, shortName = null, aliases = [] }) {
  const record = { id, name, shortName, parentId, kind, depth: 0, order: 0, emoji, colorOwn: color, aliases, childIds: [], instrumentIds: [id] };
  Object.defineProperties(record, {
    color: {
      enumerable: true,
      get() {
        return effectiveColor(this);
      },
      set(value) {
        this.colorOwn = value;
      },
    },
    memberCount: {
      enumerable: true,
      get() {
        return members.filter((m) => this.instrumentIds.includes(m.sectionId)).length;
      },
    },
  });
  sectionRegistry.set(id, record);
  return record;
}

export const sections = SECTION_DEFS.map(([id, name, shortName, parentId, kind, emoji, color, aliases]) =>
  createSection({ id, name, shortName, parentId, kind, emoji, color, aliases }));

function reindexSections() {
  const kids = new Map(sections.map((s) => [s.id, []]));
  sections.forEach((s, index) => {
    s.order = index;
    kids.get(s.parentId)?.push(s.id);
  });
  const leaves = (id) => (kids.get(id).length ? kids.get(id).flatMap(leaves) : [id]);
  for (const s of sections) {
    s.childIds = kids.get(s.id);
    s.instrumentIds = leaves(s.id);
    let depth = 0;
    for (let p = s.parentId; p; p = sectionRegistry.get(p)?.parentId) depth += 1;
    s.depth = depth;
  }
}

reindexSections();

export function applySectionTree(tree) {
  const root = sectionRegistry.get('tutti');
  const out = [root];
  const walk = (nodes, parent) => {
    for (const node of nodes) {
      const record = sectionRegistry.get(node.id);
      record.parentId = parent.id;
      out.push(record);
      walk(node.children ?? [], record);
    }
  };
  walk(tree, root);
  sections.splice(0, sections.length, ...out);
  reindexSections();
}

const SECTION_DEFAULTS = new Map(SECTION_DEFS.map(([id, name, shortName, , , emoji, color]) => [id, { name, shortName, emoji, color }]));

export function resetSectionDefaults() {
  for (const [id, fields] of SECTION_DEFAULTS) {
    const record = sectionRegistry.get(id);
    if (record) Object.assign(record, { name: fields.name, shortName: fields.shortName, emoji: fields.emoji, colorOwn: fields.color });
  }
}

export function compressGroups(instrumentIds) {
  const chosen = new Set(instrumentIds);
  const out = [];
  const walk = (id) => {
    const s = getSection(id);
    if (!s) return;
    if (s.instrumentIds.every((x) => chosen.has(x))) out.push(id);
    else if (s.instrumentIds.some((x) => chosen.has(x))) s.childIds.forEach(walk);
  };
  walk('tutti');
  return out;
}

export const roles = ROLE_DEFS.map((r) => ({
  ...r,
  memberCount: members.filter((m) => m.roleIds.includes(r.id)).length,
}));

// Auffälligkeiten

const histRate = (() => {
  const acc = {};
  for (const a of attendance) {
    const s = MEMBER.get(a.memberId).sectionId;
    acc[s] = acc[s] ?? { n: 0, p: 0 };
    acc[s].n += 1;
    acc[s].p += a.present ? 1 : 0;
  }
  return Object.fromEntries(Object.entries(acc).map(([s, v]) => [s, v.p / v.n]));
})();

const SEVERITY_ORDER = { critical: 3, warning: 2, positive: 1 };

export const insights = rehearsals
  .filter((r) => !r.isPast)
  .map((r) => {
    const stat = promiseStats.find((s) => s.rehearsalId === r.id);
    const days = daysBetween(SEED_DAY, r.date);
    if (r.cancelled || days > 21) {
      return { rehearsalId: r.id, criticalSections: [], deviations: [], insufficientData: [] };
    }
    const criticalSections = stat.bySection
      .filter((s) => s.total >= 2 && s.attending / s.total < 0.65)
      .map((s) => {
        const rate = Math.round((100 * s.attending) / s.total);
        return {
          sectionId: s.sectionId,
          name: SECTION_NAME[s.sectionId],
          rate,
          attending: s.attending,
          total: s.total,
          severity: rate < 25 ? 'critical' : 'warning',
          message: `${SECTION_NAME[s.sectionId]} nur ${rate}% Zusagen`,
        };
      })
      .sort((a, b) => a.rate - b.rate)
      .slice(0, 3);
    const deviations = [];
    if (days <= 14 && stat.responseRate < 60) {
      deviations.push({
        type: 'overall_response_rate',
        severity: stat.responseRate < 40 ? 'critical' : 'warning',
        sectionId: null,
        message: `Nur ${stat.responseRate}% Rückmeldungen in allen Registern`,
      });
    }
    if (days <= 14 && stat.total && (100 * stat.attending) / stat.total < 50) {
      deviations.push({
        type: 'overall_performance',
        severity: 'critical',
        sectionId: null,
        message: `Nur ${Math.round((100 * stat.attending) / stat.total)}% Teilnahme in allen Registern`,
      });
    }
    for (const s of stat.bySection) {
      if (s.total < 2) continue;
      const name = SECTION_NAME[s.sectionId];
      const responded = s.attending + s.notAttending;
      const usual = histRate[s.sectionId] ?? 0.85;
      if (responded >= 2) {
        const current = s.attending / responded;
        if (usual - current >= 0.2) {
          deviations.push({
            type: 'negative_statistical_anomaly',
            severity: usual - current >= 0.4 ? 'critical' : 'warning',
            sectionId: s.sectionId,
            message: `${name}: ${Math.round((usual - current) * 100)}% weniger Teilnahme als üblich`,
          });
        } else if (s.total >= 3 && s.attending === s.total && 1 - usual >= 0.1) {
          deviations.push({
            type: 'positive_statistical_anomaly',
            severity: 'positive',
            sectionId: s.sectionId,
            message: `${name}: ${Math.round((1 - usual) * 100)}% mehr Teilnahme als üblich`,
          });
        }
      }
      if (days <= 14 && s.total >= 3 && responded / s.total <= 0.4) {
        deviations.push({
          type: 'negative_response_rate_anomaly',
          severity: 'warning',
          sectionId: s.sectionId,
          message: `${name}: ${Math.round((0.95 - responded / s.total) * 100)}% weniger Rückmeldungen als üblich`,
        });
      }
    }
    deviations.sort((a, b) => SEVERITY_ORDER[b.severity] - SEVERITY_ORDER[a.severity]);
    return { rehearsalId: r.id, criticalSections, deviations: deviations.slice(0, 5), insufficientData: [] };
  });

// Tags, Orte, Typen

export const tags = tally(rehearsals.flatMap((r) => r.tags));
export const locations = tally(rehearsals.map((r) => r.location)).map((l) => ({ ...l, address: ADDRESSES[l.name] ?? null }));
export const rehearsalTypes = tally(rehearsals.map((r) => r.type));

// Ensemble und Benutzer

export const ensemble = {
  id: 1,
  name: 'Junges Sinfonieorchester Heidelberg',
  shortName: 'JSO Heidelberg',
  slug: 'jso-heidelberg',
  type: 'Orchester',
  organization: { id: 1, name: 'Jugendmusik Heidelberg e.V.', slug: 'jugendmusik-heidelberg' },
  defaultLocation: 'Probenraum',
  defaultGroups: [...OHNE_HARFE.groups],
  defaultScope: OHNE_HARFE.groupsText,
  season: 'Saison 2026/27',
  inviteLink: 'https://probenplaner.example/invite/a7c3e91f',
  memberCount: members.filter((m) => m.canAttend).length,
  settings: {
    allowPastEdit: true,
    forceDeclineReason: false,
    showRehearsalInsights: true,
    allowAttendanceReset: true,
    allowRehearsalImport: true,
  },
};

export const otherEnsembles = {
  member: [{ id: 2, name: 'Uniorchester Heidelberg', shortName: 'Uniorchester', monogram: 'UO', role: 'Mitglied' }],
  leader: [{ id: 3, name: 'Blechbläserensemble Neckar', shortName: 'Blech Neckar', monogram: 'BN', role: 'Mitglied' }],
  conductor: [{ id: 4, name: 'Kammerorchester Heidelberg', shortName: 'KO Heidelberg', monogram: 'KO', role: 'Leitung' }],
};

const toUser = (displayName, role, label) => {
  const member = members.find((m) => m.displayName === displayName);
  return { ...member, role, label, permissions: permissionsOf(member.roleIds), theme: 'light' };
};

export const users = {
  member: toUser('Lena Hartmann', 'member', 'Mitglied'),
  leader: toUser('Benedikt Auer', 'leader', 'Stimmführung'),
  conductor: toUser('Stefan Eberhardt', 'conductor', 'Leitung'),
};

// Benachrichtigungen

const byDate = (d) => rehearsals.find((r) => r.date === d);
const next = byDate('2026-10-27');
const nextStats = promiseStats.find((s) => s.rehearsalId === next.id);
const decline =
  promises.find(
    (p) =>
      p.rehearsalId === next.id &&
      p.status === 'not_attending' &&
      p.note &&
      MEMBER.get(p.memberId).sectionId === 'Horn'
  ) ?? promises.find((p) => p.rehearsalId === next.id && p.status === 'not_attending' && p.note);
const declineMember = MEMBER.get(decline.memberId);

const notification = (id, type, title, text, createdAt, read, audience, rehearsalId = null, memberId = null) => ({
  id,
  type,
  title,
  text,
  createdAt,
  read,
  audience,
  rehearsalId,
  memberId,
});

const rosa = baseMembers.find((m) => m.displayName === 'Rosa Navarro');
const reminderText = `Push-Erinnerung an ${nextStats.pending} Mitglieder ohne Rückmeldung für Di, 27. Okt.`;
const declineText = `${declineMember.displayName} (${SECTION_NAME[declineMember.sectionId]}) hat für Di, 27. Okt. abgesagt: „${decline.note}“`;

export const notifications = [
  notification(1, 'reminder_sent', 'Erinnerung gesendet', reminderText, '2026-10-24T08:00:00', false, 'leaders', next.id),
  notification(2, 'note_added', 'Absage mit Notiz', declineText, '2026-10-23T18:42:00', false, 'leaders', next.id, declineMember.id),
  notification(3, 'rehearsal_created', 'Neue Probe', 'Probenwochenende in Weikersheim (Fr, 6. Nov. – So, 8. Nov.) wurde angelegt.', '2026-10-22T20:15:00', false, 'all', byDate('2026-11-06').id),
  notification(4, 'rehearsal_created', 'Neue Probe', 'Registerprobe für die Streicher am Di, 10. Nov. wurde angelegt.', '2026-10-20T22:10:00', true, 'all', byDate('2026-11-10').id),
  notification(5, 'member_joined', 'Neues Mitglied', 'Rosa Navarro ist dem Ensemble beigetreten (Kontrabass).', '2026-10-19T16:30:00', true, 'leaders', null, rosa.id),
  notification(6, 'info_added', 'Neue Info', 'Generalprobe am Sa, 21. Nov.: „Konzertkleidung mitbringen“', '2026-10-18T11:05:00', true, 'all', byDate('2026-11-21').id),
  notification(7, 'reminder_sent', 'Erinnerung gesendet', 'Push-Erinnerung für Di, 20. Okt. wurde an alle Mitglieder ohne Rückmeldung gesendet.', '2026-10-17T09:00:00', true, 'leaders', byDate('2026-10-20').id),
  notification(8, 'rehearsal_cancelled', 'Probe fällt aus', 'Die Probe am Di, 1. Dez. fällt aus: Raum nicht verfügbar.', '2026-10-14T19:20:00', true, 'all', byDate('2026-12-01').id),
];

// Hilfsfunktionen für die Oberfläche

export function formatDate(iso) {
  const [, month, day] = dateParts(iso);
  return `${WEEKDAYS[weekdayIndex(iso)]}, ${day}. ${MONTHS[month - 1]}`;
}

export function formatDateLong(iso) {
  const [year, month, day] = dateParts(iso);
  return `${WEEKDAYS_LONG[weekdayIndex(iso)]}, ${day}. ${MONTHS_LONG[month - 1]} ${year}`;
}

export function formatTime(iso) {
  return /[T ]/.test(iso) ? iso.slice(11, 16) : iso.slice(0, 5);
}

export function weekdayShort(iso) {
  return WEEKDAYS[weekdayIndex(iso)].toUpperCase();
}

export function getRehearsal(id) {
  return rehearsals.find((r) => r.id === Number(id)) ?? null;
}

export function getMember(id) {
  return members.find((m) => m.id === Number(id)) ?? null;
}

export function getSection(id) {
  return sections.find((s) => s.id === id) ?? null;
}

export function defaultBesetzung() {
  const groups = [...ensemble.defaultGroups];
  return {
    groups,
    groupsText: ensemble.defaultScope,
    instrumentIds: [...new Set(groups.flatMap((id) => getSection(id)?.instrumentIds ?? [id]))],
  };
}

export function getRole(id) {
  return roles.find((r) => r.id === Number(id)) ?? null;
}

export function getPromise(memberId, rehearsalId) {
  return promises.find((p) => p.memberId === Number(memberId) && p.rehearsalId === Number(rehearsalId)) ?? null;
}

export function getPromiseStats(rehearsalId) {
  const s = promiseStats.find((x) => x.rehearsalId === Number(rehearsalId));
  return s
    ? { attending: s.attending, notAttending: s.notAttending, pending: s.pending, total: s.total }
    : { attending: 0, notAttending: 0, pending: 0, total: 0 };
}

export function getAttendance(rehearsalId) {
  return attendance.filter((a) => a.rehearsalId === Number(rehearsalId));
}

export function getInsights(rehearsalId) {
  return (
    insights.find((i) => i.rehearsalId === Number(rehearsalId)) ?? {
      rehearsalId: Number(rehearsalId),
      criticalSections: [],
      deviations: [],
      insufficientData: [],
    }
  );
}

export function getLocation(name) {
  return locations.find((l) => l.name === name) ?? { name, count: 0, address: null };
}

export function getMembersBySection(sectionId) {
  const ids = getSection(sectionId)?.instrumentIds ?? [sectionId];
  return members.filter((m) => ids.includes(m.sectionId));
}

export function appliesTo(member, rehearsal) {
  return isEligible(member, rehearsal);
}

export function getRehearsalsForMember(memberId) {
  const member = getMember(memberId);
  return rehearsals.filter((r) => isEligible(member, r));
}

export function getUpcoming() {
  return rehearsals.filter((r) => !r.isPast).sort((a, b) => a.start.localeCompare(b.start));
}

export function getPast() {
  return rehearsals.filter((r) => r.isPast).sort((a, b) => b.start.localeCompare(a.start));
}

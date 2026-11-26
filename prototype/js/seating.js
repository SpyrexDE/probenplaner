/* Sitzplan: who is coming, as seats on the orchestra's stage */

import { h, navigate } from './app.js';
import { members, sections, promises, attendance, getSection, appliesTo } from './data.js';

const NS = 'http://www.w3.org/2000/svg';
const STAGGER_MS = 220;
const STATE_ORDER = { yes: 0, open: 1, no: 2, ghost: 3 };

const STAGE = {
  seat: 0.35,
  pitch: 1,
  row: 1.3,
  desk: 0.8,
  deskGap: 1,
  voiceGap: 0.55,
  zoneGap: 0.2,
  inner: 6.5,
  drop: 3.8,
  edge: 0.4,
  grow: 1.2,
  stagger: 1,
  lead: 2,
  minWidth: 11.5,
};

const SIZES = {
  medium: { digits: 40, of: 17, box: 320 },
  hero: { digits: 60, of: 24, box: 480 },
};

const WORDS = {
  answers: { yes: 'zugesagt', no: 'abgesagt', open: 'offen' },
  attendance: { yes: 'anwesend', no: 'gefehlt', open: 'nicht erfasst' },
};

function svg(tag, attrs = {}, ...children) {
  const el = document.createElementNS(NS, tag);
  for (const [key, value] of Object.entries(attrs)) {
    if (value == null || value === false) continue;
    el.setAttribute(key, value === true ? '' : String(value));
  }
  el.append(...children.flat().filter(Boolean));
  return el;
}

/* Families and voices in section order, from the section tree */

function familyPlan() {
  const root = sections.find((s) => !s.parentId);
  const kids = (s) => (s?.childIds ?? []).map(getSection).filter(Boolean);
  const families = [];
  const walk = (node) => {
    const children = kids(node);
    if (!children.length) families.push([node.id]);
    else if (children.every((c) => !kids(c).length)) families.push(children.map((c) => c.id));
    else children.forEach(walk);
  };
  walk(root);
  return families;
}

function groupSeats(seats) {
  const byVoice = new Map();
  for (const seat of seats) {
    if (!byVoice.has(seat.sectionId)) byVoice.set(seat.sectionId, []);
    byVoice.get(seat.sectionId).push(seat);
  }
  const plan = familyPlan();
  const known = new Set(plan.flat());
  const stray = [...byVoice.keys()].filter((id) => !known.has(id));
  const families = plan.map((all) => ({ all, voices: all.filter((id) => byVoice.has(id)).map((id) => ({ id, seats: byVoice.get(id) })) }));
  if (stray.length) {
    const family = { all: stray, voices: stray.map((id) => ({ id, seats: byVoice.get(id) })) };
    if (families.some((f) => f.voices.length)) families.push(family);
    else families.unshift(family);
  }
  return families;
}

/* Geometry: the stage as the conductor sees it, podium at the centre bottom.
   The first family sits in desks of two on as many arcs around the podium as it fills, desk 1 in front; the arcs end at the front edge.
   A fifth voice stands one arc further out behind the last voice. The next family sits behind in two straight rows,
   the ones after on wider arcs of the same centre. Single voices take a straight back row, then the wings. */

function arcAt(d, scale = 1) {
  const T = STAGE;
  const r = T.inner * scale + d;
  const half = Math.acos(Math.min(1, (T.drop + T.edge) / r));
  return {
    length: 2 * half * r,
    point: (off) => {
      const t = off / r - half;
      return { x: r * Math.sin(t), y: T.drop - r * Math.cos(t) };
    },
  };
}

function arcRow(k, scale = 1) {
  return arcAt(k * STAGE.row, scale);
}

function rowXs(voices) {
  const T = STAGE;
  const out = [];
  let x = 0;
  voices.forEach((voice, i) => {
    if (i) x += T.voiceGap;
    for (let k = 0; k < voice.seats.length; k++) {
      out.push({ voice: voice.id, x });
      x += T.pitch;
    }
  });
  const shift = out.length ? (out.at(-1).x + out[0].x) / 2 : 0;
  for (const p of out) p.x -= shift;
  return out;
}

function behind(points, obstacles, gap, cap) {
  let y = cap;
  for (const p of points) {
    for (const o of obstacles) {
      const dx = Math.abs(p.x - o.x);
      if (dx < gap) y = Math.min(y, o.y - Math.sqrt(gap * gap - dx * dx) - p.off);
    }
  }
  return y;
}

function apportion(n, remaining) {
  const total = remaining.reduce((a, b) => a + b, 0);
  if (!total) return remaining.map(() => 0);
  const exact = remaining.map((r) => (n * r) / total);
  const out = exact.map((x, i) => Math.min(remaining[i], Math.floor(x)));
  let left = n - out.reduce((a, b) => a + b, 0);
  const order = exact.map((x, i) => i).sort((i, j) => (exact[j] - out[j]) - (exact[i] - out[i]) || j - i);
  while (left > 0) {
    const i = order.find((k) => out[k] < remaining[k]);
    if (i == null) break;
    out[i] += 1;
    order.splice(order.indexOf(i), 1);
    order.push(i);
    left -= 1;
  }
  return out;
}

function deskRun(counts) {
  const T = STAGE;
  const slots = [];
  let s = 0;
  counts.forEach(({ voice, desks, seats }) => {
    if (!desks) return;
    if (slots.length) s += T.deskGap + T.voiceGap;
    for (let d = 0; d < desks; d++) {
      if (d) s += T.deskGap;
      const pair = Math.min(2, seats - 2 * d);
      slots.push({ voice, s, desk: d });
      if (pair > 1) {
        slots.push({ voice, s: s + T.desk, desk: d });
        s += T.desk;
      }
    }
  });
  return { slots, length: s };
}

function layout(families) {
  const T = STAGE;
  const spots = [];
  const head = families[0]?.voices.length ? families[0] : null;
  const rest = families.slice(1);
  const blocks = rest.filter((f) => f.all.length > 1);
  const singles = rest.filter((f) => f.all.length === 1);
  const gap = T.row + T.zoneGap;
  const clearOf = (p, d = gap) => spots.every((o) => Math.hypot(p.x - o.x, p.y - o.y) >= d - 1e-6);

  const lowId = head && head.all.length >= 5 ? head.all.at(-1) : null;
  let fanVoices = head ? head.voices.filter((v) => v.id !== lowId) : [];
  let low = head?.voices.find((v) => v.id === lowId) ?? null;
  if (!fanVoices.length && low) [fanVoices, low] = [[low], null];
  const tree = head ? head.all.filter((id) => id !== lowId) : [];
  const leftSide = (id) => tree.indexOf(id) < tree.length / 2;
  const desks = fanVoices.map((v) => Math.ceil(v.seats.length / 2));
  const total = desks.reduce((a, b) => a + b, 0);
  const pitch = T.desk + T.deskGap;
  const usable = (row, k) => row.length - (k % 2) * pitch * T.stagger;
  const capacity = (row, k) => Math.max(1, Math.floor((usable(row, k) + T.deskGap - (fanVoices.length - 1) * T.voiceGap) / pitch));

  let rows = 1;
  let scale = 1;
  for (let r = 1; r <= 24; r++) {
    let s = 1;
    const fits = (sc) => Array.from({ length: r }, (_, k) => capacity(arcRow(k, sc), k)).reduce((a, b) => a + b, 0) >= total;
    while (!fits(s) && s < T.grow) s += 0.02;
    if (fits(s)) {
      rows = r;
      scale = s;
      break;
    }
  }
  const arcs = Array.from({ length: rows }, (_, k) => arcRow(k, scale));
  const caps = arcs.map((row, k) => capacity(row, k));
  const perRow = apportion(total, caps);
  const left = desks.slice();
  const used = desks.map(() => 0);
  const ends = new Map();
  arcs.forEach((row, k) => {
    const n = perRow[k];
    if (!n) return;
    const plan = apportion(n, left);
    const counts = fanVoices.map((v, i) => ({ voice: v.id, desks: plan[i], seats: v.seats.length - 2 * used[i] }));
    const run = deskRun(counts);
    const shift = (row.length - run.length) / 2;
    for (const slot of run.slots) {
      slot.s += shift;
      const p = row.point(slot.s);
      const side = leftSide(slot.voice) ? 1 : -1;
      spots.push({ voice: slot.voice, x: p.x, y: p.y, rank: 1000 * k + side * p.x });
      const end = ends.get(slot.voice);
      if (!end || end.k < k) ends.set(slot.voice, { k, lo: slot.s, hi: slot.s, length: row.length });
      else end.hi = slot.s;
    }
    fanVoices.forEach((v, i) => {
      used[i] += plan[i];
      left[i] -= plan[i];
    });
  });

  const ring = (id, n, from, k, dir) => {
    const along = (row, step) => {
      const start = dir > 0 ? from : row.length - from;
      return Array.from({ length: n }, (_, j) => row.point(start + dir * (step * 0.1 + j * T.pitch)));
    };
    let pts = null;
    for (let kk = k; kk < k + 6 && !pts; kk++) {
      const row = arcRow(kk, scale);
      for (let step = 0; step * 0.1 + (n - 1) * T.pitch + from <= row.length && !pts; step++) {
        const at = along(row, step);
        if (at.every((p) => clearOf(p, T.row))) pts = at;
      }
    }
    (pts ?? along(arcRow(k + 6, scale), 0)).forEach((p, j) => spots.push({ voice: id, x: p.x, y: p.y, rank: j }));
  };
  const edgeOf = (voice, dir, middle = false) => {
    const end = voice && ends.get(voice.id);
    if (!end) return { from: 0, k: rows };
    const at = middle ? (end.lo + end.hi) / 2 : dir > 0 ? end.lo : end.hi;
    const from = dir > 0 ? at : end.length - at;
    return { from: !middle && from < 2.5 * T.pitch ? T.pitch : from, k: end.k + 1 };
  };
  if (low) {
    const at = edgeOf(fanVoices.at(-1), -1);
    ring(low.id, low.seats.length, at.from, at.k, -1);
  }

  const cap = -(T.inner * scale - T.drop + 0.5 * T.row);
  const onArc = (voices, from) => {
    const line = rowXs(voices);
    for (let d = from; d < from + 30; d += 0.05) {
      const arc = arcAt(d, scale);
      const pts = line.map((p) => ({ voice: p.voice, rank: Math.abs(p.x), ...arc.point(arc.length / 2 + p.x) }));
      if (pts.every((p) => clearOf(p))) return pts;
    }
    return [];
  };
  blocks.forEach((family, b) => {
    if (!family.voices.length) return;
    const ids = family.all;
    const half = Math.ceil(ids.length / 2);
    if (b > 0) {
      spots.push(...onArc(family.voices, rows * T.row));
      return;
    }
    const lines = ids.length >= 4 ? [ids.slice(0, half), ids.slice(half)] : [ids];
    let prev = null;
    for (const line of lines) {
      const voices = family.voices.filter((v) => line.includes(v.id));
      if (!voices.length) continue;
      const points = rowXs(voices).map((p) => ({ ...p, off: 0 }));
      const y = prev != null ? prev - T.row : behind(points, spots, gap, cap);
      for (const p of points) spots.push({ voice: p.voice, x: p.x, y, rank: Math.abs(p.x) });
      prev = y;
    }
  });
  const [back, ...wings] = singles.filter((f) => f.voices.length);
  if (back) {
    const voice = back.voices[0];
    const lead = ((voice.seats.length - 1) * T.pitch) / 2 + T.lead * T.pitch;
    const points = rowXs([voice]).map((p) => ({ ...p, x: p.x + lead, off: 0 }));
    const y = behind(points, spots, gap, cap);
    for (const p of points) spots.push({ voice: p.voice, x: p.x, y, rank: Math.abs(p.x) });
  }
  wings.forEach((family, i) => {
    const voice = family.voices[0];
    const dir = i % 2 ? -1 : 1;
    const at = edgeOf(dir > 0 ? fanVoices[0] : fanVoices.at(-1), dir, true);
    ring(voice.id, voice.seats.length, at.from, at.k, dir);
  });

  const byVoice = new Map();
  for (const spot of spots) {
    if (!byVoice.has(spot.voice)) byVoice.set(spot.voice, []);
    byVoice.get(spot.voice).push(spot);
  }
  for (const list of byVoice.values()) list.sort((a, b) => a.rank - b.rank);
  const halfW = Math.max(T.minWidth / 2, ...spots.map((s) => Math.abs(s.x) + T.seat));
  const top = Math.min(T.drop - T.inner * scale, ...spots.map((s) => s.y - T.seat));
  const bottom = Math.max(0.1, ...spots.map((s) => s.y + T.seat));
  return { byVoice, box: { x: -halfW, y: top, w: 2 * halfW, h: bottom - top } };
}

function seatOrder(list) {
  return list
    .map((seat, i) => ({ seat, i }))
    .sort((a, b) => (STATE_ORDER[a.seat.state] ?? 1) - (STATE_ORDER[b.seat.state] ?? 1) || a.i - b.i)
    .map((item) => item.seat);
}

/* Counting */

function tally(seats) {
  const c = { yes: 0, no: 0, open: 0, total: 0 };
  for (const seat of seats) {
    if (seat.state === 'ghost') continue;
    c.total += 1;
    c[seat.state] = (c[seat.state] ?? 0) + 1;
  }
  return c;
}

function sectionName(id) {
  return getSection(id)?.name ?? id;
}

function invitedText(total) {
  return `${total} eingeladen`;
}

export function seatingLabel(seats, { kind = 'answers' } = {}) {
  const c = tally(seats);
  const w = WORDS[kind];
  return `${c.yes} ${w.yes}, ${c.no} ${w.no}, ${c.open} ${w.open}, ${invitedText(c.total)}`;
}

/* Tooltip */

let tipHost = null;

function tipFor(el) {
  if (tipHost?.isConnected && tipHost.parentElement === el) return tipHost;
  tipHost?.remove();
  tipHost = h('div', { class: 'seating__tip', role: 'tooltip' });
  el.append(tipHost);
  return tipHost;
}

function clearMarks(el) {
  el?.classList.remove('is-focus');
  el?.querySelectorAll('.is-lit, .is-hot').forEach((n) => n.classList.remove('is-lit', 'is-hot'));
}

function hideTip() {
  tipHost?.classList.remove('is-on');
  clearMarks(tipHost?.closest('.seating'));
}

const MARKS = {
  yes: () => svg('path', { d: 'M2.5 6.4 5 8.8l4.5-5.6' }),
  no: () => svg('path', { d: 'M3.2 3.2l5.6 5.6M8.8 3.2l-5.6 5.6' }),
  open: () => svg('circle', { cx: 6, cy: 6, r: 3.9 }),
};

function mark(state) {
  return svg('svg', { class: `seating__mark seating__mark--${state}`, viewBox: '0 0 12 12', 'aria-hidden': 'true' }, MARKS[state]?.());
}

function showTip(el, target, kind) {
  const tip = tipFor(el);
  const box = el.getBoundingClientRect();
  const words = WORDS[kind];
  const seat = target.__seat;
  const node = target.__node ?? target;
  const mates = [...el.querySelectorAll(`.seat[data-section="${CSS.escape(seat.sectionId)}"]`)];
  const c = tally(mates.map((n) => n.__seat));
  clearMarks(el);
  const person = seat.state === 'ghost' ? null : h('span', { class: 'seating__tip-line' },
    h('span', { class: 'seating__tip-name' }, seat.name ?? ''),
    h('span', { class: 'seating__tip-val' }, mark(seat.state), words[seat.state]),
    seat.note ? h('span', { class: 'seating__tip-note' }, `„${seat.note}“`) : null);
  const section = c.total ? h('span', { class: 'seating__tip-line seating__tip-line--section' },
    h('span', { class: 'seating__tip-name' }, sectionName(seat.sectionId)),
    h('span', { class: 'seating__tip-val' }, `${c.yes}/${c.total}`),
    c.no ? h('span', { class: 'seating__tip-val' }, mark('no'), String(c.no)) : null,
    c.open ? h('span', { class: 'seating__tip-val' }, mark('open'), String(c.open)) : null) : null;
  if (!person && !section) return;
  tip.replaceChildren(...[person, section].filter(Boolean));
  el.classList.add('is-focus');
  mates.forEach((n) => n.classList.add('is-lit'));
  node.classList.add('is-hot');
  const rect = node.getBoundingClientRect();
  tip.style.left = `${rect.left + rect.width / 2 - box.left}px`;
  tip.style.top = `${rect.top - box.top}px`;
  tip.classList.add('is-on');
}

function seatText(el, seat, kind) {
  const words = WORDS[kind];
  const mates = [...el.querySelectorAll(`.seat[data-section="${CSS.escape(seat.sectionId)}"]`)];
  const c = tally(mates.map((n) => n.__seat));
  const person = seat.state === 'ghost' ? null : `${seat.name ?? ''}, ${words[seat.state]}${seat.note ? `, „${seat.note}“` : ''}`;
  const section = c.total ? `${sectionName(seat.sectionId)}: ${c.yes} von ${c.total} ${words.yes}` : null;
  return [person, section].filter(Boolean).join('. ');
}

document.addEventListener('pointerdown', (event) => {
  if (!event.target.closest?.('.seating')) hideTip();
}, true);

/* The component */

export function seatingChart(seats, {
  size = 'medium', kind = 'answers', emphasis = null, ownId = null, label, sectionHref = null, interactive = true,
} = {}) {
  const spec = SIZES[size] ?? SIZES.medium;
  const families = groupSeats(seats);
  const geo = layout(families);
  const { box } = geo;
  const focus = emphasis ? new Set(getSection(emphasis)?.instrumentIds ?? [emphasis]) : null;
  const inFocus = (seat) => !focus || focus.has(seat.sectionId);

  const places = [];
  for (const family of families) {
    for (const voice of family.voices) {
      for (const spot of geo.byVoice.get(voice.id) ?? []) {
        const node = svg('circle', { class: 'seat', cx: spot.x.toFixed(3), cy: spot.y.toFixed(3), r: STAGE.seat, 'data-section': voice.id });
        const hit = interactive ? svg('circle', { class: 'seat-hit', cx: spot.x.toFixed(3), cy: spot.y.toFixed(3), r: STAGE.pitch * 0.5 }) : null;
        if (hit) hit.__node = node;
        places.push({ voice: voice.id, node, hit });
      }
    }
  }

  const paint = (list, { animate = false } = {}) => {
    const motion = animate && !matchMedia('(prefers-reduced-motion: reduce)').matches && document.documentElement.dataset.motion !== 'reduced';
    const queues = new Map();
    for (const seat of list) {
      if (!queues.has(seat.sectionId)) queues.set(seat.sectionId, []);
      queues.get(seat.sectionId).push(seat);
    }
    for (const [key, queue] of queues) queues.set(key, seatOrder(queue));
    const changed = [];
    for (const place of places) {
      const seat = queues.get(place.voice)?.shift();
      if (!seat) continue;
      const before = place.node.__seat?.state;
      place.node.__seat = seat;
      if (place.hit) place.hit.__seat = seat;
      if (before && before !== seat.state) changed.push(place.node);
      const classes = ['seat', `seat--${seat.state}`, seat.id === ownId && 'seat--own', !inFocus(seat) && 'is-dim'].filter(Boolean).join(' ');
      if (place.node.getAttribute('class') !== classes) place.node.setAttribute('class', classes);
    }
    changed.forEach((node, i) => {
      node.style.transitionDelay = motion && changed.length > 1 ? `${Math.round((i * STAGGER_MS) / changed.length)}ms` : '0ms';
    });
  };
  paint(seats);

  const scope = seats.filter(inFocus);
  const count = tally(scope);
  const digits = svg('tspan', { class: 'seating__n' }, String(count.yes));
  const ofText = document.createTextNode(`/${count.total}`);
  const ofTitle = svg('title', {}, invitedText(count.total));
  const of = spec.of ? svg('tspan', { class: 'seating__of', dx: '0.06em' }, ofText, ofTitle) : null;
  const unit = box.w / spec.box;
  const text = svg('text', {
    class: 'seating__num',
    x: 0,
    y: 0,
    'text-anchor': 'middle',
    style: `font-size:${(spec.digits * unit).toFixed(3)}px`,
  }, digits, of);
  if (of) of.setAttribute('style', `font-size:${(spec.of * unit).toFixed(3)}px`);

  const graphic = svg('svg', {
    class: 'seating__svg',
    viewBox: `${box.x.toFixed(3)} ${box.y.toFixed(3)} ${box.w.toFixed(3)} ${box.h.toFixed(3)}`,
    preserveAspectRatio: 'xMidYMax meet',
    role: 'img',
    'aria-label': label ?? seatingLabel(scope, { kind }),
  }, svg('g', { class: 'seating__seats' }, places.map((p) => p.node)), text, svg('g', { class: 'seating__hits' }, places.map((p) => p.hit)));

  const el = h('div', {
    class: ['seating', `seating--${size}`, emphasis && 'has-emphasis', interactive && 'is-interactive'],
    style: `--seating-ratio:${(box.w / box.h).toFixed(4)}`,
  }, graphic);

  if (interactive) {
    const pick = (event) => event.target.closest?.('.seat-hit');
    graphic.addEventListener('pointerover', (event) => {
      const target = pick(event);
      if (target) showTip(el, target, kind);
    });
    graphic.addEventListener('pointerleave', () => hideTip());
    graphic.addEventListener('click', (event) => {
      const target = pick(event);
      if (!target) return;
      if (event.pointerType === 'touch' || !matchMedia('(hover: hover)').matches) {
        showTip(el, target, kind);
        return;
      }
      const id = target.__seat?.sectionId;
      const href = id && sectionHref ? sectionHref(id) : null;
      if (href) navigate(href.replace(/^#/, ''));
    });
    if (sectionHref) el.classList.add('is-linked');

    const live = h('span', { class: 'visually-hidden', 'aria-live': 'polite' });
    el.append(live);
    el.tabIndex = 0;
    el.setAttribute('role', 'group');
    el.setAttribute('aria-label', 'Sitzplan, Pfeiltasten zeigen die Plätze');
    let cursor = -1;
    const order = () => places.filter((p) => p.hit?.__seat && inFocus(p.hit.__seat)).map((p) => p.hit);
    el.addEventListener('keydown', (event) => {
      const list = order();
      if (!list.length || event.altKey || event.ctrlKey || event.metaKey) return;
      const step = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[event.key];
      if (event.key === 'Escape' && cursor > -1) {
        event.preventDefault();
        cursor = -1;
        hideTip();
        return;
      }
      if (event.key === 'Enter' && cursor > -1) {
        const id = list[cursor].__seat?.sectionId;
        const href = id && sectionHref ? sectionHref(id) : null;
        if (!href) return;
        event.preventDefault();
        navigate(href.replace(/^#/, ''));
        return;
      }
      if (step) cursor = cursor < 0 ? (step > 0 ? 0 : list.length - 1) : (cursor + step + list.length) % list.length;
      else if (event.key === 'Home') cursor = 0;
      else if (event.key === 'End') cursor = list.length - 1;
      else return;
      event.preventDefault();
      const hit = list[cursor];
      showTip(el, hit, kind);
      live.textContent = seatText(el, hit.__seat, kind);
    });
    el.addEventListener('blur', () => {
      cursor = -1;
      hideTip();
    });
  }

  el.update = (next) => {
    hideTip();
    paint(next, { animate: true });
    const c = tally(next.filter(inFocus));
    digits.textContent = String(c.yes);
    ofText.data = `/${c.total}`;
    ofTitle.textContent = invitedText(c.total);
    graphic.setAttribute('aria-label', seatingLabel(next.filter(inFocus), { kind }));
  };
  return el;
}

/* The same form in a row slot: one arc, split by answer */

const GAUGE = { w: 88, h: 40, r: 31, stroke: 6, gap: 2.2 };

function arc(cx, cy, r, from, to) {
  const pt = (t) => `${(cx - r * Math.cos(t)).toFixed(2)} ${(cy - r * Math.sin(t)).toFixed(2)}`;
  return `M${pt(from)} A${r} ${r} 0 ${to - from > Math.PI ? 1 : 0} 1 ${pt(to)}`;
}

export function seatGauge(counts, { label } = {}) {
  const { w, h: height, r, gap } = GAUGE;
  const cx = w / 2;
  const cy = height - 3;
  const parts = [['yes', counts.yes], ['open', counts.open], ['no', counts.no]].filter(([, n]) => n > 0);
  const total = parts.reduce((sum, [, n]) => sum + n, 0);
  const gapAngle = gap / r;
  const usable = Math.PI - gapAngle * Math.max(0, parts.length - 1);
  let at = 0;
  const segments = total ? parts.map(([part, n]) => {
    const span = (usable * n) / total;
    const path = svg('path', { class: `gauge__seg gauge__seg--${part}`, d: arc(cx, cy, r, at, at + span) });
    at += span + gapAngle;
    return path;
  }) : [svg('path', { class: 'gauge__seg gauge__seg--open', d: arc(cx, cy, r, 0, Math.PI) })];
  return svg('svg', {
    class: 'gauge',
    viewBox: `0 0 ${w} ${height}`,
    role: label ? 'img' : null,
    'aria-label': label || null,
    'aria-hidden': label ? null : 'true',
  },
  segments,
  svg('text', { class: 'gauge__num', x: cx, y: cy, 'text-anchor': 'middle' }, String(counts.yes)));
}

/* Seats from the data */

const ANSWER = { attending: 'yes', not_attending: 'no', pending: 'open' };

export function answerSeats(r, { scope = null, overrides = null } = {}) {
  const status = new Map(promises.filter((p) => p.rehearsalId === r.id).map((p) => [p.memberId, p]));
  return members
    .filter((m) => m.sectionId && appliesTo(m, r))
    .map((m) => {
      const p = status.get(m.id);
      const own = overrides?.get(m.id) ?? p?.status ?? 'pending';
      const seat = { id: m.id, name: m.displayName, sectionId: m.sectionId, state: ANSWER[own] ?? 'open', note: p?.note || '' };
      if (scope && m.sectionId !== scope) return { ...seat, state: 'ghost', name: sectionName(m.sectionId), note: '' };
      return seat;
    });
}

export function attendanceSeats(r, { scope = null } = {}) {
  const rows = new Map(attendance.filter((a) => a.rehearsalId === r.id).map((a) => [a.memberId, a]));
  return members
    .filter((m) => m.sectionId && appliesTo(m, r))
    .map((m) => {
      const row = rows.get(m.id);
      const state = !row ? 'open' : row.present ? 'yes' : 'no';
      if (scope && m.sectionId !== scope) return { id: m.id, name: sectionName(m.sectionId), sectionId: m.sectionId, state: 'ghost', note: '' };
      return { id: m.id, name: m.displayName, sectionId: m.sectionId, state, note: row?.comment || '' };
    });
}

export function seatSlot(r, { past = false, href = null } = {}) {
  const seats = past ? attendanceSeats(r) : answerSeats(r);
  const text = seatingLabel(seats, { kind: past ? 'attendance' : 'answers' });
  return h(href ? 'a' : 'div', { class: 'seat-slot', href: href ?? null, title: text, 'aria-label': href ? text : null }, seatGauge(tally(seats), { label: text }));
}

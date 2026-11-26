# Probenplaner App: final design spec (build contract)

Winner: concept C "Evolve what exists", with A's iOS navigation, detail and settings anatomy grafted on. Two ideas come from B: the rose concert badge and the 50→100 status gradients. Today is `NOW` from data.js (2026-10-24 15:40). The prototype never reads the device clock. UI copy is German and uses "du".

## 1 Principles

1. **Native frame, own face.** iOS-grade shell (labelled tabs, collapsing large titles, push pages, bottom sheets, undo toasts) in the existing soft blue brand: #478cf4 family, 135° gradients, white rounded cards on a cool grey canvas, tinted soft shadows.
2. **The card survives.** Every rehearsal is the same card (date chip · time · exceptions-only line · category stripe · own answer · note). The editor *is* that card with a glow ring.
3. **Action first.** Only an open RSVP gets the big labelled "Zusagen | Absagen" bar; answered cards shrink to a status pill.
4. **Exceptions only.** Lists hide "Probe", "Probenraum" and "Tutti"; detail, Plan and print show everything.
5. **Gradient budget.** Max 3 saturated gradient elements of 44 px or larger per screen (`--grad-accent`, `--grad-hero`): primary CTA/FAB, hero or today chip, own avatar. Pale tints (`--grad-tint` tab capsule and date chips, `--grad-yes`, `--grad-no`) and monograms of 24 px or smaller don't count.
6. **Optimistic and reversible.** Writes update the UI in under 100 ms with undo; confirm only irreversible actions.
7. **Accessible.** Text ≥ 4.5:1, 44 px targets, icon + text status, visible `:focus-visible`, zoom allowed, inputs ≥ 16 px, nothing below 11 px.
8. **Buildable.** Plain CSS/ES modules, transform/opacity motion, app.js contract plus the four extensions in §4.6.

## 2 Tokens

`css/tokens.css`, copied verbatim. Components never use raw hex except the values documented in §5.

```css
/* css/tokens.css — Probenplaner design tokens (final build contract)
   html[data-theme="light"|"dark"] is set before first paint by index.html and app.js.
   Optional html attributes written by ui.js applyAppearance():
   data-palette="jeunesse", data-density="compact", data-motion="reduced", data-transparency="reduced".
   Breakpoints (media queries only, never custom properties): 430, 600, 840, 1200. */

:root {
  color-scheme: light;
  --theme-color: #f2f4f8;

  /* Brand primitives (#478cf4 family) */
  --brand-50: #eef4fe;
  --brand-100: #dce8fd;
  --brand-200: #bdd5fb;
  --brand-300: #93bbf8;
  --brand-400: #6ba3f6;
  --brand-500: #478cf4; /* icons, rings, tints only (3.32:1 on white) */
  --brand-600: #2f6fe0; /* filled controls, accent text (4.70:1 on white) */
  --brand-700: #2563eb; /* gradient end, accent text on tints (5.17:1) */
  --brand-800: #1d4ed8; /* pressed */
  --brand-a05: color-mix(in srgb, var(--brand-500) 5%, transparent);
  --brand-a10: color-mix(in srgb, var(--brand-500) 10%, transparent);
  --brand-a20: color-mix(in srgb, var(--brand-500) 20%, transparent);
  --brand-a30: color-mix(in srgb, var(--brand-500) 30%, transparent);
  --rose-500: #f4476b;

  /* Status primitives */
  --green-50: #ecfdf5;
  --green-100: #d1fae5;
  --green-500: #10b981;
  --green-600: #059669;
  --green-700: #047857;
  --red-50: #fef2f2;
  --red-100: #fee2e2;
  --red-500: #ef4444;
  --red-600: #dc2626;
  --red-700: #b91c1c;
  --amber-500: #f59e0b;
  --amber-700: #b45309;
  --violet-600: #7c3aed;
  --rose-700: #be123c;

  /* Rehearsal category palette: 3px stripe and swatches only, never status. Weiß = no stripe. */
  --cat-weiss: #e5e7eb;
  --cat-blau: #3b82f6;
  --cat-gruen: #10b981;
  --cat-gelb: #f59e0b;
  --cat-rot: #ef4444;
  --cat-lila: #8b5cf6;
  --cat-orange: #f97316;
  --cat-pink: #ec4899;
  --cat-tuerkis: #14b8a6;
  --cat-indigo: #6366f1;
  --cat-grau: #6b7280;
  --cat-schiefer: #475569;

  /* Section accents (emoji tiles); role colours come from data (tagColor) */
  --section-streicher: #478cf4;
  --section-blaeser: #7c3aed;
  --section-schlagwerk: #d97706;
  --section-harfe: #059669;

  /* Surfaces and text (OKLCH neutrals, brand hue 260) */
  --bg: oklch(0.968 0.006 260);              /* ≈ #f2f4f8 page canvas */
  --surface: #ffffff;                        /* cards, list groups */
  --surface-raised: #ffffff;                 /* opaque sheets, dialogs, menus */
  --surface-selected: var(--brand-a10);
  --fill: oklch(0.55 0.015 260 / 0.12);      /* segmented track, chips, value pills, inputs */
  --fill-strong: oklch(0.55 0.015 260 / 0.20);
  --fill-hover: oklch(0.55 0.015 260 / 0.06);
  --fill-pressed: oklch(0.55 0.015 260 / 0.14);
  --text: oklch(0.22 0.02 262);              /* ≈ #151b27 */
  --text-secondary: oklch(0.40 0.02 262);    /* ≈ #485062 */
  --text-muted: oklch(0.49 0.018 262);       /* ≈ #626a7a, 5.0:1 on white, 4.6:1 on --bg */
  --text-placeholder: oklch(0.62 0.012 262); /* placeholders and disabled only, never content */
  --icon-muted: oklch(0.22 0.02 262 / 0.36); /* chevrons, decorative icons */
  --separator: oklch(0.22 0.02 262 / 0.12);  /* 0.5px hairlines */
  --border-hairline: oklch(0.22 0.02 262 / 0.10);
  --border-strong: oklch(0.22 0.02 262 / 0.24);

  /* Accent roles */
  --accent: var(--brand-600);           /* text buttons, links, switch on, selected labels */
  --accent-strong: var(--brand-700);    /* text on accent tints */
  --accent-icon: var(--brand-500);      /* icons, rings, progress */
  --accent-pressed: var(--brand-800);
  --accent-tint: var(--brand-a10);
  --accent-tint-strong: var(--brand-a20);
  --on-accent: #ffffff;
  --focus-ring: var(--brand-500);

  /* Gradients. Saturated (count toward the 3-per-screen budget): --grad-accent, --grad-hero.
     Pale tints (do not count): --grad-tint, --grad-yes, --grad-no. */
  --grad-accent: linear-gradient(135deg, var(--brand-600) 0%, var(--brand-700) 100%);
  --grad-accent-pressed: linear-gradient(135deg, var(--brand-700) 0%, var(--brand-800) 100%);
  --grad-hero:
    radial-gradient(120% 100% at 100% 0%, color-mix(in srgb, var(--brand-300) 45%, transparent) 0%, transparent 55%),
    linear-gradient(135deg, var(--brand-600) 0%, var(--brand-700) 65%, var(--brand-800) 100%);
  --grad-tint: linear-gradient(135deg, color-mix(in srgb, var(--brand-500) 10%, transparent) 0%, color-mix(in srgb, var(--brand-500) 20%, transparent) 100%);
  --grad-yes: linear-gradient(135deg, var(--green-50) 0%, var(--green-100) 100%);
  --grad-no: linear-gradient(135deg, var(--red-50) 0%, var(--red-100) 100%);
  --grad-auth:
    radial-gradient(70% 45% at 15% 8%, color-mix(in srgb, var(--brand-500) 20%, transparent) 0%, transparent 70%),
    radial-gradient(60% 40% at 90% 95%, color-mix(in srgb, var(--rose-500) 10%, transparent) 0%, transparent 70%),
    var(--bg);

  /* Status semantics (always icon + text) */
  --status-yes-fg: var(--green-700);
  --status-yes-solid: var(--green-500);
  --status-yes-strong: var(--green-700);   /* solid fill with white text (5.5:1) */
  --status-yes-bg: rgb(16 185 129 / 0.14);
  --status-yes-bg-strong: rgb(16 185 129 / 0.24);
  --status-yes-ring: rgb(16 185 129 / 0.55);
  --status-no-fg: var(--red-700);
  --status-no-solid: var(--red-500);
  --status-no-strong: var(--red-600);
  --status-no-bg: rgb(239 68 68 / 0.12);
  --status-no-bg-strong: rgb(239 68 68 / 0.20);
  --status-no-ring: rgb(239 68 68 / 0.50);
  --status-open-fg: oklch(0.42 0.02 262);
  --status-open-solid: oklch(0.82 0.01 262);
  --status-open-bg: oklch(0.55 0.015 260 / 0.14);
  --status-warn-fg: var(--amber-700);
  --status-warn-solid: var(--amber-500);
  --status-warn-bg: rgb(245 158 11 / 0.16);
  --status-live: var(--green-500);
  --badge-bg: var(--red-600);
  --badge-fg: #ffffff;
  --type-fg: var(--violet-600);
  --type-bg: rgb(124 58 237 / 0.12);
  --concert-fg: var(--rose-700);
  --concert-bg: rgb(244 71 107 / 0.12);

  /* Materials: glass only on tab bar, scrolled header, sheets, bottom bar */
  --glass-bg: rgb(255 255 255 / 0.78);
  --glass-sheet-bg: rgb(246 247 250 / 0.88);
  --glass-filter: blur(20px) saturate(160%);
  --glass-edge: rgb(255 255 255 / 0.55);
  --glass-fallback: oklch(0.985 0.004 260);
  --scrim: rgb(15 23 42 / 0.32);
  --toast-bg: oklch(0.25 0.02 262 / 0.94);
  --toast-fg: #ffffff;
  --toast-action: #9cc2ff;

  /* Typography: system stack, weights 400/500/600/700 only */
  --font-sans: -apple-system, BlinkMacSystemFont, system-ui, 'Segoe UI Variable Text', 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Noto Color Emoji';
  --font-rounded: ui-rounded, 'SF Pro Rounded', -apple-system, system-ui, 'Segoe UI Variable Display', Roboto, sans-serif;
  --font-mono: ui-monospace, 'SF Mono', 'Cascadia Mono', 'Roboto Mono', Menlo, Consolas, monospace;
  --text-large-title: 700 34px/41px var(--font-sans);
  --text-title1: 700 28px/34px var(--font-sans);
  --text-title2: 600 22px/28px var(--font-sans);
  --text-title3: 600 20px/25px var(--font-sans);
  --text-headline: 600 17px/22px var(--font-sans);
  --text-body: 400 17px/22px var(--font-sans);
  --text-callout: 400 16px/21px var(--font-sans);
  --text-subhead: 400 15px/20px var(--font-sans);
  --text-meta: 400 14px/19px var(--font-sans);
  --text-footnote: 400 13px/18px var(--font-sans);
  --text-caption: 500 12px/16px var(--font-sans);
  --text-micro: 500 11px/13px var(--font-sans);
  --text-digit: 600 22px/28px var(--font-rounded);
  --text-digit-lg: 700 28px/34px var(--font-rounded);
  --text-digit-sm: 600 17px/22px var(--font-rounded);
  --tracking-large-title: -0.01em;
  --tracking-title: -0.006em;
  --tracking-caps: 0.04em;

  /* Spacing (4pt) */
  --space-1: 4px;
  --space-2: 8px;
  --space-3: 12px;
  --space-4: 16px;
  --space-5: 20px;
  --space-6: 24px;
  --space-8: 32px;
  --space-10: 40px;
  --gutter: 16px;

  /* Radii (inner radius = parent radius − padding) */
  --radius-xs: 6px;
  --radius-sm: 8px;
  --radius-md: 12px;
  --radius-lg: 16px;
  --radius-xl: 20px;
  --radius-2xl: 24px;
  --radius-full: 999px;

  /* Shadows (tinted hsl 220 40% 30%; never animated; no 'none' so lists can be combined) */
  --shadow-1: 0 1px 2px hsl(220 40% 30% / 0.06), 0 0 0 0.5px hsl(220 40% 30% / 0.08);
  --shadow-2: 0 1px 2px hsl(220 40% 30% / 0.06), 0 4px 10px hsl(220 40% 30% / 0.07), 0 12px 24px hsl(220 40% 30% / 0.08);
  --shadow-3: 0 1px 2px hsl(220 40% 30% / 0.05), 0 4px 8px hsl(220 40% 30% / 0.06), 0 12px 24px hsl(220 40% 30% / 0.08), 0 24px 48px hsl(220 40% 30% / 0.10), 0 40px 72px hsl(220 40% 30% / 0.08);
  --shadow-hero: 0 2px 6px color-mix(in srgb, var(--brand-700) 14%, transparent), 0 10px 28px color-mix(in srgb, var(--brand-700) 22%, transparent);
  --shadow-accent: 0 4px 14px color-mix(in srgb, var(--brand-600) 30%, transparent);
  --shadow-yes: 0 4px 14px rgb(16 185 129 / 0.25);
  --shadow-no: 0 4px 14px rgb(239 68 68 / 0.22);
  --shadow-thumb: 0 3px 8px rgb(0 0 0 / 0.12), 0 3px 1px rgb(0 0 0 / 0.04);
  --ring-edit: 0 0 0 2px color-mix(in srgb, var(--brand-500) 30%, transparent), 0 0 12px color-mix(in srgb, var(--brand-500) 15%, transparent), 0 0 24px color-mix(in srgb, var(--brand-500) 8%, transparent);
  --highlight-top: inset 0 0 0 0 transparent;

  /* Motion */
  --dur-instant: 100ms;
  --dur-fast: 150ms;
  --dur-base: 250ms;
  --dur-slow: 350ms;
  --dur-push: 300ms;
  --dur-highlight: 1500ms;
  --dur-toast: 3000ms;
  --dur-undo: 5000ms;
  --ease-standard: cubic-bezier(0.2, 0, 0, 1);
  --ease-enter: cubic-bezier(0.05, 0.7, 0.1, 1);
  --ease-exit: cubic-bezier(0.3, 0, 0.8, 0.15);
  --ease-push: cubic-bezier(0.2, 0.8, 0.2, 1);
  --ease-sheet: cubic-bezier(0.32, 0.72, 0, 1);
  --ease-spring: linear(0, 0.025 2.8%, 0.101 6.1%, 0.539 18.9%, 0.849 31.5%, 0.968 41.8%, 1.006 50.1%, 1.017 63.9%, 1.001 85.8%, 1);
  --press-scale: 0.97;

  /* Layers (sheets 100 and toasts 200 are set by base.css) */
  --z-sticky: 5;
  --z-fab: 15;
  --z-bottom-bar: 15;
  --z-header: 20;
  --z-tabbar: 20;
  --z-rail: 20;

  /* Safe areas and shell sizes (app.js overrides --header-height/--tabbar-height inline with measured px) */
  --safe-top: env(safe-area-inset-top, 0px);
  --safe-bottom: env(safe-area-inset-bottom, 0px);
  --safe-left: env(safe-area-inset-left, 0px);
  --safe-right: env(safe-area-inset-right, 0px);
  --header-h: 44px;
  --header-height: calc(var(--header-h) + var(--safe-top));
  --tabbar-h: 56px;
  --tabbar-height: calc(var(--tabbar-h) + var(--safe-bottom));
  --rail-w: 80px;
  --sidebar-w: 240px;
  --list-pane-w: 360px;
  --content-max-w: 680px;
  --detail-max-w: 760px;
  --wide-max-w: 1040px;
  --sheet-max-w: 640px;
  --dialog-w: 560px;
  --toast-max-w: 560px;

  /* Component sizes */
  --touch: 44px;
  --row-h: 44px;
  --row-h-2line: 60px;
  --row-h-dense: 56px;
  --card-pad: 12px;
  --card-min-h: 76px;
  --rsvp-h: 44px;
  --rsvp-h-lg: 50px;
  --att-row-h: 52px;
  --section-header-h: 32px;
  --date-sep-h: 36px;
  --date-chip-w: 44px;
  --date-chip-h: 48px;
  --date-chip-lg-w: 56px;
  --date-chip-lg-h: 60px;
  --date-chip-sm-w: 36px;
  --date-chip-sm-h: 40px;
  --stripe-w: 3px;
  --btn-h-lg: 50px;
  --btn-h-md: 44px;
  --btn-h-sm: 32px;
  --input-h: 52px;
  --segment-h: 36px;
  --chip-h: 32px;
  --pill-h: 28px;
  --badge-h: 20px;
  --fab-h: 56px;
  --icon-tab: 24px;
  --icon-row: 20px;
  --icon-inline: 16px;
}

:root[data-theme='dark'] {
  color-scheme: dark;
  --theme-color: #121419;
  --bg: oklch(0.18 0.01 260);
  --surface: oklch(0.225 0.012 260);          /* +4.5% L instead of shadows */
  --surface-raised: oklch(0.262 0.013 260);
  --surface-selected: color-mix(in srgb, var(--brand-400) 16%, transparent);
  --fill: oklch(0.75 0.01 260 / 0.16);
  --fill-strong: oklch(0.75 0.01 260 / 0.24);
  --fill-hover: oklch(0.75 0.01 260 / 0.08);
  --fill-pressed: oklch(0.75 0.01 260 / 0.18);
  --text: oklch(0.965 0.004 260);
  --text-secondary: oklch(0.83 0.01 260);
  --text-muted: oklch(0.72 0.012 260);
  --text-placeholder: oklch(0.58 0.012 260);
  --icon-muted: oklch(0.98 0 0 / 0.34);
  --separator: oklch(0.98 0 0 / 0.10);
  --border-hairline: oklch(0.98 0 0 / 0.08);
  --border-strong: oklch(0.98 0 0 / 0.22);
  --accent: var(--brand-400);                 /* ≈ 6.4:1 on dark surfaces */
  --accent-strong: var(--brand-300);
  --accent-icon: var(--brand-400);
  --accent-pressed: var(--brand-500);
  --accent-tint: color-mix(in srgb, var(--brand-400) 16%, transparent);
  --accent-tint-strong: color-mix(in srgb, var(--brand-400) 26%, transparent);
  --grad-hero:
    radial-gradient(120% 100% at 100% 0%, color-mix(in srgb, var(--brand-300) 28%, transparent) 0%, transparent 55%),
    linear-gradient(135deg, color-mix(in oklch, var(--brand-600) 88%, black) 0%, color-mix(in oklch, var(--brand-700) 78%, black) 100%);
  --grad-tint: linear-gradient(135deg, color-mix(in srgb, var(--brand-400) 18%, transparent) 0%, color-mix(in srgb, var(--brand-400) 28%, transparent) 100%);
  --grad-yes: linear-gradient(135deg, rgb(16 185 129 / 0.20) 0%, rgb(16 185 129 / 0.32) 100%);
  --grad-no: linear-gradient(135deg, rgb(239 68 68 / 0.20) 0%, rgb(239 68 68 / 0.30) 100%);
  --status-yes-fg: #34d399;
  --status-yes-bg: rgb(16 185 129 / 0.20);
  --status-yes-bg-strong: rgb(16 185 129 / 0.32);
  --status-yes-ring: rgb(52 211 153 / 0.60);
  --status-no-fg: #f87171;
  --status-no-bg: rgb(239 68 68 / 0.20);
  --status-no-bg-strong: rgb(239 68 68 / 0.30);
  --status-no-ring: rgb(248 113 113 / 0.60);
  --status-open-fg: oklch(0.83 0.01 262);
  --status-open-solid: oklch(0.45 0.012 262);
  --status-open-bg: oklch(0.75 0.01 260 / 0.16);
  --status-warn-fg: #fbbf24;
  --status-warn-bg: rgb(245 158 11 / 0.20);
  --type-fg: #c4b5fd;
  --type-bg: rgb(167 139 250 / 0.18);
  --concert-fg: #fda4af;
  --concert-bg: rgb(244 71 107 / 0.20);
  --glass-bg: rgb(24 27 36 / 0.72);
  --glass-sheet-bg: rgb(30 33 42 / 0.86);
  --glass-edge: rgb(255 255 255 / 0.06);
  --glass-fallback: oklch(0.21 0.012 260);
  --scrim: rgb(0 0 0 / 0.48);
  --toast-bg: oklch(0.32 0.014 260 / 0.96);
  --shadow-1: 0 0 0 0 transparent;
  --shadow-2: 0 0 0 0 transparent;
  --shadow-3: 0 0 0 1px rgb(255 255 255 / 0.08);
  --shadow-hero: 0 0 0 0 transparent;
  --shadow-accent: 0 0 0 0 transparent;
  --shadow-yes: 0 0 0 0 transparent;
  --shadow-no: 0 0 0 0 transparent;
  --shadow-thumb: 0 2px 6px rgb(0 0 0 / 0.40);
  --highlight-top: inset 0 1px 0 rgb(255 255 255 / 0.06);
}

/* Ensemble colour scheme 'Jeunesse' (white on #006aa6 ≈ 5.6:1); tints and gradients derive automatically */
:root[data-palette='jeunesse'] {
  --brand-300: #7ab8de;
  --brand-400: #3d97cc;
  --brand-500: #0073b4;
  --brand-600: #006aa6;
  --brand-700: #005c91;
  --brand-800: #004d7a;
  --rose-500: #e30513;
}

/* Darstellung › Dichte › Kompakt */
:root[data-density='compact'] {
  --card-pad: 8px;
  --card-min-h: 68px;
  --rsvp-h: 40px;           /* hit area stays 44 via ::before */
  --row-h-2line: 52px;
  --row-h-dense: 48px;
  --att-row-h: 44px;
  --date-chip-w: 40px;
  --date-chip-h: 44px;
  --text-digit: 600 20px/25px var(--font-rounded);
}

@media (min-width: 430px) { :root { --gutter: 20px; } }
@media (min-width: 600px) { :root { --tabbar-h: 0px; --tabbar-height: 0px; } }
@media (min-width: 840px) { :root { --header-h: 56px; } }
@media (min-width: 1200px) { :root { --list-pane-w: 380px; } }

@media (prefers-reduced-motion: reduce) {
  :root { --dur-base: 150ms; --dur-slow: 150ms; --dur-push: 150ms; --ease-spring: var(--ease-standard); --press-scale: 1; }
}
:root[data-motion='reduced'] { --dur-base: 150ms; --dur-slow: 150ms; --dur-push: 150ms; --ease-spring: var(--ease-standard); --press-scale: 1; }

@media (prefers-reduced-transparency: reduce) {
  :root { --glass-bg: var(--glass-fallback); --glass-sheet-bg: var(--surface-raised); --glass-filter: none; }
}
:root[data-transparency='reduced'] { --glass-bg: var(--glass-fallback); --glass-sheet-bg: var(--surface-raised); --glass-filter: none; }

@media (prefers-contrast: more) { :root { --text-muted: var(--text-secondary); --fill: var(--fill-strong); } }

@supports not (transition-timing-function: linear(0, 1)) { :root { --ease-spring: cubic-bezier(0.05, 0.7, 0.1, 1); } }
@supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
  :root { --glass-bg: var(--glass-fallback); --glass-sheet-bg: var(--surface-raised); --glass-filter: none; }
}
```

## 3 Typography

The font is the system stack (`--font-sans`): SF on Apple, Segoe on Windows, Roboto on Android. Delete all Google Fonts and Font Awesome. `--font-rounded` (ui-rounded) is used only for numerals: date-chip digits, KPI values, count badges and the attendance count.

| Role | Size/LH | Weight | Tracking | Use |
|---|---|---|---|---|
| largeTitle | 34/41 | 700 | −0.01em | Tab-root titles (`.large-title`), auth titles |
| title1 | 28/34 | 700 | −0.006em | Login wordmark line, KPI values ≥ 840 |
| title2 | 22/28 | 600 | −0.006em | Hero line, detail title, member name, KPI values |
| title3 | 20/25 | 600 | −0.006em | Account name, empty-state title, desktop toolbar title |
| headline | 17/22 | 600 | 0 | Card time, nav-bar title, sheet title, lg buttons, date separators |
| body | 17/22 | 400 | 0 | List-row labels/values, inputs, agenda titles, Hinweise, action-sheet items |
| callout | 16/21 | 400 / 600 | 0 | Dense rows: member and attendance names (600) |
| subhead | 15/20 | 400 (500 chips, 600 buttons) | 0 | Card meta, hero meta, segmented, chips, md/sm buttons, toast |
| meta | 14/19 | 400 (600 pills) | 0 | Dense meta, status-pill label, notes |
| footnote | 13/18 | 400 (600 headers) | 0 | Section headers (uppercase +0.04em), footers, legends, captions |
| caption | 12/16 | 500 (600 weekday/badges) | +0.01em | Badges, date-chip weekday (uppercase +0.04em), KPI labels, rail labels |
| micro | 11/13 | 500 / 600 | +0.01em | Tab labels and count badges only |

Rules:
- Numerals: `font-variant-numeric: tabular-nums` on every time, date, count and percentage.
- Ranges use an en dash with spaces: "19:00 – 21:30".
- Anything a person taps is ≥ 14 px. Inputs are ≥ 16 px.
- No gradient text anywhere. No uppercase below 12 px. No italics: notes use a quote icon.
- Row lines are one line with ellipsis. Titles wrap to at most 2 lines.
- `hyphens: auto` applies to paragraph text only.
- Icons are Lucide (loaded via `icon()`): 24 tab (stroke 1.75, active 2.25), 20 row, 16 inline, 22 header buttons.

## 4 Layout and navigation

### 4.1 Breakpoints

- **< 600 (phone):** bottom tab bar, large titles, single column, gutter 16 (20 at ≥ 430).
- **600–839:** 80 px rail with icon + 12/16 label and no tab bar. `.screen` is centred with max 680. Large titles and bottom sheets stay.
- **840–1199:**
  - The rail stays. The header becomes a 56 px toolbar with the title on the left (title3) and no large title (`.large-title{display:none}`, header title always visible).
  - Proben (both variants) uses list-detail `.split`: list 360 plus a detail pane whose inner max is 760.
  - Other screens are a single column, max 760; `.screen--wide` is max 1040.
  - Sheets become centred dialogs 560 wide.
- **≥ 1200:** the rail becomes a 240 px sidebar (rows of icon + label) and the list pane is 380.
- JS checks `ui.isWide()` = `matchMedia('(min-width: 840px)')`. Split screens re-render on its change event and remove the listener in `onLeave`.

### 4.2 Shell (base.css, replaces the current shell section)

The header and tab bar overlay the scroll container, so content scrolls under the glass.

```css
html,body{background:var(--bg);color:var(--text);font:var(--text-body)}
.app{display:grid;grid-template-columns:minmax(0,1fr);grid-template-rows:minmax(0,1fr);height:100dvh}
.app-header,.app-content,.app-tabbar{grid-area:1/1}
.app-header{align-self:start;z-index:var(--z-header)}
.app-tabbar{align-self:end;z-index:var(--z-tabbar)}
.app-content{padding-top:var(--header-height);padding-bottom:var(--tabbar-height);scroll-padding-top:var(--header-height)}
@media(min-width:600px){.app{grid-template-columns:auto minmax(0,1fr)}.app-rail{display:flex;grid-area:1/1;z-index:var(--z-rail)}.app-header,.app-content{grid-area:1/2}.app-tabbar{display:none}}
:root[data-shell=auth] :is(.app-header,.app-tabbar,.app-rail){display:none}
:root[data-shell=auth] .app-content{grid-column:1/-1;padding:0}
.layer-toasts{bottom:calc(var(--tabbar-height) + var(--bottom-bar-height,0px) + 12px)}
:focus-visible{outline:2px solid var(--focus-ring);outline-offset:2px}
```

Shell rules:
- Every sticky element inside content uses `top: var(--header-height)`.
- `.screen` gets `padding-bottom: 32px; margin-inline: auto`. Its max-width follows the breakpoints, and `.screen--wide` and `.screen--split` are the variants.
- `.screen--split` at ≥ 840 is `height: calc(100dvh - var(--header-height)); display: grid; grid-template-columns: var(--list-pane-w) minmax(0,1fr)`.
  - `.split__list` and `.split__detail` scroll independently (`overflow-y: auto; overscroll-behavior: contain`).
  - `.split__list` has a right hairline and stores its scrollTop in a module variable so it can be restored after the re-render.
  - `.split__detail` has padding 24 32 and inner max 760.
- Push keyframes stay as in base.css, retimed to `var(--dur-push) var(--ease-push)`.
- Reduced motion (OS or `data-motion=reduced`): transitions and animations drop to 1 ms, as the current base.css already does for the OS setting.

### 4.3 Header (`.app-header`, runtime DOM)

- **Bar.** 44 px + safe-top on phones, 56 px at ≥ 840. Grid `1fr auto 1fr` below 840 and `auto 1fr auto` at ≥ 840, where the title is left-aligned.
- **Background.** Transparent at the top. With `.is-scrolled`, a `::before` layer fades in over 150 ms with `--glass-bg` + `--glass-filter` and a 0.5 px `--separator` bottom line. At ≥ 840 the background is a solid `--bg` with a hairline at all times.
- **Title** (`.app-header__title`): headline 17/22, centred, ellipsis, max-width `calc(100% - 176px)`. On large pages it stays at opacity 0 until `.is-collapsed`, then fades in with translateY 4→0 over 150 ms.
- **Subtitle** (`.app-header__subtitle`, extension R4): caption 12/16 500 muted, under the title. It shows the save state.
- **Back** (`.app-header__back`): 44×44 with chevron-left 24, stroke 2.25, `--accent`. `.app-header__back-label` is visually hidden: back is chevron-only.
- **Actions** (`.app-header__action`): 44×44, icon 22 `--accent`. `.is-text` renders the label at 17/22 400 accent with padding 0 8; `.is-primary` makes it 600. `.app-header__dot` is an 8 px `--badge-bg` dot with a 2 px `--bg` ring.
- **Leading.** On tab roots, `setHeader({leading: ui.ensembleChip()})`.

### 4.4 Tab bar, rail, sidebar

**`.app-tabbar` (< 600)**
- Glass (`--glass-bg` + filter, top hairline). Height 56 + safe-bottom. Items `flex:1; max-width:168px`, centred.
- `.nav-item` is a column 56 tall with padding-top 4.
- `.nav-item__icon` is a 56×32 capsule with icon 24 (stroke 1.75, `--text-muted`).
- `.nav-item__label` is 11/13 500 `--text-muted`.
- Active `[aria-current=page]`: capsule background `--grad-tint`, icon `--accent` with stroke 2.25, label `--accent` 600.
- `.nav-item__badge`: absolute at top −2 and left `calc(50% + 6px)`, min 18×18, radius 9, padding 0 5, 11/13 600 rounded tabular, `--badge-fg` on `--badge-bg`, 2 px ring in the bar colour. Counts above 99 read "99+".
- Pressed: the capsule scales to 0.92 over 100 ms.
- Never greyed out; a tab without permission is omitted.

**`.app-rail` (600–1199)**
- 80 wide, `--surface` background, right hairline, padding-top 12 + safe-top.
- `.app-rail__brand`: icon area 48. Show `assets/icon.svg` (32) by swapping the img src via CSS `content`, or simply size `label.svg` to 32 high and crop it.
- Nav items stacked with gap 12: 80×56, 56×32 capsule plus label 12/16.

**Sidebar (≥ 1200)**
- The same element at 240 wide, padding 12.
- Brand: `label.svg` 120×24 at the top.
- `.nav-item` becomes a row: 44 tall, radius 12, padding 0 12, gap 12, icon 20, label 15/20 500.
- Active: `--accent-tint` background, `--accent` 600.
- The badge sits inline at the right.
- Collapse is P2.

### 4.5 Tabs per role (TABS in app.js; badges assigned by ui.js at import)

| Role (label) | Slot 1 | Slot 2 | Slot 3 | Slot 4 |
|---|---|---|---|---|
| member (Mitglied) | Proben `/promises` calendar-check, badge = open RSVPs | Plan `/plan` list-music | Profil `/profile` circle-user-round | — |
| leader (Stimmführung) | Proben `/promises` badge as above | Plan `/plan` | Team `/team` users-round, badge = undocumented | Profil `/profile` |
| conductor (Leitung) | Proben `/rehearsals` calendar-check (no badge) | Plan `/plan` | Team `/team` badge = undocumented | Mehr `/more` circle-ellipsis |

Badge rules:
- **Open RSVPs:** count of rehearsals where the own promise is pending, the rehearsal is not cancelled, its end is after `NOW` and its start is within 28 days. Lena gets 3.
- **Undocumented:** count of rehearsals in scope with start ≤ `NOW`, not cancelled, start within the last 60 days, and no attendance rows. Leitung gets 3 (10.09., 20.10., today). For the leader, only rehearsals targeting Horn count.

After every write, call `renderTabbar(TABS[role])`.

Team segments:
- Leitung: `Übersicht | Anwesenheit | Mitglieder`.
- Stimmführung: `Anwesenheit | Mitglieder`.

The segments are sibling routes. Switching uses `navigate(path,{replace:true,direction:'none'})` (instant, like iOS) and stores `store.set('teamSegment', path)`. `/team` replaces itself with `teamSegment`; the default is `/dashboard` for Leitung and `/attendance` for Stimmführung.

### 4.6 Runtime changes to app.js (design-system owner, P0)

- **R1:** `ROLE_LABELS = {member:'Mitglied', leader:'Stimmführung', conductor:'Leitung'}`.
- **R2:** `TABS` exactly as in §4.5.
- **R3:** `ROUTES` in this order. The order matters for `series`/`import` before `:id`.

| Pattern | Screen | tab | Roles | File (owner) |
|---|---|---|---|---|
| /login | login | – | all | auth.js (D) |
| /ensembles | ensembles | – | all | auth.js (D) |
| /promises | promises | /promises | member, leader | promises.js (A) |
| /promises/:id/responses | responses | /promises | leader | responses.js (A) |
| /promises/:id | promise-detail | /promises | member, leader | promise-detail.js (A) |
| /rehearsals | rehearsals | /rehearsals | conductor | rehearsals.js (B) |
| /rehearsals/series | series (P2) | /rehearsals | conductor | series.js (B) |
| /rehearsals/import | ai-import (P2) | /rehearsals | conductor | ai-import.js (B) |
| /rehearsals/:id/responses | rehearsal-responses | /rehearsals | conductor | rehearsal-edit.js (B) |
| /rehearsals/:id | rehearsal-edit | /rehearsals | conductor | rehearsal-edit.js (B) |
| /plan | plan | /plan | all | plan.js (D) |
| /plan/:id | plan-detail | /plan | all | plan.js (D) |
| /team | team | /team | leader, conductor | team.js (C) |
| /dashboard | dashboard | /team | conductor | dashboard.js (C) |
| /attendance/table | attendance-table (P2) | /team | leader, conductor | attendance.js (C) |
| /attendance | attendance | /team | leader, conductor | attendance.js (C) |
| /members/:id | member-detail | /team | leader, conductor | members.js (C) |
| /members | members | /team | leader, conductor | members.js (C) |
| /profile | profile | /profile | member, leader | profile.js (D) |
| /profile/:page | profile-page | /profile | member, leader | profile.js (D) |
| /more | more | /more | conductor | profile.js (D) |
| /more/:page | more-page | /more | conductor | profile.js (D) |
| /settings/roles | settings-roles (P2) | /more | conductor | settings.js (D) |
| /settings/sections | settings-sections (P2) | /more | conductor | settings.js (D) |
| /settings | settings | /more | conductor | settings.js (D) |

The `:page` values are `konto`, `darstellung` and `kalender`.

- **R4: `setHeader` additions.**
  - `leading` (Node): rendered in `.app-header__leading` when `back` is false.
  - `subtitle` (string): a `<span class="app-header__subtitle">` inside the title element.
  - Action objects gain `text:true` (renders the label as `.app-header__action.is-text`), `primary:true` (`.is-primary`) and `dot:true` (`.app-header__dot`).
  - Screens that are not tab roots but act as one (the Team segments) call `setHeader({title:'Team', large:true, back:false, leading})`.
  - `setHeader` merges options with the route defaults, not with the previous call. A screen that calls it again (for example to update the subtitle) passes its full config, or sets the text of `.app-header__subtitle` directly.

Everything else uses the existing contract:
- `openSheet` returns `{el, body, close}`; screens add `sheet--large` or `sheet--action` to `el`.
- Sheet footer buttons use the runtime classes `.button.button--{variant}`.
- `toast(message)` accepts SafeHTML, for example `` html`${icon('circle-check')} Zugesagt` ``.
- Auth screens set `document.documentElement.dataset.shell='auth'` and delete it in `onLeave`.

### 4.7 Shared modules and ownership

- **DS (design system):** tokens.css, base.css, css/components.css, css/screens.css (`.screen`, `.split`), js/ui.js, the app.js R1–R4 edits and js/screens/index.js. index.js imports placeholder.js, then `await Promise.allSettled([...files].map(f => import('./'+f+'.js')))`, so a missing file never breaks boot. index.html links `css/screens/{proben,leitung,team,plan,me,auth}.css`; a missing file only 404s.
- **A:** js/rsvp.js, promises.js, promise-detail.js (exports `renderRehearsalDetail(root, id, ctx, {embedded})`), responses.js (exports `renderResponses(root, id, ctx)`), css/screens/proben.css.
- **B:** rehearsals.js, rehearsal-edit.js (exports `renderEditor`), series.js, ai-import.js, css/screens/leitung.css.
- **C:** team.js, dashboard.js, attendance.js, members.js, css/screens/team.css.
- **D:** plan.js, profile.js, settings.js, auth.js, plan.css, me.css, auth.css.

`js/ui.js` exports (exact signatures):
- **People and stats:** `me(role)` (users[role]), `now()` (Date of NOW), `stats(rehearsalId, memberIds?)` (live counts from `promises`), `openCount(memberId)`, `undocumentedCount(role)`, `isWide()`, `guard(ctx, roles)` (if the role is not allowed: replace-navigates to the first tab, shows toast "Kein Zugriff" and returns false).
- **Text helpers:** `timeRange(r)` ("19:00 – 21:30" or "Fr 17:30 – So 15:30"), `relDay(r)` ("Heute", "Morgen", "in 3 Tagen", else "Di, 27. Okt."), `metaText(r)` (groupsText unless "Tutti", location unless "Probenraum", joined with " · "), `groupsSentence(instrumentIds)`.
- **Builders:** `dateChip(r,{size:'md'|'lg'|'sm'})`, `badges(r)`, `statusPill(kind,{interactive,label})`, `triad(counts,{size,onHero})`, `avatar(member,size)`, `rehearsalCard(r,{mode:'member'|'leader'|'mgmt'|'past'|'preview'|'edit', memberId, href})` (returns a Node; the RSVP bar is appended by the caller), `segmented(items,value,onChange)`, `emptyState({icon,title,text,action})`, `ensembleChip()`.
- **Sheets:** `openEnsembleSheet()`, `openNotificationsSheet(role)`, `openRemindSheet(rehearsal, memberIds)`, `openPicker({title, options, value, onSelect, search})` (single-select list sheet).
- **Appearance:** `applyAppearance()`. It runs on import and reads `pp.palette`, `pp.density`, `pp.motion`, `pp.transparency` from localStorage (inside try/catch).

`groupsSentence` rule:
- All instruments: "Tutti".
- All but one top-level section: "Tutti ohne X".
- Only whole top-level sections: their names joined with ", " and " und ".
- Otherwise the leaf names joined the same way.

If an export is missing at build time, write the markup from §5 directly. Never invent new class names.

### 4.8 Data rules

- **Current user:** member = Lena (12, Violine 2), leader = Benedikt (41, Horn), conductor = Stefan (1, canAttend false).
- **Writes:** writes mutate the data.js objects in memory: `promise.status/note/respondedAt`, attendance rows pushed or edited, rehearsal fields, `ensemble.settings`. After a write, the screen calls `ctx.rerender()` or replaces the affected node.
- **Counts:** use `ui.stats()`, never the frozen `promiseStats`.
- **Ensemble switcher:** shows the real ensemble plus the static demo row "Kammerchor Heidelberg" (Alt, "Nächste Probe Do, 29. Okt.", no badge). Choosing it shows the toast "Im Prototyp gibt es nur ein Ensemble."

### 4.9 Pages vs sheets, back

**Push a page** for an object or a long task: rehearsal detail, Rückmeldungen, editor, member detail, settings, profile subpages, Serie, KI-Import, Fehlzeiten.

**Use a sheet** (`openSheet`) for one decision or up to 3 fields: Absage, Antwort ändern, Erinnern, Anwesenheitsnotiz, Probe wählen, all editor pickers, Ensemble, Mitteilungen, Einladen, Vorlauf, confirmations.

**Action sheets** (`sheet--action`) are for 2–5 choices and every destructive confirmation.

Sheet rules:
- Sheets never stack: a sub-step replaces `body` content and shows `.sheet__back`.
- No centred alerts below 840.
- Back order is: sheet (runtime closes it on popstate/Escape), then the page.
- Tapping the active tab scrolls to the top or pops to the root (runtime behaviour).
- No gesture is the only path to an action.

## 5 Component catalogue (css/components.css)

Measurements are px. "Pressed" means `:active` with `transform: scale(var(--press-scale))` over 100 ms unless stated. Hover rules live only in `@media (hover:hover) and (pointer:fine)`.

**5.1 `.large-title`** (runtime h1 in content)
- Padding 3px var(--gutter) 8px. largeTitle font, tracking `--tracking-large-title`. 52 tall.
- Hidden at ≥ 840.

**5.2 `.ensemble-chip`** (`<button>`)
- 32 tall, radius 999, padding 0 10 0 4, gap 6, background `--fill`.
- `.ensemble-chip__avatar`: 24, radius 7, `--grad-accent`, initials "JS" 11/13 700 white.
- `.ensemble-chip__name`: `ensemble.shortName` 15/20 600, max 140, ellipsis.
- chevron-down 14 `--text-muted`.
- `::after` extends the hit area to 44.
- In `.app-header.is-collapsed` the name is hidden.
- aria-label "Ensemble: Junges Sinfonieorchester Heidelberg, wechseln". Opens `openEnsembleSheet()`.

**5.3 `.button`** (runtime class)
- Capsule (radius 999), with a gap of 8 between icon 20 and label.
- Sizes:
  - Default md: 44 tall, padding 0 16, 15/20 600.
  - `--lg`: 50 tall, padding 0 20, 17/22 600.
  - `--sm`: 32 tall, padding 0 12, 15/20 500, icon 16, 44 hit via `::before`.
  - `--block`: width 100%.
- Variants:
  - `--primary`: `--grad-accent`, white, `--shadow-accent`; pressed `--grad-accent-pressed`.
  - `--secondary`: `--fill` with `--text`.
  - `--tinted`: `--accent-tint` with `--accent-strong`.
  - `--plain`: transparent `--accent`; pressed opacity 0.4.
  - `--destructive`: `--status-no-bg` with `--status-no-fg`.
  - `--destructive-solid`: `--status-no-strong` with white. Use only in confirm sheets.
  - `--on-hero`: `rgb(255 255 255/.94)` with `--accent-strong`.
- Disabled: opacity 0.4, no shadow.
- Loading `.is-loading`: an 18 px spinner (2 px ring, 700 ms linear) replaces the icon and keeps the width.
- In `.sheet__actions` buttons are lg, block width, stacked with gap 8, primary first.

**5.4 `.icon-button`**
- 44×44, icon 22 `--accent` (in rows: 20 `--text-secondary`).
- Pressed: a 36 px `--fill` circle via `::before` opacity. aria-label is required.

**5.5 `.list-group`, `.list-row`, `.section-header`, `.section-footer`, `.icon-tile`**
- `.list-group`:
  - Margin 0 var(--gutter). Background `--surface`, radius 16, `box-shadow: var(--shadow-1), var(--highlight-top)`, `overflow: clip`.
  - Never nested inside another card.
- `.list-row`:
  - Grid `[lead auto][body 1fr][trail auto]`, gap 12, padding 0 16, min-height 44, body/value 17/22.
  - Hairline: `::after` at the bottom, starting at `var(--sep-inset,16px)`, `--separator`; none on the last row. Rows with a lead tile set `--sep-inset: 58px`.
  - `__title` 17/22; `__sub` 15/20 `--text-muted`; `__value` 17/22 `--text-muted`; chevron-right 16 `--icon-muted`.
  - Modifiers:
    - `--2line`: min 60.
    - `--dense`: min 56, title 16/21 600, sub 14/19.
    - `--switch`: min 64, title + 13/18 description.
    - `--action`: `--accent` with a leading icon 20.
    - `--destructive`: `--status-no-fg`, centred when alone.
    - `.is-external`: trailing arrow-up-right 16.
  - Pressed: background `--fill-pressed`, instant on and 150 ms off; no scale.
- `.section-header`:
  - Padding 16 calc(var(--gutter) + 16px) 6. 13/18 600, uppercase, +0.04em, `--text-muted`.
  - Optional trailing element: a 15/20 accent link or a 13/18 count.
- `.section-footer`:
  - Padding 6 calc(var(--gutter) + 16px) 0. 13/18 `--text-muted`.
  - Errors use `--status-no-fg` plus alert-circle 14.
  - Groups are separated by 24 px.
- `.icon-tile`:
  - 30, radius 8. Background `color-mix(in srgb, var(--tile) 14%, transparent)`, icon 18 in `var(--tile)`.
  - `--tile` is set inline from a token, for example `style="--tile:var(--amber-500)"`.
  - Sizes: `--sm` 28 (section emoji 16), `--lg` 56 radius 16 (icon 28), `--xl` 64 radius 18.

**5.6 `.segmented`** (`role=tablist`)
- Track 36 tall (32 at ≥ 840), radius 999, padding 2, background `--fill`, equal grid columns.
- The wrapper has margin 4 var(--gutter) 12.
- `.segmented__btn`: 15/20 500 `--text`. When `[aria-selected=true]`: background `--surface` (dark `--fill-strong`), `--shadow-thumb`, weight 600, 150 ms background fade. There is no sliding thumb.
- Optional `.badge--count` or a muted count after the label.
- Arrow keys move the selection. At most 4 segments.
- Sticky variant `.segmented--sticky`: `position: sticky; top: var(--header-height)`, padding 8 0, with `--glass-bg` + `--glass-filter` behind it. It acts as a header accessory (chrome), so glass is allowed.

**5.7 `.chip` and `.chip-row`**
- `.chip`: 32 tall, padding 0 14, radius 999, 15/20 500, background `--fill`, colour `--text`.
  - `[aria-pressed=true]`: `--accent-tint-strong` with `--accent-strong` and a leading check 14.
  - `.chip__count`: 13/18 `--text-muted`, tabular.
  - The remove "×" (16) is its own 44 hit.
- `.chip-row`: flex, gap 8, padding 0 var(--gutter), `overflow-x: auto`, scrollbar hidden. A mask fades the right 24 px only while it overflows.

**5.8 `.switch`** (`<input type=checkbox role=switch class=switch>`, `appearance: none`)
- Track 51×31, radius 999.
  - Off: `--fill-strong` with an inset hairline.
  - On: `--accent`.
- Knob: 27 white circle with `--shadow-thumb`, translateX 20 over 250 ms `--ease-spring`.
- Disabled: opacity 0.4. The whole `.list-row` is its `<label>`.

**5.9 Inputs**
- `.input`: 52 tall, radius 12, background `--surface`, inset 1 px `--border-hairline`, padding 0 16, 17/22, placeholder `--text-placeholder`.
  - Focus: inset 2 px `--focus-ring`.
  - Error: inset 2 px `--status-no-solid` plus a footer message.
- `.textarea`: min 96, padding 12 16, auto-grow to 200. `.textarea__count` "0/500" at 13/18 muted, right-aligned.
- `.field-row`: a list-row label with a min-width 112 label at 17/22 and an input flexing to the right (borderless, right-aligned, 17/22).
- `.value-pill`: background `--fill`, radius 8, padding 6 11, 17/22 tabular `--accent`. A transparent native `<input type=date|time class="value-pill__native">` is stretched over it (opacity 0.011, font-size 16). Invalid: colour `--status-no-fg`.
- `.search-field`: 36 tall, radius 10, background `--fill`, magnifier 16 at left 10, padding-left 32, 17/22, clear x-circle 18 in a 44 hit.

**5.10 `.date-chip`**
- 44×48, radius 12, background `--grad-tint`, grid centred.
- `.date-chip__wd` "DI": 12/16 600, uppercase, +0.04em, `--accent-strong`.
- `.date-chip__day` "27": `--text-digit`, tabular, `--text`.
- Modifiers:
  - `--today`: `--grad-accent`, both lines white, `--shadow-accent`.
  - `--past`: background `--fill`, both lines muted.
  - `--cancelled`: background `--fill`, day line-through.
  - `--range`: auto width (min 44, padding 0 6), day "6–8" at `--text-digit-sm`, weekday "FR–SO".
  - `--lg`: 56×60, weekday 13/18, day `--text-digit-lg`.
  - `--sm`: 36×40, weekday 11/13, day `--text-digit-sm`.
  - `--on-hero`: `rgb(255 255 255/.18)`, inset 1 px `rgb(255 255 255/.30)`, white text.
- aria-hidden.

**5.11 `.rehearsal-card`** (built by `ui.rehearsalCard`)

```html
<article class="rehearsal-card rehearsal-card--open" style="--stripe:#3b82f6" data-id="114">
  <a class="rehearsal-card__link" href="#/promises/114" aria-label="Di, 27. Okt., 19:00 bis 21:30, Tutti ohne Harfe, offen"></a>
  <div class="date-chip">…</div>
  <div class="rehearsal-card__main">
    <div class="rehearsal-card__line1"><span class="rehearsal-card__time">19:00 – 21:30</span></div>
    <div class="rehearsal-card__line2">[badges] <span class="rehearsal-card__meta">Tutti ohne Harfe</span> <span class="rehearsal-card__emoji">🎼📣</span> [count]</div>
    <p class="rehearsal-card__note">[quote icon] „Komme 15 Minuten später“</p>
  </div>
  <div class="rehearsal-card__trail">[status pill | response meter | select circle]</div>
  [div.rsvp — open member cards only]
</article>
```

Layout:
- Grid `var(--date-chip-w) minmax(0,1fr) auto`, column-gap 12. Padding `var(--card-pad) 12px var(--card-pad) 15px`, min-height `var(--card-min-h)` (76).
- Background `--surface`, radius 16, `box-shadow: var(--shadow-1), var(--highlight-top)`, margin 0 var(--gutter) 8, `position: relative; overflow: hidden`.
- `::before` is the stripe: left 0, inset-block 0, width 3, background `var(--stripe, transparent)`. Set `--stripe` only when the colour ≠ #e5e7eb.
- `__link` covers the card (`position: absolute; inset: 0; z-index: 0`). Controls sit at z-index 1, so they are never nested in the link.

Lines:
- line1: 28 tall, time at headline, tabular, ellipsis.
- line2: margin-top 2, 22 tall, one line, flex gap 6. In order:
  - `.badge--warn` (mgmt only)
  - `.badge--type` or `.badge--concert` when the type ≠ Probe
  - role badges
  - meta 15/20 `--text-muted` with ellipsis
  - up to 3 info emoji at 15 px plus "+n"
  - leader only: `.rehearsal-card__count`, pushed right with `margin-left: auto`. It reads "Horn 2/5" at 13/18 600 tabular with users 14. Below 65 % it turns `--status-warn-fg` and uses an alert-triangle icon.
- note: margin-top 6, 14/19 `--text-secondary`, quote 14, one line. It shows the own note or the cancel reason.
- trail: aligned to the top. Holds the `.status-pill` (member/leader answered), `.response-meter` (mgmt), or a 24 selection circle (P2).

Variants:
- `--open`: the `.rsvp` bar spans `grid-column: 1/-1`, margin-top 12. The card is about 132 tall.
- `--past`: past date chip. The pill shows the attendance outcome.
- `--cancelled`: time line-through `--text-muted`, pill "Fällt aus", note = `cancelReason`, no RSVP.
- `.is-selected` (split list): background `--surface-selected` plus a 3 px inset `--accent` bar on the left.
- `.is-highlighted`: an `::after` overlay `--accent-tint` with a 2 px accent inset ring, fading opacity 1→0 over `--dur-highlight` after 300 ms.
- `.is-updated`: re-render fade, opacity 0.4→1 over 150 ms.

Interaction:
- Pressed: scale 0.98, only when the press did not start on a control.
- Long-press 500 ms on a member card opens the "Antwort ändern" sheet. This is a shortcut; the pill does the same.

**5.12 `.rehearsal-card--mgmt`**
- Same skeleton, 76 tall. Trail is `.response-meter`, 64 wide, right-aligned column:
  - "39/53": 15/20 600 tabular `--status-yes-fg`.
  - a `.triad` 64×6, margin-top 4.
  - "6 offen": 13/18 muted, or "alle" with a check when 0.
- `.response-meter` is a `<button>` that opens `?seg=wer`.
- line2 starts with `.badge--warn` "Horn 40 %" (the worst critical section within 21 days, plus "+1"). It appears only when `settings.showRehearsalInsights` is on.

**5.13 `.rehearsal-card--edit`**
- Same anatomy as the member card, with `--ring-edit` instead of `--shadow-1`.
- Tap targets:
  - date chip: dashed 1.5 px `--brand-a30` outline, native date overlay.
  - each time: a span with a 1.5 px dashed underline (offset 3) over a native time input, 44 hit.
  - every badge, the meta text and the emoji teaser open the matching picker.
  - empty placeholders render as dashed badges "+ Art", "+ Ort", "+ Rolle" in `--text-muted`.
- Caption under the card: "So sehen Mitglieder diese Probe." at 13/18 muted, margin 8 32 0.
- On save, a check 14 `--status-yes-fg` fades in on the edited element and stays for 1200 ms.

**5.14 `.rehearsal-hero`**
- Margin 0 var(--gutter) 16, radius 20, padding 16, background `--grad-hero`, white text, `--shadow-hero`, min-height 124. The whole card is a link to the detail; pressed scale 0.98.
- `__line1`: title2 "Heute · 14:00 – 18:00" plus a trailing `.status-pill--on-hero`.
- `__meta`: 15/20 `rgb(255 255 255/.9)`, location + group, always both.
- `__divider`: 1 px `rgb(255 255 255/.22)`, margin 12 −16 0.
- `__live` (padding-top 10, 20 tall), one of:
  - Live: an 8 px `--status-live` dot with a pulse ring, "Jetzt" 13/18 600, the current agenda title at 15/20 600 with ellipsis, and a trailing "bis 16:45" at 13/18 `rgb(255 255 255/.85)`.
  - Not live: "Beginnt mit" plus the first item.
  - No agenda: the first info as emoji + text.
  - Member answer pending: replaced by `.rsvp.rsvp--on-hero`.
- `--mgmt`:
  - `__live` is replaced by `.triad--on-hero` (6 px) plus "46 zugesagt · 6 abgesagt · 1 offen" at 13/18.
  - Then `.button.button--on-hero` "Anwesenheit erfassen" (clipboard-check), margin-top 12.

**5.15 `.rsvp`** (`role=group`, aria-label "Antwort für Di, 27. Okt.")
- Grid of 2 columns, gap 8. `.rsvp__btn` is `var(--rsvp-h)` tall (44 hit always), radius 12, label 15/20 600 + icon 20 (stroke 2.25), with `aria-pressed`.
- Default states:
  - `--yes` "Zusagen": check, `--status-yes-bg`, `--status-yes-fg`.
  - `--no` "Absagen": x, `--status-no-bg`, `--status-no-fg`.
- Selected:
  - Label "Zugesagt" / "Abgesagt", icon circle-check / circle-x.
  - Background `--grad-yes` / `--grad-no` with an inset 1.5 px `--status-*-ring` and `--shadow-yes` / `--shadow-no`.
  - The other button turns neutral (`--fill`, `--text-secondary`) and stays tappable.
- `.rsvp--lg`: 50 tall, label 17/22.
- `.rsvp--on-hero`:
  - Default: `rgb(255 255 255/.16)` with an inset 1 px `rgb(255 255 255/.28)` and white text.
  - Selected: `#fff` with the status fg.
- `.is-locked`: opacity 0.4, lock 14, plus a footnote.
- The icon pops scale 0.8→1 over 250 ms `--ease-spring`.

**5.16 `.status-pill`** (`<span>`, or `<button>` when interactive)
- 28 tall, padding 0 10 0 8, radius 999, gap 4, icon 16 (stroke 2.5), label 14/19 600, tabular.
- Variants:
  - `--yes` "Zugesagt": circle-check.
  - `--no` "Abgesagt": circle-x.
  - `--open` "Offen": circle-dashed, `--status-open-*`.
  - `--warn` "Abweichung": triangle-alert.
  - `--cancelled` "Fällt aus": ban, open colours.
  - `--live` "Jetzt": a 6 px pulsing dot instead of the icon.
  - `--present` "Anwesend": user-check, yes colours.
  - `--absent` "Gefehlt": user-x, no colours.
  - `--excused` "Entschuldigt": circle-minus, open colours.
  - `--queued` "Wird gesendet": clock.
- `.is-interactive` adds chevron-down 12 and `::after` with inset −8px −4px for a 44 hit.
- `--compact`: 24 tall, icon only plus a visually hidden label.
- `--on-hero`: background `rgb(255 255 255/.94)`.

**5.17 `.badge`** (non-interactive)
- 20 tall, padding 0 6, radius 6, caption 12/16 600, nowrap, max-width 160, ellipsis.
- Variants:
  - `--type`: `--type-bg` / `--type-fg`. Used for Registerprobe, Probenwochenende, Vorstandssitzung and others.
  - `--concert`: `--concert-*`. Used for Generalprobe, Konzert, Konzertreise.
  - `--role`: radius 999. Background `color-mix(in srgb, var(--role) 14%, transparent)`. Text `color-mix(in oklch, var(--role) 70%, black)`; in dark mode `color-mix(in oklch, var(--role) 60%, white)`. `--role` comes from `tagColor` inline. The default role is never shown; show the first role plus "+n".
  - `--warn`: `--status-warn-*` with triangle-alert 12.
  - `--neutral`: `--fill` with `--text-secondary`.
  - `--count`: min 18×18, radius 9, 11/13 600 rounded, white on `--badge-bg`.

**5.18 `.triad`** (`role=img`, aria-label "39 zugesagt, 8 abgesagt, 6 offen")
- Flex with gap 2. Heights: `--sm` 4, default 6, `--lg` 8. Segments are radius 999 with `flex-grow` = count, coloured `--status-yes-solid`, `--status-no-solid`, `--status-open-solid`.
- A total of 0 renders one `--fill` bar.
- `--on-hero`: yes white, no `rgb(255 255 255/.55)`, open `rgb(255 255 255/.25)`.
- First paint: scaleX 0→1 from the left over 350 ms `--ease-enter`, once per visit.
- `.triad-legend`: icon 14 + count, 14/19 tabular, gap 12.

**5.19 `.date-sep`**
- Sticky at `top: var(--header-height)`, 36 tall, padding 12 calc(var(--gutter) + 4px) 4, background `--bg`.
- Label 15/20 600 `--text`. Trailing 13/18 muted tabular: the range "26.10. – 1.11." for week groups, "n Proben" for months.

**5.20 `.agenda`** (inside `.list-group`, padding 8 0)
- `.agenda__item`: grid `52px 20px 1fr`, min-height 44, padding 6 16 6 0.
- `.agenda__time`: 15/20 600 tabular `--text-secondary`, right-aligned, padding-right 8.
- `.agenda__dot`: 10 px, `--surface` fill with a 2 px `--accent-icon` border. A 2 px `--separator` line joins it to the next dot.
- `.agenda__title`: 17/22, with an optional `.badge--neutral` group ("Streicher").
- Parallel items at the same time stack inside one row with gap 4.
- `.is-done`: time and title `--text-muted`; the dot fills `--status-open-solid`.
- `.is-now`: a `--status-live` dot with a pulse ring, title 600, trailing `.status-pill--live`.
- `.agenda__day`: 13/18 600 uppercase muted, 28 tall ("FR, 6. NOV.").
- `.info-row`: list-row with a 32 emoji tile (radius 8, `--fill`, emoji 20) and 17/22 text that wraps.

**5.21 `.who-card`** ("Wer kommt?", a `.list-group`)
- `__summary`: padding 16, `.triad--lg` plus the legend.
- `.who-row`: 52 tall (64 when flagged).
  - Left: `.icon-tile--sm` with the section emoji (`--tile`: section accent).
  - Name 16/21 600.
  - A mini triad 80×6.
  - "18/28" at 14/19 tabular, then a chevron.
  - Flagged second line: 14/19 600 `--status-warn-fg` with alert-triangle 14: "Violine 2 nur 29 % Zusagen".
- `__remind`: a 44 action row "bell · 6 offen – Erinnern". After sending it reads "Erinnert gerade eben" in muted, disabled.
- Leader scope: `.person-row`s replace the who-rows.

**5.22 `.person-row` and `.avatar`**
- `.person-row`: `.list-row--dense` (56).
  - Avatar 36.
  - Name 16/21 600.
  - Meta 14/19 muted (instrument display name, plus role badges or the note).
  - Trailing: a compact status pill, the attendance "89 %" (12/16 600 tabular: ≥ 80 yes-fg, 65–79 warn-fg, < 65 no-fg), or a chevron.
- `.avatar`:
  - Circle at `--24/32/36/40/60/72/80`.
  - Background `linear-gradient(135deg, color-mix(in oklch,var(--avatar) 90%,white), color-mix(in oklch,var(--avatar) 80%,black))`.
  - White initials 600 at 0.4× the size. aria-hidden.
  - `.avatar--ring` (Stimmführung, Konzertmeister): box-shadow `0 0 0 2px var(--surface), 0 0 0 4px color-mix(in srgb,var(--ring) 55%,transparent)`.
  - Own and ensemble avatars use `--grad-accent`; ensemble avatars are a rounded square.

**5.23 Attendance components**
- `.att-context`: a card (`.list-group`), sticky at `top: calc(var(--header-height) + 52px)`.
  - Row A, 64 tall: ‹ (`.icon-button`) | centre `<button>` (sm date chip, "Sa, 24. Okt. · 14:00" at 17/22 600, "Aula Musikschule · läuft" at 13/18 muted, chevron-down 16) | ›.
  - Row B, padding 0 16 12: `.att-progress`.
- `.att-progress`: a 6 px track `--fill`, radius 999, with segments present `--status-yes-solid` and absent `--status-no-solid`. Caption 13/18 tabular: "12 von 53 erfasst · 41 Vorschläge".
- `.att-row`: list-row, min `var(--att-row-h)`.
  - Avatar 32.
  - Name 16/21 600 (+ " · Violine 1" 14/19 muted in mixed groups).
  - Line 2, 14/19, one of: member note (quote 14), leader note (pencil 14), "keine Antwort", or the deviation "Zugesagt, aber gefehlt" / "Abgesagt, aber da" in 600 `--status-warn-fg` with alert-triangle 14.
  - A deviation adds a 3 px `--status-warn-solid` bar on the left.
- `.att-toggle` (`role=radiogroup`, aria-label "Anwesenheit Lena Hartmann"):
  - 112×36, 44 hit, radius 999, `--fill` track, padding 2.
  - `.att-toggle__btn` is 54×32 with icon 14 + label 14/19 600: "Da" (check) and "Fehlt" (x). Unset buttons are `--text-muted`.
  - Confirmed `[aria-checked=true]`: `--status-yes-bg-strong` / `--status-no-bg-strong` with the status fg.
  - `.is-proposed` (pre-filled from the RSVP): the proposed side gets a 1.5 px dashed inset in its status solid colour on a transparent background, with the status fg at 500.
  - Change: background cross-fades over 150 ms.

**5.24 `.kpi-strip`**
- One `.list-group` split into 3 columns by vertical hairlines, 84 tall.
- `.kpi`: padding 12 16.
  - `__value`: `--text-digit` tabular (title1 at ≥ 840).
  - `__label`: 12/16 500 muted, 2 lines.
- `<button>`: opens a definition sheet. No sparklines.

**5.25 `.plan-row` and `.plan-table`**
- `.plan-row`: a 56-tall list-row, padding-left 19 with the 3 px stripe.
  - Date column 40: weekday 12/16 600 muted over the day in `--text-digit-sm`; today in `--accent`.
  - Line 1: 16/21 600 tabular time + " · Registerprobe" in `--type-fg`.
  - Line 2: 14/19 muted "Probenraum · Tutti ohne Harfe". Defaults are shown here.
  - Trailing: the own status icon 20 (circle-check yes, circle-x no, circle-dashed open) with an aria-label.
- `.plan-table` (≥ 840 and print):
  - Columns Tag | Datum | Zeit | Ort | Art | Stimmen | Antwort.
  - Rows 44 at 15/20; sticky thead 13/18 600 uppercase muted.
  - The first cell has a 3 px left border in the stripe colour.

**5.26 `.sheet`** (runtime DOM: `.sheet-host > .sheet-backdrop + .sheet > .sheet__grabber, .sheet__header(.sheet__title, .sheet__close), .sheet__body, .sheet__actions`)
- Phone:
  - Bottom-anchored, width 100% (max 640, centred), radius 24 24 0 0.
  - Background `--glass-sheet-bg` + `--glass-filter` with an inset 1 px `--glass-edge`. `--shadow-3`. Max-height 92dvh.
  - Grabber: 36×5, radius 3, `--fill-strong`, top 6, in a 20 px zone (the header extends the drag area).
  - Header: 56 tall. Title 17/22 600 centred. Close: a 30 px `--fill` circle with x 16 inside a 44 hit.
  - Body: padding 0 var(--gutter) 16, scrolls. Inner `.list-group`s stay opaque `--surface`.
  - Actions: padding 12 var(--gutter) calc(12px + var(--safe-bottom)).
  - Backdrop `--scrim`.
  - Motion: enter translateY(100%)→0 over 350 ms `--ease-sheet`; exit 250 ms `--ease-exit`.
- `.sheet--large`: height 92dvh.
- `.sheet--action`:
  - Close and title are hidden. `.sheet__lead` holds the message (13/18 muted, centred).
  - Body holds `.action-list`: a `.list-group` of 56-tall rows with icon 20 + 17/22 `--accent`. `--destructive` rows are `--status-no-fg`. `.is-checked` adds a trailing check.
  - The footer is a single `.button--secondary` "Abbrechen".
- `.sheet__lead`: 15/20 `--text-secondary`, margin 0 4 12.
- `.sheet__back`: a chevron button 44 at the body top-left for sub-steps.
- ≥ 840 (fine pointer):
  - The host centres the sheet. Width 560, radius 24, max-height 80dvh.
  - Motion: opacity plus scale 0.96→1 over 250 ms.

**5.27 `.toast`** (runtime)
- Min 48 tall, radius 16, padding 12 16, width `min(100% - 2*gutter, 560px)`.
- Background `--toast-bg` + `--glass-filter`, `--toast-fg`, 15/20. Leading icon 20 when present (circle-check `#34d399`, alert-circle `#f87171`).
- `.toast__action`: 15/20 600 `--toast-action`, 44 hit.
- Enter: translateY(16)→0 + opacity over 250 ms `--ease-enter`. Exit: 150 ms.
- Durations: 3 s default, 5 s with undo, 8 s for imports.

**5.28 `.fab`** (Leitung Proben, < 840)
- Fixed: right `calc(var(--gutter) + var(--safe-right))`, bottom `calc(var(--tabbar-height) + 16px)`, z `--z-fab`. aria-label "Neue Probe".
- The button itself is transparent with two layers:
  - `.fab__extended`: 56 tall, padding 0 20 0 16, radius 999, `--grad-accent`, `--shadow-accent` + `--shadow-2`, plus 24 + "Probe" 17/22 600 white.
  - `.fab__compact`: a 56 circle with plus 24.
- `.is-collapsed` (set after scrolling down more than 24 px; removed when scrolling up): the layers cross-fade with opacity + scale 0.9↔1 over 150 ms. Width is never animated.

**5.29 `.bottom-bar`**
- Fixed above the tab bar: bottom `var(--tabbar-height)`, left 0 (at ≥ 600 left `--rail-w`; at ≥ 1200 left `--sidebar-w`), right 0. Padding 8 var(--gutter).
- `--glass-bg` with a top hairline. The inner content is max 640, centred.
- Holds one `.button--primary.button--lg.button--block`.
- While mounted, the screen sets `--bottom-bar-height: 66px` on `<html>` and adds the same bottom padding. It removes both in `onLeave`.
- Enter: translateY(100%)→0 over 250 ms.

**5.30 Other components**
- `.banner`: margin 0 var(--gutter) 12, radius 16, padding 12 16, grid `24px 1fr`, title 15/20 600 + text 13/18. Variants:
  - `--info`: `--accent-tint`.
  - `--warn`: `--status-warn-bg`.
  - `--error`: `--status-no-bg`.
  - Never glass.
- `.empty-state`: column centred, max 300, margin 48 auto, gap 8. Contents: `.icon-tile--lg` (`--tile: var(--brand-500)`), a title3 title, 15/20 muted text and an optional tinted md button.
- `.progress-ring`: an SVG of 24/40, track 3 `--fill`, progress `--status-yes-solid` with round caps. When complete it becomes a filled green circle with a white check 14.
- `.swatch-grid`: 4×3 grid of 44 swatches with names at 12/16 below. The selected swatch has a 3 px `--accent` ring and a check.
- `.tree` rows: 44 tall, indent 20 per level, a `.check` circle (24, 2 px `--icon-muted` border; checked: `--accent` fill with a white check 16; mixed: a minus), emoji 18, label 17/22 and a muted count.
- `.bulk-bar` (P2): replaces the tab bar with a glass bar of 56 + safe-bottom. Actions are icon 24 + 11/13 label.
- `.detail-card`: the rehearsal-detail header card. White, radius 20, `--shadow-2`, `::before` 4 px stripe, padding 16. Grid `56px 1fr`, gap 14. `__badges` row margin-top 12 (wraps). `__place` row 44 with a top hairline.
- `.identity`: centred block (avatar, title2 name, 15/20 muted meta, badges), padding 16 16 24. At ≥ 840 it switches to a left-aligned row.
- `.quick-actions`: a 3-column grid with gap 8 and margin 0 var(--gutter) 24. Each tile is 64 tall, radius 16, `--surface`, `--shadow-1`, icon 22 `--accent` over a 14/19 500 label.
- Ensemble monogram: the first two letters of `shortName` in uppercase ("JS"), on `--grad-accent`, used everywhere.

## 6 Screen specs

Every screen root is the runtime `section.screen`. Roles that are not allowed hit `ui.guard`.

### 6.1 Proben — `#/promises` (member, leader; `?seg=past`, `?rehearsal=ID`)

**Header.** `setHeader({title:'Proben', leading: ensembleChip(), actions:[{icon:'bell', label:'Mitteilungen', dot: unread>0, onClick: openNotificationsSheet}]})`. The large title is "Proben".

**Anstehend, top to bottom:**
1. `.segmented` "Anstehend [3] | Vergangen". It scrolls away.
2. `.rehearsal-hero` for the first eligible, non-cancelled rehearsal that has not ended. Show it only if it is ongoing or starts within 7 days; today that is 113.
   - "Heute · 14:00 – 18:00" with the pill "Zugesagt".
   - "Aula Musikschule · Tutti ohne Harfe".
   - Live line "Jetzt · Tutti: Brahms 2, 4. Satz · bis 16:45". It recomputes every 30 s from `NOW`; the prototype keeps NOW fixed.
3. Groups:
   - "Diese Woche" (until Sunday) and "Nächste Woche" with week ranges, then one group per month ("November 2026" …). Empty groups are omitted.
   - Each group is a `.date-sep` plus its cards; the hero rehearsal is excluded.
   - Lena's list:
     - 114: open, with the RSVP bar.
     - 115: Registerprobe · Streicher, Zugesagt.
     - 116: Aula Musikschule, note „Komme 15 Minuten später“.
     - 117: range chip FR–SO 6–8, "Fr 17:30 – So 15:30", Probenwochenende, Abgesagt with note „Hochzeit meiner Schwester“.
     - 130: "Fällt aus · Weihnachtspause".
4. Footnote "Alle Proben bis 26. Jan. 2027", centred, 13/18 muted.

Leader cards add "Horn 2/5" on line 2.

**Vergangen:**
- Summary row: `.progress-ring` 28 + "Deine Anwesenheit: 89 % in dieser Saison".
- Month groups, newest first. Cards use `--past`. The pill shows the attendance outcome if documented, otherwise the own answer.
- Pills are interactive only if `settings.allowPastEdit`.

**Interactions (js/rsvp.js):**
- **Zusagen:**
  - Set the promise and `haptic('light')`.
  - Re-render the card as `.is-updated` after 400 ms; the open card becomes a pill card.
  - Refresh the tab badge.
  - Toast "Zugesagt · Di, 27. Okt." with "Rückgängig" (5 s), which restores the previous status and note.
- **Absagen with `forceDeclineReason` off:**
  - Save the decline immediately, then open the sheet "Absage für Di, 27. Okt.".
  - Lead text: "Magst du einen Grund angeben? Deine Stimmführung und die Leitung sehen ihn."
  - `.chip-row` of chips Krank / Beruflich / Schule/Uni / Urlaub / Familie / Terminkonflikt. A chip fills the textarea.
  - Textarea "Grund (optional)", max 500, with a counter.
  - Actions "Grund speichern" (primary) and "Ohne Grund" (plain).
  - Closing keeps the decline. Toast "Abgesagt · Di, 27. Okt." with "Rückgängig".
- **Absagen with `forceDeclineReason` on:**
  - The sheet opens before anything is saved.
  - "Absage senden" stays disabled until the reason has 3 or more characters. "Abbrechen" saves nothing.
- **Status pill tap or 500 ms long-press:** action sheet "Antwort ändern" with:
  - Zusagen (circle-check, checked if current)
  - Absagen
  - Grund bearbeiten (declined only; opens the Absage sheet pre-filled)
  - Antwort zurücksetzen (only if `allowAttendanceReset`; rotate-ccw)
- **Card tap:** push `/promises/:id`. **Hero tap:** the same.
- **Bell:** Mitteilungen sheet.
  - Notifications filtered by audience: member sees "all"; leader and conductor see all.
  - Rows are 64 tall: icon tile, title 16/21 600, text 14/19 (2 lines), relative time 12/16, unread dot.
  - Tapping a row marks it read and navigates to the rehearsal route for the role.
- **Deep link `?rehearsal=ID`:** scroll the card to the centre and apply `.is-highlighted`. Past IDs switch to Vergangen first.

**Empty states:**
- No upcoming rehearsals: `emptyState` calendar-check "Keine Proben geplant" / "Sobald neue Proben eingetragen sind, siehst du sie hier." with "Probenplan ansehen".
- When everything is answered, the badge disappears.

**≥ 840:** `.screen--split`.
- List pane: segmented, hero (min 104), cards.
- Detail pane: `renderRehearsalDetail(selected, {embedded:true})`. Default is the hero rehearsal; `/promises/:id` selects that ID.
- Row tap: `navigate('/promises/'+id,{replace:true,direction:'none'})`.

### 6.2 Probe-Detail — `#/promises/:id` (also `#/plan/:id`)

**Header.**
- Back. Title `formatDate` "Di, 27. Okt.".
- Action share-2 "Link kopieren": copies the deep link and shows the toast "Link kopiert".
- Conductor (plan-detail only): text action "Bearbeiten", which goes to `/rehearsals/:id`.

**Content:**
1. **`.detail-card`**: a white card, radius 20, `--shadow-2`, 4 px stripe, padding 16.
   - Grid: `.date-chip--lg`, then title2 "Dienstag, 27. Oktober" over 15/20 muted "19:00 – 21:30 · in 3 Tagen".
   - Badges row (wraps): type, group badge "Tutti ohne Harfe" with users 14 (always shown), roles.
   - Location row, 44 tall, with a top hairline: map-pin 20 accent, "Probenraum" (defaults shown), and trailing "Karte" with arrow-up-right. It opens `https://maps.google.com/?q=…` in a new tab.
2. **"KOMMST DU?"** (pending) or **"DEINE ANTWORT"** (only for `canAttend` and eligible users):
   - `.rsvp.rsvp--lg`, same logic as 6.1. Selected state is shown in place.
   - When declined: a note row card with a quote, the note and a "Bearbeiten" plain button.
   - Footnote "Geantwortet am 20. Okt.".
   - Past: read-only, showing "Anwesend" or "Gefehlt" if documented.
3. **"WER KOMMT?"** (leader only):
   - `.who-card` scoped to Horn: summary "2 zugesagt · 2 abgesagt · 1 offen".
   - Person rows sorted Absage → Zusage → offen, then alphabetically. Aaron Beck; Jan Ludwig „Hochzeit im Freundeskreis“; Benedikt Auer; Nele Thomsen; Mia Santoro.
   - Remind row "1 offen – Erinnern".
   - Rows are read-only, with no chevron. The section header's trailing link "Alle anzeigen" opens `/promises/:id/responses`.
4. **"HINWEISE"**: a `.list-group` of `.info-row`s ("🎼 Noten mitbringen", "📣 Bitte bis Montag 12 Uhr zu- oder absagen").
5. **"ABLAUF"**: `.agenda`. Today's rehearsal is live (113: 15:30 is `.is-now`, earlier rows `.is-done`). Multi-day rehearsals get day headers.
6. **"ANWESENHEIT"** (leader, past or today): a row going to `/attendance?rehearsal=ID`.

Missing infos or agenda omit their group.

**Edge states:**
- Cancelled: a `.banner--error` "Fällt aus · Weihnachtspause" at the top; RSVP, Wer kommt and Ablauf are hidden.
- Not eligible (from Plan › Alle): `.banner--info` "Diese Probe betrifft deine Stimme nicht."
- Unknown ID: empty state "Diese Probe gibt es nicht mehr" with "Zu den Proben".

**Rückmeldungen page — `#/promises/:id/responses` and `#/rehearsals/:id/responses`** (`renderResponses`):
- Header: back, title "Rückmeldungen", subtitle "Di, 27. Okt.".
- `.segmented--sticky`: "Offen 6 | Absagen 8 | Zusagen 39" (`?filter=open|no|yes`, default open).
- Groups per instrument: a `.section-header` with emoji + "Violine 2" + count, then person rows (note in meta).
- `?section=` scrolls to that section.
- In Offen with more than 0 rows, a `.bottom-bar` reads "6 per Push erinnern" and opens `openRemindSheet`.
- Leader scope: own register only.

**Remind sheet** (`ui.openRemindSheet`):
- Lead text: "6 haben für Di, 27. Okt. noch nicht geantwortet."
- Rows: avatar 32, name, instrument, then a trailing bell 16 accent, or bell-off muted "kein Push" (demo: `id % 5 === 0` is unreachable).
- Actions: primary "5 per Push erinnern", plain "Namen kopieren".
- Toast "Erinnerung an 5 von 6 gesendet" with `haptic('success')`.

### 6.3 Proben (Leitung) — `#/rehearsals` (`?seg=past`)

**Header.** Leading ensemble chip; action magnifier "Suchen". The large title is "Proben".

**Layout:**
1. Segmented "Anstehend 21 | Vergangen 12".
2. When search is active: `.search-field` "Proben durchsuchen" plus "Abbrechen". It filters live on date text, weekday, type, location, groupsText, tags, info and agenda titles. No hits: "Keine Treffer für „Bach“".
3. `.rehearsal-hero--mgmt` for an ongoing rehearsal or one within 48 h (113): "Heute · 14:00 – 18:00 · läuft", then the triad, "46 zugesagt · 6 abgesagt · 1 offen", then "Anwesenheit erfassen" → `/attendance?rehearsal=113`.
4. `.date-sep` groups of `--mgmt` cards. For example 114 reads "19:00 – 21:30", [⚠ Violine 2 29 % +1], "Tutti ohne Harfe", "🎼📣", meter 39/53 · 6 offen.
   - 124 shows the role badge "Vorstand".
   - 130 is cancelled.
5. `.fab` "Probe" opens an action sheet:
   - "Neue Probe" (calendar-plus, subtitle "Mi, 27. Jan. · 18:00 – 20:00"). It creates an in-memory rehearsal with defaults: day after the last upcoming rehearsal, 18:00–20:00, Probenraum, all instruments, #e5e7eb. Then it pushes `/rehearsals/:id?new=1`.
   - "Serie erstellen" (repeat) → `/rehearsals/series`.
   - "KI-Import" (sparkles; hidden unless `allowRehearsalImport`) → `/rehearsals/import`.

**Interactions:** card tap → `/rehearsals/:id`; response meter → `/rehearsals/:id?seg=wer`.

**Vergangen:** the trail shows `statusPill` "Erfasst" (yes) or the badge-warn "Nicht erfasst", which goes to `/attendance?rehearsal=ID`.

**Empty state:** "Noch keine Proben" with primary "Erste Probe anlegen".

**≥ 840:**
- `.screen--split` with the editor in the detail pane.
- The FAB becomes a header action `{text:true, primary:true, label:'Neue Probe'}`.

**P2:** filter sheet; selection mode with `.bulk-bar`; Serie page (weekday toggles 44, "Jede Woche | Alle 2 Wochen", Von/Bis value pills + presets, date preview with exclude switches, bottom bar "12 Proben erstellen"); KI-Import page ("Prompt kopieren", paste textarea, preview cards with checks, "5 Proben importieren" + 8 s undo).

### 6.4 Probe bearbeiten — `#/rehearsals/:id` (`?seg=wer`, `?new=1`)

**Header.**
- Back. Title `formatDate(date)`, which updates live.
- Subtitle "Gespeichert" ("Speichert…" for 400 ms after each change).
- Text action "Fertig" (primary) → `back()`. The title is "Neue Probe" while `?new=1` and unchanged.

**Content:**
1. `.rehearsal-card--edit` (the member view, live) plus its caption.
2. `.segmented` "Details | Wer kommt? 6".

**Details:**
- **"WANN":** field rows with value pills:
  - Datum "Di, 27.10.2026"
  - Beginn "19:00"
  - Ende "21:30"
  - Switch "Mehrtägig" (on adds a "Bis" row)
  - An end time before the start: the pill turns red and the footer reads "Ende liegt vor dem Beginn." Nothing is saved until it is valid.
- **"WAS & WO":** value rows with chevrons:
  - Art: "Probe" in muted + "Standard" → `openPicker`, with types and counts plus "Eigene Art…".
  - Ort: "Probenraum" → `openPicker` with search, recent locations with counts, and a free text of max 50 characters.
  - Farbe: dot + name → `.swatch-grid` sheet.
  - Footer: "Art „Probe“ und Ort „Probenraum“ sind Standard und erscheinen nicht in der Probenliste."
- **"FÜR WEN":**
  - Register "Tutti ohne Harfe" → `.sheet--large` `.tree` picker. Quick chips: Tutti, Streicher, Bläser. The live `groupsSentence` sits in the sheet lead. "Fertig" is disabled, with the hint "Wähle mindestens ein Register.", when nothing is selected.
  - Rollen "Alle" → a sheet of switch rows with role badges.
  - Footer: "Mit Rollen sehen nur Mitglieder mit dieser Rolle die Probe."
- **"ABLAUF":**
  - Rows 52: a time value-pill 72 wide, a borderless title input (17 px, placeholder "Programmpunkt"), and a remove minus-circle 22 `--status-no-fg` (44 hit).
  - Add row "Programmpunkt hinzufügen". The first item starts at the rehearsal start; each later item is the latest + 30 min, at most 23:59. Items sort on blur.
- **"HINWEISE":**
  - Rows 52: an emoji button 44 (radius 12, `--fill`), a text input and a remove button.
  - The emoji sheet is a curated 8-column grid of 48: 📍👔🎼🥪🚌🅿️⚠️📝⏰🧳💊🎻🥁🪑🎉📸📋☕🍽️🛏️🎄❌💶📣. "Anderes Emoji…" opens an input for the system keyboard.
  - Add row "Hinweis hinzufügen".
- **"INTERN":** tag chips with "×" plus a dashed "+ Tag". Footer "Nur für die Leitung sichtbar."
- **Action group:**
  - "Duplizieren…" opens a sheet with a date value pill (+7 days) and "Duplizieren". It replace-navigates to the copy and shows the toast "Dupliziert für Di, 3. Nov." with Rückgängig.
  - "Probe absagen…" opens a reason sheet; when cancelled the row reads "Absage zurücknehmen".
- **Destructive group:** "Probe löschen" opens an action sheet "Probe am Di, 27. Okt. löschen? 47 Rückmeldungen gehen verloren." with "Probe löschen". After deleting it pops and shows the toast "Probe gelöscht" with Rückgängig (5 s).

Every change mutates the rehearsal immediately. Text debounces 600 ms. There is no save button and nothing asks on leave.

**Wer kommt?:**
- `.who-card` with the summary "39 zugesagt · 8 abgesagt · 6 offen".
- who-rows for Streicher, Holzbläser, Blechbläser and Schlagwerk.
- Flagged lines from insights ("Violine 2 nur 29 % Zusagen", "Horn nur 40 % Zusagen").
- A row tap goes to `/rehearsals/:id/responses?section=…`.
- Remind row.
- Then "AUFFÄLLIGKEITEN" rows with trend icons, when the insights setting is on.

**≥ 840:** rendered inside the split detail pane, with the same content and pickers as centred dialogs.

### 6.5 Team › Übersicht — `#/dashboard` (Leitung)

**Header.** `setHeader({title:'Team', large:true, back:false, leading})` plus `.segmented--sticky` "Übersicht | Anwesenheit | Mitglieder".

**Content:**
1. `.kpi-strip`:
   - "73 %" Rückmeldungen, nächste 4 Wochen: (attending + declined) / total over non-cancelled rehearsals that start within 28 days.
   - "61 %" Zusagen, nächste 4 Wochen.
   - "74 %" Anwesenheit, letzte 10 Proben: present / records over the last 10 documented rehearsals.
   - Footer "Stand: heute, 15:40".
   - A KPI tap opens a definition sheet.
2. **"BRAUCHT AUFMERKSAMKEIT"** with a count. `--2line` rows, at most 5, then "Alle anzeigen":
   - Critical sections within 21 days, with an amber tile, e.g. "Violine 2 nur 29 % Zusagen" / "Di, 27. Okt. · 2 von 7" → `/rehearsals/114?seg=wer`.
   - Pending answers within 7 days, with a bell tile: "6 Rückmeldungen offen" / "Di, 27. Okt." plus a trailing tinted sm "Erinnern" that opens the remind sheet.
   - Undocumented rehearsals, with a red clipboard-x tile: "Anwesenheit nicht erfasst" / "Di, 20. Okt. · Probe" → `/attendance?rehearsal=112`.
   - Nothing to flag: a single row with a green check "Alles im grünen Bereich".
3. **"NÄCHSTE PROBEN"** with the trailing link "Alle" → `/rehearsals`: 3 mgmt cards.
4. **"AUFFÄLLIGKEITEN"** (insights on): arrow-down-right red / arrow-up-right green rows.
5. **"AKTIVITÄT"**: 5 notifications as 64-tall rows.

**≥ 840:** `.screen--wide` grid of `1fr 380px`. Left: KPIs, attention, next rehearsals. Right: Auffälligkeiten and Aktivität.

### 6.6 Team › Anwesenheit — `#/attendance` (`?rehearsal=ID`)

**Header.** Team (as in 6.5), sticky segments (2 for the leader), action "table-2" "Fehlzeiten" → `/attendance/table` (P2).

**Default rehearsal:** the most recent one that has started (113).

**Content:**
1. **`.att-context`:** "Sa, 24. Okt. · 14:00", "Aula Musikschule · läuft", progress "0 von 53 erfasst · 52 Vorschläge".
   - ‹ and › step through started rehearsals.
   - The centre opens the "Probe wählen" sheet: started, non-cancelled rehearsals grouped by month. Rows show a sm chip, "Probe · 19:00", and a `.progress-ring` or "Erfasst" pill / warn "Offen".
2. **`.chip-row`:** Alle 53 · Fehlt 6 · Abweichung 0 · Unbestätigt 53.
   - Fehlt: absent, confirmed or proposed.
   - Abweichung: a confirmed status that contradicts the RSVP.
   - Unbestätigt: no record.
   - Filters are single-select, and sections with no matches hide.
3. **Sections.**
   - Leitung: parent groups Streicher, Holzbläser, Blechbläser, Schlagwerk.
   - Leader: "Hörner · 5".
   - Each section is a `.section-header` with the count, `.badge--neutral` "✕ 3" and `.badge--warn` "! 1", followed by `.att-row`s.
   - Proposals come from the RSVP: attending → Da, not_attending → Fehlt, pending → none ("keine Antwort").
   - Members not invited are excluded.
4. **`.bottom-bar`** "Alle 52 Vorschläge übernehmen" while proposals exist.
   - It confirms only proposals and never overwrites records.
   - The dashed outline turns solid with a 12 ms stagger, capped at 300 ms.
   - Toast "52 übernommen · 1 offen" with Rückgängig and `haptic('success')`.

**Interactions:**
- **Toggle tap:** writes `{status:'present'|'absent', present, comment, recordedBy}` and `haptic('light')`. Counters update live.
- **Row body tap:** opens the note sheet.
  - Title: the name. Lead: "Zugesagt · „note“".
  - `.segmented` "Da | Fehlt".
  - Chips: verspätet, früher gegangen, entschuldigt, krank. They append to the note; late maps to present and excused to absent.
  - Textarea "Notiz (nur für Leitung und Stimmführung)".
  - Plain destructive "Erfassung entfernen" when a record exists. Primary "Speichern".

**Empty and edge states:**
- No started rehearsal: "Noch keine Probe zum Erfassen".
- Fully documented: no bottom bar; progress "53 von 53 erfasst ✓".

**≥ 840:** single column, max 760. At ≥ 1200 each section's rows form 2 columns.

**P2 Fehlzeiten:** period chip "Letzte 10 Proben", a "Mind. 2" stepper, a matrix with a sticky name column, ✓ ✕ – cells on tints, and A4 landscape print.

### 6.7 Plan — `#/plan` (`?scope=mine|all`, `?past=1`)

**Header.** Leading chip; action printer "Drucken" → `window.print()`. The large title is "Probenplan".

**Content:**
1. Segmented "Meine Stimme | Alle" (default Meine Stimme). Hidden for Leitung.
2. Footnote "Stand: Sa, 24. Okt. 2026, 15:40 · 22 Proben".
3. Plain button "Vergangene Proben einblenden (12)", which toggles to "ausblenden". It prepends past months, using `overflow-anchor` to keep the position.
4. Sticky month header (`.date-sep`) plus a `.list-group` of `.plan-row`s. Cancelled rows are struck through with "Fällt aus".
5. Footer "Probenplan · Junges Sinfonieorchester Heidelberg".

**Interactions:** row → `/plan/:id`.

**Print:** a hidden `.plan-table` is always rendered.
- `@media print`: A4 portrait, margin 12 mm, 10 pt.
- Header: "Probenplan"; "Junges Sinfonieorchester Heidelberg · Meine Stimme (Violine 2)" or "· Alle Proben"; "Stand: 24.10.2026, 15:40 Uhr"; range.
- The thead repeats on every page. Stripe uses `print-color-adjust: exact`.
- The shell, glass and dark tokens are reset.

**≥ 840:** `.screen--wide` shows the `.plan-table` instead of the list.

### 6.8 Team › Mitglieder — `#/members` and `#/members/:id`

**Header.** Team; sticky segments; Leitung action "user-plus" "Einladen" opens the invite sheet.

**List:**
1. `.search-field` "Name oder Stimme suchen".
2. Role `.chip-row`: Stimmführung 13 · Konzertmeister 1 · Vorstand 5 · Notenwart 2 (AND filter).
3. Footnote "54 Mitglieder".
4. Sections Streicher / Holzbläser / Blechbläser / Schlagwerk / Harfe. Each is a `.section-header` with an emoji tile, name and count, then `.person-row`s: avatar 36 (with ring), name, instrument + role badges, attendance %, chevron.
5. Footer "Leitung: Stefan Eberhardt, Julia Reinhardt".

**Detail page:**
- Header: back; the title appears when collapsed.
- Centred identity: avatar 72, title2 name, 15/20 muted "Violine 2 · seit Sept. 2023", role badges.
- Quick actions: 3 tiles of 64 (radius 16, `--surface`, `--shadow-1`, icon 22 accent + 14/19 500):
  - "E-Mail" (mailto)
  - "Erinnern" (only with open answers; toast "Erinnerung an Lena gesendet")
  - "Kopieren" (copies the email)
- **"ANWESENHEIT":** ring 40 "89 %", "Letzte 10 Proben", a strip of 10 status dots.
- **"OFFENE ANTWORTEN":** up to 3 rows.
- **Leitung only:**
  - "IM ENSEMBLE": Stimme › (picker), Rollen › (switch sheet; the default role is locked).
  - "KONTO": "Passwort zurücksetzen" opens a confirm action sheet, then a result sheet with the password in mono 17/22 on `--fill`, "Kopieren" and the footnote "Wird nur einmal angezeigt."
  - Destructive "Aus Ensemble entfernen" opens a confirm action sheet, then pops and shows a toast.

Stimmführung gets a read-only detail.

**Invite sheet:**
- Mono 15/20 link card "probenplaner.example/invite/a7c3e91f" and "12× genutzt".
- Primary "Link teilen" (`navigator.share`, falling back to copy) and tinted "Link kopieren".
- Switch "Nur JMD-Accounts".
- Plain destructive "Neuen Link erstellen" with an inline confirmation.

**Search with no hits:** "Kein Mitglied „xyz“".

### 6.9 Einstellungen — `#/settings` (Leitung, pushed from Mehr)

**Header.** Back; title "Einstellungen"; subtitle "Gespeichert" after a change.

**Content:**
1. Header row 76: ensemble avatar 60 "JS", the name in 17/22 600 (2 lines), and 15/20 muted "Jugendmusik Heidelberg e.V. · 54 Mitglieder · Saison 2026/27".
2. **"RÜCKMELDUNGEN"** (`--switch` rows, writing `ensemble.settings` live):
   - "Grund bei Absage verlangen" / "Mitglieder müssen beim Absagen einen Grund angeben."
   - "Antwort zurücksetzen erlauben" / "Mitglieder können ihre Antwort wieder auf offen stellen."
   - "Vergangene Proben änderbar" / "Antworten auf vergangene Proben bleiben änderbar."
3. **"ERINNERUNGEN":** switch "Push-Erinnerungen"; row "Standard-Vorlauf" "1 Std. 30 Min." opens a radio sheet (30 Min., 1 Std., 1 Std. 30 Min., 2 Std., 1 Tag).
4. **"FUNKTIONEN":** switches "Proben-Insights (Beta)" and "KI-Probenplan-Import".
5. **"STRUKTUR":** "Registerstruktur" (P2 page), "Rollen & Rechte" "6" (P2 page), "Einladungslink" "12× genutzt" (invite sheet).
6. Destructive "Ensemble löschen" opens a large sheet:
   - Consequence list.
   - Input "Gib „JSO Heidelberg“ ein".
   - "Endgültig löschen" (destructive-solid), enabled only on an exact match. The prototype then shows the toast "Im Prototyp nicht möglich".

### 6.10 Profil — `#/profile` (member, leader) and `#/profile/:page`

**Header.** Leading chip; large title "Profil".

**Content:**
1. Account row 76: avatar 60 (`--avatar` from data, "LH"), title3 "Lena Hartmann", 15/20 muted "Violine 2 · seit Sept. 2023", chevron → `/profile/konto`.
2. **"ENSEMBLE":**
   - "JSO Heidelberg" "Wechseln" (ensemble sheet)
   - "Meine Stimme" "Violine 2" (picker with confirm footer "Du siehst danach andere Proben.")
   - "Meine Rollen" "Keine" (self-assignable roles only)
3. **"BENACHRICHTIGUNGEN":** switch "Push-Erinnerung" (bell tile amber); "Vorlauf" "1 Std. 30 Min." (preset sheet). Footer "Wir erinnern dich vor Proben, auf die du noch nicht geantwortet hast."
4. **"KALENDER":** "Kalender-Abo" "Aus" → `/profile/kalender`.
5. **"APP":** "Darstellung" "System" → `/profile/darstellung`; "App installieren" (download tile) opens a 3-step iOS guide sheet.
6. **"HILFE":** "Hilfe & Tipps" (sheet), "Datenschutz" and "Impressum" (external).
7. Destructive "Abmelden" → action sheet → `/login`.
8. Footer "Probenplaner 2.6 · JSO Heidelberg".
9. **"PROTOTYP":** segmented "Mitglied | Stimmführung | Leitung" → `setRole`.

**Subpages:**
- **konto:**
  - "Anzeigename" field row (saved on blur, subtitle "Gespeichert").
  - "E-Mail" opens a sheet with an explicit "Speichern".
  - "Passwort ändern" opens a 3-field sheet.
  - Destructive "Ensemble verlassen" (action sheet with "Du kannst später über einen Einladungslink jederzeit wieder beitreten.") and "Account löschen" (typed "LÖSCHEN").
- **darstellung:**
  - "ERSCHEINUNGSBILD" segmented "System | Hell | Dunkel" → `setTheme`.
  - "FARBSCHEMA" radio rows "Standard" / "Jeunesse" (3 colour dots) → `data-palette`.
  - "DICHTE" "Standard | Kompakt" → `data-density`.
  - Switches "Bewegung reduzieren" and "Transparenz reduzieren".
  - Everything persists to `pp.*`.
- **kalender:** segmented "Nur ansehen | Mit Zu-/Absagen".
  - Abo: "Kalender-Abo erstellen", then rows "Im Kalender öffnen" (webcal), "Link kopieren" (shows "Kopiert ✓" for 2 s), and destructive "Link zurücksetzen".
  - CalDAV: copy rows Server-URL / Benutzername / Kalender-Passwort (mono, middle ellipsis) and the warning "Das Kalender-Passwort ist nicht dein Probenplaner-Passwort."

### 6.11 Mehr — `#/more` (Leitung) and `#/more/:page`

- Large title "Mehr". Account row "Stefan Eberhardt" / "Leitung · JSO Heidelberg" → `/more/konto`.
- **"ENSEMBLE":**
  - Ensemble "Wechseln"
  - "Einstellungen" (gray tile) → `/settings`
  - "Rollen & Rechte" "6" → `/settings/roles`
  - "Registerstruktur" → `/settings/sections`
  - "Mitglieder einladen" (invite sheet)
- **"KALENDER"**, **"APP"**, **"HILFE"**, Abmelden, version and **"PROTOTYP"** as in Profil.
- Benachrichtigungen is hidden because `canAttend` is false.
- Mehr never holds frequent destinations.

### 6.12 Anmelden — `#/login` (auth shell; P2 modes `?mode=register|welcome|invite`)

**Layout.** Full bleed on `--grad-auth`, min-height 100dvh, padding `calc(var(--safe-top) + 56px) var(--gutter) calc(var(--safe-bottom) + 16px)`, flex column.

**Content:**
1. Brand block, centred: `icon.svg` 72 (radius 16, `--shadow-2`), `label.svg` 32 high, and 15/20 muted "Proben planen. Zusagen in Sekunden."
2. Flexible spacer.
3. Action block (max 400, centred):
   - `.button--primary.button--lg.button--block` "Mit JMD-Account anmelden" with a JMD mark in a 24 px white circle.
   - A divider "oder mit E-Mail" (13/18).
   - `.list-group` with field rows "E-Mail" (type email, `autocomplete=username`, `inputmode=email`) and "Passwort" (current-password, eye toggle 44 with `aria-pressed`).
   - Plain "Passwort vergessen?", right-aligned.
   - Tinted lg "Anmelden".
   - "Noch kein Account? Registrieren".
   - "Datenschutz · Impressum" (13/18, 44 hits).

**Behaviour:**
- Submit: in-button spinner "Anmelden…", then `navigate` to the first tab (`replace`).
- Empty fields: inline error "E-Mail oder Passwort stimmt nicht." with a red inset and `haptic('error')`. No modal.

**≥ 840:** split screen. Left 50 % is the brand panel on `--grad-hero` (white icon 96, white wordmark, 17/22 tagline). Right is the form column of 400.

### 6.13 Ensembles — `#/ensembles` (auth shell)

- Top bar: plain "Abmelden". Large title "Ensembles", subhead "Wo probst du heute?".
- `.list-group` of 72-tall rows:
  - Ensemble avatar 44 (rounded square, `--grad-accent`, monogram "JS" 15/20 700).
  - Name 17/22 600 (2 lines).
  - 15/20 muted "Violine 2 · Nächste Probe Di, 27. Okt." plus the role badge when not the default.
  - Trailing `.badge--count` "3" and a chevron.
  - Plus the Kammerchor demo row.
- Row "Einladungslink einlösen" (link tile) opens a sheet with an input "Link oder Code einfügen", tinted "Aus Zwischenablage einfügen" and primary "Beitreten".
- Footer "Angemeldet als lena.hartmann@example.com".
- Tapping the real ensemble goes to the first tab.
- No ensembles: empty state "Noch in keinem Ensemble".

## 7 Motion and feedback

- **Tokens:** instant 100, fast 150, base 250, slow 350, push 300 ms. Easing: standard, enter, exit, push (iOS), sheet (iOS), spring (1.7 % overshoot, used only for the switch knob, RSVP icon pop and badge pop). Only transform and opacity animate; never shadow, blur, width or height.
- **Push/back:** runtime View Transitions. The new page slides from 100 % while the old one moves −30 % and fades to 0.5, over `--dur-push` `--ease-push`; back is mirrored. The header cross-fades through its own transition name. Without View Transitions, the `.is-entering` keyframes run.
- **Tab and segment switches:** instant (direction `'none'`), as on iOS.
- **Sheets:** enter 350 ms `--ease-sheet`, exit 250 ms, backdrop 250 ms. Drag-to-dismiss past 1/3 of the height or faster than 0.5 px/ms (runtime). No overshoot, so there is no bottom gap.
- **Press:** scale 0.97 over 100 ms (cards 0.98, tab capsule 0.92). Rows use a background change instead. Header buttons dim to opacity 0.4.
- **RSVP:** UI updates in under 100 ms; the icon pops; the card re-renders after 400 ms with `.is-updated`; a 5 s undo toast follows; errors roll back with an error toast.
- **Lists:** inserted cards fade + translateY 8→0 over 250 ms plus `.is-highlighted`. Triads scaleX on first paint. Badge changes pop 1→1.2→1.
- **Live:** the active agenda and hero dot pulse ring: scale 1→2.2, opacity 0.45→0, 2000 ms, infinite.
- **Reduced motion:** no slides, pulse, stagger or press scale; durations ≤ 150 ms or instant.
- **Haptics** (runtime `haptic`, Android only):
  - selection: tabs and segments
  - light: RSVP, toggles, switches
  - medium: long-press
  - success: bulk confirm and reminders sent
  - error: failed save or login

## 8 Icon mapping (Lucide)

**Navigation:** Proben calendar-check · Plan list-music · Team users-round · Profil circle-user-round · Mehr circle-ellipsis · back chevron-left · disclosure chevron-right · menus chevron-down.

**Status:**
- Zugesagt circle-check · Abgesagt circle-x · Offen circle-dashed · Fällt aus ban · Abweichung triangle-alert.
- Anwesend user-check · Gefehlt user-x · Entschuldigt circle-minus · Wird gesendet clock · locked lock.

**Rehearsal:** Ort map-pin · Für users · Hinweis note quote · Ablauf list-ordered · Teilen share-2 · Karte arrow-up-right · Mitteilungen bell · Erinnern bell-ring / bell-off.

**Leitung:** Neue Probe calendar-plus · Serie repeat · KI-Import sparkles · Suchen search · Filter sliders-horizontal · Duplizieren copy · Probe absagen ban · Löschen trash-2 · remove minus-circle · add plus-circle · Farbe palette · Art tag · Register users-round · Rollen shield · Tags hash.

**Team:** Anwesenheit erfassen clipboard-check · nicht erfasst clipboard-x · Fehlzeiten table-2 · Einladen user-plus · E-Mail mail · Kopieren copy · Passwort key-round · Entfernen user-minus · trends arrow-up-right / arrow-down-right.

**Settings:** Einstellungen settings · Registerstruktur network · Darstellung sun-moon · Kalender calendar-sync · App download · Hilfe circle-help · Abmelden log-out · Drucken printer · Link link.

## 9 Do and Don't

**Do:**
- Use the classes and tokens from this spec.
- Hide defaults in lists and show them in detail.
- Pair every status with icon + text.
- Undo instead of confirm.
- Write German "du" copy exactly as given.
- Use `NOW` for time.
- Use tabular numerals.
- Keep 44 px targets.
- Put sheets in `openSheet`.
- Clean up `data-shell`, `--bottom-bar-height` and listeners in `onLeave`.

**Don't:**
- Hamburger menus or drawers.
- Actions in the tab bar, or greyed tabs.
- Centred alerts on phones.
- Stacked sheets.
- `outline: none`.
- Zoom locks.
- Text below 11 px.
- Colour-only status.
- Gradient text.
- Glass on content cards.
- Cards nested in cards.
- Hover-only affordances.
- Animated blur, shadow, width or height.
- `100vh`.
- 52 px icon-only buttons.
- Raw hex in screen CSS.
- `Date.now()`.
- New class names for things this spec already names.
- Screen-level View Transitions: `startViewTransition` is reserved for the router.

## 10 Definition of done (check at 375×812 light and dark, at 1280×800, and in all 3 roles)

- **All screens:** no console errors; the tab is highlighted; header title and back are correct; focus rings are visible; no horizontal scroll; dark mode is legible; the layout survives 200 % zoom.
- **Proben:**
  - Lena sees the live hero, 114 with the RSVP bar, 115 and 116 as pill cards, 117 with its range chip and note, and 130 marked "Fällt aus".
  - Zusagen collapses 114 and lowers the badge 3→2; undo restores both.
  - Absagen opens the sheet with chips.
  - The pill opens "Antwort ändern".
  - Vergangen shows outcomes.
  - At ≥ 840, list-detail works.
- **Detail:** the live agenda shows 15:30 as now; the leader sees Horn Wer kommt? with 5 people; the remind sheet sends and shows its toast; cancelled and not-eligible banners work; `/plan/:id` highlights Plan.
- **Leitung Proben:** the mgmt hero, meters and warn badges render; the FAB action sheet works; Neue Probe opens the editor; split works at ≥ 840.
- **Editor:**
  - Changing the date, time, type, place, colour or register updates the edit card live and shows "Gespeichert".
  - End before start shows the error.
  - Agenda and Hinweise can be added and removed.
  - Delete with undo works.
  - Wer kommt? shows sections and leads to Rückmeldungen.
- **Übersicht:** KPIs read 73/61/74 %; attention rows navigate; Erinnern works.
- **Anwesenheit:** proposals appear dashed; toggling commits; filters count; bulk confirm with undo works; the note sheet saves; ‹ › and the picker change the rehearsal; the leader is scoped to Hörner.
- **Plan:** the scope switch works; past rows toggle; print preview shows the A4 table with the header; a table appears at ≥ 840.
- **Mitglieder:** search and role filter work; the detail shows quick actions; Leitung-only groups are hidden for Stimmführung; the invite sheet works.
- **Einstellungen:** each switch changes behaviour live (forced Absage reason, insights hidden, KI-Import hidden).
- **Profil/Mehr:** role switch, theme, palette, density and reduce-motion apply instantly and persist; subpages push with back.
- **Login and Ensembles:** the chrome is hidden; inline errors show; the flow enters the app; the switcher sheet works from every tab root.

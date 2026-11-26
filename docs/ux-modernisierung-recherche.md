# Probenplaner 2026: UX-Modernisierung. Recherchebericht für den Product Owner

Stand: 24.10.2026. Grundlage sind neun Recherchedimensionen (Quellen überwiegend 2025/26) und das Code-Inventar der Views. Alles, was als „Judgement“ markiert ist, ist eine Gestaltungsentscheidung auf Basis der Quellen und kein zitierter Normwert.

## 1 Zusammenfassung

**These:** Probenplaner wirkt nicht wegen des Farbstils wie eine alte PHP-Seite, sondern wegen Struktur und Maßstab. Dazu gehören eine Seitenleiste mit bis zu 10 Einträgen plus Hamburger, eine 64-px-Leiste, 52-px-Buttons, 10-px-Mikrotext, zentrierte SweetAlert-Dialoge und Voll-Reloads ohne Übergang. Der weiche Look (Blau #478cf4, 135°-Verläufe, runde Karten) kann bleiben. Er wird aber auf die Chrome-Ebene (Leisten, Sheets, Primäraktionen) konzentriert; Inhalte werden ruhiger und flacher („flat content, glass chrome“). Die Webplattform liefert 2026 fast alles, was ein natives Gefühl in einer serverseitig gerenderten PHP-App braucht, ohne SPA-Umbau.

**Die zwölf Kernentscheidungen:**

1. **Navigation:** Persistente Bottom-Tab-Bar mit 3–4 beschrifteten Tabs, rollenabhängig, unter 600 px. Rail bei 600–839 px, Zwei-Pane-Arbeitsfläche ab 840 px, Sidebar (240 px) ab 1200 px. Kein Hamburger mehr.
2. **Typografie:** Systemschrift (`system-ui`) statt fünf Google-Fonts, iOS-Skala (17/22 Body, 15/20 Meta, 13/18 Footnote, Boden 11 px). Nichts unter 12 px außer 11-px-Badges. Eingaben immer 16 px.
3. **RSVP:** Statt zweier 52-px-Icon-Kreise ein beschriftetes Zwei-Segment-Control „Zusagen | Absagen“ (44 px). Offene Zeilen sind aufgeklappt und handlungsstark, beantwortete Zeilen kompakt mit Statuspille.
4. **Absage-Grund:** Bottom-Sheet mit Schnell-Chips statt zentriertem SweetAlert. Die Absage wird sofort gespeichert, danach folgt ein „Rückgängig“-Toast.
5. **Rückmeldungen für Leitung/Stimmführung** wandern in die Probe-Detailseite („Wer kommt?“ mit Stimmen-Balken und „Offene erinnern“). Das Dashboard mit Analytics-Kacheln als Erstbildschirm entfällt.
6. **Editor:** Der Inline-Editor bleibt als Prinzip (Tap-to-Edit, Auto-Save, gleiche Optik für Lesen/Bearbeiten). Auf dem Phone läuft er als Vollbild-Seite mit sichtbaren Feldzeilen, auf dem Desktop in der rechten Pane.
7. **Dialoge:** Native `<dialog>` als Bottom-Sheet ersetzt SweetAlert2 schrittweise. Sheets haben sichtbares Schließen-Kreuz, Detents, Swipe-Dismiss und werden nie gestapelt.
8. **Übergänge:** Cross-Document View Transitions (Push 300 ms, Tab-Wechsel 150 ms Cross-Fade) als reines Progressive Enhancement, dazu bfcache und Prefetch.
9. **PWA-Shell:** `viewport-fit=cover`, Zoom nicht mehr sperren, kein `black-translucent`, `dvh` statt `vh`, Safe-Area-Insets, `theme-color`-Paare.
10. **Barrierefreiheit** wird Verkaufsargument: Fokusringe zurück, Kontrast ≥ 4,5:1 (heute 3,32:1 für weiß auf #478cf4), Status nie nur über Farbe, 44-px-Ziele.
11. **Gradient-Politik:** Verläufe nur noch für maximal drei Rollen pro Screen (Primär-CTA, aktiver Tab, kleiner Datumschip/Hero). Große Flächen werden solid.
12. **Build-Hygiene:** Tailwind-CDN, Font Awesome und Google-Fonts-Hotlinks weichen einer statisch gebauten CSS-Datei und einem SVG-Sprite.

**Navigation je Rolle (Kurzfassung, Details in Abschnitt 5):**

| Rolle | Tabs (feste Slots) |
|---|---|
| Mitglied | Proben · Plan · Profil |
| Stimmführung | Proben · Plan · Team · Profil |
| Leitung | Proben · Plan · Team · Mehr |

Slot 5 bleibt bewusst frei für ein späteres Feature (Nachrichten oder Noten).

## 2 Was heute „native“ bedeutet (mit Zahlen)

**Navigation.** Apple, Google und die Usability-Forschung sind sich einig: 3–5 beschriftete, dauerhaft sichtbare Ziele unten. Die HIG verlangt Tab-Leisten nur für Navigation, nicht für Aktionen. Sie rät zu Ein-Wort-Labels, dazu, Tabs nie auszublenden oder zu deaktivieren, und warnt, dass ein „More“-Tab Inhalte schwerer erreichbar macht ([HIG Tab Bars](https://developer.apple.com/design/human-interface-guidelines/tab-bars)). Material 3 Expressive hat die Leiste von 80 dp auf 64 dp verkleinert (Indikator 56×32 dp, Icon 24 dp, max. 5 Ziele) und Drawer zugunsten von Rails abgelöst ([Material Components](https://github.com/material-components/material-components-android/blob/master/docs/components/BottomNavigation.md)). Die NN/g-Studie mit 179 Nutzern zeigt, warum der Hamburger stirbt: Auf Mobilgeräten wurde verstecktes Menü in 57 % der Aufgaben genutzt gegenüber 86 % bei kombinierter Navigation. Die Auffindbarkeit sank um mehr als 20 %, Aufgaben dauerten rund 15 % länger und wurden 21 % schwieriger bewertet. Empfehlung dort: höchstens 4 sichtbare Top-Level-Links ([NN/g](https://www.nngroup.com/articles/hamburger-menus/)). Bei breiteren Fenstern übernehmen Rail oder Sidebar ([Android Compose](https://developer.android.com/develop/ui/compose/components/navigation-bar), [Apple Sidebars](https://developer.apple.com/design/human-interface-guidelines/sidebars)).

**Zielgrößen.** Apple 44×44 pt (Minimum 28 pt, ca. 12 pt Abstand um Buttons mit Rahmen), Material 48 dp, WCAG 2.2 AA 24×24 CSS px, NN/g 1 cm ([HIG Accessibility](https://developer.apple.com/design/human-interface-guidelines/accessibility), [W3C 2.5.8](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html), [NN/g](https://www.nngroup.com/articles/touch-target-size/)). Die sichtbare Fläche darf kleiner sein, wenn die Trefferfläche über Padding wächst.

**Text.** iOS-Standard 17 pt, Minimum 11 pt, Large Title 34/41 ([HIG Typography](https://developer.apple.com/design/human-interface-guidelines/typography)). Material liegt einen Schritt darunter (Body 16/24, 14/20, 12/16, Label 11/16) ([Android Developers](https://developer.android.com/develop/ui/compose/designsystems/material3)).

**Bewegung.** Material liefert tokenisierte Dauern (50–1000 ms; 150–300 ms für die meiste UI) und Kurven ([Motion.md](https://raw.githubusercontent.com/material-components/material-components-android/master/docs/theming/Motion.md)). Apple: Feedback kurz halten, auf häufigen Aktionen keine zusätzliche Bewegung, Nutzer nie auf Animationen warten lassen ([HIG Motion](https://developer.apple.com/tutorials/data/design/human-interface-guidelines/motion.json)). Bei „Bewegung reduzieren“ werden x/y/z-Übergänge durch Fades ersetzt, nicht gestrichen ([MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/@media/prefers-reduced-motion)).

**Performance.** INP ≤ 200 ms ([web.dev](https://web.dev/articles/inp)), CLS ≤ 0,1 ([web.dev](https://web.dev/articles/optimize-cls)). Nur `transform` und `opacity` animieren: Im Chrome-Beispiel kosteten `top/left` 37 ms Rendering plus 79 ms Painting bei 50 % verworfenen Frames, die Transform-Variante etwa 0 ms bei 1 % ([web.dev Animations](https://web.dev/articles/animations-guide)).

**Plattformrealität für eine PWA.**
- iOS 26 öffnet jede zum Home-Bildschirm hinzugefügte Seite als Web-App. Es gibt aber weiterhin kein `beforeinstallprompt`, kein Link-Capturing, Web Push nur für installierte Apps (16.4), Declarative Web Push ab 18.4, App Badging ab 16.4 ([firt.dev](https://firt.dev/notes/pwa-ios/)).
- Seit iOS 26.1 zeichnet `black-translucent` einen dunklen Scrim über die Statusleiste. Apples Antwort: Meta-Tag entfernen ([qui #2520](https://github.com/autobrr/qui/issues/2520)). WebKit-Bug 301994 (Safe-Area-Regression) ist laut Recherche mit letztem Kommentar vom 21.10.2026 wieder offen ([WebKit](https://bugs.webkit.org/show_bug.cgi?id=301994)).
- Android Chrome 135+ läuft Edge-to-Edge. `padding-bottom: env(safe-area-inset-bottom)` an der Tab-Leiste schaltet das Wegrutschen der Gesten-Leiste ab ([Chrome](https://developer.chrome.com/docs/css-ui/edge-to-edge)).
- Haptik gibt es nur auf Android (`navigator.vibrate`). Der iOS-Trick mit `<input switch>` wurde laut Drittquellen ab 26.5 auf einen Tick reduziert (Konfidenz niedrig) ([haptics-web](https://haptics-web.vercel.app/)).
- Pull-to-Refresh: Chrome-Android hat ihn in Standalone-PWAs, iOS nicht ([web.dev](https://web.dev/learn/pwa/app-design)).

**Rechtlicher Rahmen.** Das BFSG gilt seit 28.06.2025 und verweist auf WCAG 2.1 AA; genannt werden Bußgelder bis 100.000 EUR. Ob und wie es auf Probenplaner (B2C/B2B, Kleinstunternehmen) anwendbar ist, ist nicht geprüft ([Quelle](https://kopfundstift.de/barrierefreies-webdesign/)). Mit Blick auf den Verkauf ist AA trotzdem das richtige Ziel.

## 3 Typografie & Dichte (konkrete Skala)

**Befund im Ist-Code:** `--font-family-sans` nennt Roboto, geladen wird es nie. Stattdessen werden Aldrich, Goldman, Kantumruy Pro, Poppins (100–900) und Sansation vom Google-Server geholt. Es gibt rund 50 Deklarationen mit 8–11 px (32× 10 px), Eingaben im Inline-Editor mit 13 px (iOS zoomt bei ≤ 15 px beim Fokus, [CSS-Tricks](https://css-tricks.com/16px-or-larger-text-prevents-ios-form-zoom/)) und `user-scalable=no` auf fast allen Seiten. Zeit, Datum und Rest nutzen drei verschiedene Schriften.

**Schrift:** `font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;` Nur Gewichte 400/500/600/700. SF Pro darf nicht selbst gehostet werden, `system-ui` liefert SF mit automatischem Optical Sizing und Tracking ohne Download ([Apple Forums](https://developer.apple.com/forums/thread/127350)). Optional `ui-rounded` nur für die Datumsziffern (Fallback auf System). Wenn Marken-Konsistenz auf Windows-Desktop wichtig wird: genau eine selbst gehostete Variable-Font (Inter Latin 48.256 B, Geist 29.400 B, Plus Jakarta Sans 27.348 B, WOFF2; alle OFL), `font-display: optional` für Body ([Fontsource](https://api.fontsource.org/v1/fonts/inter), [web.dev](https://web.dev/articles/font-best-practices)). Vorher ß, ẞ, ä, ö, ü prüfen.

**Skala (rem, Root 100 %):**

| Token | px / Zeilenhöhe | Gewicht | Einsatz |
|---|---|---|---|
| `large-title` | 34/41 | 700 | Tab-Root, einmal pro Screen; kollabiert beim Scrollen auf 17/22 600 |
| `title-1` | 28/34 | 700 | Leerzustände, Hero-Kennzahl |
| `title-2` | 22/28 | 600 | Datumsziffer im Datumschip, Abschnittstitel in Detailseiten |
| `title-3` | 20/25 | 600 | Hero-Zeit, Sheet-Titel bei großen Sheets |
| `headline` | 17/22 | 600 | Zeilentitel (Uhrzeit), Navigationstitel |
| `body` | 17/22 | 400 | Fließtext, Hinweis-Texte |
| `subhead` | 15/20 | 400 | Meta (Stimmen-Satz, Ort), Buttons |
| `footnote` | 13/18 | 400 | Sekundär-Info, Section-Header |
| `caption` | 12/16 | 500 | Wochentag im Chip, Zähler |
| `micro` | 11/13 | 600 | nur Tab-Labels und Badges |

In dichten Leitungslisten (Tabellen, Mitgliederzeilen) eine Stufe darunter: Zeilentitel 16/21, Meta 14/19. Rehearsal-Meta, die ein Mitglied zum Handeln braucht (Zeit, Ort, Status), nie kleiner als 14 px. Die Skala ist die Quelle der Dichte: Apple holt sie aus enger Zeilenhöhe (ca. 1,29), 44-pt-Zeilen und Haarlinien, nicht aus winzigem Text ([HIG](https://developer.apple.com/design/human-interface-guidelines/typography.md)).

**Regeln:**
- Alle Größen in `rem`, keine festen Zeilenhöhen für Text (mind. `min-height`), 200 % Zoom und WCAG-Textabstände müssen funktionieren ([W3C 1.4.12](https://www.w3.org/WAI/WCAG22/Understanding/text-spacing.html)).
- `font-variant-numeric: tabular-nums` global für Zeiten, Daten, Zähler (heute nur in `attendance/index.php`, [MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/font-variant-numeric)). Zeiten zero-padded (19:30), Daten fester Länge („Di 27.10.“).
- Großschreibung nur für 1–2 Wörter bei 12–13 px mit +0,04 em; Sätze nie in Versalien ([NN/g](https://www.nngroup.com/articles/glanceable-fonts/)).
- Deutsch ist 20–35 % länger als Englisch, kurze Labels bis 2×: `lang="de"`, `hyphens: auto`, `overflow-wrap: anywhere` an Chips und Titeln, `text-wrap: balance` für Überschriften, `pretty` für Hinweistexte ([MDN hyphens](https://developer.mozilla.org/en-US/docs/Web/CSS/hyphens)).
- Tracking nur bei Custom-Fonts nachbauen; auf `system-ui` nie setzen.

**Dichte** entsteht aus drei Hebeln: (1) 4-px-Raster (4/8/12/16/20/24/32/40), Gutter 16 px (20 px ab 430 px Breite), (2) Zeilen statt verschachtelter Karten (Haarlinie 0,5–1 px, eingerückt), (3) Progressive Disclosure. Zeilenhöhen: 44 px einzeilig, 56–60 px zweizeilig, 72–76 px dreizeilig; Section-Header 28–32 px. Die Recherche schätzt den Platzgewinn gegenüber heute auf 25–30 % vertikal. Zusätzlich eine Profil-Einstellung „Dichte: Kompakt / Standard / Groß“ (Root 15/16/17 px, Zeilenpadding −4/0/+4 px): In einer App wählten 17,5 % der Nutzer kleinere und 11,5 % größere Schrift, kleinere Schrift oft nur für mehr Inhalt ([David Smith](https://www.david-smith.org/blog/2023/01/24/design-notes-19/)).

**Kontrast (Pflicht vor jedem Politur-Schritt).** Mit der WCAG-Formel gerechnet gegen Weiß: #478cf4 = 3,32:1, #10b981 = 2,54:1, #ef4444 = 3,76:1, #f59e0b = 2,15:1, #9ca3af (`--color-text-muted`) = 2,54:1, #d1d5db = 1,47:1 ([W3C 1.4.3](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html)). Neu:
- Gefüllte Buttons/Gradient-Basis: #2f6fe0 (4,70:1) bis #2563eb (5,17:1). #478cf4 nur für Icons, Rahmen, Tints und Ringe (3:1 reicht dort).
- Textfarben für Status: #047857 (grün), #dc2626 (rot), #b45309 (amber). Die hellen Töne bleiben Füllungen und Balken.
- Muted-Text mindestens #6b7280 (4,83:1); #d1d5db nie für Text.
- Gradient-Text unter 20 px entfällt. Weiße Schrift auf #92c02b (Jeunesse-Success) und ähnlichen Hellfarben fällt ebenfalls durch, deshalb braucht jedes Theme einen automatischen 4,5:1-Check für `--on-accent`.

## 4 Visuelle Sprache 2026 (was bleibt, was weg muss)

**Leitbild:** Apples Liquid Glass gilt ausdrücklich nur für die Navigations-/Kontrollschicht, nie für Inhalte, und „glass on glass“ ist verboten ([WWDC25 Liquid Glass](https://developer.apple.com/videos/play/wwdc2025/219/), [HIG Materials](https://developer.apple.com/design/human-interface-guidelines/materials)). Apple hat sich dreimal Richtung Opazität zurückbewegt: Beta-Anhebung, Clear/Tinted in 26.1, weniger transparenter Standard plus Slider in iOS 27 ([MacRumors](https://www.macrumors.com/2026/06/10/how-liquid-glass-is-changing-in-ios-27/)). NN/g kritisiert Lesbarkeit und Bewegung um der Bewegung willen ([NN/g](https://www.nngroup.com/articles/liquid-glass/)). Linear zeigt die SaaS-Linie: Struktur „gefühlt, nicht gesehen“, weniger Icons und Trenner ([Linear](https://linear.app/now/behind-the-latest-design-refresh)). Material 3 Expressive geht in Richtung mehr Ausdruck, warnt aber selbst, bekannte Muster nicht zu brechen, und eine starke Minderheit bevorzugt ruhigere UIs ([Google Research](https://design.google/library/expressive-material-design-google-research)). Für Probenplaner (Orchester, Kirchen, Schulen) passt: ruhig, weich, mit genau einem ausdrucksstarken Element (RSVP).

**Bleibt:**
- Primärblau als Markenanker, 135°-Verläufe für Primär-Button, aktiven Tab und Datumschip; farbige Glow-Schatten nur auf Primär-Aktionen.
- Status-Triade Grün/Rot/Grau, jetzt immer mit Icon und Text.
- Probenkarten-Anatomie: Datum/Zeit oben, Badge-Zeile (Typ nur wenn ≠ „Probe“, Stimmen-Satz, Rollenpillen, Info-Emojis, Ort nur wenn ≠ „Probenraum“), farbiger Kategoriestreifen links.
- Rollenpillen (Farbe, +N-Überlauf), Emoji-Info-Zeilen, Zeitleiste mit Punkten und „aktiv“-Puls, Skeleton-Shimmer, Auto-Save-Muster.
- Das Prinzip der Standardwerte-Unterdrückung und die SmartGroup-Sätze („Bläser ohne Flöte und Oboe“).

**Wird umgebaut:**
- **Glas nur auf Chrome:** Tab-Leiste, Top-Bar ab dem ersten Scroll, Sheets. Light `rgba(255,255,255,.78)` / Dark `rgba(24,27,36,.72)`, `backdrop-filter: blur(20px) saturate(160%)` (Deckel 24 px), 1-px-Innenrand, opake Fallback-Fläche, in-App-Schalter „Transparenz reduzieren“, kein Animieren von Blur, kein SVG-Refraction (nur Chromium) ([MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/@media/prefers-reduced-transparency)).
- **Schatten:** Drei Stufen, mehrlagig und blau getönt mit einer Lichtquelle (y = 2× x), z. B. ruhend `0 1px 2px hsl(220 40% 30% / .06)` plus Haarlinie ([Josh Comeau](https://www.joshwcomeau.com/css/designing-shadows/)). Weg: `--shadow-2xl` und die weißen Glow-Schatten im Dark Mode. Im Dark Mode Elevation über hellere Flächen (+4–6 %) statt Schatten; Schatten nie animieren.
- **Radien konzentrisch:** Innenradius = Elternradius − Padding ([WWDC25 356](https://developer.apple.com/videos/play/wwdc2025/356/)). Karten/gruppierte Listen 16, Hero 20, Sheets oben 24, Eingaben und Segmente 12, Chips/Tab-Indikator/Segment außen als Kapsel. `corner-shape: squircle` nur als Chromium-Enhancement.
- **Gradienten:** Maximal drei Rollen pro Screen; große Flächen solid oder faint tonal. In OKLCH interpolieren (`in oklch`), sRGB-Fallback zuerst ([MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/color_value/light-dark)).
- **Icons:** Font Awesome weg, stattdessen ein Inline-SVG-Sprite mit 40–60 Symbolen. Phosphor (Regular inaktiv, Fill aktiv) oder Lucide (1.903 Icons, ISC); die Anzahlen bei Phosphor widersprechen sich in den Quellen, vor der Entscheidung prüfen ([Material Symbols](https://developers.google.com/fonts/docs/material_symbols)). Icons: 24 px Tab, 20 px Zeile, 16 px inline. Farbige Icon-Kacheln hinter Alltagszeilen entfallen.
- **Dark Mode** aktivieren (Tokens existieren, werden nie geschaltet): semantische Tokens mit `color-scheme: light dark` und `light-dark()`, OKLCH-Neutrals mit Markenton (Chroma 0,005–0,02), Dunkelgrund ca. `oklch(0.18 0.01 260)` statt #1a1a1a/#2d2d2d. Jedes Ensemble-Theme (Standard, Jeunesse) aus einem Farbton per `color-mix()` ableiten.

**Muss weg:** Die 24-px/900-Wochentagsbuchstaben mit Gradient-Text, 52-px-Icon-Kreise, Hover-Hüpfen (`translateY/X`, Shimmer auf Buttons), 144-px-Logo-Karten, Doppelschatten auf Settings-Karten, Hell-Dunkel-Umkehr über `--color-white/--color-black`, nackte blaue Empty-State-Kästen.

## 5 Navigation & Informationsarchitektur (Empfehlung mit Begründung)

**Grundsatz:** Ein Shell, Tabs nach Berechtigung. Die Rechte werden wie heute in `sidebar-content.php` vereinigt, nichts wird ausgegraut. Berechtigungsabhängiges Weglassen ist etwas anderes als das von Apple verbotene Deaktivieren sichtbarer Tabs.

| Slot | Label | Wer | Inhalt |
|---|---|---|---|
| 1 | **Proben** | alle | Listen mit Segment „Anstehend \| Vergangen“; Leitung: Proben mit Antwortbalken, Erstellen |
| 2 | **Plan** | `can_view_schedule` | Probenplan als Agenda, Segment „Alle \| Meine Stimme“, Drucken/PDF, iCal |
| 3 | **Team** | Stimmführung, Leitung | Segmente je nach Recht: Übersicht (nur Leitung) \| Anwesenheit \| Mitglieder |
| 4 | **Profil** oder **Mehr** | alle | „Mehr“, wenn die Rolle Ensemble-Verwaltung hat, sonst „Profil“ |
| 5 | frei | | Reserve (Nachrichten/Noten) |

- Ein-Segment-Fall: Hat jemand nur ein Segment, wird der Segmentschalter ausgeblendet.
- **Warum nicht „Rückmeldungen“ als eigener Tab?** Die Antworten sind pro Probe das Entscheidende. Sie liegen deshalb als „Wer kommt?“ in der Proben-Detailseite (Stimmen-Balken, nur Offene, „Erinnern“). Das spart den heutigen Namenswirrwarr („Meine Meldungen“ vs. „Rückmeldungen“ vs. „Termine“ vs. „Probenplan“) und folgt Konzertmeister, dessen Listenzeile die Antworten für Leiter direkt zeigt ([Konzertmeister](https://konzertmeister.app/en/help/view-my-appointments)).
- **Probenplan als eigener Tab oder Segment?** Offen: Das hängt an Nutzungsdaten (wie oft wird der Plan geöffnet). Startempfehlung: eigener Tab, weil Kalender-Sicht bei Konzertmeister erst im September 2026 kam und Probenplaner mit Druck-Ausgabe punkten kann.
- **Badge** auf „Proben“ nur für Mitglieder: Anzahl offener Antworten (Apple: roter Punkt/Oval nur für Kritisches, Chorilo macht dasselbe auf dem Events-Icon). Zusätzlich `navigator.setAppBadge()`.
- **Tab-Leiste:** 56 px Inhaltshöhe + Safe-Area-Inset (Judgement zwischen 49 pt iOS und 64 dp Material), Icons 24 px, Label 11/13 px immer sichtbar, aktiv = gefülltes Icon + Akzentfarbe + 56×32-px-Pille (nicht nur Farbe). Items mind. 48 px hoch, Trefferfläche ≥ 44 px. Ausblenden nur unter Sheets und bei fokussiertem Textfeld (iOS-Tastatur überdeckt fixe Leisten, [Chrome](https://developer.chrome.com/blog/viewport-resize-behavior)).
- **Header:** 44 px + Inset, Large Title (34/41) kollabiert auf 17/22 per Scroll-Animation. Detailseiten: Chevron-Zurück (44×44), Titel < 15 Zeichen. Ensemble-Name als kleiner Chip unter dem Titel; Tippen öffnet den Ensemble-Wechsler (Sheet, mit Suche ab > 5 Ensembles). Im Standalone-Modus immer ein In-App-Zurück, denn iOS hat keinen Browser-Zurück ([web.dev](https://web.dev/learn/pwa/app-design)).
- **Erstellen (Leitung):** Genau ein Einstieg: erweiterter FAB „Probe“ (56 px hoch, 16 px über der Tab-Leiste, kollabiert beim Scrollen), Tap öffnet ein kleines Aktions-Sheet „Neue Probe · Serie · Aus Plan importieren (KI)“. Google fand, dass hervorgehobene Hauptaktionen bis zu 4× schneller gefunden werden ([Google Research](https://design.google/library/expressive-material-design-google-research)). Ab 840 px wird daraus ein gefüllter Button im Header. Das ersetzt die drei konkurrierenden Wege (Toolbar „+“, Add-Box, „Serie erstellen“).
- **Mehr/Profil** als Inset-Grouped-Liste (16-px-Gutter, 12-px-Radius, 44–52-px-Zeilen):
  1. Kopfzeile: Avatar, Name, Ensemble (Wechsler).
  2. *Ensemble* (nur mit Recht): Einstellungen, Rollen & Rechte, Registerstruktur, Einladungslink.
  3. Benachrichtigungen (Push, Vorlaufzeit als Presets 30 Min · 1 Std · 2 Std · 1 Tag + Eigene), Kalender-Sync, App installieren.
  4. Darstellung: Erscheinungsbild (System/Hell/Dunkel), Theme, Dichte, Animationen reduzieren, Haptik, Transparenz reduzieren.
  5. Konto: Passwort, Ensemble verlassen; „Account löschen“ ganz unten in Rot.
  6. Hilfe, Datenschutz, Impressum, Version; Abmelden.

  Aufgabenspezifische Optionen (Filter, „nur meine Stimme“) bleiben auf dem Screen, den sie betreffen ([Android Settings](https://developer.android.com/design/ui/mobile/guides/patterns/settings)).
- **Segmentierte Controls** (max. 3–4 Segmente, 32–36 px, Text, gleiche Breite) für Unteransichten. Letzte Wahl pro Tab merken (`localStorage` mit `try/catch`) und als `?view=` in der URL halten, damit PHP-Seiten und Zurück funktionieren ([HIG Segmented Controls](https://developer.apple.com/design/human-interface-guidelines/segmented-controls)).
- **Sheets:** Verwenden für kurze Aufgaben (Absage-Grund, Wechsler, Gruppen-Auswahl, Pickers, Filter). Größe: ca. 50 % und ca. 92 % `dvh`, Griff 36×5 mit 48-px-Fläche, sichtbares Schließen-Kreuz, Abbrechen links/Speichern rechts, Zurück-Geste schließt (History-Eintrag), Verwerfen-Rückfrage nur bei geänderten Daten, nie stapeln ([NN/g Bottom Sheets](https://www.nngroup.com/articles/bottom-sheet/), [HIG Sheets](https://developer.apple.com/design/human-interface-guidelines/sheets)). Lange Flows (Serie, Editor) sind Vollbild-Seiten, keine Sheets.
- **Mitglied bearbeiten** wird eine Detailseite/Sheet mit Schnellaktionen; „Entfernen“ steht abgesetzt am Ende. Heute landet ein Tap auf eine Mitgliedszeile bei Stimmführern im Fehlerdialog „Keine Berechtigung“. Zeilen ohne Recht sind nicht tappbar.
- **Gesten** sind Abkürzungen, nie der einzige Weg. Swipe nur in Verwaltungslisten (max. 1–2 Aktionen, trailing = destruktiv), die linke Kante bleibt frei fürs System-Zurück ([NN/g Swipe](https://www.nngroup.com/articles/contextual-swipe/), [HIG Gestures](https://developer.apple.com/design/human-interface-guidelines/accessibility)). Das gilt auch für das versteckte Long-Press-Zurücksetzen der Antwort: Es bekommt einen sichtbaren Weg im Status-Sheet („Antwort zurücksetzen“, wenn `allow_attendance_reset` an ist).
- **Suche:** Lupe im Header (v1). Ab ca. 30 Mitgliedern Suchfeld oben in der Mitgliederliste mit Bereichs-Placeholder („Mitglieder suchen“).
- **Onboarding:** Keine Slides. Einladungslink → Name → Stimme → Proben. Push-Berechtigung erst nach der ersten Antwort, mit Begründung; PWA-Installation als Karte unter Mehr nach dem zweiten Besuch ([NN/g](https://www.nngroup.com/articles/empty-state-interface-design/)).

## 6 RSVP-/Proben-UI-Patterns

**Gemeinsamer Nenner der Wettbewerber:** Ein-Tap-Antwort, expliziter Offen-Zustand, optionale kurze Begründung, Frist, aggregierte Zahlen je Stimme für Leiter ([Konzertmeister](https://konzertmeister.app/en/help/reply-to-appointment), [Chorilo](https://custom.chrome.com/en/help/events/rsvp), [Spond](https://www.spond.com/news-and-blog/invites-and-reminders-spond-app/)). Richtig ist hier die Chorilo-URL `https://custom.chorilo.com/en/help/events/rsvp`.

**Zeilen- und Kartenaufbau (Proben-Liste):**
- **Datumschip** 44×48 px: Wochentag 12/16 (500), Tagesziffer 22/28 (600), Hintergrund ein schwacher Primärverlauf mit Haarlinie. Der Verlauf-Text entfällt. Relative Hinweise („Heute“, „Morgen“) kommen als Caption.
- **Titelzeile:** Zeit „19:30 – 21:45“ 17/22 600 (tabular), Typ-Badge nur wenn ≠ „Probe“.
- **Metazeile:** Stimmen-Satz · Ort (nur wenn ≠ „Probenraum“) 15/20.
- **Dritte Zeile (optional):** Rollenpillen und Info-Emojis (Teaser). Max. 2 Badges sichtbar, Rest „+N“.
- **Kategoriefarbe** als 3-px-Streifen am Zeilenrand, entkoppelt vom Status (heute kollidiert sie mit der Status-Logik, außerdem greift die CSS-Klasse `status-not-attending` nie, weil das Markup `status-not_attending` schreibt).
- **Nächste Probe** bekommt eine Hero-Karte: Zeit 34/41 600, Datum 15/20, Kontrolle in 48 px.
- Liste in Wochen-/Monats-Headern (28 px sticky), „Heute“-Marker (2-px-Linie) und schwebender „Heute“-Pille nach dem Hochscrollen. Segment „Anstehend | Vergangen“ ersetzt den HEUTE-Scrolltrick und den Nachlade-Button. Agenda-/Listenansicht ist auf Phones der Standard, Monatsraster nur als optionale Navigation ([NN/g Cards vs. Lists](https://www.nngroup.com/articles/cards-component/), Konfidenz für Agenda-Evidenz gering).
- Filter-Chips (32 px sichtbar, 44 px Trefferfläche): Leitung/Stimmführung „Alle · Meine Stimme · Offen (n)“.

**Zustandsdesign: offen groß, beantwortet klein.**
- **Offen:** Zeile mit vollbreitem Zwei-Segment-Control, 44 px (48 px im Hero), 2 px Lücke, 8-px-Innenradius, außen Kapsel, Text 15/20 600 „Zusagen“ / „Absagen“ plus Icon 18–20 px. Beide Segmente neutral umrandet, nicht ausgegraut. Das behebt, dass heute der handlungsrelevante Zustand am schwächsten aussieht (beide Buttons bei 40 % Deckkraft).
- **Beantwortet:** Zeile kompakt (72–84 px) mit Statuspille rechts („Zugesagt“ grün getönt, „Abgesagt“ rot getönt, „Offen“ grau; Icon + Text, Text in den dunklen Statusfarben). Die Pille ist ein eigenes 44-px-Ziel und öffnet das Status-Sheet.
- Auswahl = getönte Füllung + 2-px-Rand + Icon, damit es in Graustufen und im Druck funktioniert ([W3C 1.4.1](https://www.w3.org/WAI/WCAG22/Understanding/use-of-color.html)). Rund 8 % der Männer haben eine Farbsehschwäche ([W3C](https://www.w3.org/WAI/WCAG22/Understanding/use-of-color.html)).
- Update **optimistisch** unter 100 ms; bei Fehler Rollback plus Fehlertoast ([React-Doku](https://react.dev/reference/react/useOptimistic)).
- **Absage:** Speichert sofort, öffnet danach das Sheet (ca. 50 %) mit Chips „Krank · Arbeit · Urlaub · Familie · Anderes“, einzeiliges wachsendes Textfeld (16 px, max. 500 Zeichen) und „Überspringen“ als Standard. Ist `force_decline_reason` an, öffnet sich das Sheet vor dem Speichern, „Abbrechen“ bricht die Absage ab (Chorilo erzwingt mind. 5 Zeichen, optional übernehmbar). Danach 5-s-Snackbar „Rückgängig“ in einer `role=status`-Live-Region ([W3C 2.2.1](https://www.w3.org/WAI/WCAG22/Understanding/timing-adjustable.html)). Keine Bestätigungsdialoge für Reversibles ([NN/g](https://www.nngroup.com/articles/confirmation-dialog/)).
- **Swipe-links** auf der Zeile als Abkürzung („Zusagen/Absagen“), mit einmaligem Peek-Hinweis, nie als einziger Weg.

**Detailseite einer Probe (alle Rollen):** Datum/Zeit, Ort, Stimmen-Satz, Rollen, Hinweise als **Text** (heute nur Emojis sichtbar, 14-px-Grauschrift im Kasten), Ablauf als vertikale Zeitleiste (48-px-Zeitspalte tabular, 2-px-Linie, 8-px-Punkte; „Jetzt“-Marker, vergangene Schritte eingeklappt) und das RSVP-Control. Der Ablauf kennt heute keine Stimmen-Zuordnung: „Dein Einsatz: 19:45“ als Zeile ganz oben setzt voraus, dass Ablaufpunkte Gruppen bekommen. Das ist eine Datenmodell-Erweiterung und eine Produktentscheidung.

**Detailseite für Leitung/Stimmführung („Wer kommt?“):**
- Ein sortierter 100-%-gestapelter Balken je Probe (zugesagt/abgesagt/offen, 2-px-Trenner, Zahlen als Label; 6–8 px in der Liste, 12 px im Detail) ([UNHCR Dataviz](https://dataviz.unhcr.org/chart-types/part-to-a-whole/)).
- Darunter die Stimmen in Partiturreihenfolge mit Mini-Balken und „n/Soll“. Das optionale Soll je Stimme fehlt heute im Datenmodell; Chorilos „Unterbesetzung erkennen“ legt es nahe ([Chorilo](https://www.chorilo.com/en/blog/anwesenheitsverwaltung-einfach-und-transparent)).
- Der Mehrebenen-Baum (Register > Gruppe > Instrument > Mitglied, alles zu, 3–4 Taps) wird auf zwei Ebenen reduziert: Stimme aufklappen zeigt Namen, sortiert Absagen zuerst, dann Offen, mit Grund inline (wie heute).
- **„n offen“ ist tappbar** und öffnet ein Sheet mit den Offenen nach Stimme und einer Schaltfläche „Erinnern“ (rate-limitiert, eine manuelle Erinnerung pro Person und 24 h). Das bestehende Push-Erinnern bleibt, mit Bestätigung und „X von N gesendet“. Zusätzlich eine automatische Erinnerung für Offene 48 h vor Probe (Spond-Muster) neben der bestehenden Vorlaufzeit pro Nutzer. Push-Text nennt die Probe („Probe Do 19:30 – Zusage?“). Aktionsbuttons im Push (Zusage/Absage) sind nicht verifiziert und müssen auf iOS-Web-Push getestet werden.
- Kritische Register und Auffälligkeiten (Schwelle 65 %) bleiben als optionale Insights-Schicht; die Analytics-Kacheln werden in „Team › Übersicht“ verschoben und fachlich korrigiert (heute: nächste 10 Proben, Trend vergleicht dieselben 10 Proben mit sich selbst und zeigt fast immer „Änderung <0,5 %“).

**Anwesenheit (Team › Anwesenheit):**
- Vorbefüllte Liste nach Stimmen: Zusage = anwesend als **vorgeschlagener** Zustand (gestrichelt), erst mit Tap oder „Alle Zugesagten übernehmen“ gespeichert (Chorilo: nur Ausnahmen korrigieren, sofort speichern, an Notenwart delegierbar) ([Chorilo](https://www.chorilo.com/en/blog/anwesenheitsliste-probe-mobile-app)).
- 48-px-Zeile, ein Umschalt-Control „Anwesend | Abwesend“ (+ Notiz-Icon). Das heutige Doppelring-Indikator-Rätsel (Icon = Zusage, Ring = Realität, dreistufiger Tap-Zyklus) entfällt.
- Kopf: Probenwähler als Chip „Sa 17.10. · Probe ▾“ mit Sheet der letzten Proben statt endloser Pillenleiste; sticky Fortschritt „37/42 erfasst“; Abweichung (Zusage ≠ real) als Amber-Zeile mit Icon.
- Notiz als Sheet mit Chips „verspätet · früher gegangen · entschuldigt“. Bulk-Aktion mit Toast „n bestätigt“ und Rückgängig.
- Tabelle (Mitglieder × Proben, Fehlzeiten, Druck A4 quer) nur ab 840 px; auf dem Phone eine Mitgliederliste mit Quote und Zeitraum-Chip („Letzte 10 Proben“ / „Saison“). Chorus Connection beschränkt die Spreadsheet-Ansicht auf Desktop/Tablet ([Chorus Connection](https://help.chorusconnection.com/overview-of-attendance?hsLang=en)).

**Produktoptionen im Basistarif** (Konzertmeister sperrt Maybe, „alle zugesagt“, Pflichtgrund und Reply-Benachrichtigungen hinter Pro, [Konzertmeister Pricing](https://konzertmeister.app/en/pricing)): Antwortfrist mit sichtbarer Restzeit, optionales „Vielleicht“ (Standard aus), Pflichtgrund, Teilnehmerlimit je Stimme, Opt-out-Modus. Sichtbarkeit zweigeteilt für „Antworten sichtbar für“ und „Gründe sichtbar für“ (Leitung | Leitung + eigene Stimme | Alle; Standard Leitung) ([Konzertmeister](https://konzertmeister.app/en/help/why-can-only-leaders-see-replies-from-all-members-and-how-can-i-change-this)). „Abwesenheit eintragen“ (von–bis, Einladen / Einladen & automatisch absagen / Nicht einladen) ([Konzertmeister](https://konzertmeister.app/en/help/manage-my-absences)). Probendetails (Ort, Ablauf, „Wer kommt?“-Sichtbarkeit) nie hinter der Antwort verstecken (Partiful-Beschwerde).

**Druck (Probenplan/Anwesenheit):** `@page { size: A4 portrait; margin: 14mm }`, App-Hülle ausblenden, `break-inside: avoid` je Probenblock, Statusangaben als Text/Icon, `print-color-adjust: exact` nur für Statussymbole ([MDN @page](https://developer.mozilla.org/en-US/docs/Web/CSS/@page)). Seitenzähler per Margin Box nur als Zusatz (unvollständig unterstützt). CSV für Excel mit Semikolon und UTF-8-BOM ist eine Ableitung, keine belegte Quelle.

## 7 Desktop & Tablet

- **Breitenstufen** (Material-Klassen, breiten- statt gerätebasiert, [Android](https://developer.android.com/develop/ui/compose/layouts/adaptive/use-window-size-classes)): < 600 Bottom-Bar; 600–839 Rail (80–88 px, Icon über Label); 840–1199 Rail + Zwei-Pane; ≥ 1200 expandierte Sidebar 240 px. Als Build-Konstanten in Tailwind (`--breakpoint-md: 37.5rem`, `--breakpoint-lg: 52.5rem`, `--breakpoint-xl: 75rem`), weil Tailwind-Standard (768/1024) Tablets in andere Stufen als Material einsortiert. Nie nach User-Agent verzweigen (iPads melden sich als Mac) ([WWDC25 208](https://developer.apple.com/videos/play/wwdc2025/208/)).
- **Sidebar** max. zwei Ebenen, dieselben Ziele wie die Tabs, Ensemble-Gruppe einklappbar. Zustand pro Nutzer speichern (`[` als Shortcut), unter 1200 px automatisch auf Rail, bei Verbreiterung Nutzerwahl wiederherstellen. Sidebar flach-neutral, leicht abgedunkelt wie bei Linear ([Linear](https://linear.app/changelog/2026-03-12-ui-refresh)).
- **Zwei-Pane ab 840 px** (Material List-Detail, [Canonical Layouts](https://developer.android.com/develop/ui/compose/layouts/adaptive/canonical-layouts)): Proben-Liste 340–380 px links, Detail/Editor rechts (Editor max. ca. 760 px); Auswahl in der Liste bleibt hervorgehoben, Platzhalter „Wähle eine Probe“, wenn nichts selektiert ist. Zusammenfassungs-Pane bei 840+ als 70/30. Darunter eine Pane nach der anderen mit Zurück. Jede Probe/jedes Mitglied hat eine deep-linkbare URL.
- **Inhaltsbreite:** Mitgliederansichten und Formulare zentriert max. 640–720 px (≈ 66ch, [UXPin/Baymard-Zitat in der Recherche](https://useyourloaf.com/blog/readable-content-guides/)), Verwaltungsseiten bis 1280 px mit 16 bzw. ab 1280 px 24 px Seitenrand ([Primer](https://primer.style/foundations/layout)). Die 640–720 px sind abgeleitet, nicht als Pixelregel belegt.
- **Zeigergesteuerte Dichte:** Maßgeblich ist `pointer`, nicht die Breite. `pointer: coarse` = 44–48-px-Steuerelemente, `pointer: fine` = 32–36 px Controls und 40-px-Tabellenzeilen (32 px kompakt) ([Carbon](https://carbondesignsystem.com/components/data-table/usage/)); WCAG 24-px-Boden gilt immer. `:hover`-Regeln (ca. 34) in `@media (hover: hover) and (pointer: fine)` hüllen; nichts darf nur per Hover erreichbar sein ([MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/At-rules/@media/hover)).
- **Tabellen (Anwesenheit, Mitglieder, Rollen):** Sticky Header und erste Spalte, Auswahl mit Batch-Aktionsleiste (max. 5 Aktionen), Spaltenwähler, Seiten-Detailpanel bei ≤ 5 Kernspalten ([NN/g Mobile Tables](https://www.nngroup.com/articles/mobile-tables/)).
- **Tastatur:** Cmd/Ctrl+K Befehlspalette (Probe, Mitglied, Aktion), `?` Shortcut-Übersicht, `[` Sidebar. Ein-Tasten-Kürzel brauchen Aus-Schalter oder Modifier (WCAG 2.1.4, Level A) und dürfen nie in Eingaben/Inline-Editoren feuern ([W3C](https://www.w3.org/WAI/WCAG21/Understanding/character-key-shortcuts.html)).
- **Zustand erhalten:** Auswahl, Scrollposition, offene Editoren und Entwürfe überleben Resize/Rotation; ein DOM, per CSS umgestellt, keine doppelten Mobil-/Desktop-Bäume.
- **Container Queries** für Karten (Slot statt Viewport): z. B. Karte stapelt bei < 448 px Container, zeigt Zeitleiste neben Info ab 672 px. Baseline Widely Available seit August 2025 ([Tailwind](https://tailwindcss.com/docs/responsive-design)).
- **Registerstruktur-Editor:** Drag&Drop ist heute nur Maus (HTML5), auf Touch lässt sich nichts verschieben. Für Phone Auf/Ab-Buttons und Einrücken ergänzen.
- **Orga-/Admin-Panel:** Bleiben Desktop-first, übernehmen aber Tokens und ersetzen native `confirm()` durch Dialoge; Ensemble-Löschen auf zwei Schritte reduzieren (Folgenliste, Namenseingabe) statt der heutigen vier Stufen.
- **Abnahmematrix:** 360×800, 390×844, 600, 768, 840, 1024×640, 1280×800, 1440×900, 1600×900, jeweils mit Touch/Maus/Trackpad, Hoch/Quer, freies Fenster, 200 % Zoom. Ziel „Tier 2“ des Android-Großbildschirm-Modells ([Android Quality](https://developer.android.com/docs/quality-guidelines/large-screen-app-quality)). Window Controls Overlay erst später (Chromium-only).

## 8 Wettbewerb & Positionierung

| Produkt | Plattform / Preis | Relevante Stärken | Schwächen laut Quellen |
|---|---|---|---|
| **Konzertmeister** (AT/DE) | Web, iOS, Android. Basic gratis bis 30 Mitglieder; Pro EUR 33/66/99 pro Jahr | Ein-Tap-Antwort, Rollenmatrix (Leiter/Co-Leiter/Sekretär), Register, Chat (März 2026), Hub für Aushilfen (18.06.2026), 5.4.0 (27.09.2026) mit Kalenderansicht und Besetzungen; iOS-26-Layout seit 5.1.0 | Anwesenheit person-für-person abhaken, unklare Push-Texte, Funktionen wie Maybe/Pflichtgrund nur Pro ([Preise](https://konzertmeister.app/en/pricing), [iOS-Historie](https://apps.apple.com/de/app/konzertmeister/id1114620982), [Blasmusikblog](https://blasmusikblog.com/wie-der-konzertmeister-die-terminkoordination-im-musikverein-meistert/)) |
| **Chorilo** (DE-Hosting) | EUR 100/Jahr pro Ensemble flach, 60 Tage Test | Vollständiges RSVP-Modell, Anwesenheitsliste aus RSVP vorbefüllt, Stimmen-Zähler | Weniger Marktpräsenz ([RSVP](https://custom.chorilo.com/en/help/events/rsvp)) |
| **cori** | EUR 1/Mitglied/Monat | Übe-/Praxisfunktionen | Breite „kann zu viel sein“, laut eigenem Vergleich ([cori](https://cori.music/en)) |
| **Spond / SpielerPlus / TeamSnap / Heja** | Spond gratis; SpielerPlus Premium; TeamSnap Premium | Tab-Leisten (SpielerPlus seit März 2024, 4,8 bei 12.133 Bewertungen), Liquid-Glass-Update (TeamSnap 7.72/7.73, September 2026), Excel-Export (Spond) | TeamSnap: tiefe Navigation, RSVP-Aktualisierung, Verfügbarkeit hinter Paywall ([Spond](https://apps.apple.com/us/app/spond/id755596884), [SpielerPlus](https://apps.apple.com/de/app/spielerplus-teamorganisation/id914253760), [TeamSnap](https://apps.apple.com/us/app/teamsnap-sports-team-manager/id393048976)) |
| **442Hz, OrchesterDienst, #DIESE** | Preise nicht öffentlich | Dienstpläne für Profi-Orchester | Vertriebsgesteuert, nicht für Laien-/Semiprofi-Ensembles ([442Hz](https://442hz.com/en/customers)) |

**Positionierung:** Probenplaner kann sich nicht über Funktionsbreite (Chat, Tasks, Markt) mit Konzertmeister messen, sondern über **Tiefe in Proben und Anwesenheit**:
1. Stimmen-erste Leiteransicht (Besetzung je Register, „Offene erinnern“ in einem Tap).
2. Inline-Editing mit Auto-Save und Smart-Defaults, zeitgeplanter Probenablauf, Push-Vorlaufzeit pro Nutzer, druckfertiger Probenplan.
3. RSVP-Optionen im Basistarif (Frist, Vielleicht, Pflichtgrund, Sichtbarkeit).
4. Barrierefreiheit und Deutsch-Hosting als Vertrauenssignal (Chorilo wirbt mit Servern in Nürnberg/Falkenstein/Frankfurt und 30 Tagen Datenhaltung nach Kündigung).

Ob kein Wettbewerber zeitgeplante Register-Ablaufpläne oder persönliche Anrufzeiten hat, ist nur ein Dokumentationsbefund und nicht bewiesen; vor Marketing verifizieren. Die Recherche konnte Konzertmeisters echte Tab-Struktur nicht bestätigen: App installieren und per Screenshot vergleichen.

**Preis-Anker (Geschäftsentscheidung, nicht UX):** Flach pro Ensemble, nicht pro Mitglied. Gratis bis ca. 30 Mitglieder (Konzertmeister-Anker), bezahlt ca. EUR 99–149 pro Jahr unbegrenzt (Konzertmeister Pro unbegrenzt EUR 99, Chorilo EUR 100). Per-Mitglied-Preise (cori) kämen bei 60–100 Mitgliedern auf EUR 720–1.200 pro Jahr ([Preisübersicht](https://joinit.com/blog/choir-management-software)).

**Mobile Messlatte:** Native Konkurrenten wiegen 11,3 MB (SpielerPlus) bis 241 MB (TeamSnap). Eine PWA mit < 300 KB gzipped Start-Payload, Offline-Lesen der nächsten fünf Proben und Link-Einladung sticht dort.

## 9 Web-Plattform-Features, die wir nutzen sollten

Support-Policy: **Volle Stufe** = Safari/iOS ≥ 17.5, Chrome ≥ 120, Firefox ≥ 129 (Nesting, `@starting-style`, `light-dark`, `text-wrap: balance`, Container Queries, `:has()`). **Enhanced** = Features mit funktionierendem Fallback.

| Feature | Stand (Okt. 2026) | Einsatz in Probenplaner |
|---|---|---|
| Cross-Document View Transitions (`@view-transition { navigation: auto }`) | Chrome/Edge 126+, Safari/iOS 18.2+, Firefox nein; 4-s-Timeout; ca. 89 % global ([Chrome](https://developer.chrome.com/docs/web-platform/view-transitions/cross-document), [Caniuse](https://caniuse.com/wf-view-transitions)) | Push/Pop-Slide und Tab-Cross-Fade ohne SPA; Tab-Leiste und Header per `view-transition-name` stabil; hinter `prefers-reduced-motion: no-preference` |
| Same-Document View Transitions | Baseline (Firefox 144) | Sheet-/Karten-Morphs in einer Seite |
| `<dialog>` + Invoker Commands (`command`/`commandfor`) | Safari 26.2, Chrome 135, Firefox 144; `closedby` ohne Safari ([WebKit 26.2](https://webkit.org/blog/17640/webkit-features-for-safari-26-2/)) | Bottom-Sheets statt SweetAlert2 (21 Dateien); Backdrop-Klick-Fallback für Safari |
| Popover + Anchor Positioning | Popover Chrome 116/Safari 17/Firefox 125; Anchor Baseline seit Firefox 147 (Jan. 2026) ([Chrome](https://developer.chrome.com/docs/css-ui/anchor-positioning-api)) | Menüs/Farbwahl/Typ-Autocomplete statt selbstgebauter JS-Popover |
| `@starting-style` + `allow-discrete` | Baseline 2024 ([MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/@starting-style)) | Ein-/Ausblenden von Sheets und Popovers in reinem CSS |
| Scroll-driven Animations | Chrome 115, Safari 26 (Compositor ab 26.4), Firefox 159 ab 27.10.2026 ([WebKit](https://webkit.org/blog/17101/a-guide-to-scroll-driven-animations-with-just-css/)) | Large-Title-Kollaps (`animation-range: 0 48px`), nur in `@supports` und `no-preference` |
| `scroll-state()` Queries | nur Chromium ([Chrome](https://developer.chrome.com/blog/css-scroll-state-queries)) | Header-Schatten, rein optional |
| Container Queries, `:has()` | ca. 96 % | Karten reagieren auf Slot; Zustandsstyling ohne JS |
| `light-dark()`, `color-mix()`, `@layer` | Baseline | Semantische Tokens, Dark Mode, Ensemble-Themes ([MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/color_value/color-mix)) |
| `field-sizing: content` | Baseline seit Juni 2026 | Notizfelder ohne Autosize-JS ([MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Properties/field-sizing)) |
| `text-wrap: balance` / `pretty` | balance Baseline; pretty ohne Firefox ([MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Properties/text-wrap-style)) | Titel, Hinweistexte |
| `dvh/svh`, `env(safe-area-*)`, `viewport-fit=cover` | Wide | Shell, Tab-Leiste, Sheets ([web.dev](https://web.dev/blog/viewport-units)) |
| `touch-action: manipulation`, `:focus-visible`, `prefers-reduced-motion` | Wide | Tap-Delay weg, Fokusringe, Bewegung ([MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/touch-action)) |
| Speculation Rules (`prefetch`, `moderate`) | nur Chromium ([Caniuse](https://caniuse.com/wf-speculation-rules)) | Tab-Routen vorab laden; `not`-Regeln für Logout und mutierende URLs |
| bfcache | Standard in allen | Keine `unload`-Listener, kein `no-store`; `pageshow` frischt RSVP-Zahlen auf ([web.dev](https://web.dev/articles/bfcache)) |
| `interpolate-size` | nur Chromium (Safari Tech Preview) | Nur additiv; Fallback `grid-template-rows: 0fr → 1fr` |
| `appearance: base-select` | Chrome 135, Safari 27, nicht Firefox; ersetzt den nativen Mobil-Picker | Nur Desktop (`hover: hover` + `pointer: fine`), nie auf dem Phone |
| `linear()` Easing | Chrome 113, Firefox 112, Safari 17.2 ([Chrome](https://developer.chrome.com/docs/css-ui/css-linear-easing-function)) | Sheet-Feder, Press-Feedback |
| `corner-shape: squircle` | Chrome 139+ ([Smashing](https://www.smashingmagazine.com/2026/03/beyond-border-radius-css-corner-shape-property-ui/)) | Optionales Detail |

**Build-Pflicht:** Das Tailwind Play CDN ist laut Tailwind nicht für Produktion gedacht ([Tailwind](https://tailwindcss.com/docs/installation/play-cdn)). Ein CLI-Build erzeugt eine minifizierte statische CSS-Datei mit Hash und Immutable-Cache, ohne Laufzeit-JIT und FOUC. Das passt zur Launch-Reife und zur Offline-Fähigkeit der PWA.

**PWA-Shell-Details:**
- `<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">`.
- Statusleiste: Tag weglassen oder `default`, **nie** `black-translucent`. Header als `calc(44px + env(safe-area-inset-top, 0px))`, weil der Inset in Standalone 0 sein kann. Wert wird bei der Installation gelesen, Bestandsnutzer müssen die App neu hinzufügen (In-App-Hinweis) ([Joe Bell](https://github.com/joe-bell/skills/blob/main/skills/apple-web-app/references/theme-color-and-status-bar.md)). Safari 26 tönt die Leisten aus fixen/sticky Randelementen (≥ 90 % Breite, > 10 px hoch, alpha ≥ 0,75), nicht aus `theme-color`.
- Android Chrome 135+: Tab-Leiste mit `bottom: calc(env(safe-area-inset-bottom,0px) - env(safe-area-max-inset-bottom,36px))` plus passendem Body-Padding.
- `overscroll-behavior-y: contain` global; eigenes Pull-to-Refresh nur auf Proben-Liste und Anwesenheit (Auslöser ca. 64 px mit Widerstand 0,5; Judgement), dazu Refresh bei `pageshow` und bei `visibilitychange` nach > 60 s.
- iOS-Installationsführer (3 Schritte) mit `apple-touch-icon` 180×180; Android `beforeinstallprompt`-Button.
- Haptik optional: Android `navigator.vibrate(10)` bei Auswahl, Muster `[12,40,12]` bei Erfolg; iOS-Trick nicht ausliefern; Schalter „Haptisches Feedback“.

## 10 Anti-Patterns im aktuellen Probenplaner (aus dem Inventar)

**Struktur und Navigation**
1. Drawer-Sidebar mit bis zu 10 Textzeilen, 2 Taps zu jedem Ziel; Logout und „Ensemble wechseln“ stehen zwischen Hauptzielen.
2. Namenswirrwarr: „Meine Meldungen“ / „Rückmeldungen“ / „Termine“ / „Probenplan“; „Ensemble“ vs. „Orchester“; gemischtes Du/Sie (Profil „du“, Absage-Dialog „Sie“, `beforeunload`-Meldung).
3. Persönliche Zusammenfassung („3 zugesagt / 1 abgesagt / 4 offen“) liegt im geschlossenen Drawer, nicht auf dem Hauptscreen.
4. 64-px-Top-Bar + 24 px Padding: Inhalt beginnt ca. 88 px unter dem Rand. Titel als Gradient-Text, keine Large-Title-Logik, doppelter Titel (Top-Bar „Profil bearbeiten“ + H1 „Mein Profil“).
5. Rückmeldungs-Dashboard: Zwei 260–336 px hohe Analytics-Kacheln als Erstbildschirm. Verfälschte Semantik (nächste 10 Proben, Trend gegen sich selbst).
6. Tree-in-Tree-in-Card: drei verschachtelte Ränder/Radien (Karte > `.tree-view` > Tiefe-0-Knoten), 3–4 Akkordeonstufen, alles zu.

**Interaktion und Dialoge**
7. RSVP-Pending sieht deaktiviert aus (beide Buttons 40 % Deckkraft), keine Textbeschriftung, kein Statuswort.
8. Versteckte Gesten: Long-Press zum Zurücksetzen und Doppelklick zum Notiz-Ändern ohne Hinweis und ohne Touch-Äquivalent; Hilfe-Text noch „Klicken Sie irgendwo“.
9. Alle Dialoge sind zentrierte SweetAlert2-Popups mit Standardfarben (#478cf4, #6c757d, #4f46e5, #dc2626 wild gemischt); Fehler als blockierender Modal; native `confirm()` im Orga-/Admin-Bereich und beim Standard-Zurücksetzen der Registerstruktur.
10. Inline-Editor: unsichtbare Overlay-Inputs (opacity 0) mit gestricheltem Unterstrich, Mikro-Ziele (Tag-× 12 px, Rollen-× 14 px, Ablauf-Entfernen 24 px), geschachtelte Formular-Kästen (Padding 24, margin 32) in der Karte, Ablauf und Hinweise immer offen, Seite wird sehr lang. Native Time-Picker ist ein Firefox-Popover mit Maus-Scroll.
11. Zu viele Wege zum Erstellen (Toolbar „+“, Add-Box am Listenende, „Serie erstellen“); der Mehrfachauswahl-Modus nur über unbeschriftetes Icon oder 500-ms-Long-Press; Bulk-Leiste mit 7 Aktionen und 28-px-Scrollpfeilen.
12. Sticky Toolbar (Suche + 7 Chips) frisst ca. 110–130 px unter der Navbar; Filterwerte nicht am Chip ablesbar, kein „Alle zurücksetzen“.
13. Anwesenheit: Doppelring-Indikator ohne Legende, 3-stufiger Tap-Zyklus, endlose Pillenleiste aller Proben, rohe Instrument-IDs („· Violine_1“), Bulk-Bestätigung ohne Rückfrage/Undo.
14. Registerstruktur-Editor ist auf Touch nicht verschiebbar.
15. Stimmführer sehen anklickbare Mitgliederzeilen, die in „Keine Berechtigung“ enden; der Mitglieder-Dialog enthält „Passwort zurücksetzen“ und „Entfernen“ in derselben Ebene wie „Speichern“.

**Typografie, Layout und Visuelles**
16. Roboto deklariert, aber nicht geladen; fünf Google-Font-Familien ungenutzt (Aldrich, Goldman, Poppins 100–900, Sansation, Kantumruy Pro nur für Zeiten); rund 50 Deklarationen ≤ 11 px.
17. Gewichts- und Größenstreuung durch Inline-Styles und harte Werte (10/11/13/9 px), zwei konkurrierende `.btn-secondary`-Definitionen (weiß mit Rand vs. pinker Verlauf), Hex-Werte im JS-Dialog-HTML (#d1d5db, #4b5563, #007bff).
18. Wochentag in 24 px/900 mit Gradient-Text und iOS-`translateZ(0)`-Hack; 52-px-Check/Kreuz-Buttons verbrauchen ca. 36 % der Kartenbreite auf einem 375-px-Gerät.
19. Hover-Hüpfen (`translateY/X`, Shimmer-Sweeps) überall, aber keine Touch-Pressed-States, kein Haptik, keine Übergänge zwischen Seiten.
20. Dark-Mode-Tokens existieren, aber nichts aktiviert sie; Dark entsteht durch Umdefinieren von `--color-white/--color-black`.
21. Detail-Info versteckt: Hinweistexte (Kleidung, Abfahrt) erst nach dem Aufklappen sichtbar, Chevron 12 px überlappt das Kartenpadding; `max-height: 600px` kappt lange Abläufe.
22. Leere Zustände als blauer Textkasten (24-px-Titel, 18-px-Text, Shimmer) ohne Handlungsvorschlag.

**Barrierefreiheit und Technik**
23. `focus-removal.css` entfernt alle Fokusringe mit `!important`; `user-scalable=no` auf allen Seiten außer Probenplan; Status nur per Farbe (Button-Tint, Punkte); `#9ca3af`-Text (2,54:1); Icon-Buttons ohne Label (Hamburger, Hilfe, FAB, Kopieren).
24. Kein `safe-area` (nur in der Bulk-Leiste), `theme-color` statisch #ffffff, `100vh` an 8 Stellen; `.container-app` startet mit `opacity: 0` bis JS läuft (leerer Bildschirm).
25. Tailwind-Play-CDN, Font-Awesome-CDN, jQuery, SweetAlert2, tippy: Laufzeit-JIT, FOUC, kein Offline.
26. Tote/defekte Stellen, die nicht in den Neubau wandern sollten: Hilfe-Icon und Hilfe-Modal-Routing (URL-Präfix `/{org}/{ensemble}` wird nicht berücksichtigt), `#pastRehearsalsSection` wird nie gerendert (Past-Edit-Sperre greift clientseitig nicht), `calendar-integration-modal.php`, ungerouteter Legacy-Beitritt (`/orchestras/join`, `select-section`), Bootstrap-Reste in `errors/404.php`, `delete.php` mit kaputtem Icon-Markup, ca. die Hälfte von `promises-dashboard.css`.
27. Sicherheitshygiene: In `probenplan/index.php` werden `location` und `date_formatted` unescaped ausgegeben; globaler `window.onerror` zeigt Endnutzern rohe JS-Fehler.
28. Auth: 144-px-Logo plus 32-px-Abstände belegen ein Viertel eines 667-px-Screens, E-Mail-Feld ist `type=text`, kein Passwort-anzeigen, kein „Passwort vergessen“, Fehler als Modal, der Hauptpfad für JMD-Nutzer (JMD-Button) ist nur sekundär dargestellt; Beitritt per `<select>` mit ca. 20 Optionen trotz vorhandener Section-Emojis.

## 11 Priorisierte Ansatzpunkte (Top 15)

Aufwand: S = Tage, M = 1–2 Wochen, L = mehrere Wochen.

1. **Shell umbauen** (hoch, L): rollenadaptive Bottom-Tab-Bar (Proben · Plan · Team · Mehr/Profil), 44-px-Header mit Large Title, Rail/Sidebar-Stufen bei 600/840/1200 px; Sidebar-Hamburger unter 600 px entfernen.
2. **Barrierefreiheits-Sofortpaket** (hoch, S): Fokusringe über `:focus-visible` zurück, Zoom-Sperre raus, Eingaben auf 16 px, Kontrast-Tokens (#2f6fe0-Basis, #6b7280 Muted, dunkle Statustexte), Status mit Icon + Text, 44-px-Ziele.
3. **Token-Reset** (hoch, M): Typ-Skala, 4-px-Raster, Radien, drei Schattenstufen, Systemschrift; Google Fonts löschen, Font Awesome → SVG-Sprite, Tailwind-CDN → CLI-Build, Dark Mode mit `light-dark()`.
4. **RSVP-Control und Absage-Sheet** (hoch, M): beschriftetes Zwei-Segment, Offen-Zeile groß / beantwortet kompakt, optimistisch, Undo-Snackbar, Chips, sichtbarer Reset-Weg.
5. **Proben-Zeile, Datumschip, Hero und Anstehend/Vergangen** (hoch, M): neue Zeilenanatomie, Kategoriestreifen entkoppelt von Status, Wochen-Header, Heute-Marker.
6. **Sheet-Komponente auf `<dialog>`** (hoch, M–L): Detents, Griff, Schließen, Zurück-Geste; SweetAlert2-Dialoge schrittweise ersetzen (Absage, Profil, Mitglied, Rolle, Gruppen).
7. **Seitenübergänge und Ladegefühl** (hoch, S–M): Cross-Document View Transitions, bfcache, Speculation Rules, `pageshow`-Refresh, Skeleton-Policy (nichts < 1 s, Platzhalter verzögert, passend zur Kartenhöhe).
8. **PWA-Shell iOS/Android** (hoch, S): `viewport-fit=cover`, `dvh`, Safe-Area-Muster für beide Plattformen, `theme-color`-Paare, iOS-Installationsführer, App-Badge, Neuinstallations-Hinweis; Test auf frisch installierter App (iOS 26.1+/27).
9. **Proben-Detail mit „Wer kommt?“** (hoch, M): Stimmen-Balken, nur Offene, tappbares „n offen“, „Erinnern“, Insights optional; Analytics in „Team › Übersicht“ mit korrigierter Semantik.
10. **Anwesenheit neu** (hoch, M): vorbefüllt, 2-Zustands-Toggle, Fortschritt, Notiz-Sheet mit Chips, Undo; Tabelle nur ab 840 px.
11. **Leitungs-Editor** (hoch, L): sichtbare Feldzeilen, Auto-Save mit „Gespeichert“-Feedback, Vollbild auf Phone / Pane auf Desktop; einziger Erstellen-Einstieg (FAB-Aktions-Sheet), Serien-Wizard, Bulk mit beschrifteter Leiste.
12. **Mehr/Profil und Mitglieder** (mittel, M): Inset-Grouped-Listen, Ensemble-Wechsler im Header, Rollen & Rechte als eigener Screen, Mitgliederliste gruppiert mit Sticky-Headern, Detailseite.
13. **Desktop-Stufen** (mittel, L): Zwei-Pane, Tabellen, Hover/Pointer-Gating, Befehlspalette, Druck-Stylesheet bündeln.
14. **Visuelle Politur** (mittel, M): Glas auf Leisten/Sheets, Gradient-Politik, konzentrische Radien, Motion-Tokens, `prefers-reduced-motion`-Layer plus In-App-Schalter, Haptik-Option.
15. **Auth, Onboarding, Leerzustände, Copy** (mittel, S–M): schmaler Login mit Primär-JMD-Pfad, `inputmode=email`, Passwort-Toggle, Inline-Fehler; Stimmwahl als durchsuchbare Liste mit Emojis; rollenspezifische Leerzustände; konsequent „du“; einheitliche Begriffe (Probe, Stimme, Ensemble).

**Danach:** RSVP-Produktoptionen im Basistarif (Frist, Vielleicht, Pflichtgrund, Sichtbarkeit, Abwesenheiten), Aushilfen-Einladung per Link, Stimmen-bezogene Ablaufpunkte („Dein Einsatz“), Soll-Zahlen je Stimme, Offline-Cache der nächsten fünf Proben.

**Vor dem Build zu klären (nicht belegt oder nur indirekt):**
- Echtgerätetests: Cross-Document View Transitions in einer installierten iOS-26/27-PWA; Statusleiste/Safe-Area (WebKit 301994); Kantenwisch-Zurück; iOS-Haptik ab 26.5; Aktionsbuttons in iOS-Web-Push.
- Nutzungsdaten: iOS-PWA vs. Android vs. Desktop, Häufigkeit des Probenplans.
- Recht: BFSG-Anwendbarkeit; DSGVO-Risiko beim Google-Fonts-Hotlinking (aus Allgemeinwissen, nicht recherchiert).
- Eingebetteter JOinJMD-WebView-Modus ($_SESSION['embedded_app']): Tab-Leiste zeigen oder ausblenden, nicht untersucht.
- Konkurrenzmessung: Zeilenhöhen und Datumsblöcke von Fantastical, Apple Calendar, Planning Center wurden nicht erhoben, die große-Ziffer-Empfehlung stützt sich auf HIG und NN/g.
- Ob „Vielleicht“ gewünscht ist, ist eine Produktentscheidung mit der Leitungs-Persona.

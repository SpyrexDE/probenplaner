# UX-Audit: Regeln aus offiziellen Richtlinien

Hinweis: Apple-Regeln stammen aus den HIG-Seiten (JSON-Quelle `developer.apple.com/tutorials/data/design/human-interface-guidelines/<seite>.json`, Seite = gleiche Slug wie unter `/design/human-interface-guidelines/<seite>`). m3.material.io ließ sich nicht abrufen (JS-Seite); Material-Regeln stammen aus developer.android.com und den Material-Components-Docs. Fluent-2-Site liefert keine Dichte-/Navigationsregeln, dafür Microsoft Learn.

## 1. Apple HIG

- Tab-Bar nur für Navigation zwischen Bereichen, nie für Aktionen; Aktionen gehören in eine Toolbar. https://developer.apple.com/design/human-interface-guidelines/tab-bars
- Tab-Bar sichtbar halten, Tabs nie ausblenden oder deaktivieren; leere Bereiche erklären warum. https://developer.apple.com/design/human-interface-guidelines/tab-bars
- Wenige Tabs; Standard auf iPad: max. 5 Tabs, damit Compact und Regular gleich bleiben; "Mehr"-Überlauf vermeiden. https://developer.apple.com/design/human-interface-guidelines/tab-bars
- ★ Jeder Tab hat ein Label (möglichst ein Wort) plus SF-Symbol, bevorzugt gefüllt. https://developer.apple.com/design/human-interface-guidelines/tab-bars
- Badge nur für kritische Information: rotes Oval, weiße Zahl oder Ausrufezeichen. https://developer.apple.com/design/human-interface-guidelines/tab-bars
- Sidebar: max. zwei Hierarchieebenen; tiefer = Split View mit Inhaltsliste zwischen Sidebar und Detail. https://developer.apple.com/design/human-interface-guidelines/sidebars
- Sidebar-Icons: bekannte Symbole, Systemakzent; feste Icon-Farben sparsam und nur mit Zweck. https://developer.apple.com/design/human-interface-guidelines/sidebars
- Sidebar nicht standardmäßig verstecken (Auffindbarkeit); wichtige Aktionen nie unten in der Sidebar (macOS). https://developer.apple.com/design/human-interface-guidelines/sidebars
- iPad: erst Tab-Bar erwägen, bei komplexer App in Sidebar wandelbar (`sidebarAdaptable`). https://developer.apple.com/design/human-interface-guidelines/sidebars
- ★ Layout nach Size Classes, nicht nach Gerät oder Orientierung; Funktionalität in allen Größen identisch, nur die Menge des Sichtbaren ändert sich (z. B. Tab-Bar → Sidebar). https://developer.apple.com/design/human-interface-guidelines/layout
- Wichtiges oben und am Leading-Rand; Ausrichtung = verwandt, Einrückung = untergeordnet; Gruppen per Leerraum, Container oder Trenner. https://developer.apple.com/design/human-interface-guidelines/layout
- Split View: Sidebar = oberste Ebene, weitere Panes für Kinder/Details; aktuelle Auswahl in jedem Pane dauerhaft markieren. https://developer.apple.com/design/human-interface-guidelines/split-views
- Split View nur in Regular-Umgebung, nicht auf Compact (iPhone hochkant); Panes brauchen Min/Max-Breiten. https://developer.apple.com/design/human-interface-guidelines/split-views
- Listen/Tabellen für Text (Zeilenformat scannt gut); Itemtext kurz halten, lange Inhalte nur als Titel + Detailansicht. https://developer.apple.com/design/human-interface-guidelines/lists-and-tables
- macOS-Tabellen: sortierbare, größenänderbare Spalten, Substantiv-Spaltenköpfe, ggf. alternierende Zeilenfarben. https://developer.apple.com/design/human-interface-guidelines/lists-and-tables
- ★ Farbe nie als einziges Unterscheidungsmerkmal; mit Text oder eigener Glyphenform paaren. https://developer.apple.com/design/human-interface-guidelines/color
- Dieselbe Farbe nie für verschiedene Bedeutungen; Akzentfarbe nur für Status und Primäraktion, nicht auf mehreren Controls. https://developer.apple.com/design/human-interface-guidelines/color
- Eigene Farben brauchen Light-, Dark- und Increased-Contrast-Variante. https://developer.apple.com/design/human-interface-guidelines/color
- Typo-Hierarchie über Gewicht, Größe, Farbe; so wenig Schriftfamilien wie möglich; Regular bis Bold, keine Thin/Light klein. https://developer.apple.com/design/human-interface-guidelines/typography
- Mindestgröße iOS 11 pt (Standard 17 pt), macOS 10 pt (Standard 13 pt). https://developer.apple.com/design/human-interface-guidelines/typography
- Icons: gleiche Größe, Detailgrad, Strichstärke und Perspektive; Gewicht passend zum Nachbartext. https://developer.apple.com/design/human-interface-guidelines/icons
- Ein Icon = ein Konzept; für Standardaktionen die Standard-Symbole (z. B. `checkmark`, `xmark`, `plus`). https://developer.apple.com/design/human-interface-guidelines/icons
- SF-Symbol-Varianten tragen Zustand konsistent (fill = ausgewählt, slash = nicht verfügbar). https://developer.apple.com/design/human-interface-guidelines/sf-symbols

## 2. Material 3 / Android adaptiv

- ★ Breiten-Klassen: Compact <600, Medium 600–839, Expanded 840–1199, Large 1200–1599, Extra-large ≥1600 dp (Höhe: <480 / 480–899 / ≥900). https://developer.android.com/develop/ui/compose/layouts/adaptive/use-window-size-classes
- Klassen richten sich nach Fenstergröße, nicht Gerät; sie ändern sich zur Laufzeit. https://developer.android.com/develop/ui/compose/layouts/adaptive/use-window-size-classes
- ★ Navigation: Navigation Bar bei Compact (Breite oder Höhe), Navigation Rail für alles andere; Permanent Drawer für Expanded+ ist eine dokumentierte Option. https://developer.android.com/develop/ui/compose/layouts/adaptive/build-adaptive-navigation
- Dieselben Destination-Definitionen (Icon, Label) speisen Bar, Rail und Drawer. https://developer.android.com/develop/ui/compose/layouts/adaptive/build-adaptive-navigation
- Navigation Bar: 3–5 Ziele, jedes mit Icon und Label; ab 4 Zielen zeigt Auto-Modus das Label nur am aktiven. https://github.com/material-components/material-components-android/blob/master/docs/components/BottomNavigation.md
- Rail-Anatomie: Container 80 dp; oben optional Menü und FAB/Extended FAB, darunter Items mit Icon, Active-Indicator, Label. https://github.com/material-components/material-components-android/blob/master/docs/components/NavigationRail.md
- Rail eingeklappt 3–7 Ziele; ausgeklappte Rail ohne Limit und ersetzt den Drawer. https://github.com/material-components/material-components-android/blob/master/docs/components/NavigationRail.md
- Badge sitzt oben rechts am Icon; ohne Zahl als Punkt, mit Zahl/Text als Large Badge. https://github.com/material-components/material-components-android/blob/master/docs/components/NavigationRail.md
- Ab Medium: Items horizontal (Icon links, Label rechts) statt gestapelt. https://github.com/material-components/material-components-android/blob/master/docs/components/BottomNavigation.md
- ★ List-Detail: bei Expanded beide Panes nebeneinander; bei Medium/Compact Liste ODER Detail, Zurück kehrt zur Liste; Auswahl bleibt beim Resize erhalten. https://developer.android.com/develop/ui/compose/layouts/adaptive/canonical-layouts
- Supporting Pane: Compact darunter/Bottom-Sheet; Medium 50/50; Expanded 70/30 (Hauptinhalt/Zusatz). https://developer.android.com/develop/ui/compose/layouts/adaptive/canonical-layouts
- Supporting Pane nur für Inhalte, die ohne den Hauptinhalt sinnlos sind; sonst List-Detail. https://developer.android.com/develop/ui/compose/layouts/adaptive/canonical-layouts
- ★ Feed/Karten auf großen Fenstern als Raster (Spalten mit Mindestbreite, z. B. 180 dp), nicht gestreckte Einzelspalte; gleiche Größe = Gruppe, größer = Aufmerksamkeit. https://developer.android.com/develop/ui/compose/layouts/adaptive/canonical-layouts
- Erst für Expanded optimieren (meiste Fläche), dann Medium; Zwei-Pane-Layouts nicht bei Compact-Höhe. https://developer.android.com/develop/ui/compose/layouts/adaptive/use-window-size-classes
- Zeilenlänge Fließtext 50–75 Zeichen, WCAG 1.4.8 max. 80. https://baymard.com/blog/line-length-readability

## 3. Microsoft Fluent / Windows

- NavigationView adaptiv: ≤640 px nur Menü-Button, 641–1007 px linke Icon-Leiste, ≥1008 px ausgeklappte Pane (Schwellen einstellbar). https://learn.microsoft.com/en-us/windows/apps/design/controls/navigationview
- Responsive-Techniken: Reposition, Resize, Reflow, Show/Hide, Re-architect (Liste + Detail bei großen Fenstern). https://learn.microsoft.com/en-us/windows/apps/design/layout/responsive-design
- Große Fenster nutzen für mehr Inhalt statt Navigation: mehrere Kategorien gleichzeitig, mehr Metadaten, gestapelte Listeneinträge werden Zeilen mit Spalten. https://learn.microsoft.com/en-us/windows/apps/design/layout/responsive-design
- Kleine Fenster: minimale Metadaten; große Fenster: viel Metadaten zeigen. https://learn.microsoft.com/en-us/windows/apps/design/layout/responsive-design
- Fluent-2-Breakpoints: 320–479, 480–639, 640–1023, 1024–1365, 1366–1919, 1920+ px. https://fluent2.microsoft.design/layout
- 12-Spalten-Raster, Basiseinheit 4 px; wichtigster Inhalt bekommt die größten Rasterbereiche; Touch-Ziel min. 44×44 (Web/iOS). https://fluent2.microsoft.design/layout
- Fluent 2 nennt keine Dichte-Modi und keine Max-Breiten (geprüft); Leerraum für "roomy visual rhythm". https://fluent2.microsoft.design/layout
- Command Bar: auf großen Bildschirmen oben platzieren (auffindbarer), auf Handhelds unten. https://learn.microsoft.com/en-us/windows/apps/design/controls/command-bar
- Command-Bar-Buttons haben Icon + Label; bei großen Fenstern Label rechts neben das Icon, damit es sichtbar bleibt. https://learn.microsoft.com/en-us/windows/apps/design/controls/command-bar
- Befehle nach Wichtigkeit ordnen, wichtigste immer sichtbar; Überlauf nur für Sekundäres; gleiche Aktion immer am gleichen Ort. https://learn.microsoft.com/en-us/windows/apps/design/controls/command-bar

## 4. Wahrnehmung / Glanceability (NN/g)

- Pre-attentive: Länge und 2D-Position für Mengen nutzen; Fläche/Winkel schlecht einschätzbar; Farbe nie für Größenordnung. https://www.nngroup.com/articles/dashboards-preattentive/
- Farbe nur sekundär, Gruppierung zusätzlich über Form oder Nähe tragen; bis 4,5 % Farbfehlsichtigkeit. https://www.nngroup.com/articles/dashboards-preattentive/
- Dashboard: eine Seite, ein Zweck (kritische Info), wenig Interaktion; Balken, Linien, Bullet-Charts; keine Kreis-, Donut-, Treemap-, Gauge-, 3D-Charts. https://www.nngroup.com/articles/dashboards-preattentive/
- ★ Farbpalette: ca. 2 Primär- + 2 Sekundärfarben; komplexe Designs max. 3 Kontraststufen ("alles kontrastiert = nichts fällt auf"). https://www.nngroup.com/articles/visual-hierarchy-ux-definition/
- Max. 3 Schriftgrößen, max. 2 große Elemente; Sättigung/Warm (Rot) für Warnungen reservieren. https://www.nngroup.com/articles/visual-hierarchy-ux-definition/
- Squint-Test: unscharf ansehen, ob Gruppen und Betonung der Absicht entsprechen. https://www.nngroup.com/articles/visual-hierarchy-ux-definition/
- Kontrast nie über schwächeren Textkontrast absenken; Rot nur für Löschen/Fehler. https://www.nngroup.com/articles/principles-visual-design/
- Proximity: Zusammengehöriges nah, Fremdes mit Abstand trennen; Nähe schlägt Ähnlichkeit; Gruppierung bei jeder Breite neu prüfen (Stapeln trennt Nachbarn). https://www.nngroup.com/articles/gestalt-proximity/
- Ähnlichkeit: gleiches Aussehen = gleiche Funktion; zwei Badges gleicher Form per Farbe oder Icon unterscheiden; gleiche Größe für gleichartige Inhalte. https://www.nngroup.com/articles/gestalt-similarity/
- 79 % scannen immer, 16 % lesen Wort für Wort; Wortzahl auf die Hälfte kürzen, Schlüsselwörter hervorheben. https://www.nngroup.com/articles/how-users-read-on-the-web/
- Ohne starke visuelle Anker scannen Nutzer F-förmig und überspringen rechts; Wichtiges mit Gruppen und Hervorhebung ziehen. https://www.nngroup.com/articles/f-shaped-pattern-reading-web-content/
- Indikatoren/Badges nur wenn wichtig genug für den Platz: kontextnah, bedingt, passiv; zu viele = Rauschen. https://www.nngroup.com/articles/indicators-validations-notifications/
- ★ Icons brauchen sichtbare Textlabels (nie nur Hover); nur wenige Icons (Home, Suche, Drucken) sind universell. https://www.nngroup.com/articles/icon-usability/
- Navigation sichtbar halten; Tab-Bar bei ≤5 Zielen; Hamburger ist am wenigsten auffindbar. https://www.nngroup.com/articles/mobile-navigation-patterns/

## 5. Konsistenz über Breakpoints

- ★ Pro Funktion ein Icon, überall gleich; Bedeutung eines Icons nie wechseln. https://www.nngroup.com/articles/consistency-and-standards/
- Menüreihenfolge über alle Ausprägungen gleich; ein visueller Stil pro Elementtyp. https://www.nngroup.com/articles/consistency-and-standards/
- Funktionalität in allen Größen identisch; nur Sichtbares variiert (Tab-Bar ↔ Sidebar). https://developer.apple.com/design/human-interface-guidelines/layout
- Dieselbe Destination (Icon + Label + Reihenfolge) in Bar, Rail und Drawer aus einer Definition speisen. https://developer.android.com/develop/ui/compose/layouts/adaptive/build-adaptive-navigation
- Tab-Anzahl so wählen, dass sie in Compact und Regular gleich bleibt (≤5). https://developer.apple.com/design/human-interface-guidelines/tab-bars
- Gleiche Aktion bleibt an gleicher Stelle; Position nicht je Breakpoint wechseln. https://learn.microsoft.com/en-us/windows/apps/design/controls/command-bar
- Auswahlzustand und Kontext beim Resize erhalten (List-Detail merkt das gewählte Item). https://developer.android.com/develop/ui/compose/layouts/adaptive/canonical-layouts
- Hamburger fällt auf Desktop weniger auf als auf Mobil; auf großen Screens Navigation sichtbar machen. https://www.nngroup.com/articles/icon-usability/
- Gleiche Badge-Form = gleiche Bedeutung; abweichende Bedeutung per Farbe/Icon trennen. https://www.nngroup.com/articles/gestalt-similarity/

## Top 10 (★)

Tab/Nav-Label + Symbol, Size Classes statt Gerät, Farbe nie allein, Breakpoints 600/840/1200/1600, Bar → Rail, List-Detail, Raster statt gestreckter Karten, Farbbudget 2+2, Icons mit sichtbarem Label, ein Icon pro Funktion überall.

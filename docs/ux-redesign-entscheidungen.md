# Probenplaner: Designentscheidungen der UX-Modernisierung

Stand: 24.10.2026. „Spec §n“ meint `docs/ux-redesign-spec.md` (maßgeblich für jede Zahl), „Recherche Kap. n“ meint `docs/ux-modernisierung-recherche.md`. Den Prototyp startest du im Repo-Root mit `php -S localhost:5173 -t prototype`. Rolle und Theme wechselst du per `?role=` und `?theme=dark`.

## 1 Ausgangslage und Ziel

Probenplaner wirkt nicht wegen des Farbstils alt, sondern wegen Struktur und Maßstab (Recherche Kap. 1). Die Navigation ist eine Seitenleiste mit bis zu 10 Einträgen hinter einem Hamburger. Dazu kommen eine 64-px-Leiste, 52-px-Icon-Buttons, 10-px-Mikrotext, zentrierte SweetAlert-Dialoge und Voll-Reloads ohne Übergang. Das Ziel ist ein natives App-Gefühl bei gleichem weichen Stil: Blau #478cf4, 135°-Verläufe, runde Karten, Kategoriestreifen. Für ein kommerzielles Produkt kommt Barrierefreiheit dazu: Kontrast ab 4,5:1, 44-px-Ziele, Status nie nur über Farbe.

## 2 Das Panel und die Entscheidung

Drei Konzepte wurden parallel entworfen und von einer Jury bewertet.

- **A „iOS-native first“:** beste Navigation und Detail-Anatomie. Aber die Karten werden zu Zeilen, und der Umfang war für die Prototyp-Laufzeit `app.js` zu groß.
- **B „Material 3 adaptive“:** dicht, wirkt auf dem iPhone aber wie Android. Schattenkarten und blauer Hero gehen verloren.
- **C „Evolve what exists“:** behält Karte, Glow-Ring, Verläufe und Streifen und ersetzt nur Rahmen und Maßstab. Etwas weniger dicht: Eine offene Karte ist 132 px hoch.

| Konzept | Native (30 %) | Marke (25 %) | Dichte (20 %) | Umsetzbar (15 %) | Politur (10 %) | Gesamt |
|---|---|---|---|---|---|---|
| A | 9 | 7,5 | 9 | 5 | 9 | 8,03 |
| B | 6,5 | 6 | 8 | 6 | 8 | 6,75 |
| **C** | 8 | 9,5 | 7,5 | 8 | 8 | **8,28** |

Das Protokoll nennt nur 25 % und 15 %, die übrigen Gewichte sind aus den Summen zurückgerechnet. C gewinnt bei den zwei Dingen, die du genannt hast: Marken-Look mit Probenkarte und Inline-Editor sowie Umsetzbarkeit.

**Von A übernommen:** Ensemble-Chip in der Kopfleiste (spart etwa 40 px), Team als Tab mit Segmenten, gruppierte Listen für Profil und Mehr, blauer Hero mit Live-Zeile, „Wer kommt?“ mit Erinnern-Sheet, KPI-Streifen, Token-Architektur (OKLCH-Neutrals, eigener Dark-Block) und die Leiste „Alle n Vorschläge übernehmen“.

**Von B übernommen:** das rosa Konzert-Badge (Generalprobe, Konzert, Konzertreise; andere Arten violett), die Statusverläufe 50→100 mit Glow für die gewählte Antwort und #b91c1c als Absage-Text.

**Bei Widersprüchen entschieden:**
- „Neue Probe“ ist ein FAB mit Aktions-Sheet, kein „+“ in der Kopfleiste.
- Tab- und Segmentwechsel sind sofort, Sheets ohne Überschwingen.
- Theme über `[data-theme]`-Blöcke statt `light-dark()`, weil es vor dem ersten Paint feststeht.
- Leitung landet beim Antippen im Editor, alle anderen in der lesenden Detailseite.
- Mitglieder sehen Antworten anderer nie (Datenschutz).
- Swipe, Pull-to-Refresh, Filter, Mehrfachauswahl, Serie, KI-Import und Fehlzeiten sind P2 (Abschnitt 9).

## 3 Navigation und Informationsarchitektur

Der Hamburger entfällt: In der NN/g-Studie wurde ein verstecktes Menü mobil in 57 % der Aufgaben genutzt, eine kombinierte Navigation in 86 % (Recherche Kap. 2). Aus „Meine Meldungen“, „Rückmeldungen“, „Termine“ und „Probenplan“ werden **Proben** und **Plan**. Die Antworten stehen als „Wer kommt?“ in der Probe (Recherche Kap. 5).

Tabs je Rolle (Spec §4.5):

| Rolle | Slot 1 | Slot 2 | Slot 3 | Slot 4 |
|---|---|---|---|---|
| Mitglied | Proben `/promises` (calendar-check), Badge = offene Antworten | Plan `/plan` (list-music) | Profil `/profile` (circle-user-round) | – |
| Stimmführung | Proben `/promises`, Badge wie oben | Plan `/plan` | Team `/team` (users-round), Badge = nicht erfasst | Profil `/profile` |
| Leitung | Proben `/rehearsals`, kein Badge | Plan `/plan` | Team `/team`, Badge = nicht erfasst | Mehr `/more` (circle-ellipsis) |

- **Offene Antworten:** eigene Antwort offen, Probe nicht abgesagt, Ende nach jetzt, Beginn innerhalb von 28 Tagen.
- **Nicht erfasst:** Beginn spätestens jetzt, nicht abgesagt, letzte 60 Tage, keine Anwesenheitszeilen.
- Fehlt das Recht, fehlt der Tab. Er wird nie ausgegraut.

**Team-Segmente.** Leitung: Übersicht | Anwesenheit | Mitglieder. Stimmführung: Anwesenheit | Mitglieder. Die Segmente sind Geschwister-Routen mit sofortigem Wechsel. `/team` leitet auf die zuletzt gewählte weiter.

**Header.** 44 px plus Safe-Area auf dem Telefon, 56 px ab 840. Links steht der Ensemble-Chip, darunter der Large Title 34/41. Beim Scrollen klappt er zu einem zentrierten Titel 17/22 ein, und in 150 ms blendet sich Glas mit Haarlinie ein. „Zurück“ ist ein reiner Chevron. Ab 840 steht der Titel links, der Large Title entfällt.

**Breakpoints** (Material-Fensterklassen statt Tailwind 768/1024, Recherche Kap. 7):
- **< 600:** Tab-Leiste (56 px + Safe-Area), eine Spalte, Gutter 16 (ab 430: 20).
- **600–839:** Rail (80 px), zentrierte Spalte, max. 680.
- **840–1199:** Rail. Proben als Liste (360) und Detail (max. 760), Sheets als Dialoge mit 560.
- **≥ 1200:** Sidebar (240 px), Liste 380.

**Seite oder Sheet** (Spec §4.9): Eine **Seite** gehört zu einem Objekt oder einer langen Aufgabe (Detail, Editor, Serie). Ein **Sheet** gehört zu einer Entscheidung oder bis zu drei Feldern (Absage, Erinnern). Ein **Aktions-Sheet** zeigt 2 bis 5 Optionen und jede destruktive Bestätigung. Sheets stapeln sich nie.

## 4 Typografie, Dichte, Farbe

Die Schrift ist der Systemstack (SF, Segoe, Roboto). Die fünf Google-Fonts und Font Awesome entfallen, Icons sind Lucide als Inline-SVG. `ui-rounded` gilt nur für Ziffern (Spec §3).

| Stufe | Größe/Zeile | Gewicht | Einsatz |
|---|---|---|---|
| largeTitle | 34/41 | 700 | Tab-Wurzeln |
| title1 | 28/34 | 700 | Login, KPI ab 840 |
| title2 | 22/28 | 600 | Hero, Detailtitel |
| title3 | 20/25 | 600 | Kontoname, Leerzustand |
| headline | 17/22 | 600 | Uhrzeit, Nav-Titel, Sheet-Titel |
| body | 17/22 | 400 | Zeilen, Eingaben, Hinweise |
| callout | 16/21 | 400/600 | Namen in dichten Listen |
| subhead | 15/20 | 400–600 | Meta, Chips, Buttons |
| meta | 14/19 | 400/600 | Statuspille, Notizen |
| footnote | 13/18 | 400/600 | Abschnittsköpfe |
| caption | 12/16 | 500/600 | Badges, Wochentag |
| micro | 11/13 | 500/600 | nur Tab-Labels, Zähler |

Eingaben sind mindestens 16 px, sonst zoomt iOS. Zeiten, Daten und Zähler laufen mit `tabular-nums`.

**Dichte** kommt aus dem Raster (4/8/12/16/20/24/32/40) und aus Zeilenhöhen, nicht aus kleinem Text (Recherche Kap. 3): 44 einzeilig, 60 zweizeilig, Karte mindestens 76. Auf 375×812 siehst du Hero plus drei Karten, nach dem Einklappen des Titels etwa sechs. „Kompakt“ senkt das Kartenpadding von 12 auf 8 und die Kartenhöhe auf 68.

**Radien und Schatten.** Radien 6/8/12/16/20/24 und Kapsel, Innenradius = Elternradius minus Padding. Karten 16, Hero 20, Sheets oben 24. Drei Schattenstufen, bläulich getönt, nie animiert. Im Dark Mode ersetzt hellere Fläche (+4,5 %) den Schatten.

**Farbe:**
- Buttons: #478cf4 hat auf Weiß nur 3,32:1. Der Verlauf läuft jetzt von #2f6fe0 (4,70:1) nach #2563eb (5,17:1). #478cf4 bleibt für Icons, Ringe und Tönungen.
- Statustext: #047857 (5,5:1, vorher #10b981 mit 2,54:1), #b91c1c, #b45309. Die hellen Töne bleiben Füllung und Balken.
- Gedämpfter Text: ≈ #626a7a, 5,0:1 auf Weiß. Vorher #9ca3af mit 2,54:1.
- Jeunesse bleibt (weiß auf #006aa6 ≈ 5,6:1). Das alte Grün #92c02b fällt mit Weiß durch, jedes Schema braucht einen 4,5:1-Check (Recherche Kap. 3).

**Was von der Marke bleibt:**
- **Blau-Verläufe als Tiefe:** höchstens drei gesättigte Flächen ab 44 px je Screen (Primär-Button oder FAB, Hero oder Heute-Chip, eigener Avatar). Glas nur auf Chrome-Elementen wie Tab-Leiste, Kopfleiste und Sheets.
- **Status-Triade** Grün #10b981, Rot #ef4444, Grau, immer mit Icon und Text.
- **Kategorie-Streifen** 3 px in einer der 12 benannten Farben, unabhängig vom Status.

## 5 Die Probenkarte und der Editor

**Die Karte** behält ihre Anatomie (Spec §5.11):
- **Datums-Chip** 44×48 statt Gradient-Wochentag: Wochentag 12/16, Tag 22/28 in rounded Ziffern, blasse Tönung. Heute ist er voll im Verlauf. Mehrtägige Proben zeigen „FR–SO 6–8“.
- **Zeile 1:** Uhrzeit 17/22 tabellarisch.
- **Zeile 2 nur mit Ausnahmen:** Art ≠ Probe, Stimmen-Satz ≠ „Tutti“, Ort ≠ „Probenraum“, Rollen-Pillen, bis zu drei Info-Emojis.
- **Status** als Pille mit Icon und Text.

**RSVP.** Nur eine offene Karte zeigt die beschriftete Leiste „Zusagen | Absagen“ (44 px). Beantwortete Karten schrumpfen auf die Pille. Die alten zwei 52-px-Icon-Buttons mit 40 % Deckkraft sahen deaktiviert aus. Die Pille öffnet „Antwort ändern“ (Spec §6.1). Die Absage speichert sofort und öffnet ein Sheet mit Grund-Chips. Ist „Grund bei Absage verlangen“ an, kommt das Sheet vor dem Speichern. Leitung sieht statt der Pille einen Antwort-Meter mit Triade.

**Editor.** Die Inline-Idee bleibt: Oben steht die Karte, wie Mitglieder sie sehen, mit Glow-Ring. Darunter folgen sichtbare Feldzeilen (Spec §6.4): Wann, Was & Wo, Für wen, Ablauf, Hinweise, Intern. Dazu kommen Duplizieren (+7 Tage), Absagen und Löschen mit Rückgängig.

Auf dem Telefon ist der Editor eine Vollbild-Seite, ab 840 die rechte Pane. Es gibt keinen Speichern-Button. Jede Änderung gilt sofort, Text nach 600 ms. Der Untertitel zeigt „Speichert…“ oder „Gespeichert“. Fällt das Ende vor den Beginn, wird nichts gespeichert. Unsichtbare Overlay-Inputs, graue Schachtelkästen und der Picmo-CDN-Picker entfallen (Recherche Kap. 10, Nr. 10).

**„Wer kommt?“** (Spec §5.21) ersetzt den 3–4-stufigen Akkordeon-Baum. Oben steht die Triade, darunter eine Zeile je Stimme mit Mini-Balken und „18/28“, Auffälliges in Amber. „6 offen – Erinnern“ öffnet das Erinnern-Sheet, der Toast meldet „Erinnerung an 5 von 6 gesendet“. Stimmführung sieht Personenzeilen der eigenen Stimme.

## 6 Screens im Überblick

**Proben (§6.1).** „Anstehend | Vergangen“ ersetzt den HEUTE-Trick und den Nachlade-Button. Der Hero zeigt die Probe, die läuft oder innerhalb von 7 Tagen beginnt. Es folgen Wochen und Monate mit klebenden Trennern.

**Probe-Detail (§6.2).** Detailkarte mit Ort und Kartenlink, „Kommst du?“, „Wer kommt?“ (Stimmführung), Hinweise als Text, Ablauf als Zeitleiste. Hier erscheinen auch die Standardwerte.

**Rückmeldungen (§6.2).** Segmente „Offen | Absagen | Zusagen“, Gruppen je Instrument, unten „6 per Push erinnern“.

**Leitung-Liste (§6.3).** Dieselbe Liste mit Antwort-Metern und einem Hero mit „Anwesenheit erfassen“. Der FAB „Probe“ öffnet Neue Probe, Serie und KI-Import.

**Editor (§6.4).** Siehe Abschnitt 5.

**Team › Übersicht (§6.5).** Drei KPIs: Rückmeldungen und Zusagen der nächsten 4 Wochen, Anwesenheit der letzten 10 Proben. Sie ersetzen Kacheln, deren Trend dieselben 10 Proben mit sich verglich (Recherche Kap. 6). Darunter folgen „Braucht Aufmerksamkeit“ und „Nächste Proben“.

**Team › Anwesenheit (§6.6).** Kontextkarte mit ‹ Datum › und Fortschritt. Der Schalter „Da | Fehlt“ ist aus den Zusagen vorbefüllt, als gestrichelter Vorschlag. „Alle n Vorschläge übernehmen“ bestätigt mit 5 s Rückgängig. Doppelring und dreistufiger Tipp-Zyklus entfallen.

**Team › Mitglieder (§6.8).** Suche, Rollenfilter, Gruppen je Register. Die Detailseite bietet Schnellaktionen. Für Leitung stehen Passwort zurücksetzen und Entfernen am Ende hinter einer Bestätigung.

**Plan (§6.7).** „Meine Stimme | Alle“, Monatsköpfe, 56-px-Zeilen. Ab 840 und im Druck (A4 hochkant) gibt es eine Tabelle.

**Profil, Mehr, Einstellungen (§6.9–6.11).** Gruppierte Listen. „Darstellung“ bündelt System/Hell/Dunkel, Farbschema, Dichte, Bewegung und Transparenz. Der Push-Vorlauf wird zur Preset-Auswahl (30 Min. bis 1 Tag). Einstellungen sind Schalterzeilen mit Sofort-Speichern. „Ensemble löschen“ ist ein Sheet mit Namenseingabe statt vier Schritten.

**Anmelden (§6.12).** „Mit JMD-Account anmelden“ ist der Hauptpfad, E-Mail und Passwort folgen darunter. Fehler erscheinen inline statt im Modal.

## 7 Bewegung und Feedback

- **Dauern:** 100 / 150 / 250 / 350 ms, Push 300 ms. Animiert werden nur `transform` und `opacity`. Grund: `top/left` kostete 37 ms Rendering plus 79 ms Painting, Transform etwa 0 ms (Recherche Kap. 2).
- **Kurven:** Standard `cubic-bezier(0.2, 0, 0, 1)`, Push `(0.2, 0.8, 0.2, 1)`, Sheet `(0.32, 0.72, 0, 1)`. Die Feder (1,7 % Überschwingen) gilt nur für Schalter, RSVP-Icon und Badge.
- **Push und Zurück:** Die neue Seite gleitet von 100 % herein, die alte wandert um −30 % und blendet auf 0,5.
- **Tabs und Segmente:** sofort. Den 150-ms-Cross-Fade der Recherche (Kap. 1, Nr. 8) verwarf die Jury.
- **Sheets:** Auftritt 350 ms, Abgang 250 ms, Wegziehen ab einem Drittel der Höhe. Ab 840 skaliert der Dialog von 0,96 auf 1.
- **Press:** Skalierung 0,97 in 100 ms.
- **RSVP und Toasts:** Die Oberfläche reagiert in unter 100 ms. Ein Toast bleibt 3 s, mit „Rückgängig“ 5 s. Bei Fehlern gibt es Rollback und Fehlertoast. Bestätigungsdialoge gibt es nur für Unumkehrbares.
- **Reduzierte Bewegung** greift per OS oder Schalter: keine Slides, kein Puls, keine Press-Skalierung, Dauern höchstens 150 ms.

## 8 Übertragung in die PHP-App

**Was sich ändert.** Der Prototyp ist eine Hash-Router-App mit JS-Laufzeit. Übertragbar sind Tokens, CSS, Markup, Texte und Regeln. `setHeader` wird zu Layout-Variablen (`$title`, `$large`, `$back`, `$leading`, `$subtitle`, `$actions`), `openSheet` zu `<dialog>`, die Router-Transitions zu Cross-Document View Transitions (Recherche Kap. 9).

**Zwei Layouts statt Big Bang.** `Controller::render()` nutzt immer `layouts/default`, obwohl `TemplateRenderer::layout()` existiert. Ergänze einen Parameter, damit jeder Screen einzeln auf ein neues Layout `app.php` wechselt. Jedes Layout lädt nur seine Stylesheets, denn `.empty-state` und `.fab` existieren schon als Inline-Styles in `empty-state.php` und `fab.php`. Gib das neue Layout erst nach Schritt 7 frei, sonst wechselt die Navigation zwischen Seiten. Davor testest du hinter einem Session-Flag.

Alle Tage sind **Schätzung** für eine Person, die die drei CSS-Dateien aus dem Prototyp übernimmt. Die S/M/L-Klassen der Recherche (Kap. 11) gelten für den Neubau.

| # | Schritt | Tage (Schätzung) |
|---|---|---|
| 1 | **Fundament:** drei CSS-Dateien unter `src/public/assets/css/`, versioniert über `$assetVersion` oder Hash. Lucide-Sprite mit Helfer `icon()`. | 3–4 |
| 2 | **Shell:** `app.php` mit Header, Tab-Leiste, Rail und Sidebar aus einem DOM. Die Rechte-Logik aus `sidebar-content.php` speist die Tabs, Badges serverseitig. | 5–7 |
| 3 | **Sheet-Baustein:** `<dialog>`-Partial mit JS (History-Eintrag, Wegziehen, Backdrop-Klick für Safari), Toast mit Rückgängig. | 3–4 |
| 4 | **Proben (Mitglied):** Karte, Chip, RSVP, Absage-Sheet, Hero. | 5–7 |
| 5 | **Probe-Detail:** Wer kommt?, Rückmeldungen, Erinnern. | 5–6 |
| 6 | **Plan:** Agenda, Tabelle, Druck. | 2–3 |
| 7 | **Profil, Mehr, Einstellungen, Darstellung.** | 4–6 |
| 8 | **Team:** Übersicht, Anwesenheit, Mitglieder. | 9–12 |
| 9 | **Leitung-Liste und Editor:** der größte Brocken. | 10–14 |
| 10 | **Anmelden, Ensembles, Onboarding.** | 2–3 |
| 11 | **Übergänge und PWA-Shell:** View Transitions, bfcache, `viewport-fit=cover`, `dvh`. | 2–3 |
| 12 | **Aufräumen:** Tailwind-CDN, Font Awesome, Google Fonts, `focus-removal.css`, SweetAlert2. | 2–3 |
| 13 | **Abnahme** nach Spec §10 (375×812 hell/dunkel, 1280×800, drei Rollen, 200 % Zoom). | 3–4 |

Summe: 55 bis 76 Arbeitstage (Schätzung).

**Hinweise zu den Schritten:**
- **Schritt 1:** Die Tokens brauchen keinen Node-Build. Das Tailwind-CDN bleibt im alten Layout. Aus `theme-jeunesse.css` wird der Block `data-palette='jeunesse'` (Spec §2). `data-theme` setzt ein Inline-Skript im `<head>` vor dem ersten Paint.
- **Schritt 2:** Entferne `maximum-scale=1.0, user-scalable=no`, ergänze `viewport-fit=cover`, setze `theme-color` als Paar und nie `black-translucent` (Recherche Kap. 9). Den Team-Tab verlinkst du auf das zuletzt benutzte Segment, `/team` gibt es nicht. `/promises/admin` und `/promises/leader` fallen als Seiten weg. Der Push-Link `/promises?rehearsal=ID` (`JmdPush.php`) muss weiter laufen.
- **Schritt 6:** Behebe die unescapte Ausgabe von `location` und `date_formatted` in `probenplan/index.php` (Recherche Kap. 10, Nr. 27).
- **Schritt 12:** Die Recherche nennt 21 Dateien mit SweetAlert2 (Kap. 9). Zähle mit einer Suche nach `Swal.` neu. Native `confirm()`-Aufrufe werden Sheets. Die Panels (`panel-shell.php`) übernehmen nur Tokens (Recherche Kap. 7).

**Bausteine, die wohin gehen:**

| Heute | Neu |
|---|---|
| `sidebar-content.php`, `top-navigation.php` | `.app-header`, `.app-tabbar`, `.app-rail` |
| `rehearsal-card.php` mit `-head`, `rehearsal-header.php`, `promise-button.php`, `rehearsal-infobox.php`, `schedule-timeline.php` | `.rehearsal-card`, `.date-chip`, `.rsvp`, `.status-pill`, `.info-row`, `.agenda` |
| `promises-dashboard-card.php`, `tree-view.php` | `.who-card`, `.kpi-strip` |
| `date-separator.php`, `fab.php`, `rehearsal-add-box.php` | `.date-sep` mit Segment, `.fab` mit Aktions-Sheet |
| `schedule-editor.php`, `infobox-editor.php`, `tag-select.php`, `dynamic-group-selector.php` | Feldzeilen, Picker-Sheets, `.tree` |
| `attendance-member-row.php`, `user-item.php` | `.att-row`, `.person-row` |
| `notification-system.php`, `save-indicator.php`, `help-modal.php` | `.toast`, Untertitel, Sheet „Hilfe & Tipps“ |

`settings-renderer.php` (Auto-Save) und `lazy-section.php` bleiben. Der Push-Vorlauf speichert weiter Minuten, nur die Eingabe wird zu Presets. `calendar-integration-modal.php` ist laut Recherche tot (Kap. 10, Nr. 26).

**Übergänge.** Aktiviere `@view-transition { navigation: auto }` nur hinter `prefers-reduced-motion: no-preference`. Kopfleiste und Tab-Leiste bekommen eigene `view-transition-name`s. Tab-Wechsel bleiben sofort, Push und Zurück gleiten. Erkenne die Navigationsart etwa über `pageswap` und `pagereveal`. Firefox kennt die API nicht, dort laden die Seiten wie bisher. Setze keine `unload`-Listener, damit bfcache greift, und frische RSVP-Zahlen bei `pageshow` auf.

## 9 Offene Punkte

**P2 laut Spec:** Swipe-Aktionen, Pull-to-Refresh, Kanten-Wisch-Zurück, Filter, Mehrfachauswahl, Serie, KI-Import, Fehlzeiten, die Seiten „Rollen & Rechte“ und „Registerstruktur“, einklappbare Sidebar, Anmelde-Modi.

**Offene Fragen:**
1. **Datenschutz:** Namen in „Wer kommt?“ hängen an `can_view_all_section_stats`. Vor einer Freigabe für Mitglieder ist eine Prüfung nötig.
2. **Echtgeräte-Tests** (Recherche Kap. 11): View Transitions in einer installierten iOS-PWA, Safe-Area (WebKit-Bug 301994), Aktionsbuttons im Web-Push.
3. **JOinJMD-WebView:** Tab-Leiste zeigen oder ausblenden (`$_SESSION['embedded_app']`)? Nicht untersucht.
4. **Browser-Untergrenze:** Die Tokens nutzen `oklch()`, `color-mix()` und `linear()`. Ältere Android-WebViews verlieren sonst Farben. Lege eine Mindestversion fest (Recherche Kap. 9: Safari/iOS 17.5, Chrome 120, Firefox 129) oder baue Fallbacks.
5. **FAB oder „+“:** Teste die Erreichbarkeit mit Leitungen.
6. **Passwort vergessen?** Der Spec zeigt den Link. Im Code gibt es keinen Ablauf, nur den Hinweis, die Leitung zu kontaktieren. Backend und Mail sind offen.
7. **Insights-Daten:** Die Anwesenheits-KPI braucht mindestens 10 dokumentierte Proben, bis dahin steht dort „–“.
8. **Plan als Tab:** Prüfe das mit Nutzungsdaten (Recherche Kap. 5).
9. **Recht:** BFSG-Anwendbarkeit und Google-Fonts-Hotlinking sind ungeprüft (Recherche Kap. 11).
10. **Texte:** „Registerleitung“ und „Dirigent“ heißen jetzt „Stimmführung“ und „Leitung“; Hilfe- und Support-Texte brauchen das Update. Formelles „Sie“ (etwa in der Passwortmeldung des Logins) wird zu „du“.

## 10 Runde 3: Disziplin vor Dekoration

Der Vergleich des Prototyps mit der bestehenden App (25.10.) hat gezeigt, dass die alte Liste trotz weniger Funktionen leichter lesbar ist: eine Kartenform, feste Plätze, wenige Akzentfarben. Der Prototyp wirkte überladen, obwohl er weniger zeigt: vier Kartentypen auf einem Screen, wandernder Status, fünf Akzentfarben, Pills und Icon-Kacheln. Daraus folgen diese Regeln, die ab jetzt über den Kapiteln 4 bis 6 stehen:

1. **Eine Kartenform je Liste.** Jede Probe hat dieselbe Anatomie: Datumsblock links (Wochentag, Tag), Zeitzeile, eine graue Meta-Zeile (Ort, Besetzung, Art als Text), Status-Platz rechts. Heute ist dieselbe Karte mit dem Label „Heute“ und einer dünnen Akzentkante, keine Hero-Karte.
2. **Fester Status-Platz.** Offen: zwei Icon-Buttons (Haken, Kreuz), grau umrandet. Beantwortet: der gewählte Button gefüllt in Grün oder Rot, der andere grau. Die Antwort ist direkt in der Liste änderbar. Leitung und Stimmführung sehen an derselben Stelle die Zähler mit Balken.
3. **Frist in der Zeitzeile.** Kleiner grauer Text mit Uhr-Icon („bis Mo 12:00“), nie amber, nie eigene Zeile. Abgesagt: Zeit durchgestrichen plus „Abgesagt“ in Grau.
4. **Ein Akzent.** Markenblau für Datum, aktiven Tab, Links und Primärbutton; Grün und Rot nur für Antworten und Anwesenheit; alles andere Tinte und zwei Grautöne. Register- und Probenart-Farben werden in Listen zu grauem Text.
5. **Keine Pills für Daten.** Chips nur als Bedienelemente (Filter, Segmente). Emoji-Hinweise verschwinden aus Listenkarten; im Detail sind Hinweise Textzeilen mit kleinem grauem Icon.
6. **Überschriften als Text.** Keine Icon-Kacheln, keine getönten Quadrate. Wochenüberschriften bleiben als ruhige graue Labels mit Datumsbereich rechts.
7. **Kopfzeile sparsam.** Titel plus höchstens zwei Icon-Aktionen. Ensemble-Wechsler nur bei mehreren Ensembles. Kein „3 offen“-Chip; die Zahl steht im Tab-Badge und in der ersten Wochenüberschrift.
8. **Dichte und Maß.** Etwa vier Probenkarten je Handy-Screen, 4/8-Raster, Detailinhalt am Desktop maximal 720 px breit, Buttons 44 bis 48 px.
9. **Kniff-Test nach jeder Änderung.** Unscharf gesehen dürfen nur Datumsblöcke und Antwortfarben hervortreten.

Was bleibt: Tab-Leiste unten, Wochengruppierung, Dunkelmodus über Tokens, Zeitleiste im Ablauf, Sheets, die gestrichelte Unterlinie im Editor, die Wortregeln aus `ux-microcopy-regeln.md`. Keine Information fällt weg, sie bekommt einen festen Platz.

Umsetzung: Variante „Klar“ im Scratchpad (`variants/klar`, Port 5186) neben dem Prototyp (Port 5173); Übernahme in `prototype/` nach Freigabe.

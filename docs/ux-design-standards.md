# Interface-Standards Probenplaner

Verbindlich vor jedem Entwurf und jeder Änderung am Prototyp. Rangfolge bei Widerspruch: `ux-owner-regeln.md` (R) > `ux-visual-language.md` (VL) > `ux-microcopy-regeln.md` (MC) > dieses Dokument > `ux-audit-guidelines.md` (AG). Heuristik-IDs (N, NO, SH, T, G, L, W, P) aus `ux-heuristiken.md`.
Rollen: Mitglied, Stimmführung (`leader`), Leitung (`conductor`). Breitenklassen nach AG §2: Compact < 600, Medium 600–839, Expanded 840–1199, Large 1200–1599, Extra-large ≥ 1600 px. Prüfbreiten: 375, 768, 1280, 2000, 3440 px, jeweils Hell und Dunkel (R97).

---

## 1. Informationsarchitektur und Navigation

### 1.1 Ebenen

| Ebene | Was | Beispiele | Steuerung (genau eine) |
|---|---|---|---|
| E1 App-Bereich | Tab | Proben, Plan, Orchester, Profil | Tab-Leiste, Rail oder Seitenleiste |
| E2 Screen | Wurzel eines Tabs oder Unterseite | Proben-Liste, Rückmeldungen, Mitglied, Rollen & Rechte | Titelzeile mit Titel und Zurück |
| E3 Segment | Teilansicht desselben Gegenstands | Anstehend / Vergangen; Übersicht / Anwesenheit / Mitglieder / Verwaltung | Segment am Kopf |
| E4 Objekt | ein Eintrag einer Liste | Probe, Person, Rolle, Ort, Verwaltungsbereich | Zeile oder Karte |
| E5 Detail | alles zu einem Objekt | aufgeklappte Zeile, Bereich rechts, Detailseite | nach Breite, Tabelle 2.3 |

Filter, Suche und Sortierung sind keine Ebene; sie schränken die Liste einer Ebene ein.

**NAV-01** Jede Ansicht gehört genau einer Ebene E1 bis E5 an.
Prüfung: Für jede Route aus `ROUTES` (`js/app.js`) die Ebene in Tabelle 1.2 eintragen; eine Route ohne eindeutige Ebene ist ein Fehler.
Quelle: R36, NO7, AG §1 (Sidebar höchstens zwei Ebenen, tiefer als Split View).

**NAV-02** Pro Ebene gibt es genau eine Navigationsschicht. Gleichzeitig sichtbar sind höchstens die App-Navigation (E1) und ein Segment (E3). Keine zweite Tab-, Segment- oder Navigationsgruppe über, unter oder neben diesen beiden.
Prüfung: Jede Route bei 375, 1280 und 2000 px: Gruppen zählen, die eine Ansicht umschalten (Tabs, Segmente, Navigationsspalten). Mehr als App-Navigation plus ein Segment ist ein Fehler. Filter-Chips zählen nicht, solange sie nur filtern.
Quelle: R42, R09, R50, AG §1.

**NAV-03** Neben der App-Navigation stehen höchstens zwei Inhaltsspalten, die eine Auswahl tragen (Liste und Detail). Was tiefer liegt, öffnet als Unterseite (E2) mit Zurück.
Prüfung: Desktop 2000 px: Spalten zählen, in denen ein Eintrag als gewählt markiert ist; höchstens zwei.
Quelle: AG §1 (Split View), R92, L4.

**NAV-04** Ein Segment hat 2 bis 4 Einträge mit je höchstens 12 Zeichen. Mehr Teilansichten werden eigene Screens oder eine Liste von Objekten.
Prüfung: Alle Segmente auflisten, Einträge und Zeichen zählen.
Quelle: MC §3 (Längenbudget), VL (Segment), L2.

### 1.2 Routen und Eltern

| Route | Ebene | Eltern (Ziel von Zurück ohne Verlauf) |
|---|---|---|
| `/promises`, `/rehearsals`, `/plan`, `/team`, `/profile` | E1/E2 Wurzel | keins |
| `/promises/:id`, `/rehearsals/:id`, `/plan/:id` | E5 | Liste des Tabs, Probe gewählt |
| `/…/:id/edit` | E2 Unterseite | Probe (`/…/:id`) |
| `/…/:id/responses`, `/…/:id/attendance` | E2 Unterseite | Probe (`/…/:id`) |
| `/…/:rid/members/:id` | E2 Unterseite | Rückmeldungen der Probe (`/…/:rid/responses`) |
| `/dashboard`, `/attendance`, `/members`, `/manage` | E3 im Tab Orchester | keins (Segmentwechsel) |
| `/attendance/table` | E2 Unterseite | `/attendance` |
| `/members/:id` | E2 Unterseite | `/members` |
| `/settings`, `/settings/sections`, `/settings/places`, `/settings/roles` | E2 Unterseite | `/manage` |
| `/profile/:page` | E2 Unterseite | `/profile` |
| `/rehearsals/series`, `/rehearsals/import` | E2 Unterseite | `/rehearsals` |

**NAV-05** Jedes Objekt hat genau eine URL; die Breite entscheidet nur die Darstellung (Seite, Aufklappen, Bereich rechts). Bearbeiten hat überall die eigene URL `…/:id/edit`.
Prüfung: Dieselbe Probe bei 375 und 2000 px öffnen; die Hash-URL ist gleich. `#/…/:id/edit` öffnet auf jeder Breite den Editor, `#/…/:id` nie.
Quelle: AG §2 und §5 (Auswahl beim Resize erhalten), T17, R36.

**NAV-06** Deep Links rendern ohne Vorgeschichte vollständig; Zurück führt dann zur Spalte „Eltern“ in Tabelle 1.2.
Prüfung: Jede Route in einem frischen Tab per Hash öffnen und Zurück wählen; das Ziel entspricht der Tabelle.
Quelle: R44, T8.

### 1.3 Tabs, Segment oder Navigation

| Die Steuerung soll … | Steuerung | Nicht |
|---|---|---|
| den App-Bereich wechseln | Tab (E1) | Segment, Dropdown, Kachel im Inhalt |
| zwischen 2–4 gleichrangigen Teilansichten desselben Gegenstands wechseln, Kopf bleibt gleich | Segment (E3) am Kopf | Karten als Tabs, Chips, zweite Tab-Leiste |
| ein untergeordnetes Objekt öffnen | Zeile mit › (Seite oder Bereich rechts) oder ⌄ (Aufklappen) | Segment, Tab |
| eine Liste einschränken, Ansicht bleibt | Filter-Chip oder „Filtern“ (2.5) | Segment |
| einen Wert setzen | Wertzeile, Dropdown oder Wert-Segment (2.4) | Tab, Filter-Chip |
| eine Aktion auslösen | Button | Chip, Tab |

**NAV-07** Tabs, die alle Rollen haben, heißen und stehen für alle Rollen gleich: Proben, Plan, Profil, Profil immer zuletzt. Ein Tab, der Rechte braucht (Orchester), kommt nur bei diesen Rollen dazu, immer vor Profil, und ersetzt oder benennt nie einen gemeinsamen Tab. Kein Tab „Mehr“. Der Orchester-Tab trägt den Ensembletyp aus den Daten (Orchester, Chor, Ensemble).
Prüfung: Als Mitglied, Stimmführung und Leitung bei 375, 840 und 1280 px einloggen; gemeinsame Tabs haben gleiche Namen, Icons und Reihenfolge; der Orchester-Tab steht bei allen Rollen mit Rechten an derselben Stelle.
Quelle: R40, R39, W21, AG §5, MC §3.

**NAV-08** Tab-Leiste, Rail und Seitenleiste entstehen aus einer einzigen Definition (`TABS` in `js/app.js`).
Prüfung: Im Code gibt es genau eine Liste der Navigationsziele, die alle Shells speist.
Quelle: AG §2, AG §5.

**NAV-09** Kein Tab „Mehr“, kein Hamburger-Menü, keine Navigation, die erst eingeblendet werden muss.
Prüfung: Alle Breiten ansehen; jedes Ziel ist ohne Öffnen eines Menüs sichtbar.
Quelle: R39, AG §4 (Hamburger am wenigsten auffindbar).

**NAV-10** Zahlen an Tabs gibt es nur für eigene offene Aufgaben (offene Antworten, nicht erfasste Anwesenheit), als Akzent-Blase. Dieselbe Zahl steht pro Bildschirm höchstens zweimal: am Tab und als Chip im Seitenkopf des Bereichs.
Prüfung: Mitglied mit offenen Antworten bei 2000 px: die Zahl steht nur am Tab und im Chip, in keiner Karte.
Quelle: R08, VL (Zu tun), AG §1 (Badge nur für Wichtiges).

**NAV-11** Der aktuelle Ort ist immer markiert: aktiver Tab (`aria-current="page"`), gewähltes Segment, gewählte Zeile, solange ihr Detail rechts steht.
Prüfung: Jede Route: ein markierter Tab, ein markiertes Segment, bei Bereich rechts eine markierte Zeile.
Quelle: T18, N1, AG §1 (Split View), W28.

### 1.4 Räumliche Zuordnung

**NAV-12** Eine Steuerung steht über oder links neben dem Inhalt, den sie verändert, im selben sichtbaren Element oder direkt angrenzend. Zwischen Steuerung und Inhalt liegt kein fremder Inhalt.
Prüfung: Für jede Steuerung einen Pfeil zum betroffenen Inhalt ziehen; er zeigt nach unten oder rechts und kreuzt kein anderes Element.
Quelle: NO4, G1, AG §1 (Wichtiges oben und am Leading-Rand).

**NAV-13** Die Kopfzeile eines Screens (Titel, Aktionen) ändert sich nie mit Segment, Filter oder Auswahl. Segmentbezogene Aktionen stehen im Kopf des Segmentinhalts. Einzige Ausnahme ist der Auswahlmodus, der die Aktionen durch Anzahl und „Fertig“ ersetzt.
Prüfung: Alle Segmente und Filter durchschalten; die Kopfzeile ist pixelgleich.
Quelle: R45, W20, AG §3 (gleiche Aktion am gleichen Ort).

**NAV-14** Eine Aktion hat auf allen Breiten dieselbe Lage relativ zu ihrem Inhalt: Kopf bleibt Kopf, Zeile bleibt Zeile, Segmentinhalt bleibt Segmentinhalt. Nur die Hauptaktion eines Erfassungs-Screens darf mobil in eine Leiste am unteren Rand wandern.
Prüfung: Jede Aktion bei 375 und 1280 px suchen; Lage relativ zum Inhalt gleich, sie fehlt in keinem Zustand, in dem sie auf der anderen Breite da ist.
Quelle: AG §5, AG §3, T9.

### 1.5 Eltern, Zurück, Gruppenpfad

**NAV-15** Jeder Screen unterhalb der Tab-Wurzel hat oben links eine Zurück-Steuerung: mobil Chevron und „Zurück“ oder der Name des Eltern-Screens (höchstens 15 Zeichen), am Desktop Chevron mit `aria-label` und Tooltip „Zurück zu <Eltern>“.
Prüfung: Jeden Unterscreen ohne Browser-Zurück verlassen; die Steuerung ist oben links und führt zum Elternobjekt.
Quelle: R44, T8, MC §3.

**NAV-16** Zurück führt zum vorigen Screen, wenn er im Verlauf liegt, sonst zum Eltern-Eintrag. Querverweise in einen anderen Bereich öffnen das Ziel unter dem Präfix des aktuellen Tabs (wie `/promises/:rid/members/:id`), damit Zurück im Tab bleibt.
Prüfung: Proben › Rückmeldungen › Person › Zurück › Zurück: Rückmeldungen, dann Probe; der aktive Tab wechselt nie.
Quelle: R44, T8, L3.

**NAV-17** Brotkrumen als Navigation gibt es nicht; die Tiefe E1–E5 braucht nur Zurück. Der Gruppenpfad „Übergruppe · Stimme“ (z. B. „Streicher · Violine 2“) ist ein Wert und steht überall, wo eine Stimme ohne sichtbaren Registerkopf erscheint: Wertzeile Stimme in Profil und Mitglied, Kopf von Rückmeldungen und Anwesenheit, Scope-Chip. Trenner ist „ · “; „›“ ist der Navigation vorbehalten.
Prüfung: Alle Stellen mit einem Stimmnamen auflisten; ohne sichtbaren Registerkopf steht der Pfad, nirgends „›“ als Trenner.
Quelle: Owner-Lob Gruppenpfad „Streicher · Violine 2“ (noch nicht in `ux-owner-regeln.md`), R53, VL (› = andere Seite), R106.

**NAV-18** Keine Sackgassen: Jeder Screen in jedem Zustand (leer, nicht gefunden, kein Zugriff, Fehler) bietet einen Weg weiter oder zurück. Editoren haben auf jeder Breite Zurück oder Abbrechen.
Prüfung: Jede Route in jedem Zustand öffnen; mindestens eine sichtbare Steuerung führt weg, ohne Browser-Zurück.
Quelle: R44, N3, T8, MC §1 (Ausnahme 4).

**NAV-19** Zustand bleibt erhalten: Segment, Filter, Suchbegriff, Scrollstand, Auswahl und aufgeklappte Zeile überstehen Detail und Zurück sowie den Tabwechsel. Erneutes Tippen auf den aktiven Tab scrollt nach oben.
Prüfung: Liste filtern, scrollen, Zeile aufklappen, Detail öffnen, zurück, Tab wechseln und zurück; alles steht wie vorher.
Quelle: R37, T17, SH7.

**NAV-20** Die Orchesterwahl ist ein echtes Menü. Desktop: Kopf der Seitenleiste. Mobil: in derselben Zeile wie der Seitentitel, nie als eigene Zeile darüber oder darunter.
Prüfung: 375 px: Zeilen zwischen Statusleiste und erstem Inhalt zählen; Titel und Orchesterwahl teilen eine Zeile.
Quelle: R46, R07, R29, R89.

**NAV-21** Ein Detail-Screen trägt den Namen des Objekts als Titel (Probe: Datum „Sa 24.10.“, Person: Name), keinen Gattungstitel wie „Mitglied“ mit dem Namen als zweiter Überschrift.
Prüfung: Alle Detail-Screens: genau eine Überschrift, und sie nennt das Objekt.
Quelle: MC §3, R07, W13.

### 1.6 Wo Einstellungen liegen

| Gilt für | Einziger Ort | Inhalte |
|---|---|---|
| mich selbst | Profil | Konto, eigene Stimme, selbst wählbare Rollen, Erinnerungen, Kalender-Abo, Darstellung, App installieren, Orchester verlassen |
| das ganze Ensemble | Orchester › Verwaltung | Einstellungen (Name, Typ, Standardort, Standardbesetzung, Saison), Einladungslink, Registerstruktur, Orte, Rollen & Rechte |
| eine andere Person | Orchester › Mitglieder › Person | Stimme, Rollen, Passwort zurücksetzen, Entfernen |
| eine Probe | Editor der Probe | Zeit, Ort, Art, Farbe, Besetzung, Frist, Ablauf, Hinweise, Tags, Absagen |

**NAV-22** Jede Einstellung hat genau einen Ort nach Tabelle 1.6. Andere Screens dürfen dorthin verlinken (z. B. „Rollen verwalten“, Zahnrad in Mitteilungen), aber die Steuerung nicht wiederholen.
Prüfung: Jede Einstellung suchen; die Steuerung existiert einmal, alle anderen Fundstellen sind Links zu ihr.
Quelle: R41, R106, W22.

**NAV-23** Neue Einträge einer Auswahlliste (Ort, Art, Register, Raum) dürfen dort angelegt werden, wo ausgewählt wird; sie landen in derselben Liste wie in der Verwaltung.
Prüfung: Ort im Editor anlegen; er erscheint unter Verwaltung › Orte.
Quelle: R31, R132.

**NAV-24** Wo Rollen oder Stimmen angezeigt werden und die Person sie verwalten darf, führt ein sichtbarer Weg in höchstens zwei Klicks ohne Scrollen zu ihrer Verwaltung.
Prüfung: Von Mitgliederliste, Mitglied und Profil aus „Rollen & Rechte“ erreichen; Klicks zählen.
Quelle: R43.

---

## 2. Komponenten und Interaktionsmuster

### 2.1 Komponentenkatalog

| Bedarf | Komponente | Code |
|---|---|---|
| Teilansicht umschalten | Segment | `ui.segmented` |
| Wert aus 2–4 kurzen Optionen | Wert-Segment | `ui.segmented` als `radiogroup` |
| Liste filtern | Filter-Chip, Chip-Reihe | `ui.chip`, `ui.chipRow` |
| einzelner einstellbarer Wert | Wertzeile „Label · Wert ›“ | `valueRow` (`js/value-row.js`) |
| Wahl aus kurzem Menü am Auslöser | Dropdown-Menü | `menuSelect`, `openMenu` (`js/menu.js`) |
| Wahl aus langer oder gruppierter Liste | Auswahl-Sheet | `ui.openPicker`, `openVoiceSheet` |
| Formular, Teilaufgabe | Sheet, Dialog oder Popover | `openSheet` (`js/app.js`) |
| Bestätigung | Bestätigungs-Sheet | `ui.openConfirmSheet` |
| vorübergehende Meldung | Toast | `toast` (`js/app.js`) |
| leerer Zustand | Leer-Element | `ui.emptyState` |
| eigene Antwort | zwei Antwortquadrate | `answerControl` (Zeile), `rsvpBar` (mobil) in `js/rsvp.js` |
| Anwesenheit | Haken/Kreuz-Schalter | `ui.attToggle` |
| Rolle | Rollen-Chip | `ui.roleChip` |
| Probe in einer Liste | Probenzeile, Probenkarte | `probenRow` (`js/rsvp.js`), `ui.rehearsalCard` |
| Abschnittskopf | Abschnittskopf mit Zahl rechts | `ui.sectionHeader` |
| sofort wirkendes Ein/Aus | Schalter | `.switch` (`role="switch"`) |
| Mehrfachwahl | Checkbox | `role="checkbox"` (Besetzung, Auswahlmodus) |

**KOM-01** Neue Oberfläche nutzt die Komponente aus Tabelle 2.1. Eine zweite Implementierung desselben Musters (eigenes Menü, eigenes Popover, eigene Bestätigung) ist verboten; fehlt etwas, wird die Katalog-Komponente erweitert.
Prüfung: Code nach `role="menu"`, `role="dialog"`, eigenen Popover-Klassen und Bestätigungen außerhalb der Katalog-Dateien durchsuchen; kein Treffer.
Quelle: R106, R110, SH1, VL.

### 2.2 Tabs, Dropdown, Segment oder Filter-Chips

| Situation | Steuerung | Zustand sichtbar | Nicht |
|---|---|---|---|
| App-Bereich | Tab | aktiver Tab gefüllt | |
| 2–4 Teilansichten, gegenseitig exklusiv, Labels ≤ 12 Zeichen | Segment am Kopf (`tablist`) | gewählter Eintrag mit Fläche `--segment-thumb` | Dropdown, Chips |
| Liste einschränken, bis 6 Optionen, Zahl hilft | Filter-Chips | gewählt gefüllt, ungewählt neutral | Segment |
| Liste einschränken, mehr als 6 Optionen oder kombinierbar | Button „Filtern“ mit Popover (Desktop) oder Sheet (mobil), aktive Filter als Chips über der Liste | Zahl aktiver Filter am Button | lange horizontale Chip-Reihe |
| Wert aus 2–4 kurzen Optionen in einer Einstellung | Wert-Segment in der Zeile (`radiogroup`) | gewählter Eintrag mit Fläche | Dropdown |
| Wert aus mehr als 4 Optionen oder langen Labels | Wertzeile › Auswahl-Sheet, am Desktop Popover; kurze Menüs als Dropdown | Wert in der Zeile | Segment |
| hierarchische Auswahl (Stimmen) | Auswahl-Sheet mit Übergruppen | Haken am gewählten Eintrag | flache Liste, Chips |
| Objekt im Screen wechseln (Anwesenheit: Probe) | Dropdown am Kontext plus ‹ › für die Nachbarn | Objekt im Auslöser | Segment |

**KOM-02** Die Steuerung folgt Tabelle 2.2.
Prüfung: Jede Auswahlsteuerung einer Zeile der Tabelle zuordnen; passt sie nicht, ist sie falsch gewählt.
Quelle: T10, L2, R24, R53, VL.

**KOM-03** Ein Segment, das die Ansicht schaltet, steht am Kopf seines Inhalts und ist `role="tablist"` mit `tabpanel`. Ein Wert-Segment steht in einer Einstellungs- oder Formularzeile und ist `role="radiogroup"`. Die Lage unterscheidet beide.
Prüfung: Alle Segmente: Lage und ARIA-Rolle passen zusammen.
Quelle: VL (Segment), R24, R50, W28, T10.

**KOM-04** Ein Chevron nach unten steht nur an einem Element, das ein Menü öffnet oder an Ort und Stelle aufklappt. Jedes Element, das ein Menü öffnet, trägt ihn.
Prüfung: Alle ⌄ anklicken: Menü oder Aufklappen; alle Menü-Auslöser haben ⌄.
Quelle: R29, R20, VL (⌄).

**KOM-05** Gleich aussehende Chips haben dieselbe Funktion: ein Chip filtert. Was ein Sheet öffnet oder etwas sendet, ist ein Button und sieht wie einer aus.
Prüfung: Alle Chips anklicken; jeder schaltet einen Filter.
Quelle: G2, T4, VL (Filter-Chip).

### 2.3 Overlay, Bereich, Aufklappen oder Seite

Form nach Aufgabe und Breite. „Fein“ = `(min-width: 840px) and (pointer: fine)`; Touch-Geräte ab 840 px nutzen die Spalte 600–839.

| Aufgabe | < 600 | 600–839 oder Touch | ≥ 840 fein |
|---|---|---|---|
| kurze Wahl (≤ 7 Einträge, ohne Zusatzinfo) | Dropdown-Menü am Auslöser | Dropdown-Menü | Dropdown-Menü |
| längere oder gruppierte Wahl, Wert mit Eingabe | Bottom Sheet | Bottom Sheet (max. `--sheet-max-w`) | Popover am Auslöser (400 px) |
| kurzes Formular (Ort, Rolle anlegen, Grund) | Bottom Sheet | Bottom Sheet | Dialog zentriert (`--dialog-w`) |
| Bestätigung irreversibel oder ausgehend | Bottom Sheet | Bottom Sheet | Dialog zentriert |
| mehrschrittige Aufgabe (Serie, KI-Import, neue Probe) | eigene Seite | eigene Seite | eigene Seite |
| Detail eines Listenobjekts | Seite | Seite | Aufklappen; Bereich rechts, sobald die Liste daneben ≥ 1100 px bleibt |
| Zusatzinfo zu einem Element (Rollenname, genaue Frist) | sichtbarer Text oder Antippen → Popover | wie < 600 | Tooltip bei Hover und Fokus, zusätzlich sichtbarer Text im Detail |
| Ergebnis einer Aktion | Toast | Toast | Toast |

**KOM-06** Die Form folgt Tabelle 2.3. Die Entscheidung trifft eine zentrale Abfrage (`openSheet`, `ui.isWide`), kein Screen entscheidet selbst.
Prüfung: Jedes Overlay bei 375, 768 (Touch) und 1280 px öffnen; Form wie in der Tabelle. Code: keine eigenen `matchMedia`-Schwellen in Screens.
Quelle: AG §2 (List-Detail, Supporting Pane), P10, R106.

**KOM-07** Jedes Overlay hat Scrim (außer Menü und Popover), Schließen-Button, schließt mit Esc und Klick daneben, setzt den Hintergrund `inert`, fokussiert beim Öffnen das erste Feld (bei Bestätigungen „Abbrechen“) und gibt den Fokus beim Schließen an den Auslöser zurück.
Prüfung: Per Tastatur öffnen, Tab bleibt im Overlay, Esc schließt, Fokus steht wieder auf dem Auslöser.
Quelle: W11, W12, P2, G6.

**KOM-08** Ein Sheet ersetzt ein anderes, statt sich darüber zu stapeln; mehr als eine Ebene Overlay gibt es nicht.
Prüfung: Aus jedem Sheet heraus weitere Sheets öffnen; nie zwei Scrims übereinander.
Quelle: P10.

**KOM-09** Aufklappen fügt nur hinzu. Was die zugeklappte Zeile zeigt (Zeit, „Jetzt …“, Hinweis, Zähler, Probenart), erscheint im aufgeklappten Teil nicht noch einmal in anderer Form.
Prüfung: Jede Zeilenart auf- und zuklappen; jede Größe kommt im aufgeklappten Zustand genau einmal vor.
Quelle: R08, VL (pro Ansicht und Zustand einmal).

**KOM-10** Zeile und aufgeklappter Teil bilden eine Karte mit durchgehendem Radius und einem Schatten; der Übergang läuft über `--dur-base`.
Prüfung: Aufklappen in Zeitlupe; keine Kerben, kein zweiter Schatten, kein Sprung.
Quelle: VL (Formen), R111, R105.

**KOM-11** Pro Liste ist höchstens eine Zeile aufgeklappt; der Kopf der geöffneten Zeile bleibt an seiner Bildschirmposition, der Fokus bleibt auf dem Auslöser.
Prüfung: Zeile A offen, Zeile B öffnen: A schließt, B bleibt an ihrer y-Position (±2 px), Fokus auf B.
Quelle: R37, G7, W12.

**KOM-12** Im aufgeklappten Teil und im Bereich rechts stehen alle Aktionen des Objekts sichtbar (Desktop: kein Menü), dazu der Ablauf.
Prüfung: Heutige Probe am Desktop aufklappen; Erfassen, Bearbeiten, Duplizieren, Teilen, Absagen, Löschen und Ablauf sichtbar.
Quelle: R90, R94.

**KOM-13** Der Bereich rechts zeigt dieselbe Detail-Komponente wie das Aufklappen; aus der Zeile wiederholt er nur Termin-Anker und Zeit als Bezug.
Prüfung: Gleiche Probe bei 1280 px aufgeklappt und bei 2000 px rechts nebeneinanderlegen; gleicher Inhalt, gleiche Reihenfolge.
Quelle: R106, R08, AG §2.

### 2.4 Checkbox, Radio, Schalter, Segment oder Button

| Bedürfnis | Steuerung | Wirkung |
|---|---|---|
| Einstellung an/aus, wirkt sofort | Schalter | sofort, ohne Speichern |
| Ja/Nein in einem Formular, das mit „Anlegen“ oder „Speichern“ endet | Checkbox | erst mit dem Abschluss |
| mehrere aus einer Liste | Checkbox-Zeilen, bei Hierarchie mit gemischtem Zustand | sofort im Entwurf |
| genau eins aus 2–4 kurzen Werten | Wert-Segment | sofort |
| genau eins aus mehr als 4 | Radio-Liste im Sheet, Haken rechts | sofort, Sheet bleibt bis „Fertig“ oder schließt bei Einzelwahl |
| eigene Antwort zu einer Probe | zwei Antwortquadrate ohne Text | sofort, optimistisch |
| Anwesenheit einer Person | Haken/Kreuz-Schalter | sofort |
| etwas tun | Button | Aktion |

**KOM-14** Die Steuerung folgt Tabelle 2.4. Ein Schalter löst nie eine Aktion aus und steht nie in einem Formular mit Abschluss-Button; ein Button trägt nie einen Zustand.
Prüfung: Alle Schalter: Wirkung tritt ohne weiteren Klick ein. Alle Formulare mit Abschluss: keine Schalter.
Quelle: T10, T4, R18, R122.

**KOM-15** Exklusive Wahl hat Radio-Semantik (`role="radio"`, `aria-checked`) mit Pfeiltasten und einem Tab-Stopp pro Gruppe; Mehrfachwahl hat `aria-pressed` oder `role="checkbox"`.
Prüfung: Jede Gruppe per Tastatur: Tab erreicht die Gruppe einmal, Pfeile wechseln die Wahl.
Quelle: W28, W10, T10.

**KOM-16** Ein gewählter Zustand wirkt nie ausgeblendet oder deaktiviert. Was die Person nicht ändern darf, zeigt ein Schloss am Ende, Text voll lesbar; Antippen nennt, wer es ändern darf.
Prüfung: Gewählte Rollen, Emojis, Segmente im Graustufen-Screenshot: eindeutig „gewählt“. Gesperrte Felder: Schloss, kein Ausgrauen.
Quelle: R27, VL (Gesperrt), N10.

### 2.5 Filter, Suche, Sortierung

**KOM-17** Filter stehen direkt über der Liste, die sie filtern, links und nach dem Segment; am Desktop in der Titelzeile, mobil in der ersten Zeile unter dem Titel.
Prüfung: Jede gefilterte Liste: zwischen Filter und Liste liegt kein anderer Inhalt.
Quelle: NAV-12, NO4, G1.

**KOM-18** Bis 6 Optionen stehen als Chips sichtbar; mehr oder kombinierbare Filter liegen hinter „Filtern“. Aktive Filter aus dem Popover erscheinen als entfernbare Chips über der Liste und als Zahl am Button.
Prüfung: Jeden Filter setzen; der aktive Zustand ist ohne Öffnen eines Menüs sichtbar.
Quelle: N1, N6, T6.

**KOM-19** Gewählter und ungewählter Chip unterscheiden sich in Fläche und Textfarbe; der Unterschied bleibt im unscharfen Graustufen-Screenshot erkennbar.
Prüfung: Chip-Reihe mit gewähltem Chip, Gaußunschärfe 4 px, Graustufen.
Quelle: R25, W3.

**KOM-20** Einfachfilter beginnen mit „Alle …“ (Menge benannt, z. B. „Alle Mitglieder“); Mehrfachfilter haben „Zurücksetzen“. Eine leere Trefferliste bietet „Filter zurücksetzen“ oder „Suche leeren“.
Prüfung: Filter so setzen, dass nichts übrig bleibt; ein Klick stellt die volle Liste her.
Quelle: R54, N3, MC §1 (Ausnahme 4).

**KOM-21** Die Zahl an einem Chip ist die Zahl der Einträge, die nach Aktivierung erscheinen. Dieselbe Menge hat überall dieselbe Zahl; verschiedene Mengen tragen verschiedene Namen („56 Mitglieder“, „53 eingeladen“).
Prüfung: Chip aktivieren und Zeilen zählen; alle Zahlen für Mitglieder auf allen Screens vergleichen.
Quelle: R13, T2, NO7.

**KOM-22** Ein Filter wirkt nur auf die Liste direkt darunter und bleibt bis zum Zurücksetzen oder Abmelden erhalten (NAV-19).
Prüfung: Filtern, Tab wechseln, zurückkehren; Filter und Ergebnis unverändert.
Quelle: T17, SH7.

**KOM-23** Die Reihenfolge ist fachlich fest: anstehende Proben nächste zuerst, vergangene neueste zuerst, Mitglieder nach Registerstruktur. Nutzersortierung gibt es nur in Desktop-Tabellen über klickbare Spaltenköpfe mit Pfeil.
Prüfung: Listen prüfen; Sortiersteuerung nur in Tabellen mit Spaltenköpfen.
Quelle: N2, L3, AG §1 (Tabellen mit sortierbaren Spalten).

**KOM-24** Die Suche steht auf Höhe des Titels; Treffer erscheinen beim Tippen in unter 400 ms; Esc leert das Feld.
Prüfung: Proben, Mitglieder, Rückmeldungen bei 375 und 1280 px.
Quelle: R49, L5.

### 2.6 Listen und Tabellen

| Breite | Darstellung | Zeile |
|---|---|---|
| < 600 | Karten, eine Spalte | Karte nach VL, Antwort rechts |
| 600–839 | Karten, eine Spalte, max. `--content-max-w` | wie < 600 |
| ≥ 840 | tabellenartige Zeilen mit festen Spalten | Anker · Zeit · Probenart/Besetzung · Ort · Hinweise · Status-Slot · Affordanz |
| ≥ 840, Matrix (Fehlzeiten, Rechte) | echte Tabelle (`table`, `th`), sortierbare Spaltenköpfe | Namensspalte fixiert |
| < 840, Matrix | Tabelle scrollt in sich, angeschnittene letzte Spalte, Namensspalte fixiert | |

**KOM-25** Listen und Tabellen folgen der Tabelle oben.
Prüfung: Proben, Plan, Mitglieder, Anwesenheit, Fehlzeiten bei 375 und 1280 px.
Quelle: R88, R61, R26, W4, W9, AG §3 (Einträge werden Zeilen mit Spalten).

**KOM-26** Zeilenanatomie ist fest: Anker links, Hauptwert, Meta-Spalten, Status-Slot fester Breite, Affordanz am rechten Rand. Dieselbe Datenart steht in allen Zeilen einer Liste auf derselben x-Position; der Status-Slot ändert nie seine Größe.
Prüfung: x-Position von Pin, Probenart und Status-Slot in allen Zeilen messen; Abweichung höchstens 1 px.
Quelle: R102, R103, R104, G4, VL.

**KOM-27** Jede Spalte einer Zeile ist eine Textzeile; abgeschnitten wird mit „…“, der Volltext steht im Detail.
Prüfung: Lange Ortsnamen, Notizen und Hinweise einsetzen; keine Zeile bricht um.
Quelle: MC §3 (abschneiden statt umbrechen), R66.

**KOM-28** Eine navigierende Zeile trägt › am rechten Rand, eine aufklappende ⌄, eine nicht interaktive keinen Chevron und keinen Hover. Mobile Probenkarten tragen keinen Chevron, weil der rechte Rand dem Antwort-Slot gehört; Kartenform und Druckzustand sind dort der Hinweis.
Prüfung: Jede Zeile anklicken; Chevron und Verhalten stimmen überein, Chevron sitzt bündig am rechten Rand des Elements.
Quelle: R20, R23, R62, R102, VL (›, ⌄).

**KOM-29** Einfachauswahl (Bereich rechts) zeigt `--surface-selected` ohne Größenänderung. Mehrfachauswahl gibt es nur im Auswahlmodus: Checkbox links, Leiste mit Anzahl und Aktionen, Esc beendet.
Prüfung: Auswahl setzen; Zeilenhöhe gleich. Auswahlmodus öffnen und mit Esc verlassen.
Quelle: AG §1 (Auswahl markieren), N7, T10.

### 2.7 Aktionen platzieren

| Aktion gilt für | Ort |
|---|---|
| den ganzen Screen (Neue Probe, Drucken, Suche, Filtern, Auswählen) | Titelzeile rechts, über Segmente unverändert |
| den Inhalt eines Segments (Erinnern an Offene, Vorschläge übernehmen) | Kopf des Segmentinhalts |
| ein Objekt (Erfassen, Bearbeiten, Duplizieren, Teilen, Absagen, Löschen) | Kopf des Details: Hauptaktion als Button mit Text links, weitere als Icon-Buttons, zerstörerische rechts abgesetzt |
| eine Gruppe (Register erinnern) | Gruppenkopf rechts |
| mehrere ausgewählte Objekte | Leiste des Auswahlmodus mit Anzahl |

**KOM-30** Aktionen stehen nach der Tabelle oben. Pro Ansicht gibt es höchstens einen primären Button (Akzent-Verlauf); zwei gleichwertige Optionen bekommen beide den Sekundärstil.
Prüfung: Jede Ansicht: Primärbuttons zählen (≤ 1); Kalender-Abo zeigt zwei gleich gewichtete Optionen.
Quelle: VL (Primärer Button), R38, L7, AG §3.

**KOM-31** Am Desktop liegt keine Aktion hinter „···“. Menüs dienen der Auswahl (Typ der neuen Probe), nicht dem Verstecken von Aktionen.
Prüfung: Desktop bei 1280 und 2000 px nach `ellipsis` absuchen; kein Treffer.
Quelle: R90, R48, NO1.

**KOM-32** Zerstörerische Aktionen sind rot, stehen am Ende abgesetzt, tragen nie den Akzent-Verlauf und sind nie Enter-Standard.
Prüfung: Alle Löschen-, Entfernen-, Absagen-Aktionen und ihre Bestätigungen ansehen.
Quelle: VL, MC §4, P2, T4.

**KOM-33** Ausgehende Aktionen (Push, Mail, Link zurücksetzen) sehen anders aus als Kopieren und nennen vor dem Senden Anlass und Empfängerzahl.
Prüfung: Erinnern auslösen; das Sheet nennt Probe und Anzahl, der Button „An 6 senden“.
Quelle: R28, MC §4.

**KOM-34** Mobil stehen höchstens drei Icon-Aktionen in der Kopfzeile. Am Desktop tragen Werkzeugbuttons ab 1200 px Icon und Text; darunter dürfen Suche, Filter und Drucken als Icon mit Tooltip stehen.
Prüfung: Kopfzeilen aller Tabs bei 375, 1000 und 1280 px zählen.
Quelle: AG §3 (Label neben Icon bei großen Fenstern), AG §4 (Icons brauchen Labels), L2.

**KOM-35** Icons kommen ausschließlich aus Lucide; Textzeichen (‹ › ✓ ✕ ↗ →) dienen nie als Icon.
Prüfung: Templates nach diesen Zeichen durchsuchen; nur Fließtext-Vorkommen sind erlaubt.
Quelle: R115, AG §1 (Icons gleicher Strich und Größe).

### 2.8 Interaktionszustände

| Zustand | Pflicht für | Darstellung | Tokens |
|---|---|---|---|
| Standard | alle | Form nach VL | |
| Hover | alles Klickbare, nur bei `(hover:hover) and (pointer:fine)` | Fläche heller oder dunkler, Cursor Hand | `--fill-hover`, `--control-fill-hover` |
| Fokus sichtbar | alles Fokussierbare | Ring 2 px, Abstand 2 px | `--focus-ring` |
| Gedrückt | alles Klickbare | Fläche plus leichte Skalierung | `--fill-pressed`, `--press-scale` |
| Gewählt | Segment, Chip, Tab, Radio, Zeile, Antwort | Fläche oder Füllung, nie verblasst | `--surface-selected`, `--accent`, `--status-*-bg-strong` |
| Gesperrt | Felder ohne Recht | Schloss am Ende, Text voll | VL Gesperrt |
| Deaktiviert | nur Abschluss-Buttons bis Pflichtfelder gefüllt | Fläche reduziert, Text ≥ 4,5:1 | |
| Lädt | Aktionen über 400 ms, Listen beim ersten Laden | Button `.is-loading` mit `aria-busy`, Liste als Skeleton in Kartenform | |
| Fehler | Eingaben, Speichern | Text unter dem Feld, `aria-invalid`, Rand nicht allein | `--status-no-fg` |
| Leer | Listen, Abschnitte | `ui.emptyState`: Icon, Titel, höchstens ein Satz, ein Button | |

**KOM-36** Jede interaktive Komponente definiert alle Zustände der Tabelle 2.8, die für sie gelten.
Prüfung: Zustandsmatrix je Komponente (Segment, Chip, Schalter, Checkbox, Wertzeile, Antwortquadrate, Anwesenheitsschalter, Zeile, Menüpunkt, Eingabe) mit CSS-Selektor füllen; keine Lücke.
Quelle: R88 (Hover), N1, NO5, W14, P5.

**KOM-37** Hover ändert nie Layout oder Größe und gibt es nicht auf Touch.
Prüfung: Hover über jede Zeile; Nachbarn verschieben sich nicht.
Quelle: G7, R95.

**KOM-38** Der Fokusring wird nie entfernt und nie von Kopfzeile, Tab-Leiste oder Toast verdeckt.
Prüfung: Mit Tab durch eine lange Liste bis zur letzten Zeile; Ring immer ganz sichtbar.
Quelle: W14, W15.

**KOM-39** Zielgrößen: Touch mindestens 44 × 44 px (`--touch`), Zeiger mindestens 24 × 24 px; Antwortquadrate haben mindestens 8 px Abstand.
Prüfung: Chevrons, Icon-Buttons, Chips und Schalter in DevTools messen.
Quelle: W16, T9, N5, AG §3.

### 2.9 Rückmeldung und Timing

| Zeit nach Klick | Pflicht |
|---|---|
| ≤ 100 ms | gedrückter Zustand sichtbar (Ziel 50 ms) |
| ≤ 400 ms | Ergebnis sichtbar; lokale Änderungen optimistisch |
| > 1 s | Ladeanzeige am Ort des Ergebnisses (Skeleton, Button lädt) |
| > 10 s | Fortschritt und „Abbrechen“ (KI-Import) |

**KOM-40** Zeiten nach Tabelle oben; eine laufende Aktion ignoriert weitere Klicks.
Prüfung: Netz auf „Slow 4G“ drosseln; Antwort, Speichern, Import ausführen, Doppelklick testen.
Quelle: L5, T11, P5.

**KOM-41** Antwort, Anwesenheit, Schalter und Editorfelder ändern sich sofort. Scheitert das Speichern, springt der Zustand zurück und ein Toast meldet „Nicht gespeichert“ mit „Erneut versuchen“.
Prüfung: Netz trennen, antworten; Zustand springt zurück, Toast mit Aktion.
Quelle: N1, N9, T14.

| Aktion | Behandlung | Beispiele |
|---|---|---|
| häufig, umkehrbar, Wirkung an Ort und Stelle sichtbar | sofort, kein Toast | Antwort, Anwesenheit, Schalter |
| umkehrbar, Wirkung außer Sicht oder für andere | sofort, Toast mit „Rückgängig“ (5 s) | Probe absagen, Stimme ändern, Mitglied verschieben, Feldänderung im Editor, Probe ohne Antworten löschen |
| irreversibel | Bestätigung, kein Rückgängig | Serie löschen, Probe mit Antworten löschen, Konto löschen (getippte Bestätigung) |
| ausgehend | Bestätigung mit Empfängerzahl | Erinnerung senden, Link zurücksetzen, Absage mit Benachrichtigung |

**KOM-42** Rückgängig oder Bestätigung nach der Tabelle oben, nie beides für dieselbe Aktion.
Prüfung: Jede zerstörerische Aktion auslösen; entweder Bestätigung oder Rückgängig, nicht beides.
Quelle: MC §4, P1, P4, SH6, W26.

**KOM-43** Bestätigungen entstehen aus `ui.openConfirmSheet`: Titel als Frage mit Objekt („Probe am Di 27.10. löschen?“), Buttons mit dem Ergebnis und „Abbrechen“ an fester Stelle, Fokus auf „Abbrechen“.
Prüfung: Alle Bestätigungen nebeneinanderlegen; gleicher Aufbau, Fokus beim Öffnen auf „Abbrechen“.
Quelle: MC §4, P2, R106.

**KOM-44** Dauer und Kurve jeder Bewegung kommen aus Tokens: `--dur-fast` für Zustände, `--dur-base` für Aufklappen und Sheets, `--ease-standard` oder `--ease-sheet`. Reduzierte Bewegung wirkt über die Tokens.
Prüfung: Code nach `ms` und `s` in `transition`/`animation` außerhalb von `tokens.css` durchsuchen; kein Treffer. Mit `data-motion="reduced"` aufklappen: ohne Bewegung, Zustand erkennbar.
Quelle: P8, R111, R135.

**KOM-45** Es gibt höchstens einen Toast gleichzeitig; ein neuer ersetzt den alten. Dauer `--dur-toast`, mit Aktion `--dur-undo`. Der Toast liegt in `role="status"` und verdeckt weder Tab-Leiste noch Fokus.
Prüfung: Drei Antworten schnell nacheinander zurücknehmen; ein Toast, Ziel des Rückgängig ist die letzte.
Quelle: P9, W29, W15.

### 2.10 Tastatur am Desktop

| Taste | Wirkung |
|---|---|
| Tab / Umschalt+Tab | Fokus in Lesereihenfolge: Titelzeile, Segment, Filter, Liste, Bereich rechts |
| ↑ / ↓ | in Listen mit Bereich rechts: Auswahl; in Menüs und Radiogruppen: Eintrag |
| ← / → | in Segmenten und Wert-Segmenten: Eintrag wechseln |
| Pos1 / Ende | erster / letzter Eintrag in Menü, Segment, Liste |
| Enter / Leertaste | Element auslösen, Zeile aufklappen oder öffnen |
| Esc | oberstes Overlay schließen; sonst Suche leeren; sonst Auswahlmodus beenden |
| Alt+← | Zurück |
| / | Suchfeld fokussieren (nicht in Eingabefeldern) |

**KOM-46** Alles ist per Tastatur bedienbar, mit den Tasten der Tabelle oben.
Prüfung: Antworten, Aufklappen, Segment, Filter, Sheet, Rolle vergeben, Anwesenheit ohne Maus.
Quelle: W10, W12, N7, R88.

**KOM-47** Jede Zeiger- oder Gesteninteraktion (Langdruck, Wischen, Hover-Tooltip im Sitzplan) hat eine sichtbare Steuerung oder eine Tastaturentsprechung mit gleichem Ergebnis.
Prüfung: Jede Geste auflisten und ihre Alternative auslösen.
Quelle: W17, W7, P11.

**KOM-48** Einzeltasten-Kürzel wirken nie in Eingabefeldern; Kürzel mit Modifikator dürfen Browser-Standards (Strg+Z, Strg+F) nicht überschreiben.
Prüfung: In jedem Feld „/“ tippen; das Zeichen landet im Feld.
Quelle: WCAG 2.2 SC 2.1.4, P4.

---

## 3. Visuelle Grundlagen

### 3.1 Typografie

Schrift ist der Systemstapel `--font-sans`, Zahlen-Kennwerte `--font-rounded`. Gewichte nur 400, 500, 600, 700.

| Rolle | Token | Größe/Zeile, Gewicht | Verwendung | VL |
|---|---|---|---|---|
| Seitentitel | `--text-large-title` | 34/41, 700 | Titel E1 und E2 | 34/700 |
| Abschnittskopf | `--text-title2` oder `--text-title3`, Gewicht 700 | 22/28 oder 20/25 | Wochen, Monate, Register | 20–22/700 |
| Kopf im Bereich rechts | `--text-headline` | 17/22, 600 | „Ablauf“, „Rückmeldungen“ | ruhig |
| Zeit | `--text-headline` + `tabular-nums` | 17/22, 600 | Uhrzeit in Zeile und Karte | 17/600 |
| Name | `--text-subhead`, Gewicht 600 | 15/20, 600 | Person, Ort im Kopf | 15/600 |
| Fließtext, Wert | `--text-body` (mobil), `--text-subhead` (≥ 840) | 17/22 bzw. 15/20, 400 | Wertzeilen, Hinweistext | |
| Meta | `--text-footnote`, Gewicht 500 | 13/18, 500 | Ort, Besetzung, Frist mit Icon | 13/500 |
| Etikett | `--text-micro`, Gewicht 700, Versalien, `--tracking-caps` | 11/13, 700 | Probenart, Wochentag in der Kachel | 10–11/700 |
| Kennzahl | `--text-digit-lg`, `--text-digit`, `--text-digit-sm` | 28, 22, 17 | Zähler, Brüche | fett, tabellarisch |
| Nenner, kleine Zahl | `--text-caption` | 12/16, 500 | „/53“ | |

**VIS-01** Schriftgrößen kommen nur aus Tabelle 3.1 und nur über Tokens; das Gewicht darf nur wie in der Tabelle angehoben werden.
Prüfung: Stylelint mit `stylelint-declaration-strict-value` für `font`, `font-size`; kein Rohwert außerhalb von `tokens.css` und Druck-CSS.
Quelle: VL (Typografie), R106, AG §1 (Hierarchie über Gewicht, Größe, Farbe).

**VIS-02** Kleinste Schrift ist 12 px; 11 px nur für Etikett, Wochentag in der Kachel und Zahl-Badges in Versalien. Nichts ist kleiner als 11 px.
Prüfung: Alle berechneten `font-size` im Screen auslesen; Minimum 11 px, 11 px nur an den genannten Elementen.
Quelle: P7, MC §2, VL (Etikett).

**VIS-03** Ein Element (Karte, Zeile, Sheet) nutzt höchstens drei Schriftgrößen.
Prüfung: Größen je Element zählen.
Quelle: AG §4 (max. 3 Schriftgrößen), L7.

**VIS-04** Zahlen mit Aussage (Zeit, Zähler, Kennzahl) sind tabellarisch und fett; Brüche zeigen den Nenner klein.
Prüfung: Spalte mit Zeiten untereinander; Ziffern fluchten.
Quelle: VL (Typografie), G4, T15.

### 3.2 Abstände

Das Abstandssystem existiert in `tokens.css` auf 4-px-Basis: `--space-1` 4, `--space-2` 8, `--space-3` 12, `--space-4` 16, `--space-5` 20, `--space-6` 24, `--space-8` 32, `--space-10` 40 px; Seitenrand `--gutter` 16 / 20 (ab 430) / 32 px (ab 840); Dichte über `--density-*`.

| Beziehung | Token |
|---|---|
| Icon zu Text, Zahl zu Nenner | `--space-1` |
| Elemente in einer Zeile, Chips nebeneinander | `--space-2` |
| Zeilen innerhalb einer Karte | `--density-line-gap` |
| Innenabstand Karte | `--density-card-pad-y`, `--density-card-pad-x` |
| Karten oder Zeilen untereinander | `--density-card-gap` |
| Abschnitte | `--density-section-gap`; Listengruppen `--density-list-gap` |
| Spalten nebeneinander (Liste und Bereich rechts, Kartenraster) | `--space-6` |
| Seitenrand | `--gutter` |

**VIS-05** Abstände kommen nur aus Tokens nach der Tabelle oben. Rohwerte sind nur als 2-px-Korrektur innerhalb eines Bedienelements erlaubt.
Prüfung: Stylelint `declaration-strict-value` für `padding`, `margin`, `gap`, `inset`; Ausnahmen einzeln begründet.
Quelle: R114, AG §3 (Basiseinheit 4 px), R106.

**VIS-06** Der Abstand innerhalb einer Gruppe ist kleiner als der zwischen Gruppen, mindestens um den Faktor 1,5.
Prüfung: In jeder Karte Abstand Titel–Meta und Karte–Karte messen.
Quelle: G1, AG §4 (Proximity).

**VIS-07** Hinweise und Chips haben oben und unten denselben Innenabstand wie seitlich (bei Pillen: halbe Höhe als seitlicher Mindestabstand); Linien der Zeitleiste berühren ihre Punkte.
Prüfung: 2x-Crop messen; Abweichung höchstens 1 px.
Quelle: R114, R112.

**VIS-08** Die Dichte „Kompakt“ ändert nur `--density-*` und Größen-Tokens und zeigt sichtbar mehr Zeilen.
Prüfung: Auf 375 × 812 Zeilen in Normal und Kompakt zählen; Kompakt zeigt mehr.
Quelle: R14.

### 3.3 Farbe

**VIS-09** Farbe trägt nur die Bedeutungen der VL-Tabelle „Farbe hat Bedeutung“. Eine neue Bedeutung wird zuerst dort eingetragen.
Prüfung: Unscharfer Screenshot: jede bunte Stelle einer VL-Zeile zuordnen.
Quelle: VL, R80, AG §1 (eine Farbe, eine Bedeutung).

**VIS-10** Komponenten nutzen nur semantische Tokens (`--accent*`, `--surface*`, `--text*`, `--fill*`, `--status-*`, `--on-*`). Primitive (`--brand-*`, `--green-*`, `--cat-*`) und Rohfarben stehen nur in `tokens.css` und `theme-dark.css`. Datenfarben (Probenart, Rolle, Register) kommen als Custom Property aus den Daten (`--tag`, `--role`) und erscheinen nur an den VL-Stellen. Ausnahme: Druckblätter.
Prüfung: Stylelint `color-no-hex` und `declaration-strict-value` für Farbeigenschaften; kein Treffer außerhalb der Token-Dateien und Druck-CSS.
Quelle: VL, R52, R106.

**VIS-11** Farbe ist nie das einzige Merkmal: jeder Zustand hat zusätzlich Icon oder Form (Haken, Kreuz, gestrichelter Ring, Schloss).
Prüfung: Screenshot in Graustufen; alle Zustände bleiben unterscheidbar.
Quelle: W3, T3, AG §1.

**VIS-12** Verläufe tragen nur der primäre Button und die Heute-Fläche; alles andere ist flach.
Prüfung: Code nach `--grad-accent`, `--grad-hero` durchsuchen; nur diese Stellen.
Quelle: R81, R119, VL (Heute), Kommentar in `tokens.css`.

**VIS-13** Im Dunkelmodus sind Flächen neutral abgestuft; Blau steht nur für Interaktives und Daten.
Prüfung: Dunkler Screenshot, Sättigung je Fläche: nur Interaktives und Daten sind blau.
Quelle: R76.

### 3.4 Kontrast

**VIS-14** Text hat mindestens 4,5:1 gegen seine tatsächliche Fläche, großer Text (≥ 24 px oder ≥ 19 px fett) mindestens 3:1. Das gilt auch für Platzhalter, Text auf Tönungen und Text auf Verläufen an deren hellster Stelle.
Prüfung: axe `color-contrast` bei 375 und 1280 px, Hell und Dunkel; Verläufe manuell an der hellsten Stelle.
Quelle: W1, T15.

**VIS-15** Bedeutungstragende Icons, Ringe (offen), Kontrollflächen und der Fokusring haben mindestens 3:1 gegen ihre Umgebung.
Prüfung: Ring „offen“, Antwortquadrate, Schalter, `--icon-muted` gegen Karte messen.
Quelle: W2, W14.

**VIS-16** Karte gegen Seite: Leuchtdichteverhältnis mindestens 1,10:1 (Hell) und 1,20:1 (Dunkel), dazu `--shadow-card`. Im Unschärfe-Test (Gauß 4 px) bleibt jede Karte als Fläche erkennbar.
Prüfung: `--surface` gegen `--bg` berechnen; Screenshot mit Unschärfe.
Quelle: R79, R77, G6 (Projektwerte, gesetzt nach R79).

### 3.5 Visuelles Gewicht und Blickführung

| Screen | Erster Blick | Zweiter Blick | Ruhig |
|---|---|---|---|
| Proben, Mitglied | Heute-Karte bzw. nächste offene Probe mit Antwortquadraten | Antwort-Slots der Liste, Wochenköpfe | Ort, Besetzung, Hinweise, Tags |
| Proben, Leitung | Heute bzw. nächste Probe mit Rückmeldestand | Zähler der Zeilen, Chip „offen“ | Tags, Hinweise |
| Plan | Datum und Zeit | Probenart | Ort, Hinweise |
| Probe (Detail) | Termin und eigene Antwort; Leitung: Rückmeldestand | Ablauf, Hinweise | Teilen, Duplizieren |
| Rückmeldungen | Verteilung Zugesagt / Abgesagt / Offen | Personen der gewählten Gruppe, Erinnern | Sitzplan |
| Orchester › Übersicht | nächste Probe mit Rückmeldestand | Nicht erfasst | Letzte Proben |
| Orchester › Anwesenheit | gewählte Probe und Erfassungsstand | Personenzeilen mit Schalter | Sitzplan |
| Orchester › Mitglieder | Registerköpfe | Namen | Rollen-Icons |
| Profil | keine Rangfolge, gleichrangige Gruppen | Werte rechts | Fußzeile |

**VIS-17** Jede Ansicht hat genau einen ersten Blickpunkt und höchstens zwei zweite nach Tabelle 3.5; höchstens zwei große Elemente (Seitentitel und Heute-Fläche zählen).
Prüfung: Unschärfe-Test und 1-Sekunden-Test mit einer Testperson; sie nennt den ersten Blickpunkt der Tabelle.
Quelle: R59, R60, R11, AG §4 (max. 2 große Elemente).

**VIS-18** In einer Zeile haben Zeit und Status das größte Gewicht, Namen und Ort das mittlere, Probenart, Tags und Hinweise das kleinste.
Prüfung: Karte unscharf: Zeit und Status zuerst lesbar.
Quelle: R70, T15.

**VIS-19** Personen-Avatare sind ruhig getönt; ein einzelnes buntes Element zieht nur Aufmerksamkeit, wenn es eine VL-Bedeutung trägt.
Prüfung: Mitgliederliste unscharf; nur Status und Akzent fallen auf.
Quelle: R80, VL (Person).

### 3.6 Flächen und Erhöhung

| Stufe | Fläche | Schatten | Verwendung |
|---|---|---|---|
| 0 Seite | `--bg` | keiner | Hintergrund |
| 1 Karte | `--surface` | `--shadow-card` | Karten, Listengruppen, Zeilen |
| 2 hervorgehoben | `--surface`, `--tint-today` | `--shadow-2` | Detailkarte, Toast, Kontextkarte |
| 3 schwebend | `--surface-raised`, `--glass-sheet-bg` | `--shadow-3` | Sheet, Dialog, Popover, Menü |
| Heute | `--grad-hero`, `--grad-today` | `--shadow-hero` | nur die heutige Probe |

**VIS-20** Getrennt wird durch Fläche, Erhöhung und Abstand. Rahmen bis 1 px gibt es nur im Druck; Fokusring und Auswahlring (2 px) sind keine Rahmen.
Prüfung: Hell und Dunkel nach sichtbaren 1-px-Kanten absuchen (Rahmen und `inset 0 0 0 1px`); kein Treffer außer Druck.
Quelle: R78, G3.

**VIS-21** In einer Karte liegt keine zweite Karte mit eigenem Schatten. Innere Flächen gibt es nur für Bedienelemente (`--control-fill`) und Hinweise mit eigener lebendiger Fläche; keine grauen Kästen hinter Emojis oder Hinweisen.
Prüfung: Alle Karten auf verschachtelte Schatten und graue Innenflächen prüfen.
Quelle: R82, R74, VL (Hinweis).

**VIS-22** Glas (`--glass-*`, Unschärfe) gibt es nur auf Tab-Leiste, Kopfzeile und Sheets; „Transparenz reduzieren“ ersetzt es überall durch volle Flächen.
Prüfung: `data-transparency="reduced"` setzen; keine durchscheinende Fläche.
Quelle: R135, Kommentar in `tokens.css`.

**VIS-23** Schatten werden nirgends abgeschnitten.
Prüfung: `overflow` der Eltern gegen Schattenausdehnung prüfen, 2x-Crops ansehen.
Quelle: R113.

### 3.7 Radien

| Ebene | Token | Wert |
|---|---|---|
| Karte, Sheet, Dialog, Menü | `--radius-card` | 16 px |
| Bedienelement in einer Karte (Button, Feld, Menüpunkt) | `--radius-control` | 10 px |
| Etikett, Inneres eines Bedienelements | `--radius-inner` | 6 px |
| Pille, Kreis (Chip, Segment, Avatar) | `--radius-full` | rund |

**VIS-24** Radien kommen nur aus Tabelle 3.7. Ein Kind nutzt die nächstkleinere Stufe; liegt es mit höchstens 6 px Abstand im Eltern-Element, gilt Kind = Eltern − Abstand (konzentrisch). Pillen dürfen in jeder Stufe liegen.
Prüfung: Radius jedes verschachtelten Elements und Abstand zum Elternrand messen.
Quelle: R105, VL (Formen).

**VIS-25** Eine aufgeklappte Zeile und ihr Inhalt haben außen durchgehend `--radius-card`; innen gibt es keine Ecken.
Prüfung: Aufgeklappte Zeile im 2x-Crop; vier runde Außenecken, keine Kerben.
Quelle: VL, R111.

### 3.8 Design-Tokens

| Gruppe | Tokens (`tokens.css`, `theme-dark.css`) | In Komponenten |
|---|---|---|
| Primitive | `--brand-50` … `--brand-800`, `--green-*`, `--red-*`, `--amber-*`, `--violet-*`, `--rose-*`, `--teal-*`, `--indigo-*`, `--sky-*`, `--pink-*`, `--slate-*`, `--cat-*`, `--section-*` | verboten |
| Flächen | `--bg`, `--surface`, `--surface-raised`, `--surface-inset`, `--surface-tint`, `--surface-selected`, `--fill`, `--fill-strong`, `--fill-hover`, `--fill-pressed`, `--control-fill`, `--control-fill-hover`, `--tint-today` | Pflicht |
| Text | `--text`, `--text-secondary`, `--text-muted`, `--text-placeholder`, `--icon-muted`, `--on-accent`, `--on-warn` | Pflicht |
| Akzent | `--accent`, `--accent-strong`, `--accent-icon`, `--accent-pressed`, `--accent-tint`, `--accent-tint-strong`, `--focus-ring` | Pflicht |
| Status | `--status-yes-*`, `--status-no-*`, `--status-open-*`, `--status-warn-*`, `--status-live`, `--badge-*` | Pflicht |
| Probenart, Gruppe | `--type-*`, `--concert-*`, `--group`, `--group-fg` | Pflicht |
| Verläufe | `--grad-accent`, `--grad-accent-pressed`, `--grad-hero`, `--grad-today`, `--grad-yes`, `--grad-no` | nur nach VIS-12 |
| Material | `--glass-*`, `--scrim`, `--toast-*` | nur nach VIS-22 |
| Schrift | `--font-*`, `--text-large-title` … `--text-micro`, `--text-digit*`, `--tracking-*` | Pflicht |
| Abstand, Dichte | `--space-*`, `--gutter`, `--density-*` | Pflicht |
| Radius | `--radius-card`, `--radius-control`, `--radius-inner`, `--radius-full` (Aliasse `--radius-xs` … `--radius-2xl` nicht neu verwenden) | Pflicht |
| Erhöhung | `--shadow-1`, `--shadow-card`, `--shadow-2`, `--shadow-3`, `--shadow-hero`, `--shadow-accent*`, `--shadow-yes`, `--shadow-no`, `--shadow-thumb`, `--ring-edit` | Pflicht |
| Bewegung | `--dur-*`, `--ease-*`, `--press-scale` | Pflicht |
| Größen | `--touch`, `--row-h*`, `--btn-h-*`, `--input-h`, `--segment-h`, `--chip-h`, `--pill-h`, `--badge-h`, `--icon-*`, `--date-chip-*`, `--rsvp-h*` | Pflicht |
| Schale | `--header-h`, `--tabbar-h`, `--rail-w`, `--sidebar-w`, `--list-pane-w`, `--content-max-w`, `--detail-max-w`, `--wide-max-w`, `--content-wide`, `--table-max-w`, `--screen-margin`, `--sheet-max-w`, `--dialog-w`, `--z-*` | Pflicht |

**VIS-26** Komponenten-CSS enthält keine Rohwerte für Farbe, Schriftgröße, Abstand, Radius, Schatten und Dauer. Fehlt ein Wert, wird zuerst ein Token angelegt.
Prüfung: Stylelint mit `stylelint-declaration-strict-value` im Projekt; null Verstöße außer Druck-CSS.
Quelle: R106, R110, SH1.

### 3.9 Layout-Raster je Breite

| Klasse | Fenster | Navigation | Rand | Inhalt | Spalten |
|---|---|---|---|---|---|
| Compact | < 600 | Tab-Leiste unten (`--tabbar-h` 56) | `--gutter` 16, ab 430: 20 | volle Breite | 1 |
| Medium | 600–839 | Rail 80 (`--rail-w`) | 20 | max. `--content-max-w` 680, zentriert | 1 |
| Expanded | 840–1199 | Rail 80 | 32 (`--screen-margin`) | bis `--wide-max-w` | Liste 1 Spalte Zeilen; Karten-Raster auto-fill, Spalte ≥ 320 |
| Large | 1200–1599 | Seitenleiste 240 (`--sidebar-w`) | 32 | bis `--content-wide` 1440 | wie Expanded |
| Extra-large | ≥ 1600 | Seitenleiste 240 | 32 | bis `--content-wide` | Liste plus Bereich rechts (`--list-pane-w` 400–440), sobald die Liste ≥ 1100 px bleibt |

**VIS-27** Das Layout folgt Tabelle 3.9. Bei vertikalem Scrollen bleibt rechts keine ungenutzte Fläche breiter als eine Spalte des Rasters.
Prüfung: Jeden Screen bei 2000 und 3440 px; leere Fläche rechts neben dem Inhalt messen.
Quelle: R91, R88, AG §2 (Raster statt gestreckter Einzelspalte).

**VIS-28** Karten-Raster nutzen `auto-fill` mit Mindestspalte 320 px statt gestreckter Einzelkarten; Fließtext läuft höchstens 75 Zeichen breit.
Prüfung: Profil und Einstellungen bei 1280 und 2000 px; Zeilenlänge von Hinweistext zählen.
Quelle: AG §2.

**VIS-29** Mehrspaltigkeit braucht je Spalte einen eigenen Zweck.
Prüfung: Für jede Spalte den Zweck in einem Satz nennen.
Quelle: R92.

### 3.10 Dichte und Weißraum

**VIS-30** Auf 375 × 812 sind nicht weniger Proben ohne Scrollen sichtbar als in `prototype-verlauf/v00-produktion`.
Prüfung: Sichtbare Proben zählen und vergleichen.
Quelle: R101.

**VIS-31** Zähler und Meta stehen oben rechts auf Titelhöhe; ein einzelnes kleines Element bekommt keine eigene Zeile; keine halbleeren Zeilen neben kurzen Elementen.
Prüfung: Alle Karten- und Gruppenköpfe: Zahl rechtsbündig auf Titelhöhe.
Quelle: R64, R65, R66, VL (Kennzahl, Registerkopf mit Anzahl rechts).

**VIS-32** Am Desktop sind Listen dichte Zeilen (`--row-h-dense`, kompakt 48 px), keine gestreckten Mobilkarten.
Prüfung: Zeilenhöhe bei 1280 px messen.
Quelle: R88, R98.

### 3.11 Responsive Komposition

**VIS-33** Viewport-Breakpoints gibt es nur bei 600, 840, 1200 und 1600 px. Feinere Anpassung innerhalb einer Klasse geschieht über Container-Abfragen, `auto-fill`/`minmax` oder `clamp()`, nie über weitere Viewport-Breiten.
Prüfung: Alle `@media (min-width|max-width …)` im CSS und `matchMedia` im JS auflisten; nur diese vier Werte.
Quelle: AG §2 (Fenstergrößenklassen), R106.

**VIS-34** Umbau der Struktur (Shell, Liste zu Detail) geschieht nur an Klassengrenzen; innerhalb einer Klasse wird nur umgebrochen und skaliert.
Prüfung: Fenster langsam von 375 auf 3440 ziehen; Strukturwechsel nur an den Grenzen und an der Liste-≥-1100-Bedingung.
Quelle: AG §3 (Reposition, Resize, Reflow, Show/Hide, Re-architect).

**VIS-35** Die Reihenfolge der Inhalte ist auf allen Breiten gleich; Nähe und Gruppierung werden bei jedem Umbruch neu geprüft.
Prüfung: Inhaltsreihenfolge bei 375 und 1280 px vergleichen.
Quelle: AG §4, AG §5, G1.

**VIS-36** Desktoparbeit lässt Mobil unverändert; jede Änderung wird vorher und nachher nebeneinander geprüft, auch auf nicht kritisierten Screens.
Prüfung: Mobil-Screenshots vor und nach der Änderung pixelgleich oder bewusst geändert.
Quelle: R99, R116.

---

## 4. Inhalte und Microcopy

Grundlage ist `ux-microcopy-regeln.md` (Glossar MC §3). Jeder String ist Name, Wert oder Aktion (R01).

**TXT-01** Jeder sichtbare String ist ein Name, ein Wert oder eine Aktion. Ein Satz ist nur in den fünf Ausnahmen von MC §1 erlaubt, höchstens einer pro Stelle.
Prüfung: Alle Textknoten eines Screens auflisten und typisieren; alles andere streichen.
Quelle: R01, R03, R04, R05, MC §1.

**TXT-02** Titel bekommen keinen Untertitel, wenn gleichrangige Titel keinen haben; Vereinsname, Erklärband oder Zählzeile stehen nicht als graue Zeile unter einem Titel.
Prüfung: Titel vergleichbarer Screens nebeneinander; Zusatzzeilen überall oder nirgends.
Quelle: R06, R07.

**TXT-03** Labels sind Substantive aus dem Glossar, sichtbar über oder links neben dem Feld, nie nur Platzhalter. Längenbudget: Tab 1 Wort ≤ 10 Zeichen, Segment und Chip ≤ 12 Zeichen, Navigationstitel ≤ 15 Zeichen.
Prüfung: Alle Felder: sichtbares Label; Zeichen zählen.
Quelle: W24, MC §3.

| Bedeutung | Wort |
|---|---|
| Einstieg zum Anlegen | „Neue Probe“, „Neue Rolle“, „Neuer Ort“ (R48) |
| neues Objekt abschließen | Anlegen |
| Änderung an bestehendem Objekt übernehmen (ohne Autosave) | Speichern |
| Unterpunkt zum aktuellen Objekt hinzufügen | Hinzufügen („Programmpunkt hinzufügen“) |
| Sheet mit Autosave oder Auswahl schließen | Fertig |
| ohne Änderung schließen | Abbrechen |
| Rücknahme eines Zustands | Zurücknehmen (Absage), Zurücksetzen (Link, Standardwerte) |
| Erfolg des Anlegens | „Probe angelegt“ |

**TXT-04** Aktionen sind Infinitive mit Objekt, wo der Kontext es nicht klärt („Probe absagen“, „Erinnerung senden“). Für wiederkehrende Bedeutungen gilt die Tabelle oben; „erstellen“, „Übernehmen“ und „Anwenden“ werden nicht verwendet.
Prüfung: Alle Buttons auflisten; jeder ist ein Infinitiv oder ein Wort der Tabelle.
Quelle: MC §3 (Grammatik), W13, W22.

**TXT-05** Fehlertexte nennen in einem Satz, was nicht ging, und den Ausweg, am Feld bzw. im Toast mit „Erneut versuchen“; keine Codes, keine Versalien, kein Vorwurf.
Prüfung: Jeden Fehler auslösen (Validierung, Netz, Login); Text und Ausweg vorhanden.
Quelle: N9, W25, MC §1 (Ausnahme 5), MC §5.

**TXT-06** Leere Zustände: Titel „Keine …“, ein Satz nur wenn der nächste Schritt bei jemand anderem liegt, genau ein Button zum nächsten Schritt; leere Suche: „Keine Treffer“ mit „Suche leeren“.
Prüfung: Jede Liste leer und jede Suche ohne Treffer ansehen.
Quelle: MC §1 (Ausnahme 4), MC §5, N10, R106.

**TXT-07** Tooltips gibt es nur an Icon-Buttons (Text = `aria-label` = Name der Aktion) und an abgeschnittenem Text (Volltext). Keine Information steht nur im Tooltip.
Prüfung: Alle `title` und Tooltips auflisten; jeder Inhalt ist auch ohne Hover erreichbar.
Quelle: MC §2, AG §4 (Icons mit sichtbarem Label), W7, W19.

**TXT-08** Bestätigungen: Titel ist eine Frage mit Objekt und Aktion („Erinnerung an 6 Mitglieder senden?“), Body nur bei Folgen außerhalb des Offensichtlichen, Buttons nennen das Ergebnis; nie Ja/Nein/OK, nie „Bist du sicher“.
Prüfung: Alle Bestätigungen auflisten; Titel endet mit „?“, Body fehlt oder nennt eine nicht offensichtliche Folge.
Quelle: MC §4, R04, P2.

| Wirkung der Aktion | Meldung |
|---|---|
| sichtbar an Ort und Stelle (Antwort, Schalter, Wert in der Zeile) | keine; der Zustand ist die Meldung |
| außer Sicht (kopiert, gesendet, gelöscht, verschoben) | Toast, höchstens 6 Wörter, ggf. „Rückgängig“ |
| Eingabe ungültig | Text am Feld |
| Speichern gescheitert | Toast mit „Erneut versuchen“ |
| Abschluss einer mehrschrittigen Aufgabe | Rückkehr zur Liste, neues Objekt hervorgehoben (`--dur-highlight`) |

**TXT-09** Erfolgs- und Fehlermeldungen folgen der Tabelle oben. Toasts lauten „<Objekt> <Partizip>“ („Probe abgesagt“, „6 Erinnerungen gesendet“), ohne „erfolgreich“, Zustellung oder Lesen werden nie behauptet.
Prüfung: Alle `toast(`-Aufrufe auflisten.
Quelle: P3, P9, SH4, MC §3 (Toast ≤ 6 Wörter), MC §4.

**TXT-10** Begriffe nur aus dem Glossar MC §3, ein Wort pro Begriff: Antwort ist die einzelne Reaktion (Frist: „Antwort bis …“), Rückmeldungen ist nur der Name der Ansicht aller Antworten einer Probe, Offen ist ohne Antwort.
Prüfung: Code nach „Rückmeldung“ durchsuchen; jeder Treffer meint die Ansicht. Nach „Termin“, „Team“, „Account“ suchen; kein sichtbarer Treffer.
Quelle: MC §2 (Synonymdrift), MC §3, R83, W22.

**TXT-11** Datums- und Zeitformate sind fest.

| Kontext | Format | Beispiel |
|---|---|---|
| Kachel | Wochentag 2 Buchstaben in Versalien (per CSS), Tag | DI / 27 |
| Zeile, Liste | Anfangszeit groß, Ende leichter | **19:30**–22:00 |
| Navigationstitel, Detailkopf | Wochentag kurz, Datum | Sa 24.10. |
| Spanne | Halbgeviertstrich ohne Leerzeichen | 19:30–22:00, 6.–8.11. |
| Frist | „Antwort bis“ Wochentag Uhrzeit | Antwort bis Fr 12:00 |
| relative Tage | Heute, Morgen | Heute · 27.10. |

Prüfung: Alle Datumsausgaben eines Durchgangs mit der Tabelle vergleichen.
Quelle: R73, VL (Termin: Ende leichter), MC §3, N2.

**TXT-12** Normale Satzschreibung. Versalien entstehen nur per CSS (`text-transform`) bei Etikett und Kachel-Wochentag, nie in String-Literalen.
Prüfung: Code nach Literalen in Versalien durchsuchen (außer Initialen und dem getippten Bestätigungswort).
Quelle: MC §2, W9 (Screenreader liest Text, nicht Optik).

**TXT-13** Keine Abkürzungen außer fachüblichen, im Orchester gesprochenen (NBK, Tutti); Statuswörter sind auf den ersten Blick verständlich.
Prüfung: Blindtest: Testperson erklärt jede Abkürzung und jedes Statuswort ohne Hilfe.
Quelle: R85, R84.

**TXT-14** Anrede du, kein „wir“, keine Ausrufezeichen, keine Füllwörter („einfach“, „schnell“).
Prüfung: Code nach „wir“, „Sie“, „!“, „einfach“, „schnell“ in sichtbaren Strings durchsuchen.
Quelle: MC §2, MC §3.

**TXT-15** `aria-label` nennen Aktion und Objekt oder Zustand auf Deutsch und enthalten den sichtbaren Text, wenn es einen gibt.
Prüfung: axe `label-content-name-mismatch`, `button-name`.
Quelle: W19, W28, P11.

---

## 5. Responsive und adaptives Design

### 5.1 375 px (Compact, Referenz-Smartphone)

| Aspekt | Regel |
|---|---|
| Navigation | Tab-Leiste unten, immer sichtbar, 4 Tabs (R51) |
| Kopfzeile | großer Titel; Orchesterwahl, Suche und „Neue Probe“ auf Titelhöhe; höchstens drei Icon-Aktionen |
| Liste | Karten, eine Spalte; eigene Antwort sichtbar ohne Aufklappen |
| Detail | eigene Seite mit Zurück |
| Tabellen | Matrix scrollt in sich, angeschnittene Spalte, Namen fixiert |
| Overlays | Bottom Sheet; kurze Wahl als Dropdown-Menü am Auslöser |
| darf entfallen | Ende der Zeit, Ort und Besetzung wenn Standard, Hinweistexte außer dem ersten (Emoji bleibt), Ablauf und Sitzplan (nur im Detail), Text an Werkzeug-Icons |
| immer erreichbar | eigene Antwort, Hauptaktion, Tab-Leiste, Zurück, Heute, Frist, Fällt aus |

### 5.2 600 px (Medium)

| Aspekt | Regel |
|---|---|
| Navigation | Rail links (80 px), Icon und Label, gleiche Ziele wie Tab-Leiste |
| Kopfzeile | wie 375, Suche als Feld, wenn Platz |
| Liste | Karten, eine Spalte, max. 680 px zentriert |
| Detail | eigene Seite |
| Tabellen | wie 375 |
| Overlays | Bottom Sheet bis `--sheet-max-w` |
| darf entfallen | wie 375 |
| immer erreichbar | wie 375; Rail statt Tab-Leiste |

### 5.3 840 px (Expanded)

| Aspekt | Regel |
|---|---|
| Navigation | Rail links |
| Kopfzeile | Werkzeugleiste in der Titelzeile: Titel, Chip offen, Segment, Filter, Suche, Hauptaktion; Icons ohne Text nur für Suche, Filter, Drucken |
| Liste | tabellenartige Zeilen mit festen Spalten |
| Detail | Aufklappen in der Liste |
| Tabellen | echte Tabellen mit Spaltenköpfen |
| Overlays | Zeiger fein: Popover am Auslöser, Dialog zentriert; Touch: Bottom Sheet |
| darf entfallen | Text an Werkzeug-Icons (siehe oben) |
| immer erreichbar | eigene Antwort, Hauptaktion, Navigation, Zurück, alle Objekt-Aktionen ohne Menü |

### 5.4 1200 px (Large)

| Aspekt | Regel |
|---|---|
| Navigation | Seitenleiste 240 px: Orchesterwahl oben, Ziele, Nächste-Probe-Widget, Person unten (R89) |
| Kopfzeile | Werkzeugbuttons mit Icon und Text |
| Liste | tabellenartige Zeilen; Hinweistext neben dem Emoji |
| Detail | Aufklappen |
| Tabellen | alle Spalten |
| Overlays | wie 840 |
| darf entfallen | nichts aus der Desktop-Zeile |
| immer erreichbar | wie 840 |

### 5.5 1600 px (Extra-large)

| Aspekt | Regel |
|---|---|
| Navigation | Seitenleiste wie 1200 |
| Kopfzeile | wie 1200 |
| Liste | Zeilen; Bereich rechts (400–440 px), sobald die Liste daneben ≥ 1100 px breit bleibt, sonst Aufklappen |
| Detail | Bereich rechts zeigt die gewählte Zeile, ohne Wahl die nächste Probe |
| Tabellen | wie 1200 |
| Overlays | wie 840 |
| darf entfallen | nichts |
| immer erreichbar | wie 840; gewählte Zeile markiert |

### 5.6 3440 px (Ultrabreit, Owner-Monitor)

| Aspekt | Regel |
|---|---|
| Navigation | Seitenleiste wie 1200 |
| Kopfzeile | Werkzeugleiste endet am rechten Inhaltsrand, nicht am Fensterrand jenseits des Inhalts |
| Liste | Liste und Bereich rechts füllen die Breite bis `--content-wide`; Karten-Raster mit mehr Spalten |
| Detail | Bereich rechts; ab 640 px Bereichsbreite zwei Spalten (RES-06) |
| Tabellen | alle Spalten, keine gestreckten Leerspalten |
| Overlays | wie 840 |
| darf entfallen | nichts |
| immer erreichbar | wie 1600 |

**RES-01** Eigene Antwort, Hauptaktion, Navigation und Zurück sind auf jeder Breite ohne Menü und ohne Scrollen zur Steuerung erreichbar (Kopfzeile und Tab-Leiste bleiben beim Scrollen stehen).
Prüfung: Je Breite der Tabellen 5.1–5.6 jede der vier Steuerungen aus der Mitte einer langen Liste erreichen.
Quelle: N6, T9, T18, AG §1 (Tab-Bar sichtbar halten).

**RES-02** Nie entfallen dürfen: Heute, Frist, Status (Fällt aus), eigene Antwort, Wochengruppierung, Übergruppen der Stimmen, Probenart außer der Standardart.
Prüfung: Liste der Informationen je Breite abhaken.
Quelle: R10, R47, R53, R120.

**RES-03** Entfallen dürfen nur die in 5.1 genannten Informationen; was entfällt, steht im Detail vollständig.
Prüfung: Für jede entfallene Information die Stelle im Detail nennen.
Quelle: R73, AG §3 (kleine Fenster: minimale Metadaten).

**RES-04** Auswahl, aufgeklappte Zeile, Segment und Scrollstand überstehen jeden Breitenwechsel.
Prüfung: Zeile wählen, Fenster über 840 und 1600 px ziehen; dieselbe Probe bleibt gewählt bzw. geöffnet.
Quelle: AG §2, AG §5, T17.

**RES-05** Die Form von Overlays richtet sich nach Breite und Zeigerart, nicht nach Gerätenamen.
Prüfung: 1024 px mit Touch-Emulation: Bottom Sheet; 1024 px mit Maus: Popover bzw. Dialog.
Quelle: AG §1 (Size Classes statt Gerät), AG §2.

**RES-06** Die Liste behält ihre Obergrenze; was an Breite übrig bleibt, trägt der Bereich rechts. Ist er mindestens 640 px breit (ab etwa 2560 px Fenster), steht sein Inhalt in zwei Spalten: links der Ablauf, rechts Rückmeldungen mit Sitzplan, darunter Hinweise und Ort; die Aktionsleiste steht wie in der aufgeklappten Zeile über beiden Spalten. Keine einzelne schmale Spalte in einem breiten Bereich.
Prüfung: Bei 2560 und 3440 px die Proben-Liste (Mitglied, Stimmführung, Leitung) öffnen; der Bereich rechts zeigt zwei Spalten, keine Inhaltsspalte ist schmaler als ein Drittel des Bereichs.
Quelle: R91, R97, VIS-27.

---

## 6. Abweichungen im Prototyp

Stand: Code am 2026-11-25 und Screenshots `prototype-verlauf/_roh/runde-c/*/nachher/` (22:42–22:46) sowie `_roh/inventur/s/`. Prio A: auf Hauptscreens sichtbar oder vom Owner schon bemängelt; B: sichtbar, seltener; C: Code-Disziplin oder Barrierefreiheit ohne sichtbaren Effekt.

| Nr | Screen (Rolle, Breite) | Regel | Abweichung | Beleg | Prio |
|---|---|---|---|---|---|
| 1 | Proben, Übersicht, Anwesenheit, Rückmeldungen, mobiles Detail (Leitung, alle) | R08, KOM-09 | Sitzplan zeigt „39/53“ groß, daneben dieselben Zahlen als Legende ✓ 39 ✕ 8 ○ 6; Übersicht „36“ zweimal, Anwesenheit „0/53“ in Probenwahl und Sitzplan, Rückmeldungen „2/5“ plus Status-Tabs | `runde-c/proben/nachher/c-2000d-sel114.png`, `c-dash-2000d.png`, `orchester/nachher/c-d2-attendance.png`, `l-2000d-responses114.png`, `js/seating.js` | A |
| 2 | Proben aufgeklappt, heutige Probe (alle, 1280) | R08, KOM-09 | „Jetzt Brahms 2, 1. Satz bis 20:45“ bleibt im Zeilenkopf und derselbe Punkt ist im Ablauf hervorgehoben | `runde-c/proben/nachher/c-1280d-x114.png` | A |
| 3 | Proben (Mitglied, 2000) | NAV-10, R08 | Zahl offener Antworten dreimal: Badge am Tab, Chip „3 offen“, Kreis in Karte „Meine Proben“ | `runde-c/wuensche-proben.md`, `js/shell-nav.js:202` | A |
| 4 | Orchester › Verwaltung › Rollen & Rechte (Leitung, 2000) | NAV-02, NAV-03, R42 | Segment plus linke Bereichsspalte (Einstellungen, Registerstruktur, Orte, Rollen & Rechte) plus Rollenliste plus Detail: drei Auswahlspalten neben der Seitenleiste | `orchester/nachher/c-d2-roles.png`, `js/screens/settings.js:315-342` | A |
| 5 | Proben (alle, 375) und Orchester (375) | NAV-20, R46, R07 | Orchesterwahl steht als eigene graue Zeile unter dem großen Titel | `proben/nachher/c-375l-list.png`, `orchester/nachher/c-m-members.png` | A |
| 6 | Proben (Leitung, 375) | R48, R49, KOM-34 | Suche und „Neue Probe“ stehen als Icons in der Leiste über dem Titel, nicht auf Titelhöhe; fünf Icon-Aktionen ohne Text (Filter, Auswählen, Suche, Neu, Glocke) | `proben/nachher/c-375l-list.png`, `js/screens/rehearsals.js:909-919` | A |
| 7 | Probe (Leitung, < 840) | NAV-05, NAV-06 | Dieselbe Probe hat mobil `#/rehearsals?probe=ID`, breit `#/rehearsals/ID`; `#/rehearsals/ID` ist mobil der Editor. Zurück aus `#/rehearsals/ID/responses` landet mobil im Editor statt im Detail | `js/screens/rehearsals.js:81-84, 1060-1065`, `js/screens/rehearsal-edit.js:1418-1432` | A |
| 8 | Editor Probe und Plan (Leitung, < 840) | NAV-18, R44 | `back: false`: mobil weder Zurück noch Abbrechen, nur „Fertig“ und „···“ | `js/screens/rehearsal-edit.js:237-256, 334-337` | A |
| 9 | Rückmeldungen (Stimmführung, 2000) | KOM-28, R62, VIS-27 | Personenzeile spannt 1690 px, Chevron sitzt bei x ≈ 1088 mitten in der Zeile; darunter leere Fläche | `proben/nachher/l-2000d-responses114.png` | A |
| 10 | Kalender-Abo im Profil (alle, 2000) | KOM-30, R38 | zwei primäre Verlaufs-Buttons („Abonnieren“, „Einrichten“) in einer Ansicht | `orchester/nachher/m-d2-profile.png` | A |
| 11 | Mitglied, Mitgliederliste, Profil (alle) | VL (Rolle), VIS-09 | Rollen-Chips färben Fläche und Text in Rollenfarbe; VL erlaubt die Farbe nur am Icon | `orchester/nachher/c-d2-member3.png`, `css/components.css` `.role-chip` (~1650), `runde-c/wuensche-orchester.md` | A |
| 12 | Mitgliederliste, Anwesenheit, Übersicht, Mitglied (Leitung, alle) | VIS-31, R65, VL (Registerkopf) | Zahl direkt hinter dem Namen statt rechts: „Violine 1 8“, „Streicher 28“ (mobil), „Nicht erfasst 2“, „Offene Rückmeldungen 4 von 8“ | `orchester/nachher/c-d2-members.png`, `c-m-members.png`, `proben/nachher/c-dash-2000d.png`, `c-d2-member3.png`, `js/ui.js` `voiceBlocks` | A |
| 13 | Proben, Plan, Übersicht (alle, alle Breiten) | TXT-11, R73, VL (Termin) | Zeilen zeigen die volle Spanne „19:30 – 22:00“ gleich gewichtet; R73 verlangt Anfangszeit, VL „Ende leichter“; Strich mit Leerzeichen | `proben/nachher/c-2000d-sel114.png`, `c-375l-list.png` | A |
| 14 | Probe mobil (Leitung, 375) | VL (Einstellungs-Eintrag) | Icon-Kacheln vor den Überschriften „Rückmeldungen“, „Hinweise“, „Ablauf“; VL: Icon-Kachel nie vor Überschriften | `proben/nachher/c-375l-probe114.png` | B |
| 15 | Proben, Plan (alle, 375) | VIS-20, R78, R82 | Emoji-Chips mit sichtbarer 1-px-Kante (`inset 0 0 0 1px var(--border-pill)`), ebenso Status-Pillen, Badges, Antwortbuttons mobil | `css/components.css:1595, 1822, 1889, 2618, 2782-2809`, `css/screens/proben.css:311` | A |
| 16 | Primärbutton (alle, Hell und Dunkel) | VIS-14, W1 | Weißer Text auf `--grad-accent`-Start `#478cf4`: 3,32:1 | `css/tokens.css:116`, `css/components.css:307` | A |
| 17 | Akzentflächen im Dunkelmodus | VIS-14, W1 | `--on-accent` (#fff) auf flachem `--accent` (#6ba3f6): 2,56:1; kein Dark-Override | `css/tokens.css:112, 354`, `css/base.css:720`, `css/screens/members.css:1344` | A |
| 18 | Eingabefelder (alle) | VIS-14, W1 | `--text-placeholder` 2,74:1 (Hell, Seite), 2,94:1 (Hell, Karte), 4,39:1 (Dunkel, Karte) | `css/tokens.css:97`, `css/theme-dark.css:15` | B |
| 19 | Ring „offen“ in Sitzplan und Antworten | VIS-15, W2 | `--status-open-solid` gegen Karte 1,63:1 (Hell), 2,45:1 (Dunkel); `--icon-muted` Hell 2,13:1 | `css/tokens.css:98, 152`, `css/theme-dark.css:23` | B |
| 20 | Karten im Light Mode | VIS-16, R79 | `--surface` #fff gegen `--bg` #f4f7fd: 1,07:1 | `css/tokens.css:82-83` | B |
| 21 | Seitenleiste (alle, ≥ 1200) | R89, Tabelle 5.4 | kein Nächste-Probe-Widget in der Seitenleiste; die nächste Probe steht nur im Bereich rechts, der erst ab ca. 1940 px erscheint | `proben/nachher/c-2000d-sel114.png`, `js/shell-aside.js:122-150`, `js/shell-nav.js:18-28` | B |
| 23 | Seitenleiste `?nav=top/jmd/rail` | NAV-08 | zweite Navigationsdefinition `SIDEBAR` mit anderen Zielen und Badges | `js/app.js:82-97, 739-781` | C |
| 24 | Mitglied (Leitung, 2000) | NAV-21, R07 | Titel „Mitglied“, der Name steht als zweite Überschrift darunter | `orchester/nachher/c-d2-member3.png`, `js/screens/members.js:740-754` | B |
| 25 | Mitglied, Profil, Rückmeldungen (alle) | NAV-17 | Stimme ohne Übergruppe („Violine 1“); Scope-Chip trennt mit Chevron-Icon („Streicher › Violine 2“) statt „ · “ | `js/screens/responses.js:297-302`, `js/screens/members.js:712-719`, `js/screens/profile.js:570-580` | B |
| 26 | Mitgliederliste (Leitung, 375) | R54, KOM-34, AG §4 | Rollen-Filterchips nur als Icon und Zahl („☆ 1“, „👑 13“), „Rollen verwalten“ und „Einladen“ nur als Icon | `orchester/nachher/c-m-members.png` | B |
| 27 | Einladungslink | NAV-22, R41, W22 | Zurücksetzen an zwei Orten mit zwei Titeln: „Link zurücksetzen?“ und „Einladungslink zurücksetzen?“ | `js/screens/settings.js:377`, `js/screens/members.js:507` | B |
| 28 | Rückmeldungen (Stimmführung, 375 vs. 1280) | NAV-14, AG §5 | „Erinnern“ steht am Desktop im Status-Tab „offen“, mobil als Leiste unten und nur bei Filter „offen“ | `js/screens/responses.js:341-352, 437-450` | B |
| 29 | `/…/:rid/members/:id` per Deep Link | NAV-06, Tabelle 1.2 | Zurück ohne Verlauf springt zur Tab-Wurzel statt zu den Rückmeldungen der Probe; `/team/rehearsals/:id` springt zum gespeicherten Segment | `js/app.js:432-437` | C |
| 30 | Proben (Leitung) und Proben (Mitglied) | NAV-19, KOM-22 | Filter und Suche werden beim Verlassen des Tabs zurückgesetzt | `js/screens/rehearsals.js:1394-1401`, `js/screens/promises.js` (~753) | B |
| 31 | Chip „N offen“ | KOM-05, G2 | gleicher Chip: bei Leitung öffnet er das Erinnern-Sheet, bei Mitglied schaltet er einen Filter | `js/screens/rehearsals.js:464`, `js/screens/promises.js:356` | B |
| 32 | Status-Pille in Probenkarte | KOM-04, R29 | interaktive `statusPill` trägt Chevron nach unten, hat aber keinen Klick-Handler (nur Langdruck) | `js/ui.js:495, 711`, `js/screens/promises.js:211` | B |
| 33 | Farbschema, Scope, Zeitraum, Plattform | KOM-03, W28 | Wert-Segmente sind `tablist` ohne `tabpanel` statt `radiogroup` | `js/ui.js:733`, `js/screens/profile.js:861, 1238`, `js/screens/plan.js:263`, `js/screens/attendance.js:1070` | C |
| 34 | Radiogruppen (Antwort am Desktop, Anwesenheit, Stimmen, Farben, Icons, Gründe) | KOM-15, W10 | keine Pfeiltasten, jede Option ist ein eigener Tab-Stopp | `js/rsvp.js:667`, `js/ui.js:869, 1329`, `js/voice-sheet.js:21`, `js/screens/settings.js:866, 890` | C |
| 35 | Exklusive Filter (Plan-Gruppen, Anwesenheit, Absagegründe) | KOM-15, W28 | Einfachauswahl mit `aria-pressed` statt Radio-Semantik | `js/screens/plan.js:273`, `js/screens/attendance.js:243`, `js/rsvp.js:148` | C |
| 36 | Ort anlegen (Sheet), Editor „Mehrtägig“ | KOM-14 | Schalter in Formularen mit Abschluss-Button; „Standardort“ wird nach dem Einschalten deaktiviert und lässt sich nicht mehr ausschalten | `js/places.js:116-119`, `js/screens/rehearsal-edit.js:882` | B |
| 37 | Menüs und Popover | KOM-01, R106 | drei Implementierungen: `menu.js`, eigenes Erstellen-Menü, eigenes Filter-Popover | `js/menu.js`, `js/screens/rehearsals.js:849-905`, `js/screens/rehearsals-tools.js:225-312` | C |
| 38 | Auswahl-Sheet | W28 | `role="option"` ohne `listbox`-Eltern | `js/ui.js:1143` | C |
| 39 | Probe löschen, Mitglied entfernen, N Proben löschen, Rolle löschen, Registerknoten löschen | KOM-42, MC §4 | Bestätigung und zusätzlich Rückgängig-Toast für dieselbe Aktion | `js/screens/rehearsal-edit.js:1372` + `:547`, `js/screens/members.js:914, 927`, `js/screens/rehearsals-tools.js:548, 561`, `js/screens/settings.js:833, 846, 1647, 1658` | B |
| 40 | Bestätigungs-Sheet | KOM-43, P2 | `autofocus` liegt auf dem bestätigenden (roten) Button und wirkt nicht, weil `openSheet` den Fokus vorher setzt | `js/ui.js:1067` | C |
| 41 | Bestätigungen | TXT-08 | Titel ohne Frage („Probe löschen“, „N Proben löschen“, „Absage zurücknehmen“); Bodies wiederholen die Aktion („Die Probe am … wird gelöscht.“, „… endgültig gelöscht.“) | `js/screens/rehearsal-edit.js:1308, 1372-1374`, `js/screens/rehearsals-tools.js:548-550`, `js/screens/settings.js:433, 1475` | B |
| 42 | Erinnern am Mitglied | KOM-43, R106 | eigene Bestätigung außerhalb von `openConfirmSheet` | `js/screens/members.js:660` | C |
| 43 | Alle Screens | KOM-36, KOM-40, P5, N9 | kein Lade- oder Skeleton-Zustand außer Login, kein `aria-busy` außerhalb Login, kein Fehlerzustand mit „Erneut versuchen“ | `js/screens/auth.js:50`; `.skeleton` in `css/components.css:4849` ungenutzt | B |
| 44 | Chip, Segment, Schalter, Checkbox, Anwesenheitsschalter, Wertzeile | KOM-36, R88 | kein Hover-Zustand | `css/components.css:875-914, 1049-1057, 1195-1203, 3535-3599`, `css/orchester.css:69` | B |
| 45 | Eingabefeld, Antwortbuttons, Anwesenheitsschalter | KOM-36 | kein gestalteter Deaktiviert-Zustand | `css/components.css:1233-1239, 2612-2665, 3546` | C |
| 46 | Suche ohne Treffer (Proben) | TXT-06, KOM-20 | „Keine Treffer“ ohne Button; Mitglieder hat „Suche leeren“ | `js/screens/promises.js:654, 660`, `js/screens/rehearsals.js:1267`, `js/screens/members.js:221` | B |
| 47 | Probenkarte mobil | KOM-47, W17 | Langdruck öffnet das Antwort-Sheet ohne sichtbare oder Tastatur-Entsprechung | `js/screens/promises.js:211, 286, 290` | B |
| 48 | Sitzplan | KOM-47, TXT-07, W7, W10 | Namen und Antworten je Platz nur im Hover-Tooltip; SVG-Plätze nicht fokussierbar | `js/seating.js:400-417, 516` | B |
| 49 | Zeilen mit „+N“, Ablauf-Segmente, Frist | TXT-07 | Information nur im `title`: weitere Hinweise, Ablaufnamen der Zeitleiste, genaues Fristdatum | `js/rsvp.js:402`, `js/ui.js:334, 631` | C |
| 50 | Anwesenheit (Leitung, 2000) | KOM-27, R66 | Notizen unter Namen brechen auf zwei bis drei Zeilen um („Komme direkt von der Arbeit“) | `orchester/nachher/c-d2-attendance.png` | B |
| 51 | Anwesenheit (Leitung) | KOM-35, R115 | Textzeichen „‹“ als Icon im Button „‹ N ältere“ | `js/screens/attendance.js:941` | C |
| 52 | Anwesenheit, Fehlzeiten | TXT-13, R85 | Abkürzungen „Abw.“, „Reg.“, „Konz.“, „Vorst.“, „GP“, „WE“ | `js/screens/attendance.js:17, 897` | B |
| 53 | Frist, Einstellungen, Navigation | TXT-10, MC §2 | Drift Antwort/Rückmeldung: „Antwort bis“ neben Tooltip „Rückmeldung bis“, „Rückmeldefrist“, „Offene Antworten“ neben „Offene Rückmeldungen“, „Rückmeldung zurücknehmen erlauben“ | `js/ui.js:333-334`, `js/screens/rehearsal-edit.js:382-412`, `js/shell-nav.js:202`, `js/screens/members.js:762`, `js/screens/settings.js:554` | B |
| 54 | Anlegen | TXT-04 | „Probe erstellt“ neben „Probe angelegt“, „Serie erstellen“; Abschluss-Buttons Fertig / Speichern / Anlegen / Übernehmen / Hinzufügen / Anwenden ohne feste Bedeutung; „Zu wenig Zusagen“ / „Zu wenige Zusagen“ / „Wenig Zusagen“ | `js/screens/rehearsals.js:461, 816, 1053`, `js/screens/series.js:256, 468`, `js/screens/rehearsal-edit.js:18, 200`, `js/shell-nav.js:204` | B |
| 55 | Monatskürzel, Import-Fehler | TXT-12, TXT-10 | Monatsnamen als Versalien-Literale „JAN“ … „DEZ“; Importfehler „kein gültiges Termin-Objekt“ | `js/ui.js:427`, `js/screens/ai-import.js:221` | C |
| 56 | CSS gesamt | VIS-33 | rund 40 verschiedene Viewport-Breiten (u. a. 360, 400, 430, 480, 720, 1000, 1100, 1280, 1400, 1440, 1500, 1700, 1900, 2400, 2600) statt 600/840/1200/1600; `tokens.css` nennt selbst 430/600/840/1200/1280/1920 | `css/tokens.css:6, 527-533`, `css/shell-variants.css`, `css/screens/members.css`, `css/screens/team.css`, `js/screens/settings.js:307` | B |
| 57 | CSS gesamt | VIS-10, VIS-26 | 109 Deklarationen mit Rohfarben außerhalb der Token-Dateien (u. a. Hero-Weiß, `#6ee7b7`, `rgb(15 23 42 / .18)`); `seating.css` definiert eigene `--plate-bg`/`--plate-ink` | `css/components.css` (50), `css/screens/team.css` (18), `css/seating.css:8-9` | C |
| 58 | CSS gesamt | VIS-01, VIS-26 | 345 von 436 `font`-Kurzformen mit Rohgröße; Größen ohne Token: 10, 10,5, 18, 19, 21, 23, 26, 30, 40 px | `css/components.css`, `css/shell-variants.css`, `css/screens/members.css`, `css/screens/rows.css` | C |
| 59 | Tokens Typografie | VIS-01, VL | VL-Rollen ohne eigenes Token: Name 15/600, Meta 13/500, Etikett 11/700; `--text-meta` ist 14/400 und weicht von VL „Meta 13/500“ ab | `css/tokens.css:196-211` | C |
| 60 | CSS gesamt | VIS-05 | 993 Abstandsdeklarationen mit Rohwerten; außerhalb des 4-px-Rasters 3, 5, 6, 7, 10, 14, 18 px | `css/components.css` (223), `css/shell-variants.css` (140), `css/screens/team.css` (102) | C |
| 61 | CSS gesamt | VIS-24 | 89 Radien ohne Token, davon ohne Stufe 8, 9, 11, 12, 14 px | `css/components.css` (36), `css/shell-variants.css` (10), `css/screens/me.css` (9) | C |
| 62 | CSS gesamt | KOM-44 | 56 Bewegungen mit Roh-Dauer (u. a. Sheets 200/220/240 ms) und Roh-Kurven `ease`, `ease-out` | `css/components.css:3803-4145`, `css/shell-variants.css`, `css/orchester.css` | C |
| 63 | Plan und Proben (Leitung, Desktop) | R09 | beide Tabs zeigen dieselbe Probenliste mit fast gleicher Zeile; die Aufgabe der beiden Tabs ist für die Leitung nicht in einem Satz unterscheidbar | `proben/nachher/c-plan-2000d-x115.png`, `c-2000d-sel114.png` | B |

---

Zählung: NAV 24, KOM 48, VIS 36, TXT 15, RES 6 Regeln; 63 Abweichungen.

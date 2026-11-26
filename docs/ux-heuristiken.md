# UX-Heuristiken für die Heuristische Evaluation

Bezug: Plattformregeln, Breakpoints, Konsistenz über Breakpoints und Glanceability stehen in `ux-audit-guidelines.md` und werden hier nur referenziert (Kürzel `AG §n`). Owner-Regeln: `ux-owner-regeln.md`.
Alle Quellen am 2026-11-25 abgerufen. Zitierte Zahlen stammen aus den abgerufenen Seiten.
Jede Regel hat eine ID für die Befundtabelle. Rollen: Mitglied (antwortet), Registerführung, Dirigent (verwalten).

---

## 1. Nielsen: 10 Usability-Heuristiken

Quelle für alle: https://www.nngroup.com/articles/ten-usability-heuristics/

**N1 Sichtbarkeit des Systemstatus.** Nutzer stets prompt über den Zustand informieren.
- Reibung, wenn verletzt: Nutzer weiß nicht, ob Antwort ankam, und tippt erneut oder zweifelt.
- Prüfung im Probenplaner: Nach Zusage/Absage Status sofort am selben Element sichtbar (fester Statusplatz); nach Tap erscheint innerhalb 0,1 s eine Reaktion (siehe T11, L5). Aktueller Tab/Segment (Anstehend/Vergangen) und aktive Navigation markiert.

**N2 Übereinstimmung mit der realen Welt.** Sprache und Konzepte der Nutzer, natürliche Reihenfolge.
- Reibung, wenn verletzt: Nutzer übersetzt Systembegriffe in Musikersprache.
- Prüfung im Probenplaner: Begriffe wie Probe, Register, Stimme, Zusage/Absage, Registerführer statt "Event", "Attendee", "Role". Proben chronologisch, nächste zuerst.

**N3 Nutzerkontrolle und Freiheit.** Klar markierter Ausweg aus ungewollten Aktionen ohne langen Weg.
- Reibung, wenn verletzt: Angst vor Fehltipp, Nutzer antwortet gar nicht oder zögerlich.
- Prüfung im Probenplaner: Versehentliche Absage per Rückgängig-Toast oder direktes Umentscheiden in einem Tap; jedes Detail/Sheet/Edit-Formular hat Schließen/Zurück; Rollenvergabe abbrechbar.

**N4 Konsistenz und Standards.** Gleiche Wörter, Aktionen, Situationen; Plattformkonventionen.
- Reibung, wenn verletzt: Nutzer muss je Screen neu lernen.
- Prüfung im Probenplaner: Antwortbuttons (Zusage/Vielleicht/Absage) gleiche Form, Farbe, Reihenfolge in Liste, Detail, Heute-Karte, Desktop. Gleiche Funktion = gleiches Control (AG §5). Verwaltungsaktionen überall gleich benannt.

**N5 Fehlervermeidung.** Probleme verhindern statt melden; Bestätigung bei riskanten Aktionen.
- Reibung, wenn verletzt: Fehlbedienung mit Folgekosten (falsche Absage, falsche Rolle).
- Prüfung im Probenplaner: Absage/Rolle entziehen/Mitglied entfernen/Probe löschen mit Folgetext oder Undo; Antwort-Buttons mit Abstand (kein Fehltreffer auf Nachbarbutton); Datum/Uhrzeit-Eingabe mit Validierung vor Speichern.

**N6 Wiedererkennen statt Erinnern.** Optionen sichtbar machen.
- Reibung, wenn verletzt: Nutzer muss sich Ort/Zustand merken.
- Prüfung im Probenplaner: Eigene Antwort an jeder Probe in der Liste sichtbar, ohne Aufklappen; Detail zeigt Datum/Ort/Stück ohne Rückgriff auf vorherigen Screen; Rollen-Zuweisung zeigt aktuellen Stand.

**N7 Flexibilität und Effizienz.** Abkürzungen für Geübte, ohne Anfänger zu stören.
- Reibung, wenn verletzt: Häufige Aufgabe (10 Proben beantworten) kostet zu viele Schritte.
- Prüfung im Probenplaner: Antwort direkt aus der Liste ohne Detailaufruf; Desktop: Tastaturkürzel, Pfeiltasten durch Liste, Enter öffnet Detail; Dirigent: Mehrfachauswahl bei Anwesenheit.

**N8 Ästhetisches und minimalistisches Design.** Nur Relevantes zeigen.
- Reibung, wenn verletzt: Wichtiges geht im Rauschen unter.
- Prüfung im Probenplaner: Pro Karte nur Datum, Zeit, Ort, eigene Antwort; keine Pillen/Labels ohne Entscheidungswert; ein Akzent. Siehe Owner-Regeln (keine Hairlines, ein Kartentyp).

**N9 Fehler erkennen, diagnostizieren, beheben.** Klartext, Problem benennen, Lösung vorschlagen.
- Reibung, wenn verletzt: Nutzer sitzt vor "Fehler 500" ohne Weg.
- Prüfung im Probenplaner: Netzwerkfehler beim Antworten: Text sagt, was nicht gespeichert wurde, und bietet "Erneut versuchen"; Formularfehler am Feld, nicht nur oben; Push-Berechtigung verweigert: Hinweis mit Weg zur Einstellung.

**N10 Hilfe und Dokumentation.** Kurze, aufgabenbezogene Hilfe, möglichst im Kontext.
- Reibung, wenn verletzt: Nutzer rät bei Rollen/Rechten.
- Prüfung im Probenplaner: Rollen (Mitglied, Registerführung, Dirigent) mit einem Satz erklärt, wo sie vergeben werden; Leerzustände erklären den nächsten Schritt.

### Nielsen Schweregrad (Skala 0 bis 4)

Quelle: https://www.nngroup.com/articles/how-to-rate-the-severity-of-usability-problems/

**S0** "I don't agree that this is a usability problem at all". **S1** kosmetisch, nur bei Zeitreserve. **S2** kleines Problem, niedrige Priorität. **S3** großes Problem, hohe Priorität. **S4** Katastrophe, vor Release zwingend beheben. Faktoren: Häufigkeit, Auswirkung (wie schwer zu überwinden), Persistenz (einmalig oder dauerhaft); dazu Marktwirkung. Details im Prüfablauf.

---

## 2. Norman: Design of Everyday Things

Quellen: https://en.wikipedia.org/wiki/The_Design_of_Everyday_Things und https://ixdf.org/literature/topics/affordances und https://jnd.org/the-design-of-everyday-things-revised-and-expanded-edition/ (Kapitelstruktur). Hinweis: Das Wort "Discoverability" steht nur im Kapiteltitel 4 des Buchs; Definition aus Wikipedia ("Sichtbarkeit": Nutzer sieht Zustand und Handlungsalternativen).

**NO1 Auffindbarkeit (Discoverability).** Zustand und mögliche Aktionen sind durch Hinschauen erkennbar.
- Reibung, wenn verletzt: Funktion existiert für den Nutzer nicht.
- Prüfung im Probenplaner: Aufklappbare Zeilen: erkennt man ohne Probieren, dass sie aufklappen? Verwaltungsfunktionen (Anwesenheit, Rollen) für Dirigent ohne Suchen auffindbar; keine Aktionen hinter "···" auf Desktop (AG §5).

**NO2 Affordanz.** Beziehung zwischen Objekt und Nutzer, die eine Handlung möglich macht.
- Reibung, wenn verletzt: Nutzer weiß nicht, was er tun kann.
- Prüfung im Probenplaner: Antwortbuttons sehen anklickbar aus (Fläche, Kontrast, Zustand); reine Textinfo wirkt nicht wie Button.

**NO3 Signifier.** Wahrnehmbarer Hinweis, der auf Affordanz und Ort der Aktion zeigt.
- Reibung, wenn verletzt: Interaktives ist nicht als solches erkennbar.
- Prüfung im Probenplaner: Chevron am Zeilenende signalisiert Aufklappen; Cursor pointer und Hover-Zustand auf Desktop; Swipe-Gesten haben sichtbare Alternative; Chevron nie direkt am Textfragment.

**NO4 Mapping.** Beziehung zwischen Bedienelement und Wirkung ist natürlich.
- Reibung, wenn verletzt: Nutzer trifft das falsche Element.
- Prüfung im Probenplaner: Antwortbuttons liegen an der Probe, die sie betreffen, nicht an Listenende; Anwesenheitsschalter in der Zeile des Mitglieds; Reihenfolge Zusage/Vielleicht/Absage überall gleich.

**NO5 Feedback.** Vollständige, kontinuierliche Rückmeldung zum Ergebnis jeder Aktion.
- Reibung, wenn verletzt: Gulf of Evaluation, Nutzer prüft selbst nach.
- Prüfung im Probenplaner: Gewählte Antwort sofort hervorgehoben (Zustand auf Button, nicht nur Toast); Ladezustand während Speichern; Bestätigung der Push-Erinnerungs-Einstellung (Vorlaufzeit sichtbar).

**NO6 Constraints.** Physische, kulturelle, semantische, logische Beschränkungen führen zur richtigen Bedienung; Forcing Functions (Interlock, Lock-in, Lock-out) bei Risiko.
- Reibung, wenn verletzt: Ungültige Eingaben werden möglich.
- Prüfung im Probenplaner: Zeit-Picker verhindert Ende vor Beginn; Antwort nach Probenstart/Stichtag gesperrt oder klar markiert; Rolle "Dirigent" nicht ohne Bestätigung entziehbar (letzter Verwalter).

**NO7 Konzeptuelles Modell.** Konsistentes Systembild mit stets gleichen Operationen und Resultaten.
- Reibung, wenn verletzt: Nutzer bildet falsches mentales Modell ("Wer sieht meine Absage?").
- Prüfung im Probenplaner: Klar: Antwort = sichtbar für Register/Dirigent; Absage mit Grund ja/nein einheitlich; Unterschied Probe vs. Termin vs. Konzert nachvollziehbar; Rollen steuern sichtbar, was man sieht.

**NO8 Gulf of Execution und Evaluation.** Lücke zwischen Absicht und erlaubter Aktion bzw. zwischen Zustand und Verständnis.
- Reibung, wenn verletzt: Nutzer weiß nicht, wie er sein Ziel erreicht, oder ob er es erreicht hat.
- Prüfung im Probenplaner: Aufgabe "Absage für die nächste Probe" in unter drei Taps ab Start; danach ist ohne Nachdenken klar, dass sie gespeichert ist.

---

## 3. Shneiderman: Eight Golden Rules

Quelle für alle: https://www.cs.umd.edu/users/ben/goldenrules.html

**SH1 Strive for consistency.** Gleiche Abläufe, Begriffe, visueller Stil; wenige, verständliche Ausnahmen.
- Reibung, wenn verletzt: Lernen pro Screen.
- Prüfung im Probenplaner: Gleiche Karte/Zeile für Probe in Liste, Heute-Card, Verlauf; Dirigent-Ansicht nutzt dieselben Komponenten wie Mitglieder-Ansicht.

**SH2 Seek universal usability.** Anfänger und Experten, Alter, Fähigkeiten, Technik.
- Reibung, wenn verletzt: Teile des Orchesters (ältere Mitglieder, kleine Geräte) scheitern.
- Prüfung im Probenplaner: 200 % Schrift, 375 px, alte Android-Browser, Screenreader-Durchlauf einer Antwort; WCAG-Teil in Abschnitt 7.

**SH3 Offer informative feedback.** Jede Aktion beantworten, klein bei Kleinem, deutlich bei Großem.
- Reibung, wenn verletzt: Stille nach Tap.
- Prüfung im Probenplaner: Antwort: leises Zustandsfeedback; Löschen/Rollenänderung: deutliche Bestätigung.

**SH4 Design dialogs to yield closure.** Handlungsfolgen mit Anfang, Mitte, Ende und Abschlussbestätigung.
- Reibung, wenn verletzt: Offene Schleife, Nutzer weiß nicht, ob fertig.
- Prüfung im Probenplaner: Probe anlegen: Ende erkennbar (Speichern, Rückkehr zur Liste mit hervorgehobener neuer Probe); Rollenvergabe endet mit sichtbarem Ergebnis.

**SH5 Prevent errors.** Schwere Fehler schwer machen; bei Fehlern spezifische Hilfe nur für den fehlerhaften Teil.
- Reibung, wenn verletzt: Fehler mit Folgen, Eingabe wird verworfen.
- Prüfung im Probenplaner: Formular behält Eingaben bei Validierungsfehler; Fehler zeigt nur das betroffene Feld; Massenaktionen (alle Proben absagen) bestätigt.

**SH6 Permit easy reversal of actions.** Aktionen umkehrbar; senkt Angst, fördert Erkunden.
- Reibung, wenn verletzt: Zögern, kein Ausprobieren.
- Prüfung im Probenplaner: Undo nach versehentlicher Absage (zeitlich und per erneutem Tap); gelöschte Probe/Mitglied wiederherstellbar oder deutlich gewarnt.

**SH7 Keep users in control.** Vorhersehbare Reaktion, keine Überraschungen, keine Hürden.
- Reibung, wenn verletzt: Gefühl, vom System gesteuert zu werden.
- Prüfung im Probenplaner: Keine automatischen Seitenwechsel nach Antwort; Scrollposition und aufgeklappte Zeilen bleiben nach Antwort/Reload der Liste erhalten; Push nur nach eigener Einwilligung.

**SH8 Reduce short-term memory load.** Nichts von Screen zu Screen merken müssen; Formulare einseitig.
- Reibung, wenn verletzt: Rückwärtsblättern, Notizzettel.
- Prüfung im Probenplaner: Ort/Zeit/Stück im Antwort-Kontext sichtbar; Anwesenheitsliste zeigt Register-Zugehörigkeit direkt.

---

## 4. Tognazzini: First Principles of Interaction Design

Quelle für alle: https://asktog.com/atc/principles-of-interaction-design/ (die 0,1/1/10-s-Werte stehen dort nur in einer Grafik; Zahlen unten aus Nielsen: https://www.nngroup.com/articles/response-times-3-important-limits/)

**T1 Anticipation.** Alle Information und Werkzeuge für den nächsten Schritt zum Nutzer bringen.
- Reibung, wenn verletzt: Nutzer sucht, was das System wissen könnte.
- Prüfung im Probenplaner: Heute-Card zeigt die nächste unbeantwortete Probe mit Antwortbuttons; Dirigent sieht offene Antworten ohne Filtern.

**T2 Autonomy / Status.** Nutzer entscheidet selbst; Status aktuell, genau, auf einen Blick.
- Reibung, wenn verletzt: Veraltete Zahlen führen zu falschen Entscheidungen.
- Prüfung im Probenplaner: Zähler "12 von 20 zugesagt" aktuell nach jeder Antwort; Zeitstempel/Aktualisierung bei Offline.

**T3 Color.** Farbe immer mit zweitem, nicht-farbigem Signal paaren (ca. 10 % der Männer farbenblind).
- Reibung, wenn verletzt: Grün/Rot für Zusage/Absage ist für Betroffene ununterscheidbar.
- Prüfung im Probenplaner: Zusage/Vielleicht/Absage tragen Icon oder Text zusätzlich zur Farbe; Graustufen-Screenshot gegenprüfen. Siehe AG §1 Farbe nie allein.

**T4 Consistency.** Objekte mit anderem Verhalten sehen anders aus; Nutzererwartung vor Logik.
- Reibung, wenn verletzt: Falsche Erwartung.
- Prüfung im Probenplaner: Löschen sieht anders aus als Absagen (Absage ist umkehrbar, Löschen nicht); Toggle vs. Button nicht vermischt.

**T5 Defaults.** Nur setzen bei vorhersehbarem Gewinner; leicht ersetzbar; Reset-Umfang klar.
- Reibung, wenn verletzt: Falsch vorbelegte Werte werden übersehen.
- Prüfung im Probenplaner: Antwort ist nie vorab "Zusage"; Vorlaufzeit der Push-Erinnerung hat einen benannten Standard; neue Probe: Standardzeit/-ort aus letzter Probe, editierbar.

**T6 Discoverability.** Versteckte Komplexität erhöht Komplexität; Features müssen auffindbar sein; auf großen Screens keine Controls im Inhalt versteckt.
- Reibung, wenn verletzt: Funktion "existiert nicht".
- Prüfung im Probenplaner: Anwesenheit, Rollen, Export in Navigation oder Toolbar sichtbar; Gesten mit sichtbarer Entsprechung.

**T7 Efficiency of the User.** Produktivität des Nutzers messen, nicht der Maschine.
- Reibung, wenn verletzt: Nutzer wartet auf System statt zu arbeiten.
- Prüfung im Probenplaner: Dirigent beantwortet "Wer fehlt?" in unter 5 s; Mitglied beantwortet Probe in unter 5 s ab App-Öffnen.

**T8 Explorable Interfaces.** Klare Wege, Orientierungspunkte, immer Undo, immer Ausweg.
- Reibung, wenn verletzt: Nutzer traut sich nicht zu klicken.
- Prüfung im Probenplaner: Logo/Heute als "Home" erreichbar; Zurück-Verhalten in Detail und Sheets vorhersehbar (Browser-Back, Esc).

**T9 Fitts's Law.** Wichtige Ziele groß und nah; Zielzeit ist Summe der Einzelziele.
- Reibung, wenn verletzt: Fehltreffer, Mehrfachtap.
- Prüfung im Probenplaner: Antwortbuttons auf Mobil im Daumenbereich unten/rechts, mind. 44 px (AG §3 Fluent: 44×44); Desktop: Aktion nahe der Zeile statt am Fensterrand.

**T10 Human-Interface Objects.** Jedes Objekt hat Standardmanipulation und Standardverhalten; neues Verhalten braucht neues Objekt.
- Reibung, wenn verletzt: Papierkorb-Icon für Dauerlöschen etc.
- Prüfung im Probenplaner: Checkbox, Radio, Segment, Toggle nur für ihre Standardfunktion; Anstehend/Vergangen als Segment.

**T11 Latency Reduction.** Jeden Klick innerhalb 50 ms quittieren; Wiederholklicks ignorieren; bei Verzögerung informieren.
- Reibung, wenn verletzt: Doppelabsendungen, Zweifel.
- Prüfung im Probenplaner: Button hat sofortigen Pressed/Pending-Zustand; Doppeltap sendet keine zweite Antwort; Langsames Netz (Throttling "Slow 4G") wird geprüft.

**T12 Learnability.** Nicht nur Erstnutzung testen, auch langfristige Produktivität.
- Reibung, wenn verletzt: Anfängerfreundlich, aber dauerhaft mühsam.
- Prüfung im Probenplaner: Wiederholte Nutzung über Wochen: ist die Antwort-Routine schneller geworden?

**T13 Metaphors.** Metaphern müssen das konzeptuelle Modell stützen; verlassen, wenn sie einengen.
- Reibung, wenn verletzt: Irreführende Bildsprache.
- Prüfung im Probenplaner: Kalender-/Notenpult-Metaphern verändern keine Funktion; Icons nicht doppelt belegt (AG §5).

**T14 Protect Users' Work.** Nutzer verliert nie Arbeit durch Fehler oder Netzwerk.
- Reibung, wenn verletzt: Eingabe weg, Frust, Vertrauensverlust.
- Prüfung im Probenplaner: Offline-Antwort wird gepuffert oder mit Fehler und Retry gemeldet; Probenformular (Notizen, Stücke) behält Entwurf bei Abbruch/Reload.

**T15 Readability.** Hoher Kontrast; Daten größer als Labels; Schlüsselwörter zuerst; Test mit älteren Nutzern.
- Reibung, wenn verletzt: Lesen kostet Anstrengung.
- Prüfung im Probenplaner: Probendatum und Uhrzeit größer als Beschriftung; Buttons "Absagen", nicht "Teilnahme verweigern"; Schriftgröße prüfen mit 200 % Zoom.

**T16 Simplicity.** Keine Scheinvereinfachung; Progressive Revelation für Fortgeschrittenes.
- Reibung, wenn verletzt: Notwendige Funktion fehlt oder alles gleichzeitig sichtbar.
- Prüfung im Probenplaner: Mitglied sieht nur Antworten; Dirigent-Funktionen kommen schrittweise (Detail, Verwaltungsbereich) statt alle auf einer Karte.

**T17 State.** Zustand merken (Webapp ist zustandslos).
- Reibung, wenn verletzt: Nutzer startet immer von vorn.
- Prüfung im Probenplaner: Gewählter Tab/Segment, Filter, Scrollposition nach Detail und Zurück erhalten; Login bleibt bestehen.

**T18 Visible Navigation.** Navigation sichtbar; Mental Maps sind unzuverlässig; Overlays für Teilaufgaben.
- Reibung, wenn verletzt: Nutzer verirrt sich.
- Prüfung im Probenplaner: Aktueller Ort immer markiert (Mobil Tab-Bar, Desktop Sidebar); Teilaufgaben (Antwort mit Grund) in Sheet, nicht eigener Screen.

---

## 5. Gestalt-Prinzipien für UI

**G1 Nähe (Proximity).** Nahes wird als zusammengehörig gelesen. https://www.nngroup.com/articles/gestalt-proximity/
- Reibung, wenn verletzt: Falsche Zuordnung, Nutzer liest Fremdes zusammen.
- Prüfung im Probenplaner: Antwortbuttons näher an ihrer Probe als an der nächsten; Meta (Ort, Zeit) näher am Titel als am Nachbarkartentitel; bei Umbruch auf 375 px Gruppierung neu prüfen.

**G2 Ähnlichkeit (Similarity).** Gleiches Aussehen = gleiche Funktion. https://www.nngroup.com/articles/gestalt-similarity/
- Reibung, wenn verletzt: Zwei Dinge sehen gleich aus, tun Verschiedenes (oder umgekehrt).
- Prüfung im Probenplaner: Tags, Antwortzustände, Rollenbadges gleicher Form nur bei gleicher Bedeutung (AG §4/§5); eine Linkfarbe nur für Klickbares.

**G3 Gemeinsamer Bereich (Common Region).** Was in einer Fläche liegt, gehört zusammen. https://www.nngroup.com/articles/common-region/
- Reibung, wenn verletzt: Zusammengehöriges zerfällt in lose Zeilen.
- Prüfung im Probenplaner: Eine Probe = ein sichtbares Element (Karte/Fläche), Aktionen innerhalb; keine dekorativen Hairlines, Gruppierung per Fläche und Abstand.

**G4 Kontinuität (Continuity).** Elemente auf einer Linie oder Kurve werden als verbunden gelesen. https://ixdf.org/literature/topics/gestalt-principles
- Reibung, wenn verletzt: Blick springt, Tabellenspalten wirken ungeordnet.
- Prüfung im Probenplaner: Desktop-Tabelle: Spalten und Linksbündigkeit sauber ausgerichtet; Mobil: linke Textkante durchgehend, Antwortbuttons auf einer Achse.

**G5 Geschlossenheit (Closure).** Lücken werden mental geschlossen. https://ixdf.org/literature/topics/gestalt-principles
- Reibung, wenn verletzt: Abgeschnittene Inhalte wirken als Fehler.
- Prüfung im Probenplaner: Abgeschnittene Karte am unteren Rand signalisiert Scrollbarkeit absichtlich; keine halb sichtbaren Buttons; Skeletons deuten Form des kommenden Inhalts an.

**G6 Figur und Grund (Figure-Ground).** Hauptobjekt hebt sich vom Hintergrund ab. https://ixdf.org/literature/topics/gestalt-principles
- Reibung, wenn verletzt: Karte verschwindet im Hintergrund, Modals nicht erkennbar.
- Prüfung im Probenplaner: Karten gegen Seite in Hell und Dunkel klar abgesetzt (R79); Sheet/Modal hat Scrim; Heute-Card hebt sich als Figur ab.

**G7 Gemeinsames Schicksal (Common Fate).** Was sich zusammen bewegt, gehört zusammen. https://ixdf.org/literature/topics/gestalt-principles
- Reibung, wenn verletzt: Animationen trennen oder verbinden falsch.
- Prüfung im Probenplaner: Aufklappen verschiebt die Zeile samt Inhalt gemeinsam, folgende Zeilen gleichzeitig; Antwort-Animation bewegt nur das Betroffene; Reduced Motion respektiert.

**G8 Prägnanz.** Mehrdeutiges wird in der einfachsten Form gelesen. https://lawsofux.com/
- Reibung, wenn verletzt: Komplexe Layouts werden falsch zerlegt.
- Prüfung im Probenplaner: Squint-Test (AG §4): Karten, Gruppen und Primäraktion sind unscharf erkennbar.

---

## 6. Laws of UX

Quelle für alle Definitionen: https://lawsofux.com/ (Einzelseiten unten je Gesetz).

**L1 Fitts.** "The time to acquire a target is a function of the distance to and size of the target." https://lawsofux.com/fitts-law/
- Reibung, wenn verletzt: Fehltreffer, Mehraufwand.
- Prüfung im Probenplaner: Primäre Antwortbuttons groß, nahe am Daumen/Zeiger; kleine Ziele wie Chevron haben große Trefferfläche; Siehe T9, W16.

**L2 Hick.** "The time it takes to make a decision increases with the number and complexity of choices." https://lawsofux.com/hicks-law/
- Reibung, wenn verletzt: Zögern, Überforderung.
- Prüfung im Probenplaner: Pro Probe 3 Antwortoptionen, nicht mehr; Verwaltungsmenü gegliedert statt 12 Einträge flach; Rollenauswahl höchstens wenige Stufen.

**L3 Jakob.** "Users spend most of their time on other sites." https://lawsofux.com/jakobs-law/
- Reibung, wenn verletzt: Eigenwilliges Muster zwingt zum Umlernen.
- Prüfung im Probenplaner: Tab-Bar unten (Mobil), Sidebar (Desktop), Segment oben, Pull-to-refresh, Esc schließt Sheet nach Standard; Kalender-Konventionen (Montag zuerst, 24 h).

**L4 Miller / Chunking.** "The average person can only keep 7 (plus or minus 2) items in their working memory." Gruppen in Chunks. https://lawsofux.com/millers-law/ und https://lawsofux.com/chunking/ (Hinweis: Miller ist kein Grund für starre Limits.)
- Reibung, wenn verletzt: Lange ungruppierte Listen.
- Prüfung im Probenplaner: Probenliste nach Monat/Woche gruppiert; Mitgliederliste nach Register gruppiert; Navigation höchstens 5 Ziele (AG §1).

**L5 Doherty-Schwelle.** "Productivity soars when a computer and its users interact at a pace (<400ms)." https://lawsofux.com/doherty-threshold/
- Reibung, wenn verletzt: Wartezeit bricht den Arbeitsfluss.
- Prüfung im Probenplaner: Rückmeldung nach Antwort-Tap unter 400 ms (lokal optimistisch); Navigation zwischen Listen/Details unter 400 ms; sonst Skeleton/Fortschritt. Nielsen-Grenzen: 0,1 s unmittelbar, 1 s Gedankenfluss, 10 s Aufmerksamkeitsgrenze (https://www.nngroup.com/articles/response-times-3-important-limits/).

**L6 Ästhetik-Usability-Effekt.** "Users often perceive aesthetically pleasing design as design that's more usable." https://lawsofux.com/aesthetic-usability-effect/
- Reibung, wenn verletzt: Schöne Oberfläche verdeckt reale Probleme bei der Evaluation (Gegenrisiko!).
- Prüfung im Probenplaner: Evaluation nach Aufgaben, nicht nach Gefallen; bei gutem Eindruck trotzdem Zeit und Fehler messen.

**L7 Von Restorff.** "...when multiple similar objects are present, the one that differs from the rest is most likely to be remembered." https://lawsofux.com/von-restorff-effect/
- Reibung, wenn verletzt: Alles hervorgehoben, nichts fällt auf.
- Prüfung im Probenplaner: Nur eine Hervorhebung pro Screen (z. B. unbeantwortete nächste Probe, Heute-Card); Akzentfarbe nicht auf mehreren Controls (AG §1).

**L8 Serial Position.** "Users have a propensity to best remember the first and last items in a series." https://lawsofux.com/serial-position-effect/
- Reibung, wenn verletzt: Mittlere Einträge werden übersehen.
- Prüfung im Probenplaner: Wichtigste Nav-Ziele am Anfang und Ende der Leiste; Wichtigste Antwort (Zusage) nicht in der Mitte versteckt; Primäraktion an Listenrand.

**L9 Tesler.** "...for any system there is a certain amount of complexity which cannot be reduced." https://lawsofux.com/teslers-law/
- Reibung, wenn verletzt: Komplexität landet beim Nutzer statt beim System.
- Prüfung im Probenplaner: Rechte, Register, Vertretungen werden vom System abgeleitet, nicht manuell eingegeben; Dirigent muss keine Doppel-Pflege betreiben.

**L10 Postel.** "Be liberal in what you accept, and conservative in what you send." https://lawsofux.com/postels-law/
- Reibung, wenn verletzt: Formulare lehnen gültige Varianten ab.
- Prüfung im Probenplaner: Uhrzeit "19", "19:00", "19 Uhr" werden akzeptiert; Telefonnummer mit Leerzeichen; Exporte/Push-Texte sauber normiert.

**L11 Goal-Gradient.** "The tendency to approach a goal increases with proximity to the goal." https://lawsofux.com/goal-gradient-effect/
- Reibung, wenn verletzt: Mehrschritt-Aufgaben wirken endlos.
- Prüfung im Probenplaner: Anlegen einer Probe oder Onboarding mit Schrittanzeige; Antwortquote "12 von 20" zeigt Fortschritt zum Ziel, ohne Druck zu erzeugen.

**L12 Peak-End.** "People judge an experience largely based on how they felt at its peak and at its end" https://lawsofux.com/peak-end-rule/
- Reibung, wenn verletzt: Eine schlechte Erfahrung (Fehlertext, Verlust) prägt das Urteil.
- Prüfung im Probenplaner: Erfolgsmoment nach Antwort ruhig positiv; Fehler- und Leerzustände freundlich und mit Ausweg; Abschluss von Aufgaben (Probe speichern) ohne Reibung.

---

## 7. WCAG 2.2 Level AA (relevante Kriterien)

Quelle für alle: https://www.w3.org/TR/WCAG22/ (Anker je Kriterium). Zahlen aus der abgerufenen Spezifikation. Hinweis: SC 2.5.5 (44×44, AAA) ist nicht AA.

**W1 Kontrast Text, 1.4.3 (AA).** Text 4,5:1, großer Text 3:1. #contrast-minimum
- Reibung, wenn verletzt: Text unlesbar bei Sonne, schwachem Sehen.
- Prüfung im Probenplaner: Sekundärtext (Ort, Meta), Platzhalter, Disabled-Text, Text auf Akzentfläche, Hell und Dunkel; Hilfsmittel: axe-core Regel `color-contrast`.

**W2 Kontrast Nicht-Text, 1.4.11 (AA).** UI-Komponenten und grafische Objekte mind. 3:1. #non-text-contrast
- Reibung, wenn verletzt: Grenzen von Feldern, Toggles, Icons verschwinden.
- Prüfung im Probenplaner: Inaktive vs. aktive Antwortbuttons, Fokusring, Toggle, Icon-Buttons gegen Karte und Seite. Owner-Regel "keine Hairlines" erfordert Fläche statt Rand; Flächenkontrast gegen Hintergrund trotzdem 3:1 für das Control selbst.

**W3 Farbe nicht allein, 1.4.1 (A).** Information nicht nur durch Farbe. #use-of-color
- Reibung, wenn verletzt: Farbenblinde erkennen Zustand nicht.
- Prüfung im Probenplaner: Zusage/Vielleicht/Absage, Überfälliges, Fehlerfelder mit Icon oder Text; siehe T3.

**W4 Reflow, 1.4.10 (AA).** Kein 2D-Scrollen bei 320 CSS px Breite (bzw. 256 px Höhe). #reflow
- Reibung, wenn verletzt: Horizontales Hin- und Herschieben.
- Prüfung im Probenplaner: Alle Screens bei 320 px und bei 400 % Zoom auf 1280 px; Tabellen (Anwesenheit) umbrechen oder scrollen nur in sich.

**W5 Textgröße, 1.4.4 (AA).** Bis 200 % ohne Verlust. #resize-text
- Reibung, wenn verletzt: Abgeschnittene Texte, überlappende Buttons.
- Prüfung im Probenplaner: Browser-Textzoom 200 %: Antwortbuttons, Tab-Bar, Heute-Card intakt.

**W6 Textabstände, 1.4.12 (AA).** Zeilenhöhe 1,5, Absatz 2, Buchstabe 0,12, Wort 0,16 (je × Schriftgröße) ohne Verlust. #text-spacing
- Reibung, wenn verletzt: Text läuft aus Karten.
- Prüfung im Probenplaner: Text-Spacing-Bookmarklet auf Probenkarte und Formular.

**W7 Inhalt bei Hover/Fokus, 1.4.13 (AA).** Tooltips schließbar, überfahrbar, beständig. #content-on-hover-or-focus
- Reibung, wenn verletzt: Tooltip verdeckt oder verschwindet.
- Prüfung im Probenplaner: Desktop-Tooltips (Icon-Buttons) mit Esc schließbar; keine Info nur im Tooltip (AG §4 Icons mit Label).

**W8 Orientierung, 1.3.4 (AA).** Hoch- und Querformat. #orientation
- Reibung, wenn verletzt: Gesperrte Orientierung.
- Prüfung im Probenplaner: Mobil quer: Antworten und Navigation bedienbar.

**W9 Info und Beziehungen, 1.3.1 (A).** Struktur im Markup, nicht nur visuell. #info-and-relationships
- Reibung, wenn verletzt: Screenreader hört flachen Textbrei.
- Prüfung im Probenplaner: Überschriften, Listen für Proben, Tabellen mit `th`; Formular-Gruppen mit `fieldset`/`legend`; axe: `list`, `heading-order`, `th-has-data-cells`.

**W10 Tastatur, 2.1.1 (A).** Alles per Tastatur bedienbar. #keyboard
- Reibung, wenn verletzt: Desktop-Nutzer ohne Maus gesperrt.
- Prüfung im Probenplaner: Antworten, Aufklappen, Sheets, Rollen, Segment per Tab/Enter/Space/Pfeile; keine nur-Hover-Aktionen.

**W11 Keine Tastaturfalle, 2.1.2 (A).** Fokus verlässt jede Komponente. #no-keyboard-trap
- Reibung, wenn verletzt: Fokus gefangen.
- Prüfung im Probenplaner: Sheet/Modal: Fokus gefangen innerhalb nur solange offen, Esc schließt, Fokus kehrt zum Auslöser zurück.

**W12 Fokusreihenfolge, 2.4.3 (A).** Reihenfolge erhält Bedeutung. #focus-order
- Reibung, wenn verletzt: Springender Fokus.
- Prüfung im Probenplaner: Tab-Reihenfolge Titel, Meta, Antwortbuttons, Chevron; nach Aufklappen folgt Fokus dem neuen Inhalt logisch.

**W13 Überschriften und Labels, 2.4.6 (AA).** Beschreiben Zweck. #headings-and-labels
- Reibung, wenn verletzt: Mehrdeutige Beschriftung.
- Prüfung im Probenplaner: "Absagen" statt "Nein"; Überschrift pro Screen eindeutig.

**W14 Fokus sichtbar, 2.4.7 (AA).** Tastaturfokus erkennbar. #focus-visible
- Reibung, wenn verletzt: Nutzer weiß nicht, wo er ist.
- Prüfung im Probenplaner: Fokusring auf jedem Control, Hell und Dunkel, mit 3:1 (W2); Owner-Regel "keine Hairlines" betrifft Dekoration, nicht den Fokusring.

**W15 Fokus nicht verdeckt, 2.4.11 (AA).** Fokussiertes Element nicht komplett von Autoreninhalt verdeckt. #focus-not-obscured-minimum
- Reibung, wenn verletzt: Fokus verschwindet unter Sticky-Elementen.
- Prüfung im Probenplaner: Sticky Header, Tab-Bar, Snackbar/Undo-Toast und Cookie-Banner verdecken den fokussierten Antwortbutton nicht (Tab durch Liste, unterste Zeile).

**W16 Zielgröße, 2.5.8 (AA).** Mind. 24×24 CSS px, Ausnahmen Abstand, Äquivalent, Inline, User-Agent, Essential. #target-size-minimum
- Reibung, wenn verletzt: Fehltreffer, vor allem mit Zittern oder Handschuh.
- Prüfung im Probenplaner: Chevron, Icon-Buttons, Chips, Checkboxen in Anwesenheitsliste messen (DevTools). Projektziel Touch 44 px (AG §3).

**W17 Zeigergesten, 2.5.1 (A) und Ziehen, 2.5.7 (AA).** Mehrfinger/Pfad-Gesten und Ziehen brauchen Einzelzeiger-Alternative. #pointer-gestures #dragging-movements
- Reibung, wenn verletzt: Wischgeste ist der einzige Weg.
- Prüfung im Probenplaner: Swipe-zum-Absagen oder Drag-and-drop (Sortierung, Rollenzuweisung) hat Button-Alternative.

**W18 Zeigerabbruch, 2.5.2 (A).** Ausführung beim Loslassen oder umkehrbar. #pointer-cancellation
- Reibung, wenn verletzt: Aktion löst bei Berührungsbeginn aus.
- Prüfung im Probenplaner: Antwort löst bei `click`/Up aus; Wegziehen vom Button bricht ab.

**W19 Label im Namen, 2.5.3 (A).** Zugänglicher Name enthält sichtbaren Text. #label-in-name
- Reibung, wenn verletzt: Sprachsteuerung ("Klick Absagen") funktioniert nicht.
- Prüfung im Probenplaner: Buttons mit sichtbarem Text haben `aria-label` nur, wenn es den Text enthält; axe: `label-content-name-mismatch`.

**W20 Bei Fokus/Eingabe, 3.2.1 und 3.2.2 (A).** Kein unerwarteter Kontextwechsel. #on-focus #on-input
- Reibung, wenn verletzt: Seite springt beim Tippen oder Fokussieren.
- Prüfung im Probenplaner: Registerwahl oder Filter lösen keinen Seitenwechsel ohne Ankündigung aus.

**W21 Konsistente Navigation, 3.2.3 (AA).** Wiederkehrende Navigation in gleicher Reihenfolge. #consistent-navigation
- Reibung, wenn verletzt: Menü wandert zwischen Screens.
- Prüfung im Probenplaner: Tab-Bar/Sidebar identisch auf allen Screens und Rollen (Verwaltungsziele nur ergänzt, Reihenfolge stabil); AG §5.

**W22 Konsistente Identifikation, 3.2.4 (AA).** Gleiche Funktion, gleiche Bezeichnung und gleiches Icon. #consistent-identification
- Reibung, wenn verletzt: Gleiche Funktion unter anderem Namen.
- Prüfung im Probenplaner: "Absagen" nie auch "Nicht dabei"; Icon für Drucken/Export/Bearbeiten identisch auf Mobil und Desktop.

**W23 Konsistente Hilfe, 3.2.6 (A).** Hilfemechanismen an gleicher Stelle. #consistent-help
- Reibung, wenn verletzt: Hilfe nicht auffindbar.
- Prüfung im Probenplaner: Kontakt/Hilfe-Link an gleichem Ort.

**W24 Labels oder Anweisungen, 3.3.2 (A).** Bei Eingabe Label oder Anweisung. #labels-or-instructions
- Reibung, wenn verletzt: Leeres Feld ohne Bedeutung, Placeholder verschwindet.
- Prüfung im Probenplaner: Jedes Feld (Probe anlegen, Absagegrund, Vorlaufzeit) hat sichtbares Label, nicht nur Placeholder; Pflichtfelder markiert; axe: `label`.

**W25 Fehler erkennen, 3.3.1 (A) und Fehlervorschlag, 3.3.3 (AA).** Fehler in Text benennen, Korrektur vorschlagen. #error-identification #error-suggestion
- Reibung, wenn verletzt: Nutzer rät.
- Prüfung im Probenplaner: Validierung nennt Feld und Regel ("Ende muss nach Beginn liegen"); nicht nur roter Rand.

**W26 Fehlervermeidung, 3.3.4 (AA).** Bei Daten ändernden Eingaben: umkehrbar, geprüft oder bestätigt. #error-prevention-legal-financial-data
- Reibung, wenn verletzt: Unumkehrbarer Datenverlust.
- Prüfung im Probenplaner: Löschen von Probe/Mitglied und Rollenänderung erfüllen mindestens eines: Undo, Prüfung, Bestätigung.

**W27 Redundante Eingabe, 3.3.7 (A).** Bereits Eingegebenes nicht erneut verlangen. #redundant-entry
- Reibung, wenn verletzt: Doppelte Eingabe.
- Prüfung im Probenplaner: Wiederkehrende Absagegründe oder Standardort aus Vorwerten vorschlagen; Profildaten nicht erneut abfragen.

**W28 Name, Rolle, Wert, 4.1.2 (A).** Name und Rolle programmatisch ermittelbar, Zustände setzbar und meldbar. #name-role-value
- Reibung, wenn verletzt: Screenreader nennt "Schaltfläche" ohne Namen oder Zustand.
- Prüfung im Probenplaner: Antwortbuttons als Gruppe mit `aria-pressed` oder Radiogruppe, aufklappbare Zeilen mit `aria-expanded`, Segment mit `aria-selected` oder `aria-current`; axe: `button-name`, `aria-valid-attr-value`, `aria-required-attr`.

**W29 Statusmeldungen, 4.1.3 (AA).** Meldungen ohne Fokus für Hilfstechnik wahrnehmbar. #status-messages
- Reibung, wenn verletzt: Blinde erfahren nicht, ob Antwort gespeichert ist.
- Prüfung im Probenplaner: Toast/Undo-Hinweis und "Gespeichert"/Fehlermeldung in `role="status"` oder `aria-live`; Fehler `role="alert"`.

**W30 Authentifizierung, 3.3.8 (AA).** Kein kognitiver Funktionstest erforderlich ohne Alternative. #accessible-authentication-minimum
- Reibung, wenn verletzt: Passwort merken/Puzzle ohne Alternative.
- Prüfung im Probenplaner: Login erlaubt Paste und Passwortmanager; kein Captcha-Puzzle ohne Alternative.

---

## 8. Plattform: Apple HIG und Material 3 (Ergänzung zu `ux-audit-guidelines.md`)

Nicht doppelt: Tab-Bar, Sidebar, Size Classes, Farbe, Typo, Icons, Breakpoints, Navigation, Command Bar siehe AG §1 bis §3. Apple-Texte aus den HIG-JSON-Quellen (`developer.apple.com/tutorials/data/design/human-interface-guidelines/<seite>.json`), Material aus Material-Components-Docs.

**P1 Alerts sparsam (HIG).** Nur für Wesentliches und Entscheidungen; keine reine Info; keine Alerts für häufige, umkehrbare Aktionen. https://developer.apple.com/design/human-interface-guidelines/alerts
- Reibung, wenn verletzt: Dialogmüdigkeit, Nutzer klickt blind durch.
- Prüfung im Probenplaner: Zusage/Absage ohne Dialog (Undo statt Bestätigung); Bestätigung nur für Unumkehrbares (Probe löschen, Mitglied entfernen).

**P2 Alert-Text und Buttons (HIG).** Titel beschreibt Situation, nicht "Fehler"; bis zu drei Buttons; Cancel bei zerstörerischer Aktion. Zerstörerischer Stil nur, wenn die Aktion nicht gezielt gewählt wurde. https://developer.apple.com/design/human-interface-guidelines/alerts
- Reibung, wenn verletzt: Unklar, was passiert.
- Prüfung im Probenplaner: Dialogtitel "Probe am 12.3. löschen?", Buttons "Löschen" und "Abbrechen" (nicht "OK/Ja"); Löschen nicht als Enter-Standard.

**P3 Feedback an Wichtigkeit koppeln (HIG).** Status passiv, Datenverlust-Warnung unterbrechend; Bestätigung nur bei wichtigen Abschlüssen; Befehl, der nicht geht, erklären. https://developer.apple.com/design/human-interface-guidelines/feedback
- Reibung, wenn verletzt: Alles klingt gleich laut.
- Prüfung im Probenplaner: Antwort-Erfolg leise (Zustand im Control), Fehlschlag laut; gesperrte Antwort (Probe vorbei) erklärt warum.

**P4 Undo vorhersagbar (HIG).** Ergebnis von Undo benennen und hervorheben; mehrfaches Undo; Standardgesten nicht umdeuten. https://developer.apple.com/design/human-interface-guidelines/undo-and-redo
- Reibung, wenn verletzt: Undo greift Unerwartetes oder Wirkung bleibt unsichtbar.
- Prüfung im Probenplaner: Toast "Absage zurückgenommen" benennt die Probe; Nach Undo scrollt/markiert die Liste die betroffene Probe; Ctrl+Z auf Desktop nicht belegt mit Eigenem.

**P5 Laden: sofort etwas zeigen (HIG).** Platzhalter statt leerer Bildschirm; Hintergrund laden; bestimmter Fortschritt bevorzugt. https://developer.apple.com/design/human-interface-guidelines/loading und https://developer.apple.com/design/human-interface-guidelines/progress-indicators
- Reibung, wenn verletzt: Leere wird als Defekt gelesen.
- Prüfung im Probenplaner: Skeleton in Form der Probenkarte; Spinner an konsistenter Stelle; nicht vom Spinner zum Balken wechseln; keine Fortschrittsanzeige für Aktionen unter 1 s.

**P6 Benachrichtigungen (HIG).** Knapp, informativ, keine Mehrfachbenachrichtigung für dasselbe, keine Aufgabenanweisung, keine sensiblen Daten, Berechtigung im Kontext, Verweigerung gnädig behandeln. https://developer.apple.com/design/human-interface-guidelines/notifications
- Reibung, wenn verletzt: Nutzer schaltet Push insgesamt aus.
- Prüfung im Probenplaner: Push-Erinnerung genau einmal je Probe zur eingestellten Vorlaufzeit; Text nennt Probe, Zeit, Ort; keine Anrede mit Daten Dritter; Einstellung der Vorlaufzeit pro Nutzer auffindbar, Bestätigung nach Änderung sichtbar; Push öffnet die richtige Probe.

**P7 Dynamic Type und Mindestgröße (HIG).** Text mind. auf 200 % vergrößerbar; iOS Standard 17 pt, Minimum 11 pt. https://developer.apple.com/design/human-interface-guidelines/accessibility
- Reibung, wenn verletzt: Zu kleiner Text.
- Prüfung im Probenplaner: Kleinster Text (Meta, Tags) nicht unter 11 px-Äquivalent, Schriftzoom 200 % (siehe W5); Eingabefelder mind. 16 px gegen iOS-Zoom.

**P8 Reduce Motion (HIG).** Bei aktivierter Einstellung automatische Animationen reduzieren. https://developer.apple.com/design/human-interface-guidelines/accessibility
- Reibung, wenn verletzt: Schwindel, Ablenkung.
- Prüfung im Probenplaner: `prefers-reduced-motion`: Aufklappen/Übergänge ohne Bewegung, Zustandswechsel bleibt erkennbar.

**P9 Snackbar (Material).** Kurze temporäre Meldung nahe der Aktion; nicht unterbrechend; höchstens eine Aktion (Undo/Retry); nur eine zugleich; nicht für Kritisches. https://github.com/material-components/material-components-android/blob/master/docs/components/Snackbar.md
- Reibung, wenn verletzt: Meldung verschwindet, bevor sie gelesen wird, oder stapelt sich.
- Prüfung im Probenplaner: Absage-Undo als einzelner Snackbar; nach schneller Folge von Antworten ersetzt die neue die alte (Undo-Ziel eindeutig); Dauer lang genug zum Lesen und Treffen; Tastatur/Screenreader erreichbar (W15, W29).

**P10 Dialog sparsam (Material).** Dialoge sind bewusst unterbrechend und blockieren; für Dringendes. https://github.com/material-components/material-components-android/blob/master/docs/components/Dialog.md
- Reibung, wenn verletzt: Unnötige Unterbrechungen.
- Prüfung im Probenplaner: Keine Dialoge für Informatives; mehrstufige Aufgaben (Probe anlegen) als Vollbild-Sheet, nicht gestapelte Dialoge.

**P11 Beschriftung und Gruppen für Hilfstechnik (Android).** Eindeutige Labels in Listen; verwandte Elemente zu einer Einheit zusammenfassen; Wisch-/Long-Press-Aktionen als benannte Aktionen anbieten; Hinweis: Material-Zahlen (48 dp Ziel) wurden nicht abgerufen. https://developer.android.com/guide/topics/ui/accessibility/principles
- Reibung, wenn verletzt: Screenreader liest jede Probe als Fragmente.
- Prüfung im Probenplaner: Jede Probenkarte wird als eine Einheit angesagt ("Probe Dienstag 19:00 Aula, Zusage"); Swipe/Long-Press-Aktionen mit sichtbarem Button-Äquivalent.

---

## 9. Prüfablauf

Quelle: https://www.nngroup.com/articles/how-to-conduct-a-heuristic-evaluation/ und https://www.nngroup.com/articles/how-to-rate-the-severity-of-usability-problems/

### 9.1 Ablauf pro Screen und Zustand

1. Umfang festlegen (Rolle, Gerät, Aufgabe). Rollen: Mitglied, Registerführung, Dirigent. Geräte: 375 px, 768 px, 1280 px, 2000 px, 3440 px (Hell und Dunkel).
2. Zustandsliste je Screen: Standard, Hover, Fokus (Tastatur), gedrückt/pending, gespeichert, Fehler, Leer, Laden, Offline, aufgeklappt, nach Undo, Sheet/Modal offen, 200 % Zoom, Reduced Motion.
3. Pass 1 (Familiarisierung, ohne Bewertung): Aufgaben einmal durchspielen: Probe beantworten, Zeile aufklappen, Absage zurücknehmen, Push-Vorlaufzeit ändern, Rolle vergeben, Anwesenheit führen, per Tastatur auf Desktop.
4. Pass 2 (Bewertung): je Screen und Zustand alle Regel-IDs aus Abschnitt 1 bis 8 durchgehen; jede Verletzung genau einer Primär-ID zuordnen (weitere in Spalte "Auch").
5. Pro Befund Screenshot (Pfad im Ordner `prototype-verlauf/` des geprüften Standes), Reproduktionsschritte, betroffene Rolle.
6. Ideal: 3 bis 5 unabhängige Prüfer, je 1 bis 2 Stunden, Befunde erst nach eigener Runde teilen (NN/g); im Projekt mindestens ein Prüfer pro Rolle plus Tastatur/Screenreader-Durchlauf.
7. Aggregation: Doppelte Befunde zusammenführen (Affinity-Clustering), Schweregrad abstimmen, Median nehmen.
8. Fixes erst nach Aggregation; danach betroffene Screens und angrenzende (nicht kritisierte) neu prüfen, Vorher/Nachher nebeneinander.

### 9.2 Schweregrad (Nielsen, 0 bis 4)

| Stufe | Bedeutung (Nielsen) | Faustregel im Projekt |
|---|---|---|
| 0 | Kein Usability-Problem | Verworfen, nicht dokumentiert |
| 1 | Kosmetisch, nur bei Zeitreserve beheben | Optik, selten gesehen |
| 2 | Kleines Problem, niedrige Priorität | Umweg, aber selbsterklärend |
| 3 | Großes Problem, hohe Priorität | Häufige Aufgabe stockt oder Fehler ohne leichten Ausweg |
| 4 | Katastrophe, vor Release zwingend | Antwort geht verloren, Datenverlust, Aufgabe unmöglich (z. B. per Tastatur) |

Bestimmen aus drei Faktoren: Häufigkeit (wie oft), Auswirkung (wie schwer zu überwinden), Persistenz (einmalig oder dauerhaft); dazu Marktwirkung. Hoch bei allen drei = 4.

### 9.3 Befundtabelle

| ID | Screen | Zustand | Rolle/Gerät | Prinzip (ID) | Befund (ein Satz, beobachtbar) | Schweregrad 0 bis 4 | Screenshot |
|---|---|---|---|---|---|---|---|
| F-001 | Probenliste | Zeile aufgeklappt | Mitglied, 375 px | N1, NO5 | Nach Tap auf "Absagen" zeigt die Zeile keinen Zustand bis zur Serverantwort | 3 | `prototype-verlauf/<Stand>/liste-aufgeklappt-375.png` |

Regeln: ein Befund pro Zeile, Beobachtung statt Meinung, keine Lösung in der Befundzeile, Schweregrad vor dem Fix vergeben.

### 9.4 Automatisierter WCAG-Teil: axe-core

Quellen: https://github.com/dequelabs/axe-core/blob/develop/doc/API.md und https://playwright.dev/docs/accessibility-testing

Rule-Tags (jede Stufe einzeln nennen, `wcag2aa` enthält `wcag2a` nicht): `wcag2a`, `wcag2aa`, `wcag21a`, `wcag21aa`, `wcag22aa`. Optional `best-practice` als getrennter Lauf. Automatik deckt nur einen Teil von WCAG ab (Playwright-Doku: nicht alle Verstöße erkennbar); Tastatur, Fokusreihenfolge, Zielgröße im Kontext, Statusmeldungen und Verständlichkeit bleiben manuell (W10 bis W17, W29).

Variante A, Playwright (empfohlen):

```
npm i -D @playwright/test @axe-core/playwright
npx playwright install chromium
```

```ts
import { test } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

const tags = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

test('axe Probenliste 375', async ({ page }) => {
  await page.setViewportSize({ width: 375, height: 812 });
  await page.goto('http://localhost:5173');
  const r = await new AxeBuilder({ page }).withTags(tags).analyze();
  console.log(JSON.stringify(r.violations.map(v => ({ id: v.id, impact: v.impact, nodes: v.nodes.map(n => n.target) })), null, 2));
});
```

Je Screen und Zustand wiederholen: Zustand zuerst herstellen (aufklappen, Sheet öffnen, Fehler auslösen, Dark via `page.emulateMedia({ colorScheme: 'dark' })`), dann `analyze()`. Viewports 375 und 1280 und 3440.

Variante B, Chrome DevTools Protocol (ohne Test-Runner): Chrome mit `--remote-debugging-port=9222` starten, per CDP `Runtime.evaluate` die axe-Datei (`node_modules/axe-core/axe.min.js`) in die Seite injizieren und ausführen:

```js
await axe.run(document, {
  runOnly: { type: 'tag', values: ['wcag2a','wcag2aa','wcag21a','wcag21aa','wcag22aa'] },
  resultTypes: ['violations', 'incomplete']
});
```

Auswertung: `violations` sind Befunde; `incomplete` manuell prüfen (z. B. Kontrast über Bildern). Schwere `impact` (minor, moderate, serious, critical) ist ein Hinweis; den Nielsen-Grad trotzdem selbst vergeben. Ergebnis je Regel als Zeile in die Befundtabelle (Prinzip = passende W-ID, Screenshot = Seitenansicht des Zustands).

Rule-Zuordnung (Auswahl): `color-contrast` W1; `button-name`, `aria-*` W28; `label` W24; `label-content-name-mismatch` W19; `target-size` W16 (Kandidat, Ergebnis manuell bestätigen); `list`, `heading-order` W9.

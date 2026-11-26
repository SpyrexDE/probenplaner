# UX-Audit: Referenz-Apps für "in unter einer Sekunde verstehbar" und eigenen Charakter

Stand der Recherche: 25.11.2026. Methode: Websuche, Apple-Developer-Artikel, Designkritiken, App-Store-Seiten.

Kennzeichnung der Belegqualität:
- **[belegt]** steht in einer genannten Quelle (Apple, Hersteller, Kritik).
- **[Drittquelle]** nur in einem Blog/Leitfaden. Die Seiten von blakecrosley.com sind Analysen eines Dritten mit teils illustrativem Code und Hex-Werten, keine offiziellen Spezifikationen.
- **[nicht verifiziert]** Annahme aus Allgemeinwissen, keine Quelle gefunden. Vor Übernahme per Screenshot prüfen.

Screenshots selbst wurden nicht angesehen, nur Texte gelesen. Die Beschreibungen "was ohne Text sichtbar ist" stützen sich daher auf Textbeschreibungen.

---

## 1. Flighty (iPhone, iPad; Apple Design Award 2023, Kategorie Interaction)

- **Ohne Text:** Horizontale Routenlinie Start-Ziel mit gefülltem Abschnitt bis zur aktuellen Position [belegt: Apple, Drittquelle]. Kreis-Fortschritt in der Dynamic Island. Statusfarbe: grün pünktlich, gelb/amber Verspätung, rot Annullierung [Drittquelle]. Boarding als Segmentbalken [Drittquelle]. Große Zähler/Uhrzeiten.
- **Charakter:** Leitmetapher Flughafen-Anzeigetafel, eine Zeile pro Flug [belegt]. Ziel laut Apple "almost boringly obvious" [belegt]. Monospace-Ziffern für Zeiten, Flugnummern [Drittquelle]. Passport-Funktion macht Statistiken zu teilbarer Grafik [belegt].
- **Phone/Desktop:** Fokus iPhone (Live Activity, Lock Screen, Dynamic Island). iPad existiert, Layoutunterschiede nicht belegt [nicht verifiziert].
- **Übertragbar:** Probe als "Abflug-Zeile": Datum groß, Beginn in Monospace, darunter eine Linie mit Fortschritt "bis zur Probe" (Countdown als gefüllter Balken). Zustände (zugesagt, offen, Probe läuft, vorbei) bekommen je eigene Kartenform statt nur Etiketten.
- **URLs:** https://developer.apple.com/news/?id=970ncww4 · https://blakecrosley.com/guides/design/flighty

## 2. Things 3 (Mac, iPhone, iPad; Apple Design Award 2017, Mac schon 2009)

- **Ohne Text:** Etwa 95 % neutral, Farbe nur mit Bedeutung: Gelb = Heute, Rot = Deadline, Indigo = Abend, Blau = Tags, Grün = erledigt [Drittquelle]. Kreis-Checkbox füllt sich im Uhrzeigersinn, dann Haken [Drittquelle]. Hierarchie Bereich > Projekt > Überschrift über Position/Einrückung.
- **Charakter:** Radikale Zurückhaltung, "unrivaled simplicity" [belegt: Apple Story]. Magic-Plus-Button, der sich per Drag an jede Stelle setzen lässt [belegt, Store]. Eigene Animationen und Drag-Interaktionen [belegt: Dice]. Zeitpunkt und Abschlussgeräusch/Haptik [Drittquelle].
- **Phone/Desktop:** Mac mit Tastaturkürzeln (Quick Entry per globalem Hotkey) [Drittquelle], iOS mit Haptik. Layoutunterschiede nicht im Detail belegt.
- **Übertragbar:** Zusage als Kreis, der sich beim Antippen füllt (Ja = gefüllt, Nein = durchgestrichener Kreis, offen = leerer Ring). Ein einziges Gelb für "Heute". Der Rest bleibt ruhig, damit Farbe sofort Bedeutung trägt.
- **URLs:** https://blakecrosley.com/blog/design-study-things · https://apps.apple.com/us/story/id1390028519

## 3. Fantastical (Mac, iPhone, iPad, Watch; Editors' Choice im App Store)

- **Ohne Text:** Farbige Kalenderfarben, Emoji-Tags an Terminen, Wetter-Symbole im Tag [belegt: Store/Review]. Große Monatsbeschriftung und dicke Tagestrenner im neuen Design [belegt: MacStories]. DayTicker (Zeitleiste der Tage) [belegt: MacStories, Details nicht verifiziert].
- **Charakter:** Natürlichsprachliche Eingabe ("Mittagessen morgen um 1") als Signatur [belegt]. Kalender-Sets (Gruppen ein-/ausschalten, auch ortsabhängig) [belegt]. Kräftige, scanbare Seite statt dünner Linien.
- **Phone/Desktop:** Mac mit Menüleisten-Mini-Fenster und vielen Widgets, iPhone/Watch mit Up Next [belegt, Store]. Einzelheiten der Layouts [nicht verifiziert].
- **Übertragbar:** Eingabe und Anzeige verbinden: Probenplan als "Tagesband" mit Emoji/Icon je Probenart (Tutti, Register, Konzert). Kalender-Sets = Filter "Meine Gruppe / alle".
- **URLs:** https://www.macstories.net/reviews/the-new-fantastical-review/ · https://apps.apple.com/app/id975937182

## 4. Notion Calendar (früher Cron; Mac, Windows, iOS)

- **Ohne Text:** Termine als weich gefüllte Blöcke auf einem Zeitraster (Länge = Dauer, Position = Uhrzeit) [Drittquelle]. Acht Farbtöne als Kategorie. Zweite Zeitzone als Spalte am Rand [belegt: mehrere Reviews].
- **Charakter:** Extremer Größenkontrast zwischen riesiger Überschrift (64 px) und winzigen Labels, kaum Farbe oder Rahmen [Drittquelle]. Eine Schrift (Inter-Fork), Monospace-Ziffern in der Zeitspalte [Drittquelle]. Tastaturbedienung (n, g, t, /, Cmd+P) [Drittquelle/Reviews].
- **Phone/Desktop:** Desktop-first [belegt: Review]. Mobile eher Ableger.
- **Übertragbar:** Desktop-Dirigentenansicht: Wochenraster mit Probenblöcken, Farbe = Probenart, Höhe = Dauer. Rasterlinien fast unsichtbar (passt zur Projektregel "keine Haarlinien"). Zeitachse in Monospace.
- **URLs:** https://blakecrosley.com/guides/design/notion-calendar · https://dev.to/pickuma/cron-vs-notion-calendar-the-comparison-that-quietly-became-one-app-bnb

## 5. Linear (Web/Desktop, mobile App)

- **Ohne Text:** Statussymbole als Kreise mit Füllgrad (Backlog, Todo, In Progress halb, Done mit Haken) und Prioritätssymbole als Balken [nicht verifiziert für Details; Drittquelle nennt nur Farben: grau, blau, gelb, grün, rot]. Zeilen mit rechts ausgerichteten Metadaten (Priorität, Assignee, Schätzung) [Drittquelle].
- **Charakter:** Dunkel zuerst. Farbsystem in LCH aus nur drei Variablen (Basis, Akzent, Kontrast), wahrnehmungsgleiche Helligkeit [belegt: Linear-Blog]. Inter Display für Überschriften, Inter für Text [belegt]. Command Palette (Cmd+K), Inline-Bearbeitung, 100-250 ms Ease-out [Drittquelle].
- **Phone/Desktop:** Desktop dicht und tastaturgetrieben, Mobile separat. Details [nicht verifiziert].
- **Übertragbar:** Registerstatus als Füllkreis pro Register (Anteil zugesagt). Rechts bündige Zahlenspalte. Theme aus Basis + Akzent + Kontrast erzeugen, damit Dark und Light konsistent bleiben.
- **URLs:** https://linear.app/now/how-we-redesigned-the-linear-ui · https://blakecrosley.com/guides/design/linear

## 6. Apple Sports (iPhone; Apple Watch, Live Activities seit iOS 18)

- **Ohne Text:** Teamfarben als Flächenhintergrund, Spielstand als große, schmale Fettziffern (variable Schrift, Breite und Gewicht) [belegt: Lickability]. Hintergrund bei Tennis wie Rasen/Sand [belegt: Cult of Mac]. Ein Segment Gestern/Heute/Anstehend mit Vibrancy statt Standard-Look [belegt].
- **Charakter:** "Scores, fast" als bewusste Einfachheit [belegt: Eddy Cue, MacRumors]. Viele Verläufe, Platten mit Durchscheinen der Hintergrundfarbe [belegt]. Spekulativ: bewegter Trikotstoff-Hintergrund per Shader [Lickability markiert selbst als Vermutung].
- **Phone/Desktop:** iPhone-App mit Lock-Screen-Live-Activity und stapelbaren Spielen [belegt: TechRadar]. Desktop/iPad nicht verifiziert.
- **Übertragbar:** Probenkarte mit Farbe des Ensembles/Registers als Fläche, Uhrzeit als große schmale Ziffer. Zusagen-Zähler "23/31" als Held-Zahl. Segment Anstehend/Vergangen oben (deckt sich mit Projektregel).
- **URLs:** https://lickability.com/blog/apple-sports/ · https://sixcolors.com/post/2024/02/simple-complexity-apples-trio-of-sports-apps/

## 7. Apple Wetter (iOS 15+ / Dark-Sky-Erbe)

- **Ohne Text:** Tagesbalken je Tag mit Temperaturspanne, Punkt = aktuell; Farbverlauf im Balken kodiert Temperatur [belegt: MacRumors]. Kacheln für Wind, UV, Druck als kleine Einzelkarten [belegt: AppleInsider]. Hintergrund wechselt mit Wetterlage [belegt: 9to5Mac].
- **Charakter:** Gesamte Stimmung über Hintergrund und riesige Gradzahl. Kartenraster im Stil der Einstellungen.
- **Phone/Desktop:** iPhone, iPad und Mac besitzen die App, Unterschiede [nicht verifiziert].
- **Übertragbar:** Pro Register ein Balken: Spanne "sicher zugesagt bis maximal möglich", Punkt = jetzt. So sieht der Dirigent in einer Sekunde, ob die Besetzung trägt. Wichtige Zahlen in eigene Kacheln.
- **URLs:** https://www.macrumors.com/guide/ios-15-weather-app · https://9to5mac.com/2021/09/24/ios-15-weather-app-hands-on/

## 8. Structured (iPhone, iPad, Mac, Watch; Finalist Apple Design Awards 2026 Inclusion laut Meetup-Bericht [Drittquelle, nicht gegen Apple geprüft])

- **Ohne Text:** Tag als vertikale Zeitleiste: Aufgaben, Kalendertermine und Routinen als farbige Blöcke, Länge = Dauer [belegt: Store]. Hunderte Icons pro Block, automatische Farbwahl (Beta) [belegt]. Inbox für Ungeplantes getrennt von der Zeitleiste.
- **Charakter:** Runde, farbige Blöcke mit Icon als Wiedererkennung. Dunkel- und Kontrastmodi [belegt]. 4,8 Sterne bei etwa 169 Tsd. Bewertungen [belegt, Stand Store-Seite].
- **Phone/Desktop:** Layout passt sich Falt-Geräten an [belegt]. Mac, iPad nutzen dieselbe Zeitleiste, Details [nicht verifiziert].
- **Übertragbar:** "Heute"-Karte als Zeitleiste: Aufbau, Einspielen, Probe, Pause als Blöcke mit Dauerlänge und Icon (Instrument, Stück). Ein Blick zeigt den Tagesablauf ohne Fließtext.
- **URLs:** https://apps.apple.com/app/structured-daily-planner/id1499198946 · https://forums.appleinsider.com/discussion/230281

## 9. Spond (Vereins-/Teamapp, iOS, Android, Web)

- **Ohne Text:** Teilnahme mit Antwortstatus zugesagt/abgesagt/offen und erwartete Teilnehmerzahl pro Termin [belegt: Spond-Blog]. Konkrete Farben, Avatare, Icons [nicht verifiziert, nur Text gelesen].
- **Charakter:** Wenig eigener Charakter laut Textlage, Funktion im Vordergrund. [nicht verifiziert, Screenshots nicht gesichtet]
- **Phone/Desktop:** Web und Apps vorhanden, Unterschiede [nicht verifiziert].
- **Übertragbar:** Als Gegenbeispiel nutzen: Teilnehmerzahl und drei Zustände sind das Mindeste. Wir wollen sie als Fläche zeigen (Segmentbalken pro Register), nicht als Liste.
- **URLs:** https://www.spond.com/en-us/news-and-blog/attendance-tracking-on-spond/ · https://www.spond.com/news-and-blog/spond-vs-teamsnap-vs-sportsengine/
- Hinweis: TeamSnap zeigt die Verfügbarkeit nur in bezahlten Tarifen, Heja Anwesenheitsstatistik nur im Bezahlplan [Drittquelle: Vergleichsseiten, möglicherweise veraltet].

## 10. Konzertmeister (Orchester/Chöre, Wien; iOS, Android, Web)

- **Ohne Text:** Terminkarte mit Datum links samt "wie lange noch antwortbar", Mitte Titel und Antwortübersicht für Leitende, rechts Vereinslogo und Terminart [belegt: Hilfeseite]. Zeigt Abwesenheiten beim Anlegen eines Termins [belegt].
- **Charakter:** Funktional, an Orchesterbegriffen orientiert (Vereinsmodell, Termintypen). Visuelle Besonderheiten [nicht verifiziert]. Es gibt eine Anwesenheitsansicht (Release-Notiz) [belegt].
- **Phone/Desktop:** Apps plus Web-Oberfläche. Einzelheiten [nicht verifiziert].
- **Übertragbar:** Direktes Konkurrenzprodukt: "Antwort noch möglich bis" als schrumpfende Frist-Anzeige (Ring oder Balken). Abwesenheiten beim Planen sichtbar machen. Antwortübersicht der Leitung als farbiger Segmentbalken statt Zahlen.
- **URLs:** https://konzertmeister.app/en/help/view-my-appointments · https://konzertmeister.app/en

## 11. BAND und Choir Genius (Gruppen-/Chor-Apps)

- **BAND:** Geteilter Kalender mit RSVP und Anwesenheitsprüfung, Abstimmungen [belegt: Store, Wikipedia]. Visuelle Kodierung [nicht verifiziert]. Charakter: Social-Feed-Stil, eher Kommunikation als Übersicht.
- **Choir Genius:** Anwesenheit per Name oder QR-Scan, großer oranger "Attendance"-Button am Termin, Filter nach Stimmgruppe [belegt: Hilfe, Store]. Mitglieder-Selbst-Check-in per QR [belegt: Herstellerseite]. Oberfläche sonst [nicht verifiziert].
- **Phone/Desktop:** Choir Genius mit Admin-Web und App [belegt vage].
- **Übertragbar:** QR-Selbst-Check-in am Probenort. Anwesenheit nach Stimmgruppe filtern, als Fläche je Register zeigen. Eine auffällige Primäraktion pro Termin statt vieler Buttons.
- **URLs:** https://apps.apple.com/us/app/choir-genius/id6450846555 · https://apps.apple.com/app/id542613198

## 12. Gentler Streak (iPhone, Watch; Apple Design Award 2024 Social Impact)

- **Ohne Text:** Balkendiagramme der Monats-Workouts aus HealthKit, Liniengraph für Belastung/Erholung [belegt: Apple, grob]. Konkrete Ringe/Pfad-Visuals [nicht verifiziert].
- **Charakter:** Maskottchen Yorhart, das die inneren Gedanken ausspricht [belegt]. Ermutigende Texte statt Leistungsdruck, "Fortschritt statt Vergleich" [belegt]. Bunt, freundlich.
- **Phone/Desktop:** iPhone, iPad, Watch einheitlich [belegt].
- **Übertragbar:** Ein Maskottchen/Figur pro Ensemble (z. B. Notenschlüssel-Figur) für leere Zustände und Erinnerungen. Wärmerer Ton bei Absagen, ohne Schuldgefühl.
- **URLs:** https://developer.apple.com/jp/news/?id=3m0ht22s · https://sketch.com/blog/gentler-streak

## 13. Partiful (Event-Einladungen, Web und iOS)

- **Ohne Text:** RSVP-Auswahl in der Reihenfolge Going, Maybe, Can't go als Skala: Ja und Nein an den Enden, Vielleicht in der Mitte [belegt: Pratt-Kritik]. Gästeliste nach Zustand gruppiert, Absagen in der Version 1.36.7 nicht sichtbar [belegt: Pratt, Stand Feb. 2025]. Reaktionen per Emoji [belegt: Quelle zweiter Hand].
- **Charakter:** Marke steckt in Umschlag, Antwortfluss und Details, nicht im Logo [Drittquelle: Made Good]. Verspielte Display-Grotesk [Drittquelle]. Individuell gestaltbare Cover, Effekte, eigene RSVP-Emojis [belegt: Projektbericht].
- **Phone/Desktop:** Mobil-first, Einladungslink im Web für Gäste ohne App [belegt: Sacra, Marketing].
- **Übertragbar:** Zusage-Antwort als Skala mit drei großen Flächen und Emoji (Ja, Vielleicht, Nein). Pro Ensemble ein eigenes Cover und Antwort-Emoji für Charakter. Absagende sichtbar lassen (Fehler der Vorlage vermeiden).
- **URLs:** https://ixd.prattsi.org/2025/02/design-critique-partiful/ · https://clxyang.github.io/2024/partiful

---

## Muster, die mehrfach vorkommen

1. **Ein Signaturelement:** Flighty die Routenlinie mit Flugzeug, Things der sich füllende Kreis, Notion Calendar der Größensprung Überschrift/Label, Apple Wetter der Temperaturbalken. Pro Produkt genau eine wiedererkennbare Form.
2. **Zeit als Geometrie:** Länge = Dauer, Position = Uhrzeit (Notion Calendar, Structured, Flighty-Linie). Gilt für Tagesplan und Countdown.
3. **Zahl als Held:** Große, oft schmale oder monospace Ziffern (Apple Sports Score, Flighty Countdown, Notion Calendar H1). Für uns: "23/31 zugesagt".
4. **Farbe als Kategorie oder Zustand, sonst neutral:** Things nutzt Farbe nur mit Bedeutung. Linear und Flighty legen Status auf wenige Farben (grün/amber/rot).
5. **Status durch Füllung statt Etikett:** Checkbox füllt sich (Things), Status als Kreis mit Füllgrad (Linear), Boarding-Segmentbalken (Flighty), Spanne mit Punkt (Wetter).
6. **Eine echte Metapher:** Flughafentafel, Wetterkarte, Spielstand. Der Nutzer kennt die Form schon.
7. **Fläche trägt die Identität:** Apple Sports färbt den Hintergrund mit Teamfarben, Wetter mit Wetterlage. Farbfläche statt Text sagt "wer/was".
8. **Wenige, gut gewählte Typografie-Entscheidungen:** eine Familie, extremer Größenkontrast, Monospace nur für Zeiten (Notion Calendar, Flighty, Linear).
9. **Wenige Linien, viel Fläche und Abstand:** Notion Calendar mit fast unsichtbarem Raster, Apple Wetter mit Kacheln. Deckt sich mit der Projektregel gegen Haarlinien.
10. **Bewegung mit Funktion:** Füllen, Federn, Zahlenübergänge (Things, Flighty), nie reine Dekoration.
11. **Eine Primäraktion groß:** Magic Plus (Things), Attendance-Button (Choir Genius), Going/Maybe/Can't go (Partiful).
12. **Charakter durch Figur oder Ton:** Yorhart (Gentler Streak), Partiful-Cover und Emoji. Wo Daten trocken sind, hilft ein Gesicht.
13. **Mehrere Ausgabeflächen aus einer Form:** Flighty und Apple Sports bringen dieselbe Information auf Lock Screen, Dynamic Island und Widget. Erst die kleinste Form entwerfen, dann hochskalieren.

## Offene Lücken
- Screenshots wurden nicht visuell geprüft. Vor Übernahme eines Musters die verlinkten Bilder ansehen.
- Spond, Konzertmeister, BAND, Choir Genius: Farb- und Formkodierung der Antwortübersicht nicht belegt. Sie ist der wichtigste Vergleich für uns und sollte als nächstes per App-Store-Screenshots geprüft werden.
- Heja und TeamSnap wurden nur über Vergleichsseiten erwähnt, nicht untersucht.
- Hex-Werte und Schriftgrößen aus Blake-Crosley-Leitfäden sind illustrativ, nicht offiziell.

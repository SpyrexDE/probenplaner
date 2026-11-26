# Semantische Bildsprache

Verbindlich für jeden Screen, jede Größe, jede Rolle.

Leitsatz: gleiche Bedeutung → gleiche Darstellung → gleiche Interaktion. Es entsteht ein visuelles Kommunikationssystem, das mit jeder Nutzung vertrauter und schneller verständlich wird. Wer eine Form einmal gelernt hat, erkennt sie überall wieder und weiß, was ein Klick tut, ohne zu lesen.

Regeln für das System selbst:
- Jede Informationsart hat genau einen Eintrag in der Tabelle unten. Eine neue Informationsart bekommt erst einen Eintrag, dann eine Darstellung.
- Eine Form, Farbe oder ein Icon gehört genau einer Bedeutung. Wird es für etwas anderes gebraucht, braucht das andere eine eigene Form.
- Größenstufen (Zeile, Detail, Held) ändern die Größe, nicht die Form, die Farben oder die Interaktion.
- Pro Ansicht und Zustand erscheint jede Information einmal.

## Farbe hat Bedeutung

| Farbe | Bedeutet | Nie für |
|---|---|---|
| Akzent (Markenblau, `--accent`, Verlauf `--grad-accent`) | bedienbar, zu tun, heute | Dekoration, Kategorien |
| Grün (`--status-yes-*`) | zugesagt, anwesend, läuft jetzt (Live-Punkt) | Erfolgsmeldungen ohne Bezug zu Antwort/Anwesenheit |
| Rot (`--status-no-*`) | abgesagt, fehlt, fällt aus, zerstörerische Aktion | Fristen, Hinweise |
| Neutraler Ring (`--status-open-*`) | offen, noch keine Antwort | |
| Amber (`--status-warn-*`) | Frist, Abweichung | zu tun (das ist Akzent) |
| Probenart-Farbe (Daten, pro Art einstellbar) | Art der Probe: Wochentag-Kachel und Art-Etikett | alles andere |
| Rollen-Farbe (Daten, pro Rolle einstellbar) | Rolle: nur das Rollen-Icon | Flächen, Text |
| Register-Farbe (Daten) | Register: nur kleiner Marker | Überschriften, Sitzplan |
| Ruhige Tinte | vergangen, nachrangig | Informationen, die man unterscheiden muss |

## Informationsarten

| Information | Form | Farbe | Icon | Typografie | Interaktion | Größenstufen |
|---|---|---|---|---|---|---|
| Termin (wann) | Wochentag-Kachel, abgerundetes Quadrat | Wochentag in Probenart-Farbe, Kachel getönt | – | Tag groß, fett, tabellarisch; Zeit 17/600 tabellarisch, Ende leichter | keine eigene; die Zeile ist das Ziel | Zeile, Heute-Karte, Detailkopf |
| Probenart | Etikett, kleiner Radius (`--radius-inner`) | Tönung + Text in Probenart-Farbe | Tutti mit kleinem Icon | 10–11 px, Großbuchstaben, gesperrt | keine (Filter nur im Filter) | überall gleich; Standardart „Probe“ ohne Etikett |
| Besetzung (wer eingeladen) | Text mit Icon | Tinte, mittelstark | Personen-Icon | Meta 13/500 | keine | nur wenn nicht Standard |
| Ort | Text mit Icon | Tinte | Pin | Meta 13/500 | im Detail: Adresse als Link zur Karte | nur wenn nicht Standardort |
| Hinweis | Emoji ohne Kasten, 18–20 px | keine Fläche, nie grauer Kasten | Emoji | Text daneben in Meta | Tooltip/Antippen zeigt Text, wenn nur Emoji | Zeile: erster mit Text, weitere als Emoji; Detail: alle mit Text |
| Frist | Text in der Datum/Zeit-Zeile | Amber | Uhr | Meta, fett | keine | gleiche Stelle überall |
| Eigene Antwort | zwei Quadrate im festen Platz rechts | gewählt: grün oder rot gefüllt; offen: beide neutral | Haken, Kreuz | – | Antippen setzt und ändert an Ort und Stelle | Zeile, Heute-Karte, mobiles Detail: gleiche Form |
| Wer kommt (Gruppe) | Halbkreis-Zähler (Zeile); Sitzplan nach echter Aufstellung (Detail, Bereich rechts, Übersicht) | grün, neutraler Ring, rot wie die eigene Antwort | Zusagen als große Zahl in der Mitte („39/53“), daneben nur ✗ n und ○ n | Zahl fett, tabellarisch, Nenner klein | Stimme im Sitzplan → Rückmeldungen dieser Stimme; Zähler → Rückmeldungen | Zeile: Zähler; sonst Sitzplan, nie beides sichtbar |
| Anwesenheit | wie „Wer kommt“, gleiche Formen | grün, rot | Haken, Kreuz | wie oben | Erfassen per Haken/Kreuz-Schalter | gleiche Formen wie Zusagen |
| Zu tun (offen) | Zähl-Blase | Akzent | gestrichelter Ring für „offen“ | Zahl fett | führt zu den offenen Punkten | Navigation, Chip im Seitenkopf: gleiche Blase |
| Heute, läuft jetzt | Heute-Fläche im Akzent-Verlauf | Akzent; Live-Punkt grün | Live-Punkt | „Jetzt …“ kompakt | – | nur die heutige Probe |
| Ablauf | senkrechte Zeitleiste | vergangen ruhig, aktuell Akzent und fett, kommend normal | Punkte auf Linie | Zeit tabellarisch links | – | einzige Fortschrittsanzeige; Zeitlinie nur auf mobiler Heute-Karte ohne sichtbaren Ablauf |
| Rolle | Rollen-Chip | Icon in Rollen-Farbe | Rollen-Icon (Daten) | Name | Antippen/Hover zeigt Namen; mit Recht: Zuweisen, von dort „Rollen verwalten“ | überall dasselbe Chip |
| Stimme, Register | Wertzeile „Stimme · Horn ›“ | Tinte | Instrument-Icon | Label, Wert | öffnet Auswahl, wenn man darf; sonst kein Chevron | Listen: Registerkopf mit Anzahl rechts |
| Person | Kreis mit Initialen + Name | ruhige Tönung, nie laute Farben | – | Name 15/600 | öffnet die Person, wenn man darf | überall gleich |
| Kennzahl | Zahl oben rechts im Element | Tinte oder Statusfarbe | – | fett, tabellarisch, Bruch mit kleinem Nenner | führt zur Liste dahinter | – |
| Fällt aus | Etikett „Fällt aus“ + durchgestrichene Zeit | Rot | – | wie Probenart | – | überall gleich |
| Einstellungs-Eintrag | Icon-Kachel vor dem Namen, wie in den iOS-Einstellungen | feste Farbe je Eintrag, gleich in Profil und Verwaltung | Eintrags-Icon | Name 15/500 | öffnet den Bereich (›) oder schaltet (Schalter) | nur in Einstellungslisten, nie vor Überschriften |
| Gesperrt (darf man nicht ändern) | Schloss am Ende statt Bedienelement | Text voll lesbar, kein Ausgrauen | Schloss | wie die Zeile | Antippen nennt, wer es ändern darf | überall gleich |
| Orchester | Monogramm-Kachel | Markenfarbe | Kürzel | Name 15/600 | Orchesterwahl (echtes Menü) | Seitenleiste, Menü, mobil gleich |

## Aktionen und Bedienelemente

| Element | Bedeutet | Form |
|---|---|---|
| Primärer Button der Seite | die eine Hauptaktion der Seite (z. B. Neue Probe) | gefüllte Pille im Akzent-Verlauf; genau einer pro Seite, in der Kopfzeile |
| Primäre Aktion am Objekt | Hauptaktion einer Probe, Person, Rolle (Aktionsleiste) | getönte Pille im Akzent (`--accent-tint`), Akzent-Text; nie gefüllt, damit sie nicht mit dem Seiten-Primärbutton konkurriert |
| Sekundäre Aktion | weitere Aktionen am selben Objekt | Icon-Button mit Tooltip, gleiche Größe |
| Zerstörerische Aktion | absagen, löschen, entfernen | rot, am Ende abgesetzt, mit Bestätigung |
| Ausgehende Aktion | Erinnern per Push | Glocke + „Erinnern“, ohne Zahl im Button (die Zahl steht schon bei ○); die Bestätigung nennt Empfänger, Anzahl und Anlass |
| › | geht zu einer anderen Seite oder in den Bereich rechts | am Ende des Elements |
| ⌄ | klappt an Ort und Stelle auf, dreht sich | am Ende des Elements |
| ⌄ an einem Bedienelement | echtes Dropdown-Menü am Auslöser | nur wenn es ein Menü öffnet |
| Segment | schließt sich gegenseitig aus, wechselt die Ansicht (Anstehend/Vergangen) | immer oben, nie gestapelt |
| Filter-Chip | schaltet einen Filter, zeigt Anzahl | Zustand an/aus sichtbar verschieden |
| Wertzeile | ein einzelner einstellbarer Wert | Label, Wert, › , öffnet Auswahl |

## Formen und Radien

- Karte: `--radius-card`. Bedienelement: `--radius-control`. Etikett und Inneres: `--radius-inner`. Pille und Kreis: `--radius-full`.
- Abgerundetes Quadrat = Zeitanker oder eigene Antwort. Halbkreis und Punkte = Gruppe (wer kommt, wer war da). Pille = Aktion oder Filter. Kleines Rechteck = Klassifikation (Probenart). Kreis = Person.
- Eine aufgeklappte Zeile und ihr Inhalt bilden eine Karte mit einem Schatten und durchgehenden Radien.

## Typografie

- Seitentitel 34/700. Abschnittskopf 20–22/700, Bereich rechts ruhig.
- Zeit 17/600 tabellarisch. Namen 15/600. Meta 13/500 mit Icon. Etikett 10–11/700 Großbuchstaben gesperrt.
- Zahlen immer tabellarisch und fett, wo sie Aussage sind.

## Prüfung vor jedem Review

- Für jede sichtbare Information die Zeile in der Tabelle nennen. Gibt es keine, fehlt der Eintrag oder die Information gehört nicht hierhin.
- Jede Informationsart über alle Screens listen und gleiche Darstellung bestätigen.
- Jede Ansicht in jedem Zustand: nichts doppelt.
- Unscharf ansehen: Farbe nur dort, wo die Tabelle Farbe erlaubt.

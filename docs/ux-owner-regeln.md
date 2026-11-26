# Verbindliche Regeln des Owners

## 1. Information und Doppelungen

**R01** Jeder sichtbare Text ist ein Name, ein Wert oder eine Aktion; erklärende Zusatzzeilen entfallen.
Zitat: „dass alles so überexplizit nochmal beschrieben wird“ (2026-10-24)
Prüfung: Alle Textknoten der Ansicht auflisten; jeder, der weder Name noch Wert noch Aktion ist, wird gestrichen.

**R02** Über oder unter Antwort-Buttons steht kein Text wie „DEINE ANTWORT“ oder „geantwortet am …“; der Zustand ergibt sich aus den Buttons selbst.
Zitat: „Und darunter nochmal, geantwortet am 22. Oktober.“ (2026-10-24)
Prüfung: Karte mit offener und beantworteter Antwort ansehen; neben den Buttons steht kein Wort außer dem Zustand der Buttons.

**R03** Hinweise erscheinen nur in dem Moment und an dem Ort, an dem sie gebraucht werden (Löschwarnung nur im Löschdialog).
Zitat: „Sowas kann gerne angezeigt werden, wenn man gerade wirklich seinen Account löschen will“ (2026-10-24)
Prüfung: In Profil und Einstellungen steht keine Folgenbeschreibung einer Aktion, die gerade nicht ausgelöst wird.

**R04** Selbstverständliche Folgen werden nicht erklärt („alter Link funktioniert danach nicht mehr“, „System folgt dem Gerät“).
Zitat: „Natürlich funktioniert der alte Link danach nicht mehr.“ (2026-10-24)
Prüfung: Bei jeder Einstellung und Aktion prüfen, ob ein Satz nur die Bezeichnung wiederholt; solche Sätze entfernen.

**R05** Jeder Satz in einer Ansicht muss für jeden relevant sein, der diese Ansicht öffnet; keine Fußnoten wie „gilt nur auf diesem Gerät“.
Zitat: „die müssen relevant sein für jeden, der diese Ansicht öffnet“ (2026-10-24)
Prüfung: Grauen Kleintext am Ende von Einstellungsseiten suchen; es darf keinen geben.

**R06** Es gibt keinen grauen Info-Balken über dem Inhalt einer Seite (z. B. Mitgliederzahl und Erklärsatz über der Registerstruktur).
Zitat: „riesige Label, der einfach nur grau und lang da oben“ (2026-10-24)
Prüfung: Erstes Element unter der Titelzeile jeder Seite ist Inhalt, kein Erklärband.

**R07** Ein Titel bekommt keinen Untertitel, wenn gleichrangige Titel keinen haben; Vereinsname o. Ä. steht nicht als graues Zusatzlabel unter dem Namen.
Zitat: „Braucht es bei neue Probe überhaupt diesen Subtitle?“ (2026-10-27)
Prüfung: Titel vergleichbarer Einträge nebeneinanderlegen; Zusatzzeilen sind überall oder nirgends vorhanden und begründet.

**R08** Dieselbe Information erscheint pro Zustand genau einmal; aufgeklappt gibt es nur einen Fortschrittsbalken, nicht drei.
Zitat: „kriege ich 3 Progress Bars, wie weit die Probe grade ist“ (2026-11-25)
Prüfung: Heutige Probe aufklappen; jede Größe (Fortschritt, Zeit, Status) kommt in genau einer Darstellung vor.

**R09** Zwei Ansichten zeigen nicht dieselbe Liste mit unterschiedlichen Fähigkeiten; jede Ansicht hat eine eigene Aufgabe, ohne dass man Pro und Kontra abwägen muss.
Zitat: „Das ist zu viel Cognitive Load.“ (2026-10-25)
Prüfung: Für jedes Tab-Paar die Aufgabe in einem Satz benennen; zwei Tabs mit gleicher Aufgabe sind ein Fehler.

**R10** Beim Entschlacken gehen keine nötigen Informationen verloren (Heute, Frist, Status, Wochengruppierung, Tab-Leiste, Übergruppen der Stimmen).
Zitat: „wo man dann plötzlich selbst wieder die Informationen, die man braucht, gar nicht bekommt“ (2026-10-25)
Prüfung: Liste der Informationen der Vorgängerversion abhaken; jede ist noch auffindbar.

**R11** Gezeigt wird, was die Person in diesem Moment sehen will, nicht alles, was verfügbar ist.
Zitat: „So ein bisschen Empathie fehlt manchmal noch ein bisschen.“ (2026-10-25)
Prüfung: Pro Ansicht benennen, welche Frage die Person hier hat; Elemente, die diese Frage nicht beantworten, entfernen.

**R12** Ein Hinweis zu einem Problem verkleinert nie das Element, das er kommentiert; Warnwerte stehen in eigener Spalte oder vor dem Balken.
Zitat: „dass dadurch die Progressbar, die man ja verwendet, um zu vergleichen“ (2026-10-27)
Prüfung: Alle Fortschrittsbalken einer Liste haben dieselbe Länge, auch bei Warnung.

**R13** Nichts wird gezeigt, was es im Datenmodell oder in der App nicht gibt; jedes sichtbare Attribut ist ein echtes, an einer Stelle änderbares Feld.
Zitat: „Features gezeigt werden, die so noch gar nicht existieren“ (2026-10-27)
Prüfung: Jedes Attribut (Adresse eines Ortes, Rollen-Icon, Farbe) bis zum Editierfeld zurückverfolgen.

**R14** Einstellungen, die den Zustand ändern, haben sofort sichtbare Wirkung (z. B. Dichte „Kompakt“).
Zitat: „Sehe immer noch 4 Proben.“ (2026-10-24)
Prüfung: Dichte umschalten; die Anzahl sichtbarer Zeilen ändert sich.

**R15** Aussagen in der App sind sachlich richtig (kein Push bei PWA-Installation, Gerät im Text passt zum Gerät).
Zitat: „Einerseits wundere ich mich, warum steht da auf deinem iPhone?“ (2026-10-24)
Prüfung: Jeden Hilfetext gegen das tatsächliche Verhalten und das aktuelle Gerät prüfen.

**R16** Testdaten bilden realistische Orchestermuster ab: feste Probentage mit wenigen Sonderproben.
Zitat: „erkenne ich in deinen Testdaten nicht wirklich ein regelmäßiges Muster“ (2026-10-27)
Prüfung: Testdaten ansehen; Standardproben hängen an festen Wochentagen, Sonderproben sind selten.

**R17** Ein Sitzplan wird nur gezeigt, wenn er der tatsächlichen Sitzordnung des Orchesters entspricht.
Zitat: „Sonst ist es einfach nur verwirrend.“ (2026-11-25)
Prüfung: Sitzordnung pro Orchester konfigurierbar; ohne Konfiguration keine Sitzplan-Darstellung.

## 2. Affordanzen, Dropdowns, Klickbares

**R18** Zusagen und Absagen werden über zwei Buttons ohne Beschriftung kommuniziert; Zustand und Bedienbarkeit sind implizit erkennbar.
Zitat: „zwei Buttons, die haben eine gewisse implizite Nachricht“ (2026-10-24)
Prüfung: Karte ansehen; Haken und Kreuz ohne Worte; offen, zugesagt, abgesagt sind unterscheidbar.

**R19** Implizite Hinweise wie der Pfeil zum Aufklappen bleiben ohne Erklärtext; was sich beim Antippen offensichtlich verhält, braucht kein Label.
Zitat: „Nicht alles muss erklärt werden, wenn es intuitiv ist für Menschen.“ (2026-10-27)
Prüfung: Chevron antippen; Zeile klappt aus und Pfeil dreht sich; kein Zusatztext.

**R20** Was klickbar ist, sieht klickbar aus, und was nicht klickbar ist, sieht nicht klickbar aus.
Zitat: „Das ist auch ein Problem mit Discoverability und Affordance.“ (2026-10-24)
Prüfung: Jedes Element einer Ansicht anklicken; Optik (Unterstrich, Kontur, Chevron) stimmt mit dem Verhalten überein.

**R21** Eine übernommene Affordance (gestrichelter Unterstrich) steht nur dort, wo sie funktional ist, und wird nicht durch einen Erklärsatz erklärt.
Zitat: „diese Affordance einfach blind übernommen“ (2026-10-24)
Prüfung: Alle unterstrichenen Elemente anklicken; jedes öffnet etwas, und es steht kein Satz daneben.

**R22** Der Marker für „hier kann man ändern“ ist an allen Eingabefeldern derselbe; er fehlt nicht bei einzelnen Feldern.
Zitat: „warum ist alles blau umrandet, um zu zeigen, dass man draufklicken kann?“ (2026-10-27)
Prüfung: Alle Felder im Probeneditor nebeneinander ansehen; gleiche Felder tragen gleichen Marker.

**R23** Menschen in einer Liste sind anklickbar, wenn die Optik es nahelegt; Listen von Personen führen zum Profil oder die Optik signalisiert „nicht klickbar“.
Zitat: „dass man hier nicht mehr auf die Leute raufklicken kann, finde ich seltsam“ (2026-10-24)
Prüfung: Jede Personenzeile antippen; Reaktion passt zur Optik.

**R24** Tabs sehen wie Tabs aus; ein Segment mit Zugesagt/Abgesagt/Offen muss als wählbar erkennbar sein.
Zitat: „dass das überhaupt Tabs sind“ (2026-10-27)
Prüfung: Blindtest: Testperson zeigt ohne Hilfe, dass die drei Einträge umschaltbar sind.

**R25** Filter-Chips unterscheiden sich im Zustand sichtbar und sind nicht alle gleich gestaltet.
Zitat: „die sehen alle gleich aus“ (2026-10-24)
Prüfung: Unscharf ansehen; ausgewählter und nicht ausgewählter Chip bleiben unterscheidbar.

**R26** Horizontales Scrollen ist erkennbar (angeschnittene Spalte, Verlauf, Pfeil), nie nur verborgen.
Zitat: „nicht offensichtlich sein, dass man horizontal überhaupt scrollen kann“ (2026-10-24)
Prüfung: Tabelle mit mehr Spalten als Breite laden; der Rand deutet Fortsetzung an.

**R27** Ein ausgewählter Zustand wirkt nie ausgeblendet oder deaktiviert; ein bereits gewählter Emoji ist als gewählt und nicht als löschbar gezeichnet.
Zitat: „dass es dann eher ausgeblendet aussieht als ausgewählt“ (2026-10-27)
Prüfung: Rollenwahl (Vorstand) und Emoji-Auswahl im gewählten Zustand ansehen; Zustand ist eindeutig „gewählt“.

**R28** Ausgehende oder nicht rücknehmbare Aktionen (Erinnern per Push) sehen anders aus als Kopieren und nennen Empfänger, Anlass und Anzahl vor dem Senden.
Zitat: „auf einmal ist irgendwas losgeschickt worden und ich kann es halt nicht zurücknehmen“ (2026-10-24)
Prüfung: Erinnern auslösen; vor dem Senden steht, woran erinnert wird und an wie viele Personen.

**R29** Was wie ein Dropdown aussieht, ist ein Dropdown, und was ein Dropdown ist, sieht wie eines aus.
Zitat: „warum sind manche Dropdowns einfach keine Dropdowns?“ (2026-11-25)
Prüfung: Alle Auswahlfelder einer Ansicht öffnen; Optik und Verhalten stimmen überein.

**R30** Uhrzeit und Datum sind auf dem PC genauso änderbar wie mobil, auch per Tastatur.
Zitat: „bei der Uhrzeit kann man die Uhrzeit nicht ändern“ (2026-10-27)
Prüfung: Auf Desktop die Uhrzeit anklicken und tippen; der Wert ändert sich.

**R31** Auswahllisten (Register, Art, Raum) erlauben eigene Einträge nach dem Muster der Ortsauswahl, nicht nur vorgefertigte.
Zitat: „wieder nur vorgefertigte auswählen kann“ (2026-10-27)
Prüfung: In jedem Auswahlfeld einen neuen Wert anlegen.

**R32** Der Emoji-Picker zeigt die Emojis groß, nicht im Mini-Chip-Format, und bringt die im Orchester häufig genutzten nach vorn.
Zitat: „nicht unbedingt die Emojis so winzig anzeigen“ (2026-10-27)
Prüfung: Picker öffnen; Emoji-Größe lesbar, häufige Emojis stehen zuerst.

**R33** Emojis erscheinen auf der Karte als Tags mit Bedeutung, nicht als Fließtext der Beschreibung.
Zitat: „Jeder Emoji ist ein Tag in gewisser Weise.“ (2026-10-24)
Prüfung: Karte ansehen; Emojis sind als eigene Tags abgesetzt.

**R34** „Keine Farbe“ wird durch ein Symbol dargestellt, nicht durch Weiß und einen Erklärsatz.
Zitat: „Ersetzt doch Weiß einfach mit einer Anzeige“ (2026-10-24)
Prüfung: Farbauswahl ansehen; der Eintrag „keine“ ist ein Symbol, ohne Satz darunter.

**R35** Teilen eines Links ist eine einzige Aktion; es gibt keine drei Buttons in drei Stilen und keine getrennten Einträge für Link kopieren und Probe teilen.
Zitat: „Wenn man auf Teilen drückt, kann man auch auf Kopieren drücken.“ (2026-10-24)
Prüfung: Einladungs- und Proben-Teilen-Ansicht; je genau ein Button.

**R36** Mit jedem Ansichtswechsel stimmt die Erwartung nach dem Klick mit dem Ziel überein.
Zitat: „das musst du dir bei jedem einzelnen View-Change auch überlegt haben“ (2026-10-24)
Prüfung: Für jeden Klick im Prototyp „Was erwartet die Person?“ aufschreiben und mit dem Ziel vergleichen.

**R37** Der Scrollstand bleibt erhalten, wenn man etwas anklickt (Anwesenheit und anderswo).
Zitat: „der Scroll zum Beispiel resettet immer, wenn man irgendwie was anklickt“ (2026-10-27)
Prüfung: Scrollen, ein Element anklicken, Scrollposition vorher/nachher gleich.

**R38** Eine Auswahl im Kalender-Abo zeigt beide Optionen (nur ansehen / mit Zu- und Absagen) gleichwertig nebeneinander, ohne dominierenden Primärbutton.
Zitat: „und die wurden nebeneinander gestellt“ (2026-10-24)
Prüfung: Kalender-Abo-Ansicht; beide Optionen gleich gewichtet und mit Vor- und Nachteil erkennbar.

## 3. Navigation und Informationsarchitektur

**R39** Es gibt keinen Tab „Mehr“; jeder Tab sagt, was darin ist.
Zitat: „Mehr ist auch so ein Menüpunkt, der so typisch faul ist“ (2026-10-24)
Prüfung: Tab-Leiste ansehen; kein Tab heißt „Mehr“ oder ähnlich unspezifisch.

**R40** Der vierte Tab ist für alle Rollen derselbe; Ensemble-Verwaltung liegt beim Orchester, nicht im Profil.
Zitat: „Weil es gehört ja eigentlich nicht zum eigenen Profil.“ (2026-10-24)
Prüfung: Mit jeder Rolle einloggen; gemeinsame Tabs gleich benannt und gleich platziert, kein Tab wird je Rolle ersetzt, Verwaltung nur unter Orchester.

**R41** Jede Einstellung hat genau einen Ort (Registerstruktur nicht an zwei Stellen).
Zitat: „Einmal unter Mehr und dann einmal unter Einstellungen und Registerstruktur.“ (2026-10-24)
Prüfung: Nach jeder Einstellung suchen; sie ist genau einmal auffindbar.

**R42** Es gibt keine zweite Tab-Gruppe über oder neben den Tabs; die Verwaltung ist über die erwartete Navigation erreichbar.
Zitat: „öffnet sich über den Tabs noch mal ’ne andere Tabgruppe“ (2026-11-25)
Prüfung: Orchester öffnen; es liegt genau eine Tab-Ebene vor.

**R43** Wo Rollen oder Stimmen angezeigt werden, führt ein sichtbarer Weg zu deren Verwaltung, sofern die Person die Berechtigung hat; ohne langes Hochscrollen.
Zitat: „Dafür muss ich zum Orchester, muss ganz hoch scrollen“ (2026-11-25)
Prüfung: Von der Mitgliederliste aus Rollen verwalten in höchstens zwei Klicks, ohne Scrollen.

**R44** Jeder Screen hat einen sichtbaren Weg zurück zum übergeordneten Objekt (Anwesenheit zur Probe und zu deren Editor).
Zitat: „wie komme ich jetzt von da wieder zurück zu dem anderen?“ (2026-10-25)
Prüfung: Von jedem Screen ohne Browser-Zurück zum Elternobjekt navigieren.

**R45** Aktionen in der Kopfzeile ändern sich nie mit dem Segment; segmentbezogene Aktionen stehen im Segmentinhalt.
Zitat: „Ganz wildes, schlechtes UI-Design.“ (2026-10-25)
Prüfung: Segmente wechseln; die Kopfzeile bleibt unverändert.

**R46** Orchesterwahl und Seitentitel teilen eine Zeile; die Orchesterwahl ist kein eigener Stapel über dem Titel.
Zitat: „diesen Orchestra-Switch zum Teil der Titelzeile machen“ (2026-10-24)
Prüfung: Zeilen zwischen Seitenrand und erstem Inhalt zählen; Orchesterwahl, Titel und Segment teilen sich so wenige Zeilen wie möglich.

**R47** Keine Funktion der Vorgängerversion verschwindet unbemerkt (Orchesterwechsel, Filter, Bulk-Editing); Streichungen sind bewusste, benannte Entscheidungen.
Zitat: „wo ist zum Beispiel das Wechseln des Orchesters hin?“ (2026-10-27)
Prüfung: Funktionsliste der Vorgängerversion abhaken; jede Lücke hat eine dokumentierte Begründung.

**R48** „Neue Probe“ steht in der Titelzeile der Probenliste auf Höhe von Titel und Suche und bietet alle Probentypen (Probe, Serie, KI-Import, weitere) (ersetzt Regel vom 2026-10-27, Button oben rechts; der Owner lobte am Abend die Platzierung bei der Liste).
Zitat: „zumindest das neue Probe ist jetzt actually bei der Probenliste“ (2026-10-27)
Prüfung: Auf Mobil und Desktop genau ein Button, an einer Stelle, der alle vier Typen anbietet.

**R49** Suche und „Neue Probe“ haben keine eigene Zeile unter der Überschrift, sondern stehen auf Höhe der Überschrift.
Zitat: „Das sollte doch auf der gleichen Höhe sein.“ (2026-10-27)
Prüfung: Titel, Suche und Button teilen sich eine Zeile.

**R50** Ob vergangene Proben angezeigt werden, wählt ein Segment „Anstehend / Vergangen“ am Kopf der Liste, nie ein Aufklapper unter der Liste (ersetzt Aussage vom 2026-10-24, das Ein- und Ausblenden passe so).
Zitat: „warum ist jetzt auf einmal der fucking vergangene Proben Dropdown unter den Proben?“ (2026-10-27)
Prüfung: Probenliste ansehen; Segment oben, kein Disclosure unter der Liste.

**R51** Die Navigationsleiste am unteren Rand der mobilen Ansicht bleibt.
Zitat: „unten die Menüpunkte sind. Das gefällt mir sehr gut“ (2026-10-24)
Prüfung: Mobilansicht ansehen; untere Tab-Leiste ist da.

## 4. Rollen, Rechte, Stimmen

**R52** Rollen-Icons und -Farben sind konfigurierbare Felder, an einer Stelle definiert und überall identisch genutzt.
Zitat: „wenn ich Icons sehe, bei Rollen, dann müssen die auch festgelegt und geändert werden können“ (2026-10-27)
Prüfung: Alle Orte auflisten, an denen Rollen erscheinen; sie nutzen dieselbe Komponente, ein Icon-Wechsel wirkt überall.

**R53** Die Stimmenauswahl behält die Gliederung in Übergruppen (Streicher, Holzbläser, Blechbläser).
Zitat: „wir haben überhaupt gar keine Unterteilung in die Übergruppen“ (2026-10-24)
Prüfung: Stimmenauswahl öffnen; Übergruppen sind sichtbar.

**R54** Auswahlbeschriftungen nennen, was sie zeigen („Alle Mitglieder“, nicht „Alle anzeigen“), und folgen der Berechtigung der Person.
Zitat: „habe ich das nicht ganz direkt intuitiv verstanden, was alle Anzeigen eigentlich bedeutet“ (2026-10-24)
Prüfung: Blindtest; die Testperson erklärt ohne Hilfe, wen der Schalter zeigt.

**R55** Anwesenheit pro Person erscheint nicht als farbige Prozentzahl in der Vergleichsliste; Details gibt es erst auf Klick.
Zitat: „sehr, sehr streng und sehr kompetitiv“ (2026-10-24)
Prüfung: Mitgliederliste ansehen; keine farbig bewertete Anwesenheitsquote pro Person in der Übersicht.

**R56** Berechtigungen gelten überall gleich; Rollen und Stimmen ändern nur Personen mit dem Recht, und kein Recht erlaubt Selbstvergabe, die nicht gewollt ist.
Zitat: „Warum kann sich die Leitung selbst Notenbart geben?“ (2026-11-25)
Prüfung: Je Rolle alle Änderungsstellen prüfen; erlaubt oder gesperrt ist an allen Stellen identisch.

**R57** Erinnerungen zerfallen in zwei Arten: offene Rückmeldungen und Erinnerung an zugesagte Proben; beides ist einstellbar.
Zitat: „Es sind zwei verschiedene Arten von Erinnerungen.“ (2026-10-24)
Prüfung: Benachrichtigungen ansehen; beide Arten sind getrennt einstellbar.

**R58** Sichtbarkeits-Anzeigen („Sichtbar für“) nutzen die Rollen-Darstellung der Mitgliederlisten.
Zitat: „wonach du bestimmst, was du anzeigst für Stimmen, die Dinge zu Sicht bekommen“ (2026-10-27)
Prüfung: Probeneditor und Mitgliederliste vergleichen; gleiche Icons und Namen.

## 5. Visuelle Hierarchie und Blickführung

**R59** Der Blick wird geführt: pro Ansicht gibt es klar erkennbar, wohin man zuerst schaut und wohin als Zweites.
Zitat: „Das Auge wird nicht richtig geführt.“ (2026-11-25)
Prüfung: Blur-Test und 1-Sekunden-Test; die Testperson findet das Wichtigste.

**R60** Eine Ansicht ist in unter einer Sekunde verständlich und bedienbar, über Anordnung, Form und Farbe statt über Text.
Zitat: „innerhalb von unter einer Sekunde von einem Betrachter verstanden werden kann“ (2026-11-25)
Prüfung: Screen eine Sekunde zeigen; die Testperson nennt den Zweck und die wichtigste Aktion.

**R61** Das Layout besteht aus erkennbaren Elementen, nicht aus Zeilen; was zusammengehört, bildet ein sichtbares Ganzes.
Zitat: „Wir müssen ja das Element als sich als Ganzes irgendwie designen.“ (2026-10-27)
Prüfung: Unscharf ansehen; Karten bleiben als Karten, Kopfzeilen als Kopfzeilen erkennbar.

**R62** Ein Pfeil oder Chevron sitzt am Ende des Elements, das er steuert, nicht direkt hinter einem Textfragment.
Zitat: „dieser blaue Pfeil ist halt am Ende vom Label wieder“ (2026-10-27)
Prüfung: Alle Chevrons ansehen; sie stehen bündig am rechten Rand ihres Elements.

**R63** Zeilen unterschiedlicher Rolle sehen verschieden aus; keine fünf gleichartigen Vollbreitezeilen mit verschiedenen Aufgaben.
Zitat: „die sind alle genau gleich quasi in der Hierarchie auch“ (2026-10-27)
Prüfung: Aufgeklappte Karte ansehen; Kopf, Zu-/Absagen, Erinnern und Stimmgruppen unterscheiden sich in Form und Gewicht.

**R64** Einzelne Kleinteile (ein Emoji, ein Chip) bekommen keine eigene Zeile.
Zitat: „Warum bekommt das eine einzelne Zeile?“ (2026-10-27)
Prüfung: Zeilen mit nur einem kleinen Element suchen; es gibt keine.

**R65** Zählwerte und Metadaten stehen oben rechts statt linksbündig gestapelt; horizontaler Platz wird genutzt.
Zitat: „mal die Probenanzahl nach oben rechts zu packen“ (2026-10-27)
Prüfung: Karten ansehen; Zählwerte stehen rechts auf Höhe des Titels, die rechte Hälfte ist nicht leer.

**R66** Zeilenumbrüche erzeugen keinen unnötigen Leerraum an den Rändern; Komponenten werden im verfügbaren Bereich platziert, nicht als Textzeilen gedacht.
Zitat: „zu sehr in Text- und Zeilenumbrüche denkst“ (2026-10-27)
Prüfung: Breite der Inhalte ansehen; keine halbleeren Zeilen neben kurzen Elementen.

**R67** Der Probentyp (Register-, Tutti-, Sonderprobe) ist auf den ersten Blick erkennbar; Einträge sehen nicht alle gleich aus.
Zitat: „Auf den ersten Blick wirken diese ganzen Einträge alle exakt gleich.“ (2026-10-24)
Prüfung: Liste mit gemischten Probentypen unscharf ansehen; Typen sind unterscheidbar.

**R68** Verschiedene Datenarten (Typ-Tag, Gruppe, Ort, Emoji) haben verschiedene typografische Behandlung und sind nicht in einer umbrechenden Zeile gleichgesetzt.
Zitat: „Aber Streicher steht da in der gleichen Font wie der Ort“ (2026-10-24)
Prüfung: Kartenzeile ansehen; Tag, Ort und Gruppe sind optisch getrennt.

**R69** Der Wochentag bleibt der farbige Anker der Karte.
Zitat: „was ist mit wochentag anzeige in der farbe?“ (2026-10-30)
Prüfung: Alle Karten haben den Wochentag in Akzentfarbe an gleicher Stelle.

**R70** Informationen sind nach Wichtigkeit gestaffelt: Termin, Zeit und Status prominent, musikalische Zusatzinformationen klein und zurückhaltend.
Zitat: „die kritik geht in die richtige richtung“ (2026-10-27)
Prüfung: Karte ansehen; Zeit und Status haben das größte Gewicht, Tags und Zusätze das kleinste.

**R71** Die Probenfarbe wird nicht als dünner Streifen am Kartenrand gezeigt; stattdessen wird die Karte selbst gefärbt oder es liegt eine begründete Alternative vor.
Zitat: „Weil der Farbstreifen ist auch eine typische KI-Slop-Design-Eigenschaft.“ (2026-10-27)
Prüfung: Karten ansehen; kein Streifen am Rand, Probenfarbe sitzt anders und bleibt erkennbar.

**R72** Die Zeitleiste im Ablauf unterscheidet Vergangenes, Laufendes und Kommendes eindeutig, ohne umkreiste Punkte und ohne doppelte Markierung.
Zitat: „Das sah vorher sehr clean aus und sehr, sehr sauber.“ (2026-10-24)
Prüfung: Ablauf ansehen; drei Zustände sind auf einen Blick unterscheidbar, es gibt pro Punkt nur eine Markierung.

**R73** Sichtbare Liste und Überblick zeigen verkürzte Information (Anfangszeiten statt Zeitspannen), Detail zeigt alles.
Zitat: „erstmal die Zeiten nicht ganz anzeigt, sondern nur vielleicht die Anfangszeiten“ (2026-10-24)
Prüfung: Liste ansehen; nur Anfangszeit, im Detail die volle Zeitspanne.

## 6. Farbe

**R74** Die Oberfläche ist nicht grau; Grau auf Grau auf Grau ist verboten.
Zitat: „Ich sehe nur grau, über grau, über grau.“ (2026-10-27)
Prüfung: Screenshot auf Graustufen und Farbigkeit prüfen; Karten, Kopfzeilen und Hintergründe sind klar unterscheidbar und warm.

**R75** Das Farbbild folgt dem Jeunesses-Musicales-Branding: saftige, volle Farben, mehr Dopamin, aber professionell.
Zitat: „Du hast es halt zwar modern gemacht, aber das Gegenteil von Dopamin“ (2026-10-24)
Prüfung: Neben JoinJMD-App und Produktions-JMD legen; die Farbwelt wirkt verwandt.

**R76** Der Dark Mode ist nicht durchgehend dunkelblau-grau; Interaktives erkennt man an Farbe und Fläche.
Zitat: „am Ende ist immer noch alles irgendwie dunkelblau-grau“ (2026-10-27)
Prüfung: Dark Mode ansehen; blau nur für Interaktives und Daten, Flächen neutral abgestuft.

**R77** Im Dark Mode bleibt das Layout im Blur-Test lesbar: Karten, Kopfzeilen und Tab-Leiste heben sich sichtbar ab.
Zitat: „wenn man das ein bisschen unscharf sieht, würde ich sagen, ist es schwer zu verstehen“ (2026-10-25)
Prüfung: Screenshot mit Gaußunschärfe prüfen; Aufbau bleibt erkennbar.

**R78** Keine hauchdünnen dekorativen Rahmen, besonders im Dark Mode; getrennt wird durch Fläche, Erhöhung und Abstand.
Zitat: „hauchdünne Borders“ (2026-10-27)
Prüfung: Dark-Mode-Screens nach 1-px-Rändern absuchen; es gibt keine zur Trennung.

**R79** Im Light Mode haben Karten genug Kontrast zum Hintergrund.
Zitat: „bei den Karten, es ist schon sehr viel weiß“ (2026-10-27)
Prüfung: Kontrast Karte zu Hintergrund messen und in Graustufen ansehen.

**R80** Bunte Elemente tragen Bedeutung; ein einzelnes buntes Element in einer grauen Umgebung zieht keine falsche Aufmerksamkeit (z. B. Nutzer-Avatare).
Zitat: „denkt man, die hätten irgendwie eine viel wichtigere Bedeutung“ (2026-10-24)
Prüfung: Unscharf ansehen; nur wirklich Wichtiges sticht farblich heraus.

**R81** Primäre Aktionen und Akzentflächen dürfen Verläufe tragen; der weiche, polierte Gradient-Stil bleibt.
Zitat: „Ich fand bisher eigentlich das Styling mit den Gradients ganz nett.“ (2026-10-24)
Prüfung: Primärbutton und Akzentfläche ansehen; weicher Verlauf, kein Glow-Effekt.

**R82** Graue Boxen hinter Emojis und Hinweisen entfallen; Hinweise bekommen eigene, lebendige Fläche.
Zitat: „dieser graue Box-Hintergrund sieht immer noch sehr tröge aus“ (2026-10-24)
Prüfung: Hinweise und Emoji-Tags ansehen; keine grauen Kästen.

## 7. Text und Wortwahl

**R83** Musiker sind ein Orchester, Chor oder Ensemble, nie ein „Team“.
Zitat: „Und Team klingt ein bisschen, als hätte ich die Zielgruppe verfehlt.“ (2026-10-24)
Prüfung: Suche nach „Team“ in der Oberfläche; kein Treffer.

**R84** Die Wortwahl ist professionell: kein flapsiges „Da“, „Fehlt“ nur als gut verständliches Wort.
Zitat: „da klingt definitiv nicht professionell“ (2026-10-24)
Prüfung: Alle Statuswörter ansehen; keine Umgangssprache.

**R85** Begriffe sind auf den ersten Blick verständlich („nicht eingeplant“, „intern“ genügen nicht).
Zitat: „Ich verstehe aber nicht auf das erste Lesen, was nicht eingeplant heißen soll.“ (2026-10-24)
Prüfung: Blindtest; Testperson erklärt jedes Statuswort ohne Hilfe.

**R86** Bedeutung entsteht durch Bildsprache, Icons und Anordnung, nicht durch Lesen; ein Wort weniger ist immer besser.
Zitat: „Ich muss immer alles lesen.“ (2026-10-24)
Prüfung: Text auf der Ansicht zählen; Funktionen sind auch bei ausgeblendetem Text erschließbar.

**R87** Ein Label, ein Icon oder ein Tag zeigt keine Doppelmarkierung (grüner Punkt neben grünem Punkt, Haken neben Wort für denselben Zustand).
Zitat: „Und auch, dass da nochmal ein grüner Punkt ist, neben dem grünen Punkt“ (2026-10-24)
Prüfung: Karte ansehen; jeder Zustand hat eine Markierung.

## 8. Layout Desktop

**R88** Die Desktopansicht ist für den PC gedacht (dichte Zeilen und Spalten, Liste mit Detailbereich, Hover, Tastatur) und wirkt nicht wie eine gestreckte Mobil-App.
Zitat: „nicht wie eine Mobile-App, die gut gerelayoutet wurde für einen PC“ (2026-10-27)
Prüfung: Desktopscreenshot ansehen; es gibt Mehrspaltigkeit, Tabellenstruktur und sichtbare Hover- und Tastaturzustände.

**R89** Die Desktop-Struktur bleibt nah am Produktions-JMD (Sidebar mit Nutzer und Nächste-Probe-Widget, tabellenartiger Probenplan) und weicht nur ab, wo es klar hilft.
Zitat: „Nicht unnötig abändern vom Original“ (2026-11-25)
Prüfung: Neben Produktions-JMD legen; jede Abweichung ist begründet.

**R90** Auf dem Desktop gibt es keine Aktionen hinter einem Drei-Punkte-Menü; Aktionen stehen sichtbar dort, wo sie gelten.
Zitat: „warum muss ich auf dieses kleine Drei-Punkte-Menü klicken“ (2026-10-27)
Prüfung: Desktop absuchen nach „···“; es gibt kein Menü.

**R91** Desktop-Raum wird genutzt; rechts bleibt keine ungenutzte Fläche, während man vertikal scrollt.
Zitat: „Warum ist rechts so viel Platz frei? Und da muss ich trotzdem scrollen.“ (2026-10-27)
Prüfung: Bei 2000 px und 3440x1440 ansehen; die Breite trägt Inhalt.

**R92** Zweispaltigkeit nur, wenn sie einen Nutzen hat; sie darf Inhalte nicht ohne Grund aufteilen.
Zitat: „warum so unnötig auf zweil cols? einfach nur dumm ux technisch“ (2026-10-30)
Prüfung: Jede Mehrspaltigkeit begründen; die Spalten ergeben jeweils einen eigenen Nutzen.

**R93** Die Desktop-Ansicht sieht nicht schlechter aus als die mobile und ist nicht schlechter geworden als ihre Vorgängerversion.
Zitat: „es ist schlechter geworden“ (2026-10-30)
Prüfung: Vorher und Nachher nebeneinander; jeder Screen ist gleichwertig oder besser.

**R94** Aufgeklappte Karten am Desktop behalten die Zeitleiste, haben keine halbfertigen Notizen und verstecken Links nicht unten.
Zitat: „warum hast du zum Beispiel keine Timeline mehr?“ (2026-10-27)
Prüfung: Heutige Probe am Desktop aufklappen; Zeitleiste vorhanden, Aktionen sichtbar.

**R95** Beim Scrollen überlappt nichts und es entstehen keine zufälligen Ränder.
Zitat: „manche Dinge überlappen, manche Dinge haben so Ränder“ (2026-10-27)
Prüfung: Alle Listen langsam scrollen und in 2x-Crops prüfen.

**R96** „Probenplan drucken“ ist eine sichtbare Aktion in der Kopfzeile oben rechts, und das Druckblatt ist hochwertig gestaltet.
Zitat: „anstatt ihn einfach als schönen Button oben rechts zu haben“ (2026-10-27)
Prüfung: Druckknopf oben rechts; Druckblatt ansehen.

**R97** Die Desktop-Prüfung erfolgt in der Auflösung des Owners (Dark, ca. 2000 px Fenster und 3440x1440).
Zitat: „vor allem auf einem Ultra-Wide-Monitor“ (2026-10-27)
Prüfung: Screenshot in beiden Größen vorliegen und gesichtet haben.

**R98** Mobil und Desktop haben jeweils eigene, optimierte Nutzererfahrung, nicht Mobil-first mit Desktop als Nachgedanke.
Zitat: „für beide Plattformen einfach eine eigene optimierte Nutzererfahrung“ (2026-10-27)
Prüfung: Für beide Plattformen einen eigenen Nutzerpfad beschreiben; sie unterscheiden sich sinnvoll.

## 9. Layout Mobil

**R99** Die mobile Ansicht bleibt unangetastet, wenn am Desktop gearbeitet wird.
Zitat: „Die Mobile-UI sieht schon sehr clean aus.“ (2026-10-27)
Prüfung: Mobile Screens vor und nach einer Desktop-Änderung vergleichen; sie sind identisch.

**R100** Mobil fühlt sich wie eine native Smartphone-App an, nutzt den Bildschirm sparsam und hat angemessene Schriftgrößen.
Zitat: „wie eine native Smartphone-App“ (2026-10-24)
Prüfung: Mobilscreen ansehen; Schriftgrößen und Abstände wie bei nativen Apps, keine zu großen Elemente.

**R101** Vergleichsfähigkeit der Liste: mehrere Termine passen auf einen Bildschirm; Karten sind kompakt.
Zitat: „Die Nutzung vom Screenspace ist auch öfters sehr suboptimal“ (2026-10-24)
Prüfung: Auf 375 px Breite die sichtbare Anzahl Proben zählen und mit der Vorgängerversion vergleichen.

## 10. Konsistenz (gleiche Funktion, gleiche Komponente)

**R102** Zu- und Absagen sieht in jeder Karte, jedem Zustand und jeder Ansicht gleich aus und sitzt am selben Platz.
Zitat: „Du musst bei jeder Probe neu überlegen, okay, wo muss ich jetzt hinschauen“ (2026-10-27)
Prüfung: Karten in allen Zuständen nebeneinander; der Platz der Antwort bleibt gleich.

**R103** Eine Listenkarte hat eine einzige Form und Anatomie, auch für „Heute“.
Zitat: „Warum sind die Sachen alle so unfassbar unterschiedlich?“ (2026-10-25)
Prüfung: Alle Kartentypen in einer Liste vergleichen; gleiche Anatomie, nur Inhalt wechselt.

**R104** Der Zustand verändert nie die Größe der Antwortfläche; die Antwort ist immer gleichgroß und am selben Ort.
Zitat: „bei dem einen sieht man keine Zusage oder Absage-Buttons, bei dem anderen schon“ (2026-10-27)
Prüfung: Karte von offen zu beantwortet umschalten; Position und Größe der Antwortfläche bleiben.

**R105** Eckenradien folgen einem System pro Verschachtelungsebene; kollidierende Radien (Icon im Button) gibt es nicht.
Zitat: „ganz viele verschiedene Rounded Corner Radiuses treffen hier manchmal aufeinander“ (2026-10-25)
Prüfung: Radius jedes verschachtelten Elements messen; das Verhältnis folgt der Regel.

**R106** Eine gute Lösung wird überall verwendet, wo dieselbe Information erscheint; nirgends bleibt eine schlechtere Variante.
Zitat: „wird aber trotzdem an ’ner anderen Stelle von der App ’ne schlechtere Variante verwendet“ (2026-11-25)
Prüfung: Alle Orte für ein Attribut auflisten; sie nutzen dieselbe Komponente.

**R107** Gleiche Funktion, gleiche Steuerung: Anstehend/Vergangen immer als Segment oben; Drucken immer in der Kopfzeile.
Zitat: „Warum ist der Druckenbutton immer noch unten?“ (2026-10-27)
Prüfung: Gleiche Funktionen in allen Ansichten gegenüberstellen; Steuerung und Platz identisch.

**R108** Jede Funktion ist komplett durchgespielt: hinzufügen, ändern, verschieben, entfernen für jeden Fall (mehrtägige Proben, Ablaufpunkte je Tag).
Zitat: „es gibt keinen Weg, hier irgendwas zu ändern an den Ablaufpunkten“ (2026-10-27)
Prüfung: Jeden Editor mit mehrtägiger Probe durchspielen; alle vier Operationen gehen.

**R109** Konsistenz gilt wörtlich für jeden Screen; Hinweise auf Inkonsistenz werden nicht in ein Prinzip verallgemeinert und dann übersprungen.
Zitat: „meine Anweisungen nicht als 1:1 zu sehen, sondern zu generalisieren“ (2026-10-27)
Prüfung: Jeder konkret genannte Fall wird einzeln abgehakt.

**R110** Neue Features sind erlaubt, wenn sie sinnvoll sind; sie müssen konsistent in das System passen.
Zitat: „Die kann man ja nachrüsten, wenn sie sinnvoll sind.“ (2026-10-27)
Prüfung: Jedes neue Element nutzt bestehende Komponenten und Muster.

## 11. Bewegung und Feinschliff

**R111** Alles ist poliert, mit echtem Aufwand, auch durch Übergänge; keine kantigen, ungewollten Ecken beim Aufklappen.
Zitat: „richtig polished und auch richtig viel Aufwand in das Design steckst“ (2026-11-25)
Prüfung: Alle Aufklapper in Zeitlupe prüfen; Ecken bleiben rund, Übergänge laufen weich.

**R112** Icon und Text sind vertikal zentriert, auch in Chips; Zahlen und Labels stehen auf einer Achse.
Zitat: „wirken alle ein bisschen zu tief“ (2026-10-27)
Prüfung: Per Skript Kastenmitten und Kappenhöhen-Mitten messen; Abweichung höchstens 1 px.

**R113** Schatten und Glows werden nicht abgeschnitten.
Zitat: „dass ein Glow abgeschnitten wird“ (2026-10-27)
Prüfung: Schattenausdehnung gegen overflow-Clips prüfen und 2x-Crops ansehen.

**R114** Abstände sind gleichmäßig: oben und unten bei Hinweisen so groß wie seitlich, Linien der Zeitleiste ohne Lücke zu den Punkten.
Zitat: „Die Abstände passen nicht immer ganz perfekt.“ (2026-10-24)
Prüfung: Abstände messen; gleiche Paddings, die Linie berührt den Punkt.

**R115** Icons kommen aus einem Icon-Set, nicht als Unicode-Zeichen.
Zitat: „nicht mal ein Icon aus dem Icon-Set“ (2026-10-24)
Prüfung: Alle Icons im Quelltext prüfen; keine Textzeichen als Icon.

**R116** Wenn am Layout etwas „verschlimmbessert“ ist, wird die Vorgängerversion wiederhergestellt; gutes Bestehendes bleibt.
Zitat: „Ich will auf jeden Fall nicht, dass du das verschlimmbesserst“ (2026-10-25)
Prüfung: Vorher/Nachher nebeneinander auch bei Screens, die niemand kritisiert hat.

## 12. Gelobtes, das bleiben muss

**R117** Rehearsal Cards (Anatomie der alten hellen Karte: farbiger Wochentag, fettes Datum, Meta-Tags, zwei Antwortquadrate) bleiben.
Zitat: „bei den Rehearsal Cards oder auch bei dem Inline-Editor“ (2026-10-24)
Prüfung: Neue Karte neben der alten Produktionskarte; Anatomie ist erkennbar gleich.

**R118** Inline-Editor bleibt.
Zitat: „denke ich, ist das Ganze schon sehr, sehr gut“ (2026-10-24)
Prüfung: Editor ist inline bedienbar wie zuvor.

**R119** Weicher, polierter Stil mit Gradients bleibt.
Zitat: „Ich fand bisher eigentlich das Styling mit den Gradients ganz nett.“ (2026-10-24)
Prüfung: siehe R81.

**R120** Zusammenfassung für die nächste Woche und besondere Hervorhebung der ersten Probe bleiben.
Zitat: „das Zusammenfassen für die nächste Woche“ (2026-10-24)
Prüfung: Probenliste ansehen; Woche und nächste Probe sind hervorgehoben.

**R121** Das eigene Anwesenheitsfeld in vergangenen Proben (man sieht, ob man als anwesend markiert ist) bleibt.
Zitat: „dass man selbst sehen kann, ob man als anwesend markiert wurde“ (2026-10-24)
Prüfung: Vergangene Probe ansehen; eigene Anwesenheit ist sichtbar.

**R122** Die Anwesenheits-Toggle-Komponente (Haken/Kreuz) bleibt.
Zitat: „kommuniziert tatsächlich deutlich besser als mein Component“ (2026-10-25)
Prüfung: Anwesenheit ansehen; Komponente vorhanden.

**R123** Der Orchester-Tab mit Übersicht, Anwesenheit und Mitgliedern bleibt, ebenso sichtbare Affordanzen.
Zitat: „dass man jetzt halt Orchester wirklich als einen ganzen Tab hat“ (2026-10-25)
Prüfung: Tab ansehen; die drei Bereiche sind da.

**R124** Mitglieder: „Beigetreten seit“ und die Rollen-Tag-Übersicht bleiben.
Zitat: „Das ist ein sehr schöner, eine sehr schöne Idee gewesen.“ (2026-10-24)
Prüfung: Mitgliederdetail ansehen; Angaben vorhanden.

**R125** Die Ansicht der Stimmführung mit Termin in Vollansicht und Zähler der erfassten Anwesenheiten bleibt.
Zitat: „dass man oben den Termin in Vollansicht sieht und wie viele erfasst wurden“ (2026-10-24)
Prüfung: Anwesenheitsansicht ansehen; Kopf mit Termin und Zähler vorhanden.

**R126** Wer-kommt-Ansicht in der eigenen Stimmgruppe mit Erinnern, Namen kopieren und Rückgängig bleibt.
Zitat: „dass man auch rückgängig machen kann, ist auch sehr sinnvoll“ (2026-10-24)
Prüfung: Wer-kommt öffnen; Funktionen vorhanden.

**R127** Offene-Rückmeldungen-Ansicht („Alle anzeigen“-Konzept) bleibt als Funktion.
Zitat: „mir gefällt sehr diese Alle-Anzeigen-Ansicht“ (2026-10-24)
Prüfung: Ansicht vorhanden; Bedienung klar (siehe R54).

**R128** Teilen eines Probenlinks bleibt.
Zitat: „Dass man den Probenlink teilen kann, finde ich auch sehr cool.“ (2026-10-24)
Prüfung: Probe teilen vorhanden.

**R129** Tutti bekommt ein kleines Icon im Tag.
Zitat: „dass Tutti ein kleines Icon bekommt in dem Tag, finde ich auch sehr sinnvoll“ (2026-10-24)
Prüfung: Tutti-Tag mit Icon.

**R130** Tabellenansicht mit Weiterführung zur Druckansicht (Fehlzeiten) bleibt.
Zitat: „eine Tabellenansicht zu haben, die dann auch später zur Druckansicht führt“ (2026-10-24)
Prüfung: Tabelle vorhanden, Druck erreichbar.

**R131** Neue-Probe-Auswahl (KI-Import, Serie, mehrere Typen) bleibt, einschließlich der Serien-Oberfläche mit Streichen einzelner Termine.
Zitat: „UI für Serie erstellen und es sieht echt geil aus“ (2026-10-27)
Prüfung: Serie anlegen; einzelne Termine abwählbar.

**R132** Probenort-Auswahl mit Ort plus Raum/Zusatz bleibt.
Zitat: „Du hast es verstanden. Es ist wirklich genau das, was wir brauchen.“ (2026-10-27)
Prüfung: Ort wählen und Raum ergänzen; Anzeige „Ort, Raum“.

**R133** Farbauswahl bleibt, ebenso das Kennzeichnen von Probenarten durch eigene Art.
Zitat: „Den Farbselektor ist sinnvoll gewählt“ (2026-10-27)
Prüfung: Farbauswahl vorhanden und funktional.

**R134** Programmpunkt hinzufügen, Register-Chip-Auswahl samt Kurzbeschreibung, Rollenauswahl, Tags und „Probe absagen“ bleiben.
Zitat: „Programmpunkt hinzufügen. Das ist cool, ja, das sieht sehr schick aus.“ (2026-10-24)
Prüfung: Editor ansehen; alle Elemente vorhanden.

**R135** Die Stimmenauswahl, die Farbschema- und Bewegungsoptionen („Bewegung reduzieren“, „Transparenz reduzieren“) und die App-Installationsansicht bleiben im Look.
Zitat: „Farbschema-Darstellung, alles ganz nett, wirkt alles sehr nativ.“ (2026-10-24)
Prüfung: Einstellungen ansehen; Optionen vorhanden.

**R136** Vergangene Proben als Segment und die Zusammenfassung offener Rückmeldungen bleiben.
Zitat: „das mit den vergangenen Proben finde ich sehr schick gelöst“ (2026-10-27)
Prüfung: Segment vorhanden; Zusammenfassung offener Rückmeldungen sichtbar.

**R137** Die Probenübersicht am Desktop, die Rückmeldungen-Ansicht und das Tag-Design bleiben.
Zitat: „Die Probenübersicht finde ich sehr gelungen.“ (2026-10-27)
Prüfung: Ansichten vorhanden und im Aufbau unverändert.

**R138** Der Prototyp wird nie wieder wholesale ersetzt; die Runde-2-Basis bleibt und wird in kleinen verifizierten Schritten verändert.
Zitat: „Es ist mittlerweile echt gut geworden.“ (2026-10-27)
Prüfung: Diff der Struktur; kein kompletter Neubau.

**R139** Der Charakter der hellen Produktions-App (Gefühl, Wochentagsanker, weiße Karten) bleibt Maßstab.
Zitat: „das weiße hat ein gewisses FEEL und charakter“ (2026-10-27)
Prüfung: Seite an Seite mit Produktion; das Gefühl ist verwandt.

**R140** Das Chevron zum Aufklappen als implizite Bedienung bleibt.
Zitat: „Das ist gutes UX.“ (2026-10-27)
Prüfung: siehe R19.

**R161** Der Gruppenpfad in der Rückmeldungen-Ansicht („Streicher · Violine 2“, andere Gruppen wählbar) bleibt.
Zitat: Lob des Gruppen-Breadcrumbs (2026-10-27)
Prüfung: Rückmeldungen einer Probe öffnen; Pfad sichtbar, Nachbargruppen wählbar.

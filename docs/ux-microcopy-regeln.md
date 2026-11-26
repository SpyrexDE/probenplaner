# Microcopy-Regelwerk Probenplaner

## 1. Erlaubte Texttypen
Jeder sichtbare String ist genau eines von drei Dingen: ein **Name** („Proben", „Ort", „Streicher", „Rückmeldungen"), ein **Wert** („Sa 24.10. · 19:30–22:00", „9/12", „1 Std. vorher", „NBK") oder eine **Aktion** im Infinitiv („Zusagen", „Speichern", „Erinnerung senden"). Alles, was erklärt, beruhigt, Folgen beschreibt oder sagt, was der Nutzer ohnehin annimmt, wird gelöscht.

Fünf Ausnahmen erlauben genau einen Satz: (1) die Folge einer irreversiblen oder ausgehenden Aktion im Bestätigungs-Sheet, (2) eine Nebenwirkung außerhalb der App (Kalender-Link wird ungültig, Push kommt über JOinJMD), (3) eine Formatvorgabe an einem Eingabefeld, (4) der nächste Schritt in einem leeren Zustand, (5) ein Fehler samt Lösung. Hinweise sind ein Satz, höchstens acht Wörter, ohne Punkt. Pro Einstellungsgruppe höchstens ein Fußtext mit höchstens zwölf Wörtern.

Abnahmetest pro Screen: alle Textknoten auflisten. Jeder ist Name, Wert oder Aktion, oder er fliegt. Danach laut vorlesen.

## 2. Verbotene Muster
- Captions, die Label, Geste oder Vorschau wiederholen: „Tippe auf den Status einer Probe, um deine Antwort zu ändern.", „Kompakt zeigt mehr Proben auf einmal.", „So sehen Mitglieder diese Probe."
- Beschreibung des Aus-Zustands oder Doppelung des Labels: „Erinnerungen" plus „Wir erinnern dich, wenn eine Antwort noch fehlt."
- „Wir" und überflüssige Possessive: „Wir erinnern dich …", „deine Antworten", „deinem Kalender". Richtig: „Erinnerung", „Antworten", „Kalender-Abo".
- Ja/Nein/OK-Buttons und „Bist du sicher?".
- Mischung von du und Sie; Denglisch („Account", „Team"); Versalien und Title Case; mehr als ein Ausrufezeichen in der ganzen App; „Oops", „einfach", „schnell".
- Synonymdrift für einen Begriff: Antwort / Rückmeldung / Zu- und Absage; Probe / Termin.
- Zustände als Verben in Tags („Zusagen" als Status).
- Bewertende Zahlen neben Namen (Anwesenheitsquote in der Mitgliederliste).
- Platzhalter als einziges Label; Tooltip als einzige Information; Text unter 12 px.
- Legenden für Symbole, die der Nutzer nach dem ersten Mal kennt.

## 3. Benennung
**Glossar, ein Wort pro Begriff:** Probe (nie Termin). Zusage / Absage (Entscheidung und Aktion). Antwort (eine einzelne Reaktion). Rückmeldungen (die Ansicht aller Antworten eines Termins). Offen (ohne Antwort). Anwesenheit (der Fakt am Abend; Zustände: Anwesend, Fehlt, Entschuldigt, Spät). Abweichung (Anwesenheit ungleich Antwort). Register / Stimme. Leitung. Stimmführung. Mitglieder. Orchester (vierter Tab; der Ensembletyp kommt aus den Daten: Chor, Ensemble). Konto (nie Account). Tags (nie „Interne Tags"). Hinweise. Ablauf. Plan. Kalender-Abo. Erinnerung.

**Grammatik:** Aktion = Infinitiv („Probe absagen", „An 6 senden"). Zustand = Partizip oder Adjektiv („Zugesagt", „Abgesagt", „Offen", „Fällt aus"). Tabs sind einzelne Substantive: Proben | Plan | Orchester | Profil. Segmente ebenso: Übersicht | Anwesenheit | Mitglieder | Verwaltung. Normale Satzschreibung.

**Anrede:** du, überall und konsistent. Neutrale Substantive statt Sternchen-Formen (Leitung, Stimmführung, Mitglieder). „Sie" nur auf Rechtsseiten.

**Längenbudget (Deutsch läuft 1,8- bis 3-mal länger als Englisch):** Tab 1 Wort, höchstens 10 Zeichen. Chip oder Segment 1–2 Wörter, höchstens 12 Zeichen. Button 1–2 Wörter, 3 mit Objekt. Navigationstitel höchstens 15 Zeichen, bei Proben das Datum („Sa 24.10."). Listenzeile und Nebenzeile je eine Zeile, abschneiden statt umbrechen. Sheet-Titel höchstens 2 Zeilen, Body ein Satz. Toast höchstens 6 Wörter plus „Rückgängig".

## 4. Destruktive und ausgehende Aktionen
- Der Button nennt das Ergebnis: „Serie löschen" / „Abbrechen", „Probe absagen" / „Probe behalten". Nie Ja/Nein.
- Bestätigen nur bei seltenen, irreversiblen Aktionen (Serie löschen, Konto löschen, Probe mit Antworten löschen) und bei ausgehenden Aktionen (Push, Link zurücksetzen). Umkehrbares bekommt keinen Dialog, sondern einen Toast mit „Rückgängig" („Probe abgesagt · Rückgängig").
- Ausgehend: Empfängerzahl im Titel und im Button. „Erinnerung an 6 Mitglieder senden?" → „An 6 senden" / „Abbrechen". Danach „6 Erinnerungen gesendet". Zustellung oder Lesen nie behaupten.
- Destruktiv ist rot, nie der Markenverlauf, nie der Default-Button. „Abbrechen" steht immer an derselben Stelle.
- Konto löschen ist der einzige Fall mit getippter Bestätigung.

## 5. Vorher / Nachher aus dem Feedback
- „Deine Antwort" (Caps-Label über den RSVP-Buttons) → kein Label; die beiden Buttons tragen den Zustand.
- „Geantwortet am 22. Okt." → gelöscht.
- „Kein Grund angegeben · bearbeiten" → Zeile nur, wenn ein Grund existiert: „Grund · Dienstreise".
- „Stimme und Rollen kann nur die Leitung ändern" → gelöscht; die Felder zeigen keine Bearbeiten-Affordance.
- „Da" / „Fehlt" → Toggle mit Haken und X, aria-label „Anwesend" / „Fehlt".
- „Zugesagt, aber gefehlt" → amberfarbener Punkt, aria-label „Abweichung von der Zusage".
- „Wer kommt? (14)" → „Rückmeldungen" mit Triadenbalken „✓ 9 · ○ 4 · ✕ 1".
- „Alle anzeigen" → „Alle Stimmen"; der Scope steht im Label.
- „Team" → „Orchester". „Mehr" → entfällt; der vierte Tab heißt für alle „Profil".
- „Interne Tags" → „Tags" mit Schloss-Icon.
- „Bratsche nur 40 % Zusagen" → amberfarbener Punkt und „40 %" am Registerbalken.
- „5 Personen mit mindestens 2 Fehlzeiten in den letzten 10 Proben" → Segment „10 | 20 | Saison" über der Tabelle.
- „Wir erinnern dich vor Proben, auf die du noch nicht geantwortet hast." → zwei Zeilen: „Erinnerung zum Rückmelden · 1 Tag vorher" und „Erinnerung vor der Probe · 1 Std. vorher".
- „Der alte Link funktioniert danach nicht mehr." → Sheet „Link zurücksetzen?", Body „Der bisherige Link wird ungültig.", Buttons „Zurücksetzen" (rot) / „Abbrechen".
- „Die Farbe erscheint als Streifen … Weiß bedeutet kein Streifen" → Live-Swatch auf der Karte, „Keine Farbe" als durchgestrichener Kreis.
- „Dein Account wird endgültig gelöscht. Das kann nicht rückgängig gemacht werden." → „Konto löschen?", Body „Antworten und Notizen werden entfernt.", „Konto löschen" (rot) / „Abbrechen".
- Absagegründe „Weihnachtspause", „Ferien" → „Leitung erkrankt", „Raum nicht verfügbar", „Zu wenige Zusagen", „Sonstiges".
- „Erinnern" am Mitglied → „Zum Rückmelden erinnern", Sheet „Erinnerung an Anna senden?" / „Senden".
- Leerer Zustand: „Keine Proben" + „Sobald die Leitung eine Probe plant, erscheint sie hier." + ein Button.
- Fehler: „Mail oder Passwort stimmt nicht." + Link „Passwort vergessen". Nie „FALSCHES PASSWORT".
- Kalender: Karten „Nur Termine" | „Mit Zu-/Absagen"; Aktionen „Im Kalender abonnieren", „Link kopieren", „Link erneuern"; eine Zeile: „Google übernimmt Änderungen bis zu 24 Stunden später."
- Push: Titel „Probe am Sa 17.10. um 10:00", Body ein ganzer Satz, ein Push pro Probe und Person, keine Aufforderung wie „Bitte jetzt zusagen".
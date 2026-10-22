# JOinJMD-Schnittstelle

Stand: 13.10.2026. Für Ruwen (JOinJMD) und für die Wartung des Probenplaners.

## 1. Überblick

Der JOinJMD-Server hält Orchester, Leitungen und Mitglieder im Probenplaner aktuell, indem er die Partner-API (`/api/v1`) aufruft. Der Probenplaner schickt Erinnerungen an Mitglieder ohne Rückmeldung über die Push-API von JOinJMD. Die JOinJMD-App öffnet Probenplaner-Links in der Probenplaner-Kachel und hängt das Keycloak-Access-Token des Nutzers an; der Probenplaner meldet ihn damit an.

| Begriff | Wert |
|---|---|
| Person | Keycloak-ID (`sub`, Realm `jmd`) = `users.keycloak_id` |
| Orchester | JMD-Orchester-ID = `orchestras.slug`, Vergleich ohne Groß-/Kleinschreibung, erlaubt: `A-Z a-z 0-9 _ -`, 1-100 Zeichen |

## 2. Authentifizierung (Partner-API)

```
Authorization: Bearer <PARTNER_API_TOKEN>
```

- Gemeinsames Geheimnis, erzeugen mit `openssl rand -hex 32`, im `.env` des Probenplaners als `PARTNER_API_TOKEN` hinterlegen und Ruwen auf sicherem Weg geben.
- Kein Session-Cookie, kein CSRF-Token.
- Falsches oder fehlendes Token: `401` mit `WWW-Authenticate: Bearer`.
- API aus (`PARTNER_API_TOKEN` leer oder `KEYCLOAK_ENABLED=false`): jeder Pfad unter `/api/v1` antwortet `404`.

## 3. Endpunkte

Basis: `https://<probenplaner-domain>/api/v1`. Request-Body ist JSON (`Content-Type: application/json`), alle Felder sind Strings und werden getrimmt; fehlende Felder gelten als leer. Antworten sind JSON, außer `204`.

Alle Aufrufe sind idempotent: ein wiederholtes `PUT` ändert nichts mehr (Antwort `200`), ein `DELETE` auf etwas, das es nicht (mehr) gibt, antwortet `204`.

### Fehlerformat

```json
{"error": "orchestra_not_found", "message": "Orchestra not found"}
```

| Status | `error` | Wann |
|---|---|---|
| 400 | `invalid_json` | Body ist kein JSON-Objekt |
| 401 | `unauthorized` | Token fehlt/falsch |
| 404 | `not_found` | API aus oder unbekannter Pfad |
| 404 | `orchestra_not_found` | Orchester gibt es nicht (bei `PUT` auf Leitung/Mitglied) |
| 405 | `method_not_allowed` | Pfad existiert, Methode nicht (Header `Allow: PUT, DELETE`) |
| 409 | `conflict` | Orchester-ID gehört zu einer anderen Organisation |
| 409 | `email_conflict` | E-Mail gehört zu einem Konto mit anderer Keycloak-ID |
| 422 | `invalid_id`, `invalid_sub`, `invalid_field`, `invalid_name`, `invalid_email`, `missing_fields` | Ungültige Eingabe |
| 500 | `config_error`, `internal_error` | Konfiguration fehlt / interner Fehler |

### Konten (gilt für Leitung und Mitglied)

- Konto mit dieser Keycloak-ID vorhanden: wird verwendet. Anzeigename und E-Mail werden **nicht** überschrieben (`display_name` ist nur der Startwert, Mitglieder ändern ihren Anzeigenamen im Profil).
- Sonst: Gibt es ein Probenplaner-Konto mit dieser E-Mail (ohne Keycloak-ID), wird es mit der Keycloak-ID verknüpft, wie beim ersten Login über JOinJMD (lokales Passwort entfällt, CalDAV-Token wird neu erzeugt).
- Sonst wird ein Konto angelegt; dafür sind `email` und `display_name` (max. 100 Zeichen) Pflicht.
- Über die API angelegte Konten gelten als „noch nie im Probenplaner gewesen“ (`users.last_login_at` = NULL), bis die Person den Probenplaner einmal öffnet.

### `PUT /orchestras/{id}`

Legt das Orchester in der Organisation `PARTNER_API_ORG` an (mit Standardrollen „Leitung“ und „Mitglied“, ohne Einladungslinks).

```json
{"name": "Bundesjugendorchester Herbst"}
```

| Status | Body | Wann |
|---|---|---|
| 201 | `{"id": "bjo-herbst", "name": "Bundesjugendorchester Herbst"}` | angelegt |
| 200 | `{"id": "bjo-herbst", "name": "<aktueller Name>"}` | gab es schon; Name bleibt (Leitungen können ihn im Probenplaner ändern) |
| 409 | `conflict` | Slug gehört einem Orchester einer anderen Organisation |
| 422 | `invalid_name` | `name` leer oder länger als 100 Zeichen (nur beim Anlegen) |
| 500 | `config_error` | `PARTNER_API_ORG` ist keine existierende Organisation |

### `DELETE /orchestras/{id}`

Löscht das Orchester mit allen Proben, Rückmeldungen und Mitgliedschaften (wie Löschen im Orga-Panel). Konten bleiben. `204`; `409 conflict` bei fremder Organisation.

### `PUT /orchestras/{id}/members/{sub}`

```json
{"email": "fabian@example.org", "display_name": "Fabian K.", "instrument": "Bratsche", "instrument2": ""}
```

`instrument`/`instrument2` sind die Freitexte aus dem JOinJMD-Profil. Neue Mitglieder bekommen die Standardrolle wie über einen Mitglieder-Einladungslink.

| Status | Body | Wann |
|---|---|---|
| 201 | `{"sub": "f3c1…", "section": "Bratsche", "section_confirmed": true}` | neu Mitglied (auch: war bisher nur Leitung) |
| 200 | wie oben | war schon Mitglied |

`section` ist die Stimme im Probenplaner (`user_orchestras.type`), immer ein gültiges Blatt der Stimmen-Konfiguration des Orchesters (eigene Konfiguration, sonst Standard: `Violine_1`, `Violine_2`, `Bratsche`, `Cello`, `Kontrabass`, `Flöte`, `Oboe`, `Klarinette`, `Fagott`, `Horn`, `Trompete`, `Posaune`, `Tuba`, `Schlagwerk`, `Harfe`). Ohne gültige Stimme sieht ein Mitglied keine Proben.

Zuordnung der Stimme (`SectionMatcher`):

| Fall | Ergebnis |
|---|---|
| `instrument` passt genau auf eine Stimme (ID, Anzeigename, Plural oder Alias, ohne Groß-/Kleinschreibung, `-`/`_` = Leerzeichen), sonst `instrument2` | diese Stimme, `section_confirmed: true` (z. B. „Bratsche“ oder „Viola“ → `Bratsche`) |
| sonst | beste Vorauswahl (ähnlichster Name, z. B. „Geige“/„Violine“ → `Violine_1`; ohne Instrument die erste Stimme), `section_confirmed: false` |

Bei `section_confirmed: false` fragt der Probenplaner das Mitglied beim nächsten Öffnen des Ensembles einmal nach seiner Stimme (Auswahlliste, vorausgewählt). Danach gilt die Stimme als bestätigt.

Bestehende Mitglieder: Eine bestätigte Stimme (vom Mitglied gewählt oder von der Leitung gesetzt) wird nie überschrieben. Eine noch unbestätigte Vorauswahl folgt einem geänderten `instrument`.

### `DELETE /orchestras/{id}/members/{sub}`

Beendet die Mitgliedschaft, das Konto bleibt. Ist die Person auch Leitung, bleibt sie nur Leitung. `204`, auch wenn Orchester, Person oder Mitgliedschaft fehlen.

### `PUT /orchestras/{id}/leaders/{sub}`

```json
{"email": "lea@example.org", "display_name": "Lea M."}
```

Gibt die Rolle „Leitung“. Neue Personen kommen wie über einen Leitungs-Einladungslink dazu (nur Rolle „Leitung“, keine Stimme). Ein Mitglied behält Stimme und Rollen.

| Status | Body | Wann |
|---|---|---|
| 201 | `{"sub": "9a7e…", "role": "Leitung"}` | Rolle neu vergeben |
| 200 | wie oben | war schon Leitung |

### `DELETE /orchestras/{id}/leaders/{sub}`

Nimmt die Rolle „Leitung“. War die Person nur Leitung, endet die Mitgliedschaft. Bleibt sie Mitglied, aber ohne Stimme, bekommt sie die erste Stimme als Vorauswahl und bestätigt sie beim nächsten Öffnen. `204`.

## 4. Push-Benachrichtigungen

Schnittstelle von JOinJMD (digil-chat, Spezifikation von Ruwen vom 22.10.2026), Umsetzung in `src/Core/JmdPush.php`.

### Was der Probenplaner sendet

```
POST https://oxtutmhbtwtncqzimogb.supabase.co/functions/v1/external-api/push   (JMD_PUSH_URL)
Content-Type: application/json
Accept: application/json
Authorization: Bearer <JMD_PUSH_TOKEN>      (DIGIL_CHAT_API_KEY)
```

```json
{
  "keycloak_user_ids": ["f3c1…", "9a7e…"],
  "title": "Probe am Sa 17.10. um 10:00",
  "body": "JSO Bremen: Du hast noch nicht zu- oder abgesagt.",
  "data": { "url": "https://probenplaner.jmd.info/jmd/jso-bremen/promises?rehearsal=123" }
}
```

| Feld | Inhalt |
|---|---|
| `keycloak_user_ids` | Keycloak-IDs (`sub`) der Empfänger, ohne Duplikate; API-Grenze 1–500 |
| `title` | Probentyp (z. B. „Probe“), Wochentag, Datum, Uhrzeit; API-Grenze 200 Zeichen |
| `body` | `<Ensemblename>: Du hast noch nicht zu- oder abgesagt.`; API-Grenze 1.000 Zeichen |
| `data.url` | `https://<Domain>/<org_slug>/<orchestra_slug>/promises?rehearsal=<Proben-ID>`; Domain aus `KEYCLOAK_REDIRECT_URI` |

Antwort `{"success": true, "results": [{"keycloak_user_id": "…", "status": "sent"}]}`, Status je Person `sent` (Versand angenommen), `no_device` (kein Push-Gerät registriert) oder `user_not_found` (in der Chat-App unbekannt). Die Leitung sieht „Erinnerung an 5 gesendet“ bzw. „an 5 von 8“, wenn nicht alle `sent` sind; die Verteilung der Status wird dann geloggt.

Fehler (`400`, `401`, `429`, `500`, Timeout 10 s) werden geloggt und der Leitung als Fehlermeldung angezeigt; der Knopf bleibt. Die API ist nicht idempotent, deshalb gibt es keine automatische Wiederholung. Ratenlimit 60 Aufrufe/Minute pro Schlüssel (ein Aufruf pro Klick).

Nicht genutzt: `POST …/external-api/planner-message` (dauerhafte Nachricht im schreibgeschützten Probenplaner-Chat, max. 4.000 Zeichen, gleiche Header und `keycloak_user_ids`, Felder `text` und `sender_name`).

### Wann

- Knopf „Erinnern“ auf einer Probe im Rückmeldungs-Dashboard (Leitung bzw. Verwaltung), neben der Zahl ohne Rückmeldung, nach Bestätigung.
- Nur für kommende Proben und nur, wenn Push eingerichtet ist (`KEYCLOAK_ENABLED` und `JMD_PUSH_URL`).
- Empfänger: Personen ohne Rückmeldung im Bereich, den die Karte zeigt (eigene Stimme, Stimmgruppe oder ganzes Orchester; in der Verwaltung das ganze Orchester), mit Keycloak-ID und **die den Probenplaner schon einmal geöffnet haben** (`last_login_at` nicht NULL). Gibt es keine, erscheint der Knopf nicht.

### Erinnerung vor Proben

Mitglieder mit Keycloak-ID schalten im Profil unter „Push-Erinnerung“ eine Erinnerung ein und wählen, wie lange vorher (00:01 bis 23:59, Std:Min; `users.push_reminder_minutes`, NULL = aus). `PushReminderJob` schickt sie einmal pro Mitglied und Probe:

- für jede Probe, zu der die Person eingeladen ist (gleiche Regel wie im Rückmeldungs-Dashboard) und nicht abgesagt hat;
- zugesagt: Titel wie oben, Text `<Ensemblename>, <Ort>`; ohne Rückmeldung: Titel und Text wie beim Knopf „Erinnern“;
- Link wie oben; ein Aufruf pro Probe und Text.

Ist die Probe beim Einschalten schon näher als der Vorlauf, kommt die Erinnerung sofort. Gesendete Erinnerungen stehen in `push_reminders` und werden vor dem Aufruf eingetragen; ein fehlgeschlagener Aufruf wird nicht wiederholt.

### Was die JOinJMD-App mit dem Link macht

Die App öffnet `link` in der Probenplaner-Kachel und hängt das Access-Token an:

```
<link>&jmd_accesstoken=<token>     (link enthält schon ein ?)
<link>?jmd_accesstoken=<token>     (sonst)
```

Der Probenplaner dann (`JmdTokenLogin::handleDeepLink`, für jeden GET-Pfad außer `/`, `/login` und `/api/v1/…`):

1. prüft das Token bei Keycloak (Userinfo) und meldet die Person an (Konto wird über die Keycloak-ID gefunden oder angelegt);
2. leitet mit `303` auf dieselbe URL **ohne** Token weiter (Token taucht nicht im Verlauf auf; Apache loggt solche Anfragen ohne Query);
3. die Rückmeldeseite scrollt zur Probe `rehearsal` und hebt sie hervor.

Token ungültig: Ist die Person in der Kachel noch angemeldet, öffnet sich die Seite trotzdem; sonst Weiterleitung auf `/login` mit Fehlermeldung.

## 5. Kachel-Link (bestehend)

```
https://<Domain>/?jmd_accesstoken=<token>&orchestra=<JMD-Orchester-ID>
```

Meldet an wie oben. `orchestra` wählt unter den eigenen aktiven Mitgliedschaften das Orchester mit diesem Slug aus und öffnet dessen Rückmeldeseite (Leitung mit Ensemble-Verwaltungsrecht: `/promises/admin`). Es gibt nie Zugang zu einem fremden Orchester. `/login?jmd_accesstoken=…` funktioniert gleich.

## 6. Betrieb

### Umgebungsvariablen (`.env`)

| Variable | Bedeutung | Leer/aus |
|---|---|---|
| `KEYCLOAK_ENABLED` | Keycloak-/JOinJMD-Login | `false`: Partner-API, Token-Login und Push sind aus |
| `PARTNER_API_TOKEN` | Bearer-Token der Partner-API | API aus, alle `/api/v1`-Pfade `404` |
| `PARTNER_API_ORG` | Slug der Organisation, in der API-Orchester liegen; die API sieht nur Orchester dieser Organisation | `PUT /orchestras/{id}` für neue Orchester → `500 config_error`; bestehende gelten als fremd (`409`/`404`) |
| `JMD_PUSH_URL` | Endpunkt der JOinJMD-Push-API | kein Push, kein „Erinnern“-Knopf |
| `JMD_PUSH_TOKEN` | Bearer-Token für die Push-API | kein `Authorization`-Header |

`KEYCLOAK_REDIRECT_URI` bestimmt die Domain der Push-Links. Lokale Instanzen ohne diese Werte verhalten sich wie bisher (Passwort-Login, Registrierung, Einladungslinks unverändert).

### Migration

`database/migrations/20261013_120000_partner_api_sync.sql` läuft beim Containerstart automatisch (`docker/startup.sh` → `php cli-migrate.php up`):

- `users.last_login_at` (NULL = nie angemeldet; bestehende Konten werden mit `created_at` befüllt, wird bei jedem Login gesetzt)
- `user_orchestras.section_confirmed` (0 = Stimme aus dem JOinJMD-Instrument geraten, Mitglied bestätigt noch)

`database/migrations/20261022_120000_push_reminders.sql`: `users.push_reminder_minutes`, Tabelle `push_reminders`.

Organisations-Slug `api` ist reserviert (Pfad `/api/v1`).

### Erinnerungs-Job

Ist `JMD_PUSH_URL` gesetzt, startet `docker/startup.sh` im web-Container eine Schleife: `php /var/www/html/jobs/push-reminders.php`, danach 60 s Pause. Läufe überschneiden sich nie. Fehler stehen in `docker compose -f docker-compose.prod.yml logs web` (`PushReminders …`, fehlgeschlagene Aufrufe als `JOinJMD push failed …`). Von Hand: `docker compose -f docker-compose.prod.yml exec web php /var/www/html/jobs/push-reminders.php`.

### Smoke-Test

```sh
T=$(grep '^PARTNER_API_TOKEN=' .env | cut -d= -f2-)
B=https://<Domain>/api/v1

# 401 ohne Token, 405 mit Allow-Header
curl -i -X PUT $B/orchestras/pp-smoketest
curl -i -H "Authorization: Bearer $T" $B/orchestras/pp-smoketest

# Orchester, Mitglied, Leitung anlegen (201), wiederholen (200)
curl -i -X PUT -H "Authorization: Bearer $T" -H 'Content-Type: application/json' \
  -d '{"name":"Smoke-Test"}' $B/orchestras/pp-smoketest
curl -i -X PUT -H "Authorization: Bearer $T" -H 'Content-Type: application/json' \
  -d '{"email":"smoke@example.org","display_name":"Smoke T.","instrument":"Geige"}' \
  $B/orchestras/pp-smoketest/members/smoke-sub-1
curl -i -X PUT -H "Authorization: Bearer $T" -H 'Content-Type: application/json' \
  -d '{}' $B/orchestras/pp-smoketest/leaders/smoke-sub-1

# Aufräumen (204); das Testkonto smoke@example.org bleibt bestehen
curl -i -X DELETE -H "Authorization: Bearer $T" $B/orchestras/pp-smoketest
```

Lokal: `JMD_PUSH_URL=http://localhost/debug/push-mock.php` nimmt Pushes an; `GET http://localhost:8080/debug/push-mock.php` zeigt die letzten 20 (nur mit `APP_ENV` development).

Log: eine Zeile pro API-Aufruf (`PartnerApi <Methode> <Route> <Status>`, ohne personenbezogene Daten) im PHP-Error-Log.

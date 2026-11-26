# Probenplaner – App-Prototyp

Klickbarer UI-Prototyp für das Redesign des Probenplaners als native wirkende Smartphone-App.
Nur Frontend: Demo-Daten aus `js/data.js`, kein Backend, nichts wird gespeichert außer Rolle und Theme im `localStorage`.

## Starten

Im Repo-Root `php -S localhost:5173 -t prototype` ausführen, dann http://localhost:5173 öffnen.

## Aufbau

- `index.html` – App-Shell (Header, Inhalt, Tabbar, Rail für Desktop, Ebenen für Sheets und Toasts), lädt Lucide-Icons per CDN
- `css/` – `tokens.css` (Design-Tokens), `base.css` (Shell: Header, Tabbar, Rail/Sidebar, Ebenen, Seitenübergänge), `components.css` (Komponentenkatalog), `screens.css` (`.screen`, `.split`), `screens/*.css` (pro Screen-Bereich)
- `js/app.js` – Hash-Router, Store, Shell-Helfer (`setHeader`, `openSheet`, `toast`, `h`, `html`, `icon`, Datumsformatierung); `TABS` legt die Tabs pro Rolle fest, `ROUTES` ist `[pattern, screen, tab]`
- `js/shell-nav.js` – Desktop-Shell ab 1200 px (Seitenleiste als Standard, alte Varianten per `?nav=top|jmd|rail`) und zweite Spalte der Proben; Tabbar (unter 600 px), Schiene (600–1199 px) und Seitenleiste baut `renderNav` in `app.js` aus `TABS`
- `js/ui.js` – gemeinsame Bausteine (Probenkarte, Datums-Chip, Status-Pill, Triade, Avatar, Segmented Control, Sheets) und Zählerlogik für die Tab-Badges
- `js/data.js` – Demo-Daten
- `js/screens/` – ein Modul pro Screen, `index.js` importiert alle
- `assets/` – Logo (`icon.svg`, `label.svg`)

## Screen hinzufügen

1. `js/screens/<name>.js` anlegen und `registerScreen('<name>', { title, tab, render(root, params, ctx) })` aus `../app.js` aufrufen. `name` muss zu einem Eintrag in `ROUTES` passen, `tab` ist die Tab-Route, die aktiv markiert wird.
2. Den Dateinamen in die Liste `SCREENS` in `js/screens/index.js` aufnehmen. Die Module werden nach `placeholder.js` per `Promise.allSettled` geladen, eine fehlende Datei blockiert den Start nicht.

## Rollen

Rollen: `member` (Mitglied), `leader` (Stimmführung), `conductor` (Leitung). Die aktuelle Rolle steht in `localStorage` (`pp.role`) und als `data-role` auf `<html>`.
Wechseln über `setRole()` (z. B. im Profil), per URL `?role=conductor` oder in der Konsole mit `pp.setRole('leader')`.
Die Tabbar wird neu aufgebaut; ist der aktuelle Tab für die neue Rolle nicht vorhanden, springt die App auf deren ersten Tab.
Das Theme funktioniert genauso: `pp.theme`, `?theme=dark`, `pp.setTheme('system')`.

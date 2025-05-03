# JSO-Planer

Eine Webanwendung zur Planung von Proben und Verwaltung von Zusagen für ein Orchester.

## Funktionen

- Benutzer-Authentifizierung (Login/Registrierung)
- Anzeige des Probenplans für das jeweilige Instrument/die jeweilige Stimmgruppe
- Zu- und Absagen zu Proben mit Anmerkungen
- Dirigenten-Dashboard zur Übersicht aller Zu- und Absagen
- Stimmgruppenleiter-Bereich zur Verwaltung der eigenen Gruppe

## Technologien

- PHP 8.0
- MySQL 8.0
- Bootstrap 4
- jQuery
- Docker für Entwicklung und Produktion

## Installation

### Mit Docker (empfohlen)

1. Stelle sicher, dass [Docker](https://www.docker.com/get-started) und [Docker Compose](https://docs.docker.com/compose/install/) installiert sind.

2. Klone das Repository:
   ```
   git clone https://github.com/yourusername/jso-planer.git
   cd jso-planer
   ```

3. Starte die Container:
   ```
   docker-compose up -d
   ```

4. Die Anwendung ist nun unter [http://localhost:8080](http://localhost:8080) erreichbar.
   
5. PHPMyAdmin ist unter [http://localhost:8081](http://localhost:8081) erreichbar (Benutzername: root, Passwort: root).

### Manuelle Installation

1. Stelle sicher, dass PHP 8.0+ und MySQL 8.0+ installiert sind.

2. Klone das Repository in deinen Webserver-Ordner:
   ```
   git clone https://github.com/yourusername/jso-planer.git
   ```

3. Importiere die Datenbankstruktur:
   ```
   mysql -u dein_benutzer -p < database/init/01-schema.sql
   mysql -u dein_benutzer -p < database/init/02-sample-data.sql
   ```

4. Passe die Datenbankverbindung in `src/config/config.php` an.

5. Stelle sicher, dass der Webserver Zugriff auf das `src/public` Verzeichnis hat und die URL zur `index.php` führt.

## Testbenutzer

Nach der Installation mit den Beispieldaten stehen folgende Testbenutzer zur Verfügung:

| Benutzer | Passwort     | Rolle                      |
|----------|--------------|----------------------------|
| Martin   | Bremen-Mitte | Dirigent                   |
| Anna     | test1234     | Violine 1                  |
| Max ♚    | test1234     | Violine 2 (Stimmführer)    |
| Sophie   | test1234     | Bratsche                   |
| David    | test1234     | Cello                      |
| Julia ♚  | test1234     | Flöte (Stimmführerin)      |
| Marc     | test1234     | Trompete                   |
| Lena     | test1234     | Klarinette                 |

## Projekt-Struktur

```
.
├── database               # Datenbankskripte und Migrations
│   └── init              # Initialskripte für die Datenbank
├── docker                # Docker-Konfigurationsdateien
├── src                   # Quellcode der Anwendung
│   ├── Controllers       # Controller-Klassen
│   ├── Core              # Kern-Klassen (Router, Controller, Model, Database)
│   ├── Models            # Model-Klassen
│   ├── Views             # View-Templates
│   ├── config            # Konfigurationsdateien
│   └── public            # Öffentlich zugängliche Dateien
│       ├── assets        # Assets (CSS, JS, Bilder)
│       └── index.php     # Haupteinstiegspunkt
└── docker-compose.yml    # Docker Compose Konfiguration
```

## Entwicklung

### Empfohlene Entwicklungsumgebung

- PHP-IDE (z.B. PhpStorm, Visual Studio Code mit PHP-Erweiterungen)
- Docker und Docker Compose
- Git

### Coding Standards

- PSR-4 Autoloading
- Klare Trennung von Model, View und Controller
- Dokumentation mit PHPDoc-Kommentaren

## Mitwirkende

- [Dein Name](https://github.com/yourusername)

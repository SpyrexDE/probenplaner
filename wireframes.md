# Probenplaner – Wireframes v4

> Nur neue oder stark veränderte Views. Jede Wireframe zeigt die komplette Seite.

---

## Architektur

```
Super-Admin ─── verwaltet Organisationen (ADMIN_PW)
     │
Orga-Account ── verwaltet Ensembles einer Org (1 Account pro Org, normaler Login)
     │
Ensemble ────── Dirigent & Spieler (Permission-basiert)
```

Alle Account-Typen nutzen denselben Login-Screen.

---

## 1. Super-Admin Panel

Erreichbar über `/admin`. Passwort: `ADMIN_PW` aus `.env`.

### 1a. Login

```
┌───────────────────────────────────────────────────────────────┐
│                                                               │
│                    [Probenplaner Logo]                         │
│                                                               │
│              ┌──────────────────────────────────┐             │
│              │                                  │             │
│              │   🔒 Admin-Zugang                │             │
│              │                                  │             │
│              │   Admin-Passwort:                │             │
│              │   [________________________]     │             │
│              │                                  │             │
│              │   [      Anmelden          ]     │             │
│              │                                  │             │
│              └──────────────────────────────────┘             │
│                                                               │
└───────────────────────────────────────────────────────────────┘
```

### 1b. Dashboard

```
┌───────────────────────────────────────────────────────────────┐
│ ☰  Admin Panel                                      [Logout] │
├──────────────┬────────────────────────────────────────────────┤
│              │                                                │
│  🏢 Organi-  │  🏢 Organisationen               [+ Erstellen] │
│  sationen ●  │                                                │
│              │  ┌─ Übersicht ──────────────────────────────┐  │
│              │  │  2 Organisationen · 6 Ensembles ·        │  │
│              │  │  342 Nutzer                               │  │
│              │  └──────────────────────────────────────────┘  │
│              │                                                │
│              │  ┌──────────────────────────────────────────┐  │
│              │  │  🏢  Jeunesses Musicales Deutschland     │  │
│              │  │      Slug: jmd                            │  │
│              │  │      4 Ensembles · 289 Nutzer             │  │
│              │  │                                           │  │
│              │  │      Orga-Account: jmd-admin              │  │
│              │  │      [PW anzeigen] [🔄 PW neu generieren] │  │
│              │  │                                           │  │
│              │  │                     [Bearbeiten]  [🗑️]    │  │
│              │  └──────────────────────────────────────────┘  │
│              │                                                │
│              │  ┌──────────────────────────────────────────┐  │
│              │  │  🏢  Musikverein Harmonie                │  │
│              │  │      Slug: mvh                            │  │
│              │  │      2 Ensembles · 53 Nutzer              │  │
│              │  │                                           │  │
│              │  │      Orga-Account: mvh-admin              │  │
│              │  │      [PW anzeigen] [🔄 PW neu generieren] │  │
│              │  │                                           │  │
│              │  │                     [Bearbeiten]  [🗑️]    │  │
│              │  └──────────────────────────────────────────┘  │
│              │                                                │
│ ──────────── │                                                │
│  🚪 Logout   │                                                │
│              │                                                │
├──────────────┤                                                │
│ Probenplaner │                                                │
│ · v2.0       │                                                │
└──────────────┴────────────────────────────────────────────────┘
```

### 1c. Organisation erstellen

```
┌───────────────────────────────────────────────────────────────┐
│ ☰  Admin Panel                                      [Logout] │
├──────────────┬────────────────────────────────────────────────┤
│              │                                                │
│  🏢 Organi-  │  ← Zurück                                     │
│  sationen ●  │                                                │
│              │  Neue Organisation erstellen                   │
│              │                                                │
│              │  Name:                                         │
│              │  ┌──────────────────────────────────────┐     │
│              │  │ ________________________________     │     │
│              │  └──────────────────────────────────────┘     │
│              │                                                │
│              │  Slug:                                         │
│              │  ┌──────────────────────────────────────┐     │
│              │  │ ________________________________     │     │
│              │  └──────────────────────────────────────┘     │
│              │                                                │
│              │  Orga-Account wird automatisch erstellt        │
│              │  (Benutzername: {slug}-admin).                 │
│              │                                                │
│              │  [          Erstellen          ]               │
│              │                                                │
│              │  ─── Nach Erstellung: ───                      │
│              │  ┌──────────────────────────────────────┐     │
│              │  │  ✅ Organisation "JMD" erstellt      │     │
│              │  │                                      │     │
│              │  │  Orga-Account:                       │     │
│              │  │  Benutzer: jmd-admin                 │     │
│              │  │  Passwort: aX9k#mQ2                  │     │
│              │  │                                [📋]  │     │
│              │  │                                      │     │
│              │  │  ⚠️ Passwort jetzt notieren!         │     │
│ ──────────── │  └──────────────────────────────────────┘     │
│  🚪 Logout   │                                                │
│              │                                                │
├──────────────┤                                                │
│ Probenplaner │                                                │
│ · v2.0       │                                                │
└──────────────┴────────────────────────────────────────────────┘
```

### 1d. Organisation bearbeiten

```
┌───────────────────────────────────────────────────────────────┐
│ ☰  Admin Panel                                      [Logout] │
├──────────────┬────────────────────────────────────────────────┤
│              │                                                │
│  🏢 Organi-  │  ← Zurück                                     │
│  sationen ●  │                                                │
│              │  JMD bearbeiten                                │
│              │                                                │
│              │  ┌─ Allgemein (auto-save) ──────────────────┐  │
│              │  │                                          │  │
│              │  │  Name:  [Jeunesses Musicales Deutschl.]  │  │
│              │  │  Slug:  [jmd]                             │  │
│              │  │                                          │  │
│              │  └──────────────────────────────────────────┘  │
│              │                                                │
│              │  ┌─ Gefährliche Aktionen ───────────────────┐  │
│              │  │  [ Organisation löschen ]                 │  │
│              │  └──────────────────────────────────────────┘  │
│              │                                                │
│ ──────────── │                                                │
│  🚪 Logout   │                                                │
│              │                                                │
├──────────────┤                                                │
│ Probenplaner │                                                │
│ · v2.0       │                                                │
└──────────────┴────────────────────────────────────────────────┘
```

---

## 2. Orga-Panel

Login über den normalen `/login`-Screen (Email-Feld: z.B. `jmd-admin`). Verwendet Sidebar-Layout.

### 2a. Dashboard – Ensembles

```
┌───────────────────────────────────────────────────────────────┐
│ ☰  Orga-Panel                                                │
├──────────────┬────────────────────────────────────────────────┤
│              │                                                │
│  [J]         │  🎵 Ensembles                  [+ Erstellen]  │
│  jmd-admin   │                                                │
│  JMD         │  ┌──────────────────────────────────────────┐  │
│              │  │  🎵  JSO Bremen                          │  │
│ ──────────── │  │      /jmd/jso-bremen · 58 Mitglieder      │  │
│              │  │      Leitung: Vera Schmidt,               │  │
│  🎵 Ensem-   │  │               Klaus Weber                 │  │
│  bles ●      │  │                                           │  │
│              │  │                     [Bearbeiten]  [🗑️]    │  │
│              │  └──────────────────────────────────────────┘  │
│              │                                                │
│              │  ┌──────────────────────────────────────────┐  │
│              │  │  🎵  Jugendkammerorchester               │  │
│              │  │      /jmd/jk-bremen · 24 Mitglieder       │  │
│              │  │      Leitung: Klaus Weber                 │  │
│              │  │                                           │  │
│              │  │                     [Bearbeiten]  [🗑️]    │  │
│              │  └──────────────────────────────────────────┘  │
│              │                                                │
│              │  ┌──────────────────────────────────────────┐  │
│              │  │  🎵  Bläserphilharmonie                  │  │
│              │  │      /jmd/bph · 35 Mitglieder             │  │
│              │  │      Leitung: —                           │  │
│              │  │                                           │  │
│              │  │                     [Bearbeiten]  [🗑️]    │  │
│              │  └──────────────────────────────────────────┘  │
│              │                                                │
│  [📲 App]    │                                                │
│  🚪 Logout   │                                                │
│              │                                                │
├──────────────┤                                                │
│ Probenplaner │                                                │
│ · v2.0       │                                                │
└──────────────┴────────────────────────────────────────────────┘
```

### 2b. Ensemble erstellen

```
┌───────────────────────────────────────────────────────────────┐
│ ☰  Orga-Panel                                                │
├──────────────┬────────────────────────────────────────────────┤
│              │                                                │
│  [J]         │  ← Zurück zu Ensembles                        │
│  jmd-admin   │                                                │
│  JMD         │  Neues Ensemble erstellen                     │
│              │                                                │
│ ──────────── │  Name:                                         │
│              │  ┌──────────────────────────────────────┐     │
│  🎵 Ensem-   │  │ ________________________________     │     │
│  bles ●      │  └──────────────────────────────────────┘     │
│              │                                                │
│              │  Kürzel (URL):                                 │
│              │  ┌──────────────────────────────────────┐     │
│              │  │ ________________________________     │     │
│              │  └──────────────────────────────────────┘     │
│              │  → {APP_URL}/jmd/{kürzel}                      │
│              │                                                │
│              │  ─── Leitung zuweisen (optional) ───           │
│              │                                                │
│              │  Generiere einen Einladungslink für die        │
│              │  zukünftige Leitung dieses Ensembles.          │
│              │                                                │
│              │  [  🔗 Leitungs-Link generieren  ]            │
│              │                                                │
│              │  ┌──────────────────────────────────────┐     │
│              │  │ {APP_URL}/invite/xK9m..         [📋] │     │
│              │  └──────────────────────────────────────┘     │
│              │  Diesen Link an die Leitung senden.            │
│              │  ☑ Nur JMD-Accounts erlauben                   │
│              │                                                │
│              │  [          Erstellen                 ]        │
│              │                                                │
│  [📲 App]    │                                                │
│  🚪 Logout   │                                                │
│              │                                                │
├──────────────┤                                                │
│ Probenplaner │                                                │
│ · v2.0       │                                                │
└──────────────┴────────────────────────────────────────────────┘
```

### 2c. Ensemble bearbeiten

```
┌───────────────────────────────────────────────────────────────┐
│ ☰  Orga-Panel                                                │
├──────────────┬────────────────────────────────────────────────┤
│              │                                                │
│  [J]         │  ← Zurück zu Ensembles                        │
│  jmd-admin   │                                                │
│  JMD         │  JSO Bremen bearbeiten                        │
│              │                                                │
│ ──────────── │  ┌─ Allgemein (auto-save) ──────────────────┐  │
│              │  │                                          │  │
│  🎵 Ensem-   │  │  Name:   [Jugend-Sinfonieorchester Brem] │  │
│  bles ●      │  │  Kürzel: [jso-bremen]                     │  │
│              │  │                                          │  │
│              │  └──────────────────────────────────────────┘  │
│              │                                                │
│              │  ┌─ Leitung ────────────────────────────────┐  │
│              │  │                                          │  │
│              │  │  [V]  Vera Schmidt          [Entfernen]  │  │
│              │  │  [K]  Klaus Weber           [Entfernen]  │  │
│              │  │                                          │  │
│              │  │  [🔗 Neuen Leitungs-Link generieren]     │  │
│              │  │  ☑ Nur JMD-Accounts erlauben              │  │
│              │  └──────────────────────────────────────────┘  │
│              │                                                │
│              │  ┌─ Statistiken ────────────────────────────┐  │
│              │  │  58 Mitglieder · 12 Proben geplant       │  │
│              │  └──────────────────────────────────────────┘  │
│              │                                                │
│              │  ┌─ Gefährliche Aktionen ───────────────────┐  │
│              │  │  [ Ensemble löschen ]                     │  │
│              │  └──────────────────────────────────────────┘  │
│              │                                                │
│  [📲 App]    │                                                │
│  🚪 Logout   │                                                │
│              │                                                │
├──────────────┤                                                │
│ Probenplaner │                                                │
│ · v2.0       │                                                │
└──────────────┴────────────────────────────────────────────────┘
```

---

## 3. Ensemble-Auswahl (verändert)

Invite-Link hinter Button + eigenem View statt Inline-Textfeld.

```
┌───────────────────────────────────────────────────────────────┐
│                                                               │
│                    [Probenplaner Logo]                         │
│                  Ensemble auswählen                            │
│                                                               │
│              ┌──────────────────────────────────┐             │
│              │                                  │             │
│              │  ┌────────────────────────────┐  │             │
│              │  │ 🎵  JSO Bremen        [→]  │  │             │
│              │  │     Violine 1               │  │             │
│              │  │     Ensemble-Leitung 🟢     │  │             │
│              │  └────────────────────────────┘  │             │
│              │                                  │             │
│              │  ┌────────────────────────────┐  │             │
│              │  │ 🎵  Jugendkammer-     [→]  │  │             │
│              │  │     orchester               │  │             │
│              │  │     Violine 1               │  │             │
│              │  └────────────────────────────┘  │             │
│              │                                  │             │
│              │  [  🔗 Einladungslink einlösen ]  │             │
│              │                                  │             │
│              │  ← Abmelden                      │             │
│              └──────────────────────────────────┘             │
│                                                               │
└───────────────────────────────────────────────────────────────┘
```

### 3a. Einladungslink einlösen (eigener View nach Button-Klick)

```
┌───────────────────────────────────────────────────────────────┐
│                                                               │
│                    [Probenplaner Logo]                         │
│                                                               │
│              ┌──────────────────────────────────┐             │
│              │                                  │             │
│              │  🔗 Einladungslink einlösen       │             │
│              │                                  │             │
│              │  ┌────────────────────────────┐  │             │
│              │  │ Link hier einfügen...      │  │             │
│              │  └────────────────────────────┘  │             │
│              │                                  │             │
│              │  [        Beitreten            ]  │             │
│              │                                  │             │
│              │  ← Zurück zur Auswahl            │             │
│              └──────────────────────────────────┘             │
│                                                               │
└───────────────────────────────────────────────────────────────┘
```

---

## 4. Onboarding: Anzeigename

Centered-Card mit Gradient-Background (bestehende Auth-Layout-Variante). Wird gezeigt wenn `display_name` leer ist.

```
┌───────────────────────────────────────────────────────────────┐
│  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  │
│  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  │
│  ░░░░░░░░░    [Probenplaner Logo]                    ░░░░░░  │
│  ░░░░░░░░░                                           ░░░░░░  │
│  ░░░░░░░░░  ┌──────────────────────────────────┐     ░░░░░░  │
│  ░░░░░░░░░  │                                  │     ░░░░░░  │
│  ░░░░░░░░░  │  Willkommen! 👋                   │     ░░░░░░  │
│  ░░░░░░░░░  │                                  │     ░░░░░░  │
│  ░░░░░░░░░  │  Wie möchtest du in deinem        │     ░░░░░░  │
│  ░░░░░░░░░  │  Ensemble angezeigt werden?       │     ░░░░░░  │
│  ░░░░░░░░░  │                                  │     ░░░░░░  │
│  ░░░░░░░░░  │  ┌────────────────────────────┐  │     ░░░░░░  │
│  ░░░░░░░░░  │  │ z.B. Vera S.              │  │     ░░░░░░  │
│  ░░░░░░░░░  │  └────────────────────────────┘  │     ░░░░░░  │
│  ░░░░░░░░░  │  So erkennt dich dein Register    │     ░░░░░░  │
│  ░░░░░░░░░  │  und die Leitung.                 │     ░░░░░░  │
│  ░░░░░░░░░  │                                  │     ░░░░░░  │
│  ░░░░░░░░░  │  [         Weiter              ]  │     ░░░░░░  │
│  ░░░░░░░░░  │                                  │     ░░░░░░  │
│  ░░░░░░░░░  └──────────────────────────────────┘     ░░░░░░  │
│  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  │
└───────────────────────────────────────────────────────────────┘
```

---

## 5. Einladungslink-Landing

### 5a. Nicht eingeloggt

```
┌───────────────────────────────────────────────────────────────┐
│  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  │
│  ░░░░░░░░░    [Probenplaner Logo]                    ░░░░░░  │
│  ░░░░░░░░░                                           ░░░░░░  │
│  ░░░░░░░░░  ┌──────────────────────────────────┐     ░░░░░░  │
│  ░░░░░░░░░  │                                  │     ░░░░░░  │
│  ░░░░░░░░░  │  Du wurdest eingeladen zum        │     ░░░░░░  │
│  ░░░░░░░░░  │                                  │     ░░░░░░  │
│  ░░░░░░░░░  │  ┌────────────────────────────┐  │     ░░░░░░  │
│  ░░░░░░░░░  │  │  🎵  JSO Bremen            │  │     ░░░░░░  │
│  ░░░░░░░░░  │  │  Jeunesses Musicales       │  │     ░░░░░░  │
│  ░░░░░░░░░  │  │  Deutschland               │  │     ░░░░░░  │
│  ░░░░░░░░░  │  └────────────────────────────┘  │     ░░░░░░  │
│  ░░░░░░░░░  │                                  │     ░░░░░░  │
│  ░░░░░░░░░  │  [  Mit JMD-Account anmelden   ] │     ░░░░░░  │
│  ░░░░░░░░░  │                                  │     ░░░░░░  │
│  ░░░░░░░░░  │  ─── oder ───                    │     ░░░░░░  │
│  ░░░░░░░░░  │                                  │     ░░░░░░  │
│  ░░░░░░░░░  │  [  Anmelden / Registrieren    ] │     ░░░░░░  │
│  ░░░░░░░░░  │                                  │     ░░░░░░  │
│  ░░░░░░░░░  └──────────────────────────────────┘     ░░░░░░  │
│  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  │
└───────────────────────────────────────────────────────────────┘
```

> Wenn `keycloak_only` aktiv: "oder" + "Anmelden / Registrieren" ausgeblendet.
> Nach Login → weiter zu 5b.

### 5b. Eingeloggt – Register wählen

```
┌───────────────────────────────────────────────────────────────┐
│  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  │
│  ░░░░░░░░░    [Probenplaner Logo]                    ░░░░░░  │
│  ░░░░░░░░░                                           ░░░░░░  │
│  ░░░░░░░░░  ┌──────────────────────────────────┐     ░░░░░░  │
│  ░░░░░░░░░  │                                  │     ░░░░░░  │
│  ░░░░░░░░░  │  🎵 JSO Bremen beitreten          │     ░░░░░░  │
│  ░░░░░░░░░  │                                  │     ░░░░░░  │
│  ░░░░░░░░░  │  Was spielst du im               │     ░░░░░░  │
│  ░░░░░░░░░  │  JSO Bremen?                     │     ░░░░░░  │
│  ░░░░░░░░░  │                                  │     ░░░░░░  │
│  ░░░░░░░░░  │  ▼ Streicher                      │     ░░░░░░  │
│  ░░░░░░░░░  │     ○ Violine 1                   │     ░░░░░░  │
│  ░░░░░░░░░  │     ○ Violine 2                   │     ░░░░░░  │
│  ░░░░░░░░░  │     ○ Bratsche                    │     ░░░░░░  │
│  ░░░░░░░░░  │     ○ Cello                       │     ░░░░░░  │
│  ░░░░░░░░░  │     ○ Kontrabass                  │     ░░░░░░  │
│  ░░░░░░░░░  │  ▶ Bläser                         │     ░░░░░░  │
│  ░░░░░░░░░  │  ▶ Schlagwerk                     │     ░░░░░░  │
│  ░░░░░░░░░  │                                  │     ░░░░░░  │
│  ░░░░░░░░░  │  [        Beitreten             ] │     ░░░░░░  │
│  ░░░░░░░░░  │                                  │     ░░░░░░  │
│  ░░░░░░░░░  └──────────────────────────────────┘     ░░░░░░  │
│  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  │
└───────────────────────────────────────────────────────────────┘
```

---

## 6. Mitglieder (neue Seite, Ensemble-Kontext)

### 6a. Mitglieder-Liste

```
┌───────────────────────────────────────────────────────────────┐
│ ☰  Mitglieder                                           [?]  │
├──────────────┬────────────────────────────────────────────────┤
│              │                                                │
│  [V]         │  👥 Mitglieder                [🔗 Einladen]   │
│  Vera Schmidt│                                                │
│  JSO Bremen  │  🔍 [________________________]                │
│  Violine 1   │                                                │
│              │  ┌─ Streicher ──────────────────────────────┐  │
│ ──────────── │  │                                          │  │
│              │  │  Violine 1 (8)                           │  │
│  📋 Meine    │  │  ┌──────────────────────────────────┐    │  │
│  Meldungen   │  │  │ [V] Vera Schmidt   ● Leitung ⚙️ │    │  │
│  📊 Rückmel- │  │  │ [B] Ben Weber  ● Reg.leitung ⚙️ │    │  │
│  dungen      │  │  │ [A] Anna Müller              ⚙️ │    │  │
│  📅 Termine  │  │  │ [C] Clara Schulz             ⚙️ │    │  │
│  📝 Proben-  │  │  └──────────────────────────────────┘    │  │
│  plan        │  │                                          │  │
│  👥 Mit-     │  │  Violine 2 (6)                           │  │
│  glieder ●   │  │  ┌──────────────────────────────────┐    │  │
│  ⚙️ Ensemble │  │  │ [E] Eva Braun                ⚙️ │    │  │
│              │  │  │ [F] Felix König              ⚙️ │    │  │
│              │  │  └──────────────────────────────────┘    │  │
│              │  └──────────────────────────────────────────┘  │
│              │                                                │
│              │  ┌─ Bläser ─────────────────────────────────┐  │
│              │  │  ...                                      │  │
│              │  └──────────────────────────────────────────┘  │
│  [📲 App]    │                                                │
│  🔄 Wechseln │                                                │
│  👤 Profil   │                                                │
│  🚪 Logout   │                                                │
│              │                                                │
├──────────────┤                                                │
│ Probenplaner │                                                │
│ · v2.0       │                                                │
└──────────────┴────────────────────────────────────────────────┘
```

> `view_members` = Liste ohne ⚙️ und ohne [🔗 Einladen].
> `manage_members` = ⚙️ und [🔗 Einladen] sichtbar.

### 6b. Mitglied bearbeiten (⚙️ → Modal)

```
         ┌──────────────────────────────────────────────────┐
         │  Ben Weber                                 [✕]   │
         │  ben.weber@email.de                               │
         │                                                    │
         │  Register:                                         │
         │  ┌──────────────────────────────────────────┐     │
         │  │ Violine 1                            [▼] │     │
         │  └──────────────────────────────────────────┘     │
         │                                                    │
         │  ☐ Kleingruppe                                     │
         │                                                    │
         │  ─── Berechtigungen ───                            │
         │                                                    │
         │  Vorlage: [Registerleitung ▼]                      │
         │                                                    │
         │  ☑ Eigenes Register sehen                          │
         │  ☑ Alle Register-Statistiken sehen                 │
         │  ☐ Mitgliederliste sehen                           │
         │  ☐ Proben verwalten                                │
         │  ☐ Mitglieder verwalten                            │
         │  ☐ Berechtigungen vergeben                         │
         │                                                    │
         │  ─── Gefährliche Aktionen ───                      │
         │  [  Aus Ensemble entfernen  ]                      │
         │                                                    │
         │  [          Speichern              ]               │
         └────────────────────────────────────────────────────┘
```

### 6c. Einladungslink-Verwaltung (🔗 → Modal)

```
         ┌──────────────────────────────────────────────────┐
         │  🔗 Einladungslink                         [✕]   │
         │                                                    │
         │  Teile diesen Link mit neuen Mitgliedern.          │
         │                                                    │
         │  ┌──────────────────────────────────────────┐     │
         │  │ {APP_URL}/invite/xK9m2p           [📋]  │     │
         │  └──────────────────────────────────────────┘     │
         │                                                    │
         │  Bisher genutzt: 3×                                │
         │                                                    │
         │  ☑ Nur JMD-Accounts erlauben                       │
         │                                                    │
         │  [  🔄 Neuen Link generieren  ]                   │
         │  Alter Link wird sofort ungültig.                  │
         │                                                    │
         └────────────────────────────────────────────────────┘
```

> Keycloak-Toggle wirkt sofort auf alle Neuregistrierungen, ohne Link neu zu generieren.

---

## 7. Profil (verändert)

Vereint Member + Conductor Profil. Klarer Split: global vs. pro Ensemble.

```
┌───────────────────────────────────────────────────────────────┐
│ ☰  Profil                                               [?]  │
├──────────────┬────────────────────────────────────────────────┤
│              │                                                │
│  [V]         │  👤 Profil                                     │
│  Vera Schmidt│                                                │
│  JSO Bremen  │  ┌─ Mein Account ──────────────────────────┐  │
│  Violine 1   │  │                                          │  │
│              │  │       [V]                                 │  │
│ ──────────── │  │                                          │  │
│              │  │  Anzeigename: [Vera Schmidt]              │  │
│  📋 Meine    │  │  Email:       vera@email.de               │  │
│  Meldungen   │  │               (JoinJMD verknüpft ✓)       │  │
│  📊 Rückmel- │  │                                          │  │
│  dungen      │  │  [Speichern]                              │  │
│  📅 Termine  │  └──────────────────────────────────────────┘  │
│  📝 Proben-  │                                                │
│  plan        │  ┌─ In diesem Ensemble ── JSO Bremen ──────┐  │
│  👥 Mit-     │  │                                          │  │
│  glieder     │  │  Register: [Violine 1 ▼]                 │  │
│  ⚙️ Ensemble │  │  ☐ Kleingruppe                            │  │
│              │  │                                          │  │
│              │  │  [Speichern]  [Ensemble verlassen]        │  │
│              │  └──────────────────────────────────────────┘  │
│              │                                                │
│              │  ┌─ Design ────────────────────────────────┐  │
│              │  │  Theme: [Jeunesse ▼]                     │  │
│              │  └──────────────────────────────────────────┘  │
│              │                                                │
│              │  ┌─ Sicherheit ─────────────────────────────┐  │
│  [📲 App]    │  │  [ Passwort ändern ]  [ Konto löschen ]  │  │
│  🔄 Wechseln │  └──────────────────────────────────────────┘  │
│  👤 Profil ● │                                                │
│  🚪 Logout   │                                                │
│              │                                                │
├──────────────┤                                                │
│ Probenplaner │                                                │
│ · v2.0       │                                                │
└──────────────┴────────────────────────────────────────────────┘
```

> Email: Nur editierbar wenn NICHT mit JoinJMD verknüpft. Sonst read-only + Status-Badge.
> "Passwort ändern": Nur für lokale Accounts sichtbar.

---

## Sidebar: Permission-basierte Menüstruktur

```
┌──────────────────────┐
│  [V]  Vera Schmidt   │  ← Avatar + Display-Name
│  JSO Bremen          │  ← aktuelles Ensemble
│  Violine 1           │  ← Register
├──────────────────────┤
│  ┌─ Statistik-Bar  ─┤  ← wie bisher
│  │ ██████░░░░ █ █ █  │
│  └───────────────────┤
├──────────────────────┤
│  📋 Meine Meldungen  │  ← immer
│  📊 Rückmeldungen    │  ← view_all_section_stats
│  📅 Termine          │  ← manage_rehearsals
│  📝 Probenplan       │  ← immer
│  👥 Mitglieder       │  ← view_members
│  ⚙️ Ensemble         │  ← manage_ensemble
│                      │
│  ╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌ │  ← bottom-aligned ab hier:
│  [📲 App installieren]│  ← PWA card (wenn nicht installiert)
│  🔄 Ensemble wechseln│  ← wenn >1 Ensemble
│  👤 Profil           │  ← immer
│  🚪 Logout           │  ← immer
├──────────────────────┤
│  Probenplaner · v2.0 │
└──────────────────────┘
```

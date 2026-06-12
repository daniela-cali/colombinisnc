# Changelog — Colombini SNC Gestionale

## [0.2.0] - 2026-06-12

### Autenticazione (Shield)

- [APP] Login con username e password, logout, "Ricordami"
- [APP] Pagina di login con layout dedicato (gradiente blu/teal, card centrata)
- [DEV] Installazione e configurazione CodeIgniter Shield
- [DEV] Migrazioni Shield: tabelle `users`, `auth_identities`, `auth_logins` e correlate
- [DEV] Configurazione login per username (override validazione Shield che usa email di default)
- [DEV] Gruppi utente: `admin`, `staff`, `tecnici`, `clienti`
- [DEV] Filter `session` globale con esclusione rotte auth (protezione tutte le rotte)
- [DEV] Filter `noauth` su `/login` (redirect dashboard se già autenticati)
- [DEV] Seeder `AdminSeeder` per creazione utente admin iniziale
- [DEV] Fix attivazione utente nel seeder (`active = 1`)
- [DEV] Override `Validation::$login` in `app/Config/Validation.php`

## [0.1.0] - 2026-06-10

### Inizializzazione progetto

- [DEV] Setup CodeIgniter 4.7.3
- [DEV] Integrazione AdminLTE 4.0.2 con Bootstrap 5.3.8
- [DEV] Pipeline asset: npm + comando Spark `assets:publish`
- [DEV] Layout admin con sidebar, navbar, dark mode, flashdata
- [DEV] Localizzazione italiana completa
- [DEV] Dashboard base

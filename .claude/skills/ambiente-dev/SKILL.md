---
name: ambiente-dev
description: Note tecniche e troubleshooting dell'ambiente di sviluppo Colombini SNC sul server (utente colombini-dev, sito colombini-dev.metesoftware.it). Da consultare per sapere cosa si può fare da qui e cosa no (sudo, deploy, log di Nginx, riavvio di PHP), dove leggere i log del gestionale, come interrogare il database di sviluppo, per provare l'app dal telefono, quando il sito chiede una password prima del login, quando una modifica non sembra arrivare al browser, quando serve npm, quando `dd()` sembra non produrre output, quando il diff delle modifiche non appare più nell'IDE VS Code, o davanti a una LogicException di Shield al login.
---

# Ambiente di sviluppo — note e troubleshooting

Note pratiche sull'ambiente di sviluppo e rimedi a problemi già incontrati. Il perché
dell'assetto (utente dedicato, pool PHP proprio, database separato) è in
`docs/spec/ambiente_dev_server_spec.md`.

## Com'è fatto

| | |
|---|---|
| Codice | `/var/www/colombini-dev`, di `colombini-dev` (gruppo `www-data`, `750`) |
| Sito | `https://colombini-dev.metesoftware.it` |
| PHP | pool PHP-FPM `colombini-dev` (`ondemand`), gira **come `colombini-dev`** |
| Database | `colombinisnc_dev` su MariaDB 10.11, utente `colombini_dev` |
| Accesso | VS Code Remote-SSH, chiavi `mio-pc` (casa) e `pc-ufficio` |

Il sito e il terminale sono **lo stesso utente**: i file che il sito crea in `writable/`
(sessioni, cache, log) sono modificabili da `php spark`, e viceversa. Non serve `sudo -u`
davanti ai comandi, a differenza della produzione (vedi `docs/deploy.md`).

## Cosa non si può fare da qui

`colombini-dev` non ha `sudo`, per scelta. Quindi:

- **niente deploy**: la produzione (`/var/www/colombini`) si aggiorna da `nhildra` seguendo
  `docs/deploy.md`. Da qui non si legge nemmeno il suo `.env`;
- **niente log di Nginx** (gruppo `adm`): per un `401`/`403`/`502` va chiesto all'utente, che
  li legge come `nhildra` con `sudo`;
- **niente riavvio di PHP-FPM o Nginx**. Di norma non serve: OPcache ricontrolla i file
  modificati (default di Debian), quindi una modifica PHP arriva al browser al massimo dopo
  un paio di secondi. Se un cambio a `.env` o a `app/Config` sembra ignorato, ricaricare la
  pagina dopo qualche secondo prima di cercare altre cause;
- **niente `npm`**: Node non è installato (Fase 8 della spec, facoltativa). Gli asset sono
  committati in `public/assets/vendor/`, quindi serve solo per aggiornare i pacchetti
  frontend. Quando servirà, si installa per il solo `colombini-dev` con nvm, senza `sudo`.

## Log e database

- I log del gestionale sono in `writable/logs/log-AAAA-MM-GG.log` e si leggono direttamente.
  I log dei comandi batch sono in `writable/custom_log/<contesto>/`.
- `mariadb` senza argomenti apre già `colombinisnc_dev`: utente, password e database stanno
  in `~/.my.cnf`. Le **letture** si fanno liberamente. Le **scritture** (`migrate`, `db:seed`,
  `UPDATE` a mano) le lancia l'utente, vedi `CLAUDE.md` → "Modo di lavorare".
- Non stampare mai credenziali: niente `SHOW GRANTS`, niente `cat` del `.env` o di
  `~/.my.cnf`. Per un singolo valore non segreto, `grep` sulla chiave precisa.
- `php spark migrate:status` **non è di sola lettura**: su un database vuoto crea la tabella
  `migrations`.
- Il database contiene `clienti_adhoc`, cioè l'anagrafica reale importata da Ad Hoc: non è
  tutto sacrificabile.

## Due password prima di entrare

Il sito è dietro `auth_basic` di Nginx: prima la password di Nginx (utente `daniela`, nel
gestore di password), poi il login del gestionale. Un `401` senza pagina del gestionale è la
prima delle due, non Shield. La stessa password Nginx protegge
`colombini-old.metesoftware.it`.

## Provare dal telefono

Basta aprire `https://colombini-dev.metesoftware.it`, da qualunque rete. Essendo in HTTPS,
anche Chrome su Android accetta il manifest PWA, quindi l'installazione come app si prova
qui. Su `http` non funzionava: era uno dei tre limiti noti della v0.35.0.

## Il vecchio progetto

`/var/www/colombini-old` è leggibile come riferimento: anche quello è CodeIgniter 4. Non va
modificato. Vedi `CLAUDE.md` → "Vecchio progetto".

## `dd()` funziona regolarmente

CodeIgniter 4 include una copia di Kint dentro il framework
(`vendor/codeigniter4/framework/system/ThirdParty/Kint/`), attiva da sola quando `CI_DEBUG` è
`true` (ambiente `development`): non serve installare `kint-php/kint`. Se in una sessione di
debug sembra non fare nulla, sospettare prima il contesto: un `ob_start()` esterno che
inghiotte l'output, o un dump che compare in un punto della pagina dove non lo si guarda.

## Hot-reload della Debug Toolbar disattivato

`app/Config/Events.php` non registra la rotta `__hot-reload` dello scaffolding di CI4. Quella
rotta apre una connessione SSE che il browser tiene aperta finché la tab resta aperta. Col
vecchio `php -S` locale, che serve una richiesta alla volta, bastava una tab per piantare
tutto il server.

Con PHP-FPM non blocca più tutto, ma ogni tab aperta occupa uno dei 5 processi del pool
(`pm.max_children`): con qualche tab dimenticata il sito di sviluppo smette di rispondere
allo stesso modo. Se si vuole riattivarla, alzare prima `pm.max_children`. Il pool lo
modifica `nhildra`.

## Doppio login involontario → `LogicException` di Shield

Se una tab col form di login resta aperta mentre la sessione è già autenticata (tasto
Indietro, tab dimenticata, doppio submit), il POST arrivava a
`LoginController::loginAction()` e Shield lanciava `LogicException: The user has User Info in
Session...`. Il filtro `App\Filters\NoAuth` copriva solo `GET login`. Fix: `Routes.php`
sovrascrive anche `POST login` con lo stesso filtro `noauth`, prima che
`service('auth')->routes($routes)` registri le rotte di default di Shield.

## Ripristino diff VS Code (Claude Code)

Se il diff delle modifiche smette di apparire nell'IDE:

1. Verificare che `~/.claude/settings.json` abbia `"defaultMode": "default"` e non
   `"acceptEdits"`. Con Remote-SSH è il file **sul server**, nella home di `colombini-dev`.
2. `Ctrl+Shift+P` → **Developer: Reload Window**.
3. Se non basta, aprire una nuova sessione di Claude Code.

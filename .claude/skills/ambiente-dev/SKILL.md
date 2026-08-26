---
name: ambiente-dev
description: Note tecniche e troubleshooting dell'ambiente di sviluppo Colombini SNC. Da consultare quando il server di sviluppo si pianta o non risponde, quando il browser mostra "Not Found" in pagina bianca senza grafica, per avviare il server e accedere all'app dallo smartphone in LAN, per fermare il server quando il Ctrl+C non è disponibile (trovare il processo dalla porta e terminarlo per PID), quando `dd()` sembra non produrre output, quando il diff delle modifiche non appare più nell'IDE VSCode, o davanti a una LogicException di Shield al login.
---

# Ambiente di sviluppo — note e troubleshooting

Note tecniche sull'ambiente di sviluppo e soluzioni a problemi ricorrenti. Non sono regole di progetto: sono sintomi già incontrati con la loro causa e il loro rimedio.

## Ripristino diff VSCode (Claude Code)

Se il diff delle modifiche smette di apparire nell'IDE VSCode:

1. Verificare che `~/.claude/settings.json` abbia `"defaultMode": "default"` (non `"acceptEdits"`)
2. Eseguire `Ctrl+Shift+P` → **Developer: Reload Window** per ricaricare l'estensione
3. Se non basta, aprire una nuova sessione di Claude Code

## `dd()` funziona regolarmente

CodeIgniter 4 include una copia vendorizzata di Kint dentro il framework (`vendor/codeigniter4/framework/system/ThirdParty/Kint/`), attivata automaticamente quando `CI_DEBUG` è `true` (ambiente `development`) — non serve installare `kint-php/kint` separatamente. Verificato con `dd()` diretto: dump completo e corretto. Se in una sessione di debug sembra non fare nulla, sospettare prima il contesto (output engoiato da un ob_start() esterno, contenuto che appare ma passa inosservato più in alto/basso nella pagina) prima di concludere che Kint sia assente.

## Accesso da smartphone in LAN (dev server)

Per testare l'app dal telefono sulla stessa rete Wi-Fi del PC di sviluppo:

1. `cd d:\Programmazione\Progetti\colombinisnc`
2. `php -S 0.0.0.0:8081 -t public` (in ascolto su tutte le interfacce, non solo `localhost`)
3. Dal telefono: `http://<IP-LAN-PC>:8081`

`app.baseURL` in `.env` è attualmente impostato su `http://192.168.1.133:8081` (IP LAN del PC di sviluppo, non `localhost:8081`) — necessario perché `base_url()` genera i link assoluti in base a quel valore fisso, non in base all'host da cui arriva la richiesta. Funziona identico anche da desktop (basta aprire lo stesso IP invece di `localhost`), quindi si può lasciare così come configurazione permanente di sviluppo.

Se l'IP del PC cambia (riconnessione Wi-Fi, rinnovo DHCP): controllare il nuovo indirizzo con `ipconfig` (voce "Indirizzo IPv4" della scheda Wi-Fi) e aggiornare `app.baseURL` di conseguenza. Per fermare il server: `Ctrl+C` nel terminale in cui gira.

## Fermare il server quando il `Ctrl+C` non è disponibile

(server avviato in background, o terminale chiuso): si trova il processo dalla porta e lo si termina per PID.

```powershell
netstat -ano | findstr 8081        # ultima colonna della riga LISTENING = PID
Stop-Process -Id <PID> -Force
```

Prima di terminarlo conviene verificare di aver preso il processo giusto — e con quali argomenti era partito:

```powershell
Get-CimInstance Win32_Process -Filter "ProcessId = <PID>" | Select-Object CommandLine
```

Non usare `Stop-Process -Name php -Force`: chiude **tutti** i processi PHP, compresi eventuali `spark` o script in esecuzione.

## `Not Found` senza grafica → manca `-t public`

Se il browser mostra `Not Found — The requested resource / was not found on this server` in pagina bianca, è il 404 **del server built-in di PHP**, non quello di CodeIgniter: l'app non è mai stata eseguita. La causa è quasi sempre `php -S 0.0.0.0:8081` avviato senza `-t public`, con la document root che finisce sulla cartella corrente invece che su `public/`: PHP cerca un `index.php` nella root del progetto, non lo trova e risponde da sé.

Il sintomo somiglia a un problema di rotte, ma si distingue subito da due segni: la pagina non ha nessuno stile dell'applicazione, e nel log del server non compare nessuna riga di CodeIgniter. Per confermarlo basta il `Get-CimInstance` qui sopra, che mostra gli argomenti con cui il processo è partito.

## Hot-reload della Debug Toolbar disattivato (blocca `php -S`)

`app/Config/Events.php` non registra più la rotta `__hot-reload` (era codice di scaffolding standard di CodeIgniter 4). Quella rotta apre una connessione SSE che il browser tiene aperta indefinitamente finché la tab resta aperta. Siccome `php -S` gestisce **una richiesta alla volta per l'intero processo** (non per tab/sessione), anche una sola di quelle connessioni rimaste appese blocca tutto il server — qualunque altra richiesta, da qualunque tab o browser, resta in coda senza mai essere servita, senza nessun errore nei log (non è un'eccezione, è solo una connessione che non finisce mai). Sintomo tipico: il sito "si pianta" dal nulla, non riparte nemmeno in incognito, ma il processo PHP risulta ancora vivo. Se in futuro serve riattivarla, va usato un server che gestisca richieste concorrenti (es. `php spark serve` con più worker, o Apache/nginx+PHP-FPM) — mai con `php -S`.

## Doppio login involontario → `LogicException` di Shield

Se una tab con il form di login resta aperta mentre la sessione nel frattempo è già autenticata (tasto Indietro, tab dimenticata, doppio submit), il POST arrivava direttamente a `LoginController::loginAction()` e Shield lanciava `LogicException: The user has User Info in Session...`. Il filtro `App\Filters\NoAuth` (redirect alla dashboard se già loggato) era applicato solo alla rotta `GET login` in `Routes.php`, non al `POST login` registrato da Shield in `AuthRoutes.php`. Fix: `Routes.php` ora sovrascrive **anche** il `POST login` con lo stesso filtro `noauth`, prima che `service('auth')->routes($routes)` (fine file) registri le rotte di default di Shield.

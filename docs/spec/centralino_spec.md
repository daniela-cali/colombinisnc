# Analisi Tecnica ed Economica: Centralino Voxloud & Integrazione Web App (CI4)

Questo documento riassume la configurazione commerciale, il funzionamento logico e le specifiche di sviluppo per l'integrazione del centralino cloud Voxloud con la web app aziendale basata su **CodeIgniter 4** e la libreria **Shield**.

---

## 1. Stima Economica e Configurazione Commerciale

La configurazione prevede l'attivazione di un centralino in cloud per **5 interni**, l'utilizzo di un **singolo numero fisso aziendale** e la gestione delle deviazioni di chiamata su un dispositivo mobile.

### Prospetto dei Costi Mensili Indicativi
* **Canone Utente/Interno:** ~31,00 € / mese per utente (× 5 utenti) = **~155,00 € / mese**
* **Numero di telefono fisso aziendale:** 1 numero incluso nel canone = **0,00 €**
* **Noleggio Telefoni Fisici VoIP:** Incluso in promo o ~1,00 - 3,00 € cad. = **Variabile / Incluso**
* **Deviazione di chiamata al cellulare:** Gestita nativamente via Cloud/App = **0,00 €**
* **Costo Mensile Totale Stimato:** **~161,00 € / mese** (IVA esclusa, al netto di promozioni)

### Apparati da Richiedere a Voxloud
* **Per le 3 postazioni con cuffia:** Non sono necessari telefoni particolari. L'app desktop Voxloud gestirà le chiamate sul PC.
* **Per le 2 postazioni standard:** Richiedere telefoni fissi VoIP professionali a noleggio a lungo termine (es. **Yealink T31P** o **Yealink T43U** con tasti BLF per vedere lo stato libero/occupato dei colleghi) con garanzia Kasko inclusa.

---

## 2. Funzionamento della Linea e Gestione Chiamate

Il centralino opera interamente in cloud, eliminando i vecchi vincoli della linea telefonica fisica tradizionale.

* **Linee Contemporanee:** Il numero fisso aziendale accetta più chiamate simultanee. Se l'Operatore A risponde al PC, la linea si "libera" istantaneamente per l'apparecchio fisico. Una seconda chiamata in ingresso farà squillare gli altri interni liberi senza dare mai il segnale di occupato al cliente.
* **Deviazione su "Non Risposto":** Tramite il pannello Voxloud si imposta la regola: *"Se i 5 interni fissi/PC non rispondono entro X secondi, trasferisci la chiamata sul cellulare"*.
* **Integrazione Mobile:** Installando l'applicazione Voxloud sullo smartphone del dipendente, questo diventa un interno a tutti gli effetti. Il trasferimento avviene via rete dati (VoIP) senza tariffazione per la deviazione di chiamata.

---

## 3. Scelta Hardware: Cuffie Professionali

Per le 3 postazioni davanti al computer, la soluzione ottimale ed economica prevede l'utilizzo di **cuffie con cavo USB collegate direttamente al PC**, abbinate all'applicazione desktop Voxloud Phone. Questo evita l'acquisto di costosi switch meccanici o cuffie wireless multipoint.

### Modelli USB Consigliati (Budget per 3 unità)
1. **Yealink UH34 Dual** (Scelta Economica): ~35,00 € cad. → **Totale: ~105,00 €**
2. **Jabra Evolve2 30 SE** (Scelta Professionale): ~50,00 € cad. → **Totale: ~150,00 €**

---

## 4. Architettura Software e Sviluppo (CodeIgniter 4 + Shield)

Per far comunicare Voxloud e la web app ospitata su server **IONOS Linux**, l'approccio *Client-Side* via `GET` è il più efficiente. Evita configurazioni complesse sul server (come WebSockets) e non richiede l'uso di IP locali statici.

### Standard Database: E.164
Tutti i numeri nel database verranno salvati in formato internazionale standard **E.164** (es. `+39021234567` per l'Italia, `+33612345678` per la Francia). 
I dati storici sporchi derivanti dall'importazione contabile verranno rimossi, procedendo a un inserimento pulito a mano o tramite script batch.

### Rotta in `app/Config/Routes.php`
La rotta è protetta dal filtro di sessione di Shield per garantire la sicurezza dei dati dei clienti:
```php
$routes->get('clienti/find-by-telefono', 'ClientiController::findByTelefono', ['filter' => 'session']);
```

### Logica del Controller (`ClientiController.php`)
Il metodo intercetta la richiesta `GET` inviata dall'app locale di Voxloud ed esegue il redirect alla scheda cliente:

```php
public function findByTelefono()
{
    // Recupera il numero inviato in formato E.164 dall'app Voxloud (es. +393331234567)
    $telefonoRaw = $this->request->getGet('telefono');

    if (empty($telefonoRaw)) {
        return redirect()->to('/clienti')->with('error', 'Nessun numero fornito.');
    }

    // Uniforma eventuali variazioni sintattiche (es. 0039 in +39)
    $telefonoRaw = trim($telefonoRaw);
    if (strpos($telefonoRaw, '00') === 0) {
        $telefonoRaw = '+' . substr($telefonoRaw, 2);
    }

    $clientiModel = new \App\Models\ClientiModel();
    
    // Query diretta sul DB normalizzato E.164
    $cliente = $clientiModel->where('telefono', $telefonoRaw)
                            ->orWhere('cellulare', $telefonoRaw)
                            ->first();

    if ($cliente) {
        // Cliente trovato: mostra la scheda di dettaglio
        return redirect()->to('/clienti/show/' . $cliente->id);
    }

    // Cliente NON trovato: rimanda al form di creazione precompilando il numero
    return redirect()->to('/clienti/create?telefono=' . urlencode($telefonoRaw))
                     ->with('info', 'Nuovo numero rilevato. Crea l\'anagrafica.');
}
```

### Configurazione App Desktop Voxloud (Pop-up automatico)
Nelle impostazioni avanzate dell'applicazione Voxloud sul PC di ciascun operatore, abilitare l'apertura dell'URL esterno su chiamata in entrata valorizzando la stringa:
```text
https://tua-webapp-ionos.it%
```
*Se l'utente non è loggato nel browser, Shield intercetta la richiesta, mostra la pagina di login e, dopo l'autenticazione, reindirizza l'utente alla scheda del cliente senza perdere il parametro.*
---
name: session-handoff
description: Da attivare quando l'utente dice "handoff", "chiudi la sessione", "tira giù la sessione", "passaggio di consegne", "riassumi prima del clear", "chiudiamo qui", "chiudiamo la sessione", o vuole un riepilogo strutturato di fine sessione prima di fare /clear del contesto. Produce un handoff solo in chat che copre decisioni, modifiche fatte, file chiave, processi in esecuzione, comandi di verifica, cose rimandate e domande aperte — così un agente nuovo può continuare il lavoro senza perdere il filo.
---

# Session Handoff

Produce un riepilogo di fine sessione ripetibile, così l'utente può fare `/clear` e ripartire con un agente nuovo senza perdere continuità. Il prossimo agente deve riuscire a riprendere il lavoro leggendo SOLO questo riepilogo.

Questo è un **artefatto di handoff di contesto**, non un report di status. Il pubblico è una futura istanza di te stesso, non uno stakeholder.

## Quando attivarsi

L'utente dice: "handoff", "chiudi la sessione", "tira giù la sessione", "passaggio di consegne", "riassumi prima del clear", "chiudiamo qui", "chiudiamo la sessione", o equivalenti vicini. Attivati anche proattivamente se l'utente dice che sta per fare `/clear` senza averlo ancora eseguito.

## Come produrre il riepilogo

1. **Rivedi tutta la conversazione**, non solo gli ultimi turni. Gli handoff perdono pezzi quando riassumono solo il contesto recente.

2. **Estrai lo stato da queste fonti (in ordine):**
   - File di piano referenziati in questa sessione (controlla `~/.claude/plans/` se è stato menzionato un piano).
   - Stato di TodoWrite — task in_progress o pending.
   - Processi in background avviati con `run_in_background` — gli shell ID sono critici per il prossimo agente.
   - File creati o modificati in questa sessione — sai cosa hai toccato; non fare grep per riscoprirlo.
   - File di memory scritti o aggiornati (`~/.claude/projects/<nome-progetto>/memory/`).
   - Domande non risolte — cose che hai chiesto all'utente e che non hanno avuto risposta chiara, o cose che l'utente ha chiesto a te e che sono state evitate.

3. **NON fare audit del filesystem.** Questa è una sintesi di cosa è successo in QUESTA sessione. Niente `git log`, niente `Glob` esplorativi a tappeto. Se non l'hai toccato in questa sessione, non va qui.

4. **Produci l'output in chat.** Non scrivere file. Non aggiornare memory. Solo chat.

## Template di output — usa esattamente questa struttura, ogni volta

```
# Handoff Sessione — <titolo in una riga di cosa è stata questa sessione>

## Dove eravamo
<2-3 frasi: cosa ha chiesto l'utente, framing chiave o vincoli emersi>

## Decisioni prese + cosa è stato fatto
- <decisione o cambio> — <perché, e dove vive (path assoluto se è un file)>
- ...

## File chiave per la prossima sessione
- `<path assoluto>` — <perché la prossima istanza dovrebbe leggerlo per primo>
- File piano: `<path>` (se un piano ha guidato la sessione)
- File di memory toccati: `<paths>` (se ce ne sono)

## Stato in esecuzione
- Processi in background: <shell ID + cosa sono + come killarli> — oppure "nessuno"
- Dev server / porte: <url + porta> — oppure "nessuno"
- Worktree / branch aperti: <paths> — oppure "nessuno"

## Verifica — come confermare che tutto funziona ancora
- `<comando>` — <risultato atteso>
- ...

## Rimandato + domande aperte
- Rimandato: <item> — <perché pushato dopo>
- Aperto: <domanda che richiede input dell'utente> — <contesto>

## Riparti da qui
<1-2 frasi: la singola prossima azione più probabile per una nuova istanza>
```

## Regole rigide

1. **Output solo in chat.** Mai scrivere l'handoff su file. Mai aggiornare memory da questa skill.
2. **Mai inventare stato.** Se una sezione non ha nulla da riportare, scrivi "nessuno" — non omettere la sezione. La stabilità della struttura è il punto centrale.
3. **Sempre path assoluti.** Il prossimo agente potrebbe avere una working directory diversa.
4. **Se un file di piano ha guidato la sessione, nominalo per primo** in "File chiave per la prossima sessione" così il prossimo agente lo legge prima di tutto.
5. **Niente emoji, niente hype, niente "ottimo lavoro!".** Terso e concreto — path, comandi, shell ID, decisioni. Tono di un engineer esperto che passa il turno a fine giornata.
6. **Gli ID dei processi in background sono critici.** Se hai avviato shell con `run_in_background`, i loro ID devono comparire in "Stato in esecuzione" con il comando per killarli — il prossimo agente non può trovarli altrimenti.

## Anti-pattern — non fare queste cose

- Riassumere gli ultimi 3 turni e chiamarlo "handoff".
- Elencare i file con path relativi.
- Saltare la sezione "Stato in esecuzione" perché "non c'è niente in esecuzione" — scrivi "nessuno" invece.
- Scrivere il riepilogo su `~/.claude/handoffs/` o qualsiasi altro file. Questo è solo-chat by design.
- Aggiungere una retrospettiva "cosa è andato bene / cosa è andato male". Questa non è una retro.
- Raccomandare next step oltre la singola riga "Riparti da qui". Il prossimo agente decide; tu fai solo il passaggio di consegne.

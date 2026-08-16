<?php

namespace App\Models;

use CodeIgniter\Model;

class ClientiAdhocModel extends Model
{
    protected $table         = 'clienti_adhoc';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'codice', 'tipo', 'ragsoc', 'cognome', 'nome',
        'piva', 'cfisc',
        'indirizzo', 'citta', 'cap', 'provincia', 'nazione',
        'telefono', 'email', 'note',
        'importato', 'cliente_id', 'imported_at',
        'created_by', 'updated_by',
    ];

    protected $beforeInsert = ['normalizza'];
    protected $beforeUpdate = ['normalizza'];

    /**
     * Campi anagrafici mappabili da CSV, con l'etichetta mostrata nelle tendine
     * del passo "mappa colonne". Vive qui e non nella libreria di import perché
     * descrive le colonne della tabella: la libreria e la view la leggono da qui.
     */
    public const CAMPI_IMPORTABILI = [
        'codice'    => 'Codice *',
        'tipo'      => 'Tipo (societa / persona_fisica)',
        'ragsoc'    => 'Ragione sociale',
        'cognome'   => 'Cognome',
        'nome'      => 'Nome',
        'piva'      => 'P.IVA',
        'cfisc'     => 'Codice fiscale',
        'indirizzo' => 'Indirizzo',
        'citta'     => 'Città',
        'cap'       => 'CAP',
        'provincia' => 'Provincia',
        'nazione'   => 'Nazione',
        'telefono'  => 'Telefono',
        'email'     => 'Email',
        'note'      => 'Note',
    ];

    /**
     * Sigle nazione usate da Ad Hoc, tradotte nei valori del gestionale
     * (vedi ClientiModel::NAZIONI_PREDEFINITE).
     *
     * Applicata alla promozione e non all'import: il parcheggio conserva il dato
     * grezzo dell'export. Serve perché la tendina Nazione del form confronta valori
     * per intero — con la sigla ricadrebbe sempre su "Altra…".
     */
    public const NAZIONI_ADHOC = [
        'IT' => 'ITALIA',
        'FR' => 'FRANCIA',
    ];

    /**
     * Denominazione calcolata al volo: la tabella di parcheggio non ha la colonna
     * generata `denominazione` di `clienti`, perché conserva dato grezzo senza vincoli.
     * Volutamente NON aliasata `cliente_denominazione`: quella convenzione vale per
     * `clienti.denominazione`, e un record parcheggiato non è ancora un cliente.
     */
    private const SELECT_DENOMINAZIONE =
        "COALESCE(NULLIF(TRIM(ragsoc), ''), NULLIF(TRIM(CONCAT_WS(' ', cognome, nome)), '')) AS denominazione";

    /**
     * Imposta created_by/updated_by leggendo l'utente Shield loggato.
     */
    protected function normalizza(array $data): array
    {
        $userId = user_id();

        // created_by si imposta solo all'inserimento: CI4 passa $data['id'] solo negli update
        if (! isset($data['id'])) {
            $data['data']['created_by'] = $userId;
        }
        $data['data']['updated_by'] = $userId;

        return $data;
    }

    /**
     * Record ancora da promuovere a cliente, per l'elenco in Impostazioni.
     */
    public function daMigrare(): array
    {
        return $this->select('clienti_adhoc.*, ' . self::SELECT_DENOMINAZIONE)
            ->where('importato', 0)
            ->orderBy('denominazione', 'ASC')
            ->findAll();
    }

    /**
     * Un singolo record con la denominazione calcolata, per la precompilazione del form.
     */
    public function trovaConDenominazione(int $id): ?array
    {
        return $this->select('clienti_adhoc.*, ' . self::SELECT_DENOMINAZIONE)
            ->where('clienti_adhoc.id', $id)
            ->first();
    }

    /**
     * Contatori di avanzamento della migrazione per la landing di Import Clienti.
     *
     * @return array{totale:int, importati:int, da_migrare:int}
     */
    public function contatori(): array
    {
        $row = $this->select('COUNT(*) AS totale, SUM(importato = 1) AS importati, SUM(importato = 0) AS da_migrare')
            ->first();

        // MySQL via CI4 restituisce sempre stringhe: il cast esplicito evita
        // confronti e somme sbagliate a valle
        return [
            'totale'     => (int) ($row['totale'] ?? 0),
            'importati'  => (int) ($row['importati'] ?? 0),
            'da_migrare' => (int) ($row['da_migrare'] ?? 0),
        ];
    }

    /**
     * Scrive in blocco le righe estratte dal CSV.
     *
     * Usa `upsertBatch` sul vincolo UNIQUE di `codice`: ri-caricare lo stesso export
     * aggiorna le righe invece di duplicarle (caso previsto, non eccezionale — la query
     * di export verrà affinata più volte). `updateFields` limita la riscrittura ai campi
     * anagrafici, così `created_at` e le colonne di stato (`importato`, `cliente_id`,
     * `imported_at`) sopravvivono: un cliente già promosso resta promosso.
     *
     * `upsertBatch` non esegue i callback `$beforeInsert`/`$beforeUpdate`, quindi
     * created_by/updated_by e i timestamp vanno valorizzati qui a mano.
     *
     * @param list<array<string,mixed>> $righe
     *
     * @return array{scritti:int, inseriti:int, aggiornati:int}
     */
    public function importaBatch(array $righe): array
    {
        if ($righe === []) {
            return ['scritti' => 0, 'inseriti' => 0, 'aggiornati' => 0];
        }

        $userId = user_id();
        $ora    = date('Y-m-d H:i:s');

        foreach ($righe as &$riga) {
            $riga['created_by'] = $userId;
            $riga['updated_by'] = $userId;
            $riga['created_at'] = $ora;
            $riga['updated_at'] = $ora;
        }
        unset($riga);

        // MySQL non sa dire quante righe ha inserito e quante aggiornate in un upsert
        // (affected_rows conta 2 per ogni aggiornamento): il delta sul totale è affidabile.
        $prima = $this->countAllResults();

        $this->builder()
            ->onConstraint('codice')
            ->updateFields(array_merge(
                array_keys(self::CAMPI_IMPORTABILI),
                ['updated_by', 'updated_at']
            ))
            ->upsertBatch($righe, null, 500);

        $inseriti = $this->countAllResults() - $prima;

        return [
            'scritti'    => count($righe),
            'inseriti'   => $inseriti,
            'aggiornati' => count($righe) - $inseriti,
        ];
    }

    /**
     * Marca un record come promosso, collegandolo al cliente appena creato.
     * Il flag resta a 1 anche se il cliente verrà poi eliminato (la FK azzera
     * solo cliente_id): "migrato e poi cancellato" non è "mai valutato".
     */
    public function marcaImportato(int $id, int $clienteId): bool
    {
        return $this->update($id, [
            'importato'   => 1,
            'cliente_id'  => $clienteId,
            'imported_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

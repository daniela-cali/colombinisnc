<?php

namespace App\Libraries;

use App\Models\AbbonamentiModel;
use App\Models\AbbonamentiPeriodiModel;
use App\Models\ClientiModel;
use App\Models\TipiInterventoModel;
use RuntimeException;

/**
 * Proposta di abbonamento in Word: trasforma abbonamento e cliente nei valori del modello e
 * li passa a DocumentoWord. Le decisioni sul contenuto sono in
 * docs/spec/abbonamenti_proposte_word_spec.md.
 */
class PropostaAbbonamento
{
    /**
     * Modello per categoria del tipo di intervento. Una categoria assente non genera proposte:
     * oggi le piscine, che hanno un documento più complesso e avranno un modello loro.
     */
    private const MODELLI = [
        TipiInterventoModel::CATEGORIA_ADDOLCITORI => 'proposta_addolcitori.docx',
    ];

    /** Mesi in italiano per la data del documento, senza dipendere dal locale del server. */
    private const MESI = [
        1 => 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno',
        'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre',
    ];

    /**
     * Dice se per questa categoria esiste un modello di proposta. La scheda abbonamento lo usa
     * per mostrare il bottone, così la regola sta in un punto solo.
     */
    public static function haModello(?string $categoria): bool
    {
        return isset(self::MODELLI[$categoria ?? '']);
    }

    /**
     * Genera la proposta dell'abbonamento, come lo restituisce
     * AbbonamentiModel::trovaConDettagli() (serve tipo_categoria).
     *
     * I messaggi delle eccezioni sono scritti per l'utente: il controller li mostra così come sono.
     *
     * @return array{nome: string, contenuto: string}
     *
     * @throws RuntimeException se la categoria non ha un modello, o mancano prezzo, operazioni
     *                          o apparecchiature
     */
    public function genera(array $abbonamento): array
    {
        $categoria = $abbonamento['tipo_categoria'] ?? null;
        if (! self::haModello($categoria)) {
            throw new RuntimeException('Per il tipo "' . ($abbonamento['tipo_nome'] ?? '—') . '" non esiste ancora un modello di proposta.');
        }
        if ($abbonamento['prezzo'] === null) {
            throw new RuntimeException('Manca il prezzo: senza prezzo la proposta non si può generare.');
        }

        // Il documento rispecchia ciò che è salvato sull'abbonamento: niente ripiego sul testo
        // standard del tipo. Le apparecchiature le impone il form, ma non agli abbonamenti
        // creati prima che il campo esistesse.
        $operazioni      = $this->righe($abbonamento['operazioni_incluse']);
        $apparecchiature = $this->righe($abbonamento['apparecchiature']);
        if ($operazioni === []) {
            throw new RuntimeException('Mancano le operazioni incluse: compilale in Modifica prima di generare la proposta.');
        }
        if ($apparecchiature === []) {
            throw new RuntimeException('Mancano le apparecchiature installate: compilale in Modifica prima di generare la proposta.');
        }

        $cliente = (new ClientiModel())->find((int) $abbonamento['cliente_id']);
        $periodi = (new AbbonamentiPeriodiModel())->perAbbonamento((int) $abbonamento['id']);

        $personaFisica = $cliente['tipo'] === ClientiModel::TIPO_PERSONA_FISICA;

        $contenuto = (new DocumentoWord(APPPATH . 'Templates/word/' . self::MODELLI[$categoria]))
            ->valori([
                'titolo'         => $personaFisica ? 'Gentile Sig./Sig.ra' : 'Spett.le',
                // nel documento il nome precede il cognome, al contrario di clienti.denominazione
                'destinatario'   => $personaFisica ? trim($cliente['nome'] . ' ' . $cliente['cognome']) : (string) $cliente['ragsoc'],
                'indirizzo'      => (string) $cliente['indirizzo'],
                'cap_citta'      => $this->capCitta($cliente),
                'frequenza'      => $this->frequenza($periodi),
                'data_inizio'    => date('d/m/Y', strtotime($abbonamento['data_inizio'])),
                'data_fine'      => date('d/m/Y', strtotime($abbonamento['data_fine'])),
                'prezzo'         => number_format((float) $abbonamento['prezzo'], 2, ',', '.'),
                'pagamento'      => (string) $abbonamento['modalita_pagamento'],
                'data_documento' => date('d') . ' ' . self::MESI[(int) date('n')] . ' ' . date('Y'),
            ])
            ->elenco('riga_telefono', 'telefono', $this->facoltativo($cliente['telefono']))
            ->elenco('riga_email', 'email', $this->facoltativo($cliente['email']))
            ->elenco('apparecchiature', 'apparecchiatura', $apparecchiature)
            ->elenco('operazioni', 'operazione', $operazioni)
            ->contenuto();

        return ['nome' => $this->nomeFile($abbonamento), 'contenuto' => $contenuto];
    }

    /**
     * «17021 ALASSIO (SV)»: le parti mancanti si saltano, così non restano parentesi vuote.
     */
    private function capCitta(array $cliente): string
    {
        $riga = trim($cliente['cap'] . ' ' . $cliente['citta']);

        return $cliente['provincia'] ? $riga . ' (' . $cliente['provincia'] . ')' : $riga;
    }

    /**
     * Le frequenze dei periodi, in maiuscolo come nel modello. Oggi un abbonamento addolcitori ha
     * un periodo solo; se ne avesse più d'uno con frequenze diverse si elencano nell'ordine,
     * senza bloccare la generazione per un caso che non esiste ancora.
     */
    private function frequenza(array $periodi): string
    {
        $etichette = array_map(
            static fn ($p) => mb_strtoupper(AbbonamentiModel::FREQUENZE_LABEL[$p['frequenza']] ?? $p['frequenza'], 'UTF-8'),
            $periodi
        );

        return implode(' / ', array_unique($etichette));
    }

    /**
     * Una voce se il dato c'è, nessuna se manca: con zero voci il blocco del modello sparisce.
     *
     * @return list<string>
     */
    private function facoltativo(?string $valore): array
    {
        $valore = trim((string) $valore);

        return $valore === '' ? [] : [$valore];
    }

    /**
     * Un testo libero diviso in righe, senza quelle vuote. Toglie anche un simbolo d'elenco
     * scritto a mano a inizio riga («-», «°», «•», «*»): il simbolo lo mette il modello, e
     * altrimenti nel documento uscirebbe doppio («- - Controllo…»).
     *
     * @return list<string>
     */
    private function righe(?string $testo): array
    {
        $righe = array_map(
            static fn ($riga) => trim(preg_replace('/^\s*[-–•°*]+\s*/u', '', $riga)),
            preg_split('/\R/u', (string) $testo)
        );

        return array_values(array_filter($righe, static fn ($riga) => $riga !== ''));
    }

    /**
     * «DENOMINAZIONE ANNO TIPO.docx». L'anno è quello d'inizio del servizio, perché le proposte
     * si preparano l'anno prima; il tipo distingue due abbonamenti dello stesso cliente
     * nello stesso anno, che nello zip si sovrascriverebbero. I caratteri vietati nei nomi di
     * file di Windows diventano un trattino.
     */
    private function nomeFile(array $abbonamento): string
    {
        $nome = $abbonamento['cliente_denominazione'] . ' ' . substr($abbonamento['data_inizio'], 0, 4)
            . ' ' . mb_strtoupper((string) $abbonamento['tipo_nome'], 'UTF-8');
        $nome = preg_replace('#[/\\\\:*?"<>|]#', '-', $nome);

        return trim(preg_replace('/\s+/', ' ', $nome)) . '.docx';
    }
}

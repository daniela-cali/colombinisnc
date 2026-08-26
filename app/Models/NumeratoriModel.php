<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Contatori progressivi per i codici di clienti e interventi.
 *
 * Ogni serie è una riga di `settings`: la classe dice a cosa appartiene ('Clienti',
 * 'Interventi'), la chiave è `seq_` più il prefisso ('seq_CLI', 'seq_PIS'), e il valore è
 * l'ultimo numero assegnato. Le righe nascono al primo utilizzo del prefisso, senza bisogno
 * di una migrazione per ogni nuovo tipo intervento.
 *
 * Qui vive tutto ciò che riguarda le sequenze: prima la stessa logica era duplicata in
 * InterventiModel e ClientiModel, che pure risolvevano lo stesso problema in due modi
 * diversi. L'unificazione serve anche alla pagina Impostazioni → Numeratori, che deve leggere
 * gli stessi contatori che il resto dell'applicazione incrementa.
 *
 * Vedi docs/spec/numeratori_atomici_spec.md.
 */
class NumeratoriModel extends Model
{
    protected $table         = 'settings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['class', 'key', 'value', 'type', 'context'];
    protected $useTimestamps = false;

    /** Prefisso delle chiavi in `settings` che identificano una sequenza. */
    public const CHIAVE = 'seq_';

    /** Cifre minime del numero nel codice: sotto si riempie di zeri, sopra il codice cresce. */
    public const CIFRE = 4;

    /**
     * Assegna il numero successivo della serie e restituisce il codice completo.
     *
     * Un contatore dedicato, non MAX(codice) sulla tabella di destinazione. MAX regredisce
     * dopo una cancellazione, riassegnando a un record nuovo il codice di uno eliminato, che
     * nel frattempo può essere finito su un documento; e ordina i codici come stringhe,
     * quindi basta che due abbiano un numero di cifre diverso perché il massimo trovato sia
     * quello sbagliato. AUTO_INCREMENT letto da `information_schema` non serve allo scopo:
     * InnoDB aggiorna quella vista in modo asincrono e il valore può essere vecchio.
     *
     * SELECT ... FOR UPDATE prende un lock esclusivo sulla riga del contatore e lo tiene fino
     * al COMMIT: due salvataggi simultanei si mettono in fila invece di leggere lo stesso
     * valore, e nessun numero esce due volte. Il lock vale però solo fra transazioni che lo
     * richiedono — una SELECT ordinaria legge lo snapshot e non aspetta — quindi ogni punto
     * che incrementa deve passare di qui.
     *
     * Se la riga non esiste non c'è nulla da bloccare, e in teoria due processi potrebbero
     * inserirla insieme: succede solo al primissimo utilizzo di un prefisso, una volta nella
     * vita della serie.
     */
    public function prossimo(string $classe, string $prefisso): string
    {
        $prefisso = strtoupper(trim($prefisso));
        $chiave   = self::CHIAVE . $prefisso;
        $db       = $this->db;

        $db->transStart();

        $riga = $db->query(
            "SELECT value FROM settings WHERE class = ? AND `key` = ? FOR UPDATE",
            [$classe, $chiave]
        )->getRowArray();

        $numero = (int) ($riga['value'] ?? 0) + 1;

        if ($riga) {
            $db->query(
                "UPDATE settings SET value = ?, updated_at = NOW() WHERE class = ? AND `key` = ?",
                [$numero, $classe, $chiave]
            );
        } else {
            $db->query(
                "INSERT INTO settings (class, `key`, value, type, context, created_at, updated_at)
                 VALUES (?, ?, ?, 'int', NULL, NOW(), NOW())",
                [$classe, $chiave, $numero]
            );
        }

        $db->transComplete();

        return $this->formatta($prefisso, $numero);
    }

    /**
     * Tutte le serie, per la pagina Impostazioni → Numeratori.
     *
     * Comprende anche quelle **previste ma non ancora usate**: una riga in `settings` nasce
     * con il primo codice generato, ma un tipo intervento appena configurato con il suo
     * prefisso è già un numeratore agli occhi di chi lo ha creato, fermo a zero e pronto a
     * partire da -0001. Senza questa unione la pagina resterebbe vuota su un'installazione
     * nuova, che è proprio il momento in cui si va a controllare che i codici siano a posto.
     *
     * Ogni riga porta il prossimo codice che verrà generato, che è l'informazione per cui si
     * apre quella pagina, e la data dell'ultimo utilizzo: dice a colpo d'occhio quali serie
     * sono vive e quali ferme da mesi.
     */
    public function elenco(): array
    {
        // Un prefisso come 'PIS' non dice niente a chi legge: il nome del tipo intervento sì.
        $tipi = array_column(
            (new TipiInterventoModel())
                ->select('prefisso_codice, nome')
                ->where('prefisso_codice IS NOT NULL')
                ->findAll(),
            'nome',
            'prefisso_codice'
        );

        // Serie previste dal progetto: i tre prefissi assegnati dal codice più uno per ogni
        // tipo abbonabile. Valgono zero finché non viene generato il primo codice.
        $attese = [
            [ClientiModel::CLASSE_NUMERATORE,    ClientiModel::PREFISSO_CODICE],
            [InterventiModel::CLASSE_NUMERATORE, InterventiModel::PREFISSO_CODICE],
            [InterventiModel::CLASSE_NUMERATORE, InterventiModel::PREFISSO_EXTRA],
        ];

        foreach (array_keys($tipi) as $prefissoTipo) {
            $attese[] = [InterventiModel::CLASSE_NUMERATORE, $prefissoTipo];
        }

        $serie = [];

        foreach ($attese as [$classe, $prefisso]) {
            $serie[$classe . '|' . $prefisso] = ['class' => $classe, 'prefisso' => $prefisso, 'value' => 0, 'updated_at' => null];
        }

        // Le righe reali sovrascrivono le attese e portano le serie di prefissi non più
        // previsti — un tipo intervento eliminato dopo aver generato codici resta visibile.
        foreach ($this->select('settings.class, settings.key, settings.value, settings.updated_at')
            ->like('settings.key', self::CHIAVE, 'after')
            ->findAll() as $riga) {
            $prefisso = substr($riga['key'], strlen(self::CHIAVE));

            $serie[$riga['class'] . '|' . $prefisso] = [
                'class'      => $riga['class'],
                'prefisso'   => $prefisso,
                'value'      => $riga['value'],
                'updated_at' => $riga['updated_at'],
            ];
        }

        $righe = [];

        foreach ($serie as $s) {
            $ultimo  = (int) $s['value'];
            $righe[] = $s + [
                'ultimo'        => $ultimo,
                'ultimo_codice' => $ultimo > 0 ? $this->formatta($s['prefisso'], $ultimo) : null,
                'prossimo'      => $this->formatta($s['prefisso'], $ultimo + 1),
                'descrizione'   => $this->descrizione($s['class'], $s['prefisso'], $tipi),
            ];
        }

        usort($righe, static fn (array $a, array $b): int
            => [$a['class'], $a['prefisso']] <=> [$b['class'], $b['prefisso']]);

        return $righe;
    }

    /**
     * A cosa serve una serie, in parole leggibili.
     *
     * I prefissi degli interventi da abbonamento arrivano dai tipi intervento, quindi il nome
     * lo sa il database. Gli altri sono fissi perché nascono nel codice e non da una tabella:
     * i clienti creati qui, gli interventi manuali e le visite extra, il cui prefisso viene
     * assegnato da InterventiModel::normalizza() quando l'intervento ha il flag `extra`.
     *
     * Una serie il cui tipo è stato nel frattempo eliminato resta senza descrizione: il
     * contatore continua a esistere, ed è giusto vederlo.
     */
    private function descrizione(string $classe, string $prefisso, array $tipi): ?string
    {
        if ($classe === ClientiModel::CLASSE_NUMERATORE) {
            return 'Clienti creati nel gestionale, non importati dalla contabilità';
        }

        if ($classe === InterventiModel::CLASSE_NUMERATORE) {
            return match ($prefisso) {
                InterventiModel::PREFISSO_CODICE => 'Interventi inseriti a mano',
                InterventiModel::PREFISSO_EXTRA  => 'Visite extra, fuori dalle scadenze dell\'abbonamento',
                default                          => $tipi[$prefisso] ?? null,
            };
        }

        return null;
    }

    /**
     * Compone il codice. str_pad non tronca: superate le CIFRE previste il numero cresce e il
     * codice si allunga, senza che la serie si interrompa.
     */
    private function formatta(string $prefisso, int $numero): string
    {
        return $prefisso . '-' . str_pad((string) $numero, self::CIFRE, '0', STR_PAD_LEFT);
    }
}

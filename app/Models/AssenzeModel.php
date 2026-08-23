<?php

namespace App\Models;

use CodeIgniter\Model;

class AssenzeModel extends Model
{
    protected $table         = 'assenze';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'personale_id', 'tipo', 'data_inizio', 'data_fine', 'note',
        'created_by', 'updated_by',
    ];

    protected $beforeInsert = ['normalizza'];
    protected $beforeUpdate = ['normalizza'];

    // valori: ferie, malattia, permesso, altro
    const TIPO_FERIE    = 'ferie';
    const TIPO_MALATTIA = 'malattia';
    const TIPO_PERMESSO = 'permesso';
    const TIPO_ALTRO    = 'altro';

    const TIPI_LABEL = [
        'ferie'    => 'Ferie',
        'malattia' => 'Malattia',
        'permesso' => 'Permesso',
        'altro'    => 'Altro',
    ];

    const TIPI_BADGE = [
        'ferie'    => 'bg-info text-dark',
        'malattia' => 'bg-danger',
        'permesso' => 'bg-warning text-dark',
        'altro'    => 'bg-secondary',
    ];

    /**
     * Imposta created_by all'inserimento e updated_by ad ogni salvataggio,
     * nullifica le note vuote.
     */
    protected function normalizza(array $data): array
    {
        $userId = user_id();

        if (! array_key_exists('id', $data)) {
            $data['data']['created_by'] = $userId;
        }
        $data['data']['updated_by'] = $userId;

        if (isset($data['data']['note']) && $data['data']['note'] === '') {
            $data['data']['note'] = null;
        }

        return $data;
    }

    /**
     * Diario delle assenze di un dipendente, dalla più recente.
     */
    public function perPersonale(int $personaleId): array
    {
        return $this->where('personale_id', $personaleId)
            ->orderBy('data_inizio', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    /**
     * Quante assenze sono registrate per un dipendente.
     *
     * Serve prima di eliminarne la scheda: assenze.personale_id ha ON DELETE CASCADE,
     * quindi la cancellazione porterebbe via anche tutto il suo diario.
     */
    public function contaPerPersonale(int $personaleId): int
    {
        return $this->where('personale_id', $personaleId)->countAllResults();
    }

    /**
     * Assenze dello stesso dipendente che si sovrappongono all'intervallo indicato.
     * $escludiId esclude il record stesso durante una modifica.
     */
    public function sovrapposizioni(int $personaleId, string $dataInizio, string $dataFine, int $escludiId = 0): array
    {
        $builder = $this->where('personale_id', $personaleId)
            ->where('data_inizio <=', $dataFine)
            ->where('data_fine >=', $dataInizio);

        if ($escludiId > 0) {
            $builder->where('id !=', $escludiId);
        }

        return $builder->findAll();
    }

    /**
     * Assenze che intersecano l'intervallo [start, end) richiesto dal calendario,
     * con nome/cognome/colore del dipendente per il rendering dell'evento.
     */
    public function perCalendario(string $start, string $end): array
    {
        return $this->select('assenze.*, p.nome AS personale_nome, p.cognome AS personale_cognome, p.colore AS personale_colore')
            ->join('personale p', 'p.id = assenze.personale_id', 'left')
            ->where('assenze.data_inizio <', $end)
            ->where('assenze.data_fine >=', $start)
            ->orderBy('assenze.data_inizio', 'ASC')
            ->findAll();
    }

    /**
     * Assenze non ancora concluse (per tecnico_id, data_inizio, data_fine), usate dal calendario
     * per avvisare se si pianifica un intervento su un tecnico assente in quella data.
     */
    public function daOggiInPoi(): array
    {
        return $this->select('personale_id, tipo, data_inizio, data_fine')
            ->where('data_fine >=', date('Y-m-d'))
            ->findAll();
    }

    /**
     * Assenze future raggruppate per dipendente (personale_id => [['data_inizio','data_fine','tipo_label'], ...]),
     * usate lato client per avvisare in tempo reale se si assegna un tecnico assente a un intervento.
     */
    public function mappaPerDipendente(): array
    {
        $mappa = [];
        foreach ($this->daOggiInPoi() as $a) {
            $mappa[(int) $a['personale_id']][] = [
                'data_inizio' => $a['data_inizio'],
                'data_fine'   => $a['data_fine'],
                'tipo_label'  => self::TIPI_LABEL[$a['tipo']] ?? ucfirst($a['tipo']),
            ];
        }

        return $mappa;
    }

    /**
     * Assenza del dipendente che copre la data indicata (solo la parte YYYY-MM-DD), o null se nessuna.
     * Controllo autoritativo lato server per bloccare l'assegnazione di un tecnico assente.
     */
    public function copreData(int $personaleId, string $data): ?array
    {
        $giorno = substr($data, 0, 10);

        return $this->where('personale_id', $personaleId)
            ->where('data_inizio <=', $giorno)
            ->where('data_fine >=', $giorno)
            ->first();
    }

    /**
     * Assenze che coprono la data odierna, con nome/cognome del dipendente — per la dashboard.
     */
    public function oggi(): array
    {
        $oggi = date('Y-m-d');

        return $this->select('assenze.*, p.nome AS personale_nome, p.cognome AS personale_cognome')
            ->join('personale p', 'p.id = assenze.personale_id', 'left')
            ->where('assenze.data_inizio <=', $oggi)
            ->where('assenze.data_fine >=', $oggi)
            ->orderBy('p.cognome', 'ASC')
            ->orderBy('p.nome', 'ASC')
            ->findAll();
    }
}

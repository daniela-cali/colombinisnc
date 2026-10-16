<?php

namespace App\Models;

use CodeIgniter\Encryption\Exceptions\EncryptionException;
use CodeIgniter\Model;

/**
 * Casella email e firma di un dipendente, per spedire dal gestionale. Una riga al massimo per
 * scheda personale. Sta fuori da personale perché quella tabella si legge con personale.* in
 * molti punti, e la firma con le immagini incorporate pesa ~100 KB.
 */
class PersonaleCaselleEmailModel extends Model
{
    protected $table         = 'personale_caselle_email';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'personale_id', 'smtp_password', 'firma_email',
        'created_by', 'updated_by',
    ];

    protected $beforeInsert = ['normalizza'];
    protected $beforeUpdate = ['normalizza'];

    /**
     * Imposta created_by solo all'inserimento e updated_by a ogni salvataggio. Cifra la
     * password e ripulisce la firma qui, perché succeda a ogni salvataggio: nessun chiamante
     * può scrivere la password in chiaro o la firma con le istruzioni del file.
     */
    protected function normalizza(array $data): array
    {
        $userId = user_id();

        if (! isset($data['id'])) {
            $data['data']['created_by'] = $userId;
        }
        $data['data']['updated_by'] = $userId;

        // L'encrypter restituisce dati binari: in base64 entrano in una colonna di testo.
        if (isset($data['data']['smtp_password']) && $data['data']['smtp_password'] !== '') {
            $data['data']['smtp_password'] = base64_encode(service('encrypter')->encrypt($data['data']['smtp_password']));
        }

        if (isset($data['data']['firma_email'])) {
            $data['data']['firma_email'] = $this->estraiFirma($data['data']['firma_email']);
        }

        return $data;
    }

    /**
     * La riga della casella di un dipendente, con la firma: per il profilo.
     */
    public function perPersonale(int $personaleId): ?array
    {
        return $this->where('personale_id', $personaleId)->first();
    }

    /**
     * Crea la riga della casella o aggiorna quella esistente: il profilo non deve sapere se il
     * dipendente l'ha già configurata.
     */
    public function salva(int $personaleId, array $dati): void
    {
        $esistente = $this->select('id')->where('personale_id', $personaleId)->first();

        if ($esistente) {
            $this->update($esistente['id'], $dati);
        } else {
            $this->insert(array_merge($dati, ['personale_id' => $personaleId]));
        }
    }

    /**
     * La password della casella in chiaro, solo per l'invio. Null se non è stata inserita o se
     * non si decifra più, per esempio dopo un cambio di encryption.key: in entrambi i casi va
     * reinserita nel profilo.
     */
    public function password(int $personaleId): ?string
    {
        $riga = $this->select('smtp_password')->where('personale_id', $personaleId)->first();

        if (! $riga || $riga['smtp_password'] === null) {
            return null;
        }

        try {
            return service('encrypter')->decrypt(base64_decode($riga['smtp_password']));
        } catch (EncryptionException) {
            return null;
        }
    }

    /**
     * Se il dipendente ha inserito la password della casella, senza leggere firma e password:
     * decide se «Invia per email» è attivo.
     */
    public function haPassword(int $personaleId): bool
    {
        return $this->select('id')
            ->where('personale_id', $personaleId)
            ->where('smtp_password IS NOT NULL', null, false)
            ->first() !== null;
    }

    /**
     * Dal file HTML della firma tiene solo la firma: il contenuto fra i commenti INIZIO FIRMA e
     * FINE FIRMA se ci sono, altrimenti quello di <body>, altrimenti tutto. Poi toglie i
     * commenti, perché le istruzioni per Gmail e Outlook non finiscano nell'email.
     */
    private function estraiFirma(string $html): string
    {
        if (preg_match('/<!--[^>]*INIZIO FIRMA.*?-->(.*)<!--[^>]*FINE FIRMA.*?-->/si', $html, $m)) {
            $html = $m[1];
        } elseif (preg_match('/<body[^>]*>(.*)<\/body>/si', $html, $m)) {
            $html = $m[1];
        }

        return trim(preg_replace('/<!--.*?-->/s', '', $html));
    }
}

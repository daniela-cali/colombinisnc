<?php

namespace App\Validation;

/**
 * Regole di validazione su date che il framework non copre: quelle di CI4 giudicano
 * un valore per volta, qui servono confronti fra due campi dello stesso form.
 * Registrata in Config\Validation::$ruleSets.
 */
class DateRules
{
    /**
     * Vera se la data non è anteriore a quella del campo indicato — l'uguaglianza passa.
     * `non_anteriore_a[data_inizio]` su una data di fine impedisce gli intervalli rovesciati
     * lasciando però possibile la durata di un giorno solo: è il caso dei cantieri, dove un
     * lavoro che si apre e si chiude in giornata esiste davvero.
     */
    public function non_anteriore_a(
        ?string $str,
        ?string $campoRiferimento,
        array $data,
        ?string $error = null,
        ?string $campo = null
    ): bool {
        $limiti = $this->limiti($str, $campoRiferimento, $data, $campo);

        return $limiti === null ? true : $limiti[0] >= $limiti[1];
    }

    /**
     * Come non_anteriore_a, ma l'uguaglianza non basta: la data dev'essere successiva.
     * `successiva_a[data_inizio]` su `data_fine` vieta l'intervallo di durata zero — un
     * abbonamento che inizia e finisce lo stesso giorno non è un abbonamento, è un intervento.
     */
    public function successiva_a(
        ?string $str,
        ?string $campoRiferimento,
        array $data,
        ?string $error = null,
        ?string $campo = null
    ): bool {
        $limiti = $this->limiti($str, $campoRiferimento, $data, $campo);

        return $limiti === null ? true : $limiti[0] > $limiti[1];
    }

    /**
     * Risolve il riferimento e restituisce [timestamp del valore, timestamp del limite],
     * oppure null quando non c'è niente da giudicare.
     *
     * Restituisce null — cioè "non mi esprimo" — se uno dei due valori manca o non è una data
     * leggibile: sono casi di competenza di permit_empty, required e valid_date, e bocciarli
     * anche qui produrrebbe due messaggi di errore per un solo problema.
     *
     * Il riferimento accetta il jolly delle regole su array: `successiva_a[periodi.*.data_inizio]`
     * applicato a `periodi.*.data_fine` confronta ogni riga con la propria, non con la prima.
     * CI4 passa alla regola il nome concreto del campo in esame (`periodi.2.data_fine`), da cui
     * si ricava l'indice; senza questo, il riferimento col jolly cercherebbe una chiave
     * inesistente e la regola tacerebbe sempre.
     *
     * @return array{0: int, 1: int}|null
     */
    private function limiti(?string $str, ?string $campoRiferimento, array $data, ?string $campo): ?array
    {
        if ($str === null || $str === '' || $campoRiferimento === null || $campoRiferimento === '') {
            return null;
        }

        helper('array');

        if (str_contains($campoRiferimento, '*') && $campo !== null) {
            $segmentiRiferimento = explode('.', $campoRiferimento);
            $segmentiCampo       = explode('.', $campo);

            foreach ($segmentiRiferimento as $i => $segmento) {
                if ($segmento === '*' && isset($segmentiCampo[$i])) {
                    $segmentiRiferimento[$i] = $segmentiCampo[$i];
                }
            }

            $campoRiferimento = implode('.', $segmentiRiferimento);
        }

        $riferimento = dot_array_search($campoRiferimento, $data);
        if (! is_string($riferimento) || $riferimento === '') {
            return null;
        }

        $valore = strtotime($str);
        $limite = strtotime($riferimento);

        return ($valore === false || $limite === false) ? null : [$valore, $limite];
    }
}

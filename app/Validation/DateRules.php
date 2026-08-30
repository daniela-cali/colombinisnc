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
     * Vera se la data non è anteriore a quella contenuta nel campo indicato:
     * `non_anteriore_a[data_inizio]` su `data_fine_prevista` impedisce gli intervalli
     * rovesciati, che a valle costringerebbero ogni calcolo di durata a difendersi.
     *
     * Non giudica quando uno dei due valori manca o non è una data leggibile: sono
     * casi di competenza di permit_empty e valid_date, e bocciarli anche qui
     * produrrebbe due messaggi di errore per un solo problema.
     */
    public function non_anteriore_a(?string $str, ?string $campoRiferimento, array $data, ?string &$error = null): bool
    {
        if ($str === null || $str === '' || $campoRiferimento === null || $campoRiferimento === '') {
            return true;
        }

        $riferimento = $data[$campoRiferimento] ?? null;
        if ($riferimento === null || $riferimento === '') {
            return true;
        }

        $valore = strtotime($str);
        $limite = strtotime($riferimento);
        if ($valore === false || $limite === false) {
            return true;
        }

        return $valore >= $limite;
    }
}

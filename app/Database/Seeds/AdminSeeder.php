<?php

namespace App\Database\Seeds;

use App\Controllers\Anagrafiche\PersonaleController;
use App\Models\PersonaleModel;
use App\Models\UserModel;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use RuntimeException;

/**
 * Crea l'account dell'amministratore iniziale con la sua scheda dipendente.
 *
 *     php spark db:seed AdminSeeder
 *
 * L'identità della persona sta nel file .env — `admin.username`, `admin.email`,
 * `admin.password`, `admin.nome`, `admin.cognome` — e non nel codice: la password è un
 * segreto che non deve entrare nel repository, e il resto la accompagna perché al go-live
 * sulla VPS si valorizzi tutto in un posto solo, senza modificare un file versionato.
 *
 * I gruppi invece restano qui sotto: non sono l'identità della persona ma i suoi permessi,
 * e la policy di questo progetto vive nel codice (vedi Config\AuthGroups). Un elenco di
 * gruppi nel .env sarebbe anche un invito al refuso, che produrrebbe in silenzio un account
 * senza accesso alle Impostazioni.
 *
 * Se una chiave manca il seeder si ferma: un valore di ripiego ricreerebbe esattamente
 * l'account di servizio con password prevedibile che questo seeder esiste per eliminare
 * (docs/spec/gestione_account_spec.md §6).
 *
 * È rieseguibile: in sviluppo l'account esiste già, al go-live il database sarà vuoto.
 * L'account viene riconosciuto dallo **username**, che è quindi la sua identità: cambiare
 * `admin.username` nel .env e rilanciare non rinomina niente, crea un secondo account.
 */
class AdminSeeder extends Seeder
{
    /**
     * `ufficio` è ridondante rispetto ai permessi — admin ha già tutto ciò che ha ufficio —
     * ma descrive il ruolo aziendale della persona, e alcuni controlli ragionano per gruppo
     * e non per permesso (per esempio le assenze in PersonaleController).
     */
    private const GRUPPI = ['admin', 'developer', 'ufficio'];

    public function run()
    {
        $dati    = $this->datiDaEnv();
        $users   = new UserModel();
        $persone = new PersonaleModel();

        if ($user = $users->findByCredentials(['username' => $dati['username']])) {
            $this->allinea($persone, $user, $dati);

            return;
        }

        // Account e scheda nella stessa transazione, come in PersonaleController::store():
        // sono una cosa sola, e un account creato a metà occuperebbe lo username senza
        // permettere di rientrare. creaAccount() ne apre una sua, che CI4 annida.
        $db = db_connect();
        $db->transStart();

        $user = $users->creaAccount(array_merge($dati, ['gruppi' => self::GRUPPI]), self::GRUPPI);

        $persone->insert([
            'user_id' => $user->id,
            'nome'    => $dati['nome'],
            'cognome' => $dati['cognome'],
            'colore'  => $this->primoColoreLibero($persone),
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new RuntimeException('Creazione non riuscita: nessun dato è stato salvato.');
        }

        echo "Account '{$dati['username']}' creato, con scheda dipendente e gruppi: "
            . implode(', ', self::GRUPPI) . ".\n";
    }

    /**
     * Legge l'identità dell'amministratore dal .env, fermandosi se manca qualcosa.
     *
     * @throws RuntimeException con l'elenco delle chiavi da aggiungere
     */
    private function datiDaEnv(): array
    {
        $dati     = [];
        $mancanti = [];

        foreach (['username', 'email', 'password', 'nome', 'cognome'] as $chiave) {
            $valore = env('admin.' . $chiave);

            if ($valore === null || $valore === false || $valore === '') {
                $mancanti[] = 'admin.' . $chiave;
                continue;
            }

            $dati[$chiave] = $valore;
        }

        if ($mancanti) {
            throw new RuntimeException(
                "Chiavi mancanti o vuote nel file .env: " . implode(', ', $mancanti) . ".\n"
                . "Valorizzale (il file va salvato in UTF-8, altrimenti le lettere accentate "
                . "arrivano storte nell'anagrafica) e rilancia il seeder."
            );
        }

        return $dati;
    }

    /**
     * Riesecuzione su un database che ha già l'account: aggiunge i gruppi mancanti e la
     * scheda dipendente se non c'è, e non tocca nient'altro.
     *
     * Email e password restano quelle in uso: allinearle al .env significherebbe cambiare
     * silenziosamente le credenziali di chi sta lavorando, che non è ciò che ci si aspetta
     * da un comando eseguito per essere sicuri che l'account esista.
     */
    private function allinea(PersonaleModel $persone, User $user, array $dati): void
    {
        echo "Account '{$dati['username']}' già presente (id {$user->id}).\n";

        $mancanti = array_diff(self::GRUPPI, $user->getGroups());

        foreach ($mancanti as $gruppo) {
            $user->addGroup($gruppo);
        }

        echo $mancanti
            ? '  gruppi aggiunti: ' . implode(', ', $mancanti) . ".\n"
            : "  gruppi già a posto.\n";

        if ($persone->perUtente((int) $user->id)) {
            echo "  scheda dipendente già presente.\n";
        } else {
            $persone->insert([
                'user_id' => $user->id,
                'nome'    => $dati['nome'],
                'cognome' => $dati['cognome'],
                'colore'  => $this->primoColoreLibero($persone),
            ]);
            echo "  scheda dipendente creata.\n";
        }

        echo "  email e password non toccate.\n";
    }

    /**
     * Primo colore non ancora assegnato della palette, così l'admin nasce già distinguibile
     * nel calendario senza doverlo scegliere a mano. Se sono esauriti riparte dal primo:
     * due colori uguali sono un fastidio, non un errore.
     */
    private function primoColoreLibero(PersonaleModel $persone): string
    {
        $liberi = array_values(array_diff(PersonaleController::PASTELLI, $persone->coloriUsati()));

        return $liberi[0] ?? PersonaleController::PASTELLI[0];
    }
}

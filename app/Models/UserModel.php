<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserIdentityModel;
use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;

class UserModel extends ShieldUserModel
{
    protected function initialize(): void
    {
        parent::initialize();
        $this->allowedFields[] = 'ultima_versione_vista';
    }

    /**
     * Tutti gli utenti Shield con nome/cognome da personale (se presente), email
     * e gruppi concatenati. Alimenta la vista d'insieme di Impostazioni > Utenti,
     * che risponde a "chi ha accesso, con quali ruoli, e chi è ancora attivo" —
     * domande a cui l'elenco del personale non sa rispondere.
     *
     * personale_id e cliente_id servono alla view per il collegamento alla scheda di
     * provenienza, e per smistare l'elenco fra le due schede Personale e Clienti. Sono
     * entrambi facoltativi: un account può non avere né l'una né l'altra, e in quel caso
     * deve restare comunque visibile — questa pagina è l'unico posto da cui vederlo.
     *
     * Lo stato mostrato è `status` ('banned' o null), non `active`: in questo progetto
     * `active` non impedisce l'accesso e indicarlo come "attivo" direbbe il falso su un
     * utente sospeso (vedi docs/spec/gestione_account_spec.md §2.2).
     */
    public function tuttiConGruppi(): array
    {
        return $this->select('users.id, users.username, users.status, ANY_VALUE(p.id) as personale_id, ANY_VALUE(p.nome) as nome, ANY_VALUE(p.cognome) as cognome, ANY_VALUE(c.id) as cliente_id, ANY_VALUE(c.denominazione) as cliente_denominazione, ANY_VALUE(i.secret) as email, GROUP_CONCAT(g.group ORDER BY g.group SEPARATOR ", ") as gruppi')
            ->join('personale p', 'p.user_id = users.id', 'left')
            ->join('clienti c', 'c.user_id = users.id', 'left')
            ->join('auth_groups_users g', 'g.user_id = users.id', 'left')
            ->join('auth_identities i', "i.user_id = users.id AND i.type = 'email_password'", 'left')
            ->groupBy('users.id')
            ->orderBy('users.username')
            ->asArray()
            ->findAll();
    }

    /**
     * Crea un account completo: utente, identità email e gruppi.
     *
     * I tre passi stanno in transazione perché finora non lo erano: se la creazione
     * dell'identità falliva (email già usata, vincolo UNIQUE su auth_identities)
     * restava un utente senza credenziali e senza gruppi, che però occupava lo username
     * scelto — al secondo tentativo l'operatore sbatteva su is_unique senza capire perché.
     *
     * Le transazioni di CI4 sono annidabili: il chiamante può aprirne una più ampia
     * (per includere la scheda personale) senza che questa la chiuda in anticipo.
     *
     * @param array $dati        username, email, password, gruppi
     * @param array $assegnabili gruppi che il chiamante ha il diritto di assegnare
     */
    public function creaAccount(array $dati, array $assegnabili): User
    {
        $this->db->transStart();

        $user = new User([
            'username' => $dati['username'],
            'active'   => true,
        ]);

        $this->save($user);
        $user = $this->findById($this->getInsertID());

        $user->createEmailIdentity([
            'email'    => $dati['email'],
            'password' => $dati['password'],
        ]);

        foreach (array_intersect((array) ($dati['gruppi'] ?? []), $assegnabili) as $gruppo) {
            $user->addGroup($gruppo);
        }

        $this->db->transComplete();

        return $user;
    }

    /**
     * Aggiorna email, gruppi e password di un account esistente.
     *
     * Unico punto di scrittura sull'account: prima queste tre operazioni erano copiate
     * parola per parola in UtentiController, PersonaleController e ProfiloController, ed
     * è da lì che è nata la possibilità per un utente ufficio di promuoversi ad admin
     * (vedi docs/spec/gestione_account_spec.md §1.1).
     *
     * I gruppi vengono toccati solo se compresi in $assegnabili, sia in aggiunta sia in
     * rimozione. Due conseguenze volute: chi non può assegnare un ruolo non può nemmeno
     * toglierlo per sbaglio salvando un form che non lo contiene; e passando un elenco
     * vuoto (il caso del profilo personale) i gruppi restano intatti anche se la richiesta
     * prova a includerli.
     *
     * @param array $dati        email, password (facoltativa), gruppi
     * @param array $assegnabili gruppi che il chiamante ha il diritto di modificare
     */
    public function aggiornaAccount(User $user, array $dati, array $assegnabili): void
    {
        $identity = $user->getEmailIdentity();
        if ($identity && isset($dati['email']) && $identity->secret !== $dati['email']) {
            $identity->secret = $dati['email'];
            model(UserIdentityModel::class)->save($identity);
        }

        $correnti = array_intersect($user->getGroups(), $assegnabili);
        $richiesti = array_intersect((array) ($dati['gruppi'] ?? []), $assegnabili);

        foreach (array_diff($correnti, $richiesti) as $gruppo) {
            $user->removeGroup($gruppo);
        }
        foreach (array_diff($richiesti, $correnti) as $gruppo) {
            $user->addGroup($gruppo);
        }

        if (! empty($dati['password'])) {
            // Rileggo l'utente prima di impostare la password, come faceva il codice
            // sostituito: costa una query e toglie ogni dubbio sullo stato dell'entità
            // dopo le modifiche ai gruppi.
            $utente = $this->findById($user->id);
            $utente->setPassword($dati['password']);
            $this->save($utente);
        }
    }
}

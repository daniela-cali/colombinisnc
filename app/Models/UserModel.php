<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;

class UserModel extends ShieldUserModel
{
    /**
     * Tutti gli utenti Shield con nome/cognome da personale (se presente)
     * e gruppi concatenati. Usato dalla sezione Impostazioni > Utenti.
     */
    public function tuttiConGruppi(): array
    {
        return $this->select('users.id, users.username, users.active, ANY_VALUE(p.nome) as nome, ANY_VALUE(p.cognome) as cognome, GROUP_CONCAT(g.group ORDER BY g.group SEPARATOR ", ") as gruppi')
            ->join('personale p', 'p.user_id = users.id', 'left')
            ->join('auth_groups_users g', 'g.user_id = users.id', 'left')
            ->groupBy('users.id')
            ->orderBy('users.username')
            ->asArray()
            ->findAll();
    }
}

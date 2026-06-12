<?php

namespace App\Database\Seeds;

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Database\Seeder;

/**
 * Crea l'utente amministratore iniziale con gruppo admin.
 * Eseguire una sola volta: php spark db:seed AdminSeeder
 */
class AdminSeeder extends Seeder
{
    public function run()
    {
        $users = auth()->getProvider();

        if ($users->findByCredentials(['username' => 'admin'])) {
            echo "Utente 'admin' già presente — seeder saltato.\n";
            return;
        }

        $user = new User([
            'username' => 'admin',
            'email'    => 'admin@colombini-snc.it',
            'password' => 'Admin1234!',
        ]);

        $users->save($user);

        $user = $users->findById($users->getInsertID());
        $users->activate($user);
        $user->addGroup('admin');

        echo "Utente 'admin' creato con gruppo 'admin'.\n";
    }
}

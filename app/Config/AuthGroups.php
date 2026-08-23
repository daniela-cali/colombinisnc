<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter Shield.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /**
     * --------------------------------------------------------------------
     * Default Group
     * --------------------------------------------------------------------
     * The group that a newly registered user is added to.
     */
    public string $defaultGroup = 'cliente';

    /**
     * --------------------------------------------------------------------
     * Groups
     * --------------------------------------------------------------------
     * An associative array of the available groups in the system, where the keys
     * are the group names and the values are arrays of the group info.
     *
     * Whatever value you assign as the key will be used to refer to the group
     * when using functions such as:
     *      $user->addGroup('superadmin');
     *
     * @var array<string, array<string, string>>
     *
     * @see https://codeigniter4.github.io/shield/quick_start_guide/using_authorization/#change-available-groups for more info
     */
    public array $groups = [
        'admin' => [
            'title'       => 'Admin',
            'description' => 'Amministratore del sito',
        ],
        'developer' => [
            'title'       => 'Sviluppatore',
            'description' => 'Sviluppatore applicazione',
        ],
        'ufficio' => [
            'title'     => 'Staff',
            'description' => 'Staff amministrativo',
        ],
        'tecnico' => [
            'title'     => 'Tecnico',
            'description' => 'Operatore tecnico sul campo',
        ],
        'cliente' => [
            'title'     => 'Cliente',
            'description' => 'Cliente con accesso al portale',
        ],
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions
     * --------------------------------------------------------------------
     * The available permissions in the system.
     *
     * If a permission is not listed here it cannot be used.
     */
    public array $permissions = [
        'personale.manage'    => 'Può gestire l\'anagrafica del personale e le assenze',
        'personale.account'   => 'Può creare account, assegnare ruoli e cambiare le password altrui',
        'personale.elimina'   => 'Può eliminare dipendenti e account',
        'impostazioni.manage' => 'Può accedere alle impostazioni applicative',
        'abbonamenti.manage'  => 'Può creare, modificare e cambiare stato agli abbonamenti',
        'cantieri.manage'     => 'Può creare, modificare, cambiare stato ed eliminare i cantieri',
        'magazzino.elimina'   => 'Può eliminare gli articoli di magazzino',
        'clienti.elimina'     => 'Può eliminare un cliente',
        'interventi.elimina'  => 'Può eliminare un intervento (distinto da annulla)',
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions Matrix
     * --------------------------------------------------------------------
     * Maps permissions to groups.
     *
     * This defines group-level permissions.
     */
    //Costante di definizione permessi, eventualmente per permettere più granularità
    private const PERMESSI_ADMIN = [
        'personale.*', 'impostazioni.*', 'abbonamenti.*',
        'cantieri.*', 'magazzino.*', 'clienti.*', 'interventi.*',
    ];
    // Elencati uno per uno di proposito: con le wildcard (`personale.*`) ogni permesso
    // aggiunto in futuro finirebbe in silenzio anche a ufficio. È così che ufficio poteva
    // promuoversi ad admin — vedi docs/spec/gestione_account_spec.md §1.1.
    private const PERMESSI_UFFICIO = [
        'personale.manage',
        'abbonamenti.manage',
        'cantieri.manage',
        'magazzino.elimina',
        'clienti.elimina',
        'interventi.elimina',
    ];
   
    
    public array $matrix = [
        'admin' => self::PERMESSI_ADMIN,
        'developer' => self::PERMESSI_ADMIN,
        'ufficio' => self::PERMESSI_UFFICIO,
        'tecnico' => [],
        'cliente' => [],
    ];
}

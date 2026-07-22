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
    public string $defaultGroup = 'ufficio';

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
        'personale.manage'    => 'Può gestire anagrafica personale e account',
        'impostazioni.manage' => 'Può accedere alle impostazioni applicative',
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions Matrix
     * --------------------------------------------------------------------
     * Maps permissions to groups.
     *
     * This defines group-level permissions.
     */
    public array $matrix = [
        'admin' => [
            'personale.manage',
            'impostazioni.manage',
        ],
        'developer' => [
            'personale.manage',
            'impostazioni.manage',
        ],
        'ufficio' => [
            'personale.manage',
            'impostazioni.manage',
        ],
        'tecnico' => [],
        'cliente' => [],
    ];
}

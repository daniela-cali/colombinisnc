<?php

namespace Config;

use App\Validation\DateRules;
use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
        // Regole del progetto: confronti fra due campi data (vedi app/Validation/DateRules.php)
        DateRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    /**
     * Regole di validazione per il form di login (sovrascrive Shield che usa email di default).
     *
     * @var array<string, array<string, list<string>|string>>
     */
    public array $login = [
        'username' => [
            'label' => 'Auth.username',
            'rules' => ['required', 'max_length[30]', 'min_length[3]'],
        ],
        'password' => [
            'label' => 'Auth.password',
            'rules' => ['required', 'max_byte[72]'],
        ],
    ];
}

<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        $this->helpers = ['changelog', 'validazione'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * Valida i dati della richiesta dando a ogni campo un'etichetta leggibile.
     *
     * I messaggi di CI4 mostrano il nome grezzo della colonna quando la regola non
     * ha una `label`: all'utente arrivava «Il campo "tipo_intervento_id" è
     * obbligatorio». Le etichette si aggiungono qui, in un punto solo, invece che
     * nei 35 punti in cui i controller validano — vedi `validazione_helper.php`.
     *
     * Un `$rules` in forma di stringa è il nome di un gruppo di regole definito
     * nella configurazione, non un array di campi: passa oltre intatto.
     *
     * @param array|string $rules
     * @param array        $messages An array of custom error messages
     */
    protected function validate($rules, array $messages = []): bool
    {
        if (is_array($rules)) {
            $rules = regole_con_etichette($rules);
        }

        return parent::validate($rules, $messages);
    }
}

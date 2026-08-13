<?php

namespace App\Commands;

use App\Models\AbbonamentiModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Comando Spark: php spark batch:AbbonamentiScaduti
 *
 * Batch notturno per la verifica e l'aggiornamento stato degli abbonamenti scaduti
 */
class AbbonamentiScaduti extends BaseCommand
{
    protected $group = 'batch';
    protected $name = 'batch:abbonamenti-scaduti';
    protected $description = 'Modifica lo stato degli abbonamenti scaduti se data > data scadenza';
    protected $options = [
        '-force' => 'Salta la conferma interattiva e aggiorna direttamente i record scaduti (necessario per l\'esecuzione da cron)',
    ];
    protected $from = 'abbonamenti_scaduti';

    public function run(array $params)
    {
        $header = '*** Inizio Batch Abbonamenti Scaduti ***'.PHP_EOL;
        $footer = '*** Fine Batch Abbonamenti Scaduti ***'.PHP_EOL;

        helper('custom_log');
        custom_log($this->from, $header);

        $model = new AbbonamentiModel();
        $abbonamenti = $model->leggiScaduti();

        $n = count($abbonamenti);
        if($n > 0){
            custom_log($this->from, $abbonamenti);
            $ids = array_column($abbonamenti, 'id');
            $thead = ['ID', 'Cliente', 'Tipo Abbonamento', 'Stato', 'Data Scadenza'];
            $tbody = $abbonamenti;
            CLI::table($tbody, $thead);
            $aggiorna = CLI::getOption('force') ? 's' : CLI::prompt('Vuoi aggiornare i record indicati?', ['s', 'n']);
            if ($aggiorna === 's') {
                custom_log($this->from, 'Procedo ad aggiornare i record indicati');
                CLI::write('Procedo con l\'aggiornamento', 'black','green');
                $u = $model->updateScaduti($ids);
                if ($u > 0) {
                    CLI::write($u.' abbonamenti aggiornati a scaduto');
                    custom_log($this->from, 'Aggiornati '. $u .' record');
                }
            } else {
                custom_log($this->from, 'Aggiornamento interrotto dall\'utente');
            }
        } else {
            CLI::write('Nessun abbonamento scaduto. Risultano tutti attivi');
            custom_log($this->from, 'Nessun abbonamento scaduto. Risultano tutti attivi');
        }
        custom_log($this->from, $footer);
                
    }
}

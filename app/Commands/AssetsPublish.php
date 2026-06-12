<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Comando Spark: php spark assets:publish
 *
 * Copia i file dist delle dipendenze frontend da node_modules/
 * verso public/assets/vendor/, rendendoli accessibili al browser.
 *
 * Da eseguire dopo ogni "npm install" o aggiornamento di pacchetti.
 * Sul server di produzione non serve: i file sono committati in git.
 */
class AssetsPublish extends BaseCommand
{
    protected $group       = 'Assets';
    protected $name        = 'assets:publish';
    protected $description = 'Copia i file dist di ogni asset in public/assets/vendor/.';

    public function run(array $params): void
    {
        $root     = rtrim(ROOTPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $destBase = rtrim(FCPATH, DIRECTORY_SEPARATOR)   . DIRECTORY_SEPARATOR
            . 'assets' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;

        $manifest = [
            ['label' => 'AdminLTE CSS',          'src' => 'node_modules/admin-lte/dist/css',          'dest' => 'adminlte',              'pattern' => 'adminlte.min.css'],
            ['label' => 'AdminLTE JS',           'src' => 'node_modules/admin-lte/dist/js',           'dest' => 'adminlte',              'pattern' => 'adminlte.min.js'],

            ['label' => 'Bootstrap JS',          'src' => 'node_modules/bootstrap/dist/js',           'dest' => 'bootstrap',             'pattern' => 'bootstrap.min.js'],

            ['label' => 'Popper.js',             'src' => 'node_modules/@popperjs/core/dist/umd',     'dest' => 'popper',                'pattern' => 'popper.min.js'],

            ['label' => 'OverlayScrollbars CSS', 'src' => 'node_modules/overlayscrollbars/styles',    'dest' => 'overlayscrollbars',     'pattern' => 'overlayscrollbars.min.css'],
            ['label' => 'OverlayScrollbars JS',  'src' => 'node_modules/overlayscrollbars/browser',   'dest' => 'overlayscrollbars',     'pattern' => 'overlayscrollbars.browser.es6.min.js'],

            ['label' => 'Bootstrap Icons CSS',   'src' => 'node_modules/bootstrap-icons/font',        'dest' => 'bootstrap-icons',       'pattern' => 'bootstrap-icons.min.css'],
            ['label' => 'Bootstrap Icons fonts', 'src' => 'node_modules/bootstrap-icons/font/fonts',  'dest' => 'bootstrap-icons/fonts', 'pattern' => '*.woff2'],
        ];

        CLI::write('Pubblicazione assets frontend...', 'yellow');
        CLI::newLine();
        $errors = 0;

        foreach ($manifest as $entry) {
            $srcDir  = $root . str_replace('/', DIRECTORY_SEPARATOR, $entry['src']);
            $destDir = $destBase . str_replace('/', DIRECTORY_SEPARATOR, $entry['dest']);

            if (! is_dir($srcDir)) {
                CLI::error("  [SKIP] {$entry['label']}: sorgente non trovata");
                CLI::write("         Controlla che node_modules/ esista (esegui npm install).", 'dark_gray');
                $errors++;
                continue;
            }

            if (! is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            $files = glob($srcDir . DIRECTORY_SEPARATOR . $entry['pattern'], GLOB_BRACE);

            if (empty($files)) {
                CLI::write("  [WARN] {$entry['label']}: nessun file trovato (pattern: {$entry['pattern']})", 'yellow');
                continue;
            }

            $copied = 0;
            foreach ($files as $file) {
                if (! is_file($file)) {
                    continue;
                }
                copy($file, $destDir . DIRECTORY_SEPARATOR . basename($file));
                $copied++;
            }

            CLI::write("  [OK]   {$entry['label']} ({$copied} file)", 'green');
        }

        CLI::newLine();

        if ($errors > 0) {
            CLI::write("Completato con {$errors} errori. Verifica i messaggi [SKIP] sopra.", 'red');
        } else {
            CLI::write('Tutti gli assets pubblicati in public/assets/vendor/', 'green');
        }
    }
}

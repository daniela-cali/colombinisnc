<?php

if (! function_exists('changelog_to_html')) {
    /**
     * Converte il markdown del CHANGELOG in HTML raggruppando per categoria [APP]/[DEV].
     * Se il testo non contiene righe ## (es. estratto di una sola versione), gli item
     * vengono emessi direttamente senza intestazione di versione.
     * Le righe [DEV] vengono incluse solo se $isDevMode è true.
     */
    function changelog_to_html(string $md, bool $isDevMode): string
    {
        $html     = '';
        $version  = '';
        $appItems = [];
        $devItems = [];

        $flush = function () use (&$html, &$version, &$appItems, &$devItems, $isDevMode) {
            if (! $appItems && ! $devItems) {
                return;
            }
            if ($version) {
                $html .= '<h5 class="mt-3 mb-2 pb-1 border-bottom">' . htmlspecialchars($version) . '</h5>';
            }
            if ($appItems) {
                $html .= '<p class="small fw-semibold text-muted mb-1">Applicazione</p><ul class="mb-2">';
                foreach ($appItems as $i) {
                    $html .= '<li>' . htmlspecialchars($i) . '</li>';
                }
                $html .= '</ul>';
            }
            if ($isDevMode && $devItems) {
                $html .= '<p class="small fw-semibold text-muted mb-1">Sviluppo</p><ul class="mb-2">';
                foreach ($devItems as $i) {
                    $html .= '<li>' . htmlspecialchars($i) . '</li>';
                }
                $html .= '</ul>';
            }
            $appItems = [];
            $devItems = [];
        };

        foreach (explode("\n", $md) as $line) {
            if (str_starts_with($line, '## ')) {
                $flush();
                $version = preg_replace('/^## \[(.+?)\].*$/', 'v$1', $line);
            } elseif (str_starts_with($line, '- [APP]')) {
                $appItems[] = trim(substr($line, 7));
            } elseif (str_starts_with($line, '- [DEV]')) {
                $devItems[] = trim(substr($line, 7));
            }
        }
        $flush();

        return $html;
    }
}

if (! function_exists('changelog_data')) {
    /**
     * Legge CHANGELOG.md e restituisce i dati pronti per i modali nel layout.
     */
    function changelog_data(bool $isDevMode): array
    {
        $md = @file_get_contents(ROOTPATH . 'CHANGELOG.md');

        if (! $md) {
            return ['versioneCorrente' => '', 'htmlNovita' => '', 'htmlChangelog' => ''];
        }

        $versioneCorrente = '';
        if (preg_match('/^## \[(\d+\.\d+\.\d+)\]/m', $md, $m)) {
            $versioneCorrente = $m[1];
        }

        // Estrae solo il contenuto della versione più recente (senza l'intestazione ##)
        $sezioneNovita = '';
        if ($versioneCorrente) {
            $pattern = '/^## \[' . preg_quote($versioneCorrente, '/') . '\][^\n]*\n(.*?)(?=^## \[|\z)/ms';
            preg_match($pattern, $md, $ms);
            $sezioneNovita = trim($ms[1] ?? '');
        }
        $htmlNovita = $sezioneNovita !== '' ? changelog_to_html($sezioneNovita, $isDevMode) : '';

        return [
            'versioneCorrente' => $versioneCorrente,
            'htmlNovita'       => $htmlNovita,
            'htmlChangelog'    => changelog_to_html($md, $isDevMode),
        ];
    }
}

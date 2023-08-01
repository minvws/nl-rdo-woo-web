<?php

declare(strict_types=1);

namespace App;

/**
 * Converts citation codes to human-readable classifications.
 */
class Citation
{
    /** @var array|string[] */
    public static array $citations = [
        '5.1.1a' => 'Eenheid van de Kroon',
        '5.1.1b' => 'Veiligheid van de Staat',
        '5.1.1c' => 'Vertrouwelijke bedrijfs- of fabricagegegevens',
        '5.1.1d' => 'Persoonsgegevens',
        '5.1.1e' => 'Persoonlijke identificatienummers',
        '5.1.2a' => 'Internationale betrekkingen',
        '5.1.2b' => 'Economische of financiële belangen van de overheid',
        '5.1.2c' => 'Opsporing en vervolging van strafbare feiten',
        '5.1.2d' => 'Inspectie, controle en toezicht',
        '5.1.2e' => 'Eerbiediging van de persoonlijke levenssfeer',
        '5.1.2f' => 'Concurrentiegevoelige bedrijfs- en fabricagegegevens',
        '5.1.2g' => 'Bescherming van het milieu',
        '5.1.2h' => 'Beveiliging van personen en bedrijven',
        '5.1.2i' => 'Goed functioneren van de overheid',
    ];

    public static function toClassification(string $citation): string
    {
        $citation = str_replace(' ', '', $citation);
        $citation = strtolower($citation);

        if (isset(self::$citations[$citation])) {
            return self::$citations[$citation];
        }

        return "Onbekende reden ($citation)";
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Publication;

use Webmozart\Assert\Assert;

/**
 * Converts citation codes to human-readable classifications.
 */
class Citation
{
    public const string DUBBEL = 'dubbel';
    public const string TYPE_WOO = 'woo';
    public const string TYPE_WOB = 'wob';
    public const string TYPE_UNKNOWN = 'wob';

    /** @var array<string,string> */
    public static array $wooCitations = [
        '5.1.1a' => 'Eenheid van de Kroon',
        '5.1.1b' => 'Veiligheid van de Staat',
        '5.1.1c' => 'Vertrouwelijk verstrekte bedrijfs- of fabricagegegevens',
        '5.1.1d' => 'Bijzondere persoonsgegevens',
        '5.1.1e' => 'Nationale identificatienummers',
        '5.1.2a' => 'Internationale betrekkingen',
        '5.1.2b' => 'Economische of financiële belangen van de Staat',
        '5.1.2c' => 'Opsporing en vervolging van strafbare feiten',
        '5.1.2d' => 'Inspectie, controle en toezicht van bestuursorganen',
        '5.1.2e' => 'Eerbiediging van de persoonlijke levenssfeer',
        '5.1.2f' => 'Bescherming van andere dan vertrouwelijk aan de overheid verstrekte concurrentiegevoelige bedrijfs- en fabricagegevens',
        '5.1.2g' => 'De bescherming van het milieu waarop deze informatie betrekking heeft',
        '5.1.2h' => 'De beveiliging van personen of bedrijven en het voorkomen van sabotage',
        '5.1.2i' => 'Het goed functioneren van de staat, andere publiekrechtelijke lichamen of bestuursorganen',
        '5.1.5' => 'Het voorkomen van onevenredige benadeling',
        '5.2' => 'Persoonlijke beleidsopvattingen',
        '5.4' => 'Informatie die berust bij de formateur',

        self::DUBBEL => ': inhoud is in een ander document al beoordeeld',
    ];

    /** @var array<string,string> */
    public static array $wobCitations = [
        '10.1a' => 'Eenheid van de Kroon',
        '10.1b' => 'Veiligheid van de Staat',
        '10.1c' => 'Vertrouwelijk verstrekte bedrijfs- en fabricagegegevens',
        '10.1d' => 'Bijzondere persoonsgegevens',
        '10.2a' => 'Internationale betrekkingen',
        '10.2b' => 'Economische of financiële belangen van de Staat',
        '10.2c' => 'Opsporing en vervolging van strafbare feiten',
        '10.2d' => 'Inspectie, controle en toezicht door bestuursorganen',
        '10.2e' => 'Eerbiediging van de persoonlijke levenssfeer',
        '10.2g' => 'Het voorkomen van onevenredige bevoordeling of benadeling',
        '11.1' => 'Persoonlijke beleidsopvattingen',
        self::DUBBEL => ': inhoud is in een ander document al beoordeeld',
    ];

    /**
     * Converts a given citation to a human-readable classification.
     */
    public static function toClassification(string $citation): string
    {
        $canonical = str_replace(' ', '', $citation);
        $canonical = strtolower($canonical);

        if (isset(self::$wobCitations[$canonical])) {
            return self::$wobCitations[$canonical];
        }

        // Unknown citations get no classification intentionally
        return self::$wooCitations[$canonical] ?? '';
    }

    /**
     * Returns citation type: woo, wob or unknown.
     */
    public static function getCitationType(string $citation): string
    {
        $citation = str_replace(' ', '', $citation);
        $citation = strtolower($citation);

        if (isset(self::$wooCitations[$citation])) {
            return self::TYPE_WOO;
        }
        if (isset(self::$wobCitations[$citation])) {
            return self::TYPE_WOB;
        }

        return self::TYPE_UNKNOWN;
    }

    /**
     * @param array<array-key,string> $citations
     *
     * @return list<string>
     */
    public static function sortWooCitations(array $citations): array
    {
        Assert::allString($citations);

        $allCitations = self::$wooCitations;
        if (key_exists(Citation::DUBBEL, $allCitations)) {
            unset($allCitations[Citation::DUBBEL]);
        }
        $allCitations = array_keys($allCitations);
        Assert::allString($allCitations);

        $allCitations = array_flip($allCitations);

        usort($citations, static function (string $a, string $b) use ($allCitations): int {
            if (isset($allCitations[$a], $allCitations[$b])) {
                return $allCitations[$a] <=> $allCitations[$b];
            }

            if (isset($allCitations[$a])) {
                return -1;
            }

            if (isset($allCitations[$b])) {
                return 1;
            }

            return strcmp($a, $b);
        });

        return $citations;
    }
}

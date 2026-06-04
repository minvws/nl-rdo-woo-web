<?php

declare(strict_types=1);

namespace Shared\Domain\Publication;

use Webmozart\Assert\Assert;

use function array_flip;
use function array_keys;
use function key_exists;
use function str_replace;
use function strcmp;
use function strtolower;
use function usort;

/**
 * Converts citation codes to human-readable classifications.
 */
class Citation
{
    public const string DUBBEL = 'dubbel';
    public const string TYPE_WOO = 'woo';
    public const string TYPE_WOB = 'wob';
    public const string TYPE_UNKNOWN = 'wob';

    public const string GROUND_WOO_511A = '5.1.1a';
    public const string GROUND_WOO_511B = '5.1.1b';
    public const string GROUND_WOO_511C = '5.1.1c';
    public const string GROUND_WOO_511D = '5.1.1d';
    public const string GROUND_WOO_511E = '5.1.1e';
    public const string GROUND_WOO_512A = '5.1.2a';
    public const string GROUND_WOO_512B = '5.1.2b';
    public const string GROUND_WOO_512C = '5.1.2c';
    public const string GROUND_WOO_512D = '5.1.2d';
    public const string GROUND_WOO_512E = '5.1.2e';
    public const string GROUND_WOO_512F = '5.1.2f';
    public const string GROUND_WOO_512G = '5.1.2g';
    public const string GROUND_WOO_512H = '5.1.2h';
    public const string GROUND_WOO_512I = '5.1.2i';
    public const string GROUND_WOO_515 = '5.1.5';
    public const string GROUND_WOO_52 = '5.2';
    public const string GROUND_WOO_54 = '5.4';

    public const string GROUND_WOB_101A = '10.1a';
    public const string GROUND_WOB_101B = '10.1b';
    public const string GROUND_WOB_101C = '10.1c';
    public const string GROUND_WOB_101D = '10.1d';
    public const string GROUND_WOB_102A = '10.2a';
    public const string GROUND_WOB_102B = '10.2b';
    public const string GROUND_WOB_102C = '10.2c';
    public const string GROUND_WOB_102D = '10.2d';
    public const string GROUND_WOB_102E = '10.2e';
    public const string GROUND_WOB_102G = '10.2g';
    public const string GROUND_WOB_111 = '11.1';

    /** @var array<string,string> */
    public static array $wooCitations = [
        self::GROUND_WOO_511A => 'Eenheid van de Kroon',
        self::GROUND_WOO_511B => 'Veiligheid van de Staat',
        self::GROUND_WOO_511C => 'Vertrouwelijk verstrekte bedrijfs- of fabricagegegevens',
        self::GROUND_WOO_511D => 'Bijzondere persoonsgegevens',
        self::GROUND_WOO_511E => 'Nationale identificatienummers',
        self::GROUND_WOO_512A => 'Internationale betrekkingen',
        self::GROUND_WOO_512B => 'Economische of financiële belangen van de Staat',
        self::GROUND_WOO_512C => 'Opsporing en vervolging van strafbare feiten',
        self::GROUND_WOO_512D => 'Inspectie, controle en toezicht van bestuursorganen',
        self::GROUND_WOO_512E => 'Eerbiediging van de persoonlijke levenssfeer',
        self::GROUND_WOO_512F =>
            'Bescherming van andere dan vertrouwelijk aan de overheid verstrekte concurrentiegevoelige bedrijfs- en fabricagegevens',
        self::GROUND_WOO_512G => 'De bescherming van het milieu waarop deze informatie betrekking heeft',
        self::GROUND_WOO_512H => 'De beveiliging van personen of bedrijven en het voorkomen van sabotage',
        self::GROUND_WOO_512I => 'Het goed functioneren van de staat, andere publiekrechtelijke lichamen of bestuursorganen',
        self::GROUND_WOO_515 => 'Het voorkomen van onevenredige benadeling',
        self::GROUND_WOO_52 => 'Persoonlijke beleidsopvattingen',
        self::GROUND_WOO_54 => 'Informatie die berust bij de formateur',
        self::DUBBEL => ': inhoud is in een ander document al beoordeeld',
    ];

    /** @var array<string,string> */
    public static array $wobCitations = [
        self::GROUND_WOB_101A => 'Eenheid van de Kroon',
        self::GROUND_WOB_101B => 'Veiligheid van de Staat',
        self::GROUND_WOB_101C => 'Vertrouwelijk verstrekte bedrijfs- en fabricagegegevens',
        self::GROUND_WOB_101D => 'Bijzondere persoonsgegevens',
        self::GROUND_WOB_102A => 'Internationale betrekkingen',
        self::GROUND_WOB_102B => 'Economische of financiële belangen van de Staat',
        self::GROUND_WOB_102C => 'Opsporing en vervolging van strafbare feiten',
        self::GROUND_WOB_102D => 'Inspectie, controle en toezicht door bestuursorganen',
        self::GROUND_WOB_102E => 'Eerbiediging van de persoonlijke levenssfeer',
        self::GROUND_WOB_102G => 'Het voorkomen van onevenredige bevoordeling of benadeling',
        self::GROUND_WOB_111 => 'Persoonlijke beleidsopvattingen',
        self::DUBBEL => ': inhoud is in een ander document al beoordeeld',
    ];

    public const array WOO_GROUND_KEYS = [
        self::GROUND_WOO_511A,
        self::GROUND_WOO_511B,
        self::GROUND_WOO_511C,
        self::GROUND_WOO_511D,
        self::GROUND_WOO_511E,
        self::GROUND_WOO_512A,
        self::GROUND_WOO_512B,
        self::GROUND_WOO_512C,
        self::GROUND_WOO_512D,
        self::GROUND_WOO_512E,
        self::GROUND_WOO_512F,
        self::GROUND_WOO_512G,
        self::GROUND_WOO_512H,
        self::GROUND_WOO_512I,
        self::GROUND_WOO_515,
        self::GROUND_WOO_52,
        self::GROUND_WOO_54,
        self::DUBBEL,
    ];

    public const array WOB_GROUND_KEYS = [
        self::GROUND_WOB_101A,
        self::GROUND_WOB_101B,
        self::GROUND_WOB_101C,
        self::GROUND_WOB_101D,
        self::GROUND_WOB_102A,
        self::GROUND_WOB_102B,
        self::GROUND_WOB_102C,
        self::GROUND_WOB_102D,
        self::GROUND_WOB_102E,
        self::GROUND_WOB_102G,
        self::GROUND_WOB_111,
        self::DUBBEL,
    ];

    public const array ALL_GROUND_KEYS = [
        ...self::WOO_GROUND_KEYS,
        ...self::WOB_GROUND_KEYS,
    ];

    /**
     * Converts a given citation to a human-readable classification.
     */
    public static function toClassification(string $citation): string
    {
        $canonical = str_replace(' ', '', $citation);
        $canonical = strtolower($canonical);

        // Unknown citations get no classification intentionally
        return self::$wobCitations[$canonical] ?? self::$wooCitations[$canonical] ?? '';
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

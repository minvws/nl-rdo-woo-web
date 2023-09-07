<?php

declare(strict_types=1);

namespace App;

/**
 * Converts citation codes to human-readable classifications.
 */
class Citation
{
    public const DUBBEL = 'dubbel';

    /** @var array|string[] */
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
        self::DUBBEL => 'Dubbel: inhoud is in een ander document al beoordeeld',
    ];

    /** @var array|string[] */
    public static array $wobCitations = [
        '10.1.a' => 'Eenheid van de Kroon',
        '10.1.b' => 'Veiligheid van de Staat',
        '10.1.c' => 'Vertrouwelijk verstrekte bedrijfs- en fabricagegegevens',
        '10.1.d' => 'Bijzondere persoonsgegevens',
        '10.2.a' => 'Internationale betrekkingen',
        '10.2.b' => 'Economische of financiële belangen van de Staat',
        '10.2.c' => 'Opsporing en vervolging van strafbare feiten',
        '10.2.d' => 'Inspectie, controle en toezicht door bestuursorganen',
        '10.2.e' => 'Eerbiediging van de persoonlijke levenssfeer',
        '10.2.g' => 'Het voorkomen van onevenredige bevoordeling of benadeling',
        '11.1' => 'Persoonlijke beleidsopvattingen',
        self::DUBBEL => 'Dubbel: inhoud is in een ander document al beoordeeld',
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

        if (isset(self::$wooCitations[$canonical])) {
            return self::$wooCitations[$canonical];
        }

        // Unknown citations get no classification intentionally
        return '';
    }

    /**
     * Returns citation type: woo, wob or unknown.
     */
    public static function getCitationType(string $citation): string
    {
        $citation = str_replace(' ', '', $citation);
        $citation = strtolower($citation);

        if (isset(self::$wooCitations[$citation])) {
            return 'woo';
        }
        if (isset(self::$wobCitations[$citation])) {
            return 'wob';
        }

        return 'unknown';
    }
}

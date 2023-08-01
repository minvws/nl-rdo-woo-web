<?php

declare(strict_types=1);

namespace App\Service\Search\Model;

class Facet
{
    public const FACET_DEPARTMENT = 'department';
    public const FACET_OFFICIAL = 'official';
    public const FACET_SUBJECT = 'subject';
    public const FACET_SOURCE = 'source';
    public const FACET_PERIOD = 'period';
    public const FACET_GROUNDS = 'grounds';
    public const FACET_JUDGEMENT = 'judgement';
    public const FACET_DATE_FROM = 'date_from';
    public const FACET_DATE_TO = 'date_to';
    public const FACET_DOSSIER_NR = 'dossier_nr';

    /**
     * This returns a mapping between the facet name and the query parameter name. These do not match exactly so we can change
     * mapping internally without disrupting the API.
     *
     * @return string[]
     */
    public static function getQueryMapping(): array
    {
        return [
            self::FACET_DEPARTMENT => 'dep',
            self::FACET_OFFICIAL => 'off',
            self::FACET_SUBJECT => 'sub',
            self::FACET_SOURCE => 'src',
            self::FACET_PERIOD => 'prd',
            self::FACET_GROUNDS => 'gnd',
            self::FACET_JUDGEMENT => 'jdg',
            self::FACET_DATE_FROM => 'df',
            self::FACET_DATE_TO => 'dt',
            self::FACET_DOSSIER_NR => 'dnr',
        ];
    }

    public static function getQueryVarForFacet(string $facet): string
    {
        $mapping = self::getQueryMapping();

        return $mapping[$facet] ?? '';
    }
}

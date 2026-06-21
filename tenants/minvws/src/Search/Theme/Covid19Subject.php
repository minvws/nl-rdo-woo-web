<?php

declare(strict_types=1);

namespace WooMinVWS\Search\Theme;

use function array_map;

enum Covid19Subject: string
{
    case VACCINATIES_EN_MEDICATIE = 'COVID-19 Vaccinaties en medicatie';
    case SCENARIOS_EN_MAATREGELEN = 'COVID-19 Scenario\'s en maatregelen';
    case OVERLEG_VWS = 'COVID-19 Overleggen';
    case OVERLEG_OVERIG = 'COVID-19 Overleggen Overig';
    case DIGITALE_MIDDELEN = 'COVID-19 Digitale middelen';
    case RIVM = 'COVID-19 Woo-besluiten RIVM';
    case MEDISCHE_HULPMIDDELEN = 'COVID-19 Medische hulpmiddelen';
    case CAPACITEIT_ZIEKENHUIZEN = 'COVID-19 Capaciteit ziekenhuizen';
    case BESMETTELIJKE_KINDEREN = 'COVID-19 Besmettelijkheid kinderen';
    case TESTEN = 'COVID-19 Testen';
    case OPSTART_CORONA = 'Opstart Corona';

    /**
     * @return array<array-key, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn ($case) => $case->value,
            self::cases(),
        );
    }
}

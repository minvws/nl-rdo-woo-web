<?php

declare(strict_types=1);

namespace App\Domain\Search\Theme;

enum Covid19Subject: string
{
    case OPSTART_CORONA = 'Opstart Corona';
    case OVERLEG_VWS = 'Overleg VWS';
    case OVERLEG_OVERIG = 'Overleg overig';
    case RIVM = 'RIVM';
    case DIGITALE_MIDDELEN = 'Digitale middelen';
    case BESMETTELIJKE_KINDEREN = 'Besmettelijkheid kinderen';
    case SCENARIOS_EN_MAATREGELEN = 'Scenario\'s en maatregelen';
    case MEDISCHE_HULPMIDDELEN = 'Medische hulpmiddelen';
    case CAPACITEIT_ZIEKENHUIS = 'Capaciteit ziekenhuis';
    case TESTEN = 'Testen';
    case VACCINATIES_EN_MEDICATIE = 'Vaccinaties en medicatie';
    case CHATS = 'Chats';

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

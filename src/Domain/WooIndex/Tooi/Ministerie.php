<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Tooi;

/**
 * Based on https://standaarden.overheid.nl/diwoo/metadata/doc/0.9.4/diwoo-metadata-lijsten_xsd_Simple_Type_diwoo_ministerielijst.
 *
 * @SuppressWarnings("PHPMD.ConstantNamingConventions")
 */
enum Ministerie: string
{
    private const BASE_URI = 'https://identifier.overheid.nl/tooi/id/ministerie/';

    case mnre1010 = 'ministerie van Algemene Zaken';

    case mnre1162 = 'ministerie van Asiel en Migratie';

    case mnre1034 = 'ministerie van Binnenlandse Zaken en Koninkrijksrelaties';

    case mnre1013 = 'ministerie van Buitenlandse Zaken';

    case mnre1018 = 'ministerie van Defensie';

    case mnre1045 = 'ministerie van Economische Zaken';

    /**
     * @phpcs:disabled Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
     */
    public const mnre1040 = self::mnre1045;

    case mnre1090 = 'ministerie van Financiën';

    case mnre1130 = 'ministerie van Infrastructuur en Waterstaat';

    case mnre1058 = 'ministerie van Justitie en Veiligheid';

    case mnre1182 = 'ministerie van Klimaat en Groene Groei';

    case mnre1150 = 'ministerie van Landbouw, Natuur en Voedselkwaliteit';

    case mnre1153 = 'ministerie van Landbouw, Visserij, Voedselzekerheid en Natuur';

    case mnre1109 = 'ministerie van Onderwijs, Cultuur en Wetenschap';

    case mnre1073 = 'ministerie van Sociale Zaken en Werkgelegenheid';

    case mnre0170 = 'ministerie van Verkeer en Waterstaat';

    case mnre1025 = 'ministerie van Volksgezondheid, Welzijn en Sport';

    case mnre1171 = 'ministerie van Volkshuisvesting en Ruimtelijke Ordening';

    case mnre0180 = 'ministerie van Volkshuisvesting, Ruimtelijke Ordening en Milieubeheer';

    public function getResource(): string
    {
        return self::BASE_URI . $this->name;
    }
}

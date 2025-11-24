<?php

declare(strict_types=1);

namespace Shared\Tests\Story;

enum DepartmentEnum: string
{
    case AZ = 'ministerie van Algemene Zaken';
    case BZK = 'ministerie van Binnenlandse Zaken en Koninkrijksrelaties';
    case BZ = 'ministerie van Buitenlandse Zaken';
    case DEF = 'ministerie van Defensie';
    case EZK = 'ministerie van Economische Zaken en Klimaat';
    case FIN = 'ministerie van Financiën';
    case IW = 'ministerie van Infrastructuur en Waterstaat';
    case JV = 'ministerie van Justitie en Veiligheid';
    case LNV = 'ministerie van Landbouw, Natuur en Voedselkwaliteit';
    case OCW = 'ministerie van Onderwijs, Cultuur en Wetenschap';
    case SZW = 'ministerie van Sociale Zaken en Werkgelegenheid';
    case VWS = 'ministerie van Volksgezondheid, Welzijn en Sport';

    public function getShortTag(): string
    {
        return match ($this) {
            self::DEF => 'Def',
            self::FIN => 'Fin',
            self::IW => 'I&W',
            self::JV => 'J&V',
            default => $this->name,
        };
    }
}

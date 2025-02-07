<?php

declare(strict_types=1);

namespace App\Enum;

enum Department: string
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

    public static function tryFromShortTag(string $tag): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->getShortTag() === $tag) {
                return $case;
            }
        }

        return null;
    }

    public static function tryFromName(string $name): ?self
    {
        foreach (self::cases() as $case) {
            if (strtolower($case->value) === strtolower($name)) {
                return $case;
            }
        }

        return null;
    }

    public static function tryFromNameOrShortTag(string $input): ?self
    {
        return self::tryFromName($input) ?? self::tryFromShortTag($input);
    }

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

    public function equals(string $name): bool
    {
        $given = self::tryFromNameOrShortTag($name);

        if ($given === null) {
            return false;
        }

        return $given === $this;
    }
}

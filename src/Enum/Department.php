<?php

declare(strict_types=1);

namespace App\Enum;

enum Department: string
{
    case AZ = 'Ministerie van Algemene Zaken';
    case BZK = 'Ministerie van Binnenlandse Zaken en Koninkrijksrelaties';
    case BZ = 'Ministerie van Buitenlandse Zaken';
    case DEF = 'Ministerie van Defensie';
    case EZK = 'Ministerie van Economische Zaken en Klimaat';
    case FIN = 'Ministerie van Financiën';
    case IW = 'Ministerie van Infrastructuur en Waterstaat';
    case JV = 'Ministerie van Justitie en Veiligheid';
    case LNV = 'Ministerie van Landbouw, Natuur en Voedselkwaliteit';
    case OCW = 'Ministerie van Onderwijs, Cultuur en Wetenschap';
    case SZW = 'Ministerie van Sociale Zaken en Werkgelegenheid';
    case VWS = 'Ministerie van Volksgezondheid, Welzijn en Sport';

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
            if ($case->value === $name) {
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

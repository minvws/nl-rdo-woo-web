<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function strtolower;

/**
 * @phpstan-type AttachmentLanguageArray array{
 *   type: string,
 *   value: string,
 *   label: string,
 * }
 */
enum AttachmentLanguage: string implements TranslatableInterface
{
    public const TRANS_DOMAIN = 'attachment';

    // Sortering: NLD eerst, dan rest alfabetisch
    case NLD = 'NLD';
    case BUL = 'BUL';
    case DAN = 'DAN';
    case DEU = 'DEU';
    case ENG = 'ENG';
    case EST = 'EST';
    case FIN = 'FIN';
    case FRA = 'FRA';
    case FRY = 'FRY';
    case ELL = 'ELL';
    case HUN = 'HUN';
    case GLE = 'GLE';
    case ITA = 'ITA';
    case HRV = 'HRV';
    case LAV = 'LAV';
    case LIT = 'LIT';
    case MLT = 'MLT';
    case PAP_AW = 'PAP-AW';
    case PAP_CW = 'PAP-CW';
    case POL = 'POL';
    case POR = 'POR';
    case RON = 'RON';
    case SLV = 'SLV';
    case SLK = 'SLK';
    case SPA = 'SPA';
    case CES = 'CES';
    case SWE = 'SWE';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(
            strtolower($this->name),
            domain: self::TRANS_DOMAIN,
            locale: $locale,
        );
    }

    /**
     * @return array<string,string>
     *
     * @phpstan-return AttachmentLanguageArray
     */
    public function toArray(TranslatorInterface $translator, ?string $locale = null): array
    {
        return [
            'type' => 'AttachmentLanguage',
            'value' => $this->value,
            'label' => $this->trans($translator, $locale),
        ];
    }
}

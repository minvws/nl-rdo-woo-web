<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum DossierType: string implements TranslatableInterface
{
    case COVENANT = 'covenant';                         // Convenant
    case WOO_DECISION = 'woo-decision';                 // Woo-besluit
    case ANNUAL_REPORT = 'annual-report';               // Jaarplan of jaarverslag (JP)
    case INVESTIGATION_REPORT = 'investigation-report'; // Onderzoeksrapport (OR)
    case DISPOSITION = 'disposition';                   // Beschikking (BES)
    case COMPLAINT_JUDGEMENT = 'complaint-judgement';   // Klachtoordeel (KO)
    case OTHER_PUBLICATION = 'other-publication';       // Overig
    case ADVICE = 'advice';                             // Advies

    public function isCovenant(): bool
    {
        return $this === self::COVENANT;
    }

    public function isWooDecision(): bool
    {
        return $this === self::WOO_DECISION;
    }

    public function isAnnualReport(): bool
    {
        return $this === self::ANNUAL_REPORT;
    }

    public function isInvestigationReport(): bool
    {
        return $this === self::INVESTIGATION_REPORT;
    }

    public function isDisposition(): bool
    {
        return $this === self::DISPOSITION;
    }

    public function isComplaintJudgement(): bool
    {
        return $this === self::COMPLAINT_JUDGEMENT;
    }

    public function isOtherPublication(): bool
    {
        return $this === self::OTHER_PUBLICATION;
    }

    public function isAdvice(): bool
    {
        return $this === self::ADVICE;
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('dossier.type.' . $this->value, locale: $locale);
    }

    public function getValueForRouteName(): string
    {
        return str_replace('-', '', $this->value);
    }

    /**
     * @return list<string>
     */
    public static function getAllValues(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::cases(),
        );
    }
}

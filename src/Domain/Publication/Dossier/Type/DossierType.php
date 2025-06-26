<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type;

use App\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use App\Domain\Publication\Dossier\Type\Advice\Advice;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Disposition\Disposition;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use App\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
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
    case REQUEST_FOR_ADVICE = 'request-for-advice';     // Adviesaanvraag

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

    public function isRequestForAdvice(): bool
    {
        return $this === self::REQUEST_FOR_ADVICE;
    }

    public function hasAttachments(): bool
    {
        return is_subclass_of($this->getDossierClass(), EntityWithAttachments::class);
    }

    public function getDossierClass(): string
    {
        return match ($this) {
            self::COVENANT => Covenant::class,
            self::WOO_DECISION => WooDecision::class,
            self::ANNUAL_REPORT => AnnualReport::class,
            self::INVESTIGATION_REPORT => InvestigationReport::class,
            self::DISPOSITION => Disposition::class,
            self::COMPLAINT_JUDGEMENT => ComplaintJudgement::class,
            self::OTHER_PUBLICATION => OtherPublication::class,
            self::ADVICE => Advice::class,
            self::REQUEST_FOR_ADVICE => RequestForAdvice::class,
        };
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

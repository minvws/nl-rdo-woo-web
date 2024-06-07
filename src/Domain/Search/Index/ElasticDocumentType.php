<?php

declare(strict_types=1);

namespace App\Domain\Search\Index;

use App\Domain\Publication\Dossier\Type\DossierType;

enum ElasticDocumentType: string
{
    case WOO_DECISION = 'dossier';
    case WOO_DECISION_DOCUMENT = 'document';
    case COVENANT = 'covenant';
    case ANNUAL_REPORT = 'annual_report';
    case INVESTIGATION_REPORT = 'investigation_report';
    case DISPOSITION = 'disposition';
    case COMPLAINT_JUDGEMENT = 'complaint_judgement';

    public static function fromDossierType(DossierType $dossierType): self
    {
        return match ($dossierType) {
            DossierType::COVENANT => self::COVENANT,
            DossierType::WOO_DECISION => self::WOO_DECISION,
            DossierType::ANNUAL_REPORT => self::ANNUAL_REPORT,
            DossierType::INVESTIGATION_REPORT => self::INVESTIGATION_REPORT,
            DossierType::DISPOSITION => self::DISPOSITION,
            DossierType::COMPLAINT_JUDGEMENT => self::COMPLAINT_JUDGEMENT,
        };
    }

    /**
     * @return self[]
     */
    public static function getMainTypes(): array
    {
        return [
            self::WOO_DECISION,
            self::COVENANT,
            self::ANNUAL_REPORT,
            self::INVESTIGATION_REPORT,
            self::DISPOSITION,
            self::COMPLAINT_JUDGEMENT,
        ];
    }

    /**
     * @return self[]
     */
    public static function getSubTypes(): array
    {
        return [
            self::WOO_DECISION_DOCUMENT,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Shared\Domain\Publication\Dossier\Type\DossierType as DossierTypeEnum;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\PlainDate;

final readonly class CommonDossierProperties
{
    public function __construct(
        public string $dossierId,
        public string $dossierNr,
        public string $documentPrefix,
        public bool $isPreview,
        public DossierTitle $title,
        public PlainDate $publicationDate,
        public Department $mainDepartment,
        public string $summary,
        public DossierTypeEnum $type,
        public ?Subject $subject,
    ) {
    }
}

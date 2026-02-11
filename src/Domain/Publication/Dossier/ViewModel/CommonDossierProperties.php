<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use DateTimeImmutable;
use Shared\Domain\Publication\Dossier\Type\DossierType as DossierTypeEnum;

final readonly class CommonDossierProperties
{
    public function __construct(
        public string $dossierId,
        public string $dossierNr,
        public string $documentPrefix,
        public bool $isPreview,
        public string $title,
        public string $pageTitle,
        public DateTimeImmutable $publicationDate,
        public Department $mainDepartment,
        public string $summary,
        public DossierTypeEnum $type,
        public ?Subject $subject,
    ) {
    }
}

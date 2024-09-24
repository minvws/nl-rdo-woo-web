<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\ViewModel;

use App\Domain\Publication\Dossier\Type\DossierType as DossierTypeEnum;
use App\Domain\Publication\Dossier\ViewModel\Department;

/**
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
final readonly class CommonDossierProperties
{
    public function __construct(
        public string $dossierId,
        public string $dossierNr,
        public string $documentPrefix,
        public bool $isPreview,
        public string $title,
        public string $pageTitle,
        public \DateTimeImmutable $publicationDate,
        public Department $mainDepartment,
        public string $summary,
        public DossierTypeEnum $type,
        public ?string $subject,
    ) {
    }
}

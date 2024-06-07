<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\AnnualReport\ViewModel;

use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\ViewModel\Department;

/**
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
final readonly class AnnualReport
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
        public DossierType $type,
        public string $year,
    ) {
    }
}

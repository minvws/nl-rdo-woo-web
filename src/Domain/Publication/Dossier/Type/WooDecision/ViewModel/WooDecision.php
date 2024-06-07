<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Domain\Publication\Dossier\ViewModel\Department;
use App\Domain\Publication\Dossier\ViewModel\PublicationItem;
use App\Enum\Department as DepartmentEnum;
use Doctrine\Common\Collections\Collection;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
final readonly class WooDecision
{
    /**
     * @param Collection<array-key,Department> $departments
     */
    public function __construct(
        public DossierCounts $counts,
        public string $dossierId,
        public string $dossierNr,
        public string $documentPrefix,
        public bool $isPreview,
        public string $title,
        public string $pageTitle,
        public \DateTimeImmutable $publicationDate,
        public Department $mainDepartment,
        public Collection $departments,
        public string $summary,
        public bool $needsInventoryAndDocuments,
        public DecisionType $decision,
        public \DateTimeImmutable $decisionDate,
        public PublicationItem $decisionDocument,
        public ?\DateTimeImmutable $dateFrom,
        public ?\DateTimeImmutable $dateTo,
        public PublicationReason $publicationReason,
    ) {
    }

    public function isVwsResponsible(): bool
    {
        if ($this->departments->count() === 1) {
            /** @var Department $department */
            $department = $this->departments->first();

            Assert::isInstanceOf($department, Department::class);

            return $department->isDepartment(DepartmentEnum::VWS);
        }

        return false;
    }
}

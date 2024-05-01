<?php

declare(strict_types=1);

namespace App\ViewModel;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision as WooDecisionEntity;
use App\Entity\DecisionDocument;
use App\Entity\Department;
use App\Entity\Inventory;
use App\Enum\Department as EnumDepartment;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
final readonly class WooDecision
{
    public function __construct(
        private WooDecisionEntity $entity,
        public DossierCounts $counts,
        public string $dossierId,
        public string $dossierNr,
        public string $documentPrefix,
        public bool $isPreview,
        public string $title,
        public string $pageTitle,
        public \DateTimeImmutable $publicationDate,
        public Department $mainDepartment,
        public string $summary,
        public bool $needsInventoryAndDocuments,
        public string $decision,
        public \DateTimeImmutable $decisionDate,
        public ?Inventory $inventory,
        public DecisionDocument $decisionDocument,
        public ?\DateTimeImmutable $dateFrom,
        public ?\DateTimeImmutable $dateTo,
        public string $publicationReason,
    ) {
    }

    public function isVwsResponsible(): bool
    {
        if ($this->entity->getDepartments()->count() === 1) {
            $department = $this->entity->getDepartments()->first();

            Assert::isInstanceOf($department, Department::class);

            return EnumDepartment::VWS->equals($department->getName());
        }

        return false;
    }
}

<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierProperties;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesAccessors;
use Shared\Domain\Publication\Dossier\ViewModel\Department;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocument;

final readonly class WooDecision
{
    use CommonDossierPropertiesAccessors;

    /**
     * @param Collection<array-key,Department> $departments
     */
    public function __construct(
        private CommonDossierProperties $commonDossier,
        public DossierCounts $counts,
        public Collection $departments,
        public bool $isInventoryRequired,
        public bool $isInventoryOptional,
        public bool $canProvideInventory,
        public DecisionType $decision,
        public DateTimeImmutable $decisionDate,
        public MainDocument $mainDocument,
        public ?DateTimeImmutable $dateFrom,
        public ?DateTimeImmutable $dateTo,
        public PublicationReason $publicationReason,
        public string $documentSearchUrl,
    ) {
    }

    public function getResponsibilityContent(): ?string
    {
        foreach ($this->departments as $department) {
            if ($department->responsibilityContent !== null) {
                return $department->responsibilityContent;
            }
        }

        return null;
    }
}

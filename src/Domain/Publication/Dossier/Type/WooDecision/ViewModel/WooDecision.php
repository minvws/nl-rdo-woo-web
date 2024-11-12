<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

use App\Domain\Publication\Dossier\Type\CommonDossierPropertiesAccessors;
use App\Domain\Publication\Dossier\Type\ViewModel\CommonDossierProperties;
use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Domain\Publication\Dossier\ViewModel\Department;
use App\Domain\Publication\MainDocument\ViewModel\MainDocument;
use Doctrine\Common\Collections\Collection;

/**
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
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
        public bool $needsInventoryAndDocuments,
        public DecisionType $decision,
        public \DateTimeImmutable $decisionDate,
        public MainDocument $mainDocument,
        public ?\DateTimeImmutable $dateFrom,
        public ?\DateTimeImmutable $dateTo,
        public PublicationReason $publicationReason,
    ) {
    }
}

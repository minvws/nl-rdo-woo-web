<?php

declare(strict_types=1);

namespace App\Domain\Publication\BatchDownload\Type;

use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Doctrine\ORM\QueryBuilder;
use Webmozart\Assert\Assert;

readonly class WooDecisionBatchDownload implements BatchDownloadTypeInterface
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
    ) {
    }

    public function supports(BatchDownloadScope $scope): bool
    {
        return $scope->wooDecision instanceof WooDecision
            && $scope->inquiry === null;
    }

    public function getFileBaseName(BatchDownloadScope $scope): string
    {
        Assert::notNull($scope->wooDecision);

        return sprintf(
            '%s-%s',
            $scope->wooDecision->getDocumentPrefix(),
            $scope->wooDecision->getDossierNr(),
        );
    }

    public function getDocumentsQuery(BatchDownloadScope $scope): QueryBuilder
    {
        Assert::notNull($scope->wooDecision);

        return $this->wooDecisionRepository->getDocumentsForBatchDownload($scope->wooDecision);
    }

    public function isAvailableForBatchDownload(BatchDownloadScope $scope): bool
    {
        $query = $this->getDocumentsQuery($scope);

        /** @var int $count */
        $count = $query->select('count(doc)')->getQuery()->getSingleScalarResult();

        return $count > 0;
    }
}

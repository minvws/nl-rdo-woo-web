<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\BatchDownload\Type;

use Doctrine\ORM\QueryBuilder;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use Webmozart\Assert\Assert;

readonly class InquiryBatchDownload implements BatchDownloadTypeInterface
{
    public function __construct(
        private InquiryRepository $inquiryRepository,
    ) {
    }

    public function supports(BatchDownloadScope $scope): bool
    {
        return $scope->inquiry instanceof Inquiry
            && $scope->wooDecision === null;
    }

    public function getFileBaseName(BatchDownloadScope $scope): string
    {
        Assert::notNull($scope->inquiry);

        return $scope->inquiry->getCasenr();
    }

    public function getDocumentsQuery(BatchDownloadScope $scope): QueryBuilder
    {
        Assert::notNull($scope->inquiry);

        return $this->inquiryRepository->getDocumentsForBatchDownload($scope->inquiry);
    }

    public function isAvailableForBatchDownload(BatchDownloadScope $scope): bool
    {
        $query = $this->getDocumentsQuery($scope);

        /** @var int $count */
        $count = $query->select('count(doc)')->getQuery()->getSingleScalarResult();

        return $count > 0;
    }
}

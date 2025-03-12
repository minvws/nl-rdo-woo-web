<?php

declare(strict_types=1);

namespace App\Domain\Publication\BatchDownload\Type;

use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Doctrine\ORM\QueryBuilder;
use Webmozart\Assert\Assert;

readonly class InquiryDossierBatchDownload implements BatchDownloadTypeInterface
{
    public function __construct(
        private InquiryRepository $inquiryRepository,
    ) {
    }

    public function supports(BatchDownloadScope $scope): bool
    {
        return $scope->inquiry instanceof Inquiry
            && $scope->wooDecision instanceof WooDecision;
    }

    public function getFileBaseName(BatchDownloadScope $scope): string
    {
        Assert::notNull($scope->inquiry);
        Assert::notNull($scope->wooDecision);

        return sprintf(
            '%s-%s-%s',
            $scope->inquiry->getCasenr(),
            $scope->wooDecision->getDocumentPrefix(),
            $scope->wooDecision->getDossierNr(),
        );
    }

    public function getDocumentsQuery(BatchDownloadScope $scope): QueryBuilder
    {
        Assert::notNull($scope->inquiry);
        Assert::notNull($scope->wooDecision);

        return $this->inquiryRepository->getDocumentsForBatchDownload($scope->inquiry, $scope->wooDecision);
    }

    public function isAvailableForBatchDownload(BatchDownloadScope $scope): bool
    {
        Assert::notNull($scope->inquiry);
        Assert::notNull($scope->wooDecision);

        if (! $scope->wooDecision->getStatus()->isPubliclyAvailable()) {
            return false;
        }

        if ($scope->wooDecision->getUploadStatus()->getActualUploadCount() === 0) {
            return false;
        }

        foreach ($scope->inquiry->getDocuments() as $document) {
            if ($document->shouldBeUploaded() && $document->isUploaded()) {
                return true;
            }
        }

        return false;
    }
}

<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\BatchDownload\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\Type\InquiryDossierBatchDownload;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;

class InquiryDossierBatchDownloadTest extends UnitTestCase
{
    private InquiryRepository&MockInterface $repository;
    private InquiryDossierBatchDownload $type;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(InquiryRepository::class);

        $this->type = new InquiryDossierBatchDownload(
            $this->repository,
        );
    }

    public function testSupports(): void
    {
        $inquiry = Mockery::mock(Inquiry::class);
        $wooDecision = Mockery::mock(WooDecision::class);

        $scope = BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision);
        self::assertTrue($this->type->supports($scope));

        $scope = BatchDownloadScope::forWooDecision($wooDecision);
        self::assertFalse($this->type->supports($scope));

        $scope = BatchDownloadScope::forInquiry($inquiry);
        self::assertFalse($this->type->supports($scope));
    }

    public function testGetFileBasename(): void
    {
        $inquiry = Mockery::mock(Inquiry::class);
        $inquiry->expects('getInquiryNumber')->andReturn('CASE-X');

        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getDocumentPrefix')->andReturn('FOO');
        $wooDecision->expects('getDossierNr')->andReturn('BAR-123');
        $scope = BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision);

        self::assertEquals(
            'CASE-X-FOO-BAR-123',
            $this->type->getFileBaseName($scope),
        );
    }

    public function testGetDocumentsQuery(): void
    {
        $inquiry = Mockery::mock(Inquiry::class);
        $wooDecision = Mockery::mock(WooDecision::class);
        $queryBuilder = Mockery::mock(QueryBuilder::class);

        $this->repository
            ->expects('getDocumentsForBatchDownload')
            ->with($inquiry, $wooDecision)
            ->andReturn($queryBuilder);

        $scope = BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision);

        self::assertEquals(
            $queryBuilder,
            $this->type->getDocumentsQuery($scope),
        );
    }

    public function testIsAvailableForBatchDownloadReturnsFalseForNonPublicWooDecision(): void
    {
        $inquiry = Mockery::mock(Inquiry::class);
        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getStatus')->andReturn(DossierStatus::CONCEPT);

        $scope = BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision);

        self::assertFalse($this->type->isAvailableForBatchDownload($scope));
    }

    public function testIsAvailableForBatchDownloadReturnsFalseForWooDecisionWithoutUploads(): void
    {
        $inquiry = Mockery::mock(Inquiry::class);
        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $wooDecision->expects('getUploadStatus->getActualUploadCount')->andReturn(0);

        $scope = BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision);

        self::assertFalse($this->type->isAvailableForBatchDownload($scope));
    }

    public function testIsAvailableForBatchDownloadReturnsTrueForPublicWooDecisionWithUploads(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('shouldBeUploaded')->andReturnTrue();
        $document->expects('isUploaded')->andReturnTrue();

        $inquiry = Mockery::mock(Inquiry::class);
        $inquiry->expects('getDocuments')->andReturn(new ArrayCollection([$document]));

        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $wooDecision->expects('getUploadStatus->getActualUploadCount')->andReturn(12);

        $scope = BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision);

        self::assertTrue($this->type->isAvailableForBatchDownload($scope));
    }
}

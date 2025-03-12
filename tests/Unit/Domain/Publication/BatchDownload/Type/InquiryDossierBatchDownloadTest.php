<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\BatchDownload\Type;

use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\BatchDownload\Type\InquiryDossierBatchDownload;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class InquiryDossierBatchDownloadTest extends MockeryTestCase
{
    private InquiryRepository&MockInterface $repository;
    private InquiryDossierBatchDownload $type;

    public function setup(): void
    {
        $this->repository = \Mockery::mock(InquiryRepository::class);

        $this->type = new InquiryDossierBatchDownload(
            $this->repository,
        );
    }

    public function testSupports(): void
    {
        $inquiry = \Mockery::mock(Inquiry::class);
        $wooDecision = \Mockery::mock(WooDecision::class);

        $scope = BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision);
        self::assertTrue($this->type->supports($scope));

        $scope = BatchDownloadScope::forWooDecision($wooDecision);
        self::assertFalse($this->type->supports($scope));

        $scope = BatchDownloadScope::forInquiry($inquiry);
        self::assertFalse($this->type->supports($scope));
    }

    public function testGetFileBasename(): void
    {
        $inquiry = \Mockery::mock(Inquiry::class);
        $inquiry->shouldReceive('getCaseNr')->andReturn('CASE-X');

        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getDocumentPrefix')->andReturn('FOO');
        $wooDecision->shouldReceive('getDossierNr')->andReturn('BAR-123');
        $scope = BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision);

        self::assertEquals(
            'CASE-X-FOO-BAR-123',
            $this->type->getFileBaseName($scope),
        );
    }

    public function testGetDocumentsQuery(): void
    {
        $inquiry = \Mockery::mock(Inquiry::class);
        $wooDecision = \Mockery::mock(WooDecision::class);
        $queryBuilder = \Mockery::mock(QueryBuilder::class);

        $this->repository
            ->shouldReceive('getDocumentsForBatchDownload')
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
        $inquiry = \Mockery::mock(Inquiry::class);
        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);

        $scope = BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision);

        self::assertFalse($this->type->isAvailableForBatchDownload($scope));
    }

    public function testIsAvailableForBatchDownloadReturnsFalseForWooDecisionWithoutUploads(): void
    {
        $inquiry = \Mockery::mock(Inquiry::class);
        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $wooDecision->shouldReceive('getUploadStatus->getActualUploadCount')->andReturn(0);

        $scope = BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision);

        self::assertFalse($this->type->isAvailableForBatchDownload($scope));
    }

    public function testIsAvailableForBatchDownloadReturnsTrueForPublicWooDecisionWithUploads(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('shouldBeUploaded')->andReturnTrue();
        $document->shouldReceive('isUploaded')->andReturnTrue();

        $inquiry = \Mockery::mock(Inquiry::class);
        $inquiry->shouldReceive('getDocuments')->andReturn(new ArrayCollection([$document]));

        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $wooDecision->shouldReceive('getUploadStatus->getActualUploadCount')->andReturn(12);

        $scope = BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision);

        self::assertTrue($this->type->isAvailableForBatchDownload($scope));
    }
}

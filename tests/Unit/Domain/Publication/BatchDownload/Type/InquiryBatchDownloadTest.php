<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\BatchDownload\Type;

use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\BatchDownload\Type\InquiryBatchDownload;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class InquiryBatchDownloadTest extends MockeryTestCase
{
    private InquiryRepository&MockInterface $repository;
    private InquiryBatchDownload $type;

    public function setup(): void
    {
        $this->repository = \Mockery::mock(InquiryRepository::class);

        $this->type = new InquiryBatchDownload(
            $this->repository,
        );
    }

    public function testSupports(): void
    {
        $inquiry = \Mockery::mock(Inquiry::class);
        $scope = BatchDownloadScope::forInquiry($inquiry);

        self::assertTrue($this->type->supports($scope));

        $wooDecision = \Mockery::mock(WooDecision::class);
        $scope = BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision);

        self::assertFalse($this->type->supports($scope));
    }

    public function testGetFileBasename(): void
    {
        $inquiry = \Mockery::mock(Inquiry::class);
        $inquiry->shouldReceive('getCasenr')->andReturn($caseNr = 'foo-123');
        $scope = BatchDownloadScope::forInquiry($inquiry);

        self::assertEquals(
            $caseNr,
            $this->type->getFileBaseName($scope),
        );
    }

    public function testGetDocumentsQuery(): void
    {
        $inquiry = \Mockery::mock(Inquiry::class);
        $queryBuilder = \Mockery::mock(QueryBuilder::class);

        $this->repository
            ->shouldReceive('getDocumentsForBatchDownload')
            ->with($inquiry)
            ->andReturn($queryBuilder);

        $scope = BatchDownloadScope::forInquiry($inquiry);

        self::assertEquals(
            $queryBuilder,
            $this->type->getDocumentsQuery($scope),
        );
    }

    public function testIsAvailableForBatchDownloadReturnsTrueForAtLeastOneUploadedDocument(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('shouldBeUploaded')->andReturnTrue();
        $document->shouldReceive('isUploaded')->andReturnTrue();

        $inquiry = \Mockery::mock(Inquiry::class);
        $inquiry->shouldReceive('getDocuments')->andReturn(new ArrayCollection([$document]));

        $scope = BatchDownloadScope::forInquiry($inquiry);

        self::assertTrue($this->type->isAvailableForBatchDownload($scope));
    }

    public function testIsAvailableForBatchDownloadReturnsFalseIfThereAreNoUploadedDocuments(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('shouldBeUploaded')->andReturnFalse();
        $document->shouldReceive('isUploaded')->andReturnFalse();

        $inquiry = \Mockery::mock(Inquiry::class);
        $inquiry->shouldReceive('getDocuments')->andReturn(new ArrayCollection([$document]));

        $scope = BatchDownloadScope::forInquiry($inquiry);

        self::assertFalse($this->type->isAvailableForBatchDownload($scope));
    }
}

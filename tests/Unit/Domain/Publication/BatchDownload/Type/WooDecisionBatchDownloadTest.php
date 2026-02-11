<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\BatchDownload\Type;

use Doctrine\ORM\QueryBuilder;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\Type\WooDecisionBatchDownload;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Tests\Unit\UnitTestCase;

class WooDecisionBatchDownloadTest extends UnitTestCase
{
    private WooDecisionRepository&MockInterface $repository;
    private WooDecisionBatchDownload $type;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(WooDecisionRepository::class);

        $this->type = new WooDecisionBatchDownload(
            $this->repository,
        );
    }

    public function testSupports(): void
    {
        $wooDecision = Mockery::mock(WooDecision::class);
        $scope = BatchDownloadScope::forWooDecision($wooDecision);

        self::assertTrue($this->type->supports($scope));

        $inquiry = Mockery::mock(Inquiry::class);
        $scope = BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision);

        self::assertFalse($this->type->supports($scope));
    }

    public function testGetFileBasename(): void
    {
        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getDocumentPrefix')->andReturn('FOO');
        $wooDecision->shouldReceive('getDossierNr')->andReturn('BAR-123');
        $scope = BatchDownloadScope::forWooDecision($wooDecision);

        self::assertEquals(
            'FOO-BAR-123',
            $this->type->getFileBaseName($scope),
        );
    }

    public function testGetDocumentsQuery(): void
    {
        $wooDecision = Mockery::mock(WooDecision::class);
        $queryBuilder = Mockery::mock(QueryBuilder::class);

        $this->repository
            ->shouldReceive('getDocumentsForBatchDownload')
            ->with($wooDecision)
            ->andReturn($queryBuilder);

        $scope = BatchDownloadScope::forWooDecision($wooDecision);

        self::assertEquals(
            $queryBuilder,
            $this->type->getDocumentsQuery($scope),
        );
    }

    public function testIsAvailableForBatchDownloadReturnsFalseForNoPublicDocumentsFound(): void
    {
        $wooDecision = Mockery::mock(WooDecision::class);
        $scope = BatchDownloadScope::forWooDecision($wooDecision);
        $queryBuilder = Mockery::mock(QueryBuilder::class);

        $this->repository
            ->shouldReceive('getDocumentsForBatchDownload')
            ->with($wooDecision)
            ->andReturn($queryBuilder);

        $queryBuilder->expects('select')->with('count(doc)')->andReturnSelf();
        $queryBuilder->expects('getQuery->getSingleScalarResult')->andReturn(0);

        self::assertFalse($this->type->isAvailableForBatchDownload($scope));
    }

    public function testIsAvailableForBatchDownloadReturnsTrueForAtLeastOnePublicDocumentFound(): void
    {
        $wooDecision = Mockery::mock(WooDecision::class);
        $scope = BatchDownloadScope::forWooDecision($wooDecision);
        $queryBuilder = Mockery::mock(QueryBuilder::class);

        $this->repository
            ->shouldReceive('getDocumentsForBatchDownload')
            ->with($wooDecision)
            ->andReturn($queryBuilder);

        $queryBuilder->expects('select')->with('count(doc)')->andReturnSelf();
        $queryBuilder->expects('getQuery->getSingleScalarResult')->andReturn(1);

        self::assertTrue($this->type->isAvailableForBatchDownload($scope));
    }
}

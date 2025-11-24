<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\BatchDownload;

use Shared\Domain\Publication\BatchDownload\BatchDownload;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;

class BatchDownloadScopeTest extends UnitTestCase
{
    public function testForWooDecision(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $scope = BatchDownloadScope::forWooDecision($wooDecision);

        $this->assertEquals($wooDecision, $scope->wooDecision);
        $this->assertNull($scope->inquiry);
    }

    public function testForInquiry(): void
    {
        $inquiry = \Mockery::mock(Inquiry::class);
        $scope = BatchDownloadScope::forInquiry($inquiry);

        $this->assertEquals($inquiry, $scope->inquiry);
        $this->assertNull($scope->wooDecision);
    }

    public function testForInquiryAndWooDecision(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $inquiry = \Mockery::mock(Inquiry::class);
        $scope = BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision);

        $this->assertEquals($wooDecision, $scope->wooDecision);
        $this->assertEquals($inquiry, $scope->inquiry);
    }

    public function testFromBatch(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $inquiry = \Mockery::mock(Inquiry::class);

        $batch = \Mockery::mock(BatchDownload::class);
        $batch->shouldReceive('getDossier')->andReturn($wooDecision);
        $batch->shouldReceive('getInquiry')->andReturn($inquiry);

        $scope = BatchDownloadScope::fromBatch($batch);

        $this->assertEquals($wooDecision, $scope->wooDecision);
        $this->assertEquals($inquiry, $scope->inquiry);
    }
}

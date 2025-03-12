<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\BatchDownload;

use App\Domain\Publication\BatchDownload\BatchDownload;
use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\BatchDownload\BatchDownloadStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class BatchDownloadTest extends MockeryTestCase
{
    public function testConstructor(): void
    {
        $inquiry = \Mockery::mock(Inquiry::class);
        $wooDecision = \Mockery::mock(WooDecision::class);

        $entity = new BatchDownload(
            BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision),
            $expiration = new \DateTimeImmutable('+1 day'),
        );

        $this->assertEquals(BatchDownloadStatus::PENDING, $entity->getStatus());
        $this->assertEquals($expiration, $entity->getExpiration());
        $this->assertEquals($wooDecision, $entity->getDossier());
        $this->assertEquals($inquiry, $entity->getInquiry());
    }

    public function testMarkAsOutdated(): void
    {
        $inquiry = \Mockery::mock(Inquiry::class);
        $wooDecision = \Mockery::mock(WooDecision::class);

        $entity = new BatchDownload(
            BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision),
            new \DateTimeImmutable('+1 day'),
        );

        $this->assertEquals(BatchDownloadStatus::PENDING, $entity->getStatus());

        $entity->complete(
            $fileName = 'foo.zip',
            $fileSize = '123',
            $fileCount = 456,
        );

        $this->assertEquals(BatchDownloadStatus::COMPLETED, $entity->getStatus());
        $this->assertEquals($fileSize, $entity->getSize());
        $this->assertEquals($fileCount, $entity->getFileCount());
        $this->assertEquals($fileName, $entity->getFilename());
        $this->assertTrue($entity->canBeDownloaded());

        $entity->markAsOutdated();

        $this->assertEquals(BatchDownloadStatus::OUTDATED, $entity->getStatus());
        $this->assertTrue($entity->getExpiration() < new \DateTimeImmutable('+3 hours'));
        $this->assertTrue($entity->canBeDownloaded()); // Still possible due to grace period of two hours!
    }

    public function testMarkAsFailed(): void
    {
        $inquiry = \Mockery::mock(Inquiry::class);
        $wooDecision = \Mockery::mock(WooDecision::class);

        $entity = new BatchDownload(
            BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision),
            $expiration = new \DateTimeImmutable('+1 day'),
        );

        $this->assertEquals(BatchDownloadStatus::PENDING, $entity->getStatus());
        $this->assertEquals($expiration, $entity->getExpiration());

        $entity->markAsFailed();

        $this->assertEquals(BatchDownloadStatus::FAILED, $entity->getStatus());
        $this->assertEquals('0', $entity->getSize());
    }

    public function testComplete(): void
    {
        $inquiry = \Mockery::mock(Inquiry::class);
        $wooDecision = \Mockery::mock(WooDecision::class);

        $entity = new BatchDownload(
            BatchDownloadScope::forInquiryAndWooDecision($inquiry, $wooDecision),
            new \DateTimeImmutable('+1 day'),
        );

        $this->assertEquals(BatchDownloadStatus::PENDING, $entity->getStatus());

        $entity->complete(
            $fileName = 'foo.zip',
            $fileSize = '123',
            $fileCount = 456,
        );

        $this->assertEquals(BatchDownloadStatus::COMPLETED, $entity->getStatus());
        $this->assertEquals($fileSize, $entity->getSize());
        $this->assertEquals($fileCount, $entity->getFileCount());
        $this->assertEquals($fileName, $entity->getFilename());
        $this->assertTrue($entity->canBeDownloaded());
    }
}

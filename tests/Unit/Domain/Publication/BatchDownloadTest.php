<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication;

use App\Domain\Publication\BatchDownload;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\EntityWithBatchDownload;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class BatchDownloadTest extends MockeryTestCase
{
    public function testSetAndGetExpiration(): void
    {
        $entity = new BatchDownload();
        $entity->setExpiration($expiration = new \DateTimeImmutable());

        $this->assertEquals($expiration, $entity->getExpiration());
    }

    public function testSetAndGetSize(): void
    {
        $entity = new BatchDownload();
        $entity->setSize($size = '123');

        $this->assertEquals($size, $entity->getSize());
    }

    public function testSetAndGetEntityWithWooDecision(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);

        $download = new BatchDownload();
        $download->setEntity($dossier);

        $this->assertEquals($dossier, $download->getEntity());
    }

    public function testSetAndGetEntityWithInquiry(): void
    {
        $inquiry = \Mockery::mock(Inquiry::class);

        $download = new BatchDownload();
        $download->setEntity($inquiry);

        $this->assertEquals($inquiry, $download->getEntity());
    }

    public function testSetEntityThrowsExceptionForUnsupportedEntity(): void
    {
        $entity = \Mockery::mock(EntityWithBatchDownload::class);

        $download = new BatchDownload();

        $this->expectException(\RuntimeException::class);
        $download->setEntity($entity);
    }

    public function testGetEntityThrowsExceptionWhenNoEntityIsSet(): void
    {
        $download = new BatchDownload();

        $this->expectException(\RuntimeException::class);
        $download->getEntity();
    }
}

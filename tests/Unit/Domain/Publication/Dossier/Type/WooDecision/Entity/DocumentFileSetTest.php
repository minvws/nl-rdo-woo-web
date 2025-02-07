<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Entity;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileSetStatus;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DocumentFileSetTest extends MockeryTestCase
{
    public function testGetters(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $documentFileSet = new DocumentFileSet($dossier);

        self::assertEquals(DocumentFileSetStatus::OPEN_FOR_UPLOADS, $documentFileSet->getStatus());
        self::assertEquals($dossier, $documentFileSet->getDossier());
        self::assertCount(0, $documentFileSet->getUploads());
        self::assertCount(0, $documentFileSet->getUpdates());
    }

    public function testSetAndGetStatus(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $documentFileSet = new DocumentFileSet($dossier);
        $documentFileSet->setStatus(DocumentFileSetStatus::COMPLETED);

        self::assertEquals(DocumentFileSetStatus::COMPLETED, $documentFileSet->getStatus());
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Tests\Unit\UnitTestCase;

final class CovenantAttachmentTest extends UnitTestCase
{
    public function testAllowedTypes(): void
    {
        $this->assertMatchesJsonSnapshot(CovenantAttachment::getAllowedTypes());
    }

    public function testGetFileCacheKey(): void
    {
        $attachment = new CovenantAttachment(
            \Mockery::mock(Covenant::class),
            new \DateTimeImmutable(),
            AttachmentType::ADVICE,
            AttachmentLanguage::DUTCH,
        );

        self::assertEquals('CovenantAttachment-' . $attachment->getId()->toBase58(), $attachment->getFileCacheKey());
    }
}

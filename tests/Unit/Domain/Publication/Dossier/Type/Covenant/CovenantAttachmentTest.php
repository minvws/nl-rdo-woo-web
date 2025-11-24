<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\Covenant;

use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Tests\Unit\UnitTestCase;

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

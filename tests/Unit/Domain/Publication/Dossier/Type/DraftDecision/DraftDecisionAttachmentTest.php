<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\DraftDecision;

use Mockery;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionAttachment;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\PlainDate;

final class DraftDecisionAttachmentTest extends UnitTestCase
{
    public function testAllowedTypes(): void
    {
        $this->assertMatchesJsonSnapshot(DraftDecisionAttachment::getAllowedTypes());
    }

    public function testGetFileCacheKey(): void
    {
        $attachment = new DraftDecisionAttachment(
            Mockery::mock(DraftDecision::class),
            PlainDate::today(),
            AttachmentType::POLICY_NOTE,
            AttachmentLanguage::NLD,
        );

        self::assertEquals('DraftDecisionAttachment-' . $attachment->getId()->toBase58(), $attachment->getFileCacheKey());
    }
}
